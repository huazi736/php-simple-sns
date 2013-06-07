<?php
/**
 * 挂件
 * @abstract abstract
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 * @version $Id$
 */

abstract class MY_Widget
{
	protected $_template = '';
	
	public function __construct()
	{
	    //log_message('debug','Widget Class Initialized');
	}
	
	abstract public function render($data);
	
	/**
	 * 获取挂件模板
	 */
	protected function renderFile($templateFile = '',$var = array())
	{
		if(is_array($var) AND count($var))
		{
			foreach($var as $key=>$val)
			{
				$this->assign($key,$val);
			}
		}
		$templateFile = 'widgets' . DS . $templateFile;
		return $this->fetch($templateFile);
	}
	
	/**
	 * 魔术方法,不存在的方法自动调用控制器的对应方法
	 */
	public function __call($name,$params)
	{
		$CI =& get_instance();
		return call_user_func_array(array($CI,$name),$params);
	}
	/**
	 * 魔术方法,不存在的属性自动调用控制器的对应属性
	 */
	public function __get($key)
	{
	    $CI =& get_instance();
	    return $CI->$key;
	}
}