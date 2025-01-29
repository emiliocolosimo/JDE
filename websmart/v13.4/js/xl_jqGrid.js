// Encapsulate functionality to load jQuery grid component and control add, change delete panels. 
// 2 methods: load - to load grid, and populateForm - to populate Add, Change, Delete forms 
// in AJAX callback.

var xl_jqGrid = new Object();
xl_jqGrid.options = new Object();
xl_jqGrid.load = function(optionsin)
{
	var options = this.options;
	
	options.crudid = optionsin.gridid; 
	options.serverpgm = optionsin.serverpgm;
	options.listsize = optionsin.listsize;
	options.keyobj = optionsin.keyobj; // Unique keys to the table. 
	options.colNames = optionsin.colNames;
	options.colModel = optionsin.colModel;
	options.allowadd = optionsin.allowadd;
	options.allowchg = optionsin.allowchg;
	options.allowdlt = optionsin.allowdlt;
	options.allowdsp = optionsin.allowdsp;
	
	// construct HTML element names
	options.gridid = "#"+options.crudid+"_grid";
	options.gridpagerid = "#"+options.crudid+"_pager";
	options.adddialog = "#"+options.crudid+"_add_dialog";
	options.addform   = "#"+options.crudid+"_add_form";
	options.chgdialog = "#"+options.crudid+"_chg_dialog";
	options.chgform   = "#"+options.crudid+"_chg_form";
	options.dltdialog = "#"+options.crudid+"_dlt_dialog";
	options.dltform   = "#"+options.crudid+"_dlt_form";
	options.dspdialog  = "#"+options.crudid+"_dsp_dialog";
	options.dspform = "#"+options.crudid+"_dsp_form";
	options.filterbtn = "#"+options.crudid+"_filt_btn";
	options.filterform = "#"+options.crudid+"_filt_form";

	// Load the  grid initially on document ready. 
	var lastrowselid;   // Last row selected, so we can reset it if user moves focus to another row
	jQuery(options.gridid).jqGrid(
	{
		url:  options.serverpgm + '?task=loadgrid',
		datatype: 'json',
		mtype: 'GET',
		colNames: options.colNames,
		height: "auto", 
		colModel : options.colModel, 
		loadComplete: attachClickHandlers,
		chgurl: options.serverpgm,
		pager: jQuery(options.gridpagerid),
		xtoppager: true,
		xcloneToTop: true,
		rowNum: options.listsize,
		rowList:[options.listsize, options.listsize*2, options.listsize*3],
		viewrecords: true,
		caption: '',
		onPaging: function(pgButton){
			clearMessage();
		},
		onSortCol: function(index, iCol, sortorder){
			clearMessage();
		}
	});

	jQuery(options.gridid).jqGrid('navGrid', options.gridpagerid,{edit:false,add:false,del:false,search:false});
	jQuery(".ui-jqgrid-hdiv").addClass("ui-widget-header").removeClass("ui-state-default");
	jQuery(".ui-th-column").removeClass("ui-state-default");
	if( options.allowadd ) // only show add button if it is an enabled option
	{
		jQuery(options.gridid).navButtonAdd(options.gridpagerid,
		{
			caption:"<span class=\"text\">Add</span>", 
			buttonicon:"ui-icon-plus", 
			onClickButton: addClickHandler,
			position:"last",
			title: "Add a new Record"
		});

		// For adding a record:
		jQuery(options.adddialog).dialog(
		{
			autoOpen: false,
			height: 'auto',
			width: 650,
			modal: true,
			dialogClass: 'add-dialog',
			buttons:
			{
				Add: addRecord,
				Cancel: function()
				{
					jQuery(this).dialog('close');
				}
			},
			close: function() {}
		});
		jQuery("div.add-dialog button:nth-child(1)").addClass("accept");
		jQuery("div.add-dialog button:nth-child(2)").addClass("cancel");
	}
	
	if( options.allowchg ) // only allow change if it is an enabled option
	{
		// For changing a record:
		jQuery(options.chgdialog).dialog(
		{
			autoOpen: false,
			height: 'auto',
			width: 650,
			modal: true,
			dialogClass: 'chg-dialog',
			buttons:
			{
				Change: changeRecord,
				Cancel: function()
				{
					jQuery(this).dialog('close');
				} 
			},
			open: function(event, ui)
			{
				$(this).css({'overflow-y': 'auto'});

			},
			close: function()
			{
				$('.ui-row-ltr').removeClass('ui-state-highlight');
			}
		});
		jQuery("div.chg-dialog button:nth-child(1)").addClass("accept");
		jQuery("div.chg-dialog button:nth-child(2)").addClass("cancel");
	}
	
	if( options.allowdlt ) // only allow deletion if it is enabled
	{
		// For Deleting a record:
		jQuery(options.dltdialog).dialog(
		{
			autoOpen: false,
			height: 'auto',
			width: 650,
			modal: true,
			dialogClass: 'dlt-dialog',
			buttons:
			{
				Delete: function()
				{
					deleteRecord();
				},
				Cancel: function()
				{
					jQuery(this).dialog('close');
				}
			},
			open: function(event, ui)
			{
				$(this).css({'overflow-y': 'auto'});
			},
			close: function()
			{
				$('.ui-row-ltr').removeClass('ui-state-highlight');
			}
		});
		jQuery("div.dlt-dialog button:nth-child(1)").addClass("accept");
		jQuery("div.dlt-dialog button:nth-child(2)").addClass("cancel");		
	}
	
	if( options.allowdsp ) // only allow display of record if it is enabled
	{
		// For Displaying a record:
		jQuery(options.dspdialog).dialog(
		{
			autoOpen: false,
			height: 'auto',
			width: 650,
			modal: true,
			dialogClass: 'dsp-dialog',
			buttons:
			{
				Return: function()
				{
					jQuery(this).dialog('close');
				}
			},
			open: function(event, ui)
			{
				$(this).css({'overflow-y': 'auto'});
			},
			close: function() {}
		});
		jQuery("div.dsp-dialog button:nth-child(1)").addClass("accept");
	}
}; // end of load method

/*  populateForm Populates form elements with values from a json string (usually returned from the server).
 *  Get the id of each form element from returned json object key, and its value from the value. 
 *  It might be a div- used to output output-only data. If so, just insert the value in the html inside the div
 *  @param {String} elemprefix - a common prefix on every element id in a form - to support multiple forms in same page. 
 *                               Typical use: 'chg' for chging existing records, 'add' for adding new records, 'dlt' for delete, 'dsp' for display
 *                               Old (current) key values are stored as field name+"_" in hidden fields in the form. 
 *  @param {object} elemsobj - name/value pairs that correspond to the form element id and its value- usually returned as JSON string from server
 */
xl_jqGrid.populateForm = function (formid, elemprefix, elemsobj)
{
	// restrict to the correct form
	var formelem = jQuery(formid);
 
	for (var elemid in elemsobj) 
	{
		// Process the key fields as hidden fields. 
		// (they may be repeated on the actual display, too).
		if (elemid == "_KEY_COLS") 
		{
				keysobj = elemsobj[elemid]; 
				for (var keyname in keysobj) 
				{
					var keyelem = jQuery("#"+keyname+"_", formelem); 
					if (keyelem.is('div') || keyelem.is('span')) 
					{
						if (keyelem.hasClass('htmlfield'))
						{
							keyelem.html(keysobj[keyname]);
						}
						else
						{
							keyelem.text(keysobj[keyname]);
						}
					}
					else
					{
						keyelem.val(keysobj[keyname]); 
					}
				}
		}
		else
		{
			var nonkeyelem = jQuery("#"+elemprefix + elemid, formelem); 
			if (nonkeyelem.is('div') || nonkeyelem.is('span')) 
			{
				if (nonkeyelem.hasClass('htmlfield'))
				{
					nonkeyelem.html(elemsobj[elemid]);
				}
				else
				{
					nonkeyelem.text(elemsobj[elemid]);
				}
			}
			else
			{
				nonkeyelem.val(elemsobj[elemid]); 
			}
		}
	 }
}; // end populateForm function

// filters the grid data using the filter inputs
function filterGrid(event)
{
	var options = xl_jqGrid.options;

	clearMessage();

	var filtParms = jQuery(options.filterform).serialize();
	jQuery(options.gridid).jqGrid('setGridParam', {url: options.serverpgm +"?" + filtParms, page:1} )
	jQuery(options.gridid).trigger("reloadGrid");
	
	event.preventDefault();
}

//attaches click handlers to change and delete icons in subfile list
function attachClickHandlers()
{
	var options = xl_jqGrid.options;
	function pointerize()
	{
		$(this).css({'cursor': 'pointer'});
	};
	
	// The returned JSON string contains divs with classes of chg and del in the first column. 
	// Add an icon for chging, and one for deletion, and bind a new click event handler for each one. 
	
	if( options.allowchg ) // only bind the event and show the icon if change is enabled
	{
		var chgElems = jQuery('.change');
		chgElems.button({
			icons: { primary: "ui-icon-pencil" },
			text: false
		});
		chgElems.attr('title', 'Change this record');
		chgElems.click( changeClickHandler );
	}
	else
	{
		jQuery('.change').hide();
	}

	if( options.allowdlt ) // only bind the event and show the icon if delete is enabled
	{
		var delElems = jQuery('.delete');
		delElems.button({
			icons: { primary: "ui-icon-closethick" },
			text: false
		});
		delElems.attr('title', 'Delete this record');
		delElems.click( deleteClickHandler );
	}
	else
	{
		jQuery('.delete').hide();
	}
	
	if( options.allowdsp ) // only bind the event and show the icon if display is enabled
	{
		var dspElems = jQuery('.display');
		dspElems.button({
			icons: { primary: "ui-icon-document" },
			text: false
		});
		dspElems.attr('title', 'View this record');
		dspElems.click( displayClickHandler );
	}
	else
	{
		jQuery('.display').hide();
	}

	// attach filter form submit handling
	jQuery(options.filterbtn).click(filterGrid);
	jQuery(options.filterform).submit(filterGrid);
}

// obtains record details and prompts for changes when the user clicks a record's change icon
function changeClickHandler()
{
	clearMessage();
	var options = xl_jqGrid.options;

	// Get the values for the key fields from the custom attributes of the clicked element: 
	// Use an object that has the key field names in it as the property names, with blanks for initial values.
	// Loop through each property name and retrieve the corresponding value from the parent div of the '.chg' div
	var sKeyObject = {
		"task": "beginchg"
	};
	for (var keyname in options.keyobj) 
	{
		sKeyObject[keyname] = jQuery(this).parent().attr(keyname);
	}
	
	// Call server with keys to retrieve correct table row and populate change form: 
	jQuery.ajax(
	{
		url: options.serverpgm,
		data: sKeyObject,
		type: "POST", 
		dataType: "json", 
		success: function (rtndata, err)
		{
			// If we've encountered an error, cancel
			if( handleError(rtndata) )
			{
				return;
			}
		
			// Populate form elements with values from server (JSON object with name/value pairs)  
			xl_jqGrid.populateForm(options.chgform, 'chg', rtndata); 
			jQuery(options.chgdialog).dialog('open'); 
			
			// Focus first element
			jQuery('input:visible:enabled:first', options.chgdialog).focus();
		},
		error: function(jqXHR,statustext,error)
		{
			if(statustext == 'parsererror')
			{
				alert("Invalid JSON response. Item not opened for change.");
			}
		}
	});
}

// prompts user for add record properties when they click the 'Add' button
function addClickHandler()
{
	clearMessage();
	
	var options = xl_jqGrid.options;
	sParmString = "task=beginadd";

	// Call server to populate add form: 
	jQuery.ajax(
	{
		url: options.serverpgm,
		data: sParmString,
		type: "POST", 
		dataType: "json", 
		success: function (rtndata, err)
		{
			// If we've encountered an error, cancel
			if( handleError(rtndata) )
			{
				return;
			}
		
			// Populate form elements with values from server (JSON object with name/value pairs)  
			xl_jqGrid.populateForm(options.addform, 'add', rtndata); 
			jQuery(options.adddialog).dialog('open');
			
			// Focus first element
			jQuery('input:visible:enabled:first', options.adddialog).focus();
		},
		error: function(jqXHR,statustext,error)
		{
			if(statustext == parsererror)
			{
				alert("Invalid JSON response. Item not opened for change.");
			}
		}
	});
}

// obtains and displays info to confirm deletion of a record when a user clicks on it's delete icon
function deleteClickHandler()
{
	clearMessage();
	var options = xl_jqGrid.options;

	var sKeyObject = {
		"task": "begindlt"
	};
	for (var keyname in options.keyobj) 
	{
		sKeyObject[keyname] = jQuery(this).parent().attr(keyname);
	}

	jQuery.ajax(
	{ 
		url: options.serverpgm,
		data: sKeyObject,
		type: "POST",
		dataType: "json", 
		success: function (rtndata, err)
		{
			// If we've encountered an error, cancel
			if( handleError(rtndata) )
			{
				return;
			}
		
			// Populate form elements with values from server (JSON object with name/value pairs)  
			xl_jqGrid.populateForm(options.dltform, 'dlt', rtndata); 
			jQuery(options.dltdialog).dialog('open'); 
			
			// Focus first element
			jQuery('input:visible:enabled:first', options.dltdialog).focus();
		} 
	 }); 
}


// obtains and displays info on a record when a user clicks on it's display icon
function displayClickHandler()
{
	clearMessage();
	var options = xl_jqGrid.options;

	var sKeyObject = {
		"task": "display"
	};
	for (var keyname in options.keyobj) 
	{
		sKeyObject[keyname] = jQuery(this).parent().attr(keyname);
	}

	jQuery.ajax(
	{ 
		url: options.serverpgm,
		data: sKeyObject,
		type: "POST",
		dataType: "json", 
		success: function (rtndata, err)
		{
			// If we've encountered an error, cancel
			if( handleError(rtndata) )
			{
				return;
			}
		
			// Populate form elements with values from server (JSON object with name/value pairs)  
			xl_jqGrid.populateForm(options.dspform, 'dsp', rtndata); 
			jQuery(options.dspdialog).dialog('open'); 
			
			// Focus first element
			jQuery('input:visible:enabled:first', options.dspdialog).focus();
		} 
	 }); 
}

// handles deletion of a record when a user clicks on the 'Delete' button on the delete dialog
function deleteRecord()
{
	var formdata = jQuery(xl_jqGrid.options.dltform).serialize() + "&mode=ajax&"; 
	var mydialog = jQuery(xl_jqGrid.options.dltdialog); 
	
	sendAndProcessRequest(formdata, mydialog);
}

// handles changing of a record when a user clicks on the 'Change' button on the change dialog
function changeRecord()
{
	var formdata = jQuery(xl_jqGrid.options.chgform).serialize() + "&mode=ajax&"; 
	var mydialog = jQuery(this); 
	
	sendAndProcessRequest(formdata, mydialog);
}

// handles adding a record when a user clicks on the 'Add' button on the add dialog
function addRecord()
{
	var formdata = jQuery(xl_jqGrid.options.addform).serialize() + "&mode=ajax&"; 
	var mydialog = jQuery(this); 
	
	sendAndProcessRequest(formdata, mydialog);
}

function clearErrors()
{
	$('.error-text').text('');
	$('form .field-group').removeClass('has-error');
}

function sendAndProcessRequest(formdata, mydialog)
{
	var options = xl_jqGrid.options;
	jQuery.ajax(
	{ 
		type: "POST", 
		url: options.serverpgm, 
		data: formdata,
		dataType: "json", 
		success: function (rtndata, err)
		{
			clearErrors();
			// If we've encountered an error, cancel
			if (handleError(rtndata))
			{
				return;
			}
			// Check if anything didn't pass validation and display it
			if (rtndata.hasOwnProperty("isvalid") && !rtndata.isvalid)
			{
				outputValidationMessages(mydialog, rtndata);
				return;
			}
			
			// No error, good to go
			updateStatus(rtndata);
		
			mydialog.dialog('close');
			jQuery(options.gridid).trigger("reloadGrid"); 
		}
	});
}

function outputValidationMessages(mydialog, jsondata)
{
	Object.getOwnPropertyNames(jsondata.fields).forEach(
		function (propName, idx, array) {
			$('.group' + propName, mydialog).addClass('has-error');
			$('.group' + propName + ' .error-text', mydialog).text(jsondata.fields[propName]);
		}
	);
}

// check for and handle an error response from the server
function handleError(jsondata)
{
	if (jsondata.hasOwnProperty("type") && jsondata.type == 'error')
	{
		alert(jsondata.msg);
		return true;
	}
	
	return false;
}

// check for a status update and display it to the user
function updateStatus(jsondata)
{
	if (jsondata.hasOwnProperty("type") && jsondata.type == 'status')
	{
		$('#message').html(jsondata.msg);
		
		// yellow bar the display message (as long as there is one)
		$('#message').addClass('ui-state-highlight');
	}
}

// remove the currently displayed message (if any)
function clearMessage()
{
	$('#message').html('');
	$('#message').removeClass('ui-state-highlight');
	clearErrors();
}
