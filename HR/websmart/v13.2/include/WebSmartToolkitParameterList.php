<?php

require_once('WebSmartToolkitParameter.php');
require_once('WebSmartToolkitDataStructure.php');

// A class to extend for WebSmartToolkit parameter list model instances
class WebSmartToolkitParameterList
{
	public $Parameters;
	public $Error;
	
	public function __construct()
	{
		$this->Parameters = array();
	}

	// Add the given parameter to the parameters list
	public function AddParameter($sDirection, $sType, $nLength, $nDecimals, $sName, $bRequired = false, $sValue = '', $nDim = 0)
	{
		$Parameter = $this->CreateParameter($sDirection, $sType, $nLength, $nDecimals, $sName, $bRequired, $sValue, $nDim);
		$this->Parameters[] = $Parameter;
		return $Parameter;
	}

	// Add the given data structure to the parameters list
	public function AddDataStructure($sName, $Parameters = array(), $bRequired = false, $nDim = 0)
	{
		$Parameter = new WebSmartToolkitDataStructure($sName, $Parameters, $bRequired, $nDim);
		$this->Parameters[] = $Parameter;
		return $Parameter;
	}

	// Create the given parameter and return it without adding it to the parameters list
	public function CreateParameter($sDirection, $sType, $nLength, $nDecimals, $sName, $bRequired = false, $sValue = '', $nDim = 0)
	{
		return new WebSmartToolkitParameter($sDirection, $nLength, $nDecimals, $sName, $sValue, $sType, $bRequired, $nDim);
	}

	// Parse the input data from the browser
	public function ProcessInputParameters($InputData, $bValidate = true)
	{
		$this->Error = null;
		$bResult = true;
		
		foreach ($this->Parameters as &$Parameter)
		{
			if ($Parameter instanceof WebSmartToolkitParameter)
			{
				$bResult = $this->ProcessParameter($Parameter, $InputData, '', $bValidate);
			}
			else if ($Parameter instanceof WebSmartToolkitDataStructure)
			{
				$bResult = $this->ProcessDataStructure($Parameter, $InputData, $bValidate);
			}

			if (!$bResult)
			{
				break;
			}
		}
		
		return $bResult;
	}

	/// Parse input data from the browser for a data structure
	private function ProcessDataStructure(&$Parameter, $InputData, $bValidate = true)
	{
		$bResult = true;
		if (isset($InputData[$Parameter->sName]))
		{
			$InputData = $InputData[$Parameter->sName];

			// If the parameter is an array
			if ($Parameter->nDim > 0)
			{
				// If the data isn't a sequential array, the input wasn't a JSON array, return an error
				if (!$this->isSequentialArray($InputData))
				{
					$this->Error = array(
						'status' => 'error',
						'httpstatus' => 400,
						'message' => 'Array expected for parameter ' . $Parameter->sName
					);
					return false;
				}
				// If the parameter is an array, and too much data is passed in, return an error
				else if (count($InputData) > $Parameter->nDim)
				{
					$this->Error = array(
						'status' => 'error',
						'httpstatus' => 400,
						'message' => 'Too many array entries for parameter ' . $Parameter->sName
					);
					return false;
				}

				// For each entry that is provided, update the value from the input
				for ($i = 0; $i < $Parameter->nDim; $i++)
				{
					if (array_key_exists($i, $InputData))
					{
						$sParentSuffix = "{$Parameter->sName}[{$i}].";
						$bResult = $this->ProcessDataStructureParameters($Parameter->Parameters[$i], $InputData[$i], $sParentSuffix, $bValidate);

						// If an error occurred, break out of the loop so the error is returned to the caller
						if (!$bResult)
						{
							break;
						}
					}
				}
			}
			else
			{
				$sParentSuffix = "{$Parameter->sName}.";
				$bResult = $this->ProcessDataStructureParameters($Parameter->Parameters, $InputData, $sParentSuffix, $bValidate);
			}
		}
		else if ($Parameter->bRequired)
		{
			$this->Error = array(
				'status' => 'error',
				'httpstatus' => 400,
				'message' => 'Missing required parameter ' . $Parameter->sName
			);
			$bResult = false;
		}

		return $bResult;
	}

	// Parse input data from the browser for a data structure
	private function ProcessDataStructureParameters(&$Parameters, $InputData, $sParentSuffix, $bValidate = true)
	{
		$bResult = true;
		foreach ($Parameters as &$DSParameter)
		{
			$bResult = $this->ProcessParameter($DSParameter, $InputData, $sParentSuffix, $bValidate);
			if (!$bResult)
			{
				break;
			}
		}

		return $bResult;
	}

	// Parse input data from the browser for a base parameter type
	private function ProcessParameter(&$Parameter, $InputData, $sParentSuffix = '', $bValidate = true)
	{
		if ($Parameter->sDirection === 'in' || $Parameter->sDirection === 'both')
		{
			// parameter should be present and not empty to be considered it's there, so we can also return
			// it's a required parameter when it may be there but empty
			if (isset($InputData[$Parameter->sName]) && $InputData[$Parameter->sName] != "")
			{
				// Array validation
				if ($Parameter->nDim > 0)
				{
					// If the parameter is an array, but the input data isn't, return an error
					if (!is_array($InputData[$Parameter->sName]))
					{
						$this->Error = array(
							'status' => 'error',
							'httpstatus' => 400,
							'message' => 'Array expected for parameter ' . $sParentSuffix . $Parameter->sName
						);
						return false;
					}
					// If the parameter is an array, and too much data is passed in, return an error
					else if (count($InputData[$Parameter->sName]) > $Parameter->nDim)
					{
						$this->Error = array(
							'status' => 'error',
							'httpstatus' => 400,
							'message' => 'Too many array entries for parameter ' . $sParentSuffix . $Parameter->sName
						);
						return false;
					}
					// If the incoming array isn't the full length, pad it out
					else if (count($InputData[$Parameter->sName]) < $Parameter->nDim)
					{
						// For any missing parameter value, just create an empty string, it'll be converted to the appropriate type later
						for ($i = count($InputData[$Parameter->sName]); $i < $Parameter->nDim; $i++)
						{
							$InputData[$Parameter->sName][] = '';
						}
					}
				}
				
				$Parameter->sValue = $InputData[$Parameter->sName];
				
				if ($bValidate)
				{
					$this->ValidateParameter($Parameter);
					if (!$Parameter->IsValid())
					{
						$this->Error = array(
							'status' => 'error',
							'httpstatus' => 400,
							'message' => $Parameter->GetError() . ' Parameter: ' . $sParentSuffix . $Parameter->sName
						);
						return false;
					}
				}
			}
			else if ($Parameter->bRequired)
			{
				$this->Error = array(
					'status' => 'error',
					'httpstatus' => 400,
					'message' => 'Missing required parameter ' . $sParentSuffix . $Parameter->sName
				);
				return false;
			}
			// For non-required input/both parameters, we still need to build out the sValue property for arrays, so the XML is built correctly
			else if ($Parameter->nDim > 0)
			{
				$Parameter->sValue = $this->CreateEmptyParameterValueArray($Parameter);
			}
		}
		else
		{
			// For output parameters, we still need to build out the sValue property for arrays, so the XML is built correctly
			if ($Parameter->nDim > 0)
			{
				$Parameter->sValue = $this->CreateEmptyParameterValueArray($Parameter);
			}
		}

		return true;
	}
	
	/**
	 * Validate the parameter. A parameter can be a simple value or an array.
	 * If it is an array call the ValidateParameterValue on each value.
	 */
	private function ValidateParameter(&$Parameter)
	{
		if ($Parameter->nDim > 0) 
		{
			// value can be any type, don't prefix with a specific type
			foreach ($Parameter->sValue as $value) 
			{
				$this->ValidateParameterValue($Parameter, $value);
			}	
		}
		else
		{
			$this->ValidateParameterValue($Parameter, $Parameter->sValue);
		}
	}
	
	/**
	 * Validate each parameter value. Function needs to be called for each value in an array.
	 */
	private function ValidateParameterValue(&$Parameter, $value)
	{
		$sGenericType = $Parameter->GetGenericType();
		
		// reset to valid without an error
		$Parameter->SetIsValid(true)->SetErrorText('');	

		switch ($sGenericType) 
		{
			case 'string':
				if (strlen($value) > $Parameter->nLength) 
				{
					$Parameter->SetIsValid(false)->SetErrorText('Parameter length is longer than the definition.');
				}
				break;
			case 'decimal':
				if (!$this->IsValidNumber($value))
				{
					$Parameter->SetIsValid(false)->SetErrorText('Parameter value is not a number.');
				} 
				else
				{
					$sValue = str_replace("-", "", $value);
					$nDecimalPosition = strpos($sValue, ".");
					// should there be no decimal found, assume this is a whole number only and set the variables accordingly
					if (!$nDecimalPosition)
					{
						$sWholeNumber = $sValue;
						$sFraction = '';
					}
					else
					{
						$sWholeNumber = substr($sValue, 0, $nDecimalPosition);
						$sFraction = substr($sValue, $nDecimalPosition + 1);
					}
					$nTotalLength = strlen($sWholeNumber) + strlen($sFraction);
					if ($nTotalLength > $Parameter->nLength || strlen($sFraction) > $Parameter->nDecimals) 
					{
						$Parameter->SetIsValid(false)->SetErrorText('Input value doesn\'t match the definition.');
					}	
				}
				break;
			case 'integer':
				if (!$this->IsValidNumber($value))
				{
					$Parameter->SetIsValid(false)->SetErrorText('Parameter value is not a number.');
				}
				break;
			case 'uinteger':
				if (!$this->IsValidNumber($value) || $value < 0)
				{
					$Parameter->SetIsValid(false)->SetErrorText('Input value doesn\'t match the definition.');
				}
				break;
			default:
				$Parameter->SetIsValid(true)->SetErrorText('');	
		}	
	}
	
	private function IsValidNumber($sValue)
	{
		if ($sValue !== "" && !is_numeric($sValue))
		{
			return false;
		}
		
		return true;
	}
	
	// Create an array of empty strings based on the given Parameter's array dimension
	private function CreateEmptyParameterValueArray($Parameter)
	{
		$Value = array();
		for ($i = 0; $i < $Parameter->nDim; $i++)
		{
			$Value[] = '';
		}

		return $Value;
	}
	
	// Build an array of OUT and BOTH parameters
	public function GetOutputParameters()
	{
		$Result = new ArrayObject();
		
		foreach ($this->Parameters as $Parameter)
		{
			if ($Parameter instanceof WebSmartToolkitParameter)
			{
				if ($Parameter->sDirection === 'both' || $Parameter->sDirection === 'out')
				{
					$Result[$Parameter->sName] = $Parameter->sValue;
				}
			}
			else if ($Parameter instanceof WebSmartToolkitDataStructure)
			{
				$DSResult = array();
				if ($Parameter->nDim > 0)
				{
					for ($i = 0; $i < $Parameter->nDim; $i++)
					{
						$TempResult = $this->GetDataStructureOutputParameters($Parameter->Parameters[$i]);

						// Only add the data structure output if it contains both/output parameters
						if (count($TempResult) > 0)
						{
							$DSResult[] = $TempResult;
						}
					}
				}
				else
				{
					$DSResult = $this->GetDataStructureOutputParameters($Parameter->Parameters);
				}

				if (count($DSResult) > 0)
				{
					$Result[$Parameter->sName] = $DSResult;
				}
			}
		}
		
		return $Result;
	}

	// GetBuild an array of OUT and BOTH parameters of the given data structure
	private function GetDataStructureOutputParameters($Parameters)
	{
		$Result = new ArrayObject();
		foreach ($Parameters as $Parameter)
		{
			if ($Parameter->sDirection === 'both' || $Parameter->sDirection === 'out')
			{
				$Result[$Parameter->sName] = $Parameter->sValue;
			}
		}

		return $Result;
	}

	// Return the previous error
	public function GetError()
	{
		return $this->Error;
	}

	// Return true if the passed in array is sequential
	private function isSequentialArray($Arr)
	{
		// Consider empty arrays to be sequential
		if (count($Arr) == 0)
		{
			return true;
		}

		// If the keys of the array match the ordered set of keys from 0 to the length of the array, it's sequential
		$OrderedKeys = range(0, count($Arr) - 1);
		if (array_keys($Arr) == $OrderedKeys)
		{
			return true;
		}

		return false;
	}
}
