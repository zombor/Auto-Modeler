<?php
/**
* AutoModelerORM
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/

class Auto_Modeler_ORM_Core extends Auto_Modeler
{
	protected $_has_many = array();
	protected $_belongs_to = array();

	public function __get($key)
	{
		// See if we are requesting a foreign key
		if (isset($this->_data[$key.'_id']))
			// Get the row from the foreign table
			return db::build()->select('*')->from(inflector::plural($key))->where('id', '=', $this->_data[$key.'_id'])->execute($this->db)->as_object(inflector::singular(ucwords($key)).'_Model')->current();
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
			if ( ! count(db::build()->select('*')->from($this->_table_name.'_'.$key)->where(array(array($f_key, '=', $value), array($this_key, '=', $this->_data['id'])))->execute($this->db)))
			{
				// Insert
				db::insert($this->_table_name.'_'.$key, array($f_key => $value, $this_key => $this->_data['id']))->execute($this->db);
			}
		}
		else if (in_array($key, $this->_belongs_to))
		{
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($key).'_id';
			// See if this is already in the join table
			if ( ! count(db::build()->from($key.'_'.$this->_table_name)->where(array(array($this_key, '=', $value), array($f_key, '=', $this->_data['id'])))->execute($this->db)))
			{
				// Insert
				db::insert($key.'_'.$this->_table_name, array($f_key => $value, $this_key => $this->_data['id']))->execute($this->db);
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
				if ( ! count(db::build()->from($this->_table_name.'_'.$key)->where(array(array($f_key, '=', $value), array($this_key, '=', $this->_data['id'])))->execute($this->db)))
				{
					// Insert
					db::insert($this->_table_name.'_'.$key, array($f_key => $value, $this_key => $this->_data['id']))->execute($this->db);
				}
		}
	}

	public function find_related($key, $where = array(), $order_by = 'id', $order = 'ASC')
	{
		$model = inflector::singular($key).'_Model';

		$temp = new $model();
		if ($temp->has_attribute(inflector::singular($this->_table_name).'_id')) // Look for a one to many relationship
			return db::build()->from($key)->order_by($order_by, $order)->where($where + array(array(inflector::singular($this->_table_name).'_id', '=', $this->_data['id'])))->execute($this->db)->as_object(inflector::singular(ucwords($key)).'_Model');
		else // Get a many to many relationship
		{
			$join_table = $this->_table_name.'_'.$key;
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($key).'_id';

			return db::build()->select($key.'.*')->from($key)->where($where + array(array($join_table.'.'.$this_key, '=', $this->_data['id'])))->join($join_table, $join_table.'.'.$f_key, $key.'.id')->execute($this->db)->as_object(inflector::singular(ucwords($key)).'_Model');
		}
	}

	public function find_parent($key = NULL, $where = array(), $order_by = 'id', $order = 'ASC')
	{
		if ($this->has_attribute($key.'_id')) // Look for a one to many relationship
		{
			return db::build()->from(inflector::plural($this->_table_name))->where($where + array(array('id', '=', $this->_data[$key.'_id'])))->execute($this->db)->as_object(ucwords($this->_table_name).'_Model');
		}
		else
		{
			$join_table = $key.'_'.$this->_table_name;
			$f_key = inflector::singular($this->_table_name).'_id';
			$this_key = inflector::singular($key).'_id';

			return db::build()->select($key.'.*')->from($key)->order_by($order_by, $order)->where($where + array(array($join_table.'.'.$f_key, '=', $this->_data['id'])))->join($join_table, $join_table.'.'.$this_key, $key.'.id')->execute($this->db)->as_object(inflector::singular(ucwords($key)).'_Model');
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
			return (bool) db::build()->select($key.'.id')->from($key)->where(array(array($join_table.'.'.$this_key, '=', $this->_data['id']), array($join_table.'.'.$f_key, '=', $value)))->join($join_table, $join_table.'.'.$f_key, $key.'.id')->execute($this->db)->count();
		}
		return FALSE;
	}

	// Removes a relationship
	public function remove($key, $id)
	{
		return db::delete($this->_table_name.'_'.inflector::plural($key), array(array($key.'_id', '=', $id), array(inflector::singular($this->_table_name).'_id', '=', $this->_data['id'])))->execute($this->db);
	}

	// Removes all relationships of $key in the join table
	public function remove_all($key)
	{
		if (in_array($key, $this->_has_many))
		{
			return db::delete($this->_table_name.'_'.$key, array(array(inflector::singular($this->_table_name).'_id', '=', $this->id)))->execute($this->db);
		}
		else if (in_array($key, $this->_belongs_to))
		{
			return db::delete($key.'_'.$this->_table_name, array(array(inflector::singular($this->_table_name).'_id', '=', $this->id)))->execute($this->db);
		}
	}

	// Removes all parent relationships of $key in the join table
	public function remove_parent($key)
	{
		return db::delete($key.'_'.$this->_table_name, array(array(inflector::singular($this->_table_name).'_id', '=', $this->id)))->execute($this->db);
	}
}