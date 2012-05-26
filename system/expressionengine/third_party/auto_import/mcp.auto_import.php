<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Auto Import
 * 
 * @package		Auto Import
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com
 * @version		0.1.0
 * @build		20120516
 */

require 'config/auto_import_config.php';

if(!defined('AUTO_IMPORT_VERSION'))
{	
	define('AUTO_IMPORT_VERSION', $config['AUTO_IMPORT_VERSION']);
}

class Auto_import_mcp {
	
	public $themes;

	public function __construct()
	{
		$this->EE =& get_instance();

		$this->EE->load->library('auto_import_lib');
		$this->EE->load->model('auto_import_model');

		$this->settings = $this->EE->auto_import_model->get_settings();
	}

	public function index()
	{
		$this->EE->load->config('auto_import_config');

		$data = config_item('auto_import_upload_fields');

		$this->EE->interface_builder->data = $this->settings;
		$this->EE->interface_builder->add_fieldsets($data);

		$vars = array(
			'action'   => $this->current_url('ACT', $this->EE->channel_data->get_action_id('Auto_import_mcp', 'save_settings')),
			'import_url'   => $this->current_url('ACT', $this->EE->channel_data->get_action_id('Auto_import_mcp', 'import')),
			'return'  => $this->cp_url(),
			'settings' => $this->EE->interface_builder->fieldsets()
		);

		return $this->EE->load->view('settings', $vars, TRUE);
	}

	public function import()
	{
		$vendors        = $this->EE->auto_import_lib->load_file($this->settings['vendors_path']);

		$this->EE->auto_import_model->add_vendors($vendors);
		
		$categories     = $this->EE->auto_import_lib->load_file($this->settings['categories_path']);

		$this->EE->auto_import_model->add_categories($categories);

		$sub_categories = $this->EE->auto_import_lib->load_file($this->settings['sub_categories_path']);
		
		$this->EE->auto_import_model->add_sub_categories($sub_categories);

		$products       = $this->EE->auto_import_lib->load_file($this->settings['products_path']);

		$this->EE->auto_import_model->add_products($products);
	}

	public function save_settings()
	{
		foreach($_POST as $key => $value)
		{
			$post = $this->EE->input->post($key);
			$data[$key] = $post !== FALSE ? $post : NULL;
		}

		$this->EE->auto_import_model->save_settings($data);
		
		$this->EE->functions->redirect($this->EE->input->post('return'));
	}
	
	private function cp_url($method = 'index', $useAmp = FALSE)
	{
		$amp  = !$useAmp ? AMP : '&';

		$file = substr(BASE, 0, strpos(BASE, '?'));
		$file = str_replace($file, '', $_SERVER['PHP_SELF']) . BASE;

		$url  = $file .$amp. '&C=addons_modules' .$amp . 'M=show_module_cp' . $amp . 'module=auto_import' . $amp . 'method=' . $method;

		return 'http'.(!empty($_SERVER['HTTPS']) ? 's' : null).'://'. $_SERVER['HTTP_HOST'] . str_replace(AMP, $amp, $url);
	}
	
	private function current_url($append = '', $value = '')
	{
		$url = (!empty($_SERVER['HTTPS'])) ? 'https://'.$_SERVER['SERVER_NAME'] : 'http://'.$_SERVER['SERVER_NAME'];
		
		if(!empty($append))
			$url .= '?'.$append.'='.$value;
		
		return $url;
	}
	
}