<?php
class WSValidator
{
	protected $options = ['errorText' => 'これは有効ではありません。'];
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