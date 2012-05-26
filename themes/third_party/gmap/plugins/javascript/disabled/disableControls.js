/**
 * Disable Controls
 *
 * This plugins disables all the onscreen control within the fieldtype
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	JavaScript API
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps/api/plugins
 * @version		1.0
 * @build		20120115
 */
 
$(document).ready(function() {
	
	var Gmap = GmapGlobal.object[0];
	
	var fieldName = 'member_location';
	
	if(Gmap.settings.field_name == fieldName) {
		Gmap.bind('gmapInit', function(Gmap) {
			
			//Add a custom class to the Gmap wrapper
			Gmap.ui.wrapper.addClass('gmap');
			
			//Hides the geocoder
			Gmap.ui.geocoder.parent().hide();
			
			//Disable the default UI
			Gmap.map.setOptions({
				disableDefaultUI: true
			});
		});
	}	
});