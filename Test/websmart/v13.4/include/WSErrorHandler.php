<?php

//	Program Name:		WSErrorHandler.php 
//	Program Title:		 
//	Purpose:			Generic WS Error Handling class to handle some errors differently.       

//	Program Modifications:

class WSErrorHandler 
{
	
	protected $ignoreErrors = array();
	
	function __construct() { }
	
	public function SetErrorsToIgnore(...$errnos) 
	{
		$this->errNoToHandle = $errnos;
	}
	
	
	/**
 	 *	Handle custom errors called by set_error_handler. This is the default function that won't do much of anything.
 	 *  Instead it is expected to extend this class and overwrite the customErrorHandler function.
 	 *
	 *	@param int errno
	 *	    The first parameter, errno, contains the level of the error raised, as an integer. 
	 *	@param string errstr
	 *	    The second parameter, errstr, contains the error message, as a string. 
	 *	@param string errfile
	 *	    The third parameter is optional, errfile, which contains the filename that the error was raised in, as a string. 
	 *	@param int errline
	 *	    The fourth parameter is optional, errline, which contains the line number the error was raised at, as an integer. 
	 *	@param array errcontext (deprecated as of PHP 7.2)
	 *	    The fifth parameter is optional, errcontext, which is an array that points to the active symbol table at the point the error occurred. 
	 *	    In other words, errcontext will contain an array of every variable that existed in the scope the error was triggered in. 
	 *	    User error handler must not modify error context. 
	 *	
	 *	@return true/false If the function returns FALSE then the normal error handler continues. 
 	 *
 	 */
	public function customErrorHandler($errno, $errstr, $errfile, $errline)
	{
		/* Execute PHP internal error handler */
		return false;
	}
}
