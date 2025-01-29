<?php
/**
 * Nexus Class to retrieve information from Nexus
 */
class Nexus
{
	protected $db_connection;
	
	/**
	 * Get the Session Info using the nexusaccess cookie and querying the session file directly
	 * on success this function will return an array, on failure it will return null
	 */
	protected function getNexusSessionInfo()
	{
		// Retrieve the session information from the nexus cookie
		$nexusAccess = $_COOKIE['nexusaccess'];
		if ($nexusAccess === '' || $nexusAccess === '*NONE')
		{
			return NULL;
		}
	
		// Retrieve session data
		$selString = "select SVSESSION, SVVARID, SVVARSEQ, SVDATA from XL_WEBSPT/PW_SVARF "
					. " where SVSESSION = :SVSESSION AND SVVARID = :SVVARID";
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			return NULL;
		}
		
		$SVVARID = 'SESSDS';
		$stmt->bindValue(':SVSESSION', $nexusAccess, PDO::PARAM_STR);
		$stmt->bindValue(':SVVARID', $SVVARID, PDO::PARAM_STR);
		
		$result = $stmt->execute();
		if ($result === false) 
		{
			return NULL;
		}
		
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($row['SVDATA'] == '')
		{
			return NULL;
		}
	
		return [
			'siteId' => (int) substr($row["SVDATA"], 0, 5),
			'userId' => (int) substr($row["SVDATA"], 5, 7),
			'userName' => trim(substr($row["SVDATA"], 12, 128))
		];
	}
	
	/**
	 * Set the database connection
	 */
	function setDbConnection($dbConn)
	{
		$this->db_connection = $dbConn;
	}
	
	/**
	 * Get the Nexus information for the currently logged in user
	 * This is either a specific value or the full user details
	 */
	function getNexusInfo($nxinfval, $nexusLib = "*LIBL")
	{
		$nexusSessionInfo = $this->getNexusSessionInfo();
		if (is_null($nexusSessionInfo))
		{
			return;
		}
		
		// Retrieve session data
		$selString = "select * from ".$nexusLib."/SMUSRF where USSITNBR = :USSITNBR and USUNBR = :USUNBR and USUSER = :USUSER";
		// Prepare the statement
		$stmt = $this->db_connection->prepare($selString);
		if (!$stmt)
		{
			return;
		}
		
		$stmt->bindValue(':USSITNBR', $nexusSessionInfo['siteId'], PDO::PARAM_INT);
		$stmt->bindValue(':USUNBR', $nexusSessionInfo['userId'], PDO::PARAM_INT);
		$stmt->bindValue(':USUSER', $nexusSessionInfo['userName'], PDO::PARAM_STR);
		
		$result = $stmt->execute();
		if ($result === false) 
		{
			return ;
		}
		
		$nxdata = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// Return the appropriate value
		switch($nxinfval)
		{
			case "*USERID": return $nxdata["USUSER"]; break;
			case "*FNAME": return $nxdata["USFNAM"]; break;
			case "*LNAME": return $nxdata["USLNAM"]; break;
			case "*EMAIL": return $nxdata["USEMAL"]; break;
			case "*USERNBR": return $nxdata["USUNBR"]; break;
			case "*MANAGE": return $nxdata["USGMNG"]; break;
			case "*SITENUM": return $nxdata["USSITNBR"]; break;
			default: return $nxdata;
		}
	}
}