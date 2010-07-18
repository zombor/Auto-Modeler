<?php
/**
* AutoModeler
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/
class AutoModeler extends Model implements ArrayAccess, Iterator
{
	const VERSION = 3.2;

	// The database table name
	protected $_table_name = '';

	// The database fields
	protected $_data = array();

	// Validation rules in a 'field' => 'rules' array
	protected $_rules = array();
	protected $_callbacks = array();

	protected $_validation = array();

	protected $_validated = FALSE;

	protected $_lang = 'form_errors';

	/**
	 * Standard constructor, accepts an `id` column to look for
	 *
	 * @param string $id  an id to search for
	 *
	 */
	public function __construct($id = NULL)
	{
		parent::__construct();

		if ($id != NULL)
		{
			// try and get a row with this ID
			$data = db::select('*')->from($this->_table_name)->where('id', '=', $id)->execute($this->_db);

			// try and assign the data
			if (count($data) == 1 AND $data = $data->current())
			{
				foreach ($data as $key => $value)
					$this->_data[$key] = $value;
			}
		}
	}

	/**
	 * Magic get method, gets model properties from the db
	 *
	 * @param string $key the field name to look for
	 *
	 * @return String
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->_data))
		 	return $this->_data[$key];

		throw new AutoModeler_Exception('Field '.$key.' does not exist in '.get_class($this).'!', array(), '');
	}

	/**
	 * Magic get method, gets model properties from the db
	 *
	 * @param string $key   the field name to set
	 * @param string $value the value to set to
	 *
	 */
	public function __set($key, $value)
	{
		if (array_key_exists($key, $this->_data))
		{
			$this->_data[$key] = $value;
			$this->_validated = FALSE;
			return;
		}

		throw new AutoModeler_Exception('Field '.$key.' does not exist in '.get_class($this).'!', array(), '');
	}

	/**
	 * sleep method for serialization
	 *
	 * @return array
	 */
	public function __sleep()
	{
		// Store only information about the object without db property
		return array_diff(array_keys(get_object_vars($this)), array('_db'));
	}

	/**
	 * wakeup method for serialization
	 *
	 */
	public function __wakeup()
	{
		$this->_db = Database::instance($this->_db);
	}

	/**
	 * Magic isset method to test _data
	 *
	 * @param string $name the property to test
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->_data[$name]);
	}

	/**
	 * Gets an array version of the model
	 *
	 * @return array
	 */
	public function as_array()
	{
		return $this->_data;
	}

	/**
	 * Useful for chaining
	 *
	 * @param string $model the model name
	 * @param int    $id    an id to pass to the constructor
	 *
	 * @return Object
	 */
	public static function factory($model, $id = NULL)
	{
		$model = empty($model) ? __CLASS__ : 'Model_'.ucfirst($model);
		return new $model($id);
	}

	/**
	 * Mass sets object properties
	 *
	 * @param array $data the data to set
	 *
	 */
	public function set_fields(array $data)
	{
		foreach (array_intersect_key($data, $this->_data) as $key => $value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Returns errors for this model
	 *
	 * @param string $lang the messages file to use
	 *
	 * @return array
	 */
	public function errors($lang = NULL)
	{
		return $this->_validation != NULL ? $this->_validation->errors($lang) : array();
	}

	/**
	 * Determines the validity of this object
	 *
	 * @param mixed $validation a manual validation object to combine the model properties with
	 *
	 * @return mixed
	 */
	public function is_valid($validation = NULL)
	{
		$data = $validation instanceof Validate ? $validation->copy($validation->as_array()+$this->_data) : Validate::factory($this->_data);

		foreach ($this->_rules as $field => $rule)
		{
			foreach ($rule as $sub_rule)
				$data->rule($field, $sub_rule);
		}

		foreach ($this->_callbacks as $field => $callback)
			$data->callback($field, array($this, $callback));

		if ($data->check())
		{
			$this->_validation = NULL;
			return $this->_validated = TRUE;
		}
		else
		{
			$this->_validation = $data;
			$errors = View::factory('form_errors')->set(array('errors' => $data->errors($this->_lang)));
			return array('string' => $errors->render(), 'errors' => $data->errors($this->_lang));
		}
	}

	/**
	 * Saves the current object
	 *
	 * @param mixed $validation a manual validation object to combine the model properties with
	 *
	 * @return int
	 */
	public function save($validation = NULL)
	{
		$status = $this->_validated ? TRUE : $this->is_valid($validation);

		if ($status === TRUE)
		{
			if ($this->_data['id']) // Do an update
			{
				return count(db::update($this->_table_name)->set(array_diff_assoc($this->_data, array('id' => $this->_data['id'])))->where('id', '=', $this->_data['id'])->execute($this->_db));
			}
			else // Do an insert
			{
				$columns = array_keys($this->_data);
				$id = db::insert($this->_table_name)
						->columns($columns)
						->values($this->_data)->execute($this->_db);
				return ($this->_data['id'] = $id[0]);
			}
		}

		throw new AutoModeler_Exception($status['string'], array(), $status['errors']);
	}

	/**
	 * Deletes the current object from the database
	 *
	 * @return integer
	 */
	public function delete()
	{
		if ($this->_data['id'])
		{
			return db::delete($this->_table_name)->where('id', '=', $this->_data['id'])->execute($this->_db);
		}

		throw new AutoModeler_Exception('Cannot delete a non-saved model '.get_class($this).'!', array(), array());
	}

	/**
	 * fetches all rows in the database for this model
	 *
	 * @param string $order_by  a column to order on
	 * @param string $direction the direction to sort
	 *
	 * @return Database_Result
	 */
	public function fetch_all($order_by = 'id', $direction = 'ASC')
	{
		return db::select('*')->from($this->_table_name)->order_by($order_by, $direction)->as_object('Model_'.inflector::singular(ucwords($this->_table_name)))->execute($this->_db);
	}

	/**
	 * Same as fetch_all except you can pass a where clause
	 *
	 * @param array  $where     the where clause
	 * @param string $order_by  a column to order on
	 * @param string $direction the direction to sort
	 * @param string $type      the type of where to run
	 *
	 * @return Database_Result
	 */
	public function fetch_where($wheres = array(), $order_by = 'id', $direction = 'ASC', $type = 'and')
	{
		$function = $type.'_where';
		$query = db::select('*')->from($this->_table_name)->order_by($order_by, $direction)->as_object('Model_'.inflector::singular(ucwords($this->_table_name)));

		foreach ($wheres as $where)
			$query->$function($where[0], $where[1], $where[2]);

		return $query->execute($this->_db);
	}

	/**
	 * Same as fetch_where except you get a nice array back for form::dropdown()
	 *
	 * @param array  $key       the key to use for the array
	 * @param array  $where     the value to use for the display
	 * @param string $order_by  a column to order on
	 * @param array  $where     the where clause
	 *
	 * @return Database_Result
	 */
	public function select_list($key, $display, $order_by = 'id', $where = array())
	{
		$rows = array();

		$query = empty($where) ? $this->fetch_all($order_by) : $this->fetch_where($where, $order_by);

		$array_display = is_array($display);

		foreach ($query as $row)
		{
			if ($array_display)
			{
				$display_str = array();
				foreach ($display as $text)
					$display_str[] = $row->$text;
				$rows[$row->$key] = implode(' - ', $display_str);
			}
			else
				$rows[$row->$key] = $row->$display;
		}

		return $rows;
	}

	// Array Access Interface
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->_data);
	}

	public function offsetSet($key, $value)
	{
		$this->__set($key, $value);
	}

	public function offsetGet($key)
	{
		return $this->$key;
	}

	public function offsetUnset($key)
	{
		$this->_data[$key] = NULL;
	}

	// Iterable interface
	public function rewind()
	{
		return reset($this->_data);
	}

	public function current()
	{
		return current($this->_data);
	}

	public function key()
	{
		return key($this->_data);
	}

	public function next()
	{
		return next($this->_data);
	}

	public function valid()
	{
		return key($this->_data) !== null;
	}
}

class AutoModeler_Exception extends Kohana_Exception
{
	public $errors;

	public function __construct($title, $message, $errors)
	{
		parent::__construct($title, $message);
		$this->errors = $errors;
	}

	public function __toString()
	{
		return $this->message;
	}
}