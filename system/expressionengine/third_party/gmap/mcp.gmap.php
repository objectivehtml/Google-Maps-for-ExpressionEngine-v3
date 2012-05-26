<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.0.18
 * @build		20120506
 */

class Gmap_mcp {

	function __construct()
	{
		$this->EE =& get_instance();
	}
	
	function index()
	{
		$this->update();
		
		return 'This page will soon have licensing information and store your product key.';
	}
	
	private function update()
	{
		$update = new Gmap_upd();
		$update->update();
	}
	
}
// END CLASS

/* End of file mcp.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/mcp.gmap.php */