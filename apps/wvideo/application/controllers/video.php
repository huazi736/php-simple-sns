<?php 
/**
 * 网页视频
 * @author        qqyu wangying
 * @date          2012/02/21
 * @version       1.2
 * @description   视频页面相关功能
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class video extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('videomodel');
		$this->load->model('accessmodel');
		$this->config->load('video');
		$this->load->helper('wvideo');

		$this->assign('avatar_url',get_webavatar($this->web_id, $size = 's'));
		$this->assign('webmain_url', mk_url('webmain/index/main',array('web_id'=>$this->web_id)));
		$this->assign('video_url',mk_url('wvideo/video/index',array('web_id'=>$this->web_id)));
	}
	/**
	 * 取得视频列表
	 *
	 * @author wangying
	 * @date   2012/02/27
	 * @access public
	 */
	public function index()
	{
		$action_uid = $this->action_uid;
		$is_author = ($this->uid == $action_uid) ? 1 : 0 ;
		$allnums = $this->videomodel->getVideoListsAllNums($action_uid,$this->web_id);
		if($allnums['rows']){
			$num = $allnums['rows'];
			$result = $this->videomodel->getVideoLists($action_uid,$this->web_id,1);
		}else{
			$num = 0;
		}
		$tips = $is_author ? '你还没有添加任何视频' : '该网页尚未添加任何视频' ;
		$star_time = $num ? $result[0]['dateline'] : '' ;

		$this->assign('web_id',$this->web_id);
		$this->assign('is_author',$is_author);
		$this->assign('addvideo_url',mk_url('wvideo/video/video_upload',array('web_id'=>$this->web_id)));
		$this->assign('username',$this->web_info['name']);
		$this->assign('action_uid',$action_uid);
		$this->assign('star_time',$star_time);
		$this->assign('tips',$tips);
		$this->assign('num',$num);
		$this->display('video_list');
	}
	/**
	 * AJAX取得更多视频列表
	 * ajax：json
	 * @author wangying
	 * @date   2012/2/29
	 * @access public
	 * @param integer num 加载展示次数
	 * @param integer start 展示的最后一个视频的上传时间
	 */
	public function ajax_lists(){
		$page = $this->input->post('page');
		$action_uid = htmlspecialchars($this->input->get('action_uid'));
		$dateline = htmlspecialchars($this->input->get('dateline'));

		$is_author = $action_uid==$this->uid ? 1 : 0;
		$limit = (($page-1)*16).',16';
		$allnums = $this->videomodel->getVideoListsAllNums($action_uid,$this->web_id,$dateline);
		$result = $this->videomodel->getVideoLists($action_uid,$this->web_id,$limit,$dateline);
		foreach ($result as $key => $value){
			$result[$key]['video_pic'] = get_video_img($value['video_pic'],'_1');
			$result[$key]['time'] =  floor($value['lentime']/60).':'.($value['lentime']%60);
		}
		$total_page = ceil($allnums['rows'] / 16);
		$isend = ($total_page - $page)>0 ? false:true;

		$data = array('content' => $result,'is_author'=>$is_author,'isend'=>$isend);
		$this->ajaxReturn($data, '', 1, $type = 'json');

	}
	/**
	 * 视频播放页面--单个视频展示
	 *
	 * @author wangying
	 * @date   2012/3/02
	 * @access public
     * @param integer vid 视频id
	 */
	public function player_video(){
		$vid = (int)$this->input->get('vid');
		if(!$vid) $this->error('对不起,您访问的视频不存在!');

		$fields = 'id,title,uid,video_pic,discription,dateline,volume,status';
		$videoinfo = $this->videomodel->getOneVideoinfo($vid,$fields);
		if(empty($videoinfo)) $this->error('对不起,您访问的视频不存在！');
		if($videoinfo['status'] == 4 || $videoinfo['status']== 5){
			$this->error('对不起,视频在审核中暂时不能访问！');
		}
		if($videoinfo['uid'] == $this->uid){//访问者为自己本人
			$is_author = true;
			$this->assign('login_avatar',get_webavatar($this->web_id,'s'));
			$this->assign('login_username',$this->web_info['name']);
			$this->assign('login_userpageurl',mk_url('webmain/index/main',array('web_id'=>$this->web_id)));
		}else{
			$is_author = false;
			$this->assign('login_avatar',get_avatar($this->uid,'s'));
			$this->assign('login_username',$this->username);
			$this->assign('login_userpageurl',mk_url('main/index/main', array('dkcode'=>$this->dkcode)));
		}
		$videoinfo['video_pic'] = get_video_img($videoinfo['video_pic'],'_1');
		$videoinfo['video_player'] = 'vid='.$vid.'&mod=2';
		$friendlydate = friendlyDate($videoinfo['dateline']); //动态时间差

		$play_url = mk_url('wvideo/video/player_video', array('vid'=> $vid));
		$edit_url = mk_url('wvideo/video/edit_video', array('vid'=> $vid,'web_id'=>$this->web_id,'referer'=>'play'));
		$del_url = mk_url('wvideo/video/del_video', array('vid'=> $vid,'web_id'=>$this->web_id));
		$this->assign('username',$this->web_info['name']);
		$this->assign('play_url',$play_url);
		$this->assign('edit_url',$edit_url);
		$this->assign('del_url',$del_url);
		$this->assign('friendlydate',$friendlydate);
		$this->assign('uid',$this->uid);
		$this->assign('web_id',$this->web_id);
		$this->assign('vid',$vid);
		$this->assign('volume',$videoinfo['volume']);
		$this->assign('action_uid',$videoinfo['uid']);
		$this->assign('is_author',$is_author);
		$this->assign('videoinfo',$videoinfo);
		$this->display('video_info');
	}

	/**
	 * 显示视频上传页面
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 */
	function video_upload(){
		if($this->uid != $this->action_uid) $this->showmessage('对不起,您没有上传权限!');
		$this->assign('add_url',mk_url('wvideo/video/add_video', array('web_id'=>$this->web_id)));
		$authcode_url = authcode('video','',config_item('authcode_key') );
		$this->assign('authcode_url',base64_encode($authcode_url));
		$this->assign('video_upload_url',config_item('video_upload_url'));
		$this->assign('web_id',$this->web_id);
		$this->assign('username',$this->web_info['name']);
		$this->display('video_upload.html');
	}

	/**
	 * 上传视频页面->保存上传视频信息（新版）
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $uid 用户id
	 */
	function add_video(){
		$id        = (int)$this->input->post('vid');
		$vd['uid'] = $this->uid;
		$vd['id']  = $this->get_vid();
		$title  = trim($this->input->post('title'));
		$discription = trim($this->input->post('txtdesc'));
		if($title){
			if(mb_strlen($title,'utf8') > 50) $title = mb_substr($title,0,50,'utf-8');
			$vd['title'] = check_string($title);
		}else{
			$vd['title'] = date('Y-m-d H:i:s');
		}
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');
		$vd['discription'] = check_string($discription);
		$vd['dateline']    = time();
		$vd['web_id']      = $this->web_id;
			
		$list = $this->videomodel->getTmp($id);
		if(!$list) $this->ajaxReturn($data='', $info = '保存失败!!!', 0, $type = 'json');

		$video_data = array();
		$video_data      = unserialize($list['video_data']);
		$vd['type']      = $video_data['type'];
		$vd['lentime']   = $video_data['lentime'];
		$vd['width']     = $video_data['width'];
		$vd['height']    = $video_data['height'];
		$vd['video_src'] = $list['video_src'];	
		$vd['video_pic'] = $list['video_pic'];
		$vd['check']     = config_item('check');
		unset($video_data);
		$this->videomodel->addVideo($vd['uid'],$vd);
		if($vd['type'] == 'flv'){
			$this->videomodel->delTmp($id);
			if($vd['check']== 0){
				//入注时间线--更新应用区图片--添加索引
				$pic_path   = get_img_path($vd['video_pic']);
				$this->addTimeline($vd,$pic_path);
				$this->accessmodel->apisetUserMenuImg($vd['web_id'],$vd['video_pic']);  
				$vd['uname'] = $this->web_info['name'];
				$this->accessmodel->addVideoSearch($vd);
				
				$data = array('vid' => $vd['id'],'url'=>mk_url('wvideo/video/player_video',array('vid' => $vd['id'])));
				$this->ajaxReturn($data, $info = '上传成功!!!', 1, $type = 'json');
			}else{
				$data = array('url'=>mk_url('wvideo/video/index',array('web_id'=>$vd['web_id'])));
				$this->ajaxReturn($data, $info = '上传成功,请等待系统处理及审核!', 3, $type = 'json');
			}
		}else{
			$update_data['status'] = 5;
			$update_data['vid'] = $vd['id'];
			$this->videomodel->updateTmp($update_data,$id);
			$data = array('url'=> mk_url('wvideo/video/index',array('web_id'=>$vd['web_id'])));
			if($vd['check']== 0){
				$this->ajaxReturn($data, $info = '视频正在转码中……<br/>视频处理结果会以通知的形式告诉您。', 2, $type = 'json');
			}else{
				$this->ajaxReturn($data, $info = '视频已上传成功,请等待系统处理及审核!', 3, $type = 'json');
			}
		}
	}

	
	/**
	 * 编辑视频页面
	 * @author qqyu
	 * @date   2012/02/23
	 * @param  $vid 视频vid
	 */
	function edit_video(){
		$vid = (int)$this->input->get('vid');
		$play = $this->input->get('referer');
		$play = $play ? 1 : 2 ;
		if(!$vid) $this->error('您访问的视频不存在!');

		$fields = 'id,uid,title,discription,video_pic';
		$videoinfo = $this->videomodel->getOneVideoinfo($vid,$fields);
		if( empty($videoinfo) || ($videoinfo['uid'] != $this->uid)) $this->showmessage('此视频不允许你编辑！');
		$videoinfo['video_pic'] = get_video_img($videoinfo['video_pic'],'_1');

		$save_url = mk_url('wvideo/video/save_video', array('re'=> $play,'web_id'=>$this->web_id));
		$del_url = mk_url('wvideo/video/del_video', array('re'=> $play,'web_id'=>$this->web_id));

		$this->assign('save_url',$save_url);
		$this->assign('del_url',$del_url);
		$this->assign('url',$_SERVER['HTTP_REFERER']);
		$this->assign('play',$play);
		$this->assign('web_id',$this->web_id);
		$this->assign('username',$this->web_info['name']);
		$this->assign('videoinfo',$videoinfo);
		$this->display('video_edit.html');
	}

	/**
	 * 编辑视频页面->保存修改的视频信息
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 */
	function save_video(){
		$vid = (int)$this->input->post('vid');
		$title = trim($this->input->post('title'));
		$discription = trim($this->input->post('txtdesc'));//视频说明
		
		if($title){
			if(mb_strlen($title,'utf8') > 50) $title = mb_substr($title,0,50,'utf-8');
			$update_data['title'] = check_string($title);
		}else{
			$update_data['title'] = date('Y-m-d H:i:s');
		}
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');
		$update_data['discription'] = check_string($discription);

		$res = $this->videomodel->updateVideo($vid,$update_data,$this->uid);
		if(!$res) $this->error('修改视频信息失败!!!');
		
		$fields= 'id,uid,video_pic,title,discription,dateline,status';
		$vd = $this->videomodel->getVideoInfo($vid,$fields,2);
		if($vd['status'] == 4 || $vd['status']== 5){//审核中视频编辑只更新数据库,不做其他操作
			$this->redirect('wvideo/video/index',array('web_id'=>$this->web_id));
		}
		//修改时间线
		$bool = $this->accessmodel->getWebtopicVideoInfo($vid,$this->web_id);
		if($bool) $this->accessmodel->updateTimeline($vid,$this->web_id,$update_data['title'],$update_data['discription']);
		//删除旧索引 ,添加新索引
		$this->accessmodel->apiSearchDeleteWebVideoId($vid);
		//$this->accessmodel->restoreVideoInfo($vd['id']);
		$vd['uname'] = $this->username;	
		$this->accessmodel->addVideoSearch($vd);
		
		//暂时跳转到该页面
		$play = $this->input->get('re');
		if($play == 2) $this->redirect('wvideo/video/index', array('web_id'=>$this->web_id));
		$this->redirect('wvideo/video/player_video',array('vid'=>$vid));
	}
	/**
	 * 删除视频（彻底删除）
	 *
	 * @author qqyu
	 * @date   2012/02/23
  	 * @param  $vid 视频ID
	 */
	function del_video(){
		$uid = $this->uid;
		$vid = (int)$this->input->post('videoID');
		if(!$vid) $this->ajaxReturn('','视频文件已删除或不存在!',0,'json');
		$fields = 'uid,video_pic,video_src,status';
		$videoinfo = $this->videomodel->getVideoInfo($vid,$fields,2);
		if( empty($videoinfo) || ($uid != $videoinfo['uid']) ) $this->ajaxReturn($data='', $info = '您没有权限删除该视频!', $status = 0, $type = 'json');

		$vd = array();
		$vd = $this->videomodel->getTowNewVideo($uid,$this->web_id);
		$res = $this->videomodel->delVideo($vid,$uid);
		if(!$res) $this->ajaxReturn($data='', $info = '删除视频失败!', $status = 0, $type = 'json');
		$this->del_fastdfs_flies($videoinfo['video_pic'],$videoinfo['video_src']);
		$data = array('url'=> mk_url('wvideo/video/index',array('web_id'=>$this->web_id)));
		if($videoinfo['status'] == 4 || $videoinfo['status']== 5){//删除审核中的视频只更新数据库,不做其他操作
			$this->ajaxReturn($data, $info = '视频删除成功!', $status = 1, $type = 'json');
		}	
		//更新应用区图片
		if(count($vd) > 0 && $vd[0]['id'] == $vid){
			if(count($vd) == 2 && $vd[1]['video_pic']!= ''){
				$vd['video_pic'] = $vd[1]['video_pic'];
			}else{
				$vd['video_pic'] = '';
			}
		}else{
			$vd['video_pic'] = $vd[0]['video_pic'];
		}
		//删除时间线--更新应用区图片--删除索引
		$bool = $this->accessmodel->getWebtopicVideoInfo($vid,$this->web_id);
		if($bool) $this->accessmodel->delTimeline($vid,$this->web_id);
		$this->accessmodel->apisetUserMenuImg($this->web_id,$vd['video_pic']);
		$this->accessmodel->apiSearchDeleteWebVideoId($vid);
		
		$this->ajaxReturn($data, $info = '视频删除成功!', $status = 1, $type = 'json');
	}
	/**
	 * 删除fastdfs上图片和视频文件
	 * @author qqyu
	 * @param unknown_type $video_pic
	 * @param unknown_type $video_src
	 */
	function del_fastdfs_flies($video_pic,$video_src){
		//删除fastdfs上的两张图片和视频
		$this->load->fastdfs('album','', 'fastdfs');
		$pic_info = explode('/',$video_pic,2);
		$this->fastdfs->deleteFile($pic_info[0], $pic_info[1]);
		$this->fastdfs->deleteFile($pic_info[0], $pic_info[1],'_1');
		$this->fastdfs->deleteFile($pic_info[0], $pic_info[1],'_ico');
		
		$this->load->fastdfs('video', '', 'fastdfs');
		$video_info = explode('/',$video_src,2);
		$this->fastdfs->deleteFile($video_info[0], $video_info[1]);
	}
	/**
	 * 显示录制页面
	 * @author qqyu
	 * @date   2012/04/09
	 */
	function list_cam(){
		if($this->uid != $this->uid){
			$this->error('对不起,您没有录制权限!');
		}
		$this->assign('web_id',$this->web_id);
		$this->assign('recordurl',config_item('recordurl'));
		$this->assign('uid',date('YmdHis').'_'.$this->uid);
		$this->assign('username',$this->web_info['name']);
		$this->display('video_cam.html');
	} 

	/**
	 * 录制视频
	 * @author qqyu
	 * @date   2012/04/09
	 */
	function save_makevideo(){
		set_time_limit(0);
		$vd['uid'] = $this->uid;	
		if(!$vd['uid']){
			$this->ajaxReturn($data='', $info = '请先登录!', 0, $type = 'json');
		}
		$title = trim($this->input->post('title'));
		$discription = trim($this->input->post('txtdesc'));	
		if($title){
			if(mb_strlen($title,'utf8') > 50) $title = mb_substr($title,0,50,'utf-8');
			$vd['title'] = check_string($title);
		}else{
			$vd['title'] = date('Y-m-d H:i:s');
		}			
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');	
		$vd['discription'] = check_string($discription);	
		$name = $this->input->post('hd_v_name');
		$vd['id'] = $this->get_vid();	
		$url = authcode('video','ENCODE',config_item('authcode_key'));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,  config_item('transcod_url').'record.php?appkey='.base64_encode($url).'&file='.$name.'&mid=2');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		$sFile = curl_exec($ch);
		curl_close($ch);
		$str = json_decode($sFile);	
		if(!$sFile || $str->status == 0){
			$this->ajaxReturn($data='', $info = $str->info, 0, $type = 'json');
		}
		$vd['web_id'] = $this->web_id;
		$vd['type'] = 'flv';
		$id = $str->data->id;	
		$vd['video_src'] = $str->data->video_src;	
		$vd['video_pic'] = $str->data->video_pic;
		$vd['lentime'] = ceil($str->data->lentime);	
		$vd['width'] = $str->data->width;
		$vd['height'] = $str->data->height;	
		$vd['dateline'] = time();	
		$vd['check'] = config_item('check');						
		$this->videomodel->addVideo($vd['uid'],$vd);	
		$this->videomodel->delTmp($id);	
		$data = array('url'=>mk_url('wvideo/video/index',array('web_id'=>$this->web_id)));	
		if($vd['check']== 0){
			//入驻时间线--更新应用区图片--增加索引
			$pic_path  = get_img_path($vd['video_pic']);
			$this->addTimeline($vd,$pic_path);
			$this->accessmodel->apisetUserMenuImg($this->web_id,$vd['video_pic']);  		
			$vd['uname'] = $this->web_info['name'];
			$this->accessmodel->addVideoSearch($vd);
			
			$this->ajaxReturn($data, $info = '视频发布成功!', 1, $type = 'json');
		}else{
			$this->ajaxReturn($data, $info = '视频已上传成功,请等待系统处理及审核!', 1, $type = 'json');
		}
	}
	/**
	 * 入驻时间线
	 * @author qqyu
	 * @param  array $vd
	 * @param  string $uid
	 */
	function addTimeline($vd,$video_pic){
		//入驻时间线
		$data = array(
			'type' => 'video',
			'fid' => $vd['id'],
			'uid' => $vd['uid'],
			'pid' => $this->web_id,
			'dkcode' => $this->user['dkcode'],
			'uname' => $this->web_info['name'],
			'title' => $vd['title'],
			'content' => $vd['discription'],
		 	'width' => $vd['width'],
    		'height' => $vd['height'],
			'imgurl' => $video_pic,
			'timedesc' => '',//时间描述
			'dateline' => date('YmdHis',$vd['dateline'])
		);
		$this->accessmodel->setTimeline($data,$this->web_id);
		return true;
	}
	
	/**
	 * 批量全部更新时间线和搜索索引(慎重)
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @return string
	 */
	function update(){
		$a = isset($_GET['a']) ? $_GET['a'] : '';
			if($a == 'a'){
				$data = $this->videomodel->test();
				foreach ($data as $value) {
					//修改时间线			
					$imgurl = $value['video_pic'];
					$bool = $this->accessmodel->getWebtopicVideoInfo($value['id'],$value['web_id']);
					if($bool) $this->accessmodel->resetTimeline($value['id'],$value['web_id'],$imgurl);
					
					/*
					// 删除旧索引 
					$this->accessmodel->apiSearchDeleteWebVideoId($value['id']);
					//添加新索引
					$web_info = service('interest')->get_web_info($value['web_id']);
					$video_info=array(
						'web_id' => $value['web_id'],
						'id'     => $value['id'],
						'uid'    => $value['uid'],
						'uname'  => $web_info['name'],		
						'title'  => $value['title'],
						'dateline' => $value['dateline'],
						'video_pic'   => $value['video_pic'],
						'discription' => $value['discription']		
					);
					$this->accessmodel->addVideoSearch($video_info);
					*/
				}
				die('更新时间线和索引成功!');
		}
	}
	/**
	 * 批量全部更新时间线和搜索索引(慎重)
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @return string
	 */
	/*
	function update1(){
		$a = isset($_GET['a']) ? $_GET['a'] : '';
		$id = isset($_GET['id']) ? $_GET['id'] : '';
		if($a == 'a' && $id ){
			$data = $this->videomodel->test1($id);
			$value = $data[0];
			if(isset($data[1])){
				$next_id = $data[1]['id'];
			}else{
				$next_id ='';
			}	
			//修改时间线			
			$imgurl = $value['video_pic'];
			$bool = $this->accessmodel->getWebtopicVideoInfo($value['id'],$value['web_id']);
			if($bool) $this->accessmodel->resetTimeline($value['id'],$value['web_id'],$imgurl);				
			// 删除旧索引 
			$this->accessmodel->apiSearchDeleteWebVideoId($value['id']);
			//添加新索引
			$web_info = service('interest')->get_web_info($value['web_id']);
			$video_info=array(
				'web_id' => $value['web_id'],
				'id'     => $value['id'],
				'uid'    => $value['uid'],
				'uname'  => $web_info['name'],		
				'title'  => $value['title'],
				'dateline' => $value['dateline'],
				'video_pic'   => $value['video_pic'],
			    'discription' => $value['discription']		
			);
			$this->accessmodel->addVideoSearch($video_info);
			if(!$next_id) die('更新时间线和索引成功!');
			$url = mk_url('wvideo/video/update1',array('web_id'=>$this->web_id,'a'=>'a','id'=>$next_id));
			echo '<script>window.location.href="'.$url.'";</script>';
		}
	}
	function gettime(){
		$id = $_GET['vid'];
		$bool = $this->accessmodel->getWebtopicVideoInfo($id,$this->web_id);
		if($bool){
		 	echo "yes";
		}else{
			 echo "no";
		}
	}	
	
	function getallvideo()
	{
		$redis = get_redis('default');
		$keys = $redis->keys('webmap:video*');
		print_r($keys);
		$arr = array();
		foreach ($keys as $value) {
			$res = $redis->hGetAll($value) ?: array();
			foreach($res as $key)
			{
				$arr[] = $redis->hGetAll("webtopic:{$key}");
			}
		}
		print_r($arr);
	}
	*/
}

