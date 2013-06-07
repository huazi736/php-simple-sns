<?php



class Apps_infoModel extends MY_Model{
	
	public $interest_page_size;
	public $interest_home_first_page_size;
	public $interest_hot_category_page;
	public $interest_top_page_size;
	
	public $interest_list_size;
	
	
	function __construct(){
		parent::__construct();
		$this->mycache 	= $this->memcache;
		
		$this->interest_page_size = 100;	// 单个分页里的数量
		
		$this->interest_home_first_page_size= 200;	// 首页加载的条数
		
		$this->interest_hot_category_page = 10;		// 热门分类下最热门的 分类数
		
		$this->interest_top_page_size	= 100;	// top 加载条数
		// PAGE_SIZE_DEFAULT
		
		$this->interest_list_size	= 20;	// 网页列表  20 个 分类
				
	}
	
	
	
	function get_category_web($web_id){
		$sql = "SELECT * FROM `apps_info` where aid='{$web_id}' limit 1 ";
		$result	= $this->db->query($sql)->row_array();
		return $result;
	}
	
	// 同名的  都查询出来
	function get_homonymy_web($name , $page=1){
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->interest_list_size;
		$sql = "select a1.* , a2.iname from (
					select * from `apps_info` where name like '{$name}' order by fans_count DESC LIMIT {$limit_page} ,".$this->interest_list_size." 
				) as a1 , `interest_category` as a2 where a1.iid=a2.iid ";
		
		return $this->db->query($sql)->result_array();
		
	}
	
	
	// 获得 分类下  10 个最热门的分类
	function hot_category_web($imid,$iid){
		$mem_key = "hot_category_tte_".$imid.'_'.$iid;
		
		$result	= $this->mycache->get($mem_key);
		if(!$result){
			// $sql = "select * from `apps_info` where imid='{$imid}' and iid='{$iid}' ORDER BY `fans_count` DESC limit ".$this->interest_hot_category_page;
			$sql = "select a.aid ,a.uid ,a.name ,a.name_pinyin, a.imid , a.fans_count, a.create_time, b.iid  from `apps_info_category` as b, `apps_info` as a  where b.imid='{$imid}' and a.display=0 and b.iid='{$iid}' and a.aid=b.aid ORDER BY a.`fans_count` DESC , `name_pinyin` ASC limit ".$this->interest_hot_category_page;
			
			$result	= $this->db->query($sql)->result_array();
			$this->mycache->set( $mem_key, $result , 1200);
		}
		return $result;
	}
	
	
	
	// 获得 第一页的数据
	function get_pinyin_weba_page1($caput_pinyin , $imid , $iid ){
		$caput_pinyin = strtolower($caput_pinyin);
		$mem_key = "get_pinyin_weba_page1_".$caput_pinyin.'_'.$imid.'_'.$iid;
		$result	= $this->mycache->get($mem_key);
		if(! $result ){
			$limit 	= $this->interest_home_first_page_size;	// 长度
			// $sql 	= "SELECT * FROM `apps_info` WHERE `caput_pinyin`='{$caput_pinyin}' and iid='{$iid}' and imid='{$imid}'  LIMIT 0 ,".$limit;
			$sql 	= "SELECT a.aid ,a.uid ,a.name ,a.name_pinyin, a.imid , a.fans_count, a.create_time, b.iid 
						FROM `apps_info` as a , `apps_info_category` as b 
						WHERE a.`caput_pinyin`='{$caput_pinyin}' and a.imid='{$imid}' and a.display=0 and a.aid=b.aid and b.iid='{$iid}' ORDER BY `fans_count` DESC , `name_pinyin` ASC  LIMIT 0 ,".$limit;
			$result	= $this->db->query($sql)->result_array();
			if( count($result)<$limit ){
				$this->mycache->set( $mem_key, $result , rand(7200,14400));
			}
		}
		return $result;
	}
	// 获得 第一页的数据  0-9的数据
	public function get_pinyin_weba_page1_0to9($imid , $iid ){
		$mem_key = "get_pinyin_weba_page1_0to9".'_'.$imid.'_'.$iid;
		$result	= $this->mycache->get($mem_key);
		if(! $result ){
			$limit 	= $this->interest_home_first_page_size;	// 长度
			// $sql 	= "SELECT * FROM `apps_info` WHERE `caput_pinyin` >= '0' AND `caput_pinyin` <= '9' and iid='{$iid}' and imid='{$imid}' LIMIT 0 ,".$limit;
			$sql 	= "SELECT a.aid ,a.uid ,a.name ,a.name_pinyin, a.imid , a.fans_count, a.create_time, b.iid 
						FROM `apps_info` as a , `apps_info_category` as b 
						WHERE a.`caput_pinyin` >= '0' AND a.`caput_pinyin` <= '9' and  a.imid='{$imid}' and a.display=0 and a.aid=b.aid and b.iid='{$iid}' ORDER BY `fans_count` DESC , `name_pinyin` ASC  LIMIT 0 ,".$limit;
			
			$result	= $this->db->query($sql)->result_array();
			if( count($result)<$limit ){
				$this->mycache->set( $mem_key, $result , rand(7200,14400));
			}
		}
		return $result;
	}
	
	
	
	function get_pinyin_web($caput_pinyin , $imid , $iid , $page=1){
		$caput_pinyin = strtolower($caput_pinyin);
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->interest_page_size;
		// $sql = "SELECT * FROM `apps_info` WHERE `caput_pinyin`='{$caput_pinyin}' and iid='{$iid}' and imid='{$imid}' LIMIT {$limit_page} ,".$this->interest_page_size;
		$sql = "SELECT * FROM `apps_info` as a , `apps_info_category` as b 
			WHERE a.`caput_pinyin`='{$caput_pinyin}' and a.imid='{$imid}' and a.aid=b.aid and a.display=0 and b.iid='{$iid}' ORDER BY `fans_count` DESC , `name_pinyin` ASC  LIMIT {$limit_page} ,".$this->interest_page_size;
		
		$result	= $this->db->query($sql)->result_array();
		return $result;
	}
	function get_pinyin_weba_0to9($imid , $iid , $page=1){
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->interest_page_size;
		// $sql = "SELECT * FROM `apps_info` WHERE `caput_pinyin` >= '0' AND `caput_pinyin` <= '9' and iid='{$iid}' and imid='{$imid}' LIMIT {$limit_page} ,".$this->interest_page_size;
		$sql = "SELECT * FROM `apps_info` as a , `apps_info_category` as b 
			WHERE a.`caput_pinyin` >= '0' AND a.`caput_pinyin` <= '9' and a.imid='{$imid}' and a.aid=b.aid and a.display=0 and b.iid='{$iid}' ORDER BY `fans_count` DESC , `name_pinyin` ASC  LIMIT {$limit_page} ,".$this->interest_page_size;
		
		
		$result	= $this->db->query($sql)->result_array();
		return $result;
	}
	
	
	
	
	/*
	 * 加 20		首页 top 的 数据
	 */ 
	function get_pinyin_web_top($caput_pinyin , $imid , $iid , $page=1){
		$caput_pinyin = strtolower($caput_pinyin);
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= ($page * $this->interest_top_page_size);
		
		if($limit_page==0){	// 第一次 取 80个
			$this->interest_top_page_size = $this->interest_top_page_size - $this->interest_home_first_page_size;
			$limit_page = $this->interest_home_first_page_size;
		}
		// $sql 	= "SELECT * FROM `apps_info` WHERE `caput_pinyin`='{$caput_pinyin}' and iid='{$iid}' and imid='{$imid}'  LIMIT {$limit_page} ,".$this->interest_top_page_size;
		
		$sql 	= "SELECT * FROM `apps_info` as a , `apps_info_category` as b 
			WHERE a.`caput_pinyin`='{$caput_pinyin}' and a.imid='{$imid}' and a.aid=b.aid and a.display=0 and b.iid='{$iid}' ORDER BY `fans_count` DESC , `name_pinyin` ASC LIMIT {$limit_page} ,".$this->interest_top_page_size;
		
		$result	= $this->db->query($sql)->result_array();
		return $result;
		
	}
	// 加 20   	首页 top 的 数据
	function get_pinyin_web_top0to9($imid , $iid , $page=1){
		$page--;
		if($page<=0) $page = 0;
		//$limit_page	= $this->interest_home_first_page_size + ($page * $this->interest_top_page_size);
		// $sql 	= "select * from `apps_info` where `caput_pinyin` >= '0' AND `caput_pinyin` <= '9' and iid='{$iid}' and imid='{$imid}'  LIMIT {$limit_page} ,".$this->interest_top_page_size;
		$limit_page	= ($page * $this->interest_top_page_size);
		if($limit_page==0){	// 第一次 取 80个
			$this->interest_top_page_size = $this->interest_top_page_size - $this->interest_home_first_page_size;
			$limit_page = $this->interest_home_first_page_size;
		}
		
		
		$sql 	= "SELECT * FROM `apps_info` as a , `apps_info_category` as b 
			WHERE a.`caput_pinyin` >= '0' AND a.`caput_pinyin` <= '9' and a.imid='{$imid}' and a.aid=b.aid and a.display=0 and b.iid='{$iid}' ORDER BY `fans_count` DESC , `name_pinyin` ASC LIMIT {$limit_page} ,".$this->interest_top_page_size;
		
		$result = $this->db->query($sql)->result_array();
		return $result;
	}
	

	
	
	
	
	
	
	
/***********/
	// 获得三级分类的数据	
	function get_entry_group($iid , $limit=50){
		var_dump($iid);
		if(intval($iid)<=0) return "";
		$mem_key = "get_entry_group_".$iid.'_'.$limit;
		$result	= $this->mycache->get($mem_key);
		echo $mem_key;
		
		if( !$result ){
			$limit_var 	= "";
			if($limit<=0){
				$limit_var = " limit {$limit} "; 
			}
			$sql = "SELECT * FROM `apps_entry` where iid={$iid} and piid=0 and display=0 ORDER BY `eid` ASC  {$limit_var} ";
			
			echo $sql;
			
			$result	= $this->db->query($sql)->result_array();
			$this->mycache->set( $mem_key, $result , rand(300,600));
		}
		return $result;
		
	}
	
	function get_entry_group_eid( $eid , $limit=50	){
		if(intval($eid)<=0 ) return "";
		$mem_key = "get_entry_group_eid_".$eid.'_'.$limit;
		$result	= $this->mycache->get($mem_key);
		
		if( !$result ){
			$limit_var 	= "";
			if($limit<=0){
				$limit_var = " limit {$limit} "; 
			}
			$sql = "SELECT * FROM `apps_entry` where piid={$eid} and display=0 ORDER BY `eid` ASC {$limit_var} ";
			
			$result	= $this->db->query($sql)->result_array();
			$this->mycache->set( $mem_key, $result , rand(300,600));
		}
		return $result;
	}
	
	/**
	 * 
	 * @param int $imid
	 * @param int $iid
	 * @param char $group
	 */
	public function get_shoot_elements($imid,$iid,$group='a')
	{
		$ret = array();
		if($imid >0 && $iid >0){
			$mem_key = 'get_shoot_'.$iid.'_'.$group;
			$result = $this->mycache->get($mem_key);
			if(!$result){
				$sql = "select eid,imid,iid,name from `apps_entry` where imid={$imid} and iid={$iid} 
						and caput_pinyin='".$group."' and display=0";
				$result = $this->db->query($sql)->result_array();
			}
			$ret = array_chunk($result, 50);
		}
		return $ret;
	}
	/*
	 * 加 20		首页 top 的 数据
	 */ 
	function get_entry_lemma_tow($eid, $page=1){

		$page--;
		if($page<=0) $page = 0;
		$limit_page	= ($page * $this->interest_top_page_size);
		
		if($limit_page==0){	// 第一次 取 80个
			$this->interest_top_page_size = $this->interest_top_page_size - $this->interest_home_first_page_size;
			$limit_page = $this->interest_home_first_page_size;
		}
		// $sql 	= "SELECT * FROM `apps_info` WHERE `caput_pinyin`='{$caput_pinyin}' and iid='{$iid}' and imid='{$imid}'  LIMIT {$limit_page} ,".$this->interest_top_page_size;
		
		$sql 	= "SELECT * FROM `apps_entry` where piid={$eid} and display=0 ORDER BY `fans_count` DESC  LIMIT {$limit_page} ,".$this->interest_top_page_size;
		
		$result	= $this->db->query($sql)->result_array();
		return $result;
		
	}
	
	
	// 获得 第一页的数据
	function get_entry_lemma_page1($eid){
		$mem_key = "get_entry_lemma_page1_".$eid;
		$result	= $this->mycache->get($mem_key);
		$result = "";
		if(! $result ){
			$limit 	= $this->interest_home_first_page_size;	// 长度
			
			$sql 	= "SELECT * FROM `apps_entry` where piid={$eid} and display=0 ORDER BY `fans_count` DESC limit {$limit} ";
			$result	= $this->db->query($sql)->result_array();
			if( count($result)<$limit ){
				$this->mycache->set( $mem_key, $result , rand(600,1200));
			}
		}
		return $result;
	}
	
	/***
	 * 获得   apps_info 下 eid 相同的数据
	 * $eid		分类id
	 * $page	页数
	 * $area	区域id	-1不查询区域
	 * **/
	function get_apps_info_eid($eid , $page , $area=-1){
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->interest_list_size;
		
		$where	= "";
		if($area>0) $where .= " and town=".$area;
		
		$sql = "select a1.* , a2.iname from (
					select * from `apps_info` where eid='{$eid}' and display=0 {$where} order by fans_count DESC LIMIT {$limit_page} , ".$this->interest_list_size."
				) as a1 left join `interest_category` as a2 on (a1.iid=a2.iid) ";
		return $this->db->query($sql)->result_array();
	}
	
	/***
	 * 获得   apps_info 下 eid 相同的数据
	 * $eid		分类id
	 * $page	页数
	 * $area	区域id	-1不查询区域
	 * **/
	function get_apps_info_iid($iid , $page, $area=-1){
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->interest_list_size;
		
		$where	= "";
		if($area>0) $where .= " and town=".$area;
		
		$sql = "select a1.* , a2.iname from (
					select * from `apps_info` where iid='{$iid}' and eid=0 and display=0 {$where} order by fans_count DESC LIMIT {$limit_page} , ".$this->interest_list_size."
				) as a1 left join `interest_category` as a2 on (a1.iid=a2.iid) ";
		return $this->db->query($sql)->result_array();
	}
	
	/***
	 * 获得   apps_info 下 eid 相同的数据
	 * $eid		分类id
	 * $page	页数
	 * $area	区域id	-1不查询区域
	 * **/
	function get_apps_info_imid($imid , $page, $area=-1){
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->interest_list_size;
		
		$where	= "";
		if($area>0) $where .= " and town=".$area;
		
		$sql = "select a1.* , a2.iname from (
					select * from `apps_info` where eid='{$imid}' and eid=0 and iid=0 and display=0 {$where} order by fans_count DESC LIMIT {$limit_page} , ".$this->interest_list_size."
				) as a1 left join `interest_category` as a2 on (a1.iid=a2.iid) ";
		return $this->db->query($sql)->result_array();
	}
	
	
	// 获得  一条记录
	function get_category_entry_web($eid){
		$sql = "SELECT * FROM `apps_entry` where eid='{$eid}'  limit 1 ";
		
		$result	= $this->db->query($sql)->row_array();
		return $result;
	}
	
/***********/
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	// 获得 第一页的数据
	function get_pinyin_entry_weba_page1($caput_pinyin , $imid , $iid ){
		$caput_pinyin = strtolower($caput_pinyin);
		$mem_key = "get_pinyin_entry_weba_page1_".$caput_pinyin.'_'.$imid.'_'.$iid;
		$result	= $this->mycache->get($mem_key);
		
		if(! $result ){
			$limit 	= $this->interest_home_first_page_size;	// 长度
			// $sql 	= "SELECT * FROM `apps_info` WHERE `caput_pinyin`='{$caput_pinyin}' and iid='{$iid}' and imid='{$imid}'  LIMIT 0 ,".$limit;
			$sql 	= "SELECT a.eid  , a.aid ,a.uid ,a.name , a.iid , a.name_pinyin, a.imid , a.fans_count, a.create_time 
						FROM `apps_entry` as a 
						WHERE a.`caput_pinyin`='{$caput_pinyin}' and a.imid='{$imid}' and a.iid='{$iid}' and a.display=0  ORDER BY `fans_count` DESC , a.`aid_count` DESC , `name_pinyin` ASC  LIMIT 0 ,".$limit;
			
			$result	= $this->db->query($sql)->result_array();
			if( count($result)<$limit ){
				$this->mycache->set( $mem_key, $result , rand(1800,7200));
			}
		}
		return $result;
	}
	
	// 获得 第一页的数据  0-9的数据
	public function get_pinyin_entry_weba_page1_0to9($imid , $iid ){
		$mem_key = "get_pinyin_entry_weba_page1_0to9".'_'.$imid.'_'.$iid;
		$result	= $this->mycache->get($mem_key);
		if(! $result ){
			$limit 	= $this->interest_home_first_page_size;	// 长度
			// $sql 	= "SELECT * FROM `apps_info` WHERE `caput_pinyin` >= '0' AND `caput_pinyin` <= '9' and iid='{$iid}' and imid='{$imid}' LIMIT 0 ,".$limit;
			$sql 	= "SELECT a.eid , a.aid ,a.uid ,a.name ,a.name_pinyin, a.imid , a.fans_count, a.create_time, a.iid 
						FROM `apps_entry` as a 
						WHERE a.`caput_pinyin` >= '0' AND a.`caput_pinyin` <= '9' and a.iid='{$iid}'  and  a.imid='{$imid}' and a.display=0 ORDER BY `fans_count` DESC , a.`aid_count` DESC , `name_pinyin` ASC  LIMIT 0 ,".$limit;
			
			$result	= $this->db->query($sql)->result_array();
			if( count($result)<$limit ){
				$this->mycache->set( $mem_key, $result , rand(1800,7200));
			}
		}
		return $result;
	}
	
	
	function get_pinyin_entry_weba_0to9($imid , $iid , $page=1){ 
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->interest_page_size;
		// $sql = "SELECT * FROM `apps_info` WHERE `caput_pinyin` >= '0' AND `caput_pinyin` <= '9' and iid='{$iid}' and imid='{$imid}' LIMIT {$limit_page} ,".$this->interest_page_size;
		$sql = "SELECT * FROM `apps_entry` as a 
			WHERE a.`caput_pinyin` >= '0' AND a.`caput_pinyin` <= '9' and a.iid='{$iid}' and a.imid='{$imid}' and a.display=0 ORDER BY `fans_count` DESC , a.`aid_count` DESC , `name_pinyin` ASC  LIMIT {$limit_page} ,".$this->interest_page_size;
		
		
		$result	= $this->db->query($sql)->result_array();
		return $result;
	}
	function get_pinyin_entry_web($caput_pinyin , $imid , $iid , $page=1){
		$caput_pinyin = strtolower($caput_pinyin);
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->interest_page_size;
		// $sql = "SELECT * FROM `apps_info` WHERE `caput_pinyin`='{$caput_pinyin}' and iid='{$iid}' and imid='{$imid}' LIMIT {$limit_page} ,".$this->interest_page_size;
		$sql = "SELECT * FROM `apps_entry` as a 
			WHERE a.`caput_pinyin`='{$caput_pinyin}' and a.iid='{$iid}' and a.imid='{$imid}' and a.display=0 ORDER BY `fans_count` DESC , a.`aid_count` DESC , `name_pinyin` ASC  LIMIT {$limit_page} ,".$this->interest_page_size;
		
		$result	= $this->db->query($sql)->result_array();
		return $result;
	}
	
	
	
	// 加 20   	首页 top 的 数据
	function get_pinyin_entry_web_top0to9($imid , $iid , $page=1){
		$page--;
		if($page<=0) $page = 0;
		//$limit_page	= $this->interest_home_first_page_size + ($page * $this->interest_top_page_size);
		// $sql 	= "select * from `apps_info` where `caput_pinyin` >= '0' AND `caput_pinyin` <= '9' and iid='{$iid}' and imid='{$imid}'  LIMIT {$limit_page} ,".$this->interest_top_page_size;
		$limit_page	= ($page * $this->interest_top_page_size);
		if($limit_page==0){	// 第一次 取 80个
			$this->interest_top_page_size = $this->interest_top_page_size - $this->interest_home_first_page_size;
			$limit_page = $this->interest_home_first_page_size;
		}
		
		
		$sql 	= "SELECT * FROM `apps_entry` as a 
			WHERE a.`caput_pinyin` >= '0' AND a.`caput_pinyin` <= '9' and a.iid='{$iid}'  and a.imid='{$imid}' and a.display=0 ORDER BY `fans_count` DESC , a.`aid_count` DESC , `name_pinyin` ASC LIMIT {$limit_page} ,".$this->interest_top_page_size;
		
		$result = $this->db->query($sql)->result_array();
		return $result;
	}
	
	/*
	 * 加 20		首页 top 的 数据
	 */ 
	function get_pinyin_entry_web_top($caput_pinyin , $imid , $iid , $page=1){
		$caput_pinyin = strtolower($caput_pinyin);
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= ($page * $this->interest_top_page_size);
		
		if($limit_page==0){	// 第一次 取 80个
			$this->interest_top_page_size = $this->interest_top_page_size - $this->interest_home_first_page_size;
			$limit_page = $this->interest_home_first_page_size;
		}
		// $sql 	= "SELECT * FROM `apps_info` WHERE `caput_pinyin`='{$caput_pinyin}' and iid='{$iid}' and imid='{$imid}'  LIMIT {$limit_page} ,".$this->interest_top_page_size;
		
		$sql 	= "SELECT * FROM `apps_entry` as a 
			WHERE a.`caput_pinyin`='{$caput_pinyin}' and a.imid='{$imid}'  and a.iid='{$iid}' and a.display=0 ORDER BY `fans_count` DESC , a.`aid_count` DESC , `name_pinyin` ASC LIMIT {$limit_page} ,".$this->interest_top_page_size;
		
		$result	= $this->db->query($sql)->result_array();
		return $result;
		
	}
	

	
	
	
	
	
	
	
	
	
}



