<?php

class UserWikiService extends DK_Service {

	//读取用户好友用的的redis键名
	private $friendkey = "friend:%d";
	private $hiddenfriendkey = "friend:hidden:%d";
	private $_table = "user_edit_access";
	private $_edit_type = array('base', 'private', 'contact', 'edu', 'job', 'school', 'teach', 'language', 'skill', 'book', 'life', 'interest', 'project', 'relative');

	public function __construct() {
		parent::__construct();
		$this->init_db('user');
		$this->init_redis('user');
	}

	/**
	 * 设置应用区菜单的封面
	 * @author sunlufu
	 * @date 2012/7/14
	 * @param int $type  0:普通用户 1:广告商
	 * @return boolean  true：成功  false：失败
	 */
	public function setAccess($uid, $type){
		$uid = intval($uid);
		$type = intval($type);
		if($uid < 1 or $type < 1) return false;
		$usertype = array(1);
		if(!in_array($type, $usertype)) return false;
		$data = array('access' => 1);
		$this->db->where("`uid` = '".$uid."'");
		$this->db->update("user_info", $data);
		$ret = $this->db->affected_rows();
		if($ret) return true;
		return false;
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

	//获取指定用户的公司信息
	public function getWorkInfo($uid) {
		$uid = intval($uid);
		if (!is_numeric($uid)) return "";
		$where = array('uid' => $uid);
		$field = array('uid', 'companyid', 'company', 'department', 'positioncode', 'starttime', 'endtime');
		
		$field = implode(',', $field);
		$rs = $this->db->from('user_work')->where($where)->order_by('companyid', 'asc')->select($field)->get()->result_array();
		return $rs;
	}

	//获得同学
	public function getclassmate($uid, $schoolType) {
		$uid = intval($uid);
		if(!is_numeric($uid)) return "";
		//$where = array('uid'=>$uid, 'schoolid'=>$schoolId);
		$where = array('uid' => $uid);
		if($schoolType == 's') {
			$where['edulevel'] = '2';
		} else if ($schoolType == 'm') {
			$where['edulevel'] = '3';
		} else if ($schoolType == 'g') {
			$where['edulevel'] = '4';
		} else if ($schoolType == 'u') {
			$where['edulevel'] = array('gt', '4');
		}
		$field = array('uid', 'starttime', 'schoolid', 'edulevel', 'schoolname', 'department', 'department_id', 'classmate');
		
		$field = implode(',', $field);
		$rs = $this->db->from('user_edu')->where($where)->select($field)->get()->result_array();
		return $rs;
	}

	//获得同事
	public function getworkmate($uid) {
		$uid = intval($uid);
		if (!is_numeric($uid)) return "";
		$where = array('uid' => $uid, 'endtime' => 0);
		$field = array('uid', 'companyid', 'company', 'department', 'positioncode', 'starttime', 'endtime', 'workmate');
		
		$field = implode(',', $field);
		$rs = $this->db->from('user_work')->where($where)->order_by('id', 'desc')->select($field)->get()->result_array();
		if(empty($rs)){
			return "";
		}else{
			return $rs[0];
		}
	}

	//获得同行
	public function gettrade($uid, $page = 1, $size = 20) {
		$uid = intval($uid);
		$page = intval($page);
		$size = intval($size);
		if ($uid < 0 || $page < 1 || $size < 0) return array();
		
		$friends = array();
		$friends = $this->getAllFriends($uid);
		if(empty($friends)) return array();
		$str_friends = implode(',', $friends);
		$trade_where = "`uid` = '" . $uid . "' AND `endtime` = '0'";
		$trade_field = 'trade';
		$ret = $this->db->from('user_work')->where($trade_where)->select($trade_field)->get()->row_array();
		if(empty($ret['trade'])) return array();		
		$count_where = "`trade` = '" . $ret['trade'] . "' AND `uid` in ($str_friends)";
		$count_field = 'uid';
		$countss = $this->db->from('user_work')->where("`trade` = '" . $ret['trade'] . "'")->select($count_field)->get()->result_array();
		$count = count($countss);
		if(!$count) return array();		
		$start = ($page - 1) * $size;
		$where = "`trade` = '" . $ret['trade'] . "' AND uid in ($str_friends)";
		$field = array('uid', 'companyid', 'company', 'trade');
		$field = implode(',', $field);
		$tradeinfo = $this->db->from('user_work')->where("`trade` = '" . $ret['trade'] . "'")->where_in('uid',$friends)->select($field)->order_by('id', 'desc')->get()->result_array();
		if(empty($tradeinfo)) return array();
		$map =array('1'=>'IT信息技术','2'=>'金融','4'=>'互联网','5'=>'广告传媒','6'=>'贸易零售','7'=>'交通物流','8'=>'房地产','9'=>'旅游餐饮',
'10'=>'加工制造业','11'=>'石化采掘','12'=>'农林牧渔','13'=>'社会服务','14'=>'医药生物','15'=>'教育培训科研');
		foreach($tradeinfo as $k=>$v){
			$department = $map[$v['trade']];
			$tradeinfo[$k]['department'] = $department;
		}
		$tradeinfo['total'] = $count;	
		//print_r($tradeinfo);die();
		return json_encode($tradeinfo);
	}

	//获得亲人
	//public function getrelative($uid, $page, $size){
	public function getrelative($uid) {
		$uid = intval($uid);
		//$page = intval($page);
		//$size = intval($size);
		//if($uid < 0 || $page < 1 || $size < 0) return array();
		if ($uid < 0) return array();

		$where = "`uid` = '" . $uid . "'";
		$field = 'relativemate';
		$ret = $this->db->from('user_relative')->where($where)->select($field)->get()->result_array();
		
		if (empty($ret)) return array();
		$result = array();
		foreach($ret as $val) {
			$result[] = $val['relativemate'];
		}
		return $result;
	}

	//按居住地，年龄，性别获取人数  lvxinxin 2012-06-26 add
	public function getUserCount($now_addr,$age,$sex){
		if(empty($now_addr) ||  empty($sex)) return false;
		foreach($now_addr as $val){
			//$str .= '_' . $val . $age . $sex;
			$condition[] = 'hashid = ' . "'" . md5($val . $age . $sex) . "'" ;
		}
		$where = implode(" OR ",$condition);		
		$query = $this->db->from('ad_usercount')->where($where)->select('usercount')->get()->row_array();		
		// file_put_contents('log.txt',$this->db->last_query());
		// die($this->db->last_query());
		// $usercount = M('ad_usercount');
		// $query = $usercount->field('usercount')->where($where)->select();
		// return $usercount->getLastSql();//json_encode($query);		
		if(!empty($query)){
			foreach($query as $val){
				$count += $val;
			}			
			return $count;
		}else{			
			return $this->queryUserinfo($now_addr,$age,$sex);
		}	
	}	
	//查询user_info并插入数据到ad_userconut
	private function queryUserInfo($now_addr,$age,$sex){
		if(empty($now_addr) ||  empty($sex)) return false;
		
		switch($age){
			case 1 : 
				$s = @mktime(0,0,0,date('m'),date('d'),date('Y') - 10);
				$e = @mktime(0,0,0,date('m'),date('d'),date('Y') - 15);
				break;
			case 2 : 
				$s = @mktime(0,0,0,date('m'),date('d'),date('Y') - 16);
				$e = @mktime(0,0,0,date('m'),date('d'),date('Y') - 22);
				break;
			case 3 : 
				$s = @mktime(0,0,0,date('m'),date('d'),date('Y') - 23);
				$e = @mktime(0,0,0,date('m'),date('d'),date('Y') - 30);
				break;
			case 4 : 
				$s = @mktime(0,0,0,date('m'),date('d'),date('Y') - 31);
				$e = @mktime(0,0,0,date('m'),date('d'),date('Y') - 40);
				break;
			case 5 : 
				$s = @mktime(0,0,0,date('m'),date('d'),date('Y') - 41);
				$e = @mktime(0,0,0,date('m'),date('d'),date('Y') - 50);
				break;
			case 6 : 
				$s = @mktime(0,0,0,date('m'),date('d'),date('Y') - 50);
				break;
			default :
				$s = 0;
				break;
		}
		$data = array();
		foreach($now_addr as $key => $val){
			$data[$key]['hashid'] = md5( $val . $age . $sex);
			$data[$key]['real'] = $val . $age . $sex;
			$data[$key]['searchcount'] = 1;
			unset($where);
			$where = "select count(*) from user_info where ";
			if(strlen($val) == 2){ //只选某个省
				$where .= ' cityid > ' . $val .'0000' . ' AND cityid < ' . $val .'9999' ;
				if($s && $e){
					$where .= " AND birthday != 0 AND (birthday >". $e . " AND  birthday < " . $s .")"; 
				}
				elseif($s){
					$where .= " AND birthday != 0 AND birthday <". $s;
				}
				elseif($s == 0){
					//
				}
				if($sex == 1){//1男2女3全部
					$where .= " AND sex = 1";
				}
				elseif($sex == 2){
					$where .= " AND sex = 2";
				}
				$condition[] = $where;

			}
			elseif($val == 1){ //只选某个国家
				$where .= ' cityid < ' . $val .'000000' ;
				if($s && $e){
					$where .= " AND birthday != 0 AND (birthday >". $e . " AND  birthday < " . $s .")"; 
				}
				elseif($s){
					$where .= " AND birthday != 0 AND birthday <". $s;
				}
				elseif($s == 0){
					//
				}
				if($sex == 1){//1男2女3全部
					$where .= " AND sex = 1";
				}
				elseif($sex == 2){
					$where .= " AND sex = 2";
				}
				$condition[] = $where;
			}
			else{//只选某个市
				$where .= ' cityid > ' . $val .'00' . ' AND cityid < ' . $val .'99' ;
				if($s && $e){
					$where .= " AND birthday != 0 AND (birthday >". $e . " AND  birthday < " . $s .")"; 
				}elseif($s){
					$where .= " AND birthday != 0 AND birthday <". $s;
				}
				elseif($s == 0){
					//
				}
				if($sex == 1){//1男2女3全部
					$where .= " AND sex = 1";
				}elseif($sex == 2){
					$where .= " AND sex = 2";
				}
				$condition[] = $where;
			}
		}
		
		$where = implode(" union all ",$condition);		
		$query = $this->db->query($where)->result_array();//$ui->query($where);		
		foreach($data as $key => $val){
			if(!empty($query[$key]['count(*)'])){
				$data[$key]['usercount'] = $query[$key]['count(*)'];
			}else{
				$data[$key]['usercount'] = 0;
			}
			
			$count += $query[$key]['count(*)'];
		}
		// $uc = M('ad_usercount');
		// $uc->addAll($data);
		$this->db->insert_batch('ad_usercount',$data);
		return $count;

	}
	//更新按居住地，年龄，性别获取人数的数据  lvxinxin 2012-06-26 add
	public function updateUserCount($now_addr,$age,$sex){
		if(empty($now_addr) ||  empty($sex)) return false;
		$newstr = $now_addr . $age . $sex;
		$newhash = md5($newstr);
		// $usercount = M('ad_usercount');
		
		// $query = $usercount->where(array('hashid'=>$newhash))->find();
		//return $usercount->getLastSql();
		$query = $this->db->from('ad_usercount')->where(array('hashid'=>$newhash))->get()->row_array();
		if($query){
			$sql = "update ad_usercount set searchcount = searchcount + 1 ,usercount = usercount + 1 where hashid = '{$newhash}'";
			//return $sql;
			// $usercount ->query($sql);
			$this->db->query($sql);
			return true;
		}else{
			return false;
		}
		
	}

	
	//根据dkcode获取省、市代码
	public function getAreaCode($dkcode){
		if(empty($dkcode)) return false;
		// $ui = M('user_info')->field('cityid,sex,birthday')->where(array('dkcode'=>$dkcode))->find();
		$ui = $this->db->from('user_info')->where(array('dkcode'=>$dkcode))->select('cityid,sex,birthday')->get()->row_array();
		if(!$ui) return false;
		if(isset($ui['cityid']) && strlen($ui['cityid']) == 6){			
			$ui['cityid'] = substr($ui['cityid'],0,4);			
		}
		else{
			$ui['cityid'] = null;
		}
		if(empty($ui['birthday'])){
			$ui['birthday'] = null;
		}
		else{
			$ui['birthday'] = date('Y',time()) - date('Y',$ui['birthday']);
		}
		return $ui;
	}

    // 获取全部好友ID列表
    public function getAllFriends($uid, $self = true, $actorId = null) {
        return $this->_getAllFriends($uid, $self, $actorId);
    }

/**
     * 获取用户的完整的好友列表
     * 
     * @param int $uid
     * @param bool $self
     */
    private function _getAllFriends($uid, $self = true, $actorId = null) {
        if ($self) {
            $unionKey = $this->_unionFriend($uid);
            $res = $this->redis->zRevRange($unionKey, 0, -1);
        } else {
            if (!empty($actorId) && $this->_isHiddenFriend($uid, $actorId)) {
                $openKey = $this->_makeOpenFriends($uid, $actorId);
                $res = $this->redis->zRevRange($openKey, 0, -1);
            } else {
                $res = $this->redis->zRevRange('friend:' . $uid, 0, -1);
            }
        }
        return $res;
    }

    private function _unionFriend($uid) {
        $union_keys = array('friend:' . $uid, 'friend:hidden:' . $uid);
        $output_key = 'tmp:friend:union:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }

    //判断用户2是否为用户隐藏的好友
    private function _isHiddenFriend($uid, $uid2) {
        return is_numeric($this->redis->zScore('friend:hidden:' . $uid, $uid2));
    }

    //生成用户公开的好友列表
    private function _makeOpenFriends($uid, $actorId) {
        $this->redis->delete('tmp:friend:open:' . $uid);
        $time = $this->redis->zScore('friend:hidden:' . $uid, $actorId);
        $this->redis->zAdd('tmp:friend:open:' . $uid, $time, $actorId);

        $union_keys = array('friend:' . $uid, 'tmp:friend:open:' . $uid);

        $output_key = 'tmp:friend:open:' . $uid;
        $this->redis->zUnion($output_key, $union_keys, array(1, 1), 'MAX');
        $this->redis->setTimeout($output_key, 300);
        return $output_key;
    }
    
    public function getPermission($uids, $uid){
    	 $rs = array();
         foreach($uids as $key=>$val){
			  $isFans = false;
			  if($val == 10){
				$isFriend = true;
			  }else{
				$isFriend = false;
			  }
         	  $rs[$val]['base']=$this->isAllow('base', $key, $uid,$key, $isFriend, $isFans);
              $rs[$val]['edu']=$this->isAllow('edu', $key, $uid,$key, $isFriend, $isFans);
              $rs[$val]['job']=$this->isAllow('job', $key, $uid,$key, $isFriend, $isFans);
         }
         return $rs;
    }
    
    private  function isAllow($type, $object_id, $uid , $action_uid , $is_friend = false, $is_fans = false)
	{
		$object_ids = is_array($object_id) ? $object_id : (array)$object_id;
		$return = array();
		if ($uid == $action_uid)
		{
			foreach ($object_ids as $id)
			{
				$return[] = $id;
			}
		} else
		{
			$object_list = $this -> getObject($type, $object_ids);
			foreach ($object_ids as $id)
			{
				if (in_array($type, $this -> _edit_type))
				{
					if (empty($object_list) || !isset($object_list[$id]))
					{
						switch($type) {
							//公开
							case 'base' :
							case 'interest' :
								$return[] = $id;
								break;
							//仅限自己
							case 'private' :
							case 'contact' :
								break;
							//朋友
							default :
								if ($is_friend){
									$return[] = $id;
								}
						}
					} else
					{
						switch($object_list[$id]['type']) {
							case -1:
							//自定义
								if (isset($object_list[$id]['content']) && in_array($uid, $object_list[$id]['content'])){
									if($is_friend){
										$return[] = $id;
									} else {
										$this->_updateCustomerPermission($object_id, $object_list[$id]['content'], $uid, $type);
									}
								}
								break;
							case 1 :
							//公开
								$return[] = $id;
								break;
							case 8:
							//自己
							//已处理
								break;
							case 4:
							//好友
								if ($is_friend)
								{
									$return[] = $id;
								}
								break;
							case 3:
							//粉丝
								if ($is_friend || $is_fans)
								{
									$return[] = $id;
								}
								break;
						}
					}
				} else
				{
					if (empty($object_list) || !isset($object_list[$id]))
					{
						continue;
					}
					switch($object_list[$id]['type']) {
						case -1:
						//自定义
							if (isset($object_list[$id]['content']) && in_array($uid, $object_list[$id]['content'])){
								if($is_friend){
									$return[] = $id;
								} else {
									$this->_updateCustomerPermission($object_id, $object_list[$id]['content'], $uid, $type);
								}
							}
							break;
						case 1:
						//公开
							$return[] = $id;
							break;
						case 8:
						//自己
						//已处理
							break;
						case 4:
						//好友
							if ($is_friend)
							{
								$return[] = $id;
							}
							break;
						case 3:
						//粉丝
							if ($is_friend || $is_fans)
							{
								$return[] = $id;
							}
							break;
					}
				}
			}
		}
		return is_array($object_id) ? $return : in_array($object_id, $return);
	}  

    //获得对象的权限列表
    protected function getObject($type, $object_ids) {
        $_edit_type = array('base', 'private', 'contact', 'edu', 'job', 'school', 'teach', 'language', 'skill', 'book', 'life', 'interest', 'project', 'relative');
        $return = array();
        if (in_array($type, $_edit_type)) {
            $table = $this -> _table;
            $sql = "SELECT object_id, {$type} FROM {$table}
                            WHERE object_id in ('" . implode("','", $object_ids) . "')";
            $res = $this -> db -> query($sql);
            foreach ($res->result_array() as $item) {
                $return[$item['object_id']] = json_decode($item[$type], true);
            }
        }
        return $return;
    }
    
	/**
	 * 去除自定义中的非好友
	 * 
	 * @author bohailiang
	 * @date   2012/3/22
	 * @access public
	 * @param  string $uid 用户ID
	 * @param  array  $permission_content  uid数组
	 * @param  int    $object_uid  需要去除的uid
	 * @param  string $field 涉及的类型
	 * @return true / false
	 */
	private function _updateCustomerPermission($uid = 0, $permission_content = array(), $object_uid = 0, $field = ''){
		if(empty($uid)){
			return false;
		}

		if(!is_array($permission_content)){
			return false;
		}

		$index = array_search($object_uid, $permission_content);
		unset($permission_content[$index]);

		$permission_array = array('type' => -1);
		if(empty($permission_content)){
			$permission_array['type'] = 8;
		} else {
			$permission_array['content'] = $permission_content;
		}

		$params = array($field => json_encode($permission_array));
        $query = $this -> db -> update('user_edit_access', $params, array('object_id' => $uid));
		return true;
	}

	 /**
     * 获取指定好友的uids的权限
     *
     * @author hxm
     * @date 2012/07/27
     * @access public 
     * @param array $uids 用户id
     * @param array $fields 指定模块名称
     * @return array
     */
    public function getPermissonByModule($uids, $fields="*"){    	
     $module = array('base', 'private', 'contact', 'edu', 'job', 'school', 'teach', 'language', 'skill', 'book', 'life', 'interest', 'project', 'relative');
     $uids = is_array($uids) ? $uids : (array)$uids;
     if($fields == '*'){
     	$mapFeild = " * ";
     }else{
     	$mapFeild = 'object_id';
        $fields = is_array($fields) ? $fields : (array)$fields;
     	foreach($fields as $val){
     	if(in_array($val, $module)){
     	$mapFeild .= ', '.$val;
     	}
     }
     }     
     $uidsFeild = implode(',', $uids);
     $sql = "SELECT " .$mapFeild. " FROM user_edit_access
                            WHERE object_id in (" . $uidsFeild . ")";
     $res = $this->db->query($sql)->result_array();
     return $res;
    }

	/****by  sunlufu start ***/
	//检测用户是否申请了修改登录邮箱功能
	public function ismodemail($params){
		$sql = "SELECT `sendtime`,`updateemail` FROM `user_setting` WHERE `dkcode` = '".$params['dkcode']."'";
		$result = $this->db->query($sql);
		$result = $result->row_array();
		if(empty($result) or $result['updateemail'] != $params['email'] or $result['sendtime'] != $params['time']) {
			return false;
		}
		return true;
	}
	
	//修改用户登录邮箱
	public function modemail($params){
		//更新用户资料表email字段
		$user_ret = $this->db->update('user_info' , array('email' => $params['email']), array('dkcode' => $params['dkcode']));
		//更新登录表信息
		$user_auth_ret = $this->db->update('user_auth', array('email' => $params['email']), array('dkcode' => $params['dkcode']));
		//更新redis用户信息
		//$this->redis->
		
		//删除user_setting表数据
		$setting_params = array('sendtime' => 0,'updateemail' => '');
		$setting_ret = $this->db->update('user_setting' , $setting_params, array('dkcode' => $params['dkcode']));
		
		//如果用户登录，则退出登录
		if(empty($_SESSION)){
			
		}
		return 1;
	}
	//判断邮箱是否在已经在重置邮箱中被用过了
	public function settingemail($email){
		$sql = "SELECT `sendtime` FROM `user_setting` WHERE `updateemail` = '".$email."'";
		$result = $this->db->query($sql);
		$result = $result->row_array();
		if(!empty($result['sendtime'])) {
			return false;
		}
		return true;
	}
	
	//修改用户资料同步reids
	/*
	*uid   用户uid
	*array('name', 'sex')
	*/
	public function modRedisUserInfo($uid, $uinfo=array()){
		$uid = intval($uid);
		if($uid < 1 or !is_array($uinfo) or (empty($uinfo['name']) && empty($uinfo['sex']))) {
			return 0;
		}
		if(!empty($uinfo['sex']) && !in_array($uinfo['sex'] ,array(1, 2, 3))) {
			return 0;
		}
		if(count($uinfo) >2 ){
			return 0;
		}
		return $this->redis->hMset('user:'.$uid.'', $uinfo);
	}
	
	/****by  sunlufu end***/
}