<?php
class Im extends MY_Controller {
	function __construct() {
		error_reporting(0);
		parent::__construct();
		$my_info = $this->user;
		$this->username = $my_info['username'];
	}
	/**
	 * IM获取当前用户信息
	 * @date 2012-04-05
	 * @access public
	 * @param
	 */
	function getCurUser(){
		$this->ajaxReturn($data=array('uid'=>$this->uid,'username'=>$this->username), $info = '', $status = 1, $type = 'jsonp');
		//die(json_encode(array('uid'=>$this->uid,'username'=>$this->username)));
	}

	/**
	 * IM首页信息
	 * @date 2012-04-05
	 * @access public
	 * @param
	 */
	function index(){
		$this->display("im/im.html");
	}

}