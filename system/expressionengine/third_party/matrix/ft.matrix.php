<?php if (! defined('BASEPATH')) exit('No direct script access allowed');


require_once PATH_THIRD.'matrix/config.php';


/**
 * Matrix Fieldtype Class for EE2
 *
 * @package   Matrix
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @copyright Copyright (c) 2011 Pixel & Tonic, Inc
 */
class Matrix_ft extends EE_Fieldtype {

	var $info = array(
		'name'    => MATRIX_NAME,
		'version' => MATRIX_VER
	);

	var $has_array_data = TRUE;

	var $bundled_celltypes = array('text', 'date', 'file');

	/**
	 * Fieldtype Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// -------------------------------------------
		//  Prepare Cache
		// -------------------------------------------

		if (! isset($this->EE->session->cache['matrix']))
		{
			$this->EE->session->cache['matrix'] = array('celltypes' => array());
		}
		$this->cache =& $this->EE->session->cache['matrix'];
	}

	// --------------------------------------------------------------------

	/**
	 * Update Field Column Associations
	 *
	 * Before Matrix 2.2, Matrix would associate Matrix columns to fields via the fields’ col_ids setting.
	 * But now (on EE2 only), those associations are made via the exp_matrix_cols.field_id column.
	 * This function populates that field_id column accordingly, and also duplicates any Matrix columns that belong to more than one field (via MSM field duplication)
	 */
	function _update_field_col_associations($field_id = FALSE)
	{
		$this->EE->load->dbforge();

		$affected_cols = 0;

		// get each of the Matrix fields
		$this->EE->db->select('field_id, site_id, field_settings')
		             ->where('field_type', 'matrix');

		if ($field_id)
		{
			$this->EE->db->where('field_id', $field_id);
		}

		$fields = $this->EE->db->order_by('site_id')
		                       ->get('channel_fields');

		if ($fields->num_rows())
		{
			foreach ($fields->result() as $field)
			{
				// unserialize the field settings
				$field_settings = unserialize(base64_decode($field->field_settings));

				$new_col_ids = array();

				// make sure the col_ids setting is in-tact
				if (isset($field_settings['col_ids']))
				{
					foreach (array_filter($field_settings['col_ids']) as $col_id)
					{
						// get the column data
						$col = $this->EE->db->get_where('matrix_cols', array('col_id' => $col_id));

						if ($col->num_rows())
						{
							$data = $col->row_array();

							// does this col already belong to this field?
							if ($data['field_id'] == $field->field_id)
							{
								$new_col_ids[] = $col_id;

								continue;
							}

							$affected_cols++;

							// does it belong to another?
							if ($data['field_id'])
							{
								// get the existing columns
								$celltype = $this->_get_celltype($data['col_type']);
								$old_data_col_names = array_keys($this->_apply_settings_modify_matrix_column($celltype, $data, 'get_data'));

								// duplicate it
								unset($data['col_id']);
								$data['site_id'] = $field->site_id;
								$data['field_id'] = $field->field_id;

								$this->EE->db->insert('matrix_cols', $data);

								// get the new col_id
								$data['col_id'] = $this->EE->db->insert_id();

								// add the new data column(s)
								$new_data_cols = $this->_apply_settings_modify_matrix_column($celltype, $data, 'add');
								$this->EE->dbforge->add_column('matrix_data', $new_data_cols);

								// migrate the data
								$sql = 'UPDATE exp_matrix_data SET ';

								foreach (array_keys($new_data_cols) as $i => $new_col_name)
								{
									if ($i) $sql .= ', ';
									$old_col_name = $old_data_col_names[$i];
									$sql .= "{$new_col_name} = {$old_col_name}, {$old_col_name} = NULL";
								}

								$sql .= " WHERE field_id = {$field->field_id}";

								$this->EE->db->query($sql);

								$new_col_ids[] = $data['col_id'];
							}
							else
							{
								// just assign it to this field
								$data = array(
									'site_id' => $field->site_id,
									'field_id' => $field->field_id
								);

								$this->EE->db->where('col_id', $col_id)
								             ->update('matrix_cols', $data);

								$new_col_ids[] = $col_id;
							}
						}
					}
				}

				// update the field settings with the new col_ids array
				$field_settings['col_ids'] = $new_col_ids;

				$this->EE->db->where('field_id', $field->field_id)
				             ->update('channel_fields', array('field_settings' => base64_encode(serialize($field_settings))));
			}
		}

		return $affected_cols;
	}

	/**
	 * Install
	 */
	function install()
	{
		$this->EE->load->dbforge();

		// -------------------------------------------
		//  Create the exp_matrix_cols table
		// -------------------------------------------

		if (! $this->EE->db->table_exists('matrix_cols'))
		{
			$this->EE->dbforge->add_field(array(
				'col_id'           => array('type' => 'int', 'constraint' => 6, 'unsigned' => TRUE, 'auto_increment' => TRUE),
				'site_id'          => array('type' => 'int', 'constraint' => 4, 'unsigned' => TRUE, 'default' => 1),
				'field_id'         => array('type' => 'int', 'constraint' => 6, 'unsigned' => TRUE),
				'col_name'         => array('type' => 'varchar', 'constraint' => 32),
				'col_label'        => array('type' => 'varchar', 'constraint' => 50),
				'col_instructions' => array('type' => 'text'),
				'col_type'         => array('type' => 'varchar', 'constraint' => 50, 'default' => 'text'),
				'col_required'     => array('type' => 'char', 'constraint' => 1, 'default' => 'n'),
				'col_search'       => array('type' => 'char', 'constraint' => 1, 'default' => 'n'),
				'col_order'        => array('type' => 'int', 'constraint' => 3, 'unsigned' => TRUE),
				'col_width'        => array('type' => 'varchar', 'constraint' => 4),
				'col_settings'     => array('type' => 'text')
			));

			$this->EE->dbforge->add_key('col_id', TRUE);
			$this->EE->dbforge->add_key('site_id');
			$this->EE->dbforge->add_key('field_id');

			$this->EE->dbforge->create_table('matrix_cols');
		}

		// -------------------------------------------
		//  Create the exp_matrix_data table
		// -------------------------------------------

		if (! $this->EE->db->table_exists('matrix_data'))
		{
			$this->EE->dbforge->add_field(array(
				'row_id'    => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE),
				'site_id'   => array('type' => 'int', 'constraint' => 4, 'unsigned' => TRUE, 'default' => 1),
				'entry_id'  => array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
				'field_id'  => array('type' => 'int', 'constraint' => 6, 'unsigned' => TRUE),
				'row_order' => array('type' => 'int', 'constraint' => 4, 'unsigned' => TRUE)
			));

			$this->EE->dbforge->add_key('row_id', TRUE);
			$this->EE->dbforge->add_key('site_id');
			$this->EE->dbforge->add_key('entry_id');
			$this->EE->dbforge->add_key('field_id');

			$this->EE->dbforge->create_table('matrix_data');
		}

		// -------------------------------------------
		//  EE1 Conversion
		// -------------------------------------------

		if (! class_exists('FF2EE2')) require_once PATH_THIRD.'matrix/includes/ff2ee2/ff2ee2.php';

		// FF Matrix 1 conversion
		$converter = new FF2EE2(array('ff_matrix', 'matrix'), array(&$this, '_convert_ff_matrix_field'));

		// Matrix 2 conversion
		$converter = new FF2EE2('matrix', array(&$this, '_convert_ee1_matrix2_field'));
		return $converter->global_settings;
	}

	/**
	 * Convert FF Matrix Field
	 * 
	 * @todo - find unique words and add them to the exp_channel_data cell
	 */
	function _convert_ff_matrix_field($settings, $field)
	{
		$settings['col_ids'] = array();

		if (isset($settings['cols']))
		{
			if ($settings['cols'])
			{
				// -------------------------------------------
				//  Add the rows to exp_matrix_cols
				// -------------------------------------------

				$col_ids_by_key = array();
				$matrix_data_columns = array();

				foreach ($settings['cols'] as $col_key => $col)
				{
					$col_type = $col['type'];
					$col_settings = $col['settings'];

					switch ($col_type)
					{
						case 'ff_checkbox':
						case 'ff_checkbox_group':
							if ($col_type == 'ff_checkbox')
							{
								$col_settings = array('options' => array('y' => $col_settings['label']));
							}

							$col_type = 'pt_checkboxes';
							break;

						case 'ff_select':
							$col_type = 'pt_dropdown';
							break;

						case 'ff_multiselect':
							$col_type = 'pt_multiselect';
							break;

						case 'ff_radio_group':
							$col_type = 'pt_radio_buttons';
							break;

						case 'ff_matrix_text':
						case 'ff_matrix_textarea':
							$col_settings['multiline'] = ($col_type == 'ff_matrix_text' ? 'n' : 'y');
							$col_type = 'text';
							break;

						case 'ff_matrix_date':
							$col_type = 'date';
							break;
					}

					$this->EE->db->insert('matrix_cols', array(
						'site_id'      => $field['site_id'],
						'field_id'     => $field['field_id'],
						'col_name'     => $col['name'],
						'col_label'    => $col['label'],
						'col_type'     => $col_type,
						'col_search'   => $field['field_search'],
						'col_order'    => $col_key,
						'col_settings' => base64_encode(serialize($col_settings))
					));

					// get the new col_id
					$col_id = $this->EE->db->insert_id();

					// add it to the matrix_data_columns queue
					$matrix_data_columns['col_id_'.$col_id] = array('type' => 'text');

					// map the col_id to the col_key for later
					$col_ids_by_key[$col_key] = $col_id;
				}

				// -------------------------------------------
				//  Add the columns to matrix_data
				// -------------------------------------------

				$this->EE->dbforge->add_column('matrix_data', $matrix_data_columns);

				// -------------------------------------------
				//  Move the field data into exp_matrix_data
				// -------------------------------------------

				$field_id = 'field_id_'.$field['field_id'];

				$this->EE->db->select('entry_id, '.$field_id);
				$this->EE->db->where($field_id.' !=', '');
				$entries = $this->EE->db->get('channel_data');

				foreach ($entries->result_array() as $entry)
				{
					// unserialize the data
					$old_data = FF2EE2::_unserialize($entry[$field_id]);

					foreach ($old_data as $row_count => $row)
					{
						$data = array(
							'site_id'   => $field['site_id'],
							'entry_id'  => $entry['entry_id'],
							'field_id'  => $field['field_id'],
							'row_order' => $row_count+1
						);

						foreach ($row as $col_key => $cell_data)
						{
							// does this col exist?
							if (! isset($col_ids_by_key[$col_key])) continue;

							// get the col_id
							$col_id = $col_ids_by_key[$col_key];

							// flatten the cell data if necessary
							$cell_data = $this->_flatten_data($cell_data);

							// queue it up
							$data['col_id_'.$col_id] = $cell_data;
						}

						// add the row to exp_matrix_data
						$this->EE->db->insert('matrix_data', $data);
					}

					// clear out the old field data from exp_channel_data
					$new_data = $this->_flatten_data($old_data);
					$this->EE->db->where('entry_id', $entry['entry_id']);
					$this->EE->db->update('channel_data', array($field_id => $new_data));
				}
			}

			// -------------------------------------------
			//  Remove 'cols' from field settings
			// -------------------------------------------

			unset($settings['cols']);
		}

		return $settings;
	}

	/**
	 * Convert EE1 Matrix 2 Field
	 */
	function _convert_ee1_matrix2_field($settings, $field)
	{
		$this->_update_field_col_associations($field['field_id']);

		return $settings;
	}

	/**
	 * Update
	 */
	function update($from)
	{
		if (! $from || $from == MATRIX_VER) return FALSE;

		if (version_compare($from, '2.2', '<'))
		{
			$this->_update_field_col_associations();
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Theme URL
	 */
	private function _theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : $this->EE->config->slash_item('theme_folder_url').'third_party/';
			$this->cache['theme_url'] = $theme_folder_url.'matrix/';
		}

		return $this->cache['theme_url'];
	}

	/**
	 * Include Theme CSS
	 */
	private function _include_theme_css($file)
	{
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().$file.'?'.MATRIX_VER.'" />');
	}

	/**
	 * Include Theme JS
	 */
	private function _include_theme_js($file)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().$file.'?'.MATRIX_VER.'"></script>');
	}

	// --------------------------------------------------------------------

	/**
	 * Insert CSS
	 */
	private function _insert_css($css)
	{
		$this->EE->cp->add_to_head('<style type="text/css">'.$css.'</style>');
	}

	/**
	 * Insert JS
	 */
	private function _insert_js($js)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
	}

	// --------------------------------------------------------------------

	/**
	 * Prepare Params
	 */
	private function _prep_params(&$params)
	{
		$params = array_merge(array(
			'cellspacing'        => '1',
			'cellpadding'        => '10',
			'dynamic_parameters' => '',
			'row_id'             => '',
			'orderby'            => '',
			'sort'               => 'asc',
			'offset'             => '',
			'limit'              => '',
			'backspace'          => ''
		), $params);
	}

	// --------------------------------------------------------------------

	/**
	 * Display Global Settings
	 */
	function display_global_settings()
	{
		$license_key = isset($this->settings['license_key']) ? $this->settings['license_key'] : '';

		// load the language file
		$this->EE->lang->loadfile('matrix');

		// load the table lib
		$this->EE->load->library('table');

		// use the default template known as
		// $cp_pad_table_template in the views
		$this->EE->table->set_template(array(
			'table_open'    => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
			'row_start'     => '<tr class="even">',
			'row_alt_start' => '<tr class="odd">'
		));

		$this->EE->table->set_heading(array('data' => lang('preference'), 'style' => 'width: 50%'), lang('setting'));

		$this->EE->table->add_row(
			lang('license_key', 'license_key'),
			form_input('license_key', $license_key, 'id="license_key" size="40"')
		);

		return $this->EE->table->generate();
	}

	/**
	 * Save Global Settings
	 */
	function save_global_settings()
	{
		return array(
			'license_key' => isset($_POST['license_key']) ? $_POST['license_key'] : ''
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Get Field Cols
	 */
	private function _get_field_cols($field_id)
	{
		if (! isset($this->cache['field_cols'][$field_id]))
		{
			$query = $this->EE->db->select('col_id, col_type, col_label, col_name, col_instructions, col_width, col_required, col_search, col_settings')
			                       ->where('field_id', $field_id)
			                       ->order_by('col_order')
			                       ->get('matrix_cols');

			if (! $query->num_rows())
			{
				if ($this->_update_field_col_associations())
				{
					// probably need to update the fieldtypes version number so update() doesn't get called...
					$this->EE->db->where('name', 'matrix')
					             ->update('fieldtypes', array('version' => MATRIX_VER));

					// try again
					return $this->_get_field_cols($field_id);
				}

				$cols = array();
			}
			else
			{
				$cols = $query->result_array();

				// unserialize the settings and cache
				foreach ($cols as &$col)
				{
					$col['col_settings'] = unserialize(base64_decode($col['col_settings']));
					if (! is_array($col['col_settings'])) $col['col_settings'] = array();

					$celltype = $this->_get_celltype($col['col_type']);

					$col['has_validate_cell'] = method_exists($celltype, 'validate_cell');
					$col['has_save_cell'] = method_exists($celltype, 'save_cell');
					$col['has_post_save_cell'] = method_exists($celltype, 'post_save_cell');

					// are we ever going to call this celltype during the save process?
					if ($col['has_validate_cell'] || $col['has_save_cell'] || $col['has_post_save_cell'])
					{
						// prepare the celltype's col settings
						$col['celltype_settings'] = array_merge($this->settings, $celltype->settings, (is_array($col['col_settings']) ? $col['col_settings'] : array()));
						$col['celltype_settings']['col_id']   = $col['col_id'];
						$col['celltype_settings']['col_name'] = 'col_id_'.$col['col_id'];
						$col['celltype_settings']['col_required'] = $col['col_required'];
					}
				}
			}

			$this->cache['field_cols'][$field_id] = $cols;
		}

		return $this->cache['field_cols'][$field_id];
	}

	// --------------------------------------------------------------------

	/**
	 * Get Celltype Class
	 */
	private function _get_celltype_class($name, $text_fallback = FALSE)
	{
		// $name should look like exp_fieldtypes.name values
		if (substr($name, -3) == '_ft') $name = substr($name, 0, -3);
		$name = strtolower($name);

		// is this a bundled celltype?
		if (in_array($name, $this->bundled_celltypes))
		{
			$class = 'Matrix_'.$name.'_ft';

			if (! class_exists($class))
			{
				// load it from matrix/celltypes/
				require_once PATH_THIRD.'matrix/celltypes/'.$name.EXT;
			}
		}
		else
		{
			$class = ucfirst($name).'_ft';
			$this->EE->api_channel_fields->include_handler($name);
		}

		if (class_exists($class))
		{
			// method_exists() is supposed to accept the class name (string),
			// but running into at least one server where that's not the case...
			$ft = new $class();

			if (method_exists($ft, 'display_cell'))
			{
				if (! isset($this->cache['celltype_global_settings'][$name]))
				{
					$this->EE->db->select('settings');
					$this->EE->db->where('name', $name);
					$query = $this->EE->db->get('fieldtypes');

					$settings = $query->row('settings');
					$this->cache['celltype_global_settings'][$name] = is_array($settings) ? $settings : unserialize(base64_decode($settings));
				}

				return $class;
			}
		}

		return $text_fallback ? $this->_get_celltype_class('text') : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Celltype
	 */
	private function _get_celltype($name, $text_fallback = FALSE)
	{
		$class = $this->_get_celltype_class($name, $text_fallback);

		if (! $class) return FALSE;

		$celltype = new $class();

		$global_settings = $this->cache['celltype_global_settings'][$name];
		$celltype->settings = $global_settings && is_array($global_settings) ? $global_settings : array();

		return $celltype;
	}

	// --------------------------------------------------------------------

	/**
	 * Get All Celltypes
	 */
	private function _get_all_celltypes()
	{
		// this is only called once, from display_settings(),
		// so don't worry about caching the results

		// begin with what we already know about
		$ft_names = array_merge($this->bundled_celltypes);

		// get the fieldtypes from exp_fieldtypes
		$query = $this->EE->db->select('name, settings')
		                      ->get('fieldtypes');

		if (! isset($this->cache['celltype_global_settings']))
		{
			$this->cache['celltype_global_settings'] = array();
		}

		foreach ($query->result_array() as $ft)
		{
			$ft_names[] = $ft['name'];
			$this->cache['celltype_global_settings'][$ft['name']] = unserialize(base64_decode($ft['settings']));
		}

		// now get the actual celltype instances
		$celltypes = array();

		foreach ($ft_names as $name)
		{
			if (($ct = $this->_get_celltype($name)) !== FALSE)
			{
				$celltypes[$name] = $ct;
			}
		}

		// sort them alphabetically
		ksort($celltypes);

		return $celltypes;
	}

	// --------------------------------------------------------------------

	/**
	 * Add Package Path
	 */
	private function _add_package_path($celltype)
	{
		$name = strtolower(substr(get_class($celltype), 0, -3));
		$path = PATH_THIRD.$name.'/';
		$this->EE->load->add_package_path($path);

		// manually add the view path if this is less than EE 2.1.5
		if (version_compare(APP_VER, '2.1.5', '<'))
		{
			$this->EE->load->_ci_view_path = $path.'views/';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Namespace Settings
	 */
	function _namespace_settings(&$settings, $namespace)
	{
		$settings = preg_replace('/(name=([\'\"]))([^\'"\[\]]+)([^\'"]*)(\2)/i', '$1'.$namespace.'[$3]$4$5', $settings);
	}

	// --------------------------------------------------------------------

	/**
	 * Celltype Settings HTML
	 */
	private function _celltype_settings_html($namespace, $celltype, $data = array())
	{
		if (method_exists($celltype, 'display_cell_settings'))
		{
			$this->_add_package_path($celltype);
			$returned = $celltype->display_cell_settings($data);

			// should we create the html for them?
			if (is_array($returned))
			{
				$r = '<table class="matrix-col-settings" cellspacing="0" cellpadding="0" border="0">';

				$total_cell_settings = count($returned);

				foreach ($returned as $cs_key => $cell_setting)
				{
					$tr_class = '';
					if ($cs_key == 0) $tr_class .= ' matrix-first';
					if ($cs_key == $total_cell_settings-1) $tr_class .= ' matrix-last';

					$r .= '<tr class="'.$tr_class.'">'
					    .   '<th class="matrix-first">'.$cell_setting[0].'</th>'
					    .   '<td class="matrix-last">'.$cell_setting[1].'</td>'
					    . '</tr>';
				}

				$r .= '</table>';
			}
			else
			{
				$r = $returned;
			}

			$this->_namespace_settings($r, $namespace);
		}
		else
		{
			$r = '';
		}

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field Settings
	 */
	function display_settings($data)
	{
		$min_rows = isset($data['min_rows']) ? $data['min_rows'] : '0';
		$max_rows = isset($data['max_rows']) ? $data['max_rows'] : '';

		// include css and js
		$this->_include_theme_css('styles/matrix.css');
		$this->_include_theme_js('scripts/matrix.js');
		$this->_include_theme_js('scripts/matrix_text.js');
		$this->_include_theme_js('scripts/matrix_conf.js');

		// language
		$this->_insert_js('MatrixConf.lang = { '
			. 'delete_col: "'.lang('delete_col').'" };');

		// load the language file
		$this->EE->lang->loadfile('matrix');

		// -------------------------------------------
		//  Get the celltypes
		// -------------------------------------------

		$celltypes = $this->_get_all_celltypes();
		$celltypes_select_options = array();
		$celltypes_js = array();

		foreach ($celltypes as $name => $celltype)
		{
			$celltypes_select_options[$name] = $celltype->info['name'];

			// default cell settings
			$celltypes_js[$name] = $this->_celltype_settings_html('matrix[cols][{COL_ID}][settings]', $celltype, $data);
		}

		// -------------------------------------------
		//  Get the columns
		// -------------------------------------------

		// is this an existing Matrix field?
		if ($data['field_id'] && $data['field_type'] == 'matrix')
		{
			$cols = $this->_get_field_cols($data['field_id']);
		}

		if (isset($cols) && $cols)
		{
			$new = FALSE;
		}
		else
		{
			$new = TRUE;

			// start off with a couple text cells
			$cols = array(
				array('col_id' => '0', 'col_label' => 'Cell 1', 'col_instructions' => '', 'col_name' => 'cell_1', 'col_type' => 'text', 'col_width' => '33%', 'col_required' => 'n', 'col_search' => 'n', 'col_settings' => array('maxl' => '',    'multiline' => 'n')),
				array('col_id' => '1', 'col_label' => 'Cell 2', 'col_instructions' => '', 'col_name' => 'cell_2', 'col_type' => 'text', 'col_width' => '',    'col_required' => 'n', 'col_search' => 'n', 'col_settings' => array('maxl' => '140', 'multiline' => 'y'))
			);
		}

		$cols_js = array();

		foreach ($cols as &$col)
		{
			$cols_js[] = array(
				'id' => ($new ? 'col_new_' : 'col_id_') . $col['col_id'],
				'type' => $col['col_type']
			);
		}

		// -------------------------------------------
		//  Minimum Rows
		// -------------------------------------------

			$this->EE->table->add_row(
				lang('min_rows', 'matrix_min_rows'),
				form_input('matrix[min_rows]', $min_rows, 'id="matrix_min_rows" style="width: 3em;"')
			);

		// -------------------------------------------
		//  Maximum Rows
		// -------------------------------------------

			$this->EE->table->add_row(
				lang('max_rows', 'matrix_max_rows'),
				form_input('matrix[max_rows]', $max_rows, 'id="matrix_max_rows" style="width: 3em;"')
			);

		// -------------------------------------------
		//  Matrix Configuration
		// -------------------------------------------

			$total_cols = count($cols);

			$table = '<div id="matrix-conf-container"><div id="matrix-conf">'
			       .   '<table class="matrix matrix-conf" cellspacing="0" cellpadding="0" border="0" style="background: #ecf1f4;">'
			       .     '<thead class="matrix">'
			       .       '<tr class="matrix matrix-first">'
			       .         '<td class="matrix-breakleft"></td>';

			// -------------------------------------------
			//  Labels
			// -------------------------------------------

			foreach ($cols as $col_index => &$col)
			{
				$col_id = $new ? 'col_new_'.$col_index : 'col_id_'.$col['col_id'];

				$class = 'matrix';
				if ($col_index == 0) $class .= ' matrix-first';
				if ($col_index == $total_cols - 1) $class .= ' matrix-last';

				$table .= '<th class="'.$class.'" scope="col">'
				        .   '<input type="hidden" name="matrix[col_order][]" value="'.$col_id.'" />'
				        .   '<span>'.$col['col_label'].'</span>'
				        . '</th>';
			}

			$table .= '</tr>'
			        . '<tr class="matrix matrix-last">'
			        .   '<td class="matrix-breakleft"></td>';

			// -------------------------------------------
			//  Instructions
			// -------------------------------------------

			foreach ($cols as $col_index => &$col)
			{
				$class = 'matrix';
				if ($col_index == 0) $class .= ' matrix-first';
				if ($col_index == $total_cols - 1) $class .= ' matrix-last';

				$table .= '<td class="'.$class.'">'.($col['col_instructions'] ? nl2br($col['col_instructions']) : '&nbsp;').'</td>';
			}

			$table .=   '</tr>'
			        . '</thead>'
			        . '<tbody class="matrix">';

			// -------------------------------------------
			//  Col Settings
			// -------------------------------------------

			$col_settings = array('type', 'label', 'name', 'instructions', 'width', 'required', 'search', 'settings');
			$total_settings = count($col_settings);

			foreach ($col_settings as $row_index => $col_setting)
			{
				$tr_class = 'matrix';
				if ($row_index == 0) $tr_class .= ' matrix-first';
				if ($row_index == $total_settings - 1) $tr_class .= ' matrix-last';

				$table .= '<tr class="'.$tr_class.'">'
				        .   '<th class="matrix-breakleft" scope="row">'.lang('col_'.$col_setting).'</th>';

				foreach ($cols as $col_index => &$col)
				{
					$col_id = $new ? 'col_new_'.$col_index : 'col_id_'.$col['col_id'];
					$setting_name = 'matrix[cols]['.$col_id.']['.$col_setting.']';

					$td_class = 'matrix';
					if ($col_index == 0) $td_class .= ' matrix-first';
					if ($col_index == $total_cols - 1) $td_class .= ' matrix-last';

					switch ($col_setting)
					{
						case 'type':
							$shtml = form_dropdown($setting_name, $celltypes_select_options, $col['col_'.$col_setting]);
							break;

						case 'name':
						case 'width':
							$td_class .= ' matrix-text';
							$shtml = form_input($setting_name, $col['col_'.$col_setting], 'class="matrix-textarea"');
							break;

						case 'required':
						case 'search':
							$shtml = form_checkbox($setting_name, 'y', ($col['col_'.$col_setting] == 'y'));
							break;

						case 'settings':
							$cell_data = array_merge($data, is_array($col['col_'.$col_setting]) ? $col['col_'.$col_setting] : array());
							if (! ($shtml = $this->_celltype_settings_html($setting_name, $celltypes[$col['col_type']], $cell_data)))
							{
								$td_class .= ' matrix-disabled';
								$shtml = '&nbsp;';
							}
							break;

						default:
							$td_class .= ' matrix-text';
							$shtml = '<textarea class="matrix-textarea" name="'.$setting_name.'" rows="1">'.$col['col_'.$col_setting].'</textarea>';
					}

					$table .= '<td class="'.$td_class.'">'.$shtml.'</td>';
				}

				$table .= '</tr>';
			}

			// -------------------------------------------
			//  Delete Row buttons
			// -------------------------------------------

			$table .=     '<tr>'
			        .       '<td class="matrix-breakleft"></td>';

			foreach ($cols as &$col)
			{
				$table .=   '<td class="matrix-breakdown"><a class="matrix-btn" title="'.lang('delete_col').'"></a></td>';
			}

			$table .=       '</tr>'
			        .     '</tbody>'
			        .   '</table>'
			        .   '<a class="matrix-btn matrix-add" title="'.lang('add_col').'"></a>'
			        . '</div></div>';

			$this->EE->table->add_row(array(
				'colspan' => '2',
				'data'    => lang('matrix_configuration', 'matrix_configuration')
				           . $table
			));

		// -------------------------------------------
		//  Initialize the configurator js
		// -------------------------------------------

		$js = 'MatrixConf.EE2 = true;' . NL
		    . 'var matrixConf = new MatrixConf("matrix", '
		    .   $this->EE->javascript->generate_json($celltypes_js, TRUE) . ', '
		    .   $this->EE->javascript->generate_json($cols_js, TRUE) . ', '
		    .   $this->EE->javascript->generate_json($col_settings, TRUE)
		    . ');';

		if ($new) $js .= NL.'matrixConf.totalNewCols = 2;';

		$this->_insert_js($js);
	}

	/**
	 * Save Field Settings
	 */
	function save_settings($data)
	{
		// cross the T's
		$settings['field_fmt'] = 'none';
		$settings['field_show_fmt'] = 'n';
		$settings['field_type'] = 'matrix';

		return $settings;
	}

	/**
	 * Save Field Settings
	 */
	function post_save_settings($data)
	{
		$this->EE->load->dbforge();

		$post = $this->EE->input->post('matrix');

		// -------------------------------------------
		//  Delete any removed columns
		// -------------------------------------------

		if (isset($post['deleted_cols']))
		{
			$delete_cols = array();

			foreach ($post['deleted_cols'] as $col_name)
			{
				$delete_cols[] = substr($col_name, 7);
			}

			$this->_delete_cols($delete_cols);
		}

		// -------------------------------------------
		//  Add/update columns
		// -------------------------------------------

		$settings = array(
			'min_rows' => (isset($post['min_rows']) && $post['min_rows'] ? $post['min_rows'] : '0'),
			'max_rows' => (isset($post['max_rows']) && $post['max_rows'] ? $post['max_rows'] : ''),
			'col_ids' => array()
		);

		foreach ($post['col_order'] as $col_order => $col_id)
		{
			$col = $post['cols'][$col_id];

			$celltype = $this->_get_celltype($col['type']);

			$cell_settings = isset($col['settings']) ? $col['settings'] : array();

			// give the celltype a chance to override
			if (method_exists($celltype, 'save_cell_settings'))
			{
				$cell_settings = $celltype->save_cell_settings($cell_settings);
			}

			$col_data = array(
				'col_name'         => $col['name'],
				'col_label'        => str_replace('$', '&#36;', $col['label']),
				'col_instructions' => str_replace('$', '&#36;', $col['instructions']),
				'col_type'         => $col['type'],
				'col_required'     => (isset($col['required']) && $col['required'] ? 'y' : 'n'),
				'col_search'       => (isset($col['search']) && $col['search'] ? 'y' : 'n'),
				'col_width'        => $col['width'],
				'col_order'        => $col_order,
				'col_settings'     => base64_encode(serialize($cell_settings))
			);

			$new = (substr($col_id, 0, 8) == 'col_new_');

			if ($new)
			{
				$col_data['site_id'] = $this->EE->config->item('site_id');
				$col_data['field_id'] = $this->settings['field_id'];

				// insert the row
				$this->EE->db->insert('matrix_cols', $col_data);

				// get the col_id
				$col_id = $this->EE->db->insert_id();
				$col_data['col_id'] = $col_id;

				// notify the celltype
				$fields = $this->_apply_settings_modify_matrix_column($celltype, $col_data, 'add');

				// add the new column(s) to exp_matrix_data
				$this->EE->dbforge->add_column('matrix_data', $fields);
			}
			else
			{
				$col_id = substr($col_id, 7);
				$col_data['col_id'] = $col_id;
				$primary_col_name = 'col_id_'.$col_id;

				// get the previous col_type
				$prev_col_type = $this->EE->db->select('col_type')
				                              ->where('col_id', $col_id)
				                              ->get('matrix_cols')
				                              ->row('col_type');

				// has the col type changed?
				if ($prev_col_type != $col['type'])
				{
					// notify the old celltype
					$fields = $this->_apply_settings_modify_matrix_column($prev_col_type, $col_data, 'delete');

					// delete any extra exp_matrix_data cols
					unset($fields[$primary_col_name]);
					foreach (array_keys($fields) as $field_name)
					{
						$this->EE->dbforge->drop_column('matrix_data', $field_name);
					}

					// notify the new celltype
					$fields = $this->_apply_settings_modify_matrix_column($celltype, $col_data, 'add');

					// extract the primary field
					$primary_field = array($primary_col_name => $fields[$primary_col_name]);
					unset($fields[$primary_col_name]);

					// update the primary column
					$primary_field[$primary_col_name]['name'] = $primary_col_name;
					$this->EE->dbforge->modify_column('matrix_data', $primary_field);

					// add any extra cols
					$this->EE->dbforge->add_column('matrix_data', $fields);
				}
				else
				{
					// notify the celltype
					$fields = $this->_apply_settings_modify_matrix_column($celltype, $col_data, 'get_data');

					// update the columns
					foreach ($fields as $field_name => &$field)
					{
						$field['name'] = $field_name;
					}
					$this->EE->dbforge->modify_column('matrix_data', $fields);
				}

				// update the existing row
				$this->EE->db->where('col_id', $col_id);
				$this->EE->db->update('matrix_cols', $col_data);
			}

			// add the col_id to the field settings
			//  - it's unfortunate that we can't just place the field_id in the matrix_cols
			//    data, but alas, the future field_id is unknowable on new fields
			$settings['col_ids'][] = $col_id;
		}

		// save the settings to exp_channel_fields
		$data = array('field_settings' => base64_encode(serialize($settings)));
		$this->EE->db->where('field_id', $this->settings['field_id'])
		             ->update('channel_fields', $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Rows
	 */
	function delete_rows($row_ids)
	{
		// -------------------------------------------
		//  Notify the celltypes
		// -------------------------------------------

		$celltypes = $this->_get_all_celltypes();

		foreach ($celltypes as $name => $celltype)
		{
			if (method_exists($celltype, 'delete_rows'))
			{
				$celltype->delete_rows($row_ids);
			}
		}

		// -------------------------------------------
		//  Delete the rows
		// -------------------------------------------

		$this->EE->db->where_in('row_id', $row_ids)
		             ->delete('matrix_data');
	}

	/**
	 * Delete Columns
	 */
	private function _delete_cols($col_ids)
	{
		$this->EE->load->dbforge();

		$cols = $this->EE->db->select('col_id, col_type, col_label, col_name, col_instructions, col_width, col_required, col_search, col_settings')
		                     ->where_in('col_id', $col_ids)
		                     ->get('matrix_cols')
		                     ->result_array();

		// -------------------------------------------
		//  exp_matrix_data
		// -------------------------------------------

		foreach ($cols as &$col)
		{
			// notify the celltype
			$fields = $this->_apply_settings_modify_matrix_column($col['col_type'], $col, 'delete');

			// drop the exp_matrix_data columns
			foreach (array_keys($fields) as $field_name)
			{
				$this->EE->dbforge->drop_column('matrix_data', $field_name);
			}
		}

		// -------------------------------------------
		//  exp_matrix_cols
		// -------------------------------------------

		$this->EE->db->where_in('col_id', $col_ids)
		             ->delete('matrix_cols');
	}

	// --------------------------------------------------------------------

	/**
	 * Modify exp_channel_data Column Settings
	 */
	function settings_modify_column($data)
	{
		if ($data['ee_action'] == 'delete')
		{
			// -------------------------------------------
			//  Delete the field data
			// -------------------------------------------

			$rows = $this->EE->db->select('row_id')
			                     ->where('field_id', $data['field_id'])
			                     ->get('matrix_data');

			if ($rows->num_rows())
			{
				$delete_rows = array();

				foreach ($rows->result() as $row)
				{
					$delete_rows[] = $row->row_id;
				}

				$this->delete_rows($delete_rows);
			}

			// -------------------------------------------
			//  Delete the columns
			// -------------------------------------------

			// decode the field settings
			$query = $this->EE->db->select('col_id')->where('field_id', $data['field_id'])->get('matrix_cols');

			if ($query->num_rows())
			{
				foreach ($query->result() as $col)
				{
					$col_ids[] = $col->col_id;
				}

				$this->_delete_cols($col_ids);
			}
		}

		// just return the default column settings
		return parent::settings_modify_column($data);
	}

	/**
	 * Apply settings_modify_matrix_column
	 */
	private function _apply_settings_modify_matrix_column($celltype, $data, $action)
	{
		$primary_col_name = 'col_id_'.$data['col_id'];

		if (is_string($celltype)) $celltype = $this->_get_celltype($celltype);

		// give the celltype a chance to override the settings of the exp_matrix_data columns
		if (method_exists($celltype, 'settings_modify_matrix_column'))
		{
			$data['matrix_action'] = $action;

			$fields = (array) $celltype->settings_modify_matrix_column($data);

			// make sure the celltype returned the required column
			if (! isset($fields[$primary_col_name]))
			{
				$fields[$primary_col_name] = array('type' => 'text');
			}
		}
		else
		{
			$fields = array($primary_col_name => array('type' => 'text'));
		}

		return $fields;
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field
	 */
	function display_field($data)
	{
		// -------------------------------------------
		//  Include dependencies
		//   - this needs to happen *before* we load the celltypes,
		//     in case the celltypes are loading their own JS
		// -------------------------------------------

		if (! isset($this->cache['included_dependencies']))
		{
			// load the language file
			$this->EE->lang->loadfile('matrix');

			// include css and js
			$this->_include_theme_css('styles/matrix.css');
			$this->_include_theme_js('scripts/matrix.js');

			// menu language
			$this->_insert_js('Matrix.lang = { '
				. 'options: "'.lang('options').'", '
				. 'add_row_above: "'.lang('add_row_above').'", '
				. 'add_row_below: "'.lang('add_row_below').'", '
				. 'delete_row: "'.lang('delete_row').'", '
				. 'remove_file: "'.lang('remove_file').'", '
				. 'select_file_error: "'.lang('select_file_error').'" };');

			$this->cache['included_dependencies'] = TRUE;
		}

		// -------------------------------------------
		//  Initialize the field
		// -------------------------------------------

		$field_id = $this->settings['field_id'];
		$entry_id = $this->EE->input->get('entry_id');

		$min_rows = isset($this->settings['min_rows']) ? (int) $this->settings['min_rows'] : 0;
		$max_rows = isset($this->settings['max_rows']) ? (int) $this->settings['max_rows'] : 0;

		// default $min_rows to 1 if the field is required
		if (! $min_rows && $this->settings['field_required'] == 'y') $min_rows = 1;

		// single-row mode?
		$single_row_mode = ($min_rows == 1 && $max_rows == 1);

		$cols = $this->_get_field_cols($field_id);
		if (! $cols) return;

		$total_cols = count($cols);

		$col_settings = array();

		$select_col_ids = '';
		$show_instructions = FALSE;

		$cols_js = array();

		foreach ($cols as &$col)
		{
			// index the col by ID
			$select_col_ids .= ', col_id_'.$col['col_id'];

			// show instructions?
			if ($col['col_instructions']) $show_instructions = TRUE;

			// include this->settings in col settings
			$col_settings[$col['col_id']] = array_merge($this->settings, (is_array($col['col_settings']) ? $col['col_settings'] : array()));

			$celltype = $this->_get_celltype($col['col_type']);
			$celltype->settings = array_merge($celltype->settings, $col_settings[$col['col_id']]);
			$celltype->field_id = $field_id;
			$celltype->field_name = $this->field_name;
			$celltype->col_id = $col['col_id'];
			$celltype->cell_name = '{DEFAULT}';

			$this->_add_package_path($celltype);
			$new_cell_html = $celltype->display_cell('');

			$new_cell_settings = FALSE;
			$new_cell_class = FALSE;

			if (is_array($new_cell_html))
			{
				if (isset($new_cell_html['settings']))
				{
					$new_cell_settings = $new_cell_html['settings'];
				}

				if (isset($new_cell_html['class']))
				{
					$new_cell_class = $new_cell_html['class'];
				}

				$new_cell_html = $new_cell_html['data'];
			}

			// store the js-relevant stuff in $cols_js

			$cols_js[] = array(
				'id' => 'col_id_'.$col['col_id'],
				'name' => $col['col_name'],
				'label' => $col['col_label'],
				'required' => ($col['col_required'] == 'y' ? TRUE : FALSE),
				'settings' => $col['col_settings'],
				'type' => $col['col_type'],
				'newCellHtml' => $new_cell_html,
				'newCellSettings' => $new_cell_settings,
				'newCellClass' => $new_cell_class
			);
		}

		// -------------------------------------------
		//  Get the data
		// -------------------------------------------

		// autosave data?
		if (is_array($data) && isset($data['row_order']))
		{
			unset($data['row_order']);

			foreach ($data as $row_id => &$row)
			{
				if (substr($row_id, 0, 7) == 'row_id_')
				{
					$row['row_id'] = substr($row_id, 7);
				}
			}
		}
		else
		{
			$data = array();

			// is there post data?
			if (isset($_POST[$this->field_name]) && isset($_POST[$this->field_name]['row_order']) && $_POST[$this->field_name]['row_order'])
			{
				foreach ($_POST[$this->field_name]['row_order'] as $row_id)
				{
					$row = isset($_POST[$this->field_name][$row_id]) ? $_POST[$this->field_name][$row_id] : array();

					foreach ($cols as &$col)
					{
						$data[$row_id]['col_id_'.$col['col_id']] = isset($row['col_id_'.$col['col_id']]) ? $row['col_id_'.$col['col_id']] : '';
					}
				}
			}
			else
			{
				// is this an existing entry?
				if ($entry_id)
				{
					$this->EE->db->select('row_id' . $select_col_ids);
					$this->EE->db->where('site_id', $this->EE->config->item('site_id'));
					$this->EE->db->where('field_id', $field_id);
					$this->EE->db->where('entry_id', $entry_id);
					$this->EE->db->order_by('row_order');

					if ($max_rows)
					{
						$this->EE->db->limit($max_rows);
					}

					$query = $this->EE->db->get('matrix_data')->result_array();

					// is this a clone?
					$clone = ($this->EE->input->get('clone') == 'y');

					// re-index the query data
					foreach ($query as $count => $row)
					{
						$key = $clone ? 'row_new_'.$count : 'row_id_'.$row['row_id'];
						$data[$key] = $row;
					}
				}
			}
		}

		// -------------------------------------------
		//  Reach the Minimum Rows count
		// -------------------------------------------

		$total_rows = count($data);

		if ($total_rows < $min_rows)
		{
			$extra_rows = $min_rows - $total_rows;

			for ($i = 0; $i < $extra_rows; $i++)
			{
				foreach ($cols as &$col)
				{
					$data['row_new_'.$i]['col_id_'.$col['col_id']] = '';
				}
			}

			$total_rows = $min_rows;
		}

		// -------------------------------------------
		//  Table Head
		// -------------------------------------------

		$thead = '<thead class="matrix">';

		$headings = '';
		$instructions = '';

		// add left gutters if there can be more than one row
		if (! $single_row_mode)
		{
			$headings .= '<th class="matrix matrix-first matrix-tr-header"></th>';

			if ($show_instructions)
			{
				$instructions .= '<td class="matrix matrix-first matrix-tr-header"></td>';
			}
		}

		// add the labels and instructions
		foreach ($cols as $col_index => &$col)
		{
			$col_count = $col_index + 1;

			$class = 'matrix';
			if ($single_row_mode && $col_count == 1) $class .= ' matrix-first';
			if ($col_count == $total_cols) $class .= ' matrix-last';

			$headings .= '<th class="'.$class.'" scope="col" width="'.$col['col_width'].'">'.$col['col_label'].'</th>';

			if ($show_instructions)
			{
				$instructions .= '<td class="'.$class.'">'.nl2br($col['col_instructions']).'</td>';
			}
		}

		$thead = '<thead class="matrix">'
		       .   '<tr class="matrix matrix-first'.($show_instructions ? '' : ' matrix-last').'">' . $headings . '</tr>'
		       .   ($show_instructions ? '<tr class="matrix matrix-last">' . $instructions . '</tr>' : '')
		       . '</thead>';

		// -------------------------------------------
		//  Table Body
		// -------------------------------------------

		$rows_js = array();

		$tbody = '<tbody class="matrix">';

		$row_count = 0;
		$total_new_rows = 0;

		foreach ($data as $row_name => &$row)
		{
			$row_count ++;

			// new?
			$new = (substr($row_name, 0, 8) == 'row_new_');
			if ($new) $total_new_rows++;

			$row_js = array('id' => $row_name, 'cellSettings' => array());

			$tr_class = 'matrix';
			if ($row_count == 1) $tr_class .= ' matrix-first';
			if ($row_count == $total_rows) $tr_class .= ' matrix-last';

			$tbody .= '<tr class="'.$tr_class.'">';

			// add left heading if there can be more than one row
			if (! $single_row_mode)
			{
				$tbody .= '<th class="matrix matrix-first matrix-tr-header">'
				        .   '<div><span>'.$row_count.'</span><a title="'.lang('options').'"></a></div>'
				        .   '<input type="hidden" name="'.$this->field_name.'[row_order][]" value="'.$row_name.'" />'
				        . '</th>';
			}

			// add the cell data
			foreach ($cols as $col_index => &$col)
			{
				$col_name = 'col_id_'.$col['col_id'];

				$col_count = $col_index + 1;

				$td_class = 'matrix';

				// is this the first data cell?
				if ($col_count == 1)
				{
					// is this also the first cell in the <tr>?
					if ($single_row_mode) $td_class .= ' matrix-first';

					// use .matrix-firstcell for active state
					$td_class .= ' matrix-firstcell';
				}

				if ($col_count == $total_cols) $td_class .= ' matrix-last';

				// was there a validation error for this cell?
				if (isset($this->cache['cell_errors'][$field_id][$row_name][$col_name]))
				{
					$td_class .= ' matrix-error';
				}

				// get new instance of this celltype
				$celltype = $this->_get_celltype($col['col_type']);

				$cell_name = $this->field_name.'['.$row_name.']['.$col_name.']';
				$cell_data = isset($row['col_id_'.$col['col_id']]) ? $row['col_id_'.$col['col_id']] : '';

				// fill it up with crap
				$celltype->settings = array_merge($celltype->settings, $col_settings[$col['col_id']]);
				if (isset($row['row_id'])) $celltype->row_id = $row['row_id'];
				$celltype->field_id = $field_id;
				$celltype->field_name = $this->field_name;
				$celltype->col_id = $col['col_id'];
				$celltype->cell_name = $cell_name;

				// get the cell html
				$this->_add_package_path($celltype);
				$cell_html = $celltype->display_cell($cell_data);

				// is the celltype sending settings too?
				if (is_array($cell_html))
				{
					if (isset($cell_html['settings']))
					{
						$row_js['cellSettings'][$col_name] = $cell_html['settings'];
					}

					if (isset($cell_html['class']))
					{
						$td_class .= ' '.$cell_html['class'];
					}

					$cell_html = $cell_html['data'];
				}

				$tbody .= '<td class="'.$td_class.'">'.$cell_html.'</td>';
			}

			$tbody .= '</tr>';

			$rows_js[] = $row_js;
		}

		$tbody .= '</tbody>';

		// -------------------------------------------
		//  Plug it all together
		// -------------------------------------------

		$r = '<div id="'.$this->field_name.'" class="matrix" style="margin: 11px 0 1px">'
		   .   '<table class="matrix'.($data ? '' : ' matrix-nodata').'" cellspacing="0" cellpadding="0" border="0">'
		   .     $thead
		   .     $tbody
		   .   '</table>';

		if ($single_row_mode)
		{
			// no <th>s in the <tbody>, so we need to store the row_order outside of the table
			$r .= '<input type="hidden" name="'.$this->field_name.'[row_order][]" value="'.$rows_js[0]['id'].'" />';
		}
		else
		{
			// add the '+' button
			$r .= '<a class="matrix-btn matrix-add'.($max_rows && $max_rows == $total_rows ? ' matrix-btn-disabled' :  '').'" title="'.lang('add_row').'"></a>';
		}

		$r .= '</div>';

		// initialize the field js
		$js = 'jQuery(document).ready(function(){'
		    .   'var m = new Matrix("'.$this->field_name . '", '
		    .     '"' . addslashes($this->settings['field_label']) . '", '
		    .     $this->EE->javascript->generate_json($cols_js, TRUE) . ', '
		    .     $this->EE->javascript->generate_json($rows_js, TRUE) . ', '
		    .     $min_rows . ', '
		    .     $max_rows
		    .   ');' . NL
		    .   'm.totalNewRows = '.$total_new_rows.';'
		    . '});';

		$this->_insert_js($js);

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate
	 */
	function validate($data)
	{
		$errors = array();

		$field_id = $this->settings['field_id'];
		$cols = $this->_get_field_cols($field_id);

		if (isset($data['row_order']) && $data['row_order'] && $cols)
		{
			// load the language file
			$this->EE->lang->loadfile('matrix_validation', 'matrix');

			foreach ($data['row_order'] as $row_index => $row_name)
			{
				if (isset($data[$row_name]))
				{
					$row = $data[$row_name];

					foreach ($cols as &$col)
					{
						// if the celltype has a validate_cell() method, use that for validation
						if ($col['has_validate_cell'])
						{
							$col_name = 'col_id_'.$col['col_id'];
							$cell_data = isset($row[$col_name]) ? $row[$col_name] : '';

							$celltype = $this->_get_celltype($col['col_type']);
							$celltype->settings = $col['celltype_settings'];
							$celltype->settings['row_name'] = $row_name;

							if (($error = $celltype->validate_cell($cell_data)) !== TRUE)
							{
								$this->cache['cell_errors'][$field_id][$row_name][$col_name] = TRUE;

								$errors[] = sprintf($error, $col['col_label']);
							}
						}
					}
				}
			}
		}
		else
		{
			// is this a required field?
			if ($this->settings['field_required'] == 'y')
			{
				return lang('required');
			}
		}

		if ($errors)
		{
			return '<ul><li>'.implode('</li><li>', array_unique($errors)).'</li></ul>';
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Flatten Data
	 */
	private function _flatten_data($data)
	{
		$r = array();

		if (is_array($data))
		{
			foreach ($data as $val)
			{
				$r[] = $this->_flatten_data($val);
			}
		}
		else
		{
			$r[] = $data;
		}

		return implode(NL, array_filter($r));
	}

	// --------------------------------------------------------------------

	/**
	 * Save
	 */
	function save($data)
	{
		$field_id = $this->settings['field_id'];
		$cols = $this->_get_field_cols($field_id);

		// cache the data for post_save()
		$this->cache['field_data'][$field_id] = $data;

		// create a flattened string of all the searchable columns' values
		if (isset($data['row_order']) && $data['row_order'] && $cols)
		{
			$r = '';

			foreach ($data['row_order'] as $row_order => $row_id)
			{
				if (! isset($data[$row_id])) continue;

				$row = $data[$row_id];

				foreach ($cols as &$col)
				{
					if ($col['col_search'] == 'y')
					{
						$cell_data = isset($row['col_id_'.$col['col_id']]) ? $row['col_id_'.$col['col_id']] : '';
						$flattened_cell_data = $this->_flatten_data($cell_data);

						if (strlen($flattened_cell_data))
						{
							$r .= $flattened_cell_data . NL;
						}
					}
				}
			}

			return $r ? $r : '1';
		}

		// no rows, so return blank
		return '';
	}

	/**
	 * Post Save
	 */
	function post_save($data)
	{
		$field_id = $this->settings['field_id'];
		$cols = $this->_get_field_cols($field_id);

		// get the cached field data if it's there
		if (isset($this->cache['field_data'][$field_id]))
		{
			$data = $this->cache['field_data'][$field_id];
		}

		// -------------------------------------------
		//  Delete the deleted rows
		// -------------------------------------------

		if (isset($data['deleted_rows']) && $data['deleted_rows'])
		{
			foreach ($data['deleted_rows'] as $row_name)
			{
				$delete_rows[] = substr($row_name, 7);
			}

			$this->delete_rows($delete_rows);
		}

		// -------------------------------------------
		//  Add/update rows
		// -------------------------------------------

		if (isset($data['row_order']) && $data['row_order'] && $cols)
		{
			foreach ($data['row_order'] as $row_order => $row_name)
			{
				// get the row data
				$row = isset($data[$row_name]) ? $data[$row_name] : array();

				// is this a new row?
				$new = (substr($row_name, 0, 8) == 'row_new_');

				if (! $new)
				{
					$row_id = substr($row_name, 7);
				}

				// -------------------------------------------
				//  Prep the row's DB data
				// -------------------------------------------

				$row_data = array(
					'row_order' => $row_order
				);

				foreach ($cols as &$col)
				{
					$cell_data = isset($row['col_id_'.$col['col_id']]) ? $row['col_id_'.$col['col_id']] : '';

					// give the celltype a chance to do what it wants with it
					if ($col['has_save_cell'])
					{
						$celltype = $this->_get_celltype($col['col_type']);
						$celltype->settings = $col['celltype_settings'];
						$celltype->settings['entry_id'] = $this->settings['entry_id'];
						$celltype->settings['row_name'] = $row_name;

						$cell_data = $celltype->save_cell($cell_data);
					}

					$row_data['col_id_'.$col['col_id']] = $cell_data;
				}

				// -------------------------------------------
				//  Save or update the row
				// -------------------------------------------

				if ($new)
				{
					$row_data['site_id']  = $this->EE->config->item('site_id');
					$row_data['entry_id'] = $this->settings['entry_id'];
					$row_data['field_id'] = $field_id;

					// insert the row
					$this->EE->db->insert('matrix_data', $row_data);

					// get the new row_id
					$row_id = $this->EE->db->insert_id();
				}
				else
				{
					// just update the existing row
					$this->EE->db->where('row_id', $row_id)
					             ->update('matrix_data', $row_data);
				}

				// -------------------------------------------
				//  post_save_cell()
				// -------------------------------------------

				foreach ($cols as &$col)
				{
					if ($col['has_post_save_cell'])
					{
						$celltype = $this->_get_celltype($col['col_type']);
						$celltype->settings = $col['celltype_settings'];
						$celltype->settings['entry_id'] = $this->settings['entry_id'];
						$celltype->settings['row_id']   = $row_id;
						$celltype->settings['row_name'] = $row_name;

						$celltype->post_save_cell($row_data['col_id_'.$col['col_id']]);
					}
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete
	 */
	function delete($entry_ids)
	{
		$rows = $this->EE->db->select('row_id')
		                     ->where_in('entry_id', $entry_ids)
		                     ->get('matrix_data');

		if ($rows->num_rows())
		{
			$row_ids = array();

			foreach ($rows->result() as $row)
			{
				$row_ids[] = $row->row_id;
			}

			$this->delete_rows($row_ids);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Data Query
	 */
	private function _data_query($params, $cols, $select_mode = 'data', $select_aggregate = '')
	{
		if (! $cols) return FALSE;

		// -------------------------------------------
		//  What's and Where's
		// -------------------------------------------

		$col_ids_by_name = array();

		$select = 'row_id';
		$where = '';
		$use_where = FALSE;

		foreach ($cols as &$col)
		{
			$col_id = 'col_id_'.$col['col_id'];
			$col_ids_by_name[$col['col_name']] = $col['col_id'];

			if ($select_mode == 'data') $select .= ', '.$col_id;

			if (isset($params['search:'.$col['col_name']]))
			{
				$use_where = TRUE;
				$terms = $params['search:'.$col['col_name']];

				if (strncmp($terms, '=', 1) == 0)
				{
					// -------------------------------------------
					//  Exact Match e.g.: search:body="=pickle"
					// -------------------------------------------

					$terms = substr($terms, 1);

					// special handling for IS_EMPTY
					if (strpos($terms, 'IS_EMPTY') !== FALSE)
					{
						$terms = str_replace('IS_EMPTY', '', $terms);

						$add_search = $this->EE->functions->sql_andor_string($terms, $col_id);

						// remove the first AND output by $this->EE->functions->sql_andor_string() so we can parenthesize this clause
						$add_search = substr($add_search, 3);

						$not = (strncmp($terms, 'not ', 4) == 0);
						$conj = ($add_search != '' && ! $not) ? 'OR' : 'AND';

						if ($not)
						{
							$where .= 'AND ('.$add_search.' '.$conj.' '.$col_id.' != "") ';
						}
						else
						{
							$where .= 'AND ('.$add_search.' '.$conj.' '.$col_id.' = "") ';
						}
					}
					else
					{
						$where .= $this->EE->functions->sql_andor_string($terms, $col_id).' ';
					}
				}
				else
				{
					// -------------------------------------------
					//  "Contains" e.g.: search:body="pickle"
					// -------------------------------------------

					if (strncmp($terms, 'not ', 4) == 0)
					{
						$terms = substr($terms, 4);
						$like = 'NOT LIKE';
					}
					else
					{
						$like = 'LIKE';
					}

					if (strpos($terms, '&&') !== FALSE)
					{
						$terms = explode('&&', $terms);
						$andor = (strncmp($like, 'NOT', 3) == 0) ? 'OR' : 'AND';
					}
					else
					{
						$terms = explode('|', $terms);
						$andor = (strncmp($like, 'NOT', 3) == 0) ? 'AND' : 'OR';
					}

					$where .= ' AND (';

					foreach ($terms as $term)
					{
						if ($term == 'IS_EMPTY')
						{
							$where .= ' '.$col_id.' '.$like.' "" '.$andor;
						}
						else if (preg_match('/^[<>]=?/', $term, $match)) // less than/greater than
						{
							$term = substr($term, strlen($match[0]));

							$where .= ' '.$col_id.' '.$match[0].' "'.$this->EE->db->escape_str($term).'" '.$andor;
						}
						else if (strpos($term, '\W') !== FALSE) // full word only, no partial matches
						{
							$not = ($like == 'LIKE') ? ' ' : ' NOT ';

							// Note: MySQL's nutty POSIX regex word boundary is [[:>:]]
							$term = '([[:<:]]|^)'.preg_quote(str_replace('\W', '', $term)).'([[:>:]]|$)';

							$where .= ' '.$col_id.$not.'REGEXP "'.$this->EE->db->escape_str($term).'" '.$andor;
						}
						else
						{
							$where .= ' '.$col_id.' '.$like.' "%'.$this->EE->db->escape_like_str($term).'%" '.$andor;
						}
					}

					$where = substr($where, 0, -strlen($andor)).') ';
				}
			}
		}

		// -------------------------------------------
		//  Row IDs
		// -------------------------------------------

		if ($fixed_order = (isset($params['fixed_order']) && $params['fixed_order']))
		{
			$params['row_id'] = $params['fixed_order'];
		}

		if (isset($params['row_id']) && $params['row_id'])
		{
			$use_where = TRUE;

			if (strncmp($params['row_id'], 'not ', 4) == 0)
			{
				$not = 'NOT ';
				$params['row_id'] = substr($params['row_id'], 4);
			}
			else
			{
				$not = '';
			}

			$row_ids = explode('|', $params['row_id']);

			$where .= ' AND row_id '.$not.'IN (' . implode(',', $row_ids) . ')';
		}

		$sql = 'SELECT '.($select_mode == 'aggregate' ? $select_aggregate.' aggregate' : $select).'
		        FROM   exp_matrix_data
		        WHERE  field_id = '.$this->field_id.'
		               AND entry_id = '.$this->row['entry_id'].'
		               '.($use_where ? $where : '');

		// -------------------------------------------
		//  Orberby + Sort
		// -------------------------------------------

		$orderbys = (isset($params['orderby']) && $params['orderby']) ? explode('|', $params['orderby']) : array('row_order');
		$sorts    = (isset($params['sort']) && $params['sort']) ? explode('|', $params['sort']) : array();

		$all_orderbys = array();
		foreach ($orderbys as $i => $name)
		{
			$name = (isset($col_ids_by_name[$name])) ? 'col_id_'.$col_ids_by_name[$name] : $name;
			$sort = (isset($sorts[$i]) && strtoupper($sorts[$i]) == 'DESC') ? 'DESC' : 'ASC';
			$all_orderbys[] = $name.' '.$sort;
		}

		$sql .=  ' ORDER BY '.implode(', ', $all_orderbys);

		// -------------------------------------------
		//  Offset and Limit
		// -------------------------------------------

		// if we're not sorting randomly, go ahead and set the offset and limit in the SQL
		if ((! isset($params['sort']) || $params['sort'] != 'random') && (isset($params['limit']) || isset($params['offset'])))
		{
			$offset = (isset($params['offset']) && $params['offset'] && $params['offset'] > 0) ? $params['offset'] . ', ' : '';
			$limit  = (isset($params['limit']) && $params['limit'] && $params['limit'] > 0) ? $params['limit'] : 100;

			$sql .= ' LIMIT ' . $offset . $limit;
		}

		// -------------------------------------------
		//  Run and return
		// -------------------------------------------

		// -------------------------------------------
		//  'matrix_data_query' hook
		//   - Override the SQL query
		// 
			if ($select_mode == 'data' && $this->EE->extensions->active_hook('matrix_data_query'))
			{
				$query = $this->EE->extensions->call('matrix_data_query', $this, $params, $sql);
			}
			else
			{
				$query = $this->EE->db->query($sql);
			}
		// 
		// -------------------------------------------

		switch ($select_mode)
		{
			case 'data':

				$data = $query->result_array();

				if ($fixed_order)
				{
					$data_by_id = array();

					foreach ($data as $row)
					{
						$data_by_id[$row['row_id']] = $row;
					}

					$data = array();

					foreach ($row_ids as $row_id)
					{
						if (isset($data_by_id[$row_id]))
						{
							$data[] = $data_by_id[$row_id];
						}
					}
				}

				return $data ? $data : FALSE;

			case 'aggregate':

				return $query->row('aggregate');

			case 'row_ids':

				$row_ids = array();

				foreach ($query->result() as $row)
				{
					$row_ids[] = $row->row_id;
				}

				return $row_ids;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Tag
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		// ignore if no tagdata
		if (! $tagdata) return;

		// dynamic params
		if (isset($params['dynamic_parameters']))
		{
			$dynamic_parameters = explode('|', $params['dynamic_parameters']);
			foreach ($dynamic_parameters as $param)
			{
				if (($val = $this->EE->input->post($param)) !== FALSE)
				{
					$params[$param] = $this->EE->db->escape_str($val);
				}
			}
		}

		$r = '';

		// -------------------------------------------
		//  Get the columns
		// -------------------------------------------

		$cols = $this->_get_field_cols($this->field_id);
		if (! $cols) return $r;

		// -------------------------------------------
		//  Get the data
		// -------------------------------------------

		$data = $this->_data_query($params, $cols);

		if (! $data) return $r;

		// -------------------------------------------
		//  Randomize
		// -------------------------------------------

		if (isset($params['sort']) && $params['sort'] == 'random')
		{
			shuffle($data);

			// apply the limit now, since we didn't do it in the original query
			if (isset($params['limit']) && $params['limit'])
			{
				$data = array_splice($data, 0, $params['limit']);
			}
		}

		// -------------------------------------------
		//  Tagdata
		// -------------------------------------------

		// get the full list of row IDs
		$field_row_ids_params = array_merge((array) $params, array('row_id' => '', 'limit' => '', 'offset' => ''));
		$this->_field_row_ids = $this->_data_query($field_row_ids_params, $cols, 'row_ids');
		$this->_field_total_rows = count($this->_field_row_ids);

		// are {prev_row} or {next_row} being used?
		$siblings_in_use = ((strstr($tagdata, 'prev_row') !== FALSE) || (strstr($tagdata, 'next_row') !== FALSE));

		// see which col tags are being used
		foreach ($cols as &$col)
		{
			$col['in_use'] = preg_match('/\{'.$col['col_name'].'[\}: ]/', $tagdata) ? TRUE : FALSE;
		}

		// {total_rows} and {field_total_rows}
		$vars = array(
			'total_rows' => count($data),
			'field_total_rows' => $this->_field_total_rows
		);
		$tagdata = $this->EE->functions->var_swap($tagdata, $vars);
		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $vars);

		// process each row
		foreach ($data as $this->_row_index => &$row)
		{
			$row_tagdata = $tagdata;

			// get the row's index within the entire field
			$this->_field_row_index = array_search($row['row_id'], $this->_field_row_ids);

			// parse sibling tags
			if ($siblings_in_use)
			{
				$conditionals = array(
					'prev_row' => ($this->_field_row_index > 0 ? 'y' : ''),
					'next_row' => ($this->_field_row_index < $this->_field_total_rows-1 ? 'y' : '')
				);

				$row_tagdata = $this->EE->functions->prep_conditionals($row_tagdata, $conditionals);

				// {prev_row} and {next_row} tag pairs
				$row_tagdata = preg_replace_callback('/'.LD.'(prev_row|next_row)'.RD.'(.*)'.LD.'\/\1'.RD.'/sU', array(&$this, '_parse_sibling_tag'), $row_tagdata);
			}

			$conditionals = array();
			$tags = array();

			foreach ($cols as &$col)
			{
				$col_name = 'col_id_'.$col['col_id'];

				$cell_data = $row[$col_name];

				$conditionals[$col['col_name']] = $cell_data;

				if ($col['in_use'])
				{
					$celltype = $this->_get_celltype($col['col_type']);

					$celltype_vars = array(
						'row'        => $this->row,
						'field_id'   => $this->field_id,
						'field_name' => $this->field_name,
						'col_id'     => $col['col_id'],
						'col_name'   => $col_name,
						'row_id'     => $row['row_id'],
						'row_name'   => 'row_id_'.$row['row_id'],
						'settings'   => array_merge($this->settings, $celltype->settings, $col['col_settings'])
					);

					// call pre_process?
					if (method_exists($celltype, 'pre_process'))
					{
						foreach ($celltype_vars as $key => $value)
						{
							$celltype->$key = $value;
						}

						$cell_data = $celltype->pre_process($cell_data);
					}

					$tags[$col['col_name']] = array(
						'data' => $cell_data,
						'type' => $col['col_type'],
						'vars' => $celltype_vars
					);
				}
			}

			$vars = array(
				'field_row_index' => $this->_field_row_index,
				'field_row_count' => $this->_field_row_index + 1,
				'row_index'       => $this->_row_index,
				'row_count'       => $this->_row_index + 1,
				'row_id'          => $row['row_id']
			);

			$row_tagdata = $this->EE->functions->var_swap($row_tagdata, $vars);
			$row_tagdata = $this->EE->functions->prep_conditionals($row_tagdata, array_merge($vars, $conditionals));

			if ($tags)
			{
				$this->_parse_tagdata($row_tagdata, $tags);
			}

			// {switch} tags
			$row_tagdata = preg_replace_callback('/'.LD.'switch\s*=\s*([\'\"])([^\1]+)\1'.RD.'/sU', array(&$this, '_parse_switch_tag'), $row_tagdata);

			$r .= $row_tagdata;
		}

		unset($this->_field_row_ids, $this->_field_total_rows, $this->_field_row_index, $this->_row_index);

		if (isset($params['backspace']) && $params['backspace'])
		{
			$r = substr($r, 0, -$params['backspace']);
		}

		return $r;
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Step Tag
	 */
	private function _parse_sibling_tag($match)
	{
		if ($match[1] == 'prev_row')
		{
			// ignore if this is the first row
			if ($this->_field_row_index == 0) return;

			$row_id = $this->_field_row_ids[$this->_field_row_index-1];
		}
		else
		{
			// ignore if this is the last row
			if ($this->_field_row_index == $this->_field_total_rows - 1) return;

			$row_id = $this->_field_row_ids[$this->_field_row_index+1];
		}

		$obj = new Matrix_ft();
		$obj->settings = $this->settings;
		$obj->row = $this->row;
		$obj->field_id = $this->field_id;
		$obj->field_name = $this->field_name;
		return $obj->replace_tag('', array('row_id' => $row_id), $match[2]);
	}

	/**
	 * Parse Switch Tag
	 */
	private function _parse_switch_tag($match)
	{
		$options = explode('|', $match[2]);

		$option = $this->_row_index % count($options);

		return $options[$option];
	}

	// --------------------------------------------------------------------

	/**
	 * Table
	 */
	function replace_table($data, $params = array())
	{
		$thead = '';
		$tagdata = '    <tr>' . "\n";

		// get the cols
		$cols = $this->_get_field_cols($this->field_id);
		if (! $cols) return '';

		// which table features do they want?
		$set_row_ids = (isset($params['set_row_ids']) && $params['set_row_ids'] == 'yes');
		$set_classes = (isset($params['set_classes']) && $params['set_classes'] == 'yes');
		$set_widths = (isset($params['set_widths']) && $params['set_widths'] == 'yes');

		$thead = '';
		$tagdata = '    <tr'.($set_row_ids ? ' id="row_id_'.LD.'row_id'.RD.'"' : '').'>' . "\n";

		foreach ($cols as &$col)
		{
			$attr = '';
			if ($set_classes) $attr .= ' class="'.$col['col_name'].'"';
			if ($set_widths)  $attr .= ' width="'.$col['col_width'].'"';

			$thead .= '      <th scope="col"'.$attr.'>'.$col['col_label'].'</th>' . "\n";
			$tagdata .= '      <td'.$attr.'>'.LD.$col['col_name'].RD.'</td>' . "\n";
		}

		$tagdata .= '    </tr>' . "\n";

		$attr = '';
		if (isset($params['cellspacing'])) $attr .= ' cellspacing="'.$params['cellspacing'].'"';
		if (isset($params['cellpadding'])) $attr .= ' cellpadding="'.$params['cellpadding'].'"';
		if (isset($params['border']))      $attr .= ' border="'.$params['border'].'"';
		if (isset($params['width']))       $attr .= ' width="'.$params['width'].'"';
		if (isset($params['class']))       $attr .= ' class="'.$params['class'].'"';

		return '<table'.$attr.'>' . "\n"
		     . '  <thead>' . "\n"
		     . '    <tr>' . "\n"
		     .        $thead
		     . '    </tr>' . "\n"
		     . '  </thead>' . "\n"
		     . '  <tbody>' . "\n"
		     .      $this->replace_tag($data, $params, $tagdata)
		     . '  </tbody>' . "\n"
		     . '</table>';
	}

	// --------------------------------------------------------------------

	/**
	 * Replace Sibling Tag
	 */
	private function _replace_sibling_tag($params, $tagdata, $which)
	{
		// ignore if no tagdata
		if (! $tagdata) return;

		// get the cols
		$cols = $this->_get_field_cols($this->field_id);
		if (! $cols) return;

		// get the full list of row IDs
		$field_row_ids_params = array_merge($params, array('row_id' => '', 'limit' => '', 'offset' => ''));
		$field_row_ids = $this->_data_query($field_row_ids_params, $cols, 'row_ids');
		$field_total_rows = count($field_row_ids);

		// get the starting row's ID
		if (isset($params['row_id']) && $params['row_id'])
		{
			$row_id = $params['row_id'];
		}
		else
		{
			$query_params = array_merge($params, array('limit' => '1'));
			$query = $this->_data_query($query_params, $cols, 'row_ids');
			$row_id = $query[0];
		}

		// get the starting row's index within the entire field
		$field_row_index = array_search($row_id, $field_row_ids);

		if ($which == 'prev')
		{
			// ignore if this is the first row
			if ($field_row_index == 0) return;

			$sibling_row_id = $field_row_ids[$field_row_index-1];
		}
		else
		{
			// ignore if this is the last row
			if ($field_row_index == $field_total_rows - 1) return;

			$sibling_row_id = $field_row_ids[$field_row_index+1];
		}

		return $this->replace_tag('', array('row_id' => $sibling_row_id), $tagdata);
	}

	/**
	 * Previous Row
	 */
	function replace_prev_row($data, $params = array(), $tagdata = FALSE)
	{
		return $this->_replace_sibling_tag($params, $tagdata, 'prev');
	}

	/**
	 * Next Row
	 */
	function replace_next_row($data, $params = array(), $tagdata = FALSE)
	{
		return $this->_replace_sibling_tag($params, $tagdata, 'next');
	}

	// --------------------------------------------------------------------

	/**
	 * Total Rows
	 */
	function replace_total_rows($data, $params = array())
	{
		$cols = $this->_get_field_cols($this->field_id);
		if (! $cols) return 0;

		return $this->_data_query($params, $cols, 'aggregate', 'COUNT(row_id)');
	}

	// --------------------------------------------------------------------

	/**
	 * Get Col ID by Name
	 */
	function _get_col_id_by_name($col_name, $cols)
	{
		// find the target col
		foreach ($cols as $col)
		{
			if ($col['col_name'] == $col_name) return $col['col_id'];
		}
	}

	/**
	 * Format Number
	 */
	private function _format_number($params, $num)
	{
		$decimals = isset($params['decimals']) ? (int) $params['decimals'] : 0;
		$dec_point = isset($params['dec_point']) ? $params['dec_point'] : '.';
		$thousands_sep = isset($params['thousands_sep']) ? $params['thousands_sep'] : ',';

		return number_format($num, $decimals, $dec_point, $thousands_sep);
	}

	/**
	 * Aggregate Column
	 */
	private function _aggregate_col($params, $func)
	{
		// ignore if no col= param is set
		if (! isset($params['col']) || ! $params['col']) return;

		$cols = $this->_get_field_cols($this->field_id);
		if (! $cols) return;

		// get the col_id
		$col_id = $this->_get_col_id_by_name($params['col'], $cols);

		// ignore if that col doesn't exist
		if (! $col_id) return;

		$num = $this->_data_query($params, $cols, 'aggregate', "{$func}(col_id_{$col_id})");

		return $this->_format_number($params, $num);
	}

	/**
	 * Average
	 */
	function replace_average($data, $params = array())
	{
		return $this->_aggregate_col($params, 'AVG');
	}

	/**
	 * Sum
	 */
	function replace_sum($data, $params = array())
	{
		return $this->_aggregate_col($params, 'SUM');
	}

	/**
	 * Lowest
	 */
	function replace_lowest($data, $params = array())
	{
		return $this->_aggregate_col($params, 'MIN');
	}
	/**
	 * Highest
	 */
	function replace_highest($data, $params = array())
	{
		return $this->_aggregate_col($params, 'MAX');
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Tagdata
	 */
	private function _parse_tagdata(&$tagdata, $tags)
	{
		global $DSP;

		// find the next celltype tag
		$offset = 0;
		while (preg_match('/'.LD.'('.implode('|', array_keys($tags)).')(:(\w+))?(\s+.*)?'.RD.'/sU', $tagdata, $matches, PREG_OFFSET_CAPTURE, $offset))
		{
			$field_name = $matches[1][0];
			$field = $tags[$field_name];

			$tag_pos = $matches[0][1];
			$tag_len = strlen($matches[0][0]);
			$tagdata_pos = $tag_pos + $tag_len;
			$endtag = LD.'/'.$field_name.(isset($matches[2][0]) ? $matches[2][0] : '').RD;
			$endtag_len = strlen($endtag);
			$endtag_pos = strpos($tagdata, $endtag, $tagdata_pos);
			$tag_func = (isset($matches[3][0]) && $matches[3][0]) ? 'replace_'.$matches[3][0] : '';

			if (! $tag_func) $tag_func = 'replace_tag';
			$class = $this->_get_celltype_class($field['type'], TRUE);
			$method_exists = method_exists($class, $tag_func);

			if ($method_exists)
			{
				// get the params
				$params = array();

				if (isset($matches[4][0]) && $matches[4][0] && preg_match_all('/\s+([\w-:]+)\s*=\s*([\'\"])([^\2]*)\2/sU', $matches[4][0], $param_matches))
				{
					for ($j = 0; $j < count($param_matches[0]); $j++)
					{
						$params[$param_matches[1][$j]] = $param_matches[3][$j];
					}
				}

				// get inner tagdata
				$field_tagdata = ($endtag_pos !== FALSE)
				  ?  substr($tagdata, $tagdata_pos, $endtag_pos - $tagdata_pos)
				  :  '';

				$celltype = $this->_get_celltype($field['type']);
				$this->_add_package_path($celltype);

				foreach ($field['vars'] as $key => $value)
				{
					$celltype->$key = $value;
				}

				$new_tagdata = (string) $celltype->$tag_func($field['data'], $params, $field_tagdata);
			}
			else
			{
				$new_tagdata = $field['data'];
			}

			$offset = $tag_pos;

			$tagdata = substr($tagdata, 0, $tag_pos)
			         . $new_tagdata
			         . substr($tagdata, ($endtag_pos !== FALSE ? $endtag_pos+$endtag_len : $tagdata_pos));

			unset($new_tagdata);
		}
	}

}
