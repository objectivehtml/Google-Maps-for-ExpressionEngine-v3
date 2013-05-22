<div class="gmap-wrapper <?php echo $low_variables ? 'low-vars' : ''?> group" id="gmap-wrapper-<?php echo $settings['field_id']?>" data-gmap-id="<?php echo $settings['field_id']?>">
	
	<div class="markers column" style="<?php if((int)$settings['total_points'] == 1) echo 'display:none;'?>">		
		<ul class="toggle group">
			<?php if($settings['marker_mode'] == 'yes'):?>
			<li class="" <?php if($settings['region_mode'] == 'no' && $settings['waypoint_mode'] == 'no'): ?> style="display:none"<?php endif; ?>><a href="#markers">Markers</a></li>
			<?php endif; ?>
			<?php if($settings['waypoint_mode'] == 'yes'):?>
			<li class="" <?php if($settings['marker_mode'] == 'no' && $settings['region_mode'] == 'no'): ?> style="display:none"<?php endif; ?>><a href="#waypoints" class="">Route</a></li>
			<?php endif; ?>
			<?php if($settings['region_mode'] == 'yes'):?>
			<li class="" <?php if($settings['marker_mode'] == 'no' && $settings['waypoint_mode'] == 'no'): ?> style="display:none"<?php endif; ?>><a href="#regions">Regions</a></li>
			<?php endif; ?>
		</ul>
		
		<div class="lists">
			<ul class="markers" data-name="markers">
				<li class="empty"><?php echo lang('gmap_empty_fieldtype')?></li>
			</ul>
			<ul class="waypoints" data-name="waypoints">
				<li class="empty"><?php echo lang('gmap_empty_fieldtype')?></li>
			</ul>
			<ul class="regions" data-name="regions">
				<li class="empty"><?php echo lang('gmap_empty_fieldtype')?></li>
			</ul>
		</div>
	</div>
	
	<div class="map column" style="<?php if($settings['total_points'] == 1):?>width:100%<?php endif; ?>">
		<div class="geocoder">
			<label for="gmap_geocoder"><?php echo (isset($settings['geocoder_field_label']) && !empty($settings['geocoder_field_label'])) ? $settings['geocoder_field_label'] : lang('gmap_geocoder')?></label>
			<input type="text" name="gmap_geocoder" id="gmap_geocoder" value="" placeholder="<?php echo (isset($settings['geocoder_field_place']) && !empty($settings['geocoder_field_place'])) ? $settings['geocoder_field_place'] : lang('gmap_geocoder_placeholder') ?>" />
			<a href="#" class="gmap-upload"></a>
			<button type="button" class="submit"><?php echo (isset($settings['geocoder_button']) && !empty($settings['geocoder_button'])) ? $settings['geocoder_button'] : 'Plot Location'?></button>
		</div>
		
		<div class="canvas" style="<?php if(isset($settings['map_height']) && !empty($settings['map_height'])): ?>height:<?php echo $settings['map_height']?>;<?php else: ?>height:600px;<?php endif; ?>">
			<div class="deferer"><p><?php echo lang('gmap_click_to_activate')?></p></div>
		</div>
		
		<div class="suggestions gmap-flyout">
			<a href="#" class="close"><span class="times">&times;</span> Close</a>
			
			<h4>Results</h4>
			
			<p class="statistics"></p>
			
			<ul></ul>
		</div>
		
		<div class="side-content-panel gmap-flyout">			
			<h4>Window Content</h4>
			
			<textarea name="side-content-text"></textarea>			
		</div>
		
		<input type="hidden" name="import_url" value="<?php echo $import_url?>" />
			
		<!-- 	
		<div class="gmap-import-panel gmap-flyout">			
			
			<form action="<?php echo $import_url?>" method="post" enctype="multipart/form-data">
				<h4>Import .CSV</h4>
				
				<input type="file" name="file" value="" />
				
				<button type="submit">Import</button>
			</form>
			
		</div>		
		-->
				
		<div class="marker side-panel gmap-flyout">
			<!--<a href="#" class="close"><span class="times">&times;</span> Close</a>-->
			
			<h4>Marker Options</h4>
			
			<ul>
				<li>
					<!-- <label>Address</label> -->
					<div class="address"></div>
				</li>
				
				<?php if(isset($settings['include_marker_title']) && $settings['include_marker_title'] == 'yes'): ?>
				<li>
					<label for="marker-title-<?php echo $settings['field_id']?>">Title</label>
					<input name="marker-title" id="marker-title-<?php echo $settings['field_id']?>" value="" />
				</li>
				<?php endif; ?>
				<li>
					<label for="marker-content-<?php echo $settings['field_id']?>">Content</label>
					<input name="side-content" id="marker-content-<?php echo $settings['field_id']?>" value="" />
				</li>
				<li>
					<label for="marker-icon-<?php echo $settings['field_id']?>"><span class="clear-icon" style="display:none"><a href="#">clear</a></span></label>
					<div class="gmap-icons"></div>
				</li>
			</ul>
			
			<button type="button" class="cancel-marker">Cancel Marker</button>
			<button type="button" class="save-marker">Save Marker</button>
			
		</div>
		
		<div class="region side-panel gmap-flyout">
			<!--<a href="#" class="close"><span class="times">&times;</span> Close</a>-->
			
			<h4>Region Options</h4>
			
			<ul>
				<li>
					<p>Total Points: 3</p>
				</li>
				<li>
					<label for="region-name-<?php echo $settings['field_id']?>">Title</label>
					<input name="region-name" id="region-name-<?php echo $settings['field_id']?>" value="" />
				</li>
				<li>
					<label for="region-content-<?php echo $settings['field_id']?>">Content</label>
					<input name="side-content" id="region-content-<?php echo $settings['field_id']?>" value="" />
				</li>
				<li>
					<div id="region-stroke-color-picker-<?php echo $settings['field_id']?>" class="color-picker gmap-flyout">
						<a href="#" class="close"><span class="times">&times;</span> Close</a>
			
						<h4>Color Picker</h4>
						
						<div></div>
					</div>
					<label for="region-stroke-color-<?php echo $settings['field_id']?>">Stroke Color</label>
					<input name="region-stroke-color" id="region-stroke-color-<?php echo $settings['field_id']?>" value="" data-show="region-stroke-color-picker-<?php echo $settings['field_id']?>" data-field="region-stroke-color-<?php echo $settings['field_id']?>" class="show-color-picker"/>
				</li>
				<li>
					<label for="region-stroke-opacity-<?php echo $settings['field_id']?>">Stroke Opacity</label>
					<select name="region-stroke-opacity" id="region-stroke-opacity-<?php echo $settings['field_id']?>">
						<option value="0.1">.1</option>
						<option value="0.2">.2</option>
						<option value="0.3">.3</option>
						<option value="0.4">.4</option>
						<option value="0.5" selected="selected">.5</option>
						<option value="0.6">.6</option>
						<option value="0.7">.7</option>
						<option value="0.8">.8</option>
						<option value="0.9">.9</option>
						<option value="1">1</option>
					</select>
				</li>
				<li>
					<label for="region-stroke-weight-<?php echo $settings['field_id']?>">Stroke Weight</label>
					<select name="region-stroke-weight" id="region-stroke-weight-<?php echo $settings['field_id']?>">
						<option value="1" selected="selected">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="5">5</option>
						<option value="6">6</option>
						<option value="7">7</option>
						<option value="8">8</option>
						<option value="9">9</option>
						<option value="10">10</option>					
					</select>
				</li>
				<li>
					<div id="region-fill-color-picker-<?php echo $settings['field_id']?>" class="color-picker gmap-flyout">
						<a href="#" class="close"><span class="times">&times;</span> Close</a>
			
						<h4>Color Picker</h4>
						
						<div></div>
					</div>
					
					<label for="region-fill-color-<?php echo $settings['field_id']?>">Fill Color</label>
					<input name="region-fill-color" id="region-fill-color-<?php echo $settings['field_id']?>" data-show="region-fill-color-picker-<?php echo $settings['field_id']?>" data-field="region-fill-color-<?php echo $settings['field_id']?>" class="show-color-picker" value="" />
				</li>
				<li>
					<label for="region-fill-opacity-<?php echo $settings['field_id']?>">Fill Opacity</label>
					<select name="region-fill-opacity" id="region-fill-opacity-<?php echo $settings['field_id']?>">
						<option value="0.1">.1</option>
						<option value="0.2">.2</option>
						<option value="0.3">.3</option>
						<option value="0.4" selected="selected">.4</option>
						<option value="0.5">.5</option>
						<option value="0.6">.6</option>
						<option value="0.7">.7</option>
						<option value="0.8">.8</option>
						<option value="0.9">.9</option>
						<option value="1">1</option>
					</select>
				</li>
			</ul>
			
			<button type="button" class="cancel-region">Cancel Region</button>
			<button type="button" class="save-region">Save Region</button>
			
		</div>
	</div>
	
	<textarea style="display:none" name="<?php echo $field_name?>" id="gmap_output_<?php echo $field_name?>" class="gmap-output"><?php echo $saved_value?></textarea>
	
</div>