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

require_once 'config/table_builder_config.php';

if(!defined('TABLE_BUILDER_VERSION'))
{	
	define('TABLE_BUILDER_VERSION', $config['table_builder_version']);
}

class Table_builder_mcp {
	
	public $themes;

	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->library('table_builder_lib');
		$this->EE->load->model('table_builder_model');
	}

	public function delete_preset()
	{
		$id = $this->EE->input->post('id');

		$this->EE->table_builder_model->delete_preset($id);
	}

	public function load_preset()
	{
		$id = $this->EE->input->get('id');

		$preset = $this->EE->table_builder_model->get_preset($id)->row_array();
		
		$preset['columns'] = json_decode($preset['columns']);
		$preset['rows'] = json_decode($preset['rows']);
		$preset['cells'] = json_decode($preset['cells']);
		$preset['celltypes'] = json_decode($preset['celltypes']);

		header('Content-type: application/json');
		exit(json_encode($preset));
	}

	public function save_preset()
	{
		$this->EE->load->helper('form');

		$celltypes  = $_POST['celltypes'];
		$columns    = $_POST['data']['column'];
		$rows       = $_POST['data']['row'];
		$cells      = $_POST['data']['cell'];
		$field_name = $_POST['field_name'];

		$empty_message = '
		<div class="ui-tb-message ui-tb-empty" style="display:none">
			<p>You have not created a table yet. Add your first column to the table to get started.</p>
		</div>';
		
		$table_header = array('<th class="ui-tb-id-column"></th>');
		$table_cells  = array();

		foreach($columns as $column)
		{
			$column = json_decode($column);

			unset($column->html);

			$table_header[] = '<th class="ui-tb-resizable" style="'.($column->width ? 'width: '.$column->width.'px' : '').'"><div class="ui-tb-relative"><a href="#ui-tb-column-menu" class="ui-tb-column-button">&#x25BE;</a><span class="title">'.$column->title.'</span></div></th>';

			$hidden[] = '<input type="hidden" name="'.$field_name.'[column]['.$column->name.']" value="'.form_prep(json_encode($column)).'"  class="ui-tb-hidden-column ui-tb-hidden-value" />';
		}
		
		foreach($rows as $index => $row)
		{	
			$table_cells[$index][] = '<td class="ui-tb-id-column"><div class="ui-tb-relative"><span class="ui-tb-drag-handle"></span><div class="ui-tb-number">'.($index+1).'</div></div></td>';

			foreach($columns as $column)
			{
				$column = json_decode($column);

				$value = $cells[$index][$column->name];
				$cell  = $this->EE->table_builder_lib->load_cell_type($column->celltype, $value);

				$table_cells[$index][] = '<td>'.str_replace('{DEFAULT}', $field_name.'[cell]['.$index.']['.$column->name.']', $cell->html).'</td>';
			}

			$table_cells[$index] = '<tr>'.implode('', $table_cells[$index]).'</tr>';

			$hidden[] = '<input type="hidden" name="'.$field_name.'[row]['.$index.']" value="'.form_prep($row).'"  class="ui-tb-hidden-row ui-tb-hidden-value" />';
		}

		$html = $empty_message . '
		<table class="ui-tb-table" cellspacing="0" cellpadding="0">
			<thead>'.implode("\n", $table_header).'</thead>
			<tbody>'.implode("\n", $table_cells).'</tbody>
		</table>
		'.implode("\n", $hidden);

		foreach($columns as $index => $column)
		{
			$columns[$index] = json_decode($column);
		}

		foreach($rows as $index => $row)
		{
			$rows[$index] = json_decode($row);
		}

		$data = array(
			'site_id'		=> $this->EE->config->item('site_id'),
			'author_id' 	=> $this->EE->session->userdata('member_id'),
			'entry_id'      => $this->EE->input->post('entry_id'),
			'field_id'      => $this->EE->input->post('field_id'),
			'celltypes'		=> $celltypes,
			'columns'       => json_encode($columns),
			'rows'          => json_encode($rows),
			'cells'         => json_encode($cells),
			'html'			=> trim($html),
			'name' 			=> $this->EE->input->post('name'),
			'total_columns' => count($columns),
			'total_rows'	=> count($rows)
		);

		$this->EE->table_builder_model->add_preset($data);

		$data['preset_id'] = $this->EE->table_builder_model->insert_id();

		$this->EE->output->send_ajax_response($data);
	}

	private function post($name)
	{
		$return = $this->EE->input->post($name);
		$return = $return !== FALSE ? $return : '';

		return $return;
	}

	private function get($name, $require = FALSE)
	{
		$return = $this->EE->input->get_post($name);

		if($require && !$return)
		{
			show_error('The <b>'.$name.'</b> parameter is required');
		}

		return $return;
	}

	private function cp_url($method = 'index', $useAmp = FALSE)
	{
		$amp  = !$useAmp ? AMP : '&';

		$file = substr(BASE, 0, strpos(BASE, '?'));
		$file = str_replace($file, '', $_SERVER['PHP_SELF']) . BASE;

		$url  = $file .$amp. '&C=addons_modules' .$amp . 'M=show_module_cp' . $amp . 'module=postmaster' . $amp . 'method=' . $method;

		return str_replace(AMP, $amp, $url);
	}
	
	private function current_url($append = '', $value = '')
	{
		$url = (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['SERVER_NAME'] : 'http://'.$_SERVER['SERVER_NAME'];
		
		if(!empty($append))
			$url .= '?'.$append.'='.$value;
		
		return $url;
	}
	
}