<?php

namespace IFR\Cli;

class Config
{
	public static $config = null;
	private static $file = null;

	public function __construct($array = array())
	{
		if(count($array))
		{
			self::$config = self::object($array);
		}
	}

	public static function get($path = null)
	{
		$config = self::$config;

		if(!is_null($path))
		{
			$path = trim($path, '/');

			$chunks = explode('/', $path);

			foreach($chunks as $chunk)
			{
				if(isset($config->{$chunk}))
				{
					$config = $config->{$chunk};
				}
				else
				{
					throw new \Exception("Config::/{$path} not found");
				}
			}
		}

		return $config;
	}

	public static function load($file = null, $stage = 'production')
	{
		if(is_null($file))
		{
			$file = dirname($_SERVER['PHP_SELF']) . '/config/config.ini';
		}

		self::$file = $file;

		$config = parse_ini_file(self::$file, true);

		if(is_file(self::$file . '.local'))
		{
			$config_local = parse_ini_file(self::$file . '.local', true);
			$config = array_replace_recursive($config, $config_local);
		}

		$config = self::nesting($config);

		self::$config = self::object($config[$stage]);

		return self::$config;
	}
	static function nesting($array = array())
	{
		$nested = false;

		foreach($array as $key=>$value)
		{
			if(strpos($key,'.')!==false)
			{
				$nested = true;

				$path = explode('.',$key);
				$root = array_shift($path);
				$path = join('.',$path);
				$array[$root][$path] = $value;

				unset($array[$key]);
			}
		}

		foreach($array as $key=>$value)
		{
			if(is_array($value))
			{
				$array[$key] = self::nesting($array[$key]);;
			}
		}

		if($nested)
		{
			$array = self::nesting($array);
		}

		return $array;
	}

	static function object($array = array())
	{
		$numeric = self::is_allKeysNumeric($array);

		$obj = $numeric?array():new Config(null);

		foreach($array as $key => $value)
		{
			if(is_array($value))
			{
				$value = self::object($value);
			}

			if($numeric)
			{
				$obj[$key] = $value;
			}
			else
			{
				$obj->$key = $value;
			}
		}

		return $obj;
	}

	public function __get($name)
	{
		return isset(self::$config->{$name}) ? self::$config->{$name} : null;
	}

	public function configGet($path)
	{
		return self::get($path);
	}

	static function is_allKeysNumeric($array)
	{
		$ret = true;

		foreach($array as $key=>$value)
		{
			if(!is_numeric($key))
			{
				$ret = false;
			}
		}

		return $ret;
	}
}
