<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组扩展信息表原子Dao
 */
class Db_GroupExtend extends Db_Base implements GroupExtendInterface
{
	protected $table = "group_extend";
	
	public function create($array)
	{
		$fields = array("gid","member_counts","chat_enable","type");
		return parent::create($array, $fields);
	}
	
	public function update($gid, $array)
	{
		$fields = array("member_counts","chat_enable","type","invitation");
		return parent::update($gid, $array, 'gid', $fields);
	}
	
	public function findById($id)
	{
		return parent::findById($id, 'gid');
	}
	
	public function findByIds($ids)
	{
		$list = parent::findByIds($ids, 'gid');
		$array = array();
		foreach($list as $a){
			$array[$a['gid']] = $a;
		}
		return $array;
	}
	
	public function delete($id)
	{
		return parent::delete($id, 'gid');
	}
	
	public function setMemberInc($id, $value)
	{
		return parent::setInc($id, 'gid', "member_counts", intval($value));
	}
}