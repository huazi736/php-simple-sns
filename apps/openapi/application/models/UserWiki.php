<?php
/*
 * user service class
 * Athor:heqq
 * date:2012/8/7
 * 端口网OPENAPI接口规范定义如下
 * 
 * */

class UserWikiModel extends CI_Model {
	
	 var $baseresult;	//返回基本值code,text

	 public function __construct()
	 {
	 	parent::__construct();
	 	$this -> load -> database();
				
	 }

	 //获取指定用户的学校信息
	 public function getEduInfo($uid) {
	 	$uid = intval($uid);
	 	if (!is_numeric($uid)) return "";
	 	$map = array('2' => '小学', '3' => '初中', '4' => '高中', '5' => '专科', '6' => '本科', '7' => '研究生', '8' => '硕士', '9' => '博士');
	 	$where = array('uid' => $uid);
	 	$field = array('uid', 'starttime', 'edulevel', 'schoolid', 'schoolname', 'department', 'department_id');
	 
	 	$field = implode(',', $field);
	 	$rs = $this->db->from('user_edu')->where($where)->order_by('edulevel', 'asc')->select($field)->get()->result_array();
	 
	 	$is = false;
	 	foreach($rs as $key => $val) {
	 		if ($val['edulevel'] <= 4) {
	 			$rs[$key]['edu'] = $map[$val['edulevel']];
	 		} else {
	 			unset($rs[$key]);
	 			$is = true;
	 		}
	 	}
	 	if($is) {
	 		$item = array();
	 		$item['uid'] = $uid;
	 		$item['edu'] = '大学';
	 		$rs[] = $item;
	 	}
	 	return $rs;
	 }
	 
	 
	 
	 public function userAuth($param = '')
	 {
	 	if($param == '')
	 		return -1;
		
		$json = json_decode($param,true);
		$dkcode = $json['dkcode'];
		$passwd = $json['passwd'];

		if(filter_var($json['dkcode'],FILTER_VALIDATE_EMAIL)) //判断是否是有效的电子邮件
		{
			$auth_sql = "select email,dkcode,status,editpwdtime from user_auth where email='$dkcode' and passwd='$passwd'";//验证的SQL语句
			$info_sql ="select username,email,dkcode from user_info where email='$dkcode'";//查询数据的SQL语句
		}
		else
		{
			$auth_sql = "select email,dkcode,status,editpwdtime from user_auth where dkcode=$dkcode and passwd='$passwd'";
			$info_sql ="select username,email,dkcode from user_info where dkcode=$dkcode";
		}

	 	//根据端口号和密码对用户进行认证
	 	$result_auth = $this -> db -> query($auth_sql) -> result_array();

		if($result_auth == NULL)
		{
			$this->baseresult['code'] = -100;
			$this->baseresult['text'] = "用户名或密码失败";
			return json_encode($this->baseresult);
		}

		if($result_auth[0]['status'] == 0)
		{
			$this->baseresult['code'] = -102;
			$this->baseresult['text']= "该帐户已经禁用";
			return json_encode($this->baseresult);
		}
	 	
	 	
	 	$result_info = $this->db->query($info_sql)->result_array();
		$this->baseresult['code'] = 1;
		$this->baseresult['text'] = "查询成功";
		$this->baseresult['result'] = $result_info;
	 	
	 	$result = json_encode($this->baseresult);
	 	return $result;
	 }
	 
	 public function getuserinfo2($param = '')
	 {
	 	if($param == '')
	 		return -1;
		$json = json_decode($param,true);
		
		$dkcode = $json['dkcode'];
		$basic = $json['basic'];

	 	$sql = "select * from user_info where email='$dkcode'";
		//return $sql;
	 	$result = $this->db->query($sql)->result_array();
	 	//$list = json_encode($result);
	 	return $result;
	 }
	 
	 public function getUserInfo($param = '')
	 {
	 	if($param == '')
	 		return -99;
	 	
	 	$json = json_decode($param,true);
	 	
	 	$dkcode = $json['dkcode'];
	 	$basic = $json['basic'];
	 	$education = $json['education'];
	 	$scope = $json['scope'];
	 }
	 
}