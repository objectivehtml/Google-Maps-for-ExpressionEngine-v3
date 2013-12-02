(function($) {
	
	PhotoFrame.Buttons.Geolocation = PhotoFrame.Button.extend({
		
		/**
		 * An array of button objects
		 */
		
		buttons: [],
		
		/**
		 * An object of classes
		 */
		
		classes: {
			loading: 'photo-frame-loading'
		},
		
		/**
		 * The button description 
		 */
		
		description: false,
		
		/**
		 * The latitude
		 */
		
		lat: false,
		
		/**
		 * The longitude
		 */
		
		lng: false,
		
		/**
		 * The location string
		 */
		
		loc: '',
		
		/**
		 * An array of markers
		 */
		
		markers: [],
		
		/**
		 * Name of the button
		 */
		
		name: false,
		
		/**
		 * Name of the button
		 */
		
		icon: 'location',
		
		/**
		 * Should Photo Frame render the photo after removing the layer
		 */	
		 
		renderAfterRemovingLayer: false,
		
		/**
		 * The JSON object used for Window settings 
		 */
		
		windowSettings: {
		 	css: 'photo-frame-geolocation',		
			title: false,
			width: 490
		},
		
		constructor: function(buttonBar) {
			var t = this;
			
			this.name				  = PhotoFrame.Lang.geolocation;
			this.description		  = PhotoFrame.Lang.geolocation_desc;
			this.windowSettings.title = PhotoFrame.Lang.geolocation;

			this.base(buttonBar);
		},
				
		enable: function() {
		},
		
		disable: function() {
		},

		removeLayer: function() {
			this.removeManipulation();
			this.updateJson();
		},
		
		refresh: function() {
			if(this.marker) {
				this.addManipulation(true, {
					lat: this.lat,
					lng: this.lng,
					loc: this.loc,
					zoom: this.map ? this.map.getZoom() : false
				});
			}	
			else {
				this.lat = '';
				this.lng = '';
				this.loc = '';	
			}
			
			this.window.ui.lat.val(this.lat);
			this.window.ui.lng.val(this.lng);
			this.window.ui.loc.val(this.loc);
		
			this.updateJson();			
		},
		
		enable: function() {
			this.window.ui.lat.attr('disabled', false);
			this.window.ui.lng.attr('disabled', false);
			this.window.ui.loc.attr('disabled', false);
		},
		
		disable: function() {
			this.window.ui.lat.attr('disabled', true);
			this.window.ui.lng.attr('disabled', true);
			this.window.ui.loc.attr('disabled', true);
		},
		
		toggleLayer: function(visibility, render) {
			this.base(visibility, false);

			if(render) {
				this.updateJson();
			}
			
			if(this.marker) {
				this.marker.setVisible(visibility);
			}
		},
		
		addMarker: function(lat, lng, populate) {
			var t = this;
			
			if(typeof lat === "object") {
				lng = lat.lng();
				lat = lat.lat();
			}
			
			this.lat = lat;
			this.lng = lng;
			
			if(this.marker) {
				this.marker.setMap(null);
			}
		
			this.marker = new google.maps.Marker({
				map: this.map,
				position: new google.maps.LatLng(lat, lng),
				draggable: true
			});
			
			google.maps.event.addListener(this.marker, 'dragend', function(e) {					
				t.lat = e.latLng.lat();
				t.lng = e.latLng.lng();
				
				t.refresh();
			});
			
			if(this.map) {
				this.map.setCenter(new google.maps.LatLng(lat, lng));
			}
			
			if(typeof populate === "undefined" || populate !== false) {
				this.window.ui.lat.val(lat);
				this.window.ui.lng.val(lng);
			}
			
			t.refresh();
		},
		
		removeLayer: function() {
			if(this.marker) {
				this.marker.setMap(null);
				this.marker = false;
			}
			if(this.map) {
				this.map.setCenter(this.geo.getPosition());
			}
			this.lat = false;
			this.lng = false;
			this.loc = false;
			this.base();
			this.refresh();
		},
		
		startCrop: function() {
			var t = this;		
			
		},
		
		buildWindow: function() {	
			this.base({ buttons: this.buttons });
			
			var t = this, html = $([
				'<div class="photo-frame-geolocation-form two-columns photo-frame-grid">',
					'<div class="photo-frame-column">',
						'<div class="photo-frame-inline photo-frame-location clearfix photo-frame-margin-bottom">',
							'<div class="photo-frame-label"><label for="photo-frame-location">'+PhotoFrame.Lang.location+'</label></div>',
							'<div class="photo-frame-input"><input type="text" name="photo-frame-location" id="photo-frame-location" placeholder="'+PhotoFrame.Lang.enter_location+'" /></div>',
							'<a href="#" class="photo-frame-tool-window-button">'+PhotoFrame.Lang.locate+'</a>',
						'</div>',
						'<hr>',
						'<div class="photo-frame-inline clearfix  photo-frame-margin-bottom">',
							'<div class="photo-frame-label"><label for="photo-frame-lat">'+PhotoFrame.Lang.lat+'</label></div>',
							'<div class="photo-frame-input"><input type="text" name="photo-frame-lat" id="photo-frame-lat" placeholder="" /></div>',
						'</div>',
						'<div class="photo-frame-inline clearfix photo-frame-margin-bottom">',
							'<div class="photo-frame-label"><label for="photo-frame-lng">'+PhotoFrame.Lang.lng+'</label></div>',
							'<div class="photo-frame-input"><input type="text" name="photo-frame-lng" id="photo-frame-lng" placeholder="" /></div>',
						'</div>',
					'</div>',
					'<div class="photo-frame-column">',
						'<div id="photo-frame-map"></div>',
					'</div>',
				'</div>'
			].join(''));
			
			this.window.ui.content.html(html);
			this.window.ui.map = html.find('#photo-frame-map');
			this.window.ui.loc = html.find('#photo-frame-location');
			this.window.ui.btn = html.find('a');						
			this.window.ui.lat = html.find('#photo-frame-lat');						
			this.window.ui.lng = html.find('#photo-frame-lng');
			
			this.window.ui.loc.keypress(function(e) {
				if(e.keyCode == 13) {
					t.window.ui.btn.click();
					e.preventDefault();
				}
			});
			
			html.find('#photo-frame-lat, #photo-frame-lng').keyup(function() {
				var lat = html.find('#photo-frame-lat').val();
				var lng = html.find('#photo-frame-lng').val();
				
				t.addMarker(lat, lng);
			});
			
			t.bind('metaLatLng', function(lat, lng) {
				t.lat = lat;
				t.lng = lng;
		    	t.addMarker(t.lat, t.lng);
				t.refresh();
			});
			
			t.bind('metaStaticMap', function(map) {
				map.html('');
				if(t.lat !== false && t.lng !== false) {
					map.html('<img src="https://maps.googleapis.com/maps/api/staticmap?markers='+t.lat+','+t.lng+'&size=400x160&scale=2&sensor=true&zoom=14">')
				}
			});
			
			t.bind('metaStartCrop', function() {
				t.lat = false;
				t.lng = false;
			});
			
			this.window.ui.btn.click(function(e) {
				var m = t.getManipulation();
				
				if(!m || m.visible) {
					t.loc = t.window.ui.loc.val();
					
					t.geocoder.geocode(
						{
							address: t.loc
						}, function(data, status) {
							if(status == google.maps.GeocoderStatus.OK) {
								var lat = data[0].geometry.location.lat();
								var lng = data[0].geometry.location.lng();
								
								t.addMarker(lat, lng);
							}	
						}
					);
				}
				
				e.preventDefault();
			});
			
			google.maps.visualRefresh = true;
			
			this.geocoder = new google.maps.Geocoder();
			
			this.bind('metaLatLng', function(lat, lng) {
				t.lat = lat;
				t.lng = lng;
			});
			
			this.bind('windowOpenEnd', function(window) {
				if(window.title == 'Geolocation') {	
					var m = t.getManipulation();
					
					if(!t.map) {		
						t.map = new google.maps.Map(t.window.ui.map.get(0), {
							disableDoubleClickZoom: true,
							zoom: m && m.data.zoom ? m.data.zoom : 8,
							center: new google.maps.LatLng(0, 0),
							mapTypeId: google.maps.MapTypeId.ROADMAP
						});
					}
					
					if(!t.geo) {
						t.geo = new GeolocationMarker(t.map);	
					}
					else {
						t.map.setCenter(t.geo.getPosition());
						t.geo.setMap(t.map);
					}
					
					google.maps.event.addListener(t.map, 'zoom_changed', function(e) {
						var zoom = t.map.getZoom();
						
						t.refresh();
					});
					
					google.maps.event.addListener(t.map, 'dblclick', function(e) {
						var m = t.getManipulation();
						
						if(!m || m.visible) {
							t.addMarker(e.latLng);
						}
					});
					
					google.maps.event.addListenerOnce(t.geo, "position_changed", function(e) {
						if(!t.marker) {
							t.map.setCenter(t.geo.getPosition());
						}
			        });
			        
					if(t.getManipulation()) {
						var m = t.getManipulation();
						
				        if(m) {
					     	t.loc = m.data.loc;
					     	t.lat = m.data.lat;
					     	t.lng = m.data.lng;
					     	
					     	t.addMarker(t.lat, t.lng);
				        }	
				        
						t.refresh();	
					}  
				}
			});
		}
	});

}(jQuery));