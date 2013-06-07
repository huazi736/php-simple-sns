<?php

class Credit extends DK_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	public function index()
	{
		$userinfo = array(
				'uid'=>$this->uid,
				'username'=>$this->username,
				'action_username' => $this->username,
				'action_uid'      =>$this->uid,
				'avatar' => get_avatar($this->uid, 's'),
				'url' => mk_url('main/index/main', array('dkcode' => $this->dkcode)),
				'uavatar' => get_avatar($this->uid, 's'),
		);
		
		$this->assign('creditDetail', service('credit')->getInfo($this->uid));
		$this->assign('userinfo',$userinfo);
		$this->display('credits/index');
	}
	
	public function getRankingList()
	{
		$this->load->model('creditmodel');
		$this->ajaxReturn($this->creditmodel->getRankingList($this->uid));
	}
	
	public function getCreditInfo()
	{
		$this->load->model('creditmodel');
		$this->ajaxReturn($this->creditmodel->getCreditDetail($this->uid), '', 1);
	}
	
	public function faq()
	{
		//获取用户信息
		$userinfo = array(
				'uid'=>$this->uid,
				'username'=>$this->username,
				'action_username' => $this->username,
				'action_uid'      =>$this->uid,
				'avatar' => get_avatar($this->uid, 's'),
				'url' => mk_url('main/index/main', array('dkcode' => $this->dkcode)),
				'uavatar' => get_avatar($this->uid, 's'),
		);
	
		$this->assign('userinfo',$userinfo);
		$this->display('credits/creditAsk');
	}
	
	public function intro()
	{
		//获取用户信息
		$userinfo = array(
				'uid'=>$this->uid,
				'username'=>$this->username,
				'action_username' => $this->username,
				'action_uid'      =>$this->uid,
				'avatar' => get_avatar($this->uid, 's'),
				'url' => mk_url('main/index/main', array('dkcode' => $this->dkcode)),
				'uavatar' => get_avatar($this->uid, 's'),
		);
	
		$this->assign('userinfo',$userinfo);
		$this->display('credits/creditsIntro');
	}
}
