<?php
require_once('WSValidator.php');

class WSRequiredValidator extends WSValidator
{
	protected $options = ['errorText' => '%sは必須項目です'];
	public function isValid($value)
	{
		if($value != "")
		{
			return true;
		}
		return false;
	}
}