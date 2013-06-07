<?php
/**
 * 视频
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
		$this->config->load('video');
		$this->load->model('videomodel');
		$this->load->model('accessmodel');
		$this->load->helper('video');
	}
	public function index()
	{
		//测试视频service
		//service('Video')->test();exit;
		//$data = api('Timeline')->getTopicByMap(1069489485, 'video',1000002912);
		//print_r($data);exit;
		$this->lists();
	}
	/**
	 * 取得视频列表
	 *
	 * @author wangying
	 * @date   2012/02/27
	 * @access public
	 */
	public function lists()
	{
		if(ACTION_DKCODE){//访问别人
			$action_username = $this->action_user['username'];
			$action_uid = ACTION_UID;
			$main_url = mk_url('main/index/main', array('dkcode'=> ACTION_DKCODE));
			$video_url = mk_url('video/video/index', array('dkcode'=> ACTION_DKCODE));
		}else{//访问自己
			$action_uid = $this->uid;
			$action_username = $this->username;
			$main_url = mk_url('main/index/main', array('dkcode'=> $this->dkcode));
			$video_url = mk_url('video/video/index');
			$this->assign('upload_url',mk_url('video/video/video_upload'));
		}
		$identity = $this->accessmodel->getRelationWithUser($action_uid,$this->uid);
		$is_author = $identity == 5 ? 1 : 0 ;
		$result = $this->videomodel->getVideoLists($identity,$action_uid,$this->uid);
		$num = $result['count_rows']['rows'];
		$star_time = $num ? $result['data'][0]['dateline'] : '' ;
		$tips = $is_author ? '你还没有添加任何视频' : '该用户尚未添加任何视频' ;
		
		$this->assign('main_url',$main_url);
		$this->assign('video_url',$video_url);
		$this->assign('is_author',$is_author);
		$this->assign('avatar_url',get_avatar($action_uid,'s'));
		$this->assign('username',$action_username);
		$this->assign('action_dkcode',ACTION_DKCODE);
		$this->assign('star_time',$star_time);
		$this->assign('tips',$tips);
		$this->assign('num',$num);
		$this->display('video_list');
	}
	/**
	 * AJAX取得更多视频列表
	 *
	 * @author wangying
	 * @date   2012/2/29
	 * @access public
	 * @param integer num 加载展示次数
	 * @param integer start 展示的最后一个视频的上传时间
	 */
	public function ajax_lists()
	{
		$page = (int)$this->input->post('page');
		$dateline = (int)$this->input->post('dateline');
		$object_type = (int)$this->input->post('permissionType');

		$action_uid = ACTION_DKCODE ? ACTION_UID : $this->uid ;
		$identity = $this->accessmodel->getRelationWithUser($action_uid, $this->uid);
		$result = $this->videomodel->getVideoLists($identity,$action_uid,$this->uid,$page,$dateline,$object_type);

		$data = array();
		$data = $result['data'];
		foreach ($data as $key => $value){
			$data[$key]['video_pic'] = get_video_img($value['video_pic'],'_1');
			$data[$key]['time'] =  floor($value['lentime']/60).':'.($value['lentime']%60);
		}
		$rows = $result['count_rows']['rows'];
		unset($result);

		$isend = ( ceil($rows/16) - $page)>0 ? false:true;
		$is_author = $identity == 5 ? 1 : 0 ;

		$data = array('content' => $data,'is_author'=>$is_author,'isend'=>$isend,'rows'=>$rows);
		$this->ajaxReturn($data, $info = '', $status = 1, $type = 'json');
	}
	/**
	 * 访问者访问别人的的视频的权限
	 *
	 * @author wangying
	 * @date   2012/02/24
	 * @access public
	 * @param integer $uid 访问者的用户编号，一般为常量UID
	 * @param integer $vid  视频vid
     * @return boolean
	 */
	public function access_video($action_uid,$uid,$vid)
	{
		if(empty($uid) || empty($vid)){
			return false;
		}
		$return_fields = 'object_type,object_content';
		$result = $this->videomodel->getVideoInfo($vid,$return_fields,1);
        $object_type = $result['object_type'];
		$object_content = $result['object_content'];
		return $this->accessmodel->isAllow($action_uid,$uid,$object_type,$object_content);
	}
	/**
	 * 单个视频展示
	 *
	 * @author wangying
	 * @date   2012/3/02
	 * @access public
     * @param integer vid 视频id
	 */
	public function player_video()
	{
		$vid = (int)$this->input->get('vid');
		if(!$vid) $this->error('对不起,您访问的视频不存在!');
		$return_fields='id,uid,title,video_pic,dateline,discription,object_type,object_content,volume,status';
		$videoinfo = $this->videomodel->getVideoInfo($vid,$return_fields,1);
		if(empty($videoinfo)) $this->error('对不起,您访问的视频不存在!');
		$action_uid = $videoinfo['uid'];
		$this->checkVideoPurview($action_uid);//判断用户是否有访问视频模块的权限
		if($videoinfo['status'] == 4 || $videoinfo['status']== 5) $this->error('对不起,视频在审核中暂时不能访问!');

		$action_user= $this->accessmodel->getUserInfo($action_uid,'uid',array('dkcode','username'));
		if($action_uid == $this->uid){//访问者为自己本人
			$is_author = true;
			$access_ture = true;
			$main_url = mk_url('main/index/main', array('dkcode'=> $this->dkcode));
			$video_url = mk_url('video/video/index');
		}else{
			$is_author = false;
			$access_ture = $this->access_video($action_uid,$this->uid,$vid) ? true : false;
			$main_url = mk_url('main/index/main', array('dkcode'=> $action_user['dkcode']));
			$video_url = mk_url('video/video/index', array('dkcode'=>$action_user['dkcode']));
		}
		if(!$access_ture) $this->error('您没有权利访问!!!');

		//----------------个人对他人动作交互接口 boolee 7/26--------------
		service('Relation')->updateFollowTime($this->uid, $action_uid);
		
		$videoinfo['video_pic'] = get_video_img($videoinfo['video_pic'],'_1');
		$videoinfo['video_player'] = 'vid='.$vid.'&mod=1';
		if($videoinfo['object_type'] == -1){
			$videoinfo['object_uid'] = $videoinfo['object_content'];
			$videoinfo['object_permission'] = $videoinfo['object_content'];
		}else{
			$videoinfo['object_uid'] = -1;
			$videoinfo['object_permission'] = $videoinfo['object_type'];
		}
		//获取视频模块权限
		$sys_purview= service('SystemPurview')->checkApp('video');
		$this->assign('sys_purview', $sys_purview);

		$friendlydate = friendlyDate($videoinfo['dateline']); //动态时间差
		$this->assign('friendlydate',$friendlydate);
		$this->assign('username',$action_user['username']);
		$this->assign('uid',$this->uid);
		$this->assign('login_avatar',get_avatar($this->uid, $size = 's'));
		$this->assign('login_username',$this->username);
		$this->assign('hd_userPageUrl',mk_url('main/index/main', array('dkcode'=>$this->dkcode)));
		$this->assign('vid',$vid);
		$this->assign('volume',$videoinfo['volume']);
		$this->assign('action_uid',$action_uid);
		$this->assign('main_url',$main_url);
		$this->assign('video_url',$video_url);
		$this->assign('is_author',$is_author);
		$this->assign('avatar_url',get_avatar($action_uid, $size = 's'));
		$this->assign('videoinfo',$videoinfo);
		$this->assign('edit_video',mk_url('video/video/edit_video', array('vid'=> $vid,'referer'=>'play')));
		$this->assign('del_video',mk_url('video/video/del_video', array('vid'=> $vid)));
		$this->display('video_info');
		
	}
	/**
	 * 显示视频上传页面
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 */
	function video_upload()
	{
		$authcode_url = authcode('video','',config_item('authcode_key') );
		$this->assign('authcode_url',base64_encode($authcode_url));
		$this->assign('video_upload_url',config_item('video_upload_url'));
		$this->assign('uid',$this->uid);
		$this->assign('username',$this->username);
		$this->assign('main_url',mk_url('main/index/main', array('dkcode'=> $this->dkcode)));
		$this->assign('video_url',mk_url('video/video/index'));
		$this->assign('add_video',mk_url('video/video/add_video'));
		$this->assign('avatar_url',get_avatar($this->uid,'s'));
		$this->display('video_upload.html');
	}
	/**
	 * 保存上传视频信息
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $uid 用户id
	 */
	function add_video(){
		$id        = (int)$this->input->post('vid');
		$vd['uid'] = $this->uid;
		$vd['id']  = $this->get_vid();
		$title = trim($this->input->post('title'));
		$vd['permission']  = $this->input->post('permission');
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
	
		if(mb_strlen($vd['permission']) > 1){
			$vd['object_type']    = -1;
			$vd['object_content'] = $vd['permission'];	
		}elseif(!$vd['permission']){
			$vd['object_type']    = 1;
			$vd['object_content'] = "";
		}else{
			$vd['object_type']    = $vd['permission'];
			$vd['object_content'] = "";
		}
		$list = $this->videomodel->getTmp($id);
		if(!$list) $this->ajaxReturn('', $info = '保存失败!!!', $status = 0, $type = 'json');
		
		$video_data = array();
		$video_data      = unserialize($list['video_data']);
		$vd['type']      = $video_data['type'];
		$vd['lentime']   = $video_data['lentime'];
		$vd['width']     = $video_data['width'];
		$vd['height']    = $video_data['height'];
		$vd['video_src'] = $list['video_src'];
		$vd['video_pic'] = $list['video_pic'];
		unset($video_data);
		$vd['check']     = config_item('check');
		//录入数据库
		$this->videomodel->addVideo($vd['uid'],$vd);
		if($vd['type'] == 'flv'){
			$this->videomodel->delTmp($id);
			if($vd['check']== 0){
				$this->accessmodel->video_credit('add');//增加积分
				$pic_path  = get_img_path( $vd['video_pic']);
				$this->addTimeline($vd,$pic_path);//入住时间线
				if($vd['permission'] == 1){
					//应用区图片设置
					$this->accessmodel->setUserMenuImg($vd['uid'],$vd['video_pic']);
					//添加搜索索引
					$vd['uname'] = $this->username;
					$this->accessmodel->addVideoSearch($vd);
				}
				$data = array('vid' => $vd['id']);
				$this->ajaxReturn($data, $info = '上传成功!!!', $status = 1, $type = 'json');
			}else{
				$data = array('url'=>mk_url('video/video/index'));
				$this->ajaxReturn($data, $info = '视频已上传成功,请等待系统处理及审核!', $status = 3, $type = 'json');
			}
		}else{
			$update_data['status'] = 5;
			$update_data['vid']    = $vd['id'];
			$this->videomodel->updateTmp($update_data,$id);
			$data = array('url'=>mk_url('video/video/index'));
			//是否开启审核
			if($vd['check']== 0){
				$this->ajaxReturn($data, $info = '视频正在转码中……<br/>视频处理结果会以通知的形式告诉您。', $status = 2, $type = 'json');
			}else{
				$this->ajaxReturn($data, $info = '视频已上传成功,请等待系统处理及审核!', $status = 3, $type = 'json');
			}
		}
	}
	/**
	 * 编辑视频页面-显示
	 * @author qqyu
	 * @date   2012/02/23
	 * @param  $vid 视频vid
	 */
	function edit_video(){
		$vid = (int)$this->input->get('vid');
		$play = $this->input->get('referer');
		$play = $play ? 1 : 2 ;
		if(!$vid) $this->error('您访问的视频不存在!');
		$return_fields = 'id,uid,title,discription,object_type,video_pic,object_content';
		$videoinfo = $this->videomodel->getVideoInfo($vid,$return_fields,1);
		if(empty($videoinfo)) $this->error('您访问的视频不存在!');
		if($videoinfo['uid'] != $this->uid) $this->error('不可以编辑别人视频!');
		$videoinfo['video_pic'] =  get_video_img($videoinfo['video_pic'],'_1');//删除调用的视频缩略图
		if($videoinfo['object_type'] == -1){
			$videoinfo['object_uid'] = $videoinfo['object_content'];
			$videoinfo['object_permission'] = $videoinfo['object_content'];
		}else{
			$videoinfo['object_uid'] = -1;
			$videoinfo['object_permission'] = $videoinfo['object_type'];
		}
		//获取视频模块权限
		$sys_purview= service('SystemPurview')->checkApp('video');
		$this->assign('sys_purview', $sys_purview);

		$this->assign('save_url',mk_url('video/video/save_video', array('re'=> $play)));
		$this->assign('main_url',mk_url('main/index/main', array('dkcode'=> $this->dkcode)));
		$this->assign('video_url',mk_url('video/video/index'));
		$this->assign('avatar_url',get_avatar($this->uid, $size = 's'));
		$this->assign('username',$this->username);
		$this->assign('videoinfo',$videoinfo);
		$this->display('video_edit.html');
	}
	/**
	 * 编辑视频页面-保存修改的视频信息
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 */
	function save_video(){
		$vid = (int)$this->input->post('vid');
		$title = trim($this->input->post('title'));//视频标题
		$discription = trim($this->input->post('txtdesc'));//视频说明
		$permission = $this->input->post('permission');//视频权限
		$old_permission = $this->input->post('hd_permission');//该视频之前的权限值

		if($title){
			if(mb_strlen($title,'utf8') > 50) $title = mb_substr($title,0,50,'utf-8');
			$update_data['title'] = check_string($title);
		}else{
			$update_data['title'] = date('Y-m-d H:i:s');
		}
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');
		$update_data['discription'] = check_string($discription);
		if(mb_strlen($permission) > 1){
			$update_data['object_type'] = -1;
			$update_data['object_content'] = $permission;
			$custom = explode(',', $update_data['object_content']);	
			$update_permission = -1;
		}else{
			$update_data['object_type'] = $permission;
			$update_data['object_content'] = '';
			$custom = array();
		}
		$res = $this->videomodel->updateVideo($vid,$update_data,$this->uid);
		if(!$res) $this->error('修改视频信息失败!!!');

		//审核中视频编辑只更新数据库,不做其他操作
		$data = 'id,uid,video_pic,title,discription,dateline,status';
		$videoinfo = $this->videomodel->getVideoInfo($vid,$data,1);
		if($videoinfo['status'] == 4 || $videoinfo['status']== 5 ){
			$this->redirect('video/video/index');eixt;
		}
		//更新时间线
		$bool = $this->accessmodel->getTimelineVideoInfo($vid,$this->uid);
		if(isset($update_permission)) $update_data['permission'] = $update_permission;
		if($bool) $this->accessmodel->updateTimeline($this->uid,$vid,$update_data,$custom);
		//更新搜索索引
		if($old_permission == 1 && $permission != 1){
			$this->accessmodel->searchDeleteVideoId($vid);
		}
		if($permission == 1){
			//$this->accessmodel->restoreVideoInfo($vid);
			$videoinfo['uname'] = $this->username;
			$this->accessmodel->addVideoSearch($videoinfo);
		}

		//更新应用区图片
		$info = $this->videomodel->getNewVideo($this->uid);
		if($info['id'] == $vid){
			$this->accessmodel->setUserMenuImg($this->uid,$info['video_pic']);
		}
		$play = $this->input->get('re');
		if($play == 2){
			$this->redirect('video/video/index'); 
		}
		$this->redirect('video/video/player_video',array('vid'=>$vid));
	}
	/**
	 * 删除视频（彻底删除）
	 * 请求方式：ajax请求
	 * @author qqyu
	 * @date   2012/02/23
  	 * @param  $vid 视频ID
	 */
	function del_video(){
		$vid = (int)$this->input->post('videoID');
		if(!$vid) $this->ajaxReturn('','视频已删除或不存在!', $status = 0, $type = 'json');

		$return_fields = ' uid,video_pic,video_src,object_type,status ';
		$videoinfo = $this->videomodel->getVideoInfo($vid,$return_fields,1);
		if(empty($videoinfo) || ($this->uid != $videoinfo['uid'])) $this->ajaxReturn('', $info = '视频已删除或不存在!', $status = 0, $type = 'json');

		$vd = $this->videomodel->getTowNewVideo($this->uid);
		$res = $this->videomodel->delVideo($vid,$this->uid);
		if(!$res) $this->ajaxReturn('', $info = '删除视频失败!', $status = 0, $type = 'json');
		//审核中视频编辑只更新数据库,不做其他操作
		if( $videoinfo['status'] == 4 || $videoinfo['status']== 5){
			$this->del_fastdfs_flies($videoinfo['video_pic'], $videoinfo['video_src']);
			$this->ajaxReturn('', $info = '删除视频成功!', $status = 1, $type = 'json');exit;
		}
		$this->del_fastdfs_flies($videoinfo['video_pic'], $videoinfo['video_src']);
		$this->accessmodel->video_credit('del');
		//更新应用区图片
		if(count($vd) > 0 && $vd[0]['id'] == $vid){
			if(count($vd) == 2 && $vd[1]['video_pic']!= ''){
				$vd['video_pic'] = $vd[1]['video_pic'];
			}else{
				$vd['video_pic'] = '';
			}	
		}else{
			$vd['video_pic']= $vd[0]['video_pic'];
		}
		$this->accessmodel->setUserMenuImg($this->uid,$vd['video_pic']);
		//删除时间线
		$bool = $this->accessmodel->getTimelineVideoInfo($vid,$this->uid);
		if($bool) $this->accessmodel->delTimeline($vid,$this->uid);

		if($videoinfo['object_type'] == 1){
			 $this->accessmodel->searchDeleteVideoId($vid);
		}
		$this->ajaxReturn('', $info = '删除视频成功!', $status = 1, $type = 'json');
	}
	/**
	 * 删除fastdfs上图片和视频文件
	 * @author qqyu
	 * @param unknown_type $video_pic
	 * @param unknown_type $video_src
	 */
	function del_fastdfs_flies($video_pic,$video_src){
		if($video_pic){//删除fastdfs上的两张图片
			if($video_pic != 'group2/M00/00/00/del.jpg'){
				$this->load->fastdfs('album','', 'fastdfs');
				$pic_info = explode('/',$video_pic,2);
				$this->fastdfs->deleteFile($pic_info[0], $pic_info[1]);
				$this->fastdfs->deleteFile($pic_info[0], $pic_info[1],'_1');
				$this->fastdfs->deleteFile($pic_info[0], $pic_info[1],'_ico');			
			}
		}
		if($video_src){//删除fastdfs上视频文件
			if($video_src != 'video10/M00/00/00/dell.flv'){
				$this->load->fastdfs('video', '', 'fastdfs');
				$video_info = explode('/',$video_src,2);
				$this->fastdfs->deleteFile($video_info[0], $video_info[1]);
			}
		}
	}
	/**
	 * 显示录制页面
	 * @author qqyu
	 * @date   2012/04/09
	 */
	function list_cam(){
		$this->assign('recordurl',config_item('recordurl')); 
		$this->assign('uid',date('YmdHis').'_'.$this->uid);
		$this->assign('username',$this->username);
		$this->assign('main_url',mk_url('main/index/main', array('dkcode'=> $this->dkcode)));
		$this->assign('video_url',mk_url('video/video/index'));
		$this->assign('avatar_url',get_avatar($this->uid,'s'));
		$this->display('video_cam.html');
	}
	
	/**
	 * 录制视频页面->发布视频
	 * ajax
	 * @author qqyu
	 * @date   2012/04/09
	 */
	function save_makevideo(){
		set_time_limit(0);
		$vd['uid'] = $this->uid;
		if(!$vd['uid']){
			$this->ajaxReturn($data='', $info = '请先登录!', 0, $type = 'json');
		}
		//接收表单值
		$title = trim($this->input->post('title'));
		$discription = trim($this->input->post('txtdesc'));
		$name = $this->input->post('hd_v_name');
		if(!$name) $this->ajaxReturn($data='', $info = '录制视频文件不存在', 0, $type = 'json');

		if($title){
			if(mb_strlen($title,'utf8') > 50) $discription = mb_substr($title,0,50,'utf-8');
			$vd['title'] = check_string($title);
		}else{
			$vd['title'] = date('Y-m-d H:i:s');
		}
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');
		$vd['discription'] = check_string($discription);
		
		
		$vd['id'] = $this->get_vid();
		$url = authcode('video','ENCODE',config_item('authcode_key'));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, config_item('transcod_url').'record.php?appkey='.base64_encode($url).'&file='.$name.'&mid=1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		$sFile = curl_exec($ch);
		curl_close($ch);
		$str = json_decode($sFile);
		if(!$sFile || $str->status == 0){
			$this->ajaxReturn($data='', $info = $str->info, 0, $type = 'json');
		}
		$vd['type'] = 'flv';
		$id = $str->data->id;
		$vd['video_src'] = $str->data->video_src;
		$vd['video_pic'] = $str->data->video_pic;
		$vd['lentime'] = ceil($str->data->lentime);
		$vd['width'] = $str->data->width;
		$vd['height'] = $str->data->height;
		$vd['permission'] = $this->input->post('permission');
		$vd['dateline'] = time();

		if(mb_strlen($vd['permission']) > 1){
			$vd['object_type'] = -1;
			$vd['object_content'] = $vd['permission'];
		}elseif(!$vd['permission']){
			$vd['object_type'] = 1;
			$vd['object_content'] = '';
		}else{
			$vd['object_type'] = $vd['permission'];
			$vd['object_content'] = '';
		}	
		$vd['check']  = config_item('check');
		//录入数据库
		$this->videomodel->addVideo($vd['uid'],$vd);
		$update_data['vid'] = $vd['id'];
		$this->videomodel->updateTmp($update_data,$id);
		$data = array('url'=>mk_url('video/video/index'));
		if($vd['check']== 0){
			$this->videomodel->delTmp($id);
			$pic_path = get_img_path($vd['video_pic']);
			$this->addTimeline($vd,$pic_path);
			if($vd['permission'] == 1){
				$this->accessmodel->setUserMenuImg($vd['uid'],$vd['video_pic']);
				$vd['uname'] = $this->username;
				$this->accessmodel->addVideoSearch($vd);
			}
			//积分
			$this->accessmodel->video_credit('add');
			$this->ajaxReturn($data, $info = '视频发布成功!', 1, $type = 'json');
		}else{
			$this->ajaxReturn($data, $info = '视频已上传成功,请等待系统处理及审核!', 1, $type = 'json');
		}
	}

    /**
     * player_video 页面视频权限设置
     * ajax
     * @author wangying
     * @param string $method
     * @param array $data
     * @param string $permision
     * @return string
     */
    public function set_permission()
    {
    	if(!$this->uid) $this->ajaxReturn('', $info = '请先登录!', $status = 0, $type = 'json');
		$vid = (int)$this->input->post('object_id');
		$permission = $this->input->post('permission');

		if(mb_strlen($permission) > 1){
			$object_type = -1;
			$object_content = $permission;
			$custom = explode(',',$object_content);		
		}else{
			$object_type = $permission;
			$object_content = '';
			$custom = array();
		}
		$update_data = array('object_type'=>$object_type,'object_content'=>$object_content);
		$oldinfo = $this->videomodel->getVideoInfo($vid,$select = 'object_type',1);
		$res = $this->videomodel->updateVideo($vid,$update_data,$this->uid);
		if($res){
			if($permission == 1){
				//添加搜索索引
				$this->accessmodel->restoreVideoInfo($vid);
				$info = $this->videomodel->getNewVideo($this->uid);
				if(!empty($info) && $info['id'] == $vid){
					$this->accessmodel->setUserMenuImg($this->uid,$info['video_pic']);
				}
			}else{
				if($oldinfo['object_type'] == 1){
					$this->accessmodel->searchDeleteVideoId($vid);
				}
			}
			//更新时间线
			$bool = $this->accessmodel->getTimelineVideoInfo($vid,$this->uid);
			if($bool){
				$data = array('permission' => $object_type);
				$this->accessmodel->updateTimeline($this->uid,$vid,$data,$custom);
			}
			$this->ajaxReturn('', $info = '权限设置成功!', $status = 1, $type = 'json');
		}else{
			$this->ajaxReturn('', $info = '权限设置失败!', $status = 0, $type = 'json');
		}
	}
	
	/**
	 * 入驻时间线
	 * @author qqyu
	 * @param  array $vd
	 * @param  string $uid
	 */	
	function addTimeline($vd,$video_pic){	
		$data = array(	
			'type' => 'video',
			'fid' => $vd['id'],
			'uid' => $vd['uid'],
			'dkcode' => $this->dkcode,	
			'uname' => $this->username,
			'title' => $vd['title'],
			'content' => $vd['discription'],
		 	'width' => $vd['width'],
    		'height' => $vd['height'],
			'imgurl' => $video_pic,
			'permission' => $vd['object_type'],
			'dateline' => $vd['dateline']
		);
		if(!empty($vd['object_content'])){
			$permission = explode(',', $vd['object_content']);
		}else{
			$permission = array();
		}
		$this->accessmodel->setTimeline($data,$permission);
		return true;
	}
	/**
	 * 批量全部更新时间线和搜索索引(慎重)
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @return string
	 */
	public function update(){	
		$a = isset($_GET['a']) ? $_GET['a'] : '';
		$id = isset($_GET['id']) ? $_GET['id'] : '';
		if($a == 'a' && $id ){
			$data = $this->videomodel->test($id);
			$value = $data[0];
			if(isset($data[1])){
				$next_id = $data[1]['id'];
			}else{
				$next_id ='';
			}	
			//修改时间线			
			$imgurl = $value['video_pic'];
			$bool = $this->accessmodel->getTimelineVideoInfo($value['id'],$value['uid']);
			if($bool) $this->accessmodel->resetTimeline($value['uid'],$value['id'],$imgurl,$permission=array());							
			//添加新索引
			if($value['object_type'] == 1){
				// 删除旧索引 
				$this->accessmodel->searchDeleteVideoId($value['id']);
				//添加新索引
				$userinfo = $this->accessmodel->getUserInfo($value['uid'],'uid',array('username'));
				$video_info=array(
					'id'     => $value['id'],
					'uid'    => $value['uid'],
					'uname'  => $userinfo['username'],		
					'title'  => $value['title'],
					'dateline' => $value['dateline'],
					'video_pic'   => $value['video_pic'],
				    'discription' => $value['discription']
				);
				$this->accessmodel->addVideoSearch($video_info);
			}
			if(!$next_id) die('更新时间线和索引成功!');
			$url = mk_url('video/video/update',array('a'=>'a','id'=>$next_id));
			echo '<script>window.location.href="'.$url.'";</script>';
		}
	}
}

