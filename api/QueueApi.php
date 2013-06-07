<?php

/**
 * 队列接口
 */
class QueueApi extends DkApi {

    protected $queue;

    public function __initialize() {
        $this->queue = DKBase::import('Queue', 'queue');
    }
    
    /**
     * 取队列
     * @param type $queuename   队列名
     * @param type $get_pos     是否获取队列的pos值
     * @return type             $get_pos为true,返回数组
     *                          $get_pos为fasel,返回字符串数据
     *                          出错,未取到数据返回	false;
     */
    function getData($queuename = null, $get_pos = false) {
        return $this->queue->getData($queuename, $get_pos);
    }

    /**
     * 保存队列
     * @param type $queuename   队列名
     * @param type $data        要保存到队列中的数据
     * @return type 
     */
    function putData($queuename = null, $data = null) {
        return $this->queue->putData($queuename, $data);
    }

    /**
     * 获取状态
     * @param type $queuename   队列名
     * @return type 
     */
    function getStatus($queuename = null) {
        return $this->queue->getStatus($queuename);
    }

}