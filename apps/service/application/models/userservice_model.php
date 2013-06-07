<?php
/**
 * 用户资料对外接口
 * @author sunlufu
 */
class UserService_model extends MY_Model {

	public function __construct(){
		parent::__construct();
		$this->init_memcache('session');
	}

	/**
	 * java登陆接口,用户判断用户是否登录
	 * @author lvxinxin
	 * @date   2012-07-30
	 * @access public
	 * @param  json后的一维数组  有两个键sessionid、info:是否返回用户基本信息  true 是  false 否
	 * @return json
	 */
	public function getUserLoginState($param)
	{
		$param = urldecode($param);
		
		//json格式统一转换
		$decode_param = $this->decodeParams($param);
		if(empty($param) || $decode_param == null) {
			return $this->encodeResult(99, '参数非法', '');
			
		}
		
		//判断是否已经登录
		$login_data = $this->memcache->get($decode_param['sessionid']);
		if(empty($login_data)) {
			return $this->encodeResult(99, '请重新登陆', '');
		}
		
		$cur_session = $_SESSION;
		
		//解析存memcache中的数据
		if(session_decode($login_data)) {
			$tmp_session = $_SESSION;
		} else {
			return $this->encodeResult(99, '数据解析失败', '');
		}
		
		$_SESSION = $cur_session;
		
		//判断是否需要用户信息
		if($decode_param['info']) {
			$array = array(
				'dkcode'=>$tmp_session['user']['dkcode'],
				'email'=>$tmp_session['user']['email'],
				'username'=>$tmp_session['user']['username'],
				'userImg'=>get_avatar($tmp_session['uid'],'ss'),
				'link'=>mk_url('main/index/profile')
			);
			return $this->encodeResult(100, 'ok', $array);
		} else {
			return $this->encodeResult(100, 'ok', '');
		}
	}
	
}