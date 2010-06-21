<?php defined('SYSPATH') or die('No direct script access.');
/**
* Role Model
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/
class Model_TestRole extends AutoModeler_ORM {

	protected $_table_name = 'testroles';

	protected $_data = array('id' => '',
	                         'name' => '');

	protected $_belongs_to = array('ormusers');

} // End Model_Role