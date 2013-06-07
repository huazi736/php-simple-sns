<?php

/**
 * 个人活动接口
 */
class EventApi extends DkApi {
	protected $event;
	
	public function __initialize() {
		$this->event = DKBase::import('Event', 'event');
	}
	/**
	 * 个人活动删除
	 *
	 * @param int $eventid 活动编号
	 * @param int $uid 创建人UID
	 */
	public function delEvent($eventid , $uid){
		$einfo = $this->event->getEvent($eventid , $uid);
		if($einfo['id']){
			//获取参加人员
			$users = $this->event->getEventUser($einfo['id']);
			if(!empty($einfo['filename'])){
				$this->event->delAttach(array('group' => $einfo['group'], 'filename' => $einfo['filename']));
			}
			$this->event->delEvent($einfo['id']);
				
			$users = array_diff($users, array($uid));
			//通知参加人员活动被删除
			if (strtotime($einfo['endtime']) > time() && !empty($users)) {
				service('Notice')->add_notice(1, $uid, $users, 'event', 'event_cancel', array(
				'name' => $einfo['name'],
				'url' => mk_url('event/event/mylist'),
				));
			}
			service('RelationIndexSearch')->deleteEvent($einfo['id']);
		}
	}
}