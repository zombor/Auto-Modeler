# auto-modeler: Simple and easy to use Kohana 3 CRUD/ORM library

## What does it do?

AutoModeler is a group of modeling classes to let you write better apps. It facilitates the seperation of persistance logic from your models.

## Using it

See the userguide contents in the `guide/` directory.

## Running tests / contributing

AutoModeler uses PHPSpec and Mockery for it's testing suite. These are pulled in with Composer:

	wget http://getcomposer.org/composer.phar
	php composer.phar install

This will install composer. Run the specs with:

	./phpspec-composer.php specs -f d -b -c

If you are contributing to AutoModeler, you must include specs with your contribution.
