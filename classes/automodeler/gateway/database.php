<?php

class AutoModeler_Gateway_Database extends AutoModeler_DAO_Database
{
	protected $_model_name;

	/**
	 * Loads a single database result. You can pass any query builder object
	 * into the first parameter to load the specific data you need. 
	 * 
	 * @param Database_Query_Builder_Select $query an optional query builder
	 *                                             object to load with
	 *
	 * @return AutoModeler_Model when loading one object
	 */
	protected function _load_object(
		Database_Query_Builder_Select $query = NULL,
		AutoModeler_Model $model = NULL
	)
	{
		if (NULL === $model)
		{
			$model = new $this->_model_name;
		}

		// Start
		$model->state(AutoModeler_Model::STATE_LOADING);

		// Use a normal select query by default
		if ($query == NULL)
		{
			$query = new Database_Query_Builder_Select;
			$query->select_array(array_keys($model->as_array()));
		}

		$query->from($model->table_name());

		$data = $query->execute($this->_db);

		if ($data->count() > 1)
		{
			return $data;
		}

		// Process the results with this model's logic
		if ($data->count() AND $data = $data->current())
		{
			$this->process_load($model, $data);

			return $this->process_load_state($model);
		}
	}

	/**
	 * Loads a database result set. You can pass any query builder object
	 * into the first parameter to load the specific data you need. Use the
	 * second parameter to set the limit. Omit it to load all results.
	 *
	 * (note: if your $query has a limit parameter in it, this method might
	 *        exibit odd behavior ;)
	 * 
	 * @param Database_Query_Builder_Select $query an optional query builder
	 *                                             object to load with
	 * @param int                           $limit a limit to apply to the query
	 *
	 * @return AutoModeler_Model when loading one object
	 */
	protected function _load_set(
		Database_Query_Builder_Select $query = NULL,
		AutoModeler_Model $model = NULL
	)
	{
		if (NULL === $model)
		{
			$model = new $this->_model_name;
		}

		// Start
		$model->state(AutoModeler_Model::STATE_LOADING);

		// Use a normal select query by default
		if ($query == NULL)
		{
			$query = new Database_Query_Builder_Select;
			$query->select_array(array_keys($model->as_array()));
		}

		$query->from($model->table_name());

		$query->as_object($this->_model_name);

		$data = $query->execute($this->_db);

		return $data;
	}

	/**
	 * Processes a load() from a result
	 *
	 * @return null
	 */
	protected function _process_load(AutoModeler_Model $model, array $data)
	{
		$model->set_fields($data);
	}

	/**
	 * Processes the object state before a load() finishes
	 *
	 * @param AutoModeler_Model $model the model to process
	 *
	 * @return AutoModeler_Model
	 */
	protected function _process_load_state(AutoModeler_Model $model)
	{
		if ($model->id)
		{
			$model->state(AutoModeler_Model::STATE_LOADED);
		}

		return $model;
	}

}
