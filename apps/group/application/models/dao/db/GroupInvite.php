<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组邀请表原子Dao接口
 */
class Db_GroupInvite extends Db_Base implements GroupInviteInterface
{
	protected $table = "group_invite";
	
	public function create($array)
	{
		$fields = array("from_uid","to_uid","gid","status","accept_result","invite_time","accept_time");
		$array['invite_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function createMulti($array)
	{
		if(empty($array)) return true;
		$fields = array("from_uid","to_uid","gid","status","accept_result","invite_time","accept_time");
		foreach($array as &$a) {
			$a['invite_time'] = time();
		}
		return parent::createMulti($array, $fields);
	}
	
	public function update($id, $array)
	{
		$fields = array("status","accept_result","invite_time","accept_time");
		$array['accept_time'] = time();
		return parent::update($id, $array, 'id', $fields);
	}
	
	public function updateMulti($ids, $array)
	{
		$fields = array("status","accept_result","invite_time","accept_time");
		if(!is_array($ids)) $ids = array(intval($ids));
		if(empty($ids)) return false;
		return parent::updateMulti($ids, $array, 'id', $fields);
	}
	
	public function findById($id)
	{
		return parent::findById($id, 'id');
	}
	
	public function findByIds($ids)
	{
		return parent::findByIds($ids, 'id');
	}
	
	public function findByGroupByFrom($gid, $from_uid, $uids, $status = GroupConst::GROUP_PROCESSING_WAITTING)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "SELECT * FROM ".$this->table." WHERE gid = ? AND to_uid in(" . implode(",", $uids) . ") AND from_uid = ? AND status = " . $status;
		return $this->getList($sql, array(intval($gid), intval($from_uid)));
	}
	
	public function findByGroupByUids($gid, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "SELECT * FROM ".$this->table." WHERE gid = ? AND to_uid in(" . implode(",", $uids) . ") AND status = " . GroupConst::GROUP_PROCESSING_WAITTING . " ORDER BY id DESC";
		return $this->getList($sql, array(intval($gid)));
	}
	
	public function findByUid($uid, $limit = 0, $lastId = null)
	{
		$where = $limit_sql = '';
		if(isset($lastId)){
			$where = intval($lastId)>0 ? " AND id < ". intval($lastId) : '';
			$limit_sql = " LIMIT ".intval($limit);
		}
		$sql = "SELECT * FROM ".$this->table." WHERE to_uid = ? AND from_uid <> 0 AND status = " . GroupConst::GROUP_PROCESSING_WAITTING . $where . " ORDER BY id DESC" . $limit_sql;
		return $this->getList($sql, array(intval($uid)));
	}
	
	public function findByGroup($gid)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE gid = ? AND status = " . GroupConst::GROUP_PROCESSING_WAITTING;
		return $this->getList($sql, array(intval($gid)));
	}
	
	public function findByGroupByUidsProcessed($gid, $uids)
	{
		if(!is_array($uids)) $uids = array(intval($uids));
		if(empty($uids)) return array();
		$sql = "SELECT * FROM ".$this->table." WHERE gid = ? AND to_uid in(" . implode(",", $uids) . ") AND status = " . GroupConst::GROUP_PROCESSING_SUCCESS . " AND accept_result = '" . GroupConst::GROUP_INVITE_ACCEPT . "' ORDER BY id DESC";
		return $this->getList($sql, array(intval($gid)));
	}
	
	public function uniqueInvite($gid, $to_uid, $from_uid)
	{
		$sql = "DELETE FROM " . $this->table . " WHERE gid = ? AND to_uid = ? AND from_uid <> ?";
		return $this->execute($sql, array(intval($gid), intval($to_uid), intval($from_uid)));
	}
	
	public function delete($id)
	{
		return parent::delete($id, 'id');
	}
	
	/**
	 * 根据群组ID删除对应数据
	 * @param unknown_type $gid
	 */
	public function deleteGid( $gid )
	{
		return parent::delete( $gid, 'gid' );
	}
	
}