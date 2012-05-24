<?php

class AutoModeler_Exception_Validation extends AutoModeler_Exception
{
	public function __construct(array $errors, $exception_text, $code = NULL)
	{
		$this->errors = $errors;
		parent::__construct($exception_text, $code);
	}
}
