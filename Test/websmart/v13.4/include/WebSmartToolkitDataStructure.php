<?php

// A representation of a Toolkit DataStructure parameter
class WebSmartToolkitDataStructure
{
	public $sName;
	public $Parameters;
	public $bRequired;
	public $nDim;

	private $InternalParameters;

	public function __construct($sName, $Parameters, $bRequired, $nDim)
	{
		$this->sName = $sName;
		$this->InternalParameters = $Parameters;
		$this->bRequired = $bRequired;
		$this->nDim = $nDim;

		// Rebuild the Parameters list, based on the InternalParameters and nDim
		$this->RebuildParameters();
	}

	public function SetName($sName)
	{
		$this->sName = $sName;
		return $this;
	}

	public function SetParameters($Parameters)
	{
		$this->InternalParameters = $Parameters;

		// Since we're replacing the parameters, we need to rebuild the full list
		$this->RebuildParameters();

		return $this;
	}

	public function SetRequired($bRequired)
	{
		$this->bRequired = $bRequired;
		return $this;
	}

	public function SetDimension($nDim)
	{
		$this->nDim = $nDim;

		// Since we're adjusting the dimension of the DS, we need to rebuild the list
		$this->RebuildParameters();

		return $this;
	}

	// Based on the $InternalParameters and $nDim variables, rebuild $Parameters
	private function RebuildParameters()
	{
		// If this is an array of data structures, create an array for the paramaters
		if ($this->nDim > 0)
		{
			// Clear any existing Parameters first, then copy InternalParameters nDim times into Parameters
			$this->Parameters = array();

			for ($i = 0; $i < $this->nDim; $i++)
			{
				// Due to PHP by default using references to objects in arrays, we need to clone the objects manually
				$Parameters = array_map(function ($Object) { return clone $Object; }, $this->InternalParameters);

				// If any of the parameters in the DS is an array, populate its default value for each entry
				foreach ($Parameters as &$Parameter)
				{
					if ($Parameter->nDim > 0)
					{
						$DefaultValue = $Parameter->sValue;
						$Parameter->sValue = array();
						for ($j = 0; $j < $Parameter->nDim; $j++)
						{
							$Parameter->sValue[] = $DefaultValue;
						}
					}
				}

				$this->Parameters[] = $Parameters;
			}
		}
		// Otherwise just copy InternalParameters to Parameters
		else
		{
			$this->Parameters = $this->InternalParameters;
		}
	}
}