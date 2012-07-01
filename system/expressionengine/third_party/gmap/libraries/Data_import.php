<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'gmap/libraries/Google_maps.php';
require_once PATH_THIRD . 'gmap/libraries/DataSource.php';

class Data_import extends Google_maps {
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function run()
	{
		echo 'run';exit();
	}
	
	
	public function load_file($file, $columns = FALSE)
	{
		$csv = new File_CSV_DataSource;

		$csv->settings['eol'] = "\n\r";

		if (!$csv->load($file))
		{
			die('can not load csv file');
		}

		if (!$csv->isSymmetric())
		{
			$csv->symmetrize();
		}	
		
		$entries = $csv->connect();

		return $entries;
	}
	
	/*------------------------------------------
	 *	Settings
	/* -------------------------------------- */
	
	
	public function get_setting($id)
	{
		$setting = $this->get_settings($id);
		
		if($setting->num_rows() == 0)
		{
			show_error($id . ' is not a valid setting id');
		}
		
		return json_decode($setting->row('settings'));
	}
	
	public function get_settings($id = FALSE)
	{
		if($id)
		{
			$this->EE->db->where('id', $id);
		}
		
		return $this->EE->db->get('gmap_import_settings');
	}
	
	public function save_settings($id, $settings)
	{
		$existing = $this->EE->db->where('id', $id)->get('gmap_import_settings')->num_rows() > 0 ? TRUE : FALSE;
		
		if(!$existing)
		{
			$this->EE->db->insert('gmap_import_settings', array(
				'id'       => $id, 
				'settings' => $settings
			));
		}
		else
		{
			$this->EE->db->update('gmap_import_settings', array(
				'id'       => $id, 
				'settings' => $settings
			));	
		}
	}
	
}