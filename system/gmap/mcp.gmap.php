<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Google Maps for ExpressionEngine v3
 * 
 * @package		Google Maps for ExpressionEngine
 * @author		Justin Kimbrell
 * @copyright	Copyright (c) 2012, Objective HTML
 * @link 		http://www.objectivehtml.com/google-maps
 * @version		3.3.0
 * @build		20120522
 */

class Gmap_mcp {
	
	public $fields;
	public $fields_by_id;
	public $entry;
	public $settings;
	public $categories;
	
	function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->library('Google_maps');
		
		$this->EE->load->library('theme_loader', array(
			'module_name'	=> 'gmap'
		));
		
		$this->EE->load->driver('Interface_builder');
		$this->EE->load->driver('Channel_data');
		$this->EE->load->library('Data_import');
		$this->EE->load->model('data_import_model');
		$this->EE->load->library('gmap_import');
	}
	
	public function index()
	{	
		$this->EE->cp->set_right_nav(array(
			'Import Log' => $this->cp_url('import_log'),
			'Manage Schemas' => $this->cp_url('schemas'),
			'New Schema' => $this->cp_url('settings'), 
		));
		
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.js');
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.24/jquery-ui.min.js');
		
		$vars = array(
			'xid'      => XID_SECURE_HASH,
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_data_action'),
			'status_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'change_statuses'),
			'clear_pool_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'clear_pool'),
			'cron_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'cron_import_action').'&schema_id=X',			
			'settings' => $this->EE->data_import_model->get_settings(),
			'stats'    => $this->EE->data_import_model->get_stats(),
			'import_url' => $this->cp_url('import_pool'),
			'return'   => $this->cp_url('index', TRUE)
		);
		
		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		}
		else
		{
			$this->EE->view->cp_page_title = $this->EE->lang->line('gmap_module_name');
		}

		return $this->EE->load->view('csv_index', $vars, TRUE);
	}
	
	public function import_log_action()
	{
		$id 	= $this->EE->input->get_post('id');
		$status = $this->EE->input->get_post('status');
		
		$this->EE->data_import_model->clear_item($id, $status);
	}
	
	public function clear_log_action()
	{
		$this->EE->data_import_model->clear_log(FALSE, FALSE, TRUE);
		
		$this->EE->functions->redirect($this->cp_url('import_log'));
	}
	
	public function import_log()
	{
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Home' => $this->cp_url(),
			'Clear Log' => $this->cp_url('clear_log_action'),
			'Manage Schemas' => $this->cp_url('schemas'),
		));
		
		$vars = array(
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_log_action'),
			'base_url' => $this->edit_url(),
			'items' => $this->EE->data_import_model->get_log('open')
		);
		
		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		}
		else
		{
			$this->EE->view->cp_page_title = $this->EE->lang->line('gmap_module_name');
		}

		return $this->EE->load->view('import_log', $vars, TRUE);
	}
	
	public function clear_pool()
	{
		if(isset($this->EE->session->userdata['member_id']))
		{
			$this->EE->load->model('data_import_model');
			$this->EE->data_import_model->clear_pool();
		}
		
		$this->EE->functions->redirect($this->EE->input->post('return'));
	}
	
	public function change_statuses()
	{
		if(isset($this->EE->session->userdata['member_id']))
		{
			$schema = $this->EE->data_import_model->get_setting($this->EE->input->get_post('schema_id'));
			
			$this->EE->db->where('channel_id', $schema->channel);
			$this->EE->db->update('channel_titles', array(
				'status' => $this->EE->input->post('status', TRUE)
			));
		}
		
		$this->EE->functions->redirect($this->EE->input->post('return'));
	}
	
	public function schemas()
	{
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Import' => $this->cp_url(),
			'New Schema' => $this->cp_url('settings'), 
		));
		
		$vars = array(
			'settings'      => $this->EE->data_import_model->get_settings(),
			'edit_url'      => $this->cp_url('settings'),
			'delete_url'    => $this->cp_url('delete_schema_action'),
			'duplicate_url' => $this->cp_url('duplicate_schema_action')
		);
		
		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		}
		else
		{
			$this->EE->view->cp_page_title = $this->EE->lang->line('gmap_module_name');
		}

		return $this->EE->load->view('schemas', $vars, TRUE);		
	}
	
	public function import_pool()
	{
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Pool' => $this->cp_url(), 
			'Import Log' => $this->cp_url('import_log'),
		));
		
		$id = $this->EE->input->get('id');
		
		$settings = $this->EE->data_import_model->get_setting($id);
		
		$this->EE->theme_loader->javascript('https://maps.google.com/maps/api/js?sensor=true'.($this->EE->theme_loader->requirejs() ? '&callback=init' : NULL));
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.js');
		$this->EE->theme_loader->javascript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.24/jquery-ui.min.js');
		$this->EE->theme_loader->javascript('json2');
		$this->EE->theme_loader->javascript('gmap_import');
		
		$vars = array(
			'id' => $id,
			'total_items' => $this->EE->data_import_model->get_pools($id, 'pending')->num_rows(),
			'stats' => $this->EE->data_import_model->get_stats($id),
			'import_start_url' => $this->cp_url('import_start_action', TRUE),
			'import_item_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_item_action'),
			'import_check_url' => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_check_existing_action'),
		);
		
		$this->EE->cp->add_to_head('
		<script type="text/javascript">
			
			var id         = '.$vars['id'].';
			var totalItems = '.$vars['total_items'].';
			var importItemURL = \''.$vars['import_item_url'].'\';
			var importCheckURL = \''.$vars['import_check_url'].'\';
			var init;
			
		</script>');
		
		$this->EE->theme_loader->output('
				
		init = function() {
			$bar = $(\'.progress-bar\');
			$bar.progressbar({value: 0});
			
			$(\'.start .submit\').click(function() {
				
				var $t = $(this);
				
				if($t.html() == \'Start Import\') {
					stop = false;
					$t.html(\'Stop Import\');
					
					$.get(\''.$vars['import_start_url'].'\',
						{
							id: id
						},
						function(data) {
							
							itemsRemaining = data.items_in_pool;
							
							$(\'.items\').html(itemsRemaining);
							$(\'.success\').html(data.total_entries_imported);
							$(\'.last-ran\').html(data.importer_last_ran);
							$(\'.total-runs\').html(data.importer_total_runs);
							$(\'.progress-bar, .geocoding\').show();
							
							startTimer();
							geocode(lastIndex);
						}
					);
				}
				else {
					clearInterval(timer);
	        
					$t.html(\'Start Import\');
					stop = true;
				}
				
				return false;
				
			});
		}; '. (!$this->EE->theme_loader->requirejs() ? 'init();' : NULL));
		
		/*
		foreach($vars['items'] as $index => $item)
		{
			$item->data = json_decode($item->data);	
		}*/
		
		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		}
		else
		{
			$this->EE->view->cp_page_title = $this->EE->lang->line('gmap_module_name');
		}

		return $this->EE->load->view('import_pool', $vars, TRUE);
	}
	
	public function import_check_existing_action()
	{
		return $this->EE->gmap_import->check_existing_entries($this->EE->input->get_post('schema_id'));
	}
	
	public function import_csv_ft_action()
	{
		$entries    = $this->EE->gmap_import->load_file($_FILES["file"]['tmp_name']);
		
		$response = array(
			'columns' => array_keys($entries[0]),
			'rows'    => $entries
		);
		
		$this->json($response);
	}
	
	public function import_start_action()
	{
		$id = $this->EE->input->get_post('id');
		
		$start_data = $this->EE->data_import_model->start_import($id);
		$start_data->importer_last_ran = date('Y-m-d h:i A', $start_data->importer_last_ran);
		
		return $this->json($start_data);		
	}
	
	public function cron_import_action()
	{
		$schema_id = $this->EE->input->get_post('schema_id');
		
		set_time_limit(0);
		
		$this->EE->gmap_import->threshold = config_item('gmap_import_threshold');	
		
		$this->EE->gmap_import->import_pool($schema_id);
		
		exit();	
	}
	
	public function import_item_action()
	{
		$schema_id      = $this->EE->input->get_post('schema_id');
		$valid_address  = $this->EE->input->get_post('valid_address') == 'true' ? TRUE : FALSE;
		$markers        = $this->EE->input->get_post('markers');
		$existing_entry = $this->EE->input->get_post('existing_entry') == 'true' ? TRUE : FALSE;
		$status			= $this->EE->input->get_post('status');
		
		return $this->EE->gmap_import->import_item($schema_id, $valid_address, $markers, $existing_entry, $status);
	}
	
	public function import_data_action()
	{		
		$this->EE->gmap_import->import_from_csv($_FILES["file"]['tmp_name'], $this->EE->input->get_post('id'));
		
		$this->EE->functions->redirect($this->EE->input->post('return'));
	}
	
	public function delete_schema_action()
	{
		$id = $this->EE->input->get_post('id');
		
		$this->EE->data_import_model->delete_schema($id);
		
		$this->EE->functions->redirect($this->cp_url('schemas'));	
	}
		
	public function duplicate_schema_action()
	{
		$id = $this->EE->input->get_post('id');
		
		$this->EE->data_import_model->duplicate_schema($id);
		
		$this->EE->functions->redirect($this->cp_url('schemas'));
	}
		
	public function settings()
	{	
		$fields = $this->EE->channel_data->get_fields(array(
			'select' => 'field_id, group_id, field_name, field_label, field_type',
			'where' => array(
				'site_id' => config_item('site_id')	
			)
		));
		
		$fields_by_group = array();
		
		foreach($fields->result() as $index => $field)
		{
			if(!isset($fields_by_group[$field->group_id]))
			{
				$fields_by_group[$field->group_id] = array();
			}
			
			$fields_by_group[$field->group_id][] = $field;
		}
		
		$channels = $this->EE->channel_data->get_channels(array(
			'select' => 'channel_id, field_group',
			'where' => array(
				'site_id' => config_item('site_id')	
			)
		))->result();
		
		$channels = $this->EE->channel_data->utility->reindex($channels, 'channel_id');
		
		$this->EE->theme_loader->require_js = FALSE;
		$this->EE->theme_loader->output('var ChannelData = '.json_encode(array(
			'channels' => $channels,
			'fields'   => $fields_by_group,
			'isNew'    => !$this->EE->input->get('id') ? TRUE : FALSE
		)));
		
		
		$this->EE->theme_loader->output('
			$(document).ready(function() {
				var $channel = $(\'select[name="channel"]\');
				var $map     = $(\'select[name="gmap_field"], select[name="lat_field"], select[name="lng_field"]\');
				var $table   = $(\'#ib-matrix-channel_fields\');
				var $addRow  = $table.find(\'.ib-add-row\');
				var init     = false;
				var curVal   = false;
				var curObj   = false;
				var initTbl  = false;
				
				if(ChannelData.isNew) {
					$channel.change(function() {
						var value   = $(this).val();
						var group   = ChannelData.channels[value];
						var options = [\'<option value="">--</option>\'];
						
						if(init || initTbl) {
							$table.find("tbody").html("");
						}
						
						if(group && ChannelData.fields[group.field_group]) {
							$.each(ChannelData.fields[group.field_group], function(i, field) {
								if(init || initTbl) {
									$addRow.click();
									$table.find("tbody tr:last-child td:nth-child(2) input").val(field.field_name);
								}
								
								var selected = field.field_type == "gmap" || (field.field_type != "gmap" && field.field_id == curVal) ? \'selected="selected"\' : "";
								
								options.push(\'<option value="\'+field.field_id+\'" \'+selected+\'>\'+field.field_label+\'</option>\');
							});
							
							options = options.join(\'\');
							
							if(!init) {
								console.log(options);
								curObj.html(options);
							}
							else {
								$map.html(options);
							}
						}
					});
					
					initTbl = $table.find("tbody tr").length == 0 ? true : false;
					
					$map.each(function() {
						curObj = $(this);
						curVal = curObj.val();
						
						$channel.change();
					});
					
					init = true;
					curVal = false;
					curObj = false;
				}
			});
		');
		
		$this->EE->cp->set_right_nav(array(
			'&larr; Back to Schemas' => $this->cp_url('schemas'), 
		));
		
		$fields = array(
			'settings' => array(
				'title' => 'Import Settings',
				'attributes'  => array(
					'class' => 'mainTable padTable',
					'border' => 0,
					'cellpadding' => 0,
					'cellspacing' => 0
				),
				'wrapper' => 'div',
				'fields'  => array(
					'id' => array(
						'label'       => 'ID',
						'description' => 'Since you can save multiple variations of settings to import, your must give a unique identifier for these settings.',
						'type'        => 'input'
					),
					'delimeter' => array(
						'label'       => 'Delimeter',
						'description' => 'The delimeting character used in the .csv you will be uploading. If left blank, the default value will be used (,).',
						'type'        => 'input'
					),
					'eol' => array(
						'label'       => 'EOL',
						'description' => 'The EOL character used in the .csv you will be uploading. If left blank, the default value will be used (\n\r).',
						'type'        => 'input'
					),
					'channel' => array(
						'label'       => 'Channel',
						'description' => 'This is the channel where your data will be imported.',
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
							'options' => 'FIELD_DROPDOWN'
						)
					),
					'lat_field' => array(
						'label'       => 'Latitude Field',
						'description' => 'This is the name of latitude field to store your data.',
						'type'        => 'select',
						'settings' => array(
							'options' => 'FIELD_DROPDOWN'
						)
					),
					'lng_field' => array(
						'label'       => 'Longitude Field',
						'description' => 'This is the name of longitude field to store your data.',
						'type'        => 'select',
						'settings' => array(
							'options' => 'FIELD_DROPDOWN'
						)
					),
					'geocode_fields' => array(
						'label'       => 'Geocode Fields',
						'description' => 'This field is used to define the channel fields and column(s) in the .CSV you wish to use for geocoding. The fields are used to prevent existing entries from getting geocoded unnecessarily. The .csv fields are what are actually used to create the address string.',
						'type'	=> 'matrix',
						'settings' => array(
							'columns' => array(
								1 => array(
									'name'  => 'field_name',
									'title' => 'Channel Field Name'
								),
								0 => array(
									'name'  => 'column_name',
									'title' => 'CSV Column Name'
								),
							),
							'attributes' => array(
								'class'       => 'mainTable padTable',
								'border'      => 0,
								'cellpadding' => 0,
								'cellspacing' => 0
							)
						)
					),
					'author_id' => array(
						'label'       => 'Author ID',
						'description' => 'This is the author id that will be assigned to the imported entries.',
						'type'        => 'input'
					),
					/*'author_id_column' => array(
						'label'       => 'Author ID Column',
						'description' => 'If your .CSV has a column that stores author_id\'s, you can define that column name here. If the column doesn\'t exist, or have a value, the Author ID setting will be used instead.',
						'type'        => 'input'
					),*/
					'username_column' => array(
						'label'       => 'Username Column',
						'description' => 'If your .CSV has a column that stores usernames, you can define that column name here. If the column doesn\'t exist, or have a value, the Author ID setting will be used instead.',
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
					'group_by' => array(
						'label'       => 'Group By',
						'description' => 'This is the unique identifier for each row that will group your entries together. If there are duplicate rows, define the column in the .csv that stores the ID.',
						'type'        => 'input'
					),
					'duplicate_fields' => array(
						'label' => 'Duplicate Data',
						'description' => 'Defines fields and columns used to check for duplicate data. If multiple fields are defined, they must all match an entry to trigger an update vs. creating a new record.',
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
					'status' => array(
						'label'       => 'Status',
						'description' => 'This is the status that is assigned to each entry that is imported.',
						'type'        => 'input'
					),
					'force_status_update' => array(
						'label'       => 'Force Status Update',
						'description' => 'By default, the status of existing entries will not change. Set this option to "yes" if you want your existing entries to make the status to match your schema.',
						'type'        => 'select',
						'settings' => array(
							'options' => array(
								'false' => 'No',
								'true'  => 'Yes',
							)
						)
					),
					'title' => array(
						'label'       => 'Title',
						'description' => 'This is the title assigned to each entry. It can be a combination of channel fields, or a static string.',
						'type'        => 'input'
					),
					'title_column' => array(
						'label'       => 'Title Column',
						'description' => 'If your .CSV has a column that stores the entry title, you can define that column name here. If the column doesn\'t exist, or have a value, the Title setting will be used instead.',
						'type'        => 'input'
					),
					'title_prefix' => array(
						'label'		  => 'Title Prefix',
						'description' => 'This is the prefix that gets prepended to the title if the title contains only numbers.',		
					),
					'category_column' => array(
						'label'       => 'Category Column(s)',
						'description' => 'This is the name of the column(s) that stores the category. You can have a data file with categories stored in multiple columns, or just one.',
						'type'        => 'matrix',
						'settings' => array(
							'columns' => array(
								0  => array(
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
					'category_boolean_value' => array(
						'label'       => 'Category Boolean Value for <i>true</i>',
						'description' => 'If your catagories are stored in multiple columns, with the columns header being a category name, chances are the values are stored as boolean. So if "X" represents <i>true</i> what is the value of "X"? Leave the field blank if it does not apply.',
						'type'        => 'input'
					),
					'category_column_type' => array(
						'label'       => 'Category Column Type',
						'description' => 'This is the type of data stored in the category column(s).',
						'type'        => 'select',
						'settings' => array(
							'options' => array(
								'cat_name'      => 'Category Name',
								'cat_url_title' => 'Category URL Title',
								'cat_id'        => 'Category ID',
							)
						)
					),
					'create_category' => array(
						'label'       => 'Create Category',
						'description' => 'Creates a category if one doesn\'t exist based on the category column type. Be sure your category is stored as a Category Name for the best results.',
						'type'        => 'select',
						'settings' => array(
							'options' => array(
								'false' => 'No',
								'true'  => 'Yes',
							)
						)
					),
					'category_group_id' => array(
						'label'       => 'Category Group ID',
						'description' => 'If creating a category, you need to define a group_id to which it will be assigned.',
						'type'        => 'input'
					),
				)
			)
		);
		
		
		$id   = $this->EE->input->get('id');
		
		if($id)
		{
			$data = (array) $this->EE->data_import_model->get_setting($id);
		}
		else
		{
			$data = array();
		}
		
		$this->EE->interface_builder->data = $data;
		$this->EE->interface_builder->add_fieldsets($fields);

		$vars = array(
			'xid'      => XID_SECURE_HASH,
			'return'   => $this->cp_url('schemas'),
			'header'   => $this->EE->input->get('id') ? 'Edit' : 'New',
			'action'   => $this->EE->google_maps->base_url().'?ACT='.$this->EE->channel_data->get_action_id('Gmap_mcp', 'import_csv_save_settings_action'),
			'interface_builder' => $this->EE->theme_loader->theme_url().'/gmap/javascript/InterfaceBuilder.js',
			'settings' => $this->EE->interface_builder->fieldsets(),
			'schema_id' => $this->EE->input->get('id') ? $this->EE->input->get('id') : NULL
		);
		
		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('gmap_module_name'));
		}
		else
		{
			$this->EE->view->cp_page_title = $this->EE->lang->line('gmap_module_name');
		}

		return $this->EE->load->view('csv_import', $vars, TRUE);
	}
	
	public function import_csv_save_settings_action()
	{		
		$return = $this->EE->input->post('return');
		
		unset($_POST['return']);
		
		$channel_fields = array();
		
		if(isset($_POST['channel_fields']))
		{
			foreach($_POST['channel_fields'] as $field)
			{
				$channel_fields[] = $field;	
			}
		}
		
		$_POST['channel_fields'] = $channel_fields;
		
		$this->EE->data_import_model->save_settings($this->EE->input->post('schema_id'), json_encode($_POST));
			
		$this->EE->functions->redirect($return);
	}
	
	private function update()
	{
		$update = new Gmap_upd();
		$update->update();
	}
	
	private function cp_url($method = 'index', $useAmp = FALSE)
	{
		$amp  = !$useAmp ? AMP : '&';

		$file = substr(BASE, 0, strpos(BASE, '?'));
		$file = str_replace($file, '', $_SERVER['PHP_SELF']) . BASE;

		$url  = $file .$amp. 'C=addons_modules' .$amp . 'M=show_module_cp' . $amp . 'module=gmap' . $amp . 'method=' . $method;

		return str_replace(AMP, $amp, $url);
	}
	
	private function edit_url($method = 'index', $useAmp = FALSE)
	{
		$amp  = !$useAmp ? AMP : '&';

		$file = substr(BASE, 0, strpos(BASE, '?'));
		$file = str_replace($file, '', $_SERVER['PHP_SELF']) . BASE;

		$url  = $file .$amp. 'C=content_publish&M=entry_form';

		return str_replace(AMP, $amp, $url);
	}
	
	private function json($data)
	{
		header('Content-type: application/json');
		echo json_encode($data);
		exit();
	}
}
// END CLASS

/* End of file mcp.gmap.php */
/* Location: ./system/expressionengine/third_party/modules/gmap/mcp.gmap.php */