<?php

/**
 * Following 关注关系模型
 */
class RelationFollowingModel extends DkModel {
    public function __initialize() {
        $this->init_redis();
    }

    // temp method of web
    public function getWebpageFollowingDetail($uid, $pageid) {
         return $this->redis->hget('webpage:followingdetail:'.$uid, $pageid);
    }
    
    /**
	 * @abstract 更新对个人的关注时间
	 * --------------------------------------------------------------------------------------------
	 * 包括:评论，赞，转发，留言，相册，视频，日志，问答，活动，通知，发信息，加好友 ，网页对话IM,访问主页
	 * --------------------------------------------------------------------------------------------
	 * @author boolee 2012/7/2
	 * @param $uid   
	 * @param $to_uid
	 * @param $timestamp int
	 * @param $accesstype varchar
	 **/
    public function updateFollowTime( $uid, $to_uid, $timestamp = NULL, $accesstype = NULL ){
    	if(!$uid || !$to_uid || $uid == $to_uid)
    	return 0;
    	//检查是否有关注关系
		if(!in_array($to_uid, $this->redis->zrange("following:$uid", 0, -1)))
		return false;
		$re = $this->redis->hSet('following:expiry:' . $uid, $to_uid, $timestamp ? $timestamp : time());
		if($re === 0 )
		return true;
		return false;
    }
    
    public function getAllFollowings($uid, $self = true, $actorId = null) {
        if ($self) {
            $unionKey = $this->unionFollowing($uid);
            $res = $this->redis->zRevRange($unionKey, 0, -1);
        } else {
            if (!empty($actorId) && $this->isHiddenFollowing($uid, $actorId)) {
                $openKey = $this->makeOpenFollowings($uid, $actorId);
                $res = $this->redis->zRevRange($openKey, 0, -1);
            } else {
                $res = $this->redis->zRevRange('following:' . $uid, 0, -1);
            }
        }
        return $res;
    }

    /**
     * 获取用户的关注列表
     * 
     * @param type $uid     用户id
     * @param type $self    是否是用户本人获取
     * @param type $offset  列表的起始位置
     * @param type $limit   需要获取的粉丝的个数
     * @param type $actorId 当前用户id
     */
    public function getFollowings($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        if ($self) {
            $unionKey = $this->unionFollowing($uid);
            $res = $this->redis->zRevRange($unionKey, $offset, $end);
        } else {
            if (!empty($actorId) && $this->isHiddenFollowing($uid, $actorId)) {
                $openKey = $this->makeOpenFollowings($uid, $actorId);
                $res = $this->redis->zRevRange($openKey, $offset, $end);
            } else {
                $res = $this->redis->zRevRange('following:' . $uid, $offset, $end);
            }
        }
        return $res;
    }

    /**
     * 获取用户隐藏的关注列表
     * @param type $uid
     * @return type 
     */
    public function getHiddenFollowings($uid) {
        return $this->redis->zRevRange('following:hidden:' . $uid, 0, -1);
    }

    /**
     * 获取某个用户的互相关注的用户
     * 
     * @param int $uid  用户id
     */
    public function getInterFollowings($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        $interKey = $this->interFollowing($uid, $self, $actorId);
        return $this->redis->zRevRange($interKey, $offset, $end);
    }

    public function getAllInterFollowings($uid, $self = true, $actorId = null) {
        $interKey = $this->interFollowing($uid, $self, $actorId);
        return $this->redis->zRevRange($interKey, 0, -1);
    }

    /**
     * 获取两个用户的共同关注者
     * 
     * @param int $uid1 
     * @param int $uid2
     * @return array  如果两个用户没有共同关注者或者某个用户没有关注者将返回一个空的数组
     */
    public function getCommonFollowings($uid1, $uid2, $self = true) {
        $commonKey = $this->commonFollowing($uid1, $uid2, $self);
        return $this->redis->zRevRange($commonKey, 0, -1);
    }

    /**
     * 获取用户相互关注的数量
     * @param type $uid
     * @param type $self
     * @return type 
     */
    public function getNumOfInterFollowing($uid, $self = true, $actorId = null) {
        $interKey = $this->interFollowing($uid, $self, $actorId);
        return $this->redis->zCard($interKey);
    }

    /**
     * 获取某个用户的关注数
     * 
     * @param int $uid
     */
    public function getNumOfFollowings($uid) {
        return $this->redis->zCard('following:' . $uid) + $this->redis->zCard('following:hidden:' . $uid);
    }
	/**
	 *	获取个人失效关注数量
	 *	@author boolee 7/25
	 *	@param  $uid 个人id
	 *	@return int
	 * */
    public function getNumOfInvalidateFollowings( $uid ){
    	$all = $this->redis->hgetall('following:expiry:' . $uid);
    	$num = 0;
    	$nowtime = time();
    	$default_expiry = config_item('default_user_follow_expiry');
    	foreach($all as $uid1=>$time){
    		if( $time + $default_expiry < $nowtime )
    		$num++;
    	}
    	return $num;
    }
    /**
     * 获取某个用户隐藏关注对象的数量
     * @param type $uid
     * @return type 
     */
    public function getNumOfHiddenFollowings($uid) {
        return $this->redis->zCard('following:hidden:' . $uid);
    }

    /**
     * 获取两个用户的共同关注者的个数
     * 
     * @param int $uid1
     * @param int $uid2
     * @return int 两个用户的共同关注者的个数
     */
    public function getNumOfCommonFollowings($uid1, $uid2) {
        $commonKey = $this->commonFollowing($uid1, $uid2);
        return $this->redis->zCard($commonKey);
    }

    /**
     * 判断用户1是否关注了用户2
     * 
     * @param int $uid1 用户1
     * @param int $uid2 用户2
     */
    public function isFollowing($uid1, $uid2) {
        $r1 = is_numeric($this->redis->zScore('following:' . $uid1, $uid2));
        $r2 = is_numeric($this->redis->zScore('following:hidden:' . $uid1, $uid2));
        return $r1 || $r2;
    }

    //判断用户2是否用户隐藏关注的人
    public function isHiddenFollowing($uid1, $uid2) {
        return is_numeric($this->redis->zScore('following:hidden:' . $uid1, $uid2));
    }

    /**
     * 关注某个用户
     * 
     * @param int   发起关注请求的用户ID
     * @param int   需要被关注的用户ID
     * @return bool 如果成功返回true，如果用户1已经是用户2的粉丝则返回false
     */
    public function follow($uid1, $uid2) {
        $time = time();
        if ($this->redis->zAdd('follower:' . $uid2, $time, $uid1)) {
            $this->redis->zAdd('following:' . $uid1, $time, $uid2);
            //set first beginning following time. add by boolee
            $this->redis->hSet('following:expiry:' . $uid1, $uid2, $time);
            //dump keys
            $this->redis->zAdd('dump:following', $time, $uid1);
            $this->redis->zAdd('dump:follower', $time, $uid2);

            // 获取用户信息
            $userModel = DKBase::import('FastUser', 'user');
            $userinfo = $userModel->getShortInfo($uid1);

            // 关注操作产生时间轴和信息流记录
            $timelineApi = DKBase::import('Timeline');
            $data = array(
                'uid' => $userinfo['id'],
                'dkcode' => $userinfo['dkcode'],
                'uname' => $userinfo['name'],
                'permission' => 4,
                'from' => 5,
                'type' => 'social',
                'dateline' => $time,
                'ctime' => $time,
                'union'=>'follow',
            );
            $timelineApi->addTimeline($data);
            
            return true;
        }
        return false;
    }

    /**
     * 用户1取消对用户2的关注
     * 
     * @param type $uid1 用户1
     * @param type $uid2 用户2
     */
    public function unFollow($uid1, $uid2) {
        $r1 = $this->redis->zDelete('following:' . $uid1, $uid2);
        $r2 = $this->redis->zDelete('follower:' . $uid2, $uid1);
        $r3 = $this->redis->zDelete('following:hidden:' . $uid1, $uid2);
        $res = ($r1 || $r3) && $r2;

        //clear friend request
        $noticeModel = DKBase::import('Notice', 'relation');
        //删除uid1发送的好友请求
        $noticeModel->deleteFriendRequest($uid1, $uid2);
        //删除uid2发送的好友请求
        $noticeModel->deleteFriendRequest($uid2, $uid1);

        if ($res) {
            //clear dump keys
            if (!$this->redis->exists('following:' . $uid1) && !$this->redis->exists('following:hidden:' . $uid1)) {
                $this->redis->zDelete('dump:following', $uid1);
            }
            if (!$this->redis->exists('follower:' . $uid2) && !$this->redis->exists('following:hidden:' . $uid2)) {
                $this->redis->zDelete('dump:follower', $uid2);
            }
            //clear followingtime boolee 7/27
            $this->redis->hdel('following:expiry:'.$uid1, $uid2);
        }
        return $res;
    }

    /**
     * 用户隐藏某个关注，使这个关注对象在别人查看其关注列表时不可见
     * 
     * @param int $uid      用户id
     * @param int $followId 要隐藏的关注对象id
     */
    public function hideFollowing($uid, $followingId) {
        $exists = $this->redis->exists('following:' . $uid);
        if (!$exists) {
            return false;
        }

        $time = $this->redis->zScore('following:' . $uid, $followingId);
        if (!is_numeric($time)) {
            return false;
        }
        $res = $this->redis->zAdd('following:hidden:' . $uid, $time, $followingId);
        $res2 = $this->redis->zDelete('following:' . $uid, $followingId);

        $friendModel = DKBase::import('RelationFriend', 'relation');
        if ($friendModel->isFriend($uid, $followingId)) {
            //hide friend too
            $friendModel->hideFriend($uid, $followingId);
        }
        return $res && $res2;
    }

    /**
     * 取消隐藏某个关注
     * @param type $uid
     * @param type $followingId
     * @return type 
     */
    public function unHideFollowing($uid, $followingId) {
        $exists = $this->redis->exists('following:hidden:' . $uid);
        if (!$exists) {
            return false;
        }

        $time = $this->redis->zScore('following:hidden:' . $uid, $followingId);
        $res = $this->redis->zAdd('following:' . $uid, $time, $followingId);
        $res2 = $this->redis->zDelete('following:hidden:' . $uid, $followingId);
        return $res && $res2;
    }

    //生成用户公开的关注列表
    private function makeOpenFollowings($uid, $actorId) {
        $this->redis->delete('tmp:following:open:' . $uid);
        $time = $this->redis->zScore('following:hidden:' . $uid, $actorId);
        $this->redis->zAdd('tmp:following:open:' . $uid, $time, $actorId);

        $union_keys = array('following:' . $uid, 'tmp:following:open:' . $uid);

        $output_key = 'tmp:following:open:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    /**
     * 生成相互关注集合
     * @param type $uid
     * @param type $self 
     */
    private function interFollowing($uid, $self = true, $actorId = null) {
        $inter_keys = array('follower:' . $uid);
        if ($self) {
            $unionKey = $this->unionFollowing($uid);
            $inter_keys[] = $unionKey;
        } else {
            if (!empty($actorId) && $this->isHiddenFollowing($uid, $actorId)) {
                $openKey = $this->makeOpenFollowings($uid, $actorId);
                $inter_keys[] = $openKey;
            } else {
                $inter_keys[] = 'following:' . $uid;
            }
        }

        $output_key = 'tmp:following:inter:' . $uid;
        $this->redis->zInter($output_key, $inter_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    /**
     * 生成共同关注集合
     * @param type $uid
     * @param type $self 
     */
    private function commonFollowing($uid1, $uid2, $self = true) {
        $inter_keys = array('following:' . $uid2);
        if ($self) {
            $unionKey = $this->unionFollowing($uid1);
            $inter_keys[] = $unionKey;
        } else {
            $inter_keys[] = 'following:' . $uid1;
        }

        $output_key = 'tmp:following:common:' . $uid1 . ':' . $uid2;
        $this->redis->zInter($output_key, $inter_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    /**
     * 生成所有关注（公开+隐藏）集合
     * @param type $uid
     * @return type 
     */
    private function unionFollowing($uid) {
        $union_keys = array('following:' . $uid, 'following:hidden:' . $uid);

        $output_key = 'tmp:following:union:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }
	/**
	 * 取出无效关注分页uid或全部uid 
	 * @author boolee 7/25
	 * @param $uid
	 * @param $self
	 * @param $offset 初始取值地址
	 * @param $limit  每页数据
	 * @return array
	 */
    public function getInvalidateFollowingsByOffset($uid, $self, $offset = 0, $limit = -1){
    	if($self){
	    	$all = $this->redis->hgetall('following:expiry:'. $uid );
	    	$return  = array();
	    	$nowtime = time();
	    	$expiry  = config_item('default_user_follow_expiry');
    	
    		if($limit == -1){ //取出全部数据
    			foreach($all as $uid1=>$time){
		    		if($expiry + $time < $nowtime)
		    		$return[] = $uid1; 
    			}
    		}else{ //取出分页数据
    			$id = 0; //中间值
    			foreach($all as $uid1=>$time){
		    		if($expiry + $time < $nowtime){
		    			if( $id >= $offset && $id < ($offset + $limit) ){
		    				$return[] = $uid1; 
		    			}elseif($id >= $offset + $limit){
		    				break;
		    			}
		    			$id++;
		    		}
    			}
    		}
	    	
    	}else{
    		$return  = array();
    	}
    	return $return;
    }
}