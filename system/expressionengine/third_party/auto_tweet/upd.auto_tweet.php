<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package		Auto Tweet
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.0.1
 * @build		20120313
 */
 
class Auto_tweet_upd {

    public $version = '1.0';
	public $mod_name;
	public $ext_name;
	public $mcp_name;
	
	private $tables = array(
		'auto_tweet_channels' => array(
			'id' => array(
				'type' => 'int',
				'constraint' => 50,
				'primary_key' => TRUE,
	            'auto_increment' => TRUE
			),
			'channel_id' => array(
				'type' 				=> 'int',
				'constraint' 		=> 10
			),
			'users'	=> array(
				'type' 			=> 'varchar',
				'constraint' 	=> 250
			),
			'tweet_format' 	=> array(
				'type' 		=> 'text'
			),
			'hash_tags'		=> array(
				'type'			=> 'varchar',
				'constraint'	=> 50
			),
			'latitude_field_id' => array(
				'type'			=> 'int',
				'constraint'	=> 20
			),
			'longitude_field_id' => array(
				'type'			=> 'int',
				'constraint'	=> 20
			),
			'multiple_tweets' => array(
				'type'			=> 'int',
				'constraint'	=> 10
			),
			'statuses' => array(
				'type'			=> 'varchar',
				'constraint'	=> 250 
			),
			'mentions' => array(
				'type'			=> 'varchar',
				'constraint'	=> 50
			),
			'url' => array(
				'type'			=> 'varchar',
				'constraint'	=> 50
			)
		),
		'auto_tweet_users'		=> array(
			'user_id' => array(
				'type' => 'varchar',
				'constraint' => 50,
				'primary_key' => TRUE
			),
			'oauth_token'	=> array (
				'type' => 'varchar',
				'constraint' => 250,
			),
			'oauth_token_secret' => array (
				'type' => 'varchar',
				'constraint' => 250,
			),
			'screen_name' => array(
				'type' => 'varchar',
				'constraint' => 50
			)
		),
		'auto_tweet_settings'	=> array(
			'key'	=> array(
				'type'			=> 'varchar',
				'constraint'	=> 100,
				'primary_key'	=> TRUE
			),
			'value'	=> array(
				'type'			=> 'text'
			)
		)
	);
	
	private $actions = array(
		array(
		    'class'     => 'Auto_tweet_mcp',
		    'method'    => 'callback'
		),
		array(
		    'class'     => 'Auto_tweet_mcp',
		    'method'    => 'save_settings'
		),
		array(
		    'class'     => 'Auto_tweet_mcp',
		    'method'    => 'delete_channel'
		),
		array(
		    'class'     => 'Auto_tweet_mcp',
		    'method'    => 'delete_account'
		)
	);
	
	private $hooks = array(
		array('entry_submission_end', 'entry_submission_end')
	);
	
    public function __construct()
    {
        // Make a local reference to the ExpressionEngine super object
        $this->EE =& get_instance();
        
        $this->mod_name 	= str_replace('_upd', '', __CLASS__);
        $this->ext_name		= $this->mod_name . '_ext';
        $this->mcp_name		= $this->mod_name . '_mcp';
    }
	
	public function install()
	{	
		$this->EE->load->dbforge();
		
		$this->EE->load->library('data_forge');
		
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
					'class' 	=> 'Auto_tweet_ext',
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
		
		foreach(array_keys($this->tables) as $table)
		{
			$this->EE->dbforge->drop_table($table);
		}
			
		return TRUE;
	}
	
	private function _set_defaults()
	{ 
		$this->EE->load->model('auto_tweet_model');

		$action_id = $this->EE->auto_tweet_model->get_action_id();
									
		$this->EE->db->insert('auto_tweet_settings', array(
			'key' 	=> 'action_id',
			'value'	=> $action_id
		));
			
		$this->EE->db->insert('auto_tweet_settings', array(
			'key' 	=> 'callback_url',
			'value'	=> $this->EE->functions->fetch_site_index() . '?ACT=' . $action_id
		));
		
		$this->EE->db->insert('auto_tweet_settings', array(
			'key' 	=> 'consumer_key',
			'value'	=> '0'
		));
		
		$this->EE->db->insert('auto_tweet_settings', array(
			'key' 	=> 'consumer_secret',
			'value'	=> '0'
		));
		
		$this->EE->db->insert('auto_tweet_settings', array(
			'key' 	=> 'shorten_url',
			'value'	=> '0'
		));
		
		$this->EE->db->insert('auto_tweet_settings', array(
			'key' 	=> 'bitly_username',
			'value'	=> ''
		));
		
		$this->EE->db->insert('auto_tweet_settings', array(
			'key' 	=> 'bitly_api_key',
			'value'	=> ''
		));
	}
}