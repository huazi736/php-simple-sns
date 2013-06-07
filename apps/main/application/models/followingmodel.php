<?php
/**
 * 个人关注列表模型
 *
 * @author zengmm
 * @date 2012/7/23
 * 
 * @history <boolee><2012/7/7> & <lanyanguang><2012-03-02>
 */

class FollowingModel extends MY_Model {

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
		foreach ($userlist as $k=>$v) {
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
	 * 组合关注的网页信息
	 *
	 * @author zengmm
	 * @date 2012/7/23
	 *
	 * @param array $webpagelist 关注的网页列表
	 * @param int $visituid 访问住UID
	 *
	 * @return array
	 */
	private function _combineWebpageList($webpagelist, $visituid) {

		if (empty($webpagelist) || empty($visituid)) { return array(); }

		$webpage_ids = array();

		foreach ($webpagelist as $v) {
			$webpage_ids[] = $v['aid'];
		}

		// 获取网页的粉丝数
		$webpage_follower = service('WebpageRelation')->getMultiNumOfFollowers($webpage_ids);

		// 获取网页的摘要
		$webpage_notes = service('Webwiki')->getWebDesc($webpage_ids);

		// 获取对网页关注时间
		$days = api('webpageRelation')->getMultiExpiry($visituid, $webpage_ids);

		foreach ($webpagelist as &$v) {

			// 关注时效
			$v['days'] = $days[$v['aid']];

			// 网页粉丝
			$v['follower'] = $webpage_follower['p' . $v['aid']];

			// 网页摘要
			if (isset($webpage_notes[$v['aid']])) {
				$v['notes'] = $webpage_notes[$v['aid']];
			} else {
				$v['notes'] = '';
			}
			// 用户隐藏标识重置(方便前端代码复用)
			// 只有用户自己才可以操作隐藏功能
			if(isset($v['is_display'])){
				$v['hidden'] = $v['is_display'] == 1 ? 0 : 1;
				unset($v['is_display']);
			}
			// 搜索提供的隐藏字段转换
			if (isset($v['type'])) {
				$v['hidden'] = $v['type'];
				unset($v['type']);
			}

		}

		return $webpagelist;
	}

	/**
     * 获得用户关注的个人数量
     * 
	 * @author zengmm
	 * @date 2012/7/24
	 * 
	 * @history <lanyanguang><2012-03-01>
     *  
     * @param int $uid 用户UID
     * @param boolean $is_self 是否是自己
     * @param int $visituid 访问者UID
	 *
	 * @return int
     */
    function getNumOfFollowings($uid, $self, $visituid) {
        $followings_count = service('Relation')->getNumOfFollowings($uid, $self, $visituid);
        return $followings_count;
    }

	/**
     * 获取用户关注个人列表
     * 
	 * 偏移量方式
	 * 暂时只用于首页的关注列表
	 *
	 * @author zengmm
	 * @date 2012/7/24
	 *
	 * @history <lanyanguang><2012-03-01>
     * 
     * @param int $uid 用户UID
     * @param boolean $is_self 是否自己
     * @param int $offset 起始值
     * @param int $limit 偏移量
	 * @param int $visituid 访问者UID
	 *
     * @return array
     */
    public function getFollowingsWithInfoByOffset($uid, $is_self, $offset, $limit, $visituid) {
        $following_userlist = service('Relation')->getFollowingsWithInfoByOffset($uid, $is_self, $offset, $limit, $visituid);
		return $following_userlist;
    }

    /**
     * 获取用户的关注个人列表
     * 
	 * @author zengmm
	 * @date 2012/7/23
	 *
	 * @history <lanyanguang><2012-03-01>
	 *
     * @param int $uid 用户uid
     * @param boolean $is_self 是否自己
     * @param int $offset 页码
     * @param int $limit 偏移量
	 * @param int $visituid 访问者UID
	 *
     * @return array
     */
    public function getFollowingsWithInfo($uid, $is_self, $offset, $limit, $visituid) {
        
		$following_userlist = service('Relation')->getFollowingsWithInfo($uid, $is_self, $offset, $limit, $visituid);

		if ($following_userlist) {
			$following_userlist = $this->_combineUserinfo($following_userlist, $visituid);
		}

        return $following_userlist;
    }
    /**
     * @author boolee 2012/7/25
     * @abstract 获取无效个人关注数据详表
     * @param uid
     */
	public function getInvalidateFollowingsWithInfo($uid, $is_self, $offset, $limit, $visituid) {
        
		$following_userlist = service('Relation')->getInvalidateFollowingsWithInfo($uid, $is_self, $offset, $limit);

		if ($following_userlist) {
			$following_userlist = $this->_combineUserinfo($following_userlist, $visituid);
		}

        return $following_userlist;
    }
	/**
     * 通过用户名查找关注用户
     * 
	 * @author zengmm
	 * @date 2012/7/24
	 * 
	 * @history <lanyanguang><2012-03-01>
	 * 
     * @param string $uid 用户UID
     * @param string $keyword 搜索用户名
     * @param int $offset 页码
     * @param int $limit 偏移量
	 * 
     * @return array
     */
    public function getFollowingsByUsername($uid, $keyword, $offset, $limit) {
        
        $following_userlist = service('PeopleSearch')->getFollowingReturnJSON($uid, $keyword, $offset, $limit);
        $following_userlist = json_decode($following_userlist, TRUE);
		
        if ($following_userlist['total'] > 0) {

			// 统一数据格式
			$following_userlist['data'] = $following_userlist['object'];
			unset($following_userlist['object']);

			$following_userlist['data'] = $this->_combineUserinfo($following_userlist['data'], $uid);
        } else {

        	$following_userlist['data'] = array();
        }

        return $following_userlist;
    }

	/**
     * 获得用户关注分类
     * 
	 * @author zengmm
	 * @date 2012/7/24
	 *
	 * @history <lanyanguang><2012-04-24>
	 *
     * @param int $uid 用户UID
     * @param boolean $is_self 是否自己
	 *
	 * @return array
     */
    function getWebFollowingCategory($uid, $is_self = TRUE, $channel_id = 0) {
        $result = service('Attention')->get_attention_category($uid, $is_self, $channel_id);
        return $result;
    }

	/**
	 * 获取个人关注的所有网页
	 *
	 * @author zengmm
	 * @date 2012/7/13
	 *
	 * @param int $uid 用户uid
     * @param boolean $is_self 是否自己
     * @param int $offset 页码
     * @param int $limit 偏移量
	 * @param int $visituid 访问者UID
	 *
	 * @return array
	 */
	public function getFollowingWebpages($uid, $is_self, $offset, $limit, $visituid) {

		$webpage_list = array();

		// 获取用户关注的网页数
		$webpage_count = service('WebpageRelation')->getNumOfFollowings($uid, $is_self);

		// 获取用户关注的网页ID
		$webpage_ids = service('WebpageRelation')->getFollowings($uid, $is_self, $offset, $limit);

		if ($webpage_ids) {

			// 获取网页信息
			$webpage_infos = service('Interest')->get_web_info($webpage_ids);

			// 获取用户对网页是否隐藏
			$is_display = array();
			if ($is_self) {
				$is_display = service('Attention')->getWebpageHiddenStatus($uid, $webpage_ids);
			}

			foreach ($webpage_infos as $v) {

				// 网页创建者UID
				$tmp['web_uid'] = $v['uid'];
				// 网页ID
				$tmp['aid'] = $v['aid'];
				//  网页名
				$tmp['name'] = $v['name'];
				
				if ($is_self) {
					if (isset($is_display[$v['aid']])) {
						$tmp['is_display'] = $is_display[$v['aid']];
					} else {
						$tmp['is_display'] = 1;
					}
				}

				$webpage_list[] = $tmp;
			}

			if ($webpage_list) {
				$webpage_list = $this->_combineWebpageList($webpage_list, $visituid);
			}	
		}

		return array('total'=>$webpage_count, 'data'=>$webpage_list);
	}

	/**
	 * 获取用户关注的网页
	 *
	 * 根据网页所属的频道
	 *
	 * @author zengmm
	 * @date 2012/8/6
	 *
	 * 
	 */
	public function getWebpagesByChannel($uid, $channel_id, $is_self, $offset, $limit, $visituid)
	{
		$webpage_list = service('Attention')->get_attention_web($uid , $channel_id , $is_self , $offset , $limit, $visituid, TRUE);

		if ($webpage_list['ct'] > 0) {
			$webpage_list['data'] = $this->_combineWebpageList($webpage_list['data'], $visituid);
		}

		// 统一数据格式
		$webpage_list['total'] = $webpage_list['ct'];
		unset($webpage_list['ct']);
		
        return $webpage_list;
	}

	/**
     * 获取网页分类下用户关注的网页
     *
	 * @author zengmm
	 * @date 2012/7/23
	 * 
	 * @history <lanyanguang><2012-04-24>
	 *
     * @param int $uid 被访问者UID
     * @param int $iid 网页分类ID
     * @param boolean $is_self 是否是自己(用于是否隐藏关注对象功能)
     * @param int $offset 页码
     * @param int $limit 偏移量
     * @param int $visituid 访问者UID
	 *
     * @return array
     */
    public function getWebpagesByWebcate($uid, $iid, $is_self, $offset, $limit, $visituid) {
        
        $webpage_list = service('Attention')->get_attention_web($uid , $iid , $is_self , $offset , $limit, $visituid);

		if ($webpage_list['ct'] > 0) {
			$webpage_list['data'] = $this->_combineWebpageList($webpage_list['data'], $visituid);
		}

		// 统一数据格式
		$webpage_list['total'] = $webpage_list['ct'];
		unset($webpage_list['ct']);
		
        return $webpage_list;
    }

	/**
     * 通过用户名查找关注用户
     * 
	 * @author zengmm
	 * @date 2012/7/24
	 * 
	 * @history <lanyanguang><2012-05-04>
     * 
     * @param string $uid 用户uid
     * @param string $iid 网页二级分类id
     * @param string $keyword 网页名关键字
     * @param int $offset 页码
     * @param int $limit 偏移量
	 * 
     * @return array
     */
    public function getWebpagesByUsername($uid, $iid, $keyword, $offset, $limit) {
        
		$webpage_list = service('WebpageSearch')->getWebpagesByUser($uid, $iid, $keyword, $offset, $limit);

        $webpage_list = json_decode($webpage_list, TRUE);

		if ($webpage_list['total'] > 0) {

			// 统一数据格式
			$webpage_list['data'] = $webpage_list['object'];
			unset($webpage_list['object']);

			foreach ($webpage_list['data'] as &$v) {

				// 网页创建者UID
				$v['web_uid'] = $v['creator_id'];
				// 网页ID
				$v['aid'] = $v['id'];
			}

			$webpage_list['data'] = $this->_combineWebpageList($webpage_list['data'], $uid);
		} else {

			$webpage_list['data'] = array();
		}

		return $webpage_list;

	}

	/**
	 * 获取用户关注时效已过期的网页所属的网页分类
	 *
	 * @author zengmm
	 * @date 
	 *
	 * @param int $uid 用户UID
	 */
	public function getInvalidFollowingWebcate($uid) {

		if (empty($uid)) { return array(); }

		$validFollowingWebcate = service('Attention')->get_attention_invalid_category($uid);

		return $validFollowingWebcate ? $validFollowingWebcate : array();
	}

	/**
     * 获取用户关注失效的全部网页
     * 
     * @author boolee
     * @date 2012-07-20
	 *
     * @param string $uid 用户uid
	 * @param boolean $is_self 是否是自己
     * @param int $page 页码
     * @param int $limit 偏移量
	 * @param int $visituid 访问住UID
	 * 
     * @return array
     */
    public function getInvalidWebpage($uid, $is_self = TRUE, $page = 1, $limit = 20, $visituid = 0){

    	if (empty($uid)) { return array(); }

		$webpage_list = array();

		// 获取用户关注失效的网页数
		$webpage_count = service('WebpageRelation')->getNumOfUnValidateFollowings($uid, $is_self);

		// 获取用户关注失效的网页ID
		$webpage_ids = service('WebpageRelation')->getUnValidateFollowings($uid, $is_self, $page, $limit);

		if ($webpage_ids) {
			// 获取网页信息
			$webpage_infos = service('Interest')->get_web_info($webpage_ids);

			// 获取用户对网页是否隐藏
			$is_display = array();
			if ($is_self) {
				$is_display = service('Attention')->getWebpageHiddenStatus($uid, $webpage_ids);
			}

			foreach ($webpage_infos as $v) {

				// 网页创建者UID
				$tmp['web_uid'] = $v['uid'];
				// 网页ID
				$tmp['aid'] = $v['aid'];
				// 网页名
				$tmp['name'] = $v['name'];
				
				if ($is_self) {
					if (isset($is_display[$v['aid']])) {
						$tmp['is_display'] = $is_display[$v['aid']];
					} else {
						$tmp['is_display'] = 1;
					}
				}

				$webpage_list[] = $tmp;
			}

			if ($webpage_list) {
				$webpage_list = $this->_combineWebpageList($webpage_list, $visituid);
			}
    	}

		return array('total'=>$webpage_count, 'data'=>$webpage_list);
    }

	/**
     * 获得网页分类失效关注 
     * 
     * @author boolee
     * @date 2012-07-23
     * @param int $uid 被访问者UID
     * @param int $iid 网页ID
     * @param boolean $is_display 是否显示
     * @param int $start 起始值
     * @param int $limit 偏移量
     * @param int $visituid 访问者UID
     * @return void
     */
    public function getUnvalidateAttentionWeb($uid , $iid , $is_display , $start , $limit, $visituid) {

        $webpage_list = service('Attention')->get_unvalidate_attention_web($uid , $iid , $is_display , $start , $limit, $visituid);
        
		if ($webpage_list['ct'] > 0) {
			$webpage_list['data'] = $this->_combineWebpageList($webpage_list['data'], $visituid);
		}

		$webpage_list['total'] = $webpage_list['ct'];
		unset($webpage_list['ct']);
		
        return $webpage_list;
    }

	/**
     * 获得二级分类信息
	 *
	 * 返回的数据格式
	 * Array ( [iid] => 96 [iname] => 爱好市场 [iname_pinyin] => aihaoshichang [imid] => 1 [sort] => 19 
	 *[is_system] => 1 [is_list] => 1 [is_hot] => 0 [is_display] => 1 [stat] => 76 ) 
	 *
	 * @author zengmm
	 * @date 2012/7/24
	 * 
	 * @history <lanyanguang><2012/05/07>
	 * 	
     * @param int $iid 二级分类id
     * 
	 * @return array
     */
    public function get_iid_info($iid) {
         $webpage_info = service('Interest')->get_iid_info($iid);
		 return $webpage_info;
    }

    /**
     * 判断用户隐藏关系
     *
     * return true隐藏/false未隐藏
     * 
     * @author zengmm
     * @date 2012/7/24
     * 
     * @history <lanyanguang><2012-04-24>
     *  
     * @param int $uid1 用户UID
     * @param int $uid2 被隐藏用户UID
     * 
     * @return boolean
     */
    function isHiddenFollowing($uid1, $uid2) {

    	if (empty($uid1) || empty($uid2)) { return FALSE; }

        $result = service('Relation')->isHiddenFollowing($uid1, $uid2);

        return $result;
    }

	/**
     * 隐藏关注的用户
	 *
	 * return true成功|false失败
     *
	 * @author zengmm
	 * @date 2012/7/24
	 * 
	 * @history <lanyanguang><2012-03-01>
     * 
     * @param int $uid 用户UID
     * @param int $following_uid 关注的用户UID
	 *
     * @return boolean 
     */
    function hideFollowing($uid, $following_uid) {

		if (empty($uid) || empty($following_uid)) { return FALSE; }

        $result = service('Relation')->hideFollowing($uid, $following_uid);

        return $result;
    }

	/**
     * 取消隐藏关注的用户
     * 
	 * @author zengmm
	 * @date 2012/7/24
	 *
	 * @history <lanyanguang><2012-03-01>
	 *
     * @param int $uid 用户uid
     * @param int $following_uid 目标用户uid
	 *
     * @return boolean true成功|false失败
     */
    function unHideFollowing($uid, $following_uid) {

		if (empty($uid) || empty($following_uid)) { return FALSE; }

        $result = service('Relation')->unHideFollowing($uid, $following_uid);

        return $result;
    }
    
    /**
     * 隐藏关注的网页
	 *
	 * @author zengmm
	 * @date 2012/7/24
	 * 
	 * @history <lanyanguang><2012-04-24>
     * 
     * @param int $uid 用户uid
     * @param int $following_webid 关注的网页ID
	 *
     * @return boolean true成功|false失败
     */
    function hideWebFollowing($uid, $following_webid) {

		if (empty($uid) || empty($following_webid)) { return FALSE; }

		$result = service('WebpageRelation')->hideFollowing($uid, $following_webid);

		if ($result) {
			$result = service('Attention')->set_attention_web_show($uid, $following_webid, 0);
		}

        return $result;
    }
    
    /**
     * 取消隐藏关注网页
     *
	 * @author zengmm
	 * @date 2012/7/24
	 * 
	 * @history <lanyanguang><2012-04-24>
     * 
     * @param int $uid 用户uid
     * @param int $following_webid 关注的网页ID
	 *
     * @return boolean true成功|false失败
     */
    function unHideWebFollowing($uid, $following_webid) {

    	if(empty($uid) || empty($following_webid)) { return FALSE; }

    	$result = service('WebpageRelation')->unHideFollowing($uid, $following_webid);

    	if ($result) {
    		service('Attention')->set_attention_web_show($uid, $following_webid, 1);
    	}

        return $result;
    }
    /**
	 * 隐藏和取消隐藏网页关注分类|需要处理的地方|数据库分类,数据库记录,redis关注隐藏
	 * @author boolee 2012/8/3
	 * @param $uid
	 * @param $webpage
	 * @param $is_hideen 是否进行隐藏
	 * @return boolean
	 */
	function categoryHidden($uid, $iid, $is_show){
		if(empty($uid) || empty($iid)) { return FALSE; }
		
		//取得分类下全部web_id
        $allfollowings = service('Attention')->get_web_id_by_iid($uid, $iid);
        $allwebid =array();
        
        //未能在分类里面查找到webid.
        if(!$allfollowings){
        	return FALSE;
        }else{
	        foreach ($allfollowings as $field){
	        	$allwebid[] = $field['aid'];
	        }
        }
        
        $result1 = service('Attention')->set_attention_webs_show($uid, $iid, $is_show);//数据库
        if($is_show){
        	$result2 = service('WebpageRelation')->UnHideMoreFollowing($uid, $allwebid);//redis
        }else{
        	$result2 = service('WebpageRelation')->hideMoreFollowing($uid, $allwebid);//redis
        }
        return $result1 && $result2;
	}
    /**
     * 加关注|显示隐藏网页操作 更新网页索引
     *
     * @author	lanyanguang
     * @date	2012/05/07
     *
     * @param array $info 用户资料
     *
     * @return boolean
     */
    function unHidingAUserInWebpage($info) {
        return service('WebpageSearch')->unHidingAUserInWebpage($info);
    }

    /**
     * 隐藏网页 更新网页索引
     *
     * @author	lanyanguang
     * @date	2012/05/07
     *
     * @param array $info 用户资料
     *
     * @return boolean
     */
    function hidingAUserInWebpage($info) {
        return service('WebpageSearch')->hidingAUserInWebpage($info);
    }
	
	/**
	 * 获取用户个人失效关注的数量
	 *
	 * @author boolee
	 * @date 2012/7/25
	 *
	 * @param int $uid 用户UID
	 *
	 * @return int
	 */
    public function getNumOfInvalidateFollowings($uid) {
       return service('Relation')->getNumOfInvalidateFollowings($uid);
    }

    /**
     * 获取用户关注的网页(根据网页分类)
     *
     * 用于用户首页右边显示关注的网页
     * 
     * @author zengmm
     * @date 2012/7/30
     *
     * @param int $uid 被访问者UID
     * @param int $imid 网页所属的频道ID
     * @param int $offset 页码
     * @param int $limit 偏移量
     * 
     * @return array
     */
    public function getNewestFollowingWebpage($uid, $imid, $offset, $limit) {
        
        $webpage_list = service('Attention')->getNewestFollowingWebpage($uid, $imid, $offset, $limit);

        return isset($webpage_list) ? $webpage_list : array();
    }
}
/* End of file followingmodel.php */
/* Location: ./application/models/followingmodel.php */