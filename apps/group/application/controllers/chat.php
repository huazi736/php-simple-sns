<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 群组
 * title :
 * Created on 2012-06-16
 * @author hexin
 * discription : 群组与IM对接的控制逻辑
 */
class Chat extends MY_Controller 
{
	public function __construct(){
		parent::__construct();
		//$this->output->enable_profiler(TRUE);
		$this->load->model('groupchatmodel', 'groupChat');
	}
	
	/**
	 * 新建一个IM群组
	 */
	public function create()
	{
		$gid = intval( $this->input->post( 'gid', true ) );
		$result = 1;
		try {
			$result = $this->groupChat->createRoom( $gid );
		} catch ( Exception $e ) {
			$this->showMessage( $e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION );
			die();
		}
		$result = json_decode( $result, true );
		if ( $result ['code'] <= 0 ) {
			$this->showMessage( "IM operate failed!", ErrorCode::CODE_IM_OPERATE_FAILED );
		} else {
			$this->load->model( 'groupmodel', 'group' );
			$this->group->updateExtend( $gid, GroupConst::GROUP_CHAT_ENABLED );
			$this->showMessage( "Success!", ErrorCode::CODE_SUCCESS );
		}
	}
	
	/**
	 * 新建一个IM子群
	 */
	public function createsub()
	{
		$sid = intval( $this->input->get( 'gid', true ) );
	}
	
	/**
	 * 向IM群中添加用户
	 */
	public function addMember()
	{
		$gid = intval($this->input->get('gid', true));
		$uid = intval($this->input->get('uid', true));
		$result = 1;
		try {
			$result = $this->groupChat->addMember($gid, $uid);
		} catch(Exception $e) {
			$this->showMessage($e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION);
			die();
		}
		if($result < 0) $this->showMessage("IM operate failed!", ErrorCode::CODE_IM_OPERATE_FAILED, array(), mk_url('group/index/detail', array('gid'=>$gid)));
		else $this->showMessage("Success!", ErrorCode::CODE_SUCCESS, array(), mk_url('group/index/detail', array('gid'=>$gid)), 3);
	}
	
	public function delMember()
	{
		$gid = intval($this->input->get('gid', true));
		$uid = intval($this->input->get('uid', true));
		$result = 1;
		try {
			$result = $this->groupChat->delMember($gid, $uid);
		} catch(Exception $e) {
			$this->showMessage($e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION);
			die();
		}
		if($result < 0) $this->showMessage("IM operate failed!", ErrorCode::CODE_IM_OPERATE_FAILED, array(), mk_url('group/index/detail', array('gid'=>$gid)));
		else $this->showMessage("Success!", ErrorCode::CODE_SUCCESS, array(), mk_url('group/index/detail', array('gid'=>$gid)), 3);
	}
	
	public function destroy()
	{
		$gid = intval($this->input->get('gid', true));
		$result = 1;
		try {
			$result = $this->groupChat->destroyRoom($gid);
		} catch(Exception $e) {
			$this->showMessage($e->getMessage(), ErrorCode::CODE_IM_CONNECTION_EXCEPTION);
			die();
		}
		if($result < 0) $this->showMessage("IM operate failed!", ErrorCode::CODE_IM_OPERATE_FAILED, array(), mk_url('group/index/detail', array('gid'=>$gid)));
		else{
			$this->load->model('groupmodel', 'group');
			$this->group->updateExtend($gid, GroupConst::GROUP_CHAT_CLOSED);
			$this->showMessage("Success!", ErrorCode::CODE_SUCCESS, array(), mk_url('group/index/detail', array('gid'=>$gid)), 3);
		}
	}
}