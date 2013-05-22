/**
 * Google Maps for ExpressionEngine
 *
 * This file controls all the JavaScript for the settings page.
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Javascript
 * @category	Settings
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/libraries/channel_data
 * @version		2.0
 * @build		20120416
 */

var GmapPreview = {};

GmapPreview.validate = function(markerMode, waypointMode, regionMode) {
	
	if(markerMode == 'no' && waypointMode == 'no' && regionMode == 'no') {
		alert('Error: You must enable at least the Marker Mode before continuing.\n\rYou need to select a mode to tell the fieldtype what the types of data to accept, and at least one Mode is required.');
		return false;
	}
	
	return true;
}

GmapPreview.init = function() {

	google.maps.visualRefresh = true;

	var canvas 	= $('#gmap_canvas').get(0);
	var lat		= $('#gmap_latitude').val();
	var lng 	= $('#gmap_longitude').val();
	var zoom 	= parseInt($('#gmap_zoom').val());
	var defer	= $('#gmap_defer_init').val();
	var options = { scrollwheel: true }
	var markers = [];
	var start	= '';
	var end		= '';
	var directionsService = new google.maps.DirectionsService();
	var directionsDisplay = new google.maps.DirectionsRenderer();
	var	initiated = false;
	
	$('#ft_gmap').css('position', 'relative');
	
	function route(gmap) {

		var request = {
	        origin: start, 
	        destination: end,
	        waypoints: [],
	        optimizeWaypoints: true,
	        travelMode: google.maps.DirectionsTravelMode.DRIVING
	    };
	    
		directionsDisplay.setMap(gmap);

	    directionsService.route(request, function(response, status) {
			if (status == google.maps.DirectionsStatus.OK) {
				directionsDisplay.setDirections(response);
			}
	    });
	}
	
	function reset() {
		initiated = false;
		
		$("#gmap_latitude, #gmap_longitude").unbind('blur');
		$("#gmap_zoom").unbind('blur');
		$("#gmap_map_height").unbind('blur');
		$('.gmap-container').unbind('keypress');
		$('.gmap-container button').unbind('click');
		
		markers = [];
	}
	
	function init() {
		if(!initiated) {
			Gmap.init(canvas, lat, lng, options, function(gmap) {
				
				lat	= $('#gmap_latitude').val();
				lng = $('#gmap_longitude').val();
				
				Gmap.center(gmap, lat, lng);
				gmap.setZoom(zoom);
				
				google.maps.event.addListener(gmap, 'center_changed', function() {
					var center = gmap.getCenter();
					$("#gmap_latitude").val(center.lat());
					$("#gmap_longitude").val(center.lng());
				});
				
				google.maps.event.addListener(gmap, 'zoom_changed', function() {
					var zoom = gmap.getZoom();
					$("#gmap_zoom").val(zoom);
				});
								
				$("#gmap_latitude, #gmap_longitude").blur(function() {
					var lat = parseFloat($("#gmap_latitude").val());
					var lng = parseFloat($("#gmap_longitude").val());
					
					if(!isNaN(lat) && !isNaN(lat)) {
						var new_location = new google.maps.LatLng(lat, lng);
					
						gmap.setCenter(new_location);
						
					} else {
						alert("Invalid latitude or longitude: "+lat+", "+lng);
					}
				});
				
				$("#gmap_zoom").blur(function() {
					var zoom = parseInt($("#gmap_zoom").val());
					
					if(zoom <= 20 && zoom >= 0) {
						gmap.setZoom(zoom);
					} else {
						alert("Invalid zoom: "+zoom+". The zoom must be between 0 and 20.");
					}
				});
				
				$("#gmap_map_height").blur(function() {
					$("#gmap_wrapper").css("height", $(this).val());
				});
				
				
				//if($('#gmap_waypoint_mode select').val() == 'no') {
					
					markers[markers.length] = Gmap.marker(gmap, lat, lng);
					$('.gmap-flyout').hide();
				/*}
				else {
					Gmap.center(gmap, lat, lng);
					
					$('.gmap-flyout').show();
					$('.gmap-container input').keypress(function(event) {
						if(event.keyCode == 13) {
							route(gmap);
							return false;
						}
						
						return true;
					});
				
					$('.gmap-container button').click(function() {
						start = $('.gmap-container #start').val();
						end	  = $('.gmap-container #end').val();
						
						if(start != '' && end != '') {
							route(gmap);
						}
					});
					
					$('.gmap-flyout .close').click(function() {
						$(this).parent().hide();
						
						return false;
					});
					
				}*/
			});
		}
	}
	
	$('.gmap .deferer').live('click', function() {
		init();		
	});
	
	$('#gmap_defer_init').change(function() {
		var val = $(this).val();
		
		if(val == 'yes')
			$('#gmap_canvas').html('<div class="deferer"><p>Click to Activate</p></div>');
		else {
			if($('.gmap .deferer').length)
				$('.gmap .deferer').click();
		}
	});

	$('#gmap_scroll_wheel').change(function() {
		var $t 	= $(this);
		var val = $t.val();
		var deferer = $('.deferer').length;
		
		if(val == 'yes')
			options.scrollwheel = true;
		else
			options.scrollwheel = false;
					
		if(deferer == 0) {
			reset();
			init();
		}
	});
	
	$('#gmap_waypoint_mode').change(function() {
		var val = $('#field_type').val();
		
		if(val == 'gmap' && defer != 'yes') {
			reset();
			init();
		}
	});
	
	$('.onchange select').change(function() {
		var $t 	= $(this);
		var val = $t.val();
		var id	= $t.attr('id');
		var obj = $('.'+id);
		
		if(val == 'yes')
			obj.show();
		else
			obj.hide();			
	});
	
	$('.onchange select').change();
	
	// 
	if (typeof fieldEditor !== 'undefined') {
		if (defer != 'yes') {
			init();
		}
		fieldEditor.addCustomSave('gmap', function(form) {
			var markerMode = form.find('select[name="gmap_marker_mode"]').val();
			var waypointMode = form.find('select[name="gmap_waypoint_mode"]').val();
			var regionMode = form.find('select[name="gmap_region_mode"]').val();
			return GmapPreview.validate(markerMode, waypointMode, regionMode);
		});
		return;
	}
	
	$('#field_type, select[name="variable_type"]').change(function() {
		var val = $(this).val();
		
		if(val == 'gmap' && defer != 'yes') {
			$('.gmap .deferer').click();
		}
	});
	
	// For low variables
	$('select[name="variable_type"]').change();
	
	$('form').submit(function() {
		
		var fieldType = $('select[name="field_type"]').val();
		var markerMode = $('select[name="gmap_marker_mode"]').val();
		var waypointMode = $('select[name="gmap_waypoint_mode"]').val();
		var regionMode = $('select[name="gmap_region_mode"]').val();
				
		return (fieldType == 'gmap') ? GmapPreview.validate(markerMode, waypointMode, regionMode) : true;
	});

};
