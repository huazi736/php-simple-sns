<?php
//require_once EXTEND_PATH . 'core' . DS . 'DK_Model.php';
class MY_Model extends DK_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->init_db('user');
        $this->init_redis('user');
        $this->init_memcache('user');
        $this->init_mongodb('user');
    }
    
    
	/***
	 * 
	 * 重新选择数据库
	 * return true or false
	 * **/
	public function db_selectdb($database){
		
		return @mysql_select_db($database, $this->db->conn_id);
	}
    
	
	
	
	

	/**
	 * 插入数据
	 * $table		表
	 * $arr 		要插入的数据		array(key=>value) key 字段	 value 值
	 * $del_null	删除 arr 中空的 字段    ， 这里是因为  字段是数据型如果生成一个'' 数据插入进去    就会有错误
	 * 
	 * return  0 表示没有插入成功
	 * **/
	public function __insert($table , $arr, $del_null=true){
		if(!is_array($arr))	return 0;
		if($del_null){
			foreach($arr as $key=>$val){
				if($val===null || $val ==='')	unset($arr[$key]);	// if(!$val) 不能这样   因为 0 也会被删除 
			}
		}
		$sql 	= "INSERT INTO `{$table}`(`" . implode('`,`',array_keys($arr)) . "`)VALUES ('". implode("','",array_values($arr)) ."')";
		$id	= $this->db->query($sql);
		if($id){
			return mysql_insert_id();
		}else{
			return 0;
		}
		
	}
	
	/**
	 * 忽略重复  插入
	 * $table		表
	 * $arr 		要插入的数据		array(key=>value) key 字段	 value 值
	 * $del_null	删除 arr 中空的 字段    ， 这里是因为  字段是数据型如果生成一个'' 数据插入进去    就会有错误
	 * 
	 * return  0 表示没有插入成功
	 * **/
	public function __insert_ignore($table , $arr , $del_null=true){
		if(!is_array($arr)) return 0;
		if($del_null){
			foreach($arr as $key=>$val){
				if($val===null || $val ==='')	unset($arr[$key]);	// if(!$val) 不能这样   因为 0 也会被删除 
			}
		}
		$sql	= "INSERT IGNORE INTO `{$table}`(`" . implode('`,`',array_keys($arr)) . "`)VALUES ('". implode("','",array_values($arr)) ."')";
		$id	= $this->db->query($sql);
		if($id){
			return mysql_insert_id();
		}else{
			return 0;
		}
		
	}
	
	
	
	/**
	 * 更新数据
	 * $table 		表
	 * $arr			要插入的数据
	 * $is_one		是否只更新一条	true 只更新一条 	false 可以更新多条
	 * 
	 * 返回  	1,2     0 表示没有更新成功
	 * **/
	public function __update($table , $arr , $where=null , $is_one=false ){
		if(!is_array($arr)) return 0;
		
		$where_val	= "";
		if($where)	$where_val = " where ".$where;
		$limit_val 	= "";
		if($is_one)	$limit_val = " limit 1";
		
		$set_val	= $this->__create_update($arr);
		$sql		= "update {$table} set {$set_val} {$where_val} {$limit_val} ";
		return $this->db->query($sql);
		
	}
	
	
	
	/*
	 * 变换 生成 更新的 sql 语句  的内容
	 * $arr 	==> array('field'=>'val',....);
	 * return   ==> `field1`='val1',`field2`='val2'
	 */
	function __create_update($arr){
		//if(! is_array($arr)) return '';
		foreach($arr as $key=>$val){
			$set[] = " `{$key}`='{$val}' ";
		}
		return @ implode(',',$set);
	}
	
	
	
	
    
}