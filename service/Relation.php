<?php

/**
 * 关系接口
 * @author shedequan
 */
class RelationService extends DK_Service {

    public function __construct() {
        parent::__construct();

        $this->init_redis();
    }

    public function test() {
        return 'hello';
    }

    // ############ 关系获取 ############ @get
    // 获取关系状态
    public function getRelationStatus($uid, $uid2) {
        if (empty($uid) || empty($uid2) || $uid == $uid2) {
            return 0;  //错误
        }

        if ($this->isFriend($uid, $uid2)) {
            $status = 10;   //好友
        } else if ($this->hasPostRequest($uid, $uid2)) {
            $status = 8;    //已发送请求
        } else if ($this->hasPostRequest($uid2, $uid)) {
            $status = 7;    //被请求为好友
        } else if ($this->isBothFollow($uid, $uid2)) {
            $status = 6;    //相互关注
        } else if ($this->isFollowing($uid, $uid2)) {
            $status = 4;    //粉丝
        } else if ($this->isFollowing($uid2, $uid)) {
            $status = 3;    //被关注
        } else {
            $status = 2;    //无关系
        }
        return $status;
    }

    // 获取与多个目标用户的关系状态
    public function getMultiRelationStatus($uid, $uids) {
        if (empty($uid) || empty($uids) || !is_array($uids)) {
            return 0;  //错误
        }

        $results = array();
        foreach ($uids as $someone) {
            $results['u' . $someone] = $this->getRelationStatus($uid, $someone);
        }
        return $results;
    }
	//批量获取一个人对多个网页关注的剩余时间 addby boolee 2012/7/14
    public function getMultiExpiry($uid, $web_ids){
    	if(!$uid || !$web_ids)
    	return false;
    	$return = array();

		foreach($web_ids as $web_id){
			 $day = $this->redis->hget('webpage:followingdetail:'.$uid, $web_id);
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
    // 获取用户关系 - 旧版
    public function getRelationWithUser($uid, $uid2) {
        if (empty($uid) || empty($uid2) || $uid == $uid2) {
            return -1;  //错误
        }

        if ($this->isFriend($uid, $uid2)) {
            return 1;   //好友
        } else if ($this->hasRequested($uid, $uid2, false)) {
            return 5;   //准好友
        } else if ($this->isBothFollow($uid, $uid2)) {
            return 2;   //相互关注
        } else if ($this->isFollowing($uid, $uid2)) {
            return 3;   //关注对象
        } else if ($this->isFollower($uid, $uid2)) {
            return 4;   //粉丝
        } else {
            return 0;   //无关系
        }
    }

    // 关系权限值
    public function getRelationWeightWithUser($uid, $uid2) {
        switch ($this->getRelationWithUser($uid, $uid2)) {
            case 1: //好友
                return 4;
            case 2: //相互关注
            case 4: //粉丝
                return 3;
            case 3: //关注对象
            case 0: //无关系
                return 1;
        }
    }

    // 关系建立的时间
    public function getStartTtimeOfUsers($fromUid, $toUid, $relation) {
        switch (intval($relation)) {
            case 1: //成为好友的时间
                $this->_getTimeOfBeFriend($toUid, $fromUid);
                break;
            case 2: //成为相互关注的时间
                $time_a = $this->_getTimeOfFollowed($toUid, $fromUid);
                $time_b = $this->_getTimeOfFollowed($fromUid, $toUid);
                return $time_a > $time_b ? $time_a : $time_b;
            case 4: //成为粉丝的时间
                return $this->_getTimeOfFollowed($toUid, $fromUid);
                break;
            default :
                return false;
                break;
        }
    }

    public function getRelationStartTime($uid, $uids, $relation) {
        
    }

    // 关注数
    public function getNumOfFollowings($uid, $self = true, $actorId = null) {
        if ($self === false) {
            $count = $this->redis->zCard('following:' . $uid);
            if (!empty($actorId) && $this->isHiddenFollowing($uid, $actorId)) {
                $count += 1;
            }
            return $count;
        }
        return $this->redis->zCard('following:' . $uid) + $this->redis->zCard('following:hidden:' . $uid);
    }

    // 粉丝数
    public function getNumOfFollowers($uid) {
        return $this->redis->zCard('follower:' . $uid);
    }

    // 相互关注数
    public function getNumOfBothFollowers($uid, $self = true, $actorId = null) {
        $interKey = $this->_interFollowing($uid, $self, $actorId);
        return $this->redis->zCard($interKey);
    }

    // 好友数
    public function getNumOfFriends($uid, $self = true, $actorId = null) {
        if ($self === false) {
            $count = $this->redis->zCard('friend:' . $uid);
            if (!empty($actorId) && $this->isHiddenFriend($uid, $actorId)) {
                $count += 1;
            }
            return $count;
        }
        return $this->redis->zCard('friend:' . $uid) + $this->redis->zCard('friend:hidden:' . $uid);
        ;
    }

    // 共同好友数
    public function getNumOfCommonFriends($uid, $uid2) {
        $commonKey = $this->_commonFriend($uid, $uid2);
        return $this->redis->zCard($commonKey);
    }

    // 获取收到的好友请求数
    public function getNumOfReceivedFriendRequests($uid) {
        return $this->redis->lSize('friend:response:' . $uid);
    }

    // ############ 关系验证 ############ @check

    /**
     * 验证用户与多个目标用户否满足某个关系状态
     * @param type $uid         用户ID
     * @param type $uids        目标用户ID列表
     * @param type $relation    关系状态类型（friend, has_requested, both_following, follower, nothing）
     * @return int              结果， 1 是 -1 否 0 错误
     */
    public function checkMultiRelation($uid, $uids, $relation = '') {
        if (empty($uid) || empty($uids) || empty($relation) || !is_array($uids)) {
            return 0;  //错误
        }

        $results = array();
        foreach ($uids as $someone) {
            $results['u' . $someone] = $this->checkRelation($uid, $someone, $relation);
        }
        return $results;
    }

    /**
     * 是否是某个关系状态
     * @param type $uid         用户ID
     * @param type $uid2        目标用户ID
     * @param type $relation    关系状态类型
     * @return int              结果，1 是 -1 否 0 错误 
     */
    public function checkRelation($uid, $uid2, $relation = '') {
        if (empty($uid) || empty($uid2) || empty($relation) || $uid == $uid2) {
            return 0;  //错误
        }

        $status = $this->getRelationStatus($uid, $uid2);

        $relations = array(
            'friend' => 10,
            'has_requested' => 8,
            'be_requested' => 7,
            'both_following' => 6,
            'follower' => 4,
            'be_followed' => 3,
            'nothing' => 2,
        );

        if (in_array($relation, array_keys($relations))) {
            //返回关系判断结果
            return $relations[$relation] == $status ? 1 : -1;
        }
        return 0;   //参数错误
    }

    // 是否关注
    public function isFollowing($uid, $uid2) {
        $r1 = is_numeric($this->redis->zScore('following:' . $uid, $uid2));
        $r2 = is_numeric($this->redis->zScore('following:hidden:' . $uid, $uid2));
        return $r1 || $r2;
    }

    // 是否是隐藏关注
    public function isHiddenFollowing($uid, $uid2) {
        return is_numeric($this->redis->zScore('following:hidden:' . $uid, $uid2));
    }

    // 是否是粉丝
    public function isFollower($uid, $uid2) {
        return is_numeric($this->redis->zScore('follower:' . $uid, $uid2));
    }

    // 是否是相互关注
    public function isBothFollow($uid, $uid2) {
        return $this->isFollowing($uid, $uid2) && $this->isFollower($uid, $uid2);
    }

    // 是否是好友
    public function isFriend($uid, $uid2) {
        $r1 = is_numeric($this->redis->zScore('friend:' . $uid, $uid2));
        $r2 = is_numeric($this->redis->zScore('friend:hidden:' . $uid, $uid2));
        return $r1 || $r2;
    }

    // 是否是隐藏好友
    public function isHiddenFriend($uid, $uid2) {
        return is_numeric($this->redis->zScore('friend:hidden:' . $uid, $uid2));
    }

    // 是否发送过好友请求
    public function hasRequested($uid, $uid2, $isBoth = true) {
        if ($isBoth) {
            return $this->_hasPostRequest($uid, $uid2) || $this->_hasPostRequest($uid2, $uid);
        }
        return $this->_hasPostRequest($uid, $uid2);
    }

    // 是否发送过好友请求
    public function hasPostRequest($uid, $uid2, $isBoth = false) {
        $r1 = false;
        if ($this->redis->exists('friend:request:' . $uid)) {
            $allFriendRequests = $this->redis->lRange('friend:request:' . $uid, 0, -1);
            $r1 = is_array($allFriendRequests) ? in_array($uid2, $allFriendRequests) : false;
        }

        if ($isBoth) {
            $r2 = $this->hasPostRequest($uid2, $uid);
            return $r1 && $r2;
        } else {
            return $r1;
        }
    }

    // ############ 关系操作 ############ @action
    // 关注
    public function follow($uid, $uid2, $max_count = 200) {
        if (empty($uid) || empty($uid2) || $uid == $uid2) {
            return 0;
        }
        
        if ($this->getNumOfFollowings($uid2) >= $max_count) {
            return -1;  //已达到关注上限，关注失败
        }

        $relationStatus = $this->getRelationStatus($uid, $uid2);

        //已关注了该用户
        if ($this->isFollowing($uid, $uid2)) {
            return 0 - $relationStatus;
        }

        $res = $this->_follow($uid, $uid2);

        $relation = $res ? $this->getRelationStatus($uid, $uid2) : 0;
        
        if ($res) {
            $is_bothfollow = $this->isBothFollow($uid, $uid2);

            $datas = $this->_processUserData($uid, $uid2);
            $datas = $this->_processTimeOfFollowed($uid, $uid2, $is_bothfollow, $datas);

            try {
                service('PeopleSearch')->addFollowing($datas['operator'], $datas['aim'], $is_bothfollow);
            } catch (Exception $e) {
                
            }
        }

        return $relation;
    }

    // 批量关注
    public function fastFollow($uid, $uids) {
        if (empty($uid) || empty($uids) || !is_array($uids)) {
            return false;
        }

        $failed_uids = array();
        foreach ($uids as $someone) {
            if ($this->follow($uid, $someone) <= 0) {
                $failed_uids[] = $someone;
            }
        }
        return empty($failed_uids) ? true : $failed_uids;
    }

    // 取消关注
    public function unFollow($uid, $uid2) {
        if (empty($uid) || empty($uid2) || $uid == $uid2) {
            return 0;
        }

        $relationStatus = $this->getRelationStatus($uid, $uid2);
        if (!$this->isFollowing($uid, $uid2)) {
            //不是关注关系
            return 0 - $relationStatus;
        } else if ($this->isFriend($uid, $uid2)) {
            //好友不可取消关注
            return 0 - $relationStatus;
        }


        //清除关注的topic信息
        try {
            service('Timeline')->delRelationsTopic($uid, $uid2, 4);
        } catch (Exception $e) {
        }
        
        $res = $this->_unFollow($uid, $uid2);
        $relation = $res ? $this->getRelationStatus($uid, $uid2) : 0;

        if ($res) {
            //清除索引关系数据
            try {
                service('PeopleSearch')->deleteFollowing($uid, $uid2);
            } catch (Exception $e) {
                
            }
        }

        return $relation;
    }

    // 隐藏关注
    public function hideFollowing($uid, $followingId) {
        if (empty($uid) || empty($followingId) || $uid == $followingId) {
            return false;
        }

        $res = $this->_hideFollowing($uid, $followingId);

        if ($res) {
            $is_bothfollow = $this->isBothFollow($uid, $followingId);
            $is_friend = $this->isFriend($uid, $followingId);

            $datas = $this->_processUserData($uid, $followingId);
            $datas = $this->_processTimeOfFollowed($uid, $followingId, $is_bothfollow, $datas);
            if ($is_friend) {
                $datas = $this->_processTimeOfBeFriend($uid, $followingId, $datas);
            }

            try {
                service('PeopleSearch')->hideFollowing($datas['operator'], $datas['aim'], $is_bothfollow, $is_friend);
            } catch (Exception $e) {
                
            }
        }

        return $res;
    }

    // 取消隐藏关注
    public function unHideFollowing($uid, $followingId) {
        if (empty($uid) || empty($followingId) || $uid == $followingId) {
            return false;
        }

        $res = $this->_unHideFollowing($uid, $followingId);

        if ($res) {
            $is_bothfollow = $this->isBothFollow($uid, $followingId);

            $datas = $this->_processUserData($uid, $followingId);
            $datas = $this->_processTimeOfFollowed($uid, $followingId, $is_bothfollow, $datas);

            try {
                service('PeopleSearch')->unHideFollowing($datas['operator'], $datas['aim'], $is_bothfollow);
            } catch (Exception $e) {
                
            }
        }

        return $res;
    }

    // 加好友
    public function addFriend($uid, $uid2) {
        if (empty($uid) || empty($uid2) || $uid == $uid2) {
            return 0;
        }

        $relationStatus = $this->getRelationStatus($uid, $uid2);
        if ($this->isFriend($uid, $uid2)) {
            return 0 - $relationStatus;   //已经是好友
        }

        //我是否有未接受的请求
        if ($this->hasPostRequest($uid2, $uid)) {
            //有请求，接受请求
            if ($this->approveFriendRequest($uid, $uid2)) {
                return $this->getRelationStatus($uid, $uid2);
            }
            return 0;
        }

        if ($this->hasPostRequest($uid, $uid2)) {
            return 0 - $relationStatus;  //已经发送好友请求
        } else if (!$this->isBothFollow($uid, $uid2)) {
            return 0 - $relationStatus;  //不满足相互关注的条件
        }

        //没有发送则发送好友请求，2表示请求发送成功
        if ($this->makeFriend($uid, $uid2)) {
            return $this->getRelationStatus($uid, $uid2);
        }
        return 0;
    }

    // 删除好友
    public function deleteFriend($uid, $uid2) {
        if (empty($uid) || empty($uid2) || $uid == $uid2) {
            return 0;
        }

        $relationStatus = $this->getRelationStatus($uid, $uid2);
        if (!$this->isFriend($uid, $uid2)) {
            //不是好友关系
            return 0 - $relationStatus;
        }

        //清除关注的topic信息
        try {
            service('Timeline')->delRelationsTopic($uid, $uid2, 1);
        } catch (Exception $e) {
            
        }

        $res = $this->_deleteFriend($uid, $uid2);
        $relation = $res ? $this->getRelationStatus($uid, $uid2) : 0;

        if ($res) {
            //清除索引关系数据
            try {
                service('PeopleSearch')->deleteFriendById($uid, $uid2);
            } catch (Exception $e) {
                
            }
        }

        return $relation;
    }

    // 隐藏好友
    public function hideFriend($uid, $friendId) {
        if (empty($uid) || empty($friendId) || $uid == $friendId) {
            return false;
        }

        $res = $this->_hideFriend($uid, $friendId);

        if ($res) {
            $datas = $this->_processUserData($uid, $friendId);
            $datas = $this->_processTimeOfBeFriend($uid, $friendId, $datas);

            try {
                service('PeopleSearch')->hideFriend($datas['operator'], $datas['aim']);
            } catch (Exception $e) {
                
            }
        }

        return $res;
    }

    // 取消隐藏好友
    public function unHideFriend($uid, $friendId) {
        if (empty($uid) || empty($friendId) || $uid == $friendId) {
            return false;
        }

        $res = $this->_unHideFriend($uid, $friendId);

        if ($res) {
            $datas = $this->_processUserData($uid, $friendId);
            $datas = $this->_processTimeOfBeFriend($uid, $friendId, $datas);

            try {
                service('PeopleSearch')->unHideFriend($datas['operator'], $datas['aim']);
            } catch (Exception $e) {
                
            }
        }

        return $res;
    }

    // 删除好友请求
    public function deleteFriendRequest($uid, $uid2) {
        if (empty($uid) || empty($uid2)) {
            return false;
        }

        return $this->_deleteFriendRequest($uid2, $uid);
    }

    // 发送好友请求
    public function makeFriend($uid, $uid2) {
        return $this->_makeFriendsWith($uid, $uid2) ? 1 : 0;
    }

    // 接受好友请求
    public function approveFriendRequest($uid, $from_uid) {
        $res = $this->_approveFriendRequest($uid, $from_uid);

        if ($res) {
            $datas = $this->_processUserData($uid, $from_uid);
            $datas = $this->_processTimeOfBeFriend($uid, $from_uid, $datas);

            try {
                service('PeopleSearch')->makeFriendWithSomeone($datas['operator'], $datas['aim']);
            } catch (Exception $e) {
                
            }
        }

        return $res ? 1 : 0;
    }

    // 设置用户信息 array('uid' => 'user id', 'uname' => 'user name', 'dkcode' => 'duankou num', 'sex' => 'sex num')
    public function setUserInfo($data = array()) {
        if (empty($data)) {
            return false;
        }

        return $this->_set($data);
    }

    // ############ 关系列表 ############ $list
    // 获取好友ID列表
    public function getFriends($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getFriendsByOffset($uid, $self, $offset, $limit, $actorId);
    }

    // 按偏移获取好友ID列表
    public function getFriendsByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        return $this->_getFriends($uid, $self, $offset, $limit, $actorId);
    }

    // 获取好友信息列表
    public function getFriendsWithInfo($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getFriendsWithInfoByOffset($uid, $self, $offset, $limit, $actorId);
    }

    // 按偏移获取好友信息列表
    public function getFriendsWithInfoByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $uids = $this->getFriendsByOffset($uid, $self, $offset, $limit, $actorId);

        $users = $this->_getByIds($uids);

        //获取用户自己的好友时，添加好友隐藏状态标记
        if ($self === true) {
            $hidden_uids = $this->_getHiddenFriends($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    // 获取JSON格式的好友信息
    public function getFriendsWithInfoInJSON($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        return json_encode($this->getFriendsWithInfo($uid, $self, $offset, $limit, $actorId));
    }

    // 获取全部好友ID列表
    public function getAllFriends($uid, $self = true, $actorId = null) {
        return $this->_getAllFriends($uid, $self, $actorId);
    }

    // 获取全部好友信息列表
    public function getAllFriendsWithInfo($uid, $self = true, $actorId = null) {
        $uids = $this->getAllFriends($uid, $self, $actorId);

        $users = $this->_getByIds($uids);

        //获取用户自己的好友时，添加好友隐藏状态标记
        if ($self === true) {
            $hidden_uids = $this->_getHiddenFriends($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    // 获取粉丝ID列表
    public function getFollowers($uid, $offset = 1, $limit = 10) {
        $offset = ($offset - 1) * $limit;
        return $this->_getFollowers($uid, $offset, $limit);
    }

    // 获取全部粉丝ID列表
    public function getAllFollowers($uid) {
        return $this->_getAllFollowers($uid);
    }

    // 获取粉丝信息列表
    public function getFollowersWithInfo($uid, $offset = 1, $limit = 10) {
        $uids = $this->getFollowers($uid, $offset, $limit);
        return $this->_getByIds($uids);
    }

    // 获取关注用户的ID列表
    public function getFollowings($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getFollowingsByOffset($uid, $self, $offset, $limit, $actorId);
    }

    // 按偏移获取关注用户的ID列表
    public function getFollowingsByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        return $this->_getFollowings($uid, $self, $offset, $limit, $actorId);
    }

    // 获取关注用户信息的列表
    public function getFollowingsWithInfo($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getFollowingsWithInfoByOffset($uid, $self, $offset, $limit, $actorId);
    }

    // 按偏移获取关注用户信息的列表
    public function getFollowingsWithInfoByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $uids = $this->getFollowingsByOffset($uid, $self, $offset, $limit, $actorId);

        $users = $this->_getByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self === true) {
            $hidden_uids = $this->_getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    // 获取JSON格式的关注用户信息
    public function getFollowingsWithInfoInJSON($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        return json_encode($this->getFollowingsWithInfo($uid, $self, $offset, $limit, $actorId));
    }

    // 获取全部关注用户的ID
    public function getAllFollowings($uid, $self = true, $actorId = null) {
        return $this->_getAllFollowings($uid, $self, $actorId);
    }

    // 获取全部关注用户的信息
    public function getAllFollowingsWithInfo($uid, $self = true, $actorId = null) {
        $uids = $this->getAllFollowings($uid, $self, $actorId);

        $users = $this->_getByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self === true) {
            $hidden_uids = $this->_getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    // 获取相互关注用户的ID列表
    public function getBothFollowers($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getBothFollowersByOffset($uid, $self, $offset, $limit, $actorId);
    }

    // 按偏移获取相互关注用户的ID列表
    public function getBothFollowersByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        return $this->_getInterFollowings($uid, $self, $offset, $limit, $actorId);
    }

    // 获取相互关注用户的信息
    public function getBothFollowersWithInfo($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getBothFollowersWithInfoByOffset($uid, $self, $offset, $limit, $actorId);
    }

    // 按偏移获取相互关注的用户信息
    public function getBothFollowersWithInfoByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $uids = $this->getBothFollowersByOffset($uid, $self, $offset, $limit, $actorId);

        $users = $this->_getByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self === true) {
            $hidden_uids = $this->_getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    // 获取JSON格式的相互关注的用户信息
    public function getBothFollowersWithInfoInJSON($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        return json_encode($this->getBothFollowersWithInfo($uid, $self, $offset, $limit, $actorId));
    }

    // 获取所有的相互关注的用户ID
    public function getAllBothFollowers($uid, $self = true, $actorId = null) {
        return $this->_getAllInterFollowings($uid, $self, $actorId);
    }

    // 获取所有的相互关注的用户信息
    public function getAllBothFollowersWithInfo($uid, $self = true, $actorId = null) {
        $uids = $this->getAllBothFollowers($uid, $self, $actorId);

        $users = $this->_getByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self === true) {
            $hidden_uids = $this->_getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    // 获取共同好友ID
    public function getCommonFriends($uid, $uid2) {
        return $this->_getCommonFriends($uid, $uid2);
    }
    
    // 批量获取共同好友ID
    public function getMultiCommonFriends($uid, $uids) {
        $arr = array();
        foreach ($uids as $someone) {
            $arr[$someone] = $this->_getCommonFriends($uid, $someone);
        }
        return $arr;
    }

    // 获取共同关注ID
    public function getCommonFollowings($uid, $uid2) {
        return $this->_getCommonFriends($uid, $uid2);
    }

    // 获取收到的好友请求
    public function getReceivedFriendRequests($uid, $offset = 1, $limit = 10) {
        $offset = ($offset - 1) * $limit;
        return $this->_getReceivedFriendRequests($uid, $offset, $limit);
    }

    // 获取用户信息
    public function getUserInfo($uid, $fields = array()) {
        return $this->_get($uid, $fields);
    }

    // 获取多个用户的信息
    public function getMultiUserInfo($uids, $fields = array()) {
        $results = array();
        foreach ($uids as $uid) {
            $results[] = $this->getUserInfo($uid, $fields);
        }
        return $results;
    }

    // #################################################################
    // #################################################################
    // #################################################################
    // ================================
    // Friend Methods
    // ================================

    /**
     * 发送加好友请求
     * 
     * @param int $uid 发起好友请求的用户ID
     * @param int $uid2 要加为好友的用户ID
     * @return bool 如果成功返回true，如果这两个用户已经是好友则返回false
     */
    private function _makeFriendsWith($uid, $uid2) {
        if (!$this->_isFriend($uid, $uid2)) {
            // 将这个请求加入到用户1发送的好友请求队列、消息队列中
            $this->redis->lPush('friend:request:' . $uid, $uid2);
            $this->redis->hSet('friend:request:notice:' . $uid, $uid2, time());

            // 将这个请求加入到用户2收到的好友请求队列、消息队列中
            $this->redis->lPush('friend:response:' . $uid2, $uid);
            $this->redis->hSet('friend:response:notice:' . $uid2, $uid, time());

            return true;
        }
        return false;
    }

    /**
     * 获取用户的好友列表
     * 
     * @param int $uid   用户ID
     * @param bool $self 是否是用户本人
     * @param int $offset 好友列表的起始位置
     * @param int $limit 需要获取的好友的个数
     */
    private function _getFriends($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        if ($self) {
            $unionKey = $this->_unionFriend($uid);
            $res = $this->redis->zRevRange($unionKey, $offset, $end);
        } else {
            if (!empty($actorId) && $this->_isHiddenFriend($uid, $actorId)) {
                $openKey = $this->_makeOpenFriends($uid, $actorId);
                $res = $this->redis->zRevRange($openKey, $offset, $end);
            } else {
                $res = $this->redis->zRevRange('friend:' . $uid, $offset, $end);
            }
        }
        return $res;
    }

    /**
     * 获取用户隐藏的好友数
     * @param type $uid
     * @return type 
     */
    private function _getHiddenFriends($uid) {
        return $this->redis->zRevRange('friend:hidden:' . $uid, 0, -1);
    }

    /**
     * 获取用户的完整的好友列表
     * 
     * @param int $uid
     * @param bool $self
     */
    private function _getAllFriends($uid, $self = true, $actorId = null) {
        if ($self) {
            $unionKey = $this->_unionFriend($uid);
            $res = $this->redis->zRevRange($unionKey, 0, -1);
        } else {
            if (!empty($actorId) && $this->_isHiddenFriend($uid, $actorId)) {
                $openKey = $this->_makeOpenFriends($uid, $actorId);
                $res = $this->redis->zRevRange($openKey, 0, -1);
            } else {
                $res = $this->redis->zRevRange('friend:' . $uid, 0, -1);
            }
        }
        return $res;
    }

    /**
     * 获取两个用户的共同好友
     * 
     * @param int $uid 
     * @param int $uid2
     * @return array  如果两个用户没有共同好友或者某个用户没有好友将返回一个空的数组
     */
    private function _getCommonFriends($uid, $uid2, $self = true) {
        $commonKey = $this->_commonFriend($uid, $uid2, $self);
        return $this->redis->zRevRange($commonKey, 0, -1);
    }

    /**
     * 判断用户是否是好友
     * 
     * @param int $uid
     * @param int $uid2
     */
    private function _isFriend($uid, $uid2) {
        $r1 = is_numeric($this->redis->zScore('friend:' . $uid, $uid2));
        $r2 = is_numeric($this->redis->zScore('friend:hidden:' . $uid, $uid2));
        return $r1 || $r2;
    }

    //判断用户2是否为用户隐藏的好友
    private function _isHiddenFriend($uid, $uid2) {
        return is_numeric($this->redis->zScore('friend:hidden:' . $uid, $uid2));
    }

    //接受好友请求
    private function _approveFriendRequest($uid, $uid2) {
        if (!$this->_hasPostRequest($uid2, $uid)) {
            return false;
        }

        //删除uid1发送的好友请求
        $this->_deleteFriendRequest($uid, $uid2);
        //删除uid2发送的好友请求
        $this->_deleteFriendRequest($uid2, $uid);

        /*
         * @todo: 记录加为好友的信息记录
         */

        $time = time();
        if ($this->_isHiddenFollowing($uid, $uid2)) {
            $r1 = $this->redis->zAdd('friend:hidden:' . $uid, $time, $uid2);
        } else {
            $r1 = $this->redis->zAdd('friend:' . $uid, $time, $uid2);
        }
        if ($this->_isHiddenFollowing($uid2, $uid)) {
            $r2 = $this->redis->zAdd('friend:hidden:' . $uid, $time, $uid2);
        } else {
            $r2 = $this->redis->zAdd('friend:' . $uid2, $time, $uid);
        }
        $res = $r1 && $r2;

        if ($res) {
            //dump keys
            $this->redis->zAdd('dump:friend', $time, $uid);
            $this->redis->zAdd('dump:friend', $time, $uid2);
        }
        return $res;
    }

    /**
     * 忽略好友的请求
     * 
     * @param int $uid
     * @param int $uid2
     */
    private function _denyFriendRequest($uid, $uid2) {
        /*
         * @todo: 用户拒绝加好友
         */
    }

    /**
     * 获取某个用户的好友数
     * 
     * @param int $uid
     */
    private function _getNumOfFriends($uid) {
        return $this->redis->zCard('friend:' . $uid) + $this->redis->zCard('friend:hidden:' . $uid);
    }

    /**
     * 获取用户隐藏的好友数
     * @param type $uid
     * @return type 
     */
    private function _getNumOfHiddenFriends($uid) {
        return $this->redis->zCard('friend:hidden:' . $uid);
    }

    /**
     * 获取两个用户的共同好友的个数
     * 
     * @param int $uid
     * @param int $uid2
     * @return int 两个用户的共同好友的个数
     */
    private function _getNumOfCommonFriends($uid, $uid2) {
        $commonKey = $this->_commonFriend($uid, $uid2);
        return $this->redis->zCard($commonKey);
    }

    /**
     * 删除好友
     * 
     * @param int $uid
     * @param int $uid2
     */
    private function _deleteFriend($uid, $uid2) {
        /**
         * @todo: 记录删除好友的事件
         */
        $r1 = $this->redis->zDelete('friend:' . $uid, $uid2);
        $r2 = $this->redis->zDelete('friend:' . $uid2, $uid);
        $r3 = $r1 || $r2;
        // delete hiden friend
        $r1 = $this->redis->zDelete('friend:hidden:' . $uid, $uid2);
        $r2 = $this->redis->zDelete('friend:hidden:' . $uid2, $uid);
        $r4 = $r1 || $r2;
        $res = $r3 || $r4;

        if ($res) {
            //clear dump keys
            if (!$this->redis->exists('friend:' . $uid) && !$this->redis->exists('friend:hidden:' . $uid)) {
                $this->redis->zDelete('dump:friend', $uid);
            }
            if (!$this->redis->exists('friend:' . $uid2) && !$this->redis->exists('friend:hidden:' . $uid2)) {
                $this->redis->zDelete('dump:friend', $uid2);
            }
        }
        return $res;
    }

    /**
     * 用户隐藏某个好友，使这个好友在别人查看其好友列表时不可见
     * 
     * @param int $uid
     * @param int $friendId
     */
    private function _hideFriend($uid, $friendId) {
        $exists = $this->redis->exists('friend:' . $uid);
        if (!$exists) {
            return false;
        }

        $time = $this->redis->zScore('friend:' . $uid, $friendId);
        $res = $this->redis->zAdd('friend:hidden:' . $uid, $time, $friendId);
        $res2 = $this->redis->zDelete('friend:' . $uid, $friendId);
        return $res && $res2;
    }

    /**
     * 取消隐藏某个好友
     * @param type $uid
     * @param type $friendId
     * @return type 
     */
    private function _unHideFriend($uid, $friendId) {
        $exists = $this->redis->exists('friend:hidden:' . $uid);
        if (!$exists) {
            return false;
        }

        //Cancel to hide friend
        $time = $this->redis->zScore('friend:hidden:' . $uid, $friendId);
        if (is_numeric($time)) {
            $res = $this->redis->zAdd('friend:' . $uid, $time, $friendId);
            $res2 = $this->redis->zDelete('friend:hidden:' . $uid, $friendId);
        } else {
            return false;
        }

        //Cancel to hide following
        $time = $this->redis->zScore('following:hidden:' . $uid, $friendId);
        if (is_numeric($time)) {
            $this->redis->zAdd('following:' . $uid, $time, $friendId);
            $this->redis->zDelete('following:hidden:' . $uid, $friendId);
        }

        return $res && $res2;
    }

    //获取成为好友的时间
    private function _getTimeOfBeFriend($uid, $friendId) {
        $time1 = $this->redis->zScore('friend:' . $uid, $friendId);
        $time2 = $this->redis->zScore('friend:hidden:' . $uid, $friendId);
        if (is_numeric($time1)) {
            return $time1;
        } elseif (is_numeric($time2)) {
            return $time2;
        }
        return false;
    }

    //生成用户公开的好友列表
    private function _makeOpenFriends($uid, $actorId) {
        $this->redis->delete('tmp:friend:open:' . $uid);
        $time = $this->redis->zScore('friend:hidden:' . $uid, $actorId);
        $this->redis->zAdd('tmp:friend:open:' . $uid, $time, $actorId);

        $union_keys = array('friend:' . $uid, 'tmp:friend:open:' . $uid);

        $output_key = 'tmp:friend:open:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    private function _commonFriend($uid, $uid2, $self = true) {
        $inter_keys = array('friend:' . $uid2);
        if ($self) {
            $unionKey = $this->_unionFriend($uid);
            $inter_keys[] = $unionKey;
        } else {
            $inter_keys[] = 'friend:' . $uid;
        }

        $output_key = 'tmp:friend:common:' . $uid . ':' . $uid2;
        $this->redis->zInter($output_key, $inter_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    private function _unionFriend($uid) {
        $union_keys = array('friend:' . $uid, 'friend:hidden:' . $uid);

        $output_key = 'tmp:friend:union:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    // ================================
    // Follower Methods
    // ================================

    private function _getAllFollowers($uid) {
        return $this->redis->zRevRange('follower:' . $uid, 0, -1);
    }

    /**
     * 获取用户的粉丝列表
     * 
     * @param int $uid      用户ID
     * @param int $offset   粉丝列表的起始位置
     * @param int $limit    需要获取的粉丝的个数
     */
    private function _getFollowers($uid, $offset = 0, $limit = 10) {
        if ($limit == 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        return $this->redis->zRevRange('follower:' . $uid, $offset, $end);
    }

    /**
     * 获取两个用户的共同粉丝
     * 
     * @param int $uid 
     * @param int $uid2
     * @return array  如果两个用户没有共同粉丝或者某个用户没有粉丝将返回一个空的数组
     */
    private function _getCommonFollowers($uid, $uid2) {
        $commonKey = $this->_commonFollower($uid, $uid2);
        return $this->redis->zRevRange($commonKey, 0, -1);
    }

    /**
     * 获取用户的粉丝数
     * 
     * @param int $uid
     */
    private function _getNumOfFollowers($uid) {
        return $this->redis->zCard('follower:' . $uid);
    }

    /**
     * 获取两个用户的共同粉丝的个数
     * 
     * @param int $uid
     * @param int $uid2
     * @return int 两个用户的共同粉丝的个数
     */
    private function _getNumOfCommonFollowers($uid, $uid2) {
        $commonKey = $this->_commonFollower($uid, $uid2);
        return $this->redis->zCard($commonKey);
    }

    //获取关注某个的时间
    private function _getTimeOfFollowed($uid, $followerId) {
        $time = $this->redis->zScore('follower:' . $uid, $followerId);
        return is_numeric($time) ? $time : false;
    }

    private function _commonFollower($uid, $uid2) {
        $inter_keys = array('follower:' . $uid, 'follower:' . $uid2);

        $output_key = 'tmp:follower:common:' . $uid . ':' . $uid2;
        $this->redis->zInter($output_key, $inter_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    // ================================
    // Following Methods
    // ================================

    private function _getAllFollowings($uid, $self = true, $actorId = null) {
        if ($self) {
            $unionKey = $this->_unionFollowing($uid);
            $res = $this->redis->zRevRange($unionKey, 0, -1);
        } else {
            if (!empty($actorId) && $this->_isHiddenFollowing($uid, $actorId)) {
                $openKey = $this->_makeOpenFollowings($uid, $actorId);
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
    private function _getFollowings($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        if ($self) {
            $unionKey = $this->_unionFollowing($uid);
            $res = $this->redis->zRevRange($unionKey, $offset, $end);
        } else {
            if (!empty($actorId) && $this->_isHiddenFollowing($uid, $actorId)) {
                $openKey = $this->_makeOpenFollowings($uid, $actorId);
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
    private function _getHiddenFollowings($uid) {
        return $this->redis->zRevRange('following:hidden:' . $uid, 0, -1);
    }

    /**
     * 获取某个用户的互相关注的用户
     * 
     * @param int $uid  用户id
     */
    private function _getInterFollowings($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        $interKey = $this->_interFollowing($uid, $self, $actorId);
        return $this->redis->zRevRange($interKey, $offset, $end);
    }

    private function _getAllInterFollowings($uid, $self = true, $actorId = null) {
        $interKey = $this->_interFollowing($uid, $self, $actorId);
        return $this->redis->zRevRange($interKey, 0, -1);
    }

    /**
     * 获取两个用户的共同关注者
     * 
     * @param int $uid 
     * @param int $uid2
     * @return array  如果两个用户没有共同关注者或者某个用户没有关注者将返回一个空的数组
     */
    private function _getCommonFollowings($uid, $uid2, $self = true) {
        $commonKey = $this->_commonFollowing($uid, $uid2, $self);
        return $this->redis->zRevRange($commonKey, 0, -1);
    }

    /**
     * 获取用户相互关注的数量
     * @param type $uid
     * @param type $self
     * @return type 
     */
    private function _getNumOfInterFollowing($uid, $self = true, $actorId = null) {
        $interKey = $this->_interFollowing($uid, $self, $actorId);
        return $this->redis->zCard($interKey);
    }

    /**
     * 获取某个用户的关注数
     * 
     * @param int $uid
     */
    private function _getNumOfFollowings($uid) {
        return $this->redis->zCard('following:' . $uid) + $this->redis->zCard('following:hidden:' . $uid);
    }

    /**
     * 获取某个用户隐藏关注对象的数量
     * @param type $uid
     * @return type 
     */
    private function _getNumOfHiddenFollowings($uid) {
        return $this->redis->zCard('following:hidden:' . $uid);
    }

    /**
     * 获取两个用户的共同关注者的个数
     * 
     * @param int $uid
     * @param int $uid2
     * @return int 两个用户的共同关注者的个数
     */
    private function _getNumOfCommonFollowings($uid, $uid2) {
        $commonKey = $this->_commonFollowing($uid, $uid2);
        return $this->redis->zCard($commonKey);
    }

    //是否是关注
    private function _isFollowing($uid, $uid2) {
        $r1 = is_numeric($this->redis->zScore('following:' . $uid, $uid2));
        $r2 = is_numeric($this->redis->zScore('following:hidden:' . $uid, $uid2));
        return $r1 || $r2;
    }

    //是否是相互关注
    private function _isBothFollowing($uid, $uid2) {
        $this->_isFollowing($uid, $uid2) && $this->_isFollowing($uid2, $uid);
    }

    //是否是隐藏关注
    private function _isHiddenFollowing($uid, $uid2) {
        return is_numeric($this->redis->zScore('following:hidden:' . $uid, $uid2));
    }

    //关注
    private function _follow($uid, $uid2) {
        $time = time();
        if ($this->redis->zAdd('follower:' . $uid2, $time, $uid)) {
            $this->redis->zAdd('following:' . $uid, $time, $uid2);
            //set first beginning following time. add by boolee
            $this->redis->hSet('following:expiry:' . $uid, $uid2, $time);
            //dump keys
            $this->redis->zAdd('dump:following', $time, $uid);
            $this->redis->zAdd('dump:follower', $time, $uid2);
            return true;
        }
        return false;
    }

    /**
     * 用户1取消对用户2的关注
     * 
     * @param type $uid 用户1
     * @param type $uid2 用户2
     */
    private function _unFollow($uid, $uid2) {
        $r1 = $this->redis->zDelete('following:' . $uid, $uid2);
        $r2 = $this->redis->zDelete('follower:' . $uid2, $uid);
        $r3 = $this->redis->zDelete('following:hidden:' . $uid, $uid2);
        $res = ($r1 || $r3) && $r2;

        //clear friend request
        //删除uid1发送的好友请求
        $this->_deleteFriendRequest($uid, $uid2);
        //删除uid2发送的好友请求
        $this->_deleteFriendRequest($uid2, $uid);

        if ($res) {
            //clear dump keys
            if (!$this->redis->exists('following:' . $uid) && !$this->redis->exists('following:hidden:' . $uid)) {
                $this->redis->zDelete('dump:following', $uid);
            }
            if (!$this->redis->exists('follower:' . $uid2) && !$this->redis->exists('following:hidden:' . $uid2)) {
                $this->redis->zDelete('dump:follower', $uid2);
            }
        }
        return $res;
    }

    /**
     * 用户隐藏某个关注，使这个关注对象在别人查看其关注列表时不可见
     * 
     * @param int $uid      用户id
     * @param int $followId 要隐藏的关注对象id
     */
    private function _hideFollowing($uid, $followingId) {
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

        if ($this->_isFriend($uid, $followingId)) {
            //hide friend too
            $friend_model->_hideFriend($uid, $followingId);
        }
        return $res && $res2;
    }

    /**
     * 取消隐藏某个关注
     * @param type $uid
     * @param type $followingId
     * @return type 
     */
    private function _unHideFollowing($uid, $followingId) {
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
    private function _makeOpenFollowings($uid, $actorId) {
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
    private function _interFollowing($uid, $self = true, $actorId = null) {
        $inter_keys = array('follower:' . $uid);
        if ($self) {
            $unionKey = $this->_unionFollowing($uid);
            $inter_keys[] = $unionKey;
        } else {
            if (!empty($actorId) && $this->_isHiddenFollowing($uid, $actorId)) {
                $openKey = $this->_makeOpenFollowings($uid, $actorId);
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
    private function _commonFollowing($uid, $uid2, $self = true) {
        $inter_keys = array('following:' . $uid2);
        if ($self) {
            $unionKey = $this->unionFollowing($uid);
            $inter_keys[] = $unionKey;
        } else {
            $inter_keys[] = 'following:' . $uid;
        }

        $output_key = 'tmp:following:common:' . $uid . ':' . $uid2;
        $this->redis->zInter($output_key, $inter_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    /**
     * 生成所有关注（公开+隐藏）集合
     * @param type $uid
     * @return type 
     */
    private function _unionFollowing($uid) {
        $union_keys = array('following:' . $uid, 'following:hidden:' . $uid);

        $output_key = 'tmp:following:union:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    // ================================
    // Notice Methods
    // ================================

    /**
     * 获取某个用户的发送的好友请求
     * 
     * @param int $uid
     * @param int $offset
     * @param int $limit  
     */
    private function _getFriendRequests($uid, $offset = 0, $limit = 3) {
        //$start = ($offset - 1) * $limit;
        //$end = $offset * $limit - 1;
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        // 首先获取该用户已发送的好友请求的接收者ID
        $friendsRequested = $this->redis->lRange('friend:request:' . $uid, $offset, $end);
        if (!empty($friendsRequested)) {
            return $this->redis->hMget('friend:request:notice:' . $uid, $friendsRequested);
        }
        return array();
    }

    private function _hasPostRequest($uid, $uid2) {
        if ($this->redis->exists('friend:request:' . $uid)) {
            $allFriendRequests = $this->redis->lRange('friend:request:' . $uid, 0, -1);
            return is_array($allFriendRequests) ? in_array($uid2, $allFriendRequests) : false;
        }
        return false;
    }

    /**
     * 获取某个用户收到的好友请求
     * 
     * @param int $uid
     * @param int $offset
     * @param int $limit
     */
    private function _getReceivedFriendRequests($uid, $offset = 0, $limit = 3) {
        //$start = $offset;
        //$start = ($offset - 1) * $limit;
        //$end = $offset * $limit - 1;
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        // 首先获取该用户收到的好友请求的发送者的ID
        $requestsReceived = $this->redis->lRange('friend:response:' . $uid, $offset, $end);

        if (!empty($requestsReceived)) {
            $noticeTimes = $this->redis->hMget('friend:response:notice:' . $uid, $requestsReceived);
            foreach ($noticeTimes as $key => $time) {
                $user = $this->_get($key);
                $notice['uid'] = $key;
                $notice['uname'] = $user['name'];
                $notice['ctime'] = $time;
                $data[] = $notice;
            }
            return $data;
        }
        return array();
    }

    /**
     * 获取用户收到的好友请求数
     * 
     * @param int $uid
     */
    private function _getNumOfReceivedFriendRequests($uid) {
        return $this->redis->lSize('friend:response:' . $uid);
    }

    /**
     * 获取用户发送的好友请求数
     * 
     * @param int $uid
     */
    private function _getNumOfFriendRequests($uid) {
        return $this->redis->lSize('friend:request:' . $uid);
    }

    //删除好友请求
    private function _deleteFriendRequest($uid, $uid2) {
        // 从用户1发送的好友请求列表中删除该请求
        $r1 = $this->redis->lRemove('friend:request:' . $uid, $uid2);
        $r2 = $this->redis->hDel('friend:request:notice:' . $uid, $uid2);

        // 从用户2收到的好友请求列表中删除该请求
        $r3 = $this->redis->lRemove('friend:response:' . $uid2, $uid);
        $r4 = $this->redis->hDel('friend:response:notice:' . $uid2, $uid);

        return $r1 && $r2 && $r3 && $r4;
    }

    // ================================
    // User Methods
    // ================================

    private function _getByIds($uids) {
        $users = array();
        foreach ($uids as $uid) {
            $users[] = $this->_get($uid);
        }
        return $users;
    }

    private function _get($uid, $fields = '') {
        if (empty($fields)) {
            $fields = 'id,name,dkcode,sex';
        }

        if ($fields == '*') {
            $user = $this->redis->hGetAll('user:' . $uid);
        } else {
            $show_fields = explode(',', $fields);
            if (empty($show_fields)) {
                return array();
            }
            $user = $this->redis->hMGet('user:' . $uid, $show_fields);
        }

        if ($user['name'] === false || $user['dkcode'] === false) {
            $user = $this->_syncUser($uid);
        }
        return $user;
    }

    private function _set($data) {
        if ($this->_checkFields($data)) {
            if (isset($data['sex']) && empty($data['sex'])) {
                $data['sex'] = '3'; //默认3，性别保密
            }

            $data = array_filter($data);
            $mapping = array('uid' => 'id', 'uname' => 'name', 'dkcode' => 'dkcode', 'sex' => 'sex');
            $data_arr = array();
            foreach ($mapping as $key => $value) {
                if (isset($data[$key])) {
                    $data_arr[$value] = $data[$key];
                }
            }

            $res = $this->redis->hMset('user:' . $data['uid'], $data_arr);			
            if ($res) {
                //dump keys
                $this->redis->zAdd('dump:user', time(), $data_arr['id']);
            }
            return $res;
        }
        return false;
    }

    private function _delete($uid) {
        $res = $this->redis->delete('user:' . $uid);
        if ($res) {
            //dump keys
            if (!$this->redis->exists('user:' . $uid)) {
                $this->redis->zDelete('dump:user', $uid);
            }
        }
        return $res;
    }

    /**
     * 验证传入的数据信息
     * @param type $data
     * @return bool 
     */
    private function _checkFields($data) {
        if (!is_array($data)) {
            return false;
        }

        //验证field是否规范
        $valid_fields = array('uid', 'dkcode');
        $data_fields = array_keys(array_filter($data));

        foreach ($valid_fields as $field) {
            if (!in_array($field, $data_fields)) {
                return false;
            }
        }
        return true;
    }

    //Sync user from mysql to redis
    private function _syncUser($uid) {
        $default = array(
            'id' => false,
            'name' => false,
            'dkcode' => false,
            'sex' => false
        );

//        $host = C('USER_DB_CONFIG.HOST');
//        $username = C('USER_DB_CONFIG.USERNAME');
//        $pwd = C('USER_DB_CONFIG.PWD');
//        $dbname = C('USER_DB_CONFIG.DBNAME');
//
//        $conn = mysql_connect($host, $username, $pwd);
//        if (!$conn) {
//            return $default;
//        }
//
//        mysql_select_db($dbname);
//
//        $query = sprintf('SELECT * FROM user_info WHERE uid = \'%d\'', $uid);
//        mysql_query('set names utf8');
//        $result = mysql_query($query);
//
//        $row = mysql_fetch_array($result, MYSQL_ASSOC);
//        if (empty($row)) {
//            return $default;
//        }

        $user = service('User')->getUserInfo($uid);
        
        $user['id'] = strval($uid);
        $user['name'] = $user['username'];
        $user['dkcode'] = $user['dkcode'];
        $user['sex'] = $user['sex'];

        //Set user info
        $res = $this->redis->hMset('user:' . $uid, $user);

//        mysql_free_result($result);
//        mysql_close($conn);

        return $res ? $user : $default;
    }
    
    //组装用户信息
    private function _processUserData($operator_id, $aim_id) {
        $operator = $this->getUserInfo($operator_id);
        $operator_followers = $this->_getNumOfFollowers($operator_id);
        $aim_user = $this->getUserInfo($aim_id);
        $aim_user_followers = $this->_getNumOfFollowers($aim_id);
        $operator['follower_num'] = $operator_followers;
        $aim_user['follower_num'] = $aim_user_followers;
        return array(
            'operator' => $operator,
            'aim' => $aim_user
        );
    }

    //组装关注时间
    private function _processTimeOfFollowed($operator_id, $aim_id, $is_bothfollow, $datas) {
        $be_following_time = $this->_getTimeOfFollowed($aim_id, $operator_id);
        $datas['operator']['time'] = $be_following_time;
        if ($is_bothfollow) {
            $be_follower_time = $this->_getTimeOfFollowed($operator_id, $aim_id);
            $datas['aim']['time'] = $be_follower_time;
        }
        return $datas;
    }

    //组装成为好友的时间
    private function _processTimeOfBeFriend($operator_id, $aim_id, $datas) {
        $be_friend_time = $this->_getTimeOfBeFriend($operator_id, $aim_id);
        $datas['operator']['frd_time'] = $be_friend_time;
        return $datas;
    }
    
    /**
     * 获取有效期内粉丝
     * @author boolee 2012/7/3
     * @param  $uid 用户的ID
     * @return array 
     */
    public function getValiditionFollowers($uid,$order = false) {
        $allfollowers = $this->redis->zRevRange('follower:' . $uid, 0, -1);
        $times = $this->redis->hMgetall('following:expiry:' . $uid, $allfollowers);
        $return = array();
        $ordera = array();
        foreach($times as $uid=>$eachtime){
        	$is_validate = $eachtime + config_item('default_user_follow_expiry')- time();
        	if($is_validate > 0)  //操作时间+系统有效时间>当前时间
        	$return[] = $uid;
        	$ordera[$uid] = $eachtime;
        }
        if($order){
        	arsort($ordera);//对数组由时间排序
        	return array_keys($ordera);
        }else{
        	return $return;
        }   
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
    
	//获取用户关系数 add by boolee 2012/7/12
	public function getRelationNums($uids = null, $is_self = TRUE){
		if($uids){
			$return = array();
			if(!is_array($uids))
			$uids =  array($uids);
			foreach( $uids as $uid ){
				$fans      = $this->_getNumOfFollowers($uid); //粉丝数
				$following = $this->getNumOfFollowings($uid, $is_self);//关注数
				$friend    = $this->getNumOfFriends($uid, $is_self);   //好友数
				$fans      = $fans ? $fans : 0;
				$following = $following ? $following : 0;
				$friend    = $friend ? $friend : 0;
				
			    $return[$uid]['fan']       = $fans-$friend;
				$return[$uid]['following'] = $following-$friend;
				$return[$uid]['friend']    = $friend;
			}
			return $return;
		}else{
			return false;
		}
	}
}

