<?php
/*
 * 群组
 * title :
 * Created on 2012-07-07
 * @author hexin
 * discription : 群组相册表原子Dao
 */
class Db_GroupAlbum extends Db_Base implements GroupAlbumInterface
{
	protected $table = "group_album";
	
	public function create($array)
	{
		$fields = array("name","uid","gid","description","create_time","cover");
		$array['create_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function update($id, $array)
	{
		$fields = array("name","photo_count","description","a_sort","cover","update_time","is_delete");
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
		//return parent::delete($id, 'id');
		if(!is_array($id)) $id = array(intval($id));
		if(empty($Id)) return 1;
		$sql = "UPDATE ".$this->table." SET is_delete=".GroupConst::GROUP_DELETE." WHERE id in(".implode(',',$id).")";
		return $this->execute($sql);
	}
	
	public function findAllByGroup($gid)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE gid = ? AND is_delete = ".GroupConst::GROUP_NOT_DELETE." ORDER BY a_sort DESC, id DESC";
		return $this->getList($sql, array(intval($gid)));
	}
	
	public function setPhotoInc($id, $value)
	{
		return parent::setInc($id, 'id', "photo", intval($value));
	}
	
}