jQuery(document).ready( function()
{
	// prompt to delete the record
	$('#deletebutton').click( function()
	{
		$('#change-form #task').val('confdlt');
		$('#change-form').submit();
	});

	// go back to an empty prompt page
	$('#cancelchangebutton').click( function()
	{
		document.location.href = '?task=default&msg=Record%20changes%20cancelled.';
	});
	$('#canceldeletebutton').click( function()
	{
		document.location.href = '?task=default&msg=Record%20deletion%20cancelled.';
	});
	$('#canceladdbutton').click( function()
	{
		document.location.href = '?task=default&msg=Record%20addition%20cancelled.';
	});
	
	// yellow bar the display message (as long as there is one)
	if( $('#display-message').html() != "" && $('#display-message').html() != '&nbsp;')
	{
		$('#display-message').addClass('display-message-active').removeClass('display-message-active', 2000);
	}
});