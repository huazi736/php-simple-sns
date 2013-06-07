<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-7-11
 * @author hujiashan
 * discription : 广告系统设置model
 */

class Adsetupmodel extends MY_Model {

	function __construct() {
        parent::__construct();
    }
    
    
    /**
     * 
     * 获取系统设置内容
     */
	public function get_system_config() {
		$results = $this->db->get(AD_CONFIG)->result_array();
		if($results){
			$configs = array();
		   	foreach ($results as $k => $value) {
				$configs[$value['var']] = shtmlspecialchars($value['datavalue']);
			}
			
			return $configs;
		}
		return FALSE;
		
    }
}


?>