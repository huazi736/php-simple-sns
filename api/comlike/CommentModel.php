<?php

class CommentModel extends DkModel {

    /**
     * add module comments
     * 
     * @param type $ret reomote param
     */
//    protected $_objectdb = array(
//        'ask' => array('table' => ANSWER_COMMENTS, 'primary' => 'id'),
//        'event' => array('table' => EVENTTABLE, 'primary' => 'eventid'),
//        'blog' => array('table' => BLOG, 'primary' => 'id'),
//        'video' => array('table' => USER_VIDEO, 'primary' => 'id'),
//        'album' => array('table' => USER_ALBUM, 'primary' => 'id'),
//        'photo' => array('table' => USER_PHOTO, 'primary' => 'id'),
//    );

    public function __initialize() {
        $this->init_db('system');
    }

    /**
     * comments type
     * 
     * @param	$ret 数组
     */
    public function addComment($ret) {
        $data = array(
            'object_id' => $ret['object_id'],
            'uid' => $ret['uid'],
            'content' => $ret['content'],
            'object_type' => $ret['object_type'],
            'username' => $ret['username'],
            'src_uid' => $ret['src_uid'],
            'usr_ip' => $ret['usr_ip'],
            'dateline' => time(),
        	'web_id' => $ret['web_id'],
        );
        
        $this->db->insert('comments', $data);
        $cid = $this->db->insert_id();
        if ($cid) {
            return $cid;
        } else {
            return false;
        }
    }

    /**
     * 获取评论
     * 
     * @param array $params
     * <code>
     * $params['id']			    //评论编号，可以为数组
     * $params['object_id']			//对象编号，可以为数组
     * $params['uid']				//用户编号，可以为数组
     * $params['object_type']		//类型，支持：'ask','event','topic','blog','photo','album'
     * $params['start_dateline']	//开始时间，int型
     * $params['end_dateline']		//结束时间，int型
     * $params['is_stat']			//是否统计总数
     * $params['is_private']		//是否为私有
     * $params['is_delete']			//是否删除
     * $params['order']				//排序，可选：date_asc,date_desc,id_asc,id_desc
     * $params['primary_key']		//key值所用字段
     * $params['pagesize']			//每次取出的条数，如果没有此参数则表示全部取出
     * $params['page']				//当前第几页，默认为第一页，此参数只有在pagesize设置后才有效
     * $params['return_type']		//返回数据类型，可选：array，json，默认为array
     * </code>
     * 
     * @return array
     * <code>
     * $return['data']				//返回的数据集
     * $return['total_num']			//总数，根据条件返回
     * $return['page']				//当前页数，根据条件返回
     * $return['pagesize']			//当前分页数，根据条件返回
     * </code>
     */
    public function getComment($params) {
        $where = array();
        if (isset($params['id'])) {
            if (is_array($params['id'])) {
//                    $where['id'] = array('in'=>implode(',',$params['id']));
                $this->db->where_in('id', $params['id']);
            } else {
                $where['id'] = $params['id'];
            }
        }
        if (isset($params['object_id'])) {
            if (is_array($params['object_id'])) {
//                    $where['object_id'] =array('in',implode(',', $params['object_id']));
                $this->db->where_in('object_id', $params['object_id']);
            } else {
                $where['object_id'] = $params['object_id'];
            }
        }
        if (isset($params['uid'])) {
            if (is_array($params['uid'])) {
//                    $where['uid'] = array('in',implode(',', $params['uid']));
                $this->db->where_in('uid', $params['uid']);
            } else {
                $where['uid'] = $params['uid'];
            }
        }
        if (isset($params['object_type']) && $params['object_type']) {
            $where['object_type'] = $params['object_type'];
        }
        //创建时间
        if (isset($params['start_dateline']) && $params['start_dateline']) {
            $where['dateline'] >= intval($params['start_dateline']);
        }
        if (isset($params['end_dateline']) && $params['end_dateline']) {
            $where['dateline'] <= intval($params['end_dateline']);
        }
        if (isset($params['is_private']) && is_int($params['is_private'])) {
            $where['is_private'] = intval($params['is_private']);
        }
        if (isset($params['is_delete']) && is_int($params['is_delete'])) {
            $where['is_delete'] = intval($params['is_delete']);
        }

        if (isset($params['is_stat']) && $params['is_stat'] == 1) {
            //计算数量
//                $num_sql = $this->db->from('comments')->where($where)->get()->num_rows();
//                $num_res = $this->where($where)->findall();
            $return['total_num'] = $this->db->from('comments')->where($where)->get()->num_rows();
        }
        //$list_sql = $this->where($where)->field(' id, object_id, object_type, src_uid, uid, username, usr_ip, dateline, content')->findall();
        //排序
        if (isset($params['order']) && in_array($params['order'], array('date_asc', 'date_desc', 'id_asc', 'id_desc'))) {
            switch ($params['order']) {
                case 'date_asc' :
                    $order = "dateline ASC";
                    break;
                case 'date_desc' :
                    $order = "dateline DESC";
                    break;
                case 'id_asc' :
                    $order = "id ASC";
                    break;
                case 'id_desc' :
                    $order = "id DESC";
                    break;
            }
        } else {
            $order = "dateline DESC";
        }
        //分页
        if (isset($params['limit']) && $params['limit'] > 0) {
            $limit = $params['limit'];
        } else {
            $limit = 3;
        }

        if (isset($params['pagesize']) && $params['pagesize'] > 0) {
            $pagesize = $params['pagesize'] - 1;
        } else {
            $pagesize = 0;
        }

        $res = $this->db->from('comments')->where($where)->order_by($order)->limit($limit, $pagesize * $limit)->get()->result_array();

        $return['data'] = array();
        if (isset($params['primary_key']) && $params['primary_key']) {
            foreach ($res as $item) {
                $return['data'][$item[$params['primary_key']]] = $item;
            }
        } else {
            $return['data'] = $res;
        }
        if (isset($params['return_type']) && $params['return_type'] == 'json') {
            return $this->toJson($return);
        } else {
            return $return;
        }
    }

    /**
     * just delete one record once.
     */
    public function delComment($cid, $object_type, $web_id = 0) {
    	//删除网页下所有评论
    	if($web_id > 1){
    		if(!$this->db->where(array('web_id' => $web_id))->delete('comments')){
    			return false;
    		} else {
    			return true;
    		}
    	}
        if (!$this->db->where(array('id' => $cid, 'object_type' => $object_type))->delete('comments')) {
            return false;
        } else {
        	return true;
        }
    }
    
    /**
     * @description 输出JSON格式字符串
     * @param       mix $data
     */
    private function toJson($data) {
        header("Content-Type: application/json; charset=utf-8");
        return json_encode($data);
    }
	
    /**
     * 查询评论信息
     * 
     * @param $where   条件
     * @param $fields  要返回的字段
     * @param $order   排序
     * @param $limit   查询条数
     */
    public function get_comments($where = '', $fields = '*', $order = '', $limit = 1){
    	return $this->db->from('comments')->select($fields)->where($where)->order_by($order)->limit($limit)->get()->row_array();
    }
}