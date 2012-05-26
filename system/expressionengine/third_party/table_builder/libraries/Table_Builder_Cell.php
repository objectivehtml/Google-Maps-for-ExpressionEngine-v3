<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

abstract class Table_Builder_Cell {

	public function __construct()
	{
		$this->EE =& get_instance();
	}

	public $info;
	public $cell_name;
	public $settings;
	public $default_settings = array();

	abstract public function display_cell($data = '');

	public function replace_tag($data = '') {
		return $data;
	}

	public function display_settings() {
		return '';
	}

	public function validate_settings($data) {
		return TRUE;
	}
	
	public function save_settings($data) {
		return $data;
	}

	public function validate($data) {
		return TRUE;
	}

	public function save($data) {
		return $data;
	}
	
	public function post_save($data) {
		return $data;
	}
}