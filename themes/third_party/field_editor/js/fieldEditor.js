(function(w){
	w.fieldEditor = {
		groupId: null,
		table: null,
		form: null,
		rowIdCounter: 0,
		blankRow: "",
		fieldSettingsHtmlCache: {},
		noFieldSettings: [],
		errors: false,
		existingFields: false,
		lang: {},
		allFieldNames: [],
		reservedWords: [],
		loadingImg: "",
		errorColspan: 1,
		globalPrefix: "",
		unsavedChanges: 0,
		saving: false,
		updatedSettings: [],
		createRowCallbacks: {},
		customValidation: {},
		customSave: {},
		deleteField: function($table, i, row) {
			var tr = row.find("tr:first");
			var fieldId = tr.data("id");
			if (fieldId){
				fieldEditor.form.append("<input type=\'hidden\' name=\'delete_fields[]\' value=\'"+fieldId+"\'>");
			}
			fieldEditor.unsavedChanges++;
		},
		addCustomValidation: function(fieldType, callback) {
				fieldEditor.customValidation[fieldType] = callback;
		},
		addCustomSave: function(fieldType, callback) {
				fieldEditor.customSave[fieldType] = callback;
		},
		addField: function($table, i, row, fieldData) {
			var tr = row.find("tr:first");
			tr.data("rowid", fieldEditor.rowIdCounter)
			   .attr("id", "rowid_"+fieldEditor.rowIdCounter)
			   .find("input[name*=rowid]").val(fieldEditor.rowIdCounter);
			row.find("input[type!=hidden]:first").focus();
			/*
			if (fieldData !== undefined){
				$.each(fieldData, function(i, v){
					tr.find(":input[name*=\'"+i+"\']").val(v);
				});
			}
			*/
			fieldEditor.rowIdCounter++;
			fieldEditor.unsavedChanges++;
		},
		resetFields: function() {
			fieldEditor.table.find("tbody").find("tr:first").each(function(index){
				var mod = (index % 2) ? "odd" : "even";
				$(this).removeClass("even").removeClass("odd");
				$(this).addClass(mod);
			});
		},
		prefix: function(field, groupPrefix) {
			var $field = $(field),
			    val = $field.val(),
			    match;
		
			if ( ! val) {
				return true;
			}
		
			if ( ! fieldEditor.globalPrefix && ! groupPrefix) {
				return true;
			}
		
			//does it alreay match all prefixes?
			if (new RegExp("^"+fieldEditor.globalPrefix+groupPrefix).test(val)) {
				return true;
			}
		
			if (fieldEditor.globalPrefix) {
				match = val.match(new RegExp("^"+fieldEditor.globalPrefix+"(.*)$"));
			}
		
			if (match) {
				val = match[1];
			} else {
				if (groupPrefix) {
					match = val.match(new RegExp("^"+groupPrefix+"(.*)$"));
				}
		
				if (match) {
					val = match[1];
				}
			}
		
			$field.val(fieldEditor.globalPrefix+groupPrefix+val);
		
			return true;
		},
		submit: function() {
			fieldEditor.saving = true;
			var groupPrefix = $("#group_prefix").val();
			fieldEditor.table.find("input[name*=field_name]").each(function(){
				fieldEditor.prefix(this, groupPrefix);
			});
			var totalErrorsCount = 0;
			var allFieldNames = fieldEditor.allFieldNames.slice(0);
			var methods = {
				noFieldLabel: function(row) {
					return row.find(":input[name*=field_label]").val() == "";
				},
				noFieldName: function(row) {
					return row.find(":input[name*=field_name]").val() == "";
				},
				reservedWord: function(row) {
					return $.inArray(row.find(":input[name*=field_name]").val(), fieldEditor.reservedWords) !== -1;
				},
				duplicateFieldName: function(row) {
					var fieldName = row.find(":input[name*=field_name]").val();
					return fieldName != "" && $.inArray(fieldName, allFieldNames) !== -1;
				},
				pleaseAddUpload: function(row) {
					return row.find(":input[name*=field_type]").val() == "file";
				}
			};
			fieldEditor.table.find("tbody").each(function(){
				var $self = $(this);
				var errorsCount = 0;
				var errors = [];
				$.each(methods, function(lang, func) {
					if (func($self) === true) {
						totalErrorsCount++;
						errorsCount++;
						errors.push(fieldEditor.lang[lang]);
					}
				});
			
				var fieldName = $self.find(":input[name*=field_name]").val();
				if (fieldName) {
					allFieldNames.push(fieldName);
				}
				
				var fieldType = $self.find(":input[name*=field_type]").val();
				if (fieldEditor.customValidation[fieldType] !== "undefined" && typeof fieldEditor.customValidation[fieldType] === "function") {
					var customValidation = fieldEditor.customValidation[fieldType](row);
					if (customValidation !== true) {
						errors.push(customValidation);
						errorsCount++;
						totalErrorsCount++;
					}
				}
			
				var errorsRow = $self.find("tr.errors");
				if (errorsCount === 0) {
					errorsRow.remove();
				}
				else {
					if (errorsRow.length === 0) {
						errorsRow = $("<tr class='errors'></tr>").appendTo($self);
					}
					var html = ["<td>","</td>","<td colspan='", fieldEditor.errorColspan, "'>", "<ul>"];
					$.each(errors, function(i, v){
						html.push("<li>"+v+"</li>");
					});
					html.push("</ul>");
					html.push("</td>");
					errorsRow.html(html.join(""));
				}
				allFieldNames.push()
			});
			if (totalErrorsCount > 0) {
				fieldEditor.saving = false;
				return false;
			}
			$.each(fieldEditor.updatedSettings, function(i, fieldId) {
				fieldEditor.form.append($("<input>", {
					value: fieldId,
					name: "updated_settings[]",
					type: "hidden"
				}));
			});
			return true;
		},
		getFieldSettings: function(){
			var self = this;
			var row = $(self).parents("tr");
			var fieldId = row.data("id");
			var rowId = row.data("rowid");
			//this function can be bound to the field_type select or the settings button
			var fieldType = ($(self).hasClass("field_type")) ? $(self).val() : row.find("select.field_type").val();
			if ($.inArray(fieldType, fieldEditor.noFieldSettings) !== -1){
				if ( ! $(self).hasClass("field_type")){
					$.ee_notice(fieldEditor.lang.noSettings, {"type" : "error"});
					setTimeout($.ee_notice.destroy, 2000);
					return false;
				}
				return null;
			}
			var modalHtml = "";
			var fieldSettingsInput = row.find("input[name*=field_type_settings]");
			if (fieldEditor.fieldSettingsHtmlCache[rowId] !== undefined && fieldEditor.fieldSettingsHtmlCache[rowId][fieldType] !== undefined){
				modalHtml = fieldEditor.fieldSettingsHtmlCache[rowId][fieldType];
			}
			if ($("#modal").length === 0) {
				$("body").append($("<div>", {id: "modal"}).hide());
			}
			var canceled = false;
			$("#modal").html(fieldEditor.loadingImg).dialog({
				modal: true,
				resizable: false,
				title: fieldEditor.lang.loading,
				minHeight: 40,
				close: function() {
					canceled = true;
				},
				buttons: null,
				width: "auto",
				open: null
			});
			$.post(
				EE.BASE+"&C=addons_modules&M=show_module_cp&module=field_editor&method=field_settings",
				{
					XID: EE.XID,
					group_id: fieldEditor.groupId,
					field_id: fieldId,
					field_type: fieldType,
					modal_html: modalHtml
				},
				function(data){
					$("#modal").dialog({close: null}).dialog("close");
					if (canceled === true) {
						return;
					}
					$("#modal").html(data).dialog({
						modal: true,
						resizable: false,
						width: "90%",
						title: fieldEditor.lang.fieldTypeOptions,
						buttons: [
							{
								text: fieldEditor.lang.save,
								"class": "submit",
								click: function() {
									if (fieldEditor.customSave[fieldType] !== "undefined" && typeof fieldEditor.customSave[fieldType] === "function") {
										if (fieldEditor.customSave[fieldType]($("#modal form")) === false) {
											return;
										}
									}
									var settings = $(this).find("#ft_"+fieldType+" form").serializeArray();
									settings = window.JSON.stringify(settings);
									fieldSettingsInput.val(settings);
									if ($(self).hasClass("field_type")) {
										$(self).data("value", $(self).val());
									}
									if (fieldEditor.fieldSettingsHtmlCache[rowId] === undefined) {
										fieldEditor.fieldSettingsHtmlCache[rowId] = {};
									}
									fieldEditor.fieldSettingsHtmlCache[rowId][fieldType] = $("#modal div form").html();
									$(this).data("cancel", false);
									$(this).dialog("close");
									if ($.inArray(fieldId, fieldEditor.updatedSettings) === -1) {
										fieldEditor.updatedSettings.push(fieldId);
									}
								}
							},
							{
								text: fieldEditor.lang.cancel,
								"class": "submit",
								click: function() {
									$(this).dialog("close");
								}
							}
						],
						open: function(element, ui){
							$(this).data("cancel", true);
							var modal = $("#modal");
								maxHeight = $(window).height() - 100;
							if (modal.height() > maxHeight) {
								modal.css({"height": ($(window).height()-100)+"px", "overflow": "scroll"});
							}
							var settings = fieldSettingsInput.val();
							if (settings){
								settings = $.parseJSON(settings);
								var cache = [];
								var arrayCache = {};
								$.each(settings, function(i, v){
									cache.push(v.name);
									var input = $(element.target).find(":input[name='"+v.name+"']");
									if (/\[\]$/.test(v.name)) {//is an array name
										arrayCache[v.name] = (arrayCache[v.name] === undefined)
											? 0
											: arrayCache[v.name] + 1;
										input = input.eq(arrayCache[v.name]);
									}
									if (input.is(":radio")) {
										input.each(function(){
											if (this.value === v.value) {
												this.checked = true;
											}
										});
									} else if (input.is(":checkbox")) {
										input.attr("checked", true);
									} else {
										input.val(v.value);
									}
								});
								$(element.target).find(":checkbox").each(function() {
									if ($.inArray($(this).attr("name"), cache) === -1) {
										this.checked = false;
									}
								});
							}
						},
						close: function(element, ui){
							if ($(self).hasClass("field_type") && $(this).data("cancel") !== false){
								$(self).val($(self).data("value"));
							}
						}
					});
				},
				"html"
			);
			return ! $(self).hasClass("field_type");
		},
		createRowCallback: function(row) {
				var fieldType = $(row).find(".field_type").val();
				if (fieldEditor.createRowCallbacks[fieldType] !== undefined && typeof fieldEditor.createRowCallbacks[fieldType].callback === "function") {
					fieldEditor.createRowCallbacks[fieldType].callback($(row), fieldEditor.createRowCallbacks[fieldType].settings);
				}	
		},
		cloneField: function(fieldId, callback) {
			$.post(
				EE.BASE+"&C=addons_modules&M=show_module_cp&module=field_editor&method=clone_field",
				{
					XID: EE.XID,
					group_id: fieldEditor.groupId,
					field_id: fieldId,
					rowid: fieldEditor.rowIdCounter
				},
				function(data){
					if ( ! data.row) {
						return;
					}
					fieldEditor.rowIdCounter++;
					var row = $(data.row);
					row.appendTo(fieldEditor.table);
					var form = $(data.form);
					fieldEditor.initFieldSettings(row, form);
					fieldEditor.prefix(row.find("input[name*=field_name]"), $("#group_prefix").val());
					fieldEditor.unsavedChanges++;
					if (typeof callback === "function") {
						callback();
					}
					fieldEditor.createRowCallback(row);
				},
				"json"
			);
		},
		initFieldSettings: function(row, form) {
			var fieldSettingsInput = row.find("input[name*=field_type_settings]");
			var settings;
			if ( ! fieldSettingsInput.val()){
				settings = form.serializeArray();
				settings = window.JSON.stringify(settings);
				fieldSettingsInput.val(settings);
			}
			/* trash the form, we don't need it, and it'll interfere with field settings modal later on due to duplicate id's */
			form.remove();
		},
		initExistingField: function(field) {
			$.each(field, function(name, value){
				if (name !== "rowid"){
					$("#row_"+value.rowid).find(":input[name*="+name+"]").val(value);
				}
			});
		},
		init: function(options) {
			$.extend(this, options);
			fieldEditor.table = $("table.field_editor");
			fieldEditor.form = $("#field_editor");
			$("#clone_field a.submit").bind("click", function() {
				var $self = $(this);
				var buttonText = $self.text();
				$self.html(fieldEditor.lang.loading);
				fieldEditor.cloneField($("#clone_field select").val(), function() {
					$self.html(buttonText);
				});
				return false;
			});
			fieldEditor.table.find("input[name*=field_name]").live("blur", function() {
				fieldEditor.prefix(this, $("#group_prefix").val());
			});
			$("#group_prefix").focus(function(){
				$(this).data("original-value", this.value);
			}).blur(function(){
				var groupPrefix = $(this).val(),
				    originalValue = $(this).data("original-value");
				if (groupPrefix) {
					fieldEditor.table.find("input[name*=field_name]").each(function(){
						if (originalValue) {
							this.value = this.value.replace(new RegExp("^"+originalValue), "");
						}
						fieldEditor.prefix(this, groupPrefix);
					})
				}
			});
			fieldEditor.table.find("input.field_label_new").live("keyup keydown", function() {
				var fieldName = $(this).parents("tr").find("input[name*=field_name]");
				$(this).ee_url_title(fieldName);
				fieldEditor.prefix(fieldName, $("#group_prefix").val());
			});
			fieldEditor.table.find("select.field_type").live("change", fieldEditor.getFieldSettings);
			fieldEditor.table.find("a.settings").live("click", fieldEditor.getFieldSettings);
			if (fieldEditor.existingFields !== null){
				$.each(fieldEditor.existingFields, function(i, field) {
					fieldEditor.initExistingField(field);
				});
			}
			$(window).bind("beforeunload", function(){
				return ( ! fieldEditor.saving && fieldEditor.unsavedChanges > 0) ? fieldEditor.lang.unsavedChanges : null;
			});
			fieldEditor.form.bind("interact", function() {
				fieldEditor.unsavedChanges++;
			})
			fieldEditor.table.find("tbody").find("tr:first").each(function() {
				fieldEditor.initFieldSettings($(this), $("form#field_type_table_"+$(this).data("id")));
			});
			fieldEditor.form.bind("submit", fieldEditor.submit);
		}
	};
})(window)