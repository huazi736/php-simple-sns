<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-6-13
 * @author qianc
 * discription : 投放对象model
 */

class Adcrowdmodel extends MY_Model {

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
        return $this->db->delete(AD_CROWD, array('id' => $id));
    } 

    
	/**
	 * 	取得投放对象详情
	 *  @author	    qianc
	 * 	@date	    2012/7/9
	 * @return		array
	 */
	function getCrowdInfo($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT * FROM  ".AD_CROWD." WHERE ".$where;
    	$data = $this->db->query($sql)->result_array();
    	//return service('Interest')->get_category_level_name(78,2)['name'];
//return $data;
			//$data_arr = get_area_data();
	    	foreach ($data as $k=>$v){
		    	//地区
		    	$region_arr = explode(',', $data[$k]['region']);
		    	$region_str = '';
    			foreach($region_arr as $k1=>$v1){
    				$region_str .=  service('Location')->getLocation($v1).' , ';
    			}
		    	$data[$k]['region_str'] = trim($region_str,' , ');
		    	
		    	//兴趣
		    	$interest_arr = explode(',', trim($data[$k]['interest'],','));
		    	$interest_str = '';
		    	
		    	
		    	//生成a,h_i_j_k,b,c方式......begin
//		    	foreach($interest_arr as $k2=>$v2){
//		    		if(!strstr($v2,'_')){
//		    			$interest_arr_in = service('Interest')->get_category_level_name($v2,'1');
//		    			$interest_str .= $interest_arr_in['name'].' , ';
//		    		}else{
//		    			$sub_interest_arr = explode('_', $v2);
//		    			for($i=0;$i < $len = count($sub_interest_arr);$i++){
//		    				if($i==$len-1){
//		    					$interest_arr_in = service('Interest')->get_category_level_name($sub_interest_arr[$i],$i+1);
//		    					$interest_str .= $interest_arr_in['name'].' , ';    					
//		    				}else{
//		    					$interest_arr_in = service('Interest')->get_category_level_name($sub_interest_arr[$i],$i+1);
//		    					$interest_str .= $interest_arr_in['name'].'_';
//		    				}
//		    			}
//		    		}
//		    	}
		    	//生成a,h_i_j_k,b,c方式......end		    	
		    	
		    	
		    	//生成a,b,c,d方式......begin
		    	foreach ($interest_arr as $k2=>$v2){
		    		if(!strstr($v2,'_')){
		    			$interest_arr_in = service('Interest')->get_category_level_name($v2,'1');
		    			$interest_str .= $interest_arr_in['name'].'&nbsp;&nbsp;';
		    		}else{
		    			$sub_interest_arr = explode('_', $v2);
		    			$len = count($sub_interest_arr);
		    			$interest_arr_in = service('Interest')->get_category_level_name($sub_interest_arr[$len-1],$len);
		    			$interest_str .= $interest_arr_in['name'].'&nbsp;&nbsp;';		    			
		    		}		    		
		    	}
		    	//生成a,b,c,d方式......begin		    	
		    	
		    	$data[$k]['interest_str'] = trim($interest_str,'&nbsp;&nbsp;');		    	
		    	
				//年龄
    			switch ($v['age_range']) {
					case '0':
						$data[$k]['age_range_str'] = '不限';
						break;
					case '1': 
						$data[$k]['age_range_str'] = '10-15岁';
						break;
					case '2': 
						$data[$k]['age_range_str'] = '16-22岁';
						break;
					case '3': 
						$data[$k]['age_range_str'] = '23-30岁';
						break;
					case '4': 
						$data[$k]['age_range_str'] = '31-40岁';
						break;
					case '5': 
						$data[$k]['age_range_str'] = '41-50岁';
						break;
					case '6': 
						$data[$k]['age_range_str'] = '50岁以上';
						break;
				}
								
		    	//性别
				switch ($v['gender']) {
					case '1':
						$data[$k]['gender_str'] = '男';
						break;
					case '2': 
						$data[$k]['gender_str'] = '女';
						break;
					case '3': 
						$data[$k]['gender_str'] = '全部';
						break;
				}		 		   		
    		
    	}    	
    	return $data; 

	} 

	/**
	 * 	编辑投放对象
	 *  @author	    qianc
	 * 	@date	    2012/7/9
	 *  @return		boolean
	 */
	public function editCrowd( $data = NULL, $where = NULL) {
		if(!$data or !$where) {
			return FALSE;
		}
		$this->db->where($where);
		return $this->db->update(AD_CROWD,$data);
	}	
   
}

/* End of file adcrowdmodel.php */
/* Location: ./app/models/adcrowdmodel.php */