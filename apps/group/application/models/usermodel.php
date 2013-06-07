<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 用户部分业务逻辑，非群成员基本逻辑
 */
class UserModel extends MY_Model
{
	public function getUserInfo($uid)
	{
		return $this->getDao('User', 'api')->getUserInfo($uid);
	}
	
	/**
	 * 获取用户资料的学校信息
	 * @param $uidgetFriendByGroup
	 * @param $schoolType
	 */
	public function getClassmate($uid)
	{
		$classmate = $this->getDao('User', 'api')->getClassmate($uid);
		$this->ci->load->model('groupmodel','group');
		$groups = $this->ci->group->getAllGroups($uid, GroupConst::GROUP_TYPE_CLASSMATE, true);
		$schools = $names = $ids = array();
		//先过滤掉自己已经创建或已经加入的学校群
		if(!empty($groups)){
			$temp = $groups;
			$groups = array();
			foreach($temp as $t){
				$groups = array_merge($groups,$t);
			}
			foreach($groups as $tmp) {
				$ids[] = $tmp['gid'];
				$names[$tmp['name']] = 1;
			}
			$classmate = array_diff_key($classmate, $names);
		}
		//再过滤掉自己没进入但自己好友已经创建的学校群
		if(!empty($classmate)){
			$friend_ids = $this->getFriends($uid);
			$join_groups = $this->ci->group->getGroupByNames(array_keys($classmate), GroupConst::GROUP_TYPE_CLASSMATE, $friend_ids, true);
			if(!empty($join_groups)){
				$names = array();
				foreach($join_groups as $tmp) {
					$ids[] = $tmp['gid'];
					$names[$tmp['name']] = 1;
				}
				$classmate = array_diff_key($classmate, $names);
			}
		}
		if(!empty($ids)){
			$this->ci->load->model('membermodel','member');
			$members = $this->ci->member->getAllMemberIdsByGroups($ids);
			$group_url = mk_url('group/index/detail', array('gid'=>''));
			//处理自己已经可以进入的学校群
			if(!empty($groups)){
				foreach($groups as &$g) {
					//新增字段对历史数据的补丁
					if((!isset($g['type']) || $g['type'] == '') && array_key_exists($g['name'], $classmate)) {
						$this->ci->group->updateExtend($g['gid'], null, $classmate[$g['name']]['edulevel']);
					}
					$g['member_counts'] = count($members[$g['gid']]);
					$count = $g['member_counts']>=4?4:$g['member_counts'];
					if($count > 0){
						//@todo 可以优化：批量从redis中获取数据
						for($i=0; $i<$count; $i++){
							$g['members'][] = get_avatar($members[$g['gid']][$i],'s');
						}
					}
					$g['url'] = $group_url.$g['gid'];
					if(in_array($g['type'],array('小学', '初中', '高中'))) {
						$schools[$g['type']][]=array(
							'group'	=> $g,
							'in_group' => true,
							'name' => $g['name']
						);
					}elseif($g['type'] != '学前') {
						$schools['大学'][]=array(
							'group'	=> $g,
							'in_group' => true,
							'name' => $g['name']
						);
					}
				}
			}
			//处理自己没进入但自己好友已经创建的学校群
			if(!empty($join_groups)) {
				foreach($join_groups as &$g) {
					$array = array();
					//新增字段对历史数据的补丁
					if((!isset($g['type']) || $g['type'] == '') && array_key_exists($g['name'], $classmate)) {
						$this->ci->group->updateExtend($g['gid'], null, $classmate[$g['name']]['edulevel']);
					}
					$g['member_counts'] = count($members[$g['gid']]);
					$count = $g['member_counts']>=4?4:$g['member_counts'];
					if($count > 0){
						//@todo 可以优化：批量从redis中获取数据
						for($i=0; $i<$count; $i++){
							$g['members'][] = get_avatar($members[$g['gid']][$i],'s');
						}
					}
					$g['url'] = $group_url.$g['gid'];
					if(in_array($g['type'],array('小学', '初中', '高中'))) {
						$array[]=array(
							'group'	=> $g,
							'in_group' => false,
							'name' => $g['name']
						);
						$schools[$g['type']] = array_merge($array, $schools[$g['type']]);
					}elseif($g['type'] != '学前') {
						$array[]=array(
							'group'	=> $g,
							'in_group' => false,
							'name' => $g['name']
						);
						$schools['大学'] = array_merge($array, $schools['大学']);
					}
				}
			}
		}
		//处理自己需要创建的学校群
		if(!empty($classmate)){
			//业务上需要显示小学，初中，高中，大学分类
			foreach($classmate as $key=>$val) {
				$val['group'] = '';
				$val['in_group'] = false;
				if($val['edulevel'] == "学前"){
					unset($classmate[$key]);
					continue;
				} elseif($val['edulevel'] == '小学') {
					if(!isset($schools['小学'])) $schools['小学'] = array();
					$schools['小学'] = array_merge(array($val),$schools['小学']);
				} elseif($val['edulevel'] == '初中') {
					if(!isset($schools['初中'])) $schools['初中'] = array();
					$schools['初中'] = array_merge(array($val),$schools['初中']);
				} elseif($val['edulevel'] == '高中') {
					if(!isset($schools['高中'])) $schools['高中'] = array();
					$schools['高中'] = array_merge(array($val),$schools['高中']);
				} else {
					if(!isset($schools['大学'])) $schools['大学'] = array();
					$schools['大学'] = array_merge(array($val),$schools['大学']);
				}
			}
		}
		
		$array = array();
		isset($schools['大学'])?$array['大学'] = $schools['大学']:'';
		isset($schools['高中'])?$array['高中'] = $schools['高中']:'';
		isset($schools['初中'])?$array['初中'] = $schools['初中']:'';
		isset($schools['小学'])?$array['小学'] = $schools['小学']:'';
		return $array;
	}
	
	/**
	 * 获取用户资料的同事信息
	 * @param $uid
	 */
	public function getWorkmate($uid)
	{
		$workmate = $this->getDao('User', 'api')->getWorkmate($uid);
//		if($this->ci->input->get('debug') == 1){
//			echo "<pre>";
//			print_r($workmate);
//			echo "</pre>";
//			exit;
//		}
		return $workmate;
	}
    
    /**
	 * 获取用户资料的同行信息
	 * @param $uid
	 */
	function getPeer($uid)
	{
		$peer = $this->getDao('User', 'api')->getPeer($uid);
		return $peer;
	}
    
     /**
	 * 获取用户资料的亲人信息
	 * @param $uid
	 */
	function getRelative($uid)
	{
		$relative = $this->getDao('User', 'api')->getRelative($uid);
		return $relative;
	}
	
	/**
	 *	获取当前用户的所有好友
	 *	@param $uid
	 */
	public function getFriends($uid)
	{
		return $this->getDao('User', 'api')->getFriends($uid);
	}
	
	/**
	 * 获取当前永固的所有好友ID集合
	 * @param unknown_type $uid
	 */
	public function getAllFriends($uid)
	{
		return $this->getDao('User', 'api')->getAllFriends($uid);
	}
	
	/**
	 * 获取当前用户的好友信息，可以分页
	 * @param int $uid
	 * @param int $page
	 * @param int $limit
	 */
	public function getFriendsByPage($uid, $page = 1, $limit = 25)
	{
		$page = intval($page) < 1 ? 1 : intval($page);
		$users['count'] = $this->getDao('User', 'api')->getNumOfFriends($uid);
		$users['list'] = $this->getDao('User', 'api')->getFriendsUseIntoGroup($uid, $page, $limit);
		if(!empty($users['list'])){
			foreach($users['list'] as $k => &$v){
				$v['src'] = get_avatar($v['id'],'mm');
			}
		}
		$users['last'] = ($users['count'] > $page * $limit ) ? false:true;
		return $users;
	}
	
	/**
	 * 根据名称获取当前用户的好友信息，可以分页
	 * @param int $uid
	 * @param int $pagegetFriendByGroup
	 * @param int $limit
	 */
	public function searchFriendsByPage($uid, $name = '', $page = 1, $limit = 25)
	{
		$page = intval($page) < 1 ? 1 : intval($page);
		$users['count'] = $this->getDao('User', 'api')->getNumOfFriends($uid);
		if(empty($name)){
			$users['list'] = $this->getDao('User', 'api')->getFriendsUseIntoGroup($uid, $page, $limit);
		} else {
			$temp = $this->getDao('User', 'api')->getFriendByName($uid, $name, $page, $limit);
			$users['list'] = $temp['object'];
		}
		if(!empty($users['list'])){
			foreach($users['list'] as $k => &$v){
				$v['src'] = get_avatar($v['id'],'mm');
			}
		}
		$users['last'] = ($users['count'] > $page * $limit ) ? false:true;
		return $users;
	}
	
	/**
	 * 获取不在某群的好友列表
	 * @return json
	 */
	function getFriendByGroup($gid, $login_uid, $page = 1, $limit = 25) {
		$result['list'] = array();
		$result['last'] = true;
		
		$this->ci->load->model('membermodel','member');
		$g_uids = $this->ci->member->getAllMemberIdsByGroup($gid);
		$r_uids = $this->getDao('User', 'api')->getAllFriends($login_uid);
		$uids = array_diff($r_uids, $g_uids);
		//获得好友总数
		$result['NumOfFriends'] = count($uids);
		$uids = array_slice($uids, ($page - 1)*$limit, $limit);

		//获得好友列表
		$friends = $this->getDao('User', 'api')->getUsers($uids);
		if($friends){
			foreach($friends as $k => $v){
				$v['src'] = get_avatar($v['id'],'s');
				$v['href'] = '';
				$result['list'][] = $v;
			}
		}
		$result['last'] = ($result['NumOfFriends'] > $page * $limit ) ? false:true;
		return $result;
	}
	
	/**
	 * 通过姓名获取好友列表
	 * @return json
	 */
	function searchFriendByGroup($gid, $login_uid,$keyword = '', $page = 1, $limit = 25) {
		$result['list'] = array();
		$result['last'] = true;
		$keyword = trim($keyword);
		if($keyword != ''){
			//获得好友列表
			$getFriendByName = $this->getDao('User', 'api')->getFriendByName($login_uid,$keyword,1,10000);
			if($getFriendByName['total'] > 0){
				$this->ci->load->model('membermodel','member');
				$g_uids = $this->ci->member->getAllMemberIdsByGroup($gid);
				$uids = $r_uids = array();
				foreach($getFriendByName['object'] as $k => $v){
					$r_uids[] = $v['id'];
				}
				$uids = array_diff($r_uids, $g_uids);
				$count = count($uids);
				$uids = array_slice($uids, ($page - 1)*$limit, $limit);
				$list = $this->getDao('User', 'api')->getUsers($uids);
				
				foreach($list as $k => $v){
					$v['src'] = get_avatar($v['id'],'ss');
					$v['href'] = '';
					$result['list'][] = $v;
				}
				//判断是否为最后一页
				$result['last'] = ($count > $page * $limit) ? false:true;
			}
		}else{
			$result = $this->getFriendByGroup($gid, $login_uid, $page);
		}
		return $result;
	}

	/**
	 * 获取粉丝列表
	 * @return json
	 */
	function getFollowerByGroup($action_uid,$page) {
		$result['list'] = array();
		$result['last'] = true;
		//获得粉丝数
		$result['NumOfFollowers'] = $this->getDao('User', 'api')->getNumOfFollowers($action_uid);
		//获得粉丝列表
		$followers = $this->getDao('User', 'api')->getFollowersUseIntoGroup($action_uid,$page);
		if($followers){
			foreach($followers as $k => $v){
				$v['src'] = get_avatar($v['id'],'ss');
				$v['href'] = '';
				$result['list'][] = $v;
			}
		}
		$result['last'] = ($result['NumOfFollowers'] > $page * 25 ) ? false:true;
		return $result;
	}

	/**
	 * 通过姓名获取粉丝列表
	 * @return json
	 */
	function searchFollowerByGroup($action_uid,$keyword,$page) {
		$result['list'] = array();
		$result['last'] = true;
		if($keyword != ''){
			//获得好友列表
			$getFollowerByName = $this->getDao('User', 'api')->getFollowerByName($action_uid,$keyword,$page);
			if($getFollowerByName['total'] > 0){
				foreach($getFollowerByName['object'] as $k => $v){
					$v['src'] = get_avatar($v['id'],'ss');
					$v['href'] = '';
					$result['list'][] = $v;
				}
				//判断是否为最后一页
				$result['last'] = ($getFollowerByName['total'] > $page * 25) ? false:true;
			}
		}else{
			$result = $this->getFollowerByGroup($action_uid,$page);
		}
		return $result;
	}

	/**
	 * 获取关注列表
	 * @return json
	 */
	function getFollowingByGroup($action_uid,$page) {
		$result['list'] = array();
		$result['last'] = true;
		//获得好友数
		$result['NumOfFollowings'] = $this->getDao('User', 'api')->getNumOfFollowings($action_uid);
		//获得好友列表
		$followings = $this->getDao('User', 'api')->getFollowingsUseIntoGroup($action_uid,$page);
		if($followings){
			foreach($followings as $k => $v){
				$v['src'] = get_avatar($v['id'],'ss');
				$v['href'] = '';
				$result['list'][] = $v;
			}
		}
		$result['last'] = ($result['NumOfFollowings'] > $page * 25 ) ? false:true;
		return $result;
	}

	/**
	 * 通过姓名获取关注列表
	 * @return json
	 */
	function searchFollowingByGroup($action_uid,$keyword,$page) {
		$result['list'] = array();
		$result['last'] = true;
		if($keyword != ''){
			//获得好友列表
			$getFollowingByName = $this->getDao('User', 'api')->getFollowingByName($action_uid,$keyword,$page);
			if($getFollowingByName['total'] > 0){
				foreach($getFollowingByName['object'] as $k => $v){
					$v['src'] = get_avatar($v['id'],'ss');
					$v['href'] = '';
					$result['list'][] = $v;
				}
				//判断是否为最后一页
				$result['last'] = ($getFollowingByName['total'] > $page * 25) ? false:true;
			}
		}else{
			$result = $this->getFollowingByGroup($action_uid,$page);
		}
		return $result;
	}
	
	/**
	 * 根据用户名查询群组内的用户名
	 * @param unknown_type $gid
	 * @param unknown_type $keyword
	 * @param unknown_type $offset
	 * @param unknown_type $limit
	 */
	public function getMembersByGroupIdAndKey( $uid, $gid, $keyword, $offset, $limit )
	{
		// 根据关键字查询出用户的所有好友
		$friends = $this->getDao( 'User', 'api' )->getFriendByName( $uid, $keyword, 1, 10000 );
		
		// 查询群组中所有用户的ID
		$members = $this->getDao( 'GroupMemberShip' )->findByGid( $gid );
		
		$result = array ();
		foreach ( $members as $members ) {
			foreach ( $friends ['object'] as $friend ) {
				if ( $members ['uid'] == $friend ['id'] ) {
					array_push( $result, $friend );
				}
			}
		}
		
		foreach ( $result as &$value ) {
			$value ['uid'] = $value ['id'];
			$value ['avatar'] = get_avatar( $value ['id'], 'mm' );
			$value ['href'] = mk_url( 'main/index/main', array ( 'dkcode' => $value ['dkcode'] ) );
		}
		
		// 根据 $offset 和 limit 取出用户数
		$return = array ();
		
		$top = $offset + $limit;
		
		if ( $top > count( $result ) ) {
			$top = count( $result );
		}
		
		for( $i = $offset; $i < $top; $i ++ ) {
			if ( $i <= count( $result ) ) {
				array_push( $return, $result [$i] );
			}
		
		}
		
		return $return;
	}
	
}