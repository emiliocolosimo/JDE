<?php
if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

//	Program Name:		calc_sfere.php
//	Program Title:		Calcolo sfere
//	Created by:			matti
//	Template family:	Responsive
//	Template name:		Page at a Time.tpl
//	Purpose:        	Maintain a database file using embedded SQL. Supports options for add, change, delete and display.
//	Program Modifications:


require_once('websmart/v13.2/include/WebSmartObject.php');
require_once('websmart/v13.2/include/xl_functions.php');
require_once('websmart/v13.2/include/en-US/WSRequiredValidator.php');
require_once('websmart/v13.2/include/en-US/WSNumericValidator.php');

class calc_sfere extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'page' => 1,
		'listSize' => 20,
		'filters' => array('IMLITM' => '')
	);
	
	
	protected $keyFields = array('IMITM');
	protected $uniqueFields = array('IMITM');
	
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
			"IMLITM" => array("validators"=> array("WSRequired")),
			"IMDSC1" => array("validators"=> array("WSRequired")));
		$this->optionalIndicator = "(Optional)";
		
		// Run the specified task
		switch ($this->pf_task)
		{
			// Display the main list
			case 'default':
			$this->generic();
			break;
			
			// Record display option
			case 'disp':
			$this->displayRecord();
			break;
			
			// Output a filtered list
			case 'filter':
			$this->filterList();
			break;
			
			case 'calcola':
			$this->calcola();
			break;
			
			
		}
	}
	 
	protected function calcola()
	{
		$fld1 = xl_get_parameter("fld1");
		$fld2 = xl_get_parameter("fld2");
		$UMCONV = xl_get_parameter("UMCONV");
		
		$fld1 = str_replace(",",".",$fld1);
		$fld2 = str_replace(",",".",$fld2);
		
		$errMsg = '';
		$errSep = '';
		if($fld1=="") {
			$errMsg.=$errSep.'{"stat":"err","id":"fld1","msg":"Campo obbligatorio"}';	
			$errSep = ",";
		}
		if($fld2=="") {
			$errMsg.=$errSep.'{"stat":"err","id":"fld2","msg":"Campo obbligatorio"}';	
			$errSep = ",";
		}
		if(!is_numeric($fld1)) {
			$errMsg.=$errSep.'{"stat":"err","id":"fld1","msg":"Valore non valido"}';	
			$errSep = ",";
		}
		if(!is_numeric($fld2)) {
			$errMsg.=$errSep.'{"stat":"err","id":"fld2","msg":"Valore non valido"}';	
			$errSep = ",";
		}		
		
		if($errMsg!="") {
			die("[".$errMsg."]");	
		}
		
		$risultato1 = $fld1 / ($UMCONV/10000000);
		$risultato2 = $fld2 * ($UMCONV/10000000);
		
		echo '[{"stat":"OK","res1":"'.number_format($risultato1,8,",",".").'","res2":"'.number_format($risultato2,8,",",".").'"}]';
		
	}

	// Update the program state, and show the current page of entries
	protected function generic()
	{
		// Update the program state
		$this->updateState();
		
		// Build current page of records
		$this->writeSegment('MainSeg', array_merge(get_object_vars($this), get_defined_vars()));
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
		$keyFieldArray['IMITM'] = (int) $keyFieldArray['IMITM'];
		
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
		$IMITM_url = urlencode(rtrim($row['IMITM']));
		
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
	
	// Load list with filters
	protected function filterList()
	{
		// Retrieve the filter information
		$this->programState['filters']['IMLITM'] = xl_get_parameter('filter_IMLITM');
		 
		// Update the program state
		$this->updateState();
		
		$query = "SELECT JRGDTA94C.F4101.IMITM, JRGDTA94C.F4101.IMDSC1, JRGDTA94C.F4101.IMLITM, JRGDTA94C.F41002.UMCONV 
		FROM JRGDTA94C.F4101 
		inner join JRGDTA94C.F41002 on JRGDTA94C.F4101.IMITM = JRGDTA94C.F41002.UMITM 
		WHERE JRGDTA94C.F4101.IMLITM = :IMLITM  
		";
		// Prepare the statement
		$stmt = $this->db_connection->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		if (!$stmt)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Bind the filter parameters
		$stmt->bindValue(':IMLITM', $this->programState['filters']['IMLITM'], PDO::PARAM_STR);
		 
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		} 
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// Sanitize the fields
		foreach(array_keys($row) as $key)
		{
			$row[$key] = htmlspecialchars(rtrim($row[$key]));
			
			
			// make the file field names available in HTML
			$escapedField = xl_fieldEscape($key);
			$$escapedField = $row[$key];
		}		 
		 
		 
		$pesoSingolaSfera = $UMCONV / 10000000;
		$numSfereAlKg = floor(1 / $pesoSingolaSfera);
		 
		$this->writeSegment('RcdDisplay', array_merge(get_object_vars($this), get_defined_vars()));
		
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
			$IMITM_url = urlencode(rtrim($row['IMITM']));
			
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
		$selString .= ' ' . $this->buildOrderBy();
		
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the filter parameters
		if ($this->programState['filters']['IMLITM'] != '')
		{
			$stmt->bindValue(':IMLITM', '%' . $this->programState['filters']['IMLITM'] . '%', PDO::PARAM_STR);
		}
		
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildSelectString()
	{
		$selString = 'SELECT JRGDTA94C.F4101.IMITM, JRGDTA94C.F4101.IMLITM, JRGDTA94C.F41002.UMCONV FROM JRGDTA94C.F4101 inner join JRGDTA94C.F41002 on JRGDTA94C.F4101.IMITM = JRGDTA94C.F41002.UMITM';
		
		return $selString;
	}
	
	// Build where clause to filter rows from table
	protected function buildWhereClause()
	{
		$whereClause = '';
		$link = 'WHERE ';
		
		// Filter by IMLITM
		if ($this->programState['filters']['IMLITM'] != '')
		{
			$whereClause = $whereClause . $link . 'JRGDTA94C.F4101.IMLITM LIKE :IMLITM';
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
			$stmt->bindValue(':IMITM_', (int) $keyFieldArray['IMITM'], PDO::PARAM_INT);
			
		} else {
			$stmt->bindValue(':IMITM_', (int) $keyFieldArray['IMITM_'], PDO::PARAM_INT);
			
		}
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildRecordSelectString()
	{
		$selString = 'SELECT JRGDTA94C.F4101.IMITM, JRGDTA94C.F4101.IMLITM, JRGDTA94C.F4101.IMDSC1, JRGDTA94C.F41002.UMCONV FROM JRGDTA94C.F4101 inner join JRGDTA94C.F41002 on JRGDTA94C.F4101.IMITM = JRGDTA94C.F41002.UMITM';
		
		return $selString;
	}
	
	// Build where clause to filter single entries
	protected function buildRecordWhere()
	{
		$whereClause = 'WHERE JRGDTA94C.F4101.IMITM = :IMITM_';
		
		return $whereClause;
	}
	
	// Return the SELECT SQL for a count on the primary file
	protected function getPrimaryFileCountSelect()
	{
		$selString = 'SELECT COUNT(*) FROM JRGDTA94C.F4101';
		
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
		$stmt->bindValue(':IMITM_', (int) $keyFieldArray['IMITM'], PDO::PARAM_INT);
		
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
    <title>Calcolo sfere</title>
    
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/crud/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="display-list">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Calcolo sfere</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <!-- Form containing filter inputs -->
          <form id="filter-form" class="container-fluid" method="post" action="$pf_scriptname">
            <input type="hidden" name="task" value="filter" />
            <div class="form">
              <div class="row">
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_IMLITM">Ricerca codice articolo:</label>
                  <input id="filter_IMLITM" class="form-control" type="text" name="filter_IMLITM" maxlength="25" value="{$programState['filters']['IMLITM']}"/>
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
                <th width="250">
                  <a class="list-header" href="$pf_scriptname?sidx=IMLITM&amp;rnd=$rnd">2nd Item Number. . . . . . . . . . . . .</a>
                </th>
                <th width="150">
                  <a class="list-header" href="$pf_scriptname?sidx=UMCONV&amp;rnd=$rnd">Conversion Factor. . . . . . . . . . . .</a>
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
      <a class="btn btn-default btn-xs glyphicon glyphicon-file" title="View this record" href="$pf_scriptname?task=disp&amp;IMITM=$IMITM_url&amp;rnd=$rnd"></a>  
    </span>
  </td> 
  <td class="text">$IMLITM</td>
  <td class="text num">$UMCONV</td>
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
    <title>Calcolo sfere</title>
    
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/crud/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record display-record">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Calcolo sfere</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <div id="display-fields">
            <div class="form-group row">
              <label class="col-sm-4 text-right">Codice articolo:</label>
              <div class="col-sm-8">$IMLITM</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4 text-right">Descrizione:</label>
              <div class="col-sm-8">$IMDSC1</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4 text-right">Conversion Factor:</label>
              <div class="col-sm-8">
SEGDTA;
 echo number_format($UMCONV,0,",","."); 
		echo <<<SEGDTA
</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4 text-right">Peso singola sfera:</label>
              <div class="col-sm-8">
SEGDTA;
 echo number_format($pesoSingolaSfera,8,",","."); 
		echo <<<SEGDTA
</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4 text-right">Numero Sfere in un Kg:</label>
              <div class="col-sm-8">
SEGDTA;
 echo number_format($numSfereAlKg,0,",","."); 
		echo <<<SEGDTA
</div>
            </div>
            <div id="fld1-grp" class="form-group row">
              <label class="col-sm-4 text-right">Sfere in un Chilo:</label>
              <div class="col-sm-8">
              	<input type="text" class="form-field" name="fld1" id="fld1" value="" /> 
              	 <span id="fld1-err" class="invalid help-block"></span>
              </div>
            </div>
            <div id="fld2-grp" class="form-group row">
              <label class="col-sm-4 text-right">Numero sfere da peso:</label>
              <div class="col-sm-8">
              	<input type="text" class="form-field" name="fld2" id="fld2" value="" /> 
              	 <span id="fld2-err" class="invalid help-block"></span>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4 text-right">&nbsp;</label>
              <div class="col-sm-8">
              	 <input type="button" class="btn btn-sm btn-primary" onclick="calcola();" value="Calcola" />
              </div>
            </div>
            
            <div class="form-group row">
              <label class="col-sm-4 text-right">Risultato 1:</label>
              <div class="col-sm-8 risultati" id="risultato-1"></div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4 text-right">Risultato 2:</label>
              <div class="col-sm-8 risultati" id="risultato-2"></div>
            </div>
            
            
            
          </div>
          
          <div id="nav">
            
SEGDTA;

		    	$this->writeSegment('RtnToList', $segmentVars);
          	
		echo <<<SEGDTA

          </div>
        </div>
      </div>	
    </div>
    <script type="text/javascript">
		jQuery(function() {
			
			jQuery("#fld1").focus();
			
			// Bind event to the Back button
			jQuery(".cancel").click(goback);
			function goback()
			{
				window.location.replace("$pf_scriptname?page={$programState['page']}");
				return false;
			}
		});
		
		function calcola() {
			jfld1 = $("#fld1").val();
			jfld2 = $("#fld2").val();
			$(".risultati").html("");
			$(".invalid").html("");
			$(".has-error").removeClass("has-error");
			
			url = "?task=calcola&fld1="+jfld1+"&fld2="+jfld2+"&UMCONV=$UMCONV";
			$.getJSON(url,function(data){
				
				if(data[0].stat!="OK") {
					for(i=0;i<data.length;i++) {
						$("#"+data[i].id+"-err").html(data[i].msg);
						$("#"+data[i].id+"-grp").addClass("has-error");
					}
					return false;
				}
				
				$("#risultato-1").html(data[0].res1);	
				$("#risultato-2").html(data[0].res2);	
			});	
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

<button class="btn btn-info cancel">Indietro</button>
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "mainseg")
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
    <title>Calcolo sfere</title>
    
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="/crud/websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="/crud/websmart/v13.2/js/jquery.min.js"></script>
    <script src="/crud/websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="display-list">
    <div id="outer-content">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/crud/websmart/v13.2/images/rgp-logo.jpg" alt="logo">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content">
        <div id="content-header">
          <h1 class="title">Calcolo sfere</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <!-- Form containing filter inputs -->
          <form id="filter-form" class="container-fluid" method="post" action="$pf_scriptname">
            <input type="hidden" name="task" value="filter" />
            <div class="form">
              <div class="row">
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_IMLITM">Ricerca codice articolo:</label>
                  <input id="filter_IMLITM" class="form-control" type="text" name="filter_IMLITM" maxlength="25" value="{$programState['filters']['IMLITM']}"/>
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
	           
	</div>
	</div>

<!-- Supporting JavaScript -->
<script type="text/javascript">
	// Focus the first input on page load
	jQuery(function() {
		jQuery("input:enabled:first").focus();
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

		$this->pf_scriptname = 'calc_sfere.php';
		$this->pf_wcm_set = '';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		
		// Last Generated CRC: F3E76442 96457DCB 04BF4087 646F84C2
		// Last Generated Date: 2024-04-02 10:48:30
		// Path: C:\Users\matti\OneDrive\Desktop\calc_sfere.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'calc_sfere');?>