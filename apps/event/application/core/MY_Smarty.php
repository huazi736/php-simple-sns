<?php

/**
 * Smarty模板引擎类
 */
class MY_Smarty
{
	private static $_instance = null;
	
	private $tpl = null;

	public static function getInstance($config=array())
	{
		if(!(self::$_instance instanceof self))
		{
			self::$_instance = new self($config);
		}
		return self::$_instance;
	} 

	private function __construct($config=array())
	{
		require_once APPPATH . 'libraries' . DS . 'Smarty' . DS . 'Smarty.class.php';
		$this->tpl = new Smarty();
		if(!is_array($config) or count($config) ==0)
		{
			$this->tpl->compile_dir = APPPATH . 'var' . DS . 'runtime' . DS . 'templates_c' . DS;
			$this->tpl->caching = false;
			$this->tpl->left_delimiter = '<!--{';
			$this->tpl->right_delimiter = '}-->';		
			$this->tpl->template_dir = APPPATH . 'views' . DS;
		}
		else
		{
			foreach($config as $key=>$val)
			{
				$this->tpl->$key = $val;
			}
		}
	}

	public function fetch($templateFile = '', $vars)
	{
	    if(($pos = strrpos($templateFile,'.')) === FALSE)
		{
			$ext = '.html';
		}
		else 
		{
			$ext = '';
		}
								
		$filepath = APPPATH . 'views' . DS . $templateFile . $ext;
		if(!file_exists($filepath))
		{
			show_error('Template file not found:' . $filepath);
		}
		
		$this->tpl->assign($vars);
		return $this->tpl->fetch($filepath);
	}
}
