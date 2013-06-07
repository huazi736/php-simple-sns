<?php
namespace Domains;

use \Models as Model;

/**
 * 活动model
 * @author hpw
 * @date  2012/07/07
 */
class Event extends \DK_Model
{	

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
	public function getEvent($eid, $gid)
	{
		$sql = "SELECT * FROM group_event WHERE id = {$eid} AND group_id={$gid}";

		$this->row = $this->db->query($sql)->row_array();
		return $this->row;

	}
	
	/**
	 * 创建活动
	 */
	public function create($uid, $gid, $data, $img, $img_s, $img_b)
	{
		

		$inserts = $data;
		$inserts['user_id'] = $uid;
		$inserts['group_id'] = $gid;
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

		$bool = $this->db->insert('group_event', $inserts);

		if (!$bool) 
			throw new Exception('创建活动失败',1);
		
			$eid = $this->db->insert_id();

			//创建者总是确定参加活动
			$insert2 = array(
				'event_id' => $eid,
				'group_id' => $gid,
				'user_id'  => $uid,
				'type'     => 3,
			);

			$this->db->insert('group_event_users', $insert2);
			return $eid;

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
		$this->db->update('group_event', $update);
		$update['join_num'] = $this->row['join_num'];
	}
	/**
	 * 取消活动
	 */
	public function cancel()
	{
		$this->_del_old_img($this->row);
		//删除数据库数据
		$sql = "DELETE FROM group_event WHERE id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$sql = "DELETE FROM group_event_messages WHERE event_id = {$this->row['id']}";
		$this->db->simple_query($sql);

		$sql = "DELETE FROM group_event_users WHERE event_id = {$this->row['id']}";
		$this->db->simple_query($sql);

		return true;
	}
	


	public function getUsers($show_all=false, $only_id=false)
	{
		if (!$show_all) {
			$sql = "SELECT user_id as id, type FROM group_event_users WHERE event_id = {$this->row['id']} AND type > 0";
		}
		else {
			$sql = "SELECT user_id as id, type FROM group_event_users WHERE event_id = {$this->row['id']}";
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
			$tmp = service_api('User', 'getUserList',array($uids));

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

		$sql = "SELECT * FROM group_event_users WHERE event_id = {$this->row['id']} AND user_id = {$uid}";

		
		$row = $this->db->query($sql)->row_array();
		if (empty($row))
			return null;
		return new EventUser($row, $this);
	}
	
	/**
	 * 申请加入活动
	 * @param int $uid   参与人员
	 */
	public function applyJoin($uid, $gid)
	{		
		$sql = "INSERT INTO group_event_users
			(`event_id` ,`user_id` ,`group_id` ,`type`)
			VALUES ({$this->row['id']}, {$uid},{$gid}, 2);";

		$this->db->simple_query($sql);
		$this->_join_num('+1');
	}

	/**
	 * 得到活动的留言
	 */
	public function getMessages($offset, $length)
	{
		$sql = "SELECT * FROM group_event_messages WHERE event_id = {$this->row['id']} ORDER BY id DESC LIMIT {$offset},{$length}";

		
		$rows = $this->db->query($sql)->result_array();

		$uids = array();
		foreach ($rows as $row) {
			$uids[] = $row['user_id'];
		}

		$users = array();
		if ($uids) {
			$tmp = service_api('User', 'getUserList',array($uids));

			foreach ($tmp as $user) {
				$users[$user['uid']] = $user;
			}
		}

		foreach ($rows as $key => $row) {
			$rows[$key]['username'] = $users[$row['user_id']]['username'];
		}

		return $rows;
	}
	
	
	/**
	 * 回复活动
	 */
	public function addMessage($type, $msg, $src, $uid)
	{
		$re['group_name'] = '';
		$re['filename'] = '';
		if ($type == 2) {
			$this->load->fastdfs('default','', 'fdfs');
			$re = $this->fdfs->uploadFile($src, 'jpg');

			$src = $this->fdfs->get_file_url($re['filename'], $re['group_name']);
		}

		$insert = array(
			'event_id' => $this->row['id'],
			'user_id' => $uid,
			'message' => $msg,
			'addtime' => date('Y-m-d H:i:s'),
			'type' => $type,
			'group' => $re['group_name'],
			'filename' => $re['filename']
		);

		
		$this->db->insert('group_event_messages', $insert);

		if ($type == 2) {
			return array($this->db->insert_id(), $src);
		}

		return $this->db->insert_id();
	}

	public function getMessage($rid)
	{
		$sql = "SELECT * FROM group_event_messages WHERE event_id = {$this->row['id']} and id = {$rid}";

		
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
		$this->load->fastdfs('default','', 'fdfs');

		$re = $this->fdfs->uploadFile($img);

		$this->fdfs->uploadSlaveFile($img_s, $re['filename'], '_s');
		$this->fdfs->uploadSlaveFile($img_b, $re['filename'], '_b');

		return $re;
	}

	protected function _del_old_img($row)
	{
		if (!$row['fdfs_group'] || !$row['fdfs_filename']) {
			return;
		}
		$this->load->fastdfs('default','', 'fdfs');
		$this->fdfs->deleteFile($row['fdfs_group'], $row['fdfs_filename']);
		$this->fdfs->deleteFile($row['fdfs_group'], $row['fdfs_filename'], '_s');
		$this->fdfs->deleteFile($row['fdfs_group'], $row['fdfs_filename'], '_b');
	}


	/**
	 * 更新参与人员计数器
	 *
	 * @param string $num  例: '+1' 或 '-1'
	 * @access private
	 */
	public function _join_num($num)
	{
		
		$sql = "UPDATE group_event SET join_num = join_num {$num} WHERE id = {$this->row['id']}";

		$this->db->simple_query($sql);

		$update = $this->row;
		if ($num == '+1') 
			++$update['join_num'];
		else
			--$update['join_num'];
	}


}
