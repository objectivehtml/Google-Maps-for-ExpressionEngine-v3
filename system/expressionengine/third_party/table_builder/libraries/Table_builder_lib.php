<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Table_builder_lib {
	
	public $field_name;
	public $model;

	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->model('table_builder_model');

		$this->model =& $this->EE->table_builder_model;

		$this->EE->load->helper('directory');
	}	

	/*
	public function get_available_celltypes()
	{
		$path  = PATH_THIRD . 'table_builder/celltypes/';
		$files = directory_map($path);

		$celltypes = array();

		foreach($files as $data)
		{
			require_once $path . $data;

			$type 	  	 = ucfirst(str_replace('.php', '', $data));
			$celltypes[] = $this->load_cell_type($type);
		}

		return $celltypes;
	}
	*/

	public function add_prefix($prefix, $data)
	{
		return $this->EE->channel_data->utility->add_prefix($prefix, $data);
	}

	public function build_table($field_name, $field_id, $entry_id)
	{
		$this->EE->load->helper('form');

		$entry = $this->model->get_entry($field_id, $entry_id);

		$style = (int) $entry->row('total_rows')  > 0 ? 'style="display:none"' : null;

		$empty_message = '
		<div class="ui-tb-message ui-tb-empty" ' . $style . '>
			<p>You have not created a table yet. Add your first column to the table to get started.</p>
		</div>';

		if(empty($entry_id) || $entry->num_rows() == 0 || (int) $entry->row('total_rows')  == 0)
		{
			return $empty_message . '
			<table class="ui-tb-table" cellspacing="0" cellpadding="0"></table>';
		}

		$columns 	= json_decode($entry->row('columns'));

		$row_data   = $this->model->get_rows($columns, array(
			'where'    => array(
				'field_id' => $field_id,
				'entry_id' => $entry_id
			),
			'order_by' => 'row_index',
			'sort'     => 'asc'
		));

		$header = array('<th class="ui-tb-id-column"></th>');
		$cells  = array();
		$hidden = array();

		foreach($columns as $column)
		{
			unset($column->html);

			$header[] = '<th class="ui-tb-resizable" style="'.($column->width ? 'width: '.$column->width.'px' : '').'"><div class="ui-tb-relative"><a href="#ui-tb-column-menu" class="ui-tb-column-button">&#x25BE;</a><span class="title">'.$column->title.'</span></div></th>';

			$hidden[] = '<input type="hidden" name="'.$field_name.'[column]['.$column->name.']" value="'.form_prep(json_encode($column)).'"  class="ui-tb-hidden-column ui-tb-hidden-value" />';
		}

		foreach($row_data->result() as $index => $row)
		{	
			$cells[$index][] = '<td class="ui-tb-id-column"><div class="ui-tb-relative"><span class="ui-tb-drag-handle"></span><div class="ui-tb-number">'.($index+1).'</div></div></td>';

			foreach($columns as $column)
			{
				$value = $row->{$column->name};
				$cell  = $this->load_cell_type($column->celltype, $value);

				$cells[$index][] = '<td>'.str_replace('{DEFAULT}', $field_name.'[cell]['.$index.']['.$column->name.']', $cell->html).'</td>';
			}

			$cells[$index] = '<tr>'.implode('', $cells[$index]).'</tr>';

			$hidden[] = '<input type="hidden" name="'.$field_name.'[row]['.$index.']" value="'.form_prep($row->attributes).'"  class="ui-tb-hidden-row ui-tb-hidden-value" />';
		}

		$html = $empty_message . '
		<table class="ui-tb-table" cellspacing="0" cellpadding="0">
			<thead>'.implode("\n", $header).'</thead>
			<tbody>'.implode("\n", $cells).'</tbody>
		</table>
		'.implode("\n", $hidden);

		return $html;
	}

	public function get_installed_celltypes($field_id)
	{
		$celltypes = $this->model->get_celltypes(array(
			'where' => FALSE
		));

		$return = array();

		foreach($celltypes->result() as $index => $celltype)
		{
			$cell           = $this->load_cell_type($celltype);
			$celltype->html = $cell->html;

			$return[$celltype->cell_id] = $celltype;
		}

		return $return;
	}

	public function load_cell_type($celltype, $data = '')
	{
		require_once PATH_THIRD . 'table_builder/celltypes/'.$celltype->type.'.php';

		$field_name  = $celltype->type.'_Table_Builder_Cell';
		
		$celltype             = new $field_name($celltype);
		$celltype->field_name = $this->field_name;
		$celltype->html       = $celltype->display_cell($data);

		return $celltype;
	}

	public function parse($entry_data, $columns = FALSE, $rows = FALSE)
	{
		if(!$columns)
		{
			$columns = $entry_data->columns;
		}

		if(!$rows)
		{
			$rows = $entry_data->rows;
		}

		foreach($rows as $row)
		{
			$row = $this->remove($row);

			foreach($row as $column_name => $value)
			{
				if(isset($columns->$column_name->celltype))
				{
					$column = $this->load_cell_type($columns->$column_name->celltype);
					$row->$column_name = $column->replace_tag($value);
				}
				else
				{
					$row->$column_name = json_decode($value);
				}

				//$row->row_id = $row->attributes->rowId;
			}

			$row_data[] = (array) $row;
		}

		return (object) array(
			'columns' => $columns,
			'rows'    => $rows
		);
	}

	public function parse_columns($columns)
	{
		$return = array();

		foreach($columns as $index => $column)
		{
			$return[] = array(
				'column:title'    => $column->title,
				'column:name'     => $column->name,
				'column:type'     => $column->type,
				'column:celltype' => array(
					array(
						'celltype:cell_id'  => $column->celltype->cell_id,
						'celltype:field_id' => $column->celltype->field_id,
						'celltype:title'    => $column->celltype->title,
						'celltype:name'     => $column->celltype->name,
						'celltype:type'     => $column->celltype->type,
						'celltype:settings' => FALSE,
						'celltype:html'     => $column->celltype->html
					)
				)
			);
		}

		return $return;
	}

	public function parse_rows($rows)
	{
		$return = array();
			
		foreach($rows as $index => $row)
		{
			unset($row->row_id);
			
			$prefix_row = (array) $row;
			$prefix_row = $this->EE->table_builder_lib->add_prefix('row', $prefix_row);


			$row->attributes = $this->EE->table_builder_lib->add_prefix('attribute', array((array) $row->attributes));

			$row->attributes[0]['index']              = $row->attributes[0]['attribute:index'];
			$row->attributes[0]['attribute:class']    = $row->attributes[0]['attribute:cssClass'];
			$row->attributes[0]['class']              = $row->attributes[0]['attribute:cssClass'];
			$row->attributes[0]['attribute:entry_id'] = $row->attributes[0]['attribute:entryId'];
			$row->attributes[0]['entry_id']           = $row->attributes[0]['attribute:entryId'];
			$row->attributes[0]['attribute:row_id']   = $row->attributes[0]['attribute:rowId'];
			$row->attributes[0]['row_id']             = $row->attributes[0]['attribute:rowId'];

			unset($prefix_row['row:attributes']);
			unset($row->attributes[0]['attribute:cssClass']);
			unset($row->attributes[0]['attribute:entryId']);
			unset($row->attributes[0]['attribute:rowId']);

			$row_html = array();

			$cells = array();

			$cell_count = 1;

			foreach($row as $field => $value)
			{
				if($field != 'attributes')
				{
					$cells[] = array(
						'cell:name'     => $field,
						'cell_name'     => $field,
						'cell:value'    => $value,
						'cell_value'    => $value,
						'cell:count'    => $cell_count,
						'cell_count'    => $cell_count,
						'cell:is_first' => $cell_count == 1 ? TRUE : FALSE,
						'cell_is_first' => $cell_count == 1 ? TRUE : FALSE,
						'cell:is_last'  => $cell_count == count($prefix_row) ? TRUE : FALSE,
						'cell_is_last'  => $cell_count == count($prefix_row) ? TRUE : FALSE
					);

					$row_html[] = '<td class="'.$field.'">'.$value.'</td>';
					
					$cell_count++;
				}
			}

			$row->cells = $cells;

			$class = $index % 2 == 1 ? 'odd' : 'even';

			$row = array_merge((array) $row, $prefix_row);
			
			$class .= ' '.$row['attributes'][0]['attribute:class'];
			
			$row['row_id']        = $row['attributes'][0]['attribute:row_id'];
			$row['row:id']        = $row['attributes'][0]['attribute:row_id'];
			$row['odd_even']      = $class;
			$row['row:odd_even']  = $class;
			$row['cell_html']     = implode('', $row_html);
			$row['row:cell_html'] = implode('', $row_html);
			$row['row_html']      = '<tr data-index="'.$index.'" class="'.trim($class).'">'.$row['row:cell_html'].'</tr>';
			$row['row:html']  = '<tr data-index="'.$index.'" class="'.trim($class).'">'.$row['row:cell_html'].'</tr>';
			$row['row_index']     = $index;
			$row['row:index']     = $index;
			$row['count']         = ($index+1);
			$row['row:count']     = ($index+1);
			$row['is_first']      = $index == 0 ? TRUE : FALSE;
			$row['row:is_first']  = $index == 0 ? TRUE : FALSE;
			$row['is_last']       = ($index+1) == count($rows) ? TRUE : FALSE;
			$row['row:is_last']   = ($index+1) == count($rows) ? TRUE : FALSE;

			foreach($row['attributes'][0] as $name => $value)
			{
				$row['row:'.$name] = $value;
			}

			$return[] = $row;
		}

		return $return;
	}

	public function save($columns, $rows, $cells, $delete, &$settings)
	{
		$add  = array();
		$edit = array();
		$column_array = array();

		foreach($cells as $index => $cell)
		{
			$record = array();

			$attributes = json_decode($rows[$index]);
			$is_new     = !isset($attributes->entryId) || $settings['preset'] === TRUE ? TRUE : FALSE;

			foreach($cell as $cell_name => $cell_value)
			{
				foreach($columns as $column_name => $column_data)
				{
					if($cell_name == $column_name)
					{
						$column_data         = json_decode($column_data);
						$celltype 			 = $this->load_cell_type($column_data->celltype);
						$col_id              = $this->update_column($column_data);
						$column_data->col_id = $col_id;

						$record['col_id_'.$col_id]  = $celltype->save($cell_value);

						$column_array[$column_name] = $column_data;

						$entry_data[$index]['columns'][$column_name] = $column_data;
					}
				}
			}

			$attributes->entryId         = $settings['entry_id'];

			$record['field_id']			 = $settings['field_id'];
			$record['row_index']         = $attributes->index;
			$record['attributes']        = json_encode($attributes);

			$record = array_merge($settings, $record);

			unset($record['preset']);

			if($is_new)
			{
				$add[] = $record;
			}
			else
			{
				$edit[] = $record;
			}
		}

		if($settings['preset'])
		{
			$this->model->delete_entry_rows($settings['field_id'], $settings['entry_id']);
		}

		if(count($add) > 0)
		{
			$entry_ids = $this->model->add_rows($add);
		}
		
		if(count($edit) > 0)
		{
			$this->model->edit_rows($edit);
		}

		$this->model->delete_rows($delete);

		$this->model->update_entry($column_array, $cells, $settings['field_id'], $settings['entry_id']);
	}

	public function validate($data)
	{
		var_dump($data);exit();
	}

	public function does_column_exist($data)
	{
		$column = $this->model->get_columns(array(
			'where' => array(
				'name'  => $data->name,
				'type'  => $data->type
			)
		));

		return $column->num_rows() == 0 ? FALSE : TRUE;
	}

	public function update_column($data)
	{
		if(!$this->does_column_exist($data))
		{	
			$this->model->add_column($data);
		}

		return $this->model->get_column_id(array(
			'name'  => $data->name,
			'type'  => $data->type
		));
	}

	public function remove($data, $terms = false)
	{
		if(!$terms)
		{
			$terms = array('row_id', 'row_index', 'channel_id', 'author_id', 'entry_id');
		}

		foreach($terms as $term)
		{
			unset($data->$term);
		}

		return $data;
	}
}