<?php

include 'classes/automodeler/dao/database.php';
include 'classes/automodeler/model.php';
include 'classes/automodeler/exception.php';

class Test_AutoModeler_DAO_Database extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests we can create a new, clean record with a new model instance
	 */
	public function test_create_new()
	{
		// Create database mock
		$database = $this->getMock('Database');
		$database->expects($this->any())
			->method('execute')
			->will($this->returnValue('foo'));

		// Create a qb mock
		$qb = $this->getMock(
			'Database_Query_Builder_Insert',
			array('execute', 'values')
		);
		$qb->expects($this->any())
			->method('execute')
			->will($this->returnValue('foo'));
		$qb->expects($this->any())
			->method('values')
			->will($this->returnValue(NULL));

		// Create model mock
		$model = $this->getMock(
			'AutoModeler_Model',
			array('as_array', 'state', 'pk_value')
		);
		$model->expects($this->any())
			->method('as_array')
			->will(
				$this->returnValue(
					array(
						'id' => NULL,
						'foo' => 'bar',
					)
			)
		);
		$model->expects($this->at(0))
			->method('state')
			->will(
				$this->returnValue(AutoModeler_Model::STATE_NEW)
			);
		$model->expects($this->at(1))
			->method('state')
			->will(
				$this->returnValue(AutoModeler_Model::STATE_LOADED)
			);
		$model->expects($this->at(3))
			->method('state')
			->will(
				$this->returnValue(AutoModeler_Model::STATE_LOADED)
			);
		$model->expects($this->any())
			->method('pk_value')
			->will(
				$this->returnValue(NULL)
			);

		// Save the model
		$dao = new AutoModeler_DAO_Database($database);
		$new_model = $dao->create($model, $qb);

		$this->assertSame(AutoModeler_Model::STATE_LOADED, $new_model->state());
		$this->assertFalse($new_model->id == NULL);
	}

	/**
	 * Tests that already loaded methods throw exceptions when being created
	 */
	public function test_create_new_with_loaded_model()
	{
		$database = $this->getMock('Database');

		// Create model mock
		$model = $this->getMock(
			'AutoModeler_Model',
			array('state')
		);

		$model->expects($this->at(0))
			->method('state')
			->will(
				$this->returnValue(AutoModeler_Model::STATE_LOADED)
			);

		try
		{
			// Try and save the model
			$dao = new AutoModeler_DAO_Database($database);
			$new_model = $dao->create($model);

			$this->fail('Create cannot be called on saved models!');
		}
		catch (AutoModeler_Exception $e)
		{

		}
	}

	/**
	 * Tests we can persist an existing record
	 */
	public function test_persist_existing()
	{
		// Create database mock
		$database = $this->getMock('Database');

		// Create a qb mock
		$qb = $this->getMock(
			'Database_Query_Builder_Update',
			array('execute', 'set', 'where')
		);
		$qb->expects($this->once())
			->method('execute')
			->with($this->equalTo($database))
			->will($this->returnValue(1));
		$qb->expects($this->once())
			->method('set')
			->with(
				$this->equalTo(
					array('foo' => 'bar')
				)
			);
		$qb->expects($this->once())
			->method('where')
			->with(
				$this->equalTo('id'),
				$this->equalTo('='),
				$this->equalTo('1')
			);

		// Create model mock
		$model = $this->getMock(
			'AutoModeler_Model',
			array('as_array', 'state', 'pk_value')
		);
		$model->expects($this->any())
			->method('as_array')
			->will(
				$this->returnValue(
					array(
						'id' => 1,
						'foo' => 'bar',
					)
			)
		);
		$model->expects($this->any())
			->method('state')
			->will(
				$this->returnValue(AutoModeler_Model::STATE_LOADED)
			);
		$old_id = $model->id = 1;

		// Save the model
		$dao = new AutoModeler_DAO_Database($database);
		$count = $dao->update($model, $qb);

		// We should still have one updated row
		$this->assertSame(1, $count);
	}

	/**
	 * Asserts that we can't update a non-loaded model
	 */
	public function test_update_nonloaded_model()
	{
		$database = $this->getMock('Database');

		// Create model mock
		$model = $this->getMock(
			'AutoModeler_Model',
			array('state')
		);

		$model->expects($this->at(0))
			->method('state')
			->will(
				$this->returnValue(AutoModeler_Model::STATE_NEW)
			);

		try
		{
			// Try and save the model
			$dao = new AutoModeler_DAO_Database($database);
			$new_model = $dao->update($model);

			$this->fail('Create cannot be called on saved models!');
		}
		catch (AutoModeler_Exception $e)
		{

		}
	}
}
