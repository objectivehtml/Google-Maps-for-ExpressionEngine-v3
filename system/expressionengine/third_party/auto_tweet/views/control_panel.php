<style type="text/css">
	table label,
	table select {
		display: block;
		margin-bottom: 10px;
		}
</style>

<script type="text/javascript">
	var index = 0;
	var rowIndex = 'new-'+index;
	
	function callback(user)
	{
		users.push(user);
		AutoTweet.callback(user);
	}
	
	$(document).ready(function() {
		AutoTweet = {
			ui: {
				accountsTable: $('#accounts'),
				addChannel: $('#add-channel'),
				authorize: $('#authorize'),
				channelTable: $('#channels'),
				deleteChannel: $('.delete-channel'),
				deleteAccount: $('.delete-account'),
				settingsForm: $('#settings-form'),
				popup: $('.popup'),
				form: {
					channel: $('select.channel-name'),
					format: $('textarea.tweet-format'),
					toggle: $('select.toggle'),
					user: $('input.user'),
					lat: $('select.latitude'),
					lng: $('select.longitude')
				}
			},
			
			callback: function(user) {
				var html = [
					'<tr style="display:none">',
						'<td>'+user.user_id+'</td>',
						'<td><a href="http://www.twitter.com/'+user.screen_name+'">'+user.screen_name+'</a></td>',
						'<td>'+user.oauth_token+'</td>',
						'<td>'+user.oauth_token_secret+'</td>',
						'<td><a href="<?=$delete_account_url?>&user_id='+user.user_id+'" class="delete-account" data-user="'+user.user_id+'">Delete</a></td>',
					'</tr>'
				];
				
				html = html.join('');
				
				AutoTweet.ui.accountsTable.find('.empty').remove();
				AutoTweet.ui.accountsTable.append(html);
				AutoTweet.ui.accountsTable.find('tr:last-child').fadeIn('slow');
				
				$('.accounts-column').each(function() {
					var $t = $(this);
					var index = $t.data('index');
					
					$t.find('.user[value="'+user.user_id+'"]').parent('label').remove();
					
					$t.append('<label><input type="checkbox" checked="checked" class="user" value="'+user.user_id+'" name="channel['+index+'][user]['+user.user_id+']"> IListUList</label>');
				});
			},
			
			getFields: function(channel_id) {
				var fields = [];
				
				$.each(channels, function(i, channel) {
					if(parseInt(channel.channel_id) == parseInt(channel_id)) {
						fields = channel.fields;
					}
				});
				
				return fields;
			},
			
			getStatuses: function(channel_id) {
				var statuses = [];
				
				$.each(channels, function(i, channel) {
					if(parseInt(channel.channel_id) == parseInt(channel_id)) {
						statuses = channel.statuses;
					}
				});
				
				return statuses;
			},
			
			populateFields: function(obj, channel_id) {
				var fields = AutoTweet.getFields(channel_id);
				
				obj.html('<option value="" selected="selected">N/A</option>');
				
				$.each(fields, function(i, field) {
					obj.append('<option value="'+field.field_id+'">'+field.field_label+'</option>');
				});
			},
			
			populateStatuses: function(obj, channel_id) {
				var statuses = AutoTweet.getStatuses(channel_id);
				
				obj.html('');
				
				$.each(statuses, function(i, status) {
					obj.append('<label><input type="checkbox" name="channel[new-'+(index-1)+'][statuses]['+i+']" value="'+status.status+'" /> '+status.status+'</label>');
				});
			},
			
			addRow: function(html) {
				AutoTweet.ui.channelTable.append(html);
				
				index++;
				rowIndex = 'new-'+index;				
			}
		};
		
		AutoTweet.ui.form.channel.live('change', function() {
			var $t 	= $(this);
			var val = $t.val();
			
			AutoTweet.populateFields($t.parents('tr').find('.latitude, .longitude'), val);
			AutoTweet.populateStatuses($t.parents('tr').find('.statuses'), val);
		});
		
		AutoTweet.ui.addChannel.click(function() {
			if(users.length > 0) {
				var channelDropDown = '<select name="channel['+rowIndex+'][channel_id]" id="channel-'+rowIndex+'" class="channel-name">';
				
				$.each(channels, function(i, channel) {
					channelDropDown += '<option value="'+channel.channel_id+'">'+channel.channel_title+'</option>';
				});
				
				channelDropDown += '</select>';
				
				var usersCheckList = '';
							
				$.each(users, function(i, user) {
					usersCheckList += '<label><input type="checkbox" class="user" value="'+user.user_id+'" name="channel['+rowIndex+'][user][]"> '+user.screen_name+'</label>';
				});
				
				var html = [
					'<tr>',
						'<td>',
							channelDropDown,
						'</td>',
						'<td>',
							'<textarea class="tweet-format" name="channel['+rowIndex+'][tweet-format]">{title}</textarea>',
							'<label for="hash-tags-'+rowIndex+'">Hash Tags</label>',
							'<input type="text" id="hash-tags-'+rowIndex+'" maxlength="15" value="" name="channel['+rowIndex+'][hash_tags]">',
							'<label for="url-'+rowIndex+'">URL</label>',
							'<input type="text" id="url-'+rowIndex+'" maxlength="250" value="" name="channel['+rowIndex+'][url]" />',
							'<label><input type="checkbox" name="channel['+rowIndex+'][multiple-tweets]" class="multiple-tweets" /> Span across multiple tweets?</label>',
						'</td>',
						'<td data-index="'+rowIndex+'" class="accounts-column">',
							usersCheckList,
						'</td>',
						'<td class="statuses">',
						'</td>',
						'<td>',
							'<label>',
								'Latitude <select name="channel['+rowIndex+'][latitude]" class="latitude"></select>',
							'</label>',							
							'<label>',
								'Longitude <select name="channel['+rowIndex+'][longitude]" class="longitude"></select>',
							'</label>',
						'</td>',
						'<td><a href="#" class="delete-channel">Delete</a></td>',
					'</tr>'
				];
				
				html = html.join('');
				
				AutoTweet.addRow(html);
				AutoTweet.ui.channelTable.find('.empty').remove();
				AutoTweet.ui.form.channel = $('select.channel-name');
				AutoTweet.ui.form.latitude = $('select.latitude');
				AutoTweet.ui.form.longitude = $('select.longitude');
				
				$('#channel-new-'+(index-1)).change();
			}
			else {
				alert('You must authorize at least one Twitter account before setting up a channel');
			}
			
			return false;			
		});
		
		AutoTweet.ui.settingsForm.submit(function() {
		
		});
		
		AutoTweet.ui.deleteChannel.live('click', function() {
			var $t 		= $(this);
			var table 	= $t.parents('table');
			var rows 	= table.find('tr');
			var id 		= $t.attr('id');

			$t.parents('tr').fadeOut('slow', function() {
				$(this).remove();

				$.get('<?=$delete_channel_url?>&id='+id, function(data) {
					if(rows.length <= 2)
						table.append('<tr class="empty"><td colspan="5"><p>You have not setup any channels yet.</p></td></tr>');
				});
				
			});
			
			return false;
		});
		
		AutoTweet.ui.form.toggle.change(function() {
			var $t 		= $(this);
			var value 	= parseInt($t.val());
			var id		= $t.attr('id');
			var fields	= $('.'+id);
			
			if(value)	fields.show();
			else		fields.hide();
		});
		
		AutoTweet.ui.form.toggle.change();
		
		AutoTweet.ui.popup.click(function() {
			var $t = $(this);
			var href = $t.attr('href');
			
			window.open (href, "Authorize your Twitter Account","status=0, toolbar=0, width=800, height=780");
			
			return false;
		});
		
		AutoTweet.ui.deleteAccount.live('click', function() {
			var $t = $(this);
			var href = $t.attr('href');
			var rows = $t.parents('tr');
			var user = $t.data('user');
			
			$.get(href, function() {
				$t.parents('tr').fadeOut('fast');
				
				$('input[value="'+user+'"]').parent('label').remove();
			
				if(rows.length <= 1) {
					$t.parents('table').append([
					'<tr class="empty">',
						'<td colspan="5">',
							'<p>You have no authorized Twitter accounts.</p>',
						'</td>',
					'</tr>'].join(''));
				}
			});
			
			return false;
		});
	});
	
</script>
	
	<form action="<?=$settings_action?>" method="post" id="settings-form">
		<div style="margin-bottom:1em">
	
			<h3>Twitter API Settings</h3>
			
			<p>Go to the <a href="https://dev.twitter.com/apps" target="_blank">Twitter Developer</a> page and make sure to input the callback URL, Consumer Key, and Consumer Secret.</p>
			
			<table cellspacing="0" cellpadding="0" border="0" class="mainTable">
				<thead>
					<tr>
						<th colspan="2">Twitter API Settings</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td width="50%"><label for="consumer_key">Consumer Key</label></td>
						<td><input type="text" name="consumer_key" value="<?=$settings['consumer_key']?>" id="consumer_key" /></td>
					</tr>
					<tr>
						<td width="50%"><label for="consumer_secret">Consumer Secret</label></td>
						<td><input type="text" name="consumer_secret" value="<?=$settings['consumer_secret']?>" id="consumer_secret" /></td>
					</tr>
					<tr>
						<td width="50%"><label for="callback_url">Callback URL</label></td>
						<td><input type="text" name="callback_url" value="<?=$settings['callback_url']?>" id="callback_url" /></td>
					</tr>
					</tr>
				</tbody>
			</table>			
			
		</div>
	
		<div style="margin-bottom:1em">
		
			<h3>Authorized Twitter Accounts</h3>
			
			<? if($settings['consumer_key'] && $settings['consumer_secret']): ?>
						
			<p>You can authorize multiple accounts that can automatically tweet entries. You can authorize different users for different channels too.</p>
			
			<p><a href="<?=$request_token_url?>" id="authorize" class="popup">&plus; Authorize Twitter Account</a></p>
			
			<table cellspacing="0" cellpadding="0" border="0" class="mainTable" id="accounts">
				<thead>
					<tr>
						<th>User Id</th>
						<th>Screen Name</th>
						<th>OAuth Token</th>
						<th>OAuth Token Secret</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<? if($users->num_rows() == 0): ?>
					<tr class="empty">
						<td colspan="5">
							<p>You have no authorized Twitter accounts.</p>
						</td>
					</tr>
					<? else: ?>
						<? foreach($users->result() as $user): ?>
						<tr>
							<td width="20"><?=$user->user_id?></td>
							<td width="90"><a href="https://www.twitter.com/<?=$user->screen_name?>" target="_blank"><?=$user->screen_name?></a></td>			
							<td><?=$user->oauth_token?></td>			
							<td><?=$user->oauth_token_secret?></td>
							<td><a href="<?=$delete_account_url?>&user_id=<?=$user->user_id?>" data-user="<?=$user->user_id?>" class="delete-account">Delete</a></td>		
						</tr>
						<? endforeach; ?>
					<? endif; ?>
				</tbody>
			</table>
			
			<? else: ?>
				<p>You have not setup your Twitter API settings yet. Enter your consumer key and consumer secret before adding Twitter account.</p>			
			<? endif; ?>
		</div>
		
		<div>
			
			<h3>URL Shortener</h3>
			
			<table cellspacing="0" cellpadding="0" border="0" class="mainTable">
				<thead>
					<tr>
						<th colspan="2">Bitly API Settings</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td width="50%">
							<label for="shorten_url">Do you want to shorten your URLs?</label>
							<p>Go to your <a href="http://bitly.com/a/account" target="_blank">Bitly Account</a> page and retrieve your username and API key and enter it below.</p>
						</td>
						<td>
							<select name="shorten_url" id="shorten_url" class="toggle">
								<option value="0" <?=$settings['shorten_url'] == 0 ? 'selected="selected"' : ''?>>No</option>
								<option value="1" <?=$settings['shorten_url'] == 1 ? 'selected="selected"' : ''?>>Yes</option>
							</select>
						</td>
					</tr>
					<tr class="shorten_url">
						<td width="50%"><label for="bitly_username">Username</label></td>
						<td><input type="text" name="bitly_username" value="<?=$settings['bitly_username']?>" id="bitly_username" /></td>
					</tr>
					<tr class="shorten_url">
						<td width="50%"><label for="bitly_api_key">API Key</label></td>
						<td><input type="text" name="bitly_api_key" value="<?=$settings['bitly_api_key']?>" id="bitly_api_key" /></td>
					</tr>
				</tbody>
			</table>
			
		</div>
	
		<div>
		
			<h3>Auto Tweet Channels</h3>
			
			<p>This section is where you define the channels' entries that will get automatically tweeted. You can set any combination of custom fields that will make up your tweet. Just type your channel fields out like you would in the template. If you define a latitude and longitude, the coordinates will be included in the tweet giving it a location.</p>
			
			<p><a href="#" id="add-channel">&plus; Add Channel</a></p>
		
			<table cellspacing="0" cellpadding="0" border="0" class="mainTable" id="channels">
				<thead>
					<tr>
						<th>Channel Name</th>
						<th>Tweet</th>
						<th>Users</th>
						<th>Status</th>
						<th>Geolocation</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<? if(count($saved_channels) == 0): ?>
					<tr class="empty">
						<td colspan="6">
							<p>You have not setup any channels yet.</p>
						</td>
					</tr>
					<? else: ?>
						<? foreach($saved_channels as $index => $saved_channel): ?> 
						<tr>
							<td width="120">
								<select name="channel[<?=$saved_channel->id?>][channel_id]" class="channel-name">
								<? foreach($channels as $channel): ?>
									<option value="<?=$channel->channel_id?>" <?=$saved_channel->channel_id == $channel->channel_id ? 'selected="selected"' : ''?>><?=$channel->channel_title?></option>
								<? endforeach; ?>
								</select>
							</td>
							<td>
								<label for="tweet-format-<?=$saved_channel->id?>">Custom Fields and Format</label>
								<textarea id="tweet-format-<?=$saved_channel->id?>" name="channel[<?=$saved_channel->id?>][tweet-format]" class="tweet-format"><?=$saved_channel->tweet_format?></textarea>
								
								<label for="url-<?=$saved_channel->id?>">URL</label>
								<input type="text" name="channel[<?=$saved_channel->id?>][url]" value="<?=$saved_channel->url?>" maxlength="250" id="url-<?=$saved_channel->id?>" />
								
								<label for="hash-tags-<?=$saved_channel->id?>">Hash Tags</label>
								<input type="text" name="channel[<?=$saved_channel->id?>][hash_tags]" value="<?=$saved_channel->hash_tags?>" maxlength="50" id="hash-tags-<?=$saved_channel->id?>" />
								
								<label><input type="checkbox" class="multiple-tweets" name="channel[<?=$saved_channel->id?>][multiple-tweets]" <?=$saved_channel->multiple_tweets == 1 ? 'checked="checked"' : ''?> /> Span across multiple tweets?</label>
							</td>	
							<td data-index="<?=$saved_channel->id?>" class="accounts-column">
								<? 
									$user_options = explode('|', $saved_channel->users);
									
									foreach($users->result() as $user): ?>
										<label><input type="checkbox" name="channel[<?=$saved_channel->id?>][user][<?=$user->user_id?>]" value="<?=$user->user_id?>" class="user" <?=in_array($user->user_id, $user_options) ? 'checked="checked"' : '' ?> /> <?=$user->screen_name?></label>
								<? 	endforeach; ?>
							</td>
							<td>
								<? 
									$statuses = explode('|', $saved_channel->statuses);
									
									foreach($saved_channel->status_options as $status_option): ?>
										<label><input type="checkbox" name="channel[<?=$saved_channel->id?>][statuses][]" value="<?=$status_option->status?>" <?=in_array($status_option->status, $statuses) ? 'checked="checked="' : ''?> /> <?=$status_option->status?></label>
								<? 	endforeach; ?>
							</td>
							<td width="120">
								<label>
									Latitude
									<select class="latitude" name="channel[<?=$saved_channel->id?>][latitude]" data-select="">	
										<option value="">N/A</option>
									<? foreach($saved_channel->fields as $field): ?>
										<option value="<?=$field->field_id?>" <?=$saved_channel->latitude_field_id == $field->field_id ? 'selected="selected"' : ''?>><?=$field->field_label?></option>
									<? endforeach; ?>						
									</select>
								</label>
								
								<label>
									Longitude
									<select class="longitude" name="channel[<?=$saved_channel->id?>][longitude]" data-select="">				
										<option value="">N/A</option>
									<? foreach($saved_channel->fields as $field): ?>
										<option value="<?=$field->field_id?>" <?=$saved_channel->longitude_field_id == $field->field_id ? 'selected="selected"' : ''?>><?=$field->field_label?></option>
									<? endforeach; ?>						
									</select>
								</label>
							</td>
							<td width="10"><a href="#" id="<?=$saved_channel->id?>" class="delete-channel">Delete</a></td>		
						</tr>
						<? endforeach; ?>
					<? endif; ?>
				</tbody>
			</table>
			
		</div>

		<button type="submit" class="submit">Save Settings</button>
		
		<input type="hidden" name="redirect_uri" value="<?=$return?>" />
		
	</form>