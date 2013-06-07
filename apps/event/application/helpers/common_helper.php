<?php
/**
 * 判断用户uids是否是用户uid的粉丝
 *
 * @param int   $uid
 * @param array $uids
 */
function users_isFollower($uid, array $uids)
{
	try {
		$re = service('Relation')->checkMultiRelation($uid, $uids, 'follower');
		return $re;
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

/**
 * 判断用户uids和uid是否是互相关注的
 *
 * @param int   $uid
 * @param array $uids
 */
function users_isBothFollow($uid, array $uids)
{
	try {
		$re = service('Relation')->checkMultiRelation($uid, $uids, 'both_following');
		return $re;
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

/**
 * 批量获取用户信息
 */
function api_ucenter_user_getUserList()
{
	try {
		$param = func_get_args();
		$re = call_user_func_array(array(service('User'), 'getUserList'),$param);
		return $re;
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}


/**
 * 得到用户信息
 */
function api_ucenter_user_getUserInfo()
{
	try {
		$param = func_get_args();
		$re = call_user_func_array(array(service('User'), 'getUserInfo'),$param);
		return $re;
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

/**
 * 添加通知接口
 * 使用call_soap,不然发送失败
 */
function api_ucenter_notice_addNotice()
{
	try {
		$param = func_get_args();
		$re = call_user_func_array(array(service('Notice'), 'add_notice'),$param);
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

/**
 * 获取粉丝
 * @param type $uid 用户的ID
 * @param type $offset 页码
 * @param type $limit 每页数量
 * return array
 */

function api_social_social_getFollowersWithInfo()
{
	try {
		$param = func_get_args();
		$re = call_user_func_array(array(service('Relation'), 'getFollowersWithInfo'),$param);
		return $re;
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

function api_social_social_getAllFollowers()
{
	try {
		$param = func_get_args();
		$re = call_user_func_array(array(service('Relation'), 'getFollowersWithInfo'),$param);
		return $re;
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

/**
 * 添加搜所索引
 */
function search_relationindex_addOrUpdateEventInfo()
{
	try {
		$param = func_get_args();
		$re = call_user_func_array(array(service('RelationIndexSearch'), 'addOrUpdateEventInfo'),$param);
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

/**
 * 更新搜所索引
 */
function search_relationindex_restoreEventInfo()
{
	try {
		$param = func_get_args();
		$re = call_user_func_array(array(service('RestorationSearch'), 'restoreEventInfo'),$param);
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

/**
 * 删除搜所索引
 */
function search_relationindex_deleteEvent()
{
	try {
		$param = func_get_args();
		$re = call_user_func_array(array(service('RelationIndexSearch'), 'deleteEvent'),$param);
	}catch(Exception $e) {
		MY_Error::getInstance()->_error_log($e);
	}
}

/**
 * 获取用户资料信息
 * @param int/string $uid
 * @author fbbin
 * @return array
 */
function getUserInfo($uids)
{
	if (empty($uids)){
		return false;
	}
	if(is_string($uids)){
		$uids = array($uids);
	}
	$aResults = api('User')->getUserList($uids,'', 0,1000); //临时显示1000个
	return $aResults;
}
function url_home($dkcode)
{
	return mk_url('main/index/main', array('dkcode'=>$dkcode));
}

function url_fdfs($group, $filename, $prefix=null)
{
	if (!$group || !$filename) {
		return MISC_ROOT . 'img/default/event.jpg';
	}
	return get_storage('event')->get_file_url($filename, $group ,$prefix);
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


/**
 * service 接口调用
 * @param string $serviceName 接口名称
 * @param string $method 接口方法
 * @param mixed $params 接口参数
 * @author hpw
 * @date 2012/07/07
 */
function api_api()
{
	$param = func_get_args();
	$serverName = $param[0];
	$method = $param[1];
	if(is_array($param[2]))
	$params = $param[2];
	else
	$params = array($param[2]);
	$server = api($serverName);
	return call_user_func_array(array($server, $method),$params);
}