<?php

class LinkStatModel extends DkModel {

    protected $trueTableName = 'link_stat';

    public function __initialize() {
        $this->init_db('system');
    }

     /**
     * 查看赞、评论的统计
     * 
     * @author boolee
     * @param mix $object
     * @return array
     */
    public function getStat($object_id, $object_type, $field='id, object_id, share_count, like_count, comment_count, total_count, like_record,favorite_count') {
        
    	if (!$field) {
    		$field = '*';
    	}
    	
    	$sql = "SELECT $field FROM link_stat";
        if (is_array($object_id)) {
            $sql .= " WHERE object_id in ('" . implode("','", $object_id) . "') AND object_type = '$object_type'";
        } else {
            $sql .= " WHERE object_id = '$object_id' AND object_type = '$object_type'";
        }
        $res = $this->db->query($sql)->result_array();

        $result = array();
        foreach ($res as $key => $item) {
        	if (strpos('like_record', $field) && $res) {
        		$item['like_record'] = empty($item['like_record']) ? array() : json_decode(stripslashes($item['like_record']), true);
        	}
        	if (is_array($object_id)) {
        		$result[$item['object_id']] = $item;
        	} else {
        		$result[$key] = $item;
        	}
        }
        
        return $result;
    }

    /**
     * 获取统计列表
     */
    public function getStatList($obj_ids, $object_type, $return_fields = array()) {
        if (empty($return_fields)) {
            $field = '*';
        } else {
            $field = implode(",", $return_fields);
        }
        $sql = "SELECT {$field} FROM link_stat";
        if (is_array($obj_ids)) {
            $sql .= " WHERE object_id in ('" . implode("','", $obj_ids) . "') AND object_type = '$object_type'";
        } else {
            $sql .= " WHERE object_id = '$obj_ids' AND object_type = '$object_type'";
        }

        return $this->db->query($sql)->result_array();
    }

    /**
     * 返回可以删除的评论ID
     * 
     * @param mix $id		评论编号
     * @param string $uid
     */
    public function checkDelComment($id, $object_type, $uid) {

        $ids = is_array($id) ? $id : (array) $id;
        
        $commentModel = DKBase::import('Comment', 'comlike');
        
        $comments = $commentModel->getComment(array('id' => $ids, 'object_type' => $object_type, 'limit' => count($ids)));
        $return = array();
        foreach ($comments['data'] as $item) {
            if ($uid == $item['uid'] || $uid == $item['src_uid']) {
                $return[] = $item['id'];
            }
        }
        return $return;
    }
    
    public function replaceRecord() {
    }

    //添加一条新纪录
    public function addRecord($type='', $object_id='', $object_type='', $uid='', $username='', $web_id = 0) {
        /**
         * id	object_id 对像ID	share_count 分享数	like_count 赞的数目	comment_count 评论的数目	total_count 总数	like_record 几个人赞的记录
         * */
        $data['object_id'] = $object_id;
        $data['object_type'] = $object_type;
        $data['total_count'] = 1;
        $data['web_id']      = $web_id;
        //新加评论//新加赞
        if (strtolower($type) == 'like') {
            $data['total_count'] = 1;
            $data['like_count']  = 1;
            $data['like_record'] = addslashes(json_encode(array(array('id' => $this->get_uuid(), 'uid' => $uid, 'username' => $username, 'dateline' => time()))));
        } elseif (strtolower($type) == 'comment') {
            $data['comment_count'] = 1;
        }
        $this->db->insert('link_stat', $data);
        $id = $this->db->insert_id();
        if ($id) {
            return $id;
        } else {
            return false;
        }
    }

    /**
     * 评论赞表更新
     * 
     * @param string $objectid
     * @param string $object_type
     * @param string $type
     * @param integer $num
     * @param integer $uid
     * @param string $username
     */
    public function commentUpdate($objectid = null, $object_type = null, $type = null, $num = 0, $uid = null, $username = '', $web_id = 0) {
        $org = $this->db->from('link_stat')->select('comment_count,like_count,total_count,like_record')->where(array('object_id' => $objectid, 'object_type' => $object_type))->get()->result_array();

        $type_count = $type . '_count';
        
		$data['web_id'] = intval($web_id);
        
		$records = array();
        if (empty($org)) {
            $data[$type_count] = intval($num);
            $data['total_count'] = intval($num);
        } else {
            $records = json_decode(stripslashes($org[0]['like_record']), true);
            $data[$type_count] = intval($org[0][$type_count]) + intval($num);
            $data['total_count'] = intval($org[0]['total_count']) + intval($num);
        }

        //添加 like_record字段记录
        if (strtolower($type) == 'like' && intval($num) > 0) {
            $record = array('id' => $this->get_uuid(), 'uid' => $uid, 'username' => $username, 'dateline' => time());

            // 如果赞的记录大于等于3，则弹出最后一个，将最新的赞的人插入到数组前面，只保存最近三个赞过的人的记录
            if (count($records) >= 3) {
                array_pop($records);
            }
            $records = is_array($records) ? array_unshift($records, $record) : array($record);
            $data['like_record'] = addslashes(json_encode($records));
        }

        //修改 like_record字段记录
        if (strtolower($type) == 'like' && intval($num) < 0 && is_array($records)) {

            // 删除可能会有的赞记录
            foreach ($records as $key => $list) {
                if (isset($list['uid']) && $list['uid'] == $uid) {
                    unset($records[$key]);
                    break;
                }
            }

            $data['like_record'] = $records ? addslashes(json_encode($records)) : '';
        }

        $this->db->where(array('object_id' => $objectid, 'object_type' => $object_type));
        if ($this->db->update('link_stat', $data)) {
            return $data[$type_count];
        } else {
            return false;
        }
    }

    /**
     * 删除记录, //
     * @param $object_id 删除的相关对象id
     * @param $id        自己id
     */
    public function delStat($object_id, $object_type, $id = 0, $web_id = 0) {
    	//删除一个网页下所有统计
    	if($web_id > 0){
    		if (!$this->db->where(array("web_id" => $web_id))->delete('link_stat')) {
                return false;
            }
            return true;
    	}
        if ($id) {
            if (!$this->db->where("`id`='$id' AND `object_type`='$object_type'")->delete('link_stat')) {
                return false;
            }
        } else {
            if (!$this->db->where("`object_id` = '$object_id' AND `object_type`='$object_type'")->delete('link_stat'))
                return false;
        }
        
        return true;
    }
    
    public function get_uuid() {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-';
        $uuid .= substr($chars, 8, 4) . '-';
        $uuid .= substr($chars, 12, 4) . '-';
        $uuid .= substr($chars, 16, 4) . '-';
        $uuid .= substr($chars, 20, 16);
        return $uuid;
    }
    
    public function get_link_stat($where = ''){
    	return $this->db->from('link_stat')->where($where)->get()->result_array();
    }
    
    /**
     * 检测当前赞是否有统计
     * 
     * @param array $where 条件
     * 
     * @return integer
     */
    public function check_stat($where = ''){
    	if($this->db->from('link_stat')->where($where)->get()->num_rows()){
			return true;    	
    	}
    	
    	return false;
    }
    
    /**
     * 
     * 取得一条统计数据
     * @param integer $oid
     * @param string  $type
     */
    public function getData($oid, $type) {
    	
    	$sql = "SELECT * FROM link_stat WHERE `object_id`='$object_id' AND `object_type`='$object_type'";
    	$data = $this->db->query($sql)->result_array();
    	if ($data) {
    		return $data[0];
    	}
    	return array();
    }
    
    /**
     * 
     * 更新分享次数
     * @param integer $oid
     * @param string  $type
     * @return integer 分享的次数
     */
    public function updateShareCount($oid, $type) {
    	
    	$res = $this->getData($oid, $type);
		if ($res) {
			
			// 统计数量加1
			$data = array(
				'share_count' => $res['share_count'] + 1,
				'total_count' => $res['total_count'] + 1
			);
			
			$this->db->update('link_stat', $data, 'id = ' . $res['id']);
		} else {
			$data = array(
				'object_id' => $oid,
				'object_type' => $type,
				'total_count' => 1,
				'share_count' => 1,
				'comment_count' => 0,
				'like_count' => 0,
				'favorite_count' => 0
			);
			$this->db->insert('link_stat', $data);
		}

		return $data['share_count'];
    }
    
    /**
     * 
     * 删除分享次数
     * @param integer $oid
     * @param string  $type
     * @return integer 分享的次数
     */
    public function delShareCount($oid, $type) {
    	
    	$res = $this->getData($oid, $type);
    	if (!$res) {
    		return array();
    	}

		// 统计数量-1
		$data = array(
			'share_count' => $res['share_count'] - 1,
			'total_count' => $res['total_count'] - 1
		);
		$this->db->update('link_stat', $data, 'id = ' . $res['id']);
		return $data['share_count'];
    }
}