<?php

/**
 * 用户短信息模型
 * @author shedequan
 */
class UserInfoModel extends DkModel
{

	public function __initialize()
    {
        $this->init_redis();
    }

    public function getByIds($uids) {
        $users = array();
        foreach ($uids as $uid) {
            $users[] = $this->get($uid);
        }
        return $users;
    }

    public function get($uid, $fields = '') {
        if (empty($fields)) {
            $fields = 'id,name,dkcode,sex';
        }

        if ($fields == '*') {
            $user = $this->_redis->hGetAll('user:' . $uid);
        } else {
            $show_fields = explode(',', $fields);
            if (empty($show_fields)) {
                return array();
            }
            $user = $this->_redis->hMGet('user:' . $uid, $show_fields);
        }

        if ($user['name'] === false || $user['dkcode'] === false) {
            $user = $this->syncUser($uid);
        }
        return $user;
    }

    public function set($data) {
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

            $res = $this->_redis->hMset('user:' . $data['uid'], $data_arr);
            if ($res) {
                //dump keys
                $this->_redis->zAdd('dump:user', time(), $data_arr['uid']);
            }
            return $res;
        }
        return false;
    }

    public function delete($uid) {
        $res = $this->_redis->delete('user:' . $uid);
        if ($res) {
            //dump keys
            if (!$this->_redis->exists('user:' . $uid)) {
                $this->_redis->zDelete('dump:user', $uid);
            }
        }
        return $res;
    }

    /**
     * 验证传入的数据信息
     * @param type $data
     * @return bool 
     */
    public function checkFields($data) {
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
            'id' => false,
            'name' => false,
            'dkcode' => false,
            'sex' => false
        );

        $host = C('USER_DB_CONFIG.HOST');
        $username = C('USER_DB_CONFIG.USERNAME');
        $pwd = C('USER_DB_CONFIG.PWD');
        $dbname = C('USER_DB_CONFIG.DBNAME');

        $conn = mysql_connect($host, $username, $pwd);
        if (!$conn) {
            return $default;
        }
        
        mysql_select_db($dbname);

        $query = sprintf('SELECT * FROM user_info WHERE uid = \'%d\'', $uid);
        mysql_query('set names utf8');
        $result = mysql_query($query);
        
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        if (empty($row)) {
            return $default;
        }
        
        $user['id'] = strval($uid);
        $user['name'] = $row['username'];
        $user['dkcode'] = $row['dkcode'];
        $user['sex'] = $row['sex'];
        
        
        //Set user info
        $res = $this->_redis->hMset('user:' . $uid, $user);

        mysql_free_result($result);
        mysql_close($conn);

        return $res ? $user : $default;
    }

}