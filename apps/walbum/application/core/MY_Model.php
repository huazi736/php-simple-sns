<?php
/**
 * 模型
 * 
 * @author vicente
 * @date <2012/07/06>
 * @version $Id
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