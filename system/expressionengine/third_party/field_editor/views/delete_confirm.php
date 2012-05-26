<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=delete_group', 'id="delete_confirm"', array('group_id' => $group_id))?>
	<p class="notice"><?=lang('field_editor_delete_field_group_confirmation')?></p>
	<h2><?=$group_name?></h2>
	<?php if ($channels) : ?>
	<h4><?=lang('field_editor_used_by')?></h4>
	<?=ul($channels)?>
	<?php endif; ?>
	<?php if ($fields) : ?>
	<h4><?=lang('field_editor_contains_fields')?></h4>
	<?=ul($fields)?>
	<?php endif; ?>
	<?=form_submit('', lang('delete'), 'class="submit"')?>
	<?=form_submit('cancel', lang('cancel'), 'class="submit"')?>
<?=form_close()?>