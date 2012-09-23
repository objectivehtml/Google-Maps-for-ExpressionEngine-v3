<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'gmap/libraries/Gmap_curl.php';

/**
 * Base Google API Class
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.0
 * @build		20120103
 */

abstract class Google_api extends Gmap_curl {

	public $base_url		= 'http://maps.googleapis.com/maps/api/';
	public $format			= 'json';
	public $language 		= 'en';
	public $last_response 	= FALSE;
	public $last_query 		= FALSE;
	public $responses		= array();
	public $secure			= TRUE;
	public $sensor 			= TRUE;
		
	protected function connect($url)
	{	       	
       	$this->create($url);
       	$this->ssl(FALSE);
		$response = $this->execute();
		       	
       	$this->last_response 	= $response;
       	$this->last_query		= $url;
       	$this->responses[] 		= $response;
       	
       	return $response;
	}
	
	public function clean_js($str)
	{
		$str = trim($str);
		$str = preg_replace("/[\n\r\t]/", '', $str);
		$str = str_replace("'", "\'", $str);
		
		return $str;
	}
}