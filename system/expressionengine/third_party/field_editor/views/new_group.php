<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=field_editor'.AMP.'method=create_group')?>
	<p><label for="group_name"><?=lang('group_name')?>: </label><?=form_input('group_name', '', 'class="field"')?></p>
	<?=form_submit('', lang('field_editor_continue'), 'class="submit"')?>
	<?=form_submit('cancel', lang('cancel'), 'class="submit"')?>
<?=form_close()?>