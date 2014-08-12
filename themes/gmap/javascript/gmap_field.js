/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Javascript
 * @category	Fieldtype
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.3.0
 * @build		20120522
 */
 
 
var Gmap  = function($wrapper, options) {
	
	google.maps.visualRefresh = true;
	
	var $t            = $wrapper;
	var $gmap         = $t;
	var id            = parseInt($t.attr('data-gmap-id'));
	var icons         = options.icons;
	var savedResponse = options.response;
	
	var t = {
		bounds: {},
		colors: [
			{
				stroke: '#006200',
				fill:	'#003D00'
			},
			{
				stroke: '#750001',
				fill:	'#B20001'
			},
			{
				stroke: '#FFFF00',
				fill:	'#9B9B00'
			},
			{
				stroke: '#04009B',
				fill:	'#0600FF'
			}
		],
		center:	{},
		directionsService: new google.maps.DirectionsService(),
		directionsRenderer: new google.maps.DirectionsRenderer(),
		editRegion: false,
		field:	options.field,
		fields:	options.fields,
		geocoder: new google.maps.Geocoder(),
		icons: false,
		isLoaded: false,
		isNewEntry: savedResponse == [] ? true : false,
		bounds: new google.maps.LatLngBounds(),
		lastColor: '',
		lastRegion: false,
		map: {},
		markers: [],
		newRegion: false,
		override: false,
		overlimit: {
			markers: false,
			route: false,
			regions: false
		},
		plugins: options.plugins,
		polyInfoWindow: false,
		results: [],
		region: [],
		response: {
			markers: {
				total: 0,
				results: []
			},
			waypoints: {
				total: 0,
				route: [],
				results: []
			},
			regions: {
				total: 0,
				results: []
			}
		},
		reqFields: options.reqFields,
		responseType: '',
		safecracker: options.safecracker,
		settings: options.settings,
		steps: {
			markers: 1,
			waypoints: 1,
			regions: 1
		},
		totalPoints: 0,
		ui:	{
			button: $t.find('button.submit'),
			closeButtons: $t.find('.close'),
			canvas: $t.find('.canvas'),
			deferer: $t.find('.deferer'),
			deleteButton: $t.find('.delete'),
			deletePoint: $t.find('.delete-point'),
			deleteRegion: $t.find('.delete-region'),
			deleteMarker: $t.find('.delete-marker'),
			editMarker: $t.find('.edit-marker'),
			editRegion: $t.find('.edit-region'),
			geocoder: $t.find('.geocoder input'),
			input: $t.find('.gmap-output'),
			importPanel: $t.find('.gmap-import-panel'),
			upload: $t.find('.gmap-upload'),
			lists: $t.find('.lists ul'),
			markerPanel: $t.find('.marker.side-panel'),
			markerCancelButton: $t.find('.marker.side-panel button.cancel-marker'),
			markerSaveButton: $t.find('.marker.side-panel button.save-marker'),
			markerName: $t.find('input[name="marker-title"]'),
			sideContentPanel: $t.find('.side-content-panel'),
			sideContentText: $t.find('.side-content-panel textarea'),
			sideContent: $t.find('input[name="side-content"]'),
			regionPanel: $t.find('.region.side-panel'),
			regionCancelButton: $t.find('.region.side-panel button.cancel-region'),
			regionSaveButton: $t.find('.region.side-panel button.save-region'),
			regionName: $t.find('input[name="region-name"]'),
			regionContent: $t.find('input[name="side-content"]'),
			strokeColor: $t.find('input[name="region-stroke-color"]'),
			strokeOpacity: $t.find('select[name="region-stroke-opacity"]'),
			strokeWeight: $t.find('select[name="region-stroke-weight"]'),
			fillColor: $t.find('input[name="region-fill-color"]'),
			fillOpacity: $t.find('select[name="region-fill-opacity"]'),
			showStatistics: $t.find('.show-statistics'),
			suggestions: $t.find('.suggestions'),
			suggestion: $t.find('.suggestions a'),
			suggestionStatistics: $t.find('.suggestions .statistics'),
			toggle: $t.find('.toggle a'),
			wrapper: $t
		},
		windows: [],
		
		bind: function(event, callback) {
			if(typeof data == "function") {
				callback = data;
				data = {};
			}
			
			$t.bind(event, function(data, param1, param2, param3, param4, param5) {
				callback(param1, param2, param3, param4, param5);
			});
		},
		
		getBounds: function() {

			var newBounds = new google.maps.LatLngBounds();

			$.each(t.response.markers.results, function(i, marker) {
				if(marker) {
					var lat    = parseInt(marker.geometry.location.lat);
					var lng    = parseInt(marker.geometry.location.lng);
					var latLng = new google.maps.LatLng(lat, lng);
					
					newBounds.extend(latLng);
				}
			});

			$.each(t.response.waypoints.results, function(i, waypoint) {
				if(waypoint) {
					var lat    = parseInt(waypoint.geometry.location.lat);
					var lng    = parseInt(waypoint.geometry.location.lng);
					var latLng = new google.maps.LatLng(lat, lng);
					newBounds.extend(latLng);
				}
			});

			$.each(t.response.regions.results, function(i, region) {
				if(region) {
					$.each(region.saveData.coords, function(i, coord) {
						var LatLng = new google.maps.LatLng(coord.lat, coord.lng);
						newBounds.extend(LatLng);
					});
					/*var latLng = new google.maps.LatLng(waypoint.lat, waypoint.lng);
					newBounds.extend(latLng);*/
				}
			});

			return newBounds;
		},

		addMarker: function(response, resize, open, callback) {
			
			if(typeof open == "function") {
				callback = open;
				open = true;
			}
			
			if(typeof resize == "function") {
				callback = resize;
				resize = true;
			}
			else if(resize == undefined) {
				resize = true;
			}
						
			if(typeof response.geometry != "undefined") {
				
				/* Convert the function to a storable integer */
				if(typeof response.geometry.location.lat == "function")
					response.geometry.location.lat = response.geometry.location.lat();
				
				if(typeof response.geometry.location.lng == "function")
					response.geometry.location.lng = response.geometry.location.lng();
							
				var lat = response.geometry.location.lat;
				var lng = response.geometry.location.lng;

				var options = {
					draggable: true,
					position: new google.maps.LatLng(lat, lng),
					map: t.map,
					title: '',
					zIndex: 1000
				};
				
				if(response.icon)
					options.icon = response.icon;
				
				if(t.bounds) {
					t.bounds.extend(options.position);
				}				
			}
		
			t.ui.markerPanel.fadeOut();
			
			if(t.responseType == 'markers') {
				
				if(!t.overlimit.markers) {

					if(t.editMarker) {
						t.ui.markerCancelButton.click();
						t.editMarker = false;
					}

					var marker 		= new google.maps.Marker(options);
					var markerObj	= marker;
					var index 		= t.response.markers.results.length-1;
					
					marker.index 	= index;
					
					var title		= response.title ? response.title : '';
					
					marker.title 	= title;
					marker.content  = response.content ? response.content : '';
					
					var content 	= response.content ? t.buildInfoWindow(index, false, response.content) :
									  t.buildInfoWindow(index, response);
					
					var infoWindow  = t.window(marker, content, open);
					
					t.markers[t.markers.length] 	= marker;
					
					t.windows[t.windows.length]	= infoWindow;
					
					if(t.bounds && resize) {
						if(t.settings.zoom_one_marker && parseInt(t.settings.zoom_one_marker) > 0 && t.response.markers.total == 1) {
								for(var i = 0; i < t.response.markers.results.length; i++) {
									var centerMarker = t.response.markers.results[i];

									if(centerMarker) {
										var lat = centerMarker.geometry.location.lat;
										var lng = centerMarker.geometry.location.lng;
										var pos = new google.maps.LatLng(lat, lng);

										t.map.setCenter(pos);
										t.map.setZoom(parseInt(t.settings.zoom_one_marker));
										break;
									}
								}
							
						}
						else {
							t.map.fitBounds(t.bounds);
						}
					}

					//t.showMarkerPanel(response);
					
					$t.trigger('gmapAddMarker', [marker.index, response, infoWindow, t]);
					
					google.maps.event.addListener(markerObj, 'dragend', function(event) {
						var lat = event.latLng.lat();
						var lng	= event.latLng.lng();
															
						t.geocode(lat+','+lng, function(response) {
							
							response = response[0];
							
							t.updateMarkerFields(response, lat, lng);
							
							response.geometry.location.lat = lat;
							response.geometry.location.lng = lng;
							response.icon = marker.icon ? marker.icon : '';
							response.title = marker.title ? marker.title : '';
							response.content = marker.content ? marker.content : '';
							
							t.response.markers.results[marker.index] = response;
							
							t.ui.markerPanel.find('.address').html(response.formatted_address);
							
							if(!t.markers[index].content) {
								var content = t.buildInfoWindow(index, response);
								infoWindow.setContent(content);
								
								marker.title   = '';	
								marker.content = '';
							}
							
							//t.showMarkerPanel(response);
					
							$t.trigger('gmapMarkerDragEnd', [index, response, infoWindow, t]);
					
							t.refresh(t.response.markers.results, 'markers');
					
						});
					});
				}
			}
			else if(t.responseType == 'waypoints') {
				
				if(t.bounds && t.response.waypoints.total > 1)
					t.map.fitBounds(t.bounds);
				
				t.route();
			}
			else if(t.responseType == 'regions') {
				
				t.showRegionPanel(response);
			
				t.addPoint(response);	
			}
			
			//t.bounds = t.map.getBounds();
							
			if(typeof callback == "function")
				callback(marker, lat, lng, options);
			
			
			//return marker;
		},
		
		addPoint: function(response, resize, index) {
			var index = t.region.length;
			var newRecord = t.region.length == 0 ? true : false;
			
			if(response) {				
				if(response.geometry != undefined)
				{
					var lat = response.geometry.location.lat;
					var lng = response.geometry.location.lng;
					
				}
				else if(response.lat && response.lng)
				{
					var lat = response.lat;
					var lng = response.lng;
				}
			}
			else
			{
				return;
			}
			
			options = {
				draggable: true,
				position: new google.maps.LatLng(lat, lng),
				map: t.map,
				title: response.title,
				icon: {
					url: t.settings.theme_url+'/gmap/css/images/marker_orange.png',
					size: new google.maps.Size(22, 31),
					origin: new google.maps.Point(-3, -17)
				}
			};
			
			var content 		= 'Are you sure you want to <a href="#" data-index="'+index+'" class="delete-point">Delete</a>?';
			var marker 			= new google.maps.Marker(options);
		
			marker.index 		= index;
			marker.lat 			= lat;
			marker.lng		 	= lng;
			
			t.region[index] = marker;
			t.totalPoints++;
			t.ui.regionPanel.find('li:first-child').html('<p>Total Points: '+t.totalPoints+'</p>');
			
			var infoWindow  = t.window(marker, content, false);
			
			t.windows.push(infoWindow);
			
			if(resize)	t.map.fitBounds(t.bounds);

			$t.trigger('gmapAddPoint', [response, infoWindow, t]);
			
			google.maps.event.addListener(marker, 'dragend', function(event) {
				var lat = event.latLng.lat();
				var lng	= event.latLng.lng();
				
				t.region[marker.index].lat = lat;
				t.region[marker.index].lng = lng;
				
				$t.trigger('gmapPointDragEnd', [event, marker, t]);
				
				t.renderPolygon();
			});
			
			t.renderPolygon();
		},
		
		renderPolygon: function() {
			
			var coords = [];
			
			$.each(t.region, function(i, point) {
				if(point !== false) {
					coords.push(new google.maps.LatLng(point.lat, point.lng));
				}
			});
		
			var polygon = {
				map: t.map,
				paths: coords,
				strokeColor: t.ui.strokeColor.val(),
				strokeOpacity: parseFloat(t.ui.strokeOpacity.val()),
				strokeWeight: parseFloat(t.ui.strokeWeight.val()),
				fillColor: t.ui.fillColor.val(),
				fillOpacity: parseFloat(t.ui.fillOpacity.val())
			}
			
			if(t.newRegion) { 
				t.newRegion.setMap(null);
			}
			
			t.newRegion = new google.maps.Polygon(polygon);

			google.maps.event.clearListeners(t.newRegion, 'dblclick');
			google.maps.event.addListener(t.newRegion, 'dblclick', function(e) {
				google.maps.event.trigger(t.map, 'dblclick', e);
			});	
		},
		
		addRegion: function(response) {
		
			var index = t.editRegion !== false ? t.editRegion : t.response.regions.results.length;
						
			if(!response) {
				var coords = [];
				var count  = 0;

				var response = {
					title: t.ui.regionName.val() ? t.ui.regionName.val() : 'Region '+(index+1),
					content: t.ui.sideContentText.val(),
					total: 0,
					coords: []
				}
				
				$.each(t.region, function(i, data) {
					if(data) {
						response.coords[count] = {lat: data.lat, lng:data.lng};
						
						var latlng = new google.maps.LatLng(data.lat, data.lng);
						
						coords.push(latlng);							
						t.bounds.extend(latlng);
						
						count++;
					}
				});
				
				response.total = coords.length;
				
				response.style = {
					paths: coords,
					strokeColor: t.ui.strokeColor.val(),
					strokeOpacity: parseFloat(t.ui.strokeOpacity.val()),
					strokeWeight: parseFloat(t.ui.strokeWeight.val()),
					fillColor: t.ui.fillColor.val(),
					fillOpacity: parseFloat(t.ui.fillOpacity.val())
				}
				
				var polygon = response.style;
			}
			else {
				var polygon = response.style;
			}
			
			t.response.regions.results[index] = new google.maps.Polygon(polygon);
			
			t.response.regions.total++;
							
			t.totalPoints = 0;
				
			$t.trigger('gmapAddRegion', [response, t]);
				
			google.maps.event.addListener(t.response.regions.results[index], 'click', function(event) {
				if(t.region.length == 0) {
					var options = {
						position: event.latLng,
						content: t.buildInfoWindow(index, false, response.content, 'region')
					};
					
					if(t.polyInfoWindow) t.polyInfoWindow.close();
					
					t.polyInfoWindow = new google.maps.InfoWindow(options);
					
					t.polyInfoWindow.open(t.map);
					
					$t.trigger('gmapRegionClick', [event, t.response.regions.results[index], t.polyInfoWindow, t]);
				}
			});

			google.maps.event.addListener(t.response.regions.results[index], 'dblclick', function(event) {
				if(t.region.length > 0) {
					google.maps.event.trigger(t.map, 'dblclick', event);
				}
			});
			
			t.response.regions.results[index].saveData = response;
			t.response.regions.results[index].setMap(t.map);
			
			$.each(t.region, function(i, data) {
				if(data.setMap) {
					data.setMap(null);
				}
			});
			
			t.region = [];
			t.ui.regionPanel.hide();
			
			t.map.fitBounds(t.bounds);
			t.refresh(t.response.regions.results);
			t.editRegion = false;
			
			if(t.newRegion) {
				t.newRegion.setMap(null);
			}
		},
		
		buildInfoWindow: function(index, data, content, type) {
			
			if(!type) {
				var type = 'marker';
			} 
			
			if(!content && data) {
				var lat		= data.geometry.location.lat;
				var lng		= data.geometry.location.lng;
				var content = '';
				
				$.each(data.formatted_address.split(','), function(i, value) {
					content += value + '<br>';
				});		
					
				content += lat+','+lng+'<br>';		
			}
			
			content += [
				'<div class="actions">',
					'<a href="#" class="edit-'+type+'" data-type="'+t.responseType+'" data-index="'+index+'" id="edit-'+type+'-'+index+'">Edit</a> | ',
					'<a href="#" class="delete-'+type+'" data-type="'+t.responseType+'" data-index="'+index+'" id="delete-'+type+'-'+index+'">Delete</a>',
				'</div>'
			].join(' ');
			
			return content;
		},
		
		/*showHelp: function(target, options) {
			
			$('.ui-tooltip').qtip('destroy');
			
			if(t.settings.display_help == 'yes') {		
				
				var defaultOptions = {
					position: {
						my: 'top left',
						at: 'bottom left'	
					},
					style: {
						classes: 'ui-tooltip-green'
					},
					hide: {
						event: 'click'
					},
					show: {
						ready: true
					}
				}
				
				options = $.extend(true, defaultOptions, options);
				
				if(typeof target == "object")
					target.qtip(options);
			}
			
		},
		*/
		
		init: function(options, callback) {
			
		    if(typeof options == "function") {
		    	callback = options;
		    	options = {};
		    }
		    
		    var lat		= t.settings.latitude  ? parseFloat(t.settings.latitude)  : 0;
		    var lng		= t.settings.longitude ? parseFloat(t.settings.longitude) : 0;
			var zoom 	= t.settings.zoom 	  ? t.settings.zoom 					: 0;
			
		    var latlng 	= new google.maps.LatLng(lat, lng);
		    	
		    if(t.settings.zoom_field != "") {
				var fieldZoom = $('#'+t.settings.zoom_field).val();
				
				if(fieldZoom != "" && !isNaN(fieldZoom)) {
					zoom = fieldZoom;
				}
		    }
		    
		    var defaultOptions = {
		      	zoom		: parseInt(zoom),
		      	center		: latlng,
		      	mapTypeId	: google.maps.MapTypeId.ROADMAP,
		      	scrollwheel	: t.settings.scroll_wheel == 'yes' ? true : false,
		      	disableDoubleClickZoom: true
		    };
		    
		    options = $.extend(true, defaultOptions, options);
		    
		    t.map = new google.maps.Map(t.ui.canvas.get(0), options);
		    
		    google.maps.event.addListener(t.map, 'tilesloaded', function() {
		    	if(!t.isLoaded) {
				    $t.trigger('gmapInit', [t]);				    
		    		
		    		t.isLoaded = true;
		    	}
		    });
		    
		   	t.center = t.map.getCenter();
		    //t.bounds = t.map.getBounds();
		    t.directionsRenderer.setMap(t.map);
		    
		    if(t.settings.zoom_field != "") {
		    	var zoom = t.map.getZoom();
		    	
			    google.maps.event.addListener(t.map, 'zoom_changed', function() {
			    	var zoom = t.map.getZoom();
		    		
		    		if(t.isLoaded) {
				    	t.updateCustomField(t.settings.zoom_field, zoom);
				    }
			    });
			    
			    if(t.isLoaded) {
				    t.updateCustomField(t.settings.zoom_field, zoom);
				}
		    }
		    
		    google.maps.event.addListener(t.map, 'dblclick', function(e) {
		    	var lat   = e.latLng.lat();
		    	var lng   = e.latLng.lng();
		    	var value = lat + ',' + lng;
		    	
		    	t.geocode(value, function(results, status) {
		    		if(results) {
						results = t.saveResponse(results[0], lat, lng);
						results.geometry.location.lat = lat;
						results.geometry.location.lng = lng;

						t.addMarker(results, false);
					}
					else {
						alert(status);
					}
				});
		    });
		    
		    $t.find('#region-stroke-opacity-'+id).selectToUISlider({
				sliderOptions: {
					stop: function(e,ui) {
						t.ui.strokeOpacity.change();
					}
				}
			});
			
		    $t.find('#region-stroke-weight-'+id).selectToUISlider({
				sliderOptions: {
					stop: function(e,ui) {
						t.ui.strokeWeight.change();
					}
				}
			});				
			
		    $t.find('#region-fill-opacity-'+id).selectToUISlider({
				sliderOptions: {
					stop: function(e,ui) {
						t.ui.fillOpacity.change();
					}
				}
			});			
		
			$t.find('.show-color-picker').focus(function() {
				var $t = $(this);
				var id = '#'+$t.attr('data-show');
				$(id).show();
			});
			
			$t.find('.show-color-picker').blur(function() {
				var $t = $(this);
				var id = '#'+$t.attr('data-show');
				$(id).hide();
				t.renderPolygon();
			});
			
		    if(typeof callback == "function")
		    {
		    	callback(t.map);
		    }
		    
		    return t.map;
		},
		geocode: function(address, callback) {
			
		    $t.trigger('gmapGeocodeStart', [address, t]);
		    
			(function () {  
				var geocoder = new google.maps.Geocoder();
				var search   = { 'address': address}
				var isCoord  = false;
				var latLng;

				if(address.match(/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/)) {
					var coord = address.split(',');
					latLng = new google.maps.LatLng(coord[0], coord[1]);

					search    = {latLng: latLng};
					isCoord   = true;
				}
				
				geocoder.geocode(search, function(results, status) {
					//$('#'+t.settings.response).val(results);
					if(isCoord && status == 'ZERO_RESULTS') {
						results = [{
							types: ['unknown'],
							formatted_address: '',
							address_components: [],
							geometry: {
								location: latLng,
								location_type: 'unknown',
								viewport: t.bounds,
								bounds: t.bounds
							}
						}];
						status = 'OK';
					}
					else if(isCoord) {
						results[0].geometry.location = latLng;
					}

					t.results = results;
									
		   			$t.trigger('gmapGeocodeStop', [results, status, t]);
		    
					callback(results, status, isCoord);
				});
			})();		
		},
		
		populateIcons: function(obj, index) {
						
			if(obj.find('.gmap-icon').length == 0) {
				
				t.icons = [];
				
				if(obj) {
					obj.find('.gmap-icons').html('<ul></ul>');
				}
				
				$.each(icons, function(i, icon) {
					t.icons[i] = new Image();
					t.icons[i].src = icon.path;
					
					var selected = '';
					
					if(t.markers[t.editMarker].icon == icon.path)
						selected = 'class="selected"';
						
					//console.log('<li><a href="#" '+selected+' class="gmap-icon"><img src="'+icon.path+'" alt="'+icon.name+'" /></a></li>');
					
					obj.find('.gmap-icons ul').append('<li><a href="#" '+selected+' class="gmap-icon"><img src="'+icon.path+'" alt="'+icon.name+'" /></a></li>');
				});
			}
			else {
			
				if(obj) {
					obj.find('.gmap-icons li a').removeClass('selected');
				}
				
				$.each(t.icons, function(i, icon) {
					if(index && t.markers[index] && icon.src == t.markers[index].icon) {
						obj.find('.gmap-icons ul li').eq(i).children('a').addClass('selected');
					}
				});
			}
				
		},
		
		showMarkerPanel: function(index, response) {
		
			if(typeof index == "object") {
				response = index;
				index = ((t.markers.length - 1) > 0) ? t.markers.length - 1 : 0;
			}
			
			t.editMarker = index;
			t.editMarkerData = t.markers[index];
							
			if(response) {
				t.ui.markerPanel.find('.address').html(response.formatted_address);
				
				t.ui.markerPanel.find('input').val('');
				t.ui.sideContentText.val('');
				
				if(response.title)
					t.ui.markerPanel.find('input[name="marker-title"]').val(response.title);
			
				if(response.content) {
								
					var content = response.content;
					
					t.ui.markerPanel.find('input[name="side-content"]').val(content);
					t.ui.sideContentText.val(content);
				}
			}
			
			t.populateIcons(t.ui.markerPanel, index);				
			t.ui.markerPanel.show();
			
			$t.trigger('gmapShowMarkerPanel', [t.ui.markerPanel, t]);
		},
					
		route: function() {
		
			var points = [];
											
			$t.find('ul.waypoints li').each(function() {
				var	$t 	  = $(this);
				var index = parseInt($t.attr('data-index'));
				
				points.push(t.response.waypoints.results[index]);
			});
			
			var waypoints = [];
			
			if(points.length > 0) {
				$.each(points, function(i, response) {
					if(typeof response == "object") {
						waypoints.push({
							location: response.geometry.location.lat+','+response.geometry.location.lng,
							stopover: true
						});
					}
				});
				
				if(waypoints.length >= 2) {
					var start = waypoints[0];
					var end	  = waypoints[waypoints.length - 1];
					
					t.remove(waypoints, 0);
					t.remove(waypoints, waypoints.length - 1);
					
					var request = {
				        origin: start.location, 
				        destination: end.location,
				        waypoints: waypoints,
				        optimizeWaypoints: true,
				        travelMode: google.maps.DirectionsTravelMode.DRIVING
				    }
					
					$t.trigger('gmapRouteStart', [request, t]);
										
					t.directionsService.route(request, function(response, status) {
						if (status == google.maps.DirectionsStatus.OK) {
						
							$t.trigger('gmapRouteStop', [response, status, t]);
							
							t.response.waypoints.route = response;						
							t.directionsRenderer.setDirections(response);
							
							t.reorder();
						}
					});
				}
			}
		},
		
		refresh: function(response, list) {
			
			if(!list) {
				$(t.ui.lists).each(function() {
					var $t = $(this);
				
					if($t.hasClass('active')) list = $t;
				});
			}
			else {
				list = 	$t.find('ul.'+list);
			}
			
			if(list)list.html('');
				
			if(response.length > 0) {
				$.each(response, function(i, data) {
					if(data) {
						
						var html 		= '<li data-index="'+i+'"><a href="#" class="delete" data-type="'+t.responseType+'" data-index="'+i+'"><span class="times">&times;</span></a>';
						
						if(data.address_components && t.responseType == 'markers' || t.responseType == 'waypoints') {
			
							var components 	= data.address_components;
							var address		= data.formatted_address.split(',');
							
							if(!data.title) {
								$.each(address, function(i, component) {
									html += '<span class="gmap-address">'+address[i]+'</span><br>';
								});
							}
							else {
								html += '<p class="gmap-marker-title">'+data.title+'</p>';
							}
							
							if(typeof data.geometry.location.lat == "function")
							{
								data.geometry.location.lat = data.geometry.location.lat();
							}
							
							if(typeof data.geometry.location.lng == "function")
							{
								data.geometry.location.lng = data.geometry.location.lng();
							}
							
							html += '<a href="#" class="coordinate" data-type="'+t.responseType+'" data-index="'+i+'">'+data.geometry.location.lat+','+data.geometry.location.lng+'</a></li>';										
						}
						else {
							if(data.saveData) {
								data = data.saveData;
								html += '<span class="name">'+data.title+'</span><br><span class="total">Total Points:'+data.total+'</span>';
							}
						}
			
						if(list) list.append(html);
					}
				});	
			}
			else {
				list.append('<li class="empty"><p>You have not added any '+list.attr('data-name')+' to this list.</p></li>');
			}
			
			$t.trigger('gmapRefresh', [response, list, t]);
			
			t.ui.geocoder.val('');
			t.reorder();
		},
		
		removeMarker: function(type, index) {
			
			var currentResponseType = t.responseType;
			
			t.responseType = type;
			
			var data = {};
			
			if(type == 'markers') {
				t.response.markers.results[parseInt(index)] = false;

				if(typeof t.markers[parseInt(index)].setMap == "function") {
					t.markers[parseInt(index)].setMap(null);
					t.markers[parseInt(index)] = false;
					t.response.markers.total--;
					
					data = t.response.markers.results;
						
					$t.trigger('gmapRemoveMarker', [index, data, t]);
					
					t.refresh(data);
				}
			}
			else if(type == 'waypoints') {
				if(t.response.waypoints.total > 2 || t.override) {
					t.response.waypoints.results[index] = false;
					t.response.waypoints.total--;
					t.route();
					
					data = t.response.waypoints.results;
					
					t.refresh(data);
					
					$t.trigger('gmapRemoveWaypoint', [index, data, t]);
				}
				else {
					alert('At least 2 points are required in waypoint mode.');
				}
			}
			else if(type == 'regions') {
				
				if(t.polyInfoWindow)	{
					t.polyInfoWindow.setMap(null);
				}
				
				t.response.regions.results[index].setMap(null);
				t.response.regions.results[index] = false;
				t.response.regions.total--;
				
				data = t.response.regions.results;
					
				$t.trigger('gmapRemoveRegion', [index, data, t]);
				
				t.refresh(data);
			}
			
			t.bounds = t.getBounds();
			
			t.ui.wrapper.find('.toggle a[href="#'+t.responseType+'"]').click();
		},
		
		remove: function(obj, from, to) {
		  var rest = obj.slice((to || from) + 1 || obj.length);
		  obj.length = from < 0 ? obj.length + from : from;
		  return obj.push.apply(obj, rest);
		},
		
		reorder: function() {
			var response = {
				markers: {
					total: 0,
					results: []
				},
				waypoints: {
					total: 0,
					results: {}
				},
				regions: {
					total: 0,
					results: []
				}
			};
			
			t.ui.lists.each(function() {
				var list 	= $(this);
				var items = $(this).find('li');
				var name = $(this).data('name');
				
				items.each(function(i) {
					var item = $(this);
					var index = item.attr('data-index');
					
					if(index != undefined) {
						item.removeClass('odd').removeClass('even');
						
						if(i % 2 == 0)
							item.addClass('even');
						else
							item.addClass('odd');
							
						switch (name) {
							
							case 'markers':
								var data = t.response.markers.results[index];
								
								response.markers.results[response.markers.results.length] = data;
								response.markers.total++;
								
								break;
								
							case 'waypoints':
								var data 	= t.response.waypoints.results[index];
								
								response.waypoints.results[response.waypoints.total] = data;
								response.waypoints.total++;
								
								break;
								
							case 'regions':
								if( t.response.regions.results[index]) {
								var data = t.response.regions.results[index].saveData;
								
								response.regions.results[response.regions.total] = data;
								response.regions.total++;
								}
								break;
						}
					}
				});
				
				if(name == 'waypoints') {
					var route 	= t.response.waypoints.route;
								
					if( t.responseType == 'waypoints' && 
						typeof t.response.waypoints.route.routes != "undefined") {
						
						var legs = route.routes[0].legs;
						
						$.each(legs, function(i, leg) {
							var distance 	= leg.distance.text;
							var duration 	= leg.duration.text;
							var start		= leg.start_address;
							var end			= leg.end_address;
							var html 		= [
								'<a class="show-statistics">Show Details</a>',
								'<div class="statistics">',
									'<p class="starting-address">',
										'<b>Starting Address</b><br>'+start,
									'</p>',
									'<p class="ending-address">',
										'<b>Ending Address</b><br>'+end,
									'</p>',
									'<p class="distance-duration">',
										'Distance: '+distance+' – Duration: '+duration,
									'</p>',
								'</div>'
							].join(' ');
							
							list.find('li[data-index="'+(i+1)+'"] .show-statistics').remove();
							list.find('li[data-index="'+(i+1)+'"] .statistics').remove();
							list.find('li[data-index="'+(i+1)+'"]').append(html);
						});
						
						var first 	= response.waypoints.results[0];
						var last  	= response.waypoints.results[response.waypoints.results.length - 1];
						
						$('#' + t.settings.waypoint_start_coord).val(JSON.stringify(first));
						$('#' + t.settings.waypoint_end_coord).val(JSON.stringify(last));
					}
				}
				
			});				
		
			var updateFields = [
				'#'+t.settings.marker_field,
				'#'+t.settings.waypoint_field,
				'#'+t.settings.region_field
			];
			
			var updateValues = [
				JSON.stringify(response.markers.results),
				JSON.stringify(response.waypoints.results),
				JSON.stringify(response.regions.results)
			];
			
			t.updateCustomField(updateFields, updateValues);
			
			if(t.isLoaded) {
				t.ui.input.html(JSON.stringify(response));
			}
		},
		
		updateCustomField: function(setFields, values) {
			
			if(typeof setFields != "object") {
				setFields = [setFields];
			}
			
			if(typeof values != "object") {
				values = [values];
			}
			
			$.each(t.fields, function(i, field) {					
				$.each(setFields, function(x, setField) {
					$('#'+setField.replace('#', '')).val(values[x]);					
					if(i == setField) {
						$('#'+field.field_name).val(values[x]);
						$('*[name="'+field.field_name+'"]').val(values[x]);
						$('*[name="field_id_'+field.field_id+'"]').val(values[x]);
						$('#field_id_'+field.field_id).val(values[x]);
					}
				});
			});
		},
		
		updateMarkerFields: function(response, lat, lng) {
			if(lat && lng) {
				response.geometry.location.lat = lat;
				response.geometry.location.lng = lng;
				
				var address = response.formatted_address;
				
				var fields = [
					t.settings.latitude_field,
					t.settings.longitude_field,
					t.settings.formatted_address
				];
				
				var values = [lat, lng, address];

				t.updateCustomField(fields, values);
			}
		},
		
		saveResponse: function(response, lat, lng) {
		
			var _return = response;
			
			$('#'+t.settings.response).val(JSON.stringify(response));
			
			if(t.responseType == 'markers' || t.responseType == 'waypoints') {
				
				/* Ensure backwards compatibility is met upon users upgrading from v3.1.0 */
				if(typeof t.settings.min_points == "undefined") {
					t.settings.min_points = 0;
				}
			
				if(t.responseType == 'markers') {
					if(t.settings.total_points == 0 || t.response.markers.total < t.settings.total_points) {
						t.response.markers.results[t.response.markers.results.length] = response;
						
						t.updateMarkerFields(response, lat, lng);

						response = t.response.markers.results;
						t.response.markers.total++;
						t.overlimit.markers = false;
					}
					else {
						t.overlimit.markers = true;
						
						/*
						var errors = [];
						
						if(t.settings.min_points <= t.response.markers.total) {
							errors.push(t.settings.min_points > 0 ? 'Your map must contain at least '+t.settings.min_points+' marker'+(t.settings.min_points > 1 ? 's' : '') : '');
						}
						
						if(t.response.markers.total > t.settings.total_points) {
							errors.push('You are only allowed a maximum of '+t.settings.total_points+' marker'+(t.settings.total_points > 1 ? 's' : '')+' per map.');
						}
						*/
						
						alert('You are only allowed a maximum of '+t.settings.total_points+' marker'+(t.settings.total_points > 1 ? 's' : '')+' per map.');
					}
				}
				else if(t.responseType == 'waypoints') {
					t.response.waypoints.results[t.response.waypoints.results.length] = response;
					response = t.response.waypoints.results;
					t.response.waypoints.total++;
				}
				
				if(!t.overlimit.markers) {
					t.refresh(response);
				}
			}
							
			return _return;
		},
		
		resize: function(delay) {
			if(!delay)	var delay = 100;

			setTimeout(function () {
				google.maps.event.addListener(t.map, 'resize', function() {
					t.map.setCenter(t.center);
				});
				
				google.maps.event.trigger(t.map, 'resize');
				
			}, delay);
		},
		
		showRegionPanel: function(newRegion) {
			if(t.editRegion === false && t.region.length == 0) {				
				var color = t.colors[Math.floor(Math.random()*(t.colors.length))];
				
				while(t.lastColor == color) {
					color = t.colors[Math.floor(Math.random()*(t.colors.length))];
				}
				
				t.lastColor = color;
				t.ui.regionPanel.find('input[name="region-stroke-color"]').val(color.stroke);
				t.ui.regionPanel.find('input[name="region-fill-color"]').val(color.fill);				
			}
			else {
				color = t.lastColor;
			}	
					
			t.ui.sideContentText.val('');
			t.ui.regionName.val('');
			t.ui.regionContent.val('');
			
			if(t.editRegion !== false) {
				
				var data  = t.response.regions.results[t.editRegion].saveData;
				var style = data.style;
				
				var color = {
					stroke: style.strokeColor,
					fill: style.fillColor
				}
				
				t.ui.regionPanel.find('select[name="region-stroke-weight"]').val(style.strokeWeight).change();
				t.ui.regionPanel.find('select[name="region-stroke-opacity"]').val(style.strokeOpacity).change();
				t.ui.regionPanel.find('select[name="region-fill-opacity"]').val(style.fillOpacity).change();						
				t.ui.regionName.val(data.title);					
				t.ui.regionContent.val(data.content);
				t.ui.sideContentText.val(data.content);
			}
			
			if(t.region.length == 0) {
				$t.find('.show-color-picker').each(function(i) {
					var $t = $(this);
					var id = $t.attr('data-show');
					var fieldName = $t.attr('data-field');
					
					$('#'+id).children('div').farbtastic(function(color) {
						var field = $('#'+fieldName);
						field.css('backgroundColor', color);
						field.val(color);
						t.renderPolygon();
					});
					
					var picker = $.farbtastic($('#'+id).children('div').get(0));
					
					if(i == 0) {
						$t.val(color.stroke);
						picker.setColor(color.stroke);
					} else {
						$t.val(color.fill);
						picker.setColor(color.fill);
					}
				});	
			}
			
			t.ui.regionPanel.show();
		},
		
		window: function(marker, options, open) {
			if(typeof options == "string") {
				options = {
					content: options
				};
			}
			
			if(typeof open == "undefined") open = true;
			
			var infoWindow = new google.maps.InfoWindow(options);
			
			$.each(t.windows, function(i) {
				t.windows[i].close();
			});
							
			if(t.responseType == "markers" && open === true)
				infoWindow.open(t.map, marker);				
			
			google.maps.event.addListener(marker, 'click', function() {
				$.each(t.windows, function(i) {
					t.windows[i].close();
				});
							
				infoWindow.open(t.map, marker);
			});
			
			return infoWindow;
		}
	}
	
	t.ui.strokeColor.blur(function() {
		t.renderPolygon();		
	});
		
	t.ui.fillColor.blur(function() {
		t.renderPolygon();		
	});
	
	t.ui.strokeOpacity.change(function() {
		t.renderPolygon();
	});
		
	t.ui.strokeWeight.change(function() {
		t.renderPolygon();
	});
		
	t.ui.fillOpacity.change(function() {
		t.renderPolygon();
	});
						
	t.ui.button.click(function() {
		var value = t.ui.geocoder.val();
		
		t.geocode(value, function(results, status, isCoord) {
			if(status == "OK") {
				if(results.length > 1 && !isCoord) {
					
					t.ui.suggestions.fadeIn('fast');
					t.ui.suggestionStatistics.html(results.length+' possible locations');
					
					t.ui.suggestions.find('li').remove();
					
					$.each(results, function(i, data) {
						t.ui.suggestions.children('ul').append('<li data-index="'+i+'"><a href="#" data-index="'+i+'">'+data.formatted_address+'</a><li>');
					});						
				}
				else {
					
					t.ui.suggestions.hide();
				
					var lat = results[0].geometry.location.lat();
					var lng = results[0].geometry.location.lng();
					
					results = t.saveResponse(results[0], lat, lng);
					
					t.addMarker(results);
					t.ui.geocoder.val('');
					
					//t.showHelp('2');
				}
			}
			else if(status == "ZERO_RESULTS") {
				alert('No location found for \''+value+'\'.');
			}
			else {
				alert('The error \''+status+'\' was returned from the geocoder.'); 
			}
		});
		
		return false;
	});
	
	t.ui.lists.sortable({
		axis: 'y',
		placeholder: 'placeholder',
		update: function(event, ui) {
			t.reorder();
			
			if(t.responseType == 'waypoints')
				t.route();
			
			//$('.gmap-popup-window.step-4').hide();					
		}
	}).disableSelection();
	
	t.ui.geocoder.keypress(function(e) {
		if(e.keyCode == 13) {
			t.ui.button.click();
			return false;
		}
	});
	
	t.ui.deferer.click(function() {
		t.init(function() {
		
			t.bounds = new google.maps.LatLngBounds();
			
			//if(!t.bounds) { 
			
				/*$('#hide_field_12').parent().bind('drag', function() {
					t.resize(500);
				});*/
			
				$('#mainWrapper .content_tab a').click(function() {
					t.resize();
				});
				
				$t.parents('.main_tab').find('label > span').click(function() {
					t.resize();
				});
				
			//}
			
			// t.showHelp('1');
			
			t.ui.lists.each(function() {
				var list 		= $(this);
				var name 		= list.data('name');
				var data 		= {};
				
				if(savedResponse) {						
					
					/* Begin early 3.0 beta compatibility fix */
					
					if(savedResponse.markers.data)
						savedResponse.markers.results = savedResponse.markers.data;
					
					if(savedResponse.waypoints.data)
						savedResponse.waypoints.results = savedResponse.waypoints.data;
					
					if(savedResponse.regions.data)
						savedResponse.regions.results = savedResponse.regions.data;
					
					/* End compatibility fix */
							
					switch (name) {
						case 'markers':
							data = savedResponse.markers.results;
							break;
						case 'waypoints':
							data = savedResponse.waypoints.results;
							break;
						case 'regions':
							data = savedResponse.regions.results;
					}
							 
					t.responseType = name;
					t.ui.lists.removeClass('active');
					list.addClass('active');
					
					$.each(data, function(i, response) {
						if(response != null) {
							switch (name) {
								case 'markers':
									var lat = response.geometry.location.lat;
									var lng = response.geometry.location.lng;
									
									response = t.saveResponse(response, lat, lng);
									
									var extendBounds = true;

									if(t.settings.zoom_field != "" && zoom != "") {
										var zoom = $('#'+t.settings.zoom_field+', [name="'+t.settings.zoom_field+'"]').val();
									
										zoom = parseInt(zoom);
										t.map.setZoom(zoom);
										t.map.setCenter(new google.maps.LatLng(lat, lng));
										extendBounds = false;
									}
					
									t.addMarker(response, extendBounds, false);
									
									break;
								
								case 'waypoints':
									var lat = response.geometry.location.lat;
									var lng = response.geometry.location.lng;
									
									response = t.saveResponse(response, lat, lng);
									
									t.addMarker(response);
									
									break;
								
								case 'regions':
									t.region = response;
									
									var coords = [];
									
									$.each(t.region.coords, function(i, coord) {
										var latlng =  new google.maps.LatLng(coord.lat, coord.lng);
										t.bounds.extend(latlng);
										coords[coords.length] = latlng;
									});
									
									var response = {
										title: 		t.region.title,
										content: 	t.region.content,
										total: 		t.region.total,
										coords:		t.region.coords,
										style:	{
											paths: 			coords,
											strokeColor: 	t.region.style.strokeColor,
											strokeOpacity:	t.region.style.strokeOpacity,
											strokeWeight:	t.region.style.strokeWeight,
											fillColor:		t.region.style.fillColor,
											fillOpacity:	t.region.style.fillOpacity
										}
									}
									
									t.addRegion(response);
									
									break;
							}
						}						
					});
				}
				
				$(t.ui.toggle.get(0)).click();

			});
			
		});
		
		return false;
	});
	
	if(t.settings.defer_init == 'no')
		t.ui.deferer.click();
	
	var importPanel = [
		'<div class="gmap-import-panel gmap-flyout">',
			'<form method="post" enctype="multipart/form-data" class="step-1">',
				'<h3>1. Import .CSV</h3>',
				'<input type="file" name="file" value="" />',
				'<button type="submit">Import</button>',
			'</form>',
			'<form class="step-2" style="display:none">',
				'<h3>2. Setup Geocoder</h3>',
				'<p>You can select multiple columns that will be join with a space.</p>',
				'<div class="title">',
					'<label for="title">Title</label>',
					'<select name="title" class="create-tags"></select>',
					'<div class="tags group"></div>',		
				'</div>',
				'<div class="content">',
					'<label for="content">Content</label>',
					'<select name="content" class="create-tags"></select>',	
					'<div class="tags group"></div>',	
				'</div>',
				'<div class="geocode">',
					'<label for="geocode">Geocode</label>',
					'<select name="geocode" class="create-tags"></select>',
					'<div class="tags group"></div>',	
				'</div>',
				'<div>',
					'<label for="marker-icon">Marker Icon <span class="clear-icon" style="display:none"><a href="#">clear</a></span></label>',
					'<div class="gmap-icons group"><ul></ul></div>',
				'</div>',
				/* '<div class="latitude">',
					'<label for="latitude">Latitude</label>',
					'<select name="latitude"></select>',	
				'</div>',
				'<div class="longitude">',
					'<label for="longitude">Longitude</label>',
					'<select name="longitude"></select>',
				'</div>',*/
				'<button type="submit">Run Geocoder</button>',
			'</form>',
			'<div class="step-3" style="display:none">',
				'<h3>3. Run Import</h3>',
				'<p>',
					'Total Markers: <span class="total-markers"></span><br>',
					'Total Imported: <span class="total-imported"></span><br>',
					'Total Failed: <span class="total-failed"></span><br>',
					'Geocoding: <span class="geocoding"></span>',
				'</p>',
				'<div class="progress-bar"></div>',
				'<p>Errors</p>',
				'<textarea readonly></textarea>',
				'<button type="button">Close</button>',
			'</div>',
		'</div>'
	].join('');
	
	$('body').append(importPanel);
	
	var action = $('input[name="import_url"]').val();		
	
	var importData;
	var importMarkers;
	var importErrors = 0;
	
	t.importArray = function(array, count) {
	
		var marker = array[count];
		
		$imported = t.ui.importPanel.find('.step-3 .total-imported');
		$failed = t.ui.importPanel.find('.step-3 .total-failed');
		
		t.geocode(marker.location, function(results, status) {
			if(status == 'OK' && results) {
				var lat = results[0].geometry.location.lat();
				var lng = results[0].geometry.location.lng();
							
				results = t.saveResponse(results[0], lat, lng);
				results.icon = marker.icon;
				results.content = marker.content;
				results.geometry.location.lat = lat;
				results.geometry.location.lng = lng;
				
				t.addMarker(results);
			
				var index = t.markers.length - 1;
									
				if(marker.title != "") {
					t.response.markers.results[index].title = marker.title;
				}
				
				if(marker.content != "") {
					content = t.buildInfoWindow(index, true, marker.content);
								
					t.markers[index].content = marker.content;					
					t.windows[index].setContent(content);
				}
				
				var progress = Math.ceil(count / array.length * 100);
				
				t.ui.importPanel.find('.step-3 .progress-bar').progressbar({value: progress});
				
				$imported.html(parseInt($imported.html())+1);
			}
			else {
				importErrors++;
				t.ui.importPanel.find('.step-3 textarea').attr('rows', importErrors);
				t.ui.importPanel.find('.step-3 textarea').append('['+(count+2)+'] Error Geocoding: '+marker.location+"\r\n");
				$failed.html(parseInt($failed.html())+1);					
			}
			
			if(count < array.length - 1) {
				setTimeout(function() {
					t.importArray(array, count + 1);
				}, 1000);										
			}
			else {
				t.ui.importPanel.find('.step-3 .progress-bar').progressbar({value: 100});
			}
			
			t.refresh(t.response.markers.results, 'markers');
		});
	}
	
	t.ui.importPanel = $('.gmap-import-panel');		
	t.ui.importPanel.find('.step-1').attr('action', action).ajaxForm({
		//dataType: 'json',
		success: function(response) {
		
			importData = response;
			
			var options = ['<option value="">--</option>'];
			
			$.each(response.columns, function(i, column) {
				options.push('<option value="'+column+'">'+column+'</option>');	
			});
			
			t.ui.importPanel.find('.step-1, .step-3').hide();
			t.ui.importPanel.find('.step-2').show();
			t.ui.importPanel.find('.step-2 select').show().html(options.join(''));
			
			t.populateIcons(t.ui.importPanel);
		}
	});
	
	t.ui.importPanel.find('form .create-tags').change(function() {
		var $t    = $(this);
		var tags  = $t.next('.tags');
		var value = $t.val();
		var name  = $t.attr('name');
					
		tags.append('<div class="tag">'+value+'<a href="#">&times;</a><input type="hidden" value="'+value+'" name="'+name+'" /></div>');
		
		$t.val('');
	});
	
	t.ui.importPanel.find('.tag a').live('click', function(e) {
		var $t = $(this);
		
		$t.parent().fadeOut(function() {
			$(this).remove();	
		});
		
		e.preventDefault();
	});
	
	t.ui.importPanel.find('.step-2').submit(function(e) {
		
		var markers = [];
		
		$.each(importData.rows, function(i, row) {
		
			var title   = '';
			var content = '';
			var location = '';
			var icon = '';
			
			t.ui.importPanel.find('.step-2 .title input').each(function() {
				if($(this).val() != "") {
					title += row[$(this).val()] + ' ';
				}
			});
			
			t.ui.importPanel.find('.step-2 .content input').each(function() {
				if($(this).val() != "") {
					content += row[$(this).val()] + ' ';
				}
			});				
			
			t.ui.importPanel.find('.step-2 .geocode input').each(function() {
				if($(this).val() != "") {
					location += row[$(this).val()] + ' ';
				}
			});
			
			var icon = t.ui.importPanel.find('.gmap-icon.selected img').attr('src');
			
			var lat = t.ui.importPanel.find('.step-2 .latitude select').val();
			var lng = t.ui.importPanel.find('.step-2 .longitude select').val();
							
			markers.push({
				title: $.trim(title).replace(/\s/g, " "),
				content: $.trim(content).replace(/\s/g, " "),
				location: $.trim(location).replace(/\s/g, " "),
				latitude: (lat != '' ? row[lat] : false),
				longitude: (lng != '' ? row[lng] : false),
				icon: (icon ? icon : false)
			});	
		});
		
		t.ui.importPanel.find('.step-3 .total-markers').html(markers.length);
		t.ui.importPanel.find('.step-3 .total-imported').html(0);
		t.ui.importPanel.find('.step-3 .total-failed').html(0);
		t.ui.importPanel.find('.step-3 .progress-bar').progressbar({value: 0});
		t.ui.importPanel.find('.step-3 textarea').html('');
		t.ui.importPanel.find('.step-2, .step-1').hide();
		t.ui.importPanel.find('.step-3').show();
		
		t.importArray(markers, 0);
		
		/*
		t.geocode(markers[0].location, function(data) {
			
		});
		
		$.each(markers, function(i, marker) {
			if(!marker.latitude || !marker.longitude) {
				alert(marker.location);
				t.geocode(marker.location, function(data) {
					alert(data);	
				});
			}
		});
		
		p.each(function() {
			var tags = $(this).find('.tags');
			
			if(tags.length > 0) {
					
			}							
		});
		*/
		
		//console.log(importData);
		
		return false;
	});
	
	
	t.ui.importPanel.find('.step-3 button').click(function(e) {
		t.ui.importPanel.fadeOut();
		
		e.preventDefault();
	});
	
	t.ui.suggestion.live('click', function() {
		var $t = $(this);
		var index = parseInt($t.attr('data-index'));
		var lat = t.results[index].geometry.location.lat();
		var lng = t.results[index].geometry.location.lng();
		
		var response = t.saveResponse(t.results[index], lat, lng);
		
		t.addMarker(response);
		t.ui.suggestions.fadeOut('fast');

		// t.showHelp('3');
		
		return false;
	});
	
	t.ui.toggle.click(function(obj) {

		var $t = $(this);
		var href = $t.attr('href').replace('#', '');
		
		t.responseType = href;
		
		t.ui.lists.hide();
		t.ui.lists.removeClass('active');
		t.ui.toggle.removeClass('active');
		t.ui.suggestions.hide();
		
		if(t.isLoaded) {
			t.ui.geocoder.focus();
		}
		
		//t.showHelp(true);
		
		t.ui.lists.each(function() {
			var $t = $(this);
			
			if($t.hasClass(href)) {
				t.ui.regionCancelButton.click();
				
				if(href == 'regions' && t.region.length > 0) {
					t.showRegionPanel(true);
				}
				
				$t.addClass('active');
				$t.show();
			}
		});
		
		if(href == 'markers')
			t.refresh(t.response.markers.results);
		else if(href == 'waypoints')
			t.refresh(t.response.waypoints.results);
		else if(href == 'regions')
			t.refresh(t.response.regions.results);
		
		$t.addClass('active');
		
		return false;
	});
	
	$(t.ui.toggle.get(0)).click();
	
	t.ui.closeButtons.click(function() {
		$(this).parent().fadeOut('fast');
		
		return false;
	});
	
	t.ui.deleteButton.live('click', function() {
		var $t	  	= $(this);
		var index	= $t.attr('data-index');
		var type 	= $t.attr('data-type');
		
		t.removeMarker(type, index);
			
		return false;
	});
				
	t.ui.markerCancelButton.click(function() {
	
		var marker = t.response.markers.results[t.editMarker]
		var icon = marker.icon;
		var title = marker.title;
		var content = marker.content;
		
		t.markers[t.editMarker].icon = icon ? icon : '';
		
		t.markers[t.editMarker].setIcon(t.markers[t.editMarker].icon);
		
		t.ui.markerPanel.fadeOut();
		
		return false;
	});		
	
	t.ui.markerSaveButton.click(function() {
		
		var index = t.editMarker;
		
		var marker = t.response.markers.results[index];
		
		marker.title 	= t.ui.markerName.val();
		marker.content	= t.ui.sideContentText.val();
		marker.icon		= t.markers[t.editMarker].icon;
		
		t.response.markers.results[index] = marker;
		
		if(marker.content != '') {
			var hasContent  = marker.content ? false : marker;
			var content     = marker.content ? marker.content : false;
			
			content = t.buildInfoWindow(index, hasContent, content);
							
			t.markers[index].title = marker.title;
			t.markers[index].content = marker.content;
			
			t.windows[index].setContent(content);
		}
		
		t.ui.markerPanel.fadeOut();
			
		t.refresh(t.response.markers.results, 'markers');
		
		$t.trigger('gmapSaveMarker', [index, marker, t.windows[index], t]);
					
		return false;
	});
		
	t.ui.regionCancelButton.click(function() {
		
		if(t.editRegion !== false) {
			t.addRegion(t.response.regions.results[t.editRegion].saveData);
		}
		else {
			var region		= t.region;
			
			$.each(region, function(i, data) {
				data.setMap(null);
			});
			
			t.region = [];
			t.ui.regionPanel.hide();
		}
		
		if(t.newRegion) {
			t.newRegion.setMap(null);
		}
		
		t.totalPoints = 0;
		t.editRegion  = false;
			
		return false;
	});
	
	t.ui.regionSaveButton.click(function() {
		t.addRegion();
		
		return false;
	});
	
	t.ui.importPanel.find('.gmap-icon').live('click', function() {
		var $t = $(this);
					
		if(!$t.hasClass('selected')) {
			t.ui.importPanel.find('.gmap-icon').removeClass('selected');
			$t.addClass('selected');				
		}
		else {
			$t.removeClass('selected');
		}
		
		return false;
	});
	
	t.ui.markerPanel.find('.gmap-icons a').live('click', function() {
		var $t = $(this);
		var index = $t.parent().index();
		var path = icons[index].path;
	
		if(!$t.hasClass('selected')) {	
			t.ui.markerPanel.find('.gmap-icons a').removeClass('selected');
		
			t.markers[t.editMarker].setIcon(path);
			
			$t.addClass('selected');				
		}
		else {
			t.markers[t.editMarker].setIcon('');
			$t.removeClass('selected');
		}
		
		return false;
	});
			
	t.ui.lists.find('a.coordinate').live('click', function() {
		var $t 	 	= $(this);
		var type 	= $t.data('type');
		var index   = parseInt($t.data('index'));
		
		if(type == "markers") {
			google.maps.event.trigger(t.markers[index], 'click');			
		}
		
		return false;
	});
	
	t.ui.deletePoint.live('click', function() {
		var $t = $(this);
		var index = parseInt($t.attr('data-index'));
		
		t.region[index].setMap(null);
		t.region[index] = false;
		t.totalPoints--;
		
		t.ui.regionPanel.find('li:first-child').html('Total Points: '+t.totalPoints);
		
		t.renderPolygon();
			
		return false;
	});
	
	t.ui.deleteMarker.live('click', function() {
		var $t = $(this);
		var index = parseInt($t.attr('data-index'));
		
		t.removeMarker('markers', index);
		t.ui.markerPanel.hide();
		
		return false;
	});
	
	t.ui.editMarker.live('click', function() {
		var $t = $(this);
		var index = parseInt($t.attr('data-index'));
		var response = t.response.markers.results[index];
		
		t.showMarkerPanel(index, response);
					
		return false;
	});
	
	t.ui.editRegion.live('click', function() {
		var $t = $(this);
		var index = parseInt($t.attr('data-index'));
		var regionData = t.response.regions.results[index].saveData;
		
		$t.find('.toggle a[href="#regions"]').click();
		t.responseType = 'regions';
		
		t.lastRegion = regionData;
		t.response.regions.results[index].setMap(null);
		t.polyInfoWindow.setMap(null);
		t.editRegion = index;

		t.showRegionPanel(t.editRegion);
			
		$.each(regionData.coords, function(i, data) {
			t.addPoint(data, false)		
		});
		
		return false;
	});
	
	t.ui.deleteRegion.live('click', function() {
		var $t = $(this);
		var index = parseInt($t.attr('data-index'));
		
		t.removeMarker('regions', index);
		
		return false;
	});
	
	t.ui.upload.click(function() {
		
		if(t.ui.importPanel.css('display') == 'block') {
			t.ui.importPanel.fadeOut('fast');	
		}
		else {
			t.ui.importPanel.find('.step-1').show();
			t.ui.importPanel.find('.step-2, .step-3').hide();
			t.ui.importPanel.fadeIn('fast');
			
			if(!t.icons) {
				t.icons = [];					
				t.ui.markerPanel.find('.gmap-icons').html('<ul></ul>');
				
				$.each(icons, function(i, icon) {
					t.icons[i] = new Image();
					t.icons[i].src = icon.path;						
					t.ui.importPanel.find('.gmap-icons ul').append('<li><a href="#" class="gmap-icon"><img src="'+icon.path+'" alt="'+icon.name+'" /></a></li>');
				});
			}
			else {
				t.ui.importPanel.find('.gmap-icons li a').removeClass('selected');
			}
		}
		
		t.ui.importPanel.position({
			my: 'left top',
			at: 'left bottom',
			of: $(this).parent(),
			offset: "0 10"
		});
		
		return false;
	});
	
	t.ui.sideContent.focus(function() {
		t.ui.sideContentPanel.fadeIn('fast');
		t.ui.sideContentText.focus();
	});
	
	t.ui.sideContentText.blur(function() {
		t.ui.markerPanel.find('input[name="side-content"]').val($(this).val());
		t.ui.sideContentPanel.fadeOut('fast');
	});
	
	t.ui.sideContentText.keyup(function() {
		t.ui.sideContent.val($(this).val());
	});
	
	t.ui.showStatistics.live('click', function() {
		var $t 		= $(this);
		var stats 	= $t.next('.statistics');
		
		if(stats.css('display') == 'none') {
			stats.show();
			$t.html('Hide Details');
		}
		else {
			stats.hide();
			$t.html('Show Details');
		}
	});
		
	/*
	$t.parents('form').submit(function() {
		
		var title 	= $('#title').val();

		var _return = true;
		
		if(title == '') {
			alert('You must enter a title before saving the entry. If you submit your phone with errors, the map data with not be saved.');
			
			return false;
		}
		
		return _return;
	});
	*/
	
	/* Load the Third-party JS plugins */
	if(!GmapPluginsLoaded)
	{
	    $.each(t.plugins, function(i, url) {
	    	var element = document.createElement('script');
	    	
			element.src = url;
			element.type = 'text/javascript';
			
			$('body').append(element);
	    });
	    
	    GmapPluginsLoaded = true;
	}
	
	var count = 0;
	
	$t.find('.toggle li').each(function() {
		var $t = $(this);
		
		if($t.css('display') != 'none')
			count++;
	});
	
	$t.find('.toggle li:first a').addClass('first');
	$t.find('.toggle li:last a').addClass('last');
	
	if(count == 0) $t.find('.toggle').hide();

	GmapGlobal.object.push(t);
	Gmap.instances.push(t);
	Gmap.settings[options.colId] = t.settings;
	
	return Gmap;
}

Gmap.instances = [];
Gmap.settings  = [];