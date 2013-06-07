<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组成员信息表原子Dao
 */
class Db_GroupMember extends Db_Base implements GroupMemberInterface
{
	protected $table = "group_member";
	
	public function create($array)
	{
		$fields = array("mid","create_time");
		$array['create_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function createMulti($array)
	{
		if(empty($array)) return true;
		$fields = array("mid","create_time");
		foreach($array as &$a) {
			$a['create_time'] = time();
		}
		return parent::createMulti($array, $fields);
	}
	
	public function update($uid, $array)
	{
		$fields = array("update_time");
		$array['update_time'] = time();
		return parent::update($uid, $array, 'mid', $fields);
	}
	
	public function findById($id)
	{
		return parent::findById($id, 'mid');
	}
	
	public function findByIds($ids)
	{
		return parent::findByIds($ids, 'mid');
	}
	
	public function delete($id)
	{
		return parent::delete($id, 'mid');
	}
}