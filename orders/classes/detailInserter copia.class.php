<?php


class DetailInserter
{
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
    private $orderNumber = null;
    private $lineNumber = null;

    public function __construct()
    {

    }

    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }
    public function setlineNumber($lineNumber)
    {
        $this->lineNumber = $lineNumber;
    }

    public function setConnection($conn)
    {

        $this->odbc_conn = $conn;
        return true;
    }

    public function setHeaderFields($headerFields)
    {

        $this->headerFields = $headerFields;
        return true;
    }

    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    public function setInputFields($inputFields, $fieldTypes, $fieldSizes)
    {
        $this->inputFields = $inputFields;
        $this->fieldTypes = $fieldTypes;
        $this->fieldSizes = $fieldSizes;
    }

    public function setCostantFields($costantFields)
    {
        $this->costantFields = $costantFields;
    }

    public function setMandatoryFields($mandatoryFields)
    {
        $this->mandatoryFields = $mandatoryFields;
    }

    public function setFieldsReference($fieldsReference)
    {
        $this->fieldsReference = $fieldsReference;
    }

    public function setEnvLib($envLib)
    {
        $this->envLib = $envLib;
    }

    public function setEnv($env)
    {
        $envLib = $this->envLib;

        if ($env == '') {
            die('{"stat":"err","msg":"parametro ambiente mancante"}');
        }
        if (!isset($envLib[$env])) {
            die('{"stat":"err","msg":"parametro ambiente errato"}');
        }
        $this->curLib = $envLib[$env];
    }

    public function isValidField($DRSY, $DRRT, $fieldValue, $fieldName)
    {

        $fieldSizes = $this->fieldSizes;

        //i campi sono allineati a destra:
        $DRKY = '';
        for ($p = 0; $p < (10 - $fieldSizes[$fieldName]); $p++) $DRKY .= ' ';
        $DRKY .= $fieldValue;

        $query = "SELECT 1 AS VALIDFIELD  
		FROM JRGCOM94T.F0005 
		WHERE DRSY=? AND DRRT=? AND DRKY=? 
		FETCH FIRST ROW ONLY
		";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = trim($DRSY);
        $arrParams[] = trim($DRRT);
        $arrParams[] = $DRKY;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row && $row["VALIDFIELD"] == 1) return true;

        return false;

    }

    public function checkMandatoryFields($fieldsArray)
    {
        $inputFields = $this->inputFields;
        $mandatoryFields = $this->mandatoryFields;
        $fieldsReference = $this->fieldsReference;
        $costantFields = $this->costantFields;
        $headerFields = $this->headerFields;

        foreach ($fieldsArray as $fieldName => $fieldValue) {
            $db2FieldName = $fieldsReference[$fieldName];
            $db2InputFields[$db2FieldName] = $fieldValue;
        }
        $fieldsArray = $db2InputFields;

        foreach ($costantFields as $fieldName => $fieldValue) {
            if (!isset($fieldsArray[$fieldName])) $fieldsArray[$fieldName] = $fieldValue;
        }

        $Utils = new Utils();
        //mettere qui i campi "particolari":
        $fieldsArray["SZDOCO"] = $this->orderNumber;
        $fieldsArray["SZEDOC"] = $this->orderNumber;
        $fieldsArray["SZCRCD"] = $headerFields["quotation_recipient_currency"];
        $fieldsArray["SZAN8"] = $headerFields["quotation_recipient_code"];
        $fieldsArray["SZSHAN"] = $headerFields["quotation_recipient_shipping_code"];
        $fieldsArray["SZRORN"] = $headerFields["quotation_number"];
        $fieldsArray["SZPTC"] = $headerFields["quotation_payment_terms"];
        $fieldsArray["SZRYIN"] = $headerFields["quotation_payment_method"];
        $fieldsArray["SZROUT"] = $headerFields["quotation_request_route"];
        $fieldsArray["SZCARS"] = $headerFields["quotation_carrier"];
        $fieldsArray["SZFRTH"] = $headerFields["quotation_return"];
        $fieldsArray["SZPRIO"] = $headerFields["quotation_priority"];


        if ($fieldsArray["SZPDDJ"] != "") $fieldsArray["SZPDDJ"] = $Utils->dateToJUL($fieldsArray["SZPDDJ"]);

        if ($fieldsArray["SZDRQJ"] != "") {
            $fieldsArray["SZDRQJ"] = $Utils->dateToJUL($fieldsArray["SZDRQJ"]);
            if ($fieldsArray["SZPDDJ"] == "") $fieldsArray["SZPDDJ"] = $fieldsArray["SZDRQJ"];
        }

        $fieldsArray["SZEDLN"] = $this->getLineNumber($fieldsArray["SZDOCO"]);      
        $fieldsArray["SZLNID"] = $this->getLineNumber($fieldsArray["SZDOCO"]);  
    
        // $fieldsArray["SZEDLN"] = $fieldsArray["SZEDLN"] * 100;
        // $fieldsArray["SZLNID"] = $fieldsArray["SZEDLN"];
    
        $fieldsArray["SZITM"] = $this->getShortItemNumber($fieldsArray["SZLITM"]);
        if ($fieldsArray["SZSHAN"] == '') $fieldsArray["SZSHAN"] = $fieldsArray["SZAN8"];

        /*
        $ubi =  $this->getItemLocation($fieldsArray["SZITM"]);
        if(is_array($ubi)) {
            $fieldsArray["SZLOCN"] = $ubi["LILOCN"];
            $fieldsArray["SZLOTN"] = $ubi["LILOTN"];
        }
        */

        $fieldsArray["SZVR01"] = $headerFields["quotation_referent_customer"];
        $fieldsArray["SZURDT"] = $headerFields["quotation_referent_customer_date"];

        $fieldsArray["SZURDT"] = $Utils->dateToJUL($fieldsArray["SZURDT"]);

        $fieldsArray["SZUORG"] = $fieldsArray["SZUORG"] * 100;
        $fieldsArray["SZSOQS"] = $fieldsArray["SZUORG"];
        if ($fieldsArray["SZCRCD"] != "EUR") {
            $fieldsArray["SZCRR"] = $this->getCambio($fieldsArray["SZCRCD"], $Utils->dateToJUL($headerFields["quotation_date"]));
            //SZUPRC Se valuta ordine = ‘EUR’ scrivere l’importo passato da GRF; Se invece la valuta è estera prendere l’importo passato da GRF e dividerlo per il tasso di cambio:
            $fieldsArray["SZUPRC"] = round($fieldsArray["SZFUP"] * $fieldsArray["SZCRR"], 2) * 100;
            $fieldsArray["SZAEXP"] = round($fieldsArray["SZFEA"] * $fieldsArray["SZCRR"], 2) * 100;
            $fieldsArray["SZFUP"] = $fieldsArray["SZFUP"] * 100;
            $fieldsArray["SZFEA"] = $fieldsArray["SZFEA"] * 100;
        } else {
            $fieldsArray["SZUPRC"] = $fieldsArray["SZFUP"] * 100;
            $fieldsArray["SZAEXP"] = $fieldsArray["SZFEA"] * 100;
            $fieldsArray["SZFUP"] = 0;
            $fieldsArray["SZFEA"] = 0;
        }


        $fieldsArray["SZUSER"] = $this->getUtenteJDE($headerFields["crm_user"]);
        //$fieldsArray["SZUSER"] = "CRM";

        //$fieldsArray["SZDCTO"] = $this->getSYDCTO($headerFields["quotation_recipient_code"]);

        $fieldsArray["SZDCTO"] = !empty($headerFields["quotation_type"])
            ? $headerFields["quotation_type"]
            :  $this->getSYDCTO($headerFields["quotation_recipient_code"]);

        /*
        $tmp = '';
        for($p=0;$p<12 - strlen($fieldsArray["SZMCU"]);$p++) $tmp .= ' ';
        $fieldsArray["SZMCU"] = $tmp.$fieldsArray["SZMCU"];
        */

        $hasErrors = false;
        $arrErrors = array();
        $xe = 0;
        if (isset($fieldsArray)) {
            for ($i = 0; $i < count($inputFields); $i++) {
                $currentField = $inputFields[$i];

                if (isset($fieldsArray[$currentField])) $$currentField = trim($fieldsArray[$currentField]);

                if (in_array($currentField, $mandatoryFields) && !isset($fieldsArray[$currentField])) {
                    $hasErrors = true;
                    $arrErrors[$xe]["field"] = $currentField;
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


        return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors);

    }

    public function getLineNumber($SZDOCO)
    {

        $query = "SELECT MAX(CAST(SZLNID AS INT)) + 100 as SZLNID FROM " . $this->curLib . ".F47012  WHERE SZDOCO = ? FETCH FIRST ROW ONLY";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $SZDOCO;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row && isset($row["SZLNID"])) {
            return $row["SZLNID"];

        }

        return 100;

    }

    public function getCountryCode($customerCode)
    {

        $query = "SELECT trim(ALCTR) as ALCTR FROM " . $this->curLib . ".F0116  WHERE ALAN8 = ? FETCH FIRST ROW ONLY";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $customerCode;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row && isset($row["ALCTR"])) {
            return $row["ALCTR"];
        }

        return false;

    }

    public function getSYDCTO($customerCode)
    {

        $countryCode = $this->getCountryCode($customerCode);
        if (!$countryCode) return "";

        if ($countryCode == "IT") return "O1";

        $query = "SELECT 1 AS ISCEE FROM BCD_DATIV2.NAZCEE0F WHERE NCCDNA = ? FETCH FIRST ROW ONLY";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $countryCode;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row && $row["ISCEE"] == 1) {
            return "O2";
        } else {
            return "O3";
        }

    }

    public function getUtenteJDE($SYTORG)
    {
        //utente JDE (reperito da DRKY di UDC 55/US per DRDL02=<utente CRM>
        $query = "SELECT DRKY 
		FROM JRGCOM94T.F0005  
		WHERE DRSY='55' AND DRRT='US' AND DRDL02 = ? 
		 ";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $SYTORG;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row && isset($row["DRKY"])) return $row["DRKY"];
        return '';
    }

    public function getCambio($SYCRCD, $SYTRDJ)
    {
        /*
        Il tasso di cambio si trova nel campo CXCRRD del file F0015 letto per:
        - CXCRCD = <valuta estera>
        - CXCRDC = "EUR"
        Essendo i tassi di cambio definiti per data, si deve usare il record più
        recente avente data CXEFT <= SZTRDJ (data ordine)
        */
        $query = "SELECT CXCRRD 
		FROM " . $this->curLib . ".F0015 
		WHERE CXCRCD = ? AND CXCRDC = 'EUR' 
		AND CXEFT <= ? 
		ORDER BY CXEFT DESC 
		FETCH FIRST ROW ONLY
		";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $SYCRCD;
        $arrParams[] = $SYTRDJ;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row && isset($row["CXCRRD"])) return $row["CXCRRD"];
        return 0;
    }

    public function getShortItemNumber($SZLITM)
    {
        //F4101.IMITM dove F4101.IMLITM=(codice articolo)
        $query = "SELECT IMITM  
		FROM " . $this->curLib . ".F4101   
		WHERE IMLITM = ? 
		 ";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $SZLITM;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row && isset($row["IMITM"])) return $row["IMITM"];
        return '';
    }

    public function getItemLocation($ZSITM)
    {
        //F4101.IMITM dove F4101.IMLITM=(codice articolo)
        $query = "SELECT LIMCU, LILOCN, LILOTN    
		FROM " . $this->curLib . ".F41021   
		WHERE LIITM = ? 
		 ";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $ZSITM;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);

        if ($row && is_array($row)) return $row;


        return '';
    }

    public function validateFields()
    {
        $validator = $this->validator;
        $inputFields = $this->inputFields;
        $fieldsArray = $this->fieldsArray;

        $xe = 0;
        $hasErrors = false;
        $arrErrors = array();
        foreach ($validator as $fieldName => $validatorCode) {

            $fieldValue = "";
            if (isset($fieldsArray[$fieldName])) $fieldValue = $fieldsArray[$fieldName];
            $va = explode("/", $validatorCode);
            $DRSY = $va[0];
            $DRRT = $va[1];
            if (!$this->isValidField($DRSY, $DRRT, $fieldValue, $fieldName)) {
                $hasErrors = true;
                $arrErrors[$xe]["field"] = $fieldName;
                $arrErrors[$xe]["msg"] = "Valore non valido";
                $xe++;
            }
        }

        return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors);
    }

    public function setDefaultValueFields($defaultValueFields)
    {
        $this->defaultValueFields = $defaultValueFields;
    }

    public function loadDefaultValues()
    {
        $inputFields = $this->inputFields;
        $fieldsArray = $this->fieldsArray;
        $defaultValueFields = $this->defaultValueFields;

        $xe = 0;
        $hasErrors = false;
        $arrErrors = array();
        for ($i = 0; $i < count($defaultValueFields); $i++) {
            $fieldName = $defaultValueFields[$i];
            $fieldValue = $this->getDefaultValue($fieldName);

            if ($fieldValue === false) {
                $hasErrors = true;
                $arrErrors[$xe]["field"] = $fieldName;
                $arrErrors[$xe]["msg"] = "Valore di default non trovato";
                $xe++;
            }

            $defaultValuesArray[$fieldName] = $fieldValue;
        }

        $this->defaultValuesArray = $defaultValuesArray;

        return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors);
    }


    private function getDefaultValue($fieldName)
    {

        $fieldTypes = $this->fieldTypes;

        $FRDTAI = substr($fieldName, 2);
        $query = "SELECT FRDVAL    
		FROM JRGCOM94T.F9210 
		WHERE FRDTAI = ?
		FETCH FIRST ROW ONLY
		";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $FRDTAI;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row) {
            $defVal = $row["FRDVAL"];
            if (trim($defVal) == '' && $fieldTypes[$fieldName] == 'S') $defVal = 0;

            return rtrim($defVal);
        }

        return false;
    }

    public function execInsert()
    {
        $inputFields = $this->inputFields;
        $fieldsArray = $this->fieldsArray;
        $defaultValuesArray = $this->defaultValuesArray;
        $fieldTypes = $this->fieldTypes;
        $fieldSizes = $this->fieldSizes;

        $errMsg = "";
        $arrErrors = array();
        $hasErrors = false;
        $xe = 0;

        $query = "INSERT INTO " . $this->curLib . ".F47012 (" . implode(",", $inputFields) . ") VALUES(";
        for ($i = 0; $i < count($inputFields); $i++) {
            if ($i > 0) $query .= ",";
            if ($fieldTypes[$inputFields[$i]] == "A") $query .= "Cast(Cast(? As Char(" . $fieldSizes[$inputFields[$i]] . ") CCSID 65535) As Char(" . $fieldSizes[$inputFields[$i]] . ") CCSID 37) ";
            else $query .= "?";
        }
        $query .= ") WITH NC";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        if (!$pstmt) {
            $hasErrors = true;
            $arrErrors[$xe]["field"] = "";
            $arrErrors[$xe]["msg"] = "F47012:" . odbc_errormsg($this->odbc_conn);
            $xe++;
            return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors);
        }
        $arrParams = array();
        for ($i = 0; $i < count($inputFields); $i++) {
            $curFieldName = $inputFields[$i];

            if (isset($fieldsArray[$curFieldName])) $curFieldValue = $fieldsArray[$curFieldName];
            else {
                //per questi inserimenti non va preso il default da tabella,
                //carico il default in base al tipo di campo
                if ($fieldTypes[$curFieldName] == "A") {
                    $curFieldValue = "";
                } else {
                    $curFieldValue = 0;
                }
            }
            //$curFieldValue = $defaultValuesArray[$curFieldName];

            $arrParams[] = mb_convert_encoding($curFieldValue, "ISO-8859-1", "UTF-8");
        }

        $res = odbc_execute($pstmt, $arrParams);
        if (!$res) {
            $hasErrors = true;
            $arrErrors[$xe]["field"] = "";
            $arrErrors[$xe]["msg"] = "F47012:" . odbc_errormsg($this->odbc_conn);
            $xe++;
            return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors);
        }

        return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors);


    }

}