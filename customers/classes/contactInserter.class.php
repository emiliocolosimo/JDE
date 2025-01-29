<?php


class ContactInserter {
	private $odbc_conn = null;
	private $curLib = "";
	private $inputFields = null;
	private $fieldTypes = null;
	private $fieldSizes = null;
	private $mandatoryFields = null;
	private $envLib = null;
	private $fieldsArray = null;
	private $validator = null;
	private $defaultValueFields = null;
	private $defaultValuesArray = null;
	private $costantFields = null;
	private $fieldsReference = null;
	private $headerFields = null;
 
    public function __construct() {
		
    }	
	 
	public function setConnection($conn) {

		$this->odbc_conn = $conn;
		return true;
	}
	
	public function setHeaderFields($headerFields) {

		$this->headerFields = $headerFields;
		return true;
	}	
	
	public function setValidator($validator) {
		$this->validator = $validator;
	}
	
	public function setInputFields($inputFields,$fieldTypes,$fieldSizes) {
		$this->inputFields = $inputFields;
		$this->fieldTypes = $fieldTypes;
		$this->fieldSizes = $fieldSizes;
	}

	public function setCostantFields($costantFields) {
		$this->costantFields = $costantFields;
	}

	public function setMandatoryFields($mandatoryFields) {
		$this->mandatoryFields = $mandatoryFields;
	}
	
	public function setFieldsReference($fieldsReference) {
		$this->fieldsReference = $fieldsReference;
	}	
	 
	public function setEnvLib($envLib) {
		$this->envLib = $envLib;
	}
	
	public function setEnv($env) { 
		$envLib = $this->envLib;
	  
		if($env=='') {
			die('{"stat":"err","msg":"parametro ambiente mancante"}');
		}
		if(!isset($envLib[$env])) {
			die('{"stat":"err","msg":"parametro ambiente errato"}');
		} 	
		$this->curLib =$envLib[$env];	
	}

	public function isValidField($DRSY,$DRRT,$fieldValue,$fieldName) {
		
		$fieldSizes = $this->fieldSizes; 
		
		//i campi sono allineati a destra:
		$DRKY = '';
		for($p=0;$p<(10 - $fieldSizes[$fieldName]);$p++) $DRKY .= ' ';
		$DRKY .= $fieldValue;
		 
 		$query = "SELECT 1 AS VALIDFIELD  
		FROM JRGCOM94T.F0005 
		WHERE DRSY=? AND DRRT=? AND DRKY=? 
		FETCH FIRST ROW ONLY
		";
		$pstmt = odbc_prepare($this->odbc_conn,$query);
		$arrParams = array();	
		$arrParams[] = trim($DRSY);
		$arrParams[] = trim($DRRT);
		$arrParams[] = $DRKY;
		$res = odbc_execute($pstmt,$arrParams);
		$row = odbc_fetch_array($pstmt);
		if($row && $row["VALIDFIELD"]==1) return true;
		
		return false;
		
	}
	
	public function getSpyFieldName($db2NameToConvert) {
		$fieldsReference = $this->fieldsReference; 
		foreach($fieldsReference as $spyName => $db2Name) {
			if($db2NameToConvert == $db2Name) return $spyName; 
		} 
		return ""; 
	}		
	
	public function checkMandatoryFields($fieldsArray) {
		$inputFields = $this->inputFields;
		$mandatoryFields = $this->mandatoryFields;
		$fieldsReference = $this->fieldsReference;
		$costantFields = $this->costantFields;
		$headerFields = $this->headerFields;
		 
		foreach($fieldsArray as $fieldName => $fieldValue) {
			$db2FieldName = $fieldsReference[$fieldName];
			$db2InputFields[$db2FieldName] = $fieldValue;
		}
		$fieldsArray = $db2InputFields;
		
		foreach($costantFields as $fieldName => $fieldValue) {
			if(!isset($fieldsArray[$fieldName])) $fieldsArray[$fieldName] = $fieldValue;
		}
		
		//mettere qui i campi "particolari":
		$fieldsArray["WWAN8"] = $headerFields["customer_code"];
		if($headerFields["customer_type"]=="C") $fieldsArray["WWAN8"] = $fieldsArray["WWAN8"] + 1000000;
		$fieldsArray["WWALPH"] = $headerFields["customer_name"];
		$fieldsArray["WWDC"] = substr(str_replace(array(" "),array(""),$headerFields["customer_name"]),0,40);
		//La mail Scritta nel campo WWMLNM del file F0111 se supera i 40 caratteri la parte restante scriverla in WWATTL:
		if(($fieldsArray["WWTYC"]=="EM" || $fieldsArray["WWTYC"]=="EF" || $fieldsArray["WWTYC"]=="PE") && strlen($fieldsArray["WWMLNM"])>40) {
			$fieldsArray["WWATTL"] = substr($fieldsArray["WWMLNM"],40);
			$fieldsArray["WWMLNM"] = substr($fieldsArray["WWMLNM"],0,40);
		}
		$fieldsArray["WWMLNM"] = substr($fieldsArray["WWMLNM"],0,40);
		
		$fieldsArray["WWUSER"] = $this->getUtenteJDE($headerFields["crm_user"]);
		$fieldsArray["WWPID"] = $fieldsArray["WWUSER"];
		$fieldsArray["WWTORG"] = $fieldsArray["WWUSER"];
		//..
		  
		  
		$hasErrors = false;
		$arrErrors = array();
		$xe = 0;
		if(isset($fieldsArray)) { 
			for($i=0;$i<count($inputFields);$i++) {
				$currentField = $inputFields[$i];
				
				if(isset($fieldsArray[$currentField])) $$currentField = trim($fieldsArray[$currentField]);
				 
				if(in_array($currentField,$mandatoryFields) && !isset($fieldsArray[$currentField])) {
					$hasErrors = true;
					$arrErrors[$xe]["field"] = $this->getSpyFieldName($currentField);//$currentField;
					$arrErrors[$xe]["msg"] = "Campo obbligatorio";
					$xe++;
				}
			} 
		} else {
			$hasErrors = true;
			$arrErrors[$xe]["field"] = "";
			$arrErrors[$xe]["msg"] = "Invalid json"; 
			$xe++;
		}
		
		$this->fieldsArray = $fieldsArray;
		
	
		
		return array("hasErrors"=>$hasErrors,"arrErrors"=>$arrErrors);
		
	}

	public function getUtenteJDE($crmUser) {
		//utente JDE (reperito da DRKY di UDC 55/US per DRDL02=<utente CRM>
		$query = "SELECT DRKY 
		FROM JRGCOM94T.F0005  
		WHERE DRSY='55' AND DRRT='US' AND DRDL02 = ? 
		 ";
		$pstmt = odbc_prepare($this->odbc_conn,$query);
		$arrParams = array();	
		$arrParams[] = $crmUser;
		$res = odbc_execute($pstmt,$arrParams);
		$row = odbc_fetch_array($pstmt);
		if($row && isset($row["DRKY"])) return $row["DRKY"]; 		
		return '';
	}

	public function validateFields() {
		$validator = $this->validator;
		$inputFields = $this->inputFields;
		$fieldsArray = $this->fieldsArray;
		
		$xe = 0;
		$hasErrors = false; 
		$arrErrors = array();
		foreach($validator as $fieldName => $validatorCode) {
			
			$fieldValue = "";
			if(isset($fieldsArray[$fieldName])) $fieldValue = $fieldsArray[$fieldName]; 
			$va = explode("/",$validatorCode);
			$DRSY = $va[0];
			$DRRT = $va[1];
			if(!$this->isValidField($DRSY,$DRRT,$fieldValue,$fieldName)) {
				$hasErrors = true;
				$arrErrors[$xe]["field"] = $this->getSpyFieldName($fieldName);//$fieldName;
				$arrErrors[$xe]["msg"] = "Valore non valido";
				$xe++;
			}
		}
		 
		return array("hasErrors"=>$hasErrors,"arrErrors"=>$arrErrors);
	}
	
	public function setDefaultValueFields($defaultValueFields) {
		$this->defaultValueFields = $defaultValueFields;
	}
	
	public function loadDefaultValues()  {
		$inputFields = $this->inputFields;
		$fieldsArray = $this->fieldsArray;
		$defaultValueFields = $this->defaultValueFields;
		
		$xe = 0;
		$hasErrors = false; 
		$arrErrors = array();		
		for($i=0;$i<count($defaultValueFields);$i++) {
			$fieldName = $defaultValueFields[$i]; 
			$fieldValue = $this->getDefaultValue($fieldName);
			 
			if($fieldValue === false) {
				$hasErrors = true;
				$arrErrors[$xe]["field"] = $fieldName;
				$arrErrors[$xe]["msg"] = "Valore di default non trovato";
				$xe++;
			}
			
			$defaultValuesArray[$fieldName] = $fieldValue;
		}
		 
		$this->defaultValuesArray = $defaultValuesArray;
 
		return array("hasErrors"=>$hasErrors,"arrErrors"=>$arrErrors);
	}
	
	
	private function getDefaultValue($fieldName) {
		
		$fieldTypes = $this->fieldTypes;		
		 
		$FRDTAI = substr($fieldName,2);
 		$query = "SELECT FRDVAL    
		FROM JRGCOM94T.F9210 
		WHERE FRDTAI = ?
		FETCH FIRST ROW ONLY
		";
		$pstmt = odbc_prepare($this->odbc_conn,$query);
		$arrParams = array();	
		$arrParams[] = $FRDTAI;
		$res = odbc_execute($pstmt,$arrParams);
		$row = odbc_fetch_array($pstmt);
		if($row) {
			$defVal = $row["FRDVAL"];
			if(trim($defVal)=='' && $fieldTypes[$fieldName]=='S') $defVal = 0;
			
			return rtrim($defVal);
		}
		
		return false;
	}
	
	public function execInsert() {
		$inputFields = $this->inputFields;
		$fieldsArray = $this->fieldsArray;
		$defaultValuesArray = $this->defaultValuesArray;
		$fieldTypes = $this->fieldTypes;
		$fieldSizes = $this->fieldSizes;
		
		$errMsg = "";
		$arrErrors = array();
		$hasErrors = false;
		$xe = 0;
		
		$query = "INSERT INTO ".$this->curLib.".F0111 (".implode(",",$inputFields).") VALUES("; 
		for($i=0;$i<count($inputFields);$i++) { 
			if($i>0) $query.= ",";
			if($fieldTypes[$inputFields[$i]]=="A") $query.= "Cast(Cast(? As Char(".$fieldSizes[$inputFields[$i]].") CCSID 65535) As Char(".$fieldSizes[$inputFields[$i]].") CCSID 37) ";
			else $query.= "?"; 
		} 
		$query.= ") WITH NC"; 
		$pstmt = odbc_prepare($this->odbc_conn,$query);
		if(!$pstmt) {
			$hasErrors = true;
			$arrErrors[$xe]["field"] = "";
			$arrErrors[$xe]["msg"] = "F0111:".odbc_errormsg($this->odbc_conn);
			$xe++;			
			return array("hasErrors"=>$hasErrors,"arrErrors"=>$arrErrors);
		}
		$arrParams = array();	
		for($i=0;$i<count($inputFields);$i++) {
			$curFieldName = $inputFields[$i];
			
			if(isset($fieldsArray[$curFieldName])) $curFieldValue = $fieldsArray[$curFieldName];
			else $curFieldValue = $defaultValuesArray[$curFieldName];
				
			$arrParams[] = mb_convert_encoding($curFieldValue, "ISO-8859-1", "UTF-8"); 
		}
	
		$res = odbc_execute($pstmt,$arrParams);
		if(!$res) {
			$hasErrors = true;
			$arrErrors[$xe]["field"] = "";
			$arrErrors[$xe]["msg"] = "F0111:".odbc_errormsg($this->odbc_conn);
			$xe++;			
			return array("hasErrors"=>$hasErrors,"arrErrors"=>$arrErrors);
		}		
		
		return array("hasErrors"=>$hasErrors,"arrErrors"=>$arrErrors);
		    
		
	}
	
}