<?php

class WebpageRelationApi extends DkApi {

    protected $following;
    protected $follower;

    /**
     * 关注网页
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @param action_time 设置时间戳
     * @param expiry_time 关注总时间 
     * @return mix 返回值：成功 粉丝数, 失败 false
     */
    public function follow($uid, $pageid, $action_time = 0, $expiry_time = 0, $max_count = 200) {
        if ($this->getNumOfFollowings($uid) >= $max_count) {
            return -1;  //已达到关注上限，关注失败
        }

        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        $re = $this->following->follow($uid, $pageid, $action_time, $expiry_time);
        if ($re) {
            return $this->getNumOfFollowers($pageid);
        } else {
            return false;
        }
    }

    /**
     * 取消关注
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return mix 返回值：成功 粉丝数, 失败 false
     */
    public function unFollow($uid, $pageid) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        if ($this->following->unFollow($uid, $pageid)) {
            return $this->getNumOfFollowers($pageid);
        }
        return false;
    }

    /**
     * 修改redis保存网页关注时间
     * @author	boolee
     * @date	2012/06/26
     * @param  $uid string 用户ID
     * @param  $pageid int  目标pageid
     * @param  $action_time int 操作时间戳
     * @param  $expiry_time int 关注时间
     * @return boolean
     */
    public function updateFollowTime($uid, $pageid, $action_time, $expiry_time) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->updateFollowTime($uid, $pageid, $action_time, $expiry_time);
    }

    /**
     * 获取关注网页的时间
     * @param type $uid
     * @param type $pageid
     * @return type 
     */
    public function getStartTtimeOfWeb($uid, $pageid) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->getTimeOfFollow($uid, $pageid);
    }
    
    /**
     * 隐藏网页关注
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return bool 
     */
    public function hideFollowing($uid, $pageid) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->hideFollowing($uid, $pageid);
    }
	/**
     * 批量隐藏网页关注
     * @author boolee 8/3
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return bool 
     */
    public function hideMoreFollowing($uid, $pageids) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        foreach ($pageids as $web_id){
        	$re[] = $this->following->hideFollowing($uid, $web_id);
        }
        return !empty($re);//非空就是真
    }
    /**
     * 取消批量隐藏网页关注
     * @author boolee 8/3
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return bool 
     */
    public function unHideMoreFollowing($uid, $pageids) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        foreach ($pageids as $web_id){
        	$re[] = $this->following->unHideFollowing($uid, $web_id);
        }
        return !empty($re);//非空就是真
    }
    
    /**
     * 取消隐藏网页关注
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return bool 
     */
    public function unHideFollowing($uid, $pageid) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->unHideFollowing($uid, $pageid);
    }

    /**
     * 获取所有网页关注
     * @param type $uid 用户ID
     * @param type $self 网页ID
     * @return array 
     */
    public function getAllFollowings($uid, $self = true) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->getAllFollowings($uid, $self);
    }

    /**
     * 获取网页关注
     * @param type $uid 用户ID
     * @param type $self 是否已自己身份获取
     * @param type $offset 起始偏移量
     * @param type $limit 返回数
     * @return array
     */
    public function getFollowings($uid, $self = true, $offset = 0, $limit = 10) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->getFollowings($uid, $self, $offset, $limit);
    }

    /**
     * 获取网页所有的粉丝
     * @param type $pageid  网页ID
     * @return array 
     */
    public function getAllFollowers($pageid) {
        $this->follower = DKBase::import('WebpageFollower', 'web_relation');
        return $this->follower->getAllFollowers($pageid);
    }

    /**
     * 获取一个网页当前所有的有效粉丝
     * @author boolee 2012/6/30
     * @param  $pageid  网页ID
     * @return array 
     */
    public function getAllValiditionFollowers($pageid) {
        $this->follower = DKBase::import('WebpageFollower', 'web_relation');
        return $this->follower->getAllValiditionFollowers($pageid);
    }

    /**
     * 对指定网页关注判断
     * @param  $uid      用户ID
     * @param  $web_ids  网页ID数组
     * @author boolee
     * @return json 
     */
    public function checkUserFollowings($uid, $web_ids=array()) {
        if (!$uid || !$web_ids)
            return false;

        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        $re = $this->following->checkUserFollowings($uid, $web_ids);

        $return = array();
        $defaultdays = intval(config_item('default_follow_expiry_time') / 86400);
        foreach ($re as $key => $list) {
            $return[$key]['type'] = 'd';
            if ($list) {
                $list = json_decode($list, 1);
                //计算剩余天数
                $days = $list['expiry_time'] == -1 ? $list['expiry_time'] : ceil(($list['action_time'] + $list['expiry_time'] - time()) / 86400);

                if ($days > 0) {  //关注有效
                    $return[$key]['days'] = $days;
                    $return[$key]['relation'] = 4;
                    $return[$key]['state'] = 1;
                } elseif ($days == -1) {//永久关注
                    $return[$key]['days'] = $defaultdays;
                    $return[$key]['relation'] = 6;
                    $return[$key]['state'] = 1;
                } else {    //关注过期,使用上次保存时间或者默认时间
                    $return[$key]['days'] = $list['expiry_time'] == -1 ? $defaultdays : intval($list['expiry_time'] / 86400);
                    $return[$key]['relation'] = 8;
                    $return[$key]['state'] = 1;
                }
            } else {     //未关注网页
                $return[$key]['days'] = $defaultdays;
                $return[$key]['relation'] = 2;
                $return[$key]['state'] = 1;
            }
        }
        return $return;
    }

    /**
     * 获取网页的粉丝
     * @param type $pageid  网页ID
     * @param type $offset 起始偏移量
     * @param type $limit 返回数
     * @return array
     */
    public function getFollowers($pageid, $offset = 0, $limit = 10) {
        $this->follower = DKBase::import('WebpageFollower', 'web_relation');
        return $this->follower->getFollowers($pageid, $offset, $limit);
    }

    /**
     * 获取网页的粉丝 包含用户简短信息
     * @param type $pageid 网页ID
     * @param type $offset 起始偏移量
     * @param type $limit 返回数
     * @return type 
     */
    public function getFollowersWithInfo($pageid, $offset = 0, $limit = 10) {
        $uids = $this->getFollowers($pageid, $offset, $limit);
        return DKBase::import('User')->getShortInfoByIds($uids);
    }

    /**
     * 获取网页关注数
     * @param type $uid 用户ID
     * @param type $self 是否以自己身份
     * @return int
     */
    public function getNumOfFollowings($uid, $self = true) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->getNumOfFollowings($uid, $self);
    }

    /**
     * 获取粉丝数
     * @param type $pageid  网页ID
     * @return int
     */
    public function getNumOfFollowers($pageid) {
        $this->follower = DKBase::import('WebpageFollower', 'web_relation');
        return $this->follower->getNumOfFollowers($pageid);
    }

    /**
     * 获取多个网页粉丝数
     * @param type $pageids 网页ID列表
     * @return type 
     */
    public function getMultiNumOfFollowers($pageids) {
        $arr = array();
        foreach ($pageids as $pageid) {
            $arr['p' . $pageid] = $this->getNumOfFollowers($pageid);
        }
        return $arr;
    }

    /**
     * 是否关注
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return type 
     */
    public function isFollowing($uid, $pageid) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->isFollowing($uid, $pageid);
    }

    /**
     * 获取关注时间
     * @author boolee 7/20
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return type 
     */
    public function getFollowingTime($uid, $pageid) {
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->getFollowingTime($uid, $pageid);
    }

    /**
     * 是否关注了多个网页
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return type 
     */
    public function isFollowings($uid, $pageids) {
        $arr = array();
        foreach ($pageids as $pageid) {
            $arr['p' . $pageid] = $this->isFollowing($uid, $pageid);
        }
        return $arr;
    }

    /**
     * 清除网页关系
     * @param type $pageid 网页ID
     * @return mix  成功 true，失败 粉丝ID集合
     */
    public function clearRelation($pageid) {
        $this->follower = DKBase::import('WebpageFollower', 'web_relation');
        $this->following = DKBase::import('WebpageFollowing', 'web_relation');
        
        $followers = $this->follower->getAllFollowers($pageid);

        //Clear following
        $failed = array();
        foreach ($followers as $uid) {
            if ($this->following->unFollow($uid, $pageid) === false) {
                $failed[] = $uid;
            }
        }

        //Clear follower
        $this->follower->flushFollowers($pageid);
        return empty($failed) ? true : false;
    }
	//批量获取一个人对多个网页关注的剩余时间 addby boolee 2012/7/14
    public function getMultiExpiry($uid, $web_ids){
    	if(!$uid || !$web_ids)
    	return false;
    	$return = array();
		$this->following = DKBase::import('WebpageFollowing', 'web_relation');
		foreach($web_ids as $web_id){
			 $day = $this->following->getWebExpiry($uid, $web_id);
			 $day = json_decode($day,1);
			 if(!isset($day['expiry_time'])){
			 	$return[$web_id]['relation'] = 2;		//无关系
			 	$return[$web_id]['days'] = ceil(config_item('default_follow_expiry_time')/86400);		//默认时间
			 }elseif($day['expiry_time'] == -1){
			 	$return[$web_id]['relation'] = 6;	    //永久
			 	$return[$web_id]['days'] = ceil(config_item('default_follow_expiry_time')/86400);		//默认时间
			 }else{
			 	$last = $day['expiry_time']+$day['action_time']-time();
			 	if( $last > 0 ){
			 		$return[$web_id]['relation'] = 4;  //剩余时间
			 		$return[$web_id]['days'] = ceil($last/86400);
			 	}else{
			 		$return[$web_id]['relation'] = 8;  //剩余时间
			 		$return[$web_id]['days'] = ceil($day['expiry_time']/86400);
			 	}
			 }
		}
		return $return;
    }
 	/**
     * 获取所有共同关注网页
     * @param type $uid 用户ID
     * @param type $self 网页ID
	 * @author boolee 2012/7/14
     * @return array 
     */
    public function getCommonFollowings($uid1,$uid2, $self = true) {
        $f1 = $this->getAllFollowings($uid1);
        $f2 = $this->getAllFollowings($uid2);
        $re = array_intersect($f1, $f2);
        return $re; 
    }
	/**
     * 获取所有共同关注网页
     * @param type $uid 用户ID
     * @param type $self 网页ID
	 * @author boolee 2012/7/14
     * @return array 
     */
    public function getCommonFollowingsInfo($uid1,$uid2, $self = true) {
        $f1 = $this->getAllFollowings($uid1);
        $f2 = $this->getAllFollowings($uid2, $self);
        $re = array_intersect($f1, $f2);
        $return = array();
        if($re){
	        foreach ($re as $webid)
	        {
	        	$return[$webid] = service('interest')->get_web_info($webid);
	        }
        }
        return $return; 
    }
    /**
     * 取得个人失效网页的数字
     * @author boolee 7/21
     */
    function getNumOfUnValidateFollowings($uid, $is_self = true){
    	$this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->getNumOfUnValidateFollowings($uid, $is_self);
    }
 	/**
     * 取得个人失效网页web_ids
     * @author boolee 7/21
     */
    function getUnValidateFollowings($uid, $is_self = true, $offset = null, $limit = null){
    	$this->following = DKBase::import('WebpageFollowing', 'web_relation');
        return $this->following->getUnValidateFollowings($uid, $is_self, $offset, $limit);
    }
	/**
     * 对指定网页关注判断
     * @param  $uid      用户ID
     * @param  $web_ids  网页ID数组
     * @author boolee
     * @return json 
     */
//    public function checkUserFollowings($uid,$web_ids=array()) {
//    	if(!$uid || !$web_ids)
//    	return false;
//
//        $re= $this->redis->hMget('webpage:followingdetail:'.$uid,$web_ids);
//        
//        $return=array();
//        $defaultdays=intval(config_item('default_follow_expiry_time')/86400);
//        foreach ($re as $key=>$list){
//        	$return[$key]['type']='d';
//        	if($list){
//        		$list=json_decode($list,1);
//        		//计算剩余天数
//        		$days= $list['expiry_time'] == -1 ? $list['expiry_time'] : ceil(($list['action_time']+$list['expiry_time']-time())/86400); 
//        		
//        		if($days > 0){		//关注有效
//        			$return[$key]['days']     = $days;
//        			$return[$key]['relation'] = 4;
//        			$return[$key]['state']	  = 1;
//        		}elseif($days == -1){//永久关注
//        			$return[$key]['days'] 	  = $defaultdays;
//        			$return[$key]['relation'] = 6;
//        			$return[$key]['state']	  = 1;
//        		}else{				//关注过期,使用上次保存时间或者默认时间
//        			$return[$key]['days'] 	  = $list['expiry_time']==-1 ? $defaultdays : intval($list['expiry_time']/86400);
//        			$return[$key]['relation'] = 8;
//        			$return[$key]['state']	  = 1;
//        		}
//        	}else{					//未关注网页
//        		$return[$key]['days']		  = $defaultdays;
//        		$return[$key]['relation']     = 2;
//        		$return[$key]['state']        = 1;
//        	}
//        }
//        return  $return;
//    }

}