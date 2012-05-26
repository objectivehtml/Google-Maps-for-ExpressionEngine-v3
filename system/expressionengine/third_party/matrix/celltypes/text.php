<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Text Celltype Class for EE2
 * 
 * @package   Matrix
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Matrix_text_ft {

	var $info = array(
		'name' => 'Text'
	);

	var $default_settings = array(
		'maxl' => '',
		'multiline' => 'n',
		'fmt' => 'none',
		'dir' => 'ltr',
		'content' => 'all'
	);

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['matrix']['celltypes']['text']))
		{
			$this->EE->session->cache['matrix']['celltypes']['text'] = array();
		}
		$this->cache =& $this->EE->session->cache['matrix']['celltypes']['text'];
	}

	/**
	 * Prep Settings
	 */
	private function _prep_settings(&$settings)
	{
		$settings = array_merge($this->default_settings, $settings);
		if ($settings['content'] == 'any') $settings['content'] = 'all';
	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell Settings
	 */
	function display_cell_settings($data)
	{
		$this->_prep_settings($data);

		$field_content_options = array('all' => lang('all'), 'numeric' => lang('type_numeric'), 'integer' => lang('type_integer'), 'decimal' => lang('type_decimal'));

		return array(
			array(lang('maxl'), form_input('maxl', $data['maxl'], 'class="matrix-textarea"')),
			array(lang('multiline'), form_checkbox('multiline', 'y', ($data['multiline'] == 'y'))),
			array(lang('formatting'), form_dropdown('fmt', $data['field_fmt_options'], $data['fmt'])),
			//array(lang('direction'), form_dropdown('dir', array('ltr'=>lang('ltr'), 'rtl'=>lang('rtl')), $data['dir'])),
			array(lang('content'), form_dropdown('content', $field_content_options, $data['content']))
		);
	}

	/**
	 * Modify exp_matrix_data Column Settings
	 */
	function settings_modify_matrix_column($data)
	{
		// decode the field settings
		$settings = unserialize(base64_decode($data['col_settings']));

		if (isset($settings['content']))
		{
			switch ($settings['content'])
			{
				case 'numeric':
					return array('col_id_'.$data['col_id'] => array(
						'type'    => 'FLOAT',
						'default' => 0
					));

				case 'integer':
					return array('col_id_'.$data['col_id'] => array(
						'type'    => 'INT',
						'default' => 0
					));

				case 'decimal':
					return array('col_id_'.$data['col_id'] => array(
						'type'    => 'DECIMAL(10,4)',
						'default' => 0
					));
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display Cell
	 */
	function display_cell($data)
	{
		$this->_prep_settings($this->settings);

		if (! isset($this->cache['displayed']))
		{
			// include matrix_text.js
			$theme_url = $this->EE->session->cache['matrix']['theme_url'];
			$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$theme_url.'scripts/matrix_text.js"></script>');

			$this->cache['displayed'] = TRUE;
		}

		if ($this->settings['content'] == 'decimal')
		{
			$data = rtrim($data, '0.');
		}

		if ($this->settings['content'] == 'all')
		{
			// encode ampersands
			$data = str_replace('&', '&amp;', $data);
		}

		$r['class'] = 'matrix-text';
		$r['data'] = '<textarea class="matrix-textarea" name="'.$this->cell_name.'" rows="1" dir="'.$this->settings['dir'].'">'.$data.'</textarea>';

		if ($this->settings['maxl'])
		{
			$r['data'] .= '<div class="matrix-charsleft-container"><div class="matrix-charsleft"></div></div>';
		}

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate Cell
	 */
	function validate_cell($data)
	{
		// is this a required column?
		if ($this->settings['col_required'] == 'y' && ! strlen($data))
		{
			return lang('col_required');
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Pre-process
	 */
	function pre_process($data)
	{
		$this->_prep_settings($this->settings);

		if ($this->settings['content'] == 'all')
		{
			$this->EE->load->library('typography');

			$data = $this->EE->typography->parse_type(
				$this->EE->functions->encode_ee_tags($data),
				array(
					'text_format'	=> $this->settings['fmt'],
					'html_format'	=> $this->row['channel_html_formatting'],
					'auto_links'	=> $this->row['channel_auto_link_urls'],
					'allow_img_url' => $this->row['channel_allow_img_urls']
				)
			);
		}

		return $data;
	}

	/**
	 * Replace Tag
	 */
	function replace_tag($data, $params = array())
	{
		$this->_prep_settings($this->settings);

		if ($this->settings['content'] != 'all')
		{
			$decimals = isset($params['decimals']) ? (int) $params['decimals'] : strlen(substr(strrchr($data, "."), 1));;
			$dec_point = isset($params['dec_point']) ? $params['dec_point'] : '.';
			$thousands_sep = isset($params['thousands_sep']) ? $params['thousands_sep'] : ',';

			$data = number_format($data, $decimals, $dec_point, $thousands_sep);
		}

		return $data;
	}

}
