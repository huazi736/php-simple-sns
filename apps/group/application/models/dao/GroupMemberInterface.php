<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组成员信息表原子Dao接口
 */
interface GroupMemberInterface
{
	public function create($array);
	public function update($gid, $array);
	public function findById($id);
	public function findByIds($ids);
	public function delete($id);
	
	public function createMulti($array);
}