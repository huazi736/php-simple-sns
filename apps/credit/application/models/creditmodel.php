<?php

class CreditModel extends DK_Model
{
	private $_db;
	
	public function __construct()
	{
		parent::__construct();
		$this->init_mongodb('credit');
		$this->_db = $this->mongodb->getDbInstance();
	}
	
	public function getCreditDetail($uid)
	{
		$creditDetail = $this->_db->user_credits->findOne(array('_id' => $uid));
		
		// 获取到下个等级所需要的积分
		$creditDetail['demanded'] = 200;
		
		return $creditDetail;
	}
	
	public function getCreditInfo($uid)
	{
		return $this->_db->user_credits->findOne(array('_id' => $uid));
	}
	
	public function getRankingList($uid)
	{
		$frindRankingList = $this->getRankingListByRelation($uid, 'friend');
		$bothFollowingRnakingList = $this->getRankingListByRelation($uid, 'bothFollow');
		$allRankingList = $this->getAllRankingList();
		return array('follows' => $bothFollowingRnakingList, 'friends' => $frindRankingList, 'all' => $allRankingList);
	}
	
	private function getAllRankingList()
	{
		$this->init_memcache();
		//$this->memcache->delete('all:ranklist');
		if ($rankListUsers = $this->memcache->get('all:ranklist')) {
			return $rankListUsers;
		} else {
			return service('cron')->rebuilingRankingList();
		}
	}
	
	private function getRankingListByRelation($uid, $relation)
	{
		$users = array();
		switch ($relation) {
			case 'friend':
				$users = service('relation')->getAllFriends($uid, true);
				//$users = service('relation')->getAllFriendsWithInfo($uid, true);
				break;
			case 'bothFollow':
				$users = service('relation')->getAllBothFollowers($uid);
				break;
		}
		
		if (!empty($users)) {			
			$rankListUsers = array();
			
			// 把用户自己加进排行榜里
			$users[] = $uid;
			
			// mongo里存的id为整型 （未解决）
			foreach ($users as $key => $u) {
				$users[$key] = (int) $u;
			}
			
			// 查找积分最高的前10位
			foreach ($this->_db->user_credits->find(array('_id' => array('$in' => $users))
					, array('_id', 'c', 'lv'))->sort(array('c' => -1, '_id' => 1))->limit(10) as $user) {
				$rankListUsers[] = $user;
			}
			
			if (!empty($rankListUsers)) {
				// 组合用户的uid来获取用户的姓名、头像信息
				$uids = array();
				foreach ($rankListUsers as $user) {
					$uids[] = $user['_id'];
				}
				
				$userInfos = $this->getUserName($uids);
				
				$users = array();
				foreach ($userInfos as $u) {
					$users[$u['uid']] = $u;
				}
				
				// 组合用户的姓名、头像信息
				foreach ($rankListUsers as $key => $user) {
					$rankListUsers[$key]['uname'] = $users[$user['_id']]['username'];
					$rankListUsers[$key]['home'] = mk_url("main/index/main", array('dkcode' => $users[$user['_id']]['dkcode']));
					$rankListUsers[$key]['avatar'] = get_avatar($user['_id']);
				}
				
				return array('users' => $rankListUsers, 'time' => date('Y年m月d日 H:i'));
			} else {
				return array('users' => array(), 'time' => date('Y年m月d日 H:i'));
			}
			
		} else {
			return array('users' => array(), 'time' => date('Y年m月d日 H:i'));
		}
		
	}
	
	private function getUserName($uids)
	{
		$infos = service('user')->getUserList($uids, array('username', 'dkcode', 'uid'));
		return $infos;
	}
}