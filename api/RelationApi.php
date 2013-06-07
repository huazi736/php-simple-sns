<?php

class RelationApi extends DkApi {

    /**
     * 常量定义
     */
    // 陌生人(无关系)
    const STRANGER = 2;

    // 被关注
    const FOLLOWED = 3;

    // 已关注(是TA的粉丝)
    const FOLLOWINGED = 4;

    // 相互关注
    const BOTH_FOLLOWING = 6;

    // 已被好友发送请求
    const SENDED_FRIEND_REQUEST = 7;

    // 已发送好友请求
    const SENDINGED_FRIEND_REQUEST = 8;

    // 好友
    CONST FRIEND = 10;

    protected $following;
    protected $follower;
    protected $friend;
    protected $notice;
    protected $fastUser;
    /**
     * 获取用户建立关系的时间
     * @param type $fromUid 关系的起点用户ID
     * @param type $toUid 关系的终点用户ID
     * @param type $relation 关系值
     * @return type 
     */
    public function getStartTtimeOfUsers($fromUid, $toUid, $relation) {
        switch (intval($relation)) {
            case 1: //成为好友的时间
                $this->friend = DKBase::import('RelationFriend', 'relation');
                return $this->friend->getTimeOfBeFriend($toUid, $fromUid);
                break;
            case 2: //成为相互关注的时间
                $this->follower = DKBase::import('RelationFollower', 'relation');
                $time_a = $this->follower->getTimeOfFollowed($toUid, $fromUid);
                $time_b = $this->follower->getTimeOfFollowed($toUid, $fromUid);
                return $time_a > $time_b ? $time_a : $time_b;
            case 4: //成为粉丝的时间
                $this->follower = DKBase::import('RelationFollower', 'relation');
                return $this->follower->getTimeOfFollowed($toUid, $fromUid);
                break;
            default :
                return false;
                break;
        }
    }

    /**
     * 获取用户的好友数量
     * @param type $uid 用户ID
     * @param type $self 是否以自己的身份获取
     * @return type 
     */
    public function getNumOfFriends($uid, $self = true, $actorId = null) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        if ($self === false) {
            $count = $this->friend->getNumOfFriends($uid) - $this->friend->getNumOfHiddenFriends($uid);
            if (!empty($actorId) && $this->friend->isHiddenFriend($uid, $actorId)) {
                $count += 1;
            }
            return $count;
        }
        return $this->friend->getNumOfFriends($uid);
    }

    /**
     * 获取用户2与用户的共同的好友数量
     * @param type $uid 用户的ID
     * @param type $uid2 用户2的ID
     * @return type 
     */
    public function getNumOfCommonFriends($uid, $uid2) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->getNumOfCommonFriends($uid, $uid2);
    }

    /**
     * 获取用户关注的数量
     * @param type $uid
     * @param type $self
     * @return type 
     */
    public function getNumOfFollowings($uid, $self = true, $actorId = null) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        if ($self === false) {
            $count = $this->following->getNumOfFollowings($uid) - $this->following->getNumOfHiddenFollowings($uid);
            if (!empty($actorId) && $this->following->isHiddenFollowing($uid, $actorId)) {
                $count += 1;
            }
            return $count;
        }
        return $this->following->getNumOfFollowings($uid);
    }
	/**
     * 获取用户个人失效关注的数量
     * @author boolee
     * @param type $uid
     * @return int 
     */
    public function getNumOfInvalidateFollowings($uid) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->getNumOfInvalidateFollowings($uid);
    }
    /**
     * 获取个人失效好友数量
     * @author boolee
     * @param type $uid
     * @return int 
     */
    public function getNumOfInvalidateFriends($uid){
    	$this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->getNumOfInvalidateFriends($uid);
    }
	/**
     * 获取个人失效好友信息
     * @author boolee
     * @param type $uid
     * @return int 
     */
    public function getInvalidateFriendsWithInfo($uid, $is_self, $page, $limit, $action_uid){
        return $this->getInvalidateFriendsWithInfoByOffset($uid, $is_self, $page, $limit, $action_uid);
    }
    
    /**
     * 获取用户的过期好友列表 - 包含用户信息
     * @author boolee 7/27
     **/
	public function getInvalidateFriendsWithInfoByOffset($uid, $is_self = true, $page = 1, $limit = 10, $action_uid = null) {
        $uids = $this->getInvalidateFriendsByOffset($uid, $is_self, $page, $limit, $action_uid);
        $this->fastUser = DKBase::import('FastUser', 'user');
        $users = $this->fastUser->getShortInfoByIds($uids);

        //获取用户自己的好友时，添加好友隐藏状态标记
        if ($is_self === true) {
            $this->friend = DKBase::import('RelationFriend', 'relation');
            $hidden_uids = $this->friend->getHiddenFriends($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }
    
    /**
     * 获取无效好友分业数据
     * @author boolee 7/27
     */
    public function getInvalidateFriendsByOffset($uid, $is_self = true, $page = 1, $limit = 10, $action_uid = null) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->invalidateFriends($uid, $is_self, $page, $limit, $action_uid);
    }
    
    /**
     * 获取用户的粉丝数量
     * @param type $uid 用户的ID
     * @return type
     */
    public function getNumOfFollowers($uid) {
        $this->follower = DKBase::import('RelationFollower', 'relation');
        return $this->follower->getNumOfFollowers($uid);
    }

    /**
     * 获取相互关注的用户的数量
     * @param type $uid 用户ID
     * @param type $self 是否以自己的身份获取
     * @param type $actorId 访问者ID
     * @return type 
     */
    public function getNumOfBothFollowers($uid, $self = true, $actorId = null) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->getNumOfInterFollowing($uid, $self, $actorId);
    }

    /**
     * 用户2是否为用户的好友
     * @param type $uid 用户的ID
     * @param type $uid2 用户2的ID
     * @return type 
     */
    public function isFriend($uid, $uid2) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->isFriend($uid, $uid2);
    }

    /**
     * 用户2是否为用户的隐藏好友
     * @param type $uid 用户的ID
     * @param type $uid2 用户2的ID
     * @return type 
     */
    public function isHiddenFriend($uid, $uid2) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->isHiddenFriend($uid, $uid2);
    }

    /**
     * 用户2是否为用户的关注对象
     * @param type $uid 用户的ID
     * @param type $uid2 用户2的ID
     * @return bool
     */
    public function isFollowing($uid, $uid2) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->isFollowing($uid, $uid2);
    }

    /**
     * 用户2是否为用户的隐藏关注
     * @param type $uid 用户的ID
     * @param type $uid2 用户2的ID
     * @return type 
     */
    public function isHiddenFollowing($uid, $uid2) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->isHiddenFollowing($uid, $uid2);
    }

    /**
     * 用户2是否为用户的粉丝
     * @param type $uid 用户的ID
     * @param type $uid2 用户2的ID
     * @return bool 
     */
    public function isFollower($uid, $uid2) {
        return $this->isFollowing($uid2, $uid);
    }

    /**
     * 用户与某人是否为相互关注
     * @param type $uid     用户的ID
     * @param type $uid2    用户2的ID，某人
     * @return type 
     */
    public function isBothFollow($uid, $uid2) {
        return $this->isFollowing($uid, $uid2) && $this->isFollowing($uid2, $uid);
    }

    /**
     * 获取用户2与用户的关系
     * @param type $user 用户的ID
     * @param type $user2 用户2的ID
     * @return int 用户2与用户的关系值： 1 好友, 2 相互关注, 3 关注对象, 4 粉丝, 5 已发请求的准好友, 0 无关系, -1 错误
     */
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

    /**
     * 获取用户与目标用户的关系状态
     * @param type $uid     用户ID
     * @param type $uid2    目标用户ID
     * @return mix          关系状态，0 
     */
    public function getRelationStatus($uid, $uid2) {
        if (empty($uid) || empty($uid2) || $uid == $uid2) {
            return 0;  //错误
        }

        $this->notice = DKBase::import('Notice', 'relation');
        if ($this->isFriend($uid, $uid2)) {
            $status = 10;   //好友
        } else if ($this->notice->hasPostRequest($uid, $uid2)) {
            $status = 8;    //已发送请求
        } else if ($this->notice->hasPostRequest($uid2, $uid)) {
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

    /**
     * 获取与多个目标用户的关系状态
     * @param type $uid     用户ID
     * @param type $uids    目标用户ID列表
     * @return mix          关系状态集合，0
     */
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

    /**
     * 验证用户与某人，是否满足某个关系状态
     * @param type $uid 用户ID
     * @param type $uid2 目标用户ID
     * @param type $relation 关系状态类型
     * @return type 
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

    /**
     * 验证用户与多个目标用户否满足某个关系状态
     * @param type $uid         用户ID
     * @param type $uids        目标用户ID列表
     * @param type $relation    关系状态类型（friend, has_requested, both_following, follower, nothing）
     * @return mix              验证结果的集合（结果为 1 或 -1），0
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
     * 获取与多人的好友关系
     * @param type $uid 用户的ID
     * @param type $uids 目标用户的ID列表
     * @return type 
     */
    public function getMultiRelationWithUsers($uid, $uids) {
        if (!is_array($uids) || empty($uids)) {
            return false;
        }
        $relations = array();
        foreach ($uids as $someone) {
            $relations['u' . $someone] = $this->getRelationWithUser($uid, $someone);
        }
        return $relations;
    }

    /**
     * 获取用户关系权值
     * @param type $uid
     * @param type $uid2
     * @return int 用户2与用户的关系权值： 4 好友, 3 粉丝, 1 陌生人
     */
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

    /**
     * 获取用户的好友列表
     * @param type $uid 用户的ID
     * @param bool $self 是否是该用户自己获取好友列表
     * @param type $offset 页码
     * @param type $limit 每页数量
     * @return type 
     */
    public function getFriends($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getFriendsByOffset($uid, $self, $offset, $limit, $actorId);
    }

    public function getFriendsByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->getFriends($uid, $self, $offset, $limit, $actorId);
    }

    /**
     * 获取用户的好友列表 - 包含用户信息
     * @param type $uid 用户的ID
     * @param bool $self 是否是该用户自己获取好友列表
     * @param type $offset 页码
     * @param type $limit 每页数量
     * @return type 
     */
    public function getFriendsWithInfo($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getFriendsWithInfoByOffset($uid, $self, $offset, $limit, $actorId);
    }

    public function getFriendsWithInfoByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $uids = $this->getFriendsByOffset($uid, $self, $offset, $limit, $actorId);
        $this->fastUser = DKBase::import('FastUser', 'user');
        $users = $this->fastUser->getShortInfoByIds($uids);

        //获取用户自己的好友时，添加好友隐藏状态标记
        if ($self === true) {
            $this->friend = DKBase::import('RelationFriend', 'relation');
            $hidden_uids = $this->friend->getHiddenFriends($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    public function getFriendsWithInfoInJSON($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        return json_encode($this->getFriendsWithInfo($uid, $self, $offset, $limit, $actorId));
    }

    /**
     * 获取用户自己的所有好友
     * 
     * @param int $uid  用户ID
     * @param bool $self 是否以自己的身份获取
     */
    public function getAllFriends($uid, $self = true, $actorId = null) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->getAllFriends($uid, $self, $actorId);
    }

    public function getAllFriendsWithInfo($uid, $self = true, $actorId = null) {
        $uids = $this->getAllFriends($uid, $self, $actorId);
        $this->fastUser = DKBase::import('FastUser', 'user');
        $users = $this->fastUser->getShortInfoByIds($uids);
        //获取用户自己的好友时，添加好友隐藏状态标记
        if ($self === true) {
            $this->friend = DKBase::import('RelationFriend', 'relation');
            $hidden_uids = $this->friend->getHiddenFriends($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    /**
     * 获取粉丝
     * @param type $uid 用户的ID
     * @param type $offset 页码
     * @param type $limit 每页数量
     * @return type 
     */
    public function getFollowers($uid, $offset = 1, $limit = 10) {
        $offset = ($offset - 1) * $limit;
        $this->follower = DKBase::import('RelationFollower', 'relation');
        return $this->follower->getFollowers($uid, $offset, $limit);
    }

    public function getAllFollowers($uid) {
        $this->follower = DKBase::import('RelationFollower', 'relation');
        return $this->follower->getAllFollowers($uid);
    }

    /**
     * 获取有效期内粉丝
     * @author boolee 2012/7/3
     * @param  $uid 用户的ID
     * @return array 
     */
    public function getValiditionFollowers($uid) {
        $this->follower = DKBase::import('RelationFollower', 'relation');
        return $this->follower->getValiditionFollowers($uid);
    }

    /**
     * 获取粉丝 - 包含用户信息
     * @param type $uid 用户的ID
     * @param type $offset 页码
     * @param type $limit 每页数量
     * @return type 
     */
    public function getFollowersWithInfo($uid, $offset = 1, $limit = 10) {
        $uids = $this->getFollowers($uid, $offset, $limit);
        $this->fastUser = DKBase::import('FastUser', 'user');
        return $this->fastUser->getShortInfoByIds($uids);
    }

    /**
     * 获取关注的用户
     * @param type $uid 用户的ID
     * @param bool $self 是否是用户本人获取关注列表
     * @param type $offset 页码
     * @param type $limit 每页数量
     * @return type 
     */
    public function getFollowings($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getFollowingsByOffset($uid, $self, $offset, $limit, $actorId);
    }

    public function getFollowingsByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->getFollowings($uid, $self, $offset, $limit, $actorId);
    }

    /**
     * 获取关注的用户 - 包含用户信息
     * @param type $uid 用户的ID
     * @param bool $self 是否是用户本人获取关注列表
     * @param type $offset 页码
     * @param type $limit 每页数量
     * @return type 
     */
    public function getFollowingsWithInfo($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getFollowingsWithInfoByOffset($uid, $self, $offset, $limit, $actorId);
    }
	/**
     * 获取无效关注的用户 - 包含用户信息
     * @author boolee 7/25
     * @param type $uid 用户的ID
     * @param bool $self 是否是用户本人获取关注列表
     * @param type $offset 页码
     * @param type $limit 每页数量
     * @return type 
     */
    public function getInvalidateFollowingsWithInfo($uid, $self = true, $offset = 1, $limit = 10) {
        $offset = ($offset - 1) * $limit;
        return $this->getInvalidateFollowingsWithInfoByOffset($uid, $self, $offset, $limit);
    }
    /**
     * 获取无效个人关注详细信息
     * @author boolee 7/25
     **/
    public function getInvalidateFollowingsWithInfoByOffset($uid, $self, $offset, $limit){
    	$uids = $this->getInvalidateFollowingsByOffset($uid, $self, $offset, $limit);
    	$this->fastUser = DKBase::import('FastUser', 'user');
        $users = $this->fastUser->getShortInfoByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self) {
            $this->following = DKBase::import('RelationFollowing', 'relation');
            $hidden_uids = $this->following->getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    } 
    /**
     * 获取无效关注分页id
     * @author boolee 7/25 
     **/
	public function getInvalidateFollowingsByOffset($uid, $self = true, $offset = 0, $limit = 10) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->getInvalidateFollowingsByOffset($uid, $self, $offset, $limit);
    }
    
    public function getFollowingsWithInfoByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $uids = $this->getFollowingsByOffset($uid, $self, $offset, $limit, $actorId);
        $this->fastUser = DKBase::import('FastUser', 'user');
        $users = $this->fastUser->getShortInfoByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self === true) {
            $this->following = DKBase::import('RelationFollowing', 'relation');
            $hidden_uids = $this->following->getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    public function getFollowingsWithInfoInJSON($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        return json_encode($this->getFollowingsWithInfo($uid, $self, $offset, $limit, $actorId));
    }

    public function getAllFollowings($uid, $self = true, $actorId = null) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->getAllFollowings($uid, $self, $actorId);
    }

    public function getAllFollowingsWithInfo($uid, $self = true, $actorId = null) {
        $uids = $this->getAllFollowings($uid, $self, $actorId);
        $this->fastUser = DKBase::import('FastUser', 'user');
        $users = $this->fastUser->getShortInfoByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self === true) {
            $this->following = DKBase::import('RelationFollowing', 'relation');
            $hidden_uids = $this->following->getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    /**
     * 获取与用户相互关注的人
     * @param type $uid 用户的ID
     * @return type 
     */
    public function getBothFollowers($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getBothFollowersByOffset($uid, $self, $offset, $limit, $actorId);
    }

    public function getBothFollowersByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->getInterFollowings($uid, $self, $offset, $limit, $actorId);
    }

    /**
     * 获取与用户相互关注的人 - 包含用户信息
     * @param type $uid 用户的ID
     * @return type 
     */
    public function getBothFollowersWithInfo($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        $offset = ($offset - 1) * $limit;
        return $this->getBothFollowersWithInfoByOffset($uid, $self, $offset, $limit, $actorId);
    }

    public function getBothFollowersWithInfoByOffset($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        $uids = $this->getBothFollowersByOffset($uid, $self, $offset, $limit, $actorId);
        $this->fastUser = DKBase::import('FastUser', 'user');
        $users = $this->fastUser->getShortInfoByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self === true) {
            $this->following = DKBase::import('RelationFollowing', 'relation');
            $hidden_uids = $this->following->getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    /**
     * 获取与用户相互关注的人 - 包含用户信息
     * @param type $uid
     * @return string 返回JSON
     */
    public function getBothFollowersWithInfoInJSON($uid, $self = true, $offset = 1, $limit = 10, $actorId = null) {
        return json_encode($this->getBothFollowersWithInfo($uid, $self, $offset, $limit, $actorId));
    }

    public function getAllBothFollowers($uid, $self = true, $actorId = null) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->getAllInterFollowings($uid, $self, $actorId);
    }

    public function getAllBothFollowersWithInfo($uid, $self = true, $actorId = null) {
        $uids = $this->getAllBothFollowers($uid, $self, $actorId);
        $this->fastUser = DKBase::import('FastUser', 'user');
        $users = $this->fastUser->getShortInfoByIds($uids);

        //获取用户自己关注用户时，添加关注用户隐藏状态标记
        if ($self === true) {
            $this->following = DKBase::import('RelationFollowing', 'relation');
            $hidden_uids = $this->following->getHiddenFollowings($uid);
            $flag_uids = array_intersect($uids, $hidden_uids);
            foreach ($users as $index => $user) {
                $user['hidden'] = in_array($user['id'], $flag_uids) ? 1 : 0;
                $users[$index] = $user;
            }
        }
        return $users;
    }

    /**
     * 获取用户与用户2共同的好友
     * @param type $uid 用户的ID
     * @param type $uid2 用户2的ID
     * @return type 
     */
    public function getCommonFriends($uid, $uid2, $is_self = TRUE) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->getCommonFriends($uid, $uid2);
    }

    /**
     * 获取用户与用户2共同关注的人
     * @param type $uid 用户的ID
     * @param type $uid2 用户2的ID
     * @return type 
     */
    public function getCommonFollowings($uid, $uid2, $is_self = TRUE) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        return $this->following->getCommonFollowings($uid, $uid2, $is_self);
    }

    /**
     * 获取用户收到的好友请求
     * @param type $uid 用户ID
     * @param type $offset 页码
     * @param type $limit 每页数量
     * @return type 
     */
    public function getReceivedFriendRequests($uid, $offset = 1, $limit = 10) {
        $offset = ($offset - 1) * $limit;
        $this->notice = DKBase::import('Notice', 'relation');
        return $this->notice->getReceivedFriendRequests($uid, $offset, $limit);
    }

    /**
     * 获取收到的好友请求数量
     * @param type $uid 用户ID
     * @return type 
     */
    public function getNumOfReceivedFriendRequests($uid) {
        $this->notice = DKBase::import('Notice', 'relation');
        return $this->notice->getNumOfReceivedFriendRequests($uid);
    }

    /**
     * 删除用户收到的好友请求
     * @param type $uid 用户ID
     * @param type $uid2 发送请求的用户ID
     * @return bool
     */
    public function deleteFriendRequest($uid, $uid2) {
        if (empty($uid) || empty($uid2)) {
            return false;
        }
        $this->notice = DKBase::import('Notice', 'relation');
        return $this->notice->deleteFriendRequest($uid2, $uid);
    }

    /**
     * 用户之间的好友请求关系
     * @param type $uid 用户的ID
     * @param type $to_uid 接收用户请求的用户的ID
     * @param type $both 是否按双方关系进行判断，true 按双方关系， false 按单方关系
     */
    public function hasRequested($uid, $uid2, $isBoth = true) {
        $this->notice = DKBase::import('Notice', 'relation');
        if ($isBoth) {
            return $this->notice->hasPostRequest($uid, $uid2) || $this->notice->hasPostRequest($uid2, $uid);
        }
        return $this->notice->hasPostRequest($uid, $uid2);
    }

    /**
     * 发送好友请求
     * @param type $uid 用户的ID
     * @param type $to_uid 接收请求用户的ID
     * @return bool/int 执行结果：true 成功,false 失败,1 不能加自己为好友,2 不是相互关注关系 ,3 已发送过好友请求 
     */
    public function makeFriend($uid, $to_uid) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        return $this->friend->makeFriendsWith($uid, $to_uid) ? 1 : 0;
    }

    /**
     * 接受好友请求
     * @param type $uid 用户的ID
     * @param type $from_uid 发送请求用户的ID
     * @return type 
     */
    public function approveFriendRequest($uid, $from_uid) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        $res = $this->friend->approveFriendRequest($uid, $from_uid);
        if ($res) {
            $datas = $this->processUserData($uid, $from_uid);
            $datas = $this->processTimeOfBeFriend($uid, $from_uid, $datas);

            try {
                $peopleSearchApi = DKBase::import('PeopleSearch');
                $peopleSearchApi->makeFriendWithSomeone($datas['operator'], $datas['aim']);
            } catch (Exception $e) {
                
            }
        }
        return $res ? 1 : 0;
    }

    /**
     * 添加好友
     * @param type $uid     用户ID
     * @param type $to_uid  目标用户ID
     * @return mix          关系状态，0 
     */
    public function addFriend($uid, $to_uid) {
        if (empty($uid) || empty($to_uid) || $uid == $to_uid) {
            return 0;
        }

        $relationStatus = $this->getRelationStatus($uid, $to_uid);
        if ($this->isFriend($uid, $to_uid)) {
            return 0 - $relationStatus;   //已经是好友
        }

        $this->notice = DKBase::import('Notice', 'relation');
        //我是否有未接受的请求
        if ($this->notice->hasPostRequest($to_uid, $uid)) {
            //有请求，接受请求
            if ($this->approveFriendRequest($uid, $to_uid)) {
                return $this->getRelationStatus($uid, $to_uid);
            }
            return 0;
        }

        if ($this->notice->hasPostRequest($uid, $to_uid)) {
            return 0 - $relationStatus;  //已经发送好友请求
        } else if (!$this->isBothFollow($uid, $to_uid)) {
            return 0 - $relationStatus;  //不满足相互关注的关系
        }

        //没有发送则发送好友请求，2表示请求发送成功
        if ($this->makeFriend($uid, $to_uid)) {
            return $this->getRelationStatus($uid, $to_uid);
        }
        return 0;
    }

    /**
     * 添加用户关注
     * @param type $uid 用户的ID
     * @param type $to_uid 被关注用户的ID
     * @return mix 关系状态，0
     * @ok
     */
    public function follow($uid, $to_uid, $max_count = 200) {
        if (empty($uid) || empty($to_uid) || $uid == $to_uid) {
            return 0;
        }

        if ($this->getNumOfFollowings($uid) >= $max_count) {
            return -1;  //已达到关注上限，关注失败
        }

        $relationStatus = $this->getRelationStatus($uid, $to_uid);
        //已关注了该用户
        if ($this->isFollowing($uid, $to_uid)) {
            return 0 - $relationStatus;
        }

        $this->following = DKBase::import('RelationFollowing', 'relation');
        $res = $this->following->follow($uid, $to_uid);
        $relationStatus = $this->getRelationStatus($uid, $to_uid);
        $relation = $res ?  $relationStatus : 0;

        //Update search index
        if ($res) {

            $is_bothfollow = FALSE;
            if ($relationStatus == self::BOTH_FOLLOWING) {
                $is_bothfollow = TRUE;
            }

            $datas = $this->processUserData($uid, $to_uid);
            $datas = $this->processTimeOfFollowed($uid, $to_uid, $is_bothfollow, $datas);

            try {
                $peopleSearchApi = DKBase::import('PeopleSearch', 'search');
                $peopleSearchApi->addFollowing($datas['operator'], $datas['aim'], $is_bothfollow);
            } catch (Exception $e) {
                
            }
        }

        return $relation;
    }

    /**
     * 添加批量关注
     * @param type $uid 用户ID
     * @param type $to_ids 被关注的用户ID列表
     * @return bool/array 成功返回空数组，失败返回false或关注失败的用户ID列表
     */
    public function fastFollow($uid, $to_uids) {
        if (empty($uid) || empty($to_uids) || !is_array($to_uids)) {
            return false;
        }

        $failed_uids = array();
        foreach ($to_uids as $to_uid) {
            if ($this->follow($uid, $to_uid) <= 0) {
                $failed_uids[] = $to_uid;
            }
        }
        return empty($failed_uids) ? true : $failed_uids;
    }

    /**
     * @abstract 更新对个人的关注时间 |同时对好友联系时间更新add 2012/8/1
     * --------------------------------------------------------------------------------------------
     * 包括:评论，赞，转发，留言，相册，视频，日志，问答，活动，通知，发信息，加好友 ，网页对话IM,访问主页
     * --------------------------------------------------------------------------------------------
     * @author boolee 2012/7/2
     * @param $uid   
     * @param $to_uid
     * @param $timestamp int
     * @param $accesstype varchar
     * */
    public function updateFollowTime($uid, $to_uid, $timestamp = NULL, $accesstype = NULL) {
        $this->following = DKBase::import('RelationFollowing', 'relation');
        $this->friend	 = DKBase::import('RelationFriend', 'relation');
        $following = $this->following->updateFollowTime($uid, $to_uid, $timestamp, $accesstype);
        $friend	   = $this->friend->updateFriendTime($uid, $to_uid, $timestamp, $accesstype);
        return $following || $friend;
    }

    public function handleFollow($uid, $uid2) {
        if (!$this->isFollowing($uid, $uid2)) {
            if ($this->follow($uid, $uid2)) {
                return $this->getRelationWithUser($uid, $uid2);
            }
        }
        return false;
    }

    /**
     * 删除好友
     * @param type $uid     用户的ID
     * @param type $uid2    好友的ID
     * @return mix          关系状态，0 
     */
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
            $timelineApi = DKBase::import('Timeline');
            $timelineApi->delRelationsTopic($uid, $uid2, 1);
        } catch (Exception $e) {
            
        }

        $this->friend = DKBase::import('RelationFriend', 'relation');
        $res = $this->friend->deleteFriend($uid, $uid2);
        $relation = $res ? $this->getRelationStatus($uid, $uid2) : 0;

        if ($res) {
            //清除索引关系数据
            try {
                $peopleSearchApi = DKBase::import('PeopleSearch');
                $peopleSearchApi->deleteFriendById($uid, $uid2);
            } catch (Exception $e) {
                
            }
        }

        return $relation;
    }

    /**
     * 取消对目标用户的关注
     * @param type $uid     用户的ID
     * @param type $uid2    关注用户的ID
     * @return mix          关系状态，0 
     */
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
            $timelineApi = DKBase::import('Timeline');
            $timelineApi->delRelationsTopic($uid, $uid2, 4);
        } catch (Exception $e) {
            
        }

        $this->following = DKBase::import('RelationFollowing', 'relation');
        $res = $this->following->unFollow($uid, $uid2);
        $relation = $res ? $this->getRelationStatus($uid, $uid2) : 0;

        if ($res) {
            //清除索引关系数据
            try {
                $peopleSearchApi = DKBase::import('PeopleSearch');
                $peopleSearchApi->deleteFollowing($uid, $uid2);
            } catch (Exception $e) {
                
            }
        }

        return $relation;
    }

    /**
     * 用户隐藏某个好友，使这个好友在别人查看其好友列表时不可见
     * 
     * @param int $uid
     * @param int $friendId
     */
    public function hideFriend($uid, $friendId) {
        if (empty($uid) || empty($friendId) || $uid == $friendId) {
            return false;
        }

        $this->friend = DKBase::import('RelationFriend', 'relation');
        $res = $this->friend->hideFriend($uid, $friendId);
        if ($res) {
            $datas = $this->processUserData($uid, $friendId);
            $datas = $this->processTimeOfBeFriend($uid, $friendId, $datas);

            try {
                $peopleSearchApi = DKBase::import('PeopleSearch');
                $peopleSearchApi->hideFriend($datas['operator'], $datas['aim']);
            } catch (Exception $e) {
                
            }
        }
        return $res;
    }

    /**
     * 取消好友隐藏
     * @param type $uid 用户ID
     * @param type $friendId 好友ID
     * @return type 
     */
    public function unHideFriend($uid, $friendId) {
        if (empty($uid) || empty($friendId) || $uid == $friendId) {
            return false;
        }

        $this->friend = DKBase::import('RelationFriend', 'relation');
        $res = $this->friend->unHideFriend($uid, $friendId);
        if ($res) {
            $datas = $this->processUserData($uid, $friendId);
            $datas = $this->processTimeOfBeFriend($uid, $friendId, $datas);

            try {
                $peopleSearchApi = DKBase::import('PeopleSearch');
                $peopleSearchApi->unHideFriend($datas['operator'], $datas['aim']);
            } catch (Exception $e) {
                
            }
        }
        return $res;
    }

    /**
     * 用户隐藏某个关注，使这个关注对象在别人查看其关注列表时不可见
     * 
     * @param int $uid
     * @param int $followingId
     */
    public function hideFollowing($uid, $followingId) {
        if (empty($uid) || empty($followingId) || $uid == $followingId) {
            return false;
        }

        $this->following = DKBase::import('RelationFollowing', 'relation');
        $res = $this->following->hideFollowing($uid, $followingId);
        if ($res) {
            $is_bothfollow = $this->isBothFollow($uid, $followingId);
            $is_friend = $this->isFriend($uid, $followingId);

            $datas = $this->processUserData($uid, $followingId);
            $datas = $this->processTimeOfFollowed($uid, $followingId, $is_bothfollow, $datas);
            if ($is_friend) {
                $datas = $this->processTimeOfBeFriend($uid, $followingId, $datas);
            }

            try {
                $peopleSearchApi = DKBase::import('PeopleSearch');
                $peopleSearchApi->hideFollowing($datas['operator'], $datas['aim'], $is_bothfollow, $is_friend);
            } catch (Exception $e) {
                
            }
        }
        return $res;
    }

    /**
     * 取消关注隐藏
     * @param type $uid
     * @param type $followingId
     * @return type 
     */
    public function unHideFollowing($uid, $followingId) {
        if (empty($uid) || empty($followingId) || $uid == $followingId) {
            return false;
        }

        $this->following = DKBase::import('RelationFollowing', 'relation');
        $res = $this->following->unHideFollowing($uid, $followingId);
        if ($res) {
            $is_bothfollow = $this->isBothFollow($uid, $followingId);

            $datas = $this->processUserData($uid, $followingId);
            $datas = $this->processTimeOfFollowed($uid, $followingId, $is_bothfollow, $datas);

            try {
                $peopleSearchApi = DKBase::import('PeopleSearch');
                $peopleSearchApi->unHideFollowing($datas['operator'], $datas['aim'], $is_bothfollow);
            } catch (Exception $e) {
                
            }
        }
        return $res;
    }

    // #######################################################################

    /**
     * 设置用户简要信息
     * 信息格式为 array('uid' => 'user id', 'uname' => 'user name', 'dkcode' => 'duankou num', 'sex' => 'sex num')
     * @param type $data 用户信息, 
     * @return type 
     */
    public function setUserInfo($data = array()) {
        if (empty($data)) {
            return false;
        }
        return $this->fastUser->setShortInfo($data);
    }

    /**
     * 获取用户简要信息
     * @param type $uid 用户ID
     * @return type 
     */
    public function getUserInfo($uid, $fields = array()) {
        $this->fastUser = DKBase::import('FastUser', 'user');
        return $this->fastUser->getShortInfo($uid, $fields);
    }

    /**
     * 获取多个目标用户的简要信息
     * @param type $uids    目标用户ID集合
     * @return type 
     */
    public function getMultiUserInfo($uids, $fields = array()) {
        $results = array();
        foreach ($uids as $uid) {
            $results[] = $this->getUserInfo($uid, $fields);
        }
        return $results;
    }

    //组装用户信息
    private function processUserData($operator_id, $aim_id) {
        $operator = $this->getUserInfo($operator_id);
        $operator_followers = $this->getNumOfFollowers($operator_id);
        $aim_user = $this->getUserInfo($aim_id);
        $aim_user_followers = $this->getNumOfFollowers($aim_id);
        $operator['follower_num'] = $operator_followers;
        $aim_user['follower_num'] = $aim_user_followers;
        return array(
            'operator' => $operator,
            'aim' => $aim_user
        );
    }

    //组装关注时间
    private function processTimeOfFollowed($operator_id, $aim_id, $is_bothfollow, $datas) {
        $this->follower = DKBase::import('RelationFollower', 'relation');
        $be_following_time = $this->follower->getTimeOfFollowed($aim_id, $operator_id);
        $datas['operator']['time'] = $be_following_time;
        if ($is_bothfollow) {
            $be_follower_time = $this->follower->getTimeOfFollowed($operator_id, $aim_id);
            $datas['aim']['time'] = $be_follower_time;
        }
        return $datas;
    }

    //组装成为好友的时间
    private function processTimeOfBeFriend($operator_id, $aim_id, $datas) {
        $this->friend = DKBase::import('RelationFriend', 'relation');
        $be_friend_time = $this->friend->getTimeOfBeFriend($operator_id, $aim_id);
        $datas['operator']['frd_time'] = $be_friend_time;
        return $datas;
    }

	/**
	 * 获取用户关系数
	 *
	 * 获取用户的关注数(个人+网页), 粉丝数, 好友数
	 * 用户看他人的关系数, 需要隐藏他人设置隐藏的用户
	 *
	 * @param int|array $uids 需要获取关系数的用户UID 
	 * @param int $visituid 访问者用户UID
	 * 
	 * @return array
	 *
	 * @history boolee 2012/7/12
	 */
    public function getRelationNums($uids, $visituid) {

		if (empty($uids) || empty($visituid)) { return array(); }

		if (!is_array($uids)) $uids = array($uids);

		$webpageRelationApi = DKBase::import('WebpageRelation');

		foreach ($uids as $v) {

			// 用户看自己的关系数
			$is_self = TRUE;

			if ($v != $visituid) {
				// 用户看他人的关注数
				$is_self = FALSE;
			}

			// 关注数(人+网页)
			$result[$v]['following'] = $this->getNumOfFollowings($v, $is_self, $visituid) + $webpageRelationApi->getNumOfFollowings($v, $is_self);

			// 粉丝数
			$result[$v]['follower'] = $this->getNumOfFollowers($v, $is_self, $visituid);

			// 好友数
			$result[$v]['friend'] = $this->getNumOfFriends($v, $is_self, $visituid);

		}

		return $result;
    }

    //批量获取一个人对多个网页关注的剩余时间 addby boolee 2012/7/14
    public function getMultiExpiry($uid, $web_ids) {
        if (!$uid || !$web_ids) return false;
            
        $return = array();
		$this->following = DKBase::import('RelationFollowing', 'relation');
        foreach ($web_ids as $web_id) {
            $day = $this->following->getWebpageFollowingDetail($uid, $web_id);
            $day = json_decode($day, 1);
            if (!isset($day['expiry_time'])) {
                $return[$web_id]['relation'] = 2;  //无关系
                $return[$web_id]['days'] = ceil(config_item('default_follow_expiry_time') / 86400);  //默认时间
            } elseif ($day['expiry_time'] == -1) {
                $return[$web_id]['relation'] = 6;     //永久
                $return[$web_id]['days'] = ceil(config_item('default_follow_expiry_time') / 86400);  //默认时间
            } else {
                $last = $day['expiry_time'] + $day['action_time'] - time();
                if ($last > 0) {
                    $return[$web_id]['relation'] = 4;  //剩余时间
                    $return[$web_id]['days'] = ceil($last / 86400);
                } else {
                    $return[$web_id]['relation'] = 8;  //剩余时间
                    $return[$web_id]['days'] = ceil($day['expiry_time'] / 86400);
                }
            }
        }
        return $return;
    }

    /**
     * 批量获取用户和他人的共同好友ID
     *
     * 暂时只用于搜索
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @history <boolee><2012/7/16>
     *
     * @param int $uid 用户UID
     * @param array $uids 用户UID数组
     *
     * @return array
     */
    public function getMultiCommonFriends($uid, $uids) {
        $commonFriendUids = array();
        foreach ($uids as $v) {
            $commonFriendUids[$v] = $this->getCommonFriends($uid, $v);
        }
        return $commonFriendUids;
    }

    /**
     * 获取用户和他人共同关注的个人
     *
     * 用于关系列表中的共同信息显示
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @history <boolee><7/16>
     *
     * @param int $uid 用户1的UID
     * @param int $uid2 用户2的UID
     *
     * @return array
     */
    public function getCommonFollowingsInfo($uid, $uid2) {

        // 是否是自己
        $is_self = $uid == $uid2 ? TRUE : FALSE;

        $uids = $this->getCommonFollowings($uid, $uid2, $is_self);
        $return =array();
        foreach ($uids as $kuid){
            $users = service('User')->getShortInfoByIds(array($kuid));
            $return[$kuid]['username'] = $users[0]['name'];
            $return[$kuid]['dkcode']   = $users[0]['dkcode'];
        }
        
        return $return;
    }

    /**
     * 获取用户和他人共同的好友
     *
     * 用于关系列表中的共同信息显示
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @history <boolee><7/16>
     *
     * @param int $uid 用户1的UID
     * @param int $uid2 用户2的UID
     *
     * @return array
     */
    public function getCommonFriendsInfo($uid, $uid2) {

        // 是否是自己
        $is_self = $uid == $uid2 ? TRUE : FALSE;

        $uids = $this->getCommonFriends($uid, $uid2, $is_self);

        $return =array();
        foreach ($uids as $uid){
        	$users = service('User')->getShortInfoByIds(array($uid));
        	$return[$uid]['username'] = $users[0]['name'];
       		$return[$uid]['dkcode']   = $users[0]['dkcode'];
			$return[$uid]['uid']   	  = $uid;
        }
        
        return $return;
    }

    /**
     * 获取用户和他人共同关注的兴趣(网页)
     *
     * 用于关系列表中的共同信息显示
     *
     * @author zengmm
     * @date 2012/7/26
     *
     * @param int $uid 用户1的UID
     * @param int $uid2 用户2的UID
     *
     * @return array
     */
    public function getCommonFollowingWebpage($uid, $uid2)
    {
        // 是否是自己
        $is_self = $uid == $uid2 ? TRUE : FALSE;

        $webpageRelationApi = DKBase::import('WebpageRelation');

        return $webpageRelationApi->getCommonFollowingsInfo($uid, $uid2, $is_self);
    }

    public function getCommonRelationInfo($relationStatus, $visituid)
    {

        $commonRelationInfo = array();

        foreach ($relationStatus as $k => $v) {
            
            $uid = (int) end(explode('u', $k));

            if ($v == 2) {

                $commonRelationInfo[$k]['data'] = $this->getCommonFollowingsInfo($visituid, $uid);
                
            } elseif ($v > 2 && $v < 10) {

                $commonRelationInfo[$k]['data'] = $this->getCommonFriendsInfo($visituid, $uid);

            } elseif ($v == 10) {

                $commonRelationInfo[$k]['data'] = $this->getCommonFollowingWebpage($visituid, $uid);
            }

            if ($v > 0) {
                $commonRelationInfo[$k]['relation'] = $v;
            }
        }

        return $commonRelationInfo;
    }
    /**
	 * 隐藏和取消隐藏网页关注分类
	 * @author boolee 2012/8/3
	 * @param $uid
	 * @param $webpage
	 * @param $is_hideen 是否进行隐藏
	 * @return boolean
	 */
    function categoryHidden($uid, $webpage, $is_hideen){
		return $this->following->categoryHidden($uid, $webpage, $is_hideen);
    }
}