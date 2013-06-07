<?php
/**
 * 发现兴趣  与  目录接口
 * author heyuejuan
 */ 
class InterestService extends DK_Service { 
    var $DELETE_WEB_DATA 	=  259200;		// 删除网页时间 (三天   3600*24*3) 
    
    
    public function __construct() {
        parent::__construct();
        $this->init_db('interest');
        $this->DELETE_WEB_DATA = config_item('del_web_stmp') ? config_item('del_web_stmp') : 259200;
    }
    
    /**
     * 添加分类接口
     * $imid			大类分类名
     * $category_name   类名
     * 
     * 返回    array(act=>'', 'msg'=>'')	act 是否执行成功    		msg 不成功返回错误原因	成功 返回id	 
     * **/
    public function add($imid , $category_name){
    	
    	$imid	 = intval($imid);
    	$category_name	= trim($category_name);
    	if($imid<0){ return array('act'=>0,'msg'=>'主分类ID错误'); }
    	if($category_name==''){ return array('act'=>0,'msg'=>'分类名不能为空'); }
    	
    	
    	$result_mid 	= $this->get_category_main_one($imid);
    	if(!is_array($result_mid)){
    		return array('act'=>0,'msg'=>'主分类ID错误'); 
    	}
    	
    	
    	$res_category 	= $this->get_category_one($imid,$category_name);
    	if(is_array($res_category) && isset($res_category['iid'])){	// 己经存再
    		return array('act'=>1,'msg'=>$res_category['iid']);
    	}else{
    		$inset['iname'] = $category_name;
    		$inset['imid'] 	= $imid;
    		$iid = $this->insert_category($inset);
    		$res_category 	= $this->get_category_one($imid,$category_name);
    		if(isset($res_category['iid'])){
    			return array('act'=>1,'msg'=>$res_category['iid']);
    		}else{
    			return array('act'=>1,'msg'=>$iid );
    		}
    	}
    	
    	
    }
    
    /**
     * 记录 分类下  网页记数会自动加1
     * $iid   二级分类id	
     * 
     * **/
    public function web_increase($iid){
    	$iid 	= intval($iid);
    	$id	= $this->increase_category_stat($iid);
    	$this->update_50_to_list($iid);
    	return $id;
    }
    
    
    /**
     * 记录  分类下的网页数
     * $iid 	二级分类id
     * $count	网页数
     * **/
    public function web_count($iid,$count){
    	$iid 	= intval($iid);
    	$count 	= intval($count);
    	$id	=  $this->count_category_stat($iid , $count);
    	
    	$this->update_50_to_list($iid);
    	return $id;
    }
    
    /**
     * 获得 二级分类 信息
     * $iid  网页 id   或以是 数组 型式    iid=array(0=>iid , 1=>iid , 2=>iid )
     * return  （单维数组或多维数组）
     * **/
    public function  get_iid_info($iid){
    	if(is_array($iid)){
    		return $this->get_iid_info_arr($iid);	// 多组数组
    	}else{
    		$iid	= intval($iid);
    		return $this->get_iid_info_db($iid);
    	}
    }
    
    /**
     * 获得 所有显示的大类
     * $imid 大类id    如果没有传大类id 则返回 指定大类 
     * */
    public function get_category_main($imid=0){
    	$imid	= intval($imid);
    	if($imid){
    		return $this->get_category_main_one($imid);
    	}else{
    		return $this->get_category_main_all();
    	}
    	
    }
    
    
    /**
     * 获得大分类下的  可以显示的二级分类数据
     *	
     * **/
    public function get_category_small($imid){
    	$imid	= intval($imid);
		$sql 	= "SELECT * FROM `interest_category` where imid='{$imid}' and is_display='1' and is_list='1' ORDER BY `sort`,`iid` ASC limit 150";
		return $this->__query($sql);
    	// return $this->interest->get_category_small_all($imid);
    }
    
    
    /***
     * 获得分类下所有网页的数据	
     * $iid			二级分类
     * $first_char	拼音首字母		如果传空   则取所有
     * $start		数据记录 起始值
     * $limit		数据记录数
     * **/
    public function get_category_web_all($iid , $first_char='' , $start=0 , $limit=1 ){
    	$imid	= intval($imid);
    	$start 	= intval($start);
    	$limit  = intval($limit);
    	
		if($first_char!=''){
			$sql = "SELECT a1.* FROM `apps_info` as a1 join `apps_info_category` as a2 
						on (a2.iid='{$iid}' and a1.aid=a2.aid) 
					where a1.caput_pinyin='{$first_char}' and a1.display='0' limit {$start},{$limit} ";
		}else{	// 查询所有
			$sql = "SELECT a1.* FROM `apps_info` as a1 join `apps_info_category` as a2 
						on (a2.iid='{$iid}' and a1.aid=a2.aid) 
					where a1.display='0' limit {$start},{$limit} ";
		}
		return $this->__query($sql);
    }
    
    
    /**
     * $aid   网页id			可以是单个也可以是 数组   	array(0=>$aid,1=>$aid);
     * 获取网页数据  (不包括获取   二级分类 )
     * 
     * 返回说明		$aid不是数组	        则返回的是一维数组 
     * 				$aid传的是数组    则返回的是二维数组 
     * ***/
    public function get_web_info($aid){
    	if(!is_array($aid)){	// 单个
     		$aid  	= intval($aid);
     		$result	= $this->get_data_one($aid);
     		return @$result[0];
    	}else{
    		return $this->get_data_multi($aid);
    	}
    }
    
    
    /**
     * 跟据网页名字   模湖查询 
     * $name	
     * **/
    public function get_name_web($name,$start,$limit){
    	$name	= trim($name);
    	$start	= intval($start);
    	$limit	= intval($limit);
    	return $this->get_name_data($name , $start , $limit);
    }
    
    
    /***
     * 传用户uid    
     * 获得用户所有网页数据    (不包括获取  二级分类id)
     * **/
    public function get_webs($uid){
    	$uid 	= intval($uid);
		$sql 	= "select `aid`,`uid`,`name`,`name_pinyin`,`fans_count`,`imid`,`create_time`,`sort`,`is_info` from `apps_info` where uid='{$uid}' and `display`='0' ORDER BY `sort` , `aid` DESC limit 100";
		$result	= $this->__query($sql);
    	
    	return json_encode($result);
    }
    
    
    /**
     * 获得用户网页数据     分页显示
     * $uid		用户id
     * $start	起始值
     * $limit	加载多少条
     * **/
    public function get_webs_page($uid,$start=0 , $limit=30){
    	$uid	= intval($uid);
    	$start	= intval($start);
    	$limit	= intval($limit);
    	
		$sql 	= "select count(1) as ct from `apps_info` where uid='{$uid}' and `display`='0' ";
		$result	= $this->__query($sql);
		$arr['ct'] 	= $result[0]['ct'];

		$sql 	= "select `aid`,`uid`,`name`,`name_pinyin`,`fans_count`,`imid`,`create_time`,`sort`,`is_info`  from `apps_info` where uid='{$uid}' and `display`='0' ORDER BY `sort` , `aid` DESC limit {$start} , {$limit}";
		$result	= $this->__query($sql);
		$arr['data']= $result;
		
    	return json_encode($arr);
    }
    
    
    /***
     * 获得网页的  分类id
     * 
     * 返回 二级分类的 id   数组
     * **/
    public function get_web_category_id($aid){

    	$aid	= intval($aid);
    	$result	= $this->aid_get_iid($aid);
    	$arr = array();
    	foreach($result as $key=>$val){
    		$arr[] = $val['iid'];
    	}

    	return @$arr;
    }
    
    /***
     * 获得网页的大分类
     * 
     * **/
    public function get_web_imid($aid){
    	$aid	= intval($aid);
    	
    }
    
    
    
    /***
     * 获得网页的  分类id
     * 
     * 返回 二级分类的 id   没有转变的数组  
     * **/
    public function get_web_category_id2($aid){
    	
    	$aid	= intval($aid);
    	$result	= $this->aid_get_iid($aid);
    	return @$result;
    }
    
    
    /**
     * 禁用网页
     * $aid		网页id
     * $data 	数据 		数据格式为    arr[0]['call']='fun'  arr[0]['data']='数据'		json 数据
     * **/
	public function display_web($aid , $data){
		$aid 	= intval($aid);
		$data	= trim($data);
		if(!get_magic_quotes_gpc()){
			$data	= addslashes($data);
		}
		// // $this->appsinfo->set_display_web($aid);
		
		$result	= $this->add_del_web_event($aid,$data);
		return $result;
	}
    
    
	/**
	 * 查询  网页是否在  删除装态
	 * $aid_arr		网页id   可以是数组与 网页id 		   数组 array(0=>aid,1=>aid);
	 * 
	 * 返回    	false or true
	 * **/
	public function get_display_web_info($aid_arr){
		$is_arr 	= true;
		if(! is_array($aid_arr) ){
			$aid	= $aid_arr;
			$aid_arr= null;
			$aid_arr[]	= $aid;
			$is_arr	= false;
		}
		
		$result		= $this->get_del_web_event($aid_arr);
		$execute_arr= null;
		foreach($result as $val){
			$execute_arr[$val['execute']] = 1;
		}
		$return_arr	= null;
		if($is_arr){
			
			foreach($aid_arr as $val){
				$return_arr[$val]	= isset($execute_arr[$val]);
			}
			
		}else{
			$return_arr[$aid] = isset($execute_arr[$aid]);
		}
		return json_encode($return_arr);
	}
    
    
	/**
	 * 排序显示  网页
	 * $uid 	用户id
	 * $aid    	aid 会设在最前显示
	 * **/
	public function web_order( $uid , $aid){
		$aid 	= intval($aid);
		
		// 所有的都加1
		$sql = "UPDATE `apps_info` SET `sort` = sort+1 WHERE `uid` ='{$uid}' ";
		$this->__execute($sql);
		
		// 
		$sql = "UPDATE `apps_info` SET `sort` = '0' WHERE `aid` ='{$aid}' and `uid`='{$uid}' ";
		$this->__execute($sql);
		//$this->appsinfo->web_order($uid ,$aid);
		return true;
	}
    
	
    /**
     * 设置  是否显示个人信息到网页资料
     * $aid		网页id
     * $is_info	 0 不显示      1 显示
     * **/
	public function web_is_info( $aid , $is_info){
		$aid	= intval($aid);
		
		$sql 	= "update  `apps_info` SET `is_info` = '{$is_info}' WHERE `aid` ='{$aid}' ";
		return $this->__execute($sql);
	}
	
	/**
	 * 获得网页数据     (品新新那边获取  用于生成头像)
	 * start 	起始值    
	 * limit	查多少数据	0 查出所有数据
	 * **/
	public function get_webid_all($start=0,$limit=0){
		$count = "";
		if($limit!=0){
			$count	= " limit {$start}, {$limit} ";
		}
		$sql 	= "SELECT aid ,uid FROM `apps_info` where display='0' {$count} ";
		return $this->__query($sql);
	
	}
	
	
	/**
	 * 获得用户网页的数量
	 * */
	public function get_web_user_count($uid){
		$sql 	= "SELECT count(1) as ct FROM `apps_info` where uid='{$uid}' and display='0' ";
		$result	= $this->__query($sql);
		return @$result[0]['ct'];
		
	}
	
	
    /**
     * 获得  层级分类 的数据
     * $id  	上级分类id      顶级分类时  id 传0
     * $level 	传本次要查询的级数    顶级传  1    二级传2 ..
     * */
    public function get_category_level($id , $level=1){
    	$id		= intval($id);
    	$level	= intval($level);
    	if($level<=0)	$level = 1;
    	if($level==1){
    		return $this->get_category_level_one();
    	}else if($level==2){
    		return $this->get_category_level_two($id);
    	}else if($level==3){
    		return $this->get_category_level_three($id);
    	}else{
    		return $this->get_category_level_more($id , $level);
    	}
    }
	
    /**
     * 获得   分类id  的数据信息
     * $id 		分类id
     * $level	分类级别
     * **/
    public function get_category_level_name($id,$level=1){
    	$id		= intval($id);
    	$level	= intval($level);
    	if($level<=0)	$level = 1;
        	if($level==1){
    		$result	= $this->get_category_level_name_one($id);
    	}else if($level==2){
    		$result = $this->get_category_level_name_two($id);
    	}else{
    		$result = $this->get_category_level_name_more($id , $level);
    	}
    	return @$result[0];
    }
    
	
	
/***    提供给 广告组   接口    start   ***/
	/**
	 * iid		传入兴趣 id 数组   (可以是数组  也可以是单个)
	 * start    起始值
	 * limit	长度
	 * 返回   数组 网页id 
	 * **/
	public function get_web_info_iid($iid ,$start=0 , $limit=0 ){
		$start 	= intval($start);
		$limit	= intval($limit);
		if(! is_array($iid) ){
			$id		= intval($iid);
			$iid	= null;
			$iid	= array($id);
		}
		if($limit<=0){
			$limit 	= "";
		}else{
			$limit	= " limit {$start} , {$limit} "; 
		}
		$sql 	= "select aid from `apps_info_category` where iid in ('".implode("','" , $iid)."') {$limit} ";
		$rest_arr	= $this->__query($sql);
		
		$aid_arr	= null;
		foreach($rest_arr as $key=>$val){
			$aid_arr[$val['aid']]	= $key;
		}
		return array_flip($aid_arr);
	}
	
	/**
	 * 获得  所有大分类与二级分类
	 * $start	起始值
	 * $limit	取出的数量
	 * **/
	public function get_category_all($start=0,$limit=0){
		$start 	= intval($start);
		$limit	= intval($limit);
		
		$limit_val	= "";
		if($limit>0){
			$limit_val	= " limit {$start},{$limit} ";
		}
		
		$sql	= "select c.iid,c.iname,c.imid,m.imname from `interest_category` as c join `interest_category_main` as m on (c.is_display='1' and c.is_list='1' and c.imid=m.imid) order by c.imid ASC {$limit_val} ";
		return $this->__query($sql);
		// return $this->interest->get_category_all($start,$limit);
	}
	
/***    提供给 广告组   接口    end   ***/
	
	
	
	
	
	
	
	
	
	
    
    
    
    
    
    
    
    
    
    
	/**
	 * 插入  二级分类
	 * $arr		表 key=>value
	 * **/
	public function insert_category($arr){
		return $this->__insert('interest_category', $arr);
		
	}
	// 获得一条一级分类
	public function get_category_main_one($imid){
		$sql = "SELECT * FROM `interest_category_main` where imid='{$imid}' limit 1";
		$result	= $this->__query($sql);
		return $result;
	}
    
	// 获得二级分类
	public function  get_category_one($imid , $iname){
		$sql = "SELECT * FROM `interest_category` where iname='{$iname}' and imid='{$imid}' limit 1";
		$result	= $this->__query($sql);
		return @$result[0];
	}
    
	// 递增  分类的  网页数
	public function increase_category_stat($iid){
		$sql 	= "update `interest_category` set stat=stat+1 where iid='{$iid}' limit 1";
		return $this->__execute($sql);
	}
    
	
	// 50 时 就在  列表里显示
	public function  update_50_to_list($iid){
		$sql = "update `interest_category` set `is_list`='1' where iid='{$iid}' and stat>=50 limit 1";
		return $this->__execute($sql);
		
	}
    
	/***
	 * 递增分类数据
	 * iid		二级分类id
	 * count	网页数
	 */
	public function count_category_stat($iid,$count){
		$sql 	= "update `interest_category` set stat='{$count}' where iid='{$iid}' limit 1";
		return $this->__execute($sql);
	}
	
	
	// 获得  二级分类的数据
	public function get_iid_info_db($iid){
		$sql = "select * from `interest_category` where iid='{$iid}' limit 1";
		$restult 	= $this->__query($sql);
		return @$restult[0];
	}
	
	// 批理  获得  二级分类 数据
	public function get_iid_info_arr($iid_arr){
		$sql = "select * from `interest_category` where iid in ('".implode("','",$iid_arr)."')";
		
		return $this->__query($sql);
	}
	
	// 获得所有可以显示的大类
	public function get_category_main_all(){
		$sql 	= "select * from `interest_category_main` where is_display='1' ORDER BY `sort` ASC limit 100";
		return $this->__query($sql);
	}
	
	/**
	 * aid 获得一条记录   (不包括二级分类id)
	 * */
	function get_data_one($aid){
		$sql 	= "select aid,uid,dkcode,name,name_pinyin,imid,iid,eid,category_group,fans_count,create_time,is_info,webcover from `apps_info` where aid='{$aid}' and `display`='0' limit 1";
		$result	= $this->__query($sql);
		return $result;
	}
	
	/**
	 * 获得多维数组  
	 * **/
	function get_data_multi($aid_arr){
		$sql 	= "select * from `apps_info` where aid in ('".implode("','",$aid_arr)."') and `display`='0' ";
		return $this->__query($sql);
	}
	
	
	// 跟据网页名字   模湖查询 
	function get_name_data($name,$start , $limit){
		$sql 	= "SELECT * FROM `apps_info` WHERE `name` LIKE '%{$name}%' LIMIT {$start},{$limit} ";
		return $this->__query($sql);
		
	}
	
	/**
	 * 传 aid
	 * 获得网页  二级分类 id  
	 * **/
	function aid_get_iid($aid){
		$sql = "select * from `apps_info_category` as b where b.aid='{$aid}' ";
		return $this->__query($sql);
	}
	
	
	/**
	 * 添加网页的事务
	 * $aid 	网页id
	 * **/
	public function add_del_web_event($aid , $data){
		
		$del_time	= $this->DELETE_WEB_DATA;	// 删除  网页 后移 时间
		$time		= time();
		
		$sql 	= "select * from `apps_queue` where `info`='delete_web' and execute='{$aid}' and `finish`='0' ";
		$result	= $this->__query($sql);
		if( !( is_array($result)==true && count($result)>=1 ) ){
			$sql 	= "INSERT INTO `apps_queue` (`id` ,`info` ,`execute` , `data` ,`end_time` ,`create_time` ,`finish`)VALUES (
							'', 'delete_web', '{$aid}', '{$data}' , '".($time+$del_time)."', '{$time}', '0')";
			$result	= $this->__execute($sql);
			$list_id= $this->__insert_id();
			
			//$sql 	= "CREATE EVENT `e_delete_web_{$aid}_{$list_id}` ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL {$del_time} SECOND DO CALL delete_web({$aid},{$list_id})";
			//$id		= $this->db->execute($sql);
			
			return $list_id;
		}else{
			return 0;
		}
	}
	
	public function get_del_web_event($aid_arr){
		$del_time	= $this->DELETE_WEB_DATA;	// 删除  网页 后移 时间
		$time	= time();
		$sql 	= "SELECT * FROM `apps_queue` where `info`='delete_web' and end_time<=".($time+$del_time)." and finish=0 and `execute` in('".implode("','",$aid_arr)."')";
		return $this->__query($sql);
	}
	
/******    层级分类   start    ******/
	// 一级
	public function get_category_level_one(){
		$sql 	= "SELECT imid as id , imname as name , 1 as level , 0 as has_son FROM `interest_category_main` where is_display='1' limit 50"; 
		return $this->__query($sql);
	}
	// 二级
	public function get_category_level_two($id){
		$sql 	= "SELECT iid as id , iname as name , 2 as level , 1 as has_son FROM `interest_category` where imid={$id} and is_display='1' limit 100"; 
		return $this->__query($sql);
	}
	// 三级
	public function get_category_level_three($id){
		$sql 	= "SELECT eid as id , name as name , 3 as level , if(piid_type=0 , 1 ,piid_type) as has_son FROM `apps_entry` where iid={$id} and piid=0 and display=0 ORDER BY `fans_count` DESC limit 500";
		return $this->__query($sql);
	}
	// 四级，五级，六级...
	public function get_category_level_more($id,$level){
		$sql 	= "SELECT eid as id , name as name , {$level} as level , if(piid_type=0 , 1 ,piid_type) as has_son FROM `apps_entry` where piid={$id} and display=0 ORDER BY `fans_count` DESC limit 500";
		return $this->__query($sql);
	}
	
	
	// 一级
	public function get_category_level_name_one($id){
		$sql 	= "SELECT imid as id , imname as name , 1 as level , 0 as has_son FROM `interest_category_main` where imid={$id} and is_display='1' limit 1"; 
		return $this->__query($sql);
	}
	// 二级
	public function get_category_level_name_two($id){
		$sql 	= "SELECT iid as id , iname as name , 2 as level , 1 as has_son FROM `interest_category` where iid={$id} and is_display='1' limit 1"; 
		return $this->__query($sql);
	}
	// 四级，五级，六级...
	public function get_category_level_name_more($id,$level){
		$sql 	= "SELECT eid as id , name as name , {$level} as level , if(piid_type=0 , 1 ,piid_type) as has_son FROM `apps_entry` where eid={$id} and display=0  limit 1";
		return $this->__query($sql);
	}
	
	
/******    层级分类   end    ******/
    
    
    
    
    
    
    
/*********   sql 数据库操作	start   *************/
	/**
	 * 查询
	 * **/
	private function __query($sql){
		return $this->db->query($sql)->result_array();
	}
	
	private function __insert_id(){
		
		return $this->db->insert_id();
	}
	
	/**
	 * 删除
	 * **/
	private function __delete($sql){
		return $this->db->query($sql);
	}
	
	// 执行 sql 语句
	private function __execute($sql){
		return $this->db->query($sql);
	}
	
	
	/**
	 * 插入数据
	 * $table		表
	 * $arr 		要插入的数据		array(key=>value) key 字段	 value 值
	 * $del_null	删除 arr 中空的 字段    ， 这里是因为  字段是数据型如果生成一个'' 数据插入进去    就会有错误
	 * 
	 * return  0 表示没有插入成功
	 * **/
	private function __insert($table , $arr, $del_null=true){
		if(!is_array($arr))	return 0;
		if($del_null){
			foreach($arr as $key=>$val){
				if($val===null || $val ==='')	unset($arr[$key]);	// if(!$val) 不能这样   因为 0 也会被删除 
			}
		}
		$sql 	= "INSERT INTO `{$table}`(`" . implode('`,`',array_keys($arr)) . "`)VALUES ('". implode("','",array_values($arr)) ."')";
		return $this->db->query($sql);
		
	}
	
	/**
	 * 忽略重复  插入
	 * $table		表
	 * $arr 		要插入的数据		array(key=>value) key 字段	 value 值
	 * $del_null	删除 arr 中空的 字段    ， 这里是因为  字段是数据型如果生成一个'' 数据插入进去    就会有错误
	 * 
	 * return  0 表示没有插入成功
	 * **/
	private function __insert_ignore($table , $arr , $del_null=true){
		if(!is_array($arr)) return 0;
		if($del_null){
			foreach($arr as $key=>$val){
				if($val===null || $val ==='')	unset($arr[$key]);	// if(!$val) 不能这样   因为 0 也会被删除 
			}
		}
		$sql	= "INSERT IGNORE INTO `{$table}`(`" . implode('`,`',array_keys($arr)) . "`)VALUES ('". implode("','",array_values($arr)) ."')";
		return $this->db->query($sql);
		
	}
	
	
	
	/**
	 * 更新数据
	 * $table 		表
	 * $arr			要插入的数据
	 * $is_one		是否只更新一条	true 只更新一条 	false 可以更新多条
	 * 
	 * 返回  	1,2     0 表示没有更新成功
	 * **/
	private function __update($table , $arr , $where=null , $is_one=false ){
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
	private function __create_update($arr){
		//if(! is_array($arr)) return '';
		foreach($arr as $key=>$val){
			$set[] = " `{$key}`='{$val}' ";
		}
		return @ implode(',',$set);
	}
/*********   sql 查询	end   *************/
    
    
    
}
