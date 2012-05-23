<?php

include_once 'classes/automodeler/exception/validation.php';

class DescribeAutoModelerExceptionValidation extends \PHPSpec\Context
{
	public function itAcceptsAnArrayOfErrorsAndReturnsThem()
	{
		$errors = array('foo' => 'bar');
		$exception = new AutoModeler_Exception_Validation(
			$errors,
			'phpspec'
		);

		$this->spec($exception->as_array())->should->equal($errors);
	}

	public function itRendersHtml()
	{
		$errors = array('foo' => 'bar');
		$exception = new AutoModeler_Exception_Validation(
			$errors,
			'phpspec'
		);

		$view = Mockery::mock('View');
		$view->shouldReceive('set');
		$view->shouldReceive('render')->andReturn('foobar');
		$this->spec($exception->to_html($view))->should->equal('foobar');
	}
}
