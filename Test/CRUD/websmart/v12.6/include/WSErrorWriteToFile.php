<?php

require_once('WSErrorHandler.php');

class WSErrorWriteToFile extends WSErrorHandler
{

	protected $db2conn;
	protected $errorFile; // database file to write to
	
	public function SetDbConnection($conn)
	{
		$this->db2conn = $conn;
	}
	
	public function SetErrorFile($errFile)
	{
		$this->errorFile = $errFile;
	}
	
	/**
 	 *	Handle custom errors called by set_error_handler
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
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting, so let it fall
			// through to the standard PHP error handler
			return false;
		}
		
		
		$fileParms['USERID'] = "";
		$fileParms['VERSION'] = "PHP: " . PHP_VERSION;
		$fileParms['SITE'] = "WSErrorHandler.php";
		$fileParms['ERRORNO'] = $errno;
		$fileParms['LINENO'] = $errline;
		$fileParms['DATETIME'] = date("Y-m-d H:i:s");
		$fileParms['JOBNUM'] = "";
		$fileParms['PROGRAM'] = $errfile;
		$fileParms['MSGSTRING'] = $errstr;
		$fileParms['LASTERROR'] = ""; 
		
		
		// $errno defined at: http://php.net/manual/en/errorfunc.constants.php
		// The following error types cannot be handled with a user defined function: 
		// E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, 
		// and most of E_STRICT raised in the file where set_error_handler() is called. 
		
		// let PHP handle the error if we don't want to do this
		if (in_array($errno, $this->ignoreErrors))
		{
			return false;
		}
		
		$this->WriteToFile($fileParms);
		
	    /* Don't execute PHP internal error handler */
    	return true;
	}
	
	/*
 	*	Function Description: Log the error in the ERRHANDLER file
 	*
	*	@param array $fileParms
	*		$fileParms['USERID']
	*		$fileParms['VERSION']
	*		$fileParms['SITE'] 
	*		$fileParms['ERRORNO']
	*		$fileParms['LINENO']
	*		$fileParms['DATETIME']
	*		$fileParms['JOBNUM']
	*		$fileParms['PROGRAM']
	*		$fileParms['MSGSTRING']
	*		$fileParms['LASTERROR']
	*
 	*/
	public function WriteToFile($fileParms) 
	{				
		$fileParms['MSGSTRING'] = substr(trim($fileParms['MSGSTRING']), 0, 500);
		
		$insert = "INSERT INTO $this->pTable (USERID, VERSION, SITE, ERRORNO, LINENO,
						DATETIME, JOBNUM, PROGRAM, MSGSTRING, LASTERROR)
		    			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$stmt = db2_prepare($this->db2conn, $insert);
		if ($stmt) 
		{
			$result = db2_execute($stmt, $fileParms);
		}
	}
}