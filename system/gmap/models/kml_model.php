<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kml_model extends CI_Model {
	
	public function __construct()
	{
		parent::__construct();
		
		$this->load->driver('Channel_data');
	}
	
	public function get_country_code($county_code)
	{
		return $this->get_world_borders(array(
			'where' => array(
				'country_code' => $county_code
			)
		));
	}
	
	public function get_world_borders($select = array('*'), $where = array(), $order_by = FALSE, $sort = 'asc', $limit = FALSE, $offset = 0)
	{
		if($this->channel_data->is_polymorphic($select))
		{
			$default_select = array('*');
		
			$select = array_merge(
				array('select' => $default_select),
				$select
			);
		}
		
		return $this->channel_data->get('gmap_world_borders', $select, $where, $order_by, $sort, $limit, $offset);
	}		
}