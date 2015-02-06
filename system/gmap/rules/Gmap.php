<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Gmap_channel_search_rule extends Base_rule {
	
	protected $title = 'Google Maps for ExpressionEngine';
	
	protected $description = 'The Google Maps for ExpressionEngine search modifier allows you to map any form field(s) in a search to be geocoded, which will be used to search the database for latitude and longitude coordinates.';
	
	protected $name = 'gmap';
	
	protected $fields = array(						
		'location_fields' => array(
			'label' => 'Location Fields',
			'description' => 'Use this field to add the names of the form fields used to pass the location to the geocoder. If you have multiple form fields, add all fields you wish that contain address components. The fields will be appended to a string in the order they are entered.',
			'id'    => 'search_fields',
			'type'	=> 'matrix',
			'settings' => array(
				'columns' => array(
					0 => array(
						'name'  => 'field_name',
						'title' => 'Form Field Name'
					),
				),
				'attributes' => array(
					'class'       => 'mainTable padTable',
					'border'      => 0,
					'cellpadding' => 0,
					'cellspacing' => 0
				)
			)
		),
		'latitude_field' => array(
			'label'       => 'Latitude Field',
			'description' => 'Enter the name of the channel field that stores the latitude',
			'id'          => 'latitude_field',
		),
		'longitude_field' => array(
			'label'       => 'Longitude Field',
			'description' => 'Enter the name of the channel field that stores the longitude',
			'id'          => 'longitude_field',
		),
		'distance_field' => array(
			'label'       => 'Distance Field',
			'description' => 'Enter the name of the distance field in your form. If no form field is preset, all distances will be returned.',
			'id'          => 'longitude_field',
		),
		'search_trigger' => array(
			'label'       => 'Search Trigger',
			'description' => 'Since Google Maps for EE needs sends a request to Google to geocode your response before your results are turned, you can define a GET variable to act as a trigger. If this variable doesn\'t exist, then the location search will not be used. If this field is empty, no specific trigger is used.',
			'id'          => 'search_trigger',
		),
		'zipcode_field' => array(
			'label'       => 'Zipcode Channel Field',
			'description' => 'Optionally enter a zipcode field that is used for a literal match. Sometimes users want to search by zipcode but some locations are outside the distance being searched but the user still wants to see all matching locations with the same zipcode. Enter the zipcode field here to ensure all locations with the same zipcode are returned.',
			'id'          => 'zipcode_field',
		),		
		'zipcode_form_field' => array(
			'label'       => 'Zipcode Form Field',
			'description' => 'If you entered a zipcode channel field, enter the corresponding form field name here.',
			'id'          => 'zipcode_form_field',
		),	
	);
	
	public function __construct($properties = array())
	{
		parent::__construct($properties);
	}
	
	public function get_select()
	{
		$EE =& get_instance();
		
		$select = array();
			
		if(is_object($this->settings->rules))
		{
			$rules = $this->settings->rules;
			
			$geocode = array();
			
			if(!$this->_trigger())
			{
				return $this->_no_distance();;
			}
			
			foreach($rules->location_fields as $field)
			{
				$value = $EE->input->get_post($field->field_name);
				
				if($value)
				{
					$geocode[] = $value;
				}
				else
				{
					$geocode[] = $field->field_name;
				}
			}
			
			$EE->channel_data->api->load('gmap');
			
			$response = $EE->channel_data->gmap->geocode(implode(' ', $geocode));
			
			if($response[0]->status == 'OK')
			{
				$loc = $response[0]->results[0]->geometry->location;				
				$lat = $loc->lat;
				$lng = $loc->lng;
				
				if(isset($this->fields[$rules->latitude_field]))
				{
					$lat_field = 'field_id_'.$this->fields[$rules->latitude_field]->field_id;
				}
				
				if(isset($this->fields[$rules->latitude_field]))
				{
					$lng_field = 'field_id_'.$this->fields[$rules->longitude_field]->field_id;
				}
				
				if(isset($lat_field) && isset($lng_field))
				{
					$select[] = 'ROUND((((ACOS(SIN('.$lat.' * PI() / 180) * SIN('.$lat_field.' * PI() / 180) + COS('.$lat.' * PI() / 180) * COS('.$lat_field.' * PI() / 180) * COS(('.$lng.' - '.$lng_field.') * PI() / 180)) * 180 / PI()) * 60 * 1.1515) * 1), 1) AS distance';
				}
				else
				{
					$select = $this->_no_distance();
				}
			}
			
			if(empty($select))
			{
				$select = $this->_no_distance();
			}
			
			$this->select = $select;
		}
		
		return $select;
	}
	
	public function get_having()
	{
		$EE =& get_instance();
		
		$having = array();
		
		if(is_object($this->settings->rules))
		{
			if(!$this->_trigger())
			{
				return $having;
			}
			
			$distance_field = $this->settings->rules->distance_field;
			$value = $EE->input->get_post($distance_field);
			
			if($value)
			{
				$having[] = 'distance <= '.$EE->db->escape($value);
			}
			else
			{
				$having[] = 'distance >= 0';
			}
		}

		if(isset($this->settings->rules->zipcode_field) && !empty($this->settings->rules->zipcode_field))
		{
			if(isset($this->fields[$this->settings->rules->zipcode_field]))
			{
				$having[] = 'field_id_' . $this->fields[$this->settings->rules->zipcode_field]->field_id . ' LIKE \'%' . $EE->input->get_post($this->settings->rules->zipcode_form_field) .'%\'';
			}
		}
		
		return implode(' or ', $having);
	}
	
	public function get_vars_row($row)
	{
		return array('distance' => isset($row['distance']) ? $row['distance'] : 'N/A');
	}

	private function _no_distance()
	{
		return array('\'N/A\' as \'distance\'');
	}
	
	private function _trigger()
	{		
		$EE 	 =& get_instance();			
		$trigger = TRUE;
		$rules   = $this->settings->rules;
		
		if(!empty($rules->search_trigger))
		{
			if(!$EE->input->get_post($rules->search_trigger))
			{
				$trigger = FALSE;
			}	
		}
		
		return $trigger;		
	}	
}