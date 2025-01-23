<?php
if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

//	Program Name:		nonconf02.php
//	Program Title:		Non conformità - Elenco non conformità
//	Created by:			matti
//	Template family:	Responsive
//	Template name:		Page at a Time.tpl
//	Purpose:        	Maintain a database file using embedded SQL. Supports options for add, change, delete and display.
//	Program Modifications:
 
 
/*
	to do:
	- in inserimento ordine legato controllare che l'ordine esista
	  controllare anche che appartenga allo stesso cliente / fornitore
	- dialog di ricerca dell'ordine? 
*/ 
 
require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');
require_once('websmart/v13.2/include/en-US/WSRequiredValidator.php');
require_once('websmart/v13.2/include/en-US/WSNumericValidator.php');

class nonconf02 extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'page' => 1,
		'listSize' => 20,
		'filters' => array('NCPROG' => '', 'NCTPCF' => '', 'NCAB8' => '', 'NCRGCF' => '', 'NCDCTO' => '', 'NCDOCO' => '', 'NCLNID' => '', 'NCLITM' => '', 'NCLOTN' => '', 'NCLOT2' => '')
	);
	
	
	protected $keyFields = array('NCPROG');
	protected $uniqueFields = array('NCPROG');
	
	public function runMain()
	{
		error_reporting(E_ALL);
		ini_set('display_errors',1);
		
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
		
		// Fetch the program state
		$this->getState();
		
		$this->formFields = array(
			"NCPROG" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCDTEM" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCTPCF" => array("validators"=> array("WSRequired")),
			"NCAB8" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCRGCF" => array("validators"=> array("WSRequired")),
			"NCDCTO" => array("validators"=> array("WSRequired")),
			"NCDOCO" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCLNID" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCLITM" => array("validators"=> array("WSRequired")),
			"NCLOTN" => array("validators"=> array("WSRequired")),
			"NCLOT2" => array("validators"=> array("WSRequired")),
			"NCPZCO" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCPPCO" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCFORN" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCRICL" => array("validators"=> array("WSRequired")),
			"NCDTRC" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCDIFE" => array("validators"=> array("WSRequired")),
			"NCRISO" => array("validators"=> array("WSRequired")),
			"NCSOEF" => array("validators"=> array("WSRequired")),
			"NCACEF" => array("validators"=> array("WSRequired")),
			"NCCHIU" => array("validators"=> array("WSRequired")),
			"NCDTCH" => array("validators"=> array("WSRequired","WSNumeric")),
			"NCNOTE" => array("validators"=> array("WSRequired")),
			"NCRIFE" => array("validators"=> array("WSRequired","WSNumeric")));
		$this->optionalIndicator = "(Optional)";
		
		// Run the specified task
		switch ($this->pf_task)
		{
			// Display the main list
			case 'default':
			$this->displayList();
			break;
			
			// Record display option
			case 'disp':
			$this->displayRecord();
			break;
			
			// Confirm deletion
			case 'delconf':
			$this->displayRecord();
			break;
			
			// Perform deletion
			case 'del':
			$this->deleteRecord();
			break;
			
			// Start the add process
			case 'beginaddcli': 
			$this->beginAddCli();
			break;
			
			// Complete record add
			case 'endaddcli':
			$this->endAddCli();
			break;
			
			// Start the change process
			case 'beginchangecli':
			$this->beginChangeCli();
			break;
			
			// Complete the change process
			case 'endchangecli':
			$this->endChangeCli();
			break;
			
			// Start the add process
			case 'beginaddfor': 
			$this->beginAddFor();
			break;
			
			// Complete record add
			case 'endaddfor':
			$this->endAddFor();
			break;			
			
			// Start the change process
			case 'beginchangefor':
			$this->beginChangeFor();
			break;
			
			// Complete the change process
			case 'endchangefor':
			$this->endChangeFor();
			break;			
			
			// Output a filtered list
			case 'filter':
			$this->filterList();
			break;
			
			case 'getNonConfCli':
			$this->getNonConfCli();
			break;	 
			
			case 'getNonConfFor':
			$this->getNonConfFor();
			break;
			
			case 'addAlleg':
			$this->addAlleg();
			break;

			case 'addAlleg1':
			$this->addAlleg1();
			break;
			
			case 'flstAlleg':
			$this->flstAlleg();
			break;	
			
			case 'dltAlleg':
			$this->dltAlleg();
			break;	
			
			case 'dspAlleg':
			$this->dspAlleg();
			break;	
			
			case 'srcOrdFor':
			$this->srcOrdFor();
			break;				
			
			case 'fltOrdFor':
			$this->fltOrdFor();
			break;	
			/*	
			case 'addOrdLeg':
			$this->addOrdLeg();
			break;

			case 'addOrdLeg1':
			$this->addOrdLeg1();
			break;
			*/
			case 'flstOrdLeg':
			$this->flstOrdLeg();
			break;	
			
			case 'dltOrdLeg':
			$this->dltOrdLeg();
			break;
			
			case 'srcOrdColCli':
			$this->srcOrdColCli();
			break;				
			
			case 'fltOrdColCli':
			$this->fltOrdColCli();
			break;	

			case 'addOrdColCli':
			$this->addOrdColCli();
			break;				
			 	
			case 'srcOrdColFor':
			$this->srcOrdColFor();
			break;				
			
			case 'fltOrdColFor':
			$this->fltOrdColFor();
			break;	

			case 'addOrdColFor':
			$this->addOrdColFor();
			break;	
			
			case 'fcalcNCQTSP':
			$this->fcalcNCQTSP();
			break;
		}
	}

	protected function decodDifetto($NCDIFE) {
		$selString = "SELECT JRGCOM94T.F0005.DRKY, JRGCOM94T.F0005.DRDL01 
		FROM JRGCOM94T.F0005 
		WHERE DRSY='55' AND DRRT='NC' AND DRKY = '".$NCDIFE."'
		ORDER BY DRDL01";
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return $row["DRDL01"];
	}
	
	protected function getNonConfCli() {
		 	
		$TIPOFILE = xl_get_parameter("TIPOFILE");
		$RRNF = xl_get_parameter("RRNF");
		
		if($TIPOFILE=="1") {  
			$selString = 'SELECT JRGDTA94C.F4211.SDKCOO, JRGDTA94C.F4211.SDDOCO, JRGDTA94C.F4211.SDDCTO, 
			JRGDTA94C.F4211.SDLNID, JRGDTA94C.F4211.SDPSN, JRGDTA94C.F4211.SDDELN, JRGDTA94C.F4211.SDDOC, 
			JRGDTA94C.F4211.SDLITM, JRGDTA94C.F4211.SDDSC1, JRGDTA94C.F4211.SDLOTN, JRGDTA94C.F4211.SDIVD, 
			JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4211.SDAN8, JRGDTA94C.F4211.SDUORG, JRGDTA94C.F4211.SDMCU    
			FROM JRGDTA94C.F4211 
			inner join JRGDTA94C.F0101 on JRGDTA94C.F4211.SDAN8 = JRGDTA94C.F0101.ABAN8  
			WHERE RRN(JRGDTA94C.F4211) = :RRNF 
			'; 
		}
		if($TIPOFILE=="2") {  
			$selString = 'SELECT JRGDTA94C.F42119.SDKCOO, JRGDTA94C.F42119.SDDOCO, JRGDTA94C.F42119.SDDCTO, 
			JRGDTA94C.F42119.SDLNID, JRGDTA94C.F42119.SDPSN, JRGDTA94C.F42119.SDDELN, JRGDTA94C.F42119.SDDOC, 
			JRGDTA94C.F42119.SDLITM, JRGDTA94C.F42119.SDDSC1, JRGDTA94C.F42119.SDLOTN, JRGDTA94C.F42119.SDIVD, 
			JRGDTA94C.F0101.ABALPH, JRGDTA94C.F42119.SDAN8, JRGDTA94C.F42119.SDUORG, JRGDTA94C.F42119.SDMCU    
			FROM JRGDTA94C.F42119 
			inner join JRGDTA94C.F0101 on JRGDTA94C.F42119.SDAN8 = JRGDTA94C.F0101.ABAN8  
			WHERE RRN(JRGDTA94C.F42119) = :RRNF 
			';  		
		}
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}			
 		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT);
 		 
		$result = $stmt->execute();
		if (!$result)
		{
			$this->dieWithPDOError($stmt);
		}			

		$row = $stmt->fetch(PDO::FETCH_ASSOC); 
		if($row) {
			foreach(array_keys($row) as $key)
			{
				$row[$key] = rtrim($row[$key]);
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
		} 		
		
		
 		
		$selString = "SELECT *
		FROM BCD_DATIV2.NONCON0F 
		WHERE 
		(
			NCDCTO = :NCDCTO 
			AND NCDOCO = :NCDOCO 
			AND NCLNID = :NCLNID 
			AND NCLITM = :NCLITM 
			AND NCLOTN = :NCLOTN 
		)
		OR NCPROG IN (
			SELECT OLPRNC
			FROM NONCOL0F 
			WHERE OLDCTO = :NCDCTO2 
			AND OLDOCO = :NCDOCO2 
			AND OLLNID = :NCLNID2 
			AND OLLOTN = :NCLOTN2 
		) 
		ORDER BY NCDTEM DESC
		";
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		} 
		$stmt->bindValue(':NCDCTO', $SDDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':NCDOCO', $SDDOCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCLNID', $SDLNID, PDO::PARAM_INT);
		$stmt->bindValue(':NCLITM', $SDLITM, PDO::PARAM_STR);
		$stmt->bindValue(':NCLOTN', $SDLOTN, PDO::PARAM_STR);
		
		$stmt->bindValue(':NCDCTO2', $SDDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':NCDOCO2', $SDDOCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCLNID2', $SDLNID, PDO::PARAM_INT);
		$stmt->bindValue(':NCLOTN2', $SDLOTN, PDO::PARAM_STR);		
		
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		$this->writeSegment('HeadNCCli', array_merge(get_object_vars($this), get_defined_vars()));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{ 
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1"); 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$this->writeSegment('DettNCCli', array_merge(get_object_vars($this), get_defined_vars()));
		} 
		echo '</table>';
			
	}

	protected function getNonConfFor() {
		 	
 		$RRNF = xl_get_parameter("RRNF");
		 
		$selString = 'SELECT JRGDTA94C.F4311.PDKCOO, JRGDTA94C.F4311.PDDOCO, JRGDTA94C.F4311.PDDCTO, 
		JRGDTA94C.F4311.PDLNID,  
		JRGDTA94C.F4311.PDLITM, JRGDTA94C.F4311.PDDSC1, JRGDTA94C.F43121.PRLOTN, 
		JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4311.PDAN8, JRGDTA94C.F4311.PDUORG, JRGDTA94C.F4311.PDMCU    
		FROM JRGDTA94C.F43121 
		inner join JRGDTA94C.F4311 on PDDOCO=PRDOCO AND PDLNID=PRLNID AND PDKCOO=PRKCOO AND PDDCTO=PRDCTO 
		inner join JRGDTA94C.F0101 on JRGDTA94C.F4311.PDAN8 = JRGDTA94C.F0101.ABAN8  
		WHERE RRN(JRGDTA94C.F43121) = :RRNF 
		';
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}			
 		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT);
 		 
		$result = $stmt->execute();
		if (!$result)
		{
			$this->dieWithPDOError($stmt);
		}			

		$row = $stmt->fetch(PDO::FETCH_ASSOC); 
		if($row) {
			foreach(array_keys($row) as $key)
			{
				$row[$key] = rtrim($row[$key]);
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
		} 		
		 
		$selString = "SELECT *
		FROM BCD_DATIV2.NONCON0F 
		WHERE (
			NCDCTO = :NCDCTO 
			AND NCDOCO = :NCDOCO 
			AND NCLNID = :NCLNID 
			AND NCLITM = :NCLITM 
			AND NCLOTN = :NCLOTN 
		)
		OR NCPROG IN (
			SELECT OLPRNC 
			FROM NONCOL0F 
			WHERE OLDCTO = :NCDCTO2 
			AND OLDOCO = :NCDOCO2 
			AND OLLNID = :NCLNID2 
			AND OLLOTN = :NCLOTN2 
		) 		 
		ORDER BY NCDTEM DESC
		";
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		} 
		$stmt->bindValue(':NCDCTO', $PDDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':NCDOCO', $PDDOCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCLNID', $PDLNID, PDO::PARAM_INT);
		$stmt->bindValue(':NCLITM', $PDLITM, PDO::PARAM_STR);
		$stmt->bindValue(':NCLOTN', $PRLOTN, PDO::PARAM_STR);
		
		$stmt->bindValue(':NCDCTO2', $PDDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':NCDOCO2', $PDDOCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCLNID2', $PDLNID, PDO::PARAM_INT);
		$stmt->bindValue(':NCLOTN2', $PRLOTN, PDO::PARAM_STR);
		
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		$this->writeSegment('HeadNCFor', array_merge(get_object_vars($this), get_defined_vars()));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{ 
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1"); 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$this->writeSegment('DettNCFor', array_merge(get_object_vars($this), get_defined_vars()));
		} 
		echo '</table>';
			
	}
	
	// Update the program state, and show the current page of entries
	protected function displayList()
	{
		// Update the program state
		$this->updateState();
		
		// Build current page of records
		$this->buildPage();
	}
	
	// Display details for selected record
	protected function displayRecord()
	{
		$mode = 'Display';
		if ($this->pf_task == 'delconf')
		{
			$mode = 'Delete';
		}
		
		// Fetch parameters which identify the record
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		$keyFieldArray['NCPROG'] = (int) $keyFieldArray['NCPROG'];
		
		// Make sure our key values match only a single record
		$stmt = $this->buildCountStmt($keyFieldArray);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		$row = $stmt->fetch(PDO::FETCH_NUM);
		if ($row[0] > 1)
		{
			die('Error: More than one record is identified by the key values specified. No record has been displayed.');
		}
		else if ($row[0] == 0)
		{
			die('Error: No records were identified by the key values specified. No record has been displayed.');
		}
		
		// Prepare the statement for fetching the entry
		$stmt = $this->buildEntryStmt($keyFieldArray);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Store the result in the row variable
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// Urlencode the key fields so we can use that form on the HTML output
		$NCPROG_url = urlencode(rtrim($row['NCPROG']));
		
		// Sanitize the fields
		foreach(array_keys($row) as $key)
		{
			$row[$key] = htmlspecialchars(rtrim($row[$key]));
			
			
			// make the file field names available in HTML
			$escapedField = xl_fieldEscape($key);
			$$escapedField = $row[$key];
		}
		
		// Output the segment
		$this->writeSegment('RcdDisplay', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	// Delete the record
	protected function deleteRecord() 
	{
		// Fetch parameters which identify the record
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		$keyFieldArray['NCPROG'] = (int) $keyFieldArray['NCPROG'];
		
		// Make sure we'll only be deleting a single record
		$stmt = $this->buildCountStmt($keyFieldArray);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		$row = $stmt->fetch(PDO::FETCH_NUM);
		if ($row[0] > 1)
		{
			die('Error: More than one record is identified by the key values specified. Record was NOT deleted.');
		}
		else if ($row[0] == 0)
		{
			die('Error: No records were identified by the key values specified. No records were removed.');
		}
		
		// Prepare and execute the SQL statement to delete the record
		$delString = 'DELETE FROM BCD_DATIV2.NONCON0F ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($delString);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the key parameters
		$stmt->bindValue(':NCPROG_', (int) $keyFieldArray['NCPROG'], PDO::PARAM_INT);
		
		// Execute the delete statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		
		//elimino ordini collegati:
		$delString = 'DELETE FROM BCD_DATIV2.NONCOL0F WHERE OLPRNC = :NCPROG_ WITH NC';
		$stmt_oc = $this->db_connection->prepare($delString); 
		// Bind the key parameters
		$stmt_oc->bindValue(':NCPROG_', (int) $keyFieldArray['NCPROG'], PDO::PARAM_INT);
		
		// Execute the delete statement
		$result_oc = $stmt_oc->execute();
		if ($result_oc === false)
		{
			$this->dieWithPDOError($stmt_oc);
		}		
		
		
		// Redirect to the original page of the main list
		header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
	}
	/*********************/
	// Show the add page
	protected function beginAddFor()
	{
		$RRNF = xl_get_parameter("RRNF");
 		$NCRIFE = xl_get_parameter("NCRIFE");
 			 
		//creazione partendo da ordine fornitore:
		if($RRNF!='') {
	 		$retArray = $this->getDatiOrdFor($RRNF);
			foreach(array_keys($retArray) as $key)
			{
				$retArray[$key] = rtrim($retArray[$key]);
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $retArray[$key];
			}
		} 
		
		//creazione partendo da ordine cliente:
		$RRNC = xl_get_parameter("RRNC");
		if($RRNC!='') {
			$TIPOFILE = xl_get_parameter("TIPOFILE");
	 		$retArray = $this->getDatiOrdCli($TIPOFILE,$RRNC);
			foreach(array_keys($retArray) as $key)
			{
				$retArray[$key] = rtrim($retArray[$key]);
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $retArray[$key];
			}
			$PDLNID = $SDLNID;  
			$PDLITM = $SDLITM; 
			$VDSC1 = $SDDSC1; 
			$PRLOTN = $SDLOTN;  
			$PDAN8 = $IOVEND; 
			$PDUORG = $SDUORG; 
			$PDMCU = $SDMCU;  	 		
			$PDDCTO = '';
			$PDDOCO = '';
		 	$PDDSC1 = $SDDSC1;
		 	$ABALPH = $NCRGFO;
			$PRUORG = $SDUORG;
		}
		
		//progressivo temporaneo per allegati:
		$TMPROG = $this->getProgre("NONCNT0F");

		$NCPROG = "";
		$NCDTEM = date("d-m-Y");
		$NCTPCF = "C";
		$NCAB8 = $PDAN8;
		$NCRGCF = $ABALPH;
		$NCDCTO = $PDDCTO;
		$NCDOCO = $PDDOCO;
		$NCLNID = $PDLNID;
		$NCLITM = $PDLITM;
		$NCDSC1 = $PDDSC1;
		$NCLOTN = $PRLOTN;
		$NCLOT2 = $IOLOT2; 
		$NCUORG = $PDUORG;
		$NCPZCO = $NCUORG / 100;
		$NCPPCO = "100";
		$NCFORN = $IOVEND;
		$NCRICL = "";
		$NCDTRC = "";
		$NCDIFE = "";
		$NCRISO = "";
		$NCSOEF = "";
		$NCACEF = "";
		$NCCHIU = "";
		$NCDTCH = "";
		$NCNOTE = "";
		 
		$NCRSQT = "";
		$NCRSDT = "";
		$NCSEQT = "";
		$NCSEDT = "";
		$NCAEQT = "";
		$NCAEDT = "";	
		$NCRANC = "";
		$NCCATE = "";
		
		$NCQTSP = $PRUORG / 100;
		$NCQTSE = "";
		$NCQTRI = "";
		$NCDTSP = "";			 
		
		$NCQTOP = $NCQTSP;
		
		// Output the segment
		$this->writeSegment('RcdAddFor', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	protected function getDatiOrdFor($RRNF) {

		$SDLNID = ''; 
		$SDPSN = ''; 
		$SDDELN = ''; 
		$SDDOC = ''; 
		$SDLITM = ''; 
		$SDDSC1 = ''; 
		$SDLOTN = ''; 
		$SDIVD = ''; 
		$ABALPH = ''; 
		$SDAN8 = ''; 
		$SDUORG = ''; 
		$SDMCU = ''; 
		$NCRGFO = '';
		$SDDCTO = '';		
		$SDDOCO = '';						
		$SDSOQS = '';
		$IOLOT2 = '';
		$IOVEND = '';

		$retArray = array();
		
		$selString = 'SELECT JRGDTA94C.F4311.PDKCOO, JRGDTA94C.F4311.PDDOCO, JRGDTA94C.F4311.PDDCTO, 
		JRGDTA94C.F4311.PDLNID,  
		JRGDTA94C.F4311.PDLITM, JRGDTA94C.F4311.PDDSC1, JRGDTA94C.F43121.PRLOTN, 
		JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4311.PDAN8, JRGDTA94C.F4311.PDUORG, JRGDTA94C.F4311.PDMCU, 
		JRGDTA94C.F43121.PRUORG     
		FROM JRGDTA94C.F43121 
		inner join JRGDTA94C.F4311 on PDDOCO=PRDOCO AND PDLNID=PRLNID AND PDKCOO=PRKCOO AND PDDCTO=PRDCTO 
		inner join JRGDTA94C.F0101 on JRGDTA94C.F4311.PDAN8 = JRGDTA94C.F0101.ABAN8  
		WHERE RRN(JRGDTA94C.F43121) = :RRNF 
		AND PDDCTO NOT IN (\'ST\' , \'OF\' , \'SQ\') AND PDLNTY=\'S\' 
		';
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}			
 		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT);
 		 
		$result = $stmt->execute();
		if (!$result)
		{
			$this->dieWithPDOError($stmt);
		}			

		$row = $stmt->fetch(PDO::FETCH_ASSOC); 
		if($row) {
			foreach(array_keys($row) as $key)
			{
				$row[$key] = rtrim($row[$key]);
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
		}
		
		$IOLOT2 = '';
		$IOVEND = '';
		$selString = "SELECT JRGDTA94C.F4108.IOLOT2, JRGDTA94C.F4108.IOVEND, COALESCE(JRGDTA94C.F0101.ABALPH, '') AS NCRGFO 
		FROM JRGDTA94C.F4108 
		LEFT JOIN JRGDTA94C.F0101 ON IOVEND = ABAN8 
		WHERE IOLOTN=:PRLOTN AND IOMCU=:LIMCU AND IOLITM=:PDLITM";
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}			
		$stmt->bindValue(':PRLOTN', $PRLOTN, PDO::PARAM_STR);
 		$stmt->bindValue(':LIMCU', $PDMCU, PDO::PARAM_STR);
 		$stmt->bindValue(':PDLITM', $PDLITM, PDO::PARAM_STR);
		$result = $stmt->execute();
		if (!$result)
		{
			$this->dieWithPDOError($stmt);
		}			

		$row = $stmt->fetch(PDO::FETCH_ASSOC); 
		if($row) {
			foreach(array_keys($row) as $key)
			{
				$row[$key] = rtrim($row[$key]);
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
		}
		
		$retArray["PDLNID"] = $PDLNID;   
		$retArray["PDLITM"] = $PDLITM;
		$retArray["PDDSC1"] = $PDDSC1;
		$retArray["PRLOTN"] = $PRLOTN; 
		$retArray["ABALPH"] = $ABALPH;
		$retArray["PDAN8"]  = $PDAN8;
		$retArray["PDUORG"] = $PDUORG;
		$retArray["PDMCU"]  = $PDMCU;
		$retArray["NCRGFO"] = $NCRGFO; 
		$retArray["PDDCTO"] = $PDDCTO; 
		$retArray["PDDOCO"] = $PDDOCO; 
		$retArray["IOLOT2"] = $IOLOT2; 
		$retArray["IOVEND"] = $IOVEND; 
		$retArray["PRUORG"] = $PRUORG;
		 
		return $retArray;
		
	}	
	
	// Add the passed in data as a new row
	protected function endAddFor()
	{
		// Get values from the page
 
 		header("Content-Type: application/json");
 
 		$TMPROG = (int) xl_get_parameter('TMPROG');
 
		$NCDTEM = xl_get_parameter('NCDTEM');
		$NCTPCF = "F";
		$NCAB8 = xl_get_parameter('NCAB8');
		$NCRGCF = xl_get_parameter('NCRGCF');
		$NCDCTO = xl_get_parameter('NCDCTO');
		$NCDOCO = xl_get_parameter('NCDOCO');
		$NCLNID = xl_get_parameter('NCLNID');
		$NCLITM = xl_get_parameter('NCLITM');
		$NCDSC1 = xl_get_parameter('NCDSC1');
		$NCLOTN = xl_get_parameter('NCLOTN');
		$NCLOT2 = xl_get_parameter('NCLOT2');
		$NCPZCO = xl_get_parameter('NCPZCO');
		$NCPPCO = xl_get_parameter('NCPPCO'); 
		$NCRICL = xl_get_parameter('NCRICL');
		$NCDTRC = xl_get_parameter('NCDTRC');
		$NCDIFE = xl_get_parameter('NCDIFE');
		$NCRISO = xl_get_parameter('NCRISO');
		$NCSOEF = xl_get_parameter('NCSOEF');
		$NCACEF = xl_get_parameter('NCACEF');
		$NCCHIU = xl_get_parameter('NCCHIU');
		$NCDTCH = xl_get_parameter('NCDTCH');
		$NCNOTE = xl_get_parameter('NCNOTE');
		$NCUORG = xl_get_parameter('NCUORG');
		$NCRGFO = xl_get_parameter('NCRGFO');
 		$NCRIFE = xl_get_parameter('NCRIFE');
 		
 		$NCRSQT = xl_get_parameter('NCRSQT'); 
		$NCRSDT = xl_get_parameter('NCRSDT'); 
		$NCSEQT = xl_get_parameter('NCSEQT'); 
		$NCSEDT = xl_get_parameter('NCSEDT'); 
		$NCAEQT = xl_get_parameter('NCAEQT'); 
		$NCAEDT = xl_get_parameter('NCAEDT'); 	
		$NCRANC = xl_get_parameter('NCRANC'); 	
		$NCCATE = xl_get_parameter('NCCATE'); 	
			 
 		$NCQTSP = xl_get_parameter('NCQTSP'); 	 
 		$NCQTSE = xl_get_parameter('NCQTSE'); 	 
 		$NCQTRI = xl_get_parameter('NCQTRI'); 	 
 		$NCDTSP = xl_get_parameter('NCDTSP'); 	 
		$NCQTOP = xl_get_parameter('NCQTOP'); 	 
			
 		$NCORFO = 0;
 		$NCTRFO = '';
 		$NCFORN = 0;
 		 
		// Do any add validation here
		$errorMsg = $this->checkRecordFor();
		if($errorMsg!="") {
			echo '['.$errorMsg.']';
			exit;
		}
 
	 	$NCDTEM = substr($NCDTEM,6,4).substr($NCDTEM,3,2).substr($NCDTEM,0,2);
	 	$NCDTRC = substr($NCDTRC,6,4).substr($NCDTRC,3,2).substr($NCDTRC,0,2);
	 	$NCDTCH = substr($NCDTCH,6,4).substr($NCDTCH,3,2).substr($NCDTCH,0,2);
		$NCRSDT = substr($NCRSDT,6,4).substr($NCRSDT,3,2).substr($NCRSDT,0,2);
		$NCSEDT = substr($NCSEDT,6,4).substr($NCSEDT,3,2).substr($NCSEDT,0,2);
		$NCAEDT = substr($NCAEDT,6,4).substr($NCAEDT,3,2).substr($NCAEDT,0,2);	
		$NCDTSP = substr($NCDTSP,6,4).substr($NCDTSP,3,2).substr($NCDTSP,0,2);
		 
	 	if(!is_numeric($NCDTEM)) $NCDTEM = 0;
	 	if(!is_numeric($NCDTRC)) $NCDTRC = 0;
	 	if(!is_numeric($NCDTCH)) $NCDTCH = 0;
	 	if(!is_numeric($NCRSDT)) $NCRSDT = 0;
	 	if(!is_numeric($NCSEDT)) $NCSEDT = 0;
	 	if(!is_numeric($NCAEDT)) $NCAEDT = 0;
	 	if(!is_numeric($NCDOCO)) $NCDOCO = 0;
	 	if(!is_numeric($NCDTSP)) $NCDTSP = 0;
	 		 	
	 	if(!is_numeric($NCRSQT)) $NCRSQT = 0;
	 	if(!is_numeric($NCSEQT)) $NCSEQT = 0;
	 	if(!is_numeric($NCAEQT)) $NCAEQT = 0;
	 	if(!is_numeric($NCAEQT)) $NCAEQT = 0;
	 	if(!is_numeric($NCQTSP)) $NCQTSP = 0;
	 	if(!is_numeric($NCQTSE)) $NCQTSE = 0;
	 	if(!is_numeric($NCQTRI)) $NCQTRI = 0;
	 	if(!is_numeric($NCQTOP)) $NCQTOP = 0;
	 		 	
	 	//progressivo:
	 	$NCPROG = $this->getProgre("NONCON0F");
	 	 	
		// Prepare the statement to add the record
		$insertSql = 'INSERT INTO BCD_DATIV2.NONCON0F (NCPROG, NCUORG, NCRGFO, NCDTEM, NCTPCF, NCAB8, NCRGCF, 
		NCDCTO, NCDOCO, NCLNID, NCLITM, NCDSC1, NCLOTN, NCLOT2, NCPZCO, NCPPCO, NCFORN, NCRICL, NCDTRC, NCDIFE, 
		NCRISO, NCSOEF, NCACEF, NCCHIU, NCDTCH, NCNOTE, NCRIFE, NCORFO, NCTRFO, NCRSQT, NCRSDT, NCSEQT, NCSEDT, NCAEQT, NCAEDT, 
		NCRANC, NCCATE, NCQTSP, NCQTSE, NCQTRI, NCDTSP, NCQTOP) 
		VALUES(:NCPROG, :NCUORG, :NCRGFO, :NCDTEM, :NCTPCF, 
		:NCAB8, :NCRGCF, :NCDCTO, :NCDOCO, :NCLNID, :NCLITM, :NCDSC1, :NCLOTN, :NCLOT2, :NCPZCO, :NCPPCO, :NCFORN, 
		:NCRICL, :NCDTRC, :NCDIFE, :NCRISO, :NCSOEF, :NCACEF, :NCCHIU, :NCDTCH, :NCNOTE, :NCRIFE, :NCORFO, :NCTRFO, :NCRSQT, 
		:NCRSDT, :NCSEQT, :NCSEDT, :NCAEQT, :NCAEDT, :NCRANC, :NCCATE, :NCQTSP, :NCQTSE, :NCQTRI, :NCDTSP, :NCQTOP)' . ' WITH NC';
		$stmt = $this->db_connection->prepare($insertSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':NCPROG', $NCPROG, PDO::PARAM_INT);
		$stmt->bindValue(':NCUORG', $NCUORG, PDO::PARAM_INT);
		$stmt->bindValue(':NCRGFO', $NCRGFO, PDO::PARAM_STR); 
		$stmt->bindValue(':NCDTEM', $NCDTEM, PDO::PARAM_INT);
		$stmt->bindValue(':NCTPCF', $NCTPCF, PDO::PARAM_STR);
		$stmt->bindValue(':NCAB8', $NCAB8, PDO::PARAM_INT);
		$stmt->bindValue(':NCRGCF', $NCRGCF, PDO::PARAM_STR);
		$stmt->bindValue(':NCDCTO', $NCDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':NCDOCO', $NCDOCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCLNID', $NCLNID, PDO::PARAM_INT);
		$stmt->bindValue(':NCLITM', $NCLITM, PDO::PARAM_STR);
		$stmt->bindValue(':NCDSC1', $NCDSC1, PDO::PARAM_STR); 
		$stmt->bindValue(':NCLOTN', $NCLOTN, PDO::PARAM_STR);
		$stmt->bindValue(':NCLOT2', $NCLOT2, PDO::PARAM_STR);
		$stmt->bindValue(':NCPZCO', $NCPZCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCPPCO', $NCPPCO, PDO::PARAM_STR);
		$stmt->bindValue(':NCFORN', $NCFORN, PDO::PARAM_INT);
		$stmt->bindValue(':NCRICL', $NCRICL, PDO::PARAM_STR);
		$stmt->bindValue(':NCDTRC', $NCDTRC, PDO::PARAM_INT);
		$stmt->bindValue(':NCDIFE', $NCDIFE, PDO::PARAM_STR);
		$stmt->bindValue(':NCRISO', $NCRISO, PDO::PARAM_STR);
		$stmt->bindValue(':NCSOEF', $NCSOEF, PDO::PARAM_STR);
		$stmt->bindValue(':NCACEF', $NCACEF, PDO::PARAM_STR);
		$stmt->bindValue(':NCCHIU', $NCCHIU, PDO::PARAM_STR);
		$stmt->bindValue(':NCDTCH', $NCDTCH, PDO::PARAM_INT);
		$stmt->bindValue(':NCNOTE', $NCNOTE, PDO::PARAM_STR);
		$stmt->bindValue(':NCRIFE', $NCRIFE, PDO::PARAM_INT);
		$stmt->bindValue(':NCORFO', $NCORFO, PDO::PARAM_INT);
		$stmt->bindValue(':NCTRFO', $NCTRFO, PDO::PARAM_STR);
		$stmt->bindValue(':NCRSQT', $NCRSQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCRSDT', $NCRSDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCSEQT', $NCSEQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCSEDT', $NCSEDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCAEQT', $NCAEQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCAEDT', $NCAEDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCRANC', $NCRANC, PDO::PARAM_STR); 
		$stmt->bindValue(':NCCATE', $NCCATE, PDO::PARAM_STR); 
		$stmt->bindValue(':NCQTSP', $NCQTSP, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTSE', $NCQTSE, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTRI', $NCQTRI, PDO::PARAM_INT);  
		$stmt->bindValue(':NCDTSP', $NCDTSP, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTOP', $NCQTOP, PDO::PARAM_INT);  
		

		// Execute the insert statement
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		
		//Allegati:
		$selString = "SELECT * 
		FROM BCD_DATIV2.NONCOATF 
		WHERE NAPRNC = ".$TMPROG."";
		$stmt_ia = $this->db_connection->prepare($selString); 
		$result = $stmt_ia->execute(); 
		if($result) {
			while($row = $stmt_ia->fetch(PDO::FETCH_ASSOC)) {
				
				foreach(array_keys($row) as $key)
				{ 
					$row[$key] = htmlspecialchars(rtrim($row[$key]));
					$escapedField = xl_fieldEscape($key); 
					$$escapedField = $row[$key];
				}			
				
				$target_tmp_file = $NAPATH;
				
			 	//progressivo:
			 	$NAPROG = $this->getProgre("NONCOA0F");
			 	 
				$selString = "INSERT INTO BCD_DATIV2.NONCOA0F (NAPRNC,NAPROG,NAFILN,NAEXT, NAPATH,NADTIN,NAORIN) VALUES(:NAPRNC,:NAPROG,:NAFILN,:NAEXT,:NAPATH,:NADTIN,:NAORIN) WITH NC";
				$stmt_ta = $this->db_connection->prepare($selString); 
				 
				$target_dir = "/www/php80/htdocs/CRUD/uploads/"; 
				$NAPATH = $target_dir.$NAPROG . ".".$NAEXT;
				 
				// Bind the parameters
				$stmt_ta->bindValue(':NAPRNC', $NCPROG, PDO::PARAM_INT);
				$stmt_ta->bindValue(':NAPROG', $NAPROG, PDO::PARAM_INT);
				$stmt_ta->bindValue(':NAFILN', $NAFILN, PDO::PARAM_STR);  
				$stmt_ta->bindValue(':NAEXT', $NAEXT, PDO::PARAM_STR);  
				$stmt_ta->bindValue(':NAPATH', $NAPATH, PDO::PARAM_STR);  
				$stmt_ta->bindValue(':NADTIN', $NADTIN, PDO::PARAM_INT);  
				$stmt_ta->bindValue(':NAORIN', $NAORIN, PDO::PARAM_INT);  
				
				// Execute the insert statement
				$result = $stmt_ta->execute();
				
				// Sposto file:
				rename($target_tmp_file, $NAPATH);
				
			}
		}
		
		//elimino allegati temp
		$selString = "DELETE FROM 
		BCD_DATIV2.NONCOATF 
		WHERE NAPRNC = ".$TMPROG." WITH NC";
		$res_da = $this->db_connection->exec($selString); 		
		
		
		
		//Ordini legati:
		$selString = "SELECT * 
		FROM BCD_DATIV2.NONCOLTF  
		WHERE OLPRNC = ".$TMPROG."";
		$stmt_ol = $this->db_connection->prepare($selString); 
		$result = $stmt_ol->execute(); 
		if($result) {
			while($row = $stmt_ol->fetch(PDO::FETCH_ASSOC)) {
				
				foreach(array_keys($row) as $key)
				{ 
					$row[$key] = htmlspecialchars(rtrim($row[$key]));
					$escapedField = xl_fieldEscape($key); 
					$$escapedField = $row[$key];
				}			
				 
			 	//progressivo:
			 	$OLPROG = $this->getProgre("NONCOL0F");
			 	 
				$selString = "INSERT INTO BCD_DATIV2.NONCOL0F (OLPRNC,OLPROG,OLDCTO,OLDOCO,OLLNID,OLLOTN,OLQTSP) VALUES(:OLPRNC,:OLPROG,:OLDCTO,:OLDOCO,:OLLNID,:OLLOTN,:OLQTSP) WITH NC";
				$stmt_to = $this->db_connection->prepare($selString); 
				  
				// Bind the parameters
				$stmt_to->bindValue(':OLPRNC', $NCPROG, PDO::PARAM_INT);
				$stmt_to->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
				$stmt_to->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);  
				$stmt_to->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR);
				$stmt_to->bindValue(':OLLNID', $OLLNID, PDO::PARAM_INT);  
				$stmt_to->bindValue(':OLLOTN', $OLLOTN, PDO::PARAM_STR);   
				$stmt_to->bindValue(':OLQTSP', $OLQTSP, PDO::PARAM_INT);   
				
				// Execute the insert statement
				$result = $stmt_to->execute();
				 
				
			}
		}
		
		//elimino ordini legati temp
		$selString = "DELETE FROM 
		BCD_DATIV2.NONCOLTF 
		WHERE OLPRNC = ".$TMPROG." WITH NC";
		$res_do = $this->db_connection->exec($selString);   		
		
		
		// Redirect to the original page of the main list
		echo '[{"stat":"OK"}]';
	}
	
	protected function checkRecordFor() { 
		$errorMsg = "";
		$errorSep = "";

		if(trim(xl_get_parameter('NCDTEM'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCDTEM","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCAB8'))=='')  { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCAB8","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCRGCF'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCRGCF","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		//if(trim(xl_get_parameter('NCDCTO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCDCTO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		//if(trim(xl_get_parameter('NCDOCO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCDOCO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCLNID'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCLNID","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCLITM'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCLITM","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCPZCO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCPZCO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCPPCO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCPPCO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCDIFE'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCDIFE","msg":"Campo obbligatorio"}'; $errorSep = ","; }

		 
		return $errorMsg;
	
	}	
	/********************/
	
	protected function fltOrdFor() {
		$filt_NCFORN_o = xl_get_parameter("filt_NCFORN_o");
		$filt_NCLITM_o = xl_get_parameter("filt_NCLITM_o");
		$filt_NCLOTN_o = xl_get_parameter("filt_NCLOTN_o");
		
		$_SESSION["filt_NCFORN_o"] = $filt_NCFORN_o;
		$_SESSION["filt_NCLITM_o"] = $filt_NCLITM_o;
		$_SESSION["filt_NCLOTN_o"] = $filt_NCLOTN_o;
		
		$this->srcOrdFor();
	}
	
	protected function srcOrdFor() {
		
		$filt_NCFORN_o = "";
		$filt_NCLITM_o = "";
		$filt_NCLOTN_o = "";
		if(isset($_SESSION["filt_NCFORN_o"])) $filt_NCFORN_o = $_SESSION["filt_NCFORN_o"];
		if(isset($_SESSION["filt_NCLITM_o"])) $filt_NCLITM_o = $_SESSION["filt_NCLITM_o"];
		if(isset($_SESSION["filt_NCLOTN_o"])) $filt_NCLOTN_o = $_SESSION["filt_NCLOTN_o"];
		 
		$selString = "SELECT MIN(RRN(JRGDTA94C.F43121)) AS RRNF, JRGDTA94C.F4311.PDAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4311.PDKCOO, 
		JRGDTA94C.F4311.PDDOCO, JRGDTA94C.F4311.PDDCTO, JRGDTA94C.F4311.PDLNID, JRGDTA94C.F43121.PRVRMK, JRGDTA94C.F4311.PDLITM, JRGDTA94C.F4311.PDDSC1, 
		JRGDTA94C.F43121.PRLOTN 
		FROM JRGDTA94C.F43121 
		inner join JRGDTA94C.F4311 on PDDOCO=PRDOCO AND PDLNID=PRLNID AND PDKCOO=PRKCOO AND PDDCTO=PRDCTO 
		inner join JRGDTA94C.F0101 on JRGDTA94C.F4311.PDAN8 = JRGDTA94C.F0101.ABAN8 ";
		
		$whereClause = '';
		$link = 'WHERE '; 
		if ($filt_NCFORN_o != '')
		{
			$whereClause = $whereClause . $link . ' JRGDTA94C.F4311.PDAN8 = :PDAN8';
			$link = ' AND ';
		}		
		if ($filt_NCLITM_o != '')
		{
			$whereClause = $whereClause . $link . ' JRGDTA94C.F4311.PDLITM = :PDLITM';
			$link = ' AND ';
		}
		if ($filt_NCLOTN_o != '')
		{
			$whereClause = $whereClause . $link . ' JRGDTA94C.F43121.PRLOTN = :PRLOTN';
			$link = ' AND ';
		}
		$selString.=$whereClause;
		$selString.="
		GROUP BY JRGDTA94C.F4311.PDAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4311.PDKCOO, 
		JRGDTA94C.F4311.PDDOCO, JRGDTA94C.F4311.PDDCTO, JRGDTA94C.F4311.PDLNID, JRGDTA94C.F43121.PRVRMK, JRGDTA94C.F4311.PDLITM, JRGDTA94C.F4311.PDDSC1, 
		JRGDTA94C.F43121.PRLOTN 
		FETCH FIRST 50 ROWS ONLY
		";
		 
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString);
	 
	 	$PDAN8 = $filt_NCFORN_o;
	 	$PDLITM = $filt_NCLITM_o;
	 	$PRLOTN = $filt_NCLOTN_o;
	 
		if ($filt_NCFORN_o != '') $stmt->bindValue(':PDAN8', $PDAN8, PDO::PARAM_INT);
		if ($filt_NCLITM_o != '') $stmt->bindValue(':PDLITM', $PDLITM, PDO::PARAM_STR);
		if ($filt_NCLOTN_o != '') $stmt->bindValue(':PRLOTN', $PRLOTN, PDO::PARAM_STR);
		
		$result = $stmt->execute(); 
		
		$i = 0;  
		// Display each of the records, up to $listSize
		$this->writeSegment("hOrdFor", array_merge(get_object_vars($this), get_defined_vars()));	
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$this->writeSegment("dOrdFor", array_merge(get_object_vars($this), get_defined_vars()));	
			
			$i++;
		}
		
		if($i==0) { $nrColspan = "6"; $this->writeSegment('NoRecords', array_merge(get_object_vars($this), get_defined_vars())); }
		
		$this->writeSegment("fOrdFor", array_merge(get_object_vars($this), get_defined_vars()));	
			
	}

	/********************/
	protected function addOrdColCli() {
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");
		$tipoDato = xl_get_parameter("tipoDato");
		$RRNF = xl_get_parameter("RRNF");
		
		$RRN_OC = xl_get_parameter("RRN_OC");
		$TIPOFILE_OC = xl_get_parameter("TIPOFILE_OC");
 		$retArray = $this->getDatiOrdCli($TIPOFILE_OC,$RRN_OC);
		foreach(array_keys($retArray) as $key)
		{
			$retArray[$key] = rtrim($retArray[$key]);
			$escapedField = xl_fieldEscape($key);
			$$escapedField = $retArray[$key];
		}
		
		$OLDCTO = $SDDCTO;
		$OLDOCO = $SDDOCO;
		$OLQTSP = $SDUORG;
		$OLLOTN = $SDLOTN;
		$OLLNID = $SDLNID;
		
		//$OLDCTO = xl_get_parameter("OLDCTO");
		//$OLDOCO = xl_get_parameter("OLDOCO");
		//$OLQTSP = xl_get_parameter("OLQTSP");
		if(!is_numeric($OLDOCO)) $OLDOCO = 0;
		if(!is_numeric($OLQTSP)) $OLQTSP = 0;
		$OLDCTO = strtoupper($OLDCTO); 
		
		if($tipoIns=="T") {
 
		 	//progressivo:
		 	$OLPROG = $this->getProgre("NONCOLTF");
		 	 	 
			$insertSql = "INSERT INTO BCD_DATIV2.NONCOLTF (OLPRNC,OLPROG,OLDCTO,OLDOCO,OLLNID,OLLOTN,OLQTSP) VALUES(:OLPRNC,:OLPROG,:OLDCTO,:OLDOCO,:OLLNID,:OLLOTN,:OLQTSP) WITH NC";
			$stmt = $this->db_connection->prepare($insertSql);
			if (!$stmt)
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}			
			 
			// Bind the parameters
			$stmt->bindValue(':OLPRNC', $TMPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);
			$stmt->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR); 
			$stmt->bindValue(':OLLNID', $OLLNID, PDO::PARAM_INT);   
			$stmt->bindValue(':OLLOTN', $OLLOTN, PDO::PARAM_STR);  
			$stmt->bindValue(':OLQTSP', $OLQTSP, PDO::PARAM_INT);  			
			
			$result = $stmt->execute();
			if ($result === false) 
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}
			
		} else {
			
		 	//progressivo:
		 	$OLPROG = $this->getProgre("NONCOL0F");
		 	 	 
			$insertSql = "INSERT INTO BCD_DATIV2.NONCOL0F (OLPRNC,OLPROG,OLDCTO,OLDOCO,OLQTSP,OLLNID,OLLOTN) VALUES(:OLPRNC,:OLPROG,:OLDCTO,:OLDOCO,:OLQTSP,:OLLNID,:OLLOTN) WITH NC";
			$stmt = $this->db_connection->prepare($insertSql);
			if (!$stmt)
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}			
			 
			// Bind the parameters 
			$stmt->bindValue(':OLPRNC', $TMPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);
			$stmt->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR);
			$stmt->bindValue(':OLLNID', $OLLNID, PDO::PARAM_INT);
			$stmt->bindValue(':OLLOTN', $OLLOTN, PDO::PARAM_STR);
			$stmt->bindValue(':OLQTSP', $OLQTSP, PDO::PARAM_INT);  				
			
			$result = $stmt->execute();
			if ($result === false) 
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}
		}
		
		echo '[{"stat":"OK"}]';	
	}
 
	protected function fltOrdColCli() {
		$filt_NCAB8_occ = xl_get_parameter("filt_NCAB8_occ");
		$filt_NCLOTN_occ = xl_get_parameter("filt_NCLOTN_occ");
		
		$_SESSION["filt_NCAB8_occ"] = $filt_NCAB8_occ;
		$_SESSION["filt_NCLOTN_occ"] = $filt_NCLOTN_occ;
		  
		$this->srcOrdColCli();
	}
	
	protected function srcOrdColCli() {
		
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = (int) xl_get_parameter("TMPROG");		
		$RRNF = (int) xl_get_parameter("RRNF");	
		$TIPOFILE = xl_get_parameter("TIPOFILE");	
		 
		//escludere ordine principale:
		$excludeSDDCTO = "";
		$excludeSDDOCO = "";
		$excludeSDLNID = "";
		$excludeSDLOTN = "";
		$excludeFilt = false;
					
		if($tipoIns=="T") {
			if($RRNF != 0) {
		 		$retArray = $this->getDatiOrdCli($TIPOFILE,$RRNF); 
				$excludeSDDCTO = $retArray["SDDCTO"];
				$excludeSDDOCO = $retArray["SDDOCO"];
				$excludeSDLNID = $retArray["SDLNID"];
				$excludeSDLOTN = $retArray["SDLOTN"]; 
				$excludeFilt = true;
			}
		} else {
			$query = "SELECT NCDCTO,NCDOCO,NCLNID,NCLOTN 
			FROM BCD_DATIV2.NONCON0F 
			WHERE NCPROG = ".$TMPROG;
			$stmt_ex = $this->db_connection->prepare($query);
			$result_ex = $stmt_ex->execute(); 
			$row_ex = $stmt_ex->fetch(PDO::FETCH_ASSOC); 
			if($row_ex) {
				$excludeSDDCTO = $row_ex["NCDCTO"];
				$excludeSDDOCO = $row_ex["NCDOCO"];
				$excludeSDLNID = $row_ex["NCLNID"];
				$excludeSDLOTN = $row_ex["NCLOTN"]; 
				$excludeFilt = true;
			}
		}
		//escludere ordine principale [f]		 
		 
		$filt_NCAB8_occ = "";
		$filt_NCLOTN_occ = ""; 
		if(isset($_SESSION["filt_NCAB8_occ"])) $filt_NCAB8_occ = $_SESSION["filt_NCAB8_occ"];
		if(isset($_SESSION["filt_NCLOTN_occ"])) $filt_NCLOTN_occ = $_SESSION["filt_NCLOTN_occ"];
		 
		$selString = 'SELECT T.RRNF, T.SDAN8, T.ABALPH, T.SDKCOO, 
		T.SDDOCO, T.SDDCTO, T.SDLNID, 
		T.SDPSN, T.SDDELN, T.SDDOC, 
		T.SDLITM, T.SDDSC1, T.SDLOTN,  
		T.SDIVD, T.TIPOFILE, T.SDLNTY, CAST(T.SDUORG / 100 AS INTEGER) AS SDUORG, 
		T.SDSOQS      
		FROM TABLE ( 
			SELECT RRN(JRGDTA94C.F4211) AS RRNF, JRGDTA94C.F4211.SDAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4211.SDKCOO, 
			JRGDTA94C.F4211.SDDOCO, JRGDTA94C.F4211.SDDCTO, JRGDTA94C.F4211.SDLNID, 
			JRGDTA94C.F4211.SDPSN, JRGDTA94C.F4211.SDDELN, JRGDTA94C.F4211.SDDOC, 
			JRGDTA94C.F4211.SDLITM, JRGDTA94C.F4211.SDDSC1, JRGDTA94C.F4211.SDLOTN, JRGDTA94C.F4211.SDLNTY, JRGDTA94C.F4211.SDUORG,   
			CASE WHEN SDIVD <> 0 THEN date (days(concat(cast(integer(1900000+ SDIVD ) /1000 as Char(4)),\'-01-01\'))+mod(integer(1900000+  SDIVD ), 1000)-1) 
			ELSE \'0001-01-01\' END AS SDIVD, \'1\' AS TIPOFILE, JRGDTA94C.F4211.SDSOQS 
			FROM JRGDTA94C.F4211 inner join JRGDTA94C.F0101 on JRGDTA94C.F4211.SDAN8 = JRGDTA94C.F0101.ABAN8
			
			UNION 
			
			SELECT RRN(JRGDTA94C.F42119) AS RRNF, JRGDTA94C.F42119.SDAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F42119.SDKCOO, 
			JRGDTA94C.F42119.SDDOCO, JRGDTA94C.F42119.SDDCTO, JRGDTA94C.F42119.SDLNID, 
			JRGDTA94C.F42119.SDPSN, JRGDTA94C.F42119.SDDELN, JRGDTA94C.F42119.SDDOC, 
			JRGDTA94C.F42119.SDLITM, JRGDTA94C.F42119.SDDSC1, JRGDTA94C.F42119.SDLOTN, JRGDTA94C.F42119.SDLNTY, JRGDTA94C.F42119.SDUORG,    
			CASE WHEN SDIVD <> 0 THEN date (days(concat(cast(integer(1900000+ SDIVD ) /1000 as Char(4)),\'-01-01\'))+mod(integer(1900000+  SDIVD ), 1000)-1) 
			ELSE \'0001-01-01\' END AS SDIVD, \'2\' AS TIPOFILE, JRGDTA94C.F42119.SDSOQS  
			FROM JRGDTA94C.F42119 inner join JRGDTA94C.F0101 on JRGDTA94C.F42119.SDAN8 = JRGDTA94C.F0101.ABAN8
		) AS T 	
		';
		
		$whereClause = ' WHERE SDDCTO NOT IN (\'ST\' , \'OF\' , \'SQ\')  AND SDLNTY=\'S\' '; 
		$link = ' AND ';
		
		if($excludeFilt) {
			$whereClause = $whereClause . $link . " 
			(
			T.SDDCTO <> '".$excludeSDDCTO."' 
			OR T.SDDOCO <> '".$excludeSDDOCO."'
			OR T.SDLNID <> '".$excludeSDLNID."'
			OR T.SDLOTN <> '".$excludeSDLOTN."'
			)
			";
			$link = ' AND ';		
		}
		
		if($tipoIns=="T") {
			$whereClause = $whereClause . $link . " NOT EXISTS(SELECT 1 AS HASCOL FROM BCD_DATIV2.NONCOLTF WHERE OLPRNC = '".$TMPROG."' AND OLDCTO = T.SDDCTO AND OLDOCO = T.SDDOCO AND OLLNID = T.SDLNID AND OLLOTN = T.SDLOTN) ";
			$link = ' AND ';
		} 
		else {
			$whereClause = $whereClause . $link . " NOT EXISTS(SELECT 1 AS HASCOL FROM BCD_DATIV2.NONCOL0F WHERE OLPRNC = '".$TMPROG."' AND OLDCTO = T.SDDCTO AND OLDOCO = T.SDDOCO AND OLLNID = T.SDLNID AND OLLOTN = T.SDLOTN) ";
			$link = ' AND ';
		} 
		 
		if ($filt_NCAB8_occ != '')
		{
			$whereClause = $whereClause . $link . ' T.SDAN8 = :SDAN8';
			$link = ' AND ';
		}		 
		if ($filt_NCLOTN_occ != '')
		{
			$whereClause = $whereClause . $link . ' lower(T.SDLOTN) LIKE :SDLOTN';
			$link = ' AND ';
		}
		$selString.=$whereClause;
		$selString.=" 
		ORDER BY SDDOCO DESC 
		FETCH FIRST 50 ROWS ONLY";	 
	   
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString);
	 
	 	$SDAN8 = $filt_NCAB8_occ;
	 	$SDLOTN = $filt_NCLOTN_occ; 
	 
		if ($filt_NCAB8_occ != '') $stmt->bindValue(':SDAN8', $SDAN8, PDO::PARAM_INT);
		if ($filt_NCLOTN_occ != '') $stmt->bindValue(':SDLOTN', '%'.strtolower($SDLOTN).'%', PDO::PARAM_STR); 
		
		$result = $stmt->execute(); 
		
		$i = 0;  
		// Display each of the records, up to $listSize
		$this->writeSegment("hOrdColCli", array_merge(get_object_vars($this), get_defined_vars()));	
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$hasCol = false;
 			/*
 			if($tipoIns=="T") $query = "SELECT 1 AS HASCOL FROM BCD_DATIV2.NONCOLTF WHERE OLPRNC = '".$TMPROG."' AND OLDCTO = '".$SDDCTO."'  AND OLDOCO = '".$SDDOCO."' AND OLLNID = '".$SDLNID."' AND OLLOTN = '".$SDLOTN."'";
			else $query = "SELECT 1 AS HASCOL FROM BCD_DATIV2.NONCOL0F WHERE OLPRNC = '".$TMPROG."' AND OLDCTO = '".$SDDCTO."'  AND OLDOCO = '".$SDDOCO."' AND OLLNID = '".$SDLNID."' AND OLLOTN = '".$SDLOTN."'";
			$stmt_co = $this->db_connection->prepare($query);
			$result_co = $stmt_co->execute(); 
			$row_co = $stmt_co->fetch(PDO::FETCH_ASSOC);
			if($row_co && $row_co["HASCOL"]==1) { $hasCol = true; }
			*/
			
			//$SDUORG = $SDUORG / 100;
			
			$this->writeSegment("dOrdColCli", array_merge(get_object_vars($this), get_defined_vars()));	
			
			$i++;
		}
		
		if($i==0) { $nrColspan = "6"; $this->writeSegment('NoRecords', array_merge(get_object_vars($this), get_defined_vars())); }
		
		$this->writeSegment("fOrdColCli", array_merge(get_object_vars($this), get_defined_vars()));	
			
	}

	/********************/
	protected function addOrdColFor() {
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");
		$tipoDato = xl_get_parameter("tipoDato");
		$RRNF = xl_get_parameter("RRNF");
		
		$RRN_OC = xl_get_parameter("RRN_OC");
  		$retArray = $this->getDatiOrdFor($RRN_OC);
		foreach(array_keys($retArray) as $key)
		{
			$retArray[$key] = rtrim($retArray[$key]);
			$escapedField = xl_fieldEscape($key);
			$$escapedField = $retArray[$key];
		}
		 
		$OLDCTO = $PDDCTO;
		$OLDOCO = $PDDOCO;
		$OLQTSP = $PRUORG;
		$OLLOTN = $PRLOTN;	
		$OLLNID = $PDLNID;	
		
		var_dump($OLLOTN);
		
		//$OLDCTO = xl_get_parameter("OLDCTO");
		//$OLDOCO = xl_get_parameter("OLDOCO");
		//$OLQTSP = xl_get_parameter("OLQTSP");		
		if(!is_numeric($OLDOCO)) $OLDOCO = 0;
		if(!is_numeric($OLQTSP)) $OLQTSP = 0;
		$OLDCTO = strtoupper($OLDCTO); 
		  
		if($tipoIns=="T") {
 
		 	//progressivo:
		 	$OLPROG = $this->getProgre("NONCOLTF");
		 	 
			$insertSql = "INSERT INTO BCD_DATIV2.NONCOLTF (OLPRNC,OLPROG,OLDCTO,OLDOCO,OLLNID,OLLOTN,OLQTSP) VALUES(:OLPRNC,:OLPROG,:OLDCTO,:OLDOCO,:OLLNID,:OLLOTN,:OLQTSP) WITH NC";
			$stmt = $this->db_connection->prepare($insertSql);
			if (!$stmt)
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}			
			 
			// Bind the parameters
			$stmt->bindValue(':OLPRNC', $TMPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);
			$stmt->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR);  
			$stmt->bindValue(':OLLNID', $OLLNID, PDO::PARAM_INT);
			$stmt->bindValue(':OLLOTN', $OLLOTN, PDO::PARAM_STR);  	
			$stmt->bindValue(':OLQTSP', $OLQTSP, PDO::PARAM_INT);  			
			
			$result = $stmt->execute();
			if ($result === false) 
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}
			
		} else {
			
		 	//progressivo:
		 	$OLPROG = $this->getProgre("NONCOL0F");
		 	 	 
			$insertSql = "INSERT INTO BCD_DATIV2.NONCOL0F (OLPRNC,OLPROG,OLDCTO,OLDOCO,OLLNID,OLLOTN,OLQTSP) VALUES(:OLPRNC,:OLPROG,:OLDCTO,:OLDOCO,:OLLNID,:OLLOTN,:OLQTSP) WITH NC";
			$stmt = $this->db_connection->prepare($insertSql);
			if (!$stmt)
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}			
			 
			// Bind the parameters 
			$stmt->bindValue(':OLPRNC', $TMPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);
			$stmt->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR);	
			$stmt->bindValue(':OLLNID', $OLLNID, PDO::PARAM_INT); 
			$stmt->bindValue(':OLLOTN', $OLLOTN, PDO::PARAM_STR);		
			$stmt->bindValue(':OLQTSP', $OLQTSP, PDO::PARAM_INT);  			
			
			$result = $stmt->execute();
			if ($result === false) 
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}
		}
		
		echo '[{"stat":"OK"}]';	
	}
	
	protected function fltOrdColFor() {
		$filt_NCAB8_occ = xl_get_parameter("filt_NCAB8_occ");
		$filt_NCLOTN_occ = xl_get_parameter("filt_NCLOTN_occ");
		
		$_SESSION["filt_NCAB8_occ"] = $filt_NCAB8_occ;
		$_SESSION["filt_NCLOTN_occ"] = $filt_NCLOTN_occ;
		 
		$this->srcOrdColFor();
	}
	
	protected function srcOrdColFor() {
		
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");		
		$RRNF = (int) xl_get_parameter("RRNF");	
		
		//escludere ordine principale:
		$excludePDDCTO = "";
		$excludePDDOCO = "";
		$excludePDLNID = "";
		$excludePRLOTN = "";
		$excludeFilt = false;
		if($tipoIns=="T") {			
			if($RRNF != 0) {
		 		$retArray = $this->getDatiOrdFor($RRNF); 
				$excludePDDCTO = $retArray["PDDCTO"];
				$excludePDDOCO = $retArray["PDDOCO"];
				$excludePDLNID = $retArray["PDLNID"];
				$excludePRLOTN = $retArray["PRLOTN"]; 
				$excludeFilt = true;
			}
		} else {
			$query = "SELECT NCDCTO,NCDOCO,NCLNID,NCLOTN 
			FROM BCD_DATIV2.NONCON0F 
			WHERE NCPROG = ".$TMPROG;
			$stmt_ex = $this->db_connection->prepare($query);
			$result_ex = $stmt_ex->execute(); 
			$row_ex = $stmt_ex->fetch(PDO::FETCH_ASSOC); 
			if($row_ex) {
				$excludePDDCTO = $row_ex["NCDCTO"];
				$excludePDDOCO = $row_ex["NCDOCO"];
				$excludePDLNID = $row_ex["NCLNID"];
				$excludePRLOTN = $row_ex["NCLOTN"]; 
				$excludeFilt = true;
			}
		}		
		//escludere ordine principale [f]
		
		$filt_NCAB8_occ = "";
		$filt_NCLOTN_occ = ""; 
		if(isset($_SESSION["filt_NCAB8_occ"])) $filt_NCAB8_occ = $_SESSION["filt_NCAB8_occ"];
		if(isset($_SESSION["filt_NCLOTN_occ"])) $filt_NCLOTN_occ = $_SESSION["filt_NCLOTN_occ"];
		 
		$selString = 'SELECT MIN(RRN(JRGDTA94C.F43121)) AS RRNF, JRGDTA94C.F4311.PDAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4311.PDKCOO, 
		JRGDTA94C.F4311.PDDOCO, JRGDTA94C.F4311.PDDCTO, JRGDTA94C.F4311.PDLNID, JRGDTA94C.F43121.PRVRMK, JRGDTA94C.F4311.PDLITM, 
		JRGDTA94C.F4311.PDDSC1, CAST(JRGDTA94C.F4311.PDUORG / 100 AS INTEGER) AS PDUORG, 
		JRGDTA94C.F43121.PRLOTN, JRGDTA94C.F43121.PRUORG 
		FROM JRGDTA94C.F43121 
		inner join JRGDTA94C.F4311 on PDDOCO=PRDOCO AND PDLNID=PRLNID AND PDKCOO=PRKCOO AND PDDCTO=PRDCTO 
		inner join JRGDTA94C.F0101 on JRGDTA94C.F4311.PDAN8 = JRGDTA94C.F0101.ABAN8 	
		';
		
		$whereClause = ' WHERE PDDCTO NOT IN (\'ST\' , \'OF\' , \'SQ\')  AND PDLNTY=\'S\' ';
		$link = ' AND '; 
		
		if($excludeFilt) {
			$whereClause = $whereClause . $link . " 
			(
			PDDCTO <> '".$excludePDDCTO."' 
			OR PDDOCO <> '".$excludePDDOCO."'
			OR PDLNID <> '".$excludePDLNID."'
			OR PRLOTN <> '".$excludePRLOTN."'
			)
			";
			$link = ' AND ';		
		}
		
		//escludere ordini già collegati:
		if($tipoIns=="T") {
			$whereClause = $whereClause . $link . " NOT EXISTS(SELECT 1 AS HASCOL FROM BCD_DATIV2.NONCOLTF WHERE OLPRNC = '".$TMPROG."' AND OLDCTO = PDDCTO AND OLDOCO = PDDOCO AND OLLNID = PDLNID AND OLLOTN = PRLOTN) ";
			$link = ' AND ';
		} 
		else {
			$whereClause = $whereClause . $link . " NOT EXISTS(SELECT 1 AS HASCOL FROM BCD_DATIV2.NONCOL0F WHERE OLPRNC = '".$TMPROG."' AND OLDCTO = PDDCTO AND OLDOCO = PDDOCO AND OLLNID = PDLNID AND OLLOTN = PRLOTN) ";
			$link = ' AND ';
		} 		
		
		if ($filt_NCAB8_occ != '')
		{
			$whereClause = $whereClause . $link . ' JRGDTA94C.F4311.PDAN8 = :PDAN8';
			$link = ' AND ';
		}		 
		if ($filt_NCLOTN_occ != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.F43121.PRLOTN) LIKE :PRLOTN';
			$link = ' AND ';
		}
		$selString.=$whereClause;
		$selString .= ' GROUP BY JRGDTA94C.F4311.PDAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4311.PDKCOO, 
		JRGDTA94C.F4311.PDDOCO, JRGDTA94C.F4311.PDDCTO, JRGDTA94C.F4311.PDLNID, JRGDTA94C.F43121.PRVRMK, JRGDTA94C.F4311.PDLITM, JRGDTA94C.F4311.PDDSC1, 
		JRGDTA94C.F43121.PRLOTN, CAST(JRGDTA94C.F4311.PDUORG / 100 AS INTEGER), JRGDTA94C.F43121.PRUORG 
		ORDER BY PDDOCO DESC 
		FETCH FIRST 50 ROWS ONLY
		';
	  
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString);
	 
	 	$PDAN8 = $filt_NCAB8_occ;
	 	$PRLOTN = $filt_NCLOTN_occ; 
	 
		if ($filt_NCAB8_occ != '') $stmt->bindValue(':PDAN8', $PDAN8, PDO::PARAM_INT);
		if ($filt_NCLOTN_occ != '') $stmt->bindValue(':PRLOTN', '%'.strtolower($PRLOTN).'%', PDO::PARAM_STR); 
		
		$result = $stmt->execute(); 
		
		$i = 0;  
		// Display each of the records, up to $listSize
		$this->writeSegment("hOrdColFor", array_merge(get_object_vars($this), get_defined_vars()));	
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$hasCol = false;
			/*
			if($tipoIns=="T") $query = "SELECT 1 AS HASCOL FROM BCD_DATIV2.NONCOLTF WHERE OLPRNC = '".$TMPROG."' AND OLDCTO = '".$PDDCTO."'  AND OLDOCO = '".$PDDOCO."' AND OLLNID = '".$PDLNID."' AND OLLOTN = '".$PRLOTN."'";
			else $query = "SELECT 1 AS HASCOL FROM BCD_DATIV2.NONCOL0F WHERE OLPRNC = '".$TMPROG."' AND OLDCTO = '".$PDDCTO."'  AND OLDOCO = '".$PDDOCO."' AND OLLNID = '".$PDLNID."' AND OLLOTN = '".$PRLOTN."'";
			$stmt_co = $this->db_connection->prepare($query);
			$result_co = $stmt_co->execute(); 
			$row_co = $stmt_co->fetch(PDO::FETCH_ASSOC);
			if($row_co && $row_co["HASCOL"]==1) { $hasCol = true; }
			*/
			
			$PRUORG = $PRUORG / 100;
			
			$this->writeSegment("dOrdColFor", array_merge(get_object_vars($this), get_defined_vars()));	
			
			$i++;
		}
		
		if($i==0) { $nrColspan = "7"; $this->writeSegment('NoRecords', array_merge(get_object_vars($this), get_defined_vars())); }
		
		$this->writeSegment("fOrdColFor", array_merge(get_object_vars($this), get_defined_vars()));	
			
	}

	
	// Show the add page
	protected function beginAddCli()
	{
		$TIPOFILE = xl_get_parameter("TIPOFILE");
		$RRNF = xl_get_parameter("RRNF");
 		 
 		$retArray = $this->getDatiOrdCli($TIPOFILE,$RRNF);
		foreach(array_keys($retArray) as $key)
		{
			$retArray[$key] = rtrim($retArray[$key]);
			$escapedField = xl_fieldEscape($key);
			$$escapedField = $retArray[$key];
		}
 		 
		//progressivo temporaneo per allegati:
		$TMPROG = $this->getProgre("NONCNT0F");
		 
		$NCPROG = "";
		$NCDTEM = date("d-m-Y");
		$NCTPCF = "C";
		$NCAB8 = $SDAN8;
		$NCRGCF = $ABALPH;
		$NCDCTO = $SDDCTO;
		$NCDOCO = $SDDOCO;
		$NCLNID = $SDLNID;
		$NCLITM = $SDLITM;
		$NCDSC1 = $SDDSC1;
		$NCLOTN = $SDLOTN;
		$NCLOT2 = $IOLOT2; 
		$NCUORG = $SDUORG;
		$NCPZCO = $NCUORG / 100;
		$NCPPCO = "100";
		$NCFORN = $IOVEND;
		$NCRICL = "";
		$NCDTRC = "";
		$NCDIFE = "";
		$NCRISO = "";
		$NCSOEF = "";
		$NCACEF = "";
		$NCCHIU = "";
		$NCDTCH = "";
		$NCNOTE = "";
		$NCRIFE = "";
		$NCORFO = "";
		$NCTRFO = "";
		
		$NCRSQT = "";
		$NCRSDT = "";
		$NCSEQT = "";
		$NCSEDT = "";
		$NCAEQT = "";
		$NCAEDT = "";
		$NCRANC = "";
		$NCCATE = "";
		
		$NCQTSP = $SDUORG / 100;
		$NCQTSE = "";
		$NCQTRI = "";
		$NCDTSP = "";
		
		$NCQTOP = $NCQTSP;
		
		// Output the segment
		$this->writeSegment('RcdAdd', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	protected function dspNoDetailsMsg($noDetailsMsg) {
		$this->writeSegment("NoDetails", array_merge(get_object_vars($this), get_defined_vars()));	
	}
	
	protected function getDatiOrdCli($TIPOFILE,$RRNF) {

		$SDLNID = ''; 
		$SDPSN = ''; 
		$SDDELN = ''; 
		$SDDOC = ''; 
		$SDLITM = ''; 
		$SDDSC1 = ''; 
		$SDLOTN = ''; 
		$SDIVD = ''; 
		$ABALPH = ''; 
		$SDAN8 = ''; 
		$SDUORG = ''; 
		$SDMCU = ''; 
		$NCRGFO = '';
		$SDDCTO = '';		
		$SDDOCO = '';						
		$SDSOQS = '';
		$IOLOT2 = '';
		$IOVEND = '';
		 
		$retArray = array();
		
		if($TIPOFILE=="1") { 
 
			$selString = 'SELECT JRGDTA94C.F4211.SDKCOO, JRGDTA94C.F4211.SDDOCO, JRGDTA94C.F4211.SDDCTO, 
			JRGDTA94C.F4211.SDLNID, JRGDTA94C.F4211.SDPSN, JRGDTA94C.F4211.SDDELN, JRGDTA94C.F4211.SDDOC, 
			JRGDTA94C.F4211.SDLITM, JRGDTA94C.F4211.SDDSC1, JRGDTA94C.F4211.SDLOTN, JRGDTA94C.F4211.SDIVD, 
			JRGDTA94C.F0101.ABALPH, JRGDTA94C.F4211.SDAN8, JRGDTA94C.F4211.SDUORG, JRGDTA94C.F4211.SDMCU, 
			JRGDTA94C.F4211.SDSOQS 
			FROM JRGDTA94C.F4211 
			inner join JRGDTA94C.F0101 on JRGDTA94C.F4211.SDAN8 = JRGDTA94C.F0101.ABAN8  
			WHERE RRN(JRGDTA94C.F4211) = :RRNF 
			AND SDDCTO NOT IN (\'ST\' , \'OF\' , \'SQ\') AND SDLNTY=\'S\' 
			';
			$stmt = $this->db_connection->prepare($selString);
			if (!$stmt)
			{
				$this->dieWithPDOError($stmt);
			}			
	 		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT);
	 		 
			$result = $stmt->execute();
			if (!$result)
			{
				$this->dieWithPDOError($stmt);
			}			
 
			$row = $stmt->fetch(PDO::FETCH_ASSOC); 
			if($row) {
				foreach(array_keys($row) as $key)
				{
					$row[$key] = rtrim($row[$key]);
					$escapedField = xl_fieldEscape($key);
					$$escapedField = $row[$key];
				}
			}
			
			$IOLOT2 = '';
			$IOVEND = '';
			$selString = "SELECT JRGDTA94C.F4108.IOLOT2, JRGDTA94C.F4108.IOVEND, COALESCE(JRGDTA94C.F0101.ABALPH, '') AS NCRGFO 
			FROM JRGDTA94C.F4108 
			LEFT JOIN JRGDTA94C.F0101 ON IOVEND = ABAN8 
			WHERE IOLOTN=:SDLOTN AND IOMCU=:LIMCU AND IOLITM=:SDLITM";
			$stmt = $this->db_connection->prepare($selString);
			if (!$stmt)
			{
				$this->dieWithPDOError($stmt);
			}			
			$stmt->bindValue(':SDLOTN', $SDLOTN, PDO::PARAM_STR);
	 		$stmt->bindValue(':LIMCU', $SDMCU, PDO::PARAM_STR);
	 		$stmt->bindValue(':SDLITM', $SDLITM, PDO::PARAM_STR);
			$result = $stmt->execute();
			if (!$result)
			{
				$this->dieWithPDOError($stmt);
			}			
 
			$row = $stmt->fetch(PDO::FETCH_ASSOC); 
			if($row) {
				foreach(array_keys($row) as $key)
				{
					$row[$key] = rtrim($row[$key]);
					$escapedField = xl_fieldEscape($key);
					$$escapedField = $row[$key];
				}
			}
		}
		if($TIPOFILE=="2") { 
			
			$selString = 'SELECT JRGDTA94C.F42119.SDKCOO, JRGDTA94C.F42119.SDDOCO, JRGDTA94C.F42119.SDDCTO, 
			JRGDTA94C.F42119.SDLNID, JRGDTA94C.F42119.SDPSN, JRGDTA94C.F42119.SDDELN, JRGDTA94C.F42119.SDDOC, 
			JRGDTA94C.F42119.SDLITM, JRGDTA94C.F42119.SDDSC1, JRGDTA94C.F42119.SDLOTN, JRGDTA94C.F42119.SDIVD, 
			JRGDTA94C.F0101.ABALPH, JRGDTA94C.F42119.SDAN8, JRGDTA94C.F42119.SDUORG, JRGDTA94C.F42119.SDMCU, 
			JRGDTA94C.F42119.SDSOQS    
			FROM JRGDTA94C.F42119 
			inner join JRGDTA94C.F0101 on JRGDTA94C.F42119.SDAN8 = JRGDTA94C.F0101.ABAN8  
			WHERE RRN(JRGDTA94C.F42119) = :RRNF 
			AND SDDCTO NOT IN (\'ST\' , \'OF\' , \'SQ\') AND SDLNTY=\'S\' 
			';
			$stmt = $this->db_connection->prepare($selString);
			if (!$stmt)
			{
				$this->dieWithPDOError($stmt);
			}			 
	 		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT);
			$result = $stmt->execute();
			if (!$result)
			{
				$this->dieWithPDOError($stmt);
			}			
 
			$row = $stmt->fetch(PDO::FETCH_ASSOC); 
			if($row) {
				foreach(array_keys($row) as $key)
				{
					$row[$key] = rtrim($row[$key]);
					$escapedField = xl_fieldEscape($key);
					$$escapedField = $row[$key];
				}
			}
			
			$IOLOT2 = '';
			$IOVEND = '';
			$selString = "SELECT JRGDTA94C.F4108.IOLOT2, JRGDTA94C.F4108.IOVEND, COALESCE(JRGDTA94C.F0101.ABALPH, '') AS NCRGFO 
			FROM JRGDTA94C.F4108 
			LEFT JOIN JRGDTA94C.F0101 ON IOVEND = ABAN8 
			WHERE IOLOTN=:SDLOTN AND IOMCU=:LIMCU AND IOLITM=:SDLITM";
			$stmt = $this->db_connection->prepare($selString);
			if (!$stmt)
			{
				$this->dieWithPDOError($stmt);
			}			
			$stmt->bindValue(':SDLOTN', $SDLOTN, PDO::PARAM_STR);
	 		$stmt->bindValue(':LIMCU', $SDMCU, PDO::PARAM_STR);
	 		$stmt->bindValue(':SDLITM', $SDLITM, PDO::PARAM_STR);
			$result = $stmt->execute();
			if (!$result)
			{
				$this->dieWithPDOError($stmt);
			}			
 
			$row = $stmt->fetch(PDO::FETCH_ASSOC); 
			if($row) {
				foreach(array_keys($row) as $key)
				{
					$row[$key] = rtrim($row[$key]);
					$escapedField = xl_fieldEscape($key);
					$$escapedField = $row[$key];
				}
			}			
		}
		
		$retArray["SDLNID"] = $SDLNID;
		$retArray["SDPSN"]  = $SDPSN;
		$retArray["SDDELN"] = $SDDELN;
		$retArray["SDDOC"]  = $SDDOC;
		$retArray["SDLITM"] = $SDLITM;
		$retArray["SDDSC1"] = $SDDSC1;
		$retArray["SDLOTN"] = $SDLOTN;
		$retArray["SDIVD"]  = $SDIVD;
		$retArray["ABALPH"] = $ABALPH;
		$retArray["SDAN8"]  = $SDAN8;
		$retArray["SDUORG"] = $SDUORG;
		$retArray["SDMCU"]  = $SDMCU;
		$retArray["NCRGFO"] = $NCRGFO; 
		$retArray["SDDCTO"] = $SDDCTO; 
		$retArray["SDDOCO"] = $SDDOCO; 
		$retArray["IOLOT2"] = $IOLOT2; 
		$retArray["IOVEND"] = $IOVEND; 
		$retArray["SDSOQS"] = $SDSOQS;
		 
		return $retArray;
		
	}
	
	protected function flstAlleg() {
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");
		$this->lstAllegati($tipoIns,$TMPROG);
	}
	
	protected function lstAllegati($tipoIns,$TMPROG) {
		if($tipoIns=="T") {
			$selString = "SELECT * 
			FROM BCD_DATIV2.NONCOATF 
			WHERE NAPRNC = ".$TMPROG."
			";
		} else {
			$selString = "SELECT * 
			FROM BCD_DATIV2.NONCOA0F 
			WHERE NAPRNC = ".$TMPROG."
			";	
		}

		$x = 0;

		
 		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if($x==0) $this->writeSegment("hAlleg", array_merge(get_object_vars($this), get_defined_vars()));	
			
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			
			$this->writeSegment("dAlleg", array_merge(get_object_vars($this), get_defined_vars()));
			
			$x++;	
		} 
		
		if($x>0) $this->writeSegment("fAlleg", array_merge(get_object_vars($this), get_defined_vars()));	
		else { 
			$this->dspNoDetailsMsg("Nessun allegato"); 
		}
		
	}

	protected function dltAlleg() {
		$tipoIns = xl_get_parameter("tipoIns");
		$NAPROG = xl_get_parameter("NAPROG");
		
		if($tipoIns=="T") {
			$selString = "DELETE FROM BCD_DATIV2.NONCOATF WHERE NAPROG = :NAPROG WITH NC";
		} else {
			$selString = "DELETE FROM BCD_DATIV2.NONCOA0F WHERE NAPROG = :NAPROG WITH NC";
		}
		
		$stmt = $this->db_connection->prepare($selString);
		$stmt->bindValue(':NAPROG', $NAPROG, PDO::PARAM_INT);
		$result = $stmt->execute();
		if ($result === false) 
		{
			echo '[{"stat":"ERR","id":"allegato","msg":"Errore cancellazione"}]';
			exit;
		}		
		 
		echo '[{"stat":"OK"}]';
	}
	
	protected function dspAlleg() {
		$tipoIns = xl_get_parameter("tipoIns");
		$NAPROG = xl_get_parameter("NAPROG");
 
		if($tipoIns=="T") {
			$selString = "SELECT * FROM BCD_DATIV2.NONCOATF WHERE NAPROG = :NAPROG "; 
		} else {
			$selString = "SELECT * FROM BCD_DATIV2.NONCOA0F WHERE NAPROG = :NAPROG ";
		} 
		
		$stmt = $this->db_connection->prepare($selString);
		$stmt->bindValue(':NAPROG', $NAPROG, PDO::PARAM_INT);
		$result = $stmt->execute();
		if ($result === false) 
		{
			echo '[{"stat":"ERR","id":"allegato","msg":"Errore lettura"}]';
			exit;
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$NAPATH = trim($row["NAPATH"]);
		$NAFILN = trim($row["NAFILN"]);	
		
		 
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream'); 
		header('Content-Transfer-Encoding: binary');
		header('Connection: Keep-Alive');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public'); 
		header('Content-Disposition: attachment; filename="' . $NAFILN. '"'); 		 
		echo file_get_contents($NAPATH);
	}
	 
	protected function addAlleg() {
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");
		
		$this->writeSegment("formAlleg", array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	protected function addAlleg1() {
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");
		
		if(!isset($_FILES["allegato"]["tmp_name"]) || $_FILES["allegato"]["tmp_name"]=="") {
			echo '[{"stat":"ERR","id":"allegato","msg":"Selezionare un file"}]';
			exit;
		}
		
		if($tipoIns=="T") {
 
		 	//progressivo:
		 	$NAPROG = $this->getProgre("NONCOATF");
		 	 	 
			$insertSql = "INSERT INTO BCD_DATIV2.NONCOATF (NAPRNC,NAPROG,NAFILN,NAEXT,NAPATH,NADTIN,NAORIN) VALUES(:NAPRNC,:NAPROG,:NAFILN,:NAEXT,:NAPATH,:NADTIN,:NAORIN) WITH NC";
			$stmt = $this->db_connection->prepare($insertSql);
			if (!$stmt)
			{
				echo '[{"stat":"ERR","id":"allegato","msg":"Errore upload del file 2"}]';
				exit;
			}			
			
			$target_dir = "/www/php80/htdocs/CRUD/uploads/temp/";
			$file_extension = pathinfo($_FILES["allegato"]["name"], PATHINFO_EXTENSION);
			$target_file = $target_dir . $NAPROG . "." . $file_extension;
			
			if (!move_uploaded_file($_FILES["allegato"]["tmp_name"], $target_file)) {
				echo '[{"stat":"ERR","id":"allegato","msg":"Errore upload del file 1"}]';
				exit;
			} 
			
			// Bind the parameters
			$NAFILN = basename($_FILES["allegato"]["name"]);
			$NAEXT = $file_extension;
			$NAPATH = $target_file;
			$NADTIN = date("Ymd");
			$NAORIN = date("His");

			$stmt->bindValue(':NAPRNC', $TMPROG, PDO::PARAM_INT);
			$stmt->bindValue(':NAPROG', $NAPROG, PDO::PARAM_INT);
			$stmt->bindValue(':NAFILN', $NAFILN, PDO::PARAM_STR);
			$stmt->bindValue(':NAEXT', $NAEXT, PDO::PARAM_STR); 
			$stmt->bindValue(':NAPATH', $NAPATH, PDO::PARAM_STR); 
			$stmt->bindValue(':NADTIN', $NADTIN, PDO::PARAM_INT); 			
			$stmt->bindValue(':NAORIN', $NAORIN, PDO::PARAM_INT); 			
			
			$result = $stmt->execute();
			if ($result === false) 
			{
				echo '[{"stat":"ERR","id":"allegato","msg":"Errore upload del file 2"}]';
				exit;
			}
			
		} else {
			
		 	//progressivo:
		 	$NAPROG = $this->getProgre("NONCOA0F");
		 	 	 
			$insertSql = "INSERT INTO BCD_DATIV2.NONCOA0F (NAPRNC,NAPROG,NAFILN,NAEXT,NAPATH,NADTIN,NAORIN) VALUES(:NAPRNC,:NAPROG,:NAFILN,:NAEXT,:NAPATH,:NADTIN,:NAORIN) WITH NC";
			$stmt = $this->db_connection->prepare($insertSql);
			if (!$stmt)
			{
				echo '[{"stat":"ERR","id":"allegato","msg":"Errore upload del file 2"}]';
				exit;
			}			
			
			$target_dir = "/www/php80/htdocs/CRUD/uploads/";
			$file_extension = pathinfo($_FILES["allegato"]["name"], PATHINFO_EXTENSION);
			$target_file = $target_dir . $NAPROG . "." . $file_extension;
			
			if (!move_uploaded_file($_FILES["allegato"]["tmp_name"], $target_file)) {
				echo '[{"stat":"ERR","id":"allegato","msg":"Errore upload del file 1"}]';
				exit;
			} 
			
			// Bind the parameters
			$NAFILN = basename($_FILES["allegato"]["name"]);
			$NAEXT = $file_extension;
			$NAPATH = $target_file;
			$NADTIN = date("Ymd");
			$NAORIN = date("His");

			$stmt->bindValue(':NAPRNC', $TMPROG, PDO::PARAM_INT);
			$stmt->bindValue(':NAPROG', $NAPROG, PDO::PARAM_INT);
			$stmt->bindValue(':NAFILN', $NAFILN, PDO::PARAM_STR);
			$stmt->bindValue(':NAEXT', $NAEXT, PDO::PARAM_STR); 
			$stmt->bindValue(':NAPATH', $NAPATH, PDO::PARAM_STR); 
			$stmt->bindValue(':NADTIN', $NADTIN, PDO::PARAM_INT); 			
			$stmt->bindValue(':NAORIN', $NAORIN, PDO::PARAM_INT); 			
			
			$result = $stmt->execute();
			if ($result === false) 
			{
				echo '[{"stat":"ERR","id":"allegato","msg":"Errore upload del file 2"}]';
				exit;
			}
		}
		
		echo '[{"stat":"OK"}]';
		
	}
	
	// Add the passed in data as a new row
	protected function endAddCli()
	{
		// Get values from the page
 
 		header("Content-Type: application/json");

		$TMPROG = (int) xl_get_parameter('TMPROG');
 
		$NCDTEM = xl_get_parameter('NCDTEM');
		$NCTPCF = "C";
		$NCAB8 = xl_get_parameter('NCAB8');
		$NCRGCF = xl_get_parameter('NCRGCF');
		$NCDCTO = xl_get_parameter('NCDCTO');
		$NCDOCO = xl_get_parameter('NCDOCO');
		$NCLNID = xl_get_parameter('NCLNID');
		$NCLITM = xl_get_parameter('NCLITM');
		$NCDSC1 = xl_get_parameter('NCDSC1');
		$NCLOTN = xl_get_parameter('NCLOTN');
		$NCLOT2 = xl_get_parameter('NCLOT2');
		$NCPZCO = xl_get_parameter('NCPZCO');
		$NCPPCO = xl_get_parameter('NCPPCO');
		$NCFORN = xl_get_parameter('NCFORN');
		$NCRICL = xl_get_parameter('NCRICL');
		$NCDTRC = xl_get_parameter('NCDTRC');
		$NCDIFE = xl_get_parameter('NCDIFE');
		$NCRISO = xl_get_parameter('NCRISO');
		$NCSOEF = xl_get_parameter('NCSOEF');
		$NCACEF = xl_get_parameter('NCACEF');
		$NCCHIU = xl_get_parameter('NCCHIU');
		$NCDTCH = xl_get_parameter('NCDTCH');
		$NCNOTE = xl_get_parameter('NCNOTE');
		$NCUORG = xl_get_parameter('NCUORG');
		$NCRGFO = xl_get_parameter('NCRGFO');
		$NCORFO = xl_get_parameter('NCORFO');
		$NCTRFO = xl_get_parameter('NCTRFO');
		
		$NCRSQT = xl_get_parameter('NCRSQT'); 
		$NCRSDT = xl_get_parameter('NCRSDT'); 
		$NCSEQT = xl_get_parameter('NCSEQT'); 
		$NCSEDT = xl_get_parameter('NCSEDT'); 
		$NCAEQT = xl_get_parameter('NCAEQT'); 
		$NCAEDT = xl_get_parameter('NCAEDT'); 		
		$NCRANC = xl_get_parameter('NCRANC'); 		
		$NCCATE = xl_get_parameter('NCCATE'); 		
		 
 		$NCQTSP = xl_get_parameter('NCQTSP'); 	 
 		$NCQTSE = xl_get_parameter('NCQTSE'); 	 
 		$NCQTRI = xl_get_parameter('NCQTRI'); 	 
 		$NCDTSP = xl_get_parameter('NCDTSP'); 	 		 
		 
		$NCQTOP = xl_get_parameter('NCQTOP'); 	 		  
		 
 		$NCRIFE = 0;
 		 
		// Do any add validation here
		$errorMsg = $this->checkRecordCli();
		if($errorMsg!="") {
			echo '['.$errorMsg.']';
			exit;
		}

	 	$NCDTEM = substr($NCDTEM,6,4).substr($NCDTEM,3,2).substr($NCDTEM,0,2);
	 	$NCDTRC = substr($NCDTRC,6,4).substr($NCDTRC,3,2).substr($NCDTRC,0,2);
	 	$NCDTCH = substr($NCDTCH,6,4).substr($NCDTCH,3,2).substr($NCDTCH,0,2);
		$NCRSDT = substr($NCRSDT,6,4).substr($NCRSDT,3,2).substr($NCRSDT,0,2);
		$NCSEDT = substr($NCSEDT,6,4).substr($NCSEDT,3,2).substr($NCSEDT,0,2);
		$NCAEDT = substr($NCAEDT,6,4).substr($NCAEDT,3,2).substr($NCAEDT,0,2);
		$NCDTSP = substr($NCDTSP,6,4).substr($NCDTSP,3,2).substr($NCDTSP,0,2);
		
	 	if(!is_numeric($NCDTEM)) $NCDTEM = 0;
	 	if(!is_numeric($NCDTRC)) $NCDTRC = 0;
	 	if(!is_numeric($NCDTCH)) $NCDTCH = 0;
	 	if(!is_numeric($NCRSDT)) $NCRSDT = 0;
	 	if(!is_numeric($NCSEDT)) $NCSEDT = 0;
	 	if(!is_numeric($NCAEDT)) $NCAEDT = 0;
	 	if(!is_numeric($NCDTSP)) $NCDTSP = 0;
	 	
	 	if(!is_numeric($NCRSQT)) $NCRSQT = 0;
	 	if(!is_numeric($NCSEQT)) $NCSEQT = 0;
	 	if(!is_numeric($NCAEQT)) $NCAEQT = 0;
	 	if(!is_numeric($NCQTSP)) $NCQTSP = 0;
	 	if(!is_numeric($NCQTSE)) $NCQTSE = 0;
	 	if(!is_numeric($NCQTRI)) $NCQTRI = 0;
	 	if(!is_numeric($NCQTOP)) $NCQTOP = 0;
	 	 
	 	//progressivo:
	 	$NCPROG = $this->getProgre("NONCON0F");
	 	 
		// Prepare the statement to add the record
		$insertSql = 'INSERT INTO BCD_DATIV2.NONCON0F (NCPROG, NCUORG, NCRGFO, NCDTEM, NCTPCF, NCAB8, NCRGCF, NCDCTO, NCDOCO, NCLNID, 
		NCLITM, NCDSC1, NCLOTN, NCLOT2, NCPZCO, NCPPCO, NCFORN, NCRICL, NCDTRC, NCDIFE, NCRISO, NCSOEF, NCACEF, NCCHIU, NCDTCH, NCNOTE, 
		NCRIFE, NCORFO, NCTRFO, NCRSQT, NCRSDT, NCSEQT, NCSEDT, NCAEQT, NCAEDT, NCRANC, NCCATE, NCQTSP, NCQTSE, NCQTRI, NCDTSP, NCQTOP) VALUES(:NCPROG, :NCUORG, :NCRGFO, :NCDTEM, :NCTPCF, :NCAB8, :NCRGCF, :NCDCTO, :NCDOCO, :NCLNID, :NCLITM, 
		:NCDSC1, :NCLOTN, :NCLOT2, :NCPZCO, :NCPPCO, :NCFORN, :NCRICL, :NCDTRC, :NCDIFE, :NCRISO, :NCSOEF, :NCACEF, :NCCHIU, :NCDTCH, 
		:NCNOTE, :NCRIFE, :NCORFO, :NCTRFO, :NCRSQT, :NCRSDT, :NCSEQT, :NCSEDT, :NCAEQT, :NCAEDT, :NCRANC, :NCCATE, 
		:NCQTSP, :NCQTSE, :NCQTRI, :NCDTSP, :NCQTOP  
		)' . ' WITH NC';
		$stmt = $this->db_connection->prepare($insertSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':NCPROG', $NCPROG, PDO::PARAM_INT);
		$stmt->bindValue(':NCUORG', $NCUORG, PDO::PARAM_INT);
		$stmt->bindValue(':NCRGFO', $NCRGFO, PDO::PARAM_STR); 
		$stmt->bindValue(':NCDTEM', $NCDTEM, PDO::PARAM_INT);
		$stmt->bindValue(':NCTPCF', $NCTPCF, PDO::PARAM_STR);
		$stmt->bindValue(':NCAB8', $NCAB8, PDO::PARAM_INT);
		$stmt->bindValue(':NCRGCF', $NCRGCF, PDO::PARAM_STR);
		$stmt->bindValue(':NCDCTO', $NCDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':NCDOCO', $NCDOCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCLNID', $NCLNID, PDO::PARAM_INT);
		$stmt->bindValue(':NCLITM', $NCLITM, PDO::PARAM_STR);
		$stmt->bindValue(':NCDSC1', $NCDSC1, PDO::PARAM_STR); 
		$stmt->bindValue(':NCLOTN', $NCLOTN, PDO::PARAM_STR);
		$stmt->bindValue(':NCLOT2', $NCLOT2, PDO::PARAM_STR);
		$stmt->bindValue(':NCPZCO', $NCPZCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCPPCO', $NCPPCO, PDO::PARAM_STR);
		$stmt->bindValue(':NCFORN', $NCFORN, PDO::PARAM_INT);
		$stmt->bindValue(':NCRICL', $NCRICL, PDO::PARAM_STR);
		$stmt->bindValue(':NCDTRC', $NCDTRC, PDO::PARAM_INT);
		$stmt->bindValue(':NCDIFE', $NCDIFE, PDO::PARAM_STR);
		$stmt->bindValue(':NCRISO', $NCRISO, PDO::PARAM_STR);
		$stmt->bindValue(':NCSOEF', $NCSOEF, PDO::PARAM_STR);
		$stmt->bindValue(':NCACEF', $NCACEF, PDO::PARAM_STR);
		$stmt->bindValue(':NCCHIU', $NCCHIU, PDO::PARAM_STR);
		$stmt->bindValue(':NCDTCH', $NCDTCH, PDO::PARAM_INT);
		$stmt->bindValue(':NCNOTE', $NCNOTE, PDO::PARAM_STR);
		$stmt->bindValue(':NCRIFE', $NCRIFE, PDO::PARAM_INT);
		$stmt->bindValue(':NCORFO', $NCORFO, PDO::PARAM_INT);
		$stmt->bindValue(':NCTRFO', $NCTRFO, PDO::PARAM_STR);
		$stmt->bindValue(':NCRSQT', $NCRSQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCRSDT', $NCRSDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCSEQT', $NCSEQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCSEDT', $NCSEDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCAEQT', $NCAEQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCAEDT', $NCAEDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCRANC', $NCRANC, PDO::PARAM_STR); 
		$stmt->bindValue(':NCCATE', $NCCATE, PDO::PARAM_STR); 
		$stmt->bindValue(':NCQTSP', $NCQTSP, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTSE', $NCQTSE, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTRI', $NCQTRI, PDO::PARAM_INT);  
		$stmt->bindValue(':NCDTSP', $NCDTSP, PDO::PARAM_INT); 
		$stmt->bindValue(':NCQTOP', $NCQTOP, PDO::PARAM_INT); 
		 
		 
		// Execute the insert statement
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		
		
		//Allegati:
		$selString = "SELECT * 
		FROM BCD_DATIV2.NONCOATF 
		WHERE NAPRNC = ".$TMPROG."";
		$stmt_ia = $this->db_connection->prepare($selString); 
		$result = $stmt_ia->execute(); 
		if($result) {
			while($row = $stmt_ia->fetch(PDO::FETCH_ASSOC)) {
				
				foreach(array_keys($row) as $key)
				{ 
					$row[$key] = htmlspecialchars(rtrim($row[$key]));
					$escapedField = xl_fieldEscape($key); 
					$$escapedField = $row[$key];
				}			
				
				$target_tmp_file = $NAPATH;
				
			 	//progressivo:
			 	$NAPROG = $this->getProgre("NONCOA0F");
			 	 
				$selString = "INSERT INTO BCD_DATIV2.NONCOA0F (NAPRNC,NAPROG,NAFILN,NAEXT, NAPATH,NADTIN,NAORIN) VALUES(:NAPRNC,:NAPROG,:NAFILN,:NAEXT,:NAPATH,:NADTIN,:NAORIN) WITH NC";
				$stmt_ta = $this->db_connection->prepare($selString); 
				 
				$target_dir = "/www/php80/htdocs/CRUD/uploads/"; 
				$NAPATH = $target_dir.$NAPROG . ".".$NAEXT;
				 
				// Bind the parameters
				$stmt_ta->bindValue(':NAPRNC', $NCPROG, PDO::PARAM_INT);
				$stmt_ta->bindValue(':NAPROG', $NAPROG, PDO::PARAM_INT);
				$stmt_ta->bindValue(':NAFILN', $NAFILN, PDO::PARAM_STR);  
				$stmt_ta->bindValue(':NAEXT', $NAEXT, PDO::PARAM_STR);  
				$stmt_ta->bindValue(':NAPATH', $NAPATH, PDO::PARAM_STR);  
				$stmt_ta->bindValue(':NADTIN', $NADTIN, PDO::PARAM_INT);  
				$stmt_ta->bindValue(':NAORIN', $NAORIN, PDO::PARAM_INT);  
				
				// Execute the insert statement
				$result = $stmt_ta->execute();
				
				// Sposto file:
				rename($target_tmp_file, $NAPATH);
				
			}
		}
		
		//elimino allegati temp
		$selString = "DELETE FROM 
		BCD_DATIV2.NONCOATF 
		WHERE NAPRNC = ".$TMPROG." WITH NC";
		$res_da = $this->db_connection->exec($selString); 
   
		//Ordini legati:
		$selString = "SELECT * 
		FROM BCD_DATIV2.NONCOLTF  
		WHERE OLPRNC = ".$TMPROG."";
		$stmt_ol = $this->db_connection->prepare($selString); 
		$result = $stmt_ol->execute(); 
		if($result) {
			while($row = $stmt_ol->fetch(PDO::FETCH_ASSOC)) {
				
				foreach(array_keys($row) as $key)
				{ 
					$row[$key] = htmlspecialchars(rtrim($row[$key]));
					$escapedField = xl_fieldEscape($key); 
					$$escapedField = $row[$key];
				}			
				 
			 	//progressivo:
			 	$OLPROG = $this->getProgre("NONCOL0F");
			 	 
				$selString = "INSERT INTO BCD_DATIV2.NONCOL0F (OLPRNC,OLPROG,OLDCTO,OLDOCO,OLLOTN,OLLNID,OLQTSP) VALUES(:OLPRNC,:OLPROG,:OLDCTO,:OLDOCO,:OLLOTN,:OLLNID,:OLQTSP) WITH NC";
				$stmt_to = $this->db_connection->prepare($selString); 
				  
				// Bind the parameters
				$stmt_to->bindValue(':OLPRNC', $NCPROG, PDO::PARAM_INT);
				$stmt_to->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
				$stmt_to->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);  
				$stmt_to->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR);  
				$stmt_to->bindValue(':OLLOTN', $OLLOTN, PDO::PARAM_STR);   
				$stmt_to->bindValue(':OLLNID', $OLLNID, PDO::PARAM_STR);   
				$stmt_to->bindValue(':OLQTSP', $OLQTSP, PDO::PARAM_INT);   
				
				// Execute the insert statement
				$result = $stmt_to->execute();
				 
				
			}
		}
		
		//elimino ordini legati temp
		$selString = "DELETE FROM 
		BCD_DATIV2.NONCOLTF 
		WHERE OLPRNC = ".$TMPROG." WITH NC";
		$res_do = $this->db_connection->exec($selString);   
  
  
  
		// Redirect to the original page of the main list
		echo '[{"stat":"OK","NCPROG":"'.$NCPROG.'"}]';
	}
	
	protected function getProgre($NNTIPO) {
		$NNANNO = date("Y");
		$selString = "SELECT NNCONT FROM BCD_DATIV2.NONCNT0F WHERE NNTIPO = '".$NNTIPO."' AND NNANNO = ".$NNANNO." FETCH FIRST ROW ONLY";
		$stmt_cnt = $this->db_connection->prepare($selString);
		if (!$stmt_cnt)
		{
			$this->dieWithPDOError($stmt_cnt);
		} 
		$result_cnt = $stmt_cnt->execute();
		if ($result_cnt === false)
		{
			$this->dieWithPDOError($stmt_cnt);
		}
		$row_cnt = $stmt_cnt->fetch(PDO::FETCH_ASSOC);
		if(!$row_cnt) {
			$NNCONT = 1;
			$selString = "INSERT INTO BCD_DATIV2.NONCNT0F (NNANNO,NNTIPO,NNCONT) VALUES ('".$NNANNO."','".$NNTIPO."','".$NNCONT."') WITH NC";
			$res_cnt = $this->db_connection->exec($selString);
			return ($NNANNO * 1000000 + $NNCONT);
		} else {
			$NNCONT = $row_cnt["NNCONT"];
			$NNCONT++;
			$selString = "UPDATE BCD_DATIV2.NONCNT0F SET NNCONT = ".$NNCONT." WHERE NNANNO = '".$NNANNO."' AND NNTIPO = '".$NNTIPO."' WITH NC";
			$res_cnt = $this->db_connection->exec($selString);
			return ($NNANNO * 1000000 + $NNCONT);
		}
		
		return false;
		
	}
	
	protected function checkRecordCli() { 
		$errorMsg = "";
		$errorSep = "";

		if(trim(xl_get_parameter('NCDTEM'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCDTEM","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCAB8'))=='')  { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCAB8","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCRGCF'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCRGCF","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCDCTO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCDCTO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCDOCO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCDOCO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCLNID'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCLNID","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCLITM'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCLITM","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCLOTN'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCLOTN","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCPZCO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCPZCO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCPPCO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCPPCO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCFORN'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCFORN","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		if(trim(xl_get_parameter('NCDIFE'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCDIFE","msg":"Campo obbligatorio"}'; $errorSep = ","; }
		//if(trim(xl_get_parameter('NCORFO'))=='') { $errorMsg = $errorMsg.$errorSep.'{"stat":"ERR","id":"addNCORFO","msg":"Campo obbligatorio"}'; $errorSep = ","; }
 
		return $errorMsg;
	
	}
	
	protected function cvtDateFromDb($date) { 
		return substr($date,6,2)."-".substr($date,4,2)."-".substr($date,0,4); 
	} 
	
	protected function lstDifetti($curNCDIFE) {
		$selString = "SELECT JRGCOM94T.F0005.DRKY, JRGCOM94T.F0005.DRDL01 
		FROM JRGCOM94T.F0005 
		WHERE DRSY='55' AND DRRT='NC' 
		ORDER BY DRDL01";
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
		
		echo '<select name="NCDIFE" id="addNCDIFE" class="form-control">';
		echo '<option value=""></option>';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			foreach(array_keys($row) as $key)
			{ 
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1"); 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			echo '<option value="'.$DRKY.'" '.(($DRKY==$curNCDIFE)?('selected="selected"'):('')).'>'.$DRDL01.'</option>';
		} 
		echo '</select>';
		
	}
	
	// Show the change page
	protected function beginChangeCli()
	{
		$redirPgm = "nonconf01.php";
		$fromPgm = xl_get_parameter("fromPgm");
		if($fromPgm=="2") $redirPgm = "nonconf02.php";
		
		// Fetch parameters which identify the record
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		$keyFieldArray['NCPROG'] = (int) $keyFieldArray['NCPROG'];
		
		// Make sure we would only be changing a single record
		$stmt = $this->buildCountStmt($keyFieldArray);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		$row = $stmt->fetch(PDO::FETCH_NUM);
		if ($row[0] > 1)
		{
			die('Error: More than one record is identified by the key values specified. A record was not opened for changes.');
		}
		else if ($row[0] == 0)
		{
			die('Error: No records were identified by the key values specified. A record was not opened for changes.');
		}
		
		$record = $this->getRecord($keyFieldArray);
		
		extract($record);
		
		//vedo se esiste non conformità fornitore collegata
		$rifNcFor = 0;
		$selString = "SELECT NCPROG FROM BCD_DATIV2.NONCON0F WHERE NCTPCF = 'F' AND NCRIFE = ".$NCPROG;
		$stmt_rif = $this->db_connection->prepare($selString);
		$res_rif = $stmt_rif->execute();
		$row_rif = $stmt_rif->fetch(PDO::FETCH_ASSOC);
		if($row_rif) $rifNcFor = $row_rif["NCPROG"]; 
		 
		
		$RRNF = 0;
		
		// Output the segment
		$this->writeSegment('RcdChange', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	protected function getRecord($keyFieldArray, $excludeFromReturn = array(), $oldKeyFields = false)
	{
		// Construct and execute the SQL to select the record
		$stmt = $this->buildEntryStmt($keyFieldArray, $oldKeyFields);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Execute the select statement and store the result in the row variable
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$record = array();
		
		// Sanitize the fields
		foreach(array_keys($row) as $key)
		{
			if(in_array($key, $excludeFromReturn)){
				continue;
			}
			
			$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1");
			
			// make the file field names available in HTML
			$escapedField = xl_fieldEscape($key);
			$record[$escapedField] = $row[$key];
		}
		
		return $record;
	}
	
	// Get input values and update the database
	protected function endChangeCli()
	{
		// Fetch parameters which identify the record
		$oldKeyFieldArray = $this->getParameters(array('NCPROG_'));
		$oldKeyFieldArray['NCPROG_'] = (int) $oldKeyFieldArray['NCPROG_'];
		
		extract($oldKeyFieldArray);
		// Get values from the page
		$NCDTEM = xl_get_parameter('NCDTEM');
		$NCTPCF = "C";
		$NCAB8 = xl_get_parameter('NCAB8');
		$NCRGCF = xl_get_parameter('NCRGCF');
		$NCDCTO = xl_get_parameter('NCDCTO');
		$NCDOCO = xl_get_parameter('NCDOCO');
		$NCLNID = xl_get_parameter('NCLNID');
		$NCLITM = xl_get_parameter('NCLITM');
		$NCDSC1 = xl_get_parameter('NCDSC1');
		$NCLOTN = xl_get_parameter('NCLOTN');
		$NCLOT2 = xl_get_parameter('NCLOT2');
		$NCPZCO = xl_get_parameter('NCPZCO');
		$NCPPCO = xl_get_parameter('NCPPCO');
		$NCFORN = xl_get_parameter('NCFORN');
		$NCRICL = xl_get_parameter('NCRICL');
		$NCDTRC = xl_get_parameter('NCDTRC');
		$NCDIFE = xl_get_parameter('NCDIFE');
		$NCRISO = xl_get_parameter('NCRISO');
		$NCSOEF = xl_get_parameter('NCSOEF');
		$NCACEF = xl_get_parameter('NCACEF');
		$NCCHIU = xl_get_parameter('NCCHIU');
		$NCDTCH = xl_get_parameter('NCDTCH');
		$NCNOTE = $_REQUEST['NCNOTE'];
		$NCORFO = xl_get_parameter('NCORFO'); 
		$NCTRFO = xl_get_parameter('NCTRFO'); 

		$NCRSQT = xl_get_parameter('NCRSQT'); 
		$NCRSDT = xl_get_parameter('NCRSDT'); 
		$NCSEQT = xl_get_parameter('NCSEQT'); 
		$NCSEDT = xl_get_parameter('NCSEDT'); 
		$NCAEQT = xl_get_parameter('NCAEQT'); 
		$NCAEDT = xl_get_parameter('NCAEDT'); 
		$NCRANC = xl_get_parameter('NCRANC'); 	
		$NCCATE = xl_get_parameter('NCCATE'); 	 
 
 		$NCQTSP = xl_get_parameter('NCQTSP'); 	 
 		$NCQTSE = xl_get_parameter('NCQTSE'); 	 
 		$NCQTRI = xl_get_parameter('NCQTRI'); 	 
 		$NCDTSP = xl_get_parameter('NCDTSP'); 	 
   
		//Protect Key Fields from being Changed
		$NCPROG = $NCPROG_;
		
		//ricalcolo qta spedita e percentuale contestati:		
		$NCQTOP = xl_get_parameter('NCQTOP'); 		
		if(!is_numeric($NCQTOP)) $NCQTOP = 0;
		$NCQTSP = $this->calcNCQTSP("D",$NCPROG,$NCQTOP);
		if($NCQTSP<>0) $NCPPCO = round(($NCPZCO / $NCQTSP) * 100, 2);
		 
		// Do any add validation here
		$errorMsg = $this->checkRecordCli();
		if($errorMsg!="") {
			echo '['.$errorMsg.']';
			exit;
		}
		
	 	$NCDTEM = substr($NCDTEM,6,4).substr($NCDTEM,3,2).substr($NCDTEM,0,2);
	 	$NCDTRC = substr($NCDTRC,6,4).substr($NCDTRC,3,2).substr($NCDTRC,0,2);
	 	$NCDTCH = substr($NCDTCH,6,4).substr($NCDTCH,3,2).substr($NCDTCH,0,2);
		$NCRSDT = substr($NCRSDT,6,4).substr($NCRSDT,3,2).substr($NCRSDT,0,2);
		$NCSEDT = substr($NCSEDT,6,4).substr($NCSEDT,3,2).substr($NCSEDT,0,2);
		$NCAEDT = substr($NCAEDT,6,4).substr($NCAEDT,3,2).substr($NCAEDT,0,2);
		$NCDTSP = substr($NCDTSP,6,4).substr($NCDTSP,3,2).substr($NCDTSP,0,2);
		
	 	if(!is_numeric($NCDTEM)) $NCDTEM = 0;
	 	if(!is_numeric($NCDTRC)) $NCDTRC = 0;
	 	if(!is_numeric($NCDTCH)) $NCDTCH = 0;
	 	if(!is_numeric($NCRSDT)) $NCRSDT = 0;
	 	if(!is_numeric($NCSEDT)) $NCSEDT = 0;
	 	if(!is_numeric($NCAEDT)) $NCAEDT = 0;
	 	if(!is_numeric($NCDTSP)) $NCDTSP = 0;
	 	
	
	 	if(!is_numeric($NCRSQT)) $NCRSQT = 0;
	 	if(!is_numeric($NCSEQT)) $NCSEQT = 0;
	 	if(!is_numeric($NCAEQT)) $NCAEQT = 0;
	 	if(!is_numeric($NCQTSP)) $NCQTSP = 0;
	 	if(!is_numeric($NCQTSE)) $NCQTSE = 0;
	 	if(!is_numeric($NCQTRI)) $NCQTRI = 0;
	 	
	
		// Construct and prepare the SQL to update the record
		$updateSql = 'UPDATE BCD_DATIV2.NONCON0F SET NCTRFO = :NCTRFO, NCORFO = :NCORFO, NCDTEM = :NCDTEM, NCTPCF = :NCTPCF, 
		NCAB8 = :NCAB8, NCRGCF = :NCRGCF, NCDCTO = :NCDCTO, NCDOCO = :NCDOCO, NCLNID = :NCLNID, NCLITM = :NCLITM, 
		NCDSC1 = :NCDSC1, NCLOTN = :NCLOTN, NCLOT2 = :NCLOT2, NCPZCO = :NCPZCO, NCPPCO = :NCPPCO, NCFORN = :NCFORN, 
		NCRICL = :NCRICL, NCDTRC = :NCDTRC, NCDIFE = :NCDIFE, NCRISO = :NCRISO, NCSOEF = :NCSOEF, NCACEF = :NCACEF, 
		NCCHIU = :NCCHIU, NCDTCH = :NCDTCH, NCNOTE = :NCNOTE, 
		NCRSQT = :NCRSQT, NCRSDT = :NCRSDT, NCSEQT = :NCSEQT, 
		NCSEDT = :NCSEDT, NCAEQT = :NCAEQT, NCAEDT = :NCAEDT, 
		NCRANC = :NCRANC, NCCATE = :NCCATE, 
		NCQTSP = :NCQTSP, NCQTSE = :NCQTSE, 
		NCQTRI = :NCQTRI, NCDTSP = :NCDTSP   
		';
		$updateSql .= ' ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($updateSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':NCDTEM', $NCDTEM, PDO::PARAM_INT);
		$stmt->bindValue(':NCTPCF', $NCTPCF, PDO::PARAM_STR);
		$stmt->bindValue(':NCAB8', $NCAB8, PDO::PARAM_INT);
		$stmt->bindValue(':NCRGCF', $NCRGCF, PDO::PARAM_STR);
		$stmt->bindValue(':NCDCTO', $NCDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':NCDOCO', $NCDOCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCLNID', $NCLNID, PDO::PARAM_INT);
		$stmt->bindValue(':NCLITM', $NCLITM, PDO::PARAM_STR);
		$stmt->bindValue(':NCDSC1', $NCDSC1, PDO::PARAM_STR); 
		$stmt->bindValue(':NCLOTN', $NCLOTN, PDO::PARAM_STR);
		$stmt->bindValue(':NCLOT2', $NCLOT2, PDO::PARAM_STR);
		$stmt->bindValue(':NCPZCO', $NCPZCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCPPCO', $NCPPCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCFORN', $NCFORN, PDO::PARAM_INT);
		$stmt->bindValue(':NCRICL', $NCRICL, PDO::PARAM_STR);
		$stmt->bindValue(':NCDTRC', $NCDTRC, PDO::PARAM_INT);
		$stmt->bindValue(':NCDIFE', $NCDIFE, PDO::PARAM_STR);
		$stmt->bindValue(':NCRISO', $NCRISO, PDO::PARAM_STR);
		$stmt->bindValue(':NCSOEF', $NCSOEF, PDO::PARAM_STR);
		$stmt->bindValue(':NCACEF', $NCACEF, PDO::PARAM_STR);
		$stmt->bindValue(':NCCHIU', $NCCHIU, PDO::PARAM_STR);
		$stmt->bindValue(':NCDTCH', $NCDTCH, PDO::PARAM_INT); 
		$stmt->bindValue(':NCNOTE', mb_convert_encoding($NCNOTE, 'ISO-8859-1', 'UTF-8'), PDO::PARAM_STR); 
		$stmt->bindValue(':NCORFO', $NCORFO, PDO::PARAM_INT);
		$stmt->bindValue(':NCTRFO', $NCTRFO, PDO::PARAM_STR);  
		$stmt->bindValue(':NCRSQT', $NCRSQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCRSDT', $NCRSDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCSEQT', $NCSEQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCSEDT', $NCSEDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCAEQT', $NCAEQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCAEDT', $NCAEDT, PDO::PARAM_STR);  
		$stmt->bindValue(':NCRANC', $NCRANC, PDO::PARAM_STR);  
		$stmt->bindValue(':NCCATE', $NCCATE, PDO::PARAM_STR);  
		$stmt->bindValue(':NCQTSP', $NCQTSP, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTSE', $NCQTSE, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTRI', $NCQTRI, PDO::PARAM_INT);  
		$stmt->bindValue(':NCDTSP', $NCDTSP, PDO::PARAM_INT);  
		 
		$stmt->bindValue(':NCPROG_', $NCPROG_, PDO::PARAM_INT);
		  
		// Execute the update statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
		echo '[{"stat":"OK"}]';
	}

/********************/
	// Show the change page
	protected function beginChangeFor()
	{
		$redirPgm = "nonconf01.php";
		$fromPgm = xl_get_parameter("fromPgm");
		if($fromPgm=="2") $redirPgm = "nonconf02.php";
		
		// Fetch parameters which identify the record
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		$keyFieldArray['NCPROG'] = (int) $keyFieldArray['NCPROG'];
		
		// Make sure we would only be changing a single record
		$stmt = $this->buildCountStmt($keyFieldArray);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		$row = $stmt->fetch(PDO::FETCH_NUM);
		if ($row[0] > 1)
		{
			die('Error: More than one record is identified by the key values specified. A record was not opened for changes.');
		}
		else if ($row[0] == 0)
		{
			die('Error: No records were identified by the key values specified. A record was not opened for changes.');
		}
		
		$record = $this->getRecord($keyFieldArray);
		extract($record);
		
		$RRNF = 0;	
		
		// Output the segment
		$this->writeSegment('RcdChangeFor', array_merge(get_object_vars($this), get_defined_vars()));
	} 
	
	// Get input values and update the database
	protected function endChangeFor()
	{
		// Fetch parameters which identify the record
		$oldKeyFieldArray = $this->getParameters(array('NCPROG_'));
		$oldKeyFieldArray['NCPROG_'] = (int) $oldKeyFieldArray['NCPROG_'];
		
		extract($oldKeyFieldArray);
		// Get values from the page
		$NCDTEM = xl_get_parameter('NCDTEM');
		$NCTPCF = "F";
		$NCAB8 = xl_get_parameter('NCAB8');
		$NCRGCF = xl_get_parameter('NCRGCF');
		$NCDCTO = xl_get_parameter('NCDCTO');
		$NCDOCO = xl_get_parameter('NCDOCO');
		$NCLNID = xl_get_parameter('NCLNID');
		$NCLITM = xl_get_parameter('NCLITM');
		$NCDSC1 = xl_get_parameter('NCDSC1');
		$NCLOTN = xl_get_parameter('NCLOTN');
		$NCLOT2 = xl_get_parameter('NCLOT2');
		$NCPZCO = xl_get_parameter('NCPZCO');
		$NCPPCO = xl_get_parameter('NCPPCO');
		$NCRICL = xl_get_parameter('NCRICL');
		$NCDTRC = xl_get_parameter('NCDTRC');
		$NCDIFE = xl_get_parameter('NCDIFE');
		$NCRISO = xl_get_parameter('NCRISO');
		$NCSOEF = xl_get_parameter('NCSOEF');
		$NCACEF = xl_get_parameter('NCACEF');
		$NCCHIU = xl_get_parameter('NCCHIU');
		$NCDTCH = xl_get_parameter('NCDTCH');
		$NCNOTE = xl_get_parameter('NCNOTE');
		
		$NCRSQT = xl_get_parameter('NCRSQT'); 
		$NCRSDT = xl_get_parameter('NCRSDT'); 
		$NCSEQT = xl_get_parameter('NCSEQT'); 
		$NCSEDT = xl_get_parameter('NCSEDT'); 
		$NCAEQT = xl_get_parameter('NCAEQT'); 
		$NCAEDT = xl_get_parameter('NCAEDT'); 	
		$NCRANC = xl_get_parameter('NCRANC'); 	
		$NCCATE = xl_get_parameter('NCCATE'); 		
		
 		$NCQTSP = xl_get_parameter('NCQTSP'); 	 
 		$NCQTSE = xl_get_parameter('NCQTSE'); 	 
 		$NCQTRI = xl_get_parameter('NCQTRI'); 	 
 		$NCDTSP = xl_get_parameter('NCDTSP'); 			
		
		$NCFORN = 0;
		 
		//Protect Key Fields from being Changed
		$NCPROG = $NCPROG_;
		
		//ricalcolo qta spedita e percentuale contestati:
		$NCQTOP = xl_get_parameter('NCQTOP'); 		
		if(!is_numeric($NCQTOP)) $NCQTOP = 0;
		$NCQTSP = $this->calcNCQTSP("D",$NCPROG,$NCQTOP);
		if($NCQTSP<>0) $NCPPCO = round(($NCPZCO / $NCQTSP) * 100, 2);
		  
		// Do any add validation here
		$errorMsg = $this->checkRecordFor();
		if($errorMsg!="") {
			echo '['.$errorMsg.']';
			exit;
		}
		
	 	$NCDTEM = substr($NCDTEM,6,4).substr($NCDTEM,3,2).substr($NCDTEM,0,2);
	 	$NCDTRC = substr($NCDTRC,6,4).substr($NCDTRC,3,2).substr($NCDTRC,0,2);
	 	$NCDTCH = substr($NCDTCH,6,4).substr($NCDTCH,3,2).substr($NCDTCH,0,2);
		$NCRSDT = substr($NCRSDT,6,4).substr($NCRSDT,3,2).substr($NCRSDT,0,2);
		$NCSEDT = substr($NCSEDT,6,4).substr($NCSEDT,3,2).substr($NCSEDT,0,2);
		$NCAEDT = substr($NCAEDT,6,4).substr($NCAEDT,3,2).substr($NCAEDT,0,2);
		$NCDTSP = substr($NCDTSP,6,4).substr($NCDTSP,3,2).substr($NCDTSP,0,2);
		
	 	if(!is_numeric($NCDTEM)) $NCDTEM = 0;
	 	if(!is_numeric($NCDTRC)) $NCDTRC = 0;
	 	if(!is_numeric($NCDTCH)) $NCDTCH = 0; 
	 	if(!is_numeric($NCRSDT)) $NCRSDT = 0;
	 	if(!is_numeric($NCSEDT)) $NCSEDT = 0;
	 	if(!is_numeric($NCAEDT)) $NCAEDT = 0;
	 	if(!is_numeric($NCDTSP)) $NCDTSP = 0;
	 	 
	 	if(!is_numeric($NCRSQT)) $NCRSQT = 0;
	 	if(!is_numeric($NCSEQT)) $NCSEQT = 0;
	 	if(!is_numeric($NCAEQT)) $NCAEQT = 0;
	 	if(!is_numeric($NCQTSP)) $NCQTSP = 0;
	 	if(!is_numeric($NCQTSE)) $NCQTSE = 0;
	 	if(!is_numeric($NCQTRI)) $NCQTRI = 0;	 	
		
		// Construct and prepare the SQL to update the record
		$updateSql = 'UPDATE BCD_DATIV2.NONCON0F SET NCDTEM = :NCDTEM, NCTPCF = :NCTPCF, NCAB8 = :NCAB8, NCRGCF = :NCRGCF, 
		NCDCTO = :NCDCTO, NCDOCO = :NCDOCO, NCLNID = :NCLNID, NCLITM = :NCLITM, NCDSC1 = :NCDSC1, NCLOTN = :NCLOTN, NCLOT2 = :NCLOT2, 
		NCPZCO = :NCPZCO, NCPPCO = :NCPPCO, NCFORN = :NCFORN, NCRICL = :NCRICL, NCDTRC = :NCDTRC, NCDIFE = :NCDIFE, NCRISO = :NCRISO, 
		NCSOEF = :NCSOEF, NCACEF = :NCACEF, NCCHIU = :NCCHIU, NCDTCH = :NCDTCH, NCNOTE = :NCNOTE, 
		NCRSQT = :NCRSQT, NCRSDT = :NCRSDT, NCSEQT = :NCSEQT, 
		NCSEDT = :NCSEDT, NCAEQT = :NCAEQT, NCAEDT = :NCAEDT, 
		NCRANC = :NCRANC, NCCATE = :NCCATE,    
		NCQTSP = :NCQTSP, NCQTSE = :NCQTSE, 
		NCQTRI = :NCQTRI, NCDTSP = :NCDTSP 
		';
		$updateSql .= ' ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($updateSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':NCDTEM', $NCDTEM, PDO::PARAM_INT);
		$stmt->bindValue(':NCTPCF', $NCTPCF, PDO::PARAM_STR);
		$stmt->bindValue(':NCAB8', $NCAB8, PDO::PARAM_INT);
		$stmt->bindValue(':NCRGCF', $NCRGCF, PDO::PARAM_STR);
		$stmt->bindValue(':NCDCTO', $NCDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':NCDOCO', $NCDOCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCLNID', $NCLNID, PDO::PARAM_INT);
		$stmt->bindValue(':NCLITM', $NCLITM, PDO::PARAM_STR);
		$stmt->bindValue(':NCDSC1', $NCDSC1, PDO::PARAM_STR); 
		$stmt->bindValue(':NCLOTN', $NCLOTN, PDO::PARAM_STR);
		$stmt->bindValue(':NCLOT2', $NCLOT2, PDO::PARAM_STR);
		$stmt->bindValue(':NCPZCO', $NCPZCO, PDO::PARAM_INT);
		$stmt->bindValue(':NCPPCO', $NCPPCO, PDO::PARAM_STR);
		$stmt->bindValue(':NCFORN', $NCFORN, PDO::PARAM_INT);
		$stmt->bindValue(':NCRICL', $NCRICL, PDO::PARAM_STR);
		$stmt->bindValue(':NCDTRC', $NCDTRC, PDO::PARAM_INT);
		$stmt->bindValue(':NCDIFE', $NCDIFE, PDO::PARAM_STR);
		$stmt->bindValue(':NCRISO', $NCRISO, PDO::PARAM_STR);
		$stmt->bindValue(':NCSOEF', $NCSOEF, PDO::PARAM_STR);
		$stmt->bindValue(':NCACEF', $NCACEF, PDO::PARAM_STR);
		$stmt->bindValue(':NCCHIU', $NCCHIU, PDO::PARAM_STR);
		$stmt->bindValue(':NCDTCH', $NCDTCH, PDO::PARAM_INT);
		$stmt->bindValue(':NCNOTE', $NCNOTE, PDO::PARAM_STR); 
		$stmt->bindValue(':NCRSQT', $NCRSQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCRSDT', $NCRSDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCSEQT', $NCSEQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCSEDT', $NCSEDT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCAEQT', $NCAEQT, PDO::PARAM_STR); 
		$stmt->bindValue(':NCAEDT', $NCAEDT, PDO::PARAM_STR);  
		$stmt->bindValue(':NCRANC', $NCRANC, PDO::PARAM_STR);  
		$stmt->bindValue(':NCCATE', $NCCATE, PDO::PARAM_STR); 
		$stmt->bindValue(':NCQTSP', $NCQTSP, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTSE', $NCQTSE, PDO::PARAM_INT);  
		$stmt->bindValue(':NCQTRI', $NCQTRI, PDO::PARAM_INT);  
		$stmt->bindValue(':NCDTSP', $NCDTSP, PDO::PARAM_INT);  
		
		$stmt->bindValue(':NCPROG_', $NCPROG_, PDO::PARAM_INT);
		
		// Execute the update statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
		echo '[{"stat":"OK"}]';
	}

/********************/
	
	// Load list with filters
	protected function filterList()
	{
		// Retrieve the filter information
		
		$this->programState['filters']['NCPROG'] = trim(xl_get_parameter('filter_NCPROG'));
		$this->programState['filters']['NCTPCF'] = xl_get_parameter('filter_NCTPCF');
		
		$this->programState['filters']['NCAB8'] = trim(xl_get_parameter('filter_NCAB8'));
		$this->programState['filters']['NCRGCF'] = xl_get_parameter('filter_NCRGCF');
		$this->programState['filters']['NCDCTO'] = xl_get_parameter('filter_NCDCTO');
		
		$this->programState['filters']['NCDOCO'] = trim(xl_get_parameter('filter_NCDOCO'));
		$this->programState['filters']['NCLNID'] = trim(xl_get_parameter('filter_NCLNID'));
		$this->programState['filters']['NCLITM'] = xl_get_parameter('filter_NCLITM');
		$this->programState['filters']['NCLOTN'] = xl_get_parameter('filter_NCLOTN');
		$this->programState['filters']['NCLOT2'] = xl_get_parameter('filter_NCLOT2');
		
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
			$NCPROG_url = urlencode(rtrim($row['NCPROG']));
			
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
		if ($this->programState['filters']['NCPROG'] != '')
		{
			$stmt->bindValue(':NCPROG', strtolower($this->programState['filters']['NCPROG']), PDO::PARAM_INT);
		}
		
		if ($this->programState['filters']['NCTPCF'] != '')
		{
			$stmt->bindValue(':NCTPCF', '%' . strtolower($this->programState['filters']['NCTPCF']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['NCAB8'] != '')
		{
			$stmt->bindValue(':NCAB8', strtolower($this->programState['filters']['NCAB8']), PDO::PARAM_INT);
		}
		
		if ($this->programState['filters']['NCRGCF'] != '')
		{
			$stmt->bindValue(':NCRGCF', '%' . strtolower($this->programState['filters']['NCRGCF']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['NCDCTO'] != '')
		{
			$stmt->bindValue(':NCDCTO', '%' . strtolower($this->programState['filters']['NCDCTO']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['NCDOCO'] != '')
		{
			$stmt->bindValue(':NCDOCO', strtolower($this->programState['filters']['NCDOCO']), PDO::PARAM_INT);
		}
		
		if ($this->programState['filters']['NCLNID'] != '')
		{
			$stmt->bindValue(':NCLNID', strtolower($this->programState['filters']['NCLNID']), PDO::PARAM_INT);
		}
		
		if ($this->programState['filters']['NCLITM'] != '')
		{
			$stmt->bindValue(':NCLITM', '%' . strtolower($this->programState['filters']['NCLITM']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['NCLOTN'] != '')
		{
			$stmt->bindValue(':NCLOTN', '%' . strtolower($this->programState['filters']['NCLOTN']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['NCLOT2'] != '')
		{
			$stmt->bindValue(':NCLOT2', '%' . strtolower($this->programState['filters']['NCLOT2']) . '%', PDO::PARAM_STR);
		}
		
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildSelectString()
	{
		$selString = 'SELECT BCD_DATIV2.NONCON0F.NCPROG, BCD_DATIV2.NONCON0F.NCDTEM, BCD_DATIV2.NONCON0F.NCTPCF, BCD_DATIV2.NONCON0F.NCAB8, BCD_DATIV2.NONCON0F.NCRGCF, BCD_DATIV2.NONCON0F.NCDCTO, BCD_DATIV2.NONCON0F.NCDOCO, BCD_DATIV2.NONCON0F.NCLNID, BCD_DATIV2.NONCON0F.NCLITM, BCD_DATIV2.NONCON0F.NCDSC1, BCD_DATIV2.NONCON0F.NCLOTN, BCD_DATIV2.NONCON0F.NCLOT2, BCD_DATIV2.NONCON0F.NCPZCO, BCD_DATIV2.NONCON0F.NCPPCO, BCD_DATIV2.NONCON0F.NCFORN, BCD_DATIV2.NONCON0F.NCRICL, BCD_DATIV2.NONCON0F.NCDTRC, BCD_DATIV2.NONCON0F.NCDIFE, BCD_DATIV2.NONCON0F.NCRISO, BCD_DATIV2.NONCON0F.NCSOEF, BCD_DATIV2.NONCON0F.NCACEF, BCD_DATIV2.NONCON0F.NCCHIU, BCD_DATIV2.NONCON0F.NCDTCH, BCD_DATIV2.NONCON0F.NCNOTE, BCD_DATIV2.NONCON0F.NCRIFE FROM BCD_DATIV2.NONCON0F';
		
		return $selString;
	}
	
	// Build where clause to filter rows from table
	protected function buildWhereClause()
	{
		$whereClause = '';
		$link = 'WHERE ';
		
		// Filter by NCPROG
		if ($this->programState['filters']['NCPROG'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCPROG) = :NCPROG';
			$link = ' AND ';
		}
		
		// Filter by NCTPCF
		if ($this->programState['filters']['NCTPCF'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCTPCF) LIKE :NCTPCF';
			$link = " AND ";
		}
		
		// Filter by NCAB8
		if ($this->programState['filters']['NCAB8'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCAB8) = :NCAB8';
			$link = ' AND ';
		}
		
		// Filter by NCRGCF
		if ($this->programState['filters']['NCRGCF'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCRGCF) LIKE :NCRGCF';
			$link = " AND ";
		}
		
		// Filter by NCDCTO
		if ($this->programState['filters']['NCDCTO'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCDCTO) LIKE :NCDCTO';
			$link = " AND ";
		}
		
		// Filter by NCDOCO
		if ($this->programState['filters']['NCDOCO'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCDOCO) = :NCDOCO';
			$link = ' AND ';
		}
		
		// Filter by NCLNID
		if ($this->programState['filters']['NCLNID'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCLNID) = :NCLNID';
			$link = ' AND ';
		}
		
		// Filter by NCLITM
		if ($this->programState['filters']['NCLITM'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCLITM) LIKE :NCLITM';
			$link = " AND ";
		}
		
		// Filter by NCLOTN
		if ($this->programState['filters']['NCLOTN'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCLOTN) LIKE :NCLOTN';
			$link = " AND ";
		}
		
		// Filter by NCLOT2
		if ($this->programState['filters']['NCLOT2'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.NONCON0F.NCLOT2) LIKE :NCLOT2';
			$link = " AND ";
		}
		
		return $whereClause;
	}
	
	// Build a single entry statement
	protected function buildEntryStmt($keyFieldArray, $oldKeyFields = false)
	{
		// Build the query with parameters
		$selString = $this->buildRecordSelectString();
		$selString .= ' ' . $this->buildRecordWhere();
		
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the key parameters
		if(!$oldKeyFields) {
			$stmt->bindValue(':NCPROG_', (int) $keyFieldArray['NCPROG'], PDO::PARAM_INT);
			
		} else {
			$stmt->bindValue(':NCPROG_', (int) $keyFieldArray['NCPROG_'], PDO::PARAM_INT);
			
		}
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildRecordSelectString()
	{
		 
		$selString = 'SELECT BCD_DATIV2.NONCON0F.NCPROG, BCD_DATIV2.NONCON0F.NCORFO, BCD_DATIV2.NONCON0F.NCTRFO, 
		BCD_DATIV2.NONCON0F.NCUORG, BCD_DATIV2.NONCON0F.NCRGFO, BCD_DATIV2.NONCON0F.NCDSC1, BCD_DATIV2.NONCON0F.NCDTEM, 
		BCD_DATIV2.NONCON0F.NCTPCF, BCD_DATIV2.NONCON0F.NCAB8, BCD_DATIV2.NONCON0F.NCRGCF, BCD_DATIV2.NONCON0F.NCDCTO, 
		BCD_DATIV2.NONCON0F.NCDOCO, BCD_DATIV2.NONCON0F.NCLNID, BCD_DATIV2.NONCON0F.NCLITM, BCD_DATIV2.NONCON0F.NCLOTN, 
		BCD_DATIV2.NONCON0F.NCLOT2, BCD_DATIV2.NONCON0F.NCPZCO, BCD_DATIV2.NONCON0F.NCPPCO, BCD_DATIV2.NONCON0F.NCFORN, 
		BCD_DATIV2.NONCON0F.NCRICL, BCD_DATIV2.NONCON0F.NCDTRC, BCD_DATIV2.NONCON0F.NCDIFE, BCD_DATIV2.NONCON0F.NCRISO, 
		BCD_DATIV2.NONCON0F.NCSOEF, BCD_DATIV2.NONCON0F.NCACEF, BCD_DATIV2.NONCON0F.NCCHIU, BCD_DATIV2.NONCON0F.NCDTCH, 
		BCD_DATIV2.NONCON0F.NCNOTE, BCD_DATIV2.NONCON0F.NCRIFE, 
		BCD_DATIV2.NONCON0F.NCRSQT, BCD_DATIV2.NONCON0F.NCRSDT,
		BCD_DATIV2.NONCON0F.NCSEQT, BCD_DATIV2.NONCON0F.NCSEDT,
		BCD_DATIV2.NONCON0F.NCAEQT, BCD_DATIV2.NONCON0F.NCAEDT,
		BCD_DATIV2.NONCON0F.NCRANC, BCD_DATIV2.NONCON0F.NCCATE,
		BCD_DATIV2.NONCON0F.NCQTSP, BCD_DATIV2.NONCON0F.NCQTSE,
		BCD_DATIV2.NONCON0F.NCQTRI, BCD_DATIV2.NONCON0F.NCDTSP, 
		BCD_DATIV2.NONCON0F.NCQTOP  
		FROM BCD_DATIV2.NONCON0F';
		
		return $selString;
	}
	
	// Build where clause to filter single entries
	protected function buildRecordWhere()
	{
		$whereClause = 'WHERE BCD_DATIV2.NONCON0F.NCPROG = :NCPROG_';
		
		return $whereClause;
	}
	
	// Return the SELECT SQL for a count on the primary file
	protected function getPrimaryFileCountSelect()
	{
		$selString = 'SELECT COUNT(*) FROM BCD_DATIV2.NONCON0F';
		
		return $selString;
	}
	
	// Build a file entry count statement
	protected function buildCountStmt($keyFieldArray)
	{
		// Build the query with parameters
		$selString = $this->getPrimaryFileCountSelect();
		$selString .= ' ' . $this->buildRecordWhere();
		
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the key parameters
		$stmt->bindValue(':NCPROG_', (int) $keyFieldArray['NCPROG'], PDO::PARAM_INT);
		
		return $stmt;
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
	
	//
	protected function flstOrdLeg() {
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");
		$this->lstOrdLeg($tipoIns,$TMPROG);
	}
	
	protected function lstOrdLeg($tipoIns,$TMPROG) {
		if($tipoIns=="T") {
			$selString = "SELECT * 
			FROM BCD_DATIV2.NONCOLTF  
			WHERE OLPRNC = ".$TMPROG."
			";
		} else {
			$selString = "SELECT * 
			FROM BCD_DATIV2.NONCOL0F 
			WHERE OLPRNC = ".$TMPROG."
			";	
		}

		$x = 0;

		
 		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if($x==0) $this->writeSegment("hOrdLeg", array_merge(get_object_vars($this), get_defined_vars()));	
			
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$OLQTSP = $OLQTSP / 100;
			
			$this->writeSegment("dOrdLeg", array_merge(get_object_vars($this), get_defined_vars()));
			
			$x++;	
		} 
		
		if($x>0) $this->writeSegment("fOrdLeg", array_merge(get_object_vars($this), get_defined_vars()));	
		else { 	
			$this->dspNoDetailsMsg("Nessun ordine collegato"); 
		}
		
	}

	protected function dltOrdLeg() {
		$tipoIns = xl_get_parameter("tipoIns");
		$OLPROG = xl_get_parameter("OLPROG");
		
		if($tipoIns=="T") {
			$selString = "DELETE FROM BCD_DATIV2.NONCOLTF WHERE OLPROG = :OLPROG WITH NC";
		} else {
			$selString = "DELETE FROM BCD_DATIV2.NONCOL0F WHERE OLPROG = :OLPROG WITH NC";
		}
		
		$stmt = $this->db_connection->prepare($selString);
		$stmt->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
		$result = $stmt->execute();
		if ($result === false) 
		{
			echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore cancellazione"}]';
			exit;
		}		
		 
		echo '[{"stat":"OK"}]';
	}
	/* 
	protected function addOrdLeg() {
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");
		$tipoDato = xl_get_parameter("tipoDato");
		$RRNF = xl_get_parameter("RRNF");
		
		$this->writeSegment("formOrdLeg", array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	protected function addOrdLeg1() {
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = xl_get_parameter("TMPROG");
		$tipoDato = xl_get_parameter("tipoDato");
		$RRNF = xl_get_parameter("RRNF");
		
		$OLDCTO = xl_get_parameter("OLDCTO");
		$OLDOCO = xl_get_parameter("OLDOCO");
		if(!is_numeric($OLDOCO)) $OLDOCO = 0;
		$OLDCTO = strtoupper($OLDCTO); 
		 
		//controlli..
		if($tipoDato=="CLI") {
			$selString = "SELECT T.EXS    
			FROM TABLE ( 
				SELECT 1 AS EXS
				FROM JRGDTA94C.F4211 inner join JRGDTA94C.F0101 on JRGDTA94C.F4211.SDAN8 = JRGDTA94C.F0101.ABAN8 
				WHERE SDDCTO = :OLDCTO1 AND SDDOCO = :OLDOCO1 
				
				UNION 
				
				SELECT 1 AS EXS 
				FROM JRGDTA94C.F42119 inner join JRGDTA94C.F0101 on JRGDTA94C.F42119.SDAN8 = JRGDTA94C.F0101.ABAN8 
				WHERE SDDCTO = :OLDCTO2 AND SDDOCO = :OLDOCO2 
			) AS T 
			FETCH FIRST ROW ONLY
			";

			$stmt_chk = $this->db_connection->prepare($selString);
			$stmt_chk->bindValue(':OLDCTO1', $OLDCTO, PDO::PARAM_STR);
			$stmt_chk->bindValue(':OLDOCO1', $OLDOCO, PDO::PARAM_STR); 
			$stmt_chk->bindValue(':OLDCTO2', $OLDCTO, PDO::PARAM_STR);
			$stmt_chk->bindValue(':OLDOCO2', $OLDOCO, PDO::PARAM_STR);			
			
		}
		if($tipoDato=="FOR") {
			$selString = "SELECT 1 as EXS 
			FROM JRGDTA94C.F4311 
			inner join JRGDTA94C.F43121 on PDDOCO=PRDOCO AND PDLNID=PRLNID AND PDKCOO=PRKCOO AND PDDCTO=PRDCTO 
			inner join JRGDTA94C.F0101 on JRGDTA94C.F4311.PDAN8 = JRGDTA94C.F0101.ABAN8 
			WHERE PDDCTO = :OLDCTO AND PDDOCO = :OLDOCO 
			FETCH FIRST ROW ONLY
			";
			$stmt_chk = $this->db_connection->prepare($selString);
			$stmt_chk->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);
			$stmt_chk->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR); 
		}

		$result_chk = $stmt_chk->execute();
		$row = $stmt_chk->fetch(PDO::FETCH_ASSOC);
		if(!$row || $row["EXS"]!=1) {
			echo '[{"stat":"ERR","id":"OLDOCO","msg":"Ordine non trovato"}]';
			exit;
		}
		
		//!! passare parametro se si tratta di cliente o fornitore
		//..
		//.. 
		 
		if($tipoIns=="T") {
 
		 	//progressivo:
		 	$OLPROG = $this->getProgre("NONCOLTF");
		 	 	 
			$insertSql = "INSERT INTO BCD_DATIV2.NONCOLTF (OLPRNC,OLPROG,OLDCTO,OLDOCO) VALUES(:OLPRNC,:OLPROG,:OLDCTO,:OLDOCO) WITH NC";
			$stmt = $this->db_connection->prepare($insertSql);
			if (!$stmt)
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}			
			 
			// Bind the parameters
			$stmt->bindValue(':OLPRNC', $TMPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);
			$stmt->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR);  			
			
			$result = $stmt->execute();
			if ($result === false) 
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}
			
		} else {
			
		 	//progressivo:
		 	$OLPROG = $this->getProgre("NONCOL0F");
		 	 	 
			$insertSql = "INSERT INTO BCD_DATIV2.NONCOL0F (OLPRNC,OLPROG,OLDCTO,OLDOCO) VALUES(:OLPRNC,:OLPROG,:OLDCTO,:OLDOCO) WITH NC";
			$stmt = $this->db_connection->prepare($insertSql);
			if (!$stmt)
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}			
			 
			// Bind the parameters 
			$stmt->bindValue(':OLPRNC', $TMPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLPROG', $OLPROG, PDO::PARAM_INT);
			$stmt->bindValue(':OLDCTO', $OLDCTO, PDO::PARAM_STR);
			$stmt->bindValue(':OLDOCO', $OLDOCO, PDO::PARAM_STR);		
			
			$result = $stmt->execute();
			if ($result === false) 
			{
				echo '[{"stat":"ERR","id":"OLDOCO","msg":"Errore inserimento"}]';
				exit;
			}
		}
		
		echo '[{"stat":"OK"}]';
		
	}	
	*/
	
	protected function fcalcNCQTSP() { 
		$tipoIns = xl_get_parameter("tipoIns");
		$TMPROG = (int) xl_get_parameter("TMPROG");
		$NCQTOP = xl_get_parameter("NCQTOP"); 
		$NCQTSP = $this->calcNCQTSP($tipoIns,$TMPROG,$NCQTOP);
		
		echo '{"NCQTSP":"'.$NCQTSP.'"}';
	}
	
	protected function calcNCQTSP($tipoIns,$TMPROG,$NCQTOP) {
		 
 		$NCQTSP = $NCQTOP;
		if($tipoIns=="T") $query = "SELECT SUM(OLQTSP) AS OLQTSP FROM NONCOLTF WHERE OLPRNC = '".$TMPROG."'";
		else $query = "SELECT SUM(OLQTSP) AS OLQTSP FROM NONCOL0F WHERE OLPRNC = '".$TMPROG."'";
		$stmt = $this->db_connection->prepare($query);
		$result = $stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if($row && isset($row["OLQTSP"])) {
			$OLQTSP = $row["OLQTSP"] / 100; 
			$NCQTSP = $NCQTSP + $OLQTSP;
		}
		
		return $NCQTSP;
		 
		
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
    <title>Non conformità - Elenco non conformità</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="display-list">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Elenco non conformità</h1>
          <span class="add-link">
            <a class="btn btn-info btn-sm" href="nonconf01.php">
              <span class="glyphicon glyphicon-list"></span> Vai a Non conformità - Elenco ordini
            </a>
          </span> 
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <!-- Form containing filter inputs -->
          <form id="filter-form" class="container-fluid" method="post" action="$pf_scriptname">
            <input type="hidden" name="task" value="filter" />
            <div class="form">
              <div class="row">
                 
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NCTPCF">Tipo</label>
                  <!--<input id="filter_NCTPCF" class="form-control" type="text" name="filter_NCTPCF" maxlength="3" value="{$programState['filters']['NCTPCF']}"/>-->
                  <select name="filter_NCTPCF" id="filter_NCTPCF" class="form-control">
                  	<option value=""></option>
                  	<option value="C">Cliente</option>
                  	<option value="F">Fornitore</option>
                  </select>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NCAB8">Cod. Cli/For</label>
                  <input id="filter_NCAB8" class="form-control" type="text" name="filter_NCAB8" maxlength="8" value="{$programState['filters']['NCAB8']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NCRGCF">Rag. Soc.</label>
                  <input id="filter_NCRGCF" class="form-control" type="text" name="filter_NCRGCF" maxlength="40" value="{$programState['filters']['NCRGCF']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NCDCTO">Tipo ordine</label>
                  <input id="filter_NCDCTO" class="form-control" type="text" name="filter_NCDCTO" maxlength="2" value="{$programState['filters']['NCDCTO']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NCDOCO">Num. ordine</label>
                  <input id="filter_NCDOCO" class="form-control" type="text" name="filter_NCDOCO" maxlength="8" value="{$programState['filters']['NCDOCO']}"/>
                </div></div><div class="row">
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NCLNID">Num. linea</label>
                  <input id="filter_NCLNID" class="form-control" type="text" name="filter_NCLNID" maxlength="6" value="{$programState['filters']['NCLNID']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NCLITM">Des. articolo</label>
                  <input id="filter_NCLITM" class="form-control" type="text" name="filter_NCLITM" maxlength="25" value="{$programState['filters']['NCLITM']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NCLOTN">Lotto</label>
                  <input id="filter_NCLOTN" class="form-control" type="text" name="filter_NCLOTN" maxlength="30" value="{$programState['filters']['NCLOTN']}"/>
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
                <th class="actions" width="100">Action</th> 
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCPROG&amp;rnd=$rnd">Progr.</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCDTEM&amp;rnd=$rnd">Data emissione</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCTPCF&amp;rnd=$rnd">Tipo</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCAB8&amp;rnd=$rnd">Cod. Cli/For</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCRGCF&amp;rnd=$rnd">Rag. Soc.</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCDCTO&amp;rnd=$rnd">Tipo ordine</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCDOCO&amp;rnd=$rnd">Num. ordine</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCLNID&amp;rnd=$rnd">Num. linea</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCLITM&amp;rnd=$rnd">Des. articolo</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCLOTN&amp;rnd=$rnd">Lotto</a>
                </th>  
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCFORN&amp;rnd=$rnd">Fornitore</a>
                </th> 
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCDIFE&amp;rnd=$rnd">Difetto</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCRICL&amp;rnd=$rnd">Risp. Cliente</a>
                </th> 
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCRISO&amp;rnd=$rnd">Rich. Sost.</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCSOEF&amp;rnd=$rnd">Sost. Eff.</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCACEF&amp;rnd=$rnd">Accr. Eff.</a>
                </th>
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCCHIU&amp;rnd=$rnd">Chiusa</a>
                </th>  
                <th >
                  <a class="list-header" href="$pf_scriptname?sidx=NCRIFE&amp;rnd=$rnd">Rif.</a>
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
  
  <td class="actions">
    <span>
      <a class="btn btn-default btn-xs glyphicon glyphicon-pencil" title="Change this record" href="$pf_scriptname?task=beginchange
SEGDTA;
 echo (($NCTPCF=="C")?('cli'):('for')); 
		echo <<<SEGDTA
&amp;fromPgm=2&amp;NCPROG=$NCPROG_url&amp;rnd=$rnd"></a> 
      <a class="btn btn-default btn-xs glyphicon glyphicon-remove" title="Delete this record" href="javascript:void('0');" onclick="dltRecord('$NCPROG');"></a>
    </span>
  </td> 
  <td class="text num">$NCPROG</td>
  <td class="text num">
SEGDTA;
 echo $this->cvtDateFromDb($NCDTEM); 
		echo <<<SEGDTA
</td>
  <td class="text">
  	
SEGDTA;
 echo (($NCTPCF=="C")?("Cliente"):("Fornitore")); 
		echo <<<SEGDTA

  </td>
  <td class="text num">$NCAB8</td>
  <td class="text">$NCRGCF</td>
  <td class="text">$NCDCTO</td>
  <td class="text num">$NCDOCO</td>
  <td class="text num">$NCLNID</td>
  <td class="text">$NCLITM</td>
  <td class="text">$NCLOTN</td> 
  <td class="text num">$NCFORN</td>
  <td class="text">
SEGDTA;
 echo $this->decodDifetto($NCDIFE); 
		echo <<<SEGDTA
</td>
  
  <td class="text"><span class="
SEGDTA;
 echo (($NCRICL=="S")?('glyphicon glyphicon-check'):('')); 
		echo <<<SEGDTA
">&nbsp;</span></td> 
  <td class="text"><span class="
SEGDTA;
 echo (($NCRISO=="S")?('glyphicon glyphicon-check'):('')); 
		echo <<<SEGDTA
">&nbsp;</span></td>
  <td class="text"><span class="
SEGDTA;
 echo (($NCSOEF=="S")?('glyphicon glyphicon-check'):('')); 
		echo <<<SEGDTA
">&nbsp;</span></td>
  <td class="text"><span class="
SEGDTA;
 echo (($NCACEF=="S")?('glyphicon glyphicon-check'):('')); 
		echo <<<SEGDTA
">&nbsp;</span></td>
  <td class="text"><span class="
SEGDTA;
 echo (($NCCHIU=="S")?('glyphicon glyphicon-check'):('')); 
		echo <<<SEGDTA
">&nbsp;</span></td>  
  
  <td class="text num">$NCRIFE</td>
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
</div>

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
	
	function dltRecord(jNCPROG) {
		if(!confirm("Procedere con la cancellazione?")) return false;
		
		document.location.href = "?task=del&NCPROG="+jNCPROG;	
	}
	
</script>
</body>
</html>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "rcddisplay")
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
    <title>Non conformità - Elenco non conformità - $mode</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record display-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="websmart/v13.2/Responsive/images/company-logo.png" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Non conformità - Elenco non conformità - $mode</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <div id="display-fields">
            <div class="form-group row">
              <label class="col-sm-4">Progressivo:</label>
              <div class="col-sm-8">$NCPROG</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Data emissione:</label>
              <div class="col-sm-8">$NCDTEM</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Tipo:</label>
              <div class="col-sm-8">$NCTPCF</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Cod. Cli/For:</label>
              <div class="col-sm-8">$NCAB8</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Rag. Soc.:</label>
              <div class="col-sm-8">$NCRGCF</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Tipo ordine:</label>
              <div class="col-sm-8">$NCDCTO</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Num. ordine:</label>
              <div class="col-sm-8">$NCDOCO</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Num. linea:</label>
              <div class="col-sm-8">$NCLNID</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Des. articolo:</label>
              <div class="col-sm-8">$NCLITM</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Lotto:</label>
              <div class="col-sm-8">$NCLOTN</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">IOLOT2:</label>
              <div class="col-sm-8">$NCLOT2</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Pezzi contestati:</label>
              <div class="col-sm-8">$NCPZCO</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">% Pezzi contestati:</label>
              <div class="col-sm-8">$NCPPCO</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Fornitore:</label>
              <div class="col-sm-8">$NCFORN</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">RISPOSTA CLIENTE S/N:</label>
              <div class="col-sm-8">$NCRICL</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Data risposta cliente:</label>
              <div class="col-sm-8">$NCDTRC</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Difetto:</label>
              <div class="col-sm-8">$NCDIFE</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">RICHIESTA SOST. S/N:</label>
              <div class="col-sm-8">$NCRISO</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">SOSTITUZ. EFF. S/N:</label>
              <div class="col-sm-8">$NCSOEF</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">ACCREDUTI EFF. S/N:</label>
              <div class="col-sm-8">$NCACEF</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">CHIUSA S/N:</label>
              <div class="col-sm-8">$NCCHIU</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Data chiusura:</label>
              <div class="col-sm-8">$NCDTCH</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">NOTE:</label>
              <div class="col-sm-8">$NCNOTE</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">RIF. NC:</label>
              <div class="col-sm-8">$NCRIFE</div>
            </div>
          </div>
          
          <div id="nav">
            
SEGDTA;

		    	if ($this->pf_task == "disp")
		    	{
					$this->writeSegment('RtnToList', $segmentVars);
				}
				else if ($this->pf_task == 'delconf')
				{
					$this->writeSegment('DelChoice', $segmentVars);
				}
          	
		echo <<<SEGDTA

          </div>
        </div>
      </div>	
    </div>
    <script type="text/javascript">
		jQuery(function() {
			// Bind event to the Back button
			jQuery(".cancel").click(goback);
			function goback()
			{
				window.location.replace("$pf_scriptname?page={$programState['page']}");
				return false;
			}
		});
	</script>
  </body>
</html>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "delchoice")
	{

		echo <<<SEGDTA
<form action="" method="post">
  <input type="hidden" name="task" value="del" />
  <input id="NCPROG" type="hidden" name="NCPROG" value="$NCPROG" />
  
  <p>Sei sicuro di voler cancellare questo record?</p>
  <input type="submit" class="btn btn-primary accept" value="Sì">
  <input type="button" class="btn btn-default cancel" value="No">
</form>
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
	if($xlSegmentToWrite == "rcdadd")
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
    <title>Inserimento non conformità cliente</title>
    
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/crud/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/js/jquery-ui.js"></script> 
    <script src="/crud/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
    
    <style>
    #navtop { margin-bottom:20px; }
    #srcOrdFor-btn { top:0px; }
    #srcOrdFor-btn:hover { cursor: pointer; }           
    </style>
    
  </head>
  <body class="single-record manage-record">
    <div id="div-add-alleg"></div>
  	<div id="div-src-ordfor"></div>
  	<div id="div-add-ordleg"></div> 
  	<div id="div-src-ordcolcli"></div>
  	<div id="div-crt-nonconffor" style="display:none">
  		<h3>Creare non conformità fornitore?</h3>
  		<input type="button" class="btn btn-default" value="S&igrave;" onclick="crtNonConfFor();" />
  		<a class="btn btn-default" onclick="$.blockUI();" href="nonconf01.php">No</a>
  	</div>
  
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Inserimento non conformità cliente</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="rcd-add-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endaddcli" />
            <input type="hidden" name="NCUORG" value="$NCUORG" />
            <input type="hidden" name="TMPROG" value="$TMPROG" />  
            <input type="hidden" name="NCRIFE_cli" id="NCRIFE_cli" value="" />  
            <input type="hidden" name="NCQTOP" id="NCQTOP" value="$NCQTOP" />  
               
			  <div id="navtop">
			    <input type="button" onclick="sbm_add();" class="btn btn-primary accept" value="Salva" />
			    <input type="button" class="btn btn-default cancel" onclick="$.blockUI();" value="Torna alla lista" />
			  </div>	
               
              <div class="row"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDTEM_lbl">
	                <label for="addNCDTEM">Data emissione</label>
	                <div>
	                  <input type="text" id="addNCDTEM" class="form-control" name="NCDTEM" size="10" maxlength="10" value="$NCDTEM" readonly>
	                  <span class="help-block" id="addNCDTEM_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRANC_lbl">
	                <label for="addNCRANC">Rapporto NC</label>
	                <div>
	                  <input type="text" id="addNCRANC" class="form-control" name="NCRANC" size="100" maxlength="100" value="$NCRANC">
	                  <span class="help-block" id="addNCRANC_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCCATE_lbl">
	                <label for="addNCCATE">Categoria</label>
	                <div>
	                  <select class="form-control" name="NCCATE" id="addNCCATE">
	                  	<option value=""></option>
	                  	<option value="NC" 
SEGDTA;
 echo (($NCCATE=="NC")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>NC</option>
	                  	<option value="SEGNALAZIONE" 
SEGDTA;
 echo (($NCCATE=="SEGNALAZIONE")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Segnalazione</option>
	                  </select> 
	                  <span class="help-block" id="addNCCATE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCAB8_lbl">
	                <label for="addNCAB8">Cod. Cliente</label>
	                <div>
	                  <input type="text" id="addNCAB8" class="form-control" name="NCAB8" size="10" maxlength="10" value="$NCAB8" readonly>
	                  <span class="help-block" id="addNCAB8_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRGCF_lbl">
	                <label for="addNCRGCF">Ragione Sociale</label>
	                <div>
	                  <input type="text" id="addNCRGCF" class="form-control" name="NCRGCF" size="40" maxlength="40" value="$NCRGCF" readonly>
	                  <span class="help-block" id="addNCRGCF_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">   
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDCTO_lbl">
	                <label for="addNCDCTO">Tipo ordine</label>
	                <div>
	                  <input type="text" id="addNCDCTO" class="form-control" name="NCDCTO" size="2" maxlength="2" value="$NCDCTO" readonly>
	                  <span class="help-block" id="addNCDCTO_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDOCO_lbl">
	                <label for="addNCDOCO">Num. ordine</label>
	                <div>
	                  <input type="text" id="addNCDOCO" class="form-control" name="NCDOCO" size="10" maxlength="10" value="$NCDOCO" readonly>
	                  <span class="help-block" id="addNCDOCO_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLNID_lbl">
	                <label for="addNCLNID">Num. linea</label>
	                <div>
	                  <input type="text" id="addNCLNID" class="form-control" name="NCLNID" size="6" maxlength="6" value="$NCLNID" readonly>
	                  <span class="help-block" id="addNCLNID_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLITM_lbl">
	                <label for="addNCLITM">Cod. articolo</label>
	                <div>
	                  <input type="text" id="addNCLITM" class="form-control" name="NCLITM" size="25" maxlength="25" value="$NCLITM" readonly>
	                  <span class="help-block" id="addNCLITM_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDSC1_lbl">
	                <label for="addNCDSC1">Des. articolo </label>
	                <div>
	                  <input type="text" id="addNCDSC1" class="form-control" name="NCDSC1" size="25" maxlength="25" value="$NCDSC1" readonly>
	                  <span class="help-block" id="addNCDSC1_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLOTN_lbl">
	                <label for="addNCLOTN">Lotto</label>
	                <div>
	                  <input type="text" id="addNCLOTN" class="form-control" name="NCLOTN" size="30" maxlength="30" value="$NCLOTN" readonly>
	                  <span class="help-block" id="addNCLOTN_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLOT2_lbl">
	                <label for="addNCLOT2">Selezione</label>
	                <div>
	                  <input type="text" id="addNCLOT2" class="form-control" name="NCLOT2" size="30" maxlength="30" value="$NCLOT2" readonly>
	                  <span class="help-block" id="addNCLOT2_err"></span>
	                </div>
	              </div>
	          </div>
	          
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCQTSP_lbl">
	                <label for="addNCQTSP">Quantit&agrave; spedita</label>
	                <div>
	                  <input type="text" id="addNCQTSP" class="form-control fld-calc-percentuale" name="NCQTSP" size="30" maxlength="30" value="$NCQTSP" readonly>
	                  <span class="help-block" id="addNCQTSP_err"></span>
	                </div>
	              </div> 
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCPZCO_lbl">
	                <label for="addNCPZCO">Pezzi contestati</label>
	                <div>
	                  <input type="text" id="addNCPZCO" class="positive-integer form-control fld-calc-percentuale" name="NCPZCO" size="15" maxlength="15" value="$NCPZCO">
	                  <span class="help-block" id="addNCPZCO_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCPPCO_lbl">
	                <label for="addNCPPCO">% Pezzi contestati</label>
	                <div>
	                  <input type="text" id="addNCPPCO" class="form-control" name="NCPPCO" size="6" maxlength="7" value="$NCPPCO" readonly>
	                  <span class="help-block" id="addNCPPCO_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCFORN_lbl">
	                <label for="addNCFORN">Fornitore</label>
	                <div>
	                  <input type="text" id="addNCFORN" class="form-control" name="NCFORN" size="8" maxlength="8" value="$NCFORN" readonly>
	                  <span class="help-block" id="addNCFORN_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRGFO_lbl">
	                <label for="addNCRGFO">&nbsp;</label>
	                <div>
	                  <input type="text" id="addNCRGFO" class="form-control" name="NCRGFO" value="$NCRGFO" readonly> 
	                </div>
	              </div>
              </div>
              
              
              <input type="hidden" name="RRNorf" id="addRRNorf" value=""> 
              <input type="hidden" name="NCTRFO" id="addNCTRFO" value="$NCTRFO"> 
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCORFO_lbl">
	                <label for="addNCORFO">Ordine fornitore</label>
	                <div class="input-group">
	                  <input type="text" id="addNCORFO" class="form-control" name="NCORFO" size="8" maxlength="8" value="$NCORFO" readonly>
	                  <span title="Cerca ordini fornitore" id="srcOrdFor-btn" class="input-group-addon glyphicon glyphicon-pencil" onclick="srcOrdFor();"></span>
	                </div>
	                <span class="help-block" id="addNCORFO_err"></span>
	              </div> 
              </div>
              	          
	          <div class="row">    
	              <div class="form-group col-xs-12 col-sm-12 col-lg-6" id="addNCDIFE_lbl">
	                <label for="addNCDIFE">Difetto</label>
	                <div>
	                  <!--<input type="text" id="addNCDIFE" class="form-control" name="NCDIFE" size="35" maxlength="35" value="$NCDIFE">-->
	                  
SEGDTA;
 $this->lstDifetti($NCDIFE); 
		echo <<<SEGDTA

	                  <span class="help-block" id="addNCDIFE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <!--
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3">
	                <label for="addNCRICL">&nbsp;</label>
					<div class="checkbox">
						<label>
						  <input type="checkbox" id="addNCRICL" name="NCRICL" class="checkbox style-0" value="S">
						  <span>Risposta cliente</span>
						</label>
					</div> 
	              </div>
	              -->
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDTRC_lbl">
	                <label for="addNCDTRC">Data risposta cliente</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCDTRC').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTRC" class="calendario form-control" name="NCDTRC" size="10" maxlength="10" value="$NCDTRC">
	                  	<span class="help-block" id="addNCDTRC_err"></span>
	                </div>
	              </div>
	          </div>    
 
              <div class="row">   

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCRSQT_lbl">
	                <label for="addNCRSQT">Q.t&agrave; Reso</label>
	                <div>
	                  <input type="text" id="addNCRSQT" class="form-control positive-integer" name="NCRSQT" size="9" maxlength="9" value="$NCRSQT">
	                  <span class="help-block" id="addNCRSQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCRSDT_lbl">
	                <label for="addNCRSDT">Data Reso</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCRSDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCRSDT" class="calendario form-control" name="NCRSDT" size="10" maxlength="10" value="$NCRSDT">
	                  	<span class="help-block" id="addNCRSDT_err"></span>
	                </div>
	              </div>
	          </div>
	          
              <div class="row">    

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCSEQT_lbl">
	                <label for="addNCSEQT">Q.t&agrave; sostituzione effettuata</label>
	                <div>
	                  <input type="text" id="addNCSEQT" class="form-control positive-integer" name="NCSEQT" size="9" maxlength="9" value="$NCSEQT">
	                  <span class="help-block" id="addNCSEQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCSEDT_lbl">
	                <label for="addNCSEDT">Data sostituzione effettuata</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCSEDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCSEDT" class="calendario form-control" name="NCSEDT" size="10" maxlength="10" value="$NCSEDT">
	                  	<span class="help-block" id="addNCSEDT_err"></span>
	                </div>
	              </div>
	              
	          </div>
	          
	          <div class="row">      
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCQTSE_lbl">
	                <label for="addNCQTSE">Q.t&agrave; selezionata/sistemata</label>
	                <div>
	                  <input type="text" id="addNCQTSE" class="form-control positive-integer" name="NCQTSE" size="9" maxlength="9" value="$NCQTSE">
	                  <span class="help-block" id="addNCQTSE_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCQTRI_lbl">
	                <label for="addNCQTRI">Q.t&agrave; rispedita</label>
	                <div>
	                  <input type="text" id="addNCQTRI" class="form-control positive-integer" name="NCQTRI" size="10" maxlength="10" value="$NCQTRI">
	                  <span class="help-block" id="addNCQTRI_err"></span>
	                </div>
	              </div>        
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCDTSP_lbl">
	                <label for="addNCDTSP">Data spedizione</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCDTSP').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTSP" class="form-control calendario" name="NCDTSP" size="10" maxlength="10" value="$NCDTSP">
	                  	<span class="help-block" id="addNCDTSP_err"></span>
	                </div>
	              </div>    
	          </div>
	          
	          <div class="row">       

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCAEQT_lbl">
	                <label for="addNCAEQT">Q.t&agrave; accredito effettuato</label>
	                <div>
	                  <input type="text" id="addNCAEQT" class="form-control positive-integer" name="NCAEQT" size="9" maxlength="9" value="$NCAEQT">
	                  <span class="help-block" id="addNCAEQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCAEDT_lbl">
	                <label for="addNCAEDT">Data accredito effettuato</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCAEDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCAEDT" class="calendario form-control" name="NCAEDT" size="10" maxlength="10" value="$NCAEDT">
	                  	<span class="help-block" id="addNCAEDT_err"></span>
	                </div>
	              </div> 
              </div>
              
              <div class="row">

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCDTCH_lbl">
	                <label for="addNCDTCH">Data chiusura</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCDTCH').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTCH" class="calendario form-control" name="NCDTCH" size="10" maxlength="10" value="$NCDTCH">
	                  	<span class="help-block" id="addNCDTCH_err"></span>
	                </div>
	              </div>
	          </div>     
	           
	          <div class="row">    
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCNOTE_lbl">
	                <label for="addNCNOTE">Note</label>
	                <div>
	                  <textarea id="addNCNOTE" class="form-control" name="NCNOTE" maxlength="2048" rows="7">$NCNOTE</textarea>
	                  <span class="help-block" id="addNCNOTE_err"></span>
	                </div>
	              </div>
	          </div>  
	          
	          <div class="row">     
	           <fieldset>
	            <div class="form-group col-xs-12 col-sm-12 col-lg-6">
	           	<legend>Altri ordini collegati <span class="pull-right"><input type="button" class="btn btn-xs btn-primary" value="Aggiungi ordine" onclick="srcOrdColCli();"> </span></legend>
		           	<div id="div-lst-ordleg">
		           	
SEGDTA;

						$this->dspNoDetailsMsg("Nessun ordine collegato");
		           	
		echo <<<SEGDTA

		           	</div>
	           	</div>
	           </fieldset>
	          </div>  
	          
	          <div class="row">     
	           <fieldset>
	            <div class="form-group col-xs-12 col-sm-12 col-lg-6">
	           	<legend>Allegati <span class="pull-right"><input type="button" class="btn btn-xs btn-primary" value="Aggiungi allegato" onclick="addAlleg();"> </span></legend>
		           	<div id="div-lst-alleg">
		           	
SEGDTA;

						$this->dspNoDetailsMsg("Nessun allegato");
		           	
		echo <<<SEGDTA

		           	</div>
	           	</div>
	           </fieldset>
	          </div>    
	            
            <div id="navbottom">
              <input type="button" onclick="sbm_add();" class="btn btn-primary accept" value="Salva" />
              <input type="button" class="btn btn-default cancel" onclick="$.blockUI();" value="Torna alla lista" />
            </div>		
          </form>
        </div>
      </div>
    </div>
    
    <script src="/crud/websmart/v13.2/js/jquery.maskedinput.min.js"></script>  
    <script src="/crud/websmart/v13.2/js/jquery.numeric.js"></script>   
    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
    <script type="text/javascript">
		jQuery(function() {
			// Focus the first input on page load
			jQuery("input:enabled:first").focus();
			
			// Bind event to the Back button
			jQuery(".cancel").click(goback);
			function goback()
			{
				window.location.replace("nonconf01.php?page=1");
				return false;
			}
			
			$('.calendario').datepicker({
				dateFormat: 'dd-mm-yy',
				buttonImageOnly: false,
				changeMonth: true,   
				changeYear: true 
			}); 			
			$(".calendario").mask("99-99-9999",{placeholder:" "});
			
            $('.positive-integer').numeric({
	                decimal: false, 
	                negative: false
            });		
            	
            $(".fld-calc-percentuale").change(function(e){
            	jaddNCPZCO = $("#addNCPZCO").val();
            	jaddNCQTSP = $("#addNCQTSP").val();
            	 
            	if(jaddNCQTSP!='' && jaddNCQTSP != 0) {
            		jaddNCPPCO = (jaddNCPZCO /  jaddNCQTSP) * 100;	
            		$("#addNCPPCO").val(jaddNCPPCO.toFixed(2));
            	}
            });
              
			$("#div-add-alleg").dialog({
				autoOpen: false,
				modal: true,
				width: 700, 
				resizable: false,
				title: 'Allegato'
			});	
              
			$("#div-src-ordfor").dialog({
				autoOpen: false,
				modal: true,
				width: 900, 
				resizable: false,
				title: 'Ordini fornitore'
			});	
			
			$("#div-src-ordcolcli").dialog({
				autoOpen: false,
				modal: true,
				width: 900, 
				resizable: false,
				title: 'Ordini'
			});	
			 
			$("#div-add-ordleg").dialog({
				autoOpen: false,
				modal: true,
				width: 700, 
				resizable: false,
				title: 'Ordini legati'
			});	
			
			$("#div-crt-nonconffor").dialog({
				autoOpen: false,
				modal: true,
				width: 900, 
				resizable: false,
				title: 'Creazione non conformita fornitore'
			});				
			
			var options = { 
				dataType:  'json',
				success: showResponseAdd 
			}; 
			$('#rcd-add-form').ajaxForm(options); 
              
		});
		
		function sbm_add() {
			$.blockUI();
			$("#rcd-add-form").submit();
		}
		
		function sbm_alleg() {
			$.blockUI();
			$("#alleg-add-form").submit();
		}		
		
		function showResponseAdd(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");
			 
			if(respobj[0].stat!="OK") {
				$.unblockUI();
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {		
				 jRRNF = $("#addRRNorf").val();
				 /*if(jRRNF!="") {*/
				 	$.unblockUI();	
				 	$("#NCRIFE_cli").val(respobj[0].NCPROG);
				 	$("#div-crt-nonconffor").dialog('open');
				 /*}
				 else document.location.href="nonconf01.php?page=1";*/
			}  
		} 
		
		function crtNonConfFor() {
			jRRNF = $("#addRRNorf").val();
			jNCRIFE = $("#NCRIFE_cli").val();
			if(jRRNF!="") url = "nonconf02.php?task=beginaddfor&RRNF="+jRRNF+"&NCRIFE="+jNCRIFE;
			else url = "nonconf02.php?task=beginaddfor&RRNC=$RRNF&TIPOFILE=$TIPOFILE&NCRIFE="+jNCRIFE;
			document.location.href = url;
		}
		
		function addAlleg() {
			url = "?task=addAlleg&tipoIns=T&TMPROG=$TMPROG";
			$("#div-add-alleg").load(url,function(data){
				var options = { 
					dataType:  'json',
					success: showResponseAddAlleg  
				}; 
				$('#alleg-add-form').ajaxForm(options); 	
			}).dialog('open');
		}
		
		function showResponseAddAlleg(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");
			
			$.unblockUI();
			if(respobj[0].stat!="OK") { 
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {	
				$("#div-add-alleg").dialog("close");	
				lstAlleg();
			}  
		} 		
		
		function lstAlleg() {
			url = "?task=flstAlleg&tipoIns=T&TMPROG=$TMPROG";
			$("#div-lst-alleg").load(url);
		}
		
		function dltAlleg(jNAPROG) {
			if(!confirm("Eliminare questo allegato?")) return false;
			
			url = "?task=dltAlleg&tipoIns=T&NAPROG="+jNAPROG;
			$.get(url,function(data){
				lstAlleg();	
			});
		}
		
		function dspAlleg(jNAPROG) { 
			url = "?task=dspAlleg&tipoIns=T&NAPROG="+jNAPROG;
			document.location.href = url;
		}		
		
		function srcOrdFor() { 
			url = "?task=fltOrdFor&filt_NCFORN_o="+encodeURIComponent("$NCFORN")+"&filt_NCLITM_o="+encodeURIComponent("$NCLITM")+"&filt_NCLOTN_o="+encodeURIComponent("$NCLOTN");
			$("#div-src-ordfor").load(url,function(data){
					
			}).dialog('open');
		}
	
		function selOrdFor(jRRNF,jPDDCTO,jPDDOCO) {
			$("#addNCTRFO").val(jPDDCTO);	
			$("#addNCORFO").val(jPDDOCO);	
			$("#addRRNorf").val(jRRNF);	
			$("#div-src-ordfor").dialog("close");
		}
	
		function fltOrdFor() {
			formData = $("#ordfor-filter-form").serialize();
			url = "nonconf02.php?"+formData;	
			$("#div-src-ordfor").load(url,function(data){
					
			});			
		}
	
		/**/
		function srcOrdColCli() { 
			url = "?task=fltOrdColCli&tipoIns=T&TIPOFILE=$TIPOFILE&RRNF=$RRNF&TMPROG=$TMPROG&filt_NCAB8_occ="+encodeURIComponent("$NCAB8")+"&filt_NCLOTN_occ="+encodeURIComponent("$NCLOTN");
			$("#div-src-ordcolcli").load(url,function(data){
					
			}).dialog('open');
		}
	 
		function fltOrdColCli() {
			formData = $("#ordcolcli-filter-form").serialize();
			url = "nonconf02.php?"+formData;	
			$("#div-src-ordcolcli").load(url,function(data){
					
			});			
		}
		
		function addOrdColCli(jTIPOFILE_OC,jRRN_OC) {  
			url = "?task=addOrdColCli&tipoIns=T&TMPROG=$TMPROG&RRNF=0&TIPOFILE_OC="+jTIPOFILE_OC+"&RRN_OC="+jRRN_OC;
			$.get(url,function(data){ 
				alert("Ordine aggiunto");
				lstOrdLeg();
				srcOrdColCli();	
				calcNCQTSP();
			});			
		}
	
		/*
		function addOrdLeg() {
			url = "?task=addOrdLeg&tipoIns=T&TMPROG=$TMPROG&tipoDato=CLI&RRNF=0";
			$("#div-add-ordleg").load(url,function(data){
				
	            $('.positive-integer-dialog').numeric({
	                decimal: false, 
	                negative: false
	            });	
				
				var options = { 
					dataType:  'json',
					success: showResponseAddOrdLeg  
				}; 
				$('#ordleg-add-form').ajaxForm(options); 	
			}).dialog('open');
		}
		
		function showResponseAddOrdLeg(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");
			
			$.unblockUI();
			if(respobj[0].stat!="OK") {
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {	
				$("#div-add-ordleg").dialog("close");	
				lstOrdLeg();
			}  
		} 	
				 
		function sbm_ordLeg() {
			$.blockUI();
			$("#ordleg-add-form").submit();
		}	
		*/
		
		function lstOrdLeg() {
			$.blockUI();
			url = "?task=flstOrdLeg&tipoIns=T&TMPROG=$TMPROG";
			$("#div-lst-ordleg").load(url,function(data){
				$.unblockUI();	
			});
		}
		
		function dltOrdLeg(jOLPROG) {
			if(!confirm("Eliminare questo ordine legato?")) return false;
			
			url = "?task=dltOrdLeg&tipoIns=T&OLPROG="+jOLPROG;
			$.get(url,function(data){
				lstOrdLeg();
				calcNCQTSP();	
			});
		}
	 
		function calcNCQTSP() {
			jNCQTOP = $("#NCQTOP").val();
			url = "?task=fcalcNCQTSP&tipoIns=T&TMPROG=$TMPROG&NCQTOP="+jNCQTOP;
			$.getJSON(url,function(data){
				$("#addNCPZCO").val(data.NCQTSP);
				$("#addNCQTSP").val(data.NCQTSP).change();
			});
		}

	</script>
	
  </body>
</html>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "rcdchange")
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
    <title>Modifica non conformità cliente</title>
    
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/crud/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/js/jquery-ui.js"></script> 
    <script src="/crud/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
    
    <style>
    #navtop { margin-bottom:20px; }
    #rifeNcFor-btn { top:0px; }
    #rifeNcFor-btn:hover { cursor: pointer; }       
    </style>
    
    
  </head>
  <body class="single-record manage-record">
    <div id="div-add-alleg"></div>
    <div id="div-src-ordfor"></div>
    <div id="div-add-ordleg"></div>
    <div id="div-src-ordcolcli"></div>
     
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Modifica non conformità cliente</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="rcd-change-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endchangecli" />
            <input type="hidden" name="NCUORG" value="$NCUORG" />
            <input id="NCPROG_" type="hidden" name="NCPROG_" value="$NCPROG" />
            <input type="hidden" name="NCQTOP" id="NCQTOP" value="$NCQTOP" />  

			  <div id="navtop">
			    <input type="button" onclick="sbm_change();" class="btn btn-primary accept" value="Salva" />
			    <input type="button" class="btn btn-default cancel" onclick="$.blockUI();" value="Torna alla lista" />
			  </div>
            
              <div class="row" style="
SEGDTA;
 echo (($rifNcFor=="" || $rifNcFor==0)?('display:none;'):('')); 
		echo <<<SEGDTA
"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3">
	                <label for="rifeNcFor">Rif. Non conformità fornitore:</label>
	                <div class="input-group">
	                  <input type="text" id="rifeNcFor" class="form-control" name="rifeNcFor" size="10" maxlength="10" value="$rifNcFor" readonly>
	                  <span id="rifeNcFor-btn" title="Vai a non conformita cliente" class="input-group-addon glyphicon glyphicon-search" onclick="document.location.href='?task=beginchangefor&NCPROG=$rifNcFor'"></span>
	                </div>
	              </div>
              </div>
            
              <div class="row"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDTEM_lbl">
	                <label for="addNCDTEM">Data emissione</label>
	                <div>
	                  <input type="text" id="addNCDTEM" class="form-control" name="NCDTEM" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCDTEM); 
		echo <<<SEGDTA
" readonly>
	                  <span class="help-block" id="addNCDTEM_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDTEM_lbl">
	                <label for="addNCPROG">Numero non conformit&agrave;</label>
	                <div>
	                  <input type="text" id="addNCPROG" class="form-control" name="NCPROG_dsp" size="11" maxlength="11" value="$NCPROG" readonly>
	                </div>
	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRANC_lbl">
	                <label for="addNCRANC">Rapporto NC</label>
	                <div>
	                  <input type="text" id="addNCRANC" class="form-control" name="NCRANC" size="100" maxlength="100" value="$NCRANC">
	                  <span class="help-block" id="addNCRANC_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCCATE_lbl">
	                <label for="addNCCATE">Categoria</label>
	                <div>
	                  <select class="form-control" name="NCCATE" id="addNCCATE">
	                  	<option value=""></option>
	                  	<option value="NC" 
SEGDTA;
 echo (($NCCATE=="NC")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>NC</option>
	                  	<option value="SEGNALAZIONE" 
SEGDTA;
 echo (($NCCATE=="SEGNALAZIONE")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Segnalazione</option>
	                  </select> 
	                  <span class="help-block" id="addNCCATE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCAB8_lbl">
	                <label for="addNCAB8">Cod. Cliente</label>
	                <div>
	                  <input type="text" id="addNCAB8" class="form-control" name="NCAB8" size="8" maxlength="8" value="$NCAB8" readonly>
	                  <span class="help-block" id="addNCAB8_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRGCF_lbl">
	                <label for="addNCRGCF">Ragione Sociale</label>
	                <div>
	                  <input type="text" id="addNCRGCF" class="form-control" name="NCRGCF" size="40" maxlength="40" value="$NCRGCF" readonly>
	                  <span class="help-block" id="addNCRGCF_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">   
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDCTO_lbl">
	                <label for="addNCDCTO">Tipo ordine</label>
	                <div>
	                  <input type="text" id="addNCDCTO" class="form-control" name="NCDCTO" size="2" maxlength="2" value="$NCDCTO" readonly>
	                  <span class="help-block" id="addNCDCTO_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDOCO_lbl">
	                <label for="addNCDOCO">Num. ordine</label>
	                <div>
	                  <input type="text" id="addNCDOCO" class="form-control" name="NCDOCO" size="8" maxlength="8" value="$NCDOCO" readonly>
	                  <span class="help-block" id="addNCDOCO_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLNID_lbl">
	                <label for="addNCLNID">Num. linea</label>
	                <div>
	                  <input type="text" id="addNCLNID" class="form-control" name="NCLNID" size="6" maxlength="6" value="$NCLNID" readonly>
	                  <span class="help-block" id="addNCLNID_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLITM_lbl">
	                <label for="addNCLITM">Cod. articolo</label>
	                <div>
	                  <input type="text" id="addNCLITM" class="form-control" name="NCLITM" size="25" maxlength="25" value="$NCLITM" readonly>
	                  <span class="help-block" id="addNCLITM_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDSC1_lbl">
	                <label for="addNCDSC1">Des. articolo </label>
	                <div>
	                  <input type="text" id="addNCDSC1" class="form-control" name="NCDSC1" size="25" maxlength="25" value="$NCDSC1" readonly>
	                  <span class="help-block" id="addNCDSC1_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLOTN_lbl">
	                <label for="addNCLOTN">Lotto</label>
	                <div>
	                  <input type="text" id="addNCLOTN" class="form-control" name="NCLOTN" size="30" maxlength="30" value="$NCLOTN" readonly>
	                  <span class="help-block" id="addNCLOTN_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLOT2_lbl">
	                <label for="addNCLOT2">Selezione</label>
	                <div>
	                  <input type="text" id="addNCLOT2" class="form-control" name="NCLOT2" size="30" maxlength="30" value="$NCLOT2" readonly>
	                  <span class="help-block" id="addNCLOT2_err"></span>
	                </div>
	              </div>
	          </div>
	          
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCQTSP_lbl">
	                <label for="addNCQTSP">Quantit&agrave; spedita</label>
	                <div>
	                  <input type="text" id="addNCQTSP" class="form-control fld-calc-percentuale" name="NCQTSP" size="30" maxlength="30" value="$NCQTSP" readonly>
	                  <span class="help-block" id="addNCQTSP_err"></span>
	                </div>
	              </div> 
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCPZCO_lbl">
	                <label for="addNCPZCO">Pezzi contestati</label>
	                <div>
	                  <input type="text" id="addNCPZCO" class="positive-integer form-control fld-calc-percentuale" name="NCPZCO" size="15" maxlength="15" value="$NCPZCO">
	                  <span class="help-block" id="addNCPZCO_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCPPCO_lbl">
	                <label for="addNCPPCO">% Pezzi contestati</label>
	                <div>
	                  <input type="text" id="addNCPPCO" class="form-control" name="NCPPCO" size="6" maxlength="7" value="$NCPPCO" readonly>
	                  <span class="help-block" id="addNCPPCO_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCFORN_lbl">
	                <label for="addNCFORN">Fornitore</label>
	                <div>
	                  <input type="text" id="addNCFORN" class="form-control" name="NCFORN" size="8" maxlength="8" value="$NCFORN" readonly>
	                  <span class="help-block" id="addNCFORN_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRGFO_lbl">
	                <label for="addNCRGFO">&nbsp;</label>
	                <div>
	                  <input type="text" id="addNCRGFO" class="form-control" name="NCRGFO" value="$NCRGFO" readonly> 
	                </div>
	              </div>
              </div>
              
              <input type="hidden" name="NCTRFO" id="addNCTRFO" value="$NCTRFO"> 
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCORFO_lbl">
	                <label for="addNCORFO">Ordine fornitore</label>
	                <div class="input-group">
	                  <input type="text" id="addNCORFO" class="form-control" name="NCORFO" size="8" maxlength="8" value="$NCORFO" readonly>
	                  <span id="rifeNcFor-btn" title="Vai a non conformita fornitore" class="input-group-addon glyphicon glyphicon-search" style="
SEGDTA;
 echo (($rifNcFor=="" || $rifNcFor==0)?('display:none'):('')); 
		echo <<<SEGDTA
" onclick="document.location.href='?task=beginchangefor&NCPROG=$rifNcFor'"></span>
	                </div>
	                <span class="help-block" id="addNCORFO_err"></span>
	              </div> 
              </div>
              	          
	          <div class="row">    
	              <div class="form-group col-xs-12 col-sm-12 col-lg-6" id="addNCDIFE_lbl">
	                <label for="addNCDIFE">Difetto</label>
	                <div>
	                  <!--<input type="text" id="addNCDIFE" class="form-control" name="NCDIFE" size="35" maxlength="35" value="$NCDIFE">-->
	                  
SEGDTA;
 $this->lstDifetti($NCDIFE); 
		echo <<<SEGDTA

	                  <span class="help-block" id="addNCDIFE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <!--
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3">
	                <label for="addNCRICL">&nbsp;</label>
					<div class="checkbox">
						<label>
						  <input type="checkbox" id="addNCRICL" name="NCRICL" class="checkbox style-0" value="S" 
SEGDTA;
 echo (($NCRICL=="S")?('checked="checked"'):('')); 
		echo <<<SEGDTA
>
						  <span>Risposta cliente</span>
						</label>
					</div> 
	              </div>
	              -->
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDTRC_lbl">
	                <label for="addNCDTRC">Data risposta cliente</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCDTRC').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTRC" class="calendario form-control" name="NCDTRC" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCDTRC); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCDTRC_err"></span>
	                </div>
	              </div>
	          </div>    
 
              <div class="row">   

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCRSQT_lbl">
	                <label for="addNCRSQT">Q.t&agrave; Reso</label>
	                <div>
	                  <input type="text" id="addNCRSQT" class="form-control positive-integer" name="NCRSQT" size="9" maxlength="9" value="$NCRSQT">
	                  <span class="help-block" id="addNCRSQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCRSDT_lbl">
	                <label for="addNCRSDT">Data Reso</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCRSDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCRSDT" class="calendario form-control" name="NCRSDT" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCRSDT); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCRSDT_err"></span>
	                </div>
	              </div> 
	          </div>
	          
	          <div class="row">      
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCSEQT_lbl">
	                <label for="addNCSEQT">Q.t&agrave; sostituzione effettuata</label>
	                <div>
	                  <input type="text" id="addNCSEQT" class="form-control positive-integer" name="NCSEQT" size="9" maxlength="9" value="$NCSEQT">
	                  <span class="help-block" id="addNCSEQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCSEDT_lbl">
	                <label for="addNCSEDT">Data sostituzione effettuata</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCSEDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCSEDT" class="calendario form-control" name="NCSEDT" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCSEDT); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCSEDT_err"></span>
	                </div>
	              </div>     
	          </div>
	          
	          <div class="row">      
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCQTSE_lbl">
	                <label for="addNCQTSE">Q.t&agrave; selezionata/sistemata</label>
	                <div>
	                  <input type="text" id="addNCQTSE" class="form-control positive-integer" name="NCQTSE" size="9" maxlength="9" value="$NCQTSE">
	                  <span class="help-block" id="addNCQTSE_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCQTRI_lbl">
	                <label for="addNCQTRI">Q.t&agrave; rispedita</label>
	                <div>
	                  <input type="text" id="addNCQTRI" class="form-control positive-integer" name="NCQTRI" size="10" maxlength="10" value="$NCQTRI">
	                  <span class="help-block" id="addNCQTRI_err"></span>
	                </div>
	              </div>      
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCDTSP_lbl">
	                <label for="addNCDTSP">Data spedizione</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCDTSP').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTSP" class="form-control calendario" name="NCDTSP" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCDTSP); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCDTSP_err"></span>
	                </div>
	              </div>    
	          </div>
	          
	          <div class="row">       

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCAEQT_lbl">
	                <label for="addNCAEQT">Q.t&agrave; accredito effettuato</label>
	                <div>
	                  <input type="text" id="addNCAEQT" class="form-control positive-integer" name="NCAEQT" size="9" maxlength="9" value="$NCAEQT">
	                  <span class="help-block" id="addNCAEQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCAEDT_lbl">
	                <label for="addNCAEDT">Data accredito effettuato</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCAEDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCAEDT" class="calendario form-control" name="NCAEDT" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCAEDT); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCAEDT_err"></span>
	                </div>
	              </div> 
              </div>
              
              <div class="row">

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCDTCH_lbl">
	                <label for="addNCDTCH">Data chiusura</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCDTCH').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTCH" class="calendario form-control" name="NCDTCH" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCDTCH); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCDTCH_err"></span>
	                </div>
	              </div>
	          </div>     
	           
	          <div class="row">    
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCNOTE_lbl">
	                <label for="addNCNOTE">Note</label>
	                <div>
	                  <textarea id="addNCNOTE" class="form-control" name="NCNOTE" maxlength="2048" rows="7">$NCNOTE</textarea>
	                  <span class="help-block" id="addNCNOTE_err"></span>
	                </div>
	              </div>
	          </div>   
	           
	          <div class="row">     
	           <fieldset>
	            <div class="form-group col-xs-12 col-sm-12 col-lg-6">
	           	<legend>Altri ordini collegati <span class="pull-right"><input type="button" class="btn btn-xs btn-primary" value="Aggiungi ordine" onclick="srcOrdColCli();"> </span></legend>
		           	<div id="div-lst-ordleg">
SEGDTA;
 $this->lstOrdLeg("D",$NCPROG); 
		echo <<<SEGDTA
</div>
	           	</div>
	           </fieldset>
	          </div>  
	           
	          <div class="row">     
	           <fieldset>
	            <div class="form-group col-xs-12 col-sm-12 col-lg-6">
	           	<legend>Allegati <span class="pull-right"><input type="button" class="btn btn-xs btn-primary" value="Aggiungi allegato" onclick="addAlleg();"> </span></legend>
		           	<div id="div-lst-alleg">
SEGDTA;
 $this->lstAllegati("D",$NCPROG); 
		echo <<<SEGDTA
</div>
	           	</div>
	           </fieldset>
	          </div>  
	             
            <div id="navbottom">
              <input type="button" onclick="sbm_change();" class="btn btn-primary accept" value="Salva" />
              <input type="button" class="btn btn-default cancel" onclick="$.blockUI();" value="Torna alla lista" />
            </div>
            		
          </form>
        </div>
      </div>
    </div>
    
    <script src="/crud/websmart/v13.2/js/jquery.maskedinput.min.js"></script>  
    <script src="/crud/websmart/v13.2/js/jquery.numeric.js"></script>   
    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
    <script type="text/javascript">
		jQuery(function() {
			// Focus the first input on page load
			jQuery("input:enabled:first").focus();
			
			// Bind event to the Back button
			jQuery(".cancel").click(goback);
			function goback()
			{
				window.location.replace("$redirPgm");
				return false;
			}
			
			$('.calendario').datepicker({
				dateFormat: 'dd-mm-yy',
				buttonImageOnly: false,
				changeMonth: true,   
				changeYear: true 
			}); 			
			$(".calendario").mask("99-99-9999",{placeholder:" "});
			
            $('.positive-integer').numeric({
	                decimal: false, 
	                negative: false
            });			
            $(".fld-calc-percentuale").change(function(e){
            	jaddNCPZCO = $("#addNCPZCO").val();
            	jaddNCQTSP = $("#addNCQTSP").val();
            	 
            	if(jaddNCQTSP!='' && jaddNCQTSP != 0) {
            		jaddNCPPCO = (jaddNCPZCO /  jaddNCQTSP) * 100;	
            		$("#addNCPPCO").val(jaddNCPPCO.toFixed(2));
            	}
            });
              
			$("#div-add-alleg").dialog({
				autoOpen: false,
				modal: true,
				width: 700, 
				resizable: false,
				title: 'Allegato'
			});	
			
			$("#div-src-ordcolcli").dialog({
				autoOpen: false,
				modal: true,
				width: 900, 
				resizable: false,
				title: 'Ordini'
			});				
			
			$("#div-add-ordleg").dialog({
				autoOpen: false,
				modal: true,
				width: 700, 
				resizable: false,
				title: 'Ordini legati'
			});	
			 
			$("#div-src-ordfor").dialog({
				autoOpen: false,
				modal: true,
				width: 900, 
				resizable: false,
				title: 'Ordini fornitore'
			});				
              
			var options = { 
				dataType:  'json',
				success: showResponseChange 
			}; 
			$('#rcd-change-form').ajaxForm(options); 
              
		});
		
		function sbm_change() {
			$.blockUI();
			$("#rcd-change-form").submit();
		}
		
		function sbm_alleg() {
			$.blockUI();
			$("#alleg-add-form").submit();
		}			
		
		function showResponseChange(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");

			if(respobj[0].stat!="OK") {
				$.unblockUI();
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {		
				 document.location.href="$redirPgm?page=1";
			}  
		} 
		
		function addAlleg() {
			url = "?task=addAlleg&tipoIns=D&TMPROG=$NCPROG";
			$("#div-add-alleg").load(url,function(data){
				var options = { 
					dataType:  'json',
					success: showResponseAddAlleg  
				}; 
				$('#alleg-add-form').ajaxForm(options); 	
			}).dialog('open');
		}
		
		function showResponseAddAlleg(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");
			
			$.unblockUI();
			if(respobj[0].stat!="OK") {
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {	
				$("#div-add-alleg").dialog("close");	
				lstAlleg();
			}  
		} 		
		
		function lstAlleg() {
			url = "?task=flstAlleg&tipoIns=D&TMPROG=$NCPROG";
			$("#div-lst-alleg").load(url);
		}
		
		function dltAlleg(jNAPROG) {
			if(!confirm("Eliminare questo allegato?")) return false;
			
			url = "?task=dltAlleg&tipoIns=D&NAPROG="+jNAPROG;
			$.get(url,function(data){
				lstAlleg();	
			});
		}
		
		function dspAlleg(jNAPROG) { 
			url = "?task=dspAlleg&tipoIns=D&NAPROG="+jNAPROG;
			document.location.href = url;
		}			
		
		function srcOrdFor() { 
			url = "?task=fltOrdFor&filt_NCFORN_o="+encodeURIComponent("$NCFORN")+"&filt_NCLITM_o="+encodeURIComponent("$NCLITM")+"&filt_NCLOTN_o="+encodeURIComponent("$NCLOTN");
			$("#div-src-ordfor").load(url,function(data){
					
			}).dialog('open');
		}
	
		function selOrdFor(jRRNF,jPDDCTO,jPDDOCO) {
			$("#addNCTRFO").val(jPDDCTO);	
			$("#addNCORFO").val(jPDDOCO);	
			$("#addRRNorf").val(jRRNF);	
			$("#div-src-ordfor").dialog("close");
		}
	
		function fltOrdFor() {
			formData = $("#ordfor-filter-form").serialize();
			url = "nonconf02.php?"+formData;	
			$("#div-src-ordfor").load(url,function(data){
					
			});			
		}
		
		/**/
		function srcOrdColCli() { 
			url = "?task=fltOrdColCli&tipoIns=D&TMPROG=$NCPROG&filt_NCAB8_occ="+encodeURIComponent("$NCAB8")+"&filt_NCLOTN_occ="+encodeURIComponent("$NCLOTN");
			$("#div-src-ordcolcli").load(url,function(data){
					
			}).dialog('open');
		}
	 
		function fltOrdColCli() {
			formData = $("#ordcolcli-filter-form").serialize();
			url = "nonconf02.php?"+formData;	
			$("#div-src-ordcolcli").load(url,function(data){
					
			});			
		}
		
		function addOrdColCli(jTIPOFILE_OC,jRRN_OC) { 
			url = "?task=addOrdColCli&tipoIns=D&TMPROG=$NCPROG&RRNF=0&TIPOFILE_OC="+jTIPOFILE_OC+"&RRN_OC="+jRRN_OC;
			$.get(url,function(data){
				alert("Ordine aggiunto");
				lstOrdLeg();
				srcOrdColCli();	
				calcNCQTSP();
			});			
		}
		
		/*
		function addOrdLeg() {
			url = "?task=addOrdLeg&tipoIns=D&TMPROG=$NCPROG&tipoDato=CLI&RRNF=$RRNF";
			$("#div-add-ordleg").load(url,function(data){
				
	            $('.positive-integer-dialog').numeric({
	                decimal: false, 
	                negative: false
	            });	
				
				var options = { 
					dataType:  'json',
					success: showResponseAddOrdLeg  
				}; 
				$('#ordleg-add-form').ajaxForm(options); 	
			}).dialog('open');
		}
		
		function showResponseAddOrdLeg(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");
			
			$.unblockUI();
			if(respobj[0].stat!="OK") {
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {	
				$("#div-add-ordleg").dialog("close");	
				lstOrdLeg();
			}  
		} 		
		*/
		
		function lstOrdLeg() {
			$.blockUI();
			url = "?task=flstOrdLeg&tipoIns=D&TMPROG=$NCPROG";
			$("#div-lst-ordleg").load(url,function(data){
				$.unblockUI();	
			});
		}
		
		function dltOrdLeg(jOLPROG) {
			if(!confirm("Eliminare questo ordine legato?")) return false;
			
			url = "?task=dltOrdLeg&tipoIns=D&OLPROG="+jOLPROG;
			$.get(url,function(data){
				lstOrdLeg();
				calcNCQTSP();	
			});
		}
	 
		function calcNCQTSP() {
			jNCQTOP = $("#NCQTOP").val();
			url = "?task=fcalcNCQTSP&tipoIns=D&TMPROG=$NCPROG&NCQTOP="+jNCQTOP;
			$.getJSON(url,function(data){
				$("#addNCPZCO").val(data.NCQTSP);
				$("#addNCQTSP").val(data.NCQTSP).change(); 
			});
		}

		 
	</script>
  </body>
</html>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "headnccli")
	{

		echo <<<SEGDTA
<table class="table table-condensed table-bordered" style="width:auto;">
<tr>
	<th>Data emissione</th>
	<th>Tipo</th>
	<th>Pezzi contestati</th>
	<th>% Pezzi contestati</th>
	<th>Difetto</th>
	<th></th>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dettnccli")
	{

		echo <<<SEGDTA
	<tr>
		<td>
SEGDTA;
 echo $this->cvtDateFromDb($NCDTEM); 
		echo <<<SEGDTA
</td>
		<td>
		
SEGDTA;
 echo (($NCTPCF=="C")?("Cliente"):("Fornitore")); 
		echo <<<SEGDTA

		</td>
		<td class="text num">$NCPZCO</td>
		<td class="text num">$NCPPCO</td> 
		<td>
SEGDTA;
 echo $this->decodDifetto($NCDIFE); 
		echo <<<SEGDTA
</td>
		<td>
     		 <a class="btn btn-primary btn-xs glyphicon glyphicon-pencil" title="Change this record" href="$pf_scriptname?task=beginchangecli&amp;NCPROG=$NCPROG"></a> 
     		 <a class="btn btn-danger btn-xs glyphicon glyphicon-remove" title="Delete this record" href="javascript:void('0');" onclick="dltNonConfCli('$TIPOFILE','$RRNF','$NCPROG');"></a>
		</td>
	</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "rcdaddfor")
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
    <title>Inserimento non conformità fornitore</title>
    
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/crud/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/js/jquery-ui.js"></script> 
    <script src="/crud/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
    
    <style>
    #navtop { margin-bottom:20px; }
    </style>    
    
  </head>
  <body class="single-record manage-record">
    <div id="div-add-alleg"></div>
    <div id="div-add-ordleg"></div>
 	<div id="div-src-ordcolfor"></div>
 	
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Inserimento non conformità fornitore</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="rcd-add-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endaddfor" />
            <input type="hidden" name="NCUORG" value="$NCUORG" />
            <input type="hidden" name="TMPROG" value="$TMPROG" /> 
            <input type="hidden" name="NCRIFE" value="$NCRIFE" /> 
            <input type="hidden" name="NCQTOP" id="NCQTOP" value="$NCQTOP" />  
             
			  <div id="navtop">
			    <input type="button" onclick="sbm_add();" class="btn btn-primary accept" value="Salva" />
			    <input type="button" class="btn btn-default cancel" onclick="$.blockUI();" value="Torna alla lista" />
			  </div>
             
               <div class="row" style="
SEGDTA;
 echo (($NCRIFE=="")?('display:none;'):('')); 
		echo <<<SEGDTA
"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3">
	                <label for="rifeNcCli">Rif. Non conformità cliente:</label>
	                <div>
	                  <input type="text" id="rifeNcCli" class="form-control" name="rifeNcCli" size="10" maxlength="10" value="$NCRIFE" readonly>
	                  <span class="help-block" id="rifeNcCli_err"></span>
	                </div>
	              </div>
              </div>
             
              <div class="row"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDTEM_lbl">
	                <label for="addNCDTEM">Data emissione</label>
	                <div>
	                  <input type="text" id="addNCDTEM" class="form-control" name="NCDTEM" size="10" maxlength="10" value="$NCDTEM" readonly>
	                  <span class="help-block" id="addNCDTEM_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRANC_lbl">
	                <label for="addNCRANC">Rapporto NC</label>
	                <div>
	                  <input type="text" id="addNCRANC" class="form-control" name="NCRANC" size="100" maxlength="100" value="$NCRANC">
	                  <span class="help-block" id="addNCRANC_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCCATE_lbl">
	                <label for="addNCCATE">Categoria</label>
	                <div>
	                  <select class="form-control" name="NCCATE" id="addNCCATE">
	                  	<option value=""></option>
	                  	<option value="NC" 
SEGDTA;
 echo (($NCCATE=="NC")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>NC</option>
	                  	<option value="SEGNALAZIONE" 
SEGDTA;
 echo (($NCCATE=="SEGNALAZIONE")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Segnalazione</option>
	                  </select> 
	                  <span class="help-block" id="addNCCATE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCAB8_lbl">
	                <label for="addNCAB8">Cod. Fornitore</label>
	                <div>
	                  <input type="text" id="addNCAB8" class="form-control" name="NCAB8" size="10" maxlength="10" value="$NCAB8" readonly>
	                  <span class="help-block" id="addNCAB8_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRGCF_lbl">
	                <label for="addNCRGCF">Ragione Sociale</label>
	                <div>
	                  <input type="text" id="addNCRGCF" class="form-control" name="NCRGCF" size="40" maxlength="40" value="$NCRGCF" readonly>
	                  <span class="help-block" id="addNCRGCF_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">   
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDCTO_lbl">
	                <label for="addNCDCTO">Tipo ordine</label>
	                <div>
	                  <input type="text" id="addNCDCTO" class="form-control" name="NCDCTO" size="2" maxlength="2" value="$NCDCTO" readonly>
	                  <span class="help-block" id="addNCDCTO_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDOCO_lbl">
	                <label for="addNCDOCO">Num. ordine</label>
	                <div>
	                  <input type="text" id="addNCDOCO" class="form-control" name="NCDOCO" size="10" maxlength="10" value="$NCDOCO" readonly>
	                  <span class="help-block" id="addNCDOCO_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLNID_lbl">
	                <label for="addNCLNID">Num. linea</label>
	                <div>
	                  <input type="text" id="addNCLNID" class="form-control" name="NCLNID" size="6" maxlength="6" value="$NCLNID" readonly>
	                  <span class="help-block" id="addNCLNID_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLITM_lbl">
	                <label for="addNCLITM">Cod. articolo</label>
	                <div>
	                  <input type="text" id="addNCLITM" class="form-control" name="NCLITM" size="25" maxlength="25" value="$NCLITM" readonly>
	                  <span class="help-block" id="addNCLITM_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDSC1_lbl">
	                <label for="addNCDSC1">Des. articolo </label>
	                <div>
	                  <input type="text" id="addNCDSC1" class="form-control" name="NCDSC1" size="25" maxlength="25" value="$NCDSC1" readonly>
	                  <span class="help-block" id="addNCDSC1_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLOTN_lbl">
	                <label for="addNCLOTN">Lotto</label>
	                <div>
	                  <input type="text" id="addNCLOTN" class="form-control" name="NCLOTN" size="30" maxlength="30" value="$NCLOTN" readonly>
	                  <span class="help-block" id="addNCLOTN_err"></span>
	                </div>
	              </div> 
	          </div>
	          
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCQTSP_lbl">
	                <label for="addNCQTSP">Quantit&agrave; spedita</label>
	                <div>
	                  <input type="text" id="addNCQTSP" class="form-control fld-calc-percentuale" name="NCQTSP" size="30" maxlength="30" value="$NCQTSP" readonly>
	                  <span class="help-block" id="addNCQTSP_err"></span>
	                </div>
	              </div> 
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCPZCO_lbl">
	                <label for="addNCPZCO">Pezzi contestati</label>
	                <div>
	                  <input type="text" id="addNCPZCO" class="positive-integer form-control fld-calc-percentuale" name="NCPZCO" size="15" maxlength="15" value="$NCPZCO">
	                  <span class="help-block" id="addNCPZCO_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCPPCO_lbl">
	                <label for="addNCPPCO">% Pezzi contestati</label>
	                <div>
	                  <input type="text" id="addNCPPCO" class="form-control" name="NCPPCO" size="6" maxlength="7" value="$NCPPCO" readonly>
	                  <span class="help-block" id="addNCPPCO_err"></span>
	                </div>
	              </div>
	          </div>
	           
	          <div class="row">    
	              <div class="form-group col-xs-12 col-sm-12 col-lg-6" id="addNCDIFE_lbl">
	                <label for="addNCDIFE">Difetto</label>
	                <div>
	                  <!--<input type="text" id="addNCDIFE" class="form-control" name="NCDIFE" size="35" maxlength="35" value="$NCDIFE">-->
	                  
SEGDTA;
 $this->lstDifetti($NCDIFE); 
		echo <<<SEGDTA

	                  <span class="help-block" id="addNCDIFE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3">
	                <label for="addNCRICL">&nbsp;</label>
					<div class="checkbox">
						<label>
						  <input type="checkbox" id="addNCRICL" name="NCRICL" class="checkbox style-0" value="S">
						  <span>Risposta fornitore</span>
						</label>
					</div> 
	              </div> 
	          </div>    
	         
              <div class="row">   

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCRSQT_lbl">
	                <label for="addNCRSQT">Q.t&agrave; Reso</label>
	                <div>
	                  <input type="text" id="addNCRSQT" class="form-control positive-integer" name="NCRSQT" size="9" maxlength="9" value="$NCRSQT">
	                  <span class="help-block" id="addNCRSQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCRSDT_lbl">
	                <label for="addNCRSDT">Data Reso</label>
					<div class="input-group">
					     <span class="input-group-addon" onclick="$('#addNCRSDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
						 <input type="text" id="addNCRSDT" class="calendario form-control" name="NCRSDT" size="10" maxlength="10" value="$NCRSDT">
						 <span class="help-block" id="addNCRSDT_err"></span>
					</div>		
	              </div> 
	          </div>
	          
	          <div class="row"> 

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCSEQT_lbl">
	                <label for="addNCSEQT">Q.t&agrave; sostituzione effettuata</label>
	                <div>
	                  <input type="text" id="addNCSEQT" class="form-control positive-integer" name="NCSEQT" size="9" maxlength="9" value="$NCSEQT">
	                  <span class="help-block" id="addNCSEQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCSEDT_lbl">
	                <label for="addNCSEDT">Data sostituzione effettuata</label> 
					<div class="input-group">
					    <span class="input-group-addon" onclick="$('#addNCSEDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCSEDT" class="calendario form-control" name="NCSEDT" size="10" maxlength="10" value="$NCSEDT">
	                  	<span class="help-block" id="addNCSEDT_err"></span>
					</div>	 
	              </div> 
	          </div>
	          
	          <div class="row">      
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCQTSE_lbl">
	                <label for="addNCQTSE">Q.t&agrave; selezionata/sistemata</label>
	                <div>
	                  <input type="text" id="addNCQTSE" class="form-control positive-integer" name="NCQTSE" size="9" maxlength="9" value="$NCQTSE">
	                  <span class="help-block" id="addNCQTSE_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCQTRI_lbl">
	                <label for="addNCQTRI">Q.t&agrave; rispedita</label>
	                <div>
	                  <input type="text" id="addNCQTRI" class="form-control positive-integer" name="NCQTRI" size="10" maxlength="10" value="$NCQTRI">
	                  <span class="help-block" id="addNCQTRI_err"></span>
	                </div>
	              </div>       
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCDTSP_lbl">
	                <label for="addNCDTSP">Data spedizione</label>
					<div class="input-group">
					    <span class="input-group-addon" onclick="$('#addNCDTSP').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTSP" class="form-control calendario" name="NCDTSP" size="10" maxlength="10" value="$NCDTSP">
	                  	<span class="help-block" id="addNCDTSP_err"></span>
	                </div>
	              </div>    
	          </div>
	          
	          <div class="row">       

	              <div class="form-group col-xs-12  col-sm-4 col-lg-2" id="addNCAEQT_lbl">
	                <label for="addNCAEQT">Q.t&agrave; accredito effettuato</label>
	                <div>
	                  <input type="text" id="addNCAEQT" class="form-control positive-integer" name="NCAEQT" size="9" maxlength="9" value="$NCAEQT">
	                  <span class="help-block" id="addNCAEQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCAEDT_lbl">
	                <label for="addNCAEDT">Data accredito effettuato</label>
					<div class="input-group">
					    <span class="input-group-addon" onclick="$('#addNCAEDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCAEDT" class="calendario form-control" name="NCAEDT" size="10" maxlength="10" value="$NCAEDT">
	                  	<span class="help-block" id="addNCAEDT_err"></span>
	                </div>
	              </div> 
              </div>
              
              <div class="row">

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCDTCH_lbl">
	                <label for="addNCDTCH">Data chiusura</label>
					<div class="input-group">
					    <span class="input-group-addon" onclick="$('#addNCDTCH').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTCH" class="calendario form-control" name="NCDTCH" size="10" maxlength="10" value="$NCDTCH">
	                  	<span class="help-block" id="addNCDTCH_err"></span>
	                </div>
	              </div>
	          </div>     
	           
	          <div class="row">    
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCNOTE_lbl">
	                <label for="addNCNOTE">Note</label>
	                <div>
	                  <textarea id="addNCNOTE" class="form-control" name="NCNOTE" maxlength="2048" rows="7">$NCNOTE</textarea>
	                  <span class="help-block" id="addNCNOTE_err"></span>
	                </div>
	              </div>
              </div>   

	          <div class="row">     
	           <fieldset>
	            <div class="form-group col-xs-12 col-sm-12 col-lg-6">
	           	<legend>Altri ordini collegati <span class="pull-right"><input type="button" class="btn btn-xs btn-primary" value="Aggiungi ordine" onclick="srcOrdColFor();"> </span></legend>
		           	<div id="div-lst-ordleg">
		           	
SEGDTA;

						$this->dspNoDetailsMsg("Nessun ordine collegato");
		           	
		echo <<<SEGDTA

		           	</div>
	           	</div>
	           </fieldset>
	          </div>  

	          <div class="row">     
	           <fieldset>
	            <div class="form-group col-xs-12 col-sm-12 col-lg-6">
	           	<legend>Allegati <span class="pull-right"><input type="button" class="btn btn-xs btn-primary" value="Aggiungi allegato" onclick="addAlleg();"> </span></legend>
		           	<div id="div-lst-alleg">
		           	
SEGDTA;

						$this->dspNoDetailsMsg("Nessun allegato");
		           	
		echo <<<SEGDTA

		           	</div>
	           	</div>
	           </fieldset>
	          </div>  
	            
            <div id="navbottom">
              <input type="button" onclick="sbm_add()" class="btn btn-primary accept" value="Salva" />
              <input type="button" class="btn btn-default cancel" onclick="$.blockUI();" value="Torna alla lista" />
            </div>		
          </form>
        </div>
      </div>
    </div>
    
    <script src="/crud/websmart/v13.2/js/jquery.maskedinput.min.js"></script>  
    <script src="/crud/websmart/v13.2/js/jquery.numeric.js"></script>   
    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
    <script type="text/javascript">
		jQuery(function() {
			// Focus the first input on page load
			jQuery("input:enabled:first").focus();
			
			// Bind event to the Back button
			jQuery(".cancel").click(goback);
			function goback()
			{
				window.location.replace("nonconf01.php?page=1");
				return false;
			}
			
			$('.calendario').datepicker({
				dateFormat: 'dd-mm-yy',
				buttonImageOnly: false,
				changeMonth: true,   
				changeYear: true 
			}); 			
			$(".calendario").mask("99-99-9999",{placeholder:" "});
			
            $('.positive-integer').numeric({
	                decimal: false, 
	                negative: false
            });			
            $(".fld-calc-percentuale").change(function(e){
            	jaddNCPZCO = $("#addNCPZCO").val();
            	jaddNCQTSP = $("#addNCQTSP").val();
            	 
            	if(jaddNCQTSP!='' && jaddNCQTSP != 0) {
            		jaddNCPPCO = (jaddNCPZCO /  jaddNCQTSP) * 100;	
            		$("#addNCPPCO").val(jaddNCPPCO.toFixed(2));
            	}
            });
              
			$("#div-add-alleg").dialog({
				autoOpen: false,
				modal: true,
				width: 700, 
				resizable: false,
				title: 'Allegato'
			});	
              
			$("#div-add-ordleg").dialog({
				autoOpen: false,
				modal: true,
				width: 700, 
				resizable: false,
				title: 'Ordini legati'
			});	
			 
			$("#div-src-ordcolfor").dialog({
				autoOpen: false,
				modal: true,
				width: 900, 
				resizable: false,
				title: 'Ordini'
			});	
			  
			var options = { 
				dataType:  'json',
				success: showResponseAdd 
			}; 
			$('#rcd-add-form').ajaxForm(options); 
              
		});
		  
		function sbm_add() {
			$.blockUI();
			$("#rcd-add-form").submit();
		}
		
		function sbm_alleg() {
			$.blockUI();
			$("#alleg-add-form").submit();
		}	
		  
		function showResponseAdd(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");

			if(respobj[0].stat!="OK") {
				$.unblockUI();
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {		
				 document.location.href="nonconf01.php?page=1";
			}  
		} 
		
		function addAlleg() {
			url = "?task=addAlleg&tipoIns=T&TMPROG=$TMPROG";
			$("#div-add-alleg").load(url,function(data){
				var options = { 
					dataType:  'json',
					success: showResponseAddAlleg  
				}; 
				$('#alleg-add-form').ajaxForm(options); 	
			}).dialog('open');
		}
		
		function showResponseAddAlleg(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");
			
			$.unblockUI();
			if(respobj[0].stat!="OK") { 
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {	
				$("#div-add-alleg").dialog("close");	
				lstAlleg();
			}  
		} 		
		
		function lstAlleg() {
			url = "?task=flstAlleg&tipoIns=T&TMPROG=$TMPROG";
			$("#div-lst-alleg").load(url);
		}
		
		function dltAlleg(jNAPROG) {
			if(!confirm("Eliminare questo allegato?")) return false;
			
			url = "?task=dltAlleg&tipoIns=T&NAPROG="+jNAPROG;
			$.get(url,function(data){
				lstAlleg();	
			});
		}
		
		function dspAlleg(jNAPROG) { 
			url = "?task=dspAlleg&tipoIns=T&NAPROG="+jNAPROG;
			document.location.href = url;
		}		
 
		/**/
		function srcOrdColFor() { 
			url = "?task=fltOrdColFor&tipoIns=T&TMPROG=$TMPROG&RRNF=$RRNF&filt_NCAB8_occ="+encodeURIComponent("$NCAB8")+"&filt_NCLOTN_occ="+encodeURIComponent("$NCLOTN");
			$("#div-src-ordcolfor").load(url,function(data){
					
			}).dialog('open');
		}
	 
		function fltOrdColFor() {
			formData = $("#ordcolfor-filter-form").serialize();
			url = "nonconf02.php?"+formData;	
			$("#div-src-ordcolfor").load(url,function(data){
					
			});			
		}
		
		function addOrdColFor(jRRN_OC) { 
			$.blockUI();
			url = "?task=addOrdColFor&tipoIns=T&TMPROG=$TMPROG&RRNF=0&RRN_OC="+jRRN_OC;
			$.get(url,function(data){
				$.unblockUI();
				alert("Ordine aggiunto");
				lstOrdLeg();
				srcOrdColFor();	
				calcNCQTSP();
			});			
		}
		
		/*
		function addOrdLeg() {
			url = "?task=addOrdLeg&tipoIns=T&TMPROG=$TMPROG&tipoDato=FOR&RRNF=0";
			$("#div-add-ordleg").load(url,function(data){
				
	            $('.positive-integer-dialog').numeric({
	                decimal: false, 
	                negative: false
	            });	
				
				var options = { 
					dataType:  'json',
					success: showResponseAddOrdLeg  
				}; 
				$('#ordleg-add-form').ajaxForm(options); 	
			}).dialog('open');
		}
		
		function showResponseAddOrdLeg(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");
			
			$.unblockUI();
			if(respobj[0].stat!="OK") {
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {	
				$("#div-add-ordleg").dialog("close");	
				lstOrdLeg();
			}  
		} 	
		
		function sbm_ordLeg() {
			$.blockUI();
			$("#ordleg-add-form").submit();
		}				
		*/
		
		function lstOrdLeg() {
			$.blockUI();
			url = "?task=flstOrdLeg&tipoIns=T&TMPROG=$TMPROG";
			$("#div-lst-ordleg").load(url,function(data){
				$.unblockUI();	
			});
		}
		
		function dltOrdLeg(jOLPROG) {
			if(!confirm("Eliminare questo ordine legato?")) return false;
			
			url = "?task=dltOrdLeg&tipoIns=T&OLPROG="+jOLPROG;
			$.get(url,function(data){
				lstOrdLeg();
				calcNCQTSP();	
			});
		}
		 
		function calcNCQTSP() {
			jNCQTOP = $("#NCQTOP").val();
			url = "?task=fcalcNCQTSP&tipoIns=T&TMPROG=$TMPROG&NCQTOP="+jNCQTOP;
			$.getJSON(url,function(data){
				$("#addNCPZCO").val(data.NCQTSP);
				$("#addNCQTSP").val(data.NCQTSP).change();
				
			});
		}		

	</script>
  </body>
</html>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "rcdchangefor")
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
    <title>Modifica non conformità fornitore</title>
    
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/crud/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/js/jquery-ui.js"></script> 
    <script src="/crud/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
    
    <style>
    #navtop { margin-bottom:20px; }
    #rifeNcCli-btn { top:0px; }
    #rifeNcCli-btn:hover { cursor: pointer; }
    </style>    
    
  </head>
  <body class="single-record manage-record">
    <div id="div-add-alleg"></div>
    <div id="div-add-ordleg"></div>
    <div id="div-src-ordcolfor"></div>
     
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Modifica non conformità fornitore</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="rcd-change-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endchangefor" />
            <input id="NCPROG_" type="hidden" name="NCPROG_" value="$NCPROG" />
            <input type="hidden" name="NCUORG" value="$NCUORG" />
            <input type="hidden" name="NCRIFE" value="$NCRIFE" />
            <input type="hidden" name="NCQTOP" id="NCQTOP" value="$NCQTOP" />  

 
			  <div id="navtop">
			    <input type="button" onclick="sbm_change();" class="btn btn-primary accept" value="Salva" />
			    <input type="button" class="btn btn-default cancel" onclick="$.blockUI();" value="Torna alla lista" />
			  </div>
			  
               <div class="row" style="
SEGDTA;
 echo (($NCRIFE=="" || $NCRIFE==0)?('display:none;'):('')); 
		echo <<<SEGDTA
"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3">
	                <label for="rifeNcCli">Rif. Non conformità cliente:</label>
	                <div class="input-group">
	                  <input type="text" id="rifeNcCli" class="form-control" name="rifeNcCli" size="10" maxlength="10" value="$NCRIFE" readonly>
	                  <span id="rifeNcCli-btn" title="Vai a non conformita cliente" class="input-group-addon glyphicon glyphicon-search" onclick="document.location.href='?task=beginchangecli&NCPROG=$NCRIFE'"></span>
	                </div>
	              </div>
              </div>
			    
			   
               <div class="row"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDTEM_lbl">
	                <label for="addNCDTEM">Data emissione</label>
	                <div>
	                  <input type="text" id="addNCDTEM" class="form-control" name="NCDTEM" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCDTEM); 
		echo <<<SEGDTA
" readonly>
	                  <span class="help-block" id="addNCDTEM_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDTEM_lbl">
	                <label for="addNCPROG">Numero non conformit&agrave;</label>
	                <div>
	                  <input type="text" id="addNCPROG" class="form-control" name="NCPROG_dsp" size="11" maxlength="11" value="$NCPROG" readonly>
	                </div>
	              </div>
              </div>
              
              <div class="row"> 
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRANC_lbl">
	                <label for="addNCRANC">Rapporto NC</label>
	                <div>
	                  <input type="text" id="addNCRANC" class="form-control" name="NCRANC" size="100" maxlength="100" value="$NCRANC">
	                  <span class="help-block" id="addNCRANC_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCCATE_lbl">
	                <label for="addNCCATE">Categoria</label>
	                <div>
	                  <select class="form-control" name="NCCATE" id="addNCCATE">
	                  	<option value=""></option>
	                  	<option value="NC" 
SEGDTA;
 echo (($NCCATE=="NC")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>NC</option>
	                  	<option value="SEGNALAZIONE" 
SEGDTA;
 echo (($NCCATE=="SEGNALAZIONE")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Segnalazione</option>
	                  </select> 
	                  <span class="help-block" id="addNCCATE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCAB8_lbl">
	                <label for="addNCAB8">Cod. Fornitore</label>
	                <div>
	                  <input type="text" id="addNCAB8" class="form-control" name="NCAB8" size="8" maxlength="8" value="$NCAB8" readonly>
	                  <span class="help-block" id="addNCAB8_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRGCF_lbl">
	                <label for="addNCRGCF">Ragione Sociale</label>
	                <div>
	                  <input type="text" id="addNCRGCF" class="form-control" name="NCRGCF" size="40" maxlength="40" value="$NCRGCF" readonly>
	                  <span class="help-block" id="addNCRGCF_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">   
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDCTO_lbl">
	                <label for="addNCDCTO">Tipo ordine</label>
	                <div>
	                  <input type="text" id="addNCDCTO" class="form-control" name="NCDCTO" size="2" maxlength="2" value="$NCDCTO" readonly>
	                  <span class="help-block" id="addNCDCTO_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDOCO_lbl">
	                <label for="addNCDOCO">Num. ordine</label>
	                <div>
	                  <input type="text" id="addNCDOCO" class="form-control" name="NCDOCO" size="8" maxlength="8" value="$NCDOCO" readonly>
	                  <span class="help-block" id="addNCDOCO_err"></span>
	                </div>
	              </div>
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLNID_lbl">
	                <label for="addNCLNID">Num. linea</label>
	                <div>
	                  <input type="text" id="addNCLNID" class="form-control" name="NCLNID" size="6" maxlength="6" value="$NCLNID" readonly>
	                  <span class="help-block" id="addNCLNID_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLITM_lbl">
	                <label for="addNCLITM">Cod. articolo</label>
	                <div>
	                  <input type="text" id="addNCLITM" class="form-control" name="NCLITM" size="25" maxlength="25" value="$NCLITM" readonly>
	                  <span class="help-block" id="addNCLITM_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCDSC1_lbl">
	                <label for="addNCDSC1">Des. articolo </label>
	                <div>
	                  <input type="text" id="addNCDSC1" class="form-control" name="NCDSC1" size="25" maxlength="25" value="$NCDSC1" readonly>
	                  <span class="help-block" id="addNCDSC1_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCLOTN_lbl">
	                <label for="addNCLOTN">Lotto</label>
	                <div>
	                  <input type="text" id="addNCLOTN" class="form-control" name="NCLOTN" size="30" maxlength="30" value="$NCLOTN" readonly>
	                  <span class="help-block" id="addNCLOTN_err"></span>
	                </div>
	              </div> 
	          </div>
 	          
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCQTSP_lbl">
	                <label for="addNCQTSP">Quantit&agrave; spedita</label>
	                <div>
	                  <input type="text" id="addNCQTSP" class="form-control fld-calc-percentuale" name="NCQTSP" size="30" maxlength="30" value="$NCQTSP" readonly>
	                  <span class="help-block" id="addNCQTSP_err"></span>
	                </div>
	              </div> 
	          </div>
	          
	          <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCPZCO_lbl">
	                <label for="addNCPZCO">Pezzi contestati</label>
	                <div>
	                  <input type="text" id="addNCPZCO" class="positive-integer form-control fld-calc-percentuale" name="NCPZCO" size="15" maxlength="15" value="$NCPZCO">
	                  <span class="help-block" id="addNCPZCO_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCPPCO_lbl">
	                <label for="addNCPPCO">% Pezzi contestati</label>
	                <div>
	                  <input type="text" id="addNCPPCO" class="form-control" name="NCPPCO" size="6" maxlength="7" value="$NCPPCO" readonly>
	                  <span class="help-block" id="addNCPPCO_err"></span>
	                </div>
	              </div>
	          </div>
 
	          <div class="row">    
	              <div class="form-group col-xs-12 col-sm-12 col-lg-6" id="addNCDIFE_lbl">
	                <label for="addNCDIFE">Difetto</label>
	                <div>
	                  <!--<input type="text" id="addNCDIFE" class="form-control" name="NCDIFE" size="35" maxlength="35" value="$NCDIFE">-->
	                  
SEGDTA;
 $this->lstDifetti($NCDIFE); 
		echo <<<SEGDTA

	                  <span class="help-block" id="addNCDIFE_err"></span>
	                </div>
	              </div>
              </div>
              
              <div class="row">
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3">
	                <label for="addNCRICL">&nbsp;</label>
					<div class="checkbox">
						<label>
						  <input type="checkbox" id="addNCRICL" name="NCRICL" class="checkbox style-0" value="S" 
SEGDTA;
 echo (($NCRICL=="S")?('checked="checked"'):('')); 
		echo <<<SEGDTA
>
						  <span>Risposta fornitore</span>
						</label>
					</div> 
	              </div> 
	          </div>    
	         
              <div class="row">   

	              <div class="form-group col-xs-12 col-sm-6 col-lg-2" id="addNCRSQT_lbl">
	                <label for="addNCRSQT">Q.t&agrave; Reso</label>
	                <div>
	                  <input type="text" id="addNCRSQT" class="form-control positive-integer" name="NCRSQT" size="9" maxlength="9" value="$NCRSQT">
	                  <span class="help-block" id="addNCRSQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCRSDT_lbl">
	                <label for="addNCRSDT">Data Reso</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCRSDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCRSDT" class="calendario form-control" name="NCRSDT" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCRSDT); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCRSDT_err"></span>
	                </div>
	              </div> 
	          </div>
	          
			  <div class="row">     

	              <div class="form-group col-xs-12 col-sm-6 col-lg-2" id="addNCSEQT_lbl">
	                <label for="addNCSEQT">Q.t&agrave; sostituzione effettuata</label>
	                <div>
	                  <input type="text" id="addNCSEQT" class="form-control positive-integer" name="NCSEQT" size="9" maxlength="9" value="$NCSEQT">
	                  <span class="help-block" id="addNCSEQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCSEDT_lbl">
	                <label for="addNCSEDT">Data sostituzione effettuata</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCSEDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCSEDT" class="calendario form-control" name="NCSEDT" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCSEDT); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCSEDT_err"></span>
	                </div>
	              </div>  
	          </div>
	          
	          <div class="row">      
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCQTSE_lbl">
	                <label for="addNCQTSE">Q.t&agrave; selezionata/sistemata</label>
	                <div>
	                  <input type="text" id="addNCQTSE" class="form-control positive-integer" name="NCQTSE" size="9" maxlength="9" value="$NCQTSE">
	                  <span class="help-block" id="addNCQTSE_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCQTRI_lbl">
	                <label for="addNCQTRI">Q.t&agrave; rispedita</label>
	                <div>
	                  <input type="text" id="addNCQTRI" class="form-control positive-integer" name="NCQTRI" size="10" maxlength="10" value="$NCQTRI">
	                  <span class="help-block" id="addNCQTRI_err"></span>
	                </div>
	              </div>          
	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCDTSP_lbl">
	                <label for="addNCDTSP">Data spedizione</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCDTSP').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTSP" class="form-control calendario" name="NCDTSP" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCDTSP); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCDTSP_err"></span>
	                </div>
	              </div>    
	          </div>
	          
	          <div class="row">       

	              <div class="form-group col-xs-12 col-sm-6 col-lg-2" id="addNCAEQT_lbl">
	                <label for="addNCAEQT">Q.t&agrave; accredito effettuato</label>
	                <div>
	                  <input type="text" id="addNCAEQT" class="form-control positive-integer" name="NCAEQT" size="9" maxlength="9" value="$NCAEQT">
	                  <span class="help-block" id="addNCAEQT_err"></span>
	                </div>
	              </div>
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCAEDT_lbl">
	                <label for="addNCAEDT">Data accredito effettuato</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCAEDT').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCAEDT" class="calendario form-control" name="NCAEDT" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCAEDT); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCAEDT_err"></span>
	                </div>
	              </div> 
              </div>
              
              <div class="row">

	              <div class="form-group col-xs-12 col-sm-4 col-lg-2" id="addNCDTCH_lbl">
	                <label for="addNCDTCH">Data chiusura</label>
					<div class="input-group">
						<span class="input-group-addon" onclick="$('#addNCDTCH').datepicker('show');"><i class="glyphicon glyphicon-calendar"></i></span>
	                  	<input type="text" id="addNCDTCH" class="calendario form-control" name="NCDTCH" size="10" maxlength="10" value="
SEGDTA;
 echo $this->cvtDateFromDb($NCDTCH); 
		echo <<<SEGDTA
">
	                  	<span class="help-block" id="addNCDTCH_err"></span>
	                </div>
	              </div>
	          </div>     
	           
	          <div class="row">    
	              <div class="form-group col-xs-12 col-sm-6 col-lg-3" id="addNCNOTE_lbl">
	                <label for="addNCNOTE">Note</label>
	                <div>
	                  <textarea id="addNCNOTE" class="form-control" name="NCNOTE" maxlength="2048" rows="7">$NCNOTE</textarea>
	                  <span class="help-block" id="addNCNOTE_err"></span>
	                </div>
	              </div>
	          </div>   
	           
	          <div class="row">     
	           <fieldset>
	            <div class="form-group col-xs-12 col-sm-12 col-lg-6">
	           	<legend>Altri ordini collegati <span class="pull-right"><input type="button" class="btn btn-xs btn-primary" value="Aggiungi ordine" onclick="srcOrdColFor();"> </span></legend>
		           	<div id="div-lst-ordleg">
SEGDTA;
 $this->lstOrdLeg("D",$NCPROG); 
		echo <<<SEGDTA
</div>
	           	</div>
	           </fieldset>
	          </div>  
	           
	          <div class="row">     
	           <fieldset>
	            <div class="form-group col-xs-12 col-sm-12 col-lg-6">
	           	<legend>Allegati <span class="pull-right"><input type="button" class="btn btn-xs btn-primary" value="Aggiungi allegato" onclick="addAlleg();"> </span></legend>
		           	<div id="div-lst-alleg">
SEGDTA;
 $this->lstAllegati("D",$NCPROG); 
		echo <<<SEGDTA
</div>
	           	</div>
	           </fieldset>
	          </div>  
	            
            <div id="navbottom">
              <input type="button" onclick="sbm_change()" class="btn btn-primary accept" value="Salva" />
              <input type="button" class="btn btn-default cancel" onclick="$.blockUI();" value="Torna alla lista" />
            </div>		
          </form>
        </div>
      </div>
    </div>
    
    <script src="/crud/websmart/v13.2/js/jquery.maskedinput.min.js"></script>  
    <script src="/crud/websmart/v13.2/js/jquery.numeric.js"></script>   
    <script src="/crud/websmart/v13.2/js/jquery.form_2.64.js"></script>    
    <script src="/crud/websmart/v13.2/js/jquery.blockui.js"></script>     
    <script type="text/javascript">
		jQuery(function() {
			// Focus the first input on page load
			jQuery("input:enabled:first").focus();
			
			// Bind event to the Back button
			jQuery(".cancel").click(goback);
			function goback()
			{
				window.location.replace("$redirPgm");
				return false;
			}
			
			$('.calendario').datepicker({
				dateFormat: 'dd-mm-yy',
				buttonImageOnly: false,
				changeMonth: true,   
				changeYear: true 
			}); 			
			$(".calendario").mask("99-99-9999",{placeholder:" "});
			
            $('.positive-integer').numeric({
	                decimal: false, 
	                negative: false
            });			
            $(".fld-calc-percentuale").change(function(e){
            	jaddNCPZCO = $("#addNCPZCO").val();
            	jaddNCQTSP = $("#addNCQTSP").val();
            	 
            	if(jaddNCQTSP!='' && jaddNCQTSP != 0) {
            		jaddNCPPCO = (jaddNCPZCO /  jaddNCQTSP) * 100;	
            		$("#addNCPPCO").val(jaddNCPPCO.toFixed(2));
            	}
            });
              
			$("#div-add-alleg").dialog({
				autoOpen: false,
				modal: true,
				width: 700, 
				resizable: false,
				title: 'Allegato'
			});	
			
			$("#div-add-ordleg").dialog({
				autoOpen: false,
				modal: true,
				width: 700, 
				resizable: false,
				title: 'Ordini legati'
			});	
              
			$("#div-src-ordcolfor").dialog({
				autoOpen: false,
				modal: true,
				width: 900, 
				resizable: false,
				title: 'Ordini'
			});	
              
			var options = { 
				dataType:  'json',
				success: showResponseChange 
			}; 
			$('#rcd-change-form').ajaxForm(options); 
              
		});
		
		function sbm_change() {
			$.blockUI();
			$("#rcd-change-form").submit();
		}
		
		function sbm_alleg() {
			$.blockUI();
			$("#alleg-add-form").submit();
		}		
		
		function showResponseChange(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");

			if(respobj[0].stat!="OK") {
				$.unblockUI();
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {		
				 document.location.href="$redirPgm?page=1";
			}  
		} 
		
		function addAlleg() {
			url = "?task=addAlleg&tipoIns=D&TMPROG=$NCPROG";
			$("#div-add-alleg").load(url,function(data){
				var options = { 
					dataType:  'json',
					success: showResponseAddAlleg  
				}; 
				$('#alleg-add-form').ajaxForm(options); 	
			}).dialog('open');
		}
		
		function showResponseAddAlleg(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");

			$.unblockUI();
			if(respobj[0].stat!="OK") {
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {	
				$("#div-add-alleg").dialog("close");	
				lstAlleg();
			}  
		} 		
		
		function lstAlleg() {
			url = "?task=flstAlleg&tipoIns=D&TMPROG=$NCPROG";
			$("#div-lst-alleg").load(url);
		}
		
		function dltAlleg(jNAPROG) {
			if(!confirm("Eliminare questo allegato?")) return false;
			
			url = "?task=dltAlleg&tipoIns=D&NAPROG="+jNAPROG;
			$.get(url,function(data){
				lstAlleg();	
			});
		}
		
		function dspAlleg(jNAPROG) { 
			url = "?task=dspAlleg&tipoIns=D&NAPROG="+jNAPROG;
			document.location.href = url;
		}			

		/**/
		function srcOrdColFor() { 
			url = "?task=fltOrdColFor&tipoIns=D&TMPROG=$NCPROG&filt_NCAB8_occ="+encodeURIComponent("$NCAB8")+"&filt_NCLOTN_occ="+encodeURIComponent("$NCLOTN");
			$("#div-src-ordcolfor").load(url,function(data){
					
			}).dialog('open');
		}
	 
		function fltOrdColFor() {
			formData = $("#ordcolfor-filter-form").serialize();
			url = "nonconf02.php?"+formData;	
			$("#div-src-ordcolfor").load(url,function(data){
					
			});			
		}
		
		function addOrdColFor(jRRN_OC) { 
			url = "?task=addOrdColFor&tipoIns=D&TMPROG=$NCPROG&RRNF=0&RRN_OC="+jRRN_OC;
			$.get(url,function(data){
				alert("Ordine aggiunto");
				lstOrdLeg();
				srcOrdColFor();	
				calcNCQTSP();
			});			
		}
		
		/*
		function addOrdLeg() {
			url = "?task=addOrdLeg&tipoIns=D&TMPROG=$NCPROG&tipoDato=FOR&RRNF=$RRNF";
			$("#div-add-ordleg").load(url,function(data){
				
	            $('.positive-integer-dialog').numeric({
	                decimal: false, 
	                negative: false
	            });	
				
				var options = { 
					dataType:  'json',
					success: showResponseAddOrdLeg  
				}; 
				$('#ordleg-add-form').ajaxForm(options); 	
			}).dialog('open');
		}
		
		function showResponseAddOrdLeg(respobj, statusText, xhr, _form)  { 
			 
			$(".help-block").html("");
			$(".has-error").removeClass("has-error");
			
			$.unblockUI();
			if(respobj[0].stat!="OK") {
				for(ie=0;ie<respobj.length;ie++) {
					$("#"+$.trim(respobj[ie].id)+"_lbl").addClass("has-error");
					$("#"+$.trim(respobj[ie].id)+"_err").html(respobj[ie].msg);
				}
			}	    
			else {	
				$("#div-add-ordleg").dialog("close");	
				lstOrdLeg();
			}  
		} 		
		
		function sbm_ordLeg() {
			$.blockUI();
			$("#ordleg-add-form").submit();
		}	
		*/		
		
		function lstOrdLeg() {
			$.blockUI();
			url = "?task=flstOrdLeg&tipoIns=D&TMPROG=$NCPROG";
			$("#div-lst-ordleg").load(url,function(data){
				$.unblockUI();	
			});
		}
		
		function dltOrdLeg(jOLPROG) {
			if(!confirm("Eliminare questo ordine legato?")) return false;
			
			url = "?task=dltOrdLeg&tipoIns=D&OLPROG="+jOLPROG;
			$.get(url,function(data){
				lstOrdLeg();
				calcNCQTSP();	
			});
		}
		  
		function calcNCQTSP() {
			jNCQTOP = $("#NCQTOP").val();
			url = "?task=fcalcNCQTSP&tipoIns=D&TMPROG=$NCPROG&NCQTOP="+jNCQTOP;
			$.getJSON(url,function(data){
				$("#addNCPZCO").val(data.NCQTSP);
				$("#addNCQTSP").val(data.NCQTSP).change();
				
			});
		}	
		
	</script>
  </body>
</html>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "headncfor")
	{

		echo <<<SEGDTA
<table class="table table-condensed table-bordered" style="width:auto;">
<tr>
	<th>Data emissione</th>
	<th>Tipo</th>
	<th>Pezzi contestati</th>
	<th>% Pezzi contestati</th>
	<th>Difetto</th>
	<th></th>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dettncfor")
	{

		echo <<<SEGDTA
	<tr>
		<td>
SEGDTA;
 echo $this->cvtDateFromDb($NCDTEM); 
		echo <<<SEGDTA
</td>
		<td>
		
SEGDTA;
 echo (($NCTPCF=="C")?("Cliente"):("Fornitore")); 
		echo <<<SEGDTA

		</td>
		<td class="text num">$NCPZCO</td>
		<td class="text num">$NCPPCO</td> 
		<td>
SEGDTA;
 echo $this->decodDifetto($NCDIFE); 
		echo <<<SEGDTA
</td>
		<td>
     		 <a class="btn btn-primary btn-xs glyphicon glyphicon-pencil" title="Change this record" href="$pf_scriptname?task=beginchangefor&amp;NCPROG=$NCPROG"></a> 
      		 <a class="btn btn-danger btn-xs glyphicon glyphicon-remove" title="Delete this record" href="javascript:void('0');" onclick="dltNonConfFor('$RRNF','$NCPROG');"></a>
		</td>
	</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "halleg")
	{

		echo <<<SEGDTA

<table class="table table-condensed">
<thead>
	<tr>
		<th></th>
		<th>File</th>
	</tr>
</thead>
<tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dalleg")
	{

		echo <<<SEGDTA
<tr>
	<td>
      <a class="btn btn-default btn-xs glyphicon glyphicon-remove" title="Elimina allegato" href="javascript:void('0');" onclick="dltAlleg('$NAPROG');"></a>
	  <a class="btn btn-default btn-xs glyphicon glyphicon-file" title="Visualizza allegato" href="javascript:void('0');" onclick="dspAlleg('$NAPROG');"></a>
	</td>
	<td>$NAFILN</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "falleg")
	{

		echo <<<SEGDTA
</tbody>
</table>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "formalleg")
	{

		echo <<<SEGDTA
<form id="alleg-add-form" action="$pf_scriptname" method="post" enctype="multipart/form-data">
<input type="hidden" name="task" value="addAlleg1" />
<input type="hidden" name="tipoIns" value="$tipoIns" />
<input type="hidden" name="TMPROG" value="$TMPROG" />  
   
	<div class="row"> 
	  <div class="form-group col-xs-12 col-sm-12 col-lg-12" id="allegato_lbl">
	    <label for="allegato">File</label>
	    <div>
	      <input type="file" id="allegato" class="form-control" name="allegato">
	      <span class="help-block" id="allegato_err"></span>
	    </div>
	  </div>
	</div>
  
	<div id="navbottom">
	  <input type="button" onclick="sbm_alleg();" class="btn btn-primary accept" value="Salva" />
	  <input type="button" class="btn btn-default cancel" value="Chiudi" onclick="$('#div-add-alleg').dialog('close');" />
	</div>
</form>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "hordfor")
	{

		echo <<<SEGDTA
<form id="ordfor-filter-form" action="$pf_scriptname" method="post">
<input type="hidden" name="task" value="fltOrdFor" />
   
	<div class="row"> 
	  <div class="form-group col-xs-3">
	    <label for="filt_NCFORN_o">Fornitore</label>
	    <div>
	      <input type="text" id="filt_NCFORN_o" class="form-control" name="filt_NCFORN_o" value="$filt_NCFORN_o"> 
	    </div>
	  </div>
	  <div class="form-group col-xs-4">
	    <label for="filt_NCLITM_o">Articolo</label>
	    <div>
	      <input type="text" id="filt_NCLITM_o" class="form-control" name="filt_NCLITM_o" value="$filt_NCLITM_o"> 
	    </div>
	  </div>	 
	  <div class="form-group col-xs-3">
	    <label for="filt_NCLOTN_o">Lotto</label>
	    <div>
	      <input type="text" id="filt_NCLOTN_o" class="form-control" name="filt_NCLOTN_o" value="$filt_NCLOTN_o"> 
	    </div>
	  </div>
	  <div class="form-group col-xs-2">
	    <label>&nbsp;</label>
	    <div>
	      <input type="button" onclick="fltOrdFor();" class="btn btn-xs btn-primary" value="Cerca" />
	    </div>
	  </div>
	  	   
	</div>
  
	
</form>

<div style="max-height:500px;overflow-y:auto;">
<table class="table table-condensed table-striped">
<thead>
	<tr>
		<th></th>
		<th>Fornitore</th>
		<th>Tipo Ordine</th> 
		<th>Num.Ordine</th> 
		<th>Linea</th>
		<th>Lotto</th> 
	</tr>
</thead> 
<tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dordfor")
	{

		echo <<<SEGDTA
<tr>
	<td>
      <a class="btn btn-default btn-xs glyphicon glyphicon-check" title="Change this record" href="javascript:void('0');" onclick="selOrdFor('$RRNF','$PDDCTO','$PDDOCO');"></a> 
	</td>
	<td>$PDAN8</td>
	<td>$PDDCTO</td>
	<td>$PDDOCO</td>
	<td>$PDLNID</td>
	<td>$PRLOTN</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "fordfor")
	{

		echo <<<SEGDTA
</tbody>
</table>
</div>
<br>
<input type="button" class="btn btn-default cancel" value="Chiudi" onclick="$('#div-src-ordfor').dialog('close');" />
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "norecords")
	{

		echo <<<SEGDTA
<tr>
	<td colspan="$nrColspan">Nessun record trovato</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "nodetails")
	{

		echo <<<SEGDTA
<div class="alert alert-info fade in">  
	$noDetailsMsg
</div>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "formordleg")
	{

		echo <<<SEGDTA
<form id="ordleg-add-form" action="$pf_scriptname" method="post">
<input type="hidden" name="task" value="addOrdLeg1" />
<input type="hidden" name="tipoIns" value="$tipoIns" />
<input type="hidden" name="TMPROG" value="$TMPROG" />  
<input type="hidden" name="tipoDato" value="$tipoDato" />  
<input type="hidden" name="RRNF" value="$RRNF" />  
   	            
	<div class="row"> 
	  <div class="form-group col-xs-12 col-sm-12 col-lg-12" id="OLDCTO_lbl">
	    <label for="OLDCTO">Tipo ordine</label>
	    <div>
	      <input type="text" id="OLDCTO" class="form-control" name="OLDCTO" maxlength="2">
	      <span class="help-block" id="OLDCTO_err"></span>
	    </div>
	  </div>
	</div>
	
	<div class="row"> 
	  <div class="form-group col-xs-12 col-sm-12 col-lg-12" id="OLDOCO_lbl">
	    <label for="OLDOCO">Num. ordine</label>
	    <div>
	      <input type="text" id="OLDOCO" class="form-control positive-integer-dialog" name="OLDOCO" maxlength="8">
	      <span class="help-block" id="OLDOCO_err"></span>
	    </div>
	  </div>
	</div>
  
	<div id="navbottom">
	  <input type="button" onclick="sbm_ordLeg();" class="btn btn-primary accept" value="Salva" />
	  <input type="button" class="btn btn-default cancel" value="Chiudi" onclick="$('#div-add-ordleg').dialog('close');" />
	</div>
</form>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "hordleg")
	{

		echo <<<SEGDTA

<table class="table table-condensed">
<thead>
	<tr>
		<th></th>
		<th>Tipo ordine</th>
		<th>Num. ordine</th>
		<th>Linea</th>
		<th>Lotto</th>
		<th>Qta Spedita</th>
	</tr>
</thead>
<tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dordleg")
	{

		echo <<<SEGDTA
<tr>
	<td>
      <a class="btn btn-default btn-xs glyphicon glyphicon-remove" title="Elimina ordine legato" href="javascript:void('0');" onclick="dltOrdLeg('$OLPROG');"></a>
 	</td>
	<td>$OLDCTO</td>
	<td>$OLDOCO</td>
	<td>$OLLNID</td>
	<td>$OLLOTN</td>
	<td class="text num">
SEGDTA;
 echo number_format($OLQTSP,0,"","."); 
		echo <<<SEGDTA
</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "fordleg")
	{

		echo <<<SEGDTA
</tbody>
</table>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "hordcolcli")
	{

		echo <<<SEGDTA
<form id="ordcolcli-filter-form" action="$pf_scriptname" method="post">
<input type="hidden" name="task" value="fltOrdColCli" />
<input type="hidden" name="TMPROG" value="$TMPROG" />
<input type="hidden" name="tipoIns" value="$tipoIns" />
<input type="hidden" name="RRNF" value="$RRNF" />
<input type="hidden" name="TIPOFILE" value="$TIPOFILE" />

   
	<div class="row"> 
	  <div class="form-group col-xs-3">
	    <label for="filt_NCAB8_occ">Fornitore</label>
	    <div>
	      <input type="text" id="filt_NCAB8_occ" class="form-control" name="filt_NCAB8_occ" value="$filt_NCAB8_occ"> 
	    </div>
	  </div> 	 
	  <div class="form-group col-xs-3">
	    <label for="filt_NCLOTN_occ">Lotto</label>
	    <div>
	      <input type="text" id="filt_NCLOTN_occ" class="form-control" name="filt_NCLOTN_occ" value="$filt_NCLOTN_occ"> 
	    </div>
	  </div>
	  <div class="form-group col-xs-2">
	    <label>&nbsp;</label>
	    <div>
	      <input type="button" onclick="fltOrdColCli();" class="btn btn-xs btn-primary" value="Cerca" />
	    </div>
	  </div>
	  	   
	</div>
  
	
</form>

<div style="max-height:500px;overflow-y:auto;">
<table class="table table-condensed table-striped">
<thead>
	<tr>
		<th></th>
		<th>Cliente</th>
		<th>Tipo Ordine</th> 
		<th>Num.Ordine</th> 
		<th>Linea</th>
		<th>Lotto</th> 
		<th>Qta spedita</th>
	</tr>
</thead> 
<tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dordcolcli")
	{

		echo <<<SEGDTA
<tr>
	<td>
      <a style="
SEGDTA;
 echo (($hasCol)?('display:none'):('')); 
		echo <<<SEGDTA
" class="btn btn-default btn-xs glyphicon glyphicon-check" title="Change this record" href="javascript:void('0');" onclick="addOrdColCli('$TIPOFILE','$RRNF')"></a> 
	</td>
	<td>$SDAN8</td>
	<td>$SDDCTO</td>
	<td>$SDDOCO</td>
	<td>$SDLNID</td>
	<td>$SDLOTN</td>
	<td class="text num">
SEGDTA;
 echo number_format($SDUORG,0,"","."); 
		echo <<<SEGDTA
</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "fordcolcli")
	{

		echo <<<SEGDTA
</tbody>
</table>
</div>
<br>
<input type="button" class="btn btn-default cancel" value="Chiudi" onclick="$('#div-src-ordcolcli').dialog('close');" />
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "hordcolfor")
	{

		echo <<<SEGDTA
<form id="ordcolfor-filter-form" action="$pf_scriptname" method="post">
<input type="hidden" name="task" value="fltOrdColFor" />
<input type="hidden" name="TMPROG" value="$TMPROG" />
<input type="hidden" name="tipoIns" value="$tipoIns" />
<input type="hidden" name="RRNF" value="$RRNF" />

   
	<div class="row"> 
	  <div class="form-group col-xs-3">
	    <label for="filt_NCAB8_occ">Fornitore</label>
	    <div>
	      <input type="text" id="filt_NCAB8_occ" class="form-control" name="filt_NCAB8_occ" value="$filt_NCAB8_occ"> 
	    </div>
	  </div> 	 
	  <div class="form-group col-xs-3">
	    <label for="filt_NCLOTN_occ">Lotto</label>
	    <div>
	      <input type="text" id="filt_NCLOTN_occ" class="form-control" name="filt_NCLOTN_occ" value="$filt_NCLOTN_occ"> 
	    </div>
	  </div>
	  <div class="form-group col-xs-2">
	    <label>&nbsp;</label>
	    <div>
	      <input type="button" onclick="fltOrdColFor();" class="btn btn-xs btn-primary" value="Cerca" />
	    </div>
	  </div>
	  	   
	</div>
  
	
</form>

<div style="max-height:500px;overflow-y:auto;">
<table class="table table-condensed table-striped">
<thead>
	<tr>
		<th></th>
		<th>Fornitore</th>
		<th>Tipo Ordine</th> 
		<th>Num.Ordine</th> 
		<th>Linea</th>
		<th>Lotto</th> 
		<th>Qta ricevuta</th>
	</tr>
</thead> 
<tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "dordcolfor")
	{

		echo <<<SEGDTA
<tr>
	<td>
      <a style="
SEGDTA;
 echo (($hasCol)?('display:none'):('')); 
		echo <<<SEGDTA
" class="btn btn-default btn-xs glyphicon glyphicon-check" title="Change this record" href="javascript:void('0');" onclick="addOrdColFor('$RRNF')"></a> 
	</td>
	<td>$PDAN8</td>
	<td>$PDDCTO</td>
	<td>$PDDOCO</td>
	<td>$PDLNID</td>
	<td>$PRLOTN</td>
	<td class="text num">
SEGDTA;
 echo number_format($PRUORG,0,"","."); 
		echo <<<SEGDTA
</td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "fordcolfor")
	{

		echo <<<SEGDTA
</tbody>
</table>
</div>
<br>
<input type="button" class="btn btn-default cancel" value="Chiudi" onclick="$('#div-src-ordcolfor').dialog('close');" />
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
		
		$this->pf_liblLibs[1] = 'BCD_DATIV2';
		
		parent::__construct();

		$this->pf_scriptname = 'nonconf02.php';
		$this->pf_wcm_set = 'PRODUZIONE';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		
		// Last Generated CRC: FFC19633 334BC5FA 4060D2DF 9E6498E0
		// Last Generated Date: 2024-06-25 10:24:32
		// Path: nonconf02.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'nonconf02');?>