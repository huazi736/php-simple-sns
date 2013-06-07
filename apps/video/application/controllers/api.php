<?php
/**
 * 视频接口
 * @author        qqyu
 * @date          2012/02/21
 * @version       1.2
 * @description   应用模块上传视频接口
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Api extends CI_Controller {

	function __construct(){
		parent::__construct();
		$this->config->load('video');
		$this->load->model('videomodel');
		$this->load->model('accessmodel');
		$this->load->helper('video');
	}
	/**
	 * 转码后请求修改数据库和添加时间线修改应用区
	 * @author qqyu
	 * @date   2012/02/23
	 * @param  $vid 视频vid
	 */
	function transcode_result_api(){

		$url = $_GET['url'];
		$url = authcode(base64_decode($url),'DECODE', config_item('authcode_key'));
		$arr = explode('|',$url);
		$vid = str_replace('vid=','',$arr[0]);
		$module  = str_replace('module=','',$arr[1]);
		$status  = str_replace('status=','',$arr[2]);
		$vd = $this->videomodel->getVideoInfo($vid,' * ',$module);		
		if(!$vd){
			return false;
		}
		if($status == 0){
			$this->videomodel->delVideo($vid);
			$url = mk_url('video/video/video_upload');
			//通知
			$this->accessmodel->api_ucenter_notice_addNotice(1,$vd['uid'], $vd['uid'], 'video', 'video_upload_false', array('name' => $vd['title'],'url' => $url));
		}else{
			//$name = getUserInfo($vd['uid']);
			$userinfo = $this->accessmodel->getUserInfo($vd['uid'],'uid',array('username','dkcode'));
			define('WEB_DKCODE' , $userinfo['dkcode'] );
			define('WEB_UNAME' , $userinfo['username'] );
	
			$pic_path  = get_img_path($vd['video_pic']);
			if(!empty($vd['timestr'])){
				$data['dateline'] = preg_replace_callback('/(?P<year>\d{4})(-?)(?P<mon>\d{0,2})(-?)(?P<day>\d{0,2})/', function ($match)
				{
					!$match['mon'] && $match['mon'] = 1;
					!$match['day'] && $match['day'] = 1;
					return mktime(date('H',time()),date('i',time()),date('s',time()),$match['mon'],$match['day'],$match['year']);
				}, $vd['timestr']?:date('Y-n-j',time()));
				$vd['dateline'] = $data['dateline'];
				$vd['from'] = 1;
			}
			$search = $this->addTimeline($vd,$pic_path);	
			//积分
			$this->accessmodel->video_credit('add',$vd['uid']);		
			if($vd['object_type'] == 1){
				//应用区图片设置
				$this->accessmodel->setUserMenuImg($vd['uid'],$vd['video_pic']);
				//索引	
				if(!empty($search)){
					service('RelationIndexSearch')->addOrUpdateStatusInfo(json_encode($search));
					//$vd['uname'] = $userinfo['username'];
					//$this->accessmodel->addVideoSearch($vd);
				}
			}
			$url = mk_url('video/video/player_video',array('vid'=> $vid));
			//通知
			$this->accessmodel->api_ucenter_notice_addNotice(1, $vd['uid'], $vd['uid'], 'video', 'video_upload_true', array('name' => $vd['title'],'url' => $url));
			
		}
	}

	/**
	 * 入驻时间线
	 * @author qqyu
	 * @param  array $vd
	 * @param  string $uid
	 */
	function addTimeline($vd,$video_pic){
		$data['type'] = 'video';
		$data['fid'] = $vd['id'];
		$data['uid'] = $vd['uid'];
		$data['dkcode'] = WEB_DKCODE;
		$data['uname'] = WEB_UNAME;
		$data['title'] = $vd['title'];
		$data['content'] = $vd['discription'];	
		$data['imgurl'] = $video_pic;
		$data['width'] = $vd['width'];
		$data['height'] = $vd['height'];
		$data['permission'] = $vd['object_type'];
		if(isset($vd['from']) && $vd['from'] == 1){
			$data['from'] = $vd['from'];
			$data['ctime'] = $vd['dateline'];
			$data['dateline'] = time();
		}else{
			$data['dateline'] = $vd['dateline'];
		}
		if($vd['object_type'] = -1){
			$permission = explode(',', $vd['object_content']);
		}else{
			$permission = array();
		}
		$this->accessmodel->setTimeline($data,$permission);
		return true;
	}
	/**
	 * 视频播放器播放时请求数据
	 * @author wangying
	 //die(json_encode(array('status' => 2,'data' => array('rtmp' => 'rtmp://192.168.12.242/oflaDemo|data/02/1C/cu6CtzSA0waN9-_Xz0638.flv','bgpic' => 'http://192.168.12.242/video10/M00/02/16/wKgM8k-9x839sg_gAAAG5dvDaJE245_y.jpg','xmlurl'=>'path.xml'))));
	 */
	public function playerquest(){
		$vid = (int)$this->input->get('vid');
		$module = (int)$this->input->get('mod');
		$uid = (int)$this->input->get('uid');
		if( $vid==0 || $module==0){
			die(json_encode(array('status' => 2,'info' => '视频不存在或已删除!')));//视频不存在
		}

		if($module == 1){ //uservideo
			$data = 'uid,video_src,video_pic,object_type,object_content,width,height,status';
			$type = 'video';
		}elseif($module == 2){ //web_video
			$data = 'video_src,video_pic,width,height,status';
			$type = 'webvideo';
		}else{ //other_video
			$data = '*';
			$module = 3;
		}
		$videoinfo = array();
		$videoinfo = $this->videomodel->getVideoInfo($vid,$data,$module);
		if(empty($videoinfo)){
			die(json_encode(array('status' => 2,'info' => '视频不存在或已删除!')));//视频不存在
		}
		
		//关注操作时间接口start 李波2012/ 7/4
		if( $module==1 &&  $uid != $videoinfo['uid']){
    		service('Relation')->updateFollowTime($uid, $videoinfo['uid']);
		}

		$videoinfo['video_pic'] = get_video_img($videoinfo['video_pic']);
		$xmlurl = MISC_ROOT.'flash/video/path.xml';//皮肤配置
		//状态：1 正常 、 2 伪删除 、3 网页删除、4 待审核、5 待转码、6 转码失败
		$status = $videoinfo['status'];
		if($module == 2 || $module == 3){
			if($status == 1){
				if($module == 2){
					//视频播放次数+1
					$this->videomodel->addVideoVolume($type,$vid);
				}
				$video_src_arr = explode('/',$videoinfo['video_src'],3);
				$videoinfo['video_src'] = config_item('video_src_domain').'oflaDemo|data/'.$video_src_arr[2];
				die(json_encode(array('status' => 1,'data' => array('rtmp' => $videoinfo['video_src'],'bgpic' => $videoinfo['video_pic'],'width'=>$videoinfo['width'],'height'=>$videoinfo['height'],'xmlurl'=>$xmlurl))));
			}elseif($status == 4){
				die(json_encode(array('status' => 2,'info' => '正在审核中!')));
			}elseif($status == 5){
				die(json_encode(array('status' => 2,'info' => '正在转码中!')));
			}else{
				die(json_encode(array('status' => 2,'info' => '视频不存在或已删除!')));
			}
			
		}else{
			if(!$uid){ //uid 不存在
				if($videoinfo['object_type'] == 1){
					die(json_encode(array('status' => 1,'data' => array('rtmp' => $videoinfo['video_src'],'bgpic' => $videoinfo['video_pic'],'xmlurl'=>$xmlurl))));
				}else{
					die(json_encode(array('status' => 2,'info' => '暂时没有权限查看!')));//权限不够无法看
				}
			}
			$bool = $this->accessmodel->isAllow($uid,$videoinfo['uid'],$videoinfo['object_type'],$videoinfo['object_content']);
			if($bool){
				if($status == 1){
					//视频播放次数+1
					$this->videomodel->addVideoVolume($type,$vid);
					$video_src_arr = explode('/',$videoinfo['video_src'],3);
					$videoinfo['video_src'] = config_item('video_src_domain').'oflaDemo|data/'.$video_src_arr[2];
					die(json_encode(array('status' => 1,'data' => array('rtmp' => $videoinfo['video_src'],'bgpic' => $videoinfo['video_pic'],'width'=>$videoinfo['width'],'height'=>$videoinfo['height'],'xmlurl'=>$xmlurl))));
				}elseif($status == 4){
					die(json_encode(array('status' => 2,'info' => '正在审核中!')));
				}elseif($status == 5){
					die(json_encode(array('status' => 2,'info' => '正在转码中!')));
				}else{
					die(json_encode(array('status' => 2,'info' => '视频不存在或已删除!')));
				}
			}else{
				die(json_encode(array('status' => 2,'info' => '暂时没有权限查看!')));
			}
		}
	}
	
	/**
	 * 保证10位vid唯一
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @return string
	 */	
	 public function get_vid(){	 
	 	//产生10位vid
		$vid = $this->rank_vid();	
		$num = $this->videomodel->isVid($vid,'2');
		while ($num == 1){
			$vid = $this->rank_vid();
			$num = $this->videomodel->isVid($vid,'2');
		}
		return $vid;
	 }
	/**
	 * 产生10位随机vvid
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @return string
	 */	
	public function rank_vid() {
        $chars = mt_rand(1000000000,9999999999);
        $head = '1';
        $vvid  = substr($chars, 2, 5);
        $vvid .= substr($chars, 4, 2);
        $vvid .= substr($chars, 6, 2);
        $vvid = $head.$vvid;
        return $vvid;
	 }
	
}