<?php
/**
 * 控制器类文件
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
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
    	//自身进行用户信息验证
    	if($this->is_check_login === false){
    		if(!is_numeric($flash_uid)){
    	        $uid = sysAuthCode($flash_uid, 'DECODE');
    	    }else{
    	        $uid = $flash_uid;
    	    }
    	    $user = service('User')->getUserInfo($uid);
	    	if($user === false || empty($user))
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
    	
    	define('UID', $this->uid);
    	define('DKCODE', $this->dkcode);
    	
    	//判断用户是否有访问视频模块的权限
		$action_uid= ACTION_UID ? ACTION_UID: $this->uid;
		$this->checkAlbumPurview($action_uid);
    } 

 /**
     * 判断用户是否有访问相册模块的权限
     * @param integer $action_uid 被访问者
     * @return boolean 例：true 表示能访问
     */
    public function checkAlbumPurview($action_uid)
    {
    	$bool = service('UserPurview')->checkAppPurview($action_uid, $this->uid, 'album');
		if(!$bool)  $this->error('对不起,您没有查看相册模块的权限！');
    }
} 