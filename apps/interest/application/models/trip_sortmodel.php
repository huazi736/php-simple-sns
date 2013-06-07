<?php
/**
 *@author wangh
 *@date    2012-07-24
 *@旅游景点
 */
 class trip_sortmodel extends MY_Model{
 	function __construct(){
 		parent::__construct();
 	}
 	public function index(){
 		echo "sdgdg";
 	}
 	/**
 	 *@得到所在省的 eid
 	 *@author    wangh
 	 *@date    2012-07-25
 	 *@description  按城市分类
 	 */
 	public function getProvince_eid($name){
 		$sql = "SELECT name,eid from apps_entry where imid=9 AND name='{$name}'";
 		$res =  $this->db->query($sql)->result_array();
 		return $res;
 	}
 	/**
 	 *@得到所在市的 eid
 	 *@autor      wangh
 	 *@date   2012-07-25
 	 *@description  按城市分类
 	 */
 	public function getCity_eid($name){
 		$eid = $this->getProvince_eid($name);
 		foreach($eid as $v){
 			$eidcity[] = $v['eid'];
 		}
 		if(is_array($eidcity)) {$eidcity = implode(",",$eidcity);}
// 		$sql = "SELECT eid from apps_entry where imid=9 and piid in ({$eidcity})";
// 		$arr_eid = $this->db->query($sql)->result_array();
// 		foreach ($arr_eid as $v){
// 			$arr[] =$v['eid'];
// 		}
// 		if(is_array($arr)) {$arr = implode(",",$arr);}
 		return $eidcity;

 	}
 	/**
 	 *@得到某个省下面的所有景点名称
 	 *@autor   wangh
 	 *@date  2012-07-25
 	 *@description 按城市分类
 	 */
 	public function getTripName($name){
 		$thrid = $this->getCity_eid($name);
 		$sql = "SELECT name from apps_entry where piid in ({$thrid})";
 		return $this->db->query($sql)->result_array();
 	}
 	public function getTripName_eid($eid){
 		$sql = "SELECT name from apps_entry where piid in ({$eid})";
 		return $this->db->query($sql)->result_array();
 	}
 	public function get_entry_third_group($iname){
 		$sql = "SELECT eid FROM `apps_entry` where imid=9 and piid_type=0 and name='{$iname}' ORDER BY `eid` ASC ";
 		return $this->db->query($sql)->result_array();
 	}

 	public function get_category_second(){
//		$mem_key	= 'get_category_'.$imid;
//		$result 	= $this->mycache->get($mem_key);

//		if( !is_array($result) || empty ($result)){
			$sql 	= "SELECT * FROM `interest_category` where `imid`=9 and `is_list`=0 ";
			$result	= $this->db->query($sql)->result_array();
			return $result;
//			$this->mycache->set($mem_key, $result , 800);
//		}
//		return $result;
	}

 }
?>
