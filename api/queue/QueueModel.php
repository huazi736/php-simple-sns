<?php

class QueueModel extends DkModel {

    public function __initialize() {
        $this->init_htppsqs();
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
        if (!$queuename) {
            return false;
        }
        if (!$get_pos) {
            $res = $this->httpsqs->get($queuename);
            if ($res === 'HTTPSQS_GET_END') {
                return false;
            }
            return unserialize($res);
        } else {
            $res = $this->httpsqs->gets($queuename);
            if ($res && ($res['data'] === 'HTTPSQS_GET_END' && $res['pos'] === 0)) {
                return false;
            }
        }
        if ($res) {
            $res['data'] = unserialize($res['data']);
        }
        return $res;
    }

    /**
     * 保存队列
     * @param type $queuename   队列名
     * @param type $data        要保存到队列中的数据
     * @return type 
     */
    function putData($queuename = null, $data = null) {
        if (empty($queuename) or empty($data)) {
            return false;
        }
        $data = serialize($data);
        $res = $this->httpsqs->put($queuename, $data);
        if ($res === 'HTTPSQS_PUT_END') {
            return false;
        }
        return $res;
    }

    /**
     * 获取状态
     * @param type $queuename   队列名
     * @return type 
     */
    function getStatus($queuename = null) {
        if (!$queuename) {
            return false;
        }
        $res = $this->httpsqs->status_json($queuename);
        if ($res) {
            $res = (array) json_decode($res);
        }
        return $res;
    }

}