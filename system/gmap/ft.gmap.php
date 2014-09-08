<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.3.0
 * @build		20120522
 */

require PATH_THIRD . 'gmap/config/gmap_config.php';

if(!defined('GMAP_VERSION'))
{	
	define('GMAP_VERSION', $config['gmap_version']);
}

class Gmap_ft extends EE_Fieldtype {

	public $info = array(
		'name'			=> 'Google Maps for ExpressionEngine',
		'version'		=> GMAP_VERSION
	);
	
	public $matrix			    = FALSE;
	public $low_variables 		= FALSE;
	public $has_array_data 		= TRUE;
	public $safecracker			= FALSE;
		
	private $default_settings	= array(
		'gmap_defer_init'			=> 'no',
		'gmap_latitude'				=> '0',
		'gmap_longitude'			=> '0',
		'gmap_latitude_field'		=> '',
		'gmap_longitude_field'		=> '',
		'gmap_geocoder_field_label'	=> 'Geocoder',
		'gmap_geocoder_button'	    => 'Plot Location',
		'gmap_geocoder_field_place'	=> 'Enter an address, city, state, or coordinate',
		'gmap_no_valid_location'	=> '',
		'gmap_marker_field'			=> '',
		'gmap_waypoint_lng'			=> '',
		'gmap_zoom'					=> '12',
		'gmap_zoom_one_marker'		=> '15',
		'gmap_zoom_field'			=> '',
		'gmap_file_group'			=> '',
		'gmap_map_height'			=> '600px',
		'gmap_marker_mode'			=> 'True',
		'gmap_total_points'			=> '0',
		'gmap_min_points'			=> '0',
		'gmap_response'				=> '',
		'gmap_formatted_address'	=> '',
		'gmap_preview'				=> '',
		'gmap_scroll_wheel'			=> 'True',
		'gmap_waypoint_mode'		=> 'no',
		'gmap_waypoint_start_coord'	=> '',
		'gmap_waypoint_end_coord'	=> '',
		'gmap_region_mode'			=> 'no',
		'gmap_region_field'			=> '',
		'gmap_display_help'			=> '',
		'gmap_include_marker_title'	=> 'no',
		'gmap_file_group'			=> ''
	);
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		if(isset($this->EE->safecracker_lib) || isset($this->EE->channel_form_lib))
		{
			$this->safecracker = TRUE;
		}
		
		$this->info['version'] = config_item('gmap_version');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Installs the plugin by returning the default settings array.
	 *
	 * @access	public
	 * @return	array
	 */
	 
	function install()
	{
		return $this->default_settings;
	}
	
	/**
	 * Gets the settings directly from the database
	 *
	 * @param	array
	 * @access	public
	 * @return	array
	 */
	
	private function get_settings($merge = array())
	{
		$this->EE->load->driver('channel_data');
		
		$settings = unserialize(base64_decode($this->EE->channel_data->get_field($this->settings['field_id'])->row('field_settings')));

		/*
		if(isset($this->EE->zoo_visitor_lib))
		{
			$settings = unserialize(base64_decode($this->EE->channel_data->get_field($this->settings['field_id'])->row('field_settings')));
		}

		if($this->low_variables)
		{
			$settings = $this->settings;
		}
		else if($this->matrix)
		{
			$settings = $this->settings;
		}
		else
		{
			$settings = $this->EE->channel_data->get_field(isset($this->settings['field_id']) ? $this->settings['field_id'] : $this->settings['field_name'])->row('field_settings');
		}
		*/

		$settings = array_merge($this->settings, $merge, is_string($settings) ? unserialize(base64_decode($settings)) : $settings);
		
		foreach($settings as $index => $setting)
		{
			$settings[str_replace('gmap_', '', $index)] = $setting;
			
			if(strstr($index, 'gmap_') == TRUE)
			{
				unset($settings[$index]);
			}
		}
		
		return $settings;
	}
	
	public function display_var_field($data)
	{
		$this->low_variables = TRUE;
		
		$this->EE->load->add_package_path(PATH_THIRD . 'gmap');
		
		return $this->display_field($data);
	}
	
	public function display_cell($data)
	{
		$this->matrix = TRUE;
		
		return $this->display_field($data);
	}
	
	/**
	 * Displays the fieldtype
	 *
	 * @access	public
	 * @param 	array
	 * @return	string
	 */
	
	public function display_field($data)
	{	
		$this->EE->load->driver('channel_data');
		
		$this->EE->lang->loadfile('gmap');		
		
		if(!isset($this->EE->theme_loader))
		{
			$this->EE->load->library('theme_loader');
		}
		
		$this->EE->theme_loader->module_name = 'gmap';
		$this->EE->theme_loader->requirejs   = FALSE;
		
		$this->EE->theme_loader->javascript('https://maps.google.com/maps/api/js?sensor=true');
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js');
		
		$this->EE->theme_loader->requirejs   = TRUE;
		
		if(!$this->low_variables)
		{
			$field	 		= $this->EE->channel_data->get_field($this->settings['field_id'])->row();
			$field_group	= $this->EE->channel_data->get_field_group($field->group_id)->row();

			$this->settings = $this->get_settings(array(
				'field_name' 	=> $field->field_name,
				'field_id' 		=> $this->settings['field_id']
			));

			//$channel		= $this->EE->channel_data->get_channel($field_group->channel_id);
			
			$fields			= $this->EE->channel_data->get_fields_by_group($field->group_id, array(
				'order_by' => 'field_order',
				'sort' 	   => 'asc'
			))->result();

			$fields_array	= array();
			
			foreach($fields as $row)
			{
				$fields_array['field_id_'.$row->field_id] = $row;
			}
		}
		else
		{
			$field = array();
			$field_group = array();
			$fields = array();
			$fields_array = array();
			
			$this->settings = $this->get_settings();

			$this->settings['field_id'] = $this->var_id;
		}
		
		$this->settings['field_name'] = !$this->matrix ? $this->field_name : $this->cell_name;
		$this->settings['theme_url']  = $this->EE->theme_loader->theme_url();
		
		/*
		$req_fields		= $this->EE->channel_data->get_channel_fields($field->group_id, '*', array(
			'field_required'	=> 'y'
		))->result();
		*/
		
		$req_fields = array();
		
		$data	 		= empty($data) ? 'false' : html_entity_decode($data);
		$field			= json_encode($field);
		$fields			= json_encode($fields_array);
		$req_fields		= json_encode($req_fields);
		$settings 		= json_encode($this->settings);
		$file_group		= isset($this->settings['file_group']) ? $this->settings['file_group'] : '';
		
		if(!empty($file_group))
		{
			if($file_group == '__default__')
			{
				$this->EE->load->helper('directory');
				
				$icons = array();
				
				$url  = $this->EE->theme_loader->theme_url() . 'gmap/icons/';				
				$path = $this->EE->theme_loader->theme_path() . 'gmap/icons/';
			}
			else
			{
				$path = $this->EE->channel_data->get('upload_prefs', array(), array('id' => $this->settings['file_group']))->row();
				
				$url  = $path->url;
				$path = $path->server_path;
			}
		
			$directory_map  = directory_map($path);
			
			$icons 			= array();
				
			if($directory_map)
			{
				foreach($directory_map as $file)
				{
					if(!is_array($file))
					{
						$icons[] = (object) array('path' => $url . $file, 'name' => $file);
					}
				}	
			}
			
			$icons = json_encode($icons);
			
		}
		else {
			$icons = '[]';
		}
				
		$this->EE->load->helper('directory');
		
		$directory 		 = $this->EE->theme_loader->theme_path().'gmap/plugins/';
		$third_party_js  = directory_map($directory.'javascript/enabled/');
		
		if($third_party_js)
		{
			foreach($third_party_js as $index => $file)
			{
				$third_party_js[$index] = $this->EE->theme_loader->theme_url().'gmap/plugins/javascript/enabled/'.$file;
			}
		}
		
		$settings_js 	= '
		<script type="text/javascript">
			
			if(!GmapGlobal) var GmapGlobal = {};
			
			if(typeof GmapPluginsLoaded == "undefined") {
				var GmapPluginsLoaded = false;
			}
		
		</script>';
		
		$this->EE->cp->add_to_head($settings_js);
		
		$js = '
		var options = {
			settings: '.$settings.',
			response: '.$data.',
			markers: [],
			windows: [],
			field: '.$field.',
			fields: '.$fields.',
			reqFields: '.$req_fields.',
			icons: '.$icons.',
			plugins: '.json_encode($third_party_js).',
			safecracker: '.($this->safecracker ? 'true' : 'false').',
			channel_form: '.($this->safecracker ? 'true' : 'false').'
		};
	
		/* 
		 * DEPRECATED since v3.1 - 11/07/2012
		 * Going forward, use the Gmap.instance array to access the Gmap objects.
		 */
		 
		if(!GmapGlobal.settings) 	GmapGlobal.settings 	= [];
		if(!GmapGlobal.response)	GmapGlobal.response		= [];
		if(!GmapGlobal.markers)		GmapGlobal.markers		= [];
		if(!GmapGlobal.windows)		GmapGlobal.windows		= [];
		if(!GmapGlobal.field)		GmapGlobal.field		= [];
		if(!GmapGlobal.fields)		GmapGlobal.fields		= [];
		if(!GmapGlobal.reqFields)	GmapGlobal.reqFields	= [];
		if(!GmapGlobal.icons)		GmapGlobal.icons		= [];
		if(!GmapGlobal.object)		GmapGlobal.object		= [];
		if(!GmapGlobal.plugins)		GmapGlobal.plugins		= [];
		if(!GmapGlobal.safecracker)	GmapGlobal.safecracker  = [];
		if(!GmapGlobal.curl)		GmapGlobal.curl			= "";
		
		GmapGlobal.settings['.$this->settings['field_id'].']  	= '.$settings.';
		GmapGlobal.response['.$this->settings['field_id'].']  	= '.$data.';
		GmapGlobal.markers['.$this->settings['field_id'].']   	= [];
		GmapGlobal.windows['.$this->settings['field_id'].'] 	= [];
		GmapGlobal.field['.$this->settings['field_id'].'] 		= '.$field.';
		GmapGlobal.fields['.$this->settings['field_id'].'] 		= '.$fields.';
		GmapGlobal.reqFields['.$this->settings['field_id'].'] 	= '.$req_fields.';
		GmapGlobal.icons['.$this->settings['field_id'].']		= '.$icons.';
		GmapGlobal.plugins['.$this->settings['field_id'].']		= '.json_encode($third_party_js).';
		GmapGlobal.safecracker['.$this->settings['field_id'].']	= '.(isset($this->EE->safecracker_lib) ? 'true' : 'false').';';
				
		$this->EE->theme_loader->output($js);		
		
		if(!$this->matrix)
		{
			$this->EE->theme_loader->output('new Gmap($("#gmap-wrapper-'.$this->settings['field_id'].'"), options);');
		}
		else
		{
			$this->EE->theme_loader->output('
				
				Matrix.bind(\'gmap\', \'display\', function(cell) {
					//if(cell.row.isNew) {
						new Gmap(cell.dom.$td.find(\'.gmap-wrapper\'), Gmap.settings[cell.col.id]);
					//}
				});
			
			
				'.($this->matrix ? 'Gmap.settings[\'col_id_'.$this->col_id.'\'] = options;' : NULL).'
		
				$(document).ready(function() {
					//new Gmap($("#gmap-wrapper-'.$this->settings['field_id'].'"), options);
				});'
			);	
		}
		
		if($third_party_css = directory_map($directory.'css'))
		{
			foreach($third_party_css as $file)
			{
				$this->EE->theme_loader->css($file, 'plugins/css');
			}
		}		
		
		$this->EE->theme_loader->javascript('gmap_field');
		$this->EE->theme_loader->javascript('selectToUISlider');
		$this->EE->theme_loader->javascript('farbtastic');
		$this->EE->theme_loader->javascript('json2');
		$this->EE->theme_loader->javascript('jquery.form');
		//$this->EE->theme_loader->javascript('jquery.qtip.min');
		$this->EE->theme_loader->css('gmap');
		$this->EE->theme_loader->css('ui.slider.extras');
		$this->EE->theme_loader->css('farbtastic');
		//$this->EE->theme_loader->css('jquery.qtip');
		
		$vars = array(
			'low_variables' => $this->low_variables,
			'matrix'		=> $this->matrix,
			'settings'		=> $this->settings,
			'saved_value'	=> $data,
			'safecracker'	=> $this->safecracker,
			'field_name'	=> !$this->matrix ? $this->field_name : $this->cell_name,
			'import_url' 	=> $this->_current_url() . '?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_csv_ft_action')
		);

		return $this->EE->load->view('fieldtype', $vars, TRUE);
	}
	
	public function import_csv()
	{
		echo '{ "message": "test" }';
	}
	
	public function post_save($data)
	{
		$save_data = array();
		$post_data = json_decode($data);
		
		if(!empty($this->settings['gmap_latitude_field']) && isset($post_data->markers->results[0]->geometry->location->lat))
		{
			$save_data[$this->settings['gmap_latitude_field']] = $post_data->markers->results[0]->geometry->location->lat;
		}
		
		if(!empty($this->settings['gmap_latitude_field']) && isset($post_data->markers->results[0]->geometry->location->lng))
		{
			$save_data[$this->settings['gmap_longitude_field']] = $post_data->markers->results[0]->geometry->location->lng;
		}
		
		if(count($save_data))
		{
			$this->EE->db->where('entry_id', $this->settings['entry_id']);
			$this->EE->db->update('channel_data', $save_data);	
		}	
	}
	
	public function save($data)
	{
		return $data;
	}
	
	public function display_var_settings($data)
	{	
		$this->low_variables = TRUE;
		
		$this->EE->load->add_package_path(PATH_THIRD . 'gmap');

		return array(
			array(
				'Google Maps for ExpressionEngine',
				$this->display_settings($data)
			)
		);
	}
	
	
	public function display_cell_settings($data)
	{	
		$this->matrix = TRUE;
				
		return array(
			array(
				'Google Maps for ExpressionEngine',
				$this->display_settings($data)
			)
		);
	}
	
	

	public function replace_address_component($data, $params, $tagdata = FALSE)
	{
		// Set the default parameters
		
		$params = array_merge(array(
			'type'         => FALSE,
			'country_code' => 'US',
			'delimiter'	   => ' ',
			'output'	   => 'long_name'
		), $params);
		
		$components = array();
		$data       = json_decode($data);
		
		if(is_object($data))
		{
			foreach($data as $index => $obj)
			{
				if(isset($obj->results))
				{
					foreach($obj->results as $index => $obj_components)
					{
						$components = array_merge($components, $obj_components->address_components);
					}
				}
			}
		}
		
		$return = array();
		
		$aliases = array(
			'US' => array(
				'street_number' => 'street_number',
				'street'        => 'route',
				'city'          => 'locality',
				'county'        => 'administrative_area_level_2',
				'township'      => 'administrative_area_level_3',
				'state'         => 'administrative_area_level_1',
				'zip'           => 'postal_code',
				'zipcode'       => 'postal_code',
				'zip_code'      => 'postal_code',
				'country'       => 'country',
			),
			'AU' => array(
				'street_number' => 'street_number',
				'street'        => 'route',
				'city'          => 'locality',
				'suburb'        => 'locality',
				'state'         => 'administrative_area_level_1',
				'country'       => 'country',
				'postcode'      => 'postal_code'
			)
		);
		
		if($params['type'])
		{
			foreach($aliases[$params['country_code']] as $alias => $component)
			{
				if($params['type'] == $alias)
				{
					$params['type'] = $component;
				}
			}	
					
			foreach($components as $component)
			{
				if(in_array($params['type'], $component->types))
				{
					$return[] = $component->{$params['output']};
				}
			}
		}
		
		return implode($params['delimiter'], $return);
	}

	/**
	 * Displays the fieldtype settings
	 *
	 * @access	public
	 * @param 	array
	 * @return	string
	 */
	 
	function display_settings($data)
	{	
		$this->EE->load->driver('channel_data');
		
		if(!isset($this->EE->theme_loader))
		{
			$this->EE->load->library('theme_loader', array(
				'module_name'	=> 'gmap'
			));
		}
				
		$this->EE->theme_loader->module_name = 'gmap';
		
		// $this->EE->theme_loader->requirejs = FALSE;
		
		$this->settings = $data;
		
		$settings_js 	= '
		<script type="text/javascript">
			var settings  = '.json_encode($data).';
		</script>';
							
		$this->EE->cp->add_to_head($settings_js);
		
		$google_maps_api_url = 'https://maps.google.com/maps/api/js?sensor=true';
		
		// Works for FieldEditor and RequireJS
		
		if (AJAX_REQUEST || $this->EE->theme_loader->requirejs())
		{
			//set the callback to init after loading google map api asynchronously
			$google_maps_api_url .= '&callback=GmapPreview.init';
		}
		else//normal EE field settings, init GmapPreview on document ready
		{
			$this->EE->theme_loader->output('GmapPreview.init()');
		}
		
		$this->EE->theme_loader->javascript($google_maps_api_url);		
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.5/jquery-ui.min.js');
		$this->EE->theme_loader->javascript('gmap');
		$this->EE->theme_loader->javascript('gmap_preview');
		$this->EE->theme_loader->css('gmap');
			
				
		// load the language file
		$this->EE->lang->loadfile('gmap');
		$this->EE->load->helper('form');
		
		if(!$this->low_variables)
		{
			if(!isset($data['group_id']))
			{
				return;			
			}
	
			$fields			= $this->EE->channel_data->get_fields_by_group($data['group_id'], array(
				'order_by' => 'field_order',
				'sort' 	   => 'asc'
			))->result();
		}
		else
		{
			$fields = array();
		}		
		
		$vars 			= array();
		$options		= array('' => '');
		$file_options	= array('' => '', '__default__' => 'Default Icons');
		$bool_options 	= array(
			'no'	=> 'No',
			'yes'	=> 'Yes'
		);
		
		$icons = $this->EE->channel_data->get('upload_prefs')->result();
	
		foreach($icons as $file)
		{
			$file_options[$file->id] = $file->name;
		}
				
		foreach($fields as $field)
		{
			$options['field_id_'.$field->field_id] = $field->field_name;
		}
		
		foreach($this->default_settings as $setting => $value)
		{
			$name = $setting;
			
			if($this->low_variables)
			{
				$name = 'variable_settings[gmap]['.$name.']';
			}
			
			$value									= isset($data[$setting]) ? $data[$setting] : $value;
			$vars[$setting]							= $value;			
			$vars['lang'][$setting]					= lang($setting);
			$vars['lang'][$setting.'_description']	= lang($setting.'_description');
			$vars[$setting.'_select']  				= form_dropdown($name, $options, $value, 'id="'.$setting.'"');
			$vars[$setting.'_boolean']				= form_dropdown($name, $bool_options, $value, 'id="'.$setting.'"');
			$vars[$setting.'_upload_prefs']			= form_dropdown($name, $file_options, $value, 'id="'.$setting.'"');
		}
		
		//var_dump($vars);exit();
		
		$vars['matrix']        = $this->matrix;
		$vars['low_variables'] = $this->low_variables;
		$vars['name_prefix']   = '';
		$vars['name_suffix']   = '';
		
		if($this->low_variables)
		{
			$vars['name_prefix'] = 'variable_settings[gmap][';
			$vars['name_suffix'] = ']';
		}
		
		return $this->EE->load->view('fieldtype_settings', $vars, TRUE);
	}
	
	public function validate($data)
	{
		$this->EE->lang->loadfile('gmap');

		$valid		= TRUE;
		$response 	= json_decode($data);
		$settings	= $this->get_settings();
		$modes		= array();
		
		$total 		= isset($settings['total_points']) ? (int) $settings['total_points'] : FALSE;
		$min  		= isset($settings['min_points']) ? (int) $settings['min_points'] : FALSE;
		$obj_total	= 0;
		
		$total_markers = isset($response->markers) ? $response->markers->total : 0;
			
		if($settings['marker_mode'] == 'yes' && isset($response->markers))
		{	
			$custom_message = !empty($settings['no_valid_location']) ? $settings['no_valid_location'] : FALSE;
		
			if($total > 0 && $total < $total_markers)
			{
				return $custom_message ? $custom_message : $this->parse(array(
					'total'   => $total,
					'markers' => $total == 1 ? 'marker' : 'markers'
				), lang('gmap_over_marker_limit'));
			}
			
			if($min > 0 && $min > $total_markers)
			{
				return $custom_message ? $custom_message : $this->parse(array(
					'total'   => $min,
					'markers' => $min == 1 ? 'marker' : 'markers'
				), lang('gmap_under_marker_limit'));
			}
		}

		if($total_markers == 0 && $settings['field_required'] == 'y')
		{
			return $this->parse(array('name' => $settings['field_label']), lang('gmap_field_required'));
		}

		return TRUE;
	}
	
	public function save_var_settings($data)
	{
		$this->low_variables = TRUE;
		
		return $this->save_settings($data);
	}
	
	/**
	 * Saves the settings
	 *
	 * @access	public
	 * @param 	array
	 * @return	array
	 */
	 
	public function save_settings($data)
	{
		$validate 			= array();
		$return 			= array();
		$used_fields 		= array();
		$search_settings	= array(
			'gmap_zoom_field',
			'gmap_latitude_field',
			'gmap_longitude_field'
		);		
		
		foreach($search_settings as $field)
		{
			if(isset($data[$field]))
			{
				$validate[] = $data[$field];
			}
		}
		
		if(!$this->_validate_settings($validate))
		{
			$this->EE->output->show_user_error('error', 'You may not use the same custom field twice.');
		}
		
		foreach($this->default_settings as $setting => $value)
		{
			$return[$setting]	= $this->EE->input->post($setting) !== FALSE ? 
							      $this->EE->input->post($setting) : '';
			
		}
		
		if($this->low_variables)
		{
			$return = array_merge($return, $data);
		}
		
		return $return;
	}
	
	/**
	 * Validates Settings
	 *
	 * @access	private
	 * @param 	array	An array of settings to be validated
	 * @return	boolean
	 */
	 
	private function _validate_settings($settings)
	{
		$match = array();
		
		foreach($settings as $index => $setting)
		{
			foreach($match as $field)
			{
				if($field == $setting && !empty($setting))
				{	
					return FALSE;	
				}
			}
			
			$match[] = $setting;
		}
		
		return TRUE;
	}
	
	private function array_insert(&$array,$element,$position=null)
	{
		if (count($array) == 0)
		{
			$array[] = $element;
		}
		elseif (is_numeric($position) && $position < 0)
		{
			if((count($array)+position) < 0)
			{
		  		$array = array_insert($array,$element,0);
			}
			else
			{
		  		$array[count($array)+$position] = $element;
			}
		}
		elseif (is_numeric($position) && isset($array[$position]))
		{
			$part1 = array_slice($array,0,$position,true);
			$part2 = array_slice($array,$position,null,true);
			$array = array_merge($part1,array($position=>$element),$part2);
			
			foreach($array as $key=>$item)
			{
			  	if (is_null($item))
			  	{
			  	  unset($array[$key]);
			  	}
			}
		}
		elseif (is_null($position))
		{
			$array[] = $element;
		}
		elseif (!isset($array[$position]))
		{
			$array[$position] = $element;
		}
		
		$array = array_merge($array);
		
		return $array;
	}


	private function bool_param($param)
	{
		if($param === TRUE || strtolower($param) == 'true' || strtolower($param) == 'yes')
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function replace_formatted_address($data, $params, $tagdata = FALSE)
	{
		$data = json_decode($data);

		if(!$data)
		{
			return;
		}

		$default_vars = array(
			'limit'   => FALSE,
			'offset'  => 0,
			'process' => 'markers|wapoints|regions'
		);
		
		$params = array_merge($default_vars, ($params ? $params : array()));
		$params['process'] = explode('|', $params['process']);
		
		$formatted_address = array();
		
		foreach($data as $index => $obj)
		{
			if(in_array($index, $params['process']))
			{
				foreach($obj->results as $index => $row)
				{	
					if($params['limit'] === FALSE || ($param['limit'] > $index && $index >= $params['offset']))
					{
						$formatted_address[] = $row->formatted_address;
					}	
				}			
			}
		}
		
		if($format = isset($params['format']) ? $this->bool_param($params['format']) : FALSE)
		{
			foreach($formatted_address as $index => $address)
			{
				$part_1 = preg_replace('/,.*/', '$1', $address);
				$part_2 = trim(ltrim(trim(preg_replace('/(^[^,]*)/', '', $address)), ','));
								
				$formatted_address[$index] = $part_1.'<br>'.$part_2;
			}
		}
		
		return implode("\n", $formatted_address);
	}
		
	public function replace_total_markers($data, $params, $tagdata = FALSE)
	{
		$data = json_decode($data);

		return isset($data->markers->total) ? $data->markers->total : 0;
	}

	public function replace_total_regions($data, $params, $tagdata = FALSE)
	{
		$data = json_decode($data);

		return isset($data->regions->total) ? $data->regions->total : 0;
	}

	public function replace_total_waypoints($data, $params, $tagdata = FALSE)
	{
		$data = json_decode($data);

		return isset($data->waypoint->total) ? $data->waypoint->total : 0;
	}
	
	public function replace_static_map($data, $params, $tagdata = FALSE)
	{
		$this->EE->load->library('static_maps');		
		
		$default_params = array(
			'center'            => NULL,
			'class'             => NULL,
			'height'            => 300,
			'id'                => NULL,
			'language'          => 'en',
			'maptype'           => 'roadmap',
			'format'            => 'JPEG',
			'path'              => NULL,
			'region'            => NULL,
			'scale'             => 1,
			'sensor'            => FALSE,
			'style'             => NULL,
			'visible'           => NULL,
			'width'             => 400,
			'zoom'              => NULL,
			'duplicate_markers' => TRUE,
			'auto_close_region' => TRUE,
		);
		
		$params = array_merge($default_params, $params);
		
		if(isset($params['map_type']))
		{
			$params['maptype'] = $params['map_type'];	
		}	
			
		$params['duplicate_markers'] = $this->bool_param($params['duplicate_markers']);
			
		$data                   = json_decode($data);
		$markers                = array();
		$positions              = array();
		$custom_icons           = 0;
		$available_custom_icons = ((5 * 2) - 1) - $data->markers->total;
		
		foreach($data->markers->results as $marker)
		{
			$index        = count($markers);
			$is_duplicate = FALSE;
			$new_position = $marker->geometry->location->lat.','.$marker->geometry->location->lng;
			
			foreach($positions as $position)
			{
				if($position == $new_position)
				{
					$is_duplicate = TRUE;
				}	
			}
				
			if($params['duplicate_markers'] || !$params['duplicate_markers'] && !$is_duplicate)
			{			
				$markers[$index] = NULL;
				
				if($custom_icons < $available_custom_icons)
				{
					if(isset($params['icon']))
					{
						$markers[$index] .= 'icon:'.$params['icon'].'|';					
						$custom_icons++;
					}
					elseif(isset($marker->icon) && !empty($marker->icon))
					{					
						$markers[$index] .= 'icon:'.$marker->icon.'|';					
						$custom_icons++;
					}
				}
				
				$markers[$index] .= $new_position;
				$positions[]      = $new_position;	
			}			
		}
			
		$paths  = array();
		
		foreach($data->regions->results as $region)
		{		
			$path = NULL;
		
			$path .= 'fillcolor:0x'.str_replace('#', '', $region->style->fillColor.'|');
			$path .= 'color:0x'.str_replace('#', '', $region->style->strokeColor.'|');
			$path .= 'weight:'.str_replace('#', '', $region->style->strokeWeight.'|');
			
			foreach($region->coords as $coord)
			{				
				$path .= $coord->lat.','.$coord->lng.'|';
			}
			
			if($params['auto_close_region'])
			{
				$path .= $region->coords[0]->lat.','.$region->coords[0]->lng;
			}
			
			$paths[] = rtrim($path, '|');
		}
		
		$path = NULL;
		
		if($data->waypoints->total > 0)
		{	
			$start = NULL;
			$end = NULL;
			$waypoints = array();
			
			foreach($data->waypoints->results as $index => $waypoint)
			{
				$coord = $waypoint->geometry->location->lat.','.$waypoint->geometry->location->lng;
				
				if($index == 0)
				{
					$start = $coord;
				}
				else if($index == $data->waypoints->total - 1)
				{
					$end = $coord;
				}
				else
				{
					$waypoints[] = $coord;
				}
			}
			
			$this->EE->load->library('directions');
			
			$response = $this->EE->directions->query($start, $end, array(
				'waypoints' => $waypoints
			));
						
			foreach($response->routes as $route)
			{
				$paths[] = 'enc:'.$route->overview_polyline->points;
			}
		}
				
		$this->EE->static_maps->center 	 = $params['center'];
		$this->EE->static_maps->class 	 = $params['class'];
		$this->EE->static_maps->format	 = strtoupper($params['format']);	
		$this->EE->static_maps->height	 = (int) $params['height'];	
		$this->EE->static_maps->id 	 	 = $params['id'];
		$this->EE->static_maps->language = $params['language'];
		$this->EE->static_maps->maptype	 = $params['maptype'];
		$this->EE->static_maps->markers  = $markers;	
		$this->EE->static_maps->path 	 = $paths;
		$this->EE->static_maps->region	 = $params['region'];
		$this->EE->static_maps->scale	 = $params['scale'];
		$this->EE->static_maps->sensor	 = $params['sensor'];
		$this->EE->static_maps->style	 = $params['style'];
		$this->EE->static_maps->visible	 = $params['visible'];
		$this->EE->static_maps->width	 = (int) $params['width'];	
		$this->EE->static_maps->zoom 	 = $params['zoom'];
		
		return $this->EE->static_maps->render();
	}


	public function display_var_tag($data, $param, $tagdata)
	{
		$this->low_variables = TRUE;
		
		$this->EE->load->add_package_path(PATH_THIRD . 'gmap');
		
		return $this->replace_tag($data, $param, $tagdata);
	}
	
	/**
	 * Replaces the template tag
	 *
	 * @access	public
	 * @param 	array
	 * @return	string
	 */
	
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		$js		= NULL;

		$this->EE->load->library('google_maps');
		$this->EE->load->driver('channel_data');

		if(isset($params['parse']) && $params['parse'] != 'inward')
		{
			$data 	= json_decode($data);
			$limit	= isset($params['limit']) ? (int) $params['limit'] : FALSE;
			$offset	= isset($params['offset']) ? (int) $params['offset'] : 0;

			$params['parse'] = explode('|', $params['parse']);

			$vars = array(
				'total_markers' 	=> isset($data->markers->total) ? $data->markers->total : 0,
				'total_waypoints'	=> isset($data->waypoints->total) ? $data->waypoints->total : 0,
				'total_regions'		=> isset($data->regions->total) ? $data->regions->total : 0
			);

			if(isset($data->markers) && in_array('markers', $params['parse']))
			{
				$markers = $this->EE->google_maps->parse_geocoder_response(array($data->markers), $limit, $offset, '', $this->row['entry_id']);

				$vars['markers'] = $this->EE->channel_data->utility->add_prefix('marker', $markers);
			}
			
			if(isset($data->waypoints) && in_array('waypoints', $params['parse']) || in_array('route', $params['parse']))
			{
				$waypoints = $this->EE->google_maps->parse_geocoder_response(array($data->waypoints), $limit, $offset, '', $this->row['entry_id']);

				$vars['waypoints'] = $this->EE->channel_data->utility->add_prefix('waypoint', $waypoints);

				$vars['route'] = $this->EE->channel_data->utility->add_prefix('waypoint', $waypoints);
			}

			if(isset($data->regions) && in_array('regions', $params['parse']))
			{	
				$regions = $this->EE->google_maps->parse_regions($data->regions);
				$regions = $this->EE->channel_data->utility->add_prefix('region', $regions);

				$vars['regions'] = $regions;
			}

			return $this->parse(array($vars), $tagdata);
		}
		
				
		if(isset($params['redirect']))
		{
			if($this->bool_param($params['redirect']))
			{				
				$params['redirect'] = $tagdata;
				$tagdata            = NULL;
			}			
		}

		// If the id parameter is set, add the marker information to the map
		if(isset($params['id']))
		{
			$data 	= json_decode($data);
		
			$default_params = array(
				'render'			=> 'markers|route|regions',
				'extend_bounds'		=> TRUE,
				'open_windows'		=> FALSE,
				'show_one_window'	=> FALSE,
				'clustering'		=> FALSE,
				'duplicate_markers' => TRUE,
				'window_trigger'    => 'click',
				'redirect'			=> FALSE,
				'retina'			=> FALSE,
				'retinaSize'		=> FALSE,
				'retinaScaledSize'	=> FALSE,
				'size'				=> FALSE,
				'scaledSize'		=> FALSE,
				'redirect'			=> FALSE,
				'category'			=> FALSE
			);
			
			$params                      = array_merge($default_params, $params);
			$params['clustering']        = $this->bool_param($params['clustering']);
			$params['retina']       	 = $this->bool_param($params['retina']);
			$params['duplicate_markers'] = $this->bool_param($params['duplicate_markers']);
			$params['extend_bounds']     = $this->bool_param($params['extend_bounds']);
			$params['open_windows']      = $this->bool_param($params['open_windows']);
			$params['show_one_window']   = $this->bool_param($params['show_one_window']);
			$params['render']            = explode('|', $params['render']);		

			if(isset($params['append']))
			{
				$params['append']			= $this->bool_param($params['append']);

				if($params['append'])
				{
					$params['id'] .= $tagdata;
					$tagdata = FALSE;
				}
			}
			
			$this->EE->load->library('google_maps');
			
			$class			= 'ui-infowindow';
			$close_button 	= 'https://www.google.com/intl/en_us/mapfiles/close.gif';
			$margin			= '';
			
			if($data)
			{
				$info_options =  array(
					'alignBottom'			 => isset($params['alignBottom']) ? $params['alignBottom'] : 'true',
					'boxClass'				 => isset($params['class']) ? $params['class'] : $class,
					'boxStyle'				 => isset($params['style']) ? $params['style'] : '',
					'clearanceX'			 => isset($params['clearanceX']) ? $params['clearanceX'] : '0',
					'clearanceY'			 => isset($params['clearanceY']) ? $params['clearanceY'] : '0',
					'closeBoxMargin'		 => isset($params['closeBoxMargin']) ? $params['closeBoxMargin'] : $margin,
					'closeBoxURL'		 	 => isset($params['closeBoxURL']) ? $params['closeBoxURL'] : $close_button,
					'content'				 => $tagdata,
					'inner_class'		 	 => isset($params['inner_class']) ? $params['inner_class'] : 'ui-infobox-content',
					'disableAutoPan'		 => isset($params['disableAutoPan']) ? $params['disableAutoParam'] : 'false',
					'enableEventPropagation' => isset($params['enableEventPropagation']) ? $params['enableEventPropagation'] : 'false',
					'maxWidth'				 => isset($params['maxWidth']) ? $params['maxWidth'] : '0',
					'offsetX'				 => isset($params['offsetX']) ? $params['offsetX'] : '0',
					'offsetY'				 => isset($params['offsetY']) ? $params['offsetY'] : '0',
					'isHidden'				 => isset($params['isHidden']) ? $params['isHidden'] : 'false',
					'pane'					 => isset($params['pane']) ? $params['pane'] : 'floatPane',
					'zIndex'				 => isset($params['zIndex']) ? $params['zIndex'] : 'null',
					'show_one_window'		 => isset($params['show_one_window']) ? $params['show_one_window'] : FALSE,
					'open_windows'			 => isset($params['open_windows']) ? $params['open_windows'] : FALSE,
					'script_tag' 	  		 => isset($params['script_tag']) ? $params['script_tag'] : FALSE
				);
				
				$tagdata = $tagdata ? $tagdata : '';

				if(isset($data->markers) && $data->markers->total > 0 && in_array('markers', $params['render']))
				{
					$default_marker_options = array(
						'animation', 'clickable', 'cursor', 'draggable', 'flat', 'icon', 'map', 'optimized',
						'raiseOnDrag', 'shadow', 'shape', 'title', 'visible', 'zIndex', 'entry_id'
					);
					
					$markers_options = array();
					
					foreach($default_marker_options as $option)
					{
						if(isset($params[$option]))
						{
							$markers_options[$option] = $params[$option];
						}
					}
					
					$append_data = array();
		
					if(is_array($params))
					{
						foreach($params as $param => $value)
						{
							if(preg_match('/^data:/', $param))
							{
								$param = str_replace('data:', '', $param);
								
								$append_data[$param] = str_replace(array('URL_TITLE', 'ENTRY_ID'), array($this->row['url_title'], $this->row['entry_id']), $value);
							}
						}
					}
					
					$markers 	= array($data->markers);
					$options	= array(
						'id' 			=> $params['id'],
						'entry_id'		=> isset($this->row['entry_id']) ? $this->row['entry_id'] : 0,
						'var_id'		=> $this->low_variables ? $this->var_id : 0,
						'options' 		=> $markers_options, 
						'data'			=> $markers,
						'limit'			=> isset($params['limit']) ? $params['limit'] : FALSE,
						'offset'		=> isset($params['offset']) ? $params['offset']: FALSE,	
						'infobox'		=> isset($params['infobox']) ? $params['infobox'] : FALSE,
						'infowindow'	=> array(
							'content'			=> $tagdata,
							'options'			=> $info_options,
							'open_windows'		=> $params['open_windows'],
							'show_one_window' 	=> $params['show_one_window'] 
						),
						'extend_bounds'     => $params['extend_bounds'], 
						'script_tag'        => FALSE,
						'clustering'        => $params['clustering'],
						'duplicate_markers' => $params['duplicate_markers'],
						'window_trigger'    => $params['window_trigger'],
						'redirect'			=> $params['redirect'],
						'category'			=> $params['category'],
						'retina'			=> $params['retina'],
						'retinaScaledSize'	=> $params['retinaScaledSize'],
						'retinaSize'		=> $params['retinaSize'],
						'size'				=> $params['size'],
						'scaledSize'		=> $params['scaledSize'],
						'append_data'		=> $append_data
					);
					
					$marker		= $this->EE->google_maps->marker($options);			

					$js = $js . $marker;
				}
				
				if(isset($data->waypoints) && $data->waypoints->total > 0 && in_array('route', $params['render']))
				{
					$waypoints 	= array($data->waypoints);
					
					$options	= array(
						'id' 			=> $params['id'], 
						'options' 		=> array(), 
						'data'			=> $waypoints, 
						'extend_bounds'	=> $params['extend_bounds'], 
						'script_tag'	=> FALSE,
						'open_windows'		=> $params['open_windows'],
						'show_one_window' 	=> $params['show_one_window'],
					);
					
					if($tagdata !== FALSE) $options['infowindow'] = $tagdata;
					
					$js			= $js . $this->EE->google_maps->route($options);
				}
				
				if(isset($data->regions) && $data->regions->total > 0 && in_array('regions', $params['render']))
				{
					$regions	= array($data->regions);
						
					$options 	= array(
						'id'			=> $params['id'], 
						'entry_id'		=> isset($this->row['entry_id']) ? $this->row['entry_id'] : 0,
						'options'		=> array(), 
						'data'			=> $regions,
						'infobox'		=> isset($params['infobox']) ? $params['infobox'] : FALSE,
						'infowindow'	=> !isset($params['infowindow']) || isset($params['infowindow']) && $params['infowindow'] != "false" ? array(
							'content'	=> $tagdata,
							'options'	=> $info_options,
							'open_windows'		=> $params['open_windows'],
							'show_one_window' 	=> $params['show_one_window']
						) : FALSE,
						'extend_bounds'	=> $params['extend_bounds'],
						'script_tag'	=> FALSE,
						'redirect' => $params['redirect']
					);
					
					$js	= $js . $this->EE->google_maps->region($options);
				}
			
			}
			
			return '<script type="text/javascript">'.$js.'</script>';
		}
		else
		{
			if(isset($data['content']))
			{
				$data['content'] = str_replace('\'', '\\\'', $data['content']);
			}
			
			return str_replace('"', '\'', $data);
		}
	}
		
	private function parse($vars, $tagdata = FALSE)
	{
		if(!isset($vars[0]))
		{
			$vars = array($vars);
		}
		
		if(isset($this->EE->TMPL))
		{
			if($tagdata === FALSE)
			{
				$tagdata = $this->EE->TMPL->tagdata;
			}
				
			return $this->EE->TMPL->parse_variables($tagdata, $vars);
		}
		else
		{
			foreach($vars[0] as $var => $value)
			{
				$tagdata = str_replace(LD.$var.RD, $value, $tagdata);
			}
			
			return $tagdata;
		}
	}
	
	private function _url($method = 'index', $useAmp = FALSE)
	{
		$amp = !$useAmp ? AMP : '&';
		
		return str_replace(AMP, $amp, BASE . $amp . 'C=addons_modules' .$amp . 'M=show_module_cp' . $amp . 'module=Gmap' . $amp . 'method=' . $method);
	}
	
	private function _current_url($append = '', $value = '')
	{
		$url = (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['SERVER_NAME'] : 'http://'.$_SERVER['SERVER_NAME'];
		
		if(!empty($append))
			$url .= '?'.$append.'='.$value;
		
		return $url;
	}
	    

}
// END CLASS

/* End of file ft.gmap.php */
/* Location: ./system/expressionengine/third_party/google_maps/ft.google_maps.php */