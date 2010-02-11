<?php defined('SYSPATH') or die('No direct script access.');
/**
* Role Model
*
* @package        Auto_Modeler
* @author         Jeremy Bush
* @copyright     (c) 2008 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/
class Role_Model extends Auto_Modeler_ORM {

	protected $table_name = 'roles';

	protected $data = array('id' => '',
	                        'name' => '');

	protected $belongs_to = array('users');

} // End Role_Model