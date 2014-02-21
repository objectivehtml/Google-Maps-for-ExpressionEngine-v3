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
	
	public function postSave($save_photo, $orig_photo)
	{
		//$manipulations = json_decode($save_photo['manipulations']);
		
		if(isset($manipulations->geolocation))
		{
			$save_photo['lat'] = $manipulations->geolocation->data->lat;
			$save_photo['lng'] = $manipulations->geolocation->data->lng;
		}
		
		return $save_photo;
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