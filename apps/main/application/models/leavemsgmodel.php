<?php
/*
 * 留言模型
 * @author zhoutianliang 
 */
class Leavemsgmodel extends DK_Model {
	private static $TABLE_NAME = 'comments';
	public function __construct() {
		parent::__construct();
		$this->init_db('system');
	}
	public function insert($data,$is_return_id=FALSE) {
		$this->db->set($data);
		if(!$is_return_id){
		    return $this->db->insert(self::$TABLE_NAME);
		}else {
			$this->db->insert(self::$TABLE_NAME);
			return $this->db->insert_id();
		}
	}
	public function del($where) {
		return $this->db->delete(self::$TABLE_NAME,$where);
	}
	public function update($where,$data) {
		$this->db->where($where);
		$this->db->set($data);
		return $this->db->update(self::$TABLE_NAME);
	}
	public function read($where=false,$is_one=FALSE,$limit=false,$offset=false) {
		if($where) {
			$this->db->where($where);
		}
		if($is_one) {
			return $this->db->get(self::$TABLE_NAME)->row_array();
		}
		if($limit && $offset==false) {
			$this->db->limit($limit);
		}
		if($limit && $offset) {
			$this->db->limit($limit,$offset);
		}
		
		return $this->db->order_by('dateline','desc')->get(self::$TABLE_NAME)->result_array();
	}
	public function recentlyContact($field,$limit,$where='') {
		$result =  $this->db->distinct()->select($field)->where($where)->limit($limit)->order_by('dateline','desc')->get(self::$TABLE_NAME)->result_array();

	    return $result;
	}
	public function returnCount($where='') {
		if($where) {
			$this->db->where($where);
		}
		$this->db->from(self::$TABLE_NAME);
	    return	$this->db->count_all_results();
	}
}