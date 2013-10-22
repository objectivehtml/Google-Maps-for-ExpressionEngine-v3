<table class="<?php echo $matrix ? 'matrix-col-settings' : 'mainTable padTable' ?>">
	
	<?php if(!$matrix): ?>
	<thead>
		<tr>
			<th colspan="2"><?php echo lang('gmap_field_settings')?></th>
		</tr>
	</thead>
	<?php endif; ?>
	
	<tbody>
		<?php if(!$matrix): ?>
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_preview']?></strong> <br>
				<?php echo $lang['gmap_preview_description']?>
			</td>
			<td class="gmap-container">
				<div class="canvas gmap" id="gmap_canvas" style="height:400px"><div class="deferer"><p><?php echo lang('gmap_click_to_activate')?></p></div></div>
				
				<div class="gmap-flyout">
					
					<p><?php echo lang('gmap_settings_flyout_helper')?></p>
					
					<ul>
						<li>
							<label for="start"><?php echo lang('gmap_starting_point')?></label>
							<input type="text" name="start" id="start" value="" />
						</li>
						<li>
							<label for="start"><?php echo lang('gmap_ending_point')?></label>
							<input type="text" name="end" id="end" value="" />
						</li>
					</ul>
					
					<button type="button"><?php echo lang('gmap_map_route')?></button>
					
					<a href="#" class="close">&times; <?php echo lang('gmap_close')?></a>
				</div>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_defer_init']?></strong> <br>
				<?php echo $lang['gmap_defer_init_description']?>
			</td>
			<td>
				<?php echo $gmap_defer_init_boolean?>
			</td>
		</tr>
		
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_geocoder_field_label']?></strong> <br>
				<?php echo $lang['gmap_geocoder_field_label_description']?>
			</td>
			<td>
				<input type="text" name="<?php echo $name_prefix ?>gmap_geocoder_field_label<?php echo $name_suffix ?>" id="gmap_geocoder_field_label" value="<?php echo $gmap_geocoder_field_label?>" />
			</td>
		</tr>

		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_geocoder_button']?></strong> <br>
				<?php echo $lang['gmap_geocoder_button_description']?>
			</td>
			<td>
				<input type="text" name="gmap_geocoder_button" id="gmap_geocoder_button" value="<?php echo $gmap_geocoder_button?>" />
			</td>
		</tr>
		
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_geocoder_field_place']?></strong> <br>
				<?php echo $lang['gmap_geocoder_field_place_description']?>
			</td>
			<td>
				<input type="text" name="gmap_geocoder_field_place" id="gmap_geocoder_field_place" value="<?php echo $gmap_geocoder_field_place?>" />
			</td>
		</tr>
		
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_no_valid_location']?></strong> <br>
				<?php echo $lang['gmap_no_valid_location_description']?>
			</td>
			<td>
				<input type="text" name="gmap_no_valid_location" id="gmap_no_valid_location" value="<?php echo $gmap_no_valid_location?>" />
			</td>
		</tr>
		
		
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_scroll_wheel']?></strong> <br>
				<?php echo $lang['gmap_scroll_wheel_description']?>
			</td>
			<td>
				<?php echo $gmap_scroll_wheel_boolean?>
			</td>
		</tr>
		
		<!--
		To be implemented at a later date
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_display_help']?></strong> <br>
				<?php echo $lang['gmap_display_help_description']?>
			</td>
			<td>
				<?php echo $gmap_display_help_boolean?>
			</td>
		</tr>
		-->
		
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_map_height']?></strong> <br>
				<?php echo $lang['gmap_map_height_description']?>
			</td>
			<td>
				<input type="text" name="gmap_map_height" id="gmap_map_height" value="<?php echo $gmap_map_height?>" />
			</td>
		</tr>

		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_zoom']?></strong> <br>
				<?php echo $lang['gmap_zoom_description']?>
			</td>
			<td>
				<input type="text" name="gmap_zoom" id="gmap_zoom" value="<?php echo $gmap_zoom?>" />
			</td>
		</tr>
		
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_zoom_one_marker']?></strong> <br>
				<?php echo $lang['gmap_zoom_one_marker_description']?>
			</td>
			<td>
				<input type="text" name="gmap_zoom_one_marker" id="gmap_zoom_one_marker" value="<?php echo $gmap_zoom_one_marker?>" />
			</td>
		</tr>
		
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_latitude']?></strong> <br>
				<?php echo $lang['gmap_latitude_description']?>
			</td>
			<td>
				<input type="text" name="gmap_latitude" id="gmap_latitude" value="<?php echo $gmap_latitude?>" />
			</td>
		</tr>
		
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_longitude']?></strong> <br>
				<?php echo $lang['gmap_longitude_description']?>
			</td>
			<td>
				<input type="text" name="gmap_longitude" id="gmap_longitude" value="<?php echo $gmap_longitude?>" />
			</td>
		</tr>		
		
		<?php if(!$low_variables): ?>
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_response']?></strong> <br>
				<?php echo $lang['gmap_response_description']?>
			</td>
			<td>
				<?php echo $gmap_response_select?>
			</td>
		</tr>
				
		<tr>
			<td width="40%">
				<strong><?php echo $lang['gmap_formatted_address']?></strong> <br>
				<?php echo $lang['gmap_formatted_address_description']?>
			</td>
			<td>
				<?php echo $gmap_formatted_address_select?>
			</td>
		</tr>
		<?php endif; ?>
		
		<tr class="">
			<td width="40%">
				<strong><?php echo $lang['gmap_total_points']?></strong> <br>
				<?php echo $lang['gmap_total_points_description']?>
			</td>
			<td>
				<input type="text" name="gmap_total_points" id="gmap_total_points" value="<?php echo $gmap_total_points?>" />
			</td>
		</tr>
		
		<tr class="">
			<td width="40%">
				<strong><?php echo $lang['gmap_min_points']?></strong> <br>
				<?php echo $lang['gmap_min_points_description']?>
			</td>
			<td>
				<input type="text" name="gmap_min_points" id="gmap_min_points" value="<?php echo $gmap_min_points?>" />
			</td>
		</tr>
		
		<tr class="onchange" id="gmap_marker_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_marker_mode']?></strong> <br>
				<?php echo $lang['gmap_marker_mode_description']?>
			</td>
			<td>
				<?php echo $gmap_marker_mode_boolean?>
			</td>
		</tr>
		
		<tr class="gmap_marker_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_file_group']?></strong> <br>
				<?php echo $lang['gmap_file_group_description']?>
			</td>
			<td>
				<?php echo $gmap_file_group_upload_prefs?>
			</td>
		</tr>
		
		<?php if(!$low_variables): ?>
		<tr class="gmap_marker_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_latitude_field']?></strong> <br>
				<?php echo $lang['gmap_latitude_field_description']?>
			</td>
			<td>
				<?php echo $gmap_latitude_field_select?>
			</td>
		</tr>
		
		<tr class="gmap_marker_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_longitude_field']?></strong> <br>
				<?php echo $lang['gmap_longitude_field_description']?>
			</td>
			<td>
				<?php echo $gmap_longitude_field_select?>
			</td>
		</tr>
		
		<tr class="gmap_marker_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_zoom_field']?></strong> <br>
				<?php echo $lang['gmap_zoom_field_description']?>
			</td>
			<td>
				<?php echo $gmap_zoom_field_select?>
			</td>
		</tr>
		
		<tr class="gmap_marker_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_marker_field']?></strong> <br>
				<?php echo $lang['gmap_marker_field_description']?>
			</td>
			<td>
				<?php echo $gmap_marker_field_select?>
			</td>
		</tr>
		<?php endif; ?>
		
		<tr class="gmap_marker_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_include_marker_title']?></strong> <br>
				<p><?php echo $lang['gmap_include_marker_title_description']?></p>
			</td>
			<td>
				<?php echo $gmap_include_marker_title_boolean?>
			</td>
		</tr>
		
		<tr class="onchange" id="gmap_waypoint_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_waypoint_mode']?></strong> <br>
				<?php echo $lang['gmap_waypoint_mode_description']?>
			</td>
			<td>
				<?php echo $gmap_waypoint_mode_boolean?>
			</td>
		</tr>
				
		<?php if(!$low_variables): ?>
		<tr class="gmap_waypoint_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_waypoint_start_coord']?></strong> <br>
				<?php echo $lang['gmap_waypoint_start_coord_description']?>
			</td>
			<td>
				<?php echo $gmap_waypoint_start_coord_select?>
			</td>
		</tr>
		
		<tr class="gmap_waypoint_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_waypoint_end_coord']?></strong> <br>
				<?php echo $lang['gmap_waypoint_end_coord_description']?>
			</td>
			<td>
				<?php echo $gmap_waypoint_end_coord_select?>
			</td>
		</tr>
		<?php endif; ?>
		
		<tr class="onchange" id="gmap_region_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_region_mode']?></strong> <br>
				<?php echo $lang['gmap_region_mode_description']?>
			</td>
			<td>
				<?php echo $gmap_region_mode_boolean?>
			</td>
		</tr>
		
		<?php if(!$low_variables): ?>
		<tr class="gmap_region_mode">
			<td width="40%">
				<strong><?php echo $lang['gmap_region_field']?></strong> <br>
				<?php echo $lang['gmap_region_field_description']?>
			</td>
			<td>
				<?php echo $gmap_region_field_select?>
			</td>
		</tr>
		<?php endif; ?>

	</tbody>
</table>