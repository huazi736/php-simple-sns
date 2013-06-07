<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class	videomodel extends MY_Model {
	/**
	 *构造函数
	 *自动加载数据库
	 */
	public function __construct(){
		parent::__construct();
	}
	
	public function test()
	{
		$sql = "SELECT * FROM `web_video`";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		return $data;
	}
	public function test1($id)
	{
		$sql = "SELECT * FROM `web_video` where id >=$id order by id asc limit 0, 2";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		return $data;
	}
	/*
	 * 判断视频vid是否存在
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $vid 视频信息
	 */
	function isVid($vid){
		if(!$vid){
			return false;
		}
		$query = $this->db->query('SELECT id FROM `web_video` where `id`='.$vid);
		$num = $query->row_array();
		if(!$num){
			return false;
		}
		return $num;
	}
	/*
	 * 获取视频列表
	 *
	 * @author wangying
	 * @date   2012/02/24
	 * @access public
	 * @param integer $identity    访问者的身份
	 * @param integer $action_uid  被访问者的用户编号
     * @param integer $uid       访问者的用户编号
	 * @param integer $limit     输出数据sql中的limit的数量
	 * @param integer $dateline  日期时间戳
     * @return array
	 */
	public function getVideoLists($action_uid,$web_id,$limit,$dateline=null){
		$select = "SELECT `id`,`title`,`lentime`,`video_pic`,`dateline`,`status` FROM `web_video` WHERE `uid` = $action_uid AND `web_id`=$web_id AND `status` IN (1,4,5)";
		$where_dateline =$dateline?' AND dateline <= '.$dateline:'';
		$order = ' ORDER BY `dateline` DESC ';
		$limit = " LIMIT $limit";
		$sql = "$select $where_dateline $order $limit";
		return $this->getCache($sql,'result_array',10);
	}
	/*
	 * 获取视频列表视频总数量
	 *
	 * @author wangying
	 * @date   2012/02/24
	 * @access public
	 */
	public function getVideoListsAllNums($action_uid,$web_id,$dateline=null){
		$select_rows = " SELECT count(*) as rows FROM `web_video` WHERE `uid` = ".$action_uid." AND `web_id`=".$web_id." AND `status` IN (1,4,5) ";
		$where_dateline = $dateline?' AND dateline <= '.$dateline:'';
		return $this->getCache($select_rows.$where_dateline,'row_array',10);
	}
	/**
	 * 缓存应用(只能用在select操作)
	 *
	 * @author wangying
	 * @date   2012/06/4
	 * @access public
	 * @param  string $sql select
	 * @param  string $action 查询记录集('result_array','row_array')
	 * @param  int $ttl 有效期(单位s)
     * @return array
	 */
	public function getCache($sql,$action,$ttl){
		//$key = md5($sql);
		//$memcache = get_cache($key,'video');
		//if($memcache){
		//	return $memcache;
		//}else{
			$query = $this->db->query($sql);
			$data = $query->$action();
		//	set_cache($key,$data,$ttl,'video');
			return $data;
		//}
	}
	/*
	 * 取得某个视频播放信息
	 *
	 * @author wangying qqyu
	 * @date   2012/02/24
	 * @access public
	 * @$dateline integer $vid 视频ID
	 * @$dateline string $data 字段集合
     * @return array
	 */
	function getOneVideoinfo($vid,$return_fields){
		if(!$vid){
			return false;
		}
		$sql = 'SELECT '.$return_fields.' FROM `web_video` where `id`='.$vid;
		return $this->getCache($sql,'row_array',10);
	}
	/*
	 * 新增视频
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $uid 使用者uid
	 * @param  array  $vd 视频信息
	 */
	function addVideo($uid = null,$vd = null){
		if(!$uid || !$vd ){
			return false;
		}
		$data['web_id'] = $vd['web_id'];
		$data['id']     = $vd['id'];
		$data['uid']    = $uid;
		$data['title']  = $vd['title'];
		$data['lentime']     = $vd['lentime'];
		$data['width']       = $vd['width'];
		$data['height']      = $vd['height'];
		$data['video_src']   = $vd['video_src'];
		$data['video_pic']   = $vd['video_pic'];
		$data['discription'] = $vd['discription'];
		$data['dateline']    = $vd['dateline'];
		if(isset($vd['timestr'])){
			$data['timestr'] = $vd['timestr'];
		}
		if($vd['type'] != 'flv'){
			$data['status'] = 5;//转码
		}else{
			if($vd['check'] == 1){
				$data['status'] = 4;//审核		
			}else{
				$data['status'] = 1;//不审核（默认）	
			}
		}
		$this->db->insert('web_video', $data);
		$insert_res = $this->db->affected_rows();
		if(!$insert_res){
			return false;
		}
		return true;
	}

	/*
	 * 时间线调用视频数量
	 *
	 * @author wangying
	 * @date   2012/4/18
	 * @access public
     * @return array
	 */
	public function getTimelineVideoNum($web_id){
		$sql = 'SELECT count(*) as rows FROM `web_video` WHERE web_id = '.$web_id." AND status=1 ";
		$rows = $this->getCache($sql,'row_array',10);
		return $rows['rows'];
	}
	/*
	 * 取得某个视频播放信息
	 *
	 * @author wangying qqyu
	 * @date   2012/02/24
	 * @access public
	 * @$dateline integer $vid 视频ID
	 * @$dateline string $data 字段集合
     * @return array
	 */
	function getAccessVideo($vid = null,$data){
		if(!$vid){
			return false;
		}
		/*
		*缓存服务的使用
		*/
		if(config_item('memcache_star')){  //缓存开启
			$cache_get = array('key' => 'vidoe_info_'.$vd['id'],'group' => 'video');
			$cache_get_value = call_soap('cache','Mmemcache','get',$cache_get);
			if($cache_get_value){
				$arr =  unserialize($cache_get_value);
			}else{
				$sql = 'SELECT '.$data.' FROM `web_video` where `id`='.$vid;
				$query = $this->db->query($sql);
				$arr = $query->row_array();
				$cache_set = array('key' => 'vidoe_info_'.$vd['id'],'data' => serialize($arr),'ttl' => 0,'group' => 'video');
				call_soap('cache','Mmemcache','set',$cache_set);
			}
		}else{
			$sql = 'SELECT '.$data.' FROM `web_video` where `id`='.$vid;
			$query = $this->db->query($sql);
			$arr = $query->row_array();
		}
		return $arr;
	}
	/*
	 * 取得两个视频最新的视频
	 * @author qqyu
	 * @date   2012/03/26
	 * @param string $uid
	 */
	function getTowNewVideo($uid,$web_id){
		$query = $this->db->query('SELECT id,video_pic FROM `web_video` where `uid`='.$uid.' and `web_id`='.$web_id.' and status=1 order by dateline desc limit 0,2 ');
		$re = $query->result_array();
		return $re;
	}

	/*
	 * 删除视频（彻底删除）
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $uid 使用者uid
	 * @param  string $vid 视频ID
	 */
	function delVideo($vid = null,$uid = null)
	{
		if(!$vid ){
			return false;
		}
		$num = $this->isVid($vid);
		if(!$num){
			return false;
		}
		//直接删除该条视频数据
		if($uid){
			$this->db->where('uid',$uid);
		}
		$this->db->where('id',$vid);
		$this->db->limit(1);
		$res = $this->db->delete('web_video');
		if(!$res){
			return false;
		}
		return true;
	}

	/*
	 * 编辑视频信息
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  srting $uid 用户ID
	 * @param  srting $vid 视频ID
	 * @param  array  $update_data
	 */
	function updateVideo($vid,$update_data,$uid = null){
		if( !$vid || !$update_data ){
			return false;
		}
		if($uid)$this->db->where('uid',$uid);
		$this->db->where('id',$vid);
		$this->db->limit(1);
		$update_res = $this->db->update('web_video',$update_data);
		if(!$update_res){
			return false;
		}
		return true;
	}
	/*
	 * 取得某个视频播放信息
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $vid 视频ID
	 */

	function getVideoInfo($vid = null,$data = null,$module = null){
		if(!$vid ){
			return false;
		}
		if(!$data){
			$data = ' * ';
		}
		if($module == 1){
			$table = 'user_video';
		}elseif($module == 2){
			$table = 'web_video';
		}else{
			$table = 'other_video';
		}
		$sql = 'select '.$data.' from '.$table.' where `id`='.$vid.' limit 1';
		$query = $this->db->query($sql);
		$lists = $query->result_array();
		if(!$lists){
			return false;
		}
		return $lists[0];
	}
	/**
	 * 删除某个网页的所有视频（删除网页接口）
	 * @param $web_id 网页id
	 */
	function delWebVideo($web_id){
		$update_data['status'] = 3; // 3 代表删除网页是调用接口删除该网页的所有视频
		$this->db->where('web_id',$web_id);
		$update_res = $this->db->update('web_video',$update_data);
		if(!$update_res){
			return false;
		}
		return $update_res;
	}
	/**
	 * 检查是否有改网页视频
	 * Enter description here ...
	 * @param $web_id 网页id
	 */
	function checkWeb($web_id){
		$check_data = array('id','web_id');
		$sql = 'select '.$check_data.' from `web_video` where `web_id`='.$web_id.' limit 1';
		$query = $this->db->query($sql);
		$lists = $query->result_array();
		if(!$lists){
			return false;
		}
		return $lists;
	}
	/*
	 * 查询tmp_video表转码结果
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $vid 视频ID
	 */
	function getTmp($id=null,$vid=null){
		if( $id ){
			$this->db->where('id',$id);
		}
		if( $vid ){
			$this->db->where('vid',$vid);
		}
		$this->db->limit(1);
		$query = $this->db->get('tmp_video');
		$lists = $query->result_array();
		if(!$lists){
			return false;
		}
		return $lists[0];
	}
	/*
	 * 新增视频信息到tmp_video表
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $video_src 视频存储路径
	 */
	function addVideoTmp($data){
		if(!$data ){
			return false;
		}
		$this->db->insert('tmp_video', $data);
		$insert_res = $this->db->affected_rows();
		if(!$insert_res){
			return false;
		}
		return true;
	}
	/*
	 * 查询tmp_video表转码结果
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $vid 视频ID
	 */
	function delTmp($id=null,$vid=null){
		if( $id ){
			$this->db->where('id',$id);
		}
		if( $vid ){
			$this->db->where('vid',$vid);
		}
		$this->db->limit(1);
		$res = $this->db->delete('tmp_video');
		if(!$res){
			return false;
		}
		return $res;
	}
	/*
	 * 通知tmp_video表需要转码
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $vid 视频ID
	 */
	function updateTmp($update_data,$id = null,$vid = null){
		if( !$update_data ){
			return false;
		}
		if($id){
			$this->db->where('id',$id);
		}
		if($vid){
			$this->db->where('vid',$vid);
		}
		$this->db->limit(1);
		$update_res = $this->db->update('tmp_video',$update_data);
		if(!$update_res){
			return false;
		}
		return true;
	}
	
}
