<?php

/**
 * CI SYSTEM COMMON自带函数
 */

// ------------------------------------------------------------------------

/**
* Determines if the current version of PHP is greater then the supplied value
*
* Since there are a few places where we conditionally test for PHP > 5
* we'll set a static variable.
*
* @access	public
* @param	string
* @return	bool	TRUE if the current version is $version or higher
*/
if ( ! function_exists('is_php'))
{
	function is_php($version = '5.0.0')
	{
		static $_is_php;
		$version = (string)$version;

		if ( ! isset($_is_php[$version]))
		{
			$_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
		}

		return $_is_php[$version];
	}
}

// ------------------------------------------------------------------------

/**
 * Tests for file writability
 *
 * is_writable() returns TRUE on Windows servers when you really can't write to
 * the file, based on the read-only attribute.  is_writable() is also unreliable
 * on Unix servers if safe_mode is on.
 *
 * @access	private
 * @return	void
 */
if ( ! function_exists('is_really_writable'))
{
	function is_really_writable($file)
	{
		// If we're on a Unix server with safe_mode off we call is_writable
		if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == FALSE)
		{
			return is_writable($file);
		}

		// For windows servers and safe_mode "on" installations we'll actually
		// write a file then read it.  Bah...
		if (is_dir($file))
		{
			$file = rtrim($file, '/').'/'.md5(mt_rand(1,100).mt_rand(1,100));

			if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
			{
				return FALSE;
			}

			fclose($fp);
			@chmod($file, DIR_WRITE_MODE);
			@unlink($file);
			return TRUE;
		}
		elseif ( ! is_file($file) OR ($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
		{
			return FALSE;
		}

		fclose($fp);
		return TRUE;
	}
}

// ------------------------------------------------------------------------

/**
 * 服务调用函数
 */
function api($class)
{    
    static $services = array();       
//    $class = strtolower($class);
    if(isset($services[$class])) return $services[$class];
    include_once ROOT_PATH . 'extend/core/DK_Api.php';
    include_once ROOT_PATH . 'api/' . ufirst($class) . 'Api.php';
    $className = ufirst($class) . 'Api';
    $object = new $className();
    $services[$class] = $object;
    return $services[$class];   
}

/**
 * 服务调用函数
 */
function service($class)
{    
    // migrate to api
    $apis = array(
        'Relation',
        'WebpageRelation',
        'Timeline',
        'WebTimeline',
        
        'GlobalSearch',
        'PeopleSearch',
        'WebpageSearch',
        'RestorationSearch',
        'RelationIndexSearch',
        
        'Passport',
        'User',
        
        'Attention',
        'Interest',
        
        'UserPurview',
        'SystemPurview',
        
        'Comlike',
        'Share',
        
        'Location',
        'Mail',
    
    	'Webpage',
        
        'Group',
        
        'UserWiki',
        'Webwiki',
        
        'Blog',
        
        'Credit',
        'Cron',
        
        'Video',
        'Ask',
        'Album',
        'WebEvent',
        
        'Queue',
        'Mqsms',
        
        'Message',
        'Notice',
        'Ads',
        
        'Favorite',
    );
    if (in_array(ufirst($class), $apis)) {
        return api($class);
    }
    
    static $services = array();       
//    $class = strtolower($class);
    if(isset($services[$class])) return $services[$class];
    include_once ROOT_PATH . 'extend/core/DK_Service.php';
    include_once ROOT_PATH . 'service/' . ufirst($class) . '.php';
    $className = ufirst($class) . 'Service';
    $object = new $className();
    $services[$class] = $object;
    return $services[$class];    
}

// 第一个转成大字	ucfirst 在服务器上用  cli 模试有问题所以改用这个
function ufirst($str){
	$v		= substr($str,0,1);
	$last	= substr($str,1);
	return strtoupper($v).$last;
}


	/*记录应用日志
	 * $uid   为用户的uid或是端口号
	 * $type  应用日志类型 1=>'front',2=>'video',3=>'album',4=>'ask',
	 * $msg   为数组，里面可以是模块名，方法名，文件名等
	 * $time  为日志记录的时间
	 * $ret   返回值，成功为true，失败为false
	 * 2012-7-24 zengxm
	 */
if ( ! function_exists('log_apps_msg'))
{
	function log_apps_msg($uid, $type, $msg=array(),$time='')
	{
		static $_log;
		$_log =& load_class('Log');
		$ret = $_log->write_apps_log($uid, $type,$msg,$time);
		return $ret;
	}
}

/**
 * 只加载扩展类
 */
function load_extend($class,$directory = 'libraries',$prefix='DK_')
{
    $class = $prefix . $class;
    static $_classes = array();
    if (isset($_classes[$class]))
	{
		return $_classes[$class];
	}
	if (file_exists(EXTEND_PATH.$directory.'/'.$class.'.php'))
	{
	    $name = $class;
		if (class_exists($name) === FALSE)
		{
			require_once(EXTEND_PATH . $directory.'/'.$class.'.php');
		}
		is_loaded($class);

		$_classes[$class] = new $name();
		return $_classes[$class];
	}
}

/**
 * 
 * 取得上一个页面链接的地址
 * @author zengxm
 * @date <2012/06/29>
 * @reutn string
 */
function get_referer()
{
    $refere =  isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    return $refere;
}



/**
 * 
 * 取得当前的url地址
 * return string
 */
function get_url()
{
    $request = DK_Request::getInstance();
    return $request->getHostInfo() . $request->getRequestUri();
}



/**
 * 请求远程SOAP服务
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
/*function call_soap($app, $module, $method, $params = array(), $server_url = '')
{
    if (empty($server_url))
    {
        $web_url = config_item('server_url') . $app;
    }
    else
    {
        $web_url = $server_url;
    }
    if (! class_exists('SoapClient'))
    {
        require_cache(EXTEND_PATH . 'vendor' . DS . 'Nusoap' . DS . 'nusoap.php');
    }
    $client = new SoapClient($web_url);
    $client->decode_utf8 = false;
    $client->xml_encoding = 'utf-8';
    $err = $client->getError();
    if ($err)
    {
        print_r($err);
    }
    //echo '<h2>DeBug</h2>';
    //echo $client->debug_str.'<br/>';
    //echo $client->request.'<br/>';
    //echo $client->response;
    return $client->call('do_call', array($module, $method, $params));
}*/


if(!function_exists('require_cache'))
{
    // 优化的require_once
    function require_cache($filename) {
        static $_importFiles = array();
        $filename = realpath($filename);
        if (!isset($_importFiles[$filename])) {
            if (file_exists_case($filename)) {
                require $filename;
                $_importFiles[$filename] = true;
            } else {
                $_importFiles[$filename] = false;
            }
        }
        return $_importFiles[$filename];
    }
}

if(!function_exists('file_exists_case'))
{
    // 区分大小写的文件存在判断
    function file_exists_case($filename) {
        if (is_file($filename)) {
            if (IS_WIN) {
                if (basename(realpath($filename)) != basename($filename))
                    return false;
            }
            return true;
        }
        return false;
    }
}


/**
 * 实现基类自动加载功能
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */

//function __autoload($class)
//{
//    if(strpos($class,'CI_') === 0)
//    {
//        $class = str_replace('CI_','',$class);
//        $extendfile = BASEPATH . 'core' .DS . $class . EXT;
//        if(file_exists($extendfile))
//    	{
//    		require_once $extendfile;
//    	}
//    }
//    if(strpos($class,'MY_') === 0)
//    {
//        $appfile = APPPATH . 'core' . DS . $class . EXT;
//        if(file_exists($appfile))
//    	{
//    		require_once $appfile;
//    	}
//        
//    }
//    if(strpos($class,'DK_') === 0)
//    {
//        $extendfile = EXTEND_PATH . 'core' .DS . $class . EXT;
//        if(file_exists($extendfile))
//    	{
//    		require_once $extendfile;
//    	}
//    }
//    
//}


/**
 * 生成统一的URL,支持不同的模式和路由
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 * @param string $url
 * @param array  $params
 */
//function mk_url($controller='index',$method='index',$params=array(),$return = true)
//{	
//	if(substr($controller,0,4) == 'http')
//	{
//		$realurl = $controller;		
//	}
//	else
//	{
//	    if(strpos($controller,'/') !== false)
//	    {
//	        $segment = explode('/', $controller);
//	        $app = $segment[0];
//	        $controller = $segment[1];
//	        $method = $segment[2];
//	    }
//		//add start 1.0(by jiangfangtao 2012/04/24)
//		$realurl = trim(WEB_ROOT,'/') . '/' . 'index.php?app=' .$app. '&c=' . $controller .'&m=' . $method;
//		//add end 1.0(by jiangfangtao)
//	}
//	
//	if(is_array($params) && count($params)>0)
//	{
//		$realurl = http_build_query($params);
//	}
//	elseif(is_string($params) && strlen($params))
//	{
//		$realurl .= '&' . $params;
//	}
//	if($return)
//	{
//		return $realurl;
//	}
//	else
//	{
//		echo $realurl;
//	}
//}

/**
 * 生成URL路径
 * @author mawenpei<mawenpei@duankou.com>
 * @param string $udi
 * @param array $params 
 * @return  string  string
 */
function mk_url($udi,$params=array())
{
    if (substr($udi, 0, 4) == 'http')
    {
        return $udi;
    }
    $router = DK_Router::getInstance();
	return $router->url($udi,$params);
}


/**
 * 获取SessionID
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 * @return string string
 */
function get_sessionid()
{
    return session_id();
}

/**
 * 设置Session
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 * @param string key 键名
 * @param mix value 键值
 * @param int ttl 有效期
 */
function set_session($key,$value,$ttl = null)
{		 
	return $_SESSION[$key] = $value;
}

/**
 * 获取Session
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 * @param string $key 键名
 * 
 */
function get_session($key)
{		
	return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
}

/**
 * 删除Session
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
function delete_session($key)
{	
	unset($_SESSION[$key]);
}

/**
 * 设置缓存
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
function set_cache($key,$data,$ttl = 0,$group='user')
{
    $memcache = get_memcache($group);
    $key = $group.'_'.$key;
    return $memcache->set($key, $data, $ttl);
}

/**
 * 获取缓存
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
function get_cache($key,$group='user')
{	
    $memcache  = get_memcache($group);
    $key = $group.'_'.$key;
    return $memcache->get($key);
}

/**
 * 删除缓存
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
function delete_cache($key,$group='user')
{
	$memcache = get_memcache($group);
	 $key = $group.'_'.$key;
    return $memcache->delete($key);
}

/**
 * 设置Cookie
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
function set_cookie($name,$value,$expire='',$path='',$domain='')
{
	require_once(EXTEND_PATH . 'libraries' . DS . 'DK_Cookie.php');
	return DK_Cookie::set($name,$value,$expire,$path,$domain);
}

/**
 * 读取Cookie
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
function get_cookie($name)
{
	require_once(EXTEND_PATH . 'libraries' . DS . 'DK_Cookie.php');
	if(!DK_Cookie::is_set($name)) return null;
	return DK_Cookie::get($name);
}

/**
 * 删除Cookie
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
function delete_cookie($name)
{
	require_once(EXTEND_PATH . 'libraries' . DS . 'DK_Cookie.php');
	if(!DK_Cookie::is_set($name)) return null;
	DK_Cookie::delete($name);
}

/**
 * 清除Cookie
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012/02/14>
 */
function clear_cookie()
{
	require_once(EXTEND_PATH . 'libraries' . DS . 'DK_Cookie.php');
	DK_Cookie::clear();
}


/**
 * 输出Widget
 * @param string $name
 * @param array  $data
 * @return bool $return 是否返回
 */
function widget($name, $data = array(), $return = FALSE)
{
    $class = strtolower($name);
    static $_widgets = array();
    if (! isset($_widgets[$name]))
    {
        if (! class_exists('MY_Widget', false))
        {
            require_cache(APPPATH . 'core' . DS . 'MY_Widget' . EXT);
        }
        if (! class_exists($class, false))
        {
            $filepath = APPPATH . 'widgets' . DS . $class . EXT;
            if (file_exists($filepath))
            {
                require_cache($filepath);
            }
            else
            {
            }
        }
        $_widgets[$name] = new $class();
    }
    $widget = $_widgets[$name];
    $content = $widget->render($data);
    if ($return)
    {
        return $content;
    }
    echo $content;
}
/**
 *获取配置
 * lvxinxin add 
 */
function getConfig($cname = '' , $key = '', $methods = 'return'){	
	if(empty($cname)) return false;
	if(file_exists(CONFIG_PATH . $cname . '.php')){
		if($methods == 'return') {
			if(empty($key)){
				return @include(CONFIG_PATH . $cname . '.php');
			}
			else{
				$data = include(CONFIG_PATH . $cname . '.php');
				return $data[$key];
			}
		}elseif($methods == 'noReturn') {
			include(CONFIG_PATH . $cname . '.php');
			if(empty($key)){
				return $config;
			}
			else{
				return $config[$key];
			}
		}
	}
	else{
		return false;
	}
}
/**
 *获取封面
 *lvxinxin add
 */
 function get_cover(){
	if(empty($_SESSION['user']['coverurl'])){
		return false;
	}
	else{
		// $setting = getConfig('fastdfs','avatar');
		return 'http://' . config_item('fastdfs_domain') . $_SESSION['user']['coverurl'];
	}
 }	
/**
 * 获取用户头像
 * $size = ss  30*30
 * $size = s  50*50
 * $size = mm  65*65
 * $size = m  100*100
 * $size = b  125*125
 *
 */
function get_avatar($uid, $size = 's')
{	
    $v = '?v=' . time();
	return AVATAR_DOMAIN . 'avatar_' . $uid . '_' . $size . '.jpg' . $v;
    
}
/**
 * 获取网页头像
 * $size = ss  30*30
 * $size = s  50*50
 * $size = mm  99*99
 * $size = m  100*100
 * $size = b  125*125
 */
function get_webavatar($web_id, $size = 's')
{	
	$v = '?v=' . time();	
	return AVATAR_DOMAIN . 'webavatar_' . $web_id . '_' . $size . '.jpg' . $v;	
}

/**
 * 自动加载APPPATH | EXTEND_PATH | BASEPATH路径下core|libraries目录下的类
 * @author zengxm
 * @param string $class 类名
 * @date <2012/06/27>
 */
function autoload($class)
{
	$class = str_replace('CI_','',trim($class));
	$paths = defined('APPPATH')? array(APPPATH,EXTEND_PATH,BASEPATH) : array(EXTEND_PATH,BASEPATH);
	foreach($paths as $path)
	{
	    if(is_file($path . 'core/' . $class . '.php'))
		{
	        require_once $path . 'core/' . $class . '.php';
		}
	    if(is_file($path . 'libraries/' . $class . '.php'))
		{
		    require_once $path . 'libraries/' . $class . '.php';
		}
	}
}

/**
 * 
 * 除出由addslashes函数添加的' \ ' 反斜线
 * @param array|string $data
 * @return array|string
 */
function stripslashes_deep($data)
{
	if(is_array($data))
	{
		foreach($data as $key=>$value)
		{
			$data[$key] = stripslashes_deep($value);
		}
	}
	else 
	{
		$data = stripslashes($data);
	}
	
	return $data;
}


/**
* Class registry
*
* This function acts as a singleton.  If the requested class does not
* exist it is instantiated and set to a static variable.  If it has
* previously been instantiated the variable is returned.
*
* @access	public
* @param	string	the class name being requested
* @param	string	the directory where the class should be found
* @param	string	the class name prefix
* @return	object
*/
if ( ! function_exists('load_ci_class'))
{
	function &load_ci_class($class, $directory = 'libraries')
	{
		$name = &load_class($class, $directory = 'libraries', 'CI_', TRUE);
        return $name;
	}
}

// --------------------------------------------------------------------

/**
* Class registry
*
* This function acts as a singleton.  If the requested class does not
* exist it is instantiated and set to a static variable.  If it has
* previously been instantiated the variable is returned.
*
* @access	public
* @param	string	the class name being requested
* @param	string	the directory where the class should be found
* @param	string	the class name prefix
* @return	object
*/
//if ( ! function_exists('load_extend_class'))
//{
//	function &load_extend_class($class, $directory = 'libraries')
//	{
//		$name = &load_class($class, $directory = 'libraries', 'DK_', TRUE);
//        return $name;
//	}
//}



//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

function &get_instance()
{
	return CI_Controller::get_instance();
}

/**
 * 
 * 获取一个对象
 * @param string $class
 * @param string $directory
 * @param string $prefix
 * @return object|false
 */
function &load_class($class, $directory = 'libraries', $prefix = 'CI_')
{
	static $_classes = array();

	if (isset($_classes[$class]))
	{
		return $_classes[$class];
	}

	$name = FALSE;

	foreach (array(APPPATH, EXTEND_PATH, BASEPATH) as $path)
	{
		if (file_exists($path.$directory.'/'.$class.'.php'))
		{
			$name = $prefix.$class;
			
			if (class_exists($name) === FALSE)
			{
				require_once($path.$directory.'/'.$class.'.php');
			}
			break;
		}
	}

	if (file_exists(EXTEND_PATH.$directory.'/'.config_item('extend_subclass_prefix').$class.'.php'))
	{
		$name = config_item('extend_subclass_prefix').$class;

		if (class_exists($name) === FALSE)
		{
			require_once(EXTEND_PATH.$directory.'/'.config_item('extend_subclass_prefix').$class.'.php');
		}
	}
	
// Is the request a class extension?  If so we load it too
	if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
	{
		$name = config_item('subclass_prefix').$class;
		if (class_exists($name) === FALSE)
		{
			require_once(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php');
		}
	}
	
	// Did we find the class?
	if ($name === FALSE)
	{
		// Note: We use exit() rather then show_error() in order to avoid a
		// self-referencing loop with the Excptions class
		exit('Unable to locate the specified class: '.$class.'.php');
	}

	// Keep track of what we just loaded
	is_loaded($class);
	$_classes[$class] = new $name();
	return $_classes[$class];
}


/**
 * 
 *返回已经载入的类
 * @param string $class
 * @return array
 */
function is_loaded($class = '')
{
	static $_is_loaded = array();

	if ($class != '')
	{
		$_is_loaded[strtolower($class)] = $class;
	}

	return $_is_loaded;
}

/**
 * @author Yanguang Lan <lanyg.com@gmail.com>
 * 
 * 返回已经载入的数据库类
 * 
 * @param string $class
 * @param string $class
 * @return array
 */
function get_dbs($group = 'default', $db = '')
{
    static $dbs = array();

    if ($db != '')
    {
        $dbs[$group] = $db;
    }

    return $dbs;
}

/**
* Loads the main config.php file
*
* This function lets us grab the config file even if the Config class
* hasn't been instantiated yet
*
* @return	array
*/

if ( ! function_exists('get_config'))
{
	function &get_config($replace = array())
	{
		static $_config;

		if (isset($_config))
		{
			return $_config[0];
		}

		// Is the config file in the environment folder?
		if ( ! defined('ENVIRONMENT') OR ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/config.php'))
		{
			$file_path = CONFIG_PATH . 'config.php';
		}

		// Fetch the config file
		if ( ! file_exists($file_path))
		{
			exit('The configuration file does not exist.');
		}

		require_once($file_path);

		// Does the $config array exist in the file?
		if ( ! isset($config) OR ! is_array($config))
		{
			exit('Your config file does not appear to be formatted correctly.');
		}

		// Are any values being dynamically replaced?
		if (count($replace) > 0)
		{
			foreach ($replace as $key => $val)
			{
				if (isset($config[$key]))
				{
					$config[$key] = $val;
				}
			}
		}

		return $_config[0] =& $config;
	}
}

// ------------------------------------------------------------------------

/**
* Returns the specified config item
*
* @access	public
* @return	mixed
*/
if ( ! function_exists('config_item'))
{
	function config_item($item)
	{
		static $_config_item = array();

		if ( ! isset($_config_item[$item]))
		{
			$config =& get_config();

			if ( ! isset($config[$item]))
			{
				return FALSE;
			}
			$_config_item[$item] = $config[$item];
		}

		return $_config_item[$item];
	}
}


/**
 * 
 * 记录日志
 * @author zengxm
 * @param string $level (Error|Debug|INFO|All)
 * @param array $message ()
 * @param boolean $php_error
 * @return TRUE|FALSE
 */
function log_message($level = 'error', $message, $php_error = FALSE)
{
    static $_log;
	if (config_item('log_threshold') == 0)
	{
		return;
	}
	$_log =&load_class('Log');
	$ret = $_log->write_log($level, $message, $php_error);
	return $ret;
}

	/*记录用户日志
	 * $uid 为用户的uid或是端口号
	 * $msg 为数组，里面可以是模块名，方法名，文件名等
	 * $time为日志记录的时间
	 * 2012-5-28 zengxm
	 */
if ( ! function_exists('log_user_msg'))
{
	function log_user_msg($uid, $msg=array(), $time='',$db='USER')
	{
		static $_log;
		$_log =& load_class('Log');
		$ret = $_log->write_user_log($uid, $msg, $time,$db);
		return $ret;
	}
}


if ( ! function_exists('_exception_handler'))
{
	function _exception_handler($severity, $message, $filepath, $line)
	{
		 // We don't bother with "strict" notices since they tend to fill up
		 // the log file with excess information that isn't normally very helpful.
		 // For example, if you are running PHP 5 and you use version 4 style
		 // class functions (without prefixes like "public", "private", etc.)
		 // you'll get notices telling you that these have been deprecated.
		if ($severity == E_STRICT)
		{
			return;
		}

		$_error =& load_class('Exceptions', 'core');
		
		// Should we display the error? We'll get the current error_reporting
		// level and add its bits with the severity bits to find out.
		if (($severity & error_reporting()) == $severity)
		{
			$_error->show_php_error($severity, $message, $filepath, $line);
		}

		// Should we log the error?  No?  We're done...
		if (config_item('log_threshold') == 0)
		{
			return;
		}
		
		$_error->log_exception($severity, $message, $filepath, $line);
	}
}











/**
* Error Handler
*
* This function lets us invoke the exception class and
* display errors using the standard error template located
* in application/errors/errors.php
* This function will send the error page directly to the
* browser and exit.
*
* @access	public
* @return	void
*/
if ( ! function_exists('show_error'))
{
	function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
	{
		$_error = load_extend('Exceptions', 'core');
		echo $_error->show_error($heading, $message, 'error_general', $status_code);
		exit;
	}
}

// ------------------------------------------------------------------------

/**
* 404 Page Handler
*
* This function is similar to the show_error() function above
* However, instead of the standard error template it displays
* 404 errors.
*
* @access	public
* @return	void
*/
if ( ! function_exists('show_404'))
{
	function show_404($page = '', $log_error = TRUE)
	{
		$_error = load_extend('Exceptions', 'core');
		$_error->show_404($page, $log_error);
		exit;
	}
}

/**
 * Set HTTP Status Header
 *
 * @access	public
 * @param	int		the status code
 * @param	string
 * @return	void
 */
if ( ! function_exists('set_status_header'))
{
	function set_status_header($code = 200, $text = '')
	{
		$stati = array(
							200	=> 'OK',
							201	=> 'Created',
							202	=> 'Accepted',
							203	=> 'Non-Authoritative Information',
							204	=> 'No Content',
							205	=> 'Reset Content',
							206	=> 'Partial Content',

							300	=> 'Multiple Choices',
							301	=> 'Moved Permanently',
							302	=> 'Found',
							304	=> 'Not Modified',
							305	=> 'Use Proxy',
							307	=> 'Temporary Redirect',

							400	=> 'Bad Request',
							401	=> 'Unauthorized',
							403	=> 'Forbidden',
							404	=> 'Not Found',
							405	=> 'Method Not Allowed',
							406	=> 'Not Acceptable',
							407	=> 'Proxy Authentication Required',
							408	=> 'Request Timeout',
							409	=> 'Conflict',
							410	=> 'Gone',
							411	=> 'Length Required',
							412	=> 'Precondition Failed',
							413	=> 'Request Entity Too Large',
							414	=> 'Request-URI Too Long',
							415	=> 'Unsupported Media Type',
							416	=> 'Requested Range Not Satisfiable',
							417	=> 'Expectation Failed',

							500	=> 'Internal Server Error',
							501	=> 'Not Implemented',
							502	=> 'Bad Gateway',
							503	=> 'Service Unavailable',
							504	=> 'Gateway Timeout',
							505	=> 'HTTP Version Not Supported'
						);

		if ($code == '' OR ! is_numeric($code))
		{
			show_error('Status codes must be numeric', 500);
		}

		if (isset($stati[$code]) AND $text == '')
		{
			$text = $stati[$code];
		}

		if ($text == '')
		{
			show_error('No status text available.  Please check your status code number or supply your own message text.', 500);
		}

		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

		if (substr(php_sapi_name(), 0, 3) == 'cgi')
		{
			header("Status: {$code} {$text}", TRUE);
		}
		elseif ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0')
		{
			header($server_protocol." {$code} {$text}", TRUE, $code);
		}
		else
		{
			header("HTTP/1.1 {$code} {$text}", TRUE, $code);
		}
	}
}

/**
 * Remove Invisible Characters
 *
 * This prevents sandwiching null characters
 * between ascii characters, like Java\0script.
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('remove_invisible_characters'))
{
	function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		$non_displayables = array();
		
		// every control character except newline (dec 10)
		// carriage return (dec 13), and horizontal tab (dec 09)
		
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}
		
		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}
}

// ------------------------------------------------------------------------

/**
* Returns HTML escaped variable
*
* @access	public
* @param	mixed
* @return	mixed
*/
if ( ! function_exists('html_escape'))
{
	function html_escape($var)
	{
		if (is_array($var))
		{
			return array_map('html_escape', $var);
		}
		else
		{
			return htmlspecialchars($var, ENT_QUOTES, config_item('charset'));
		}
	}
}

/**
 * 返回一个redis对象
 * @author zengxm
 * @access	public
 * @params  string|array	
 * @return	object|false
 */
if(!function_exists('get_redis')){
	function get_redis($params = '')
    {        
    	if(!$params)
    	{
    		die('请输入初始化参数');
    	}
    	$filename =  EXTEND_PATH . 'libraries/DK_Redis.php';
        if(file_exists($filename))
        {
        	include_once $filename;
        	return DK_Redis::getInstance($params);
        }
        else
        {
        	return false;
        }
    }
}


/**
 * 返回一个mongodb对象
 * @author zengxm
 * @date <2012/06/28>
 * @access	public
 * @params  string|array	
 * @return	object|false
 */
if(!function_exists('get_mongodb'))
{
	function get_mongodb($params = '')
    {    
    	if(!$params)
    	{
    		die('请输入初始化参数');
    	}
    	$filename =  EXTEND_PATH . 'libraries/DK_Mongodb.php';
        if(file_exists($filename))
        {
        	include_once $filename;
        	return DK_Mongodb::getInstance($params);
        }
        else
        {
        	return false;
        }
    }
}


/**
 * 返回一个storage对象
 * @author zengxm
 * @date <2012/06/28>
 * @access	public
 * @params  string|array	
 * @return	object|false
 */
if(!function_exists('get_storage')){
	function get_storage($params = '')
    {        
    	if(!$params)
    	{
    		die('请输入初始化参数');
    	}
    	$filename =  EXTEND_PATH . 'libraries/DK_Storage.php';
        if(file_exists($filename))
        {
        	include_once $filename;
        	return DK_Storage::getInstance($params);
        }
        else
        {
        	return false;
        }
    }
}


/**
 * 
 * 返回一个memcache对象
 * @author zengxm
 * @date <2012/06/28>
 * @access	public
 * @params  string|array	
 * @return	object|false
 */
if(!function_exists('get_memcache')){
	function get_memcache($params = '')
    {        
    	if(!$params)
    	{
    		die('请输入初始化参数');
    	}
    	$filename =  EXTEND_PATH . 'libraries/DK_Memcache.php';
        if(file_exists($filename))
        {
        	include_once $filename;
        	return DK_Memcache::getInstance($params);
        }
        else
        {
        	return false;
        }
    }
}


/**
 * 返回一个httpsqs对象
 * @author zengxm
 * @date <2012/06/28>
 * @access	public
 * @params  string|array	
 * @return	object|false
 */
if(!function_exists('get_httpsqs')){
	function get_httpsqs($params=''){
		if(!$params)
	    	{
	    		die('请输入初始化参数');
	    	}
	    	$filename =  EXTEND_PATH . 'libraries/DK_Httpsqs.php';
	        if(file_exists($filename))
	        {
	        	include_once $filename;
	        	return DK_Httpsqs::getInstance($params);
	        }
	        else
	        {
	        	return false;
	        }
	}
}

/**
 * 得到一个image处理对象
 * 
 * @author vicente
 * @date <2012/07/09>
 * @access	public
 * @params  string|array	
 * @return	object|false
 */
if(!function_exists('get_image')){
	function get_image($params=''){
		if(!$params)
    	{
    		die('请输入初始化参数');
    	}
    	$filename =  EXTEND_PATH . 'libraries/DK_Image.php';
        if(file_exists($filename))
        {
        	include_once $filename;
        	return DK_Image::getInstance($params);
        }
        else
        {
        	return false;
        }
	}
}

/**
 * 返回一个solr对象
 * @access	public
 * @params  string|array	
 * @return	object|false
 */
if(!function_exists('get_solr')){
	function get_solr($params=''){
		if(!$params)
	    	{
	    		die('请输入初始化参数');
	    	}
	    	$filename =  EXTEND_PATH . 'libraries/DK_Solr.php';
	        if(file_exists($filename))
	        {
	        	include_once $filename;
                return new DK_Solr($params);
	        }
	        else
	        {
	        	return false;
	        }
	}
}

/**
 * 获取一个SMS对象
 */
if (!function_exists('get_sms')) {
    function get_sms($params = 'default') {
        if (!$params) {
            die('请输入初始化参数');
        }
        $filename = EXTEND_PATH . 'libraries/DK_Sms.php';
        if (file_exists($filename)) {
            include_once $filename;
            return DK_Sms::getInstance($params);
        } else {
            return false;
        }
    }
}

/**
 * 发起curl请求
 *
 * @author lanyanguang
 * @access	public
 * @param  $method string 方法：get,post....
 * @param  $url string
 * @param  $params array
 * @param  $options array
 * @return	object|false
 */
if(!function_exists('curl_call')){
	function curl_call($method, $url, $params = array(), $options = array()){
	    	$filename =  EXTEND_PATH . 'libraries/DK_Curl.php';
	        if(file_exists($filename))
	        {
	        	include_once $filename;
                $curl =  new DK_Curl();
				return $curl->_simple_call($method, $url, $params = array(), $options = array('CURLOPT_TIMEOUT' => 5));
	        }
	        else
	        {
	        	return false;
	        }
	}		
}

 /**
  * 
  * 
  * @param string
  * @param string
  * @author zengxm
  */
 	function getServer($name = null,$default = null)
	{
		if($name === null) return $_SERVER;
		return (isset($_SERVER[$name])) ? $_SERVER[$name] : $default;
	}
	/**
	 * 返回访问者IP
	 * 如果获取请求IP失败,则返回127.0.0.1
	 * @return string 
	 * @date <2012/07/02>
	 * @author zengxm
	 */
if(!function_exists('get_client_ip'))
{
    function get_client_ip() 
    {
		if (($ip = getServer('HTTP_CLIENT_IP')) != null) 
		{
			$_clientIp = $ip;
		} elseif (($_ip = getServer('HTTP_X_FORWARDED_FOR')) != null) 
		{
			$ip = strtok($_ip, ',');
			do {
				$ip = ip2long($ip);
				if (!(($ip == 0) || ($ip == 0xFFFFFFFF) || ($ip == 0x7F000001) || (($ip >= 0x0A000000) && ($ip <= 0x0AFFFFFF)) || (($ip >= 0xC0A8FFFF) && ($ip <= 0xC0A80000)) || (($ip >= 0xAC1FFFFF) && ($ip <= 0xAC100000)))) {
					$_clientIp = long2ip($ip);
					return;
				}
			} while (($ip = strtok(',')) !== false);
		} elseif (($ip = getServer('HTTP_PROXY_USER')) != null) {
			$_clientIp = $ip;
		} elseif (($ip = getServer('REMOTE_ADDR')) != null) {
			$_clientIp = $ip;
		} else {
			$_clientIp = "127.0.0.1";
		}
		return $_clientIp;
	}
}	

	/**
	 * 检查email是否是合法的邮箱
	 * @param string $email
	 * @return boolean
	 * @date <2012/07/02>
	 * @author zengxm
	 */
if(!function_exists('check_email'))
{	
	function check_email($email)
	{
		if (preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email) && strlen($email) >= 6 && strlen($email) <= 64)
		{
			return true;
		}
		return false;
	}
}

/**
 * 
 * 返回请求方法
 * @author zengxm
 * @date <2012/07/2>
 * @reutn string|false
 */
function get_method()
{
    $refere =  isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : FALSE;
    return $refere;
}

/**
 * Firebug 调试使用
 * 
 * @author devin_yee
 * @param mixed $var 输出变量
 * @param string 输出的标签
 */
function logging($var, $label = 'Test Vars') {
	if (defined('CLI')) {
		return;
	}
	static $logger;
	if (empty($logger)) {
		require_cache(EXTEND_PATH . 'vendor' . DS . 'FirePHP.php');
		$logger = FirePHP::getInstance(true);
	}
	$logger -> info($var, $label);
}

/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. mohu | full | ymd | other
 * @return string
 */
function friendlyDate($sTime, $type = 'mohu') {
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime = time();
    $todayTime = mktime('0', '0', '0', date('m'), date('d'), date('Y'));
    $yestodayTime = mktime('0', '0', '0', date('m'), date('d') - 1, date('Y'));
    $tommrrowTime = mktime('0', '0', '0', date('m'), date('d') + 1, date('Y'));
    $weekTime = $todayTime - date('w', $cTime) * 86400;
    $dTime = $cTime - $sTime;

    if ($type == 'mohu') {
        if ($dTime < 10) {
            return '刚刚';
        }
        if (10 <= $dTime && $dTime < 60) {
            return (ceil($dTime) + 0) . " 秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . " 分钟前";
        }
        //时间在今天0点到明天0点之间
        elseif ($sTime < $tommrrowTime && $sTime > $todayTime) {
            $h = intval($dTime / 3600);
            if (ceil($dTime % 3600 / 60) > 30) {
                $h++;
            }
            if ($h >= 3) {
                return "今天  " . date('H:i', intval($sTime));
            }
            return $h . " 小时前";
        }
        //时间在本周0点到今天0点之间
        elseif ($sTime < $todayTime && $sTime > $weekTime) {
            //时间在今天0点到昨天0点之间
            if ($sTime > $yestodayTime && $sTime < $todayTime) {
                return "昨天 " . date('H:i', intval($sTime));
            }
            //时间在前天0点到昨天0点之间
            elseif ($sTime > ($yestodayTime - 86400) && $sTime < $yestodayTime) {
                return "前天 " . date('H:i', intval($sTime));
            }
            //其他
            else {
                return date("Y年n月j日H:i", intval($sTime));
            }
        } else {
            return date("Y年n月j日H:i", intval($sTime));
        }
    } elseif ($type == 'full') {
        return date("Y-m-d , H:i:s", intval($sTime));
    } elseif ($type == 'ymd') {
        return date("Y-m-d", intval($sTime));
    } else {
        if ($dTime < 60) {
            return $dTime . "秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime / 60) . "分钟前";
        } elseif ($sTime < $tommrrowTime && $sTime > $todayTime) {
            return intval($dTime / 3600) . "小时前";
        } else {
            return date("Y-m-d H:i", intval($sTime)); /* 葛飞超 2012-01-12 去掉 秒 */
        }
    }
}

/**
 * 递归方式的对变量中的特殊字符进行转义
 *	heyuejuan
 * @access  public
 * @param   $value		要转义的字符	（可以是数组）
 *
 * @return  返回  
 */
function addslashes_deep($value)
{
    if (empty($value))
    {
        return $value;
    }
    else
    {
        return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
    }
}


/**
 * @author weijian
 * 字符串加密、解密函数
 * @param	string	$txt		字符串
 * @param	string	$operation	ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE，
 * @param	string	$key		密钥：数字、字母、下划线
 * @return	string
 */

function sysAuthCode($txt, $operation = 'ENCODE', $key = '!@#$%^&*1QAZ2WSX') {
    $key = $key ? $key : 'HZYEYAOMAI2011';
    $txt = $operation == 'ENCODE' ? (string) $txt : str_replace(array('*', '-', '.'), array('+', '/', '='), base64_decode($txt));
    $len = strlen($key);
    $code = '';
    for ($i = 0; $i < strlen($txt); $i++) {
        $k = $i % $len;
        $code .= $txt[$i] ^ $key[$k];
    }
    $code = $operation == 'DECODE' ? $code : str_replace(array('+', '/', '='), array('*', '-', '.'), base64_encode($code));
    return $code;
}

/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其它编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    if ($suffix && $str != $slice)
        return $slice . "...";
    return $slice;
}


/**
 * 取消HTML代码替换原有的htmlspecialchars，支持数组
 * @ pragma string 需要处理的字符串
 * @author fbbin
 */
function shtmlspecialchars($string) {
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = shtmlspecialchars($val);
        }
    } else {
        //$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;', '&nbsp;'), $string));
        $string = htmlspecialchars($string);
    }
    //完全过滤JS
    $string = preg_replace('/<script?.*\/script>/', '********', $string);
    return $string;
}

/**
 * @author  wangying
 * 参数解释 :  视频模块用到的加密解密方法
 * $string： 明文 或 密文   
 * $operation：DECODE表示解密,其它表示加密   
 * $key： 密匙   
 * $expiry：密文有效期
*/
if(!function_exists('authcode')){
	function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0 )
	{
		$ckey_length = 4;
		$key = md5($key);
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
		$cryptkey = $keya.md5($keya.$keyc);
		$key_length = strlen($cryptkey); 
		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
		$string_length = strlen($string);

		$result = '';
		$box = range(0, 255);
		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}  
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}  
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
	   
		if($operation == 'DECODE') {
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			return $keyc.str_replace('=', '', base64_encode($result));
		}
	}
}
/**
 * @author  hujiashan
 * @data    2012/3/23
 * 判断手机号是否正确
 * @param string $mobile
 * 正确 返回true 错误 false
 */
function is_mobile($mobile) {
	return preg_match('/^1[3458][\d]{9}$/', $mobile);
}

/**
 * @author  hujiashan
 * 检查字符串是否是UTF8编码
 * @param string $string
 */
function is_utf8($string) {
	return preg_match('%^(?:
			 [\x09\x0A\x0D\x20-\x7E]            # ASCII
		   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
		   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
		   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
		   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
	   )*$%xs', $string);
}

/**
 *@auth lvxinxin
 *curl调用
 *2012-07-16 add
 */
 function call_curl($url,$flag = true,$timeout = 3000){
	if(empty($url)) return false;
	$res = curl_init();
	$data = array(
			CURLOPT_URL=> $url,
			CURLOPT_CONNECTTIMEOUT_MS=>$timeout,
			CURLOPT_RETURNTRANSFER=>$flag,
	);
	curl_setopt_array($res, $data);
	$result = curl_exec($res);	
	curl_close($res);
	return $result;
 }
/**
 * @author  wangying
 * 得到视频图片完整url地址
 * @param string $filename 例子：video10/M00/01/C0/M251.jpg
 * @param string $prefix 例子：图片从图片的后缀
 * @return string  例子：http://192.168.12.242/video10/M00/01/C0/M251.jpg
 */
if(!function_exists('get_video_img')){
	function get_video_img($filename, $prefix=null){
		if(empty($filename)){
			return MISC_ROOT.'img/default/video_cover.gif';
		}
		$config_video =  CONFIG_PATH . 'video.php';
		if(file_exists($config_video)){
			include $config_video;
			if ($prefix) {
				$tmp = explode('.', $filename);
				return $config['video_pic_domain']."{$tmp[0]}{$prefix}.{$tmp[1]}";
			}else {
				return $config['video_pic_domain']."{$filename}";
			}	
		}else{
			die('视频模块配置文件找不到！');
		}
	}
}

/**
 * 性能测试开始
 * @author mawenpei
 */
function xhprof_start()
{
    include_once EXTEND_PATH . 'vendor' . DS . 'xhprof' . DS . 'xhprof_lib' . DS . 'utils' . DS . 'xhprof_lib.php';
    include_once EXTEND_PATH . 'vendor' . DS . 'xhprof' . DS . 'xhprof_lib' . DS . 'utils' . DS . 'xhprof_runs.php';
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);    
}

/**
 * 性能测试结束
 * @author mawenpei
 */
function xhprof_end()
{
    $xhprof_data = xhprof_disable();
    $xhprof_runs = new XHProfRuns_Default();
    $run_id = $xhprof_runs->save_run($xhprof_data,'xhprof_foo');
    echo '' . '<a href="' . WEB_ROOT . 'extend/vendor/xhprof/xhprof_html/index.php?run=' . $run_id . '&source=xhprof_foo" target="_blank">xhprof</a>';
}





/**
 * 字符串截取
 * str 字符串
 * $len 如果多出多少位就用  “...” 表示
 */
function sm_substrs($str , $len){
	/*
	$len= intval($len);
	if(mb_strlen($str,'utf-8')>$len){
		return mb_substr($str,0,$len,'utf-8').'...';
	}else{
		return $str;
	}
	*/
	$len = $len * 2;
	if(strlen($str)>$len){
		$str2	= $str;
		$mb_str	= __get_sub_str($str , $len-2);
		if($str2!=$mb_str){
			$mb_str = $mb_str.'...';
		}
	}else{
		$mb_str = $str;
	}
	return $mb_str;
}


// 字符串占位的截取
// $str  	字符串
// $count	截取数	如 12 全是中文  就截取了 6个中文    全是英文  那截取了 12 个
function __get_sub_str(&$str,$count){
	$count++;
	$v 		= 0;
	$ret_str= "";
	$str1	= $str;
	$pos	= 0;

	$en_count	= 0; // 英文数字
	while($v<$count){
		$c=substr($str1,0,1);
		if(ord($c)>=128){
			if($v+2 >=$count) break;
			$one_str	= mb_substr($str1,0,1,'utf-8');
			$ret_str 	.= $one_str;
			$jc			= strlen($one_str);
			$str1		= substr($str1,$jc);
			$v			= $v +2;

		}else{
			$ret_str .= $c;
			//echo $c."--";
			$str1		= substr($str1,1);
			$v++;
			//if($en_count%3==0){$v++;}
		}



	}
	$str = $str1;
	return $ret_str;
}

/**
 * 合并js文件
 */
function compile_js($files = array())
{
    if(!is_array($files)) $files = array($files);
    if(LOCAL_RUN == false)
    {
        $str = ''; 
        $str = implode(',',$files);
        $str = rtrim(rtrim(MISC_ROOT,'/'),'misc') . '??' . $str . '?v=' . time();
        echo '<script src=" ' . $str . ' " type="text/javascript"></script>';
    }
    else 
    {
        foreach($files as $js)
        {
            $str = rtrim(rtrim(MISC_ROOT,'/'),'misc') . $js;
            echo '<script src=" ' . $str . ' " type="text/javascript"></script>';
        }
    }
}

function getFastdfs() {
	return config_item('fastdfs_domain');
}

/**
 * 
 * 敏感词过滤
 *  update on 2012/08/03
 *  @author yinyancai  
 *  替换str_replace函数为array_combine
 * 
 * @param	$string		data 过滤的字符串
 * @param	$action		type: 1 => ***  
 * 							  2 => bool 
 * 							  3 => 带颜色文本输出 
 * @param	$color		标记颜色
 */
if (!function_exists('filter')) {

	function filter($string, $action='1', $color = 'red') {
		$fiterArray = array();  //去查询敏感词数组
		$CI =& get_instance();
		$CI->load->database('blog');

		$arr = $CI->db->select('badword,filter')->where('is_delete', '1')->get('filter')->result_array();
		if ($arr) {
			foreach ($arr as $key => $val) {
				if(empty($val['badword']) || empty($val['filter'])) {
					continue;
				}
				$fiterArray['badword'][$key] = $val['badword'];
				$fiterArray['filter1'][$key] = $val['filter'];
				$fiterArray['filter3'][$key] = '<span style="color:' . $color . ';">' . $val['badword'] . '</span>';
				$strtr[$val['badword']] = $val['filter'];
			}
			$cres['strtr'] = $strtr;
			$cres['filter'] = $fiterArray;
		} else {
			return FALSE;
		}
		if ($action == '2') {  // 检验是否有敏感词(有敏感词,返回true,没有false);
			$tempstr = strtr($string, $strtr);
			return ($tempstr == $string) ? false : true;
		} else if ($action == '3') { // 返回标记过敏感词
			$keyword = array_combine($fiterArray['badword'], $fiterArray['filter3']);
			return strtr($string, $keyword );
		} else {     // 直接替换为***
			$keyword = array_combine($fiterArray['badword'], $fiterArray['filter1']);
			return strtr($string, $keyword );
		}
	}
}

/**
 * 是否是一个网址
 *
 * date 2012-6-28
 * @author xwsoul
 */
function is_url($url, $httpPre=true) {
	$pattern = "([a-z0-9\-]+\.)+[a-z0-9]{2,4}(:\d{2,5})?(/.*)?|";
	$patternPre = '|';
	if($httpPre) {
		$patternPre = '|^http(s)?://';
	}
	$pattern = $patternPre.$pattern;
	if (preg_match($pattern, $url)) {
		return true;
	}
	return false;
}

