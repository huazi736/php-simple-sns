<?php

class RelationService_model extends MY_Model {

    public function sayhello() {
        return 'hello bob';
    }

    //获取好友列表
    public function getFriendList($userid) {
        $params = $this->decodeParams($userid);

        $res = service('Relation')->getAllFriendsWithInfo($params['userid']);
        
        $status = is_array($res) ? 1 : 0;
        if ($status) {
            $msg = '获取用户好友列表成功';
        } else {
            $msg = '内部错误';
            $res = null;
        }
        $result = array(
            'list' => $res,
        );

        return $this->encodeResult($status, $msg, $result);
    }

    //获取相互关注列表
    public function getBothAttentionList($userid) {
        $params = $this->decodeParams($userid);
        
        $res = service('Relation')->getAllBothFollowersWithInfo($params['userid']);
        
        $status = is_array($res) ? 1 : 0;
        if ($status) {
            $msg = '获取关注列表成功';
        } else {
            $msg = '内部错误';
            $res = null;
        }
        $result = array(
            'list' => $res,
        );
        
        return $this->encodeResult($status, $msg, $result);
    }

}