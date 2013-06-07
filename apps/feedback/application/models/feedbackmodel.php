<?php
/**
 * 
 * 存用户返馈到数据中
 * @author stang
 *
 */
class FeedbackModel extends DK_Model{
	function __construct(){
		parent::__construct();
		$this->init_db('feedback');
	}
	
	public function f_insert($data =array()){
		if(count($data)<=0){
			return false;
		}
		$ret = $this->db->insert('feedback',$data);
		return $ret;
	}
}