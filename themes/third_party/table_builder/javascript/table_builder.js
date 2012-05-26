
if(!Table_Builder) {
	var Table_Builder;
}

Table_Builder = function(settings) {

	Table_Builder.instances.push(this);

	var table     = this;
	var table_id  = '#table_builder_'+settings.id;
	var $wrapper  = $(table_id);
	var $document = $(document);
	var $body     = $('body');

	/*------------------------------------------
	 *	Properties
	/* -------------------------------------- */

	table.id                     = settings.id;
	table.name                   = settings.name;
	table.rows                   = [];
	table.dragging 				 = false;
	table.rowCount				 = 0;
	table.activeMenu             = false;
	table.celltypes              = settings.celltypes;
	table.columns                = [];
	table.insertIndex            = false;
	
	//table.minRows              = minRows ? minRows : false;
	//table.maxRows              = maxRows ? maxRows : false;
	//table.isNewColumn          = false;
	//table.isAddColumnMenuOpen  = false;
	//table.isEditColumnMenuOpen = false;
	//table.isColumnMenuOpen     = false;
	//table.currentColumnMenu    = false;

	table.override               = false;
	table.editColumnObj          = false;
	table.editColumnIndex        = false;
	table.editRowObj             = false;
	table.editRowIndex           = false;
	table.totalNewRows           = 0;
	table.totalNewCols           = 0;

	table.setUI = function() {

		table.ui              = {
			wrapper: $wrapper,
			table: $wrapper.find('table'),
			thead: $wrapper.find('table thead tr'),
			tbody: $wrapper.find('table tbody'),
			rows: $wrapper.find('table tbody tr'),
			content: $wrapper.find('.ui-tb-content'),
			field: {
				columnName: $wrapper.find('input[name="column_name"]'),
				columnTitle: $wrapper.find('input[name="column_title"]'),
				columnType: $wrapper.find('select[name="column_type"]')
			},
			menu: {
				addColumn: $wrapper.find('.ui-tb-add-column-menu'),
				editColumn: $wrapper.find('.ui-tb-edit-column-menu'),
				empty: $wrapper.find('.ui-tb-empty'),
				deleteColumn: $wrapper.find('.ui-tb-delete-column-menu'),
				deleteRow: $wrapper.find('.ui-tb-delete-row-menu'),
				column: $wrapper.find('.ui-tb-column-menu'),
				editClass: $wrapper.find('.ui-tb-class-menu'),
				row: $wrapper.find('.ui-tb-row-menu'),
				loadPreset: $wrapper.find('.ui-tb-load-preset-menu'),
				savePreset: $wrapper.find('.ui-tb-save-preset-menu'),
				activeMenu: $wrapper.find('.ui-tb-active-menu'),
				saveSuccess: $wrapper.find('.ui-tb-save-success')
			},
			button :{
				cancel: $wrapper.find('.ui-tb-cancel'),
				addColumn: $wrapper.find('a[href="#tb-add-column"]'),
				deleteColumn: $wrapper.find('.ui-tb-delete-column-menu a[href="#delete-column"]'),
				deletePreset: $wrapper.find('a[href="#tb-delete-preset"]'),
				submitAddColumn: $wrapper.find('.ui-tb-add-column-menu button'),
				submitEditColumn: $wrapper.find('.ui-tb-edit-column-menu button'),
				submitSavePreset: $wrapper.find('.ui-tb-save-preset-menu button'),
				submitLoadPreset: $wrapper.find('.ui-tb-load-preset-menu button'),
				addRow: $wrapper.find('a[href="#tb-add-row"]'),
				editClass: $wrapper.find('a[href="#edit-class"]'),
				deleteRow: $wrapper.find('.ui-tb-row-menu a[href="#delete-row"]'),
				loadPreset: $wrapper.find('a[href="#tb-load-preset"]'),
				savePreset: $wrapper.find('a[href="#tb-save-preset"]')
			}
		};

	}

	table.setUI();

	table.totalRows    = table.ui.tbody.find('tr').length;
	table.totalColumns = table.ui.thead.find('th').length - 1;
	table.totalColumns = table.totalColumns < 0 ? 0 : table.totalColumns;

	/*------------------------------------------
	 *  Methods
	/* -------------------------------------- */

	table.init = function(refresh) {
		table.ui.table.parents('form').attr('novalidate', 'novalidate');
		table.ui.table = $wrapper.find('table');
		table.ui.tbody = table.ui.table.find('tbody');
		table.ui.rows = table.ui.tbody.find('tr');
		table.ui.thead.find('.ui-tb-resizable').resizable({
			handles: 'e',
			resize: function(event, ui) {
				if(table.hideMenu) {
					table.hideMenu();
				}
				table.inactive();
				table.resizeCol(this, event);
			}
		});

		table.reorder();
		table.drag();
	}

	table.addToHead = function(html) {

		var html = $(html);
		
		table.ui.thead = table.ui.table.find('thead tr');

		if(table.ui.thead.length == 0) {
			table.ui.table.append('<thead><tr><th class="ui-tb-id-column"></th></tr></thead>');
			table.ui.thead = table.ui.table.find('thead tr');
		}

		if(table.insertIndex === false) {
			table.ui.thead.append(html);
		}
		else {
			var tr = table.ui.thead.find('th');
			$(html).insertAfter(tr[table.insertIndex]);
		}

		table.init(true);

		return html;
	}

	table.addToBody = function(html, index, insertBefore) {
	
		html = $(html);

		if(table.ui.tbody.length == 0) {
			table.ui.table.append('<tbody></tbody>');
			table.ui.tbody = table.ui.table.find('tbody');
		}

		var insertObj = table.ui.tbody.find('tr').get(index);

		if(insertObj && index != table.totalRows) {
			if(insertBefore)
				html.insertBefore(insertObj);
			else
				html.insertAfter(insertObj);
		}
		else {
			table.ui.tbody.append(html);
		}

		table.drag();
		table.totalRows++;

		return html;
	}

	table.deletePreset = function(callback) {
		var selected = table.ui.menu.activeMenu.find('.ui-tb-active');
		var id = selected.data('id');

		$.post(settings.url.deletePreset, {id: id}, function(data) {
			selected.parent().fadeOut();
			if(typeof callback == "function") {
				callback();
			}
		});
	}

	table.drag = function() {
		table.ui.tbody.tableDnD({
			onDragClass: 'ui-tb-dragging',
			dragHandle: 'ui-tb-drag-handle',
			onDragStart: function(t, row) {
				table.dragging = true;

				if(table.hideMenu) {
					table.hideMenu();
				}
				
				if (window.getSelection) {
			        window.getSelection().removeAllRanges();
			    } else if (document.selection) {
			        document.selection.empty();
			    }
			},
			onDrop: function(dragTable, dragRow) {
				setTimeout(function() {
					table.dragging = false;
				}, 100);
				table.reorder();
			}
		});
	}

	table.inactive = function() {
		table.ui.wrapper.find('.active').removeClass('active');
	}

	table.loadPreset = function() {
		if(table.isValid(table.ui.menu.loadPreset)) {
			var id = table.ui.menu.activeMenu.find('.ui-tb-active').data('id');

			$.get(settings.url.loadPreset+'&id='+id, function(data) {

				$.each(data.celltypes, function(i, celltype) {
					table.celltypes[i] = celltype;
				});

				var name = table.name;

				table.name = 'field_id_'+data.field_id;
				table.hideMenu()
				table.ui.content.html(data.html);
				table.columns = [];
				table.rows    = [];

				table.setUI();
	
				var count = 1;

				$.each(data.columns, function(i, column) {

					var obj   = new Table_Builder.Column(table, column.title, column.name, column.type);
					
					obj.ui.th = $(table.ui.thead.find('th').get(count));

					obj.ui.th.val(JSON.stringify(value));
					obj.ui.column = table.ui.wrapper.find('input[name="'+table.name+'[column]['+column.name+']"]');
					obj.ui.column.attr('name', name+'[column]['+column.name+']');

					var value = JSON.parse(obj.ui.column.val());

					value.celltype.field_id = table.id;

					obj.ui.column.val(JSON.stringify(value));

					table.columns.push(obj);

					count++;	
				});

				table.totalColumns = table.columns.length;

				if(data.rows != null) {

					$.each(data.rows, function(i, row) {

						var obj = new Table_Builder.Row(table, $(table.ui.tbody.find('tr').get(i)));

						table.rowCount++;

						obj.ui.row = table.ui.wrapper.find('input[name="'+table.name+'[row]['+i+']"]');
						obj.ui.row.attr('name', name+'[row]['+i+']');

						obj.index = i;

						var value = JSON.parse(obj.ui.row.val());

						value.rowId = false;

						obj.ui.row.val(JSON.stringify(value));

						obj.ui.tr.find('td').each(function(i, td) {
							if(i > 0) {
								var column = table.columns[i-1];
								var cell = new Table_Builder.Cell(obj, column);

								cell.ui.td = $(td);

								obj.cells.push(cell);
							}
						});

						table.rows.push(obj);
					});

					table.totalRows = data.rows.length;
				}
				else {
					table.totalRows = 0;
				}

				table.name = name;
				table.init();
				table.reorder();
				table.ui.wrapper.append('<input type="hidden" name="'+table.name+'[preset]" value="1" />');
			});
		}
	}

	table.savePreset = function() {
		if(table.isValid(table.ui.menu.savePreset)) {

			var name = table.ui.menu.savePreset.find('input[name="preset_name"]').val();
			var field_name = table_id.replace('#', '');

			var fields = {
				field_name: table.name,
				field_id: settings.id,
				entry_id: settings.entry_id,
				channel_id: settings.channel_id,
				name: name,
				celltypes: JSON.stringify(table.celltypes)
			};

			$('input[name^="field_id_'+settings.id+'"]').each(function() {
				var $t = $(this);
				var name = $t.attr('name').replace('field_id_'+settings.id, 'data');
				var value = $t.val();

				fields[name] = value;
			});

			$.post(settings.url.savePreset, fields, function(data) {
				
				if(table.ui.menu.activeMenu.length == 0) {
					table.ui.menu.activeMenu         = $('<ul class="ui-tb-active-menu"></ul>');
					table.ui.button.submitLoadPreset = $('<button type="button" class="ui-tb-button ui-tb-disabled">Load Preset</button>');

					table.ui.menu.loadPreset.append(table.ui.menu.activeMenu);
					table.ui.menu.loadPreset.append(table.ui.button.submitLoadPreset);

					table.ui.button.submitLoadPreset.click(function() {
						table.loadPreset();
					});
				}

				table.ui.menu.activeMenu.append('<li><a href="#" data-id="'+data.preset_id+'">'+data.name+'</a></li>');
				
				table.hideMenu();

				var position = {
					my: 'left top',
					at: 'left bottom',
					offset: '0 10'
				};

				table.showMenu(table.ui.menu.saveSuccess, table.ui.button.loadPreset, position, {
					show: function() {
						setTimeout(function() {
							table.hideMenu();
						}, 3000);
					}
				});
			});

		}
	}

	table.reorder = function() {
		
		var newOrder = [];
		var count  = 0;

		table.totalRows = 0;

		if(table.totalColumns == 0) {
			return;
		}

		table.ui.tbody.find('tr').each(function(i, row) {

			var cell  = $(row).find('.ui-tb-id-column');

			cell.find('.ui-tb-number').html(i+1);
			table.totalRows++;
		});

		$.each(table.rows, function(i, row) {
			if(row) {
				$.each(row.cells, function(x, cell) {
					cell.ui.td.find('*[role="cell"]').attr('name', table.name+'[cell]['+row.ui.tr.index()+']['+table.columns[x].name+']');
				});
				
				var attributes = JSON.parse(row.ui.row.val());
				attributes.index = row.ui.tr.index();
				row.ui.row.val(JSON.stringify(attributes));
				row.ui.row.attr('name', table.name+'[row]['+row.ui.tr.index()+']');
			}
			//row.ui.row.
		});

		if(table.totalColumns > 0) {
			table.ui.button.addRow.show();
			table.ui.button.savePreset.show();
		}
		else {
			table.ui.button.addRow.hide();
			table.ui.button.savePreset.hide();
		}
	}

	// Row Methods

	table.addRow = function(index, insertBefore) {

		if(typeof index == "undefined") {
			var index = table.totalRows;
		}
		else {
			if(index < 0) {
				index = 0;
			}
		}

		if(table.totalColumns == 0) {
			alert('You must add a column before adding a row.');
			return;
		}

		if(table.hideMenu) {
			table.hideMenu();
		}

		var row = new Table_Builder.Row(table, $('<tr><td class="ui-tb-id-column"><div class="ui-tb-relative"><span class="ui-tb-drag-handle"></span><div class="ui-tb-number">'+(table.totalRows+1)+'</div></div></td></tr>'));

		table.addToBody(row.ui.tr, index, insertBefore);

		table.ui.wrapper.append(row.ui.row);

		$.each(table.columns, function(i, column) {
			var cell = new Table_Builder.Cell(row, column);
			
			row.addCell(cell);
		});

		row.json();

		table.rows.push(row);

		table.rowCount++;

		table.reorder();

	}

	table.deleteRow = function(index) {

	}

	// Column Methods

	table.addColumn = function() {
		var title = table.ui.field.columnTitle.val();
		var name  = table.ui.field.columnName.val();
		var field  = table.ui.field.columnType.val();

		if(table.isValid(table.ui.menu.addColumn)) {
			if(!table.isDuplicateColumn(name, field)) {

				if(!title || title == '') {
					title = 'Col '+(table.totalColumns + 1);
				}

				var column = new Table_Builder.Column(table, title, name, field);

				table.addToHead(column.ui.th);

				table.ui.wrapper.append(column.ui.column);
				
				if(table.insertIndex === false) {
					table.columns.push(column);
				}
				else {
					table.columns.insert(column, table.insertIndex);
				}

				if(table.totalRows > 0) {
					$.each(table.rows, function(i, row) {

						var cell = new Table_Builder.Cell(row, column);
						
						if(table.insertIndex === false) {
 							row.ui.tr.append(cell.ui.td);
							row.cells.push(cell);
 						}
 						else {
							var tr = row.ui.tr.find('td');
							row.cells.insert(cell, table.insertIndex);
							$(cell.ui.td).insertAfter(tr[table.insertIndex]);
 						}

 						row.json();
					});
				}

				table.totalColumns++;

				table.ui.thead = $wrapper.find('thead tr');
				table.ui.menu.addColumn.find('input, select').val('');
				table.reorder();
				table.hideMenu();
			}
		}

		table.insertIndex = false;
	}

	table.editColumn = function(title, name, type) {
		if(table.isValid(table.ui.menu.editColumn)) {
			
			var data = {
				title: table.ui.menu.editColumn.find('input[name="column_title"]').val(),
				name: table.ui.menu.editColumn.find('input[name="column_name"]').val(),
				type: table.ui.menu.editColumn.find('*[name="column_type"]').val()
			}

			table.editColumnObj.edit(data);
		}
	}

	table.isValid = function(validate) {
		if(validate) {
			return validate.find('.validate').isValid({
				invalid: function(obj) {
					 validate.find('.invalid:first').focus();
				}
			});
		}

		return false;
	}

	table.isDuplicateColumn = function(name, field) {
		
		var isValid = false;

		$.each(table.columns, function(i, column) {
			if(column.name == name || column.field == field) {
				isValid = true;
				table.ui.field.columnName.addClass('invalid');
				alert('You can\'t have two columns with the same name');
			}
		});

		return isValid;
	}

	table.deleteColumn = function() {
		table.editColumnObj.delete();
		table.hideMenu();
	}

	table.resizeCol = function(obj, event) {
		var index  = $(obj).index()-1;
		var column = table.columns[index];

		column.width = $(obj).width();
		column.update();
	}

	table.showMenu = function(menu, of, position, callback) {

		if(table.dragging) {
			return;
		}

		var _default = {
			my: 'center top',
			bottom: 'center bottom',
			offset: '0 0'
		}

		if(!position) {
			var position = _default;
		}

		if(typeof position == "function") {
			callback = position;
			position = _default;
		}

		if(!callback) {
			callback = {};
		}

		if(!position.my)     position.my     = _default.my;
		if(!position.bottom) position.bottom = _default.bottom;
		if(!position.offset) position.offset = _default.offset;

		table.ui.wrapper.find('.validate').validate();

		table.callback(callback.click)

		if(table.hideMenu) {
			table.hideMenu();
		}

		if(menu.css('display') == 'none') {
			
			table.activeMenu = menu;
			table.inactive();

			menu.find('.validate:first').focus();
			menu.find('.validate').val('').removeClass('invalid');

			table.callback(callback.show, menu);

			menu.fadeIn(function() {
				if(typeof callback.visible == "function") {
					menu.find('.validate:first').focus();

					table.callback(callback.visible, menu);
				}
			});

			menu.position({
				my: position.my,
				at: position.at,
				offset: position.offset,
				of: of,
				collision: position.collision ? position.collision : 'none'
			});

			if(!table.hideMenu || table.override) {

				menu.unbind('keypress').keypress(function(e) {
					if(e.keyCode == 13) {
						if(table.isValid(menu)) {
							table.callback(callback.valid, menu);
						}
						
						e.preventDefault();

						return false;
					}
					if(e.keyCode == 27) {
						if(table.hideMenu) {
							table.hideMenu(callback);
						}

						e.preventDefault();
					}
				});

				table.override = false;
			}

			table.hideMenu = function(secondCallback) {
				
				if(callback) {
					table.callback(callback.hide, menu);

					menu.fadeOut(function() {
						table.callback(callback.hidden, menu);

						if(typeof secondCallback == "function") {
							secondCallback(menu);
						}
					});
				}
				else {
					menu.fadeOut(function() {
						if(typeof secondCallback == "function") {
							secondCallback(menu);
						}
					});
				}
			}
		}
		else {
			menu.fadeOut(function() {
				table.callback(callback.hide, menu);
			});
		}
	}

	table.callback = function(callback, menu) {
		if(typeof callback == "function") {
			callback(menu);
		}
	}

	/*------------------------------------------
	 *	Events
	/* -------------------------------------- */

	table.ui.table.find('td input, td textarea, td select').live('focus', function() {
		if(table.hideMenu) {
			table.hideMenu();
		}

		return false;
	});

	table.ui.button.addColumn.click(function(event) {
		var position = {
			my: 'left top',
			at: 'left bottom',
			offset: '0 10'
		};

		table.override = true;

		table.showMenu(table.ui.menu.addColumn, $(this), position, {
			visible: function(menu) {
				table.ui.field.columnTitle.focus();

				menu.find('button').unbind('click').click(function() {
					table.addColumn();	
				});
			},
			valid: function(menu) {
				menu.find('button').click();
			}
		});

		return false;
	});

	table.ui.button.loadPreset.click(function() {
		
		var position = {
			my: 'left top',
			at: 'left bottom',
			offset: '0 10'
		};

		var activeClass   = 'ui-tb-active';
		var disabledClass = 'ui-tb-disabled';

		table.showMenu(table.ui.menu.loadPreset, $(this), position, {
			show: function() {
				$body.css('overflow', 'hidden');
				table.ui.menu.loadPreset.find('.'+activeClass).removeClass(activeClass);
				table.ui.button.deletePreset.hide();
				table.ui.button.submitLoadPreset.removeClass(activeClass);
			},
			hide: function() {
				$body.css('overflow', 'auto');
				table.ui.button.submitLoadPreset.addClass(disabledClass);
			}
		});

		return false;
	});

	table.ui.button.submitLoadPreset.click(function() {
		
		if(!$(this).hasClass('ui-tb-disabled')) {
			table.loadPreset();
		}

		return false;
	});

	table.ui.menu.activeMenu.find('a').live('click', function() {

		var $t          = $(this);
		var activeClass = 'ui-tb-active';
		var button 		= table.ui.menu.activeMenu.parent().find('a[href="#tb-load-preset"]');

		if(!$t.hasClass(activeClass)) {
			table.ui.menu.activeMenu.find('a').removeClass(activeClass);
			$t.addClass(activeClass);
			table.ui.button.deletePreset.show();
			table.ui.button.submitLoadPreset.removeClass('ui-tb-disabled');
		}
		else {
			$t.removeClass(activeClass);
			table.ui.button.submitLoadPreset.addClass('ui-tb-disabled');
			table.ui.button.deletePreset.hide();
		}

		return false;
	});

	table.ui.button.savePreset.click(function() {
		
		var obj = $(this);

		var position = {
			my: 'left top',
			at: 'left bottom',
			offset: '0 10'
		};

		table.override = true;

		table.showMenu(table.ui.menu.savePreset, obj, position, {
			visible: function() {
				obj.find('*[name="preset_name"]').focus();
			},
			valid: function() {
				table.savePreset();
			}
		});

		return false;
	});

	table.ui.button.submitSavePreset.click(function() {
		table.savePreset();

		return false;
	});

	table.ui.button.addRow.click(function() {
		table.addRow();
		
		return false;
	});
	
	table.ui.button.submitEditColumn.click(function() {
		table.editColumn();
		
		return false;		
	});

	table.ui.button.cancel.click(function() {
		table.hideMenu();

		return false;
	});

	table.ui.menu.editClass.find('button').click(function() {
		
		var val = table.ui.menu.editClass.find('input[name="row_class"]').val();

		table.rows[table.editRowIndex].cssClass = val;
		table.rows[table.editRowIndex].json();
		
		table.hideMenu();


		return false;
	});

	table.ui.button.deleteRow.click(function() {
		var position = {
			my: 'left top',
			at: 'right center',
			offset: '7 -23'
		};

		table.showMenu(table.ui.menu.deleteRow, table.editRowObj.ui.tr.find('.ui-tb-id-column'), position, {
			show: function(menu) {
				menu.find('a[href="#delete-row"]').unbind('click').click(function() {
					table.editRowObj.delete();
				});
			}
		});
		
		return false;
	});

	var presetDialog = table.ui.wrapper.find('.ui-tb-delete-preset-dialog').dialog({
		autoOpen: false,
		modal: true,
		title: 'Delete Preset Confirmation',
		resizable: true,
		draggable: true,
		buttons: {
			'Cancel': function() {
				$(this).dialog('close');
			},
			'Delete Preset': function() {
				var $t = $(this);

				table.deletePreset(function() {
					$t.dialog('close');
					table.ui.button.submitLoadPreset.addClass('ui-tb-disabled');
					table.ui.button.deletePreset.hide();
				});
			}
		}
	});

	table.ui.button.deletePreset.click(function() {

		presetDialog.dialog('open');

		return false;
	});

	var columnIndex = false;

	table.ui.wrapper.find('th a[href="#ui-tb-column-menu"]').live('click', function(event) {
		var obj = $(this);

		var index  = obj.parent().parent().index()-1;
		var column = table.columns[index];

		table.editColumnObj = column;

		var position = {
			my: 'center top',
			at: 'center bottom',
			offset: '3 10'
		};

		if(columnIndex !== false && columnIndex != index) {
			table.ui.menu.column.hide();
		
			if(table.hideMenu) {
				table.hideMenu();
			}
			
		}
		
		table.showMenu(table.ui.menu.column, obj, position, {
			click: function() {
			},
			show: function() {

				var position = {my: 'left top', at: 'left bottom', offset: '-10 12'};
					
				table.ui.menu.column.find('a[href="#edit-column"]').unbind('click').click(function() {

					table.override = true;

					table.showMenu(table.ui.menu.editColumn, obj.parent(), position, {
						show: function() {
							table.override = true;
							table.ui.menu.editColumn.find('input[name="column_name"]').val(column.name);
							table.ui.menu.editColumn.find('input[name="column_title"]').val(column.title);
							table.ui.menu.editColumn.find('*[name="column_type"]').val(column.type);
							obj.addClass('active');
						},
						hide: function() {
							obj.removeClass('active');
						},
						valid: function() {
							table.editColumn();
						},
						visible: function() {
						}
					});
				});

				table.ui.menu.column.find('a[href="#delete-column"]').unbind('click').click(function() {
					
					table.showMenu(table.ui.menu.deleteColumn, obj.parent(), position, {
						show: function() {
							table.override = true;
							obj.addClass('active');
						},
						hide: function() {
							obj.removeClass('active');
						},
						visible: function() {
						}
					});
				});

				table.ui.menu.column.find('a[href="#insert-after"], a[href="#insert-before"], ').unbind('click').click(function() {

					table.insertIndex = columnIndex;

					if($(this).attr('href') == '#insert-after') {
						table.insertIndex++;
					}

					table.showMenu(table.ui.menu.addColumn, obj.parent(), position, {
						show: function() {
							table.override = true;
							obj.addClass('active');
						},
						visible: function(menu) {
							menu.find('input:first').focus();
						},
						hide: function() {
							obj.removeClass('active');
						},
						valid: function() {
							table.addColumn();
						}
					});
				});

				obj.addClass('active');
			},
			hide: function() {
				obj.removeClass('active');
			}
		});

		table.ui.button.deleteColumn.unbind('click').click(function() {
			table.deleteColumn();

			return false;
		});
		
		columnIndex           = index;
		table.editColumnIndex = columnIndex;

		return false;
	});
	
	/*------------------------------------------
	 *	Contructor
	/* -------------------------------------- */

	$(function() {
		$.each(table.celltypes, function(i, celltype) {
			var value = celltype.cell_id;
			var name  = celltype.display_name != '' && celltype.display_name != null ? celltype.display_name : celltype.type;

			table.ui.field.columnType.append('<option value="'+value+'">'+name+'</option>');
		});

		var count = 1;

		$.each(settings.columns, function(i, column) {

			var obj   = new Table_Builder.Column(table, column.title, column.name, column.type);
			
			obj.ui.th = $(table.ui.thead.find('th').get(count));
			obj.ui.column = table.ui.wrapper.find('input[name="'+table.name+'[column]['+column.name+']"]');

			table.columns.push(obj);

			count++;	
		});

		$.each(settings.rows, function(i, row) {

			var obj = new Table_Builder.Row(table, $(table.ui.tbody.find('tr').get(i)));

			table.rowCount++;

			obj.ui.row = table.ui.wrapper.find('input[name="'+table.name+'[row]['+i+']"]');
			obj.index = i;

			var value = obj.ui.row.val();
			var rowObj = JSON.parse(value);

			if(rowObj.entryId) {
				obj.entryId = rowObj.entryId
			}

			if(rowObj.rowId) {
				obj.rowId = rowObj.rowId
			}

			if(rowObj.cssClass) {
				obj.cssClass = rowObj.cssClass
			}

			obj.ui.tr.find('td').each(function(i, td) {
				if(i > 0) {
					var column = table.columns[i-1];
					var cell = new Table_Builder.Cell(obj, column);

					cell.ui.td = $(td);

					obj.cells.push(cell);
				}
			});

			table.rows.push(obj);
		});

		table.totalRows = settings.rows.length;

		table.reorder();
	});

	table.init();

	return table;
}

Table_Builder.Row = function(table, tr) {

	var row   = this;

	/*------------------------------------------
	 *	Properties
	/* -------------------------------------- */

	row.index 		  = table.totalRows;

	row.table 		  = table;
	row.cssClass      = '';
	row.cells		  = [];

	row.ui            = {
		tr: tr,
		row: $('<input type="hidden" name="'+row.table.name+'[row]['+row.index+']" class="ui-tb-hidden-row ui-tb-hidden-value" />')
	}


	/*------------------------------------------
	 *	Methods
	/* -------------------------------------- */

	row.addCell = function(cell) {
		row.ui.tr.append(cell.ui.td);
		row.cells.push(cell);
	}

	row.delete = function(cell) {
		table.hideMenu()

		row.ui.tr.remove();
		row.ui.row.remove();

		//table.rows.remove(row.index);

		if(row.rowId) {
			table.ui.wrapper.append('<input type="hidden" name="'+row.table.name+'[delete][]" value="'+row.rowId+'" />');
		}

		table.reorder();
	}

	row.json = function() {

		var obj = {
			index: row.index,
			cssClass: row.cssClass
		}

		if(row.entryId) {
			obj.entryId = row.entryId;
		}

		if(row.rowId) {
			obj.rowId = row.rowId;
		}

		if(row.cssClass) {
			obj.cssClass = row.cssClass;
		}

		var json = JSON.stringify(obj);

		row.ui.row.val(json);

		return json;
	}

	/*------------------------------------------
	 *	Constructor
	/* -------------------------------------- */
	
	/*

	*/

	row.ui.tr.find('.ui-tb-id-column').click(function() {
		
		var obj = $(this);
		var tr  = obj.parent().index();

		if(row.index != table.editRowIndex) {
			table.ui.menu.row.hide();
			
			if(table.hideMenu) {
				table.hideMenu();
			}
		}

		var position = {
			my: 'left top',
			at: 'left center',
			offset: '30 -23'
		};

		table.showMenu(table.ui.menu.row, obj, position, {

			show: function(menu) {

				var parent = menu;

				table.editRowObj = row;
				table.editRowIndex = row.index;

				table.ui.button.editClass.unbind('click').click(function() {
					table.override = true;
					table.showMenu(table.ui.menu.editClass, obj, position, {
						show: function(menu) {
							menu.find('.validate').val(row.cssClass);
						},
						visible: function(menu) {
							menu.find('.validate').focus();
						},
						valid: function(menu) {
							menu.find('button').click();
						}
					});
				});

				parent.find('a[href="#insert-after"], a[href="#insert-before"]').unbind('click').click(function() {

					var insertBefore = $(this).attr('href') == '#insert-after' ? false : true;

					table.addRow(tr, insertBefore);

					return false;
				});
			}
		});

		table.editRowIndex = row.index;
	});
	
	return row;
}

Table_Builder.Cell = function(row, column) {

	var cell = this;

	/*------------------------------------------
	 *	Properties
	/* -------------------------------------- */

	cell.type     = column.celltype;
	cell.html	  = cell.type.html.replace('{DEFAULT}', row.table.name+'[cell]['+row.table.rowCount+']['+column.name+']');
	cell.row      = row;
	cell.hasFocus = false;
	cell.ui		  = {
		td: $('<td>'+cell.html+'</td>')
	}


	/*------------------------------------------
	 *	Methods
	/* -------------------------------------- */

	cell.focus = function() {

	}

	cell.blur = function() {

	}

	/*------------------------------------------
	 *	Constructor
	/* -------------------------------------- */

	return cell;
}

Table_Builder.Column = function(table, title, name, type) {
	
	var column = this;

	/*------------------------------------------
	 *  Properties	
	/* -------------------------------------- */

	column.title    = title;
	column.name     = name;
	column.type     = type;
	column.celltype = table.celltypes[type];
	column.html     = column.celltype.html;
	column.width	= false;
	column.ui 		= {
		th: $('<th class="ui-tb-resizable"><div class="ui-tb-relative"><a href="#ui-tb-column-menu" class="ui-tb-column-button">&#x25BE;</a><span class="title">'+column.title+'</span></div></th>'),
		column: $('<input type="hidden" name="'+table.name+'[column]['+column.name+']" value="" class="ui-tb-hidden-value ui-tb-hidden-column" />')
	}
	
	/*------------------------------------------
	 *	Methods
	/* -------------------------------------- */

	column.edit = function(data) {

		var prevName = column.name;

		column.title = data.title;

		if( column.type == data.type && 
			column.name != data.name) {

			column.name  = data.name;

			$.each(table.rows, function(i, row) {
				row.cells[table.editColumnIndex].ui.td.find('*[name="'+table.name+'[cell]['+i+']['+prevName+']"]').attr('name', table.name+'[cell]['+i+']['+data.name+']');
			});
		}

		column.ui.column.attr('name', table.name+'[column]['+column.name+']');

		if(column.type != data.type) {
			column.type     = data.type;
			column.celltype = table.celltypes[data.type];
			column.html     = column.celltype.html;

			$.each(table.rows, function(i, row) {
				var cell = new Table_Builder.Cell(row, column);
				row.cells[table.editColumnIndex].ui.td.html(cell.html);
			});
		}

		table.hideMenu(function() {
			table.inactive();
		});

		table.ui.thead.find('th:nth-child('+(table.editColumnIndex+2)+') .title').html(data.title);
		column.update();
	}

	column.delete = function() {
		column.ui.th.remove();
		column.ui.column.remove();

		table.columns.remove(table.editColumnIndex);

		table.totalColumns--;

		$.each(table.rows, function(i, row) {
			if(table.totalColumns <= 0 && row.entryId) {
				row.delete();
			}
			row.cells[table.editColumnIndex].ui.td.remove();
			row.cells.remove(table.editColumnIndex);
		});

		if(table.columns.length == 0) {
			table.ui.thead.remove();
			table.ui.tbody.remove();
			table.rows = [];
			table.totalRows = 0;
		}

		if(table.totalColumns < 0) {
			table.totalColumns = 0;
		}

		if(table.totalColumns > 0) {
			table.ui.button.addRow.show();
			table.ui.button.savePreset.show();
		}
		else {
			table.ui.button.addRow.hide();
			table.ui.menu.empty.show();
			table.ui.button.savePreset.hide();
		}
	}

	column.update = function() {

		var data = {
			title: column.title,
			name: column.name,
			type: column.type,
			celltype: column.celltype,
			html: column.html,
			width: column.width
		}
		
		column.ui.column.val(JSON.stringify(data));

		table.reorder();
	}

	Array.prototype.insert = function(value, index) {
		if(this[index]) {
			this.splice(index, 0, value);
		}
		else {
			this.push(value);
		}
	}

	Array.prototype.remove = function(from, to) {
	  var rest = this.slice((to || from) + 1 || this.length);
	  this.length = from < 0 ? this.length + from : from;
	  return this.push.apply(this, rest);
	};
	
	/*------------------------------------------
	 *	Constructor
	/* -------------------------------------- */
			
	table.ui.menu.empty.hide();
				
	/*
	*/

	column.update();
	
	
	return column;
}

Table_Builder.instances = [];
