<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'gmap/libraries/Google_maps.php';

class Data_import extends Google_maps {
	
	public function __construct()
	{
		parent::__construct();
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