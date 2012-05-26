<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Field_editor_mcp
{
	protected $settings = array(
		'global_prefix' => '',
		'group_prefixes' => array(),
	);
	
	protected $fieldtype_row_js_callbacks = array(
		'nsm_publish_hints' => 'function(row, settings){
				var instructions = row.find(".field_instructions");
				if (settings.field_publishing_hints !== undefined) {
					instructions.val(settings.field_publishing_hints);
				}
				instructions.change(function(){
					if ($.inArray(row.data("id"), fieldEditor.updatedSettings) === -1) {
						fieldEditor.updatedSettings.push(row.data("id"));
					}
				});
			}',
	);
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$query = $this->EE->db->select('settings')
				      ->where('class', 'Field_editor_ext')
				      ->limit(1)
				      ->get('extensions');
		
		if ($query->num_rows() !== 0 && $settings = $this->unserialize($query->row('settings'), FALSE))
		{
			foreach (array_keys($this->settings) as $key)
			{
				if (isset($settings[$key]))
				{
					$this->settings[$key] = $settings[$key];
				}
			}
		}
	}
	
	public function index()
	{
		$this->EE->load->library('table');
		
		$this->set_page_title()
		     ->set_right_nav();
		
		$this->set_right_nav();
		
		$this->EE->table->clear();
		
		$this->EE->table->set_template(array(
			'table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="field_editor mainTable padTable">',
			'row_start' => '<tr class="even">',
			'row_alt_start' => '<tr class="odd">',
		));
		
		$this->EE->table->set_heading(lang('group_name'), '', '', '', '');
		
		$this->EE->load->model('field_model');
		
		$this->EE->load->helper('html');
		
		$query = $this->EE->field_model->get_field_groups();
		
		foreach ($query->result() as $row)
		{
			$this->EE->table->add_row(
				anchor(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=edit'.AMP.'group_id='.$row->group_id, $row->group_name).sprintf(' (%d)', $row->count),
				anchor(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=delete_confirm'.AMP.'group_id='.$row->group_id, lang('delete')),
				anchor(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=export'.AMP.'group_id='.$row->group_id, lang('field_editor_export')),
				anchor(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=import_upload'.AMP.'group_id='.$row->group_id, lang('field_editor_import')),
				anchor(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=clone_group'.AMP.'group_id='.$row->group_id, lang('field_editor_clone'))
			);
		}
		
		return $this->EE->table->generate();
	}
	
	private function set_page_title($title = FALSE)
	{
		if ($title === FALSE)
		{
			$title = lang('field_editor_module_name');
		}
		
		$this->EE->cp->set_variable('cp_page_title', $title);
		
		return $this;
	}
	
	private function set_right_nav()
	{
		$this->EE->lang->loadfile('admin_content');
		
		$this->EE->cp->set_right_nav(array(
			'create_new_field_group' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=new_group',
			'field_editor_edit_groups' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=edit',
			'field_editor_import_group' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=import_upload',
			'settings' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=settings',
		));
		
		return $this;
	}
	
	public function settings()
	{
		$this->set_page_title()
		     ->set_right_nav();
		
		$this->EE->load->library('table');
		
		$this->EE->table->set_template(array(
			'table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">',
			'row_start' => '<tr class="even">',
			'row_alt_start' => '<tr class="odd">',
		));
		
		foreach ($this->settings as $key => $value)
		{
			switch($key)
			{
				case 'group_prefixes':
					break;
				default:
					$this->EE->table->add_row(
						form_label(lang('field_editor_'.$key), $key),
						form_input($key, $value, 'id="'.$key.'"')
					);
			}
		}
		
		return form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=save_settings')
		       .$this->EE->table->generate()
		       .form_submit('', lang('submit'), 'class="submit"')
		       .form_close();
	}
	
	public function save_settings()
	{
		$this->do_save_settings($_POST);
		
		$this->EE->session->set_flashdata('message_success', lang('settings_saved'));
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=settings');
	}
	
	private function do_save_settings($settings = array())
	{
		foreach (array_keys($this->settings) as $key)
		{
			if (isset($settings[$key]))
			{
				$this->settings[$key] = $settings[$key];
			}
		}
		
		$this->EE->db->update('extensions', array('settings' => serialize($this->settings)), array('class' => 'Field_editor_ext'));
	}
	
	public function new_group()
	{
		$this->EE->lang->loadfile('admin_content');
		
		$this->EE->cp->set_variable('cp_page_title', lang('create_new_field_group'));
		
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor', lang('field_editor_module_name'));
		
		return $this->EE->load->view('new_group', NULL, TRUE);
	}
	
	public function create_group()
	{
		if ($this->EE->input->post('cancel'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		
		if ( ! $group_name = $this->EE->input->post('group_name'))
		{
			show_error(lang('field_editor_no_group_name'));
		}
		
		$this->EE->load->model('field_model');
		
		$this->EE->field_model->insert_field_group($group_name);
		
		$group_id = $this->EE->db->insert_id();
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=edit'.AMP.'group_id='.$group_id);
	}
	
	public function delete_confirm()
	{
		$this->EE->lang->loadfile('admin_content');
		
		$this->EE->load->helper('html');
		
		$vars['group_id'] = $this->EE->input->get_post('group_id');
		
		$query = $this->EE->db->select('group_name')
				      ->where('group_id', $vars['group_id'])
				      ->get('field_groups');
		
		$vars['group_name'] = $query->row('group_name');
		
		$query->free_result();
		
		$vars['channels'] = FALSE;
		
		$query = $this->EE->db->select('channel_title')
				      ->where('field_group', $vars['group_id'])
				      ->get('channels');
		
		foreach ($query->result() as $row)
		{
			$vars['channels'][] = $row->channel_title;
		}
		
		$query->free_result();
		
		$vars['fields'] = FALSE;
		
		$query = $this->EE->db->select('field_label')
				      ->where('group_id', $vars['group_id'])
				      ->order_by('field_order', 'asc')
				      ->get('channel_fields');
		
		foreach ($query->result() as $row)
		{
			$vars['fields'][] = $row->field_label;
		}
		
		$this->EE->cp->add_to_head('<link rel="stylesheet" href="'.$this->EE->config->item('theme_folder_url').'third_party/field_editor/css/field_editor.css" type="text/css" media="screen" />');
		
		$this->EE->cp->set_variable('cp_page_title', lang('field_editor_module_name'));
		
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor', lang('field_editor_module_name'));
		
		return $this->EE->load->view('delete_confirm', $vars, TRUE);
	}
	
	public function delete_group()
	{
		if ($this->EE->input->post('cancel'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		
		$group_id = $this->EE->input->post('group_id');
		
		$this->EE->load->model('field_model');
		
		$this->EE->field_model->delete_field_groups($group_id);
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
	}
	
	public function edit($errors = FALSE, $existing_fields = FALSE)
	{
		if ( ! $this->EE->input->get_post('group_id'))
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		
		if ( ! $this->EE->cp->allowed_group('can_access_admin') OR ! $this->EE->cp->allowed_group('can_access_content_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->load->library(array('javascript', 'table', 'api'));
		
		$this->EE->load->helper(array('html', 'form'));
		
		$this->EE->api->instantiate('channel_fields');
		
		$this->EE->load->model('field_model');
		
		$this->EE->lang->loadfile('admin_content');
		
		$this->EE->lang->loadfile('filemanager');
		
		$this->EE->cp->add_to_head('<link rel="stylesheet" href="'.$this->EE->config->item('theme_folder_url').'third_party/field_editor/css/field_editor.css" type="text/css" media="screen" />');
		
		$this->EE->cp->add_js_script(array('ui' => array('sortable'), 'plugin' => array('ee_url_title')));
		
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor', lang('field_editor_module_name'));
		
		$group_id = $this->EE->input->get_post('group_id');
		
		/**
		 * get group name
		 */
		$query = $this->EE->field_model->get_field_group($group_id);
		
		$group_name = $query->row('group_name');
		
		$this->EE->cp->set_variable('cp_page_title', lang('field_editor_edit_group'));
		
		$query->free_result();
		
		/**
		 * get all fields
		 */
		$this->EE->db->select('field_groups.group_name, channel_fields.*')
			     ->join('field_groups', 'channel_fields.group_id = field_groups.group_id');
		
		$query = $this->EE->field_model->get_fields();
		
		$all_fields = $query->result_array();
		
		$query->free_result();
		
		$current_fields = ($existing_fields === FALSE) ? array() : $existing_fields;
		
		$all_field_names = array();
		
		$clone_fields = array('' => lang('field_editor_choose_field_to_clone'));
		
		foreach ($all_fields as $row)
		{
			if ( ! isset($clone_fields[$row['group_name']]))
			{
				$clone_fields[$row['group_name']] = array();
			}
			
			$clone_fields[$row['group_name']][$row['field_id']] = $row['field_label'];
			
			if ($row['group_id'] != $group_id && $row['site_id'] == $this->EE->config->item('site_id'))
			{
				$all_field_names[] = $row['field_name'];
			}
			
			if ($existing_fields === FALSE && $row['group_id'] == $group_id)
			{
				unset($row['group_name']);
				
				$current_fields[] = $row;
			}
		}
		
		$fields = array();
		
		$field_type_tables = array();
		
		$max_rowid = 0;
		
		if (empty($current_fields))
		{
			$current_fields = array($this->field_edit_vars($group_id));
			$current_fields[0]['new'] = TRUE;
		}
		
		foreach ($current_fields as $index => $field)
		{
			$rowid = (isset($field['rowid'])) ? $field['rowid'] : $index;
			
			if ($rowid > $max_rowid)
			{
				$max_rowid = $rowid;
			}
			
			$field = array_merge($this->field_edit_vars($group_id, $field['field_id']), $field);
			
			if (isset($field['field_type_tables'][$field['field_type']]))
			{
				if (is_array($field['field_type_tables'][$field['field_type']]))
				{
					$this->EE->load->library('table');
					
					$this->EE->table->clear();
					
					foreach ($field['field_type_tables'][$field['field_type']] as $row)
					{
						$this->EE->table->add_row($row);
					}
					
					$field_type_tables[$field['field_id']] = $this->EE->table->generate();
				}
				else
				{
					$field_type_tables[$field['field_id']] = $field['field_type_tables'][$field['field_type']];
				}
			}
			else
			{
				$field_type_tables[$field['field_id']] = '';
			}
			
			$field['rowid'] = $rowid;
			
			$fields[$index] = $field;
		}
		
		$max_rowid++;
		
		$no_field_settings = array();
		
		$blank_vars = $this->field_edit_vars($group_id);
		
		//some fields have no settings, like the date field. let's store that info for later
		foreach ($blank_vars['field_type_tables'] as $ft => $ft_settings)
		{
			if ( ! $ft_settings)
			{
				$no_field_settings[] = $ft;
			}
		}
		
		$error_colspan = 10;
		
		$blank_vars['rowid'] = $max_rowid;
		
		$blank_row = '<tbody>'.preg_replace('/[\r\n\t]+/', '', $this->EE->load->view('field_row', array('index' => 'INDEX', 'field' => $blank_vars), TRUE)).'</tbody>';//$this->process_row($this->compile_row('INDEX', $blank_vars), '');
		
		$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$this->EE->config->slash_item('theme_folder_url').'third_party/field_editor/js/jquery.settingsTable.js"></script>');
		
		$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$this->EE->config->slash_item('theme_folder_url').'third_party/field_editor/js/fieldEditor.js"></script>');
		
		$callbacks = array();
		
		foreach ($this->fieldtype_row_js_callbacks as $field_type => $callback)
		{
			$field_settings = array();
			
			foreach ($current_fields as $field)
			{
				if ($field['field_type'] === $field_type && ! empty($field['field_settings']))
				{
					$field_settings = $this->unserialize($field['field_settings']);
					
					break;
				}
			}
			
			$callbacks[] = sprintf("%s: {\n\t\t\t\"callback\": %s,\n\t\t\t\"settings\": %s\n\t\t}", $this->EE->javascript->generate_json($field_type), $callback, $this->EE->javascript->generate_json($field_settings));
		}
		
		$callbacks = "{\n\t".implode(",\n\t\t", $callbacks)."\n}";
		
		$this->EE->javascript->output('
			fieldEditor.init($.extend('.$this->EE->javascript->generate_json(array(
				'groupId' => $group_id,
				'rowIdCounter' => $max_rowid,
				'errorColspan' => $error_colspan,
				'blankRow' => $blank_row,
				'noFieldSettings' => $no_field_settings,
				'existingFields' => $existing_fields,
				'errors' => $errors,
				'loadingImg' => img(array(
					'src' => $this->EE->config->slash_item('theme_folder_url').'cp_global_images/loadingAnimation.gif',
					'style' => 'display: block; margin: 20px auto;',
				)),
				'lang' => array(
					'noSettings' => lang('field_editor_no_settings'),
					'fieldTypeOptions' => lang('field_type_options'),
					'save' => lang('save'),
					'cancel' => lang('cancel'),
					'loading' => lang('loading').'...',
					'noFieldName' => lang('no_field_name'),
					'reservedWord' => lang('reserved_word'),
					'noFieldLabel' => lang('no_field_label'),
					'duplicateFieldName' => lang('duplicate_field_name'),
					'pleaseAddUpload' => lang('please_add_upload'),
					'unsavedChanges' => lang('field_editor_unsaved_changes'),
				),
				'reservedWords' => array_values($this->EE->cp->invalid_custom_field_names()),
				'allFieldNames' => $all_field_names,
				'globalPrefix' => $this->settings['global_prefix'],
			), TRUE).', {"createRowCallbacks":'.$callbacks.'}));
			fieldEditor.table.settingsTable({
				rowSelector: "tbody",
				addSelector: ".add_field",
				blankRow: fieldEditor.blankRow,
				addFullSelector: "a#add_field",
				deleteSelector: ".delete_field",
				dragHandle: ".drag_handle",
				deleteConfirm: '.$this->EE->javascript->generate_json(lang('field_editor_delete_field_confirm')).',
				afterReset: fieldEditor.resetFields,
				beforeDelete: fieldEditor.deleteField,
				afterAdd: fieldEditor.addField
			});
			fieldEditor.table.find("td.checkbox").live("click", function(e){
				if (e.target != this) {
					return;
				}
				var checkbox = $(this).find(":checkbox");
				checkbox.attr("checked", ! checkbox.is(":checked"));
			});
			var error = $(".field_editor tr.errors:first");
			if (error.length > 0) {
				error.parent().find(":input[type!=hidden]:first").focus();
			}
			fieldEditor.table.find("tbody tr").each(function() {
				fieldEditor.createRowCallback(this);
			});
		');
		
		$vars['group_id'] = $group_id;
		$vars['group_name'] = $group_name;
		$vars['fields'] = $fields;
		$vars['errors'] = $errors;
		$vars['field_type_tables'] = $field_type_tables;
		$vars['error_colspan'] = $error_colspan;
		$vars['clone_fields'] = $clone_fields;
		$vars['group_prefix'] = (isset($this->settings['group_prefixes'][$group_id])) ? $this->settings['group_prefixes'][$group_id] : '';
		
		return $this->EE->load->view('edit', $vars, TRUE);
	}
	
	public function save()
	{
		$this->EE->load->model('field_model');
		
		$group_id = $this->EE->input->post('group_id');
		
		if ($group_name = $this->EE->input->post('group_name'))
		{
			$this->EE->db->update('field_groups', array('group_name' => $this->EE->input->post('group_name')), array('group_id' => $group_id));
		}
		
		if ($group_prefix = $this->EE->input->post('group_prefix'))
		{
			if ( ! isset($this->settings['group_prefixes']))
			{
				$this->settings['group_prefixes'] = array($group_id => $group_prefix);
			}
			else
			{
				$this->settings['group_prefixes'][$group_id] = $group_prefix;
			}
			
			$this->do_save_settings();
		}
		
		$errors = array();
		
		if (is_array($this->EE->input->post('delete_fields')))
		{
			$this->EE->load->model('field_model');
			
			foreach ($this->EE->input->post('delete_fields') as $field_id)
			{
				$this->EE->field_model->delete_fields($field_id);
			}
		}
		
		$updated_settings = is_array($this->EE->input->post('updated_settings')) ? $this->EE->input->post('updated_settings') : array();
		
		if ($fields = $this->EE->input->post('fields'))
		{
			foreach ($fields as $index => $field_data)
			{
				$field_data['group_id'] = $group_id;
				
				$field_data['site_id'] = $this->EE->config->item('site_id');
				
				$field_data['field_order'] = $index + 1;
				
				foreach (array('field_is_hidden', 'field_required', 'field_search') as $key)
				{
					if ( ! isset($field_data[$key]))
					{
						$field_data[$key] = 'n';
					}
				}
				
				$keep_existing_settings = ! empty($field_data['field_id']) && ! in_array($field_data['field_id'], $updated_settings);
				
				if ( ! $this->update_field($field_data, $keep_existing_settings))
				{
					if (in_array(lang('no_field_name'), $this->EE->api_channel_fields->errors))
					{
						unset($this->EE->api_channel_fields->errors[array_search(lang('duplicate_field_name'), $this->EE->api_channel_fields->errors)]);
					}
					
					$errors[$field_data['rowid']] = $this->EE->api_channel_fields->errors;
				}
			}
		}
		
		if ($errors)
		{
			return $this->edit($errors, $fields);
		}
		
		//@TODO set flashdata msg
		$this->EE->session->set_flashdata('message_success', lang('field_editor_fields_updated'));
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=edit'.AMP.'group_id='.$this->EE->input->get_post('group_id'));
	}
	
	public function field_settings()
	{
		$field_id = $this->EE->input->get_post('field_id');
		
		$group_id = $this->EE->input->get_post('group_id');
		
		$field_type = $this->EE->input->get_post('field_type');
		
		$this->EE->lang->loadfile('admin_content');
		
		$this->EE->load->library('api');
		
		$this->EE->api->instantiate('channel_fields');
		
		$field_vars = $this->field_edit_vars($group_id, $field_id, $field_type);
		
		$vars['field_type'] = $field_type;
		
		if (is_array($field_vars['field_type_tables'][$field_type]))
		{
			$this->EE->load->library('table');
			
			$this->EE->table->clear();
			
			foreach ($field_vars['field_type_tables'][$field_type] as $row)
			{
				$this->EE->table->add_row($row);
			}
			
			$this->EE->table->set_template(array(
				'table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable" style="width:100%;border-top:1px solid #D0D7DF;">',
				'row_start' => '<tr class="even">',
				'row_alt_start' => '<tr class="odd">',
			));
			
			$vars['content'] = $this->EE->table->generate();
		}
		else
		{
			$vars['content'] = $field_vars['field_type_tables'][$field_type];
		}
		
		if ($this->EE->input->post('modal_html'))
		{
			$vars['content'] = $this->EE->input->post('modal_html');
		}
		
		$vars['pre'] = $field_vars['pre'];
		$vars['post'] = $field_vars['post'];
		
		exit($this->EE->load->view('field_settings', $vars, TRUE));
	}
	
	public function save_field_settings()
	{
		$this->EE->output->send_ajax_response($_POST);
	}
	
	//@TODO there are major exceptions here, like matrix, which will need their own special behavior
	public function clone_field()
	{
		$result = array(
			'row' => FALSE,
			'form' => '',
		);
		
		$field_id = $this->EE->input->get_post('field_id');
		$rowid = $this->EE->input->get_post('rowid');
		$group_id = $this->EE->input->get_post('group_id');
		
		if ( ! $field_id || ! $group_id || $rowid === FALSE)
		{
			exit($this->EE->javascript->generate_json($result));
		}
		
		$this->EE->load->model('field_model');
		
		$query = $this->EE->field_model->get_field($field_id);
		
		if ($query->num_rows() === 0)
		{
			exit($this->EE->javascript->generate_json($result));
		}
		
		$field = array_merge($this->field_edit_vars($group_id, $field_id), $query->row_array());
		
		$query->free_result();
		
		if (is_array($field['field_type_tables'][$field['field_type']]))
		{
			$this->EE->load->library('table');
			
			$this->EE->table->clear();
			
			foreach ($field['field_type_tables'][$field['field_type']] as $row)
			{
				$this->EE->table->add_row($row);
			}
			
			$result['form'] = '<form>'.$this->EE->table->generate().'</form>';
		}
		else
		{
			$result['form'] = '<form>'.$field['field_type_tables'][$field['field_type']].'</form>';
		}
		
		switch ($field['field_type'])
		{
			case 'matrix':
				
				//make all the cols into new cols
				if (preg_match_all('/col_id_(\d+)/', $result['form'], $matches))
				{
					$col_count = 0;
					
					$existing_cols = array();
					
					foreach ($matches[1] as $col_id)
					{
						if ( ! isset($existing_cols[$col_id]))
						{
							$existing_cols[$col_id] = $col_count++;
						}
					}
					
					foreach ($existing_cols as $old_col_id => $new_col_id)
					{
						$result['form'] = str_replace('col_id_'.$old_col_id, 'col_new_'.$new_col_id, $result['form']);
					}
				}
				
				break;
		}
		
		//remove the field_id
		$field['field_id'] = '';
		
		$field['rowid'] = $rowid;
		
		$result['row'] = '<tbody>'.str_replace(array("\r", "\n", "\t"), '', $this->EE->load->view('field_row', array('index' => $rowid, 'field' => $field), TRUE)).'</tbody>';
		
		exit($this->EE->javascript->generate_json($result));
	}
	
	private function field_edit_vars($group_id, $field_id = FALSE, $field_type = FALSE)
	{
		if ( ! defined('FIELD_EDITOR'))
		{
			define('FIELD_EDITOR', TRUE);
		}
		
		$this->EE->load->library('api');
		
		$this->EE->api->instantiate('channel_fields');
		
		$cache = array(
			'jquery_code_for_compile' => $this->EE->javascript->js->jquery_code_for_compile,
			'its_all_in_your_head' => $this->EE->cp->its_all_in_your_head,
			'footer_item' => $this->EE->cp->footer_item,
		);
		
		$_GET['field_id'] = $field_id;
		
		$_GET['group_id'] = $group_id;
		
		if ($field_type)
		{
			//cache the original objects
			$object_cache = array(
				'js' => $this->EE->javascript->js,
				'cp' => $this->EE->cp,
			);
			
			//override these objects
			$this->EE->javascript->js = new Field_editor_Jquery(array('autoload' => FALSE));
			$this->EE->cp = new Field_editor_Cp;
			
			$vars = $this->EE->api_channel_fields->field_edit_vars($group_id, $field_id);
			
			//we only want the header/footer/javascript for this fieldtype
			$vars['pre'] = '';
			
			if (isset($this->EE->cp->per_field_head[$field_type]))
			{
				foreach ($this->EE->cp->per_field_head[$field_type] as $row)
				{
					$vars['pre'] .= $row;
				}
			}
			
			$vars['post'] = '';
			
			if (isset($this->EE->cp->per_field_foot[$field_type]))
			{
				foreach ($this->EE->cp->per_field_foot[$field_type] as $row)
				{
					$vars['post'] .= $row;
				}
			}
			
			if (isset($this->EE->javascript->js->per_field_code[$field_type]))
			{
				$vars['post'] .= $this->EE->javascript->inline('jQuery(document).ready(function($) {
					'.implode("\n", $this->EE->javascript->js->per_field_code[$field_type]).'
				});');
			}
			
			//restore the original objects
			$this->EE->javascript->js = $object_cache['js'];
			$this->EE->cp = $object_cache['cp'];
		}
		else
		{
			
			$vars = $this->EE->api_channel_fields->field_edit_vars($group_id, $field_id);
			/*
			$vars['pre'] = '';
			
			foreach ($this->EE->cp->its_all_in_your_head as $row)
			{
				$vars['pre'] .= $row;
			}
			
			$vars['post'] = '';
			
			foreach ($this->EE->cp->footer_item as $row)
			{
				$vars['post'] .= $row;
			}
			
			$vars['post'] .= $this->EE->javascript->inline('jQuery(document).ready(function($) {
				'.implode("\n", $this->EE->javascript->js->jquery_code_for_compile).'
			});');
			*/
		}
		
		$this->EE->javascript->js->jquery_code_for_compile = $cache['jquery_code_for_compile'];
		$this->EE->cp->its_all_in_your_head = $cache['its_all_in_your_head'];
		$this->EE->cp->footer_item = $cache['footer_item'];
		
		return $vars;
	}
	
	private function update_field($field_data, $keep_existing_settings = FALSE)
	{
		$this->EE->load->library('api');
		
		$this->EE->api->instantiate('channel_fields');
		
		//grab existing data
		if ( ! empty($field_data['field_id']))
		{
			$field_id = $field_data['field_id'];
			
			$query = $this->EE->db->where('field_id', $field_id)
					      ->get('channel_fields');
			
			$existing_settings = $query->row('field_settings');
			
			$field_settings = $this->unserialize($query->row('field_settings'));
			
			//this makes matrix work, might work with others
			$field_data[$field_data['field_type']] = $field_settings;
			
			$field_data = array_merge($query->row_array(), $field_settings, $field_data);
		}
		
		if ( ! empty($field_data['field_type_settings']) && $field_data['field_type_settings'] !== 'click')//dunno wtf is up with dat
		{
			$this->EE->load->library('services_json');
			
			$field_type_settings = array();
			
			$decoded_field_type_settings = json_decode($field_data['field_type_settings'], TRUE);
			
			$working_field_type_settings = array();
			
			$counts = array();
			
			/**
			 * this mess is to convert a faux nested array like this into a real one
			 *
			 *   'matrix[min_rows]' => '0',
			 *   'matrix[max_rows]' => '',
			 *   'matrix[col_order][]' => 'col_new_1',
			 *   'matrix[cols][col_new_0][type]' => 'text',
			 *   'matrix[cols][col_new_1][type]' => 'text',
			 */
			foreach ($decoded_field_type_settings as $row)
			{
				//ends with []
				if (preg_match('/\[\]$/', $row['name']))
				{
					if (isset($counts[$row['name']]))
					{
						$index = ++$counts[$row['name']];
					}
					else
					{
						$index = $counts[$row['name']] = 0;
					}
					
					$row['name'] = substr($row['name'], 0, strlen($row['name']) - 2).'['.$index.']';
				}
				
				$working_field_type_settings[$row['name']] = $row['value'];
			}
			
			$working_field_type_settings_query = http_build_query($working_field_type_settings);
			
			parse_str($working_field_type_settings_query, $field_type_settings);
			/*
			foreach ($decoded_field_type_settings as $key => $value)
			{
				if (preg_match_all('/\[(.*?)\]/', $key, $matches))
				{
					$new_key = substr($key, 0, strpos($key, '['));
				
					if ( ! isset($field_type_settings[$new_key]))
					{
						$field_type_settings[$new_key] = array();
					}
					
					$array =& $field_type_settings[$new_key];
					
					$count = count($matches[1]) - 1;
					
					foreach ($matches[1] as $i => $sub_key)
					{
						if ( ! $sub_key)
						{
							if ($i < $count)
							{
								$array[] = array();
							}
							else
							{
								$array[] = $value;
							}
						}
						else
						{
							if ( ! isset($array[$sub_key]))
							{
								if ($i < $count)
								{
									$array[$sub_key] = array();
								}
								else
								{
									$array[$sub_key] = $value;
								}
							}
						}
						
						if ($i < $count)
						{
							$array =& $array[$sub_key];
						}
					}
				}
				else
				{
					$field_type_settings[$key] = $value;
				}
			}
			*/
			
			$field_data = array_merge($field_data, $field_type_settings);
		}
		
		$field_type_length = strlen($field_data['field_type']) + 1;
		
		foreach ($field_data as $key => $value)
		{
			if (strncmp($key, $field_data['field_type'].'_', $field_type_length) === 0)
			{
				$field_data[substr($key, $field_type_length)] = $value;
			}
		}
		
		/* special cases */
		
		//this has a different array key in the settings and needs to be remove
		//or else the old original value gets saved
		unset($field_data['field_related_id']);
		
		//some fieldtypes use $_POST directly, I think
		$_POST = $field_data;
		
		$return = $this->EE->api_channel_fields->update_field($field_data);
		
		//just in case--we restore the original settings if they have not been flagged as updated
		if (isset($existing_settings) && $keep_existing_settings)
		{
			$this->EE->db->update('channel_fields', array('field_settings' => $existing_settings), array('field_id' => $field_data['field_id']));
		}
		
		return $return;
	}

	/**
	 * Generate and force download the XML of selected channels and template groups
	 *
	 * @access public
	 * @return void
	 */
	public function export()
	{
		$this->EE->load->library('package_exporter');
		
		$this->EE->package_exporter->set_format('json');
		
		$this->EE->load->helper('download');
		
		if ( ! $group_id = $this->EE->input->get_post('group_id'))
		{
			$this->EE->session->set_flashdata('message_failure', lang('field_editor_no_group_id'));
			
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		
		$query = $this->EE->db->select('group_name')
				      ->where('group_id', $group_id)
				      ->get('field_groups');
		
		if ( ! $query->row('group_name'))
		{
			$this->EE->session->set_flashdata('message_failure', lang('field_editor_no_group_id'));
			
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		
		$filename = url_title($query->row('group_name'));
		
		$query->free_result();
		
		$data = $this->EE->package_exporter->export('field_groups', array($group_id));
		
		$data = $this->EE->package_exporter->generate($data);
		
		force_download($filename.'.'.$this->EE->package_exporter->format(), $data);
	}
	
	public function import_upload()
	{
		$this->EE->lang->loadfile('admin_content');
		
		$this->EE->load->helper('html');
		
		$key = lang('field_editor_or_import_into_an_existing_group');
		
		$vars['groups'] = array(
			lang('field_editor_create_new_group') => array('' => lang('field_editor_new_field_group')),
			$key => array(),
		);
		
		$query = $this->EE->db->select('group_id, group_name')
				      ->where('site_id', $this->EE->config->item('site_id'))
				      ->get('field_groups');
				      
		foreach ($query->result() as $row)
		{
			$vars['groups'][$key][$row->group_id] = $row->group_name;
		}
		
		$this->EE->cp->add_to_head('<link rel="stylesheet" href="'.$this->EE->config->item('theme_folder_url').'third_party/field_editor/css/field_editor.css" type="text/css" media="screen" />');
		
		$this->EE->cp->set_variable('cp_page_title', lang('field_editor_import_group'));
		
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor', lang('field_editor_module_name'));
		
		return form_open_multipart('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=import')
		       .form_fieldset().form_dropdown('group_id', $vars['groups'], $this->EE->input->get_post('group_id')).form_fieldset_close()
		       .form_fieldset().form_upload('data').form_fieldset_close()
		       .form_fieldset().form_submit('', lang('submit'), 'class="submit"').form_fieldset_close()
		       .form_close();
	}
	
	public function clone_group()
	{
		$this->EE->load->library('package_exporter');
		
		$this->EE->package_exporter->set_format('json');
		
		$this->EE->load->helper('download');
		
		if ( ! $group_id = $this->EE->input->get_post('group_id'))
		{
			$this->EE->session->set_flashdata('message_failure', lang('field_editor_no_group_id'));
			
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		
		$query = $this->EE->db->select('group_name')
				      ->where('group_id', $group_id)
				      ->get('field_groups');
		
		if ( ! $query->row('group_name'))
		{
			$this->EE->session->set_flashdata('message_failure', lang('field_editor_no_group_id'));
			
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		
		$query->free_result();
		
		$data = $this->EE->package_exporter->export('field_groups', array($group_id));
		
		$group_name = $data['field_groups'][0]['group_name'];
		
		$query = $this->EE->db->like('group_name', $group_name, 'after')
					->limit(1)
					->order_by('group_name', 'desc')
					->get('field_groups');
		
		if ($query->num_rows() !== 0)
		{
			$data['field_groups'][0]['group_name'] .= str_replace($group_name, '', $query->row('group_name')) + 1;
			
			unset($_GET['group_id'], $_POST['group_id'], $_REQUEST['group_id']);
		}
		
		$data = $this->EE->package_exporter->generate($data);
		
		$this->_import($data);
	}
	
	private function _import($data)
	{
		$this->EE->load->library('package_installer');
		
		$this->EE->package_installer->set_format('json');
		
		$this->EE->package_installer->load($data);
		
		foreach ($this->EE->package_installer->packages() as $i => $node)
		{
			if ( ! in_array($node->getName(), array('field_group', 'field')))
			{
				$this->EE->package_installer->remove_package($i);
			}
		}
		
		if ( ! $this->EE->package_installer->packages())
		{
			$this->EE->session->set_flashdata('message_failure', lang('field_editor_no_data'));
			
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		else
		{
			if ($group_id = $this->EE->input->get_post('group_id'))
			{
				$fields = array();
				
				foreach ($this->EE->package_installer->packages() as $i => $node)
				{
					$field_order = 1;
					
					if ($node->getName() === 'field_group')
					{
						foreach ($node->field as $field)
						{
							$field->addAttribute('group_id', $group_id);
							
							$field->addAttribute('field_order', $field_order++);
							
							$fields[] = $field;
						}
					
						$this->EE->package_installer->remove_package($i);
					}
					else
					{
						$node->addAttribute('group_id', $group_id);
						
						$field->addAttribute('field_order', $field_order++);
					}
				}
				
				$this->EE->package_installer->add_package($fields);
			}
			
			$this->EE->package_installer->install();
		}
		
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
	}

	/**
	 * Process and install the submitted XML
	 *
	 * @access public
	 * @return void
	 */
	public function import()
	{
		$this->EE->load->library('upload', array(
			'upload_path' => ($this->EE->config->item('cache_path')) ? $this->EE->config->item('cache_path') : APPPATH.'cache/',
			'file_name' => 'field_editor_import.tmp',
			'overwrite' => TRUE,
			'allowed_types' => '*',
		));
		
		if ($this->EE->upload->do_upload('data'))
		{
			$data = $this->EE->upload->data();
			
			$data = file_get_contents($data['full_path']);
			
			@unlink($data['full_path']);
		}
		else
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->upload->display_errors());
			
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor');
		}
		
		$this->_import($data);
	}
	
	private function unserialize($string, $base64_decode = TRUE)
	{
		if ($base64_decode)
		{
			$string = base64_decode($string);
		}
		
		$array = @unserialize($string);
		
		return (is_array($array)) ? $array : array();
	}
	
	public static function backtrace_fieldtype()
	{
		$backtrace = debug_backtrace();
		
		foreach ($backtrace as $step)
		{
			$object = (isset($step['object'])) ? $step['object'] : FALSE;
			
			if ($object && $object instanceof EE_Fieldtype)
			{
				$class = get_class($object);
				
				return strtolower(preg_replace('/_ft$/', '', $class));
			}
		}
		
		return FALSE;
	}
}
// END CLASS

/**
 * class overrides
 */

class Field_editor_Cp extends Cp
{
	public $per_field_head;
	public $per_field_foot;
	
	public function add_to_head($data)
	{
		parent::add_to_head($data);
		
		if ($fieldtype = Field_editor_mcp::backtrace_fieldtype())
		{
			$this->per_field_head[$fieldtype][] = $data;
		}
	}
	
	public function add_to_foot($data)
	{
		parent::add_to_foot($data);
		
		if ($fieldtype = Field_editor_mcp::backtrace_fieldtype())
		{
			$this->per_field_foot[$fieldtype][] = $data;
		}
	}
}

class Field_editor_Jquery extends CI_Jquery
{
	public $per_field_code = array();
	
	public function _output($data)
	{
		parent::_output($data);
		
		if ($fieldtype = Field_editor_mcp::backtrace_fieldtype())
		{
			$this->per_field_code[$fieldtype][] = $data;
		}
	}
}

/* End of file mcp.field_editor.php */
/* Location: ./system/expressionengine/third_party/modules/field_editor/mcp.field_editor.php */