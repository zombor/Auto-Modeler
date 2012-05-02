<?php

include_once 'classes/automodeler/model.php';
include_once 'classes/automodeler/exception.php';

class DescribeAutoModeler extends \PHPSpec\Context
{
	public function itShouldHaveAnEmptyDataArrayWhenNew()
	{
		$model = new AutoModeler_Model(
			array(
				'id',
				'foo',
			)
		);

		$this->spec($model->as_array())->shouldNot->beEmpty();
	}

	public function itShouldStoreDataAssignedToItDirectly()
	{
		$model = new AutoModeler_Model(
			array(
				'id',
				'foo',
			)
		);
		$model->id = 5;
		$model->foo = 'bar';

		$this->spec($model->id)->should->be(5);
		$this->spec($model->foo)->should->be('bar');
	}

	public function itShouldStoreDataAssignedToItInMass()
	{
		$model = new AutoModeler_Model(array('id', 'foo'));
		$model->data($array = array('id' => 5, 'foo' => 'bar'));

		$this->spec($model->as_array())->should->be($array);
	}

	public function itShouldChangeState()
	{
		$model = new AutoModeler_Model;
		$model->state($state = AutoModeler_Model::STATE_LOADED);

		$this->spec($model->state())->should->be($state);
	}

	public function itShouldHaveNewStateWhenNew()
	{
		$model = new AutoModeler_Model;

		$this->spec($model->state())->should->be(AutoModeler_Model::STATE_NEW);
	}

	public function itShouldHaveAssignedStateWhenAssigned()
	{
		$model = new AutoModeler_Model;
		$model->state(AutoModeler_Model::STATE_LOADED);

		$this->spec($model->state())->should->be(AutoModeler_Model::STATE_LOADED);
	}

	public function itShouldHaveNoRulesWhenNew()
	{
		$model = new AutoModeler_Model;

		$this->spec($model->rules())->should->beEmpty();
	}

	public function itShouldHaveAssignedRules()
	{
		$model = new AutoModeler_Model(array('id'));
		$model->rules($rules = array('id' => array(array('not_empty'))));

		$this->spec($model->rules())->should->be($rules);
	}

	public function itShouldBeValidWithNoRules()
	{
	//	$this->pending('Mockery doesn\'t seem to support named mocks.');
		$model = new AutoModeler_Model;

		$validation = Mockery::mock('Validation');
		$validation->shouldReceive('bind');
		$validation->shouldReceive('rules')->never();
		$validation->shouldReceive('check')->once()
			->andReturn(TRUE);

		$this->spec($model->valid(NULL, $validation))->should->beTrue();
	}

	public function itShouldFailValidationWhenInvalid()
	{
		$model = new AutoModeler_Model;

		$validation = Mockery::mock('Validation');
		$validation->shouldReceive('bind');
		$validation->shouldReceive('check')
			->once()
			->andReturn(FALSE);
		$validation->shouldReceive('errors')
			->once()
			->andReturn($errors = array('id' => 'required'));

		$this->spec($model->valid(NULL, $validation))
			->should
			->be(
				array('status' => FALSE, 'errors' => $errors)
			);
	}
}
