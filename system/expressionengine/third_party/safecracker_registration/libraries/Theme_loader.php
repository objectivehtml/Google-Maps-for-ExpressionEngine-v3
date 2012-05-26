<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Theme Loader
 *
 * A helper class that allows developers to easily add CSS and JS 
 * packages from the associating third party theme directory.
 *
 * @package		Theme Loader
 * @subpackage	Libraries
 * @category	Library
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2011, Justin Kimbrell
 * @link 		http://www.objectivehtml.com/libraries/channel_data
 * @version		1.0 
 * @build		2011117
 */

if(!class_exists('Theme_loader'))
{
	class Theme_loader {
		
		public $module_name;
		public $url_format;
		
		public function __construct($data = array())
		{
			$this->EE =& get_instance();
			
			if(isset($data['module_name']))
				$this->module_name = $data['module_name'];
	
			/* Url Validation */
			$this->url_format = 

			'/^(https?):\/\/'.                                         // protocol
			'(([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+'.         // username
			'(:([a-z0-9$_\.\+!\*\'\(\),;\?&=-]|%[0-9a-f]{2})+)?'.      // password
			'@)?(?#'.                                                  // auth requires @
			')((([a-z0-9][a-z0-9-]*[a-z0-9]\.)*'.                      // domain segments AND
			'[a-z][a-z0-9-]*[a-z0-9]'.                                 // top level domain  OR
			'|((\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])\.){3}'.
			'(\d|[1-9]\d|1\d{2}|2[0-4][0-9]|25[0-5])'.                 // IP address
			')(:\d+)?'.                                                // port
			')(((\/+([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)*'. // path
			'(\?([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)'.      // query string
			'?)?)?'.                                                   // path and query string optional
			'(#([a-z0-9$_\.\+!\*\'\(\),;:@&=-]|%[0-9a-f]{2})*)?'.      // fragment
			'$/i';
		}
		
		/**
		 * Theme URL
		 *
		 * A helper method to easily return the current theme path
		 *
		 * @access	public
		 * @return	string
		 */
		 
		public function theme_path()
		{
			return $this->EE->config->item('theme_folder_path');
		}
		
		/**
		 * Theme URL
		 *
		 * A helper method to easily return the current theme url
		 *
		 * @access	public
		 * @return	string
		 */
				
		public function theme_url()
		{
			return $this->EE->config->item('theme_folder_url');
		}	
		
		/**
		 * JavaScript
		 *
		 * Adds an external JavaScript to the header
		 *
		 * @access	public
		 * @param	string	A valid file name
		 * @return	void
		 */
		
		
		public function javascript($file)
		{
			$file = str_replace('js', '', $file);
			$file = $this->_prep_url('javascript', $file, '.js');
			
			$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$file.'"></script>');
		}
		
		/**
		 * CSS
		 *
		 * Adds a CSS link tag to the header
		 *
		 * @access	public
		 * @param	string	A valid file name
		 * @return	void
		 */
		
		public function css($file)
		{	
			$file = str_replace('.css', '', $file);
			$file = $this->_prep_url('css', $file, '.css');
			
			$this->EE->cp->add_to_head('<link type="text/css" href="'.$file.'" rel="stylesheet" media="screen" />');
		}
		
		/**
		 * Prep URL
		 *
		 * Formats a directory, file name, and extension into a properly
		 * formatted URL.
		 *
		 * @access	public
		 * @param	string	A valid directory name
		 * @param	string	A valid file name
		 * @param	string	A valid file extension
		 * @return	string
		 */
		
		
		private function _prep_url($directory, $file, $ext)
		{
			if(!$this->is_valid_url($file))
			{
				$file 	= str_replace('.js', '', $file);
				$file 	= $this->theme_url() . 'third_party/' . $this->module_name . '/' . $directory . '/' . $file . $ext;
			}
			
			return $file;	
		}
		
		/**
		 * Verify the syntax of the given URL. 
		 * 
		 * @access public
		 * @param $url The URL to verify.
		 * @return boolean
		 */
		 
		private function is_valid_url($url)
		{
			return $this->str_starts_with(strtolower($url), 'http://localhost') ? TRUE : preg_match($this->url_format, $url);
		}
	
		/**
		 * String starts with something
		 * 
		 * This function will return true only if input string starts with
		 * niddle
		 * 
		 * @param string $string Input string
		 * @param string $niddle Needle string
		 * @return boolean
		 */
		 
		private function str_starts_with($string, $niddle) {
		      return substr($string, 0, strlen($niddle)) == $niddle;
		}
	
		
	}
}