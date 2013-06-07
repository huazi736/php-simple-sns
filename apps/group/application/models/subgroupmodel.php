<?php
/*
 * 群组
 * title :
 * Created on 2012-07-04
 * @author yaohaiqi
 * discription : 子群组基本业务逻辑
 */
class SubgroupModel extends MY_Model
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
	 * 获得子群的详细信息
	 * @param int $sid
	 * @return array
	 */
	public function getGroup($sid)
	{
		return $this->getDao('SubGroupInfo')->findById($sid);
	}
	
	/**
	 * 获得当前用户的唯一名称的子群id
	 * @param int gid
	 * @param string name
	 * @return int
	 */
	public function getGroupByName($gid, $name) {
		return $this->getDao('SubGroupInfo')->ifExist($gid, $name);
	}
	
	/**
	 * 根据名称得到子群数据集合
	 * @param array $name
	 * @param $source_type
	 * @param array
	 */
	public function getGroupByNames($gid, $name) {
		if(!is_array($name)) $name = array($name);
		$list = $this->getDao('SubGroupInfo')->findByNames($gid, $name);
		$array = array();
		foreach($list as $l) {
			$array[$l['name']] = $l;
		}
		return $array;
	}
	
	/**
	 * 创建群，创建成功返回群组的sid
	 * @param int $gid 父群号
	 * @param int $authorId 群主ID
	 * @param string $name 群名称
	 * @param max $uids 群成员ID数组
	 * @param string $description 群简介
	 * @param string $icon 群头像
	 * @return int $gid
	 */
	public function create( $gid, $authorId, $name, $uids, $description = null, $icon = null )
	{
		$sid = $this->getDao( 'SubGroupInfo' )->ifExist( $gid, $name );
		
		if ( $sid == 0 ) {
			$insert = array ( 
					'gid' 			=> $gid, 
					'sid' 			=> $this->_generateSid( $gid ), 
					'name' 			=> $name, 
					'description' 	=> nl2br( $description ), 
					'creator' 		=> $authorId, 
					'icon' 			=> $icon ? $icon : null 
			);
			$sid = $insert ['sid'];
			
			/**
			 * IM 接口数据添加，创建群聊数据
			 *
			 * @author Huifeng Yao
			 * @see Hessian_GroupChat::createSubRoom( $authorId, $sid, $gid, $name, $members )
			 */
			try {
				$hessian_result = $this->hession_group->createSubRoom( $authorId, $sid, $gid, $name, $uids );
			} catch ( Exception $e ) {
				//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
				//die();
			}
			// $obj = json_decode( $hessian_result );
			
			// 判断java端群聊数据是否创建成功
			//if ( $obj->code ) {
				$this->getDao( 'SubGroupInfo' )->create( $insert );
				$this->ci->load->model( 'submembermodel', 'member' );
				$uids = array_merge( array ( $authorId ), $uids );
				$this->ci->member->addSubGroupMembers( $sid, $authorId, array_unique( $uids ) );
				return $sid;
			//} else {
			//	return 0;
			//}
		}
	}
	
	private function _generateSid($id)
	{
		$sid = time().rand(100000,999999);
		//$this->getDao('SubGroupInfo')->updateSid($id, $sid);
		return $sid;
	}
	
	/**
	 * 更新群信息
	 * @param int $sid 群号
	 * @param string $name 群名称
	 * @param string description 群简介
	 * @param string icon 群头像
	 * @return int
	 */
	public function update($sid, $name, $description=null, $icon=null)
	{
		$gid = intval($gid);
		$group['name'] = $name;
		$group['description'] = nl2br($description);
		$group['icon'] = $icon;
		$group['update_time'] = date('Y-m-d H:i:s');
		return $this->getDao('GroupInfo')->update($sid, $group);
	}
	
	/**
	 * 邀请群:如果用户已存在则返回用户ID,否则插入数据库
	 * @param int $sid 群号
	 * @param max $uids 被邀请子群成员数组
	 */
	public function invite($sid, $authorId, $uids)
	{
		$sid = intval($sid);
		if(!is_array($uids)) $uids = array($uids);
		
		$users = $this->getDao('SubGroupMemberShip')->checkMembersExist($sid, $uids);
		if(!empty($users))
			return $users;	
		
		$this->ci->load->model('submembermodel', 'member');
		$this->ci->member->addMembers($sid, $authorId, $uids);
		$this->getDao('SubGroup')->setMemberInc($sid, count($authorId));
		
		/**
		 * IM 接口数据添加
		 *
		 * @author Huifeng Yao
		 * @see Hessian_GroupChat::addMember( $authorId, $memberId, $gid )
		 */
		try {
			$this->hession_group->addMember( $authorId, $uids, $sid );
		} catch ( Exception $e ) {
			//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
			//die();
		}
		
		return array();
	}
	
	/**
	 * 群解散
	 * @param int $sid
	 * @return boolean
	 */
	public function disband( $sid )
	{
		// 取出子群信息
		$subgroup_info = $this->getGroup( $sid );
		
		/**
		 * 调用接口删除对应的java数据
		 *
		 * @todo 返回处理
		 */
		try {
			$this->hession_group->destroyRoom( $subgroup_info ['creator'], $sid );
		} catch ( Exception $e ) {
			//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
			//die();
		}
		
		// 删除子群关系
		$this->getDao( 'SubGroupMemberShip' )->deleteAllByGroup( $sid );
		
		// 删除子群信息
		$this->getDao( 'SubGroupInfo' )->delete( $sid );
		
		return true;
	}
	
	/**
	 * 群踢掉某人
	 * 
	 * @param $sid int       	
	 * @param $uid int       	
	 * @return boolean
	 */
	public function kickOut( $sid, $uid )
	{
		// 取出子群信息
		$subgroup_info = $this->getGroup( $sid );
		
		// 调用接口删除对应的java数据
		try {
			$this->hession_group->delMember( $subgroup_info ['creator'], $uid, $sid );
		} catch ( Exception $e ) {
			//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
			//die();
		}
		
		$this->getDao( 'SubGroupMemberShip' )->deleteByGroup( $sid, $uid );
		return true;
	}
	
	/**
	 * 从群中退出
	 * 
	 * @param $gid int       	
	 * @param $uid int       	
	 * @return boolean
	 */
	public function quit( $sid, $uid )
	{
		// 取出子群信息
		$subgroup_info = $this->getGroup( $sid );
		
		// 调用接口删除对应的java数据
		try {
			$this->hession_group->delMember( $subgroup_info ['creator'], $uid, $sid );
		} catch ( Exception $e ) {
			//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
			//die();
		}
		
		$this->getDao( 'SubGroupMemberShip' )->deleteByGroup( $sid, $uid );
		return true;
	}
    
    /**
	 * 获得我创建和我参与的所有子群
	 * @param int $uid
	 * @return array
	 */
	public function getAllGroups($uid,$gid)
	{
		$groups = $this->getDao('SubGroupMemberShip')->getGroupsByMember($uid);
		$sids = $group = array();
		foreach($groups as $g){
			$sids[] = $g['sid'];
			$array[$g['sid']] = $g['position'];
		}
		$mygroups = $this->getDao('SubGroupInfo')->findByIdsByType($sids);
		foreach($mygroups as &$g) {
            if($g['gid'] == $gid){
                $g['icon'] = MISC_ROOT.$g['icon'];
                $group[] = $g;
            }
		}
		return $group;
	}
	
	private function getIcon(&$group)
	{
		if(isset($group['icon']) && !empty($group['icon'])) {
			$group['icon'] = $group['icon'];
		} else {
			$group['icon'] = MISC_ROOT . "img/group/icon/subgroup/icon_default.png";
		}
	}
    
    public function subgroup_config_num($uid,$gid){
        $result = array();
        $config_num = $this->getDao('Config')->findById('subgroup_num');
        $group_num = $this->getDao('SubGroupInfo')->getNumOfMySubGroups($uid,$gid);
        $result['is_exceed'] = $group_num['subgroup_num'] <= $config_num['value'] ? TRUE : FALSE;
        $result['limit_num'] = $config_num['value'];
        return $result;
    }
    
    public function subgroup_member_config_num($sid){
        $result = array();
        $config_num = $this->getDao('Config')->findById('subgroup_member_num');
        $group_num = $this->getDao('SubGroupMemberShip')->getNumOfSubGroupMember($sid);
        //$result['is_exceed'] = $group_num['0']['num'] <= $config_num['value'] ? TRUE : FALSE;
        $result['limit_num'] = $config_num['value'];
        $result['now_num'] = $group_num['0']['num'];
        return $result;
    }
}