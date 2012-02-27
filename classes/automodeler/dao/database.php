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
	 * @param AutoModeler_Model $model the model to save
	 *
	 */
	public function save(AutoModeler_Model $model)
	{
		if (AutoModeler_Model::STATE_NEW == $model->state())
		{
			$data = $model->as_array();
			$columns = array_keys($data);
			$values = array_values($data);
var_dump('foobar');
			$insert = new Database_Query_Builder_Insert($this->_table_name, $columns);
			$insert->values($values);
			$id = $insert->execute($this->_db);

			$model->pk_value($id);
			$model->state(AutoModeler_Model::STATE_LOADED);
		}

		return $model;
	}
}
