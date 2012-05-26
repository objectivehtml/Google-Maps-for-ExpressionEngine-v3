<style type="text/css">
	.tags {
		margin: 5px 0;
		}
		
	.tag {
		padding: 0 0 0 7px;
		margin: 2px 7px 2px 0;
		background: rgb(193, 223, 244);
		display: inline-block;
		float: left;
		-moz-border-radius: 3px;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		}
	
	.tag a {
		display: inline-block;
		padding: 5px 3px 5px 3px;
		color: rgb(53, 61, 67) !important;
		}
	 
	.tag a:hover {
		color: rgb(53, 61, 67);
		text-decoration: none;
		background: rgb(169, 195, 214);
		-moz-border-radius: 0 3px 3px 0;
		-webkit-border-radius: 0 3px 3px 0;
		border-radius: 0 3px 3px 0;
		}
</style>

<script type="text/javascript">
	
	var Safecracker = {
		fields: <?=$channel_fields?>,
		settings: 	   <?=$settings?>,
		tags: []
	};
	
	$(document).ready(function() {

		$('select[name="channel_id"]').change(function() {		
			var $t = $(this);
			var channel_id = $t.val();
			
			var html = ['<option value=""></option>'];
			
			if(channel_id != "") {
				$.each(Safecracker.fields[channel_id], function(i, field) {
					html.push('<option value="'+field.field_id+'">'+field.field_name+'</option>');		
				});	
			}
			
			$('.channel_fields').html(html.join(''));
		});
		
		$('select[name="channel_id"]').change();
		
		$('select[name="search_field"]').change(function() {
			var $t 	= $(this);
			var val = $t.val();
			var txt	= $t.find(':selected').text();
			
			if(val != '') {
				var html = [
					'<li>',
						'<div class="tag">',
							txt,
							'<input type="hidden" name="search_fields[]" value="'+val+'" />',
							'<a href="#delete">×</a>',
						'</div>',
					'</li>'
				].join('');
				
				$('.tags').append(html);
			}
		});
		
		$('.tag a').live('click', function() {
			var $t = $(this);
			var tag = $t.parent();
			var index = $t.data('index');
			
			tag.fadeOut();
			
			Safecracker.tags[parseInt(index)] = false;
			
			return false;
		});
		
	});

</script>

<form method="post" action="<?=$form_action_url?>">

	<h3>Overview</h3>
	
	<p>This utility allows you to painlessly migrate the data in the member module into a specified channel. Simply select your desired channel, and define the desired fields to prevent duplicate records (if any). If you are converting 100's of records, the script will take some time to execute.</p>
	
	<h3>Upload Settings</h3>
	
	<table class="mainTable" cellpadding="0" cellspacing="0">
		
		<thead>
			<tr>
				<th style="width:40%;">Setting</th>
				<th>Preference</th>
			</tr>
		</thead>
		
		<tbody>
			<tr>
				<td style="">
					<label for="channel_id">Member Channel</label>
				</td>
				<td style="">
					<?=$channel_dropdown?>
				</td>
			</tr>
			<tr>
				<td style="">
					<label for="search_field">Match Fields</label>
					<p>Any field defined here will be used to search for matching records. If there is a matching entry, then the record is ignored. A channel entry will be created for each member, assuming there are no existing members.</p>
				</td>
				<td style="">
					<select name="search_field" id="search_field" class="channel_fields"></select>
					
					<ul class="tags"></ul>
				</td>
			</tr>
			<tr>
				<td style="">
					<label for="search_field">Title Fields</label>
					<p>Enter the member fields here to create a dynamic title for your entry. Be sure to use the <b>member</b> fields, not the <b>channel</b> fields.</p>
					<p><b>Example:</b><br> {first_name} {last_name} - {email}</p>
					<p><em>These must be your custom field, if not they will not parse </em></p>
				</td>
				<td style="">
					<input type="text" name="title_fields" value="" id="title_fields" />
				</td>
			</tr>
		</tbody>
		
	</table>
	
	<h3>Member Field Mapping</h3>
	
	<p>Map each of your member fields to their corresponding channel field. Do not skip this step.</p>
	
	<table class="mainTable" cellpadding="0" cellspacing="0">
		
		<thead>
			<tr>
				<th style="width:20%;">Member Field Name</th>
				<th style="width:20%;">Member Field Label</th>
				<th>Channel Field</th>
			</tr>
		</thead>
		
		<tbody>
		<? foreach($member_fields->result() as $field):?>
			<tr>
				<td>
					<label for="<?=$field->m_field_name?>"><?=$field->m_field_name?></label>
				</td>
				<td>
					<label for="<?=$field->m_field_name?>"><?=$field->m_field_label?></label>
				</td>
				<td>
					<select name="member_field[<?=$field->m_field_name?>]" id="<?=$field->m_field_name?>" class="channel_fields"></select>
				</td>
			</tr>
		<? endforeach;?>
		</tbody>
		
	</table>
	
	<button type="submit" class="submit">Upload Members</button>
	<input type="hidden" name="return" value="<?=$return?>" />
	
</form>