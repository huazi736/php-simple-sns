<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : Dao的底层方法
 */
class Db_Base
{
	protected $db;
	protected $orderBy = '';
	protected $groupBy = '';
	protected $limit = '';
	
	public function __construct()
	{
		//$CI =& get_instance();
		//$CI->load->database('group', true, true);
		//$this->db = $CI->db;
		$loader = load_class('Loader','core');
		$this->db = $loader->database('group',true,true);
	}
	
	/**
	 * 执行一条SQL查询语句
	 * 
	 * @param string $sql
	 */
	protected function execute($sql, array $parameters = null, $callback = null)
	{
		$rs = $this->db->query($sql, $parameters);
		//echo $sql."<br />\n";
		//print_r($parameters);
		$this->orderBy = $this->groupBy = $this->limit = '';
		if(isset($callback))
		{
			return $callback($rs);
		}
		else
		{
			if(substr_compare(trim($sql), 'SELECT', 0, 6, true) === 0)
				return $rs->result_array();
			elseif(substr_compare(trim($sql), 'INSERT', 0, 6, true) === 0)
				return $this->db->insert_id();
			else
				return $this->db->affected_rows();
		};
	}
	
	/**
	 * 获得单条记录
	 * 
	 * @param string $sql
	 * @param array $parameters
	 * @param Closure $callback
	 */
	protected function get($sql, array $parameters = null, $callback = null)
	{
	    return $this->execute($sql, $parameters, function($result) use($callback){
	    	if(isset($callback))
	    		return $callback($result);
	    	else
	    		return $result->row_array();
	    });
	}
	
	/**
	 * 获得多条记录
	 * 
	 * @param string $sql
	 * @param array $parameters
	 * @param Closure $callback
	 */
	protected function getList($sql, array $parameters = null, $callback = null)
	{
	    return $this->execute($sql, $parameters, function($result) use($callback){
	    	if(isset($callback))
	    		return $callback($result);
	    	else
	    		return $result->result_array();
	    });
	}
	
	/**
	 * 获得某个字段的值，例如获得多条记录的数据行数
	 * 
	 * @param string $sql
	 * @param array $parameters
	 * @param int $num
	 * @param Closure $callback
	 */
	protected function getByColumn($sql, array $parameters = null, $num=0, $callback = null)
	{
	    return $this->execute($sql, $parameters, function($result) use($callback){
	    	if(isset($callback))
	    		return $callback($result);
	    	else
	    		return $result->row_array($num);
	    });
	}
	
	//以下是最简单的CURD
	protected function findById($id, $key)
	{
		$sql = "SELECT * FROM ".$this->table." WHERE $key = ?";
		return $this->get($sql, array(intval($id)));
	}
	
	protected function findByIds($ids, $key)
	{
		if(!is_array($ids)) $ids = array(intval($ids));
		if(empty($ids)) return array();
		$sql = "SELECT * FROM ".$this->table." WHERE $key IN (".implode(',', $ids).") ".$this->groupBy.$this->orderBy.$this->limit;
		return $this->getList($sql);
	}
	
	protected function create($array, array $fields)
	{
		$insert_keys = $insert_values = '';
		$parameters = array();
		foreach($array as $k => $v)
		{
			if(in_array($k,$fields))
			{
				$insert_keys .= "`$k`,";
				$insert_values .= "?,";
				$parameters[] = $v;
			}
		}
		$sql = "INSERT INTO ".$this->table."(". trim($insert_keys, ',') .") VALUES(". trim($insert_values, ',') .")";
		return $this->execute($sql, $parameters);
	}
	
	protected function createMulti($array, array $fields)
	{
		if(empty($array)) return true;
		$insert_keys = $insert_values = $temp = '';
		$parameters = array();
		foreach($array as $row)
		{
			$insert_values .= "(";
			foreach($row as $k => $v)
			{
				if(in_array($k,$fields))
				{
					if(empty($insert_keys))
						$temp .= "`$k`,";
					$insert_values .= "?,";
					$parameters[] = $v;
				}
			}
			if(empty($insert_keys))
				$insert_keys = trim($temp, ',');
			$insert_values = substr($insert_values,0, -1)."),";
		}
		$sql = "INSERT INTO ".$this->table."(". $insert_keys .") VALUES ". trim($insert_values, ',');
		return $this->execute($sql, $parameters);
	}
	
	protected function update($id, $array, $key, array $fields)
	{
		$updates = '';
		$parameters = array();
		foreach($array as $k => $v)
		{
			if(in_array($k,$fields))
			{
				$updates .= "`$k` = ?,";
				$parameters[] = $v;
			}
		}
		$sql = "UPDATE ".$this->table." SET ". trim($updates, ',') ." WHERE $key = ?" ;
		$parameters[] = intval($id);
		return $this->execute($sql, $parameters);
	}
	
	protected function updateMulti($ids, $array, $key, array $fields)
	{
		if(empty($array)) return true;
		if(!is_array($ids)) $ids = array(intval($ids));
		if(empty($ids)) return false;
		$updates = '';
		$parameters = array();
		foreach($array as $k => $v)
		{
			if(in_array($k,$fields))
			{
				$updates .= "`$k` = ?,";
				$parameters[] = $v;
			}
		}
		$sql = "UPDATE ".$this->table." SET ". trim($updates, ',') ." WHERE $key in (" . implode(',', $ids) . ")" ;
		return $this->execute($sql, $parameters);
	}
	
	protected function delete($ids, $key)
	{
		if(!is_array($ids)) $ids = array(intval($ids));
		if(empty($ids)) return array();
		$sql = "DELETE FROM ".$this->table." WHERE $key IN (".implode(',', $ids).")";
		return $this->execute($sql);
	}
	
	protected function setInc($id, $key, $fields, $action = 1)
	{
		if(!is_array($fields)) $fields = array($fields);
		if(empty($fields)) return 0;
		$sql_1 = '';
		$parameters = array();
		foreach($fields as $f => $v) {
			if(is_string($f)) {
				$sql_1 = "`$f` = `$f` + ?,";
				$parameters[] = $v;
			} else {
				$sql_1 = "`$v` = `$v` + ?,";
				$parameters[] = intval($action);
			}
		}
		$sql = "UPDATE " . $this->table . " SET ".trim($sql_1, ',')." WHERE $key = " . intval($id);
		return $this->execute($sql, $parameters);
	}
	
	public function orderBy($key, $desc = 'DESC')
	{
		if($this->orderBy) {
			$this->orderBy .= " , $key $desc";
		} else {
			$this->orderBy = " ORDER BY $key $desc";
		}
		return $this;
	}
	
	public function groupBy($key)
	{
		if($this->groupBy) {
			$this->groupBy .= " , $key";
		} else {
			$this->groupBy = " GROUP BY $key";
		}
		return $this;
	}
	
	public function limit($limit, $start = '')
	{
		$this->limit = " LIMIT $limit " . ($start === '' ? '' : ', '.$start);
		return $this;
	}
}