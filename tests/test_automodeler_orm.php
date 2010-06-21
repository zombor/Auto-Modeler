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
 * `logins` INT UNSIGNED NOT NULL,
 * `testrole_id` INT UNSIGNED NOT NULL
 * ) ENGINE = INNODB ;
 * 
 * CREATE TABLE  `automodeler`.`testroles` (
 * `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 * `name` VARCHAR( 50 ) NOT NULL
 * ) ENGINE = INNODB;
 * 
 * CREATE TABLE  `automodeler`.`ormusers_testroles` (
 * `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 * `ormuser_id` INT UNSIGNED NOT NULL ,
 * `testrole_id` INT UNSIGNED NOT NULL
 * ) ENGINE = INNODB;
 * ALTER TABLE  `automodeler`.`testusers_testroles` ADD INDEX (  `ormuser_id` );
 * ALTER TABLE  `automodeler`.`testusers_testroles` ADD INDEX (  `testrole_id` );
 * ALTER TABLE  `testusers_testroles` ADD FOREIGN KEY (  `ormuser_id` ) REFERENCES  `automodeler`.`ormusers` (`id`) ON DELETE CASCADE ;
 * ALTER TABLE  `testusers_testroles` ADD FOREIGN KEY (  `testrole_id` ) REFERENCES  `automodeler`.`testroles` (`id`) ON DELETE CASCADE ;
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
	 * Provides test data for test_create_save()
	 *
	 * @return array
	 */
	public function provider_onetomany()
	{
		return array(
			// $user_id, $expected_username, $expected_role_id, $expected_role_name, $expected_role_class
			array(1, 'foobar', '1', 'Login', 'Model_TestRole'),
		);
	}

	/**
	 * Tests getting a one to many relationship
	 *
	 * @test
	 * @dataProvider provider_onetomany
	 * @covers AutoModeler_ORM::__get
	 * @param string $str       String to parse
	 * @param array  $expected  Callback and its parameters
	 */
	public function test_onetomany($user_id, $expected_username, $expected_role_id, $expected_role_name, $expected_role_class)
	{
		$user = new Model_ORMUser($user_id);

		$this->assertSame($expected_username, $user->username);
		$this->assertSame($expected_role_id, $user->testrole->id);
		$this->assertSame($expected_role_name, $user->testrole->name);
		$this->assertTrue($user->testrole instanceof $expected_role_class);
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
			array('Model_TestRole', 2, 'ormusers', 1, 1, FALSE),
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