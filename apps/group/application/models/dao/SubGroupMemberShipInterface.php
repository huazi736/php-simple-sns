<?php
/*
 * 群组
 * title :
 * Created on 2012-07-04
 * @author hexin
 * discription : 子群与成员关系表原子Dao接口
 */
interface SubGroupMemberShipInterface
{
	public function create($array);
	public function update($sid, $array);
	public function findById($id);
	public function findByIds($ids);
	public function orderBy($key, $desc = 'DESC');
	
	public function createMulti($array);
	/**
	 * 获得sid和uid关联的数据
	 * @param $sid
	 * @param $uid
	 */
	public function findByGroupByUser($sid, $uid);
	/**
	 * 获得sid和uids关联的数据
	 * @param int $sid
	 * @param int $uids
	 * @return array
	 */
	public function findByGroupByUsers($sid, $uids);
	/**
	 * 检查用户是否已经加入该群组，是返回true，否返回false
	 * @param int $sid
	 * @param int $uid
	 * @return boolean
	 */
	public function checkMemberExist($sid, $uid);
	/**
	 * 检查传入的uid集合那些已经存在gid这个群组中
	 * @param int $sid
	 * @param array $uids
	 * @return array
	 */
	public function checkMembersExist($sid, $uids);
	/**
	 * 获得群内用户集合，用来查询群用户详细信息
	 * @param int $sid
	 * @param int $role 可以删选用户角色，可单独选择某一角色，也可以多选array(0,1,2)
	 * @return array
	 */
	public function getMembersByGroup($sid,$page,$role = -1);
	/**
	 * 获得某些群内用户集合，用来查询群用户详细信息
	 * @param int $sid
	 * @param int $role 可以删选用户角色，可单独选择某一角色，也可以多选array(0,1,2)
	 * @return array
	 */
	public function getMembersByGroups($sids, $role = -1);
	/**
	 * 获得某用户的群组集合
	 * @param int $uid
	 * @param int $role
	 * @return array
	 */
	public function getGroupsByMember($uid, $role = -1);
	/**
	 * 删除某群某用户
	 * @param int $sid
	 * @param int $uid
	 * @return int
	 */
	public function deleteByGroup($sid,$uid);
	/**
	 * 删除群内所有用户
	 * @param int $sid
	 * @return int
	 */
	public function deleteAllByGroup($sid);
    /**
	 * 获取子群成员数量
	 * @param int $gid
	 * @return int
	 */
	public function getNumOfSubGroupMember($sid);
    /**
	 * 获得群内用户集合uid，用来查询群用户详细信息
	 * @param int $sid
	 * @param int $role 可以删选用户角色，可单独选择某一角色，也可以多选array(0,1,2)
	 * @return array
	 */
	public function getUidOfMembersByGroup($sid);
    /**
	 * 获取群成员
	 * @param int $gid
	 * @return int
	 */
	public function getMembersExceptSelfByGroup($gid,$self_uid,$page,$keyword,$limit);
}