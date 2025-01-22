<?php
class WSValidator
{
	protected $options = ['errorText' => 'This is not valid.'];
	function __construct($options = array()) 
	{
		if(is_array($options))
			$this->options = array_merge($this->options, $options);
	}
	public function getErrorText()
	{
		return $this->options['errorText'];
	}
}