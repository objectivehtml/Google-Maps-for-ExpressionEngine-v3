<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>

<script type="text/javascript">
	    
	var id         = <? echo $id ?>;
	var totalItems = <? echo $total_items?>;
	var stop       = false;
	var lastIndex  = 0;
	var $bar;
	
	function geocode(index) {
		if(totalItems > 0 && index < totalItems && !stop) {
				
			$.get('<? echo $import_item_url?>', {schema_id: id}, function(data) {
			
				//alert(data);
				
				$('.geocoding p').html(data.geocode);
				$('.success').html(data.total_entries_imported);
				$('.failed').html(data.total_entries_failed);
				$('.items').html(data.items_in_pool);
			
				if(data.errors) {
					
					var errors = '';
					
					$.each(data.errors, function(name, value) {
						if(value) {
							if(value == "The following field is required:") {
								errors += value + ' ' + name + '<br>';
							}
							else {
								errors += value + '<br>';
							}
						}
					});
										
					$('dd.error').html(errors);
					$('dl .error').show();
				}
				
				$bar.progressbar({value: index / totalItems * 100});				
				geocode(index+1);				
			});
		}
		else if(index == totalItems) {
			$('.start').hide();
			$('.success').html(parseInt($('.success').html())+1);
			$('.items').html(parseInt($('.items').html())-1);
			$('.geocoding p').html('<i>The geocoder has finished</i>');
			$bar.progressbar({value: 100});
		}
		
		lastIndex = index;
	}
		
	$(document).ready(function() {
		
		$bar = $('.progress-bar');
		$bar.progressbar({value: 0});
				
		$('.start .submit').click(function() {
			
			var $t = $(this);
			
			if($t.html() == 'Start Import') {
				stop = false;
				$t.html('Stop Import');
				
				$.get('<? echo $import_start_url?>',
					{
						id: id
					},
					function(data) {
						$('.last-ran').html(data.importer_last_ran);
						$('.total-runs').html(data.importer_total_runs);
						$('.progress-bar, .geocoding').show();
						
						geocode(lastIndex);
					}
				);
			}
			else {
				$t.html('Start Import');
				stop = true;
			}
			
			return false;
			
		});
	});
	
</script>

<h2>Import Pool</h2>

<dl>
	<dt>Schema ID</dt>
	<dd><? echo isset($stats->schema_id) ? $stats->schema_id : 'N/A'?>
	<dt>Schema Name</dt>
	<dd><? echo isset($stats->schema_name) ? $stats->schema_name : 'N/A'?></dd>
	<dt>Items in Pool</dt>
	<dd class="items"><? echo isset($stats->items_in_pool) ? $stats->items_in_pool : 'N/A'?></dd>
	<dt>Total Entries Imported</dt>
	<dd class="success"><? echo isset($stats->total_entries_imported) ? $stats->total_entries_imported : 'N/A'?></dd>
	<dt>Total Entries Failed</dt>
	<dd class="failed"><? echo isset($stats->total_entries_failed) ? $stats->total_entries_failed : 'N/A'?></dd>
	<dt class="error">Last Error</dt>
	<dd class="error"></dd>
	<dt>Importer Last Ran</dt>
	<dd class="last-ran"><? echo isset($stats->importer_last_ran) ? date('Y-m-d h:i A', $stats->importer_last_ran) : 'N/A'?></dd>
	<dt>Importer Total Runs</dt>
	<dd class="total-runs"><? echo isset($stats->importer_total_runs) ? $stats->importer_total_runs : 'N/A'?></dd>
</dl>

<p class="start"><a href="#" class="submit">Start Import</a></p>

<div class="geocoding">

	<h3>Geocoding</h3>

	<p></p>

</div>

<div class="progress-bar"></div>

<style type="text/css">
	
	
	dl {
		font-size: 1.25em;
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