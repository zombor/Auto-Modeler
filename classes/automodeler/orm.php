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

	/**
	 * Magic get method, can obtain one to many relationships
	 *
	 * @param String $key the key to __get (singular)
	 *
	 * @return mixed
	 */
	public function __get($key)
	{
		// See if we are requesting a foreign key
		if (isset($this->_data[$key.'_id']))
			// Get the row from the foreign table
			return db::select_array(AutoModeler::factory($key)->fields())->from(AutoModeler::factory($key)->get_table_name())->where('id', '=', $this->_data[$key.'_id'])->as_object('Model_'.ucwords($key))->execute($this->_db)->current();
		else if (isset($this->_data[$key]))
			return $this->_data[$key];
	}

	/**
	 * Magic set method, can set many to many relationships
	 *
	 * @param string $key the key to set
	 * @param mixed  $value the value to set the key to
	 *
	 * @return none
	 */
	public function __set($key, $value)
	{
		if (in_array($key, $this->_has_many))
		{
			$related_table = AutoModeler::factory(inflector::singular($key))->get_table_name();
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($related_table).'_id';

			// See if this is already in the join table
			if ( ! count(db::select('*')->from($this->_table_name.'_'.$related_table)->where($f_key, '=', $value)->where($this_key, '=', $this->_data['id'])->execute($this->_db)))
			{
				// Insert
				db::insert($this->_table_name.'_'.$related_table)->columns(array($f_key, $this_key))->values(array($value, $this->_data['id']))->execute($this->_db);
			}
		}
		else if (in_array($key, $this->_belongs_to))
		{
			$related_table = AutoModeler::factory(inflector::singular($key))->get_table_name();
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($related_table).'_id';
			// See if this is already in the join table
			if ( ! count(db::select('*')->from($related_table.'_'.$this->_table_name)->where($this_key, '=', $value)->where($f_key, '=', $this->_data['id'])->execute($this->_db)))
			{
				// Insert
				db::insert($related_table.'_'.$this->_table_name, array($f_key => $value, $this_key => $this->_data['id']))->execute($this->_db);
			}
		}
		else
			parent::__set($key, $value);
	}

	/**
	 * Performs mass relations
	 *
	 * @param string $key    the key to set
	 * @param array  $values an array of values to relate the model with
	 *
	 * @return none
	 */
	public function relate($key, array $values)
	{
		if (in_array($key, $this->_has_many))
		{
			$related_table = AutoModeler::factory(inflector::singular($key))->get_table_name();
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($related_table).'_id';
			foreach ($values as $value)
				// See if this is already in the join table
				if ( ! count(db::select('*')->from($this->_table_name.'_'.$related_table)->where($f_key, '=', $value)->where($this_key, '=', $this->_data['id'])->execute($this->_db)))
				{
					// Insert
					db::insert($this->_table_name.'_'.$related_table)->columns(array($f_key, $this_key))->values(array($value, $this->_data['id']))->execute($this->_db);
				}
		}
	}

	/**
	 * Finds many to many relationships
	 *
	 * @param string $key      the model name to look for
	 * @param array  $where    an array of where clauses to apply to the search
	 * @param array  $order_by the column to order by
	 * @param array  $order    the direction to order
	 *
	 * @return Database_Result
	 */
	public function find_related($key, $where = array(), $order_by = 'id', $order = 'ASC')
	{
		$model = 'Model_'.inflector::singular($key);

		$temp = new $model();
		if ($temp->field_exists(inflector::singular($this->_table_name).'_id')) // Look for a one to many relationship
		{
			$query = db::select_array(AutoModeler::factory(inflector::singular($key))->fields())->from($temp->get_table_name())->order_by($order_by, $order);
			$query->where(inflector::singular($this->_table_name).'_id', '=', $this->_data['id']);
			foreach ($where as $sub_where)
				$query->where($sub_where[0], $sub_where[1], $sub_where[2]);
	
			return $query->as_object('Model_'.ucwords(inflector::singular($key)))->execute($this->_db);
		}
		else // Get a many to many relationship
		{
			$related_table = AutoModeler::factory(inflector::singular($key))->get_table_name();
			$join_table = $this->_table_name.'_'.$related_table;
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($related_table).'_id';

			$columns = array();
			foreach (AutoModeler::factory(inflector::singular($key))->fields() as $field)
			{
				$columns[] = array($related_table.'.'.$field, $field);
			}

			$query = db::select_array($columns)->from($related_table)->join($join_table)->on($join_table.'.'.$f_key, '=', $related_table.'.id')->order_by($order_by, $order);
			$query->where($join_table.'.'.$this_key, '=', $this->_data['id']);
			foreach ($where as $sub_where)
				$query->where($sub_where[0], $sub_where[1], $sub_where[2]);
			return $query->as_object('Model_'.ucwords(inflector::singular($key)))->execute($this->_db);
		}
	}

	/**
	 * Finds parents of a belongs_to model
	 *
	 * @param string $key      the model name to look for
	 * @param array  $where    an array of where clauses to apply to the search
	 * @param array  $order_by the column to order by
	 * @param array  $order    the direction to order
	 *
	 * @return Database_Result
	 */
	public function find_parent($key, $where = array(), $order_by = 'id', $order = 'ASC')
	{
		if ($this->field_exists($key.'_id')) // Look for a one to many relationship
		{
			$query = db::select()->from($this->_table_name);
			$query->where('id', '=', $this->_data[$key.'_id']);
			foreach ($where as $sub_where)
				$query->where($sub_where[0], $sub_where[1], $sub_where[2]);
			return $query->as_object('Model_'.ucwords($this->_table_name))->execute($this->_db);
		}
		else
		{
			$related_table = AutoModeler::factory(inflector::singular($key))->get_table_name();
			$join_table = $related_table.'_'.$this->_table_name;
			$f_key = inflector::singular($this->_table_name).'_id';
			$this_key = inflector::singular($related_table).'_id';

			$columns = array();
			foreach (AutoModeler::factory(inflector::singular($key))->fields() as $field)
			{
				$columns[] = array($related_table.'.'.$field, $field);
			}

			$query = db::select_array($columns)->from($related_table)->order_by($order_by, $order)->where($join_table.'.'.$f_key, '=', $this->_data['id']);
			foreach ($where as $sub_where)
				$query->where($sub_where[0], $sub_where[1], $sub_where[2]);
			return $query->join($join_table)->on($join_table.'.'.$this_key, '=', $key.'.id')->as_object('Model_'.ucwords(inflector::singular($key)))->execute($this->_db);
		}
	}

	/**
	 * Tests if a many to many relationship exists
	 *
	 * @param string $key   the model name to look for (plural)
	 * @param string $value an id to search for
	 *
	 * @return bool
	 */
	public function has($key, $value)
	{
		$related_table = AutoModeler::factory(inflector::singular($key))->get_table_name();
		$join_table = $this->_table_name.'_'.$related_table;
		$f_key = inflector::singular($related_table).'_id';
		$this_key = inflector::singular($this->_table_name).'_id';

		if (in_array($key, $this->_has_many))
		{
			return (bool) db::select($related_table.'.id')->
			                  from(AutoModeler::factory(inflector::singular($key))->get_table_name())->
			                  where($join_table.'.'.$this_key, '=', $this->_data['id'])->
			                  where($join_table.'.'.$f_key, '=', $value)->
			                  join($join_table)->on($join_table.'.'.$f_key, '=', $related_table.'.id')->
			                  execute($this->_db)->count();
		}
		return FALSE;
	}

	/**
	 * Removes a relationship if you aren't using innoDB (shame on you!)
	 *
	 * @param string $key the model name to look for
	 * @param string $id  an id to search for
	 *
	 * @return integer
	 */
	public function remove($key, $id)
	{
		return db::delete($this->_table_name.'_'.AutoModeler::factory(inflector::singular($key))->get_table_name())->where($key.'_id', '=', $id)->where(inflector::singular($this->_table_name).'_id', '=', $this->_data['id'])->execute($this->_db);
	}

	/**
	 * Removes all relationships of a model
	 *
	 * @param string $key the model name to look for
	 *
	 * @return integer
	 */
	public function remove_all($key)
	{
		if (in_array($key, $this->_has_many))
		{
			return db::delete($this->_table_name.'_'.AutoModeler::factory(inflector::singular($key))->get_table_name())->where(inflector::singular($this->_table_name).'_id', '=', $this->id)->execute($this->_db);
		}
		else if (in_array($key, $this->_belongs_to))
		{
			return db::delete(AutoModeler::factory(inflector::singular($key))->get_table_name().'_'.$this->_table_name)->where(inflector::singular($this->_table_name).'_id', '=', $this->id)->execute($this->_db);
		}
	}

	/**
	 * Removes a parent relationship of a belongs_to
	 *
	 * @param string $key the model name to look for
	 *
	 * @return integer
	 */
	public function remove_parent($key)
	{
		return db::delete(AutoModeler::factory(inflector::singular($key))->get_table_name().'_'.$this->_table_name)->where(inflector::singular($this->_table_name).'_id', '=', $this->id)->execute($this->_db);
	}
}