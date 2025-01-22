<?php
require_once('WSValidator.php');

class WSDateValidator extends WSValidator
{
	protected $options = ['dateFormat' => 'Y-m-d', 'errorText' => 'This is not a valid date.'];
	public function isValid($value)
	{
		$d = DateTime::createFromFormat($this->options["dateFormat"], $value);
		
		if(!$d || !($d->format($this->options["dateFormat"]) == $value)) {
			return false;
		}
		return true;
	}
}