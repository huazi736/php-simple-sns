<?php
/**
 * 控制器类文件
 * @author vicente
 * @date <2012/07/05>
 * @version $Id
 */
class MY_Controller extends DK_Controller
{
	
	/**
     * 构造函数
     */    
    public function __construct()
    {
    	//其他方式登录，绕过基类的用户验证
    	$flash_uid = isset($_GET['flashUploadUid']) ? $_GET['flashUploadUid'] : 0;
    	if(!empty($flash_uid)){
    		$this->is_check_login = false;
    	}
    	
    	parent::__construct();
    	
    	if($this->web_id <= 0){
    		$this->ajaxReturn('', '非法请求', 0);
    	}
    	
    	//自身进行用户信息验证
    	if($this->is_check_login === false){
    		if(!is_numeric($flash_uid)){
    	        $uid = sysAuthCode($flash_uid, 'DECODE');
    	    }else{
    	        $uid = $flash_uid;
    	    }
    	    $user = service('User')->getUserInfo($uid);
	    	if($user === false)
	        {
	            if($this->isAjax())
	            {
					$callback = isset($_GET['callback']) ? $_GET['callback'] : '';
					if(empty($callback)){
						$this->ajaxReturn('','登陆超时',-1,'json');
					}
					else{
						$this->ajaxReturn('','登陆超时',-1,'jsonp');					
					}               
	            }
	            else 
	            {
	                $this->redirect('front/login/index');
	            }
	        }
	        else 
	        {
	            $this->uid = $user['uid'];
	            $this->dkcode = $user['dkcode'];
				$this->username = $user['username'];
	            $this->user = $user;                       
	            unset($user);
	        }
	        
    		$this->init_js();
    	}
    }
    
	/**
     * 异常消息处理
     * 
     * @return void
     */
    public function _ex($ex, $return_struct = array())
    {
    	//初始化结构体
    	$return_struct['status'] = 0;
    	$return_struct['code'] = 500;
    	$return_struct['msg'] = $ex->getMessage();
    	$return_struct['file'] = $ex->getFile();
    	$return_struct['line'] = $ex->getLine();
    	
    	$this->ajaxReturn($return_struct, $ex->getMessage(), 0, 'html');
    	
    	/*
    	if(empty($return_struct['jumpurl'])) {
    		$return_struct['jumpurl'] = $this->_get_default_jmupurl();
    	}
    	
    	if($this->input->is_ajax_request()) {
    		exit(json_encode($return_struct));
    	}else {
    		//TODO
        	header("Content-Type:text/html; charset=utf-8");
        	echo '<script language="javascript">';
        	echo 'alert("'.$ex->getMessage().'");';
        	echo 'window.location="'.$this->_get_default_jmupurl().'"';
        	echo '</script>';
            $message = "错误提示：".$ex->getMessage()."<br>";
            $message .= "错误文件：".$ex->getFile()."<br>";
            $message .= "错误所在行：".$ex->getLine()."<br>";
            echo $message;exit;
    	}*/
    }
} 