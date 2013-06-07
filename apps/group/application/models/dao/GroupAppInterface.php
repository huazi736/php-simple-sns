<?php
/*
 * 群组
 * title :
 * Created on 2012-07-05
 * @author hexin
 * discription : 群组公司内部应用原子Dao接口
 */
interface GroupAPPInterface
{
	public function create($array);
	public function update($gid, $array);
	public function findById($id);
	public function findByIds($ids);
	public function delete($id);
	
	/**
	 * 查找所有内部应用
	 * @return
	 */
	public function findAll();
	
	/**
	 * 更新APP的使用数
	 * @param int $id APPID
	 * @param int $value
	 */
	public function setAppInc($id, $value);
	
	/**
	 * 获取不在这些已安装应用ids列表中的未安装应用列表
	 * @param max $ids
	 * @return array
	 */
	public function findNotInByIds($ids);
}