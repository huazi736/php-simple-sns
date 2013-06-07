<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组基本信息表原子Dao
 */
class Db_GroupInfo extends Db_Base implements GroupInfoInterface
{
	protected $table = "group_info";
	
	public function create($array)
	{
		$fields = array("gid","name","description","source_type","creator","icon","create_time");
		$array['create_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function update($gid, $array)
	{
		$fields = array("name","description","icon","update_time");
		$array['update_time'] = time();
		return parent::update($gid, $array, 'gid', $fields);
	}
	
	public function updateGid($id, $gid)
	{
		return parent::update($id, array('gid' => $gid), 'id', array('gid'));
	}
	
	public function findById($id)
	{
		return parent::findById($id, 'gid');
	}
	
	public function findByIds($ids)
	{
		return parent::findByIds($ids, 'gid');
	}
	
	public function delete($id)
	{
		return parent::delete($id, 'gid');
	}
	
	public function getMyGroups($uid){
		$sql = "SELECT * FROM ".$this->table." WHERE creator = ? ORDER BY gid DESC";
		return $this->getList($sql, array(intval($uid)));
	}
	
	public function ifExist($name, $creator, $source_type = GroupConst::GROUP_TYPE_FRIEND)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE name=? AND creator=? AND source_type = ? ORDER BY id ASC";
		$group = $this->get($sql, array($name, intval($creator), $source_type));
		if(isset($group['gid'])) return $group['gid'];
		else return 0;
	}
	
	public function findUniqueByName($name, $source_type = GroupConst::GROUP_TYPE_FANS)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE name=?";
		return $this->get($sql, array($name));
	}
	
	public function findByNames($name, $source_type = GroupConst::GROUP_TYPE_CUSTOM, $friend_ids = array())
	{
		if(!is_array($name)) $name = array($name);
		if(!is_array($friend_ids)) $friend_ids = array(intval($friend_ids));
		$sql = "SELECT * FROM ".$this->table." WHERE name in('".implode("','",$name)."')" . (empty($friend_ids)? "" : " AND creator in (" . implode(',', $friend_ids) . ")");
		return $this->getList($sql);
	}
	
	public function findByIdsByType($ids, $source_type = GroupConst::GROUP_TYPE_CUSTOM)
	{
		if(!is_array($ids)) $ids = array(intval($ids));
		$sql = "SELECT * FROM ".$this->table." WHERE gid in('".implode("','",$ids)."') AND source_type = ? ORDER BY id DESC";
		return $this->getList($sql, array($source_type));
	}
    
    public function getNumOfMyGroups($uid){
		$sql = "SELECT count('id') as group_num FROM ".$this->table." WHERE creator = ? ";
		return $this->get($sql, array(intval($uid)));
	}
	
	public function findExistGroupByName($name, $uid, $source_type = GroupConst::GROUP_TYPE_CUSTOM)
	{
		$sql = "SELECT g.* FROM ".$this->table." AS g LEFT JOIN group_membership AS m ON g.gid = m.gid WHERE g.name = ? AND g.source_type = ? AND m.uid = ? ORDER BY g.id ASC LIMIT 1";
		return $this->get($sql, array($name, $source_type, intval($uid)));
	}
}