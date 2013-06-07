<?php
/*
 * 群组
 * title :
 * Created on 2012-07-04
 * @author hexin
 * discription : 子群基本表原子Dao
 */
class Db_SubGroupInfo extends Db_Base implements SubGroupInfoInterface
{
	protected $table = "group_sub_info";
	
	public function create($array)
	{
		$fields = array("gid","sid","name","description","creator","icon","create_time");
		$array['create_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function update($sid, $array)
	{
		$fields = array("name","description","icon","member_counts","update_time");
		$array['update_time'] = time();
		return parent::update($gid, $array, 'sid', $fields);
	}
	
	public function updateSid($id, $sid)
	{
		return parent::update($id, array('sid' => $sid));
	}
	
	public function findById($id)
	{
		return parent::findById($id, 'sid');
	}
	
	public function findByIds($ids)
	{
		return parent::findByIds($ids, 'sid');
	}
	
	public function delete($id)
	{
		return parent::delete($id, 'sid');
	}
	
	public function getMyGroups($uid){
		$sql = "SELECT * FROM ".$this->table." WHERE creator = ? ORDER BY sid DESC";
		return $this->getList($sql, array(intval($uid)));
	}
	
	public function ifExist($gid, $name)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE gid=? AND name=? ORDER BY id ASC";
		$group = $this->get($sql, array(intval($gid), $name));
		if(isset($group['sid'])) return $group['sid'];
		else return 0;
	}
	
	public function findByNames($gid, $names)
	{
		if(!is_array($names)) $names = array($names);
		$sql = "SELECT * FROM ".$this->table." WHERE gid=? AND name in('".implode("','",$names)."')";
		return $this->getList($sql, array(intval($gid)));
	}
	
	/**
	 * 根据群组ID查询子群信息
	 * 
	 * @param unknown_type $gid
	 * @param unknown_type $field
	 * @see ManageModel::kickOut( $gid, $uid ),quit( $gid, $uid )
	 */
	public function findByGid( $gid, $field )
	{
		$where = array ( 'gid' => $gid );
		return $this->db->from( $this->table )->where( $where )->select( $field )->get()->result_array();
	}
	
	public function setMemberInc($id, $value)
	{
		return parent::setInc($id, 'sid', "member_counts", $value);
	}
    
    public function findByIdsByType($ids)
	{
		if(!is_array($ids)) {
            $ids = array(intval($ids));
        }
		$sql = "SELECT * FROM ".$this->table." WHERE sid in('".implode("','",$ids)."') ORDER BY id DESC";
		return $this->getList($sql);
	}
    
    public function getNumOfMySubGroups($uid,$gid){
		$sql = "SELECT count('id') as subgroup_num FROM ".$this->table." WHERE gid=".$gid." and creator = ? ";
		return $this->get($sql, array(intval($uid)));
	} 
}