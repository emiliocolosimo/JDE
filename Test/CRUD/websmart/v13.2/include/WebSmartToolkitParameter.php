<?php

// A representation of a Toolkit parameter
class WebSmartToolkitParameter
{
	public $sDirection;
	public $nLength;
	public $nDecimals;
	public $sName;
	public $sValue;
	public $sType;
	public $bRequired;
	public $nDim;
	protected $isValid = false;
	protected $sError = '';
	
	
	public function __construct($sDirection, $nLength, $nDecimals, $sName, $sValue, $sType, $bRequired, $nDim)
	{
		$this->sDirection = $sDirection;
		$this->nLength = $nLength;
		$this->nDecimals = $nDecimals;
		$this->sName = $sName;
		$this->sValue = $sValue;
		$this->sType = $sType;
		$this->bRequired = $bRequired;
		$this->nDim = $nDim;
	}

	public function SetDirection($sDirection)
	{
		$this->sDirection = $sDirection;
		return $this;
	}

	public function SetLength($nLength)
	{
		$this->nLength = $nLength;
		return $this;
	}

	public function SetDecimals($nDecimals)
	{
		$this->nDecimals = $nDecimals;
		return $this;
	}

	public function SetName($sName)
	{
		$this->sName = $sName;
		return $this;
	}

	public function SetValue($sValue)
	{
		$this->sValue = $sValue;
		return $this;
	}

	public function SetType($sType)
	{
		$this->sType = $sType;
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
		return $this;
	}
	
	public function GetGenericType()
	{
		if ($this->sType === 'alpha')
		{
			return 'string';
		}
		
		if ($this->sType === 'packed' ||  $this->sType === 'float' || $this->sType === 'zoned')	
		{
			return 'decimal';
		}
		
		if ($this->sType === 'int8' || $this->sType === 'int16' || $this->sType === 'int32' || $this->sType === 'int64')
		{
			return 'integer';
		}
		
		// positive integer only
		if ($this->sType === 'uint8' || $this->sType === 'uint16' || $this->sType === 'uint32' || $this->sType === 'uint64')
		{
			return 'uinteger';
		}
		
		return 'unknown';
	}
	
	public function SetIsValid($isValid)
	{
		$this->isValid = $isValid;
		return $this;
	}
	
	public function SetErrorText($sError)
	{
		$this->sError = $sError;
		return $this;
	}
	
	public function IsValid()
	{
		return $this->isValid;
	}
	
	public function GetError()
	{
		return $this->sError;
	}
}