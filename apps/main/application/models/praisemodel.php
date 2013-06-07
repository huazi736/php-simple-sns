<?php
/**
 * @desc 赞的模型类
 * @author lijianwei
 * @date 2012-03-19
 */
require_cache(APPPATH. "core/MY_Redis.php");
class PraiseModel extends MY_Model {
	private  $_redis;
	public function __construct(){
		parent::__construct();
		$this->_redis = MY_Redis::getInstance();
		/*测试插入
		 $test = array("uid" => "1000001035", "uname" => "李建伟", "from" => "1", "content" => "测试一", 
		 "type" => "info", "dateline" => "1332321692", "permision" => "3", "ctime" => "1332321692",
		 "tid" => "34", "hot" => "0", "highlight" => "0");
		 $test2 = array("uid" => "1000001035", "uname" => "李建伟", "from" => "1", "content" => "测试二", 
		 "type" => "info", "dateline" => "1332321692", "permision" => "3", "ctime" => "1332321692",
		 "tid" => "35", "hot" => "0", "highlight" => "0");
		 $this->_redis->hMset("Topic:34", $test);
		 $this->_redis->hMset("Topic:35", $test2);
		 */
	}
	/**
	 *  获取信息流内容
	 *
	 *  @author  lijianwei
	 *  @date    2012-03-19
	 *  @access    public
	 *  @param array $topic_ids  信息流id
	 *  @return    array
	 */
	public function getAllTopics($topic_ids = array(), $type) {
		$key = (int)$type == 3 ? "Webtopic:" : "Topic:";
		$data = array();
		if(count($topic_ids) && is_array($topic_ids)) {
			foreach ( $topic_ids as $topic_id ) {
				$tmp = $this->_redis->hGetAll ( $key . $topic_id);
				if (empty($tmp)) {
					continue;
				}
				$data[] = $tmp;
			}
				
			//按ctime进行排序
			/*
			 $len = count ( $data );
			 if ($len > 0) {
				if ($type != 3) {
				foreach ( $data as $k => $v ) {
				if (isset ( $v ['ctime'] )) {
				if ($v ['ctime'] < $birthday) {
				unset ( $data [$k] );
				}
				} else {
				unset ( $data [$k] );
				}
				}
				}
				}
				*/
			return $data;
		}
		return array();
	}
	/**
	 * 获取某个特定topic的信息
	 *
	 * @param int $topicId
	 */
	public function getTopic($topicId) {
		$topicId = intval($topicId);
		if($topicId < 0) {
			return array();
		}
		return $this->_redis->hGetAll("Topic:$topicId");
	}
	/**
	 *  处理信息流,输出到前端
	 *
	 *  @author  lijianwei
	 *  @date    2012-03-19
	 *  @access    public
	 *  @param array $topics  信息流
	 *  @return    array
	 */
	public function comboTopics($topics = array(),$object_type = 'topic') {
		if(!count($topics)) return array();
		foreach($topics as  $key => $topic) {
			if ($object_type == 'topic') {
				$topic ['friendly_time'] = friendlyDate ( $topic ['ctime'] );
				//增加获取头像地址功能
				$topic['headpic'] = get_avatar($topic['uid'],'s');
			} else {
				/*
				$strlen = strlen ( $topic['ctime'] );
				if (substr ( $topic['ctime'], 0, 1 ) != '-') {
					$topic ['friendly_time'] = substr ( $topic['ctime'], 0, $strlen - 10 ) . "年" . substr ( $topic['ctime'], $strlen - 10, 2 ) . "月" . substr ( $topic['ctime'], $strlen - 8, 2 ) . "日";
				} else {
					if (11 < $strlen && $strlen < 16) {
						$topic ['friendly_time'] = '公元前' . substr ( $topic['ctime'], 1, $strlen - 11 ) . "年" . substr($topic['ctime'], $strlen - 10,2) . "月" . substr($topic['ctime'], $strlen - 8,2) . "日";
					} elseif (15 < $strlen && $strlen < 20) {
						$topic ['friendly_time'] = '公元前' . ( int ) floor ( substr ( $topic['ctime'], 1, $strlen - 11 ) / 10000 ) . "万年";
					} elseif (19 < $strlen && $strlen < 24) {
						$topic ['friendly_time'] = '公元前' . ( int ) floor ( substr ( $topic['ctime'], 1, $strlen - 11 ) / 100000000 ) . "亿年";
					}
				}
				*/
				if (isset ( $topic ['pid'] )) { //获取信息流 的头像地址
					if ($topic ['pid'] > 0) {
						$get_headimage = get_webavatar ( $topic ['uid'], 's', $topic ['pid'] );
						$topic ['headpic'] = $get_headimage;
						//加上web地址 
						$topic ['web_url'] = rtrim ( WEB_DUANKOU_ROOT, '/' ) . '/main/?web_id=' . $topic ['pid'];
					}
				}
				
				
				$topic['friendly_time'] = makeFriendlyTime($topic['ctime']);
			}
			
			if (isset($topic['type'])) {
				if ($topic['type'] == 'album' || $topic['type'] == 'forward' || $topic['type'] == 'web_album' || $topic['type'] == 'web_photo') {
					$topic = $this->prepareTopic($topic);
				}
			}
			$topics[$key] = $topic;
		}
		return $topics;
	}

	/**
	 * 处理格式化数据     参照timelinemodel.php中prepareTopic方法
	 *
	 * @param array $topic
	 */
	private function prepareTopic($topic)
	{
		switch ($topic['type']) {
			case 'album':
				$topic['picurl'] = json_decode($topic['picurl'], true);
				return $topic;
			case 'forward':
				$topic['forward'] = $this->getTopic($topic['fid']);
				if ($topic['forward'] && $topic['forward']['type'] == 'album') {
					$topic['forward']['picurl'] = json_decode($topic['forward']['picurl']);
				}
				return $topic;
			case 'web_album':
				$topic['picurl'] = json_decode($topic['picurl'], true);
				return $topic;
			default:
				return $topic;
		}
	}

	/**
	 * 获取访问者与被访问者之间的关系
	 *  @author  lijianwei
	 *  @date    2012-03-19
	 *  @access    public
	 *  @param int $uid  访问者id
	 *  @param int $action_uid  被访问者id
	 *  @return int 关系      0  错误     2  无关系   4  粉丝   6   相互关注  8   已发送请求     10  好友       edit by lijianwei  getRelationWithUser  接口改为 getRelationStatus
	 */
	public function getRelation($uid = 0, $action_uid = 0) {
		if($uid == $action_uid) return '100';
		return call_soap("social", "Social", "getRelationStatus", array($uid, $action_uid));
	}

	/**
	 * 获取访问者与多个被访问者之间的关系
	 *  @author  lijianwei
	 *  @date    2012-05-25
	 *  @access    public
	 *  @param int $uid  访问者id
	 *  @param array $action_uids  多个被访问者ids
	 *  @return int 关系      0  错误     2  无关系   4  粉丝   6   相互关注  8   已发送请求     10  好友        关系状态的集合，集合中索引为：字母u加用户ID
	 */
	public function getRelations($uid = 0, $action_uids= array()) {
		$result = call_soap("social", "Social", "getMultiRelationStatus", array($uid, $action_uids));  //批量获取关系   有错误
		if(is_array($result)) {
			$i = 0;
			foreach($result as $k => $v) {
				if(!$v && ($uid == $action_uids[$i])) $result[$k] = 100; //自己
				$i++;
			}
		}
		return $result;

	}

	/**
	 * @desc 验证用户是否有访问某条信息流权限, 如果没有，删除信息流
	 *  @author  lijianwei
	 *  @date    2012-03-19
	 *  @access    public
	 *  @param int $uid 当前登录用户
	 *  @param array $topics 信息流
	 *  @param array $relations 关系数组
	 *  @return 返回过滤之后的信息流
	 */
	public function checkTopicPression($uid = 0, $topics = array(), $relations = array()) {
		if(empty($uid) || empty($topics) || empty($relations)) return array();

		foreach($topics as $k => $topic) {
			
			if(!isset($topic['uid']) || !isset($topic['permission'])) { unset($topic[$k]); continue; }
			
			$flag = 0;
			$relation = intval($relations['u'. $topic['uid']]);
			
			$permision = intval($topic['permission']);//信息流权限
			if(is_int($permision) && $permision < 9) {//判断信息流权限是否在用户设定的权限内
				switch($permision) {
					case 8://仅自己
						$flag =  ($relation == 100);
						break;
					case 4://好友
						$flag =  in_array($relation, array(10, 100));
						break;
					case 3://粉丝
						$flag =  in_array($relation, array(4, 6, 8, 10, 100));
						break;
					case 1://公开
						$flag = 1;
						break;
				}
			}else {//自定义
				$pre_arr = explode(",", $permision);
				$flag =  in_array($uid, $pre_arr);
			}
			if(!$flag)unset($topics[$k]);	
		}
		return $topics;
	}
	/**
	 * 获取评论、赞信息
	 *  @author  lijianwei
	 *  @date    2012-03-19
	 *  @access    public
	 *  @param array $id
	 *  @return json
	 *  @deprecated
	 */
	public function getStat($topicIds = array(),$object_type = 'topic') {
		$stats = call_soap('comlike', 'Index', 'call_stat', array($topicIds, $object_type));
		return json_decode($stats, true);
	}
	public function __destruct() {
		$this->_redis = null;
	}
}
/* End of file praisemodel.php */
/* Location: ./main/application/models/praisemodel.php */