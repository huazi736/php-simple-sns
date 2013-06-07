<?php
/**
 * 赞管理文件
 * 
 * @author yangshunjun 2012-07-03 
 *
 */

include_once('./CMS.php');

class LikeService extends DK_Service {

    public function __construct() {
        parent::__construct();
        $this->init_db('system');
        $this->init_memcache('default');
    }
	
    private $_debug = 1; //@TODO 生产环境改为0

	/**
	 * @desc 获取年份
	 * @author  lijianwei
	 * @date    2012-03-19
	 * @access    public
	 * @param int $uid  访问者id
	 * @param int $action_uid  被访问者id
	 * @return array
	 */
	public function getYears($uid = 0, $action_uid = 0) {
		if(!is_numeric($uid) || !is_numeric($action_uid)) return array();
		//@todo 更优缓存
		if(!$this->_debug) {
		    $flag = md5($uid. "-". $action_uid);
			$tmp_years = unserialize($this->memcache->get($flag));
			if(is_array($tmp_years) && count($tmp_years)) return $tmp_years;
		}
		$map = array();
		$field = 'ctime';
		$map['_string'] = "tid <> 0 AND ((object_type in ('topic','blog','photo','video','album','forward') AND ( src_uid = $action_uid OR uid = $action_uid )) OR (object_type in ('web_topic','web_blog','web_photo','web_video','web_album') AND uid = $action_uid AND src_uid <> uid))";
		
		$data = $this->db->from('likes')->where($map)->order_by("ctime DESC")->get()->result_array();
		$arr = array();
		foreach ($data as $val) {
			$arr[] = $val['ctime'];
		}
		rsort($arr);
		$data = $arr;
		if(!count($data)) return array();
		$bc_year = array();
		$year = array();
		foreach($data as $val) {
			$strlen = strlen($val);
			if (substr($val,0,1) != '-') {
				if ($strlen > 10 && $strlen < 15) {
					$year[] = substr($val, 0 , $strlen - 10);
				} elseif (is_numeric($val)) {
					$year[] = date("Y", $val);
				} 
			} else {
				if (11 < $strlen && $strlen < 16) {
					$bc_year[] = 'B.C ' . substr($val, 1 ,$strlen - 11) . "年";
				} elseif (15 < $strlen && $strlen < 20) {
					$bc_year[] = 'B.C ' . (int)floor(substr($val, 1 ,$strlen - 11)/10000) . "万年"; 
				} elseif (19 < $strlen && $strlen < 24) {
					$bc_year[] = 'B.C ' . (int)floor(substr($val, 1 , $strlen - 11)/100000000) . "亿年";
				}
			}
		}
		rsort($year);
		$output = array_unique(array_merge($year,$bc_year));
		if(!$this->_debug) {
			$this->memcache->set($flag, serialize($output), 86400);  //24小时过期
		}
		return $output;
	}
	
	/**
	 * @desc 获取信息流ID
	 * @author  sunlufu
	 * @date    2012-03-19
	 * @access    public
	 * @param int $uid  访问者id
	 * @param int $action_uid  被访问者id 
	 * @param int $year  年份参数
	 * @param int $type  1 别人赞你  2  你赞个人 
	 * @return array
	 */
	public function getObject_ids($uid = 0, $action_uid = 0, $year = 0,$type = 1) {
		if(!is_numeric($uid) || !is_numeric($action_uid) || !is_numeric($type) || !is_string($year)) return array();
		if(!$this->_debug) {
			$key = md5($uid.'-'.$action_uid.'-'.$type.'-'.$year);
			$tmp_object_ids = unserialize($this->memcache->get($key));
			if(is_array($tmp_object_ids) && count($tmp_object_ids)) return $tmp_object_ids;
		}
		$map = array();
		if($year) {
			$start = strtotime($year.'-1-1 00:00:00')-1;
			$end   = strtotime(($year+1).'-1-1 00:00:00');
			$map['_string'] = "$start < ctime AND ctime < $end AND object_type in ('topic','photo','album','blog','forward','video')";
		}
		switch($type) {
			case 1:
				$map['src_uid'] = $action_uid;
				$map['uid'] = array('neq',$action_uid);
				break;
			case 2:
				$map['uid'] = $action_uid;
				break;
			default :
				break;
		}
		$like = M('likes');
		$data = $this->db->select(tid,ctime)->from('likes ')->where($map)->order_by('ctime DESC')->get()->result_array();
		
		if(!count($data)) return array();
		$object_ids = array();
		foreach($data as $v) {
			$object_ids[$v['tid']] = $v['ctime'];
		}
		arsort($object_ids);
		$data = array_keys($object_ids);
		if(!$this->_debug) {
			$this->memcache->set($key, serialize($data), 300);
		}
		return $data;     //返回已经按ctime排好序的IDS
	}
	
	
	/**
	 * 获取赞了网页的信息流IDS
	 * @param int $action_uid
	 * @param int $year
	 * @return array
	 */
	public function getWebObjectids($action_uid = 0 , $year = '0') {
		if(!is_numeric($action_uid) ||  !is_string($year)) return array();
		if(!$this->_debug) {
			$key = md5($action_uid.'-web_topic-'.$year);
			$tmp_object_ids = unserialize($this->memcache->get($key));
			if(is_array($tmp_object_ids) && count($tmp_object_ids)) return $tmp_object_ids;
		}
		$map = array();
		if (substr($year, 0 , 1) == '-') {
			if (strlen($year) < 6) {
				$map['_string'] = "uid = $action_uid AND src_uid <> $action_uid AND object_type in ('web_topic','web_blog','web_album','web_photo','web_video') AND ctime like '" . $year . "____000000'";   //公元前年
			} else {
				$map['_string'] = "uid = $action_uid AND src_uid <> $action_uid AND object_type in ('web_topic','web_blog','web_album','web_photo','web_video') AND ctime='" . $year . "'";			   //公元前万年、亿年
			}
			
		} else {
			$map['_string'] = "uid = $action_uid AND src_uid <> $action_uid AND object_type in ('web_topic','web_blog','web_album','web_photo','web_video') AND ctime like '" . $year . "__________'"; //公元后
		}
		
		$data = $this->db->select('tid,ctime')->from('like')->where($map)->order_by('ctime DESC')->get()->result_array();
		if(!count($data)) return array();
		$object_ids = array();
		foreach($data as $v) {
			$object_ids[$v['tid']] = $v['ctime'];
		}
		arsort($object_ids);
		$data = array_keys($object_ids);
		if(!$this->_debug) {
		 $this->memcache->set($key, serialize($data), 300);
		}
		return $data;
	}
}