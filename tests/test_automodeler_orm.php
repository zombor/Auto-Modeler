<?php

/**
 * Tests automodeler functionality
 * 
 * Use this schema to use these tests:
 * 
 * CREATE TABLE `ormusers` (
 * `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 * `username` VARCHAR( 50 ) NOT NULL ,
 * `password` CHAR( 50 ) NOT NULL ,
 * `email` VARCHAR( 50 ) NOT NULL ,
 * `last_login` INT NOT NULL ,
 * `logins` INT UNSIGNED NOT NULL
 * ) ENGINE = INNODB ;
 * 
 * CREATE TABLE `testroles` (
 * `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 * `name` VARCHAR( 50 ) NOT NULL
 * ) ENGINE = INNODB;
 * 
 * CREATE TABLE `ormusers_testroles` (
 * `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
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

	/**
	 * Provides test data for test_get_manytomany()
	 *
	 * @return array
	 */
	public function provider_get_manytomany()
	{
		return array(
			// $model_name, $model_id, $related_model, $expected_count, $related_id, $expected_has
			array('Model_ORMUser', 1, 'testroles', 2, 1, TRUE),
			array('Model_ORMUser', 2, 'testroles', 1, 3, FALSE),
		);
	}

	/**
	 * Tests obtaining a many to many relationship
	 *
	 * @test
	 * @dataProvider provider_get_manytomany
	 * @covers AutoModeler_ORM::find_related
	 * @covers AutoModeler_ORM::has
	 * @param string $str       String to parse
	 * @param array  $expected  Callback and its parameters
	 */
	public function test_get_manytomany($model_name, $model_id, $related_model, $expected_count, $related_id, $expected_has)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected_count, count($model->find_related($related_model)));
		$this->assertSame($expected_has, $model->has($related_model, $related_id));
	}
}