(function($) {


var integerKeyCodes = [9 /* (tab) */ , 8 /* (delete) */ , 37,38,39,40 /* (arrows) */ , 45,91 /* (minus) */ , 48,49,50,51,52,53,54,55,56,57 /* (0-9) */ ],
	numericKeyCodes = [9 /* (tab) */ , 8 /* (delete) */ , 37,38,39,40 /* (arrows) */ , 45,91 /* (minus) */ , 46,190 /* period */ , 48,49,50,51,52,53,54,55,56,57 /* (0-9) */ ];


Matrix.bind('text', 'display', function(cell){

	var settings = $.extend({ maxl: '', multiline: 'n', spaces: 'y', content: 'any' }, cell.settings),
		$textarea = $('> *[name]', cell.dom.$td).css('overflow', 'hidden'),
		$charsLeft = $('> div.matrix-charsleft-container > div.matrix-charsleft', cell.dom.$td),
		clicked = false,
		clickedDirectly = false,
		focussed = false;

	// is this a textarea?
	if ($textarea[0].nodeName == 'TEXTAREA') {

		var updateHeight = true;

		var $stage = $('<stage />').appendTo(cell.dom.$td),
			val, textHeight;

		// replicate the textarea's text styles
		$stage.css({
			position: 'absolute',
			top: -9999,
			left: -9999,
			lineHeight: $textarea.css('lineHeight'),
			fontSize: $textarea.css('fontSize'),
			fontFamily: $textarea.css('fontFamily'),
			fontWeight: $textarea.css('fontWeight'),
			letterSpacing: $textarea.css('letterSpacing')
		});

		/**
		 * Update Stage Width
		 */
		var updateStageWidth = function(){
			$stage.width($textarea.width());
			updateTextHeight(true);
		}

		/**
		 * Update Text Height
		 */
		var updateTextHeight = function(force){
			// has the value changed?
			if (val === (val = $textarea.val()) && ! force) return;

			// update chars left notification
			if (settings.maxl) {
				var charsLeft = settings.maxl - val.length;
				$charsLeft.html(charsLeft);

				if (charsLeft < 0) {
					$charsLeft.addClass('negative');
				} else {
					$charsLeft.removeClass('negative');
				}
			}

			if (! val) {
				var html = '&nbsp;';
			} else {
				// html entities
				var html = val.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/[\n\r]$/g, '<br/>&nbsp;').replace(/[\n\r]/g, '<br/>');
			}

			if (settings.maxl) {
				html += charsLeft;
			}

			if (focussed) html += 'm';
			$stage.html(html);

			// has the height changed?
			if ((textHeight === (textHeight = $stage.height()) && ! force) || ! textHeight) return;

			// update the textarea height
			$textarea.height(textHeight);
		};

		$(window).resize(updateStageWidth);
		updateStageWidth();
	}
	else {
		updateHeight = false;
	}

	// -------------------------------------------
	//  Focus and Blur
	// -------------------------------------------

	cell.dom.$td.mousedown(function(){
		clicked = true;
	});

	$textarea.mousedown(function(){
		clickedDirectly = true;
	});

	/**
	 * Focus
	 */
	$textarea.focus(function(){
		focussed = true;

		if (updateHeight) {
			updateTextHeight(true);
			interval = setInterval(updateTextHeight, 100);
		}

		setTimeout(function(){
			if (! clickedDirectly) {
				// focus was *given* to the textarea, so we'll do our best
				// to make it seem like the entire $td is a normal text input

				var val = $textarea.val();

				if ($textarea[0].setSelectionRange) {
					var length = val.length * 2;

					if (! clicked) {
						// tabbed into, so select the entire value
						$textarea[0].setSelectionRange(0, length);
					} else {
						// just place the cursor at the end
						$textarea[0].setSelectionRange(length, length);
					}
				} else {
					// browser doesn't support setSelectionRange so try refreshing
					// the value as a way to place the cursor at the end
					$textarea.val(val);
				}
			}

			clicked = clickedDirectly = false;
		}, 0);
	});

	/**
	 * Blur
	 */
	$textarea.blur(function(){
		focussed = false;

		if (updateHeight) {
			clearInterval(interval);
			updateTextHeight(true);
		}
	});

	// -------------------------------------------
	//  Input validation
	// -------------------------------------------

	if (settings.multiline != 'y' || settings.spaces != 'y' || settings.content != 'any') {
		$textarea.keypress(function(event) {
			var keyCode = event.keyCode ? event.keyCode : event.charCode;

			if (! event.metaKey && (
				(settings.multiline != 'y' && keyCode == 13)
				|| (settings.spaces != 'y' && keyCode == 32)
				|| (settings.content == 'integer' && $.inArray(keyCode, integerKeyCodes) == -1)
				|| (settings.content == 'numeric' && $.inArray(keyCode, numericKeyCodes) == -1))) {
				event.preventDefault();
			}
		});
	}

	// -------------------------------------------
	//  Crop to max length
	// -------------------------------------------

	if (settings.maxl) {
		var $form = cell.dom.$td.closest('form');
		$form.submit(function(){
			var cropped = $textarea.val().substr(0, settings.maxl);
			$textarea.val(cropped);
		});
	}

});


})(jQuery);
