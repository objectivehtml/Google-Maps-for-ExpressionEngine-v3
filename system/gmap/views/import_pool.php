<h2>Import Pool</h2>

<dl>
	<dt>Schema ID</dt>
	<dd><?php echo isset($stats->schema_id) ? $stats->schema_id : 'N/A'?>
	<dt>Schema Name</dt>
	<dd><?php echo isset($stats->schema_name) ? $stats->schema_name : 'N/A'?></dd>
	<dt>Items in Pool</dt>
	<dd class="items"><?php echo isset($stats->items_in_pool) ? $stats->items_in_pool : 'N/A'?></dd>
	<dt>Total Entries Imported</dt>
	<dd class="success"><?php echo isset($stats->total_entries_imported) ? $stats->total_entries_imported : 'N/A'?></dd>
	<dt>Total Entries Failed</dt>
	<dd class="failed"><?php echo isset($stats->total_entries_failed) ? $stats->total_entries_failed : 'N/A'?></dd>
	<dt class="error">Last Error</dt>
	<dd class="error">&nbsp;</dd>
	<dt>Importer Total Runs</dt>
	<dd class="total-runs"><?php echo isset($stats->importer_total_runs) ? $stats->importer_total_runs : 'N/A'?></dd>
	<dt>Avg Entries per Second</dt>
	<dd class="average">N/A</dd>
	<dt>Time Remaining</dt>
	<dd class="time-remaining">N/A</dd>
	<dt>Total Run Time</dt>
	<dd class="run-time">N/A</dd>
	<dt>Importer Last Ran</dt>
	<dd class="last-ran"><?php echo !empty($stats->importer_last_ran) ? date('Y-m-d h:i A', $stats->importer_last_ran) : 'N/A'?></dd>
</dl>

<p class="start"><button href="#" class="submit">Start Import</button></p>

<div class="geocoding">

	<h3>Geocoding</h3>

	<p></p>

</div>

<div class="progress-bar"></div>

<style type="text/css">
	
	
	dl {
		font-size: 1.25em;
		margin-bottom: 1em;
	}
	
	dt {
		float: left;
		width: 15em;	
		clear: left;
		margin-bottom: .5em;
	}
	
	dd {	
		margin-bottom: .5em;
		color: rgb(95, 108, 116) !important;
	}
	
	p.start {
		margin: 2em 0 2em 0 !important;
		display: block;
		clear: both;	
	}
	
	.progress-bar {
		width: 50%;
		display: none;
	}
	
	.geocoding {
		display: none;
	}
	
	.error { display: none; }

</style>