<?php
function url_home($dkcode)
{
	return mk_url('main/index/index', array('dkcode'=>$dkcode));
}

function url_fdfs($group, $filename, $prefix=null)
{
	if (!$group || !$filename) 
		return MISC_ROOT . 'img/default/event.jpg';
	$configs = require CONFIG_PATH.'fastdfs.php';
    $host = $configs["default"]['host'];
	if ($prefix)
	{
		$tmp = explode('.', $filename);
		return 'http://'.config_item('fastdfs_domain')."/{$group}/{$tmp[0]}{$prefix}.{$tmp[1]}";
	}
	else 
		return 'http://'.config_item('fastdfs_domain')."/{$group}/{$filename}";
}

/**
 * 给时间分组
 * 
 *  今天, 本周, 本月, 下月, xx年x月
 *
 *
 * @param string|int $time 时间
 *
 * @return string
 */
function time_group($time)
{
	static $t_today, $t_tomorrow, $t_week, $t_month, $t_n_month; 

	if (!$t_today) {
		//今天
		$t_today = strtotime(date('Y-m-d'));

		//明天0时0分0秒
		$t_tomorrow = $t_today + (60*60*24);
	
		//下周0时0分0秒(这里认为星期天是一周的最后一天)
		$t_week = $t_today + (60*60*24*(8 - date('N', $t_today)));
	
		//下月0时0分0秒
		$t_month = strtotime('+1 month', strtotime(date('Y-m-01', $t_today)));

		//下下月
		$t_n_month = strtotime('+1 month', $t_month);

	}

	if (is_string($time)) {
		$time = strtotime($time);
	}

		/*
		 * time < t_tomorrow 今天
		 * time < t_week 本周
		 * time < t_month 本月
		 * time < t_n_month 下月
		 * other xx年x月
		 */
		if ($time < $t_tomorrow) {
			return '今天';
		}
		else if ($time < $t_week) {
			return '本周';
		}
		else if ($time < $t_month) {
			return '本月';
		}
	//	else if ($time < $t_n_month) {
	//		return '下月';
	//	}
		else {
			return '将来';
		}
	
}

/**
 * service 接口调用
 * @param string $serviceName 接口名称
 * @param string $method 接口方法
 * @param mixed $params 接口参数
 * @author hpw
 * @date 2012/07/07
 */
function service_api()
{
	$param = func_get_args();
	$serverName = $param[0];
	$method = $param[1];
	if(is_array($param[2]))
		$params = $param[2];
	else 
		$params = array($param[2]);
	$server = service($serverName);
	return call_user_func_array(array($server, $method),$params);
}
