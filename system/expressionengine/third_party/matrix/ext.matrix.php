<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


require_once PATH_THIRD.'matrix/config.php';


/**
 * Matrix Extension Class for ExpressionEngine 2
 *
 * @package   Matrix
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Matrix_ext {

	var $name = MATRIX_NAME;
	var $version = MATRIX_VER;
	var $settings_exist = 'n';
	var $docs_url = 'http://pixelandtonic.com/matrix';

	/**
	 * Extension Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['matrix']))
		{
			$this->EE->session->cache['matrix'] = array();
		}
		$this->cache =& $this->EE->session->cache['matrix'];
	}

	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		$this->EE->db->insert('extensions', array(
			'class'    => 'Matrix_ext',
			'method'   => 'channel_entries_tagdata',
			'hook'     => 'channel_entries_tagdata',
			'settings' => '',
			'priority' => 10,
			'version'  => $this->version,
			'enabled'  => 'y'
		));
	}

	/**
	 * Update Extension
	 */
	function update_extension($current = FALSE)
	{
		if (! $current || $current == $this->version)
		{
			return FALSE;
		}

		$this->EE->db->where('class', 'Matrix_ext');
		$this->EE->db->update('extensions', array('version' => $this->version));
	}

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		$this->EE->db->query('DELETE FROM exp_extensions WHERE class = "Matrix_ext"');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Site Fields
	 */
	private function _get_site_fields($site_id)
	{
		if (! isset($this->cache['fields'][$site_id]))
		{
			$this->EE->db->select('field_id, field_name, field_settings')
			             ->where('field_type', 'matrix');

			if ($site_id)
			{
				$this->EE->db->where('site_id', $site_id);
			}

			$query = $this->EE->db->get('channel_fields');

			$fields = $query->result_array();

			foreach ($fields as &$field)
			{
				$field['field_settings'] = unserialize(base64_decode($field['field_settings']));
			}

			$this->cache['fields'][$site_id] = $fields;
		}

		return $this->cache['fields'][$site_id];
	}

	/**
	 * Parse Tag Pair
	 */
	private function _parse_tag_pair($m)
	{
		// prevent {exp:channel:entries} from double-parsing this tag
		unset($this->EE->TMPL->var_pair[$m[1]]);

		//$params_str = isset($m[2]) ? $m[2] : '';
		$tagdata = isset($m[3]) ? $m[3] : '';

		// get the params
		$params = array();
		if (isset($m[2]) && $m[2] && preg_match_all('/\s+([\w-:]+)\s*=\s*([\'\"])([^\2]*)\2/sU', $m[2], $param_matches))
		{
			for ($i = 0; $i < count($param_matches[0]); $i++)
			{
				$params[$param_matches[1][$i]] = $param_matches[3][$i];
			}
		}

		// get the tagdata
		$tagdata = isset($m[3]) ? $m[3] : '';

		// -------------------------------------------
		//	Call the tag's method
		// -------------------------------------------

		if (! class_exists('Matrix_ft'))
		{
			require_once PATH_THIRD.'matrix/ft.matrix'.EXT;
		}

		$Matrix_ft = new Matrix_ft();
		$Matrix_ft->row = $this->row;
		$Matrix_ft->field_id = $this->field['field_id'];
		$Matrix_ft->field_name = $this->field['field_name'];
		$Matrix_ft->entry_id = $this->row['entry_id'];
		$Matrix_ft->settings = array_merge($this->row, $this->field['field_settings']);

		return (string) $Matrix_ft->replace_tag(NULL, $params, $tagdata);
	}

	/**
	 * channel_entries_tagdata hook
	 */
	function channel_entries_tagdata($tagdata, $row)
	{
		// has this hook already been called?
		if (isset($this->EE->extensions->last_call) && $this->EE->extensions->last_call)
		{
			$tagdata = $this->EE->extensions->last_call;
		}

		$this->row = $row;

		// get the fields
		$entry_site_id = isset($row['entry_site_id']) ? $row['entry_site_id'] : 0;
		$fields = $this->_get_site_fields($entry_site_id);

		// iterate through each Matrix field
		foreach ($fields as $field)
		{
			if (strpos($tagdata, '{'.$field['field_name']) !== FALSE)
			{
				$this->field = $field;
				$tagdata = preg_replace_callback("/\{({$field['field_name']}(\s+.*?)?)\}(.*?)\{\/{$field['field_name']}\}/s", array(&$this, '_parse_tag_pair'), $tagdata);
			}
		}

		unset($this->row, $this->field);

		return $tagdata;
	}

}
