<?php
/**
 * @author heyuejuan
 * 兴趣分类数据模型
 * **/

class Interest_categoryModel extends MY_Model{
	
	function __construct(){
		parent::__construct();
		$this->mycache 	= $this->memcache;
		
	}
	
	/**
	 * 插入主分类
	 * $arr 	表 key=>value
	 * **/
	public function insert_category_main($arr){
		/*
		$arr['imname'] = 'dfr';
		$arr['sort'] = '999';
		$arr['is_system'] = '0';
		$arr['is_display'] = '1';
		*/
		return $this->__insert('interest_category_main', $arr);
		
		
	}
	
	public function get_category_imid_one($imid){
		$sql 	= "SELECT * FROM `interest_category_main` where imid={$imid} ";
		return $this->db->query($sql)->row_array();
	}
	
	/**
	 * 修改 主分类
	 * $id		imid
	 * $arr   	表 key=>value	要更新的数据
	 * 
	 * **/
	public function update_category_main($imid , $arr){
		//$arr['is_display'] = 1;
		return $this->__update('interest_category_main', $arr, "imid='{$imid}' ");
		
	}
	
	
	/**
	 * 删除主分类
	 * $id		imid
	 * 
	 * **/
	public function delete_category_main($imid){
		$sql = "delete from `interest_category_main` where imid='{$imid}' limit 1";
		return $this->db->query($sql);
		
	}
	
	
	/**
	 * 查询主类   最多取  150个主分类
	 * **/
	public function get_category_main(){
		$mem_key = 'get_category_main_1256';
		$result	= $this->mycache->get($mem_key);
		echo $mem_key;
		if(!$result){
			$sql = "SELECT * FROM `interest_category_main` where is_display='1' ORDER BY `sort` ASC limit 100";
			$result	= $this->db->query($sql)->result_array();
			$this->mycache->set( $mem_key, $result , 1200);
		}
		
		return $result;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * 插入  二级分类
	 * $arr		表 key=>value
	 * **/
	public function insert_category($arr){
		/*
		$arr['iname']	= 'sadf';
		$arr['imid']	= '1';
		$arr['sort']	= '';
		$arr['is_system']	= '1';
		$arr['is_list']	= '0';
		$arr['is_hot']	= '0';
		$arr['is_display']	= '1';
		*/
		return $this->__insert('interest_category', $arr);
		
	}
	
	// 获得一个二级分类
	public function get_category_iid_one($iid){
		$sql = "SELECT * FROM `interest_category` where iid={$iid} limit 1";
		return $this->db->query($sql)->row_array();
	}
	
	
	/**
	 * 更新  二级分类
	 * **/
	public function update_category($iid ,$arr){
		
		return $this->__update('interest_category', $arr," iid='{$iid}' ");
	}
	
	
	
	public function delete_category($iid){
		$sql 	= " delete from `interest_category` where iid='{$iid}' limit 1";
		return $this->db->query($sql);
	}
	
	
	/**
	 * 查询  在列表中显示的    二级分类   
	 * $imid	主类
	 * */
	public function get_category($imid){
		$mem_key	= 'get_category_'.$imid;
		$mem_key;
		$result 	= $this->mycache->get($mem_key);
		if( !is_array($result) || empty ($result)){
			$sql 	= "SELECT * FROM `interest_category` where `imid`='{$imid}' and `is_list`=1 and is_display=1 order by sort asc limit 150";
			$result	= $this->db->query($sql)->result_array();
			
			echo $sql;
			$this->mycache->set($mem_key, $result , 800);
		}
		return $result;
	}
	
	
	
	
	
	
	
	
	
	
	
/************	应用模块	start	*************/
	function get_apps_main($limit=0){
		$limit_var	= "";
		if($limit>=0){
			$limit_var	= " limit {$limit} ";
			
		}
		$sql 	= "SELECT * FROM `interest_category_main` where is_display=1 {$limit_var} ";
		$result	= $this->db->query($sql)->result_array();
		return $result;
		
	}
	
/************	应用模块	end	*************/
	
	
	
	
	
	
	
	
	
	
	
	
	
	
/******  生成  拼音   start   **********/ 
	// 生成  分类的拼音
	public function create_pinyin(){
		$this->load->library('pinyin');
		$sql = "SELECT iid,iname,iname_pinyin FROM `interest_category` WHERE `iname_pinyin` = '' LIMIT 0 , 100";
		$result	= $this->db->query($sql)->result_array();
		if(is_array($result)){
			foreach($result as $arr){
				$pinyin	= $this->pinyin->convert($arr['iname']);
				if($pinyin=='') $pinyin = $arr['iname'];
				$sql 	= "update `interest_category` set iname_pinyin='".$pinyin."' where iid='".$arr['iid']."' limit 1";
				$this->db->query($sql);
			}
		}
		return true;
	}
	
	public function create_entry_pinyin(){
		$this->load->library('pinyin');
		// $sql = "SELECT  eid ,name,name_pinyin,caput_pinyin FROM `apps_entry` WHERE `caput_pinyin` = '' LIMIT 0 , 500";
		$sql = "SELECT  eid ,name,name_pinyin,caput_pinyin FROM `apps_entry` WHERE `caput_pinyin` is NULL LIMIT 0 , 500";
		$result	= $this->db->query($sql)->result_array();
		if(is_array($result)){
			foreach($result as $arr){
				$pinyin		= $this->pinyin->convert($arr['name']);
				if($pinyin=='') $pinyin = $arr['name'];
				$caput_pinyin = strtolower( substr($pinyin, 0 ,1 ) );
				$sql 	= "update `apps_entry` set name_pinyin='".$pinyin."', caput_pinyin='".$caput_pinyin."' where eid='".$arr['eid']."' limit 1";
				$this->db->query($sql);
			}
		}
		return true;
		
	}
	
	public function create_category_tag(){
		$this->load->library('pinyin');
		$sql = "SELECT  tid ,tname FROM `interest_category_tag` WHERE `tname_pinyin`='' LIMIT 0 , 500";
		$result	= $this->db->query($sql)->result_array();
		if( is_array($result) ){
			foreach($result as $arr){
				$pinyin		= $this->pinyin->convert($arr['tname']);
				$sql 	= "update `interest_category_tag` set tname_pinyin='".$pinyin."' where tid='".$arr['tid']."' limit 1";
				$this->db->query($sql);
			}
		}
	}
/******  生成  拼音   end   **********/ 	
	
	
	
	

}