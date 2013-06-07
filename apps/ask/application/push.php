<?php

/**
 * 只能在命令行运行
 */
if (php_sapi_name() != 'cli') {
	die;
}

/**
 * 加载基础环境
 */

require __DIR__ . '/../../../defined.inc.php';

define('CI_VERSION', '2.1.0');
require_once CONFIG_PATH . 'constants.php';
require(EXTEND_PATH . 'helpers/common_helper'.EXT);

if(function_exists('spl_autoload_register')){
	spl_autoload_register('autoload');
}

define('APP_NAME', 'ask');
define('APPPATH', APP_ROOT_PATH . APP_NAME . DS . 'application' . DS);

/**
 * 用文件锁实现单例运行
 */
$fp = fopen(VAR_PATH . '/ask_push.lock', 'w');

$lock = flock($fp, LOCK_EX|LOCK_NB);

if (!$lock) {
	die('locked');
}

register_shutdown_function(function($fp, $lock)
{
	if ($lock) {
		flock($fp, LOCK_UN);
	}

	fclose($fp);

}, $fp, $lock);

//锁结束


/**
 * 循环处理推送列表直到队列为空
 */
while (1) {

	$sql = "SELECT * FROM ask_pushs LIMIT 10";
	$rows = getDatabase()->query($sql)->result_array();;

	if (empty($rows)) {
		break;
	}

	foreach ($rows as $row)
	{
		set_time_limit(30);

		push_to_list($row);

		$sql2 = "DELETE FROM ask_pushs WHERE id = {$row['id']} LIMIT 1";
		getDatabase()->simple_query($sql2);
	}
}

/**
 * 处理推送队列中的单个动作
 */
function push_to_list($row)
{
	$poll = getPoll($row['poll_id']);

	if (!$poll) {
		return;
	}

	switch ($row['type']) {
	case 1: //提问
		switch ($poll['perm']) {
		case 1 : //公开
			$followings = getAllFollowers($row['trigger_uid']);
			do_push($followings, $row, 1);

			$friends = getAllFriends($row['trigger_uid']);
			do_push($friends, $row, 1);
			break;

		case 3 : //粉丝
			$followings = getAllFollowers($row['trigger_uid']);
			do_push($followings, $row, 1);
			break;

		case 4 : //好友
			$friends = getAllFriends($row['trigger_uid']);
			do_push($friends, $row, 1);
			break;
		}
		break;

	case 2: //回答
		switch ($poll['perm']) {
		case 1 : //公开
		case 3 : //粉丝
			$friends = getAllFriends($row['trigger_uid']);
			do_push($friends, $row, 2);
		}

		break;

	case 3: //删除
		delPoll($row['poll_id']);
		break;
	}
}

/**
 * 得到问答
 */
function getPoll($id)
{
	$result = getDatabase()->query("SELECT * FROM ask_polls WHERE id = {$id} LIMIT 1");

	return $result->row_array();
}

/**
 * 删除问答
 */
function delPoll($poll_id)
{
	$db = getDatabase();

	//当影响到的记录行数小于limit数时说明数据库中没有更多的数据了
	$limit = 500;

	//删除用户动态列表数据
	do {
		$db->simple_query("DELETE FROM ask_lists WHERE poll_id = {$poll_id} LIMIT {$limit}");
	}
	while ($db->affected_rows() == $limit);

	//删除关注数据
	do {
		$db->simple_query("DELETE FROM ask_follows WHERE poll_id = {$poll_id} LIMIT {$limit}");
	}
	while ($db->affected_rows() == $limit);

	//删除问答
	$db->simple_query("DELETE FROM ask_polls WHERE id = {$poll_id} LIMIT 1");

	//删除时间线动态
	$query = $db->query("SELECT DISTINCT uid FROM ask_voters WHERE poll_id = {$poll_id}");
	foreach ($query->result_array() as $row) {
		delVoteToTimeline($row['uid'], $poll_id);
	}

	//删除投票数据
	do {
		$db->simple_query("DELETE FROM ask_voters WHERE poll_id = {$poll_id} LIMIT {$limit}");
	}
	while ($db->affected_rows() == $limit);

	//删除选项
	$db->simple_query("DELETE FROM ask_options WHERE poll_id = {$poll_id}");
}

function do_change_type($poll_id, $from_uid)
{
	$db = getDatabase();

	$sql = "UPDATE SET type = 3 WHERE from_uid = {$from_uid} AND poll_id = {$poll_id} AND type = 1";

	$db->simple_query($sql);
}

/**
 * 分批量插入数据
 */
function do_push($to_uids, $row, $type)
{
	$db = getDatabase();
	static $fields = array('uid', 'from_uid', 'type', 'poll_id', 'addtime');

	while ($slice = array_splice($to_uids, 0, 100)) {

		$sql = "SELECT * FROM ask_lists
			WHERE
			from_uid = {$row['trigger_uid']} AND
			poll_id = {$row['poll_id']}
			AND uid IN (".implode(',', $slice) . ")";

		$tmps = $db->query($sql)->result_array();

		$update_type = array();

		foreach ($tmps as $tmp) {
			$key = array_search($tmp['uid'], $slice);

			if ($key !== false) {
				if ($tmp['type'] == 1 && $type == 2) {
					$update_type[] = $tmp['uid'];
				}

				unset($slice[$key]);
			}
		}

		$data = array();

		foreach ($slice as $uid) {
			$data[] = array(
				'uid' => $uid,
				'from_uid' => $row['trigger_uid'],
				'type' => $type,
				'poll_id' => $row['poll_id'],
				'addtime' => $row['addtime']
			);
		}

		if ($data) {
			$db->insert_batch('ask_lists', $data);
		}

		if ($update_type) {
			$sql2 = "UPDATE SET type = 3 FROM ask_lists WHERE
				from_uid = {$row['trigger_uid']} AND
				poll_id = {$row['poll_id']} AND
				type = 1 AND
				uid IN (".implode(',', $update_type).")";

			$db->simple_query($sql2);
		}
	}
}

/**
 * 得到粉丝
 */
function getAllFollowers($uid)
{
	$re = service('Relation')->getAllFollowers($uid);

	if (is_array($re)) {
		return $re;
	}

	throw new Exception('getAllFriends api fail');
}

/**
 * 得到好友
 */
function getAllFriends($uid)
{
	$re = service('Relation')->getAllFriends($uid);

	if (is_array($re)) {
		return $re;
	}

	throw new Exception('getAllFriends api fail');
}

function getDatabase()
{
	static $db;

	if ($db) return $db;

	$db = load_class('Loader','core')->database('ask',true,true);

	return $db;
}

function delVoteToTimeline($uid, $poll_id)
{
	$data = array(
		'uid' => $uid,
		'type' => 'answer',
		//问题ID
		'index' => $poll_id,
		//回答时间
		'ctime' => time(),
	);

	api('Timeline')->removeMultiItem($data);
}

