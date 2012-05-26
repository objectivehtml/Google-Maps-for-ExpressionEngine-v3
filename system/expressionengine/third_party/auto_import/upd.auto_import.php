<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Auto Import
 * 
 * @package		Auto Import
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com
 * @version		0.1.0
 * @build		20120516
 */

require 'config/auto_import_config.php';

if(!defined('AUTO_IMPORT_VERSION'))
{	
	define('AUTO_IMPORT_VERSION', $config['AUTO_IMPORT_VERSION']);
}

class Auto_import_upd {

    public $version = AUTO_IMPORT_VERSION;
	public $mod_name;
	public $ext_name;
	public $mcp_name;
	public $ft_name;
	
	private $tables = array(
		'auto_import_settings' 	=> array(
			'key'	=> array(
				'type'				=> 'varchar',
				'constraint'		=> 100,
				'primary_key'		=> TRUE
			),
			'value' => array(
				'type'				=> 'varchar',
				'constraint'		=> 100,
			)
		)
	);
	
	private $actions = array(
		array(
		   	'class'     => 'Auto_import_mcp',
		    'method'    => 'import'
		),
		array(
		   	'class'     => 'Auto_import_mcp',
		    'method'    => 'save_settings'
		)
	);
	
	private $hooks = array(
	);
	
    public function __construct()
    {
        // Make a local reference to the ExpressionEngine super object
        $this->EE =& get_instance();
        
        $this->mod_name 	= str_replace('_upd', '', __CLASS__);
        $this->ext_name		= $this->mod_name . '_ext';
        $this->mcp_name		= $this->mod_name . '_mcp';
        $this->ft_name		= $this->mod_name . '_ft';
    }
	
	public function install()
	{	
		$this->EE->load->dbforge();
		
		//create tables from $this->tables array
		$this->EE->load->library('Data_forge');
		
		$this->EE->data_forge->update_tables($this->tables);
		
		$data = array(
	        'module_name' => $this->mod_name,
	        'module_version' => $this->version,
	        'has_cp_backend' => 'y',
	        'has_publish_fields' => 'n'
	    );
	    	
	    $this->EE->db->insert('modules', $data);
	    	    	    
		foreach ($this->hooks as $row)
		{
			$this->EE->db->insert(
				'extensions',
				array(
					'class' 	=> $this->ext_name,
					'method' 	=> $row[0],
					'hook' 		=> ( ! isset($row[1])) ? $row[0] : $row[1],
					'settings' 	=> ( ! isset($row[2])) ? '' : $row[2],
					'priority' 	=> ( ! isset($row[3])) ? 10 : $row[3],
					'version' 	=> $this->version,
					'enabled' 	=> 'y',
				)
			);
		}
		
		foreach($this->actions as $action)
			$this->EE->db->insert('actions', $action);
		
		$this->_set_defaults();
				
		return TRUE;
	}
	
	public function update($current = '')
	{
		if(version_compare($current, AUTO_IMPORT_VERSION, '<'))
		{
			require_once PATH_THIRD.'table_builder/libraries/Data_forge.php';

			unset($this->tables['table_builder_rows']);

			$this->EE->data_forge = new Data_forge();
			$this->EE->data_forge->update_tables($this->tables);

			foreach($this->actions as $action)
			{
				$this->EE->db->where($action);
				$existing = $this->EE->db->get('actions');

				if($existing->num_rows() == 0)
				{
					$this->EE->db->insert('actions', $action);
				}
			}
			
			foreach($this->hooks as $row)
			{
				$this->EE->db->where(array(
					'class'  => $this->ext_name,
					'method'  => $row[0],
					'hook' => $row[1]
				));
				
				$existing = $this->EE->db->get('extensions');

				if($existing->num_rows() == 0)
				{
					$this->EE->db->insert(
						'extensions',
						array(
							'class' 	=> $this->ext_name,
							'method' 	=> $row[0],
							'hook' 		=> ( ! isset($row[1])) ? $row[0] : $row[1],
							'settings' 	=> ( ! isset($row[2])) ? '' : $row[2],
							'priority' 	=> ( ! isset($row[3])) ? 10 : $row[3],
							'version' 	=> $this->version,
							'enabled' 	=> 'y',
						)
					);
				}
			}
		}

	    return TRUE;
	}
	
	public function uninstall()
	{
		$this->EE->load->dbforge();
		
		$this->EE->db->delete('modules', array('module_name' => $this->mod_name));
		$this->EE->db->delete('extensions', array('class' => $this->ext_name));		
		$this->EE->db->delete('actions', array('class' => $this->mod_name));		
		$this->EE->db->delete('actions', array('class' => $this->ext_name));
		$this->EE->db->delete('actions', array('class' => $this->mcp_name));
		$this->EE->db->delete('actions', array('class' => $this->ft_name));
		
		foreach(array_keys($this->tables) as $table)
		{
			$this->EE->dbforge->drop_table($table);
		}
			
		return TRUE;
	}
	
	private function _set_defaults()
	{
	}
}