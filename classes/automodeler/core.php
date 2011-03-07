<?php
/**
* AutoModeler
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/
class AutoModeler_Core extends Model_Database implements ArrayAccess
{
	const VERSION = '3.7.1';

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

	protected $_state = AutoModeler::STATE_NEW;

	const STATE_NEW = 'new';
	const STATE_LOADING = 'loading';
	const STATE_LOADED = 'loaded';
	const STATE_DELETED = 'deleted';

	// Lists available states for this model
	protected $_states = array(
		AutoModeler::STATE_NEW,
		AutoModeler::STATE_LOADING,
		AutoModeler::STATE_LOADED,
		AutoModeler::STATE_DELETED
	);

	/**
	 * Standard constructor, accepts an `id` column to look for
	 *
	 * @param string $id  an id to search for
	 *
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($this->_db);

		if ($id !== NULL)
		{
			$this->load(db::select_array(array_keys($this->_data))->where($this->_table_name.'.id', '=', $id));
		}
	}

	/**
	 * Loads a database result. Can be used to load a single item into this model
	 * or return a result set of many models. You can pass any query builder object
	 * into the first parameter to load the specific data you need. Common usage:
	 * 
	 * 	$user = new Model_User;
	 * 	// Load a specific row
	 * 	$user->load(db::select_array($user->fields())->where('id', '=', '1'));
	 * 
	 * 	// Load many rows with where
	 * 	$result = Model::factory('user')->load(db::select_array($user->fields())->where('id', '>', '3'), NULL);
	 * 
	 * 	// Load all rows
	 * 	$result = Model::factory('user')->load(NULL, NULL);
	 * 
	 * 	// Load first two rows
	 * 	$result = Model::factory('user')->load(NULL, 2);
	 * 
	 * @param Database_Query_Builder_Select $query an optional query builder object to load with
	 * @param integer                       $limit a number greater than one will return a data set
	 *
	 * @return $this when loading one object
	 * @return Database_Result when loading multiple results
	 */
	public function load(Database_Query_Builder_Select $query = NULL, $limit = 1)
	{
		// Start
		$this->_state = AutoModeler::STATE_LOADING;

		// Use a normal select query by default
		if ($query == NULL)
		{
			$query = db::select_array(array_keys($this->_data));
		}

		// Add limit if passed
		if ($limit)
		{
			$query->limit($limit);
		}

		$query->from($this->_table_name);

		// If we are going to return a data set, we want objects back
		if ($limit != 1)
		{
			$query->as_object(get_class($this));
		}

		$data = $query->execute($this->_db);

		if ($limit != 1)
		{
			return $data;
		}

		// Process the results with this model's logic
		if (count($data) AND $data = $data->current())
		{
			$this->process_load($data);
		}

		// We are done!
		$this->process_load_state();

		return $this;
	}

	/**
	 * Processes a load() from a result
	 *
	 * @return null
	 */
	protected function process_load($data)
	{
		$this->_data = $data;
	}

	/**
	 * Processes the object state before a load() finishes
	 *
	 * @return null
	 */
	public function process_load_state()
	{
		if ($this->id)
		{
			$this->_state = AutoModeler::STATE_LOADED;
		}
		else
		{
			$this->_state = AutoModeler::STATE_NEW;
		}
	}

	/**
	 * Magic get method, gets model properties from the db
	 *
	 * @param string $key the field name to look for
	 * 
	 * @throws AutoModeler_Exception
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
	 * Magic get method, set model properties to the model
	 *
	 * @param string $key   the field name to set
	 * @param string $value the value to set to
	 * 
	 * @throws AutoModeler_Exception
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

		Log::instance()->add(Log::ERROR, 'Field '.$key.' does not exist in '.get_class($this).'!');
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
	 * Gets/sets the object state
	 *
	 * @return string/$this when getting/setting
	 */
	public function state($state = NULL)
	{
		if ($state)
		{
			if ( ! in_array($state, $this->_states))
			{
				throw new AutoModeler_Exception('Invalid state');
			}

			$this->_state = $state;

			return $this;
		}

		return $this->_state;
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
	 * Gets the table name for this object
	 *
	 * @return string
	 */
	public function get_table_name()
	{
		return $this->_table_name;
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
		$model = 'Model_'.ucfirst($model);
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
		$data = $validation instanceof Validation ? $validation->copy($validation->as_array()+$this->_data) : Validation::factory($this->_data);

		$data->bind(':model', $this);

		foreach ($this->_rules as $field => $rules)
		{
			$data->rules($field, $rules);
		}

		if ($data->check(TRUE))
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
			if ($this->state() == AutoModeler::STATE_LOADED) // Do an update
			{
				return count(db::update($this->_table_name)->set(array_diff_assoc($this->_data, array('id' => $this->_data['id'])))->where('id', '=', $this->_data['id'])->execute($this->_db));
			}
			else // Do an insert
			{
				$columns = array_keys($this->_data);
				$id = db::insert($this->_table_name)
						->columns($columns)
						->values($this->_data)->execute($this->_db);

				$this->state(AutoModeler::STATE_LOADED);

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
		if (AutoModeler::STATE_LOADED)
		{
			$this->_state = AutoModeler::STATE_DELETED;

			return db::delete($this->_table_name)->where('id', '=', $this->_data['id'])->execute($this->_db);
		}

		throw new AutoModeler_Exception('Cannot delete a non-loaded model '.get_class($this).'!', array(), array());
	}

	/**
	 * Get a nice array for form::dropdown()
	 *
	 * @param array  $key       the key to use for the array
	 * @param array  $where     the value to use for the display
	 * @param array  $where     the where clause
	 *
	 * @return Database_Result
	 */
	public function select_list($key, $display, Database_Query_Builder_Select $query = NULL)
	{
		$rows = array();

		$array_display = FALSE;
		$select_array = array($key);
		if (is_array($display))
		{
			$array_display = TRUE;
			$select_array = array_merge($select_array, $display);
		}
		else
		{
			$select_array[] = $display;
		}

		if ($query) // Fetch selected rows
		{
			$query = $this->load($query->select_array($select_array), NULL);
		}
		else // Fetch all rows
		{
			$query = $this->load(db::select_array($select_array), NULL);
		}

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
			{
				$rows[$row->$key] = $row->$display;
			}
		}

		return $rows;
	}

	/**
	 * Returns all the columns in this model
	 *
	 * @return array
	 */
	public function fields()
	{
		foreach ($this->_data as $key => $value)
			$fields[] = $this->_table_name.'.'.$key;

		return $fields;
	}

	/**
	 * Returns if the specified field exists in the model
	 *
	 * @return bool
	 */
	public function field_exists($field)
	{
		return array_key_exists($field, $this->_data);
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
}

class AutoModeler_Exception extends Kohana_Exception
{
	public $errors;

	public function __construct($title, array $message = NULL, $errors = '')
	{
		parent::__construct($title, $message);
		$this->errors = $errors;
	}

	public function __toString()
	{
		return $this->message;
	}
}