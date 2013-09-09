<script type="text/javascript" src="<?php echo $interface_builder?>"></script>
<script type="text/javascript">
	$(document).ready(function() {
		
		var IB = new InterfaceBuilder();
	
	});
</script>

<h2><?php echo $header?> Schema</h2>

<p>Enter all the correct values for the following settings to create a schema. Be sure to double check that the field names and column names are correct.</p>

<form method="post" action="<?php echo $action?>">
	<?php echo $settings?>

	<input type="hidden" name="return" value="<?php echo $return?>" />
	<input type="hidden" name="schema_id" value="<?php echo $schema_id?>" />
	<input type="hidden" name="XID" value="<?php echo $xid?>" />
	
	<button class="submit">Save Settings</button>
</form>