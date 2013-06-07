<?php
/*
 * 群组
 * title :
 * Created on 2012-07-30
 * @author yaohaiqi
 * discription : 群组配置文件Dao
 */
class Db_Config extends Db_Base implements ConfigInterface
{
	protected $table = "group_config";
	
	public function findById($name)
	{
        $sql = "SELECT value FROM ".$this->table." WHERE name = ? ";
        
		return $this->get($sql, array($name));
	}
}