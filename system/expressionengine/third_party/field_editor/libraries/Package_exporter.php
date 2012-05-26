<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Package_exporter
{
	public $site_id;
	
	private $format = 'xml';
	
	private $valid_formats = array('xml', 'json');
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->site_id = $this->EE->config->item('site_id');
	}
	
	public function set_format($format)
	{
		if (in_array($format, $this->valid_formats))
		{
			$this->format = $format;
		}
	}
	
	public function format()
	{
		return $this->format;
	}
	
	public function export_raw($driver, $params)
	{
		$class = 'Package_exporter_'.$driver;
		
		require_once dirname(__FILE__).'/Package_exporter/'.$class.'.php';
		
		$data = call_user_func(array($class, 'export'), $params);
		
		return $data;
	}
	
	public function export($driver, $params)
	{
		$class = 'Package_exporter_'.$driver;
		
		require_once dirname(__FILE__).'/Package_exporter/'.$class.'.php';
		
		$args = array($this->export_raw($driver, $params));
		
		//@TODO change this
		//for some reason $class::$master_node caused errors, not sure why
		switch ($driver)
		{
			case 'field_groups':
				array_push($args, Package_exporter_field_groups::$master_node);
				array_push($args, Package_exporter_field_groups::$sub_nodes);
				array_push($args, Package_exporter_field_groups::$values);
				break;
		}
		
		return call_user_func_array(array($this, 'export_'.$this->format), $args);
		
		//return call_user_func_array(array($this, 'export_'.$this->format), array($this->export_raw($driver, $params), $class::$master_node, $class::$sub_nodes, $class::$values));
	}
	
	public function generate($data)
	{
		return $this->{'generate_'.$this->format}($data);
	}
	
	public function generate_json($data)
	{
		$this->EE->load->library('javascript');
		
		return $this->EE->javascript->generate_json($data, TRUE);
	}
	
	public function export_xml($data, $master_node, $sub_nodes, $values, $first = TRUE)
	{
		$nodes = array();
		
		foreach ($data as $row)
		{
			$children = array();
			
			if ($sub_nodes)
			{
				foreach ($sub_nodes as $_master_node => $_sub_nodes)
				{
					if (isset($row[$_master_node]) && is_array($row[$_master_node]));
					{
						$_data = $row[$_master_node];
						
						unset($row[$_master_node]);
						
						$_data = $this->export_xml($_data, $_master_node, $_sub_nodes, $values, FALSE);
						
						$children = array_merge($children, $_data);
					}
				}
			}
			
			$value = '';
			
			if (isset($values[$master_node]))
			{
				foreach ($row as $k => $v)
				{
					if ($k === $values[$master_node])
					{
						$value = $v;
						
						unset($row[$k]);
						
						break;
					}
				}
			}
			
			$nodes[] = array(
				'name' => $master_node,
				'attributes' => $row,
				'value' => $value,
				'children' => $children,
			);
		}
		
		return $nodes;
	}
	
	public function export_json($data, $master_node, $sub_nodes, $values)
	{
		$this->EE->load->helper('inflector');
		
		$master_node = plural($master_node);
		
		$nodes = array($master_node => array());
		
		foreach ($data as $row)
		{
			if ($sub_nodes)
			{
				foreach ($sub_nodes as $_master_node => $_sub_nodes)
				{
					if (isset($row[$_master_node]) && is_array($row[$_master_node]))
					{
						$_row = $row[$_master_node];
						
						unset($row[$_master_node]);
						
						$row = array_merge($row, $this->export_json($_row, $_master_node, $_sub_nodes, $values));
					}
				}
			}
			
			$nodes[$master_node][] = $row;
		}
		
		return $nodes;
	}

	/**
	 * Generate XML from an array
	 * Operates recursively on $data['children'] which can contain it's own data and children
	 * 
	 * Example array:
	 * 	'name' => 'field',
	 * 	'attributes' => array('attr'=>'value'),
	 * 	'value' => '',
	 * 	'children' => array()
	 * 
	 * @access private
	 * @param array $data a keyed array containing name, attributes, value and children
	 * @param int $depth the tab depth of this XML node
	 * @return string $xml
	 */
	public function generate_xml($data, $depth = 0)
	{
		if ($depth === 0)
		{
			$attributes = array();
			$name = 'xml';
			$children = $data;
			$value = '';
		}
		else
		{
			$attributes = (isset($data['attributes'])) ? $data['attributes'] : array();
	
			$name = (isset($data['name'])) ? $data['name'] : '';
	
			$children = (isset($data['children'])) ? $data['children'] : array();
	
			$value = (isset($data['value'])) ? $data['value'] : '';
		}

		$xml = '';

		$attributes_string = '';

		foreach ($attributes as $key => $attribute)
		{
			$attribute = str_replace(
				array(
				      "'",
				      "\n",
				      "\r"
				),
				array(
				      '&#39;',
				      '\n',
				      '\r'
				),
				$attribute
			);

			$attributes_string .= " $key='$attribute'";
		}

		$indent = str_repeat("\t", $depth);

		if ($children || $value)
		{
			$xml .= "$indent<$name$attributes_string>\n";

			if (is_array($children))
			{
				foreach ($children as $child)
				{
					$xml .= $this->generate_xml($child, $depth + 1);
				}
			}
			else
			{
				$xml .= $children;
			}

			if ($value)
			{
				$xml .= $indent."<![CDATA[\n";

				$xml .= $value;

				$xml .= $indent."]]>\n";
			}

			$xml .= "$indent</$name>\n";
		}
		else
		{
			$xml .= "$indent<$name$attributes_string />\n";
		}

		return $xml;
	}
}

abstract class Package_exporter_driver
{
	public static $master_node;
	
	public static $sub_nodes = array();
	
	public static $values = array();
	
	abstract public static function export($params);
}