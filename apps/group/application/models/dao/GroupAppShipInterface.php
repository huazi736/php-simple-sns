<?php
/*
 * 群组
 * title :
 * Created on 2012-07-05
 * @author hexin
 * discription : 群组与应用关系原子Dao接口
 */
interface GroupAPPShipInterface
{
	public function create($array);
	public function update($gid, $array);
	public function findById($id);
	public function findByIds($ids);
	public function delete($id);
	
	/**
	 * 获取某群组的所有有效应用的id集合
	 * @param $gid
	 * @return array
	 */
	public function findAllIdsByGroup($gid);
	
	/**
	 * 从群组中删除某应用
	 * @param int $gid 群号
	 * @param int $uid 群主ID
	 * @param int $appId 应用ID
	 */
	public function deleteByGroup($gid, $uid, $appId);
}