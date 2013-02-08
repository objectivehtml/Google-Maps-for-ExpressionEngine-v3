<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Directions Class
 *
 * A class that extends the base Google API and retrieves directions
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/libraries/channel_data
 * @version		1.0
 * @build		20120103
 */
 
require_once('Google_API.php');

class Directions extends Google_api {	
	
	public $origin			= FALSE;
	public $destination		= FALSE;
	public $mode 			= FALSE;
	public $waypoints		= FALSE;
	public $alternatives	= FALSE;
	public $avoid			= FALSE;
	public $units			= FALSE;
	public $region			= FALSE;
	
	public function __construct()
	{
		$this->base_url .= 'directions/'.$this->format;
	}
		
	/**
	 * Returns an a block of JavaScript code that will execute Asynchronously
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	
	public function javascript($params, $callback = NULL)
	{
		$options = $this->clean_js(json_encode($params['options']));
		
		$options = str_replace(":\"", ": ", $options);
		$options = str_replace("\",", ", ", $options);
		$options = str_replace("\"}", "}", $options);
		
		$options = str_replace('{"location": ', '{"location": "', $options);
		$options = str_replace(', "stopover":true}', '", "stopover":true}', $options);
		
		$js = '
			var request = '.$options.';
			
			'.$params['id'].'_directionsService.route(request, function(response, status) {
				
				if(status == google.maps.DirectionsStatus.OK) {
					'.$params['id'].'_directionsDisplay.setDirections(response);
				}
			});
		';
		
		return $js;
	}
	
	public function parse_response($response)
	{
		$js = 'var response = {};';
		
		foreach($response as $route)
		{
			var_dump($route);exit();
		}
	}
	
	/**
	 * Get directions
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	string
	 */
	
	public function query($origin, $destination, $params = array(), $return_url = FALSE)
	{
		$url = $this->construct_url(urlencode($origin), urlencode($destination), $params);
	
		if($return_url)
			return $url;
		
       	return json_decode($this->connect($url));
	}
	
	/**
	 * Constructs the URL from the object properties or by passing a unique URL
	 *
	 * @access	public
	 * @param	mixed
	 * @return	string
	 */
	
	public function construct_url($origin, $destination, $params = array(), $url = FALSE)
	{
		foreach($params as $param => $value)
		{
			$this->$param = $value;
		}
		
		$this->origin 		= $origin;
		$this->destination 	= $destination;
		
		foreach($params as $param => $value)
			$this->$param = $value;
		
		$this->url = !$url ? rtrim($this->base_url, '/') : $url;
		
		if($this->secure)
			$this->url = str_replace('http://', 'https://', $this->url);
		
		$this->url .= $this->sensor 		? '?sensor=true' : '?&sensor=false';
		
		$this->url .= $this->origin			? '&origin='		. $this->origin			: NULL;
		$this->url .= $this->destination	? '&destination='	. $this->destination	: NULL;
		$this->url .= $this->mode			? '&mode='			. $this->mode			: NULL;
		$this->url .= $this->waypoints		? '&waypoints='		. $this->waypoints		: NULL;
		$this->url .= $this->alternatives	? '&alternatives='	. $this->alternatives	: NULL;
		$this->url .= $this->avoid			? '&avoid='			. $this->avoid			: NULL;
		$this->url .= $this->units			? '&units='			. $this->units			: NULL;
		$this->url .= $this->region			? '&region='		. $this->region			: NULL;
		
		return $this->url;
	}
	
}