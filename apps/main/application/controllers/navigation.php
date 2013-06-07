<?php
class Navigation extends MY_Controller {
	
	public function index()
	{
		$this->user['avatar'] = get_avatar($this->uid);
		$this->assign('user', $this->user);
		$this->display('navigation/index');
	}
}