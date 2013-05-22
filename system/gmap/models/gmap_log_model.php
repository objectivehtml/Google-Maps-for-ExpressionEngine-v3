<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Log Model
 *
 * Model for cacheing the data retrieved from Google Services
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Models
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/libraries/channel_data
 * @version		1.0
 * @build		20120103
 */

class Gmap_log_model extends CI_Model {
	
	public $cache_length = 0;
	
	public function __construct()
	{
		$this->load->config('gmap_config');
		$this->load->driver('channel_data');
		
		$this->cache_length = config_item('gmap_cache_length');
	}
	
	public function check_image($query, $expires = TRUE)
	{
		if($expires) $this->db->where('expires >=', $this->localize->now);
		
		$this->db->where('query', $query);
		$this->db->order_by('expires', 'desc');
		
		$results = $this->db->get('gmap_static_maps');
		
		return $results;	
	}
	
	public function get_expiration()
	{		
		$cache_length 	= $this->cache_length;
		$expires 		= $cache_length > 0 ? $this->localize->now + $cache_length : 0;
		
		return $expires;	
	}
	
	public function cache_image($query, $filename)
	{
		$expires = $this->get_expiration();		
		$where	 = array('expires <=' => $this->localize->now);
				
		$entries = $this->channel_data->get('gmap_static_maps', array(
			'where' => $where
		));
		
		foreach($entries->result() as $row)
		{
			$file = config_item('gmap_static_map_path') . $row->filename;
			
			if(is_file($file))
			{
				unlink($file);
			}
		}
		
		$this->db->delete('gmap_static_maps', $where);
		
		$this->db->insert('gmap_static_maps', array(
			'query' 	=> $query,
			'filename' 	=> $filename,
			'date'		=> $this->localize->now,
			'expires'	=> $this->localize->now + $expires
		));
	}
	
	public function check_response($query, $type = FALSE, $expires = TRUE)
	{
		if($type) 		$this->db->where('type', $type);
		if($expires)	$this->db->where('expires >=', $this->localize->now);
		
		$this->db->where('query', $query);
		$this->db->order_by('expires', 'desc');
		
		$results = $this->db->get('gmap_cache');
		
		return $results;
	}
	
	public function cache_response($query, $response, $type)
	{
		if(!is_string($response)) $response = json_encode($response);
		
		$expires 		= $this->get_expiration();
		
		$this->db->delete('gmap_cache', array(
			'expires <=' => $this->localize->now
		));
		
		$this->db->insert('gmap_cache', array(
			'query' 	=> $query,
			'response' 	=> $response,
			'type'		=> $type,
			'date'		=> $this->localize->now,
			'expires'	=> $expires
		));
	}
	
}
