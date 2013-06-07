<?php
// 


// do_call('interest','Index','get_category_main',array('124598') )

/**
 * @author heyuejuan
 * 调用  数据 兴趣分类数据
 * **/

class InterestsModel extends MY_Model{
	
	function __construct(){
		parent::__construct();
		$interestdb	= config_item('interestdb');
		
		$this->db_selectdb($interestdb['database']);
		
		$this->mycache  = $this->memcache;
		
		
	}
	
	// 获得大类的数据
	public function get_category_main_all(){
		$mem_key	= "category_main_all_345";
		echo $mem_key;
		$result	= $this->mycache->get($mem_key);
		if(!is_array($result)){
			//$result	= call_soap('interest','Index','get_category_main');
			$sql 	= "select * from `interest_category_main` where is_display='1' ORDER BY `sort` ASC limit 100";
			$result = $this->db->query($sql)->result_array();
			$this->mycache->set($mem_key, $result , 3600);	// 一个小时
		}
		return $result;
	}
	
	
	public function get_category_small($imid){
		$mem_key	= "category_small_684_".$imid;
		echo $mem_key;
		$result		= $this->mycache->get($mem_key);
		if(!is_array($result) ){
			// $result	= call_soap('interest','Index','get_category_small' , array($imid));
			$sql 	= "SELECT * FROM `interest_category` where imid='{$imid}' and is_display='1' and is_list='1' ORDER BY iname_pinyin ASC limit 150";
			$result	= $this->db->query($sql)->result_array();
			$this->mycache->set($mem_key, $result , rand(1800,3600));	// 半个小时到一个小时之间
		}
		
		print_r($result);
		return $result;
	}
	
	
	
	// 获得一条一级分类
	public function get_category_main_one($imid){
		$sql = "SELECT * FROM `interest_category_main` where imid='{$imid}' limit 1";
		echo $sql;
		$result	= $this->db->query($sql)->result_array();
		return $result[0];
	}
	
	
	
	// 获得二级分类
	public function  get_category_one($imid , $iname){
		$sql = "SELECT * FROM `interest_category` where iname='{$iname}' and imid='{$imid}' limit 1";
		$result	= $this->db->query($sql)->result_array();
		return @$result[0];
	}
	
	/**
	 * 插入  二级分类
	 * $arr		表 key=>value
	 * **/
	public function insert_category($arr){
		
		return $this->__insert('interest_category', $arr);
	}
	
	// 递增  分类的  网页数
	public function increase_category_stat($iid){
		$sql 	= "update `interest_category` set stat=stat+1 where iid='{$iid}' limit 1";
		return $this->db->query($sql);
	}
	
	// 50 时 就在  列表里显示
	public function  update_50_to_list($iid){
		$sql = "update `interest_category` set `is_list`='1' where iid='{$iid}' and stat>=50 limit 1";
		return $this->db->query($sql);
		
	}
	
	/***
	 * 递增分类数据
	 * iid		二级分类id
	 * count	网页数
	 */
	public function count_category_stat($iid,$count){
		$sql 	= "update `interest_category` set stat='{$count}' where iid='{$iid}' limit 1";
		return $this->db->query($sql);
	}
	
	
	// 添加
	//public function  add_category($imid , $category_name){
		
		// return call_soap('interest','Index','add' , array($imid , $category_name));
	//}
	
	/*
	// 网页数加1
	public function web_increase($iid){
		// return call_soap('interest','Index','web_increase' , array($iid));
	}
	*/
	
	/*
	public function web_count($iid,$count){
		// return call_soap('interest','Index','web_count' , array($iid , $count));
	}
	*/
	
	
	
/*******	标签  start	*********/
		
	// 获得分类 兴趣下的   标签
	public function category_tag_iid( $imid , $iid){
		$page_size	= 20;
		if($iid<=0){	// 没有传小分类的。。。就找大分类下的
			$sql	= "select tid,tname,count,imid,iid from interest_category_tag where imid={$imid} ORDER BY `count` DESC limit {$page_size} ";
		}else{
			$sql	= "select tid,tname,count,imid,iid from interest_category_tag where imid={$imid} and iid={$iid} ORDER BY `count` DESC limit {$page_size} ";
		}
		$result	= $this->db->query($sql)->result_array();
		$result_count	= count($result);
		if( $result_count < 5 ){ 		// 少于5个标签   找大分类加
			$page_merge_count	= $page_size - $result_count;
			
			$sql 	= "select tid,tname,count,imid,iid from interest_category_tag where imid={$imid} ORDER BY `count` DESC limit {$page_merge_count} ";
			$result_merge	= $this->db->query($sql)->result_array();
			$result	= array_merge($result , $result_merge );
			
		}
		return $result;
		
	}

	/**
	 * 添加   分类的标签
	 * 返回  标签 id
	 * **/
	public function add_category_tag($imid , $iid , $tname ,$tname_pinyin){
		$inst	= "INSERT INTO `interest_category_tag` (`tid`, `tname`, `tname_pinyin`, `count`, `imid`, `iid`) VALUES
(null, '{$tname}', '{$tname_pinyin}', 1, {$imid}, {$iid}) on duplicate key update `count`=`count`+1";
		$this->db->query($inst);
		$sql 	= "select * from `interest_category_tag` where imid={$imid} and iid={$iid} and tname='{$tname}' ";
		$result	= $this->db->query($sql)->row_array();
		return @$result['tid'];
	}
	
	/**
	 * 分类标签数据递增
	 * count 递增
	 * */ 
	public function category_tag_count_increase($imid , $iid , $tname ){
		$sql = "update `interest_category_tag` set `count`=`count`+1 where imid={$imid} and iid={$iid} and tname='{$tname}' ";
		return $this->db->query($sql);
		
	}
	

	
	
/*******	标签  end 	***********/
	
	
	
	
/*******	多级分类   start	*********/
	// 获得大类的数据
	public function get_category_level_one(){
		//$mem_key	= "get_category_one_34345";
		$result	= "";
		if(!is_array($result)){
			//$result	= call_soap('interest','Index','get_category_main');
			$sql 	= "select imid as id , imname as name , 1 as level , 0 as has_son , is_local , app_icon , create_url from `interest_category_main` where is_display='1' ORDER BY `sort` ASC limit 100";
			$result = $this->db->query($sql)->result_array();
			//$this->mycache->set($mem_key, $result , 3600);	// 一个小时
		}
		return $result;
	}
	
	public function get_category_level_two($imid){
		$mem_key	= "category_small_6ttt_".$imid;
		
		echo $mem_key;
		
		$result		= $this->mycache->get($mem_key);
		if(!is_array($result) || empty ($result) ){
			$sql 	= "SELECT iid as id , iname as name , 2 as level , 1 as has_son FROM `interest_category` where imid='{$imid}' and is_display='1' ORDER BY iname_pinyin ASC limit 100";
			$result	= $this->db->query($sql)->result_array();
			$this->mycache->set($mem_key, $result , rand(1800,3600));	// 半个小时到一个小时之间
		}
		return $result;
	}
	
	// 获得三级分类数据		创建网页时用到
	public function get_category_level_three($iid){
		
		$sql = "SELECT eid as id , name as name , 3 as level , if(piid_type=0 , 1 ,piid_type) as has_son FROM `apps_entry` where iid={$iid} and piid=0 and display=0 ORDER BY `fans_count` DESC limit 50 ";
		return $this->db->query($sql)->result_array();
		
	}
	// 四级与四级以后 		创建网页时用到
	public function get_category_level_more($eid , $level){
		$sql = "SELECT eid as id , name as name , {$level} as level , if(piid_type=0 , 1 ,piid_type) as has_son FROM `apps_entry` where piid={$eid} and display=0 ORDER BY `fans_count` DESC limit 50 ";
		
		return $this->db->query($sql)->result_array();
	}
	
	
	
	/***	查询分类数据		***/
	
	// 一级
	public function get_category_level_name_one($id){
		$sql 	= "SELECT imid as id , imname as name , 1 as level , 0 as has_son , is_local FROM `interest_category_main` where imid={$id} and is_display='1' limit 1"; 
		return $this->db->query($sql)->row_array();
	}
	// 二级
	public function get_category_level_name_two($id){
		$sql 	= "SELECT iid as id , iname as name , 2 as level , 1 as has_son FROM `interest_category` where iid={$id} and is_display='1' limit 1"; 
		return $this->db->query($sql)->row_array();
	}
	// 四级，五级，六级...
	public function get_category_level_name_more($id,$level){
		$sql 	= "SELECT eid as id , name as name , {$level} as level , if(piid_type=0 , 1 ,piid_type) as has_son FROM `apps_entry` where eid={$id} and display=0  limit 1";
		return $this->db->query($sql)->row_array();
	}
	
	
/*******	多级分类   end 	*********/
	
	
	
}