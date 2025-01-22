<?php
require_once('WSValidator.php');

class WSNumericValidator extends WSValidator
{
	protected $options = ['errorText' => 'これは数値ではありません。'];
	public function isValid($value)
	{
		if(!is_numeric($value))
		{
			return false;
		}
		return true;
	}
}