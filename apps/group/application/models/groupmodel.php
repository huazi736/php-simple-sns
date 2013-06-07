<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组基本业务逻辑
 */
class GroupModel extends MY_Model
{
	public function __construct()
	{
		parent::__construct();
	
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
	 * 获得所有群组分类
	 * @return array
	 */
	public function getAllType()
	{
		$this->ci->load->model('typemodel', 'type');
		return $this->ci->type->getAll();
	}
	
	/**
	 * 获得我创建的群
	 * @param int $uid
	 * @return array
	 */
	public function getMyGroups($uid, $extend = false)
	{
		$types = $this->getAllType();
		$groups = $this->getDao('GroupInfo')->getMyGroups($uid);
		if(!empty($groups)){
			if($extend) $extends = $this->getDao('GroupExtend')->findByIds($gids);
			foreach($groups as &$g){
				$g['type'] = $types[$g['source_type']];
				if($extend) $g = array_merge($g, $extends[$g['gid']]);
			}
		}
		return $groups;
	}
	
	/**
	 * 获得我参与的群
	 * @param int $uid
	 * @return array
	 */
	public function getJoinGroups($uid, $extend = false)
	{
		$groups = $this->getDao('GroupMemberShip')->getGroupsByMember($uid, GroupConst::GROUP_ROLE_MEMBER);
		$gids = array();
		foreach($groups as $g){
			$gids[] = $g['gid'];
		}
		$groups = $this->getDao('GroupInfo')->findByIds($gids);
		$types = $this->getAllType();
		if(!empty($groups)){
			if($extend) $extends = $this->getDao('GroupExtend')->findByIds($gids);
			foreach($groups as &$g){
				$g['type'] = $types[$g['source_type']];
				if($extend) $g = array_merge($g, $extends[$g['gid']]);
			}
		}
		return $groups;
	}
	
	/**
	 * 获得我创建和我参与的所有群
	 * @param int $uid
	 * @return array
	 */
	public function getAllGroups($uid, $source_type = GroupConst::GROUP_TYPE_CUSTOM, $extend = false, $position = true)
	{
		$groups = $this->getDao('GroupMemberShip')->getGroupsByMember($uid);
		$gids = array();
		foreach($groups as $g){
			$gids[] = $g['gid'];
			$array[$g['gid']] = $g['position'];
		}
		$mygroups = $this->getDao('GroupInfo')->findByIdsByType($gids, $source_type);
		$types = $this->getAllType();
		$groups_by_type = array();
		if(!empty($mygroups)){
			if($extend) $extends = $this->getDao('GroupExtend')->findByIds($gids);
			foreach($mygroups as &$g){
				$this->getGroupIcon($g);
				$g['type'] = $types[$g['source_type']];
				$g['frequency'] = frequencyOfUse($g['update_time']);
				if($extend) $g = array_merge($g, $extends[$g['gid']]);
				if($position) $groups_by_type[$array[$g['gid']]][] = $g;
				else $groups_by_type[] = $g;
			}
		}
		return $groups_by_type;
	}
	
	/**
	 * 根据ID获得群组的详细信息
	 * @param int $gid
	 * @return array
	 */
	public function getGroup($gid, $extend = false)
	{
		$group = $this->getDao('GroupInfo')->findById($gid);
		$types = $this->getAllType();
		if(isset($group['scource_type'])) $group['type'] = $types[$group['source_type']];
		$this->getGroupIcon($group);
		if($extend){
			$ext = $this->getDao('GroupExtend')->findById($gid);
			$group = array_merge($group, $ext);
		}
		return $group;
	}
	
	/**
	 * 根据ID集合获得群组的详细信息
	 * @param array $gids
	 * @param boolean $extend
	 */
	public function getGroups($gids, $extend = false)
	{
		if(!is_array($gids)) $gids = array(intval($gids));
		if(empty($gids)) return array();
		$groups = $this->getDao('GroupInfo')->findByIds($gids);
		$types = $this->getAllType();
		if($extend) {
			$temp = $this->getDao('GroupExtend')->findByIds($gids);
			$exts = array();
			foreach($temp as $t){
				$exts[$t['gid']] = $t;
			}
		}
		foreach($groups as &$g) {
			$g['type'] = $types[$g['source_type']];
			$this->getGroupIcon($g);
			if($extend) $g = array_merge($g, $exts[$g['gid']]);
		}
		return $groups;
	}
	
	/**
	 * 获得群组的扩展信息
	 * @param $gid
	 */
	public function getGroupExtend($gid)
	{
		return $this->getDao('GroupExtend')->findById($gid);
	}
	
	/**
	 * 获得全站唯一名称的群组信息
	 * @param unknown_type $name
	 * @param unknown_type $source_type
	 */
	public function getUniqueByName($name, $source_type = GroupConst::GROUP_TYPE_FANS)
	{
		return $this->getDao('GroupInfo')->findUniqueByName($name, $source_type);
	}
	
	/**
	 * 获得当前用户自己的唯一名称的群组id
	 */
	public function getGroupByName($uid, $name, $source_type = GroupConst::GROUP_TYPE_CUSTOM) {
		return $this->getDao('GroupInfo')->ifExist($name, $uid, $source_type);
	}
	
	/**
	 * 根据名称得到群组数据集合
	 * @param array $name
	 * @param $source_type
	 */
	public function getGroupByNames($name, $source_type = GroupConst::GROUP_TYPE_CUSTOM, $friend_ids = array(), $extend = false) {
		if(!is_array($name)) $name = array($name);
		$list = $this->getDao('GroupInfo')->findByNames($name, $source_type, $friend_ids);
		$array = $gids = array();
		if(!empty($list)) {
			foreach($list as $l) {
				$array[$l['name']] = $l;
				$gids[] = $l['gid'];
			}
			if($extend){
				$ext = $this->getDao('GroupExtend')->findByIds($gids);
				foreach($array as $k=>&$g){
					$array[$k] = array_merge($g, $ext[$g['gid']]);
				}
			}
		}
		return $array;
	}
	
	/**
	 * 获得我创建的和我参与好友创建的一个群
	 * @param string $name 群名称
	 * @param int $uid 群主
	 * @param string $source_type 群类别
	 */
	public function getGroupExistByFriends($name, $uid, $source_type = GroupConst::GROUP_TYPE_CUSTOM){
		return $this->getDao('GroupInfo')->findExistGroupByName($name, $uid, $source_type);
	}
	
	/**
	 * 获取群组头像，没有的话是默认头像
	 * @param array $group
	 */
	private function getGroupIcon(&$group){
		if(isset($group['icon'])) {
			//todo 以后会有上传头像功能，对应的有fastdfs地址
		}elseif(isset($group['source_type'])){
			$group['icon'] = MISC_ROOT . "img/group/icon/subgroup/icon_default.png";
			//$group['icon'] = MISC_ROOT . "img/group/icon/group/group_logo_" . strtolower($group['source_type']) . ".png";
		}
	}
	
	/**
	 * 创建群，创建成功返回群组的gid
	 * @param int $authorId 群主ID
	 * @param string $name 群名称
	 * @param max $uids 群成员ID数组
	 * @param string $source_type 群来源分类
	 * @param string $description 群简介
	 * @param string $icon 群头像
	 * @param string $type 自定义分类
	 * @return int $gid
	 */
	public function create($authorId, $name, $uids, $source_type, $description = null, $icon = null, $type = null)
	{	
		if(!is_array($uids)) $uids = array($uids);
		$source_type = empty($source_type)? GroupConst::GROUP_TYPE_CUSTOM : $source_type;
		
		$gid = $this->getDao('GroupInfo')->ifExist($name, $authorId, $source_type);
        
		if($gid == 0) {
            $insert = array(
                'name' => $name,
                'description' => nl2br($description),
                'source_type' => $source_type,
                'creator' => $authorId,
                'icon' => $icon ? $icon : null,
            );
            $id = $this->getDao('GroupInfo')->create($insert);
            $gid = $this->_generateGid($id);
            $this->getDao('GroupExtend')->create(array('gid' => $gid, 'member_counts' => 0, 'type' => $type));

            $this->ci->load->model('membermodel', 'member');
//            $uids = array_merge(array($authorId), $uids);
//            $this->ci->member->addMembers($gid, $authorId, array_unique($uids));
//
//            $this->getDao('GroupExtend')->setMemberInc($gid, count($uids)+1);
			$this->ci->member->addMembers($gid, $authorId, $authorId);
			$this->ci->member->invite($gid, $authorId, $uids);

            //群组至少要有信息流应用
            $this->ci->load->model('appModel', 'app');
            $this->ci->app->install($gid, $authorId, GroupConst::GROUP_APP_INFOMATION_FLOW);
			$this->ci->app->install($gid, $authorId, GroupConst::GROUP_APP_ACTIVITY);
		}
		return $gid;
	}
	
	private function _generateGid($id)
	{
		$gid = base_convert(30000000000+$id."a".rand(10,99), 11, 10);
		$this->getDao('GroupInfo')->updateGid($id, $gid);
		return $gid;
	}
	
	/**
	 * 更新群信息
	 * @param int $gid 群号
	 * @param string $name 群名称
	 * @param string description 群简介
	 * @param string icon 群头像
	 * @return int
	 */
	public function update($gid, $name, $description=null, $icon=null)
	{
		$gid = intval($gid);
		$group['name'] = $name;
		if( $description !== null ) 
			$group['description'] = nl2br($description);
		if( $icon !== null ) 
			$group['icon']= $icon;
		$group['update_time'] = date('Y-m-d H:i:s');
		return $this->getDao('GroupInfo')->update($gid, $group);
	}
	
	public function updateExtend($gid, $chat_enable = null, $type = null, $invitation = null, $member_count = null) {
		$gid = intval($gid);
		if($chat_enable !== null) $extend['chat_enable'] = intval($chat_enable);
		if($type !==null) $extend['type'] = $type;
		if($invitation !==null) $extend['invitation'] = intval($invitation);
		if($member_count !==null) $extend['member_counts'] = intval($member_count);
		return $this->getDao('GroupExtend')->update($gid, $extend);
	}
	
	/**
	 * 群组直接加人:如果用户已存在则返回用户ID,否则插入数据库
	 * @param int $gid 群号
	 * @param int $authorId 群主
	 * @param max $uids 被邀请群成员数组
	 */
	public function addMember($gid, $authorId, $uids)
	{
		$gid = intval($gid);
		if(!is_array($uids)) $uids = array($uids);
		
		$users = $this->getDao('GroupMemberShip')->checkMembersExist($gid, $uids);
		$uids = array_diff($uids, $users);
		if(empty($uids)) return $users;

		//@todo 使用消息队列来做，这样就不需要处理异常情况
		$this->hession_group->addMember( $authorId, $uids, $gid );
			
		$this->ci->load->model('membermodel', 'member');
		$this->ci->member->addMembers($gid, $authorId, $uids);
		$this->getDao('GroupExtend')->setMemberInc($gid, count($authorId));
		
		if(!empty($users)) return $users;
		else return array();
	}
	
	/**
	 * 从群中退出
	 * @param int $gid
	 * @param int $uid
	 * @return boolean
	 */
	public function quit($gid, $uid)
	{
		$this->ci->load->model('membermodel', 'member');
		$this->ci->member->deleteByGroup($gid, $uid);
		return true;
	}
	
    public function group_config_num($uid){
        $result = array();
        $config_num = $this->getDao('Config')->findById('group_num');
        $group_num = $this->getDao('GroupInfo')->getNumOfMyGroups($uid);
        $result['is_exceed'] = $group_num['group_num'] <= $config_num['value'] ? TRUE : FALSE;
        $result['limit_num'] = $config_num['value'];
        return $result;
    }
    
    public function group_member_config_num($gid){
        $result = array();
        $config_num = $this->getDao('Config')->findById('group_member_num');
        $group_num = $this->getDao('GroupMemberShip')->getNumOfGroupMember($gid);
        $result['limit_num'] = $config_num['value'];
        $result['now_num'] = $group_num['0']['num'];
        return $result;
    }

	/**
	 * 被邀请的群组信息
	 * @param int $uid 当前登录的人
	 */
	public function invitedGroups($uid, $lastId = null, $limit = GroupConst::GROUP_PAGESIZE)
	{
		$invite = $this->getDao('GroupInvite')->findByUid($uid, $limit, $lastId);
		$ids = $from_uids = array();
		foreach($invite as $in) {
			$ids[] = $in['gid'];
			$from_uids[] = $in['from_uid'];
		}
		$temp = $this->getGroups($ids);
		$groups = array();
		foreach($temp as $g) {
			$groups[$g['gid']] = $g;
		}
		$users = $this->getDao('User', 'api')->getUsers($from_uids);
		foreach($invite as $k => &$in) {
			if(!isset($groups[$in['gid']])){
				unset($invite[$k]);
				continue;
			}
			$in['group'] = $groups[$in['gid']];
			$users[$in['from_uid']]['avatar'] = get_avatar($users[$in['from_uid']]['id'],'s');
			$users[$in['from_uid']]['href'] = mk_url( 'main/index/profile', array('dkcode' => $users[$in['from_uid']]['dkcode']));
			$in['from'] = $users[$in['from_uid']];
		}
		return $invite;
	}
}