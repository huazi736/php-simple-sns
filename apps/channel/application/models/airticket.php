<?php
/**
 *@景点--物价机票
 */
 class airticket extends My_model{
 	const TABLE_NAME = 'airlineticket';
 	public $groupon_list_size;
 	function __construct(){
 		parent::__construct();
 		$this->groupon_list_size = 20;
		$this->init_db('interest');
 	}
 	public function add($data) {
		return $this->db->insert(self::TABLE_NAME, $data);
	}
	public function get_insert_id() {
		return $this->db->insert_id();
	}
 }
?>
