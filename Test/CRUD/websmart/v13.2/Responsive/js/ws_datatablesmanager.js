var ws_DataTablesManager = function(programName, tableId, columns)
{
	this.m_ProgramName = programName;
	this.m_TableId = tableId;
	this.m_Columns = columns;
};

// Alter the data sent to the server to match what our program is expecting
ws_DataTablesManager.prototype.tableData = function(data) {
	var elTable = jQuery('#' + this.m_TableId);
	var oParams = {'task': 'loadgrid'};
	
	// If we are filtering the table, change the task
	if (elTable.data('filter'))
	{
		oParams['task'] = 'filter';
		elTable.data('filter', false);
	}
	
	// Add our filter fields to the data passed to the server
	jQuery("#filter-form .form input[name]").each(function() {
		var elFilter = jQuery(this);
		if (elFilter.val() !== '')
		{
			oParams["ww_f" + elFilter.attr("name")] = elFilter.val();
		}
	});
	
	return jQuery.extend({}, data, oParams);
};

// Update the DataTable with the user-provided filter data from #filter-form
ws_DataTablesManager.prototype.filterTable = function(oTable)
{
	var that = this;
	var elTable = jQuery('#' + this.m_TableId);
	var oTable = elTable.DataTable();
	var filterFields = jQuery('#filter-form [id^="ww_f"]');

	filterFields.each(function() {
		var fieldName = jQuery(this).attr('name');
		var columnIndex = that.getColumnIndex(fieldName);
		oTable.column(columnIndex).search(jQuery(this).val());
	});
	
	// Mark the table as being a filter on the next draw
	elTable.data('filter', true);
};

// Return the index of the specified column in our selected column list
ws_DataTablesManager.prototype.getColumnIndex = function(sColumnName)
{
	for (var i = 0; i < this.m_Columns.length; i++)
	{
		if (sColumnName == this.m_Columns[i].name)
		{
			return i;
		}
	}
	
	return -1;
};

// Send an AJAX request for the dialogs data, and show the specified dialog once the data is returned
ws_DataTablesManager.prototype.showDialog = function(dialogType, data)
{
	var that = this;
	this.clearMessage();
	
	// Default data to empty string if not provided
	data = data || '';
	
	// Fetch the data to show, and show the dialog
	var sData = 'task=begin' + dialogType + data;
	jQuery.ajax({
		url: this.m_ProgramName,
		data: sData,
		type: 'POST',
		dataType: 'json',
		success: function(data, err) {
			if (that.isError(data))
			{
				that.handleError(data);
				return;
			}
			
			that.populateDialog(dialogType, '#' + that.m_TableId + '_' + dialogType + '_dialog', data);
			jQuery('#' + that.m_TableId + '_' + dialogType + '_dialog').modal();
		}
	});
};

// Return the key data associated with the given element
// This data is stored as attributes of the elements closest parent div
ws_DataTablesManager.prototype.getKeyData = function(el)
{
	// Determine the keys to pass to get the record data
	var sData = jQuery(el).closest('div').attr('data-url');
	if(sData == '' || sData == undefined)
	{
		//leaving this in here to be backwards compatible. As of 11.3 this shouldn't be necessary.
		sData = '';
		var aAttributes = jQuery(el).closest('div')[0].attributes;
		jQuery.each(aAttributes, function(index, attr) {
			sData += '&' + attr.name + '=' + attr.value;
		});
	}
	
	return sData;
};


ws_DataTablesManager.prototype.clearErrors = function ()
{
	$('.error-text').text('');
	$('form .field-group').removeClass('has-error');
}

ws_DataTablesManager.prototype.outputValidationMessages = function (mydialog, jsondata)
{
	Object.getOwnPropertyNames(jsondata.fields).forEach(
		function (propName, idx, array) {
			$('.group' + propName, mydialog).addClass('has-error');
			$('.group' + propName + ' .error-text', mydialog).text(jsondata.fields[propName]);
		}
	);
}

// Serialize the dialog's form, submit the data to the server, and close the dialog on success
ws_DataTablesManager.prototype.submitDialog = function(dialogType)
{
	var that = this;
	var elTable = jQuery('#' + this.m_TableId);
	var oTable = elTable.DataTable();
	var formData = jQuery('#' + this.m_TableId + '_' + dialogType + '_form').serialize() + '&mode=ajax&';
	
	jQuery.ajax({
		type: "POST", 
		url: this.m_ProgramName,
		data: formData,
		dataType: "json", 
		success: function (data, err)
		{
			var mydialog = '#' + that.m_TableId + '_' + dialogType + '_dialog';
			that.clearErrors();
			// If we've encountered an error, cancel
			if (that.isError(data))
			{
				that.handleError(data);
				return;
			}
			
			// Check if anything didn't pass validation and display it
			if (data.hasOwnProperty("isvalid") && !data.isvalid)
			{
				that.outputValidationMessages(mydialog, data);
				return;
			}
			
			// Hide the modal
			jQuery(mydialog).modal('hide');
			
			// Show the user a status message
			that.setMessage(data.msg, 'alert-success');
			oTable.ajax.reload(null, false);
		}
	});
};

// Take the passed in data, and populate the dialog with it
ws_DataTablesManager.prototype.populateDialog = function(dialogType, dialogId, data)
{
	var dialog = jQuery(dialogId);

	for (var elementId in data)
	{
		var element = jQuery('#' + dialogType + elementId, dialog);
		if (element.is('div') || element.is('span') || element.is('p'))
		{
			element.text(data[elementId]);
		}
		else
		{
			element.val(data[elementId]);
		}
	}
};

// Return true if the passed in data is an error
ws_DataTablesManager.prototype.isError = function(data)
{
	if (data.type === 'error')
	{
		return true;
	}
	return false;
};

// Show the passed in data's error message
ws_DataTablesManager.prototype.handleError = function(data)
{
	this.setMessage(data.msg, 'alert-danger');
};

// Set the message in the alert-container to the specified message with the specified class
ws_DataTablesManager.prototype.setMessage = function(msg, msgClass)
{
	// Find the currently active "frame" to put the message in (Either the currently visible modal, or the document body)
	var container = jQuery('#outer-content');
	if (jQuery('.modal.in:visible').length > 0)
	{
		container = jQuery('.modal:visible');
	}

	var closeElement = jQuery('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
	var msgElement = jQuery('<div class="alert ' + msgClass + '"></div>');
	msgElement.text(msg).append(closeElement);
	jQuery('.alert-container', container).html('').append(msgElement);
};

// Clear the contents of the alert-container
ws_DataTablesManager.prototype.clearMessage = function()
{
	return jQuery('.alert-container').html('');
};
