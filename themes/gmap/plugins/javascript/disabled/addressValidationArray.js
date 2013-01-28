/**
 * Address Validation Array (Multiple Maps)
 *
 * This plugin allows users to plot their location using standard text
 * fields and use the map as an address validator with multiple maps
 * on the same page. The other address plugin is the same thing, just
 * simplified.
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	JavaScript API
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps/api/plugins
 * @version		1.0
 * @build		20120223
 */
 
 /**
  * Instructions
  *
  * 1. Change the fieldName in that is used to reconcile the fieldtype
  * 
  * 2. Change the requiredFields and optionalFields to represent
  *    your field names.
  */
 
 $(document).ready(function() {
	
	// Loops through each global Gmap object	
	$.each(GmapGlobal.object, function(i , Gmap) {
		
		// Change this to your desired field name or remove the if statement
		// all together if you want to apply to all the Gmap fields
		var fieldNames = ['venue_location'];
		
		//Define the required fields
		var fieldSet = {
			//Field Name
			'venue_location': {
				'zoom'  : 15,
				'fields': [
					//Required Fields
					'textarea[name="venue_address"]',
					'input[name="venue_city"]',
					'select[name="venue_province"]',
					'input[name="venue_postal_code"]'
				]
			}
		}
		 
		$.each(fieldNames, function(i, fieldName) {
		
			if(Gmap.settings.field_name == fieldName) {
				if(Gmap.safecracker) {
					Gmap.bind('gmapInit', function(Gmap) {
									
						// The required address fields
						var requiredFields = fieldSet[fieldName].fields;
						var zoom 		   = fieldSet[fieldName].zoom;
						
						// Checks fields to see if they are empty
						function areFieldsEmpty() {
							var isEmpty = false;
							
							$.each(requiredFields, function(i, field) {
						
								var $t = $(field);
								var val = $t.val();
								
								if(val == "") {
									isEmpty = true;
								}
							});
							
							return isEmpty;
						}
						
						// Compiles the address string from the required and
						// optional fields.
						
						function compileAddress() {
							var address = '';
							
							$.each(requiredFields, function(i, field) {
						
								var $t = $(field);
								var val = $t.val();
								
								address += val + ' ';
							});
							
							return address.trim();				
						}
						
						// Performs the actual plotting action
						function bind() {
							
							if(!areFieldsEmpty()) {
								var location = compileAddress();
								
								if(Gmap.response.markers.total > 0) {
									Gmap.removeMarker('markers', Gmap.response.markers.results.length - 1);
								}
								
								// Geocodes the address
								Gmap.geocode(location, function(results, status) {
									var results = results[0];
									var lat		= results.geometry.location.lat();
									var lng		= results.geometry.location.lng();
									var latlng  = new google.maps.LatLng(lat, lng);
									
									// Saves the response and adds marker to map
									results = Gmap.saveResponse(results, lat, lng);
									Gmap.addMarker(results, false, false);
									
									// Sets the zoom and the center
									Gmap.map.setZoom(zoom);
									Gmap.map.setCenter(latlng);
								});
							}
						}
						
						var allFields = [];
						
						// Loop through all the fields and bind the plotting action to
						// all of each field individually.
						
						$.each(requiredFields, function(i, field) {
							$(field).bind('blur', function() {
								bind();
							});
						});
						
						// Call the plotting action once in case the user has auto-
						// populated forms
									
						bind();	
					});
				}
			}
		});	
	
	});
	
});