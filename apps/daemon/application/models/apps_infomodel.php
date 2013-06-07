<?php



class Apps_infoModel extends MY_Model{
	
	
	function __construct(){
		parent::__construct();
		
	}
	
	// 获得  队列的数据
	function get_apps_queue(){
		$time	= time();
		$sql 	= "select * from `apps_queue` where `info`='delete_web' and end_time<'{$time}' and finish=0 ORDER BY `end_time` DESC  limit 50";
		
		$result	= $this->db->query($sql)->result_array();
		return $result;
	}
	
	// 禁用网页
	function delete_web($aid){
		$sql 	= "UPDATE `apps_info` SET display=1 WHERE aid='{$aid}'";
		return $this->db->query($sql);
		
	}
	
	// 关闭队列
	function close_queue($pid){
		$sql 	= "UPDATE `apps_queue` SET finish=1 WHERE id='{$pid}'";
		return $this->db->query($sql);
		
	}
	
	// 删除关注的网页与分类
	function del_web_attention_category($aid){
		$sql 	= "delete a1 from `user_attention_category` as a1 , (
						SELECT aid,uid,iid FROM `user_apps_attention` group by aid ,`uid` having count(aid)<=1 and aid='{$aid}'
					) as a2 where a1.uid=a2.uid and a1.iid=a2.iid ";
		return $this->db->query($sql);
	}
	
	// 删除除关闭的网页
	function del_apps_attention($aid){
		$sql 	= "delete from user_apps_attention where aid='{$aid}' ";
		return $this->db->query($sql);
	}
	
}



