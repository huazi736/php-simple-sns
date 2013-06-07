<?php
/*
 * 群组
 * title :
 * Created on 2012-07-07
 * @author hexin
 * discription : 群组照片原子Dao接口
 */
interface GroupPhotoInterface
{
	public function create($array);
	public function update($gid, $array);
	public function findById($id);
	public function findByIds($ids);
	public function delete($id);
	
	/**
	 * 查找相册内所有照片
	 * @return
	 */
	public function findAllByAlbum($aid);
	
	public function createMulti($array);
	
	/**
	 * 更新评论数
	 * @param int $id
	 * @param int $value
	 */
	public function setCommentInc($id, $value);
	
	/**
	 * 删除某相册的所有照片
	 * @param int $aid
	 */
	public function deleteByAlbum($aid);
}