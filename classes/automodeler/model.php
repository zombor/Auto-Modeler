<?php

class AutoModeler_Model
{
	protected $_data = array();

	protected $_state = self::STATE_NEW;

	const STATE_NEW = 'new';
	const STATE_LOADING = 'loading';
	const STATE_LOADED = 'loaded';
	const STATE_DELETED = 'deleted';

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
}
