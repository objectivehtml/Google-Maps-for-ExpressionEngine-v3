<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.0.183
 * @build		20120526
 */

include 'config/gmap_config.php';
include 'libraries/Data_forge.php';

if(!defined('GMAP_VERSION'))
{	
	define('GMAP_VERSION', $config['gmap_version']);
}

class Gmap_upd {

	public $mod_name;
	public $ext_name;
	public $mcp_name;
	public $version;
	
	private $tables = array(
		'gmap_cache' => array(
			'id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE,
	            'auto_increment' 	=> TRUE
			),
			'query' => array(
				'type' 				=> 'TEXT'
			),
			'response' => array(
				'type' 				=> 'LONGTEXT'
			),
			'date' => array(
				'type'				=> 'int',
				'constraint'		=> 50
			),
			'expires' => array(
				'type'				=> 'int',
				'constraint'		=> 50
			),
			'type' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			)
		),
		'gmap_static_maps' => array(
			'id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE,
	            'auto_increment' 	=> TRUE
			),
			'query' => array(
				'type' 				=> 'TEXT'
			),
			'filename' => array(
				'type' 				=> 'VARCHAR',
				'constraint'		=> 50
			),
			'date' => array(
				'type'				=> 'int',
				'constraint'		=> 50
			),
			'expires' => array(
				'type'				=> 'int',
				'constraint'		=> 50
			)
		)
	);
	
	private $actions = array(
		array(
		    'class'     => 'Gmap_ft',
		    'method'    => 'curl'
		),
		array(
		    'class'     => 'Gmap_mcp',
		    'method'    => 'import_csv_action'
		)
	);
	
	private $hooks = array(
		array('channel_entries_tagdata', 'channel_entries_tagdata')
	);
	
    public function __construct()
    {
        // Make a local reference to the ExpressionEngine super object
        $this->EE =& get_instance();
        
        $this->version	    = GMAP_VERSION;
        
        $this->mod_name 	= str_replace('_upd', '', __CLASS__);
        $this->ext_name		= $this->mod_name . '_ext';
        $this->mcp_name		= $this->mod_name . '_mcp';
    }
	
	public function install()
	{	
        $this->EE->load->config('gmap_config');
        
        $this->version = config_item('gmap_version');
        
		$this->EE->load->library('data_forge');
		
		$this->EE->data_forge->update_tables($this->tables);
		
		$data = array(
	        'module_name' 			=> $this->mod_name,
	        'module_version' 		=> $this->version,
	        'has_cp_backend' 		=> 'y',
	        'has_publish_fields' 	=> 'n'
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
		$this->EE->data_forge = new Data_forge();		
		$this->EE->data_forge->update_tables($this->tables);
		
		/* Update the version numbers */
		$this->EE->db->where('name', strtolower($this->mod_name));
		$this->EE->db->update('fieldtypes', array(
			'version' => $this->version
		));
				
		$this->EE->db->where('module_name', strtolower($this->mod_name));
		$this->EE->db->update('modules', array(
			'module_version' => $this->version
		));
		
		/* Do other updates */
		
	    return TRUE;
	}
	
	public function uninstall()
	{
		$this->EE->load->dbforge();
		
		$this->EE->db->delete('modules', array('module_name' => $this->mod_name));
		$this->EE->db->delete('extensions', array('class' => $this->ext_name));		
		$this->EE->db->delete('actions', array('class' => $this->mod_name));
		
		$this->EE->db->delete('actions', array('class' => $this->mod_name));
		$this->EE->db->delete('actions', array('class' => $this->mcp_name));
					
		return TRUE;
	}
	
	private function _set_defaults()
	{ 
		
	}
}
// END CLASS

/* End of file upd.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/upd.gmap.php */