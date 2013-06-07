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
		$this->load->model('videomodel');
		$this->load->model('accessmodel');
		$this->config->load("video");
		$this->load->helper('wvideo');
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
		$module = str_replace('module=','',$arr[1]);
		$status = str_replace('status=','',$arr[2]);

		$vd = $this->videomodel->getVideoInfo($vid,' * ',$module);
		if(!$vd){
			return false;
		}
		define('WEB_ID' , $vd['web_id'] );
		$web_info = service('interest')->get_web_info(WEB_ID); 
		$vd['uname'] = $web_info['name'];        
		$weburl  = mk_url('webmain/index/main',array('web_id'=>WEB_ID));
		if($status == 0){
			//转码失败,删除video数据库改条视频信息
			$this->videomodel->delVideo($vid);
			$url = mk_url('wvideo/video/video_upload',array('web_id'=>WEB_ID));
			$this->accessmodel->api_ucenter_notice_addNotice(WEB_ID,$vd['uid'], $vd['uid'], 'web', 'upload_false_videoweb', array('name' => $vd['uname'],'url' => $weburl,'name1' => $vd['title'],'url1' => $url));
		}else{
			$video_pic  = get_img_path($vd['video_pic']);
			$pic_path  = get_img_path($vd['video_pic']);
			if(!empty($vd['timestr'])){
				$str = explode("|", $vd['timestr']);
				$data['dateline'] = preg_replace_callback('/(?P<year>\d{4})(-?)(?P<mon>\d{0,2})(-?)(?P<day>\d{0,2})/', 	function ($match)
				{
					(int)$match['mon'] < 10 && !($match['mon'] = '0'.$match['mon']) && $match['mon'] = '01';
					(int)$match['day'] < 10 && !($match['day'] = '0'.$match['day']) && $match['day'] = '01';
					return $match['year'] . min($match['mon'], 12) . min($match['day'],31);
				}, $str[1]?:date('Y-n-j',time())); 	
				if( $str[0] < 1 ){	//公元前
					$data['dateline'] = '-'.$data['dateline'];
				}else{ //公元后
					$data['dateline'] = $data['dateline'].($data['dateline']==date('Ymd',time())?date('His',time()):'000000');
				}
				$vd['dateline'] = $data['dateline'];
				$vd['timedesc'] = $str[2];
				$vd['from']     = 3;
			}else{			
				$vd['timedesc'] = '';
			}
			$search = $this->addTimeline($vd,$pic_path);
			//应用区图片设置
			$this->accessmodel->ApisetUserMenuImg(WEB_ID,$vd['video_pic']);		
			//添加搜索索引
			if(!empty($seach)){
				service('RelationIndexSearch')->addOrUpdateStatusInfo(json_encode($search));
				//$this->accessmodel->addVideoSearch($vd);
			}	
			//调取通知接口写入转码成功
			$url = mk_url('wvideo/video/player_video',array('vid'=>$vid));
			$this->accessmodel->api_ucenter_notice_addNotice(WEB_ID, $vd['uid'], $vd['uid'], 'web', 'upload_true_videoweb', array('name' => $vd['uname'],'url' => $weburl,'name1' => $vd['title'],'url1' => $url));
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
		$user = service('User')->getUserInfo($vd['uid'],'uid',array('dkcode'));
		$dkcode = $user['dkcode'];
		
		$data['uid']     = $vd['uid'];
		$data['dkcode']  = $dkcode;
		$data['pid']     = WEB_ID;
		$data['fid']     = $vd['id'];
		$data['uname']   = $vd['uname'];
		$data['title']   = $vd['title'];
		$data['content'] = $vd['discription'];
		$data['type']    = 'video';
		$data['imgurl']  = $video_pic;
		$data['width']   = $vd['width'];
		$data['height']  = $vd['height'];
		$data['timedesc'] = $vd['timedesc'];

		if(isset($vd['from']) && $vd['from']==3){
			$data['from']     = $vd['from'];
			$data['ctime']    = $vd['dateline'];
			$data['dateline'] = date('YmdHis',time());
		}else{
			$data['dateline'] = date('YmdHis',$vd['dateline']);
		}
		$this->accessmodel->setTimeline($data,WEB_ID);
		return true;
	}
	
}