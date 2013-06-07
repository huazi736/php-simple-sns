<?php
/**
 * 发现兴趣
 * 
 * **/

class Execute extends MY_Controller{
	function __construct(){
		parent::__construct();
		if(@$_GET['key']!='duankou_daemon' && $_SERVER['key']!='duankou_daemon' ){
			echo "not or error key.\r\n";
			die;
		}
		$this->load->model('apps_infomodel' , 'apps_info');
	}
	
	function index(){
		//设置执行时间
		ini_set('max_execution_time' , 30);
		$result	= $this->apps_info->get_apps_queue();
		if(is_array($result)){
			foreach ($result as $key=>$val){
				if($val['data']!=''){
					$data = (array) @json_decode($val['data']);
					if(is_array($data)){	
									
						// 执行  删除
						foreach ($data as $tt){
							$tt2 	= (array) $tt;
							if(isset($tt2['call']) && $tt2['call']!=''){
								$this->control($tt2['call'] ,(array)$tt2['data'] );
							}
						}
					}
				}
				
				// 删除关注网页的数据
				$this->del_web_category($val['execute']);
				
				// 删除网页
				$this->apps_info->delete_web($val['execute']);
				// 关闭队列
				$this->apps_info->close_queue($val['id']);
				
				echo "close web web_id=".$val['execute']."\r\n\r\n";
			}
		}
		
	}
	
	
	function control($fun , $data){
		if(method_exists($this , $fun) ){
			$this->$fun($data);
		}else{
			echo "not function ".$fun.'() <br>'."\r\n";
		}
	}
	
	
	//删除网页相关的数据
	public function del_web($data){
		if(!$data){
			exit('no data');
		}
		$infos = $data;
		
		//用户id
		$uid = $infos['uid'];
		//网页id
		$web_id = $infos['web_id'];
		//iid网面所属的二级分类id
		$iid =  $infos['iid'];
		$imid	= $infos['imid'];
		$web_name =  $infos['web_name'];
		
		unset($data);
		unset($infos);
		
		//删除网页相关的相册
		$this->del_album($web_id,$uid);
		
		//删除个人首页-所有通知页面中的该网页分类及该网页产生的所有通知条目
		$this->del_count($web_id,$uid);
		
		//删除网页时给网页粉丝发信息
		$this->send_notice($web_id,$uid,$web_name);
		
		//删除网页时给网页活动参加者发信息
		$this->send_notice_event($web_id,$uid);
		
		//删除网页相关的视频
		$this->del_video($web_id,$uid);
		
		//删除网页 转发赞，评论
		$this->del_zan($web_id,$uid);
		
	 	//删除此网页产生的所有信息流
		$this->del_praise($web_id,$imid,$uid);
		
		//清除网页与关注用户之间的关系
		$this->del_web_relation($web_id,$uid);
		
		//删除搜索引擎中的与本网页有关的索引
		$this->del_search($web_id,$uid);
		
		/***	积分接口 	删除网页减分		***/
		service('credit')->web(false,$uid);
	}
	

	//删除网页时给网页粉丝发信息
	private function send_notice($web_id,$uid,$web_name){
		//取得本网页的粉丝用户uid
		$uids = service('WebpageRelation')->getAllFollowers($web_id);
		$ret = false;
		if(count($uids)>0){
			$ret = service('Notice')->add_notice($web_id,$uid,$uids,'web','dk_del_web',array('name'=>$web_name));
		}
		
		$doc = array(
			'action'=>'Execute',
			'method'=>'send_notice',
			'web_id'=>$web_id,
			'web_name'=>$web_name,
			'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
	//删除网页时给网页活动参加者发信息
	private function send_notice_event ($web_id,$uid){
		$ret = service('WebEvent')->delEvent($web_id);
		$doc = array(
			'action'=>'Execute',
			'method'=>'send_notice_event',
			'web_id'=>$web_id,
		    'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
	//删除此网页产生的所有信息流
	private function del_praise($web_id,$imid,$uid){
		$ret = service('WebTimeline')->delWebpage($web_id,array($imid));
		$doc = array(
			'action'=>'Execute',
			'method'=>'del_praise',
			'web_id'=>$web_id,
			'iid'=>$imid,
			'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
	//删除搜索引擎中的与本网页有关的索引
	private function del_search($web_id,$uid){
		$ret = service('RelationIndexSearch')->deleteWebpage($web_id);
		$doc = array(
			'action'=>'Execute',
			'method'=>'del_search',
			'web_id'=>$web_id,
			'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
	//删除个人首页-所有通知页面中的该网页分类及该网页产生的所有通知条目
	private function del_count($web_id,$uid){
		$ret = service('Notice')->del_noticeall($web_id);
		$doc = array(
			'action'=>'Execute',
			'method'=>'del_count',
			'web_id'=>$web_id,
			'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
	//清除网页与关注用户之间的关系
	private function del_web_relation($web_id,$uid){
		$ret = service('WebpageRelation')->clearRelation($web_id);
		$doc = array(
			'action'=>'Execute',
			'method'=>'del_web_relation',
			'web_id'=>$web_id,
			'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
	// 删除关注的网页与分类
	private function del_web_category($web_id){
		$this->apps_info->del_web_attention_category($web_id);	// 删除  关注网页的分类
		$this->apps_info->del_apps_attention($web_id);	// 删除   关注的网页
	}
	
	
	//删除网页相关的视频
	private function del_video($web_id,$uid){
		$ret = service('Video')->delWebVideoApi($web_id);
		$doc = array(
			'action'=>'Execute',
			'method'=>'del_video',
			'web_id'=>$web_id,
			'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
	//删除网页相关的相册
	private function del_album($web_id,$uid){
		$ret = service('Album')->deleteWebAlbum($web_id);
		$doc = array(
			'action'=>'Execute',
			'method'=>'del_album',
			'web_id'=>$web_id,
			'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
	//删除网页 转发赞，评论
	private function del_zan($web_id,$uid){
		$ret = service('Comlike')->delWebPage($web_id);
		$doc = array(
			'action'=>'Execute',
			'method'=>'del_zan',
			'web_id'=>$web_id,
			'ret'=>$ret,
		);
		log_user_msg($uid,$doc);
		unset($doc);
	}
	
}

