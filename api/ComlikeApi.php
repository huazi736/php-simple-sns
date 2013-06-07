<?php

/**
 * 评论, 转发, 赞 公共接口
 * @author yangshunjun
 */
class ComlikeApi extends DkApi {

    protected $comlike;
    protected $comment;
    protected $like;
    protected $linkStat;
    protected $share;

    public function __initialize() {
        $this->comlike = DKBase::import('Comlike', 'comlike');
        $this->comment = DKBase::import('Comment', 'comlike');
        $this->like = DKBase::import('Like', 'comlike');
        $this->linkStat = DKBase::import('LinkStat', 'comlike');
        $this->share = DKBase::import('Share', 'share');
    }
    
    /**
     * 一次性获取多条评论和赞
     * 
     * @access public
     * 
     * @param  array   $object_id   评论对象id
     * @param  array   $object_type 评论对象类型
     * @param  string  $aids        被操作用户的ID
     * @param  array   $tid         时间线ID
     * @param  integer $uid         当前用户id
     * @param  integer $web_id      当前网页ID
     * 
     * @return array
     */
    public function get_stat_all($oids, $types, $aids, $tids = array(), $uid, $web_id = 0) {
    	
        if (count($oids) != count($types) || count($oids) != count($aids)) {
        	return array();
        }
		
        // 返回的数据
        $lastreturn = array();
        foreach ($oids as $key => $obj) {
        	$type = $types[$key];
        	$return = array(
                'state'        => 1,
                'comment_ID'   => $obj,
                'pageType'     => $type,
                'count'        => 0,
                'greeCount'    => 0,
        		'share_count'  => 0,
                'isgree'       => 0,
                'favoriteNums' => 0,
                'isFavorite'   => 0,
        		'data'         => array(),
                'greepeople'   => array(),
            );
        	
        	// 取得统计信息
        	$stat_info = $this->linkStat->getStat($obj, $type);
        	if ($stat_info) {
            	$return['count']        = $stat_info[0]['comment_count'];
                $return['greeCount']    = $stat_info[0]['like_count'];
                $return['favoriteNums'] = $stat_info[0]['favorite_count'];
            }
            
        	// 判断是否已收藏 
            if (service('Favorite')->checkFavorite($obj, $type, $uid)) {
                $return['isFavorite'] = 1;
            }
            
            // 判断是否赞过
            if ($this->like->checkMyLike($obj, $type, $uid)) {
            	$return['isgree'] = 1;
            }
            
        	// 取得分享数
        	$tid = 0;
        	$aid = $aids[$key];
        	if ($tids) {
        		$tid = $tids[$key];
        	}
            $return['share_count'] = $this->_getShareCount($obj, $type, $uid, $tid, $aid, $web_id);
            
            // 调取最新的三条评论
            if ($return['count']) {
            	$params = array(
            		'object_id' => $obj,
                	'object_type' => $type,
                	'primary_key' => 'id',
                	'limit' => 3
            	);
            	$comment_list = $this->comment->getComment($params);
            	$comment_list = $comment_list['data'];
            	$coids = array_keys($comment_list);
            	
            	// 评论赞的统计
            	$comment_stats = $this->linkStat->getStat($coids, 'comment');
            	
            	// 得到评论中有我赞过的评论编号
                $my_like_comments = $this->like->checkMyLike($coids, 'comment', $uid);
            	foreach ($comment_list as $com) {
            		$tmp = array(
                        'cid'     => $com['id'],
                        'name'    => $com['username'],
                        'uid'     => $com['uid'],
                        'content' => $com['content'],
                        'time'    => friendlyDate($com['dateline']),
                        'isgree'  => in_array($com['id'], $my_like_comments) ? true : false,
                        'isdel'   => false,
                        'greeNum' => 0,
                        'isReply' => $uid == $com['uid'] ? false : true,
                    );
                    if ($uid == $com['uid'] || $uid == $com['src_uid']) {
                    	$tmp['isdel'] = true;
                    }
                    
                    if (isset($comment_stats[$com['id']])) {
                    	$tmp['greeNum'] = $comment_stats[$com['id']]['like_count'];
                    }
                    
                    $return['data'][] = $tmp;
            	}
            }
            
            $lastreturn[] = $return;
        }

        return $lastreturn;
    }
    
    /**
     * 
     * 取得对象的分享次数
     * @param integer $obj
     * @param string  $type
     * @param integer $uid
     * @param integer $tid
     * @param integer $aid
     * @param integer $web_id
     * @return integer
     */
    private function _getShareCount($obj, $type, $uid, $tid = 0, $aid = 0, $web_id = 0) {
    	
        if (!$tid) {
        	if (strstr($type, 'web_')) {
        		$infos = service('WebTimeline')->getWebtopicByMap($obj, str_replace('web_', '', $type), $web_id);
        	} else {
        		$infos = service('Timeline')->getTopicByMap($obj, $type, $aid);
        	}
        	
        	if (!$infos) {
        		return 0;
        	}
        	$tid = $infos['tid'];
        }
        
    	// 取得分享数
        $shareType = 'topic';
        if (strstr($type, 'web_')) {
        	$shareType = 'web_topic';
        }
        return $this->share->getLen($shareType, $tid);
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
        if(!$data['object_id'] || !$data['uid'] || !$data['username'] || !$data['src_uid'] || !$data['object_type'] || ($data['content'] == '') || !$data['usr_ip']){
        	return false;
        }
        
        $comment_str = mb_substr($data['content'], 0, 140, 'utf-8');
        
        //评论数据
        $valuse = array(); 
        
        $ret['content']      = preg_replace('/\s+/', ' ', str_replace(array('<', '>', '\\', "'", "　"), array('&#60;', '&#62;', '&#92;', '&#039;', " "), $comment_str));
        $ret['object_id']    = $data['object_id'];
        $ret['object_type']  = $data['object_type'];
        $ret['uid']          = $data['uid'];
        $ret['src_uid']      = $data['src_uid'];
        $ret['username']     = $data['username'];
        $ret['usr_ip']       = $data['usr_ip'];
        $ret['web_id']       = (array_key_exists('web_id', $data) && $data['web_id'] > 1) ? $data['web_id'] : 0 ;
        
        $cid =  $this->comment->addComment($ret);
        
        //进行评论和赞表的统计
        if ($cid) {
            $check = $this->linkStat->get_link_stat(array('object_id' => $ret['object_id'], 'object_type' => $ret['object_type']));
            if ($check) {
                //更新
                $this->linkStat->commentUpdate($ret['object_id'], $ret['object_type'], 'comment', 1);
            } else {
                //新加	
                $this->linkStat->addRecord('comment', $ret['object_id'], $ret['object_type'], $ret['uid'], $ret['username'], $ret['web_id']);
            }
			
            //查询当前评论信息
            $result = $this->comment->get_comments(array('object_id' => $ret['object_id'], 'uid' => $ret['uid']), 'id', 'dateline desc', 1);
            
            $return['state'] = 1;
            $return['cid'] = $result['id'];
            $return['msg'] = $ret['content'];
        } else {
            $return['state'] = 0;
            $return['msg'] = '发表评论失败';
        }
        return $return;
    }

    /**
     * 删除评论
     * 评论人和被评人均可删除
     * 只能删除个人单条评论
     * 
     * 数组参数说明
     * @param	$id   评论对象id
     * @param	$uid  当前用户id
     *
     * @return  array
     */
    public function del_comment($id, $uid) {
    	if (!$id || !$uid) {
            return array('state' => 0, 'msg' => '无法删除未定义对象');
        }
        
        $comments = $this->comment->getComment(array('id' => $id));
    	if (!$comments || !$comments['data'] || ($uid != $comments['data'][0]['uid'] && $uid != $comments['data'][0]['src_uid'])) {
           return array('state' => 0, 'msg' => '删除评论失败.');
        }
        $query = $this->comment->delComment($id, $comments['data'][0]['object_type']);
        
        $object_id   = $comments['data'][0]['object_id'];
        $object_type = $comments['data'][0]['object_type'];

        //删除对象赞统计
        $this->linkStat->delStat($id, $object_type);

        //更新对象评论统计
        $comment_count = $this->linkStat->commentUpdate($object_id, $object_type, 'comment', -1);

        //删除相关赞
        $this->like->delrelateLike(array('object_id' => $id, 'object_type' => 'comment'));
        
        //返回评论条数，无论以上操作是否成功。
        return array('state' => 1, 'object_id' => $object_id, 'object_type' => $object_type, 'comment_count' => $comment_count);
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
            return array('state'=>0, 'msg' => '无效的对象');
        }
        
        if ($this->like->check_like_status($data['object_id'], $data['object_type'], $data['uid'])) {
            return array('state'=>0, 'msg' => '你已经赞过了');
        } else {
            $flag = $this->like->addLike($data);
            if ($flag) {
                //更新统计
                if ($this->linkStat->check_stat(array('object_id' => $data['object_id'], 'object_type' => $data['object_type']))) {
                    //更新
                    $this->linkStat->commentUpdate($data['object_id'], $data['object_type'], 'like', 1, $data['uid'], $data['username']);
                } else {
                    //新加	
                    $this->linkStat->addRecord('like', $data['object_id'], $data['object_type'], $data['uid'], $data['username'], $data['web_id']);
                }

                //赞添加后需要返回数据
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
    public function get_object_stat($object_id, $object_type, $uid, $is_greepeople=true) {
        $stat_info = $this->linkStat->getStat($object_id, $object_type);

        $stat_info = $stat_info[0];

        $return = array();
        if ($is_greepeople) {
            $return['greepeople'] = json_decode($stat_info['like_record'], true);
            $return['greeCount'] = $stat_info['like_count'];
            
            $return['isgree'] = $this->like->checkMyLike($object_id, $object_type, $uid);
        }
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
    	if(empty($data) || !is_array($data)){
    		return array('state'=>0, 'msg' => '无效对象。');
    	}
    	
        if ($this->like->delLike($data)) {
            if ($this->linkStat->check_stat(array('object_id' => $data['object_id'], 'object_type' => $data['object_type']))) {
                $this->linkStat->commentUpdate($data['object_id'], $data['object_type'], 'like', -1, $data['uid']);
            }
            $return = $this->get_object_stat($data['object_id'], $data['object_type'], $data['uid'], true);
            $return['state'] = 1;
        } else {
            $return['state'] = 0;
            $return['msg'] = '无法删除赞';
        }
        return $return;
    }

    /**
     * Topic删除时，删除和Topic有关的所有Likes 
     * @param array $data = array('object_id','object_type')
     * @return string
     */
    public function delObject($data='') {
        if (empty($data)) {
            return array('state'=>0, 'msg' => '无效对象。');
        }

        $object_id = is_array($data['object_id']) ? $data['object_id'] : array($data['object_id']);
        $web_id = isset($data['web_id']) ? $data['web_id'] : 0;
      	
        $object_type = $data['object_type'];
        $ret = $this->delComment($object_id, $object_type, $web_id);
        return $ret;
    }

    /**
     * 删除关于信息流的所有评论\统计信息
     * @param array $object_id
     * @param string $object_type
     * @author by guojianhua
     */
    public function delComment($object_id, $object_type, $web_id = 0) {
        if ($object_type == 'topic') {
            foreach ($object_id as $tid) {
                $topic = service('Timeline')->getTopicByTid($tid);
                if(!empty($topic)){
	                if ($topic ['type'] == 'info') {
	                    $this->comment->delComment($topic ['tid'], 'topic');
	                    $this->linkStat->delStat($topic['tid'], 'topic', 0);
	                    $this->like->delLike($topic['tid'], 'topic');
	                } else if($topic ['type'] == 'forward'){
	                	$this->comment->delComment($topic ['fid'], $topic ['type']);
	                    $this->linkStat->delStat($topic['fid'], $topic['type'], 0);
	                    $this->like->delLike($topic['fid'], $topic['type']);
	                }else {
	                    $this->comment->delComment($topic ['tid'], $topic ['type']);
	                    $this->linkStat->delStat($topic['tid'], $topic['type'], 0);
	                    $this->like->delLike($topic['tid'], $topic['type']);
	                }
                }
            }
        } else {
            foreach ($object_id as $tid) {
                $topic = service('WebTimeline')->getWebtopicByMap($tid, '', $web_id);
                if(!empty($topic)){
	                if ($topic ['type'] == 'info') {
	                    $this->comment->delComment($topic ['tid'], 'web_topic');
	                    $this->linkStat->delStat($topic['tid'], 'web_topic', 0);
	                    $this->like->delLike($topic['tid'], 'web_topic');
	                } else if($topic ['type'] == 'forward'){
	                	$this->comment->delComment($topic ['fid'], 'web_' . $topic ['type']);
	                    $this->linkStat->delStat($topic['fid'], 'web_' . $topic['type']);
	                    $this->like->delLike($topic['tid'], 'web_' . $topic['type']);
	                } else {
	                    $this->comment->delComment($topic ['tid'], 'web_' . $topic ['type']);
	                    $this->linkStat->delStat($topic['tid'], 'web_' . $topic['type']);
	                    $this->like->delLike($topic['tid'], 'web_' . $topic['type']);
	                }
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
        $dates = $this->share->hKeys("webpage:" . $web_id . ':infos');
        $tids = array();
        foreach ($dates as $date) {
            $tids = array_merge($tids, $this->share->zRange('webpage:' . $web_id . ':' . $date, 0, -1));
        }
        return $tids;
    }

    /**
     * 删除网页中所有信息流中的赞、评论、统计
     * @param int $web_id
     * @return string
     */
    public function delWebPage($web_id = 0) {
    	if (!$web_id) {
            return false;
        }
        
        //删除统计
        $this->linkStat->delStat('', '', 0, $web_id);
        
        //删除评论
        $this->comment->delComment('', '', $web_id);
        
        //删除赞
        $this->like->delete_Like('', '', $web_id);
        
        return true;
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
            return false;
        }
        $uid = $data['uid'];
        $stat_info = $this->getStat($object_id, $object_type);
        if (empty($stat_info)) {
            return false;
        }
        $params = array();
        $params['object_id'] = $object_id;
        $params['object_type'] = $object_type;
        $params['pagesize'] = $data['page'];
        $params['primary_key'] = 'id';
        $params['limit'] = 5;

        $comment_list = $this->comment->getComment($params);
        //得到评论的赞的统计ids
        $comment_object_ids = array_keys($comment_list['data']);
        //每条评论赞的统计
        $comment_stats = $this->linkStat->getStat($comment_object_ids, 'comment');
        //得到评论中有我赞过的评论编号
        $my_like_comments = $this->like->checkMyLike($comment_object_ids, 'comment', $uid);
        //得到可以删除的评论编号
        $my_del_comments = $this->linkStat->checkDelComment($comment_object_ids, $object_type, $uid);

        //整体返回数组
        $return = array(
            'state' => 1,
            'count' => $stat_info[0]['comment_count'],
            'greeCount' => $stat_info[0]['like_count'],
            'isgree' => $this->like->checkMyLike($object_id, $object_type, $uid),
            'data' => array(),
        );
        //返回评论相关的详细数组
        foreach ($comment_list['data'] as $item) {                               //得到相关评论自身数据
            //判断对应统计        
            $commnet_num = 0;
            if (!empty($comment_stats)) {
                foreach ($comment_stats as $c_stat) {
                    if ($item['id'] == $c_stat['object_id']) {
                        $commnet_num = $c_stat['like_count'];
                        break;
                    }
                }
            }

            $return['data'][] = array(
                'cid' => $item['id'],
                'name' => $item['username'],
                'uid' => $item['uid'],
                'content' => $item['content'],
                'time' => friendlyDate($item['dateline']),
                'isgree' => in_array($item['id'], $my_like_comments) ? true : false,
                'isdel' => in_array($item['id'], $my_del_comments) ? true : false,
                'greeNum' => $commnet_num,
                'isReply' => $uid == $item['uid'] ? false : true,
            );
        }
        return $return;
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
        $return = $this->like->getLikes($params);
        
        if ($return) {
            return $return;
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
        return $this->linkStat->getStat($object_id, $object_type);
    }

    /**
     * 取得统计数
     */
    public function getStatLists($obj_ids, $object_type, $return_fields = array()) {
        return $this->linkStat->getStatList($obj_ids, $object_type, $return_fields);
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
        return $this->like->update_Like($object_id, $object_type, $ctime);
    }
    
    /**
     * 获取分享源信息流信息
     */
   public function shareFid($tid, $type = 'topic'){
   		if(!$tid) return false;
   		$type = $type ? : 'topic';
    	$share_info = $this->share->smembers('Stat:' . $type, $tid);
    	
    	return $share_info;
   }
   
    /**
     * 
     * 检查推荐权限
     * @param string $pageType 检查的类型
     * @param string $key
     * @return boolean
     */
	public function checkAllowType($pageType, $key = '') {
   		
		$types = getConfig('recommend', 'allow_type');
		$result = array();
		foreach ($types as $k => $t) {
			if (!empty($key) && $k == $key) {
				$result = $t;
				break;
			} else {
				$result = array_merge($result, $t);
			}
		}
		return $result;
		if ($result) {
			return in_array($pageType, $result);
		}
		return false;
	}
	
	/**
	 * 
	 * 取得多条数据的推荐相关
	 * @param integer|array  $oids   对象ID
	 * @param string|array   $types  对象类型，如果是数组，个数要和ID的数量保持一致，同一种类型可使用array_pad填充到指定个数
	 * @param string|array   $aids   信息发布者的ID
	 * @param integer|array  $tid    时间线ID
	 * @param integer        $uid    当前用户ID
	 * @param integer        $web_id 网页ID
	 * @return Array(
	 * 			    [对象ID] => Array
	 * 			        (
	 * 			            [state] => 1
	 * 			            [comment_ID] => 对象ID
	 * 			            [pageType] => 对象类型
	 * 			            [count] => 评论数量
	 * 			            [greeCount] => 赞的数量
	 * 			            [isgree] => 是否赞过
	 * 			            [data] => Array
	 * 			                (最新三条评论)
	 * 			            [greepeople] => Array
	 * 			                (最近三个赞过的人)
	 * 			            [favoriteNums] => 收藏数量
	 * 			            [isFavorite] => 是否收藏
	 * 			            [share_count] => 分享数量
	 * 			        )
	 * 			
	 * 			)
	 * 
	 */
	public function getRecommendData($oids, $types, $aids, $tids = array(), $uid, $web_id = 0) {
		
		if (!$oids || !$types || !$uid || !$aids) {
			return array();
		}
		$oids  = is_array($oids) ? $oids : array($oids);
		$types = is_array($types) ? $types : array($types);
		$aids  = is_array($aids) ? $aids : array($aids);
		$tids  = is_array($tids) ? $tids : array($tids);
		
		$data = $this->get_stat_all($oids, $types, $aids, $tids, $uid, $web_id);
		$result = array();
		foreach ($data as $key => $d) {
			$result[$d['comment_ID']] = $d;
			$result[$d['comment_ID']]['data'] = $this->_getUserData($d['data']);
			$result[$d['comment_ID']]['greepeople'] = $this->_getUserData($d['greepeople']);
		}
		
		return $result;
	}
	
	/**
	 * 
	 * 获取用户的头像和生成链接
	 * @param array $data
	 * @return array
	 */
	private function _getUserData($data) {
		
		if (!$data || !is_array($data)) {
			return array();
		}
		
		// 取出UID，用于获取用户的dkcode
		$uids = array();
		foreach ($data as $key => $d) {
			if ($d['uid']) {
				$uids[] = $d['uid'];
			}
		}
		$userinfo = service('User')->getUserList($uids, array('uid', 'dkcode'));
		if (!$userinfo) {
			return array();
		}
		$userCodes = array();
		foreach ($userinfo as $user) {
			$userCodes[$user['uid']] = $user['dkcode'];
		}
		
		foreach ($data as $key => $d) {
			if ($d['uid']) {
				$data[$key]['imgUrl'] = get_avatar($d['uid']);
				$data[$key]['url'] = mk_url('main/index/profile', array('dkcode' => $userCodes[$d['uid']]));
			}
		}
		
		return $data;
	}
}

?>
