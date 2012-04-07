<?php

/**
 * Tests automodeler functionality
 * 
 * Use this schema to use these tests:
 * 
 * CREATE TABLE `ormusers` (
 *   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *   `username` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 *   `password` char(50) COLLATE utf8_unicode_ci NOT NULL,
 *   `email` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 *   `last_login` int(11) NOT NULL,
 *   `logins` int(10) unsigned NOT NULL,
 *   `foo_id` int(11) NOT NULL,
 *   PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 * 
 * CREATE TABLE `testroles` (
 *   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *   `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 *   PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

 * CREATE TABLE `foos` (
 *   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *   `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
 *   `ormuser_id` int(10) unsigned NOT NULL,
 *   PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 * 
 * CREATE TABLE `ormusers_testroles` (
 *   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *   `ormuser_id` int(10) unsigned NOT NULL,
 *   `testrole_id` int(10) unsigned NOT NULL,
 *   PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
		$config = Kohana::$config->load('database')->default;

		$pdo = new PDO('mysql:host='.$config['connection']['hostname'].';dbname='.$config['connection']['database'], $config['connection']['username'], $config['connection']['password']);
		return $this->createDefaultDBConnection($pdo, $config['connection']['database']);
	}

	protected function getDataSet()
	{
		return $this->createFlatXMLDataSet(Kohana::find_file('tests', 'test_data/testuser_relationships', 'xml'));
	}

	/**
	 * Get the currently logged set of queries from the database profiling.
	 *
	 * @param string $database The database the queries will be logged under.
	 * @return array Map of queries from the Profiler class
	 * @author Marcus Cobden
	 */
	public function getQueries($database = 'default')
	{
		$database = "database ($database)";
		
		$groups = Profiler::groups();
		if (! array_key_exists($database, $groups))
			return array();

		return $groups[$database];
	}
	
	/**
	 * Find the difference between two different query profiles
	 *
	 * @param array $before The queries before
	 * @param array $after  The queries after
	 * @return array(int, array) Total number of new queries and a map of query => increase.
	 * @author Marcus Cobden
	 */
	public function queryDiff(array $before, array $after)
	{
		$added = 0;
		$diff = array();

		foreach ($after as $query => $ids) {
			if (! array_key_exists($query, $before))
			{
				$cmp = count($ids);
				$added += $cmp;
				$diff[$query] = $cmp;
			}
			else
			{
				$cmp = count($ids) - count($before[$query]);
				if ($cmp == 0)
					continue;
					
				$added += $cmp;
				$diff[$query] = $cmp;
			}
		}
			
		return array($added, $diff);
	}
	
	/**
	 * Assert that the number of queries should have increased by a certain amount.
	 *
	 * @param int   $increase Expected increase in number of queries
	 * @param array $before   Queries before the tests
	 * @param array $after    Queries after the tests
	 * @return void
	 * @author Marcus Cobden
	 */
	public function assertQueryCountIncrease($increase, array $before, array $after)
	{
		list($added, $new_queries) = $this->queryDiff($before, $after);
		
		$this->assertEquals($increase, $added, "Expected to have $increase more queries, actual increase was $added.");
	}

	public function provider_find_parent()
	{
		return array(
			array('Model_TestRole', 1, 'ormusers', 2),
			array('Model_TestRole', 2, 'ormusers', 1),
		);
	}

	/**
	 * Tests __get() with a relationship
	 *
	 * @return null
	 */
	public function test_get()
	{
		$user = new Model_ORMUser(1);
		$this->assertInstanceOf('Model_Foo', $user->foo);
		$this->assertSame(AutoModeler::STATE_LOADED, $user->foo->state());
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
			array('Model_TestRole', 1, 'ormusers', NULL, 2),
			array('Model_TestRole', 1, 'ormusers', db::select()->where('ormusers.username', '=', 'foobar'), 1),
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

	public function provider_find_related()
	{
		return array(
			array('Model_ORMUser', 1, 'testroles', 2),
			array('Model_ORMUser', 2, 'testroles', 1),
			array('Model_Foo', 1, 'ormusers', 3),
			array('Model_ORMUser', 1, 'foos', 1),
		);
	}

	/**
	 * Tests that finding relationships that don't exist throws an exception
	 * 
	 * @dataProvider provider_find_related
	 * @expectedException AutoModeler_Exception
	 *
	 * @covers AutoModeler_ORM::find_parent
	 */
	public function test_find_parent_wrong($model_name, $model_id, $related_model, $expected_count)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected_count, count($model->find_parent($related_model)));
	}

	/**
	 * @dataProvider provider_find_related
	 *
	 * @covers AutoModeler_ORM::find_related
	 */
	public function test_find_related($model_name, $model_id, $related_model, $expected_count)
	{
		$model = new $model_name($model_id);

		$related = $model->find_related($related_model);

		$this->assertSame($expected_count, count($related));
		$this->assertSame(AutoModeler::STATE_LOADED, $related->current()->state());
	}

	public function provider_find_related_where()
	{
		return array(
			array('Model_ORMUser', 1, 'testroles', NULL, 2),
			array('Model_ORMUser', 1, 'testroles', db::select()->where('testroles.name', '=', 'Admin'), 1),
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
	 * Tests that finding relationships that don't exist throws an exception
	 *
	 * @dataProvider provider_find_parent
	 * @expectedException AutoModeler_Exception
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

	/**
	 * Tests with() support
	 *
	 * @return null
	 */
	public function test_with()
	{
		$q_before = $this->getQueries();

		$user = new Model_ORMUser();
		$user->with('foo')->load(db::select()->where('ormusers.id', '=', 1));

		// There should only be one query
		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());
		$this->assertTrue($user instanceof Model_ORMUser);

		$this->assertTrue($user->foo instanceof Model_Foo);
		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());

		// Make sure the load()ed model is loaded()
		$this->assertSame(AutoModeler::STATE_LOADED, $user->foo->state());
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

	public function provider_remove_parent()
	{
		return array(
			array('Model_TestRole', 1, 'ormusers', 2),
			array('Model_TestRole', 2, 'ormusers', 1),
		);
	}

	/**
	 * @dataProvider provider_remove_parent
	 *
	 * @covers AutoModeler_ORM::remove_parent
	 */
	public function test_remove_parent($model_name, $model_id, $related_model, $expected)
	{
		$model = new $model_name($model_id);

		$this->assertSame($expected, $model->remove_parent($related_model));
		$this->assertSame(0, count($model->find_parent($related_model)));
	}

		/**
		 * Tests that assignment to the model properties works
		 *
		 * @test
		 * @return null
		 */
		public function test_assignment()
		{
			$model = new Model_ORMUser;
			$model->username = 'foobar';

			$this->assertSame($model->username, 'foobar');
		}
		
	public function provider_set_has_many()
	{
		return array(
			array('Model_ORMUser', 3, 'testroles', 2),
			array('Model_ORMUser', 2, 'testroles', 1),
		);
	}

	/**
	 * Tests creating Many to Many relationships with __set().
	 *
	 * @dataProvider provider_set_has_many
	 *
	 * @covers AutoModeler_ORM::__set
	 * @author Jonathan Davis
	 */
	public function test_set_has_many($model_name, $model_id, $related_model, $related_id)
	{
		$model = new $model_name($model_id);

		// Remove relationship if it alread exists.
		$model->remove($related_model, $related_id);

		$model->{$related_model} = $related_id;

		$this->assertTrue($model->has($related_model, $related_id));
	}

	public function provider_set_belongs_to()
	{
		return array(
			array('Model_TestRole', 1, 'ormusers', 3),
			array('Model_TestRole', 2, 'ormusers', 2),
		);
	}

	/**
	 * Tests creating Belongs To relationships with __set().
	 *
	 * @dataProvider provider_set_belongs_to
	 *
	 * @covers AutoModeler_ORM::__set
	 * @author Jonathan Davis
	 */
	public function test_set_belongs_to($model_name, $model_id, $parent_model, $parent_id)
	{
		$model = new $model_name($model_id);
		$model->remove_parent($parent_model);

		$model->{$parent_model} = $parent_id;

		$this->assertSame(1, count($model->find_parent($parent_model)));
	}
}