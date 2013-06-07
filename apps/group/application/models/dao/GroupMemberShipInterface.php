<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组与成员关系表原子Dao接口
 */
interface GroupMemberShipInterface
{
	public function create($array);
	public function update($gid, $array);
	public function findById($id);
	public function findByIds($ids);
	public function orderBy($key, $desc = 'DESC');
	
	public function createMulti($array);
	/**
	 * 获得gid和uid关联的数据
	 * @param $gid
	 * @param $uid
	 */
	public function findByGroupByUser($gid, $uid);
	/**
	 * 获得gid和uids关联的数据
	 * @param int $gid
	 * @param int $uids
	 * @return array
	 */
	public function findByGroupByUsers($gid, $uids);
	/**
	 * 检查用户是否已经加入该群组，是返回true，否返回false
	 * @param int $gid
	 * @param int $uid
	 * @return boolean
	 */
	public function checkMemberExist($gid, $uid);
	/**
	 * 检查传入的uid集合那些已经存在gid这个群组中
	 * @param int $gid
	 * @param array $uids
	 * @return array
	 */
	public function checkMembersExist($gid, $uids);
	/**
	 * 获得群内用户集合，用来查询群用户详细信息
	 * @param int $gid
	 * @param int $role 可以删选用户角色，可单独选择某一角色，也可以多选array(0,1,2)
	 * @return array
	 */
	public function getMembersByGroup($gid,$role = -1);
	/**
	 * 获得某些群内用户集合，用来查询群用户详细信息
	 * @param int $gid
	 * @param int $role 可以删选用户角色，可单独选择某一角色，也可以多选array(0,1,2)
	 * @return array
	 */
	public function getMembersByGroups($gids,$role = -1);
	/**
	 * 获得某用户的群组集合
	 * @param int $uid
	 * @param int $role
	 * @return array
	 */
	public function getGroupsByMember($uid, $role = -1);
	/**
	 * 删除某群某用户
	 * @param int $gid
	 * @param int $uid
	 * @return int
	 */
	public function deleteByGroup($gid,$uid);
	/**
	 * 删除群内所有用户
	 * @param int $gid
	 * @return int
	 */
	public function deleteAllByGroup($gid);
    /**
	 * 获取群成员数量
	 * @param int $gid
	 * @return int
	 */
	public function getNumOfGroupMember($gid);
    /**
	 * 获取群成员数量
	 * @param int $gid
	 * @return int
	 */
	public function getLastMembersByGroup($gid,$uids,$page);
    /**
	 * 获取群成员数量
	 * @param int $gid
	 * @return int
	 */
	public function getMembersExceptSelfByGroup($gid,$self_uid,$page,$limit);
    /**
	 * 获取群成员分页列表
	 * @param int $gid
	 * @return int
	 */
    public function getGroupMembersByPage($gid, $page, $limit);
}