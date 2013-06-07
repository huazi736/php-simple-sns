<?php

/**
 * 手机接口
 * 
 * @author lxx<lvxinxin@duankou.com>
 * @date <2012/07/23>
 *
 */
class Mobile extends MY_Controller
{
	/**
	 * 构造函数
	 */	
	public function __construct()
	{	
		parent::__construct();
	}
	
	public function login(){		
		$this->load->library('user_agent');
		if(!$this->agent->is_mobile()){
			die($this->_create_xml(array('status'=>0,'msg'=>'非法访问'))); //这些代码可以放在__construct里
		}
		$name = addslashes((trim($this->input->post('login_name'))));
		$passwd = $this->input->post('passwd');
		$type = $this->input->post('returntype');		
		if(empty($type)) die($this->_create_xml(array('status'=>0,'msg'=>'类型未知'),$type));
		if(empty($name) || empty($passwd)) die($this->_create_xml(array('status'=>0,'msg'=>'用户名和密码不能为空'),$type));
		
		$user = service('Passport')->loginLocal($name,$passwd);
		if ($user && intval($user['status']) == 0)
        {
			 die($this->_create_xml(array('status'=>0,'msg'=>'帐号被停用'),$type));                  
        }
		elseif(is_array($user))
		{
			die($this->_create_xml(array('status'=>1,'msg'=>'登陆成功','data'=>array('uid'=>$user['uid'],'username'=>$user['username'])),$type));
		}
		else{
			die($this->_create_xml(array('status'=>0,'msg'=>'登陆失败'),$type));
		}
	}	
	
	public function register(){
		$this->load->library('user_agent');
		if(!$this->agent->is_mobile()){
			die($this->_create_xml(array('status'=>0,'msg'=>'非法访问')));
		}
		$type = $this->input->post('returntype');
		if(empty($type)) die($this->_create_xml(array('status'=>0,'msg'=>'类型未知'),$type));
		//端口号
        $dk_num = addslashes($this->input->post("invatationCode"));
        //全名
        $name = addslashes($this->input->post("name"));
        //帐号
        $email = addslashes(strtolower(trim($this->input->post("email"))));
        //新密码
        $pwd = $this->input->post("password");
        //确认密码
        $pwd_check = $this->input->post("repassword");
        //性别
        $sex = $this->input->post("sex");
		//现居住地汉字
		$area = $this->input->post("area");
		
		if(!true){
			die($this->_create_xml(array('status'=>0,'msg'=>'请您确定端口网服务条款!'),$type));			
		}elseif( !$dk_num || !$name || !$email || !$pwd || !$pwd_check || !$sex ){
			die($this->_create_xml(array('status'=>0,'msg'=>'信息不完整'),$type));					
		}elseif($pwd != $pwd_check){
			die($this->_create_xml(array('status'=>0,'msg'=>'确定密码不正确'),$type));						
		}elseif(!check_email($email)){
			die($this->_create_xml(array('status'=>0,'msg'=>'请填写正确的email'),$type));					
		}elseif(strlen($name) > 30){
			die($this->_create_xml(array('status'=>0,'msg'=>'姓名长度不可超过10字'),$type));								
		}elseif(strlen($email) > 64){
			die($this->_create_xml(array('status'=>0,'msg'=>'邮箱不能超过64个字符'),$type));							
		}elseif(strlen($dk_num) > 10 ){
			die($this->_create_xml(array('status'=>0,'msg'=>'您的邀请码不正确'),$type));							
		}
		if(isset($area)){
			$area = str_replace(',',' ',$area);
		}
		else{
			$area = '';
		}
		
		$now_nation = $this->input->post("now_nation");        //国
        $now_province = $this->input->post("now_province");//省
        $now_city = $this->input->post("now_city");//市
        $now_town = $this->input->post("now_town");//区
		//现居住地id
		if($now_province != '-1' && $now_city == '-1') $city = $now_province . '0102';
		if($now_city != '-1') $city = $now_city . '02';
		if($now_nation == '-1') $city = 0; 
		if($now_nation != '-1' && $now_province == '-1') $city = 0;
		
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
		
		$this->load->model('invitecodemodel');
    	$dkdata = $this->invitecodemodel->checkDkCode($dk_num);
		if(!$dkdata){
			die($this->_create_xml(array('status'=>0,'msg'=>'邀请码不存在'),$type));				
		}
		elseif($dkdata['name'] != $data['username']){
			die($this->_create_xml(array('status'=>0,'msg'=>'姓名和邀请码不一致'),$type));						
		}
		elseif ($dkdata['status'] == 1){
			die($this->_create_xml(array('status'=>0,'msg'=>'邀请码已经被使用'),$type));						
		}
		
		$reg = service('Passport')->saveRegister($data);
        if ($reg['status'] != 1)
        {
			die($this->_create_xml(array('status'=>0,'msg'=>$reg['msg']),$type));       	        
        }
		
		$mailtype = substr($email,(strrpos($email,'@')+1));	
		$mail = config_item('mail');
		if(array_key_exists($mailtype,$mail)){
			$mailtype = $mail[$mailtype];
		}
		else{
			$mailtype = null;
		}
		die($this->_create_xml(array('status'=>1,'msg'=>'注册成功','email'=>$mailtype),$type)); 
	}
	

	
	private function _create_xml($data = array(),$type = 'json'){		
		if($type == 'json'){
			return json_encode($data);
		}
		else{
			$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
			$xml .="<login>\n";		
			foreach($data as $key=>$value){
				if(is_array($value)){
					$xml .= "<".$key.">" ;
					foreach($value as $k=>$v){
						$xml .= "<".$k.">" . $v . "</".$k.">\n";
					}
					$xml .= "</".$key.">\n";
				}
				else{
					$xml .= "<".$key.">" . $value . "</".$key.">\n";
				}
				
			}
			$xml .="</login>\n";
			return $xml;
		}
		
	}
	

		
}