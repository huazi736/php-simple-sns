<?php
/*
 * 模型类
 */
class MY_Model extends DK_Model {
	/**
	 * 构造函数
	 */
	public function __construct() {
		parent::__construct();
		$this->load->library("mongo_db", "", "mdb");
		
	}
}