<?php
class SettingModel extends MY_Model {

	function __construct() {
		parent::__construct();
		$this->init_redis('user');
	}

	//获取一条重置邮箱的设置记录
	public function getSetting($dkcore){
		return false;
		$dkcore = intval($dkcore);
		if($dkcore < 1) return array();
		$sql = "SELECT `sendtime`,`updateemail` FROM `user_setting` WHERE `dkcode` = '".$dkcore."'";
		$result = $this->db->query($sql);
		$result = $result->row_array();
		return $result;
	}

	//取消发送邮件
	public function cancelEmail($dkcore){
		return false;
		$dkcore = intval($dkcore);
		if($dkcore < 1) return 0;
		$params = array('sendtime' => 0,'updateemail' => '');
		$result = $this->db->update('user_setting' , $params, array('dkcode' => $dkcore));
		return $result;
	}

	//修改邮箱
	public function modEmail($dkcore, $email, $time){
		return false;
		$dkcore = intval($dkcore);
		if($dkcore < 1) return '0';
		$updatetime = $time;
		//$params = array('sendtime' => $updatetime);
		$sql = "INSERT INTO `user_setting` (`dkcode`, `sendtime` ,`updateemail`) VALUES ('".$dkcore."', '".$updatetime."', '".$email."') ON DUPLICATE KEY UPDATE `sendtime` = '".$updatetime."' ,`updateemail` = '".$email."'";
		//$result = $this->db->insert('user_setting' , $params);
		$result = $this->db->query($sql);
		$result = $this->db->affected_rows();
		
		if($result > 0){
			return '1';
		}
		return '0';
	}

	//重新发送邮件更新时间
	public function modSendtime($dkcore, $time){
		return false;
		$dkcore = intval($dkcore);
		$time   = intval($time);
		if($dkcore < 1 or $time < 1) return 0;
		$params = array('sendtime' => $time);
		$result = $this->db->update('user_setting' , $params, array('dkcode' => $dkcore));
		return $result;
	}
	
	//同步mydql数据到redis
	public function tongbu(){
		return false;
		$sql = "select uid as id ,username as name,dkcode,sex from user_info where uid = '1000002886'";
		$result = $this->db->query($sql);
		$result = $result->result_array();
		foreach($result as $k=>$v){
			$this->redis->hMset('user:'.$v['id'].'', $v);
		}
		return 1;
		//$this->redis->del('user:0');
		//$this->redis->set('aaaa:0','aaaaaaaaa');
	}
}
?>