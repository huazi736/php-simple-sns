<?php 
/**
 * 评论,转发,赞综合接口
 * @author yangshunjun
 */

class ComlikeService extends DK_Service {
	
 	public function __construct() {
 		
        parent::__construct();
        
        $this->init_db('system');
        $this->init_redis();
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
        
		public function get_stat_all($data)
        {  		
        		//数据合法性检测
            	$obj_count			= substr_count($data['object_id'],',');
        		$object_type_count	= substr_count($data['object_type'],',');
            	if( empty($data['uid']) || ($obj_count != $object_type_count) ) {
            		$result = 1;
            	} else {
            		$result  = 0;
            	}
                $all_count			= $obj_count+1;		//需要处理的数据总数			
                if($result){
                	 $return['state'] = $result;
                     $return['msg']   = '无效的对象';
                     return $return;
                }
                /*检测结束*/
                
                $object_ids   = explode(',', $data['object_id']);
                $object_types = explode(',', $data['object_type']);
                $tid 		  = explode(',', $data['tid']);
                $uid          = $data['uid'];

                $lastreturn = array();							//最后返回的多维数组
                
                for( $i=0; $i<$all_count; $i++){
                	// return ($obj_count);
                	$stat_info = $this->getStat($object_ids[$i],$object_types[$i]);
                	
	                if(empty($stat_info[0])){
	                    $stat_info[0]['comment_count'] = 0;
	                    $stat_info[0]['like_count']    = 0;
	                    $stat_info[0]['like_record']   = array();
	                    $stat_info[0]['favorite_count']   = 0;
	                }
	                //对赞的用户添加URL
	                $like_record = array();
	                if(isset($stat_info[0]['like_record']) && $stat_info[0]['like_record']){
	                	//这里先得进行转化like_record为数组
	                	$lkr = json_decode($stat_info[0]['like_record'],true);
	                	//return $lkr;
	                	if(is_array($lkr) && !empty($lkr)){
		                	foreach($lkr as $item){
			                    if($item['uid'] == $uid){
									$item['username'] = "我";
								}else{
									$item['username'] = $item['username'];  
								}

		                        //去除非有效数据
		                        $reitem['uid']=$item['uid']; 
		                        $reitem['username']=$item['username'];     
			                    $like_record[] = $reitem;
			                }
	                	}
                	}
                	
                	//判断是否已收藏 
                	if(service('Favorite')->checkFavorite($object_ids[$i], $object_types[$i], $uid)){
                		$isFavorite = 1;
                	} else {
                		$isFavorite = 0;
                	}
                	$return = array(
	                    'state'        =>    1,
                		'comment_ID'   =>    $object_ids[$i],
                		'pageType'	   =>	 $object_types[$i],
	                    'count'        =>    $stat_info[0]['comment_count'],                     //对象评论总数
	                    'greeCount'    =>    $stat_info[0]['like_count'],						 //赞的总数
	                    'isgree'       =>    $this->checkMyLike($object_ids[$i], $object_types[$i], $uid), //我是否赞了
	                    'data'         =>    array(),
	                    'greepeople'   =>    $like_record,
                		'favoriteNums' =>    intval($stat_info[0]['favorite_count']),
                		'isFavorite'   =>    $isFavorite,
	                );
	                
	                //返回信息流的转发数，
	 				if(strstr($object_types[0],'web_')){
	 					$return['share_count']= $this->getLen('web_topic',$tid[$i]);
	 				}else{
	 					$return['share_count']= $this->getLen('topic',$tid[$i]);
	 				}
	 				
	                //如果评论数为0，则不查询
	                if($stat_info[0]['comment_count']){
	                    $params = array();
	                    $params['object_id']   = $object_ids[$i];
	                    $params['object_type'] = $object_types[$i];
	                    $params['primary_key'] = 'id';
	                    $params['limit'] = 3;												   //默认3条显示
	                    $comment_list = $this->getComment($params);                            //得到全部相关评论
	                    
	                    $comment_object_ids = array_keys($comment_list['data']);               //得到对象评论的id
	                    $comment_stats      = $this->getStat($comment_object_ids,'comment');    //得到评论的赞的统计
	
	                    //$return['comment_stat'] = $comment_list;                             //该段暂未有任何使用
	                    
	                    $my_like_comments = $this->checkMyLike($comment_object_ids, 'comment', $uid);//得到评论中有我赞过的评论编号
	                    $my_del_comments = $this->checkDelComment($comment_object_ids, $object_types[$i], $uid);//得到可以删除的评论编号
						
	                    foreach($comment_list['data'] as $item){                               //得到相关评论自身数据
	                                
	                        //判断对应统计        
	                        $commnet_num=0;
	                        if(!empty($comment_stats)){
		                        foreach($comment_stats as $c_stat){
		                        	if($item['id'] == $c_stat['object_id']){
		                        		$commnet_num = $c_stat['like_count'];
		                        		break;
		                        	}
		                        }
	                        }
	                        
	
	                        $return['data'][] = array(
	                                        'cid'        =>    $item['id'],
	                                        'name'       =>	   $item['username'],
	                                        'uid'        =>    $item['uid'],
	                                        'content'    =>    $item['content'],
	                                        'time'       =>    $this->tran_time($item['dateline']),
	                                        'isgree'     =>    in_array($item['id'], $my_like_comments) ? true : false,
	                            			'isdel'		 =>    in_array($item['id'], $my_del_comments) ? true : false,
	                            			'greeNum'    =>    $commnet_num,
	                                        'isReply'    =>    $uid == $item['uid'] ? false : true,
	                        );
						   
	                    }
	                    
	                } 
	                $lastreturn[] = $return;
                }	
                return json_encode($lastreturn);
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
        public function add_comment($data)
        {
                foreach($data as $list){
                    if(empty($list)){
                        $return['state'] = 0;
                        $return['msg'] = '无效的对象';
                        $this->toJson($return);
                    }
                }
                $ret=array();
                $ret['object_id']   = $data['object_id'];
                $ret['object_type'] = $data['object_type'];
                $ret['uid']         = $data['uid'];
                $ret['src_uid']     = $data['src_uid'];
                $r = mb_substr($data['content'], 0, 140, 'utf-8');
                if(empty($r) && !($r==='0')){
                	return $this->toJson(array('state'=>0,'msg'=>'请输入评论内容'));
                }
                $ret['content']     = preg_replace('/\s+/',' ',str_replace(array('<','>','\\', "'", "　"), array('&#60;','&#62;','&#92;', '&#039;', " "), $r));
                /*
                if( strchr($ret['content'], '<') || strchr($ret['content'], '>')){
                	$ret['content']=htmlentities($ret['content']);
                }
                */
                $ret['username']    = $data['username'];
                $ret['usr_ip']      = $data['usr_ip'];
                
                $cid = $this->addComment($ret);

                //进行评论和赞表的统计
                if($cid){
                	$obj  =	$ret['object_id'];
                	$uid  =	$ret['uid'];
                	$type =	$ret['object_type'];
                	$check= $this->db->from('link_stat')->where(array('object_id'=>$obj, 'object_type'=>$type))->get()->result_array();
                	if($check){
                	//更新
                			$this->commentUpdate($ret['object_id'],$ret['object_type'],'comment',1);
                			
                	}else{
                	//新加	
                			$this->addRecord('comment',$ret['object_id'],$ret['object_type'],$data['uid'],$data['username']);	
                	}
     
                    
                    $result = $this->db->from('comments')->select('id')->where(array('object_id'=>$obj, 'uid'=> $uid))->order_by('dateline desc')->limit(1)->get()->row_array();
                    $return['state'] = 1;
                    $return['cid'] = $result['id'];
                    $return['msg'] =   $ret['content'];
                }else{
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
    public function del_comment($id, $uid)
    {
    	$return = array('state' => 0, 'msg' => '无法删除未定义对象');
        if (!$id || !$uid) {
       		return $return;
        }

        $comments = $this->getComment(array( 'id' => $id )); 
        if(!$comments || !$comments['data']){
            return $return;
        }
        
        $comment_info = $comments['data'][0];
        if ($uid != $comment_info['uid'] && $uid != $comment_info['src_uid']) {
            return $return;
        }
        
        $query = $this->delComment($id, $comment_info['object_type']);
        if(!$query){
        	return $return;
        }
        
        $object_type = $comment_info['object_type'];
        
        //删除对象赞统计
        $this->delStat($id, $object_type);
            
        //更新对象评论统计
        $comment_count = $this->commentUpdate($comment_info['object_id'], $object_type, 'comment', -1);
            
        //删除相关赞
        $this->delrelateLike(array('object_id' => $id, 'object_type'=>'comment'));
            
        //返回评论条数，无论以上操作是否成功。
        return array('state' => 1, 'object_id' => $comment_info['object_id'], 'object_type' => $object_type, 'comment_count' => $comment_count);
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
	      *@return  json  
	     */
	    public function add_like($data)
	    {	
	        if(empty($data['object_id']) || empty($data['object_type'])|| empty($data['uid']) || empty($data['src_uid']) || empty($data['username']) || empty($data['usr_ip']) ){
	            $return['state'] = 0;
	            $return['msg'] = '无效的对象';
	        }
	        $object_id   = $data['object_id'];
	        $object_type = $data['object_type'];
	        $uid		 = $data['uid'];
	        if($this->db->from('likes')->where(array('object_id' => $object_id, 'object_type' => $object_type, 'uid' => $uid))->get()->num_rows()){
	            $return['state'] = 0;
	            $return['msg'] = '你已经赞过了';
	        }else{
	        	$flag = $this->addLike($data);
		        if($flag){
					//更新统计
					$sql = "SELECT * FROM link_stat WHERE `object_id`='$object_id' AND `object_type`='$object_type'";
					if($this->db->query($sql)->num_rows()){
					//更新
							$this->commentUpdate($data['object_id'],$data['object_type'],'like',1,$data['uid'],$data['username']);
					}else{
					//新加	
							$this->addRecord('like',$data['object_id'],$data['object_type'],$data['uid'],$data['username']);
					}

					//赞添加后需要返回数据
					$return = $this->get_object_stat($data['object_id'],$data['object_type'], $data['uid']);
				    $return['state'] = 1;
		        }else{
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
	    public function get_object_stat($object_id, $object_type, $uid ,$is_greepeople=true)
	    {
	        $stat_info = $this->getStat($object_id,$object_type);
	        
	        $stat_info = $stat_info[0];    //取出来是都是二维数组

	        $return = array();
	        if($is_greepeople)
	        {
	        	$return['greepeople'] = json_decode($stat_info['like_record'],true);
		        $return['greeCount'] =  $stat_info['like_count'];
		        $return['isgree'] = $this->checkMyLike($object_id, $object_type, $uid);
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
    public function del_like($data)
    {
       foreach($data as $list)
       {
            if(empty($list)){
                $return['state'] = 0;
                $return['msg'] = '无效的对象';
                return $this->toJson($return);
            }
        }
        if($this->delLike($data)){
        	$object_id	= $data['object_id'];
        	$object_type= $data['object_type'];
        	$uid		= $data['uid'];
			$sql = "SELECT * FROM link_stat WHERE `object_id`='$object_id' AND `object_type`='$object_type'";
			if($this->db->query($sql)->num_rows()){
        		$this->commentUpdate($object_id,$object_type, 'like', -1,$uid);
			}
        	$return = $this->get_object_stat($object_id, $object_type, $uid ,true);
            $return['state'] = 1;
        }else{
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
	public function del_Object($data='') {
		if (empty($data)) {
			$return['state'] = 0;
            $return['msg'] = '无效的对象';
            return $this->toJson($return);
		}
		
		$object_id = is_array($data['object_id']) ? $data['object_id'] : array($data['object_id']);
		//var_dump($data);die;
		$object_type = $data['object_type'];
		//$this->delComment($object_id, $object_type);
		//return true;
		
		//zengxiangmo add 2012/7/16
		$ret = $this->delComment($object_id, $object_type);
		return $ret;
	}
	
	/**
	 * 删除关于信息流的所有评论\统计信息
	 * @param array $object_id
	 * @param string $object_type
	 * @author by guojianhua
	 */
	public function delComments($object_id, $object_type) {
		if ($object_type == 'topic') {
			foreach ( $object_id as $tid ) {
				$topic = $this->redis->hGetAll ( "Topic:" . $tid );
				if ($topic ['type'] == 'info') {
					$this->delComment ( $topic ['tid'], 'topic' );
					$this->delStat($topic['tid'],'topic',0);
					$this->del_like($topic['tid'],'topic');
				} else {
					$this->delComment ( $topic ['fid'], $topic ['type'] );
					$this->delStat($topic['fid'],$topic['type'],0);
					$this->del_like($topic['fid'],$topic['type']);
				}
			}
		} else {
			foreach ( $object_id as $tid ) {
				$topic = $this->redis->hGetAll ( "Webtopic:" . $tid );
				if ($topic ['type'] == 'info') {
					$this->delComment ( $topic ['tid'], 'web_topic' );
					$this->delStat($topic['tid'],'web_topic',0);
					$this->del_like($topic['tid'],'web_topic');
				} else {
					$this->delComment ( $topic ['fid'], 'web_' . $topic ['type'] );
					$this->delStat($topic['fid'],'web_' . $topic['type']);
					$this->del_like($topic['tid'],'web_' . $topic['type']);
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
		$dates = $this->redis->hKeys("webpage:" . $web_id . ':infos');
		$tids = array();
		foreach ($dates as $date)
		{
			$tids = array_merge($tids, $this->redis->zRange( 'webpage:'.$web_id.':'.$date, 0 , -1 ));
		}
		return $tids;
	}
	
	/**
	 * 删除网页中所有信息流中的赞、评论、统计
	 * @param int $web_id
	 * @return string
	 */
	public function delWebPage ($web_id = 0) {
		if (!$web_id) {
			return json_encode(array("status"=>0,"msg"=>"网页ID错误"));
		}
		$tids = $this->getTidsByWebId($web_id);
		
		if (!count($tids)) {
			return false;
		}
		if ($this->delObject(array("object_id"=>$tids,"object_type"=>"web_topic"))) {
			return true;
		} else {
			return false;
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
 	public function get_all_comment($data)
    {
        $object_id 	 = $data['object_id'];
        $object_type = $data['object_type'];
        
        if(empty($object_id)){
            $return['state'] = 0;
            $return['msg'] = '无效的对象';
           $this->toJson($return);
        }
        $uid = $data['uid'];
        $stat_info = $this->getStat($object_id,$object_type);
        if(empty($stat_info)){
            $return['state'] = 0;
            $return['msg'] = '不存在的对象';
            $this->toJson($return);
        }
        $params = array();
        $params['object_id']   = $object_id;
        $params['object_type'] = $object_type;
        $params['pagesize'] 	   = $data['page'];
        $params['primary_key'] = 'id';
        $params['limit'] 	   = 3;
        $comment_list =  $this->getComment($params);
        //得到评论的赞的统计ids
        $comment_object_ids = array_keys($comment_list['data']);
        //每条评论赞的统计
        $comment_stats = $this->getStat($comment_object_ids,'comment');
        //得到评论中有我赞过的评论编号
        $my_like_comments = $this->checkMyLike($comment_object_ids, 'comment', $uid);
        
        //得到可以删除的评论编号
        $my_del_comments = $this->checkDelComment($comment_object_ids, $object_type, $uid);

        //整体返回数组
        $return = array(
            'state'        =>    1,
            'count'        =>    $stat_info[0]['comment_count'],
            'greeCount'    =>    $stat_info[0]['like_count'],
            'isgree'       =>    $this->checkMyLike($object_id, $object_type, $uid),
            'data'         =>    array(),
        );
        //返回评论相关的详细数组
	    foreach($comment_list['data'] as $item){                               //得到相关评论自身数据
			//判断对应统计        
	        $commnet_num = 0;
	        if(!empty($comment_stats)){
		        foreach($comment_stats as $c_stat){
		            if($item['id'] == $c_stat['object_id']){
		                 $commnet_num = $c_stat['like_count'];
		                 break;
		            }
		        }
	        }
	        
							
			$return['data'][] = array(
				'cid'        =>    $item['id'],
				'name'       =>	   $item['username'],
				'uid'        =>    $item['uid'],
				'content'    =>    $item['content'],
				'time'       =>    $this->tran_time($item['dateline']),
				'isgree'     =>    in_array($item['id'], $my_like_comments) ? true : false,
				'isdel'		 =>    in_array($item['id'], $my_del_comments) ? true : false,
				'greeNum'    =>    $commnet_num,
				'isReply'    =>    $uid == $item['uid'] ? false : true,
			);
        }
        return $this->toJson($return);
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
	public function getLike($params)
	{
        $return = $this->getLikes($params);
        if($return){
        	return $return;
        }else{
        	return  false;
        }
	}
	
	/**
	 *取得统计数
	 *
	 *@param object_id
	 *@param object_type
	 *@return json
	 */
	public function getStats($object_id,$object_type){
		return $this->getStat($object_id,$object_type);
	}
	
	/**
	 *取得统计数
	 */
    public function getStatLists($obj_ids, $object_type, $return_fields = array())
    {
        return $this->getStatList($obj_ids, $object_type, $return_fields);
    }

    
	/**
	 *获取外部调用统计[数组下标返回，空也返回空数组]
	 *
	 *@param object_id  string or array is acceptable
	 *@param object_typed
	 *@return array
	 */
	public function call_stat($object_id,$object_type){
		if(is_array($object_id)){
			$re = $this->getStat($object_id,$object_type,'object_id ,share_count, like_count, comment_count, total_count');
			
			/*foreach ($re as $list){
			
			$arr[$list['object_id']]=array(
					'share_count'   =>  $list['share_count'],
					'like_count'	=>  $list['like_count'],
					'comment_count'	=>  $list['comment_count'],
					'total_count'	=>  $list['total_count'],
				);
			}*/
			$return = array();
			foreach ($object_id as $obid){
				
				foreach ($re as $list){
					if($list['object_id'] == $obid){
						$return[$obid] = array(
							'share_count'   =>  $list['share_count'],
							'like_count'	=>  $list['like_count'],
							'comment_count'	=>  $list['comment_count'],
							'total_count'	=>  $list['total_count'],
						);		
						break;
					}
					
				}
				
				if(!($return[$obid])){
					$return[$obid] = array(
							'share_count'   =>  0,
							'like_count'	=>  0,
							'comment_count'	=>  0,
							'total_count'	=>  0,
						);
				}			
						
			}
		}else{
			$re = $this->getStat($object_id,$object_type,'object_id, share_count, like_count, comment_count, total_count');
			$return[$object_id] = array(
							'share_count'   =>  isset($re[0]['share_count'])   ? $re[0]['share_count']  : 0,
							'like_count'	=>  isset($re[0]['like_count'])    ? $re[0]['like_count']   : 0,
							'comment_count'	=>  isset($re[0]['comment_count']) ? $re[0]['comment_count']: 0,
							'total_count'	=>  isset($re[0]['total_count'])   ? $re[0]['total_count']  : 0,
			);
		}
		return $this->toJson($return);
	}
	
	
	/**
	 *获取外部赞调用统计[数组下标返回，小于3返回名字，大于3返回·我·和数字]
	 *
	 *@param object_id  string or array is acceptable
	 *@param object_typed
	 *@return array
	 */
	public function call_ids_like($object_id,$object_type,$uid){
		$re = $this->getStat($object_id,$object_type,'object_id , like_count, like_record');

		$return = array();	
		$ids    = array();																//返回数组
		if($re){	
			foreach($re as $stat){
				$ids[]= $stat['object_id'];											//做差，查找空
				if($stat['like_count']<=3 && $stat['like_record']){					//有赞，但小于3条
					$likelist = json_decode($stat['like_record'], true);				//拆分,二维数组
					foreach ($likelist as $userlist){
						$obj  = $stat['object_id'];
						$usid = $userlist['uid'];
						if($userlist['uid'] == $uid){
							$return[$obj][$usid]['name'] = '我';
						}else{
							$return[$obj][$usid]['name'] = $userlist['username'];
							$return[$obj][$usid]['uid']  = $userlist['uid'];
						}
						 
					}
					$return[$stat['object_id']]['like_count'] = $stat['like_count'];
				}elseif($stat['like_count']>3 && $stat['like_record']){																//大于3条赞
					$likelist = json_decode($stat['like_record'], true);				//拆分,二维数组
					foreach ($likelist as $userlist){
						$obj  = $stat['object_id'];
						$usid = $userlist['uid'];
						if($userlist['uid'] == $uid){
							$return[$obj][$usid]['name'] = '我';
						}
					}
					$return[$stat['object_id']]['like_count'] = $stat['like_count']-1;
				}																
					
			}
		 }
		 
	 	 $none = array_diff($object_id,$ids);

		 if(is_array($object_id)){									 	//无赞
			foreach ($none as $list){
				$return[$list]='';									//该处暂未返回任何值。
			}
		}else{
			$return[$none]='';
		}
		  return serialize($return);
	}
	
	
	public function call_ids_stat($object_id,$object_type,$uid){
		$re = $this->getStat($object_id,$object_type,'object_id , comment_count, share_count, like_count, like_record');
	
		$return = array();	
		$ids    = array();																//返回数组
		if($re){	
			foreach($re as $stat){
				$objectid=$stat['object_id'];	
				$ids[]= $objectid;										     		//做差，查找空
				
				//评论列表
				if( $stat['comment_count'] ){									//有评论
					$condition['object_id'] = $objectid;	
					$condition['limit'] 	= 3;
					$commentdata = $this->getComment($condition);
	
					$returncomment = array();
					foreach ($commentdata['data'] as $clist){
						$commentone['id']			= $clist['id'];
						$commentone['content']		= $clist['content'];
						$commentone['uid']			= $clist['uid'];
						if($clist['uid'] == $uid){
							$commentone['username']  = '我';
						}else{
							$commentone['username']	= $clist['username'];
						}
						
						$returncomment[]=$commentone;
					}
					$return[$objectid]['comment'] = $returncomment;
					if( $stat['comment_count']>3 ){
						$return[$objectid]['comment_count'] = $stat['comment_count']-3;
					}	
				}else{
					$return[$objectid]['comment_count']=0;
				}		
				//评论结束					
				//赞列表	
				if($stat['like_count']<=3 && $stat['like_record']){					//有赞，但小于3条
					$likelist = json_decode($stat['like_record'], true);				//拆分,二维数组
					
					foreach ($likelist as $userlist){
						$usid = $userlist['uid'];
						if($userlist['uid'] == $uid){
							$return[$objectid]['like']['is_me'] = true;
						}else{
							$likeone['name'] = $userlist['username'];
							$likeone['uid']  = $userlist['uid'];
							$return[$objectid]['like'][] = $likeone;
						}
					}
				}elseif($stat['like_count']>3 && $stat['like_record']){																//大于3条赞
					$likelist=json_decode($stat['like_record'],true);				//拆分,二维数组
					foreach ($likelist as $userlist){
						$usid = $userlist['uid'];
						if($userlist['uid'] == $uid){
							$return[$objectid]['like']['is_me'] = true;
						}
					}
					$return[$objectid]['like_count'] = $return[$objectid]['like']['is_me']? $stat['like_count']-1: $stat['like_count'];
				}		
				//赞列表结束			
				//转发
				if($stat['share_count']){
					$return[$objectid]['share_count'] = $stat['share_count'];
				}else{
					$return[$objectid]['share_count'] = 0;
				}				
			}
		 }else{
		 	$return=false;
		 }
		 $none = array_diff($object_id,$ids);
	
		 if(is_array($object_id)){									 	//无赞
			foreach ($none as $list){
				$return[$list] = null;									//该处暂未返回任何值。
			}
		 }else{
			$return[$none] = null;
		 }
		 return serialize($return);
	}
	
	/**
 	 * 根据object_id,object_type更新ctime字段
	 * @param int $object_id
	 * @param string $object_type
	 * @param string $ctime
	 * @return boolean
	 */
	
	public function updateLike($object_id,$object_type,$ctime) {
		if (empty($object_id) || empty($object_type) || empty($ctime)) {
			return false;
		}
		return  $this->update_Like($object_id,$object_type,$ctime);
	}
	
    /**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 转发模型
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
    
	/**
     * 新增转发
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     * @param int $tid 转发后的编号
     * @param string $params 需要保存的值
     * @return boolean
     */
    public function add($object_type, $first_tid, $tid, $params)
    {
    	
        $key = $this->getKey($object_type, $first_tid);
        
        $flag = $this->redis->hset($key, (string)$tid, json_encode($params));

        if($flag){
            $skey = $this->getSetKey($object_type, $tid);
            return $this->redis->sadd($skey, $first_tid);
        }
        return $flag;
    }
    
	/**
     * 获得hash的key
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     */
    public function getKey($object_type, $first_tid)
    {
        return "Share:{$object_type}:{$first_tid}";
    }
    
	/**
     * 获得set的key
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     */
    protected function getSetKey($object_type, $first_tid)
    {
        return "Share:Stat:{$object_type}:{$first_tid}";
    }
    
/**
     * 删除list列表
     */
    public function delList($key,$value,$num=1){
    	$this->redis->lrem($key,$value,$num);
    }
    /**
     * 删除转发
     * 
     * @param string $object_type 转发类型
     * @param int $tid 信息流编号
     * @return boolean
     */
    public function del($object_type, $tid)
    {
        $skey = $this->getSetKey($object_type, $tid);
        $members = $this->redis->smembers($skey);
        foreach($members as $t){
            $key = $this->getKey($object_type, $t);
            $flag = $this->redis->hdel($key, $tid);
            if(!$flag){
                return $flag;
            }
            $this->redis->srem($skey, $t);
        }
        return true;
    }
    
    /**
     * 得到转发数据
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     * @return array
     */
    public function get($object_type, $first_tid)
    {
        $key = $this->getKey($object_type, $first_tid);
        $return = array();
        $return['count'] = $this->redis->hlen($key);
        $return['data'] = $this->redis->hgetall($key);
        return $return;
    }
    
    /**
     * 分页获得数据
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     * @param integer $page 当前页
     * @param integer $pagesize 每页数量
     */
    public function getPageList($object_type, $first_tid, $page, $pagesize)
    {
        $key = $this->getKey($object_type, $first_tid);
        $list = $this->redis->hKeys($key);
    	$max = $page*$pagesize;
    	$min = ($page-1)*$pagesize;
    	$key_array = array();
    	for($i = $min; $i < $max; $i++) {
    	    if(isset($list[$i])){
    		    $key_array[] = $list[$i];
    	    }
    	}
    	return $this->redis->hmGet($key, $key_array);
    }
    
    /**
     * 得到转发数量
     * 
     * @param string $object_type 转发类型
     * @param int $first_tid 转发的对象编号
     * @return boolean
     */
    public function getLen($object_type, $first_tid)
    {
        $key = $this->getKey($object_type, $first_tid);
        return $this->redis->hlen($key);
    }
    
	/**
     * 添加列表
     * @author yangshunjun
     * 
     * @param string $key
     */
    
   	public function lpush($key, $value){
   		return $this->redis->lpush($key, $value);
   	}
    /**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 转发模型结束
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
    
    /**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 赞模型
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
	/**
     * 检查用户是否赞过对象
     * 如果object_id为字符串，那么返回值为真假，如果为数组，则返回是自己赞过的object_id数组
     *
     * @param mix $object_id
     * @param string $uid
     * @return boolean|array
     */
    public function checkMyLike($object_id, $object_type, $uid)
    {
            $sql = "SELECT object_id FROM likes WHERE uid = '$uid'";
            if (is_array($object_id))
            {
                $sql .= " AND object_id in ('" . implode("','", $object_id) . "') AND object_type = '$object_type'";
            }else{
                $sql .= " AND object_id = '$object_id' AND object_type = '$object_type'";
            }
            
            $res = $this->db->query($sql)->result_array();
            
            if (is_array($object_id))
            {
                $return = array();
                foreach($res as $item){
                    $return[] = $item['object_id'];
                }
                return $return;
            }else{
               return (isset($res[0]['object_id']) && $res[0]['object_id']) ? true : false;
            }
    }
	
    /**
     *插入赞
     */
    public function addLike($data=array())
    {	
    	$data['dateline'] = time();
    	foreach ($data AS $list){
    		if(!isset($list))
            return false;
    	}
    	$this->db->insert('likes', $data);
    	$this->db->insert_id();
        if($this->db->insert_id()){
        	return true;
        }else{
        	return false;
        }
        
    }
	
	/**
	 * 删除赞
	 */
    public function delLike($data){
		if(empty($data['object_id']) || empty($data['object_type']) || empty($data['uid']))
            return false;
        $object_id	= $data['object_id'];   
        $object_type= $data['object_type'];    
        $uid		= $data['uid'];
        if($this->db->delete('likes',array('object_id' => $object_id, 'uid' => $uid, 'object_type' => $object_type))){
        	return true;
        }else{
        	return false;
        }    
	}
	
	/**
	 * 评论被删除时相关的赞
	 **/
	public function delrelateLike($data){
		if(!isset($data['object_id']) && !isset($data['object_type']))
			return false;
        $object_id	= $data['object_id'];   
        $object_type= $data['object_type'];    
        if($this->db->where(array('object_id' => $object_id, 'object_type' => $object_type))->delete('likes')){
        	return true;
        }else{
        	return false;
        }    
	}
	
	/**
	 *查询赞的列表
	 */
	public function getLikes($data=array()){
		$where = array();
        if (isset($data['object_id']))
        {
            if (is_array($data['object_id']))
            {
                $where[] = "`object_id` in ('" . implode("','", $data['object_id']) . "')";
            }
            else
            {
                $where[] = "`object_id` = '" . $data['object_id']."'";
            }
        }
        if (isset($data['uid']))
        {
            if (is_array($data['uid']))
            {
                $where[] = "`uid` in ('" . implode("','", $data['uid']) . "')";
            }
            else
            {
                $where[] = "`uid` = '" . $data['uid']."'";
            }
        }
        if (isset($data['object_type']) && $data['object_type'])
        {
            $where[] = "`object_type` = '". $data['object_type'] . "'";
        }	       
        //返回字段
        if (isset($data['field']) && $data['field']){
            $field = $data['field'];
        }else{
            $field = "id, uid, username, usr_ip, dateline";
        }
        $where_sql = count($where) ? " WHERE " . implode(" AND ", $where) : "";
        
        $list_sql = "SELECT {$field} FROM likes " . $where_sql;
        
        //排序
        if (isset($data['order']) && in_array($data['order'], array('date_asc', 'date_desc', 'id_asc', 'id_desc')))
        {
            switch ($data['order'])
            {
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
        }else{
            $list_sql .= " ORDER BY dateline DESC";
        }
		
        if( $data['page'] >= 1 ){							//假分页,每次返回50条，有page参数决定第几页
        	$limit     = 2;									//每次输出数据条数
        	$offset	   =(intval($data['page'])-1)*$limit;
							
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
	public function delObject($object_id = 0 , $object_type = '') {
		if (!$object_id || empty($object_type)) {
			return  false;
		}
		if ($object_type == 'topic') {
			$map = "`tid` = '" . $object_id . "'" . "AND `object_type` in ('topic','video','blog','photo','album','forward')";
		} else {
			if (is_array($object_id)) {
				$object_id = "( `tid` = '" . implode("' OR `tid` = '", $object_id) . "')";
			} else {
				$object_id = "`tid` = '" . $object_id . "'";
			}
			$map =  $object_id  . "AND `object_type` in ('web_topic','web_video','web_blog','web_photo','web_album')";
		}
		if($this->db->where($map)->delete('likes')){
        	return true;
        }else{
        	return false;
        }
	}
	
	/**
	 * 删除Likes表里数据
	 * @param int $object_id
	 * @param string $object_type
	 * @return boolean
	 */
	public function delete_Like($object_id,$object_type){
		if(empty($object_id) || empty($object_type)) {
			return false;
		}
        if($this->db->where("`object_id` = '$object_id'  AND `object_type`='$object_type'")->delete('likes')){
        	return true;
        }else{
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
	public function update_Like($object_id,$object_type,$ctime) {
		
		if ($object_type == "info") {
			$where = "`tid` = '$object_id'  AND (`object_type`='topic' OR `object_type`='blog' OR `object_type`='forward' OR `object_type`='video')";
		} elseif ($object_type=="web_info") {
			$where = "`tid` = '$object_id'  AND (`object_type`='web_topic' OR `object_type`='web_blog' OR `object_type`='web_video')";
		} else {
			$where = "`tid` = '$object_id'  AND `object_type`='$object_type'";
		}
		
        if($this->db->where($where)->update('likes', array('ctime' => $ctime))){
        	return true;
        }else{
        	return false;
        }
	}
    /**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 赞模型结束
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
	/**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 评论模型
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
//	/**
//     * add module comments
//     * 
//     * @param type $ret reomote param
//     */
//    protected $_objectdb = array(
//        'ask'	=>    array('table' => ANSWER_COMMENTS, 'primary' => 'id'),
//        'event'	=>    array('table' => EVENTTABLE, 'primary' => 'eventid'),
//        'blog'	=>    array('table' => BLOG, 'primary' => 'id'),
//        'video'	=>    array('table' => USER_VIDEO, 'primary' => 'id'),
//        'album'	=>    array('table' => USER_ALBUM, 'primary' => 'id'),
//        'photo'	=>    array('table' => USER_PHOTO, 'primary' => 'id'),
//    );
//    
    /**
     * comments type
     * 
     * @param	$ret 数组
     */
    public function addComment($ret)
    {      
            $this->object_id    =   $ret['object_id'];
            $this->uid          =   $ret['uid'];
            $this->content      =   $ret['content'];
            $this->object_type  =   $ret['object_type'];
            $this->username     =   $ret['username'];
            $this->src_uid      =   $ret['src_uid'];  
            $this->usr_ip       =   $ret['usr_ip'];
            $this->dateline     =   time();
            $data = array(
            	'object_id' => $ret['object_id'],
	            'uid'          =>   $ret['uid'],
	            'content'      =>   $ret['content'],
	            'object_type'  =>   $ret['object_type'],
	            'username'     =>   $ret['username'],
	            'src_uid'      =>   $ret['src_uid'],  
	            'usr_ip'       =>   $ret['usr_ip'],
	            'dateline'     =>   time(),
            );
            $this->db->insert('comments', $data);
            $cid = $this->db->insert_id();
            if($cid){
                return $cid;
            }else{
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
    public function getComment($params)
    {       
            $where = array();
            if (isset($params['id']))
            {
                if (is_array($params['id']))
                {
//                    $where['id'] = array('in'=>implode(',',$params['id']));
                    $this->db->where_in('id', $params['id']);
                }
                else
                {
                    $where['id'] = $params['id'];
                }
            }
            if (isset($params['object_id']))
            {
                if (is_array($params['object_id']))
                {
//                    $where['object_id'] =array('in',implode(',', $params['object_id']));
                    $this->db->where_in('object_id', $params['object_id']);
                }
                else
                {
                    $where['object_id'] = $params['object_id'];
                }
            }
            if (isset($params['uid']))
            {
                if (is_array($params['uid']))
                {
//                    $where['uid'] = array('in',implode(',', $params['uid']));
                    $this->db->where_in('uid', $params['uid']);
                }
                else
                {
                    $where['uid'] = $params['uid'];
                }
            }
            if (isset($params['object_type']) && $params['object_type'])
            {
                $where['object_type'] = $params['object_type'];
            }
            //创建时间
            if (isset($params['start_dateline']) && $params['start_dateline'])
            {
                $where['dateline']  >=  intval($params['start_dateline']);
            }
            if (isset($params['end_dateline']) && $params['end_dateline'])
            {
                $where['dateline']  <=  intval($params['end_dateline']);
            }
            if (isset($params['is_private']) && is_int($params['is_private']))
            {
                $where['is_private']  =  intval($params['is_private']);
            }
            if (isset($params['is_delete']) && is_int($params['is_delete']))
            {
                $where['is_delete']  =  intval($params['is_delete']) ;
            }
            
            if (isset($params['is_stat']) && $params['is_stat'] == 1)
            {
                //计算数量
//                $num_sql = $this->db->from('comments')->where($where)->get()->num_rows();
//                $num_res = $this->where($where)->findall();
                $return['total_num'] = $this->db->from('comments')->where($where)->get()->num_rows();
            }
            //$list_sql = $this->where($where)->field(' id, object_id, object_type, src_uid, uid, username, usr_ip, dateline, content')->findall();
            //排序
            if (isset($params['order']) && in_array($params['order'], array('date_asc', 'date_desc', 'id_asc', 'id_desc')))
            {
                switch ($params['order'])
                {
                    case 'date_asc' :
                        $order= "dateline ASC";
                        break;
                    case 'date_desc' :
                        $order= "dateline DESC";
                        break;
                    case 'id_asc' :
                        $order= "id ASC";
                        break;
                    case 'id_desc' :
                        $order= "id DESC";
                        break;
                }
            }else{
                $order= "dateline DESC";
            }
            //分页
            if (isset($params['limit']) && $params['limit'] > 0)
            {
               $limit=$params['limit'];
            }else{
               $limit = 3;
            }
            
            if(isset($params['pagesize']) && $params['pagesize'] > 0){
            	$pagesize=$params['pagesize']-1;
            }else{
            	$pagesize=0;
            }
            
            $res = $this->db->from('comments')->where($where)->order_by($order)->limit($limit, $pagesize*$limit)->get()->result_array();
            
            $return['data'] = array();
            if(isset($params['primary_key']) && $params['primary_key']){
                foreach ( $res as $item )
                {
                    $return['data'][$item[$params['primary_key']]] = $item;
                }
            }else{
                $return['data'] = $res;
            }
            if (isset($params['return_type']) && $params['return_type'] == 'json')
            {
                return $this->toJson($return);
            }
            else
            {
               return $return;
            }
    }
    
    /**
     * just delete one record once.
     */
    public function delComment($cid,$object_type){
            if($this->db->where(array('id' => $cid, 'object_type' => $object_type))->delete('comments')){
                return true;
            }else{
                return false;
            }
    }
	
	/**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 评论模型结束
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
    /**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 赞统计模型
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
    
	/**
     * 查看赞、评论的统计
     * 
     * @author boolee
     * @param mix $object
     * @return array
     */
    public function getStat($object_id,$object_type,$field='id, object_id, share_count, like_count, comment_count, total_count, like_record,favorite_count')
    {
            $sql = "SELECT $field FROM link_stat" ;
            if (is_array($object_id))
            {
                $sql .= " WHERE object_id in ('" . implode("','", $object_id) . "') AND object_type = '$object_type'";
            }
            else
            {
                $sql .= " WHERE object_id = '$object_id' AND object_type = '$object_type'" ;
            }
            $res = $this->db->query($sql)->result_array();

            if(strpos('like_record',$field)){
	            if(is_array($object_id)){
	                $return = array();
	                if(!empty($res)){
		                foreach ( $res as $item )
		                {
		                    $item['like_record'] = empty($item['like_record']) ? array() : json_decode($item['like_record'], true);
		                    $return[$item['object_id']] = $item;
		                }
	                }
	                
	                return $return;
	            }else{
	                $item = $res;
	                if($res){
	                    $item[0]['like_record'] = empty($item[0]['like_record']) ? array() : json_decode($item[0]['like_record'], true);
	                }
	            }
	                
            }else{
            	if($res){
            		$item = $res;
	            }else{
	            	return false;
	            }
            }
            return $item;
    }
    
    /**
     * 获取统计列表
     */
    public function getStatList($obj_ids, $object_type, $return_fields = array())
    {
    	if(empty($return_fields)){
    		$field = '*';
    	}else{
    		$field = implode(",", $return_fields);
    	}
    	$sql = "SELECT {$field} FROM link_stat" ;
        if (is_array($obj_ids))
        {
        	$sql .= " WHERE object_id in ('" . implode("','", $obj_ids) . "') AND object_type = '$object_type'";
        }
        else
        {
        	$sql .= " WHERE object_id = '$obj_ids' AND object_type = '$object_type'" ;
        }

        return $this->db->query($sql)->result_array();   
    }
    
    
    /**
     * 返回可以删除的评论ID
     * 
     * @param mix $id		评论编号
     * @param string $uid
     */
    public function checkDelComment($id, $object_type, $uid)
    {
    	
            $ids = is_array($id) ? $id : (array) $id;
            $comments = $this->getComment(array('id' => $ids,'object_type'=>$object_type, 'limit' => count($ids)));
            $return = array();
            foreach($comments['data'] as $item){
                if($uid == $item['uid'] || $uid == $item['src_uid']){
                    $return[] = $item['id'];
                }
            }
            return $return;
    }
    
    //添加一条新纪录
    public function addRecord($type='',$object_id='',$object_type='',$uid='',$username=''){
    	/**
    	 * id	object_id 对像ID	share_count 分享数	like_count 赞的数目	comment_count 评论的数目	total_count 总数	like_record 几个人赞的记录
    	 * */
    	$data['object_id']		= $object_id;
    	$data['object_type']	= $object_type;
    	$data['total_count']	= 1;
    	//新加评论//新加赞
    	if(strtolower($type)=='like'){
    		$data['total_count'] = 1;
    		$data['like_count'] = 1;
    		$data['like_record'] = addslashes(json_encode(array(array('id' => $this->get_uuid(),'uid' => $uid,'username' => $username,'dateline' => time()))));
    	}elseif (strtolower($type) == 'comment'){
    		$data['comment_count'] = 1;
    	}
    	$this->db->insert('link_stat', $data);
    	$id = $this->db->insert_id();
    	if($id){
    		return $id;
    	}else{
    		return false;
    	}
    }

    //评论赞表更新
    public function commentUpdate($objectid=null,$object_type=null,$type=null,$num=0,$uid=null,$username=''){
    		$org = $this->db->from('link_stat')->select('comment_count,like_count,total_count,like_record')->where(array('object_id'=>$objectid, 'object_type'=>$object_type))->get()->result_array();
    		
    		$type_count = $type.'_count';
    		
			$records = array();
    		if(empty($org)){
    			$data[$type_count]	  =  intval($num);
            	$data['total_count']  =  intval($num);
    		} else {
				$records = json_decode(stripslashes($org[0]['like_record']),true);
    			$data[$type_count]	  =  intval($org[0][$type_count])    + intval($num);
            	$data['total_count']  =  intval($org[0]['total_count'])  + intval($num);
    		}
		
            //添加 like_record字段记录
            if(strtolower($type)=='like' && intval($num)>0 ){
				$record = array('id'=>$this->get_uuid(),'uid'=>$uid,'username'=>$username,'dateline'=>time());
				
				// 如果赞的记录大于等于3，则弹出最后一个，将最新的赞的人插入到数组前面，只保存最近三个赞过的人的记录
				if (count($records) >= 3) {
					array_pop($records);
				}
				$records = is_array($records) ? array_unshift($records, $record) : array($record);
				$data['like_record'] = addslashes(json_encode($records));
            }
            
			//修改 like_record字段记录
            if(strtolower($type)=='like' && intval($num)<0 && is_array($records)){
				
            	// 删除可能会有的赞记录
            	foreach($records as $key=>$list){
	            	if( isset($list['uid']) && $list['uid'] == $uid){ 
	            		unset($records[$key]);
	            		break;
	            	}
            	}
				
            	$data['like_record'] = $records ? addslashes(json_encode($records)) : '';
            }
            
            $this->db->where(array('object_id'=>$objectid, 'object_type'=>$object_type));
            if($this->db->update('link_stat', $data)){
                return $data[$type_count];
            }else{
                return false;
            }
    }
    
    /**
     * 删除记录, //
     * @param $object_id 删除的相关对象id
     * @param $id        自己id
     */
    public function delStat($object_id,$object_type,$id = 0){
        if($id){
         	if(!$this->db->where("`id`='$id' AND `object_type`='$object_type'")->delete('link_stat')){
	               return false;
	        }
        }else{
	        if($this->db->where("`object_id` = '$object_id' AND `object_type`='$object_type'")->delete('link_stat'))
	        return true;
	        return false;
    	}
    }
    /**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 赞统计模型结束
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
    
    /**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 公共方法结束
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */
	/**
     *@description 输出JSON格式字符串
     *@param       mix $data
     */
    function toJson($data) {
//        header("Content-Type: application/json; charset=utf-8");
        return json_encode($data);
    }
    
    
	/**
	 * 将待显示的时间智能转换
	 * @author wangqiang
	 * @param int $sTime 待显示的时间
	 * @return string $str 智能时间显示
	 */
	function tran_time($sTime)
	{
		$time = time() - $sTime;
		if($time < 0) {
			$str = "错误的时间！";
		}elseif($time < 3) {
			$str = '刚刚';
		}elseif($time < 60) {
			$str = $time."秒前";
		}elseif($time < 60 * 60) {
			$min = floor($time/60);
			$str = $min."分钟前";
		}elseif($time < 60 * 60 * 24) {
			$h = round($time/(60*60));
			$str = $h."小时前";
		}else {
			$time_array = getdate($sTime);
			$hours = $time_array['hours'];
			$minutes = $time_array['minutes'];
			if( $minutes<10 ) $minutes='0'.$minutes;
			$seconds = $time_array['seconds'];
			if( $seconds<10 ) $seconds='0'.$seconds;
			$month = $time_array['mon'];
			$day = $time_array['mday'];
			$year = $time_array['year'];
			$str = $year."年".$month."月".$day."日".$hours.":".$minutes;
		}
	
		return $str;
	}
	
	/**
     * 产生36位uuid
     *
     * @author boolee
     * @date   2012-3-10
     * @access public
     * @return string
     */
     public function get_uuid() 
     {
            $chars = md5(uniqid(mt_rand(), true));
            $uuid = substr($chars, 0, 8) . '-';
            $uuid .= substr($chars, 8, 4) . '-';
            $uuid .= substr($chars, 12, 4) . '-';
            $uuid .= substr($chars, 16, 4) . '-';
            $uuid .= substr($chars, 20, 16);
            return $uuid;
     }
    
    /**++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     * 
     * 公共方法型结束
     * 
     * ++++++++++++++++++++++++++++++++++万恶的分割线+++++++++++++++++++++++++++++++++++
     */

}
?>
