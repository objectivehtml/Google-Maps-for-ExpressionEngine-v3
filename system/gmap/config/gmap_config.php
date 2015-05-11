<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* 
 *	Version and Build
*/

$config['gmap_version']	= '3.3.9';
$config['gmap_build']	= config_item('gmap_build') ? config_item('gmap_build') : '20150511';

/* 
 *	Protect JavaScript
 *	
 *	By default EE protects the JavaScript from the template parser.
 *	By settings this variable to 'n', the parser will include
 *	JavaScript.
 */

$config['protect_javascript'] = config_item('gmap_protect_javascript') ? config_item('gmap_protect_javascript') : 'n';

/* 
 *	API Service Cache Rate
 *
 *  Default cache length is set to 1 week (60 * 60 * 24 * 7)
 */

$config['gmap_cache_length'] = config_item('gmap_cache_length') ? config_item('gmap_cache_length') : 604800;


/* 
 *	Force HTTP (instead of HTTPS)
 *
 *  Some server environments have issues with HTTPS and cURL.
 *  So far only environment found affected has been local WAMP
 *  installations.
 */
 
 $config['gmap_force_http'] = config_item('gmap_force_http') ? config_item('gmap_force_http') : FALSE;
 

/* 
 *	Default Geocoding Region
 *
 *  If FALSE, the default region will be used (United States)
 */
 
 $config['gmap_default_geocoding_region'] = config_item('gmap_default_geocoding_region') ? config_item('gmap_default_geocoding_region') : 'us';

/* 
 *	Default Geocoding language
 *
 *  If FALSE, the default language will be used (English)
 */
 
 $config['gmap_default_geocoding_language'] = config_item('gmap_default_geocoding_language') ? config_item('gmap_default_geocoding_language') : 'en';


/* 
 *	Static Maps
 *
 *  Static Maps are cached for a set period of time to reduce
 *  the overhead on the Google Maps API. To generate static
 *	maps you must set define the directory that will store
 *  the cached maps.
 */
 
 
 $config['gmap_static_map_path'] = config_item('gmap_static_map_path') ? config_item('gmap_static_map_path') : false;
 
 $config['gmap_static_map_url']  = config_item('gmap_static_map_url') ? config_item('gmap_static_map_url') : false;
 
 
/* 
 *	Google API Proxy URLs
 *
 *  In the event that you have a shared IP and run into Google
 *  API limits, you can define a proxy URL that basically defers
 *  these requests to another server. This should only be used
 *  with sites will a small traffic load, otherwise invest in a
 *  unique public IP address.
 * 
 *  Geocoder
 *	https://www.objectivehtml.com/api/v1/geocode
 *
 *  Directions
 *	https://www.objectivehtml.com/api/v1/directions
 *
 *  By using Objective HTML's proxy, you are agreeing to the terms
 *  of service outlined on the page below.
 *  http://www.objectivehtml.com/terms
 *
 *  By default, there values should be set to FALSE.
 */

$config['gmap_geocoder_proxy_url']   = config_item('gmap_geocoder_proxy_url') ? config_item('gmap_geocoder_proxy_url') : FALSE;

$config['gmap_directions_proxy_url'] = config_item('gmap_directions_proxy_url') ? config_item('gmap_directions_proxy_url') : FALSE;


/* 
 *	Import Threshold
 *
 *  The total number of entries a CRON request can import at one time.
 *  Adjust this for maximum results on your server.
 */

$config['gmap_import_threshold'] = config_item('gmap_import_threshold') ? config_item('gmap_import_threshold') : 500;

$config['gmap_import_memory'] = config_item('gmap_import_memory') ? config_item('gmap_import_memory') : '1028M';

$config['gmap_import_max_file_size'] = config_item('gmap_import_max_file_size') ? config_item('gmap_import_max_file_size') : '200M';

$config['gmap_import_skip_rows'] = config_item('gmap_import_skip_rows') ? config_item('gmap_import_skip_rows') : 0;

/* 
 *	Use Yahoo Boss Geocoder for Importing
 *
 *  You can use Yahoo's BOSS Placefinder geocoder for importing to comply
 *  to Google's TOS or to get an more affordable geocoder. Simply input the
 *  client key and secret (oAuth tokens) and the Appid given to each BOSS 
 *  account.
 */

$config['gmap_import_use_yahoo'] = config_item('gmap_import_use_yahoo') ? config_item('gmap_import_use_yahoo') : FALSE;

$config['gmap_import_client_key'] = config_item('gmap_import_client_key') ? config_item('gmap_import_client_key') : '';

$config['gmap_import_client_secret'] = config_item('gmap_import_client_secret') ? config_item('gmap_import_client_secret') : '';

$config['gmap_import_appid'] = config_item('gmap_import_appid') ? config_item('gmap_import_appid') : '';

/* 
 *	Define the ee() function introduced in EE 2.6
 */

if ( ! function_exists('ee'))
{
    function ee()
    {
        static $EE;
        if ( ! $EE) $EE = get_instance();
        return $EE;
    }
} 