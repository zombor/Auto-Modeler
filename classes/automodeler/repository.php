<?php

class AutoModeler_Repository
{
	protected static $_repositories = array();

	public static function add($name, $repository)
	{
		static::$_repositories[$name] = $repository;
	}

	public static function fetch($name)
	{
		return static::$_repositories[$name];
	}
}
