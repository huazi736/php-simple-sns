<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-7-7
 * @author qianc
 * discription : 投放对象model
 */

class Adcompanycostmodel extends MY_Model {

    function __construct() {
        parent::__construct();
    }

	/**
	 * 	添加内容
	 *  @author	    qianc
	 * 	@date	    2012/6/14
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
     * @date: 2012-6-27
     * @desc: 删除投放对象
     * @access public
     * @return bool
     */
    public function delete($id) {
        return $this->db->delete(AD_COMPANYCOST, array('id' => $id));
    }

	/**
	 * 	取得广告商花费详情
	 *  @author	    qianc
	 * 	@date	    2012/7/12
	 *  @return		array
	 */
	function getCompanyCostInfo($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT * FROM  ".AD_COMPANYCOST." WHERE ".$where;
    	$data = $this->db->query($sql)->result_array();

	    	foreach ($data as $k=>$v){
	    		$data[$k]['all_money_format'] = number_format($data[$k]['all_money'],2,'.',' '); 
	    		$data[$k]['cost_money_format'] = number_format($data[$k]['cost_money'],2,'.',' ');    		 	    			 		   		
    			$data[$k]['leave_money'] = $data[$k]['all_money']-$data[$k]['cost_money']; 
    			$data[$k]['leave_money_format'] = number_format($data[$k]['leave_money'],2,'.',' ');
    		}    	
    		return $data; 

	}   

	
    /** 
     * @author: qianc
     * @date: 2012-6-27
     * @desc: 更新广告商花费
     * @access public
     * @return bool
     */
    public function updateCompanyCost($all_money,$cid) {
        return $this->db->where('cid',$cid)->set("all_money","all_money+$all_money",false)->update(AD_COMPANYCOST);
    }   
   
}

/* End of file adcrowdmodel.php */
/* Location: ./app/models/adcrowdmodel.php */