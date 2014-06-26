<?php
/**
* User: Zbigniew 'zibi' Jarosik <zibi@nora.pl>
* Date: 26.06.14 13:58
 */

namespace IFR\Cli;

class Object
{
	public function __construct($array=array())
	{
		foreach($array as $key=>$value)
		{
			if(is_array($value))
			{
				$value = new Object($value);
			}
			$this->$key = $value;
		}
	}
	public function __get($name)
	{
		return isset($this->{$name})?$this->{$name}:null;
	}
}
