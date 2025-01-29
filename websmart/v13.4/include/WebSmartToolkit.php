<?php

require_once('ToolkitService.php');
require_once('WebSmartToolkitParameter.php');
require_once('WebSmartToolkitDataStructure.php');

// Helper class for calling a Toolkit program with our WebSmartToolkitParameterList implementation
class WebSmartToolkit
{
	private $tkConnection = NULL;

	// Create the primary toolkit connection, will throw an exception on failure
	public function __construct($DatabaseNameOrResource = '*LOCAL', $sUserOrI5NamingFlag = '', $sPassword = '', $sTransportType = '', $bIsPersistent = false)
	{
		$this->tkConnection = ToolkitService::getInstance($DatabaseNameOrResource, $sUserOrI5NamingFlag, $sPassword, $sTransportType, $bIsPersistent);
	}

	// Close the toolkit connection
	public function __destruct()
	{
		if ($this->tkConnection != NULL)
		{
			$this->tkConnection->disconnect();
		}
	}

	// Return the ToolkitService instance
	public function GetToolkitService()
	{
		return $this->tkConnection;
	}

	// Call the given program using the given tkConnection, passing in the specified Parameters
	public function CallProgram($sProgram, $sLibrary, $Parameters)
	{
		// Setup toolkit parameter structure, will return a response structure on error
		$tkParameters = $this->BuildToolkitParameters($Parameters);
		if (array_key_exists('status', $tkParameters))
		{
			return $tkParameters;
		}

		// NOTE: The following code can be used to debug the XML that is sent to the XMLService Toolkit
		//$XMLWrapper = new XMLWrapper(array('encoding' => $this->tkConnection->getOption('encoding')), $this->tkConnection);
		//$inputXml = $XMLWrapper->buildXmlIn($tkParameters, NULL, $sProgram, $sLibrary, NULL);
		//die($inputXml);
		
		// Make the program call
		$Result = $this->tkConnection->PgmCall($sProgram, $sLibrary, $tkParameters, NULL, NULL);
		if ($Result)
		{
			$this->ExtractResult($Result, $Parameters);
			$Response = $Parameters->GetOutputParameters();
		}
		else
		{
			// An error occured during the program call
			$Response = array(
				'status' => 'error',
				'httpstatus' => 500,
				'message' => 'Error executing PGM: ' . $this->tkConnection->getErrorMsg()
			);
		}
		
		return $Response;
	}

	// Build a list of toolkit parameters based on the incoming parameters
	public function BuildToolkitParameters($Parameters)
	{
		$tkParameters = array();
		foreach ($Parameters->Parameters as $Parameter)
		{
			if ($Parameter instanceof WebSmartToolkitParameter)
			{
				$tkParameter = $this->BuildToolkitParameter($Parameter);
			}
			else if ($Parameter instanceof WebSmartToolkitDataStructure)
			{
				if ($Parameter->nDim > 0)
				{
					$DSParameters = $this->BuildDataStructArrayParameter($Parameter->Parameters, $Parameter->nDim);
				}
				else
				{
					$DSParameters = $this->BuildDataStructParameter($Parameter->Parameters);
				}

				if (array_key_exists('status', $DSParameters))
				{
					$tkParameter = $DSParameters;
				}
				else
				{
					$tkParameter = $this->tkConnection->AddDataStruct($DSParameters, $Parameter->sName);
				}
			}

			// Make sure an error wasn't returned from the parameter creation process
			if (array_key_exists('status', $tkParameter))
			{
				return $tkParameter;
			}

			$tkParameters[] = $tkParameter;
		}

		return $tkParameters;
	}

	// Build a list Toolkit parameters from the given data struct array
	public function BuildDataStructArrayParameter($Parameters, $nDim)
	{
		$DSParameterList = array();

		for ($i = 0; $i < $nDim; $i++)
		{
			$sNameSuffix = '_' . $i;
			$Result = $this->BuildDataStructParameter($Parameters[$i], $sNameSuffix);

			if (array_key_exists('status', $Result))
			{
				return $Result;
			}

			$DSParameterList = array_merge($DSParameterList, $Result);
		}

		return $DSParameterList;
	}

	// Build a list of Toolkit parameters from the given data struct
	public function BuildDataStructParameter($Parameters, $sNameSuffix = '')
	{
		$DSParameters = array();


		foreach ($Parameters as $Parameter)
		{
			$Result = $this->BuildToolkitParameter($Parameter);

			// If there is a status in the result, pass it back up to the caller
			if (array_key_exists('status', $Result))
			{
				return $Result;
			}

			// If a name suffix was supplied, append it to the parameter name
			if (!empty($sNameSuffix))
			{
				$Result->setParamName($Result->getParamName() . $sNameSuffix);
			}
			
			$DSParameters[] = $Result;
		}

		return $DSParameters;
	}

	// Build a Toolkit parameter from the given Parameter
	public function BuildToolkitParameter($Parameter)
	{
		switch ($Parameter->sType)
		{
			case 'alpha':
				$tkParameter = $this->tkConnection->AddParameterChar(
					$Parameter->sDirection, 
					$Parameter->nLength,
					'', 
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'packed':
				$tkParameter = $this->tkConnection->AddParameterPackDec(
					$Parameter->sDirection,
					$Parameter->nLength,
					$Parameter->nDecimals,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'zoned':
				$tkParameter = $this->tkConnection->AddParameterZoned(
					$Parameter->sDirection,
					$Parameter->nLength,
					$Parameter->nDecimals,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'int8':
				$tkParameter = $this->tkConnection->AddParameterInt8(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'int16':
				$tkParameter = $this->tkConnection->AddParameterInt16(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'int32':
				$tkParameter = $this->tkConnection->AddParameterInt32(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'int64':
				$tkParameter = $this->tkConnection->AddParameterInt64(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'uint8':
				$tkParameter = $this->tkConnection->AddParameterUInt8(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'uint16':
				$tkParameter = $this->tkConnection->AddParameterUInt16(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'uint32':
				$tkParameter = $this->tkConnection->AddParameterUInt32(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'uint64':
				$tkParameter = $this->tkConnection->AddParameterUInt64(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			case 'float':
				$tkParameter = $this->tkConnection->AddParameterFloat(
					$Parameter->sDirection,
					'',
					$Parameter->sName,
					$Parameter->sValue);
				break;
			
			default:
			// In the event we get an unsupported param type, return an error
				$Response = array(
					'status' => 'error',
					'httpstatus' => 400,
					'message' => 'Unsupported parameter type ' . $Parameter->sType
				);
				return $Response;
		}

		return $tkParameter;
	}

	// Extract updated parameter values from the passed in toolkit call result
	public function ExtractResult($Result, &$Parameters)
	{
		// Extract the parameters from the result
		foreach ($Parameters->Parameters as &$Parameter)
		{
			if ($Parameter instanceof WebSmartToolkitParameter)
			{
				$this->ExtractParameterResult($Result['io_param'], $Parameter);
			}
			else if ($Parameter instanceof WebSmartToolkitDataStructure)
			{
				if ($Parameter->nDim > 0)
				{
					$this->ExtractDataStructureArrayResult($Result['io_param'][$Parameter->sName], $Parameter);
				}
				else
				{
					$this->ExtractDataStructureResult($Result['io_param'][$Parameter->sName], $Parameter->Parameters);
				}
			}
		}
	}

	// Extract the Toolkit call result into the passed in Parameter
	public function ExtractParameterResult($Result, &$Parameter)
	{
		if ($Parameter->sDirection === 'out' || $Parameter->sDirection === 'both')
		{
			if ($Parameter->nDim > 0)
			{
				$Parameter->sValue = array();
				for ($i = 0; $i < $Parameter->nDim; $i++)
				{
					$Parameter->sValue[] = $this->GetParameterValue($Result[$Parameter->sName], $Parameter, $i);
				}
			}
			else
			{
				$Parameter->sValue = $this->GetParameterValue($Result, $Parameter);
			}
		}
	}

	// Extract the Toolkit call result for the passed in data struct array
	public function ExtractDataStructureArrayResult($Result, &$Parameter)
	{
		for ($i = 0; $i < $Parameter->nDim; $i++)
		{
			$this->ExtractDataStructureResult($Result, $Parameter->Parameters[$i], $i);
		}
	}

	// Extract the Toolkit call result for the passed in data struct
	public function ExtractDataStructureResult($Result, &$Parameters, $nDim = -1)
	{
		foreach ($Parameters as &$Parameter)
		{
			if ($Parameter->sDirection === 'out' || $Parameter->sDirection === 'both')
			{
				// If this data structure contains a parameter that is an array, we need to handle that in a special way
				if ($Parameter->nDim > 0)
				{
					$sParameterName = $Parameter->sName;
					if ($nDim >= 0)
					{
						$sParameterName .= '_' . $nDim;
					}

					$Parameter->sValue = array();
					for ($i = 0; $i < $Parameter->nDim; $i++)
					{
						$Parameter->sValue[] = $this->GetParameterValue($Result[$sParameterName], $Parameter, $i);
					}
				}
				// Otherwise just extract the value
				else
				{
					$Parameter->sValue = $this->GetParameterValue($Result, $Parameter, $nDim);
				}
			}
		}
	}

	// Return the Toolkit call result value for the given Parameter
	public function GetParameterValue($Result, $Parameter, $nDim = -1)
	{
		if ($nDim >= 0)
		{
			$sValueKey = $Parameter->sName . '_' . $nDim;
			if (array_key_exists($sValueKey, $Result))
			{
				$Value = $Result[$sValueKey];
			}
			else
			{
				$Value = '';
			}
		}
		else
		{
			$Value = $Result[$Parameter->sName];
		}

		switch ($Parameter->sType)
		{
			case 'packed':
			case 'zoned':
			case 'float':
				$Value = floatval($Value);
				break;
			case 'int8':
			case 'int16':
			case 'int32':
			case 'int64':
			case 'uint8':
			case 'uint16':
			case 'uint32':
			case 'uint64':
				$Value = intval($Value);
				break;
		}

		return $Value;
	}
}