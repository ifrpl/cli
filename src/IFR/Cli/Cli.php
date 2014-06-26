<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zibi
 * Date: 14.08.13
 * Time: 10:27
 * To change this template use File | Settings | File Templates.
 */

namespace IFR\Cli;

use IFR\Cli\Object;

class Cli
{
	public $param = null;
	public $flag = null;
	public $input = null;

	public function __construct()
	{
		$this->param = new Object();
		$this->flag = new Object();
		$this->input = new Object();
	}
	/**
	 * @param array $argv
	 * @return Cli
	 */
	static function parse(array $inputs = array())
	{
		$cli = new Cli();

		if(self::isCli())
		{
			$cli = self::parseArgv($cli,$inputs);
		}
		else
		{
			$cli = self::parseRequest($cli,$inputs);
		}

		return $cli;
	}

	/**
	 * @param Cli $cli
	 * @param array $inputs
	 * @return Cli
	 */
	static function  parseArgv(Cli $cli, array $inputs)
	{
		$argv = $_SERVER['argv'];

		$inputCounter=0;

		foreach($argv as $arg)
		{
			// named param
			if(substr($arg,0,2)==='--')
			{
				$value = preg_split( '/[= ]/', $arg, 2 );
				$param = substr( array_shift($value), 2 );
				$value = join('',$value);

				$cli->param->{$param} = (!empty($value) || $value==='0') ? $value : true;
				continue;
			}
			// flag
			if(substr($arg,0,1)==='-')
			{
				for($i=1;isset($arg[$i]);$i++)
				{
					$flag = substr($arg,$i,1);
					if($flag!=='-')
					{
						$cli->flag->{$flag}=(substr($arg,$i+1,1)=='-')?false:true;
					}
				}
				continue;
			}
			if(substr($arg,0,1)==='+')
			{
				$flag = substr($arg,1,1);
				$cli->flag->{$flag}=true;
				continue;
			}

			if(count($inputs)&&($inputCounter>0)) // we do not want script name as command ;P
			{
				$cli->input->{array_shift($inputs)}=$arg;
			}
			$cli->input->{$inputCounter}=$arg;
			$inputCounter++;
		}

		return $cli;
	}

	/**
	 * @param Cli $cli
	 * @param array $inputs
	 * @return Cli
	 */
	static function parseRequest(Cli $cli, array $inputs)
	{
		$request = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:(isset($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:$_SERVER["REQUEST_URI"]);
		$request .= isset($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:'';

		$params=array();

		if(strpos($request,'?',0)!==false)
		{
			list($path,$paramsStr)=explode('?',$request);
		}
		else
		{
			$path = $request;
			$paramsStr='';
		}
		$path = explode('/',trim($path,'/'));

		$inputCounter = 0;

		while($node = array_shift($path))
		{
			if($node!='')
			{
				$cli->input->{$inputCounter++} = $node;
				if($name = array_shift($inputs))
				{
					$cli->param->{$name} = $node;
					$cli->input->{$name} = $node;
				}
				else
				{
					$value = array_shift($path);
					$cli->param->{$node} = $value;
					$cli->input->{$inputCounter++} = $value;
				}
			}
		}

		if($paramsStr!='')
		{
			$paramsArray = explode('&',$paramsStr);

			foreach($paramsArray as $param)
			{
				if(strpos($request,'=',0)!==false)
				{
					list($key,$value) = explode('=',$param);
				}
				else
				{
					$key = $param;
					$value = true;
				}

				$cli->param->{$key} = $value;
			}
		}

		foreach($_POST as $key=>$value)
		{
			$cli->param->{$key} = $value;
		}
		foreach($_GET as $key=>$value)
		{
			$cli->param->{$key} = $value;
		}
		foreach($params as $key=>$value)
		{
			if(str_contains($key,'['))
			{
				unset($cli->param->{$key});
			}
		}

//        $params = requestFilter($params);

		return $cli;
	}
	static function isCli()
	{
		if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function __get($name)
	{
		return isset(self::$config->{$name}) ? self::$config->{$name} : null;
	}
}
