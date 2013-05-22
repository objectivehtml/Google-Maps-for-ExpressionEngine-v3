<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kml_model extends CI_Model {
	
	public function __construct()
	{
		parent::__construct();
		
		if(!isset($this->channel_data))
		{
			require_once PATH_THIRD . 'gmap/libraries/Channel_data/Channel_data.php';
			
			$this->channel_data = new Channel_data();
		}
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
	
	public function install()
	{
		require_once PATH_THIRD . 'gmap/libraries/DataSource.php';
		
		$csv  = new File_CSV_DataSource(PATH_THIRD . 'gmap/data/WorldBorders.csv');
		
		$this->db->insert_batch('gmap_world_borders', $csv->connect());
	}	
	
	public function update()
	{
		$row = $this->db->get('gmap_world_borders');
		
		if($row->num_rows() == 0)
		{
			$this->install();
		}
	}
}