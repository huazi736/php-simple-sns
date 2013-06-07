<?php
/**
 *@景点--物价机票
 */
 class airticketmodel extends My_model{
 	const TABLE_NAME = 'airlineticket';
 	public $airticket_list_size;
 	function __construct(){
 		parent::__construct();
 		$this->airticket_list_size = 20;
		$this->init_db('interest');
 	}
 	public function add($data) {
		return $this->db->insert(self::TABLE_NAME, $data);
	}
	public function get_insert_id() {
		return $this->db->insert_id();
	}
	/**
	 * 分页查询网页物价机票数据
	 * @param $webid 网页id
	 * @param $limit
	 * @param $offset
	 */
	public function all($webid, $page) {
		$page--;
		if($page<=0) {
			$page = 0;
		}
		$limit_page	= $page * $this->airticket_list_size;
		$this->db->where('web_id', $webid);
		$this->db->order_by('edittime desc');
		$this->db->limit($this->airticket_list_size, $limit_page);
		$list = $this->db->get('airlineticket')->result_array();

		foreach ($list as $airticket) {
			$user = service('User')->getUserInfo($airticket['uid'], 'uid',array('username'));
			$show['id'] = $airticket['id'];
			$show['username'] = $user['username'];
			$show['gocity'] = $airticket['gocity'];
			$show['returntrip'] = $airticket['returntrip'];
			$show['andfromtime'] = $airticket['andfromtime'];
			$show['rate'] = $airticket['rate'];
			$show['travelsigns'] = $airticket['travelsigns'];
			$show['link'] = mk_url('channel/trip/detail_page',array('id'=>$airticket['id'], 'web_id'=>$webid));
			$show['price'] = $airticket['price'];
			$airticket_list[] = $show;
		}

		return isset($airticket_list) ? $airticket_list : array();
	}
 }
?>
