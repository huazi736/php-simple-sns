<?php

/**
 * 消息接口
 */
class MessageApi extends DkApi {

    protected $message;

    public function __initialize() {
        $this->message = DKBase::import('TheMessage', 'message');
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
        return $this->message->get_message($from_dkcode, $to_dkcode);
    }

}