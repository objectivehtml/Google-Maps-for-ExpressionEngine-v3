<script type="text/javascript">
	
	var user = <?=json_encode($user)?>;
	
	window.opener.callback(user);	
	window.close();
	window.opener.focus()
	
</script>

<h2>Processing...</h2>