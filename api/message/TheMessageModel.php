<?php

class TheMessageModel extends DkModel {

    public function __initialize() {
        $this->init_mongo();
    }

    /**
     * 获取消息聊天记录列表
     * @author gefeichao
     * @date 2012-03-15
     * @param	from_dkcode 当前用户端口号
     * @param	to_dkcode	对话好友端口号
     * @return array
     */
    function get_message($from_dkcode=null, $to_dkcode=null) {
        if (!$from_dkcode || !$to_dkcode) {
            return 1;
        }
        /* 获取uid */
        $f_uid = ($from_dkcode);
        $t_uid = ($to_dkcode);
        $users = $from_dkcode . ',' . $to_dkcode;
        $users1 = $to_dkcode . ',' . $from_dkcode;
        /* 获取gid */
        $gid = $this->mongo->where_in('g_list', array($users, $users1))->get('message_usergroup');
        if (!$gid)
            return 2;
        $gid = (array) $gid[0]['_id'];
        $mresult = $this->mongo->where(array('gid' => $gid['$id']))->order_by(array('dateline' => - 1))->select(array('from_uid', 'to_uid', 'message', 'dateline'))->get('message_info');

        if (!$mresult)
            return 3;
        return $mresult;
    }

}