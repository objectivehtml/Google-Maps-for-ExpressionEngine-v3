<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'field_editor/config.php';

class Field_editor_upd
{
	public $version = FIELD_EDITOR_VERSION;
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */	
	public function install()
	{
		if (version_compare(APP_VER, '2.4.0', '<'))
		{
			show_error('You must have ExpressionEngine 2.4+ installed');
		}
		
		$data = array(
			'module_name' => 'Field_editor',
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);

		$this->EE->db->insert('modules', $data);
		
		require_once PATH_THIRD.'field_editor/ext.field_editor.php';
		
		$ext = new Field_editor_ext;
		
		$ext->activate_extension();
		
		return TRUE;
	}
	
	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	public function uninstall()
	{
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Field_editor'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Field_editor');
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', 'Field_editor_mcp');
		$this->EE->db->delete('actions');
		
		require_once PATH_THIRD.'field_editor/ext.field_editor.php';
		
		$ext = new Field_editor_ext;
		
		$ext->disable_extension();

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	public function update($current='')
	{
		return TRUE;
	}
	
}

/* End of file upd.field_editor.php */
/* Location: ./system/expressionengine/third_party/field_editor/upd.field_editor.php */