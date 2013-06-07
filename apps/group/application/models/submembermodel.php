<?php
/*
 * 群组
 * title :
 * Created on 2012-07-04
 * @author yaohaiqi
 * discription : 子群成员基本业务逻辑
 */
class SubmemberModel extends MY_Model
{
	/**
	 * 加入群成员
	 * @param int $sid
	 * @param int $creator
	 * @param array[int] $uids
	 * @return boolean
	 */
	public function addMembers($sid, $creator, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
			
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
	 * @param int $sid
	 * @param int $uid
	 * @param boolean $extend 是否需要全部的信息，否的时候只有关联表的信息
	 * @return array
	 */
	public function getMemberByGroup($sid, $uid)
	{
		return $this->getDao('SubGroupMemberShip')->findByGroupByUser($sid, $uid);
	}

	/**
	 * 获得某群组的某些群成员的详细信息
	 * @param max $sid
	 * @param boolean $extend 是否需要全部的信息，否的时候只有关联表的信息
	 * @return array
	 */
	public function getMembersByGroup($sid, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		return $this->getDao('SubGroupMemberShip')->orderBy('id')->findByGroupByUsers($sid, $uids);
	}

	/**
	 * 获得某群组的所有群成员的详细信息
	 * @param int $sid
	 * @param boolean $extend 是否需要全部的信息，否的时候只有关联表的信息
	 * @return array
	 */
	public function getAllMembersByGroup($sid)
	{
		$ids = array();
		$users = $this->getDao('SubGroupMemberShip')->getMembersByGroups($sid);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		$api_users =$this->getDao('User', 'api')->getUsers($ids);
		$href = mk_url( APP_MAIN_URL . 'index');
		foreach($users as &$u) {
			if($extend) $u = array_merge($u, $infos[$u['id']]);
			unset($api_users[$u['uid']]['id']);
			$u = array_merge($u, $api_users[$u['uid']]);
			//@todo 可以优化：批量从redis中获取数据
			$u['avatar'] = get_avatar($u['uid'],'mm');
			$u['href'] = $href . '&action_dkcode=' . $u['dkcode'];
		}
		return $users;
	}
	
	/**
	 * 获得某群组的所有群成员的详细信息
	 * @param unknown_type $sid
	 */
	public function getAllMembersByGroups($sid)
	{
		$ids = array();
		$users = $this->getDao('SubGroupMemberShip')->getMembersByGroups($sid);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		$api_users =$this->getDao('User', 'api')->getUsers($ids);
		
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
	 * 获得某群组的所有群成员的ID集合
	 * @param int $sid
	 * @return array
	 */
	public function getAllMemberIdsByGroup($sid)
	{
		$ids = array();
		$users = $this->getDao('SubGroupMemberShip')->getMembersByGroup($sid);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		return $ids;
	}
	
	/**
	 * 获得某些群组的所有群成员的ID集合
	 * @param max $sids
	 * @return array
	 */
	public function getAllMemberIdsByGroups($sids)
	{
		if(!is_array($sids)) $sids = array(intval($gids));
		$ids = array();
		$users = $this->getDao('SubGroupMemberShip')->getMembersByGroups($sids);
		foreach($users as $k => $v){
			$ids[$v['sid']][] = $v['uid'];
		}
		return $ids;
	}

	/**
	 * 从群组中删除某个或某些成员
	 * @param int $sid
	 * @param max $uids
	 * @return boolean
	 */
	public function deleteByGroup($sid, $uids)
	{
		return $this->getDao('SubGroupMemberShip')->deleteByGroup($sid, $uids);
	}

	/**
	 * 从群组中删除所有成员
	 * @param int $sid
	 * @return boolean
	 */
	public function deleteAllByGroup($sid)
	{
		return $this->getDao('SubGroupMemberShip')->deleteAllByGroup($sid);
	}
	
	/**
     *获取子群成员数量
     * @return type 
     */
    public function getNumOfGroupMember($sid){
        return $this->getDao('SubGroupMemberShip')->getNumOfSubGroupMember($sid);
    }
    
     /**
     *去掉本人uid
     * @param type $gid
     * @param type $page
     * @param type $extend
     * @return type 
     */
    public function getAllMembersExceptSelfByGroup( $gid, $self_uid, $page = 1)
	{
		$ids = array();
		$page = intval($page) < 1 ? 1 : intval($page);
		$users = $this->getDao('SubGroupMemberShip')->getMembersExceptSelfByGroup($gid,$self_uid,$page,'');
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
     *创建子群时父群成员数量
     * @return type 
     */
    public function NumOfGroupMember($gid, $self_uid, $page = 1, $keyword = ''){
        $ids =$result= array();
        $users = $this->getDao('SubGroupMemberShip')->getMembersExceptSelfByGroup($gid,$self_uid,$page,$keyword);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		$api_users = $this->getDao('User', 'api')->getUsers($ids);
		foreach($users as $k => &$u) {
			$u = array_merge($u, $api_users[$u['uid']]);
            if(strstr($u['name'],$keyword)){
                $result[] = $u;
            }
		}
		return count($result);
    }
    
    /**
     *去掉本人uid
     * @param type $gid
     * @param type $page
     * @param type $extend
     * @return type 
     */
    public function searchMembersExceptSelfByGroup( $gid, $self_uid, $page = 1, $keyword = '',$limit)
	{
		$ids =$result= array();
        $offset = ($page - 1) * 20;
        $pagelimit = $page * 20;
		$page = intval($page) < 1 ? 1 : intval($page);
		$users = $this->getDao('SubGroupMemberShip')->getMembersExceptSelfByGroup($gid,$self_uid,$page,$keyword,$limit);
		foreach($users as $k => $v){
			$ids[] = $v['uid'];
		}
		$api_users = $this->getDao('User', 'api')->getUsers($ids);
		foreach($users as $k => &$u) {
			unset($api_users[$u['uid']]['id']);
			$u = array_merge($u, $api_users[$u['uid']]);
            if(strstr($u['name'],$keyword) && $k >= $offset && $k <= $pagelimit){
                //@todo 可以优化：批量从redis中获取数据
                $u['avatar'] = get_avatar($u['uid'],'mm');
                $u['href'] = mk_url( 'main/index/main', array('dkcode' => $u['dkcode']));
                $result[] = $u;
            }
		}
		return $result;
	}
    
    /**
	 * 添加子群成员时子群和群的差集成员数量
	 * @param int $gid
	 * @return array
	 */
    public function LastNumOfGroupMember($gid,$sid,$page,$keyword)
	{
		$ids = $result = array();
        $uids = $this->getDao('SubGroupMemberShip')->getUidOfMembersByGroup($sid,'');
        $groupusers = $this->getDao('GroupMemberShip')->getLastMembersByGroup($gid,$uids,$page);
		foreach($groupusers as $key => $value){
             $ids[] = $value['uid'];
		}
		$soap_users = $this->getDao('User', 'api')->getUsers($ids);
		foreach($groupusers as $k => &$u) {
			$u = array_merge($u, $soap_users[$u['uid']]);
            if(strstr($u['name'],$keyword)){
                $result[] = $u;
            }
		}
		return count($result);
	}
    
    /**
	 * 搜索某群组的子群和群的差集成员
	 * @param int $gid
	 * @return array
	 */
    public function searchDifferenceGroupAndSubGroup($gid,$sid,$page,$keyword,$limit)
	{
		$ids = $result = array();
        $offset = ($page - 1) * 20;
        $pagelimit = $page * 20;
        $uids = $this->getDao('SubGroupMemberShip')->getUidOfMembersByGroup($sid,'');
        $groupusers = $this->getDao('GroupMemberShip')->getLastMembersByGroup($gid,$uids,$page,$limit);
		foreach($groupusers as $key => $value){
             $ids[] = $value['uid'];
		}
		$soap_users = $this->getDao('User', 'api')->getUsers($ids);
		foreach($groupusers as $k => &$u) {
			unset($soap_users[$u['uid']]['id']);
			$u = array_merge($u, $soap_users[$u['uid']]);
            if(strstr($u['name'],$keyword) && $k >= $offset && $k <= $pagelimit){
                //@todo 可以优化：批量从redis中获取数据
                $u['avatar'] = get_avatar($u['uid'],'mm');
                $u['href'] = '';
                $result[] = $u;
            }
		}
		return $result;
	}
}