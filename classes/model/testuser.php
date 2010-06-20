<?php defined('SYSPATH') or die('No direct script access.');
/**
* User Model
*
* @package        Auto_Modeler
* @author         Jeremy Bush
* @copyright     (c) 2008 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/
class Model_TestUser extends AutoModeler {

	protected $_table_name = 'testusers';

	protected $_data = array('id' => '',
	                        'username' => '',
	                        'password' => '',
	                        'email' => '',
	                        'last_login' => '',
	                        'logins' => '');

	protected $_rules = array('username' => array('not_empty'),
	                          'email' => array('email'));
}