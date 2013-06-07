<?php

/**
 * 用户认证服务接口
 * 
 * @author lvxinxin
 */
class PassportModel extends DkModel {

    private $crypt_key = 'duankou';

    public function __initialize() {
        $this->init_db('user');
    }

    /**
     * 检查用户是否登录
     * 
     * @return array|bool 如果已经登录返回用户基本信息，否则返回FALSE
     */
    public function checkLogin() {
        if (isset($_SESSION['uid']) && isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        return false;
    }

    /**
     * 用户登录
     */
    public function loginLocal($identifier, $password, $is_remember_me = false) {
        if (($dkcode = $this->checkUserAuth($identifier, $password)) != false) {
            $user = $this->db->from('user_info')->where(array('dkcode' => $dkcode['dkcode']))->get()->row_array();
            $user['status'] = $dkcode['status'];

            $_SESSION['uid'] = $user['uid'];
            $_SESSION['user'] = $user;
            return $user;
        }
        return false;
    }

    /**
     * 注销用户登录
     */
    public function logoutLocal() {
        unset($_SESSION['uid']);
        unset($_SESSION['user']);
    }

    /**
     * 用户注册
     */
    public function saveRegister($userdata) {
        if (empty($userdata['dkcode']) || empty($userdata['username']) || empty($userdata['email']) || empty($userdata['passwd']) || empty($userdata['sex'])) {
            return array('status' => 0, 'msg' => '信息不完整');
        }
        if (isset($userdata)) {
            $passwd = $userdata['passwd'];
            unset($userdata['passwd']);
        }
        //$user = D('User')->getUserFieldInfo($userdata['dkcode'], 'dkcode',array('uid','email','dkcode','isactive'));
        $user = $this->db->from('user_info')->where(array('dkcode' => $userdata['dkcode']))->select('uid,email,dkcode,isactive')->get()->row_array();
		
        if ($user) {
            if ($user['isactive']) {
                return array('status' => 0, 'msg' => '邀请码已被注册');
            } else {
                // $res = D('User')->saveUserInfo($userdata, $userdata['dkcode']);
                $res = $this->db->update('user_info', $userdata, array('dkcode' => $userdata['dkcode']));
                if (!$res) {
                    return array('status' => 0, 'msg' => '注册失败');
                }
                /* $other = D('User')->getUserFieldInfo($userdata['email'], 'email',array('uid','email','dkcode','isactive'));
                  if(!$other || $other['dkcode']==$userdata['dkcode'])
                  {

                  }
                  else
                  {
                  return array('status' => 3, 'msg' => '邮箱已被注册');
                  } */
            }
        } else {
            // $other = D('User')->getUserFieldInfo($userdata['email'], 'email',array('uid','email','dkcode','isactive'));
            $other = $this->db->from('user_info')->where(array('email' => $userdata['email']))->select('uid,email,dkcode,isactive')->get()->row_array();
            if (!empty($other)) {
                return array('status' => 0, 'msg' => '邮箱已被注册');
            }
            // $res = D('User')->addUserInfo($userdata);			
            $res = $this->db->insert('user_info', $userdata);
            if (!$res) {
                return array('status' => 0, 'msg' => '注册失败');
            }
        }


        $str = $userdata['email'] . "\t" . $userdata['dkcode'] . "\t" . $passwd . "\t" . $userdata['regdate'] . "\t" . $userdata['username'];
        $crypt_code = $this->cryptEnOrDe($str);
        if (!empty($crypt_code)) {
			// service('Mail')->sendEmail('sunlufu@duankou.com','test','注册激活',4,mk_url('front/register/do_active_userinfos',array('active_code'=>$crypt_code)));
            service('Mail')->sendEmail($userdata['email'],$userdata['username'],'注册激活',4,mk_url('front/register/do_active_userinfos',array('active_code'=>$crypt_code)));
			return array('status' => 1, 'msg' => $crypt_code);
            //return array('status' => 1, 'msg' => $str);
        }
        return array('status' => 0, 'msg' => '注册失败!');
    }

    /**
     * 重置用户密码
     */
    public function resetUserPassword($identifier, $password) {
        if (empty($identifier)) {
            return false;
        }
        $identifier_type = $this->isEmail($identifier) ? 'email' : 'dkcode';
        $pass = array(
            'passwd' => $this->pwd_crypt($password),
        );
        $this->db->where($identifier_type, $identifier);
        $result = $this->db->update('user_auth', $pass);
        //$result = $this->db->from('user_auth')->where(array($identifier_type=>$identifier))->update($pass);
        $time = array(
            'lastupdatepwdtime' => time(),
        );
        $this->db->where($identifier_type, $identifier);
        $this->db->update('user_info', $time);
        //$this->db->from('user_info')->where(array($identifier_type=>$identifier))->update($time);
        //$result = D('User')->resetPass($identifier, $this->pwd_crypt($password), $identifier_type);
        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * 激活用户
     */
    public function activeUser($verifycode) {
        if (empty($verifycode)) {
            return array('status' => 2, 'msg' => '验证字符串无效');
        }
        $active_code = $this->cryptEnOrDe($verifycode, false);
        $data = explode("\t", $active_code);
		if($data[0] == $active_code) return array('status'=>2,'msg'=>'激活链接非法');
        $array = array('email', 'dkcode', 'passwd', 'time', 'username');
        $n_data = array_combine($array, $data);
        $validate = $n_data['time'] + 86400 * 7;
        $n_data['passwd'] = $this->pwd_crypt($n_data['passwd']);
        $isactive = $this->db->from('user_info')->where(array('email' => $n_data['email']))->select('isactive')->get()->row_array();
        // $isactive = D('User')->getActiveByIdentifier($n_data['email'],'email');
        if (empty($isactive)) {
            return array('status' => 0, 'msg' => '激活链接失效');
        }
        if ($isactive['isactive'] == 1) {
            return array('status' => 0, 'msg' => '该邮箱已经被使用');
        }
        if (intval($validate) <= time()) {
            return array('status' => 3, 'msg' => '激活超时'); //超时
        }
        // $user = D('User')->getUserFieldInfo($n_data['dkcode'], 'dkcode');
        $user = $this->db->from('user_info')->where(array('dkcode' => $n_data['dkcode']))->get()->row_array();
        if (empty($user)) {
            return array('status' => 0, 'msg' => '用户不存在'); //验证失败 
        }
        if (isset($n_data['time']))
            unset($n_data['time']);
        if (isset($n_data['username']))
            unset($n_data['username']);
        $n_data['status'] = 1;
		// log_user_msg('lxx',array('info'=>'执行事务'));
        if (!$this->updateUser($n_data)) {
            return array('status' => 0, 'msg' => '失败'); //成功
        }
        $user = array_merge($user, $n_data);

        $_SESSION['user'] = $user;
        $_SESSION['uid'] = $user['uid'];
        // file_put_contents('session.txt',var_export($_SESSION,true));
        return true;
    }

    /**
     * 事务执行更新用户
     */
    private function updateUser($user) {
        if (!is_array($user)) {
            return false;
        }
        //$this->startTrans();
        $this->db->trans_begin();
        // $this->db->set('isactive', 1);
        $this->db->update('user_info',array('isactive'=>1),array('dkcode'=>$user['dkcode']));
        $this->db->insert('user_auth', $user);
        // $this->db->set('usedateline', time());
        // $this->db->set('status', 1);
        $this->db->update('invite_record',array('usedateline'=>time(),'status'=>'1'),array('code'=>$user['dkcode']));
        //激活成功之后，更新用户，更新邀请人的邀请成功数(invite_user_nums)和剩余邀请次数(invite_count)
        $invite_uid = $this->db->from('invite_record')->where(array('code' => $user['dkcode']))->select('invite_uid')->get()->row_array();
        $sql = 'update user_code_nums set invite_user_nums = invite_user_nums + 1 , invite_count = invite_count + 1 where uid = ' . $invite_uid['invite_uid'];
        $this->db->query($sql);
        if ($this->db->trans_status() === FALSE) {
			log_user_msg('',array('info'=>'回滚事务','data'=>$user),'','register');
            $this->db->trans_rollback();
            return false;
        } else {
			log_user_msg('',array('info'=>'提交事务','data'=>$user),'','register');
            $this->db->trans_commit();
            return true;
        }
    }

    /**
     * 检查用户认证
     */
    public function checkUserAuth($identifier, $password) {
        $identifier_type = $this->isEmail($identifier) == true ? 'email' : 'dkcode';
        $password = $this->pwd_crypt($password);
        $user = $this->db->from('user_auth')->where(array($identifier_type => $identifier, 'passwd' => $password))->select('email,dkcode,status')->get()->row_array();
        //$user = $this->db->from('user_auth')->where(array($identifier_type=>$identifier))->select('email,dkcode')->get()->row_array();
        return ($user && isset($user['dkcode'])) ? $user : false;
    }

    /**
     * 密码加密算法
     */
    private function pwd_crypt($pwd) {
        $pwd = round(strlen($pwd) / 4) . $pwd . round(strlen($pwd) / 6);
        $pwd = md5(hash('sha256', $pwd));
        return $pwd;
    }

    /**
     * 验证邮箱地址是否合法
     */
    private function isEmail($user_email) {
        if (preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $user_email) && strlen($user_email) >= 6 && strlen($user_email) <= 64)
		{
			return true;
		}
		return false;
    }

    //检测用户是否存在
    public function checkUserIsExists($identifier) {
        if (empty($identifier)) {
            return false;
        }
        $identifier_type = $this->isEmail($identifier) ? 'email' : 'dkcode';
        $flag = $this->db->from('user_info')->where(array($identifier_type => $identifier))->select($identifier)->count_all_results();
        return $flag > 0 ? true : false;
    }

    //加密 or 解密
    private function cryptEnOrDe($value, $flag = true) {
        include_once(EXTEND_PATH . 'vendor/Crypt.php');
        $crypt = new Crypt();
        if ($flag) {
            return $crypt->encrypt($value, $this->crypt_key, true);
        } else {
            return $crypt->decrypt($value, $this->crypt_key, true);
        }
    }

    //获取加密串	
    public function getCrypt($str, $flag = true) {
        return $this->cryptEnOrDe($str, $flag);
    }

    //发送激活邮件
    public function sendActiveMail() {
        //
    }

    //发送修改密码邮件
    public function sendEditPassMail() {
        //
    }

    //是否设置密保
    public function isHasSecurity($dkcode) {
        $setting = $this->db->from('user_setting')->where(array('dkcode' => $dkcode))->get()->row_array();
        //$setting = D('User')->getUserSetting($dkcode);
        //return D('User')->getLastSql();
        if ($setting && isset($setting['security']) && !empty($setting['security']) && unserialize($setting['security']) !== false) {
            return $setting['security'];
        }
        return false;
    }

    //验证密保问题 
    public function verifyUserSecurity($dkcode, $data) {
        if (empty($dkcode) || empty($data))
            return false;
        $security = $this->isHasSecurity($dkcode);
        $security = unserialize($security);
        if ($security && is_array($security) && count($security) > 0) {

            return $data == $security ? true : false;
        }
        return false;
    }

    //设置密保
    public function setUserSecurity($dkcode, $info) {
        if (empty($dkcode) || empty($info))
            return false;
        $data['security'] = serialize($info);
        $setting = $this->db->from('user_setting')->where(array('dkcode' => $dkcode))->get()->row_array();
        if ($setting) {
            $this->db->where('dkcode', $dkcode);
            return $this->db->update('user_setting', $data);
        } else {
            $data['dkcode'] = $dkcode;
            return $this->db->insert('user_setting', $data);
        }
    }

    //获取用户密保
    public function getUserSecurity($dkcode) {
        $setting = $this->db->from('user_setting')->where(array('dkcode' => $dkcode))->get()->row_array();
        if ($setting && isset($setting['security']) && !empty($setting['security']) && unserialize($setting['security']) !== false) {
            return $setting['security'];
        }
        return false;
    }

    //搜索邮箱里的通讯录是否在本站注册过
    public function searchFriend($emailData) {
        if (empty($emailData)) {
            return false;
        }
        $sql = sprintf("SELECT uid,username,dkcode 
                        FROM user_info 
                        WHERE isactive = '1' 
                        AND LENGTH(email) > 0                      
                        AND email in (%s)", "'" . $emailData . "'");
        $list = $this->db->query($sql)->row_array();
        return $list;
    }

    //更换邮箱 
    public function changeEmail($now, $new, $dkcode) {
        if (empty($now) || empty($new) || empty($dkcode)) {
            return false;
        }
        $flag = $this->db->from('user_info')->where(array('email' => $new))->get()->row_array();
        //return $flag; //$this->getLastSql();
        if (!empty($flag)) {
            if (isset($flag['isactive']) && $flag['isactive'] == 1)
                return false;
            if (isset($flag['dkcode']) && $flag['dkcode'] != $dkcode)
                return false;
        }
        $f = $this->db->from('user_info')->where(array('email' => $now))->get()->row_array();
        //return $f;//$this->getLastSql();
        if (is_array($f)) {
            if (isset($f['isactive']) && $f['isactive'] == 1)
                return 3;
            if (isset($f['dkcode']) && $f['dkcode'] != $dkcode)
                return 3;
        }
        //$map['email'] = $now;  
        $email = array(
            'email' => $new,
        );
        $res = $this->db->update('user_info', $email, array('email' => $now, 'isactive' => 0));
        //$res = $this->table('user_info')->where(array('email'=>$now,'isactive'=>0))->setField('email',$new);
        //return $this->getLastSql();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    //获取测试数据,随机获取8条数据
    public function getTestEmail() {
        $sql = "select email from user_info order by rand() limit 12";
        return $this->db->query($sql)->result_array();
    }

    //修改session里的用户名
    public function setUsernameFromSessinon($sessionid, $val) {
        if (empty($val))
            return false;
        if (!empty($_SESSION['user'])) {
            $_SESSION['user'][0]['username'] = $val;
            return true;
        } else {
            return false;
        }
    }

    //检测邮件是否被使用
    public function checkEmail($email) {
        if (empty($email))
            return false;
        return $this->db->from('user_info')->where(array('email' => $email))->select('dkcode,email,isactive')->get()->row_array();
    }

    /**
     * 获取总用户数
     * lvxinxin 2012-07-11 add
     */
    public function get_user_counts() {
        $sql = 'select count(1) as ct from user_info where isactive = 1';
        // $res = D('User')->query($sql);
        $query = $this->db->query($sql);
        $res = $query->row_array();
        return $res['ct'];
    }

    /**
     * 获取用户数据
     * lvxinxin 2012-07-11
     */
    public function get_user_info($uid, $start, $limit) {
		if(is_array($uid))
		{
			$uids = implode(',',$uid);
		}
		else{
			$uids = $uid;
		}
        $sql = 'select uid,email,username,dkcode,sex from user_info where uid not in (' . $uids . ') AND isactive = 1 limit ' . $start . ',' . $limit;
        return $this->db->query($sql)->result_array();
    }

    /**
     * 设置修改密码状态
     *
     */
    public function set_edit_pwd_status($time, $dkcode) {
        $identifier_type = $this->isEmail($dkcode) ? 'email' : 'dkcode';
        return $this->db->update('user_auth', array('editpwdtime' => $time), array($identifier_type => $dkcode));
    }

    /**
     * 获取修改密码状态
     *
     */
    public function get_edit_pwd_status($dkcode) {
        if (empty($dkcode))
            return false;
        $res = $this->db->from('user_auth')->select('editpwdtime')->where(array('dkcode' => $dkcode))->get()->row_array();
        // file_put_contents('sql.txt',$this->db->last_query());
        return $res['editpwdtime'];
    }
	
	/**
	 *获取个人封面
	 *lvxinxin add 2012-07-18
	 */
	 public function get_cover($uid){
		$res = $this->db->from('user_info')->select('coverurl') ->where(array('uid'=>$uid))->get()->row_array();
		return @$res['coverurl'];
	 }
	/**
	  *测试接口，生成用户
	  *lvxinxin add
	  */
	  
	public function addUserReturnUid($info,$auth,$invite_uid){
		if(empty($info) || empty($auth)){
			return false;
		}
		$this->db->trans_begin();
		$ui = $this->db->insert('user_info',$info);//$this->table('user_info')->add($info);
		$uid = $this->db->insert_id();
		$ua = $this->db->insert('user_auth',$auth);//$this->table('user_auth')->add($auth);

		// $r = $this->table('invite_record')->where(array('code' => $user['dkcode']))->save(array('usedateline' => time(), 'status' => '1'));
		$r = $this->db->update('invite_record',array('usedateline' => time(), 'status' => '1'),array('code'=>$info['dkcode']));
		//激活成功之后，更新用户，更新邀请人的邀请成功数(invite_user_nums)和剩余邀请次数(invite_count)
		//$invite_uid = $this->getInviteUidByDkcode($user['dkcode']);
		$sql = 'update user_code_nums set invite_user_nums = invite_user_nums + 1 , invite_count = invite_count + 1 where uid = '.$invite_uid;
		$ucn = $this->db->query($sql);
		if($this->db->trans_status() === FALSE){
			$this->db->trans_rollback();
			return false;
		}
		else
		{
			$this->db->trans_commit();
			return $uid;
			
		}	
	}
	
	public function reSendEmail($nowEmail){
		$res = $this->db->from('user_info')->select('isactive')->where(array('email'=>$nowEmail,'isactive'=>1))->get()->row_array();
		if(!empty($res) && $res['isactive'] == 1) return 3;
	}
	
	/**
	 *批量生成脚本专用接口
	 */
	public function uidlist(){
		$sql = 'select uid from user_info where isactive = 1 ';
		return $this->db->query($sql)->result_array();
	}
}