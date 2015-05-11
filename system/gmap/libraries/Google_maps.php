<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.3.0
 * @build		20120111
 */
 
class Google_maps {
	
	public $default_marker  = array();
	public $default_options = array();
	public $reserved_terms 	= array('', '_min', '_max', '_like', '_day');
	
	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->config('gmap_config');
		$this->EE->load->helper('url');
	}	
	
	public function build_response($data)
	{
		$response = array(
			'markers' => array(
				'total' => 0,
				'results' => array()
			),
			'waypoints' => array(
				'total' => 0,
				'results' => array()
			),
			'regions' => array(
				'total' => 0,
				'results' => array()
			)
		);
		
		if(is_array($data))
		{
			$data = (object) $data;
		}
		
		foreach(array('markers', 'waypoints', 'regions') as $type)
		{		
			if(is_array($data))
			{
				$data = (object) $data;
			}
			
			if(isset($data->$type))
			{
				$response[$type]['results'] = $data->$type;			
				$response[$type]['total'] 	= count($data->$type);
			}	
		}
		
		$response = (object) $response;
		
		return json_encode($response);
	}
	
	public function center($map_id, $latitude, $longitude, $script = TRUE)
	{
		$latlng = str_replace(';', '', $this->LatLng($latitude, $longitude));
		$js 	= $map_id.'_map.setCenter('.$latlng.');';
		
		return $this->return_js($js, $script);
	}

	public function center_js($map_id, $lat, $lng) {
		$js = '
		var location = new google.maps.LatLng('.$lat.', '.$lng.')
		'.$map_id.'_map.setCenter(location)';

		return $js;
	}
	
	public function current_location($params = array())
	{		
		$params = array_merge(array(
			'id'             => $params['id'],
			'zoom_location'  => TRUE,
			'extend_bounds'  => TRUE,
			'zoom'  		 => FALSE,
			'script_tag'     => TRUE,
			'content'        => FALSE,
			'marker_options' => array(),
			'circle_options' => array()
		), $params);
		
		$show_one_window = isset($params['infowindow']['show_one_window']) ? $params['infowindow']['show_one_window'] : FALSE;
		$open_windows = isset($params['infowindow']['open_windows']) ? $params['infowindow']['open_windows'] : FALSE;
		
		unset($params['options']['show_one_window']);
		unset($params['options']['open_windows']);
		
		$return = NULL;
		
		if($params['script_tag'])
		{
			$url = rtrim($this->EE->theme_loader->theme_url(), '/') . '/';

			$return .= '<script type="text/javascript" src="'.$url.'gmap/javascript/geolocationmarker.js"></script>';
		}
		
		$content = isset($result->content) && !empty($result->content) ? $result->content : null;
		$content = isset($params['infowindow']['content']) && $params['infowindow']['content'] ? $params['infowindow']['content'] : $content;
						
		if(isset($params['infobox']) && $params['infobox'])
		{							
			$window = $this->EE->google_maps->infobox(array(
				'id'              => $params['id'],
				'content'         => $content,
				'options'         => $params['infowindow']['options'],
				'script_tag'      => FALSE,
				'var'             => $params['id'].'_GeoMarker',
				'show_one_window' => $show_one_window,
				'open_windows'    => $open_windows,
				'trigger'         => $params['window_trigger']
			));
		}
		else
		{
			$window = $this->EE->google_maps->infowindow(array(
				'id'				=> $params['id'],
				'content'			=> $content, 
				'options'			=> $params['infowindow']['options'],
				'script_tag'		=> FALSE,
				'var'				=> $params['id'].'_GeoMarker',
				'show_one_window' 	=> $show_one_window,
				'open_windows'		=> $open_windows,
				'trigger'			=> $params['window_trigger']
			));
		}
		
		$return .=
		'<script type="text/javascript">
			
			var '.$params['id'].'_GeoMarker = new GeolocationMarker('.$params['id'].'_map, '.$this->convert_to_js($params['marker_options']).', '.json_encode($params['circle_options']).');
					    	
			'.$params['id'].'_markers.push('.$params['id'].'_GeoMarker.b);
			
			var index = '.$params['id'].'_markers.length - 1;
						
			'.($params['extend_bounds'] ? '
				'.((!$params['zoom_location'] && !$params['zoom']) ? '
					google.maps.event.addListenerOnce('.$params['id'].'_GeoMarker, "position_changed", function() {			
						'.$params['id'].'_bounds.extend(this.getPosition());
		           		'.$params['id'].'_map.fitBounds('.$params['id'].'_bounds);
			        });
			    ' : ((!$params['zoom']) ? '			    	
			        google.maps.event.addListenerOnce('.$params['id'].'_GeoMarker, "position_changed", function() {
			          '.$params['id'].'_map.setCenter(this.getPosition());
			          '.$params['id'].'_map.fitBounds(this.getBounds());
			        });
			    ' : 'google.maps.event.addListenerOnce('.$params['id'].'_GeoMarker, "position_changed", function() {
			            '.$params['id'].'_map.setCenter(this.getPosition());
						'.$params['id'].'_map.setZoom('.$params['zoom'].');
			        });
			    ')).'
			' : '').'
						
	        '.$window.'

		</script>';	
		
		return $return;
	}
	
	public function directions($origin, $destination, $params = array())
	{
		$this->EE->load->library('directions');
		$this->EE->load->model('gmap_log_model');
		
		$proxy_url = config_item('gmap_directions_proxy_url');

		if($proxy_url)
		{
			$this->EE->directions->base_url = $proxy_url;
		}
		
		$url 	= $this->EE->directions->construct_url(urlencode($origin), urlencode($destination), $params);
		
		$cache 	= $this->EE->gmap_log_model->check_response($url, 'directions', $this->EE->localize->now);
		
		if($cache->num_rows() == 0)
		{
			$directions = $this->EE->directions->query($origin, $destination, $params);
			
			$this->EE->gmap_log_model->cache_response($url, $directions, 'directions');		
		}
		else
		{
			$directions = json_decode($cache->row('response'));
		}
	
		return $directions;
	}
	
	public function search_cache()
	{
		if($this->EE->input->post('init_gmap_search') == 'y')
		{	
			// $this->EE->functions->set_cookie('gmap_last_post', serialize($_POST), strtotime('+1 year'));

			setcookie('gmap_last_post', serialize($_POST), strtotime('+1 year'), '/');	

		}
		else
		{
			// $cookie = $this->EE->input->cookie('gmap_last_post');
			
			$cookie = isset($_COOKIE['gmap_last_post']) ? $_COOKIE['gmap_last_post'] : FALSE;

			if($cookie)
			{
				$_POST = unserialize($cookie);
			}
		}
	}
	
	public function event($params = array())
	{
		$params = array_merge(array(
			'args'     => 'event',
			'id'       => isset($params['map']) ? $params['map'] : 'map',
			'event'    => 'click',
			'obj'      => 'map_markers[map_markers.length - 1]',
			'callback' => ''
		), $params);
		
		$callback = $params['callback'] ? $params['callback'] : '';
		
		return '<script type="text/javascript">
		google.maps.event.addDomListener('.$params['obj'].', \''.$params['event'].'\', function('.$params['args'].') { 
			'.$callback.'
		});
		</script>';
	}
	
	public function infobox($params)
	{
		$default_params = array(
			'content'         => '',
			'options'         => array(),
			'var'             => $params['id'].'_markers[index]',
			'inner_class' 	  => 'ui-infowindow-content',
			'open_windows'    => FALSE,
			'show_one_window' => FALSE,
			'script_tag'	  => FALSE,
			'trigger' 		  => 'click'
		);
		
		$params = array_merge($default_params, $params);
		
		if(!isset($params['content']) || empty($params['content']))
		{
			return NULL;	
		}
		
		$default_options = array(
			'alignBottom'			 => 'true',
			'boxClass'				 => 'ui-infowindow',
			'boxStyle'				 => '',
			'clearanceX'			 => 1,
			'clearanceY'		 	 => 1,
			'closeBoxMargin'		 => '10px 2px 2px 2px',
			'closeBoxURL'		 	 => 'http://www.google.com/intl/en_us/mapfiles/close.gif',
			'inner_class'		 	 => $params['inner_class'],
			'content'				 => $params['content'],
			'disableAutoPan'		 => 'false',
			'enableEventPropagation' => 'false',
			'maxWidth'				 => 0,
			'offsetX'				 => 0,
			'offsetY'				 => 0,
			'isHidden'				 => 'false',
			'pane'					 => 'floatPane',
			'zIndex'				 => 'null',
			'show_one_window'		 => false,
			'open_windows'			 => false,
			'options' 		=> array(
				'location'	=> 'new google.maps.LatLng(0, 0)',
				'content'	=> ''
			),
			'script_tag' 	=> FALSE
		);
		
		$options = array_merge($default_options, $params['options']);
		
		$js = '
			(function () {
				
				var options = {
					alignBottom: '.$options['alignBottom'].',
					boxClass: "'.$options['boxClass'].'",
					boxStyle: {'.$options['boxStyle'].'},
	                closeBoxMargin: "'.$options['closeBoxMargin'].'",
	                closeBoxURL: "'.$options['closeBoxURL'].'",
	                content:  \'{exp:gmap:clean_js}<div class="'.$options['inner_class'].'">'.$this->clean_js($params['content']).'</div>{/exp:gmap:clean_js}\',
	                disableAutoPan: false,
	                enableEventPropagation: '.$options['enableEventPropagation'].',
	                infoBoxClearance: new google.maps.Size('.$options['clearanceX'].', '.$options['clearanceY'].'),
	                isHidden: '.$options['isHidden'].',
	                maxWidth: '.$options['maxWidth'].',
	                pane: "'.$options['pane'].'",
	                pixelOffset: new google.maps.Size('.$options['offsetX'].', '.$options['offsetY'].'),
	                zIndex: null
		        };

				var infowindow 	= new InfoBox(options);
					
				var obj	= '.$params['var'].';		
				';
			
				if(isset($params['open_windows']) && $params['open_windows'])
				{
					$js .= '
					if(obj.strokeColor) {
						var bounds  = new google.maps.LatLngBounds();

						obj.getPath().forEach(function(latLng, i) {
							bounds.extend(latLng);
						});

						infowindow.setPosition(bounds.getCenter());
						
						infowindow.open('.$params['id'].'_map);
					}
					else {
						infowindow.open('.$params['id'].'_map, obj);
					}
					';
				}
		
			$js .= '			
				
				'.$params['id'].'_windows.push(infowindow);

				var callback = function(obj, e) {
					obj.position = e.latLng;							
					obj.getPosition = function() {
						return e.latLng;
					};';

					if(isset($params['show_one_window']) && $params['show_one_window'])
					{
						$js .= '					
						for(var i = 0; i < '.$params['id'].'_windows.length; i++) {
							'.$params['id'].'_windows[i].close();
						}';					
					}
					
					$js.='
					obj.window.setPosition(e.latLng);

					if(obj.strokeColor) {
						obj.window.open('.$params['id'].'_map);
					}
					else {
						obj.window.open('.$params['id'].'_map, obj);
					}					
				};			

				obj.window = infowindow;		

				if(!'.$params['id'].'_oms) {
					google.maps.event.addListener(obj, "'.$params['trigger'].'", function(e) {
						callback(obj, e);					
					});
				}
				else if(!'.$params['id'].'_oms.clickEventAdded) {
					'.$params['id'].'_oms.addListener("'.$params['trigger'].'", function(marker, e) {
						callback(marker, e);
					});

					'.$params['id'].'_oms.addListener("spiderfy", function(markers) {
					  for(var x in '.$params['id'].'_windows) {
					  	var window = '.$params['id'].'_windows[x];

					  	window.close();
					  }
					});

					'.$params['id'].'_oms.clickEventAdded = true;
				}

				'.$params['id'].'_window = infowindow;
				
			})();
		';
		
		return $js;
	}
	
	public function infowindow($params)
	{
		$default_params = array(
			'content'         => '',
			'options'         => array(),
			'var'             => $params['id'].'_markers[index]',
			'inner_class' 	  => 'ui-infowindow-content',
			'open_windows'    => FALSE,
			'show_one_window' => FALSE,
			'script_tag'	  => FALSE,
			'trigger' 		  => 'click'
		);
		
		$params = array_merge($default_params, $params);

		if(!isset($params['content']) || empty($params['content']))
		{
			return NULL;	
		}

		if(!empty($params['options']['offsetX']) || !empty($params['options']['offsetY']))
		{
			$options['pixelOffset'] = 'new google.maps.Size('.$params['options']['offsetX'].', '.$params['options']['offsetY'].')';	
		}
			
		if(!isset($options['content']))
		{
			$options['content'] = '\'{exp:gmap:clean_js}<div class="'.$params['inner_class'].'">'.$this->clean_js($params['content']).'</div>{/exp:gmap:clean_js}\'';
		}
		
		$obj = $this->convert_to_js($options);
		
		$js = '
			(function () {
				var infowindow 	= new google.maps.InfoWindow('.$obj.');	
				var obj			= '.$params['var'].';
				';
			
				if(isset($params['open_windows']) && $params['open_windows'])
				{
					$js .= '
					if(obj.strokeColor) {
						var bounds  = new google.maps.LatLngBounds();

						obj.getPath().forEach(function(latLng, i) {
							bounds.extend(latLng);
						});

						infowindow.setPosition(bounds.getCenter());

						infowindow.open('.$params['id'].'_map);
					}
					else {
						infowindow.open('.$params['id'].'_map, obj);
					}
					';
				}
		
			$js .= '			
				'.$params['id'].'_windows.push(infowindow);';
				
			$js .= '	

				var callback = function(obj, e) {
					var currentPos = e.latLng;
				';

					if(isset($params['show_one_window']) && $params['show_one_window'])
					{
						$js .= '					
						for(var i = 0; i < '.$params['id'].'_windows.length; i++) {
							'.$params['id'].'_windows[i].close();
						}';					
					}
					
			$js.='
					obj.window.setPosition(currentPos);

					if(obj.strokeColor) {
						obj.window.open('.$params['id'].'_map);
					}
					else {
						obj.window.open('.$params['id'].'_map, obj);
					}
				};	

				obj.window = infowindow;		

				if(!'.$params['id'].'_oms) {
					google.maps.event.addListener(obj, "'.$params['trigger'].'", function(e) {
						callback(obj, e);					
					});
				}
				else if(!'.$params['id'].'_oms.clickEventAdded) {
					'.$params['id'].'_oms.addListener("'.$params['trigger'].'", function(marker, e) {
						callback(marker, e);
					});

					'.$params['id'].'_oms.addListener("spiderfy", function(markers) {
					  for(var x in '.$params['id'].'_windows) {
					  	var window = '.$params['id'].'_windows[x];

					  	window.close();
					  }
					});

					'.$params['id'].'_oms.clickEventAdded = true;
				}

				'.$params['id'].'_window = infowindow;
			})();
		';
		
		return $js;
	}
	
	public function init($map_id, $options = FALSE, $args = array(), $visual_refresh = TRUE)
	{
		$cluster = array(
			'maxZoom'  => $options['clusterMaxZoom'],
			'gridSize' => $options['clusterGridSize'],
			'styles'   => $options['clusterStyles']
		);

		unset($options['clusterMaxZoom']);
		unset($options['clusterGridSize']);
		unset($options['clusterStyles']);

		$obj = $this->convert_to_js($options);

		$class = isset($args['plugin']['class']) ? $args['plugin']['class'] : '';
		$style = isset($args['plugin']['style']) ? $args['plugin']['style'] : '';
		
		$js = '';

		if(!isset($options['no_div']) || !$options['no_div'])
		{
			$js .= '<div id="'.$map_id.'" class="'.$class.'" style="'.$style.'"></div>';
		}

		$js .= '
		<script type="text/javascript">
			
			'.($visual_refresh ? 'google.maps.visualRefresh = true;' : false).'
			
			var '.$map_id.'_options 			= '.$obj.';
			var '.$map_id.'_canvas 				= document.getElementById("'.$map_id.'");
			var '.$map_id.'_map					= new google.maps.Map('.$map_id.'_canvas, '.$map_id.'_options);
			var '.$map_id.'_bounds				= new google.maps.LatLngBounds();
			var '.$map_id.'_markers 			= [];
			var '.$map_id.'_window 				= {};
			var '.$map_id.'_windows 			= [];
			var '.$map_id.'_responses 			= [];
			var '.$map_id.'_html				= [];
			var '.$map_id.'_waypoints 			= [];
			var '.$map_id.'_regions 			= [];
			var '.$map_id.'_isRetina 			= window.devicePixelRatio > 1;
			var '.$map_id.'_geocoder 			= new google.maps.Geocoder();
			var '.$map_id.'_directionsService 	= new google.maps.DirectionsService();
			var '.$map_id.'_directionsDisplay	= new google.maps.DirectionsRenderer({map: '.$map_id.'_map});
			var '.$map_id.'_clusterOptions		= {maxZoom: '.$cluster['maxZoom'].', gridSize: '.$cluster['gridSize'].', styles:'.$cluster['styles'].'};
			var '.$map_id.'_cluster				= new MarkerClusterer('.$map_id.'_map, '.$map_id.'_markers, '.$map_id.'_clusterOptions);
			var '.$map_id.'_oms					= false;
		</script>
		';
			
		return $js;
	}
	
	public function latlng($latitude, $longitude, $script = FALSE)
	{	
		$js = $this->return_js('new google.maps.LatLng('.$latitude.','.$longitude.');', $script);
	
		return $js;
	}
	
	public function marker($params)
	{
		$default_params = array(
			'options'           => array(),
			'data'              => array(),
			'append_data'       => array(),
			'extend_bounds'     => FALSE,
			'retina'     		=> FALSE,
			'size'     			=> FALSE,
			'scaledSize'     	=> FALSE,
			'retinaSize'     	=> FALSE,
			'retinaScaledSize'	=> FALSE,
			'script_tag'        => TRUE,
			'duplicate_markers' => TRUE,
			'clustering' 		=> FALSE,
			'window_trigger' 	=> 'click',
			'redirect'			=> FALSE,
			'category'			=> FALSE
		);
		
		$params = array_merge($default_params, $params);

		$show_one_window = isset($params['infowindow']['show_one_window']) ? $params['infowindow']['show_one_window'] : FALSE;
		$open_windows = isset($params['infowindow']['open_windows']) ? $params['infowindow']['open_windows'] : FALSE;
		
		unset($params['options']['show_one_window']);
		unset($params['options']['open_windows']);
		
		$string_params = array('title', 'icon', 'infowindow');
		
		foreach($string_params as $param)
		{
			if(isset($params['options'][$param])) {
	
				$params['options'][$param] = '"'.str_replace("\"", "\\\"", $params['options'][$param]).'"';
			}			
		}
		
		$js     = NULL;
		$limit  = isset($params['limit']) && $params['limit'] !== FALSE ? (int) $params['limit'] : FALSE;
		$offset = isset($params['offset']) ? (int) $params['offset'] : FALSE;

		$data_count = 0;

		foreach($params['data'] as $response)
		{
			foreach($response->results as $data_index => $result)
			{
				// Limit the results
				if($limit === FALSE || $data_count < $limit)
				{
					// Offset the results
					if($data_index >= $offset || $offset === FALSE)
					{
						// Verify that the results are an object
						if(is_object($result))
						{
							$data_count++;

							$options 	= $params['options'];
							$latitude 	= $result->geometry->location->lat;
							$longitude 	= $result->geometry->location->lng;
							$options['map']		 = $params['id'].'_map';
							$options['position'] = rtrim($this->LatLng($latitude, $longitude), ';');

							$icon = isset($options['icon']) ? $options['icon'] : '""';

							if(empty($icon) || $icon == '""')
							{
								if(isset($result->icon))
								{
									$icon = '"'.$result->icon.'"';
								}					
							}

							$options['icon'] = $icon;
							
							$icon_options = array(
								'url'        => $icon
							);
							
							if($params['size'])
							{
								$icon_options['size'] = 'new google.maps.Size('.$params['size'].')';	
							}
							
							if($params['scaledSize'])
							{
								$icon_options['scaledSize'] = 'new google.maps.Size('.$params['scaledSize'].')';	
							}
							
							if($params['retina'] && $icon != "")
							{
								$filename = basename(ltrim(rtrim($icon, '"'), '"'));
								$ext 	  = pathinfo($filename, PATHINFO_EXTENSION);
								$filebase = str_replace('.'.$ext, '', $filename);
								
								$retina_name = $filebase . '@2x' . '.' . $ext;
								$retina_icon = str_replace($filename, $retina_name, $icon);
								
								$options['icon']     = '(' . $params['id'].'_isRetina ? ' . $retina_icon . ' : '. $icon . ')';
									
								if($params['retinaSize'])
								{
									if(!$params['size'])
									{
										$params['size'] = 'new google.maps.Size('.$params['retinaSize'].')';	
									}
									
									$icon_options['size'] = '(' . $params['id'].'_isRetina ? new google.maps.Size(' . $params['retinaSize'] . ') : '. $params['size'] . ')';
								}
								
								if($params['retinaScaledSize'])
								{
									if(!$params['scaledSize'])
									{
										$params['scaledSize'] = 'new google.maps.Size('.$params['retinaScaledSize'].')';	
									}
									
									$icon_options['scaledSize'] = '(' . $params['id'].'_isRetina ? new google.maps.Size(' . $params['retinaScaledSize'] . ') : '. $params['scaledSize'] . ')';	
								}
								
								$icon_options['url'] = $options['icon'];
							}
							
							if($icon_options['url'] != '""')
							{
								$options['icon'] = ''.$this->convert_to_js($icon_options).'';
							}
							
							$js .= '
							var index = '.$params['id'].'_markers.length;';

							if(isset($params['options']['infowindow'])) {
								$infowindow = $params['options']['infowindow'];
								unset($params['options']['infowindow']);
							}

							if(isset($params['clustering']) && $params['clustering'])
							{
								unset($options['map']);
							}

							if(!$params['duplicate_markers'])
							{
								$js .= '
								var newMarker   = new google.maps.Marker('.$this->convert_to_js($options).');
								var isDuplicate = false;
								
								if(typeof '.$params['id'].'_positions == "undefined") {
									'.$params['id'].'_positions = [];
								}
								
								if('.$params['id'].'_markers.length > 0) {
									for(var i = 0; i < '.$params['id'].'_markers.length; i++) {
								
										var marker = '.$params['id'].'_markers[i];
										
										var a = newMarker.getPosition();
										var b = marker.getPosition();

										if(a.lat() == b.lat() && a.lng() == b.lng()) {
											newMarker.setMap(null);
										}		
									};
								}
								
								'.$params['id'].'_positions.push(newMarker.getPosition());
								'.$params['id'].'_markers[index] = newMarker;';
							}
							else
							{
								$js .= $params['id'].'_markers[index] = new google.maps.Marker('.$this->convert_to_js($options).');';
							}
							
							if(isset($result->title))
							{
								$js .= $params['id'].'_markers[index].title = \''.$this->clean_js($result->title).'\';';
							}
							
							if(isset($result->content))
							{
								$js .= $params['id'].'_markers[index].content = \''.$this->clean_js($result->content).'\';';
							}
							
							if(isset($params['redirect']) && $params['redirect'])
							{
								$js .= '
								google.maps.event.addListener('.$params['id'].'_markers[index], \'click\', function() {
									window.location = "'.$this->clean_js($params['redirect']).'";
								});';
							}
							
							if(isset($params['clustering']) && $params['clustering'])
							{
								$js .= $params['id'].'_cluster.addMarker('.$params['id'].'_markers[index]);';
							}

							$js .= $params['id'].'_oms ? '.$params['id'].'_oms.addMarker('.$params['id'].'_markers[index]) : false;';
							
							if(isset($params['entry_id']))
							{
								$js .= $params['id'].'_markers[index].entry_id = '.$params['entry_id'].';';
							}
							
							$js .= $params['id'].'_markers[index].index = '.$data_index.';';
							
							if(isset($params['category']) && $params['category'] !== FALSE)
							{								
								$js .= $params['id'].'_markers[index].category = '.json_encode(explode('|', $params['category'])).';';
							}
							
							if(!empty($params['append_data']))
							{
								$js .= $params['id'].'_markers[index].data = '.json_encode($params['append_data']).';';
							}
							
							if($params['extend_bounds'])
							{
								if(isset($params['exclude_single_marker']) && $params['exclude_single_marker'])
								{
									$js .= 
									$params['id'].'_bounds.extend('.$options['position'].');' . 
									$params['id'].'_map.fitBounds('.$params['id'].'_bounds);';
								}
								else
								{	
									$js .=
									$params['id'].'_bounds.extend('.$options['position'].');
									if (index > 0) {' .
										// multiple markers, fit bounds
										$params['id'].'_map.fitBounds('.$params['id'].'_bounds);
									} else {' .
										// single marker, center around marker and set zoom
										$params['id'].'_map.setCenter('.$params['id'].'_bounds.getCenter());' .
										$params['id'].'_map.setZoom('.$params['id'].'_options.zoom);
									}';
								}
							}
							
							if(isset($params['infowindow']) || isset($infowindow) || isset($result->content))
							{
								
								$geocoded_response = $this->parse_geocoder_response(array((object) array('results' => array($result))));
								$geocoded_response = $this->EE->channel_data->utility->add_prefix('marker', $geocoded_response);

								$content = isset($params['infowindow']['content']) ? $params['infowindow']['content'] : NULL;
								$content = $content == NULL && isset($result->content) ? $this->EE->google_maps->clean_js($result->content) : $content;

								$content = $this->parse($geocoded_response, $content);
								
								if(isset($params['infobox']) && $params['infobox'])
								{							
									$js .= $this->EE->google_maps->infobox(array(
										'id'              => $params['id'],
										'content'         => trim($content),
										'options'         => $params['infowindow']['options'],
										'script_tag'      => FALSE,
										'var'             => $params['id'].'_markers[index]',
										'show_one_window' => $show_one_window,
										'open_windows'    => $open_windows,
										'trigger'         => $params['window_trigger']
									));
								}
								else
								{
									$js .= $this->EE->google_maps->infowindow(array(
										'id'				=> $params['id'],
										'content'			=> trim($content), 
										'options'			=> $params['infowindow']['options'],
										'script_tag'		=> FALSE,
										'var'				=> $params['id'].'_markers[index]',
										'show_one_window' 	=> $show_one_window,
										'open_windows'		=> $open_windows,
										'trigger'			=> $params['window_trigger']
									));
								}
							}
						}
					}
				}
			}
		}

		$js = $this->return_js($js, $params['script_tag']);
		
		return $js;
	}
	
	public function geocode($query, $limit = FALSE, $offset = 0)
	{
		$this->EE->load->library('geocoder');

		$proxy_url = config_item('gmap_geocoder_proxy_url');

		if($proxy_url)
		{
			$this->EE->geocoder->base_url = $proxy_url;
		}
		
		$this->EE->load->model('gmap_log_model');
		
		$query 		= explode('|', $query);
		$response 	= array();
		
		if($this->EE->config->item('gmap_force_http'))
		{
			$this->EE->geocoder->secure = FALSE;
		}

		$this->EE->load->helper('url');
		
		foreach($query as $query)
		{
			$url = $this->EE->geocoder->query($query, $limit, $offset, FALSE, TRUE);

			$cache 	= $this->EE->gmap_log_model->check_response($url, 'geocode', $this->EE->localize->now);
			
			if($cache->num_rows() == 0)
			{			
				$data = $this->EE->geocoder->query($query, $limit, $offset);
				
				$this->EE->gmap_log_model->cache_response($url, $data, 'geocode');
				$response[] = $data;
			}
			else
			{
				$response[] = json_decode($cache->row('response'));
			}
		}
		
		return $response;
	}

	public function geocode_js($map_id, $query, $callback = '')
	{
		$this->EE->load->library('geocoder');

		return $this->EE->geocoder->javascript($map_id, $query, $callback);
	}
	
	public function convert_metric($metric = 'miles')
	{
		
		$metrics = array(
			'miles' 	 => 1,
			'feet'  	 => 5280,
			'kilometres' => 1.609344,
			'kilometers' => 1.609344,
			'metres'	 => 1609.344,
			'meters'	 => 1609.344
		);
		
		/*
		$mile = 3956.547;
		$kilo = $mile; //6367.445;

		$metrics = array(
			'miles' 	 => $mile,
			'feet'  	 => $mile * 5280,
			'kilometres' => $kilo,
			'kilometers' => $kilo,
			'metres'	 => $kilo * 1609.344,
			'meters'	 => $kilo * 1609.344
		);		
		*/

		$metric = strtolower($metric);
		$return = isset($metrics[$metric]) ? $metrics[$metric] : $metrics['miles'];
		
		return isset($metrics[$metric]) ? $metrics[$metric] : $metrics['miles'];
	}
	
	public function parse_geocoder_response($results, $limit = FALSE, $offset = 0, $prefix = '', $entry_id = FALSE)
	{
		$vars 	= array();
		$count 	= 0;

		foreach($results as $row)
		{
			foreach($row->results as $index => $result)
			{
				if(!$result)
				{
					continue;
				}

				if($limit === FALSE || $count < $limit && $index >= $offset)
				{
					$vars[$count][$prefix.'title'] 				= isset($result->title) ? $result->title : NULL;
					$vars[$count][$prefix.'content']			= isset($result->content) ? $result->content : NULL;
					$vars[$count][$prefix.'address_components'] = isset($result->address_components) ? $this->object_to_array($result->address_components) : array();
					$vars[$count][$prefix.'formatted_address']	= isset($result->formatted_address) ? $result->formatted_address : NULL;
					$vars[$count][$prefix.'latitude']			= $result->geometry->location->lat;
					$vars[$count][$prefix.'longitude']			= $result->geometry->location->lng;
					$vars[$count][$prefix.'location_type']		= isset($result->geometry->location_type) ? $result->geometry->location_type : NULL;
					$vars[$count][$prefix.'types'] 				= isset($result->types) ? implode('|', $result->types) : NULL;
					$vars[$count][$prefix.'count']				= $count+1;
					$vars[$count][$prefix.'index']				= $index;
					$vars[$count][$prefix.'row_index'] 			= isset($this->EE->extensions->last_call['count']) ? (int) $this->EE->extensions->last_call['count'] - 1 : 0;
					$vars[$count][$prefix.'row_count'] 			= isset($this->EE->extensions->last_call['count']) ? (int) $this->EE->extensions->last_call['count'] : 1;
					$vars[$count][$prefix.'limit']				= $limit;
					$vars[$count][$prefix.'offset']				= $offset;
					$vars[$count][$prefix.'icon']				= isset($result->icon) ? $result->icon : NULL;

					if($entry_id)
					{
						$vars[$count][$prefix.'entry_id']		= $entry_id;
					}

					foreach($vars[$count][$prefix.'address_components'] as $component_index => $component_val)
					{
						$vars[$count][$prefix.'address_components'][$component_index]['long_name'] = $component_val['long_name'];
						$vars[$count][$prefix.'address_components'][$component_index]['short_name'] = $component_val['short_name'];
						$vars[$count][$prefix.'address_components'][$component_index]['types'] = implode('|', $component_val['types']);
					}

					$count++;
				}
			}
		
		}

		return $vars;
	}

	public function parse_regions($results, $prefix = FALSE, $entry_id = FALSE)
	{
		$vars  = array();
		$count = 0;

		$regions = array();

		foreach($results->results as $index => $region)
		{
			$regions[$index]['title']		   = $region->title;
			$regions[$index]['content']		   = $region->content;
			$regions[$index]['total_points']   = count($region->coords);
			$regions[$index]['total_coords']   = count($region->coords);
			
			$regions[$index]['strokeColor']    = $region->style->strokeColor;
			$regions[$index]['strokeOpacity']  = $region->style->strokeOpacity;
			$regions[$index]['strokeWeight']   = $region->style->strokeWeight;
			$regions[$index]['fillColor']      = $region->style->fillColor;
			$regions[$index]['fillOpacity']    = $region->style->fillOpacity;
			
			$regions[$index]['stroke_color']   = $region->style->strokeColor;
			$regions[$index]['stroke_opacity'] = $region->style->strokeOpacity;
			$regions[$index]['stroke_weight']  = $region->style->strokeWeight;
			$regions[$index]['fill_color']     = $region->style->fillColor;
			$regions[$index]['fill_opacity']   = $region->style->fillOpacity;

			if($entry_id)
			{
				$regions[$index]['entry_id']   = $entry_id;
			}

			$coords = array();

			foreach($region->coords as $coord_index => $coord)
			{
				$coords[] = $coord->lat.','.$coord->lng;

				$regions[$index]['coords'][$coord_index] = array(
					'lat' => $coord->lat,
					'lng' => $coord->lng
				);
			}

			$regions[$index]['coord_string'] = implode('|', $coords);

			if($prefix)
			{
				$regions = $this->EE->channel_data->utility->add_prefix($prefix, $regions);
			}
		}

		return $regions;
	}
	
	public function route($params = array()) 
	{
		$default_params = array(
			'options'		=> array(),
			'data'			=> array(),
			'extend_bounds'	=> FALSE,
			'script_tag'	=> TRUE
		);
		
		$params = array_merge($default_params, $params);
		
		$points = array();
		
		foreach($params['data'] as $response)
		{
	
			foreach($response->results as $result)
			{			
				$lat 	= $result->geometry->location->lat;
				$lng 	= $result->geometry->location->lng;
				
				$points[]	=  '"'.$lat . ',' . $lng.'"';
			}
		}
		
		$last_index		= count($points) - 1;
		
		$request = array(
			'origin' 		=> $points[0],
			'destination'	=> $points[$last_index],
			'travelMode'	=> 'google.maps.TravelMode.DRIVING'
		);
		
		unset($points[0]);
		unset($points[$last_index]);	
		
		$waypoints = array();
		
		if(count($points) > 0)
		{
			foreach($points as $point)
			{
				$point =  explode(',', str_replace('"', '', $point));
				$lat = $point[0];
				$lng = $point[1];
				
				$waypoints[] = '{location: '.str_replace(';', '', $this->latlng($lat, $lng)).', stopover: true}';
			}
			
			$request['waypoints'] = '['. implode(',', $waypoints).']';
		}
		
		$request = $this->convert_to_js(array_merge($request, $params['options']));
		
		$preserveViewport = !$params['extend_bounds'] ? $params['id'].'_directionsDisplay.setOptions({preserveViewport: true})' : '';
		
		$js = '
			var request = '.$request.';
			
			'.$preserveViewport.'
			
			'.$params['id'].'_directionsService.route(request, function(response, status) {
				if(status == google.maps.DirectionsStatus.OK) {
					'.$params['id'].'_directionsDisplay.setDirections(response);
				}
			});
		';
		
		return $js;
	}
	
	public function region($params = array())
	{
		$default_params = array(
			'options'			=> array(),
			'data'				=> array(),
			'extend_bounds'		=> FALSE,
			'script_tag'		=> TRUE,
			'entry_id'			=> 0,
			'redirect'          => FALSE,
		);
		
		$params = array_merge($default_params, $params);

		$js = NULL;
		
		foreach($params['data'] as $response)
		{
			foreach($response->results as $result)
			{
				$js .= '
				var paths = '.json_encode($result->coords).';
				
				var region = {
					paths: [],
					strokeColor: "'.$result->style->strokeColor.'",
					strokeOpacity: '.$result->style->strokeOpacity.',
					strokeWeight: '.$result->style->strokeWeight.',
					fillColor: "'.$result->style->fillColor.'",
					fillOpacity: '.$result->style->fillOpacity.',
					entry_id: '.$params['entry_id'].'						
				}
				
				for(var x = 0; x < paths.length; x++) {
					var path = new google.maps.LatLng(paths[x].lat, paths[x].lng);
					
					'.$params['id'].'_bounds.extend(path);
					
					region.paths.push(path);
				}
				
				var index = '.$params['id'].'_regions.length;';
				
				if($params['extend_bounds'])
				{
					$js  .= '
					'.$params['id'].'_map.fitBounds('.$params['id'].'_bounds);';
				}
				

				$js .= '				
				'.$params['id'].'_regions[index] = new google.maps.Polygon(region);					
				'.$params['id'].'_regions[index].setMap('.$params['id'].'_map);';
				
				if(isset($params['redirect']) && $params['redirect'])
				{
					$js .= '
					google.maps.event.addListener('.$params['id'].'_regions[index], \'click\', function() {
						window.location = "'.$this->clean_js($params['redirect']).'";
					});';
				}
				

				if($params['infowindow'])
				{
					if(isset($result->content) && !empty($result->content) || isset($params['infowindow']))
					{
						$content = isset($result->content) && !empty($result->content) ? $result->content : null;
						$content = isset($params['infowindow']['content']) && $params['infowindow']['content'] ? $params['infowindow']['content'] : $content;
						
						if(isset($params['infobox']) && $params['infobox'])
						{
							$js .= $this->EE->google_maps->infobox(array(
								'id'				=> $params['id'],
								'content'			=> $content,
								'options'			=> $params['infowindow']['options'],
								'script_tag'		=> FALSE,
								'var'				=> $params['id'].'_regions[index]',
								'open_windows'		=> isset($params['infowindow']['options']['open_windows']) ? 
													   $params['infowindow']['options']['open_windows'] : 
													   false
							));
						}
						else
						{						
							$js .= $this->EE->google_maps->infowindow(array(
								'id'			=> $params['id'],
								'content'		=> $content,
								'options'		=> $params['options'],
								'script_tag'	=> FALSE,
								'var'			=> $params['id'].'_regions[index]',
								'open_windows'	=> isset($params['infowindow']['options']['open_windows']) ? 
												   $params['infowindow']['options']['open_windows'] : 
												   false
							));
						}
					}
				}
			}
		}
		
		$js = $this->return_js($js, $params['script_tag']);
		
		return $js;
	}
	
	public function zoom($map_id, $zoom, $script = TRUE)
	{
		$js = $map_id.'_map.setZoom('.$zoom.');';
		$js = $this->return_js($js, $script);
		
		return $js;
	}
		
	public function return_js($js, $include_script_tag = TRUE)
	{
		$return = $js;
		
		if($include_script_tag)
			$return = '<script type="text/javascript">'.$js.'</script>';
		else
			$return = $js;
		
		return $return;
	}
	
	public function is_checked_or_selected($post, $item)
	{
		if(is_array($post))
		{
			foreach($post as $post_index => $post_value)
			{											
				if($item == $post_value)
				{
					return TRUE;
				}
			}									
		}
		else
		{
			if($item == $post)
			{	
				return TRUE;								
			}
		}
		
		return FALSE;
	}
	
	
	public function prep_sql_fieldname($field_array, $user_value = FALSE, $to_append = TRUE)
	{	
		$reserved_fields = array('title', 'status', 'expiration_date', 'entry_date', 'author_id');
		$return = FALSE;
		$string = array();
		
		//Converts a single field to an array
		$field_array = is_array($field_array) ? $field_array : array($field_array => '');
		
		//Loops through the field array
		foreach($field_array as $field_name => $field_value)
		{	
			$value = FALSE;
			
			//Fallsback to the post variable if no value is passed
			$value = !empty($field_value) ? $field_value : $user_value;			
			$value = $value ? $value : $this->EE->input->post($field_name);
												
			//Creates the SQL field name by removed the reserved terms
			$sql_field_name = str_replace($this->reserved_terms, '', $field_name);

			//Gets the field data and if the field exists, the sql statement is created
			$field_data = $this->EE->channel_data->get_field_by_name($sql_field_name);
			$in_array   = in_array($sql_field_name, $reserved_fields);

			if($field_data->num_rows() > 0 || $in_array === TRUE)
			{	
				//Validates that a value is not FALSE
				if($value !== FALSE && !empty($value) || $to_append == FALSE)
				{
					if(is_string($value))
					{
						if(count($explode_value = explode('|', $value)) > 1)
						{
							$value = 'IN (\''.implode('\' OR \'', $explode_value).'\')';
						}
					}

					//If to_append is TRUE, then the operator is appended
					if($to_append == TRUE)
					{	
						//Converts a value string to a variable
						$values = is_array($value) ? $value : array($value);
						
						//Loops through the values array and creates the SQL conditions
						foreach($values as $value)
						{
							$operator = $this->prep_value($field_name, $value, $field_data->row('field_id'));
		
							if($in_array)
							{
								$string[] = '`'.$sql_field_name.'` '.$operator;
							}
							else
							{
								$string[] = '`field_id_'.$field_data->row('field_id').'` '.$operator;
							}
						}
					}
					else
					{	
						if($in_array)
						{
							$string[] = '`'.$field_name.'` '.$operator;
						}
						else
						{			
							$string[] = '`field_id_'.$field_data->row('field_id').'`';
						}
					}
				}
			}			
		}

		return $string;
	}
	
	public function create_id_string($results)
	{		
		$id = NULL;
		
		foreach($results as $row)
			$id .= $row->entry_id . '|';
		
		return rtrim($id, '|');
	}
	
	public function prep_value($field_name, $value, $field_id = FALSE)
	{
		if(is_string($value))
		{
			$value = ltrim(rtrim($value, '\''), '\'');
		}
		if(strpos($field_name, '_min'))
		{
			$operator = ' >= \''.$value.'\'';
		}
		else if(strpos($field_name, '_max'))
		{
			$operator = ' <= \''.$value.'\'';
		}
		else if(strpos($field_name, '_like'))
		{
			$operator = ' LIKE \'%'.$value.'%\'';
		}
		else if(strpos($field_name, '_day') && $field_id)
		{
			$value = str_replace('\'', '', $value);
			$date = $this->EE->localize->convert_human_date_to_gmt(date('Y-m-d 23:59:59', $value));
			$operator = ' >= '.$this->EE->localize->convert_human_date_to_gmt(date('Y-m-d 00:00:00', $value)).' AND `field_id_'.$field_id.'` <= '.$date;
		}			
		//Preps conditional statement by testing the field_name for keywords
		else if(preg_match('/^IN \(/', $value))
		{
			$operator = $value;
		}
		else
		{
			$operator = ' = \''.$value.'\'';
		}		
		
		return $operator;
	}
		
	/**
     *
     * Convert an object to an array
     *
     * @param   object  The object to convert
     * @return	array
     *
     */
    public function object_to_array($object)
    {
        if( !is_object( $object ) && !is_array( $object ) )
        {
            return $object;
        }
        if( is_object( $object ) )
        {
            $object = get_object_vars( $object );
        }
        return array_map( array($this, 'object_to_array'), $object );
    }
    
	private function convert_to_js($options = array())
	{
		$obj = NULL;
		
		if(is_array($options))
		{
			foreach($options as $option_index => $option_value)
			{
				$option_value = (!is_null($option_value) ? $option_value : 'null');
				$option_value = (!is_bool($option_value) ? $option_value : ($option_value ? 'true' : 'false'));
				
				$obj .= $option_index . ': '.$option_value.', ';
			}
			
			$obj = '{' . rtrim(trim($obj), ',') . '}';
		}
		else
		{
			$obj = $options;
		}
		
		return $obj;
	}
	
	public function clean_js($str, $escape = TRUE)
	{
		//$this->EE->load->library('template');
		$matches = array();
		
		$str = trim($str);
		$str = preg_replace("/[\n\r\t]/", '', $str);

		if($escape)
		{
			$str = preg_replace('/\'/','\\\'', $str);
			$str = str_replace('\\\\', '\\', $str);
		}

		return $str;
	}
	
	public function parse_fields($vars, $tagdata = FALSE, $parse_tags = FALSE, $prefix = '')
	{
	
		if($tagdata === FALSE)
		{
			$tagdata = $this->EE->TMPL->tagdata;
		}
		
		$return = NULL;
		

		if($parse_tags)
		{
			$channels = $this->EE->channel_data->get_channels(array(
				'where' => array(
					'site_id' => config_item('site_id')
				)
			))->result_array();
			
			$channels = $this->EE->channel_data->utility->reindex($channels, 'channel_id');
			
			$fields = $this->EE->channel_data->get_fields()->result_array();
			$fields = $this->EE->channel_data->utility->reindex($fields, 'field_name');
			
			$count = 0;
			
			if(!isset($vars[0]))
			{
				$vars = array($vars);
			}
			
			$global_vars = $vars[0];
			unset($global_vars['results']);
			
			$TMPL = $this->EE->channel_data->tmpl->init();
			
			if(!isset($vars[0]['results']))
			{
				$vars[0]['results'] = $vars;	
			}
			
			if(count($vars[0]['results']) > 0)
			{
				foreach($vars[0]['results'] as $index => $var)
				{		
					$count++;
					
					$var = array_merge($global_vars, $var);
					$var['result_index'] = $index;
					$var['result_count'] = $index + 1;
					
					$row_tagdata = $this->EE->channel_data->tmpl->parse_fieldtypes($var, $channels, $fields, $tagdata, $prefix, $count);
					$row_tagdata = $this->EE->TMPL->parse_variables_row($row_tagdata, $vars[0]);					
					$row_tagdata = $this->EE->TMPL->parse_variables_row($row_tagdata, $vars[0]['results'][$index]);	
					$return .= $row_tagdata;
				}
			}
			else
			{
				$return = $this->EE->TMPL->parse_variables($tagdata, $vars);
			}
		}
		else
		{
			$return = $this->EE->TMPL->parse_variables($tagdata, $vars);
		}
		
		return $return;
	}
	
	public function prep_field_options($options, $field)
	{
		$return = array();
		
		//Loops through the list items for the fieldtype
		foreach($options as $item)
		{
			$checked  = '';
			$selected = '';

			//Checks to see if the entry should be checked or selected
			if($this->EE->input->post($field) !== FALSE)
			{
				$post = $this->EE->input->post($field);
				
				if($this->is_checked_or_selected($post, $item))
				{
					$checked 	= 'checked="checked"';
					$selected 	= 'selected="selected"';
				}
			}
			
			//Adds all the data to the template variable
			$return[] = array(
				'option_name'  => ucfirst($item),
				'option_value' => $item,
				'selected'	   => $selected,
				'checked'	   => $checked
			);
		}
		
		return $return;
	}
	
	function current_url($uri_segments = TRUE, $get_string = TRUE, $base_url = FALSE)
	{
		$this->EE->load->helper('addon');
		
		if(!$base_url)
		{
			$return = base_url();
		}
		else
		{
			$return = $base_url;
		}
		
		if($uri_segments)
		{
			$return .= $this->EE->uri->uri_string();
		}
		
		if($get_string && count($_GET) > 0)
		{
			$get =  array();
		
			foreach($_GET as $key => $val)
			{
				$get[] = $key.'='.$val;
			}
			
			$return .= '?'.implode('&',$get);
		}
		
		return $return;
	}  
	
	public function world_borders($params = array())
	{		
		$this->EE->load->library('kml_api');
		
		$params = array_merge(array(
			'country_code' => FALSE,
			'id' 		   => 'map',
			'asynchronous' => TRUE, 
			'options'      => array(
				'afterParse'          => NULL,
				'createOverlay'       => NULL,
				'createMarker'        => NULL,
				'failedParse'         => NULL,
				'processStyles'       => FALSE,
				'singleInfoWindow'    => FALSE,
				'suppressInfoWindows' => TRUE,
				'zoom'                => FALSE
			),
			'extend_bounds' => TRUE,
			'infobox'	 => FALSE,
			'infowindow' => FALSE,
			'style' => array(
				'strokeWeight'  => 1,
				'strokeOpacity' => .5,
				'strokeColor'   => 'blue',
				'fillOpacity'   => .3,
				'fillColor'     => 'blue' 
			)
		), $params);
		
		if(!$params['country_code'])
		{
			return;
		}
		
		//$geoxml_options = json_encode($params['options']);
		
		$content = isset($params['infowindow']['content']) ? $params['infowindow']['content'] : NULL;
		$content = $content == NULL && isset($result->content) ? $this->EE->google_maps->clean_js($result->content) : $content;
		
		$show_one_window = isset($params['infowindow']['show_one_window']) ? $params['infowindow']['show_one_window'] : FALSE;
		$open_windows = isset($params['infowindow']['open_windows']) ? $params['infowindow']['open_windows'] : FALSE;
		
		if(isset($params['infobox']) && $params['infobox'])
		{							
			$window = $this->EE->google_maps->infobox(array(
				'id'              => $params['id'],
				'content'         => $content,
				'options'         => $params['infowindow']['options'],
				'script_tag'      => FALSE,
				'var'             => $params['id'].'_regions[index]',
				'show_one_window' => $show_one_window,
				'open_windows'    => $open_windows,
				'trigger'         => $params['window_trigger']
			));
		}
		else
		{
			$window = $this->EE->google_maps->infowindow(array(
				'id'				=> $params['id'],
				'content'			=> $content, 
				'options'			=> $params['infowindow']['options'],
				'script_tag'		=> FALSE,
				'var'				=> $params['id'].'_regions[index]',
				'show_one_window' 	=> $show_one_window,
				'open_windows'		=> $open_windows,
				'trigger'			=> $params['window_trigger']
			));
		}
		
		$this->EE->load->helper('addon');
		
		if($params['asynchronous'])
		{
			$params['options']['afterParse'] = 'function(docs) { 
				
			    var polygon = docs[0].gpolygons[0];
			    
			    if(polygon) {
				    '.$params['id'].'_bounds.union(polygon.getBounds());
				    
				    polygon.setOptions('.json_encode($params['style']).');
				    
				    '.(!$params['extend_bounds'] && !$params['options']['zoom'] ? NULL : '
				    '.$params['id'].'_map.fitBounds('.$params['id'].'_bounds);').'
				    '.$params['id'].'_regions.push(polygon);
				    
				    index = '.$params['id'].'_regions.length - 1;
				    			    
				    '.$window.'
			    }
			}';
			
			$kml = action_url('gmap', 'world_borders_action', FALSE) . '&country_code='.$params['country_code'];
		}
		else
		{
			$country_border  = $this->EE->kml_model->get_country_code($params['country_code']);
			$country_border  = $country_border->row('geometry');
			
			if(is_array($country_border))
			{
				$country_border = NULL;
			}
		
			$kml = $this->EE->kml_api->prep_string($country_border, $params);
		}
		
		$geoxml_options = $this->convert_to_js($params['options']);
		
		$return = '	
		    var index   = '.$params['id'].'_regions.length;
		    var options = '.$geoxml_options.';
		    options.map = '.$params['id'].'_map;
		    		    
			var geoXml = new geoXML3.parser(options);
			
			'.(!$params['asynchronous'] ? '
				
	    	geoXml.parseKmlString(\''.$kml.'\');
	    	
	    	if(geoXml.docs[0].gpolygons[0]) {
			    '.$params['id'].'_bounds.union(geoXml.docs[0].gpolygons[0].getBounds());
			    
		    	geoXml.docs[0].gpolygons[0].setOptions('.json_encode($params['style']).');
		    
			    '.(!$params['extend_bounds'] && !$params['options']['zoom'] ? NULL : '
			    '.$params['id'].'_map.fitBounds('.$params['id'].'_bounds);').'				    
		    	'.$params['id'].'_regions.push(geoXml.docs[0].gpolygons[0]);
		    	
			    index = '.$params['id'].'_regions.length - 1;
			    			    
			    '.$window . '
			}' : 'geoXml.parse(\''.$kml.'\');').'
		';
		
		$return = '<script type="text/javascript">'.$return.'</script>';
		
		if(isset($params['script_tag']))
		{
			$url = rtrim($this->EE->theme_loader->theme_url(), '/') . '/';

			$return = '
			<script type="text/javascript" src="'.rtrim($url, '/').'/gmap/javascript/geoxml3.js"></script>
			' . $return;
		}
		
		return $return;
	}
	
	public function base_url($append = '', $value = '')
	{
		$this->EE->load->helper('addon_helper');
		
		$base_url = base_url();
		
		if(!empty($append))
		{
			$base_url .= '?'.$append.'='.$value;
		}
		
		return $base_url;
	}
	
	private function parse($vars, $tagdata = FALSE)
	{
		if($tagdata === FALSE)
		{
			$tagdata = $this->EE->TMPL->tagdata;
		}
			
		return $this->EE->TMPL->parse_variables($tagdata, $vars);
	}
	
	public function fetch_param($which, $default = FALSE)
	{
		if ( ! isset($this->EE->TMPL->tagparams[$which]))
		{
			return $default;
		}
		else
		{			
			$param = $this->EE->TMPL->tagparams[$which];
				
			// Making yes/no tag parameters consistent.  No "y/n" or "on/off".
			if(strtolower($param) === 'y' || strtolower($param) === 'on')
			{
				return 'yes';
			}
			else if(strtolower($param) === 'n' || strtolower($param) === 'off')
			{
				return 'no';
			}
			else
			{
				return $param;
			}
		}
	}
}