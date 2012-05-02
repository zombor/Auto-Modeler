<?php

include_once 'classes/automodeler/model.php';
include_once 'classes/automodeler/exception.php';
include_once 'classes/automodeler/dao/database.php';

class DescribeAutoModelerDAO extends \PHPSpec\Context
{
	public function itShouldCreateALoadedModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Insert');
		$qb->shouldReceive('values')
			->with(Mockery::type('array'))
			->once();
		$qb->shouldReceive('execute')
			->once();

		$model = new AutoModeler_Model(array('id', 'foo'));

		$dao = AutoModeler_DAO_Database::factory($database, 'foos');

		$new_model = $dao->create($model, $qb);
		$this->spec($new_model->state())->should->be(AutoModeler_Model::STATE_LOADED);
	}

	public function itShouldCreateAModelWithAnId()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Insert');
		$qb->shouldReceive('values')
			->with(Mockery::type('array'))
			->once();
		$qb->shouldReceive('execute')
			->once()
			->andReturn(1);

		$model = new AutoModeler_Model(array('id', 'foo'));

		$dao = AutoModeler_DAO_Database::factory($database, 'foos');

		$new_model = $dao->create($model, $qb);
		$this->spec($new_model->id)->should->be(1);
	}

	public function itShouldThrowExceptionWhenCreatingLoadedModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Insert');

		$model = new AutoModeler_Model;
		$model->state(AutoModeler_Model::STATE_LOADED);
		$dao = AutoModeler_DAO_Database::factory($database, 'foos');

		$this->spec(
			function() use ($model, $dao, $qb)
			{
				$dao->create($model, $qb);
			}
		)->should->throwException('AutoModeler_Exception');
	}

	public function itShouldUpdateOneRow()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Update');
		$qb->shouldReceive('set')
			->with(Mockery::type('array'))
			->once();
		$qb->shouldReceive('where')
			->with('id', '=', 1)
			->once();
		$qb->shouldReceive('execute')
			->once()
			->andReturn(1);

		$model = new AutoModeler_Model(array('id', 'foo'));
		$model->id = 1;
		$model->state(AutoModeler_Model::STATE_LOADED);

		$dao = AutoModeler_DAO_Database::factory($database, 'foos');

		$count = $dao->update($model, $qb);

		$this->spec($count)->should->be(1);
	}

	public function itShouldThrowExceptionWhenUpdatingNonLoadedModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Update');

		$model = new AutoModeler_Model;
		$dao = AutoModeler_DAO_Database::factory($database, 'foos');

		$this->spec(
			function() use ($model, $dao, $qb)
			{
				$dao->update($model, $qb);
			}
		)->should->throwException('AutoModeler_Exception');
	}

	public function itShouldReturnNewModelWhenDeleted()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Delete');
		$qb->shouldReceive('where')
			->with('id', '=', 1)
			->once();
		$qb->shouldReceive('execute')
			->once()
			->andReturn(1);
		
		$model = new AutoModeler_Model(array('id', 'foo'));
		$model->id = 1;
		$model->state(AutoModeler_Model::STATE_LOADED);

		$dao = AutoModeler_DAO_Database::factory($database, 'foos');
		$new_model = $dao->delete($model, $qb);

		$this->spec($new_model)->should->beAnInstanceOf('AutoModeler_Model');
		$this->spec($new_model->state())->should->be(AutoModeler_Model::STATE_NEW);
	}

	public function itShouldThrowExceptionWhenDeletingNonLoadedModel()
	{
		$database = Mockery::mock('Database');
		$qb = Mockery::mock('Database_Query_Builder_Delete');

		$model = new AutoModeler_Model;
		$dao = AutoModeler_DAO_Database::factory($database, 'foos');

		$this->spec(
			function() use ($model, $dao, $qb)
			{
				$dao->delete($model, $qb);
			}
		)->should->throwException('AutoModeler_Exception');
	}
}
