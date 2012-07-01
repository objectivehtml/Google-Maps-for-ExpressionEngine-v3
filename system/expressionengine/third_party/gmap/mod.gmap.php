<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.0.184
 * @build		20120617
 */

Class Gmap {
	
	private $reserved_terms = array('', '_min', '_max', '_like');
	
	private $args = array(
		
		/* Google Map Parameters */
		'map' => array(
			'backgroundColor', 'disableDefaultUI', 'disableDoubleClickZoom', 'draggable', 
			'draggableCursor', 'heading', 'keyboardShortcuts', 'mapTypeControl', 
			'mapTypeControlOptions', 'mapTypeId', 'maxZoom', 'minZoom', 'noClear', 
			'overviewMapControl', 'overviewMapControlOptions', 'panControl', 'panControlOptions', 			
			'rotateControl', 'rotateControlOptions', 'scaleControl', 'scaleControlOptions', 
			'scrollwheel', 'streetView', 'streetViewControl', 'streetViewControlOptions', 
			'tilt', 'title', 'zoomControl', 'zoomControlOptions', 'zoom'
		),
		
		'marker' => array(
			'animation', 'clickable', 'cursor', 'draggable', 'flat', 'icon', 'map', 'optimized',
			'raiseOnDrag', 'shadow', 'shape', 'title', 'visible', 'zIndex', 'entry_id'
		),
		
		/* EE Channel Parameters */
		'channel' => array(
		    'author_id', 'backspace', 'cache', 'refresh', 'cat_limit', 'category', 
		    'category_group', 'isable','channel', 'display_by', 'dynamic', 'dynamic_start',
	   		'entry_id', 'entry_id_from', 'entry_id_to', 'fixed_order', 'group_id', 'limit', 
	   		'month_limit', 'offset', 'orderby', 'paginate', 'paginate_base', 'paginate_type', 			'related_categories_mode', 'relaxed_categories', 'require_entry', 'show_current_week', 
	   		'show_expired', 'show_future_entries', 'show_pages', 'sort', 'start_day', 'start_on', 
	   		'status', 'stop_before', 'sticky', 'track_views', 'uncategorized_entries', 'url_title', 
	   	 	'username', 'week_sort', 'year', 'month', 'day'
	   	 ),
		
		/* Plugin & Convenience Parameters */
		'plugin' => array(
			'center', 'channel', 'hide_markers', 'open_windows', 'map_type', 'id', 'class', 'style',
			'style_link', 'style_obj', 'extend_bounds', 'show_one_window', 'icon', 'show_coordinate',
			'add_title_to_dropdown', 'metric', 'offset', 'distance', 'cache_post', 'show_sql', 'require', 
			'name'
		),		
		
		/* Dynamic and Static fields */
		'fields' => array(
			'address_field', 'city_field', 'state_field', 'zipcode_field', 'country_field', 
			'latitude_field', 'longitude_field', 'zoom_field', 'address', 'latitude', 'longitude',
			'gmap_field', 'lat_lng'
		)
		
	);
		
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->config('gmap_config');
		$this->EE->load->library('google_maps');
		$this->EE->load->library('static_maps');
	}
		
	public function init()
	{
		$map_id      = $this->param('id', 'map');
		$map_type    = $this->param('map_type', 'google.maps.MapTypeId.ROADMAP');
		$map_type    = $this->param('mapTypeId', $map_type);
		$latitude    = $this->param('latitude', 0);
		$longitude   = $this->param('longitude', 0);
		$center      = $this->param('center', FALSE);
		$center      = $center && $center != 'false' && $center != 'no' ? $center : FALSE;
		$position    = $this->EE->google_maps->latlng($latitude, $longitude);
		
		$address     = $this->param('address');
		$client_side = $this->param('client_side');

		if($center)
		{

			if(!$client_side)
			{					
				$center 	= $this->EE->google_maps->geocode($center, 1);
				
				$center_lat	= $center[0]->results[0]->geometry->location->lat;
				$center_lng = $center[0]->results[0]->geometry->location->lng;
				$center		= $this->EE->google_maps->center($map_id, $center_lat, $center_lng);
			}
			else
			{
				$center  	= $this->EE->google_maps->return_js($this->EE->google_maps->geocode_js($map_id, $center, '
					var lat = response[0].geometry.location.lat();
					var lng = response[0].geometry.location.lng();
					
					'.$this->EE->google_maps->center_js($map_id, 'lat', 'lng').'
				'));
			}

			/* -------------------------------------------
			/* 'gmap_init_center hook.
			/*  - Modify the map's center when initialized
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_init_center', $center);			
				
				if($edata !== NULL) $center = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
		}

		$extend		= !$center && $position == 'true' || !$center && $position == 'yes' ? TRUE : FALSE;
		
		$map_type 	= 'google.maps.MapTypeId.'.strtoupper(str_replace('google.maps.MapTypeId.', '', $map_type));
		
		$default_options = array(
			'zoom'		=> $this->param('zoom', 12),
			'center' 	=> str_replace(';', '', $this->EE->google_maps->latlng($latitude, $longitude, FALSE)),
			'mapTypeId' => $map_type
		);

		/*
		url: '../images/heart30.png',
        height: 26,
        width: 30,
        anchor: [4, 0],
        textColor: '#ff00ff',
        textSize: 10
	
		*/

		$map_options 	= array_merge($default_options, $this->get_options('map'));
		
		$map_options['clusterMaxZoom']  = $this->param('clusterMaxZoom', $this->param('cluster_max_zoom', 0));
		$map_options['clusterGridSize'] = $this->param('clusterGridSize', $this->param('cluster_grid_size', 0));
		$map_options['clusterStyles']   = '['.$this->param('clusterStyles', $this->param('cluster_styles', '')).']';

		/* -------------------------------------------
		/* 'gmap_init_params hook.
		/*  - Modify the map's initialization parameters
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_init_params', $map_options);			
			
			if($edata !== NULL) $map_options = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$return			= $this->EE->google_maps->init($map_id, $map_options, $this->get_options());
		
		$return			= $center ? $return . $center : $return;
		
		$return 		.= $this->marker();
		
		if($styles = $this->param('styles') || $this->param('style_var'))
		{
			$json_obj =  $this->param('style_var') ?  $this->param('style_var') : 'stylez';
			
			$return .= $this->EE->google_maps->return_js('
			var '.$map_id.'_styleType = new google.maps.StyledMapType('.$json_obj.', '.$map_id.'_options);
			'.$map_id.'_map.mapTypes.set(\''.$map_id.'_styleType\', '.$map_id.'_styleType);
			'.$map_id.'_map.setMapTypeId(\''.$map_id.'_styleType\');');
		}
		
		if($this->param('script_tag', TRUE, TRUE))
		{
			$lang	= $this->param('language', 'en');
			$key    = $this->param('key', '');
			$sensor	= $this->param('sensor', 'true');

			$this->EE->load->library('theme_loader');
			
			$url = rtrim($this->EE->theme_loader->theme_url(), '/') . '/';

			$return = '
			<link rel="stylesheet" href="'.$url.'third_party/gmap/css/infobox.css" media="screen, projection">
		
			<script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor='.$sensor.'&key='.$key.'&language='.$lang.'"></script>
			<script type="text/javascript" src="'.rtrim($url, '/').'/third_party/gmap/javascript/infobox.js"></script>
			<script type="text/javascript" src="'.rtrim($url, '/').'/third_party/gmap/javascript/markerclusterer.js"></script>
			' . $return;
		}
		
		if(!$map_id)
		{
			show_error('You must define an id parameter before continuing. This parameter must be a valid JavaScript variable with no hyphens, special characters, spaces, and cannot begin with a number.');
		}
		
		if($styles = $this->param('styles'))
		{
			$return = '<script src="'.$styles.'"></script>' . $return;
		}
	
		/* -------------------------------------------
		/* 'gmap_init_javascript' hook.
		/*  - Modify the map's JavaScript before it's returned
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_init_javascript', $return);			
			
			if($edata !== NULL) $return = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $return;
	}
	
	public function clean_js()
	{
		return $this->EE->google_maps->clean_js($this->EE->TMPL->tagdata);
	}
	
	public function marker()
	{			
		$return		= NULL; 
		$zoomfit	= TRUE;
		$map_id 	= $this->param('id', 'map');
		$options	= $this->get_options('marker');
		$limit		= $this->param('limit');
		$offset		= $this->param('offset');
		
		$default_options = array(
			'map'		=> $map_id.'_map',
		);
		
		$options	= array_merge($default_options, $options);

		/* -------------------------------------------
		/* 'gmap_marker_params' hook.
		/*  - Modify the map's center when initialized
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_marker_params', $options);			
			
			if($edata !== NULL) $options = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		//Since the marker method is called with every init() method, it must 
		//return NULL if no valid parameters exist.
		if(!$this->param('latitude') && !$this->param('longitude') && !$this->param('address'))
		{
			return NULL;
		}
		
		//If user doesn't want to pass information to geocoder for limitation reasons
		//this param is way to opt-out of the system. By default you are opt-in.
		if($this->param('geocode', TRUE, TRUE))
		{
			$coordinate	= $this->param('latitude') .','.$this->param('longitude');
			$query 		= ($address = $this->param('address')) ? $address : str_replace(array('<p>', '</p>'), '', $coordinate);
			
			$response 	= $this->EE->google_maps->geocode($query, $limit, $offset);		
			
			foreach($response as $row)
			{
				if($row->status == 'ZERO_RESULTS')
					return NULL;
			}
			
			$latitude	= $response[0]->results[0]->geometry->location->lat;
			$longitude	= $response[0]->results[0]->geometry->location->lng;
		}
		else
		{
			$latitude  = $this->param('latitude');
			$longitude = $this->param('longitude');
			
			$response = array(
				(object) array(
					'results' => array(
						(object) array(
							'geometry' => (object) array(
								'location' => (object) array(
									'lat' => $latitude,
									'lng' => $longitude
								)
							)
						)
					)
				)
			);
		}
		
		$position	= $this->param('extend_bounds', 'true');
		$zoom		= $this->param('zoom');
		$center		= $this->param('center', FALSE);
		$center		= $center && $center != 'false' && $center != 'no' ? $center : FALSE;
		$extend		= !$center && $position == 'true' || !$center && $position == 'yes' ? TRUE : FALSE;
		$position	= $this->EE->google_maps->latlng($latitude, $longitude);
		
		$content = $this->EE->google_maps->clean_js($this->EE->TMPL->tagdata);
		
		if($center)
		{
			$center 	= $this->EE->google_maps->geocode($center, 1);
			$center_lat	= $center[0]->results[0]->geometry->location->lat;
			$center_lng = $center[0]->results[0]->geometry->location->lng;
			$center_js	= $this->EE->google_maps->center($map_id, $center_lat, $center_lng);
			
			/* -------------------------------------------
			/* 'gmap_marker_center' hook.
			/*  - Modify the map's center when initialized
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_marker_center', $center_js);			
				
				if($edata !== NULL) $center_js = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
			
			$return		.= $center_js;
		}
		
		if($zoom)
		{
			$extend		 = FALSE;
			$center_lat	 = $response[0]->results[0]->geometry->location->lat;
			$center_lng  = $response[0]->results[0]->geometry->location->lng;				
			$return 	.= $this->EE->google_maps->center($map_id, $center_lat, $center_lng);
			$zoom_js 	 = $this->EE->google_maps->zoom($map_id, $zoom);
			
			/* -------------------------------------------
			/* 'gmap_marker_zoom' hook.
			/*  - Modify the map's zoom when initialized
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_marker_zoom', $zoom_js);			
				
				if($edata !== NULL) $zoom_js = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
			
			$return 	.= $zoom_js;
		}
		
		$class			= 'ui-infowindow';
		$close_button 	= $this->param('close_button', 'http://www.google.com/intl/en_us/mapfiles/close.gif');
		$margin			= '';
		
		$marker_js = $this->EE->google_maps->marker(array(
			'id' 					=> $map_id, 
			'options' 				=> $options,
			'data'					=> $response,
			'limit'					=> $this->param('limit', FALSE),
			'offset'				=> $this->param('offset', 0),
			'extend_bounds'			=> $extend,
			'infobox'				=> $this->param('infobox', FALSE, TRUE),
			'infowindow'			=> array(
				'options' 	=> array(
					'alignBottom'			 => $this->param('alignBottom', 'false', FALSE),
					'boxClass'				 => $this->param('class', $class),
					'boxStyle'				 => $this->param('style', ''),
					'clearanceX'			 => $this->param('clearanceX', 0),
					'clearanceY'			 => $this->param('clearanceY', 0),
					'closeBoxMargin'		 => $this->param('closeBoxMargin', ''),
					'closeBoxURL'		 	 => $this->param('closeBoxURL', $close_button),
					'inner_class'		     => $this->param('inner_class', 'ui-infobox-content'),
					'content'				 => $content,
					'disableAutoPan'		 => $this->param('disableAutoPan', 'false', FALSE),
					'enableEventPropagation' => $this->param('enableEventPropagation', 'false', FALSE),
					'maxWidth'				 => $this->param('maxWidth', '0'),
					'offsetX'				 => $this->param('offsetX', '0'),
					'offsetY'				 => $this->param('offsetY', '0'),
					'isHidden'				 => $this->param('isHidden', 'false', FALSE),
					'pane'					 => $this->param('pane', 'floatPane'),
					'zIndex'				 => $this->param('zIndex', 'null')
				),
				'content'				 => $content,
				'show_one_window'		 => $this->param('show_one_window', FALSE),
				'open_windows'			 => $this->param('open_windows', $this->param('open_window', FALSE, TRUE), TRUE),
				'script_tag' 	  		 => $this->param('script_tag', FALSE)
			),
			'category'				=> $this->param('category', FALSE),
			'exclude_single_marker' => $this->param('exclude_single_marker', TRUE, TRUE),
			'clustering'            => $this->param('clustering', FALSE, TRUE),
			'duplicate_markers'     => $this->param('duplicate_markers', FALSE, TRUE),
			'window_trigger'        => $this->param('window_trigger', 'click'),
			'redirect'        		=> $this->param('redirect', FALSE, TRUE)
		));
		
		/* -------------------------------------------
		/* 'gmap_marker_javascript' hook.
		/*  - Modify the map's marker before returned
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_marker_javascript', $marker_js);			
			
			if($edata !== NULL) $marker_js = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $return . $marker_js;
	}
	
	public function geocode()
	{
		$query 		= $this->param('query');
		$limit 		= $this->param('limit');
		$offset 	= $this->param('offset') !== FALSE ? (int) $this->param('offset') : 0;
		
		if($query === FALSE)
		{
			show_error('You must define a query – either an address or latitude and longitude – before you can use the geocode method.');
		}
				
		/* -------------------------------------------
		/* 'gmap_geocode_query' hook.
		/*  - Modify the query before sent to the geocoder
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_geocode_query', $query);			
			
			if($edata !== NULL) $query = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
			
		$response = $this->EE->google_maps->geocode($query, $limit, $offset);
				
		/* -------------------------------------------
		/* 'gmap_geocode_response' hook.
		/*  - Modify the query after sent to the geocoder
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_geocode_response', $response);			
			
			if($edata !== NULL) $response = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
				
		if($response[0]->status == 'ZERO_RESULTS')
		{
			return $this->EE->TMPL->no_results();
		}
		else
		{
			$vars = $this->EE->google_maps->parse_geocoder_response($response, $limit, $offset);
				
			/* -------------------------------------------
			/* 'gmap_geocode_parse' hook.
			/*  - Modify the query before sent to the geocoder
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_geocode_parse', $vars);			
				
				if($edata !== NULL) $vars = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
			
			return $this->parse($vars);
		}
	}
	
	public function center()
	{
		$map_id		= $this->param('id', 'map');
		$address	= $this->param('address', FALSE);
		$latitude	= $this->param('latitude', 0);
		$longitude  = $this->param('longitude', 0);
		
		if($address)
		{
			$latitude 	= $address;
			$longitude 	= FALSE;
		}
		
		$response = $this->EE->google_maps->center($map_id, $latitude, $longitude);
		
		/* -------------------------------------------
		/* 'gmap_center' hook.
		/*  - Modify the JavaScript before returned
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_center', $response, $map_id, $latitude, $longitude);			
			
			if($edata !== NULL) $response = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $response;
	}
	
	public function directions()
	{
		$origin	 		= $this->param('origin');
		$destination 	= $this->param('destination');
		
		if(empty($origin) || empty($destination))
		{
			return $this->EE->TMPL->no_results();
		}
		
		$directions 	= $this->EE->google_maps->directions($origin, $destination, array(
			'waypoints' => urlencode($this->param('waypoints', ''))
		));
		
		/* -------------------------------------------
		/* 'gmap_directions_response' hook.
		/*  - Modify the directions response after it returns
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_directions_response', $directions);			
			
			if($edata !== NULL) $directions = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		if($directions->status != 'OK')
		{
			return $this->EE->TMPL->no_results();
		}		
		
		$response 		= $directions;
				
		$instructions 	= array();
		
		if(!isset($directions->routes))
			return $this->EE->TMPL->no_results();
			
		$vars = array();
		
		foreach($directions->routes as $index => $route)
		{
			/* -------------------------------------------
			/* 'gmap_directions_route' hook.
			/*  - Modify the direction route
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_directions_route', $route);			
				
				if($edata !== NULL) $route = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
			
			$legs = array();
			
			foreach($route->legs as $leg_index => $leg)
			{
				/* -------------------------------------------
				/* 'gmap_directions_leg' hook.
				/*  - Modify the direction leg
				/*  - Added v3.0
				*/
					$edata = $this->EE->extensions->call('gmap_directions_leg', $leg);			
					
					if($edata !== NULL) $leg = $edata;
					
					if ($this->EE->extensions->end_script === TRUE) return;
				/*
				/* -------------------------------------------*/
				
				$legs[$leg_index] = array();
				
				$legs[$leg_index]['start_address'] 	= $leg->start_address;
				$legs[$leg_index]['end_address'] 	= $leg->end_address;
				$legs[$leg_index]['steps'] 			= array();
								
				$legs[$leg_index] = $this->merge(array(
					'distance', 'duration'
				), $legs[$leg_index], $leg, 'text', 'value');
				
				$legs[$leg_index] = $this->merge(array(
					'start_location', 'end_location'
				), $legs[$leg_index], $leg, 'lat', 'lng');
				
				$steps = array();
				
				foreach($leg->steps as $step_index =>  $step)
				{
					/* -------------------------------------------
					/* 'gmap_directions_step' hook.
					/*  - Modify the direction step
					/*  - Added v3.0
					*/
						$edata = $this->EE->extensions->call('gmap_directions_step', $step);			
						
						if($edata !== NULL) $step = $edata;
						
						if ($this->EE->extensions->end_script === TRUE) return;
					/*
					/* -------------------------------------------*/
					
					$instructions[] = $step->html_instructions;
					
					$steps[$step_index]['html_instructions'] 	= $step->html_instructions;
					$steps[$step_index]['travel_mode'] 			= $step->travel_mode;
						
					$steps[$step_index] = $this->merge(array(
						'duration', 'distance'
					), $steps[$step_index], $step, 'text', 'value');
					
					$steps[$step_index] = $this->merge(array(
						'start_location', 'end_location'
					), $steps[$step_index], $step, 'lat', 'lng');
				}
				
				$legs[$leg_index]['steps'] = $steps;
			}
			
			$id		= $this->param('id') ? ' id="'.$this->param('id').'"' : NULL;
			$class 	= $this->param('class') ? ' class="'.$this->param('class').'"' : NULL;
			 
			$instruction_html 	= '<ol'.$id.''.$class.'>';
			
			foreach($instructions as $instruction_index => $instruction)
			{
				$class  = $instruction_index % 2 == 0 ? 'odd' : 'even';
				
				$html = '<li class="'.$class.'" data-index="'.$instruction_index.'">'.$instruction.'</li>';
				
				/* -------------------------------------------
				/* 'gmap_directions_instructions_html' hook.
				/*  - Modify the instructions HTML
				/*  - Added v3.0
				*/
					$edata = $this->EE->extensions->call('gmap_directions_instructions_html', $html);			
					
					if($edata !== NULL) $html = $edata;
					
					if ($this->EE->extensions->end_script === TRUE) return;
				/*
				/* -------------------------------------------*/
				
				$instruction_html .= $html;
			}
			
			$instruction_html .= '</ol>';
			
			$legs[$leg_index]['total_steps']	= count($legs[$leg_index]['steps']);
			
			$vars[$index] = array(
				'instructions' => $instruction_html,
				'bounds' => array(
					array(
						'northeast'		=> array(
							array(
								'lat' => $route->bounds->northeast->lat,
								'lng' => $route->bounds->northeast->lng,
							),
						),
						'southwest'		=> array(
							array(
								'lat' => $route->bounds->southwest->lat,
								'lng' => $route->bounds->southwest->lng,
							)
						),
						'northeast:lat' => $route->bounds->northeast->lat,
						'northeast:lng' => $route->bounds->northeast->lng,
						'southwest:lat' => $route->bounds->southwest->lat,
						'southwest:lng' => $route->bounds->southwest->lng
					)
				),
				'response'		=> json_encode($directions),
				'total_legs'	=> count($legs),
				'legs'			=> $legs,
				'copyright' 	=> $route->copyrights
			);
			
			/* -------------------------------------------
			/* 'gmap_directions_vars' hook.
			/*  - Modify the variable for each rows
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_directions_vars', $vars[$index]);			
				
				if($edata !== NULL) $vars[$index] = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
				
		}
		
		/* -------------------------------------------
		/* 'gmap_directions_parse' hook.
		/*  - Modify the directions variables before they are parsed
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_directions_parse', $vars);			
			if($edata !== NULL) $vars = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $this->parse($vars);
	}
	
	public function route()
	{
		$map_id = $this->param('id', FALSE, FALSE, TRUE);
		
		$response = json_decode($this->EE->TMPL->tagdata);
		
		/* -------------------------------------------
		/* 'gmap_route_response' hook.
		/*  - Modify the response before it's manipulated
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_route_response', $response);			
			
			if($edata !== NULL) $response = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$js = array();
		
		$waypoints 	= array();
		$params		= array();
		$start		= array();
		$end		= array();
		
		if(!isset($response->routes))
		{
			return NULL;
		}
		
		foreach($response->routes as $route_index => $route)
		{
			foreach($route->legs as $leg_index => $leg)
			{
				if($leg_index + 1 < count($route->legs) && count($route->legs) > 1)
				{
					$waypoints[$route_index][] = $leg->end_address;
				}
				
				if($leg_index == 0)
					$start[$route_index]  = $leg->start_location;
				
				if($leg_index + 1 == count($route->legs))
					$end[$route_index]	= $leg->end_location;
			}
		}
		
		foreach($response->routes as $route_index => $route)
		{
			$waypoint_array = array();
			
			if(isset($waypoints[$route_index]))
			{
				foreach($waypoints[$route_index] as $index => $waypoint)
				{
					//$latlng = $this->EE->google_maps->LatLng($waypoint->lat, $waypoint->lng);
					
					$waypoint_array[$index] = array(
						'location' => urlencode($waypoint),
						'stopover' => true
					);
					
				}
			}
				
			$params = array(
				'id' => $map_id,
				'options' => array(
					'origin' 					=> str_replace(';', '', $this->EE->google_maps->latlng($start[$route_index]->lat, $start[$route_index]->lng)),
					'destination'				=> str_replace(';', '', $this->EE->google_maps->latlng($end[$route_index]->lat, $end[$route_index]->lng)),
					'waypoints'					=> $waypoint_array,
					'avoidHighways' 			=> $this->param('avoid_highways', FALSE, TRUE),
					'avoidTolls'				=> $this->param('avoid_tolls', TRUE, TRUE),
					'optimizeWaypoints' 		=> $this->param('optimize_waypoints', TRUE, TRUE),
					'provideRouteAlternatives' 	=> $this->param('provide_route_alternative', FALSE, TRUE),
					'travelMode'				=> 'google.maps.TravelMode.'.$this->param('travel_mode', 'DRIVING')
				)
			);
			
			/* -------------------------------------------
			/* 'gmap_route_params' hook.
			/*  - Modify the route parameters
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_route_params', $params);			
				
				if($edata !== NULL) $params = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
			
			if($unit_mode = $this->param('unit_mode') !== FALSE)
			{
				$params[$route_index]['unit_mode'] = $unit_mode;
			}
			
			$directions_js = $this->EE->directions->javascript($params);
			
			$js[] = $directions_js;		
		}
		
		$route_js = $this->EE->google_maps->return_js(implode('', $js));

		/* -------------------------------------------
		/* 'gmap_route' hook.
		/*  - Modify the entire route
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_route', $route_js);			
			
			if($edata !== NULL) $route_js = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $route_js;
	}
	
	public function streetview()
	{
		$id          = $this->param('id', FALSE, FALSE, TRUE);
		$address     = $this->param('address');
		$latitude    = $this->param('latitude');
		$longitude   = $this->param('longitude');
		$return      = NULL;
		$location 	 = NULL;
		$client_side = $this->param('client_side');
		$html        = NULL;

		if($address)
		{
			if(!$client_side)
			{
				$response = $this->EE->google_maps->geocode($address);
				
				if($response[0]->status == "OK")
				{
					$latitude  = $response[0]->results[0]->geometry->location->lat;
					$longitude = $response[0]->results[0]->geometry->location->lng;
					$location = $this->EE->google_maps->latlng($latitude, $longitude);
				}
				else
				{
					$this->EE->output->show_user_error('error', '"'.$address.'" is not a valid location');
				}
			}
		}
		else if($latitude && $longitude)
		{
			$location = $this->EE->google_maps->latlng($latitude, $longitude);
		}
		else
		{
			$location = $id.'_map.getCenter()';
		}
		
		$location = str_replace(';', '', $location);
		
		$overlay = $id.'_canvas';
		
		if(!$this->param('overlay', TRUE, TRUE))
		{
			$overlay = $id.'_streetview_canvas';
			$html   .= '<div class="'.$this->param('class', NULL).'" id="'.$id.'_pano" style="'.$this->param('style').'"></div>';
		
		}

		if($client_side && $address)
		{
			$return .= '
			'.$id.'_geocoder.geocode({address: "'.$address.'"}, function(response, status) {';
			$location = str_replace(';', '', $this->EE->google_maps->LatLng('response[0].geometry.location.lat()', 'response[0].geometry.location.lng()'));
		}
		else if($client_side && $latitude && $longitude)
		{
			$location = str_replace(';', '', $this->EE->google_maps->LatLng($latitude, $longitude));
		}

		$return .= '
		var '.$id.'_streetview_canvas	= document.getElementById(\''.$id.'_pano\');
	
		var '.$id.'_streetview_pano		= new google.maps.StreetViewPanorama('.$overlay.', {
			addressControl: '.$this->param('addressControl', 'true').',
			addressControlOptions: {position: google.maps.ControlPosition.'.strtoupper($this->param('addressControlOptions', 'TOP_LEFT')).'},
			disableDoubleClickZoom:	'.$this->param('disableDoubleClickZoom', 'true').',
			enableCloseButton: '.$this->param('enableCloseButton', 'true').',
			linksControl: '.$this->param('linksControl', 'true').',
			panControl: '.$this->param('panControl', 'true').',
			panControlOptions: {position: google.maps.ControlPosition.'.strtoupper($this->param('panControlOptions', 'TOP_LEFT')).'},
			pano: '.$this->param('pano', '""').',
	      	position: '.$location.',
			pov: {
				heading: '.$this->param('heading', 0).',
				pitch: '.$this->param('pitch', 0).',
				zoom: '.$this->param('zoom', 1).'
			},
			scrollwheel: '.$this->param('scrollwheel', 'false').',
			visible: '.$this->param('visible', 'true').',
			zoomControl: '.$this->param('zoomControl', 'true').',
			zoomControlOptions: {position: google.maps.ControlPosition.'.strtoupper($this->param('zoomControlOptions', 'TOP_LEFT')).'}
	    }); 
		
		'.$id.'_map.setStreetView('.$id.'_streetview_pano);';
		
		if($client_side && $address)
		{
			$return .= '
			});';
		}

		/* -------------------------------------------
		/* 'gmap_streetview' hook.
		/*  - Modify the JavaScript that created the street view overlay
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_streeview', $return);			
			
			if($edata !== NULL) $return = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $html.$this->EE->google_maps->return_js($return);
	}
	
	private function merge($array, $subject, $var, $term_1, $term_2)
	{
		foreach($array as $index => $term)
		{
			$subject = array_merge($subject, array(
				$term.':'.$term_1	=> $var->$term->$term_1,
				$term.':'.$term_2 	=> $var->$term->$term_2,
				$term => array(
					array(
						$term_1	=> $var->$term->$term_1,
						$term_2 => $var->$term->$term_2 
					)
				)
			));
		}
		
		return $subject;
	}
		
	public function dropdown()
	{
		$map_id	= $this->param('id', 'map');
		$limit	= $this->param('limit', 'false');
		 
		$dropdown = '				
		<select name="'.$map_id.'_dropdown" id="'.$map_id.'_dropdown" class="'.$this->param('class', '').'" style="'.$this->param('style', '').'">
			<option>--Select a location--</option>
		</select>
				
		<script type="text/javascript">
			$(document).ready(function() {
			
				var '.$map_id.'_dropdown = $("#'.$map_id.'_dropdown");				
				var '.$map_id.'_dropdownLimit  = '.$limit.';
				var '.$map_id.'_count = '.$map_id.'_markers.length
				
				function '.$map_id.'_showMarker(obj) {
					var index = obj.selectedIndex - 1;
					
					if(index >= 0) {
						var marker = '.$map_id.'_markers[index];
						var position = marker.position;
						var window = '.$map_id.'_windows[index];
						
						if(window) {
							for(i = 0; i < '.$map_id.'_count; i++) {
								'.$map_id.'_windows[i].close();
							}
							
							'.$map_id.'_map.setCenter(position);';
							
							if($zoom = $this->param('zoom'))
								$dropdown .= $map_id.'_map.setZoom('.$zoom.');';
							
							$dropdown .= '
							window.open('.$map_id.'_map, marker);
						}
					}
				}
				
				$('.$map_id.'_dropdown).change(function() {
					'.$map_id.'_showMarker(this);
				});
				
				for(i = 0; i < '.$map_id.'_count; i++) {
					if(i < '.$map_id.'_dropdownLimit || !'.$map_id.'_dropdownLimit) {
						var marker = '.$map_id.'_markers[i];
						
						if(marker) {
							var content = (typeof marker.title != "undefined") ? marker.title : "Marker "+(i+1);
							var html = content;';
							
						$dropdown .= '
							'.$map_id.'_dropdown.append(\'<option id="\'+i+\'">\'+html+\'</option>\');
						}
					}
				}
			});
			
		</script>
		';
		
		/* -------------------------------------------
		/* 'gmap_dropdown' hook.
		/*  - Modify the dropdown before it's returned
		/*  - Added Google Maps for ExpressionEngine 3.0
		*/
			$edata = $this->EE->extensions->call('gmap_dropdown', $dropdown, $map_id, $limit);			
			
			if($edata !== NULL) $dropdown = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $dropdown;
	}
	
	public function response()
	{
		$this->EE->load->driver('channel_data');
		
		$field_id 	= $this->param('field_id');
		$entry_id	= $this->param('entry_id');
		$field_name	= $this->param('field_name');
		
		$params 	= $field_id !== FALSE ? array('field_id' => $field_id) : array('field_name' => $field_name);
		
		$field		= $this->EE->channel_data->get_fields(array(), $params)->row();
		$entry		= $this->EE->channel_data->get_channel_entry($entry_id)->row();
		
		$response	= json_decode($entry->{$field->field_name});
		
		if(count($response->markers->results) > 0)
		{
			$vars['markers'] = $this->EE->google_maps->parse_geocoder_response($response->markers->results);
			
			/* -------------------------------------------
			/* 'gmap_response_markers' hook.
			/*  - Modify the response markers before returned
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_response_markers', $vars['markers']);			
				
				if($edata !== NULL) $vars['markers'] = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
			
			foreach($vars['markers'] as $index => $marker)
			{
				$marker['response_type'] = 'marker';
				$vars['markers'][$index] = $marker;
			}
		}
		
		if(count($response->waypoints->results) > 0)
		{
			$vars['waypoints'] = $this->EE->google_maps->parse_geocoder_response($response->waypoints->results);
			
			/* -------------------------------------------
			/* 'gmap_response_waypoints' hook.
			/*  - Modify the response waypoints before returned
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_response_markers', $vars['waypoints']);			
				
				if($edata !== NULL) $vars['wapoints'] = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
					
			foreach($vars['waypoints'] as $index => $waypoints)
			{
				$marker['response_type'] 	= 'waypoint';
				$vars['waypoints'][$index] 	= $marker;
			}
		}
		
		if(count($response->regions->results) > 0)
		{
			$vars['regions'] = $this->EE->google_maps->objectToArray($response->regions->results);
			
			/* -------------------------------------------
			/* 'gmap_response_regions' hook.
			/*  - Modify the response regions before returned
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_response_regions', $vars['regions']);			
				
				if($edata !== NULL) $vars['regions'] = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
				
			foreach($vars['regions'] as $index => $region)
			{			
				$vars['regions'][$index]['stroke_color'] 	= $region['style']['strokeColor'];
				$vars['regions'][$index]['stroke_opacity'] 	= $region['style']['strokeOpacity'];
				$vars['regions'][$index]['stroke_weight'] 	= $region['style']['strokeWeight'];
				$vars['regions'][$index]['fill_color'] 		= $region['style']['fillColor'];
				$vars['regions'][$index]['fill_opacity'] 	= $region['style']['fillOpacity'];
			}
			
		}
	
		/* -------------------------------------------
		/* 'gmap_response' hook.
		/*  - Modify the response waypoints before returned
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_response', $vars);			
			
			if($edata !== NULL) $vars = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
			
		return $this->parse($vars);
	}
	
	public function post()
	{
		/* -------------------------------------------
		/* 'gmap_post' hook.
		/*  - Modify post variables before method executes
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_post', $_POST);			
			
			if($edata !== NULL) $_POST = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		if($field = $this->param('field') !== FALSE)
		{	
			if($post = $this->EE->input->post($field) !== FALSE)
			{
				return $post;
			}
		}
		
		$prefix = $this->param('prefix', 'post');
		
		$vars = array(
			array_merge(array(
				'has_submit' 		=> count($_POST) > 0 ? TRUE  : FALSE,
				'has_not_submit' 	=> count($_POST) > 0 ? FALSE : TRUE
			), $_POST)
		);
		
		$vars = $this->EE->channel_data->utility->add_prefix($prefix, $vars);

		/* -------------------------------------------
		/* 'gmap_post_vars' hook.
		/*  - Modify post variables before they are parsed 
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_post_vars', $vars);			
			
			if($edata !== NULL) $vars = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		return $this->parse($vars);
	}
	
	public function static_map()
	{
		$this->EE->static_maps->center 	 = $this->param('center', NULL);
		$this->EE->static_maps->class 	 = $this->param('class', NULL);
		$this->EE->static_maps->format	 = strtoupper($this->param('format', 'JPEG'));	
		$this->EE->static_maps->height	 = $this->param('height', 300);	
		$this->EE->static_maps->id 	 	 = $this->param('id', NULL);
		$this->EE->static_maps->language = $this->param('language', 'en');
		$this->EE->static_maps->maptype	 = $this->param('maptype', $this->param('map_type', 'roadmap'));
		$this->EE->static_maps->markers  = explode(';', $this->param('markers'));	
		$this->EE->static_maps->path 	 = $this->param('path', NULL);
		$this->EE->static_maps->region	 = $this->param('region', NULL);
		$this->EE->static_maps->scale	 = $this->param('scale', 1);
		$this->EE->static_maps->sensor	 = $this->param('sensor', FALSE);
		$this->EE->static_maps->style	 = explode(';', $this->param('style', NULL));
		$this->EE->static_maps->visible	 = $this->param('visible', NULL);
		$this->EE->static_maps->width	 = (int) $this->param('width', 400);	
		$this->EE->static_maps->zoom 	 = $this->param('zoom', NULL);
		
		$return = $this->EE->static_maps->render();
		
		return $return;
	}
	
	public function search()
	{		
		$this->EE->load->driver('channel_data');
		$this->EE->load->library('base_form');
		
		/* -------------------------------------------
		/* 'gmap_search_post' hook.
		/*  - Modify the search POST variables before method executes
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_search_post', $_POST);			
			
			if($edata !== NULL) $_POST = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$vars = array();
		
		$this->EE->load->helper('form');
		
		if($this->param('cache_post', TRUE, TRUE))
		{		
			if($this->EE->input->post('init_gmap_search') == 'y')
			{			
				$this->EE->functions->set_cookie('gmap_last_post', serialize($_POST), strtotime('+1 year'));
			}
			else
			{
				$cookie = $this->EE->input->cookie('gmap_last_post');
				
				if($cookie) $_POST = unserialize($cookie);
			}
		}
		
		$checked_true  		= 'checked="checked"';
		$selected_true 		= 'selected="selected"';
		$metric		   		= $this->param('metric', 'miles');
		$geocode_fields		= explode('|', $this->param('geocode_field', 'location'));		
		$channels 	   		= explode('|', $this->param('channel', FALSE, FALSE, TRUE));
		
		$location = '';
		
		foreach($geocode_fields as $geocode_field)
		{	
			$post 						 = $this->EE->input->post($geocode_field) . ' ';
			$location				 	.= $post;
			$vars[0][$geocode_field]  	 = trim($post);
		}
							
		$location 	= trim($location);		
		$distance 	= $this->EE->input->post('distance');	
		$field_loop	= $this->reserved_terms;
				
		foreach($field_loop as $append)
		{
			$vars[0]['distance'.$append] = $this->EE->input->post('distance'.$append) ? 
										   $this->EE->input->post('distance'.$append) : '';
		}
		
		$channel_fields = $this->EE->channel_data->get_fields()->result();
		$field_index    = $this->EE->channel_data->utility->reindex($channel_fields, 'field_id');
		
		//Loops through the defined channels
		foreach($channels as $channel)
		{
			$channel_data = $this->EE->channel_data->get_channel_by_name($channel)->row();
			
			if(!isset($channel_data->channel_name))
			{
				$this->EE->output->show_user_error('general', '"'.$channel.'" is not a valid channel name.');	
			}
			
			$available_categories = explode('|', $channel_data->cat_group);
						
			//Loops throught the fields in each channel
			foreach($channel_fields as $index => $channel_field)
			{
				$field = $channel_field;
				$field_name = str_replace(array('_min', '_max'), array('', ''), $field->field_name);
								
				$fields = array();
				
				//Loops throught the standard, min, and max fields
				foreach($field_loop as $append)
				{
					$field_appendage = $field_name . $append;
										
					$input = $this->EE->input->post($field_appendage) ?
					    	 $this->EE->input->post($field_appendage) : '';
					    		
					$vars[0]['post:'.$field_appendage] = $input;
					
					//If list items are populated from another field
					if((int) $field->field_pre_field_id)
					{	
						$pre_field = $field_index[$field->field_pre_field_id];
						
						$entries = $this->EE->channel_data->get_channel_entries($field->field_pre_channel_id, array(
							'select'   => 'field_id_'.$pre_field->field_id.' as \''.$pre_field->field_name.'\'',
							'order_by' => $pre_field->field_related_orderby,
							'limit'    => 5,
							'sort'     => $pre_field->field_related_sort,
						));
						
						$list_items = array();
						
						foreach($entries->result() as $entry)
						{
							if(!in_array($entry->{$pre_field->field_name}, (array) $list_items))
							{
								$list_items[] = $entry->{$pre_field->field_name};	
							}
						}
						
						if(!isset($vars[0]['options:'.$channel_field->field_name]))
						{
							$vars[0]['options:'.$channel_field->field_name] = $this->EE->google_maps->prep_field_options($list_items, $field_appendage);	
						}					
					}
					
					//If list items exist, it build the option:field_name array
					else if(!empty($field->field_list_items))
					{					
						$list_items = explode("\n", $field->field_list_items);
								
						if(!isset($vars[0]['options:'.$channel_field->field_name]))
						{
							$vars[0]['options:'.$channel_field->field_name] = $this->EE->google_maps->prep_field_options($list_items, $field_appendage);	
						}	
					}
					
					$fields['label:'.$field_appendage] = $field->field_label;
					$fields['instructions:'.$field_appendage] = $field->field_instructions;
					$fields[$field_appendage] = $input;
				}
				
				$vars[0] = array_merge($fields, $vars[0]);
			}
			
			//Loops through the channel categories and assigns them to template
			//variable in a linear fasion, similar to the steps above
			foreach($available_categories as $cat_group_id)
			{
				$cat_data = $this->EE->channel_data->get_category_by_group($cat_group_id, array(
					'select'   => '*',
					'order_by' => $this->param('cat_order_by', 'parent_id, cat_order'),
					'sort'     => $this->param('cat_sort', 'asc, asc'),
					'limit'    => $this->param('cat_limit', FALSE),
					'offset'   => $this->param('cat_offset', 0)
				))->result();
				
				foreach($cat_data as $cat_index => $category)
				{
					$selected = '';
					$checked  = '';
					
					if($this->EE->google_maps->is_checked_or_selected($this->EE->input->post('categories'), $category->cat_id))
					{
						$selected = $selected_true;
						$checked  = $checked_true;
					}
					
					$vars[0]['categories'][] = array(
						'category_id'   		  => $category->cat_id,
						'category_group_id'		  => $category->group_id,
						'category_name' 		  => $category->cat_name,
						'category_url_title'      => $category->cat_url_title,
						'category_description'    => $category->cat_description,
						'category_image'		  => $category->cat_image,
						'selected'				  => $selected,
						'checked'				  => $checked
					);	
				}
			}
		}
				
		//var_dump($vars[0]['categories']);exit();
		
		$return    	   = $this->EE->TMPL->fetch_param('return');
		
		$attributes    = array();
		$hidden_fields = array(
			'init_gmap_search' 	=> 'y',
			'channel'		   	=> $channels,
			'distance'		   	=> $distance,
			'metric'		   	=> $metric,
			'geocode_field'	   	=> implode('|', $geocode_fields),
			'distance_field'	=> $this->param('distance_field', 'distance'),
			'location'		  	=> $location,
			'latitude_field'	=> $this->param('latitude_field', FALSE, FALSE),
			'longitude_field'	=> $this->param('longitude_field', FALSE, FALSE)
		);
		
		$vars[0]['has_searched']     = $this->EE->input->post('init_gmap_search') == 'y' ? TRUE : FALSE;
		$vars[0]['has_not_searched'] = $vars[0]['has_searched'] ? FALSE : TRUE;
		
		if($vars[0]['has_searched'])
		{
			//$vars = $this->EE->base_form->validate_required_fields($vars);
		}
		
		
		if($this->param('cache_post', TRUE, TRUE))
		{
			$hidden_fields['cache_post'] = 'y';
		}
				
		/* -------------------------------------------
		/* 'gmap_search_vars' hook.
		/*  - Modify the route before they are parsed
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_search_vars', $vars);			
			
			if($edata !== NULL) $vars = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$tagdata = $this->parse($vars);		
				
		/* -------------------------------------------
		/* 'gmap_search_vars_parse' hook.
		/*  - Modify the search variables after they are parsed
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_search_vars_parse', $vars, $tagdata);			
			
			if($edata !== NULL) $tagdata = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$this->EE->base_form->tagdata = $tagdata;
		$this->EE->base_form->action  = $this->param('return', $this->EE->google_maps->current_url());
		
		$form = $this->EE->base_form->open($hidden_fields);
		
		if(isset($response))
		{
			$this->EE->functions->set_cookie('gmap_last_search', serialize($response), strtotime('+1 year'));
		}
		
		$return = $form;
		
		/* -------------------------------------------
		/* 'gmap_search' hook.
		/*  - Modify the search form before it's returned
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_search', $return);			
			if($edata !== NULL) $return = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $return;
	}
	
	public function results()
	{		
		$this->EE->load->library('base_form');
		
		$method = strtolower($this->param('method', 'post'));

		/* -------------------------------------------
		/* 'gmap_results_post' hook.
		/*  - Modify the POST variables before method is executed
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_results_post', $_POST);			
			if($edata !== NULL) $_POST = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		if($this->EE->input->post('cache_post') == 'y')
		{		
			if($this->EE->input->post('init_gmap_search') == 'y')
			{			
				$this->EE->functions->set_cookie('gmap_last_post', serialize($_POST), strtotime('+1 year'));
			}
			else
			{
				$cookie = $this->EE->input->cookie('gmap_last_post');
				
				if($cookie)
				{
					$_POST = unserialize($cookie);
				}
			}
		}
		
		$tagdata			 = empty($this->EE->TMPL->tagdata) ? FALSE : $this->EE->TMPL->tagdata;
		$metric		   		 = $this->EE->input->$method('metric');
		$geocode_fields		 = explode('|', $this->EE->input->post('geocode_field'));
		
		$location = '';
		
		foreach($geocode_fields as $geocode_field)
		{	
			$post = $this->EE->input->post($geocode_field);
			
			if($post === FALSE && $this->EE->input->post('init_gmap_search') == 'y')
			{
				$this->EE->output->show_user_error('error', '\''.$geocode_field.'\' is not valid post variable.');
			}
			
			$location .= $post;
			$vars[0][$geocode_field] = trim($post);
		}
		
		$location 				= trim($location);
		$channels			 	= $this->EE->input->post('channel');
		$distance_field 	 	= $this->EE->input->post('distance_field');
		$distance			 	= $this->EE->input->post($distance_field);		
		$categories			 	= $this->EE->input->post('categories');
		
		foreach($_POST as $field => $value)
		{
			$vars[0]['post:'.$field] = $this->EE->input->post($field);
		}
		
		$vars[0]['has_searched']     = $this->EE->input->post('init_gmap_search') == 'y' ? TRUE : FALSE;
		$vars[0]['has_not_searched'] = $vars[0]['has_searched'] ? FALSE : TRUE;

		if($vars[0]['has_searched'])
		{
			//$vars = $this->EE->base_form->validate_required_fields($vars);
		}
		
		if($location !== FALSE && !empty($location))
		{
			$response = $this->EE->google_maps->geocode($location);
			
			if($response[0]->status == 'OVER_QUERY_LIMIT' && $this->EE->session->userdata['group_id'] == 1)
			{
				return $this->EE->output->show_user_error('general', 'You are over your query limit for the Google\'s Geocoding services. If you are on a shared IP, it\'s advised you upgrade to a dedicated IP so you don\t have to share your requests with other sites. For more information on Geocoding limits, go to <a href="https://developers.google.com/maps/documentation/geocoding/#Limits">https://developers.google.com/maps/documentation/geocoding/#Limits</a>');
			}
			
			if($response[0]->status == "OK")
			{			
				$response = $this->EE->google_maps->parse_geocoder_response($response, FALSE, 0);
														
				$vars[0] = array_merge($vars[0], $response[0]);
				
				unset($vars[0]['title']);
				unset($vars[0]['content']);
				
				$lat = $vars[0]['latitude'];
				$lng = $vars[0]['longitude'];
				
				if($lat !== FALSE && $lng !== FALSE)
				{
					if($distance === FALSE)
					{
						$this->EE->output->show_user_error('error', 'The distance field is not defined in the template tag. The distance_field parameter value should be the same as the name of your distance field in the DOM.');
					}				
					
					$lat_field = $this->EE->input->post('latitude_field');
					$lng_field = $this->EE->input->post('longitude_field');
					
					$lat_field_name = $this->EE->google_maps->prep_sql_fieldname($lat_field, FALSE, FALSE);		

					if(count($lat_field_name) == 0)
					{
						return $this->EE->output->show_user_error('error', 'The latitude field \''.$lat_field.'\' is not valid.');
					}
					
					$lat_field_name = $lat_field_name[0];
									
					$lng_field_name = $this->EE->google_maps->prep_sql_fieldname($lng_field, FALSE, FALSE);
					
					if(count($lng_field_name) == 0)
					{
						return $this->EE->output->show_user_error('error', 'The latitude field \''.$lng_field.'\' is not valid.');
					}

					$lng_field_name = $lng_field_name[0];
					
					$vars[0]['search_distance'] = $distance;
					$vars[0]['metric'] = $metric;
				}
				else
				{
					$vars[0]['metric'] = '';
					$vars[0]['search_distance'] = 'any distance';
				}
			}
		}

		$prep_fields = $this->EE->google_maps->prep_sql_fieldname($_POST, FALSE);
	
		$select   	= array(
			'`exp_channel_titles`.*',
			'`exp_channel_data`.`entry_id`'
		);

		$where   	= array();
		$cat_where  = array();
		$having  	= array();
		$loc_sql 	= ', 0 as `distance`';
		 
		if($location && isset($lat) && isset($lng))
		{
			$select[] = 'ROUND((((ACOS(SIN('.$lat.' * PI() / 180) * SIN('.$lat_field_name.' * PI() / 180) + COS('.$lat.' * PI() / 180) * COS('.$lat_field_name.' * PI() / 180) * COS(('.$lng.' - '.$lng_field_name.') * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * '.$this->EE->google_maps->convert_metric($metric).'), 1) AS distance';
		}
		else
		{
			$select[] = '0 AS distance';
		}
		
		if($distance)
		{
			$having[] = '`distance` '.$this->EE->google_maps->prep_value($distance_field, (float) $distance);
		}
		
		if($channels)
		{									
			//Loops through the defined channels
			foreach($channels as $channel_name)
			{		
				$channel_data = $this->EE->channel_data->get_channel_by_name($channel_name)->row();
				$fields	 	  = $this->EE->channel_data->get_channel_fields($channel_data->channel_id);
				
				foreach($fields->result() as $row)
				{
					$select[] = ' `field_id_'.$row->field_id.'` as \''.$row->field_name.'\'';
				}
				
				if(count($channel_data) > 0)
				{	
					//$this->EE->db->or_where('channel_id', $channel_data->channel_id);
					$where[] = 'OR `exp_channel_data`.`channel_id` = \''.$channel_data->channel_id.'\'';

					if(is_array($prep_fields))
					{				
						foreach($prep_fields as $prep_index => $prep_value)
						{
							$where[] = ' AND '.$prep_value;
						}
					}
					
					if(is_array($categories))
					{	
						$vars[0]['category']   = implode('|', $categories);
						$vars[0]['categories'] = $vars[0]['category'];
							
						foreach($categories as $category)
						{			
							if(!empty($category))
							{			
								$cat_where[] = '`cat_id` = '.$category.'';
							}
						}
					}
					
					//Checks to see if the category is actually set						
					else if($categories)	
					{	
						if(!empty($category))
						{		
							$cat_where[] = '`cat_id` = '.$category.'';
						}
						
						$vars[0]['category'] 	= $categories;
						$vars[0]['categories'] 	= $vars[0]['category'];
					}			
												
				}
			}				
		}
		
		if(count($cat_where) > 0)
		{
			$select[] = 'cc.*';
			
			$match_categories = $this->param('match_categories');

			$cat_having		  = '= '.count($cat_where);

			if($match_categories !== FALSE)
			{
				$cat_having   = '>= '.(int)$match_categories;
			}

			$table = '(
			    SELECT distinct entry_id, COUNT(cat_id) AS cat_count, cat_id, cat_id as \'category_id\', GROUP_CONCAT(cat_id SEPARATOR \'|\') as \'cat_ids\', GROUP_CONCAT(cat_id SEPARATOR \'|\') as \'category_ids\', exp_categories.cat_name, exp_categories.cat_name as \'category_name\', exp_categories.cat_url_title, exp_categories.cat_url_title as \'category_url_title\', exp_categories.parent_id as \'cat_parent_id\', exp_categories.parent_id as \'category_parent_id\', exp_categories.site_id as \'cat_site_id\', exp_categories.site_id as \'category_site_id\', exp_categories.group_id as \'cat_group_id\', exp_categories.group_id as \'category_group_id\', exp_categories.cat_description as \'cat_description\', exp_categories.cat_description as \'category_description\', exp_categories.cat_image as \'cat_image\', exp_categories.cat_image as \'category_image\', GROUP_CONCAT(exp_categories.cat_name  SEPARATOR \'|\') as \'cat_names\', GROUP_CONCAT(exp_categories.cat_name  SEPARATOR \'|\') as \'category_names\',  GROUP_CONCAT(exp_categories.cat_url_title  SEPARATOR \'|\') as \'cat_url_titles\', GROUP_CONCAT(exp_categories.cat_url_title  SEPARATOR \'|\') as \'category_url_titles\', GROUP_CONCAT(exp_categories.parent_id  SEPARATOR \'|\') as \'cat_parent_ids\', GROUP_CONCAT(exp_categories.parent_id  SEPARATOR \'|\') as \'category_parent_ids\', GROUP_CONCAT(exp_categories.cat_description  SEPARATOR \'|\') as \'cat_descriptions\', GROUP_CONCAT(exp_categories.cat_description  SEPARATOR \'|\') as \'category_descriptions\',  GROUP_CONCAT(exp_categories.group_id SEPARATOR \'|\') as \'cat_group_ids\', GROUP_CONCAT(exp_categories.group_id SEPARATOR \'|\') as \'category_group_ids\', GROUP_CONCAT(exp_categories.site_id  SEPARATOR \'|\') as \'cat_site_ids\', GROUP_CONCAT(exp_categories.site_id  SEPARATOR \'|\') as \'category_site_ids\', GROUP_CONCAT(exp_categories.cat_image  SEPARATOR \'|\') as \'cat_images\',  GROUP_CONCAT(exp_categories.cat_image  SEPARATOR \'|\') as \'category_images\'
			    FROM exp_category_posts 
			    INNER JOIN exp_categories USING (cat_id)
			    WHERE '.implode(' OR ', $cat_where).'
			    GROUP BY entry_id
			    HAVING cat_count '.$cat_having.'
			) cc
			INNER JOIN
		    	exp_channel_data
		  	USING (entry_id)';
		}
		else
		{
			$select[] = '\'\' as cat_id, \'\' as category_id, \'\' as cat_name, \'\' as category_name, \'\' as cat_url_title, \'\' as category_url_title, \'\' as cat_parent_id, \'\' as category_parent_id, \'\' as cat_site_id, \'\' as category_site_id, \'\' as cat_group_id, \'\' as category_group_id, \'\' as cat_description, \'\' as category_description, \'\' as cat_image, \'\' as category_image, \'\' as cat_names, \'\' as category_names, \'\' as cat_url_titles, \'\' as category_url_titles, \'\' as cat_parent_ids, \'\' as category_parent_ids, \'\' as cat_descriptions, \'\' as category_description, \'\' as cat_groups_ids, \'\' as category_group_ids, \'\' as cat_site_ids, \'\' as category_site_ids, \'\' as cat_images, \'\' as category_images, \'\' as cat_ids, \'\' as category_ids, \'\' as categories';
			
			$table = '`exp_channel_data`';
		}

		$order_by = $this->param('order_by', $this->param('orderby', 'entry_date'));
		$sort     = $this->param('sort', strtoupper('asc'));
		$limit    = $this->param('limit', FALSE);
		$offset   = $this->param('offset', 0);
		
		$segments = $this->EE->uri->segment_array();

		$page_segment  = $this->param('page_segment', 'page');
		$limit_segment = $this->param('limit_segment', 'limit');

		if($this->param('paginate', FALSE, TRUE))
		{
			foreach($segments as $index => $segment)
			{
				if($segment == $page_segment)
				{
					$page = (int) $segments[$index+1];
				}
				
				if($segment == $limit_segment)
				{
					$limit = (int) $segments[$index+1];
				}
			}

			if(!isset($page))
			{
				$page = 1;
			}

			if(!$limit)
			{
				$limit = 20;
			}

			if($page && $limit)
			{
				$offset = $limit * $page - $limit;
			}

			$vars[0]['current_page'] = $page;
			$vars[0]['next_page']    = $page+1;
			$vars[0]['prev_page']    = $page-1;
		}

		$vars[0]['limit']    = $limit;
		$vars[0]['sort']     = $sort;
		$vars[0]['order_by'] = $order_by;
		$vars[0]['offset']   = $offset;

		$base_sql =  '
		SELECT 
			'.implode(', ', $select).'
		FROM
			'.$table.'
		INNER JOIN `exp_channel_titles` USING (entry_id)
		'.(count($where) > 0 ? ' WHERE ' . ltrim(implode(' ', $where), 'OR') : NULL).' 
		'.(count($having) > 0 ? ' HAVING '.implode(' ', $having) : NULL);
		
		$grand_total_results = $this->EE->db->query($base_sql)->num_rows();

		$sql = $base_sql . '
		ORDER BY `'.$order_by.'` '.$sort.'
		'.($limit ? 'LIMIT '.$offset.','.$limit : NULL);
		
		/* -------------------------------------------
		/* 'gmap_results_sql' hook.
		/*  - Modify the SQL statement before the query is executed
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_results_sql', $sql, $vars);			
			if($edata !== NULL) $sql = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$vars[0]['sql'] = $sql;
		
		if($this->param('show_sql')) echo $sql;
		
		$results 	   = $this->EE->db->query($sql);
		$total_results = $results->num_rows();
		$entry_ids 	   = $this->EE->google_maps->create_id_string($results->result());
			
		if($tagdata)
		{
			$result_array = $results->result_array();
			
			foreach($result_array as $row_index => $row)
			{
				$row['count'] = $row_index + 1;
				$row['index'] = $row_index;

				$result_array[$row_index] = array_merge($row, $this->EE->channel_data->utility->add_prefix('result', $row));
			}
			
			$vars[0]['entry_ids'] 		= $entry_ids;
			$vars[0]['total_results']   = $total_results;
			$vars[0]['results'] 		= $vars[0]['has_searched'] && $total_results > 0 ? $result_array : array();

			if($this->param('paginate', FALSE, TRUE))
			{
				$vars[0]['total_pages'] = ceil($grand_total_results / $limit);
				
				if($vars[0]['next_page'] > $vars[0]['total_pages'])
				{
					$vars[0]['next_page'] = FALSE;
				}

				if($vars[0]['prev_page'] < 0)
				{
					$vars[0]['prev_page'] = FALSE;
				}
			}
			
			/* -------------------------------------------
			/* 'gmap_results_vars' hook.
			/*  - Modify the result variables before they are parsed
			/*  - Added v3.0
			*/
				$edata = $this->EE->extensions->call('gmap_results_vars', $vars);			
				if($edata !== NULL) $vars = $edata;
				
				if ($this->EE->extensions->end_script === TRUE) return;
			/*
			/* -------------------------------------------*/
			
			if($vars[0]['has_searched'] && $total_results == 0)
			{
				return $this->EE->TMPL->no_results();
			}		
			
			return $this->parse($vars);
		}
		else
		{
			return $entry_ids;
		}		
	
		return NULL;
	}
	
	public function zoom()
	{
		$map_id		= $this->param('id', 'map');
		$zoom		= (int) $this->param('zoom', 12);
		
		$js = $this->EE->google_maps->zoom($map_id, $zoom);
		
		/* -------------------------------------------
		/* 'gmap_zoom' hook.
		/*  - Modify the zoom before returned
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_zoom', $js);			
			
			if($edata !== NULL) $js = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $js;
	}
	
	private function get_options($index = FALSE)
	{
		$options = array();
		
		foreach($this->args as $arg_index => $args)
		{
			foreach($args as $arg)
			{
				if(!isset($options[$arg_index]))
					$options[$arg_index] = array();
				
				if($this->param($arg))
					$options[$arg_index][$arg] = $this->param($arg);
			}
		}
		
		return $index === FALSE ? $options : $options[$index];
	}
	
	public function curl()
	{
		$reserved_terms = array('url', 'method');
		
		$url 	= $this->EE->input->post('url');
		$method	= $this->EE->input->post('method') ? $this->EE->input->post('method') : 'GET';
		
		foreach($reserved_terms as $term)
		{
			unset($_POST[$term]);
		}
		
		$data 	= array();
		$string = NULL;
		 
		foreach($_POST as $post => $value)
		{
			$data[$post] = $value;
			$string 	.= urlencode($post).'='.urlencode($value).'&';
		}
				
		$ch = curl_init($url.'?'.$string);
		curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($string, '&'));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 
		$response = curl_exec($ch);
		curl_close($ch);

	}
	
	private function parse($vars, $tagdata = FALSE)
	{
		if($tagdata === FALSE)
		{
			$tagdata = $this->EE->TMPL->tagdata;
		}
			
		return $this->EE->TMPL->parse_variables($tagdata, $vars);
	}
	
	private function param($param, $default = FALSE, $boolean = FALSE, $required = FALSE)
	{
		$name 	= $param;
		$param 	= $this->EE->TMPL->fetch_param($param);
		
		if($required && !$param) show_error('You must define a "'.$name.'" parameter in the '.__CLASS__.' tag.');
			
		if($param === FALSE && $default !== FALSE)
		{
			$param = $default;
		}
		else
		{				
			if($boolean)
			{
				$param = strtolower($param);
				$param = ($param == 'true' || $param == 'yes') ? TRUE : FALSE;
			}			
		}
		
		/* -------------------------------------------
		/* 'gmap_fetch_param' hook
		/*  - Modify the fetched_parameter before it's returned
		/*  - Added v3.0
		*/
			$edata = $this->EE->extensions->call('gmap_fetch_param', $param, $name, $default, $boolean, $required);			
			if($edata !== NULL) $param = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $param;			
	}
	
}
// END CLASS

/* End of file mop.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/mod.gmap.php */