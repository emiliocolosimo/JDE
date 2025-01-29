<?php

date_default_timezone_set("Europe/Rome");

header('Content-Type: application/json'); 
error_reporting(E_ALL); 
ini_set("display_errors",1);

require("envs.customer.php");
require("fields.customer.update.php");
require("fields.customer.php");
require("classes/customerUpdater.class.php");
require("classes/contactUpdater.class.php");
require("classes/contactInserter.class.php");
require("classes/addressUpdater.class.php");
require("classes/phoneUpdater.class.php");
require("classes/accountabilityUpdater.class.php");
require("classes/billingReferenceUpdater.class.php"); 
require("classes/billingReferenceInserter.class.php"); 
require("classes/bankInfoUpdater.class.php"); 
require("classes/bankInfoInserter.class.php"); 
 
include("config.inc.php"); 

 
/*
Per file con:
- relazione [0,1] => controllo se esiste eseguo update, altrimenti insert
- relazione [1,1] => update
- relazione [1,N] => delete ed insert
*/ 

$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;"; 
$user=DB2_USER; 
$pass=DB2_USER;   
$conn=odbc_connect($server,$user,$pass); 
//!!! disabilitare autocommit
if(!$conn) {
	die('{"stat":"err", "errors": [{"field":"", "msg":"connection error"}]}');
}
odbc_autocommit($conn,false); 
 
$postedBody = file_get_contents('php://input');

$env = ''; 
$hasError = false;
$resArray = json_decode($postedBody, true);
if(!$resArray) {
	die('{"stat":"err", "errors": [{"field":"", "msg":"invalid json"}]}');
}

if(!isset($resArray['F0101'])) {
	die('{"stat":"err", "errors": [{"field":"", "msg":"missing F0101"}]}');	
}

/********** CONTROLLI: ************/
$customerUpdater = new customerUpdater();
$customerUpdater->setConnection($conn);
$customerUpdater->setInputFields($updInputFields["F0101"],$updFieldType["F0101"],$updFieldSize["F0101"]);
$customerUpdater->setKeyFields($updKeyFields["F0101"]); 
$customerUpdater->setMandatoryFields($updMandatoryFields["F0101"]);
$customerUpdater->setCostantFields($updCostantFields["F0101"]);
$customerUpdater->setDefaultValueFields($updDefaultValueFields["F0101"]);
$customerUpdater->setFieldsReference($updFieldsReference["F0101"]); 
$customerUpdater->setEnvLib($envLib);
$customerUpdater->setValidator($updValidator["F0101"]);

$customerUpdater->setEnv($resArray['env']);
$res = $customerUpdater->checkMandatoryFields($resArray['F0101']['fields']);
if($res["hasErrors"]) { 
	die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
}
 
$res = $customerUpdater->validateFields();
if($res["hasErrors"]) { 
	die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
}


 
//aggiorno altri file:
//F0111 
if(isset($resArray['F0111'])) {
	 
	for($i=0;$i<count($resArray['F0111']);$i++) { 
		 
		$currentResArray = $resArray['F0111'][$i]; 
	 
		$contactUpdater = new contactUpdater();
		$contactUpdater->setConnection($conn);
		$contactUpdater->setHeaderFields($resArray['F0101']['fields']);
		$contactUpdater->setInputFields($updInputFields["F0111"],$updFieldType["F0111"],$updFieldSize["F0111"]);
		$contactUpdater->setKeyFields($updKeyFields["F0111"]); 
		$contactUpdater->setMandatoryFields($updMandatoryFields["F0111"]);
		$contactUpdater->setCostantFields($updCostantFields["F0111"]);
		$contactUpdater->setDefaultValueFields($updDefaultValueFields["F0111"]);
		$contactUpdater->setFieldsReference($updFieldsReference["F0111"]); 
		$contactUpdater->setEnvLib($envLib);
		$contactUpdater->setValidator($updValidator["F0111"]);

		$contactUpdater->setEnv($resArray['env']);
		$res = $contactUpdater->checkMandatoryFields($currentResArray);
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
		 
		$res = $contactUpdater->validateFields();
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}  
	}
}

//indirizzi:
if(isset($resArray['F0116'])) {
	$addressUpdater = new addressUpdater();
	$addressUpdater->setConnection($conn);
	$addressUpdater->setHeaderFields($resArray['F0101']['fields']);
	$addressUpdater->setF0111Fields($resArray['F0111']);
	$addressUpdater->setInputFields($updInputFields["F0116"],$updFieldType["F0116"],$updFieldSize["F0116"]);
	$addressUpdater->setKeyFields($updKeyFields["F0116"]); 
	$addressUpdater->setMandatoryFields($updMandatoryFields["F0116"]);
	$addressUpdater->setCostantFields($updCostantFields["F0116"]);
	$addressUpdater->setDefaultValueFields($updDefaultValueFields["F0116"]);
	$addressUpdater->setFieldsReference($updFieldsReference["F0116"]); 
	$addressUpdater->setEnvLib($envLib);
	$addressUpdater->setValidator($updValidator["F0116"]);

	$addressUpdater->setEnv($resArray['env']);
	$res = $addressUpdater->checkMandatoryFields($resArray['F0116']['fields']);
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
	$res = $addressUpdater->validateFields();
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
  
}

//numero di telefono:
if(isset($resArray['F0115'])) {
	$phoneUpdater = new phoneUpdater();
	$phoneUpdater->setConnection($conn);
	$phoneUpdater->setHeaderFields($resArray['F0101']['fields']);
	$phoneUpdater->setInputFields($updInputFields["F0115"],$updFieldType["F0115"],$updFieldSize["F0115"]);
	$phoneUpdater->setKeyFields($updKeyFields["F0115"]); 
	$phoneUpdater->setCostantFields($updCostantFields["F0115"]);
	$phoneUpdater->setMandatoryFields($updMandatoryFields["F0115"]);
	$phoneUpdater->setDefaultValueFields($updDefaultValueFields["F0115"]);
	$phoneUpdater->setFieldsReference($updFieldsReference["F0115"]); 
	$phoneUpdater->setEnvLib($envLib);
	$phoneUpdater->setValidator($updValidator["F0115"]);

	$phoneUpdater->setEnv($resArray['env']);
	$res = $phoneUpdater->checkMandatoryFields($resArray['F0115']['fields']);
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	} 
 
 
}

//numero di telefono:
if(isset($resArray['F0301'])) {
	$accountabilityUpdater = new accountabilityUpdater();
	$accountabilityUpdater->setConnection($conn);
	$accountabilityUpdater->setHeaderFields($resArray['F0101']['fields']);
	if(isset($resArray['F0116'])) $accountabilityUpdater->setF0116Fields($resArray['F0116']['fields']); 
	$accountabilityUpdater->setInputFields($updInputFields["F0301"],$updFieldType["F0301"],$updFieldSize["F0301"]);
	$accountabilityUpdater->setKeyFields($updKeyFields["F0301"]); 
	$accountabilityUpdater->setCostantFields($updCostantFields["F0301"]);
	$accountabilityUpdater->setMandatoryFields($updMandatoryFields["F0301"]);
	$accountabilityUpdater->setDefaultValueFields($updDefaultValueFields["F0301"]);
	$accountabilityUpdater->setFieldsReference($updFieldsReference["F0301"]); 
	$accountabilityUpdater->setEnvLib($envLib);
	$accountabilityUpdater->setValidator($updValidator["F0301"]);

	$accountabilityUpdater->setEnv($resArray['env']);
	$res = $accountabilityUpdater->checkMandatoryFields($resArray['F0301']['fields']);
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	  
	$res = $accountabilityUpdater->validateFields();
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
	$res = $accountabilityUpdater->loadDefaultValues(); 
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
 
}
 
//cliente di fatturazione:
if(isset($resArray['F01017']) && isset($resArray['F01017']['custumer_billing_code']) && $resArray['F01017']['custumer_billing_code']!="") {
	$billingReferenceUpdater = new billingReferenceUpdater();
	$billingReferenceUpdater->setConnection($conn);
	$billingReferenceUpdater->setHeaderFields($resArray['F0101']['fields']);
	$billingReferenceUpdater->setInputFields($updInputFields["F01017"],$updFieldType["F01017"],$updFieldSize["F01017"]);
	$billingReferenceUpdater->setKeyFields($updKeyFields["F01017"]); 
	$billingReferenceUpdater->setCostantFields($updCostantFields["F01017"]);
	$billingReferenceUpdater->setMandatoryFields($updMandatoryFields["F01017"]);
	$billingReferenceUpdater->setDefaultValueFields($updDefaultValueFields["F01017"]);
	$billingReferenceUpdater->setFieldsReference($updFieldsReference["F01017"]); 
	$billingReferenceUpdater->setEnvLib($envLib);
	$billingReferenceUpdater->setValidator($updValidator["F01017"]);
	$billingReferenceUpdater->setEnv($resArray['env']);
	$res = $billingReferenceUpdater->checkMandatoryFields($resArray['F01017']['fields']);
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}	
	
	//il record esiste già?
	$billingRecordExists = $billingReferenceUpdater->recordExists();
	if(isset($billingRecordExists["hasErrors"]) && $billingRecordExists["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($billingRecordExists["arrErrors"]).'}');
	} 	
	
	if($billingRecordExists) { 
		//UPDATE: 
		$res = $billingReferenceUpdater->validateFields();
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
		 
		$res = $billingReferenceUpdater->loadDefaultValues(); 
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
	} else {
		//INSERT:
		$billingReferenceInserter = new billingReferenceInserter();
		$billingReferenceInserter->setConnection($conn);
		$billingReferenceInserter->setHeaderFields($resArray['F0101']['fields']);
		$billingReferenceInserter->setInputFields($inputFields["F01017"],$fieldType["F01017"],$fieldSize["F01017"]);
		$billingReferenceInserter->setCostantFields($costantFields["F01017"]);
		$billingReferenceInserter->setMandatoryFields($mandatoryFields["F01017"]);
		$billingReferenceInserter->setDefaultValueFields($defaultValueFields["F01017"]);
		$billingReferenceInserter->setFieldsReference($fieldsReference["F01017"]); 
		$billingReferenceInserter->setEnvLib($envLib);
		$billingReferenceInserter->setValidator($validator["F01017"]);

		$billingReferenceInserter->setEnv($resArray['env']);
		$res = $billingReferenceInserter->checkMandatoryFields($resArray['F01017']['fields']);
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
		  
		$res = $billingReferenceInserter->validateFields();
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
		 
		$res = $billingReferenceInserter->loadDefaultValues(); 
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		} 
	}		
}
 
//info bancarie:
if(isset($resArray['F0030']) && isset($resArray['F0030']['customer_bank_account']) && $resArray['F0030']['customer_bank_account']!="") {
	$bankInfoUpdater = new bankInfoUpdater();
	$bankInfoUpdater->setConnection($conn);
	$bankInfoUpdater->setHeaderFields($resArray['F0101']['fields']);
	$bankInfoUpdater->setKeyFields($updKeyFields["F0030"]); 
	$bankInfoUpdater->setInputFields($updInputFields["F0030"],$updFieldType["F0030"],$updFieldSize["F0030"]);
	$bankInfoUpdater->setCostantFields($updCostantFields["F0030"]);
	$bankInfoUpdater->setMandatoryFields($updMandatoryFields["F0030"]);
	$bankInfoUpdater->setDefaultValueFields($updDefaultValueFields["F0030"]);
	$bankInfoUpdater->setFieldsReference($updFieldsReference["F0030"]); 
	$bankInfoUpdater->setEnvLib($envLib);
	$bankInfoUpdater->setValidator($updValidator["F0030"]);
	$bankInfoUpdater->setEnv($resArray['env']);
	$res = $bankInfoUpdater->checkMandatoryFields($resArray['F0030']['fields']);
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}

	//il record esiste già?
	$bankInfoRecordExists = $billingReferenceUpdater->recordExists();
	if(isset($bankInfoRecordExists["hasErrors"]) && $bankInfoRecordExists["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($bankInfoRecordExists["arrErrors"]).'}');
	}  	
	 
	if($bankInfoRecordExists) { 
		//UPDATE: 		  
		$res = $bankInfoUpdater->validateFields();
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
		 
		$res = $bankInfoUpdater->loadDefaultValues(); 
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
	} else {
		//INSERT:
		$bankInfoInserter = new bankInfoInserter();
		$bankInfoInserter->setConnection($conn);
		$bankInfoInserter->setHeaderFields($resArray['F0101']['fields']);
		$bankInfoInserter->setInputFields($inputFields["F0030"],$fieldType["F0030"],$fieldSize["F0030"]);
		$bankInfoInserter->setCostantFields($costantFields["F0030"]);
		$bankInfoInserter->setMandatoryFields($mandatoryFields["F0030"]);
		$bankInfoInserter->setDefaultValueFields($defaultValueFields["F0030"]);
		$bankInfoInserter->setFieldsReference($fieldsReference["F0030"]); 
		$bankInfoInserter->setEnvLib($envLib);
		$bankInfoInserter->setValidator($validator["F0030"]);

		$bankInfoInserter->setEnv($resArray['env']);
		$res = $bankInfoInserter->checkMandatoryFields($resArray['F0030']['fields']);
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
		  
		$res = $bankInfoInserter->validateFields();
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
		 
		$res = $bankInfoInserter->loadDefaultValues(); 
		if($res["hasErrors"]) { 
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		} 
	}		
}
 
 
/********** AGGIORNAMENTO: ************/

$res = $customerUpdater->execUpdate();
if($res["hasErrors"]) { 
	odbc_rollback($conn); 
	odbc_close($conn);
	die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
} 

//F0111 è di dettaglio: elimino tutto e poi reinserisco
if(isset($resArray['F0111'])) {

	$contactUpdater = new contactUpdater();
	$contactUpdater->setConnection($conn);
	$contactUpdater->setEnvLib($envLib);
	$contactUpdater->setEnv($resArray['env']);
	$contactUpdater->setHeaderFields($resArray['F0101']['fields']);
	$res = $contactUpdater->deleteAllRows();
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}

	for($i=0;$i<count($resArray['F0111']);$i++) { 
		 
		$currentResArray = $resArray['F0111'][$i]; 
	 
		$contactInserter = new contactInserter();
		$contactInserter->setConnection($conn);
		$contactInserter->setHeaderFields($resArray['F0101']['fields']);
		$contactInserter->setInputFields($inputFields["F0111"],$fieldType["F0111"],$fieldSize["F0111"]);
		$contactInserter->setCostantFields($costantFields["F0111"]);
		$contactInserter->setMandatoryFields($mandatoryFields["F0111"]);
		$contactInserter->setDefaultValueFields($defaultValueFields["F0111"]);
		$contactInserter->setFieldsReference($fieldsReference["F0111"]); 
		$contactInserter->setEnvLib($envLib);
		$contactInserter->setValidator($validator["F0111"]);

		$contactInserter->setEnv($resArray['env']); 
		$res = $contactInserter->checkMandatoryFields($currentResArray);
		$res = $contactInserter->validateFields(); 
		$res = $contactInserter->loadDefaultValues();  
		$res = $contactInserter->execInsert();
		if($res["hasErrors"]) { 
			odbc_rollback($conn); 
			odbc_close($conn);
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
	}
}

if(isset($resArray['F0116'])) {
	$res = $addressUpdater->execUpdate();
	if($res["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	} 
}

if(isset($resArray['F0115'])) {
	$res = $phoneUpdater->execUpdate();
	if($res["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	} 
}

if(isset($resArray['F0301'])) {
	$res = $accountabilityUpdater->execUpdate();
	if($res["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	} 
}

if(isset($resArray['F01017']) && isset($resArray['F01017']['custumer_billing_code']) && $resArray['F01017']['custumer_billing_code']!="") {
	if($billingRecordExists) { 
		$res = $billingReferenceUpdater->execUpdate();
		if($res["hasErrors"]) { 
			odbc_rollback($conn); 
			odbc_close($conn);
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		} 
	} else {
		$res = $billingReferenceInserter->execInsert();
		if($res["hasErrors"]) { 
			odbc_rollback($conn); 
			odbc_close($conn);
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		} 
	}
}
 
if(isset($resArray['F0030']) && isset($resArray['F0030']['customer_bank_account']) && $resArray['F0030']['customer_bank_account']!="") {
	if($bankInfoRecordExists) { 
		$res = $bankInfoUpdater->execUpdate();
		if($res["hasErrors"]) { 
			odbc_rollback($conn); 
			odbc_close($conn);
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		}
	} else {
		$res = $bankInfoInserter->execInsert();
		if($res["hasErrors"]) { 
			odbc_rollback($conn); 
			odbc_close($conn);
			die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
		} 
	}		
}

 

//ripulisco:
 /*
$query = "DELETE FROM JRGDTA94T.F0101 WHERE ABAN8 = 81009589 WITH NC";
$res = odbc_exec($conn,$query);
$query = "DELETE FROM JRGDTA94T.F0111 WHERE WWAN8 = 81009589 WITH NC";
$res = odbc_exec($conn,$query);
$query = "DELETE FROM JRGDTA94T.F0115 WHERE WPAN8 = 81009589 WITH NC";
$res = odbc_exec($conn,$query);
$query = "DELETE FROM JRGDTA94T.F0116 WHERE AlAN8 = 81009589 WITH NC";
$res = odbc_exec($conn,$query);
$query = "DELETE FROM JRGDTA94T.F0301 WHERE A5AN8 = 81009589 WITH NC";
$res = odbc_exec($conn,$query);
$query = "DELETE FROM JRGDTA94T.F01017 WHERE AGAN8 = 81009589 WITH NC";
$res = odbc_exec($conn,$query);
$query = "DELETE FROM JRGDTA94T.F0030 WHERE AYAN8 = 81009589 WITH NC";
$res = odbc_exec($conn,$query);
 */

odbc_commit($conn);   
odbc_close($conn);
echo '{"stat":"OK"}';



 