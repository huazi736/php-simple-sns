<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

class DK_Controller extends CI_Controller
{
    /**
     * @var mix 模板视图引擎
     */
    protected $view = null;
    //当前登录用户信息
    protected $uid    = null;
    protected $dkcode = null;
    protected $user   = null;
    protected $username   = null;
    //当前主页用户信息
    protected $action_uid    = null;
    protected $action_dkcode = null;
    protected $action_user   = null;
    //是否是自己访问
    protected $is_self = false;
    //当前网页信息
    protected $web_id = 0;
    protected $web_info = null;
    
    protected $js_config = array();
    
    protected $is_check_login = true;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->init_view();
        // $this->autoLogin();
        if($this->is_check_login == true)
        {
            $this->checkLogin();
        }
        
        $this->init_user();
        $this->init_web();
        $this->init_js();       
        if (method_exists($this, '_initialize'))
        {
            $this->_initialize();
        }   
		
		$this->create_menu();	// 生成首页菜单
		
    }
    
    /**
     * 初始化模板
     */
    protected function init_view()
    {
        //开启模板引擎
        require_once(EXTEND_PATH . 'core' . DS . 'DK_View' . EXT);
        $this->view = new DK_View(array('engine' => 'smarty', 'config' => array()));
    }
    
    /**
     * 
     */
    protected function init_user()
    {        		
        $this->action_dkcode = intval($this->input->get_post('dkcode')); 
        if($this->action_dkcode && intval($this->action_dkcode) > 0)
        {
            $this->action_user = service('User')->getUserInfo($this->action_dkcode,'dkcode');
            $this->action_uid = isset($this->action_user['uid']) ? $this->action_user['uid'] : 0;
            define('ACTION_UID',$this->action_uid);
            define('ACTION_DKCODE',$this->action_dkcode);            
        }
        else 
        {      	     	        
            define('ACTION_UID',0);
            define('ACTION_DKCODE',0);
        }
             
    }
    
    protected function init_web()
    {
        if(intval($this->input->get_post('web_id')) > 0)
        {
            $this->web_id = intval($this->input->get_post('web_id'));
            $this->web_info = service('interest')->get_web_info($this->web_id);
			$this->web_info[0]	= $this->web_info;
            if($this->web_info && isset($this->web_info['uid']) && $this->web_info['uid'])
            {
                $this->action_user = service('User')->getUserInfo($this->web_info['uid'],'uid');
                $this->action_uid = isset($this->action_user['uid']) ? $this->action_user['uid'] : 0;
                $this->action_dkcode = isset($this->action_user['dkcode']) ? $this->action_user['dkcode'] : 0;
            }else{
				$this->web_id = 0;
			}
        }
        else
        {
            $this->web_id = 0;
        }
		define('WEB_ID',$this->web_id);
    }
    
    protected function init_js()
    {
        if($this->user)
        {
            $this->js_config['uid'] = $this->user['uid'];
            $this->js_config['username'] = $this->user['username'];
            $this->js_config['dkcode'] = $this->user['dkcode'];
            $this->js_config['avatar'] = get_avatar($this->user['uid']);
			$this->js_config['access'] = $this->user['access'];
        }
        
        $this->js_config['web_id'] = $this->web_id;
		$this->js_config['fastdfs_domain'] = 'http://'.config_item('fastdfs_domain').'/';
        //获取子域名列表
        $this->js_config['subdomain'] = $this->get_subdomain();
        $this->js_config['action_dkcode'] = isset($this->action_user['dkcode']) ? $this->action_user['dkcode'] : 0;
        $this->assign('js_config',$this->js_config);
    }
    
    //取得路由配置文件中的子域名配置项
    protected function get_subdomain()
    {
    	$subdomain = json_encode(array());
		if(defined('SUBDOMAIN'))
		{
				$subdomain =SUBDOMAIN;
		}
		return $subdomain;
    }
    
    protected function init_site()
    {
        $this->load->redis('default');
        $siteopt = Array();
        $siteopt = $this->redis->hgetall('config:siteopt');
        //赋值给模板变量
        $this->assign('siteopt',$siteopt);
    }
	/**
	 *自动登陆
	 *lvxinxin 2012-07-06 add
	 */
	public function autoLogin(){
		$autoLoginstr = get_cookie('dknet');
		if(!empty($autoLoginstr)){
			$uid = service('Passport')->getCrypt($autoLoginstr,false);
			if(!empty($uid)){
				$user = service('User')->getUserInfo($uid,'uid','',true);				
				if(!empty($user)){
					// $_SESSION['status'] = $user['status'];
					$_SESSION['uid']  = $uid;
					$_SESSION['user'] = $user; 
					unset($user);
					return true;
					// var_dump($_SESSION);
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	
	}
    /**
     * 检查是否登录
     */
    public function checkLogin()
    {
        $user = service('Passport')->checkLogin();       
        if($user === false)
        {
            if($this->isAjax())
            {
                $this->ajaxReturn('','登陆超时',-1,'jsonp');	          
            }
            else 
            {
                $url = get_url();
                if(rtrim($url,'/') == rtrim(WEB_ROOT,'/'))
                {
                    $this->redirect('front/login/index');  
                }
                else 
                {
                    $this->redirect('front/login/index',array('backurl'=>get_url()));
                }
				
                //
            }
        }
        else 
        {
            $this->uid = $user['uid'];
            $this->dkcode = $user['dkcode'];
			$this->username = $user['username'];
            $this->user = $user;
            unset($user);
			//$this->redirect('blog/blog/index');
        }
    }    

    /**
     * 检查表单令牌是否正确
     */
    protected function _check_token()
    {
        if (! $this->isAjax())
        {
            if ($this->input->server('REQUEST_METHOD') == 'POST')
            {
                $token_name = config_item('token_name');
                if ($this->session->userdata($token_name) != $this->input->post($token_name))
                {
                    show_error('表单验证失败');
                }
            }
        }
    }

    /**
     * 获取模板文件的内容
     * 
     * @access protected
     * @param string $templateFile 模板文件名
     * @param string $charset 模板的字符集,默认是utf-8
     * @param string $contentType 输出类型,默认是text/html
     * @return string 返回模板内容
     */
    public function fetch($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        return $this->view->fetch($templateFile, $charset, $contentType);
    }

    /**
     * 模板变量赋值
     * @param string $name 模板中变量的名称
     * @param mix $value 用来替换模板中变量的值
     */
    public function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }

    /**
     * 加载并显示模板
     * @param string $templateFile 模板文件名
     * @param string $charset 模板的字符集,默认是utf-8
     * @param string $contentType 输出类型,默认是text/html
     */
    public function display($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $this->view->display($templateFile, $charset, $contentType);
    }
    
    function enable_profiler($val = TRUE)
    {
        $this->view->enable_profiler($val);
    }
    
    function set_profiler_sections($sections)
    {
        $this->view->set_profiler_sections($sections);
    }

    /**
     * 是否是AJAX请求
     * 
     * @return bool
     */
    public function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
        {
            if ('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * 
     */
    public function showmessage($msg = array(), $type = 2, $url = '', $time = 3)
    {
        if (! is_array($msg))
        {
            $msg = array('info' => $msg);
        }
        if (empty($url))
        {
            $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : base_url();
        }
        $this->assign('msg', $msg);
        $this->assign('type', $type);
        $this->assign('url', $url);
        $this->assign('time', $time);
        $this->display('404.html');
        die();
    }

    /**
     * 跳转到指定的模块
     * @access protected
     * @param string $url 跳转的URL表达式
     * @param array  $params 其他URL参数
     * @param int    $delay 延迟跳转的时间，单位是秒
     * @param string $msg   跳转提示信息
     */
    public function redirect($uri, $params = array(), $delay = 0, $msg = '')
    {
        $http_response_code = 302;
        $tourl = mk_url($uri, $params);
        if (!headers_sent($filename, $linenum)) {
			    header("Location: " . $tourl, TRUE, $http_response_code);
			} else {
				echo '<script type="text/javascript">window.location.href="'.$tourl.'"</script>';
		}
        
		exit;
    }

    /**
     * Ajax方式返回数据到客户端
     * 
     * @param mixed $data 要返回的数据
     * @param string $info 提示信息
     * @param boolean $status 返回的状态
     * @param string $type 返回的类型 JSON|XML|HTML|EVAL|TEXT
     * 
     */
    public function ajaxReturn($data, $info = '', $status = 1, $type = 'json')
    {
        $result = array();
        $result['status'] = $status;
        $result['info'] = $info;
        $result['data'] = $data;
        $type = strtoupper($type);
        $callback = $this->input->get_post('callback');
        // 修正前端请求返回json,jsonp格式
        if($type == 'JSON' && !empty($callback)) {
            $type = 'JSONP';
        }
        if($type == 'JSONP' && empty($callback)) {
            $type = 'JSON';
        }       
        if ($type == 'JSON')
        {
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($result));
        }
		elseif ($type == 'JSONP')
        {
            header("Content-Type:text/html; charset=utf-8");
			exit($callback.'('.json_encode($result).')');
        }
        elseif ($type == 'XML')
        {
            header("Content-Type:text/xml; charset=utf-8");
        }
        elseif ($type == 'EVAL')
        {
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }
        elseif ($type == 'TEXT')
        {
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }
        elseif ($type == 'HTML')
        {
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }
    }

    /**
     * 跳转到登陆页面
     * Enter description here ...
     * @param string $msg
     */
    public function redirectLogin($msg = '')
    {
        if (! empty($msg))
        {
            $this->assign('iserror', true);
            $this->assign('error', $msg);
        }
        else
        {
            $this->assign('iserror', false);
        }
        $this->assign('url_login', mk_url('front/login/userlogin'));
        $this->assign('reg', mk_url('front/index/index'));
		$this->assign('forget',mk_url('front/login/forget_pass'));
        $this->display('login.html');
        exit();
    }

    /**
     * 错误页面
     * @param array $msg
     */
    public function error($msg = array())
    {
        if (! is_array($msg))
        {
            $msg = array('info' => $msg);
        }
        $this->assign('msg', $msg);
        $this->assign('root', WEB_ROOT);
        $this->display('error.html');
        exit();
    }

    /**
     * 获取当前登录用户的UID
     */
    protected function getLoginUID()
    {
    	// 应晓斌  调整调用接口 2012-5-25
    	if (isset($this->uid)) {
    		return $this->uid;
    	} else {
    		return get_cache($this->sessionid . 'uid');   //lvxinxin 2012-06-04 edit
    	}
    } 
	
	
	/**
	 * 生成 菜单
	 */
	private function create_menu(){
		$is_highlight	= false;	// 是否高亮
		$navigation	= null;
		
		if(!$this->uid) return null;

		if($_GET['app']=='webmain' && $_GET['controller']=='create' ){
			$this->assign('is_create_web_current' , true );		// 创建网页加高亮
			$is_highlight	= true;
		}
		
		$web_list	= $this->get_web_list($this->uid);
		$web_id		= intval(@$this->input->get('web_id'));
		$n			= 0;
		foreach($web_list as $key=>$val){
			$n++;
			if($val['aid']==$web_id && (!$is_highlight) ){
				$val['current']	= 1;		// web页高亮
				$is_highlight 	= true;
			}else{
				$val['current']	= 0;
			}
			$navigation[] = $val;
			
			if($n>=6)	break;
		}
		
		if(!$is_highlight){
			$this->assign('is_index_current' , true );		// 首页加高亮	
		}

		
		$this->assign('navigation_menu' , $navigation);
		
	}
	
	/**
	 * 获得指定用户的网页数
	 **/
	private function get_web_list($uid){
		$weblist	= service('Interest')->get_webs($uid); // ('interest', 'Index' ,'get_webs' , array($uid) );
		$weblist	= json_decode($weblist,true);
		return $weblist;
	}
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */