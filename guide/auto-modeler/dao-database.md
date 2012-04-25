# DAO Database Class

## Creating a DAO Database class

There are two ways to use the DAO class:

### Factory Pattern

You can use the factory pattern if you have a basic need for only Create, Update and Delete functionality. This has already been written for you.

	$dao = AutoModeler_DAO_Database::factory(Database::instance(), $table_name);

You must pass in a database instance as the first parameter and the table name you wish to use as the second parameter. The table name should match the table for the `Automodeler_Model` class that you are using with the DAO instance.

### Writing a custom DAO Class

If you have advanced rules for creating, saving and deleting, you should create a custom DAO class. Here is a basic example:

	class AutoModeler_DAO_Database_Foo extends AutoModeler_DAO_Database
	{
		protected $_table_name = 'foo';

		public function my_custom_create(AutoModeler_Model $model, Database_Query_Builder_Insert $qb = NULL)
		{
			// ... Do your custom insert stuff here
		}
	}

Make sure you specify the $_table_name property, and make sure any custom methods you write (if any) take two parameters:

 - AutoModeler_Model $model
 - Datbase_Query_Builder_Insert $qb = NULL

This is so you can mock these items during testing and truly unit test your classes.

## Using a Database DAO Class

You should have an existing AutoModeler_Model class to pass into your DAO methods:

	$model = new Model_Foo;
	$model->set_fields(array('foo' => 'bar'));
	$dao = new AutoModeler_DAO_Database_Foo(Database::instance());
	$model = $dao->create($model);

	$model->foo = 'foobar';
	$dao->update($model);

	$dao->delete($model);

## Using a factory DAO object

You can also generate custom DAO objects at runtime, with the `factory()` method:

	$model = new Model_Foo;
	$model->set_fields(array('foo' => 'bar'));
	$dao = AutoModeler_DAO_Database::factory(Database::instance(), 'foo');
	$new_model = $dao->create($model);

Your `$new_model` variable will contain a loaded and saved model instance. You can update and delete it like this too:

	$new_model->foo = 'new value';
	$dao->update($new_model);

	$dao->delete($new_model);
