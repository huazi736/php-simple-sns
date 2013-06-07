<?php

class UserInfoModel extends DkModel {

    public function __initialize() {
        $this->init_db('user');
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo($value, $type='uid', $return_fields = array(), $isactive = false) {
        if (!in_array($type, array('uid', 'dkcode', 'email')))
            return null;
        $where = array($type => $value);
        if ($isactive)
            $where['isactive'] = 1;
        if (!empty($return_fields) && count($return_fields) > 0) {
            $field = implode(',', $return_fields);
        } else {
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

    //设置应用区菜单封面
    public function setAppMenuCover($uid, $menu_module, $imgpath, $group='') {
        if (empty($uid) || empty($menu_module)) {
            return FALSE;
        }

        //默认权限
        $weight = 0;

        switch ($menu_module) {
            case 'interest': case 'praise': case 'msg': case 'favorite': case 'ask':
                $weight = 8;
                break;
        }

        $where = array(
            'uid' => $uid,
            'menu_module' => $menu_module
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