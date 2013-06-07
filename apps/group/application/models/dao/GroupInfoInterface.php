<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组基本信息表原子Dao接口
 */
interface GroupInfoInterface
{
	public function create($array);
	public function update($gid, $array);
	public function findById($id);
	public function findByIds($ids);
	public function delete($id);
	public function orderBY($key, $desc = 'DESC');
	/**
	 * 获得我自创建的所有群信息
	 * @param int $uid
	 * @return array
	 */
	public function getMyGroups($uid);
	/**
	 * 更新gid
	 * @param int $id
	 * @param int $gid
	 * @return int
	 */
	public function updateGid($id, $gid);
	
	/**
	 * 判断群组是否存在，并返回gid
	 * @param $name
	 * @param $creator
	 * @param $source_type
	 * @return int
	 */
	public function ifExist($name, $creator, $source_type = GroupConst::GROUP_TYPE_FRIEND);
	
	/**
	 * 根据名称获得群组数据集合
	 * @param max $name
	 * @param string $source_type
	 * $return array
	 */
	public function findByNames($name, $source_type = GroupConst::GROUP_TYPE_CUSTOM, $friend_ids = array());
	
	/**
	 * 获取指定id集合，指定来源分类的群组信息
	 * @param max $ids
	 * @param string $source_type
	 * @return array
	 */
	public function findByIdsByType($ids, $source_type = GroupConst::GROUP_TYPE_CUSTOM);
    
    /**
	 * 获得我自创建的群数量
	 * @param int $uid
	 * @return array
	 */
	public function getNumOfMyGroups($uid);
}