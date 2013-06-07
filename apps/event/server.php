<?php
/**
 * 活动邀请daemon
 * @author  hpw
 */
set_time_limit(0);
require __DIR__.'/../../defined.inc.php';
$eventConfig = require CONFIG_PATH.'event.php';
$address = $eventConfig['default']['host'];//本机地址
$port = $eventConfig['default']['port'];//端口号	

require CONFIG_PATH.'database.php';
$host = $db['event']['hostname'];//数据库地址
$username = $db['event']['username'];//数据库账号
$passwd = $db['event']['password'];//数据库密码
$dbname = $db['event']['database'];//库名

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, $address, $port);
socket_listen($socket);
while(1)
{
	$link = socket_accept($socket);		
	if(!socket_recv($link, $data, 10240, MSG_WAITALL))
	{
		$str = date('[Y-m-d H:i:s]', time()).": ".socket_strerror(socket_last_error($socket));
		error_log($str, 3, VAR_PATH . 'logs/event/server.txt');
		goto There;
	}
	$mysqlLink = mysql_connect($host,$username,$passwd);
	mysql_select_db($dbname,$mysqlLink);
	$dataArr = unserialize($data);
	$str = date('[Y-m-d H:i:s] ', time()). ": " .$data. "\n";
	error_log($str, 3, VAR_PATH . 'logs/event/data.txt');
	
	$uidArr = $dataArr['uids'];
	
	foreach($uidArr as $uid)
	{
		$sql = "SELECT 1 FROM user_events WHERE uid = {$uid} AND type = 1 AND event_id={$dataArr['event_id']} ";
		$rs = mysql_query($sql,$mysqlLink);
		if(!mysql_num_rows($rs))
		{
			$insertSql = "insert into user_events(uid,result,hide,type,event_id,c_starttime,c_endtime,from_uid)values({$uid},1,0,1,{$dataArr['event_id']},'{$dataArr['starttime']}','{$dataArr['endtime']}',{$dataArr['uid']})";
			$rs = mysql_query($insertSql);
			$insertSql = "insert into event_users(event_id,uid,type,answer)values({$dataArr['event_id']},{$uid},0,1)";
			$rs = mysql_query($insertSql);
			$insertSql = "insert into event_invite(event_id,from_uid,to_uid,send_time,is_answer,answer_time)values({$dataArr['event_id']},{$dataArr['uid']},{$uid},'".date('Y-m-d H:i:s', time())."',0,0)";
			$rs = mysql_query($insertSql);
			
		}
		if(!$rs)
		{
			$str =  date('[Y-m-d H:i:s] ' , time()). ": " . mysql_error() . "\n";
			error_log($str, 3, VAR_PATH . 'logs/event/server.txt');
			continue;
			
		}
		
	}		
	mysql_close($mysqlLink);
	There:
	socket_close($link);
}
socket_close($socket);
//nohup /usr/local/php/bin/php /new_duankou/single/event/application/server.php  >/dev/null 2>&1 &
