<?php
// 


// do_call('interest','Index','get_category_main',array('124598') )

/**
 * @author heyuejuan
 * 调用  数据 兴趣分类数据
 * **/

class Apps_infoModel extends MY_Model{
	
	function __construct(){
		parent::__construct();
		$interestdb	= config_item('interestdb');
		$this->db_selectdb($interestdb['database']);
		
		// $this->db->query('');
		$this->mycache  = $this->memcache;
		
	}
	
	
	// 插入网页
	function add_apps($arr ){
		
		return $this->__insert( 'apps_info', $arr);
		
	}
	
	
	// 获得id 分类下的网页数
	function get_iid_count($iid){
		$sql = "SELECT count(1) as ct FROM `apps_info` WHERE `iid`='{$iid}'";
		$result	= $this->db->query($sql)->result_array();
		return isset($result[0]['ct'])? $result[0]['ct'] : 0;
		
	}
	
	// 获得一个二级分类  
	function get_iid_one($iid){
		$sql 	= "select * from `interest_category` where iid='{$iid}' limit 1 ";
		return $this->db->query($sql)->row_array();
		
	}
	
	
	// 获得网页数据
	function get_web_info($aid){
		$sql = "select * from `apps_info` where aid='{$aid}' limit 1";
		$result	= $this->db->query($sql)->result_array();
		return @$result[0];
	}
	
	
	// 开启 网页
	function set_web_enable($uid,$aid){
		$sql 	= "UPDATE `apps_info` SET `display` = '0' WHERE `uid` ={$uid} and aid={$aid} LIMIT 1";
		return $this->db->query($sql);
	}
	
	
	
	/**
	 * 插入网页的分类
	 * 
	 * **/
	function add_apps_category($aid,$imid,$iid){
		$arr['aid']		= $aid;
		$arr['imid']	= $imid;
		$arr['iid']		= $iid;
		
		return $this->__insert( 'apps_info_category', $arr);
		// INSERT INTO `apps_info_category` (`id`, `aid`, `imid`, `iid`) VALUES (NULL, '0', '10', '1');
	}
	

	
	
	
	// 获得分类下网页数
	function get_category_stat($imid,$category_name){
		$sql 	= "select * from `interest_category` where `imid`='{$imid}' and `iname`='{$category_name}' limit 1 ";	// 
		$result	= $this->db->query($sql)->result_array();
		return @$result[0];
	}
	
	
	
	
	
	
	
	
	
/***********    词条  start    ***********/
	function get_apps_entry_one($eid){
		$sql 	= "SELECT * FROM `apps_entry` where `eid`='{$eid}' limit 1 ";
		return $this->db->query($sql)->row_array();
	}
	
	
	/***
	 * 更新分类下的粉丝数
	 * **/
	function update_entry_fans_count($uid,$aid,$eid){
		$sql2	= "select count(*) as ct , sum(`fans_count`) as fc , uid , aid from `apps_info` where eid={$eid} ";
		$apps_result	= $this->db->query($sql2)->row_array();
		
		$update	= "update `apps_entry` set aid=if(uid=0,".intval($apps_result['aid']).",aid) , uid = if(uid=0,".intval($apps_result['uid'])." , uid) ,
					aid_count='".$apps_result['ct']."' , fans_count='".intval($apps_result['fc'])."' where eid='".$eid."' limit 1 ";
		return $this->db->query($update);
	}
	
	
	
	// 记录分类表里的   网页 数据		// 最多不个数据
	function add_entry_web_info($eid){
		$sql 	= "select * from `apps_info` where eid='{$eid}' limit 5 ";
		$apps_result	= $this->db->query($sql)->result_array();
		$arr	= null;
		
		if(is_array($apps_result)){
			foreach($apps_result as $key=>$val){
				$arr[]	= array('aid'=>$val['aid'], 'uid'=>$val['uid'] );
			}
			$update	= "update `apps_entry` set app_info='".json_encode($arr)."' where eid='{$eid}' ";
			return $this->db->query($update);
		}
	}
/******    词条   end   ********/
	
	
/*******	标签  start	*********/
	// 插入标签  		$aname  为网页名
	function add_info_tag($aid, $tid, $tname, $tname_pinyin, $imid, $iid  , $aname){
		//$sql 	= "INSERT IGNORE INTO `apps_info_tag` (`atid`, `aid`, `tid`, `tname`, `tname_pinyin`, `imid`, `iid`) VALUES
		//(null, 952, 126, '桌游', 'zhuoyou', 2, 143) ";
		
		$arr['aid']		= $aid;
		$arr['tid']		= $tid;
		$arr['tname']	= $tname;
		$arr['tname_pinyin']= $tname_pinyin;
		$arr['imid']	= $imid;
		$arr['iid']		= $iid;
		$main_id 		= $this->__insert_ignore( 'apps_info_tag', $arr );
		
		
		$sql = "SELECT * FROM `apps_entry` where name='{$aname}' and imid='{$imid}' and iid='{$iid}' ";
		$result	= $this->db->query($sql)->row_array();
		
		if(is_array($result) && $result['eid']>0){
			$sql = "INSERT INTO `apps_entry_tag` (`etid` ,`eid` ,`tid` ,`tname` ,`tname_pinyin` ,`imid` ,`iid` ,`tag_count` )VALUES 
					(NULL , '".$result['eid']."', '{$tid}', '{$tname}', '{$tname_pinyin}', '{$imid}', '{$iid}', '1') on duplicate key update tag_count=tag_count+1 ";
			$this->db->query($sql);
		}
		return $main_id;
		
		/*
		// 加入到词条库
		$arr['aid']		= $aid;
		$arr['tid']		= $tid;
		$arr['tname']	= $tname;
		$arr['tname_pinyin']= $tname_pinyin;
		$arr['imid']	= $imid;
		$arr['iid']		= $iid;
		return $this->__insert_ignore( 'apps_info_tag', $arr );
		*/
	}
/*******	标签  end 	***********/
	
	
	
	
	
	
}