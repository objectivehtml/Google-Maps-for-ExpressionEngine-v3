<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package		Auto Tweet
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.0.1
 * @build		20120313
 */
 
require 'libraries/TwitterOAuth.php';

class Auto_tweet_mcp {
	
	public $consumer_key, $consumer_secret;
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->library('channel_data');
		$this->EE->load->model('auto_tweet_model');
		
		$this->consumer_key 	= $this->EE->auto_tweet_model->consumer_key;
		$this->consumer_secret 	= $this->EE->auto_tweet_model->consumer_secret;
	}
	
	public function index()
	{
		$this->EE->load->library('Twitter');
		
		$vars 				= array();
		$vars['settings'] 	= $this->EE->auto_tweet_model->get_settings();
		
		$action_id 	= $this->EE->auto_tweet_model->get_action_id();
		
		$vars['settings']['callback_url'] = !empty($vars['settings']['callback_url']) ? $vars['settings']['callback_url'] : $this->_current_url('ACT', $action_id);
	
		$vars['return'] = $this->_current_url() . $_SERVER['REQUEST_URI'];
		
		$vars['request_token_url'] = $this->_url('request_token');
		$vars['delete_account_url'] = $this->_url('delete_account');
		$vars['delete_channel_url'] = $this->_current_url('ACT', $this->EE->channel_data->get_action_id('Auto_tweet_mcp', 'delete_channel'));
		
		$vars['users'] = $this->EE->auto_tweet_model->get_users();
		$vars['channels'] = $this->EE->auto_tweet_model->get_channels();
		$vars['saved_channels'] = $this->EE->auto_tweet_model->get_saved_channels()->result();
		$vars['settings_action'] = $this->_current_url('ACT', $this->EE->auto_tweet_model->get_action_id(__CLASS__, 'save_settings'));
				
		foreach($vars['saved_channels'] as $index => $saved_channel)
		{
			$channel = $this->EE->auto_tweet_model->get_channel($saved_channel->channel_id)->row();
			$fields = $this->EE->auto_tweet_model->get_fields($saved_channel->channel_id)->result();
			$statuses = $this->EE->auto_tweet_model->get_statuses($channel->status_group)->result();
						
			$saved_channel->fields = $fields;
			$saved_channel->status_options = $statuses;
			
			$vars['saved_channels'][$index] = $saved_channel;
		}
		
		
		$vars['channels'] = $this->add_dynamic_fields($vars['channels']->result());
		
		$js	= '
		<script type="text/javascript">
			var channels = '.json_encode($vars['channels']).';
			var users	 = '.json_encode($vars['users']->result()).';
		</script>';
		
		$this->EE->cp->add_to_head($js);
		
		return $this->EE->load->view('control_panel', $vars, TRUE);
	}
	
	public function delete_channel()
	{
		$this->EE->auto_tweet_model->delete_channel($this->EE->input->get('id'));
	}

	public function save_settings()
	{
		$settings = array(
			'consumer_key'		=> $this->EE->input->post('consumer_key'),
			'consumer_secret'	=> $this->EE->input->post('consumer_secret'),
			'callback_url'		=> $this->EE->input->post('callback_url'),
			'shorten_url'		=> $this->EE->input->post('shorten_url'),
			'bitly_username'	=> $this->EE->input->post('bitly_username'),
			'bitly_api_key'		=> $this->EE->input->post('bitly_api_key'),
		);
		
		$this->EE->auto_tweet_model->save_settings($settings);
				
		$channels = $this->EE->input->post('channel');
		
		if(is_array($channels))
		{			
			foreach($channels as $index => $channel)
			{
				$data = array(
					'channel_id' 			=> $channel['channel_id'],
					'users'		 			=> isset($channel['user']) ? implode('|', $channel['user']) : NULL,
					'latitude_field_id'		=> trim($channel['latitude']),
					'longitude_field_id'	=> trim($channel['longitude']),
					'statuses'				=> isset($channel['statuses']) ? implode('|', $channel['statuses']) : NULL,
					'tweet_format'			=> trim(htmlentities($channel['tweet-format'])),
					'multiple_tweets'		=> isset($channel['multiple-tweets']) ? 1 : 0,
					'hash_tags'				=> trim($channel['hash_tags']),
					'url'					=> trim(htmlentities($channel['url'])),
					'mentions'				=> isset($channel['mentions']) ? trim($channel['mentions']) : ''
				);
				
				if(strpos($index, 'new') !== FALSE)
				{
					$this->EE->auto_tweet_model->save_channel($data);
				}
				else
				{
					$this->EE->auto_tweet_model->update_channel($index, $data);
				}
			}
			
		}
		
		$this->EE->functions->redirect($this->EE->input->post('redirect_uri')) . '&message=success';
	}
	
	public function request_token()
	{	
		$oauth 		= new TwitterOAuth($this->consumer_key, $this->consumer_secret);
		
		$vars['return'] = $this->_current_url().$_SERVER['REQUEST_URI'];
		
		$base_url 	= substr($vars['return'], 0, strpos($vars['return'], '?'));
		$base_url 	= substr($base_url, 0, strrpos($base_url, '/'));
		$base_url 	= $base_url.'/'.$this->_url('callback');
		
		$vars['return'] = html_entity_decode($base_url);
		
		$request 	= $oauth->getRequestToken($vars['return']);
		
		$this->EE->functions->set_cookie('oauth_token', $request['oauth_token'], 1000);
		$this->EE->functions->set_cookie('oauth_token_secret', $request['oauth_token_secret'], 1000);
		$this->EE->functions->set_cookie('oauth_return', $vars['return'], 1000);
		
		/* Get temporary credentials. */
		$vars['redirect'] = $oauth->getAuthorizeURL($request);
				
		return $this->EE->load->view('redirect', $vars, TRUE);
	}
	
	public function callback()
	{
		$oauth_token 		= $this->EE->input->cookie('oauth_token');
		$oauth_token_secret = $this->EE->input->cookie('oauth_token_secret');
		$return 			= $this->EE->input->cookie('oauth_return');
		
		$this->EE->functions->set_cookie('oauth_token', '');
		$this->EE->functions->set_cookie('oauth_token_secret', '');
		$this->EE->functions->set_cookie('oauth_return', '');
		
		$vars = array();
		
		if($oauth_token && $oauth_token_secret)
		{
			$oauth 	= new TwitterOAuth($this->consumer_key, $this->consumer_secret, $oauth_token, $oauth_token_secret);
	
			$token_credentials = $oauth->getAccessToken($_GET['oauth_verifier']);
		
			$vars = array(
				'user'	=> array(
					'oauth_token'			=> $token_credentials['oauth_token'],
					'oauth_token_secret'	=> $token_credentials['oauth_token_secret'],
					'user_id'				=> $token_credentials['user_id'],
					'screen_name'			=> $token_credentials['screen_name']
				)
			);
			
			$vars['is_authorized'] = TRUE;
			
			if(!$this->EE->auto_tweet_model->is_user_authorized($token_credentials['user_id']))
				$vars['is_authorized'] = FALSE;
				
			$this->EE->auto_tweet_model->save_credentials($token_credentials);
		}
		
		return $this->EE->load->view('callback', $vars, TRUE);
	}
	
	public function delete_account()
	{
		$this->EE->auto_tweet_model->delete_account($this->EE->input->get('user_id'));
	}
	
	private function add_dynamic_fields($data)
	{
		$channels = array();
		
		foreach($data as $index => $channel)
		{
			$fields = $this->EE->auto_tweet_model->get_fields($channel->channel_id)->result();
			
			$channel->fields = array();
			
			foreach($fields as $field)
			{
				if($field->group_id == $channel->field_group)
				{
					$channel->fields[] = $field;
				}
			}
			
			$statuses = $this->EE->auto_tweet_model->get_statuses($channel->status_group);
			
			$channel->statuses = array();
			
			foreach($statuses->result() as $status)
			{
				if($status->group_id == $channel->status_group)
				{
					$channel->statuses[] = $status;
				}
			}
			
			$channels[$index] = $channel;	
		}
		
		return $channels;
	}
	
	private function _url($method = 'index')
	{
		return BASE . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=Auto_tweet' . AMP . 'method=' . $method;
	}
	
	private function _current_url($append = '', $value = '')
	{
		$url = (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['SERVER_NAME'] : 'http://'.$_SERVER['SERVER_NAME'];
		
		if(!empty($append))
			$url .= '?'.$append.'='.$value;
		
		return $url;
	}
	
}