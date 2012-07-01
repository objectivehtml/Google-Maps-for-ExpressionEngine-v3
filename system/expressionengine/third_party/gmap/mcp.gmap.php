<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.0.184
 * @build		20120617
 */

class Gmap_mcp {

	function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->library('Google_maps');
		$this->EE->load->library('Theme_loader');
		$this->EE->load->driver('Interface_builder');
		$this->EE->load->driver('Channel_data');
		$this->EE->load->library('Data_import');
		
	}
	
	function index()
	{		
		$vars = array(
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_data_action'),
			'settings' => $this->EE->data_import->get_settings()
		);
		
		return $this->EE->load->view('csv_index', $vars, TRUE);
	}
	
	public function import_data_action()
	{		
		$entries    = $this->EE->data_import->load_file($_FILES["file"]['tmp_name']);
		$settings   = (array) $this->EE->data_import->get_setting($this->EE->input->post('id'));
		$categories = array();;
		
		foreach($this->EE->channel_data->get_categories()->result() as $category)
		{
			$categories[$category->{$settings['category_column_type']}] = $category;
		}
		
		$data = array();
		
		foreach($entries as $entry)
		{
			$geocode = FALSE;
			
			foreach($settings['geocode_fields'] as $field)
			{
				$geocode .= isset($entry[$field->field_name]) ? $entry[$field->field_name] . ' ' : NULL;
			}
			
			if(!isset($data[$entry[$settings['group_by']]]))
			{
				$data[$entry[$settings['group_by']]] = array(
					'settings_id' => $this->EE->input->post('id'),
					'status'	  => 'pending',
					'gmt_date'    => $this->EE->localize->now,
					'geocode'	  => trim($geocode),
					'data'        => json_encode($entry),
					'categories'  => $categories[$entry[$settings['category_column']]]->cat_id
				);	
			}
			else
			{
				$data[$entry[$settings['group_by']]]['categories'] .= '|'.$categories[$entry[$settings['category_column']]]->cat_id;
			}
		}
		
		$this->EE->db->insert_batch('gmap_upload_pool', $data);
		
		exit();
	}
	
	function settings()
	{
		$fields = array(
			'settings' => array(
				'title' => 'Upload Settings',
				'attributes'  => array(
					'class' => 'mainTable padTable',
					'border' => 0,
					'cellpadding' => 0,
					'cellspacing' => 0
				),
				'wrapper' => 'div',
				'fields'  => array(
					'id' => array(
						'label'       => 'ID',
						'description' => 'Since you can save multiple variations of settings to import, your must give a unique identifier for these settings.',
						'type'        => 'input'
					),
					'channel' => array(
						'label'       => 'Channel',
						'description' => 'This is the channel where your data will be uploaded.',
						'type'        => 'select',
						'settings' => array(
							'options' => 'CHANNEL_DROPDOWN'
						)
					),
					'gmap_field' => array(
						'label'       => 'Map Field',
						'description' => 'This is the name of Google Maps for ExpressionEngine fieldtype to store your data.',
						'type'        => 'select',
						'settings' => array(
							'options' => 'FIELD_DROPDOWN'
						)
					),
					'geocode_fields' => array(
						'label'       => 'Geocode Fields',
						'description' => 'This field is used to define the column(s) in the .CSV you wish to use for geocoding.',
						'type'	=> 'matrix',
						'settings' => array(
							'columns' => array(
								0 => array(
									'name'  => 'field_name',
									'title' => 'Column Name'
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
					'author_id' => array(
						'label'       => 'Author ID',
						'description' => 'This is the author id that will be assigned to the uploaded entries.',
						'type'        => 'input'
					),
					'channel_fields' => array(
						'label' => 'Channel Fields',
						'description' => 'Use this field to pair up your channel fields with the columns in the .csv file. Be sure to make sure the names are an exact match.',
						'id'    => 'field_map_fields',
						'type'	=> 'matrix',
						'settings' => array(
							'columns' => array(
								0 => array(
									'name'  => 'field_name',
									'title' => 'Channel Field Name'
								),
								1 => array(
									'name'  => 'column_name',
									'title' => 'CSV Column Name'
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
					'group_by' => array(
						'label'       => 'Group By',
						'description' => 'This is the unique identifier for each row that will group your entries together. If there are duplicate rows, define the column in the .csv that stores the ID.',
						'type'        => 'input'
					),
					'category_column' => array(
						'label'       => 'Category Column',
						'description' => 'This is the name of the column that stores the category.',
						'type'        => 'input'
					),
					'category_column_type' => array(
						'label'       => 'Category Column Type',
						'description' => 'This is the type of data stored in the cateogry column.',
						'type'        => 'select',
						'settings' => array(
							'options' => array(
								'cat_name'      => 'Category Name',
								'cat_url_title' => 'Category URL Title',
								'cat_id'        => 'Category ID',
							)
						)
					),
				)
			)
		);
		
		$data = (array) $this->EE->data_import->get_setting($this->EE->input->get('id'));
		
		$this->EE->interface_builder->data = $data;
		$this->EE->interface_builder->add_fieldsets($fields);

		$vars = array(
			'return'   => $this->EE->google_maps->current_url(TRUE, TRUE, config_item('cp_url')),
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'gmap_import_csv_save_settings_action'),
			'interface_builder' => $this->EE->theme_loader->theme_url().'third_party/gmap/javascript/InterfaceBuilder.js',
			'settings' => $this->EE->interface_builder->fieldsets()
		);
		
		return $this->EE->load->view('csv_import', $vars, TRUE);
	}
	
	public function gmap_import_csv_save_settings_action()
	{		
		$return = $this->EE->input->post('return');
		
		unset($_POST['return']);
		
		$this->EE->data_import->save_settings($this->EE->input->post('id'), json_encode($_POST));
		$this->EE->functions->redirect($return);
	}
	
	public function import_csv_action()
	{
		$this->EE->data_import->run();
	}
	
	private function update()
	{
		$update = new Gmap_upd();
		$update->update();
	}
}
// END CLASS

/* End of file mcp.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/mcp.gmap.php */