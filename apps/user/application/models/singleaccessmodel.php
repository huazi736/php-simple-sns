<?php
class SingleAccessModel extends MY_Model {
    protected $_table = ACCESS_EDIT;
    protected $_edit_type = array('base', 'private', 'contact', 'edu', 'job', 'school', 'teach', 'language', 'skill', 'book', 'life', 'interest', 'project', 'relative');
    protected $_user_friends = array();
    protected $_user_follows = array();

    //设置权限
    public function set($type, $object_id, $permission) {     	
        //1-公开，3-粉丝，4-朋友，8-自己，-1-自定义        
        $permission_arr = array(1, 3, 4, 8);
        if (is_numeric($permission) && in_array($permission, $permission_arr)) {
            $access_type = $permission;
            $access_content = '';
        } else {
            $access_type = -1;
            //自定义
            if ($permission == '0') {
                $permission = '';
            }
            $access_content = $permission;
        }
        $type = strtolower($type);
        if (in_array($type, $this -> _edit_type)) {
            return $this -> _setEdit($type, $object_id, $access_type, $access_content);
        } else {
            return false;
        }
    }

    //获知资料的权限
    protected function _setEdit($field, $uid, $access_type, $access_content) {    	  	
        $sql = "SELECT id FROM {$this->_table}
                            WHERE object_id = ?";
        $res = $this -> db -> query($sql, array($uid));        
        $row = $res -> row_array();          	          
        if (isset($row['id']) && $row['id']) {
            $params = array($field => $this -> getObjectAccess($access_type, $access_content));            
            $query = $this -> db -> update($this -> _table, $params, array('id' => $row['id']));                     
        } else {
            $params = array('object_id' => $uid, $field => $this -> getObjectAccess($access_type, $access_content));
            $query = $this -> db -> insert($this -> _table, $params);
        }
        return $query;
    }

	//获得权限的jsion字符串
	protected function getObjectAccess($access_type, $access_content)
	{
		$object_access = array();
		$object_access['type'] = $access_type;
		if ($access_type == -1 && !empty($access_content))
		{
			$object_access['content'] = explode(',', $access_content);
		}
		return json_encode($object_access);
	}


	//是否有权限访问
	public function isAllow($type, $object_id, $uid = UID, $action_uid = ACTION_UID, $is_friend = false, $is_fans = false)
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
        //$type=base $object_ids=array(uid)
        $return = array();
        if (in_array($type, $this -> _edit_type)) {
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

    //获得对象的权限
    public function getAccess($type, $object_id) {
        $object_ids = is_array($object_id) ? $object_id : (array)$object_id;
        $return = array();
        if (in_array($type, $this -> _edit_type)) {
            $table = $this -> _table;
            $sql = "SELECT object_id, {$type} FROM {$table}
                            WHERE object_id in ('" . implode("','", $object_ids) . "')";
            $res = $this -> db -> query($sql);
            $object_ids_info = $res -> result_array();
            //已存在的编号
            $has_ids = array();
            foreach ($object_ids_info as $item) {
                $has_ids[] = $item['object_id'];
            }
            foreach ($object_ids as $id) {
                if (!in_array($id, $has_ids)) {
                    $object_ids_info[] = array('object_id' => $id);
                }
            }
            foreach ($object_ids_info as $item) {
                if (!isset($item[$type]) || empty($item[$type])) {
                    switch($type) {
                        //公开
                        case 'base' :
                        case 'interest' :
                            $return[$item['object_id']]['object_type'] = 1;
                            //公开
                            break;
                        //仅限自己
                        case 'private' :
                        case 'contact' :
                            $return[$item['object_id']]['object_type'] = 8;
                            //自己
                            break;
                        //朋友
                        default :
                            $return[$item['object_id']]['object_type'] = 4;
                        //好友
                    }
                    $return[$item['object_id']]['object_content'] = '';
                } else {
                    $tmp = json_decode($item[$type], true);
                    $return[$item['object_id']] = array('object_type' => $tmp['type'], 'object_content' => isset($tmp['content']) ? $tmp['content'] : '', );
                }
            }
        }
        return is_array($object_id) ? $return : (isset($return[$object_id]) ? $return[$object_id] : array('object_type' => 1));
    }

    /**
     * 判断是否是好友
     *
     * @author bohailiang
     * @date   2012/3/22
     * @access public
     * @param  string $uid 用户ID
     * @return true / false
     */
    public function isFriend($uid = 0, $uid2 = 0) {
        if (empty($uid) || empty($uid2)) {
            return false;
        }
        //这个是老版本的call_soap调用方式，换成service方式  by hxm 2012/07/06
        //$is_friend = call_soap('social', 'Social', 'isFriend', array('uid' => $uid, 'uid2' => $uid2));
        $is_friend = service('Relation')->isFriend($uid, $uid2);
        return $is_friend;
    }

	/**
	 * 判断是否是粉丝
	 * 
	 * @author bohailiang
	 * @date   2012/3/22
	 * @access public
	 * @param  string $uid 用户ID
	 * @return true / false
	 */
	public function isFans($uid = 0, $uid2 = 0){
		if(empty($uid) || empty($uid2)){
			return false;
		}
		//这个是老版本的call_soap调用方式，换成service方式  by hxm 2012/07/06
		//$is_friend = call_soap('social', 'Social', 'isFollower', array('uid' => $uid, 'uid2' => $uid2));
		$is_fans = service('Relation')->isFollower($uid, $uid2);
		return $is_fans;
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
        $query = $this -> db -> update($this -> _table, $params, array('object_id' => $uid));
		return true;
	}
	
    public function getPermissionIndex($uid){
    	$sql = "SELECT id, base, edu, job FROM {$this->_table}
                            WHERE object_id = ?";
        $res = $this -> db -> query($sql, array($uid));        
        $row = $res -> row_array();        
        $rs = array();
        if(!empty($row)){
        	$rs['people_level'] = $row['base'];
        	$rs['school_level'] = $row['edu'];
        	$rs['company_level'] = $row['job'];
        }else{
        	$rs['people_level'] = '{"type":1}';
        	$rs['school_level'] = '{"type":4}';
        	$rs['company_level'] = '{"type":4}';        	
        }
        return $rs;
    }
}
?>
