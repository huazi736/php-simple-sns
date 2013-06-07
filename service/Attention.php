<?php
/**
 * 网页关注接口
 * author heyuejuan
 */   
class AttentionService extends DK_Service { 
    
    public function __construct() {
        parent::__construct();
        $this->init_db('interest');
        
    }
    

    /*
     * 加关注时   保存关注人的分类数据  与   网页的粉丝数
     * $uid   		用户id
     * $aid			网页id
     * $fans_count	网页的粉丝数
     * 
     * $action_time 操作时间
     * $expiry_time	关注失效时间
     */
    public function add_attention($uid,$aid, $fans_count , $action_time =0 , $expiry_time=0 ){
    	$uid	= intval($uid);
    	$aid	= intval($aid);
    	$fans_count	= intval($fans_count);
    	$action_time	= intval($action_time);
    	$expiry_time	= intval($expiry_time);
    	
    	// 设置粉丝数
    	$this->set_fans_count($aid,$fans_count);
    	$this->set_entry_count($aid);	// 设置词条  粉丝数
    	
    	/** 设置关注  **/
    	$result	= $this->aid_get_iid($aid);	// 最多三个数组
    	if( is_array($result) ){
	    	foreach($result as $key=>$val){
	    		$arr['aid'] = $aid;
	    		$arr['imid'] = $val['imid'];
	    		$arr['iid'] = $val['iid'];
	    		$arr['uid'] = $uid;
	    		$arr['create_date'] = time();
	    		$arr['action_time']	= $action_time;
	    		$arr['expiry_time'] = $expiry_time;
	    		$this->add_attention_idd($arr);
	    		
	    		$category_arr['uid'] = $uid;
	    		$category_arr['iid'] = $val['iid'];
	    		$this->add_attention_category($category_arr);
	    		
	    	}
    	}
    	return true;
    }
    
    
    /**
     * 修改关注时间
     * @param $uid   		用户id
     * @param $webid		网页id
     * @param $action_time  操作时间
     * @param $expiry_time	关注失效时间
     * @author boolee 2012/6/26
     */
    public function updateAttentionTime($uid,$webid, $action_time =0 , $expiry_time=0 ){
    	if(!$uid || !$webid || !$action_time || !$expiry_time)	return false;
    	
    	$sql="UPDATE  `user_apps_attention` SET  `action_time` =  '{$action_time}',`expiry_time`='{$expiry_time}' WHERE  `aid` =$webid AND  `uid` =$uid LIMIT 1";
		return $this->__execute($sql);
    }
    
    
    /*
     * 删除加关注时   要处理分类数据与   网页的粉丝数据
     * $uid   		用户id
     * $aid			网页id
     * $fans_count	网页的粉丝数
     */
    public function del_attention($uid, $aid, $fans_count){
    	$uid	= intval($uid);
    	$aid	= intval($aid);
    	$fans_count	= intval($fans_count);
    	
    	// 设置粉丝数
    	$this->set_fans_count($aid,$fans_count);
    	$this->set_entry_count($aid);	// 设置词条  粉丝数
    	
    	/** 处理关注  **/
    	$result	= $this->aid_get_iid($aid);	// 最多三个数组
    	if(is_array($result) ){
    		foreach($result as $key=>$val){
    			$this->del_attention_idd($aid, $val['imid'] , $val['iid'] , $uid);
    			
    			$user_iid	= $this->get_user_iid($uid, $val['iid']);
    			if( (!isset($user_iid[0]['id'])) || (count($user_iid)==1 && $user_iid[0]['aid']==$aid && $user_iid[0]['iid']==$val['iid'] ) ){
    				$this->del_attention_category($uid, $val['iid']);		// 删除显示分类
    			}
    		}
    	}
    	return true;
    }
    
    
    /**
     * 获得  网页里的关注 分类     （关注）
     * $uid   		uid用户名
     * $is_display	是否显示隐藏的分类	1 显示 ， 0 不显示
     * **/
    public function get_attention_category($uid , $is_display=0){
    	$uid 	= intval($uid);
    	$is_display = intval($is_display);
    	
		$where = '';
		if($is_display==0){
			$where = ' and is_display=1 ';
		}
		$sql 	= "select i.iname , i.imid  , a.* from interest_category as i, 
				(select * from user_attention_category where uid='{$uid}' {$where} ORDER BY `id` ASC ) as a
				where i.iid=a.iid
		 ";
		return $this->__query($sql);
    	
    }
    
    
    /***
     * 设置分类显示与隐藏   （关注）
     * $uid 	用户id
     * $iid		分类id
     * $is_show	是否显示   0 不显示  1 显示
     * **/
    public function set_attention_category_show($uid,$iid , $is_show=0){
    	$uid 	= intval($uid);
    	$iid 	= intval($iid);
    	$is_show= intval($is_show);
    	
		if($is_show==1){
			$display = 1;
		}else{
			$display = 0;
		}
		$sql 	= "update `user_attention_category` set is_display='{$display}' where uid='{$uid}' and iid='{$iid}' ";
		return $this->__execute($sql);
    	
    }
    
    /***
     * 获得   分类里的网页数据     （关注）    (如果 $is_display=0时   action_uid 等于网页人的id那么也显示)
     * $uid 	用户id
     * $iid		分类id
     * $is_display 是否显示隐藏的分类	1 显示 ， 0 不显示
     * $start   从第几条记录取起
     * $limit   取多少个
     * 
     * $action_uid	当前活动的用户id
     * **/
    public function get_attention_web($uid , $iid , $is_display , $start , $limit , $action_uid=0){
    	$uid 	= intval($uid);
    	$iid 	= intval($iid);
    	$is_display = intval($is_display);
    	$start 	= intval($start);
    	$limit	= intval($limit);
    	$action_uid = intval($action_uid);
    	
    	$where = '';
		if($is_display==0){
			if($action_uid!=0){
				$where = " and (u.is_display=1  or a.uid='{$action_uid}') ";
			}else{
				$where = ' and u.is_display=1 ';
			}
		}
		
    	$sql 	= "SELECT count(1) as ct FROM `user_apps_attention` as u join apps_info as a on (u.uid='{$uid}' and u.iid='{$iid}' {$where} and u.aid=a.aid)";
    	$ct		= $this->__query($sql);
		$sql 	= "SELECT u.id,u.aid,u.imid,u.iid,u.uid,a.name,a.fans_count, a.uid as web_uid , u.is_display FROM `user_apps_attention` as u join apps_info as a on (u.uid='{$uid}' and u.iid='{$iid}' {$where} and u.aid=a.aid) ORDER BY `create_date` DESC , `id` DESC limit {$start} , {$limit} ";
    	$restul = $this->__query($sql);
    	return array('ct'=>@$ct[0]['ct'] , 'data'=>$restul);
    	
    	// return $this->user->get_attention_web($uid,$iid , $is_display , $start , $limit , $action_uid);
    }
    
    
    
    /***
     * 设置网页显示与隐藏   （关注）
     * $uid 	用户id
     * $aid		网页id
     * $is_show	是否显示   0 不显示  1 显示
     * **/
    public function set_attention_web_show($uid, $aid , $is_show=0){
    	$uid	= intval($uid);
    	$aid	= intval($aid);
    	$is_show= intval($is_show);
    	
    	if($is_show==1){
			$display = 1;
		}else{
			$display = 0;
		}
		$sql 	= "update `user_apps_attention` set is_display='{$display}' where uid='{$uid}' and aid='{$aid}' ";
		return $this->__execute($sql);
    	
    }
    
    
    /**
     * 获得用户 关注的所有网页的  数据      按粉丝数排序
     * $uid		用户id
     * $start	起始值
     * $limit	取多少
     * */
    public function get_attention_name($uid , $start=0 , $limit=9 ){
    	$uid	= intval($uid);
    	$start	= intval($start);
    	$limit	= intval($limit);
    	$sql 	= "SELECT u.id,u.aid,u.imid,u.iid,u.uid,a.name,a.fans_count, a.uid as web_uid, u.is_display FROM `user_apps_attention` as u join apps_info as a on (u.uid='{$uid}' and u.aid=a.aid) order by fans_count DESC limit {$start} , {$limit} ";
    	return $this->__query($sql);
    }
    
	/**
	 * 设置网页的粉丝数
	 * 
	 * **/
	function set_fans_count($aid,$count){
		$sql 	= "UPDATE `apps_info` SET `fans_count` = '{$count}' WHERE `aid` ='{$aid}'  LIMIT 1 ";
		$result = $this->__execute($sql);
		return $result;
	}
    
	// 设置词 条的粉丝数
	function set_entry_count($aid){
		$sql 	= "select * from `apps_info` WHERE `aid` ='{$aid}'  LIMIT 1 ";
		$rest	= $this->__query($sql);
		
		if( isset($rest[0]['fans_count']) ){
			$sql2	= "select sum(fans_count) as sm from `apps_info` where name='".$rest[0]['fans_count']."' ";
			$rest	= $this->__query($sql);
		}else{
			return false;
		}
		if( isset($rest[0]['sm']) ){
			$update = "update apps_entry set fans_count='".$rest[0]['sm']."' where name='".$rest[0]['fans_count']."' ";
			$result = $this->__execute($update);
			return $result;
		}else{
			return false;
		}
		
	}
	
	/**
	 * 传 aid
	 * 获得网页  二级分类 id  
	 * **/
	function aid_get_iid($aid){
		$sql = "select * from `apps_info_category` as b where b.aid='{$aid}' ";
		return $this->__query($sql);
	}
	
	// 插入  用户关注分类数据
	public function add_attention_idd($arr){
		return $this->__insert('user_apps_attention', $arr);
	}
	/**
	 * 添加  注入里显示的兴趣分类
	 * **/
	public function add_attention_category($arr){
        if (empty($arr) || !isset($arr['uid']) || !isset($arr['iid'])) {
            return false;
        }
        
        $uid = $arr['uid'];
        $iid = $arr['iid'];
        
        $sql = "select id from user_attention_category where uid='{$uid}' and iid='{$iid}' ";
        $res = $this->__query($sql);

        if ($res) {
            return true;
        }
		return $this->__insert('user_attention_category', $arr);
	}

	// 删除关注
	public function del_attention_idd($aid, $imid, $iid, $uid){
		$sql = "delete from user_apps_attention where aid='{$aid}' and imid='{$imid}' and iid='{$iid}' and uid='{$uid}' ";
		return $this->__delete($sql);
	}
	
	// 查询关注的分类
	public function get_user_iid($uid , $iid){
		$sql  = "select * from user_apps_attention where uid='{$uid}' and iid='{$iid}' limit 1";
		return $this->__query($sql);
	}
	
	// 删除  注入里显示的兴趣分类
	public function del_attention_category($uid , $iid){
		$sql = "delete from user_attention_category where uid='{$uid}' and iid='{$iid}' ";
		return $this->__delete($sql);
	}

	/**
	 * 获取用户对网页的隐藏状态
	 *
	 * @author zengmm
	 * @date 2012/7/14
	 *
	 * @param int $uid 用户UID
	 * @param int|array $webpge_ids 网页ID
	 *
	 * @return int|array
	 */
	public function getWebpageHiddenStatus($uid = 0, $webpage_ids = array())
	{
		if (empty($uid) || empty($webpage_ids)) { return FALSE; }

		$webpageids = !is_array($webpage_ids) ? array($webpage_ids) : $webpage_ids;

		$sql = 'SELECT `uid`, `aid`, `is_display` FROM user_apps_attention WHERE `uid`=' . $uid . ' AND `aid` IN (' . implode(',', $webpageids) . ')';

		$result = $this->__query($sql);

		// 组装
		$is_display = array();
		if ($result) {
			foreach ($result as $v) {
				$is_display[$v['aid']] = $v['is_display'];
			}
		}
		
		return !is_array($webpage_ids) ? end($is_display) : $is_display;
	}
	
	
/*********   sql 数据库操作	start   *************/
	/**
	 * 查询
	 * **/
	private function __query($sql){
		return $this->db->query($sql)->result_array();
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


?>