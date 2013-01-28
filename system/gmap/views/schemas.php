<h2>Schemas</h2>

<p>Schemas allow you to define any number of different options to import your data into multiple channels with any combination of settings. Creating atleat one schema is required before you can import data.</p>

<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">
	<thead>
		<tr>
			<th>ID</th>
			<th>Schema Name</th>
			<th></th>
			<th></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($settings->result() as $setting): ?>
		<tr>
			<td style="width:50%"><?php echo $setting->schema_id?></td>
			<td style="width:50%"><?php echo json_decode($setting->settings)->id?></td>
			<td><a href="<?php echo $edit_url?>&id=<?php echo $setting->schema_id?>">Edit</a></td>
			<td><a href="<?php echo $duplicate_url?>&id=<?php echo $setting->schema_id?>">Duplicate</a></td>
			<td><a href="<?php echo $delete_url?>&id=<?php echo $setting->schema_id?>">Delete</a></td>
		</tr>
	<?php endforeach; ?>
	</tbody>	
</table>