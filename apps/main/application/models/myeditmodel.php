<?php

    /**
     * 用户资料(工作、教育、在校情况)增、删、改model
     * @author chenxujia
     * @date   2012/3/22
     */


    class MyEditModel extends CI_Model{
        public function __construct()
	{
                require_once APPPATH . 'config/tables.php';
		$this->load->database();
	}
        
        function hasValues($uid){
            if(!$uid){
                return false;
            }else{
                $sql = 'select `birthday` from '.USERS.' where `uid` = '.$uid;
                $resutl = $this->db->query($sql)->result_array();
                if($resutl[0]['birthday']){
                    return true;
                }else{
                    return false;
                }
            }
        }
        
        
    /**
     * 根据id获取用户信息
     * @author chenxujia
     * @date   2012/3/22
     * @param  $uid用户id
     * @access public
     * @return $result/false
     */
        public function getUserByUid($uid){
        if(!$uid){
                return false;
        }
            $sql  = 'select * from '.USERS.' where uid = "'.$uid.'"';
            $result = $this->db->query($sql)->result_array();

            return ($result)?array_shift($result):false;
        }
        
        
    /**
     * 用户基本资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $uid用户id
     * @access public
     * @return true/false
     */
	public function baseEditDo(array $keys,array $usefulKeys, $uid){
		if(!($keys && $usefulKeys && $uid)){
			return false;
		}
		$keyVals=getKeyVals($keys,$usefulKeys);
		
		if(isset($keyVals['birthday']) && $keyVals['birthday']==''){
			$keyVals['birthday']=0;
		}
		$conditions=array('uid'=>$uid);
		if(!($keyVals && $this->db->update(USERS,$keyVals,$conditions))){
			return false;
		}
		return true;
	}
        
        
    /**
     * 添加学校
     * @author chenxujia
     * @date   2012/3/22
     * @param  $keys 数据 $table 表
     * @access public
     * @return json
     */
        public function schoolAdd(array $keys,$isReturn=false,$tables,$uid){
        $usefulKeys=array_flip($keys);
        if(!$keyVals=getKeyVals($keys,$usefulKeys)){
            return false;
        }
        $keyVals=array_merge($keyVals, array('uid'=>$uid));
        if(!$this->db->insert($tables,$keyVals)){
            return false;
        }else{
            $id = $this->db->insert_id();
             if(!$isReturn){
                json_encodes(1,L('operate_success'),array('id'=>$id));
             }else{
                return $id;
             }
        }
    }
    
    /**
     * 修改学校
     * @author chenxujia
     * @date   2012/3/22
     * @param  array $keys,array $usefulKeys,$id,$uid,$tables
     * @access public
     * @return true/false
     */
    public function schoolEdit(array $keys,array $usefulKeys,$id,$uid,$tables){
        if(!($keys && $id && $uid)){
            return false;
        }
        $keyVals=getKeyVals($keys,$usefulKeys);
        if(!$this->db->update($tables,$keyVals,array('id'=>$id,'uid'=>$uid))){
            return false;
        }
        return true;

    }

    
    }
?>
