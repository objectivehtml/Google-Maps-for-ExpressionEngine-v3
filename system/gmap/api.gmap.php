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

class Gmap_api extends Base_API {
	
	public function __construct()
	{
		parent::__construct();

		$this->EE->load->library('Google_maps');
	}
	
	public function build_response($response)
	{
		return $this->EE->google_maps->build_response($response);
	}
	
	public function geocode($query, $limit = FALSE, $offset = 0)
	{
		return $this->EE->google_maps->geocode($query, $limit, $offset);
	}
	
	public function directions($origin, $destination, $params = array())
	{
		return $this->EE->google_maps->directions($origin, $destination, $params = array());
	}
	
	public function center($map_id, $latitude, $longitude, $script = FALSE)
	{
		return $this->EE->google_maps->center($map_id, $latitude, $longitude, $script = TRUE);
	}
	
	public function infowindow($params)
	{
		return $this->EE->google_maps->infowindow($params);
	}
	
	public function init($map_id, $options = FALSE, $args)
	{
		return $this->EE->google_maps->init($map_id, $options, $args);
	}
	
	public function latlng($latitude, $longitude, $script = FALSE)
	{
		return $this->EE->google_maps->latlng($latitude, $longitude, $script);
	}
	
	public function marker($params)
	{
		return $this->EE->google_maps->marker($params);
	}
	
	public function parse_geocoder_response($results, $limit = FALSE, $offset = 0, $prefix = '')
	{
		return $this->EE->google_maps->parse_geocoder_response($results, $limit, $offset, $prefix);
	}
	
	public function route($params = array()) 
	{
		return $this->EE->google_maps->route($params);
	}
	
	public function region($params = array())
	{
		return $this->EE->google_maps->region($params);
	}
	
	public function zoom($map_id, $zoom, $script = TRUE)
	{
		return $this->EE->google_maps->zoom($map_id, $zoom, $script = TRUE);
	}
	
	public function return_js($js, $include_script_tag = TRUE)
	{
		return $this->EE->google_maps->return_js($js, $include_script_tag = TRUE);
	}
	
	public  function is_checked_or_selected($post, $item)
	{
		return $this->EE->google_maps->is_checked_or_selected($post, $item);
	}
	
	public function clean_js($str)
	{
		return $this->EE->google_maps->clean_js($str);
	}

	public function usage()
	{

	}
}

// END CLASS

/* End of file api.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/api.gmap.php */