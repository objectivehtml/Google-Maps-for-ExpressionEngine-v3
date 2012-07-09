/**
 * Address Components
 *
 * This plugins parses individual address components and inserts
 * the values in the user defined fields.
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	JavaScript API
 * @author		Objective HTML
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps/api/plugins
 * @version		1.0.1
 * @build		20120709
 */
 
$(document).ready(function() {
	
	var Gmap = GmapGlobal.object[0];
	
	/*=============================*
	 * Enter your field's name here
	 *=============================*/
	 
	var fieldId = 77;
		 
	/**
	 * Template Instructions 
	 *
	 * The following template is an example of all possibilities. Obviously,
	 * you should remove that you don't need.
	 *
	 * The type will be matched against the address type in the response.
	 * If the two types match, the value is inserted into the defined field.
	 *
	 * Any number of combinations may be used. Be wary of performance issues.
	 * The more fields you add to the template, the longer the loops takes to
	 * process.
	 *
	 *
	 * If not fill variable is present, the short_name will be used.
	 *
	 */
	 
	var template  = [{
		type: 'administrative_area_level_1',
		field: 'input[name="field_id_82"]',
		fill: 'short_name' // Alternatively it could be 'long_name'
	}];
	
	/*====================*
	 *  Component Parser  *
	 *====================*/
	 
	function parseAddressComponents(response) {
		
		// Loop through address components for only first result
		$.each(response.address_components, function(i, component) {
			
			// Loop through each component type				
			$.each(component.types, function(i, type) {
				
				// Loop through the user defined template variables					
				$.each(template, function(i, match) {
					
					// Match the component types to template
					if(type == match.type) {
						if (match.fill == 'short_name') {
							$(match.field).val(component.short_name);
						}
						else {
							$(match.field).val(component.long_name);
						}
						
					}
					
				});
				
			});
			
		});
		
	}
	
	/*=========*
	 *  Event  *
	 *=========*/
	
	if(Gmap.settings.field_id == fieldId) {
		Gmap.bind('gmapGeocodeStop', function(response, status, Gmap) {
			//Parse the first returned response.
			parseAddressComponents(response[0]);				
		});
	}
	
});