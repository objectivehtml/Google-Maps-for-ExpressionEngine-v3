<?php

$lang = array(

'gmap_module_name'                      => 'Google Maps for ExpressionEngine',
'gmap_module_description'               => 'The complete geolocation and mapping toolkit.',

/*
'gmap_center'                           => 'Center Location',
'gmap_center_description'               => 'Alternatively, enter an location (address, city, state, zip code, etc) that will be use Google\'s geocoder to get the coordinate to be used as the center.',
*/

'gmap_geocoder_field_label'             => 'Geocoder Field Label',
'gmap_geocoder_field_label_description' => 'You can change the default label for the main Geocoder field in the Fieldtype to better suit you and your client\'s needs.',

'gmap_geocoder_button'                  => 'Geocoder Button Text',
'gmap_geocoder_button_description'      => 'Plot Location',

'gmap_geocoder_field_place'             => 'Geocoder Field Placeholder',
'gmap_geocoder_field_place_description' => 'You can change the default placeholder for the main Geocoder field in the Fieldtype to better suit you and your client\'s needs.',

'gmap_latitude' 						=> 'Latitude',
'gmap_latitude_description' 			=> 'You can set the default map center by entering a latitude & longitude.',

'gmap_longitude' 						=> 'Longitude',
'gmap_longitude_description'			=> 'You can set the default map center by entering a latitude & longitude.',

'gmap_zoom' 							=> 'Zoom',
'gmap_zoom_description'					=> 'Set the default zoom level.',

'gmap_zoom_one_marker' 					=> 'One Marker Zoom',
'gmap_zoom_one_marker_description'		=> 'When adding markers to the map, the zoom will go as detailed as possible, while still maintaining visibility of all the markers and regions on the map. This often times leaving the user confused. Override this setting by defining a default zoom for the first marker.',

'gmap_total_points'						=> 'Maximum Points',
'gmap_total_points_description' 		=> 'The maximum points the user is allowed to add to the map. Note, this applies to markers, waypoints, and points for regions.<br> (0 = Unlimited)',

'gmap_min_points'						=> 'Minimum Points',
'gmap_min_points_description' 			=> 'The minimum points the user is allowed to add to the map. Note, this applies to markers, waypoints, and points for regions.<br> (0 = No minimum)',

'gmap_map_height'						=> 'Map Height',
'gmap_map_height_description' 			=> 'Enter a custom height for the map to appear in the control panel. (The default height is "600px")',

'gmap_preview'							=> 'Map Preview',
'gmap_preview_description'				=> 'The map preview is an interactive of demo representing the fieldtype once it\'s installed. The functionality of the demo is limited in comparison to the actual fieldtype, but hopefully it helps you figure out how a lot of these option are intended to be used. You can also start adjusting the map and it will automatically fill in the default center and zoom of the fieldtype.',

'gmap_marker_mode'						=> 'Enable Marker Mode?',
'gmap_marker_mode_description'			=> 'If you enable Marker Mode, you will allow users to add basic markers to the map.',

'gmap_file_group'						=> 'File Directory',
'gmap_file_group_description'			=> 'You can choose a file upload directory that contain icons that the user can choose from and add to the map. Any icon in the directory will be available within the <i>Marker Options</i> panel. You may optionally use the default icon set, if you only need basic functionality. If no icon set is selected, the default icon from Google Maps will be used. Default map icons are credited to <a href="http://mapicons.nicolasmollet.com/" title="Map Icons">Map Icons Collection</a>.',

'gmap_latitude_field'					=> 'Latitude Field',
'gmap_latitude_field_description'		=> 'You can choose a custom field within the channel to store a single latitude degree. If you use this field, the user will be limited to one location per entry. Use this field anytime you need the latitude stored inside a single column. This field is required to recall entries with a defined radius.',

'gmap_longitude_field'					=> 'Longitude Field',
'gmap_longitude_field_description'		=> 'You can choose a custom field within the channel to store a single longitude degree. If you use this field, the user will be limited to one location per entry. This field is required to recall entries within a defined radius.',

'gmap_zoom_field' 						=> 'Zoom Field',
'gmap_zoom_field_description'			=> 'If a zoom field is defined, the field will receive the zoom level of the map. This is useful if the user needs to control the zoom level from the backend.',

'gmap_marker_field' 					=> 'Marker Field',
'gmap_marker_field_description'			=> 'This field will receive the JSON string for the individual marker data. This field generally does not need to be used unless you have some programmatic use for it.',

'gmap_response'							=> 'Geocoder Response',
'gmap_response_description'				=>	'If defined, this custom field will receive the entire JSON response. This is useful if you need to recall detailed information returned from the geocoder.',

'gmap_formatted_address'				=> 'Formatted Address',
'gmap_formatted_address_description'	=>	'If defined, this custom field will be populated with the entire address string returned by the geocoder.',

'gmap_defer_init'						=> 'Defer loading map until user click?',
'gmap_defer_init_description'			=> 'If you choose to defer the map loading, it won\'t initialize until the user clicks it.',

'gmap_scroll_wheel'						=> 'Use scroll-wheel as zoom control?',
'gmap_scroll_wheel_description'			=> 'If you choose to enable the scrollwheel zooming feature, you can control the map\'s zoom level with scrolling. By default this option is disabled for better UX.',

'gmap_waypoint_mode'					=> 'Enable Waypoint Mode?',
'gmap_waypoint_mode_description'		=> 'When waypoint mode is enabled, the markers on the map become waypoints for the Google Directions service. When waypoint mode is enable, a minimum of two points are required to be on the map before it\'s valid.',

'gmap_waypoint_start_coord'				=> 'Starting Waypoint Coordinate Field',
'gmap_waypoint_start_coord_description'	=> 'A field that will receive the coordinate of the first waypoint in the route.',

'gmap_waypoint_end_coord'				=> 'Ending Waypoint Coordinate Field',
'gmap_waypoint_end_coord_description'	=> 'A field that will receive the coordinate of the last waypoint in the route.',

'gmap_waypoint_route_data'				=> 'Route Data Field',
'gmap_waypoint_route_data_description'	=> 'A textarea field that will receive the JSON string with all the route information. This field generally does not need to be used unless you have some programmatic use for it.',

'gmap_region_mode'						=> 'Enable Region Mode?',
'gmap_region_mode_description'			=> 'When Region mode is enabled, a toggle will appear on the map. The user can toggle to add coordinates that are part of polygon shapes. Effectively, the user can add any shape to any part of the map.',

'gmap_region_field'						=> 'Region Field',
'gmap_region_field_description'			=> 'A textarea field that will receive the JSON string about all regions on the map. This field generally does not need to be used unless you have some programmatic use for it.',

'gmap_display_help'						=> 'Display help tips?',
'gmap_display_help_description'			=> 'While not exactly difficult, the process to the plot markers, routes, and regions on a map has several steps than can potentially confuse users for the first few times. <br><br> Do you want to display help balloons within the fieldtype to give more information to user about the plotting process?',

'gmap_google_map_render_bug'			=> 'Map not displaying properly? Sometimes Google Maps bugs out and the map won\'t render properly. If this happens, refresh your browser.',

'gmap_settings_flyout_helper'			=> 'Enter two locations for a brief demonstration of functionality. The route shown will not have anything to do with the settings or points in your channel.',

'gmap_include_marker_title'				=> 'Include Marker Title field?',
'gmap_include_marker_title_description' => 'The marker title field allows you to name your markers something other than the geocoded address. This field can also be used for programmatic purposes.',

'gmap_no_valid_location'                => 'Custom Error Message',
'gmap_no_valid_location_description'    => 'Enter a custom message that will prompt users when entries with invalid locations are submitted (overrides the default).',

'gmap_over_marker_limit'                => 'You are only allowed to have {total} {markers} on the map.',
'gmap_under_marker_limit'               => 'You must have at least {total} {markers} on the map.',

'gmap_starting_point'                   => 'Starting Point',
'gmap_ending_point'						=> 'Ending Point',
'gmap_field_settings'					=> 'Field Settings',
'gmap_click_to_activate'				=> 'Click to Activate',
'gmap_map_route'						=> 'Map Route',
'gmap_close'							=> 'Close',
'gmap_empty_fieldtype'					=> 'You have not added any locations to the map.',
'gmap_geocoder'							=> 'Geocoder',
'gmap_geocoder_placeholder'				=> 'Enter an address, city, state, zipcode, or coordinate',
'gmap_geocode'							=> 'Geocode',
'gmap_step_1_of_2'						=> 'Step 1 of 2',
'gmap_step_1_of_2_description'			=> 'Enter a starting location and press <b>Geocode</b>',
'gmap_step_2_of_2'						=> 'Step 2 of 2',
'gmap_step_2_of_2_description'			=> 'Enter an ending location and press <b>Geocode</b>',
'gmap_whats_next'						=> 'What\'s Next?',
'gmap_whats_next_description'			=> 'Try entering more waypoints',
'gmap_try_dragging_routes'				=> 'Try dragging the routes...',
'gmap_hint'								=> 'Hint',


'gmap_field_required' => 'You must enter a location in the {name} field.',


//
''=>''
);