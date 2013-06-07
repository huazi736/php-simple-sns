<?php
/**
 * 自定义应用异常
 * 
 * @since 2012-04-12 16:28
 * @author vicente
 * @version $Id$
 */
class MY_Exception extends Exception
{	
	
	/**
	 * 重定义构造器使message 变为指定的属性
	 */
	public function __construct($message = null, $code = 0) {
		//确保所有变量都被正确赋值
		parent::__construct($message, $code);
	}
	
	/**
	 * 自定义字符串输出的样式
	 * @return string
	 */
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}