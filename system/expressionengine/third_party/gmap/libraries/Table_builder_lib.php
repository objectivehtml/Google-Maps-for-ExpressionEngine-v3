<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Table_builder_lib {
	
	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->model('tabler_builder_model');
	}	

	public function get_celltypes()
	{
		$this->EE->load->helper('directory');

		$files = directory_map(PATH_THIRD . 'table_builder/celltypes/');

		foreach($files as $file => $data)
		{
			var_dump($file);exit();
		}
	}
}