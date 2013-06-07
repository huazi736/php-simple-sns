<?php

/**
 * 赞管理文件
 * 
 * @author yangshunjun  2012-07-03
 *
 */

include_once('Comlike.php');

class CommentService extends DK_Service {

    public function __construct() {
        parent::__construct();
        
        $this->comlike = new ComlikeService();
    }

    /**
     * 获取评论和赞
     * 
     * @access public
     * 
     * @param  object_id   评论对象id
     * @param  object_type 评论对象类型
     * @param  src_uid     应用用户id
     * @param  uid         当前用户id
     * @param  username    当前用户名
     * 
     * @param  $data=array('object_id','src_uid','uid','username') 
     * @return josn
     */
    public function get_stat($data) {
        foreach ($data as $list) {
            if (empty($list)) {
                $return['state'] = 0;
                $return['msg'] = '无效的对象';
                return $this->comlike->toJson($return);
            }
        }

        $object_id = $data['object_id'];
        $object_type = $data['object_type'];
        $uid = $data['uid'];
        if (empty($object_id) && empty($uid) && empty($object_type)) {
            $return['state'] = 0;
            $return['msg'] = '无效的对象';
            return $this->comlike->toJson($return);
        }

        $stat_info = $this->comlike->getStat($object_id, $object_type);

        if (empty($stat_info[0])) {
            $stat_info['comment_count'] = 0;
            $stat_info['like_count'] = 0;
            $stat_info['like_record'] = array();
        }
        //对赞的用户添加URL
        $like_record = array();
        if ($stat_info[0]['like_record']) {
            //这里先得进行转化like_record为数组
            $lkr = json_decode($stat_info[0]['like_record'], true);
            //return $lkr;
            foreach ($lkr as $item) {
                if ($item['uid'] == $uid) {
                    $item['username'] = "我";
                    $item['url'] = C('USEROOT') . $uid;
                } else {
                    $item['username'] = $item['username'];
                    $item['url'] = C('USEROOT') . $item['uid'];
                }
                $like_record[] = $item;
            }
        }

        $return = array(
            'state' => 1,
            'count' => $stat_info[0]['comment_count'], //对象评论总数
            'greeCount' => $stat_info[0]['like_count'], //赞的总数
            'isgree' => $this->comlike->checkMyLike($object_id, $object_type, $uid), //我是否赞了
            'data' => array(),
            'greepeople' => $like_record
        );
        //返回信息流的转发数
        if ($object_type == 'topic') {
            $return['share_count'] = $this->comlike->getLen($object_type, $object_id);
        }
        //如果评论数为0，则不查询
        if ($stat_info[0]['comment_count']) {
            $params = array();
            $params['object_id'] = $object_id;
            $params['object_type'] = $object_type;

            $params['primary_key'] = 'id';
            $params['limit'] = 3;
            $comment_list = $this->comlike->getComment($params);                             //得到全部相关评论

            $comment_object_ids = array_keys($comment_list['data']);               //得到对象评论的id
            $comment_stats = $this->comlike->getStat($comment_object_ids, 'comment');    //得到评论的赞的统计
            //$return['comment_stat'] = $comment_list;                             //该段暂未有任何使用

            $my_like_comments = $this->comlike->checkMyLike($comment_object_ids, 'comment', $uid);     //得到评论中有我赞过的评论编号

            $my_del_comments = $this->comlike->checkDelComment($comment_object_ids, $object_type, $uid); //得到可以删除的评论编号

            foreach ($comment_list['data'] as $item) {                               //得到相关评论自身数据
                if ($item['uid'] == $uid) {
                    $item['username'] = "我";
//                    $url = C('USEROOT') . $uid;
                    $url = $uid;
                } else {
//                    $url = C('USEROOT') . $item['uid'];
                    $url = $item['uid'];
                }

                //判断对应统计        
                $commnet_num = 0;
                foreach ($comment_stats as $c_stat) {
                    if ($item['id'] == $c_stat['object_id']) {
                        $commnet_num = $c_stat['like_count'];
                        break;
                    }
                }

                $return['data'][] = array(
                    'cid' => $item['id'],
                    'name' => $item['username'],
                    'uid' => $item['uid'],
                    //'imgUrl'   =>    '',//头像地址移植到应用进行操作
                    'url' => $url,
                    'content' => $item['content'],
                    'time' => tran_time($item['dateline']),
                    'isgree' => in_array($item['id'], $my_like_comments) ? true : false,
                    'isdel' => in_array($item['id'], $my_del_comments) ? true : false,
                    'greeNum' => $commnet_num,
                );
            }
        }
        return base64_encode(json_encode($return)); //为什么有时候不能返回复杂数组；
    }

    /**
     * 一次性获取多条评论和赞
     * 
     * @access public
     * 
     * @param  string object_id   评论对象id
     * @param  string object_type 评论对象类型
     * @param  string uid         当前用户id
     * 
     * @param  $data=array('object_id','object_type','uid') 
     * @return array
     */
    public function get_stat_all($data) {

        //数据合法性检测
        $obj_count = substr_count($data['object_id'], ',');
        $object_type_count = substr_count($data['object_type'], ',');
        if (empty($data['uid']))
            $result = 1;
        if ($obj_count != $object_type_count)
            $result = 1;
        $all_count = $obj_count + 1;  //需要处理的数据总数			
        if ($result) {
            $return['state'] = $result;
            $return['msg'] = '无效的对象';
            return $return;
        }
        /* 检测结束 */

        $object_ids = explode(',', $data['object_id']);
        $object_types = explode(',', $data['object_type']);
        $tid = explode(',', $data['tid']);
        $uid = $data['uid'];

        $lastreturn = array();       //最后返回的多维数组

        for ($i = 0; $i < $all_count; $i++) {
            // return ($obj_count);
            $stat_info = $this->comlike->getStat($object_ids[$i], $object_types[$i]);
            if (empty($stat_info[0])) {
                $stat_info['comment_count'] = 0;
                $stat_info['like_count'] = 0;
                $stat_info['like_record'] = array();
            }
            //对赞的用户添加URL
            $like_record = array();
            if ($stat_info[0]['like_record']) {
                //这里先得进行转化like_record为数组
                $lkr = json_decode($stat_info[0]['like_record'], true);
                //return $lkr;
                foreach ($lkr as $item) {
                    if ($item['uid'] == $uid) {
                        $item['username'] = "我";
                    } else {
                        $item['username'] = $item['username'];
                    }
                    //去除非有效数据
                    $reitem['uid'] = $item['uid'];
                    $reitem['username'] = $item['username'];
                    $like_record[] = $reitem;
                }
            }

            $return = array(
                'state' => 1,
                'comment_ID' => $object_ids[$i],
                'pageType' => $object_types[$i],
                'count' => $stat_info[0]['comment_count'], //对象评论总数
                'greeCount' => $stat_info[0]['like_count'], //赞的总数
                'isgree' => $this->comlike->checkMyLike($object_ids[$i], $object_types[$i], $uid), //我是否赞了
                'data' => array(),
                'greepeople' => $like_record
            );

            //返回信息流的转发数，
            if (strstr($object_types[0], 'web_')) {
                $return['share_count'] = $this->comlike->getLen('web_topic', $tid[$i]);
            } else {
                $return['share_count'] = $this->comlike->getLen('topic', $tid[$i]);
            }
            //如果评论数为0，则不查询
            if ($stat_info[0]['comment_count']) {
                $params = array();
                $params['object_id'] = $object_ids[$i];
                $params['object_type'] = $object_types[$i];
                $params['primary_key'] = 'id';
                $params['limit'] = 3;               //默认3条显示
                $comment_list = $this->comlike->getComment($params);                             //得到全部相关评论

                $comment_object_ids = array_keys($comment_list['data']);               //得到对象评论的id
                $comment_stats = $this->comlike->getStat($comment_object_ids, 'comment');    //得到评论的赞的统计
                //$return['comment_stat'] = $comment_list;                             //该段暂未有任何使用

                $my_like_comments = $this->comlike->checkMyLike($comment_object_ids, 'comment', $uid); //得到评论中有我赞过的评论编号

                $my_del_comments = $this->comlike->checkDelComment($comment_object_ids, $object_types[$i], $uid); //得到可以删除的评论编号

                foreach ($comment_list['data'] as $item) {                               //得到相关评论自身数据
                    //判断对应统计        
                    $commnet_num = 0;
                    foreach ($comment_stats as $c_stat) {
                        if ($item['id'] == $c_stat['object_id']) {
                            $commnet_num = $c_stat['like_count'];
                            break;
                        }
                    }

                    $return['data'][] = array(
                        'cid' => $item['id'],
                        'name' => $item['username'],
                        'uid' => $item['uid'],
                        'content' => $item['content'],
                        'time' => tran_time($item['dateline']),
                        'isgree' => in_array($item['id'], $my_like_comments) ? true : false,
                        'isdel' => in_array($item['id'], $my_del_comments) ? true : false,
                        'greeNum' => $commnet_num,
                    );
                }
            }
            $lastreturn[] = $return;
        }
        return $this->comlike->toJson($lastreturn);
    }

    /**
     * 添加评论
     * 
     * @author  boolee
     * 
     * 数组参数说明
     * @param   object_id   评论对象id
     * @param   uid         当前用户id
     * @param   username    当前用户名
     * @param	src_uid     应用用户id
     * @param	object_type 应用属性，如相册abulm，问答ask,视频video等等
     * @param	usr_ip      客户端ip
     * @param	content     评论内容
     *  
     * @param   $data=array('object_id','uid','username','src_uid','object_type','usr_ip','content') 
     *
     * @return  json  
     */
    public function add_comment($data) {
        foreach ($data as $list) {
            if (empty($list)) {
                $return['state'] = 0;
                $return['msg'] = '无效的对象';
                $this->comlike->toJson($return);
            }
        }
        $ret = array();
        $ret['object_id'] = $data['object_id'];
        $ret['object_type'] = $data['object_type'];
        $ret['uid'] = $data['uid'];
        $ret['src_uid'] = $data['src_uid'];
        $r = mb_substr($data['content'], 0, 140, 'utf-8');
        if (empty($r) && !($r === '0')) {
            return $this->comlike->toJson(array('state' => 0, 'msg' => '请输入评论内容'));
        }
        $ret['content'] = preg_replace('/\s+/', ' ', str_replace(array('<', '>', '\\', "'", "　"), array('&#60;', '&#62;', '&#92;', '&#039;', " "), $r));
        /*
          if( strchr($ret['content'], '<') || strchr($ret['content'], '>')){
          $ret['content']=htmlentities($ret['content']);
          }
         */
        $ret['username'] = $data['username'];
        $ret['usr_ip'] = $data['usr_ip'];

        $cid = $this->comlike->addComment($ret);

        //进行评论和赞表的统计
        if ($cid) {
            $obj = $ret['object_id'];
            $uid = $ret['uid'];
            $type = $ret['object_type'];
            $check = $this->db->from('link_stat')->where("`object_id`='$obj' AND `object_type`='$type'")->get()->result();
            if ($check) {
                //更新
                $this->comlike->commentUpdate($ret['object_id'], $ret['object_type'], 'comment', 1);
            } else {
                //新加	
                $this->comlike->addRecord('comment', $ret['object_id'], $ret['object_type'], $data['uid'], $data['username']);
            }

            $return['state'] = 1;
            $return['cid'] = $this->db->select('title')->from('comments')->where("`object_id`='$obj' AND `uid`='$uid'")->order_by('dateline desc')->get()->result_array();
            $return['msg'] = $ret['content'];
        } else {
            $return['state'] = 0;
            $return['msg'] = '发表评论失败';
        }
        return $this->comlike->toJson($return);
    }

    /**
     * 删除评论
     * 评论人和被评人均可删除
     * 只能删除个人单条评论
     * 
     * 数组参数说明
     * @param	object_id   评论对象id
     * @param	uid         当前用户id
     *
     * @param	$data=array('object_id','uid')	对象编号
     * @return  json
     */
    public function del_comment($data) {
        foreach ($data as $list) {
            if (empty($list)) {
                $return['state'] = 0;
                $return['msg'] = '无效的对象';
                return $this->comlike->toJson($return);
            }
        }

        $object_id = $data['object_id'];
        $object_type = $data['object_type'];

        $comments = $this->comlike->getComment(array('id' => $object_id));
        //return   $comments;
        if (!$comments['data']) {
            $return['state'] = 0;
            $return['msg'] = '无法删除未定义对象';
            return $return;
        }
        $comment_info = $comments['data'][0];
        if ($data['uid'] == $comment_info['uid'] || $data['uid'] == $comment_info['src_uid']) {
            $query = $this->comlike->delComment($object_id, $object_type);
        }
        if ($query) {
            //删除对象赞统计
            $lin->delStat($object_id, $object_type);
            //更新对象评论统计
            $comment_count = $this->comlike->commentUpdate($comment_info['object_id'], $object_type, 'comment', -1);
            //删除相关赞
            $this->comlike->delrelateLike(array('object_id' => $object_id, 'object_type' => 'comment'));
            //返回评论条数，无论以上操作是否成功。
            return array('object_id' => $comment_info['object_id'], 'comment_count' => $comment_count);
        } else {
            //处理失败返回
            return FALSE;
        }
    }

    /**
     * 添加赞
     * 数组参数说明
     * @param	object_id   对象id,对象可以是任何id。
     * @param	object_type 应用属性，如相册abulm，问答ask,视频video等等
     * @param	uid         当前用户id
     * @param	username    当前用户名
     * @param	usr_ip      客户端ip
     *  
     * @param   $data=array('object_id','object_type','uid','username','usr_ip') 
     * @author  boolee
     * @return  json  
     */
    public function add_like($data) {
        if (empty($data['object_id']) || empty($data['object_type']) || empty($data['uid']) || empty($data['src_uid']) || empty($data['username']) || empty($data['usr_ip'])) {
            $return['state'] = 0;
            $return['msg'] = '无效的对象';
            $this->comlike->toJson($return);
        }
        $object_id = $data['object_id'];
        $object_type = $data['object_type'];
        $uid = $data['uid'];
        if (($this->db->from('Likes')->where("`object_id` = '$object_id' AND `object_type`='$object_type' AND `uid`='$uid'")->get()->result_array())) {
            $return['state'] = 0;
            $return['msg'] = '你已经赞过了';
        } else {
            $flag = $this->comlike->addLike($data);
            if ($flag) {
                //更新统计
                if ($this->db->from('link_stat')->where("`object_id` = '$object_id' AND `object_type`='$object_type'")->get()->result_array()) {
                    //更新
                    $this->comlike->commentUpdate($data['object_id'], $data['object_type'], 'like', 1, $data['uid'], $data['username']);
                } else {
                    //新加	
                    $this->comlike->addRecord('like', $data['object_id'], $data['object_type'], $data['uid'], $data['username']);
                }

                //赞添加后需要返回何种数据?
                $return = $this->get_object_stat($data['object_id'], $data['object_type'], $data['uid']);
                $return['state'] = 1;
            } else {
                $return['state'] = 0;
                $return['msg'] = "无法加入赞";
            }
        }

        return $return;
    }

    /**
     * 返回统计表对象的统计信息
     * 
     * @access	protected
     * @param	object_id
     * @param	uid
     * @return	array
     */
    public function get_object_stat($object_id, $object_type, $uid, $is_greepeople=TRUE) {
        $stat_info = $this->comlike->getStat($object_id, $object_type);
        $stat_info = $stat_info[0];    //取出来是都是二维数组

        $return = array();
        if ($is_greepeople)
            $return['greepeople'] = json_decode($stat_info['like_record'], true);
        $return['greeCount'] = $stat_info['like_count'];
        $return['isgree'] = $this->comlike->checkMyLike($object_id, $object_type, $uid);
        return $return;
    }

    /**
     * 删除赞
     * 
     * 数组参数说明
     * @param	object_id   赞对象id
     * @param	uid         当前用户id
     *
     * @param	$data=array('object_id','uid')	对象编号
     * @return  json
     */
    public function del_like($data) {
        foreach ($data as $list) {
            if (empty($list)) {
                $return['state'] = 0;
                $return['msg'] = '无效的对象';
                return $this->comlike->toJson($return);
            }
        }
        if ($this->comlike->delLike($data)) {
            $object_id = $data['object_id'];
            $object_type = $data['object_type'];
            $uid = $data['uid'];
            $this->comlike->commentUpdate($object_id, $object_type, 'like', -1, $uid);
            $return = $this->get_object_stat($object_id, $object_type, $uid, TRUE);
            $return['state'] = 1;
        } else {
            $return['state'] = 0;
            $return['msg'] = '无法删除赞';
        }
        return $return;
    }

    /**
     * Topic删除时，删除和Topic有关的所有Likes 
     * author By @郭建华    2012-05-02
     * @param array $data = array('object_id','object_type')
     * @return string
     */
    public function delObject($data='') {
        if (empty($data)) {
            $return['state'] = 0;
            $return['msg'] = '无效的对象';
            return $this->comlike->toJson($return);
        }

        $object_id = is_array($data['object_id']) ? $data['object_id'] : array($data['object_id']);
        $object_type = $data['object_type'];
        $this->delComment($object_id, $object_type);
        return true;
    }

    /**
     * 删除关于信息流的所有评论\统计信息
     * @param array $object_id
     * @param string $object_type
     * @author by guojianhua
     */
    public function delComment($object_id, $object_type) {
        if ($object_type == 'topic') {
            foreach ($object_id as $tid) {
                $topic = $this->_redis->hGetAll("Topic:" . $tid);
                if ($topic ['type'] == 'info') {
                    $this->comlike->delComment($topic ['tid'], 'topic');
                    $this->comlike->delStat($topic['tid'], 'topic', 0);
                    $this->comlike->del_like($topic['tid'], 'topic');
                } else {
                    $this->comlike->delComment($topic ['fid'], $topic ['type']);
                    $this->comlike->delStat($topic['fid'], $topic['type'], 0);
                    $$this->comlike->del_like($topic['fid'], $topic['type']);
                }
            }
        } else {
            foreach ($object_id as $tid) {
                $topic = $this->_redis->hGetAll("Webtopic:" . $tid);
                if ($topic ['type'] == 'info') {
                    $this->comlike->delComment($topic ['tid'], 'web_topic');
                    $this->comlike->delStat($topic['tid'], 'web_topic', 0);
                    $this->comlike->del_like($topic['tid'], 'web_topic');
                } else {
                    $this->comlike->delComment($topic ['fid'], 'web_' . $topic ['type']);
                    $this->comlike->delStat($topic['fid'], 'web_' . $topic['type']);
                    $this->comlike->del_like($topic['tid'], 'web_' . $topic['type']);
                }
            }
        }
    }

    /**
     * 根据Web_id获取网页下面的所有ID
     * @param int $web_id
     * @return Array $tids
     */
    public function getTidsByWebId($web_id = 0) {
        $dates = $this->_redis->hKeys("webpage:" . $web_id . ':infos');
        $tids = array();
        foreach ($dates as $date) {
            $tids = array_merge($tids, $this->_redis->zRange('webpage:' . $web_id . ':' . $date, 0, -1));
        }
        return $tids;
    }

    /**
     * 删除网页中所有信息流中的赞、评论、统计
     * @param int $web_id
     * @return string
     */
    public function delWebPage($web_id = 0) {
        if (!web_id) {
            return json_encode(array("status" => 0, "msg" => "网页ID错误"));
        }
        $tids = $this->getTidsByWebId($web_id);

        if (!count($tids)) {
            return FALSE;
        }
        if ($this->delObject(array("object_id" => $tids, "object_type" => "web_topic"))) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 二次加载获取所有相关评论与赞
     * 
     * 数组参数说明
     * @param	object_id   赞对象id
     * @param	uid         当前用户id
     *
     * @param	$data=array('object_id','uid')	对象编号
     * @return  json
     * */
    public function get_all_comment($data) {
        $object_id = $data['object_id'];
        $object_type = $data['object_type'];

        if (empty($object_id)) {
            $return['state'] = 0;
            $return['msg'] = '无效的对象';
            $this->comlike->toJson($return);
        }
        $uid = $data['uid'];
        $stat_info = $this->comlike->getStat($object_id, $object_type);
        if (empty($stat_info)) {
            $return['state'] = 0;
            $return['msg'] = '不存在的对象';
            $this->comlike->toJson($return);
        }
        $params = array();
        $params['object_id'] = $object_id;
        $params['object_type'] = $object_type;
        $params['pagesize'] = $data['page'];
        $params['primary_key'] = 'id';
        $params['limit'] = 50;
        $comment_list = $this->comlike->getComment($params);
        //得到评论的赞的统计ids
        $comment_object_ids = array_keys($comment_list['data']);
        //每条评论赞的统计
        $comment_stats = $this->comlike->getStat($comment_object_ids, 'comment');
        //得到评论中有我赞过的评论编号
        $my_like_comments = $this->comlike->checkMyLike($comment_object_ids, 'comment', $uid);

        //得到可以删除的评论编号
        $my_del_comments = $this->comlike->checkDelComment($comment_object_ids, $object_type, $uid);

        //整体返回数组
        $return = array(
            'state' => 1,
            'count' => $stat_info[0]['comment_count'],
            'greeCount' => $stat_info[0]['like_count'],
            'isgree' => $this->comlike->checkMyLike($object_id, $object_type, $uid),
            'data' => array(),
        );
        //返回评论相关的详细数组
        foreach ($comment_list['data'] as $item) {                               //得到相关评论自身数据
            //判断对应统计        
            $commnet_num = 0;
            foreach ($comment_stats as $c_stat) {
                if ($item['id'] == $c_stat['object_id']) {
                    $commnet_num = $c_stat['like_count'];
                    break;
                }
            }

            $return['data'][] = array(
                'cid' => $item['id'],
                'name' => $item['username'],
                'uid' => $item['uid'],
                'content' => $item['content'],
                'time' => tran_time($item['dateline']),
                'isgree' => in_array($item['id'], $my_like_comments) ? true : false,
                'isdel' => in_array($item['id'], $my_del_comments) ? true : false,
                'greeNum' => $commnet_num,
            );
        }
        return $this->comlike->toJson($return);
    }

    /**
     * 获取赞
     * 
     * 数组参数说明
     * @param	object_id     赞对象id
     * @param	uid           当前用户id
     * @param	object_type   对象类型
     * @param	field		     查询字段
     * @param	start_dateline起始时间
     * @param	is_stat       
     * @param	order		     排序规则
     * @param	pagesize page 分页	  
     * @param	returntype	    返回类型
     * 
     * @return  json
     */
    public function getLike($params) {
        $return = $this->comlike->getLikes($params);
        if ($return) {
            return $this->comlike->toJson($return);
        } else {
            return false;
        }
    }

    /**
     * 取得统计数
     *
     * @param object_id
     * @param object_type
     * @return json
     */
    public function getStat($object_id, $object_type) {
        return $this->comlike->getStat($object_id, $object_type);
    }

    /**
     * 取得统计数
     */
    public function getStatList($obj_ids, $object_type, $return_fields = array()) {
        return $this->comlike->getStatList($obj_ids, $object_type, $return_fields);
    }

    /**
     * 根据object_id,object_type更新ctime字段
     * @param int $object_id
     * @param string $object_type
     * @param string $ctime
     * @return boolean
     */
    public function update_Like($object_id, $object_type, $ctime) {
        if (empty($object_id) || empty($object_type) || empty($ctime)) {
            return false;
        }
        return $this->comlike->update_Like($object_id, $object_type, $ctime);
    }

}