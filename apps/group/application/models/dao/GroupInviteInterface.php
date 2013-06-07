<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组邀请记录表原子Dao接口
 */
interface GroupInviteInterface
{
	public function create($array);
	public function update($gid, $array);
	public function updateMulti($ids, $array);
	public function findById($id);
	public function findByIds($ids);
	public function delete($id);
	
	public function createMulti($array);
	
	/**
	 * 查询是否邀请过某人
	 * @param int $gid
	 * @param int $uid
	 * @param int $from_uid
	 * @param int status
	 * @return array
	 */
	public function findByGroupByFrom($gid, $from_uid, $uids, $status = GroupConst::GROUP_PROCESSING_WAITTING);
	/**
	 * 根据gid查询及Uids集合查询未处理的邀请信息
	 * @param int $gid
	 * @param array $uids
	 * @return array
	 */
	public function findByGroupByUids($gid, $uids);
	
	/**
	 * 查询某人的所有未处理邀请信息
	 * @param int $uid
	 * @param int $limit
	 * @param int|null $lastId
	 * @return array
	 */
	public function findByUid($uid, $limit = 0, $lastId = null);
	
	/**
	 * 查询某群组的所有未处理邀请信息
	 * @param int $gid
	 * @return array
	 */
	public function findByGroup($gid);
	
	/**
	 * 根据gid查询及Uids集合查询已处理的同意的邀请信息
	 * @param int $gid
	 * @param array $uids
	 */
	public function findByGroupByUidsProcessed($gid, $uids);
	
	/**
	 * 根据gid,to_uid去掉非from_uid的邀请信息
	 * @param unknown_type $gid
	 * @param unknown_type $to_uid
	 * @param unknown_type $from_uid
	 */
	public function uniqueInvite($gid, $to_uid, $from_uid);
}