<?php
/*
 * 群组
 * title :
 * Created on 2012-07-05
 * @author hexin
 * discription : 群组与应用关系表原子Dao
 */
class Db_GroupAPPShip extends Db_Base implements GroupAppShipInterface
{
	protected $table = "group_appship";
	
	public function create($array)
	{
		$fields = array("gid","uid","aid","create_time");
		$array['create_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function update($id, $array)
	{
		$fields = array("gid","uid","aid","update_time");
		$array['update_time'] = time();
		return parent::update($id, $array, 'id', $fields);
	}
	
	public function findById($id)
	{
		return parent::findById($id, 'id');
	}
	
	public function findByIds($ids)
	{
		return parent::findByIds($ids, 'id');
	}
	
	public function delete($id)
	{
		return parent::delete($id, 'id');
	}
	
	public function deleteGid( $gid )
	{
		return parent::delete( $gid, 'gid' );
	}
	
	public function findAllIdsByGroup($gid)
	{
		$sql = "SELECT aid FROM ".$this->table." WHERE gid=?";
		$list = $this->getList($sql, array(intval($gid)));
		$array = array();
		foreach($list as $l) {
			$array[] = $l['aid'];
		}
		return $array;
	}
	
	public function deleteByGroup($gid, $uid, $appId)
	{
		$sql = "DELETE FROM ".$this->table." WHERE gid=? AND uid=? AND aid=?";
		return $this->execute($sql, array(intval($gid), intval($uid), intval($appId)));
	}
}