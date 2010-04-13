<?php defined('SYSPATH') or die('No direct script access.');
/**
* Role Model
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/
class Model_Role extends AutoModelerORM {

	protected $_table_name = 'roles';

	protected $_data = array('id' => '',
	                        'name' => '');

	protected $_belongs_to = array('users');

} // End Model_Role