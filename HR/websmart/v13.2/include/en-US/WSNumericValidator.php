<?php
require_once('WSValidator.php');

class WSNumericValidator extends WSValidator
{
	protected $options = ['errorText' => 'This is not a numeric value.'];
	public function isValid($value)
	{
		if(!is_numeric($value))
		{
			return false;
		}
		return true;
	}
}