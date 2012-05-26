<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Safecracker Registration
 * 
 * @package		Safecracker Registration
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/safecracker-registration
 * @version		1.1.4
 * @build		20120220
 */

class Safecracker_registration_mcp {
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->driver('channel_data');
		$this->EE->load->library('theme_loader', array(
			'module_name' => 'safecracker_registration'
		));			
	}
		
	public function index()
	{
		$this->EE->cp->set_variable('cp_page_title', 'Safecracker Registration > Member Migration Utility');
		//$this->EE->cp->set_right_nav(array('Back to Settings' => $this->cp_url('index')));
		
		$channel_data = $this->EE->channel_data->get_channels(array(
			'order_by'  => 'channel_title',
			'sort'		=> 'desc'
		))->result();
		
		$channels = array('' => '');
		$fields	  = array();
		
		foreach($channel_data as $channel)
		{
			$fields[$channel->channel_id]   = $this->EE->channel_data->get_channel_fields($channel->channel_id, array(
				'order_by' => 'field_order',
				'sort'	   => 'asc'
			))->result();
			
			$channels[$channel->channel_id] = $channel->channel_title;
		}
				
		$vars = array(
			'channel_fields'	=> json_encode($fields),
			'form_action_url'  	=> $this->current_url('ACT', $this->EE->channel_data->get_action_id(__CLASS__, 'upload_action')),
			'member_fields'    	=> $this->EE->channel_data->get_member_fields(array(
				'order_by'  => 'm_field_order',
				'sort'		=> 'asc'
			)),
			'settings'		   	=> json_encode(array(
				'get_fields_url' => $this->cp_url('get_channel_fields', FALSE),
			)),
			'channel_dropdown' 	=> form_dropdown('channel_id', $channels),
			'return'			=> $this->current_url()
		);
		
		return $this->EE->load->view('upload', $vars, TRUE);
	}
	
	public function get_channel_fields()
	{
		$channel_id = $this->EE->input->get('channel_id');
		$select		= $this->EE->input->get('select');
		$fields		= $this->EE->channel_data->get_channel_fields($channel_id, array(
			'order_by' => 'field_order',
			'sort'	   => 'asc'
		));
		
		$options	= '<option value=""></option>';
		
		foreach($fields->result() as $field)
		{
			$selected = ((int) $field->field_id == (int) $channel_id) ? 'selected="selected"' : NULL;
			$options .= '<option value="'.$field->field_id.'" '.$selected.'>'.$field->field_label.'</option>';
		}
		
		exit($options);
	}
	
	public function upload_action()
	{	
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_entries');
		
		$channel_id = $this->EE->input->post('channel_id');
		
		// Show error if there is no channel_id in the POST
		if(!$channel_id)
		{
			$this->EE->output->show_user_error('general', 'You did not define a Member channel');
		}
		
		// Get the members
		$members 		= $this->EE->channel_data->get_members();
		
		// Get the member fields
		$member_fields 	= $this->EE->channel_data->get_member_fields(array(
			'order_by'  => 'm_field_order',
			'sort'		=> 'asc'
		));
		
		// Get the member fields
		$member_fields_by_name = array();
		$member_fields_by_id   = array();
		
		foreach($member_fields->result() as $field)
		{
			$member_fields_by_name[$field->m_field_name] = $field;
			$member_fields_by_id[$field->m_field_id]     = $field;
		}
		
		// Get the channel fields
		$channel_fields_by_name = array();
		$channel_fields_by_id   = array();
		
		$channel_fields = $this->EE->channel_data->get_channel_fields($channel_id);
		
		foreach($channel_fields->result() as $field)
		{
			$channel_fields_by_name[$field->field_name]  = $field;
			$channel_fields_by_id[$field->field_id]		 = $field;
		}
		
		// Get the existing entries
		$existing_entries = $this->EE->channel_data->get_channel_entries($channel_id);
		
		$search_records = array();
		
		$post = array();
		
		foreach($members->result_array() as $member)
		{	
			$valid		= TRUE;
			$match		= array();
			
			$new_record = array(
				'title'		 => $this->parse_entry_title($_POST['title_fields'], $member),
				'entry_date' => $this->EE->localize->now,
				'author_id'  => $member['member_id']
			);
			
			foreach($member as $member_field => $member_field_value)
			{					
				foreach($existing_entries->result() as $entry)
				{	
					$match_title = NULL;
					
					foreach($_POST['search_fields'] as $field_id)
					{
						$channel_field = $channel_fields_by_id[$field_id]->field_name;
						$channel_value = $entry->$channel_field;
						
						$match_value   = $member[$member_field];
						
						if($channel_value == $match_value)
						{
							$match[$channel_field] = TRUE;
						}
						else
						{
							if(!isset($match[$channel_field]) || $match[$channel_field] !== TRUE)
							{
								$match[$channel_field] = FALSE;
							}
						}
					}
				}
				
				if(isset($_POST['member_field'][$member_field]))
				{	
					$field_id 	   = $_POST['member_field'][$member_field];
					$channel_field = $channel_fields_by_id[$field_id];
					
					$new_record['field_id_'.$channel_field->field_id] = $member_field_value;
					$new_record['field_ft_'.$channel_field->field_id] = $channel_field->field_fmt;
				}
			}
			
			$valid = $this->check_validity($match);
			
			if($valid)
			{			
				$this->EE->api_channel_entries->submit_new_entry($channel_id, $new_record);
				
				if(count($this->EE->api_channel_entries->errors) > 0)
				{
					$this->EE->output->show_user_error('general', 'The member <b>'.$member['member_id'].'</b> could not be created.');
				}
							
			}
			
		}
		
		$this->EE->functions->redirect($this->EE->input->post('return').'&success=true');
	}
	
	private function check_validity($match)
	{	
		$valid 	     = TRUE;
		$valid_count = 0;
		
		foreach($match as $is_match)
		{
			if($is_match == TRUE)
			{
				$valid_count++;
			}
		}
		
		if(count($match) == $valid_count)
		{
			$valid = FALSE;
		}
		
		return $valid;
	}
	
	private function get_settings()
	{
		$settings = $this->EE->db->get('safecracker_registration_settings');
		
		$return   = array();
		
		foreach($settings->result() as $setting)
		{
			$return[$setting->key] = $setting->value;
		}
		
		var_dump($return);exit();
	}
	
	private function parse_entry_title($title, $data)
	{
		foreach($data as $field => $value)
		{
			$title = str_replace(LD.$field.RD, $value, $title);	
		}
		
		return $title;
	}
	
	private function cp_url($method = 'index', $amp = TRUE)
	{
		$replace = $amp ? AMP : '&';
		
		$url = str_replace('index.php', BASE, config_item('cp_url')) . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=' . str_replace('_mcp', '', strtolower(__CLASS__)) . AMP . 'method=' . $method;
		
		$url = str_replace(AMP, $replace, $url);
		
		return $url;
	}
		
	private function current_url($append = '', $value = '')
	{
		$url = (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['SERVER_NAME'] : 'http://'.$_SERVER['SERVER_NAME'];
		
		if(!empty($append))
			$url .= '?'.$append.'='.$value;
		
		return $url;
	}
}

// END CLASS

/* End of file mcp.safecracker_registration.php */
/* Location: ./system/expressionengine/third_party/modules/safecracker_registration/mcp.safecracker_registration.php */