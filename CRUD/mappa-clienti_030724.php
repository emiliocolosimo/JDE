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

class mappa_clienti extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'page' => 1,
		'listSize' => 20,
		'filters' => array('ABAN8' => '', 'ABALPH' => '', 'ALCTY1' => '', 'ALCOUN' => '', 'ALADDS' => '', 'ALCTR' => '', 'filtGeo' => '')
	);
	
	
	protected $keyFields = array('ABAN8');
	protected $uniqueFields = array('ABAN8');
	protected $repPartenza = "";
	protected $repArrivo = "";
	//protected $googleApiKey = "AIzaSyDXk4p8Uy7yTGrALzm529PO8z29qA2gMco"; //bcd
	protected $googleApiKey = "AIzaSyCJnf3PhzjSRt3G76DF62K3CnsAXR-uoS8"; 
	protected $googleBatchApiKey = "AIzaSyAxfATLADr7WFVWvIJLedJI_ngBILSZtbA"; //
	
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
			  
		}
		
		session_write_close();
		
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
		 
		echo json_encode($row);
		
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
	
		$ne_lat = xl_get_parameter("ne_lat");
		$ne_lng = xl_get_parameter("ne_lng");
		$sw_lat = xl_get_parameter("sw_lat");
		$sw_lng = xl_get_parameter("sw_lng");
		  
		$sep3a = "";
		$retJson = ""; 
		 
		$retArray = array();
		$x = 0; 
		 
		$selString = "SELECT * 
		FROM BCD_DATIV2.RGPGPI00F 
		WHERE GPPROG = ".$GTPROG." 
		AND GPAN8 NOT IN (
			SELECT GTCLIE FROM BCD_DATIV2.RGPGIT00F 
			WHERE GTPROG = ".$GTPROG." 
		)
		ORDER BY GPFAT1 DESC 
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
	  		$tmpArray["fatt1"] = $GPFAT1; 
	  		$tmpArray["fatt2"] = $GPFAT2; 
	  		$tmpArray["fatt3"] = $GPFAT3; 
	  	 
	  		$retArray[$x] = $tmpArray;
	  		 
	  		$x++;
	  		 
	  		$retJson.= $sep3a.json_encode($tmpArray);
	  		$sep3a = ", ";
	  		
		}	
		  
		 
		if($x>0) echo '['.$retJson.']';	
		else echo "[{\"status\":\"NONE\"}]";			
		
	}

	protected function savPointsInBound() {
	
		$GTPROG = $_SESSION["GTPROG"];
	
		$ne_lat = xl_get_parameter("ne_lat");
		$ne_lng = xl_get_parameter("ne_lng");
		$sw_lat = xl_get_parameter("sw_lat");
		$sw_lng = xl_get_parameter("sw_lng");
		  
		$sep3a = "";
		$retJson = ""; 
		 
		$retArray = array();
		$x = 0; 
		 
		$selString = "SELECT ABAN8  
		FROM JRGDTA94C.F0101  
		inner join JRGDTA94C.F0116 on JRGDTA94C.F0101.ABAN8 = JRGDTA94C.F0116.ALAN8 
		inner join BCD_DATIV2.CLICRD0F on JRGDTA94C.F0101.ABAN8 = BCD_DATIV2.CLICRD0F.CCAN8 AND CCSTAT = 'OK' 
		WHERE ABAN8 NOT IN (
			SELECT GTCLIE 
			FROM BCD_DATIV2.RGPGIT00F 
			WHERE GTPROG = ".$GTPROG."
		) 
		and CAST(CCLATI as DOUBLE) >= ".trim($sw_lat)." and CAST(CCLONG as DOUBLE) >= ".trim($sw_lng)."  
		and CAST(CCLATI as DOUBLE) <= ".trim($ne_lat)." and CAST(CCLONG as DOUBLE) <= ".trim($ne_lng)." 
		";
		 
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
			foreach(array_keys($row) as $key)
			{ 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}	
			
			$decCliente = $this->decodCliente($ABAN8);
			 
	  		$fatt1 = 0;
	  		$fatt2 = 0;
	  		$fatt3 = 0;
	  		$fatturato = $this->calcFatturato($ABAN8);
	  		if($fatturato) {
	  			if(isset($fatturato["FATT1"])) $fatt1 = round($fatturato["FATT1"]);
	  			if(isset($fatturato["FATT2"])) $fatt2 = round($fatturato["FATT2"]);
	  			if(isset($fatturato["FATT3"])) $fatt3 = round($fatturato["FATT3"]);
	  		}
	  		
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
			
			$query = "INSERT INTO BCD_DATIV2.RGPGPI00F 
			(GPPROG,GPAN8,GPALPH,GPCTY1,GPCOUN,GPADDZ,GPADD2,GPADDS,GPLAT,GPLNG,GPFAT1,GPFAT2,GPFAT3) VALUES 
			(:GPPROG,:GPAN8,:GPALPH,:GPCTY1,:GPCOUN,:GPADDZ,:GPADD2,:GPADDS,:GPLAT,:GPLNG,:GPFAT1,:GPFAT2,:GPFAT3) WITH NC
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
		
		$this->writeSegment("segMappa", array_merge(get_object_vars($this), get_defined_vars()));
	}

	protected function dspGCoords($GTPROG,$GIPACL,$GIARCL) {
	 
		//partenza 
		$repPartenza = "";
		
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
			
		echo "stops[n] = origin;\n";
		echo "tsp.addWaypoint(origin, addWaypointCallback);\n";
		echo "n++\n";
	
	 
		$totTtc= 0;
		$cntto = 0;
		$count = 1;
		
		$selString = "SELECT GTCLIE 
		FROM BCD_DATIV2.RGPGIT00F 
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
				$decCliente = $this->decodCliente($ABAN8);
				$lat = $decCliente["CCLATI"];
				$lng = $decCliente["CCLONG"];
			 
				$this->writeSegment("segMarker", array_merge(get_object_vars($this), get_defined_vars()));
				$totTtc++;
				$count++;
	 
				echo "tmpltln = new google.maps.LatLng(".trim($lat).",".trim($lng).");\n";
				echo "tsp.addWaypoint(tmpltln, addWaypointCallback);\n";
				echo "loclat[n] = tmpltln.lat();\n";
				echo "loclng[n] = tmpltln.lng();\n";
				$ABAN8 = str_replace("'","",$decCliente["ABAN8"]);
				echo "rag[n] = '".trim(str_replace("'","",$decCliente["ABALPH"]))."';\n";
				$ALADD2 = trim(str_replace("'","",$decCliente["ALADD2"]));
				$ALCTY1 = trim(str_replace("'","",$decCliente["ALCTY1"]));
				$ALADDS = trim(str_replace("'","",$decCliente["ALADDS"]));
				 
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
		
		$repPartenza.= "<strong>".trim(htmlspecialchars($decCliente["ABALPH"],ENT_QUOTES,"ISO-8859-1")). "</strong><br>";
		
		
		 
		echo "stops[n] = destination;\n";
		echo "tsp.addWaypoint(destination, addWaypointCallback);\n";
		echo "n++;\n";	
		
		$this->repPartenza = $repPartenza;
		$this->repArrivo = $repArrivo;
			
	}

	protected function decodCliente($ABAN8) {
		$ABAN8 = (int) $ABAN8;
		
		$query = "SELECT ABAN8, ABALPH, 
		ALADD2, ALADD3, ALADD4, ALADDZ, 
		ALCTY1, ALCOUN, ALADDS, ALCTR, 
		CCLATI, CCLONG 
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
		
		echo "<table class=\"table table-condensed table-striped\">";
		
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
		FETCH FIRST 100 ROWS ONLY 
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
			
			if($x>=100) break;
			 
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
			 
			usleep(250000);
			//250000
			
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
			$stmt->bindValue(':ABAN8', $this->programState['filters']['ABAN8'], PDO::PARAM_INT);
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
	 
		
		
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildSelectString()
	{
		$selString = "SELECT JRGDTA94C.F0101.ABAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F0116.ALADD2, 
		JRGDTA94C.F0116.ALADD3, JRGDTA94C.F0116.ALADD4, JRGDTA94C.F0116.ALADDZ, JRGDTA94C.F0116.ALCTY1, 
		JRGDTA94C.F0116.ALCOUN, JRGDTA94C.F0116.ALADDS, JRGDTA94C.F0116.ALCTR, COALESCE(CCSTAT, '') AS CCSTAT 
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
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.F0101.ABAN8) = :ABAN8';
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
	</style>
    
    
  </head>
  <body class="display-list">
  
SEGDTA;

  	$this->writeSegment("ricCoord");
  
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
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ABAN8">Address Number</label>
                  <input id="filter_ABAN8" class="form-control" type="text" name="filter_ABAN8" maxlength="8" value="{$programState['filters']['ABAN8']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ABALPH">Alpha Name</label>
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
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ALADDS">State</label>
                  <input id="filter_ALADDS" class="form-control" type="text" name="filter_ALADDS" maxlength="3" value="{$programState['filters']['ALADDS']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ALCTR">Country</label>
                  <input id="filter_ALCTR" class="form-control" type="text" name="filter_ALCTR" maxlength="3" value="{$programState['filters']['ALCTR']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-4">
                  <label> </label>
				  <div class="col-md-10">
					<input type="radio" name="filter_filtGeo" value="1" 
SEGDTA;
 if($this->programState['filters']['filtGeo']=="1") echo 'checked'; 
		echo <<<SEGDTA
> Solo geolocalizzati 
					<input type="radio" name="filter_filtGeo" value="2" 
SEGDTA;
 if($this->programState['filters']['filtGeo']=="2") echo 'checked'; 
		echo <<<SEGDTA
> Solo non geolocalizzati 
					<input type="radio" name="filter_filtGeo" value="" 
SEGDTA;
 if($this->programState['filters']['filtGeo']=="") echo 'checked'; 
		echo <<<SEGDTA
> Tutti 
				</div>
                </div>
              </div>
              <div class="row">
                <div class="col-sm-2">
                  <input id="filter-button" class="btn btn-primary filter" type="submit" value="Filter" />
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
	          <table id="list-table" class="main-list table table-striped" cellspacing="0">
	            <thead>
	              <tr class="list-header">
	                <th></th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ABAN8&amp;rnd=$rnd">Address Number</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ABALPH&amp;rnd=$rnd">Alpha Name</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALADD2&amp;rnd=$rnd">Address Line 2</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALADD3&amp;rnd=$rnd">Address Line 3</a>
	                </th>
	                <th>
	                  <a class="list-header" href="$pf_scriptname?sidx=ALADD4&amp;rnd=$rnd">Address Line 4</a>
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
" class="btn btn-xs btn-success cliDrag glyphicon glyphicon-plus" ABAN8="$ABAN8" title="Click o trascina per aggiungere"></a>
 	  <a style="
SEGDTA;
 if($CCSTAT=="OK") echo 'display:none'; 
		echo <<<SEGDTA
" class="btn btn-xs btn-danger glyphicon glyphicon-question-sign" onclick="ricCoord('$ABAN8');" title="Ricava coordinate"></a>
  </td>
  <td class="text num">$ABAN8</td>
  <td class="text">$ABALPH</td>
  <td class="text">$ALADD2</td>
  <td class="text">$ALADD3</td>
  <td class="text">$ALADD4</td>
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
 
	<div class="cliDrop" id="divCliDrop"></div>
	<br>
	<input type="button" class="btn btn-xs btn-info " onclick="sbmCalcGiro();" value="Calcola"/>
 	<input type="button" class="btn btn-xs btn-info " onclick="document.location.href='?task=newGiroV'" value="Nuovo"/>&nbsp;
  	
	<form name="formGiroComposer" id="formGiroComposer" class="smart-form" method="post" action="mappa-clienti.php">
	<input type="hidden" name="task" id="formGiroTask" value="calcGiroV" />
 
 	<div class="row">
		<div class="col-sm-12">
			<div class="col-sm-9"><h5>Punto di partenza</h5></div>
			<div class="col-sm-3" style="text-align:right;padding-top:5px;"> </div>
		</div> 
		<div id="divClientiPartenza"></div>
	</div>
	
	<div class="row">
		<div class="col-sm-12">
			<div class="col-sm-9"><h5>Punto di arrivo</h5></div>
			<div class="col-sm-3" style="text-align:right;padding-top:5px;"> </div>
		</div> 
		<div id="divClientiArrivo">
		</div> 
	</div>
  	 
    <div class="row">
          <div class="form-group col-xs-12">
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
          <div class="form-group col-xs-12">
            <label for="GITIPO">Calcola percoso ottimale:</label> 
			<select class="form-control" name="GITIPO">
				<option value="time">Pi&ugrave; veloce</option>
				<option value="distance">Pi&ugrave; breve</option>
			</select> 
          </div> 
     </div> 
      
       
	</form>	
</div>

</div>

<!-- Supporting JavaScript -->
<script type="text/javascript">
	// Focus the first input on page load
	jQuery(function() {
		jQuery("input:enabled:first").focus();
		
		$("#div_ricCoord").dialog({
			autoOpen: false,
			modal: true,
			width: 900,
			position: 'center',
			resizable: false,
			title: 'Ricava coordinate'
		});				
		
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
	
 
		lstGiro();
		lstSelPartenze(); 
		lstSelArrivi();
		
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
    
	function lstSelPartenze() {
		url = "?task=lstSelPartenze";
		$.get(url,function(data){
			$("#divClientiPartenza").html(data);
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
 		
 		jGITIPA = "cliente";
 		jGIPACL = $("#GIPACL").val();
 		jGITIAR = "cliente";
 		jGIARCL = $("#GIARCL").val();
 		  
		if(jGITIPA=="" || jGITIPA==null || (jGIPACL=="" && jGITIPA=="cliente") || jGITIAR=="" || jGITIAR==null || (jGIARCL=="" && jGITIAR=="cliente") 
		|| jGIPACL == jGIARCL 
		) {
			alert("Selezionare un punto di arrivo e un punto di partenza");	
			return;	
		}	
		
		$("#formGiroComposer").submit();
		
		
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
        
			<input type="button" value="Indietro" class="btn btn-xs btn-warning" onclick="document.location.href='?task=default'" />&nbsp;
			<!--<input type="button" value="Stampa" class="btn btn-xs btn-success" onclick="window.print();" />&nbsp;-->
			
			<input type="button" value="Esporta mappa" class="btn btn-xs btn-success" onclick="getRouteLatLngs();" />
			
			
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
				suppressMarkers : true	
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

		    	$this->dspGCoords($GTPROG,$GIPACL,$GIARCL);
		    
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
				
			
			document.getElementById('totalTravelDistance').innerHTML = total + ' km';
			document.getElementById('totalTravelDuration').innerHTML = durationStr;
			//document.getElementById('totalDuration').innerHTML = durationStrTotal;
			
		}
	
		function getPointsInBound() {
			 
	        var image = '/crud/websmart/maps/img/google-maps-marker-red.png';
			const icon = 

        	url = "?task=getPointsInBound&ts="+Date.now();
        	$.getJSON(url,function(rtnpts) {         		
        		
        		if(rtnpts[0].status!="ERR" && rtnpts[0].status!="NONE") {
        			
        			for(j=0;j<rtnpts.length;j++) {
        				//Aggiungo i punti

        				contentString = '<div style="text-align:left;width:250px;height:200px;color:black;"><b>'+rtnpts[j].ABAN8+' - '+rtnpts[j].ABALPH+'</b><br><span class="indClienteMarker"><br>'+rtnpts[j].ALADD2+','+rtnpts[j].ALCTY1+','+rtnpts[j].ALADDS+'</span><br>Fatturato '+y+': '+rtnpts[j].fatt1+'<br>Fatturato '+y1+': '+rtnpts[j].fatt2+'<br>Fatturato '+y2+': '+rtnpts[j].fatt3+'<br><br><input class="navbutton addcliente" type="button" onclick="addGiro(\''+rtnpts[j].ABAN8+'\')" value="Aggiungi" /></div>';
						inBoundString = '<tr><td valign="top"><a class="btn btn-sm btn-success addcliente glyphicon glyphicon-plus" type="button" href="javascript:void(\'0\');" onclick="addGiro(\''+rtnpts[j].ABAN8+'\')"></a></td><td><b>'+rtnpts[j].ABAN8+' - '+rtnpts[j].ABALPH+'</b><br><span class="indClienteMarker">'+rtnpts[j].ALADD2+',<br>'+rtnpts[j].ALCTY1+' ('+rtnpts[j].ALADDS+')</span></td><td nowrap>Fatturato '+y+': '+rtnpts[j].fatt1+'<br>Fatturato '+y1+': '+rtnpts[j].fatt2+'<br>Fatturato '+y2+': '+rtnpts[j].fatt3+'</td></tr>';	

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
		
		// Last Generated CRC: 775576F1 E50FA060 DABAEB3B FBB020A1
		// Last Generated Date: 2024-06-28 11:36:32
		// Path: mappa-clienti.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'mappa_clienti');?>