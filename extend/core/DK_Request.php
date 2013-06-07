<?php

class DK_Request
{
    private static $instance;
	/**
	 * 访问的端口号
	 * 
	 * @var int
	 */
	private $_port = null;
	
	/**
	 * 客户端IP
	 * 
	 * @var string
	 */
	private $_clientIp = null;
	
	/**
	 * 路径信息
	 * 
	 * @var string
	 */
	private $_pathInfo = null;
	
	/**
	 * 请求脚本url
	 * 
	 * @var string
	 */
	private $_scriptUrl = null;
	
	/**
	 * 请求参数uri
	 * 
	 * @var string
	 */
	private $_requestUri = null;
	
	/**
	 * 基础路径信息
	 * 
	 * @var string
	 */
	private $_baseUrl = null;
	
	/**
	 * 请求路径信息
	 *
	 * @var string
	 */
	private $_hostInfo = null;
	
	private function __construct()
	{
		if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
		{
			isset($_GET) && $_GET = stripslashes_deep($_GET);
			isset($_POST) && $_POST = stripslashes_deep($_POST);
			isset($_COOKIE) && $_COOKIE = stripslashes_deep($_COOKIE);
			isset($_REQUEST) && $_REQUEST = stripslashes_deep($_REQUEST);
		}
	}
	
	/*
	* 获取Request的对象单例
	*/
	public static function getInstance()
	{
	    if(!isset(self::$instance))
		{
		    self::$instance = new DK_Request();
		}
		return self::$instance;
	}
	
	/**
	 * 返回该请求是否为ajax请求
	 * 
	 * 如果是ajax请求将返回true,否则返回false
	 * 
	 * @return boolean 
	 */
    public function isAjax()
	{
		return !strcasecmp($this->getServer('HTTP_X_REQUESTED_WITH'),'XMLHttpRequest');
	}
	
	/**
	 * 返回请求是否为POST请求类型
	 * 
	 * 如果请求是POST方式请求则返回true,否则返回false
	 * 
	 * @return boolean
	 */
	public function isPost()
	{
		return !strcasecmp($this->getMethod(),'POST');
	}
	
	/**
	 * 返回请求是否为GET请求类型
	 * 
	 * 如果请求是GET方式请求则返回true，否则返回false
	 * 
	 * @return boolean 
	 */
	public function isGet()
	{
		return !strcasecmp($this->getMethod(),'GET');
	}
	
	/**
	 * 获得请求的方法
	 * 
	 * 将返回POST\GET\DELETE等HTTP请求方式
	 * 
	 * @return string 
	 */
	public function getMethod()
	{
		return strtoupper($this->getServer('REQUEST_METHOD'));
	}
	
	public function getQuery($name = null, $default = null)
	{
		return $this->getGet($name, $default);
	}
	
	/**
	 * 获取请求的表单数据
	 * 
	 * 从$_POST获得值
	 * 
	 * @param string $name 获取的变量名,默认为null,当为null的时候返回$_POST数组
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认为null
	 * @return mixed
	 */
	public function getPost($name = null, $default = null)
	{
		if ($name === null) return $_POST;
		return isset($_POST[$name]) ? $_POST[$name] : $default;
	}
	
	/**
	 * 获得$_GET值
	 * 
	 * @param string $name 待获取的变量名,默认为空字串,当该值为null的时候将返回$_GET数组
	 * @param string $defaultValue 当获取的变量不存在的时候返回该缺省值,默认值为null
	 * @return mixed
	 */
	public function getGet($name=null, $default = null)
	{
		if ($name === null) return $_GET;
		return (isset($_GET[$name])) ? $_GET[$name] : $default;
	}
	
	/**
	 * 返回Server的值
	 * 
	 * 如果$name为空则返回所有Server的值
	 * 
	 * @param string $name 获取的变量名,如果该值为null则返回$_SERVER数组,默认为null
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
	 * @return mixed
	 */
	public function getServer($name = null,$default = null)
	{
		if($name === null) return $_SERVER;
		return (isset($_SERVER[$name])) ? $_SERVER[$name] : $default;
	}
	
	/**
	 * 返回cookie的值
	 * 
	 * 如果$name=null则返回所有Cookie值
	 * 
	 * @param string $name 获取的变量名,如果该值为null则返回$_COOKIE数组,默认为null
	 * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
	 * @return mixed
	 */
	public function getCookie($name = null,$default = null)
	{
		if($name === null) return $_COOKIE;
		return (isset($_COOKIE[$name])) ? $_COOKIE[$name] : $default;
	}
	
	public function getEnv($name = null,$default= null)
	{
		if($name === null) return $_ENV;
		return (isset($_ENV[$name])) ? $_ENV[$name] : $default;
	}
	
	public function getFiles($name = null)
	{
		
	}
	
	/**
	 * 返回访问IP
	 * 
	 * 如果获取请求IP失败,则返回0.0.0.0
	 * 
	 * @return string 
	 */
    public function getClientIp() {
		if (($ip = $this->getServer('HTTP_CLIENT_IP')) != null) 
		{
			$this->_clientIp = $ip;
		} elseif (($_ip = $this->getServer('HTTP_X_FORWARDED_FOR')) != null) 
		{
			$ip = strtok($_ip, ',');
			do {
				$ip = ip2long($ip);
				if (!(($ip == 0) || ($ip == 0xFFFFFFFF) || ($ip == 0x7F000001) || (($ip >= 0x0A000000) && ($ip <= 0x0AFFFFFF)) || (($ip >= 0xC0A8FFFF) && ($ip <= 0xC0A80000)) || (($ip >= 0xAC1FFFFF) && ($ip <= 0xAC100000)))) {
					$this->_clientIp = long2ip($ip);
					return;
				}
			} while (($ip = strtok(',')) !== false);
		} elseif (($ip = $this->getServer('HTTP_PROXY_USER')) != null) {
			$this->_clientIp = $ip;
		} elseif (($ip = $this->getServer('REMOTE_ADDR')) != null) {
			$this->_clientIp = $ip;
		} else {
			$this->_clientIp = "0.0.0.0";
		}
		
		return $this->_clientIp;
	}
	
	/**
	 * 初始化请求的资源标识符
	 * 
	 * 这里的uri是去除协议名、主机名的
	 * <pre>Example:
	 * 请求： http://www.duankou.com/example/index.php?a=test
	 * 则返回: /example/index.php?a=test
	 * </pre>
	 * 
	 * @return string 
	 * @throws WindException 当获取失败的时候抛出异常
	 */
	public function getRequestUri()
	{
		if (($requestUri = $this->getServer('HTTP_X_REWRITE_URL')) != null) 
		{
			$this->_requestUri = $requestUri;
		} 
		elseif (($requestUri = $this->getServer('REQUEST_URI')) != null) 
		{
			$this->_requestUri = $requestUri;
			if (strpos($this->_requestUri, $this->getServer('HTTP_HOST')) !== false) $this->_requestUri = preg_replace('/^\w+:\/\/[^\/]+/', '', $this->_requestUri);
		} 
		elseif (($requestUri = $this->getServer('ORIG_PATH_INFO')) != null) 
		{
			$this->_requestUri = $requestUri;
			if (($query = $this->getServer('QUERY_STRING')) != null) $this->_requestUri .= '?' . $query;
		}	
		return $this->_requestUri;
	}
	
	/**
	 * 返回当前执行脚本的绝对路径
	 * 
	 * <pre>Example:
	 * 请求: http://www.duankou.com/example/index.php?a=test
	 * 返回: /example/index.php
	 * </pre>
	 * 
	 * @return string
	 * @throws WindException 当获取失败的时候抛出异常
	 */
	public function getScriptUrl()
	{
		if (($scriptName = $this->getServer('SCRIPT_FILENAME')) == null) 
		{
			//throw new WindException(__CLASS__ . ' determine the entry script URL failed!!!');
		}
		$scriptName = basename($scriptName);
		if (($_scriptName = $this->getServer('SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName) 
		{
			$this->_scriptUrl = $_scriptName;
		} 
		elseif (($_scriptName = $this->getServer('PHP_SELF')) != null && basename($_scriptName) === $scriptName) 
		{
			$this->_scriptUrl = $_scriptName;
		} 
		elseif (($_scriptName = $this->getServer('ORIG_SCRIPT_NAME')) != null && basename($_scriptName) === $scriptName) 
		{
			$this->_scriptUrl = $_scriptName;
		} 
		elseif (($pos = strpos($this->getServer('PHP_SELF'), '/' . $scriptName)) !== false) {
			$this->_scriptUrl = substr($this->getServer('SCRIPT_NAME'), 0, $pos) . '/' . $scriptName;
		} 
		elseif (($_documentRoot = $this->getServer('DOCUMENT_ROOT')) != null && ($_scriptName = $this->getServer(
			'SCRIPT_FILENAME')) != null && strpos($_scriptName, $_documentRoot) === 0) {
			$this->_scriptUrl = str_replace('\\', '/', str_replace($_documentRoot, '', $_scriptName));
		}
		return $this->_scriptUrl;
	}
	
    /**
	 * 返回执行脚本名称
	 * 
	 * <pre>Example:
	 * 请求: http://www.duankou.com/example/index.php?a=test
	 * 返回: index.php
	 * </pre>
	 * 
	 * @return string
	 * @throws WindException 当获取失败的时候抛出异常
	 */
	public function getScript() {
		if (($pos = strrpos($this->getScriptUrl(), '/')) === false) $pos = -1;
		return substr($this->getScriptUrl(), $pos + 1);
	}
	
	/**
	 * 获得主机信息，包含协议信息，主机名，访问端口信息
	 * 
	 * <pre>Example:
	 * 请求: http://www.duankou.com/example/index.php?a=test
	 * 返回： http://www.duankou.com/
	 * </pre>
	 * 
	 * @return string
	 * @throws WindException 获取主机信息失败的时候抛出异常
	 */
	public function getHostInfo()
	{
		$http = $this->isSecure() ? 'https' : 'http';
		if (($httpHost = $this->getServer('HTTP_HOST')) != null)
		{
			$this->_hostInfo = $http . '://' . $httpHost;
		}
		elseif (($httpHost = $this->getServer('SERVER_NAME')) != null) 
		{
			$this->_hostInfo = $http . '://' . $httpHost;
			if (($port = $this->getServerPort()) != null) $this->_hostInfo .= ':' . $port;
		}
		return $this->_hostInfo;
	}
	
    /**
	 * 返回服务端口号
	 * 
	 * https链接的默认端口号为443
	 * http链接的默认端口号为80
	 * 
	 * @return int
	 */
	public function getServerPort() {
		if (!$this->_port) {
			$_default = $this->isSecure() ? 443 : 80;
			$this->setServerPort($this->getServer('SERVER_PORT', $_default));
		}
		return $this->_port;
	}

	/**
	 * 设置服务端口号
	 * 
	 * https链接的默认端口号为443
	 * http链接的默认端口号为80
	 * 
	 * @param int $port 设置的端口号
	 */
	public function setServerPort($port) {
		$this->_port = (int) $port;
	}
	
    /**
	 * 获取基础URL
	 * 
	 * 这里是去除了脚本文件以及访问参数信息的URL地址信息:
	 * 
	 * <pre>Example:
	 * 请求: http://www.duankou.com/example/index.php?a=test 
	 * 1]如果: $absolute = false：
	 * 返回： example    
	 * 2]如果: $absolute = true:
	 * 返回： http://www.duankou.com/example
	 * </pre>
	 * 
	 * @param boolean $absolute 是否返回主机信息
	 * @return string
	 * @throws WindException 当返回信息失败的时候抛出异常
	 */
	public function getBaseUrl($absolute = false) {
		if ($this->_baseUrl === null) $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/.');
		return $absolute ? $this->getHostInfo() . $this->_baseUrl : $this->_baseUrl;
	}
	
	/**
	 * 返回包含由客户端提供的、跟在真实脚本名称之后并且在查询语句（query string）之前的路径信息
	 * 
	 * <pre>Example:
	 * 请求: http://www.duankou.com/example/index.php?a=test
	 * 返回: a=test
	 * </pre>
	 * 
	 * @throws WindException
	 */
	public function getPathInfo()
	{
		$requestUri = urldecode($this->getRequestUri());
		$scriptUrl = $this->getScriptUrl();
		$baseUrl = $this->getBaseUrl();
        $pathInfo = '';
		if (strpos($requestUri, $scriptUrl) === 0)
		{
			$pathInfo = substr($requestUri, strlen($scriptUrl));
		}
		elseif ($baseUrl === '' || strpos($requestUri, $baseUrl) === 0)
		{
			$pathInfo = substr($requestUri, strlen($baseUrl));
		}
		elseif (strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0)
		{
			$pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
		}
		else 
		{
			$pathinfo = $_SERVER['PATH_INFO'];
		}
		
		if (($pos = strpos($pathInfo, '?')) !== false) 
		{
			$pathInfo = substr($pathInfo, 0, $pos);
		}
		
		$this->_pathInfo = trim($pathInfo, '/');
		
		return $this->_pathInfo;
	}
	
	/**
	 * 请求是否使用的是HTTPS安全链接
	 * 
	 * 如果是安全请求则返回true否则返回false
	 * 
	 * @return boolean
	 */
    public function isSecure() 
    {
		return !strcasecmp($this->getServer('HTTPS'), 'on');
	}
	
	/**
	 * 获取请求链接协议
	 * 
	 * 如果是安全链接请求则返回https否则返回http
	 * 
	 * @return string 
	 */
    public function getScheme() 
    {
		return ($this->getServer('HTTPS') == 'on') ? 'https' : 'http';
	}
}