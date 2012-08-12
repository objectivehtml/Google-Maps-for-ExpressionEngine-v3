<script type="text/javascript">
	
	/*
		Total Entries 1000
		Average 1.5 entries per second
		
	*/
	
	var id         = <? echo $id ?>;
	var totalItems = <? echo $total_items?>;
	var stop       = false;
	var lastIndex  = 0;
	var $bar;
	var totalImported = 0;
	var interval = 1000;
	var totalTime = 0;
	var timer;
	var average;
	var timeRemaining;
	var itemsRemaining;
	
	function calculateStats() {
		if(totalTime > 0) {
			average = round((totalImported / totalTime) * 1000, 3);
			timeRemaining = secondsToTime(itemsRemaining / average);
			
			$('.average').html(average + ' avg entries per second');
			$('.time-remaining').html(timeRemaining.string());
		}
		else {
			$('.average').html('Estimating...');
			$('.time-remaining').html('Estimating...');
		}
	}
	
	function round(num, dec) {
		return Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	}

	function secondsToTime(secs) {
	    var hours = Math.floor(secs / (60 * 60));
	   
	    var divisor_for_minutes = secs % (60 * 60);
	    var minutes = Math.floor(divisor_for_minutes / 60);
	 
	    var divisor_for_seconds = divisor_for_minutes % 60;
	    var seconds = Math.ceil(divisor_for_seconds);
	   
	    var obj = {
	        "h": hours,
	        "m": minutes,
	        "s": seconds
	    };
	    
	    obj.plural = function(num, string) {
		    if(num > 1) {
			    string += 's';
		    }
		    
		    return (num > 0 ? num + ' ' + string + ' ' : (string != 'second' ? '' : '0 seconds'));
	    }
	    
	    obj.string = function() {
	    
		    return obj.plural(obj.h, 'hour') + obj.plural(obj.m, 'minute') + obj.plural(obj.s, 'second');
	    }
	    
	    return obj;
	}
	
	function startTimer() {
	
		$('.average').html('Estimating...');
		$('.time-remaining').html('Estimating...');
		$('.run-time').html('Estimating...');
		
		timer = setInterval(function() {
			var runTime = secondsToTime(totalTime / 1000);
			
			if(!stop) {
				$('.run-time').html(runTime.string());
				
				totalTime += interval;	
			}
			
		}, interval);
	}
	
	function geocode(index) {
	
		if(totalItems > 0 && index < totalItems && !stop) {
			
			$.post('<? echo $import_check_url?>', {scheme_id: id}, function(data) {
			
				var geocodeError = false;
				var markers = [];
				
				if(!data.valid_address)
				{
					var geocoder = new google.maps.Geocoder();
									
					geocoder.geocode({address: data.item.geocode}, function(results, status) {
						if(status == 'OK' || 'ZERO_RESULTS') {
							
							if(typeof results == "array") {							
								$.each(results, function(i, result) {
									result.geometry.location.lat = result.geometry.location.lat();
									result.geometry.location.lng = result.geometry.location.lng();
									
									markers.push(result);
								});								
							}
							
							$.post('<? echo $import_item_url?>', {
									schema_id: id, 
									markers: JSON.stringify(markers),
									valid_address: data.valid_address
								}, function(data) {
									
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
									
									totalImported++;
									itemsRemaining--;
									
									$bar.progressbar({value: index / totalItems * 100});				
									
									calculateStats();
									
									geocode(index+1);				
								}
							);
							
						}
						else {
							geocodeError = status;
							
							alert(geocodeError);
						}
					});
				}
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
						
						itemsRemaining = data.items_in_pool;
						
						$('.last-ran').html(data.importer_last_ran);
						$('.total-runs').html(data.importer_total_runs);
						$('.progress-bar, .geocoding').show();
						
						startTimer();
						geocode(lastIndex);
					}
				);
			}
			else {
				clearInterval(timer);
        
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
	<dt>Importer Total Runs</dt>
	<dd class="total-runs"><? echo isset($stats->importer_total_runs) ? $stats->importer_total_runs : 'N/A'?></dd>
	<dt>Avg Entries per Second</dt>
	<dd class="average"></dd>
	<dt>Time Remaining</dt>
	<dd class="time-remaining"></dd>
	<dt>Total Run Time</dt>
	<dd class="run-time"></dd>
	<dt>Importer Last Ran</dt>
	<dd class="last-ran"><? echo isset($stats->importer_last_ran) ? date('Y-m-d h:i A', $stats->importer_last_ran) : 'N/A'?></dd>
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