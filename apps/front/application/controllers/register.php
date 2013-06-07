<?php

/**
 * 注册
 *
 * @author        lvxinxin
 * @date          2011/04/19
 * @version       1.0
 * @description   注册步骤相关功能
 * @history       <author><time><version><desc>
 */
class Register extends MY_Controller
{  	
	private $mduid;	
    public function __construct ()
    {
        parent::__construct();			
		if($this->autoLogin()){
			$this->redirect('main/index/main');
		}		
		$this->load->fastdfs('avatar', '', 'fdfs');	
		$this->load->redis('avatar','','redisdb');		
		$this->uid = @$_SESSION['uid'];
		$this->mduid = md5($this->uid);
    }

    /**
     * 检查端口号
     *
     * @author lvxinxin
     * @date   2012/03/14
     * @access public
     * @param int invatationCode 端口号
     * @param string name 姓名
     */
    function check_dkcode ()
    {   
		
		$this->load->model('invitecodemodel');
        $dk_num = $this->input->post("invatationCode");     
        $duankou_check = $this->invitecodemodel->checkDkCode($dk_num);        
        if (! is_array($duankou_check))
        {
			$this->ajaxReturn('','邀请码错误',0);            
        }
        elseif ($duankou_check['status'] == 1)
        {
			$this->ajaxReturn('','邀请码已注册',0);            
        }        
        else
        {
			$this->ajaxReturn('','',1);            
        }
    }
    
    /**
     * 检测姓名
     * @author lvxinxin
     * @date 2012-04-10
     */
    public function check_name(){
		
    	$dk_num = trim($this->input->post("invatationCode"));
    	$name = trim($this->input->post('name'));//暂时没过滤
    	$name = preg_replace("/(　){2,}/", "", $name); //把全角状态下空格踢除
		$name = preg_replace('/\s+/', '', $name);   //把英文状态下的空格踢除
		$name = preg_replace('/[\n\r\t]/', '', $name); //去掉非space的空格踢除   
		if(!preg_match("/^[\x{4E00}-\x{9FFF}a-zA-Z]+$/u", $name)){
			$this->ajaxReturn('','姓名只能输入中英文',0);			
		}
    	if(empty($name) && empty($dk_num)){
			$this->ajaxReturn('','姓名或邀请码不能为空',0);    		
    	}
		$this->load->model('invitecodemodel');
    	$duankou_check = $this->invitecodemodel->checkDkCode($dk_num);
    	if($duankou_check['name'] != $name){
			$this->ajaxReturn('','姓名和邀请码不一致',0);    		
    	}
    	else{
			$this->ajaxReturn('','',1);    		
    	}
    }

    /**
     * 检查Email
     *
     * @author lvxinxin
     * @date   2012/03/14
     * @access public
     * @param  string email 邮箱
     */
    function check_email ()
    {
		
        $email = strtolower(trim($this->input->post("email")));   
		$dkcode = intval($this->input->post('invatationCode'));
		$email_check  = service('Passport')->checkEmail($email);
        if(empty($dkcode) || $dkcode <= 0) $this->ajaxReturn('','请先填写邀请码',0);
        if (empty($email_check))
        {
			$this->ajaxReturn('','',1);            
        }
        else
        {
			if($email_check['isactive'] == 1 ) $this->ajaxReturn('','邮箱已经被使用',0);
			if(intval($email_check['dkcode']) != $dkcode ) $this->ajaxReturn('','邮箱已经被使用',0);
			$this->ajaxReturn('','',1); 
        }
    }
	/**
	 *注册协议
	 *
	 */
	 public function protocol(){
		$this->display('deal.html');
	 }
    /**
     * 帐户注册
     *
     * @author lvxinxin
     * @date   2011/03/14
     * @access public
     * @param int invatationCode 邀请码(端口号)
     * @param string name 姓名	 
     * @param string email 邮箱
     * @param string pwd 密码
     * @param string pwd_check
     * @param int sex 性别
     * @param int now_nation 国家
     * @param int now_province 省
     * @param int now_city 城市
     * @param int now_town 区
     */
    function index ()
    {
		
        $this->assign('url_login', mk_url('front/login/userlogin'));
        $this->assign('url_reg', mk_url('front/register/index'));
        $this->assign('forget_pass', mk_url('front/login/forget_pass'));
		$this->assign('service',mk_url('main/service/index'));
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
		 					'school_name'=>array(),
							'people_level'=>'{"type":"1"}',
							'school_level'=>'{"type":"4"}',
							'company_level'=>'{"type":"4"}'
							
        	);        	 
        	service('RelationIndexSearch')->addOrUpdateBasalInfoOfPeople($search); 
			unset($_SESSION['regData']);
        	$this->assign('regstep',mk_url('front/register/avatar'));
        	$this->assign('main',mk_url('main/index/main'));
            $this->display('register/regSuccess.html');
        }
        
    }
	//设置密保
	public function mb(){
		if(empty($_SESSION['uid']) || $_SESSION['user']['status'] == 0) $this->redirect('front/login/index');
		$check_mb = service('Passport')->isHasSecurity(@$_SESSION['user']['dkcode']);
		if($check_mb) $this->redirect('main/setting/settingSecurity');
		$list = config_item('security_list');
		if(empty($list)) $list = array(
			'1'=>'填写一部电影',
			'2'=>'填写一个演员',
			'3'=>'填写一个卡通形象',
			'4'=>'填写一首歌曲',
			'5'=>'填写一部电视剧'
		);
		$html = '';
		$selected='0';
		foreach($list as $key=>$one)
		{
			if($key == $selected)
			{
				$html .= '<option value="' . $key . '" selected>' . $one . '</option>';
			}
			else 
			{
				$html .= '<option value="' . $key . '">' . $one . '</option>';
			}
		}
		$this->assign('dkcode',@$_SESSION['user']['dkcode']);
		$this->assign('return',mk_url('front/register/avatar'));
		$this->assign('next',mk_url('main/index/main'));
		$this->assign('select',$html);
		$this->display('register/reg_security.html');
	}
	
	//设置密保
	public function setmb(){
		
		$dkcode = $this->input->post('dkcode');
		$question = $this->input->post('questions');
		$answer = $this->input->post('answers');
		if(empty($question) || empty($answer)) return false;
		$data = array_combine($question,$answer);
		
		$flag = service('Passport')->setUserSecurity($dkcode,$data);		
		if($flag){
			$this->ajaxReturn(mk_url('main/index/main'),'',1);			
		}
		else{
			$this->ajaxReturn('','密保设置失败，您可以尝试重新设置或是跳过此步',0);			
		}
		
		
	}
	/*********************************** 头像操作_begin ********************************/
	/**
	 * 个人信息注册,个人头像页面
	 *
	 * @author lvxinxin
	 * @date   2011/04/20
	 * @access public
	 * @param string $uid 用户ID
	 */
	public function avatar(){
		if(empty($_SESSION['uid']) || $_SESSION['user']['status'] == 0) $this->redirect('front/login/index');
		$this->assign('saveUrl',mk_url('front/register/avatar_save'));
		$this->assign('cameraPostUrl',mk_url('front/register/avatar_camera_save'));	
	
		$this->assign('mb',mk_url('front/register/mb'));
		$this->assign('avatar_upload',mk_url('front/register/avatar_upload'));
		$this->assign('avatar_pic',mk_url('front/register/avatar_pic'));
		$this->assign('avatar_photo',mk_url('front/register/avatar_photo'));
		$this->assign('avatar',get_avatar($this->uid,'b'));
		$this->display('register/avatar.html');
	}
	public function avatar_pic(){
		if(empty($_SESSION['uid']) || $_SESSION['user']['status'] == 0) $this->redirect('front/login/index');
		$this->assign('action',mk_url('front/register/avatar_upload'));		
		$this->assign('photo',mk_url('front/register/avatar_photo'));
		$this->assign('mb',mk_url('front/register/mb'));
		$this->display('register/avatar_pic.html');
	}
	public function avatar_photo(){
		if(empty($_SESSION['uid']) || $_SESSION['user']['status'] == 0) $this->redirect('front/login/index');
		$this->assign('mb',mk_url('front/register/mb'));		
		$this->assign('avatar_pic',mk_url('front/register/avatar_pic'));
		$this->display('register/photograph.html');
	}

	/**
	 * 上传头像
	 *
	 * @author lvxinxin
	 * @date   2011/04/20
	 * @access public
	 */
	public function avatar_upload(){	
		$upload_config['upload_path'] = config_item('avatar_root') . '/';//FWPHP_PATH . 'apps/files/'; //上传路径
        $upload_config['allowed_types'] = 'jpg|jpeg|gif|png'; //文件上传类型
        $upload_config['overwrite'] = true; //同名文件覆盖
        $upload_config['file_name'] = $this->mduid.'.jpg'; //指定文件名
        $upload_config['max_size'] = 4096;
        include_once(EXTEND_PATH.'libraries/DK_Upload.php');
		include_once(EXTEND_PATH.'vendor/File_util.php');
		$this->file_util = new File_util();
		$this->myupload = new DK_Upload($upload_config);
        if (! is_dir($upload_config['upload_path']))
        {
            $this->file_util->createDir($upload_config['upload_path']);
        }        
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");		
        if ($this->myupload->do_upload('Filedata'))
        {			
        	$img_info = $this->myupload->data();					
			if($img_info['image_width'] > 2800 || $img_info['image_height'] > 2800){				
				$s = $this->pro_avatar($upload_config['upload_path'].md5($this->uid).'.jpg', $this->uid, $img_info['image_width'], $img_info['image_height']);
				if(!$s){					
					echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
					echo '<script type="text/javascript">alert("' . $this->myupload->display_errors('','') . '");</script>';
            		exit;
				}				
			}
            $web_path = config_item('avatar_webroot') . $this->mduid . '.jpg?v='.time();
			if(WEB_ROOT == 'http://www.duankou.com/'){
				$api = 'http://127.0.0.1/index.php?app=album&controller=api&action=uploadHead&dkcode=' . $_SESSION['user']['dkcode'] . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $_SESSION['uid'];
			}
			else{
				$api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$_SESSION['uid'],'dkcode'=>$_SESSION['user']['dkcode']));
			}
			// $api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'dkcode'=>$this->dkcode));
           
            $flag = call_curl($api);		                    
			$_SESSION['avatartotl'] = json_decode($flag,true);			
            		
            echo '<script type="text/javascript">window.parent.hideLoading();window.parent.buildAvatarEditor("' . $this->mduid . '","' . $web_path .
         	'","photo");</script>';        
        	exit();
        }
        else
        {			
            $this->assign('action',mk_url('front/register/avatar_upload'));		
			$this->assign('photo',mk_url('front/register/avatar_photo'));
			$this->assign('mb',mk_url('front/register/mb'));
			$this->display('register/avatar_pic.html');           
            echo '<script type="text/javascript">window.parent.$.alert("' . $this->myupload->display_errors('',''). '");parent.hideLoading();</script>';
            exit;
        }       
		
		
	}
	
	


   

	/**
	 * 保存本地上传的头像图片
	 *
	 * @author lvxinxin
	 * @date   2011/04/20
	 * @access public
	 */
	
	public function avatar_save(){		
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");              
        $pic_size = $this->input->get('type');
        $type = isset($pic_size) ? $pic_size : 'big';		
        if ($type == 'big')
        {
            $type = 'b';            
        }    
        
        $pic_path = config_item('avatar_webroot') . $this->mduid . "_" . $type . ".jpg";        
        $file_addr = config_item('avatar_root'); 
		include_once(EXTEND_PATH.'vendor/File_util.php');
		$this->file_util = new File_util();
        if (! file_exists($file_addr))
        {
            $this->file_util->createDir($file_addr);
        }     
        $pic_abs_path = $file_addr . substr($pic_path, strrpos($pic_path, '/'));		
        if(!file_put_contents($pic_abs_path, file_get_contents("php://input")))
		{
			$d = new pic_data();
			$d->data->urls[0] = $pic_path;
			$d->status = 0;
			$d->statusText = '上传失败!';
			die(json_encode($d));
			
		}
        $avtar_img = imagecreatefromjpeg($pic_abs_path);
        imagejpeg($avtar_img, $pic_abs_path, 100); 		
		$fpath = realpath($pic_abs_path);
		if($fpath != false){			
			$flag = $this->redisdb->get($this->uid);
			$setting = getConfig('fastdfs','avatar');
			if(empty($flag)){
				@service('credit')->avatar();
				$Mfdata = $this->fdfs->uploadFile($fpath,'jpg');
				$fast_path = 'http://' . $setting['host'] . '/' . $setting['group'] . '/' .$Mfdata['filename'];
				$this->redisdb->set($this->uid,$fast_path);
			}
			else{
				$this->_delete_fastdfs_file();
				@service('credit')->avatar();
				$Mfdata = $this->fdfs->uploadFile($fpath,'jpg');
				$fast_path = 'http://' . $setting['host'] . '/' . $setting['group'] . '/' .$Mfdata['filename'];
				$this->redisdb->set($this->uid,$fast_path);
			}
		}		
		
		$this->create_avatar($this->mduid, $file_addr, $avtar_img); 
		$this->_delete_local_avatar($file_addr);
		
		if(!empty($_SESSION['avatartotl'])){
			$img_size = json_decode($_SESSION['avatartotl']['notes'],true);
			if(empty($img_size)) {
				$h = 125;
				$w = 125;
			}
			else
			{
				$h = @$img_size['f']['h'];
				$w = @$img_size['f']['w'];
			}
			$data = array(
				 'uid'=>$this->uid,
				 'dkcode'=>$_SESSION['user']['dkcode'],
				 'uname'=>$_SESSION['user']['username'],
				 'permission'=>4,
				 'from'=>5,
				 'type'=>'change',
				 'dateline'=>time(),
				 'ctime'=>time(),			
				 'filename'=>$_SESSION['avatartotl']['filename'],			
				 'union' => 'face',
				 'fid' =>$_SESSION['avatartotl']['pid'],
				 'groupname'=>$_SESSION['avatartotl']['groupname'],
				 'imgtype'=>$_SESSION['avatartotl']['type'],
				 'height'=>$h,
				 'width'=>$w
			);
			
			api('Timeline')->addTimeLine($data);
			unset($_SESSION['avatartotl']);
		}
		
        $d = new pic_data();
        $d->data->urls[0] = $pic_path;
        $d->status = 1;
        $d->statusText = '上传成功!';
        die(json_encode($d));	
	}
	
	public function _delete_fastdfs_file(){		
		$this->_delFile('_ss.jpg');
		$this->_delFile('_s.jpg');
		$this->_delFile('_mm.jpg');
		$this->_delFile('_m.jpg');
		$this->_delFile('_b.jpg');
		$this->_delFile();
	}
	
	public function _delete_local_avatar($file_addr){        
        $avatar_ss = $file_addr . '/' . $this->mduid . '_ss.jpg';		
        $avatar_s = $file_addr . '/' . $this->mduid . '_s.jpg';
		$avatar_mm = $file_addr . '/' . $this->mduid . '_mm.jpg'; //--add
        $avatar_m = $file_addr . '/' . $this->mduid . '_m.jpg';
        $avatar_b = $file_addr . '/' . $this->mduid . '_b.jpg';
		$avatar = $file_addr . '/' .  $this->mduid . '.jpg';
        if (file_exists($avatar_ss))
        {			
			$this->_saveFile($avatar_ss,'_ss');
            @unlink($avatar_ss);
		}
        if (file_exists($avatar_s))
        {
			$this->_saveFile($avatar_s,'_s');
            @unlink($avatar_s);
		}
		if (file_exists($avatar_mm))  //--add
        {
			$this->_saveFile($avatar_mm,'_mm');
            @unlink($avatar_mm);
		}
        if (file_exists($avatar_m))
        {
			$this->_saveFile($avatar_m,'_m');
            @unlink($avatar_m);
		}
        if (file_exists($avatar_b))
        {
			$this->_saveFile($avatar_b,'_b');
            @unlink($avatar_b);
		}
		if (file_exists($avatar))
        {
            @unlink($avatar);
        }
	}
	
	public function create_avatar($uid,$file_addr,$res){
		
        if (empty($uid))
        {
            return false;
        }
        $s30_res = imagecreatetruecolor(30, 30);
        $s50_res = imagecreatetruecolor(50, 50);
		$s65_res = imagecreatetruecolor(65, 65);//--add
        $s100_res = imagecreatetruecolor(100, 100);
        imagecopyresampled($s30_res, $res, 0, 0, 0, 0, 30, 30, 125, 125);
        imagecopyresampled($s50_res, $res, 0, 0, 0, 0, 50, 50, 125, 125);
		imagecopyresampled($s65_res, $res, 0, 0, 0, 0, 65, 65, 125, 125);//--add
        imagejpeg($s30_res, $file_addr . '/' . $uid . '_ss.jpg', 100);
        imagejpeg($s50_res, $file_addr . '/' . $uid . '_s.jpg', 100);        
        imagejpeg($s65_res, $file_addr . '/' . $uid . '_mm.jpg', 100);//--add
        imagecopyresampled($s100_res, $res, 0, 0, 0, 0, 100, 100, 125, 125);        
        imagejpeg($s100_res, $file_addr . '/' . $uid . '_m.jpg', 100);        
        imagedestroy($s30_res);
        imagedestroy($s50_res);
		imagedestroy($s65_res);//--add
        imagedestroy($s100_res);        
        imagedestroy($res);     
		
	}
	/**
	 *保存摄像头头像
	 */
	public function avatar_camera_save(){		
		@header("Expires: 0");
		@header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
		@header("Pragma: no-cache");
		// $this->load->library('file_util');	
		
		$pic_path = config_item('avatar_webroot').$this->mduid."_b.jpg";		
		$file_addr = config_item('avatar_root');
        include_once(EXTEND_PATH.'vendor/File_util.php');
		$this->file_util = new File_util();
		if(!file_exists($file_addr)){			
			$this->file_util->createDir($file_addr);
		}		
		$pic_abs_path = $file_addr.substr($pic_path,strrpos($pic_path,'/'));
		
		if(!file_put_contents($pic_abs_path, file_get_contents("php://input")))
        {
			$d = new pic_data();
			$d->data->urls[0] = $pic_path;
			$d->status = 0;
			$d->statusText = '拍摄失败';
			die(json_encode($d));
		}
		$avtar_img = imagecreatefromjpeg($pic_abs_path);
		imagejpeg($avtar_img,$pic_abs_path,100);		
		
		/*---save to album--	*/	
		$web_path = config_item('avatar_webroot') . $this->mduid . '_b.jpg?v=' . time();
		if(WEB_ROOT == 'http://www.duankou.com/'){
			$api = 'http://127.0.0.1/index.php?app=album&controller=api&action=uploadHead&dkcode=' . $_SESSION['user']['dkcode'] . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $_SESSION['uid'];
		}
		else{
			$api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$_SESSION['uid'],'dkcode'=>$_SESSION['user']['dkcode']));
		}
		// $api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid));
		$flag = call_curl($api);		
		$_SESSION['avatartotl'] = json_decode($flag,true);
			
		$d = new pic_data();
		$d->data->urls[0] = $pic_path;
		$d->status = 1;
		$d->statusText = '上传成功!';
		die(json_encode($d));

	}
  
	
	/**
	 * 更换email
	 * @author lvxinxin
	 * @date 2012-03-29
	 */
	public function change_email(){		
		$nowEmail = strtolower(trim($this->input->post('nowEmail')));
		$newEmail = strtolower(trim($this->input->post('newEmail')));
		$dkcode = trim($this->input->post('dkcode'));
				
		if(empty($newEmail) || !check_email($newEmail) || empty($dkcode)){
			$this->ajaxReturn('','新的邮件地址不能为空或者邮件地址不正确',0);				
		}
		$flag = service('Passport')->changeEmail($nowEmail,$newEmail,$dkcode);//call_soap('ucenter','Passport','changeEmail',array($nowEmail,$newEmail,$dkcode));
		if($flag === 3){
			$this->ajaxReturn('','原邮箱已经被激活,禁止更新换邮箱',0);			
		}
		if(empty($_SESSION['regData'])) {
			$this->ajaxReturn('','获取数据失败',0);			
		}
		else{
			$_SESSION['regData']['email'] = $newEmail;
		}
		$mailtype = substr($newEmail,(strrpos($newEmail,'@')+1));	
		$mail = config_item('mail');
		if(array_key_exists($mailtype,$mail)){
		
			$mailtype = $mail[$mailtype];
		}
		else{
			$mailtype = 0;
		}
		$str = $newEmail . "\t" . $_SESSION['regData']['dkcode'] . "\t" . $_SESSION['regData']['passwd'] . "\t" . $_SESSION['regData']['regdate'] . "\t" . $_SESSION['regData']['username'];
		$code = service('Passport')->getCrypt($str);
		
		if($flag === true){
			// service('Mail')->sendEmail('sunlufu@duankou.com','test','更换邮箱/注册激活',4,mk_url('front/register/do_active_userinfos',array('active_code'=>$code)));
			service('Mail')->sendEmail($newEmail,$_SESSION['regData']['username'],'注册激活',4,mk_url('front/register/do_active_userinfos',array('active_code'=>$code)));
			//unset($_SESSION['regData']);
			$this->ajaxReturn($mailtype,'发送邮件成功',1);					
		}
		else{
			$this->ajaxReturn('','邮箱已被使用',0);			
		}
	}
	/**
	 *重发激活验证邮件
	 *@author lvxinxin
	 *@date 2012-05-25
	 */
	 public function reSendEmail(){		
		$nowEmail = strtolower(trim($this->input->post('nowEmail')));
		$flag = service('Passport')->reSendEmail($nowEmail);
		if($flag === 3){
			$this->ajaxReturn('','该帐号已经激活，请不要重复发送邮件',0);			
		}
		if(empty($nowEmail) || !check_email($nowEmail)){
			$this->ajaxReturn('','数据不完整或邮箱地址不正确',0);			
		}			
		if(empty($_SESSION['regData'])) {
			$this->ajaxReturn('','获取数据失败',0);			
		}
		else{
			$_SESSION['regData']['email'] = $nowEmail;
		}
		$str = $nowEmail . "\t" . $_SESSION['regData']['dkcode'] . "\t" . $_SESSION['regData']['passwd'] . "\t" . $_SESSION['regData']['regdate'] . "\t" . $_SESSION['regData']['username'];		
				
		$code = service('Passport')->getCrypt($str);
		// service('Mail')->sendEmail('sunlufu@duankou.com','test','注册激活',4,mk_url('front/register/do_active_userinfos',array('active_code'=>$code)));
		service('Mail')->sendEmail($nowEmail,$_SESSION['regData']['username'],'注册激活',4,mk_url('front/register/do_active_userinfos',array('active_code'=>$code)));
		//unset($_SESSION['regData']);
		$this->ajaxReturn('','',1);			
	 }
	
	/**
	 * 处理头像上传图片的最大高度和宽度  默认是w:2800 || h:2800
	 * @author lvxinxin
	 * @date 2012-04-19
	 */
	function pro_avatar($avatar_path,$w,$h){		
		$wavg = sprintf('%.2f',2800/$w);
		$havg = sprintf('%.2f',2800/$h);
		if($wavg < $havg){
			$navg = $wavg;
		}
		else {
			$navg = $havg;
		}
		$nw = intval($w * $navg);
		$nh = intval($h * $navg);		
		$config = array(
					'image_library'=>'GD2',
					'source_image'=>$avatar_path,
					'width'=>$nw,
					'height'=>$nh		
		);
		$this->load->library('image_lib'); 
		$this->image_lib->initialize($config); 		
		if ( ! $this->image_lib->resize())
		{
    		echo  $this->image_lib->display_errors('','');
		}
		else{
			return true;
		}
	}
	
	public function _getMasterFile(){
		$fast_file = $this->redisdb->get($this->uid);
		$f_info = parse_url($fast_file);
		if(empty($f_info['path'])) return false;
		return preg_replace('/\/[A-Za-z0-9]*\//is','',$f_info['path'],1);
	}
	
	public function _saveFile($file,$size){			
		if(empty($file) || empty($size)) return false;
		$fpath = realpath($file);		
		return $f = $this->fdfs->uploadSlaveFile($fpath,$this->_getMasterFile(),$size,'jpg');	
	}
	
	public function _delFile($size = null){		
		if(empty($size)){
			$this->fdfs->deleteFile('',$this->_getMasterFile());
			$this->redisdb->delete($this->uid);
			return true;
		}
		$fname = rtrim($this->redisdb->get($this->uid),'.jpg').$size;
		$f = $this->fdfs->deleteFile('',$fname);
		if($f){
			$furl = MISC_ROOT . 'img/default/avatar_' . $size . '.gif';			
			return true;
		}
		else{
			return false;
		}
	}
}

class pic_data{
	public $data;
	public $status;
	public $statusText;
	public function __construct(){
		$this->data->urls = array();
	}
}
?>