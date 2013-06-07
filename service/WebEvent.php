<?php
/**
 * 网页活动接口
 * @author hpw
 */

class WebEventService extends DK_Service {
	function __construct() {
		parent::__construct();
		$this->init_db('event');
	}
	
	/**
	 * 删除所有该网页的活动
	 * @author hpw
	 * @date 2012/07/13
	 * @param int $webId  网页Id
	 * @return bool
	 */
	function delEvent($webId) 
	{		
		$webId = (int)$webId;
		if(!$webId)
			return false;
		$webInfo = service('Interest')->get_web_info($webId);
		$uid = $webInfo['uid'];
		$condition = 'webid='.$webId;
		$selectEvent = 'select id, name from web_event where webid='.$webId;
		$eventArr = $this->db->query($selectEvent)->result_array();
		if(!$eventArr)
			return false;
		$tags = service('Interest')->get_web_category_imid($webId);
		$eventInfo = array();
		foreach($eventArr as $event)
		{
			$re = service('WebTimeline')->checkTopicExists($event['id'], 'event', $webId);
			if($re)
				service('WebTimeline')->delWebtopicByMap($event['id'], 'event', array($tags), $webId);
			service('RelationIndexSearch')->deleteAEventOfWeb($event['id']);						
			$selectUser = 'select user_id from web_event_users where event_id='.$event['id'];
			$userId = array();
			$userArr = $this->db->query($selectUser)->result_array();
			if(!$userArr)
				continue;
			foreach($userArr as $user)
			{
				$userId[] = $user['user_id'];
			}
			$eventInfo[$event['id']]['name']= $event['name'];
			$eventInfo[$event['id']]['user']= $userId;

		}	
		foreach($eventInfo as $k=>$v)
		{
			service('Notice')->add_notice($webId, $uid, $v['user'], 'web', 'event_c_web', array(
					'name' => $v['name'],
					'name1' => $webInfo['name'],
					'url' => mk_url('wevent/event/mylist', array('web_id'=>$webId))
				));
		}					
		$delEvent = "delete from web_event where {$condition} ";
		$delUser = "delete from web_event_users where event_id in(select id from web_event where {$condition})";
		$delMsg = "delete from web_event_messages where event_id in(select id from web_event where {$condition})";		
		$rs = $this->db->simple_query($delUser);
		$rs = $this->db->simple_query($delMsg);
		$rs = $this->db->simple_query($delEvent);		
		return true;
	}
	
	/**
	 * 删除一个的活动
	 * @author hpw
	 * @date 2012/08/07
	 * @param int $webId  网页Id
	 * @param int $eventid 活动id
	 * @return bool
	 */
	function delOne($webId, $eventId) 
	{		
		$webId = (int)$webId;
		$eventId = (int)$eventId;
		if(!$webId || !$eventId)
			return false;
		$webInfo = service('Interest')->get_web_info($webId);
		$uid = $webInfo['uid'];
		$condition = 'webid='.$webId;
		$selectEvent = 'select id, name from web_event where webid='.$webId.' AND id='.$eventId;
		$eventInfo = $this->db->query($selectEvent)->row_array();
		if(!$eventInfo)
			return false;
			
		$tags = service('Interest')->get_web_category_imid($webId);

		service('RelationIndexSearch')->deleteAEventOfWeb($eventInfo['id']);						
		$selectUser = 'select user_id from web_event_users where event_id='.$eventId;
		$userId = array();
		$userArr = $this->db->query($selectUser)->result_array();
		if($userArr)
		{
			foreach($userArr as $user)
			{
				$userId[] = $user['user_id'];
			}
			
			service('Notice')->add_notice($webId, $uid, $userId, 'web', 'event_c_web', array(
					'name' => $eventInfo['name'],
					'name1' => $webInfo['name'],
					'url' => mk_url('wevent/event/mylist', array('web_id'=>$webId))
				));
	
		}
		$delEvent = "delete from web_event where id=".$eventId;
		$delUser = "delete from web_event_users where event_id=".$eventId;
		$delMsg = "delete from web_event_messages where event_id=".$eventId;		
		$rs = $this->db->simple_query($delUser);
		$rs = $this->db->simple_query($delMsg);
		$rs = $this->db->simple_query($delEvent);		
		return true;
	}
}