<?php

/**
 * 登录注册
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/21>
 *
 */
class Index extends MY_Controller
{
	/**
	 * 构造函数
	 */
	private $takon = 'lxx';
	public function __construct()
	{
		// $this->is_check_login = false;
		parent::__construct();
		
		
		$this->assign('web_root',str_replace('http://','',WEB_ROOT));
	}
	
	/**
	 * 登录注册首页
	 */
	public function index()
	{
		if($this->autoLogin()){
			$this->redirect('main/index/main');
		}
		if(!empty($_SESSION['uid']) && $_SESSION['user']['status'] != 0) $this->redirect('main/index/main');	
		$this->assign('url_login',mk_url('front/login/userlogin'));
		$this->assign('url_reg',mk_url('front/register/index'));
		$this->assign('backurl',$this->input->get('backurl'));	
		$this->assign('service',mk_url('main/service/index'));
		$this->display('index');
		//echo $this->xhprof->close('log',true);
	}	
	
	/**
	 * 处理注销
	 */
	public function dologout()
	{
		// set_cookie('dknet','',time()-3600);
		
		delete_cookie('dknet');
		service('Passport')->logoutLocal();
		session_destroy();
		//$success = call_soap('ucenter','Passport', 'logoutLocal',array($this->sessionid));
		/*$res = curl_init();
		$data = array(
				CURLOPT_URL=> WEB_ROOT . 'dksns-im-web/logout',
				CURLOPT_CONNECTTIMEOUT_MS=>3000
		);
		curl_setopt_array($res, $data);
		curl_exec($res);
		if(curl_errno($res) == 0){
			//success
		}
		else{
			//fail
		}
		curl_close($res);	*/		
	    if(!$this->uid)
		{
			$this->redirect('front/index/index');			
		}		
		
	}
	
	public function test(){
		// var_dump($_SESSION);
		// $array = array('login_name'=>'lx.xin@qq.com','passwd'=>'f0c57ab9089920685a8c1c1124ea31e2','returntype'=>'xml');
		$array = array(
					'invatationCode'=>'100328',
					'name'=>'手机接口测试',
					'email'=>'mobile@qq.com',
					'password'=>'f0c57ab9089920685a8c1c1124ea31e2',
					'repassword'=>'f0c57ab9089920685a8c1c1124ea31e2',
					'sex'=>'3',
					'area'=>'',
					'now_nation'=>'',
					'now_province'=>'',
					'now_city'=>'',
					'now_town'=>'',
					'returntype'=>'json',
		);
		$res = curl_init();
		$data = array(
				CURLOPT_URL=> mk_url('front/mobile/register'),
				CURLOPT_CONNECTTIMEOUT_MS=>5000,
				CURLOPT_RETURNTRANSFER=>true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS=>$array,
		);
		// var_dump($data);
		$flag = curl_setopt_array($res, $data);
		// var_dump($flag);
		$result = curl_exec($res);	
		curl_close($res);
		echo $result;
	}

		
}