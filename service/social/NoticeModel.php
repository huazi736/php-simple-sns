<?php

/**
 * 用户好友的请求信息模型
 * 
 * @author 应晓斌, shedequan
 *
 */
class NoticeModel extends DkModel
{

    public function __initialize()
    {
        $this->init_redis();
    }

    /**
     * 获取某个用户的发送的好友请求
     * 
     * @param int $uid
     * @param int $offset
     * @param int $limit  
     */
    public function getFriendRequests($uid, $offset = 0, $limit = 3) {
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

    public function hasPostRequest($uid1, $uid2) {
        if ($this->redis->exists('friend:request:' . $uid1)) {
            $allFriendRequests = $this->redis->lRange('friend:request:' . $uid1, 0, -1);
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
    public function getReceivedFriendRequests($uid, $offset = 0, $limit = 3) {
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
            $user_model = new UserInfoModel();
            foreach ($noticeTimes as $key => $time) {
                $user = $user_model->get($key);
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
    public function getNumOfReceivedFriendRequests($uid) {
        return $this->redis->lSize('friend:response:' . $uid);
    }

    /**
     * 获取用户发送的好友请求数
     * 
     * @param int $uid
     */
    public function getNumOfFriendRequests($uid) {
        return $this->redis->lSize('friend:request:' . $uid);
    }

    //删除好友请求
    public function deleteFriendRequest($uid1, $uid2) {
        // 从用户1发送的好友请求列表中删除该请求
        $r1 = $this->redis->lRemove('friend:request:' . $uid1, $uid2);
        $r2 = $this->redis->hDel('friend:request:notice:' . $uid1, $uid2);

        // 从用户2收到的好友请求列表中删除该请求
        $r3 = $this->redis->lRemove('friend:response:' . $uid2, $uid1);
        $r4 = $this->redis->hDel('friend:response:notice:' . $uid2, $uid1);

        return $r1 && $r2 && $r3 && $r4;
    }

}