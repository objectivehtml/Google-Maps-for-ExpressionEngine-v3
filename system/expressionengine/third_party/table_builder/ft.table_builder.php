<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Table Builder
 * 
 * @package		Table Builder
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com
 * @version		0.2.2
 * @build		20120511
 */

require 'config/table_builder_config.php';

if(!defined('TABLE_BUILDER_VERSION'))
{	
	define('TABLE_BUILDER_VERSION', $config['table_builder_version']);
}

class Table_builder_ft extends EE_Fieldtype {

	public $info = array(
		'name'			=> 'Table Builder',
		'version'		=> TABLE_BUILDER_VERSION
	);
	
	public $has_array_data 		= TRUE;
	public $safecracker			= FALSE;
		
	private $default_settings	= array(
	);
	
	public function __construct()
	{
		parent::__construct();

		if(isset($this->EE->safecracker_lib))
		{
			$this->safecracker = TRUE;
		}
		
		$this->info['version'] = TABLE_BUILDER_VERSION;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Installs the plugin by returning the default settings array.
	 *
	 * @access	public
	 * @return	array
	 */
	 
	function install()
	{
		return $this->default_settings;
	}
	
	/**
	 * Gets the settings directly from the database
	 *
	 * @param	array
	 * @access	public
	 * @return	array
	 */
	
	private function get_settings($merge = array())
	{
		$this->EE->load->driver('channel_data');
		
		$settings = $this->EE->channel_data->get_field($this->field_id)->row('field_settings');
		
		$settings = array_merge($merge, unserialize(base64_decode($settings)));
		
		foreach($settings as $index => $setting)
		{
			$settings[str_replace('table_builder_', '', $index)] = $setting;
			
			if(strstr($index, 'table_builder_') == TRUE)
			{
				unset($settings[$index]);
			}
		}
		
		return $settings;
	}
	
	/**
	 * Displays the fieldtype
	 *
	 * @access	public
	 * @param 	array
	 * @return	string
	 */
	
	function display_field($data)
	{	
		$this->EE->load->driver('channel_data');
		$this->EE->load->library('table_builder_lib');
		$this->EE->load->library('theme_loader', array('table_builder'));
		$this->EE->load->model('table_builder_model');

		$field_id = $this->settings['field_id'];
		$entry_id = FALSE;
		$columns  = '[]';
		$rows     = '[]';

		/* Restores table if form validation fails */ 
		if(isset($this->EE->session->cache['table_builder']['post_data']))
		{
			$data = $this->EE->session->cache['table_builder']['post_data'];

			$header = array('<th class="ui-tb-id-column"></th>');
			
			$column_array = array();

			foreach($data['cell'][0] as $column_name => $cell_value)
			{
				$column = json_decode($data['column'][$column_name]);

				unset($column->html);

				$header[] = '<th class="ui-tb-resizable" style="'.($column->width ? 'width: '.$column->width.'px' : '').'"><div class="ui-tb-relative"><a href="#ui-tb-column-menu" class="ui-tb-column-button">&#x25BE;</a><span class="title">'.$column->title.'</span></div></th>';

				$hidden[] = '<input type="hidden" name="'.$this->field_name.'[column]['.$column->name.']" value="'.form_prep(json_encode($column)).'"  class="ui-tb-hidden-column ui-tb-hidden-value" />';
				
				$column_array[] = $column;
			}
		
			$data['column'] = $column_array;

			$count = 0;

			$row_array = array();

			for($index = 0; $index < count($data['row']); $index++)
			{
				$row_value = $data['row'][$index];

				$row_array[] = $row_value;

				$row = json_decode($row_value);
				
				$cells[$index][] = '<td class="ui-tb-id-column"><div class="ui-tb-relative"><span class="ui-tb-drag-handle"></span><div class="ui-tb-number">'.($index+1).'</div></div></td>';
			
				foreach($data['column'] as $column)
				{
					$value = $data['cell'][$index][$column->name];
					$cell  = $this->EE->table_builder_lib->load_cell_type($column->celltype, $value);

					$cells[$index][] = '<td>'.str_replace('{DEFAULT}', $this->field_name.'[cell]['.$index.']['.$column->name.']', $cell->html).'</td>';
				}

				$cells[$index] = '<tr>'.implode('', $cells[$index]).'</tr>';

				$hidden[] = '<input type="hidden" name="'.$this->field_name.'[row]['.$index.']" value="'.form_prep($row_value).'"  class="ui-tb-hidden-row ui-tb-hidden-value" />';
			}

			if(isset($data['delete']))
			{
				foreach($data['delete'] as $id)
				{
					$hidden[] = '<input type="hidden" name="'.$this->field_name.'[delete][]" value="'.form_prep($id).'"  class="ui-tb-hidden-row ui-tb-hidden-value" />';
				}
			}

			$data['row'] = $row_array;

			$style = (int) count($data['row'])  > 0 ? 'style="display:none"' : null;

			$table = '
			<div class="ui-tb-message ui-tb-empty" ' . $style . '>
				<p>You have not created a table yet. Add your first column to the table to get started.</p>
			</div>

			<table class="ui-tb-table" cellspacing="0" cellpadding="0">
				<thead>'.implode("\n", $header).'</thead>
				<tbody>'.implode("\n", $cells).'</tbody>
			</table>
			'.implode("\n", $hidden);

			$entry_id = 0;
			$columns  = json_encode($data['column']);
			$rows     = json_encode($data['row']);		
		}
		else
		{
			$table = $this->EE->table_builder_lib->build_table($this->field_name, $this->field_id, $data);
			
			if(!empty($data))
			{
				$entry    = $this->EE->table_builder_model->get_entry($this->field_id, $data)->row();

				if(count($entry) > 0) {
					$entry_id = $entry->entry_id;
					$columns  = $entry->columns;
					$rows     = $entry->rows;
				}
			}

		}

		$vars = array(
			'field_id'   => $this->field_id,
			'field_mame' => $this->field_name,
			'table' 	 => $table,
			'presets'	 => $this->EE->table_builder_model->get_presets(array(
				'order_by' => 'name',
				'sort' => 'asc'
			))->result()
		);

		$this->EE->theme_loader->javascript('table_builder');
		$this->EE->theme_loader->javascript('validate');
		$this->EE->theme_loader->javascript('dragtable');
		$this->EE->theme_loader->javascript('qtip');
		$this->EE->theme_loader->javascript('tablednd');
		$this->EE->theme_loader->javascript('form');
		$this->EE->theme_loader->css('table_builder');
		//$this->EE->theme_loader->css('qtip');

		$this->EE->table_builder_lib->field_name = $this->field_name;

		$installed = $this->EE->table_builder_lib->get_installed_celltypes($field_id);
		$installed = json_encode($installed);

		$this->EE->javascript->output('
			var settings = {
				id: '.$this->field_id.',
				name: \''.$this->field_name.'\',
				entry_id: \''.$entry_id.'\',
				columns: '.$columns.',
				rows: '.$rows.',
				celltypes: '.$installed.',
				url: {
					loadPreset: \''.$this->_current_url('ACT', $this->EE->channel_data->get_action_id('Table_builder_mcp', 'load_preset')).'\',
					savePreset: \''.$this->_current_url('ACT', $this->EE->channel_data->get_action_id('Table_builder_mcp', 'save_preset')).'\',
					deletePreset: \''.$this->_current_url('ACT', $this->EE->channel_data->get_action_id('Table_builder_mcp', 'delete_preset')).'\'
				}
			};

			var table = new Table_Builder(settings);
		');

		return $this->EE->load->view('fieldtype', $vars, TRUE);
	}

	/**
	 * Displays the fieldtype settings
	 *
	 * @access	public
	 * @param 	array
	 * @return	string
	 */
	 
	function display_settings($data)
	{	
		$this->EE->load->driver('channel_data');
		$this->EE->load->library('table_builder_lib');
		$this->EE->load->library('theme_loader', array(
			'module_name'	=> 'gmap'
		));
		
		$vars = array();

		foreach($this->default_settings as $setting => $value)
		{
			$this->settings[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
		}

		$fields = array(
			'table_builder_min_cols' => array(
				'label' => 'Minimum Columns',
				'id'    => 'table_builder_min_cols',
				'description' => 'This is a test',
				'type'  => 'input'
			),
			'table_builder_min_rows' => array(
				'label' => 'Minimum Rows',
				'id'    => 'table_builder_min_rows',
				'type'  => 'input'
			),
			'table_builder_matrix' => array(
				'label'    => 'Matrix',
				'id'       => 'matrix',
				'type'     => 'matrix',
				'settings' => array(
					'columns' => array(
						array(
							'name'  => 'test',
							'title' => 'Test'
						),
						array(
							'name'  => 'asd',
							'title' => 'Asd'
						),
						array(
							'name'  => '123',
							'title' => '123'
						),
						array(
							'name'  => '456',
							'title' => '456'
						)
					),
					'attributes' => array(
						'class'       => 'mainTable padTable',
						'border'      => 0,
						'cellpadding' => 0,
						'cellspacing' => 0
					)
				)
			),
			'table_builder_matrix2' => array(
				'label'    => 'Matrix2',
				'id'       => 'matrix2',
				'type'     => 'matrix',
				'settings' => array(
					'columns' => array(
						array(
							'name'  => '123',
							'title' => '123'
						),
						array(
							'name'  => '456',
							'title' => '456'
						)
					),
					'attributes' => array(
						'class'       => 'mainTable padTable',
						'border'      => 0,
						'cellpadding' => 0,
						'cellspacing' => 0
					)
				)
			),
			'table_builder_checkbox' => array(
				'label'    => 'Check',
				'id'       => 'check',
				'type'     => 'checkbox',
				'settings' => array(
					'options' => array(
						'1' => '1',
						'2' => '2',
						'3' => '3'
					)
				)
			),
			'table_builder_radio' => array(
				'label'    => 'Radio',
				'id'       => 'radio',
				'type'     => 'radio',
				'settings' => array(
					'options' => array(
						'1' => '1',
						'2' => '2',
						'3' => '3'
					)
				)
			),
			'table_builder_select' => array(
				'label'    => 'Select',
				'id'       => 'select',
				'type'     => 'select',
				'settings' => array(
					'options' => array(
						'1' => '1',
						'2' => '2',
						'3' => '3'
					)
				)
			)
		);

		return $this->EE->load->view('fieldtype_settings', $vars, TRUE);
	}

	public function validate($data)
	{
		$this->EE->session->set_cache('table_builder', 'post_data', $data);
		
		return $this->EE->table_builder_lib->validate($data);
	}

	public function post_save($data)
	{
		$this->EE->load->driver('channel_data');
		$this->EE->load->library('table_builder_lib');

		$entry_id = $this->settings['entry_id'];
		$entry    = $this->EE->channel_data->get_channel_entry($entry_id)->row();
		$channel  = $this->EE->channel_data->get_channel($entry->channel_id)->row();
		
		$data     = $this->EE->session->cache['Table_Builder']['saved_data'][$this->field_id];

		$entry_info = array(
			'field_id'   => $this->field_id,
			'channel_id' => $channel->channel_id,
			'author_id'  => $entry->author_id,
			'entry_id'   => $entry->entry_id,
			'preset'	 => isset($data['preset']) && (int) $data['preset'] == 1 ? TRUE : FALSE
		);

		$cells    = isset($data['cell']) ? $data['cell'] : array();
		$rows     = isset($data['row']) ? $data['row'] : array();
		$columns  = isset($data['column']) ? $data['column'] : array();
		$delete   = isset($data['delete']) ? $data['delete'] : array();

		$this->EE->table_builder_lib->save($columns, $rows, $cells, $delete, $entry_info);

		$this->EE->db->where('entry_id', $this->settings['entry_id']);
		$this->EE->db->update('channel_data', array(
			'field_id_'.$this->settings['field_id'] => $this->settings['entry_id']
		));	

		return $data;
	}
	
	public function save_settings($data)
	{
		foreach($this->default_settings as $setting => $value)
		{
			$return[$setting]	= $this->EE->input->post($setting) !== FALSE ? 
								  $this->EE->input->post($setting) : $this->default_settings[$setting];
		}

		return $return;
	}
	public function save($data)
	{
		$existing = $this->EE->session->cache('Table_Builder', 'saved_data');
		$existing = $existing ? $existing : array();

		$existing[$this->field_id] = $data;

		$this->EE->session->set_cache('Table_Builder', 'saved_data', $existing);

		return NULL;
	}

	/**
	 * Saves the settings
	 *
	 * @access	public
	 * @param 	array
	 * @return	array
	 */
	 
	function post_save_settings($data)
	{
		$row = array(
			'field_id' => $data['field_id'],
			'title'    => 'text',
			'name'	   => 'text',
			'type'	   => 'Text',
			'settings' => '{}'
		);

		if($this->EE->db->get('table_builder_celltypes')->num_rows() == 0)
		{
			$this->EE->db->insert('table_builder_celltypes', array(
				'field_id' => $this->settings['field_id'],
				'title'    => $this->settings['field_name'],
				'name'	   => $this->settings['field_name'],
				'type'	   => 'Text',
				'settings' => '{}'
			));
		}
	}
	
	/**
	 * Validates Settings
	 *
	 * @access	private
	 * @param 	array	An array of settings to be validated
	 * @return	boolean
	 */
	 
	private function _validate_settings($settings)
	{
		return TRUE;
	}
	
	private function array_insert(&$array,$element,$position=null)
	{
		if (count($array) == 0)
		{
			$array[] = $element;
		}
		elseif (is_numeric($position) && $position < 0)
		{
			if((count($array)+position) < 0)
			{
		  		$array = array_insert($array,$element,0);
			}
			else
			{
		  		$array[count($array)+$position] = $element;
			}
		}
		elseif (is_numeric($position) && isset($array[$position]))
		{
			$part1 = array_slice($array,0,$position,true);
			$part2 = array_slice($array,$position,null,true);
			$array = array_merge($part1,array($position=>$element),$part2);
			
			foreach($array as $key=>$item)
			{
			  	if (is_null($item))
			  	{
			  	  unset($array[$key]);
			  	}
			}
		}
		elseif (is_null($position))
		{
			$array[] = $element;
		}
		elseif (!isset($array[$position]))
		{
			$array[$position] = $element;
		}
		
		$array = array_merge($array);
		
		return $array;
	}


	private function bool_param($param)
	{
		if($param === TRUE || strtolower($param) == 'true' || strtolower($param) == 'yes')
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Replaces the template tag
	 *
	 * @access	public
	 * @param 	array
	 * @return	string
	 */
	
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		$this->EE->load->library('table_builder_lib');
		$this->EE->load->model('table_builder_model');

		$limit  = isset($params['limit']) ? $params['limit'] : FALSE;
		$offset = isset($params['offset']) ? $params['offset'] : FALSE;
		$order  = isset($params['order_by']) ? $params['order_by'] : 'row_index';
		$sort   = isset($params['sort']) ? $params['sort'] : 'asc';

		$entries = $this->EE->table_builder_model->get_entry_data($this->field_id, $data, array(
			'limit'    => $limit,
			'offset'   => $offset,
			'order_by' => $order,
			'sort'     => $sort,
		));
		
		$entries = $this->EE->table_builder_lib->parse($entries);
		
		$return  = array();

		$return[0]['columns'] = $this->EE->table_builder_lib->parse_columns($entries->columns);
		$return[0]['rows']    = $this->EE->table_builder_lib->parse_rows($entries->rows);

		return $this->EE->TMPL->parse_variables($tagdata, $return);
	}
	
	function replace_html($data, $params = array(), $tagdata = FALSE)
	{
		$this->EE->load->library('table_builder_lib');
		$this->EE->load->model('table_builder_model');

		$entry     = $this->EE->table_builder_model->get_entry_data($this->field_id, $data);
		$entry     = $this->EE->table_builder_lib->parse($entry);

		$table_head = array('<thead>');

		foreach($entry->columns as $column_name => $column) 
		{
			$table_head[] = '<th class="'.$column_name.'">'.(!empty($column->title) ? $column->title : $column->name).'</th>';
		}

		$table_head[] = '</thead>';
		
		$table_body = array('<tbody>');

		foreach($entry->rows as $index => $row)
		{
			$table_row = array();

			$class = $index % 2 == 1 ? 'even' : 'odd';
			$count = 0;

			$attributes = $row->attributes;

			unset($row->attributes);

			foreach($row as $column => $value)
			{
				$table_row[] = '<td data-index="'.$count.'" class="'.$column.'">'.$value.'</td>';
				$count++;
			}

			$table_body[] = '<tr data-index="'.$index.'" class="'.trim($class.' '.$attributes->cssClass).'">'.implode('', $table_row).'</tr>';
		}

		$table_body[] = '</tbody>';

		foreach($params as $param => $value)
		{
			$params[$param] = $param . '="' . $value . '"';
		}

		$html = '<table '.implode(' ', $params).'>'.implode('', $table_head).''.implode('', $table_body).'</table>';

		return $html;
	}

	function replace_total_rows($data, $params = array(), $tagdata = FALSE)
	{
		$this->EE->load->model('table_builder_model');

		$entry = $this->EE->table_builder_model->get_entry_data($this->field_id, $data);
		
		return count($entry->rows);
	}

	function replace_total_columns($data, $params = array(), $tagdata = FALSE)
	{
		$this->EE->load->model('table_builder_model');

		$entry = $this->EE->table_builder_model->get_entry_data($this->field_id, $data);
		
		return count($entry->columns);
	}

	private function parse($vars, $tagdata = FALSE)
	{
		if($tagdata === FALSE)
		{
			$tagdata = $this->EE->TMPL->tagdata;
		}
			
		return $this->EE->TMPL->parse_variables($tagdata, $vars);
	}
	
	private function _url($method = 'index', $useAmp = FALSE)
	{
		$amp = !$useAmp ? AMP : '&';
		
		return str_replace(AMP, $amp, BASE . $amp . 'C=addons_modules' .$amp . 'M=show_module_cp' . $amp . 'module=Gmap' . $amp . 'method=' . $method);
	}
	
	private function _current_url($append = '', $value = '')
	{
		$url = (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['SERVER_NAME'] : 'http://'.$_SERVER['SERVER_NAME'];
		
		if(!empty($append))
			$url .= '?'.$append.'='.$value;
		
		return $url;
	}
	    

}
// END CLASS

/* End of file ft.gmap.php */
/* Location: ./system/expressionengine/third_party/google_maps/ft.google_maps.php */