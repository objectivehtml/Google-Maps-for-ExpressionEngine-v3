/**
* jQuery HTML5 Form Validator
* Author: Objective HTML (Justin Kimbrell)
* Build: 1.6.1 - February 23, 2012
* Copyright 2012 - All rights reserved
* http://objectivehtml.com
*/

/*
	Default Global Variables
*/

var _count = 0, _inline_regex = [], _options = [], _default = {
	bind: {
		before: '',
		after:	'',
		active: '',
		input: {		
			before: 'focus',
			after:  'blur',
			active: 'keyup'
		},
		select: {	
			before: 'blur',
			after:  'change',
			active: 'focus'
		}
	},
	classes: {
		before: 'before-active',
		after: 'after-active',
		active: 'active',
		focus: 'focus',
		invalid: 'invalid',
		valid: 'valid',
		placeholder: 'placeholder'
	}, 
	invalid: function(obj) {},
	valid: function(obj) {},
	before: function(obj) {},
	after: function(obj, validity) {},
	active: function(obj, validity) {},
	focus: function(obj, validity) {},
	blur: function(obj, validity) {},
	placeholder: true,
	reset: true,
	waitToValidate: true,
	focusClass: true,
	appendToParent: false,
	appendToParents: false
};


/*
	Pre Defined Regular Expressions
*/

_inline_regex['numbers'] = /^[0-9.]*$/;
_inline_regex['whole-number'] = /^[0-9]*$/;
_inline_regex['number'] = _inline_regex['numbers'];
_inline_regex['float'] = _inline_regex['numbers'];
_inline_regex['double'] = _inline_regex['numbers'];
_inline_regex['integer'] = _inline_regex['whole-number'];
_inline_regex['zipcode'] = /^\d{5}(-\d{4})?$/;
_inline_regex['social-security'] = /^(?!000)([0-6]\d{2}|7([0-6]\d|7[012]))([ -]?)(?!00)\d\d\3(?!0000)\d{4}$/;

_inline_regex['name'] = /^[a-zA-Z -\/.,0-9]*$/;
_inline_regex['address'] = /^([0-9])+([0-9a-zA-Z -\/.,#])*$/;
_inline_regex['letters'] = /^[a-zA-Z]*$/;
_inline_regex['email'] = /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/;
_inline_regex['url'] = /(ftp|http):\/\/([_a-z\d\-]+(\.[_a-z\d\-]+)+)(([_a-z\d\-\\\.\/]+[_a-z\d\-\\\/])+)*/;
_inline_regex['city'] = /^[A-Za-z. '-]+$/;
_inline_regex['us-home-phone'] = /^([0-9]{3})+\-+([0-9]{3})+\-+([0-9]{4})$/;
_inline_regex['strong-password'] = /(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/;
_inline_regex['credit-card-expiration'] = /^([0-9]{2})+\/+([0-9]{2})$/;

(function($) {
	
	$.fn.extend({	
		
		/*
			@method _validate (private)
			@arguments options [object]
			
			@description This is what does the actual validation. This function
			will return the element's validity.
			
			@return boolean
		*/
		
		_validate: function(options) {
			var $t = $(this);
			var valid = true, pattern;
			var value = $t._val(options);
			var attr_type = $t.attr('type');
					
			var attribute = {
				type: $t.attr('type'),
				pattern: $t.attr('pattern'),
				min: $t.attr('min'),
				max: $t.attr('max')
			};
						
			/*
			Checks to see if the field is required or if the field isn't required
			the value must not be blank before the main conditional tests */
			if($t.attr('required') || attr_type != 'hidden' && value != "") {	
				
				if(attr_type == 'email') attribute.pattern = 'email';
					
				//Checks to see if a regular expression is defined
				if(attribute.pattern) {
								
					/* 
					Convets the string to a regex object;
					If the pattern is a regualar express, the script
					evals the pattern string to convert it to a regex object */
					
					if(/^[\/]|[\/]$/.test(attribute.pattern))
						pattern = eval('/'+attribute.pattern+'/');
					else
						pattern = _inline_regex[attribute.pattern];
					
					//The validity is changed to false if the expression fails
					if(pattern && pattern.test(value) == false) valid = false;					
				}
								
				//If the min or max attribute is set							
				if(attribute.min || attribute.max) {
					
					/*
					If the min and max attribute are defined but the value 
					is not a number, the validity is set to false */
					if(!parseFloat(value))
						valid = false;
								
					/*
					If the min attribute is defined and the value is less,
					than the min attribute, then the validity is false.*/
					if(attribute.min && parseFloat(attribute.min) > parseFloat(value))
						valid = false;
					
					/*
					If the max attribute is defined and the value is less,
					than the max attribute, then the validity is false */
					if(attribute.max && parseFloat(attribute.max) < parseFloat(value))
						valid = false;
				}
				
				if(attr_type == 'checkbox' || attr_type == 'radio') {						
					var checked = false;
					
					$('input[name="'+$t.attr('name')+'"]').each(function() {
						if(checked == false) {
							if($(this).attr('checked')) checked = true;
							
						}
					});
									
					if(checked == false) valid = false;		
								
				} else {									
					//If the value is blank, the validity is false
					if($t._val(options) == "") valid = false;					
				}
							
			}
			
			return valid;
		},		
		
		/*
			@method _val (private)
			@argument options
			
			@description This compared the value to the placeholder attribute to get
			the true intended value of the element. 
			
			@return string
		*/
		
		_val: function(options) {
			var $t = $(this);
								
			var val = (!$t.hasClass(options.classes.placeholder) || !options.reset && $t.val() !='' && !$t.hasClass(options.classes.placeholder)) ? $t.val() : '';
			
			return val;
		},
		
		
		/*
			HTML5 Form Validator Plugin
			
			@method validate
			@arguments options [object]
			
			@description This method will bind a series of events to the elements
			defined within the selector. There are five states: before, active,
			after, valid, and invalid. The plugin will valid each field as the user
			complete the form. This class does not handle the form submission. To check
			for a valid form upon submission, use the isValid method. This method is
			chainable.
			
			@return this [object]
		*/
		
		validate: function(options) {
			
			var $t = $(this);
			var id = _count;
			
			options = $.extend(true, {}, _default, options);
			
			//Unless specified, the Placeholder method is called						
			if(options.placeholder && options.reset) {				
				$t.placeholder(options);
			}
				
			//Unless specified, the script clears any existing values that may lingering
			if(options.reset) {
				$t.val('');
				$t.attr('checked', false);
				$t.attr('selected', false);
			}
			
			return this.each(function() {
				
				var t 	  = this;
				var $t 	  = $(t);
				var input = $t;
				
				if(options.appendToParent)
					$t = $t.parent();
				else if(options.appendToParents)
					$t = $t.parents(options.appendToParents);
			
				//Bind the Before method to the Before event
				$t.addClass(options.classes.before);
				
				if(input.is('input') || input.is('textarea')) {
					options.bind.before = options.bind.input.before;
					options.bind.after  = options.bind.input.after;
					options.bind.active = options.bind.input.active;
				}
				else {
					options.bind.before = options.bind.select.before;
					options.bind.after  = options.bind.select.after;
					options.bind.active = options.bind.select.active;
				}
				
				input.bind(options.bind.before, function() {
				
					if(!$t.hasClass(options.classes.active) && !$t.hasClass(options.classes.after)) {
						$t.addClass(options.classes.before);
						$t.removeClass(options.classes.active).removeClass(options.classes.after);
					}
										
					if($t.hasClass(options.classes.before)) {
						options.before(t);
					}
				});
				
				/* If the focusClass setting is set to true, the script will
				   add the focus class to simulate the :focus psuedo-selector.
				*/			
				if(options.focusClass) {
				
					input.bind('focusin', function() {
						$t.addClass(options.classes.focus);
						options.focus(t, input._validate(options));
						
					});
					
					input.bind('focusout', function() {
						$t.removeClass(options.classes.focus);
						options.blur(t, input._validate(options));
					});
				}
				
				//Bind the Active method to the Active event
				input.bind(options.bind.active, function() {
					
					/*	Waits to trigger the validation until a value has been entered unless
						the waitToValidate option is set to false. */
						
					if(input._val(options) != "" && options.waitToValidate || options.waitToValidate == false) 
					{						
						$t.addClass(options.classes.active);
						$t.removeClass(options.classes.before).removeClass(options.classes.after);
											
						options.active(t, input._validate(options));
					}
				});
				
				//Binds the After Method
				input.bind(options.bind.after, function() {
					
					var validity = input._validate(options);
					
					/*	Waits to trigger the validation until a value has been entered unless
						the waitToValidate option is set to false. */
											
					if(input._val(options) != "" && options.waitToValidate || options.waitToValidate && $t.hasClass(options.classes.active) || options.waitToValidate == false) 
					{			
						$t.addClass(options.classes.after);
						$t.removeClass(options.classes.active).removeClass(options.classes.before);													
						if(validity) {
							options.valid(t);
							$t.addClass(options.classes.valid).removeClass(options.classes.invalid);
						} else {
							options.invalid(t);
							$t.removeClass(options.classes.valid).addClass(options.classes.invalid);
						}	
						
						options.after(t, validity);
					}
				});		
					
				//If auto focus is set to on, the field gets focus
				if($t.attr('autofocus') == "on") input.focus();
				
			});
			
			_count++;
		},
		
		/*			
			@method isValid
			@arguments callback [object] 

			@description The public method to that check to see the elements are valid. 
			The callback argument should contain a valid and/or invalid function within 
			the callback object.
			
			@return boolean
		*/
		
		isValid: function(appendClasses, options) {
												
			var valid = true;
						
			if(typeof appendClasses == "undefined")
				var appendClasses = true;					
			else if(typeof appendClasses == "object") {
				options = appendClasses;
				appendClasses = true;
			}
			
			options = $.extend(true, _default, options);
			
			this.each(function() {
				var t = this;
				var $t = $(t);
				var append = $t;
				
				if(options.appendToParent)
					append = $t.parent();
				else if(options.appendToParents)
					append = $t.parents(options.appendToParents);
			
				if($t._validate(options)) {					
					
					if(appendClasses)
						append.addClass(options.classes.valid).removeClass(options.classes.invalid);
									
					if(options.valid) options.valid(t);
				} else {
					valid = false;
					
					if(appendClasses)
						append.addClass(options.classes.invalid).removeClass(options.classes.valid);
							
					if(options) options.invalid(t);
				}
			});
						
			return valid;		
		},
		
		/*
			Placeholder Plugin
			
			@method placeholder
			@argument value [string]
			
			@description The placeholder plugin will make the placeholder attribute
			work as it does in Webkit across all modern browsers. If you want to omit
			the placeholder attribute, you may pass the value as an argument. This method
			is chainable.
			
			@return this [object]
		*/
		
		placeholder: function(value, options) {
			
			if(typeof value == "object") {
				options = value;
				value   = false;
			}
			
			return this.each(function() {	
				var $t = $(this);
				var placeholder = (value) ? value : $t.attr("placeholder");
				
				if(placeholder) {
				
					if($t.val() == "")
						$t.val(placeholder).addClass("placeholder");
					
					if($t.val() == placeholder)
						$t.addClass("placeholder");
					
					$t.bind(options.bind.before, function() {
						if($t.hasClass('placeholder')) {
							$t.val('');
						}
					});
					
					$t.bind(options.bind.active, function() {
						if($t.val() == "")
							$t.addClass("placeholder").val(placeholder);
							
						if($t.val() == placeholder)
							$t.removeClass("placeholder").val("");
						
						if($t.val() != "") $t.removeClass('placeholder');
					});
					
					$t.bind(options.bind.after, function() {
						if($t.val() == "")
							$t.val(placeholder).addClass("placeholder");
					});
				}
			});
		},
		
		setCursorPosition: function(pos) {
			if ($(this).get(0).setSelectionRange) {
				$(this).get(0).setSelectionRange(pos, pos);
			} else if ($(this).get(0).createTextRange) {
				var range = $(this).get(0).createTextRange();
				range.collapse(true);
				range.moveEnd('character', pos);
				range.moveStart('character', pos);
				range.select();
			}
		}

		
	});
	
})(jQuery);