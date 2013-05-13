(function($) {

	PhotoFrame.Buttons.Geolocation = PhotoFrame.Button.extend({
		
		/**
		 * An array of button objects
		 */
		
		buttons: [],
		
		/**
		 * The button description 
		 */
		
		description: 'Geolocate the photo with a latitude and longitude.',
		
		/**
		 * The button icon 
		 */
		
		icon: 'location',
		
		/**
		 * Name of the button
		 */
		
		name: 'Geolocation',
		
		/**
		 * The JSON object used for Window settings 
		 */
		
		windowSettings: {
			title: false
		},
		
		constructor: function(buttonBar) {
			var t = this;
			
			this.windowSettings.title = 'Geolocation';
			
			this.buttons = [{
				text: 'Add Location',
				css: 'photo-frame-tool-window-save',
				onclick: function(e) {
					t.apply();
				}
			}];

			t.base(buttonBar);
		},
		
		buildWindow: function() {
			this.base({ buttons: this.buttons });
			
			var html = $([
				'<div class="photo-frame-input">',
					'<label for="photo-frame-geolocation-location">Location</label>',
					'<input type="text" name="location" value="" id="photo-frame-geolocation-location" />',
				'</div>',
				'<div class="photo-frame-input">',
					'<div class="photo-frame-gmap"></div>',
				'</div>'
			].join(''));
			
			this.window.ui.content.html(html);
		}
		
	});
	
}(jQuery));