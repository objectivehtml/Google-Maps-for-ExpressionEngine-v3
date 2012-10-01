<script type="text/javascript" src="<?=$interface_builder?>"></script>
<script type="text/javascript">
	$(document).ready(function() {
		
		var IB = new InterfaceBuilder();
	
	});
</script>

<h2><?=$header?> Schema</h2>

<p>Enter all the correct values for the following settings to create a schema. Be sure to double check that the field names and column names are correct.</p>

<form method="post" action="<?=$action?>">
	<?=$settings?>

	<input type="hidden" name="return" value="<?=$return?>" />
	<input type="hidden" name="schema_id" value="<?=$schema_id?>" />
	
	<button class="submit">Save Settings</button>
</form>