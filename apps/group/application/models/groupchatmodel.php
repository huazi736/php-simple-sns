<?php
/*
 * 群组
 * title :
 * Created on 2012-06-16
 * @author hexin
 * discription : IM群聊
 */
class GroupChatModel extends MY_Model
{
	private $chat;
	
	public function __construct(){
		parent::__construct();
		$url = $this->ci->config->item("im_url");
		try {
			$this->chat = $this->getDao('GroupChat', 'hessian')->getClient($url);
		} catch (Exception $e) {
			throw $e;
			return -1;
		}
	}
	
	/**
	 * 开启群组聊
	 * @param unknown_type $gid
	 * @throws Exception
	 */
	public function createRoom( $gid )
	{
		$this->ci->load->model( 'groupmodel', 'group' );
		$group = $this->ci->group->getGroup( $gid );
		$this->ci->load->model( 'membermodel', 'member' );
		$members = $this->ci->member->getAllMemberIdsByGroup( $gid );
		$users = $this->getDao( 'User', 'api' )->getUsers( $members );

		try {
			/**
			 * 创建聊天室
			 * @see Hessian_GroupChat:createRoom( $authorId, $gid, $name, $members )
			 */
			return $this->chat->createRoom( $group ['creator'], $gid, $group ['name'], $users );
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}
	
	/**
	 * 创建子群聊天
	 * @param unknown_type $sid
	 * @throws Exception
	 */
	public function createSubRoom( $sid )
	{
		$this->ci->load->model( 'groupmodel', 'group' );
		$group = $this->ci->group->getGroup( $sid );
		$this->ci->load->model( 'membermodel', 'member' );
		$members = $this->ci->member->getAllMemberIdsByGroup( $sid );
		$users = $this->getDao( 'User', 'api' )->getUsers( $members );
	
		try {
			/**
			 * 创建聊天室
			 * @see Hessian_GroupChat:createRoom( $authorId, $gid, $name, $members )
			 */
			return $this->chat->createSubRoom( $group ['creator'], $sid, $group ['name'], $users );
		} catch ( Exception $e ) {
			throw $e;
			return - 1;
		}
	}
	
	
	
	public function addMember($gid, $uid)
	{
		$this->ci->load->model('groupmodel', 'group');
		$group = $this->ci->group->getGroup($gid);
		$user = $this->getDao('User', 'redis')->getUserInfo($uid);
		try {
			return $this->chat->addMember($group['creator'], $uid, $user['username'], $gid);
		} catch (Exception $e) {
			throw $e;
			return -1;
		}
	}
	
	public function delMember($gid, $uid)
	{
		$this->ci->load->model('groupmodel', 'group');
		$group = $this->ci->group->getGroup($gid);
		try {
			return $this->chat->delMember($group['creator'], $uid, $gid);
		} catch (Exception $e) {
			throw $e;
			return -1;
		}
	}
	
	public function destroyRoom($gid)
	{
		$this->ci->load->model('groupmodel', 'group');
		$group = $this->ci->group->getGroup($gid);
		try {
			return $this->chat->destroyRoom($group['creator'], $gid);
		} catch (Exception $e) {
			throw $e;
			return -1;
		}
	}
}