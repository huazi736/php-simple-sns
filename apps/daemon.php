<?
/*
 * 服务器上执行这个程序。。
 * 计划任务执行
 */

//加载常量定义文件
require dirname(dirname(__FILE__)) . '/defined.inc.php';

if (php_sapi_name() === 'cli') {
	define('CLI', true);
}

if(isset($argv[1])){
	$_SERVER['key']= $argv[1];
	$_SERVER['HTTP_HOST']	= "daemon".DOMAIN;
	$_SERVER['REQUEST_URI']	= "/execute/index";
}



//加载启动文件
require EXTEND_PATH . 'core' . DS . 'bootrap.php';