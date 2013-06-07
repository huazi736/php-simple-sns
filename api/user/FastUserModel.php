<?php

class FastUserModel extends DkModel {
    
    public function __initialize() {
        $this->init_redis();
    }
    
    public function getShortInfoByIds($uids) {
        $users = array();
        foreach ($uids as $uid) {
            $users[] = $this->getShortInfo($uid);
        }
        return $users;
    }

    /**
     * 设置用户简要信息
     * 信息格式为 array('uid' => 'user id', 'uname' => 'user name', 'dkcode' => 'duankou num', 'sex' => 'sex num')
     * @param type $data 用户信息, 
     * @return type 
     */
    public function setShortInfo($data = array()) {
        if (empty($data)) {
            return false;
        }

        if ($this->checkFields($data)) {
            if (isset($data['sex']) && empty($data['sex'])) {
                $data['sex'] = '3'; //默认3，性别保密
            }

            $data = array_filter($data);
            $mapping = array('uid' => 'id', 'uname' => 'name', 'dkcode' => 'dkcode', 'sex' => 'sex');
            $data_arr = array();
            foreach ($mapping as $key => $value) {
                if (isset($data[$key])) {
                    $data_arr[$value] = $data[$key];
                }
            }
            
            $res = $this->redis->hMset('user:' . $data['uid'], $data_arr);
            if ($res) {
                //dump keys
                $this->redis->zAdd('dump:user', time(), $data_arr['id']);
            }
            return $res;
        }
        return false;
    }

    public function deleteShortInfo($uid) {
        $res = $this->redis->delete('user:' . $uid);
        if ($res) {
            //dump keys
            if (!$this->redis->exists('user:' . $uid)) {
                $this->redis->zDelete('dump:user', $uid);
            }
        }
        return $res;
    }

    /**
     * 获取用户简要信息
     * @param type $uid 用户ID
     * @return type 
     */
    public function getShortInfo($uid, $fields = array()) {
        if (empty($fields)) {
            $fields = 'id,name,dkcode,sex';
        }

        if ($fields == '*') {
            $user = $this->redis->hGetAll('user:' . $uid);
        } else {
            $show_fields = explode(',', $fields);
            if (empty($show_fields)) {
                return array();
            }
            $user = $this->redis->hMGet('user:' . $uid, $show_fields);
        }

        if ($user['name'] === false || $user['dkcode'] === false) {
            $user = $this->syncUser($uid);
        }
        return $user;
    }

    /**
     * 获取多个目标用户的简要信息
     * @param type $uids    目标用户ID集合
     * @return type 
     */
    public function getMultiShortInfo($uids, $fields = array()) {
        $results = array();
        foreach ($uids as $uid) {
            $results[] = $this->getShortInfo($uid, $fields);
        }
        return $results;
    }

    /**
     * 验证传入的数据信息
     * @param type $data
     * @return bool 
     */
    private function checkFields($data) {
        if (!is_array($data)) {
            return false;
        }

        //验证field是否规范
        $valid_fields = array('uid', 'dkcode');
        $data_fields = array_keys(array_filter($data));

        foreach ($valid_fields as $field) {
            if (!in_array($field, $data_fields)) {
                return false;
            }
        }
        return true;
    }
    
    //Sync user from mysql to redis
    private function syncUser($uid) {
        $default = array(
            'id' => $uid,
            'name' => false,
            'dkcode' => false,
            'sex' => false
        );

        $userInfoModel = DKBase::import('UserInfo', 'user');
        $user = $userInfoModel->getUserInfo($uid);
        
        if ($user) {
            $user['id'] = strval($uid);
            $user['name'] = $user['username'];
            $user['dkcode'] = $user['dkcode'];
            $user['sex'] = $user['sex'];
        } else {
            return $default;
        }

        //Set user info
        $res = $this->redis->hMset('user:' . $uid, $user);

        return $res ? $user : $default;
    }
}