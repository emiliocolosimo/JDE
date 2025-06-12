<?php
if(!function_exists('xlLoadWebSmartObject')) {
		function xlLoadWebSmartObject($file, $class) {	if(realpath($file) !== realpath($_SERVER["SCRIPT_FILENAME"])) {	return;	} $instance = new $class; $instance->runMain(); }
}

//	Program Name:		gestdipe.php
//	Program Title:		Gestione tabella dipendenti
//	Created by:			matti
//	Template family:	Responsive
//	Template name:		Page at a Time.tpl
//	Purpose:        	Maintain a database file using embedded SQL. Supports options for add, change, delete and display.
//	Program Modifications:


require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/WebSmartObject.php');
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/xl_functions.php');
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/en-US/WSRequiredValidator.php');
require_once('/www/php80/htdocs/CRUD/websmart/v13.2/include/en-US/WSNumericValidator.php');

class gest_dipe extends WebSmartObject
{
	protected $programState = array(
		'sortDir' => '',
		'sort' => '',
		'page' => 1,
		'listSize' => 9999,
		'filters' => array('BDNOME' => '', 'BDCOGN' => '', 'BDPASS' => '', 'BDAUTH' => '', 'BDEMAI' => '', 'BDCOGE' => '', 'BDBADG' => '', 'BDREPA' => '', 'BDTIMB' => '', 'BDBDTM' => '')
	);
	
	
	protected $keyFields = array('BDBADG');
	protected $uniqueFields = array('BDBADG');
	
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
		if (isset($_SESSION[$this->pf_scriptname])) {
    $this->programState = $_SESSION[$this->pf_scriptname];
}
		// Fetch the program state
		$this->getState();

		$this->formFields = array(
			"BDNOME" => array("validators"=> array("WSRequired")),
			"BDCOGN" => array("validators"=> array("WSRequired")),
			"BDPASS" => array(),
			"BDAUTH" => array("validators"=> array("WSRequired")),
			"BDEMAI" => array(),
			"BDCOGE" => array("validators"=> array("WSRequired")),
			"BDBADG" => array("validators"=> array("WSRequired")),
			"BDREPA" => array("validators"=> array("WSRequired")),
			"BDTIMB" => array("validators"=> array("WSRequired")),
			"BDBDTM" => array());
			//"BDBDTM" => array("validators"=> array("WSRequired")));

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
		$BDBADG_url = urlencode(rtrim($row['BDBADG']));
		
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
		$delString = 'DELETE FROM BCD_DATIV2.BDGDIP0F ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($delString);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the key parameters
		$stmt->bindValue(':BDBADG_', $keyFieldArray['BDBADG'], PDO::PARAM_STR);
		
		// Execute the delete statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
$go = xl_get_parameter('go');
if ($go == 'next' && !empty($_POST['BDBADG_NEXT'])) {
    header("Location: $this->pf_scriptname?task=beginchange&BDBADG=" . urlencode($_POST['BDBADG_NEXT']));
} elseif ($go == 'prev' && !empty($_POST['BDBADG_PREV'])) {
    header("Location: $this->pf_scriptname?task=beginchange&BDBADG=" . urlencode($_POST['BDBADG_PREV']));
} else {
    header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
}	}
	
	// Show the add page
	protected function beginAdd()
	{
		$BDNOME = "";
		$BDCOGN = "";
		$BDPASS = "";
		$BDAUTH = "";
		$BDEMAI = "";
		$BDCOGE = "";
		$BDBADG = "";
		$BDREPA = "";
		$BDTIMB = "";
		$BDBDTM = "";
		
		// Output the segment
		$this->writeSegment('RcdAdd', array_merge(get_object_vars($this), get_defined_vars()));
	}
	
	// Add the passed in data as a new row
	protected function endAdd()
	{
		// Get values from the page
		$keyFieldArray = $this->getParameters(xl_FieldEscape($this->uniqueFields));
		extract($keyFieldArray);
		$BDNOME = strtoupper(xl_get_parameter('BDNOME'));
		$BDCOGN = strtoupper(xl_get_parameter('BDCOGN'));
		$BDPASS = strtoupper(xl_get_parameter('BDPASS'));
		$BDAUTH = strtoupper(xl_get_parameter('BDAUTH'));
		$BDEMAI = strtoupper(xl_get_parameter('BDEMAI'));
		$BDCOGE = strtoupper(xl_get_parameter('BDCOGE'));
		$BDBADG = str_pad($BDBADG,16,"0",STR_PAD_LEFT);
		$BDREPA = strtoupper(xl_get_parameter('BDREPA')); 
		$BDTIMB = strtoupper(xl_get_parameter('BDTIMB')); 
		$BDBDTM = str_pad($BDBDTM,16,"0",STR_PAD_LEFT);
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
		$insertSql = 'INSERT INTO BCD_DATIV2.BDGDIP0F (BDNOME, BDCOGN, BDPASS, BDAUTH, BDEMAI, BDCOGE, BDBADG, BDREPA, BDTIMB, BDBDTM ) VALUES(:BDNOME, :BDCOGN, :BDPASS, :BDAUTH, :BDEMAI, :BDCOGE, :BDBADG, :BDREPA, :BDTIMB, :BDBDTM)' . ' WITH NC';
		$stmt = $this->db_connection->prepare($insertSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':BDNOME', $BDNOME, PDO::PARAM_STR);
		$stmt->bindValue(':BDCOGN', $BDCOGN, PDO::PARAM_STR);
		$stmt->bindValue(':BDPASS', $BDPASS, PDO::PARAM_STR);
		$stmt->bindValue(':BDAUTH', $BDAUTH, PDO::PARAM_STR);
		$stmt->bindValue(':BDEMAI', $BDEMAI, PDO::PARAM_STR);
		$stmt->bindValue(':BDCOGE', $BDCOGE, PDO::PARAM_STR);
		$stmt->bindValue(':BDBADG', $BDBADG, PDO::PARAM_STR);
		$stmt->bindValue(':BDREPA', $BDREPA, PDO::PARAM_STR);
		$stmt->bindValue(':BDTIMB', $BDTIMB, PDO::PARAM_STR);
		$stmt->bindValue(':BDBDTM', $BDBDTM, PDO::PARAM_STR);

		// Execute the insert statement
		$result = $stmt->execute();
		if ($result === false) 
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
$go = xl_get_parameter('go');
if ($go == 'next' && !empty($_POST['BDBADG_NEXT'])) {
    header("Location: $this->pf_scriptname?task=beginchange&BDBADG=" . urlencode($_POST['BDBADG_NEXT']));
} elseif ($go == 'prev' && !empty($_POST['BDBADG_PREV'])) {
    header("Location: $this->pf_scriptname?task=beginchange&BDBADG=" . urlencode($_POST['BDBADG_PREV']));
} else {
    header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
}	}
	
	// Show the change page
	protected function beginChange()
	{
		$starter = '';
		// Se non è stato passato BDBADG ma è presente BDCOGE nell'URL
		if (empty($_GET['BDBADG']) && !empty($_GET['BDCOGE'])) {
			$BDCOGE = $_GET['BDCOGE'];
			// Cerca il BDBADG corrispondente
			$stmt = $this->db_connection->prepare("SELECT BDBADG FROM BCD_DATIV2.BDGDIP0F WHERE BDCOGE = ? FETCH FIRST 1 ROW ONLY");
			$stmt->execute([$BDCOGE]);
			$starter = 'BDCOGE';
			$result = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($result && isset($result['BDBADG'])) {
				$_GET['BDBADG'] = $result['BDBADG'];
				$_REQUEST['BDBADG'] = $result['BDBADG']; // Per sicurezza su sistemi che usano $_REQUEST
			}
		}
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
		// Recupera tutti i badge in ordine crescente
$query = "SELECT BDBADG FROM BCD_DATIV2.BDGDIP0F ORDER BY BDBADG";
$stmt = $this->db_connection->prepare($query);
$stmt->execute();
$badges = $stmt->fetchAll(PDO::FETCH_COLUMN);

$index = array_search($BDBADG, $badges);
$prevBadge = $badges[$index - 1] ?? '';
$nextBadge = $badges[$index + 1] ?? '';

		// Output the segment
$this->writeSegment('RcdChange', array_merge(get_object_vars($this), get_defined_vars(), [
    'nextBadge' => $nextBadge,
    'prevBadge' => $prevBadge
]));	}
	
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
		$oldKeyFieldArray = $this->getParameters(array('BDBADG_'));
		
		extract($oldKeyFieldArray);
		// Get values from the page
		$BDNOME = strtoupper(xl_get_parameter('BDNOME'));
		$BDCOGN = strtoupper(xl_get_parameter('BDCOGN'));
		$BDPASS = strtoupper(xl_get_parameter('BDPASS'));
		$BDAUTH = strtoupper(xl_get_parameter('BDAUTH'));
		$BDEMAI = strtoupper(xl_get_parameter('BDEMAI'));
		$BDCOGE = strtoupper(xl_get_parameter('BDCOGE'));
		$BDBADG = strtoupper(string: xl_get_parameter('BDBADG'));
		$BDBADG = str_pad($BDBADG,16,"0",STR_PAD_LEFT);
		$BDREPA = strtoupper(xl_get_parameter(xl_sField: 'BDREPA'));
		$BDTIMB = strtoupper(xl_get_parameter(xl_sField: 'BDTIMB'));
		$BDBDTM = strtoupper(string: xl_get_parameter('BDBDTM'));
		$BDBDTM = str_pad($BDBDTM,16,"0",STR_PAD_LEFT);


		//Protect Key Fields from being Changed
		//$BDBADG = $BDBADG_;
		
		
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
		$updateSql = 'UPDATE BCD_DATIV2.BDGDIP0F SET BDNOME = :BDNOME, BDCOGN = :BDCOGN, BDPASS = :BDPASS, BDAUTH = :BDAUTH, BDEMAI = :BDEMAI, BDCOGE = :BDCOGE, BDBADG = :BDBADG, BDREPA = :BDREPA, BDTIMB = :BDTIMB, BDBDTM = :BDBDTM ';
		$updateSql .= ' ' . $this->buildRecordWhere() . ' WITH NC';
		$stmt = $this->db_connection->prepare($updateSql);
		if (!$stmt)
		{
			return $stmt;
		}
		
		// Bind the parameters
		$stmt->bindValue(':BDNOME', $BDNOME, PDO::PARAM_STR);
		$stmt->bindValue(':BDCOGN', $BDCOGN, PDO::PARAM_STR);
		$stmt->bindValue(':BDPASS', $BDPASS, PDO::PARAM_STR);
		$stmt->bindValue(':BDAUTH', $BDAUTH, PDO::PARAM_STR);
		$stmt->bindValue(':BDEMAI', $BDEMAI, PDO::PARAM_STR);
		$stmt->bindValue(':BDCOGE', $BDCOGE, PDO::PARAM_STR);
		$stmt->bindValue(':BDBADG', $BDBADG, PDO::PARAM_STR);
		$stmt->bindValue(':BDBADG_', $BDBADG_, PDO::PARAM_STR);
		$stmt->bindValue(':BDREPA', $BDREPA, PDO::PARAM_STR);
		$stmt->bindValue(':BDTIMB', $BDTIMB, PDO::PARAM_STR);
		$stmt->bindValue(':BDBDTM', $BDBDTM, PDO::PARAM_STR);
		//$stmt->bindValue(':BDBDTM_', $BDBDTM_, PDO::PARAM_STR);


		// Execute the update statement
		$result = $stmt->execute();
		if ($result === false)
		{
			$this->dieWithPDOError($stmt);
		}
		
		// Redirect to the original page of the main list
$go = xl_get_parameter('go');
if ($go == 'next' && !empty($_POST['BDBADG_NEXT'])) {
    header("Location: $this->pf_scriptname?task=beginchange&BDBADG=" . urlencode($_POST['BDBADG_NEXT']));
} elseif ($go == 'prev' && !empty($_POST['BDBADG_PREV'])) {
    header("Location: $this->pf_scriptname?task=beginchange&BDBADG=" . urlencode($_POST['BDBADG_PREV']));
} else {
    header("Location: $this->pf_scriptname?page=" . $this->programState['page']);
}	}
	
	// Load list with filters
	protected function filterList()
	{
		// Retrieve the filter information
		$this->programState['filters']['BDNOME'] = xl_get_parameter('filter_BDNOME');
		$this->programState['filters']['BDCOGN'] = xl_get_parameter('filter_BDCOGN');
		$this->programState['filters']['BDPASS'] = xl_get_parameter('filter_BDPASS');
		$this->programState['filters']['BDAUTH'] = xl_get_parameter('filter_BDAUTH');
		$this->programState['filters']['BDEMAI'] = xl_get_parameter('filter_BDEMAI');
		$this->programState['filters']['BDCOGE'] = xl_get_parameter('filter_BDCOGE');
		$this->programState['filters']['BDBADG'] = xl_get_parameter('filter_BDBADG');
		$this->programState['filters']['BDREPA'] = xl_get_parameter('filter_BDREPA');
		$this->programState['filters']['BDTIMB'] = xl_get_parameter('filter_BDTIMB');
		$this->programState['filters']['BDBDTM'] = xl_get_parameter('filter_BDBDTM');



		
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
			$BDBADG_url = urlencode(rtrim($row['BDBADG']));
			
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
		if ($this->programState['filters']['BDNOME'] != '')
		{
			$stmt->bindValue(':BDNOME', '%' . strtolower($this->programState['filters']['BDNOME']) . '%', PDO::PARAM_STR);
		}
		
		if ($this->programState['filters']['BDCOGN'] != '')
		{
			$stmt->bindValue(':BDCOGN', '%' . strtolower($this->programState['filters']['BDCOGN']) . '%', PDO::PARAM_STR);
		}

		if ($this->programState['filters']['BDPASS'] != '')
		{
			$stmt->bindValue(':BDPASS', '%' . strtolower($this->programState['filters']['BDPASS']) . '%', PDO::PARAM_STR);
		}
		if ($this->programState['filters']['BDAUTH'] != '')
		{
			$stmt->bindValue(':BDAUTH', '%' . strtolower($this->programState['filters']['BDAUTH']) . '%', PDO::PARAM_STR);
		}
		if ($this->programState['filters']['BDEMAI'] != '')
		{
			$stmt->bindValue(':BDEMAI', '%' . strtolower($this->programState['filters']['BDEMAI']) . '%', PDO::PARAM_STR);
		} 
		if ($this->programState['filters']['BDCOGE'] != '')
		{
			$stmt->bindValue(':BDCOGE', '%' . strtolower($this->programState['filters']['BDCOGE']) . '%', PDO::PARAM_STR);
		}

		if ($this->programState['filters']['BDBADG'] != '')
		{ 
			$stmt->bindValue(':BDBADG', strtolower($this->programState['filters']['BDBADG']), PDO::PARAM_STR);
		}
		if ($this->programState['filters']['BDREPA'] != '')
		{
			$stmt->bindValue(':BDREPA', '%' . strtolower($this->programState['filters']['BDREPA']) . '%', PDO::PARAM_STR);
		}
			if ($this->programState['filters']['BDTIMB'] != '')
		{
			$stmt->bindValue(':BDTIMB', '%' . strtolower($this->programState['filters']['BDTIMB']) . '%', PDO::PARAM_STR);
		}
				if ($this->programState['filters']['BDBDTM'] != '')
		{ 
			$stmt->bindValue(':BDBDTM', strtolower($this->programState['filters']['BDBDTM']), PDO::PARAM_STR);
		}
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildSelectString()
	{
		$selString = 'SELECT BCD_DATIV2.BDGDIP0F.BDBADG, BCD_DATIV2.BDGDIP0F.BDNOME, BCD_DATIV2.BDGDIP0F.BDCOGN, BCD_DATIV2.BDGDIP0F.BDPASS, BCD_DATIV2.BDGDIP0F.BDAUTH, BCD_DATIV2.BDGDIP0F.BDEMAI, BCD_DATIV2.BDGDIP0F.BDCOGE , BCD_DATIV2.BDGDIP0F.BDREPA, BCD_DATIV2.BDGDIP0F.BDTIMB , BCD_DATIV2.BDGDIP0F.BDBDTM 
		FROM BCD_DATIV2.BDGDIP0F';
		
		return $selString;
	}
	
	// Build where clause to filter rows from table
	protected function buildWhereClause()
	{
		$whereClause = '';
		$link = 'WHERE ';
		
		// Filter by BDNOME
		if ($this->programState['filters']['BDNOME'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDNOME) LIKE :BDNOME';
			$link = " AND ";
		}
		
		// Filter by BDCOGN
		if ($this->programState['filters']['BDCOGN'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDCOGN) LIKE :BDCOGN';
			$link = " AND ";
		}
		
		// Filter by BDPASS
		if ($this->programState['filters']['BDPASS'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDPASS) LIKE :BDPASS';
			$link = " AND ";
		}
		// Filter by BDAUTH
		if ($this->programState['filters']['BDAUTH'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDAUTH) LIKE :BDAUTH';
			$link = " AND ";
		}
		// Filter by BDEMAI
		if ($this->programState['filters']['BDEMAI'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDEMAI) LIKE :BDEMAI';
			$link = " AND ";
		}
		// Filter by BDCOGE
		if ($this->programState['filters']['BDCOGE'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDCOGE) LIKE :BDCOGE';
			$link = " AND ";
		}
		
		// Filter by BDBADG
		if ($this->programState['filters']['BDBADG'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDBADG) = :BDBADG';
			$link = " AND ";
		}
				// Filter by BDREPA
		if ($this->programState['filters']['BDREPA'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDREPA) LIKE :BDREPA';
			$link = " AND ";
		}
				// Filter by BDTIMB
		if ($this->programState['filters']['BDTIMB'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDTIMB) LIKE :BDTIMB';
			$link = " AND ";
		}
				// Filter by BDBADG
		if ($this->programState['filters']['BDBDTM'] != '')
		{
			$whereClause = $whereClause . $link . ' lower(BCD_DATIV2.BDGDIP0F.BDBDTM) = :BDBDTM';
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
			$stmt->bindValue(':BDBADG_', $keyFieldArray['BDBADG'], PDO::PARAM_STR);
			
		} else {
			$stmt->bindValue(':BDBADG_', $keyFieldArray['BDBADG_'], PDO::PARAM_STR);
			
		}
		
		return $stmt;
	}
	
	// Build SQL Select string
	protected function buildRecordSelectString()
	{
		$selString = 'SELECT BCD_DATIV2.BDGDIP0F.BDBADG, BCD_DATIV2.BDGDIP0F.BDNOME, BCD_DATIV2.BDGDIP0F.BDCOGN, BCD_DATIV2.BDGDIP0F.BDPASS, BCD_DATIV2.BDGDIP0F.BDAUTH, BCD_DATIV2.BDGDIP0F.BDEMAI, BCD_DATIV2.BDGDIP0F.BDCOGE, BCD_DATIV2.BDGDIP0F.BDREPA , BCD_DATIV2.BDGDIP0F.BDTIMB , BCD_DATIV2.BDGDIP0F.BDBDTM FROM BCD_DATIV2.BDGDIP0F';
		
		return $selString;
	}
	
	// Build where clause to filter single entries
	protected function buildRecordWhere()
	{
		$whereClause = 'WHERE BCD_DATIV2.BDGDIP0F.BDBADG = :BDBADG_';
		
		return $whereClause;
	}
	
	// Return the SELECT SQL for a count on the primary file
	protected function getPrimaryFileCountSelect()
	{
		$selString = 'SELECT COUNT(*) FROM BCD_DATIV2.BDGDIP0F';
		
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
		$stmt->bindValue(':BDBADG_', $keyFieldArray['BDBADG'], PDO::PARAM_STR);
		
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
$valBDREPA  = $this->programState['filters']['BDREPA'] ?? '';
$valBDTIMB  = $this->programState['filters']['BDTIMB'] ?? '';
$valBDBDTM = $this->programState['filters']['BDBDTM'] ?? '';
$valBDAUTH = $this->programState['filters']['BDAUTH'] ?? '';
$valBDPASS = $this->programState['filters']['BDPASS'] ?? '';
$valBDAUTH = $this->programState['filters']['BDAUTH'] ?? '';
$valBDEMAI = $this->programState['filters']['BDEMAI'] ?? '';

$optionsBDTIMB = '';
$optionsBDTIMB .= '<option value=""' . ($valBDTIMB == '' ? ' selected' : '') . '>-- Tutti --</option>';
$optionsBDTIMB .= '<option value="70"' . ($valBDTIMB == '70' ? ' selected' : '') . '>INGRESSO</option>';
$optionsBDTIMB .= '<option value="71"' . ($valBDTIMB == '71' ? ' selected' : '') . '>UFFICIO</option>';
$optionsBDTIMB .= '<option value="72"' . ($valBDTIMB == '72' ? ' selected' : '') . '>PRODUZIONE</option>';
$optionsBDREPA = '';
$optionsBDREPA .= '<option value=""' . ($valBDREPA == '' ? ' selected' : '') . '>-- Tutti --</option>';
$optionsBDREPA .= '<option value="MAGAZZINO"' . ($valBDREPA == 'MAGAZZINO' ? ' selected' : '') . '>MAGAZZINO</option>';
$optionsBDREPA .= '<option value="UFFICIO"' . ($valBDREPA == 'UFFICIO' ? ' selected' : '') . '>UFFICIO</option>';
$optionsBDREPA .= '<option value="PRODUZIONE"' . ($valBDREPA == 'PRODUZIONE' ? ' selected' : '') . '>PRODUZIONE</option>';
$optionsBDREPA .= '<option value="LABORATORIO"' . ($valBDREPA == 'LABORATORIO' ? ' selected' : '') . '>LABORATORIO</option>';
$optionsBDBDTM = '';
$optionsBDBDTM .= '<option value=""' . ($valBDBDTM == '' ? ' selected' : '') . '>-- Tutti --</option>';
$optionsBDBDTM .= '<option value="0004FA56D6FF6180"' . ($valBDBDTM == '0004FA56D6FF6180' ? ' selected' : '') . '>JOLLY 1</option>';
$optionsBDBDTM .= '<option value="0004EB2874BF6180"' . ($valBDBDTM == '0004EB2874BF6180' ? ' selected' : '') . '>JOLLY 2</option>';
$optionsBDBDTM .= '<option value="000406ED76BF6180"' . ($valBDBDTM == '000406ED76BF6180' ? ' selected' : '') . '>JOLLY 3</option>';
$optionsBDBDTM .= '<option value="0004F025D7FF6180"' . ($valBDBDTM == '0004F025D7FF6180' ? ' selected' : '') . '>JOLLY 4</option>';
$optionsBDBDTM .= '<option value="0004FBC5D6FF6180"' . ($valBDBDTM == '0004FBC5D6FF6180' ? ' selected' : '') . '>JOLLY 5</option>';
$optionsBDAUTH = '';
$optionsBDAUTH .= '<option value="ADMIN"' . ($valBDAUTH == 'ADMIN' ? ' selected' : '') . '>AMMINISTRATORE</option>';
$optionsBDAUTH .= '<option value="RESPONSABILE"' . ($valBDAUTH == 'RESPONSABILE' ? ' selected' : '') . '>RESPONSABILE</option>';
$optionsBDAUTH .= '<option value="USER"' . ($valBDAUTH == 'USER' ? ' selected' : '') . '>UTENTE</option>';

		echo <<<SEGDTA
<!DOCTYPE html>
<html>
  <head>
    <meta name="generator" content="WebSmart" />
    <meta charset="ISO-8859-1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Pragma" content="no-cache" />
    <title>Gestione tabella dipendenti</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="display-list">
    <div id="outer-content" style="padding: 0 20px;">
      <div id="page-title-block" class="page-title-block text-center">
        <img class="page-title-image" src="include/logo.jpg" alt="logo" style="max-height: 60px;">
        <h1 class="title mt-2">Gestione tabella dipendenti</h1>
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content" style="padding: 0 20px;">
        <div id="content-header">
          <!-- <h1 class="title">Gestione tabella dipendenti</h1> -->
          <span class="add-link">
            <a class="btn btn-primary btn-sm" href="$pf_scriptname?task=beginadd&amp;rnd=$rnd">
              <span class="glyphicon glyphicon-plus"></span> Aggiungi dipendente
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
                  <label for="filter_BDNOME">COGNOME</label>
                  <input id="filter_BDNOME" class="form-control" type="text" name="filter_BDNOME" maxlength="100" value="{$programState['filters']['BDNOME']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_BDCOGN">NOME</label>
                  <input id="filter_BDCOGN" class="form-control" type="text" name="filter_BDCOGN" maxlength="100" value="{$programState['filters']['BDCOGN']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_BDCOGE">COD.GESTIONALE</label>
                  <input id="filter_BDCOGE" class="form-control" type="text" name="filter_BDCOGE" maxlength="4" value="{$programState['filters']['BDCOGE']}"/>
                </div>
                <div class="filter-group form-group col-sm-4 col-lg-2">
                  <label for="filter_BDBADG">BADGE</label>
                  <input id="filter_BDBADG" class="form-control" type="text" name="filter_BDBADG" maxlength="18" value="{$programState['filters']['BDBADG']}"/>
                </div>
				                <div class="filter-group form-group col-sm-4 col-lg-2">
				  				    <label for="filter_BDREPA">REPARTO</label>
 	 <select id="filter_BDREPA" class="form-control" name="filter_BDREPA"">
$optionsBDREPA
	 </select>
                </div>
				                <div class="filter-group form-group col-sm-4 col-lg-2">
				    <label for="filter_BDTIMB">TIMBRATORE</label>
<select id="filter_BDTIMB" class="form-control" name="filter_BDTIMB">
  $optionsBDTIMB
</select>
                </div>
<div class="filter-group form-group col-sm-4 col-lg-2">
  <label for="filter_BDBDTM">BADGE TEMPORANEO</label>
  <select id="filter_BDBDTM" class="form-control" name="filter_BDBDTM" value="{$programState['filters']['BDBDTM']}">
$optionsBDBDTM
  </select>
</div>
              </div>
              <div class="row">
                <div class="col-sm-2">
                  <input id="filter-button" class="btn btn-primary filter" type="submit" value="Cerca" />
				    <button type="button" class="btn btn-secondary" onclick="resetAndSubmit()">Reset</button>

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
          <table id="list-table" class="main-list table table-striped table-bordered" cellspacing="0">
            <thead>
              <tr class="list-header">
                <th class="actions" width="100">Action</th> 
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=BDNOME&amp;rnd=$rnd">COGNOME</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=BDCOGN&amp;rnd=$rnd">NOME</a>
                </th>
                <th>
				  <a class="list-header" href="$pf_scriptname?sidx=BDPASS&amp;rnd=$rnd">PASSWORD</a>
				</th>
				<th>
				  <a class="list-header" href="$pf_scriptname?sidx=BDAUTH&amp;rnd=$rnd">AUTORIZZAZIONE</a>
				</th>
				<th>
				  <a class="list-header" href="$pf_scriptname?sidx=BDEMAI&amp;rnd=$rnd">EMAIL</a>
				</th>
				<th>
                  <a class="list-header" href="$pf_scriptname?sidx=BDCOGE&amp;rnd=$rnd">COD.GESTIONALE</a>
                </th>
                <th>
                  <a class="list-header" href="$pf_scriptname?sidx=BDBADG&amp;rnd=$rnd">BADGE</a>
                </th>
				     <th>
                  <a class="list-header" href="$pf_scriptname?sidx=BDREPA&amp;rnd=$rnd">REPARTO</a>
                </th>
								     <th>
                  <a class="list-header" href="$pf_scriptname?sidx=BDTIMB&amp;rnd=$rnd">TIMBRATORE</a>
                </th>
								     <th>
                  <a class="list-header" href="$pf_scriptname?sidx=BDBDTM&amp;rnd=$rnd">BADGE TEMPORANEO</a>
                </th>
              </tr>
            </thead>
            <tbody>
            
SEGDTA;
		return;
	}
	if($xlSegmentToWrite == "listdetails")
	{
		$timbMap = [
			// '  ' => 'DA DEFINIRE',
			'70' => 'INGRESSO',
			'71' => 'MAGAZZINO',
			'72' => 'PRODUZIONE'
		];
		$labelTimb = $timbMap[trim($BDTIMB)] ?? ' ';
		$bdmap = [
			'0000000000000000' => 'NESSUNO',
			'0004FA56D6FF6180' => 'JOLLY 1',
			'0004EB2874BF6180' => 'JOLLY 2',
			'000406ED76BF6180' => 'JOLLY 3',
			'0004F025D7FF6180' => 'JOLLY 4',
			'0004FBC5D6FF6180' => 'JOLLY 5'
		];
		$labelBDBDTM = $bdmap[$BDBDTM] ?? $BDBDTM;

		$authmap = [
			'ADMIN' => 'AMMINISTRATORE',
			'RESPONSABILE' => 'RESPONSABILE',
			'USER' => 'UTENTE'
		];
		$labelBDAUTH = $authmap[$BDAUTH] ?? $BDAUTH;

		echo <<<SEGDTA

<tr>
  <td class="actions">
    <span>
      <a class="btn btn-default btn-xs glyphicon glyphicon-file" title="View this record" href="$pf_scriptname?task=disp&amp;BDBADG=$BDBADG_url&amp;rnd=$rnd"></a> 
      <a class="btn btn-default btn-xs glyphicon glyphicon-pencil" title="Change this record" href="$pf_scriptname?task=beginchange&amp;BDBADG=$BDBADG_url&amp;rnd=$rnd"></a> 
      <a class="btn btn-default btn-xs glyphicon glyphicon-remove" title="Delete this record" href="$pf_scriptname?task=delconf&amp;BDBADG=$BDBADG_url"></a>
    </span>
  </td> 
  <td class="text">$BDNOME</td>
  <td class="text">$BDCOGN</td>
  <td class="text">$BDPASS</td>
  <td class="text">$labelBDAUTH</td>
  <td class="text">$BDEMAI</td>
  <td class="text">$BDCOGE</td>
  <td class="text">$BDBADG</td>
  <td class="text">$BDREPA</td>
  <td class="text">$labelTimb</td>
  <td class="text">$labelBDBDTM</td>
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
    <title>Gestione tabella dipendenti - $mode</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record display-record">
    <div id="outer-content" style="padding: 0 20px;">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="include/logo.jpg" alt="logo"  style="max-height: 60px;">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content" style="padding: 0 20px;">
        <div id="content-header">
          <h1 class="title">Gestione tabella dipendenti - $mode</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents">
          <div id="display-fields">
            <div class="form-group row">
              <label class="col-sm-4">NOME:</label>
              <div class="col-sm-8">$BDNOME</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">COGNOME:</label>
              <div class="col-sm-8">$BDCOGN</div>
            </div>
			<div class="form-group row">
			  <label class="col-sm-4">PASSWORD:</label>
			  <div class="col-sm-8">$BDPASS</div>
			</div>
			<div class="form-group row">
			  <label class="col-sm-4">AUTORIZZAZIONE:</label>
			  <div class="col-sm-8">$BDAUTH</div>
			</div>
			<div class="form-group row">
			  <label class="col-sm-4">EMAIL:</label>
			  <div class="col-sm-8">$BDEMAI</div>
							</div>
            <div class="form-group row">
              <label class="col-sm-4">COD.GESTIONALE:</label>
              <div class="col-sm-8">$BDCOGE</div>
            </div>
            <div class="form-group row">
              <label class="col-sm-4">BADGE HEX:</label>
              <div class="col-sm-8">$BDBADG</div>
            </div>
			            <div class="form-group row">
              <label class="col-sm-4">REPARTO:</label>
              <div class="col-sm-8">$BDREPA</div>
            </div>
			 <div class="form-group row">
              <label class="col-sm-4">TIMBRATORE:</label>
              <div class="col-sm-8">$BDTIMB</div>
            </div>
			<div class="form-group row">
              <label class="col-sm-4">BADGE TEMPORANEO:</label>
              <div class="col-sm-8">$BDBDTM</div>
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
  <input id="BDBADG" type="hidden" name="BDBADG" value="$BDBADG" />
  
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
    <title>Gestione tabella dipendenti - Add</title>
    
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/screen.css" media="all" type="text/css" />
    <link rel="stylesheet" href="websmart/v13.2/Responsive/css/jquery-ui.css" media="all" type="text/css" />
    
    <script src="websmart/v13.2/js/jquery.min.js"></script>
    <script src="websmart/v13.2/Responsive/js/bootstrap.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content" style="padding: 0 20px;">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="/include/logo.jpg" alt="logo"  style="max-height: 60px;">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content" style="padding: 0 20px;">
        <div id="content-header">
          <h1 class="title">Gestione tabella dipendenti - Add</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="add-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endadd" />
            <div id="addfields"><div class="notice 
SEGDTA;
/* if(!$this->showRequiredIndicator) echo "hidden nodisplay"; 
		echo <<<SEGDTA
"><span class="required">{$this->requiredIndicator}</span> Denotes a required field.</div>
              <div class="form-group 
SEGDTA;
*/

 $this->displayErrorClass('BDNOME'); 
		echo <<<SEGDTA
">
                <label for="addBDNOME">COGNOME 
SEGDTA;
 $this->displayIndicator('BDNOME'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addBDNOME" class="form-control" name="BDNOME" size="100" maxlength="100" value="$BDNOME">
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDNOME', array('NOME')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('BDCOGN'); 
		echo <<<SEGDTA
">
                <label for="addBDCOGN">NOME 
SEGDTA;
 $this->displayIndicator('BDCOGN'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addBDCOGN" class="form-control" name="BDCOGN" size="100" maxlength="100" value="$BDCOGN">
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDCOGN', array('COGNOME')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('BDPASS'); 
		echo <<<SEGDTA
">
				<label for="addBDPASS">PASSWORD
SEGDTA;
 $this->displayIndicator('BDPASS'); 
		echo <<<SEGDTA
</label>
				<div>
				  <input type="password" id="addBDPASS" class="form-control" name="BDPASS" size="100" maxlength="100" value="$BDPASS">
				  <span class="error-text">
SEGDTA;
 $this->displayError('BDPASS', array('PASSWORD')); 
		echo <<<SEGDTA
</span>
				</div>
			  </div>
			  <div class="form-group
SEGDTA;

 $this->displayErrorClass('BDAUTH'); 
		echo <<<SEGDTA
">
				<label for="addBDAUTH">AUTORIZZAZIONE	
SEGDTA;
 $this->displayIndicator('BDAUTH'); 
$sel_AUTH0 = ($BDAUTH == 'User') ? 'selected' : '';
$sel_AUTH1 = ($BDAUTH == 'Responsabile') ? 'selected' : '';
$sel_AUTH2 = ($BDAUTH == 'Admin') ? 'selected' : '';
echo <<<SEGDTA
</label>
  <div>
    <select id="addBDAUTH" class="form-control" name="BDAUTH">
      <option value="User" $sel_AUTH0>User</option>
      <option value="Responsabile" $sel_AUTH1>Responsabile</option>
	  <option value="Admin" $sel_AUTH2>Admin</option>
    </select>
    <span class="error-text">
SEGDTA;

 $this->displayError('BDEMAI', array('EMAIL')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('BDEMAI'); 
		echo <<<SEGDTA
">
				<label for="addBDEMAI">EMAIL	
SEGDTA;
 $this->displayIndicator('BDEMAI'); 
		echo <<<SEGDTA
</label>
				<div>
				  <input type="email" id="addBDEMAI" class="form-control" name="BDEMAI" size="100" maxlength="100" value="$BDEMAI">
				  <span class="error-text">
SEGDTA;

 $this->displayIndicator('BDCOGE'); 
		echo <<<SEGDTA
</label>
				<div>
				  <input type="text" id="addBDCOGE" class="form-control" name="BDCOGE" size="4" maxlength="4" value="$BDCOGE">
				  <span class="error-text">
SEGDTA;

 $this->displayErrorClass('BDCOGE'); 
		echo <<<SEGDTA
">
                <label for="addBDCOGE">COD.GESTIONALE 
SEGDTA;
 $this->displayIndicator('BDCOGE'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addBDCOGE" class="form-control" name="BDCOGE" size="4" maxlength="4" value="$BDCOGE">
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDCOGE', array('COD.GESTIONALE')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('BDBADG'); 
		echo <<<SEGDTA
">
                <label for="addBDBADG">BADGE HEX
SEGDTA;
 $this->displayIndicator('BDBADG'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="addBDBADG" class="form-control" name="BDBADG" size="16" maxlength="16" value="$BDBADG">
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDBADG', array('BADGE HEX')); 
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
 $this->displayErrorClass('BDREPA'); 
 $sel_REPA1 = ($BDREPA == 'DA DEFINIRE') ? 'selected' : '';
$sel_REPA2 = ($BDREPA == 'UFFICIO') ? 'selected' : '';
$sel_REPA3 = ($BDREPA == 'MAGAZZINO') ? 'selected' : '';
$sel_REPA4 = ($BDREPA == 'PRODUZIONE') ? 'selected' : '';
$sel_REPA5 = ($BDREPA == 'LABORATORIO') ? 'selected' : '';
		echo <<<SEGDTA
</label>
                             <div>
<select id="addBDREPA" class="form-control" name="BDREPA">
  <option value="DA DEFINIRE" $sel_REPA1>DA DEFINIRE</option>
  <option value="UFFICIO" $sel_REPA2>UFFICIO</option>
  <option value="MAGAZZINO" $sel_REPA3>MAGAZZINO</option>
  <option value="PRODUZIONE" $sel_REPA4>PRODUZIONE</option>
  <option value="LABORATORIO" $sel_REPA5>LABORATORIO</option>
</select>
<span class="error-text">
SEGDTA;
 $this->displayError('BDREPA', array('REPARTO')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('BDTIMB'); 
		echo <<<SEGDTA
">
                <label for="addBDTIMB">TIMBRATORE
SEGDTA;
$this->displayIndicator('BDTIMB'); 
$sel_TIMB0 = ($BDTIMB == '  ') ? 'selected' : '';
$sel_TIMB1 = ($BDTIMB == '70') ? 'selected' : '';
$sel_TIMB2 = ($BDTIMB == '71') ? 'selected' : '';
$sel_TIMB3 = ($BDTIMB == '72') ? 'selected' : '';
echo <<<SEGDTA
</label>
  <div>
    <select id="addBDTIMB" class="form-control" name="BDTIMB">
      <option value="  " $sel_TIMB0>DA DEFINIRE</option>
      <option value="70" $sel_TIMB1>INGRESSO</option>
      <option value="71" $sel_TIMB2>MAGAZZINO</option>
      <option value="72" $sel_TIMB3>PRODUZIONE</option>
    </select>
    <span class="error-text">
SEGDTA;

 $this->displayError('BDTIMB', array('TIMBRATORE')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;

 $this->displayErrorClass('BDBDTM'); 
		echo <<<SEGDTA
">
                <label for="addBDBDTM">BADGE HEX TEMPORANEO
SEGDTA;

$this->displayIndicator('BDBDTM'); 
$sel_JOLLY0 = ($BDBDTM == '00000000000000') ? 'selected' : '';
$sel_JOLLY1 = ($BDBDTM == '04FA56D6FF6180') ? 'selected' : '';
$sel_JOLLY2 = ($BDBDTM == '04EB2874BF6180') ? 'selected' : '';
$sel_JOLLY3 = ($BDBDTM == '0406ED76BF6180') ? 'selected' : '';
$sel_JOLLY4 = ($BDBDTM == '04F025D7FF6180') ? 'selected' : '';
$sel_JOLLY5 = ($BDBDTM == '04FBC5D6FF6180') ? 'selected' : '';
		echo <<<SEGDTA
</label>
                <div>
    <select id="chgBDBDTM" class="form-control" name="BDBDTM">
      <option value="00000000000000" $sel_JOLLY0>NESSUNO</option>
      <option value="04FA56D6FF6180" $sel_JOLLY1>JOLLY 1</option>
      <option value="04EB2874BF6180" $sel_JOLLY2>JOLLY 2</option>
      <option value="0406ED76BF6180" $sel_JOLLY3>JOLLY 3</option>
      <option value="04F025D7FF6180" $sel_JOLLY4>JOLLY 4</option>
      <option value="04FBC5D6FF6180" $sel_JOLLY5>JOLLY 5</option>
    </select>
                <span class="error-text">
SEGDTA;
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
    <title>Gestione tabella dipendenti - Change</title>
    
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
<link rel="stylesheet" href="/www/php80/htdocs/CRUD/websmart/v13.2/Responsive/css/screen.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body class="single-record manage-record">
    <div id="outer-content" style="padding: 0 20px;">
      <div id="page-title-block" class="page-title-block">
        <img class="page-title-image" src="include/logo.jpg" alt="logo" style="max-height: 60px;">
        <div id="page-divider-top" class="page-divider"></div>
      </div>
      <div id="page-content" style="padding: 0 20px;">
        <div id="content-header">
          <h1 class="title">Gestione tabella dipendenti - Change</h1>
        </div>
        <div class="clearfix"></div>
        <div id="contents"> 
          
          <form id="change-form" action="$pf_scriptname" method="post">
            <input type="hidden" name="task" value="endchange" />
            <input id="BDBADG_" type="hidden" name="BDBADG_" value="$BDBADG" />
			<div id="navbottom">

</div>
            <div id="changefields"><div class="notice 
SEGDTA;
 /*if(!$this->showRequiredIndicator) echo "hidden nodisplay"; 
		echo <<<SEGDTA
"><span class="required">{$this->requiredIndicator}</span> Denotes a required field.</div>
              <div class="form-group 
SEGDTA;*/
 $this->displayErrorClass('BDNOME'); 
		echo <<<SEGDTA
">
                <label for="chgBDNOME">COGNOME 
SEGDTA;
 $this->displayIndicator('BDNOME'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgBDNOME" class="form-control" name="BDNOME" size="100" maxlength="100" value="$BDNOME">
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDNOME', array('NOME')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('BDCOGN'); 
		echo <<<SEGDTA
">
                <label for="chgBDCOGN">NOME 
SEGDTA;
 $this->displayIndicator('BDCOGN'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgBDCOGN" class="form-control" name="BDCOGN" size="100" maxlength="100" value="$BDCOGN">
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDCOGN', array('COGNOME')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
$this->displayErrorClass('BDPASS'); 
		echo <<<SEGDTA
">
				<label for="chgBDPASS">PASSWORD
SEGDTA;
 $this->displayIndicator('BDPASS'); 
		echo <<<SEGDTA
</label>
				<div>
				  <input type="password" id="chgBDPASS" class="form-control" name="BDPASS" size="100" maxlength="100" value="$BDPASS">
				  <span class="error-text">
SEGDTA;
 $this->displayError('BDPASS', array('PASSWORD')); 
		echo <<<SEGDTA
</span>
				</div>
			  </div>
			  <div class="form-group
SEGDTA;

 $this->displayErrorClass('BDAUTH'); 
		echo <<<SEGDTA
">
				<label for="chgBDAUTH">AUTORIZZAZIONE	
SEGDTA;
 $this->displayIndicator('BDAUTH'); 
$sel_AUTH0 = ($BDAUTH == 'User') ? 'selected' : '';
$sel_AUTH1 = ($BDAUTH == 'Responsabile') ? 'selected' : '';
$sel_AUTH2 = ($BDAUTH == 'Admin') ? 'selected' : '';
echo <<<SEGDTA
</label>
  <div>
    <select id="chgBDAUTH" class="form-control" name="BDAUTH">
      <option value="User" $sel_AUTH0>User</option>
      <option value="Responsabile" $sel_AUTH1>Responsabile</option>
	  <option value="Admin" $sel_AUTH2>Admin</option>
    </select>
    <span class="error-text">
SEGDTA;

 $this->displayError('BDEMAI', array('EMAIL')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('BDEMAI'); 
		echo <<<SEGDTA
">
				<label for="chgBDEMAI">EMAIL	
SEGDTA;
 $this->displayIndicator('BDEMAI'); 
		echo <<<SEGDTA
</label>
				<div>
				  <input type="email" id="chgBDEMAI" class="form-control" name="BDEMAI" size="100" maxlength="100" value="$BDEMAI">
				  <span class="error-text">
SEGDTA;

 $this->displayErrorClass('BDCOGE'); 
		echo <<<SEGDTA
                <label for="chgBDCOGE">COD.GESTIONALE 
SEGDTA;
 $this->displayIndicator('BDCOGE'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgBDCOGE" class="form-control" name="BDCOGE" size="4" maxlength="4" value="$BDCOGE">
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDCOGE', array('COD.GESTIONALE')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
 $this->displayErrorClass('BDBADG'); 
		echo <<<SEGDTA
">
                <label for="chgBDBADG">BADGE HEX 
SEGDTA;
 $this->displayIndicator('BDBADG'); 
		echo <<<SEGDTA
</label>
                <div>
                  <input type="text" id="chgBDBADG" class="form-control" name="BDBADG" size="16" maxlength="16" value="$BDBADG">
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDBADG', array('BADGE HEX')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 

SEGDTA;
 $this->displayErrorClass('BDREPA'); 
		echo <<<SEGDTA
">
                <label for="chgBDREPA">REPARTO
SEGDTA;
 $this->displayIndicator('BDREPA'); 
 $sel_REPA1 = ($BDREPA == 'DA DEFINIRE') ? 'selected' : '';
$sel_REPA2 = ($BDREPA == 'UFFICIO') ? 'selected' : '';
$sel_REPA3 = ($BDREPA == 'MAGAZZINO') ? 'selected' : '';
$sel_REPA4 = ($BDREPA == 'PRODUZIONE') ? 'selected' : '';
$sel_REPA5 = ($BDREPA == 'LABORATORIO') ? 'selected' : '';
		echo <<<SEGDTA
</label>
                             <div>
<select id="chgBDREPA" class="form-control" name="BDREPA">
  <option value="DA DEFINIRE" $sel_REPA1>DA DEFINIRE</option>
  <option value="UFFICIO" $sel_REPA2>UFFICIO</option>
  <option value="MAGAZZINO" $sel_REPA3>MAGAZZINO</option>
  <option value="PRODUZIONE" $sel_REPA4>PRODUZIONE</option>
  <option value="LABORATORIO" $sel_REPA5>LABORATORIO</option>
</select>
                  <span class="error-text">
SEGDTA;
 $this->displayError('BDREPA', array('REPARTO')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>
              <div class="form-group 
SEGDTA;
$this->displayErrorClass('BDTIMB'); 
echo <<<SEGDTA
">
  <label for="chgBDTIMB">TIMBRATORE
SEGDTA;
$this->displayIndicator('BDTIMB'); 
$sel_TIMB0 = ($BDTIMB == '  ') ? 'selected' : '';
$sel_TIMB1 = ($BDTIMB == '70') ? 'selected' : '';
$sel_TIMB2 = ($BDTIMB == '71') ? 'selected' : '';
$sel_TIMB3 = ($BDTIMB == '72') ? 'selected' : '';
echo <<<SEGDTA
</label>
  <div>
    <select id="chgBDTIMB" class="form-control" name="BDTIMB">
      <option value="  " $sel_TIMB0>DA DEFINIRE</option>
      <option value="70" $sel_TIMB1>INGRESSO</option>
      <option value="71" $sel_TIMB2>MAGAZZINO</option>
      <option value="72" $sel_TIMB3>PRODUZIONE</option>
    </select> 

    <span class="error-text">
SEGDTA;
$this->displayError('BDTIMB', array('TIMBRATORE')); 
echo <<<SEGDTA
</span>
  </div>
</div>
SEGDTA;

 $this->displayErrorClass('BDBDTM'); 
		echo <<<SEGDTA

                <label for="chgBDBDTM">BADGE HEX TEMPORANEO 
SEGDTA;

$this->displayIndicator('BDBDTM'); 
$sel_JOLLY0 = ($BDBDTM == '00000000000000') ? 'selected' : '';
$sel_JOLLY1 = ($BDBDTM == '04FA56D6FF6180') ? 'selected' : '';
$sel_JOLLY2 = ($BDBDTM == '04EB2874BF6180') ? 'selected' : '';
$sel_JOLLY3 = ($BDBDTM == '0406ED76BF6180') ? 'selected' : '';
$sel_JOLLY4 = ($BDBDTM == '04F025D7FF6180') ? 'selected' : '';
$sel_JOLLY5 = ($BDBDTM == '04FBC5D6FF6180') ? 'selected' : '';
		echo <<<SEGDTA
</label>
                <div>
    <select id="chgBDBDTM" class="form-control" name="BDBDTM">
      <option value="00000000000000" $sel_JOLLY0>NESSUNO</option>
      <option value="04FA56D6FF6180" $sel_JOLLY1>JOLLY 1</option>
      <option value="04EB2874BF6180" $sel_JOLLY2>JOLLY 2</option>
      <option value="0406ED76BF6180" $sel_JOLLY3>JOLLY 3</option>
      <option value="04F025D7FF6180" $sel_JOLLY4>JOLLY 4</option>
      <option value="04FBC5D6FF6180" $sel_JOLLY5>JOLLY 5</option>
    </select>
           <span class="error-text">
SEGDTA;

if($starter == 'BDCOGE') { 
$this->displayError('BDBDTM', array('BADGE TEMPORANEO')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>              
              
              	
            </div>
            <div id="navbottom">
  <input type="submit" class="btn btn-primary accept" value="Salva e Chiudi" onclick="submitAndClose();" />
  <input type="button" class="btn btn-default cancel" value="Cancel" onclick="window.close();" />	

<script>
function submitAndClose() {
  const form = document.getElementById('change-form');
  if (form) {
    form.submit();
    setTimeout(() => {
    window.opener.location.reload();
    window.close();
    }, 500);
  }
}

</script>

            </div>		
          </form>
        </div>
      </div>
    </div>
    <script type="text/javascript">
		jQuery(function() {
			
			//jQuery("input[name='BDBADG']").attr("disabled",true);
			
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
	else
	$this->displayError('BDBDTM', array('BADGE TEMPORANEO')); 
		echo <<<SEGDTA
</span>
                </div>
              </div>              
              
              	
            </div>
            <div id="navbottom">
  <input type="submit" class="btn btn-primary accept" value="Salva" />
  <input type="button" class="btn btn-default cancel" value="Indietro" />
  
  <button type="submit" name="go" value="prev" class="btn btn-info">Indietro</button>
  <button type="submit" name="go" value="next" class="btn btn-info">Successivo</button>
  <input type="hidden" name="BDBADG_PREV" value="$prevBadge">
  <input type="hidden" name="BDBADG_NEXT" value="$nextBadge">

            </div>		
          </form>
        </div>
      </div>
    </div>
    <script type="text/javascript">
		jQuery(function() {
			
			//jQuery("input[name='BDBADG']").attr("disabled",true);
			
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
		
		$this->pf_liblLibs[1] = 'BCD_DATIV2';
		
		parent::__construct();

		$this->pf_scriptname = 'gestdipe.php';
		$this->pf_wcm_set = 'PRODUZIONE';
		
		
		$this->xl_set_env($this->pf_wcm_set);
		$this->showRequiredIndicator = false; 
		// Last Generated CRC: CC72BA7F 271C138D 9E1FACE7 744EB416
		// Last Generated Date: 2024-06-25 11:09:26
		// Path: gestdipe.phw
	}
}

// Auto-load this WebSmart object (by calling xlLoadWebSmartObject) if this script is called directly (not via an include/require).
// Comment this line out if you do not wish this object to be invoked directly.
xlLoadWebSmartObject(__FILE__, 'gest_dipe');?>
<script>
function resetAndSubmit() {
  const form = document.getElementById('filter-form');
  if (form) {
    // Pulisce tutti gli input e i select
    form.querySelectorAll('input[type="text"], select').forEach(el => el.value = '');
    form.submit();
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('filter-form');
  if (!form) return;

  // Aggiungi listener a tutti i campi input e select nel form
  form.querySelectorAll('input[type="text"], select').forEach(field => {
    field.addEventListener('change', () => {
      form.submit();
    });
  });
});
</script>