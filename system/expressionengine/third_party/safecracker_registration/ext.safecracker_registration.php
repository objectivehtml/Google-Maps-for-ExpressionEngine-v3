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

class Safecracker_registration_ext {

    public $name       		= 'Safecracker Registration';
    public $version        	= '1.1.4';
    public $description    	= '';
    public $settings_exist 	= 'n';
  	public $docs_url       	= 'http://www.objectivehtml.com/safecracker-registration';
	public $settings 		= array();
	public $required_by 	= array('module');
	public $required_fields	= array('email', 'username', 'password', 'password_confirm');
	public $optional_fields = array('screen_name', 'location', 'url');
	
	public function __construct()
	{
        $this->settings = array();
        
		$this->EE =& get_instance();
	}
	
	public function safecracker_submit_entry_start(&$obj)
	{
		$this->EE->load->library('safecracker_registration_lib');
		
		if( (int) $this->EE->input->post('safecracker_registration_register_member') ||
			(int) $this->EE->input->post('safecracker_registration_edit_member'))
		{		
			if( (int) $this->EE->input->post('safecracker_registration_edit_member') == 1)
			{
				$this->EE->safecracker_registration_lib->edit_member($obj);
			}
			
			if( (int) $this->EE->input->post('safecracker_registration_register_member') == 1)
			{
				$this->EE->safecracker_registration_lib->register_member($obj);
			}
		}		
	}
	
	public function safecracker_submit_entry_end(&$obj)
	{
		$this->EE->load->language('member');
				
		if( (int) $this->EE->input->post('safecracker_registration_register_member') ||
			(int) $this->EE->input->post('safecracker_registration_edit_member'))
		{		
			
			if ($this->EE->config->item('req_mbr_activation') == 'email')
			{
				$message = lang('mbr_membership_instructions_email');
				
				$this->EE->output->show_user_error('general', $message);
			}
			elseif ($this->EE->config->item('req_mbr_activation') == 'manual')
			{
				$message = lang('mbr_admin_will_activate');
				
				$this->EE->output->show_user_error('general', $message);
			}
			
		}
	}

	public function safecracker_entry_form_tagdata_start($tagdata, &$obj)
	{	
		$register_member = $obj->EE->TMPL->fetch_param('register_member');
		$register_member = $this->param($register_member, FALSE, TRUE);
				
		$edit_member = $obj->EE->TMPL->fetch_param('edit_member');
		$edit_member = $this->param($edit_member, FALSE, TRUE);
		
		$this->EE->load->library('safecracker_registration_lib');
		
		if($register_member === TRUE || $edit_member === TRUE)
		{
			// Do we allow new member registrations?
			if ($this->EE->config->item('allow_member_registration') == 'n')
			{
				$this->EE->output->show_user_error('general', 'Member registrations are not allowed at this time');
			}
	
			// Is user banned?
			if ($this->EE->session->userdata('is_banned') === TRUE)
			{
				$this->EE->output->show_user_error('general', 'Member registrations are not allowed at this time');
			}
	
			// Blacklist/Whitelist Check
			if ($this->EE->blacklist->blacklisted == 'y' && 
				$this->EE->blacklist->whitelisted == 'n')
			{
				$this->EE->output->show_user_error('general', 'Member registrations are not allowed at this time');
			}
			
			$group_id = $obj->EE->TMPL->fetch_param('group_id');
			$group_id = $this->param($group_id, FALSE);
			
			if($group_id)
			{
				$member_groups = $this->EE->channel_data->get_member_groups();
				
				$valid_group = FALSE;
				
				foreach($member_groups->result_array() as $group)
				{
					if( (int) $group_id == (int) $group['group_id'])
					{
						$valid_group = TRUE;
					}
				}
				
				if( ! $valid_group)
				{
					$this->EE->output->show_user_error('general', 'Group \''.$group_id.'\' is not a valid member group.');			
				}
				
				$obj->form_hidden(array(
					'safecracker_registration_group_id' => $group_id
				));
			}
								
			if($register_member)
			{
				$obj->form_hidden(array(
					'safecracker_registration_register_member' => TRUE
				));
			}
			
			$dynamic_screen_name = $obj->EE->TMPL->fetch_param('dynamic_screen_name');
			$dynamic_screen_name = $this->param($dynamic_screen_name, FALSE);
			
			if($dynamic_screen_name)
			{
				$obj->form_hidden(array(
					'safecracker_registration_dynamic_screen_name' => $dynamic_screen_name
				));
			}
			
			$loggin_member	 = $obj->EE->TMPL->fetch_param('loggin_member');
			$loggin_member	 = $this->param($loggin_member, TRUE, TRUE);
			
			$obj->form_hidden(array(
				'safecracker_registration_loggin_member' => $loggin_member ? 'y' : 'n'
			));
		
			$member_id	 = $obj->EE->TMPL->fetch_param('member_id');
			$member_id	 = str_replace('CURRENT_USER', $this->EE->session->userdata('member_id'), $this->param($member_id, 0));
			
			if($edit_member)
			{
				$obj->form_hidden(array(
					'safecracker_registration_edit_member' => TRUE,
					'safecracker_registration_member_id' => $member_id
				));
								
				$member = $this->EE->channel_data->get_member($member_id);
				
				foreach($member->result() as $member)
				{
					foreach($member as $field => $value)
					{
						if($field != "password")
						{
							$_POST[$field] = $value;
						}
					}
				}
				
				$channel_id = $obj->channel['channel_id'];
				
				$entries	= $this->EE->channel_data->get_channel_entries($channel_id, array(
					'where' => array(
						'author_id' => $member_id
					)
				))->result_array();
				
				if(isset($entries[0]))
				{
					$obj->entry = $entries[0];
				}
			}
			
			$vars = array();
				
			foreach(array_merge($this->required_fields, $this->optional_fields) as $field)
			{
				$vars[0][$field] = $this->EE->input->post($field) ? $this->EE->input->post($field) : NULL;
			}
			
			$tagdata = $obj->EE->TMPL->parse_variables($tagdata, $vars);
		}

		return $tagdata;
	}
	
	public function member_member_register($data, $member_id)
	{
		$this->EE->load->library('safecracker_registration_lib');
		
		$this->EE->safecracker_registration_lib->activate_member($data);
		
		// Log user in (the extra query is a little annoying)
		$this->EE->load->library('auth');
		
		$member = $this->EE->db->get_where('members', array('member_id' => $member_id))->row();
		
		$group_id = $this->EE->input->post('safecracker_registration_group_id');
		
		if($group_id)
		{
			$this->EE->db->where('member_id', $member_id);
			$this->EE->db->update('members', array('group_id' => $group_id));
		}
		
		$loggin_member = $this->EE->input->post('safecracker_registration_loggin_member');
		$loggin_member = $loggin_member == 'y' ? TRUE : FALSE;
		
		if($loggin_member)
		{
			$incoming = new Auth_result($member);
			$incoming->remember_me(60*60*24*182);
			$incoming->start_session();
		}
		
		$this->EE->session->userdata['member_id'] = $member_id;
		$this->EE->session->userdata['group_id'] = 1;
		
		$_POST['author_id'] = $member_id;
			
		$this->EE->extensions->end_script = TRUE;
		
		return $data;
	}
	
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @return void
	 */
	public function activate_extension()
	{	    
	    return TRUE;
	}
	
	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return  mixed   void on update / false if none
	 */
	public function update_extension($current = '')
	{
	    if ($current == '' OR $current == $this->version)
	    {
	        return FALSE;
	    }
	
	    if ($current < '1.0')
	    {
	        // Update to version 1.0
	    }
	
	    $this->EE->db->where('class', __CLASS__);
	    $this->EE->db->update('extensions', array('version' => $this->version));
	}
	
	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	public function disable_extension()
	{
	    $this->EE->db->where('class', __CLASS__);
	    $this->EE->db->delete('extensions');
	}
	
	private function param($param, $default = FALSE, $boolean = FALSE, $required = FALSE)
	{
		$name = '';
			
		if($required && !$param) show_error('You must define a "'.$name.'" parameter in the '.__CLASS__.' tag.');
			
		if($param === FALSE && $default !== FALSE)
		{
			$param = $default;
		}
		else
		{				
			if($boolean)
			{
				$param = strtolower($param);
				$param = ($param == 'true' || $param == 'yes') ? TRUE : FALSE;
			}			
		}
		
		return $param;			
	}	 
	
}
// END CLASS

/* End of file ext.safecracker_registration.php */
/* Location: ./system/expressionengine/third_party/modules/safecracker_registration/ext.safecracker_registration.php */