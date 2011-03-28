<?php defined('SYSPATH') or die('No direct script access.');
/**
* User Model
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
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

	protected $_rules = array(
		'username' => array(
			array('not_empty'),
		),
		'email' => array(
			array('email'),
		)
	);

	/**
	 * overload __set() to hash a password
	 *
	 * @return string
	 */
	public function __set($key, $value)
	{
		if ($key == 'password')
		{
			$this->_data[$key] = sha1($value);
			return;
		}

		return parent::__set($key, $value);
	}
}