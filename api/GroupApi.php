<?php

/**
 * [ Duankou Inc ]
 * Created on 2012-3-5
 * @author fbbin
 * The filename : GroupApi.php
 */
class GroupApi extends DkApi {

    protected $group;

    public function __initialize() {
        $this->group = DKBase::import('TheGroup', 'app');
    }

    /**
     * 群组发表信息
     * @param array $data
     * @author fbbin
     * @return json
     */
    public function addGroupInfo(array $data) {
        if (empty($data) || !$data['gid'] || !$data['uid']) {
            return false;
        }
        return json_encode($this->group->addGroupInfo($data));
    }

    /**
     * 获取最新发表的信息
     * @author fbbin
     * @return json
     */
    public function getNowInfo() {
        return json_encode($this->group->getNewInfo());
    }

    /**
     * 按页获取信息
     * @param intval $gid
     * @param intval $num
     * @param intval $page
     * @author fbbin
     * @return json
     */
    public function getPageInfo($gid, $num = 20, $page = 1) {
        if (!$gid) {
            return false;
        }
        return json_encode($this->group->getPageInfo($gid, $page, $num));
    }

    public function getGroupsByCustom($uid, $limit = 5) {
        if (intval($uid) < 1)
            return array();
        return $this->group->getMyGroups($uid, 'CUSTOM', $limit);
    }

    /**
     * 获取用户所有的群组和子群
     *
     * @param $uid unknown_type       	
     */
    public function getUserGroup($uid) {
        return $this->group->getUserGroup($uid);
    }

    /**
     * 查询父群内的子群列表
     * @param unknown_type $gid
     */
    public function getSubgroupByGroup($gid) {
        return $this->group->getSubgroupByGroup($gid);
    }

    /**
     * 群成员列表
     * @param unknown_type $id
     * @param unknown_type $is_sub
     * @return multitype:
     */
    public function getGroupMembers($id, $is_sub = false) {
        return $this->group->getGroupMembers($id, $is_sub);
    }

    /**
     * 查询群信息
     * @param unknown_type $id
     */
    public function getGroupInfo($id) {
        return $this->group->getGroupInfo($id);
    }
    
    /**
     * 检查用户是否在群组里面
     * @param unknown_type $gid
     * @param unknown_type $uid
     */
    public function checkGruopMember( $gid, $uid ) {
    	return $this->group->checkGruopMember( $gid, $uid );
    }

    public function getMyGroups($uid, $type = 'CUSTOM', $limit = 3) {
        return $this->group->getMyGroups($uid, $type, $limit);
    }

}

?>