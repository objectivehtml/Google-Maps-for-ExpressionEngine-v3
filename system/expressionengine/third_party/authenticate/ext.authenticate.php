<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Authenticate
 * 
 * @package		Authenticate
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/authenticate
 * @version		1.0.6
 * @build		20120301
 */
 
class Authenticate_ext {

    public $name       		= 'Authenticate';
    public $version        	= '1.0.5';
    public $description    	= '';
    public $settings_exist 	= 'n';
  	public $docs_url       	= 'http://www.objectivehtml.com/authenticate/documentation';
	public $settings 		= array();
	public $required_by 	= array('module');
			
	public function __construct()
	{
	   	$this->EE =& get_instance();

        $this->settings = array();
	}
		
	/**
	 * Member Logout
	 *
	 * Remove the ugly redirect screen on logout
	 *
	 * @access	public
	 * @return	string
	 */
	public function member_member_logout()
	{		
		$this->EE->load->config('config');
		
		$url	  = $this->EE->input->get('return');
		$return   = $url ? $url : config_item('site_url');
		
		if(config_item('remove_redirect_screen'))
		{
			$this->EE->functions->redirect($return);
		}
	}
		 
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @return void
	 */
	function activate_extension()
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
	function update_extension($current = '')
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
	function disable_extension()
	{
	    $this->EE->db->where('class', __CLASS__);
	    $this->EE->db->delete('extensions');
	}
	
}