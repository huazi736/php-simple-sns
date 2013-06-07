<?php

/**
 * 激活更换的邮箱
 *
 * @author        sunlufu
 * @date          2012/08/04
 * @version       1.0
 * @description   
 * @history       
 */
class Activeemail extends MY_Controller
{
	public function __construct ()
	{
		parent::__construct();
		if($this->autoLogin()){
			$this->redirect('main/index/main');
		}
		//$this->load->model('usermodel', 'user');
	}

    function index ()
    {
		
        $this->assign('url_login', mk_url('front/login/userlogin'));
        $this->assign('url_reg', mk_url('front/register/index'));
        $this->assign('forget_pass', mk_url('front/login/forget_pass'));
        $step = $this->input->post('step');
        if(empty($step)) {
			if(!empty($_SESSION['uid']) && $_SESSION['user']['status'] != 0)  $this->redirect('main/index/main');
			$this->redirect('front/login/index');
		}	
        //端口号
        $dk_num = addslashes($this->input->post("invatationCode"));
        //全名
        $name = addslashes($this->input->post("name"));//暂时没过滤
        //帐号
        $email = addslashes(strtolower(trim($this->input->post("email"))));
        //新密码
        $pwd = $this->input->post("password");
        //确认密码
        $pwd_check = $this->input->post("repassword");
        //性别
        $sex = $this->input->post("sex");
		$area = $this->input->post("area");
        //国家
        $now_nation = $this->input->post("now_nation");
        //省
        $now_province = $this->input->post("now_province");
        $now_city = $this->input->post("now_city");
        $now_town = $this->input->post("now_town");
		
        //服务条款
        $dk_service = $this->input->post("comfirmClause");
        $name = preg_replace("/(　){2,}/", "", $name); //把全角状态下空格踢除
		$name = preg_replace('/\s+/', '', $name);   //把英文状态下的空格踢除
		$name = preg_replace('/[\n\r\t]/', '', $name); //去掉非space的空格踢除   
		if(!preg_match("/^[\x{4E00}-\x{9FFF}a-zA-Z]+$/u", $name)){
			die(json_encode(array('state' => '0', 'msg' => "姓名只能输入中英文")));
		}
		if(!true){
			$this->error('请您确定端口网服务条款!');			
		}elseif( !$dk_num || !$name || !$email || !$pwd || !$pwd_check || !$sex ){
			$this->error('信息不完整');			
		}elseif($pwd != $pwd_check){
			$this->error('确定密码不正确');			
		}elseif(!check_email($email)){
			$this->error('请填写正确的email');			
		}elseif(strlen($name) > 30){
			$this->error('姓名长度不可超过10字');					
		}elseif(strlen($email) > 64){
			$this->error('邮箱不能超过64个字符');				
		}elseif(strlen($dk_num) > 10 ){
			$this->error('您的邀请码不正确');						
		}
		if(isset($area)){
			$area = str_replace(',',' ',$area);
		}
		else{
			$area = '';
		}
		//处理cityid
		if($now_province != '-1' && $now_city == '-1') $city = $now_province . '0102';
		if($now_city != '-1') $city = $now_city . '02';
		if($now_nation == '-1') $city = 0; 
		if($now_nation != '-1' && $now_province == '-1') $city = 0;
		service('UserWiki')->updateUserCount($city,0,$sex);		
        $data = array(
        	'dkcode' => $dk_num, 
        	'username' => $name, 
        	'email' => $email, 
            'passwd' => $pwd, 
            'sex' => $sex, 
            'cityid' => $city,
        	'now_addr'=>$area, 
            'regdate' => time(), 
            'regip' => get_client_ip()
        );
		$_SESSION['regData'] = $data;		
		$this->load->model('invitecodemodel');
    	$dkdata = $this->invitecodemodel->checkDkCode($dk_num);
		if(!$dkdata){
			$this->error('邀请码不存在');			
		}
		elseif($dkdata['name'] != $data['username']){
			$this->error('姓名和邀请码不一致');			
		}
		elseif ($dkdata['status'] == 1){			
			$this->error('邀请码已经被使用');			
		}		
        
		$reg = service('Passport')->saveRegister($data);
        if ($reg['status'] != 1)
        {
        	$this->error($reg['msg']);            
        }
        $active_code = urlencode($reg['msg']);        
    	
        $mailtype = substr($email,(strrpos($email,'@')+1));		//edit 大小写转换
		$mail_url = mk_url('front/register/do_active_userinfos',array('active_code'=>$active_code));
		$mail = config_item('mail');
		if(array_key_exists($mailtype,$mail)){
			$mailtype = $mail[$mailtype];
		}
		else{
			$mailtype = null;
		}		
		$this->assign('usr_email',$email);
		$this->assign('mailtype',$mailtype);
		$this->assign('active_mail_url',$mail_url);
		$this->assign('dkcode',$dk_num);
		$this->assign('register_url',mk_url('front/register/index'));
		$this->assign('mail_url',$mail_url);
		$this->display('register/confirm_email');
    }
	
    /**
     * 帐号激活
     * @author lvxinxin
     * @date 2012/03/14
     * @access public
     * 
     * @param active_code 验证字符串
     */
    function do_active_userinfos ()
    {		
        $active_code = str_replace(' ','+',urldecode($this->input->get('active_code')));        
		$status = service('Passport')->activeUser($active_code);		
        if (is_array($status))
        {
        	$this->error($status['msg']);
        }
        else
        {        	     	
        	//激活成功之后，向时间线发送相关动态信息
        	$staticData = array(
        						'uid'=>$_SESSION['uid'],
								'uname'=>$_SESSION['user']['username'],
        						'dkcode'=>$_SESSION['user']['dkcode'],
								'type'=>'uinfo',
								'subtype'=>'static',					
								'content'=>'加入端口网',
								'info'=>date('Y年n月j日',time()),
								'permission'=>1,
        						'fid'=> '-1_static',
								'dateline'=>time()					
        	);
        	
			service('Timeline')->addTimeLine($staticData);
        	        		
        	$this->load->model('invitecodemodel');
			$invite_data = $this->invitecodemodel->getRecommandUser($_SESSION['user']['dkcode']);
			
			service('credit')->invite($invite_data[0]['uid']);
			
			service('credit')->register();
            
            $redisdata = array('uid'=>$_SESSION['uid'],'uname'=>$_SESSION['user']['username'],'dkcode'=>$_SESSION['user']['dkcode'],'sex'=>$_SESSION['user']['sex']);
        	
            service('User')->setShortInfo($redisdata);
        	if(empty($invite_data)){
        		
        	}
        	else{
        		
				service('Relation')->follow($_SESSION['uid'],$invite_data[0]['uid']);
        		service('RelationIndexSearch')->addAFansForOne($invite_data[0]['uid']);//增加邀请我的人的粉丝数 lvxinxin add 2012-07-30
        	}       	
        	//给邀请我的人发通知        	
			service('Notice')->add_notice(1,$_SESSION['uid'],$invite_data[0]['uid'],'dk','dk_receiveinvite');
        	//发送给搜索的数据        	
        	$search = array(
        					'uid'=>$_SESSION['uid'],
        					'uname'=>$_SESSION['user']['username'],
		 					'dkcode'=>$_SESSION['user']['dkcode'],
		 					'company'=>array(),
		 					'follower_num'=>0,
		 					'home_addr'=>'',
		 					'now_addr'=>$_SESSION['user']['now_addr'],
		 					'regdate'=>$_SESSION['user']['regdate'],
		 					'school_name'=>array()
        	);        	 
        	service('RelationIndexSearch')->addOrUpdateBasalInfoOfPeople($search);   	
        	$this->assign('regstep',mk_url('front/register/avatar'));
        	$this->assign('main',mk_url('main/index/main'));
            $this->display('register/regSuccess.html');
        }
    }

	public function activeemail(){
		$this->error('此功能正在开发中，敬请期待！');
		return false;
		
		//url参数解密
		$code = str_replace(' ','+',urldecode(trim($this->input->get('code'))));
		if(empty($code)){
			$this->error('对不起，您的邮箱重置激活链接不合法！');
			return false;
		}
		$decode_code = service('Passport')->getCrypt($code, false);
		
		//判断参数个数是否正确
		$params = explode('/', $decode_code);
		if(count($params) != 4 ) {
			$this->error('对不起，您的邮箱重置激活链接不合法！');
			return false;
		}
		
		//判断各个参数是否合法
		$params = array_combine(array('uid', 'dkcode', 'email', 'time'), $params);
		$uid    = intval($params['uid']);
		$dkcode = intval($params['dkcode']);
		$email  = trim($params['email']);
		$time   = intval($params['time']);
		if($uid < 1 or $dkcode < 1 or !check_email($email) or $time < 1) {
			$this->error('对不起，您的邮箱重置激活链接不合法！');
			return false;
		}
		$params = array(
			'uid'=> $uid,
			'dkcode'=> $dkcode,
			'email'=> $email,
			'time'=> $time
		);
		
		//修改邮箱url有效期为1小时
		$mktime = $params['time']+3600; 
		if($mktime < $params['time']) {
			$this->error('对不起，您的邮箱重置激活链接已经超时！');
			return false;
		}
		
		//判断该用户是否存在
		$userinfo = service('User')->getUserInfo($params['dkcode'], 'dkcode', array('uid') ,true);
		if(empty($userinfo) or $userinfo['uid'] != $params['uid']) {
			$this->error('对不起，您的邮箱重置激活链接不合法！');
			return false;
		}
		
		//判断该用户是否发起了修改登录邮箱操作
		//$ismodemai = $this->user->ismodemail($params);
		$ismodemai = service('UserWiki')->ismodemail($params);
		if(!$ismodemai) {
			$this->error('对不起，您的邮箱重置激活链接不合法！');
			return false;
		}
		
		//执行修改登录邮箱操作
		//$modemai = $this->user->modemail($params);
		$modemai = service('UserWiki')->modemail($params);
		if(!$modemai){
			$this->error('对不起，您的邮箱重置激活链接不合法！');
			return false;
		}
		$this->display('activeemail/success.html');
	}
}
?>