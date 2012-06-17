<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.0.183
 * @build		20120526
 */

class Gmap_mcp {

	function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->library('Google_maps');
		$this->EE->load->driver('interface_builder');
		$this->EE->load->driver('channel_data');
	}
	
	function index()
	{
		$fields = array(
			'settings' => array(
				'title' => 'Upload Settings',
				'attributes'  => array(
					'class' => 'mainTable padTable',
					'border' => 0,
					'cellpadding' => 0,
					'cellspacing' => 0
				),
				'wrapper' => 'div',
				'fields'  => array(
					'channel' => array(
						'label'       => 'Channel',
						'description' => 'This is the channel where your data will be uploaded.',
						'type'        => 'select',
						'settings' => array(
							'options' => 'CHANNEL_DROPDOWN'
						)
					),
					'gmap_field' => array(
						'label'       => 'Map Field',
						'description' => 'This is the name of Google Maps for ExpressionEngine fieldtype to store your data.',
						'type'        => 'select',
						'settings' => array(
							'options' => 'CHANNEL_DROPDOWN'
						)
					),
					'geocode_fields' => array(
						'label'       => 'Geocode Fields',
						'description' => 'Use standard ExpressionEngine tags with custom fields to create the string that will be sent to the geocoder.<br>Example: {your_address_field} {your_city_field} {your_state_field} {your_zip_field}',
						'type'        => 'input'
					),
					'author_id' => array(
						'label'       => 'Author ID',
						'description' => 'This is the author id that will be assigned to the uploaded entries.',
						'type'        => 'input'
					),
					'channel_fields' => array(
						'label' => 'Channel Fields',
						'description' => 'Use this field to pair up your channel fields with the columns in the .csv file. Be sure to make sure the names are an exact match.',
						'id'    => 'field_map_fields',
						'type'	=> 'matrix',
						'settings' => array(
							'columns' => array(
								0 => array(
									'name'  => 'field_name',
									'title' => 'Channel Field Name'
								),
								1 => array(
									'name'  => 'column_name',
									'title' => 'CSV Column Name'
								)
							),
							'attributes' => array(
								'class'       => 'mainTable padTable',
								'border'      => 0,
								'cellpadding' => 0,
								'cellspacing' => 0
							)
						)
					),
					'category_column' => array(
						'label'       => 'Category Column',
						'description' => 'This is the name of the column that stores the category.',
						'type'        => 'input'
					),
					'category_column_type' => array(
						'label'       => 'Category Column Type',
						'description' => 'This is the type of data stored in the cateogry column.',
						'type'        => 'select',
						'settings' => array(
							'options' => array(
								'cat_name'      => 'Category Name',
								'cat_url_title' => 'Category URL Title',
								'cat_id'        => 'Category ID',
							)
						)
					),
				)
			)
		);
		
		$data = array();
		
		$this->EE->interface_builder->data = $data;
		$this->EE->interface_builder->add_fieldsets($fields);

		$vars = array(
			'return'   => $this->EE->google_maps->current_url(),
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_csv_action'),
			'settings' => $this->EE->interface_builder->fieldsets()
		);
		
		return $this->EE->load->view('csv_import', $vars, TRUE);
	}
	
	public function import_csv_action()
	{
		echo 'test';exit();	
	}
	
	private function update()
	{
		$update = new Gmap_upd();
		$update->update();
	}
}
// END CLASS

/* End of file mcp.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/mcp.gmap.php */