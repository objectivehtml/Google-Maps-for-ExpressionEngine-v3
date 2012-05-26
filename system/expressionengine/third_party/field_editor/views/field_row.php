			<tr class="<?=alternator('even', 'odd')?>" id="row_<?=$field['rowid']?>" data-rowid="<?=$field['rowid']?>" data-id="<?=$field['field_id']?>">
				<td class="drag_handle">
					<img border="0" src="<?=$this->config->item('theme_folder_url')?>third_party/field_editor/images/drag_handle.gif" width="10" height="17">
					<?=form_hidden('fields['.$index.'][field_type_settings]', '')?>
					<?=form_hidden('fields['.$index.'][rowid]', $field['rowid'])?>
					<?=form_hidden('fields['.$index.'][field_id]', $field['field_id'])?>
				</td>
				<td>
					<?=$field['field_id']?>
				</td>
				<td>
					<?=form_input(array(
						'name' => 'fields['.$index.'][field_label]',
						'value' => $field['field_label'],
						'size' => '14',
						'class' => 'required field_label'.(($index === 'INDEX' || ! empty($field['new'])) ? ' field_label_new' : ''),
					))?>
				</td>
				<td>
					<?=form_input(array(
						'name' => 'fields['.$index.'][field_name]',
						'value' => $field['field_name'],
						'size' => '14',
						'class' => 'required field_name',
					))?>
				</td>
				<td>
					<?=form_textarea(array(
						'name' => 'fields['.$index.'][field_instructions]',
						'value' => $field['field_instructions'],
						'class' => 'field_instructions',
						'rows' => 1,
						'cols' => 22,
						'onfocus' => "$(this).attr('rows', 3);",
						'onblur' => "$(this).attr('rows', 1);",
					))?>
				</td>
				<td>
					<?=form_dropdown('fields['.$index.'][field_type]', $field['field_type_options'], $field['field_type'], 'class="field_type" data-value="'.$field['field_type'].'"')?>
				</td>
				<td class="checkbox" title="<?=lang('field_editor_required')?>">
					<?=form_label(
						form_checkbox(array(
							'name' => 'fields['.$index.'][field_required]',
							'id' => 'fields['.$index.'][field_required]',
							'value' => 'y',
							'title' => lang('field_editor_required'),
							'checked' => $field['field_required'] === 'y',
							'class' => 'field_required',
						)),
						'fields['.$index.'][field_required]'
					)?>
				</td>
				<td class="checkbox" title="<?=lang('field_editor_hidden')?>">
					<?=form_label(
						form_checkbox(array(
							'name' => 'fields['.$index.'][field_is_hidden]',
							'id' => 'fields['.$index.'][field_is_hidden]',
							'value' => 'y',
							'title' => lang('field_editor_hidden'),
							'checked' => $field['field_is_hidden'] === 'y',
							'class' => 'field_is_hidden',
						)),
						'fields['.$index.'][field_is_hidden]'
					)?>
				</td>
				<td class="checkbox" title="<?=lang('field_editor_searchable')?>">
					<?=form_label(
						form_checkbox(array(
							'name' => 'fields['.$index.'][field_search]',
							'id' => 'fields['.$index.'][field_search]',
							'value' => 'y',
							'title' => lang('field_editor_searchable'),
							'checked' => $field['field_search'] === 'y',
							'class' => 'field_search',
						)),
						'fields['.$index.'][field_search]'
					)?>
				<td>
					<a href="javascript:void(0);" title="<?=lang('field_editor_more_settings')?>" class="settings">
						<img border="0" src="<?=$this->config->slash_item('theme_folder_url')?>third_party/field_editor/images/fe_icon_settings.png" alt="<?=lang('field_editor_more_settings')?>">
					</a>
				</td>
				<td>
					<a href="javascript:void(0);" title="<?=lang('field_editor_add_field')?>" class="add_field">
						<img border="0" src="<?=$this->config->slash_item('theme_folder_url')?>third_party/field_editor/images/fe_icon_plus.png" alt="<?=lang('field_editor_add_field')?>">
					</a>
				</td>
				<td>
					<a href="javascript:void(0);" title="<?=lang('field_editor_delete_field')?>" class="delete_field">
						<img border="0" src="<?=$this->config->slash_item('theme_folder_url')?>third_party/field_editor/images/fe_icon_minus.png" alt="<?=lang('field_editor_delete_field')?>">
					</a>
				</td>
			</tr>