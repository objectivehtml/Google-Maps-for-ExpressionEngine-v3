<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auto_import_model extends CI_Model {

	private $json_encode = array('channel_fields', 'matrix_fields');

	public function __construct()
	{
		parent::__construct();

		$this->load->helper('url');

		$this->settings = $this->get_settings();
	}

	function add_category($category, $parent_id = 0)
	{
		$cat_group  = $this->settings['product_category_group'];

		$total_categories = $this->channel_data->get_category_entries(array(
			'where' => array(
				'categories.group_id'  => $cat_group,
				'categories.parent_id' => $parent_id
			)
		))->num_rows();

		$category_data = array(
			'site_id'         => 1,
			'group_id'        => $cat_group,
			'parent_id'       => $parent_id,
			'cat_name'        => $category[$this->settings['category_name_column']],
			'cat_description' => isset($category[$this->settings['category_description_column']]) ? $category[$this->settings['category_description_column']] : '',
			'cat_url_title'   => url_title($category[$this->settings['category_name_column']]),
			'cat_order'       => $total_categories+1
 		);

		$this->insert('categories', $category_data);

		$category_field_data = array(
			'cat_id'   => $this->insert_id(),
			'site_id'  => 1,
			'group_id' => $cat_group
		);

		$cat_fields = $this->channel_data->get_category_fields(array(
			'where' => array(
				'group_id' => $cat_group
			)
		));

		foreach($cat_fields->result() as $cat_field)
		{
			if($this->settings['category_id_field'] == $cat_field->field_name)
			{
				$category_field_data['field_id_'.$cat_field->field_id] = $category[$this->settings['category_id_column']];
				$category_field_data['field_ft_'.$cat_field->field_id] = $cat_field->field_default_fmt;
			}
		}

		$this->insert('category_field_data', $category_field_data);
	}

	function update_category($category, $parent_id = 0)
	{
		$cat_group = $this->settings['product_category_group'];
		
		$cat_id    = $this->channel_data->get_category_entries(array(
			'where' => array(
				'category_id' => $category[$this->settings['category_id_column']]
			)
		))->row('cat_id');

		$category_data = array(
			'site_id'         => 1,
			'group_id'        => $cat_group,
			'parent_id'       => $parent_id,
			'cat_name'        => $category[$this->settings['category_name_column']],
			'cat_description' => isset($category[$this->settings['category_description_column']]) ? $category[$this->settings['category_description_column']] : '',
			'cat_url_title'   => url_title($category[$this->settings['category_name_column']])
		);

		$this->where('cat_id', $cat_id);
		$this->update('categories', $category_data);

		$category_field_data = array(
			'cat_id'   => $cat_id,
			'site_id'  => 1,
			'group_id' => $cat_group
		);

		$cat_fields = $this->channel_data->get_category_fields(array(
			'where' => array(
				'group_id' => $cat_group
			)
		));

		foreach($cat_fields->result() as $cat_field)
		{
			if($this->settings['category_id_field'] == $cat_field->field_name)
			{
				$category_field_data['field_id_'.$cat_field->field_id] = $category[$this->settings['category_id_column']];
				$category_field_data['field_ft_'.$cat_field->field_id] = $cat_field->field_default_fmt;
			}
		}
		
		$this->where('cat_id', $cat_id);
		$this->update('category_field_data', $category_field_data);
	}

	function add_categories($categories, $parent_id = 0)
	{
		foreach($categories as $category)
		{
			$category_data = $this->channel_data->get_category_entries(array(
				'where' => array(
					'category_id' => $category['ID']
				)
			));

			if($category_data->num_rows() == 0)
			{
				$this->add_category($category, $parent_id);
			}
			else
			{
				$this->update_category($category, $parent_id);
			}
		}
	}

	function add_sub_categories($sub_categories)
	{
		foreach($sub_categories as $sub_category)
		{
			$parent = $this->channel_data->get_category_entries(array(
				'where' => array(
					$this->settings['category_id_field'] => $sub_category[$this->settings['category_parent_id_column']]
				)
			))->row();

			if(!$this->does_category_exist($sub_category))
			{
				$this->add_category($sub_category, $parent->cat_id);
			}
			else
			{
				$this->update_category($sub_category, $parent->cat_id);
			}
		
		}
	}

	function does_category_exist($category)
	{
		$data = $this->channel_data->get_category_entries(array(
			'where' => array(
				$this->settings['category_id_field'] => $category[$this->settings['category_id_column']]
			)
		));

		return $data->num_rows() > 0 ? TRUE : FALSE;
	}

	public function add_vendors($vendors)
	{
		foreach($vendors as $vendor)
		{
			if(!$this->does_vendor_exist($vendor))
			{
				$this->add_vendor($vendor);
			}
			else
			{
				$this->update_vendor($vendor);
			}
		}
	}

	public function add_vendor($vendor)
	{
		$channel_id = $this->settings['vendors_channel'];

		$data = array(
			'title' 	=> $vendor[$this->settings['vendors_title_column']],
			$this->settings['vendors_id_field'] => $vendor[$this->settings['vendors_id_column']],
			'status' 	=> 'open',
			'author_id' => $this->settings['author_id']
		);

		$data['url_title'] = url_title(strtolower($data['title']));

		$post = $this->channel_data->utility->prepare_entry($channel_id, $data);

		$entry_id = $this->channel_data->utility->submit_entry($channel_id, $post);
	}

	public function update_vendor($vendor)
	{
		$channel_id = $this->settings['vendors_channel'];
		
		$entry = $this->channel_data->get_channel_entries($this->settings['vendors_channel'], array(
			'where' => array(
				$this->settings['vendors_id_field'] => $vendor[$this->settings['vendors_id_column']]
			)
		))->row();

		$data = array(
			'title' => $vendor[$this->settings['vendors_title_column']],
			$this->settings['vendors_id_field'] => $vendor[$this->settings['vendors_id_column']],
			'status' => 'Open',
			'author_id' => $this->settings['author_id']
		);

		$data['url_title'] = url_title(strtolower($data['title']));

		$post = $this->channel_data->utility->prepare_entry($channel_id, $data);

		$this->channel_data->utility->update_entry($channel_id, $entry->entry_id, $post);
	}

	public function does_vendor_exist($vendor)
	{
		$vendor = $this->channel_data->get_channel_entries($this->settings['vendors_channel'], array(
			'where' => array(
				$this->settings['vendors_id_field'] => $vendor[$this->settings['vendors_id_column']]
			)
		));

		return $vendor->num_rows() > 0 ? TRUE : FALSE;
	}

	public function add_products($items)
	{
		$products = $this->build_product_array($items);

		foreach($products as $id => $product)
		{	
			$existing_product = $this->does_product_exist($id);

			if(!$existing_product)
			{
				$this->add_product($product);
			}
			else
			{
				$this->update_product($existing_product->row('entry_id'), $product, $existing_product);
			}
		}
	}

	public function add_product($product)
	{
		$product['product']['title']          = $product['product'][$this->settings['products_title_field']];
		$product['product']['url_title']      = strtolower(url_title($product['product']['title']));
		$product['product']['author_id']      = $this->settings['author_id'];
		$product['product']['status']         = 'open';
		
		$data     = $this->channel_data->utility->prepare_entry($this->settings['products_channel'], $product['product']);
		$entry_id = $this->channel_data->utility->submit_entry($this->settings['products_channel'], $data);

		$this->add_options($entry_id, $product['options']);
		
		return $entry_id;
	}

	public function update_product($entry_id, $product, $entry)
	{
		$product['product']['title']          = $product['product'][$this->settings['products_title_field']];
		$product['product']['url_title']      = $entry->row('url_title');
		$product['product']['author_id']      = $entry->row('author_id');
		$product['product']['status']         = $entry->row('status');

		$data = $this->channel_data->utility->prepare_entry($this->settings['products_channel'], array_merge($entry->row_array(), $product['product']));

		$this->channel_data->utility->update_entry($this->settings['products_channel'], $entry_id, $data);

		$this->add_options($entry_id, $product['options']);
	}

	public function does_product_exist($id)
	{
		$product = $this->channel_data->get_channel_entries($this->settings['products_channel'], array(
			'where' => array(
				$this->settings['products_id_field'] => $id
 			)
		));

		if($product->num_rows() > 0)
		{
			return $product;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_options($entry_id, $options)
	{
		$matrix_field = $this->channel_data->get_fields(array(
			'where' => array(
				'field_name' => $this->settings['matrix_field_name']
 			)
		));

		$matrix_columns = $this->channel_data->get('matrix_cols', array(
			'where' => array(
				'field_id' => $matrix_field->row('field_id')
			)
		));

		$columns = array();

		foreach($matrix_columns->result() as $column)
		{
			$columns[$column->col_name] = $column;;
		}

		foreach($options as $option)
		{
			if(!$this->does_option_exist($matrix_field, $columns, $entry_id, $option))
			{
				$this->add_option($matrix_field, $columns, $entry_id, $option);
			}
			else
			{
				$this->update_option($matrix_field, $columns, $entry_id, $option);
			}
		}

		$this->delete_options($matrix_field, $columns, $entry_id, $options);
	}

	public function add_option($field, $columns, $entry_id, $option)
	{
		$data = array(
			'site_id' => 1,
			'entry_id' => $entry_id,
			'field_id' => $field->row('field_id'),
			'row_order' => $this->get_total_options($field, $columns, $entry_id, $option)
		);

		foreach($columns as $column_name => $column)
		{
			$data['col_id_'.$column->col_id] = $option[$column->col_name];
		}

		$this->db->insert('matrix_data', $data);
	}

	public function update_option($field, $columns, $entry_id, $option)
	{
		foreach($columns as $column_name => $column)
		{
			$data['col_id_'.$column->col_id] = $option[$column->col_name];
		}
		
		$index = $this->settings['matrix_unique_column'];

		$this->db->where('field_id', $field->row('field_id'));
		$this->db->where('entry_id', $entry_id);
		$this->db->where('col_id_'.$columns[$index]->col_id, $option[$index]);
		$this->db->update('matrix_data', $data);
	}

	public function delete_options($matrix_field, $columns, $entry_id, $options)
	{
		$where = array(
		);

		$index = $this->settings['matrix_unique_column'];

		$count = 0;

		foreach($options as $option)
		{
			$where['{'.$count.'} col_id_'.$columns[$index]->col_id.' !='] = $option[$index];
			$where['{'.$count.'} field_id'] = $matrix_field->row('field_id');
			$where['{'.$count.'} entry_id'] = $entry_id;

			$count++;
		}

		$data = $this->channel_data->get('matrix_data', array(
			'where' => $where
		));

		foreach($data->result() as $row)
		{
			$row_id = $row->row_id;

			$this->db->where('row_id', $row_id);
			$this->db->delete('matrix_data');
		}

		$data = $this->channel_data->get('matrix_data', array(
			'where' => array(
				'entry_id' => $entry_id,
				'field_id' => $matrix_field->row('field_id')
			),
			'order_by' => 'row_order',
			'sort' => 'asc'
		));

		$count = 0;

		foreach($data->result() as $row)
		{
			$this->db->where('row_id', $row->row_id);
			$this->db->update('matrix_data', array(
				'row_order' => $count
			));

			$count++;
		}
	}

	public function get_total_options($field, $columns, $entry_id, $option)
	{
		$matrix_row = $this->channel_data->get('matrix_data', array(
			'where' => array(
				'entry_id' => $entry_id,
				'field_id' => $field->row('field_id')
			)
		));

		return $matrix_row->num_rows();
	}

	public function does_option_exist($field, $columns, $entry_id, $option)
	{
		$index = $this->settings['matrix_unique_column'];

		$matrix_row = $this->channel_data->get('matrix_data', array(
			'where' => array(
				'entry_id' => $entry_id,
				'field_id' => $field->row('field_id'),
				'col_id_'.$columns[$index]->col_id => $option[$index]
			)
		));

		if($matrix_row->num_rows() > 0)
		{
			return $matrix_row;
		}
		else
		{
			return FALSE;
		}
	}

	public function build_product_array($items)
	{
		$vendors_data  = $this->channel_data->get_channel_entries($this->settings['vendors_channel']);
		$vendors 	   = array();
		
		foreach($vendors_data->result_array() as $index => $vendor)
		{
			$vendors[$vendor[$this->settings['vendors_id_field']]] = $vendor;
		}

		foreach($items as $row)
		{
			$product_id = $row[$this->settings['products_id_column']];

			$options = array();

			foreach($this->settings['matrix_fields'] as $fields)
			{
				$options[$fields->field_name] = $row[$fields->column_name];

				unset($row[$fields->column_name]);
			}
			
			$vendor_id = $row[$this->settings['vendors_id_column']];
			$vendor    = isset($vendors[$vendor_id]) ? $vendors[$vendor_id] : NULL;

			unset($row[$this->settings['vendors_id_column']]);

			$row[$this->settings['vendors_id_column']] = $vendor['entry_id'];

			foreach($row as $column_name => $value)
			{
				foreach($this->settings['channel_fields'] as $index => $field)
				{	
					if($field->column_name == $column_name)
					{
						$product[$product_id]['product'][$field->field_name] = $value;
					}
				}
			}
			
			$product[$product_id]['product'][$this->settings['matrix_field_name']] = '';
			$product[$product_id]['vendor']	   = $vendor['entry_id'];
			$product[$product_id]['options'][] = $options;			
		}

		return $product;
	}

	function get_settings()
	{
		$data = $this->db->get('auto_import_settings')->result_array();
		
		$settings = array();
		
		foreach($data as $setting)
		{
			if(in_array($setting['key'], $this->json_encode))
			{
				$setting['value'] = json_decode($setting['value']);
			}

			$settings[$setting['key']] = $setting['value'];
		}
		
		return $settings;
	}	
	
	public function get_setting($index)
	{
		if(isset($this->settings[$index]))
		{
			if(in_array($this->settings[$index], $this->json_encode))
			{
				$this->settings[$index] = json_decode($this->settings[$index]);
			}

			return $this->settings[$index];
		}

		return FALSE;
	}

	public function get_setting_fields($channel_id = FALSE)
	{
		if($channel_id)
		{
			$data_fields = $this->channel_data->get_channel_fields($channel_id);
		}
		else
		{
			$data_fields = $this->channel_data->get_fields();
		}

		$fields      = array();
		$field_names = array();

		foreach($data_fields->result() as $field)
		{
			$fields[$field->field_id]        = $field;
			$field_names[$field->field_name] = $field;
		}

		return (object) array(
			'fields'     => $fields,
			'field_names' => $field_names
		);
	}

	public function get_setting_field($index)
	{
		$settings = $this->get_setting_fields();

		return $settings->field[$index];
	}

	public function get_setting_field_name($index)
	{
		$settings = $this->get_setting_fields();

		return $settings->fields[$this->settings[$index]]->field_name;
	}

	public function save_settings($data)
	{
		foreach($data as $key => $value)
		{
			$this->db->where('key', $key);
			
			if(in_array($key, $this->json_encode))
			{
				$value = json_encode($value);
			}

			$this->db->update('auto_import_settings', array(
				'key' 	=> $key,
				'value' => $value
			));
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

	private function update($table, $data)
	{
		$this->db->update($table, $data);
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