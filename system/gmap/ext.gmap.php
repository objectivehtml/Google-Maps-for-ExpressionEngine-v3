<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.3.0
 * @build		20120522
 */

require_once PATH_THIRD . 'gmap/config/gmap_config.php';

class Gmap_ext {
	
	public $version;

    public $name       		= 'Google Maps for ExpressionEngine';
    public $description    	= 'The complete geolocation and mapping toolkit.';
    public $settings_exist 	= 'n';
  	public $docs_url       	= 'http://www.objectivehtml.com/google-maps';
	public $settings 		= array();
	public $required_by 	= array('module');
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
        $this->version	= config_item('gmap_version');
	}
		 
	/**
	 * Channel Entries Tagdata
	 *
	 * Sets the JavaScript protection to FALSE for the channel entries
	 * loop to parse variables inside JavaScript.
	 *
	 * @return string
	 */
	 
	public function channel_entries_tagdata($tagdata, $row, $obj)
	{
		if(ee()->extensions->last_call)
		{
			$tagdata = ee()->extensions->last_call;
		}
		
		$obj->EE->TMPL->protect_javascript = FALSE;
		
		if(isset($obj->categories[$row['entry_id']]))
		{
			$categories = $this->get_categories($obj, $row['entry_id']);
			
			$tagdata = $obj->EE->TMPL->parse_variables_row($tagdata, array('category_ids' => $categories));
		}
	
		return $tagdata;
	}
	 
	/**
	 * Channel Entries Tagdata End
	 *
	 * Parses e 
	 *
	 * @return string
	 */
	 
	public function channel_entries_tagdata_end($tagdata, $row, $obj)
	{
		if(ee()->extensions->last_call)
		{
			$tagdata = ee()->extensions->last_call;
		}
		
		if(isset($obj->categories[$row['entry_id']]))
		{
			$categories = $this->get_categories($obj, $row['entry_id']);
			
			$tagdata = str_replace('["CATEGORY_IDS"]', json_encode($categories), $tagdata);
		}
		
		return $tagdata;
	}
	
	private function get_categories($obj, $entry_id)
	{
		$return = array();
		
		foreach($obj->categories[$entry_id] as $category)
		{
			$return[] = $category[0];	
		}
		
		$return = implode('|', $return);
		
		return $return;
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
// END CLASS

/* End of file ext.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/ext.gmap.php */