<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

require 'config/table_builder_config.php';

if(!defined('TABLE_BUILDER_VERSION'))
{	
	define('TABLE_BUILDER_VERSION', $config['table_builder_version']);
}

class Table_builder_upd {

    public $version = TABLE_BUILDER_VERSION;
	public $mod_name;
	public $ext_name;
	public $mcp_name;
	public $ft_name;
	
	private $tables = array(
		'table_builder_rows' 	=> array(
			'row_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
				'primary_key'		=> TRUE,
				'auto_increment'	=> TRUE
			),
			'row_index' => array(
				'type'			=> 'int',
				'constraint' 	=> 100
			),
			'channel_id' => array(
				'type'			=> 'int',
				'constraint' 	=> 100
			),
			'author_id' => array(
				'type'			=> 'int',
				'constraint' 	=> 100
			),
			'entry_id' => array(
				'type'			=> 'int',
				'constraint' 	=> 100
			),
			'field_id' => array(
				'type'			=> 'int',
				'constraint' 	=> 100
			),
			'attributes' => array(
				'type'			=> 'text'
			)
		),
		'table_builder_presets'	=> array(
			'id'			=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
				'primary_key'		=> TRUE,
				'auto_increment'	=> TRUE
			),
			'site_id'		=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
			),
			'channel_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
			),
			'author_id'		=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
			),
			'entry_id'		=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
			),
			'field_id'		=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
			),
			'name'			=> array(
				'type'				=> 'varchar',
				'constraint'		=> 100,
			),
			'celltypes'		=> array(
				'type'				=> 'text',
			),
			'columns'		=> array(
				'type'				=> 'text',
			),
			'rows'			=> array(
				'type'				=> 'text',
			),
			'cells'			=> array(
				'type'				=> 'text',
			),
			'html'			=> array(
				'type'				=> 'text',
			),
			'total_columns'	=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
			),
			'total_rows'	=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
			)
		),
		'table_builder_columns'	=> array(
			'col_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
				'primary_key'		=> TRUE,
				'auto_increment'	=> TRUE
			),
			'title' => array(
				'type'			=> 'varchar',
				'constraint' 	=> 100
			),
			'name' => array(
				'type'			=> 'varchar',
				'constraint' 	=> 100
			),
			'type' => array(
				'type'			=> 'int',
				'constraint' 	=> 50
			)
		),
		'table_builder_entries'	=> array(
			'field_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 100
			),
			'entry_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 100
			),
			'columns' => array(
				'type'			=> 'text',
			),
			'rows' => array(
				'type'			=> 'text',
			),
			'total_columns' => array(
				'type'			=> 'int',
				'constraint' 	=> 11
			),
			'total_rows' => array(
				'type'			=> 'int',
				'constraint' 	=> 11
			)
		),
		'table_builder_celltypes'	=> array(
			'cell_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 100,
				'primary_key'		=> TRUE,
				'auto_increment'	=> TRUE
			),
			'field_id' => array(
				'type'			=> 'int',
				'constraint' 	=> 100
			),
			'title' => array(
				'type'			=> 'varchar',
				'constraint' 	=> 100
			),
			'name' => array(
				'type'			=> 'varchar',
				'constraint' 	=> 100
			),
			'display_name' => array(
				'type'			=> 'varchar',
				'constraint' 	=> 100
			),
			'type' => array(
				'type'			=> 'varchar',
				'constraint' 	=> 100
			),
			'settings' => array(
				'type'			=> 'text'
			) 
		)
	);
	
	private $actions = array(
		array(
			'class'  => 'Table_builder_mcp',
			'method' => 'save_preset'
		),
		array(
			'class'  => 'Table_builder_mcp',
			'method' => 'load_preset'
		),
		array(
			'class'  => 'Table_builder_mcp',
			'method' => 'delete_preset'
		)
	);
	
	private $hooks = array(
		array('entry_submission_ready', 'entry_submission_ready')
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
		if(version_compare($current, TABLE_BUILDER_VERSION, '<'))
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