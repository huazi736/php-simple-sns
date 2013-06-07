<?php
/**
 * 群组管理业务逻辑类
 * 
 * @author Huifeng Yao
 * @todo 是否采用转移数据的方式删除用户
 */
class ManageModel extends MY_Model
{
	
	public function __construct()
	{
		parent::__construct();
		
		$url = $this->ci->config->item( "im_url" );
		
		try {
			$this->hession_group = $this->getDao( 'GroupChat', 'hessian' )->getClient( $url );
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
		
		// 加载模型层
		$this->ci->load->model( 'membermodel', 'member' );
		$this->ci->load->model( 'groupmodel', 'group' );
	}
	
	/**
	 * 群解散
	 *
	 * @param $gid int  
	 * @see Manage::disband()
	 */
	public function disband( $gid )
	{
		$group = $this->group->getGroup( $gid );
		
		// 删除群组与用户关系
		try {
			$this->hession_group->destroyRoom( $group ['creator'], $gid );
		} catch ( Exception $e ) {
			//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
			//die();
		}
		
		/**
		 * 删除该用户在子群中的用户关系
		 */
		// 查询该群组的子群
		$field = array ( 'sid' );
		$sub_groups = $this->getDao( 'SubGroupInfo' )->findByGid( $gid, $field );
		
		// 遍历群组内的子群信息，并全部解散
		$this->ci->load->model( 'SubgroupModel', 'subgroup' );
		foreach ( $sub_groups as $sub_group ) {
			$this->subgroup->disband( $sub_group ['sid'] );
		}
		
		// 删除用户关系
		$this->ci->member->deleteAllByGroup( $gid );
		
		// 删除群信息
		$this->getDao( 'GroupInfo' )->delete( $gid );
		
		// 删除群邀请信息
		$this->getDao( 'GroupInvite' )->deleteGid( $gid );
		
		// 删除群应用信息
		$this->getDao( 'GroupAPPShip' )->deleteGid( $gid );
		
		return true;
	}
	
	/**
	 * 群组踢掉某人
	 * 
	 * @param $gid int        	
	 * @param $uid int       	
	 * @return unknown
	 * @see 调用关系 : Manage::remove()
	 */
	public function kickOut( $gid, $uid )
	{
		// 查询出该群组的信息
		$group = $this->group->getGroup( $gid, true );
		
		// 查询该群组的子群
		$sub_groups = $this->getDao( 'SubGroupInfo' )->findByGid( $gid, array ( 'sid', 'creator' ) );
		
		/**
		 * 优先删除java端数据
		 * 
		 * @todo 消息队列模式
		 */
			// 查询出该群组中该用户加入的子群ID
			$in_subgroups = array();
			foreach ( $sub_groups as $sub_group ) {
				if ( $this->getDao( 'SubGroupMemberShip' )->findByGroupByUser( $sub_group ['sid'], $uid ) ) {
					array_push( $in_subgroups, array( 'creator' => $sub_group ['creator'], 'sid' => $sub_group ['sid'] ) );
				}
			}
			
			foreach ( $in_subgroups as $in_subgroup ) {
				// 删除子群与用户关系
				try {
					$this->hession_group->delMember( $in_subgroup ['creator'], $uid, $in_subgroup ['sid'] );
				} catch ( Exception $e ) {
					//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
					//die();
				}
				
				$this->getDao( 'SubGroupMemberShip' )->deleteByGroup( $in_subgroup ['sid'], $uid );
			}
		
			// 删除群组与用户关系
			try {
				$this->hession_group->delMember( $group ['creator'], $uid, $gid );
			} catch ( Exception $e ) {
				//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
				//die();
			}
			
		/**
		 * 删除该用户在子群中的用户关系
		 */
			// 删除用户关系
			$this->ci->member->deleteByGroup( $gid, $uid );
				
			// 更新群组成员数量
			$extend = null;
			$extend ['chat_enable'] 	= intval( $group ['chat_enable'] );
			$extend ['type'] 			= $group ['type'];
			$extend ['invitation'] 		= intval( $group ['invitation'] );
			$extend ['member_counts'] 	= intval( $group ['member_counts'] - 1 );
			
			$this->getDao( 'GroupExtend' )->update( $gid, $extend );
		
		return true;
	}
	
	/**
	 * 从群中退出
	 *
	 * @param $gid unknown_type       	
	 * @param $uid unknown_type       	
	 * @throws Exception
	 * @return number
	 */
	public function quit( $gid, $uid )
	{
		// 获取群组信息
		$group = $this->group->getGroup( $gid );
		
	    /**
		 * 删除该用户在子群中的用户关系
		 */
		// 查询该群组的子群
		$field = array( 'sid' );
		$sub_groups = $this->getDao( 'SubGroupInfo' )->findByGid( $gid, $field );
		
		// 查询出该群组中该用户加入的子群ID
		$in_subgroups = array();
		foreach ( $sub_groups as $sub_group ) {
			if ( $this->getDao( 'SubGroupMemberShip' )->findByGroupByUser( $sub_group ['sid'], $uid ) ) {
				array_push( $in_subgroups, array( 'creator' => $sub_group ['creator'], 'sid' => $sub_group ['sid'] ) );
			}
		}
		
		foreach ( $in_subgroups as $in_subgroup ) {
			// 调用接口删除对应的java数据
			try {
				$this->hession_group->delMember( $in_subgroup ['creator'], $uid, $in_subgroup ['sid'] );
			} catch ( Exception $e ) {
				//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
				//die();
			}
			// 删除子群与用户关系
			$this->getDao( 'SubGroupMemberShip' )->deleteByGroup( $in_subgroup ['sid'], $uid );
		}
		
		/**
		 * 调用接口删除对应的java数据
		 *
		 * @todo 返回处理
		 */
		try {
			$this->hession_group->delMember( $group ['creator'], $uid, $gid );
		} catch ( Exception $e ) {
			//$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
			//die();
		}
		
		// 删除关系操作
		$this->ci->member->deleteByGroup( $gid, $uid );
		
		// 更新群组成员数量
		$extend = null;
		$extend ['chat_enable'] 	= intval( $group ['chat_enable'] );
		$extend ['type'] 			= $group ['type'];
		$extend ['invitation'] 		= intval( $group ['invitation'] );
		$extend ['member_counts'] 	= intval( $group ['member_counts'] - 1 );
			
		$this->getDao( 'GroupExtend' )->update( $gid, $extend );
		
		return true;
	}

}
/* End of file managemodel.php */
/* Location: ./single/group/application/models/managemodel.php */