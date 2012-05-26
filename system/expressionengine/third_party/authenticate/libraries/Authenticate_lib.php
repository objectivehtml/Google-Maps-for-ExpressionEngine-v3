<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Authenticate
 * 
 * @package		Authenticate
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/authenticate
 * @version		1.0
 * @build		20120204
 */
 
class Authenticate_lib {
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->library('auth');
	}
	
	public function login($auth_id, $auth_pass, $type = 'username')
	{
		switch($type)
		{
			case 'email':
				$return = $this->EE->auth->authenticate_email($auth_id, $auth_pass);
				break;
			case 'username':
				$return = $this->EE->auth->authenticate_username($auth_id, $auth_pass);
				break;
			case 'id':
				$return = $this->EE->auth->authenticate_id($auth_id, $auth_pass);
				break;
		}
		
		return $return;
	}
	
}