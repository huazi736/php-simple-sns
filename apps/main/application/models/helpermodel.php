<?php
class HelperModel extends DK_Model{
	public function __construct()
	{
		parent::__construct();
		$this->init_db('helpdb');
		$this->init_memcache();
	}
	/*
	 * 获取全部分类
	 */
	public function getAllCats() 
	{
		return $this->db->order_by('orderby', 'ASC')->get('category')->result_array();
	}
	
	/*
	 * 获取所有分类  结构二维数组
	 */
	public function getCatsById($id) 
	{
		
		return $this->db->where(array('parent_id'=>$id))->order_by('orderby', 'ASC')->get('category')->result_array();
	}
	public function gettree() 
	{		

		$data=$this->db->select('id,title,parent_id')->where(array('parent_id'=>0))->order_by('orderby', 'ASC')->get('category')->result_array();
		foreach($data as $k=>$v){
			$data[$k]['ccats']=$this->getCatsById($v['id']);
		}
		return $data;
	//	return $this->db->group_by('parent_id')->order_by('orderby', 'ASC')->get('category')->result_array();
	}
	

	/*
	 * 获取指定分类下的 列表
	*/
	public function getListsByCatid($catid)
	{
		$this->db->where(array('cid'=>$catid,'type'=>'help'));
		$this->db->order_by('orderby', 'asc');
		return $this->db->get('article')->result_array();
	}
	
	/*
	 * 变量 的memcatch 缓存
	*/
	public function saveDataToMemcache($key,$data,$tt=600)
	{
		if(empty($data)) return false;
		return $this->memcache->set('helperdata',$data);
	}
	
	public function getDataByCache($key)
	{
		if(empty($key)) return false;
		return $this->memcache->get($key);
	}

	
}