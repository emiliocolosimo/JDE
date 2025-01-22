<?php
if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

//	Program Name:		mappa-clienti.php
//	Program Title:		Mappa clienti
//	Created by:			matti
//	Template family:	Responsive
//	Template name:		Page at a Time.tpl
//	Purpose:        	Maintain a database file using embedded SQL. Supports options for add, change, delete and display.
//	Program Modifications:

/*
	todo: 
	- anno fatturato sistemare in javascript
	- etichetta della ragione sociale, sistemare che sia più visibile
*/

require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');
require_once('websmart/v13.2/include/en-US/WSRequiredValidator.php');
require_once('websmart/v13.2/include/en-US/WSNumericValidator.php');
require_once('include/mappa-clienti.config.php');

class mappa_clienti extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'page' => 1,
		'listSize' => 20,
		'filters' => array('ABAN8' => '', 'ABALPH' => '', 'ALCTY1' => '', 'ALCOUN' => '', 'ALADDS' => '', 'ALCTR' => '', 'filtGeo' => '', 'ABAT1' => '', 
		'ABAT1_C' => '', 'ABAT1_CS' => '', 'ABAT1_P' => '', 'ABAT1_PS' => '', 'ABAT1_V' => ''
		)
	);
	
	
	protected $keyFields = array('ABAN8');
	protected $uniqueFields = array('ABAN8');
	protected $repPartenza = "";
	protected $repArrivo = "";
	protected $googleApiKey = GOOGLE_MAPS_APIKEY; 
	protected $googleBatchApiKey = GOOGLE_MAPS_APIKEY_BATCH;  
	
	public function runMain()
	{
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		 
		// Connect to the database
		try 
		{
			// Defaults
			// pf_db2OdbcDsn: DSN=*LOCAL
			// pf_db2OdbcLibraryList: ;DBQ=, <<libraries, space separated, build from files included, add ',' at the beginning to not have a default schema>>
			// pf_db2OdbcOptions: ;NAM=1;TSFT=1 -> setting system naming and timestamp type to IBM standards
			$this->db_connection = new PDO(
			'odbc:' . $this->defaults['pf_db2OdbcDsn'] . $this->defaults['pf_db2OdbcLibraryList'] . $this->defaults['pf_db2OdbcOptions'], 
			$this->defaults['pf_db2OdbcUserID'], 
			$this->defaults['pf_db2OdbcPassword'],
			$this->defaults['pf_db2PDOOdbcOptions']
			);
		}
		catch (PDOException $ex)
		{
			die('Could not connect to database: ' . $ex->getMessage());
		}
		
		header('Content-Type: text/html; charset=iso-8859-1');		
		 
		if(!isset($_SESSION["GTPROG"]) || $_SESSION["GTPROG"]=="" || $_SESSION["GTPROG"]=="0") $this->getTmpProgre();
	
	
		if(isset($_SESSION["GTPROG"]) && $_SESSION["GTPROG"]!="" && $_SESSION["GTPROG"]!="0") {
			$GTPROG = $_SESSION["GTPROG"];	
		}
		 
		// Fetch the program state
		$this->getState();
		
		if(!isset($_SESSION["ft_mappa-clienti"])) {
			$this->programState['filters']['ABAT1_C'] = "S"; 
			$this->programState['filters']['ABAT1_CS'] = "S"; 
			$this->programState['filters']['ABAT1_P'] = "S";  
			$this->programState['filters']['ABAT1_PS'] = "S"; 
			$this->programState['filters']['ABAT1_V'] = "S";
			$_SESSION["ft_mappa-clienti"] = "N";
		}
		
		$this->formFields = array();
		$this->optionalIndicator = "(Optional)";
		
		// Run the specified task
		switch ($this->pf_task)
		{
			// Display the main list
			case 'default':
			$this->displayList();
			break;
			
			// Output a filtered list
			case 'filter':
			$this->filterList();
			break;
			
			case 'getCoords':
			$this->getCoords();
			break;
			
			case 'lstSelArrivi':
			$this->lstSelArrivi();
			break;
			
			case 'lstSelPartenze':
			$this->lstSelPartenze();
			break;
			
			case 'addGiro':
			$this->addGiro();
			break;

			case 'dltGiro':
			$this->dltGiro();
			break;	

			case 'lstGiro':
			$this->lstGiro();
			break;	
			
			case 'newGiroV':
			$this->newGiroV();
			break;	
			  
			case 'calcGiroV':
			$this->calcGiroV();
			break;
			
			case 'getPointsInBound':
			$this->getPointsInBound();
			break;
			
			case 'savPointsInBound':
			$this->savPointsInBound();
			break;			
			
			case 'ricCoord':
			$this->ricCoord();
			break;
			
			case 'savCoord':
			$this->savCoord();
			break;
			
			case 'dspCoord':
			$this->dspCoord();
			break;	
			
			case 'getKml':
			$this->getKml();
			break;		
			  
			case 'addArrivo': 
			$this->addArrivo();
			break;
			
			case 'addArrivo1': 
			$this->addArrivo1();
			break;
			
			case 'dltArrivo': 
			$this->dltArrivo();
			break;			
			
			case 'lstArrivi': 
			$this->lstArrivi();
			break;
			 
			
			case 'addPartenza': 
			$this->addPartenza();
			break;
			
			case 'addPartenza1': 
			$this->addPartenza1();
			break;
			
			case 'dltPartenza': 
			$this->dltPartenza();
			break;			
			
			case 'lstPartenze': 
			$this->lstPartenze();
			break;			 
			  
		}
	}

	protected function getKml() {
		$routeLatLngs = xl_get_parameter("routeLatLngs");
		$stepsLatLngs = xl_get_parameter("stepsLatLngs");
		$GIPACL = xl_get_parameter("GIPACL");
		$GIARCL = xl_get_parameter("GIARCL");
		$GTPROG = xl_get_parameter("GTPROG");
		
		
		$routeLatLngsArray = json_decode($routeLatLngs,true);
		$stepsLatLngsArray = json_decode($stepsLatLngs,true);
	 	
		$routeKml = "";
		for($i=0;$i<count($routeLatLngsArray);$i++) {
			$routeKml = $routeKml."\n".$routeLatLngsArray[$i]["lng"].",".$routeLatLngsArray[$i]["lat"].",0";
		}
		$routeKml = $routeKml."\n";
		
		
		$stepsKml = "";

		$ABAN8 = $GIPACL;
		$decCliente = $this->decodCliente($ABAN8); 
		$stepsKml = $stepsKml."
		<Placemark>
		<name>".trim(htmlspecialchars($decCliente["ALADD2"],ENT_QUOTES,"ISO-8859-1")).", ".trim(htmlspecialchars($decCliente["ALCTY1"],ENT_QUOTES,"ISO-8859-1"))."</name>
		<styleUrl>#icon-1899-DB4436-nodesc</styleUrl>
		<Point>
		  <coordinates>
		    ".$decCliente["CCLONG"].",".$decCliente["CCLATI"].",0
		  </coordinates>
		</Point>
		</Placemark>";
		
		$stepsKml = $stepsKml."\n";		 
		 
		for($i=0;$i<count($stepsLatLngsArray);$i++) {
			$stepsKml = $stepsKml."
			<Placemark>
			<name>".urldecode($stepsLatLngsArray[$i]["addr"])."</name>
			<styleUrl>#icon-1899-DB4436-nodesc</styleUrl>
			<Point>
			  <coordinates>
			    ".$stepsLatLngsArray[$i]["position"]["lng"].",".$stepsLatLngsArray[$i]["position"]["lat"].",0
			  </coordinates>
			</Point>
			</Placemark>";
			
			$stepsKml = $stepsKml."\n";		
		}
		
		$stepsKml = $stepsKml."\n";		
		
		$ABAN8 = $GIARCL;
		$decCliente = $this->decodCliente($ABAN8); 
		$stepsKml = $stepsKml."
		<Placemark>
		<name>".trim(htmlspecialchars($decCliente["ALADD2"],ENT_QUOTES,"ISO-8859-1")).", ".trim(htmlspecialchars($decCliente["ALCTY1"],ENT_QUOTES,"ISO-8859-1"))."</name>
		<styleUrl>#icon-1899-DB4436-nodesc</styleUrl>
		<Point>
		  <coordinates>
		    ".$decCliente["CCLONG"].",".$decCliente["CCLATI"].",0
		  </coordinates>
		</Point>
		</Placemark>";		
		
		$stepsKml = $stepsKml."\n";		
		
		$kml = $this->getSegment("kmlHeader", array_merge(get_object_vars($this), get_defined_vars()));
		$kml = $kml . $routeKml;
		$kml = $kml . "</coordinates></LineString></Placemark>";	
		$kml = $kml . $stepsKml;
		$kml = $kml . "</Folder></Document></kml>";
		
		file_put_contents("maps/kmlexport/kml_".$GTPROG.".kml",$kml);
		 
			
	}

	protected function dspCoord() {
		$this->writeSegment("clcCoord", array_merge(get_object_vars($this), get_defined_vars()));
	}

	protected function ricCoord() {
		$ABAN8 = (int) xl_get_parameter("ABAN8");
		$selString = "SELECT JRGDTA94C.F0116.ALADD2, JRGDTA94C.F0116.ALCTY1, JRGDTA94C.F0116.ALADDS, JRGDTA94C.F0116.ALCTR 
		FROM JRGDTA94C.F0101  
		inner join JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 
		WHERE ABAN8 = '".$ABAN8."'		
		";
		
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		foreach(array_keys($row) as $key)
		{
			$row[$key] = mb_convert_encoding(trim($row[$key]), 'UTF-8', 'ISO-8859-1');  
		}
		 
		echo json_encode($row);
		
	}
	
	protected function lstNazioni() {
		$selString = "SELECT TRIM(DRKY) AS DRKY, TRIM(DRDL01) AS DRDL01   
		FROM JRGCOM94T.F0005 
		WHERE DRSY = '00' AND DRRT = 'CN'
		AND TRIM(DRKY) <> '*'";	
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		echo '<select class="form-control" name="filter_ALCTR" id="filter_ALCTR">';
		echo '<option value=""></option>';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1"); 
				 
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			echo '<option value="'.$DRKY.'" '.(($this->programState['filters']['ALCTR']==$DRKY)?('selected="selected"'):('')).'>'.$DRDL01.'</option>';
		}
		echo '</select>';
		
	}
	
	protected function savCoord() {
		$ABAN8 = (int) xl_get_parameter("ABAN8");
		
		$CCAN8 = $ABAN8;
		$CCDTIN = date("Ymd");
		$CCORIN = date("His");
		$CCLATI = xl_get_parameter("CCLATI");
		$CCLONG = xl_get_parameter("CCLONG");
		$CCSTAT = 'OK';		
		
		$dltQuery = "DELETE FROM BCD_DATIV2.CLICRD0F WHERE CCAN8 = ".$CCAN8." WITH NC";
		$res = $this->db_connection->exec($dltQuery); 		
		
		$insQuery = "INSERT INTO BCD_DATIV2.CLICRD0F (CCAN8,CCLATI,CCLONG,CCSTAT,CCDTIN,CCORIN) VALUES(:CCAN8,:CCLATI,:CCLONG,:CCSTAT,:CCDTIN,:CCORIN) WITH NC";
		$insStmt = $this->db_connection->prepare($insQuery); 
		$insStmt->bindValue(':CCAN8', $CCAN8, PDO::PARAM_INT);
		$insStmt->bindValue(':CCLATI', $CCLATI, PDO::PARAM_INT);
		$insStmt->bindValue(':CCLONG', $CCLONG, PDO::PARAM_INT);
		$insStmt->bindValue(':CCSTAT', $CCSTAT, PDO::PARAM_STR);
		$insStmt->bindValue(':CCDTIN', $CCDTIN, PDO::PARAM_INT);
		$insStmt->bindValue(':CCORIN', $CCORIN, PDO::PARAM_INT); 
		$insResult = $insStmt->execute();  

		header("Location: mappa-clienti.php");
		
	}

	protected function getPointsInBound() {
	
		set_time_limit(120);
	
		$GTPROG = $_SESSION["GTPROG"];
	
		$GITPCL_C = xl_get_parameter("GITPCL_C"); 
		$GITPCL_CS = xl_get_parameter("GITPCL_CS"); 
		$GITPCL_P = xl_get_parameter("GITPCL_P"); 
		$GITPCL_PS = xl_get_parameter("GITPCL_PS"); 
		$GITPCL_V = xl_get_parameter("GITPCL_V"); 	
		$GIRARI = (int) xl_get_parameter("GIRARI"); 	
		$GIRARI = round($GIRARI / 1.6);
		
		$ne_lat = xl_get_parameter("ne_lat");
		$ne_lng = xl_get_parameter("ne_lng");
		$sw_lat = xl_get_parameter("sw_lat");
		$sw_lng = xl_get_parameter("sw_lng");
		  
		$sep3a = "";
		$retJson = ""; 
		 
		$retArray = array();
		$x = 0; 
		 
		$lnk = ""; 
		$selString = "SELECT * 
		FROM BCD_DATIV2.RGPGPI00F 
		WHERE GPPROG = ".$GTPROG." 
		AND GPAN8 NOT IN (
			SELECT GTCLIE FROM BCD_DATIV2.RGPGIT00F 
			WHERE GTPROG = ".$GTPROG." 
		) AND (";
		if($GITPCL_C=="S") { $selString.= $lnk." GPBAT1 = 'C' "; $lnk = "OR"; }
		if($GITPCL_CS=="S") { $selString.= $lnk." GPBAT1 = 'CS' "; $lnk = "OR"; }
		if($GITPCL_P=="S") { $selString.= $lnk." GPBAT1 = 'P' "; $lnk = "OR"; }
		if($GITPCL_PS=="S") { $selString.= $lnk." GPBAT1 = 'PS' "; $lnk = "OR"; }
		if($GITPCL_V=="S") { $selString.= $lnk." GPBAT1 = 'V' "; $lnk = "OR"; } 
		$selString.= ") 
		ORDER BY GPFAT1 DESC, GPFAT2 DESC, GPFAT3 DESC
		";
		 
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
			foreach(array_keys($row) as $key)
			{ 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}	
			 
	  		$tmpArray = array(); 
	  		$tmpArray["status"] = "OK";  
	  		$tmpArray["ABAN8"] = mb_convert_encoding(trim($GPAN8), 'UTF-8', 'ISO-8859-1');  
	  		$tmpArray["ABALPH"] = mb_convert_encoding(trim($GPALPH), 'UTF-8', 'ISO-8859-1');  
	  		$tmpArray["ALCTY1"] = mb_convert_encoding(trim($GPCTY1), 'UTF-8', 'ISO-8859-1');  
	  		$tmpArray["ALCOUN"] = mb_convert_encoding(trim($GPCOUN), 'UTF-8', 'ISO-8859-1');  
	  		$tmpArray["ALADDZ"] = mb_convert_encoding(trim($GPADDZ), 'UTF-8', 'ISO-8859-1');  
	  		$tmpArray["ALADD2"] = mb_convert_encoding(trim($GPADD2), 'UTF-8', 'ISO-8859-1');
	  		$tmpArray["ALADDS"] = mb_convert_encoding(trim($GPADDS), 'UTF-8', 'ISO-8859-1');  
	  		$tmpArray["GLAT"] = $GPLAT;  
	  		$tmpArray["GLNG"] = $GPLNG;  
	  		$tmpArray["fatt1"] = number_format($GPFAT1,0,",","."); 
	  		$tmpArray["fatt2"] = number_format($GPFAT2,0,",","."); 
	  		$tmpArray["fatt3"] = number_format($GPFAT3,0,",",".");  
	  		$tmpArray["ABAT1"] = trim($GPBAT1); 
	  	 
	  		$retArray[$x] = $tmpArray;
	  		 
	  		$x++;
	  		 
	  		$retJson.= $sep3a.json_encode($tmpArray);
	  		$sep3a = ", ";
	  		
		}	
		  
		 
		if($x>0) echo '['.$retJson.']';	
		else echo "[{\"status\":\"NONE\"}]";			
		
	}

	protected function savPointsInBound() {
	
		set_time_limit(120);
	
		$GTPROG = $_SESSION["GTPROG"];
	
		$ne_lat = xl_get_parameter("ne_lat");
		$ne_lng = xl_get_parameter("ne_lng");
		$sw_lat = xl_get_parameter("sw_lat");
		$sw_lng = xl_get_parameter("sw_lng");
		$GIMIFA = (int) xl_get_parameter("GIMIFA");
		  
		$sep3a = "";
		$retJson = ""; 
		 
		$retArray = array();
		$x = 0; 
		 
		$annoAttuale = (int) substr(date("Y"),2,2); 
		 
		$selString = "
		SELECT T.ABAN8, T.ABAT1, T.FATT1, T.FATT2, T.FATT3 
		FROM TABLE(
		SELECT ABAN8, ABAT1, 
		coalesce((Select Sum(case when SDIVD between 1".$annoAttuale."000 and 1".$annoAttuale."366 then SDAEXP/100 else 0 end) as FATT1 FROM JRGDTA94C.F554211 where SDDOC<>0 and SDAN8 = ABAN8), 0) as FATT1, 
		coalesce((Select Sum(case when SDIVD between 1".($annoAttuale-1)."000 and 1".($annoAttuale-1)."366 then SDAEXP/100 else 0 end) as FATT2 FROM JRGDTA94C.F554211 where SDDOC<>0 and SDAN8 = ABAN8), 0) as FATT2, 
		coalesce((Select Sum(case when SDIVD between 1".($annoAttuale-2)."000 and 1".($annoAttuale-2)."366 then SDAEXP/100 else 0 end) as FATT3 FROM JRGDTA94C.F554211 where SDDOC<>0 and SDAN8 = ABAN8), 0) as FATT3    
		FROM JRGDTA94C.F0101  
		inner join JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 
		inner join BCD_DATIV2.CLICRD0F on JRGDTA94C.F0101.ABAN8 = BCD_DATIV2.CLICRD0F.CCAN8 AND CCSTAT = 'OK' 
		WHERE ABAN8 NOT IN (
			SELECT GTCLIE 
			FROM BCD_DATIV2.RGPGIT00F 
			WHERE GTPROG = ".$GTPROG."
		) 
		and CAST(CCLATI as DOUBLE) >= ".trim($sw_lat)." and CAST(CCLONG as DOUBLE) >= ".trim($sw_lng)."  
		and CAST(CCLATI as DOUBLE) <= ".trim($ne_lat)." and CAST(CCLONG as DOUBLE) <= ".trim($ne_lng)." )
		AS T 
		";
		
		if($GIMIFA!=0) {
			$selString .= " 
			where T.FATT1 > ".$GIMIFA." 
			OR T.FATT2 > ".$GIMIFA." 
			OR T.FATT3 > ".$GIMIFA." 
			OR T.ABAT1 NOT IN ('C', 'CS')
			";	
		}
	
		 
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
			foreach(array_keys($row) as $key)
			{ 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}	
			
			$decCliente = $this->decodCliente($ABAN8);
			
  			$fatt1 = round($FATT1);
  			$fatt2 = round($FATT2);
  			$fatt3 = round($FATT3);			
			
			/* 
	  		$fatt1 = 0;
	  		$fatt2 = 0;
	  		$fatt3 = 0;
	  		$fatturato = $this->calcFatturato($ABAN8);
	  		if($fatturato) {
	  			if(isset($fatturato["FATT1"])) $fatt1 = round($fatturato["FATT1"]);
	  			if(isset($fatturato["FATT2"])) $fatt2 = round($fatturato["FATT2"]);
	  			if(isset($fatturato["FATT3"])) $fatt3 = round($fatturato["FATT3"]);
	  		}
	  		*/
	  		
			//scrivo in file temp:
			$GPPROG = $GTPROG;
			$GPAN8 = $ABAN8;
			$GPALPH = $decCliente["ABALPH"];
			$GPCTY1 = $decCliente["ALCTY1"];
			$GPCOUN = $decCliente["ALCOUN"]; 
			$GPADDZ = $decCliente["ALADDZ"]; 
			$GPADD2 = $decCliente["ALADD2"];
			$GPADDS = $decCliente["ALADDS"]; 
			$GPLAT = $decCliente["CCLATI"];
			$GPLNG = $decCliente["CCLONG"];
			$GPFAT1 = $fatt1;
			$GPFAT2 = $fatt2;
			$GPFAT3 = $fatt3;
			$GPBAT1 = $decCliente["ABAT1"];
			
			$query = "INSERT INTO BCD_DATIV2.RGPGPI00F 
			(GPPROG,GPAN8,GPALPH,GPCTY1,GPCOUN,GPADDZ,GPADD2,GPADDS,GPLAT,GPLNG,GPFAT1,GPFAT2,GPFAT3,GPBAT1) VALUES 
			(:GPPROG,:GPAN8,:GPALPH,:GPCTY1,:GPCOUN,:GPADDZ,:GPADD2,:GPADDS,:GPLAT,:GPLNG,:GPFAT1,:GPFAT2,:GPFAT3,:GPBAT1) WITH NC
			";
			$stmt_ins = $this->db_connection->prepare($query);
			$stmt_ins->bindValue(':GPPROG', $GPPROG, PDO::PARAM_INT);
			$stmt_ins->bindValue(':GPAN8', $GPAN8, PDO::PARAM_INT);
			$stmt_ins->bindValue(':GPALPH', $GPALPH, PDO::PARAM_STR);
			$stmt_ins->bindValue(':GPCTY1', $GPCTY1, PDO::PARAM_STR);
			$stmt_ins->bindValue(':GPCOUN', $GPCOUN, PDO::PARAM_STR);
			$stmt_ins->bindValue(':GPADDZ', $GPADDZ, PDO::PARAM_STR);
			$stmt_ins->bindValue(':GPADD2', $GPADD2, PDO::PARAM_STR);
			$stmt_ins->bindValue(':GPADDS', $GPADDS, PDO::PARAM_STR);
			$stmt_ins->bindValue(':GPLAT', $GPLAT, PDO::PARAM_INT);
			$stmt_ins->bindValue(':GPLNG', $GPLNG, PDO::PARAM_INT);
			$stmt_ins->bindValue(':GPFAT1', $GPFAT1, PDO::PARAM_INT);
			$stmt_ins->bindValue(':GPFAT2', $GPFAT2, PDO::PARAM_INT);
			$stmt_ins->bindValue(':GPFAT3', $GPFAT3, PDO::PARAM_INT);
			$stmt_ins->bindValue(':GPBAT1', $GPBAT1, PDO::PARAM_STR);
			$result_ins = $stmt_ins->execute();
			if (!$result_ins)
			{
				$this->dieWithPDOError($stmt_ins);
			}
			 
	  		$x++;
	  		  
		}	
		  
		if($x>0) echo "[{\"status\":\"OK\"}]";	
		else echo "[{\"status\":\"NONE\"}]";				
		
	}

	protected function calcGiroV() {
		 
		$GIUSER = xl_get_parameter("GIUSER");
		$GITIPA = xl_get_parameter("GITIPA");
		$GITIAR = xl_get_parameter("GITIAR");
		$GINOPE = xl_get_parameter("GINOPE");
		$GITIPO = xl_get_parameter("GITIPO");
		$GIPAUS = xl_get_parameter("GIPAUS");
		$GIMECL = xl_get_parameter("GIMECL");
		$GIORDI = xl_get_parameter("GIORDI");
		$GIPACL = xl_get_parameter("GIPACL");
		$GIARCL = xl_get_parameter("GIARCL"); 
		$ricalcInBound = xl_get_parameter("ricalcInBound"); 
		$GITPCL_C = xl_get_parameter("GITPCL_C"); 
		$GITPCL_CS = xl_get_parameter("GITPCL_CS"); 
		$GITPCL_P = xl_get_parameter("GITPCL_P"); 
		$GITPCL_PS = xl_get_parameter("GITPCL_PS"); 
		$GITPCL_V = xl_get_parameter("GITPCL_V"); 
		$GIRARI = (int) xl_get_parameter("GIRARI"); 
		$GIMIFA = xl_get_parameter("GIMIFA"); 
		$GIVITU = xl_get_parameter("GIVITU"); 
		$GIRARI = round($GIRARI / 1.6); 
		
		$GTPROG = $_SESSION["GTPROG"]; 
	
		$markerColors = array();
		$markerColors[1] = "33cccc";
		$markerColors[2] = "66ffcc";
		$markerColors[3] = "ffffcc";
		$markerColors[4] = "ccff33";
		$markerColors[5] = "669900";
		$markerColors[6] = "ff0066";
		$markerColors[7] = "6666ff";
		$markerColors[8] = "990099";
		$markerColors[9] = "666699";
		$markerColors[10] = "800000";	
	
		if($ricalcInBound!="N") {
			$query = "DELETE FROM BCD_DATIV2.RGPGPI00F WHERE GPPROG = ".$GTPROG." WITH NC ";	
			$res = $this->db_connection->exec($query);
		}
		
		$this->writeSegment("segMappa2", array_merge(get_object_vars($this), get_defined_vars()));
	}

	protected function dspGCoords($GTPROG,$GIPACL,$GIARCL,$GITIPA,$GITIAR) {
	 
		//partenza 
		$repPartenza = "";
		
		if($GITIPA=="cliente") {
			$ABAN8 = $GIPACL;
			$decCliente = $this->decodCliente($ABAN8);
			$lat = $decCliente["CCLATI"];
			$lng = $decCliente["CCLONG"];
			echo "origin = new google.maps.LatLng(".$lat.", ".$lng.");\n";
			
			$repPartenza = "<tr><td><div class=\"markerTappaDivStart\"> </div></td><td>";
			$repPartenza.= "<strong>".trim(htmlspecialchars($decCliente["ABALPH"],ENT_QUOTES,"ISO-8859-1")). "</strong><br>";
			$repPartenza.= trim(htmlspecialchars($decCliente["ALADD2"],ENT_QUOTES,"ISO-8859-1")). ",";
			$repPartenza.= trim(htmlspecialchars($decCliente["ALCTY1"],ENT_QUOTES,"ISO-8859-1")). ",";
			$repPartenza.= trim(htmlspecialchars($decCliente["ALADDS"],ENT_QUOTES,"ISO-8859-1")). "</td></tr>";			
			
		} else {		
			$GITIPA = (int) $GITIPA;
			$query = "SELECT PARLAT, PARLNG, PARIND, PARPRO, PARNAZ, PARLOC FROM RGPPPA00F WHERE PARPRG=".$GITIPA;
			$stmt = $this->db_connection->prepare($query);
			$result = $stmt->execute();
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			foreach(array_keys($row) as $key)
			{ 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			echo "origin = new google.maps.LatLng(".trim($PARLAT).", ".trim($PARLNG).");\n";
			
			$repPartenza = "<tr><td><div class=\"markerTappaDivStart\"> </div></td><td>"; 
			$repPartenza.= trim(htmlspecialchars($PARIND,ENT_QUOTES,"ISO-8859-1")). ",";
			$repPartenza.= trim(htmlspecialchars($PARLOC,ENT_QUOTES,"ISO-8859-1")). ",";
			$repPartenza.= trim(htmlspecialchars($PARPRO,ENT_QUOTES,"ISO-8859-1")). "</td></tr>";			
			 
		}
		

			
		echo "stops[n] = origin;\n";
		echo "tsp.addWaypoint(origin, addWaypointCallback);\n";
		echo "n++\n";
	
	 
		$totTtc= 0;
		$cntto = 0;
		$count = 1;
		
		/*
	$query = "SELECT ABAN8, ABALPH, 
		ALADD2, ALADD3, ALADD4, ALADDZ, 
		ALCTY1, ALCOUN, ALADDS, ALCTR, 
		CCLATI, CCLONG, ABAT1  
		FROM JRGDTA94C.F0101 
		inner join JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 
		inner join BCD_DATIV2.CLICRD0F ON CCAN8 = ABAN8 
		WHERE ABAN8 = ".$ABAN8."
		";		
		*/
		
		$selString = "SELECT GTCLIE, ABAN8, ABALPH, 
		ALADD2, ALADD3, ALADD4, ALADDZ, 
		ALCTY1, ALCOUN, ALADDS, ALCTR, 
		CCLATI, CCLONG, ABAT1  
		FROM BCD_DATIV2.RGPGIT00F 
		INNER JOIN JRGDTA94C.F0101 ON JRGDTA94C.F0101.ABAN8 = BCD_DATIV2.RGPGIT00F.GTCLIE 
		INNER JOIN JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 
		INNER JOIN BCD_DATIV2.CLICRD0F ON CCAN8 = ABAN8 
		WHERE GTPROG = ".$GTPROG;
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
			foreach(array_keys($row) as $key)
			{ 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}	
			
			if($GTCLIE!=$GIPACL && $GTCLIE!=$GIARCL) {
				
				$ABAN8 = $GTCLIE;
				//rimosso per prestazioni:
				//$decCliente = $this->decodCliente($ABAN8);
				
				$lat = $CCLATI;
				$lng = $CCLONG;
			 
				$this->writeSegment("segMarker", array_merge(get_object_vars($this), get_defined_vars()));
				$totTtc++;
				$count++;
	 
				echo "tmpltln = new google.maps.LatLng(".trim($lat).",".trim($lng).");\n";
				echo "tsp.addWaypoint(tmpltln, addWaypointCallback);\n";
				echo "loclat[n] = tmpltln.lat();\n";
				echo "loclng[n] = tmpltln.lng();\n";
				$ABAN8 = str_replace("'","",$ABAN8);
				echo "rag[n] = '".trim(str_replace("'","",$ABALPH))."';\n";
				$ALADD2 = trim(str_replace("'","",$ALADD2));
				$ALCTY1 = trim(str_replace("'","",$ALCTY1));
				$ALADDS = trim(str_replace("'","",$ALADDS));
				 
		  		$fatt1 = 0;
		  		$fatt2 = 0;
		  		$fatt3 = 0;
		  		$fatturato = $this->calcFatturato($ABAN8);
		  		if($fatturato) {
		  			if(isset($fatturato["FATT1"])) $fatt1 = round($fatturato["FATT1"]);
		  			if(isset($fatturato["FATT2"])) $fatt2 = round($fatturato["FATT2"]);
		  			if(isset($fatturato["FATT3"])) $fatt3 = round($fatturato["FATT3"]);
		  		}				 
				 
				echo "ind[n] = '";
				echo $ALADD2;
				echo ",";
				echo $ALCTY1;
				echo ",";
				echo $ALADDS;
				echo "';\n";
				
				echo "fat1[n] = '".$fatt1."';\n";
				echo "fat2[n] = '".$fatt2."';\n";
				echo "fat3[n] = '".$fatt3."';\n";
				
				echo "codcli[n] = '".$ABAN8."';\n";
				
				echo "stops[n] = tmpltln;\n";
				echo "n++;\n";
				
			}
			
		}
		
		 
		$repArrivo = "";
		
		if($GITIAR=="cliente") { 
			$ABAN8 = $GIARCL;
			$decCliente = $this->decodCliente($ABAN8);
			$lat = $decCliente["CCLATI"];
			$lng = $decCliente["CCLONG"];
			echo "destination = new google.maps.LatLng(".trim($lat).", ".trim($lng).");\n";
			
			$repArrivo = "<tr><td><div class=\"markerTappaDivStart\"> </div></td><td>";
			$repArrivo.= "<strong>".trim(htmlspecialchars($decCliente["ABALPH"],ENT_QUOTES,"ISO-8859-1")). "</strong><br>";
			$repArrivo.= trim(htmlspecialchars($decCliente["ALADD2"],ENT_QUOTES,"ISO-8859-1")).",";
			$repArrivo.= trim(htmlspecialchars($decCliente["ALCTY1"],ENT_QUOTES,"ISO-8859-1")).",";
			$repArrivo.= trim(htmlspecialchars($decCliente["ALADDS"],ENT_QUOTES,"ISO-8859-1"))."<br>";
			$repArrivo.="<span id=\"stepTimeArrivo\"></span></td></tr>";
		} else {
			$GITIAR = (int) $GITIAR;
			$query = "SELECT ARRLAT, ARRLNG, ARRIND, ARRPRO, ARRNAZ, ARRLOC FROM RGPPAR00F WHERE ARRPRG=".$GITIAR;
			$stmt_ar = $this->db_connection->prepare($query);
			$result_ar = $stmt_ar->execute();
			$row_ar = $stmt_ar->fetch(PDO::FETCH_ASSOC);
			foreach(array_keys($row_ar) as $key)
			{ 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row_ar[$key];
			}
			
			echo "destination = new google.maps.LatLng(".trim($ARRLAT).", ".trim($ARRLNG).");\n";
			
			$repArrivo = "<tr><td><div class=\"markerTappaDivStart\"> </div></td><td>"; 
			$repArrivo .= trim(htmlspecialchars($ARRIND,ENT_QUOTES,"ISO-8859-1")). ",";
			$repArrivo.= trim(htmlspecialchars($ARRLOC,ENT_QUOTES,"ISO-8859-1")). ",";
			$repArrivo.= trim(htmlspecialchars($ARRPRO,ENT_QUOTES,"ISO-8859-1")). "</td></tr>";		
		} 
		
		echo "stops[n] = destination;\n";
		echo "tsp.addWaypoint(destination, addWaypointCallback);\n";
		echo "n++;\n";	
		
		$this->repPartenza = $repPartenza;
		$this->repArrivo = $repArrivo;
			
	}
	
	protected function dspAllMarkers($GIMIFA) {
		
 		
		$annoAttuale = (int) substr(date("Y"),2,2); //(int) substr(date("Y"),2,2);
 		
		$selString = "
		SELECT T.ABAN8,T.ABALPH,T.CCLATI,T.CCLONG,T.ALCTY1,T.ALADD2,T.ALADDS,T.ABAT1,T.FATT1,T.FATT2,T.FATT3 
		FROM TABLE (		
		SELECT ABAN8,ABALPH,CCLATI,CCLONG,ALCTY1,ALADD2,ALADDS,ABAT1, 
		coalesce((Select Sum(case when SDIVD between 1".$annoAttuale."000 and 1".$annoAttuale."366 then SDAEXP/100 else 0 end) as FATT1 FROM JRGDTA94C.F554211 where SDDOC<>0 and SDAN8 = ABAN8), 0) as FATT1, 
		coalesce((Select Sum(case when SDIVD between 1".($annoAttuale-1)."000 and 1".($annoAttuale-1)."366 then SDAEXP/100 else 0 end) as FATT2 FROM JRGDTA94C.F554211 where SDDOC<>0 and SDAN8 = ABAN8), 0) as FATT2, 
		coalesce((Select Sum(case when SDIVD between 1".($annoAttuale-2)."000 and 1".($annoAttuale-2)."366 then SDAEXP/100 else 0 end) as FATT3 FROM JRGDTA94C.F554211 where SDDOC<>0 and SDAN8 = ABAN8), 0) as FATT3    
		FROM JRGDTA94C.F0101 
		inner join JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 		
		JOIN BCD_DATIV2.CLICRD0F ON CCAN8 = ABAN8 
		) AS T  
		WHERE T.ABAT1 IN ('C', 'CS') 
		FETCH FIRST 1000 ROWS ONLY
		
		";
		/*
		if($GIMIFA!=0) {
			$selString .= " 
			where T.FATT1 > ".$GIMIFA." 
			OR T.FATT2 > ".$GIMIFA." 
			OR T.FATT3 > ".$GIMIFA." 
			
			";	
		}
		*/		
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{
				$escapedField = xl_fieldEscape($key);
				$$escapedField = trim($row[$key]);
			}
			
  			$fatt1 = round($FATT1);
  			$fatt2 = round($FATT2);
  			$fatt3 = round($FATT3);	
			
			$this->writeSegment("segAddMarker", array_merge(get_object_vars($this), get_defined_vars()));
		}
	}

	protected function decodCliente($ABAN8) {
		$ABAN8 = (int) $ABAN8;
		
		$query = "SELECT ABAN8, ABALPH, 
		ALADD2, ALADD3, ALADD4, ALADDZ, 
		ALCTY1, ALCOUN, ALADDS, ALCTR, 
		CCLATI, CCLONG, ABAT1  
		FROM JRGDTA94C.F0101 
		inner join JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 
		inner join BCD_DATIV2.CLICRD0F ON CCAN8 = ABAN8 
		WHERE ABAN8 = ".$ABAN8."
		";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row;
	}

	protected function newGiroV() {
		$GTPROG = $_SESSION["GTPROG"]; 
		$query = 'DELETE FROM BCD_DATIV2.RGPGIT00F WHERE GTPROG='.$GTPROG.' WITH NC';
		$res = $this->db_connection->exec($query); 		

		$this->getTmpProgre();
		header("Location: mappa-clienti.php");
	}

	protected function calcFatturato($SDAN8) {
		
		$annoAttuale = (int) substr(date("Y"),2,2); //(int) substr(date("Y"),2,2);
		
		$query = "
		Select Sum(case when SDIVD between 1".$annoAttuale."000 and 1".$annoAttuale."366 then SDAEXP/100 else 0 end) as FATT1,
		Sum(case when SDIVD between 1".($annoAttuale-1)."000 and 1".($annoAttuale-1)."366 then SDAEXP/100 else 0 end) as FATT2,
		Sum(case when SDIVD between 1".($annoAttuale-2)."000 and 1".($annoAttuale-2)."366 then SDAEXP/100 else 0 end) as FATT3   
		from JRGDTA94C.F554211 
		where SDDOC<>0
		and SDAN8 = ".$SDAN8;
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row;
	}

	protected function getTmpProgre() {
		$GTPROG = 0;
		$query = "SELECT MAX(GTPROG) AS	GTPROG FROM BCD_DATIV2.RGPGIT00F";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row) $GTPROG = $row["GTPROG"];
		$GTPROG++;
		$_SESSION["GTPROG"] = $GTPROG;
		return $GTPROG;
	} 

	protected function addGiro() {
	 
		$ABAN8 = xl_get_parameter("ABAN8");
		$GTPROG = $_SESSION["GTPROG"];
		
		$query = "SELECT 'S' AS CHKINS FROM BCD_DATIV2.RGPGIT00F WHERE GTPROG=".$GTPROG." AND GTCLIE = ".$ABAN8." FETCH FIRST ROW ONLY";
 		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row) {
			$CHKINS = $row["CHKINS"]; 
			if($CHKINS=="S") { 
				return;	
			}		
		}
		
		$GTCLIE = $ABAN8;
		$GTUSER = "";
		$GTDAIN = date("Ymd");
		$GTORIN = date("His");
		$query = "INSERT INTO BCD_DATIV2.RGPGIT00F(GTPROG,GTCLIE,GTUSER,GTDAIN,GTORIN) VALUES (:GTPROG,:GTCLIE,:GTUSER,:GTDAIN,:GTORIN) WITH NC";
		$stmt = $this->db_connection->prepare($query);
		$stmt->bindValue(':GTPROG', $GTPROG, PDO::PARAM_INT);
		$stmt->bindValue(':GTCLIE', $GTCLIE, PDO::PARAM_INT);
		$stmt->bindValue(':GTUSER', $GTUSER, PDO::PARAM_STR);
		$stmt->bindValue(':GTDAIN', $GTDAIN, PDO::PARAM_INT);
		$stmt->bindValue(':GTORIN', $GTORIN, PDO::PARAM_INT);
		$result = $stmt->execute();
				
	}
	 
	protected function lstGiro() {
		
		$GTPROG = $_SESSION["GTPROG"];
		
		echo "<table class=\"table table-condensed\">";
		
		$selstring = "SELECT GTCLIE, ABALPH  
		FROM BCD_DATIV2.RGPGIT00F 
		JOIN JRGDTA94C.F0101 ON GTCLIE = ABAN8  
		WHERE GTPROG = ".$GTPROG;	
		$stmt = $this->db_connection->prepare($selstring);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$this->writeSegment("dGiro", array_merge(get_object_vars($this), get_defined_vars()));
		}
		
		echo "</table>";
		   
	}
	
	
	protected function dltGiro() {
	
		$GTCLIE = (int) xl_get_parameter("ABAN8");
		$GTPROG = $_SESSION["GTPROG"];
		
		$query = "DELETE FROM BCD_DATIV2.RGPGIT00F WHERE GTCLIE = ".$GTCLIE." AND GTPROG=".$GTPROG." WITH NC";
		$res = $this->db_connection->exec($query); 		

	}	 
	 
	 
	protected function addArrivo() {
	
		$this->writeSegment("formArrivo", array_merge(get_object_vars($this), get_defined_vars()));
	}

	protected function addArrivo1() {
	 
		$ARRUTE = "";
		$ARRIND = xl_get_parameter("ARRIND");
		$ARRPRO = xl_get_parameter("ARRPRO");
		$ARRNAZ = xl_get_parameter("ARRNAZ");
		$ARRLOC = xl_get_parameter("ARRLOC");
	
		$errormsg = "";	
		$errorsep = "";
		if(trim($ARRPRO)=="") {
			$errormsg = trim($errormsg).trim($errorsep)."{\"stat\":\"err\", \"id\":\"ARRPRO\", \"msg\":\"Campo obbligatorio\"}";	
			$errorsep = ",";
		}
		/*
		if(trim($ARRIND)=="") {
			$errormsg = trim($errormsg).trim($errorsep)."{\"stat\":\"err\", \"id\":\"ARRIND\", \"msg\":\"Campo obbligatorio\"}";	
			$errorsep = ",";
		}
		if(trim($ARRLOC)=="") {
			$errormsg = trim($errormsg).trim($errorsep)."{\"stat\":\"err\", \"id\":\"ARRLOC\", \"msg\":\"Campo obbligatorio\"}";	
			$errorsep = ",";
		}
		*/
		if($errormsg<>"") { 
			echo "[".trim($errormsg)."]";	
			return;
		}
		 
		
		//Ricavo le coordinate
		$indirizzo = trim($ARRIND); 
		if($ARRLOC!="") $indirizzo .= ",".trim($ARRLOC);
		if($ARRPRO!="") $indirizzo .= ",".trim($ARRPRO); 
		if($ARRNAZ!="") $indirizzo .= ",".trim($ARRNAZ); 
		$indirizzo = urlencode($indirizzo); 
		
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".trim($indirizzo)."&sensor=false&key=".$this->googleBatchApiKey;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$curlResponse = curl_exec($ch); 
		//var_dump($curlResponse);  
		$geoloc = json_decode($curlResponse, true);
		if($geoloc['status']=='OK') { 
		  $ARRLAT = round($geoloc['results'][0]['geometry']['location']['lat'],7);
		  $ARRLNG = round($geoloc['results'][0]['geometry']['location']['lng'],7);
		  $CCSTAT = 'OK';
		} else {
		  $ARRLAT = 0;
		  $ARRLNG = 0;
		  $CCSTAT = $geoloc['status'];
		} 
		 
	 	if($CCSTAT<>"OK") {
	 		echo "[{\"stat\":\"ERR\",\"id\":\"ARRNAZ\",\"msg\":\"Indirizzo non trovato\"}]";	
	 		exit;	
	 	}  
	 	
		$ARRPRG = 0;
		$query = "SELECT MAX(ARRPRG) AS MARRPRG FROM BCD_DATIV2.RGPPAR00F ";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row && isset($row["MARRPRG"])) {
			 $ARRPRG = $row["MARRPRG"];
		}
		$ARRPRG++;
		 
		$insQuery = "INSERT INTO BCD_DATIV2.RGPPAR00F (ARSPRG,ARRPRG,ARRUTE,ARRIND,ARRPRO,ARRNAZ,ARRLOC,ARRLAT,ARRLNG) VALUES (:ARSPRG,:ARRPRG,:ARRUTE,:ARRIND,:ARRPRO,:ARRNAZ,:ARRLOC,:ARRLAT,:ARRLNG) WITH NC";
		$insStmt = $this->db_connection->prepare($insQuery); 
		$insStmt->bindValue(':ARSPRG', $_SESSION["GTPROG"], PDO::PARAM_INT);
		$insStmt->bindValue(':ARRPRG', $ARRPRG, PDO::PARAM_INT);
		$insStmt->bindValue(':ARRUTE', $ARRUTE, PDO::PARAM_STR);
		$insStmt->bindValue(':ARRIND', $ARRIND, PDO::PARAM_STR);
		$insStmt->bindValue(':ARRPRO', $ARRPRO, PDO::PARAM_STR);
		$insStmt->bindValue(':ARRNAZ', $ARRNAZ, PDO::PARAM_STR);
		$insStmt->bindValue(':ARRLOC', $ARRLOC, PDO::PARAM_STR);
		$insStmt->bindValue(':ARRLAT', $ARRLAT, PDO::PARAM_STR);
		$insStmt->bindValue(':ARRLNG', $ARRLNG, PDO::PARAM_STR); 
		$insResult = $insStmt->execute();  
		 
		echo "[{\"stat\":\"OK\"}]";	
		 	
	
	}

	protected function lstArrivi() {
 		
		echo "<table class=\"table table-striped table-condensed\">";
		
		$selstring = "SELECT RRN(RGPPAR00F) AS RRN_ARR, ARRPRG, ARRIND, ARRPRO, ARRNAZ, ARRLOC 
		FROM RGPPAR00F 
		WHERE ARSPRG = ".$_SESSION["GTPROG"]." 
		ORDER BY ARRPRG DESC
		";
		$stmt = $this->db_connection->prepare($selstring);
		$result = $stmt->execute(); 
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			 
			$this->writeSegment("dArrivo", array_merge(get_object_vars($this), get_defined_vars())); 
			 
			 
		}
		
		$this->writeSegment("cArrivo", array_merge(get_object_vars($this), get_defined_vars())); 
		echo "</table>";
		 
	}


	function dltArrivo() {
		$rrn_arr = (int) xl_get_parameter("rrn_arr");
		$dltQuery = "DELETE FROM RGPPAR00F WHERE RRN(RGPPAR00F) = ".$rrn_par." WITH NC";
		$res = $this->db_connection->exec($dltQuery); 		
	}	 
 
	protected function addPartenza() {
	
		$this->writeSegment("formPartenza", array_merge(get_object_vars($this), get_defined_vars()));
	}

	protected function addPartenza1() {
	 
		$PARUTE = "";
		$PARIND = xl_get_parameter("PARIND");
		$PARPRO = xl_get_parameter("PARPRO");
		$PARNAZ = xl_get_parameter("PARNAZ");
		$PARLOC = xl_get_parameter("PARLOC");
	
		$errormsg = "";	
		$errorsep = "";
		if(trim($PARPRO)=="") {
			$errormsg = trim($errormsg).trim($errorsep)."{\"stat\":\"err\", \"id\":\"PARPRO\", \"msg\":\"Campo obbligatorio\"}";	
			$errorsep = ",";
		}
		/*
		if(trim($PARIND)=="") {
			$errormsg = trim($errormsg).trim($errorsep)."{\"stat\":\"err\", \"id\":\"PARIND\", \"msg\":\"Campo obbligatorio\"}";	
			$errorsep = ",";
		}
		if(trim($PARLOC)=="") {
			$errormsg = trim($errormsg).trim($errorsep)."{\"stat\":\"err\", \"id\":\"PARLOC\", \"msg\":\"Campo obbligatorio\"}";	
			$errorsep = ",";
		}
		*/
		if($errormsg<>"") { 
			echo "[".trim($errormsg)."]";	
			return;
		}
		 
		
		//Ricavo le coordinate
		$indirizzo = trim($PARIND); 
		if($PARLOC!="") $indirizzo .= ",".trim($PARLOC);
		if($PARPRO!="") $indirizzo .= ",".trim($PARPRO); 
		if($PARNAZ!="") $indirizzo .= ",".trim($PARNAZ); 
		$indirizzo = urlencode($indirizzo); 
		
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".trim($indirizzo)."&sensor=false&key=".$this->googleBatchApiKey;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$curlResponse = curl_exec($ch); 
		//var_dump($curlResponse);  
		$geoloc = json_decode($curlResponse, true);
		if($geoloc['status']=='OK') { 
		  $PARLAT = round($geoloc['results'][0]['geometry']['location']['lat'],7);
		  $PARLNG = round($geoloc['results'][0]['geometry']['location']['lng'],7);
		  $CCSTAT = 'OK';
		} else {
		  $PARLAT = 0;
		  $PARLNG = 0;
		  $CCSTAT = $geoloc['status'];
		} 
		 
	 	if($CCSTAT<>"OK") {
	 		echo "[{\"stat\":\"ERR\",\"id\":\"PARNAZ\",\"msg\":\"Indirizzo non trovato\"}]";	
	 		exit;	
	 	}  
	 	
		$PARPRG = 0;
		$query = "SELECT MAX(PARPRG) AS MPARPRG FROM BCD_DATIV2.RGPPPA00F ";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row && isset($row["MPARPRG"])) {
			 $PARPRG = $row["MPARPRG"];
		}
		$PARPRG++;
		 
		$insQuery = "INSERT INTO BCD_DATIV2.RGPPPA00F (PASPRG,PARPRG,PARUTE,PARIND,PARPRO,PARNAZ,PARLOC,PARLAT,PARLNG) VALUES (:PASPRG,:PARPRG,:PARUTE,:PARIND,:PARPRO,:PARNAZ,:PARLOC,:PARLAT,:PARLNG) WITH NC";
		$insStmt = $this->db_connection->prepare($insQuery); 
		$insStmt->bindValue(':PASPRG', $_SESSION["GTPROG"], PDO::PARAM_INT);
		$insStmt->bindValue(':PARPRG', $PARPRG, PDO::PARAM_INT);
		$insStmt->bindValue(':PARUTE', $PARUTE, PDO::PARAM_STR);
		$insStmt->bindValue(':PARIND', $PARIND, PDO::PARAM_STR);
		$insStmt->bindValue(':PARPRO', $PARPRO, PDO::PARAM_STR);
		$insStmt->bindValue(':PARNAZ', $PARNAZ, PDO::PARAM_STR);
		$insStmt->bindValue(':PARLOC', $PARLOC, PDO::PARAM_STR);
		$insStmt->bindValue(':PARLAT', $PARLAT, PDO::PARAM_STR);
		$insStmt->bindValue(':PARLNG', $PARLNG, PDO::PARAM_STR); 
		$insResult = $insStmt->execute();  
		 
		echo "[{\"stat\":\"OK\"}]";	
		 	
	
	}

	protected function lstPartenze() {
 		
		echo "<table class=\"table table-striped table-condensed\">";
		
		$selstring = "SELECT RRN(RGPPPA00F) AS RRN_PAR, PARPRG, PARIND, PARPRO, PARNAZ, PARLOC 
		FROM RGPPPA00F 
		WHERE PASPRG = ".$_SESSION["GTPROG"]." 
		ORDER BY PARPRG DESC
		";
		$stmt = $this->db_connection->prepare($selstring);
		$result = $stmt->execute(); 
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			 
			$this->writeSegment("dPartenza", array_merge(get_object_vars($this), get_defined_vars())); 
			 
			 
		}
		
		$this->writeSegment("cPartenza", array_merge(get_object_vars($this), get_defined_vars())); 
		echo "</table>";
		 
	}


	function dltPartenza() {
		$rrn_par = (int) xl_get_parameter("rrn_par");
		$dltQuery = "DELETE FROM RGPPPA00F WHERE RRN(RGPPPA00F) = ".$rrn_par." WITH NC";
		$res = $this->db_connection->exec($dltQuery); 		
	}	 
 
	protected function lstSelPartenze() { 
 		$GTPROG = $_SESSION["GTPROG"];
		$CHKINS = "";
		
		$query = "SELECT 'S' AS CHKINS FROM BCD_DATIV2.RGPGIT00F WHERE GTPROG=".$GTPROG." FETCH FIRST ROW ONLY";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row) $CHKINS = $row["CHKINS"]; 
		
		if($CHKINS=="") {
			echo "Selezionare un cliente";
			return;	
		}
		
		echo "<select class=\"form-control\" name=\"GIPACL\" id=\"GIPACL\">";
		$query = "SELECT GTCLIE, ABALPH 
		FROM BCD_DATIV2.RGPGIT00F 
		JOIN JRGDTA94C.F0101 ON GTCLIE = ABAN8  
		WHERE GTPROG = ".$GTPROG;
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			echo "<option value=\"".$GTCLIE."\" ";
			//if($GIPACL==$GTCLIE) echo " selected=\"selected\" ";
			echo ">".$ABALPH."</option>";	
		}
		echo "</select>";	
	
	} 
	 
	protected function lstSelArrivi() { 
 		$GTPROG = $_SESSION["GTPROG"];
		$CHKINS = "";
		
		$query = "SELECT 'S' AS CHKINS FROM BCD_DATIV2.RGPGIT00F WHERE GTPROG=".$GTPROG." FETCH FIRST ROW ONLY";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row) $CHKINS = $row["CHKINS"]; 
		if($CHKINS=="") {
			echo "Selezionare un cliente";
			return;	
		}
		
		echo "<select class=\"form-control\" name=\"GIARCL\" id=\"GIARCL\">";
		$query = "SELECT GTCLIE, ABALPH 
		FROM BCD_DATIV2.RGPGIT00F 
		JOIN JRGDTA94C.F0101 ON GTCLIE = ABAN8  
		WHERE GTPROG = ".$GTPROG; 
		echo $query;
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}			
			
			echo "<option value=\"".$GTCLIE."\" ";
			//if($GIARCL==$GTCLIE) echo " selected=\"selected\" ";
			echo ">".$ABALPH."</option>";	
		}
		echo "</select>";	
	
	}
	 
	 
	protected function getCoords() {
	  
		header("Content-Type: application/json");
	  
		set_time_limit(120);
	 
		$selString = "SELECT JRGDTA94C.F0101.ABAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F0116.ALADD2, JRGDTA94C.F0116.ALADD3, 
		JRGDTA94C.F0116.ALADD4, JRGDTA94C.F0116.ALADDZ, JRGDTA94C.F0116.ALCTY1, JRGDTA94C.F0116.ALCOUN, JRGDTA94C.F0116.ALADDS, 
		JRGDTA94C.F0116.ALCTR 
		FROM JRGDTA94C.F0101 
		inner join JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 
		WHERE JRGDTA94C.F0101.ABAN8 NOT IN (SELECT CCAN8 FROM CLICRD0F) 
		AND JRGDTA94C.F0116.ALADD2 <> ''  
		AND ALCTR = 'IT' 
		FETCH FIRST 60 ROWS ONLY 
		";
		//  WHERE CCSTAT = 'OK'
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		$x = 0;
		 
		$resArray = array(); 
		
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{ 
			
			if($x>=60) break;
			 
			foreach(array_keys($row) as $key)
			{ 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = mb_convert_encoding($row[$key], 'UTF-8', 'ISO-8859-1');
			}
		  
			$indirizzo = trim($ALADD2); 
			if($ALCTY1!="") $indirizzo .= ",".trim($ALCTY1);
			if($ALADDS!="") $indirizzo .= ",".trim($ALADDS);
			if($ALCTR!="") $indirizzo .= ",".trim($ALCTR);
			$indirizzo = urlencode($indirizzo); 
			
			$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".trim($indirizzo)."&sensor=false&key=".$this->googleBatchApiKey;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			$curlResponse = curl_exec($ch); 
			//var_dump($curlResponse);  
			$geoloc = json_decode($curlResponse, true);
			if($geoloc['status']=='OK') { 
			  $CCLATI = round($geoloc['results'][0]['geometry']['location']['lat'],7);
			  $CCLONG = round($geoloc['results'][0]['geometry']['location']['lng'],7);
			  $CCSTAT = 'OK';
			} else {
			  $CCLATI = 0;
			  $CCLONG = 0;
			  $CCSTAT = $geoloc['status'];
			} 
			
			$CCAN8 = $ABAN8;
			$CCDTIN = date("Ymd");
			$CCORIN = date("His");
			$insQuery = "INSERT INTO BCD_DATIV2.CLICRD0F (CCAN8,CCLATI,CCLONG,CCSTAT,CCDTIN,CCORIN) VALUES(:CCAN8,:CCLATI,:CCLONG,:CCSTAT,:CCDTIN,:CCORIN) WITH NC";
			$insStmt = $this->db_connection->prepare($insQuery); 
			$insStmt->bindValue(':CCAN8', $CCAN8, PDO::PARAM_INT);
			$insStmt->bindValue(':CCLATI', $CCLATI, PDO::PARAM_STR);
			$insStmt->bindValue(':CCLONG', $CCLONG, PDO::PARAM_STR);
			$insStmt->bindValue(':CCSTAT', $CCSTAT, PDO::PARAM_STR);
			$insStmt->bindValue(':CCDTIN', $CCDTIN, PDO::PARAM_INT);
			$insStmt->bindValue(':CCORIN', $CCORIN, PDO::PARAM_INT); 
			$insResult = $insStmt->execute();  
			 
			usleep(500000);
			
			$resArray[$x]["AN"] = $CCAN8;
			$resArray[$x]["AD"] = $CCSTAT;
			$resArray[$x]["ST"] = $indirizzo;
			
			$x++;
		} 
		
		echo json_encode($resArray);
		
	}
	
	// Update the program state, and show the current page of entries
	protected function displayList()
	{
		// Update the program state
		$this->updateState();
		
		// Build current page of records
		$this->buildPage();
	}
	
	// Load list with filters
	protected function filterList()
	{
		// Retrieve the filter information
		
		$this->programState['filters']['ABAN8'] = trim(xl_get_parameter('filter_ABAN8'));
		$this->programState['filters']['ABALPH'] = xl_get_parameter('filter_ABALPH');
		$this->programState['filters']['ALCTY1'] = xl_get_parameter('filter_ALCTY1');
		$this->programState['filters']['ALCOUN'] = xl_get_parameter('filter_ALCOUN');
		$this->programState['filters']['ALADDS'] = xl_get_parameter('filter_ALADDS');
		$this->programState['filters']['ALCTR'] = xl_get_parameter('filter_ALCTR');
		$this->programState['filters']['filtGeo'] = xl_get_parameter('filter_filtGeo');
		$this->programState['filters']['ABAT1'] = xl_get_parameter('filter_ABAT1');
		
		$this->programState['filters']['ABAT1_C'] = xl_get_parameter('filter_ABAT1_C');
		$this->programState['filters']['ABAT1_CS'] = xl_get_parameter('filter_ABAT1_CS');
		$this->programState['filters']['ABAT1_P'] = xl_get_parameter('filter_ABAT1_P');
		$this->programState['filters']['ABAT1_PS'] = xl_get_parameter('filter_ABAT1_PS');
		$this->programState['filters']['ABAT1_V'] = xl_get_parameter('filter_ABAT1_V');
		
		 
		// Update the program state
		$this->updateState();
		
		// Display the list
		$this->buildPage();
	}
	
	// Build current page of rows up to listsize.
	protected function buildPage()
	{
		// Calculate the number of result pages
		$listSize = $this->programState['listSize'];
		
		$rowOffset = $this->getInitialOffset();
		$page = $this->programState['page'];
		
		// Fetch one past the current page, to determine if there is a next page
		$fetchLimit = $page * $listSize + 1;
		$rnd = rand(0, 99999);
		
		$previousPage = $page - 1;
		// Start $nextPage as 0 until we determine if there is a next page
		$nextPage = 0;
		
		// Output header
		$this->writeSegment('ListHeader', array_merge(get_object_vars($this), get_defined_vars()));
		
		// Create and execute the list Select statement
		$stmt = $this->buildListStmt($rowOffset, $fetchLimit);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Fetch the first row for page
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, $rowOffset);
		
		// Display each of the records, up to $listSize
		$rowCount = 0;
		while ($row && ($rowCount < $listSize))
		{
			// Set color of the row
			$this->xl_set_row_color('altcol1', 'altcol2');
			
			// Urlencode the key fields so we can use that form on the HTML output
			$ABAN8_url = urlencode(rtrim($row['ABAN8']));
			
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1"); 
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			// Output the row
			$this->writeSegment('ListDetails', array_merge(get_object_vars($this), get_defined_vars()));
			
			// Fetch the next row
			$rowCount++;
			$row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);
		}
		
		// If there is still a row defined set $nextPage to signify another page of results
		if ($row)
		{
			$nextPage = $page + 1;
		}
		
		// Show the footer
		$this->writeSegment('ListFooter', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	// Build the List statement
	protected function buildListStmt($rowOffset, $listSize)
	{
		// Build the query with parameters
		$selString = $this->buildSelectString();
		$selString .= ' ' . $this->buildWhereClause();
		$selString .= ' ' . $this->buildOrderBy();
		
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the filter parameters
		if ($this->programState['filters']['ABAN8'] != '')
		{
			$stmt->bindValue(':ABAN8', '%' . strtolower($this->programState['filters']['ABAN8']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['ABALPH'] != '')
		{
			$stmt->bindValue(':ABALPH', '%' . strtolower($this->programState['filters']['ABALPH']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['ALCTY1'] != '')
		{
			$stmt->bindValue(':ALCTY1', '%' . strtolower($this->programState['filters']['ALCTY1']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['ALCOUN'] != '')
		{
			$stmt->bindValue(':ALCOUN', '%' . strtolower($this->programState['filters']['ALCOUN']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['ALADDS'] != '')
		{
			$stmt->bindValue(':ALADDS', '%' . strtolower($this->programState['filters']['ALADDS']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['ALCTR'] != '')
		{
			$stmt->bindValue(':ALCTR', '%' . strtolower($this->programState['filters']['ALCTR']) . '%', PDO::PARAM_STR);
		}
	 
		if ($this->programState['filters']['ABAT1'] != '')
		{
			$stmt->bindValue(':ABAT1', $this->programState['filters']['ABAT1'], PDO::PARAM_STR);
		}
	 	
	 	
	 	
	 	
		
		
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildSelectString()
	{
		$selString = "SELECT JRGDTA94C.F0101.ABAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F0116.ALADD2, 
		JRGDTA94C.F0116.ALADD3, JRGDTA94C.F0116.ALADD4, JRGDTA94C.F0116.ALADDZ, JRGDTA94C.F0116.ALCTY1, 
		JRGDTA94C.F0116.ALCOUN, JRGDTA94C.F0116.ALADDS, JRGDTA94C.F0116.ALCTR, COALESCE(CCSTAT, '') AS CCSTAT, 
		JRGDTA94C.F0101.ABAT1  
		FROM JRGDTA94C.F0101 
		inner join JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 
		left join BCD_DATIV2.CLICRD0F on JRGDTA94C.F0101.ABAN8 = BCD_DATIV2.CLICRD0F.CCAN8 
		";
		//inner join BCD_DATIV2.CLICRD0F on JRGDTA94C.F0101.ABAN8 = BCD_DATIV2.CLICRD0F.CCAN8 AND CCSTAT = 'OK'
		
		
		return $selString;
	}
	
	// Build where clause to filter rows from table
	protected function buildWhereClause()
	{
		$whereClause = '';
		$link = 'WHERE ';
		
		// Filter by ABAN8
		if ($this->programState['filters']['ABAN8'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.F0101.ABAN8) LIKE :ABAN8';
			$link = ' AND ';
		}
		
		// Filter by ABALPH
		if ($this->programState['filters']['ABALPH'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.F0101.ABALPH) LIKE :ABALPH';
			$link = " AND ";
		}
		
		// Filter by ALCTY1
		if ($this->programState['filters']['ALCTY1'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.F0116.ALCTY1) LIKE :ALCTY1';
			$link = " AND ";
		}
		
		// Filter by ALCOUN
		if ($this->programState['filters']['ALCOUN'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.F0116.ALCOUN) LIKE :ALCOUN';
			$link = " AND ";
		}
		
		// Filter by ALADDS
		if ($this->programState['filters']['ALADDS'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.F0116.ALADDS) LIKE :ALADDS';
			$link = " AND ";
		}
		
		// Filter by ALCTR
		if ($this->programState['filters']['ALCTR'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.F0116.ALCTR) LIKE :ALCTR';
			$link = " AND ";
		}
		
		if ($this->programState['filters']['filtGeo'] == '2')
		{
			$whereClause = $whereClause . $link . ' (CCSTAT IS NULL OR CCSTAT <> \'OK\')';
			$link = " AND ";	
		}
		
		if ($this->programState['filters']['filtGeo'] == '1')
		{
			$whereClause = $whereClause . $link . ' (CCSTAT = \'OK\')';
			$link = " AND ";	
		}						
		
		// Filter by ABAT1
		if ($this->programState['filters']['ABAT1'] != '')
		{
			$whereClause = $whereClause . $link . ' JRGDTA94C.F0101.ABAT1 = :ABAT1';
			$link = " AND ";
		}		
		
 		$link2 = "";
		$whereClause = $whereClause . $link . ' (JRGDTA94C.F0101.ABAT1 = \'\' ';
		$link2 = "OR";
		if($this->programState['filters']['ABAT1_C'] == 'S') { $whereClause = $whereClause . $link2 . ' JRGDTA94C.F0101.ABAT1 = \'C\' '; $link2 = "OR"; }
		if($this->programState['filters']['ABAT1_CS'] == 'S') { $whereClause = $whereClause . $link2 . ' JRGDTA94C.F0101.ABAT1 = \'CS\' '; $link2 = "OR"; }
		if($this->programState['filters']['ABAT1_P'] == 'S') { $whereClause = $whereClause . $link2 . ' JRGDTA94C.F0101.ABAT1 = \'P\' '; $link2 = "OR"; }
		if($this->programState['filters']['ABAT1_PS'] == 'S') { $whereClause = $whereClause . $link2 . ' JRGDTA94C.F0101.ABAT1 = \'PS\' '; $link2 = "OR"; }
		if($this->programState['filters']['ABAT1_V'] == 'S') { $whereClause = $whereClause . $link2 . ' JRGDTA94C.F0101.ABAT1 = \'V\' '; $link2 = "OR"; }
		$whereClause = $whereClause . ') ';
		 
		return $whereClause;
	}
	

	// Build order by clause to order rows
	protected function buildOrderBy()
	{
		// Set sort order to programState's sort by and direction
		$orderBy = "ORDER BY " . $this->programState['sort'] . ' ' . $this->programState['sortDir'];
		
		return $orderBy;
	}
	
	// Compute the offset of the first record from the database to output
	protected function getInitialOffset()
	{
		$listSize = $this->programState['listSize'];
		$page = $this->programState['page'];
		$offset = (($page - 1) * $listSize) + 1;
		return $offset;
	}
	
	// Update the program state - How and what information we are displaying
	protected function updateState()
	{
		// If a column header was clicked, sort parameters will be provided.
		// Update the program state to sort that way from now on
		$sort = xl_get_parameter('sidx', 'db2_search');
		if ($sort != '')
		{
			// Reverse order if sorting by the same column
			if ($sort == $this->programState['sort'])
			{
				if ($this->programState['sortDir'] == 'asc')
				{
					$this->programState['sortDir'] = 'desc';
				}
				else
				{
					$this->programState['sortDir'] = 'asc';
				}
			}
			else
			{
				$this->programState['sortDir'] = 'asc';
			}
			$this->programState['sort'] = $sort;
		}
		
		// If no sort column is specified, use the unique keylist as the default
		if ($this->programState['sort'] == '')
		{
			// The sort order is build from the elements in $this->keyFields, if there are none then $this->uniqueFields will be used.
			$this->programState['sort'] = $this->getDefaultSort($this->keyFields, $this->uniqueFields);
			$this->programState['sortDir'] = 'asc';
		}
		// Get and save the current page if provided
		$page = (int) xl_get_parameter('page');
		if($page < 1)
		{
			$page = 1;
		}
		$this->programState['page'] = $page;
		
		// Get and save the list size, if provided
		$listSize = (int) xl_get_parameter('rows');
		if ($listSize > 0)
		{
			$this->programState['listSize'] = $listSize;
		}
		
		// Save the program state as a session variable
		$_SESSION[$this->pf_scriptname] = $this->programState;
	}
	
	// Output the last PDO error, and exit
	protected function dieWithPDOError($stmt = false)
	{
		if ($stmt)
		{
			$err = $stmt->errorInfo();
		}
		else
		{
			$err = $this->db_connection->errorInfo();
		}
		die('<b>Error #' . $err[1] . ' - ' . $err[2] . '</b>');
	}
 

	function writeSegment($xlSegmentToWrite, $segmentVars=array())
	{
		foreach($segmentVars as $arraykey=>$arrayvalue)
		{
			${$arraykey} = $arrayvalue;
		}
		// Make sure it's case insensitive
		$xlSegmentToWrite = strtolower($xlSegmentToWrite);

	// Output the requested segment:

	if($xlSegmentToWrite == "listheader")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="ISO-8859-1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Mappa clienti</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/js/jquery-ui.js"></script> 
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=
SEGDTA;
 echo $this->googleApiKey; 
		echo <<<SEGDTA
"></script>
        
	<style>
		#giroInnerContainer {
			height:550px;
			overflow-y:auto;	
		}
		.indClienteMarker {
			font-size:10px;
			font-style:italic;	
		}
		.markerAddr {
			font-size:9px;
			font-style:italic;	
		}
		#cliDropContainer {
			
		}
		.fieldsetGiro {
			padding:0px !important;
			padding-bottom:5px !important;
		}
		#divCliDrop {
			padding:10px;
			min-height:200px;
			background-color:#eeeeee;
			overflow-x:hidden;
			overflow-y:hidden;
			border:1px solid #ccc;
		}
		#divPartenze, #divArrivi {
			padding:10px;
		}
		.cliDrag {
			z-index:200; 	
		}
		#divGiriSalvati {
			height:150px;
			overflow-x: hidden;
			overflow-y: auto;	
		}
		.smart-style-5 #GIPACL, .smart-style-5 #GIARCL {
			color: #000 !important;
		}
		#giroContainer {
			background-color: white;
		}
		
		.checkbox-inline, .radio-inline { 
		  display: inline-block !important; 
		}
		
		.err {
			color:red;	
		}		
	</style>
    
    
  </head>
  <body class="display-list"> 
  
SEGDTA;

  	$this->writeSegment("ricCoord");
  	$this->writeSegment("modalArrivo");
  	$this->writeSegment("modalPartenza");
  
		echo <<<SEGDTA

  
  
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Mappa clienti</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <!-- Form containing filter inputs -->
          <form id="filter-form" class="container-fluid" method="post" action="$pf_scriptname">
            <input type="hidden" name="task" value="filter" />
            <div class="form">
              <div class="row">
                <!--
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ABAT1">Search Type</label>
                  <select class="form-control" name="filter_ABAT1" id="filter_ABAT1">
                  	<option value=""></option>
                  	<option value="C" 
SEGDTA;
 echo (($this->programState['filters']['ABAT1']=="C")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Cliente</option>
                  	<option value="CS" 
SEGDTA;
 echo (($this->programState['filters']['ABAT1']=="CS")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Indirizzo di spedizione</option>
                  	<option value="P" 
SEGDTA;
 echo (($this->programState['filters']['ABAT1']=="P")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Prospect</option>
                  	<option value="PS" 
SEGDTA;
 echo (($this->programState['filters']['ABAT1']=="PS")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Indirizzo di spedizione prospect</option>
                  	<option value="V" 
SEGDTA;
 echo (($this->programState['filters']['ABAT1']=="V")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Fornitore</option>
                  </select>
                </div>
                -->
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ABAN8">Codice cliente</label>
                  <input id="filter_ABAN8" class="form-control" type="text" name="filter_ABAN8" maxlength="8" value="{$programState['filters']['ABAN8']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ABALPH">Nome cliente</label>
                  <input id="filter_ABALPH" class="form-control" type="text" name="filter_ABALPH" maxlength="40" value="{$programState['filters']['ABALPH']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ALCTY1">City</label>
                  <input id="filter_ALCTY1" class="form-control" type="text" name="filter_ALCTY1" maxlength="25" value="{$programState['filters']['ALCTY1']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ALCOUN">County</label>
                  <input id="filter_ALCOUN" class="form-control" type="text" name="filter_ALCOUN" maxlength="25" value="{$programState['filters']['ALCOUN']}"/>
                </div>
                <div class="filter-group form-group col-sm-3 col-lg-1">
                  <label for="filter_ALADDS">State</label>
                  <input id="filter_ALADDS" class="form-control" type="text" name="filter_ALADDS" maxlength="3" value="{$programState['filters']['ALADDS']}"/>
                </div>
                <div class="filter-group form-group col-sm-3 col-lg-1">
                  <label for="filter_ALCTR">Country</label>
                  
SEGDTA;
 $this->lstNazioni(); 
		echo <<<SEGDTA

                  <!--<input id="filter_ALCTR" class="form-control" type="text" name="filter_ALCTR" maxlength="3" value="{$programState['filters']['ALCTR']}"/>-->
                </div>
                
                <div class="filter-group form-group col-sm-6 col-lg-4">
                  <label></label>
				  <div class="col-md-10">
						<label class="radio-inline">
							  <input type="radio" name="filter_filtGeo" value="1" 
SEGDTA;
 if($this->programState['filters']['filtGeo']=="1") echo 'checked'; 
		echo <<<SEGDTA
>
							  <span>Solo geolocalizzati </span>
						</label>
						<label class="radio-inline">
							  <input type="radio" name="filter_filtGeo" value="2" 
SEGDTA;
 if($this->programState['filters']['filtGeo']=="2") echo 'checked'; 
		echo <<<SEGDTA
>
							  <span>Solo non geolocalizzati </span>
						</label>				  
						<label class="radio-inline">
							  <input type="radio" name="filter_filtGeo" value="" 
SEGDTA;
 if($this->programState['filters']['filtGeo']=="") echo 'checked'; 
		echo <<<SEGDTA
>
							  <span>Tutti </span>
						</label> 
				  </div>	
				</div>
				 
                <div class="filter-group form-group col-sm-12 col-lg-6">
                  <label for="filter_ABAT1"></label>
				  <div class="col-md-10">
						<label class="checkbox-inline">
							  <input type="checkbox" class="checkbox" name="filter_ABAT1_C" value="S" 
SEGDTA;
 if($this->programState['filters']['ABAT1_C']=="S") echo 'checked'; 
		echo <<<SEGDTA
>
							  <span>Cliente</span>
						</label>
						<label class="checkbox-inline">
							  <input type="checkbox" class="checkbox" name="filter_ABAT1_CS" value="S" 
SEGDTA;
 if($this->programState['filters']['ABAT1_CS']=="S") echo 'checked'; 
		echo <<<SEGDTA
> 
							  <span>Cliente (Ind. Sped.)</span>
						</label>
						<label class="checkbox-inline">
							  <input type="checkbox" class="checkbox" name="filter_ABAT1_P" value="S" 
SEGDTA;
 if($this->programState['filters']['ABAT1_P']=="S") echo 'checked'; 
		echo <<<SEGDTA
> 
							  <span>Prospect</span>
						</label>
						<label class="checkbox-inline">
							  <input type="checkbox" class="checkbox" name="filter_ABAT1_PS" value="S" 
SEGDTA;
 if($this->programState['filters']['ABAT1_PS']=="S") echo 'checked'; 
		echo <<<SEGDTA
> 
							  <span>Prospect (Ind. Sped.)</span>
						</label>
						<label class="checkbox-inline">
							  <input type="checkbox" class="checkbox" name="filter_ABAT1_V" value="S" 
SEGDTA;
 if($this->programState['filters']['ABAT1_V']=="S") echo 'checked'; 
		echo <<<SEGDTA
> 
							  <span>Fornitore</span>
						</label>				  
					</div> 
                </div>		 
                 
                </div>
              </div>
              <div class="row">
                <div class="col-sm-2">
                  <input id="filter-button" class="btn btn-primary filter" type="submit" value="Cerca" />
                </div>
              </div>
            </div>
          </form>
          <!-- End form containing filter inputs -->
          
          <div class="col-sm-8"> 
	          
	          <span id="list-paging-top" class="list-paging">
	            <a id="previous-link-top" class="list-paging previous hidden" href="#">
	              <span class="glyphicon glyphicon-triangle-left" style="text-decoration: none; font-size: 0.8em"></span> <span>Previous</span>
	            </a>
	            <a id="next-link-top" class="list-paging next hidden" href="#">
	              <span>Next $listSize</span> <span class="glyphicon glyphicon-triangle-right" style="text-decoration: none; font-size: 0.8em"></span>
	            </a>
	          </span>
	          <div class="clearfix"></div>
	          <table id="list-table" class="main-list table table-striped table-condensed table-bordered" cellspacing="0">
	            <thead>
	              <tr class="list-header">
	                <th></th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ABAN8&amp;rnd=$rnd">Codice cliente</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ABAT1&amp;rnd=$rnd">Search Type</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ABALPH&amp;rnd=$rnd">Nome cliente</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALADD2&amp;rnd=$rnd">Address</a>
	                </th> 
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALADDZ&amp;rnd=$rnd">Postal Code</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALCTY1&amp;rnd=$rnd">City</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALCOUN&amp;rnd=$rnd">County</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALADDS&amp;rnd=$rnd">State</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALCTR&amp;rnd=$rnd">Country</a>
	                </th>
	              </tr>
	            </thead>
	            <tbody>
            
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "listdetails")
	{

		echo <<<SEGDTA
 <tr>
  <td nowrap> 
 	  <a style="
SEGDTA;
 if($CCSTAT!="OK") echo 'display:none'; 
		echo <<<SEGDTA
" class="btn btn-xs btn-success cliDrag glyphicon glyphicon-plus" ABAN8="$ABAN8" title="Click per aggiungere"></a>
 	  <a style="
SEGDTA;
 if($CCSTAT=="OK") echo 'display:none'; 
		echo <<<SEGDTA
" class="btn btn-xs btn-danger glyphicon glyphicon-question-sign" onclick="ricCoord('$ABAN8');" title="Ricava coordinate"></a>
  </td>
  <td class="text num">$ABAN8</td>
  <td>
  
SEGDTA;

  	if($ABAT1=="C") echo 'Cliente';
  	if($ABAT1=="CS") echo 'Indirizzo spedizione';
  	if($ABAT1=="P") echo 'Prospect';
  	if($ABAT1=="PS") echo 'Indirizzo spedizione Prospect';
  	if($ABAT1=="V") echo 'Fornitore';
  
		echo <<<SEGDTA
 
  </td>
  <td class="text">$ABALPH</td>
  <td class="text">$ALADD2 $ALADD3 $ALADD4</td>
  <td class="text">$ALADDZ</td>
  <td class="text">$ALCTY1</td>
  <td class="text">$ALCOUN</td>
  <td class="text">$ALADDS</td>
  <td class="text">$ALCTR</td>
</tr>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "listfooter")
	{

		echo <<<SEGDTA
	</table>
	<span id="list-paging-bottom" class="list-paging">
	  <a id="previous-link-bottom" class="list-paging previous hidden" href="#">
	    <span class="glyphicon glyphicon-triangle-left" style="text-decoration: none; font-size: 0.8em"></span> <span>Previous</span>
	  </a>
	  <a id="next-link-bottom" class="list-paging next hidden" href="#">
	    <span>Next $listSize</span> <span class="glyphicon glyphicon-triangle-right" style="text-decoration: none; font-size: 0.8em"></span>
	  </a>
	</span>
	</div>
	</div>
 
</div> <!-- col-sm-6 [f]-->
<div class="col-sm-4" id="giroContainer"> 
	<header>
		<span class="widget-icon"> <i class="fa fa-align-justify"></i> </span>
		<h4>Giro Visite</h4> 
	</header>
 	<div class="row"> 
		<div class="cliDrop" id="divCliDrop"></div> 
	</div>
	
	<form name="formGiroComposer" id="formGiroComposer" class="smart-form" method="post" action="mappa-clienti.php">
	<input type="hidden" name="task" id="formGiroTask" value="calcGiroV" />
 
 	<div class="row"> 
		<h5><strong>Punto di partenza</strong></h5>
		<div style="text-align:right;padding-top:5px;"><input type="button" class="btn btn-xs btn-info" onclick="addPartenza();" value="Utilizza un indirizzo"/></div>
		<div id="divPartenze">
		</div> 
	</div>
	
	<div class="row">
		<h5><strong>Punto di arrivo</strong></h5>
		<div style="text-align:right;padding-top:5px;"><input type="button" class="btn btn-xs btn-info" onclick="addArrivo();" value="Utilizza un indirizzo"/></div>
		<div id="divArrivi">
		</div> 
	</div>
  	 
    <div class="row">
          <div class="form-group">
            <label for="GINOPE">&nbsp;</label>
			<div class="checkbox">
				<label>
				  <input type="checkbox" id="GINOPE" name="GINOPE" class="checkbox" value="S">
				  <span>Evita strade a pedaggio</span>
				</label>
			</div> 
          </div> 
     </div>   
      
     <div class="row">
          <div class="form-group">
            <label for="GITIPO">Calcola percoso ottimale:</label> 
			<select class="form-control" name="GITIPO">
				<option value="time">Pi&ugrave; veloce</option>
				<option value="distance">Pi&ugrave; breve</option>
			</select> 
          </div> 
     </div> 
     
     <div class="row">
          <div class="form-group">
            <label for="GITPCL">Visualizza:</label> 
			<div class="checkbox">
				<label>
				  <input type="checkbox" name="GITPCL_C" id="GITPCL_C" value="S" checked>
				  <span>Clienti</span>
				</label>
			</div>  
			<div class="checkbox">
				<label>
				  <input type="checkbox" name="GITPCL_CS" id="GITPCL_CS" value="S">
				  <span>Clienti (ind. spedizione)</span>
				</label>
			</div>              
			<div class="checkbox">
				<label>
				  <input type="checkbox" name="GITPCL_P" id="GITPCL_P" value="S">
				  <span>Prospect</span>
				</label>
			</div> 
			<div class="checkbox">
				<label>
				  <input type="checkbox" name="GITPCL_PS" id="GITPCL_PS" value="S">
				  <span>Prospect (ind. spedizione)</span>
				</label>
			</div> 
			<div class="checkbox">
				<label>
				  <input type="checkbox" name="GITPCL_V" id="GITPCL_V" value="S">
				  <span>Fornitori</span>
				</label>
			</div> 
          </div> 
     </div>      
     
     <div class="row">
          <div class="form-group">
            <label for="GITPCL">Raggio di ricerca (Km):</label> 
            <select class="form-control" name="GIRARI" id="GIRARI">
            	<option value="5">5</option>
            	<option value="10" selected>10</option>
            	<option value="15">15</option>
            	<option value="20">20</option>
            	<option value="25">25</option>
            	<option value="30">30</option>
            	<option value="35">35</option>
            	<option value="40">40</option>
            	<option value="45">45</option>
            	<option value="50">50</option>
            	<option value="100">100</option>
            	<option value="200">200</option>
            	<option value="500">500</option>
            </select>
			<!--<input type="text" class="form-control" name="GIRARI" id="GIRARI" value="2" />-->
          </div> 
     </div>  
     
     <div class="row">
          <div class="form-group">
            <label for="GIMIFA">Escludi clienti con fatturato inferiore a &euro;:</label> 
			<input type="text" name="GIMIFA" id="GIMIFA" class="form-control positive-integer" maxlength="8" value="1" />
          </div> 
     </div>  
     
     <div class="row">
          <div class="form-group">
            <label for="GIVITU">&nbsp;</label> 
			<div class="checkbox">
				<label>
				  <input type="checkbox" name="GIVITU" id="GIVITU" value="S">
				  <span>Visualizza tutti i clienti</span>
				</label>
			</div>   
          </div> 
     </div>           
      
    <input type="button" class="btn btn-xs btn-primary" onclick="sbmCalcGiro();" value="Visualizza mappa"/>
 	<input type="button" class="btn btn-xs btn-primary" onclick="document.location.href='?task=newGiroV'" value="Reset scelte"/>&nbsp;
  
	</form>	
</div>

</div>

<script src="/crud/websmart/v13.2/js/jquery.maskedinput.min.js"></script>  
<script src="/crud/websmart/v13.2/js/jquery.numeric.js"></script>   
<script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
<script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     

<!-- Supporting JavaScript -->
<script type="text/javascript">
	// Focus the first input on page load
	jQuery(function() {
		jQuery("input:enabled:first").focus();
		 			
		
	});
	
	jQuery(".previous").attr("href", "$pf_scriptname?page=$previousPage&amp;rnd=$rnd");
	jQuery(".next").attr("href", "$pf_scriptname?page=$nextPage&amp;rnd=$rnd");
	
	// Show the PREV link if necessary
	if ($previousPage > 0) 
	{
		jQuery(".previous").removeClass("hidden");
	}
	// Show the NEXT link if necessary
	if ($nextPage > 0)
	{
		jQuery(".next").removeClass("hidden");
	}
  
</script>

<script type="text/javascript">
	$(document).ready(function() {
		$('.cliDrag').click(function(event){
		  	jABAN8 = $(this).attr('ABAN8');
			addGiro(jABAN8);
		});
	
        $('.positive-integer').numeric({
            decimal: false, 
            negative: false
        });		
 
		lstGiro();
		lstPartenze(); 
		lstArrivi();
		
	});
	
	function ricCoord(jABAN8) {
		url = "?task=ricCoord&ABAN8="+jABAN8;
		$.getJSON(url,function(data){
			$("#rc_ABAN8").val(jABAN8);	
			$("#rc_ALADD2").val(data.ALADD2);	
			$("#rc_ALCTY1").val(data.ALCTY1);	
			$("#rc_ALADDS").val(data.ALADDS);	
			$("#rc_ALCTR").val(data.ALCTR);	
			
			$("#rc_CCLATI").val("");	
			$("#rc_CCLONG").val("");	 
			
			$('#modal-register-form').modal('show');
		}); 
	}	
	
	function calcCoord() {
		var geocoder = new google.maps.Geocoder();
	
		var address = $('#rc_ALADD2').val()+" "+$('#rc_ALCTY1').val()+" "+$('#rc_ALADDS').val()+" "+$('#rc_ALCTR').val();
			 
		var lat = 0;
		var lng = 0;
		geocoder.geocode({'address': address}, function(results, status) {
		  if (status === 'OK') {
		 
		    lat = results[0].geometry.location.lat();
		    lng = results[0].geometry.location.lng();
		    
		    lat = +lat.toFixed(7);
		    lng = +lng.toFixed(7);
		    
	    	$("#rc_CCLATI").val(lat);	
	    	$("#rc_CCLONG").val(lng);	
		    
		  } else {
		    alert('Geocode was not successful for the following reason: ' + status);
		    
	    	$("#rc_CCLATI").val("0");	
	    	$("#rc_CCLONG").val("0");	
		    	
		  }
		});
		
		
	}
	
	function handleDropEvent( event, ui ) 
	{
		draggable = ui.draggable;
		jABAN8 = ui.draggable.attr('ABAN8');
		addGiro(jABAN8);
		
	}

	function addGiro(jABAN8) {
		url = "?task=addGiro&ABAN8="+jABAN8+"";
		$.get(url,function(data){
			lstGiro();
			lstSelArrivi();
			lstSelPartenze();
		});	
	}
	
	function dltGiro(jABAN8) {
		url = "?task=dltGiro&ABAN8="+jABAN8+"";
		$.get(url,function(data){
			lstGiro();
			lstSelArrivi();
			lstSelPartenze();
		});				
	}
 
	function lstGiro() {
		url = "?task=lstGiro";
		$.get(url,function(data){
			$("#divCliDrop").html(data);
		});
	}
    
	function lstPartenze() {
		url = "?task=lstPartenze";
		$.get(url,function(data){
			$("#divPartenze").html(data); 
			lstSelPartenze();
		});	
	}	    
    
	function lstSelPartenze() {
		url = "?task=lstSelPartenze";
		$.get(url,function(data){
			$("#divClientiPartenza").html(data);
		});				
	}
	 
	function dltPartenza(jrrn_par) {
		url = "?task=dltPartenza&rrn_par="+jrrn_par;
		$.get(url,function(data){
			lstPartenze();
		});				
	}	 
	 
	function lstArrivi() {
		url = "?task=lstArrivi";
		$.get(url,function(data){
			$("#divArrivi").html(data); 
			lstSelArrivi();
		});	
	}	 
	 
	function lstSelArrivi() {
		url = "?task=lstSelArrivi";
		$.get(url,function(data){
			$("#divClientiArrivo").html(data);
		});		
	}
	
	function dltArrivo(jrrn_arr) {
		url = "?task=dltArrivo&rrn_arr="+jrrn_arr+"";
		$.get(url,function(data){
			lstArrivi();
		});				
	}
  
 	function calcGiroV(jrrn_gir) {
 		url = "?task=loadGiroV1&rrn_gir="+jrrn_gir+"";
 		$.get(url,function(data){
 			document.location.href="?task=calcGiroV";
 		});	
 	}
 	
 	function dltGiroV(jrrn_gir) {
 		url = "?task=dltGiroV&rrn_gir="+jrrn_gir+"";
 		$.get(url,function(data){
 			loadGiroV();	
 		});	
 	}
 	
 	function sbmCalcGiro() {
 		
 		jGITIPA = $('input[name=GITIPA]:checked').val();
 		jGIPACL = $("#GIPACL").val();
 		jGITIAR = $('input[name=GITIAR]:checked').val();
 		jGIARCL = $("#GIARCL").val();
 		  
		if(jGITIPA=="" || jGITIPA==null || (jGIPACL=="" && jGITIPA=="cliente") || jGITIAR=="" || jGITIAR==null || (jGIARCL=="" && jGITIAR=="cliente") 
		
		) {
			alert("Selezionare un punto di arrivo e un punto di partenza");	
			return;	
		}	
		 
		if($("#GIVITU").is(":checked") && ($("#GIMIFA").val()==0 || $("#GIMIFA").val()=="")) {
			alert("Per visualizzare tutti i clienti, impostare un limite di fatturato");	
			return;	
		}
		 
		if(!$("#GITPCL_C").is(":checked") 
		&& !$("#GITPCL_CS").is(":checked") 
		&& !$("#GITPCL_P").is(":checked") 
		&& !$("#GITPCL_PS").is(":checked") 
		&& !$("#GITPCL_V").is(":checked")) {
			alert("Selezionare almeno una tipologia di cliente da visualizzare");	
			return;	
		} 
		
		
		$("#formGiroComposer").submit();
		
		
 	}
 	 
	function addArrivo() { 
		url = "?task=addArrivo";
		$("#modal-arrivo-body").load(url,function(data){
			var options = { 
				dataType:  'json',
		        success: showResponseArrivo
		    }; 
		    $('#arrivoForm').ajaxForm(options); 
		    
		    $("#modal-arrivo").modal('show');
		    
		});	
	}

	function showResponseArrivo(responseData, statusText, xhr, _form) {
		
		$.unblockUI();
		$(".error").html(''); 
		if(responseData[0].stat!="OK") {
			for(i=0;i<responseData.length;i++) { 
				$("#err_"+responseData[i].id).html(responseData[i].msg);	 
			}	
			return;
		}
		 
		$("#modal-arrivo").modal('hide');
		lstArrivi();			
		  
	} 	
 	
 	
	function addPartenza() { 
		url = "?task=addPartenza";
		$("#modal-partenza-body").load(url,function(data){
			var options = { 
				dataType:  'json',
		        success: showResponsePartenza
		    }; 
		    $('#partenzaForm').ajaxForm(options); 
		    
		    $("#modal-partenza").modal('show');
		    
		});	
	}

	function showResponsePartenza(responseData, statusText, xhr, _form) {
		
		$.unblockUI();
		$(".error").html(''); 
		if(responseData[0].stat!="OK") {
			for(i=0;i<responseData.length;i++) { 
				$("#err_"+responseData[i].id).html(responseData[i].msg);	 
			}
			return;	
		}
		 
		$("#modal-partenza").modal('hide');
		lstPartenze();			
		  
	} 	
 	
 	
</script> 


</body>
</html>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "rtntolist")
	{

		echo <<<SEGDTA

<button class="btn btn-default cancel">Back</button>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dgiro")
	{

		echo <<<SEGDTA
<tr>
<td width="10" nowrap> 
	  <a class="btn btn-xs btn-danger glyphicon glyphicon-remove" onclick="dltGiro('$GTCLIE')"></a> 
</td>
<td>$ABALPH</td>
<td></td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "segmappa")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="ISO-8859-1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Mappa clienti</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
     
	<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=
SEGDTA;
 echo $this->googleApiKey; 
		echo <<<SEGDTA
"></script>
	<script type="text/javascript" src="/crud/websmart/maps/BpTspSolver.js"></script> 
	<script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
	<script src="/crud/websmart/maps/RouteBoxer.js" type="text/javascript"></script>
	<script src="/crud/websmart/maps/markerwithlabel.js" type="text/javascript"></script>
     
     
	<style>
	@media print {
		#navbar, #sidebar, #breadcrumbs, .page-header, #ace-settings-container, input {
			display:none !important;
		}
		#main-content {
			margin-left:0px;	
		}
		#leftPanel, #centerPanel {
			display:none;	
		}
		#rightPanel {
			width:100%;
		}
		#directionsPanel { 
			height:auto;
			overflow-y:none;
		}
	}
	
	@media screen {
		#map_canvas {
			height:600px;
			width:100%;	
		}
		#directionsPanel {
			height:555px;
			overflow-y:auto;	
		}
		.markerTappaDivStart {
		    background-image: url("/crud/websmart/maps/img/start_point_icon.png");
		} 
		.markerTappaDiv {
		    
		} 
	    .markerTappaDivStart, .markerTappaDiv {
		    background-repeat: no-repeat;
		    font-weight: bold;
		    height: 18px;
		    padding-left: 4px;
		    vertical-align: top;
		    width: 18px;
		    color:black;
		    border-radius: 9px;
		}
	}
	
	.smart-style-5 .gm-style-iw-d div {
		color: #000 !important;	
	}
	.smart-style-5 .maplabel {
		color: #000 !important; 
	}
	.maplabel { 
		background-color: white !important;	
		display: none !important;
	}	
	#inBoundPanelDiv {
		max-height: 553px;
		overflow-y: auto;	
	}
	/*
	.smart-style-5 .adp-summary .adp-directions {
		color: #fff !important;		
	}
	*/
	</style> 
	 

  </head>
  <body class="display-list">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Mappa clienti</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
        
			<input type="button" value="Indietro" class="btn btn-xs btn-primary" onclick="document.location.href='?task=default'" />&nbsp;
			<!--<input type="button" value="Stampa" class="btn btn-xs btn-success" onclick="window.print();" />&nbsp;-->
			
			<input type="button" value="Esporta mappa" class="btn btn-xs btn-primary" onclick="getRouteLatLngs();" />
			
			
        	<br><br>
        	<div class="row">
	        	<div class="col-sm-12">
			    	<div class="col-sm-2" id="centerPanel">
						
				    	<div id="stepReport"></div>
					
			    		<br>
			    		<table class="tabRiepilogo table">
				    		<tr>
				    			<td >Durata totale:</td>
				    			<td id="totalTravelDuration"></td>
				    		</tr> 
				    		<!--
				    		<tr>
				    			<td >Durata totale:</td>
				    			<td id="totalDuration"></td>
				    		</tr>
				    		--> 	    		
				    		<tr>
				    			<td >Distanza totale:</td>
				    			<td id="totalTravelDistance"></td>
				    		</tr>
				    	</table>

			    	</div>
			    	
		        	<div class="col-sm-6" id="leftPanel">
				    	<div id="map_canvas"></div> 
				    	<div id="divMapErr"> </div>  
			    	</div>

			    	<div class="col-sm-4" id="rightPanel">
			    		<table class="table">
			    		<tr>
			    			<th>Clienti nelle vicinanze:</th>
			    		</tr>
			    		<tr>
			    		<td>
			    			<div id="inBoundPanelDiv" style="min-height:300px;">
					    		<table class="table table-striped table-condensed table-bordered" id="inBoundPanel" ></table> 
					    	</div>
				    	</td>
				    	</tr>
				    	</table>
				    	
				    	
				    </div>
			    </div>
		    </div>
		</div>
        
		</div>

	  </div>
	</div>

	<!-- Supporting JavaScript -->
	<script type="text/javascript">
		$(document).ready(function() {	
			
			$.blockUI({ message: '<h1>Loading...</h1>' });
			initializeGMap();
			
		});
		
		markerColors = new Array();
		
SEGDTA;

			for($ic=1;$ic<=10;$ic++) {
				echo "markerColors[".$ic."] = '".$markerColors[$ic]."';\n";	
			}
		
		echo <<<SEGDTA

	 	
		var routeBoxer = null;
		var directionsDisplay;
		var directionsService = new google.maps.DirectionsService();	
		var geocoder;
		var distance = 2; //Km
		var map;
		var markerArray = [];
		var stepDisplay;
		var oldDirections = [];
		var currentDirections = null;
		var origin = null;
		var destination = null;
		var waypoints = [];
		loclat = new Array();
		loclng = new Array();
		ind = new Array();
		rag = new Array();
		codcli = new Array();
		fat1 = new Array();
		fat2 = new Array();
		fat3 = new Array();
		stepMarkers = new Array();
		var tsp;
		letters = new Array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","Z");
		
		
		function getRouteLatLngs() { 
		
			 
			kmlSteps = new Array(); 
			for(i=0;i<stepMarkers.length;i++) {
				if(stepMarkers[i]) {
					
					obj = new Object();
					obj.position = stepMarkers[i].position;
					obj.title = stepMarkers[i].title;
					obj.addr = stepMarkers[i].addr;
					kmlSteps.push(obj); 
					
				}
			}
			stepsLatLngsJson = JSON.stringify(kmlSteps);
			  
			 
			routeLatLngs = tsp.getRouteLatLngs();	
			routeLatLngsJson = JSON.stringify(routeLatLngs);
			$.post("mappa-clienti.php", { task: 'getKml', 
			routeLatLngs: routeLatLngsJson, 
			stepsLatLngs: stepsLatLngsJson, 
			GIPACL: '$GIPACL',
			GIARCL: '$GIARCL',
			GTPROG: '$GTPROG' 
			})
			  .done(function( data ) {
			    document.location.href = "maps/kmlexport/kml_$GTPROG.kml";
			}); 
		} 
		
		function initializeGMap() {
			
			directionsDisplay = new google.maps.DirectionsRenderer({
				suppressMarkers : true,
				preserveViewport: true	
			});
	 
			var myOptions = {
			  zoom: 8,
			  mapTypeId: google.maps.MapTypeId.ROADMAP
			  
			}
			map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
			
			
			routeBoxer = new RouteBoxer();
			directionsDisplay.setMap(map); 	
			
			//directionsDisplay.setPanel(document.getElementById("directionsPanel"));	
			// Create the tsp object
			tsp = new BpTspSolver(map, null);
	
			codeAddress();
	 
	        infowindow = new google.maps.InfoWindow();
		}
	
		// Draw the array of boxes as polylines on the map
		function drawBoxes(boxes) {
		  boxpolys = new Array(boxes.length);
		  for (var i = 0; i < boxes.length; i++) {
		    boxpolys[i] = new google.maps.Rectangle({
		      bounds: boxes[i],
		      fillOpacity: 0,
		      strokeOpacity: 1.0,
		      strokeColor: '#b2bbfb',
		      strokeWeight: 1,
		      map: map
		    });
		  }
		}
		
		function codeAddress() {
		    
		    var lastcoords = 0;
	
			var n = 0;
		 	
		 	stepDisplay = new google.maps.InfoWindow();
	
			stops = new Array();
			//stops[] sono i jwaypoints
		    
SEGDTA;

		    	$this->dspGCoords($GTPROG,$GIPACL,$GIARCL,$GITIPA,$GITIAR);
		    
		echo <<<SEGDTA

		    /*
		    manca percorso ottimale (distance|time)
		    */
		    
SEGDTA;
 
			    echo "tsp.setAvoidHighways(false);\n";
			    if($GINOPE=="S") echo "tsp.setAvoidTolls(true);\n";
			    else echo "tsp.setAvoidTolls(false);\n";
				echo "tsp.setTravelMode(google.maps.DirectionsTravelMode.DRIVING);\n";
			    echo "tsp.solveAtoZ(onSolveCallback);\n";   
		    
		echo <<<SEGDTA

	
		}
	  
		function addWaypointCallback() {
		 
		}
	
		function onSolveCallback() {
			order = new Array();
			order = tsp.getOrder();
			
	
			
			//qui ho l'ordine dei punti
			//ricostruisco l'array dei punti e lo passo sui batches
			tmpstops = new Array();
			for(i=0;i<stops.length;i++) {
				tmpstops[i] = stops[order[i]];	
			}
			stops = tmpstops;
			
			showOnMap();
			/*
			var ordstr = order.join(",");
			url = "?task=updateSessionOrder&order="+ordstr;
			$.get(url,function(data){
			});
			*/ 
		}
	
		function showOnMap() {
			var batches = [];
			var itemsPerBatch = 5; // google API max - 1 start, 1 stop, and 8 jwaypoints
			var itemsCounter = 0;
			var wayptsExist = stops.length > 0;
			
			while (wayptsExist) {
				var subBatch = [];
				var subitemsCounter = 0;
			
				for (var j = itemsCounter; j < stops.length; j++) {
					subitemsCounter++;
					subBatch.push({
						location: new window.google.maps.LatLng(stops[j].lat(), stops[j].lng()),
						stopover: true
					});
					if (subitemsCounter == itemsPerBatch)
						break;
				}
			
				itemsCounter += subitemsCounter;
				batches.push(subBatch);
				wayptsExist = itemsCounter < stops.length;
				// If it runs again there are still points. Minus 1 before continuing to 
				// start up with end of previous tour leg
				itemsCounter--;
			} 
			
			
			calcRoute(batches, directionsService, directionsDisplay);
			
			//$.unblockUI();	
		    
		}
	  
		function computeTotalDistance(result) {
			 
			
			var total = 0;
			var totalt = 0;
			var myroute = result.routes[0];
			var rightStepReport = '<table class="table table-striped">
SEGDTA;
 echo $this->repPartenza; 
		echo <<<SEGDTA
';
			
			var path = myroute.overview_path;
	        var boxes = routeBoxer.box(path, distance);
	      
	        var image = '/crud/websmart/maps/img/google-maps-marker-red.png';
	      	var ne = 0;
	      	var sw = 0;
	      	markers = new Array();
	      	
	      	if("$ricalcInBound"!="N") {
		      	$("#inBoundPanelDiv").block({ message: null }); 
		      	
		      	$("#inBoundPanel").html("");
	 	        for(i=0;i<boxes.length;i++) {
		        	ne = boxes[i].getNorthEast();
		        	sw = boxes[i].getSouthWest();
		        	 
		        	ne_lat = ne.lat(); 
		        	ne_lng = ne.lng();
		        	sw_lat = sw.lat(); 
		        	sw_lng = sw.lng();
		        	
					y = new Date().getFullYear();
					y1 = y - 1;
					y2 = y - 2;
		        	
		        	let isLast = false;
					if(i==boxes.length-1) isLast = true;
		        	
		        	url = "?task=savPointsInBound&ne_lat="+escape(ne_lat)+"&ne_lng="+escape(ne_lng)+"&sw_lat="+escape(sw_lat)+"&sw_lng="+escape(sw_lng);
		        	$.getJSON(url,function(rtnpts) { 
		        		if(isLast) {
		        			getPointsInBound();
		        			$("#inBoundPanelDiv").unblock();
		        		}
		        		
		        	});
		        }
		    } else {
    			getPointsInBound();
    			$("#inBoundPanelDiv").unblock();
		    }
		    
	        drawBoxes(boxes);
			 
			var timePartenza = "";
			var timeArrivo = "";
			
			//Compongo stringa tappe 
			for (i = 0; i < myroute.legs.length; i++) {
				
				
			  total += myroute.legs[i].distance.value;
			  totalt+= myroute.legs[i].duration.value;
			  curlat = myroute.legs[i].start_location.lat();
			  curlng = myroute.legs[i].start_location.lng();
	 
			  //salto la partenza
			  if(i>0) {    
	 
				  var currag = "";
				  var curind = "";
				  var curcodcli = "";
				  for(j=0;j<loclat.length && currag=="";j++) {
				  	if(isSimilarNumbers(loclat[j],curlat) && isSimilarNumbers(loclng[j],curlng))
				  	{
				  		currag = rag[j];
				  		curind = ind[j];	
				  		curcodcli = codcli[j];
				  	}
				  }
				  
				  rightStepReport += '<tr><td><div class="markerTappaDiv" style="background-color:#'+markerColors[i]+';">'+i+'</div></td><td><strong>'+currag+'</strong><br><span class="markerAddr">'+curind+'</span><br>'+durationStr+'<br><input class="btn btn-xs btn-danger" type="button" onclick="dltGiro(\''+curcodcli+'\')" value="Rimuovi" /></td></tr>';
				  
			  }  
			  
		  	  var curdur = 0;
		  	  durationStr = "";
		  	  curdur = myroute.legs[i].duration.value;
			  durationObj = secondsToTime(curdur);
			  if(durationObj.h>0) durationStr = durationObj.h + ' h';
			  if(durationObj.m>0) durationStr+= ' '+durationObj.m + ' m';
			       
	    
			       
			}
			rightStepReport += '
SEGDTA;
 echo $this->repArrivo; 
		echo <<<SEGDTA
</table>';
			$("#stepReport").html(rightStepReport);
			 
			$("#stepTimeArrivo").html(durationStr);
			
			total = Math.floor(total / 1000);
			
			totalTime = totalt;  
			
			durationObj = secondsToTime(totalt);
			if(durationObj.h>0) durationStr = durationObj.h + ' ore';
			if(durationObj.m>0) durationStr+= ' '+durationObj.m + ' minuti';
			
	
			durationObj = secondsToTime(totalTime);
			//if(durationObj.h>0) durationStrTotal = durationObj.h + ' ore';
			//if(durationObj.m>0) durationStrTotal+= ' '+durationObj.m + ' minuti';
				
			
			$('#totalTravelDistance').html(total + ' km');
			$('#totalTravelDuration').html(durationStr);
			//document.getElementById('totalDuration').innerHTML = durationStrTotal;
			
		}
	
		function getPointsInBound() {
			
	        var image = '/crud/websmart/maps/img/google-maps-marker-red.png';

        	url = "?task=getPointsInBound&ts="+Date.now();
        	$.getJSON(url,function(rtnpts) {         		
        		
        		if(rtnpts[0].status!="ERR" && rtnpts[0].status!="NONE") {
        			
        			for(j=0;j<rtnpts.length;j++) {
        				//Aggiungo i punti

        				contentString = '<div style="text-align:left;width:250px;height:200px;color:black;"><b>'+rtnpts[j].ABAN8+' - '+rtnpts[j].ABALPH+'</b><br><span class="indClienteMarker"><br>'+rtnpts[j].ALADD2+','+rtnpts[j].ALCTY1+','+rtnpts[j].ALADDS+'</span><br>Fatturato '+y+': '+rtnpts[j].fatt1+'<br>Fatturato '+y1+': '+rtnpts[j].fatt2+'<br>Fatturato '+y2+': '+rtnpts[j].fatt3+'<br><br><input class="navbutton addcliente" type="button" onclick="addGiro(\''+rtnpts[j].ABAN8+'\')" value="Aggiungi" /></div>';
						inBoundString = '<tr><td valign="top"><a class="btn btn-sm btn-success addcliente glyphicon glyphicon-plus" href="javascript:void(\'0\');" onclick="addGiro(\''+rtnpts[j].ABAN8+'\')"></a></td><td><b>'+rtnpts[j].ABAN8+' - '+rtnpts[j].ABALPH+'</b><br><span class="indClienteMarker">'+rtnpts[j].ALADD2+',<br>'+rtnpts[j].ALCTY1+' ('+rtnpts[j].ALADDS+')</span></td nowrap><td nowrap>Fatturato '+y+': '+rtnpts[j].fatt1+'<br>Fatturato '+y1+': '+rtnpts[j].fatt2+'<br>Fatturato '+y2+': '+rtnpts[j].fatt3+'</td></tr>';	

						myLatlng = new google.maps.LatLng(rtnpts[j].GLAT,rtnpts[j].GLNG);
						markers[j] = new MarkerWithLabel({
						    position: myLatlng,
						    map: map,
						    title: rtnpts[j].ABALPH,
						    html: contentString,
						    icon: image,
						    zIndex: 98,
							labelContent: rtnpts[j].ABALPH,
							labelAnchor: new google.maps.Point(22, 0),
							labelClass: "maplabel", 
							labelStyle: {opacity: 0.95}
						});
						 
						var marker = markers[j];					
						google.maps.event.addListener(marker, 'click', function () { 
							infowindow.setContent(this.html);
							infowindow.open(map, this);
						});
							
						$("#inBoundPanel").html($("#inBoundPanel").html()+inBoundString);	
						
        			}	
        			
        			$.unblockUI();
        			
        		}
        	});
		}	
	
		function secondsToTime(s){
		    var h  = Math.floor( s / ( 60 * 60 ) );
		        s -= h * ( 60 * 60 );
		    var m  = Math.floor( s / 60 );
		        s -= m * 60;
		   
		    return {
		        "h": h,
		        "m": m,
		        "s": s
		    };
		}
	 
	  
	
	  function showSteps(directionResult) {
	    var myRoute = directionResult.routes[0];
	    
		//var path = directionResult.routes[0].overview_path;
		//var boxes = routeBoxer.box(path, 10);
		//drawBoxes(boxes);
	    
	    var letter = "";
		y = new Date().getFullYear();
		y1 = y - 1;
		y2 = y - 2;	    
	     
	    for (var i = 0; i < myRoute.legs.length; i++) {
	
			if (i == 0) {
				var icon = "/crud/websmart/maps/img/start_point_icon.png"; 
			} else {
				var icon = {
				    path: "M-20,0a20,20 0 1,0 40,0a20,20 0 1,0 -40,0",
				    fillColor: "#"+markerColors[i],
				    fillOpacity: 1,
				    anchor: new google.maps.Point(0, 0),
				    strokeWeight: 1,
				    scale: 0.5
				}
			}
			
			/*
			var marker = new google.maps.Marker({
				position: myRoute.legs[i].start_location, 
				map: map,
				icon: icon
			});
			*/

			/**/ 
			curlat = myRoute.legs[i].start_location.lat();
			curlng = myRoute.legs[i].start_location.lng();			
			var currag = "";
			var curind = "";
			var curcodcli = "";
			var curaddr = "";
			var curfat1 = "";
			var curfat2 = "";
			var curfat3 = "";
			for(j=0;j<loclat.length && currag=="";j++) {
			  	if(isSimilarNumbers(loclat[j],curlat) && isSimilarNumbers(loclng[j],curlng))
			  	{
			  		currag = rag[j];
			  		curind = ind[j];	
			  		curcodcli = codcli[j];
			  		curaddr = ind[j];
			  		curfat1 = fat1[j];
			  		curfat2 = fat2[j];
			  		curfat3 = fat3[j];
			  	}
			}			
			
			contentString = '<div style="text-align:left;width:250px;height:200px;color:black;"><b>'+curcodcli+' - '+currag+'</b><br><span class="indClienteMarker"><br>'+curaddr+'</span><br>Fatturato '+y+': '+curfat1+'<br>Fatturato '+y1+': '+curfat2+'<br>Fatturato '+y2+': '+curfat3+'</div>';
			
			stepMarkers[j] = new MarkerWithLabel({
			    position: myRoute.legs[i].start_location,
			    map: map,
			    title: currag,
			    addr: curaddr,
			    html: contentString,
			    icon: icon,
			    zIndex: 98,
				labelContent: currag,
				labelAnchor: new google.maps.Point(22, -12),
				labelClass: "maplabel", 
				labelStyle: {opacity: 0.95}
			});	
			 	
			var marker = stepMarkers[j];					
			google.maps.event.addListener(marker, 'click', function () { 
				infowindow.setContent(this.html);
				infowindow.open(map, this);
			});			 	
			 		
			/**/

			//attachInstructionText(marker, myRoute.legs[i].start_address);
			markerArray.push(marker);
	    } 
	     
	    var icon = "/crud/websmart/maps/img/start_point_icon.png"; 
	    var marker = new google.maps.Marker({
	      position: myRoute.legs[(i-1)].end_location, 
	      map: map,
	      icon: icon
	    });
	    attachInstructionText(marker, myRoute.legs[(i-1)].end_address);
	    markerArray.push(marker);    
	    
	    //google.maps.event.trigger(markerArray[0], "click");
	  }
	  
	  function attachInstructionText(marker, text) {
	  	
	    google.maps.event.addListener(marker, 'click', function() {
	      stepDisplay.setContent(text);
	      stepDisplay.open(map, marker);
	    });
	  }
	
	function roundNumber(number,decimal_points) {
		if(!decimal_points) return Math.round(number);
		if(number == 0) {
			var decimals = "";
			for(var i=0;i<decimal_points;i++) decimals += "0";
			return "0."+decimals;
		}
	
		var exponent = Math.pow(10,decimal_points);
		var num = Math.round((number * exponent)).toString();
		return num.slice(0,-1*decimal_points) + "." + num.slice(-1*decimal_points)
	}
	  
	function isSimilarNumbers(num1,num2) {
		//sono uguali
		if(num1==num2) return true;
		//provo a fare il round a 3 decimali
		if(roundNumber(num1,3)==roundNumber(num2,3)) return true;
		//provo a con una soglia e round
		delta = 0.001;
		if(roundNumber(num1+delta,3)==roundNumber(num2,3)) return true;
		if(roundNumber(num1-delta,3)==roundNumber(num2,3)) return true;
		
		return false;
	}  
	
	function stampaPagina() {
		self.print();	
	}
	
	
	
	function calcRoute(batches, directionsService, directionsDisplay) {
		var combinedResults;
		var unsortedResults = [{}]; // to hold the counter and the results themselves as they come back, to later sort
		var directionsResultsReturned = 0;
		
		for (var k = 0; k < batches.length; k++) {
			var lastIndex = batches[k].length - 1;
			var start = batches[k][0].location;
			var end = batches[k][lastIndex].location;
			
			// trim first and last entry from array
			var waypts = [];
			waypts = batches[k];
			waypts.splice(0, 1);
			waypts.splice(waypts.length - 1, 1);
			
			
			var request = {
				origin : start,
				destination : end,
				waypoints : waypts,
				travelMode : google.maps.DirectionsTravelMode.DRIVING,
		        language: 'it',
		        avoidHighways: false,
		        avoidTolls: 
SEGDTA;
 if($GINOPE=="S") echo "true"; else echo "false"; 
		echo <<<SEGDTA
,
		        optimizeWaypoints: false,		
			};
	
	
			(function (kk) {
				
				//sleep qui
				
				directionsService.route(request, function (result, status) {
					if (status == window.google.maps.DirectionsStatus.OK) {
						
						var unsortedResult = {
							order : kk,
							result : result
						};
						unsortedResults.push(unsortedResult);
						
						directionsResultsReturned++;
						
						if (directionsResultsReturned == batches.length) // we've received all the results. put to map
						{
							// sort the returned values into their correct order
							unsortedResults.sort(function (a, b) {
								return parseFloat(a.order) - parseFloat(b.order);
							});
							var count = 0;
							for (var key in unsortedResults) {
								if (unsortedResults[key].result != null) {
									if (unsortedResults.hasOwnProperty(key)) {
										if (count == 0) // first results. new up the combinedResults object
											combinedResults = unsortedResults[key].result;
										else {
											// only building up legs, overview_path, and bounds in my consolidated object. This is not a complete
											// directionResults object, but enough to draw a path on the map, which is all I need
											combinedResults.routes[0].legs = combinedResults.routes[0].legs.concat(unsortedResults[key].result.routes[0].legs);
											combinedResults.routes[0].overview_path = combinedResults.routes[0].overview_path.concat(unsortedResults[key].result.routes[0].overview_path);
											
											combinedResults.routes[0].bounds = combinedResults.routes[0].bounds.extend(unsortedResults[key].result.routes[0].bounds.getNorthEast());
											combinedResults.routes[0].bounds = combinedResults.routes[0].bounds.extend(unsortedResults[key].result.routes[0].bounds.getSouthWest());
										}
										count++;
									}
								}
							}
							directionsDisplay.setDirections(combinedResults);
							
						    computeTotalDistance(combinedResults);
					      	showSteps(combinedResults);
					      	//$.unblockUI();
					        
						}
											
					}
				});
			})(k);
			
			
			 
			
	
			
		}
	}
	  
	  
	function callback(results, status) {
		if (status == google.maps.places.PlacesServiceStatus.OK) {
		  for (var i = 0; i < results.length; i++) {
		    createMarker(results[i]);
		  }
		}
	}
	  
	function createMarker(place) {
		//var icon = "//app.bigblue.it/websmart/bigblue5/img/hotel.png";
		var placeLoc = place.geometry.location;
		var marker = new google.maps.Marker({
		  map: map,
		  position: place.geometry.location,
		  icon: icon
		});
		
		google.maps.event.addListener(marker, 'click', function() {
		  infowindow.setContent(place.name+"<br>"+place.vicinity);
		  infowindow.open(map, this);
		});
	}  
	 
	function addGiro(ABAN8) { 
		$.blockUI({ message: '<h1>Loading</h1>' });
		url = "?task=addGiro&ABAN8="+ABAN8;
		$.get(url,function(data){ 
			document.location.href = "?task=calcGiroV&ricalcInBound=N&GIUSER=$GIUSER&GITIPA=$GITIPA&GITIAR=$GITIAR&GINOPE=$GINOPE&GITIPO=$GITIPO&GIPAUS=$GIPAUS&GIMECL=$GIMECL&GIORDI=$GIORDI&GIPACL=$GIPACL&GIARCL=$GIARCL";
		});	
	} 
	
	function dltGiro(ABAN8) { 
		$.blockUI({ message: '<h1>Loading</h1>' });
		url = "?task=dltGiro&ABAN8="+ABAN8;
		$.get(url,function(data){ 
			document.location.href = "?task=calcGiroV&ricalcInBound=N&GIUSER=$GIUSER&GITIPA=$GITIPA&GITIAR=$GITIAR&GINOPE=$GINOPE&GITIPO=$GITIPO&GIPAUS=$GIPAUS&GIMECL=$GIMECL&GIORDI=$GIORDI&GIPACL=$GIPACL&GIARCL=$GIARCL";
		});	
	} 
	
	
	 
	 
	</script>
    
</body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "segmarker")
	{

		echo <<<SEGDTA
	waypoints.push({ location: new google.maps.LatLng($lat, $lng), stopover: true });	

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "riccoord")
	{

		echo <<<SEGDTA
<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="largeModal" aria-hidden="true" id="modal-register-form">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel">Calcolo coordinate</h4>
      </div>
      <div class="modal-body">
           <form id="calcCoord-form" action="mappa-clienti.php" method="post">
            <input type="hidden" name="task" value="savCoord" />
			<input type="hidden" id="rc_ABAN8" name="ABAN8" value="" />
			
			  <div class="form-group">
                <label for="ALADD2">Indirizzo:</label>
                <div>
                  <input type="text" id="rc_ALADD2" class="form-control" name="ALADD2" value="" readonly>
                </div>
              </div>
              
			  <div class="form-group">
                <label for="ALCTY1">Citt&agrave;:</label>
                <div>
                  <input type="text" id="rc_ALCTY1" class="form-control" name="ALCTY1" value="" readonly>
                </div>
              </div>
              
			  <div class="form-group">
                <label for="ALADDS">Provincia:</label>
                <div>
                  <input type="text" id="rc_ALADDS" class="form-control" name="ALADDS" value="" readonly>
                </div>
              </div>
              
			  <div class="form-group">
                <label for="ALCTR">Nazione:</label>
                <div>
                  <input type="text" id="rc_ALCTR" class="form-control" name="ALCTR" value="" readonly>
                </div>
              </div>
              
			  <div class="form-group">
                <label for="CCLATI">Latitudine:</label>
                <div>
                  <input type="text" id="rc_CCLATI" class="form-control" name="CCLATI" value="" readonly>
                </div>
              </div>
              
			  <div class="form-group">
                <label for="CCLONG">Longitudine:</label>
                <div>
                  <input type="text" id="rc_CCLONG" class="form-control" name="CCLONG" value="" readonly>
                </div>
              </div>
			
        </form>
      </div>
      <div class="modal-footer"> 
	    <input type="button" class="btn btn-primary accept" onclick="calcCoord();" value="Ricava coordinate" />
        <input type="button" class="btn btn-primary" onclick="$('#calcCoord-form').submit();" value="Salva" /> 
        <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
      </div>
    </div>
  </div>
</div>









SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "clccoord")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="ISO-8859-1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Mappa clienti</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/js/jquery-ui.js"></script> 
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
    
    
    <style>
    #resCoord {
    	min-height:200px;
    	max-height:600px;
    	overflow-y: auto;
    	border: 1px solid black;	
    }
    </style>
    
  </head>
  <body class="display-list"> 
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Mappa clienti</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <input type="button" id="startBtn" class="btn btn-sm btn-success" value="Start" onclick="startCoords()" />
		  <input type="button" id="stopBtn" class="btn btn-sm btn-warning" value="Stop" onclick="stopCoords()" />
		  <input type="button" id="clearBtn" class="btn btn-sm btn-warning" value="Clear" onclick="clearCoords()" />
          <br><br>
          <div id="resCoord"></div>
        </div>
      </div>	
    </div> 
    
    <script type="text/javascript">
	$(document).ready(function() {
		 
		
	});
	
	
	let i = 0; 
	let st = false;
	
	function startCoords() {
		i = 0;
		st = false;
		getCoords();
	}
	
	function getCoords() {
		
		$("#startBtn").attr("disabled","disabled");
		
		url = "?task=getCoords";
		$.getJSON(url,function(data){
			for(j = 0;j < data.length; j++) {
				h = "<div>an8:"+data[j].AN+", stat:"+data[j].AD;
				if(data[j].AD!="OK") h = h +", addr:"+data[j].ST;
				h = h +"</div>";
				$("#resCoord").html($("#resCoord").html()+h);
			}
			
			//max 20 iterazioni oppure pressione tasto stop
			if(i>=20 || st) {
				$("#startBtn").removeAttr("disabled");
				return;
			}
			
			getCoords();
			i++;
		});	
	}
	 
	function stopCoords() {
		$("#startBtn").removeAttr("disabled");
		st = true;
	} 
	 
 	function clearCoords() {
		
		$("#resCoord").html("");
	} 	
 	
</script> 
    
    
  </body>
</html>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "kmlheader")
	{

		echo <<<SEGDTA
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>Mappa senza titolo</name>
    <description/>
    <Style id="icon-1899-DB4436-nodesc-normal">
      <IconStyle>
        <color>ff3644db</color>
        <scale>1</scale>
        <Icon>
          <href>https://www.gstatic.com/mapspro/images/stock/503-wht-blank_maps.png</href>
        </Icon>
        <hotSpot x="32" xunits="pixels" y="64" yunits="insetPixels"/>
      </IconStyle>
      <LabelStyle>
        <scale>0</scale>
      </LabelStyle>
      <BalloonStyle>
        <text><![CDATA[<h3>$[name]</h3>]]></text>
      </BalloonStyle>
    </Style>
    <Style id="icon-1899-DB4436-nodesc-highlight">
      <IconStyle>
        <color>ff3644db</color>
        <scale>1</scale>
        <Icon>
          <href>https://www.gstatic.com/mapspro/images/stock/503-wht-blank_maps.png</href>
        </Icon>
        <hotSpot x="32" xunits="pixels" y="64" yunits="insetPixels"/>
      </IconStyle>
      <LabelStyle>
        <scale>1</scale>
      </LabelStyle>
      <BalloonStyle>
        <text><![CDATA[<h3>$[name]</h3>]]></text>
      </BalloonStyle>
    </Style>
    <StyleMap id="icon-1899-DB4436-nodesc">
      <Pair>
        <key>normal</key>
        <styleUrl>#icon-1899-DB4436-nodesc-normal</styleUrl>
      </Pair>
      <Pair>
        <key>highlight</key>
        <styleUrl>#icon-1899-DB4436-nodesc-highlight</styleUrl>
      </Pair>
    </StyleMap>
    <Style id="line-1267FF-5000-nodesc-normal">
      <LineStyle>
        <color>ffff6712</color>
        <width>5</width>
      </LineStyle>
      <BalloonStyle>
        <text><![CDATA[<h3>$[name]</h3>]]></text>
      </BalloonStyle>
    </Style>
    <Style id="line-1267FF-5000-nodesc-highlight">
      <LineStyle>
        <color>ffff6712</color>
        <width>7.5</width>
      </LineStyle>
      <BalloonStyle>
        <text><![CDATA[<h3>$[name]</h3>]]></text>
      </BalloonStyle>
    </Style>
    <StyleMap id="line-1267FF-5000-nodesc">
      <Pair>
        <key>normal</key>
        <styleUrl>#line-1267FF-5000-nodesc-normal</styleUrl>
      </Pair>
      <Pair>
        <key>highlight</key>
        <styleUrl>#line-1267FF-5000-nodesc-highlight</styleUrl>
      </Pair>
    </StyleMap>
    <Folder>
      <name>Indicazioni stradali</name>
      <Placemark>
        <name>Indicazioni stradali</name>
        <styleUrl>#line-1267FF-5000-nodesc</styleUrl>
        <LineString>
          <tessellate>1</tessellate>
          <coordinates>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "segmappa2")
	{

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="ISO-8859-1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Mappa clienti</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Google+Sans:400,500,700|Google+Sans+Text:400&lang=it" media="all" type="text/css" />
     
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
     
	<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=
SEGDTA;
 echo $this->googleApiKey; 
		echo <<<SEGDTA
"></script>
	<script type="text/javascript" src="/crud/websmart/maps/BpTspSolver.js"></script> 
	<script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
	<script src="/crud/websmart/maps/RouteBoxer.js" type="text/javascript"></script>
	<script src="/crud/websmart/maps/markerwithlabel.js" type="text/javascript"></script>
     
     
	<style>
	@media print {
	  body {
	    visibility: hidden;
	  }
	  #stepReportContainer {
	    visibility: visible;
	    position: absolute;
	    left: 0;
	    top: 0;
	    width: auto;
	    height: auto;
	  }
	  div#stepReport {
	  	overflow-y: hidden !important;
	  	max-height: none !important;	
	  }
	  input[type='button'] {
	  	display: none;	
	  }
	  .maplabel {
		  display: none !important;
		  visibility: hidden !important;
	  }
	  
	}
	
	@media screen {
		#directionsPanel {
			height:555px;
			overflow-y:auto;	
		}
		.markerTappaDivStart {
		    background-image: url("/crud/websmart/maps/img/start_point_icon.png");
		} 
		.markerTappaDiv {
		    
		} 
	    .markerTappaDivStart, .markerTappaDiv {
		    background-repeat: no-repeat;
		    font-weight: bold;
		    height: 18px;
		    padding-left: 4px;
		    vertical-align: top;
		    width: 18px;
		    color:black;
		    border-radius: 9px;
		}
	}
	
	.smart-style-5 .gm-style-iw-d div {
		color: #000 !important;	
	}
	.smart-style-5 .maplabel {
		color: #000 !important; 
	}
	.maplabel { 
		background-color: white !important;	
		visibility: hidden;
		padding: 2px;
		border: 1px solid black;
		border-radius: 2px;		
		font-weight: bold;
	}	
	#inBoundPanelDiv {
		max-height: 553px;
		overflow-y: auto;	
	}
	
	body, html {
	  height: 100%;
	  width: 100%;
	  margin: 0px;
	  font-family: Roboto, Arial, sans-serif;
	}
	
	div#contents, div#page-content, div#outer-content {
	  width: 100%; 
	  height: 100%;
	}	
	
	div#left_panel {
		position: absolute;
		top: 100px;
		left: 50px;
		width: 300px;
		z-index: 9996;
		/*background-color: white !important;*/
		font-size: 13px !important;
	}
	
	div#right_panel {
		position: absolute;
		top: 120px;
		right: 30px;
		width: 500px;
		z-index: 9996;
		/*background-color: white !important;*/
		font-size: 13px !important;
	}

	div#legenda_panel {
		position: absolute;
		top: 10px;
		right: 30px;
		width: 500px;
		z-index: 9996;
		/*background-color: white !important;*/
		font-size: 13px !important;
	}
	 
	div#stepReport {
		max-height: 500px;
		overflow-y: auto;
		background-color: white;
	}
	
	div#stepReportContainer, div#inBoundReportContainer, div#legendaContainer {
		padding: 10px !important;
		margin-bottom: 0px !important;
		background-color: #fff !important;
	}
	
	div#map_canvas {
		height:100%;
		width:100%;
		margin:0 auto;	
	}
	
	.tipoClieSpan {
		float:right;	
	}
	
	/*
	.smart-style-5 .adp-summary .adp-directions {
		color: #fff !important;		
	}
	*/
	</style> 
	 

  </head>
  <body class="display-list">
    <div id="outer-content">
       
      <div id="page-content"> 
        <div id="contents"> 
        
        	<div id="left_panel">

        		<div id="stepReportContainer" class="well">
					<input type="button" value="Indietro" class="btn btn-xs btn-primary" onclick="document.location.href='?task=default'" />&nbsp;
					<input type="button" value="Stampa tappe" class="btn btn-xs btn-primary" onclick="window.print();" />&nbsp;
					<input type="button" value="Esporta mappa" class="btn btn-xs btn-primary" onclick="getRouteLatLngs();" />
					<br><br>
			    	<strong>Tappe del viaggio:</strong>
			    	<div id="stepReport"></div> 
		    		<br>
		    		<table class="tabRiepilogo table">
			    		<tr>
			    			<td >Durata totale:</td>
			    			<td id="totalTravelDuration"></td>
			    		</tr> 
			    		 	    		
			    		<tr>
			    			<td >Distanza totale:</td>
			    			<td id="totalTravelDistance"></td>
			    		</tr>
			    	</table>
			    </div>
        	</div>
        
        	<div id="map_canvas"></div>
        	 
        	<div id="legenda_panel">
        		<div id="legendaContainer" class="well">
	        		<img src="/crud/websmart/maps/img/marker-green.png"> Clienti<br>
	        		<img src="/crud/websmart/maps/img/marker-blue.png"> Prospect<br>
	        		<img src="/crud/websmart/maps/img/marker-red.png"> Fornitori<br>
	        		<div style="position:absolute;top:10px;right:10px;">Visualizza etichette: <input type="checkbox" id="switch-label" onchange="switchLabel()" /></div>
	        	</div>
        	</div>
        	
	    	<div id="right_panel">
	    		<div id="inBoundReportContainer" class="well">
	    			<strong>Clienti nelle vicinanze:</strong>
	    			<div id="inBoundPanelDiv">
			    		<table class="table table-striped table-condensed table-bordered"><tbody id="inBoundPanel"></tbody></table> 
			    	</div>
			    	
		    	</div> 
		    </div>        	
        	
        	
        	<!--
			
			
        	<br><br>
        	<div class="row">
	        	<div class="col-sm-12">
			    	<div class="col-sm-2" id="centerPanel">
						
				    	<div id="stepReport"></div>
					
			    		<br>
			    		<table class="tabRiepilogo table">
				    		<tr>
				    			<td >Durata totale:</td>
				    			<td id="totalTravelDuration"></td>
				    		</tr> 
				    		 	    		
				    		<tr>
				    			<td >Distanza totale:</td>
				    			<td id="totalTravelDistance"></td>
				    		</tr>
				    	</table>

			    	</div>
			    	
		        	<div class="col-sm-6" id="leftPanel">
				    	<div id="map_canvas"></div> 
				    	<div id="divMapErr"> </div>  
			    	</div>

			    	<div class="col-sm-4" id="rightPanel">
			    		<table class="table">
			    		<tr>
			    			<th>Clienti nelle vicinanze:</th>
			    		</tr>
			    		<tr>
			    		<td>
			    			<div id="inBoundPanelDiv" style="min-height:300px;">
					    		<table class="table table-striped table-condensed table-bordered" id="inBoundPanel" ></table> 
					    	</div>
				    	</td>
				    	</tr>
				    	</table>
				    	
				    	
				    </div>
			    </div>
		    </div>
		    -->
		</div>
        
		</div>

	  </div>
	</div>

	<script src="/crud/maps/markerclusterer/src/markerclusterer.js"></script>
	<!--<script src="https://unpkg.com/@googlemaps/markerclustererplus/dist/index.min.js"></script>-->


	<!-- Supporting JavaScript -->
	<script type="text/javascript">
		$(document).ready(function() {	
			
			$.blockUI({ message: '<h1>Loading...</h1>', baseZ: 9999 });
			initializeGMap();
			
		});
		
		markerColors = new Array();
		
SEGDTA;

			for($ic=1;$ic<=10;$ic++) {
				echo "markerColors[".$ic."] = '".$markerColors[$ic]."';\n";	
			}
		
		echo <<<SEGDTA

	 	
		var routeBoxer = null;
		var directionsDisplay;
		var directionsService = new google.maps.DirectionsService();	
		var geocoder;
		var distance = $GIRARI; //Km
		var map;
		var markerArray = [];
		var stepDisplay;
		var oldDirections = [];
		var currentDirections = null;
		var origin = null;
		var destination = null;
		var waypoints = [];
		loclat = new Array();
		loclng = new Array();
		ind = new Array();
		rag = new Array();
		codcli = new Array();
		fat1 = new Array();
		fat2 = new Array();
		fat3 = new Array();
		stepMarkers = new Array();
		var tsp;
		letters = new Array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","Z");
		
		
		function getRouteLatLngs() { 
		
			 
			kmlSteps = new Array(); 
			for(i=0;i<stepMarkers.length;i++) {
				if(stepMarkers[i]) {
					
					obj = new Object();
					obj.position = stepMarkers[i].position;
					obj.title = stepMarkers[i].title;
					obj.addr = stepMarkers[i].addr;
					kmlSteps.push(obj); 
					
				}
			}
			stepsLatLngsJson = JSON.stringify(kmlSteps);
			  
			 
			routeLatLngs = tsp.getRouteLatLngs();	
			routeLatLngsJson = JSON.stringify(routeLatLngs);
			$.post("mappa-clienti.php", { task: 'getKml', 
			routeLatLngs: routeLatLngsJson, 
			stepsLatLngs: stepsLatLngsJson, 
			GIPACL: '$GIPACL',
			GIARCL: '$GIARCL',
			GTPROG: '$GTPROG' 
			})
			  .done(function( data ) {
			    document.location.href = "maps/kmlexport/kml_$GTPROG.kml";
			}); 
		} 
		
		function initializeGMap() {
			

	 
			var noPoi = [{
				featureType: "poi",
				elementType: "labels",
				
				stylers: [{
				  visibility: "off"
				}]
			}];
	  
			var myOptions = {
			  zoom: 12,
			  mapTypeControlOptions: {
			      mapTypeIds: ["roadmap", "satellite", "hybrid", "terrain"],
			  },
			  styles: noPoi
			  
			}
			map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
			
			  
			  
			google.maps.event.addListener(map, 'idle', function() {
				setTimeout(switchLabel, 1000);
			});
			 		
			
			routeBoxer = new RouteBoxer();
			
			directionsDisplay = new google.maps.DirectionsRenderer({
				map: map,
				polylineOptions: {
				  strokeColor: 'blue'
				},
				suppressMarkers : true,
				preserveViewport: true		
			});			
			/*directionsDisplay.setMap(map);
 
			directionsDisplay.setOptions({
				polylineOptions: {
				  strokeColor: 'blue'
				},
				preserveViewport: true,
				suppressMarkers : true
			});*/
 			
			//directionsDisplay.setPanel(document.getElementById("directionsPanel"));	
			// Create the tsp object
			tsp = new BpTspSolver(map, null);
	
			codeAddress();
	 
	        infowindow = new google.maps.InfoWindow();
		}
	
		// Draw the array of boxes as polylines on the map
		function drawBoxes(boxes) {
		  boxpolys = new Array(boxes.length);
		  for (var i = 0; i < boxes.length; i++) {
		    boxpolys[i] = new google.maps.Rectangle({
		      bounds: boxes[i],
		      fillOpacity: 0,
		      strokeOpacity: 1.0,
		      strokeColor: '#b2bbfb',
		      strokeWeight: 1,
		      map: map
		    });
		  }
		}
		
		function codeAddress() {
		    
		    var lastcoords = 0;
	
			var n = 0;
		 	
		 	stepDisplay = new google.maps.InfoWindow();
	
			stops = new Array();
			//stops[] sono i jwaypoints
		    
SEGDTA;

		    	$this->dspGCoords($GTPROG,$GIPACL,$GIARCL,$GITIPA,$GITIAR);
		    
		echo <<<SEGDTA

		    /*
		    manca percorso ottimale (distance|time)
		    */
		    
SEGDTA;
 
			    echo "tsp.setAvoidHighways(false);\n";
			    if($GINOPE=="S") echo "tsp.setAvoidTolls(true);\n";
			    else echo "tsp.setAvoidTolls(false);\n";
				echo "tsp.setTravelMode(google.maps.DirectionsTravelMode.DRIVING);\n";
			    echo "tsp.solveAtoZ(onSolveCallback);\n";   
		    
		echo <<<SEGDTA

	
		}
	  
		function addWaypointCallback() {
		 
		}
	
		function onSolveCallback() {
			order = new Array();
			order = tsp.getOrder();
			
	
			
			//qui ho l'ordine dei punti
			//ricostruisco l'array dei punti e lo passo sui batches
			tmpstops = new Array();
			for(i=0;i<stops.length;i++) {
				tmpstops[i] = stops[order[i]];	
			}
			stops = tmpstops;
			
			showOnMap();
			/*
			var ordstr = order.join(",");
			url = "?task=updateSessionOrder&order="+ordstr;
			$.get(url,function(data){
			});
			*/ 
		}
	
		function showOnMap() {
			var batches = [];
			var itemsPerBatch = 5; // google API max - 1 start, 1 stop, and 8 jwaypoints
			var itemsCounter = 0;
			var wayptsExist = stops.length > 0;
			
			while (wayptsExist) {
				var subBatch = [];
				var subitemsCounter = 0;
			
				for (var j = itemsCounter; j < stops.length; j++) {
					subitemsCounter++;
					subBatch.push({
						location: new window.google.maps.LatLng(stops[j].lat(), stops[j].lng()),
						stopover: true
					});
					if (subitemsCounter == itemsPerBatch)
						break;
				}
			
				itemsCounter += subitemsCounter;
				batches.push(subBatch);
				wayptsExist = itemsCounter < stops.length;
				// If it runs again there are still points. Minus 1 before continuing to 
				// start up with end of previous tour leg
				itemsCounter--;
			} 
			
			
			calcRoute(batches, directionsService, directionsDisplay);
			
			//$.unblockUI();	
		    
		}
	  
		function computeTotalDistance(result) {
			 
			
			var total = 0;
			var totalt = 0;
			var myroute = result.routes[0];
			var rightStepReport = '<table class="table table-condensed">
SEGDTA;
 echo $this->repPartenza; 
		echo <<<SEGDTA
';
			
			var path = myroute.overview_path;
	        var boxes = routeBoxer.box(path, distance);
	      
	        var image = '/crud/websmart/maps/img/google-maps-marker-red.png';
	      	var ne = 0;
	      	var sw = 0;
	      	markers = new Array();
	      	
	      	if("$GIVITU"!="S") {
		      	if("$ricalcInBound"!="N") {
			      	$("#inBoundPanelDiv").block({ message: null }); 
			      	
			      	$("#inBoundPanel").html("<tr><th colspan='2'></th><th>Fatturato</th></tr>");
		 	        for(i=0;i<boxes.length;i++) {
			        	ne = boxes[i].getNorthEast();
			        	sw = boxes[i].getSouthWest();
			        	 
			        	ne_lat = ne.lat(); 
			        	ne_lng = ne.lng();
			        	sw_lat = sw.lat(); 
			        	sw_lng = sw.lng();
			        	
						y = new Date().getFullYear();
						y1 = y - 1;
						y2 = y - 2;
			        	
			        	let isLast = false;
						if(i==boxes.length-1) isLast = true;
			        	
			        	url = "?task=savPointsInBound&GIMIFA=$GIMIFA&ne_lat="+escape(ne_lat)+"&ne_lng="+escape(ne_lng)+"&sw_lat="+escape(sw_lat)+"&sw_lng="+escape(sw_lng);
			        	$.getJSON(url,function(rtnpts) { 
			        		if(isLast) {
			        			getPointsInBound();
			        			$("#inBoundPanelDiv").unblock();
			        		}
			        		
			        	});
			        }
			    } else {
	    			getPointsInBound();
	    			$("#inBoundPanelDiv").unblock();
			    }
			    
			    drawBoxes(boxes);
			    
		    } else {  
			    jm = 0;
			    
SEGDTA;
 
			    	$this->dspAllMarkers($GIMIFA); 
			    
		echo <<<SEGDTA

	    		/*
	    		const imagePath = "/CRUD/maps/markerclusterer/images/m"; 
	    		var markerCluster = new MarkerClusterer(map, markers, {gridSize: 70, imagePath: imagePath});
			    */  
		    	$.unblockUI();
		    }
	        
			var timePartenza = "";
			var timeArrivo = "";
			
			//Compongo stringa tappe 
			for (i = 0; i < myroute.legs.length; i++) {
				
				
			  total += myroute.legs[i].distance.value;
			  totalt+= myroute.legs[i].duration.value;
			  curlat = myroute.legs[i].start_location.lat();
			  curlng = myroute.legs[i].start_location.lng();
	 
			  //salto la partenza
			  if(i>0) {     
				  var currag = "";
				  var curind = "";
				  var curcodcli = "";
				  for(j=0;j<loclat.length && currag=="";j++) {
				  	if(isSimilarNumbers(loclat[j],curlat) && isSimilarNumbers(loclng[j],curlng))
				  	{
				  		currag = rag[j];
				  		curind = ind[j];	
				  		curcodcli = codcli[j];
				  	}
				  }
				  
				  rightStepReport += '<tr><td><div class="markerTappaDiv" style="background-color:#'+markerColors[i]+';">'+i+'</div></td><td><strong>'+currag+'</strong><br><span class="markerAddr">'+curind+'</span><br>'+durationStr+'<br><input class="btn btn-xs btn-danger" type="button" onclick="dltGiro(\''+curcodcli+'\')" value="Rimuovi" /></td></tr>';
				  
			  }  
			  
		  	  var curdur = 0;
		  	  durationStr = "";
		  	  curdur = myroute.legs[i].duration.value;
			  durationObj = secondsToTime(curdur);
			  if(durationObj.h>0) durationStr = durationObj.h + ' h';
			  if(durationObj.m>0) durationStr+= ' '+durationObj.m + ' m';
			       
	    
			       
			}
			rightStepReport += '
SEGDTA;
 echo $this->repArrivo; 
		echo <<<SEGDTA
</table>';
			$("#stepReport").html(rightStepReport);
			 
			$("#stepTimeArrivo").html(durationStr);
			
			total = Math.floor(total / 1000);
			
			totalTime = totalt;  
			
			durationStr = "";
			durationObj = secondsToTime(totalt);
			if(durationObj.h>0) durationStr = durationObj.h + ' ore';
			if(durationObj.m>0) durationStr+= ' '+durationObj.m + ' minuti';
			
	
			durationObj = secondsToTime(totalTime);
			//if(durationObj.h>0) durationStrTotal = durationObj.h + ' ore';
			//if(durationObj.m>0) durationStrTotal+= ' '+durationObj.m + ' minuti';
				
			
			$('#totalTravelDistance').html(total + ' km');
			$('#totalTravelDuration').html(durationStr);
			//document.getElementById('totalDuration').innerHTML = durationStrTotal;
			
		}
	
		function decodTipoClie(tipoClie) {
			decClie = "";
			if(tipoClie=="C") decClie = "Cliente";
			if(tipoClie=="CS") decClie = "Cliente (sped.)";
			if(tipoClie=="P") decClie = "Prospect";
			if(tipoClie=="PS") decClie = "Prospect (sped.)";
			if(tipoClie=="V") decClie = "Fornitore";
			return decClie;
		}
	
		function getPointsInBound() {
			
	        var image = '/crud/websmart/maps/img/marker-green.png';

        	url = "?task=getPointsInBound&GIVITU=$GIVITU&GIMIFA=$GIMIFA&GIRARI=$GIRARI&GITPCL_C=$GITPCL_C&GITPCL_CS=$GITPCL_CS&GITPCL_P=$GITPCL_P&GITPCL_PS=$GITPCL_PS&GITPCL_V=$GITPCL_V&ts="+Date.now();
        	$.getJSON(url,function(rtnpts) {         		
        		
        		if(rtnpts[0].status!="ERR" && rtnpts[0].status!="NONE") {
        			
        			for(j=0;j<rtnpts.length;j++) {
        				//Aggiungo i punti

        				contentString = '<div style="text-align:left;width:250px;height:200px;color:black;"><b>'+rtnpts[j].ABAN8+' - '+rtnpts[j].ABALPH+'</b><br>Tipo: '+decodTipoClie(rtnpts[j].ABAT1)+'<br><span class="indClienteMarker"><br>'+rtnpts[j].ALADD2+','+rtnpts[j].ALCTY1+','+rtnpts[j].ALADDS+'</span><br>Fatturato '+y+': '+rtnpts[j].fatt1+'<br>Fatturato '+y1+': '+rtnpts[j].fatt2+'<br>Fatturato '+y2+': '+rtnpts[j].fatt3+'<br><br><input class="navbutton addcliente" type="button" onclick="addGiro(\''+rtnpts[j].ABAN8+'\')" value="Aggiungi" /></div>';
						inBoundString = '<tr><td><a class="btn btn-xs btn-primary addcliente glyphicon glyphicon-plus" href="javascript:void(\'0\');" onclick="addGiro(\''+rtnpts[j].ABAN8+'\')"></a></td><td><b>'+rtnpts[j].ABAN8+' - '+rtnpts[j].ABALPH+'</b><br><span class="indClienteMarker">'+rtnpts[j].ALADD2+',<br>'+rtnpts[j].ALCTY1+' ('+rtnpts[j].ALADDS+')</span><span class="badge tipoClieSpan">'+decodTipoClie(rtnpts[j].ABAT1)+'</span></td><td nowrap>'+y+': '+rtnpts[j].fatt1+'<br>'+y1+': '+rtnpts[j].fatt2+'<br>'+y2+': '+rtnpts[j].fatt3+'</td></tr>';	
						
						if(rtnpts[j].ABAT1=="C" || rtnpts[j].ABAT1=="CS") image = '/crud/websmart/maps/img/marker-green.png';
						if(rtnpts[j].ABAT1=="P" || rtnpts[j].ABAT1=="PS") image = '/crud/websmart/maps/img/marker-blue.png';
						if(rtnpts[j].ABAT1=="V") image = '/crud/websmart/maps/img/marker-red.png';
						
						myLatlng = new google.maps.LatLng(rtnpts[j].GLAT,rtnpts[j].GLNG);
						markers[j] = new MarkerWithLabel({
						    position: myLatlng,
						    map: map,
						    title: rtnpts[j].ABALPH,
						    html: contentString,
						    icon: image,
						    zIndex: 98,
							labelContent: rtnpts[j].ABALPH,
							labelAnchor: new google.maps.Point(22, 0),
							labelClass: "maplabel", 
							labelStyle: {opacity: 0.95}
						});
						 
						var marker = markers[j];					
						google.maps.event.addListener(marker, 'click', function () { 
							infowindow.setContent(this.html);
							infowindow.open(map, this);
						});
							
						$("#inBoundPanel").html($("#inBoundPanel").html()+inBoundString);	
						
        			}	
        			
        		}
        		 
        		$.unblockUI();
        		
        	});
		}	
	
		function secondsToTime(s){
		    var h  = Math.floor( s / ( 60 * 60 ) );
		        s -= h * ( 60 * 60 );
		    var m  = Math.floor( s / 60 );
		        s -= m * 60;
		   
		    return {
		        "h": h,
		        "m": m,
		        "s": s
		    };
		}
	 
	  
	
	  function showSteps(directionResult) {
	    var myRoute = directionResult.routes[0];
	    
		//var path = directionResult.routes[0].overview_path;
		//var boxes = routeBoxer.box(path, 10);
		//drawBoxes(boxes);
	    
	    var letter = "";
		y = new Date().getFullYear();
		y1 = y - 1;
		y2 = y - 2;	    
	     
	    for (var i = 0; i < myRoute.legs.length; i++) {
	
			if (i == 0) {
				var icon = "/crud/websmart/maps/img/start_point_icon.png"; 
			} else {
				var icon = {
				    path: "M-20,0a20,20 0 1,0 40,0a20,20 0 1,0 -40,0",
				    fillColor: "#"+markerColors[i],
				    fillOpacity: 1,
				    anchor: new google.maps.Point(0, 0),
				    strokeWeight: 1,
				    scale: 0.5
				}
			}
			
			/*
			var marker = new google.maps.Marker({
				position: myRoute.legs[i].start_location, 
				map: map,
				icon: icon
			});
			*/

			/**/ 
			curlat = myRoute.legs[i].start_location.lat();
			curlng = myRoute.legs[i].start_location.lng();			
			var currag = "";
			var curind = "";
			var curcodcli = "";
			var curaddr = "";
			var curfat1 = "";
			var curfat2 = "";
			var curfat3 = "";
			for(j=0;j<loclat.length && currag=="";j++) {
			  	if(isSimilarNumbers(loclat[j],curlat) && isSimilarNumbers(loclng[j],curlng))
			  	{
			  		currag = rag[j];
			  		curind = ind[j];	
			  		curcodcli = codcli[j];
			  		curaddr = ind[j];
			  		curfat1 = fat1[j];
			  		curfat2 = fat2[j];
			  		curfat3 = fat3[j];
			  	}
			}			
			
			contentString = '<div style="text-align:left;width:250px;height:200px;color:black;"><b>'+curcodcli+' - '+currag+'</b><br><span class="indClienteMarker"><br>'+curaddr+'</span><br>Fatturato '+y+': '+curfat1+'<br>Fatturato '+y1+': '+curfat2+'<br>Fatturato '+y2+': '+curfat3+'</div>';
			
			stepMarkers[j] = new MarkerWithLabel({
			    position: myRoute.legs[i].start_location,
			    map: map,
			    title: currag,
			    addr: curaddr,
			    html: contentString,
			    icon: icon,
			    zIndex: 98,
				labelContent: currag,
				labelAnchor: new google.maps.Point(22, -12),
				labelClass: "maplabel", 
				labelStyle: {opacity: 0.95}
			});	
			 	
			var marker = stepMarkers[j];					
			if(i>0) {
				google.maps.event.addListener(marker, 'click', function () { 
					infowindow.setContent(this.html);
					infowindow.open(map, this);
				});			 	
			} else {
				attachInstructionText(marker, myRoute.legs[i].start_address);	
			} 		
			/**/
 
			markerArray.push(marker);
	    } 
	     
	    var icon = "/crud/websmart/maps/img/start_point_icon.png"; 
	    var marker = new google.maps.Marker({
	      position: myRoute.legs[(i-1)].end_location, 
	      map: map,
	      icon: icon
	    });
	    attachInstructionText(marker, myRoute.legs[(i-1)].end_address);
	    markerArray.push(marker);    
	    
	    //google.maps.event.trigger(markerArray[0], "click");
	  }
	  
	  function attachInstructionText(marker, text) {
	  	
	    google.maps.event.addListener(marker, 'click', function() {
	      stepDisplay.setContent(text);
	      stepDisplay.open(map, marker);
	    });
	  }
	
	function roundNumber(number,decimal_points) {
		if(!decimal_points) return Math.round(number);
		if(number == 0) {
			var decimals = "";
			for(var i=0;i<decimal_points;i++) decimals += "0";
			return "0."+decimals;
		}
	
		var exponent = Math.pow(10,decimal_points);
		var num = Math.round((number * exponent)).toString();
		return num.slice(0,-1*decimal_points) + "." + num.slice(-1*decimal_points)
	}
	  
	function isSimilarNumbers(num1,num2) {
		//sono uguali
		if(num1==num2) return true;
		//provo a fare il round a 3 decimali
		if(roundNumber(num1,3)==roundNumber(num2,3)) return true;
		//provo a con una soglia e round
		delta = 0.001;
		if(roundNumber(num1+delta,3)==roundNumber(num2,3)) return true;
		if(roundNumber(num1-delta,3)==roundNumber(num2,3)) return true;
		
		return false;
	}  
	
	function stampaPagina() {
		self.print();	
	}
	
	
	
	function calcRoute(batches, directionsService, directionsDisplay) {
		var combinedResults;
		var unsortedResults = [{}]; // to hold the counter and the results themselves as they come back, to later sort
		var directionsResultsReturned = 0;
		
		for (var k = 0; k < batches.length; k++) {
			var lastIndex = batches[k].length - 1;
			var start = batches[k][0].location;
			var end = batches[k][lastIndex].location;
			
			// trim first and last entry from array
			var waypts = [];
			waypts = batches[k];
			waypts.splice(0, 1);
			waypts.splice(waypts.length - 1, 1);
			
			
			var request = {
				origin : start,
				destination : end,
				waypoints : waypts,
				travelMode : google.maps.DirectionsTravelMode.DRIVING,
		        language: 'it',
		        avoidHighways: false,
		        avoidTolls: 
SEGDTA;
 if($GINOPE=="S") echo "true"; else echo "false"; 
		echo <<<SEGDTA
,
		        optimizeWaypoints: false,		
			};
	
	
			(function (kk) {
				
				//sleep qui
				
				directionsService.route(request, function (result, status) {
					if (status == window.google.maps.DirectionsStatus.OK) {
						
						var unsortedResult = {
							order : kk,
							result : result
						};
						unsortedResults.push(unsortedResult);
						
						directionsResultsReturned++;
						
						if (directionsResultsReturned == batches.length) // we've received all the results. put to map
						{
							// sort the returned values into their correct order
							unsortedResults.sort(function (a, b) {
								return parseFloat(a.order) - parseFloat(b.order);
							});
							var count = 0;
							for (var key in unsortedResults) {
								if (unsortedResults[key].result != null) {
									if (unsortedResults.hasOwnProperty(key)) {
										if (count == 0) // first results. new up the combinedResults object
											combinedResults = unsortedResults[key].result;
										else {
											// only building up legs, overview_path, and bounds in my consolidated object. This is not a complete
											// directionResults object, but enough to draw a path on the map, which is all I need
											combinedResults.routes[0].legs = combinedResults.routes[0].legs.concat(unsortedResults[key].result.routes[0].legs);
											combinedResults.routes[0].overview_path = combinedResults.routes[0].overview_path.concat(unsortedResults[key].result.routes[0].overview_path);
											
											combinedResults.routes[0].bounds = combinedResults.routes[0].bounds.extend(unsortedResults[key].result.routes[0].bounds.getNorthEast());
											combinedResults.routes[0].bounds = combinedResults.routes[0].bounds.extend(unsortedResults[key].result.routes[0].bounds.getSouthWest());
										}
										count++;
									}
								}
							}
							directionsDisplay.setDirections(combinedResults);
							
						    computeTotalDistance(combinedResults);
					      	showSteps(combinedResults);
					      	//$.unblockUI();
					        
						}
											
					}
				});
			})(k);
			
			
			 
			
	
			
		}
	}
	  
	  
	function callback(results, status) {
		if (status == google.maps.places.PlacesServiceStatus.OK) {
		  for (var i = 0; i < results.length; i++) {
		    createMarker(results[i]);
		  }
		}
	}
	  
	function createMarker(place) {
		//var icon = "//app.bigblue.it/websmart/bigblue5/img/hotel.png";
		var placeLoc = place.geometry.location;
		var marker = new google.maps.Marker({
		  map: map,
		  position: place.geometry.location,
		  icon: icon
		});
		
		google.maps.event.addListener(marker, 'click', function() {
		  infowindow.setContent(place.name+"<br>"+place.vicinity);
		  infowindow.open(map, this);
		});
	}  
	 
	function addGiro(ABAN8) { 
		$.blockUI({ message: '<h1>Loading</h1>', baseZ: 9999 });
		url = "?task=addGiro&ABAN8="+ABAN8;
		$.get(url,function(data){ 
			document.location.href = "?task=calcGiroV&ricalcInBound=N&GIVITU=$GIVITU&GIMIFA=$GIMIFA&GIRARI=$GIRARI&GIUSER=$GIUSER&GITIPA=$GITIPA&GITIAR=$GITIAR&GINOPE=$GINOPE&GITIPO=$GITIPO&GIPAUS=$GIPAUS&GIMECL=$GIMECL&GIORDI=$GIORDI&GIPACL=$GIPACL&GIARCL=$GIARCL&GITPCL_C=$GITPCL_C&GITPCL_CS=$GITPCL_CS&GITPCL_P=$GITPCL_P&GITPCL_PS=$GITPCL_PS&GITPCL_V=$GITPCL_V";
		});	
	} 
	
	function dltGiro(ABAN8) { 
		$.blockUI({ message: '<h1>Loading</h1>', baseZ: 9999 });
		url = "?task=dltGiro&ABAN8="+ABAN8;
		$.get(url,function(data){ 
			document.location.href = "?task=calcGiroV&ricalcInBound=N&GIVITU=$GIVITU&GIMIFA=$GIMIFA&GIRARI=$GIRARI&GIUSER=$GIUSER&GITIPA=$GITIPA&GITIAR=$GITIAR&GINOPE=$GINOPE&GITIPO=$GITIPO&GIPAUS=$GIPAUS&GIMECL=$GIMECL&GIORDI=$GIORDI&GIPACL=$GIPACL&GIARCL=$GIARCL&GITPCL_C=$GITPCL_C&GITPCL_CS=$GITPCL_CS&GITPCL_P=$GITPCL_P&GITPCL_PS=$GITPCL_PS&GITPCL_V=$GITPCL_V";
		});	
	} 
	
	function switchLabel() {
		if($("#switch-label").is(":checked")) { $(".maplabel").css("visibility","visible"); }
		else $(".maplabel").css("visibility","hidden");
	}
	

	 
	</script>
    
</body>
</html>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "segaddmarker")
	{

		echo <<<SEGDTA
contentString = '<div style="text-align:left;width:250px;height:200px;color:black;"><b>$ABAN8 - 
SEGDTA;
 echo str_replace("'","\'",$ABALPH); 
		echo <<<SEGDTA
</b><br><span class="indClienteMarker"><br>
SEGDTA;
 echo str_replace("'","\'",$ALADD2); 
		echo <<<SEGDTA
,
SEGDTA;
 echo str_replace("'","\'",$ALCTY1); 
		echo <<<SEGDTA
,
SEGDTA;
 echo str_replace("'","\'",$ALADDS); 
		echo <<<SEGDTA
</span><br>Fatturato 20$annoAttuale: $fatt1<br>Fatturato 20
SEGDTA;
 echo ($annoAttuale - 1); 
		echo <<<SEGDTA
: $fatt2<br>Fatturato 20
SEGDTA;
 echo ($annoAttuale - 2); 
		echo <<<SEGDTA
: $fatt3<br><br><input class="navbutton addcliente" type="button" onclick="addGiro(\'$ABAN8\')" value="Aggiungi" /></div>';
 
myLatlng = new google.maps.LatLng($CCLATI,$CCLONG);
markers[jm] = new MarkerWithLabel({
    position: myLatlng,
    map: map,
    title: '
SEGDTA;
 echo str_replace(array("\\","'"),array("","\'"),$ABALPH); 
		echo <<<SEGDTA
',
    html: contentString,
    icon: image,
    zIndex: 98,
	labelContent: '
SEGDTA;
 echo str_replace(array("\\","'"),array("","\'"),$ABALPH); 
		echo <<<SEGDTA
',
	labelAnchor: new google.maps.Point(22, 0),
	labelClass: "maplabel", 
	labelStyle: {opacity: 0.95}
});
 
var marker = markers[jm];					
google.maps.event.addListener(marker, 'click', function () { 
	infowindow.setContent(this.html);
	infowindow.open(map, this);
});

jm++;


SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "modalarrivo")
	{

		echo <<<SEGDTA
<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="largeModal" aria-hidden="true" id="modal-arrivo">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel">Punto di arrivo</h4>
      </div>
      <div class="modal-body" id="modal-arrivo-body">

      </div>
      <div class="modal-footer"> 
        <input type="button" class="btn btn-primary" onclick="$.blockUI(); $('#arrivoForm').submit();" value="Salva" /> 
        <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
      </div>
    </div>
  </div>
</div>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "formarrivo")
	{

		echo <<<SEGDTA
 <form name="arrivoForm" id="arrivoForm" method="post" action="mappa-clienti.php">
<input type="hidden" name="task" value="addArrivo1" />

	  <div class="form-group">
        <label for="ARRIND">Indirizzo:</label>
        <div>
          <input type="text" id="ARRIND" class="form-control" name="ARRIND" value="">
          <span class="err" id="err_ARRIND"></span>
        </div>
      </div>
      
	  <div class="form-group">
        <label for="ARRLOC">Localit&agrave;:</label>
        <div>
          <input type="text" id="ARRLOC" class="form-control" name="ARRLOC" value="">
          <span class="err" id="err_ARRLOC"></span>
        </div>
      </div>
      
	  <div class="form-group">
        <label for="ARRPRO">Provincia</label>
        <div>
          <input type="text" id="ARRPRO" class="form-control" name="ARRPRO" value="" maxlength="50">
          <span class="err" id="err_ARRPRO"></span>
        </div>
      </div>
 
	  <div class="form-group">
        <label for="ARRNAZ">Nazione</label>
        <div>
          <input type="text" id="ARRNAZ" class="form-control" name="ARRNAZ" value="" maxlength="50">
          <span class="err" id="err_ARRNAZ"></span>
        </div>
      </div> 
    
</form>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "darrivo")
	{

		echo <<<SEGDTA
<tr>
	<td style="width:10%">
		<a class="btn btn-xs btn-danger glyphicon glyphicon-trash" onclick="dltArrivo('$RRN_ARR')"></a>  
	</td>
	<td style="width:10%" valign="top"><input type="radio" name="GITIAR" value="$ARRPRG"  />
	<td style="width:25%" valign="top">$ARRIND</td>
	<td style="width:25%" valign="top">$ARRLOC</td>
	<td style="width:20%" valign="top">$ARRPRO</td>
	<td style="width:10%" valign="top">$ARRNAZ</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "carrivo")
	{

		echo <<<SEGDTA
<tr>
	<td> </td>
	<td><input type="radio" name="GITIAR" value="cliente" /></td>
	<td colspan="3"><div id="divClientiArrivo"></div></td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "modalpartenza")
	{

		echo <<<SEGDTA
<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="largeModal" aria-hidden="true" id="modal-partenza">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel">Punto di partenza</h4>
      </div>
      <div class="modal-body" id="modal-partenza-body">

      </div>
      <div class="modal-footer"> 
        <input type="button" class="btn btn-primary" onclick="$.blockUI(); $('#partenzaForm').submit();" value="Salva" /> 
        <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
      </div>
    </div>
  </div>
</div>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "formpartenza")
	{

		echo <<<SEGDTA
<form name="partenzaForm" id="partenzaForm" method="post" action="mappa-clienti.php">
<input type="hidden" name="task" value="addPartenza1" />

	  <div class="form-group">
        <label for="PARIND">Indirizzo:</label>
        <div>
          <input type="text" id="PARIND" class="form-control" name="PARIND" value="">
          <span class="err" id="err_PARIND"></span>
        </div>
      </div>
      
	  <div class="form-group">
        <label for="PARLOC">Localit&agrave;:</label>
        <div>
          <input type="text" id="PARLOC" class="form-control" name="PARLOC" value="">
          <span class="err" id="err_PARLOC"></span>
        </div>
      </div>
      
	  <div class="form-group">
        <label for="PARPRO">Provincia</label>
        <div>
          <input type="text" id="PARPRO" class="form-control" name="PARPRO" value="" maxlength="50">
          <span class="err" id="err_PARPRO"></span>
        </div>
      </div>

	  <div class="form-group">
        <label for="PARNAZ">Nazione</label>
        <div>
          <input type="text" id="PARNAZ" class="form-control" name="PARNAZ" value="" maxlength="50">
          <span class="err" id="err_PARNAZ"></span>
        </div>
      </div>
    
</form>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dpartenza")
	{

		echo <<<SEGDTA
<tr>
	<td style="width:10%">
		<a class="btn btn-xs btn-danger glyphicon glyphicon-trash" onclick="dltPartenza('$RRN_PAR')"></a>  
	</td>
	<td style="width:10%" valign="top"><input type="radio" name="GITIPA" value="$PARPRG"  />
	<td style="width:25%" valign="top">$PARIND</td>
	<td style="width:25%" valign="top">$PARLOC</td>
	<td style="width:20%" valign="top">$PARPRO</td>
	<td style="width:10%" valign="top">$PARNAZ</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "cpartenza")
	{

		echo <<<SEGDTA
<tr>
	<td> </td>
	<td><input type="radio" name="GITIPA" value="cliente" /></td>
	<td colspan="3"><div id="divClientiPartenza"></div></td>
</tr>
SEGDTA;
		return;
	}

		// If we reach here, the segment is not found
		echo("Segment $xlSegmentToWrite is not defined! ");
	}

	// return a segment's content instead of outputting it to the browser
	function getSegment($xlSegmentToWrite, $segmentVars=array())
	{
		ob_start();
		
		$this->writeSegment($xlSegmentToWrite, $segmentVars);
		
		return ob_get_clean();
	}
	
	function __construct()
	{
		
		$this->pf_liblLibs[1] = 'JRGDTA94C';
		$this->pf_liblLibs[2] = 'BCD_DATIV2';
		
		parent::__construct();

		$this->pf_scriptname = 'mappa-clienti.php';
		$this->pf_wcm_set = 'PRODUZIONE';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		
		// Last Generated CRC: 518CE9F5 A3B9A124 C1C49FF6 C0476FE2
		// Last Generated Date: 2024-07-17 15:22:13
		// Path: mappa-clienti.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'mappa_clienti');?>