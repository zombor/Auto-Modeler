<?php

include_once 'classes/automodeler/dao/database.php';
include_once 'classes/automodeler/model.php';
include_once 'classes/automodeler/exception.php';

class Test_AutoModeler_Model extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that using the state() method returns the initial state of the model
	 */
	public function test_initial_state_returns_new_state()
	{
		$model = new AutoModeler_Model;

		$this->assertSame($model->state(), AutoModeler_Model::STATE_NEW);
	}

	/**
	 * Tests that assigning a state returns the new state immediately
	 */
	public function test_state_assign_returns_state()
	{
		$model = new AutoModeler_Model;

		$new_state = $model->state(AutoModeler_Model::STATE_LOADING);

		$this->assertSame($new_state, AutoModeler_Model::STATE_LOADING);
	}

	/**
	 * Tests that assigning a new state is returned later
	 */
	public function test_state_assign_returns_later()
	{
		$model = new AutoModeler_Model;

		$model->state(AutoModeler_Model::STATE_LOADING);

		$this->assertSame($model->state(), AutoModeler_Model::STATE_LOADING);
	}

	/**
	 * Tests that a bare model has no data array
	 */
	public function test_bare_model_has_no_data()
	{
		$model = new AutoModeler_Model;

		$this->assertSame($model->as_array(), array());
	}

	/**
	 * Tests that a new data definition can be injected into the model via the constructor
	 */
	public function test_new_data_can_be_injected_via_constructor()
	{
		$model = new AutoModeler_Model(
			array(
				'id',
				'foo',
				'bar',
			)
		);

		$this->assertSame(
			$model->as_array(),
			array(
				'id' => NULL,
				'foo' => NULL,
				'bar' => NULL,
			)
		);
	}

	/**
	 * Tests that we can write validations to a model and read them back.
	 */
	public function test_write_read_validation_rules()
	{
		$model = new AutoModeler_model(
			array(
				'id',
				'foo',
				'bar'
			)
		);

		$rules = array(
			'foo' => array(
				array('not_empty')
			),
			'bar' => array(
				array('numeric')
			)
		);

		$model->rules($rules);

		$this->assertSame($model->rules(), $rules);
	}

	/**
	 * Tests that we can read default data values from a model
	 */
	public function test_read_properties()
	{
		$model = new AutoModeler_Model(
			array(
				'id',
				'foo',
				'bar'
			)
		);

		$this->assertSame($model->id, NULL);
		$this->assertSame($model->foo, NULL);
		$this->assertSame($model->bar, NULL);
	}
}
