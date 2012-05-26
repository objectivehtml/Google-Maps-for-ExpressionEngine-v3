<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Table_builder_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();

		$this->load->driver('channel_data');
		$this->load->library('Data_forge');
	}

	/*------------------------------------------
	 *	Standard Methods
	/* -------------------------------------- */

	public function get_celltypes($params)
	{
		return $this->channel_data->get('table_builder_celltypes', $params);
	}

	public function get_columns($params)
	{
		return $this->channel_data->get('table_builder_columns', $params);
	}

	public function get_column($col_id)
	{
		return $this->get_columns(array(
			'col_id' => $col_id
		));
	}

	public function get_column_id($data)
	{
		return $this->get_columns(array(
			'where' => $data
		))->row('col_id');
	}

	public function get_entry($field_id, $entry_id)
	{
		return $this->get_entries(array(
			'where' => array(
				'field_id' => $field_id,
				'entry_id' => $entry_id
			)
		));
	}

	public function get_entries($params)
	{
		return $this->channel_data->get('table_builder_entries', $params);
	}

	public function get_entry_data($field_id, $entry_id, $params = array())
	{
		
		$entry   = $this->get_entry($field_id, $entry_id);

		if($entry->num_rows == 0)
		{
			return (object) array(
				'columns' => array(),
				'rows' => array()
			);
		}
		
		$columns = json_decode($entry->row('columns'));

		$params['where']['entry_id'] = $entry_id;
		$params['where']['field_id'] = $field_id;

		return (object) array(
			'columns' => $columns,
			'rows'    => $this->get_rows($columns, $params)->result()
		);
	}

	public function get_rows($columns, $params)
	{
		$params['select'][] = 'row_id, row_index, channel_id, author_id, entry_id, attributes';

		foreach($columns as $column)
		{
			$params['select'][] = 'col_id_'.$column->col_id.' as `'.$column->name.'`';
		}

		return $this->channel_data->get('table_builder_rows', $params);
	}

	public function get_row($row_id) {
		
		$params['where'] = array('row_id' => $row_id);

		return $this->get_rows(array(), $params);
	}

	public function get_preset($id)
	{
		return $this->channel_data->get('table_builder_presets', array(
			'where' => array(
				'id' => $id
			)
		));
	}

	public function get_presets($params = FALSE)
	{
		return $this->channel_data->get('table_builder_presets', $params);
	}

	public function add_column($data)
	{
		$data = array(
			'name'  => $data->name,
			'type'  => $data->type
		);

		$this->insert('table_builder_columns', $data);

		$col_id = $this->insert_id();

		$this->data_forge->add_column('table_builder_rows', array(
			'col_id_'.$col_id => array(
				'type' => 'text'
			)
		));
	}

	public function add_celltype($data)
	{
		$this->insert('table_builder_celltypes', $data);
	}

	public function add_row($data)
	{
		$this->insert('table_builder_rows', $data);
	}

	public function add_rows($data)
	{
		$entry_ids = array();

		foreach($data as $row)
		{
			$this->add_row($row);

			$this->edit_attributes($this->insert_id());
		}

		return $entry_ids;
	}

	public function add_preset($data)
	{
		$this->insert('table_builder_presets', $data);
	}

	public function edit_attributes($row_id)
	{
		$row = json_decode($this->get_row($row_id)->row('attributes'));
		$row->rowId = $row_id;
	
		$this->edit_row($row_id, array('attributes' => json_encode($row)));
	}

	public function edit_column($col_id, $data)
	{
		$this->where('col_id', $col_id);
		$this->edit('table_builder_columns', $data);
	}

	public function edit_celltype($id, $data)
	{
		$this->where('id', 'id');
		$this->edit('table_builder_celltypes', $data);
	}

	public function edit_row($row_id, $data)
	{
		$this->where('row_id', $row_id);
		$this->edit('table_builder_rows', $data);
	}

	public function edit_rows($data)
	{
		$entry_ids = array();

		foreach($data as $row)
		{
			$attributes = json_decode($row['attributes']);

			$this->edit_row($attributes->rowId, $row);
			$entry_ids[] = $attributes->rowId;
		}

		return $entry_ids;
	}

	public function edit_preset($id, $data)
	{
		$this->where('preset_id', $id);
		$this->edit('table_builder_preset', $data);
	}

	public function delete_column($col_id, $data)
	{
		$this->where('col_id', $col_id);
		$this->delete('table_builder_columns', $data);
	}

	public function delete_celltype($id, $data)
	{
		$this->where('id', $id);
		$this->delete('table_builder_celltypes', $data);
	}

	public function delete_row($row_id)
	{
		$this->where('row_id', $row_id);
		$this->delete('table_builder_rows');
	}

	public function delete_rows($data)
	{
		foreach($data as $row_id)
		{
			$this->delete_row($row_id);
		}
	}

	public function delete_entry_rows($field_id, $entry_id)
	{
		$this->where('field_id', $field_id);
		$this->where('entry_id', $entry_id);
		$this->delete('table_builder_rows');
	}

	public function delete_preset($id)
	{
		$this->where('id', $id);
		$this->delete('table_builder_presets');
	}

	public function update_entry($columns, $rows, $field_id, $entry_id)
	{
		$table = 'table_builder_entries';

		$data = array(
			'field_id'		=> $field_id,
			'entry_id'      => $entry_id,
			'columns'       => json_encode($columns),
			'rows'          => json_encode($rows),
			'total_columns' => count($columns),
			'total_rows'    => count($rows)
		);

		if($this->exists(array(
			'field_id' => $field_id, 
			'entry_id' => $entry_id), 'entry_id', $table))
		{
			$this->where('field_id', $field_id);
			$this->where('entry_id', $entry_id);
			$this->edit($table, $data);
		}
		else
		{
			$this->insert($table, $data);
		}
	}

	private function exists($where, $field, $table)
	{
		$this->db->where($where);
		
		$entries = $this->get($table);

		if($entries->num_rows() > 0)
		{
			$valid = true;
		}
		else
		{
			$valid = false;
		}

		return $valid;
	}

	private function get($table)
	{
		return $this->db->get($table);
	}
	
	private function where($field, $value)
	{
		$this->db->where($field, $value);
	}

	private function insert($table, $data)
	{
		$this->db->insert($table, $data);
	}

	private function edit($table, $data)
	{
		$this->db->update($table, $data);
	}

	private function delete($table)
	{
		$this->db->delete($table);
	}

	public function insert_id()
	{
		return $this->db->insert_id();
	}

}