<?php

/**
 * Catering groupon model
 * @author dequan.she
 */
class Catering_groupon_model extends MY_Model {
	
	const TABLE_NAME = 'catering_groupon';
	public $groupon_list_size;
	
	public function __construct() {
		parent::__construct();
		$this->groupon_list_size = 20;
		$this->init_db('interest');
	}
	
	public function add($data) {
		return $this->db->insert(self::TABLE_NAME, $data);
	}
	
	public function set($id, $data) {
		return $this->db->where('id', $id)->update(self::TABLE_NAME, $data);
	}
	
	public function remove($id) {
		return $this->db->where('id', $id)->delete(self::TABLE_NAME);
	}   
	
	public function get($id) {
		return $this->db->get_where(self::TABLE_NAME, array(
				'id' => $id 
		))->row_array();
	}
	
	/**
	 * 分页查询网页促销活动数据
	 * @param $webid 网页id
	 * @param $limit 
	 * @param $offset
	 */
	public function all($webid, $page) {
		$page--;
		if($page<=0) {
			$page = 0;
		}
		$limit_page	= $page * $this->groupon_list_size;
		$this->db->where('web_id', $webid);
		$this->db->order_by('utime desc');
		$this->db->limit($this->groupon_list_size, $limit_page);
		$list = $this->db->get('catering_groupon')->result_array();
		
		foreach ($list as $groupon) {
			$user = service('User')->getUserInfo($groupon['uid'], 'uid',array('username'));
			$diff = $groupon['etime'] - time();
			$on['id'] = $groupon['id'];
			$on['uid'] = $groupon['uid'];
			$on['web_id'] = $groupon['web_id'];
			$on['username'] = $user['username'];
			$on['title'] = $groupon['title'];
			$on['link'] = mk_url('channel/catering_groupon/detail_page',array('id'=>$groupon['id'], 'web_id'=>$webid));
			$on['original_price'] = $groupon['original_price'];
			$on['current_price'] = $groupon['current_price'];
			$on['spare_price'] = sprintf('%.0f', $groupon['original_price'] - $groupon['current_price']);
			$on['discount'] = sprintf('%.1f', ($groupon['current_price'] / $groupon['original_price']) * 10);
			$pics = json_decode($groupon['img'], true);
			if(is_array($pics)) {
				foreach($pics as $_k=>$_v) {
					$pics[$_k]['b']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['b']['url'];
				}
			} else {
				$pics[0]['b']['url'] = '';
			}
			$on['img'] = $pics;
			$on['description'] = $groupon['description'];
			$on['diff'] = $diff > 0 ? $diff : 0;
			$on['etime'] = date('Y-m-d H:i:s', $groupon['etime']);
			$on['ctime'] = $groupon['ctime'];
			$on['utime'] = date('Y-m-d H:i:s', $groupon['utime']);
			$groupon_list[] = $on;
		}
		
		return isset($groupon_list) ? $groupon_list : array();
	}
	
	public function get_insert_id() {
		return $this->db->insert_id();
	}
}