<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 */
class DaoFactory
{	
	public static function factory($dao)
	{
		$path = explode('_',$dao);
		$path = __DIR__ . DS . implode(DS , $path);
		if(include_once($path . '.php')) {
			$class = ucfirst($dao);
			return new $class();
		} else {
			show_error($dao. ' is not exist.');
		}
	}
}