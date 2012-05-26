<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package		Auto Tweet Model
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.0
 * @build		20120313
 */
 
Class Auto_tweet_model extends CI_Model {
	
	public $consumer_key;
	public $consumer_secret;
	
	public function __construct()
	{	
		$this->load->driver('channel_data');
		
		$this->consumer_key = $this->get_settings('consumer_key');
		$this->consumer_secret = $this->get_settings('consumer_secret');
	}
	
	public function delete_account($user_id)
	{
		$channels = $this->get_saved_channels();
		
		foreach($channels->result() as $channel)
		{
			$users = explode('|', $channel->users);
			
			foreach($users as $index => $user)
			{
				if($user == $user_id)
					unset($users[$index]);
			}

			$users = implode('|', $users);
			
			$this->update_channel($channel->id, array(
				'users' => $users
			));
		}		
		
		$this->db->delete('auto_tweet_users', array('user_id' => $user_id));
	}

	public function delete_channel($id)
	{
		$this->db->delete('auto_tweet_channels', array('id' => $id));
	}
	
	public function get_action_id($class = 'Auto_tweet_mcp', $method = 'callback')
	{
		$action_id = $this->db->where('class', $class)
							  ->where('method', $method)
							  ->get('actions')
							  ->row('action_id');
									  
		return !is_array($action_id) ? $action_id : FALSE;		
	}
	
	public function get_channel($channel_id)
	{			
		return $this->channel_data->get_channel($channel_id);
	}
	
	public function get_channels()
	{		
		return $this->channel_data->get_channels(array('*'));
	}
	
	public function get_entry($entry_id, $select = array())
	{
		return $this->channel_data->get_channel_entry($entry_id, $select);
	}
	
	public function get_fields($channel_id)
	{
		return $this->channel_data->get_fields();
	}
	
	public function get_saved_channels()
	{
		return $this->db->get('auto_tweet_channels');
	}
	
	public function get_settings($key = FALSE)
	{
		$settings = array();
				
		foreach($this->db->get('auto_tweet_settings')->result() as $row)
			$settings[$row->key] = $row->value ? $row->value : '';
		
		if($key === FALSE)
			return $settings;
		else
			return isset($settings[$key]) ? $settings[$key] : FALSE;
	}
	
	public function get_statuses($group_id)
	{
		return $this->channel_data->get_statuses(array(
			'where' => array(
				'group_id' => $group_id
			)
		));
	}
	
	public function get_saved_statuses($channel_id)
	{
		return $this->db->where('channel_id', $channel_id)->get('auto_tweet_channels');
	}
	
	public function get_users($users = FALSE)
	{
		if($users && !is_array($users))
		{
			$users = explode('|', $users);
		
			foreach($users as $user)
			{
				$this->db->where('user_id', $user);
			}
		}
				
		return $this->db->get('auto_tweet_users');
	}
	
	public function is_user_authorized($user_id)
	{
		$users = $this->db->where('user_id', $user_id)->get('auto_tweet_users')->num_rows();
		
		return $users == 0 ? FALSE : TRUE;
	}
	
	public function save_credentials($creds)
	{
		$this->db->delete('auto_tweet_users', array('user_id' => $creds['user_id']));
		$this->db->insert('auto_tweet_users', $creds);
	}
	
	public function save_channel($data)
	{
		$this->db->insert('auto_tweet_channels', $data);
		
		return $this->db->insert_id();
	} 
	
	public function save_settings($settings)
	{
		foreach($settings as $key => $value)
		{
			$this->db->where('key', $key)->update('auto_tweet_settings', array(
				'value' => $value
			));
		}
	}
	
	public function update_channel($id, $data)
	{
		$this->db->where('id', $id)->update('auto_tweet_channels', $data);
	}
}