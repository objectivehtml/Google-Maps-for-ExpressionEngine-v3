<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Gmap_controller {
	
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->driver('channel_data');
		$this->EE->channel_data->api->load('gmap');
	}

	function route()
	{
		$public_methods = array('geocode', 'directions');

		$method = $this->param('method');
		$method = !empty($method) ? $method : 'geocode';

		if(!in_array($method, $public_methods))
		{
			return $this->EE->output->show_user_error('general', '"'.$method.'" is not a valid method');
		}

		return $this->$method();
	}

	public function geocode()	
	{
		$query = $this->EE->input->get('address');

		if(!$query)
		{
			$response = array('error' => 'You must pass an \'address\' to geocode.');
		}
		else
		{
			$limit	  = $this->EE->input->get('limit');
			$offset	  = $this->EE->input->get('offset');

			$this->log_query($query, $limit, $offset);

			$response = $this->EE->channel_data->gmap->geocode($query, $limit, $offset);
		}

		header('Content-type: application/json');
		echo json_encode($response);
		exit();
	}

	private function log_query($query, $limit, $offset)
	{
		if($limit !== FALSE)
		{
			$query .= '&limit='.$limit;
		}

		if($offset !== FALSE)
		{
			$query .= '&offset='.$offset;
		}

		$this->EE->db->insert('gmap_api_logs', array(
			'query'      => $query,
			'date'       => $this->EE->localize->now,
			'ip_address' => $this->EE->input->ip_address()
		));
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
		
		return $param;			
	}
}