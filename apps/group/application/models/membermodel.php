<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组成员基本业务逻辑
 */
class MemberModel extends MY_Model
{
	private $hession_group;
	/**
	 * 直接加入群成员
	 * @param int $gid
	 * @param int $creator
	 * @param array[int] $uids
	 * @return boolean
	 */
	public function addMembers($gid, $creator, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
			
		$addUser = $addInvite = array();

		foreach($uids as $id) {
			$addUser[] = array(
				'gid' => $gid,
				'uid' => $id,
				'position' => $id == $creator ? GroupConst::GROUP_ROLE_MASTER : GroupConst::GROUP_ROLE_MEMBER,
			);
			$addInvite[] = array(
				'from_uid' => $creator,
				'to_uid' => $id,
				'gid' => $gid,
				'invite_time' => time(),
				'accept_time' => time(),
				'status' => GroupConst::GROUP_PROCESSING_SUCCESS,
				'accept_result' => GroupConst::GROUP_INVITE_ACCEPT,
			);
		}
		$this->getDao('GroupInvite')->createMulti($addInvite);
		$this->getDao('GroupMemberShip')->createMulti($addUser);

		$ships = $this->getDao('GroupMemberShip')->findByGroupByUsers($gid, $uids);
		$addMember = array();
		foreach($uids as $id) {
			$addMember[] = array(
				'mid' => $ships[$id]['id'],
			);
		}
        
		$this->getDao('GroupMember')->createMulti($addMember);

		return true;
	}
	
	private function getHession()
	{
		if($this->hession_group) return $this->hession_group;
		/**
		 * 加载hessian
		 */
		$url = $this->ci->config->item( "im_url" );
		try {
			$this->hession_group = $this->getDao( 'GroupChat', 'hessian' )->getClient( $url );
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}
	
	/**
	 * 获得已同意的邀请信息
	 * @param int $gid
	 * @param int $uid
	 */
	public function getInvite($gid, $uid)
	{
		$invite = $this->getDao('GroupInvite')->findByGroupByUidsProcessed($gid, array($uid));
		if(isset($invite[0])) return $invite[0];
		else return array();
	}
	
	/**
	 * 邀请加入群成员
	 * @param int $gid
	 * @param int $creator
	 * @param array[int] $uids
	 * @return boolean
	 */
	public function invite($gid, $from_uid, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		/*
		 * 数据补丁，删除之前的错误数据及拒绝的数据
		 */
		$updateInvites = $this->getDao('GroupInvite')->findByGroupByFrom($gid, $from_uid, $uids, GroupConst::GROUP_PROCESSING_SUCCESS);
		$ids = array();
		foreach($updateInvites as $u) {
			$ids[] = $u['id'];
		}
		$this->getDao('GroupInvite')->delete($ids);
		
		/*
		 * 删除已经发送过的邀请
		 */
		$ids = $to_uids = array();
		$updateInvites = $this->getDao('GroupInvite')->findByGroupByFrom($gid, $from_uid, $uids);
		foreach($updateInvites as $u) {
			$ids[] = $u['id'];
			$to_uids[] = $u['to_uid'];
		}
		if(!empty($ids)){
			$this->getDao('GroupInvite')->delete($ids);
		}

		/*
		 * 没有邀请过就新增邀请
		 */
		foreach($uids as $id) {
			$addInvite[] = array(
				'from_uid' => intval($from_uid),
				'to_uid' => $id,
				'gid' => $gid,
				'invite_time' => time(),
				'status' => GroupConst::GROUP_PROCESSING_WAITTING,
			);
		}
		$this->getDao('GroupInvite')->createMulti($addInvite);
		/*
		 * 发送邀请通知
		 */
		$this->ci->load->model('groupmodel', 'group');
		$group = $this->ci->group->getGroup($gid);
		foreach($uids as $u) {
			service( 'Notice' )->add_notice( '1', $from_uid, $u, 'group', 'group_join', array( 'name' => $group['name'], 'url'=>mk_url('group/group/confirm') ) );
		}
		return '';
	}
	
	/**
	 * 确认邀请加入群成员，只可能是用户自己做确认
	 * @param int $id 邀请ID
	 * @param int $uid 被邀请人做出的应答
	 * @param string $result 应答结果
	 */
	public function inviteConfirm($id, $uid, $result = GroupConst::GROUP_INVITE_ACCEPT){
		$invite = $this->getDao('GroupInvite')->findById($id);
		$gid = $invite['gid'];
		$this->ci->load->model('groupmodel', 'group');
		$group = $this->ci->group->getGroup($gid);
		$this->getHession();
		//@todo 使用消息队列来做，这样就不需要处理异常情况
		//$this->hession_group->addMember( $group['creator'], $uid, $gid );
		
		$update = array(
			'accept_time' => time(),
			'status' => GroupConst::GROUP_PROCESSING_SUCCESS,
			'accept_result' => $result,
		);
		$this->getDao('GroupInvite')->update($id, $update);
		if($result == GroupConst::GROUP_INVITE_ACCEPT) {
			$this->getDao('GroupInvite')->uniqueInvite($gid, $uid, $invite['from_uid']);
			if(!$this->getDao('GroupMemberShip')->checkMemberExist($gid, $uid)){
				$addUser = array(
					'gid' => $gid,
					'uid' => $uid,
					'position' => GroupConst::GROUP_ROLE_MEMBER,
				);
				$ship = $this->getDao('GroupMemberShip')->create($addUser);
				$addMember = array(
					'mid' => $ship,
				);
				$this->getDao('GroupMember')->create($addMember);
				$this->getDao('GroupExtend')->setMemberInc($gid, 1);
			}
		}
		return true;
	}
	
	/**
	 * 申请加入群成员，只可能是用户自己做申请
	 * @param int $gid
	 * @param int $uid
	 * @return boolean
	 */
	public function apply($gid, $uid)
	{
		if(intval($gid) == 0) return false;
		$updateInvites = $this->getDao('GroupInvite')->findByGroupByFrom($gid, 0, array($uid));
		if(isset($updateInvites[0])){
			$this->getDao('GroupInvite')->update($updateInvites[0]['id'], array('invite_time' => time()));
		}else{
			$addInvite = array(
				'from_uid' => 0,
				'to_uid' => $uid,
				'gid' => $gid,
				'invite_time' => time(),
				'status' => GroupConst::GROUP_PROCESSING_WAITTING,
			);
			$this->getDao('GroupInvite')->create($addInvite);
		}
		return true;
	}
	
	/**
	 * 确认申请加入群成员
	 * @param int $gid 群号
	 * @param int $from_uid 管理员
	 * @param array $uids 未处理的申请人
	 * @param string $result 处理结果
	 */
	public function applyConfirm($gid, $from_uid, $uids, $result = GroupConst::GROUP_INVITE_ACCEPT)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		$addUser = $updateInvite = $ids = array();
		$applys = $this->getDao('GroupInvite')->findByGroupByUids($gid, $uids);

		$this->ci->load->model('groupmodel', 'group');
		$group = $this->ci->group->getGroup($gid);
		
		$this->getHession();
		//@todo 使用消息队列来做，这样就不需要处理异常情况
		$this->hession_group->addMember( $group['creator'], $uids, $gid );
		
		foreach($applys as $apply) {
			$addUser[] = array(
				'gid' => $gid,
				'uid' => $apply['to_id'],
				'position' => GroupConst::GROUP_ROLE_MEMBER,
			);
			$ids[] = $apply['id'];
		}
		$updateInvite = array(
			'from_uid' => intval($from_uid),
			'accept_time' => time(),
			'status' => GroupConst::GROUP_PROCESSING_SUCCESS,
			'accept_result' => $result,
		);
		$this->getDao('GroupInvite')->updateMulti($ids, $updateinvite);
		if($result == GroupConst::GROUP_INVITE_ACCEPT){
			$this->getDao('GroupMemberShip')->createMulti($addUser);
	
			$ships = $this->getDao('GroupMemberShip')->findByGroupByUsers($gid, $uids);
			$addMember = array();
			foreach($uids as $id) {
				$addMember[] = array(
					'mid' => $ships[$id]['id'],
				);
			}
	        
			$this->getDao('GroupMember')->createMulti($addMember);
			$this->getDao('GroupExtend')->setMemberInc($gid, count($addMember));
		}
		return true;
	}

    /**
	 * 加入子群成员
	 * @param int $sid
	 * @param int $creator
	 * @param array[int] $uids
	 * @return boolean
	 */
	public function addSubGroupMembers($sid, $creator, $uids)
	{
		if(!is_array($uids)) {
            $uids = array(intval($uids));
        }	
		$addUser = $addInvite = array();

		foreach($uids as $id)
		{
			$addUser[] = array(
				'sid' => $sid,
				'uid' => $id,
				'position' => $id == $creator ? GroupConst::GROUP_ROLE_MASTER : GroupConst::GROUP_ROLE_MEMBER,
                'create_time' => time()
			);
		}
		$this->getDao('SubGroupMemberShip')->createMulti($addUser);
		return true;
	}
    
	/**
	 * 获得群内某成员的详细信息
	 * @param int $gid
	 * @param int $uid
	 * @param boolean $extend 是否需要全部的信息，否的时候只有关联表的信息
	 * @return array
	 */
	public function getMemberByGroup($gid, $uid, $extend = false)
	{
		$user = $this->getDao('GroupMemberShip')->findByGroupByUser($gid, $uid);
		if($extend){
			$info = array();
			$info = $this->getDao('GroupMember')->findById($user['id']);
			$user = array_merge($user, $info);
		}
		return $user;
	}

	/**
	 * 获得某群组的某些群成员的详细信息
	 * @param max $gid
	 * @param boolean $extend 是否需要全部的信息，否的时候只有关联表的信息
	 * @return array
	 */
	public function getMembersByGroup($gid, $uids, $extend = false)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$users = $this->getDao('GroupMemberShip')->orderBy('id')->findByGroupByUsers($gid, $uids);
		if($extend){
			$ids = $temp = $infos = array();
			foreach($users as $u) {
				$ids[] = $u['id'];
			}
			$temp = $this->getDao('GroupMember')->findByIds($ids);
			foreach($temp as $info) {
				$infos[$info['id']] = $info;
			}
			foreach($users as &$u) {
				$u = array_merge($u, $infos[$u['id']]);
			}
		}
		return $users;
	}

	/**
	 * 获得某群组的所有群成员的详细信息
	 * @param int $gid
	 * @param boolean $extend 是否需要全部的信息，否的时候只有关联表的信息
	 * @return array
	 */
	public function getAllMembersByGroup($gid, $extend = false)
	{
		$ids = array();
		$users = $this->getDao('GroupMemberShip')->getMembersByGroup($gid);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		$api_users = $this->getDao('User', 'api')->getUsers($ids);
		
		if($extend){
			$ids = $temp = $infos = array();
			foreach($users as $u) {
				$ids[] = $u['id'];
			}
			$temp = $this->getDao('GroupMember')->findByIds($ids);
			foreach($temp as $info) {
				$infos[$info['id']] = $info;
			}
		}
		foreach($users as &$u) {
			if($extend) $u = array_merge($u, $infos[$u['id']]);
			unset($api_users[$u['uid']]['id']);
			$u = array_merge($u, $api_users[$u['uid']]);
			//@todo 可以优化：批量从redis中获取数据
			$u['avatar'] = get_avatar($u['uid'],'mm');
			$u['href'] = mk_url( 'main/index/profile', array('dkcode' => $u['dkcode']));
		}
//		if($this->ci->input->get('debug') == 1){
//			echo "<pre>";
//			print_r($ids);
//			$config = get_config();
//			print_r($config['server_url']);
//			echo "<br/>";
//			print_r($api_users);
//			echo "</pre>";
//		}
		return $users;
	}

	/**
	 * 获得某群组的所有群成员的ID集合
	 * @param int $gid
	 * @return array
	 */
	public function getAllMemberIdsByGroup($gid)
	{
		$ids = array();
		$users = $this->getDao('GroupMemberShip')->getMembersByGroup($gid);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		return $ids;
	}
	
	/**
	 * 获得某些群组的所有群成员的ID集合
	 * @param max $gids
	 * @return array
	 */
	public function getAllMemberIdsByGroups($gids)
	{
		if(!is_array($gids)) $gids = array(intval($gids));
		$ids = array();
		$users = $this->getDao('GroupMemberShip')->getMembersByGroups($gids);
		foreach($users as $k => $v){
			$ids[$v['gid']][] = $v['uid'];
		}
		return $ids;
	}

	/**
	 * 从群组中删除某个或某些成员
	 * @param int $gid
	 * @param max $uids
	 * @return boolean
	 */
	public function deleteByGroup($gid, $uids)
	{
		$ship = $this->getDao('GroupMemberShip')->findByGroupByUsers($gid, $uids);
		$ids = array();
		foreach($ship as $a) {
			$ids[] = $a['id'];
		}
		$this->getDao('GroupMember')->delete($ids);
		$this->getDao('GroupMemberShip')->deleteByGroup($gid, $uids);
		return true;
	}

	/**
	 * 从群组中删除所有成员
	 * @param int $gid
	 * @return boolean
	 */
	public function deleteAllByGroup($gid)
	{
		$ship = $this->getDao('GroupMemberShip')->getMembersByGroup($gid);
		$ids = array();
		foreach($ship as $a) {
			$ids[] = $a['id'];
		}
		$this->getDao('GroupMember')->delete($ids);
		$this->getDao('GroupMemberShip')->deleteAllByGroup($gid);
		return true;
	}
    
    /**
     *获取群成员数量
     * @return type 
     */
    public function getNumOfGroupMember($gid){
        return $this->getDao('GroupMemberShip')->getNumOfGroupMember($gid);
    }
    
    /**
	 * 获得某群组的子群和群的差集成员
	 * @param int $gid
	 * @param boolean $extend 是否需要全部的信息，否的时候只有关联表的信息
	 * @return array
	 */
    public function getDifferenceGroupAndSubGroup($gid,$sid,$page)
	{
		$ids = array();
        $uids = $this->getDao('SubGroupMemberShip')->getUidOfMembersByGroup($sid,'');
        $groupusers = $this->getDao('GroupMemberShip')->getLastMembersByGroup($gid,$uids,$page);
		foreach($groupusers as $key => $value){
             $ids[] = $value['uid'];
		}
		$soap_users = $this->getDao('User', 'api')->getUsers($ids);
		foreach($groupusers as &$u) {
			unset($soap_users[$u['uid']]['id']);
			$u = array_merge($u, $soap_users[$u['uid']]);
			//@todo 可以优化：批量从redis中获取数据
			$u['avatar'] = get_avatar($u['uid'],'mm');
			$u['href'] = '';
		}
		return $groupusers;
	}
    
    /**
	 * 获得某群组的所有子群成员的详细信息
	 * @param int $gid
	 * @param boolean $extend 是否需要全部的信息，否的时候只有关联表的信息
	 * @return array
	 */
    public function getAllMembersBySubGroup($sid,$page)
	{
		$ids = array();
		$users = $this->getDao('SubGroupMemberShip')->getMembersByGroup($sid,$page);
        
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		$soap_users = $this->getDao('User', 'api')->getUsers($ids);
		foreach($users as &$u) {
			unset($soap_users[$u['uid']]['id']);
			$u = array_merge($u, $soap_users[$u['uid']]);
			//@todo 可以优化：批量从redis中获取数据
			$u['avatar'] = get_avatar($u['uid'],'s');
			$u['href'] = mk_url('main/index/profile',array('dkcode'=>$u['dkcode']));;
		}
		return $users;
	}
	
	/**
	 * 不带分页取出群组所有群组成员信息
	 * @param unknown_type $gid
	 * @param unknown_type $extend
	 */
	public function getAllMembersByGroupWithoutPage( $gid, $extend = false )
	{
		$ids = array();
		$users = $this->getDao( 'GroupMemberShip' )->getMembersByGroup( $gid );
		foreach( $users as $k => $v ){
			$ids[] = $v['uid'];
		}
		$api_users = $this->getDao( 'User', 'api' )->getUsers( $ids );
	
		if( $extend ){
			$ids = $temp = $infos = array();
			foreach( $users as $u ) {
				$ids[] = $u['id'];
			}
			$temp = $this->getDao( 'GroupMember' )->findByIds( $ids );
			foreach( $temp as $info ) {
				$infos[$info['id']] = $info;
			}
		}
		
		foreach( $users as &$u ) {
			if( $extend ) $u = array_merge( $u, $infos[$u['id']] );
			unset( $api_users[$u['uid']]['id'] );
			$u = array_merge($u, $api_users[$u['uid']]);
			//@todo 可以优化：批量从redis中获取数据
			$u['avatar'] = get_avatar( $u['uid'],'mm' );
			$u['href'] = mk_url( 'main/index/main', array( 'dkcode' => $u['dkcode'] ) );
		}
		return $users;
	}
	
	/**
	 * 查询群组用户信息
	 * 
	 * @param int $gid
	 * @param int $offset
	 * @param int $limit
	 * @see Manage::members()
	 */
	public function getMembersByGroupId( $gid, $offset, $limit )
	{
		// 查询出群组成员关系
		// 不查出管理员
		$users = $this->getDao( 'GroupMemberShip' )->getMembersShipByGroupId( $gid, $offset, $limit );
		
		$ids = array ();
		foreach ( $users as $value ) {
			$ids [] = $value ['uid'];
		}
		
		$api_users = $this->getDao( 'User', 'api' )->getUsers( $ids );
		foreach ( $users as &$u ) {
			unset( $api_users [$u ['uid']] ['id'] );
			$u = array_merge( $u, $api_users [$u ['uid']] );
			$u ['avatar'] = get_avatar( $u ['uid'], 'mm' );
			$u ['href'] = mk_url( 'main/index/main', array ( 'dkcode' => $u ['dkcode'] ) );
		}
		return $users;
	}
    
    /**
     *去掉本人uid
     * @param type $gid
     * @param type $page
     * @param type $extend
     * @return type 
     */
    public function getAllMembersExceptSelfByGroup($gid,$self_uid, $page = 1)
	{
		$ids = array();
		$page = intval($page) < 1 ? 1 : intval($page);
		$users = $this->getDao('GroupMemberShip')->getMembersExceptSelfByGroup($gid,$self_uid,$page);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		$api_users = $this->getDao('User', 'api')->getUsers($ids);
		foreach($users as &$u) {
			unset($api_users[$u['uid']]['id']);
			$u = array_merge($u, $api_users[$u['uid']]);
			//@todo 可以优化：批量从redis中获取数据
			$u['avatar'] = get_avatar($u['uid'],'mm');
			$u['href'] = mk_url( 'main/index/main', array('dkcode' => $u['dkcode']));
		}
		return $users;
	}
    
    /**
	 * 获得某群组的成员分页信息
	 * @param int $gid
	 * @return array
	 */
	public function getGroupMembersByPage($gid, $page, $limit)
	{
		$ids = array();
		$users = $this->getDao('GroupMemberShip')->getGroupMembersByPage($gid, $page, $limit);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		$api_users = $this->getDao('User', 'api')->getUsers($ids);
		foreach($users as &$u) {
			unset($api_users[$u['uid']]['id']);
			$u = array_merge($u, $api_users[$u['uid']]);
			//@todo 可以优化：批量从redis中获取数据
			$u['avatar'] = get_avatar($u['uid'],'mm');
			$u['href'] = mk_url( 'main/index/profile', array('dkcode' => $u['dkcode']));
		}
		return $users;
	}
}