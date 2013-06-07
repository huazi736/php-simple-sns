<?php
/**
 * 路由类
 * @author mawenpei<mawenpei@duankou.com>
 * @since <2012/06/22>
 */
class DK_Router
{
    private static $instance;
	private $router_config = array();
	private $request = null;
	
	protected $app = '';
	protected $controller = '';
	protected $action = '';
	protected $params = array();
	
	//配置子域名
	protected $subdomain = array();
	//不是域名的
	protected $nodomain = array();
	
	//路由
	protected $routers = null;	
	
	private function __construct()
	{		
	    $this->initConfig();
	    //配置子域名
	    if(isset($this->router_config['subdomain']) && is_array($this->router_config['subdomain'])){
	    	$this->subdomain = $this->router_config['subdomain'];
	    }
	    //不是域名的
	    if(isset($this->router_config['nodomain']) && is_array($this->router_config['nodomain'])){
	    	$this->nodomain = $this->router_config['nodomain'];
	    }
	    //设置常量供js使用
	  	if(!defined('SUBDOMAIN')){
	    	define('SUBDOMAIN', json_encode($this->subdomain));
	    }
	}
	
	/*
	* 获取Router的对象单例
	*/
	public static function getInstance()
	{
	    if(!isset(self::$instance))
		{
		    self::$instance = new DK_Router();
		}
		return self::$instance;
	}
	
	/**
	* 解析URL路由
	* @param object $request
	*/
	public function parseRouter($request)
	{
	   if(LOCAL_RUN == TRUE)
	   {
	   		return $this->parseLocal($request);
	   }
	   else 
	   {
	        return $this->parseRemote($request);
	   }
	}
	
	/**
	 * 解析本地调试环境路由
	 */
	protected function parseLocal($request)
	{	
	    $this->parseRemote($request);
	}
	
	/**
	 * 解析服务器真实环境路由
	 */
	protected function parseRemote($request)
	{
	    $this->request = $request;
        $host = $this->request->getHostInfo(); //http://www.duankou.com 
		$url = $this->request->getRequestUri(); // /example/index.php/blog/post/view?a=test
		$pathinfo = $this->request->getPathInfo(); // blog/post/view		

		$query = ($pos = strpos($url,'?')) !==false ? substr($url,$pos+1) : '';		
		$url = LOCAL_RUN==true ? $url : $host . $url;
		$acm = array();
        $params = array(); 
        		
        //把查询字符串转换为数组
		if($query && is_string($query)) parse_str($query,$params);		
    	foreach($this->routers as $name=>$router)
    	{
    	    $count = preg_match($router['rule'],$url,$res);
    		if($count!==false && $count>0)
    		{
    		    array_shift($res);			
    			break;
    		}
    	}
    	//处理应用/控制器/方法
    	$acm = $this->mapping($res , LOCAL_RUN);
    	//处理传统传参请求
    	if(isset($params['app']) && !empty($params['app'])) $acm['app'] = $params['app'];
    	if(isset($params['controller']) && !empty($params['controller'])) $acm['controller'] = $params['controller'];
    	if(isset($params['action']) && !empty($params['action'])) $acm['action'] = $params['action'];
    	if(isset($params['dkcode']) && !empty($params['dkcode'])) $acm['dkcode'] = $params['dkcode'];
    	
        if(!isset($acm['app']))
        {
            die('App is not found.');
        }    
	    if(!defined('APP_NAME'))
	    {
	        define('APP_NAME',$acm['app']);
	    }
	    if(!defined('APPPATH')) 
	    {
	        define('APPPATH', APP_ROOT_PATH . APP_NAME . DS . 'application' . DS);
	    }
    	//return array('acm'=>$acm,'params'=>$params);
    	$this->app = $acm['app'];
    	$this->controller = $acm['controller'];
    	$this->action = $acm['action'];
    	$this->params = array_merge($acm,$params);
        $_GET = $this->params;
    	return $this->params;	
	}
	
	public function testParse($url,$local = false,$config='')
	{
	    if($local==true)
	    {
	        $this->router_config = include CONFIG_PATH . 'router.local.php';
	    }
	    else
	    {
	        $this->router_config = include CONFIG_PATH . 'router.remote.php';
	    }
		if(is_string($config) && empty($config))
		{
		    $this->routers = $this->router_config['routers'];
		}
		elseif(is_string($config) && !empty($config))		
		{
		    if(isset($this->router_config[$config])) $this->routers = $this->router_config[$config]['routers'];
		}
		elseif(is_array($config) && count($config))
		{
		    $this->routers = $config;
		}
	    $acm = array();
        $params = array();
	    $query = ($pos = strpos($url,'?')) !==false ? substr($url,$pos+1) : '';			
        //把查询字符串转换为数组
		if($query && is_string($query)) parse_str($query,$params);
			        	    	    	
    	foreach($this->routers as $name=>$router)
    	{
    	    $count = preg_match($router['rule'],$url,$res);
    		if($count!==false && $count>0)
    		{
    		    array_shift($res);			
    			break;
    		}
    	}
    	$acm = $this->mapping($res);
    	return array_merge($acm,$params);	
	}
	
	private function getDefaultSetting($app,$type='controller')
	{
	    if(isset($this->router_config['default'][$app]))
	    {
	        return $this->router_config['default'][$app][$type];
	    }
	    elseif(isset($this->router_config['default']['default']))
	    {
	        return $this->router_config['default']['default'][$type];
	    }
	    else
	    {
	        return $type == 'controller' ? 'welcome' : 'index';
	    }
	}
	
    private function mapping($udi ,$is_local)
    {
        $router = array();
        $count = count($udi);
    	if($count == 1)
    	{
    			if (in_array($udi[0], $this->subdomain))
		    	{
		    			$router['app'] =  $udi[0];
		    	}
		    	else 
		    	{
		    			$router['app'] =  'main';
		    	}
		    	$router['controller'] = $this->getDefaultSetting($router['app'],'controller');
		    	$router['action'] = $this->getDefaultSetting($router['app'],'action');
    	}
    	elseif($count == 2)
    	{
    		  //是本地
    		  if($is_local)
    		  {
    		  		   $router['app'] = $udi[0];
    		  		   if( is_numeric($udi[1]))
		    		   {
		    				$router['controller'] = $this->getDefaultSetting($router['app'],'controller');
		    				$router['dkcode'] = $udi[1];
		    			}
		    			else
		    			{
		    				$router['controller'] = $udi[1];
		    			}
    			      $router['action'] = $this->getDefaultSetting($router['app'],'action');
    		  }	
    		  else //是远程
    		  {
    		  		//如果有域名
	    		    if (in_array($udi[0], $this->subdomain))
			    	{
		    			if( is_numeric($udi[1]))
		    			{
		    			    $router['app'] = $udi[0];
		    				$router['controller'] = $this->getDefaultSetting($router['app'],'controller');
		    				$router['dkcode'] = $udi[1];
		    				$router['action'] = $this->getDefaultSetting($router['app'],'action');
		    			}
		    			else
		    			{
		    			    $router['app'] = $udi[0];
		    				$router['controller'] = $udi[1];
		    				$router['action'] = $this->getDefaultSetting($router['app'],'action');
		    			}
			    	}
			    	else //没有域名
			    	{
				    	if( is_numeric($udi[1]))
		    			{
		    			    $router['app'] = 'main';
		    				$router['controller'] = $this->getDefaultSetting($router['app'],'controller');
		    				$router['dkcode'] = $udi[1];
		    				$router['action'] = $this->getDefaultSetting($router['app'],'action');
		    			}
		    			else
		    			{
		    		        if (in_array($udi[1], $this->nodomain))
				    		{
				    			$router['app']  = $udi[1];
				    			$router['controller'] = $this->getDefaultSetting($router['app'],'controller');
				    		}
				    		else
				    		{
				    			$router['app'] = 'main';
				    			$router['controller'] = $udi[1];
				    		}   
	        				$router['action'] = $this->getDefaultSetting($router['app'],'action'); 	
		    			}
			    	}
    		  }	
    	}
    	elseif($count == 3)
    	{
    		   if($is_local)
    		   {
    		   		   $router['app'] = $udi[0];
    		  		   if( is_numeric($udi[1]))
		    		   {
		    				$router['controller'] = $udi[2];
		    				$router['dkcode'] = $udi[1];
		    				$router['action'] = $this->getDefaultSetting($router['app'],'action');
		    			}
		    			else
		    			{
		    				$router['controller'] = $udi[1];
		    				$router['action'] = $udi[2];
		    			}
    		   }
    		   else 
    		   {
		    		   if (in_array($udi[0], $this->subdomain))
				    	{
				    		        $router['app'] = $udi[0];
									if( is_numeric($udi[1]))
						    		{
					    				$router['controller'] =  $udi[2];
					    				$router['dkcode'] = $udi[1];
					    				$router['action'] = $this->getDefaultSetting($router['app'],'action');
					    			}
					    			else
					    			{
					    				$router['controller'] = $udi[1];
					    				$router['action'] = $udi[2];
					    			}
				    	}
				    	else
				    	{
					    		if(is_numeric($udi[1]))
					    			{
					    			    $router['app'] = in_array($udi[2], $this->nodomain) ? $udi[2] : 'main';
					    			    //判断$udi[2]是main模块中的控制器，还是其它模块控制器
					    			    if( $router['app'] == 'main')
					    			    {
					    			    	$router['controller'] = $udi[2];
					    			    }
					    			    else
					    			    {
					    					$router['controller'] =$this->getDefaultSetting($router['app'],'controller');
					    			    }
					    				$router['dkcode'] = $udi[1];
					    				$router['action'] = $this->getDefaultSetting($router['app'],'action');
					    			}
					    			else
					    			{
					    				$router['app'] = in_array($udi[1], $this->nodomain) ? $udi[1] : 'main';
					    				if( $router['app'] == 'main')
					    			    {
					    			    	$router['controller'] = $udi[1];
					    			    	$router['action'] = $udi[2];
					    			    }
					    			    else
					    			    {
					    					$router['controller'] =$udi[2];
					    					$router['action'] = $this->getDefaultSetting($router['app'],'action');
					    			    }
					    			}
				    	}
    		   }
    	}
    	elseif($count == 4)
    	{
    		if($is_local)
    		{
    				  $router['app'] = $udi[0];
    		  		   if( is_numeric($udi[1]))
		    		   {
		    				$router['controller'] = $udi[2];
		    				$router['dkcode'] = $udi[1];
		    				$router['action'] = $udi[3];
		    			}
		    			else
		    			{
		    				$router['controller'] = $udi[1];
		    				$router['action'] = $udi[2];
		    			}
    		}
    		else 
    		{
		    		if(in_array($udi[0], $this->subdomain))
		    		{
		    			        $router['app'] = $udi[0];
				    			if( is_numeric($udi[1]))
				    			{
				    				$router['controller'] = $udi[2];
				    				$router['dkcode'] = $udi[1];
				    				$router['action'] = $udi[3];
				    			}
				    			else
				    			{
				    			    $router['controller'] = $udi[1];
				    				$router['action'] = $udi[2];
				    			}
		    		}
		    		else
				    {
			    				if(is_numeric($udi[1]))
				    			{
				    			    $router['app'] = in_array($udi[2], $this->nodomain) ? $udi[2] : 'main';
				    			 	if( $router['app'] == 'main')
					    			{
					    			   	$router['controller'] = $udi[2];
					    			   	$router['action'] = $udi[3];
					    			}
					    			else
					    			{
					    				$router['controller'] = $udi[3];
					    				$router['action'] = $this->getDefaultSetting($router['app'],'action');
					    			}
				    				$router['dkcode'] = $udi[1];
				    			}
				    			else
				    			{
				    				 $router['app'] = in_array($udi[1], $this->nodomain) ? $udi[1] : 'main';
				    				if( $router['app'] == 'main')
					    			{
					    			   	$router['controller'] = $udi[1];
					    			   	$router['action'] = $udi[2];
					    			}
					    			else
					    			{
					    				$router['controller'] = $udi[2];
					    				$router['action'] = $udi[3];
					    			}
				    			}
				    }
		    }
    	}
    	
    	return $router;
    }
	
	/*
	* 路由调度
	*
	*/
	public function dispatcher()
	{	 
		$controller_class_file = APP_ROOT_PATH . $this->app . DS . 'application' . DS . 'controllers' . DS . $this->controller . '.php';
		if(!file_exists($controller_class_file))
		{
			die('file not exists:' . $controller_class_file);
		}
		
		require_once $controller_class_file;
		
		$object = new $this->controller();
		if(!method_exists($object,$this->action))
		{
		    die('Class ' . $this->controller . ' not exists method:' . $this->action);
		}
		else
		{
		    call_user_func(array($object,$this->action));
		}
	}
	
	/**
	 * 反向解析路由,生成URL
	 */
	public function reverseRemote($udi,$params = array())
	{
	    $url = '';
        $segment = explode('/',trim($udi,'/'));
	    if(count($segment) < 3)
        {
            log_message('Debug','UDI: ' . $udi . ' ，不符合规则');
            return '';
        }
    	$acm['app'] = $segment[0];
        $acm['controller'] = $segment[1];
    	$acm['action'] = $segment[2];
    	if(isset($params['dkcode']))
    	{
    	    $acm['dkcode'] = $params['dkcode'];
    		unset($params['dkcode']);
    	}
		
    	//判断是生成目录还是生成域名
		if($acm['app']=='front')
    	{
    	    	$url = 'http://www' . DOMAIN . '/' . $acm['app'] . '/' . $acm['controller'] . '/' . $acm['action'];
    	}
    	elseif($acm['app'] == 'main')
    	{
	    	    if(isset($acm['dkcode']))
	    		{
	    	        $url = 'http://www' . DOMAIN . '/' . $acm['dkcode'] . '/' . $acm['controller'] . '/' . $acm['action'];
	    		}
	    		else
	    		{
	    		    $url = 'http://www' . DOMAIN . '/' . $acm['controller'] . '/' . $acm['action'];
	    		}
    	}
    	elseif(in_array($acm['app'],$this->subdomain))
    	{
	    		 if(isset($acm['dkcode']))
	    		{
	    		    $url = 'http://' . $acm['app'] . DOMAIN . '/' . $acm['dkcode'] . '/' . $acm['controller'] . '/' . $acm['action'];
	    		}
	    		else
	    		{
	    	        $url = 'http://' . $acm['app'] . DOMAIN . '/' . $acm['controller'] . '/' . $acm['action'];
	    		}
    	}
    	else
    	{
    		 	if(isset($acm['dkcode']))
	    		{
	    		     $url = 'http://www' . DOMAIN . '/' . $acm['dkcode'] . '/' . $acm['app'].'/'. $acm['controller'] . '/' . $acm['action'];
	    		}
	    		else
	    		{
	    	        $url = 'http://www' . DOMAIN . '/' . $acm['app'] . '/' . $acm['controller'] . '/' . $acm['action'];
	    		}
    	}
    	
    	//去除默认的action
    	if($acm['action'] == $this->getDefaultSetting($acm['app'],'action'))
    	{
    	    $url = rtrim(rtrim($url,$acm['action']),'/');    
        	//去除默认的controller
        	if($acm['controller'] == $this->getDefaultSetting($acm['app'],'controller'))
        	{
        	    $url = rtrim(rtrim($url,$acm['controller']),'/');    	    
        	}	    
    	}
    	
    	$query = $params;
    	$query = count($query)>0 ? http_build_query($query) : '';
    	if(!empty($query))
    	{    	    
    	    $query = (empty($query) && strpos($url,'?')!==false) ? $query : '?' . $query;
    	}
        $url .= $query;
    	
    	return $url;
	}
	
	/*
	* 反向解析生成url
	* @param string $udi    udi
	* @param array  $params 参数
	* @param string @router_name 路由名称   
	* @return string|bool 解析成功返回url，失败返回false
	*/
	public function reverseLocal($udi,$params=array())
	{
	    $url = '';
        $segment = explode('/',trim($udi,'/'));
        if(count($segment) < 3)
        {
            die('UDI: ' . $udi . ' ，不符合规则');
        }
    	$acm['app'] = $segment[0];
        $acm['controller'] = $segment[1];
    	$acm['action'] = $segment[2];
    	
    	if(isset($params['dkcode']))
    	{
    	    $acm['dkcode'] = $params['dkcode'];
    		unset($params['dkcode']);
    	}
    	
    	if($acm['app']=='front')
    	{
    	    $url = '/www_duankou/' . $acm['app'] . '/' . $acm['controller'] . '/' . $acm['action'];
    	}
    	elseif($acm['app'] == 'main')
    	{
    	    if(isset($acm['dkcode']))
    		{
    	        $url = '/www_duankou/' . $acm['app'] . '/' . $acm['dkcode'] . '/' . $acm['controller'] . '/' . $acm['action'];
    		}
    		else
    		{
    		    $url = '/www_duankou/' . $acm['app'] . '/' . $acm['controller'] . '/' . $acm['action'];
    		}
    	}
    	else
    	{
    	    if(isset($acm['dkcode']))
    		{
    		    $url = '/www_duankou/' . $acm['app'] . '/' . $acm['dkcode'] . '/' . $acm['controller'] . '/' . $acm['action'];
    		}
    		else
    		{
    	        $url = '/www_duankou/' . $acm['app'] . '/' . $acm['controller'] . '/' . $acm['action'];
    		}
    	}
    	
    	
    	$query = $params;
    	$query = count($query)>0 ? http_build_query($query) : '';
    	if(!empty($query))
    	{    	    
    	    $query = (empty($query) && strpos($url,'?')!==false) ? $query : '?' . $query;
    	}
        $url .= $query;
    	
    	return $url;
	}	
	
	public function url($udi,$params = null)
	{
	    if(LOCAL_RUN==false)
	    {
	       return $this->reverseRemote($udi,$params);
	    }
	    else 
	    {
	        return $this->reverseLocal($udi,$params);
	    }
	}
	
	public function initConfig($config='')
	{
	    if(LOCAL_RUN==true)
	    {
	        $this->router_config = include_once  CONFIG_PATH . 'router.local.php';
	    }
	    else
	    {
	        $this->router_config = include_once CONFIG_PATH . 'router.remote.php';
	    }
		if(is_string($config) && empty($config))
		{
		    $this->routers = $this->router_config['routers'];
		}
		elseif(is_string($config) && !empty($config))		
		{
		    if(isset($this->router_config[$config])) $this->routers = $this->router_config[$config]['routers'];
		}
		elseif(is_array($config) && count($config))
		{
		    $this->routers = $config;
		}
	}
        
        /**
         * 获得控制器名称
         * 
         * @author Yanguang Lan <lanyg.com@gmail.com>
         */
        public function getController()
        {
            return $this->controller;
        }
        
        /**
         * 获得控制器方法名
         * 
         * @author Yanguang Lan <lanyg.com@gmail.com>
         */
        public function getAction()
        {
            return $this->action;
        }
}