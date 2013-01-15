<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'BaseClass.php';
require_once 'OAuth.php';

class YahooBossGeocoder extends BaseClass {
	
	protected $location;
	
	protected $appid;
	
	protected $consumerSecret;
	
	protected $consumerKey;
	
	protected $locale;
	
	protected $start;
	
	protected $count;
	
	protected $offset;
	
	protected $flags;
	
	protected $gflags;
	
	protected $baseUrl = 'http://where.yahooapis.com/geocode';
	
	public function __construct($params = array())
	{
		parent::__construct($params);	
	}
	
	public function url()
	{
		$array = array(
			'appid'  => $this->appid,
			'locale' => $this->locale,
			'start'  => $this->start,
			'offset' => $this->offset,
			'count'  => $this->count,
			'flags'  => $this->flags,
			'gflags' => $this->gflags
		);
		
		if(is_array($this->location))
		{
			$array = array_merge($array, $this->location);
		}
		else if(is_string($this->location))
		{
			$array['q'] = $this->location;
		}
		
		return $this->baseUrl . '?' . http_build_query($array);
	}
	
	public function	geocode($location = FALSE)
	{
		if($location)
		{
			$this->location = $location;
		}
		
		$url = $this->url();
		
		$oAuthRequest = $this->authorize();
		
		$headers = array($oAuthRequest->to_header());
		
		$ch = curl_init();
		$headers = array();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$rsp = curl_exec($ch);
		
		return $rsp;
	}
	
	public function json($location = FALSE)
	{
		$this->flags = 'J';
		
		return json_decode($this->geocode($location));
	}
	
	public function authorize($key = FALSE, $secret = FALSE)
	{
		if($key)
		{
			$this->consumerKey = $key;
		}
		
		if($secret)
		{
			$this->consumerSecret = $secret;
		}
					 
		$consumer = new OAuthConsumer($this->consumerKey, $this->consumerSecret);
		$request = OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', $this->baseUrl);
		
		$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
		
		return $request;
	}
}