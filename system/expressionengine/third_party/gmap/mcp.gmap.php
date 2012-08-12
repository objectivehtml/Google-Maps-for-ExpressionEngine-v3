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
		
		$this->EE->load->library('theme_loader', array(
			'module_name'	=> 'gmap'
		));
		
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
			'status_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'change_statuses'),
			'clear_pool_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'clear_pool'),
			'settings' => $this->EE->data_import_model->get_settings(),
			'stats'    => $this->EE->data_import_model->get_stats(),
			'import_url' => $this->cp_url('import_pool'),
			'return'   => $this->cp_url('index', TRUE)
		);
		
		return $this->EE->load->view('csv_index', $vars, TRUE);
	}
	
	public function clear_pool()
	{
		if(isset($this->EE->session->userdata['member_id']))
		{
			$this->EE->load->model('data_import_model');
			$this->EE->data_import_model->clear_pool();
		}
		
		$this->EE->functions->redirect($this->EE->input->post('return'));
	}
	
	public function change_statuses()
	{
		if(isset($this->EE->session->userdata['member_id']))
		{
			$schema = $this->EE->data_import_model->get_setting($this->EE->input->get_post('schema_id'));
			
			$this->EE->db->where('channel_id', $schema->channel);
			$this->EE->db->update('channel_titles', array(
				'status' => $this->EE->input->post('status', TRUE)
			));
		}
		
		$this->EE->functions->redirect($this->EE->input->post('return'));
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
			
		$this->EE->theme_loader->javascript('https://maps.google.com/maps/api/js?sensor=true');
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js');
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.5/jquery-ui.min.js');
		$this->EE->theme_loader->javascript('json2');
		
		
		$vars = array(
			'id' => $id,
			'total_items' => $this->EE->data_import_model->get_pools($id, 'pending')->num_rows(),
			'stats' => $this->EE->data_import_model->get_stats($id),
			'import_start_url' => $this->cp_url('import_start_action', TRUE),
			'import_item_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_item_action'),
			'import_check_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_check_existing_action'),
		);
		
		/*
		foreach($vars['items'] as $index => $item)
		{
			$item->data = json_decode($item->data);	
		}*/
		
		return $this->EE->load->view('import_pool', $vars, TRUE);
	}
	
	public function import_check_existing_action()
	{
		$item = $this->EE->data_import_model->get_item($this->EE->input->get_post('schema_id'));
				
		$settings = $this->EE->data_import_model->get_setting($this->EE->input->get_post('schema_id'));
		
		$valid_address = FALSE;
		$saved_address = FALSE;
		
		if(isset($settings->duplicate_fields) && !empty($settings->duplicate_fields))
		{
			$where = array();
			
			$csv_data = json_decode($item->entry);
			
			foreach($settings->duplicate_fields as $duplicate_field)
			{
				$where[$duplicate_field->field_name] = $csv_data->{$duplicate_field->column_name};
			}
			
			$existing_entry = $this->EE->channel_data->get_channel_entries($settings->channel, array(
				'where' => $where
			));
			
			if($existing_entry->num_rows() > 0)
			{
				$new_entry = FALSE;
				$existing_entry = $existing_entry->row_array();
				
				$entry_id = $existing_entry['entry_id'];
				
				if(isset($settings->geocode_fields))
				{
					$saved_address = NULL;
					
					foreach($settings->geocode_fields as $field)
					{
						$saved_address .= isset($existing_entry[$field->field_name]) ? $existing_entry[$field->field_name] . ' ' : NULL;
						
					}	
					
					$saved_address = trim($saved_address);
					
					if($saved_address == $item->geocode)
					{
						$valid_address = TRUE;
					}
				}
			}
		}
		
		$response = array(
			'valid_address' => $valid_address,
			'saved_address' => $saved_address,
			'item' => $item
		);
		
		$this->json($response);
	}
	
	public function import_csv_ft_action()
	{
		$entries    = $this->EE->data_import->load_file($_FILES["file"]['tmp_name']);
		
		$response = array(
			'columns' => array_keys($entries[0]),
			'rows'    => $entries
		);
		
		$this->json($response);
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
		$new_entry     = TRUE;
		$geocode_error = FALSE;
		$valid_address = FALSE;
		
		$item = $this->EE->data_import_model->get_item($this->EE->input->get_post('schema_id'));
				
		$settings = $this->EE->data_import_model->get_setting($this->EE->input->get_post('schema_id'));
			
		$data = (array) json_decode($item->data);
		
		foreach(explode('|', $item->categories) as $category)
		{
			$data['category'][] = $category;
		}	
		
		if(preg_match("/^\d*$/", $data['title']) && !empty($settings->title_prefix))
		{
			$data['title'] = $settings->title_prefix . $data['title'];
		}
		
		$data['entry_date'] = $this->EE->localize->now;
		
		/*
		if(isset($settings->duplicate_fields) && !empty($settings->duplicate_fields))
		{
			$where = array();
			
			$csv_data = json_decode($item->entry);
			
			foreach($settings->duplicate_fields as $duplicate_field)
			{
				$where[$duplicate_field->field_name] = $csv_data->{$duplicate_field->column_name};
			}
			
			$existing_entry = $this->EE->channel_data->get_channel_entries($settings->channel, array(
				'where' => $where
			));
			
			if($existing_entry->num_rows() > 0)
			{
				$new_entry = FALSE;
				$existing_entry = $existing_entry->row_array();
				
				$entry_id = $existing_entry['entry_id'];
				
				if(isset($settings->geocode_fields))
				{
					$saved_address = NULL;
					
					foreach($settings->geocode_fields as $field)
					{
						$saved_address .= isset($existing_entry[$field->field_name]) ? $existing_entry[$field->field_name] . ' ' : NULL;
						
					}	
					
					$saved_address = trim($saved_address);
					
					if($saved_address == $item->geocode)
					{
						$valid_address = TRUE;
					}
				}
			}
		}
		
		if(!$valid_address)
		{
			foreach($this->EE->google_maps->geocode($item->geocode) as $response)
			{
				if($response->status == 'OK')
				{					
					foreach($response->results as $result)
					{
						$markers[] = (object) $result;	
					}
				}
				else
				{
					$geocode_error = $response->status;
				}	
			}
			
			$map_data = $this->EE->google_maps->build_response(array('markers' => $markers));
		}
		else
		{
			$map_data = $existing_entry[$item->map_field_name];			
		}
		
		*/
		
		$valid_address = $this->EE->input->get_post('valid_address') == 'true' ? TRUE : FALSE;
		
		if(!$valid_address)
		{
			/*
			foreach($this->EE->google_maps->geocode($item->geocode) as $response)
			{
				if($response->status == 'OK')
				{					
					foreach($response->results as $result)
					{
						$markers[] = (object) $result;	
					}
				}
				else
				{
					$geocode_error = $response->status;
				}	
			}*/
			
			$markers  = json_decode($this->EE->input->post('markers'));
			
			$map_data = $this->EE->google_maps->build_response(array('markers' => $markers));
		}
		else
		{
			$map_data = $existing_entry[$item->map_field_name];			
		}
		
		$data['field_id_'.$settings->gmap_field] = $map_data;
		$data['field_ft_'.$settings->gmap_field] = 'none';
		
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_entries');
		$this->EE->api->instantiate('channel_fields');
		
		$this->EE->session->userdata['group_id'] = 1;
		
		if(!$geocode_error)
		{
			$this->EE->api_channel_fields->setup_entry_settings($settings->channel, $data);
			if($new_entry)
			{
				$this->EE->api_channel_entries->submit_new_entry($settings->channel, $data);
			}
			else
			{
				$this->EE->channel_data->utility->update_entry($settings->channel, $existing_entry['entry_id'], $data);
				
			}
		}
		
		if(!$geocode_error && count($this->EE->api_channel_entries->errors) == 0)
		{
			$this->EE->data_import_model->delete_pool($item->id);
			
			$return = $this->EE->data_import_model->import_success($this->EE->input->get_post('schema_id'));
		}
		else
		{
			$this->EE->api_channel_entries->errors = array_merge($this->EE->api_channel_entries->errors, array($geocode_error));
			
			$return = $this->EE->data_import_model->import_failed($this->EE->input->get_post('schema_id'));
		}
		
		$return            = (array) $return;
		$return['errors']  = count($this->EE->api_channel_entries->errors) ? $this->EE->api_channel_entries->errors : FALSE;
		$return['geocode'] = $item->geocode;
				
		return $this->json((object) $return);
	}
	
	public function import_data_action()
	{		
		$entries    = $this->EE->data_import->load_file($_FILES["file"]['tmp_name']);
		
		$channel_fields = $this->EE->channel_data->get_fields()->result();
		
		$group_field  = FALSE;
		$entry_id     = FALSE;
		$fields       = $this->EE->channel_data->utility->reindex($channel_fields, 'field_name');
		$fields_by_id = $this->EE->channel_data->utility->reindex($channel_fields, 'field_id');
		$settings     = (array) $this->EE->data_import_model->get_setting($this->EE->input->post('id'));
		$categories   = array();
		
		foreach($this->EE->channel_data->get_categories()->result() as $category)
		{
			$categories[$category->{$settings['category_column_type']}] = $category;
		}
		
		$data = array();
		
		foreach($entries as $entry)
		{
			$geocode       = FALSE;
			
			if(isset($settings['geocode_fields']))
			{
				foreach($settings['geocode_fields'] as $field)
				{
					$geocode .= isset($entry[$field->column_name]) ? $entry[$field->column_name] . ' ' : NULL;
				}
			}
			
			if(isset($settings['create_category']) && (bool) $settings['create_category'])
			{
				if(!isset($categories[$entry[$settings['category_column']]]))
				{
					$cat_data = array();
				
					if($settings['category_column_type'] == 'cat_name')
					{
						$this->EE->load->helper('url');
						
						$category_name = $entry[$settings['category_column']];
					
						$cat_data['site_id']       = $this->EE->config->item('site_id');
						$cat_data['cat_name']      = $category_name;
						$cat_data['cat_url_title'] = strtolower(url_title($category_name));
						$cat_data['group_id']      = $settings['category_group_id'];
						
						$existing_categories = $this->EE->db->get_where('categories', $cat_data);
						
						if($existing_categories->num_rows() == 0)
						{						
							$total_categories = $this->EE->db->get_where('categories', array(
								'group_id' => $settings['category_group_id']
							))->num_rows();
							
							$cat_data['cat_order'] = $total_categories + 1;
							$this->EE->db->insert('categories', $cat_data);
							
							$cat_id = $this->EE->db->insert_id();
							
							$categories[$entry[$settings['category_column']]] = $this->EE->channel_data->get_category($cat_id)->row();
						}						
					}
				}
			}
			
			$geocode = trim($geocode);
			
			if(!isset($data[$entry[$settings['group_by']]]))
			{
				$title = $settings['title'];
				
				$entry_data = array(
					'status' 	=> $settings['status'],
					'author_id' => $settings['author_id']
				);
				
				foreach($settings['channel_fields'] as $channel_field)
				{
					if($channel_field->column_name == $settings['group_by'])
					{
						$group_field = $channel_field->field_name;	
					}
					
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
					'schema_id'     => $this->EE->input->post('id'),
					'status'        => 'pending',
					'gmt_date'      => $this->EE->localize->now,
					'group_by'      => !empty($settings['group_by']) ? $entry[$settings['group_by']] : FALSE,
					'group_by_field_name' => $group_field,
					'map_field_name'	=> $fields_by_id[$settings['gmap_field']]->field_name,
					'geocode'       => trim($geocode),
					'data'          => json_encode($entry_data),
					'entry'			=> json_encode($entry),
					'categories'    => isset($categories[$entry[$settings['category_column']]]) ? $categories[$entry[$settings['category_column']]]->cat_id : NULL
				);	
				
				/*
				
				Create categories 
				
				if(isset($settings->create_category) && $settings->create_category == 'true')
			{
				$cat_data = array();
				
				if($settings->category_column_type == 'cat_name')
				{
					$this->EE->load->helper('url');
					
					$cat_data['site_id']       = $this->EE->config->item('site_id');
					$cat_data['cat_name']      = $category;
					$cat_data['cat_url_title'] = strtolower(url_title($category));
					$cat_data['group_id']      = $settings->category_group_id;
					
					$existing_categories = $this->EE->db->get_where('categories', $cat_data);
					
					var_dump($cat_data);exit();
					
					if($existing_categories->num_rows() == 0)
					{						
						$total_categories = $this->EE->db->get_where('categories', array(
							'group_id' => $settings->category_group_id
						))->num_rows();
						
						$cat_data['cat_order'] = $total_categories + 1;
						
						$this->EE->db->insert('categories', $cat_data);
					}
				}
			}
			
			
			*/
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
						'description' => 'This field is used to define the channel fields and column(s) in the .CSV you wish to use for geocoding. The fields are used to prevent existing entries from getting geocoded unnecessarily. The .csv fields are what are actually used to create the address string.',
						'type'	=> 'matrix',
						'settings' => array(
							'columns' => array(
								1 => array(
									'name'  => 'field_name',
									'title' => 'Channel Field Name'
								),
								0 => array(
									'name'  => 'column_name',
									'title' => 'CSV Column Name'
								),
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
					'duplicate_fields' => array(
						'label' => 'Duplicate Data',
						'description' => 'Defines fields and columns used to check for duplicate data. If multiple fields are defined, that must all match an entry to trigger an update vs. creating a new record.',
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
					'title_prefix' => array(
						'label'		  => 'Title Prefix',
						'description' => 'This is the prefix that gets prepended to the title if the title contains only numbers.',		
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
					'create_category' => array(
						'label'       => 'Create Category',
						'description' => 'Creates a category if one doesn\'t exist based on the category column type. Be sure your category is stored as a Category Name for the best results.',
						'type'        => 'select',
						'settings' => array(
							'options' => array(
								'false' => 'No',
								'true'  => 'Yes',
							)
						)
					),
					'category_group_id' => array(
						'label'       => 'Category Group ID',
						'description' => 'If creating a category, you need to define a group_id to which it will be assigned.',
						'type'        => 'input'
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

		$url  = $file .$amp. 'C=addons_modules' .$amp . 'M=show_module_cp' . $amp . 'module=gmap' . $amp . 'method=' . $method;

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