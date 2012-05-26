<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package		Auto Tweet
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.0.1
 * @build		20120313
 */
 
require 'libraries/TwitterOAuth.php';

class Auto_tweet_ext {

    public $name       		= 'Auto_tweet';
    public $version        	= '1.0.1';
    public $description    	= 'Tweets the perma link every time an entry is posted.';
    public $settings_exist 	= 'y';
  	public $docs_url       	= 'http://www.objectivehtml.com';
	public $settings 		= array();
	public $required_by 	= array('module');
			
	public function __construct()
	{
	   	$this->EE =& get_instance();

        $this->settings = array();
	}
		
	/**
	 * Plugin Name
	 *
	 * Plugin description
	 *
	 * @access	public
	 * @return	string
	 */
	public function entry_submission_end($entry_id, $meta, $data)
	{		 	
		$this->EE->load->library('twitter');
		
		log_message('debug', 'Initialized the Auto Tweet entry_submission_end hook.');
		
		$this->EE->twitter->update_status($entry_id);
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