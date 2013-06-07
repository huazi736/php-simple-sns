<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组页面展示控制，只有群主有权限操作
 */
class App extends MY_Controller
{
	/*
	 * 群组信息
	 */
	private $groupInfo = null;
	/*
	 * 群成员ID集合
	 */
	private $members = array();
	
	public function __construct(){
		parent::__construct();
		$this->load->model('groupmodel', 'group');
		$this->load->model('membermodel', 'member');
		$this->load->model('appmodel', 'app');
		$this->load->helper('common');
		$this->_checkPermission();	
	}
	
	/**
	 * 群组权限检查
	 */
	private function _checkPermission(){
		$gid = intval( $this->input->get_post( 'gid' ) );
		$this->isEmpty($gid);
		$this->groupInfo = $this->group->getGroup( $gid );
		$this->members = $this->member->getAllMemberIdsByGroup($gid);
		if ( empty( $this->groupInfo ) ) {
			$this->showMessage( 'Group is not exist!', ErrorCode::CODE_GROUP_NOT_EXIST , array(), mk_url('main/index/main'));
		} elseif ( $this->uid != $this->groupInfo['creator'] ) {
			$this->showMessage( 'You are not the group master!', ErrorCode::CODE_GROUP_NO_PERMISSION , array(), mk_url('main/index/main'));
		} elseif ( !in_array($this->uid, $this->members) ) {
			$this->showMessage( 'You are not in the group!', ErrorCode::CODE_GROUP_NO_PERMISSION , array(), mk_url('main/index/main'));
		}
	}
	
	/**
	 * 参数非空检查
	 * @param max $param
	 */
	private function isEmpty( $param )
	{
		if ( empty( $param ) ) {
			$this->showMessage( 'Invalid post!', ErrorCode::CODE_INVALID_POST );
		}
	}
	
	/**
	 * 群组应用列表
	 */
	public function groupApps()
	{
		$data=array(
			'group' => $this->groupInfo,
			'apps'  => $this->app->getGroupApps($this->groupInfo['gid']),
		);
		$this->view('groupApps', $data);
	}
	
	/**
	 * 群组可添加的应用列表
	 */
	public function apps()
	{
		$data=array(
			'group' => $this->groupInfo,
			'apps'  => $this->app->getInstallApps($this->groupInfo['gid']),
		);
		$this->view('apps', $data);
	}
	
	/**
	 * 添加应用
	 */
	public function add()
	{
		$gid = intval($this->input->get('gid', true));
		$appId = intval($this->input->get('appid', true));
		$this->isEmpty($gid);
		$this->isEmpty($appId);
		
		$this->app->install($gid, $this->uid, $appId);
		$this->redirect('group/index/detail', array('gid'=>$gid));
	}
	
	/**
	 * 删除应用
	 */
	public function delete()
	{
		$gid = intval($this->input->get('gid', true));
		$appId = intval($this->input->get('appid', true));
		$this->isEmpty($gid);
		$this->isEmpty($appId);
		
		$this->app->unstall($gid, $this->uid, $appId);
		$this->redirect('group/index/detail', array('gid'=>$gid));
	}
}