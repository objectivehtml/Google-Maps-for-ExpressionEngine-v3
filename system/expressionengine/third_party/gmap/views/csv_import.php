<script type="text/javascript" src="<?=$interface_builder?>"></script>
<script type="text/javascript">
	$(document).ready(function() {
		
		var IB = new InterfaceBuilder();
	
	});
</script>

<form method="post" action="<?=$action?>">
	<?=$settings?>

	<input type="hidden" name="return" value="<?=$return?>" />
	<button class="submit">Save Settings</button>
</form>