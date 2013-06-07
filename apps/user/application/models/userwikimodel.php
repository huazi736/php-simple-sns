<?php
class UserWikiModel extends MY_Model {

    /**
     * 以前公用的方法获取国家
     *
     * @author
     * @date
     * 
     * @param  $id  int
     * @access 
     * @return string
     */
    function get_nation_name($id) {
        if (empty($id) || is_numeric($id)) {
            return '';
        } else {
            $id=$this->db->escape($id);
            $sql = 'select area_name from ' . INFO_NATION . ' where area_id=' . $id;
            $list = $this -> db -> query($sql) -> result_array();
            if ($list) {
                return $list['area_name'];
            } else {
                return '';
            }
        }
    }

    /**
     * 以前公用的方法取得国家和地址
     *
     * @author
     * @date
     * @param  $area_code
     * @param  $type
     * @param  $separator
     * @access 
     */
    function get_area_name($area_code, $type = 1, $separator = ',') {
        if (empty($area_code))
            return '';
        $sql = 'select area_name from ' . INFO_AREA . ' where area_id=' . substr($area_code, 0, 2);
        if ($type == 2) {
            $sql .= ' or area_id=' . substr($area_code, 0, 4);
        } else if ($type == 3) {
            $sql .= ' or area_id=' . substr($area_code, 0, 4);
            if (strlen($area_code) > 4)
                $sql .= ' or area_id=' . $area_code;
        }
        $sql .= ' order by area_id asc';
        $result = $this -> db -> query($sql) -> result_array();
        if (!$result) {
            return '';
        } else {
            $str = '';
            foreach ($result as $r)
                $str .= $r['area_name'] . $separator;
            return substr($str, 0, -1);
        }
    }

    /**
     * 获取访问者uid
     *
     * @author liyud
     * @date
     * @param  $dkcode
     * @access 
     */
     function getVUid($dkcode) {      
        $user_info = service('User')->getUserInfo($dkcode, 'dkcode', array('uid'));
     	return $user_info['uid'] ? $user_info['uid'] : array();
    }

    /**
     * 获得系统兴趣
     *
     * @author liyud
     * @date
     * @param
     * @access 
     */
    /*这个方法已经用不到了，兴趣爱好模块整个被砍掉了。
     function getSysInterst($type) {     	
        $sysInterst = call_soap('info', 'Interest', 'getInterest', array("type" => $type));       
        return $sysInterst;        
    }
    */
    
    /**
     * 返回用户的在校情况
     *
     * @author liyud
     * @date
     * @param  $schoolId
     * @param  $userId
     * @param  $table
     * @access 
     */
     function getMatesBySchoolIdUserId($schoolId, $userId, $table) {
        if (!$userId) {
            return false;
        }
         $schoolId=$this->db->escape($schoolId);
         $userId=$this->db->escape($userId);
        $sql = "select * from $table where sid=" . $schoolId . " and uid='" . $userId . "'";
        return $this -> db -> query($sql) -> result_array();
    }

    /**
     * 返回用户数据
     *
     * @author liyud
     * @date
     * @param  $uid
     * @param  $table
     * @access 
     */
     function getDataByUserId($uid, $table) {
        return $this -> db  -> where(array('uid' => $uid)) -> order_by("id","desc") -> get($table)->result_array();
    }

    /**
     * 返回用户兴趣
     *
     * @author liyud
     * @date
     * @param  $uid
     * @access 
     */
     function getInterestByUserId($uid) {
        if (!$uid) {
            return array();
        }
        $uid=$this->db->escape($uid);
        $sql = "select ui.*,uir.tid,uir.uid from user_interest_relate uir join user_interests ui on (uir.tid=ui.id) where uir.uid='" . $uid . "'";
        $result = $this -> db -> query($sql);
        return $result -> result_array();
    }

    /**
     * 返回用户信息
     *
     * @author liyud
     * @date
     * @param  $id
     * @param  $table
     * @access 
     */
     function getDataById($id, $table) {
        if (!$id) {
            return false;
        }
        $result = $this -> db -> where(array('id' => $id)) ->order_by("id","desc") ->get($table) -> result_array();
        return ($result) ? array_shift($result) : false;
    }

    /**
     * 返回用户公司信息
     *
     * @author liyundong
     * @date
     * @param  $eid
     * @param  $userId
     * @param  $table
     * @access 
     */
     function getMatesByCompanyIdUserId($eid, $userId, $table) {
        if (!$userId) {
            return false;
        }
        $eid=$this->db->escape($eid);
        $userId=$this->db->escape($userId);
        $sql = "select * from {$table} where eid='" . $eid . "' and uid='" . $userId . "'";
        return $this -> db -> query($sql) -> result_array();
    }

    /**
     * 返回用户生活习惯
     *
     * @author
     * @date
     * @param  $uid
     * @param  $table
     * @access 
     */
     function getLifeDataByUserId($uid, $table) {
        if (!$uid) {
            return false;
        }
        $result = $this -> db -> where(array('uid' => $uid)) -> get($table) -> result_array();
        return ($result) ? array_shift($result) : false;
    }

    /**
     * 返回用户学校信息
     *
     * @author
     * @date
     * @param  $uid
     * @access 
     */
    function get_schoolData($uid) {
        $uid=$this->db->escape($uid);
        $sql = 'select * from user_edu where uid = ' . $uid . ' order by id desc';
        $result = $this -> db -> query($sql);
        return $result -> result_array();
    }

    /**
     * 返回用户生活习惯
     *
     * @author
     * @date
     * @param  $uid
     * @access 
     */
     function getInterestForLife($uid) {
        return $this -> db -> get_where(USER_LIFE, array('uid' => $uid)) -> result_array();
    }

    /**
     * 返回用户兴趣爱好
     *
     * @author liYD
     * @date
     * @param  $uid
     * @param  $type
     * @access 
     */
      public function getInterest($uid = null, $type = null) {
        if (!$uid) {
            return false;
        }
        $where['uid'] = $uid;

        if ($type) {
            $where['type'] = $type;
        }

       return  $this->db->from(USER_INTEREST)->where($where)->get()->result_array();
    
}
   

    /**
     * 获取用户项目经历
     *
     * @author bohailiang
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function getProjectForUser($uid = 0) {
        if (empty($uid)) {
            return false;
        }

        $result = $this -> db -> where('uid', $uid) ->order_by("id","desc")->get(RESUME_PROJECT) -> result_array();
        return $result;
    }

    /**
     * 获取用户证书
     *
     * @author bohailiang
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function getBooksForUser($uid = 0) {
        if (empty($uid)) {
            return false;
        }

        $result = $this -> db -> where('uid', $uid) ->order_by("id","desc")-> get(RESUME_BOOK) -> result_array();
        return $result;
    }

    /**
     * 获取用户证书
     *
     * @author bohailiang
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function getLangForUser($uid = 0) {
        if (empty($uid)) {
            return false;
        }

        $result = $this -> db -> where('uid', $uid) -> order_by("id","desc")->get(RESUME_LANGUAGE) -> result_array();
        return $result;
    }

    /**
     * 获取用户证书
     *
     * @author bohailiang
     * @date   2012/3/22
     * @param  $uid  int  用户id
     * @access 
     * @return array / false
     */
     function getTrainForUser($uid = 0) {
        if (empty($uid)) {
            return false;
        }
        $result = $this -> db -> where('uid', $uid) -> order_by("id","desc")->get(RESUME_TRAIN) -> result_array();
        return $result;
    }
        
        
        /**
	 * 获取用户教育情况的最早时间
	 *
	 * @author chenxujia
	 * @date   2012/3/22
	 * @param  $uid  int  用户id
	 * @access 
	 * @return array / false
	 */
	 function get_edutime($uid)
	{
		if (empty($uid)) {
			return false;
		}
        $uid=$this->db->escape($uid);
        $sql = 'select `starttime` from '.USER_UNIVERSITY.' where `uid` = '.$uid.' order by starttime asc limit 1';
        $result = $this->db->query($sql)->result_array();
         if(isset($result[0]['starttime'])){
                return $result[0]['starttime'];
          }else{
                return '';
          }
		
	}
        
        /**
	 * 获取用户工作情况的最早一条时间
	 *
	 * @author chenxujia
	 * @date   2012/3/22
	 * @param  $uid  int  用户id
	 * @access 
	 * @return array / false
	 */
	 function get_jobtime($uid)
	{
		if (empty($uid)) {
			return false;
		}
                $uid=$this->db->escape($uid);
                $sql = 'select `starttime` from '.USER_JOBEXPER.' where `uid` = '.$uid.' order by starttime asc limit 1';
                $result = $this->db->query($sql)->result_array();
                if(isset($result[0]['starttime'])){
                    return $result[0]['starttime'];
                }else{
                    return '';
                }
		
	}
        
        
         /**
	 * 获取用户在校情况的最早一条数据
	 *
	 * @author chenxujia
	 * @date   2012/3/22
	 * @param  $uid  int  用户id
	 * @access 
	 * @return array / false
	 */
	 function get_schooltime($uid)
	{
		if (empty($uid)) {
			return false;
		}
                $sql = 'select `starttime` from '.RESUME_SCHOOL.' where `uid` = '.$uid.' order by starttime asc limit 1';
                $result = $this->db->query($sql)->result_array();
                if(isset($result[0]['starttime'])){
                    return $result[0]['starttime'];
                }else{
                    return '';
                }
		
	}
        
                 /**
	 * 获取用户培训情况
	 *
	 * @author chenxujia
	 * @date   2012/3/22
	 * @param  $uid  int  用户id
	 * @access 
	 * @return array / false
	 */
	 function get_teach($uid)
	{
		if (empty($uid)) {
			return false;
		}
                $sql = 'select `starttime` from '.RESUME_TRAIN.' where `uid` = '.$uid.' order by starttime asc limit 1';
                $result = $this->db->query($sql)->result_array();
                if(isset($result[0]['starttime'])){
                    return $result[0]['starttime'];
                }else{
                    return '';
                }
		
	}
       
         /**
	 * 获取用户培训情况
	 *
	 * @author chenxujia
	 * @date   2012/3/22
	 * @param  $uid  int  用户id
	 * @access 
	 * @return array / false
	 */
	 function get_project($uid)
	{
		if (empty($uid)) {
			return false;
		}
                $sql = 'select `starttime` from '.RESUME_PROJECT.' where `uid` = '.$uid.' order by starttime asc limit 1';
                $result = $this->db->query($sql)->result_array();
                if(isset($result[0]['starttime'])){
                    return $result[0]['starttime'];
                }else{
                    return '';
                }
		
	}
        
     /**
	 * 获取用户上次修改生日的时间
	 *
	 * @author chenxujia
	 * @date   2012/3/22
	 * @param  $uid  int  用户id
	 * @access 
	 * @return array / false
	 */
	 function getLastUpdateBirth($uid)
	{
		if (empty($uid)) {
			return false;
		}
        $uid=$this->db->escape($uid);
		$sql = 'select `lastupdatebirthday`, `birthday`  from '.USERS.' where `uid` = '.$uid.' limit 1';
		$result = $this->db->query($sql)->result_array();
		if($result[0]['birthday'] == 0){return '0';}
		if(isset($result[0]['lastupdatebirthday'])){
			return $result[0]['lastupdatebirthday'];
		}else{
			return '0';
		}
		
	}	
	
    
	 /**
     * 获取用户的工作情况等记录数
     * @author bohailiang
     * @date   2012/4/12
     * @param  $type  string  类型：work - 工作情况
     * @param  $uid   int  用户id
     * @access public
     * @return int
     */
	function get_count($type = '', $uid = 0){
		if(empty($type) || empty($uid)){
			return 0;
		}

		$count_arr = array(
                		                      'work' => USER_JOBEXPER,
                		                       'edu'  => USER_UNIVERSITY,
                		                       'book' => RESUME_BOOK,
                		                       'pro' => RESUME_PROJECT,
                                               'lang' => RESUME_LANGUAGE,
                                               'skill' =>RESUME_SKILL,
                                               'tech' =>RESUME_TRAIN
                                 );//需要查找数量的表
		if(!isset($count_arr[$type])){
			return 0;
		}

		$result = $this->db->where('uid', $uid)->from($count_arr[$type])->count_all_results();
		return $result;
	}

	/**
	 *获取指定用户的家庭成员 
	 *@author hxm
	 *@date 2012/06/22
	 *
	 */
	public function getFamilyMember($uid){
		if(!$uid){
			return array();
		}
		$sql = 'select * from user_relative where uid = ' . $uid . ' order by id asc';
		$result = $this -> db -> query($sql);
		return $result -> result_array();
	}
	/**
	 *删除家庭成员  
	 *@author slf
	 *@date 2012/06/21
	 *@param  $uid  int  当前登录用户id
	 *@param  $relativemate  int  删除关系用户id
	 *@access 
	 *@return  '1'  成功 '0' 失败
	 */
	public function DoDelRelative($uid,$relativemate){
		$uid = intval($uid);
		$relativemate = intval($relativemate);
		if($uid < 0 || $relativemate < 0) return '0';
		$sql = "DELETE FROM `user_relative` WHERE `uid` = '".$uid."' AND `relativemate` = '".$relativemate."'";
		$this->db->query($sql);
		$ret = $this->db->affected_rows();
		if($ret) return '1';
		return '0';
	}
    
	public function getIntroduction($uid, $table){
		if(!$uid){
			return array();
		}
		$uid = intval($uid);
		$sql = "select introduction from ". $table ." where uid = ' " . $uid . "'";
		$result = $this -> db -> query($sql);
		$rs = $result -> result_array();
		$rs = $rs[0]['introduction'];
		return $rs;
	}
	
    public function getUserInfo($uid)
    {    
    	if(is_array($uid)){
            return service('User')->getUserInfo($uid[0]);
    	}else{	
        	return service('User')->getUserInfo($uid);
    	}
    }
    
    public function getWorkNow($uid){
   		if(!$uid){
   			return 0;
   		}
        $sql  = 'select uid, company from user_work where uid = "'.$uid.'" and endtime=0';    	    
        $result = $this->db->query($sql)->result_array();
        $count = count($result);
        if($count > 0){
            $rs = 1;
        }else{
            $rs = 0;
        }
        return $rs;
    }
    
    /**
     * 获取教育的时间段
     * */
    public function getEduTime($uid, $type = "all"){
       	if(!$uid){
   			return 0;
   		}   		
   		//$map = array('2' => '小学', '3' => '初中', '4' => '高中', '5' => '专科', '6' => '本科', '7' => '研究生', '8' => '硕士', '9' => '博士');
   		$birthday = 0;
   		$lastPrimary = 0;
   		$earliestHighSchool = 0;
   		$lastHighSchool = 0;
   		$earliestUni=0;
   		$exists = array();   		
   		$birthsql = 'select uid, birthday from user_info where uid = "'.$uid.'"';
   		$rr = $this->db->query($birthsql)->result_array();
   		$birthday = $rr[0]['birthday'];
        switch($type){
        	case 'all':
        		$sql = 'select uid, starttime, edulevel from user_edu where uid = "'.$uid.'"';
        		$result = $this->db->query($sql)->result_array();
        		break;
        	case 'university':
        		$sql = 'select uid, starttime, edulevel from user_edu where uid = "'.$uid.'" and edulevel between 3 and 9 ';
        		$result = $this->db->query($sql)->result_array();
        		break;
        	case 'highSchool':
        		$sql = 'select uid, starttime, edulevel from user_edu where uid = "'.$uid.'"';
        		$result = $this->db->query($sql)->result_array();
        		break;
        	case 'primarySchool':
        		$sql = 'select uid, starttime, edulevel from user_edu where uid = "'.$uid.'" and edulevel between 2 and 4';
        		$result = $this->db->query($sql)->result_array();        		
        		break; 
        		
        }	
        foreach($result as $key=>$val){
        	if($val['edulevel'] == 2){
        		if($val['starttime'] > $earliestHighSchool){
        		$earliestHighSchool = $val['starttime'];
        		}
       		}elseif($val['edulevel'] == 3 || $val['edulevel'] == 4){
        		if($val['starttime'] < $lastPrimary || $lastPrimary ==0){
        		$lastPrimary = $val['starttime'];        		
        		}
        		if($val['starttime'] > $earliestUni){
       			$earliestUni = $val['starttime'];
        		}
        	}elseif($val['edulevel'] > 4){
       	 		if($val['starttime'] < $lastHighSchool || $lastHighSchool ==0){
        		$lastHighSchool = $val['starttime'];
        		}
        		}
        	$exists[] = $val['starttime'];   	
        }
        if($lastPrimary == 0 && $lastHighSchool != 0){
        	$lastPrimary = $lastHighSchool;
        }
        $rs['birthday'] = !empty($birthday) ? date('Y-m-d',$birthday) : 0;
        $rs['lastPrimary'] = !empty($lastPrimary) ? date('Y-m',$lastPrimary) : 0;
        $rs['earliestHighSchool'] = !empty($earliestHighSchool) ? date('Y-m',$earliestHighSchool) : date('Y-m',$birthday);
        $rs['lastHighSchool'] = !empty($lastHighSchool) ? date('Y-m',$lastHighSchool) : 0 ;
        $rs['earliestUni'] = !empty($earliestUni) ? date('Y-m',$earliestUni) : date('Y-m',$birthday) ;
        $rs['exists'] = $exists; 
        return $rs;       
    } 
}
?>
