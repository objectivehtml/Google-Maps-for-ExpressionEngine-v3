<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class GeolocationButton extends PhotoFrameButton {
	
	public $name = 'Geolocation'; 
	
	public $moduleName = 'gmap'; 
	
	public function modifyTables($tables)
	{    
		$tables['photo_frame']['lat'] = array(
			'type' => 'double'
		);
		
		$tables['photo_frame']['lng'] = array(
			'type' => 'double'
		);
		
		return $tables;
	}
	
	public function postSave($photo)
	{
		$manipulations = json_decode($photo['manipulations']);
		
		if(isset($manipulations->geolocation))
		{
			$photo['lat'] = $manipulations->geolocation->data->lat;
			$photo['lng'] = $manipulations->geolocation->data->lng;
		}
		
		return $photo;
	}
	
	public function javascript()
	{
		$ee =& get_instance();
			
		return array(
			'geolocation',
			'https://maps.google.com/maps/api/js?sensor=true',
			$ee->theme_loader->theme_url() . 'gmap/javascript/geolocationmarker.js'
		);
	}
	
}