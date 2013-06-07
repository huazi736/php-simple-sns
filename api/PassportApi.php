<?php

class PassportApi extends DkApi {

    protected $passport;

    public function __initialize() {
        $this->passport = DKBase::import('Passport', 'passport');
    }

    /**
     * 检查用户是否登录
     * 
     * @return array|bool 如果已经登录返回用户基本信息，否则返回FALSE
     */
    public function checkLogin() {
        return $this->passport->checkLogin();
    }

    /**
     * 用户登录
     */
    public function loginLocal($identifier, $password, $is_remember_me = false) {
        return $this->passport->loginLocal($identifier, $password, $is_remember_me);
    }

    /**
     * 注销用户登录
     */
    public function logoutLocal() {
        return $this->passport->logoutLocal();
    }

    /**
     * 用户注册
     */
    public function saveRegister($userdata) {
        return $this->passport->saveRegister($userdata);
    }

    /**
     * 重置用户密码
     */
    public function resetUserPassword($identifier, $password) {
        return $this->passport->resetUserPassword($identifier, $password);
    }

    /**
     * 激活用户
     */
    public function activeUser($verifycode) {
        return $this->passport->activeUser($verifycode);
    }

    /**
     * 事务执行更新用户
     */
    private function updateUser($user) {
        return $this->passport->updateUser($user);
    }

    /**
     * 检查用户认证
     */
    public function checkUserAuth($identifier, $password) {
        return $this->passport->checkUserAuth($identifier, $password);
    }

    /**
     * 密码加密算法
     */
    private function pwd_crypt($pwd) {
        return $this->passport->pwd_crypt($pwd);
    }

    /**
     * 验证邮箱地址是否合法
     */
    private function isEmail($user_email) {
        return $this->passport->isEmail($user_email);
    }

    //检测用户是否存在
    public function checkUserIsExists($identifier) {
        return $this->passport->checkUserIsExists($identifier);
    }

    //加密 or 解密
    private function cryptEnOrDe($value, $flag = true) {
        return $this->passport->cryptEnOrDe($value, $flag);
    }

    //获取加密串	
    public function getCrypt($str, $flag = true) {
        return $this->passport->getCrypt($str, $flag);
    }

    //发送激活邮件
    public function sendActiveMail() {
        return $this->passport->sendActiveMail();
    }

    //发送修改密码邮件
    public function sendEditPassMail() {
        return $this->passport->sendEditPassMail();
    }

    //是否设置密保
    public function isHasSecurity($dkcode) {
        return $this->passport->isHasSecurity($dkcode);
    }

    //验证密保问题 
    public function verifyUserSecurity($dkcode, $data) {
        return $this->passport->verifyUserSecurity($dkcode, $data);
    }

    //设置密保
    public function setUserSecurity($dkcode, $data) {
        return $this->passport->setUserSecurity($dkcode, $data);
    }

    //获取用户密保
    public function getUserSecurity($dkcode) {
        return $this->passport->getUserSecurity($dkcode);
    }

    //搜索邮箱里的通讯录是否在本站注册过
    public function searchFriend($emailData) {
        return $this->passport->searchFriend($emailData);
    }

    //更换邮箱 
    public function changeEmail($now, $new, $dkcode) {
        return $this->passport->changeEmail($now, $new, $dkcode);
    }

    //获取测试数据,随机获取8条数据
    public function getTestEmail() {
        return $this->passport->getTestEmail();
    }

    //修改session里的用户名
    public function setUsernameFromSessinon($sessionid, $val) {
        return $this->passport->setUsernameFromSessinon($sessionid, $val);
    }

    //检测邮件是否被使用
    public function checkEmail($email) {
        return $this->passport->checkEmail($email);
    }

    //获取总用户数
    public function get_user_counts() {
        return $this->passport->get_user_counts();
    }

    /**
     * 获取用户数据
     * lvxinxin 2012-07-11
     */
    public function get_user_info($uid, $start, $limit) {
        return $this->passport->get_user_info($uid, $start, $limit);
    }

    /**
     * 设置修改密码状态
     *
     */
    public function set_edit_pwd_status($time, $dkcode) {
        return $this->passport->set_edit_pwd_status($time, $dkcode);
    }

    /**
     * 获取修改密码状态
     *
     */
    public function get_edit_pwd_status($dkcode) {
        return $this->passport->get_edit_pwd_status($dkcode);
    }
	/**
	 *获取个人封面
	 *lvxinxin add 2012-07-18
	 */
	 public function get_cover($uid){
		if(empty($uid)) return false;
		return $this->passport->get_cover($uid);
	 }
	 
	 public function addUserReturnUid($info,$auth,$invite_uid){
		if(empty($info) || empty($auth) || empty($invite_uid)){
			return false;
		}
		return $this->passport->addUserReturnUid($info,$auth,$invite_uid);
	 }
	 
	 public function reSendEmail($nowEmail){
		if(empty($nowEmail)) return false;
		return $this->passport->reSendEmail($nowEmail);
	 }
}