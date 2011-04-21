<?php

/**
 * Tests automodeler functionality
 *
 * Use this schema to use these tests:
 *
 * CREATE TABLE `testusers` (
 * `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
 * `username` VARCHAR( 50 ) NOT NULL ,
 * `password` CHAR( 50 ) NOT NULL ,
 * `email` VARCHAR( 50 ) NOT NULL ,
 * `last_login` INT NOT NULL ,
 * `logins` INT UNSIGNED NOT NULL
 * ) ENGINE = MYISAM ;
 *
 * @group automodeler
 *
 * @package   AutoModeler
 * @author    Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright 2010 Jeremy Bush
 * @license   http://www.opensource.org/licenses/isc-license.txt
 */

require_once 'PHPUnit/Extensions/Database/TestCase.php';

class AutoModeler_Test extends PHPUnit_Extensions_Database_TestCase
{
	protected function getconnection()
	{
		$config = Kohana::config('database')->default;

		$pdo = new PDO('mysql:host='.$config['connection']['hostname'].';dbname='.$config['connection']['database'], $config['connection']['username'], $config['connection']['password']);
		return $this->createDefaultDBConnection($pdo, $config['connection']['database']);
	}

	protected function getdataset()
	{
		return $this->createFlatXMLDataSet(Kohana::find_file('tests', 'test_data/testusers', 'xml'));
	}

	/**
	 * Provides test data for test_create_save()
	 *
	 * @return array
	 */
	public function provider_create_save()
	{
		return array(
			// $username, $password, $email, $last_login, $logins
			array('unit_test', 'unit_test', 'unit@test.com', time(), '0'),
		);
	}

	/**
	 * Tests creating and saving an object
	 *
	 * @test
	 * @dataProvider provider_create_save
	 * @covers AutoModeler::__construct
	 * @covers AutoModeler::__set
	 * @covers AutoModeler::save
	 * @covers AutoModeler::is_valid
	 * @covers AutoModeler::as_array
	 * @covers AutoModeler::set_fields
	 * @covers AutoModeler::state
	 * @covers AutoModeler::load
	 * @param string $str       String to parse
	 * @param array  $expected  Callback and its parameters
	 */
	public function test_create_save($username, $password, $email, $last_login, $logins)
	{
		$user = new Model_TestUser;
		$user->username = $username;
		$user->password = $password;
		$user->email = $email;
		$user->last_login = $last_login;
		$user->logins = $logins;
		$this->assertTrue($user->state() == AutoModeler::STATE_NEW);
		$user->save();

		$this->assertSame(
			array(
				'id'         => $user->id,
				'username'   => $username,
				'password'   => sha1($password),
				'email'      => $email,
				'last_login' => $last_login,
				'logins'     => $logins,
			),
			$user->as_array()
		);

		$this->assertFalse(empty($user->id));
		$this->assertTrue($user->state() == AutoModeler::STATE_LOADED);

		$user = new Model_TestUser;
		$user->set_fields(
			array(
				'username'   => $username,
				'password'   => $password,
				'email'      => $email,
				'last_login' => $last_login,
				'logins'     => $logins,
			)
		);
		$user->save();
		$this->assertFalse(empty($user->id));
	}

	/**
	 * Provides test data for test_create_save()
	 *
	 * @return array
	 */
	public function provider_read_update_delete()
	{
		return array(
			// $id, $username, $new_username
			array(1, 'foobar', 'foobarbaz'),
		);
	}

	/**
	 * Tests reading and updating objects
	 *
	 * @test
	 * @dataProvider provider_read_update_delete
	 * @covers AutoModeler::save
	 * @covers AutoModeler::delete
	 * @covers AutoModeler::__construct
	 * @covers AutoModeler::__get
	 *
	 * @param string $str       String to parse
	 * @param array  $expected  Callback and its parameters
	 */
	public function test_read_update_delete($id, $username, $new_username)
	{
		$user = new Model_TestUser($id);
		$this->assertSame($username, $user->username);

		$user->username = $new_username;
		$user->save();

		$new_user = new Model_TestUser($id);
		$username = $new_user->username;
		$this->assertSame($new_username, $new_user->username);

		$deletions = $user->delete();
		$this->assertSame(1, $deletions);
		$this->assertTrue($user->state() == AutoModeler::STATE_DELETED);
	}

	/**
	 * Tests that load() does not use __set()
	 *
	 * @return null
	 */
	public function test_load_does_not_use_set()
	{
		$user = new Model_TestUser;
		$user->load(db::select_array($user->fields())->where('id', '=', '1'));

		$this->assertSame('60518c1c11dc0452be71a7118a43ab68e3451b82', $user->password);
	}

	/**
	 * Tests that load() results in a model with state = AutoModeler::STATE_LOADED
	 *
	 * @return null
	 */
	public function test_load_results_in_loaded_models()
	{
		$user = new Model_TestUser;
		$users = $user->load(db::select_array($user->fields())->where('id', '=', '1'), NULL);

		foreach ($users as $user)
		{
			$this->assertSame(AutoModeler::STATE_LOADED, $user->state());
		}
	}

	/**
	 * Tests that passing a limit higher than 1 (or null) returns a result set
	 *
	 * @return null
	 */
	public function test_load_no_limit()
	{
		$result = Model::factory('testuser')->load(NULL, NULL);
		$this->assertTrue($result instanceof Database_Result);
		$this->assertTrue(count($result) == 3);

		$result = Model::factory('testuser')->load(NULL, 2);
		$this->assertTrue($result instanceof Database_Result);
		$this->assertTrue(count($result) == 2);

		$this->assertSame(AutoModeler::STATE_LOADED, $result->current()->state());
	}

	/**
	 * Tests deleting a non-saved object
	 *
	 * @test
	 * @covers AutoModeler::delete
	 */
	public function test_delete_non_saved()
	{
		$user = new Model_TestUser;

		try
		{
			$user->delete();
		}
		catch (AutoModeler_Exception $e)
		{
			$this->assertSame('Cannot delete a non-saved model Model_TestUser!', $e->getMessage());
		}
	}

	/**
	 * Tests reading and setting an invalid model property
	 *
	 * @test
	 * @covers AutoModeler::__construct
	 * @covers AutoModeler::__set
	 * @covers AutoModeler::__get
	 * @covers AutoModeler::__isset
	 * @covers AutoModeler_Exception::__toString
	 */
	public function test_invalid_property()
	{
		$user = new Model_TestUser(1);

		$this->assertFalse(isset($user->foo));

		try
		{
			$foo = $user->foo;
		}
		catch (AutoModeler_Exception $e)
		{
			$this->assertSame('Field foo does not exist in Model_TestUser!', $e->getMessage());
		}

		try
		{
			$user->foo = 'bar';
		}
		catch (AutoModeler_Exception $e)
		{
			$this->assertSame('Field foo does not exist in Model_TestUser!', $e->getMessage());
			$this->assertSame('Field foo does not exist in Model_TestUser!', $e->__toString());
		}
	}

	/**
	 * Tests when a model save fails because of validation
	 *
	 * @test
	 * @covers AutoModeler::save
	 * @covers AutoModeler::is_valid
	 * @covers AutoModeler::errors
	 * @covers AutoModeler_Exception::__construct
	 */
	public function test_validation_fail()
	{
		$user = new Model_TestUser;

		$user->password = 'unit_test';

		try
		{
			$user->save();
		}
		catch (AutoModeler_Exception $e)
		{
			$this->assertSame('username must not be empty', $e->errors['username']);

			$errors = $user->errors();
			$this->assertSame(array('not_empty', array('')), $errors['username']);
		}
	}

	/**
	 * Tests the factory method
	 *
	 * @test
	 * @covers AutoModeler::factory
	 */
	public function test_factory()
	{
		$user = AutoModeler::factory('testuser');

		$this->assertTrue($user instanceof Model_TestUser);
	}

	/**
	 * Tests serialization of an object
	 *
	 * @test
	 * @covers AutoModeler::__sleep
	 * @covers AutoModeler::__wakeup
	 */
	public function test_serialize()
	{
		$protected = "\x0*\x0";
		$user = new Model_TestUser(1);

		$serialized = serialize($user);
		// Test the fields we expect back
		$this->assertSame(
			'O:14:"Model_TestUser":9:{s:14:"'.$protected.'_table_name";s:9:"testusers";s:8:"'.$protected.'_data";a:6:{s:2:"id";s:1:"1";s:8:"username";s:6:"foobar";s:8:"password";s:40:"60518c1c11dc0452be71a7118a43ab68e3451b82";s:5:"email";s:11:"foo@bar.com";s:10:"last_login";s:5:"12345";s:6:"logins";s:2:"10";}s:9:"'.$protected.'_rules";a:2:{s:8:"username";a:1:{i:0;a:1:{i:0;s:9:"not_empty";}}s:5:"email";a:1:{i:0;a:1:{i:0;s:5:"email";}}}s:13:"'.$protected.'_callbacks";a:0:{}s:14:"'.$protected.'_validation";a:0:{}s:13:"'.$protected.'_validated";b:0;s:8:"'.$protected.'_lang";s:11:"form_errors";s:9:"'.$protected.'_state";s:6:"loaded";s:10:"'.$protected.'_states";a:4:{i:0;s:3:"new";i:1;s:7:"loading";i:2;s:6:"loaded";i:3;s:7:"deleted";}}',
			$serialized
		);

		$unserialized = unserialize($serialized);
		$this->assertSame('foobar', $unserialized->username);
	}

	/**
	 * Tests array access of this object
	 *
	 * @test
	 * @covers AutoModeler::offsetExists
	 * @covers AutoModeler::offsetSet
	 * @covers AutoModeler::offsetGet
	 * @covers AutoModeler::offsetUnset
	 */
	public function test_array_access()
	{
		$user = new Model_TestUser(1);

		$this->assertSame('foobar', $user['username']);
		$this->assertTrue(isset($user['username']));
		$this->assertFalse(isset($user['foobar']));

		$user['username'] = 'unit_test';
		$this->assertSame('unit_test', $user['username']);

		unset($user['username']);
		$this->assertSame(NULL, $user['username']);
	}

	/**
	 * Provides test data for test_select_list()
	 *
	 * @return array
	 */
	public function provider_select_list()
	{
		return array(
			array('id', 'username', db::select(), array('1' => 'foobar', '2' => 'foobar', '3' => 'foobar')),
			array('id', array('username', 'last_login'), db::select()->where('username', '=', 'foobar'), array('1' => 'foobar - 12345', '2' => 'foobar - 12345', '3' => 'foobar - 12345')),
		);
	}

	/**
	 * Tests generating an array output for an html select list
	 *
	 * @test
	 * @dataProvider provider_select_list
	 * @covers AutoModeler::select_list
	 */
	public function test_select_list($key, $display, $query, $expected)
	{
		$this->assertSame($expected, AutoModeler::factory('testuser')->select_list($key, $display, $query));
	}

	/**
	 * Tests that assignment to the model properties works
	 *
	 * @test
	 * @return null
	 */
	public function test_assignment()
	{
		$model = new Model_TestUser;
		$model->username = 'foobar';

		$this->assertSame($model->username, 'foobar');
	}
}
