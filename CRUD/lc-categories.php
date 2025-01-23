<?php

header("Content-type: text/html; charset=ISO-8859-1");

if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

//	Program Name:		lc-categories.php
//	Program Title:		LeadChampions - Categorie
//	Created by:			matti
//	Template family:	Responsive
//	Template name:		Page at a Time.tpl
//	Purpose:        	Maintain a database file using embedded SQL. Supports options for add, change, delete and display.
//	Program Modifications:


require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');
require_once('websmart/v13.2/include/en-US/WSRequiredValidator.php');
require_once('websmart/v13.2/include/en-US/WSNumericValidator.php');

class lc_categories extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'page' => 1,
		'listSize' => 20,
		'filters' => array('ACLCCA' => '', 'ACASCA' => '')
	);
	
	
	protected $keyFields = array('ACLCCA');
	protected $uniqueFields = array('ACLCCA');
	
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
		
		// Fetch the program state
		$this->getState();
		
		$this->formFields = array(
			"ACLCCA" => array("validators"=> array("WSRequired")),
			"ACASCA" => array("validators"=> array("WSRequired")));
		$this->optionalIndicator = "(Optional)";
		
		// Run the specified task
		switch ($this->pf_task)
		{
			// Display the main list
			case 'default':
			$this->displayList();
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
		
			case 'updAssoc':
			$this->updAssoc();
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
		$ACLCCA_url = urlencode(rtrim($row['ACLCCA']));
		
		// Sanitize the fields
		foreach(array_keys($row) as $key)
		{
			$row[$key] = htmlspecialchars(rtrim($row[$key]),0,"ISO-8859-1");
			
			
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
		$delString = 'DELETE FROM JRGDTA94C.LCASCA0F ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($delString);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the key parameters
		$stmt->bindValue(':ACLCCA_', $keyFieldArray['ACLCCA'], PDO::PARAM_STR);
		
		// Execute the delete statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
		header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
	}
	
	protected function updAssoc() {
		
		$query = "update JRGDTA94C.LCcomp0F set lacate=(select LOWER(ACASCA) from JRGDTA94C.LCASCA0F     
		where lacate=ACLCCA and ACASCA<>''),
		LAISHI = (select ACAVIS from JRGDTA94C.LCASCA0F     
		where lacate=ACLCCA)
		where exists(select * from JRGDTA94C.LCASCA0F    
		where lacate=ACLCCA and ACASCA<>'') WITH NC";
		$stmt = $this->db_connection->prepare($query);
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		 
		header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
	}
	
	// Show the add page
	protected function beginAdd()
	{
		$ACLCCA = "";
		$ACASCA = "";
		$ACDTIN = "";
		$ACORIN = "";
		
		// Output the segment
		$this->writeSegment('RcdAdd', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	// Add the passed in data as a new row
	protected function endAdd()
	{
		// Get values from the page
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		extract($keyFieldArray);
		$ACASCA = xl_get_parameter('ACASCA');
		$ACAVIS = xl_get_parameter('ACAVIS');
		
		
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
		
		$ACDTIN = date("Ymd");
		$ACORIN = date("His");
		
		// Prepare the statement to add the record
		$insertSql = 'INSERT INTO JRGDTA94C.LCASCA0F (ACLCCA, ACASCA, ACDTIN, ACORIN, ACAVIS) VALUES(:ACLCCA, :ACASCA, :ACDTIN, :ACORIN, :ACAVIS)' . ' WITH NC';
		$stmt = $this->db_connection->prepare($insertSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':ACLCCA', $ACLCCA, PDO::PARAM_STR);
		$stmt->bindValue(':ACASCA', $ACASCA, PDO::PARAM_STR);
		$stmt->bindValue(':ACDTIN', $ACDTIN, PDO::PARAM_INT);
		$stmt->bindValue(':ACORIN', $ACORIN, PDO::PARAM_INT);
		$stmt->bindValue(':ACAVIS', $ACAVIS, PDO::PARAM_STR);
		
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
			
			$row[$key] = htmlspecialchars(rtrim($row[$key]),0,"ISO-8859-1");
			
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
		$oldKeyFieldArray = $this->getParameters(array('ACLCCA_'));
		
		extract($oldKeyFieldArray);
		// Get values from the page
		$ACASCA = xl_get_parameter('ACASCA');
		$ACAVIS = xl_get_parameter('ACAVIS');
 		
		//Protect Key Fields from being Changed
		$ACLCCA = $ACLCCA_;
		
		// Do any validation
		/*
		$isValid = $this->validate(xl_FieldEscape($this->uniqueFields));
		
		if(!$isValid)
		{
			$record = $this->getRecord($oldKeyFieldArray, array_keys($this->formFields), true);
			extract($record);
			$this->writeSegment('RcdChange', array_merge(get_object_vars($this), get_defined_vars()));
			return;
		}
		*/
		
		$ACDTIN = date("Ymd");
		$ACORIN = date("His");		
		
		// Construct and prepare the SQL to update the record
		$updateSql = 'UPDATE JRGDTA94C.LCASCA0F SET ACASCA = :ACASCA, ACDTIN = :ACDTIN, ACORIN = :ACORIN, ACAVIS = :ACAVIS ';
		$updateSql .= ' ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($updateSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':ACASCA', $ACASCA, PDO::PARAM_STR);
		$stmt->bindValue(':ACDTIN', $ACDTIN, PDO::PARAM_INT);
		$stmt->bindValue(':ACORIN', $ACORIN, PDO::PARAM_INT);
		$stmt->bindValue(':ACAVIS', $ACAVIS, PDO::PARAM_STR);
		$stmt->bindValue(':ACLCCA_', $ACLCCA_, PDO::PARAM_STR);
		
		
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
		$this->programState['filters']['ACLCCA'] = xl_get_parameter('filter_ACLCCA');
		$this->programState['filters']['ACASCA'] = xl_get_parameter('filter_ACASCA');
		
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
			$ACLCCA_url = urlencode(rtrim($row['ACLCCA']));
			
			// Sanitize the fields
			foreach(array_keys($row) as $key)
			{
				$row[$key] = htmlspecialchars(rtrim($row[$key]),0,"ISO-8859-1"); 
				
				
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
		if ($this->programState['filters']['ACLCCA'] != '')
		{
			$stmt->bindValue(':ACLCCA', '%' . strtoupper($this->programState['filters']['ACLCCA']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['ACASCA'] != '')
		{
			$stmt->bindValue(':ACASCA', '%' . strtoupper($this->programState['filters']['ACASCA']) . '%', PDO::PARAM_STR);
		}
		
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildSelectString()
	{
		$selString = 'SELECT JRGDTA94C.LCASCA0F.ACLCCA, JRGDTA94C.LCASCA0F.ACASCA, JRGDTA94C.LCASCA0F.ACDTIN, JRGDTA94C.LCASCA0F.ACORIN, JRGDTA94C.LCASCA0F.ACAVIS FROM JRGDTA94C.LCASCA0F';
		
		return $selString;
	}
	
	// Build where clause to filter rows from table
	protected function buildWhereClause()
	{
		$whereClause = '';
		$link = 'WHERE ';
		
		// Filter by ACLCCA
		if ($this->programState['filters']['ACLCCA'] != '')
		{
			$whereClause = $whereClause . $link . ' UPPER(JRGDTA94C.LCASCA0F.ACLCCA) LIKE :ACLCCA';
			$link = " AND ";
		}
		
		// Filter by ACASCA
		if ($this->programState['filters']['ACASCA'] != '')
		{
			$whereClause = $whereClause . $link . ' UPPER(JRGDTA94C.LCASCA0F.ACASCA) LIKE :ACASCA';
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
			$stmt->bindValue(':ACLCCA_', $keyFieldArray['ACLCCA'], PDO::PARAM_STR);
			
		} else {
			$stmt->bindValue(':ACLCCA_', $keyFieldArray['ACLCCA_'], PDO::PARAM_STR);
			
		}
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildRecordSelectString()
	{
		$selString = 'SELECT JRGDTA94C.LCASCA0F.ACLCCA, JRGDTA94C.LCASCA0F.ACASCA, JRGDTA94C.LCASCA0F.ACDTIN, JRGDTA94C.LCASCA0F.ACORIN, JRGDTA94C.LCASCA0F.ACAVIS FROM JRGDTA94C.LCASCA0F';
		
		return $selString;
	}
	
	// Build where clause to filter single entries
	protected function buildRecordWhere()
	{
		$whereClause = 'WHERE JRGDTA94C.LCASCA0F.ACLCCA = :ACLCCA_';
		
		return $whereClause;
	}
	
	// Return the SELECT SQL for a count on the primary file
	protected function getPrimaryFileCountSelect()
	{
		$selString = 'SELECT COUNT(*) FROM JRGDTA94C.LCASCA0F';
		
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
		$stmt->bindValue(':ACLCCA_', $keyFieldArray['ACLCCA'], PDO::PARAM_STR);
		
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
    <title>LeadChampions - Categorie</title>
    
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/CRUD/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/CRUD/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
	 
  </head>
  <body class="display-list">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">LeadChampions - Categorie</h1>
          <span class="add-link">

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
                  <label for="filter_ACLCCA">Categoria Lead Champion</label>
                  <input id="filter_ACLCCA" class="form-control" type="text" name="filter_ACLCCA" maxlength="255" value="{$programState['filters']['ACLCCA']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_ACASCA">Categoria associata</label>
                  <input id="filter_ACASCA" class="form-control" type="text" name="filter_ACASCA" maxlength="255" value="{$programState['filters']['ACASCA']}"/>
                </div> 
              </div>
			  
			  <div class="row">
				  <div class="col-sm-4">
				    <input id="filter-button" class="btn btn-primary filter btn-sm" type="submit" value="Cerca" /> &nbsp;&nbsp;&nbsp;&nbsp;
				  
					<a class="btn btn-warning btn-sm" href="$pf_scriptname?task=beginadd&amp;rnd=$rnd">
					  <span class="glyphicon glyphicon-plus"></span> Aggiungi associazione
					</a>
					
					<a class="btn btn-info btn-sm" href="$pf_scriptname?task=updAssoc&amp;rnd=$rnd">
					  <i class="glyphicon glyphicon-refresh"></i> Aggiorna associazioni
					</a>
					 
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
          <table id="list-table" class="main-list table table-striped" cellspacing="0" style="width:auto;">
            <thead>
              <tr class="list-header">
                <th class="actions"> </th> 
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=ACLCCA&amp;rnd=$rnd">Categoria Lead Champion</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=ACASCA&amp;rnd=$rnd">Categoria associata</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=ACAVIS&amp;rnd=$rnd">Nascosto?</a>
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
      <a class="btn btn-default btn-xs glyphicon glyphicon-pencil" title="Change this record" href="$pf_scriptname?task=beginchange&amp;ACLCCA=$ACLCCA_url&amp;rnd=$rnd"></a> 
      <a class="btn btn-default btn-xs glyphicon glyphicon-remove" title="Delete this record" href="$pf_scriptname?task=delconf&amp;ACLCCA=$ACLCCA_url"></a>
    </span>
  </td> 
  <td class="text">$ACLCCA</td>
  <td class="text">$ACASCA</td>
  <td class="text">$ACAVIS</td>
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
    <title>LeadChampions - Categorie - $mode</title>
    
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/CRUD/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/CRUD/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record display-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">LeadChampions - Categorie - $mode</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <div id="display-fields">
            <div class="form-group row">
              <label class="col-sm-4">Categoria Lead Champion:</label>
              <div class="col-sm-8">$ACLCCA</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">Categoria associata:</label>
              <div class="col-sm-8">$ACASCA</div>
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
  <input id="ACLCCA" type="hidden" name="ACLCCA" value="$ACLCCA" />
  
  <p>Vuoi cancellare questo record?</p>
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
    <title>LeadChampions - Categorie - Add</title>
    
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/CRUD/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/CRUD/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">LeadChampions - Categorie - Add</h1>
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
 $this->displayErrorClass('ACLCCA'); 
		echo <<<SEGDTA
">
                <label for="addACLCCA">
                Categoria Lead Champion 
SEGDTA;
 $this->displayIndicator('ACLCCA'); 
		echo <<<SEGDTA

                <span class="error-text">
SEGDTA;
 $this->displayError('ACLCCA', array('Categoria Lead Champion')); 
		echo <<<SEGDTA
</span>
                </label>
                <div>
                  <input type="text" id="addACLCCA" class="form-control" name="ACLCCA" maxlength="255" value="" />
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('ACASCA'); 
		echo <<<SEGDTA
">
                <label for="addACASCA">
                Categoria associata 
SEGDTA;
 $this->displayIndicator('ACASCA'); 
		echo <<<SEGDTA

                <span class="error-text">
SEGDTA;
 $this->displayError('ACASCA', array('Categoria associata')); 
		echo <<<SEGDTA
</span>
                </label>
                <div>
                  <input type="text" id="addACASCA" class="form-control" name="ACASCA" maxlength="255" value="" />
                </div>
              </div>	
			  
				<div class="form-group">
					<label for="chgACAVIS"></label>
					<div class="checkbox">
						<label>
						  <input type="checkbox" id="chgACAVIS" name="ACAVIS" class="checkbox" value="1">
						  <span>Nascosto</span>
						</label>
					</div>
				</div>	
			  
            </div>
            <div id="navbottom">
              <input type="submit" class="btn btn-primary accept" value="Conferma" />
              <input type="button" class="btn btn-default cancel" value="Annulla" />
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
    <title>LeadChampions - Categorie - Change</title>
    
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/CRUD/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/CRUD/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/CRUD/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">LeadChampions - Categorie - Change</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="change-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endchange" />
            <input id="ACLCCA_" type="hidden" name="ACLCCA_" value="$ACLCCA" />
            <div id="changefields"><div class="notice 
SEGDTA;
 if(!$this->showRequiredIndicator) echo "hidden nodisplay"; 
		echo <<<SEGDTA
"><span class="required">{$this->requiredIndicator}</span> Denotes a required field.</div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('ACLCCA'); 
		echo <<<SEGDTA
">
                <label for="chgACLCCA">
                Categoria Lead Champion 
SEGDTA;
 $this->displayIndicator('ACLCCA'); 
		echo <<<SEGDTA

                <span class="error-text">
SEGDTA;
 $this->displayError('ACLCCA', array('Categoria Lead Champion')); 
		echo <<<SEGDTA
</span>
                </label>
                <div>
                  <input type="text" id="chgACLCCA" class="form-control" name="ACLCCA" maxlength="255" value="$ACLCCA" />
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('ACASCA'); 
		echo <<<SEGDTA
">
                <label for="chgACASCA">
                Categoria associata 
SEGDTA;
 $this->displayIndicator('ACASCA'); 
		echo <<<SEGDTA

                <span class="error-text">
SEGDTA;
 $this->displayError('ACASCA', array('Categoria associata')); 
		echo <<<SEGDTA
</span>
                </label>
                <div>
                  <input type="text" id="chgACASCA" class="form-control" name="ACASCA" maxlength="255" value="$ACASCA" /> 
                </div>
              </div>
               
				<div class="form-group">
					<label for="chgACAVIS"></label>
					<div class="checkbox">
						<label>
						  <input type="checkbox" id="chgACAVIS" name="ACAVIS" class="checkbox" value="1" 
SEGDTA;
if($ACAVIS=="1") echo " checked=\"checked\" ";
		echo <<<SEGDTA
		
						  >
						  <span>Nascosto</span>
						</label>
					</div>
				</div>	
			   
			   
            </div>
            <div id="navbottom">
              <input type="submit" class="btn btn-primary accept" value="Conferma" />
              <input type="button" class="btn btn-default cancel" value="Annulla" />
            </div>		
          </form>
        </div>
      </div>
    </div>
    <script type="text/javascript">
		jQuery(function() {
			
			jQuery("input[name='ACLCCA']").attr("disabled",true);
			
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

		$this->pf_scriptname = 'lc-categories.php';
		$this->pf_wcm_set = '';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		
		// Last Generated CRC: 25259FD8 815BB4B5 49F9D83C 04272C0B
		// Last Generated Date: 2024-02-27 12:15:50
		// Path: C:\Users\matti\OneDrive\Desktop\lc-categories.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'lc_categories');?>