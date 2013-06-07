<?php
/**
 * 首页视频
 * @author        qqyu
 * @date          2012/02/21
 * @version       1.2
 * @description   首页视频接口相关功能
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Videoapi extends MY_Controller {

	function __construct(){
		parent::__construct();
		$this->config->load('video');
		$this->load->model('videomodel');
		$this->load->model('accessmodel');
		$this->load->helper('video');
	}

	/**
	 * (第三方)个人时间线视频上传->发表视频信息
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $uid 用户id
	 */
	function add_video(){
		$id                = (int)$this->input->get('vid');
		if(!$id)  $this->ajaxReturn('', $info = '保存失败!!!', $status = 0, $type = 'jsonp');
		$vd['uid']         = $this->uid;
		$vd['id']          = $this->get_vid();
		$vd['title']       = date('Y-m-d H:i');
		$discription       = trim($this->input->get('content'));
		$vd['timestr']     = $this->input->get('timestr')?:date('Y-n-j',time());
		$vd['permission']  = $this->input->get('permission');
		$update_data['vid']     = $vd['id'];
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');
		$vd['discription'] = check_string($discription);
		$vd['dateline']    = time();
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
		//去tmp表获取视频信息
		$list = $this->videomodel->getTmp($id);
		if(!$list) $this->ajaxReturn('', $info = '保存失败!!!', $status = 0, $type = 'jsonp');
		$video_data = array();
		$video_data      = unserialize($list['video_data']);
		$vd['type']      = $video_data['type'];
		$vd['lentime']   = $video_data['lentime'];
		$vd['width']     = $video_data['width'];
		$vd['height']    = $video_data['height'];	
		$vd['video_pic'] = $list['video_pic'];
		$vd['video_src'] = $list['video_src'];
		$vd['check']     = config_item('check');
		unset($video_data);
		//录入数据库
		$this->videomodel->addVideo($vd['uid'],$vd);
		if($vd['type'] == 'flv'){
			$this->videomodel->delTmp($id);
			if($vd['check']== 0){	
				$pic_path  = get_img_path($vd['video_pic']);
				if($vd['permission'] == 1){
					$this->accessmodel->setUserMenuImg($vd['uid'],$vd['video_pic']);
				}
				$data = array('vid' => $vd['id'],'imgurl' => $pic_path,'width' => $vd['width'],'height' => $vd['height']);
				$this->ajaxReturn($data, $info = '上传成功!', $status = 1, $type = 'jsonp');
			}else{
				$this->ajaxReturn('', $info = '视频已上传成功,请等待系统处理及审核！', $status = 3, $type = 'jsonp');
			}
		}else{
			$update_data['status']  = 5;
			$this->videomodel->updateTmp($update_data,$id);
			//是否开启审核
			if($vd['check']== 0){
				$this->ajaxReturn('', $info = '视频正在转码中……<br/>视频处理结果会以通知的形式告诉您。', $status = 2, $type = 'jsonp');
			}else{
				$this->ajaxReturn('', $info = '视频已上传成功,请等待系统处理及审核!', $status = 3, $type = 'jsonp');
			}
		}
	}
	/**
	 * 个人时间线视频->录制视频
	 * @author qqyu
	 * @date   2012/04/09
	 */
	function save_makevideo(){
		set_time_limit(0);
		$vd['uid'] = $this->uid;
		if(!$vd['uid']){
			$this->ajaxReturn($data='', $info = '请先登录!', 0, $type = 'jsonp');
		}
		$discription = trim($this->input->get('content'));
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');
		$vd['discription'] = check_string($discription);
		$vd['id'] = $this->get_vid();
		$name = $this->input->get('hd_v_name');
		$url = authcode('video','ENCODE',config_item('authcode_key'));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,  config_item('transcod_url').'record.php?appkey='.base64_encode($url).'&file='.$name.'&mid=1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		$sFile = curl_exec($ch);
		curl_close($ch);
		$str = json_decode($sFile);
		if(!$sFile && $str->status == 0){
			$this->ajaxReturn($data='', $info = $str->info, 0, $type = 'jsonp');
		}
		$id = $str->data->id;
		$vd['type']  = 'flv';
		$vd['title'] = date('Y-m-d H:i:s');
		$vd['video_src'] = $str->data->video_src;
		$vd['video_pic'] = $str->data->video_pic;
		$vd['lentime']   = ceil($str->data->lentime);
		$vd['width']     = $str->data->width;
		$vd['height']    = $str->data->height;
		$vd['timestr']   = $this->input->get('timestr')?:date('Y-n-j',time());
		$vd['permission']  = $this->input->get('permission');
		$vd['dateline']    = time();

		if(mb_strlen($vd['permission']) > 1){
			$vd['object_type'] = -1;
			$vd['object_content'] = $vd['permission'];
		}elseif(!$vd['permission']){
			$vd['object_type'] = 1;
			$vd['object_content'] = "";
		}else{
			$vd['object_type'] = $vd['permission'];
			$vd['object_content'] = "";
		}
		$vd['check'] = config_item('check');
		$this->videomodel->addVideo($vd['uid'],$vd);	
		$this->videomodel->delTmp($id);
		if($vd['check']== 0){
			$pic_path  = get_img_path($vd['video_pic']);
			$url = mk_url('video/video/player_video', array('vid'=> $vd['id']));
			if($vd['permission'] == 1){
				$this->accessmodel->setUserMenuImg($vd['uid'],$vd['video_pic']);
				//$vd['uname'] = $this->username;
				//$this->accessmodel->addVideoSearch($vd);
			}
			$data = array('vid' => $vd['id'],'imgurl' => $pic_path,'width' => $vd['width'],'height' => $vd['height']);
			$this->ajaxReturn($data, $info = '上传成功!', 1, $type = 'jsonp');
		}else{
			$this->ajaxReturn($data='', $info = '视频已上传成功,请等待系统 处理及审核!', 3, $type = 'jsonp');
		}
	}

	/**
	 * (第三方)群组->视频模块接口函数、保存第三方视频信息
	 * @author qqy
	 */
	function add_other_video_api(){
		$tmp_id = isset($_GET['vid']) ? $_GET['vid'] : '';
		$mid = isset($_GET['mid']) ? $_GET['mid'] : '';
		
		//去tmp_video表中取得临时数据
		if(!$tmp_id || !$mid){
			$this->ajaxReturn('', $info = 'vid或者mid为空!', $status = 2, $type = 'jsonp');
		}
		$tmp = $this->videomodel->getTmp($tmp_id);
		if(!$tmp || ($tmp['mid'] != $mid)){
			$this->ajaxReturn('', $info = '视频获取失败!', $status = 2, $type = 'jsonp');
		}
		$str = unserialize($tmp['video_data']);
		$tmp['width'] = $str['width'];
		$tmp['height'] = $str['height'];
		$tmp['lentime'] = $str['lentime']; 
		$tmp['type'] = $str['type'];
		$tmp['id']  = $this->get_vid();
		$tmp['check'] = config_item('check');
		$tmp['check'] = 0;
		//录入other_video
		$re = $this->videomodel->addOtherVideo($tmp);
		if($re == false ){
			$this->ajaxReturn('', $info = '视频保存失败!', $status = 2, $type = 'jsonp');
		}	
		$pic_path = get_img_path($tmp['video_pic']);
		$data = array('vid'=>$tmp['id'],'pic'=>$pic_path);	
		if($tmp['type']=='flv'){
			$this->videomodel->delTmp($tmp_id);	
			if($tmp['check'] == 1){
				$this->ajaxReturn($data, $info = '视频保存成功，等待系统处理及审核!', $status = 3, $type = 'jsonp');
			}else{	
				$this->ajaxReturn($data, $info = '视频保存成功!', $status = 1, $type = 'jsonp');
			}	
		}else{
			$update_data['status'] = 5;
			$update_data['vid']    = $tmp['id'];
			$this->videomodel->updateTmp($update_data,$tmp_id);
			if($tmp['check'] == 1){
				$this->ajaxReturn($data, $info = '视频保存成功，等待系统处理及审核!', $status = 3, $type = 'jsonp');
			}else{
				$this->ajaxReturn($data, $info = '视频保存成功!等待系统转码!', $status = 1, $type = 'jsonp');
			}	
		}
	}
	/**
	 * (第三方)群组->录制视频接口
	 * Enter description here ...
	 */
	function other_makevideo(){
		set_time_limit(0);
		//$name = $this->input->get('hd_v_name');
		$name = isset($_GET['hd_v_name']) ? $_GET['hd_v_name'] : '';
		$vd['mid'] = isset($_GET['mid']) ? $_GET['mid'] : '';
		
		$vd['uid'] = $this->uid;
		if(!$vd['uid']){
			$this->ajaxReturn($data='', $info = '请先登录!', 0, $type = 'jsonp');
		}
		$vd['id'] = $this->get_vid();	
		$url = authcode('video','ENCODE',config_item('authcode_key'));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,  config_item('transcod_url').'record.php?appkey='.base64_encode($url).'&file='.$name.'&mid='.$vd['mid']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		$sFile = curl_exec($ch);
		curl_close($ch);
		$str = json_decode($sFile);
		if(!$sFile && $str->status == 0){
			$this->ajaxReturn($data='', $info = $str->info, 0, $type = 'jsonp');
		}
		$id = $str->data->id;
		$vd['type']  = 'flv';
		$vd['video_src'] = $str->data->video_src;
		$vd['video_pic'] = $str->data->video_pic;
		$vd['lentime']   = ceil($str->data->lentime);
		$vd['width']     = $str->data->width;
		$vd['height']    = $str->data->height;
		$vd['dateline']  = time();
		$vd['check']     = config_item('check');
		//录入数据库
		$this->videomodel->addOtherVideo($vd);
	
		$this->videomodel->delTmp($id);
		
		$pic_path = get_img_path($vd['video_pic']);
		$data = array('vid'=>$vd['id'],'pic'=>$pic_path);	
		//是否开启审核
		if($vd['check']== 0){
			$this->ajaxReturn($data, $info = '上传成功!', 1, $type = 'jsonp');
		}else{
			$this->ajaxReturn($data, $info = '视频已上传成功,请等待系统 处理及审核!', 3, $type = 'jsonp');
		}
	}
	/**
	 * (第三方)群组->删除视频接口函数
	 * @author qqy
	 */
	function del_other_video_api(){
		$vid = $_GET['vid'];   
		$mid = $_GET['mid'];
		//getOtherVideo
		$info = $this->videomodel->getOtherVideoInfo($vid);
		if(!$info){
			$this->ajaxReturn('', $info = 'video isnot undefined!', $status = 2, $type = 'jsonp');
		}
		//delOtherVideo
		$re = $this->videomodel->delOtherVideo($vid);
		if($re== false){
			$this->ajaxReturn('', $info = 'video isnot access!', $status = 2, $type = 'jsonp');
		}
		$this->ajaxReturn('', $info = 'access!', $status = 1, $type = 'jsonp');
		//del fastdfs
		/*
		$this->load->library('Fdfs', config_item('fastdfs'), 'fdfs');
		$pic_info = explode('/',$info['video_pic'],2);
		$video_info = explode('/',$info['video_src'],2);
		$this->fdfs->delete_filename($pic_info[0], $pic_info[1]);
		*/
	}
	
	
	/**
	 * (第三方)个人时间线->应用区视频数量
	 * @author wangying
	 * @date   2012/4/18
	 * @access public
	 * @return string
	 */
	 public function timeline_video_num(){
		$uid = (int)$this->input->get('uid');
		$action_uid = (int)$this->input->get('action_uid');
		if(($uid==$this->uid) && $action_uid){
			$identity = $this->accessmodel->getRelationWithUser($action_uid, $uid);
			$num = $this->videomodel->getTimelineVideoNum($identity,$action_uid,$uid);
			$this->ajaxReturn(array('num'=>$num), '', $status = 1, $type = 'jsonp');
		}else{
			$this->ajaxReturn(array('num'=>0), '', $status = 0, $type = 'jsonp');
		}
	 }
	/**
	 * (第三方)个人时间线->视频分享解析
	 * @author wangying
	 * @date   2012/6/27
	 * @access public
	 * @return string
	 */
	 public function Video_share_link(){
		$url = $this->input->get('url');
		if($url){
			/*数据库操作 填充点*/
			//$this->load->library('videosharelink');

			//print_r(APPPATH);exit;

			include(APPPATH."/libraries/VideoShareLink.php");
			$videosharelink = new VideoShareLink();
			$data = $videosharelink->parse($url,false);
			if($data){
				$this->ajaxReturn($data, 'success!', $status = 1, $type = 'jsonp');
			}
			$this->ajaxReturn('', 'failure!', $status = 0, $type = 'jsonp');
		}else{
			$this->ajaxReturn('', 'failure!', $status = 0, $type = 'jsonp');
		}
	 }
}