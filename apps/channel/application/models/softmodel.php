<?php



class SoftModel extends MY_Model{
	
	public $goods_page_size = 20;	// ÿһ�μ��ض�������

	
	
	function __construct(){
		parent::__construct();
		
	}
	

	//����������
	function addSoft($data) {
		return $this->db->insert('soft', $data);
	}
		
	
    //��������޸�
	function editSoft($data,$id) {
		$this->db->where('id',$id);
		return $this->db->update('soft', $data);
	}

    //����б���Ϣ
	public function getList($where){
	}
	
    //ָ������б���Ϣ
	public function getInfo($id){
	}	
}



