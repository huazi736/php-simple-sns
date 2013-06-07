<?php
/*
 * 群组
 * title :
 * Created on 2012-07-04
 * @author hexin
 * discription : 子群与成员关系表原子Dao
 */
class Db_SubGroupMemberShip extends Db_Base implements SubGroupMemberShipInterface
{
	protected $table = "group_sub_membership";
	
	public function create($array)
	{
		$fields = array("sid","uid","position","create_time");
		$array['create_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function createMulti($array)
	{
		$fields = array("sid","uid","position");
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
	
	public function findByIds($ids)
	{
		return parent::findByIds($ids, 'id');
	}
	
	public function findByGroupByUser($sid, $uid)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE sid = ? AND uid = ?";
		return $this->get($sql, array(intval($sid), intval($uid)));
	}
	
	public function findByGroupByUsers($sid, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "SELECT * FROM ".$this->table." WHERE sid = ? AND uid in (".implode(',', $uids).")";
		$list = $this->getList($sql, array(intval($sid)));
		$array = array();
		foreach($list as $a){
			$array[$a['uid']] = $a;
		}
		return $array;
	}
	
	public function checkMemberExist($sid, $uid)
	{
		$sql = "SELECT id FROM ".$this->table." WHERE sid = ? AND uid = ?";
		$member = $this->get($sql, array(intval($sid), intval($uid)));
		if(empty($member))
			return false;
		else
			return true;
	}
	
	public function checkMembersExist($sid, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "SELECT uid FROM ".$this->table." WHERE sid = ? AND uid in (".implode(',', $uids).")";
		$list = $this->getList($sql, array(intval($sid)));
		$array = array();
		foreach($list as $a){
			$array[] = $a['uid'];
		}
		return $array;
	}
	
	public function getMembersByGroup($sid,$page = 1, $role = -1)
	{
		$limit = 24;
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM ".$this->table." WHERE sid = ? limit ".$offset .",".$limit;
		$parameters = array();
		if(is_array($role) && !empty($role)) {
			$sql .= " AND position in (".implode(',', $role).")";
		}
		if(intval($role) != -1) {
			$sql .= " AND position = " . intval($role);
		}
		
		return  $this->getList($sql, array(intval($sid)));
	}
	
	public function getMembersByGroups($sids, $role = -1)
	{
		if(!is_array($sids)) $sids = array(intval($sids));
		$sql = "SELECT * FROM ".$this->table." WHERE sid in (".implode(',',$sids).")";
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
	
	public function deleteByGroup($sid,$uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "DELETE FROM ".$this->table." WHERE sid = ? AND uid in (".implode(',', $uids).")";
		return $this->execute($sql, array(intval($sid)));
	}
	
	public function deleteAllByGroup($sid)
	{
		$sql = "DELETE FROM ".$this->table." WHERE sid = ?";
		return $this->execute($sql, array(intval($sid)));
	}
    
    public function getNumOfSubGroupMember($sid)
	{
		$sql = "SELECT count(id) as num FROM ".$this->table." WHERE sid = ?";
		return $this->execute($sql, array(intval($sid)));
	}
    
    public function getUidOfMembersByGroup($sid)
	{
        $uids = array();
        $sql = "SELECT uid FROM ".$this->table." WHERE sid = ?";
        $temp = $this->getList($sql, array(intval($sid)));
        if($temp){
            foreach($temp as $k=>$v){
                $uids[] = $v['uid'];
            }
        }
		return $uids;
	}
    
    public function getMembersExceptSelfByGroup($gid,$self_uid,$page,$keyword,$limit=25)
	{
        $offset = ($page - 1) * $limit;
        if($keyword != ''){
            $sql = "SELECT * FROM group_membership WHERE uid !=".$self_uid." and gid = ?";
        }else{
            $sql = "SELECT * FROM group_membership WHERE uid !=".$self_uid." and gid = ? limit ".$offset .",".$limit;
        }
		return  $this->getList($sql, array(intval($gid)));
	}
}