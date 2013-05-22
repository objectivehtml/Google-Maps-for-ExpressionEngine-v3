<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.3.0
 * @build		20120522
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
		
		'gmap_world_borders' => array(
			'id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE,
	            'auto_increment' 	=> TRUE
			),
			'name' => array(
				'type'				=> 'varchar',
				'constraint'		=> 50
			),
			'geometry_vertex_count' => array(
				'type'				=> 'int',
				'constraint'		=> 11
			),
			'country_code' => array(
				'type'				=> 'varchar',
				'constraint'		=> 2
			),
			'shape_length' => array(
				'type' 				=> 'FLOAT'
			),
			'shape_area' => array(
				'type' 				=> 'FLOAT'
			),
			'geometry' => array(
				'type' 				=> 'BLOB'
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
		),
		
		'gmap_import_pool' => array(
			'id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE,
	            'auto_increment' 	=> TRUE
			),
			'schema_id' => array(
				'type' 				=> 'varchar',
				'constraint'		=> 100
			),
			'gmt_date' => array(
				'type' 					=> 'int',
				'constraint'		=> 100
			),
			'status' => array(
				'type' 				=> 'varchar',
				'constraint'		=> 50
			),
			'data' => array(
				'type'				=> 'longtext'
			),
			'entry' => array(
				'type'				=> 'longtext'
			),
			'geocode' => array(
				'type'				=> 'text'
			),
			'categories' => array(
				'type'				=> 'text'
			),
			'group_by' => array(
				'type'				=> 'text'
			),
			'group_by_field_name' => array(
				'type'				=> 'text'
			),
			'map_field_name' => array(
				'type'				=> 'text'
			),
			'lat_field_name' => array(
				'type'				=> 'text'
			),
			'lng_field_name' => array(
				'type'				=> 'text'
			),
			'username' => array(
				'type'				=> 'text'
			),
			'force_geocoder' => array(
				'type'				=> 'int',
				'constraint'		=> 1
			)
		),
		
		'gmap_import_stats' => array(
			'schema_id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE
			),
			'schema_name' => array(
				'type' 				=> 'varchar',
				'constraint' 		=> 100,
			),
			/*'items_in_pool' => array(
				'type' 				=> 'float',
			),*/
			'total_entries_imported' => array(
				'type' 				=> 'float',
			),
			'total_entries_failed' => array(
				'type' 				=> 'float',
			),
			'importer_last_ran' => array(
				'type'				=> 'int',
				'constraint'		=> 50
			),
			'importer_total_runs' => array(
				'type'				=> 'int',
				'constraint'		=> 50
			)
		),
		
		'gmap_import_settings' => array(
			'schema_id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE,
	            'auto_increment' 	=> TRUE
			),
			'settings' => array(
				'type' 				=> 'longtext',
			)
		),
		
		'gmap_import_log' => array(
			'id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE,
	            'auto_increment' 	=> TRUE
			),
			'entry_id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE
			),
			'errors' => array(
				'type' 				=> 'longtext',
			),
			'total_errors' => array(
				'type' 				=> 'int',
				'constraint' 		=> 50,
				'primary_key' 		=> TRUE
			),
			'status' => array(
				'type' 				=> 'varchar',
				'constraint' 		=> 100,
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
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'import_csv_save_settings_action'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'import_data_action'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'import_item_action'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'import_start_action'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'change_statuses'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'clear_pool'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'import_csv_ft_action'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'import_check_existing_action'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'import_log_action'
		),
		array(
			'class' 	=> 'Gmap',
			'method'	=> 'world_borders_action'
		),
		array(
			'class' 	=> 'Gmap_mcp',
			'method'	=> 'cron_import_action'
		)
	);
	
	private $hooks = array(
		array('channel_entries_tagdata', 'channel_entries_tagdata'),
		array('channel_entries_tagdata_end', 'channel_entries_tagdata_end')
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
   	 	require_once PATH_THIRD.'gmap/models/kml_model.php';
   	 	  
    	$this->EE->kml_model = new Kml_model();
    	
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
		
		$this->EE->kml_model->install();
			
		return TRUE;
	}
	
	public function update($current = '')
	{	
		if(get_class($this->EE) == 'Wizard')
		{
			return;
		}
		
   	 	require_once PATH_THIRD.'gmap/models/kml_model.php';
   	 	  
    	$this->EE->kml_model = new Kml_model();
    	
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
		
		$this->EE->kml_model->update();
			
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