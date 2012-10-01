/**
 * Disable Controls Array
 *
 * This plugins disables all the onscreen control with multiple maps
 * on the same page (in same channel).
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	JavaScript API
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps/api/plugins
 * @version		1.0
 * @build		20120223
 */
 
$(document).ready(function() {
	
	// Loops through each global Gmap object	
	$.each(GmapGlobal.object, function(i, Gmap) {
	
		// Define all the field names in an array
		var fieldNames = ['request_location', 'request_recipient_location'];
		
		// Loop through the fields
		$.each(fieldNames, function(i, fieldName) {
			//If the field names matches the fieldtype's
			if(Gmap.settings.field_name == fieldName) {
				
				//Bind the gmapInit event
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
	})
});