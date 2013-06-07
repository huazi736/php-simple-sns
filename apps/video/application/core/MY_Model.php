<?php
/**
 * +-------------------------------
 * 模型
 * +-------------------------------
 * @author wangying
 * @date <2012/3/2>
 * @version $Id: MY_Model.php
 */

class MY_Model extends DK_Model
{		
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
		$this->init_db('video');
		//$this->init_memcache('video');
	}
}