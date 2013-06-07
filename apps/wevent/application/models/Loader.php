<?php
namespace Models;

class Loader
{
	static public $instance;

	public $names = array(
		'Web\\Event\\Domain' => 'domains/',
//		'Web\\Event\\Persists' => 'persists/',
		'Web\\Event' => 'models/',
	);

	private function __construct()
	{
	}

	static public function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function spl_autoload($class)
	{
		if(strpos($class,'\\')!==false)
		{
			$arr = explode('\\',$class);
			require APPPATH.strtolower($arr[0]).'/'.$arr[1].'.php';
		}
	}

	/**
	 * 注册加载器
	 */
	static public function autoload($flag)
	{
		$obj = self::getInstance();

		if ($flag) {
			spl_autoload_register(array($obj, 'spl_autoload'));
		}
		else {
			spl_autoload_unregister(array($obj, 'spl_autoload'));
		}

		//hack 注册原来的加载器
		static $reg = true;
		if ($reg && function_exists('__autoload')) {
			spl_autoload_register('__autoload');
			$regd = false;
		}
	}

}
