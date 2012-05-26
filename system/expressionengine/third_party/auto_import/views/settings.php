<style type="text/css">

.box {
	padding: 1em;
	background: rgb(211, 216, 219);
	background: rgba(0, 0, 0, .1);
	border: 1px solid rgb(175, 178, 180);
	margin-bottom: 2em;
}

.box a {
	color: rgb(42, 57, 64) !important;
}

</style>

<h3>Upload URL</h3>

<div class="box">
	<a href="<?=$import_url?>"><?=$import_url?></a>
</div>

<form method="post" action="<?=$action?>">
	<?=$settings?>

	<input type="hidden" name="return" value="<?=$return?>" />
	<button class="submit">Save Settings</button>
</form>