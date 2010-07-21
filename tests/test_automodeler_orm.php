<?php

/**
 * Tests automodeler functionality
 * 
 * Use this schema to use these tests:
 * 
 * CREATE TABLE `ormusers` (
 * `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
 * `username` VARCHAR( 50 ) NOT NULL ,
 * `password` CHAR( 50 ) NOT NULL ,
 * `email` VARCHAR( 50 ) NOT NULL ,
 * `last_login` INT NOT NULL ,
 * `logins` INT UNSIGNED NOT NULL
 * ) ENGINE = INNODB ;
 * 
 * CREATE TABLE `testroles` (
 * `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
 * `name` VARCHAR( 50 ) NOT NULL
 * ) ENGINE = INNODB;
 * 
 * CREATE TABLE `ormusers_testroles` (
 * `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
 * `ormuser_id` INT UNSIGNED NOT NULL REFERENCES `ormusers` (`id`) ON DELETE CASCADE,
 * `testrole_id` INT UNSIGNED NOT NULL REFERENCES `testroles` (`id`) ON DELETE CASCADE
 * ) ENGINE = INNODB;
 * 
 *
 * @group automodeler_orm
 *
 * @package   AutoModeler
 * @author    Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright 2010 Jeremy Bush
 * @license   http://www.opensource.org/licenses/isc-license.txt
 */

require_once 'PHPUnit/Extensions/Database/TestCase.php';

class AutoModeler_ORM_Test extends PHPUnit_Extensions_Database_TestCase
{
	protected function getconnection()
	{
		$config = Kohana::config('database')->default;

		$pdo = new PDO('mysql:host='.$config['connection']['hostname'].';dbname='.$config['connection']['database'], $config['connection']['username'], $config['connection']['password']);
		return $this->createDefaultDBConnection($pdo, $config['connection']['database']);
	}

	protected function getDataSet()
	{
		return $this->createFlatXMLDataSet(Kohana::find_file('tests', 'test_data/testuser_relationships', 'xml'));
	}

	public function provider_find_parent()
	{
		return array(
			array('Model_TestRole', 1, 'ormusers', 2),
			array('Model_TestRole', 2, 'ormusers', 1),
		);
	}

	/**
	 * @dataProvider provider_find_parent
	 *
	 * @covers AutoModeler_ORM::find_parent
	 */
	public function test_find_parent($model_name, $model_id, $related_model, $expected_count)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected_count, count($model->find_parent($related_model)));
	}

	public function provider_find_parent_where()
	{
		return array(
			array('Model_TestRole', 1, 'ormusers', array(), 2),
			array('Model_TestRole', 1, 'ormusers', array(array('ormusers.username', '=', 'foobar')), 1),
		);
	}

	/**
	 * @dataProvider provider_find_parent_where
	 *
	 * @covers AutoModeler_ORM::find_parent
	 */
	public function test_find_parent_where($model_name, $model_id, $related_model, $where, $expected_count)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected_count, count($model->find_parent($related_model, $where)));
	}

	/**
	 * @dataProvider provider_find_related
	 * @expectedException Database_Exception
	 *
	 * @covers AutoModeler_ORM::find_parent
	 */
	public function test_find_parent_wrong($model_name, $model_id, $related_model, $expected_count)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected_count, count($model->find_parent($related_model)));
	}

	public function provider_find_related()
	{
		return array(
			array('Model_ORMUser', 1, 'testroles', 2),
			array('Model_ORMUser', 2, 'testroles', 1),
		);
	}

	/**
	 * @dataProvider provider_find_related
	 *
	 * @covers AutoModeler_ORM::find_related
	 */
	public function test_find_related($model_name, $model_id, $related_model, $expected_count)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected_count, count($model->find_related($related_model)));
	}

	public function provider_find_related_where()
	{
		return array(
			array('Model_ORMUser', 1, 'testroles', array(), 2),
			array('Model_ORMUser', 1, 'testroles', array(array('testroles.name', '=', 'Admin')), 1),
		);
	}

	/**
	 * @dataProvider provider_find_related_where
	 *
	 * @covers AutoModeler_ORM::find_related
	 */
	public function test_find_related_where($model_name, $model_id, $related_model, $where, $expected_count)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected_count, count($model->find_related($related_model, $where)));
	}

	/**
	 * @dataProvider provider_find_parent
	 * @expectedException Database_Exception
	 *
	 * @covers AutoModeler_ORM::find_related
	 */
	public function test_find_related_wrong($model_name, $model_id, $related_model, $expected_count)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected_count, count($model->find_related($related_model)));
	}

	public function provider_has()
	{
		return array(
			array('Model_ORMUser', 1, 'testroles', 1, TRUE),
			array('Model_ORMUser', 1, 'testroles', 2, TRUE),
			array('Model_ORMUser', 2, 'testroles', 1, TRUE),
			array('Model_ORMUser', 2, 'testroles', 2, FALSE),
			array('Model_ORMUser', 3, 'testroles', 1, FALSE),
			array('Model_ORMUser', 3, 'testroles', 2, FALSE),

			array('Model_TestRole', 1, 'ormusers', 1, FALSE),
			array('Model_TestRole', 2, 'ormusers', 1, FALSE),
		);
	}

	/**
	 * @dataProvider provider_has
	 *
	 * @covers AutoModeler_ORM::has
	 */
	public function test_has($model_name, $model_id, $related_model, $related_id, $expected)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected, $model->has($related_model, $related_id));
	}

	public function provider_remove()
	{
		return array(
			array('Model_ORMUser', 1, 'testrole', 1, 1),
			array('Model_ORMUser', 1, 'testrole', 2, 1),
			array('Model_ORMUser', 2, 'testrole', 1, 1),
			array('Model_ORMUser', 2, 'testrole', 2, 0),
			array('Model_ORMUser', 3, 'testrole', 1, 0),
			array('Model_ORMUser', 3, 'testrole', 2, 0),
		);
	}

	/**
	 * @dataProvider provider_remove
	 *
	 * @covers AutoModeler_ORM::remove
	 */
	public function test_remove($model_name, $model_id, $related_model, $related_id, $expected)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected, $model->remove($related_model, $related_id));
		$this->assertFalse($model->has($related_model, $related_id));
	}

	public function provider_remove_all()
	{
		return array(
			array('Model_ORMUser', 1, 'testroles', 2),
			array('Model_ORMUser', 2, 'testroles', 1),

			array('Model_TestRole', 1, 'ormusers', 2),
			array('Model_TestRole', 2, 'ormusers', 1),

			// Invalid relationships
			array('Model_ORMUser', 1, 'ormusers', NULL),
			array('Model_TestRole', 1, 'testroles', NULL),
		);
	}

	/**
	 * @dataProvider provider_remove_all
	 *
	 * @covers AutoModeler_ORM::remove_all
	 */
	public function test_remove_all($model_name, $model_id, $related_model, $expected)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected, $model->remove_all($related_model));
	}
}
