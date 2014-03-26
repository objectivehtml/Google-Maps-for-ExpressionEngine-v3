<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Static Map Base Class
 * 
 * @package		Google Maps for ExpressionEngine
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.0.1
 * @build		20120220
 */

require_once PATH_THIRD.'gmap/libraries/Google_API.php';

class Static_maps extends Google_API {
	
	public $center;
	public $class;
	public $height;
	public $id;
	public $maptype;
	public $markers;
	public $path;
	public $region;
	public $scale;
	public $style;
	public $width;
	public $zoom;
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->config('gmap_config');
		$this->EE->load->model('gmap_log_model');
	
		$this->reset();
	}
	
	public function image($file, $url = FALSE)
	{
		$map_url = config_item('gmap_static_map_url');
		
		if($url === FALSE && $map_url)
		{
			$url = $map_url;
		}
		
		$return = '<img src="'.$url.$file.'" alt="'.($this->center ? $this->center : 'Google Map').'" class="'.$this->class.'" id="'.$this->id.'" />';		
		
		$this->reset();
		
		return $return;
	}
	
	public function rawdata()
	{
		$url  = $this->url();
		
		$this->create($url);
		
	    $this->option('header', 0);
		$this->option('returntransfer', 1);
		$this->option('binarytransfer', 1);
		
		$data = $this->connect($url);
		
		$this->reset();
		
		return $data;
	}
	
	public function render()
	{	
		$base_path = config_item('gmap_static_map_path');
		$url  	   = $this->url();
		$cache 	   = $this->EE->gmap_log_model->check_image($url);
		
		if($base_path)
		{
			$file = config_item('gmap_static_map_path');

			if($cache->num_rows() == 0 || !file_exists($file))
			{		
				if(is_writable($base_path))
				{			
					if($cache->num_rows() == 0)
					{
						$filename = md5($url.$this->EE->localize->now) . '.' . strtolower($this->format);
					}
					else
					{
						$filename = $cache->row('filename');
					}

					$file = $base_path . $filename;

					$this->save($file, $this->rawdata());
					$this->EE->gmap_log_model->cache_image($url, $filename);

					return $this->image($filename);
				}
				else
				{
					$this->EE->output->show_user_error('general', array(
						'The following path is not a writable directory: '.$base_path
					));
				}		
			}
			else
			{				
				return $this->image($cache->row('filename'));
			}
		}
		else
		{
			return $this->image($this->url());
		}
	}
	
	public function save($fullpath, $rawdata){
	    
	    if(file_exists($fullpath))
	    {
	        unlink($fullpath);
	    }
	    
	    $fp = fopen($fullpath,'x');
	    fwrite($fp, $rawdata);
	    fclose($fp);
	}

	public function url()
	{
		$components = array();
		$exclude    = array('base_url', 'last_response', 'last_query', 'markers', 'style', 'height', 'width', 'last_response', 'last_query', 'responses', 'secure', 'url', 'info', 'id', 'class');
		
		foreach(get_object_vars($this) as $param => $value)
		{
			if($value !== NULL && !in_array($param, $exclude))
			{
				if(is_array($value))
				{
					$value = implode('|', $value);
				}
			
				if(is_bool($value))
				{
					$value = ($value === TRUE) ? 'true' : 'false';
				}
			
				if(!empty($value))
				{
					$param = $this->param($param, $value);
					
					if($param != NULL)
					{
						$components[] = $param;
					}
				}
			}
		}
		
		foreach(array('markers', 'style') as $var)
		{		
			if(count($this->$var) > 0)
			{
				foreach($this->$var as $data)
				{
					if($data != NULL)
					{
						$components[] = $this->param($var, trim($data));
					}
				}
			}
		}
		
		$components[] = $this->param('size', $this->width.'x'.$this->height);
				
		if(is_array($this->path))
		{
			foreach($this->path as $path)
			{
				$components[] = 'path='.$path;
			}
		}

		$url = str_replace('%3A', ':', $this->base_url . '?' . implode('&', $components));		
		$url = str_replace('%2C', ',', $url);
		
		return $url;
	}
	
	public function reset()
	{
		$this->base_url  = 'https://maps.googleapis.com/maps/api/staticmap';
		$this->sensor    = FALSE;
		$this->format    = 'JPEG';
		$this->center 	 = NULL;
		$this->height 	 = 300;
		$this->maptype	 = 'roadmap';
		$this->markers	 = array();
		$this->path		 = NULL;
		$this->region	 = NULL;
		$this->scale	 = 1;
		$this->style	 = array();
		$this->width	 = 400;
		$this->zoom		 = 15;
	}
	
	public function param($param, $value)
	{	
		$return = NULL;
		
		if(!is_object($value))
		{
			$return = $param . '=' . urlencode($value);
		}
		
		return $return;
	}
}