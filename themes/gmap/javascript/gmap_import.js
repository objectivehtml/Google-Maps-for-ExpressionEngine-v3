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

function save(id, index, data, markers, status) {
	
	$.post(importItemURL, {
			schema_id: id, 
			markers: JSON.stringify(markers),
			valid_address: data.valid_address,
			existing_entry: JSON.stringify(data.existing_entry),
			status: status
		}, function(data) {
			
			$('.geocoding p').html(data.geocode);
			$('.success').html(data.total_entries_imported);
			
			if(data.total_entries_failed != null) {
				$('.failed').html(data.total_entries_failed);
			}
			
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

function geocode(index) {
	var interval     = false;
	var defaultDelay = 250;
	var delay        = defaultDelay;
	var timeout      = 10000;
					
	if(totalItems > 0 && index < totalItems && !stop) {
		
		$.post(importCheckURL, {schema_id: id}, function(data) {
		
			if(data.success) {
				var geocodeError = false;
				var markers = [];
				
				if(!data.valid_address) {
					var geocoder = new google.maps.Geocoder();
				
					geocoder.geocode({address: data.item.geocode}, function(results, status) {		
						if(status == "OK") {					
							$.each(results, function(i, result) {
								result.geometry.location.lat = result.geometry.location.lat();
								result.geometry.location.lng = result.geometry.location.lng();
								
								markers.push(result);
							});	
							$('dd.error').html('&nbsp;');	
						}
							
						if(status != 'OK') {
							$('dd.error').html(status);
							$('dl .error').show();
						}
					
						if(status == 'OVER_QUERY_LIMIT') {															
							if(!interval) {
								delay = timeout;
								
								var count = delay / 1000;
								
								$('dd.error').html('Exceeded Query Limit - Waiting... <span>'+count+'</span>');												
								interval = setInterval(function() {	
									count--;				
									$('dd.error').find('span').html(count);
									
									calculateStats();
			
									if(count == 0) {
										clearInterval(interval);
										interval = false;
										delay = defaultDelay;
									}
								}, 1000);
							}									
						}
						
						setTimeout(function() {
							save(id, index, data, markers, status);				
						}, delay);											
					});
						
				}
				else {
					save(id, index, data, markers, 'open');
				}
			}
			else {	
				$('dd.error').html(data.errors[0]);
				$('dl .error').show();
			}
		});
	}
	else if(index == totalItems) {
		$('.start').hide();
		$('.success').html(parseInt($('.success').html())+1);
		$('.items').html(parseInt($('.items').html())-1);
		$('.geocoding p').html('<i>The geocoder has finished</i>');
		$bar.progressbar({value: 100});
		stop = true;
		clearInterval(timer);
	}
	
	lastIndex = index;
}