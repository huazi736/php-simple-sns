<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-7-5
 * @author qianc
 * discription : 支付model
 */

class Adpaymodel extends MY_Model {

    function __construct() {
        parent::__construct();
    }

	/**
	 * 	添加内容
	 *  @author	    qianc
	 * 	@date	    2012/7/5
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
     * @author: qianc
     * @date: 2012/7/5
     * @desc: 删除支付记录
     * @access public
     * @return bool
     */
    public function delete($id) {
        return $this->db->delete(AD_PAY, array('pay_id' => $id));
    }

    
    /** 
     * @author: qianc
     * @date: 2012-7-6
     * @desc: 获取支付列表
     * @access public
     * @return array
     */
    function getPays($nowpage =1, $limit=5, $where ,$orderby){
       // $data = $this->db->from(AD_LIST)->where($where)->order_by($orderby,'DESC')->limit($limit,$from)->get()->result_array();
		$sql = "SELECT * FROM ".AD_PAY." WHERE ".$where."  ORDER BY ".$orderby." DESC LIMIT ".$nowpage." , ".$limit;
     
    	$data = $this->db->query($sql)->result_array();   
    	foreach ($data as $k=>$v){
    		switch ($v['type']){
    			case '1':
    				$data[$k]['str_type'] = '网银';
    				break;
    			case '2':
    				$data[$k]['str_type'] = '支付宝';
    				break;    
    			case '3':
    				$data[$k]['str_type'] = '其他';
    				break;
    			case '4':
    				$data[$k]['str_type'] = '线下支付';
    				break;  
    			case '5':
    				$data[$k]['str_type'] = '财付通';
    				break;    														
    		}
    		
    	    switch ($v['status']){
    			case '0':
    				$data[$k]['str_status'] = '未支付';
    				break;
    			case '1':
    				$data[$k]['str_status'] = '成功';
    				break;    
    			case '3':
    				$data[$k]['str_status'] = '出错';
    				break;  
    			case '4':
    				$data[$k]['str_status'] = '已充值但交易失败';
    				break;     								
    		}    		
    		$data[$k]['dateline'] = date("Y-m-d h:i:s",$data[$k]['dateline']);
    		$data[$k]['money'] = number_format($data[$k]['money'],2,'.',' '); 
    		
    	}
  		return $data;


   	  	

    }  

    
    /** 
     * @author: qianc
     * @date: 2012-7-6
     * @desc: 获取支付数目
     * @access public
     * @return int
     */
    function getPaysCount($where){
		$sql = "SELECT count(pay_id) AS paysCount  FROM ".AD_PAY." WHERE ".$where ; 	
    	$data = $this->db->query($sql)->result_array();  
    	return $data['0']['paysCount'];
    }     
   
}

/* End of file adcrowdmodel.php */
/* Location: ./app/models/adcrowdmodel.php */