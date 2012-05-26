<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=save', 'id="field_editor"', array('group_id' => $group_id))?>
	<header>
		<p id="group_settings">
			<label for="group_name"><?=lang('group_name')?>: </label><?=form_input('group_name', $group_name, 'class="field" id="group_name"')?>
			&nbsp;&nbsp;&nbsp;
			<label for="group_prefix"><?=lang('field_editor_group_prefix')?>: </label><?=form_input('group_prefix', $group_prefix, 'class="field" id="group_prefix"')?>
		</p>
		<?php if ($clone_fields) : ?>
		<p id="clone_field"><?=form_dropdown('', $clone_fields)?> <a href="javascript:void(0);" class="submit">Clone Field</a></p>
		<?php endif; ?>
	</header>
	
	<table border="0" cellpadding="0" cellspacing="0" class="field_editor mainTable padTable">
		<thead>
			<tr>
				<th></th>
				<th><?=lang('id')?></th>
				<th><?=lang('field_editor_label')?></th>
				<th><?=lang('field_editor_short_name')?></th>
				<th><?=lang('field_editor_instructions')?></th>
				<th><?=lang('field_editor_type')?></th>
				<th title="<?=lang('field_editor_required')?>"><?=lang('field_editor_r')?></th>
				<th title="<?=lang('field_editor_hidden')?>"><?=lang('field_editor_h')?></th>
				<th title="<?=lang('field_editor_searchable')?>"><?=lang('field_editor_s')?></th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
		</thead>
		<?php foreach ($fields as $index => $field) : ?>
		<tbody>
			<?=$this->load->view('field_row', array('index' => $index, 'field' => $field), TRUE)?>
			<?php if (isset($errors[$field['rowid']])) : ?>
			<tr class="errors">
				<td></td>
				<td colspan="<?=$error_colspan?>"><?=ul($errors[$field['rowid']])?></td>
			</tr>
			<?php endif; ?>
		</tbody>
		<?php endforeach; ?>
	</table>
	<p style="text-align:right;"><?=lang('field_editor_legend')?></p>
	<?=form_submit('', lang('save'), 'class="submit"')?>
<?=form_close()?>

<div style="display:none;" id="field_type_tables">
	<?php foreach ($field_type_tables as $field_id => $table) : ?>
	<form id="field_type_table_<?=$field_id?>">
		<?=$table?>
	</form>
	<?php endforeach; ?>
</div>