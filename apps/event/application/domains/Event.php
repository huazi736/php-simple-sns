<?php
namespace Domains;

use \Models as Model;

/**
 * 活动
 */
class Event extends \DK_Model
{
	private $user = null;
	public function __construct()
	{
		parent::__construct();
		$this->init_db('event');
	}
	/**
	 * 查找一个活动
	 * 成功反回:Event
	 * 未找到null
	 */
	public function getEvent($eid ,$uid = null)
	{
		$sql = "SELECT * FROM event WHERE id = {$eid}";
		if($uid){
			$sql.=" and uid = {$uid}";
		}
		$this->row = $this->db->query($sql)->row_array();
		return $this->row;

	}

	/**
	 * 创建活动
	 */
	public function create($curuser = array(), $data, $img, $img_s, $img_b)
	{
		$inserts = $data;
		$inserts['uid'] = $curuser['uid'];
		//创建者总是参加,所以最初人数为1
		$inserts['join_num'] = '1';
		$inserts['addtime'] = date('Y-m-d H:i:s');

		if ($img) {
			$re = $this->_save_img($img, $img_s, $img_b);

			$inserts['fdfs_group'] = $re['group_name'];
			$inserts['fdfs_filename'] = $re['filename'];
		}
		else {
			$inserts['fdfs_group'] = '';
			$inserts['fdfs_filename'] = '';
		}

		$bool = $this->db->insert('event', $inserts);

		if (!$bool) {
			throw new Exception('创建活动失败');
		}
		$eid = $this->db->insert_id();

		//创建者总是确定参加活动
		$insert2 = array(
				'event_id' => $eid,
				'uid' => $curuser['uid'],
				'type' => '2',
				'answer' => '2',
		);

		$this->db->insert('event_users', $insert2);
			
		$insert3 = array(
				'event_id' => $eid,
				'uid' => $curuser['uid'],
				'type' => '1',
				'result' => '1',
				'c_starttime' => $inserts['starttime'],
				'c_endtime' => $inserts['endtime'],
				'from_uid' => 0
		);
		$this->db->insert('user_events', $insert3);

		$photo = url_fdfs($inserts['fdfs_group'], $inserts['fdfs_filename']);
		//给时间线发送信息
		api('Timeline')->addTimeline(array(
				'uid' => $curuser['uid'],
				'dkcode' => $curuser['dkcode'],
				'fid' => $eid,
				'uname' => $curuser['username'],
				'title' => htmlspecialchars($inserts['name']),
				'type' => 'event',
				'photo' => $photo,
				'url' => mk_url('event/event/detail', array('id'=>$eid)),
				'starttime' => strtotime($inserts['starttime']),
				'permission' => 1,
				'dateline' => time()
		));

		$inserts['id'] = $eid;

		$event_info = array(
			'id'=> $eid,
			'uid'=> $curuser['uid'],
			'is_web' => 0,
  			'starttime'=> $inserts['starttime'],
			'filename'=> $inserts['fdfs_filename'],
			'groupname'=> $inserts['fdfs_group'],
			'title'=> $inserts['name'],
			'join_num'=> 1,
			'address'=>$inserts['address'],
			'uname'=>$curuser['username'],
			'endtime'=>$inserts['endtime'],
		    'time' => time(),
			'detail'=>$inserts['detail']
		);

		search_relationindex_addOrUpdateEventInfo($event_info);
		$this->row = $inserts;
		return $eid;

	}
	/**
	 * 编辑活动
	 *
	 * @param int        $uid     用户id
	 * @param array      $data    活动详情信息
	 * @param localfile  $img
	 * @param localfile  $img_s
	 * @param localfile  $img_b
	 */
	public function edit($user, $data, $img, $img_s, $img_b)
	{

		$this->user = $user;
		$uid = $user['uid'];
		$update = $data;

		if ($img) {
			$re = $this->_save_img($img, $img_s, $img_b);

			$update['fdfs_group'] = $re['group_name'];
			$update['fdfs_filename'] = $re['filename'];

			$this->_del_old_img($this->row);
		}
		else {
			$update['fdfs_group'] = $this->row['fdfs_group'];
			$update['fdfs_filename'] = $this->row['fdfs_filename'];
		}

		$this->db->where('id', $this->row['id']);
		$this->db->update('event', $update);
		$this->_timeline($update);
		$this->_notice($update, $uid);
		$this->_updateCache($update);

		$update['join_num'] = $this->row['join_num'];
		$this->_search($update);

		$this->row = array_merge($this->row, $update);

		return true;
	}
	/**
	 * 删除活动
	 */
	public function cancel($uid)
	{
		//先取得受到影响的用户
		$users = $this->getUsers(false, true);

		//删除数据库数据
		$this->db->query("DELETE FROM event WHERE id = {$this->row['id']}");
		$this->db->query("DELETE FROM event_invite WHERE event_id = {$this->row['id']}");
		$this->db->query("DELETE FROM event_messages WHERE event_id = {$this->row['id']}");
		$this->db->query("DELETE FROM event_users WHERE event_id = {$this->row['id']}");
		$this->db->query("DELETE FROM user_events WHERE type = 1 AND event_id = {$this->row['id']}");

		//删除图片
		if(!empty($this->row['fdfs_filename'])){
			$fdfsdata['fdfs_group'] = $this->row['fdfs_group'];
			$fdfsdata['fdfs_filename'] = $this->row['fdfs_filename'];
			$this->_del_old_img($fdfsdata);
		}
		//发送取消通知,不向取消人自己发
		$users = array_diff($users, array($uid));

		if (strtotime($this->row['endtime']) > time() && !empty($users)) {
			api_ucenter_notice_addNotice(1, $uid, $users, 'event', 'event_cancel', array(
				'name' => $this->row['name'],
				'url' => mk_url('event/event/mylist'),
			));
		}
		//通知搜索引擎
		search_relationindex_deleteEvent($this->row['id']);

		//通知时间线数据
		api('Timeline')->removeTimeline($this->row['id'],$uid,'event');

		return true;
	}

	/**
	 * 邀请宾客
	 */
	public function invite($uid, array $invite_users, $sed_notice=true)
	{
		if (empty($invite_users)) {
			return;
		}
		$break = false;
		$users_id = $invite_users;
		if(count($users_id)>10)
		{
			$break = true;
			$this->getUser($uid)->notifyFollowers(array('uids'=>$users_id));
		}

		if (empty($users_id)) {
			return;
		}



		//得到用户列表数据
		$users_list = $this->_getUsers_list($users_id);

		//邀请数据
		$insert1 = array(
			'event_id' => $this->row['id'],
			'uid' => null,
			'type' => '0',
			'answer' => '1',
		);

		$insert2 = array(
			'uid' => null,
			'result' => 1,
			'hide' => 1,
			'type' => 1,
			'event_id' => $this->row['id'],
			'c_starttime' => $this->row['starttime'],
			'c_endtime' => $this->row['endtime'],
			'from_uid' => $uid
		);

		//邀请跟踪数据
		$insert3 = array(
			'event_id' => $this->row['id'],
			'from_uid' => $uid,
			'to_uid' => null,
			'send_time' => date('Y-m-d H:i:s'),
			'is_answer' => 0,
			'answer_time' => 0,
		);

		if($break)
		goto jump;

		//添加数据
		foreach ($users_id as $user)
		{
			//邀请数据
			$insert1['uid'] = $user;
			$this->db->insert('event_users', $insert1);
			$list_id = isset($users_list[$user]) ? $users_list[$user] : null;
			$insert2['uid'] = $user;
			if ($list_id) {
				$this->db->where('id', $list_id);
				$this->db->update('user_events', $insert2);
			}
			else {
				$this->db->insert('user_events', $insert2);
			}
			//邀请跟踪
			$insert3['to_uid'] = $user;
			$this->db->insert('event_invite', $insert3);
		}
		jump:
		$users = array_diff($users_id, array($uid));

		//发送提醒
		// 此api 调用 非常慢，等待优化
		if ($sed_notice && !empty($users)) {
			api_ucenter_notice_addNotice(1, $uid, array_values($users), 'event', 'event_invitejoin', array(
				'name' => $this->row['name'],
				'url' => mk_url('event/event/detail', array('id'=>$this->row['id']))
			));
		}
	}


	public function getUsers($show_all=false, $only_id=false)
	{
		if (!$show_all) {
			$sql = "SELECT uid as id, type, answer FROM event_users WHERE event_id = {$this->row['id']} AND type > -1 AND answer > 0";
		}
		else {
			$sql = "SELECT uid as id, type, answer FROM event_users WHERE event_id = {$this->row['id']}";
		}

		$rows = $query = $this->db->query($sql)->result_array();

		$uids = array();
		foreach ($rows as $row) {
			$uids[] = $row['id'];
		}

		if ($only_id) {
			return $uids;
		}

		$users= array();

		if ($uids) {
			$tmp = getUserInfo($uids);
			foreach ($tmp as $user) {
				$users[$user['uid']] = $user;
			}
		}
		$out = array();

		foreach ($rows as $key => $row) {
			$user = $users[$row['id']];

			$row['name'] = $user['username'];
			$row['dkcode'] = $user['dkcode'];
			$row['avatar'] = get_avatar($row['id']);
			$row['link'] = url_home($row['dkcode']);
			$out[$row['id']] = $row;
		}
		return $out;
	}


	public function getUser($uid)
	{

		$sql = "SELECT * FROM event_users WHERE event_id = {$this->row['id']} AND uid = {$uid}";


		$row = $this->db->query($sql)->row_array();
		if (empty($row))
		return null;
		return new EventUser($row, $this);
	}

	/**
	 * 申请加入活动
	 * @param int $uid   参与人员
	 */
	public function applyJoin($uid)
	{


		$sql = "INSERT INTO event_users
			(`event_id` ,`uid` ,`type` ,`answer`)
			VALUES ({$this->row['id']}, {$uid}, 0, 2);";

		$this->db->simple_query($sql);

		$sql = "SELECT id FROM user_events WHERE uid = {$uid} AND type = 1 AND event_id={$this->row['id']} ";
		$row = $this->db->query($sql)->row_array();

		if (empty($row))
		{
			$sql2 = "INSERT INTO user_events
				(`uid`, `result`, `type`, `event_id`, `c_starttime`,`c_endtime`, `from_uid`)
				VALUES ({$uid}, 1, 1, {$this->row['id']}, '{$this->row['starttime']}', '{$this->row['endtime']}', 0);";
			$this->db->simple_query($sql2);

			$last_id = $this->db->insert_id();
		}
		else
		{
			$sql2 = "UPDATE user_events SET `result` = 1, hide = 0 WHERE id = {$row['id']}";

			$this->db->simple_query($sql2);

			$last_id = $row['id'];
		}
		$this->_join_num('+1');
	}
	/**
	 * 获取活动答复情况的用户
	 * 此方法为暂时，后期合并类似方法
	 * @param int $answerRes  答复情况
	 */
	public function getAnsUsers($answerRes = 0)
	{

		$sql = "SELECT uid as id, type, answer FROM event_users WHERE event_id = {$this->row['id']} AND type > -1 AND answer > {$answerRes}";


		$rows = $query = $this->db->query($sql)->result_array();

		$uids = array();
		foreach ($rows as $row)
		{
			$uids[] = $row['id'];
		}
		return $uids;
	}

	/**
	 * 设置管理员(覆盖)
	 */
	public function setAdmins($uid, array $admins)
	{
		$users = $this->getUsers(true);

		$old_admins = array();
		foreach ($users as $user)
		{
			if ($user['type'] == 1) {
				$old_admins[] = $user['id'];
			}
		}

		//给所有没参与活动的管理发送参加活动邀请
		$need_invite = array_diff($admins, array_keys($users));

		$this->invite($uid, $need_invite, false);



		//清除掉原来的管理员
		$sql = "UPDATE event_users SET type = 0 WHERE event_id = {$this->row['id']} AND type = 1";
		$this->db->query($sql);

		//设置新管理员
		if (!empty($admins)) {
			$sql = "UPDATE event_users SET type = 1
				WHERE event_id = {$this->row['id']} AND uid IN(".implode(',', $admins).") AND type = 0";

			$this->db->query($sql);
		}

		//新管理员
		$is_new_admins = array_diff($admins, $old_admins);

		//给所有新添加管理员发送成为管理员通知
		$tmp = array_diff($is_new_admins, array($uid));
		if (!empty($tmp)) {
			api_ucenter_notice_addNotice(1, $uid, $tmp, 'event', 'event_setting', array(
				'name' => $this->row['name'],
				'url' => mk_url('event/event/detail', array('id'=>$this->row['id']))
			));
		}

		//给被删除的管理员发送通知,过滤掉操作人自己
		$del_notice = array_diff($old_admins, $admins, array($uid));

		if (!empty($del_notice)) {
			api_ucenter_notice_addNotice(1, $uid, $del_notice, 'event', 'event_c_setting', array(
				'name' => $this->row['name'],
				'url' => mk_url('event/event/detail', array('id'=>$this->row['id'])),
			));
		}
	}

	/**
	 *
	 */
	public function getAdmins($show_all=false)
	{
		$sql = "SELECT * FROM event_users WHERE event_id = {$this->row['id']} AND type > 0";



		$rows = $ci->query($sql)->result_array();

		if ($show_all) {
			return $rows;
		}

		foreach ($rows as $key => $row) {
			if ($row['answer'] == '1') {
				unset($rows[$key]);
			}
		}

		return $rows;
	}



	/**
	 * 得到活动的留言
	 */
	public function getMessages($offset, $length)
	{
		$sql = "SELECT * FROM event_messages WHERE event_id = {$this->row['id']} ORDER BY id DESC LIMIT {$offset},{$length}";


		$rows = $this->db->query($sql)->result_array();

		$uids = array();
		foreach ($rows as $row) {
			$uids[] = $row['uid'];
		}

		$users = array();
		if ($uids) {
			$tmp = getUserInfo($uids);

			foreach ($tmp as $user) {
				$users[$user['uid']] = $user;
			}
		}

		foreach ($rows as $key => $row) {
			$rows[$key]['username'] = $users[$row['uid']]['username'];
		}

		return $rows;
	}


	/**
	 * 回复活动
	 */
	public function addMessage($type, $msg, $src, $uid)
	{
		$insert = array(
			'event_id' => $this->row['id'],
			'uid' => $uid,
			'message' => $msg,
			'addtime' => date('Y-m-d H:i:s'),
			'type' => $type
		);
		if ($type == 2) {
			$fdfs = get_storage('event');
			$re = $fdfs->uploadFile($src, 'jpg');
			if(is_array($re)){
				$src = url_fdfs($re['group_name'],$re['filename']);
				$insert['group'] = $re['group_name'];
				$insert['filename'] =  $re['filename'];
			}

		}

		$this->db->insert('event_messages', $insert);

		if ($uid != $this->row['uid']) {
			api_ucenter_notice_addNotice(
			1,
			$uid,
			$this->row['uid'],
				'event',
				'event_message',
			array(
					'name' => $this->row['name'],
					'url' => mk_url('event/event/detail', array('id'=>$this->row['id']))
			)
			);
		}

		if ($type == 2) {
			return array($this->db->insert_id(), $src);
		}

		return $this->db->insert_id();
	}

	public function getMessage($rid)
	{
		$sql = "SELECT * FROM event_messages WHERE event_id = {$this->row['id']} and id = {$rid}";
		$row = $this->db->query($sql)->row_array();

		if (empty($row)) {
			return null;
		}

		return new EventMessage($row);
	}



	/**
	 * 是否显示活动参与用户
	 */
	public function isShowUsers()
	{
		return (bool)$this->row['is_show_users'];
	}


	protected function _save_img($img, $img_s, $img_b)
	{
		$fdfs = get_storage('event');
		$re = $fdfs->uploadFile($img);
		$fdfs->uploadSlaveFile($img_s, $re['filename'], '_s');
		$fdfs->uploadSlaveFile($img_b, $re['filename'], '_b');

		return $re;
	}

	protected function _del_old_img($row)
	{
		if (!$row['fdfs_group'] || !$row['fdfs_filename']) {
			return;
		}

		$fdfs = get_storage('event');
		$fdfs->deleteFile($row['fdfs_group'], $row['fdfs_filename']);
		$fdfs->deleteFile($row['fdfs_group'], $row['fdfs_filename'], '_s');
		$fdfs->deleteFile($row['fdfs_group'], $row['fdfs_filename'], '_b');
	}


	/**
	 * 更新参与人员计数器
	 *
	 * @param string $num  例: '+1' 或 '-1'
	 * @access private
	 */
	public function _join_num($num)
	{

		$sql = "UPDATE event SET join_num = join_num {$num} WHERE id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$update = $this->row;
		if ($num == '+1') {
			++$update['join_num'];
		} else {
			--$update['join_num'];
		}

		$this->_search($update);
	}


	/**
	 * 得到用户列表区数据
	 */
	protected function _getUsers_list(array $users_id)
	{


		//得到用户列表数据
		$sql = "SELECT id, uid FROM user_events WHERE type = 1 AND event_id = {$this->row['id']} AND uid IN (".implode(',', $users_id).")";

		$rows = array();
		foreach ($this->db->query($sql)->result_array() as $row) {
			$rows[$row['uid']] = $row['id'];
		}

		return $rows;
	}

	protected function _diff(array $new, array $check)
	{
		foreach ($check as $key)
		{
			if(!isset($new[$key]))
			continue;
			if ($this->row[$key] != $new[$key])
			return true;
		}

		return false;
	}

	/**
	 * 更新缓存
	 */
	protected function _updateCache($new)
	{
		$check = array('starttime', 'endtime');

		if (!$this->_diff($new, $check)) {
			return;
		}
		$this->db->where('type', '1');
		$this->db->where('event_id', $this->row['id']);
		$this->db->update('user_events', array(
			'c_starttime' => $new['starttime'],
			'c_endtime' => $new['endtime']
		));
	}

	protected function _search($new)
	{
		$check = array('join_num');
		$df1 = $this->_diff($new, $check);

		$check = array('fdfs_group', 'fdfs_filename', 'detail', 'starttime', 'name');
		$df2 = $this->_diff($new, $check);

		if($df1)
		{
			$event_info = array(
				'id'=> $this->row['id'],
				'type' => 0,
			);
		}
		elseif ($df2)
		{
			$event_info = array(
				'id'=> $this->row['id'],
				'type' => 0,
				'starttime'=> $new['starttime'],
				'endtime'=> $new['endtime'],
				'filename'=> $new['fdfs_filename'],
				'groupname'=> $new['fdfs_group'],
				'title'=> $new['name'],
				'uid' =>$this->user['uid'],
				'join_num'=> $this->row['join_num'],
				'detail' => $new['detail'],
				'address'=> $new['address'],
				'uname'=>$this->user['username'],
			    'time' => strtotime($this->row['addtime'])

			);
		}
		else
		return;
		search_relationindex_restoreEventInfo($event_info);
	}

	protected function _timeline($new)
	{
		$check = array('starttime', 'name', 'fdfs_group', 'fdfs_filename');

		if (!$this->_diff($new, $check)) {
			return;
		}

		//时间线修改
		$timeline_data = array();
		$timeline_data['type'] = 'event';
		$timeline_data['fid'] = $this->row['id'];
		$timeline_data['title'] = htmlspecialchars($new['name']);
		$timeline_data['starttime'] = strtotime($new['starttime']);
		$timeline_data['photo'] = url_fdfs($new['fdfs_group'], $new['fdfs_filename']);
		$timeline_data['uid'] = $this->user['uid'];
		api('Timeline')->updateTopic($timeline_data);
	}

	/**
	 * 发送通知
	 */
	protected function _notice($new, $uid)
	{
		$check = array('starttime', 'endtime', 'name', 'address');

		if (!$this->_diff($new, $check)) {
			return;
		}

		//得到所有相关用户
		$users = $this->getAnsUsers(1);
		//排除掉操作者,创建者
		$users = array_diff($users, array($uid, $this->row['uid']));

		//如果操作者不是创建者则给创建者发送通知
		if ($uid != $this->row['uid']) {
			api_ucenter_notice_addNotice(1, $uid, $this->row['uid'], 'event', 'event_edit', array(
				'name' => $new['name'],
				'url' => mk_url('event/event/detail', array('id'=>$this->row['id']))
			));
		}

		if (!empty($users)) {
			api_ucenter_notice_addNotice(1, $uid, $users, 'event', 'event_update', array(
				'name' => $new['name'],
				'url' => mk_url('event/event/detail', array('id'=>$this->row['id']))
			));
		}
	}

}
