<?php



class SoftModel extends MY_Model{
	
	public $goods_page_size = 20;	// 每一次加载多少数据

	
	
	function __construct(){
		parent::__construct();
		
	}
	

	//软件数据入库
	function addSoft($data) {
		return $this->db->insert('soft', $data);
	}
		
	
    //软件数据修改
	function editSoft($data,$id) {
		$this->db->where('id',$id);
		return $this->db->update('soft', $data);
	}

    //软件列表信息
	public function getList($where){
	}
	
    //指定软件列表信息
	public function getInfo($id){
	}	
}



