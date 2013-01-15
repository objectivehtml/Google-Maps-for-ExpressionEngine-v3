<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kml_api {
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	public function db_response($data, $geofield = 'geometry', $insert_tags = FALSE)
	{
		if(is_object($data) && get_class($data) == 'CI_DB_mysql_result')
		{
			$data = $data->result();
		}
		
		$kml = array();
		
		if(!is_array($data))
		{
			$data = array($data);
		}
		
		foreach($data as $index => $row)
		{
			$row = (object) $row;
			
			if(isset($row->$geofield))
			{
				$kml[] = $row->$geofield;
			}
		}
		
		return $this->response(implode("\n\r", $kml), $insert_tags);
	}
	
	public function prep_string($string, $options = array())
	{
		$options = array_merge(array(
			'color_tags' => FALSE,
			'doc_tags'   => FALSE,
			'style_url'  => FALSE,
			'name' 		 => FALSE,
			'styles'	 => array(
				'fillopacity'   => FALSE,
				'fillcolor'     => FALSE,
				'strokeopacity' => FALSE,
				'strokeweight'  => FALSE,
				'strokecolor'   => FALSE,
			)
		), $options);
		
		if(!preg_match("/<placemark>/ui", $string))
		{
			$string = '<Placemark>' . $string . '</Placemark>';
		}
		
		return trim(preg_replace('/>(\s|\n|\r|\t)*</um', '><', $this->trim($string)));
	}
	
	public function trim($string)
	{
		return preg_replace("/(\t|\n|\r )*/um", '', $string);
	}
	
	public function response($data, $insert_tags = FALSE)
	{
		$open_tags  = NULL;
	    $close_tags = NULL;
		
		if($insert_tags)
		{
			$open_tags   = '<?xml version="1.0" encoding="UTF-8"?>';
			$open_tags  .= NL.'<kml xmlns="http://www.opengis.net/kml/2.2">';
			$open_tags  .= NL.'<Document>';
			$open_tags  .= NL.'<Placemark>';
			
			$close_tags  = NL.'</Placemark>';
			$close_tags .= NL.'</Document>';
			$close_tags .= NL.'</kml>';
			
			$data = $open_tags . $data . $close_tags;
		}
		
		header('Content-type: application/xml');
		echo $data;	
		exit();	
	}
	
}