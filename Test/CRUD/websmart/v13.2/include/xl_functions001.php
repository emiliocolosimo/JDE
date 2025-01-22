<?php

//  Security Notice: 
//
//  Your Apache configuration file should contain the following directive:
//
//  <DirectoryMatch /esdi/websmart/v*/include/>
//  Order Deny,Allow
//  Deny From all
//  </DirectoryMatch> 
//
//
//  This will deny requests to view this file and any other files in the /include/ directory.

// default values: if UserIDs and Passwords are left blank, profile NOBODY will be used.
$pf_i5UserID = '';
$pf_i5Password = '';
$pf_i5IPAddress = '';

/* DB2 connection details */
$pf_db2SystemName = '';
$pf_db2UserID = '';
$pf_db2Password = '';

/* MySQL connection details */
$pf_mysqlUrl      = '';
$pf_mysqlUserId   = '';
$pf_mysqlPassword = '';
$pf_mysqlDataBase = '';

/* Oracle connection details */
$pf_orclDB = '';
$pf_orclUserId = '';
$pf_orclPassword = '';

/* Encryption key for xl_encrypt, xl_decrypt functions */
$pf_encrypt_key;

global $environments;
$environments = array();

// Optional file with user preferences
@include('xl_user_preferences.php');

// Functions that are shared with the object oriented templates are now defined in xl_functions.php
require_once('xl_functions.php');

function xl_set_libl($liblname,$liblconn=null)
{
	$liblname = strtoupper($liblname);
	global $pf_liblLibs;

	$parametersIn = array (
		array ("name"=>"LIBLNM", "io"=>I5_IN, "type" => I5_TYPE_CHAR,"length"=> 10),
		array ("name"=>"RTVLIBL","io"=>I5_IN, "type" => I5_TYPE_CHAR,"length"=> 550),
		array ("name"=>"SETRTN", "io"=>I5_INOUT, "type" => I5_TYPE_CHAR,"length"=> 1));

	if(is_null($liblconn))
	{
		$pgm = i5_program_prepare("xl_webspt/xl_setlibl",$parametersIn);
	}
	else
	{
		$pgm = i5_program_prepare("xl_webspt/xl_setlibl",$parametersIn,$liblconn);
	}
	if (!$pgm)
	{
		$errorTab = i5_error();
		var_dump($errorTab);
		return false;
	}

	// if we need to use *FILES, then construct the list of files
	$AllLibs = '';
	if ($liblname == '*FILES')
	{
		foreach ($pf_liblLibs as $lib)
		{
			$AllLibs .= $lib . ' ';
		}
	}

	$pgmcall = i5_program_call($pgm,
				array("LIBLNM" =>$liblname,"RTVLIBL" =>$AllLibs),
				array("SETRTN" => "xl_set_libl_rtnval"));
	// to work with the compatibility wrapper for IBM i Toolkit for PHP, 
	// we need to extract the output variables
	if (function_exists('i5_output'))
	{
		extract(i5_output());
	}

	if (!$pgmcall)
	{
		$errorTab = i5_error();
		var_dump($errorTab);
		return false;
	}

	// get return values
	if($xl_set_libl_rtnval != "0")
	{
		return false;
	}

	// if we haven't failed yet, then we succeeded!
	return true;
}


function xl_i5_connect()
{
	// DB Connection code
	$conn = i5_connect($GLOBALS['pf_i5IPAddress'],
			$GLOBALS['pf_i5UserID'],
			$GLOBALS['pf_i5Password']);
	return $conn;
}

function xl_db2_connect($options=null)
{
	if ($options === null)
	{
		$conn = db2_connect($GLOBALS['pf_db2SystemName'],
				        $GLOBALS['pf_db2UserID'],
				        $GLOBALS['pf_db2Password']);		
	}
	else
	{
		$conn = db2_connect($GLOBALS['pf_db2SystemName'],
				        $GLOBALS['pf_db2UserID'],
				        $GLOBALS['pf_db2Password'],
				        $options);
    }
	return $conn;
}

function xl_oci_connect()
{
	// DB Connection code
	$conn = oci_connect(
			$GLOBALS['pf_orclUserId'],
			$GLOBALS['pf_orclPassword'],
			$GLOBALS['pf_orclDB']);
	return $conn;
}

function xl_set_row_color($color1, $color2)
{
	global $pf_altrowclr;
	// set the color
	if(!isset($pf_altrowclr) || $pf_altrowclr == $color2)
		$pf_altrowclr = $color1;
	else
		$pf_altrowclr = $color2;
}

function xl_set_env($envname)
{
	if($envname == '')
	{
		return;
	}
	
	global $environments;
	if( array_key_exists($envname, $environments) )
	{
		$env_properties = $environments[$envname];

		// if i5 connection properties are defined, retrieve them
		if( array_key_exists('i5ip', $env_properties) ) 
		{
			$GLOBALS['pf_i5IPAddress'] = $env_properties['i5ip'];
		}
		if( array_key_exists('i5user', $env_properties) )
		{
			$GLOBALS['pf_i5UserID'] = $env_properties['i5user'];
		}
		if( array_key_exists('i5password', $env_properties) )
		{
			$GLOBALS['pf_i5Password'] = $env_properties['i5pass'];
		}
			
		if( array_key_exists('libl', $env_properties) )
		{
			$libs = $env_properties['libl'];
			if( gettype($libs) == "array" )
			{
				// set library list
				global $pf_liblLibs;
				$pf_liblLibs = $libs;
				
				// set default db2_connect library list
				global $db2_options;
				$db2_options['i5_libl'] = implode(' ', $pf_liblLibs);
			}
		}
				
		// handle different database types differently
		switch($env_properties['type'])
		{
			case 'mysql':
				$GLOBALS['pf_mysqlUrl'] = $env_properties['url'];
				$GLOBALS['pf_mysqlDataBase'] = $env_properties['db'];
				$GLOBALS['pf_mysqlUserId']   = $env_properties['user'];
				$GLOBALS['pf_mysqlPassword'] = $env_properties['pass'];
				return;
				
			case 'oracle':
				$GLOBALS['pf_orclDB'] = $env_properties['db'];
				$GLOBALS['pf_orclUserId'] = $env_properties['user'];
				$GLOBALS['pf_orclPassword'] = $env_properties['pass'];
				return;
			
			case 'db2':
				$GLOBALS['pf_db2SystemName'] = $env_properties['url'];
				$GLOBALS['pf_db2UserID'] = $env_properties['user'];
				$GLOBALS['pf_db2Password'] = $env_properties['pass'];
				return;
			default:
				die('Environment '.$envname.' does not have a defined type.'); 
		}
	}
}

// return a segment's content instead of outputting it to the browser
function getseg($seg)
{
	ob_start();
	
	wrtseg($seg);
	
	return ob_get_clean();
}

// Get the nexus session information
function xl_get_nexus_info($nxinfval, $nexuslib="XL_NEXUS")
{
	$options = array('i5_naming' => DB2_I5_NAMING_ON);

	$db2conn = xl_db2_connect($options);

	// Retrieve the session information from the nexus cookie
	$sessid = $_COOKIE['nexusaccess'];
	if ($sessid == '')
	{
		echo "nexus session cookie not found.";
		die;
	}

	// Retrieve session data
	$selstring = "select SVSESSION, SVVARID, SVVARSEQ, SVDATA from XL_WEBSPT/PW_SVARF "
	. " where SVSESSION ='" . $sessid . "' AND SVVARID='SESSDS'";
	if (!($stmt = db2_exec($db2conn, $selstring, array('CURSOR' => DB2_SCROLLABLE))))
	{
		echo "Error ".db2_stmt_error() .":".db2_stmt_errormsg(). "";
		die;
	}
	$row = db2_fetch_assoc($stmt);

	if ($row['SVDATA'] == '')
	{
		return '';
	}

	// Get site number, user number and user id, these will be used to
	// get information from the user file (XL_SMSLIB/SMUSRF)
	$siteno = substr($row["SVDATA"], 0, 5);
	$userno = substr($row["SVDATA"], 5, 7);
	$userid = substr($row["SVDATA"], 12, 128);

	// Retrieve user information from the user file
	$selstring = "select * from ".$nexuslib."/SMUSRF where USSITNBR = $siteno and USUNBR = $userno and USUSER = '$userid'";
	if (!($stmt = db2_exec($db2conn, $selstring, array('CURSOR' => DB2_SCROLLABLE))))
	{
		echo " Error ".db2_stmt_error() .":".db2_stmt_errormsg(). "";
		die;
	}
	$row = db2_fetch_assoc($stmt);
	$nxdata = $row;

	// Close database connection
	db2_close($db2conn);

	// Return the appropriate value
	switch($nxinfval)
	{
		case "*USERID": return $nxdata["USUSER"]; break;
		case "*FNAME": return $nxdata["USFNAM"]; break;
		case "*LNAME": return $nxdata["USLNAM"]; break;
		case "*EMAIL": return $nxdata["USEMAL"]; break;
		case "*USERNBR": return $nxdata["USUNBR"]; break;
		case "*MANAGE": return $nxdata["USGMNG"]; break;
		case "*SITENUM": return $nxdata["USSITNBR"]; break;
		default: return $nxdata;
	}
}

?>