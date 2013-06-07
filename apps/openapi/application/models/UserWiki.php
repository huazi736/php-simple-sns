<?php
/*
 * user service class
 * Athor:heqq
 * date:2012/8/7
 * �˿���OPENAPI�ӿڹ淶��������
 * 
 * */

class UserWikiModel extends CI_Model {
	
	 var $baseresult;	//���ػ���ֵcode,text

	 public function __construct()
	 {
	 	parent::__construct();
	 	$this -> load -> database();
				
	 }

	 //��ȡָ���û���ѧУ��Ϣ
	 public function getEduInfo($uid) {
	 	$uid = intval($uid);
	 	if (!is_numeric($uid)) return "";
	 	$map = array('2' => 'Сѧ', '3' => '����', '4' => '����', '5' => 'ר��', '6' => '����', '7' => '�о���', '8' => '˶ʿ', '9' => '��ʿ');
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
	 		$item['edu'] = '��ѧ';
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

		if(filter_var($json['dkcode'],FILTER_VALIDATE_EMAIL)) //�ж��Ƿ�����Ч�ĵ����ʼ�
		{
			$auth_sql = "select email,dkcode,status,editpwdtime from user_auth where email='$dkcode' and passwd='$passwd'";//��֤��SQL���
			$info_sql ="select username,email,dkcode from user_info where email='$dkcode'";//��ѯ���ݵ�SQL���
		}
		else
		{
			$auth_sql = "select email,dkcode,status,editpwdtime from user_auth where dkcode=$dkcode and passwd='$passwd'";
			$info_sql ="select username,email,dkcode from user_info where dkcode=$dkcode";
		}

	 	//���ݶ˿ںź�������û�������֤
	 	$result_auth = $this -> db -> query($auth_sql) -> result_array();

		if($result_auth == NULL)
		{
			$this->baseresult['code'] = -100;
			$this->baseresult['text'] = "�û���������ʧ��";
			return json_encode($this->baseresult);
		}

		if($result_auth[0]['status'] == 0)
		{
			$this->baseresult['code'] = -102;
			$this->baseresult['text']= "���ʻ��Ѿ�����";
			return json_encode($this->baseresult);
		}
	 	
	 	
	 	$result_info = $this->db->query($info_sql)->result_array();
		$this->baseresult['code'] = 1;
		$this->baseresult['text'] = "��ѯ�ɹ�";
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