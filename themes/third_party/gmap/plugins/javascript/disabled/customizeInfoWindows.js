/**
 * Customize Info Windows
 * 
 * This plugins allows you to disable the infowindows or just parts
 * of their functionality like edit and delete links.
 *
 * @package     Google Maps for ExpressionEngine
 * @subpackage  JavaScript API
 * @author      Justin Kimbrell
 * @copyright   Copyright (c) 2012, Objective HTML
 * @link        http://www.objectivehtml.com/google-maps/api/plugins
 * @version     1.0.1
 * @build       20120407
 */
  
$(document).ready(function() {

    /*==============*
     * Setup Plugin *
     *=========================================================================*
     *
     * Simply set the desired variables to 'true'.
     *
     * removeInfoWindow - Removes the infowindow completely if set to TRUE.
     *
     * removeEditLink	- Remove the edit link from the infowindow
     * 
     * removeDeleteLink - Remove the delete link from the infowindow
     *
     * autoCloseWindow  - If TRUE, this automatically closes each window so
     *					  they aren't displayed when new markers are added.  
     *					  This variable doesn't remove them completely.
     *     
    /*=========================================================================*/
	

	var removeInfoWindow  = false;
	

	var removeEditLink    = true;
	

	var removeDeleteLink  = true;
	
	
	var autoCloseWindow   = false;




    /*==============*
     * Plugin Begin *
     *=========================================================================*/

    //Assumes only a single Gmap is the page.
	var Gmap       = GmapGlobal.object[0];
	var content

	Gmap.bind('gmapInit', function(Gmap) {

		var infowindow;

		$.each(Gmap.markers, function(index, marker) {
			customizeMarker(index, Gmap.windows[index]);
		});

	});

    Gmap.bind('gmapAddMarker', function(index, response, infowindow, Gmap) {
    	
    	customizeMarker(index, infowindow);

	});
	
	Gmap.bind('gmapMarkerDragEnd', function(index, response, infowindow, Gmap) {
	    
    	customizeMarker(index, infowindow);

	});

	function customizeMarker(index, infowindow) {

    	content = infowindow.getContent();

	    if(removeEditLink) {
	    	removeLink('edit', index);
	    }

	    if(removeDeleteLink) {
	    	removeLink('delete', index);
	    }

	    infowindow.setContent(content);

    	if(autoCloseWindow) {
	    	infowindow.close();
	    }

	    if(removeInfoWindow) {
	    	google.maps.event.addListener(Gmap.markers[index], 'click', function() {
	    		infowindow.open(null)
	    	});
	    	
	    	infowindow.close();
	    }

	}

	function removeLink(type, index) {

		capType = 'Edit';
		
		if(type == 'delete') {
			capType = 'Delete';
		}

		var link = [
			'|',
			'<a href="#" class="'+type+'-marker" data-type="markers" data-index="'+index+'" id="'+type+'-marker-'+index+'">'+capType+'</a>'
		];

    	for(var i = 0; i < link.length; i++) {
	    	content = content.replace(link[i], '');
	    }

	    return content;
	}


});