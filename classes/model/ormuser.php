<?php defined('SYSPATH') or die('No direct script access.');
/**
* User Model
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/
class Model_ORMUser extends AutoModeler_ORM {

	protected $_table_name = 'ormusers';

	protected $_data = array('id' => '',
	                         'username' => '',
	                         'password' => '',
	                         'email' => '',
	                         'last_login' => '',
	                         'logins' => '');

	protected $_rules = array('username' => array('not_empty'),
	                          'email' => array('email'));

	protected $_has_many = array('testroles');
}