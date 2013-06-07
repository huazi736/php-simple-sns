<?php
namespace Domains;
use \Models as Model;
use \Exception;
/**
 * 活动model
 * @author hpw
 * date 2012/04/26
 */
class Event extends Row
{
	public $webinfo;
	public $user;

	public function __construct($webinfo, $user, $row)
	{
		$this->webinfo = $webinfo;
		$this->user = $user;
		parent::__construct($row);
	}
	
	/**
	 * 编辑活动
	 *
	 * @param array      $data    活动详情信息
	 * @param localfile  $img
	 * @param localfile  $img_s
	 * @param localfile  $img_b
	 */
	public function edit($data, $img, $img_s, $img_b)
	{
		
		$update = $data;

		if ($img)
		{
			$re = $this->saveImg($img, $img_s, $img_b);
			$update['fdfs_group'] = $re['group_name'];
			$update['fdfs_filename'] = $re['filename'];
			$this->delImg();
		}
		else
		{
			$update['fdfs_group'] = $this->row['fdfs_group'];
			$update['fdfs_filename'] = $this->row['fdfs_filename'];
		}
		
		$this->db->where('id', $this->row['id']);
		$this->db->update('web_event', $update);

		$this->_timeline($update);
		$this->_notice($update);
		$update['join_num'] = $this->row['join_num'];
		$this->_search($update);

		return true;
	}
	/**
	 * 回复
	 */
	public function addMessage($type, $msg, $src = '')
	{
		$re['group_name'] = '';
		$re['filename'] = '';
		if ($type == 2)
		{
			$this->load->fastdfs('default','', 'fdfs');
			$re = $this->fdfs->uploadFile($src, 'jpg');
			$src = url_fdfs($re['group_name'],$re['filename']);						
		}
		$insert = array(
			'event_id' => $this->row['id'],
			'uid' => $this->user['uid'],
			'message' => $msg,
			'addtime' => date('Y-m-d H:i:s'),
			'type' => $type,
			'group' => $re['group_name'],
			'filename' => $re['filename']
		);

		
		$this->db->insert('web_event_messages', $insert);
		
		if ($this->user['uid'] != $this->webinfo['uid'])
		{
			service_api('Notice', 'add_notice' ,
			array(
				$this->webinfo['aid'],
				$this->user['uid'],
				$this->webinfo['uid'],
				'web',
				'event_message_web',
				array(
					'name' => $this->row['name'],
					'url' => mk_url('wevent/event/detail', array('id'=>$this->row['id'], 'web_id'=>$this->webinfo['aid']))
				))
			);
		}

		if ($type == 2)
			return array($this->db->insert_id(), $src);
		return $this->db->insert_id();
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


	protected function _search($new)
	{
		$check = array('join_num');
		$df1 = $this->_diff($new, $check);

		$check = array('fdfs_group', 'fdfs_filename', 'starttime', 'name', 'detail');
		$df2 = $this->_diff($new, $check);

		if($df1)
		{
			$event_info = array(
				'id'=> $this->row['id'],
				'type' => 1,
			);
		}
		elseif($df2)
		{
			$event_info = array(
				'id'=> $this->row['id'],
				'is_web' => 1,
				'starttime'=> $new['starttime'],
				'filename'=> $new['fdfs_filename'],
				'groupname'=> $new['fdfs_group'],
				'title'=> $new['name'],
				'join_num'=> $this->row['join_num'],
				'uid'=>$this->webinfo['uid'],
				'uname'=>$this->webinfo['name'],
				'endtime'=>$new['endtime'],
				'detail'=>$new['detail'],
				'address'=> $new['address'],
				'time'=>strtotime($this->row['addtime']),
				'web_id'=>$this->webinfo['aid']
				
			);
		}
		else
			return;
		service_api('RestorationSearch', 'restoreEventInfo', array($event_info));
	}

	/**
	 * 时间线修改
	 */
	protected function _timeline($new)
	{
		$check = array('starttime', 'name', 'fdfs_group', 'fdfs_filename');

		if (!$this->_diff($new, $check)) {
			return;
		}

		$timeline_data = array();
		$timeline_data['type'] = 'event';
		$timeline_data['fid'] = $this->row['id'];
		$timeline_data['pid'] = $this->webinfo['aid'];
		$timeline_data['starttime'] = strtotime($new['starttime']);
		$timeline_data['title'] = htmlspecialchars($new['name']);			
		$timeline_data['photo'] = url_fdfs($new['fdfs_group'], $new['fdfs_filename'], '_s');
		$timeline_data['dateline'] = date('YmdHis');

		//给时间线发送信息
		service_api('WebTimeline', 'updateWebtopicByMap', array($timeline_data, false));
	}

	/**
	 * 发送通知
	 */
	protected function _notice($new)
	{
		$check = array('starttime', 'endtime', 'name', 'address');

		if (!$this->_diff($new, $check))
			return;	
		$this->sendNotice('event_update_web', $new['name']);
	}

	/**
	 * 取消活动
	 */
	public function cancel()
	{
		
		
		//不删除活动图片(等真正删除时再进行)

		//发送通知
		if (strtotime($this->row['endtime']) > time()) {
			$this->sendNotice('event_c_web',$this->row['name']);
		}
		
		//删除时间线数据
		$tags = service_api('Interest', 'get_web_category_imid', $this->row['webid']);//获取频道Id
		service_api('WebTimeline', 'delWebtopicByMap', array($this->row['id'], 'event', array($tags), $this->webinfo['aid']));
		service_api('RelationIndexSearch', 'deleteAEventOfWeb', $this->row['id']);

		//copy相关数据到删除备份
		$sql = "INSERT INTO del_web_event SELECT * FROM web_event WHERE id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$sql = "INSERT INTO del_web_event_invite SELECT * FROM web_event_invite WHERE event_id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$sql = "INSERT INTO del_web_event_messages SELECT * FROM web_event_messages WHERE event_id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$sql = "INSERT INTO del_web_event_users SELECT * FROM web_event_users WHERE event_id = {$this->row['id']}";
		$this->db->simple_query($sql);


		//删除数据库数据
		$sql = "DELETE FROM web_event WHERE id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$sql = "DELETE FROM web_event_invite WHERE event_id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$sql = "DELETE FROM web_event_messages WHERE event_id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$sql = "DELETE FROM web_event_users WHERE event_id = {$this->row['id']}";
		$this->db->simple_query($sql);


		return true;
	}
	
	/**
	 * 获取当前用户参与情况
	 * @param $uid int 用户id
	 * @param $hide_block bool 是否禁止
	 * @return object or null
	 */
	public function getUser($uid, $hide_block=true)
	{

		if ($hide_block)
			$sql = "SELECT * FROM web_event_users WHERE event_id = {$this->row['id']} AND user_id={$uid} AND type != -1";
		else 
			$sql = "SELECT * FROM web_event_users WHERE event_id = {$this->row['id']} AND user_id={$uid}";
		
		$row = $this->db->query($sql)->row_array();

		if ($row) 
			return new EventUser($this, $row);
		return null;
	}
	
	public function getUsers($only_id = false)
	{
		
		if(!$only_id)
			$sql = "SELECT * FROM web_event_users WHERE event_id = {$this->row['id']} AND type!=-1";	
		else
			$sql = "SELECT user_id FROM web_event_users WHERE event_id = {$this->row['id']} AND type!=-1";	
		$query = $this->db->query($sql);
		
		$rows = array();
		if ($only_id)
			$rows[] = array_values($query->result_array());
		else
		{
			$result = $query->result_array();
			$userIdArr = array();
			foreach($result as $one)
			{
				$userIdArr[] = $one['user_id'];
			}
			if (!empty($userIdArr))
			{
				$userInfoArr = service_api('User', 'getUserList',array($userIdArr));
				foreach($userInfoArr as $one)
				{
					$userinfo[$one['uid']]['username'] = $one['username'];
					$userinfo[$one['uid']]['dkcode'] = $one['dkcode'];
				}
			}
			foreach ($query->result_array() as $row) {
				$row['name'] = $userinfo[$row['user_id']]['username'];
				$row['dkcode'] = $userinfo[$row['user_id']]['dkcode'];

				$row['avatar'] = get_avatar($row['user_id']);
				$row['link'] = url_home($row['dkcode']);
				$rows[$row['user_id']] = $row;
			}
		}
		return $rows;
	}

	/**
	 * 申请加入活动
	 * @param object $event 活动对象
	 */
	public function applyJoin($uid)
	{
		
		
		$sql = "INSERT INTO web_event_users
			(`event_id` ,`user_id` ,`type` ,`answer`)
			VALUES ({$this->row['id']}, {$uid}, 0, 2);";

		$this->db->simple_query($sql);

		$this->_join_num('+1');
	}

	public function getMessage($rid)
	{
		$sql = "SELECT * FROM web_event_messages WHERE event_id = {$this->row['id']} and id = {$rid}";

		
		$row = $this->db->query($sql)->row_array();

		if ($row) {
			return new EventMsg($this, $row);
		}

		return null;
	}
		
	/**
	 * 得到活动的留言
	 */
	public function getMessages($offset, $length)
	{
		$sql = "SELECT * FROM web_event_messages WHERE event_id = {$this->row['id']} ORDER BY id DESC LIMIT {$offset},{$length}";

		
		$rows = $this->db->query($sql)->result_array();

		return $rows;
	}

	/**
	 * 是否显示活动参与用户
	 */
	public function isShowUsers()
	{
		return (bool)$this->row['is_show_users'];
	}

	/**
	 * 通知时间线添加
	 * 
	 * @access private
	 */
	public function _timeline_add()
	{
		$tags = service_api('Interest', 'get_web_category_imid', $this->webinfo['aid']);//获取频道Id
		$photo = url_fdfs($this->row['fdfs_group'], $this->row['fdfs_filename']);

		//给时间线发送信息
		service_api('WebTimeline', 'addWebtopic', array(array(
			'uid' => $this->user['uid'],
			'dkcode' => $this->user['dkcode'],
			'uname' => $this->webinfo['name'],
			'pid' => $this->webinfo['aid'],
			'fid' => $this->row['id'],
			'title' => htmlspecialchars($this->row['name']),
			'type' => 'event',
			'photo' => $photo,
			'url' => mk_url('wevent/event/detail', array('id'=>$this->row['id'], 'web_id'=>$this->webinfo['aid'])),
			'starttime' => strtotime($this->row['starttime']),
			'timedesc' => '',
			'dateline' => date('YmdHis')
		), $tags));
	}

	protected function saveImg($img, $img_s, $img_b)
	{
		$this->load->fastdfs('default','', 'fdfs');

		$reImg = $this->fdfs->uploadFile($img);

		$this->fdfs->uploadSlaveFile($img_s, $reImg['filename'], '_s');
		$this->fdfs->uploadSlaveFile($img_b, $reImg['filename'], '_b');

		return $reImg;
	}

	protected function delImg()
	{
		if (!$this->row['fdfs_group'] || !$this->row['fdfs_filename']) 
			return;

		$this->load->fastdfs('default','', 'fdfs');
		$this->fdfs->deleteFile($this->row['fdfs_group'], $this->row['fdfs_filename']);
		$this->fdfs->deleteFile($this->row['fdfs_group'], $this->row['fdfs_filename'], '_s');
		$this->fdfs->deleteFile($this->row['fdfs_group'], $this->row['fdfs_filename'], '_b');
	}
	
	/**
	 * 发送通知
	 * @param string $cause
	 * @param string $name
	 */
	protected function sendNotice($cause, $name)
	{
		//得到所有相关用户
		$users = $this->getUsers(true); 

		if (!empty($users))
		{
			if ($cause == 'event_c_web') {
				service_api('Notice', 'add_notice' ,array($this->row['webid'], $this->webinfo['uid'], $users, 'web', $cause, array(
					'name' => $name,
					'name1' => $this->webinfo['name'],
					'url' => mk_url('wevent/event/mylist', array('id'=>$this->row['id'], 'web_id'=>$this->row['webid']))
				)));
			}
			else {
				service_api('Notice', 'add_notice' ,array($this->row['webid'], $this->webinfo['uid'], $users, 'web', $cause, array(
					'name' => $name,
					'url' => mk_url('wevent/event/detail', array('id'=>$this->row['id'], 'web_id'=>$this->row['webid']))
				)));
			}
		}
	}

	/**
	 * 更新参与人员计数器
	 *
	 * @param string $num  例: '+1' 或 '-1'
	 * @access private
	 */
	public function _join_num($num)
	{
		

		$sql = "UPDATE web_event SET join_num = join_num {$num} WHERE id = {$this->row['id']}";

		$this->db->simple_query($sql);

		$update = $this->row;
		if ($num == '+1') {
			++$update['join_num'];
		}
		else {
			--$update['join_num'];
		}
		$this->_search(array('join_num'=>$update['join_num']));
	
	}
	
}
