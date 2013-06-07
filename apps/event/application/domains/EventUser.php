<?php
namespace Domains;

use \Models as Model;

/**
 * 用户
 */
class EventUser extends Row
{
	public $event;


	/**
	 * @param object $event
	 */
	public function __construct($data, $event=null)
	{
		$this->event = $event;
		$this->row = $data;
		$this->uid = $data['uid'];
		parent::__construct();

	}

	/**
	 * 得到我的活动
	 */
	public function getEvents($offset, $length, $hide=false)
	{
		if ($hide) {
			$sql = "SELECT
				uid, result, type as event_type, event_id
				FROM user_events WHERE uid = '{$this->uid}' AND result=1 AND hide = 0 AND c_endtime > NOW()
				ORDER BY c_starttime,id
				LIMIT {$offset}, {$length}";
		} else {
			$sql = "SELECT
				uid, result, type as event_type, event_id
				FROM user_events WHERE uid = '{$this->uid}' AND result=1 AND c_endtime > NOW()
				ORDER BY c_starttime,id
				LIMIT {$offset}, {$length}";
		}

		$query = $this->db->query($sql);
		return $this->_get_list($query->result_array());
	}

	/**
	 * 得到其它活动
	 */
	public function getOtherEvents($offset, $length)
	{
		$sql = "SELECT
			uid, result, type as event_type, event_id
			FROM user_events WHERE uid = '{$this->uid}' AND result=2 AND c_endtime > NOW()
			ORDER BY c_starttime,id
			LIMIT {$offset}, {$length}";

		$query =  $this->db->query($sql);

		return $this->_get_list($query->result_array());
	}

	/**
	 * 得到己结束活动
	 */
	public function getEndEvents($offset, $length, $hide=false)
	{
		if ($hide) {
			$sql = "SELECT
				uid, result, type as event_type, event_id
				FROM user_events WHERE uid = '{$this->uid}' AND result=1 AND hide = 0 AND c_endtime < NOW()
				ORDER BY c_starttime DESC, id DESC
				LIMIT {$offset}, {$length}";
		} else {
			$sql = "SELECT
				uid, result, type as event_type, event_id
				FROM user_events WHERE uid = '{$this->uid}' AND result=1 AND c_endtime < NOW()
				ORDER BY c_starttime DESC, id DESC
				LIMIT {$offset}, {$length}";
		}


		$query =  $this->db->query($sql);

		return $this->_get_list($query->result_array());
	}

	protected function _get_list($query)
	{
		if (empty($query))
		{
			return array();
		}

		//首页活动和,网页活动
		$eids1 = $eids2 = $rows = $outrows = array();
		foreach ($query as $row) {
			//活动
			if ($row['event_type'] == '1') {
				$eids1[] = $row['event_id'];
				$rows['dev_'.$row['event_id']] = $row;
			}
			//网页活动
			else {
				$eids2[] = $row['event_id'];
				$rows['web_'.$row['event_id']] = $row;
			}
		}

		//活动部分
		if (!empty($eids1)) {
			$sql = "SELECT A.id as id, starttime, endtime, name,address,area,join_num, fdfs_group, fdfs_filename, type, answer
				FROM event AS A
				LEFT JOIN event_users AS B ON(B.uid = {$this->uid} AND A.id = B.event_id)
				WHERE A.id IN(".implode(',', $eids1).")";
			$tmp =  $this->db->query($sql)->result_array();

			include APPPATH.DS.'config'.DS.'area.php';
			$area = json_decode($areaJson);

			foreach ($tmp as $row)
			{
				$nation = '';
				$province = '';
				$city = '';
				if($row['area']){
					$areaArr = explode('/',$row['area']);
					if($areaArr[0]!='-1')
					{
						if(isset($areaArr[0]))
						$nation = $area->$areaArr[0]->area_name;
						if(isset($area->$areaArr[0]->list->$areaArr[1]))
						$province = $area->$areaArr[0]->list->$areaArr[1]->area_name;
						if(isset($area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]))
						$city = $area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]->area_name;
					}
				}
				$row['address'] = $nation.$province.$city.$row['address'];
				$row['img'] = url_fdfs($row['fdfs_group'], $row['fdfs_filename']);
				$outrows['dev_'.$row['id']] = array_merge($rows['dev_'.$row['id']],$row);
			}
		}

		//网页活动部分
		if (!empty($eids2)) {
			$sql = "SELECT A.id as id, A.webid, starttime, endtime, name, fdfs_group, fdfs_filename, type, answer
				FROM web_event AS A
				LEFT JOIN web_event_users AS B ON(B.user_id = {$this->uid} AND A.id = B.event_id)
				WHERE A.id IN(".implode(',', $eids2).")";

			$tmp =  $this->db->query($sql)->result_array();

			foreach ($tmp as $row)
			{
				$row['img'] = url_fdfs($row['fdfs_group'], $row['fdfs_filename']);
				$outrows['web_'.$row['id']] = array_merge($rows['web_'.$row['id']],$row);
			}
		}
		return array_values($outrows);
	}

	public function getCover()
	{
		$event = $this->getEvents(0, 1);

		if (empty($event) || empty($event[0]['fdfs_group'])) {
			$img = MISC_ROOT . "img/default/active.gif";
		}
		else {
			$img = str_replace('_s', '_b', $event[0]['img']);
		}

		return $img;
	}

	public function getEventCount()
	{
		$sql = "SELECT COUNT(*) as num FROM user_events WHERE uid = {$this->uid} AND result=1 AND c_endtime > NOW()";

		$row =  $this->db->query($sql)->row_array();

		if (empty($row)) {
			return 0;
		}
		else {
			return $row['num'];
		}
	}



	/**
	 * 答复活动
	 * @param object $event  活动对象
	 * @param $state 答复结果
	 */
	public function answer($state)
	{

		$sql = "SELECT * FROM event_invite WHERE to_uid = {$this->row['uid']} AND event_id = {$this->event->row['id']}";

		$row =  $this->db->query($sql)->row_array();

		//用户没受到邀请
		if (empty($row)) {
			throw new Exception('邀请数据不存在');
		}

		if ($row['is_answer']) {
			throw new Exception('用户己答复过邀请了');
		}

		//更新邀请跟踪数据
		$sql = "UPDATE event_invite SET is_answer = 1, answer_time = NOW() WHERE id = {$row['id']}";
		$this->db->simple_query($sql);

		$this->_answer($state);

		if ($state == 0) {
			$this->event->_join_num('-1');
		}

		if ($state > 1) {
			//给邀请人发送通知
			api_ucenter_notice_addNotice(1, $this->row['uid'], $row['from_uid'], 'event', 'event_answer', array(
				'name' => $this->event->row['name'],
				'url' => mk_url('event/event/detail', array('id'=>$this->event->row['id']))
			));
		}
	}

	protected function _answer($state)
	{
		switch ($state)
		{
			case 2 :
				$sql = "UPDATE event_users SET answer = {$state}
					WHERE uid = {$this->row['uid']} AND event_id = {$this->event->row['id']}";
				$this->db->simple_query($sql);

				//参加则移到我的活动列表中
				$sql = "UPDATE user_events SET result = 1, hide = 0 WHERE uid = {$this->row['uid']}
				AND type = 1 AND event_id = {$this->event->row['id']}";
				$this->db->simple_query($sql);

				$this->event->_join_num('+1');
				break;
			case 0 :
				//如果是管理员不确定参加则失去管理资格
				$sql = "UPDATE event_users SET answer = {$state}, type = 0
					WHERE uid = {$this->row['uid']} AND event_id = {$this->event->row['id']}";

				$this->db->simple_query($sql);

				//不参加则移到其它活动列表中
				//$sql = "UPDATE user_events SET result = 2, hide = 1 WHERE uid = {$this->row['uid']}
				// AND type = 1 AND event_id = {$this->event->row['id']}";
				//$this->db->simple_query($sql);
				break;
			default:
				throw new Exception("错误的答复值:{$state}");
		}
	}

	/**
	 * 更改答复
	 */
	public function changeAnswer($state)
	{
		//用户复答没有改变
		if ($this->row['answer'] == $state) {
			return;
		}

		//创建者不可以改变答复
		else if ($this->row['type'] == 2) {
			return;
		}

		$this->_answer($state);

		if ($state == 0) {
			$this->event->_join_num('-1');
		}
	}




	/**
	 * 删除用户并禁止用户再次参加活动
	 */
	public function blockUser($user)
	{
		/*
		 * 如果原来用户答复参加的,则用户计器减1
		 */
		if ($user['answer'] == 2 ) {
			$this->event->_join_num('-1');
		}

		$sql = "UPDATE event_users SET type = -1 WHERE event_id = {$this->row['event_id']} AND uid = {$user['uid']} AND type != 2";
		$this->db->query($sql);
        //被禁止的活动
		//$sql = "UPDATE user_events SET result = 2, hide = 1 WHERE type = 1 AND event_id = {$this->row['event_id']} AND uid = {$user['uid']}";
		//$this->db->query($sql);

		//给被禁止人发送通知
		api_ucenter_notice_addNotice(1, $this->row['uid'],$user['uid'],  'event', 'event_ban', array(
			'name' => $this->event->row['name'],
			'url' => mk_url('event/event/mylist')
		));
	}


	/**
	 * 查看用户是否有管理权限
	 */
	public function canAdmin()
	{
		switch ($this->row['type']) {
			case '2' :
				return true;
			case '1' :
				return ($this->row['answer'] == 2);
			default:
				return false;
		}
	}

	/**
	 * 检查指定用户是否可以回复
	 */
	public function canReply()
	{
		if ($this->row['type'] > -1 && $this->row['answer'] != 1) {
			return true;
		}

		return false;
	}

	/**
	 * 推送活动
	 *
	 */
	public function notifyFollowers($data=array())
	{
		$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);

		$eventConfig = require CONFIG_PATH.'event.php';
		$host = $eventConfig['default']['host'];
		$port = $eventConfig['default']['port'];

		$return = @socket_connect($socket, $host, $port);
		if(!$return)
		{
			exec('nohup /usr/local/php/bin/php '.APP_ROOT_PATH.'event/server.php  >/dev/null 2>&1 &', $output, $status);
			if($status == 127)
			{
				$str = date("[Y-m-d H:i:s]", time()).'启动daemon失败';
				error_log($str, 3, VAR_PATH . 'logs/event/client.txt');
				return;
			}
			socket_connect($socket, $host, $port);
		}
			
		$dataArr = array(
				'uid'=>$this->row['uid'],
				'event_id'=>$this->row['event_id'],
				'starttime'=>$this->event->row['starttime'],
				'endtime'=>$this->event->row['endtime'],
		);
		$dataArr = array_merge($dataArr,$data);
		$data = serialize($dataArr);
		if(!socket_write($socket,$data,strlen($data)))
		{
			$str = socket_strerror(socket_last_error());
			error_log($str, 3, VAR_PATH . 'logs/event/client.txt');
		}
		socket_close($socket);
	}
}
