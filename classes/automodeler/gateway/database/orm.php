<?php

class AutoModeler_Gateway_Database_ORM extends AutoModeler_Gateway_Database
{
	// Should this do 1:n as well as n:n?
	public function find_related(
		$key,
		AutoModeler_Model $source_model,
		Database_Query_Builder_Select $qb = NULL
	)
	{
		$model = 'Model_'.inflector::singular($key);
		$temp = new $model();

		if ( ! $query)
		{
			$qb = db::select_array($temp->fields());
		}

		$related_table = $temp->gateway()->table_name();
		$join_table = $this->_table_name.'_'.$related_table;
		$this_key = inflector::singular($this->_table_name).'_id';
		$f_key = inflector_singular($related_table).'_id';

		$query = $qb->from($related_table)
			->join($join_table)
			->on($join_table.'.'.$f_key, '=', $related_table.'.id')
			->where($join_table.'.'.$this_key, '=', $source_model->id)
		return $temp->gateway()->load_set($query)
	}

	// Should this do 1:n as well as n:n?
	public function find_parent($related, AutoModeler_Model $source_model)
	{

	}
}
