<?php

class AutoModeler_Repository_Database
{
	protected $_db;
	protected $_table_name;
	protected $_model_name;

	/**
	 * Constructor is for pass in a database instance, DI style
	 *
	 * @param Database $database the database instance
	 */
	public function __construct(Database $database)
	{
		$this->_db = $database;
	}

	/**
	 * Factory method for obtaining repository objects with a table name
	 *
	 * @param Database $database a datbase object to use
	 * @param string $table_name the table name to use for this repository
	 *
	 * @return AutoModeler_Repository_Database the object
	 */
	public static function factory(Database $database, $table_name)
	{
		$repository = new AutoModeler_Repository_Database($database);
		$repository->_table_name = (string) $table_name;

		return $repository;
	}

	/**
	 * Returns the table name for this repository
	 *
	 * @return string the table name
	 */
	public function table_name()
	{
		return $this->_table_name;
	}

	/**
	 * Saves a model object to the data store
	 *
	 * @param AutoModeler_Model             $model the model to save
	 * @param Database_Query_Builder_Insert $qb    a qb object for mocking
	 *
	 */
	public function create(AutoModeler_Model $model, Validation $validation = NULL, Database_Query_Builder_Insert $qb = NULL, Validation $default_validation = NULL)
	{
		if (AutoModeler_Model::STATE_NEW != $model->state())
		{
			throw new AutoModeler_Exception('Can\'t create a saved model!');
		}

		$status = $model->valid($validation, $default_validation);
		if ($status !== TRUE)
		{
			throw new AutoModeler_Exception_Validation($status['errors'], 'Unable to validate array: '.implode(', ', $status['errors']));
		}

		$data = $model->as_array();
		$columns = array_keys($data);
		$values = array_values($data);

		if (NULL == $qb)
		{
			$qb = new Database_Query_Builder_Insert($this->_table_name, $columns);
		}

		$qb->values($values);
		$id = $qb->execute($this->_db);

		$model->id = $id[0];
		$model->state(AutoModeler_Model::STATE_LOADED);

		return $model;
	}

	/**
	 * Persists a loaded model to the data store
	 *
	 * @param AutoModeler_Model             $model the model to save
	 * @param Database_Query_Builder_Update $qb    optional qb object for mocks
	 *
	 * @return the count of how many rows were updated
	 */
	public function update(AutoModeler_Model $model, Validation $validation = NULL, Database_Query_Builder_Update $qb = NULL, Validation $default_validation = NULL)
	{
		if (AutoModeler_Model::STATE_LOADED != $model->state())
		{
			throw new AutoModeler_Exception('Can\'t update a non-loaded model!');
		}

		$status = $model->valid($validation, $default_validation);
		if ($status !== TRUE)
		{
			throw new AutoModeler_Exception_Validation($status['errors'], 'Unable to validate array: '.implode(', ', $status['errors']));
		}

		$data = $model->as_array();

		if (NULL == $qb)
		{
			$qb = new Database_Query_Builder_Update($this->_table_name);
		}

		// Remove the id, this should not be part of the update
		$qb->set(array_diff_assoc($data, array('id' => $data['id'])));
		$qb->where('id', '=', $data['id']);
		$count = $qb->execute($this->_db);

		return $count;
	}

	/**
	 * Deletes a persited model from the data store
	 *
	 * @param AutoModeler_Model             $model the model to delete
	 * @param Database_Query_Builder_Delete $qb    optional qb object for mocking
	 */
	public function delete(AutoModeler_Model $model, Database_Query_Builder_delete $qb = NULL)
	{
		if (AutoModeler_Model::STATE_LOADED != $model->state())
		{
			throw new AutoModeler_Exception('Can\'t delete a non-loaded model!');
		}

		$data = $model->as_array();

		if (NULL == $qb)
		{
			$qb = new Database_Query_Builder_Delete($this->_table_name);
		}

		$qb->where('id', '=', $data['id']);
		$count = $qb->execute($this->_db);

		if (1 == $count)
		{
			$model->state(AutoModeler_Model::STATE_NEW);
			$model->id = NULL;
		}

		return $model;
	}

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

		$query->from($this->table_name());

		$data = $query->execute($this->_db);

		// Process the results with this model's logic
		if ($data->count() AND $data = $data->current())
		{
			$this->_process_load($model, $data);

			return $this->_process_load_state($model);
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
		Database_Query_Builder_Select $query = NULL
	)
	{
		// Use a normal select query by default
		if ($query == NULL)
		{
			$model = new $this->_model_name;

			$query = new Database_Query_Builder_Select;
			$query->select_array(array_keys($model->as_array()));
		}

		$query->from($this->_table_name);

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
		$model->data($data);
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
