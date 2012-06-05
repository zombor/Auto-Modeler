<?php

class AutoModeler_Model
{
	protected $_data = array();

	protected $_state = self::STATE_NEW;

	const STATE_NEW = 'new';
	const STATE_LOADING = 'loading';
	const STATE_LOADED = 'loaded';
	const STATE_DELETED = 'deleted';

	protected $_rules = array();
	protected $_validated = FALSE;
	protected $_lang = 'automodeler';

	protected $_gateway;

	/**
	 * Constructor allows end user to define new arbitrary models
	 *
	 * @param array $data an optional data definition array
	 */
	public function __construct(array $data = NULL)
	{
		if (NULL !== $data)
		{
			$this->_data = array_combine($data, array_fill(0, count($data), NULL));
		}
	}

	/**
	 * Getter method to read data values from this model
	 *
	 * @param string $key the key to get
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->_data))
		{
			return $this->_data[$key];
		}

		throw new AutoModeler_Exception('Undefined key: '.$key);
	}

	/**
	 * Setter method to set data array properties
	 *
	 * @param string $key the key to set
	 * @param mixed  $val the value to set
	 */
	public function __set($key, $val)
	{
		if (array_key_exists($key, $this->_data))
		{
			$this->_data[$key] = $val;

			// We've changed, so we aren't valid anymore
			$this->_validated = FALSE;
		}
	}

	/**
	 * Mass assignment method.
	 *
	 * @param array $data the data to set
	 */
	public function data(array $data)
	{
		foreach (array_intersect_key($data, $this->_data) as $key => $value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Returns this model's state
	 *
	 * @return string
	 */
	public function state($new_state = NULL)
	{
		if ($new_state)
		{
			$this->_state = $new_state;
		}

		return $this->_state;
	}

	/**
	 * Returns this model as an array
	 *
	 * @return array
	 */
	public function as_array()
	{
		return $this->_data;
	}

	/**
	 * Getter/setter method for rules
	 *
	 * @param array $rules rules to set on this object. Will overwrite
	 *                     all previous rules.
	 *
	 * @return array the current rules for this model
	 */
	public function rules(array $rules = NULL)
	{
		if ($rules)
		{
			$this->_rules = $rules;
		}

		return $this->_rules;
	}

	/**
	 * Runs business logic validations on this model.
	 *
	 * You can pass an existing validation object into this method.
	 * This will let you add some application specific validations to
	 * run on the model. Password validation is a good use case for
	 * this.
	 *
	 * You can use the :model binding in your validation rules to
	 * access this model object.
	 *
	 * @param Validation $validation a previously filled validation obj
	 *
	 * @return array
	 */
	public function valid(Validation $validation = NULL, Validation $default_validation = NULL)
	{
		$data = $validation instanceof Validation 
			? $validation->copy($validation->as_array()+$this->as_array())
			: $default_validation;

		if ( ! $default_validation)
		{
			$data = new Validation($this->as_array());
		}

		$data->bind(':model', $this);

		foreach ($this->_rules as $field => $rules)
		{
			$data->rules($field, $rules);
		}

		if ($data->check(TRUE))
		{
			return TRUE;
		}
		else
		{
			return array(
				'status' => FALSE,
				'errors' => $data->errors($this->_lang)
			);
		}
	}

	/**
	 * Returns the gateway object for this class
	 *
	 * @return AutoModeler_Gateway
	 */
	public function gateway()
	{
		return new $this->_gateway;
	}
}
