<?php
/**
 * +-------------------------------
 * 模型
 * +-------------------------------
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 * @version $Id$
 */

class MY_Model extends DK_Model
{		
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		// log_message('dubug','Model Class Initialized');
		parent::__construct();
		$this->init_db('interest');
		$this->init_redis('user');
        $this->init_memcache('user');
	}

}