<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 登录与密码找回
 * 
 * Enter description here ...
 * @author lvxinxin
 * @date   2012/04/22
 * @version 1.0
 * @description 登录与密码找回
 * @history <author><date><version><description>
 */
class Login extends MY_Controller
{   
    public function __construct ()
    {	
        parent::__construct();		
		if($this->autoLogin()){
			$this->redirect('main/index/main');
		}			
    }

    /**
     * 用户登录控制器主方法
     * 
     * @author lvxinxin
     * @date   2012/04/01
     * @access public
     */
    public function index ()
    {			
		if(!empty($_SESSION['uid']) && $_SESSION['user']['status'] != 0) $this->redirect('main/index/main');
        $this->assign('url_reg', mk_url('front/register/index'));
        $this->assign('url_login', mk_url('front/login/userlogin'));
        $this->assign('forget_pass', mk_url('front/login/forget_pass'));
		$this->assign('backurl',$this->input->get('backurl'));
		$this->assign('service',mk_url('main/service/index'));
        $this->display('index.html');
    }
    
    public function outlogin()
    {
        echo $this->fetch('loginOut.html');
    }

    /**
     * 项目登陆入口文件
     * 
     * @author lvxinxin
     * @date   2012/04/22
     * @access public
     * @param  string $name 用户登录帐号名
     * @return HTML
     */
    public function userlogin ()
    {	    	
    	if ($this->input->post('login_switch'))
        {			
            $name = addslashes((trim($this->input->post('login_name'))));
            $passwd = $this->input->post('passwd');
            $backurl = $this->input->post('backurl');  
            if (empty($name) || empty($passwd))
            { 
				log_message('ERROR',array('msg'=>'用户名密码不能为空','login_name'=>$name,'ip'=>get_client_ip()));
               	$this->redirectLogin('用户名密码不能为空');             	
            	
            }
            else
            {                   
                $user = service('Passport')->loginLocal($name,$passwd);
				
				if ($user && intval($user['status']) == 0)
                {
					$this->redirectLogin("您的帐号被停用");                    
                }
				elseif(is_array($user)){					             
                    if(!empty($_SESSION[md5($name)])) unset($_SESSION[md5($name)]);
					$remember = $this->input->post('remember') ? true : false;					
					if($remember){					
						$expire = time()+3600*24*7;				
						$autoLogin = service('Passport')->getCrypt($_SESSION['uid'],true);
						set_cookie('dknet',$autoLogin,$expire);						
					}
					//登陆积分
					service('credit')->login();
					$msg = array(
							'dkcode'=>$_SESSION['user']['dkcode'],
							'username'=>$_SESSION['user']['username'],
							'ip'=>get_client_ip(),
							);
					log_apps_msg($_SESSION['uid'],1,$msg);			
					
                    if(!empty($backurl))
                    {
						// echo 'backurl:' .$backurl;exit;
                        $this->redirect($backurl);
                    }
                    else 
                    {	
						// echo 'main:';exit;
                       $this->redirect('main/index/main');						
                    }
					
				}
                else
                {  
					log_message('ERROR',array('msg'=>'登陆失败(帐号或密码错误)','login_name'=>$name,'ip'=>get_client_ip()));
					if(empty($_SESSION[md5($name)]))  $_SESSION[md5($name)] = 0;
					if($_SESSION[md5($name)] >1){
						session_unset($_SESSION[md5($name)]);
						$this->redirect('front/login/forget_pass');
					}
					else{
						$_SESSION[md5($name)] += 1;
                		$this->redirectLogin("登陆失败，请检查帐户或者密码");
					}               	    
                }
            }
        }
        else
        { 
			$this->redirect('front/login/index');            
        }
    }   

    /**
     * 忘记密码第一步
     * 
     * @author lvxinxin
     * @date   2012/04/22
     * @access public
     * @return HTML
     */
    public function forget_pass ()
    {        
        $this->display('register/find_psw');            
        exit();
        
    }
    
    
	
    /**
     * 第二步：验证邮箱的有效性
     * 
     * @author lvxinxin
     * @date   2012/04/22
     * @access public
     * @param  string $emal 用户登录邮箱
     * @return JSON
     */
    public function doforget_pass_checkemail ()
    {
		
        $state = 0;
		$identifier = strtolower(trim($this->input->post('email')));
		$identifier_type = check_email($identifier) ? 'email' : 'dkcode';        
		// $user = call_soap('ucenter', 'User', 'getUserInfo',array($identifier,$identifier_type,array('uid','email','dkcode','username'),true));
		$user = service('User')->getUserInfo($identifier,$identifier_type,array('uid','email','dkcode','username'),true);
		if (! $user)
		{
			$state = 0;
			$msg = '请输入你注册时使用的邮箱或端口号';
		}
		else
		{                
			$success = true;
			$code = $this->_findPwdByEmail($user['email'],$user);
			if ($success && $code)
			{				
				$state = 1;
				$msg = mk_url('front/login/do_active',array('code'=>$code));//WEB_ROOT.'front/index.php?c=login&m=do_active&code=' . $code;
				service('Mail')->sendEmail($user['email'],$user['username'],'找回密码',1,$msg);
				if($identifier_type == 'dkcode'){
					$data = explode('@',$user['email']);
					$length = strlen($data[0]);
					$email = substr_replace($data[0],'***',3,$length).'@'.$data[1];
					$_SESSION['email'] = $email;
					$_SESSION['msg'] = $msg;
					$msg = '';
				}
				else{
					$email = $identifier;
					$_SESSION['email'] = $email;
					$_SESSION['msg'] = $msg;
					$msg = '';
					
				}
				
				// set_cache($this->sessionid.'findPsw', $email);
			}
			else
			{
				$state = 0;
				$msg = '验证邮件发送失败';
			}
		}
        $this->ajaxReturn('',$msg,$state);
        // die(json_encode(array('state' => $state, 'msg' => $msg/*, 'email'=>$email*/))); //参数格式化
    }
	/**
	 *发送邮件
	 *@author lvxinxin
	 *@date 2012/07/03
	 */
	 public function _findPwdByEmail($email,$data){
		if (empty($email) || ! check_email($email) || empty($data)) //判断是否是有效邮箱
        {
           return false;
        }
		$time = time();
		$str = trim($data['email'])."\t".trim($data['dkcode'])."\t".$data['username']."\t".$time;
		service('Passport')->set_edit_pwd_status($time,$data['dkcode']);
		// file_put_contents('sql.txt','set:'.$time,FILE_APPEND);
        // $active_code = call_soap('ucenter', 'Passport', 'getCrypt',array(urlencode($str),true));
		$active_code = service('Passport')->getCrypt(urlencode($str),true);
		$active_code = urlencode($active_code);
		if(config_item('net')){
			// return service('Passport')->findPwdEmail(urldecode($active_code));//call_soap('ucenter','Passport','findPwdEmail',array(urldecode($active_code)));			
			
		}
		else{
			return $active_code ;
		}
		
        	
	 }
	 /**
	  *邮件发送成功页面
	  *@author lvxinxin
	  *@date 2012-07-04
	  */
	  public function successEmail(){
		$email = @$_SESSION['email'];//$this->input->post('email');
		$mailtype = substr($email,(strrpos($email,'@')+1));	//edit 大小写转换		
		$mail = config_item('mail');
		if(array_key_exists($mailtype,$mail)){
			$mailtype = $mail[$mailtype];
		}
		else{
			$mailtype = null;
		}		
		$this->assign('mailtype',$mailtype);
		$code =  @$_SESSION['msg'];//$this->input->post('msg');
		$this->assign('url',$code);
		$this->assign('email',$email);
		$this->display('register/find_psw_email_s.html');
	  }
    //验证激活链接
	public function do_active(){
		
		$code =str_replace(' ','+', urldecode( $_GET['code']));
		if(empty($code)){
			$this->error("无效的验证链接");
		}
		
		$str = service('Passport')->getCrypt($code,false);//call_soap('ucenter', 'Passport', 'getCrypt',array($code,false));		
		$data = explode("\t", urldecode($str));	
		$status = service('Passport')->get_edit_pwd_status($data[1]);
		// file_put_contents('time.txt','get:'.$status.'----'.$data[3],FILE_APPEND);
		if($status != $data[3]) $this->error('激活链接失效');
		$flag = service('User')->getUserInfo(urldecode($data[0]),'email');//call_soap('ucenter', 'User', 'getUserInfo',array(urldecode($data[0]),'email'));		
		if(empty($flag)){            
			$this->error('验证失败');
		}
		$time = $data[3] + 86400;//lvxinxin 2012-04-05 add
		if(intval($time) <= time()){
			$this->error('验证链接超过24小时');
		}
		
		$this->assign('action',mk_url('front/login/do_reset_pass'));
		$this->assign('email',urldecode($data[0]));					
		$this->display('register/newpsw.html');
		
	}
    
    
    /**
     * 第二步:验证密保问题
     * 
     * @author lvxinxin
     * @date   2011/04/22
     * @access public
     * @param  int $value 用户端口号
     * @param  string $question 用户问题
     * @param  string $answer 用户答案
     * @return JSON
     */
    public function doforget_pass_checkquestion ()
    {
        $state = 0;
		
        $value = $this->input->post('dkcode');
		$question = $this->input->post('questions');
		$answer = $this->input->post('answers');
        if (empty($value) || ! is_numeric($value))
        {
            $state = 0;
            $msg = '您输入的端口号无效，请提供一个有效的端口号。';
        }
        else
        {
			if(empty($question) || empty($answer)) return false;
			$data = array_combine($question,$answer);			
            $user = service('Passport')->verifyUserSecurity($value,$data); //call_soap('ucenter','Passport','verifyUserSecurity',array($value,$first,$second)); //通过端口号查找出一条记录
			// var_dump($data);var_dump(unserialize($user));exit;
            if ($user)
            {
                $state = 1;
                $msg = ''; 
				@$_SESSION['email'] = $value;
				@$_SESSION['msg'] = $msg;
				// set_cache($this->sessionid.'findPsw', $value);                
            }
            else
            {
                $state = 0;
                $msg = '密保问题和答案不一致';
            }
        }
		$this->ajaxReturn('',$msg,$state);
        // die(json_encode(array('state' => $state, 'msg' => $msg)));
    }   

    
    /**
     * 重新设置密码
     * 
     * @author lvxinxin
     * @date   2011/04/22
     * @access public
     * @param  string $passwd 用户密码
     * @param  string $newpwd 用户确认密码
     * @return HTML
     */
    public function do_reset_pass ()
    {
		
        $identifier = $this->input->post('email');
		//echo $identifier.'ttt';exit;
        $passwd = $this->input->post('password');
        $newpwd = $this->input->post('repassword');		
        if (empty($passwd) || $passwd != $newpwd)
        {
            $this->error('两次密码输入不一致');
        }
        if ($passwd != "")
        {
            $flag = service('Passport')->resetUserPassword($identifier,$newpwd);//call_soap('ucenter', 'Passport', 'resetUserPassword',array($identifier,$newpwd));
			//var_dump($flag);exit;
            if ($flag)
            {
            	//  重新登陆    
				unset($_SESSION['email']);
				unset($_SESSION['msg']);
				service('Passport')->set_edit_pwd_status(0,$identifier);
				// $this->showmessage('密码修改成功',2,mk_url('front/index/index'));
                $this->redirect('front/index/index');
            }
            else
            {
                $this->error('密码修改失败');
            }
        }
        else
        {
            $this->error('密码不能为空');
        }
    }
    
	/**
	 * 检查用户是否设置了密保
	 */
	public function checkSecurity()
	{
		
		$dkcode = $this->input->post('dkcode');				
	    $result = service('Passport')->isHasSecurity($dkcode);//call_soap('ucenter','Passport','isHasSecurity',array($dkcode));    
	    if($result)
	    {
	    	$list = config_item('security_list');
    		if(empty($list)) $list = array(
    			'1'=>'填写一部电影',
    			'2'=>'填写一个演员',
    			'3'=>'填写一个卡通形象',
    			'4'=>'填写一首歌曲',
    			'5'=>'填写一部电视剧'
            );
	        $setting_list = service('Passport')->getUserSecurity($dkcode);//call_soap('ucenter','Passport','getUserSecurity',array($dkcode));
    		if($setting_list)
    		{
    	    	$setting_list = unserialize($setting_list);
    	    	$keys = array_keys($setting_list);
    	    	foreach($list as $key=>$one)
    	    	{
    	        	if(!in_array($key,$keys)) unset($list[$key]);
    	    	}
    		}
			//var_dump($list);exit;
    		$this->ajaxReturn($list,'',1);
    		// die(json_encode(array('state'=>1,'data'=>$list)));
	    }
	    else 
	    {
			$this->ajaxReturn('','您输入的端口号未设置密保问题',0);
	        // die(json_encode(array('state'=>0)));
	    }
	}
	
	public function reset_pass(){
		
		$this->assign('email',@$_SESSION['email']);
		$this->assign('action',mk_url('front/login/do_reset_pass'));
		$this->display('register/newpsw.html');
	}
	
}