<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组与成员关系表原子Dao
 */
class Db_GroupMemberShip extends Db_Base implements GroupMemberShipInterface
{
	protected $table = "group_membership";
	
	public function create($array)
	{
		$fields = array("gid","uid","position");
		return parent::create($array, $fields);
	}
	
	public function createMulti($array)
	{
		$fields = array("gid","uid","position");
		return parent::createMulti($array, $fields);
	}
	
	public function update($id, $array)
	{
		$fields = array("position");
		return parent::update($id, $array, 'id', $fields);
	}
	
	public function findById($uid)
	{
		return parent::findById($uid, 'id');
	}
	
	public function findByGid( $gid )
	{
		return parent::findByIds( $gid, 'gid');
	}
	
	public function findByIds($ids)
	{
		return parent::findByIds($ids, 'id');
	}
	
	public function findByGroupByUser($gid, $uid)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE gid = ? AND uid = ?";
		return $this->get($sql, array(intval($gid), intval($uid)));
	}
	
	public function findByGroupByUsers($gid, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "SELECT * FROM ".$this->table." WHERE gid = ? AND uid in (".implode(',', $uids).")";
		$list = $this->getList($sql, array(intval($gid)));
		$array = array();
		foreach($list as $a){
			$array[$a['uid']] = $a;
		}
		return $array;
	}
	
	public function checkMemberExist($gid, $uid)
	{
		$sql = "SELECT id FROM ".$this->table." WHERE gid = ? AND uid = ?";
		$member = $this->get($sql, array(intval($gid), intval($uid)));
		if(empty($member))
			return false;
		else
			return true;
	}
	
	public function checkMembersExist($gid, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "SELECT uid FROM ".$this->table." WHERE gid = ? AND uid in (".implode(',', $uids).")";
		$list = $this->getList($sql, array(intval($gid)));
		$array = array();
		foreach($list as $a){
			$array[] = $a['uid'];
		}
		return $array;
	}
	
	public function getMembersByGroup($gid, $role = -1)
	{
        $sql = "SELECT * FROM ".$this->table." WHERE gid = ?";
		$parameters = array();
		if(is_array($role) && !empty($role)) {
			$sql .= " AND position in (".implode(',', $role).")";
		}
		if(intval($role) != -1) {
			$sql .= " AND postion = " . intval($role);
		}
		
		return  $this->getList($sql, array(intval($gid)));
	}
	
	public function getMembersByGroups($gids, $role = -1)
	{
		if(!is_array($gids)) $gids = array(intval($gids));
		$sql = "SELECT * FROM ".$this->table." WHERE gid in (".implode(',',$gids).")";
		$parameters = array();
		if(is_array($role) && !empty($role)) {
			$sql .= " AND position in (".implode(',', $role).")";
		}
		if(intval($role) != -1) {
			$sql .= " AND postion = " . intval($role);
		}
		
		return  $this->getList($sql);
	}
	
	public function getGroupsByMember($uid, $role = -1)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE uid = ?";
		$parameters = array();
		if(is_array($role) && !empty($role)) {
			$sql .= " AND position in (".implode(',', $role).")";
		}
		if(intval($role) != -1) {
			$sql .= " AND postion = " . intval($role);
		}
		
		return $this->getList($sql, array(intval($uid)));
	}
	
	public function deleteByGroup($gid,$uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "DELETE FROM ".$this->table." WHERE gid = ? AND uid in (".implode(',', $uids).")";
		return $this->execute($sql, array(intval($gid)));
	}
	
	public function deleteAllByGroup($gid)
	{
		$sql = "DELETE FROM ".$this->table." WHERE gid = ?";
		return $this->execute($sql, array(intval($gid)));
	}
    
    public function getNumOfGroupMember($gid)
	{
		$sql = "SELECT count(id) as num FROM ".$this->table." WHERE gid = ?";
		return $this->execute($sql, array(intval($gid)));
	}
    
    public function getLastMembersByGroup($gid, $uids, $page=1, $role = -1)
	{
        $limit = 25;
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM ".$this->table." WHERE uid not in('".implode("','",$uids)."') and gid = ? limit ".$offset .",".$limit;
		$parameters = array();
		if(is_array($role) && !empty($role)) {
			$sql .= " AND position in (".implode(',', $role).")";
		}
		if(intval($role) != -1) {
			$sql .= " AND postion = " . intval($role);
		}
		return  $this->getList($sql, array(intval($gid)));
	}
    
    public function getMembersExceptSelfByGroup($gid,$self_uid,$page,$limit=25)
	{
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM ".$this->table." WHERE uid !=".$self_uid." and gid = ? limit ".$offset .",".$limit;
		return  $this->getList($sql, array(intval($gid)));
	}
	
	/**
	 * 
	 * @param unknown_type $gid
	 * @param unknown_type $offset
	 * @param unknown_type $limit
	 */
	public function getMembersShipByGroupId( $gid, $offset, $limit )
	{
		$sql = "SELECT * FROM " . $this->table . " WHERE gid = ? and position = 0 limit " . $offset . "," . $limit;
		return $this->getList( $sql, array ( intval( $gid ) ) );
	}
    
    public function getGroupMembersByPage($gid, $page=1, $limit=20)
	{
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM ".$this->table." WHERE gid = ? limit ".$offset.",".$limit;
		return  $this->getList($sql, array(intval($gid)));
	}
}