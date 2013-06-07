<?php

class LikeModel extends DkModel {

    public function __initialize() {
        $this->init_db('system');
    }

    /**
     * 检查用户是否赞过对象
     * 如果object_id为字符串，那么返回值为真假，如果为数组，则返回是自己赞过的object_id数组
     *
     * @param mix $object_id
     * @param string $uid
     * @return boolean|array
     */
    public function checkMyLike($object_id, $object_type, $uid) {
        $sql = "SELECT object_id FROM likes WHERE uid = '$uid'";
        if (is_array($object_id)) {
            $sql .= " AND object_id in ('" . implode("','", $object_id) . "') AND object_type = '$object_type'";
        } else {
            $sql .= " AND object_id = '$object_id' AND object_type = '$object_type'";
        }

        $res = $this->db->query($sql)->result_array();

        if (is_array($object_id)) {
            $return = array();
            foreach ($res as $item) {
                $return[] = $item['object_id'];
            }
            return $return;
        } else {
            return (isset($res[0]['object_id']) && $res[0]['object_id']) ? true : false;
        }
    }

    /**
     * 插入赞
     */
    public function addLike($data=array()) {
        $data['dateline'] = time();
        foreach ($data AS $list) {
            if (!isset($list))
                return false;
        }
        $this->db->insert('likes', $data);
        $this->db->insert_id();
        if ($this->db->insert_id()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除赞
     */
    public function delLike($data) {
        if (empty($data['object_id']) || empty($data['object_type']) || empty($data['uid']))
            return false;
        $object_id = $data['object_id'];
        $object_type = $data['object_type'];
        $uid = $data['uid'];
        if ($this->db->delete('likes', array('object_id' => $object_id, 'uid' => $uid, 'object_type' => $object_type))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 评论被删除时相关的赞
     * */
    public function delrelateLike($data) {
        if (!isset($data['object_id']) && !isset($data['object_type']))
            return false;
        $object_id = $data['object_id'];
        $object_type = $data['object_type'];
        if ($this->db->where(array('object_id' => $object_id, 'object_type' => $object_type))->delete('likes')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询赞的列表
     */
    public function getLikes($data=array()) {
        $where = array();
        if (isset($data['object_id'])) {
            if (is_array($data['object_id'])) {
                $where[] = "`object_id` in ('" . implode("','", $data['object_id']) . "')";
            } else {
                $where[] = "`object_id` = '" . $data['object_id'] . "'";
            }
        }
        if (isset($data['uid'])) {
            if (is_array($data['uid'])) {
                $where[] = "`uid` in ('" . implode("','", $data['uid']) . "')";
            } else {
                $where[] = "`uid` = '" . $data['uid'] . "'";
            }
        }
        if (isset($data['object_type']) && $data['object_type']) {
            $where[] = "`object_type` = '" . $data['object_type'] . "'";
        }
        //返回字段
        if (isset($data['field']) && $data['field']) {
            $field = $data['field'];
        } else {
            $field = "id, uid, username, usr_ip, dateline";
        }
        $where_sql = count($where) ? " WHERE " . implode(" AND ", $where) : "";

        $list_sql = "SELECT {$field} FROM likes " . $where_sql;

        //排序
        if (isset($data['order']) && in_array($data['order'], array('date_asc', 'date_desc', 'id_asc', 'id_desc'))) {
            switch ($data['order']) {
                case 'date_asc' :
                    $list_sql .= " ORDER BY dateline ASC";
                    break;
                case 'date_desc' :
                    $list_sql .= " ORDER BY dateline DESC";
                    break;
                case 'id_asc' :
                    $list_sql .= " ORDER BY id ASC";
                    break;
                case 'id_desc' :
                    $list_sql .= " ORDER BY id DESC";
                    break;
            }
        } else {
            $list_sql .= " ORDER BY dateline DESC";
        }

        if ($data['page'] >= 1) {       //假分页,每次返回50条，有page参数决定第几页
            $limit = 2;         //每次输出数据条数
            $offset = (intval($data['page']) - 1) * $limit;

            $list_sql .=" limit $offset,$limit";
        }

        $res = $this->db->query($list_sql)->result_array();
        return $res;
    }

    /**
     * Topic删除时，删除和Topic有关的所有Likes  
     * @param int $object_id
     * @param string $object_type
     * @return boolean
     * 
     */
    public function delObject($object_id = 0, $object_type = '') {
        if (!$object_id || empty($object_type)) {
            return false;
        }
        if ($object_type == 'topic') {
            $map = "`tid` = '" . $object_id . "'" . "AND `object_type` in ('topic','video','blog','photo','album','forward')";
        } else {
            if (is_array($object_id)) {
                $object_id = "( `tid` = '" . implode("' OR `tid` = '", $object_id) . "')";
            } else {
                $object_id = "`tid` = '" . $object_id . "'";
            }
            $map = $object_id . "AND `object_type` in ('web_topic','web_video','web_blog','web_photo','web_album')";
        }
        if ($this->db->where($map)->delete('likes')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除Likes表里数据
     * @param int $object_id
     * @param string $object_type
     * @return boolean
     */
    public function delete_Like($object_id, $object_type, $web_id = 0) {
    	if($web_id > 1){
	    	if ($this->db->where(array('web_id' => $web_id))->delete('likes')) {
	            return true;
	        } else {
	            return false;
	        }
    	}
        if (empty($object_id) || empty($object_type)) {
            return false;
        }
        if ($this->db->where("`object_id` = '$object_id'  AND `object_type`='$object_type'")->delete('likes')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据object_id,object_type更新ctime字段
     * @param int $object_id
     * @param string $object_type
     * @param string $ctime
     * @return boolean
     */
    public function update_Like($object_id, $object_type, $ctime) {

        if ($object_type == "info") {
            $where = "`tid` = '$object_id'  AND (`object_type`='topic' OR `object_type`='blog' OR `object_type`='forward' OR `object_type`='video')";
        } elseif ($object_type == "web_info") {
            $where = "`tid` = '$object_id'  AND (`object_type`='web_topic' OR `object_type`='web_blog' OR `object_type`='web_video')";
        } else {
            $where = "`tid` = '$object_id'  AND `object_type`='$object_type'";
        }

        if ($this->db->where($where)->update('likes', array('ctime' => $ctime))) {
            return true;
        } else {
            return false;
        }
    }
    
    public function check_like_status($object_id, $object_type, $uid){
    	return $this->db->from('likes')->where(array('object_id' => $object_id, 'object_type' => $object_type, 'uid'=>$uid))->get()->num_rows();
    }

}