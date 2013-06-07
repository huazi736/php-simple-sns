<?php
    /**
     * 用户资料(工作、教育、在校情况)增、删、改model
     * @author chenxujia
     * @date   2012/3/22
     */
    class MyEditModel extends MY_Model{
        
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
        $keyVals['classmate'] = strtr($keyVals['classmate'],array('[]'=>''));
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
    
    /**
     * 添加家庭成员
     * @author hxm
     * @date 2012/06/20
     * @access public 
     */
    public function familyAdd($arr, $table, $isReturn=false){
        $success = false;       
        foreach($arr as $key=>$val){
        	$rs = $this->db->insert($table, $val);
        	if($rs){
        		$id = $this->db->insert_id();
        		$ids[] = $id;
        	    $success = true;
        	}        	
        }        
        if($success){
        	if(!$isReturn){
        	    json_encodes(1,L('operate_success'),array('ids'=>$ids));
        	    //$this->ajaxReturn(array('ids'=>$ids), L('operate_success'), 1);        		
        	}else{
        		return $ids;        		
        	}     	
        }else{
        	return false;        	
        }
    }
    
    /**
     * 删除指定的成员 
     * @author hxm
     * @date 2012/06/25
     * @access public 
     * @param $arr 亲人的uid数组，$uid 用户自己的uid, $table数据库表
     */
    public function familyDel($arr, $uid, $table){
    	$map = implode(',' , $arr);    	 		
   		$sql = "delete from ". $table ." where uid = '".$uid."' and relativemate in (".$map.")";   			
    	$this->db->query($sql);
		$result = $this->db->affected_rows();   	
    	if($result){
    		$success = true;    		
    	}else{
    		$success = false;
    	}	
    	return $success;
    } 

    /**
     *修改指定的家庭成员 
     */
    function familyEdit($arr, $uid, $table){
    	$time = time();
    	foreach($arr as $key=>$val){
  			$sql = "update ". $table ." set type = '". $val ."', dateline = '". $time ."' where uid = '".$uid."' and relativemate = '". $key ."'";   			
    		$result = $this->db->query($sql);   		   	
    		if($result){
    			$success = true;    		
    			}else{
    			$success = false;
    		}	    			
    	}
    	return $success;	
    }
    
    /***
     * 添加自我介绍
     * */
    public function introductionAdd($uid, $introduction, $table){
    	if(!$uid){
    		return false;
    	}    	   	
    	$sql = "update ". $table ." set introduction = '". $introduction ."' where uid = '".$uid."'";
        $this->db->query($sql);
		$result = $this->db->affected_rows();		
		if($result){
			$success = true;
		}else{
			$success = false;			
		}
		return $success;
    }
    
    public function getDataBy($id, $tables){
        $result=$this->db->where(array('id'=>$id))->get($tables)->result_array();
        return ($result)?array_shift($result):false;
    }
    
    public function insertData($tables, $arr){
    	$result = $this->db->insert($tables, $arr);
    	$id = $this->db->insert_id();
    	return $id;
    }
    
    public function deleteData($tables,$id,$uid){
          $rs = $this->db->query("delete from ".$tables." where id='$id' and uid='$uid'");
          if($rs){
          	return true;
          }else{
          	return false;
          }         
    }
    
    public function refreshes($uid){
    	$rs = service('RestorationSearch')->restoreUserInfo($uid);
    	
    }
    
    public function getInfo($uid){
    	if(!$uid){
    		return array();
    	}
    	$sql  = 'select username, birthday, sex from '.USERS.' where uid = "'.$uid.'"';    	    
        $result = $this->db->query($sql)->result_array();
        return $result[0];    	
    }
}
?>
