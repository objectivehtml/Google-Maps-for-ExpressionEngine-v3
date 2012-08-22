/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Javascript
 * @category	Fieldtype
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.0.186
 * @build		20120711
 */
 
$(document).ready(function() {
	
	$('.gmap-wrapper').each(function() {
			
		var $t            = $(this);
		var $gmap         = $t;
		var id            = parseInt($t.attr('data-gmap-id'));
		var icons         = GmapGlobal.icons[id];
		var savedResponse = GmapGlobal.response[id];
		
		var Gmap = {
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
			field:	GmapGlobal.field[id],
			fields:	GmapGlobal.fields[id],
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
			overlimit: {
				markers: false,
				route: false,
				regions: false
			},
			plugins: GmapGlobal.plugins[id],
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
			reqFields: GmapGlobal.reqFields[id],
			responseType: '',
			safecracker: GmapGlobal.safecracker[id],
			settings: GmapGlobal.settings[id],
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

				$.each(Gmap.response.markers.results, function(i, marker) {
					if(marker) {
						var lat    = parseInt(marker.geometry.location.lat);
						var lng    = parseInt(marker.geometry.location.lng);
						var latLng = new google.maps.LatLng(lat, lng);
						
						newBounds.extend(latLng);
					}
				});

				$.each(Gmap.response.waypoints.results, function(i, waypoint) {
					if(waypoint) {
						var lat    = parseInt(waypoint.geometry.location.lat);
						var lng    = parseInt(waypoint.geometry.location.lng);
						var latLng = new google.maps.LatLng(lat, lng);
						newBounds.extend(latLng);
					}
				});

				$.each(Gmap.response.regions.results, function(i, region) {
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
						map: Gmap.map,
						title: '',
						zIndex: 1000
					};
					
					if(response.icon)
						options.icon = response.icon;
					
					if(Gmap.bounds) {
						Gmap.bounds.extend(options.position);
					}				
				}
			
				Gmap.ui.markerPanel.fadeOut();
				
				if(Gmap.responseType == 'markers') {
					
					if(!Gmap.overlimit.markers) {

						if(Gmap.editMarker) {
							Gmap.ui.markerCancelButton.click();
							Gmap.editMarker = false;
						}

						var marker 		= new google.maps.Marker(options);
						var markerObj	= marker;
						var index 		= Gmap.response.markers.results.length-1;
						
						marker.index 	= index;
						
						var title		= response.title ? response.title : '';
						
						marker.title 	= title;
						marker.content  = response.content ? response.content : '';
						
						var content 	= response.content ? Gmap.buildInfoWindow(index, false, response.content) :
										  Gmap.buildInfoWindow(index, response);
						
						var infoWindow  = Gmap.window(marker, content, open);
						
						Gmap.markers[Gmap.markers.length] 	= marker;
						
						Gmap.windows[Gmap.windows.length]	= infoWindow;
						
						if(Gmap.bounds && resize) {
							if(Gmap.settings.zoom_one_marker && parseInt(Gmap.settings.zoom_one_marker) > 0 && Gmap.response.markers.total == 1) {
									for(var i = 0; i < Gmap.response.markers.results.length; i++) {
										var centerMarker = Gmap.response.markers.results[i];

										if(centerMarker) {
											var lat = centerMarker.geometry.location.lat;
											var lng = centerMarker.geometry.location.lng;
											var pos = new google.maps.LatLng(lat, lng);

											Gmap.map.setCenter(pos);
											Gmap.map.setZoom(parseInt(Gmap.settings.zoom_one_marker));
											break;
										}
									}
								
							}
							else {
								Gmap.map.fitBounds(Gmap.bounds);
							}
						}

						//Gmap.showMarkerPanel(response);
						
						$t.trigger('gmapAddMarker', [marker.index, response, infoWindow, Gmap]);
						
						google.maps.event.addListener(markerObj, 'dragend', function(event) {
							var lat = event.latLng.lat();
							var lng	= event.latLng.lng();
																
							Gmap.geocode(lat+','+lng, function(response) {
								
								response = response[0];
								
								Gmap.updateMarkerFields(response, lat, lng);
								
								response.geometry.location.lat = lat;
								response.geometry.location.lng = lng;
								response.icon = marker.icon ? marker.icon : '';
								response.title = marker.title ? marker.title : '';
								response.content = marker.content ? marker.content : '';
								
								Gmap.response.markers.results[marker.index] = response;
								
								Gmap.ui.markerPanel.find('.address').html(response.formatted_address);
								
								if(!Gmap.markers[index].content) {
									var content = Gmap.buildInfoWindow(index, response);
									infoWindow.setContent(content);
									
									marker.title   = '';	
									marker.content = '';
								}
								
								//Gmap.showMarkerPanel(response);
						
								$t.trigger('gmapMarkerDragEnd', [index, response, infoWindow, Gmap]);
						
								Gmap.refresh(Gmap.response.markers.results, 'markers');
						
							});
						});
					}
				}
				else if(Gmap.responseType == 'waypoints') {
					
					if(Gmap.bounds && Gmap.response.waypoints.total > 1)
						Gmap.map.fitBounds(Gmap.bounds);
					
					Gmap.route();
				}
				else if(Gmap.responseType == 'regions') {
					
					Gmap.showRegionPanel(response);
				
					Gmap.addPoint(response);	
				}
				
				//Gmap.bounds = Gmap.map.getBounds();
								
				if(typeof callback == "function")
					callback(marker, lat, lng, options);
				
				
				//return marker;
			},
			
			addPoint: function(response, resize, index) {
				var index = Gmap.region.length;
				var newRecord = Gmap.region.length == 0 ? true : false;
				
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
					map: Gmap.map,
					title: response.title,
					icon: Gmap.settings.theme_url+'/third_party/gmap/css/images/marker_orange.png'
				};
				
				var content 		= 'Are you sure you want to <a href="#" data-index="'+index+'" class="delete-point">Delete</a>?';
				var marker 			= new google.maps.Marker(options);
			
				marker.index 		= index;
				marker.lat 			= lat;
				marker.lng		 	= lng;
				
				Gmap.region[index] = marker;
				Gmap.totalPoints++;
				Gmap.ui.regionPanel.find('li:first-child').html('<p>Total Points: '+Gmap.totalPoints+'</p>');
				
				var infoWindow  = Gmap.window(marker, content, false);
				
				Gmap.windows.push(infoWindow);
				
				if(resize)	Gmap.map.fitBounds(Gmap.bounds);

				$t.trigger('gmapAddPoint', [response, infoWindow, Gmap]);
				
				google.maps.event.addListener(marker, 'dragend', function(event) {
					var lat = event.latLng.lat();
					var lng	= event.latLng.lng();
					
					Gmap.region[marker.index].lat = lat;
					Gmap.region[marker.index].lng = lng;
					
					$t.trigger('gmapPointDragEnd', [event, marker, Gmap]);
					
					Gmap.renderPolygon();
				});
				
				Gmap.renderPolygon();
			},
			
			renderPolygon: function() {
				
				var coords = [];
				
				$.each(Gmap.region, function(i, point) {
					if(point !== false) {
						coords.push(new google.maps.LatLng(point.lat, point.lng));
					}
				});
			
				var polygon = {
					map: Gmap.map,
					paths: coords,
					strokeColor: Gmap.ui.strokeColor.val(),
					strokeOpacity: parseFloat(Gmap.ui.strokeOpacity.val()),
					strokeWeight: parseFloat(Gmap.ui.strokeWeight.val()),
					fillColor: Gmap.ui.fillColor.val(),
					fillOpacity: parseFloat(Gmap.ui.fillOpacity.val())
				}
				
				if(Gmap.newRegion) { 
					Gmap.newRegion.setMap(null);
				}
				
				Gmap.newRegion = new google.maps.Polygon(polygon);

				google.maps.event.clearListeners(Gmap.newRegion, 'dblclick');
				google.maps.event.addListener(Gmap.newRegion, 'dblclick', function(e) {
					google.maps.event.trigger(Gmap.map, 'dblclick', e);
				});	
			},
			
			addRegion: function(response) {
			
				var index = Gmap.editRegion !== false ? Gmap.editRegion : Gmap.response.regions.results.length;
							
				if(!response) {
					var coords = [];
					var count  = 0;

					var response = {
						title: Gmap.ui.regionName.val() ? Gmap.ui.regionName.val() : 'Region '+(index+1),
						content: Gmap.ui.sideContentText.val(),
						total: 0,
						coords: []
					}
					
					$.each(Gmap.region, function(i, data) {
						if(data) {
							response.coords[count] = {lat: data.lat, lng:data.lng};
							
							var latlng = new google.maps.LatLng(data.lat, data.lng);
							
							coords.push(latlng);							
							Gmap.bounds.extend(latlng);
							
							count++;
						}
					});
					
					response.total = coords.length;
					
					response.style = {
						paths: coords,
						strokeColor: Gmap.ui.strokeColor.val(),
						strokeOpacity: parseFloat(Gmap.ui.strokeOpacity.val()),
						strokeWeight: parseFloat(Gmap.ui.strokeWeight.val()),
						fillColor: Gmap.ui.fillColor.val(),
						fillOpacity: parseFloat(Gmap.ui.fillOpacity.val())
					}
					
					var polygon = response.style;
				}
				else {
					var polygon = response.style;
				}
				
				Gmap.response.regions.results[index] = new google.maps.Polygon(polygon);
				
				Gmap.response.regions.total++;
								
				Gmap.totalPoints = 0;
					
				$t.trigger('gmapAddRegion', [response, Gmap]);
					
				google.maps.event.addListener(Gmap.response.regions.results[index], 'click', function(event) {
					if(Gmap.region.length == 0) {
						var options = {
							position: event.latLng,
							content: Gmap.buildInfoWindow(index, false, response.content, 'region')
						};
						
						if(Gmap.polyInfoWindow) Gmap.polyInfoWindow.close();
						
						Gmap.polyInfoWindow = new google.maps.InfoWindow(options);
						
						Gmap.polyInfoWindow.open(Gmap.map);
						
						$t.trigger('gmapRegionClick', [event, Gmap.response.regions.results[index], Gmap.polyInfoWindow, Gmap]);
					}
				});

				google.maps.event.addListener(Gmap.response.regions.results[index], 'dblclick', function(event) {
					if(Gmap.region.length > 0) {
						google.maps.event.trigger(Gmap.map, 'dblclick', event);
					}
				});
				
				Gmap.response.regions.results[index].saveData = response;
				Gmap.response.regions.results[index].setMap(Gmap.map);
				
				$.each(Gmap.region, function(i, data) {
					if(data.setMap) {
						data.setMap(null);
					}
				});
				
				Gmap.region = [];
				Gmap.ui.regionPanel.hide();
				
				Gmap.map.fitBounds(Gmap.bounds);
				Gmap.refresh(Gmap.response.regions.results);
				Gmap.editRegion = false;
				
				if(Gmap.newRegion) {
					Gmap.newRegion.setMap(null);
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
						'<a href="#" class="edit-'+type+'" data-type="'+Gmap.responseType+'" data-index="'+index+'" id="edit-'+type+'-'+index+'">Edit</a> | ',
						'<a href="#" class="delete-'+type+'" data-type="'+Gmap.responseType+'" data-index="'+index+'" id="delete-'+type+'-'+index+'">Delete</a>',
					'</div>'
				].join(' ');
				
				return content;
			},
			
			showHelp: function(target, options) {
				/*
				$('.ui-tooltip').qtip('destroy');
				
				if(Gmap.settings.display_help == 'yes') {		
					
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
				*/
			},
			
			init: function(options, callback) {
				
			    if(typeof options == "function") {
			    	callback = options;
			    	options = {};
			    }
			    
			    var lat		= Gmap.settings.latitude  ? parseFloat(Gmap.settings.latitude)  : 0;
			    var lng		= Gmap.settings.longitude ? parseFloat(Gmap.settings.longitude) : 0;
				var zoom 	= Gmap.settings.zoom 	  ? Gmap.settings.zoom 					: 0;
				
			    var latlng 	= new google.maps.LatLng(lat, lng);
			    	
			    if(Gmap.settings.zoom_field != "") {
					var fieldZoom = $('#'+Gmap.settings.zoom_field).val();
					
					if(fieldZoom != "" && !isNaN(fieldZoom)) {
						zoom = fieldZoom;
					}
			    }
			    
			    var defaultOptions = {
			      	zoom		: parseInt(zoom),
			      	center		: latlng,
			      	mapTypeId	: google.maps.MapTypeId.ROADMAP,
			      	scrollwheel	: Gmap.settings.scroll_wheel == 'yes' ? true : false,
			      	disableDoubleClickZoom: true
			    };
			    
			    options = $.extend(true, defaultOptions, options);
			    
			    Gmap.map = new google.maps.Map(Gmap.ui.canvas.get(0), options);
			    
			    google.maps.event.addListener(Gmap.map, 'tilesloaded', function() {
			    	if(!Gmap.isLoaded) {
					    $t.trigger('gmapInit', [Gmap]);				    
			    		
			    		Gmap.isLoaded = true;
			    	}
			    });
			    
			   	Gmap.center = Gmap.map.getCenter();
			    //Gmap.bounds = Gmap.map.getBounds();
			    Gmap.directionsRenderer.setMap(Gmap.map);
			    
			    if(Gmap.settings.zoom_field != "") {
			    	var zoom = Gmap.map.getZoom();
			    	
				    google.maps.event.addListener(Gmap.map, 'zoom_changed', function() {
				    	var zoom = Gmap.map.getZoom();
			    	
				    	Gmap.updateCustomField(Gmap.settings.zoom_field, zoom);
				    });
				    
				    Gmap.updateCustomField(Gmap.settings.zoom_field, zoom);
			    }
			    
			    google.maps.event.addListener(Gmap.map, 'dblclick', function(e) {
			    	var lat   = e.latLng.lat();
			    	var lng   = e.latLng.lng();
			    	var value = lat + ',' + lng;
			    	
			    	Gmap.geocode(value, function(results, status) {
			    		if(results) {
							results = Gmap.saveResponse(results[0], lat, lng);
							results.geometry.location.lat = lat;
							results.geometry.location.lng = lng;

							Gmap.addMarker(results, false);
						}
						else {
							alert(status);
						}
					});
			    });
			    
			    $t.find('#region-stroke-opacity-'+id).selectToUISlider({
					sliderOptions: {
						stop: function(e,ui) {
							Gmap.ui.strokeOpacity.change();
						}
					}
				});
				
			    $t.find('#region-stroke-weight-'+id).selectToUISlider({
					sliderOptions: {
						stop: function(e,ui) {
							Gmap.ui.strokeWeight.change();
						}
					}
				});				
				
			    $t.find('#region-fill-opacity-'+id).selectToUISlider({
					sliderOptions: {
						stop: function(e,ui) {
							Gmap.ui.fillOpacity.change();
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
					Gmap.renderPolygon();
				});
				
			    if(typeof callback == "function")
			    {
			    	callback(Gmap.map);
			    }
			    
			    return Gmap.map;
			},
			geocode: function(address, callback) {
				
			    $t.trigger('gmapGeocodeStart', [address, Gmap]);
			    
				(function () {  
					var geocoder = new google.maps.Geocoder();
					var search   = { 'address': address}
					var isCoord  = false;
					var latLng;

					if(address.match(/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/)) {
						var coord = address.split(',');
						latLng = new google.maps.LatLng(coord[0], coord[1]);

						search    = {'location': latLng};
						isCoord   = true;
					}

					geocoder.geocode(search, function(results, status) {
						//$('#'+Gmap.settings.response).val(results);
						if(isCoord && status == 'ZERO_RESULTS') {
							results = [{
								types: ['unknown'],
								formatted_address: '',
								address_components: [],
								geometry: {
									location: latLng,
									location_type: 'unknown',
									viewport: Gmap.bounds,
									bounds: Gmap.bounds
								}
							}];
							status = 'OK';
						};

						Gmap.results = results;
						
			   			$t.trigger('gmapGeocodeStop', [results, status, Gmap]);
			    
						callback(results, status);
					});
				})();		
			},
			
			populateIcons: function(obj, index) {
							
					console.log(obj.find('.gmap-icon').length);
						
				if(obj.find('.gmap-icon').length == 0) {
					
					Gmap.icons = [];
					
					if(obj) {
						obj.find('.gmap-icons').html('<ul></ul>');
					}
					
					$.each(icons, function(i, icon) {
						Gmap.icons[i] = new Image();
						Gmap.icons[i].src = icon.path;
						
						var selected = '';
						
						if(Gmap.markers[Gmap.editMarker].icon == icon.path)
							selected = 'class="selected"';
							
						//console.log('<li><a href="#" '+selected+' class="gmap-icon"><img src="'+icon.path+'" alt="'+icon.name+'" /></a></li>');
						
						obj.find('.gmap-icons ul').append('<li><a href="#" '+selected+' class="gmap-icon"><img src="'+icon.path+'" alt="'+icon.name+'" /></a></li>');
					});
				}
				else {
				
					if(obj) {
						obj.find('.gmap-icons li a').removeClass('selected');
					}
					
					$.each(Gmap.icons, function(i, icon) {
						if(index && Gmap.markers[index] && icon.src == Gmap.markers[index].icon) {
							obj.find('.gmap-icons ul li').eq(i).children('a').addClass('selected');
						}
					});
				}
					
			},
			
			showMarkerPanel: function(index, response) {
			
				if(typeof index == "object") {
					response = index;
					index = ((Gmap.markers.length - 1) > 0) ? Gmap.markers.length - 1 : 0;
				}
				
				Gmap.editMarker = index;
				Gmap.editMarkerData = Gmap.markers[index];
								
				if(response) {
					Gmap.ui.markerPanel.find('.address').html(response.formatted_address);
					
					Gmap.ui.markerPanel.find('input').val('');
					Gmap.ui.sideContentText.val('');
					
					if(response.title)
						Gmap.ui.markerPanel.find('input[name="marker-title"]').val(response.title);
				
					if(response.content) {
									
						var content = response.content;
						
						Gmap.ui.markerPanel.find('input[name="side-content"]').val(content);
						Gmap.ui.sideContentText.val(content);
					}
				}
				
				Gmap.populateIcons(Gmap.ui.markerPanel, index);				
				Gmap.ui.markerPanel.show();
				
				$t.trigger('gmapShowMarkerPanel', [Gmap.ui.markerPanel, Gmap]);
			},
						
			route: function() {
			
				var points = [];
												
				$t.find('ul.waypoints li').each(function() {
					var	$t 	  = $(this);
					var index = parseInt($t.attr('data-index'));
					
					points.push(Gmap.response.waypoints.results[index]);
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
						
						Gmap.remove(waypoints, 0);
						Gmap.remove(waypoints, waypoints.length - 1);
						
						var request = {
					        origin: start.location, 
					        destination: end.location,
					        waypoints: waypoints,
					        optimizeWaypoints: true,
					        travelMode: google.maps.DirectionsTravelMode.DRIVING
					    }
						
						$t.trigger('gmapRouteStart', [request, Gmap]);
											
						Gmap.directionsService.route(request, function(response, status) {
							if (status == google.maps.DirectionsStatus.OK) {
							
								$t.trigger('gmapRouteStop', [response, status, Gmap]);
								
								Gmap.response.waypoints.route = response;						
								Gmap.directionsRenderer.setDirections(response);
								
								Gmap.reorder();
							}
						});
					}
				}
			},
			
			refresh: function(response, list) {
				
				if(!list) {
					$(Gmap.ui.lists).each(function() {
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
							
							var html 		= '<li data-index="'+i+'"><a href="#" class="delete" data-type="'+Gmap.responseType+'" data-index="'+i+'"><span class="times">&times;</span></a>';
							
							if(data.address_components && Gmap.responseType == 'markers' || Gmap.responseType == 'waypoints') {
				
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
								
								html += '<a href="#" class="coordinate" data-type="'+Gmap.responseType+'" data-index="'+i+'">'+data.geometry.location.lat+','+data.geometry.location.lng+'</a></li>';										
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
				
				$t.trigger('gmapRefresh', [response, list, Gmap]);
				
				Gmap.ui.geocoder.val('');
				Gmap.reorder();
			},
			
			removeMarker: function(type, index) {
				
				var currentResponseType = Gmap.responseType;
				
				Gmap.responseType = type;
				
				var data = {};
				
				if(type == 'markers') {
					Gmap.response.markers.results[parseInt(index)] = false;

					if(typeof Gmap.markers[parseInt(index)].setMap == "function") {
						Gmap.markers[parseInt(index)].setMap(null);
						Gmap.markers[parseInt(index)] = false;
						Gmap.response.markers.total--;
						
						data = Gmap.response.markers.results;
							
						$t.trigger('gmapRemoveMarker', [index, data, Gmap]);
						
						Gmap.refresh(data);
					}
				}
				else if(type == 'waypoints') {
					if(Gmap.response.waypoints.total > 2) {
						Gmap.response.waypoints.results[index] = false;
						Gmap.response.waypoints.total--;
						Gmap.route();
						
						data = Gmap.response.waypoints.results;
						
						Gmap.refresh(data);
						
						$t.trigger('gmapRemoveWaypoint', [index, data, Gmap]);
					}
					else {
						alert('At least 2 points are required in waypoint mode.');
					}
				}
				else if(type == 'regions') {
					
					if(Gmap.polyInfoWindow)	{
						Gmap.polyInfoWindow.setMap(null);
					}
					
					Gmap.response.regions.results[index].setMap(null);
					Gmap.response.regions.results[index] = false;
					Gmap.response.regions.total--;
					
					data = Gmap.response.regions.results;
						
					$t.trigger('gmapRemoveRegion', [index, data, Gmap]);
					
					Gmap.refresh(data);
				}
				
				Gmap.bounds = Gmap.getBounds();
				
				Gmap.ui.wrapper.find('.toggle a[href="#'+Gmap.responseType+'"]').click();
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
				
				Gmap.ui.lists.each(function() {
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
									var data = Gmap.response.markers.results[index];
									
									response.markers.results[response.markers.results.length] = data;
									response.markers.total++;
									
									break;
									
								case 'waypoints':
									var data 	= Gmap.response.waypoints.results[index];
									
									response.waypoints.results[response.waypoints.total] = data;
									response.waypoints.total++;
									
									break;
									
								case 'regions':
									if( Gmap.response.regions.results[index]) {
									var data = Gmap.response.regions.results[index].saveData;
									
									response.regions.results[response.regions.total] = data;
									response.regions.total++;
									}
									break;
							}
						}
					});
					
					if(name == 'waypoints') {
						var route 	= Gmap.response.waypoints.route;
									
						if( Gmap.responseType == 'waypoints' && 
							typeof Gmap.response.waypoints.route.routes != "undefined") {
							
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
							
							$('#' + Gmap.settings.waypoint_start_coord).val(JSON.stringify(first));
							$('#' + Gmap.settings.waypoint_end_coord).val(JSON.stringify(last));
						}
					}
					
				});				
			
				var updateFields = [
					'#'+Gmap.settings.marker_field,
					'#'+Gmap.settings.waypoint_field,
					'#'+Gmap.settings.region_field
				];
				
				var updateValues = [
					JSON.stringify(response.markers.results),
					JSON.stringify(response.waypoints.results),
					JSON.stringify(response.regions.results)
				];
				
				Gmap.updateCustomField(updateFields, updateValues);
						
				Gmap.ui.input.html(JSON.stringify(response));
			},
			
			updateCustomField: function(setFields, values) {
				
				if(typeof setFields != "object") {
					setFields = [setFields];
				}
				
				if(typeof values != "object") {
					values = [values];
				}
				
				$.each(Gmap.fields, function(i, field) {					
					$.each(setFields, function(x, setField) {
						$('#'+setField.replace('#', '')).val(values[x]);					
						if(i == setField) {
							$('#'+field.field_name).val(values[x]);
							$('*[name="'+field.field_name+'"]').val(values[x]);
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
						Gmap.settings.latitude_field,
						Gmap.settings.longitude_field,
						Gmap.settings.formatted_address
					];
					
					var values = [lat, lng, address];
					
					Gmap.updateCustomField(fields, values);
				}
			},
			
			saveResponse: function(response, lat, lng) {
			
				var _return = response;
				
				$('#'+Gmap.settings.response).val(JSON.stringify(response));
				
				if(Gmap.responseType == 'markers' || Gmap.responseType == 'waypoints') {
				
					if(Gmap.responseType == 'markers') {
						if(Gmap.settings.total_points == 0 || Gmap.response.markers.total < Gmap.settings.total_points) {
							Gmap.response.markers.results[Gmap.response.markers.results.length] = response;
							
							Gmap.updateMarkerFields(response, lat, lng);

							response = Gmap.response.markers.results;
							Gmap.response.markers.total++;
							Gmap.overlimit.markers = false;
						}
						else {
							Gmap.overlimit.markers = true;
							alert('You are only allowed '+Gmap.settings.total_points+' marker per map.');
						}
					}
					else if(Gmap.responseType == 'waypoints') {
						Gmap.response.waypoints.results[Gmap.response.waypoints.results.length] = response;
						response = Gmap.response.waypoints.results;
						Gmap.response.waypoints.total++;
					}
					
					Gmap.refresh(response);
				}
								
				return _return;
			},
			
			resize: function(delay) {
				if(!delay)	var delay = 100;

				setTimeout(function () {
					google.maps.event.addListener(Gmap.map, 'resize', function() {
						Gmap.map.setCenter(Gmap.center);
					});
					
					google.maps.event.trigger(Gmap.map, 'resize');
					
				}, delay);
			},
			
			showRegionPanel: function(newRegion) {
				if(Gmap.editRegion === false && Gmap.region.length == 0) {				
					var color = Gmap.colors[Math.floor(Math.random()*(Gmap.colors.length))];
					
					while(Gmap.lastColor == color) {
						color = Gmap.colors[Math.floor(Math.random()*(Gmap.colors.length))];
					}
					
					Gmap.lastColor = color;
					Gmap.ui.regionPanel.find('input[name="region-stroke-color"]').val(color.stroke);
					Gmap.ui.regionPanel.find('input[name="region-fill-color"]').val(color.fill);				
				}
				else {
					color = Gmap.lastColor;
				}	
						
				Gmap.ui.sideContentText.val('');
				Gmap.ui.regionContent.val('');
				
				if(Gmap.editRegion !== false) {
					
					var data  = Gmap.response.regions.results[Gmap.editRegion].saveData;
					var style = data.style;
					
					var color = {
						stroke: style.strokeColor,
						fill: style.fillColor
					}
					
					Gmap.ui.regionPanel.find('select[name="region-stroke-weight"]').val(style.strokeWeight).change();
					Gmap.ui.regionPanel.find('select[name="region-stroke-opacity"]').val(style.strokeOpacity).change();
					Gmap.ui.regionPanel.find('select[name="region-fill-opacity"]').val(style.fillOpacity).change();						Gmap.ui.regionContent.val(data.content);
					Gmap.ui.sideContentText.val(data.content);
				}
				
				if(Gmap.region.length == 0) {
					$t.find('.show-color-picker').each(function(i) {
						var $t = $(this);
						var id = $t.attr('data-show');
						var fieldName = $t.attr('data-field');
						
						$('#'+id).children('div').farbtastic(function(color) {
							var field = $('#'+fieldName);
							field.css('backgroundColor', color);
							field.val(color);
							Gmap.renderPolygon();
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
				
				Gmap.ui.regionPanel.show();
			},
			
			window: function(marker, options, open) {
				if(typeof options == "string") {
					options = {
						content: options
					};
				}
				
				if(typeof open == "undefined") open = true;
				
				var infoWindow = new google.maps.InfoWindow(options);
				
				$.each(Gmap.windows, function(i) {
					Gmap.windows[i].close();
				});
								
				if(Gmap.responseType == "markers" && open === true)
					infoWindow.open(Gmap.map, marker);				
				
				google.maps.event.addListener(marker, 'click', function() {
					$.each(Gmap.windows, function(i) {
						Gmap.windows[i].close();
					});
								
					infoWindow.open(Gmap.map, marker);
				});
				
				return infoWindow;
			}
		}
		
		Gmap.ui.strokeColor.blur(function() {
			Gmap.renderPolygon();		
		});
			
		Gmap.ui.fillColor.blur(function() {
			Gmap.renderPolygon();		
		});
		
		Gmap.ui.strokeOpacity.change(function() {
			Gmap.renderPolygon();
		});
			
		Gmap.ui.strokeWeight.change(function() {
			Gmap.renderPolygon();
		});
			
		Gmap.ui.fillOpacity.change(function() {
			Gmap.renderPolygon();
		});
							
		Gmap.ui.button.click(function() {
			var value = Gmap.ui.geocoder.val();
			
			Gmap.geocode(value, function(results, status) {
				if(status == "OK") {
					if(results.length > 1) {
						
						Gmap.ui.suggestions.fadeIn('fast');
						Gmap.ui.suggestionStatistics.html(results.length+' possible locations');
						
						Gmap.ui.suggestions.find('li').remove();
						
						$.each(results, function(i, data) {
							Gmap.ui.suggestions.children('ul').append('<li data-index="'+i+'"><a href="#" data-index="'+i+'">'+data.formatted_address+'</a><li>');
						});						
					}
					else {
						
						Gmap.ui.suggestions.hide();
					
						var lat = results[0].geometry.location.lat();
						var lng = results[0].geometry.location.lng();
						
						results = Gmap.saveResponse(results[0], lat, lng);
					
						Gmap.addMarker(results);
						Gmap.ui.geocoder.val('');
						
						Gmap.showHelp('2');
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
		
		Gmap.ui.lists.sortable({
			axis: 'y',
			placeholder: 'placeholder',
			update: function(event, ui) {
				Gmap.reorder();
				
				if(Gmap.responseType == 'waypoints')
					Gmap.route();
				
				//$('.gmap-popup-window.step-4').hide();					
			}
		}).disableSelection();
		
		Gmap.ui.geocoder.keypress(function(e) {
			if(e.keyCode == 13) {
				Gmap.ui.button.click();
				return false;
			}
		});
		
		Gmap.ui.deferer.click(function() {
			Gmap.init(function() {

				/* 	Fixes all the cropping and resizing bugs people complained about,
					and it does it better than any way anyone recommended. */
				
				Gmap.bounds = new google.maps.LatLngBounds();
				
				if(!Gmap.bounds) { 
				
					$('#hide_field_12').parent().bind('drag', function() {
						Gmap.resize(500);
					});
				
					$('.content_tab a').click(function() {
						Gmap.resize();
					});
					
					$t.parents('.main_tab').find('label > span').click(function() {
						Gmap.resize();
					});
					
				}
				
				Gmap.showHelp('1');
				
				Gmap.ui.lists.each(function() {
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
								 
						Gmap.responseType = name;
						Gmap.ui.lists.removeClass('active');
						list.addClass('active');
						
						$.each(data, function(i, response) {
							if(response != null) {
								switch (name) {
									case 'markers':
										var lat = response.geometry.location.lat;
										var lng = response.geometry.location.lng;
										
										response = Gmap.saveResponse(response, lat, lng);
										
										var extendBounds = true;
										var zoom = $('#'+Gmap.settings.zoom_field).val();
										
										if(Gmap.settings.zoom_field != "" && zoom != "") {
											zoom = parseInt(zoom);	
											Gmap.map.setZoom(zoom);
											Gmap.map.setCenter(new google.maps.LatLng(lat, lng));
											extendBounds = false;
										}
						
										Gmap.addMarker(response, extendBounds, false);
										
										break;
									
									case 'waypoints':
										var lat = response.geometry.location.lat;
										var lng = response.geometry.location.lng;
										
										response = Gmap.saveResponse(response, lat, lng);
										
										Gmap.addMarker(response);
										
										break;
									
									case 'regions':
										Gmap.region = response;
										
										var coords = [];
										
										$.each(Gmap.region.coords, function(i, coord) {
											var latlng =  new google.maps.LatLng(coord.lat, coord.lng);
											Gmap.bounds.extend(latlng);
											coords[coords.length] = latlng;
										});
										
										var response = {
											title: 		Gmap.region.title,
											content: 	Gmap.region.content,
											total: 		Gmap.region.total,
											coords:		Gmap.region.coords,
											style:	{
												paths: 			coords,
												strokeColor: 	Gmap.region.style.strokeColor,
												strokeOpacity:	Gmap.region.style.strokeOpacity,
												strokeWeight:	Gmap.region.style.strokeWeight,
												fillColor:		Gmap.region.style.fillColor,
												fillOpacity:	Gmap.region.style.fillOpacity
											}
										}
										
										Gmap.addRegion(response);
										
										break;
								}
							}						
						});
					}
					
					$(Gmap.ui.toggle.get(0)).click();

				});
				
			});
			
			return false;
		});
		
		if(Gmap.settings.defer_init == 'no')
			Gmap.ui.deferer.click();
		
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
		
		Gmap.importArray = function(array, count) {
		
			var marker = array[count];
			
			$imported = Gmap.ui.importPanel.find('.step-3 .total-imported');
			$failed = Gmap.ui.importPanel.find('.step-3 .total-failed');
			
			Gmap.geocode(marker.location, function(results, status) {
				if(status == 'OK' && results) {
					var lat = results[0].geometry.location.lat();
					var lng = results[0].geometry.location.lng();
								
					results = Gmap.saveResponse(results[0], lat, lng);
					results.icon = marker.icon;
					results.content = marker.content;
					results.geometry.location.lat = lat;
					results.geometry.location.lng = lng;
					
					Gmap.addMarker(results);
				
					var index = Gmap.markers.length - 1;
										
					if(marker.title != "") {
						Gmap.response.markers.results[index].title = marker.title;
					}
					
					if(marker.content != "") {
						content = Gmap.buildInfoWindow(index, true, marker.content);
									
						Gmap.markers[index].content = marker.content;					
						Gmap.windows[index].setContent(content);
					}
					
					var progress = Math.ceil(count / array.length * 100);
					
					Gmap.ui.importPanel.find('.step-3 .progress-bar').progressbar({value: progress});
					
					$imported.html(parseInt($imported.html())+1);
				}
				else {
					importErrors++;
					console.log(importErrors);
					Gmap.ui.importPanel.find('.step-3 textarea').attr('rows', importErrors);
					Gmap.ui.importPanel.find('.step-3 textarea').append('['+(count+2)+'] Error Geocoding: '+marker.location+"\r\n");
					$failed.html(parseInt($failed.html())+1);					
				}
				
				if(count < array.length - 1) {
					setTimeout(function() {
						Gmap.importArray(array, count + 1);
					}, 1000);										
				}
				else {
					Gmap.ui.importPanel.find('.step-3 .progress-bar').progressbar({value: 100});
				}
				
				Gmap.refresh(Gmap.response.markers.results, 'markers');
			});
		}
		
		Gmap.ui.importPanel = $('.gmap-import-panel');		
		Gmap.ui.importPanel.find('.step-1').attr('action', action).ajaxForm({
			//dataType: 'json',
			success: function(response) {
			
				importData = response;
				
				var options = ['<option value="">--</option>'];
				
				$.each(response.columns, function(i, column) {
					options.push('<option value="'+column+'">'+column+'</option>');	
				});
				
				Gmap.ui.importPanel.find('.step-1, .step-3').hide();
				Gmap.ui.importPanel.find('.step-2').show();
				Gmap.ui.importPanel.find('.step-2 select').show().html(options.join(''));
				
				Gmap.populateIcons(Gmap.ui.importPanel);
			}
		});
		
		Gmap.ui.importPanel.find('form .create-tags').change(function() {
			var $t    = $(this);
			var tags  = $t.next('.tags');
			var value = $t.val();
			var name  = $t.attr('name');
						
			tags.append('<div class="tag">'+value+'<a href="#">&times;</a><input type="hidden" value="'+value+'" name="'+name+'" /></div>');
			
			$t.val('');
		});
		
		Gmap.ui.importPanel.find('.tag a').live('click', function(e) {
			var $t = $(this);
			
			$t.parent().fadeOut(function() {
				$(this).remove();	
			});
			
			e.preventDefault();
		});
		
		Gmap.ui.importPanel.find('.step-2').submit(function(e) {
			
			var markers = [];
			
			$.each(importData.rows, function(i, row) {
			
				var title   = '';
				var content = '';
				var location = '';
				var icon = '';
				
				Gmap.ui.importPanel.find('.step-2 .title input').each(function() {
					if($(this).val() != "") {
						title += row[$(this).val()] + ' ';
					}
				});
				
				Gmap.ui.importPanel.find('.step-2 .content input').each(function() {
					if($(this).val() != "") {
						content += row[$(this).val()] + ' ';
					}
				});				
				
				Gmap.ui.importPanel.find('.step-2 .geocode input').each(function() {
					if($(this).val() != "") {
						location += row[$(this).val()] + ' ';
					}
				});
				
				var icon = Gmap.ui.importPanel.find('.gmap-icon.selected img').attr('src');
				
				var lat = Gmap.ui.importPanel.find('.step-2 .latitude select').val();
				var lng = Gmap.ui.importPanel.find('.step-2 .longitude select').val();
								
				markers.push({
					title: $.trim(title).replace(/\s/g, " "),
					content: $.trim(content).replace(/\s/g, " "),
					location: $.trim(location).replace(/\s/g, " "),
					latitude: (lat != '' ? row[lat] : false),
					longitude: (lng != '' ? row[lng] : false),
					icon: (icon ? icon : false)
				});	
				
				console.log(markers[markers.length - 1]);			
	
			});
			
			Gmap.ui.importPanel.find('.step-3 .total-markers').html(markers.length);
			Gmap.ui.importPanel.find('.step-3 .total-imported').html(0);
			Gmap.ui.importPanel.find('.step-3 .total-failed').html(0);
			Gmap.ui.importPanel.find('.step-3 .progress-bar').progressbar({value: 0});
			Gmap.ui.importPanel.find('.step-3 textarea').html('');
			Gmap.ui.importPanel.find('.step-2, .step-1').hide();
			Gmap.ui.importPanel.find('.step-3').show();
			
			Gmap.importArray(markers, 0);
			
			/*
			Gmap.geocode(markers[0].location, function(data) {
				
			});
			
			$.each(markers, function(i, marker) {
				if(!marker.latitude || !marker.longitude) {
					alert(marker.location);
					Gmap.geocode(marker.location, function(data) {
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
		
		
		Gmap.ui.importPanel.find('.step-3 button').click(function(e) {
			Gmap.ui.importPanel.fadeOut();
			
			e.preventDefault();
		});
		
		Gmap.ui.suggestion.live('click', function() {
			var $t = $(this);
			var index = parseInt($t.attr('data-index'));
			var lat = Gmap.results[index].geometry.location.lat();
			var lng = Gmap.results[index].geometry.location.lng();
			
			var response = Gmap.saveResponse(Gmap.results[index], lat, lng);
			
			Gmap.addMarker(response);
			Gmap.ui.suggestions.fadeOut('fast');

			Gmap.showHelp('3');
			
			return false;
		});
		
		Gmap.ui.toggle.click(function(obj) {

			var $t = $(this);
			var href = $t.attr('href').replace('#', '');
			
			Gmap.responseType = href;
			
			Gmap.ui.lists.hide();
			Gmap.ui.lists.removeClass('active');
			Gmap.ui.toggle.removeClass('active');
			Gmap.ui.suggestions.hide();
			
			if(Gmap.isLoaded) {
				Gmap.ui.geocoder.focus();
			}
			
			//Gmap.showHelp(true);
			
			Gmap.ui.lists.each(function() {
				var $t = $(this);
				
				if($t.hasClass(href)) {
					Gmap.ui.regionCancelButton.click();
					
					if(href == 'regions' && Gmap.region.length > 0) {
						Gmap.showRegionPanel(true);
					}
					
					$t.addClass('active');
					$t.show();
				}
			});
			
			if(href == 'markers')
				Gmap.refresh(Gmap.response.markers.results);
			else if(href == 'waypoints')
				Gmap.refresh(Gmap.response.waypoints.results);
			else if(href == 'regions')
				Gmap.refresh(Gmap.response.regions.results);
			
			$t.addClass('active');
			
			return false;
		});
		
		$(Gmap.ui.toggle.get(0)).click();
		
		Gmap.ui.closeButtons.click(function() {
			$(this).parent().fadeOut('fast');
			
			return false;
		});
		
		Gmap.ui.deleteButton.live('click', function() {
			var $t	  	= $(this);
			var index	= $t.attr('data-index');
			var type 	= $t.attr('data-type');
			
			Gmap.removeMarker(type, index);
				
			return false;
		});
					
		Gmap.ui.markerCancelButton.click(function() {
		
			var marker = Gmap.response.markers.results[Gmap.editMarker]
			var icon = marker.icon;
			var title = marker.title;
			var content = marker.content;
			
			Gmap.markers[Gmap.editMarker].icon = icon ? icon : '';
			
			Gmap.markers[Gmap.editMarker].setIcon(Gmap.markers[Gmap.editMarker].icon);
			
			Gmap.ui.markerPanel.fadeOut();
			
			return false;
		});		
		
		Gmap.ui.markerSaveButton.click(function() {
			
			var index = Gmap.editMarker;
			
			var marker = Gmap.response.markers.results[index];
			
			marker.title 	= Gmap.ui.markerName.val();
			marker.content	= Gmap.ui.sideContentText.val();
			marker.icon		= Gmap.markers[Gmap.editMarker].icon;
			
			Gmap.response.markers.results[index] = marker;
			
			if(marker.content != '') {
				var hasContent  = marker.content ? false : marker;
				var content     = marker.content ? marker.content : false;
				
				content = Gmap.buildInfoWindow(index, hasContent, content);
								
				Gmap.markers[index].title = marker.title;
				Gmap.markers[index].content = marker.content;
				
				Gmap.windows[index].setContent(content);
			}
			
			Gmap.ui.markerPanel.fadeOut();
				
			Gmap.refresh(Gmap.response.markers.results, 'markers');
			
			$t.trigger('gmapSaveMarker', [index, marker, Gmap.windows[index], Gmap]);
						
			return false;
		});
			
		Gmap.ui.regionCancelButton.click(function() {
			
			if(Gmap.editRegion !== false) {
				Gmap.addRegion(Gmap.response.regions.results[Gmap.editRegion].saveData);
			}
			else {
				var region		= Gmap.region;
				
				$.each(region, function(i, data) {
					data.setMap(null);
				});
				
				Gmap.region = [];
				Gmap.ui.regionPanel.hide();
			}
			
			if(Gmap.newRegion) {
				Gmap.newRegion.setMap(null);
			}
			
			Gmap.totalPoints = 0;
			Gmap.editRegion  = false;
				
			return false;
		});
		
		Gmap.ui.regionSaveButton.click(function() {
			Gmap.addRegion();
			
			return false;
		});
		
		Gmap.ui.importPanel.find('.gmap-icon').live('click', function() {
			var $t = $(this);
						
			if(!$t.hasClass('selected')) {
				Gmap.ui.importPanel.find('.gmap-icon').removeClass('selected');
				$t.addClass('selected');				
			}
			else {
				$t.removeClass('selected');
			}
			
			return false;
		});
		
		Gmap.ui.markerPanel.find('.gmap-icons a').live('click', function() {
			var $t = $(this);
			var index = $t.parent().index();
			var path = icons[index].path;
		
			if(!$t.hasClass('selected')) {	
				Gmap.ui.markerPanel.find('.gmap-icons a').removeClass('selected');
			
				Gmap.markers[Gmap.editMarker].setIcon(path);
				
				$t.addClass('selected');				
			}
			else {
				Gmap.markers[Gmap.editMarker].setIcon('');
				$t.removeClass('selected');
			}
			
			return false;
		});
				
		Gmap.ui.lists.find('a.coordinate').live('click', function() {
			var $t 	 	= $(this);
			var type 	= $t.data('type');
			var index   = parseInt($t.data('index'));
			
			if(type == "markers") {
				google.maps.event.trigger(Gmap.markers[index], 'click');			
			}
			
			return false;
		});
		
		Gmap.ui.deletePoint.live('click', function() {
			var $t = $(this);
			var index = parseInt($t.attr('data-index'));
			
			Gmap.region[index].setMap(null);
			Gmap.region[index] = false;
			Gmap.totalPoints--;
			
			Gmap.ui.regionPanel.find('li:first-child').html('Total Points: '+Gmap.totalPoints);
			
			Gmap.renderPolygon();
				
			return false;
		});
		
		Gmap.ui.deleteMarker.live('click', function() {
			var $t = $(this);
			var index = parseInt($t.attr('data-index'));
			
			Gmap.removeMarker('markers', index);
			Gmap.ui.markerPanel.hide();
			
			return false;
		});
		
		Gmap.ui.editMarker.live('click', function() {
			var $t = $(this);
			var index = parseInt($t.attr('data-index'));
			var response = Gmap.response.markers.results[index];
			
			Gmap.showMarkerPanel(index, response);
						
			return false;
		});
		
		Gmap.ui.editRegion.live('click', function() {
			var $t = $(this);
			var index = parseInt($t.attr('data-index'));
			var regionData = Gmap.response.regions.results[index].saveData;
			
			$gmap.find('.toggle a[href="#regions"]').click();
			Gmap.responseType = 'regions';
			
			Gmap.lastRegion = regionData;
			Gmap.response.regions.results[index].setMap(null);
			Gmap.polyInfoWindow.setMap(null);
			Gmap.editRegion = index;
					
			Gmap.showRegionPanel(Gmap.editRegion);
				
			$.each(regionData.coords, function(i, data) {
				Gmap.addPoint(data, false)		
			});
			
			return false;
		});
		
		Gmap.ui.deleteRegion.live('click', function() {
			var $t = $(this);
			var index = parseInt($t.attr('data-index'));
			
			Gmap.removeMarker('regions', index);
			
			return false;
		});
		
		Gmap.ui.upload.click(function() {
			
			if(Gmap.ui.importPanel.css('display') == 'block') {
				Gmap.ui.importPanel.fadeOut('fast');	
			}
			else {
				Gmap.ui.importPanel.find('.step-1').show();
				Gmap.ui.importPanel.find('.step-2, .step-3').hide();
				Gmap.ui.importPanel.fadeIn('fast');
				
				if(!Gmap.icons) {
					Gmap.icons = [];					
					Gmap.ui.markerPanel.find('.gmap-icons').html('<ul></ul>');
					
					$.each(icons, function(i, icon) {
						Gmap.icons[i] = new Image();
						Gmap.icons[i].src = icon.path;						
						Gmap.ui.importPanel.find('.gmap-icons ul').append('<li><a href="#" class="gmap-icon"><img src="'+icon.path+'" alt="'+icon.name+'" /></a></li>');
					});
				}
				else {
					Gmap.ui.importPanel.find('.gmap-icons li a').removeClass('selected');
				}
			}
			
			Gmap.ui.importPanel.position({
				my: 'left top',
				at: 'left bottom',
				of: $(this).parent(),
				offset: "0 10"
			});
			
			return false;
		});
		
		Gmap.ui.sideContent.focus(function() {
			Gmap.ui.sideContentPanel.fadeIn('fast');
			Gmap.ui.sideContentText.focus();
		});
		
		Gmap.ui.sideContentText.blur(function() {
			Gmap.ui.markerPanel.find('input[name="side-content"]').val($(this).val());
			Gmap.ui.sideContentPanel.fadeOut('fast');
		});
		
		Gmap.ui.sideContentText.keyup(function() {
			Gmap.ui.sideContent.val($(this).val());
		});
		
		Gmap.ui.showStatistics.live('click', function() {
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
		
		$t.parents('form').submit(function() {
			
			var title 	= $('#title').val();

			var _return = true;
			
			if(title == '') {
				alert('You must enter a title before saving the entry. If you submit your phone with errors, the map data with not be saved.');
				
				return false;
			}
			
			return _return;
		});
		
		/* Load the Third-party JS plugins */
		if(!GmapPluginsLoaded)
		{
		    $.each(Gmap.plugins, function(i, url) {
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

		GmapGlobal.object.push(Gmap);
	});
});