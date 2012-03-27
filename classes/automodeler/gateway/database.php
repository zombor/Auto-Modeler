<?php

class AutoModeler_Gateway_Database extends AutoModeler_DAO_Database
{
	protected $_model_name;

	/**
	 * Loads a database result. Can be used to load a single item into this model
	 * or return a result set of many models. You can pass any query builder object
	 * into the first parameter to load the specific data you need. Common usage:
	 * 
	 * @param Database_Query_Builder_Select $query an optional query builder object to load with
	 * @param integer                       $limit a number greater than one will return a data set
	 *
	 * @return AutoModeler_Model when loading one object
	 * @return Database_Result when loading multiple results
	 */
	protected function _load(Database_Query_Builder_Select $query = NULL)
	{
		$model = new $this->_model_name;

		// Start
		$model->state(AutoModeler_Model::STATE_LOADING);

		// Use a normal select query by default
		if ($query == NULL)
		{
			$query = new Database_Query_Builder_Select;
			$query->select_array(array_keys($model->as_array()));
		}

		$query->from($this->_table_name);

		// If we are going to return a data set, we want objects back
		// How do we do this without passing $limit now?
		if ($limit != 1)
		{
			$query->as_object($this->_model_name);
		}

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
	 * Processes a load() from a result
	 *
	 * @return null
	 */
	protected function process_load(AutoModeler_Model $model, array $data)
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
	protected function process_load_state(AutoModeler_Model $model)
	{
		if ($model->id)
		{
			$model->state(AutoModeler_Model::STATE_LOADED);
		}

		return $model;
	}

}
