<h3>Add to Pool</h3>

<p>Data is uploaded in two steps to minimize the load, memory usages, and queries against the server. Adding data to your pool is the first step. Once the data is in the pool below, run the geocoder to import and geocode your data.</p>

<form method="post" enctype="multipart/form-data" action="<? echo $action?>" style="margin-bottom:2em;">
	
	<p>
		<label for="id">Settings Schema</label><br>
		<select name="id" id="id">
		<? foreach($settings->result() as $setting): ?>
			<option value="<? echo $setting->id?>"><? echo $setting->id?></option>
		<? endforeach; ?>
		</select>
	</p>
	
	<p>
		<label for="file">File</label><br>
		<input type="file" name="file" id="file" />
	</p>
	
	<button type="submit" class="submit">Add to Pool</button>
	
</form>

<h3>Data Pool</h3>

<p>This it the data that is still in the pool. To work around Google's API limits the data is stored in a pool. Once the data has been imported, it will be removed from pool.</p>

<table class="mainTable padTable" border="0" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th>ID</th>
			<th>Items in Pools</th>
			<th>Total Entries Imported</th>
			<th>Importer Last Ran</th>
			<th>Total Import Attempts</th>
			<th style="width:85px;"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td> </td>
			<td> </td>
			<td> </td>
			<td> </td>
			<td> </td>
			<td><a href="#">Run Geocoder</a></td>
		</tr>
	</tbody>
</table>