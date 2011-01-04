<?php defined('SYSPATH') or die('No direct script access.');

class AutoModeler_Versioned extends AutoModeler_ORM
{
	protected $_table_versioned = null;
	
	protected $_data_old = array();

	public function __construct($id = NULL)
	{
		parent::__construct($id);
	
		$this->_table_versioned = ($this->_table_versioned == null) ? $this->_table_name.'_versions' : $this->_table_versioned;
		$this->_data_old = $this->_data;
	}
	
	/**
	 * Overload AutoModeler::save() to support versioned data
	 *
	 * @chainable
	 * @return object AutoModeler
	 */
	public function save($validation = NULL)
	{
		if(count(array_diff($this->_data, $this->_data_old)) == 0)
		{
			return; // Need to throw a warning for this when nothing changed.
		}
		
		if ($this->_data['id']) // Push the version up
		{
			$this->_data_old['id'] = null;
			$this->_data_old[inflector::singular($this->_table_name).'_id'] = $this->_data['id'];
			$this->_data['version'] = 1 + $this->_data['version'];
			
			
			$columns = array_keys($this->_data_old);
			db::insert($this->_table_versioned)
				->columns($columns)
				->values($this->_data_old)
			->execute($this->_db);
		
			return parent::save($validation);
		}
		else // Do an insert
		{
			return parent::save($validation);
		}
	}
	
	/**
	 * Loads previous version from current object
	 *
	 * @chainable
	 * @return object AutoModeler
	 */
	public function previous()
	{
		if ($this->id)
		{
			$version = $this->version - 1;
			$item_id = inflector::singular($this->_table_name).'_id';
			
			$data = db::select_array(array_keys($this->_data))
				->where($item_id, '=', $this->id)
				->where('version', '=', $version)
				->from($this->_table_versioned)
				->as_array()
			->execute($this->_db);

			if (count($data) AND $data = $data->current())
			{
				foreach ($data as $field => $value)
				{
					$this->$field = $value;
				}
			}
		}

		return $this;
	}
	
	/**
	 * Restores the object with data from stored version
	 *
	 * @param   integer version number you want to restore
	 * @return object AutoModeler
	 */
	public function restore($version)
	{
		if ($this->id)
		{
			$item_id = inflector::singular($this->_table_name).'_id';
			
			$data = db::select_array(array_keys($this->_data))
				->where($item_id, '=', $this->id)
				->where('version', '=', $version)
				->from($this->_table_versioned)
				->as_array()
			->execute($this->_db);
			
			$data['id'] = null;
			$data['version']++;
		
			if (count($data) AND $data = $data->current())
			{
				foreach ($data as $field => $value)
				{
					$this->$field = $value;
				}
				
				$this->save();
			}
		}

		return $this;
	}
	
	/**
	 * Overloads AutoModeler::delete() to push the element in the version table
	 *
	 * @param   integer  id of the object you want to delete
	 * @return  object	AutoModeler
	 */
	public function delete($id = NULL)
	{
		if ($this->_data['id']) // Push the version up
		{
			$this->_data_old['id'] = null;
			$this->_data_old[inflector::singular($this->_table_name).'_id'] = $this->_data['id'];
			
			$columns = array_keys($this->_data_old);
			db::insert($this->_table_versioned)
				->columns($columns)
				->values($this->_data_old)
			->execute($this->_db);
		}
	}
	
	/**
	 * Delete all versions without keeping any records
	 *
	 * @param   integer  id of the object you want to delete
	 */
	public function delete_all($id = NULL)
	{
		$item_id = inflector::singular($this->_table_name).'_id';
		
		DB::delete($this->_table_versioned)
			->where($item_id, '=', $id)
		->execute($this->_db);

		return parent::delete($id);
	}
}