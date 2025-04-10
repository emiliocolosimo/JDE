<?php


class HeaderInserter
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
    private $defaultValuesArray = null;
    private $costantFields = null;
    private $fieldsReference = null;

    public function __construct()
    {

    }

    public function setConnection($conn)
    {

        $this->odbc_conn = $conn;
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
        $fieldSizes = $this->costantFields;


        foreach ($fieldsArray as $fieldName => $fieldValue) {
            if (isset($fieldsReference[$fieldName])) {
                $db2FieldName = $fieldsReference[$fieldName];
                $db2InputFields[$db2FieldName] = $fieldValue;
            }
        }
        $fieldsArray = $db2InputFields;


        foreach ($costantFields as $fieldName => $fieldValue) {
            if (!isset($fieldsArray[$fieldName])) $fieldsArray[$fieldName] = $fieldValue;
        }

        //mettere qui i campi "particolari":
        $Utils = new Utils();
        $fieldsArray["SYTRDJ"] = $Utils->dateToJUL($fieldsArray["SYTRDJ"]);
        $fieldsArray["SYURDT"] = $Utils->dateToJUL($fieldsArray["SYURDT"]);

        if ($fieldsArray["SYPDDJ"] != "") $fieldsArray["SYPDDJ"] = $Utils->dateToJUL($fieldsArray["SYPDDJ"]);

        if ($fieldsArray["SYDRQJ"] != "") {
            $fieldsArray["SYDRQJ"] = $Utils->dateToJUL($fieldsArray["SYDRQJ"]);
            if ($fieldsArray["SYPDDJ"] == "") $fieldsArray["SYPDDJ"] = $fieldsArray["SYDRQJ"];
        }

        $fieldsArray["SYCACT"] = $this->getCodiceBanca($fieldsArray["SYAN8"]);
        $fieldsArray["SYCRRM"] = "";
        if ($fieldsArray["SYCRCD"] != "EUR") $fieldsArray["SYCRRM"] = "F";
        if ($fieldsArray["SYCRRM"] == "F") {
            $fieldsArray["SYCRR"] = $this->getCambio($fieldsArray["SYCRCD"], $fieldsArray["SYTRDJ"]);
        }
        $fieldsArray["SYUSER"] = $this->getUtenteJDE($fieldsArray["SYUSER"]);
        //$fieldsArray["SYUSER"] = "CRM";
        $fieldsArray["SYTORG"] = $fieldsArray["SYUSER"];
        $fieldsArray["SYORBY"] = $fieldsArray["SYUSER"];
        //$fieldsArray["SYEKCO"] = $fieldsArray["SYKCOO"];
        //$fieldsArray["SYCO"] = $fieldsArray["SYKCOO"];
        $fieldsArray["SYDCTO"] = !empty($fieldsArray["SYDCTO"])
                ? $fieldsArray["SYDCTO"]
                : $this->getSYDCTO($fieldsArray["SYAN8"]);
//        $fieldsArray["SYDCTO"] = $this->getSYDCTO($fieldsArray["SYAN8"]);

        if ($fieldsArray["SYSHAN"] == '') $fieldsArray["SYSHAN"] = $fieldsArray["SYAN8"];

        /*
        $tmp = '';
        for($p=0;$p<12 - strlen($fieldsArray["SYMCU"]);$p++) $tmp .= ' ';
        $fieldsArray["SYMCU"] = $tmp.$fieldsArray["SYMCU"];
        */

        //..

        $xe = 0;
        $hasErrors = false;
        $arrErrors = array();
        if (isset($fieldsArray)) {
            for ($i = 0; $i < count($inputFields); $i++) {
                $currentField = $inputFields[$i];

                if (isset($fieldsArray[$currentField])) $$currentField = trim($fieldsArray[$currentField]);

                if (in_array($currentField, $mandatoryFields) && !isset($fieldsArray[$currentField])) {

                    //carico il default: ??
                    //$fieldValue = $this->getDefaultValue($currentField);
                    //$fieldsArray[$currentField] = $fieldValue;

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

    /*
    Il numero da assegnare si trova nel campo NLN001 del file F00021 letto per NLKCO="00001", NLDCT=’O1’, NLCTRY=<secolo della data ordine>, NLFY=<anno della data ordine>.
    Al numero reperito va sommato l'anno della data ordine (2 cifre) moltiplicato per 1.000.000. Ad esempio: se la data ordine è 15/09/24 e il valore reperito in NLN001 è 127, il numero ordine sarà 24000127
    */
    public function getOrderNumber($orderDate)
    {

        $Utils = new Utils();
        $orderDate = $Utils->JULToDate($orderDate);

        $query = "SELECT NLN001 FROM " . $this->curLib . ".F00021 WHERE NLKCO='00001' AND NLDCT='O1' AND NLCTRY=? AND NLFY=? FETCH FIRST ROW ONLY";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        if (!$pstmt) return false;
        $arrParams = array();
        $arrParams[] = substr($orderDate, 0, 2);
        $arrParams[] = substr($orderDate, 2, 2);
        $res = odbc_execute($pstmt, $arrParams);
        if (!$res) return false;
        $row = odbc_fetch_array($pstmt);
        if ($row && isset($row["NLN001"])) {

            $oc = (int)$row["NLN001"];

            $query = "UPDATE " . $this->curLib . ".F00021 SET NLN001 = ? WHERE NLKCO='00001' AND NLDCT='O1' AND NLCTRY=? AND NLFY=? WITH NC";
            $pstmt2 = odbc_prepare($this->odbc_conn, $query);
            if (!$pstmt2) return false;
            $arrParams = array();
            $arrParams[] = $oc + 1;
            $arrParams[] = substr($orderDate, 0, 2);
            $arrParams[] = substr($orderDate, 2, 2);

            //var_dump($arrParams);
            $res2 = odbc_execute($pstmt2, $arrParams);
            if (!$res2) return false;

            $oy = (int)substr($orderDate, 2, 2);
            $orderNumber = $oy * 1000000 + $oc;
            return $orderNumber;
        }
        return false;
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

    public function getCodiceBanca($SYAN8)
    {
        //codice banca (preso da F0301.A5CACT per A5AN8=SYAN8)
        $query = "SELECT A5CACT
		FROM " . $this->curLib . ".F0301 
		WHERE A5AN8 = ?";
        $pstmt = odbc_prepare($this->odbc_conn, $query);
        $arrParams = array();
        $arrParams[] = $SYAN8;
        $res = odbc_execute($pstmt, $arrParams);
        $row = odbc_fetch_array($pstmt);
        if ($row && isset($row["A5CACT"])) return $row["A5CACT"];
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

        //recupero numero ordine:

        if (empty($fieldsArray["SYDOCO"])) {
            $orderNumber = $this->getOrderNumber($fieldsArray["SYTRDJ"]);
        
            if (!$orderNumber) {
                $hasErrors = true;
                $arrErrors[$xe]["field"] = "";
                $arrErrors[$xe]["msg"] = "F47011:errore recupero numero ordine:" . odbc_errormsg($this->odbc_conn);
                $xe++;
                return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors);
            }
        
            $fieldsArray["SYDOCO"] = $orderNumber;
            $fieldsArray["SYEDOC"] = $orderNumber;
        
        } else {
            $fieldsArray["SYEDOC"] =  $fieldsArray["SYDOCO"];
            $orderNumber = $fieldsArray["SYDOCO"];
         //   $orderNumber = true;
        }

        $query = "INSERT INTO " . $this->curLib . ".F47011 (" . implode(",", $inputFields) . ") VALUES(";
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
            $arrErrors[$xe]["msg"] = "F0101:" . odbc_errormsg($this->odbc_conn);
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
            $arrErrors[$xe]["msg"] = "F47011:" . odbc_errormsg($this->odbc_conn);
            $xe++;
            return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors);
        }

        return array("hasErrors" => $hasErrors, "arrErrors" => $arrErrors, "orderNumber" => $orderNumber);


    }

}