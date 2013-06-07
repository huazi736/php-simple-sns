<?php
/*
 * 群组
 * title :
 * Created on 2012-07-07
 * @author hexin
 * discription : 群组相册原子Dao接口
 */
interface GroupAPPInterface
{
	public function create($array);
	public function update($gid, $array);
	public function findById($id);
	public function findByIds($ids);
	public function delete($id);
	
	/**
	 * 查找群组内所有相册
	 * @param int gid 群号
	 * @return array
	 */
	public function findAllByGroup($gid);
	
	/**
	 * 更新相册的照片数
	 * @param int $id 相册号
	 * @param int $value
	 */
	public function setPhotoInc($id, $value);
}