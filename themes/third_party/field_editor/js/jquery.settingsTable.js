/**
 * jQuery settingsTable
 *
 * @requires jQuery
 * @requires jQuery UI
 */
(function($){
	$.settingsTable = {
		deleteRow: function($table, i) {
			var options = $table.data("settingsTable-options");
			
			var row = $table.find(options.rowSelector).eq(i);
			
			//you can skip confirmation by setting deleteConfirm to null
			if (options.deleteConfirm && ! confirm(options.deleteConfirm)) {
				return;
			}
			
			if ($.settingsTable.callback(options.beforeDelete, $table, i, row) === false) {
				return;
			}
			
			row.remove();
			
			$.settingsTable.resetRows($table);
			
			if ( ! options.allowEmptyTable && $table.find(options.rowSelector).length === 0) {
				$.settingsTable.addRow($table);
			}
			
			$.settingsTable.callback(options.afterDelete, $table);
		},
		addRow: function($table, i) {
			var options = $table.data("settingsTable-options");
			
			if ($.settingsTable.callback(options.beforeAdd, $table) === false) {
				return;
			}
			
			var addToEnd = (i === undefined || i === null);
			
			//add to the end of the table
			if (addToEnd) {
				i = $table.find(options.rowSelector).length;
			}
			
			var row = $(options.blankRow.replace(/INDEX/g, i));
			
			if (addToEnd) {
				row.appendTo($table);
			}
			else {
				row.insertAfter($table.find(options.rowSelector).eq(i -1));
			}
			
			$.settingsTable.resetRows($table);
			
			$.settingsTable.callback(options.afterAdd, $table, i, row);
		},
		resetRows: function($table) {
			var options = $table.data("settingsTable-options");
			
			if ($.settingsTable.callback(options.beforeReset, $table) === false) {
				return;
			}
			
			$table.find(options.rowSelector).each(function(i){
				$(this).find(":input").each(function(){
					$(this).attr("name", $(this).attr("name").replace(/^(.*?)\[.*?\](.*?)$/, "$1["+i+"]$2"));
				});
			});
			
			$.settingsTable.callback(options.afterReset, $table);
		},
		callback: function(func) {
			return (func && typeof func === "function") ? func.apply(null, Array.prototype.slice.call(arguments, 1)) : true;
		}
	};
	
	$.fn.settingsTable = function(options) {
		options = $.extend({
			rowSelector: "tr",
			addSelector: ".addRow",
			addFullSelector: null,
			deleteSelector: ".deleteRow",
			dragHandle: ".handle",
			deleteConfirm: "Are you sure you want to delete this row?",
			beforeReset: null,
			afterReset: null,
			beforeDelete: null,
			afterDelete: null,
			beforeAdd: null,
			afterAdd: null,
			blankRow: "",
			allowEmptyTable: false
		}, options);
		
		if ( ! options.blankRow) {
			throw("You did not provide a blankRow.");
		}
		
		return this.each(function() {
			var $table = $(this);
			
			$table.data("settingsTable-options", options);
			
			$table.sortable({
				items: options.rowSelector,
				handle: options.dragHandle,
				stop: function() {
					$.settingsTable.resetRows($table);
				}
			});
			
			if (options.deleteSelector != null) {
				$table.find(options.deleteSelector).live("click", function(e){
					e.preventDefault();
					
					var i = $table.find(options.deleteSelector).index(this);
					
					$.settingsTable.deleteRow($table, i);
				});
			}
			
			if (options.addFullSelector != null) {
				$(options.addFullSelector).live("click", function(e) {
					e.preventDefault();
					
					$.settingsTable.addRow($table);
				});
			}
			
			if (options.addSelector != null) {
				$table.find(options.addSelector).live("click", function(e) {
					e.preventDefault();
					
					var i = $table.find(options.addSelector).index(this) + 1;
					
					$.settingsTable.addRow($table, i);
				});
			}
		});
	};
})(jQuery);
