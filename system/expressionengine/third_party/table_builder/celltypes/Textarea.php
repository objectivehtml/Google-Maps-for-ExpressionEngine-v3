<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'table_builder/libraries/Table_Builder_Cell.php';

class Textarea_Table_Builder_Cell extends Table_Builder_Cell {

	public $info = array(
		'name'    => 'Textarea',
		'version' => '1.0'
	);

	public function __construct($celltype)
	{
		parent::__construct();

		$this->cell_name = $celltype->name;
	}

	public function display_cell($data = '')
	{
		return '<textarea name="{DEFAULT}" class="ui-tb-textarea" role="cell">'.$data.'</textarea>';
	}

}