<?php

// default values: if UserIDs and Passwords are left blank, profile NOBODY will be used.
class xl_defaults
{
	public $pf_i5UserId, $pf_i5Password, $pf_i5IPAddress; // i5 connection values
	public $pf_db2SystemName, $pf_db2UserId, $pf_db2Password, $pf_db2Options; // db2 connection values
	public $pf_mysqlUrl, $pf_mysqlUserId, $pf_mysqlPassword, $pf_mysqlDataBase; // mysql connection values
	public $pf_orclDB, $pf_orclUserId, $pf_orclPassword; // oracle connection values
	public $pf_db2OdbcDsn, $pf_db2OdbcUserID, $pf_db2OdbcPassword, $pf_db2OdbcOptions; // odbc connection values
	public $environments;
	
	function __construct()
	{
    // DB2 constants aren't defined on Windows (standalone client), so we have
    // to check if they're defined
		if ( defined('DB2_I5_NAMING_ON') )
			$options = array('i5_naming' => DB2_I5_NAMING_ON);
		else
			$options = array();

		// default values: if UserIDs and Passwords are left blank, profile NOBODY will be used.
		$pf_i5UserID = '';
		$pf_i5Password = '';
		$pf_i5IPAddress = '';
		$pf_db2SystemName = '';
		$pf_db2UserID = null;
		$pf_db2Password = null;
		$pf_db2Options = $options;
		$pf_db2PDOOptions = array();
		$pf_db2LibraryList = "";
		$pf_db2OdbcDsn = 'Driver={IBM i Access ODBC Driver};System=localhost;SIGNON=2;UID=JDESPYTEST;Pwd=JBALLS18';
		$pf_db2OdbcUserID = 'JDESPYTEST';
		$pf_db2OdbcPassword = 'JBALLS18';
		$pf_db2OdbcOptions = ';TRANSLATE=1;NAM=1;TSFT=1';
		$pf_db2PDOOdbcOptions = array();
		$pf_mysqlUrl = '';
		$pf_mysqlUserId = '';
		$pf_mysqlPassword = '';
		$pf_mysqlDataBase = '';
		$pf_mssqlUrl = '';
		$pf_mssqlPort = '';
		$pf_mssqlUserId = '';
		$pf_mssqlPassword = '';
		$pf_mssqlDataBase = '';		
		$pf_orclDB = '';
		$pf_orclUserId = '';
		$pf_orclPassword = '';
		$environments = array(
		//	'development' => array(
		//		'i5ip' => '127.0.0.1',
		//		'i5user' => 'nobody',
		//		'i5pass' => 'test',
		//		'libl' => array("QGPL"), //for db2 on the IBMi (first one is highest in the list)
		//		'type' => "mysql", // (OR "oracle", "db2", "mssql"),
		//		'url' => '127.0.0.1', // for mysql, db2, mssql
		//		'db' => '', //(database name for mysql, mssql and oracle)
		//		'user' => '',
		//		'pass' => '',
		//		'port' => '', //for mssql only
		//	),
		//	'production' => array()
		);
	
		// Optional file with user preferences
		if(file_exists(stream_resolve_include_path('xl_user_preferences.php')))
		{
        	include 'xl_user_preferences.php';
        }
		
		$this->pf_i5UserId		 = $pf_i5UserID;
		$this->pf_i5Password	 = $pf_i5Password;
		$this->pf_i5IPAddress	 = $pf_i5IPAddress;
		$this->pf_db2SystemName	 = $pf_db2SystemName;
		$this->pf_db2UserId		 = $pf_db2UserID;
		$this->pf_db2Password	 = $pf_db2Password;
		$this->pf_db2Options 	 = $pf_db2Options;
		$this->pf_db2LibraryList = $pf_db2LibraryList;
		$this->pf_db2PDOOptions  = $pf_db2PDOOptions;
		$this->pf_db2OdbcDsn	 	 = $pf_db2OdbcDsn;
		$this->pf_db2OdbcUserID		 = $pf_db2OdbcUserID;
		$this->pf_db2OdbcPassword	 = $pf_db2OdbcPassword;
		$this->pf_db2OdbcOptions	 = $pf_db2OdbcOptions;
		$this->pf_db2PDOOdbcOptions  = $pf_db2PDOOdbcOptions;
		$this->pf_mysqlUrl		 = $pf_mysqlUrl;
		$this->pf_mysqlUserId	 = $pf_mysqlUserId;
		$this->pf_mysqlPassword	 = $pf_mysqlPassword;
		$this->pf_mysqlDataBase	 = $pf_mysqlDataBase;
		$this->pf_mssqlPort		 = $pf_mssqlPort;
		$this->pf_mssqlUrl		 = $pf_mssqlUrl;
		$this->pf_mssqlUserId	 = $pf_mssqlUserId;
		$this->pf_mssqlPassword	 = $pf_mssqlPassword;
		$this->pf_mssqlDataBase	 = $pf_mssqlDataBase;		
		$this->pf_orclDB		 = $pf_orclDB;
		$this->pf_orclUserId	 = $pf_orclUserId;
		$this->pf_orclPassword	 = $pf_orclPassword;
		
		$this->environments 	 = $environments;
	}
}

?>
