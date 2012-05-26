<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Safecracker Registration Library
 * 
 * @package		Safecracker Registration
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/safecracker-registration
 * @version		1.0.1
 * @build		20120215
 */

class Safecracker_registration_lib {

	public $required_fields	= array('email', 'password', 'password_confirm');
	public $optional_fields = array('screen_name', 'location', 'url');
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->driver('channel_data');
				
		if ( ! class_exists('EE_Validate'))
		{
			require APPPATH.'libraries/Validate.php';
		}
		
		$this->EE->load->language('member');
		$this->EE->load->model('member_model');
		$this->EE->load->library('auth');
		$this->EE->load->library('form_validation');
	}
	
	public function activate_member($data)
	{
		$this->EE->load->language('member');
				
		$mailinglist_subscribe = FALSE;
		
		if ($this->EE->config->item('req_mbr_activation') == 'email')
		{
			$action_id  = $this->EE->functions->fetch_action_id('Member', 'activate_member');

			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			$board_id = ($this->EE->input->get_post('board_id') !== FALSE && is_numeric($this->EE->input->get_post('board_id'))) ? $this->EE->input->get_post('board_id') : 1;

			$forum_id = ($this->EE->input->get_post('FROM') == 'forum') ? '&r=f&board_id='.$board_id : '';

			$add = ($mailinglist_subscribe !== TRUE) ? '' : '&mailinglist='.$_POST['mailinglist_subscribe'];

			$swap = array(
				'name'				=> $name,
				'activation_url'	=> $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$data['authcode'].$forum_id.$add,
				'site_name'			=> stripslashes($this->EE->config->item('site_name')),
				'site_url'			=> $this->EE->config->item('site_url'),
				'username'			=> $data['username'],
				'email'				=> $data['email']
			 );

			$template = $this->EE->functions->fetch_email_template('mbr_activation_instructions');
			$email_tit = $this->var_swap($template['title'], $swap);
			$email_msg = $this->var_swap($template['data'], $swap);

			// Send email
			$this->EE->load->helper('text');

			$this->EE->load->library('email');
			$this->EE->email->wordwrap = true;
			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
			$this->EE->email->to($data['email']);
			$this->EE->email->subject($email_tit);
			$this->EE->email->message(entities_to_ascii($email_msg));
			$this->EE->email->Send();
		}
	}
	
	public function edit_member(&$obj)
	{
		$email			  = $this->EE->input->post('email');
		$username		  = $this->EE->input->post('username');
		
		if(isset($_POST['username']))
		{
			$this->EE->form_validation->set_rules('username', 'Username', 'required');
		}
		else
		{
			$username = $email;
		}
		
		$screen_name	  = $this->EE->input->post('screen_name') ? $this->EE->input->post('screen_name') : $username;
		$password 		  = $this->EE->input->post('password');
		$password_confirm = $this->EE->input->post('password_confirm');
		
		$this->EE->form_validation->set_rules('email', 'E-mail', 'required|valid_email');
		
		$member_id = $this->EE->input->post('safecracker_registration_member_id');
		$member	   = $this->EE->channel_data->get_member($member_id)->result_array();
		
		if(isset($member[0]))
		{
			$VAL = new EE_Validate(array(
				'member_id'			=> '',
				'val_type'			=> 'update', // new or update
				'fetch_lang' 		=> TRUE,
				'require_cpw' 		=> FALSE,
			 	'enable_log'		=> FALSE,
				'username'			=> $username,
				'cur_username'		=> '',
				'screen_name'		=> $screen_name,
				'cur_screen_name'	=> '',
				'password'			=> $password,
			 	'password_confirm'	=> $password_confirm,
			 	'cur_password'		=> '',
			 	'email'				=> $email,
			 	'cur_email'			=> ''
			 ));
	
			$member_data = array(
				'username' 	  => $username,
				'screen_name' => $screen_name,
				'email'		  => $email
			);
			
			if(isset($_POST['screen_name']))
			{
				$VAL->validate_screen_name();
			}
			
			if(!empty($password) && !empty($password_confirm))
			{					
				$VAL->validate_password();
			}
							
			$VAL->validate_email();
			
			if(count($VAL->errors) == 0)
			{
				if(!empty($password) && !empty($password_confirm))
				{
					$this->EE->auth->update_password($member, $password);
				}

				$this->EE->member_model->update_member($member_id, $member_data, array(
					'member_id' => $member_id	
				));
			
			}
			else
			{
				$obj->errors = array_merge($obj->errors, $VAL->errors);
			}				
		}
		
	}
	
	public function register_member(&$obj)
	{
		$email			  = $this->EE->input->post('email');
		$username		  = $this->EE->input->post('username');
		
		if(isset($_POST['username']))
		{
			$this->EE->form_validation->set_rules('username', 'Username', 'required');
		}
		else
		{
			$_POST['username'] = $email;
			$username = $email;
		}
		
		$this->EE->form_validation->set_rules('password', 'Password', 'required|matches[password_confirm]');
		$this->EE->form_validation->set_rules('password_confirm', 'Password Confirmation', 'required');
		$this->EE->form_validation->set_rules('email', 'E-mail', 'required|valid_email');

		$screen_name	  	 = $this->EE->input->post('screen_name') ? $this->EE->input->post('screen_name') : $username;
		
		$dynamic_screen_name = $this->EE->input->post('safecracker_registration_dynamic_screen_name');
		
		if($dynamic_screen_name)
		{
			foreach($_POST as $field => $value)
			{
				$dynamic_screen_name = str_replace('['.$field.']', $value, $dynamic_screen_name);
			}
			
			$screen_name = $dynamic_screen_name;
		}		
		
		$_POST['screen_name'] = $screen_name;		
		$password 		  	  = $this->EE->input->post('password');
		$password_confirm 	  = $this->EE->input->post('password_confirm');
		
		if(!$this->EE->form_validation->run())
		{
			foreach($this->required_fields as $field)
			{
				$error = $this->EE->form_validation->error($field);
				
				$obj->field_errors[$field] = $error;
			}
		}
		else
		{
			$VAL = new EE_Validate(array(
				'member_id'			=> '',
				'val_type'			=> 'new', // new or update
				'fetch_lang' 		=> TRUE,
				'require_cpw' 		=> FALSE,
			 	'enable_log'		=> FALSE,
				'username'			=> $username,
				'cur_username'		=> '',
				'screen_name'		=> $screen_name,
				'cur_screen_name'	=> '',
				'password'			=> $password,
			 	'password_confirm'	=> $password_confirm,
			 	'cur_password'		=> '',
			 	'email'				=> $email,
			 	'cur_email'			=> ''
			 ));
	
			$VAL->validate_username();
			
			if(isset($_POST['screen_name']))
			{
				$VAL->validate_screen_name();
			}
			
			$VAL->validate_password();				
			$VAL->validate_email();
	
			// Do we allow new member registrations?
			if ($this->EE->config->item('allow_member_registration') == 'n')
			{
				$obj->errors[] = 'Member registrations are not accepted at this time.';
			}
			
			// Is user banned?
			if ($this->EE->session->userdata('is_banned') === TRUE)
			{
				$obj->errors[] = lang('not_authorized');
			}
	
			// Blacklist/Whitelist Check
			if ($this->EE->blacklist->blacklisted == 'y' && 
				$this->EE->blacklist->whitelisted == 'n')
			{
				$obj->errors[] = lang('not_authorized');
			}
			
			if (isset($_POST['email_confirm']) && $_POST['email'] != $_POST['email_confirm'])
			{
				$obj->field_errors['email_confirm'] = lang('mbr_emails_not_match');
			}
	
			if ($this->EE->config->item('use_membership_captcha') == 'y')
			{
				if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
				{
					$obj->field_errors['captcha'] = lang('captcha_required');
				}
			}				
	
			if ($this->EE->config->item('require_terms_of_service') == 'y')
			{
				if ( ! isset($_POST['accept_terms']))
				{
					$obj->field_errors['accept_terms'] = lang('mbr_terms_of_service_required');
					
				}
			}
						
			$obj->field_errors = array_merge($obj->field_errors, $VAL->errors);
			
			if(count($obj->field_errors) == 0)
			{
				$this->set_validation_rules($obj);	
				
				if($this->EE->form_validation->run())
				{
					include_once(APPPATH.'modules/member/mod.member.php');				
					include_once(APPPATH.'modules/member/mod.member_register.php');
					
					$secure_form = TRUE;
					
					// Secure Mode Forms?
					if ($this->EE->config->item('secure_forms') == 'y')
					{	
						$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes WHERE hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");

						if($query->num_rows() == 0)
						{
							$secure_form = FALSE;
						}
					}
		
					$member_register = new Member_register();
					$member_register->register_member();
				
					// Secure Mode Forms?
					if ($this->EE->config->item('secure_forms') == 'y' && $secure_form)
					{							
						$this->EE->db->insert('security_hashes', array(
							'hash' 		   => $this->EE->db->escape_str($_POST['XID']),
							'ip_address'   => $this->EE->input->ip_address(),
							'date' 		   => $this->EE->localize->now
						));
					}
					
					$this->EE->extensions->end_script = FALSE;
				}
			}		
		}				
	}
	
	public function set_validation_rules(&$obj)
	{
		foreach ($obj->custom_fields as $i => $field)
		{			
			$isset = (isset($_POST['field_id_'.$field['field_id']]) || isset($_POST[$field['field_name']]) || (((isset($_FILES['field_id_'.$field['field_id']]) && $_FILES['field_id_'.$field['field_id']]['error'] != 4) || (isset($_FILES[$field['field_name']]) && $_FILES[$field['field_name']]['error'] != 4)) && in_array($field['field_type'], $this->file_fields)));

			if ( ! $obj->edit || $isset)
			{
				$field_rules = array();
				
				if ( ! empty($rules[$field['field_name']]))
				{
					$field_rules = explode('|', $this->decrypt_input($rules[$field['field_name']]));
				}
				
				if ( ! in_array('call_field_validation['.$field['field_id'].']', $field_rules))
				{
					array_unshift($field_rules, 'call_field_validation['.$field['field_id'].']');
				}
				
				if ($field['field_required'] == 'y' && ! in_array('required', $field_rules))
				{
					array_unshift($field_rules, 'required');
				}
				
				$this->EE->form_validation->set_rules($field['field_name'], $field['field_label'], implode('|', $field_rules));
			}
		}
	}
	
	/**
	 * Replace variables
	 */
	private function var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}

		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}

		return $str;
	}
	
}