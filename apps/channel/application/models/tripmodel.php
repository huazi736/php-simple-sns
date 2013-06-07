<?php
/**
 *@author wangh
 *@date    2012-07-24
 *@旅游景点
 */
 class tripmodel extends My_model{
 	const TABLE_NAME = 'travel';
 	public $trip_list_size;
 	function __construct(){
 		parent::__construct();
 		$this->trip_list_size = 20;
		$this->init_db('interest');
 	}
 	public function add($data) {
		return $this->db->insert(self::TABLE_NAME, $data);
	}
	public function get_insert_id() {
		return $this->db->insert_id();
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
		$limit_page	= $page * $this->trip_list_size;
		$this->db->where('web_id', $webid);
		$this->db->order_by('edittime desc');
		$this->db->limit($this->trip_list_size, $limit_page);
		$list = $this->db->get('travel')->result_array();

		foreach ($list as $travel) {
			$user = service('User')->getUserInfo($travel['uid'], 'uid',array('username'));
			$show['id'] = $travel['id'];
			$show['username'] = $user['username'];
			$show['description'] = $travel['description'];
			$show['link'] = mk_url('channel/trip/detail_page',array('id'=>$travel['id'], 'web_id'=>$webid));
			$show['price'] = $travel['price'];
			$pics = json_decode($travel['pics'], true);
			if(is_array($pics)) {
				foreach($pics as $_k=>$_v) {
					$pics[$_k]['b']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['b']['url'];
				}
			} else {
				$pics[0]['b']['url'] = '';
			}
			$show['pics'] = $pics;
			$travel_list[] = $show;
		}

		return isset($travel_list) ? $travel_list : array();
	}
 }
?>
