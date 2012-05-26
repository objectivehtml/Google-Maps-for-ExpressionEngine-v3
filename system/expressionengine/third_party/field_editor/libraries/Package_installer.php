<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! class_exists(basename(__FILE__, '.php'))) :

class Package_installer
{
	private $packages = array();
	private $package_types = array(
		'channel',
		'template_group',
		'field_group',
		'field',
	);
	private $subtypes = array(
		'channel' => array('field_group', 'categories'),
		'template_group' => array('template'),
		'field_group' => array('field'),
		'categories' => array('category')
	);
	private $errors = array();
	private $installed = array();
	
	private $template_path;
	
	private $format = 'xml';
	
	public function __construct($params = array())
	{
		$this->EE =& get_instance();
		
		if (isset($params['format']))
		{
			$this->set_format($params['format']);
		}
		
		if ( ! empty($params['data']))
		{
			$this->load($params['data']);
		}
	}
	
	public function format()
	{
		return $this->format;
	}
	
	public function set_format($format)
	{
		if (in_array($format, array('xml', 'json')))
		{
			$this->format = $format;
		}
	}
	
	public function clear_packages()
	{
		$this->packages = array();
	}
	
	public function remove_package($row_id)
	{
		unset($this->packages[$row_id]);
	}
	
	public function load($data = FALSE)
	{
		$this->add_package($this->{'parse_'.$this->format}($data));
	}
	
	public function add_package($package)
	{
		if ( ! $package)
		{
			return;
		}
		
		if (is_array($package))
		{
			$this->packages = array_merge($this->packages, $package);
		}
		else
		{
			$this->package[] = $package;
		}
	}
	
	public function packages()
	{
		return $this->packages;
	}
	
	// --------------------------------
	//  Clean Attributes
	// --------------------------------
	/**
	 * Clean up XML attributes before parsing
	 *
	 * @access private
	 * @param obj $xml XML object
	 * @return void
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */	
	private function clean_data_xml($data)
	{
		$data = str_replace(
			array(
			      '\n',
			      '\r'
			),
			array(
			      "\n",
			      "\r"
			),
			$data
		);
		
		return $data;
	}
	// END
	
	private function clean_data_json($data)
	{
		return $data;
	}

	// --------------------------------
	//  Clean Fields
	// --------------------------------	
	/**
	 * Remove data from array if the key is not a field in the specified table
	 *
	 * @access private
	 * @param string $table Database table name
	 * @param array $data data to be cleaned
	 * @return void
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function clean_fields($table, $data)
	{
		$fields = $this->EE->db->list_fields($table);
		
		foreach ($data as $key => $value)
		{
			if ( ! in_array($key, $fields))
			{
				unset($data[$key]);
			}
		}
		
		return $data;
	}
	// END 

	// --------------------------------
	//  Create Category
	// --------------------------------
	/**
	 * Create a category from an XML object
	 *
	 * @access private
	 * @param obj $category XML object
	 * @param int $group_id category group id
	 * @return void
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function create_category($category, $group_id)
	{
		if (count($category) > 1)
		{
			foreach ($category as $cat)
			{
				$this->create_category($cat, $group_id);
			}
			
			return;
		}
		
		$category_data = $this->get_attributes($category);
		
		$category_data['site_id'] = $this->EE->config->item('site_id');

		$category_data['group_id'] = $group_id;
		
		$original_name = $category_data['category_name'];

		$category_data['category_name'] = $this->rename('categories', $original_name, 'category_name', array('group_id' => $group_id));

		if ($category_data['category_name'] === FALSE)
		{
			return $this->log_error('category_exists', $original_name);
		}
		
		$this->insert('categories', $category_data);
		
		$this->log_install('category', $category_data['category_name']);
	}

	// --------------------------------
	//  Create Category Group
	// --------------------------------	
	/**
	 * Create a category group from an XML object
	 *
	 * @access private
	 * @param obj $category_group XML object
	 * @return int $group_id
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function create_cat_group($cat_group)
	{
		if (count($cat_group) > 1)
		{
			foreach ($cat_group as $group)
			{
				$this->create_cat_group($group);
			}
			
			return;
		}
		
		$cat_group_data = $this->get_attributes($cat_group);

		$cat_group_data['site_id'] = $this->EE->config->item('site_id');
		
		$group_id = $this->exists('category_groups', array('group_name' => $cat_group_data['group_name']), 'group_id');
		
		if ( ! $group_id)
		{
			$group_id = $this->insert('category_groups', $cat_group_data);
		
			$this->log_install('category_group', $cat_group_data['group_name']);
		}

		if (isset($cat_group->category))
		{
			foreach ($cat_group->category as $category)
			{
				$this->create_category($category, $group_id);
			}
		}
		/*
		foreach ($category_group->children as $category_group_child)
		{
			switch ($category_group_child->tag)
			{
				case 'category':
					$this->create_category($category_group_child, $group_id);
					break;
			}
		}
		*/
		
		return $group_id;
	}
	// END

	// --------------------------------
	//  Create Field
	// --------------------------------
	/**
	 * Create a custom field from an XML object
	 *
	 * @access private
	 * @param obj $field XML object
	 * @return void
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function create_field($field)
	{
		$this->EE->load->dbforge();
		
		$field_data = $this->get_attributes($field);
		
		$original_name = $field_data['field_name'];
		
		$field_data['site_id'] = $this->EE->config->item('site_id');
		
		$field_data['field_name'] = $this->rename('channel_fields', $original_name, 'field_name');

		if ($field_data['field_name'] === FALSE)
		{
			return $this->log_error('field_exists', $original_name);
		}

		$field_id = $this->insert('channel_fields', $field_data);
		
		if (@$field_data['field_type'] == 'date' || @$field_data['field_type'] == 'rel')
		{
			$this->EE->dbforge->add_column('channel_data', array('field_id_'.$field_id => array('type' => 'int(10)')));
		}
		else
		{
			$this->EE->dbforge->add_column('channel_data', array('field_id_'.$field_id => array('type' => 'text', 'null' => FALSE)));
		}
		
		$this->EE->dbforge->add_column('channel_data', array('field_ft_'.$field_id => array('type' => 'tinytext')));
		
		if (@$field_data['field_type'] == 'date')
		{
			$this->EE->dbforge->add_column('channel_data', array('field_dt_'.$field_id => array('type' => 'varchar(8)')));
		}
		
		foreach (array('none', 'br', 'xhtml') as $field_fmt)
		{
			$this->insert('field_formatting', array('field_id' => $field_id, 'field_fmt' => $field_fmt));
		}
		
		$this->log_install('field', $field_data['field_name']);
	}
	// END
	
	// --------------------------------
	//  Create Field Group
	// --------------------------------
	/**
	 * Create a custom field group from an XML object
	 *
	 * @access private
	 * @param obj $field_group XML object
	 * @return int $group_id
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function create_field_group($field_group)
	{
		$field_group_data = $this->get_attributes($field_group);
		
		$original_name = $field_group_data['group_name'];
		
		$field_group_data['site_id'] = $this->EE->config->item('site_id');

		if (FALSE === ($field_group_data['group_name'] = $this->rename('field_groups', $original_name, 'group_name')))
		{
			return $this->log_error('field_group_exists', $original_name);
		}
		
		$group_id = $this->insert('field_groups', $field_group_data);
		
		$this->log_install('field_group', $field_group_data['group_name']);
		
		if (isset($field_group->field))
		{
			$field_order = 1;
			
			foreach ($field_group->field as $field)
			{
				$field->addAttribute('group_id', $group_id);
				
				$field->addAttribute('field_order', $field_order++);
				
				$this->create_field($field);
			}
		}
		
		return $group_id;
	}
	// END

	// --------------------------------
	//  Create Member Group
	// --------------------------------
	/**
	 * Create a member group from an XML object
	 *
	 * @access private
	 * @param obj $template_group XML object
	 * @return int $group_id
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function create_member_group($member_group)
	{
		$member_group_data = $this->get_attributes($member_group);

		$member_group_data['site_id'] = $this->EE->config->item('site_id');
		
		$group_id = $this->exists('member_groups', array('group_title' => $member_group_data['group_title']), 'group_id');
		
		if ( ! $group_id)
		{
			$group_id = $this->insert('member_groups', $member_group_data);
		
			$this->log_install('member_group', $member_group_data['group_title']);
		}
		else
		{
			$this->log_error('member_group_exists', $member_group_data['group_title']);
		}
	}
	// END 

	// --------------------------------
	//  Create Template
	// --------------------------------
	/**
	 * Create a template from an XML object
	 *
	 * @access private
	 * @param obj $template XML object
	 * @param int $group_id custom field group id
	 * @return void
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function create_template($template, $group_id, $group_name)
	{
		$template_data = $this->get_attributes($template);
		
		$template_data['site_id'] = $this->EE->config->item('site_id');

		$template_data['group_id'] = $group_id;
		
		$template_file = $this->template_path.$group_name.'.group'.DIRECTORY_SEPARATOR.$template_data['template_name'].'.html';
		
		if ($this->template_path && file_exists($template_file))
		{
			$template_data['template_data'] = file_get_contents($template_file);
		}
		else
		{
			$template_data['template_data'] = trim((string) $template);
		}
		
		$template_data['edit_date'] = $this->EE->localize->now;

		if ($this->exists('templates', array('group_id' => $group_id, 'template_name' => $template_data['template_name'])))
		{
			return $this->log_error('template_exists', $template_data['template_name']);
		}
		
		/*
		$original_name = $template_data['template_name'];

		$template_data['template_name'] = $this->rename('templates', $original_name, 'template_name', array('group_id' => $group_id));
		*/

		if ($template_data['template_name'] === FALSE)
		{
			return $this->log_error('template_exists', $original_name);
		}
		
		$this->insert('templates', $template_data);
		
		$this->log_install('template', $template_data['template_name']);
	}
	// END
	
	// --------------------------------
	//  Create Template Group
	// --------------------------------
	/**
	 * Create a template group from an XML object
	 *
	 * @access private
	 * @param obj $template_group XML object
	 * @return int $group_id
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function create_template_group($template_group)
	{
		$template_group_data = $this->get_attributes($template_group);
		
		if ($this->exists('template_groups', array('group_name' => $template_group_data['group_name'])))
		{
			return $this->log_error('template_group_exists', $template_group_data['group_name']);
		}

		$template_group_data['site_id'] = $this->EE->config->item('site_id');
		
		if (@$template_group_data['is_site_default'] == 'y')
		{
			$this->EE->db->where('is_site_default', 'y');
			
			if ($this->EE->db->count_all_results('template_groups'));
			{
				$template_group_data['is_site_default'] = 'n';
			}
		}
		
		$group_id = $this->insert('template_groups', $template_group_data);
		
		$this->log_install('template_group', $template_group_data['group_name']);
	
		if (isset($template_group->template))
		{
			foreach ($template_group->template as $template)
			{
				$this->create_template($template, $group_id, $template_group_data['group_name']);
			}
		}
	}
	// END
	
	private function get_attributes($node, $which = NULL)
	{
		$attr = array();
		
		foreach($node->attributes() as $key => $value)
		{
			$attr[$key] = ($node instanceof SimpleJSONElement) ? $value : $this->clean_data_xml($value);
		}
		
		if ( ! is_null($which))
		{
			return (isset($attr[$which])) ? $attr[$which] : FALSE;
		}
		
		return $attr;
	}
	
	// --------------------------------
	//  Create Channel
	// --------------------------------
	/**
	 * Create a channel from an XML object
	 *
	 * @access private
	 * @param obj $channel XML object
	 * @return void
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function create_channel($channel)
	{
		$channel_data = $this->get_attributes($channel);

		if ($this->exists('channels', array('channel_name' => $channel_data['channel_name'])))
		{
			return $this->log_error('channel_exists', $channel_data['channel_name']);
		}
		
		$cat_group = array();
		
		foreach (array('field_group', 'cat_group') as $child)
		{
			if (isset($channel->$child))
			{
				$channel_data[$child] = call_user_func(array($this, 'create_'.$child), $channel->$child);
			}
		}
		
		/*
		if (isset($channel->field_group))
		{
			$channel_data['field_group'] = $this->create_field_group($channel->field_group);
		}
		
		if (isset($channel->category_group))
		{
			if (is_array($channel->category_group))
			{
				foreach ($channel->category_group as $category_group)
				{
					$cat_group[] = $this->create_category_group($category_group);
				}
			}
			else
			{
				$cat_group[] = $this->create_category_group($channel->categories);
			}
		}
		*/
		
		$channel_data['cat_group'] = (isset($channel_data['cat_group']) && ! count($cat_group)) ? $channel_data['cat_group'] : implode('|', $cat_group);

		$channel_data['site_id'] = $this->EE->config->item('site_id');

		$channel_data['channel_lang'] = $this->EE->config->item('xml_lang');

		$channel_data['channel_encoding'] = $this->EE->config->item('charset');

		$channel_id = $this->insert('channels', $channel_data);
		
		if ( ! empty($channel_data['channel_member_groups']))
		{
			if (strtolower($channel_data['channel_member_groups']) === 'all')
			{
				$this->EE->load->model('member_model');
				
				$query = $this->EE->member_model->get_member_groups(array(), array('group_id >' => 4));
				
				foreach ($query->result() as $row)
				{
					$this->EE->db->insert('channel_member_groups', array('group_id' => $row->group_id, 'channel_id' => $channel_id));
				}
				
				$query->free_result();
			}
			else
			{
				foreach (explode('|', $channel_data['channel_member_groups']) as $group_id)
				{
					$this->EE->db->insert('channel_member_groups', array('group_id' => $group_id, 'channel_id' => $channel_id));
				}
			}
		}
		
		$this->log_install('channel', $channel_data['channel_name']);
	}
	// END

	// --------------------------------
	//  Exists
	// --------------------------------	
	/**
	 * Check to see if a database record exists in the specified table
	 * Will return the id if $id_field is specified
	 *
	 * @access private
	 * @param string $table name of table to check
	 * @param array $data key=>value pairs of which columns to check for match
	 * @param string $id_field name of id column
	 * @return bool|int $id_field
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function exists($table, $data, $id_field = FALSE)
	{
		if ($this->EE->db->field_exists('site_id', $table))
		{
			$data['site_id'] = $this->EE->config->item('site_id');
		}
		
		$select = ($id_field) ? $id_field : '*';
		
		$this->EE->db->select($select);
		
		$this->EE->db->where($data);
		
		$query = $this->EE->db->get($table);
		
		return ($id_field) ? $query->row($id_field) : (bool) $query->num_rows();
	}
	// END

	// --------------------------------
	//  Insert
	// --------------------------------	
	/**
	 * Insert a record into the database
	 * 
	 * @access private
	 * @param string $table the database table name
	 * @param array $data a keyed array of the data to insert
	 * @return int $DB->insert_id
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	private function insert($table, $data)
	{
		$data = $this->clean_fields($table, $data);
	
		$this->EE->db->insert($table, $data);

		return $this->EE->db->insert_id();
	}
	// END 
	
	// --------------------------------
	//  Load XML
	// --------------------------------
	/**
	 * Log an error to be displayed on process
	 * 
	 * @access private
	 * @param string $error the error code
	 * @param string $data first string of data for error msg
	 * @param string $second_data second string of data for error msg
	 * @return string $xml
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	public function log_error($error)
	{
		$args = func_get_args();
		
		array_shift($args);
		
		$this->errors[] = vsprintf(lang('error_'.$error), $args);
		
		return FALSE;
	}
	
	public function errors()
	{
		return $this->errors;
	}
	// END 
	
	// --------------------------------
	//  Log Install
	// --------------------------------
	/**
	 * Logs a successful "Auto-Install" action 
	 * 
	 * @access private
	 * @param string $type the type (channel, template, etc) installed
	 * @param string $name the name of the type installed
	 * @return void
	 * @author Rob Sanchez
	 * @since 1.0.0
	 * @subpackage CT Template Installer
	 */
	public function log_install($type)
	{
		$args = func_get_args();
		
		array_shift($args);
		
		$this->installed[] = vsprintf(lang('installed_'.$type), $args);
	}
	
	public function installed()
	{
		return $this->installed;
	}
	// END
	
	/**
	 * set template path
	 *
	 * If using flat files w/ templates, you can set the dir where they're stored
	 * 
	 * @return $this
	 */
	public function set_template_path($template_path)
	{
		if (is_dir($template_path))
		{
			$this->template_path = rtrim($template_path, '/').'/';
		}
		
		return $this;
	}
	
	private function parse_xml($xml = FALSE)
	{
		if ( ! function_exists('simplexml_load_string'))
		{
			return $this->log_error('no_simplexml');
		}
		
		if ( ! $xml)
		{
			return $this->log_error('blank_xml');
		}
		
		$xml = (file_exists($xml)) ? simplexml_load_file($xml) : simplexml_load_string($xml);
		
		if ($xml === FALSE)
		{
			return $this->log_error('xml_error');
		}
		
		$packages = array();
		
		foreach ($this->package_types as $type)
		{
			if (empty($xml->$type))
			{
				continue;
			}
			
			foreach ($xml->$type as $package)
			{
				$packages[] = $package;
			}
		}
		
		return $packages;
	}
	
	private function parse_json($data = FALSE)
	{
		if ( ! $data)
		{
			return $this->log_error('blank_data');
		}
		
		$this->EE->load->library('services_json');
		
		$data = json_decode($data);
		
		if ($data === NULL)
		{
			return $this->log_error('json_error');
		}
		
		$packages = array();
		
		$this->EE->load->helper('inflector');
		
		foreach ($this->package_types as $type)
		{
			$type = plural($type);
			
			if (empty($data->$type))
			{
				continue;
			}
			
			foreach ($data->$type as $package)
			{
				$packages[] = new SimpleJSONElement($type, $package);
			}
		}
		
		return $packages;
	}
	
	// --------------------------------
	//  PARSE XML
	// --------------------------------
	/**
	 * Parse through and install the submitted XML
	 * 
	 * @access private
	 * @return void
	 * @since 1.0.0
	 * @author Rob Sanchez
	 * @subpackage CT Template Installer
	 */
	public function install()
	{
		foreach ($this->packages as $package)
		{
			if (in_array($package->getName(), $this->package_types))
			{
				call_user_func(array($this, 'create_'.$package->getName()), $package);
			}
		}
	}
	// END
	
	// --------------------------------
	//  RENAME
	// --------------------------------
	/**
	 * Checks to see if a record exists for a certain name,
	 * and if so, it will append an integer to the end of the
	 * name in an attempt to generate a unique name.
	 * If the set limit $this->rename_limit is reached it will
	 * return FALSE.
	 * 
	 * @access private
	 * @param string $table the database table to check
	 * @param string $name the name to check
	 * @param string $field the name of the name database column
	 * @param array $data additional data to check against
	 * @return string|bool
	 * @author Rob Sanchez
	 * @since 1.0.0
	 * @subpackage CT Template Installer
	 */
	private function rename($table, $name, $field, $data = array(), $rename_limit = 25)
	{
		$original_name = $name;

		$count = '';

		do
		{
			$name = $original_name.$count;

			$count++;

			$exists = $this->exists($table, array_merge(array($field => $name), $data));

		} while ($count < $rename_limit && $exists);

		return ($count == $rename_limit && $exists) ? FALSE : $name;
	}
	// END
}

/**
 * This simulates a SimpleXML object for JSON, because I couldn't be bothered with refactoring everything else
 */
class SimpleJSONElement
{
	private $name;
	private $attributes;
	
	private static $children = array(
		'channels' => array('cat_groups', 'field_groups'),
		'field_groups' => array('fields'),
		'template_groups' => array('templates'),
	);
	
	private static $values = array(
		'template' => 'template_data',
	);
	
	public static function create($name, $attributes)
	{
		return new SimpleJSONElement($name, $attributes);
	}
	
	public function __construct($name, $attributes)
	{
		get_instance()->load->helper('inflector');
		
		$this->name = $name;
		
		if (isset(self::$children[$this->name]))
		{
			foreach (self::$children[$this->name] as $_name)
			{
				if (isset($attributes->$_name))
				{
					$child = $attributes->$_name;
					
					unset($attributes->$_name);
					
					$child_name = singular($_name);
					
					$this->$child_name = array();
				
					foreach ($child as $_attributes)
					{
						array_push($this->$child_name, new SimpleJSONElement($_name, $_attributes));
					}
				}
			}
		}
		
		$this->attributes = (array) $attributes;
	}
	
	public function getName()
	{
		return singular($this->name);
	}
	
	public function attributes()
	{
		return $this->attributes;
	}
	
	public function addAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}
	
	public function __toString()
	{
		if (isset(self::$values[$this->getName()]))
		{
			$node = self::$values[$this->getName()];
			
			if (isset($this->attributes[$node]))
			{
				return $this->attributes[$node];
			}
		}
		
		return '';
	}
}

endif;