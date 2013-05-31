/**
 * Address Validation
 *
 * This plugin allows users to plot their location using standard text
 * fields and use the map as an address validator.
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	JavaScript API
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps/api/plugins
 * @version		1.1.1
 * @build		20130557
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
	
	var Gmap = GmapGlobal.object[0];
	
	// Change this to your desired field name or remove the if statement
	// all together if you want to apply to all the Gmap fields
	var fieldName = 'member_location';
	
	//Default zoom (1-20)
	var defaultZoom = 15;
	
	if(Gmap.settings.field_name == fieldName) {
		if(Gmap.safecracker) {
			Gmap.bind('gmapInit', function(Gmap) {
				
				// The required address fields
				var requiredFields = [
					'input[name="member_address"], input[name="field_id_63"]',
					'input[name="member_city"], input[name="field_id_66"]',
					'input[name="member_postal_code"], input[name="field_id_68"]'
				];
				
				// The optional address fields
				var optionalFields = [
					'input[name="member_state"], input[name="field_id_67"]',
					'input[name="member_country"], input[name="field_id_69"]'
				];
				
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
								
							if(Gmap.response.markers.total == 0) {
								// Saves the response and adds marker to map
								results = Gmap.saveResponse(results, lat, lng);
								Gmap.addMarker(results, false, false);
							}
							
							// Sets the zoom and the center
							Gmap.map.setZoom(defaultZoom);
							Gmap.map.setCenter(latlng);
						});
					}
				}
				
				var allFields = [];
				
				// Merge the requiredFields and optionalFields into one array
				$.merge(allFields, requiredFields, optionalFields);
				
				// Loop through all the fields and bind the plotting action to
				// all of each field individually.
				
				$.each(allFields, function(i, field) {
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
