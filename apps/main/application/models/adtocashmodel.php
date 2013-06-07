<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-8-7
 * @author qianc
 * discription : 广告提现model
 */

class adtocashmodel extends MY_Model {

    function __construct() {
        parent::__construct();
        $this->init_db('ads');        
    }

	/**
	 * 	添加内容
	 *  @author	    qianc
	 * 	@date	    2012/8/7
	 * 	@param $data		新建的数据
	 *
	 *  @return		最后插入数据的id
	 */
	public function newData($data = NULL) {
		if(!$data) {
			return FALSE;
		}
		$res = $this->db->insert('ad_to_cash',$data);
		if($res){
			return $this->db->insert_id();
		}else{
			return FALSE;
		}
	}

    
	/**
	 * 	取得广告提现列表/详情
	 *  @author	    qianc
	 * 	@date	    2012/8/7
	 *  @return		array
	 */
	function getCashInfo($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT * FROM  ad_to_cash WHERE ".$where;
    	$data = $this->db->query($sql)->result_array();
    	if($data){
		    foreach ($data as $k=>$v){
		    		$data[$k]['dateline_format'] = date("Y-m-d h:i:s",$data[$k]['dateline']);
	    			$data[$k]['operation_time_format'] = date("Y-m-d h:i:s",$data[$k]['operation_time']);
	    	}    	
	    	return $data; 
    	}
    	return false;


	} 
	
	
	/**
	 * 	取得广告总提现
	 *  @author	    qianc
	 * 	@date	    2012/8/7
	 * @return		array
	 */
	function getCashTotal($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT SUM(money) money_num  FROM  ad_to_cash WHERE ".$where;

    	$data = $this->db->query($sql)->result_array();
    	if($data){ 
		    foreach ($data as $k=>$v){
	    			$data[$k]['money_num'] = $data[$k]['money_num'] ? $data[$k]['money_num'] : '0.00';	
	    	}      		  	
	    	return $data; 
    	}
    	return false;


	}	

		
   
}

/* End of file adtocashmodel.php */
/* Location: ./app/models/adtocashmodel.php */