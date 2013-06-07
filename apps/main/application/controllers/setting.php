<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 用户设置
 * @author mawenpei
 * @date <2012/03/25>
 */
class setting extends MY_Controller 
{
	protected $userinfo;
	//private $javahost = 'http://analytics.duankou.com';
	public function __construct()
	{
		parent::__construct();
		$this->userinfo = $this->user;
		$this->assign('user',$this->user);
		$this->load->model('settingmodel', 'setting');
	}
	/**
	 * 首页
	 */
	public function index()
	{
		$this->settingAccount();
	}

	/**
	 * 一般设置
	 */
	public function settingAccount()
	{
	    $this->assign('login_name',$this->userinfo['username']);
	    $this->assign('login_email',$this->userinfo['email']);
	    //$this->assign('login_lastupdatepwdtime',$this->userinfo['lastupdatepwdtime']);
	    //$this->assign('select',$this->createSelect('0',true));
	    
	    $this->display("setting-userinfo/setting_account.html");
	}
	
	/**
	 * 检查用户是否设置了密保
	 */
	public function checkSecurity()
	{
		return false;
	    //$result = call_soap('ucenter','Passport','isHasSecurity',array($this->userinfo['dkcode']));
	    $result = service('Passport')->isHasSecurity($this->userinfo['dkcode']);
	    if($result)
	    {
	    	$this->ajaxReturn(array(true),'',1,'json');
	        //die(json_encode(true));
	    }
	    else 
	    {
	    	$this->ajaxReturn(array(false),'',1,'json');
	        //die(json_encode(false));
	    }
	}
	
	/**
	 * 验证密保问题
	 */
	public function verifySecurity()
	{
		return false;
	    $mb_question_id = $this->input->post('question');
	    $mb_answer = $this->input->post('answer');
	    //$result = call_soap('ucenter','Passport','verifyUserSecurity',array($this->userinfo['dkcode'],$mb_question_id,$mb_answer));
	    $result = service('Passport')->verifyUserSecurity($this->userinfo['dkcode'],$mb_question_id,$mb_answer);
	    if($result)
	    {
	    	$this->ajaxReturn(array(true),'',1,'json');
	        //die(json_encode(true));
	    }
	    else 
	    {
	    	$this->ajaxReturn(array(false),'',0,'json');
	        //die(json_encode(false));
	    }
	}
	
	
	
	/**
	 * 验证旧密码
	 */
	public function verifyOldPasswd()
	{
		return false;
		$ret = array('state'=>0,'msg'=>'密码不在存');
	    $oldpasswd = $this->input->post('old');
	    //$result = call_soap('ucenter','Passport','checkUserAuth',array($this->userinfo['dkcode'],$oldpasswd));
	    $result = service('Passport')->checkUserAuth($this->userinfo['dkcode'],$oldpasswd);
	    if($result)
	    {
	    	$ret['state'] = 1;
	    	$ret['msg'] = '密码在存';
	    	$this->ajaxReturn($ret,'',1,'json');
	        //die(json_encode($ret));
	    }
	    else 
	    {
	    	$this->ajaxReturn($ret,'',0,'json');
	        //die(json_encode($ret));
	    }
	}
	
	/**
	 * 重置密码
	 * 成功返回json格式state 为1
	 * 失败返回json格式state为0
	 */
	public function resetPasswd()
	{
	    $oldpasswd = $this->input->post('old_pwd');
	    $pwd_new = $this->input->post('new_pwd');
	    
	    $url = mk_url('main/setting/settingAccount');
	    $ret = array('state'=>0,'url'=>$url,'msg'=>'');
		
		//验证旧密码是否合法
		$oldcheck = $this->checkpsd($oldpasswd);
		if(!$oldcheck){
			$ret['msg']='密码修改失败';
			$this->ajaxReturn(array('url'=>$ret['url']),$ret['msg'],'0','json');
		}
		//验证新密码是否合法
		$newcheck = $this->checkpsd($pwd_new);
		if(!$newcheck){
			$ret['msg']='密码修改失败';
			$this->ajaxReturn(array('url'=>$ret['url']),$ret['msg'],'0','json');
		}
		
	    //验证密码合法性
	    //$result = call_soap('ucenter','Passport','checkUserAuth',array($this->userinfo['dkcode'],$oldpasswd));
	    $result = service('Passport')->checkUserAuth($this->userinfo['dkcode'],$oldpasswd);
	    if($result)
	    {
	        //$change = call_soap('ucenter','Passport','resetUserPassword',array($this->userinfo['dkcode'],$pwd_new));
	        $change = service('Passport')->resetUserPassword($this->userinfo['dkcode'],$pwd_new);
	        if($change)
	        {
				//更新SESSION中修改密码的最后时间。
				$_SESSION['user']['lastupdatepwdtime'] = time();
				
	        	$ret['state']=1;
	        	$ret['msg']='密码修改成功';
	        	$this->ajaxReturn(array('url'=>$ret['url']),$ret['msg'],'1','json');
	        	//$this->ajaxReturn($ret,'',1,'json');
	        	//die(json_encode($ret));
	        }
	        else 
	        {
	           $ret['msg']='密码修改失败';
			   $this->ajaxReturn(array('url'=>$ret['url']),$ret['msg'],'0','json');
	           //$this->ajaxReturn($ret,'',0,'json');
	           //die(json_encode($ret));
	        }
	    }
	    else
	    {
			 $ret['msg']='旧密码错误';
			 $this->ajaxReturn(array('url'=>$ret['url']),$ret['msg'],'0','json');
			 //$this->ajaxReturn($ret,'',0,'json');
			 //die(json_encode($ret));
	    }
	    
	}
	
	/**
	 * 创建密保问题
	 */
    private function createSelect($selected='0',$setting = false)
    {
    	$list = config_item('security_list');
    	if(empty($list)) $list = array(
    		'1'=>'我最喜欢的电影是？',
			'2'=>'我最喜欢的演员是？',
			'3'=>'我最喜欢的卡通形象是？',
			'4'=>'我最喜欢的歌曲是？',
			'5'=>'我最喜欢的电视剧是？',
			'6'=>'我母亲的生日是？',
			'7'=>'我父亲的生日是？',
			'8'=>'我最喜欢的食物是？',
			'9'=>'我的初中班主任是？',
			'10'=>'对我影响最大的人是？'
            );
    	//$setting_list = call_soap('ucenter','Passport','getUserSecurity',array($this->userinfo['dkcode']));
    	//$setting_list = service('Passport')->getUserSecurity($this->userinfo['dkcode']);
    	if($setting)
    	{
			$setting_list = service('Passport')->getUserSecurity($this->userinfo['dkcode']);
			if($setting_list){
				$setting_list = unserialize($setting_list);
				$keys = array_keys($setting_list);
				foreach($list as $key=>$one)
				{
					if(!in_array($key,$keys)) unset($list[$key]);
				}
			}
    	}
    	$html = '';
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
    	return $html;
    }

	/**
	 * 安全设置
	 */
	public function settingSecurity()
	{
	    $this->assign('login_name',$this->userinfo['username']);
	    $this->assign('select',$this->createSelect());
		$this->assign('login_lastupdatepwdtime',$this->userinfo['lastupdatepwdtime']);
	    $this->display("setting-userinfo/setting_security.html");
	}
	
    /**
	 * 设置密保问题
	 */
    public function setSecurity()
	{
		return false;
	    $mb_question_id = $this->input->post('question');	    
	    $mb_answer = $this->input->post('answer') ? $this->input->post('answer') : $this->input->post('newanswer');
	    $mb_oldanswer = $this->input->post('oldanswer');
	    
	    //$result = call_soap('ucenter','Passport','isHasSecurity',array($this->userinfo['dkcode']));
	    $result = service('Passport')->isHasSecurity($this->userinfo['dkcode']);
	    $result = $result ? unserialize($result) : false;

	    $ret = array('state'=>0,'msg'=>'');
	    if($result && isset($result["{$mb_question_id}"]) && !empty($result["{$mb_question_id}"]))
	    {
	        if($result["{$mb_question_id}"] != $mb_oldanswer)
	        {
	           $ret['msg'] = '密保问题的原始答案错误';
	           $this->ajaxReturn($ret,'',1,'json');
	            //die(json_encode($ret));
	        }
	    }
	    if(!$mb_answer)
	    {
	        $ret['msg'] = '密保问题的答案不能为空';
	         $this->ajaxReturn($ret,'',1,'json');
	         //die(json_encode($ret));
	    }
	    //$result = call_soap('ucenter','Passport','setUserSecurity',array($this->userinfo['dkcode'],$mb_question_id,$mb_answer));
	    $result = service('Passport')->setUserSecurity($this->userinfo['dkcode'],$mb_question_id,$mb_answer);
	    if($result)
	    {
	    	$ret['state']=1;
	         $ret['msg'] = '密保问题设置成功';
	         $this->ajaxReturn($ret,'',1,'json');
	        //die(json_encode($ret));
	    }
	    else 
	    {
	        $ret['msg'] = '密保问题设置失败';
	        $this->ajaxReturn($ret,'',1,'json');
	        //die(json_encode($ret));
	    }
	    
	}
	
	/**
	 * 密保问题是否存在
	 */
	public function isExistsSecurity()
	{
		return false;
	    $mb_question_id = $this->input->post('question');
	    //$result = call_soap('ucenter','Passport','isHasSecurity',array($this->userinfo['dkcode']));
	    $result = service('Passport')->isHasSecurity($this->userinfo['dkcode']);
	    $result = $result ? unserialize($result) : false;
	    if($result && isset($result["{$mb_question_id}"]) && !empty($result["{$mb_question_id}"]))
	    {
	    	$this->ajaxReturn(array(true),'',1,'json');
	       //die(json_encode(true));
	    }
	    else 
	    {
	    	$this->ajaxReturn(array(false),'',1,'json');
	        //die(json_encode(false));
	    }
	}
	
	//以下是系统设置修改代码  by:sunlufu at:2012-7-3
	
	//修改流程是否进行完毕
	public function isModover(){
		return false;
		$ret = $this->setting->getSetting($this->userinfo['dkcode']);
		if(empty($ret['sendtime']) or empty($ret['updateemail'])) {
			$this->ajaxReturn('','','0','json');
		} else {
			$this->ajaxReturn(array('updateemail'=>$ret['updateemail']),'','1','json');
		}
	}

	//重新发送邮件
	public function anewEmail(){
		return false;
		$ret = $this->setting->getSetting($this->userinfo['dkcode']);
		if(empty($ret['sendtime']) or empty($ret['updateemail'])) $this->ajaxReturn('','','0','json');
		
		$time = time();
		$modtime = $this->setting->modSendtime($this->userinfo['dkcode'], $time);
		if($modtime != 1) $this->ajaxReturn('','','0','json');
		
		//获取加密后的url
		$encodeurl = $this->getencodeurl($ret['updateemail'], $time);
		
		//发送邮件
		$modret = $this->sendModEmail($ret['updateemail'], $encodeurl);
		if($modret === true) {
			$this->ajaxReturn('','','1','json');
		} else {
			$this->ajaxReturn('','邮件发送失败，请重新发送','0','json');
		}
	}

	//取消修改邮箱
	public function cancelEmail(){
		return false;
		$ret = $this->setting->cancelEmail($this->userinfo['dkcode']);
		if($ret == 1){
			$this->ajaxReturn('','','1','json');
		} else {
			$this->ajaxReturn('','','0','json');
		}
	}

	//修改邮箱
	public function modEmail(){
		return false;
		$psd = $this->input->post('psd');
		$email = $this->input->post('email');
		if(empty($psd) or empty($email) or !check_email($email)) $this->ajaxReturn('','','0','json');
		//判断邮箱是否可用1
		$ableemail = service('User')->getUserInfo($email, 'email', array('uid'));
		if(!empty($ableemail)){
			$this->ajaxReturn('','','3','json');
		}
		
		//判断邮箱是否可用2
		$settingemail = service('UserWiki')->settingemail($email);
		if(!$settingemail){
			$this->ajaxReturn($settingemail,$settingemail,'3','json');
		}
		
		//判断密码是否合法
		$checkpsd = $this->checkpsd($psd);
		if(!$checkpsd){
			$this->ajaxReturn('','','0','json');
		}
		//判断密码是否正确
		$ablepsd = service('Passport')->checkUserAuth($this->userinfo['dkcode'],$psd);
		if(!$ablepsd){
			$this->ajaxReturn('','','2','json');
		}
		$time = time();
		$ret = $this->setting->modEmail($this->userinfo['dkcode'], $email, $time);
		if($ret !== '1') $this->ajaxReturn('','','0','json');
		
		//获取加密后的url
		$encodeurl = $this->getencodeurl($email, $time);
		
		$mkemail = $this->sendModEmail($email, $encodeurl);
		if($mkemail === true){
			$this->ajaxReturn('','','1','json');
		} else {
			$this->ajaxReturn('','','0','json');
		}
	}

	//发送电子邮件
	private function sendModEmail($toemail, $redirect_url){
		return false;
		//重置邮箱邮件
		$modemail = service('Mail')->sendEmail($toemail, $this->userinfo['username'], '端口网Email地址修改确认', 2, $redirect_url, $toemail);
		
		//联系我们路径
		$feedbackUrl = mk_url('feedback/main/index');
		
		//通知邮件
		$noticeemail = service('Mail')->sendEmail($this->userinfo['email'], $this->userinfo['username'], '端口网Email地址已申请修改', 3, $feedbackUrl, $toemail);
		
		if($modemail === true && $noticeemail === true) {
			return true;
		}
		return false;
	}

	//生成加密后的url
	private function getencodeurl($email, $time){
		return false;
		//url参数
		$str = $this->userinfo['uid'] . '/' . $this->userinfo['dkcode'] . '/' . $email . '/' . $time;
		
		//加密参数
		$crypt_str = service('Passport')->getCrypt($str, true);
		
		//加密后的url
		$encodeurl = mk_url('front/activeemail/activeemail', array('code' => $crypt_str));
		
		return $encodeurl;
	}

	//验证密码是否合法
	private function checkpsd($psd){
		if(empty($psd) or !isset($psd[31]) or isset($psd[32]) or preg_match("/^([a-z]|[0-9]+)$/", $psd)){
			return false;
		}
		return true; 
	}

	//获取密保问题
	public function getSecurity(){
		//判读用户是否设置密保问题
		$security = service('Passport')->isHasSecurity($this->userinfo['dkcode']);
		if($security) {
			$lock = unserialize($security);
			if(!is_array($lock) or count($lock) < 3) $this->ajaxReturn('','','0','json');//die(json_encode(array('status'=>'0')));
			$list = config_item('security_list');
			if(empty($list)) $list = array(
				'1'=>'我最喜欢的电影是？',
				'2'=>'我最喜欢的演员是？',
				'3'=>'我最喜欢的卡通形象是？',
				'4'=>'我最喜欢的歌曲是？',
				'5'=>'我最喜欢的电视剧是？',
				'6'=>'我母亲的生日是？',
				'7'=>'我父亲的生日是？',
				'8'=>'我最喜欢的食物是？',
				'9'=>'我的初中班主任是？',
				'10'=>'对我影响最大的人是？'
			);
			$key = array_keys($lock);
			$ret = array();
			$ret[] = array('qid' => $key[0], 'text' => $list[$key[0]]);
			$ret[] = array('qid' => $key[1], 'text' => $list[$key[1]]);
			$ret[] = array('qid' => $key[2], 'text' => $list[$key[2]]);
			$this->ajaxReturn($ret,'','1','json');
		}
		$this->ajaxReturn('','','0','json');
	}

	//回答密保问题
	public function replySecurity(){
		$mb_question_id = $this->input->post('questions');
		$mb_answer = $this->input->post('answers');
		
		//检测密保是否合法
		if(!$this->checkKey($mb_question_id, $mb_answer)) {
			$this->ajaxReturn('','','0','json');
		}
		
		$data = array();
		$data[$mb_question_id[0]] = $mb_answer[0];
		$data[$mb_question_id[1]] = $mb_answer[1];
		$data[$mb_question_id[2]] = $mb_answer[2];
		
		//验证密保问题
		$result = service('Passport')->verifyUserSecurity($this->userinfo['dkcode'], $data);
		if($result) {
			$this->ajaxReturn('','','1','json');
		} else {
			$this->ajaxReturn('','','0','json');
		}
	}

	//设置密保问题
	public function modSecurity(){
		$mb_question_id = $this->input->post('questions');
		$mb_answer = $this->input->post('answers');
		
		//检测密保是否合法
		if(!$this->checkKey($mb_question_id, $mb_answer)) {
			$this->ajaxReturn('','','0','json');
		}
		
		$data = array();
		$data[$mb_question_id[0]] = $mb_answer[0];
		$data[$mb_question_id[1]] = $mb_answer[1];
		$data[$mb_question_id[2]] = $mb_answer[2];
		
		//设置密保
		$result = service('Passport')->setUserSecurity($this->userinfo['dkcode'], $data);
		if($result) {
			$this->ajaxReturn('','','1','json');
		} 
		$this->ajaxReturn('','','0','json');
	}

	//验证密保问题安全性
	private function checkKey($qid = array(), $question = array()){
		if(!is_array($qid) or !is_array($question)) {
			return false;
		}
		$unqid = array_unique($qid);
		$unquestion = array_unique($question);
		if(count($unqid) != 3 or count($unquestion) != 3) {
			return false;
		}
		$qidarr = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
		if(!in_array($qid[0], $qidarr) or !in_array($qid[1], $qidarr) or !in_array($qid[2], $qidarr)) {
			return false;
		}
		$firlen  = mb_strlen($question[0]);
		$seclen  = mb_strlen($question[1]);
		$thilen  = mb_strlen($question[2]);
		if(($firlen < 2 or $firlen > 40) or  ($seclen < 2 or $seclen > 40) or ($thilen < 2 or $thilen > 40)) {
			return false;
		}
		return true;
	}

	//同步mydql数据到redis
	public function test(){
		return false;
		$ret = $this->setting->tongbu();
		echo $ret;
	}

	/**
	 * 
	 * 设置广告
	 * @author qianc
	 * @date 2012-07-26
	 */
	function settingAds(){
		$this->assign('login_name',$this->userinfo['username']);
	    $this->assign('login_email',$this->userinfo['email']);

 		
		//当前用户是否有数据存在，存在则取表中数据，不存在则随机
		$this->load->model('adcustommodel','adcustom');			
		$where = " uid = $this->uid AND ad_ids != '' ";
		$customRs = $this->adcustom->getCustomInfo($where);
		$adTop = $adid_top = array();
		if($customRs){
			$where = " uid = $this->uid";
			$adTop = service('Ads')->getAdInfo("ad_id in (".$customRs[0]['ad_ids'].")");			
		}else{
			$adTop = service('Ads')->getAdRandom(" a.is_checked = 3 AND a.sort = 3 AND a.classify = 2 ",8);				
		}
		if($adTop){
			foreach ($adTop as $k => $v){
				array_push($adid_top, $v['ad_id']);
			}
		}

 		$this->adList($adid_top);		
		$this->assign('customRs',$customRs);		 		
		$this->assign('adTop',$adTop);	    
		$this->display("setting-userinfo/setting_ad.html");
	}
	
	
	/**
	 * @author: qianc
	 * @date:2012/7/28
	 * @desc: 广告列表
	 * @access protected
	 * @return json
	 */	
		
	function adList($ad_id_arr = array()){
		// 判断是否为AJAX过来请求列表下面的页面;
		$isAjax = $this->isAjax ();
		if ($isAjax) {
			$pager = intval($this->input->post( 'pager' ));
			//当前游标	
			if ($pager) {
				$curAd = $pager * 8;
			}else{
				$curAd = 0;
			}
			
			//要排除的id
			$ad_id_arr = $this->input->post( 'ad_id_check' );
			$ad_id_str = implode(',', $ad_id_arr);		
		} else {
			$curAd = 0;
        	$ad_id_str = implode(',', $ad_id_arr);			
		}
		
		if(!$ad_id_str){return FALSE;}

        $offset = 8; //每页条数	
       	$where = " t1.is_checked = 3 AND t1.sort = 3  AND t1.classify = 2 AND t1.ad_id NOT IN ($ad_id_str)";       	 		    
		$adRs = service('Ads')->getAds($curAd,$offset,$where,'t1.create_time');
		
		if(!$adRs){return FALSE;}
		
		if($isAjax){
			$adRs_ajax = array();
			foreach ($adRs['data'] as $k => $v){
				$adRs_ajax[$k]['ad_id'] = $v['ad_id'];				
				$adRs_ajax[$k]['ad_title'] = $v['title'];
				$adRs_ajax[$k]['ad_introduce'] = $v['introduce'];
				$adRs_ajax[$k]['ad_url'] = $v['url'];	
				$adRs_ajax[$k]['ad_media_uri'] = $v['media_uri'];												
			}
			
			//是否还有下页
			$pageNum = ceil($adRs['nums']/8);
			$hasNext = $pager >= $pageNum - 1  ? 0 : 1;
			$data = array ('adRs'=>$adRs_ajax,'hasNext'=>$hasNext );		
			$this->ajaxReturn($data,'',1,'json');		
		}else{
			
			$this->assign('adList',$adRs['data']);
			$this->assign('ad_nums',$adRs['nums']);	
			
		}
	}

	
	
	/**
	 * @author: qianc
	 * @date 2012/7/30
	 * @desc: 保存ad_ids
	 * @access public
	 */	
	function setAdPost(){
		$this->load->model('adcustommodel','adcustom');			
		$custom_adid_arr = $this->input->post('ad_id');
		$custom_status = $this->input->post('status');
		//当前用户是否有数据存在，存在则更新，不存在则新建
		$where = " uid = $this->uid ";
		$customExist = $this->checkCustom($where);		
		if($customExist){
			if($custom_status){
				$data = array('status'=>$custom_status,'updatetime'=>time());
				$ret = $this->adcustom->editCustom($data,array('uid'=>$this->uid));					
			}else{
				if(!empty($custom_adid_arr)){
					$custom_adid_str = implode(',', $custom_adid_arr);
					$data = array('uid'=>$this->uid,'dkcode'=>$this->dkcode,'ad_ids'=>$custom_adid_str,'status'=>$custom_status,'dateline'=>time());
					$ret = $this->adcustom->editCustom($data,array('uid'=>$this->uid));					
				}else{
					$this->ajaxReturn('','请至少选择一条广告!',0,'json');								
				}				
			}
		}else{
			if($custom_status){
				$data = array('uid'=>$this->uid,'dkcode'=>$this->dkcode,'status'=>$custom_status,'dateline'=>time());
				$ret = $this->adcustom->newData($data,array('uid'=>$this->uid));					
			}else{
				if(!empty($custom_adid_arr)){
					$custom_adid_str = implode(',', $custom_adid_arr);
					$data = array('uid'=>$this->uid,'dkcode'=>$this->dkcode,'ad_ids'=>$custom_adid_str,'status'=>$custom_status,'dateline'=>time());
					$ret = $this->adcustom->newData($data);									
				}else{
						$this->ajaxReturn('','请至少选择一条广告!',0,'json');								
				}
			}			
		}		
		
		if($ret){
			if(isset($custom_adid_str)){
				//广告设置历史
				$this->load->model('adcustomhistorymodel','adcustomhistory');
				$data = array('uid'=>$this->uid,'dkcode'=>$this->dkcode,'ad_ids'=>$custom_adid_str,'dateline'=>time());
				$this->adcustomhistory->newData($data);	
			}			
			$this->ajaxReturn('','操作成功!',1,'json');			
		}
		$this->ajaxReturn('','操作失败!',0,'json');	

       
	}

	/**
	 * @author: qianc
	 * @date 2012/7/16
	 * @desc: 判断是否设置了广告
	 * @access private
	 */	
	private function checkCustom($where){
		$this->load->model('adcustommodel','adcustom');		
		$adCustom = $this->adcustom->getCustomInfo($where);
		if($adCustom){
			return TRUE;
		}	
		return FALSE;				
	}		
	
	/**
	 * @author: hujiashan
	 * @date 2012/8/4
	 * @desc: 广告提现
	 * @access public
	 */	
	function adsaccount(){
		$this->assign('login_name',$this->userinfo['username']);
	    $this->assign('login_email',$this->userinfo['email']);
	    
		//当前用户是否有数据存在
		$where = " uid = $this->uid AND ad_ids != ''  ";
		$this->load->model('adcustommodel','adcustom');	
		$customRs = $this->adcustom->getCustomInfo($where);


		if($customRs){
			$this->load->model('adpayassignmodel','adpayassign');
			$this->load->model('adaccountmanagemodel','adaccountmanage');
			$this->load->model('adtocashmodel','adtocash');						
							
			//本月收入，上月收入，已提现，余额，提现中
			$cur_list = $this->adpayassign->getPayAssignTotal(" FROM_UNIXTIME(dateline,'%Y-%m') = date_format(now(),'%Y-%m') AND dkcode = $this->dkcode ");
			$previous_list = $this->adpayassign->getPayAssignTotal(" FROM_UNIXTIME(dateline,'%Y-%m') = date_format(DATE_SUB(curdate(),INTERVAL 1 MONTH),'%Y-%m') AND dkcode = $this->dkcode ");
			$total_list = $this->adpayassign->getPayAssignTotal(" dkcode = $this->dkcode ");
			$me_cash_list = $this->adtocash->getCashTotal(" uid = $this->uid AND status = 3 ");
			$me_cash_ing = $this->adtocash->getCashTotal(" uid = $this->uid AND status = 0 ");			

			$cur_money = $cur_list ? $cur_list[0]['p_money_num'] : '0.00';
			$previous_money = $previous_list ? $previous_list[0]['p_money_num'] : '0.00';	
			$total_money = $total_list ? $total_list[0]['p_money_num'] : '0.00';
			$me_cash_money = $me_cash_list ? $me_cash_list[0]['money_num'] : '0.00';
			$me_avail_money = number_format($total_money - $me_cash_money,2,'.',''); 
			$me_cash_ing = $me_cash_ing ? $me_cash_ing[0]['money_num'] : '0.00';								
			
			//分成列表			
			$payAssignRs = service('Ads')->getAdPayAssign(" $this->dkcode ");
			$this->assign('payAssignRs',$payAssignRs);
			
			//提现设置
			$alipayAccount = $this->adaccountmanage->getAccountManage(" dkcode = $this->dkcode AND type = 1 ");
			$bankAccount = $this->adaccountmanage->getAccountManage(" dkcode = $this->dkcode AND type = 2 ");
			

			//提现按钮显示与隐藏
			$button_cash = $this->adtocash->getCashInfo(" uid = $this->uid AND status = 0 ");
			if($button_cash || $me_avail_money < 100) {
				$button_visiable = 0;
			}else{				
				$button_visiable = 1;
			}

			
			$this->assign('cur_money',$cur_money);	
			$this->assign('previous_money',$previous_money);
			$this->assign('me_cash_money',$me_cash_money);	
			$this->assign('me_available_money',$me_avail_money);	
			$this->assign('me_cash_ing',$me_cash_ing);							
			$this->assign('alipayAccount',$alipayAccount[0]);
			$this->assign('bankAccount',$bankAccount[0]);
			$this->assign('button_visiable',$button_visiable);																	
		}else{
			$this->error('暂无数据!');
		}
				    
		$this->assign('customRs',$customRs);	    
		$this->display("setting-userinfo/setting_adsaccount.html");
	}
	
	
	/**
	 * @author: qianc
	 * @date 2012/8/7
	 * @desc: 广告提现post
	 * @access public
	 */	
	function adsaccountPost(){
		$dataPost = $this->input->post();
		$dataPost = $dataPost['data'];
		if($dataPost['money'] < 100){
			$this->ajaxReturn('','可提现金额太少',0,'json');				
		}
		$this->load->model('adaccountmanagemodel','adaccountmanage');
		$this->load->model('adtocashmodel','adtocash');		

		//设置帐户
		$accountRs = $this->adaccountmanage->getAccountManage(" dkcode = $this->dkcode AND type = {$dataPost['type']} ");
		if($accountRs){
			$accountArr = array('name'=>$dataPost['name'],'number'=>$dataPost['number'],'title'=>$dataPost['title']);
			$accountWhere = array('dkcode'=>$this->dkcode,'type'=>$dataPost['title']);	
			$accountRet = $this->adaccountmanage->editAccountManage($accountArr,$accountWhere);	
			$am_id = $accountRet ? $accountRs[0]['id'] : 0;
		}else{
			$accountArr = array('name'=>$dataPost['name'],'number'=>$dataPost['number'],'title'=>$dataPost['title'],'type'=>$dataPost['type'],'uid'=>$this->uid,'dkcode'=>$this->dkcode);		
			$am_id = $this->adaccountmanage->newData($accountArr);	
		}
		
		if($am_id){
			$cashArr = array('uid'=>$this->uid,'am_id'=>$am_id,'money'=>$dataPost['money'],'dateline'=>time(),'status'=>0);
			$ret = $this->adtocash->newData($cashArr);	
			if($ret){
				$this->ajaxReturn('','操作成功！',1,'json');					
			}
			$this->ajaxReturn('','操作失败！',0,'json');						
		}
	$this->ajaxReturn('','操作失败！',0,'json');	
	}	
}