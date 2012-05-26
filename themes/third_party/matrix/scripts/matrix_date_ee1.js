(function($) {


Matrix.bind('date', 'display', function(cell){

	// come up with a unique ID for the calendar
	var calId = cell.field.id+'_'+cell.row.id+'_'+cell.col.id+'_datepicker';

	// create the calendar
	window[calId] = new calendar(calId, new Date(), false);

	var $cal = $(window[calId].write()).appendTo(cell.dom.$td).css({ position: 'absolute', marginTop: '3px' }).hide(),
		$input = $('> input', cell.dom.$td).attr('id', calId),
		inputHasFocus = false,
		calHasFocus = false;

	var fadeoutCal = function(){
		setTimeout(function(){
			if (! inputHasFocus && ! calHasFocus) {
				$cal.fadeOut('fast');
				$cal.removeAttr('tabindex');
			}
		}, 1);
	};

	$input.focus(function(){
		inputHasFocus = true;
		$cal.fadeIn('fast');
		$cal.attr('tabindex', '0');
	});

	$input.blur(function(){
		inputHasFocus = false;
		fadeoutCal();
	});


	$cal.focus(function(){
		calHasFocus = true;
	});

	$cal.blur(function(){
		calHasFocus = false;
		fadeoutCal();
	});

	$cal.click(function(event){
		if (event.target.className == 'caldayselected') {
			$cal.blur();
		}
	});

});


})(jQuery);
