<?php
/**
 * @desc    对版本的操作
 * @author  sunlufu
 * @date    2012-04-26
 * @version v1.2.001
 */
require(APPPATH . 'libraries' . DS . 'Mongo_db' . EXT);
class WordModel extends MY_Model {
	public $mongo_db;
	function __construct(){
		//$this->load->library('Monge_db', '', 'mdb');
		$this->mongo_db = new Mongo_db();
	}
}