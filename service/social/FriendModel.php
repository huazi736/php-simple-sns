<?php

/**
 * Friend 好友关系模型
 */
class FriendModel extends DkModel
{

    public function __initialize()
    {
        $this->init_redis();
    }

    /**
     * 发送加好友请求
     * 
     * @param int $uid1 发起好友请求的用户ID
     * @param int $uid2 要加为好友的用户ID
     * @return bool 如果成功返回true，如果这两个用户已经是好友则返回false
     */
    public function makeFriendsWith($uid1, $uid2) {
        if (!$this->isFriend($uid1, $uid2)) {
            // 将这个请求加入到用户1发送的好友请求队列、消息队列中
            $this->redis->lPush('friend:request:' . $uid1, $uid2);
            $this->redis->hSet('friend:request:notice:' . $uid1, $uid2, time());

            // 将这个请求加入到用户2收到的好友请求队列、消息队列中
            $this->redis->lPush('friend:response:' . $uid2, $uid1);
            $this->redis->hSet('friend:response:notice:' . $uid2, $uid1, time());

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
    public function getFriends($uid, $self = true, $offset = 0, $limit = 10, $actorId = null) {
        if ($limit <= 0) {
            return array();
        }
        $end = $offset + $limit - 1;

        if ($self) {
            $unionKey = $this->unionFriend($uid);
            $res = $this->redis->zRevRange($unionKey, $offset, $end);
        } else {
            if (!empty($actorId) && $this->isHiddenFriend($uid, $actorId)) {
                $openKey = $this->makeOpenFriends($uid, $actorId);
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
    public function getHiddenFriends($uid) {
        return $this->redis->zRevRange('friend:hidden:' . $uid, 0, -1);
    }

    /**
     * 获取用户的完整的好友列表
     * 
     * @param int $uid
     * @param bool $self
     */
    public function getAllFriends($uid, $self = true, $actorId = null) {
        if ($self) {
            $unionKey = $this->unionFriend($uid);
            $res = $this->redis->zRevRange($unionKey, 0, -1);
        } else {
            if (!empty($actorId) && $this->isHiddenFriend($uid, $actorId)) {
                $openKey = $this->makeOpenFriends($uid, $actorId);
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
     * @param int $uid1 
     * @param int $uid2
     * @return array  如果两个用户没有共同好友或者某个用户没有好友将返回一个空的数组
     */
    public function getCommonFriends($uid1, $uid2, $self = true) {
        $commonKey = $this->commonFriend($uid1, $uid2, $self);
        return $this->redis->zRevRange($commonKey, 0, -1);
    }

    /**
     * 判断用户是否是好友
     * 
     * @param int $uid1
     * @param int $uid2
     */
    public function isFriend($uid1, $uid2) {
        $r1 = is_numeric($this->redis->zScore('friend:' . $uid1, $uid2));
        $r2 = is_numeric($this->redis->zScore('friend:hidden:' . $uid1, $uid2));
        return $r1 || $r2;
    }
    
    //判断用户2是否为用户隐藏的好友
    public function isHiddenFriend($uid1, $uid2) {
        return is_numeric($this->redis->zScore('friend:hidden:' . $uid1, $uid2));
    }

    /**
     * 接受好友请求
     * 
     * @param int $uid1
     * @param int $uid2
     */
    public function approveFriendRequest($uid1, $uid2) {
        $notice_model = new NoticeModel();
        if (!$notice_model->hasPostRequest($uid2, $uid1)) {
            return false;
        }

        //删除uid1发送的好友请求
        $notice_model->deleteFriendRequest($uid1, $uid2);
        //删除uid2发送的好友请求
        $notice_model->deleteFriendRequest($uid2, $uid1);

        /*
         * @todo: 记录加为好友的信息记录
         */

        $time = time();
        $following_model = new FollowingModel();
        if ($following_model->isHiddenFollowing($uid1, $uid2)) {
            $r1 = $this->redis->zAdd('friend:hidden:' . $uid1, $time, $uid2);
        } else {
            $r1 = $this->redis->zAdd('friend:' . $uid1, $time, $uid2);
        }
        if ($following_model->isHiddenFollowing($uid2, $uid1)) {
            $r2 = $this->redis->zAdd('friend:hidden:' . $uid1, $time, $uid2);
        } else {
            $r2 = $this->redis->zAdd('friend:' . $uid2, $time, $uid1);
        }
        $res = $r1 && $r2;

        if ($res) {
            //dump keys
            $this->redis->zAdd('dump:friend', $time, $uid1);
            $this->redis->zAdd('dump:friend', $time, $uid2);
        }
        return $res;
    }

    /**
     * 忽略好友的请求
     * 
     * @param int $uid1
     * @param int $uid2
     */
    public function denyFriendRequest($uid1, $uid2) {
        /*
         * @todo: 用户拒绝加好友
         */
    }

    /**
     * 获取某个用户的好友数
     * 
     * @param int $uid
     */
    public function getNumOfFriends($uid) {
        return $this->redis->zCard('friend:' . $uid) + $this->redis->zCard('friend:hidden:' . $uid);
    }

    /**
     * 获取用户隐藏的好友数
     * @param type $uid
     * @return type 
     */
    public function getNumOfHiddenFriends($uid) {
        return $this->redis->zCard('friend:hidden:' . $uid);
    }

    /**
     * 获取两个用户的共同好友的个数
     * 
     * @param int $uid1
     * @param int $uid2
     * @return int 两个用户的共同好友的个数
     */
    public function getNumOfCommonFriends($uid1, $uid2) {
        $commonKey = $this->commonFriend($uid1, $uid2);
        return $this->redis->zCard($commonKey);
    }

    /**
     * 删除好友
     * 
     * @param int $uid1
     * @param int $uid2
     */
    public function deleteFriend($uid1, $uid2) {
        /**
         * @todo: 记录删除好友的事件
         */
        $r1 = $this->redis->zDelete('friend:' . $uid1, $uid2);
        $r2 = $this->redis->zDelete('friend:' . $uid2, $uid1);
        $r3 = $r1 || $r2;
        // delete hiden friend
        $r1 = $this->redis->zDelete('friend:hidden:' . $uid1, $uid2);
        $r2 = $this->redis->zDelete('friend:hidden:' . $uid2, $uid1);
        $r4 = $r1 || $r2;
        $res = $r3 || $r4;

        if ($res) {
            //clear dump keys
            if (!$this->redis->exists('friend:' . $uid1) && !$this->redis->exists('friend:hidden:' . $uid1)) {
                $this->redis->zDelete('dump:friend', $uid1);
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
    public function hideFriend($uid, $friendId) {
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
    public function unHideFriend($uid, $friendId) {
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
    public function getTimeOfBeFriend($uid, $friendId) {
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
    private function makeOpenFriends($uid, $actorId) {
        $this->redis->delete('tmp:friend:open:' . $uid);
        $time = $this->redis->zScore('friend:hidden:' . $uid, $actorId);
        $this->redis->zAdd('tmp:friend:open:' . $uid, $time, $actorId);

        $union_keys = array('friend:' . $uid, 'tmp:friend:open:' . $uid);

        $output_key = 'tmp:friend:open:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    private function commonFriend($uid1, $uid2, $self = true) {
        $inter_keys = array('friend:' . $uid2);
        if ($self) {
            $unionKey = $this->unionFriend($uid1);
            $inter_keys[] = $unionKey;
        } else {
            $inter_keys[] = 'friend:' . $uid1;
        }

        $output_key = 'tmp:friend:common:' . $uid1 . ':' . $uid2;
        $this->redis->zInter($output_key, $inter_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    private function unionFriend($uid) {
        $union_keys = array('friend:' . $uid, 'friend:hidden:' . $uid);

        $output_key = 'tmp:friend:union:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

}