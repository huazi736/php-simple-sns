<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class	videomodel extends MY_Model {
	/**
	 *构造函数
	 *自动加载数据库
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	public function test($id)
	{
		$sql = "SELECT * FROM `user_video` where id >=$id order by id asc limit 0, 2";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		return $data;
	}
	/**
	 * 根据identity获取sql语句中的where
	 *
	 * @author wangying
	 * @date   2012/04/18
	 * @access public
	 * @param integer $identity    访问者的身份
	 * @param integer $action_uid  被访问者的用户编号
     * @param integer $uid       访问者的用户编号
     * @return string
	 */
	 public function getWhereFromIdentity($identity,$action_uid,$uid,$object_type=NULL){
		if($identity == 5){//访问者为自己
			$str = $object_type ? " AND `object_type`= $object_type ": '' ;
			return " WHERE `uid` = $action_uid AND `status` in (1,4,5) $str ";

		}elseif($identity == 4){//访问者为好友
			return "  WHERE `uid` = $action_uid AND `status`=1  AND (`object_type` = 1 OR `object_type` = 3 OR `object_type` = 4 OR `object_content` LIKE '%".$uid."%') ";
		}else{//$identity访问者为互相关注3/粉丝2/访问者为陌生人1
			 return " WHERE `uid` = $action_uid AND `status`=1  AND `object_type` = 1 ";
		}
	 }
	/**
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
	 * @param integer $object_type  视频权限
     * @return array
	 */
	public function getVideoLists($identity,$action_uid,$uid,$limit=NULL,$dateline=NULL,$object_type=NULL)
	{
		if(!$identity && !$action_uid && !$uid){
			return false;
		}
		if($limit){
			$offset = ($limit-1)*16;
			$limit = 16;
		}else{
			$limit = 1;
			$offset = 0;
		}
		$select = 'SELECT `id`,`uid`,`title`,`lentime`,`video_pic`,`dateline`,`status` FROM `user_video` ';
		$select_rows = 'SELECT count(*) as rows FROM `user_video` ';
		$where_dateline =$dateline?' AND dateline <= '.$dateline:'';
		$order = ' ORDER BY `dateline` DESC ';
		$limit = " LIMIT $offset,$limit";
		$where = $this->getWhereFromIdentity($identity,$action_uid,$uid,$object_type);
		$sql = "$select $where $where_dateline $order $limit";
		$sql_count = "$select_rows $where $where_dateline";
		$arr = array();
		$arr['data'] = $this->getCache($sql,'result_array',10);
		$arr['count_rows'] = $this->getCache($sql_count,'row_array',10);
		return $arr;
	}
	/**
	 * 缓存应用(只能用在select操作)
	 * 获取单个视频信息，key为视频的id值，使用row_array
	 * 获取一组视频信息，key为md5加密值，使用result_array
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
			//print_r($sql);exit;
			//SELECT `id`,`uid`,`title`,`lentime`,`video_pic`,`dateline` FROM `user_video` WHERE `uid` = 1000002007 AND `status`=1 ORDER BY `dateline` DESC LIMIT 0,1
			$query = $this->db->query($sql);
			$data = $query->$action();
			//set_cache($key,$data,$ttl,'video');
			return $data;
		//}
	}
	/**
	 * 取得某个视频播放信息
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  int $vid 视频ID
	 * @param  string $return_fields 表字段 例：$fields = 'id,uid';
	 */
	function getVideoInfo($vid,$return_fields = null,$module = null){
		if(!$vid ){
			return false;
		}
		if(!$return_fields){
			$return_fields = ' * ';
		}
		if($module == 1){
			$table = 'user_video';
		}elseif($module == 2){
			$table = 'web_video';
		}else{
			$table = 'other_video';
		}
		$sql = 'select '.$return_fields.' from '.$table.' where `id`='.$vid.' limit 1';		
		$query = $this->db->query($sql);
		$lists = $query->result_array();
		if(!$lists){
			return false;
		}
		return $lists[0];
	}
	/**
	 * 取得两个视频最新的视频
	 * @author qqyu
	 * @date   2012/03/26
	 * @param string $uid
	 */
	function getTowNewVideo($uid){
		$query = $this->db->query('SELECT id,video_pic FROM `user_video` WHERE `uid`='.$uid.' AND `object_type` = 1 AND status = 1 ORDER BY dateline DESC LIMIT 0,2 ');
		return  $query->result_array();
	}
	/**
	 * 取得某个人最新的一个公开视频
	 * @author qqyu
	 * @date   2012/03/26
	 * @param string $uid
	 */
	function getNewVideo($uid){
		$query = $this->db->query('SELECT id,video_pic FROM `user_video` WHERE `uid`='.$uid.' AND `object_type` = 1 AND status = 1 ORDER BY dateline DESC LIMIT 0,1 ');
		$info = $query->result_array();
		return $info[0];
	}
	/**
	 * 新增视频(其他模块)
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  array  $vd 视频信息
	 */
	function addOtherVideo($vd = null){
		if(!$vd ){
			return false;
		}
		$data['id']   = $vd['id'];
		$data['mid']  = $vd['mid'];
		$data['video_src'] = $vd['video_src'];
		$data['video_pic'] = $vd['video_pic'];
		$data['width']     = $vd['width'];
		$data['height']    = $vd['height'];
		$data['lentime']   = $vd['lentime'];
		$data['dateline']  = $vd['dateline'];
		if($vd['type'] != 'flv'){
			$data['status'] = 5;//转码
		}else{
			if($vd['check'] == 1){
				$data['status'] = 4;//审核		
			}else{
				$data['status'] = 1;//不审核（默认）	
			}
		}
		$this->db->insert('other_video', $data);
		$insert_res = $this->db->affected_rows();
		if(!$insert_res){
			return false;
		}
		return true;
	}
	/**
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
	/**
	 * 通知tmp_video表需要转码
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $vid 视频ID
	 */
	function updateTmp($update_data,$id=null,$vid=null){
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
	/**
	 * 删除tmp_video表数据
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
	/**
	 * 删除视频（彻底删除）
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $uid 使用者uid
	 * @param  string $vid 视频ID
	 */
	function delVideo($vid = null,$uid = null){
		if(!$vid ){
			return false;
		}
		//判断该视频是否存在
		$num = $this->isVid($vid,1);
		if(!$num){
			return false;
		}
		//直接删除该条视频数据
		if($uid){
			$this->db->where('uid',$uid);
		}
		$this->db->where('id',$vid);
		$this->db->limit(1);
		$res = $this->db->delete('user_video');
		if(!$res){
			return false;
		}
		return true;
	}
	/**
	 * 判断视频vid是否存在(视频模块)
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $vid 视频信息
	 */
	function isVid($vid,$model=null ){
		if(!$vid){
			return false;
		}
		if($model == 1){
			$name = 'user_video';
		}elseif($model == 3){
			$name = 'web_video';
		}else{
			$name = 'other_video';
		}
		$query = $this->db->query('SELECT id FROM '.$name.' where `id`='.$vid);
		$num = $query->row_array();
		if(!$num){
			return false;
		}
		return $num;
	}
	/**
	 * 新增视频
	 *
	 * @author qqyu
	 * @date   2012/02/23
	 * @access public
	 * @param  string $uid 使用者uid
	 * @param  array  $vd 视频信息
	 */
	function addVideo($uid = null,$vd = null ){
		if(!$uid || !$vd ){
			return false;
		}
		$data['id'] = $vd['id'];
		$data['uid'] = $uid;
		$data['title'] = $vd['title'];
		$data['lentime'] = $vd['lentime'];
		$data['width'] = $vd['width'];
		$data['height'] = $vd['height'];
		$data['video_src'] = $vd['video_src'];
		$data['video_pic'] = $vd['video_pic'];
		$data['discription'] = $vd['discription'];
		$data['object_type'] = $vd['object_type'];
		$data['object_content'] = $vd['object_content'];
		$data['dateline'] = $vd['dateline'];
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
		$this->db->insert('user_video', $data);
		$insert_res = $this->db->affected_rows();
		if(!$insert_res){
			return false;
		}
		return true;
	}	
	/**
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
		$this->db->where('status',1);
		$this->db->limit(1);
		$update_res = $this->db->update('user_video',$update_data);
		if(!$update_res){
			return false;
		}
		return $update_res;
	}
	/**
	 * 时间线调用视频数量
	 *
	 * @author wangying
	 * @date   2012/4/18
	 * @access public
     * @return array
	 */
	public function getTimelineVideoNum($identity,$action_uid,$uid){
		$select_rows = 'SELECT count(*) as rows FROM `user_video` ';
		$where = $this->getWhereFromIdentity($identity,$action_uid,$uid);
		$sql_count = "$select_rows $where";
		$query_count = $this->db->query($sql_count);
		$count_rows = $query_count->row_array();
		return $count_rows['rows'];
	}
	/**
	 * 增加视频播放次数
	 * 
	 * @param string $type  视频所属模块 个人 video 网页 webvideo
	 * @param int    $vid   视频id
	 */
	function addVideoVolume($type,$vid){
		if($type == 'video'){
			$table = 'user_video';
		}else{
			$table = 'web_video';
		}
		$sql = 'update '.$table.' set `volume`= volume + 1 where `id`='.$vid;
		$query = $this->db->query($sql);
		$rs = $this->db->affected_rows();
		if(!$rs){
			return false;
		}
		return true; 
	}	
}