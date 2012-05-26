<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class OAuthToken {

	// access tokens and request tokens
	public $key;
	public $secret;
	
	/**
	* key = the token
	* secret = the token secret
	*/
	public function __construct($key, $secret) {
		if(!is_array($key))
		{
			$this->key = $key;
			$this->secret = $secret;
		}
		else
		{
			$this->key = isset($key['key']) ? $key['key'] : '';
			$this->secret = isset($key['secret']) ? $key['secret'] : '';
		}
	}
	
	/**
	* generates the basic string serialization of a token that a server
	* would respond to request_token and access_token calls with
	*/
	public function to_string() {
		return "oauth_token=" .
		       OAuthUtil::urlencode_rfc3986($this->key) .
		       "&oauth_token_secret=" .
		       OAuthUtil::urlencode_rfc3986($this->secret);
	}
	
	public function __toString() {
		return $this->to_string();
	}
}