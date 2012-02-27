<?php

include 'classes/automodeler/dao/database.php';
include 'classes/automodeler/model.php';

class Test_AutoModeler_DAO_Database extends PHPUnit_Framework_TestCase
{
	public function test_create_new()
	{
		// Create database mock
		$database = $this->getMock('Database');
		$database->expects($this->any())
			->method('save')
			->will($this->returnValue('foo'));

		// Create model mock
		$model = $this->getMock('AutoModeler_Model');
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
		$model->expects($this->any())
			->method('state')
			->will($this->returnValue(AutoModeler_Model::STATE_NEW));

		// Save the model
		$dao = new AutoModeler_DAO_Database($database);
		$new_model = $dao->save($model);

		$this->assertSame(AutoModeler_Model::STATE_LOADED, $new_model->state());
		$this->assertFalse($new_model->id == NULL);
	}
}
