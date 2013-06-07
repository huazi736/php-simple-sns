<?php
/*
 * 群组
 * title :
 * Created on 2012-07-05
 * @author hexin
 * discription : 群组公司内部应用表原子Dao
 */
class Db_GroupAPP extends Db_Base implements GroupAppInterface
{
	protected $table = "group_app";
	
	public function create($array)
	{
		$fields = array("name","creator","thumb","icon","description","url","create_time");
		$array['create_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function update($id, $array)
	{
		$fields = array("name","creator","thumb","icon","description","count");
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
	
	public function findAll()
	{
		$sql = "SELECT * FROM ".$this->table."where status=1 ORDER BY id DESC";
		return $this->getList($sql);
	}
	
	public function setAppInc($id, $value)
	{
		return parent::setInc($id, 'id', "count", intval($value));
	}
	
	public function findNotInByIds($ids)
	{
		if(!is_array($ids)) $ids = array(intval($ids));
		$sql = "SELECT * FROM ".$this->table.(!empty($ids)?" WHERE status=1 and id not in (".implode(',',$ids).")":"");
		return $this->getList($sql);
	}
}