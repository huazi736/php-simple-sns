<?php

/**
 * 用户中心接口
 * @author mawenpei
 * @since <2012/06/20>
 */
class UserService extends DK_Service {

    public function __construct() {
        parent::__construct();
        $this->init_db('user');
        $this->init_redis();
    }
    
    /**
     * 获取用户信息
     */
    public function getUserInfo($value,$type='uid',$return_fields = array(),$isactive = false)
    {
        if(!in_array($type,array('uid','dkcode','email'))) return null;
		$where = array($type=>$value);
		if($isactive)  $where['isactive'] = 1;
		if(!empty($return_fields) && count($return_fields) >0) {
			$field = implode(',',$return_fields);
		}
		else{
			$field = '*';
		}
        return $this->db->from('user_info')->where($where)->select($field)->get()->row_array();
    }

    /**
     * 获取用户列表
     */
    public function getUserList($uids, $return_fields = array(), $index = 0, $size = 10) {
        $fields = is_array($return_fields) && count($return_fields) > 0 ? implode(',', $return_fields) : '*';
        $result = $this->db->from('user_info')->where_in('uid', $uids)->limit($size, $index)->select($fields)->get()->result_array();
        return $result;
    }
    
    /**
     * 通过dkcode获取用户列表
     */
    public function getUserListByCode($dkcodes, $return_fields = array(), $index = 0, $size = 10) {
        $fields = is_array($return_fields) && count($return_fields) > 0 ? implode(',', $return_fields) : '*';
        $result = $this->db->from('user_info')->where_in('dkcode', $dkcodes)->limit($size, $index)->select($fields)->get()->result_array();
        return $result;
    }

    //通过用户姓名模糊查询用户信息
    public function getUserInfoByUsername($uname, $return_fields = array()) {
        if (empty($uname))
            return false;
        $fields = is_array($return_fields) && count($return_fields) > 0 ? implode(',', $return_fields) : '*';
        return $this->db->like(array('username' => @iconv('gbk', 'utf-8', trim($uname))))->select($fields)->get('user_info')->result_array();
    }

    // =========== redis user info ===============
    
    public function getShortInfoByIds($uids) {
        $users = array();
        foreach ($uids as $uid) {
            $users[] = $this->getUserInfo($uid);
        }
        return $users;
    }

    /**
     * 设置用户简要信息
     * 信息格式为 array('uid' => 'user id', 'uname' => 'user name', 'dkcode' => 'duankou num', 'sex' => 'sex num')
     * @param type $data 用户信息, 
     * @return type 
     */
    public function setShortInfo($data = array()) {
        if (empty($data)) {
            return false;
        }

        if ($this->checkFields($data)) {
            if (isset($data['sex']) && empty($data['sex'])) {
                $data['sex'] = '3'; //默认3，性别保密
            }

            $data = array_filter($data);
            $mapping = array('uid' => 'id', 'uname' => 'name', 'dkcode' => 'dkcode', 'sex' => 'sex');
            $data_arr = array();
            foreach ($mapping as $key => $value) {
                if (isset($data[$key])) {
                    $data_arr[$value] = $data[$key];
                }
            }

            $res = $this->redis->hMset('user:' . $data['uid'], $data_arr);
            if ($res) {
                //dump keys
                $this->redis->zAdd('dump:user', time(), $data_arr['id']);
            }
            return $res;
        }
        return false;
    }

    public function deleteShortInfo($uid) {
        $res = $this->redis->delete('user:' . $uid);
        if ($res) {
            //dump keys
            if (!$this->redis->exists('user:' . $uid)) {
                $this->redis->zDelete('dump:user', $uid);
            }
        }
        return $res;
    }

    /**
     * 获取用户简要信息
     * @param type $uid 用户ID
     * @return type 
     */
    public function getShortInfo($uid, $fields = array()) {
        if (empty($fields)) {
            $fields = 'id,name,dkcode,sex';
        }

        if ($fields == '*') {
            $user = $this->redis->hGetAll('user:' . $uid);
        } else {
            $show_fields = explode(',', $fields);
            if (empty($show_fields)) {
                return array();
            }
            $user = $this->redis->hMGet('user:' . $uid, $show_fields);
        }

        if ($user['name'] === false || $user['dkcode'] === false) {
            $user = $this->syncUser($uid);
        }
        return $user;
    }

    /**
     * 获取多个目标用户的简要信息
     * @param type $uids    目标用户ID集合
     * @return type 
     */
    public function getMultiShortInfo($uids, $fields = array()) {
        $results = array();
        foreach ($uids as $uid) {
            $results[] = $this->getUserInfo($uid, $fields);
        }
        return $results;
    }

    /**
     * 验证传入的数据信息
     * @param type $data
     * @return bool 
     */
    private function checkFields($data) {
        if (!is_array($data)) {
            return false;
        }

        //验证field是否规范
        $valid_fields = array('uid', 'dkcode');
        $data_fields = array_keys(array_filter($data));

        foreach ($valid_fields as $field) {
            if (!in_array($field, $data_fields)) {
                return false;
            }
        }
        return true;
    }

	/**
	 * 设置应用区菜单的封面
	 *
	 * @author zengmingming
	 * @date 2012/7/4
	 *
	 * @param int $userid  用户UID
     * @param int $menuid  菜单ID
     * @param string $imgpath 菜单图片地址
	 * @param string $group FASTDFS的分组
	 *
	 * @return boolean
	 */
	public function setAppMenuCover($uid, $menuid, $imgpath, $group='')
	{
		if (empty($uid) || empty($menuid)) { return FALSE; } 

        //默认权限
        $weight = 0;

        switch ($menuid) {
            case 1: case 3: case 5: case 11: case 22:
                $weight = 8;
                break;
        }

        $where = array(
			'uid' => $uid,
			'menu_id' => $menuid
        );

        $data = array_merge($where, array('menu_img' => $imgpath, 'group' => $group, 'weight' => $weight));

		$nums = $this->db->where($where)->get('user_menu_purview')->num_rows();

        if (!$nums) {
			// 设置应用区菜单的封面
            $this->db->insert('user_menu_purview', $data);

        } else {
			// 更新应用区菜单的封面
            $this->db->update('user_menu_purview', $data, $where);
        }

        return TRUE;
	}

}