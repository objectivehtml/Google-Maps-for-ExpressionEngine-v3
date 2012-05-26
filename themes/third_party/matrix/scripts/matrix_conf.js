var MatrixConf;

(function($) {


var $window = $(window);
var $document = $(document);
var $body = $(document.body);


/**
 * Matrix Conf
 */
MatrixConf = function(namespace, celltypes, colInfo, colSettings){

	var obj = this;
	obj.namespace = namespace;
	obj.celltypes = celltypes;
	obj.colSettings = colSettings;

	obj.focussedCell;
	obj.dragging = false;

	obj.dom = {};
	obj.dom.$settingsContainer = $('#ft_matrix');
	obj.dom.$container = $('#matrix-conf-container');
	obj.dom.$field = $('#matrix-conf');
	obj.dom.$table = $('> table', obj.dom.$field);
	obj.dom.$thead = $('> thead', obj.dom.$table);
	obj.dom.$tbody = $('> tbody', obj.dom.$table);
	obj.dom.$trs = $('> * > tr', obj.dom.$table);

	obj.dom.$addBtn = $('> a.matrix-add', obj.dom.$field);

	// -------------------------------------------
	//  Set the container width
	// -------------------------------------------

	var setContainerWidth = function(){

		// set the scroll container width
		var containerWidth = MatrixConf.EE1 ? $window.width() - 48 : obj.dom.$settingsContainer.width() - 12;
		obj.dom.$container.width(containerWidth);

		// set the container width based on the table width
		var fieldWidth = obj.dom.$table.outerWidth();
		obj.dom.$field.width(fieldWidth);
	};

	if (MatrixConf.EE1) {
		var $dropdown = $('select[name=field_type]'),
			dropdownVal = obj.namespace.substring(6, obj.namespace.length-1);
	} else
	{
		var $dropdown = $('#field_type'),
			dropdownVal = 'matrix';
	}

	$dropdown.change(function(){ setTimeout(function(){
		if ($dropdown.val() == dropdownVal) {
			$window.bind('resize.matrix', setContainerWidth)
			$window.trigger('resize');
		} else {
			$window.unbind('.matrix');
		}
	}, 1); });

	$document.ready(function(){ $dropdown.change(); });

	// -------------------------------------------
	//  Initialize cols
	// -------------------------------------------

	obj.cols = [];

	for (var i in colInfo) {
		var col = new MatrixConf.Col(obj, parseInt(i), colInfo[i].id, colInfo[i].type);
		obj.cols.push(col);
	}

	obj.totalCols = obj.cols.length;
	obj.totalNewCols = 0;

	// click anywhere to blur the focussed cell
	$document.mousedown(function(){
		if (obj.focussedCell) {
			obj.focussedCell.blur();
		}
	});

	/**
	 * Save Cell Sizes
	 */
	obj.saveCellSizes = function(){
		$('> *:not(:first-child)', obj.dom.$trs).each(function(){
			var $cell = $(this);
			$cell.width($cell.width());
			$cell.height($cell.height());
		});
	};

	/**
	 * Clear Cell Sizes
	 */
	obj.clearCellSizes = function(){
		$('> *:not(:first-child)', obj.dom.$trs).each(function(){
			var $cell = $(this);
			$cell.width('auto');
			$cell.height('auto');
		});
	};

	// -------------------------------------------
	//  Column Management
	// -------------------------------------------

	/**
	 * Add Col
	 */
	obj.addCol = function(index){
		if (typeof index != 'number' || index > obj.totalCols) {
			index = obj.totalCols;
		}
		else if (index < 0) {
			index = 0;
		}

		// -------------------------------------------
		//  Create the column
		// -------------------------------------------

		var colId = 'col_new_'+obj.totalNewCols,
			colCount = index + 1;

		// label preview
		$('<th class="matrix matrix-last" scope="col">'
		+   '<input type="hidden" name="'+obj.namespace+'[col_order][]" value="'+colId+'" />'
		+   '<span>&nbsp;</span>'
		+ '</th>').appendTo(obj.dom.$trs[0]);

		// instructions preview
		$('<td class="matrix matrix-last">&nbsp;</td>').appendTo(obj.dom.$trs[1]);

		// settings
		for (var i in obj.colSettings) {
			var settingName = obj.namespace+'[cols]['+colId+']['+obj.colSettings[i]+']';

			var rowIndex = parseInt(i) + 2;
			var $td = $('<td class="matrix matrix-last" />').appendTo(obj.dom.$trs[rowIndex]);

			switch (obj.colSettings[i]) {
				case 'type':
					obj.cols[0].dom.$celltype.clone().attr('name', settingName).appendTo($td).val('text');
					break;

				case 'name':
				case 'width':
					$td.addClass('matrix-text');
					$('<input type="text" class="matrix-textarea" name="'+settingName+'" value="" />').appendTo($td);
					break;

				case 'required':
				case 'search':
					$('<input type="checkbox" name="'+settingName+'" value="y" />').appendTo($td);
					break;

				case 'settings':
					var html = obj.celltypes['text'].replace(/\{COL_ID\}/g, colId);
					$td.html(html);
					break;

				default:
					$td.addClass('matrix-text');
					$('<textarea class="matrix-textarea" name="'+settingName+'" />').appendTo($td);
			}
		}

		// remove btn
		$('<td class="matrix-breakdown"><a class="matrix-btn" title="'+MatrixConf.lang.delete_col+'" /></td>').appendTo(obj.dom.$trs[obj.colSettings.length+2]);

		// fake a window resize so that any text
		// cells can update their heights
		$window.trigger('resize');

		// -------------------------------------------
		//  Initialize it
		// -------------------------------------------

		var col = new MatrixConf.Col(obj, index, colId, 'text');
		obj.cols.push(col);
		//obj.cols.splice(index, 0, col);

		obj.totalCols++;
		obj.totalNewCols++;

		// update the previous last col
		obj.cols[index-1].updateIndex(index-1);

	};

	/**
	 * Remove Col
	 */
	obj.removeCol = function(index) {
		// does this row exist, and is it not the only row?
		if (typeof index == 'undefined' || typeof obj.cols[index] == 'undefined' || obj.totalCols == 1) return false;

		var col = obj.cols[index];

		if (! col.isNew) {
			// keep a record of the col_id so we can delete it from the database
			$('<input type="hidden" name="'+obj.namespace+'[deleted_cols][]" value="'+col.id+'" />').appendTo(obj.dom.$field);
		}

		// forgedaboudit!
		obj.cols.splice(index, 1);
		obj.totalCols--;
		col.remove();
		delete col;

		// update the following cols' indices
		for (var i = index; i < obj.totalCols; i++) {
			obj.cols[i].updateIndex(i);
		}

		// was this the first row?
		if (index == 0) {
			obj.cols[0].updateIndex(0);
		}

		// was this the last row?
		if (index == obj.totalCols) {
			obj.cols[obj.totalCols-1].updateIndex(obj.totalCols-1);
		}

	};

	obj.dom.$addBtn.click(obj.addCol);

};

// --------------------------------------------------------------------

/**
 * Column
 */
MatrixConf.Col = function(field, index, id, type){

	var obj = this;
	obj.field = field;
	obj.index = index;
	obj.id = id;
	obj.type = type;
	obj.isNew = (obj.id.substr(0, 8) == 'col_new_');

	obj.dom = {};
	obj.dom.$tds = $('> *:nth-child('+(obj.index+2)+')', obj.field.dom.$trs);

	// special case rows
	obj.dom.$labelPreview = $('> span', obj.dom.$tds[0]);
	obj.dom.$instructionsPreview = $(obj.dom.$tds[1]);
	obj.dom.$celltype = $('> select', obj.dom.$tds[2]);
	obj.dom.$label = $('> textarea', obj.dom.$tds[3]);
	obj.dom.$name = $('> input', obj.dom.$tds[4]);
	obj.dom.$instructions = $('> textarea', obj.dom.$tds[5]);
	obj.dom.$settings = $(obj.dom.$tds[obj.dom.$tds.length-2]);
	obj.dom.$removeBtn = $('> a', obj.dom.$tds[obj.dom.$tds.length-1]);

	// --------------------------------------------------------------------

	/**
	 * Update Index
	 */
	obj.updateIndex = function(index){
		obj.index = index;

		// is this the new first?
		if (obj.index == 0) obj.dom.$tds.addClass('matrix-first');
		else obj.dom.$tds.removeClass('matrix-first');

		// is this the new last?
		if (obj.index == obj.field.totalCols-1) obj.dom.$tds.addClass('matrix-last');
		else obj.dom.$tds.removeClass('matrix-last');

		obj.overrideTabOrder();
	};

	/**
	 * Override Tab Order
	 */
	obj.overrideTabOrder = function(){
		if (typeof obj.dom.$inputs != 'undefined') {
			// unbind previous tab handling
			obj.dom.$inputs.unbind('keydown.matrix-tabcontrol');
		}

		obj.dom.$inputs = $('*[name][type!=hidden]', obj.dom.$tds);

		obj.dom.$inputs.bind('keydown.matrix-tabcontrol', function(event) {
			// was this a tab?
			if (! event.metaKey && event.keyCode == 9) {
				// get the index of this input
				var inputIndex = $.inArray(this, obj.dom.$inputs);

				if (! event.shiftKey) {
					// is there another input in this column?
					if (inputIndex < obj.dom.$inputs.length-1) {
						event.preventDefault();
						$(obj.dom.$inputs[inputIndex+1]).focus();
					} else {
						// is there a next column?
						if (obj.index < obj.field.totalCols-1) {
							event.preventDefault();
							$(obj.field.cols[obj.index+1].dom.$inputs[0]).focus();
						}
					}
				} else {
					// is there a previous input in this column?
					if (inputIndex > 0) {
						event.preventDefault();
						$(obj.dom.$inputs[inputIndex-1]).focus();
					} else {
						// is there a previous column?
						if (obj.index > 0) {
							event.preventDefault();
							var $prevColInputs = obj.field.cols[obj.index-1].dom.$inputs;
							$($prevColInputs[$prevColInputs.length-1]).focus();
						}
					}
				}
			}
		});
	};

	obj.overrideTabOrder();

	/**
	 * Remove
	 */
	obj.remove = function(){
		obj.dom.$tds.remove();
	};

	obj.dom.$removeBtn.click(function(){
		obj.field.removeCol(obj.index);
	});

	// --------------------------------------------------------------------

	// swap celltype settings when the select changes
	obj.dom.$celltype.change(function(){
		obj.type = obj.dom.$celltype.val();
		var html = obj.field.celltypes[obj.type].replace(/\{COL_ID\}/g, obj.id);

		if (html) {
			obj.dom.$settings.removeClass('matrix-disabled');
		} else {
			html = '&nbsp;';
			obj.dom.$settings.addClass('matrix-disabled');
		}

		obj.dom.$settings.html(html);
		obj.overrideTabOrder();
	});

	// -------------------------------------------
	//  Label and Instructions previews
	// -------------------------------------------

	/**
	 * Live Preview
	 */
	var livePreview = function($textarea, $preview) {
		var val = $textarea.val();

		$textarea.bind('keyup keydown blur update', function(){
			// has the value changed?
			if (val === (val = $textarea.val())) return;

			if (! val) {
				var html = val = '&nbsp;';
			} else {
				// html entities
				var html = val.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/[\n\r]$/g, '<br/>&nbsp;').replace(/[\n\r]/g, '<br/>');
			}

			$preview.html(html);
		});
	};

	livePreview(obj.dom.$label, obj.dom.$labelPreview);
	livePreview(obj.dom.$instructions, obj.dom.$instructionsPreview);

	// -------------------------------------------
	//  Dragging
	// -------------------------------------------

	var fieldOffset,
		mousedownX,
		mouseX,
		mouseOffset,
		helperPos,
		colAttr,
		$helper, placeholders,
		updateHelperPosInterval;

	/**
	 * Mouse down
	 */
	var onMouseDown = function(event){
		mousedownX = event.pageX;

		$document.bind('mousemove.matrix-col', onMouseMove);
		$document.bind('mouseup.matrix-col', onMouseUp);

		$body.addClass('matrix-grabbing');
	};

	obj.dom.$labelPreview.mousedown(onMouseDown);
	obj.dom.$instructionsPreview.mousedown(onMouseDown);

	/**
	 * Get Row Attributes
	 */
	var getColAttributes = function(){
		colAttr = [];

		for (i in obj.field.cols) {
			var col = obj.field.cols[i],
				$th = (col == obj && !! placeholders ? placeholders[0] : col.dom.$labelPreview);

			colAttr[i] = {};
			colAttr[i].offset = $th.offset();
			colAttr[i].width = $th.outerWidth();
			colAttr[i].midpoint = colAttr[i].offset.left + Math.floor(colAttr[i].width / 2);;
		}
	};

	/**
	 * Mouse move
	 */
	var onMouseMove = function(event){
		// prevent this from causing a selections
		event.preventDefault();

		mouseX = event.pageX;

		if (! obj.dragging) {
			// has the cursor traveled 1px yet?
			if (Math.abs(mousedownX - mouseX) > 1) {

				obj.dragging = obj.field.dragging = true;

				getColAttributes();

				// hardcode cell widths and heights
				obj.field.saveCellSizes();

				// create a floating helper table
				$helper = $('<table class="matrix matrix-conf matrix-helper" cellspacing="0" cellpadding="0" border="0">'
				          +   '<thead class="matrix"></thead>'
				          +   '<tbody class="matrix"></tbody>'
				          + '</table>');

				var $helperThead = $('> thead', $helper),
					$helperTbody = $('> tbody', $helper);

				fieldOffset = obj.field.dom.$field.offset();
				mouseOffset = mousedownX - colAttr[obj.index].offset.left;
				helperPos = colAttr[obj.index].offset.left;

				$helper.css({
					position: 'absolute',
					top:      fieldOffset.top - (colAttr[obj.index].offset.top-1) + 40,
					width:    colAttr[obj.index].width
				});

				// put it all in place
				$helper.appendTo(obj.field.dom.$field);


				// create a placeholder column
				placeholders = [];
				obj.dom.$tds.each(function(i){

					var $td = $(this),
						$placeholder = $('<td class="matrix-placeholder" style="width: '+colAttr[obj.index].width+'px;" />');

					if (i == obj.dom.$tds.length-1) $placeholder.addClass('matrix-breakdown');

					$placeholder.insertAfter($td);

					placeholders.push($placeholder);

					var $tr = $('<tr />').append($td);

					if (i < 2) {
						$tr.appendTo($helperThead);
					} else {
						$tr.appendTo($helperTbody);
					}

					$tr.addClass(obj.field.dom.$trs[i].className);
				});

				updateHelperPos();
				updateHelperPosInterval = setInterval(updateHelperPos, 25);
			}
		}

		if (obj.dragging) {

			if (obj.index > 0 && mouseX < colAttr[obj.index-1].midpoint) {
				var swapIndex = obj.index - 1,
					swapCol = obj.field.cols[swapIndex];

				for (var i in placeholders) {
					placeholders[i].insertBefore(swapCol.dom.$tds[i]);
				}
			}
			else if (obj.index < obj.field.totalCols-1 && mouseX > colAttr[obj.index+1].midpoint) {
				var swapIndex = obj.index + 1,
					swapCol = obj.field.cols[swapIndex];

				for (var i in placeholders) {
					placeholders[i].insertAfter(swapCol.dom.$tds[i]);
				}
			}

			if (typeof swapIndex != 'undefined') {
				// update field.cols array
				obj.field.cols.splice(obj.index, 1);
				obj.field.cols.splice(swapIndex, 0, obj);

				// update the rows themselves
				swapCol.updateIndex(obj.index);
				obj.updateIndex(swapIndex);

				// offsets have changed, so fetch them again
				getColAttributes();
			}
		}
	};

	/**
	 * Update Helper Position
	 */
	var updateHelperPos = function(){
		var dist = mouseX - colAttr[obj.index].midpoint,
			target = colAttr[obj.index].offset.left + Math.round(dist / 6);

		helperPos += (target - helperPos) / 2;
		$helper.css('left', helperPos - fieldOffset.left);
	};

	/**
	 * Mouse up
	 */
	var onMouseUp = function(event){
		$document.unbind('.matrix-col');
		$body.removeClass('matrix-grabbing');

		if (obj.dragging) {

			obj.dragging = obj.field.dragging = false;

			clearInterval(updateHelperPosInterval);

			// animate the helper back to the placeholder
			var left = (colAttr[obj.index].offset.left-1) - fieldOffset.left;
			$helper.animate({ left: left }, 'fast', function(){
				for (var i in placeholders) {
					placeholders[i].replaceWith(obj.dom.$tds[i]);
				}

				placeholders = null;

				$helper.remove();

				// clear the cell widths
				obj.field.clearCellSizes();
			});
		}
	};

	// -------------------------------------------
	//  Initialize cells
	// -------------------------------------------

	obj.cells = [];

	setTimeout(function(){
		for (var i in obj.field.colSettings) {
			var setting = obj.field.colSettings[i],
				td = obj.dom.$tds[parseInt(i)+2];

			switch (setting) {
				case 'label':
					var cell = new Matrix.Cell(obj.field, 'text', {}, td);
					break;

				case 'instructions':
					var cell = new Matrix.Cell(obj.field, 'text', { multiline: 'y'}, td);
					break;

				case 'name':
				case 'width':
					var cell = new Matrix.Cell(obj.field, 'text', { spaces: 'n' }, td);
					break;

				case 'required':
				case 'search':
					var cell = new Matrix.Cell(obj.field, 'checkboxes', {}, td);
					break;

				case 'settings':
					var cell = new Matrix.Cell(obj.field, 'mixed', {}, td);
					break;
			}

			obj.cells.push(cell);
		}
	}, 0);

};


})(jQuery);
