<?php
/**
 * 好友模型
 *
 * @author zengmm
 * @date 2012/7/25
 *
 * @history <yaohaiqi><2012-03-02>
 */
class FriendModel extends MY_Model {


	/**
	 * 组装用户列表
	 *
	 * @author zengmm
	 * @date 2012/7/23
	 *
	 * @param array $userlist 用户列表
	 * @param int $visituid 访问住UID 
	 *
	 * @return array
	 */
	private function _combineUserinfo($userlist, $visituid)
	{
		if (empty($userlist) || empty($visituid)) { return array(); }

		// 用户UID集合
		$uids = array();
		foreach ($userlist as $v) {
			$uids[] = $v['id'];
		}

		// 获取用户的关系
		$relation = service('Relation')->getMultiRelationStatus($visituid, $uids);

        // 获取共同关注个人、网页, 共同好友等信息
        // 此处的实现方式有待优化
        $CI =& get_instance();
        $CI->load->model('relationmodel');
        $commoninfo = $CI->relationmodel->getCommonInfo($relation, $visituid);

		// 获取用户的关注、粉丝、好友数
		$relation_count = service('Relation')->getRelationNums($uids, $visituid);

		// 获取用户的现居地
        $tmpuids = array();
        foreach ($uids as $v) {
            if (isset($relation['u' . $v])) {
                $tmpuids[$v] = $relation['u' . $v];
            }
        }
        $permission = service('UserWiki')->getPermission($tmpuids, $visituid);
        $uids = array();
        if ($permission) {
            foreach ($permission as $k=>$v) {
                if ($v['base']) {
                    $uids[] = $k;
                }
            }
        }
        $now_addr = array();
        if ($uids) {
            $userinfo = service('user')->getUserList($uids);
            if ($userinfo) {
                foreach ($userinfo as $v) {
                    if (isset($v['now_addr'])) {
                        $address = $v['now_addr'];
                        $tmp = explode(' ', $address);
                        array_shift($tmp);
                        $now_addr[$v['uid']] = implode(' ', $tmp);
                    }
                }
            }
        }

		foreach ($userlist as &$v) {

			if (isset($relation['u' . $v['id']])) {
				$v['relation'] = $relation['u' . $v['id']];
			}

			if (isset($relation_count[$v['id']])) {
				$v['following'] = $relation_count[$v['id']]['following'];
				$v['follower'] = $relation_count[$v['id']]['follower'];
				$v['friend'] = $relation_count[$v['id']]['friend'];
			}

			if (isset($now_addr[$v['id']])) {
				$v['now_addr'] = $now_addr[$v['id']];
			} else {
				$v['now_addr'] = '';
			}

			// 用户隐藏标识重置(方便前端代码复用)
			// 只有用户自己才可以操作隐藏功能
			if (isset($v['type'])) {
				// 搜索提供的隐藏字段转换
				$v['hidden'] = $v['type'];
				unset($v['type']);
			}

            if (isset($commoninfo[$v['id']])) {
                $v['display'] = $commoninfo[$v['id']];
            } else {
                $v['display'] = '';
            }
		}

		return $userlist;
	}

	/**
     * 获取用户的好友数量
     *
     * @author zengmm
     * @date 2012/7/23
     *
     * @history <yaohaiqi><2012-03-02>
     *
     * @param int $uid 用户UID
     * @param int $visituid 访问者UID
     * @param boolean $is_self 是否是自己
     * 
     * @return int
     */
    public function getNumOfFriends($uid, $visituid, $is_self) {
        $friend_count = service('Relation')->getNumOfFriends($uid, $is_self, $visituid);
        return $friend_count;
    }
    
    /**
     * 获取用户的好友列表 - 包含用户信息
     *
     * @author zengmm
     * @date 2012/7/23
     *
     * @history <yaohaiqi><2012-03-02>
     * 
     * @param int $uid 用户UID
     * @param int $visituid 访问者UID
     * @param boolean $is_self 是否是自己
     * @param int $page 页码
     * @param int $limit 每页数量
     *
     * @return array
     */
    public function getFriendsWithInfo($uid, $visituid, $is_self, $page = 1, $limit = 20) {

        $friend_userlist = service('Relation')->getFriendsWithInfo($uid, $is_self, $page, $limit, $visituid);

        if ($friend_userlist) {
        	$friend_userlist = $this->_combineUserinfo($friend_userlist, $visituid);
        }

		return $friend_userlist;
    }

    /**
     * 通过姓名查找好友 - 包含用户信息
     *
     * @author zengmm
     * @date 2012/7/23
     *
     * @history <yaohaiqi><2012-03-02>
     * 
     * @param int $uid 用户UID
     * @param int $keyword 搜索关键字
     * @param int $page 页码
     * @param int $limit 每页数量
     *
     * @return array
     */
    public function getFriendByName($uid, $keyword='', $page=1, $limit=20) {
        
		$friend_userlist = service('PeopleSearch')->getFriendsReturnJSON($uid, $keyword, $page, $limit);
		$friend_userlist = json_decode($friend_userlist, TRUE);

		if ($friend_userlist['total'] > 0) {

			// 统一数据格式
			$friend_userlist['data'] = $friend_userlist['object'];
			unset($friend_userlist['object']);

			$friend_userlist['data'] = $this->_combineUserinfo($friend_userlist['data'], $uid);

		} else {

			$friend_userlist['data'] = array();
		}

        return $friend_userlist;
	}

	/**
     * 隐藏某个好友，使这个好友在别人查看其好友列表时不可见
     *
     * @param int $uid 用户UID
     * @param int $fid 好友UID
     *
     * @return boolean
     */
    public function hideFriend($uid, $fid) {
        return service('Relation')->hideFriend($uid, $fid);
    }

    /**
     * 取消对好友隐藏
     *
     * @param int $uid 用户UID
     * @param int $fid 好友UID
     *
     * @return boolean
     */
    public function unHideFriend($uid, $fid) {
        return service('Relation')->unHideFriend($uid, $fid); 
    }

    /**
     * 判断当前用户是否已隐藏好友
     *
     * @param int $uid 用户UID
     * @param int $fid 好友UID
     *
     * @return boolean
     */        
    public function hiddenStatus($uid, $fid) {
       return service('Relation')->isHiddenFriend($uid, $fid); 
    }
        
    /**
     * 获取指定用户的全部好友
     *
     * 暂时用于权限的自定义弹出框
     *
     * @param $uid 指定用户的id
     *
     * @return array
     */
    public function getAllFriendsByUid($uid){
    	$friends = service('Relation')->getAllFriendsWithInfo($uid);
    	return $friends;
    }
	/**
	 * 获取个人失效好友数
	 * @author boolee 7/27
	 */
    public function getNumOfInvalidateFriends($uid){
    	return service('Relation')->getNumOfInvalidateFriends($uid);
    } 
	/**
	 * 获取个人失效好友列表
	 * @author boolee 7/27
	 */
    public function getInvalidateFriendsWithInfo($uid, $is_self, $page, $limit, $action_uid){
    	$friend_userlist = service('Relation')->getInvalidateFriendsWithInfo($uid, $is_self, $page, $limit, $action_uid);
    	if ($friend_userlist) {
        	$friend_userlist = $this->_combineUserinfo($friend_userlist, $uid);
        }

		return $friend_userlist;
    }

    /**
     * 获取最新的好友
     *
     * 用于个人首页好友列表
     *
     * @author zengmm
     * @date 2012/7/31
     *
     * @param int $uid 用户UID
     * @param int $offset 起始值
     * @param int $limit 偏移量
     *
     */
    public function getNewestFriend($uid, $offset, $limit)
    {
        return service('Relation')->getFriendsWithInfoByOffset($uid, true, $offset, $limit, $uid);
    }
}