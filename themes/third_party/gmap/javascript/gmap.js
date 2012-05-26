/*
// Array Remove - By John Resig (MIT Licensed)
Array.prototype.remove = function(from, to) {
  var rest = this.slice((to || from) + 1 || this.length);
  this.length = from < 0 ? this.length + from : from;
  return this.push.apply(this, rest);
};
*/
var Gmap = {
	
	bounds		: [],
	markers		: [],
	response 	: [],
	settings 	: [],
	windows		: [],
			
	/**
	 * Init
	 *
	 * Initializes a new Google Map
	 *
	 * @access	public
	 * @param	object
	 * @param	int
	 * @param	int
	 * @param	mixed
	 * @param	function
	 * @return	object
	 */
	
	init: function(obj, lat, lng, options, callback) {
	
	    if(typeof options == "function") {
	    	callback = options;
	    	options = {};
	    }
	    
	    lat	= lat ? lat : 0;
	    lng	= lng ? lng : 0;
	    
	    var latlng 	= new google.maps.LatLng(lat, lng);
	    
	    var defaultOptions = {
	      zoom: 		12,
	      center: 		latlng,
	      mapTypeId: 	google.maps.MapTypeId.ROADMAP
	    };
	    
	    options = $.extend(true, defaultOptions, options);
	    
	    var gmap = new google.maps.Map(obj, options);
	    
	    if(typeof callback == "function")
	    	callback(gmap);
	    
	    return gmap;
	},
	
	geocode: function(address, callback) {
		(function () {  
	 		var geocoder = new google.maps.Geocoder();
	 		
			geocoder.geocode( { 'address': address}, function(results, status) {
				callback(results, status);
			});
		})();		
	},
	
	window: function(map, marker, options) {
		if(typeof options == "string")
			options = {content: options};
			
		var infoWindow = new google.maps.InfoWindow(options);
		
		google.maps.event.addListener(marker, 'click', function() {
			infoWindow.open(map, marker);
		});
		
		return infoWindow;
	},
	
	/**
	 * Zoom
	 *
	 * Zooms a Google Map
	 *
	 * @access	public
	 * @param	object
	 * @param	int
	 * @param	function
	 * @return	object
	 */
	 
	zoom: function(obj, level, callback) {
		if(typeof callback != "function")
			callback = function() {};
			
		obj.setZoom(level, callback);
		
		return this;
	},
	
	/**
	 * Center
	 *
	 * Centers a Google Map
	 *
	 * @access	public
	 * @param	object
	 * @param	int
	 * @param	int
	 * @param	function
	 * @return	object
	 */
	
	center: function(obj, lat, lng, callback) {
		if(typeof callback != "function")
			callback = function() {};
			
		var coord = new google.maps.LatLng(lat, lng);
		
		obj.setCenter(coord, callback);
		
		return this;
	},
	
	/**
	 * Marker
	 *
	 * Add a marker to a Google Map
	 *
	 * @access	public
	 * @param	object
	 * @param	int
	 * @param	int
	 * @param	mixed
	 * @param	function
	 * @return	object
	 */
	
	marker: function(obj, lat, lng, options, callback) {
		if(typeof options == "function") {
			callback = options;
			options = {}
		}
		
		options = $.extend({
			position: new google.maps.LatLng(lat, lng),
			map: obj,
			title: ''
		}, options);
		
		var marker = new google.maps.Marker(options);
					
		if(typeof callback == "function")
			callback(marker, lat, lng, options);
		
		return marker;
	},

	removeMarker: function(marker, callback) {
		if(marker) {		
			marker.setMap(null);
			
			if(typeof callback == "function")
				callback(marker);
		}
		
		return this;	
	}

}