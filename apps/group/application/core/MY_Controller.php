<?php

class MY_Controller extends DK_Controller
{   
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        
    	if($this->input->get('debug') == 1) {
    		if(preg_match('/192\.168\.*\.*/', $_SERVER['REMOTE_ADDR'])){
				$this->enable_profiler(TRUE);
    		}
		}
		$this->menu_left();
    }
    
	private function menu_left(){
    	//左侧自定义群组数据
		$groups = service('Group')->getGroupsByCustom($this->uid);
		$this->assign('left_groups', $groups);

    	$this->user['avatar'] = get_avatar($this->uid);
		$this->assign('user', $this->user);
		$this->assign('level', service('credit')->getLevel($this->uid));
		
		// start add by zengmm 2012/7/30
        // 获取用户关注网页的所属频道
        $followingChannel = service('Attention')->getFollowingChannel($this->uid);
        $this->assign('followingChannel', $followingChannel);
        // end add by zengmm 2012/7/30
    }
    
	/**
     * 提示页或ajax的返回json串
     * @param max $msg 返回内容
     * @param int $code 返回码
     * @param data $data 返回数据
     * @param string $url 跳转url
     * @param int $time 自定跳转的延迟时间
     */
    public function showMessage($msg = array(),$code = ErrorCode::CODE_SUCCESS, $data = array(), $url = '', $time = 0, $returnType = '')
    {
    	if(!$data || empty($data)) $data = array();
    	if($this->isAjax()) {
    		$status = $code == ErrorCode::CODE_SUCCESS ? 1 : 0;
    		$json = json_encode(array('status' => $status, 'code' => $code, 'error' => $msg, 'data' => $data));
    		if(isset($_SERVER['SERVER_NAME']) && strpos('http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'], WEB_ROOT) === false || $returnType == 'jsonp') {
    			echo $_REQUEST['callback'] . '('. $json . ')';
    		} else {
    			echo $json;
    		}
    	} else {
	    	if(!is_array($msg))
	    	{
	    		$msg = array('info'=>$msg);
	    	}
	    	if(empty($url))
	    	{
	    		$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : base_url();
	    	}
    		
	    	$this->assign('msg',$msg);
	    	$this->assign('code',$code);
	    	$this->assign('url',$url);
	    	$this->assign('time',$time);
	    	$this->assign('data',$data);
	    	if($code == ErrorCode::CODE_SUCCESS) {
	    		if($time == 0) {
	    			header("Location: ".$url);
	    		}else{
	    	    	$this->display('success.html');
	    		}
	    	} else {
	    		$this->assign('time',3);
	    	    $this->display('error.html');
	    	}
    	}
    	die;
    }
    
	public function view($file, $parameters = array(), $fetch = false)
    {
    	if(!is_array($parameters)) $parameters = array($parameters);
    	foreach($parameters as $key => $val) {
    		$this->assign($key, $val);
    	}
    	$this->user['avatar'] = get_avatar($this->uid);
    	$this->assign('user', $this->user);
    	$dir = strtolower(get_class($this));
    	$this->assign('im_host', strstr($this->config->item("im_url"), 'dksns-im-web', true));
    	if($fetch) {
    		return $this->fetch($dir . DS . $file . '.html');
    	} else {
    		$this->display($dir . DS . $file . '.html');
    	}
    }
}