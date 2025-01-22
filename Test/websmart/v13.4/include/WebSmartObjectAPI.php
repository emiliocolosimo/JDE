<?php

require_once "Nexus.php";

class WebSmartObjectAPI
{
	protected $db_connection = '';
	protected $defaults = array();
	protected $pf_scriptname = '';
	protected $pf_liblLibs = array(); 
	protected $pf_task = '';
	protected $environments = array();
	protected $pf_set_name = '';
	protected $pf_altrowclr = '';
	
	protected $requestMethod = '';
	protected $contentType = '';
	protected $jsonParms;
	
	protected $jsonOutput = [
		'status' => '',
		'message' => ''
		];
	
	protected $programState = array();
	
	protected $msgtext = '';
	protected $msgtype = '';
	
	protected $requiredIndicator = "*";
	protected $optionalIndicator = "(Optional)";
	protected $showRequiredIndicator = false;
	protected $showOptionalIndicator = true;
	protected $hasErrorClass = "has-error";
	protected $requiredValidatorName = "WSRequired";

	function __construct()
	{
		include('xl_defaults.php');
		$this->defaults = (array) new xl_defaults;
		
		$this->contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';
		
		if (isset($_SERVER['REQUEST_METHOD']))
		{
			$this->requestMethod = $_SERVER['REQUEST_METHOD'];
			switch($this->requestMethod)
			{
				case 'GET': 
					$request_data = &$_GET; 
					break;
				case 'POST': 
					$request_data = &$_POST; 
					break;
				case 'PUT':
				case 'PATCH': 
    				if (stripos($this->contentType, 'application/json') === false)
    				{
        				parse_str(file_get_contents('php://input'), $request_data);
    				}
    				break;
				case 'DELETE': 
					if ($_SERVER['QUERY_STRING'] !== '')
					{
						parse_str($_SERVER['QUERY_STRING'], $request_data);
					} 
					else if (stripos($this->contentType, 'application/json') === false) 
    				{
        				parse_str(file_get_contents('php://input'), $request_data);
    				}
					break;
				default: $request_data = &$_POST; 	
		    }
		}

		// we assume json, then read the file and set our request_data array
		if (stripos($this->contentType, 'application/json') !== false)
		{
			$content = trim(file_get_contents('php://input'));
			$decoded = json_decode($content, true);
			if (!is_array($decoded)) 
			{
				$this->jsonOutput['status'] = 'error';
				$this->jsonOutput['message'] = 'Received content is not valid JSON.';
				die(json_encode($this->jsonOutput));
			}
			$request_data = $decoded;
		}

		$this->jsonParms = $request_data;
		
		if(isset($request_data['task']))
		{
			$this->pf_task = $request_data['task'];
		}
		else
		{
			$this->pf_task = 'default';
		}

		$this->setDBOptions();

		if(!isset($_SESSION)) session_start();
	}
	
	// Check if the request method used is part of the argument list of this function, if not
	// we know the method is not allowed
	protected function checkRequestMethod(...$methods)
	{
		$isAllowed = false;
		foreach($methods as $method)
		{
			if ($method === $this->requestMethod)
			{
				$isAllowed = true;
			}
		}
		
		if (!$isAllowed)
		{
			// setting http status code 405 for the matching method not allowed error
			$this->closeConnectionWithError('error', 'Request method not allowed for this task.', 405);
		}
	}


	// set options, mainly library list for IBM DB2 as well as PDO if the constant exists
	// if no library list exists, we default to *FILES (pf_liblLibs)
	private function setDBOptions()
	{
		//if there are no defaults for the library list, we will use "*FILES", which is a list of libraries
		//the user selected the files from during completing the wizard.
		if(strlen($this->defaults['pf_db2LibraryList']) == 0 && count($this->pf_liblLibs) > 0 && is_array($this->pf_liblLibs )) 
		{
			$this->defaults['pf_db2LibraryList'] = implode(' ', $this->pf_liblLibs);
		}
		//precaution, even though it should not be empty by now
		if($this->defaults['pf_db2LibraryList'] != '') 
		{
			$this->defaults['pf_db2Options']['i5_libl'] = $this->defaults['pf_db2LibraryList'];
		}

		if(defined('PDO::I5_ATTR_DBC_LIBL'))
		{
			$this->defaults['pf_db2PDOOptions'] = array(
				PDO::I5_ATTR_DBC_SYS_NAMING => true,
				PDO::I5_ATTR_DBC_LIBL => $this->defaults['pf_db2LibraryList']
			);
		}
	}

	// default sort order that will use keyfields or if a file has been selected that
	// does not have keys, it will use the unique fields, which the user identified 
	// when adding the file in the wizard through a prompt
	protected function getDefaultSort(array $defaultSort = array(), array $backupSort = array())
	{
		$sortFields = $defaultSort;
		
		if(count($sortFields) == 0)
		{
			$sortFields = $backupSort;
		}

		if(count($sortFields) > 0)
		{
			return implode(', ', $sortFields);
		}
		
		return '';
	}

	protected function hasParm($key)
	{
		if(isset($this->jsonParms[$key]))
		{
			return true;
		}
		return false;
	}

	protected function getParm(&$field, $key, $enctype = '')
	{
		$field = '';
		if(isset($this->jsonParms[$key]))
		{
			$xl_val = $this->jsonParms[$key];
			if ($enctype != '')
			{
				if (is_array($xl_val))
				{
					$xl_val = xl_encode_array($xl_val, $enctype);
				}
				else
				{
					$xl_val = xl_encode($xl_val, $enctype);
				}
			}
			
			$field = $xl_val;
		}
	}

	protected function getParameters(array $parametersToFetchArray, $enctype = '')
	{
		$fetchedParameters = array_flip($parametersToFetchArray);
		array_walk($fetchedParameters, array($this, "getParm"), $enctype);
		return $fetchedParameters;
	}
	
	protected function closeConnectionWithError($status, $message, $httpstatus = 0)
	{
		if (is_resource($this->db_connection)) 
		{
			$isClosed = @db2_close($this->db_connection);
		}

		if ($status === 'error' && $httpstatus == 0) 
		{
			$httpstatus = 500;
		}
		// probably not going to be true because this function is in theory only for connection errors
		// it shouldn't be called for a success, but we'll put it in here anyway
		else if ($httpstatus == 0)
		{
			$httpstatus = 200;
		}
		$this->setMessageWithStatus($status, $message, $httpstatus);
		die(json_encode($this->jsonOutput));
	}

	protected function setMessageWithStatus($status, $message, $httpstatus)
	{
		$this->jsonOutput['status'] = $status;
		$this->jsonOutput['message'] = $message;
		$this->jsonOutput['httpstatus'] = $httpstatus;
	}

	// the following moved in from xl_functions
	function xl_set_libl($liblname,$liblconn=null)
	{
		$liblname = strtoupper($liblname);
	
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
			foreach ($this->pf_liblLibs as $lib)
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
		$conn = i5_connect($this->defaults['pf_i5IPAddress'],
				$this->defaults['pf_i5UserId'],
				$this->defaults['pf_i5Password']);
		return $conn;
	}
	
	function xl_db2_connect($options=null)
	{
		
		if ($options === null)
		{
			$options = $this->defaults['pf_db2Options'];
		}
		$conn = db2_connect($this->defaults['pf_db2SystemName'],
					        $this->defaults['pf_db2UserId'],
					        $this->defaults['pf_db2Password'],
					        $options);
		return $conn;
	}
	
	function xl_mysql_connect()
	{
		$conn = mysql_connect($this->defaults['pf_mysqlUrl'],
							  $this->defaults['pf_mysqlUserId'],
							  $this->defaults['pf_mysqlPassword']);
		  	return $conn;
	}
	
	function xl_mysql_select_db($conn)
	{
	
		$db_selected = mysql_select_db($this->defaults['pf_mysqlDataBase'], $conn);
	
		return $db_selected;
	}
	
	function xl_oci_connect()
	{
		// DB Connection code
		$conn = oci_connect(
				$this->defaults['pf_orclUserId'],
				$this->defaults['pf_orclPassword'],
				$this->defaults['pf_orclDB']);
		return $conn;
	}
	
	function xl_set_env($envname)
	{
		if($envname == '')
		{
			return;
		}
		if( array_key_exists($envname, $this->defaults['environments']) )
		{
			$env_properties = $this->defaults['environments'][$envname];
			// if i5 connection properties are defined, retrieve them
			if( array_key_exists('i5ip', $env_properties) ) 
			{
				$this->defaults['pf_i5IPAddress'] = $env_properties['i5ip'];
			}
			if( array_key_exists('i5user', $env_properties) )
			{
				$this->defaults['pf_i5UserId'] = $env_properties['i5user'];
			}
			if( array_key_exists('i5pass', $env_properties) )
			{
				$this->defaults['pf_i5Password'] = $env_properties['i5pass'];
			}
				
			if( array_key_exists('libl', $env_properties) )
			{
				$libs = $env_properties['libl'];
				if( gettype($libs) == "array" )
				{
					$this->pf_liblLibs = $libs;
				}

				// set default db2_connect library list
				//pf_liblLibs should be populated during using the wizard, so in case there are no 'libl' in the
				//environment use this (equals *FILES)
				if(count($this->pf_liblLibs) > 0) {
					$this->defaults['pf_db2LibraryList'] = implode(' ', $this->pf_liblLibs);
				}
				$this->defaults['pf_db2Options']['i5_libl'] = $this->defaults['pf_db2LibraryList'];
			}

			// handle different database types differently
			switch($env_properties['type'])
			{
				case 'mysql':
					$this->defaults['pf_mysqlUrl'] = $env_properties['url'];
					$this->defaults['pf_mysqlDataBase'] = $env_properties['db'];
					$this->defaults['pf_mysqlUserId']   = $env_properties['user'];
					$this->defaults['pf_mysqlPassword'] = $env_properties['pass'];
					return;
					
				case 'oracle':
					$this->defaults['pf_orclDB'] = $env_properties['db'];
					$this->defaults['pf_orclUserId'] = $env_properties['user'];
					$this->defaults['pf_orclPassword'] = $env_properties['pass'];
					return;
				
				case 'db2':
					$this->defaults['pf_db2SystemName'] = $env_properties['url'];
					$this->defaults['pf_db2UserId'] = $env_properties['user'];
					$this->defaults['pf_db2Password'] = $env_properties['pass'];
					return;
					
				case 'mssql':
					$this->defaults['pf_mssqlUrl'] = $env_properties['url'];
					$this->defaults['pf_mssqlPort'] = $env_properties['port'];
					$this->defaults['pf_mssqlDataBase'] = $env_properties['db'];
					$this->defaults['pf_mssqlUserId']   = $env_properties['user'];
					$this->defaults['pf_mssqlPassword'] = $env_properties['pass'];
					return;

				default:
					die('Environment '.$envname.' does not have a defined type.'); 
			}
		}
	}
	
	function xlGetNexusInfo($nxinfval, $nexusLib = "*LIBL")
	{
		// the db connection is a resource if it's a db2 connection, for a pdo connection we should
		// have an object instead
		if (is_resource($this->db_connection)) 
		{
			return;
		}
		
		$Nexus = new Nexus();
		$Nexus->setDbConnection($this->db_connection);
		
		return $Nexus->getNexusInfo($nxinfval, $nexusLib);
	}
	
	// Get the nexus session information
	function xl_get_nexus_info($nxinfval, $nexuslib="XL_NEXUS")
	{
		$options = array('i5_naming' => DB2_I5_NAMING_ON);
	
		$db2conn = $this->xl_db2_connect($options);
	
		// Retrieve the session information from the nexus cookie
		$sessid = $_COOKIE['nexusaccess'];
		if ($sessid === '' || $sessid === '*NONE')
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
	
	/*
	 * START - Functions for Validation
	 */
	protected function setError($isValid, &$fieldSettings, $errorText)
	{
		if(!$isValid)
		{
			$fieldSettings['errorText'] = $errorText;
			$fieldSettings['hasError'] = true;
		}
	}
	
	protected function validate(&$formFields, $protectedFields = array())
	{
		//flag for the form as a whole
		$isValid = true;
		
		foreach($formFields as $field => &$fieldSettings)
		{
			$this->getParm($value, $field);
			if(!array_key_exists('validators', $fieldSettings))
				continue;
			
			if(in_array($field, $protectedFields))
				continue;
				
			$isFieldValid = true;
			
			foreach($fieldSettings['validators'] as $key=>$validator)
			{
				if(!$isFieldValid)
					break;
				
				if(!is_numeric($key))
				{
					$validatorName = $key . "Validator";
					$options = $validator;
				}
				else
				{
					$validatorName = $validator . "Validator";
					$options = NULL;
				}
				
				$v = new $validatorName($options);
				$isFieldValid = $v->isValid($value);
				$this->setError($isFieldValid, $fieldSettings, $v->getErrorText());
				
			}
			
			if(!$isFieldValid)
				$isValid = false;
		}
		
		unset($fieldSettings);
		
		return $isValid;
	}

	// this function is for API template
	protected function buildValidationResult($formFields) 
	{
		$result = array();
		foreach ($formFields as $fieldname => $fieldSettings) 
		{
			
			if(array_key_exists('errorText', $fieldSettings))
			{
				$result[$fieldname]['valid'] = false;
				$result[$fieldname]['error'] = vsprintf($fieldSettings['errorText'], array('This'));
			}
			else 
			{
				$result[$fieldname]['valid'] = true;
			}
		}
		return $result;
	}
	
	/*
	 * END - Functions for Validation
	 */
}

// For backwards compatability, create the WebSmartObject class as an alias of WebSmartObjectAPI
class_alias('WebSmartObjectAPI', 'WebSmartObject');

?>