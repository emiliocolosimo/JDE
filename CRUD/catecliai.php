<?php
if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

set_time_limit(120);

require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');
require_once('websmart/v13.2/include/en-US/WSRequiredValidator.php');
require_once('websmart/v13.2/include/en-US/WSNumericValidator.php');

class catecli extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'page' => 1,
		'listSize' => 20,
		'filters' => array('ABAN8' => '', 'ABALPH' => '', 'TIPOCLI' => '', 'ONDATE' => '', 'CTKY1' => '', 'NOCATE'=>'')
	);
	
	
	protected $keyFields = array();
	protected $uniqueFields = array();
	
	public function runMain()
	{
		// Connect to the database
		try 
		{
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
		
		if(!isset($_SESSION["catecli-access"])) $this->programState['filters']['NOCATE'] = 'S';
		$_SESSION["catecli-access"] = 'S';
		
		$this->formFields = array(
			"ABAN8" => array("validators"=> array("WSRequired","WSNumeric")));
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
			
			case 'updCategoria':
			$this->updCategoria();
			break;

			case 'updRelazioni':
			$this->updRelazioni();
			break;
			
			case 'updNote':
			$this->updNote();
			break;

			case 'updweb':
				$this->updweb();
				break;
		}
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
		$delString = 'DELETE FROM JRGDTA94C.F564211 ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($delString);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the key parameters
		
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
		$ABAN8 = "";
		$ABALPH = "";
		$ALCTY1 = "";
		$ALCTR = "";
		
		// Output the segment
		$this->writeSegment('RcdAdd', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	// Add the passed in data as a new row
	protected function endAdd()
	{
		// Get values from the page
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		extract($keyFieldArray);
		$ABAN8 = xl_get_parameter('ABAN8');
		
		
		// Do any add validation here
		$isValid = $this->validate();
		
		if(!$isValid)
		{
			
			$this->writeSegment('RcdAdd', array_merge(get_object_vars($this), get_defined_vars()));
			return;
		}
		
		// Make sure we don't already have a record with these keys
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
		if($row[0] > 0)
		{
			die('This record already exists.');
		}
		
		// Prepare the statement to add the record
		$insertSql = 'INSERT INTO JRGDTA94C.F564211 (SDAN8) VALUES(:ABAN8)' . ' WITH NC';
		$stmt = $this->db_connection->prepare($insertSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		
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
			
			$row[$key] = htmlspecialchars(rtrim($row[$key]));
			
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
		$oldKeyFieldArray = $this->getParameters(array());
		
		extract($oldKeyFieldArray);
		// Get values from the page
		$ABAN8 = xl_get_parameter('ABAN8');
		
		//Protect Key Fields from being Changed
		
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
		$updateSql = 'UPDATE JRGDTA94C.F564211 SET SDAN8 = :ABAN8';
		$updateSql .= ' ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($updateSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		
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
		
		$this->programState['filters']['ABAN8'] = trim(xl_get_parameter('filter_ABAN8'));
		$this->programState['filters']['ABALPH'] = trim(xl_get_parameter('filter_ABALPH'));
		$this->programState['filters']['TIPOCLI'] = trim(xl_get_parameter('filter_TIPOCLI'));
		$this->programState['filters']['ONDATE'] = trim(xl_get_parameter('filter_ONDATE'));
		$this->programState['filters']['CTKY1'] = trim(xl_get_parameter('filter_CTKY1'));
		$this->programState['filters']['NOCATE'] = trim(xl_get_parameter('filter_NOCATE'));
		
		 
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
			
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]));
				
				
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
		$selString .= ' GROUP BY 
		ABAN8,     
		ABALPH, 
        ALCTY1 , 
        ALCTR,
		CTKY1 , 
		CTREL ,
		CTNOT ,
		CTWEB 
		 ';
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
			$stmt->bindValue(':ABALPH', '%'.strtolower($this->programState['filters']['ABALPH']).'%', PDO::PARAM_STR);
		}
		if ($this->programState['filters']['ONDATE'] != '')
		{
			$stmt->bindValue(':ONDATE', $this->programState['filters']['ONDATE'], PDO::PARAM_STR);
		}
		if ($this->programState['filters']['CTKY1'] != '')
		{
			$stmt->bindValue(':CTKY1', '%'.strtolower($this->programState['filters']['CTKY1']).'%', PDO::PARAM_STR);
		}
		
		
		
		
		return $stmt;
	}
	
	protected function updCategoria() {
		$CTKY1 = xl_get_parameter("categoria");
		$ABAN8 = (int) xl_get_parameter("ABAN8");
		
		
		$selString = "SELECT 1 AS ST 
		FROM JRGDTA94C.SPCATCL0F  
		WHERE CTAN8 = :ABAN8  
		";
		$stmt = $this->db_connection->prepare($selString); 
		$stmt->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		$result = $stmt->execute(); 
 		$row = $stmt->fetch(PDO::FETCH_ASSOC);
 		if($row && $row["ST"]==1) { 
			$updateSql = "UPDATE JRGDTA94C.SPCATCL0F  
			SET CTKY1 = :CTKY1 
			WHERE CTAN8 = :ABAN8  
			WITH NC
			";
		} else { 
			$updateSql = "INSERT INTO JRGDTA94C.SPCATCL0F (CTKY1,CTREL,CTNOT,CTWEB,CTAN8) 
			VALUES(:CTKY1,'','','',:ABAN8) 
			WITH NC
			";
		}
		
		$stmt_upd = $this->db_connection->prepare($updateSql);
		if (!$stmt_upd)
		{
			$this->dieWithPDOError($stmt_upd);
		}
		
		// Bind the parameters
		$stmt_upd->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		$stmt_upd->bindValue(':CTKY1', $CTKY1, PDO::PARAM_STR);
		
		// Execute the update statement
		$result_upd = $stmt_upd->execute();
		if ($result_upd === false)
		{
			$this->dieWithPDOError($stmt_upd);
		}	 
		
	}

	protected function updRelazioni() {
		$CTREL = xl_get_parameter("relazioni");
		$ABAN8 = (int) xl_get_parameter("ABAN8");
		
		
		$selString = "SELECT 1 AS ST 
		FROM JRGDTA94C.SPCATCL0F  
		WHERE CTAN8 = :ABAN8  
		";
		$stmt = $this->db_connection->prepare($selString); 
		$stmt->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		$result = $stmt->execute(); 
 		$row = $stmt->fetch(PDO::FETCH_ASSOC);
 		if($row && $row["ST"]==1) { 		
			$updateSql = "UPDATE JRGDTA94C.SPCATCL0F  
			SET CTREL = :CTREL 
			WHERE CTAN8 = :ABAN8  
			WITH NC
			";
		} else {
			$updateSql = "INSERT INTO JRGDTA94C.SPCATCL0F (CTKY1,CTREL,CTNOT,CTWEB,CTAN8) 
			VALUES('',:CTREL,'','',:ABAN8) 
			WITH NC
			";
		}
		
		$stmt_upd = $this->db_connection->prepare($updateSql);
		if (!$stmt_upd)
		{
			$this->dieWithPDOError($stmt_upd);
		}
		
		// Bind the parameters
		$stmt_upd->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		$stmt_upd->bindValue(':CTREL', $CTREL, PDO::PARAM_STR);
		
		// Execute the update statement
		$result_upd = $stmt_upd->execute();
		if ($result_upd === false)
		{
			$this->dieWithPDOError($stmt_upd);
		}
	}
	
	protected function updNote() {
		$CTNOT = xl_get_parameter("note");
		$ABAN8 = (int) xl_get_parameter("ABAN8");
		
		$selString = "SELECT 1 AS ST 
		FROM JRGDTA94C.SPCATCL0F  
		WHERE CTAN8 = :ABAN8  
		";
		$stmt = $this->db_connection->prepare($selString); 
		$stmt->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		$result = $stmt->execute(); 
 		$row = $stmt->fetch(PDO::FETCH_ASSOC);
 		if($row && $row["ST"]==1) { 		
			$updateSql = "UPDATE JRGDTA94C.SPCATCL0F  
			SET CTNOT = :CTNOT  
			WHERE CTAN8 = :ABAN8  
			WITH NC
			";
		} else {
			$updateSql = "INSERT INTO JRGDTA94C.SPCATCL0F (CTKY1,CTREL,CTNOT,CTAN8) 
			VALUES('','',:CTNOT,:ABAN8) 
			WITH NC
			";
		}
			 
		$stmt_upd = $this->db_connection->prepare($updateSql);
		if (!$stmt_upd)
		{
			$this->dieWithPDOError($stmt_upd);
		}
		
		// Bind the parameters
		$stmt_upd->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		$stmt_upd->bindValue(':CTNOT', $CTNOT, PDO::PARAM_STR);
		
		// Execute the update statement
		$result_upd = $stmt_upd->execute();
		if ($result_upd === false)
		{
			$this->dieWithPDOError($stmt_upd);
		}	
	}	
	

	protected function updweb() {
		$CTWEB = xl_get_parameter("web");
		$ABAN8 = (int) xl_get_parameter("ABAN8");
		
		$selString = "SELECT 1 AS ST 
		FROM JRGDTA94C.SPCATCL0F  
		WHERE CTAN8 = :ABAN8  
		";
		$stmt = $this->db_connection->prepare($selString); 
		$stmt->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		$result = $stmt->execute(); 
 		$row = $stmt->fetch(PDO::FETCH_ASSOC);
 		if($row && $row["ST"]==1) { 		
			$updateSql = "UPDATE JRGDTA94C.SPCATCL0F  
			SET CTWEB = :CTWEB 
			WHERE CTAN8 = :ABAN8  
			WITH NC
			";
		} else {
			$updateSql = "INSERT INTO JRGDTA94C.SPCATCL0F (CTKY1,CTREL,CTNOT,CTWEB,CTAN8) 
			VALUES('','','',:CTWEB,:ABAN8) 
			WITH NC
			";
		}
			 
		$stmt_upd = $this->db_connection->prepare($updateSql);
		if (!$stmt_upd)
		{
			$this->dieWithPDOError($stmt_upd);
		}
		
		// Bind the parameters
		$stmt_upd->bindValue(':ABAN8', $ABAN8, PDO::PARAM_INT);
		$stmt_upd->bindValue(':CTWEB', $CTWEB, PDO::PARAM_STR);
		
		// Execute the update statement
		$result_upd = $stmt_upd->execute();
		if ($result_upd === false)
		{
			$this->dieWithPDOError($stmt_upd);
		}	
	}	
	





	protected function lstCategorie($ABAN8,$CTKY1_sel) {
		$selString = "SELECT JRGDTA94C.ANCATCL0F.CTKY1 
		FROM JRGDTA94C.ANCATCL0F  
		WHERE CTKY1 <> '' 
		ORDER BY CTKY1 
		";	
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute(); 
		echo '<select ABAN8="'.$ABAN8.'" class="categoria-input-edit" name="categoria-$ABAN8" id="categoria-$ABAN8">';
		echo '<option value=""></option>';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1"); 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			echo '<option value="'.$$escapedField.'" '.(($CTKY1_sel==$CTKY1)?('selected="selected"'):('')).'>'.$$escapedField.'</option>';	
		}
		echo '</select>';
		
	}
	
	protected function fltCategorie($CTKY1_sel) {
		$selString = "SELECT JRGDTA94C.ANCATCL0F.CTKY1 
		FROM JRGDTA94C.ANCATCL0F  
		WHERE CTKY1 <> '' 
		ORDER BY CTKY1 
		";	
		$stmt = $this->db_connection->prepare($selString);
		$result = $stmt->execute(); 
		echo '<select class="categoria-input" name="filter_CTKY1" id="filter_CTKY1">';
		echo '<option value=""></option>';
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1"); 
				$escapedField = xl_fieldEscape($key);
				$$escapedField = $row[$key];
			}
			
			echo '<option value="'.$$escapedField.'" '.(($CTKY1_sel==$CTKY1)?('selected="selected"'):('')).'>'.$$escapedField.'</option>';	
		}
		echo '</select>';
		
	}	
	
	// Build SQL Select string
	protected function buildSelectString()
	{
		$selString = '   SELECT * FROM(  SELECT JRGDTA94C.F0101.ABAN8 AS ABAN8, 
        COALESCE(JRGDTA94C.F0101.ABALPH,\'\') AS ABALPH, 
        COALESCE(JRGDTA94C.F0116.ALCTY1,\'\') AS ALCTY1, 
        COALESCE(JRGDTA94C.F0116.ALCTR,\'\')AS ALCTR,
        COALESCE(JRGDTA94C.SPCATCL0F.CTKY1,\'\') AS CTKY1,
        COALESCE(JRGDTA94C.SPCATCL0F.CTREL,\'\') AS CTREL,
        COALESCE(JRGDTA94C.SPCATCL0F.CTNOT,\'\') AS CTNOT,
        COALESCE(JRGDTA94C.SPCATCL0F.CTWEB,\'\') AS CTWEB
		FROM JRGDTA94C.F0101A  
        LEFT join JRGDTA94C.F0116 on JRGDTA94C.F0101A.AZAN8 = JRGDTA94C.F0116.ALAN8
        left join JRGDTA94C.SPCATCL0F on JRGDTA94C.F0101A.AZAN8 = JRGDTA94C.SPCATCL0F.CTAN8
        LEFT join JRGDTA94C.F0101 on JRGDTA94C.F0101A.AZAN8 = JRGDTA94C.F0101.ABAN8
where JRGDTA94C.F0101A.AZAN8 < 2000000 and JRGDTA94C.F0101A.AZACTN = \'A\' AND JRGDTA94C.F0101A.AZTRAL = \'1\' AND JRGDTA94C.F0101A.AZCHGJ > 125000

        UNION ALL
     SELECT JRGDTA94C.F0101.ABAN8 AS ABAN8,     
COALESCE(JRGDTA94C.F0101.ABALPH,\'\') AS ABALPH, 
        COALESCE(JRGDTA94C.F0116.ALCTY1,\'\') AS ALCTY1, 
        COALESCE(JRGDTA94C.F0116.ALCTR,\'\')AS ALCTR,
		COALESCE(JRGDTA94C.SPCATCL0F.CTKY1,\'\') AS CTKY1, 
		COALESCE(JRGDTA94C.SPCATCL0F.CTREL,\'\') AS CTREL,
		COALESCE(JRGDTA94C.SPCATCL0F.CTNOT,\'\') AS CTNOT,
		COALESCE(JRGDTA94C.SPCATCL0F.CTWEB,\'\') AS CTWEB
		FROM JRGDTA94C.F564211 
		inner join JRGDTA94C.F0101 on JRGDTA94C.F564211.SDAN8 = JRGDTA94C.F0101.ABAN8 
		inner join JRGDTA94C.F0116 on JRGDTA94C.F564211.SDAN8 = JRGDTA94C.F0116.ALAN8 
		left join JRGDTA94C.SPCATCL0F on JRGDTA94C.F564211.SDAN8 = JRGDTA94C.SPCATCL0F.CTAN8   
		left join JRGDTA94C.f00365 on JRGDTA94C.f00365.ondtej = CASE WHEN sddcto not in (\'OF\' , \'SQ\') THEN sdtrdj ELSE sdivd END         
) 
        		';
		 
		return $selString;
	}
	
	// Build where clause to filter rows from table
	protected function buildWhereClause()
	{
		$whereClause = ' WHERE ABAN8 < 2000000 ';
		$link = ' AND ';
		
		// Filter by ABAN8
		if ($this->programState['filters']['ABAN8'] != '')
		{
			$whereClause = $whereClause . $link . ' ABAN8 = :ABAN8';
			$link = ' AND ';
		}
		// Filter by ABALPH
		if ($this->programState['filters']['ABALPH'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(ABALPH) LIKE :ABALPH ';
			$link = ' AND ';
		}
		/*
		if ($this->programState['filters']['TIPOCLI'] == 'C')
		{
			$whereClause = $whereClause . $link . ' sddcto not in (\'OF\' , \'SQ\') ';
			$link = ' AND ';	
		}		
		if ($this->programState['filters']['TIPOCLI'] == 'P')
		{
			$whereClause = $whereClause . $link . ' sddcto in (\'OF\' , \'SQ\') ';
			$link = ' AND ';	
		}			
					
		if ($this->programState['filters']['TIPOCLI'] == 'N')
		{
			$whereClause = $whereClause . $link . ' sddcto = \'XX\' ';
			$link = ' AND ';	
		}	
*/


		// Filter by ONDATE
		if ($this->programState['filters']['ONDATE'] != '')
		{
			$whereClause = $whereClause . $link . ' ONDATE = :ONDATE';
			$link = ' AND ';
		}
		 
		// Filter by CTKY1
		if ($this->programState['filters']['CTKY1'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(CTKY1) LIKE :CTKY1';
			$link = ' AND ';
		}
		
		// Filter by  
		if ($this->programState['filters']['NOCATE'] == 'S')
		{
			$whereClause = $whereClause . $link . ' (CTKY1 = \'\' or CTKY1 is null)';
			$link = ' AND ';
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
			
		} else {
			
		}
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildRecordSelectString()
	{
		$selString = 'SELECT, JRGDTA94C.F0101.ABAN8, JRGDTA94C.F0101.ABALPH, JRGDTA94C.F0116.ALCTY1, JRGDTA94C.F0116.ALCTR FROM JRGDTA94C.F564211 inner join JRGDTA94C.F0101 on JRGDTA94C.F564211.SDAN8 = JRGDTA94C.F0101.ABAN8 inner join JRGDTA94C.F0116 on JRGDTA94C.F564211.SDAN8 = JRGDTA94C.F0116.ALAN8';
		
		return $selString;
	}
	
	// Build where clause to filter single entries
	protected function buildRecordWhere()
	{
		$whereClause = 'WHERE ';
		
		return $whereClause;
	}
	
	// Return the SELECT SQL for a count on the primary file
	protected function getPrimaryFileCountSelect()
	{
		$selString = 'SELECT COUNT(*) FROM JRGDTA94C.F564211';
		
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
		
		return $stmt;
	}
	// Build order by clause to order rows
	protected function buildOrderBy()
	{
		// Set sort order to programState's sort by and direction
	//	$orderBy = "ORDER BY " . $this->programState['sort'] . ' ' . $this->programState['sortDir'] ;
		$orderBy = "ORDER BY ABAN8, ABALPH, ALCTY1, ALCTR, CTKY1, CTREL, CTNOT, CTWEB LIMIT 50"; ;
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
			$this->programState['sort'] = 'ABAN8';
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
    <title>Categorie clienti</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />

    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/js/jquery-ui.js"></script> 
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
          <h1 class="title">Categorie clienti</h1>
           
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <!-- Form containing filter inputs -->
          <form id="filter-form" class="container-fluid" method="post" action="$pf_scriptname" onsubmit="$.blockUI();">
            <input type="hidden" name="task" value="filter" />
            <div class="form">
              <div class="row">
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ABAN8">Cod.Cliente</label>
                  <input id="filter_ABAN8" class="form-control" type="text" name="filter_ABAN8" maxlength="8" value="{$programState['filters']['ABAN8']}"/>
                </div>
              
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ABALPH">Rag.Sociale</label>
                  <input id="filter_ABALPH" class="form-control" type="text" name="filter_ABALPH" maxlength="35" value="{$programState['filters']['ABALPH']}"/>
                </div>
				   </select> 
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ONDATE">Data</label>
                  <input id="filter_ONDATE" class="form-control calendario-8" type="text" name="filter_ONDATE" maxlength="8" value="{$programState['filters']['ONDATE']}"/>
                </div>
                
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_CTKY1">Categoria</label>
                  <input id="filter_CTKY1" class="form-control" type="text" name="filter_CTKY1" maxlength="60" value="{$programState['filters']['CTKY1']}"/>   
                </div>
                
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_NOCATE">&nbsp;</label>
                  <label class="checkbox-inline">
				  	<input type="checkbox" value="S" name="filter_NOCATE" id="filter_NOCATE" 
SEGDTA;
 echo (($this->programState['filters']['NOCATE']=="S")?('checked="checked"'):('')); 
		echo <<<SEGDTA
> Visualizza senza categoria
				  </label>  	
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
                <th class="actions" width="100"> </th> 
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=ABAN8&amp;rnd=$rnd">Cod. Cliente</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=ABAN8&amp;rnd=$rnd">Rag. Sociale</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=ALCTY1&amp;rnd=$rnd">Citt&agrave;</a>
                </th> 
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=ALCTR&amp;rnd=$rnd">Nazione</a>
                </th>
                <th></th>
                <th>Categoria</th>
                <th>Relazioni aziendali</th>
                <th>Note</th>
				<th>Sito Internet</th>
              </tr>
            </thead>
            <tbody>

            
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "listdetails")
	{

		echo <<<SEGDTA

<tr data-id="{$ABALPH}">
   
  <td class="actions">
    <span>
      <a class="btn btn-default btn-xs glyphicon glyphicon-filter" title="Visualizza questa categoria" onclick="$.blockUI();" href="$pf_scriptname?task=filter&filter_CTKY1=
SEGDTA;
 echo urlencode($CTKY1); 
		echo <<<SEGDTA
"></a> 
      <!--
      <a class="btn btn-default btn-xs glyphicon glyphicon-pencil" title="Change this record" href="$pf_scriptname?task=beginchange&amp;rnd=$rnd"></a> 
      <a class="btn btn-default btn-xs glyphicon glyphicon-remove" title="Delete this record" href="$pf_scriptname?task=delconf"></a>
      -->
    </span>
  </td>
   
  <td class="text">$ABAN8</td>
  <td class="text">$ABALPH</td>
  <td class="text">$ALCTY1</td>
  <td class="text">$ALCTR</td>
  <td>
      <!-- tasto ricerca -->
      <a class="btn btn-default btn-xs glyphicon glyphicon-search" title="Ricerca" target="_blank" href="https://www.google.com/search?q=
SEGDTA;
 echo urlencode($ABALPH); 
		echo <<<SEGDTA
"></a>
      <a class="btn btn-default btn-xs glyphicon glyphicon-info-sign"
         title="AI - Analizza"
onclick="inviaChat('<?= addslashes($ABALPH) ?>', this);"
         style="margin-left: 4px;"></a>
  </td>


  <td>
  
SEGDTA;
 
  	$this->lstCategorie($ABAN8,$CTKY1);
  
		echo <<<SEGDTA

  </td>
  <td>
  	<input type="text" ABAN8="$ABAN8" class="relazioni-input form-control input-sm" name="relazioni-$ABAN8" id="relazioni-$ABAN8" value="$CTREL" size="80" maxlength="2000" />
  </td>
  <td>
  	<input type="text" ABAN8="$ABAN8" class="note-input form-control input-sm" name="note-$ABAN8" id="note-$ABAN8" value="$CTNOT" size="80" maxlength="2000" />

  </td>
  <td>
  	<input type="text" ABAN8="$ABAN8" class="web-input form-control input-sm" name="web-$ABAN8" id="web-$ABAN8" value="$CTWEB" size="80" maxlength="2000" />

  </td>
      <td>
      <a class="btn btn-link btn-xs glyphicon glyphicon-link" title="Sito" target="_blank" href="https://
SEGDTA;
 echo urlencode($CTWEB); 
		echo <<<SEGDTA
"></a> 
  </td>
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


<link href="/crud/websmart/v13.2/js/select2.min.css" rel="stylesheet" />
<script src="/crud/websmart/v13.2/js/select2.min.js"></script>
<script src="/crud/websmart/v13.2/js/jquery.maskedinput.min.js"></script>  
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
	
	
	$(document).ready(function() {
	    $(".categoria-input").select2({
		  placeholder: 'Select an option',
		  allowClear: true
		});
	    $(".categoria-input-edit").select2({
		  placeholder: 'Select an option' 
		});

		$(".categoria-input-edit").change(function(e){
			jcurVal = $(this).val();
			jABAN8 = $(this).attr("ABAN8");
			url = "?task=updCategoria&ABAN8="+jABAN8+"&categoria="+encodeURIComponent(jcurVal);
			$.get(url);	
		});
	
		$(".relazioni-input").change(function(e){
			jcurVal = $(this).val();
			jABAN8 = $(this).attr("ABAN8");
			url = "?task=updRelazioni&ABAN8="+jABAN8+"&relazioni="+encodeURIComponent(jcurVal);
			$.get(url);	
		});
		
		$(".note-input").change(function(e){
			jcurVal = $(this).val();
			jABAN8 = $(this).attr("ABAN8");
			url = "?task=updNote&ABAN8="+jABAN8+"&note="+encodeURIComponent(jcurVal);
			$.get(url);	
		});	

		$(".web-input").change(function(e){
			jcurVal = $(this).val();
			jABAN8 = $(this).attr("ABAN8");
			url = "?task=updweb&ABAN8="+jABAN8+"&web="+encodeURIComponent(jcurVal);
			$.get(url);	
		});	

		$('.calendario-8').datepicker({
			dateFormat: 'dd/mm/y',
			buttonImageOnly: false,
			changeMonth: true,   
			changeYear: true 
		}); 			
		$(".calendario-8").mask("99/99/99",{placeholder:" "});

	});	
	
	
	

	
	
	
</script>
  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
          crossorigin="anonymous"></script>
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
    <title>Categorie clienti - $mode</title>
    
    <link rel="stylesheet" href="/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/websmart/v13.2/css/jquery-ui.min.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    
    <script src="/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record display-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/websmart/v13.2/Responsive/images/company-logo.png" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Categorie clienti - $mode</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <div id="display-fields">
            <div class="form-group row">
              <label class="col-sm-4">Address Number . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$ABAN8</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Alpha Name . . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$ABALPH</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">City . . . . . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$ALCTY1</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Country. . . . . . . . . . . . . . . . .:</label>
              <div class="col-sm-8">$ALCTR</div>
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
    <title>Categorie clienti - Add</title>
    
    <link rel="stylesheet" href="/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/websmart/v13.2/css/jquery-ui.min.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    
    <script src="/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/websmart/v13.2/Responsive/images/company-logo.png" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Categorie clienti - Add</h1>
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
 $this->displayErrorClass('ABAN8'); 
		echo <<<SEGDTA
">
                <label for="addABAN8">Address Number . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('ABAN8'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addABAN8" class="form-control" name="ABAN8" size="8" maxlength="8" value="$ABAN8">
                  <span class="error-text">
SEGDTA;
 $this->displayError('ABAN8', array('Address Number . . . . . . . . . . . . .')); 
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
    <title>Categorie clienti - Change</title>
    
    <link rel="stylesheet" href="/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/websmart/v13.2/css/jquery-ui.min.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    
    <script src="/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/websmart/v13.2/Responsive/images/company-logo.png" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Categorie clienti - Change</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="change-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endchange" />
            <div id="changefields"><div class="notice 
SEGDTA;
 if(!$this->showRequiredIndicator) echo "hidden nodisplay"; 
		echo <<<SEGDTA
"><span class="required">{$this->requiredIndicator}</span> Denotes a required field.</div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('ABAN8'); 
		echo <<<SEGDTA
">
                <label for="chgABAN8">Address Number . . . . . . . . . . . . . 
SEGDTA;
 $this->displayIndicator('ABAN8'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgABAN8" class="form-control" name="ABAN8" size="8" maxlength="8" value="$ABAN8">
                  <span class="error-text">
SEGDTA;
 $this->displayError('ABAN8', array('Address Number . . . . . . . . . . . . .')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group">
                <label for="chgABALPH">Alpha Name . . . . . . . . . . . . . . .</label>
                <div id="chgABALPH">$ABALPH</div>
              </div>
              <div class="form-group">
                <label for="chgALCTY1">City . . . . . . . . . . . . . . . . . .</label>
                <div id="chgALCTY1">$ALCTY1</div>
              </div>
              <div class="form-group">
                <label for="chgALCTR">Country. . . . . . . . . . . . . . . . .</label>
                <div id="chgALCTR">$ALCTR</div>
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
		
		parent::__construct();

		$this->pf_scriptname = 'catecli.php';
		$this->pf_wcm_set = 'PRODUZIONE';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		
		// Last Generated CRC: 0DB7D047 C44318DD 323F7CAD AEA18E69
		// Last Generated Date: 2024-12-03 10:52:15
		// Path: catecli.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'catecli');?>

<!-- Popup nativo per AI -->
<div id="popupAI" style="display:none; position:fixed; left:0; top:0; width:100vw; height:100vh; z-index:9999; background:rgba(0,0,0,0.35);">
  <div style="background:#fff; padding:24px 20px 16px 20px; border-radius:8px; max-width:600px; min-width:320px; min-height:120px; box-shadow:0 4px 16px rgba(0,0,0,0.15); margin:80px auto 0 auto; position:relative;">
    <button onclick="document.getElementById('popupAI').style.display='none';" style="position:absolute;top:10px;right:10px;font-size:20px;background:none;border:none;">&times;</button>
    <div id="popupAIContent" style="white-space:pre-line; min-height:60px;">Attendere risposta...</div>
    <div style="text-align:right; margin-top:18px;">
      <button id="popupApplicaCategoria" style="display:none;" class="btn btn-primary btn-sm">Applica categoria</button>
      <button onclick="document.getElementById('popupAI').style.display='none';" class="btn btn-secondary btn-sm">Chiudi</button>
    </div>
  </div>
</div>
<script>
// Funzione aggiornata per usare popup nativo e mostrare risposta AI
async function inviaChat(codice, bottone) {
  const popup = document.getElementById('popupAI');
  const popupContent = document.getElementById('popupAIContent');
  popupContent.textContent = "Analisi in corso...";
  popup.style.display = 'block';
  document.getElementById('popupApplicaCategoria').style.display = 'none';

  try {
    const clienteRow = bottone.closest('tr');

    const sito = clienteRow.querySelector("input.web-input")?.value || '';
    const categoria = clienteRow.querySelector("select.categoria-input-edit")?.value || '';
    const ragioneSociale = clienteRow.querySelector("td:nth-child(3)")?.innerText || '';
    const relazioni = clienteRow.querySelector("input.relazioni-input")?.value || '';
    const note = clienteRow.querySelector("input.note-input")?.value || '';
    const città = clienteRow.querySelector("td:nth-child(4)")?.innerText || '';
    const stato = clienteRow.querySelector("td:nth-child(5)")?.innerText || '';

    const messaggio = sito
      ? `Il cliente ${codice} (${ragioneSociale}) non ha una categoria assegnata.
Relazioni aziendali: ${relazioni}.
Note: ${note}.
Sito internet: ${sito}.
Analizza le informazioni disponibili e, se possibile, visita il sito per capire di che settore si occupa e suggerire una categoria coerente.`
      : `Il cliente ${codice} (${ragioneSociale}) non ha una categoria assegnata e non ha un sito registrato.
Relazioni aziendali: ${relazioni}.
Note: ${note}.
Città: ${città}, Stato: ${stato}.
Cerca online il sito ufficiale del cliente e suggerisci la categoria di attività più coerente.`;

    const response = await fetch('/AI/connect.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: messaggio })
    });

    const data = await response.json();
    popupContent.textContent = data.reply || "Nessuna risposta ricevuta.";

    const btnApplica = document.getElementById('popupApplicaCategoria');
    btnApplica.style.display = 'inline-block';
    btnApplica.onclick = function () {
      const select = clienteRow.querySelector("select.categoria-input-edit");
      if (select && data.reply) {
        const suggerita = data.reply.trim().split("\n")[0];
        select.value = suggerita;
      }
      popup.style.display = 'none';
    };
  } catch (err) {
    popupContent.textContent = "Errore nella richiesta.";
  }
}
</script>