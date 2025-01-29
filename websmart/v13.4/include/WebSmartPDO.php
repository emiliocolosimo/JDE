<?php

// extend the classes PDO and PDOStatement so that we can replace blank strings with a specific value or null
class WebSmartPDO extends PDO
{
	// the default values to use for replacing blank values in prepared statements
	// null will be used if none is set
	protected $defaultReplacements = [];

	public function __construct($dsn, $username = "", $password = "", $driver_options = array())
    {
		parent::__construct($dsn, $username, $password, $driver_options);

		// set to return an WebSmartPDOStatement object for statements instead of a PDOStatement object
		$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('WebSmartPDOStatement', array($this)));
	}

	// set the default replacement text for a type
	public function setDefaultReplacement($type, $value)
	{
		// get the type without the input/output flag
		$type = $type & ~PDO::PARAM_INPUT_OUTPUT;

		$this->defaultReplacements[$type] = $value;
	}

	// get the default replacement text for a type
	public function getDefaultReplacement($type)
	{
		// get the type without the input/output flag
		$type = $type & ~PDO::PARAM_INPUT_OUTPUT;

		// if no default was set up, return null
		if (!isset($this->defaultReplacements[$type]))
		{
			return null;
		}

		return $this->defaultReplacements[$type];
	}

	// check if a default replacement value was set
	public function hasDefaultReplacement($type)
	{
		// get the type without the input/output flag
		$type = $type & ~PDO::PARAM_INPUT_OUTPUT;

		return isset($this->defaultReplacements[$type]);
	}
}

// an instance of this class will be returned from WebSmartPDO::prepare()
class WebSmartPDOStatement extends PDOStatement
{
	protected $db_connection;
	
    protected function __construct($db_connection)
    {
    	$this->db_connection = $db_connection;
    }
	
	// binds a value to a parameters, will replace blank values with a replacement value
	// if no replacement value is given then the default replacement value will be used
	public function bindValue($paramno, $param, $type = PDO::PARAM_STR, $replacementValue = null)
	{
		// if the parameter value is empty
		if (empty($param))
		{
			// if a replacement value was passed in
			if (func_num_args() >= 4)
			{
				$param = $replacementValue;
			}
			// if a default replacement was set for the type
			elseif ($this->db_connection->hasDefaultReplacement($type))
			{
				$param = $this->db_connection->getDefaultReplacement($type);
			}
		}

		return parent::bindValue($paramno, $param, $type);
	}
}
