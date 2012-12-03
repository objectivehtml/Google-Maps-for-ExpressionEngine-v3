<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.1.0
 * @build		20121203
 */

class Gmap_mcp {
	
	public $fields;
	public $fields_by_id;
	public $entry;
	public $settings;
	public $categories;
	
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
			'Import Log' => $this->cp_url('import_log'),
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
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		
		return $this->EE->load->view('csv_index', $vars, TRUE);
	}
	
	public function import_log_action()
	{
		$id 	= $this->EE->input->get_post('id');
		$status = $this->EE->input->get_post('status');
		
		$this->EE->data_import_model->clear_item($id, $status);
	}
	
	public function import_log()
	{
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Home' => $this->cp_url(),
			'Manage Schemas' => $this->cp_url('schemas'),
		));
		
		$vars = array(
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_log_action'),
			'base_url' => $this->edit_url(),
			'items' => $this->EE->data_import_model->get_log('open')
		);
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		
		return $this->EE->load->view('import_log', $vars, TRUE);
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
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		
		return $this->EE->load->view('schemas', $vars, TRUE);		
	}
	
	public function import_pool()
	{
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Pool' => $this->cp_url(), 
			'Import Log' => $this->cp_url('import_log'),
		));
		
		$id = $this->EE->input->get('id');
		
		$settings = $this->EE->data_import_model->get_setting($id);
		
		$this->EE->theme_loader->javascript('https://maps.google.com/maps/api/js?sensor=true'.($this->EE->theme_loader->requirejs() ? '&callback=init' : NULL));
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.js');
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.5/jquery-ui.min.js');
		$this->EE->theme_loader->javascript('json2');
		$this->EE->theme_loader->javascript('gmap_import');
		
		$vars = array(
			'id' => $id,
			'total_items' => $this->EE->data_import_model->get_pools($id, 'pending')->num_rows(),
			'stats' => $this->EE->data_import_model->get_stats($id),
			'import_start_url' => $this->cp_url('import_start_action', TRUE),
			'import_item_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_item_action'),
			'import_check_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_check_existing_action'),
		);
		
		$this->EE->cp->add_to_head('
		<script type="text/javascript">
			
			var id         = '.$vars['id'].';
			var totalItems = '.$vars['total_items'].';
			var importItemURL = \''.$vars['import_item_url'].'\';
			var importCheckURL = \''.$vars['import_check_url'].'\';
			var init;
			
		</script>');
		
		$this->EE->theme_loader->output('
				
		init = function() {
			$bar = $(\'.progress-bar\');
			$bar.progressbar({value: 0});
			
			$(\'.start .submit\').click(function() {
				
				var $t = $(this);
				
				if($t.html() == \'Start Import\') {
					stop = false;
					$t.html(\'Stop Import\');
					
					$.get(\''.$vars['import_start_url'].'\',
						{
							id: id
						},
						function(data) {
							
							itemsRemaining = data.items_in_pool;
							
							$(\'.last-ran\').html(data.importer_last_ran);
							$(\'.total-runs\').html(data.importer_total_runs);
							$(\'.progress-bar, .geocoding\').show();
							
							startTimer();
							geocode(lastIndex);
						}
					);
				}
				else {
					clearInterval(timer);
	        
					$t.html(\'Start Import\');
					stop = true;
				}
				
				return false;
				
			});
		}; '. (!$this->EE->theme_loader->requirejs() ? 'init();' : NULL));
		
		/*
		foreach($vars['items'] as $index => $item)
		{
			$item->data = json_decode($item->data);	
		}*/
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		
		return $this->EE->load->view('import_pool', $vars, TRUE);
	}
	
	public function import_check_existing_action()
	{
		$item = $this->EE->data_import_model->get_item($this->EE->input->get_post('schema_id'));
						
		$settings = $this->EE->data_import_model->get_setting($this->EE->input->get_post('schema_id'));
		
		$channel_fields = $this->EE->channel_data->get_fields()->result();
		$fields         = $this->EE->channel_data->utility->reindex($channel_fields, 'field_name');
		
		$valid_address  = FALSE;
		$saved_address  = FALSE;
		$existing_entry = FALSE;
		$errors		    = array();
		$success	    = TRUE;
		
		if(isset($settings->duplicate_fields) && !empty($settings->duplicate_fields))
		{
			$where = array();
			
			$csv_data = json_decode($item->entry);
			
			foreach($settings->duplicate_fields as $duplicate_field)
			{
				if(!isset($fields[$duplicate_field->field_name]))
				{
					$errors[] = '<i>'.$duplicate_field->field_name.'</i> is not a valid channel field name. Check the duplicate fields setting in your schema to make sure it\'s 100% correct.';
				}
				else
				{
					$where[$duplicate_field->field_name] = $csv_data->{$duplicate_field->column_name};
				}
			}
			
			if(count($errors) == 0)
			{
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
							$saved_address .= isset($existing_entry[$field->field_name]) && !empty($existing_entry[$field->field_name]) ? $existing_entry[$field->field_name] . ' ' : NULL;
						}	
						
						$saved_address = trim($saved_address);
						
						if($saved_address == $item->geocode)
						{
							$valid_address = TRUE;
						}
					}
				}
				else
				{
					$existing_entry = FALSE;
				}
			}
			else
			{
				$success = FALSE;
			}
		}
		
		$response = array(
			'valid_address'  => $valid_address,
			'saved_address'  => $saved_address,
			'item'           => $item,
			'existing_entry' => $existing_entry,
			'success'		 => $success,
			'errors'		 => $errors,
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
		$log_item      = array();
		
		$item = $this->EE->data_import_model->get_item($this->EE->input->get_post('schema_id'));
				
		$settings = $this->EE->data_import_model->get_setting($this->EE->input->get_post('schema_id'));
		$settings = (object) $settings;
		
		$data = (array) json_decode($item->data);
		$data['category'] = !empty($item->categories) ? explode('|', $item->categories) : array();
		
		if(preg_match("/^\d*$/", $data['title']) && !empty($settings->title_prefix))
		{
			$data['title'] = $settings->title_prefix . $data['title'];
		}
		
		$data['entry_date'] = $this->EE->localize->now;
		
		if(isset($settings->duplicate_fields) && !empty($settings->duplicate_fields))
		{
			$where = array(
				'channel_data.channel_id' => $settings->channel
			);
			
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
		
		$markers = array();
		
		$valid_address = $this->EE->input->get_post('valid_address') == 'true' ? TRUE : FALSE;
				
		if(!$valid_address)
		{
			$markers  = json_decode($this->EE->input->get_post('markers'));
	
			if(count($markers) == 0)
			{
				$log_item[] = 'The entry has no valid location.';
			}
			
			if(count($markers) > 1)
			{
				$log_item[] = 'The entry has more than 1 valid location.';
			}
					
			$map_data = $this->EE->google_maps->build_response(array('markers' => $markers));
		}
		else
		{
			$existing_entry = json_decode($this->EE->input->get_post('existing_entry'));
			$new_entry = FALSE;
			$map_data = $existing_entry->{$item->map_field_name};
			
			if(empty($map_data))
			{
				$log_item[] = 'The entry does not have a valid location.';
			}
		}
		
		if($this->EE->input->get_post('status') == 'OK')
		{
			$data['status'] = $settings->status;
		}
		
		if($data['status'] != $settings->status)
		{
			$data['status'] = 'closed';
			
			$log_item[] = 'A geocoding error has occurred with this entry.';
		}
		
		$data['field_id_'.$settings->gmap_field] = $map_data;
		$data['field_ft_'.$settings->gmap_field] = 'none';
		
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_entries');
		$this->EE->api->instantiate('channel_fields');
		
		$this->EE->session->userdata['group_id'] = 1;
		
		$entry_id = 0;
				
		$this->EE->api_channel_fields->setup_entry_settings($settings->channel, $data);
		
		if($new_entry)
		{
			$this->EE->api_channel_entries->submit_new_entry($settings->channel, $data);				
			$entry_id = $this->EE->api_channel_entries->entry_id;
		}
		else
		{
			if(isset($existing_entry))
			{
				$existing_entry = (array) $existing_entry;
		
				$this->EE->channel_data->utility->update_entry($settings->channel, $existing_entry['entry_id'], $data);
				$entry_id = $existing_entry['entry_id'];
			}			
		}
		
		$this->EE->data_import_model->log_item($entry_id, $log_item);
		
		if(count($this->EE->api_channel_entries->errors) == 0)
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
		$settings     = (array) $this->EE->data_import_model->get_setting($this->EE->input->post('id'));
		
		if(isset($settings['eol']) && !empty($settings['eol']))
		{
			$this->EE->data_import->eol       = $settings['eol'];
		}
		
		if(isset($settings['delimeter']) && !empty($settings['delimeter']))
		{
			$this->EE->data_import->delimeter = $settings['delimeter'];
		}
		
		$entries    = $this->EE->data_import->load_file($_FILES["file"]['tmp_name']);
		
		$channel_fields = $this->EE->channel_data->get_fields()->result();
		
		$group_field  = FALSE;
		$entry_id     = FALSE;
		$fields       = $this->EE->channel_data->utility->reindex($channel_fields, 'field_name');
		
		$this->fields = $fields;
		
		$fields_by_id = $this->EE->channel_data->utility->reindex($channel_fields, 'field_id');
		
		$this->fields_by_id = $fields_by_id;
		
		$this->settings = $settings;
		
		$categories   = array();
		
		foreach($this->EE->channel_data->get_categories()->result() as $category)
		{
			$categories[$category->{$settings['category_column_type']}] = $category;
		}
		
		$data = array();
		
		foreach($entries as $entry)
		{
			$this->entry   = $entry;
			$geocode       = FALSE;
			
			if(isset($settings['geocode_fields']))
			{
				foreach($settings['geocode_fields'] as $field)
				{
					if(!empty($entry[$field->column_name]))
					{
						$geocode .= isset($entry[$field->column_name]) ? $entry[$field->column_name] . ' ' : NULL;
					}
				}
			}
			
			$this->geocode = $geocode;
				
			if(isset($settings['create_category']) && $settings['create_category'] == 'true')
			{
				if(!is_array($settings['category_column']))
				{
					$settings['category_column'] = array($settings['category_column']);	
				}
				
				foreach($settings['category_column'] as $category_column)
				{					
					$category_column = trim($category_column->column_name);
					
					if(!isset($categories[$category_column]))
					{
						$cat_data = array();
					
						if($settings['category_column_type'] == 'cat_name')
						{
							$this->EE->load->helper('url');
							
							$category_name = trim($category_column);
						
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
								
								$categories[$category_name] = $this->EE->channel_data->get_category($cat_id)->row();
							}						
						}
					}
				}
			}
			
			$this->categories = $categories;
			
			$geocode = trim($geocode);
			
			if(!empty($settings['group_by']))
			{				
				if(!isset($data[$entry[$settings['group_by']]]))
				{
					$data[$entry[$settings['group_by']]] = $this->build_entry_data();	
					
				}
				else
				{
					if(!is_array($settings['category_column']))
					{
						$settings['category_column'] = array($settings['category_column']);	
					}
					
					foreach($settings['category_column'] as $category_column)
					{
						$category_column = $category_column->column_name;
						
						if(isset($categories[$entry[$category_column]]))
						{
							$data[$entry[$settings['group_by']]]['categories'] .= '|'.$categories[$entry[$category_column]]->cat_id;
						}
					}
				}
			}
			else
			{	
				$data[] = $this->build_entry_data();		
			}
			
		}
		
		if(count($data) > 0)
		{
			$this->EE->db->insert_batch('gmap_import_pool', $data);	
		}
		
		$this->EE->data_import_model->reset_stat_count($this->EE->input->post('id'));
		$this->EE->functions->redirect($this->EE->input->post('return'));
	}
	
	private function build_entry_data()
	{
		$entry        = $this->entry;
		$fields       = $this->fields;
		$settings     = $this->settings; 
		$fields_by_id = $this->fields_by_id;
		$geocode      = $this->geocode;
		$categories   = $this->categories;
		
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
				if(!isset($entry[$channel_field->column_name]))
				{
					show_error('<i>'.$channel_field->column_name.'</i> is does not exist within your data schema. Ensure that the column names match 100%, they are case-sensitive.');
				}
				
				if(!isset($fields[$channel_field->field_name]))
				{
					show_error('<i>'.$channel_field->field_name.'</i> is not a valid field name. Ensure that the field names match 100%, they are case-sensitive.');
				}
				
				$field = $fields[$channel_field->field_name];
				
				$title = str_replace(LD.$field->field_name.RD, $entry[$channel_field->column_name], $title);
				
				$entry_data['field_id_'.$field->field_id] = $entry[$channel_field->column_name];
				$entry_data['field_ft_'.$field->field_id] = $field->field_fmt;
			}
		}
		
		$entry_data['title'] = $title;
		
		$entry_categories = array();
		
		if(isset($settings['category_column']))
		{	
			/* Maintain backwards compatibility with previous settings */
			
			if(is_string($settings['category_column']))
			{
				$settings['category_column'] = array($settings['category_column']);	
			}
			
			if(isset($settings['category_column']) && count($settings['category_column']) > 0)
			{
				foreach($settings['category_column'] as $cat_index => $cat)
				{
					if(empty($settings['category_boolean_value']))
					{
						$save_value = !empty($settings['category_column']) ? (isset($categories[$entry[$cat->column_name]]) ? $categories[$entry[$cat->column_name]]->cat_id : NULL) : NULL;
						
						if(!is_null($save_value))
						{
							$entry_categories[] = $save_value;
						}
					}
					else
					{
						foreach($cat as $cat_index)
						{
							$cat_index = trim($cat_index);
							
							if(!isset($entry[$cat_index]) || empty($categories[$cat_index]))
							{
								show_error('<i>'.$cat_index.'</i> is not a valid category name. Be sure the names of the categories match exactly, they are case-sensitive. The categories with ExpressionEngine, the schema, <i>AND</i> the . must match.');
							}
							else
							{
								$save_value = $entry[$cat_index] == $settings['category_boolean_value'] ? (isset($categories[$cat_index]->cat_id) ? $categories[$cat_index]->cat_id : NULL) : NULL;
								
								if(!is_null($save_value))
								{
									$entry_categories[] = $save_value;
								}
							}
						}
					}
				}
			}
		}
		
		return array(
			'schema_id'           => $this->EE->input->post('id'),
			'status'              => 'pending',
			'gmt_date'            => $this->EE->localize->now,
			'group_by'            => !empty($settings['group_by']) ? $entry[$settings['group_by']] : FALSE,
			'group_by_field_name' => isset($group_field) ? $group_field : NULL,
			'map_field_name'      => $fields_by_id[$settings['gmap_field']]->field_name,
			'geocode'             => trim($geocode),
			'data'                => json_encode($entry_data),
			'entry'               => json_encode($entry),
			'categories'          => implode('|', $entry_categories)
		);	

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
					'delimeter' => array(
						'label'       => 'Delimeter',
						'description' => 'The delimeting character used in the .csv you will be uploading. If left blank, the default value will be used (,).',
						'type'        => 'input'
					),
					'eol' => array(
						'label'       => 'EOL',
						'description' => 'The EOL character used in the .csv you will be uploading. If left blank, the default value will be used (\n\r).',
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
						'description' => 'Defines fields and columns used to check for duplicate data. If multiple fields are defined, they must all match an entry to trigger an update vs. creating a new record.',
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
						'label'       => 'Category Column(s)',
						'description' => 'This is the name of the column(s) that stores the category. You can have a data file with categories stored in multiple columns, or just one.',
						'type'        => 'matrix',
						'settings' => array(
							'columns' => array(
								0  => array(
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
					'category_boolean_value' => array(
						'label'       => 'Category Boolean Value for <i>true</i>',
						'description' => 'If your catagories are stored in multiple columns, with the columns header being a category name, chances are the values are stored as boolean. So if "X" represents <i>true</i> what is the value of "X"? Leave the field blank if it does not apply.',
						'type'        => 'input'
					),
					'category_column_type' => array(
						'label'       => 'Category Column Type',
						'description' => 'This is the type of data stored in the category column(s).',
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
			'interface_builder' => $this->EE->theme_loader->theme_url().'/gmap/javascript/InterfaceBuilder.js',
			'settings' => $this->EE->interface_builder->fieldsets(),
			'schema_id' => $this->EE->input->get('id') ? $this->EE->input->get('id') : NULL
		);
		
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		
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
	
	private function edit_url($method = 'index', $useAmp = FALSE)
	{
		$amp  = !$useAmp ? AMP : '&';

		$file = substr(BASE, 0, strpos(BASE, '?'));
		$file = str_replace($file, '', $_SERVER['PHP_SELF']) . BASE;

		$url  = $file .$amp. 'C=content_publish&M=entry_form';

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