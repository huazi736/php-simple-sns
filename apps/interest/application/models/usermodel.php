<?php
class UserModel extends MY_Model
{
	
	function __construct(){
		parent::__construct();
		$this->mycache 	= $this->memcache;
		
	}
	
	public function getInfo()
	{
		$option['table'] = 'users';		
		return $this->find($option);		
	}
	
	public function getCount()
	{
		$result = $this->query('select count(*) as total from users');
		return $result[0]['total'];
	}
	
	public function getList()
	{
		return $this->query('select * from users limit 0,10');
	}
	
	public function updateInfo()
	{
		return $this->execute('update users set usr_lastlogin_time=' . time() . ' where usr_id = 5');
	}
}