<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-6-13
 * @author qianc
 * discription : 投放对象model
 */

class Adcompanymodel extends MY_Model {

    function __construct() {
        parent::__construct();
    }

	/**
	 * 	添加内容
	 *  @author	    qianc
	 * 	@date	    2012/6/27
	 * 	@param $table	新建数据所在表
	 * 	@param $data		新建的数据
	 *
	 * @return		最后插入数据的id
	 */
	public function newData($table = NULL, $data = NULL) {
		if(!$data or !$table ) {
			return FALSE;
		}
		$res = $this->db->insert($table,$data);
		if($res){
			return $this->db->insert_id();
		}else{
			return FALSE;
		}
	}
	
	
	/**
	 * 检测广告商uid是否存在
	 *
	 *  @author	    qianc
	 * 	@date	    2012/6/27
	 * 	@param $table	新建数据所在表
	 * 	@param $data		
	 * @return true / false
	 */
	public function checkName($table = '', $data = array()){
		if(empty($table) || empty($data) || 0 == sizeof($data)){
			return false;
		}

		$this->db->where($data);
		$result = $this->db->from($table)->count_all_results();

		if(0 < $result){
			return false;
		}

		return true;
	}
		
	/**
	 * 	取得广告商详情
	 *  @author	    qianc
	 * 	@date	    2012/7/4
	 * @return		array
	 */
	function getCompanyInfo($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT * FROM  ".AD_COMPANY." WHERE ".$where;
    	$data = $this->db->query($sql)->result_array(); 
    	return $data; 

	} 
	
	
	/**
	 * 	编辑广告商
	 *  @author	    qianc
	 * 	@date	    2012/7/4
	 *  @return		boolean
	 */
	public function editCompany( $data = NULL, $where = NULL) {
		if(!$data or !$where) {
			return FALSE;
		}
		$this->db->where($where);
		return $this->db->update(AD_COMPANY,$data);
	}

	/**
	 * 
	 * 通知和提醒
	 * @param int $cid
	 * @param int $date
	 * @param int $limit
	 * @param string $order
	 */
	public function notice_log($cid = NULL, $date = 3, $limit = 9, $order = 'dateline DESC'){
		
		if(!$cid){
			return  FALSE;
		}
		
		$this->db->where('cid', $cid);
		if($date){
			$this->db->where('dateline >=', strtotime('-'.$date.' day'));
		}
		
		$this->db->order_by($order);
		$this->db->limit($limit);
		$query = $this->db->get(AD_NOTICE_LOG);
		if ($query->num_rows() > 0) {
			return $query->result_array();
		}
		return FALSE;
		
	}
	
	/**
	 * 
	 * 每日支出
	 * @param int $cid
	 * @param int $limit
	 */
	public function days_speed($cid = NULL,$limit = 5,$order = 'dateline DESC'){
		
		if(!$cid){
			return  FALSE;
		}
		$sql ="select dateline ,sum(money)as sums from ".AD_PAY_LOG." where cid = '".$cid."' group by from_unixtime(dateline,'%Y%m%d') order by ".$order ." LIMIT ". $limit;
		$query = $this->db->query($sql)->result_array();
		return $query;

	}
   
}

/* End of file adcompany.php */
/* Location: ./app/models/adcompany.php */