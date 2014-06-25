<?php

namespace IFR\Cli;

class Config
{
	public static $config = null;
	private static $file = null;

	public function __construct($array=array())
	{
		if(count($array))
		{
			self::$config = self::object($array);
		}
	}
	public static function get($path=null)
	{
		$config = self::$config;

		if(!is_null($path))
		{
			$path = trim($path,'/');

			$chunks = explode('/',$path);

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
	public static function load($file = null,$stage = 'production')
	{
		if(is_null($file))
		{
			$file = dirname($_SERVER['PHP_SELF']).'/config/config.ini';
		}

		self::$file = $file;

		    $config = parse_ini_file(self::$file, true);

			if(is_file(self::$file.'.local')) 
		    {
			$config_local = parse_ini_file(self::$file.'.local', true);
			$config = array_replace_recursive($config, $config_local);
		    }
		
		self::$config = self::object($config[$stage]);
	}
	static function object($array = array())
	{
		$obj = new Config(null);

		foreach($array as $key=>$value)
		{
			if(is_array($value))
			{
				$value = self::object($value);
			}
			$obj->$key = $value;
		}

		return $obj;
	}
	public function __get($name)
	{
		return isset(self::$config->{$name})?self::$config->{$name}:null;
	}

    public function configGet($path)
    {
        return self::get($path);
    }
}