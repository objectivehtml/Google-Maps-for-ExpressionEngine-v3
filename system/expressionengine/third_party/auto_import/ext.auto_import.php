<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Auto Import
 * 
 * @package		Auto Import
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com
 * @version		0.1.0
 * @build		20120516
 */


require 'config/auto_import_config.php';

if(!defined('AUTO_IMPORT_VERSION'))
{	
	define('AUTO_IMPORT_VERSION', $config['AUTO_IMPORT_VERSION']);
}

class Auto_import_ext {

    public $name       		= 'Auto Import';
    public $version        	= AUTO_IMPORT_VERSION;
    public $description    	= 'Easily create e-mail template and automatically generated emails every time an entry is submitted.';
    public $settings_exist 	= 'n';
  	public $docs_url       	= 'http://www.objectivehtml.com';
	public $settings 		= array();
	public $required_by 	= array('module');
	
	public $new_entry		= TRUE;
	
	public function __construct()
	{
	   	$this->EE =& get_instance();

        $this->settings = array();
    }

	public function settings()
	{
		return '';
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