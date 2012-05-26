<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'third_party/auto_import/libraries/DataSource.php';

class Auto_import_lib {

	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->model('auto_import_model');
		$this->EE->load->driver('channel_data');
		$this->EE->load->driver('interface_builder');

		$this->settings = $this->EE->auto_import_model->settings;
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
}