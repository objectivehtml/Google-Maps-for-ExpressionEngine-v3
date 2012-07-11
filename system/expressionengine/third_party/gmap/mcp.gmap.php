<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.0.186
 * @build		20120711
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
		$this->EE->load->model('data_import_model');
	}
	
	public function index()
	{	
		$this->EE->cp->set_right_nav(array(
			'Manage Schemas' => $this->cp_url('schemas'),
			'New Schema' => $this->cp_url('settings'), 
		));
		
		$vars = array(
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_data_action'),
			'settings' => $this->EE->data_import_model->get_settings(),
			'stats'    => $this->EE->data_import_model->get_stats(),
			'import_url' => $this->cp_url('import_pool'),
			'return'   => $this->cp_url()
		);
		
		return $this->EE->load->view('csv_index', $vars, TRUE);
	}
	
	public function schemas()
	{
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Import' => $this->cp_url(),
			'New Schema' => $this->cp_url('settings'), 
		));
		
		$vars = array(
			'settings' => $this->EE->data_import_model->get_settings(),
			'edit_url' => $this->cp_url('settings')
		);
		
		return $this->EE->load->view('schemas', $vars, TRUE);		
	}
	
	public function import_pool()
	{
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Pool' => $this->cp_url(), 
		));
		
		$id = $this->EE->input->get('id');
		
		$settings = $this->EE->data_import_model->get_setting($id);
			
		$vars = array(
			'id' => $id,
			'total_items' => $this->EE->data_import_model->get_pools($id, 'pending')->num_rows(),
			'stats' => $this->EE->data_import_model->get_stats($id),
			'import_start_url' => $this->cp_url('import_start_action', TRUE),
			'import_item_url' => $this->cp_url('import_item_action', TRUE)
		);
		/*
		foreach($vars['items'] as $index => $item)
		{
			$item->data = json_decode($item->data);	
		}*/
		
		return $this->EE->load->view('import_pool', $vars, TRUE);
	}
	
	public function import_start_action()
	{
		$id 	  = $this->EE->input->get_post('id');
		
		$start_data = $this->EE->data_import_model->start_import($id);
		$start_data->importer_last_ran = date('Y-m-d h:i A', $start_data->importer_last_ran);
		
		return $this->json($start_data);		
	}
	
	public function import_item_action()
	{
		$item = $this->EE->data_import_model->get_item($this->EE->input->get_post('schema_id'));
		
		$settings = $this->EE->data_import_model->get_setting($this->EE->input->get_post('schema_id'));
		
		$data = (array) json_decode($item->data);
		
		foreach(explode('|', $item->categories) as $category)
		{
			$data['category'][] = $category;
		}	
		
		$data['entry_date'] = $this->EE->localize->now;
		
		$markers = array();
		
		//var_dump($this->EE->google_maps->geocode('50 Washington Ave Richmond CA 948013995'));exit();
		
		foreach($this->EE->google_maps->geocode($item->geocode) as $response)
		{
			if($response->status == 'OK')
			{					
				foreach($response->results as $result)
				{
					$markers[] = (object) $result;	
				}
			}	
		}
		
		$data['field_id_'.$settings->gmap_field] = $this->EE->google_maps->build_response(array('markers' => $markers));;
		$data['field_ft_'.$settings->gmap_field] = 'none';
		
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_entries');
		$this->EE->api->instantiate('channel_fields');
		
		$this->EE->session->userdata['group_id'] = 1;
		
		$this->EE->api_channel_fields->setup_entry_settings($settings->channel, $data);
		$this->EE->api_channel_entries->submit_new_entry($settings->channel, $data);
		
		if(count($this->EE->api_channel_entries->errors) == 0)
		{
			$this->EE->data_import_model->delete_pool($item->id);
			
			$return = $this->EE->data_import_model->import_success($this->EE->input->get_post('schema_id'));
		}
		else
		{
			$return = $this->EE->data_import_model->import_failed($this->EE->input->get_post('schema_id'));
		}
		
		$return = (array) $return;
		$return['geocode'] = $item->geocode;
		
		return $this->json((object) $return);
	}
	
	public function import_data_action()
	{		
		$entries    = $this->EE->data_import->load_file($_FILES["file"]['tmp_name']);
		$fields     = $this->EE->channel_data->utility->reindex($this->EE->channel_data->get_fields()->result(), 'field_name');
		$settings   = (array) $this->EE->data_import_model->get_setting($this->EE->input->post('id'));
		$categories = array();
		
		foreach($this->EE->channel_data->get_categories()->result() as $category)
		{
			$categories[$category->{$settings['category_column_type']}] = $category;
		}
		
		$data = array();
		
		foreach($entries as $entry)
		{
			$geocode = FALSE;
			
			if(isset($settings['geocode_fields']))
			{
				foreach($settings['geocode_fields'] as $field)
				{
					$geocode .= isset($entry[$field->field_name]) ? $entry[$field->field_name] . ' ' : NULL;
				}
			}
			
			if(!isset($data[$entry[$settings['group_by']]]))
			{
				$title = $settings['title'];
				
				$entry_data = array(
					'status' 	=> $settings['status'],
					'author_id' => $settings['author_id']
				);
				
				foreach($settings['channel_fields'] as $channel_field)
				{
					if(!empty($channel_field->field_name) && !empty($channel_field->column_name))
					{
						$title = str_replace(LD.$field->field_name.RD, $entry[$channel_field->column_name], $title);
						
						$field = $fields[$channel_field->field_name];
						$entry_data['field_id_'.$field->field_id] = $entry[$channel_field->column_name];
						$entry_data['field_ft_'.$field->field_id] = $field->field_fmt;
					}
				}
				
				$entry_data['title'] = $title;
				
				$data[$entry[$settings['group_by']]] = array(
					'schema_id'  => $this->EE->input->post('id'),
					'status'     => 'pending',
					'gmt_date'   => $this->EE->localize->now,
					'geocode'    => trim($geocode),
					'data'       => json_encode($entry_data),
					'categories' => isset($categories[$entry[$settings['category_column']]]) ? $categories[$entry[$settings['category_column']]]->cat_id : NULL
				);	
			}
			else
			{
				if(isset($categories[$entry[$settings['category_column']]]))
				{
					$data[$entry[$settings['group_by']]]['categories'] .= '|'.$categories[$entry[$settings['category_column']]]->cat_id;
				}
			}
		}
		
		if(count($data) > 0)
		{
			$this->EE->db->insert_batch('gmap_import_pool', $data);	
		}
		
		$this->EE->data_import_model->reset_stat_count($this->EE->input->post('id'));
		$this->EE->functions->redirect($this->EE->input->post('return'));
	}
	
	public function settings()
	{	
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Schemas' => $this->cp_url('schemas'), 
		));
		
		$fields = array(
			'settings' => array(
				'title' => 'Import Settings',
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
						'description' => 'This is the channel where your data will be imported.',
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
						'description' => 'This is the author id that will be assigned to the imported entries.',
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
					'status' => array(
						'label'       => 'Status',
						'description' => 'This is the status that is assigned to each entry that is imported.',
						'type'        => 'input'
					),
					'title' => array(
						'label'       => 'Title',
						'description' => 'This is the title assigned to each entry. It can be a combination of channel fields, or a static string.',
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
		
		
		$id   = $this->EE->input->get('id');
		
		if($id)
		{
			$data = (array) $this->EE->data_import_model->get_setting($id);
		}
		else
		{
			$data = array();
		}
		
		$this->EE->interface_builder->data = $data;
		$this->EE->interface_builder->add_fieldsets($fields);

		$vars = array(
			'return'   => $this->cp_url('schemas'),
			'header'   => $this->EE->input->get('id') ? 'Edit' : 'New',
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_csv_save_settings_action'),
			'interface_builder' => $this->EE->theme_loader->theme_url().'third_party/gmap/javascript/InterfaceBuilder.js',
			'settings' => $this->EE->interface_builder->fieldsets(),
			'schema_id' => $this->EE->input->get('id') ? $this->EE->input->get('id') : NULL
		);
		
		return $this->EE->load->view('csv_import', $vars, TRUE);
	}
	
	public function import_csv_save_settings_action()
	{		
		$return = $this->EE->input->post('return');
		
		unset($_POST['return']);
		
		$this->EE->data_import_model->save_settings($this->EE->input->post('schema_id'), json_encode($_POST));
				
		$this->EE->functions->redirect($return);
	}
	
	private function update()
	{
		$update = new Gmap_upd();
		$update->update();
	}
	
	private function cp_url($method = 'index', $useAmp = FALSE)
	{
		$amp  = !$useAmp ? AMP : '&';

		$file = substr(BASE, 0, strpos(BASE, '?'));
		$file = str_replace($file, '', $_SERVER['PHP_SELF']) . BASE;

		$url  = $file .$amp. '&C=addons_modules' .$amp . 'M=show_module_cp' . $amp . 'module=gmap' . $amp . 'method=' . $method;

		return str_replace(AMP, $amp, $url);
	}
	
	private function json($data)
	{
		header('Content-type: application/json');
		
		echo json_encode($data);
		exit();
	}
}
// END CLASS

/* End of file mcp.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/mcp.gmap.php */