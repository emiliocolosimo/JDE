<?php
if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

//	Program Name:		nonconf01.php
//	Program Title:		Non conformit&agrave;  - lista ordini
//	Created by:			matti
//	Template family:	Responsive
//	Template name:		Page at a Time.tpl
//	Purpose:        	Maintain a database file using embedded SQL. Supports options for add, change, delete and display.
//	Program Modifications:

set_time_limit(120);

require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');
require_once('websmart/v13.2/include/en-US/WSRequiredValidator.php');
require_once('websmart/v13.2/include/en-US/WSNumericValidator.php');

class nonconf01 extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'sortDirF' => '',
		'sortF' => '',		
		'page' => 1,
		'listSize' => 20,
		'filters' => array('tipo' => '', 'SDAN8' => '', 'ABALPH' => '', 'SDDOCO' => '', 'SDLOTN' => '')
	);
	
	
	protected $keyFields = array('SDKCOO', 'SDDOCO');
	protected $uniqueFields = array('SDKCOO', 'SDDOCO');

	protected $keyFieldsF = array('PDKCOO', 'PDDOCO');
	protected $uniqueFieldsF = array('PDKCOO', 'PDDOCO');
	
	public function runMain()
	{
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
			"SDDCTO" => array("validators"=> array("WSRequired")),
			"SDDOCO" => array("validators"=> array("WSRequired","WSNumeric")),
			"SDLNID" => array("validators"=> array("WSRequired","WSNumeric")),
			"SDPSN" => array("validators"=> array("WSRequired","WSNumeric")),
			"SDDELN" => array("validators"=> array("WSRequired","WSNumeric")),
			"SDDOC" => array("validators"=> array("WSRequired","WSNumeric")),
			"SDLITM" => array("validators"=> array("WSRequired")),
			"SDDSC1" => array("validators"=> array("WSRequired")),
			"SDLOTN" => array("validators"=> array("WSRequired")),
			"SDIVD" => array("validators"=> array("WSRequired","WSNumeric")));
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
			case 'beginadd': 
			$this->beginAdd();
			break;
			
			// Complete record add
			case 'endadd':
			$this->endAdd();
			break;
			
			// Start the change process
			case 'beginchange':
			$this->beginChange();
			break;
			
			// Complete the change process
			case 'endchange':
			$this->endChange();
			break;
			
			// Output a filtered list
			case 'filter':
			$this->filterList();
			break;
			
			case 'dltNonConf':
			$this->dltNonConf();
			break;
		}
	}
	
	protected function dltNonConf() 
	{
		$NCPROG = (int) xl_get_parameter("NCPROG");
		 
		// Prepare and execute the SQL statement to delete the record
		$delString = 'DELETE FROM BCD_DATIV2.NONCON0F WHERE NCPROG = '.$NCPROG.' WITH NC';
	 	$result = $this->db_connection->exec($delString); 		
 		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		 echo '{"stat":"OK"}';
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
		$keyFieldArray['SDDOCO'] = (int) $keyFieldArray['SDDOCO'];
		
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
		$SDKCOO_url = urlencode(rtrim($row['SDKCOO']));
		$SDDOCO_url = urlencode(rtrim($row['SDDOCO']));
		
		// Sanitize the fields
		foreach(array_keys($row) as $key)
		{
			$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1");
			
			
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
		$keyFieldArray['SDDOCO'] = (int) $keyFieldArray['SDDOCO'];
		
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
		$delString = 'DELETE FROM JRGDTA94T.F4211 ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($delString);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the key parameters
		$stmt->bindValue(':SDKCOO_', $keyFieldArray['SDKCOO'], PDO::PARAM_STR);
		$stmt->bindValue(':SDDOCO_', (int) $keyFieldArray['SDDOCO'], PDO::PARAM_INT);
		
		// Execute the delete statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
		header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
	}
	
	// Show the add page
	protected function beginAdd()
	{
		$SDDCTO = "";
		$SDDOCO = "";
		$SDLNID = "";
		$SDPSN = "";
		$SDDELN = "";
		$SDDOC = "";
		$SDLITM = "";
		$SDDSC1 = "";
		$SDLOTN = "";
		$SDIVD = "";
		
		// Output the segment
		$this->writeSegment('RcdAdd', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	// Add the passed in data as a new row
	protected function endAdd()
	{
		// Get values from the page
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		$keyFieldArray['SDDOCO'] = (int) $keyFieldArray['SDDOCO'];
		extract($keyFieldArray);
		$SDDCTO = xl_get_parameter('SDDCTO');
		$SDLNID = xl_get_parameter('SDLNID');
		$SDPSN = xl_get_parameter('SDPSN');
		$SDDELN = xl_get_parameter('SDDELN');
		$SDDOC = xl_get_parameter('SDDOC');
		$SDLITM = xl_get_parameter('SDLITM');
		$SDDSC1 = xl_get_parameter('SDDSC1');
		$SDLOTN = xl_get_parameter('SDLOTN');
		$SDIVD = xl_get_parameter('SDIVD');
		
		
		// Do any add validation here
		$isValid = $this->validate();
		
		if(!$isValid)
		{
			
			$this->writeSegment('RcdAdd', array_merge(get_object_vars($this), get_defined_vars()));
			return;
		}
		
		// Prepare the statement to add the record
		$insertSql = 'INSERT INTO JRGDTA94T.F4211 (SDDCTO, SDLNID, SDPSN, SDDELN, SDDOC, SDLITM, SDDSC1, SDLOTN, SDIVD) VALUES(:SDDCTO, :SDLNID, :SDPSN, :SDDELN, :SDDOC, :SDLITM, :SDDSC1, :SDLOTN, :SDIVD)' . ' WITH NC';
		$stmt = $this->db_connection->prepare($insertSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':SDDCTO', $SDDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':SDLNID', $SDLNID, PDO::PARAM_INT);
		$stmt->bindValue(':SDPSN', $SDPSN, PDO::PARAM_INT);
		$stmt->bindValue(':SDDELN', $SDDELN, PDO::PARAM_INT);
		$stmt->bindValue(':SDDOC', $SDDOC, PDO::PARAM_INT);
		$stmt->bindValue(':SDLITM', $SDLITM, PDO::PARAM_STR);
		$stmt->bindValue(':SDDSC1', $SDDSC1, PDO::PARAM_STR);
		$stmt->bindValue(':SDLOTN', $SDLOTN, PDO::PARAM_STR);
		$stmt->bindValue(':SDIVD', $SDIVD, PDO::PARAM_INT);
		
		// Execute the insert statement
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
		header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
	}
	
	// Show the change page
	protected function beginChange()
	{
		// Fetch parameters which identify the record
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		$keyFieldArray['SDDOCO'] = (int) $keyFieldArray['SDDOCO'];
		
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
	protected function endChange()
	{
		// Fetch parameters which identify the record
		$oldKeyFieldArray = $this->getParameters(array('SDKCOO_', 'SDDOCO_'));
		$oldKeyFieldArray['SDDOCO_'] = (int) $oldKeyFieldArray['SDDOCO_'];
		
		extract($oldKeyFieldArray);
		// Get values from the page
		$SDDCTO = xl_get_parameter('SDDCTO');
		$SDLNID = xl_get_parameter('SDLNID');
		$SDPSN = xl_get_parameter('SDPSN');
		$SDDELN = xl_get_parameter('SDDELN');
		$SDDOC = xl_get_parameter('SDDOC');
		$SDLITM = xl_get_parameter('SDLITM');
		$SDDSC1 = xl_get_parameter('SDDSC1');
		$SDLOTN = xl_get_parameter('SDLOTN');
		$SDIVD = xl_get_parameter('SDIVD');
		
		//Protect Key Fields from being Changed
		$SDKCOO = $SDKCOO_;
		$SDDOCO = $SDDOCO_;
		
		// Do any validation
		$isValid = $this->validate(xl_FieldEscape($this->uniqueFields));
		
		if(!$isValid)
		{
			$record = $this->getRecord($oldKeyFieldArray, array_keys($this->formFields), true);
			extract($record);
			$this->writeSegment('RcdChange', array_merge(get_object_vars($this), get_defined_vars()));
			return;
		}
		
		// Construct and prepare the SQL to update the record
		$updateSql = 'UPDATE JRGDTA94T.F4211 SET SDDCTO = :SDDCTO, SDLNID = :SDLNID, SDPSN = :SDPSN, SDDELN = :SDDELN, SDDOC = :SDDOC, SDLITM = :SDLITM, SDDSC1 = :SDDSC1, SDLOTN = :SDLOTN, SDIVD = :SDIVD';
		$updateSql .= ' ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($updateSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':SDDCTO', $SDDCTO, PDO::PARAM_STR);
		$stmt->bindValue(':SDLNID', $SDLNID, PDO::PARAM_INT);
		$stmt->bindValue(':SDPSN', $SDPSN, PDO::PARAM_INT);
		$stmt->bindValue(':SDDELN', $SDDELN, PDO::PARAM_INT);
		$stmt->bindValue(':SDDOC', $SDDOC, PDO::PARAM_INT);
		$stmt->bindValue(':SDLITM', $SDLITM, PDO::PARAM_STR);
		$stmt->bindValue(':SDDSC1', $SDDSC1, PDO::PARAM_STR);
		$stmt->bindValue(':SDLOTN', $SDLOTN, PDO::PARAM_STR);
		$stmt->bindValue(':SDIVD', $SDIVD, PDO::PARAM_INT);
		$stmt->bindValue(':SDKCOO_', $SDKCOO_, PDO::PARAM_STR);
		$stmt->bindValue(':SDDOCO_', $SDDOCO_, PDO::PARAM_STR);
		
		// Execute the update statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
		header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
	}
	
	// Load list with filters
	protected function filterList()
	{
		// Retrieve the filter information
		
		$this->programState['filters']['tipo'] = xl_get_parameter('filter_tipo');
		$this->programState['filters']['SDAN8'] = trim(xl_get_parameter('filter_SDAN8'));
		$this->programState['filters']['ABALPH'] = xl_get_parameter('filter_ABALPH');
		$this->programState['filters']['SDDOCO'] = xl_get_parameter('filter_SDDOCO');
		$this->programState['filters']['SDLOTN'] = xl_get_parameter('filter_SDLOTN');
		
		// Update the program state
		$this->updateState();
		
		// Display the list
		$this->buildPage();
	}
	
	// Build current page of rows up to listsize.
	protected function buildPage()
	{
		 
		if($this->programState['filters']['tipo']=="C") $this->buildListCliente();
		else if($this->programState['filters']['tipo']=="F") $this->buildListFornitore(); 
		else {
			$previousPage = 0;
			$nextPage = 0;
			$rnd = rand(0,9999);
			$this->writeSegment('ListHeader', array_merge(get_object_vars($this), get_defined_vars()));
			$this->writeSegment('WarnFilter', array_merge(get_object_vars($this), get_defined_vars()));
			$this->writeSegment('ListFooter', array_merge(get_object_vars($this), get_defined_vars()));
		}
	}
	
	protected function buildListCliente() {
 
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
		 
		$this->writeSegment('ListHeader', array_merge(get_object_vars($this), get_defined_vars())); 
		$this->writeSegment('TabHeaderCliente', array_merge(get_object_vars($this), get_defined_vars())); 
		 
		// Create and execute the list Select statement
		$stmt = $this->buildListStmtCliente($rowOffset, $fetchLimit);
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
			$SDKCOO_url = urlencode(rtrim($row['SDKCOO']));
			$SDDOCO_url = urlencode(rtrim($row['SDDOCO']));
			
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1");
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			  
			$HASNONCONF = 0;
			$selString = "SELECT 'S' AS HASNONCONF 
			FROM BCD_DATIV2.NONCON0F 
			WHERE NCTPCF = 'C' 
			AND NCDCTO = :NCDCTO 
			AND NCDOCO = :NCDOCO 
			AND NCLNID = :NCLNID 
			AND NCLITM = :NCLITM 
			AND NCLOTN = :NCLOTN 
			FETCH FIRST ROW ONLY 
			";
			$stmtNC = $this->db_connection->prepare($selString);
			if (!$stmtNC)
			{
				$this->dieWithPDOError($stmtNC);
			} 
			$stmtNC->bindValue(':NCDCTO', $SDDCTO, PDO::PARAM_STR);
			$stmtNC->bindValue(':NCDOCO', $SDDOCO, PDO::PARAM_INT);
			$stmtNC->bindValue(':NCLNID', $SDLNID, PDO::PARAM_INT);
			$stmtNC->bindValue(':NCLITM', $SDLITM, PDO::PARAM_STR);
			$stmtNC->bindValue(':NCDOCO', $SDDOCO, PDO::PARAM_STR);
			$stmtNC->bindValue(':NCLOTN', $SDLOTN, PDO::PARAM_STR);
 
			$resultNC = $stmtNC->execute();
			if (!$resultNC)
			{
				$this->dieWithPDOError($stmtNC);
			} 
			$rowNC = $stmtNC->fetch(PDO::FETCH_ASSOC);
			if($rowNC) $HASNONCONF = $rowNC["HASNONCONF"];
			 
			
			$this->writeSegment('ListDetailsCliente', array_merge(get_object_vars($this), get_defined_vars()));
			
			// Fetch the next row
			$rowCount++;
			$row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);
		}
		
		// If there is still a row defined set $nextPage to signify another page of results
		if ($row)
		{
			$nextPage = $page + 1;
		}
		
		$this->writeSegment('TabFooter', array_merge(get_object_vars($this), get_defined_vars())); 
		$this->writeSegment('ListFooter', array_merge(get_object_vars($this), get_defined_vars())); 
		 		
	}

	protected function buildListFornitore() {

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
		 
		$this->writeSegment('ListHeader', array_merge(get_object_vars($this), get_defined_vars()));  
		$this->writeSegment('TabHeaderFornitore', array_merge(get_object_vars($this), get_defined_vars()));  
		 
		// Create and execute the list Select statement
		$stmt = $this->buildListStmtFornitore($rowOffset, $fetchLimit);
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
 			
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1");
				
				
				// make the file field names available in HTML
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			$HASNONCONF = 0;
			$selString = "SELECT 'S' AS HASNONCONF 
			FROM BCD_DATIV2.NONCON0F 
			WHERE NCTPCF = 'F' 
			AND NCDCTO = :NCDCTO 
			AND NCDOCO = :NCDOCO 
			AND NCLNID = :NCLNID 
			AND NCLITM = :NCLITM 
			AND NCLOTN = :NCLOTN 
			FETCH FIRST ROW ONLY 
			";
			$stmtNC = $this->db_connection->prepare($selString);
			if (!$stmtNC)
			{
				$this->dieWithPDOError($stmtNC);
			} 
			$stmtNC->bindValue(':NCDCTO', $PDDCTO, PDO::PARAM_STR);
			$stmtNC->bindValue(':NCDOCO', $PDDOCO, PDO::PARAM_INT);
			$stmtNC->bindValue(':NCLNID', $PDLNID, PDO::PARAM_INT);
			$stmtNC->bindValue(':NCLITM', $PDLITM, PDO::PARAM_STR);
			$stmtNC->bindValue(':NCDOCO', $PDDOCO, PDO::PARAM_STR);
			$stmtNC->bindValue(':NCLOTN', $PDLOTN, PDO::PARAM_STR);
 
			$resultNC = $stmtNC->execute();
			if (!$resultNC)
			{
				$this->dieWithPDOError($stmtNC);
			} 
			$rowNC = $stmtNC->fetch(PDO::FETCH_ASSOC);
			if($rowNC) $HASNONCONF = $rowNC["HASNONCONF"];

			// Output the row
			$this->writeSegment('ListDetailsFornitore', array_merge(get_object_vars($this), get_defined_vars()));
			
			// Fetch the next row
			$rowCount++;
			$row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT);
		}
		
		// If there is still a row defined set $nextPage to signify another page of results
		if ($row)
		{
			$nextPage = $page + 1;
		}
		
		$this->writeSegment('TabFooter', array_merge(get_object_vars($this), get_defined_vars())); 
		$this->writeSegment('ListFooter', array_merge(get_object_vars($this), get_defined_vars())); 
		 		
	}
	
	// Build the List statement
	protected function buildListStmtCliente($rowOffset, $listSize)
	{
		// Build the query with parameters
		$selString = $this->buildSelectStringCliente();
		$selString .= ' ' . $this->buildWhereClauseCliente();
		$selString .= ' ' . $this->buildOrderByCliente();
		
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the filter parameters
		if ($this->programState['filters']['SDAN8'] != '')
		{
			$stmt->bindValue(':SDAN8', $this->programState['filters']['SDAN8'], PDO::PARAM_INT);
		}
		
		if ($this->programState['filters']['ABALPH'] != '')
		{
			$stmt->bindValue(':ABALPH', '%' . strtolower($this->programState['filters']['ABALPH']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['SDDOCO'] != '')
		{
			$stmt->bindValue(':SDDOCO', $this->programState['filters']['SDDOCO'], PDO::PARAM_INT);
		}

		if ($this->programState['filters']['SDLOTN'] != '')
		{
			$stmt->bindValue(':SDLOTN', '%' . strtolower($this->programState['filters']['SDLOTN']) . '%', PDO::PARAM_STR);
		}
		
		return $stmt;
	}
	
	protected function buildListStmtFornitore($rowOffset, $listSize)
	{
		// Build the query with parameters
		$selString = $this->buildSelectStringFornitore();
		$selString .= ' ' . $this->buildWhereClauseFornitore();
		$selString .= ' GROUP BY JRGDTA94T.F4311.PDAN8, JRGDTA94T.F0101.ABALPH, JRGDTA94T.F4311.PDKCOO, 
		JRGDTA94T.F4311.PDDOCO, JRGDTA94T.F4311.PDDCTO, JRGDTA94T.F4311.PDLNID, JRGDTA94T.F43121.PRVRMK, JRGDTA94T.F4311.PDLITM, JRGDTA94T.F4311.PDDSC1, 
		JRGDTA94T.F4311.PDLOTN ';
		$selString .= ' ' . $this->buildOrderByFornitore();
		
 		    
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the filter parameters
		if ($this->programState['filters']['SDAN8'] != '')
		{
			$stmt->bindValue(':SDAN8', $this->programState['filters']['SDAN8'], PDO::PARAM_INT);
		}
		
		if ($this->programState['filters']['ABALPH'] != '')
		{
			$stmt->bindValue(':ABALPH', '%' . strtolower($this->programState['filters']['ABALPH']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['SDDOCO'] != '')
		{
			$stmt->bindValue(':SDDOCO', $this->programState['filters']['SDDOCO'], PDO::PARAM_INT);
		}
		
		if ($this->programState['filters']['SDLOTN'] != '')
		{
			$stmt->bindValue(':SDLOTN', '%' . strtolower($this->programState['filters']['SDLOTN']) . '%', PDO::PARAM_STR);
		}		
		
		return $stmt;
	}	
	
	// Build SQL Select string
	protected function buildSelectStringCliente()
	{
		$selString = '
		SELECT T.RRNF, T.SDAN8, T.ABALPH, T.SDKCOO, 
		T.SDDOCO, T.SDDCTO, T.SDLNID, 
		T.SDPSN, T.SDDELN, T.SDDOC, 
		T.SDLITM, T.SDDSC1, T.SDLOTN,  
		T.SDIVD, T.TIPOFILE  
		FROM TABLE ( 
			SELECT RRN(JRGDTA94T.F4211) AS RRNF, JRGDTA94T.F4211.SDAN8, JRGDTA94T.F0101.ABALPH, JRGDTA94T.F4211.SDKCOO, 
			JRGDTA94T.F4211.SDDOCO, JRGDTA94T.F4211.SDDCTO, JRGDTA94T.F4211.SDLNID, 
			JRGDTA94T.F4211.SDPSN, JRGDTA94T.F4211.SDDELN, JRGDTA94T.F4211.SDDOC, 
			JRGDTA94T.F4211.SDLITM, JRGDTA94T.F4211.SDDSC1, JRGDTA94T.F4211.SDLOTN,  
			CASE WHEN SDIVD <> 0 THEN date (days(concat(cast(integer(1900000+ SDIVD ) /1000 as Char(4)),\'-01-01\'))+mod(integer(1900000+  SDIVD ), 1000)-1) 
			ELSE \'0001-01-01\' END AS SDIVD, \'1\' AS TIPOFILE
			FROM JRGDTA94T.F4211 inner join JRGDTA94T.F0101 on JRGDTA94T.F4211.SDAN8 = JRGDTA94T.F0101.ABAN8
			
			UNION 
			
			SELECT RRN(JRGDTA94T.F42119) AS RRNF, JRGDTA94T.F42119.SDAN8, JRGDTA94T.F0101.ABALPH, JRGDTA94T.F42119.SDKCOO, 
			JRGDTA94T.F42119.SDDOCO, JRGDTA94T.F42119.SDDCTO, JRGDTA94T.F42119.SDLNID, 
			JRGDTA94T.F42119.SDPSN, JRGDTA94T.F42119.SDDELN, JRGDTA94T.F42119.SDDOC, 
			JRGDTA94T.F42119.SDLITM, JRGDTA94T.F42119.SDDSC1, JRGDTA94T.F42119.SDLOTN,  
			CASE WHEN SDIVD <> 0 THEN date (days(concat(cast(integer(1900000+ SDIVD ) /1000 as Char(4)),\'-01-01\'))+mod(integer(1900000+  SDIVD ), 1000)-1) 
			ELSE \'0001-01-01\' END AS SDIVD, \'2\' AS TIPOFILE 
			FROM JRGDTA94T.F42119 inner join JRGDTA94T.F0101 on JRGDTA94T.F42119.SDAN8 = JRGDTA94T.F0101.ABAN8
		) AS T 	
		';
		  
		return $selString;
	}
	
	protected function buildSelectStringFornitore()
	{
		$selString = 'SELECT MIN(RRN(JRGDTA94T.F4311)) AS RRNF, JRGDTA94T.F4311.PDAN8, JRGDTA94T.F0101.ABALPH, JRGDTA94T.F4311.PDKCOO, 
		JRGDTA94T.F4311.PDDOCO, JRGDTA94T.F4311.PDDCTO, JRGDTA94T.F4311.PDLNID, JRGDTA94T.F43121.PRVRMK, JRGDTA94T.F4311.PDLITM, JRGDTA94T.F4311.PDDSC1, 
		JRGDTA94T.F4311.PDLOTN 
		FROM JRGDTA94T.F4311 
		inner join JRGDTA94T.F43121 on PDDOCO=PRDOCO AND PDLNID=PRLNID AND PDKCOO=PRKCOO AND PDDCTO=PRDCTO 
		inner join JRGDTA94T.F0101 on JRGDTA94T.F4311.PDAN8 = JRGDTA94T.F0101.ABAN8 
		';
		
		return $selString;
		
	}	
	
	// Build where clause to filter rows from table
	protected function buildWhereClauseCliente()
	{
		$whereClause = '';
		$link = 'WHERE ';
		
		// Filter by SDAN8
		if ($this->programState['filters']['SDAN8'] != '')
		{
			$whereClause = $whereClause . $link . ' T.SDAN8 = :SDAN8';
			$link = ' AND ';
		}
		
		// Filter by ABALPH
		if ($this->programState['filters']['ABALPH'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(T.ABALPH) LIKE :ABALPH';
			$link = " AND ";
		}
		
		// Filter by SDDOCO
		if ($this->programState['filters']['SDDOCO'] != '')
		{
			$whereClause = $whereClause . $link . ' T.SDDOCO = :SDDOCO';
			$link = " AND ";
		}
		
		// Filter by SDLOTN
		if ($this->programState['filters']['SDLOTN'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(T.SDLOTN) LIKE :SDLOTN';
			$link = " AND ";
		}		
		
		return $whereClause;
	}

	protected function buildWhereClauseFornitore()
	{
		$whereClause = '';
		$link = 'WHERE ';
		
		// Filter by SDAN8
		if ($this->programState['filters']['SDAN8'] != '')
		{
			$whereClause = $whereClause . $link . ' JRGDTA94T.F4311.PDAN8 = :SDAN8';
			$link = ' AND ';
		}
		
		// Filter by ABALPH
		if ($this->programState['filters']['ABALPH'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94T.F0101.ABALPH) LIKE :ABALPH';
			$link = " AND ";
		}
		
		// Filter by ABALPH
		if ($this->programState['filters']['SDDOCO'] != '')
		{
			$whereClause = $whereClause . $link . ' JRGDTA94T.F4311.PDDOCO = :SDDOCO';
			$link = " AND ";
		}
		
		// Filter by SDLOTN
		if ($this->programState['filters']['SDLOTN'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94T.F4311.PDLOTN) LIKE :SDLOTN';
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
			$stmt->bindValue(':SDKCOO_', $keyFieldArray['SDKCOO'], PDO::PARAM_STR);
			$stmt->bindValue(':SDDOCO_', (int) $keyFieldArray['SDDOCO'], PDO::PARAM_INT);
			
		} else {
			$stmt->bindValue(':SDKCOO_', $keyFieldArray['SDKCOO_'], PDO::PARAM_STR);
			$stmt->bindValue(':SDDOCO_', (int) $keyFieldArray['SDDOCO_'], PDO::PARAM_INT);
			
		}
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildRecordSelectString()
	{
		$selString = 'SELECT JRGDTA94T.F4211.SDKCOO, JRGDTA94T.F4211.SDDOCO, JRGDTA94T.F4211.SDDCTO, JRGDTA94T.F4211.SDLNID, JRGDTA94T.F4211.SDPSN, JRGDTA94T.F4211.SDDELN, JRGDTA94T.F4211.SDDOC, JRGDTA94T.F4211.SDLITM, JRGDTA94T.F4211.SDDSC1, JRGDTA94T.F4211.SDLOTN, JRGDTA94T.F4211.SDIVD FROM JRGDTA94T.F4211 inner join JRGDTA94T.F0101 on JRGDTA94T.F4211.SDAN8 = JRGDTA94T.F0101.ABAN8';
		
		return $selString;
	}
	
	// Build where clause to filter single entries
	protected function buildRecordWhere()
	{
		$whereClause = 'WHERE JRGDTA94T.F4211.SDKCOO = :SDKCOO_ AND JRGDTA94T.F4211.SDDOCO = :SDDOCO_';
		
		return $whereClause;
	}
	
	// Return the SELECT SQL for a count on the primary file
	protected function getPrimaryFileCountSelect()
	{
		$selString = 'SELECT COUNT(*) FROM JRGDTA94T.F4211';
		
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
		$stmt->bindValue(':SDKCOO_', $keyFieldArray['SDKCOO'], PDO::PARAM_STR);
		$stmt->bindValue(':SDDOCO_', (int) $keyFieldArray['SDDOCO'], PDO::PARAM_INT);
		
		return $stmt;
	}
	// Build order by clause to order rows
	protected function buildOrderByCliente()
	{
		// Set sort order to programState's sort by and direction
		$orderBy = "ORDER BY " . $this->programState['sort'] . ' ' . $this->programState['sortDir'];
		
		return $orderBy;
	}

	protected function buildOrderByFornitore()
	{
		// Set sort order to programState's sort by and direction
		$orderBy = "ORDER BY " . $this->programState['sortF'] . ' ' . $this->programState['sortDirF'];
		
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
		
		//
		$sortF = xl_get_parameter('sidxf', 'db2_search');
		if ($sortF != '')
		{
			// Reverse order if sorting by the same column
			if ($sortF == $this->programState['sortF'])
			{
				if ($this->programState['sortDirF'] == 'asc')
				{
					$this->programState['sortDirF'] = 'desc';
				}
				else
				{
					$this->programState['sortDirF'] = 'asc';
				}
			}
			else
			{
				$this->programState['sortDirF'] = 'asc';
			}
			$this->programState['sortF'] = $sortF;
		}
 
		// If no sort column is specified, use the unique keylist as the default
		if ($this->programState['sortF'] == '')
		{
			// The sort order is build from the elements in $this->keyFields, if there are none then $this->uniqueFields will be used.
			$this->programState['sortF'] = $this->getDefaultSort($this->keyFieldsF, $this->uniqueFieldsF);
			$this->programState['sortDirF'] = 'asc';
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
    <title>Non conformit&agrave; - lista ordini</title>
    
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
          <h1 class="title">Non conformit&agrave; - lista ordini</h1>
          <!--
          <span class="add-link">
            <a class="btn btn-primary btn-sm" href="$pf_scriptname?task=beginadd&amp;rnd=$rnd">
              <span class="glyphicon glyphicon-plus"></span> Add Record
            </a>
          </span>
          -->
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <!-- Form containing filter inputs -->
          <form id="filter-form" class="container-fluid" method="post" action="$pf_scriptname">
            <input type="hidden" name="task" value="filter" />
            <div class="form">
              <div class="row">
               <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_tipo">Tipo</label>
                  
                  <select name="filter_tipo" id="filter_tipo" class="form-control">
                  	<option value=""></option>
                  	<option value="C" 
SEGDTA;
 echo (($programState['filters']['tipo']=="C")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Cliente</option>
                  	<option value="F" 
SEGDTA;
 echo (($programState['filters']['tipo']=="F")?('selected="selected"'):('')); 
		echo <<<SEGDTA
>Fornitore</option>
                  </select>
                  
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_SDAN8">Codice cliente / fornitore</label>
                  <input id="filter_SDAN8" class="form-control" type="text" name="filter_SDAN8" maxlength="8" value="{$programState['filters']['SDAN8']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ABALPH">Ragione sociale</label>
                  <input id="filter_ABALPH" class="form-control" type="text" name="filter_ABALPH" maxlength="40" value="{$programState['filters']['ABALPH']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_SDDOCO">N.Ordine</label>
                  <input id="filter_SDDOCO" class="form-control" type="text" name="filter_SDDOCO" maxlength="40" value="{$programState['filters']['SDDOCO']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_SDLOTN">Lotto</label>
                  <input id="filter_SDLOTN" class="form-control" type="text" name="filter_SDLOTN" maxlength="40" value="{$programState['filters']['SDLOTN']}"/>
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
          

            
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "listfooter")
	{

		echo <<<SEGDTA

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
	
	function getNonConfCli(jTIPOFILE,jRRNF) {
		jBtnEle = $("#btn-expand-cli-"+jTIPOFILE+"-"+jRRNF);
		if(jBtnEle.hasClass("glyphicon-arrow-right")) {
			jBtnEle.removeClass("glyphicon-arrow-right").addClass("glyphicon-arrow-down");	
			url = "nonconf02.php?task=getNonConfCli&TIPOFILE="+jTIPOFILE+"&RRNF="+jRRNF;
			$.get(url,function(data){
				$("#row-expand-cli-"+jTIPOFILE+"-"+jRRNF).css("display","table-row");
				$("#cell-expand-cli-"+jTIPOFILE+"-"+jRRNF).html(data);
			});	
		} else {
			jBtnEle.removeClass("glyphicon-arrow-down").addClass("glyphicon-arrow-right");	
			$("#row-expand-cli-"+jTIPOFILE+"-"+jRRNF).css("display","none");
			$("#cell-expand-cli-"+jTIPOFILE+"-"+jRRNF).html("");
		} 
	}

	function getNonConfFor(jRRNF) {
		jBtnEle = $("#btn-expand-for-"+jRRNF);
		if(jBtnEle.hasClass("glyphicon-arrow-right")) {
			jBtnEle.removeClass("glyphicon-arrow-right").addClass("glyphicon-arrow-down");	
			url = "nonconf02.php?task=getNonConfFor&RRNF="+jRRNF;
			$.get(url,function(data){
				$("#row-expand-for-"+jRRNF).css("display","table-row");
				$("#cell-expand-for-"+jRRNF).html(data);
			});	
		} else {
			jBtnEle.removeClass("glyphicon-arrow-down").addClass("glyphicon-arrow-right");	
			$("#row-expand-for-"+jRRNF).css("display","none");
			$("#cell-expand-for-"+jRRNF).html("");
		} 
	}
	
	function dltNonConfFor(jRRNF,jNCPROG) {
		if(!confirm("Eliminare questa non conformita?")) return false;
		
		url = "?task=dltNonConf&NCPROG="+jNCPROG;
		$.get(url,function(data){
			url = "nonconf02.php?task=getNonConfFor&RRNF="+jRRNF;
			$.get(url,function(data){ 
				$("#cell-expand-for-"+jRRNF).html(data);
			});		
		});
	}	

	function dltNonConfCli(jTIPOFILE,jRRNF,jNCPROG) {
		if(!confirm("Eliminare questa non conformita?")) return false;
		
		url = "?task=dltNonConf&NCPROG="+jNCPROG;
		$.get(url,function(data){
			url = "nonconf02.php?task=getNonConfCli&TIPOFILE="+jTIPOFILE+"&RRNF="+jRRNF;
			$.get(url,function(data){ 
				$("#cell-expand-cli-"+jTIPOFILE+"-"+jRRNF).html(data);
			});		
		});
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
    <title>Non conformit&agrave; - lista ordini - $mode</title>
    
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
          <h1 class="title">Non conformit&agrave; - lista ordini - $mode</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <div id="display-fields">
            <div class="form-group row">
              <label class="col-sm-4">Order Type . . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDDCTO</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Order Number . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDDOCO</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Line Number. . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDLNID</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Pick Slip Number . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDPSN</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Delivery Number. . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDDELN</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Document Number. . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDDOC</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">2nd Item Number. . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDLITM</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Description. . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDDSC1</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Lot/SN . . . . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDLOTN</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Invoice Date . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$SDIVD</div>
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
  <input id="SDKCOO" type="hidden" name="SDKCOO" value="$SDKCOO" />
  <input id="SDDOCO" type="hidden" name="SDDOCO" value="$SDDOCO" />
  
  <p>Are you SURE you want to delete this record?</p>
  <input type="submit" class="btn btn-primary accept" value="Yes">
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
    <title>Non conformit&agrave; - lista ordini - Add</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="websmart/v13.2/Responsive/images/company-logo.png" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Non conformit&agrave; - lista ordini - Add</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="add-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endadd" />
            <div id="addfields"><div class="notice 
SEGDTA;
 if(!$this->showRequiredIndicator) echo "hidden nodisplay"; 
		echo <<<SEGDTA
"><span class="required">{$this->requiredIndicator}</span> Denotes a required field.</div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDDCTO'); 
		echo <<<SEGDTA
">
                <label for="addSDDCTO">Order Type . . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDDCTO'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDDCTO" class="form-control" name="SDDCTO" size="2" maxlength="2" value="$SDDCTO">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDDCTO', array('Order Type . . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group">
                <label for="addSDDOCO">Order Number . . . . . . . . . . . . . .</label>
                <div id="addSDDOCO">$SDDOCO</div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDLNID'); 
		echo <<<SEGDTA
">
                <label for="addSDLNID">Line Number. . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDLNID'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDLNID" class="form-control" name="SDLNID" size="6" maxlength="6" value="$SDLNID">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDLNID', array('Line Number. . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDPSN'); 
		echo <<<SEGDTA
">
                <label for="addSDPSN">Pick Slip Number . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDPSN'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDPSN" class="form-control" name="SDPSN" size="8" maxlength="8" value="$SDPSN">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDPSN', array('Pick Slip Number . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDDELN'); 
		echo <<<SEGDTA
">
                <label for="addSDDELN">Delivery Number. . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDDELN'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDDELN" class="form-control" name="SDDELN" size="8" maxlength="8" value="$SDDELN">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDDELN', array('Delivery Number. . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDDOC'); 
		echo <<<SEGDTA
">
                <label for="addSDDOC">Document Number. . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDDOC'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDDOC" class="form-control" name="SDDOC" size="8" maxlength="8" value="$SDDOC">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDDOC', array('Document Number. . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDLITM'); 
		echo <<<SEGDTA
">
                <label for="addSDLITM">2nd Item Number. . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDLITM'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDLITM" class="form-control" name="SDLITM" size="25" maxlength="25" value="$SDLITM">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDLITM', array('2nd Item Number. . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDDSC1'); 
		echo <<<SEGDTA
">
                <label for="addSDDSC1">Description. . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDDSC1'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDDSC1" class="form-control" name="SDDSC1" size="30" maxlength="30" value="$SDDSC1">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDDSC1', array('Description. . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDLOTN'); 
		echo <<<SEGDTA
">
                <label for="addSDLOTN">Lot/SN . . . . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDLOTN'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDLOTN" class="form-control" name="SDLOTN" size="30" maxlength="30" value="$SDLOTN">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDLOTN', array('Lot/SN . . . . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDIVD'); 
		echo <<<SEGDTA
">
                <label for="addSDIVD">Invoice Date . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDIVD'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addSDIVD" class="form-control" name="SDIVD" size="6" maxlength="6" value="$SDIVD">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDIVD', array('Invoice Date . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>	
            </div>
            <div id="navbottom">
              <input type="submit" class="btn btn-primary accept" value="Add" />
              <input type="button" class="btn btn-default cancel" value="Cancel" />
            </div>		
          </form>
        </div>
      </div>
    </div>
    <script type="text/javascript">
		jQuery(function() {
			jQuery("input[name='SDKCOO']").attr("disabled",true);
			jQuery("input[name='SDDOCO']").attr("disabled",true);
			// Focus the first input on page load
			jQuery("input:enabled:first").focus();
			
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
    <title>Non conformit&agrave; - lista ordini - Change</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="websmart/v13.2/Responsive/images/company-logo.png" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Non conformit&agrave; - lista ordini - Change</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="change-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endchange" />
            <input id="SDKCOO_" type="hidden" name="SDKCOO_" value="$SDKCOO" />
            <input id="SDDOCO_" type="hidden" name="SDDOCO_" value="$SDDOCO" />
            <div id="changefields"><div class="notice 
SEGDTA;
 if(!$this->showRequiredIndicator) echo "hidden nodisplay"; 
		echo <<<SEGDTA
"><span class="required">{$this->requiredIndicator}</span> Denotes a required field.</div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDDCTO'); 
		echo <<<SEGDTA
">
                <label for="chgSDDCTO">Order Type . . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDDCTO'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDDCTO" class="form-control" name="SDDCTO" size="2" maxlength="2" value="$SDDCTO">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDDCTO', array('Order Type . . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group">
                <label for="chgSDDOCO">Order Number . . . . . . . . . . . . . .</label>
                <div id="chgSDDOCO">$SDDOCO</div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDLNID'); 
		echo <<<SEGDTA
">
                <label for="chgSDLNID">Line Number. . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDLNID'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDLNID" class="form-control" name="SDLNID" size="6" maxlength="6" value="$SDLNID">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDLNID', array('Line Number. . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDPSN'); 
		echo <<<SEGDTA
">
                <label for="chgSDPSN">Pick Slip Number . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDPSN'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDPSN" class="form-control" name="SDPSN" size="8" maxlength="8" value="$SDPSN">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDPSN', array('Pick Slip Number . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDDELN'); 
		echo <<<SEGDTA
">
                <label for="chgSDDELN">Delivery Number. . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDDELN'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDDELN" class="form-control" name="SDDELN" size="8" maxlength="8" value="$SDDELN">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDDELN', array('Delivery Number. . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDDOC'); 
		echo <<<SEGDTA
">
                <label for="chgSDDOC">Document Number. . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDDOC'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDDOC" class="form-control" name="SDDOC" size="8" maxlength="8" value="$SDDOC">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDDOC', array('Document Number. . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDLITM'); 
		echo <<<SEGDTA
">
                <label for="chgSDLITM">2nd Item Number. . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDLITM'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDLITM" class="form-control" name="SDLITM" size="25" maxlength="25" value="$SDLITM">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDLITM', array('2nd Item Number. . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDDSC1'); 
		echo <<<SEGDTA
">
                <label for="chgSDDSC1">Description. . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDDSC1'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDDSC1" class="form-control" name="SDDSC1" size="30" maxlength="30" value="$SDDSC1">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDDSC1', array('Description. . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDLOTN'); 
		echo <<<SEGDTA
">
                <label for="chgSDLOTN">Lot/SN . . . . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDLOTN'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDLOTN" class="form-control" name="SDLOTN" size="30" maxlength="30" value="$SDLOTN">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDLOTN', array('Lot/SN . . . . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('SDIVD'); 
		echo <<<SEGDTA
">
                <label for="chgSDIVD">Invoice Date . . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('SDIVD'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgSDIVD" class="form-control" name="SDIVD" size="6" maxlength="6" value="$SDIVD">
                  <span class="error-text">
SEGDTA;
 $this->displayError('SDIVD', array('Invoice Date . . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>	
            </div>
            <div id="navbottom">
              <input type="submit" class="btn btn-primary accept" value="Change" />
              <input type="button" class="btn btn-default cancel" value="Cancel" />
            </div>		
          </form>
        </div>
      </div>
    </div>
    <script type="text/javascript">
		jQuery(function() {
			
			jQuery("input[name='SDKCOO']").attr("disabled",true);
			jQuery("input[name='SDDOCO']").attr("disabled",true);
			
			// Focus the first input on page load
			jQuery("input:enabled:first").focus();
			
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
	if($xlSegmentToWrite == "tabheadercliente")
	{

		echo <<<SEGDTA
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
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDAN8&amp;rnd=$rnd">Cod.Cliente</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=ABALPH&amp;rnd=$rnd">Rag.Soc.</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDDCTO&amp;rnd=$rnd">Tipo ordine</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDDOCO&amp;rnd=$rnd">Ordine</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDLNID&amp;rnd=$rnd">Linea</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDPSN&amp;rnd=$rnd">Prebolla</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDDELN&amp;rnd=$rnd">Bolla</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDDOC&amp;rnd=$rnd">Fattura</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDLITM&amp;rnd=$rnd">Articolo</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDDSC1&amp;rnd=$rnd">Descrizione articolo</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDLOTN&amp;rnd=$rnd">Lotto</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=SDIVD&amp;rnd=$rnd">Data fattura</a>
                </th>
              </tr>
            </thead>
            <tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "listdetailscliente")
	{

		echo <<<SEGDTA

<tr>
  
  <td class="actions">
    <span>
      <a class="btn btn-default btn-xs glyphicon glyphicon-plus" title="Crea non conformita" href="nonconf02.php?task=beginaddcli&amp;TIPOFILE=$TIPOFILE&amp;RRNF=$RRNF&amp;rnd=$rnd"></a> 
      <a style="
SEGDTA;
 if($HASNONCONF!="S") echo 'display:none;'; 
		echo <<<SEGDTA
" class="btn btn-default btn-xs glyphicon glyphicon-arrow-right" id="btn-expand-cli-$TIPOFILE-$RRNF" title="Visualizza non conformità" href="javascript:void('0');" onclick="getNonConfCli('$TIPOFILE','$RRNF');"></a> 
    </span>
  </td> 
  <td class="text">$SDAN8</td>
  <td class="text">$ABALPH</td>
  <td class="text">$SDDCTO</td>
  <td class="text num">$SDDOCO</td>
  <td class="text num">$SDLNID</td>
  <td class="text num">$SDPSN</td>
  <td class="text num">$SDDELN</td>
  <td class="text num">$SDDOC</td>
  <td class="text">$SDLITM</td>
  <td class="text">$SDDSC1</td>
  <td class="text">$SDLOTN</td>
  <td class="text num">$SDIVD</td>
</tr>
<tr id="row-expand-cli-$TIPOFILE-$RRNF" style="display:none;">
	<td id="cell-expand-cli-$TIPOFILE-$RRNF" colspan="13"></td>
</tr>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "tabheaderfornitore")
	{

		echo <<<SEGDTA
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
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=PDAN8&amp;rnd=$rnd">Cod.Fornitore</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=ABALPH&amp;rnd=$rnd">Rag.Soc.</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=PDDCTO&amp;rnd=$rnd">Tipo ordine</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=PDDOCO&amp;rnd=$rnd">Ordine</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=PDLNID&amp;rnd=$rnd">Linea</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=PRVRMK&amp;rnd=$rnd">Fattura</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=PDLITM&amp;rnd=$rnd">Articolo</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=PDDSC1&amp;rnd=$rnd">Descrizione articolo</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidxf=PDLOTN&amp;rnd=$rnd">Lotto</a>
                </th> 
              </tr>
            </thead>
            <tbody>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "listdetailsfornitore")
	{

		echo <<<SEGDTA

<tr>
  
  <td class="actions">
    <span>
      <a class="btn btn-default btn-xs glyphicon glyphicon-plus" title="Crea non conformita" href="nonconf02.php?task=beginaddfor&amp;RRNF=$RRNF&amp;rnd=$rnd"></a> 
      <a style="
SEGDTA;
 if($HASNONCONF!="S") echo 'display:none;'; 
		echo <<<SEGDTA
" class="btn btn-default btn-xs glyphicon glyphicon-arrow-right" id="btn-expand-for-$RRNF" title="Visualizza non conformità" href="javascript:void('0');" onclick="getNonConfFor('$RRNF');"></a> 
    </span>
  </td> 
  <td class="text">$PDAN8</td>
  <td class="text">$ABALPH</td>
  <td class="text">$PDDCTO</td>
  <td class="text num">$PDDOCO</td>
  <td class="text num">$PDLNID</td> 
  <td class="text num">$PRVRMK</td>
  <td class="text">$PDLITM</td>
  <td class="text">$PDDSC1</td>
  <td class="text">$PDLOTN</td>
</tr>
<tr id="row-expand-for-$RRNF" style="display:none;">
	<td id="cell-expand-for-$RRNF" colspan="10"></td>
</tr>

SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "tabfooter")
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
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "warnfilter")
	{

		echo <<<SEGDTA
<br>
<div class="alert alert-warning">
Selezionare un tipo Cliente/Fornitore
</div>
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
		
		$this->pf_liblLibs[1] = 'JRGDTA94T';
		
		parent::__construct();

		$this->pf_scriptname = 'nonconf01.php';
		$this->pf_wcm_set = 'PRODUZIONE';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		
		// Last Generated CRC: E825C183 AB192D0F 1B526789 30A24D9E
		// Last Generated Date: 2024-05-21 11:49:33
		// Path: nonconf01.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'nonconf01');?>