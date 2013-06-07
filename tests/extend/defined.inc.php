<?php
//定义目录分隔符
define('DS',DIRECTORY_SEPARATOR);

//定义框架目录
$fwphp_path = str_replace('\\', '/', dirname(__FILE__));
$fwphp_path = $fwphp_path . '/../..';
//define('FWPHP_PATH', dirname(__FILE__) . '/');
define('FWPHP_PATH', $fwphp_path. '/');
//定义配置文件目录
define('CONFIG_PATH',FWPHP_PATH . 'config' . DS);
//定义CI框架文件目录
define('SYS_PATH',FWPHP_PATH . 'system' . DS);
//定义扩展文件目录
define('EXTEND_PATH',FWPHP_PATH . 'extend' . DS);
//定义服务文件目录
define('SERVICE_PATH',FWPHP_PATH . 'service' . DS);
//定义应用根目录
define('APP_ROOT_PATH',FWPHP_PATH . 'apps' . DS);

//定义日志存放根目录
define('LOG_PATH',FWPHP_PATH . 'var/logs/');


define('VAR_PATH',FWPHP_PATH . 'var/');

//定义共公模板的路径
define('TPL_PATH',FWPHP_PATH . 'public' . DS .'tpl' . DS);
//定义文件扩展名
define('EXT','.php');
//定义CI框架文件目录
define('BASEPATH', SYS_PATH);
//定义框架所在目录
define('ROOT_PATH',FWPHP_PATH);
//定义是本地运行为true，远程运行为false
define('LOCAL_RUN',true);
//定义常量
define('APPPATH', '');
define('APP_NAME', '');
if(LOCAL_RUN)
{
    define('DOMAIN','localhost.duankou.com/www_duankou/');
	define('AVATAR_DOMAIN','http://avatar.duankou.dev/');
	define('WEB_ROOT','http://localhost.duankou.com/www_duankou/');
    define('MISC_ROOT','http://localhost.duankou.com/www_duankou/frontendcore/misc/');
}	
else 
{
    define('DOMAIN','.duankou.com');
	define('AVATAR_DOMAIN','http://avatar.duankou.dev/');
	define('WEB_ROOT','http://www.duankou.com/');
    define('MISC_ROOT','http://static.duankou.com/misc/');
}


//define('IS_CLI',false);
//定义是否为windows系统，是为true，否则为false
define('IS_WIN',false);

