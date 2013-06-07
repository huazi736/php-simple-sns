<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 邀请码
 *
 * @author        hujiashan
 * @date          2012/3/20
 * @version       1.2
 * @description   邀请码相关功能
 * @history       <author><time><version><desc>
 */

class Invitecode extends DK_Controller {
	function __construct(){
		parent::__construct();
		$this->load->model('invitecodemodel', 'invitecode', TRUE);
		//$this->load->model('apimodel', 'api', TRUE);
	}

	/**
	 * 邀请码页面
	 *
	 * @author		bohailiang
	 * @date		2012/2/21
	 * @access		public
	 * @param 
	 */
	function index(){

		//获取user信息
		$user_info = array();
		$user_info['username'] = $this->username;
		$user_info['url'] = mk_url('main/index/main', array('dkcode' => $this->dkcode));
		$user_info['avatar_img'] = get_avatar($this->uid,'ss');


		//每页数据条数
		$limit = 12;
		$recmd_lists = $recmded_info = array(); 
		$restult = $this->invitecode->getInviteCodeAllStatus($this->uid, $this->dkcode, 0, $limit);
		
		//取得推荐我的人
		if($restult['recmded_info']){
			$recmded_info = $restult['recmded_info'];
			$recmded_info[0]['url'] = mk_url('main/index/main', array('dkcode' => $recmded_info[0]['dkcode']));
			$recmded_info[0]['avatar_img'] = get_avatar($recmded_info[0]['uid'], 's');
		}
		
		//取得我推荐的人列表
		if($restult['recmd_lists']){
			foreach($restult['recmd_lists'] as $k => $v){
				$v['avatar_img'] = get_avatar($v['uid'], 's');
				$v['url'] = mk_url('main/index/main', array('dkcode' => $v['dkcode']));
				$recmd_lists[] = $v;
			}
		}

		//获取我推荐且 成功注册人的总数
		$getcount = $restult['getcount'];

		//取得总页数
		$pagecount = $getcount <= $limit ? 1 : ceil(($getcount - $limit)/20) + 1;
		
		//获取剩余邀请码数量
		$dkcode_nums = $restult['dkcode_nums'];
		
		$this->assign('pagecount',$pagecount);
		$this->assign('uid',$this->uid);
		$this->assign('recmded_info',$recmded_info);
		$this->assign('recmd_lists',$recmd_lists);
		$this->assign('user_info', $user_info);
		$this->assign('dkcode_nums', $dkcode_nums);
		$this->assign('invitecode_url', mk_url('main/invitecode/index'));
		$this->display('invitecode/invitecode.html');
	}

	/**
	 * 取得更多我推荐的人loadmore
	 *
	 * @author	hujiashan
	 * @date	2012/03/15
	 * @access	public
	 * @param	$uid 用户ID
	 * @param	$nowpage 当前页码
	 */
	function get_recommend_loadmore(){
		
		$nowpage = intval($this->input->post('nowpage')) ? intval($this->input->post('nowpage')) : 2;
		
		//每页数据条数
		$limit = 20;
		$start = ($nowpage-2) * $limit + 12;

		//取得我推荐的人列表
		$lists = $this->invitecode->get_recommend_lists($this->uid,$start,$limit);
		if(!$lists){
			$this->ajaxReturn('', 'error!', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "error!")));
		}

		$html = "";
		foreach($lists as $v){
		  $html .= '<li class="item" uid="'.$v['uid'].'">';
			  $html .= '<p class="photo"><a href="'.$v['url'].'"><img alt="头像" src="'.$v['avatar_img'].'" /></a></p>';
			$html .= '<span class="userinfo">';
			   $html .= '<p class="name"><a href="'.$v['url'].'" title="'.$v['username'].'">'.$v['username'].'</a></p>';
				$html .= '<div class="statusBox newItem" uname="'.$v['username'].'" rel="'.$v['is_follow'].'" uid="'.$v['uid'].'"></div>';
			  $html .= '</span>';
		 $html .= '</li>';
		}
		$this->ajaxReturn($html, $start, '1', 'json');
		//die(json_encode(array('state' => '1', 'data' => $html,'msg' => $start)));
	}
	
	/**
	 * 检查手机号码是否已被注册或已使用该手机号码发送三次邀请	
	 *
	 * @author	hujiashan
	 * @date	2012/04/18
	 * @access	public
	 * @param	$uid 用户ID
	 * @param	$mobile 被邀请者手机号码
	 */
	function checkmobile(){

		$mobile = $this->security->xss_clean(trim($this->input->post('userMobile')));
		if(!$mobile){
			$this->ajaxReturn('', '请输入被邀请人手机号码', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "请输入被邀请人手机号码")));
		}
		
	   //检查手机号码是否正确
		if(!is_mobile($mobile)){
			$this->ajaxReturn('', '请输入被邀请人手机号码', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "请输入正确的手机号码")));
		}
		
		//检查手机号码是否已被注册或已使用该手机号码发送三次邀请	
		$result = $this->invitecode->checkmobile($this->uid, $mobile);
		if(!$result['status']){
			$this->ajaxReturn('', $result['msg'], '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' =>$result['msg'])));
			
		}else{
			$this->ajaxReturn(array('mobile' => $mobile), '该号码可以使用', '1', 'json');
			//die(json_encode(array('state' => '1', 'msg' =>'该号码可以使用' , 'mobile' => $mobile)));
		}
		
	}
	/**
	 * 生成端口号(生成邀请码)
	 * @author	hujiashan
	 * @date	2012/3/15
	 * @access	public
	 * @param	userName 用户名称
	 * @param	userMobile 手机号码
	 */
	function duankou_num(){
		
		$name = $this->security->xss_clean(trim($this->input->post('userName')));;
		$mobile = $this->security->xss_clean(trim($this->input->post('userMobile')));
		
		if(!$name){
			$this->ajaxReturn('', '请输入被邀请人姓名', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "请输入被邀请人姓名")));
		}
		
	    if(!$mobile){
			$this->ajaxReturn('', '请输入被邀请人手机号码', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "请输入被邀请人手机号码")));
		}
		
		if(!is_utf8($name) || !is_utf8($mobile)){
			$this->ajaxReturn('', '编码格式不正确，请转为UTF-8格式!', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "编码格式不正确，请转为UTF-8格式!")));
		}
				
		//检查手机号码是否正确
		if(!is_mobile($mobile)){
			$this->ajaxReturn('', '请输入正确的手机号码', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "请输入正确的手机号码")));
		}	

		if(!preg_match("/^[\x{4E00}-\x{9FFF}a-zA-Z]+$/u", $name)){
			$this->ajaxReturn('', '2-10个字符，仅限中、英文', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "2-10个字符，仅限中、英文")));
		}
		
		$len = mb_strlen($name, 'utf-8');
		if($len < 2 || $len > 10){
			$this->ajaxReturn('', '2-10个字符，仅限中、英文', '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' => "2-10个字符，仅限中、英文")));
		}
		
		//邀请一个用户
		$result = $this->invitecode->invite_user($name, $mobile, $this->uid);
		if(!$result['status']){
			
			$renums = isset($result['renums']) ? $result['renums'] : 0;
			$this->ajaxReturn(array('renums' => $renums), $result['msg'], '0', 'json');
			//die(json_encode(array('state' => '0', 'msg' =>$result['msg'] , 'renums' => $renums)));
			
		}else{
			//短信发送
			//by sunlufu at 2012.2.31 19:44
			$content = "尊敬的用户,您好! 您的朋友'".$this->username."'邀请您加入duankou网(www.duankou.com),您的邀请码为:".$result['data'].",姓名为:".$name.".duankou网期待您的加入【端口网】";
			//$this->api->sendSMS($mobile, $content);
			//最新发送短信接口
			$ret = service('Mqsms')->sendMqsms($mobile, $content);
			
			$isextend = $this->isextend();
			if(!$isextend) {
				$this->ajaxReturn(array('dk_code' => $result['data'], 'renums' => $result['renums']), '', '1' ,'json');
				//die(json_encode(array('state' => '1', 'dk_code' => $result['data'], 'renums' => $result['renums'])));
			} else {
				$this->ajaxReturn(array('dk_code' => '', 'renums' => $result['renums']), '', '1', 'json');
				//die(json_encode(array('state' => '1', 'dk_code' => '', 'renums' => '')));
			}
			
			//die(json_encode(array('state' => '1', 'dk_code' => $result['data'], 'renums' => $result['renums'])));
			/*if($sms_result['state']){
				switch ($sms_result['state'])
		         {
		            case '0' :
		                 	die(json_encode(array('state' => '1', 'dk_code' => $result['data'], 'renums' => $result['renums'])));
		                 break;
		             case '17' :
		                  	die(json_encode(array('state' => '0','msg' => "发送信息失败!")));
		                 break;
		             case '18' :
		                    die(json_encode(array('state' => '0','msg' => "发送定时信息失败!")));
		                 break;
		             case '303' :
		                    die(json_encode(array('state' => '0','msg' => "客户端网络故障!")));
		                 break;
	                 case '305' :
	                  		die(json_encode(array('state' => '0','msg' => "服务器端返回错误，错误的返回值!")));
	                   	 break;
	                 case '307' :
		                    die(json_encode(array('state' => '0','msg' => "目标电话号码不符合规则!")));
		                 break;
	                 case '997' :
		                    die(json_encode(array('state' => '0','msg' => "平台返回找不到超时的短信，该信息是否成功无法确定!")));
		                 break;
	                 case '998' :
		                    die(json_encode(array('state' => '0','msg' => "由于客户端网络问题导致信息发送超时，该信息是否成功下发无法确定!")));
		                 break;
	                 //case '9001' :
		             //       die(json_encode(array('state' => '0','msg' => "号码为空!")));
		             //    break;
		          }
			}elseif($sms_result['error']){
				die(json_encode(array('state' => '0','msg' => "错误的手机号!")));
			}*/
		}
	}

	//判断是否有推广活动，用于显示用户邀请码。
	public function isextend(){
		$extend = config_item('extend');
		if(empty($extend) or !is_array($extend)) {
			return false;
		}
		if(!$extend['isextend']) {
			return false;
		}
		$starttime = strtotime($extend['start']);
		$endtime = strtotime($extend['end']);
		$time = time();
		if($time > $endtime or $time < $starttime) {
			return false;
		}
		return true;
	}
}
?>