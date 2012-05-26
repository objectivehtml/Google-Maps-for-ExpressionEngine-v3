<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Authenticate
 * 
 * @package		Authenticate
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/authenticate
 * @version		1.0.7
 * @build		20120301
 */
 
class Authenticate {
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->lang->loadfile('authenticate');
		$this->EE->load->library('authenticate_lib');
		$this->EE->load->driver('channel_data');	
	}
	
	public function login_form()
	{	
		$this->EE->load->library('Base_form');
		
		$username_field = $this->param('username_field', 'username');
		$password_field = $this->param('password_field', 'password');
		$auth_type		= $this->param('auth_type', 'username');
		
		if($this->EE->input->post('authenticate_member_login'))
		{
			$auth_user = $this->EE->input->post($username_field);
			$auth_pass = $this->EE->input->post($password_field);
			
			$auth = $this->EE->authenticate_lib->login($auth_user, $auth_pass, $auth_type);
			
			if($auth !== FALSE)
			{
				$auth->remember_me(60*60*24*182);
				$auth->start_session();
				
				return $this->EE->base_form->redirect($auth->member('group_id'));
			}
			else
			{
				$this->EE->base_form->validate();
				
				if(count($this->EE->base_form->field_errors) == 0)
				{
					if($auth_type == 'username')
					{
						$this->EE->base_form->set_error(lang('authenticate_failed_message_user'));
					}
					else
					{
						$this->EE->base_form->set_error(lang('authenticate_failed_message_email'));
					}
				}
			}
		}
		
		$hidden_fields = array('authenticate_member_login' => 1);
		
		if($auth_type == 'email')
		{
			$rule = 'required|valid_email|trim';
		}
		else
		{
			$rule = 'required|trim';
		}
		
		$this->EE->base_form->TMPL = $this->EE->TMPL->tagdata;
		$this->EE->base_form->set_rule($username_field, $rule);
		$this->EE->base_form->set_rule($password_field, 'required|trim');
		
		$form_open = $this->EE->base_form->open($hidden_fields);
		
		return $form_open;
	}
	
	public function login()
	{
		return $this->login_form();
	}
		
	function forgot_password_form()
	{
		$email_field = $this->param('email_field', 'email');
		
		$this->EE->base_form->validate();
		
		if($this->EE->input->post('authenticate_reset_password'))
		{
			$emails = $this->EE->channel_data->get_members(array(
				'where' => array(
					'email' => $this->EE->input->post($email_field)
				)
			));
			
			if( count($this->EE->base_form->field_errors) == 0 &&
				count($this->EE->base_form->errors) == 0)
			{
				if($emails->num_rows() == 0)
				{
					$this->EE->base_form->set_error(lang('authenticate_invalid_email'));
				}
			}	
				
			if( count($this->EE->base_form->field_errors) == 0 &&
				count($this->EE->base_form->errors) == 0)
			{
				require_once(APPPATH.'modules/member/mod.member.php');
				require_once(APPPATH.'modules/member/mod.member_auth.php');

				$Auth = new Member_auth();
				$Auth->retrieve_password();
			}
		}
		
		$hidden_fields = array(
			'authenticate_reset_password' => 1
		);
		
		$this->EE->base_form->set_rule($email_field, 'required|valid_email|trim');
		
		$form_open = $this->EE->base_form->open($hidden_fields);
		
		return $form_open;
	}
	
	public function forgot_password()
	{
		return $this->forgot_password_form();
	}
	
	public function logout_url()
	{
		$return = $this->param('return', FALSE);
		
		if($return)
		{
			$return = '&return='.urlencode($return);
		}
		
		return '{path="LOGOUT"}'.($return ? $return : NULL);
	}
	
	private function parse($vars, $tagdata = FALSE)
	{
		if($tagdata === FALSE)
		{
			$tagdata = $this->EE->TMPL->tagdata;
		}
			
		return $this->EE->TMPL->parse_variables($tagdata, $vars);
	}
	
	private function param($param, $default = FALSE, $boolean = FALSE, $required = FALSE)
	{
		$name	= $param;
		$param 	= $this->EE->TMPL->fetch_param($param);
		
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