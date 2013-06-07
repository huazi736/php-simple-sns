<?php
/**
 * 评论与赞控制器
 * 
 * @author yangshunjun 2012/07/31
 */
class Comment extends MY_Controller
{
	protected $uinfo = array();
	
	//网页id
	protected $web_id = 1;
	
	protected $web_info = array();
	
	/**
	 * 初始化需要初始化用户id用户名，头像地址;
	 * 放置与uinfo数组里
	 * uinfo['username'],uinfo['username']和uinfo['avatar']
	 * */
	public function __construct()
    {
        parent::__construct();

        $this->uinfo=array(
        	'uid' 	   => $this->uid,
        	'username' => $this->username,
 	        'avatar'   => get_avatar($this->uid),            //头像路径
        ); 
        
        //网页id
        $this->web_id = (intval($this->input->get_post('web_id')) > 1) ? intval($this->input->get_post('web_id')) : 1;
    }
    
    /**
     * 通过uids获取一个以uid为索引的dkcode数组。
     */
    private function get_dkcodes($uids)
	{
	    $userinfo = service('User')->getUserList($uids,array('uid','dkcode'));
	    $return = array();
	    if($userinfo)
	    {
	    	foreach ($userinfo as $list){
	    		foreach($uids as $uid){
		    		if($uid == $list['uid'])
		    		$return[$uid]= $list['dkcode'];
	    		}
	    	}
	        return $return;
	    }
	    return 0;
	}
	
	/**
	 * 获取单个用户dkcode
	 */
	private function get_dkcode($uid)
	{
	    $user = service('User')->getUserinfo($uid, 'uid', array('dkcode'), true);
	    if($user)
	    {
	        return $user['dkcode'];
	    }
	    return 0;
	}
	
	/**
	 * 获取用户信息
	 */
	private function get_userinfo($uid)
	{
	    $user = service('User')->getUserinfo($uid, 'uid', array('username'), true);
	    if($user)
	    {
	        return $user['username'];
	    }
	    return 0;
	}
	
	
	/**
	 * 初始化加载全部评论，赞，转发相关数据及其统计
	 */
	public function get_stat_all() {
		
    	$object_id   = shtmlspecialchars($this->input->get_post('comment_ID'));
    	$object_type = shtmlspecialchars($this->input->get_post('pageType'));
	    $action_uid  = shtmlspecialchars($this->input->get_post('action_uid'));
	    $tid		 = shtmlspecialchars($this->input->get_post('tid'));
	    
        if(!$object_id || !$object_type || !$action_uid){
            $this->ajaxReturn('', '', 1);
        }
        
        // 对象ID
        $object_ids   = explode(',', $object_id);
        
        // 对象类型
        $object_types = explode(',', $object_type);
        
        // 对象的发布者ID
        $action_uids  = explode(',', $action_uid);
        
        // 时间线对应的ID，用于查询分享的次数
        $tids         = explode(',', $tid);
 
		$re = service('Comlike')->getRecommendData($object_ids, $object_types, $action_uids, $tids, $this->uid, $this->web_id);
		if (!empty($re)) {
			
			/**
			//多次soap优化的中间变量：$rek，头像索引，$rekey,dkcode索引，$dkuids，不重复的用户uid数组
			$rek    = array();
			$rekey  = array();
			$dkuids = array();

			// 评论者个人头像+端口号处理
			foreach ($re as $k => $list1) {
				
				//查找头像端口号
				if (isset($list1['data'])) {
					foreach($list1['data'] as $key => $people){
						if($people['uid'] == $this->uid){
							$re[$k]['data'][$key]['url']    = WEB_ROOT . 'main';
						}else{
							$rek[$people['uid']][]   = $k . ',' . $key;
							if(empty($dkuids[$people['uid']])) {
								$dkuids[$people['uid']]  = $people['uid'];
							}
						}
						$re[$k]['data'][$key]['imgUrl'] = get_avatar($people['uid']);
					}
				}
				
				//赞用户端口号查找
				if(isset($list1['greepeople'])){
					foreach($list1['greepeople'] as $key => $people){
						if($people['uid'] == $this->uid){
							$re[$k]['greepeople'][$key]['url']    = WEB_ROOT.'main';
						}else{
							$rekey[$people['uid']][]   = $k.','.$key;
							if(empty($dkuids[$people['uid']])) {
								$dkuids[$people['uid']]    = $people['uid'];
							}
						}
					}
				}
			}
			 
			//完成替换
			if(!empty($dkuids)){
				$dkcodedata = $this->get_dkcodes(array_values($dkuids));
			}
			 
			if(!empty($dkcodedata) && is_array($dkcodedata)){
				foreach($dkcodedata as $keys => $list){
					foreach($rek as $reakk=>$k1){
						if($keys == $reakk){
							foreach ($k1 as $kk){
								$kk=explode(',', $kk);
								$re[$kk[0]]['data'][$kk[1]]['url']  = mk_url('main/index/profile',array('dkcode'=>$list));
							}
							break;
						}
					}
					 
					foreach($rekey as $reakk=>$k1){
						if($keys == $reakk){
							foreach ($k1 as $kk){
								$kk=explode(',', $kk);
								$re[$kk[0]]['greepeople'][$kk[1]]['url']  = mk_url('main/index/profile',array('dkcode'=>$list));
							}
						}
					}
				}
			}**/
			$this->ajaxReturn(array_values($re),'',1);
		}
		$this->ajaxReturn('', '', 1);
    }
   
	//添加评论
    public function add_comment()
    {  	
    	$object_id	= $this->input->get_post('comment_ID');
    	$content	= $this->input->get_post('comment_content');
    	$pageType	= $this->input->get_post('pageType');
    	$src_uid	= $this->input->get_post('action_uid');
    	$msgname	= $this->input->get_post('msgname');         //信息名字，信息原链接
    	$msgurl		= $this->input->get_post('msgurl');
    	$tid		= $this->input->get_post('tid');			//首页热度新加
    	$msgid		= $tid;
    	$uid		= $this->uid;
    	$action_uid	= (int)$this->input->get_post('uid');
    	$usr_ip		= get_client_ip();
    	$username	= $this->uinfo['username'];
    	$data		=array();								//插入数据
    	$this->web_info = service('Interest')->get_web_info($this->web_id);
    	
    	if( $object_id && ($content != '') && in_array($pageType, service('Comlike')->checkAllowType($pageType)) ){
    		if(!$this->check_author($src_uid, $this->uid, $tid) && $tid){
    			$this->ajaxReturn('','该用户已经设置了权限或者已删除.',0);
    		}
    		
    		if(!empty($action_uid) && $uid != $action_uid){
    			$actioin_username = $this->get_userinfo($action_uid);
    			$dkcode = $this->get_dkcode($action_uid);
    			$reply_str = '回复'. $actioin_username . ':&nbsp;';
//    			$reply     = preg_replace('/\s+/',' ',str_replace(array('<','>','\\', "'", "　"), array('&#60;','&#62;','&#92;', '&#039;', " "), $reply_str));
    			$msg_reply_str = $content ? mb_substr($content, 0, 6) : '';
		    	if(strlen($content) > 6){
		    		$msg_reply_str .= '...';
		    	}
    			$content =  $reply_str  . $content;
    		}
    		
    		$data['object_id']	= $object_id;
    		$data['object_type']= $pageType;
    		$data['content']	= $content;
    		$data['uid']		= $uid;
    		$data['src_uid']	= $src_uid;
    		$data['usr_ip']		= $usr_ip;
    		$data['username']	= $username;
    		$data['web_id']     = $this->web_id;
    		
        	$response = service('Comlike')->add_comment($data);
        	
        	if(!empty($response)){
        		$response['uid'] = $action_uid;
        	}
        	//信息流热度添加
    	 	if(strpos($pageType, 'web') === false){
	        	$hot = $tid ? service('Timeline')->updateTopicHot($tid,1) : '';
	       	} else {
	       		$hot = $tid ? service('WebTimeline')->updateWebtopicHot($tid,1) : '';
	       	}
	       
        	//对相册评论字段操作
        	if(!empty($response) && in_array($pageType, array('photo', 'web_photo'))){
				service('Album')->commentAdd($object_id);
        	}
			
			//积分
    		if(!empty($src_uid) && $src_uid != $this->uid){
        		service('credit')->comment();
			}
			
    		/**
			 * 1、自己评论或者回复自己的信息不发通知
			 * 2、回复他人需要给他人发通知
			 * 3、他人评论需要发通知
			 * 4、他人回复作者只需要发通知
			 */
			if(!empty($action_uid) && $action_uid != $this->uid){
				$this->_sendReplyNotice($object_id, $pageType, $action_uid, $this->web_id, $msg_reply_str, $src_uid);
			} else if ($this->uid != $src_uid) {
				$this->_sendNotice($object_id, $pageType, $src_uid, $this->web_id, 2);
			}
			
			//-------------------------------------个人关注接口start boolee 7/24--------------------------------------------------------------------
    		service( 'Relation')->updateFollowTime($this->uid,$src_uid);
    		//-------------------------------------个人关注接口end----------------------------------------------------------------------------------
    				
        	$this->ajaxReturn($response,'',1);
    	}else{
    		$this->ajaxReturn('','数据传输失败',0);
    	}
    }
	//删除评论
    public function del_comment()
    {
    	$id = intval($_GET['comment_ID']);
    	if(!$id){
    		$this->ajaxReturn('','获取数据异常',0);
    	}  
    		
    	//核心返回false或者操作成功后的相片评论数关于的array值
    	$comment = service('Comlike')->del_comment($id, $this->uid);
    	
    	if(array_key_exists('state', $comment) && !$comment['state']){
    		$this->ajaxReturn('', $comment['msg'], 0);
    	}
    	
    	//----------------------相册字段处理------------------------------------------------------------------
    	if($comment && $comment['object_type']=='photo'){
    		if( empty($comment['comment_count'])){
    			
    			// 最后一条进行删除处理，将照片的已评论状态改成未评论
				service('Album')->commentDelete($comment['object_id']);
    		}
    	}
    	  		
    	//----------------------搜索引擎接口------------------------------------------------------------------
    	/*switch ($_GET['pageType']){
			case 'album':
				call_soap('search','Restoration','restoreAlbumInfo',array(array('id'=>$object_id,
																		  'type'=>0,'visible'=>0
																		  )));
				break;
			case 'photo':
				call_soap('search','Restoration','restorePhotoInfo',array(array(array('id'=>$object_id,
																		  'type'=>0
																		  ))));
				break;			
			case 'topic':
				call_soap('search','Restoration','restoreStatusInfo',array(array('id'=>$object_id,
																	   'type'=>0
																		  )));
				break;
			case 'forward':
				call_soap('search','Restoration','restoreStatusInfo',array(array('id'=>$object_id,
																	   'type'=>0
																		  )));
				break;	
			case 'blog':
				call_soap('search','Restoration','restoreBlogInfo',array('id'=>$object_id));
				break;
			case 'video':
				call_soap('search','Restoration','restoreVideoInfo',array(array('id'=>$object_id,
																		  'type'=>0
																		  )));
				break;				
		}*/
    	
    	return $this->ajaxReturn($comment['comment_count'], '', 1);
    }
    
	//添加赞
    public function add_like()
    {
    	$object_id= $this->input->get_post('comment_ID');
    	$src_uid  = $this->input->get_post('action_uid');
    	$tid	  = $this->input->get_post('tid') ? : 0;
    	$ctime	  = $this->input->get_post('ctime') ? : 0;
    	$pageType = $this->input->get_post('pageType');
    	$this->web_info = service('Interest')->get_web_info($this->web_id);
    	
    	if($object_id && $pageType && $src_uid && in_array($pageType, service('Comlike')->checkAllowType($pageType))){
    		//信息流操作时候权限判断
    		if(!$this->check_author($src_uid, $this->uid, $tid) && $tid){
    			$this->ajaxReturn('','该用户设置了权限或者已删除',0);
    		}
    		//赞模块专用数据接口
    		if(!$tid && in_array($pageType, array('album','photo','video','blog'))){
    			$spage= $pageType;
    			$sobj = $object_id;
    			if($pageType == 'photo'){
    				$sobj = $ctime;
    				$spage= 'album';
    			}
		    	$info	= service('Timeline')->getTopicByMap( $sobj, $spage, $src_uid );
		    	
		    	$tid 	= $info['tid'] ? : 0;
		    	$ctime 	= $info['ctime'] ? : $ctime;
    		}

    		$data = array(	'object_id'		=> $object_id, 
    						'object_type'	=> $pageType, 
    						'usr_ip'		=> get_client_ip(), 
    						'username'		=> $this->uinfo['username'], 
    						'uid'			=> $this->uid, 
    						'src_uid'		=> $src_uid,
    						'tid'			=> $tid,
    						'ctime'			=> $ctime,
    						'web_id'        => ($this->web_id > 1) ? $this->web_id : 0, 	
    					);
	    	$re	= service('Comlike')->add_like( $data );
			if (!$re['state']) {
				$this->ajaxReturn('', $re['msg'],0);
			}
	    	
	    	//-------------------------------------赞用户处理BEGIN------------------------------------------------------------------------------------
    		if(isset($re['greepeople']) && is_array($re['greepeople'])){
	    		$rek    = array();
               	$dkuids = array();
	    		foreach ($re['greepeople'] as $key => $list){	
		    		$rek[$list['uid']][]   = $key;
		            if(empty($dkuids[$list['uid']])) $dkuids[$list['uid']]  = $list['uid']; 
		    	}
		    	$dkcodedata = $this->get_dkcodes(array_values($dkuids)); 
    			if(is_array($dkcodedata)){
	               	foreach($dkcodedata as $keys=>$list){
	               			foreach($rek as $reakk=>$k1){
	               				if($keys == $reakk){
	               					foreach ($k1 as $kk){ 
	               						$re['greepeople'][$kk]['url']  = mk_url('main/index/index',array('dkcode'=>$list));
	               					}
	               					break;
	               			}
	               		}  			
	             	}
    			}	
	    	}
	    	
	    	// 发送通知
			if ($this->uid != $src_uid) {
				$this->_sendNotice($object_id, $pageType, $src_uid, $this->web_id);
				//积分
				service('credit')->like();
			}
			//-------------------------------------个人关注接口start boolee 7/24--------------------------------------------------------------------
    		service('Relation')->updateFollowTime($this->uid,$src_uid);
    		//-------------------------------------个人关注接口end----------------------------------------------------------------------------------	
			$this->ajaxReturn($re,'',1);
	    }else{
	    	$this->ajaxReturn('','数据传输失败',0);
	    }
    }
    
    /**
     * 
     * 发送通知
     * @param integer $object_id
     * @param string  $type
     * @param integer $src_uid
     * @param integer $act_type 赞来源 0：个人首页; 1:网页模块
     * @param integer $notice_type 通知类型：1：赞; 2:评论
     */
    private function _sendNotice($object_id, $type, $src_uid, $web_id = 1, $notice_type = 1, $params = '') {
    	
    	//信息名字，信息原链接
    	$msgname	= $this->input->get_post('msgname');
    	$msgurl		= $this->input->get_post('msgurl');
    	$web_info   = $this->web_info;
    	$web_url    = mk_url('webmain/index/main/', array('web_id' => $this->web_id));
    	
    	//默认通知参数 
    	$params_notice = array(
    		'name'  => $msgname,
    		'name1' => '',
    		'url'   => $msgurl,
    		'url1'  => '',
    	);
    	
   		switch ($type){
			case 'ask':
				$subtype='ask';
				$treetype='ask_comment';
				break;
			case 'album':
				$subtype='photo';
				
				$treetype = ($notice_type == 1) ? 'photo_albumzan' : 'photo_albumcommenttoyou';
				
				service('RestorationSearch')->restoreAlbumInfo(array('id'=>$object_id,'type'=>0,'visible'=>0));
				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('dkcode'=>($this->get_dkcode($src_uid)),'albumid'=>$object_id));
				}
				break;
			case 'photo':
				$subtype='photo';
				$treetype = ($notice_type == 1) ? 'photo_zan' : 'photo_commenttoyou';
				
				service('RestorationSearch')->restorePhotoInfo(array('id'=>$object_id,'type'=>0));
   				if(!$msgurl) {
   					$photo_info = service('Album')->getPhotoInfo($object_id, 'album', $src_uid);
   					
   					$tempurl = mk_url($this->mk_msgurl($type), array('photoid' => $object_id, 'dkcode' => $this->action_dkcode));
        			$params_notice['url'] = mk_url($this->mk_msgurl('album'), array('albumid' => $photo_info['aid'], 'dkcode' => $this->action_dkcode, 'iscomment' => '1', 'jumpurl' => urlencode($tempurl)));
				}
				break;			
			case 'topic':
				$subtype='info';
				$treetype = ($notice_type == 1) ? 'info_zaninfo' : 'info_infocomment';
				service('RestorationSearch')->restoreStatusInfo(array('id'=>$object_id,'type'=>0));
				
   				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('tid'=>$object_id));
				}
				break;
			case 'forward':
				$subtype='info';
				$treetype = ($notice_type == 1) ? 'info_zaninfo' : 'info_infocomment';
				service('RestorationSearch')->restoreStatusInfo(array('id'=>$object_id,'type'=>0));
				
   				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('tid'=>$object_id));
				}
				break;	
			case 'blog':
				$subtype='blog';
				$treetype = ($notice_type == 1) ? 'blog_zan' : 'blog_commenttoyou';
				service('RestorationSearch')->restoreBlogInfo($object_id);
				
   				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('dkcode'=>($this->get_dkcode($src_uid)),'id'=>$object_id));
				}
				break;
			case 'video':
				$subtype='video';
				$treetype='video_zan';
				$treetype = ($notice_type == 1) ? 'video_zan' : 'video_commenttoyou';
				service('RestorationSearch')->restoreVideoInfo(array('id'=>$object_id,'type'=>0));
				
   				if(!$msgurl) {
					$params_notice['url']    = $msgurl = mk_url($this->mk_msgurl($type),array('vid'=>$object_id));
				}
				break;
			case 'web_album':
				$subtype = 'web';
				$treetype = ($notice_type == 1) ? 'photo_albumzan_web' : 'photo_albumcomment_web';
				$params_notice['name1']  = $msgname;
				$params_notice['name']   = $web_info['name'];
				$params_notice['url']    =  $web_url;
				service('RestorationSearch')->restoreAlbumInfo(array('id'=>$object_id,'type'=>1,'visible'=>0));
				
				$params_notice['url1'] = mk_url($this->mk_msgurl($type),array('albumid'=>$object_id,'web_id'=>$this->web_id));
				
				break;
			case 'web_photo':
				$subtype                 = 'web';
				$treetype = ($notice_type == 1) ? 'photo_zan_web' : 'photo_comment_web';
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;
				service('RestorationSearch')->restorePhotoInfo(array('id'=>$object_id,'type'=>1));
				
				$params_notice['url1'] = $msgurl ? : mk_url($this->mk_msgurl($type),array('id'=>$object_id,'web_id'=>$this->web_id));
				
				break;			
			case 'web_topic':
				$subtype = 'web';
				$treetype = ($notice_type == 1) ? 'info_zaninfo_web' : 'info_infocomment_web';
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;
				service('RestorationSearch')->restoreStatusInfo(array('id'=>$object_id,'type'=>1));
				if(!$msgurl) {
					$params_notice['url1'] = mk_url($this->mk_msgurl($type),array('tid'=>$object_id,'web_id'=>$this->web_id,'from'=>'web'));
				}
				break;
			case 'web_video':
				$subtype = 'web';
				$treetype = ($notice_type == 1) ? 'video_zan_web' : 'video_comment_web';
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;
				service('RestorationSearch')->restoreVideoInfo(array('id'=>$object_id,'type'=>1));
   				if(!$msgurl) {
					$params_notice['url1'] = mk_url($this->mk_msgurl($type),array('web_id'=>($this->web_id),'vid'=>$object_id));
				}
				break;
			case 'web_forward':
				$subtype = 'web';
				$treetype = ($notice_type == 1) ? 'info_zaninfo' : 'info_infocomment';
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;
				
   				if(!$msgurl) {
					$params_notice['url1'] = mk_url($this->mk_msgurl($type),array('web_id'=>($this->web_id),'vid'=>$object_id));
				}
				break;
			case 'goods':
				$subtype = 'web';
				$treetype = ($notice_type == 1) ? 'dk_commodityzan_web' : 'dk_commoditycomment_web';
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;
				
				$params_notice['url1'] = $msgurl ? : mk_url('channel/goods/alist', array('web_id'=>$this->web_id));
				break;
			case 'web_dish':
				$subtype = 'web';
				$treetype = ($notice_type == 1) ? 'dk_zandishes_web' : 'dk_commentdishes_web';
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;
				
   				if(!$msgurl) {
					$params_notice['url1'] = $web_url;
				}
				break;
			case 'web_groupon':
				$subtype = 'web';
				$treetype = ($notice_type == 1) ? 'dk_zanpromotions_web' : 'dk_commentpromotions_web';
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;
				
   				if(!$msgurl) {
					$params_notice['url1'] = $web_url;
				}
				break;
			default :
				$subtype='';
				$treetype='';
		}
		
		//发通知
    	if ($subtype && $treetype && $src_uid && $web_id) {
			service('Notice')->add_notice($web_id, $this->uid, $src_uid, $subtype, $treetype, $params_notice);
		}
    }
    
    public function _sendReplyNotice($object_id, $type, $action_uid, $web_id = 1, $msg_reply_str = '', $src_uid = 0) {
    	
    	//信息名字，信息原链接
    	$msgname	= $this->input->get_post('msgname');
    	$msgurl		= $this->input->get_post('msgurl');
    	$web_info   = $this->web_info;
    	$web_url    = mk_url('webmain/index/main/', array('web_id' => $this->web_id));
    	
    	$params_notice['name'] = $msg_reply_str;
    	$params_notice['url'] = $msgurl;
    	$status     = true;
    	$subtype = 'dk';
		$treetype = 'dk_reply_comment';
		
    	switch ($type){
    		case 'ask':
				break;
			case 'album':
				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('dkcode'=>($this->get_dkcode($src_uid)),'albumid'=>$object_id));
				}
				break;
			case 'photo':
   				if(!$msgurl) {
   					$photo_info = service('Album')->getPhotoInfo($object_id, 'album', $action_uid);
   					$tempurl = mk_url($this->mk_msgurl($type), array('photoid' => $object_id, 'dkcode' => $photo_info['dkcode']));
        			$params_notice['url'] = mk_url($this->mk_msgurl('album'), array('albumid' => $photo_info['aid'], 'dkcode' => $photo_info['dkcode'], 'iscomment' => '1', 'jumpurl' => urlencode($tempurl)));
				}
				break;			
			case 'topic':
   				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('tid'=>$object_id));
				}
				break;
			case 'forward':
   				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('tid'=>$object_id));
				}
				break;	
			case 'blog':
   				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('dkcode'=>($this->get_dkcode($src_uid)),'id'=>$object_id));
				}
				
				break;
			case 'video':
   				if(!$msgurl) {
					$params_notice['url']    = mk_url($this->mk_msgurl($type),array('vid'=>$object_id));
				}
				
				break;
			case 'web_album':
				$params_notice['name1']  = $msgname;
				$params_notice['name']   = $web_info['name'];
				$params_notice['url']    =  $web_url;
				
				if(!$msgurl) {
					$params_notice['url1'] = mk_url($this->mk_msgurl($type),array('albumid'=>$object_id,'web_id'=>$this->web_id));
				}
				break;
			case 'web_photo':
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;
				
   				if(!$msgurl) {
					$params_notice['url1'] = mk_url($this->mk_msgurl($type),array('id'=>$object_id,'web_id'=>$this->web_id));
				}
				break;			
			case 'web_topic':
				$params_notice['name1']   = $msgname;
				/*$params_notice['name']   = $msgname$web_info['name']*/; 
				$params_notice['url']    =  mk_url($this->mk_msgurl($type),array('tid'=>$object_id,'web_id'=>$this->web_id,'from'=>'web'))/*$web_url*/;
				
				if(!$msgurl) {
					$params_notice['url1'] = mk_url($this->mk_msgurl($type),array('tid'=>$object_id,'web_id'=>$this->web_id,'from'=>'web'));
				}

				break;
			case 'web_video':
				$params_notice['name1']   = $msgname;
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;

   				if(!$msgurl) {
					$params_notice['url1'] = mk_url($this->mk_msgurl($type),array('web_id'=>($this->web_id),'vid'=>$object_id));
				}
				break;
			case 'goods':
				$params_notice['name']   = $web_info['name']; 
				$params_notice['url']    =  $web_url;

				break;
			case 'web_dish':
				if(!$msgurl) {
					$params_notice['url'] = mk_url($this->mk_msgurl($type),array('web_id'=>($this->web_id)));
				}
				break;
			case 'web_groupon':
   				if(!$msgurl) {
					$params_notice['url'] = mk_url($this->mk_msgurl($type),array('web_id'=>($this->web_id)));
				}
				break;
			default :
				$status = false;
				break;
    	}
		
		
    	//发通知
    	if($status){
    		service('Notice')->add_notice($web_id, $this->uid, $action_uid, $subtype, $treetype, $params_notice);	
    	}
		
    }
    
	//删除赞
    public function del_like()
    {
    	$object_id= $_GET['comment_ID'];
    	if( $_GET['comment_ID'] && $_GET['pageType'] ){
    		$uid  = $this->uid;
    		$data = array('object_id'=>$_GET['comment_ID'], 'object_type'=>$_GET['pageType'], 'uid'=>$uid);

    		$re = service('Comlike')->del_like($data);
    		//-------------------------------------赞用户处理BEGIN------------------------------------------------------------------------------------
	    	if(isset($re['greepeople']) && is_array($re['greepeople'])){
	    		$rek    =array();
               	$dkuids =array();
	    		foreach ($re['greepeople'] as $key=>$list){	
		    		$rek[$list['uid']][]   = $key;
		            if(empty($dkuids[$list['uid']])) $dkuids[$list['uid']]  = $list['uid']; 
		    	}
		    	$dkcodedata= $this->get_dkcodes(array_values($dkuids)); 
    			if(is_array($dkcodedata)){
	               	foreach($dkcodedata as $keys=>$list){
	               			foreach($rek as $reakk=>$k1){
	               				if($keys == $reakk){
	               					foreach ($k1 as $kk){ $re['greepeople'][$kk]['url']  = mk_url('main/index/mian',array('dkcode'=>$list));
	               					}
	               					break;
	               			}
	               		}  			
	             	}
    			}	
	    	}
    		//-------------------------------------赞用户处理END------------------------------------------------------------------------------------
    		switch ($_GET['pageType']){
				case 'album':
//					call_soap('search','Restoration','restoreAlbumInfo',array(array('id'=>$object_id,
//																			  'type'=>0,'visible'=>0
//																			  )));
					service('RestorationSearch')->restoreAlbumInfo(array('id'=>$object_id,'type'=>0,'visible'=>0));
					break;
				case 'photo':
//					call_soap('search','Restoration','restorePhotoInfo',array(array(array('id'=>$object_id,
//																			  'type'=>0
//																			  ))));
					service('RestorationSearch')->restorePhotoInfo(array('id'=>$object_id,'type'=>0));
					break;			
				case 'topic':
//					call_soap('search','Restoration','restoreStatusInfo',array(array('id'=>$object_id,
//																		   'type'=>0
//																			  )));
					service('RestorationSearch')->restoreStatusInfo(array('id'=>$object_id,'type'=>0));
					break;
				case 'forward':
//					call_soap('search','Restoration','restoreStatusInfo',array(array('id'=>$object_id,
//																		   'type'=>0
//																			  )));
					service('RestorationSearch')->restoreStatusInfo(array('id'=>$object_id,'type'=>0));
					break;	
				case 'blog':
//					call_soap('search','Restoration','restoreBlogInfo',array('id'=>$object_id));
					service('RestorationSearch')->restoreBlogInfo($object_id);
					break;
				case 'video':
//					call_soap('search','Restoration','restoreVideoInfo',array(array('id'=>$object_id,
//																			  'type'=>0
//																			  )));
					service('RestorationSearch')->restoreVideoInfo(array('id'=>$object_id,'type'=>0));
					break;				
			}
	    	$this->ajaxReturn($re,'',1);
    	}else{
    		$this->ajaxReturn('','获取数据异常',0);
    	}
    }
	//获取所有赞列表
    public function get_all_comment()
    {
    	$object_id   = $this->input->get('comment_ID');
    	$object_type = $this->input->get('pageType');
    	$page  		 = $this->input->get('pageIndex');
    	$uid		 = $this->uid;
    	$data		 =array('object_id'=>$object_id,
    						'object_type'=>$object_type,
    						'uid'=>$uid,
    						'page'=>$page
    					);
    	$re = service('Comlike')->get_all_comment($data);
    	
    	if($re){   		
    			if(is_array($re['data']) && !empty($re['data'])){
    				$rek    = array();
               		$dkuids = array();
		        	//查找头像端口号
		            foreach($re['data'] as $key => $people){
		               	if($people['uid'] == $this->uid){
		               	    $re['data'][$key]['url']    = WEB_ROOT.'main';
		               	}else{
		               		$rek[$people['uid']][]   = $key;
		               		if(empty($dkuids[$people['uid']])) {
		               			$dkuids[$people['uid']]  = $people['uid'];
		               		}; 
		               	}		               			
		                $re['data'][$key]['imgUrl'] = get_avatar($people['uid']);
		           }
	           }
				if(!empty($dkuids)){
					$dkcodedata = $this->get_dkcodes(array_values($dkuids)); 
		      		
	    			if(is_array($dkcodedata)){
		               		foreach($dkcodedata as $keys=>$list){
		               			foreach($rek as $reakk=>$k1){//uid
		               				if($keys == $reakk){
		               					foreach ($k1 as $kk){ 
		               						$re['data'][$kk]['url'] = mk_url('main/index/profile/', array('dkcode'=>$list)); 
		               					}
		               					break;
		               				}
		               			}  			
		               		}
	    			}	
				}
              	$this->ajaxReturn($re,'',1);
         }else{
         	$this->ajaxReturn('','获取数据异常',0);
         }
    }

	/**
	 * 取得目标赞的信息
	 *
	 * @param $object_id 对象ID
	 * @param $object_type对象类型
	 * @param $page      当前页
	 */
	public function like_list()
	{
		$object_id    = shtmlspecialchars($this->input->get('comment_ID'));
    	$object_type  = shtmlspecialchars($this->input->get('pageType'));
    	$page    	  = (empty( $_GET['page'] ))? 0 : intval($_GET['page']);	    							  //判断当前请求是数据[page>0]还是初始化[page=0]
		$uid  		  = $this->uid;
		if(empty($uid) &&empty($object_id) &&empty($object_type)){
			$this->ajaxReturn('','登陆异常',0);
		}
		
		/**page区分请求类型**/
		if($page){
			$lists = service('Comlike')->getLike(array(								  //获取当前请求数据
			    'object_id'	    =>    $object_id,
				'object_type'	=>    $object_type,
			    'page'			=>    $page,
				'order'			=>	  'date_desc',
			));      
			             			
			if($lists){													//返回数组
				/**
				 * json输出内容：用户头像路径,用户名称,用户主页链接,用户间关系,[赞时间]。
				 * */
				$return	= array();
				$keys	= array();
				$uids	= array();
				foreach($lists as $key => $item){
					//关系列表  2无关系， 10好友, 6 相互关注, 4 粉丝, 8等待对方接受好友邀请
					if($this->uid == $item['uid']){
						$return[$key]['relationship']	=	7;
					}else{
						$keys[$key]	  = $item['uid'];
						if(!in_array($item['uid'], $uids))
						$uids[]	 	  = $item['uid'];
						//$relationship=call_soap( 'social','Social','getRelationWithUser',array($this->uid,$item['uid']) );//这里进行用户关系确定
					}
					$dateline= $item['dateline'];						//赞的时间
					
					$return[$key]['uid']			=	$item['uid'];
					$return[$key]['avatar_s']		=	get_avatar($item['uid']);
					$return[$key]['username']		=	$item['username'];
					$return[$key]['url']			=	mk_url('main/index/profile', array('dkcode' => $this->get_dkcode($item['uid'])));
					//$return[$key]['relationship']	=	$relationship;
				    $return[$key]['dateline']		=	$dateline;
				}
				if($uids){
//					$relationships=call_soap( 'social','Social','getMultiRelationStatus',array($this->uid,$uids) );
					$relationships = service('Relation')->getMultiRelationStatus($this->uid,$uids);
					foreach($relationships as $rek=>$relist){
						$kk=array_keys($keys,substr($rek,1));
						foreach ($kk as $k){ $return[$k]['relationship']	=	$relist;}
					}
				}
				$this->ajaxReturn($return, '', 1);
			}else{
				$this->ajaxReturn('', '加载异常', 0);
			}						      			 
		}else{
//			$statarr  = call_soap( 'comlike','Index','getStat',array($object_id,$object_type) );		  //取统计表数,用于首次计算页面数量
			$statarr = service('Comlike')->getStat($object_id,$object_type);
			
			$p = round(($statarr[0]['like_count']) / 2);
			$pagecount = $statarr[0]['like_count']?($p ? $p : 1):0;//分页计算
			$this->assign('pagecount',$pagecount);//页面总数，只第一次加载进去
			$this->assign('object_id',$object_id);
			$this->assign('object_type',$object_type);
			$this->display('comment/like_lists');
		}

	}
	
	/**
	 * 取得转发者列表
	 *
	 * @param $object_id   对象ID
	 * @param $object_type 对象类型
	 * @param $page        当前页码
	 */
	public function share_list()
	{
		$object_id    = intval($this->input->get('comment_ID'));
    	$object_type  = shtmlspecialchars($this->input->get('pageType'));
    	$page    	  = intval($this->input->get('page'));
    	$action_uid   = shtmlspecialchars($this->input->get('action_uid'));
		if ($page) {
			$trueId   = $object_id;
			$trueType = $object_type;
			if ($object_type != 'topic' && $object_type != 'web_topic') {
				if (strstr($object_type, 'web_')) {
					$trueType = 'web_topic';
					$infos = service('WebTimeline')->getWebtopicByMap($object_id, str_replace('web_', '', $object_type), $this->web_id);
				} else {
					$trueType = 'topic';
					$infos = service('Timeline')->getTopicByMap($object_id, $object_type, $action_uid);
				}

				if ($infos) {
					$trueId = $infos['tid'];
				}
			}
			$lists = service('Share')->getPageList($trueType, $trueId, $page);
		
			if (!$lists) {
				$this->ajaxReturn('', '', 1);
			}
				
			// json输出内容：用户头像路径,用户名称,用户主页链接,用户间关系,赞时间
			$return = array();
			$tids   = array();
			$uids   = array();	//用于查找用户信息
			
			foreach($lists as $key => $item){
				$item = json_decode($item, true);
				$uids[$item['uid']]		  = $item['uid'];
				$kid[$key]				  = $item['uid'];
				$tids[]					  = $item['tid'];
				
				$return[$key]['action_uid']	  = $item['uid'];
				$return[$key]['avatar_s'] = get_avatar($item['uid']);
				$return[$key]['url']	  = mk_url('main/index/profile/', array('dkcode' => $this->get_dkcode($item['uid'])));
				$return[$key]['cid']	  = $item['tid'];
				$return[$key]['pageType'] = $object_type;
				$return[$key]['tid']	  = $item['tid'];
				$return[$key]['info']  = $item['content'];
				$return[$key]['ctime']    = $item['time'];
			}
			
			$userinfo = service('User')->getUserList($uids, array('uid', 'username'));	
			foreach ($userinfo as $user) {
				$userinfo[$user['uid']] = $user['username'];
			}
			
			foreach ($return as $key => $list) {
				$return[$key]['username'] = $userinfo[$list['action_uid']];
			}
				
			// 由信息ids查找信息内容和信息时间
			/*$info = service('Timeline')->getTopicByTid($tids);
			$info = json_decode($info, TRUE);
			foreach ($return as $key => $list) {
				if ($info[$list['cid']]) {
					$return[$key]['action_uid']	= $info[$list['cid']]['uid'];
					$return[$key]['info']   	= $info[$list['cid']]['content'];
					$return[$key]['ctime']  	= $info[$list['cid']]['ctime'];
				}
			}*/
			$this->ajaxReturn(array_values($return), '', 1);      			 
		} else {
			$statarr = service('Share')->getLen($object_type,$object_id);	
			$pagecount= $statarr ? ceil( $statarr / 8 ) : 1;
			
			//页面总数，只第一次加载进去
			$this->assign('pagecount', $pagecount);
			$this->assign('object_id', $object_id);
			$this->assign('action_uid', $action_uid);
			$this->assign('object_type', $object_type);
			$this->display('comment/share_lists');
		}
	}
	
	//外部调用接口
	public function call_stat($object_id='',$object_type=''){
//		$re=call_soap('comlike', 'Index', 'call_stat',array($object_id,$object_type));
		$re = service('Comlike')->call_stat($object_id,$object_type);
		return $re;
	}
	
	
	/**
	 * 权限内部函数
	 *
	 * @param $author 作者id
	 * @param $uid    当前用户id
	 * @param $tid    信息id(可能是不同类型的id),因为信息流可直接获取权限，在首页，无需进行fid+type去查找。
	 * @param integer $type 信息类型: 1：个人首人信息流; 2:网页信息流
	 */
	private function check_author($author, $uid, $tid){
		//权限处理【是否公开或者自定义-是，不进行判断关系】
    	/*
    	 *-1 : 自定义
		 * 1 : 公开
		 * 3 : 粉丝
		 * 4 : 好友
		 * 8 : 自己
		 * */
			if($author == $uid) return true;
//    		$timeline = call_soap('timeline','Timeline','getTopicByTid',array($tid));
    		$timeline = service('Timeline')->getTopicByTid($tid);
    		if($this->web_id != 1){
    			return true;
    		}
    		if(!empty($timeline)){
	    		switch ($timeline['permission']){
	    			case'-1':
	    				//自定义,在relations有权限
	    				if(in_array($uid, $timeline['relations']))
	    				$return = true;
	    				$return = false;
	    				break;
	    			case'1':
	    					$return = true;	//不处理
	    				break;
	    			case'3':
//	    				$relationship = call_soap( 'social','Social','getRelationStatus',array($uid,$author) );//确认是否为粉丝
	    				$relationship = service('Relation')->getRelationStatus($uid,$author);
	    				if($relationship != 4){
	    					$return = true;
	    				}else{
	    					$return = false;
	    				}
	    				break;
	    			case'4':
//	    				$relationship=call_soap( 'social','Social','getRelationStatus',array($uid,$author) );	//对比好友关系
	    				$relationship = service('Relation')->getRelationStatus($uid,$author);
	    				if($relationship === 10){
	    					$return=true;
	    				}else{
	    					$return=false;
	    				}
	    				break;
	    			case'8':
	    				if($author == $uid){//判断时候是自己
	    					$return=true;
	    				}else{
	    					$return=false;
	    				}	
	    				break;			
	    			default://
	    				$return=false;
	    				break;		
	    		}
    		}else{
    			$return=false;
    		}
    		return $return;
	}

	/**
	 * 可选择类型
	 * 
	 * @param string $type 类型
	 */
	public function check_type($type){
		
		$type_data = array(
			'topic',
			'ask',
			'event',
			'blog',
			'photo',
			'video',
			'album',
			'comment',
			'forward',
			'web_topic',
			'web_ask',
			'web_event',
			'web_blog',
			'web_photo',
			'web_video',
			'web_album',
			'web_comment',
			'web_forward',
			'shopping'
		);
		if(in_array($type, $type_data)) {
			return true;
		}
		return false;
	}
	
	public function mk_msgurl($type){
			//发信息链接
        $msgurl = array(
        	'topic'        => 'main/info/view',
        	'blog'         => 'blog/blog/main',
        	'video'        => 'video/video/player_video',
        	'album'        => 'album/index/photoLists',
        	'photo'        => 'album/index/photoInfo',
        	'forward'      => 'main/info/view',
        	'web_topic'    => 'main/info/view',
        	'web_blog'     => 'blog/blog/main',
        	'web_photo'    => 'walbum/photo/set_redirect',
        	'web_video'    => 'wvideo/video/player_video',
        	'web_album'    => 'walbum/photo/index',
        	'group'        => 'main/info/view',
        	'goods'        => 'webmain/index/main',
        	'web_dish'     => 'webmain/index/main',
        	'web_groupon'  => 'webmain/index/main',
        );
        
        return array_key_exists($type, $msgurl) ? $msgurl[$type] : '';
	}
}
?>
