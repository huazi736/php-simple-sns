<?php
require_once  EXTEND_PATH . 'vendor/HessianPHP/HessianClient.php';
class Immodel {
	var $proxy;
	var $serviceurl,$ci;
	function __construct() {
		 $this->ci = get_instance();
		$this->ci->load->config('im');
		$this->serviceurl = config_item('im_java_serverurl');
		try{
			$this->proxy = new HessianClient($this->serviceurl);
		}catch(HessianException $e){
            return ;
		}catch(Exception $e){
			return ;
		}

	}
	/**
	 * IM添加好友，通知
	 * @date 2012-04-05
	 * @access public
	 * @param $myinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 * @param $herinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 */
	function addImFriend($myinfo = '',$herinfo = ''){
		try{
			$this->proxy->updateFriends($myinfo, $herinfo, 1, 1);
		}catch(Exception $e){
			return ;

		}
		return ;
	}
	/**
	 * IM删除好友，通知
	 * @date 2012-04-05
	 * @access public
	 * @param $myinfo    json格式array('uid'=>'1000001033','username'=>'童小勇')
	 * @param $herinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 */
	function delImFriend($myinfo = '',$herinfo = ''){
		try{
			$this->proxy->updateFriends($myinfo, $herinfo, 2, 1);
		}catch(Exception $e){
			return ;

		}
		return ;
	}
	/**
	 * IM添加关注，通知
	 * @date 2012-04-05
	 * @access public
	 * @param $myinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 * @param $herinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 */
	function addImFollow($myinfo = '',$herinfo = ''){
		try{
			$this->proxy->updateFollow($myinfo, $herinfo, 1, 1);
		}catch(Exception $e){
			return ;

		}
		return ;
	}
	/**
	 * IM删除关注，通知
	 * @date 2012-04-05
	 * @access public
	 * @param $myinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 * @param $herinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 */
	function delImFollow($myinfo = '',$herinfo = ''){
		try{
			$this->proxy->updateFollow($myinfo, $herinfo, 2, 1);
		}catch(Exception $e){
			return ;

		}
		return ;
	}
	/**
	 * IM用户名改变，通知
	 * @date 2012-04-05
	 * @access public
	 * @param $myinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 * @param $herinfo json格式array('uid'=>'1000001033','username'=>'童小勇')
	 */
	function chgImUserName(){
		try{
			$this->proxy = new HessianClient(str_replace('roastUpdate','nameUpdate',$this->serviceurl));
			$this->proxy->updateUsername($myinfo, $herinfo, 2, 1);
		}catch(Exception $e){
			return ;

		}
		return ;
	}
}