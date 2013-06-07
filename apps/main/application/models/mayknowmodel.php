<?php
/**
 * 可能认识的人模型
 *
 * 当前业务逻辑中, 只有朋友请求详细页面使用
 * 算法不是很完善, 暂定已模型的方式处理
 * 后面根据业务需求, 可做成接口方式
 *
 * @author zengmm
 * @date 2012/7/7
 * @history lijianwei 2012-03-29
 */

class MayknowModel extends MY_Model{

	private $_redis;
    
	/**
	 * 个人使用的redis键名
	 *
	 * @var string
	 */
	private $friendkey = "friend:%d";
	private $hiddenfriendkey = "friend:hidden:%d";
	private $tmp_may_know_hash_key = "mayknow:%d";
	private $followerkey = "following:%d";
	private $hiddenfollowingkey = "following:hidden:%d";
	
	/*
	 * 网页使用的redis键名
	 *
	 * @var string
	 */
	
	// 用户关注网页集合
	private $_webpage_following_key = "webpage:following:%d";

	// 用户关注网页隐藏集合
	private $_webpage_following_hidden_key = "webpage:following:hidden:%d";

	// 临时使用   60秒过期  关注的全部网页(有序集合)
	private $_tmp_zset_key = "tmp:mayknow:webpage:following:%d";

	// 网页二级分类集合
	private $_webpage_sids_key = "tmp:mayknow:webpage:sids:%d"; 

	/**
	 * 推荐网页使用的redis键名
	 *
	 * @var string
	 * @deprecated
	 */

	// 推荐网页list集合
	private $recoweb_list_key = "mayknow:recoweb:weblist:%d:%d";

	// 推荐网页hash集合
	private $recoweb_hash_key = "mayknow:recoweb:webhash:%d:%d:%d"; 
	
	//过期时间一天
	private $expire = 86400; 

	private $_debug = 1;

	/**
	 * 类库定义
	 *
	 * @var string
	 */

	// 用户库
	const USER_DB = 'user';

	/**
	 * 表定义
	 *
	 * @var string
	 */

	// 工作经历
	const USER_WORK = 'user_work';

	// 教育经历
	const USER_EDU = 'user_edu';

	// 用户信息
	const USER_INFO = 'user_info';

	/**
	 * 构造函数
	 */
    public function __construct() {
		parent::__construct();

		//实例化redis
		$this->init_redis();
		$this->_redis = $this->redis;
	}

	/**
	 * @desc 获取可能认识的人uids
	 * @param int $uid
	 * @return array
	 */
	public function getUserInfos($uid = 0, $page = 1, $size = 15) {
		$uid  = intval($uid);
		$page = intval($page);
		$size = intval($size);
		if($uid < 0 || $page < 1 || $size < 1) {
			return array();
		}
		//排序后的所有结果存入redis
		$mayknow_data = $this->_redis->lRange(sprintf($this->tmp_may_know_hash_key, $uid), 0, -1);
		if($this->_debug) $mayknow_data = array();//测试暂时关闭缓存

		if(empty($mayknow_data)) {
			//获取关注uid
			$follwing_uids = $this->getFollowing($uid);
			//获取好友UID
			$friends = $this->getFriends($uid);
			//获取同事uid
			$workmate_uid = $this->getWorkmate($uid);
			$workmate_uid = array_diff($workmate_uid, $follwing_uids); //踢除同事中关注的UID
			$workmate_uid = array_diff($workmate_uid, $friends);
			//获取同学uid
			$classmate_uid = $this->getClassmate($uid);
			$classmate_uid = array_diff($classmate_uid, $follwing_uids); //踢除同学中关注的UID
			$classmate_uid = array_diff($classmate_uid, $friends);
			$orginal_friends = $friends;

			//$friends_friends_setnames = call_user_func_array ( array ($this, "getFriendsKey" ), $friends ); //好友好友集合名称
			//$friends_friends = call_user_func_array ( array ($this->_redis, "sUnion" ), $friends_friends_setnames ); //好友的好友    已经剔除了重复值
			//array_unshift($friends, $uid);

			$friends_friends = $this->getFriend_Friends($friends); //获取好友的好友UID
			$combine = array_merge($friends, $follwing_uids, array($uid));
			//$friends_friends = array_diff($friends_friends, $friends); //剔除已经是自己的好友和自己
			//$friends_friends = array_diff($friends_friends, $follwing_uids);
			//$friends_friends = array_diff($friends_friends, array($uid));
			$friends_friends = array_diff($friends_friends, $combine);

			$new_mayknow_friends = array ();
			$new_mayknow_friends = $this->getCommonFriends($friends, $friends_friends);
			/*
			$same_friend_num = 0;
			//共同好友数量
			foreach ( $mayknow_friend as $k => $v ) {
			
				$same_friend_num = count ( array_intersect ( $orginal_friends, $this->getFriends ( $v ) ) ); //交集
				$new_mayknow_friends [$k] = array ("uid" => $v, "same_friend_num" => $same_friend_num );
			}
			*/
			if(empty($workmate_uid) && empty($classmate_uid) && empty($new_mayknow_friends)) return array ();
			$mayknow = array("workmate" => $workmate_uid, "classmate" => $classmate_uid, "mayknow_friend" => $new_mayknow_friends );
			//$this->_redis->hMset(sprintf($this->tmp_may_know_hash_key, $uid), $mayknow);
			//$this->_redis->expire(sprintf($this->tmp_may_know_hash_key, $uid), $this->expire); //设置过期
			$mayknow_data = $this->dealOutPut($mayknow);
			reset($mayknow_data);
			//测试时关闭缓存
			if($this->_debug){
				$this->_redis->del(sprintf($this->tmp_may_know_hash_key, $uid), 0, 0);
			}
			foreach($mayknow_data as $v) {
				$this->_redis->rPush(sprintf($this->tmp_may_know_hash_key, $uid ), $v );
			}
			$this->_redis->expire(sprintf($this->tmp_may_know_hash_key, $uid), $this->expire); //设置过期
		}
		$start = ($page-1)*$size;
		$mayknow_data = array_slice($mayknow_data, $start, $size);
		$mayknow_data = $this->getMayKnow($mayknow_data);
		return $mayknow_data;
	}

	/**
	 * 获取同事的UID数组
	 *
	 * @param int $uid 用户UID
	 *
	 * @history lijianwei
	 *
	 * @return array 
	 */
	private function getWorkmate($uid = 0) {
		$uid = intval($uid);
		if($uid < 1) {
			return array();
		}

		// 连接用户库
		$this->init_db(self::USER_DB);
		$this->db->select('company');
		$this->db->where('uid', $uid);

		$company = $this->db->get(self::USER_WORK)->row_array();

		if(empty($company)) {
			return array();
		}

		$this->db->where('company', $company['company']);
		$this->db->where('uid !=', $uid);

		$workmate_uid_arr = $this->db->get(self::USER_WORK)->result_array();

		$workmate_uid = $this->arrayTwoOneByField($workmate_uid_arr, 'uid');

		$workmate_uid = array_unique($workmate_uid);//去除重复数据
		

		return $workmate_uid;
	}
	

	 /**
	  * 获取同学UID列表
	  *
	  * @author zengmm
	  * @date 2012/7/7
	  *
	  * @param int $uid 用户UID
	  *
	  * @return array
	  *
	  * @history lijianwei
	  */
	 
	 private function getClassmate($uid = 0) {
		$uid = intval($uid);
		if($uid < 1) {
			return array();
		}

		// 连接用户库
		$this->init_db(self::USER_DB);

		$this->db->select('schoolname');

		$school_arr = $this->db->get(self::USER_EDU)->result_array();

		$school = $this->arrayTwoOneByField($school_arr, "schoolname"); //大学、中学、小学

		if(count($school)) {

			$this->db->select('uid');

			$this->db->distinct();
			
			$this->db->where('uid !=', $uid);
			$this->db->where_in('schoolname', $school);

			$classmate_arr = $this->db->get(self::USER_EDU)->result_array();

			$classmate_uid = $this->arrayTwoOneByField($classmate_arr, "uid");

			return $classmate_uid;

		} else {
			return array();
		}
	}

	/**
	 * 获取用户关注的人
	 *
	 * @author zengmm 
	 * @date 2012/7/7
	 * 
	 * @param int $uid 用户UID
	 *
	 * @return array
	 *
	 * @history lijianwei
	 */
	private function getFollowing($uid = 0) {
		$uid = intval($uid);
		if($uid < 1) {
			return array();
		}
		$followings = $this->_redis->zRange(sprintf($this->followerkey,$uid), 0, -1);
		$followings = $followings ? $followings : array();
		$hiddenfollowings = $this->_redis->zRange(sprintf($this->hiddenfollowingkey, $uid), 0, -1);
		$hiddenfollowings = $hiddenfollowings ? $hiddenfollowings : array();
		return array_merge($followings,$hiddenfollowings);
	}
	/**
	 * @desc 按照8种规则输出最后数据
	 * @param array $mayknow_data
	 */
	private function dealOutPut($mayknow_data = array()) {
		if(empty($mayknow_data)) return array();
		$case = '1';
		//同事
		$workmate = $mayknow_data['workmate'];
		$case .= empty($workmate) ? '0' : '1';
		//同学
		$classmate = $mayknow_data['classmate'];
		$case .= empty($classmate) ? '0' : '1';
		//好友的好友
		$mayknow_friend = $mayknow_data['mayknow_friend'];
		if(empty($mayknow_friend)) {
			$case .= '0';
		} else {
			//可能认识的人按共同好友数量排序
			$same_friend_num = $this->arrayTwoOneByField($mayknow_friend, 'same_friend_num');
			array_multisort($same_friend_num, SORT_DESC, $mayknow_friend);
			reset($mayknow_friend);
			$sortmayknow = $mayknow_friend;
			$mayknow_friend = $this->arrayTwoOneByField($mayknow_friend, 'uid');
			$case .= '1';
		}
		switch($case){
			case '1000'://三个数组都为空
				return array();
				break;
			case '1100'://只有同事不为空
				return $workmate;
				break;
			case '1010'://只有同学不为空
				return $classmate;
				break;
			case '1001'://只有可能认识的人不为空
				return $mayknow_friend;
				break;
			case '1110'://可能认识的人为空
				$common = array_intersect($workmate, $classmate);
				$newwork = array_diff($workmate, $common);
				$newclass = array_diff($classmate, $common);
				$result = array_merge($common, $newwork, $newclass);
				$result = array_unique($result);
				return $result;
				break;
			case '1101'://同学为空
				$common = array_intersect($mayknow_friend, $workmate);
				$newwork = array_diff($workmate, $common);
				$newmayknow = array_diff($mayknow_friend, $common);
				$result = array_merge($common, $newwork, $newmayknow);
				$result = array_unique($result);
				return $result;
				break;
			case '1011'://同事为空
				$common = array_intersect($mayknow_friend, $classmate);
				$newclass = array_diff($classmate, $common);
				$newmayknow = array_diff($mayknow_friend, $common);
				$result = array_merge($common, $newclass, $newmayknow);
				$result = array_unique($result);
				return $result;
				break;
			case '1111'://三个数组都不为空 方法一 测试：此方法是方法二执行时间的六分之一，是方法二占用内存的三分之一   by：sunlufu 2012.4.17
				$workclass = array_intersect($workmate, $classmate);
				$mayknowwork = array_intersect($mayknow_friend, $workmate);
				$mayknowclass = array_intersect($mayknow_friend, $classmate);
				//第一个条件的数据
				$first = array_intersect($mayknow_friend,$workclass);
				//第二个条件的数据
				$second = array_diff($workclass,$first);
				//第三个条件的数据
				$third = array_diff($mayknowwork,$first);
				//第四个条件的数据
				$fourth = array_diff($workmate,$workclass);
				$fourth = array_diff($fourth,$mayknowwork);
				//第五个条件的数据
				$fifth = array_diff($mayknowclass,$first);
				//第六个条件的数据
				$sixth = array_diff($classmate,$workclass);
				$sixth = array_diff($sixth,$mayknowclass);
				//第七个条件的数据
				$seventh = array_diff($mayknow_friend,$mayknowwork);
				$seventh = array_diff($seventh,$mayknowclass);
				$result = array_merge($first,$second,$third,$fourth,$fifth,$sixth,$seventh);
				$result = array_unique($result);
				return $result;
				/* //方法二 欲用该方法，打开该方法的注释并注释掉方法一即可   by：sunlufu 2012.4.17
				$combine = array_merge($workmate,$classmate,$mayknow_friend);
				$combine = array_unique($combine);
				$mayknowinfo = array();
				foreach($sortmayknow as $v) {
					$mayknowinfo[$v['uid']] = $v['same_friend_num'];
				}
				$key = '';
				$result = array();
				foreach($combine as $k => $v) {
					$key .= in_array($v,$workmate) ? '1' : '0';
					$key .= in_array($v,$classmate) ? '1' : '0';
					if(in_array($v,$mayknow_friend)) {
						$key .= '1'.str_pad($mayknowinfo[$v],5,'0',STR_PAD_LEFT);
					} else {
						$key .= '000000';
					}
					//$result[$key] = $v;
					$sortkey[] = $key;
					$result[$k]['key'] = $key;
					$result[$k]['uid'] = $v;
					$key = '';
				}
				array_multisort($sortkey, SORT_DESC, SORT_STRING, $result);
				reset($result);
				$ret = array();
				foreach($result as $v) {
					$ret[] = $v['uid'];
				}
				return $ret; */
				break;
		}
	}

	/**
	 * @desc 获取可能认识的人总数
	 * @param int $uid 用户id
	 * @param array $field  字段
	 * @return int num 可能认识的人总数
	 */
	public function getCount($uid = 0) {
		$uid = intval($uid);
		if($uid < 1) {
			return '0';
		}
		$num = $this->_redis->lSize(sprintf($this->tmp_may_know_hash_key, $uid));
		return $num ? $num : '0';
	}
	/**
	 * @desc 获取好友列表,包括自己
	 * @param int $uid
	 * @return array
	 */
	public function getFriends($uid = 0) {
		$uid = intval($uid);
		if($uid < 1) {
			return '0';
		}
		$tmp_mayknow_uid = sprintf("tmp:mayknow:%d", $uid);
		$this->_redis->zUnion($tmp_mayknow_uid, array(sprintf($this->friendkey, $uid), sprintf($this->hiddenfriendkey, $uid)));
		$this->_redis->expire($tmp_mayknow_uid, 60);
		$result = $this->_redis->zRange($tmp_mayknow_uid, 0, -1);
		$result = $result ? $result : array();
		return $result;
	}
	
	/**
	 * @desc 获取好友的好友ID
	 * @param array $friend_ids 好友的IDS
	 * @return array result     好友的好友IDS，去除重复
	 */
	private function getFriend_Friends($friend_ids = array()) {
		if(count($friend_ids) < 1) {
			return array();
		}
		$result = array();
		foreach ($friend_ids as $friend_id) {
			$friend_friend_ids = $this->getFriends($friend_id);
			$result = array_merge($result, $friend_friend_ids);
		}
		return array_unique($result);
	}

	/**
	 * @desc 获取共同好友数量及共同好友IDS
	 * @param array $myfriend_ids   自己好友IDS
	 * @param array $friend_friend_ids  好友的好友IDS
	 * @return array $result
	 */
	private function getCommonFriends($myfriend_ids = array(), $friend_friend_ids = array(), $ismayknow = false) {
		if (count($myfriend_ids) < 1 || count($friend_friend_ids) < 1) {
			return array();
		}
		$result = array();
		$i=0;
		foreach ($friend_friend_ids as $key => $friend_friend_id) {
			$temp = $ismayknow ? $this->getFriendKey($friend_friend_id) : $this->getFriends($friend_friend_id);
			$commonfriends = array_intersect($myfriend_ids, $temp);
			if(!empty($commonfriends)) {
				$result[$i]['uid'] = $friend_friend_id;
				$result[$i]['same_friend_num'] = count($commonfriends);
				//$result[$key]['same_friend_num']['same_friends'] = $commonfriends;    //共同好友IDS
				$ismayknow && $result[$i]['same_friend_info'] = $this->getMayKnow($commonfriends); //获取共同好友信息
				++$i;
			}
			/* if($ismayknow) {
				if(!empty($commonfriends)) {
					$result[$key]['same_friend_info'] = $this->getMayKnow($commonfriends);
				} else {
					unset($result[$key]);
				}
			} */
		}
		return $result;
	}

	private function getFriendKey($uid = 0) {
		$uid = intval($uid);
		if($uid < 1) {
			return array();
		}
		$result = $this->_redis->zRange(sprintf($this->friendkey, $uid), 0, -1);
		$result = $result ? $result : array();
		return $result;
	}
	
	/**
	 * @desc 二维数组根据字段转一维数组
	 * @param array $arr 二维数组
	 * @param array $field  字段
	 * @return array
	 */
	private function arrayTwoOneByField($arr = array(), $field = '') {
		if(!is_array($arr) || empty($arr)) {
			return array();
		}
		$return = array();
		foreach($arr as $k => $v) {
			$return[$k] = $v[$field];
		}
		return $return;
	}

	/**
	 * @desc 关注后从Redis删除被关注人ID
	 * @param int $uid
	 * @param int $mayknowuid
	 * return 
	 */
	public function updateIndex($uid = 0,$mayknowuid = 0) {
		$uid = intval($uid);
		$mayknowuid = intval($mayknowuid);
		if($uid < 1) {
			return '0';
		}
		$redisinfo = $this->_redis->lRange(sprintf($this->tmp_may_know_hash_key, $uid), 0, 1);
		//读Redis数据，为空则不更新
		if (!empty($redisinfo)) {
			return $this->_redis->lRem(sprintf($this->tmp_may_know_hash_key, $uid), $mayknowuid, 1);
		}
		return '0';
	}

	/**
	 * 获取用户简要信息
	 * @param type $uid 用户ID
	 * @param int $offset 开始下标
	 * @param int $size  获取大小
	 * @return array $result  
	 */
	private function getMayKnow($uids = array()) {
		if(empty($uids)) {
			return array();
		}
		$result = array();
		$uinfo = array();

		// 连接用户库
		$this->init_db(self::USER_DB);

		foreach ($uids as $key => $uid) {
			$uinfo = $this->_redis->hGetAll(sprintf("user:%d" , $uid));
			//!empty($uinfo) && $result[$key] = $uinfo;
			if(empty($uinfo)) {

				$this->db->select('uid, username, dkcode');

				$this->db->where('uid', $uid);

				$uinfo = $this->db->get(self::USER_INFO)->result_array();

				if(empty($uinfo)) continue;

				$result[$key]['id'] = $uinfo['uid'];
				$result[$key]['name'] = $uinfo['username'];
				$result[$key]['dkcode'] = $uinfo['dkcode'];

			} else {
				$result[$key] = $uinfo;
			}
		}
		return $result;
	}

	public function getMayKnowInfo($uid = 0, $page = 1, $size = 4) {
		$uid  = intval($uid);
		$page = intval($page);
		$size = intval($size);
		if($uid < 1 || $page < 1 || $size < 1) {
			return array();
		}
		//获得用户已经关注人的ids（包括隐藏关注）
		$follwing_uids = $this->getFollowing($uid);
		//获取当前用户好友ids
		$friends = $this->getFriendKey($uid);
		//获取当前用户好友的好友ids
		if(empty($friends)) {
			return array();
		}
		$friends_friends = array();
		foreach($friends as $v) {
			$ret = $this->getFriendKey($v);
			$friends_friends = array_merge($friends_friends, $ret);
		}
		$friends_friends = array_unique($friends_friends);
		$combine = array_merge($follwing_uids, $friends, array($uid));
		$friends_friends = array_diff($friends_friends, $combine);
		if(empty($friends_friends)) {
			return array();
		}
		//获得可能认识的人信息
		$result = array();
		$result = $this->getCommonFriends($friends, $friends_friends, true);
		$commonnum = array();
		foreach($result as $key=>$val) {
			$commonnum[] = $val['same_friend_num'];
		}
		array_multisort($commonnum, SORT_DESC, $result);
		$total = count($result);
		$strt = ($page - 1)*$size;
		$result = array_slice($result, $strt, $size);
		$result['total'] = $total;
		return $result;
	}
	public function test() {
		return serialize($this->_redis);
		$arr = $this->_redis->zRange("following:1000001000",0,-1);
		
		return $arr;
	}

	//获取兴趣db配置
	public function getInterestDbConfig() {
		$config_path = APP_PATH. DIRECTORY_SEPARATOR. "..". DIRECTORY_SEPARATOR ."interest". DIRECTORY_SEPARATOR. "Conf". DIRECTORY_SEPARATOR. "config.php";
		require $config_path;
		$array = require THINK_PATH . '/Conf/config.inc.php';
		return  array_merge($array, $mini);
	}

	public function testdb() {
		$config = $this->getInterestDbConfig();
		$m = D("MayKnow");
		//切换数据库
		$m->switchConnect($config);

		$data[1] = $m->table("apps_info")->limit(5)->findAll();

		 //关闭数据库 
		$m->closeConnect();
		//重新连接到默认的数据库
		$m->initConnect();

		$data[0] = $m->table("user_info")->limit(5)->findAll();
		return $data;
	}
	/**
	 * @desc 获取推荐网页信息
	 * @param int $sortid 词条二级分类id
	 * @param int $uid 用户uid
	 * @param int $page 页数
	 * @param int $size 每页显示的条数
	 * @return array
	 */
	public function getWebInfos($sortid, $uid = 0, $page, $size = 15) {
		$sortid = intval($sortid);
		$uid  = intval($uid);
		$page = intval($page);
		$size = intval($size);
		if($sortid < 1 || $uid < 0 || $page < 1 || $size < 1) {
			return array();
		}
		//排序后的所有结果存入redis
		$start = ($page-1)*$size;
		//$end = $start+$size-1;
		$recoweb_data = $this->_redis->lRange(sprintf($this->recoweb_list_key,$sortid,$uid), 0, -1);
		$ret = array();
		//if($this->_debug) $recoweb_data = array();//测试暂时关闭缓存

		if(empty($recoweb_data)) {
			/* //获取二级分类下的所以网页
			$allweb = $this->getAllWeb($sortid);
			//获取用户已经关注和创建的网页
			$myweb = $this->getMyWeb($uid);
			$key = '1';
			$key .= empty($allweb) ? '0' : '1';
			$key .= empty($myweb) ? '0' : '1';
			//踢出
			switch($key){
				case '100':
				case '101':
					return array();
					break;
				case '110':
					$recoweb = $allweb;
					unset($allweb);
					break;
				case '111': 
					$recoweb = array_diff($allweb, $myweb);
					unset($allweb);
					unset($myweb);
					if(empty($recoweb)) return array();
					break;
			} */
			//
			$allweb = $this->getAllWeb($sortid, $uid);
			$ret = $this->webSetOrder($allweb);
			count($ret) > 300 && $ret = array_slice($ret, 0, 300);
			//redis缓存
			foreach($ret as $k => $v){
				$this->_redis->rPush(sprintf($this->recoweb_list_key,$sortid,$uid),$v['aid']);
				$this->_redis->hMset(sprintf($this->recoweb_hash_key,$sortid,$uid,$v['aid']),array('aid'=>$v['aid'],'name'=>$v['name'],'uid'=>$v['uid']));
				$this->_redis->expire(sprintf($this->recoweb_hash_key,$sortid,$uid,$v['aid']),$this->expire);
			}
			$this->_redis->expire(sprintf($this->recoweb_list_key,$sortid,$uid),$this->expire);
			$ret = array_slice($ret, $start, $size);
		} else {
			$slice_data = array_slice($recoweb_data, $start, $size);
			if(empty($slice_data)) return array();
			foreach($slice_data as $k => $v){
				$ret[] = $this->_redis->hGetAll(sprintf($this->recoweb_hash_key,$sortid,$uid,$v));
			}
		}
		$ret = json_encode($ret);
		return $ret;
	}
	//记录总条数
	public function getWebCount($sortid, $uid = 0){
		$sortid = intval($sortid);
		$uid = intval($uid);
		if($sortid < 1 || $uid < 1) return '0';
		$num = $this->_redis->lSize(sprintf($this->recoweb_list_key,$sortid,$uid));
		return $num ? $num : '0';
	}
	//删除已经关注的
	public function updateWebIndex($sortid = array(), $uid = 0, $webid){
		//$sortid = intval($sortid);
		$uid = intval($uid);
		$webid = intval($webid);
		if(empty($sortid) || $uid < 1 || $webid < 1) return '0';
		foreach($sortid as $val) {
			$this->_redis->lRem(sprintf($this->recoweb_list_key, $val, $uid), $webid, 1);
			$this->_redis->del(sprintf($this->recoweb_hash_key, $val, $uid, $webid), 0 ,0);
		}
		return '1';
	}
	//推荐网页排序
	private function webSetOrder($allweb){
		if(empty($allweb)) return array();
		$newallweb = $addr = $iddnum = $ftletter = array();
		foreach($allweb as $k => $v){
			$now_home = $v['now_addr']+$v['home_addr'];
			$myiddnum = $this->getMyIddNum($uid ,$v['iids']);
			$newallweb[$k]['name'] = $v['name'];
			$newallweb[$k]['uid'] = $v['uid'];
			$newallweb[$k]['aid'] = $v['aid'];
			$newallweb[$k]['addr'] = $now_home;
			$newallweb[$k]['iddnum'] = $myiddnum;
			$newallweb[$k]['caput_pinyin'] = $v['caput_pinyin'];
			$addr[] = $now_home;
			$iddnum[] = $myiddnum;
			$ftletter[] = $v['caput_pinyin'];
		}
		array_multisort($addr, SORT_DESC, SORT_NUMERIC, $iddnum, SORT_DESC, SORT_NUMERIC, $ftletter,  SORT_ASC, SORT_REGULAR,$newallweb);
		return $newallweb;
	}
	//网页所在的二级分类，用户关注了几个
	private function getMyIddNum($uid, $myidds){
		if(empty($myidds) || empty($uid)) return array();
		//获取用户关注和创建的所有耳机分类
		$allmywebiids = $this->getMyWebSids($uid);
		$sum = array_intersect($allmywebiids, $myidds);
		return count($sum);
	}
	/**
	 * 获取某二级分类下的所有网页， 剔除用户创建、关注的网页
	 * @param int $sortid 二级分类
	 * @param int $uid   用户id
	 * @return array(array('aid'=>'网页id', 
	                       'name' => '网页名称', 
						   'home_addr' =>网页名称中是否含有出生地, 
						   'now_addr'=> '网页名称中是否含有现居地', 
						   'iids' => '网页二级分类'
						   ))
	 */
	private function getAllWeb($sortid = 0, $uid = 0){
		if(!$sortid || !$uid) return array();
		$data = array();
		
		$birthhome = $this->getUserBirthHome($uid);//获取用户的现居地、 出生地
		extract($birthhome, EXTR_OVERWRITE);
		
		//获取用户创建和关注的网页
		$mywebs = $this->getMyWeb($uid, false);
		
		$config = $this->getInterestDbConfig();
		
		$m = D("MayKnow");
		//切换到interest数据库
		$m->switchConnect($config);
		
		//@todo 这里有可能是个性能瓶颈, 以后考虑使用存储过程
		$now_addr_sql = "0 as now_addr";
		if($now_addr){
			$now_addr_sql = "IF(LOCATE('$now_addr', b.name), 1, 0) as now_addr";
		}
		
		$home_addr_sql = "0 as home_addr";
		if($home_addr){
			$home_addr_sql = "IF(LOCATE('$home_addr', b.name), 1, 0) as home_addr";
		}
		
		$sql = "SELECT b.name, b.uid, b.caput_pinyin, a.aid, $now_addr_sql, $home_addr_sql  FROM `apps_info_category` as a  INNER JOIN `apps_info` as b 
				ON a.aid = b.aid 
				WHERE a.iid = $sortid";
		
		$data = $m->query($sql);
		//剔除用户创建和关注的网页
		//$mywebs = $this->getMyWeb($uid, false);
		foreach($data as $k => $v) {
			if(in_array($v['aid'], $mywebs)) { unset($data[$k]); continue;}
			$aid = $v['aid'];
			//@todo 暂时只能一个一个缓存了
			$iids = $this->_redis->get(sprintf($this->_webpage_sids_key, $aid));
			if(empty($iids)){
				$iids = $m->query("SELECT iid FROM `apps_info_category` WHERE aid = $aid");
				$iids = $this->arrayTwoOneByField($iids, "iid");
				$iids = implode(",", $iids);
				$this->_redis->set(sprintf($this->_webpage_sids_key, $aid), $iids);
			}
			$data[$k]['iids'] = explode(",", $iids);
		}
		//关闭数据库 
		$m->closeConnect();
		//重新连接到默认的数据库
		$m->initConnect();
		return $data;
	}
	/**
	 * 获取用户已经关注和创建的网页(已剔除重复)
	 * return array(web_id, web_id,,,,,)
	 */
	private function getMyWeb($uid = 0, $reset = true){
		static $data = array();
		if(count($data) && !$reset) return $data;
		$create_web_ids = array();
		$following_web_ids = array();
		//获取兴趣db配置
		$config = $this->getInterestDbConfig();
		$m = D("MayKnow");
		//切换到interest数据库
		$m->switchConnect($config);
		//用户创建网页id
		$sql = "SELECT aid FROM `apps_info` WHERE uid = '".$uid."'";
		$create_web_ids = $m->query($sql);
		!empty($create_web_ids) && $create_web_ids = $this->arrayTwoOneByField($create_web_ids, "aid");
		//关闭数据库 
		$m->closeConnect();

		//用户关注的网页id
		$this->_redis->zUnion(sprintf($this->_tmp_zset_key, $uid), array(sprintf($this->_webpage_following_key, $uid), sprintf($this->_webpage_following_hidden_key, $uid)));
		$following_web_ids = $this->_redis->zRange(sprintf($this->_tmp_zset_key, $uid), 0, -1);
		$this->_redis->expire(sprintf($this->_tmp_zset_key, $uid), 60); //设置失效时间60s
		$following_web_ids = $following_web_ids ? $following_web_ids : array();

		//重新连接到默认的数据库
		$m->initConnect();

		$data = array_unique(array_merge($create_web_ids, $following_web_ids));

		return $data;
	}
   /**
	 * 获取用户已经关注和创建的网页的二级分类(已剔除重复)
	 * return array(web_id, web_id,,,,,)
	 */
	private function getMyWebSids($uid = 0){
		$sids = array();
		
		$mywebids = $this->getMyWeb($uid, false);//获取web_id
		
		if(empty($mywebids)) return array();
		
		$config = $this->getInterestDbConfig();
		$m = D("MayKnow");
		//切换到interest数据库
		$m->switchConnect($config);
		$map = array();
		$map['aid'] = array('in', $mywebids);
		$field = "iid";
		$sids = $m->table("apps_info_category")->field($field)->where($map)->findAll();
		//关闭数据库 
		$m->closeConnect();
		//重新连接到默认的数据库
		$m->initConnect();
		$sids = $this->arrayTwoOneByField($sids,'iid');
		return array_unique($sids);
	}

	/**
	 * 获取用户的出生地、居住地  （市一级)
	 * @param int $uid 用户id
	 * @return 返回 array("now_addr" => "现居地", "home_addr" => "出生地");
	 */
	private function getUserBirthHome($uid = 0) {
		$user_info = M("user_info");
		$map = array();
		$map['uid'] = $uid;
		$field = "now_addr, home_addr";
		$init_data = array("now_addr" => "", "home_addr" => "");
		$data = (array)$user_info->where($map)->field($field)->find();
		$special_city = array("北京", "天津", "上海", "重庆", "香港", "澳门");
		//现居地
		if(isset($data['now_addr']) && $data['now_addr']) {
			$now_addr_arr = explode(" ", $data['now_addr']);
			$data['now_addr'] = in_array($now_addr_arr[1], $special_city) ? $now_addr_arr[1] : $now_addr_arr[2]; 
		}
		//出生地
		if(isset($data['home_addr']) && $data['home_addr']) {
			$home_addr_arr = explode(" ", $data['home_addr']);
			$data['home_addr'] = in_array($home_addr_arr[1], $special_city) ? $home_addr_arr[1] : $home_addr_arr[2]; 
		}
		return array_merge($init_data, $data);
	}
}