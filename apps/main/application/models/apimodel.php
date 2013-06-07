<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 公共模块/端口关系通用模型
 * 
 * @author  boolee 2012/8/7
 * @history <lanyanguang><2012/3/20>
 */
class ApiModel extends MY_Model {
	/**
     * 构造函数
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * 取得用户资料
     * 
     * @author	lanyanguang
     * @date	2012/3/8
     * @param  string $uid 用户uid
     * @return mix
     */
    function getUserInfo($uid = null) {
        if (!$uid) {
            return false;
        }

        $lists = service('User')->getUserInfo($uid, 'uid', array('uid', 'username', 'dkcode'));
        if (!$lists) {
            return false;
        }
        $lists['url'] = mk_url('/main/index/profile', array('dkcode' => $lists['dkcode']));
        $lists['avatar_img'] = get_avatar($uid,'ss');
        return $lists;
    }
    
    /**
     * 取得网页资料
     * 
     * @author	lanyanguang
     * @date	2012/05/04
     * @param  int $webid 网页id 
     * @return mix
     */
    function getWebInfo($webid = null) {
        if (!$webid) {
            return false;
        }

	    $tmp = service('Interest')->get_web_info($webid);
        $lists = (isset($tmp) && $tmp && count($tmp)>0) ? $tmp : null;
        return $lists;
    }
    
     /**
     * 是否关注
     * 
     * @date	2012/06/24
     * @param string $uid 用户uid
     * @param string $pageid 目标用户pageid
     * @return boolean
     */
    function isWebFollowing($uid = null, $pageid = null) {
        if (!$uid || !$pageid) {
            return false;
        }

    	$re = service('WebpageRelation')->isFollowing($uid, $pageid);
        return $re;
    }
 	/**
     * 判断关注及其返回关注实效
     * 
     * @author boolee
     * @date   2012/08/7
     * @param string $uid 用户uid
     * @param string $pageid 目标用户pageid
     * @return json
     */
    function cheackWebFollowing($uid = null, $pageid = null) {
        if (!$uid || !$pageid) {
            return false;
        }

    	$re = service('WebpageRelation')->getFollowingTime($uid, $pageid);
        if($re){
 			return json_decode($re,1);
        }else{
        	return false;
        }
    }
     /**
     * 添加关注
     * @author	lanyanguang 
     * @date	2012/04/24
	 * @version modify by boolee 2012/06/025
     * @param string $uid 用户ID
     * @param string $pageid 目标pageid
     * @param $action_time 设置时间戳
     * @param $expiry_time 关注总时间
     * @return boolean
     */
    function webFollow($uid = null, $pageid = null ,$action_time = null ,$expiry_time = null) {
        if (!$uid || !$pageid || !$action_time || !$expiry_time) {
           return false;
        }

        return service('WebpageRelation')->follow($uid, $pageid , $action_time, $expiry_time);
    }

    /**
     * 添加关注时保存关注人的分类数据与网页的粉丝数
     * @author	lanyanguang
     * @date	2012/04/24
     * @version modify by boolee 2012/06/025 
     * @param int $uid 用户ID
     * @param int $pageid 目标pageid
     * @param int $fans_count 粉丝数
     * @return boolean
     */
    function addAttention($uid, $pageid, $fans_count, $action_time, $expiry_time) {
        if (!$uid || !$pageid || !$action_time || !$expiry_time) {
           return false;
        }

		return service('Attention')->add_attention($uid, $pageid, $fans_count,$action_time,$expiry_time);
    }

    /**
     * 取消关注
     * 
     * @author	lanyanguang
     * @date	2012/04/24
     * @param string $uid 用户ID
     * @param string $pageid  目标用户用户pageid
     * @return  成功返回true  失败返回false 
     */
    function unWebFollow($uid = null, $pageid = null) {
        if (!$uid || !$pageid) {
            return false;
        }

        return service('WebpageRelation')->unFollow($uid, $pageid);
    }
	/**
     * 修改redis保存网页关注时间
     * @author	boolee
     * @date	2012/06/29
     * @param  $uid string 用户ID
     * @param  $pageid string  目标pageid
     * @param  $expiry_time 设置时间戳
     * @return boolean
     */
	function updateFollowTime($uid,$pageid,$action_time,$expiry_time){
		if (!$uid || !$pageid || !$expiry_time) {
           return false;
        }

        return service('WebpageRelation')->updateFollowTime($uid, $pageid , $action_time, $expiry_time);
	}
	/**
     * 修改mysql保存网页关注时间
     * @author	boolee
     * @date	2012/06/26
     * @param  $uid string 用户ID
     * @param  $pageid string  目标pageid
     * @param  $expiry_time 设置时间戳
     * @return boolean
     */
	function updateAttentionTime($uid,$pageid,$action_time,$expiry_time){
		 if (!$uid || !$pageid || !$action_time || !$expiry_time) {
           return false;
        }

		return service('Attention')->updateAttentionTime($uid, $pageid , $action_time, $expiry_time);
	}
    /**
     * 取消关注时保存关注人的分类数据与网页的粉丝数
     * @author	lanyanguang
     * @date	2012/04/24
     * @param int $uid 用户ID
     * @param int $pageid 目标pageid
     * @param int $fans_count 粉丝数
     * @return boolean
     */
    function delAttention($uid, $pageid, $fans_count) {
        if (!$uid || !$pageid) {
           return false;
        }

        return service('Attention')->del_attention($uid, $pageid, $fans_count);
    }
    
    /**
     * 接收好友邀请
     * 
     * @author	lanyanguang
     * @date	2012/3/8
     * @param string $uid1 接受请求的用户
     * @param string $uid2 发送请求的用户
     * @return  成功返回true  失败返回false 
     */
    function approveFriendRequest($uid1 = null, $uid2 = null) {
        if (!$uid1 || !$uid2) {
            return false;
        }

        return service('Relation')->approveFriendRequest($uid1, $uid2);
    } 

    /**
     * 加好友
     * 
     * @author	lanyanguang
     * @date	2012/05/24
     * @param string $uid1 用户ID
     * @param string $uid2 目标用户ID
     * @return boolean
     */
    function addFriend($uid1 = null, $uid2 = null) {
        if (!$uid1 || !$uid2) {
            return false;
        }

       return service('Relation')->addFriend($uid1, $uid2);
    }
    
    /**
     * 获得关系状态
     * 
     * @author	lanyanguang
     * @date	2012/05/24
     * @param string $uid1 用户ID
     * @param string $uid2 目标用户ID
     * @return int 
     */
    function getRelationStatus($uid1 = null, $uid2 = null) {
        if (!$uid1 || !$uid2) {
           return false;
        }

        return service('Relation')->getRelationStatus($uid1, $uid2);
    }
    
    /**
     * 添加关注
     * @author	lanyanguang
     * @date	2012/3/8
     * @param string $uid1 用户ID
     * @param string $uid2 目标用户ID
     * @return boolean
     */
    function follow($uid1 = null, $uid2 = null) {
        if (!$uid1 || !$uid2) {
           return false;
        }

        return service('Relation')->follow($uid1, $uid2);
    }

    /**
     * 取消关注
     * 
     * @author	lanyanguang
     * @date	2012/3/8
     * @param string $uid1 用户ID
     * @param string $uid2  目标用户ID
     * @return  成功返回true  失败返回false 
     */
    function unfollow($uid1 = null, $uid2 = null) {
        if (!$uid1 || !$uid2) {
            return false;
        }

        return service('Relation')->unFollow($uid1, $uid2);
    }

    /**
     * 删除好友
     * 
     * @author	lanyanguang
     * @date	2012/3/8
     * @param string $uid1 用户ID
     * @param string $uid2  目标用户ID
     * @return boolean
     */
    function deleteFriend($uid1 = null, $uid2 = null) {
        if (!$uid1 || !$uid2) {
            return false;
        }

        return service('Relation')->deleteFriend($uid1, $uid2);
    }
    
    /**
     * 加关注操作 更新网页索引
     * @author	lanyanguang
     * @date	2012/05/17
     * @param array $info 用户资料
     * @return boolean
     */
    function addAFansToWeb($info) {
        return service('WebpageSearch')->addAFansToWeb($info);
    }
    
    /**
     * 取消网页 更新网页索引
     * @author	lanyanguang
     * @date	2012/05/07
     * @param array $info 用户资料
     * @return boolean
     */
    function deleteUserOfWeb($web_id = null, $user_id = null) {
        if (!$web_id || !$user_id) {
            return false;
        }
        
        return service('WebpageSearch')->deleteUserOfWeb($web_id, $user_id);
    }
    
    /**
     * 发送通知
     * 
     * @author	lanyanguang
     * @date	2012/3/8
     * @param int $notice_type 通知类型 1 个人通知 2 网页通知
     * @param int $uid  发送通知当前用户uid
     * @param int $to_uid  接收用户uid
     * @param string $btype  通知大分类
     * @param string $stype  通知小分类
     * @param array $param   其他参数（如URL）
     * @return state@
     * 1   操作对象uid 不存在
     * 2   大分类不存在
     * 3   小分类不存在
     * 4   当前用户登录uid不存在
     * 5   信息对应分类过滤失败
     * 6   小分类输入错误
     * 7   操作失败！
     * 8   操作成功！
     * */
    function sendNotice($notice_type = 1, $uid = NULL, $to_uid = NULL, $btype = NULL, $stype = NULL, $param = array()) {
        if (!$notice_type || !$uid || !$to_uid || !$btype || !$stype) {
            return false;
        }

    	return service('Notice')->add_notice($notice_type, $uid, $to_uid, $btype, $stype, $param);
    }
   
}
/* End of file apimodel.php */
/* Location: ./application/models/apimodel.php */