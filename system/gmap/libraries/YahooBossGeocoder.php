<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'BaseClass.php';

if(!class_exists('OAuthConsumer'))
{
	require_once 'OAuth.php';
}

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
	
	protected $baseUrl = 'http://yboss.yahooapis.com/geo/placefinder';
	
	public function __construct($params = array())
	{
		parent::__construct($params);	
	}
	
	public function args()
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
		
		$return = array();
		
		foreach($array as $index => $value)
		{
			if($value && !empty($value))
			{
				$return[$index] = $value;
			}
		}
		
		return $return;
	}
	
	public function	geocode($location = FALSE)
	{
		$args = $this->args();
		
		$request = $this->authorize($args);
		
		$url = sprintf("%s?%s", $this->baseUrl, OAuthUtil::build_http_query($args));
		
		$ch = curl_init();
		$headers = array($request->to_header());
		curl_setopt($ch,CURLOPT_ENCODING , "gzip"); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		
		return curl_exec($ch);
	}
	
	public function json($location = FALSE)
	{
		$this->flags = 'J';
		
		return json_decode($this->geocode($location));
	}
	
	public function authorize($args = array(), $key = FALSE, $secret = FALSE)
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
		$request  = OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', $this->baseUrl, $args);
		$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
						
		return $request;
	}
}