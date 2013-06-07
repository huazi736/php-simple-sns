<?php
/*
 * user service class
 * Athor:xuxuefeng
 * date:2012/7/5
 * 端口网OPENAPI接口规范定义如下
 * -1参数为空
 * -2异常错误
 * 
 * */

class OpenApi_UserinfoModel extends MY_Model {
	
	 private $baseresult;	//返回基本值code,text


	 public function userAuth($param = '')
	 {
	 	if($param == '')
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));
		
		$json = json_decode($param,true);
		$dkcode = $json['dkcode'];
		$passwd = $json['password'];
		//php后端的加密运算
		$pwd = round(strlen($passwd) / 4) . $passwd . round(strlen($passwd) / 6);
		$passwd = md5(hash('sha256', $pwd));
		
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
	 	$result_auth = $this -> db -> query($auth_sql) -> row_array();

		if($result_auth == NULL)
		{
			$this->baseresult['code'] = -100;
			$this->baseresult['text'] = "用户名或密码失败";
			return json_encode($this->baseresult);
		}

		if($result_auth['status'] == 0)
		{
			$this->baseresult['code'] = -102;
			$this->baseresult['text']= "该帐户已经禁用";
			return json_encode($this->baseresult);
		}
	 	
	 	
	 	$result_info = $this->db->query($info_sql)->row_array();
		$this->baseresult['code'] = 1;
		$this->baseresult['text'] = "查询成功";
		$this->baseresult['result'] = $result_info;
	 	
	 	$result = json_encode($this->baseresult);
	 	return $result;
	 }
	/**
	 * 获取单个用户信息
	 * @param unknown_type $param
	 * @return string
	 */
	 
	 public function getUserInfo($param = '')
	 {
	 	//验证传入参数的正确性
	 	if($param == '')
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));

	 	$json = json_decode($param,true);

	 	$dkcode = $json['dkcode'];
	 	$basic = $json['basic'];
	 	$education = $json['education'];
	 	$scope = $json['scope']?explode(',',$json['scope']):Null;
	 	if(!$scope)
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));
	 	
	 	if(filter_var($dkcode,FILTER_VALIDATE_EMAIL)) //判断是否是有效的电子邮件
		{
			$whereField = 'email';
		}
		else
		{
			$whereField = 'dkcode';
		}
		//sql查询用户信息
		$info_sql = 'SELECT uid,dkcode,email,username,sex,birthday,introduction FROM user_info WHERE '.$whereField." = '" . $dkcode."'";

		$result_info = $this->db->query($info_sql)->row_array();
		
		if((!$result_info)||!is_array($result_info)){
			
			return json_encode(array('code'=>-100,'text'=>'用户不存在','result'=>NULL));
		}
		
		
		//获取返回值中所涉及到的各模块的权限
		$permissonRes = service('UserWiki')->getPermissonByModule($result_info['uid'],array('base','edu','private'));
		//return json_encode($permissonRes);
		if(empty($permissonRes)){

			$permissons['base'] = 1;
			$permissons['edu'] = 4;
			$permissons['private'] = 1;
			
		}else{
			
			foreach(array('base'=>1,'edu'=>4,'private'=>1) as $k => $v){
	          if($permissonRes[0][$k]){
	    	   $arr1 = json_decode($permissonRes[0][$k],true);
	    	   $permissons[$k] = $arr1['type'];
	           }else{
	    	   $permissons[$k] = $v;
	           }
			}
	    
		}
	    
	    //需要返回的各个参数值
		$paraResult['dkcode'] = $result_info['dkcode'];
		$paraResult['email'] = $result_info['email'];
		$paraResult['username'] = $result_info['username'];
	    $paraResult['userImg'] = get_avatar($result_info['uid'],'mm');
	    if(in_array($permissons['private'],$scope)){
	    $paraResult['selfIntro'] = $result_info['introduction'];
	    }else{
	    $paraResult['selfIntro'] = Null;
	    }
	   //判断用户基本信息权限,获取用户基本信息
	   if($basic && in_array($permissons['base'],$scope) ){
	   $userBasicInfo['birthday'] = $result_info['birthday']?date("Y-m-d",$result_info['birthday']):Null;
	   $userBasicInfo['sex'] = $result_info['sex'];
	   $userBasicInfo['scope'] = $permissons['base'];
	   }
	   else
	   {
	   $userBasicInfo=Null;
	   }
	   
	   $paraResult['userBasicInfo'] = $userBasicInfo;
	   
	   //判断可见权限，获取用户教育经历
	   if($education && in_array($permissons['edu'],$scope) ){
	   	//将array(1=>array(..),2=>array(...))转化成array(array(...),array(....))的格式，以方便java组调用转化
	   	$eduArr = service('UserWiki')->getEduInfo($result_info['uid']);
	   	foreach($eduArr as $v){
	   		if(isset($v['starttime'])){
	   		$v['starttime'] = (int)$v['starttime']*1000;
	   		}
	   		
	   		$paraResult['userEducation'][]=$v;
	   		
	   	}
	  
	   }
	   else
	   {
	   	$paraResult['userEducation']= Null;
	   }
	   
	   return	json_encode(array('code'=>1,'text'=>'调用成功','result'=>$paraResult));
	 	
	   
	 }
	 
	 public function getUserInfoBatch($param=''){
	 	
	 	//验证传入参数的正确性
	 	if($param == '')
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));
	 	
	 	$json = json_decode($param,true);
	 	
	 	$dkcode = $json['dkcode']?explode(',',$json['dkcode'],21):Null;
	 	$scope = $json['scope']?explode(',',$json['scope']):Null;
	 	if(!$scope||!$dkcode){
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));
	 	}
	 	//只选择前20个数组value
	 	if(count($dkcode) == 21){
	 		array_pop($dkcode);
	 	}
	 	
	 	//sql查询用户信息
	 	$info_sql = 'SELECT uid,dkcode,email,username,sex,birthday,introduction FROM user_info WHERE dkcode in (' . "'". implode("','",$dkcode)."') ";
	 	
	 	$result_info = $this->db->query($info_sql)->result_array();
	 	
	 	if((!$result_info)||!is_array($result_info)){
	 			
	 		return json_encode(array('code'=>-100,'text'=>'用户不存在','result'=>NULL));
	 	}
	 	//获取各个用户的uid
	 	$uidArr=array();
	 	foreach($result_info as $v){
	 		$uidArr[]=$v['uid'];
	 	}
	 	
	 	//获取返回值中所涉用户的base模块的权限
	 	$permissonRes = service('UserWiki')->getPermissonByModule($uidArr,'base');
	 	
	 	$permissonArr = array();
	 	if($permissonRes){
	 	foreach($permissonRes as $v){
	 		if($v['base']){
	 			$basescopearr = json_decode($v['base'],true);
	 			$basescope = $basescopearr['type'];
	 		}else{
	 			$basescope = 1;
	 		}
	 	
	 		$permissonArr[$v['object_id']] = $basescope;
	 	}
	 	//新的用户权限数组
	 	$permissons = array();
	 	foreach($uidArr as $v){
	 		if(isset($permissonArr[$v])){
	 			$permissons[$v] = $permissonArr[$v];
	 		}else{
	 			$permissons[$v] = 1;
	 		}
	 	}
	 	}


	 	foreach($result_info as $v){
	 		//需要返回的各个参数值
	 		$paraResult[$v['uid']]['dkcode'] = $v['dkcode'];
	 		$paraResult[$v['uid']]['email'] = $v['email'];
	 		$paraResult[$v['uid']]['username'] = $v['username'];
	 		$paraResult[$v['uid']]['userImg'] = get_avatar($v['uid'],'mm');
	 		//$paraResult[$v['uid']]['selfIntro'] = $v['introduction'];
	 		 
	 		//判断用户基本信息权限,获取用户基本信息
	 		
	 		if(isset($permissons[$v['uid']])&&in_array($permissons[$v['uid']],$scope) ){
	 			$userBasicInfo['birthday'] = $v['birthday']?date("Y-m-d",$v['birthday']):Null;
	 			$userBasicInfo['sex'] = $v['sex'];
	 			$userBasicInfo['scope'] = $permissons[$v['uid']];
	 		}
	 		else
	 		{
	 			$userBasicInfo=Null;
	 		}
	 		
	 		$paraResult[$v['uid']]['userBasicInfo'] = $userBasicInfo;
	 	}
	 	//转化成java易调用的数据格式
	 	$convertParaResult=array();
	 	foreach($paraResult as $v){
	 		$convertParaResult[]=$v;
	 	}
	 	
	 	return json_encode(array('code'=>1,'text'=>'调用成功','result'=>$convertParaResult));
	 
	 }
	 
	 /**
	  *获取两个用户之间的关系
	  * @param string $param
	  * @return string
	  */
	 
	 public function getRelationForTwoUser($param=''){
	 	//验证传入参数的正确性
	 	if($param == '')
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));
	 	 
	 	$json = json_decode($param,true);
	 	 
	 	$dkcode1 = $json['dkcode1'];
	 	$dkcode2 = $json['dkcode2'];
	 
	 	//执行相应sql语句
	 	$whereField1 = filter_var($dkcode1,FILTER_VALIDATE_EMAIL)?'email':'dkcode'; //判断是否是有效的电子邮件
	 	$whereField2 = filter_var($dkcode2,FILTER_VALIDATE_EMAIL)?'email':'dkcode';
	 	 //获取两用户的uid
	 	$sql = 'SELECT uid FROM user_info WHERE '.$whereField1." = '".$dkcode1."'";
	 	$result_info1 = $this->db->query($sql)->row_array();
	 	
	 	$sql = 'SELECT uid FROM user_info WHERE '.$whereField2." = '".$dkcode2."'";
	 	$result_info2 = $this->db->query($sql)->row_array();
	 	
	 	//验证是否两用户都存在
	 	if(!isset($result_info1['uid']) || !isset($result_info2['uid'])){
	 		return json_encode(array('code'=>-100,'text'=>'用户不存在','result'=>NULL));
	 	}
	 	//调用api获取两用户之间的关系
	 	$paramResult = api('Relation')->getRelationStatus($result_info1['uid'],$result_info2['uid']);
	 
	 	return json_encode(array('code'=>1,'text'=>'调用成功','result'=>$paramResult));
	 	 
	 }
	 /**
	  * 获取单个用户的好友/其他关系的列表
	  * @param string $param
	  * @return string
	  */
	 public function getRelationUserByDkcode($param=''){
	 	//验证传入参数的正确性
	 	if($param == '')
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));
	 	 
	 	$json = json_decode($param,true);
	 	//获取对应参数
	 	$dkcode = $json['dkcode'];
	 	$relation =  (int)$json['relation'];
	 	$pageNo =  isset($json['pageNo'])?(int)$json['pageNo']:1;
	 	$pageSize =  isset($json['pageSize'])?(int)$json['pageSize']:1;
	 	
	 	if(!in_array($relation,array(3,4,10))){
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));
	 	}
	 	
	 	$whereField = filter_var($dkcode,FILTER_VALIDATE_EMAIL)?'email':'dkcode';
	 	$sql = 'SELECT uid FROM user_info WHERE '.$whereField." = '".$dkcode."'";
	
	 	$result_info = $this->db->query($sql)->row_array();

	 	//验证是否两用户都存在
	 	if(!isset($result_info['uid']) || !$result_info['uid']){
	 		return json_encode(array('code'=>-100,'text'=>'用户不存在','result'=>NULL));
	 	}
	 	
	 	//分页获取关系人列表
	 	switch ($relation){
	 		case 10://好友
	 			$resApi = api('Relation')->getFriendsWithInfo($result_info['uid'],true,$pageNo,$pageSize);
	 			break;
	 		case 4://关注
	 			$resApi = api('Relation')->getFollowingsWithInfo($result_info['uid'],true,$pageNo,$pageSize);
	 			break;
	 		case 3://我的粉丝
	 			$resApi = api('Relation')->getFollowersWithInfo($result_info['uid'],$pageNo,$pageSize);
	 	}
	 	
	 	if(!$resApi||!is_array($resApi)){
	 	 return json_encode(array('code'=>-100,'text'=>'获取不到关系人列表','result'=>NULL));	
	 	}
	 	
	 	//从数据库获取各个用户的email
	 	$uidArr = array();
	 	foreach($resApi as $v){
	 		$uidArr[]=$v['id'];
	 	}
	 	$sql = 'SELECT uid,email FROM user_info WHERE uid in('."'".implode("','", $uidArr)."')";
	 	$result_info = $this->db->query($sql)->result_array();
	 	$emailArr=array();
	 	foreach($result_info as $v){
	 		$emailArr[$v['uid']] = $v['email'];
	 	}
	 	
	 	//获取关系列表
	 	$paramResult = array();
	 	foreach($resApi as $v){
	 		$var = array();
	 		$var['dkcode'] = $v['dkcode'];
	 		$var['username'] =  $v['name'];
	 		$var['email'] = isset($emailArr[$v['id']])?$emailArr[$v['id']]:Null;
	 		$var['userImg'] = get_avatar($v['id'],'mm');
	 		$var['userHome'] = mk_url('main/index/profile',array('dkcode'=>$v['dkcode']));
	 		$paramResult[] = $var;
	 	}
	 	
	 	return json_encode(array('code'=>1,'text'=>'调用成功','result'=>$paramResult));
	 	
	 }
	 /**
	  * 获取两个用户间的共同好友/其他关系的列表
	  * @param string $param
	  * @return string
	  */
	 public function getOverlapRelationUser($param=''){
	 	//验证传入参数的正确性
	 	if($param == '')
	 		return json_encode(array('code'=>-99,'text'=>'参数错误','result'=>NULL));
	 
	 	$json = json_decode($param,true);
	 	//获取对应参数
	 	$dkcode1 = $json['dkcode1'];
	 	$dkcode2 = $json['dkcode2'];
	 	$relation = $json['relation'];
	 	
	 	if(!$relation){
	 		return json_encode(array('code'=>-99,'text'=>'参数错误,relation传参错误','result'=>NULL));
	 	}
	 	

	 	//执行相应sql语句
	 	$whereField1 = filter_var($dkcode1,FILTER_VALIDATE_EMAIL)?'email':'dkcode'; //判断是否是有效的电子邮件
	 	$whereField2 = filter_var($dkcode2,FILTER_VALIDATE_EMAIL)?'email':'dkcode';
	 	
	 	//获取两用户的uid
	 	$sql = 'SELECT uid FROM user_info WHERE '.$whereField1." = '".$dkcode1."'";
	 	$result_info1 = $this->db->query($sql)->row_array();
	 	 
	 	$sql = 'SELECT uid FROM user_info WHERE '.$whereField2." = '".$dkcode2."'";
	 	$result_info2 = $this->db->query($sql)->row_array();
	 	 
	 	//验证是否两用户都存在
	 	if(!isset($result_info1['uid']) || !isset($result_info2['uid'])){
	 		return json_encode(array('code'=>-100,'text'=>'有用户不存在','result'=>NULL));
	 	}
         
	 	//获取共同关系的列表
	 	$resApi=array();
	 	if( $relation == 10){
	 		
	 	    $resApi = api('Relation')->getCommonFriendsInfo($result_info1['uid'],$result_info2['uid']);
	 	    
	 	}else if( $relation == 4){
	 		
	 		$resApi = api('Relation')->getCommonFollowingsInfo($result_info1['uid'],$result_info2['uid']);
	 	}
	 	
	 	if(!$resApi||!is_array($resApi)){
	 		return json_encode(array('code'=>-99,'text'=>'参数错误,Realtion参数无效','result'=>Null));
	 	}
	 	
	 	
	 	
	 	$paramResult = array();
	 		
	 	//从数据库获取各个用户的email
	 	$uidArr = array();
	 	foreach($resApi as $k=>$v){
	 		$uidArr[]=$k;
	 	}
	 	$sql = 'SELECT uid,email FROM user_info WHERE uid in('."'".implode("','", $uidArr)."')";
	 	$result_info = $this->db->query($sql)->result_array();
	 	$emailArr=array();
	 	foreach($result_info as $v){
	 		$emailArr[$v['uid']] = $v['email'];
	 	}
	 	
	 	//获取关系列表
	 	foreach($resApi as $k=>$v){
	 		$var = array();
	 		$var['dkcode'] = $v['dkcode'];
	 		$var['username'] =  $v['username'];
	 		$var['email'] = isset($emailArr[$k])?$emailArr[$k]:Null;
	 		$var['userImg'] = get_avatar($k,'mm');
	 		$var['userHome'] = mk_url('main/index/profile',array('dkcode'=>$v['dkcode']));
	 		$paramResult[] = $var;
	 	}
	 
	 	
	 	 
	 	return json_encode(array('code'=>1,'text'=>'调用成功','result'=>$paramResult));
	 	 
	 }
	 
	 
	 
	 
}