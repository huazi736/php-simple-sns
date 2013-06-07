<?php

class UserApi extends DkApi {

    protected $user;
    protected $fastUser;

    public function __initialize() {
        $this->user = DKBase::import('UserInfo', 'user');
        $this->fastUser = DKBase::import('FastUser', 'user');
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo($value, $type='uid', $return_fields = array(), $isactive = false) {
        return $this->user->getUserInfo($value, $type, $return_fields, $isactive);
    }

    /**
     * 获取用户列表
     */
    public function getUserList($uids, $return_fields = array(), $index = 0, $size = 10) {
        return $this->user->getUserlist($uids, $return_fields, $index, $size);
    }

    /**
     * 通过dkcode获取用户列表
     */
    public function getUserListByCode($dkcodes, $return_fields = array(), $index = 0, $size = 10) {
        return $this->user->getUserListByCode($dkcodes, $return_fields, $index, $size);
    }

    //通过用户姓名模糊查询用户信息
    public function getUserInfoByUsername($uname, $return_fields = array()) {
        return $this->user->getUserInfoByUsername($uname, $return_fields);
    }

    //批量获取用户简要信息
    //@ok
    public function getShortInfoByIds($uids) {
        return $this->fastUser->getShortInfoByIds($uids);
    }
    
    /**
     * 设置用户简要信息
     * 信息格式为 array('uid' => 'user id', 'uname' => 'user name', 'dkcode' => 'duankou num', 'sex' => 'sex num')
     * @param type $data 用户信息, 
     * @return type 
     * @ok
     */
    public function setShortInfo($data = array()) {
        return $this->fastUser->setShortInfo($data);
    }

    /**
     * 删除简要信息
     * @param type $uid
     * @return type 
     * @ok
     */
    public function deleteShortInfo($uid) {
        return $this->fastUser->deleteShortInfo($uid);
    }

    /**
     * 获取用户简要信息
     * @param type $uid 用户ID
     * @return type 
     * @ok
     */
    public function getShortInfo($uid, $fields = array()) {
        return $this->fastUser->getShortInfo($uid, $fields);
    }

    /**
     * 获取多个目标用户的简要信息
     * @param type $uids    目标用户ID集合
     * @return type 
     * @ok
     */
    public function getMultiShortInfo($uids, $fields = array()) {
        return $this->fastUser->getMultiShortInfo($uids, $fields);
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
    public function setAppMenuCover($uid, $menuid, $imgpath, $group='') {
        return $this->user->setAppMenuCover($uid, $menuid, $imgpath, $group);
    }

}