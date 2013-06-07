<?php

/**
 * 登录注册
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/21>
 *
 */
class Uservice extends MY_Controller
{
	/**
	 * 构造函数
	 */	
	public function __construct()
	{		
		parent::__construct();		
	}
	
	/**
	 *lvxinxin 2012-07-30 add
	 * java登陆接口
	 */
	public function getUserLoginState()
	{
		$param = urldecode($this->input->get('param'));
		if(empty($param) || json_decode($param,true) == null) die(json_encode(array('code'=>99,'text'=>'参数非法')));
		$this->load->memcache('session',false,'session');
		$info = json_decode($param,true);		
		// die(var_dump($info));
		$data = $this->session->get($info['sessionid']);		
		if(empty($data)) die(json_encode(array('code'=>99,'text'=>'请重新登陆')));
		$cur_session = $_SESSION;
		if(session_decode($data)){
			$tmp_session = $_SESSION;
		}
		else{
			die(json_encode(array('code'=>99,'text'=>'数据解析失败')));
		}
		
		$_SESSION = $cur_session;
		
		if($info['info']){
			$array = array(
						'dkcode'=>$tmp_session['user']['dkcode'],
						'email'=>$tmp_session['user']['email'],
						'username'=>$tmp_session['user']['username'],
						'userImg'=>get_avatar($tmp_session['uid'],'ss'),
						'link'=>mk_url('main/index/profile')
			);
			die(json_encode(array('code'=>100,'text'=>'ok','result'=>$array)));
		}
		else{
			die(json_encode(array('code'=>100,'text'=>'ok')));
		}
		
	}

	
}