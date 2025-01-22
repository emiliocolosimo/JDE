<?php
require_once('WSValidator.php');

class WSRequiredValidator extends WSValidator
{
	protected $options = ['errorText' => '%s is a required field.'];
	public function isValid($value)
	{
		if($value != "")
		{
			return true;
		}
		return false;
	}
}