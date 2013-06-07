<?php
class ArticleModel extends DK_Model{
	public function __construct()
	{
		parent::__construct();
		$this->init_db('helpdb');
		$this->init_memcache();
	}




	/*
	 * 获取指定分类下的 列表
	*/
	public function getListsByType($type='about')
	{	
		$data=$this->getDataByCache('aboutdata');
		if(!$data){
			$this->db->where(array('type'=>$type));
			$data=$this->db->get('article')->result_array();
			$this->saveDataToMemcache('aboutdata',$data,20);
		}
		return $data;
	}
	
	/*
	 * 获取指定id的 文章
	*/
	public function getArticleByid($aid)
	{
		$where=array('id'=>$aid);
		$this->db->where($where);
		$data=$this->db->get('article')->result_array();
		return $data;
	}
	
	/*
	 * 变量 的memcatch 缓存
	*/
	public function saveDataToMemcache($key,$data,$tt=600)
	{
		if(empty($data)) return false;
		return $this->memcache->set('aboutdata',$data);
	}
	/*
	 * 变量 的memcatch 取值
	*/
	public function getDataByCache($key)
	{
		if(empty($key)) return false;
		return $this->memcache->get($key);
	}

	
}