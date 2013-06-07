<?php
/**
 * +-------------------------------
 * 模型
 * +-------------------------------
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 * @version $Id: MY_Model.php 13845 2012-04-01 12:19:42Z weij $
 */

class MY_Model extends DK_Model
{		
	/**
	 * 构造函数
	 */
	public function __construct()
	{	
		parent::__construct();
		$this->init_db('album');
	}
}