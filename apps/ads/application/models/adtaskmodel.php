<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-6-15
 * @author qianc
 * discription : 广告日程model
 */

class Adtaskmodel extends MY_Model {

    function __construct() {
        parent::__construct();
    }

	/**
	 * 	添加内容
	 *  @author	    qianc
	 * 	@date	    2012/6/15
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
     * @desc: 删除日程
     * @access public
     * @return bool
     */
    public function delete($id) {
        return $this->db->delete(AD_TASK, array('id' => $id));
    } 	

    /**
	 * 	编辑日程
	 *  @author	    qianc
	 * 	@date	    2012/7/9
	 *  @return		boolean
	 */
	public function editTask( $data = NULL, $where = NULL) {
		if(!$data or !$where) {
			return FALSE;
		}
		$this->db->where($where);
		return $this->db->update(AD_TASK,$data);
	}    
   
}

/* End of file admodel.php */
/* Location: ./app/models/admodel.php */