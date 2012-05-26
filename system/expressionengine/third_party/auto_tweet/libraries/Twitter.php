<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @package		Twitter
 * @subpackage	Libraries
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		1.0
 * @build		20120313
 */
 
class Twitter {
	
	public $channels;
	public $url_length = 20;
	public $concatenation_characters = '...';
	public $settings; 
	
	function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->model('auto_tweet_model');
		
		$this->channels = $this->EE->auto_tweet_model->get_saved_channels()->result();
		
		log_message('debug', 'Auto_tweet class has been initialized.');
	}
	
	function update_status($entry_id)
	{
		log_message('debug', 'Attempting to send a tweet for entry_id: '.$entry_id);
		
		$settings 		= $this->EE->auto_tweet_model->get_settings();
		
		$this->settings = $settings;
		$entry 			= $this->EE->auto_tweet_model->get_entry($entry_id, array('*'))->row_array();
		
		foreach($this->channels as $channel)
		{	
			//$channel_info = $this->EE->auto_tweet_model->get_channel($channel->channel_id)->row();
				
			if($entry['channel_id'] == $channel->channel_id && $this->is_valid_status($entry['status'], $channel->statuses))
			{	
				log_message('debug', 'Channel \''.$channel->channel_id.'\' has been found with a valid status of \''.$entry['status'].'\'');
		
				$users = $this->EE->auto_tweet_model->get_users($channel->users)->result();
				$statuses = $this->build_message($channel, $entry);
      				
				foreach($users as $user)
				{	
					log_message('debug', 'Attempting to tweet on behalf of \''.$user->user_id.'\'');
		
					$twitter = new TwitterOAuth($settings['consumer_key'], $settings['consumer_secret'], $user->oauth_token, $user->oauth_token_secret);         
      					
      				foreach($statuses as $status)
      				{	
	      				$post = array(
	      					'status' => $status,
	      					'wrap_links' => $this->settings['shorten_url'] == 0 ? 'true' : 'false'
	      				);
	      				
	      				if(isset($entry['field_id_'.$channel->latitude_field_id]))
	      					$post['lat'] = $entry['field_id_'.$channel->latitude_field_id];
	      					
	      				if(isset($entry['field_id_'.$channel->longitude_field_id]))
	      					$post['long'] = $entry['field_id_'.$channel->longitude_field_id];
	      				
	      				log_message('debug', 'Sending status "'.$status.'" to Twitter.');
	      					      				
						/* -------------------------------------------
						/* 'auto_tweet_update_status' hook.
						/*  - Modify your post data right before the status is sent to Twitter
						/*  - Added Auto Tweet 1.0
						*/
							$edata = $this->EE->extensions->call('auto_tweet_update_status', $post, $user, $entry, $channel);			
							if($edata !== NULL) $post = $edata;
							
							if ($this->EE->extensions->end_script === TRUE) return;
						/*
						/* -------------------------------------------*/
	      				
	      				$response = $twitter->post("statuses/update", $post);
	      					      				
						/* -------------------------------------------
						/* 'auto_tweet_status_updated' hook.
						/*  - Modify your post data right before the status is sent to Twitter
						/*  - Added Auto Tweet 1.0
						*/
							$this->EE->extensions->call('auto_tweet_status_updated', $response, $user, $entry, $channel);			
							
							if ($this->EE->extensions->end_script === TRUE) return;
						/*
						/* -------------------------------------------*/
	      				
	      				if(isset($response->error))
	      					log_message('debug', 'Twitter responded with: '.$response->error);	      								else
	      					log_message('debug', 'User \''.$user->user_id.'\'\'s status has been successfully updated.');
					}
				}
			}
		}
		
		return;	
	}
		
	public function build_message($channel, $entry)
	{
		$message = $this->parse_message($channel->tweet_format, $entry);
		$url 	 = $this->parse_message($channel->url, $entry);
		
		if($this->settings['shorten_url'] == 1 && !empty($this->settings['bitly_username']) && !empty($this->settings['bitly_api_key']))
		{
			$this->EE->load->library('bitly', array(
				'bitly_login' 		=> $this->settings['bitly_username'],
				'bitly_apiKey' 		=> $this->settings['bitly_api_key'],
				'bitly_x_login' 	=> $this->settings['bitly_username'],
				'bitly_x_apiKey' 	=> $this->settings['bitly_api_key'],
				'bitly_format'		=> 'json',
				'bitly_domain'		=> 'bit.ly'
			));
			
			$url = $this->EE->bitly->shorten($url);
			
			$channel->url = $url;
		}
		
		if($channel->multiple_tweets)
		{
			$message = $this->split_to_chunks($message, NULL, $channel->hash_tags, $url);
		}
		else
		{
			$message = $this->shrink_message($message, NULL, $channel->hash_tags, $url);
		}
		
		/* -------------------------------------------
		/* 'auto_tweet_build_status' hook.
		/*  - Completely customize your status
		/*  - Added Auto Tweet 1.0
		*/
			$edata = $this->EE->extensions->call('auto_tweet_build_status', $message, $channel, $entry);
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $message;
	}
	
	public function shrink_message($text, $mention = '', $hash_tags = '', $url = '')
	{
		$concat			= $this->concatenation_characters;
		$mention_len 	= empty($mention) ? 0 : strlen($mention) + 1;
		$hash_len 		= empty($hash_tags) ? 0 : strlen(trim($hash_tags));
		$url_len		= empty($url) ? 0 : strlen($url) + 1;
		
		$total_length 	= 140 - $mention_len - $hash_len - $mention_len - $url_len - strlen($concat) - 1;
		
		if($total_length < strlen(trim($text)))
			$message = $mention . ' ' . substr($text, 0, $total_length) . $concat . ' ' . $url . ' ' . $hash_tags;
		else
			$message =  $mention . ' ' . $text . ' ' . $url . ' ' . $hash_tags;
			
		$message = trim($message);
			
		/* -------------------------------------------
		/* 'auto_tweet_shrink_status' hook.
		/*  - Change the way Auto Tweets shrinks your status
		/*  - Added Auto Tweet 1.0
		*/
			$edata = $this->EE->extensions->call('auto_tweet_shrink_status', $message, $text, $mention, $hash_tags, $url);
			
			if($edata != NULL) $message = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return array($message);
	}
	
	public function split_to_chunks($text, $mention = '', $hash_tags = '', $url)
	{
		$i 				= 0;
		$concat			= $this->concatenation_characters ;
		$mention_len = empty($mention) ? 0 : strlen($mention) + 1;
		$hash_len 	= empty($hash_tags) ? 0 : strlen(trim($hash_tags));
		$url_len		= empty($url) ? 0 : strlen($url) + 1;
		
		$total_length 	= 140 - $mention_len - $hash_len - strlen($mention) - $url_len - strlen($concat) - 1;		
		$text_array 	= explode(" ",$text);
		$messages		= array('');
		
		foreach ($text_array as $word)
		{			
			$length = strlen($messages[$i] . $word . ' ');
			
			if ($length <= $total_length)
			{
				$messages[$i] .= $this->build_chunk($text_array, $word, $mention, $hash_tags);
			}
			else
			{
				$i++;
				$messages[$i] = $this->build_chunk($text_array, $word, $mention, $hash_tags);
			}			
		}
		
		foreach($messages as $i => $message)
		{
			$messages[$i] = !empty($mention)   	? $mention  . ' ' . $message : $message;
			$messages[$i] = $i + 1 < count($messages) ? trim($messages[$i]) . $concat : $messages[$i];
			$messages[$i] = !empty($hash_tags) 	? $messages[$i]  . ' ' . $hash_tags : $messages[$i];
			$messages[$i] = !empty($url)   		? $messages[$i]  . ' ' . $url : $messages[$i];
		
			$messages[$i] = trim($messages[$i]);
		}

		/* -------------------------------------------
		/* 'auto_tweet_chunk_status' hook.
		/*  - Change the way Auto Tweets chunks your status
		/*  - Added Auto Tweet 1.0
		*/
			$edata = $this->EE->extensions->call('auto_tweet_chunk_status', $messages, $text, $mention, $hash_tags, $url);
			
			if($edata != NULL) $message = $edata;
			
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		return $messages;
	}
	
	public function is_url($word)
	{
		return preg_match("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", $word);
	}
	
	public function build_chunk($text_array, $word, $mention = '', $hash_tags = '')
	{
		if ($text_array[count($text_array)-1] != $word)
			$word = $word . ' ';
			
		return $word;
	}
	
	public function parse_message($message, $entry_data)
	{
		$this->EE->load->library('template');
		
		$vars = array($entry_data);
		$vars[0]['path'] = array(NULL, array('path_variable' => TRUE));
		
		return $this->EE->template->parse_variables($message, $vars);
	}
	
		
	public function is_valid_status($status, $channel_statuses)
	{
		$return = FALSE;
		
		if(!is_array($channel_statuses))
			$channel_statuses = explode('|', $channel_statuses);
		
		foreach($channel_statuses as $channel_status)
		{
			if($status == $channel_status)
				$return = TRUE;
		}
		
		return $return;
	}
}