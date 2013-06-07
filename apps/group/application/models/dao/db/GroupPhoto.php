<?php
/*
 * 群组
 * title :
 * Created on 2012-07-07
 * @author hexin
 * discription : 群组照片表原子Dao
 */
class Db_GroupPhoto extends Db_Base implements GroupPhotoInterface
{
	protected $table = "group_photo";
	
	public function create($array)
	{
		$fields = array("name","uid","groupname","description","create_time","filename","size","aid","type","p_sort");
		$array['create_time'] = time();
		return parent::create($array, $fields);
	}
	
	public function createMulti($array)
	{
		if(empty($array)) return true;
		$fields = array("name","uid","groupname","description","create_time","filename","size","aid","type","p_sort");
		foreach($array as &$a) {
			$a['create_time'] = time();
		}
		return parent::createMulti($array, $fields);
	}
	
	public function update($id, $array)
	{
		$fields = array("name","groupname","description","p_sort","filename","is_delete","size","type","comment_count");
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
	
	public function deleteByAlbum($aid)
	{
		$sql = "UPDATE ".$this->table." SET is_delete=".GroupConst::GROUP_DELETE." WHERE aid = ?";
		return $this->execute($sql);
	}
	
	public function findAllByAlbum($aid)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE aid = ? AND is_delete = ".GroupConst::GROUP_NOT_DELETE." ORDER BY p_sort DESC, id DESC";
		return $this->getList($sql, array(intval($aid)));
	}
	
	public function setCommentInc($id, $value)
	{
		return parent::setInc($id, 'id', "comment_count", intval($value));
	}
	
}