<?php
/*
 * 群组
 * title :
 * Created on 2012-07-04
 * @author hexin
 * discription : 群组基本信息表原子Dao接口
 */
interface SubGroupInfoInterface
{
	public function create($array);
	public function update($sid, $array);
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
	 * @param int $sid
	 * @return int
	 */
	public function updateSid($id, $sid);
	
	/**
	 * 判断群组是否存在，并返回自群号sid
	 * @param $gid 群号
	 * @param $name
	 * @return int
	 */
	public function ifExist($gid, $name);
	
	/**
	 * 根据名称获得群组数据集合
	 * @param int $gid
	 * @param max $names
	 * $return array
	 */
	public function findByNames($gid, $names);
	
	/**
	 * 更新子群成员数量
	 * @param int $sid
	 * @param int $value
	 */
	public function setMemberInc($sid, $value);
    
    /**
	 * 获得我自创建的子群数量
	 * @param int $uid
	 * @return array
	 */
	public function getNumOfMySubGroups($uid,$gid);
}