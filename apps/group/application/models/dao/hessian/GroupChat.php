<?php
/**
 * IM对接 : Created on 2012-06-19 
 * @author hexin
 */
require_once APPPATH . 'libraries/HessianPHP/HessianClient.php';

class Hessian_GroupChat
{
	private $proxy;
	
	public function getClient( $url )
	{
		try {
			$this->proxy = new HessianClient( $url );
			return $this;
		} catch ( HessianException $e ) {
			throw $e;
		} catch ( Exception $e ) {
			throw $e;
		}
	}
	
	/**
	 * 创建群聊
	 *
	 * @param $authorId int 群主ID
	 * @param $gid int 群号
	 * @param $name string 群名称
	 * @param $members array 群成员ID集合
	 */
	public function createRoom( $authorId, $gid, $name, $members )
	{
		$ids = array ();
		foreach ( $members as $m ) {
			$ids [] = $m ['id'];
		}
		$array = array ( 'userid' => "$authorId", "roomid" => "$gid", "roomnick" => urlencode( $name ), "memberids" => $ids );
		try {
			return $this->proxy->createRoom( json_encode( $array ) );
		} catch ( HessianException $e ) {
			throw $e;
			return - 1;
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}
	
	/**
	 * 创建子房间
	 * 
	 * @param unknown_type $authorId
	 * @param unknown_type $sid
	 * @param unknown_type $gid
	 * @param unknown_type $name
	 * @param unknown_type $members
	 * @throws HessianException
	 * @throws Exception
	 */
	public function createSubRoom( $authorId, $sid, $gid, $name, $members )
	{
		
		$ids = array ();
		foreach ( $members as $m ) {
			
			$ids [] = $m;
		}
		
		$array = array ( 'userid' => "$authorId", "roomid" => "$sid", "parentid" => $gid, "roomnick" => urlencode( $name ), "memberids" => $ids );
		
		try {
			return $this->proxy->createRoom( json_encode( $array ) );
		} catch ( HessianException $e ) {
			throw $e;
			return - 1;
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}
	
	/**
	 * 从群聊成员中添加成员
	 *
	 * @param $authorId int 群主ID
	 * @param $memberId int 群成员ID
	 * @param $username string 群成员名称
	 * @param $gid int 群号
	 */
	public function addMember( $authorId, $memberId, $gid )
	{
		try {
			$ret_arr = array ( 'userid' => $authorId, 'memberids'=> $memberId, 'roomid' => $gid );
			return $this->proxy->addMember( json_encode( $ret_arr ) );
		} catch ( HessianException $e ) {
			throw $e;
			return - 1;
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}
	
	/**
	 * 批量添加成员
	 * @param unknown_type $authorId
	 * @param unknown_type $memberids
	 * @param unknown_type $gid
	 * @throws HessianException
	 * @throws Exception
	 */
	public function addMembers( $authorId, $memberids, $gid )
	{
		try {
			$ret_arr = array ( 'userid' => $authorId, 'memberiIds'=> $memberids, 'roomid' => $gid );
			return $this->proxy->addMember( json_encode( $ret_arr ) );
		} catch ( HessianException $e ) {
			throw $e;
			return - 1;
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}
	
	/**
	 * 从群聊成员中删除成员
	 * 
	 * @param unknown_type $authorId
	 * @param unknown_type $memberId
	 * @param unknown_type $gid
	 * @throws HessianException
	 * @throws Exception
	 */
	public function delMember( $authorId, $memberId, $gid )
	{
		try {
			$ret_arr = array ( 'userid' => $authorId, 'targetuid'=> $memberId, 'roomid' => $gid );
			return $this->proxy->delMember( json_encode( $ret_arr ) );
		} catch ( HessianException $e ) {
			throw $e;
			return - 1;
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}
	
	/**
	 * 解散群聊
	 * 
	 * @param $authorId int       	
	 * @param $gid int       	
	 */
	public function destroyRoom( $authorId, $gid )
	{
		try {
			$ret_arr = array ( 'userid' => $authorId, 'roomid' => $gid );
			return $this->proxy->destroyRoom( json_encode( $ret_arr ) );
		} catch ( HessianException $e ) {
			throw $e;
			return - 1;
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}

}
/* End of file groupchat.php */
/* Location: ./single/group/application/models/dao/hessian/groupchat.php */
