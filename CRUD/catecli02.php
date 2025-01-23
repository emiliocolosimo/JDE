<?php
if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

//	Program Name:		catecli02.php
//	Program Title:		Manutenzione categorie
//	Created by:			matti
//	Template family:	Responsive
//	Template name:		Page at a Time.tpl
//	Purpose:        	Maintain a database file using embedded SQL. Supports options for add, change, delete and display.
//	Program Modifications:


require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');
require_once('websmart/v13.2/include/en-US/WSRequiredValidator.php');
require_once('websmart/v13.2/include/en-US/WSNumericValidator.php');

class catecli02 extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'page' => 1,
		'listSize' => 20,
		'filters' => array('CTKY1' => '')
	);
	
	
	protected $keyFields = array('RRNF');
	protected $uniqueFields = array('RRNF');
	
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
			"CTKY1" => array("validators"=> array("WSRequired")),
			"CTKY2" => array("validators"=> array("WSRequired")),
			"CTKY3" => array("validators"=> array("WSRequired")),
			"CTKY4" => array("validators"=> array("WSRequired")));
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
		$RRNF = (int) xl_get_parameter("RRNF");
		if($RRNF == 0) exit;

		$selString = "SELECT * 
		FROM JRGDTA94C.ANCATCL0F 
		WHERE RRN(JRGDTA94C.ANCATCL0F) = :RRNF 
		";
		$stmt = $this->db_connection->prepare($selString); 
		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT); 
		$result = $stmt->execute(); 
		$row = $stmt->fetch(PDO::FETCH_ASSOC); 
		// Sanitize the fields
		foreach(array_keys($row) as $key)
		{
			$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1");
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
		$RRNF = (int) xl_get_parameter("RRNF");
		if($RRNF == 0) exit;

		// Prepare and execute the SQL statement to delete the record
		$delString = 'DELETE FROM JRGDTA94C.ANCATCL0F WHERE RRN(JRGDTA94C.ANCATCL0F) = :RRNF WITH NC';
		$stmt = $this->db_connection->prepare($delString);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the key parameters
		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT); 
		
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
		$CTKY1 = "";
		$CTKY2 = "";
		$CTKY3 = "";
		$CTKY4 = "";
		
		// Output the segment
		$this->writeSegment('RcdAdd', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	// Add the passed in data as a new row
	protected function endAdd()
	{
		// Get values from the page
		
		$CTKY1 = xl_get_parameter('CTKY1');
		$CTKY2 = xl_get_parameter('CTKY2');
		$CTKY3 = xl_get_parameter('CTKY3');
		$CTKY4 = xl_get_parameter('CTKY4');
		  
	 
		// Prepare the statement to add the record
		$insertSql = 'INSERT INTO JRGDTA94C.ANCATCL0F (CTKY1, CTKY2, CTKY3, CTKY4) VALUES(:CTKY1, :CTKY2, :CTKY3, :CTKY4)  WITH NC';
		$stmt = $this->db_connection->prepare($insertSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':CTKY1', $CTKY1, PDO::PARAM_STR);
		$stmt->bindValue(':CTKY2', $CTKY2, PDO::PARAM_STR);
		$stmt->bindValue(':CTKY3', $CTKY3, PDO::PARAM_STR);
		$stmt->bindValue(':CTKY4', $CTKY4, PDO::PARAM_STR);
		
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
		
		$RRNF = (int) xl_get_parameter("RRNF");
		if($RRNF == 0) exit;
		
		$selString = "SELECT * 
		FROM JRGDTA94C.ANCATCL0F 
		WHERE RRN(JRGDTA94C.ANCATCL0F) = :RRNF 
		";
		$stmt = $this->db_connection->prepare($selString); 
		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT); 
		$result = $stmt->execute(); 
		$row = $stmt->fetch(PDO::FETCH_ASSOC); 
		// Sanitize the fields
		foreach(array_keys($row) as $key)
		{
			$row[$key] = htmlspecialchars(rtrim($row[$key]),ENT_QUOTES,"ISO-8859-1");
			$escapedField = xl_fieldEscape($key);
			$$escapedField = $row[$key];
		}
		
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
		$RRNF = (int) xl_get_parameter("RRNF");
		if($RRNF==0) exit;
		
		//recupero CTKY1 attuale:
		$selString = "SELECT JRGDTA94C.ANCATCL0F.CTKY1 
		FROM JRGDTA94C.ANCATCL0F 
		WHERE RRN(JRGDTA94C.ANCATCL0F) = :RRNF
		";
		$stmt_r = $this->db_connection->prepare($selString);
		$stmt_r->bindValue(':RRNF', $RRNF, PDO::PARAM_INT);
		$result_r = $stmt_r->execute();
		$row_r = $stmt_r->fetch(PDO::FETCH_ASSOC);
		$CTKY1_old = $row_r["CTKY1"];
		 
		
		// Get values from the page
		$CTKY1 = xl_get_parameter('CTKY1');
		$CTKY2 = xl_get_parameter('CTKY2');
		$CTKY3 = xl_get_parameter('CTKY3');
		$CTKY4 = xl_get_parameter('CTKY4');
		 
		// Construct and prepare the SQL to update the record
		$updateSql = 'UPDATE JRGDTA94C.ANCATCL0F SET CTKY1 = :CTKY1, CTKY2 = :CTKY2, CTKY3 = :CTKY3, CTKY4 = :CTKY4';
		$updateSql .= ' WHERE RRN(JRGDTA94C.ANCATCL0F) = :RRNF WITH NC';
		$stmt = $this->db_connection->prepare($updateSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':CTKY1', $CTKY1, PDO::PARAM_STR);
		$stmt->bindValue(':CTKY2', $CTKY2, PDO::PARAM_STR);
		$stmt->bindValue(':CTKY3', $CTKY3, PDO::PARAM_STR);
		$stmt->bindValue(':CTKY4', $CTKY4, PDO::PARAM_STR);
		$stmt->bindValue(':RRNF', $RRNF, PDO::PARAM_INT);
		
		// Execute the update statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		//Aggiornare anche quello che c'è in SPCATCL0F 
		$updateSql = "UPDATE JRGDTA94C.SPCATCL0F SET CTKY1 = :CTKY1 WHERE CTKY1 = :CTKY1_old WITH NC";
		$stmt2 = $this->db_connection->prepare($updateSql);
		$stmt2->bindValue(':CTKY1', $CTKY1, PDO::PARAM_STR);
		$stmt2->bindValue(':CTKY1_old', $CTKY1_old, PDO::PARAM_STR);
		$result = $stmt2->execute();
		 
		
		// Redirect to the original page of the main list
		header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
	}
	
	// Load list with filters
	protected function filterList()
	{
		// Retrieve the filter information
		$this->programState['filters']['CTKY1'] = xl_get_parameter('filter_CTKY1');
		
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
		if ($this->programState['filters']['CTKY1'] != '')
		{
			$stmt->bindValue(':CTKY1', '%' . strtolower($this->programState['filters']['CTKY1']) . '%', PDO::PARAM_STR);
		}
		
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildSelectString()
	{
		$selString = 'SELECT RRN(JRGDTA94C.ANCATCL0F) AS RRNF, JRGDTA94C.ANCATCL0F.CTKY1, JRGDTA94C.ANCATCL0F.CTKY2, JRGDTA94C.ANCATCL0F.CTKY3, JRGDTA94C.ANCATCL0F.CTKY4 FROM JRGDTA94C.ANCATCL0F';
		
		return $selString;
	}
	
	// Build where clause to filter rows from table
	protected function buildWhereClause()
	{
		$whereClause = '';
		$link = 'WHERE ';
		
		// Filter by CTKY1
		if ($this->programState['filters']['CTKY1'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(JRGDTA94C.ANCATCL0F.CTKY1) LIKE :CTKY1';
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
			
		} else {
			
		}
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildRecordSelectString()
	{
		$selString = 'SELECT JRGDTA94C.ANCATCL0F.CTKY1, JRGDTA94C.ANCATCL0F.CTKY2, JRGDTA94C.ANCATCL0F.CTKY3, JRGDTA94C.ANCATCL0F.CTKY4 FROM JRGDTA94C.ANCATCL0F';
		
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
		$selString = 'SELECT COUNT(*) FROM JRGDTA94C.ANCATCL0F';
		
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
    <title>Manutenzione categorie</title>
    
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
          <h1 class="title">Manutenzione categorie</h1>
          <span class="add-link">
            
            <a class="btn btn-primary btn-sm" href="$pf_scriptname?task=beginadd&amp;rnd=$rnd">
              <span class="glyphicon glyphicon-plus"></span> Aggiungi nuova categoria
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
                  <label for="filter_CTKY1">Categoria livello uno</label>
                  <input id="filter_CTKY1" class="form-control" type="text" name="filter_CTKY1" maxlength="100" value="{$programState['filters']['CTKY1']}"/>
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
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=CTKY1&amp;rnd=$rnd">Categoria livello uno</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=CTKY2&amp;rnd=$rnd">Categoria livello due</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=CTKY3&amp;rnd=$rnd">Categoria livello tre</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=CTKY4&amp;rnd=$rnd">Categoria livello quattro</a>
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
      <!--<a class="btn btn-default btn-xs glyphicon glyphicon-file" title="View this record" href="$pf_scriptname?task=disp&amp;rnd=$rnd"></a> -->
      <a class="btn btn-default btn-xs glyphicon glyphicon-pencil" title="Change this record" href="$pf_scriptname?task=beginchange&RRNF=$RRNF&amp;rnd=$rnd"></a> 
      <a class="btn btn-default btn-xs glyphicon glyphicon-remove" title="Delete this record" href="$pf_scriptname?task=delconf&RRNF=$RRNF"></a>
    </span>
  </td> 
  <td class="text">$CTKY1</td>
  <td class="text">$CTKY2</td>
  <td class="text">$CTKY3</td>
  <td class="text">$CTKY4</td>
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
    <title>Manutenzione categorie - $mode</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record display-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Manutenzione categorie - $mode</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <div id="display-fields">
            <div class="form-group row">
              <label class="col-sm-2">Categoria livello uno:</label>
              <div class="col-sm-8">$CTKY1</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2">Categoria livello due:</label>
              <div class="col-sm-8">$CTKY2</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2">Categoria livello tre:</label>
              <div class="col-sm-8">$CTKY3</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2">Categoria livello quattro:</label>
              <div class="col-sm-8">$CTKY4</div>
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
  <input type="hidden" name="RRNF" value="$RRNF" />
  
  
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
    <title>Manutenzione categorie - Add</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Manutenzione categorie - Add</h1>
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
 $this->displayErrorClass('CTKY1'); 
		echo <<<SEGDTA
">
                <label for="addCTKY1">Categoria livello uno 
SEGDTA;
 $this->displayIndicator('CTKY1'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addCTKY1" class="form-control" name="CTKY1" size="100" maxlength="100" value="$CTKY1">
                  <span class="error-text">
SEGDTA;
 $this->displayError('CTKY1', array('Categoria livello uno')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('CTKY2'); 
		echo <<<SEGDTA
">
                <label for="addCTKY2">Categoria livello due 
SEGDTA;
 $this->displayIndicator('CTKY2'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addCTKY2" class="form-control" name="CTKY2" size="100" maxlength="100" value="$CTKY2">
                  <span class="error-text">
SEGDTA;
 $this->displayError('CTKY2', array('Categoria livello due')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('CTKY3'); 
		echo <<<SEGDTA
">
                <label for="addCTKY3">Categoria livello tre 
SEGDTA;
 $this->displayIndicator('CTKY3'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addCTKY3" class="form-control" name="CTKY3" size="100" maxlength="100" value="$CTKY3">
                  <span class="error-text">
SEGDTA;
 $this->displayError('CTKY3', array('Categoria livello tre')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('CTKY4'); 
		echo <<<SEGDTA
">
                <label for="addCTKY4">Categoria livello quattro 
SEGDTA;
 $this->displayIndicator('CTKY4'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addCTKY4" class="form-control" name="CTKY4" size="100" maxlength="100" value="$CTKY4">
                  <span class="error-text">
SEGDTA;
 $this->displayError('CTKY4', array('Categoria livello quattro')); 
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
    <title>Manutenzione categorie</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Manutenzione categorie</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="change-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endchange" />
            <input type="hidden" name="RRNF" value="$RRNF" />
            
            <div id="changefields"><div class="notice 
SEGDTA;
 if(!$this->showRequiredIndicator) echo "hidden nodisplay"; 
		echo <<<SEGDTA
"><span class="required">{$this->requiredIndicator}</span> Denotes a required field.</div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('CTKY1'); 
		echo <<<SEGDTA
">
                <label for="chgCTKY1">Categoria livello uno 
SEGDTA;
 $this->displayIndicator('CTKY1'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgCTKY1" class="form-control" name="CTKY1" size="100" maxlength="100" value="$CTKY1">
                  <span class="error-text">
SEGDTA;
 $this->displayError('CTKY1', array('Categoria livello uno')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('CTKY2'); 
		echo <<<SEGDTA
">
                <label for="chgCTKY2">Categoria livello due 
SEGDTA;
 $this->displayIndicator('CTKY2'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgCTKY2" class="form-control" name="CTKY2" size="100" maxlength="100" value="$CTKY2">
                  <span class="error-text">
SEGDTA;
 $this->displayError('CTKY2', array('Categoria livello due')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('CTKY3'); 
		echo <<<SEGDTA
">
                <label for="chgCTKY3">Categoria livello tre 
SEGDTA;
 $this->displayIndicator('CTKY3'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgCTKY3" class="form-control" name="CTKY3" size="100" maxlength="100" value="$CTKY3">
                  <span class="error-text">
SEGDTA;
 $this->displayError('CTKY3', array('Categoria livello tre')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('CTKY4'); 
		echo <<<SEGDTA
">
                <label for="chgCTKY4">Categoria livello quattro 
SEGDTA;
 $this->displayIndicator('CTKY4'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgCTKY4" class="form-control" name="CTKY4" size="100" maxlength="100" value="$CTKY4">
                  <span class="error-text">
SEGDTA;
 $this->displayError('CTKY4', array('Categoria livello quattro')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>	
            </div>
            <div id="navbottom">
              <input type="submit" class="btn btn-primary accept" value="Conferma" />
              <input type="button" class="btn btn-default cancel" value="Indietro" />
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

		$this->pf_scriptname = 'catecli02.php';
		$this->pf_wcm_set = 'PRODUZIONE';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		
		// Last Generated CRC: CAE4AD6C C974E43F 8AEBA93E B74BAAC5
		// Last Generated Date: 2024-05-28 12:32:43
		// Path: catecli02.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'catecli02');?>