<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-8-6
 * @author qianc
 * discription : 广告分成model
 */

class adpayassignmodel extends MY_Model {

    function __construct() {
        parent::__construct();
        $this->init_db('ads');        
    }

	/**
	 * 	取得广告分成列表
	 *  @author	    qianc
	 * 	@date	    2012/8/6
	 * @return		array
	 */
	function getPayAssignList($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT *  FROM  ad_pay_assign WHERE ".$where;
    	$data = $this->db->query($sql)->result_array();
    	if($data){
		    foreach ($data as $k=>$v){
		    		$data[$k]['dateline_format'] = date("Y-m-d h:i:s",$data[$k]['dateline']);
	    			$data[$k]['updatetime_format'] = date("Y-m-d h:i:s",$data[$k]['updatetime']);
	    	}    	
	    	return $data; 
    	}else{
    		return false;
    	}

	} 

	

	/**
	 * 	取得广告总分成
	 *  @author	    qianc
	 * 	@date	    2012/8/6
	 * @return		array
	 */
	function getPayAssignTotal($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT SUM(p_money) p_money_num  FROM  ad_pay_assign WHERE ".$where;

    	$data = $this->db->query($sql)->result_array();
    	if($data){ 
		    foreach ($data as $k=>$v){
	    			$data[$k]['p_money_num'] = $data[$k]['p_money_num'] ? $data[$k]['p_money_num'] : '0.00';	
	    	}      		  	
	    	return $data; 
    	}
    	return false;


	} 	
	
   
}

/* End of file adcrowdmodel.php */
/* Location: ./app/models/adcrowdmodel.php */