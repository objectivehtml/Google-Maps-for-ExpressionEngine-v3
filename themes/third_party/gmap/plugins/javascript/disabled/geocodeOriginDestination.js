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
 * @version		1.1
 * @build		20120115
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
	var fieldName     = 'load-map';
	var distanceField = 'input[name="distance-load"], input[name="field_id_24"]';
	
	//Default zoom (1-20)
	var defaultZoom = 15;
	var changed = false;
	
	if(Gmap.settings.field_name == fieldName) {
		if(Gmap.safecracker) {
		
			$(distanceField).keydown(function() {
				$(this).attr('data-user-defined', 'true');
			});
		
			Gmap.bind('gmapRouteStop', function(response) {
				if(changed) {
					var distance = response.routes[0].legs[0].distance.text;
					
					if(!$(distanceField).data('user-defined')) {
						$(distanceField).val(distance);
					}
				}
			});
			
			Gmap.bind('gmapInit', function(Gmap) {
				
				var requiredFields = {
					origin: {
						fields: [
							{
								selector: 'input[name="origin-address-load"], input[name="field_id_48"]',
								required: false
							},
							{
								selector: 'input[name="origin-city-load"], input[name="field_id_6"]',
								required: true
							},
							{
								selector: 'select[name="origin-state-load"], input[name="field_id_7"]',
								requied: true
							},
							{
								selector: 'input[name="origin-zipcode-load"], input[name="field_id_49"]',
								required: true
							}
						],
						latitude: 'input[name="latitude-load"], input[name="field_id_22"]',
						longitude: 'input[name="longitude-load"], input[name="field_id_23"]',
						label: '.origin.valid-address',
						active: false
					},
					destination: {
						fields: [
							{
								selector: 'input[name="destination-address-load"], input[name="field_id_50"]',
								required: false
							},
							{
								selector: 'input[name="destination-city-load"], input[name="field_id_9"]',
								required: true
							},
							{
								selector: 'select[name="destination-state-load"], input[name="field_id_10"]',
								requied: true
							},
							{
								selector: 'input[name="destination-zipcode-load"], input[name="field_id_51"]',
								required: true
							}
						],
						latitude: 'input[name="latitude-load-destination"], input[name="field_id_66"]',
						longitude: 'input[name="longitude-load-destination"], input[name="field_id_67"]',
						label: '.destination.valid-address',
						active: false
					}					
				};
				
				
				// Checks fields to see if they are empty
				function areFieldsEmpty(index) {
					var isEmpty = false;
					
					$.each(requiredFields[index].fields, function(i, field) {
						var $t = $(field.selector);
						var val = $t.val();
						
						if(val == "" && field.required) {
							isEmpty = true;
						}
					});
					
					return isEmpty;
				}
				
				function isOneFieldNotEmpty(index) {
					var isNotEmpty = false;
					
					$.each(requiredFields[index].fields, function(i, field) {
						var $t = $(field.selector);
						var val = $t.val();
						var tagName = $t.is('select');
						
						if(val != "" && !$t.is('select') && field.required) {
							isNotEmpty = true;
						}
					});
					
					
					
					return isNotEmpty;
				}
				
				
				// Compiles the address string from the required and
				// optional fields.
				
				function compileAddress(index) {
					var address = '';
					
					$.each(requiredFields[index].fields, function(i, field) {
				
						var $t = $(field.selector);
						var val = $t.val();
						
						address += val + ' ';
					});
					
					return address.trim();				
				}
				
				var count = 1;
				
				// Performs the actual plotting action
				function bind(index) {
					
					// var opposite = index == 'origin' ? 'destination' : index;
					
					if(!areFieldsEmpty(index)) {
					
						var location = compileAddress(index);
						
						Gmap.responseType = 'waypoints';
						Gmap.override = true;
						
						// Geocodes the address
						Gmap.geocode(location, function(results, status) {
							if(status == 'OK') {
								changed = true;
								
								if(requiredFields[index].active !== false) {	
									Gmap.removeMarker('waypoints', requiredFields[index].active);
								}	

								requiredFields[index].active = Gmap.response.waypoints.results.length;
								
								var lat = results[0].geometry.location.lat();
								var lng = results[0].geometry.location.lng();
								
								$(requiredFields[index].latitude).val(lat);
								$(requiredFields[index].longitude).val(lng);
										
								results = Gmap.saveResponse(results[0], lat, lng);																		
								Gmap.addMarker(results);
								Gmap.reorder();
							}
															
							var validStatus = 'Valid Address';
							var invalStatus = 'Invalid Address';
												
							status = status == 'OK' ? validStatus : invalStatus;
							
							$(requiredFields[index].label).addClass((status == validStatus ? 'valid' : 'invalid')).removeClass((status != validStatus ? 'valid' : 'invalid')).html(status);
		
						});
					}
					else {
						if(!isOneFieldNotEmpty(index)) {
							$(requiredFields[index].label).removeClass('valid').addClass('invalid').html('A valid address is required');
						}
						else {
							$(requiredFields[index].label).html('');
						}
					}
				}
				
								
				var count = 0;
				$.each(requiredFields, function(i, obj) {
					
					requiredFields[i].active = count;
					
					$.each(obj.fields, function(index, field) {
						$(field.selector).bind('blur', function() {
							bind(i);
						});						
					});		
					
						
					// bind(i);
					
					count++;				
				});
								
			});
		}
	}	
});
