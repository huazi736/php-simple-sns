<?php

class UserWikiApi extends DkApi {

    protected $userWiki;

    public function __initialize() {
        $this->userWiki = DKBase::import('TheUserWiki', 'user');
    }

    /**
     * 设置应用区菜单的封面
     * @author sunlufu
     * @date 2012/7/14
     * @param int $type  0:普通用户 1:广告商
     * @return boolean  true：成功  false：失败
     */
    public function setAccess($uid, $type) {
        return $this->userWiki->setAccess($uid, $type);
    }

    //获取指定用户的学校信息
    public function getEduInfo($uid) {
        return $this->userWiki->getEduInfo($uid);
    }

    //获取指定用户的公司信息
    public function getWorkInfo($uid) {
        return $this->userWiki->getWorkInfo($uid);
    }

    //获得同学
    public function getclassmate($uid, $schoolType) {
        return $this->userWiki->getclassmate($uid, $schoolType);
    }

    //获得同事
    public function getworkmate($uid) {
        return $this->userWiki->getworkmate($uid);
    }

    //获得同行
    public function gettrade($uid, $page = 1, $size = 20) {
        return $this->userWiki->gettrade($uid, $page, $size);
    }

    //获得亲人
    //public function getrelative($uid, $page, $size){
    public function getrelative($uid) {
        return $this->userWiki->getrelative($uid);
    }

    //按居住地，年龄，性别获取人数  lvxinxin 2012-06-26 add
    public function getUserCount($now_addr, $age, $sex) {
        return $this->userWiki->getUserCount($now_addr, $age, $sex);
    }

    //更新按居住地，年龄，性别获取人数的数据  lvxinxin 2012-06-26 add
    public function updateUserCount($now_addr, $age, $sex) {
        return $this->userWiki->updateUserCount($now_addr, $age, $sex);
    }

    //根据dkcode获取省、市代码
    public function getAreaCode($dkcode) {
        return $this->userWiki->getAreaCode($dkcode);
    }

    // 获取全部好友ID列表
    public function getAllFriends($uid, $self = true, $actorId = null) {
        return $this->userWiki->getAllFriends($uid, $self, $actorId);
    }

    public function getPermission($uids, $uid) {
        return $this->userWiki->getPermission($uids, $uid);
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
    public function getPermissonByModule($uids, $fields="*") {
        return $this->userWiki->getPermissonByModule($uids, $fields);
    }

    /** **by  sunlufu start ** */
    //检测用户是否申请了修改登录邮箱功能
    public function ismodemail($params) {
        return $this->userWiki->ismodemail($params);
    }

    //修改用户登录邮箱
    public function modemail($params) {
        return $this->userWiki->modemail($params);
    }

    //判断邮箱是否在已经在重置邮箱中被用过了
    public function settingemail($email) {
        return $this->userWiki->settingemail($email);
    }

    //修改用户资料同步reids
    /*
     * uid   用户uid
     * array('name', 'sex')
     */
    public function modRedisUserInfo($uid, $uinfo=array()) {
        return $this->userWiki->modRedisUserInfo($uid, $uinfo);
    }

}