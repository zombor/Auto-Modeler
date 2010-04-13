<?php
/**
* AutoModelerORM
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/

class AutoModeler_ORM extends AutoModeler
{
	protected $_has_many = array();
	protected $_belongs_to = array();

	public function __get($key)
	{
		// See if we are requesting a foreign key
		if (isset($this->_data[$key.'_id']))
			// Get the row from the foreign table
			return db::select('*')->from(inflector::plural($key))->where('id', '=', $this->_data[$key.'_id'])->as_object('Model_'.inflector::singular(ucwords($key)))->execute($this->_db)->current();
		else if (isset($this->_data[$key]))
			return $this->_data[$key];
	}

	public function __set($key, $value)
	{
		if (in_array($key, $this->_has_many))
		{
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($key).'_id';

			// See if this is already in the join table
			if ( ! count(db::select('*')->from($this->_table_name.'_'.$key)->where($f_key, '=', $value)->where($this_key, '=', $this->_data['id'])->execute($this->_db)))
			{
				// Insert
				db::insert($this->_table_name.'_'.$key)->columns(array($f_key, $this_key))->values(array($value, $this->_data['id']))->execute($this->_db);
			}
		}
		else if (in_array($key, $this->_belongs_to))
		{
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($key).'_id';
			// See if this is already in the join table
			if ( ! count(db::select()->from($key.'_'.$this->_table_name)->where($this_key, '=', $value)->where($f_key, '=', $this->_data['id'])->execute($this->_db)))
			{
				// Insert
				db::insert($key.'_'.$this->_table_name, array($f_key => $value, $this_key => $this->_data['id']))->execute($this->_db);
			}
		}
		else
			parent::__set($key, $value);
	}

	public function relate($key, array $values, $overwrite = FALSE)
	{
		if (in_array($key, $this->_has_many))
		{
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($key).'_id';
			foreach ($values as $value)
				// See if this is already in the join table
				if ( ! count(db::select()->from($this->_table_name.'_'.$key)->where($f_key, '=', $value)->where($this_key, '=', $this->_data['id'])->execute($this->_db)))
				{
					// Insert
					db::insert($this->_table_name.'_'.$key)->columns(array($f_key, $this_key))->values(array($value, $this->_data['id']))->execute($this->_db);
				}
		}
	}

	public function find_related($key, $where = array(), $order_by = 'id', $order = 'ASC')
	{
		$model = 'Model_'.inflector::singular($key);

		$temp = new $model();
		if ($temp->has_attribute(inflector::singular($this->_table_name).'_id')) // Look for a one to many relationship
		{
			$query = db::select()->from($key)->order_by($order_by, $order);
			$query->where(inflector::singular($this->_table_name).'_id', '=', $this->_data['id']);
			foreach ($where as $sub_where)
				$query->where($sub_where[0], $sub_where[1], $sub_where[2]);
	
			return $query->as_object('Model_'.inflector::singular(ucwords($key)))->execute($this->_db);
		}
		else // Get a many to many relationship
		{
			$join_table = $this->_table_name.'_'.$key;
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($key).'_id';

			$query = db::select($key.'.*')->from($key)->join($join_table)->on($join_table.'.'.$f_key, '=', $key.'.id')->order_by($order_by, $order);
			$query->where($join_table.'.'.$this_key, '=', $this->_data['id']);
			foreach ($where as $sub_where)
				$query->where($sub_where[0], $sub_where[1], $sub_where[2]);
			return $query->as_object('Model_'.inflector::singular(ucwords($key)))->execute($this->_db);
		}
	}

	public function find_parent($key = NULL, $where = array(), $order_by = 'id', $order = 'ASC')
	{
		if ($this->has_attribute($key.'_id')) // Look for a one to many relationship
		{
			$query = db::select()->from(inflector::plural($this->_table_name));
			$query->where('id', '=', $this->_data[$key.'_id']);
			foreach ($where as $sub_where)
				$query->where($sub_where[0], $sub_where[1], $sub_where[2]);
			return $query->as_object('Model_'.ucwords($this->_table_name))->execute($this->_db);
		}
		else
		{
			$join_table = $key.'_'.$this->_table_name;
			$f_key = inflector::singular($this->_table_name).'_id';
			$this_key = inflector::singular($key).'_id';

			return db::select($key.'.*')->from($key)->order_by($order_by, $order)->where($where + array(array($join_table.'.'.$f_key, '=', $this->_data['id'])))->join($join_table, $join_table.'.'.$this_key, $key.'.id')->as_object('Model_'.inflector::singular(ucwords($key)))->execute($this->_db);
		}
	}

	// Value is an ID
	public function has($key, $value)
	{
		$join_table = $this->_table_name.'_'.$key;
		$f_key = inflector::singular($key).'_id';
		$this_key = inflector::singular($this->_table_name).'_id';

		if (in_array($key, $this->_has_many))
		{
			return (bool) db::select($key.'.id')->from($key)->where($join_table.'.'.$this_key, '=', $this->_data['id'])->where($join_table.'.'.$f_key, '=', $value)->join($join_table)->on($join_table.'.'.$f_key, '=', $key.'.id')->execute($this->_db)->count();
		}
		return FALSE;
	}

	// Removes a relationship
	public function remove($key, $id)
	{
		return db::delete($this->_table_name.'_'.inflector::plural($key))->where($key.'_id', '=', $id)->where(inflector::singular($this->_table_name).'_id', '=', $this->_data['id'])->execute($this->_db);
	}

	// Removes all relationships of $key in the join table
	public function remove_all($key)
	{
		if (in_array($key, $this->_has_many))
		{
			return db::delete($this->_table_name.'_'.$key)->where(inflector::singular($this->_table_name).'_id', '=', $this->id)->execute($this->_db);
		}
		else if (in_array($key, $this->_belongs_to))
		{
			return db::delete($key.'_'.$this->_table_name)->where(inflector::singular($this->_table_name).'_id', '=', $this->id)->execute($this->_db);
		}
	}

	// Removes all parent relationships of $key in the join table
	public function remove_parent($key)
	{
		return db::delete($key.'_'.$this->_table_name)->where(inflector::singular($this->_table_name).'_id', '=', $this->id)->execute($this->_db);
	}
}