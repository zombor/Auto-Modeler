<?php

/**
 * AutoModeler_DAO_Database
 *
 * @package   AutoModeler
 * @author    Jeremy Bush
 * @copyright (c) 2012 Jeremy Bush
 * @license   http://www.opensource.org/licenses/isc-license.txt
 */
class AutoModeler_DAO_Database
{
	protected $_table_name;

	protected $_lang = 'form_errors';

	protected $_db;
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
	 * Saves a model object to the data store
	 *
	 * @param AutoModeler_Model             $model the model to save
	 * @param Database_Query_Builder_Insert $qb    a qb object for mocking
	 *
	 */
	public function create(AutoModeler_Model $model, Database_Query_Builder_Insert $qb = NULL)
	{
		if (AutoModeler_Model::STATE_NEW != $model->state())
		{
			throw new AutoModeler_Exception('Can\'t create a saved model!');
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

		$model->id = $id;
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
	public function update(AutoModeler_Model $model, Database_Query_Builder_Update $qb = NULL)
	{
		if (AutoModeler_Model::STATE_LOADED != $model->state())
		{
			throw new AutoModeler_Exception('Can\'t update a non-loaded model!');
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
}
