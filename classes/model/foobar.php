<?php defined('SYSPATH') or die('No direct script access.');
/**
* Foobar Model
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/

class Model_Foobar extends AutoModeler_ORM {

	protected $_table_name = 'foobars';

	protected $_data = array(
		'id' => '',
		'name' => '',
		'ormuser_id' => '',
	);

} // End Model_Foobar