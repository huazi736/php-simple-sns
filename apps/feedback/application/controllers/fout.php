<?php
/**
 * @author zengxm
 * @date <2012/7/24>
 * 用户返馈
 */

class Fout extends DK_Controller{
	function __construct(){
		$this->is_check_login = false;
		 parent::__construct();
	}
	
	/**
	 * 用户反馈信息页面
	 * @author zengxm
	 * @since <2012/07/24>
	 */
	public function index(){
		$url =mk_url('feedback/fout/add');
		//$is_in 为1代表登录后提交，2为未登录提交
		$is_in = isset($this->user['username']) ? 1 : 2;
		$this->assign('is_in',$is_in);
		$this->assign('url',$url);
		$this->display('feedback.html');
	}
	
	/**
	 * 
	 * 处理用户提交过来的返馈数据
	 * @author zengxm
	 * @date <2012/7/24>
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
	
}
