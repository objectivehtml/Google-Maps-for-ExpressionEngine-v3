<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('BaseClass.php');
require_once('DataSource.php');		 
			 
class Gmap_import extends BaseClass {
		
	public $fields;
	public $fields_by_id;
	public $entry;
	public $categories;
	
	public $latitude  = FALSE;
	public $longitude = FALSE;
	public $response  = FALSE;
	public $threshold = 100;
	public $settings  = FALSE;
	public $item      = FALSE;
	
	public $delimeter = ',';
	public $eol       = '\n\r';
	
	public function __construct()
	{
		parent::__construct();
		
		$this->EE =& get_instance();
		
		$this->EE->load->library('google_maps');
		$this->EE->load->model('data_import_model');
		$this->EE->load->driver('Channel_data');
			
		if($memory = config_item('gmap_import_memory'))
		{
			ini_set('memory_limit', $memory);
		}
			
		if($upload_max_filesize = config_item('gmap_import_max_file_size'))
		{
			ini_set('upload_max_filesize', $upload_max_filesize);
			ini_set('post_max_size', $upload_max_filesize);
		}
	}
	
	private function build_entry_data($force_geocoder = FALSE)
	{
		$entry        = $this->entry;
		$fields       = $this->fields;
		$settings     = $this->settings; 
		$fields_by_id = $this->fields_by_id;
		$geocode      = $this->geocode;
		$categories   = $this->categories;
		
		$title 		= $settings['title'];
					
		$entry_data = array(
			'status'         => $settings['status'],
			'author_id'      => $settings['author_id'],
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
					show_error('<i>'.$channel_field->column_name.'</i> does not exist within your data schema. Ensure that the column names match 100%, they are case-sensitive.');
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

		if(isset($settings['title_column']) && !empty($settings['title_column']) && isset($entry[$settings['title_column']]))
		{
			$title = $entry[$settings['title_column']];
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
			'schema_id'           => $this->settings['schema_id'],
			'status'              => 'pending',
			'gmt_date'            => $this->EE->localize->now,
			'username'			  => isset($settings['username_column']) && !empty($settings['username_column']) ? $entry[$settings['username_column']] : '',
			'group_by'            => !empty($settings['group_by']) ? $entry[$settings['group_by']] : FALSE,
			'group_by_field_name' => isset($group_field) ? $group_field : NULL,
			'map_field_name'      => isset($fields_by_id[$settings['gmap_field']]) ? $fields_by_id[$settings['gmap_field']]->field_name : FALSE,
			'lat_field_name'      => isset($fields_by_id[$settings['lat_field']]) ? $fields_by_id[$settings['lat_field']]->field_name : FALSE,
			'lng_field_name'      => isset($fields_by_id[$settings['lng_field']]) ? $fields_by_id[$settings['lng_field']]->field_name : FALSE,
			'geocode'             => $this->trim($geocode),
			'data'                => json_encode($entry_data),
			'entry'               => json_encode($entry),
			'categories'          => implode('|', $entry_categories),
			'force_geocoder' 	  => $force_geocoder
		);
	}
	
	public function create_category()
	{
		
	}
	
	public function does_category_exist($str, $type = 'cat_name')
	{
		
	}
	
	public function check_existing_entries($schema_id, $item = FALSE, $fields = FALSE)
	{
		if(!$item)
		{
			$item = $this->EE->data_import_model->get_item($schema_id);						
		}
		
		$settings = $this->EE->data_import_model->get_setting($schema_id);
		
		if(!$fields)
		{
			$channel_fields = $this->EE->channel_data->get_fields()->result();
			$fields         = $this->EE->channel_data->utility->reindex($channel_fields, 'field_name');
		}
		
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
						
						$saved_address = $this->trim($saved_address);
						
						if($saved_address == $this->trim($item->geocode))
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
		
		if(defined('AJAX_REQUEST') && AJAX_REQUEST)
		{
			return $this->json($response);
		}
		
		return $response;
	}
	
	public function geocode($location, $use_yahoo = FALSE)
	{
		if($use_yahoo)
		{	
			if(!class_exists('YahooBossGeocoder'))
			{
				require_once('YahooBossGeocoder.php');	
			}
			
			$boss = new YahooBossGeocoder(array(
				'consumer_key'    => config_item('gmap_import_client_key'),
				'consumer_secret' => config_item('gmap_import_client_secret'),
				'appid'		      => config_item('gmap_import_appid')
			));
			
			$boss->setLocation($location);
			
			$data = $boss->json();
			
			if(isset($data->bossresponse->placefinder->results[0]))
			{
				$response = $data->bossresponse->placefinder->results[0];
						
				$this->latitude  = $response->latitude;
				$this->longitude = $response->longitude;
			}
			
			return $data;
		}
		else
		{
			$data = $this->EE->google_maps->geocode($location);
			
			if(isset($data[0]->status) && $data[0]->status == 'OK')
			{
				$this->latitude  = $data[0]->results[0]->geometry->location->lat;
				$this->longitude = $data[0]->results[0]->geometry->location->lng;
				$this->response  = $data;
			}
			
			return $data;
		}
	}
	
	public function trim($str)
	{
		return trim(preg_replace("/\\W+/u", " ", $str));
	}
	
	public function import_pool($schema_id, $status = 'pending', $limit = FALSE, $offset = 0)
	{
		if($limit === FALSE)
		{
			$limit = $this->threshold;	
		}
		
		$id    = $schema_id;
		$items = $this->EE->data_import_model->get_pool($id, $status, $limit, $offset);
		
		if($items->num_rows() == 0)
		{
			return;
		}
		
		$this->settings = json_decode($this->EE->data_import_model->get_settings($id)->row('settings'));
		
		$channel_fields = $this->EE->channel_data->get_fields()->result();
		$fields         = $this->EE->channel_data->utility->reindex($channel_fields, 'field_name');
		
		foreach($items->result() as $index => $item)
		{
			$entries = $this->check_existing_entries($item->schema_id, $item, $fields);
			
			$this->item = $entries['item'];

			if($entries['valid_address'] && (int) $item->force_geocoder != 1)
			{	
				$this->import_item($item->schema_id, $entries['valid_address'], array(), (object) $entries['existing_entry'], FALSE);
			}
			else
			{
				$data = $this->geocode($item->geocode, config_item('gmap_import_use_yahoo'));
				
				if($entries['existing_entry'])
				{
					 $entries['existing_entry'] = (object)  $entries['existing_entry'];
				}

				$this->import_item($item->schema_id, $entries['valid_address'], FALSE, $entries['existing_entry'], FALSE);
			}
		}
	}
	
	public function import_item($schema_id, $valid_address = FALSE, $markers = array(), $existing_entry = FALSE, $status = 'OK')
	{
		$new_entry          = TRUE;
		$geocode_error      = FALSE;
		$log_item           = array();
		$has_existing_entry = FALSE;

		if(!$this->item)
		{
			$item = $this->EE->data_import_model->get_item($schema_id);
		}
		else
		{
			$item = $this->item;
		}
		
		if(!$this->settings)
		{
			$settings = $this->EE->data_import_model->get_setting($schema_id);
			$settings = (object) $settings;
		}
		else
		{
			$settings = $this->settings;
		}
		
		$data = (array) json_decode($item->data);
		$data['category'] = !empty($item->categories) ? explode('|', $item->categories) : array();

		if(preg_match("/^\d*$/", $data['title']) && !empty($settings->title_prefix))
		{
			$data['title'] = $settings->title_prefix . $data['title'];
		}
		
		if(isset($item->username) && !empty($item->username))
		{
			$member = $this->EE->channel_data->get_members(array(
				'where' => array(
					'username' => $item->username			
				)
			));
			
			if($member->num_rows() > 0)
			{
				$data['author_id'] = $member->row('member_id');
			}
		}
			
			
		$this->EE->session->userdata['member_id'] = $data['author_id'];
		
		$data['entry_date'] = $this->EE->localize->now;
		
		if(is_string($existing_entry))
		{
			$existing_entry = json_encode($existing_entry);
		}
		
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
	
			$has_existing_entry = !$existing_entry ? FALSE : TRUE;
						
			if(!$existing_entry)
			{
				$existing_entry = $this->EE->channel_data->get_channel_entries($settings->channel, array(
					'where' => $where
				));
				
				if($existing_entry->num_rows() > 0)
				{
					$has_existing_entry = TRUE;
					$existing_entry = $existing_entry->row();
				}
			}
			
			if($has_existing_entry)
			{
				$new_entry = FALSE;
				
				$entry_id = $existing_entry->entry_id;
				
				if(isset($settings->geocode_fields))
				{
					$saved_address = NULL;
					
					foreach($settings->geocode_fields as $field)
					{
						$saved_address .= isset($existing_entry->{$field->field_name}) ? $existing_entry->{$field->field_name} . ' ' : NULL;
						
					}	
					
					$saved_address = $this->trim($saved_address);
					
					if($saved_address == $this->trim($item->geocode))
					{
						$valid_address = TRUE;
					}
				}
			}
		}

		if(!$valid_address || (int) $this->item->force_geocoder == 1)
		{
			if(isset( $this->response[0]->results) && !$markers)
			{
				$markers = $this->response[0]->results;
			}
			else
			{
				$markers = json_decode($markers);
			}

			if(count($markers) == 0)
			{
				$log_item[] = 'The entry has no valid location.';
			}
			
			if(count($markers) > 1)
			{
				$log_item[] = 'The entry has more than 1 valid location.';
			}
			
			if(!empty($settings->gmap_field))
			{	
				$map_data = $this->EE->google_maps->build_response(array('markers' => $markers));
				
				$data['field_id_'.$settings->gmap_field] = $map_data;
				$data['field_ft_'.$settings->gmap_field] = 'none';
			}
			
			if(!empty($settings->lat_field) && !empty($settings->lng_field))
			{
				if($this->latitude && $this->longitude)
				{
					$data['field_id_'.$settings->lat_field] = $this->latitude;
					$data['field_ft_'.$settings->lat_field] = 'none';
					$data['field_id_'.$settings->lng_field] = $this->longitude;
					$data['field_ft_'.$settings->lng_field] = 'none';
				}
				//else
				//{
				//	$log_item[] = 'The entry has no valid location.';
				//}
			}
		}
		else
		{
			$new_entry = FALSE;

			if(!empty($settings->gmap_field))
			{
				$map_data = isset($existing_entry->{$item->map_field_name}) ? $existing_entry->{$item->map_field_name} : NULL;
				
				if(empty($map_data))
				{
					$log_item[] = 'The entry does not have a valid location.';
				}
				
				$data['field_id_'.$settings->gmap_field] = $map_data;
				$data['field_ft_'.$settings->gmap_field] = 'none';
			}
			else
			{
				if(!empty($settings->lat_field) && !empty($settings->lng_field))
				{
					$data['field_id_'.$settings->lat_field] = $existing_entry->{$item->lat_field_name};
					$data['field_ft_'.$settings->lat_field] = 'none';
					$data['field_id_'.$settings->lng_field] = $existing_entry->{$item->lng_field_name};
					$data['field_ft_'.$settings->lng_field] = 'none';
				}
				else
				{
					$log_item[] = 'The entry does not have a valid latitude and longitude.';
				}
			}			
		}
		
		if($has_existing_entry)
		{
			if(isset($settings->force_status_update) && $settings->force_status_update == 'true')
			{
				$data['status'] = $settings->status;
			}
			else
			{
				$data['status'] = $existing_entry->status;
			}

			$data['author_id']       = $existing_entry->author_id;
			$data['entry_date']      = $existing_entry->entry_date;
			$data['expiration_date'] = $existing_entry->expiration_date;
		}
		else
		{
			if($status == 'OK')
			{
				$data['status'] = $settings->status;
			}
			
			if($data['status'] != $settings->status)
			{
				$data['status'] = 'closed';
				
				$log_item[] = 'A geocoding error has occurred with this entry.';
			}
		}
		
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
				$this->EE->channel_data->utility->update_entry($settings->channel, $existing_entry->entry_id, $data);
				$entry_id = $existing_entry->entry_id;
			}			
		}
		
		$this->EE->data_import_model->log_item($entry_id, $log_item);
		
		if(count($this->EE->api_channel_entries->errors) == 0)
		{
			$this->EE->data_import_model->delete_pool($item->id);
			
			$return = $this->EE->data_import_model->import_success($schema_id);
		}
		else
		{
			$this->EE->api_channel_entries->errors = array_merge($this->EE->api_channel_entries->errors, array($geocode_error));
			
			$return = $this->EE->data_import_model->import_failed($schema_id);
		}
		
		$return            = (array) $return;
		$return['errors']  = count($this->EE->api_channel_entries->errors) ? $this->EE->api_channel_entries->errors : FALSE;
		$return['geocode'] = $item->geocode;
		
		if(defined('AJAX_REQUEST') && AJAX_REQUEST)
		{
			return $this->json((object) $return);
		}
		
		return (object) $return;
	}
	
	public function import_from_csv($csv_data, $schema_id)
	{
		$fc = iconv('windows-1250', 'utf-8', file_get_contents($_FILES['file']['tmp_name']));
 
 		file_put_contents($_FILES['file']['tmp_name'], $fc);
		
		$force_geocoder = $this->EE->input->get_post('force_geocoder');
		$force_geocoder = $force_geocoder ? 1 : 0;
		
		$settings = (array) $this->EE->data_import_model->get_setting($schema_id);
		$settings['schema_id'] = $schema_id;
		
		if(isset($settings['eol']) && !empty($settings['eol']))
		{
			$this->eol = $settings['eol'];
		}
		
		if(isset($settings['delimeter']) && !empty($settings['delimeter']))
		{
			$this->delimeter = $settings['delimeter'];
		}
		
		$entries = $this->load_file($_FILES['file']['tmp_name']);

		$channel_fields = $this->EE->channel_data->get_fields()->result();
		$fields       = $this->EE->channel_data->utility->reindex($channel_fields, 'field_name');
		
		$group_field  = FALSE;
		$entry_id     = FALSE;
		
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
					if(!isset($entry[$field->column_name]))
					{							
						show_error('<i>'.$field->column_name.'</i> is not a valid column in your .CSV. Ensure that the column names match 100%, they are case-sensitive.');
					}
					
					$geocode .= isset($entry[$field->column_name]) ? $entry[$field->column_name] . ' ' : NULL;
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
			
			$geocode = $this->trim($geocode);
			
			if(!empty($settings['group_by']))
			{				
				if(!isset($data[$entry[$settings['group_by']]]))
				{
					$data[$entry[$settings['group_by']]] = $this->build_entry_data($force_geocoder);
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
				$data[] = $this->build_entry_data($force_geocoder);	
			}
		}
		
		if(count($data) > 0)
		{
			$this->EE->db->insert_batch('gmap_import_pool', $data);	
		}
		
		$this->EE->data_import_model->reset_stat_count($schema_id);
	}
	
	public function load_file($file, $columns = FALSE)
	{
		ini_set("auto_detect_line_endings", "1");
			
		$csv = new File_CSV_DataSource;

		$csv->settings(array(
			'eol' => $this->eol,
			'delimiter' => $this->delimeter
		));
		
		if($skip_rows = config_item('gmap_import_skip_rows'))
		{
			$csv->settings['skipRows'] = $skip_rows;
		}
		
		$csv->settings['eol'] = $this->eol;

		if (!$csv->load($file))
		{
			die('can not load csv file');
		}

		if (!$csv->isSymmetric())
		{
			$csv->symmetrize();
		}	
		
		$entries = $csv->connect();
		
		return $entries;
	}
		
	public function json($data)
	{
		header('Content-type: application/json');
		echo json_encode($data);
		exit();
	}
}