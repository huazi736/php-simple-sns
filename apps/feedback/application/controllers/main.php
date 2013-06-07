<?php
/**
 * @author zengxm
 * @date <2012/7/23>
 * 用户返馈
 */

class Main extends DK_Controller{
	function __construct(){
		 parent::__construct();
	}
	
	/**
	 * 用户反馈信息页面
	 * @author zengxm
	 * @since <2012/07/23>
	 */
	public function index(){
		$url =mk_url('feedback/main/add');
		//$is_in 为1代表登录后提交，2为未登录提交
		$is_in = isset($this->user['username']) ? 1 : 2;
		$this->assign('url',$url);
		$this->assign('is_in',$is_in);
		$this->display('feedback.html');
	}
	
	/**
	 * 
	 * 处理用户提交过来的返馈数据
	 * @author zengxm
	 * @date <2012/7/23>
	 * 
	 */
	public function add(){
		$this->load->model('FeedbackModel','feedback');
		$input = array();
		
		$messageType = addslashes_deep(trim($this->input->post('messageType')));
		$pagePath = addslashes_deep(($this->input->post('pagePath')));
	  	$content = addslashes_deep(($this->input->post('content')));
		
		if(is_null($content) or is_null($pagePath)){
	   		$this->ajaxReturn('','','0','json');
	   }
		
		$username = isset($this->user['username'])  ?  $this->user['username']  :  '';
		$email = isset($this->user['email'] ) ? $this->user['email'] : '';
		
		$input['userName'] = $username;
		$input['is_login'] = $input['userName'] ? 1 : 0;
		
		$input['email'] = $email;
		$input['content'] = $content;
		$input['pagePath']  = $pagePath;
		$input['messageType'] = $messageType;
		$input['createTime']=date('Y-m-d H:i:s'); 
		$input['ip']=get_client_ip();
		
		$ret = $this->feedback->f_insert($input);
		if($ret){
			$this->ajaxReturn('','','1','json');
		}else{
			$this->ajaxReturn('','','0','json');
		}
	}
	
	/**
	 * 
	 * 通过curl方式把数据提交到指定的url上去处理
	 * @param string $url   要处理数据的url
	 * @param array $input  将要被处理的数据
	 * @param int $timeout   超时时间
	 * @param boolean $flag  是否返回结果   true为返回，false为不返回
	 * @data <2012/7/23>
	 * @author zengxm
	 */
	/*
	public function getdata($url,$input = array(),$timeout=300){
		if(count($input)<=0){
			return false;
		}
		if(function_exists('curl_init')){
			$res = curl_init();
			if(!$res){
				return false;
			}
			$data = array(
					CURLOPT_URL=> $url,
					CURLOPT_CONNECTTIMEOUT_MS=>$timeout,
					CURLOPT_RETURNTRANSFER=>1,
					CURLOPT_POST =>1,
					CURLOPT_POSTFIELDS => $input,
			);
			$csa = curl_setopt_array($res, $data);
			if(!$csa){
				return false;
			}
			$result = curl_exec($res);	
			if(!$result){
				return false;
			}
			curl_close($res);
		}else{
			$result = false;
		}
		return $result;
	} 
	*/
}
