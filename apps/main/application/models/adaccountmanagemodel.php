<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-8-7
 * @author qianc
 * discription : 广告提现帐户model
 */

class adaccountmanagemodel extends MY_Model {

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
	 * @return		最后插入数据的id
	 */
	public function newData($data = NULL) {
		if(!$data) {
			return FALSE;
		}
		$res = $this->db->insert('ad_account_manage',$data);
		if($res){
			return $this->db->insert_id();
		}else{
			return FALSE;
		}
	}

    /** 
     * @author: qianc
     * @date: 2012-6-27
     * @desc: 删除广告提现帐户
     * @access public
     * @return bool
     */
    public function delete($id) {
        return $this->db->delete('ad_account_manage', array('id' => $id));
    } 

    
	/**
	 * 	取得广告提现帐户列表/详情
	 *  @author	    qianc
	 * 	@date	    2012/7/30
	 * @return		array
	 */
	function getAccountManage($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT * FROM  ad_account_manage WHERE ".$where;
    	$data = $this->db->query($sql)->result_array();
    	if($data){   	
	    	return $data; 
    	}
    	return false;


	} 

	
	/**
	 * 	编辑广告提现帐户
	 *  @author	    qianc
	 * 	@date	    2012/7/30
	 *  @return		boolean
	 */
	public function editAccountManage( $data = NULL, $where = NULL) {
		if(!$data or !$where) {
			return FALSE;
		}
		$this->db->where($where);
		return $this->db->update('ad_account_manage',$data);
	}	
   
}

/* End of file adaccountmanagemodel.php */
/* Location: ./app/models/adaccountmanagemodel.php */