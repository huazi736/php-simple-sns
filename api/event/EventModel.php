<?php

/**
 *
 * 个人活动
 * @author tongxiaoyong
 *
 */
class EventModel extends DkModel {

	public function __initialize() {
		$this->init_db('event');
	}

	/**
	 *
	 * 删除活动接口
	 * @author tongxiaoyong
	 * @date 2012-08-10
	 * @param $eventid
	 * @param $uid
	 */
	public function delEvent($eventid){
		$this->db->query("DELETE FROM event WHERE id = {$eventid}");
		$this->db->query("DELETE FROM event_invite WHERE event_id = {$eventid}");
		$this->db->query("DELETE FROM event_messages WHERE event_id = {$eventid}");
		$this->db->query("DELETE FROM event_users WHERE event_id = {$eventid}");
		$this->db->query("DELETE FROM user_events WHERE type = 1 AND event_id = {$eventid}");
	}

	/**
	 *
	 * 获取活动信息
	 * @author tongxiaoyong
	 * @date 2012-08-10
	 * @param $eventid
	 * @param $uid
	 */
	public function getEvent($eventid, $uid = null){
		$sql = "SELECT * FROM event WHERE id = {$eventid}";
		if($uid){
			$sql.=" AND uid = {$uid}";
		}
		return $this->db->query($sql)->row_array();
	}

	/**
	 *
	 * 删除图片附件
	 * @author tongxiaoyong
	 * @date 2012-08-10
	 * @param $attach //array('group'=>group1,'filename'=filename1)
	 */
	public function delAttach($attach){
		$fdfs = get_storage('event');
		$fdfs->deleteFile($attach['group'] , $attach['filename']);
		$fdfs->deleteFile($attach['group'] , $attach['filename'], '_s');
		$fdfs->deleteFile($attach['group'] , $attach['filename'], '_b');
	}

	/**
	 *
	 * 参加活动人员
	 * @author tongxiaoyong
	 * @date 2012-08-10
	 * @param $eventid
	 */
	public function getEventUser($eventid){
		$sql = "SELECT uid as id, type, answer FROM event_users WHERE event_id = {$eventid}";
		return $this->db->query($sql)->result_array();
	}
}
