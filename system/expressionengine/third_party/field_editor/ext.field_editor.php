<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'field_editor/config.php';

class Field_editor_ext
{
	public $settings = array();
	public $name = 'Field Editor';
	public $version = FIELD_EDITOR_VERSION;
	public $description = 'Manage custom fields in a snap.';
	public $settings_exist = 'y';
	public $docs_url = 'http://mightybigrobot.com/docs/field-editor';
	public $required_by = array('module');
	
	/**
	 * Extension_ext
	 * 
	 * @access	public
	 * @param	mixed $settings = ''
	 * @return	void
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;
	}
	
	/**
	 * activate_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function activate_extension()
	{
		$this->EE->db->where('class', __CLASS__)
			     ->where('method', 'cp_menu_array');
		
		if ($this->EE->db->count_all_results('extensions') > 0)
		{
			return TRUE;
		}
		
		$hook_defaults = array(
			'class' => __CLASS__,
			'settings' => '',
			'version' => $this->version,
			'enabled' => 'y',
			'priority' => 10
		);
		
		$hooks[] = array(
			'method' => 'cp_menu_array',
			'hook' => 'cp_menu_array'
		);
		
		foreach ($hooks as $hook)
		{
			$this->EE->db->insert('extensions', array_merge($hook_defaults, $hook));
		}
		
		return TRUE;
	}
	
	/**
	 * update_extension
	 * 
	 * @access	public
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$this->EE->db->update('extensions', array('version' => $this->version), array('class' => __CLASS__));
	}
	
	/**
	 * disable_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function disable_extension()
	{
		$this->EE->db->delete('extensions', array('class' => __CLASS__));
	}
	
	/**
	 * settings
	 * 
	 * @access	public
	 * @return	void
	 */
	public function settings()
	{
		$settings = array();
		
		return $settings;
	}
	
	public function settings_form()
	{
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=settings');
	}
	
	public function cp_menu_array($menu)
	{
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$menu = $this->EE->extensions->last_call;
		}
		
		if (isset($menu['admin']['channel_management']) && is_array($menu['admin']['channel_management']))
		{
			$sub_menu = array();
			
			foreach ($menu['admin']['channel_management'] as $key => $value)
			{
				$sub_menu[$key] = $value;
				
				if ($key === 'field_group_management')
				{
					$sub_menu['field_editor'] = array(
						'nav_edit_all' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor',
					);
					
					$this->EE->lang->loadfile('field_editor', 'field_editor');
					
					$query = $this->EE->db->where('site_id', $this->EE->config->item('site_id'))
							      ->order_by('group_name', 'asc')
							      ->get('field_groups');
					
					if ($query->num_rows() > 0)
					{
						foreach ($query->result() as $row)
						{
							$this->EE->lang->language['nav_field_group_'.$row->group_id] = $row->group_name;
							
							$sub_menu['field_editor']['field_group_'.$row->group_id] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=edit'.AMP.'group_id='.$row->group_id;
						}
						
						$sub_menu['field_editor'][] = '----';
					}
					
					$sub_menu['field_editor']['create_field_group'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=new_group';
					
					$sub_menu['field_editor']['manage_field_groups'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor';
					
					$sub_menu['field_editor']['settings'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=settings';
					
					$query->free_result();
				}
			}
			
			$menu['admin']['channel_management'] = $sub_menu;
		}
		
		return $menu;
	}
	
	public function cp_js_end()
	{
		$js = $this->EE->extensions->last_call;
		
		$this->EE->load->helper('array');
		
		//get $_GET from the referring page
		parse_str(parse_url(@$_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $get);
		
		//check if we're on the publish page
		if (element('D', $get) == 'cp' && element('C', $get) == 'admin_content' && element('M', $get) == 'field_group_management')
		{
			$js .= "\r".'$(function(){$("table.mainTable thead tr:first").append("<th />");$("table.mainTable tbody tr").each(function(){if (match = $(this).find("td").eq(1).find("a:first").attr("href").match(/group_id=(\d+)/)){$(this).append(\'<td><a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'group_id='.'\'+match[1]+\'">Field Editor</a></td>\');}});});';
		}
		
		return $js;
	}
}