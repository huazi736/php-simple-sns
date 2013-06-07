<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-7-30
 * @author qianc
 * discription : 广告设置model
 */

class adcustommodel extends MY_Model {

    function __construct() {
        parent::__construct();
        $this->init_db('ads');        
    }

	/**
	 * 	添加内容
	 *  @author	    qianc
	 * 	@date	    2012/7/30
	 * 	@param $data		新建的数据
	 *
	 * @return		最后插入数据的id
	 */
	public function newData($data = NULL) {
		if(!$data) {
			return FALSE;
		}
		$res = $this->db->insert('ad_custom',$data);
		if($res){
			return $this->db->insert_id();
		}else{
			return FALSE;
		}
	}

    /** 
     * @author: qianc
     * @date: 2012-6-27
     * @desc: 删除广告设置
     * @access public
     * @return bool
     */
    public function delete($id) {
        return $this->db->delete('ad_custom', array('id' => $id));
    } 

    
	/**
	 * 	取得广告设置详情
	 *  @author	    qianc
	 * 	@date	    2012/7/30
	 * @return		array
	 */
	function getCustomInfo($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT * FROM  ad_custom WHERE ".$where;
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
	 * 	编辑广告设置
	 *  @author	    qianc
	 * 	@date	    2012/7/30
	 *  @return		boolean
	 */
	public function editCustom( $data = NULL, $where = NULL) {
		if(!$data or !$where) {
			return FALSE;
		}
		$this->db->where($where);
		return $this->db->update('ad_custom',$data);
	}	
   
}

/* End of file adcustommodel.php */
/* Location: ./app/models/adcustommodel.php */