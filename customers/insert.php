<?php

date_default_timezone_set("Europe/Rome");

header('Content-Type: application/json'); 
error_reporting(E_ALL); 
ini_set("display_errors",0);

require("envs.customer.php");
require("fields.customer.php");
require("classes/customerInserter.class.php");
require("classes/contactInserter.class.php");
require("classes/addressInserter.class.php");
require("classes/phoneInserter.class.php");
require("classes/accountabilityInserter.class.php");
require("classes/billingReferenceInserter.class.php"); 
require("classes/bankInfoInserter.class.php");  
include("config.inc.php"); 

 
/*
DELETE FROM F0101 WHERE ABAN8 = 81009586
DELETE FROM F0111 WHERE WWAN8 = 81009586
DELETE FROM F0115 WHERE WPAN8 = 81009586
DELETE FROM F0116 WHERE AlAN8 = 81009586
DELETE FROM F0301 WHERE A5AN8 = 81009586
DELETE FROM F01017 WHERE AGAN8 = 81009586
DELETE FROM F0030 WHERE AYAN8 = 81009586
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
if(!isset($resArray['F0111'])) {
	die('{"stat":"err", "errors": [{"field":"", "msg":"missing F0111"}]}');	
}

/********** CONTROLLI: ************/

$customerInserter = new customerInserter();
$customerInserter->setConnection($conn);
$customerInserter->setInputFields($inputFields["F0101"],$fieldType["F0101"],$fieldSize["F0101"]);
$customerInserter->setCostantFields($costantFields["F0101"]);
$customerInserter->setMandatoryFields($mandatoryFields["F0101"]);
$customerInserter->setDefaultValueFields($defaultValueFields["F0101"]);
$customerInserter->setFieldsReference($fieldsReference["F0101"]); 
$customerInserter->setEnvLib($envLib);
$customerInserter->setValidator($validator["F0101"]);

$customerInserter->setEnv($resArray['env']);
$res = $customerInserter->checkMandatoryFields($resArray['F0101']['fields']);
if($res["hasErrors"]) { 
	die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
}
 
$res = $customerInserter->validateFields();
if($res["hasErrors"]) { 
	die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
}

$res = $customerInserter->loadDefaultValues(); 
if($res["hasErrors"]) { 
	die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
}

//inserisco altri file:
//F0111 
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
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
	$res = $contactInserter->validateFields();
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}

	$res = $contactInserter->loadDefaultValues(); 
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	  
}
  
//indirizzi:
if(isset($resArray['F0116'])) {
	$addressInserter = new addressInserter();
	$addressInserter->setConnection($conn);
	$addressInserter->setHeaderFields($resArray['F0101']['fields']);
	$addressInserter->setF0111Fields($resArray['F0111']);
	$addressInserter->setInputFields($inputFields["F0116"],$fieldType["F0116"],$fieldSize["F0116"]);
	$addressInserter->setCostantFields($costantFields["F0116"]);
	$addressInserter->setMandatoryFields($mandatoryFields["F0116"]);
	$addressInserter->setDefaultValueFields($defaultValueFields["F0116"]);
	$addressInserter->setFieldsReference($fieldsReference["F0116"]); 
	$addressInserter->setEnvLib($envLib);
	$addressInserter->setValidator($validator["F0116"]);

	$addressInserter->setEnv($resArray['env']);
	$res = $addressInserter->checkMandatoryFields($resArray['F0116']['fields']);
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
	$res = $addressInserter->validateFields();
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
 
	$res = $addressInserter->loadDefaultValues(); 
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
 
}

//numero di telefono:
if(isset($resArray['F0115'])) {
	$phoneInserter = new phoneInserter();
	$phoneInserter->setConnection($conn);
	$phoneInserter->setHeaderFields($resArray['F0101']['fields']);
	$phoneInserter->setInputFields($inputFields["F0115"],$fieldType["F0115"],$fieldSize["F0115"]);
	$phoneInserter->setCostantFields($costantFields["F0115"]);
	$phoneInserter->setMandatoryFields($mandatoryFields["F0115"]);
	$phoneInserter->setDefaultValueFields($defaultValueFields["F0115"]);
	$phoneInserter->setFieldsReference($fieldsReference["F0115"]); 
	$phoneInserter->setEnvLib($envLib);
	$phoneInserter->setValidator($validator["F0115"]);

	$phoneInserter->setEnv($resArray['env']);
	$res = $phoneInserter->checkMandatoryFields($resArray['F0115']['fields']);
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	/* 
	$res = $phoneInserter->validateFields();
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	*/
	$res = $phoneInserter->loadDefaultValues(); 
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
 
}

//dati bancari:
if(isset($resArray['F0301'])) {
	$accountabilityInserter = new accountabilityInserter();
	$accountabilityInserter->setConnection($conn);
	$accountabilityInserter->setHeaderFields($resArray['F0101']['fields']);
	$accountabilityInserter->setF0116Fields($resArray['F0116']['fields']); 
	$accountabilityInserter->setInputFields($inputFields["F0301"],$fieldType["F0301"],$fieldSize["F0301"]);
	$accountabilityInserter->setCostantFields($costantFields["F0301"]);
	$accountabilityInserter->setMandatoryFields($mandatoryFields["F0301"]);
	$accountabilityInserter->setDefaultValueFields($defaultValueFields["F0301"]);
	$accountabilityInserter->setFieldsReference($fieldsReference["F0301"]); 
	$accountabilityInserter->setEnvLib($envLib);
	$accountabilityInserter->setValidator($validator["F0301"]);

	$accountabilityInserter->setEnv($resArray['env']);
	$res = $accountabilityInserter->checkMandatoryFields($resArray['F0301']['fields']);
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	  
	$res = $accountabilityInserter->validateFields();
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
	$res = $accountabilityInserter->loadDefaultValues(); 
	if($res["hasErrors"]) { 
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	}
	 
 
}

//cliente di fatturazione:
if(isset($resArray['F01017']) && isset($resArray['F01017']['fields']['custumer_billing_code']) && $resArray['F01017']['fields']['custumer_billing_code']!="") {
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

//info bancarie:
if(isset($resArray['F0030']) && isset($resArray['F0030']['fields']['customer_bank_account']) && $resArray['F0030']['fields']['customer_bank_account']!="") {
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

 
/********** INSERIMENTO: ************/
$res = $customerInserter->execInsert();
if($res["hasErrors"]) { 
	odbc_rollback($conn); 
	odbc_close($conn);
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

if(isset($resArray['F0116'])) {
	$res = $addressInserter->execInsert();
	if($res["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	} 
}

if(isset($resArray['F0115'])) {
	$res = $phoneInserter->execInsert();
	if($res["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	} 
}

if(isset($resArray['F0301'])) {
	$res = $accountabilityInserter->execInsert();
	if($res["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	} 
}

if(isset($resArray['F01017']) && isset($resArray['F01017']['fields']['custumer_billing_code']) && $resArray['F01017']['fields']['custumer_billing_code']!="") {
	$res = $billingReferenceInserter->execInsert();
	if($res["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
	} 
}
if(isset($resArray['F0030']) && isset($resArray['F0030']['fields']['customer_bank_account']) && $resArray['F0030']['fields']['customer_bank_account']!="") {
	$res = $bankInfoInserter->execInsert();
	if($res["hasErrors"]) { 
		odbc_rollback($conn); 
		odbc_close($conn);
		die('{"stat":"err", "errors":'.json_encode($res["arrErrors"]).'}');
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



 