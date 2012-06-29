# Repository Class

## Creating a Repository class

There are two ways to use the repository class:

### Factory Pattern

You can use the factory pattern if you have a basic need for only Create, Update and Delete functionality. This has already been written for you.

	$repository = AutoModeler_Repository_Database::factory(Database::instance(), $table_name);

You must pass in a database instance as the first parameter and the table name you wish to use as the second parameter. The table name should match the table for the `Automodeler_Model` class that you are using with the repository instance.

### Writing a custom Repository Class

If you have advanced rules for creating, saving and deleting, you should create a custom repository class. Here is a basic example:

	class AutoModeler_Repository_Database_Foo extends AutoModeler_Repository_Database
	{
		protected $_model_name = 'foo';
		protected $_table_name = 'foo';

		public function my_custom_create(AutoModeler_Model $model, Database_Query_Builder_Insert $qb = NULL)
		{
			// ... Do your custom insert stuff here
		}
	}

Make sure you specify the $_table_name and $_model_name properties, and make sure any custom methods you write (if any) take two parameters:

 - AutoModeler_Model $model
 - Datbase_Query_Builder_Insert $qb = NULL

This is so you can mock these items during testing and truly unit test your classes.

## Using a Database Repository Class

You should have an existing AutoModeler_Model class to pass into your repository methods:

	$model = new Model_Foo;
	$model->set_fields(array('foo' => 'bar'));
	$repository = new AutoModeler_Repository_Database_Foo(Database::instance());
	$model = $repository->create($model);

	$model->foo = 'foobar';
	$repository->update($model);

	$repository->delete($model);

## Using a factory repository object

You can also generate custom repository objects at runtime, with the `factory()` method:

	$model = new Model_Foo;
	$model->set_fields(array('foo' => 'bar'));
	$repository = AutoModeler_Repository_Database::factory(Database::instance(), 'foo');
	$new_model = $repository->create($model);

Your `$new_model` variable will contain a loaded and saved model instance. You can update and delete it like this too:

	$new_model->foo = 'new value';
	$repository->update($new_model);

	$repository->delete($new_model);
