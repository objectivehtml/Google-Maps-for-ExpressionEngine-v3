<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class GeolocationButton extends PhotoFrameButton {
	
	public function javascript()
	{
		return array('https://maps.google.com/maps/api/js?sensor=true');
	}
	
	public function css()
	{
		$EE =& get_instance();
		$EE->cp->add_js_script(
		    array(
		        'ui'      => array('slider'),
		    )
		);
		
		return array();
	}
	
}