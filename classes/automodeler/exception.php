<?php

class AutoModeler_Exception extends Kohana_Exception
{
	public $errors;

	public function __construct($title, array $message = NULL, $errors = '')
	{
		parent::__construct($title, $message);
		$this->errors = $errors;
	}

	public function __toString()
	{
		return $this->message;
	}
}