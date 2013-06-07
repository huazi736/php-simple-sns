<?php
class Api_User
{
	/**
	 * 获取用户信息
	 * @param $uid
	 * @return array
	 */
	public function getUserInfo($uid)
	{
		return service('User')->getUserInfo($uid, 'uid');
	}

	/**
	 * 获取一群用户信息
	 * @param array $ids
	 */
	public function getUsers($ids)
	{
		if(!is_array($ids)) $ids = array($ids);
		if(empty($ids)) return array();
		$users = service('Relation')->getMultiUserInfo($ids);
		$array = array();
		foreach($users as &$u) {
			$array[$u['id']] = $u;
		}
		return $array;
	}

	/**
	 * 我获取自己的好友数
	 * @param int $uid 我的uid，我要获取我自己的好友数
	 */
	public function getNumOfFriends($uid)
	{
		return service('Relation')->getNumOfFriends($uid);
	}
	
	/**
	 * 我获取朋友的好友数
	 * @param int $uid 我的uid
	 * @param int $fuid 别人的uid，我要获取别人的好友数
	 */
	public function getNumOfFriendsByFriend($uid, $fuid)
	{
		return service('Relation')->getNumOfFriends($fid, false, $uid);
	}

	/**
	 * 我获取自己的好友信息
	 * @param int $uid 谁的好友
	 * @param $page
	 * @param $limit
	 */
	public function getFriendsUseIntoGroup($uid, $page=1, $limit = 25) {
		$offset = ($page - 1) * 25;
		return service('Relation')->getFriendsWithInfoByOffset($uid, true, $offset, $limit);
	}
	
	/**
	 * 我获取朋友的好友信息
	 * @param int $uid 我的uid
	 * @param int $fuid 谁的好友
	 * @param $page
	 * @param $limit
	 */
	public function getFriendsUseIntoGroupByFriend($uid, $fuid, $page=1, $limit = 25) {
		$offset = ($page - 1) * 25;
		return service('Relation')->getFriendsWithInfoByOffset($fuid, false, $offset, $limit, $uid);
	}
	
	/**
	 * 我获取自己的好友ID集合
	 * @param int $uid
	 */
	public function getAllFriends($uid)
	{
		return service('Relation')->getAllFriends($uid);
	}
	
	/**
	 * 我获取朋友的好友ID集合
	 * @param int $uid
	 * @param int $fuid
	 */
	public function getAllFriendsByFriend($uid, $fuid)
	{
		return service('Relation')->getAllFriends($fuid, false, $uid);
	}

	function getFriendByName($uid, $keyword='', $page=1,$limit=25) {
		$result = service("PeopleSearch")->getFriendsReturnJSON($uid, $keyword, $page, $limit);
		return json_decode($result, true);
	}

	function getNumOfFollowers($uid)
	{
		return service('Relation')->getNumOfFollowers($uid);
	}

	function getFollowersUseIntoGroup($uid, $page=1, $limit = 25) {
		return service('Relation')->getFollowersWithInfo($uid,$page,$limit);
	}

	function getFollowerByName($uid, $keyword='', $page=1,$limit=25) {
		$result = service("PeopleSearch")->getFollowersReturnJSON($uid, $keyword, $page, $limit);
		return json_decode($result, true);
	}

	function getNumOfFollowings($uid)
	{
		return service('Relation')->getNumOfFollowings($uid);
	}

	function getFollowingsUseIntoGroup($uid, $page=1, $limit = 25) {
		return service('Relation')->getFollowingsWithInfo($uid,true,$page,$limit);
	}

	function getFollowingByName($uid, $keyword='', $page=1,$limit=25) {
		$result = service("PeopleSearch")->getFollowingReturnJSON($uid, $keyword, $page, $limit);
		return json_decode($result, true);
	}

	function getEducation($uid){
		return service('UserWiki')->getEduInfo($uid);
	}

	function getClassmate($uid){
		$classmate =$class = array();
		$temp = service('UserWiki')->getclassmate($uid,'all');
		$edulevel = array(1=>'学前',2=>'小学',3=>'初中',4=>'高中',5=>'大专',6=>'本科',7=>'研究生',8=>'硕士',9=>'博士');
		$school = array();
		if($temp){
			foreach($temp as $k => $v){
				if($v['classmate'] == '[]') continue;
				$v['classmate'] = explode(',',json_decode($v['classmate'],true));
				if(isset($v['classmate'][""])) unset($v['classmate'][""]);
				if(empty($v['classmate'][0])) unset($v['classmate'][0]);
				$v['classmate'] = array_diff($v['classmate'], array());
				$v['edulevel'] = $edulevel[$v['edulevel']];
				$v['name'] = $v['schoolname']."(".date('Y',$v['starttime'])."届".($v['department']?"[".$v['department']."]":"").")";
				if(isset($school[$v['name']])) {
					$school[$v['name']]['classmate'] = array_unique(array_merge($school[$v['name']]['classmate'], $v['classmate']));
				} else {
					$school[$v['name']] = $v;
				}
			}
		}
		return $school;
	}

	function getWorkmate($uid){
		$workmate = array();
		$temp = service('UserWiki')->getworkmate($uid);
		if($temp){
			$workmate = json_decode($temp['workmate'],true);
			if(!empty($workmate)){
				$workmate = explode(',',$workmate);
				if(isset($workmate['workmate'][""])) unset($workmate['workmate'][""]);
				if(empty($workmate['workmate'][0])) unset($workmate['workmate'][0]);
				$temp['workmate'] = array_diff($workmate, array());
			} else {
				$temp = array();
			}
		}else{
			$temp = array();
		}
		return $temp;
	}
    
    function getPeer($uid){
        $peer = array();
        $temp = service('UserWiki')->gettrade($uid,1,10000);
		if($temp){
			$temp = json_decode($temp,true);
			$ss = array_pop($temp);
			foreach($temp as $k=>$v){
                $peer['peermate'][] = $v['uid'];
            }
            $peer['department'] = $v['department'];
		}else{
			$temp = array();
		}
		return $peer;
    }
    
    function getRelative($uid){
        $temp = service('UserWiki')->getrelative($uid);
        $relative = is_array($temp) ? $temp : array();
        return $relative;
    }
	
	function getFriends($uid) {
		return service('Relation')->getFriends($uid,true,1,10000);
	}
	
	function getFriendsByPage($uid, $page, $limit = 25) {
		return service('Relation')->getFriends($uid,true,$page,$limit);
	}
}