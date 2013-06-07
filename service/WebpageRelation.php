<?php

/**
 * 网页关系接口
 */
class WebpageRelationService extends DK_Service {

    public function __construct() {
        parent::__construct();

        $this->init_redis();

        //$this->user_mini = service('UserMini');
    }

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

        $time = time();
        if ($this->redis->zAdd('webpage:follower:' . $pageid, $time, $uid)) {
            if ($action_time && $expiry_time) {
                //添加关注时间
                $data = array(
                    'action_time' => $action_time,
                    'expiry_time' => $expiry_time
                );
                $this->redis->hSet('webpage:followingdetail:' . $uid, $pageid, json_encode($data));
            }
            $res = $this->redis->zAdd('webpage:following:' . $uid, $time, $pageid) ? true : false;
        } else {
            $res = false;
        }

        if ($res) {
            return $this->getNumOfFollowers($pageid);
        }
        return false;
    }

    /**
     * 取消关注
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return mix 返回值：成功 粉丝数, 失败 false
     */
    public function unFollow($uid, $pageid) {
        $r1 = $this->redis->zDelete('webpage:following:' . $uid, $pageid);
        $r2 = $this->redis->zDelete('webpage:follower:' . $pageid, $uid);
        $r3 = $this->redis->zDelete('webpage:following:hidden:' . $uid, $pageid);
        $r4 = $this->redis->hDel('webpage:followingdetail:' . $uid, $pageid);
        if (($r1 || $r3) && $r2) {
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
        $data = array('action_time' => $action_time, 'expiry_time' => $expiry_time);
        $res = $this->redis->hSet('webpage:followingdetail:' . $uid, $pageid, json_encode($data));
        if ($res === 0) {  //覆盖旧值时候返回0，先插入时返回1
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取一个网页当前所有的有效粉丝
     * @author boolee 2012/6/30
     * @param  $pageid  网页ID
     * @return array 
     */
    public function getAllValiditionFollowers($pageid) {
        $uids = $this->redis->zRevRange('follower:' . $pageid, 0, -1);
        //循环得到一个webid对应的json数组。
        $return = array();
        foreach ($uids as $uid) {
            $value = $this->redis->hGet('webpage:followingdetail' . $uid, $pageid);
            $value = json_decode($value, 1);
            $lasttime = $value['action_time'] + $value['expiry_time'] - time();
            if ($lasttime > 0 || $value['expiry_time'] == -1)
                $return[] = $uid;
        }
        return $return;
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

        $re = $this->redis->hMget('webpage:followingdetail:' . $uid, $web_ids);

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
     * 隐藏网页关注
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return bool 
     */
    public function hideFollowing($uid, $pageid) {
        $exists = $this->redis->exists('webpage:following:' . $uid);
        if (!$exists) {
            return false;
        }

        $time = $this->redis->zScore('webpage:following:' . $uid, $pageid);
        if (is_numeric($time)) {
            $r1 = $this->redis->zAdd('webpage:following:hidden:' . $uid, $time, $pageid);
            $r2 = $this->redis->zDelete('webpage:following:' . $uid, $pageid);
            return $r1 && $r2;
        }
        return false;
    }

    /**
     * 取消隐藏网页关注
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return bool 
     */
    public function unHideFollowing($uid, $pageid) {
        $exists = $this->redis->exists('webpage:following:hidden:' . $uid);
        if (!$exists) {
            return false;
        }

        $time = $this->redis->zScore('webpage:following:hidden:' . $uid, $pageid);
        if (is_numeric($time)) {
            $r1 = $this->redis->zAdd('webpage:following:' . $uid, $time, $pageid);
            $r2 = $this->redis->zDelete('webpage:following:hidden:' . $uid, $pageid);
            return $r1 && $r2;
        }
        return false;
    }

    /**
     * 获取所有网页关注
     * @param type $uid 用户ID
     * @param type $self 网页ID
     * @return array 
     */
    public function getAllFollowings($uid, $self = true) {
        if ($self) {
            $unionKey = $this->unionFollowing($uid);
            $res = $this->redis->zRevRange($unionKey, 0, -1);
        } else {
            $res = $this->redis->zRevRange('webpage:following:' . $uid, 0, -1);
        }
        return $res;
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
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        if ($self) {
            $unionKey = $this->unionFollowing($uid);
            $res = $this->redis->zRevRange($unionKey, $offset, $end);
        } else {
            $res = $this->redis->zRevRange('webpage:following:' . $uid, $offset, $end);
        }
        return $res;
    }

    /**
     * 获取网页所有的粉丝
     * @param type $pageid  网页ID
     * @return array 
     */
    public function getAllFollowers($pageid) {
        return $this->redis->zRevRange('webpage:follower:' . $pageid, 0, -1);
    }

    /**
     * 获取网页的粉丝
     * @param type $pageid  网页ID
     * @param type $offset 起始偏移量
     * @param type $limit 返回数
     * @return array
     */
    public function getFollowers($pageid, $offset = 0, $limit = 10) {
        if ($limit == 0) {
            return array();
        }
        $end = $offset + $limit - 1;
        return $this->redis->zRevRange('webpage:follower:' . $pageid, $offset, $end);
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
        return $this->user_mini->getUsersByIds($uids);
    }

    /**
     * 获取网页关注数
     * @param type $uid 用户ID
     * @param type $self 是否以自己身份
     * @return int
     */
    public function getNumOfFollowings($uid, $self = true) {
        if ($self) {
            return $this->redis->zCard('webpage:following:' . $uid) + $this->redis->zCard('webpage:following:hidden:' . $uid);
        }
        return $this->redis->zCard('webpage:following:' . $uid);
    }

    /**
     * 获取粉丝数
     * @param type $pageid  网页ID
     * @return int
     */
    public function getNumOfFollowers($pageid) {
        return $this->redis->zCard('webpage:follower:' . $pageid);
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
        $r1 = is_numeric($this->redis->zScore('webpage:following:' . $uid, $pageid));
        $r2 = is_numeric($this->redis->zScore('webpage:following:hidden:' . $uid, $pageid));
        return $r1 || $r2;
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
     * 获取关注时间
     * @param type $uid 用户ID
     * @param type $pageid 网页ID
     * @return type 
     */
    public function getFollowingTime($uid, $pageid) {
        return $this->redis->hGet('webpage:followingdetail:' . $uid, $pageid);
    }

    /**
     * 清除网页关系
     * @param type $pageid 网页ID
     * @return mix  成功 true，失败 粉丝ID集合
     */
    public function clearRelation($pageid) {
        $followers = $this->getAllFollowers($pageid);

        //Clear following
        $failed = array();
        foreach ($followers as $uid) {
            if ($this->unFollow($uid, $pageid) === false) {
                $failed[] = $uid;
            }
        }

        //Clear follower
        $this->redis->delete('webpage:follower:' . $pageid);
        return empty($failed) ? true : false;
    }

    /**
     * 生成所有关注（公开+隐藏）集合
     * @param type $uid 用户ID
     * @return string
     */
    private function unionFollowing($uid) {
        $union_keys = array('webpage:following:' . $uid, 'webpage:following:hidden:' . $uid);

        $output_key = 'webpage:tmp:following:union:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'SUM');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

}