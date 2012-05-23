<?php

class AutoModeler_Exception_Validation extends AutoModeler_Exception
{
	public function __construct(array $errors, $exception_text, $code = NULL)
	{
		$this->errors = $errors;
		parent::__construct($exception_text, $code);
	}

	public function as_array()
	{
		return $this->errors;
	}

	public function to_html($renderer = NULL)
	{
		if ( ! $renderer)
		{
			$renderer = new View('form_errors');
		}

		$renderer->set('errors', $this->errors);

		return $renderer->render();
	}

	public function __toString()
	{
		return $this->to_html();
	}
}
