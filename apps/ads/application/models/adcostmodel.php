<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-6-13
 * @author qianc
 * discription : 广告花费model
 */

class Adcostmodel extends MY_Model {

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
        return $this->db->delete(AD_COST, array('m_id' => $id));
    } 

    
	/**
	 * 	取得广告花费详情
	 *  @author	    qianc
	 * 	@date	    2012/7/9
	 * @return		array
	 */
	function getCostInfo($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT * FROM  ".AD_COST." WHERE ".$where;
    	$data = $this->db->query($sql)->result_array();

	    	foreach ($data as $k=>$v){
	    		$data[$k]['budget_format'] = number_format($data[$k]['budget'],2,'.',' '); 
	    		$data[$k]['bid_format'] = number_format($data[$k]['bid'],2,'.',' '); 	    			 		   		
    			$data[$k]['cost_money_format'] = number_format($data[$k]['cost_money'],2,'.',' '); 
    		}    	
    		return $data; 

	} 

	
	/**
	 * 	编辑广告花费
	 *  @author	    qianc
	 * 	@date	    2012/7/9
	 *  @return		boolean
	 */
	public function editCost( $data = NULL, $where = NULL) {
		if(!$data or !$where) {
			return FALSE;
		}
		$this->db->where($where);
		return $this->db->update(AD_COST,$data);
	}	
   
}

/* End of file adcrowdmodel.php */
/* Location: ./app/models/adcrowdmodel.php */