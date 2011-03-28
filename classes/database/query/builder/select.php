<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database query builder for SELECT statements. See [Query Builder](/database/query/builder) for usage and examples.
 *
 * @package    Kohana/Database
 * @category   Query
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Database_Query_Builder_Select extends Kohana_Database_Query_Builder_Select
{
	/**
	 * Choose the columns to select from.
	 *
	 * @param   mixed  column name or array($column, $alias) or object
	 * @param   ...
	 * @return  $this
	 */
	public function select($columns = NULL, $reset = FALSE)
	{
		if ($reset)
		{
			$this->_select = array();
		}

		$columns = func_get_args();

		$this->_select = array_merge($this->_select, $columns);

		return $this;
	}

	/**
	 * Choose the columns to select from, using an array.
	 *
	 * @param   array  list of column names or aliases
	 * @return  $this
	 */
	public function select_array(array $columns = NULL, $reset = FALSE)
	{
		if ($reset)
		{
			$this->_select = array();
		}

		$this->_select = array_merge($this->_select, $columns);

		return $this;
	}
}