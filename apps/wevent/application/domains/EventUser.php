<?php
namespace Domains;

use \Models as Model;
use \Exception;

/**
 * 活动参加人员model
 * @author hpw
 * @date 2012/05/3
 */
class EventUser extends Row
{
	public $event;

	public function __construct($event, $row)
	{
		$this->event = $event;

		if ($row == null) {
			$row = array(
				'id' => 0,
				'event_id' => $this->event['id'],
				'user_id' => $this->event->webinfo['uid'],
				'type' => 2,
				'answer' => 3,
			);
		}

		parent::__construct($row);
	}
	
	/**
	 * 更改答复
	 */
	public function changeAnswer($state)
	{
		if ($this->row['id'] == 0) {
			throw new Exception('网页本身不可改变答复',1);
		}		
		$sql = "UPDATE web_event_users SET answer = '{$state}'
			WHERE user_id = '{$this->row['user_id']}' AND event_id = '{$this->row['event_id']}' ";
		$this->db->simple_query($sql);
		if ($this->row['answer']==2) 
		{
			if ($state == 0)
				$this->event->_join_num('-1');//以前回答参加,现在回答不参加
		}
		else
		{
			if ($state == 2) 
				$this->event->_join_num('+1');//以前回答不参加,现在回答参加

		}
	}

	
	
	/**
	 * 删除用户并禁止用户再次参加活动
	 */
	public function block()
	{		
		 
		if ($this->row['answer'] == 2 ) 
			$this->event->_join_num('-1');//如果原来用户答复参加的,则用户计器减1

		$sql = "UPDATE web_event_users SET type = -1 WHERE event_id = {$this->row['event_id']} AND user_id = {$this->row['user_id']} ";
		$this->db->simple_query($sql);

		//给被禁止人发送通知
		service_api('Notice', 'add_notice' ,array($this->event->webinfo['aid'], $this->event->webinfo['uid'], $this->row['user_id'], 'web', 'event_ban_web', array(
				'name' => $this->event->row['name'],
				'name1' => $this->event->webinfo['name'],
				'url' => mk_url('wevent/event/detail', array('id'=>$this->row['event_id'], 'web_id'=>$this->event->webinfo['aid']))
			)));
	}

	

}
