<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Geocoder Class
 *
 * A class that extends the base Google API and geocodes addresses
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/libraries/channel_data
 * @version		1.0.1
 * @build		20120224
 */
 
require_once('Google_API.php');

class Geocoder extends Google_API {
	
	public $address			= FALSE;
	public $bounds			= FALSE;
	public $latlng			= FALSE;
	public $region			= FALSE;
	public $language		= FALSE;
	public $url				= NULL;
	public $regex			= '/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/';
	
	public function __construct()
	{
		$this->region    = config_item('gmap_default_geocoding_region');
		$this->language  = config_item('gmap_default_geocoding_language');
		$this->base_url .= 'geocode/'.$this->format;
		$this->url = $this->construct_url();
	}
	
	/**
	 * Geocodes an address
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	
	public function address($address)
	{
		return $this->query($address);
	}
	
	/**
	 * Reverse geocodes a coordinate
	 *
	 * @access	public
	 * @param	string
	 * @return	object
	 */
	 
	public function latlng($latlng)
	{
		return $this->query(FALSE, $latlng);
	}
	
	/**
	 * Returns an a block of JavaScript code that will execute Asynchronously
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	
	public function javascript($map_id, $query, $callback = NULL)
	{
		$js = '
			'.$map_id.'_geocoder.geocode({address: "'.$query.'"}, function(response, status) {
				'.$callback.'
			});
		';
		
		return $js;
	}
	
	/**
	 * Geocodes an address or reverse geocodes a coordinate
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	string
	 */
	
	public function query($query = FALSE, $limit = FALSE, $offset = 0, $reverse_lookup = FALSE, $return_url = FALSE)
	{
		if($query)
		{
			
			if(!$reverse_lookup)
			{
				$this->address 	= urlencode($query);
				$this->latlng	= FALSE;
			}
			else
			{
				$this->latlng 	= urlencode($query);
				$this->address	= FALSE;			
			}
		}

		$url = $this->construct_url($this->base_url, $limit, $offset);
		
		if($return_url) return $url;
		
		$response = $this->connect($url);
		
		if($response === FALSE)
		{
			if(empty($this->error_string))
			{
				$this->error_string = 'An unknown error has occurred with cURL. Make sure the cURL Library is properly enabled on your server.';
			}
			
			show_error($this->error_string);
		}
		
       	$results = array();
       	
       	$absolute_count = 0;
       	$count			= 0;
       	$response		= json_decode($response);
       	
		foreach($response->results as $result)
		{	
			if($offset <= $absolute_count)
			{
				$results[] = $result;
			
				if($limit !== FALSE && $count + 1 >= $limit)
				{
					$response->results = $results;
					return $response;
				}
				
				$count++;
			}
			
			$absolute_count++;
		}
		
       	return $response;
	}
	
	/**
	 * Constructs the URL from the object properties or by passing a unique URL
	 *
	 * @access	public
	 * @param	mixed
	 * @return	string
	 */
	
	public function construct_url($url = FALSE, $limit = FALSE, $offset = 0)
	{
		$this->url = !$url ? rtrim($this->base_url, '/') . '/' . $this->format : $url;
		
		if($this->secure)
		{
			$this->url = str_replace('http://', 'https://', $this->url);
		}
	
		$this->url .= $this->sensor 	? '?sensor=true' : '?sensor=false';
		$this->url .= $this->language	? '&language='	. $this->language	: NULL;
		$this->url .= $this->bounds 	? '&bounds='  	. $this->bounds		: NULL;
		$this->url .= $this->region 	? '&region='  	. $this->region		: NULL;
		$this->url .= $this->address 	? '&address=' 	. $this->address	: NULL;
		$this->url .= $this->latlng 	? '&latlng='	. $this->latlng		: NULL;
				
		if($limit !== FALSE)
		{
			$this->url .= '&limit='.$limit;
		}

		if($offset !== FALSE)
		{
			$this->url .= '&offset='.$offset;
		}

		return $this->url;
	}
	
}