<div id="table_builder_<?php echo $field_id?>" class="ui-tb-wrapper">

	<div class="ui-tb-links">
		<a href="#tb-add-column" data-menu="ui-tb-column-menu" class="ui-tb-button">Add Column</a>
		<a href="#tb-add-row" data-menu="ui-tb-column-menu" class="ui-tb-button">Add Row</a>
		<a href="#tb-load-preset" class="ui-tb-button">Load Preset</a>
		<a href="#tb-save-preset" class="ui-tb-button">Save as Preset</a>
	</div>

	<div class="ui-tb-content">
		<?php echo $table?>
	</div>

	<div class="ui-tb-menu ui-tb-menu-top ui-tb-add-column-menu">
		<label for="column_title_<?php echo $field_id?>">Column Title</label>
		<input type="text" name="column_title" value="" id="column_title_<?php echo $field_id?>" required="required" class="validate" />
		<label for="column_name_<?php echo $field_id?>">Column Name</label>
		<input type="text" name="column_name" value="" id="column_name_<?php echo $field_id?>" required="required" class="validate" />
		<label for="column_type_<?php echo $field_id?>">Field Type</label>
		<select name="column_type" id="column_type_<?php echo $field_id?>" class="validate"></select>
		<button type="button" class="ui-tb-button ui-tb-float-right">Add Column</button>
	</div>

	<div class="ui-tb-menu ui-tb-menu-top ui-tb-edit-column-menu">
		<label for="column_title_<?php echo $field_id?>">Column Title</label>
		<input type="text" name="column_title" value="" id="column_title_<?php echo $field_id?>" required="required" class="validate" />
		<label for="column_name_<?php echo $field_id?>">Column Name</label>
		<input type="text" name="column_name" value="" id="column_name_<?php echo $field_id?>" required="required" class="validate" />
		<label for="column_type_<?php echo $field_id?>">Field Type</label>
		<select name="column_type" id="column_type_<?php echo $field_id?>" class="validate"></select>
		<button type="button" class="ui-tb-button ui-tb-float-right">Edit Column</button>
	</div>

	<div class="ui-tb-menu ui-tb-menu-small ui-tb-menu-top ui-tb-menu-center ui-tb-column-menu">
		<ul class="ui-tb-menu-group">
			<li>
				<a href="#insert-before">Insert Before</a>
			</li>
			<li>
				<a href="#insert-after">Insert After</a>
			</li>
		</ul>
		<ul class="ui-tb-menu-group">
			<li>
				<a href="#edit-column">Edit Column</a>
			</li>
			<li>
				<a href="#delete-column">Delete Column</a>
			</li>
		<ul>
	</div>

	<div class="ui-tb-menu ui-tb-menu-small ui-tb-menu-left ui-tb-row-menu">
		<ul class="ui-tb-menu-group">
			<li>
				<a href="#insert-before">Insert Before</a>
			</li>
			<li>
				<a href="#insert-after">Insert After</a>
			</li>
		</ul>
		<ul class="ui-tb-menu-group">
			<li>
				<a href="#edit-class">Edit Class</a>
			</li>
			<li>
				<a href="#delete-row">Delete Row</a>
			</li>
		<ul>
	</div>

	<div class="ui-tb-menu ui-tb-menu-top ui-tb-load-preset-menu">
		
		<?php if(count($presets) > 0): ?>
		<p>Select a preset from the list below</p>
		
		<ul class="ui-tb-active-menu">
		<?php foreach($presets as $preset): ?>
			<li><a href="#" data-id="<?php echo $preset->id?>"><?php echo $preset->name?></a></li>
		<?php endforeach; ?>
		</ul>
		<button class="ui-tb-button ui-tb-disabled">Load Preset</button>
		<a href="#tb-delete-preset" class="ui-tb-delete ui-tb-icon"><img src="/themes/third_party/table_builder/css/images/trash.png" /></a>
		<?php else: ?>
			<p class=""><i>You have not created any presets yet.</i></p>
		<?php endif; ?>
	</div>

	<div class="ui-tb-menu ui-tb-menu-top ui-tb-save-preset-menu">
		
		<label for="preset_name_<?php echo $field_id?>">Preset Name</label>
		<input type="text" name="preset_name" id="preset_name_<?php echo $field_id?>" value="" class="validate" required="required" />

		<button type="button" class="ui-tb-button">Save Preset</button>

	</div>

	<div class="ui-tb-menu ui-tb-menu-top ui-tb-delete-column-menu">
		<p>Are you sure you want to delete this column?</p>
		<a href="#delete-column" class="ui-tb-button">Delete Column</a>
		<a href="#cancel" class="ui-tb-cancel">Cancel</a>
	</div>

	<div class="ui-tb-menu ui-tb-menu-left ui-tb-delete-row-menu">
		<p>Are you sure you want to delete this row?</p>
		<a href="#delete-row" class="ui-tb-button">Delete Row</a>
		<a href="#cancel" class="ui-tb-cancel">Cancel</a>
	</div>

	<div class="ui-tb-menu ui-tb-menu-left ui-tb-class-menu">
		<label for="class_<?php echo $field_id?>">Class</label>
		<input type="text" name="row_class" id="class_<?php echo $field_id?>" value="" class="validate" required="required" />
		<button class="ui-tb-button">Save</button>
		<a href="#cancel" class="ui-tb-cancel">Cancel</a>
	</div>

	<div class="ui-tb-dialog ui-tb-delete-preset-dialog">
		<p>Are you sure you want to delete the preset?</p>
	</div>

	<div class="ui-tb-menu ui-tb-menu-top ui-tb-save-success">
		<p>The preset was successfully saved. <a href="#" class="ui-tb-cancel">&times; Close</a></p>
	</div>
</div>