/*
* Global variables
*/
var sURL = "?task=dispList";
var sqlEOF = "000000100";
var Blanks = "";
var updateElement;
var bNextLinkVisible;
var mainPageId;
var timeoutId;
/*
* Function bound to the mobileinit event. Used for configuring global defaults
* and overriding jQuery mobile options.
*/
function setGlobalDefaults(Defaults)
{
	// Turn off page transitions for Android devices. Performance issues
	// with the animation. 
	if (navigator.userAgent.indexOf("Android") != -1)
    {    	
        $.mobile.defaultPageTransition = "none";
        $.mobile.defaultDialogTransition = "none";
    }
    else if(Defaults["pageTrans"])
	{
		$.mobile.defaultPageTransition = Defaults["pageTrans"];	
	}	
	
}

/*
* Called on pageinit event. Equivalent to $(document).ready() for mobile.
*/
function initPage(page_id)
{
	mainPageId = page_id;
	
	// Add keyup handler for filter input if it exists
	if($("input[data-type='search']").not("#ww_searcharg").length > 0)
	{
		$("input[data-type='search']").on("keyup",keyUpHandler);
	}
	
	$(document).off("pagechange").on("pagechange", function(event, options)
	{
		if(options.toPage.selector == "#"+mainPageId && options.options.msg != Blanks)
		{
			showPopupMessage(options.options.msg, options.options.msgType);	
		} 
	});
	
	$('#main-list').on('listviewafterrefresh',function()
	{
		$('#main-list').trigger('create');
	});
}

/*****************************************************************
                        Tap/Click handlers
*****************************************************************/
/*
* Event handler attached to the More... button.
*/
function nextClickHandler(event)
{
	// Stop the normal processing. Produces 'page load error' jQ mobile message
	event.stopImmediatePropagation();
	event.preventDefault();
	
	$.ajax(
	{
		url:sURL,
		dataType:"json",
		success: displayListData,
		error: displayError
	});	
}

/*
* Event handler attached to the list entries. Stores the element reference
* for update and delete operations.
*/
function chgClickHandler(event)
{
	// Reference to the li entry user clicked on
	updateElement = $(this.parentElement).closest("li");
}

/*
* Event handler for the Add button on the add screen. 
*/
function addClickHandler(event)
{
	// Stop the normal processing. Produces 'page load error' jQ mobile message
	event.stopImmediatePropagation();
	event.preventDefault();
	
	$.ajax(
	{
		url: [location.protocol, '//', location.host, location.pathname].join(''),
		dataType:"json",
		type: "POST",
		data: $("#xl-add-frm").serialize()
	}).done(function (data)
	{
		if(data.hasOwnProperty('msgtype') && data.msgtype === "error")
		{
			handleError(data);
			return;
		}
		
		if(data.hasOwnProperty('isvalid') && data.isvalid === false)
		{
			displayValidationResults(data);
			return;
		}
		
		location.href = data.url;
	}).fail(displayError);	
}

/*
* Event handler attached to the Update button. Performs the endchange PML
* task and then dynamically updates the row entry.
*/
function updClickHandler(event)
{
	// Stop the normal processing. Produces 'page load error' jQ mobile message
	event.stopImmediatePropagation();
	event.preventDefault();
	
	$.ajax(
	{
		url: [location.protocol, '//', location.host, location.pathname].join(''),
		dataType:"json",
		type: "POST",
		data: $("#xl-chg-frm").serialize()
	}).done(function (data)
	{
		if(data.hasOwnProperty('msgtype') && data.msgtype === "error")
		{
			handleError(data);
			return;
		}
		
		if(data.hasOwnProperty('isvalid') && data.isvalid === false)
		{
			displayValidationResults(data);
			return;
		}
			
		updateElement[0].outerHTML = data.list_data;
		updateElement.trigger("create");
		$("#main-list").listview("refresh");		
		$(".xl-btn-chg").off("click").on("click", chgClickHandler);
			
		$.mobile.changePage($("#"+mainPageId), {msg: data.msgtext, msgType: data.msgtype});
	}).fail(displayError);	
}

function displayValidationResults(jsondata)
{
	Object.getOwnPropertyNames(jsondata.fields).forEach(
    	function (propName, idx, array) {
        	$('#group' + propName).addClass('has-error');
            $('#group' + propName + ' .error-text').text(jsondata.fields[propName]);
        }
  	);
}

function handleError(data)
{
	showPopupMessage(data.msgtext, data.msgtype);
}
/*
* Event handler for the 'Delete' button on the delete confirm dialog.
*/
function dltClickHandler(event)
{ 
	$("#xl-dlt-frm>input").prop("disabled",false);
	$.ajax(
	{
		url: [location.protocol, '//', location.host, location.pathname].join(''),
		dataType:"json",
		type: "POST",
		data: $("#xl-dlt-frm").serialize(),
		success: function(data,textStatus,jqHXR)
		{
			// If no error
			if(data.msgtype == "status")
			{
				$(updateElement).remove();	
				$("#main-list").listview("refresh");
			}
			$.mobile.changePage($("#"+mainPageId), {msg: data.msgtext, msgType: data.msgtype});
		},
		error: displayError
		
	});	
}	

/*
* Keyup event handler for the filter. Used to hide the More... button.
*/
function keyUpHandler(event)
{
	if($(this).val() == Blanks && bNextLinkVisible)
	   	$("#next-link").show(200);
    else
    	$("#next-link").hide(200);
}


/********************************************************************
                            Form Handlers
********************************************************************/
/*
* Attached to the submit event of the Search input.
*/
function filtFormHandler(event)
{
	// Prevent the default 
	event.stopImmediatePropagation();
	event.preventDefault();
	
	$("#main-list>li").remove();
	
	$.ajax(
	{
		url: [location.protocol, '//', location.host, location.pathname].join(''),
		dataType:"json",
		type: "POST",
		data: $("#xl-search-form").serialize(),
		success: displayListData, 
		error: displayError	
	});	
}

/*************************************************
             Callback Functions 
*************************************************/
/*
* Ajax callback function that displays the list data on sucess
*/
var displayListData = function(data,textStatus,jqHXR)
{
	var MainList = $("#main-list");
	if(data.msgtext == Blanks || data.msgtype == "status")
	{
	 	MainList.append(data.list_data);
	    MainList.listview("refresh");
	    $(".xl-btn-chg").off("click").on("click", chgClickHandler);
	    
	    sURL = "?task=dispList&page=" + data.next_page;
	    // data.rcd_count is for PHP. data.sqlcod for ILE
	    if((data.rcdCount && data.rcdCount <= 0) || (data.sqlcod && data.sqlcod == sqlEOF))
	    {
	    	$("#next-link").hide();
	    	bNextLinkVisible = false;	
	    }
	    else
	    {
	    	$("#next-link").show();
	    	bNextLinkVisible = true;
	    }
	}
	else
	{
		showPopupMessage(data.msgtext);	
	}
}

/*
* Ajax callback function to display message on error
*/
var displayError = function(jqXHR, textStatus, errorThrown)
{
	showPopupMessage(errorThrown);	
}

/********************************************************
 Misc Functions
********************************************************/
/*
* Display the message popup
*/
function showPopupMessage(message, messageType)
{
	var dialog = $("#message-dialog");
	var options = {transition: "pop", positionTo: "window", theme: "b", overlayTheme: ""};
	
	if(messageType == "error")
	{
		options.theme = "e";
		options.overlayTheme = "a";	
	}
	dialog.popup(options);
	dialog.popup("open").html(message);
	timeoutId =	window.setTimeout(function(){closePopup(dialog)},2000);
}

/*
* Close the 'popup' dialog provided. 
*
* @param (jQ obj reference). jQ 'popup' object to close
*
*/
function closePopup(dialog)
{
	window.clearTimeout(timeoutId);
	dialog.popup("close");
}	