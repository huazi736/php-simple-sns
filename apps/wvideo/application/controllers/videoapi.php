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
		$this->load->model('videomodel');
		$this->load->model('accessmodel');
		$this->config->load('video');
		$this->load->helper('wvideo');
	}
	
	/**
	 * 保存上传视频信息
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $uid 用户id
	 */
	function add_video(){	
		$id = (int)$this->input->get('vid');
		$vd['id'] = $this->get_vid();
		$vd['uid'] = $this->uid;
		$vd['title'] = date('Y-m-d H:i:s');
		$discription = trim($this->input->get('content'));
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');		
		$vd['discription'] = check_string($discription);
		$vd['dateline']    =  time();
		$vd['web_id']      =  WEB_ID;
		$list = $this->videomodel->getTmp($id);
		if(!$list){
			$this->ajaxReturn($data='', $info = '保存失败!!!', 0, $type = 'jsonp');
		}
		$video_data      = unserialize($list['video_data']);
		$vd['type']      = $video_data['type'];
		$vd['lentime']   = $video_data['lentime'];
		$vd['width']     = $video_data['width'];
		$vd['height']    = $video_data['height'];
		$vd['video_src'] = $list['video_src'];
		$vd['video_pic'] = $list['video_pic'];
		$vd['check']     = config_item('check');
		
		$bc       = (int)$this->input->get('bc');
		$timestr  = $this->input->get('timestr')?:date('Y-n-j',time());	
		$timedesc = htmlspecialchars(trim($this->input->get('timedesc')));
		$vd['timestr'] = $bc.'|'.$timestr.'|'.$timedesc;
		//录入数据库
		$this->videomodel->addVideo($vd['uid'],$vd);
		if($vd['type'] == 'flv'){
			$this->videomodel->delTmp($id);	
			//是否开启审核		
			if($vd['check']== 0){							
				$pic_path   = get_img_path($vd['video_pic']);
				$this->accessmodel->apisetUserMenuImg(WEB_ID,$vd['video_pic']);  				
				$data =array('vid' => $vd['id'],'imgurl' => $pic_path,'width' => $vd['width'],'height' => $vd['height']);
				$this->ajaxReturn($data, $info = '上传成功!', 1, $type = 'jsonp');
			}else{				
				$this->ajaxReturn($data='', $info = '视频已上传成功,请等待系统处理及审核!', 3, $type = 'jsonp');
			}	
		}else{				
			$update_data['status']  = 5;
			$update_data['vid']     = $vd['id'];
			$this->videomodel->updateTmp($update_data,$id);
			//是否开启审核		
			if($vd['check']== 0){	
				$this->ajaxReturn($data='', $info = '视频正在转码中……<br/>视频处理结果会以通知的形式告诉您。', 2, $type = 'jsonp');
			}else{
				$this->ajaxReturn($data='', $info = '视频已上传成功,请等待系统处理及审核!', 3, $type = 'jsonp');
			}
		}		
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
			$this->ajaxReturn($data='', $info = '请先登录!', 0, $type = 'jsonp');
		}
		//接收表单值
		$vd['title'] = date('Y-m-d H:i:s');
		$discription = trim($this->input->get('content'));	
		if(mb_strlen($discription,'utf8') > 140) $discription = mb_substr($discription,0,140,'utf-8');	
		$vd['discription'] = check_string($discription);
		$vd['id'] = $this->get_vid();
		$name = $this->input->get('hd_v_name');	
		$url = authcode('video','ENCODE',config_item('authcode_key'));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,  config_item('transcod_url').'record.php?appkey='.base64_encode($url).'&file='.$name.'&mid=2');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		$sFile = curl_exec($ch);
		curl_close($ch);
		$str = json_decode($sFile);	
		if(!$sFile || $str->status == 0){
			$this->ajaxReturn($data='', $info = $str->info, 0, $type = 'jsonp');
		}
		$id = $str->data->id;	
		$vd['web_id'] = WEB_ID;
		$vd['type'] = 'flv';
		$vd['video_src'] = $str->data->video_src;	
		$vd['video_pic'] = $str->data->video_pic;
		$vd['lentime'] = ceil($str->data->lentime);	
		$vd['width'] = $str->data->width;
		$vd['height'] = $str->data->height;	
		$vd['dateline'] = time();	
		//接收时间线值	
		$bc       = (int)$this->input->get('bc');
		$timestr  = $this->input->get('timestr')?:date('Y-n-j',time());
		$timedesc = htmlspecialchars(trim($this->input->get('timedesc')));
		$vd['timestr'] = $bc.'|'.$timestr.'|'.$timedesc;				
		$vd['check']     = config_item('check');						
		//录入数据库	
		$this->videomodel->addVideo($vd['uid'],$vd);			
		$this->videomodel->delTmp($id);
		if($vd['check']== 0){				
			$pic_path  = get_img_path($vd['video_pic']);
			$url = mk_url('wvideo/video/player_video',array('vid'=> $vd['id']));
			//应用区图片设置
			$this->accessmodel->apisetUserMenuImg(WEB_ID,$vd['video_pic']);  		
			//添加搜索索引
			//$vd['uname'] = $this->web_info['name'];
			//$this->accessmodel->addVideoSearch($vd);
			$data = array('vid' => $vd['id'],'imgurl' => $pic_path,'url' => $url,'width' => $vd['width'],'height' => $vd['height']);
			$this->ajaxReturn($data, $info = '上传成功!', 1, $type = 'jsonp');
		}else{		 				
			$this->ajaxReturn($data='', $info = '视频已上传成功,请等待系统 处理及审核!', 3, $type = 'jsonp');
		}	 		
	}

	/**
	 * 首页时间线应用区视频数量
	 * @author wangying
	 * @date   2012/4/18
	 * @access public
	 * @return string
	 */	
	 public function timeline_video_num(){
	 $web_id = (int)$this->input->get('web_id');
		if($web_id){
			$num = $this->videomodel->getTimelineVideoNum($web_id);
			$this->ajaxReturn(array('num'=>$num), $info = '', 1, $type = 'jsonp');
		}else{
			$this->ajaxReturn(array('num'=>0), $info = '', 0, $type = 'jsonp');
		}
	 }	
}