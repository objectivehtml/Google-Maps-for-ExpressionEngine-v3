(function($) {


Matrix.bind('date', 'display', function(cell){

	var $input = $('> input', cell.dom.$td),
		date = new Date(),
		hours = date.getHours(),
		minutes = date.getMinutes();

	if (minutes < 10) minutes = '0'+minutes;

	if (hours > 11) {
		hours = hours - 12;
		var meridiem = " PM";
	} else {
		var meridiem = " AM";
	}

	var time = " \'"+hours+':'+minutes+meridiem+"\'";

	$input.datepicker({
		dateFormat: $.datepicker.W3C + time,
		defaultDate: new Date(cell.settings.defaultDate)
	});

});


})(jQuery);
