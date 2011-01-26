# AutoModeler

Auto_Modeler is an extension to your models that enables rapid application development at a small cost. It enables you to easily create, edit, delete and retrieve database records.

## Setup

In order to use AutoModeler with your models, copy the module to your MODPATH, and add it in your bootstrap.php file. Then you need to add the following to your model you wish to use it with:

	<?php

	class Model_Foo extends AutoModeler {
	  protected $_table_name = 'foos';

	  protected $_data = array('id' => '', 'bar' => '', 'baz' => '');
	}

The $data variable is an associative array containing the table column names and default values for the blogs table in this case.

## Working with models

Working with models is very easy.

### Create a new model

	$foo = new Model_Foo;

### Assign data

	$foo->bar = 'foobar';
	$foo->baz = 'bazbar';

Or use set_fields():

	$foo->set_fields(
		'bar' => 'foobar',
		'baz' => 'bazbar',
	)

### Save the model

	$foo->save();

## Obtain an existing model

	$foo = new Model_Foo(1);

## Update it's values

	$foo->bar = 'changed';

## Save the model

	$foo->save();

## ArrayAccess

You can also access your model fields via the ArrayAccess interface: $user['username']

## In-Model Validation

AutoModeler supports in-model validation. You can defines your field rules in your model, and upon save(), the library will run validation for you on the entered fields. The process to do this is as follows:

Create a $_rules array in your model. They key is the field name, and the value is an array of rules.

    $_rules = array('name' => array('required', 'alpha_dash', 'min_length' => array('2')),
                   'address' => array('required'));

Now, when you save() your model, it will check the "name" and "address" fields with the rules provided. If validation fails, the library will throw an exception containing the failed error messages. You can use a try/catch block to detect failing validation:

	<?php
	    public function add()
	    {
	        $client = new Model_Client();

            $this->template->body = new View('admin/client/form');
            $this->template->body->errors = '';
            $this->template->body->client = $client;
            $this->template->body->title = 'Add';

	        if ($_POST) // Save the data
	        {
	            $client->set_fields(array_intersect_key(array('name', 'address'), $_POST));

	            try
	            {
	                $client->save();
	                $this->request->redirect('client/view/'.$client->short_name);
	            }
	            catch (Kohana_User_Exception $e)
	            {
	                $this->template->body->client = $client;
	                $this->template->body->errors = $e;
	            }
	        }
	    }

In your view, you can simply echo the $errors variable to get a nice list of errors. You can also use $e->errors to get a raw array, for example if you want to display your errors inline with your fields.

### Passing pre-built validations

For more advanced validations, such as password verifications that don't directly belong to the model, you can pass a pre-built validation object to save() and validate() and it will combine the validation objects. Below is an example:

	<?php

	class Controller_Test extends Controller
	{
		public function index()
		{
			$user = new Model_User;
		
			$_POST = array('username' => 'foobar',
			               'password' => 'testing',
			               'repeat_password' => 'tsting');

			$validation = new Validation($_POST);
			$validation->add_rules('password', 'matches[repeat_password]');
			try
			{
				$user->set_fields($_POST);
				$user->save($validation);
			}
			catch (Auto_Modeler_Exception $e)
			{
				echo $e;
				echo Kohana::debug($e->errors);
				die(Kohana::debug($e));
			}

			die(Kohana::debug($user));
		}
	}

If you run this, you will get an error about the passwords not matching. You can take the example from here to create advanced validation schemes for your models.