<?php
/**
* AutoModelerORM
*
* @package        AutoModeler
* @author         Jeremy Bush
* @copyright      (c) 2010 Jeremy Bush
* @license        http://www.opensource.org/licenses/isc-license.txt
*/

class AutoModeler_ORM_Core extends AutoModeler
{
	protected $_has_many = array();
	protected $_belongs_to = array();

	protected $_load_with = NULL;

	// Model data to lazy load
	protected $_lazy = array();

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
		{
			if (isset($this->_lazy[$key])) // See if we've lazy loaded it
			{
				$model = AutoModeler::factory($key);
				$model->process_load($this->_lazy[$key]);
				$model->process_load_state();
				return $model;
			}

			// Get the row from the foreign table
			return AutoModeler::factory($key, $this->_data[$key.'_id']);
		}
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
		elseif (strpos($key, ':')) // Process with
		{
			list($table, $field) = explode(':', $key);
			if ($table == $this->_table_name)
			{
				parent::__set($key, $value);
			}
			elseif ($field)
			{
				$this->_lazy[inflector::singular($table)][$field] = $value;
			}
		}
		else
		{
			parent::__set($key, $value);
		}
	}

	/**
	 * Processes a load() from a result, overloaded for with() support
	 *
	 * @return null
	 */
	protected function process_load($data)
	{
		$parsed_data = array();
		foreach ($data as $key => $value)
		{
			if (strpos($key, ':'))
			{
				list($table, $field) = explode(':', $key);
				if ($table == $this->_table_name)
				{
					$parsed_data[$field] = $value;
				}
				elseif ($field)
				{
					$this->_lazy[inflector::singular($table)][$field] = $value;
				}
			}
			else
			{
				$parsed_data[$key] = $value;
			}
		}
		$this->_data = $parsed_data;
	}

	/**
	 * Loads a model with a different one
	 *
	 * @return $this
	 */
	public function with($model)
	{
		$this->_load_with = $model;
		return $this;
	}

	/**
	 * Overload load() to use with()
	 *
	 * @return $this when loading one object
	 * @return Database_MySQL_Result when loading multiple results
	 */
	public function load(Database_Query_Builder_Select $query = NULL, $limit = 1)
	{
		if ($query == NULL)
		{
			$query = db::select_array(array_keys($this->_data));
		}

		if ($this->_load_with !== NULL)
		{
			if (is_array($this->_load_with))
			{
				$model = current(array_keys($this->_load_with));
				$alias = current(array_values($this->_load_with));
			}
			else
			{
				$model = $this->_load_with;
				$alias = $this->_load_with;
			}

			$fields = array();
			foreach ($this->fields() as $field)
			{
				$fields[] = array($field, str_replace($this->_table_name.'.', '', $field));
			}
			foreach (AutoModeler_ORM::factory($model)->fields() as $field)
			{
				$fields[] = array($field, str_replace('.', ':', $field));
			}

			$query->select_array($fields, TRUE);
			$join_model = Model::factory($model);
			$join_table = $join_model->get_table_name();
			$query->join($join_table)->on($join_table.'.id', '=', $this->_table_name.'.'.$alias.'_id');
		}

		return parent::load($query, $limit);
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
	 * Finds relations of a has_many relationship
	 * 
	 * 	// Finds all roles belonging to a user
	 * 	$user->find_related('roles');
	 *
	 * @param string                        $key   the model name to look for
	 * @param Database_Query_Builder_Select $query A select object to filter results with
	 *
	 * @return Database_Result
	 */
	public function find_related($key, Database_Query_Builder_Select $query = NULL)
	{
		$model = 'Model_'.inflector::singular($key);

		$temp = new $model();
		if ( ! $query)
		{
			$query = db::select_array($temp->fields());
		}

		if ($temp->field_exists(inflector::singular($this->_table_name).'_id')) // Look for a one to many relationship
		{
			return $temp->load($query->where(inflector::singular($this->_table_name).'_id', '=', $this->id), NULL);
		}
		elseif (in_array($key, $this->_has_many)) // Get a many to many relationship.
		{
			$related_table = AutoModeler::factory(inflector::singular($key))->get_table_name();
			$join_table = $this->_table_name.'_'.$related_table;
			$this_key = inflector::singular($this->_table_name).'_id';
			$f_key = inflector::singular($related_table).'_id';

			$columns = AutoModeler::factory(inflector::singular($key))->fields();

			$query = $query->from($related_table)->join($join_table)->on($join_table.'.'.$f_key, '=', $related_table.'.id');
			$query->where($join_table.'.'.$this_key, '=', $this->_data['id']);
			return $temp->load($query, NULL);
		}
		else
		{
			throw new AutoModeler_Exception('Relationship "'.$key.'" doesn\'t exist in '.get_class($this));
		}
	}

	/**
	 * Finds parents of a belongs_to model
	 * 
	 * 	// Finds all users related to a role
	 * 	$role->find_parent('users');
	 *
	 * @param string                        $key   the model name to look for
	 * @param Database_Query_Builder_Select $query A select object to filter results with
	 *
	 * @return Database_Result
	 */
	public function find_parent($key, Database_Query_Builder_Select $query = NULL)
	{
		$parent = AutoModeler::factory(inflector::singular($key));
		$columns = $parent->fields();

		if ( ! $query)
		{
			$query = db::select_array($parent->fields());
		}

		if ($this->field_exists($key.'_id')) // Look for a one to many relationship
		{
			return $parent->load($query->where('id', '=', $this->_data[$key.'_id']), NULL);
		}
		elseif(in_array($key, $this->_belongs_to)) // Get a many to many relationship.
		{
			$related_table = $parent->get_table_name();
			$join_table = $related_table.'_'.$this->_table_name;
			$f_key = inflector::singular($this->_table_name).'_id';
			$this_key = inflector::singular($related_table).'_id';

			$columns = AutoModeler::factory(inflector::singular($key))->fields();

			$query = $query->join($join_table)->on($join_table.'.'.$this_key, '=', $related_table.'.id')->from($related_table)->where($join_table.'.'.$f_key, '=', $this->_data['id']);
			return $parent->load($query, NULL);
		}
		else
		{
			throw new AutoModeler_Exception('Relationship "'.$key.'" doesn\'t exist in '.get_class($this));
		}
	}

	/**
	 * Tests if a many to many relationship exists
	 * 
	 * Model must have a _has_many relationship with the other model, which is
	 * passed as the first parameter in plural form without the Model_ prefix.
	 * 
	 * The second parameter is the id of the related model to test the relationship of.
	 * 
	 * 	$user = new Model_User(1);
	 * 	$user->has('roles', Model_Role::LOGIN);
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
	 * Removes a has_many relationship if you aren't using innoDB (shame on you!)
	 * 
	 * Model must have a _has_many relationship with the other model, which is
	 * passed as the first parameter in plural form without the Model_ prefix.
	 * 
	 * The second parameter is the id of the related model to remove.
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
	 * Model must have a _has_many or _belongs_to relationship with the other model, which is
	 * passed as the first parameter in plural form without the Model_ prefix.
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
	 * @param string $key the model name to look for in plural form, without Model_ prefix
	 *
	 * @return integer
	 */
	public function remove_parent($key)
	{
		return db::delete(AutoModeler::factory(inflector::singular($key))->get_table_name().'_'.$this->_table_name)->where(inflector::singular($this->_table_name).'_id', '=', $this->id)->execute($this->_db);
	}
}