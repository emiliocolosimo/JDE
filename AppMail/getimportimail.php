			<?php
	include("/www/php80/htdocs/config.inc.php");

	header('Content-Type: application/json; charset=utf-8');

	set_time_limit(120);

	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
	error_reporting(E_ALL);
	ini_set("log_errors", 1);
	ini_set("error_log", "/www/php80/htdocs/logs/getF0101/php-error.log");

	$k = '';
	if(isset($_REQUEST['k'])) $k = $_REQUEST["k"];
	if($k!="sJHsdwvIFTyhDuGtZoOfevsgG1A1H2s6") { 
		exit;
	}

	$env = '';
	if(isset($_REQUEST["env"])) $env = $_REQUEST["env"];
	if($env=='') {
		$env='prod'; //per retrocompatibilità
	}
	$curLib = $envLib[$env];  

	$postedBody = file_get_contents('php://input');

	$server="Driver={IBM i Access ODBC Driver};System=127.0.0.1;Uid=".DB2_USER.";Pwd=".DB2_PASS.";TRANSLATE=1;CONNTYPE=2;CMT=0;BLOCKFETCH=1;BLOCKSIZE=2000"; 
	$user=DB2_USER; 
	$pass=DB2_PASS; 

	//connessione:
	$time_start = microtime(true); 

	$conn=odbc_connect($server,$user,$pass); 
	if(!$conn) {
		echo odbc_errormsg($conn);
		exit;
	}

	$time_end = microtime(true);
	$execution_time = ($time_end - $time_start);
	//echo '<b>Connessione:</b> '.$execution_time.' s';
	
	$whrClause = ""; 
	$ordbyClause = "";
	$limitClause = "";
	$rowCount = 0;
	$resArray = json_decode($postedBody, true);
	if($resArray) {
		if(isset($resArray['filters']) && count($resArray['filters'])>0) {
			
			$filterMode = $resArray["filter_mode"];
			
			if($whrClause=="") $whrClause = " WHERE ";
			
			$whrClause .= " (";
			
			$arrFilters = $resArray['filters'];
			for($i=0;$i<count($arrFilters);$i++) { 
				if($i>0) $whrClause.= " ".$filterMode." ";
				$whrClause .= " (";
				
				$curFilterMode = $arrFilters[$i]["filter_mode"];
				$curFilterFields = $arrFilters[$i]["fields"];
				
				for($f=0;$f<count($curFilterFields);$f++) {
					
					$curFilterField = $curFilterFields[$f];
					$curFilterFieldName = $curFilterField["field"];
					$curFilterFieldType = $curFilterField["type"];
					$curFilterFieldValue = $curFilterField["value"];
					
					if($f>0) $whrClause .= " ".$curFilterMode;
					
					if($curFilterFieldType=="eq") $whrClause .= " (".$curFilterFieldName." = '".$curFilterFieldValue."') ";
					if($curFilterFieldType=="neq") $whrClause .= " (".$curFilterFieldName." <> '".$curFilterFieldValue."') ";
					if($curFilterFieldType=="lt") $whrClause .= " (".$curFilterFieldName." < '".$curFilterFieldValue."') ";
					if($curFilterFieldType=="gt") $whrClause .= " (".$curFilterFieldName." > '".$curFilterFieldValue."') ";
					if($curFilterFieldType=="like") $whrClause .= " (upper(".$curFilterFieldName.") LIKE '%".strtoupper($curFilterFieldValue)."%') ";
					
					
					
				} 
				$whrClause .= " ) "; 
			} 
			$whrClause .= " ) "; 
		}
		
		if(isset($resArray['ordby'])) {
			$arrOrdby = $resArray['ordby'];
			//var_dump($arrOrdby);
			
			if(isset($arrOrdby[0])) {
				$ordbyClause = " ORDER BY ";
				for($ob=0;$ob<count($arrOrdby);$ob++) {
					if($ob>0) $ordbyClause.= ",";
					$ordbyClause .= $arrOrdby[$ob]["field"]." ".$arrOrdby[$ob]["dir"];
				}
			} else { 
				$ordbyFields = $arrOrdby['field'];
				$arrOrdbyFields = explode(",",$ordbyFields);
				$ordbyClause = " ORDER BY ";
				for($ob=0;$ob<count($arrOrdbyFields);$ob++) {
					if($ob>0) $ordbyClause.= ",";
					$ordbyClause.= trim($arrOrdbyFields[$ob])." ".$arrOrdby['dir'];
				}
			} 
		}
		
		if(isset($resArray['limit'])) {
			$limitClause = " LIMIT ".$resArray['limit'];
		}
		
	}

	$time_start = microtime(true); 
	//query:


	$query = "SELECT * FROM TABLE(
		SELECT T1.* 
		FROM TABLE(


	SELECT 
	'OFFERTO' as TIPO , 
	sdan8 AS CODICE_CLIENTE ,               
	digits(ondtey)||'/'||digits(ondtem) AS ANNO_MESE , 
	sum(sdaexp/100) AS IMPORTO 
	FROM ".$curLib.".f554211 left join ".$curLib.".f00365 on sddrqj=ondtej 
	WHERE SDDCTO IN ('OF' , 'SQ') 
	GROUP BY sdan8 , digits(ondtey)||'/'||digits(ondtem)
	) T1
	";

	if ($whrClause != "")
		$query .= $whrClause;

	$query .= "
	UNION ALL 
	SELECT T2.* 
			FROM TABLE(
	SELECT 'ORDINATO' as TIPO , 
	sdan8 CODICE_CLIENTE , 
	digits(ondtey)||'/'||digits(ondtem) AS ANNO_MESE , 
	sum(sdaexp/100) AS IMPORTO FROM ".$curLib.".f554211 left join ".$curLib.".f00365 on sddrqj=ondtej
	WHERE SDDCTO NOT IN ('OF' , 'SQ')           
	GROUP BY sdan8 , digits(ondtey)||'/'||digits(ondtem)
	) T2                                                                                            
	";  
	if ($whrClause != "")
		$query .= $whrClause;

	$query .= ") T  ";


	if ($ordbyClause != "")
		$query .= $ordbyClause;
	if ($limitClause != "")
		$query .= $limitClause;
	$query .= " FOR FETCH ONLY";
	
	$result=odbc_exec($conn,$query);
	if(!$result) {
		echo '{"status":"ERROR","errmsg":'.json_encode(odbc_errormsg($conn)).'}';
		exit;
	}
	$time_end = microtime(true);
	$execution_time = ($time_end - $time_start);
	//echo '<b>Query:</b> '.$execution_time.' s';

	$time_start = microtime(true); 
	$r = 0;


// Modifica per raggruppare i dati per CODICE_CLIENTE
$time_start = microtime(true); 
$data = [];

while ($row = odbc_fetch_array($result)) {
    // Pulisci i valori
    foreach (array_keys($row) as $key) {
        $row[$key] = utf8_encode(trim($row[$key]));
        $row[$key] = str_replace("§", "@", trim($row[$key]));
    }

    // Raggruppa per CODICE_CLIENTE
    $codiceCliente = $row['CODICE_CLIENTE'];

    // Se il codice cliente non esiste ancora nell'array, crealo
    if (!isset($data[$codiceCliente])) {
        $data[$codiceCliente] = [
            'CODICE_CLIENTE' => $codiceCliente,
           'DATI' => [] 
        ];
    }

    // Aggiungi i dati all'array DATI
    $data[$codiceCliente]['DATI'] [] = [
        'TIPO' => $row['TIPO'],
        'ANNO_MESE' => $row['ANNO_MESE'],
        'IMPORTO' => $row['IMPORTO']
    ];
}

// Converti l'array raggruppato in JSON
$jsonOutput = json_encode(array_values($data), JSON_PRETTY_PRINT);

// Restituisci il risultato JSON
echo $jsonOutput;

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);

// Chiudi la connessione
odbc_close($conn);