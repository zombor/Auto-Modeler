<?php

include_once 'classes/automodeler/repository.php';

class DescribeAutomodelerRepository extends \PHPSpec\Context
{
	public function itStoresRepositoriesForRetreival()
	{
		AutoModeler_Repository::add('foo', 'bar');
		$this->spec(AutoModeler_Repository::fetch('foo'))->should->be('bar');
	}
}
