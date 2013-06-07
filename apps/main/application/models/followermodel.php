<?php

/**
 * 粉丝模型
 *
 * @author zengmm
 * @date 2012/7/25
 *
 * @history <yaohaiqi><2012-03-01>
 */
class FollowerModel extends MY_Model {

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

			if (isset($commoninfo[$v['id']])) {
				$v['display'] = $commoninfo[$v['id']];
			} else {
				$v['display'] = '';
			}
		}

		return $userlist;
	}

	/**
     * 获取粉丝数量
     *
     * @author zengmm
     * @date 2012/7/23
     *
     * @history <yaohaiqi><time>
     *
     * @param int $uid 用户UID
     *
     * @return int
     */
    public function getNumOfFollowers($uid)
    {
    	if (empty($uid)) { return 0; }

        return service('Relation')->getNumOfFollowers($uid);
	 }
        
    /**
     * 获取粉丝信息
     *
     * return array(id,name,dkcode,relation,following,follower,friend,now_addr)
     * 用户uid,用户名,端口号,访问住与用户的关系值,用户的关注数,用户的粉丝数,用户的好友数,用户的现居地
     *
     * @author zengmm
     * @date 2012/7/25
     *
     * @history <yaohaiqi><2012-03-01>
     *
     * @param int $uid 用户UID
     * @param int $page 页码
     * @param int $limit 偏移量/每页显示数量
     * @param int $visituid 访问住UID
     *
     * @return array
     */
    public function getFollowersWithInfo($uid, $page, $limit, $visituid) {
        
        $follower_userlist = service('Relation')->getFollowersWithInfo($uid, $page, $limit);

        if ($follower_userlist) {
        	$follower_userlist = $this->_combineUserinfo($follower_userlist, $visituid);
        }

        return $follower_userlist;
	}

	/**
	 * 通过姓名获取粉丝
	 *
	 * @author zengmm
	 * @date 2012/7/25
	 *
	 * @history <yaohaiqi><2012-03-01>
	 *
	 * @param int $uid 用户UID
	 * @param string $keyword 搜索关键字
	 * @param int $page 页码
	 * @param int $limit 偏移量/每页显示数量
	 * @param int $visituid 访问者UID
	 *
	 * @return array
	 */
    public function getFollowersByName($uid, $keyword, $page, $limit, $visituid) {
        
		$follower_userlist = service('PeopleSearch')->getFollowersReturnJSON($uid, $keyword, $page, $limit);

		$follower_userlist = json_decode($follower_userlist, TRUE);

		if ($follower_userlist['total'] > 0) {

			// 统一数据格式
			$follower_userlist['data'] = $follower_userlist['object'];
			unset($follower_userlist['object']);

			$follower_userlist['data'] = $this->_combineUserinfo($follower_userlist['data'], $visituid);

		} else {

			$follower_userlist['data'] = array();
		}
        
        return $follower_userlist;
	}
}
