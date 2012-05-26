<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Table Builder
 * 
 * @package		Table Builder
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com
 * @version		0.2.2
 * @build		20120511
 */

class Table_builder_api extends Base_API {
	
	private $lib;

	public function __construct()
	{
		parent::__construct();

		$this->EE->load->library('Table_builder_lib');

		$this->lib = $this->EE->table_builder_lib;
	}

	public function get_row($field_id, $entry_id, $row_id)
	{
		$entry_data = $this->lib->model->get_entry_data($field_id, $entry_id, $params = array());
		$rows		= $this->lib->model->get_rows($entry_data->columns, array('where' => array('row_id' => $row_id)))->result();

		return $this->lib->parse($entry_data, FALSE, $rows);
	}

	public function get_rows($field_id, $entry_id, $params = array())
	{
		$entry_data = $this->lib->model->get_entry_data($field_id, $entry_id, $params = array());
		$rows		= $this->lib->model->get_rows($entry_data->columns, $params)->result();

		return $this->lib->parse($entry_data, FALSE, $rows);
	}

	public function parse_columns($columns)
	{
		return $this->lib->parse_columns($columns);
	}

	public function parse_rows($rows)
	{
		return $this->lib->parse_rows($rows);
	}

	public function usage()
	{

	}
}