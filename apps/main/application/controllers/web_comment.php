<?php
/**
 * 评论与赞控制器
 * 
 * @author yangshunjun 2012-07-09
 */
class Web_comment extends MY_Controller
{
	protected $uinfo = array();
	/**
	 * 初始化需要初始化用户id用户名，头像地址;
	 * 放置与uinfo数组里
	 * uinfo['username'],uinfo['username']和uinfo['avatar']
	 * */
    private function init_web()
    {
    	
        if($this->web_id) {
            //获取网页信息
            $tmp = call_soap('interest','Index','get_web_info',array($this->web_id));
           
			$this->web_info = (isset($tmp) && $tmp && count($tmp)>0) ? $tmp : null;    
			 
            if ( (!$this->web_info) || @$this->web_info['display']==1) {
                $this->redirect(WEB_ROOT . 'main/index.php');
            }
        }
    }
	public function __construct()
    {
    	 parent::__construct();
    	//------------------移植后数据初始化-----------------------
    	define("WWW_ROOT", WEB_ROOT);
    	$this->web_id = $this->input->get_post('web_id');
    	if($this->web_id) 	$this->init_web();
        
        $this->uinfo=array(
        	'uid' 	   => $this->uid,
        	'username' => $this->username,
 	        'avatar'   => get_avatar($this->uid),            //头像路径
        ); 
        //可选类型
        $this->object_type=array('web_ask','web_event','web_topic','web_blog','web_photo','web_video','web_album','web_comment');
        
        $this->msgurl = array(
							'web_topic'=>'main/info/view',
        					'web_blog'=>'blog/blog/main',
        					'web_photo'=>'album/photo/set_redirect',
        					'web_video'=>'video/video/player_video',
        					'web_album'=>'album/photo/index'
        			   );
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
 	private function get_dkcodes($uids)
	{
	    $userinfo = service('User')->getUserList($uids,array('uid','dkcode'));
	    $return=array();
	    if($userinfo)
	    {	
	    	foreach ($userinfo as $list){
	    		foreach($uids as $uid){
		    		if($uid == $list['uid'])
		    		$return[$uid]=$list['dkcode'];
	    		}
	    	}
	        return $return;
	    }
	    return 0;
	}  
	public function get_stat_all()
    {
    	$uid	    =  $this->uid;
    	$object_id  =  $this->input->get('comment_ID');  
    	$object_type=  $this->input->get('pageType'); 
    	$tid		=  $this->input->get('tid');   	
	    
        if(!($object_id && $uid  && $object_type)){
            echo json_encode(array('status'=>0,'msg'=>'获取数据异常'));exit;
        }
        $data['object_id']	= $object_id;
        $data['object_type']= $object_type;
        $data['uid']		= $uid;
        
        if(isset($data['object_id']) && isset($data['uid']) && isset($data['object_type']))
        {		
               $re = service('Comlike')->get_stat_all($data);
               $re = json_decode($re, true);
           
               if($re){
               		//对调用多次soap处理
               		$rek    =array();
               		$rekey  =array();
               		$dkuids =array();
               		
               		//--------------------评论者个人头像+端口号处理------------------------------------------------
               		foreach ($re as $k=>$list1){
	               		if(is_array($list1['data']) && !empty($list1['data'])){
		               		//查找头像端口号		
		               		foreach($list1['data'] as $key => $people){
	               				$rek[$people['uid']][]   =$k.','.$key;
	               				if(empty($dkuids[$people['uid']])) $dkuids[$people['uid']]  =$people['uid']; 
	               				$re[$k]['data'][$key]['imgUrl'] = get_avatar($people['uid']);               			
		               		}
	               		}
	               		//赞用户端口号查找
	               		if(isset($list1['greepeople'])){
	               			foreach($list1['greepeople'] as $key => $people){
		               				$rekey[$people['uid']][]   =$k.','.$key;
		               				if(empty($dkuids[$people['uid']])) $dkuids[$people['uid']]    =$people['uid'];
		               		}
	               		}
               	}
               $dkcodedata = $this->get_dkcodes(array_values($dkuids));
               		if(is_array($dkcodedata)){
               			foreach($dkcodedata as $keys=>$list){
	               			foreach($rek as $reakk=>$k1){//uid
	               				if($keys == $reakk){
	               					foreach ($k1 as $kk){ 
	               						$kk=explode(',', $kk);
	               						$re[$kk[0]]['data'][$kk[1]]['url']  = WWW_ROOT.'main/index.php?c=index&m=index&action_dkcode='.$list; 
	               					}
	               					break;
	               				}
	               			}
	               			
	               			foreach($rekey as $reakk=>$k1){
	               				if($keys == $reakk){
	               					foreach ($k1 as $kk){ 
	               						$kk=explode(',', $kk);
	               						$re[$kk[0]]['greepeople'][$kk[1]]['url']  = WWW_ROOT.'main/index.php?c=index&m=index&action_dkcode='.$list; 
	               					}
	               				}
	               			}       			
						 
	               		}
               		}
               		echo json_encode($re);exit;
               }else{
               		echo json_encode(array('status'=>0,'msg'=>'未能及时获取数据','debug'=>$re));exit;
               }
        }
    }
   
	//添加评论
    public function add_comment()
    {
    	$object_id	= $this->input->post('comment_ID');
    	$content	= $this->input->post('comment_content');
    	$pageType	= $this->input->post('pageType');
    	$src_uid	= $this->input->post('action_uid');
    	$msgname	= $this->input->post('msgname');			//信息名字，信息原链接
    	$msgurl		= $this->input->post('msgurl');
    	$tid		= $this->input->get_post('tid');				//首页热度新加
    	$uid		= $this->uid;
    	$usr_ip		= $_SERVER['REMOTE_ADDR'];
    	$username	= $this->uinfo['username'];
    	$data		= array();
    	if( $object_id && ($content || $content === '0') && in_array($pageType, $this->object_type) ){
    		$data['object_id']	= $object_id;
    		$data['object_type']= $pageType;
    		$data['content']	= $content;
    		$data['uid']		= $uid;
    		$data['src_uid']	= $src_uid;
    		$data['usr_ip']		= $usr_ip;
    		$data['username']	= $this->uinfo['username'];
        	$response = service('Comlike')->add_comment($data);
    	 	if($tid){
	        	 service('Timeline')->updateTopicHot($tid,1);
	       	}
        	//对相册评论字段操作
        	if($response && $pageType=='web_photo'){
        		$url=WEB_ROOT.'web/album/index.php?c=comment&m=update1&object_id='.$object_id;
	        	file_get_contents( $url );
        	}
			//发信息
			switch ($pageType){
				case 'web_ask':
					$treetype='ask_comment';
					break;
				case 'web_album':
					$treetype='photo_albumcomment_web';
//					call_soap('search','Restoration','restoreAlbumInfo',array('id'=>$object_id,
//																			  'type'=>1,'visible'=>0
//																			  ));
					service('RestorationSearch')->restoreAlbumInfo(array('id'=>$object_id,'type'=>1,'visible'=>0));
					if(!$msgurl) $msgurl = mk_url($this->msgurl[$pageType],array('web_id'=>($this->web_id),'albumid'=>$object_id),false);
					break;
				case 'web_photo':
					$treetype='photo_comment_web';
//					call_soap('search','Restoration','restorePhotoInfo',array('id'=>$object_id,
//																			  'type'=>1
//																			  ));
					service('RestorationSearch')->restorePhotoInfo($object_id, 1);
					if(!$msgurl) $msgurl=mk_url($this->msgurl[$pageType],array('id'=>$object_id,'web_id'=>$this->web_id),false);
					break;	
				case 'web_topic':
					$treetype='info_infocomment_web';
//					call_soap('search','Restoration','restoreStatusInfo',array(array('id'=>$tid,
//																		   'type'=>1
//																			  )));
					service('RestorationSearch')->restoreStatusInfo(array('id'=>$object_id, 'type'=>1));
					if(!$msgurl) $msgurl=mk_url($this->msgurl[$pageType],array('tid'=>$tid,'web_id'=>$this->web_id,'from'=>'web'),true);
					break;
				case 'web_video':
					$treetype='video_comment_web';
//					call_soap('search','Restoration','restoreVideoInfo',array(array('id'=>$object_id,
//																			  'type'=>1
//																			  )));
					service('RestorationSearch')->restoreVideoInfo(array('id'=>$object_id, 'type'=>1));
					if(!$msgurl) $msgurl=mk_url($this->msgurl[$pageType],array('vid'=>$object_id,'web_id'=>$this->web_id),false);
					break;				
			}
			if( ($this->uid != $src_uid) && $msgurl){
				$weburl=$this->web_id;//mk_url('main',array('web_id'=>$this->web_id));
				if(isset($msgname) && !in_array($pageType, array('web_topic','web_photo'))){
//					call_soap('ucenter', 'Notice', 'add_notice', array($this->web_id,$this->uid,$src_uid,'web',$treetype,array('name'=>$this->web_info['name'],'name1'=>$msgname,'url'=>$weburl,'url1'=>$msgurl)));
					service('Notice')->add_notice($this->web_id,$this->uid,$src_uid,'web',$treetype,array('name'=>$this->web_info['name'],'name1'=>$msgname,'url'=>$weburl,'url1'=>$msgurl));
				}else{
//					call_soap('ucenter', 'Notice', 'add_notice', array($this->web_id,$this->uid,$src_uid,'web',$treetype,array('name'=>$this->web_info['name'],'url'=>$msgurl,'url1'=>$msgurl)));
					service('Notice')->add_notice($this->web_id,$this->uid,$src_uid,'web',$treetype,array('name'=>$this->web_info['name'],'url'=>$msgurl,'url1'=>$msgurl));
				}
			}
        	echo $response;
    	}else{
    		echo json_encode(array('status'=>0,'msg'=>'无效的对象'));
    	}
    	
    }
	//删除评论
    public function del_comment()
    {
    	if($_GET['comment_ID'] && $_GET['pageType']){
    		$uid  = $this->uid;
    		$data = array('object_id' => $_GET['comment_ID'],'object_type'=>$_GET['pageType'],'uid'=>$uid);
    		//核心返回false或者操作成功后的相片评论数关于的array值
//    		$comment_count_arr=call_soap('comlike','Index','del_comment',array($data));
    		$comment_count_arr = service('Comlike')->del_comment($data);
    		if($comment_count_arr && $_GET['pageType']=='photo'){	
	    		if($comment_count_arr['comment_count']){
	    			$comment_object_id=$comment_count_arr['object_id'];
	    			$url              =WEB_ROOT.'single/album/index.php?c=comment&m=update2&object_id='.$comment_object_id;
					file_get_contents($url);
	    		}		
	    		
    		}
    		if($comment_count_arr){
    			echo json_encode(array('state'=>1,'c'=>$comment_count_arr));
    		}else{
    			echo json_encode(array('status'=>0,'msg'=>'暂时无法删除评论'));
    		}  		
    		//搜索接口
    		/*switch ($_GET['pageType']){
				case 'web_album':
					call_soap('search','Restoration','restoreAlbumInfo',array(array('id'=>$_GET['comment_ID'],
																			  'type'=>1,'visible'=>0
																			  )));
					break;
				case 'web_photo':
					call_soap('search','Restoration','restorePhotoInfo',array(array(array('id'=>$_GET['comment_ID'],
																			  'type'=>1
																			  ))));
					break;			
				case 'web_topic':
					call_soap('search','Restoration','restoreStatusInfo',array(array('id'=>$_GET['comment_ID'],
																		   'type'=>1
																			  )));
					break;
				case 'web_video':
					call_soap('search','Restoration','restoreVideoInfo',array(array('id'=>$_GET['comment_ID'],
																			  'type'=>1
																			  )));
					break;				
			}*/
    	}else{
    		echo json_encode(array('status'=>0,'msg'=>'获取数据异常'));
    	}    	
    }
	//添加赞
    public function add_like()
    {	
    	$object_id= $this->input->get_post('comment_ID');
    	$src_uid  = $this->input->get_post('action_uid');
    	$pageType = $this->input->get_post('pageType');
    	$tid	  = $this->input->get_post('tid');
    	$ctime	  = $this->input->get_post('ctime');
    	if($object_id && $pageType && $src_uid && $this->web_id){
	    	//赞模块专用数据接口
    		if(!$tid && in_array($pageType, array('web_album','web_photo','web_video','web_blog'))){
    			//类型转化为没有web的
    			switch ($pageType){
    				case 'web_album':
    					$timelinepageType='album';
    					break;
    				case 'web_photo':
    					$timelinepageType='album';
    					break;	
    				case 'web_video':
    					$timelinepageType='video';
    					break;
    				case 'web_blog':
    					$timelinepageType='blog';
    					break;		
    			}
    			$sobj = $object_id;
    			if($pageType == 'web_photo'){
    				$sobj 			  = $ctime;
    				$timelinepageType = 'album';
    			}
//		    	$info	=call_soap('timeline', 'Web', 'getWebtopicByMap', array( $sobj,$timelinepageType ));
		    	$info	= service('WebTimeline')->getWebtopicByMap($sobj,$timelinepageType);
		    	$tid 	=@$info['tid'];
		    	$ctime 	=@$info['ctime'];
    		}
    		if(!$tid){
		    	$tid=0;	
		    }else{
//		    	call_soap('search','Restoration','restoreStatusInfo',array('id'=>$tid,
//																		   'type'=>1
//																			  ));
				service('RestorationSearch')->restoreStatusInfo($tid,1);
		    }
		    if(!$ctime){
		    	$ctime=0;
		    }
    		$data = array('object_id'=>$object_id, 'object_type'=>$pageType, 'usr_ip'=>$_SERVER['REMOTE_ADDR'], 'username'=>$this->uinfo['username'], 'uid'=>$this->uid, 'src_uid'=>$src_uid,'tid'=>$tid,'ctime'=>$ctime);
	    
//	    	$re=call_soap('comlike','Index','add_like',array($data));
	    	$re = service('Comlike')->add_like($data);
	    	
	    	if(!empty($re['greepeople'])){
	    		foreach ($re['greepeople'] as $key => $list){
		    		$re['greepeople'][$key]['url'] = WWW_ROOT.'main/index.php?c=index&m=index&action_dkcode='.($this->get_dkcode($list['uid']));	
	    		}
	    	}
	    	//发信息array('ask','event','topic','blog','photo','video','album','comment');
	    	$msgname	= $this->input->post('msgname');//信息名字，信息原链接
    		$msgurl		= $this->input->post('msgurl');
			switch ($pageType){
				case 'web_album':
					$treetype='photo_albumzan_web';
//					call_soap('search','Restoration','restoreAlbumInfo',array('id'=>$object_id,
//																			  'type'=>1,'visible'=>0
//																			  ));
					service('RestorationSearch')->restoreAlbumInfo($tid,1,0);
					if(!$msgurl) $msgurl=mk_url($this->msgurl[$pageType],array('albumid'=>$object_id,'web_id'=>$this->web_id),false);
					break;
				case 'web_photo':
					$treetype='photo_zan_web';
					call_soap('search','Restoration','restorePhotoInfo',array(array(array('id'=>$object_id,
																			  'type'=>1
																			  ))));
					service('RestorationSearch')->restorePhotoInfo($tid, 1);
					if(!$msgurl) $msgurl=mk_url($this->msgurl[$pageType],array('id'=>$object_id,'web_id'=>$this->web_id),false);
					break;			
				case 'web_topic':
					$treetype='info_zaninfo_web';
//					call_soap('search','Restoration','restoreStatusInfo',array(array('id'=>$tid,
//																		   'type'=>1
//																			  )));
					service('RestorationSearch')->restoreStatusInfo($tid, 1);
					if(!$msgurl) $msgurl=mk_url($this->msgurl[$pageType],array('tid'=>$tid,'web_id'=>$this->web_id,'from'=>'web'),true);
					break;
				case 'web_video':
					$treetype='video_zan_web';
//					call_soap('search','Restoration','restoreVideoInfo',array(array('id'=>$object_id,
//																			  'type'=>1
//																			  )));
					service('RestorationSearch')->restoreVideoInfo($tid, 1);
					if(!$msgurl) $msgurl = mk_url($this->msgurl[$pageType],array('web_id'=>($this->web_id),'vid'=>$object_id),false);
					break;				
			}
    		if( ($this->uid != $src_uid) && $msgurl){
				$weburl=WEB_ROOT.'main/?web_id='.$this->web_id;
				if(isset($msgname) && !in_array($pageType, array('web_topic','web_photo'))){
					call_soap('ucenter', 'Notice', 'add_notice', array($this->web_id,$this->uid,$src_uid,'web',$treetype,array('name'=>$this->web_info['name'],'name1'=>$msgname,'url'=>$weburl,'url1'=>$msgurl)));
					service('Notice')->add_notice($this->web_id,$this->uid,$src_uid,'web',$treetype,array('name'=>$this->web_info['name'],'name1'=>$msgname,'url'=>$weburl,'url1'=>$msgurl));
				}else{
					call_soap('ucenter', 'Notice', 'add_notice', array($this->web_id,$this->uid,$src_uid,'web',$treetype,array('name'=>$this->web_info['name'],'url'=>$weburl,'url1'=>$msgurl)));
					service('Notice')->add_notice($this->web_id,$this->uid,$src_uid,'web',$treetype,array('name'=>$this->web_info['name'],'url'=>$weburl,'url1'=>$msgurl));
				}
			}
			echo json_encode($re);
	    }else{
	    	echo json_encode(array('status'=>0,'msg'=>'添加赞异常'));
	    }
    }
	//删除赞
    public function del_like()
    {
    	if( $_GET['comment_ID'] && $_GET['pageType'] ){
    		$uid  = $this->uid;
    		$data = array('object_id'=>$_GET['comment_ID'], 'object_type'=>$_GET['pageType'], 'uid'=>$uid);
    		$re   = call_soap( 'comlike','Index','del_like',array($data) );
    		$re   = service('Comlike')->del_like($data);
			//-------------------------------------赞用户处理BEGIN------------------------------------------------------------------------------------
	    	if(isset($re['greepeople'])){
	    		$rek    =array();
               	$dkuids =array();
	    		foreach ($re['greepeople'] as $key=>$list){	
		    		$rek[$list['uid']][]   =$key;
		            if(empty($dkuids[$list['uid']])) $dkuids[$list['uid']]  =$list['uid']; 
		    	}
		    	$dkcodedata = $this->get_dkcodes(array_values($dkuids)); 
    			if(is_array($dkcodedata)){
	               	foreach($dkcodedata as $keys=>$list){
	               			foreach($rek as $reakk=>$k1){
	               				if($keys == $reakk){
	               					foreach ($k1 as $kk){ $re['greepeople'][$kk]['url']  = mk_url('main/index/index',array('action_dkcode'=>$list));
	               					}
	               					break;
	               			}
	               		}  			
	             	}
    			}	
	    	}
    		//-------------------------------------赞用户处理END------------------------------------------------------------------------------------
    		
	    	//搜索接口
    		switch ($_GET['pageType']){
				case 'web_album':
//					call_soap('search','Restoration','restoreAlbumInfo',array(array('id'=>$_GET['comment_ID'],
//																			  'type'=>1,'visible'=>0
//																			  )));
					service('RestorationSearch')->restoreAlbumInfo(array('id'=>$_GET['comment_ID'], 'type'=>1,'visible'=>0));
					break;
				case 'web_photo':
//					call_soap('search','Restoration','restorePhotoInfo',array(array(array('id'=>$_GET['comment_ID'],
//																			  'type'=>1
//																			  ))));
					service('RestorationSearch')->restorePhotoInfo(array(array('id'=>$_GET['comment_ID'], 'type'=>1)));
					break;			
				case 'web_topic':
//					call_soap('search','Restoration','restoreStatusInfo',array(array('id'=>$_GET['comment_ID'],
//																		   'type'=>1
//																			  )));
					service('RestorationSearch')->restoreStatusInfo(array(array('id'=>$_GET['comment_ID'], 'type'=>1)));
					break;
				case 'web_video':
//					call_soap('search','Restoration','restoreVideoInfo',array(array('id'=>$_GET['comment_ID'],
//																			  'type'=>1
//																			  )));
					service('RestorationSearch')->restoreVideoInfo(array(array('id'=>$_GET['comment_ID'], 'type'=>1)));
					break;				
			}
	    	echo json_encode($re);
    	}else{
    		echo json_encode(array('status'=>0,'msg'=>'获取数据异常'));
    	}
    }
	//获取所有赞列表
    public function get_all_comment()
    {
    	$object_id    = $this->input->get('comment_ID');
    	$object_type  = $this->input->get('pageType');
    	$page  		  = $this->input->get('pageIndex');
    	$uid		  = $this->uid;
    	$data=array('object_id'=>$object_id,'object_type'=>$object_type,'uid'=>$uid,'page'=>$page);
    	$re = service('Comlike')->get_all_comment($data);
		$re=json_decode($re,1);
    	if($re){   		
    	//加载评论者个人头像+端口号
    		if(is_array($re['data']) && !empty($re['data'])){
    			$rek    =array();
               	$dkuids =array();
	            foreach ($re['data'] as $key=>$people){
			              $rek[$people['uid']][]   =$key;
		               	  if(empty($dkuids[$people['uid']])) $dkuids[$people['uid']]  =$people['uid']; 
		               	  $re['data'][$key]['imgUrl'] = get_avatar($people['uid']);

	            }
    		}
    		$dkcodedata = $this->get_dkcodes(array_values($dkuids)); 
    		if(is_array($dkcodedata)){
	               	foreach($dkcodedata as $keys=>$list){
	               		foreach($rek as $reakk=>$k1){//uid
	               			if($keys == $reakk){
	               				foreach ($k1 as $kk){ $re['data'][$kk]['url']  = WWW_ROOT.'main/index.php?c=index&m=index&action_dkcode='.$list; 
	               				}
	               				break;
	               			}
	               		}  			
	               	}
    		}	
            echo json_encode($re);exit;
         }else{
         	echo json_encode(array('status'=>0,'msg'=>'获取数据异常'));exit;
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
		$object_id    = $this->input->get('comment_ID');
    	$object_type  = $this->input->get('pageType');
    	$page    	  = (empty( $_GET['page'] ))?0:$_GET['page'];	    							  //判断当前请求是数据[page>0]还是初始化[page=0]
		$uid  		  = $this->uid;
		if(empty($uid) || empty($object_id) || empty($object_type) || !$this->web_id){
			echo json_encode(array('status'=>0,'msg'=>'登录异常'));exit;
		}
		
		/**page区分请求类型**/
		if($page){
			$lists = service('Comlike')->getLike(array(								  //获取当前请求数据
			    'object_id'	    =>    $object_id,
				'object_type'	=>    $object_type,
			    'page'			=>    $page,
				'order'			=>	  'date_desc',
			));
			$lists=json_decode($lists,1);                        			
			if($lists){													//返回数组
				/**
				 * json输出内容：用户头像路径,用户名称,用户主页链接,用户间关系,[赞时间]。
				 * */
				$return 	= array();
				//------用于找用户关系----------------
				$keys		= array();
				$uids		= array();
				//------用于查找dkcode----------------
				$keys1		= array();
				$dkcodes	= array();
				
				foreach($lists as $key => $item){
					//关系列表  2无关系， 10好友, 6 相互关注, 4 粉丝, 8等待对方接受好友邀请
					if($item['uid'] == $this->web_info['uid']){
						//可以加关注，取消关注
						$return[$key]['username']		=	$this->web_info['name'];
						$return[$key]['avatar_s']		=	get_webavatar($item['uid'],'s',$this->web_id);
						$return[$key]['url']			=	WEB_ROOT.'main'.'?web_id='.$this->web_id;
						$return[$key]['isweb'] 			=	true;
						
						if($this->uid == $this->web_info['uid']){
							$return[$key]['relationship']	=	7;		//网页用户id,可以进行网页关注
						}else{
							//判断是否已经关注，没加关注0，已关注1 isFollowing
							//------------------判断网页关注,因为只可能有一次关注，那么soap关注判断最多调用一次-------------	
//							$attension=call_soap( 'social','Webpage','isFollowing',array($this->uid,$this->web_id) );
							$attension = service('WebpageRelation')->isFollowing($this->uid,$this->web_id);
							if($attension){
								$return[$key]['relationship']	=	1;
							}else{	
								$return[$key]['relationship']	=	0;	
							}
						}
					}else{
						if($this->uid == $item['uid']){
							$return[$key]['relationship']	=	7;	
						}else{
							$keys[$key]	  = $item['uid'];
							if(!in_array($item['uid'], $uids))
							$uids[]	 	  = $item['uid'];
						}
						$return[$key]['isweb'] 			=	false;
						$return[$key]['username']		=	$item['username'];
						$return[$key]['avatar_s']		=	get_avatar($item['uid']);
						//$return[$key]['url']			=	WWW_ROOT.'main/index.php?c=index&m=index&action_dkcode='.($this->get_dkcode($item['uid']));
						$keys1[$item['uid']][]	 		= 	$key;
						if(isset($item['uid']))
						$dkcodes[$item['uid']]		 	=   $item['uid'];
					}
					$dateline=$item['dateline'];						//赞的时间
					
					$return[$key]['uid']			=	$item['uid'];
				    $return[$key]['dateline']		=	$dateline;
				}
				//-------------------------------获取用户关系----------------------------------------------------------
				if($uids){
//					$relationships=call_soap( 'social','Social','getMultiRelationStatus',array( $this->uid,$uids) );
					$attension = service('Relation')->getMultiRelationStatus($this->uid, $uids);
					foreach($relationships as $rek => $relist){
						$kk=array_keys($keys,substr($rek,1));
						foreach ($kk as $k){ $return[$k]['relationship']	=	$relist; }
					}
				}
				//-------------------------------获取dkcode-----------------------------------------------------------
				if($dkcodes){
					$dkcodedata=$this->get_dkcodes(array_values($dkcodes)); 
	    			if(is_array($dkcodedata)){
		               		foreach($dkcodedata as $key2=>$list){
		               			foreach($keys1 as $reakk=>$k1){//uid
		               				if($key2 == $reakk){
		               					foreach ($k1 as $kk){ $return[$kk]['url']  =WWW_ROOT.'main/index.php?c=index&m=index&action_dkcode='.$list; }
		               					break;
		               				}
		               			}  			
		               		}
	    			}	
				}

				$returnjson=json_encode($return);						 //每次请求返回的json数据，不覆盖已有的
				echo 	$returnjson;	
			}else{
				echo json_encode(array('status'=>0,'msg'=>'加载异常'));exit;
			}
			
						      			 
		}else{
//			$statarr  = call_soap( 'comlike','Index','getStat',array($object_id,$object_type) );		  //取统计表数,用于首次计算页面数量
			$statarr = service('Comlike')->getStat($object_id,$object_type);
			$p=intval(($statarr[0]['like_count'])/50);
			$pagecount=$p ? $p : 1;					//分页计算
			$this->assign('pagecount',$pagecount);	//页面总数，只第一次加载进去
			$this->assign('object_id',$object_id);
			$this->assign('object_type',$object_type);
			$this->assign('web_id',$this->web_id);
			$this->display('comment/like_lists.html');
		}

	}

}
?>
