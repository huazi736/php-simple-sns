<?php if ( ! defined( 'BASEPATH' ) ) exit( 'No direct script access allowed' );

/**
 * 群的管理
 * @author Huifeng Yao
 */
class Manage extends MY_Controller
{
	
	/**
	 * 群组信息
	 */
	private $groupInfo = null;

	public function __construct()
	{
		parent::__construct();
		
		// 加载模型层
		$this->load->model( 'groupmodel', 'group' );
		$this->load->model( 'membermodel', 'member' );
		$this->load->model( 'managemodel', 'managemodel' );
		
		// 加载帮助类
		$this->load->helper( 'common' );
		
		/**
		 * 判断组是否存在
		 * 判读操作用户是否为管理员
		 */
		
 		$gid = intval( $this->input->get( 'gid' ) );
 		if ( !$gid ) {
 			$gid = intval( $this->input->post( 'gid' ) );
 		}
		
		// 获取群基础信息
		$this->groupInfo = $this->group->getGroup( $gid, true );
		
		if ( empty( $this->groupInfo ) ) {
			$this->showMessage( '群组不存在！', ErrorCode::CODE_GROUP_NOT_EXIST , array(), mk_url( 'main/index/main' ) );
		} elseif ( $this->uid != $this->groupInfo['creator'] ) {
			$this->showMessage( '你不是该群组的管理员！', ErrorCode::CODE_GROUP_NO_PERMISSION , array(), mk_url( 'main/index/main' ) );
		}
		$this->assign( 'group', $this->groupInfo );
	}
	
	/**
	 * 显示群基本信息
	 */
	public function index()
	{
		$gid = intval( $this->groupInfo['gid'] );
		
		$this->isEmpty( $gid );
		
		// 获取当前用户信息
		$user = $this->user;
		$user['avatar'] = get_avatar( $user['uid'] );
		
		// 获取群所有信息
		$group_info = $this->group->getGroup( $gid, true );
		
		$this->view( 'index', array( 'group_info' => $group_info, 'user' => $user ) );
	}
	
	/**
	 * 显示群成员
	 * @todo 实现分页
	 */
	public function members()
	{
		$gid = intval( $this->groupInfo['gid'] );
		
		$this->isEmpty( $gid );
		
		// 获取当前用户信息
		$user = $this->user;
		$user['avatar'] = get_avatar( $user['uid'] );
		
		// 获取所有群成员信息
		$offset = 0;
		$limit = 24;
		$members = $this->member->getMembersByGroupId( $gid, $offset, $limit );
		
		// 获取群所有信息
		$group_info = $this->group->getGroup( $gid, true );
		
		$this->view( 'members', array( 'group_info' => $group_info, 'members' => $members, 'user' => $user ) );
	}
	
	/**
	 * 加载更多成员
	 */
	public function load()
	{
		$gid = intval( $this->groupInfo['gid'] );
		$this->isEmpty( $gid );
		
		// 获取初始页面数
		$page = intval( $this->input->post( 'page' ) );
		$this->isEmpty( $page );
		
		$limit = 24;
		$offset = ( $page - 1 ) * 24;
		
		// 获取用户成员信息
		$members = $this->member->getMembersByGroupId( $gid, $offset, $limit );
		
		if ( count( $members ) < $limit ){
			$array = array( 'last' => true ,'list' => $members );
		} else {
			$array = array( 'last' => false ,'list' => $members );
		}
		
		$this->showMessage( '数据返回成功', ErrorCode::CODE_SUCCESS, $array );
	}
	
	/**
	 * ajax搜索用户操作
	 */
	public function search()
	{
		$gid = $this->input->post( 'gid' );
		$this->isEmpty( $gid );
		
		// 搜索关键字
		$keyword = $this->input->post( 'keyword' );
		
		// 获取初始页面数
		$page = intval( $this->input->post( 'page' ) );
		$this->isEmpty( $page );
		
		$limit = 24;
		$offset = ( $page - 1 ) * $limit;
		
		// 获取用户成员信息
		$this->load->model('userModel', 'account');
		
		$members = $this->account->getMembersByGroupIdAndKey( $this->uid, $gid, $keyword, $offset, $limit );
		
		if ( count( $members ) < $limit ){
			$array = array( 'last' => true ,'list' => $members );
		} else {
			$array = array( 'last' => false ,'list' => $members );
		}
		
		$this->showMessage( '数据返回成功', ErrorCode::CODE_SUCCESS, $array );
	}
	
	/**
	 * 更新群基本信息
	 */
	public function update()
	{
		/**
		 * @var int $gid 群组ID
		 * @var string $icon 群组图标
		 * @var int $invitation 群组加入方式
		 */
		$gid  		 = intval( $this->groupInfo['gid'] );
		$icon 		 = $this->input->post( 'icon' );
		$invite_type = intval( $this->input->post( 'invitation' ) );
		
		$group_info = $this->group->getGroup( $gid, true );
		
		// 更新基础信息
		$this->group->update( $gid, $group_info['name'], $group_info['description'], $icon );
		
		// 更新附加信息
		$this->group->updateExtend( $gid, $group_info['chat_enable'], $group_info['type'], $invite_type );
		
		// 返回到群管理首页
		$this->showMessage( '更新成功！', ErrorCode::CODE_SUCCESS, null, mk_url( 'group/index/detail', array( 'gid' => $gid ) ) );
	}
	
	/*
	 * 删除群成员
	 */
	public function remove()
	{
		/**
		 * @var int $gid 群组ID
		 * @var int $uid 用户ID
		 */
		$gid = intval( $this->groupInfo['gid'] );
		$uid = intval( $this->input->post( 'uid' ) );
		
		$this->isEmpty( $gid );
		$this->isEmpty( $uid );
		
		// 获取用户信息
		$user = $this->member->getMemberByGroup( $gid, $uid );
		
		// 判断用户是否在该组
		if ( empty( $user ) ) {
			$this->showMessage( '他/她不属于该群', ErrorCode::CODE_GROUP_MEMEBE_NOT_EXIST );
		}
		
		// 判断被删除用户是否为管理员
		if ( $user['position'] == GroupConst::GROUP_ROLE_MASTER ) {
			$this->showMessage( '未知原因删除失败', ErrorCode::CODE_GROUP_NO_PERMISSION );
		}
		
		// 删除群组和用户之间的关系
		$result = $this->managemodel->kickOut( $gid, $uid );
		
		// 获取群组信息
		$group_info = $this->group->getGroup( $gid );
		
		if ( $result ) {
			/**
			 * 删除成功，发送消息给被删除的用户
			 */
			service( 'Notice' )->add_notice( '1', $uid, $uid, 'group', 'group_out', array( 'name' => $group_info['name'], 'url'=>'#' ) );
			
			$this->showMessage( null, ErrorCode::CODE_SUCCESS );
		} else {
			// 未知原因删除失败
			$this->showMessage( '未知原因删除失败', ErrorCode::CODE_INVALID_POST );
		}
	}
	
	/**
	 * 解散群
	 */
	public function disband()
	{
		$gid = intval( $this->groupInfo['gid'] );
		
		$this->isEmpty( $gid );
		
		// 获取所有群成员信息
		$members = $this->member->getAllMembersByGroupWithoutPage( $gid );
		
		// 获取群组信息
		$group_info = $this->group->getGroup( $gid );
		
		// 解散群组操作
		$result = $this->managemodel->disband( $gid );
		
		if ( $result ) {
			// 循环向群组成员发送群组解散的信息
			// @todo 群发优化
			foreach ( $members as $member ) {
				service( 'Notice' )->add_notice( '1', $member['uid'], $member['uid'], 'group', 'group_dismiss', array( 'name' => $group_info['name'], 'url'=>'#' ) );
			}
			
			$this->showMessage( null, ErrorCode::CODE_SUCCESS );
		} else {
			// 未知原因删除失败
			$this->showMessage( '未知原因解散失败', ErrorCode::CODE_INVALID_POST );
		}
	}
	
	/**
	 * 验证输入参数是否为空
	 * @param unknown_type $param 验证参数
	 */
	private function isEmpty( $param )
	{
		if ( empty( $param ) ) {
			$this->showMessage( '无效的提交', ErrorCode::CODE_INVALID_POST );
		}
	}
	
}

/* End of file manage.php */
/* Location: ./single/group/application/controllers/manage.php */