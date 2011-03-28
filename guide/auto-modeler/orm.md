# AutoModeler_ORM Documentation

The ORM extension adds the following methods and properties to AutoModeler:

 * $_has_many and $_belongs_to member variables to define related tables for many to many relationships
 * overloaded __get() method for one to many relationships (your column needs "child_id" convention [user_id])
 * overloaded __set() method for one to many and many to many relationships
 * find_related($key) method for retrieving many to many relationships
 * remove($key, $id) to delete many to many relationships
 * overloaded delete() method to handle many to many relationships

## How to set up your database and model

Follow these steps to set your code up:

1. Your model needs to extend AutoModeler_ORM:

	class Blog extends AutoModeler_ORM

2. For many to many relationships, your model needs a $_has_many and _belongs_to variable with an array of your related tables:

	protected $_has_many = array('cars', 'boats');
	protected $_belongs_to = array('trains');

These models all need an "id" field in them as your primary key.

3. Now you need to set up join tables in your database for your related tables. The format is "parents_childs". So for our blog example, we would create three tables: blogs_cars, blogs_boats and trains_blogs. In this table, you need three columns, id, parent_id, child_id. So for our example, our columns would be: id, blog_id, car_id for the blogs_cars table; id, blog_id, boat_id for your blogs_boats table;  id, train_id, blog_id for your trains_blogs table.

This is all the setup!

## Working with one to many relationships

	$blog = new Model_Blog;

### Get a model to relate

	$foo = new Model_Foo(1);

### Relate foo to blog

	$blog->foo_id = $foo->id;
	$blog->save();

### Read it back

	$foo = $blog->foo; // $foo is a Model_Foo with id = 1

## Working with many to many relationships

	$blog = new Model_Blog(1); // Need a loaded model

## Get a model to relate

	$car = new Model_Car(1);

### Relate them

	$blog->cars = $car->id; // Works right away, no need to save()

### Read the relationships back

	$cars = $blog->find_related('cars'); // $cars is a database result object containing the relationships

## With support

AutoModeler_ORM comes with robust support for load()ing relationships with() the main model. This works with one to many relationships (you need a model_id column in your table). Suppose you have a Model_User with a `foobar` relationship:

	$user = new Model_User();
	$user->with('foobar')->load(db::select()->where('users.id', '=', 1));

Now when you call $user->foobar, it will not run an additional query. You must use with() alongside of load().

If you'd like a model to always load with another model, you can manually specify the $_load_with variable in the model:

	protected $_load_with = 'foobar';