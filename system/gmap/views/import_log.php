<h2>Import Log</h2>

<p>The import log is a utility that allows you to manage entries with invalid data that were imported into the system. Only items that have been flagged as invalid addresses or multiple locations. Once you have made the changes to entry, you may close the issue.</p>

<h3>Known Issues</h3>

<?php if($items->num_rows() > 0): ?>
<p>There <?php if($items->num_rows() == 1):?>is<?php else: ?>are<?php endif;?> <?php echo $items->num_rows()?> known issue<?php if($items->num_rows() > 1):?>s<?php endif; ?>.</p>
<?php endif; ?>

<table class="mainTable padTable" border="0" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<th width="100">Entry ID</th>
			<th>Errors</th>
			<th>Total Errors</th>
			<th>View Entry</th>
			<th>Action</th>
		</tr>
	</thead>
	<tbody>
	<?php if($items->num_rows() == 0): ?>
		<tr>
			<td colspan="5">There are no items in the log at this time</td>
		</tr>
	<?php endif;?>
	<?php foreach($items->result() as $item): ?>
		<tr>
			<td><?php echo $item->entry_id?></td>
			<td><?php echo implode(json_decode($item->errors), '<br>')?></td>
			<td><?php echo $item->total_errors?></td>
			<td><a href="<?php echo $base_url.'&entry_id='.$item->entry_id?>">View Entry</a></td>
			<td><label><input type="checkbox" name="status[<?php echo $item->entry_id?>]" value="closed" data-id="<?php echo $item->id?>" /> Mark as Valid</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		$('input').click(function() {
			var $t    = $(this);
			var _def  = 'open';
			var value = _def;
			var id 	  = $t.data('id');
			
			if($t.prop('checked')) {
				value = $(this).val();
			}
			$.post('<?php echo $action?>&id='+id, {status: value}, function(data) {
				console.log(data);
			});
		});
	});
</script>