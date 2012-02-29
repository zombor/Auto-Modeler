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
}
