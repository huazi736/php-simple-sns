<?php
class MoviesModel extends MY_Model {

	function __construct()
	{
		parent::__construct();
	}
	
	// 获取省级
	public function getProvice()
	{
		$sql = "SELECT * FROM interest_category where imid='10' and is_list='0' and is_display='1'";
		$res = $this->db->query($sql)->result_array();
		if($res) {
			return $res;
		}
		return false;
	}
	
	public function addProvice()
	{
		$sql = "insert into interest_category (`iname`,`imid`,`sort`,`is_system`)  (select `iname`,10,999,0 from interest_category where imid=9 and is_list='0' and is_display='1')";
		$res = $this->db->query($sql);
		if($res) {
			return true;
		}
		return false;
	}
	
	function getCity($imid = null)
	{
		if(empty($imid)) {
			return false;
		}
		$sql = "SELECT * FROM interest_category where imid={$imid}";
		$res = $this->db->query($sql)->result_array();
		if($res) {
			return $res;
		}
		return false;
	}
	
	public function getCityInfo($city_id = null)
	{
		if(empty($city_id)) {
			return false;
		}
		$sql = 'SELECT * from apps_info where iid='.$city_id;
		$res = $this->db->query($sql)->row_array();
		if($res) {
			return $res;
		}
		return false;
	}
	
	function addCity($imid = null, $iid = null, $name = null, $piid = null, $piid_type = 0)
	{
		$sql = "insert into apps_entry (`imid`,`iid`,`piid`,`piid_type`,`name`) 
				values ('{$imid}','{$iid}','{$piid}','{$piid_type}','{$name}')";
		$res = $this->db->query($sql);
		if($res) {
			return $res;
		}
		return false;
	}
	
	public function getCinema($eid = null)
	{
		if(empty($eid)) {
			return false;
		}
		$sql = "select * from cinema where eid='{$eid}'";
		$res = $this->db->query($sql)->result_array();
		if($res) {
			return $res;
		}
		return false;
	}
	
	public function getCinemaInfo($cid = null)
	{
		if(empty($cid)) {
			return false;
		}
		$sql = "SELECT * FROM cinema where id='{$cid}'";
		$res = $this->db->query($sql)->row_array();
		if($res) {
			return $res;
		}
		return false;
	}
	
	
}
?>