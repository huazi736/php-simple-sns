<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//require_cache(APPPATH . 'core' . DS . 'MY_RedisModel' . EXT);

/**
 * 获取首页时间线，信息流数据模型
 * 
 * @author 应晓斌
 *
 */
class TimelineModel extends DK_Model
{
	
	private $timeaxisYearMonthTopicsKey       = 'timeaxis:%d:%d%d';
	private $timeaxisYearHotsKey              = 'timeaxis:%d:%d:hot';
	private $timelineAvailableYearsKey        = 'timeline:%d:years';
	private $timelineAvailableMonthsInYearKey = 'timeline:%d:%d';
	private $topicKey                         = 'topic:%d';
	private $maxTopicsPerPage                 = 10;
	private $_redis                           = null;
	private $uid                              = 0;
	private $getUserFollowing                 = 'following:%d';
	private $getUserFriends                   = 'friend:%d'; 
	private $auid                             = null;
	private $map                              = 'map:social:%d';
	
	public function __construct()
	{
		parent::__construct();
		$this->init_redis();
		$this->_redis = $this->redis;
	}
	
	/**
	 * 从一个特定年份的某月开始取数据，一次返回20条记录
	 * 
	 * @param int $uid
	 * @param int $year
	 * @param int $month
	 * @param int $viewerId
	 * @param int $startScore
	 * @param int $birthday
	 * @param int $lastTopicId
	 */
	public function getFragmentFeeds($uid, $year, $month, $viewerId, $startScore = 0, $endScore = 0, $lastTopicId = 0)
	{
        $this->uid  = $viewerId;
        $this->auid = $uid;
		$relation = $this->getRelation($uid, $viewerId);

		if ($startScore == 0) {
			$startScore = '+inf';
		}
		
		if ($endScore == 0) {
			$endScore = '-inf';
		}
		if($year == date('Y') && $month==date('n')) {
			$currentTopicMsg = array_merge(
					           $this->getTopic($this->redis->HGET(sprintf($this->map,$uid),date("Ym"))),
					           $this->getCurrentMonthTop($uid)
					           );
		}
		switch ($relation) {
			case 10:
				// 好友				
                // modifiy zhoutianliang
                $feed = $this->getTopicsIndirect('friend', $uid, $viewerId, $year, $month, $startScore, $endScore, $lastTopicId);
                if(isset($currentTopicMsg)) {
                	$feed['current_month_top'] = $currentTopicMsg;
                }
				if(!empty($feed) && is_array($feed)) {
				    return $this->filterFriendsTopic($feed, $uid, $viewerId);
				}
			    return $feed;
			case 6:
				// 互相关注
			case 4:
				// 粉丝
				$feed = $this->getTopics('follower', $uid, $year, $month, $startScore, $endScore, $lastTopicId);
				if(isset($currentTopicMsg)) {
				    $feed['current_month_top'] = $currentTopicMsg;
				}
				return $feed;
			case 5:
				// 当前用户自己（不需要缓存？）
				$feed = $this->getTopicsSelf($uid, $year, $month, $startScore, $endScore, $lastTopicId);
				if(isset($currentTopicMsg)) {
					$feed['current_month_top'] = $currentTopicMsg;
				}
				return $feed;
			default:
				// 公开
				$feed = $this->getTopics('public', $uid, $year, $month, $startScore, $endScore, $lastTopicId);

				if(isset($currentTopicMsg)) {
					$feed['current_month_top'] = $currentTopicMsg;
				}
				return $feed;
		}
		
	}
	
	/*
	/**
	 * 从Redis中获取用户的年热点动态并返回该年有记录的月份
	 * 
	 * @param int $uid
	 * @param int $relation
	 * @param int $year
	 */
	/*
	public function getYearHottestFeeds($uid, $year, $viewerId, $endScore = '-inf')
	{
		$relation = $this->getRelation($uid, $viewerId);
		switch ($relation) {
			case 10:
				// 好友
				if (!$this->_redis->exists("timeaxis:$uid:friendtotal:$year:hot")) {
					$this->_redis->zUnion("timeaxis:$uid:friendtotal:$year:hot", array("timeaxis:$uid:friend:$year:hot", "timeaxis:$uid:custom:$year:hot"));
					$this->_redis->setTimeout("timeaxis:$uid:friendtotal:$year:hot", 60);
				}
				$rawYearHots = $this->_redis->zRevRangeByScore("timeaxis:$uid:friendtotal:$year:hot", '+inf', $endScore);
				break;
			case 6:
				// 互相关注
			case 4:
				// 粉丝
				$rawYearHots = $this->_redis->zRevRangeByScore("timeaxis:$uid:follower:$year:hot", '+inf', $endScore);
				break;
			case 5:
				// 当前用户自己（不需要缓存？）
				$this->_redis->zUnion("timeaxis:$uid:selftotal:$year:hot", array("timeaxis:$uid:friend:$year:hot", "timeaxis:$uid:custom:$year:hot", "timeaxis:$uid:self:$year:hot"));
				
				$rawYearHots = $this->_redis->zRevRangeByScore("timeaxis:$uid:selftotal:$year:hot", '+inf', $endScore);
				$this->_redis->delete("timeaxis:$uid:selftotal:$year:hot");
				break;
			default:
				// 公开
				$rawYearHots = $this->_redis->zRevRangeByScore('timeaxis:' . $uid . ':public:' . $year . ':hot', '+inf', $endScore);
				break;
		}
		$yearHots = array();
		if (!empty($rawYearHots)) {
			
			// 判断是否是当前年份，如果是当前年份的话需要过滤掉当前月份与上一月份的热点信息
			$filter = false;
			if (date('Y') == $year) {
				$filter = true;
				$currentMonth = date('n');
			}
			
						
			foreach ($rawYearHots as $topicId) {
				$topic = $this->getTopic($topicId);
				
				if (!empty($topic)) {
					if ($filter) {
						// 如果是当前月份的，或者上一月份的数据，直接跳过
						if ($currentMonth == date('n', $topic['ctime']) || $currentMonth - 1 == date('n', $topic['ctime'])) {
							continue;
						}
					}
				
					$topic['friendly_time'] = friendlyDate($topic['ctime']);
					if (isset($topic['type'])) {
						if ($topic['type'] == 'album' || $topic['type'] == 'ask'
								|| $topic['type'] == 'event' || $topic['type'] == 'forward') {
							$topic = $this->prepareTopic($topic);
						}
					}
					$yearHots[] = $topic;
				}
			}
			
			// 如果是好友或者互相关注过滤记录
			if ($relation == 1 || $relation == 2) {
				if (!empty($yearHots)) {
					$yearHots = array_filter($yearHots, function ($topic) use ($viewerId) {
						if (!isset($topic['relations'])) {
							return true;
						}
						// 该属性记录对外的权限
						$permission = json_decode($topic['relations'], true);
						return in_array($viewerId, $permission);
					
					});
				}
			}
			// 根据年份获取该年的可用月份
			$avaliableMonthsInCurrentYear = $this->_redis->hKeys(sprintf($this->timelineAvailableMonthsInYearKey, $uid, $year));
			rsort($avaliableMonthsInCurrentYear, SORT_NUMERIC);
			
			// 从生日的时候中获取对应的月份，然后删除之前的月份
			if ($endScore != '-inf') {
				$birthMonth = date('n', $endScore);
				$avaliableMonthsInCurrentYear = array_filter($avaliableMonthsInCurrentYear, function ($month)  use ($birthMonth) {
					return $month >= $birthMonth;
				});
			}
			
			return array('hots' => $yearHots, 'months' => $avaliableMonthsInCurrentYear, 'status' => 1);
		} else {
			// 如果没有记录则直接返回信息
			return array('status' => 0, 'msg' => '没有该年的热点动态');
		}
	}
	*/
	/**
	 * 从Redis中获取用户的年份第一月信息并返回该年有记录的月份
	 *
	 * @param int $uid
	 * @param int $relation
	 * @param int $year
	 */
	public function getYearHottestFeeds($uid, $year, $viewerId, $endScore = '-inf')
	{
		//$relation = $this->getRelation($uid, $viewerId);
		
		// 根据年份获取该年的可用月份
		$avaliableMonthsInCurrentYear = array();
		$avaliableMonthsInCurrentYear = $this->_redis->hKeys(sprintf($this->timelineAvailableMonthsInYearKey, $uid, $year));
		rsort($avaliableMonthsInCurrentYear, SORT_NUMERIC);
		
		// 从生日的时候中获取对应的月份，然后删除之前的月份
		if ($endScore != '-inf') {
			$birthMonth = date('n', $endScore);
			$avaliableMonthsInCurrentYear = array_filter($avaliableMonthsInCurrentYear, function ($month)  use ($birthMonth) {
				return $month >= $birthMonth;
			});
		}

		$nowYear  = date('Y'); //现在年
		if(trim($year)==$nowYear) {
			$nowMonth = date('n'); //现在月
			if(isset($avaliableMonthsInCurrentYear[0])) {
				if($avaliableMonthsInCurrentYear[0]==$nowMonth) {
					array_shift($avaliableMonthsInCurrentYear);
				}
				if($avaliableMonthsInCurrentYear[0]==(((int)$nowMonth) - 1)) {
					array_shift($avaliableMonthsInCurrentYear);
				} 
			}
		}
		if(isset($avaliableMonthsInCurrentYear[0])) {
		    //获取这一年最大的月份数据
		    //不需要 数据 直接返回月份
		    /*
			$rawYearFristMonthData = $this->getFragmentFeeds($uid,$year,$avaliableMonthsInCurrentYear[0],$viewerId);
		    
		    if(empty($rawYearFristMonthData) || !is_array($rawYearFristMonthData) || !isset($rawYearFristMonthData['topics'])) {
		    	return array('status' => 0, 'msg' => '没有该年月的可用信息');
		    }else {
		    	
		    	$rawYearFristMonthData = $this->filterFriendsTopic($rawYearFristMonthData, $uid, $viewerId);
		    }
		    */

	
			return array(/* 'hots' => $rawYearFristMonthData,  */ 'months' => $avaliableMonthsInCurrentYear, 'status' => 1);
		} else {
			// 如果没有记录则直接返回信息
			return array('status' => 0, 'msg' => '没有该年的可用信息');
		}
	}
	
	
	
	/**
	 * 过滤用户自己设定权限的记录
	 * 
	 * @param array $topic
	 * @param int $perm  访问用户所具有的权限，如果是自定义权限，使用该访问人的用户ID
	 */
	private function filterSpecialTopics($topic, $uid)
	{
		if (!isset($topic['relations'])) {
			return true;
		}
		
		// 该属性记录对那些好友可见
		$permission = json_decode($topic['relations'], true);
		//$permission = explode(',', $permission);
		return in_array($uid, $permission);
	}
		
	/**
	 * 获取用户时间线上需要显示的年份
	 * 
	 * @param int $uid
	 */
	public function getTimelineYears($uid, $viewerId)
	{
		//logging(microtime(true));
		$timeline = $this->_redis->hKeys(sprintf($this->timelineAvailableYearsKey, $uid));
		$nearestYear = null;
		if (!empty($timeline)) {
			rsort($timeline);
			$nearestYear = $timeline[0];
						
			// 获取该用户的出生年份
			$birthday = $this->getBirthYear($uid);
			if ($birthday) {
				$birthYear = date('Y', $birthday);
				// 如果用户已经录入出生日期，则替换相应的日期，如果有在出生年月已经的数据，则进行隐藏
				$timeline = array_filter($timeline, function($year) use ($birthYear) {
					return $year > $birthYear;
				});
				
				// 加入birthday用来获取该年信息的时候过滤出生之前的记录
				array_push($timeline, array('date' => $birthYear, 'title' => '出生', 'birthday' => $birthday));
			}
		}
		
		// 如果今年没有数据，或者最近的年份与当前年份不一致，则只需要插入当前月份即可
		if (date('Y') != $nearestYear || null === $nearestYear) {
			array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'));
		} else {
			// 获取当前这一年中可用的月份
			$nearestMonths = $this->_redis->hKeys(sprintf($this->timelineAvailableMonthsInYearKey, $uid, $nearestYear));

			$months = array('一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月');
			if (!empty($nearestMonths)) {
				// 如果这一年中有几个月份已经有数据了，对已有月份进行排序
				rsort($nearestMonths);
				
				$availableMonthsCount = count($nearestMonths);
				
				// 时间线上存的第一个月份不是当前的月份
				if ($nearestMonths[0] != date('m')) {
					// 如果第一个月份不是当前这个月份的前一个月
					if ($nearestMonths[0] != date('m') - 1) {
						array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'));
					} else {
						// 如果第一个月份是这个月的前一个月份
						
						// 还有除了前一月份的数据
						if (isset($nearestMonths[1])) {
							array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'), array('date' => $nearestYear . '/' . $nearestMonths[0], 'title' => $months[$nearestMonths[0] - 1]));
						} else {
							// 没有另外月份的数据了，在时间线上去除掉当前年份
							array_shift($timeline);
							array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'), array('date' => $nearestYear . '/' . $nearestMonths[0], 'title' => $months[$nearestMonths[0] - 1]));
						}
					}
					
				} else {
					// 时间线上存的第一个月份是当前的月份

					// 是否还有另外月份的数据
					if (isset($nearestMonths[1])) {
						
						// 如果第二个月份不是当前月份的前一个月份
						if ($nearestMonths[1] != date('m') - 1) {
							array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'));
						} else {
							// 如果第二个月份是当前月份的前一个月份

							// 还有除了前一月份的数据
							if (isset($nearestMonths[2])) {
								array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'), array('date' => $nearestYear . '/' . $nearestMonths[1], 'title' => $months[$nearestMonths[1] - 1]));
							} else {
								// 没有另外月份的数据了，在时间线上去除掉当前年份
								array_shift($timeline);
								array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'), array('date' => $nearestYear . '/' . $nearestMonths[1], 'title' => $months[$nearestMonths[1] - 1]));
							}
						}
						
					} else {
						// 没有的话直接去掉这个一月份，插入当前月份
						array_shift($timeline);
						array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'));
					}
										
				}
			} else {
				// 如果这一年没有可用月份，则插入当前的时间标签  （~~~~~~~~~~~~）
				array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'));
			}
		}
		return $timeline;
	}

	/**
	 * 获取某个特定topic的信息
	 *
	 * @param int $topicId
	 */
	public function getTopic($topicId)
	{
		return $this->_redis->hGetAll(sprintf($this->topicKey, $topicId));
	}
	
	/**
	 * 获取某个特定topic的信息
	 *
	 * @param stirng $topicId
	 */
	public function getTopicByKey($topicKey)
	{
		return $this->_redis->hGetAll($topicKey);
	}
	
	/**
	 * 获取访问该页面的用户与被访问者的关系
	 *
	 * @param int $uid  被访问的用户ID
	 */
	private function getRelation($uid, $viewerId)
	{	
		if ($viewerId == $uid) {
			// 当前用户自己
			return 5;
		} else {
			// 判断用户间的关系
			//return call_soap('social', 'Social', 'getRelationStatus', array($uid, $viewerId));
		      return service('Relation')->getRelationStatus($uid,$viewerId);
		}
	}
	
	/**
	 * 获取访问该页面的用户与被访问者的共同好友
	 * @param int $uid 被 访问者UID
	 * @param int $authorid 作者UID 
	 */
	private function getCommonFriends ($uid,$authorid) {
		$uid = intval($uid);
		$authorid = intval($authorid);
		if($uid==$authorid) {
			return 5;
		}else {
			//return call_soap('social','Social','getCommonFriends',array($uid,$authorid));
			  return service('Relation')->getCommonFriends($uid,$authorid);
		}
	}
	/**
	 * 获取访问该页面的用户的好友
	 * @param int $uid 被 访问者UID
	 * @param int $is_self 作者UID
	 */
	private function getFriends ($uid,$viewsId,$limit=1,$offset=8) {

		if(!$uid || !$viewsId) {
			return false;
		}else {
			if($uid==$viewsId){
				//$user =  call_soap('social','Social','getFriendsWithInfo',array($uid,true,1,8));
				  $user = service('Relation')->getFriendsWithInfo($uid,true,$limit,$offset); 
				//$num  =  call_soap('social','Social','getNumOfFriends',array($uid,true));
				  $num  = service('Relation')->getNumOfFriends($uid,true);
			}else {
				  $user = service('Relation')->getFriendsWithInfo($uid,false,$limit,$offset); 
				  $num  = service('Relation')->getNumOfFriends($uid,true);
			}
			
		}
		if(empty($user)) {
			return array('friends_num'=>$num,'friends'=>array());
		}
		$arr=array('friends_num'=>$num);
		foreach ($user as $key=>$one) {
			
			$headpic = get_avatar($one['id'],'m');
			$one['headpic'] = $headpic;
			$arr['friends'][$key]= $one;   
		}
		return $arr;
	}
	/**
	 * 过滤只有共同好友的人才能看到信息 
	 * @param array $topic
	 * @param int $uid
	 * @param int $viewerId 
	 */
	private  function filterFriendsTopic($topic,$uid,$viewerId) {
		
		$rawYearFristMonthData = $topic;
        if($uid!=$viewerId){
		foreach($rawYearFristMonthData['topics'] as $key=>$one) {
            if(isset($one['type']) && $one['type'] == 'forward' && isset($one['forward'])) {
					if($one['uid'] && $one['forward']['uid'] && $one['permission']==4) {
						if($viewerId!=$one['forward']['uid']) {
							if($one['uid']!=$one['forward']['uid']) {
								$commonfriends = $this->getCommonFriends($one['uid'], $one['forward']['uid']);
								if(empty($commonfriends)) {
									unset($rawYearFristMonthData['topics'][$key]);
								}elseif(!in_array($uid, $commonfriends)) {
									unset($rawYearFristMonthData['topics'][$key]);
								}
							}
						}
					}
				}
			}
		}
		
		return $rawYearFristMonthData;
	}
	
	/**
	 * 粉丝、公开
	 */
	private function getTopics($relation, $uid, $year, $month, $startScore, $endScore, $lastTopicId = 0)
	{
		return $this->getTopicsByKeyScore("timeaxis:$uid:$relation:$year$month", $startScore, $endScore, $lastTopicId);
	}
	
	/**
	 * 好友
	 * 
	 * @param string $relation  
	 */
	private function getTopicsIndirect($relation, $uid, $viewerId, $year, $month, $startScore, $endScore, $lastTopicId) {
		if (!$this->_redis->exists("timeaxis:$uid:$relation" . 'total:' . $year . $month)) {
			$this->_redis->zUnion("timeaxis:$uid:$relation". 'total:' . $year . $month, array("timeaxis:$uid:$relation:$year" .$month, "timeaxis:$uid:custom:$year" . $month));
			$this->_redis->setTimeout("timeaxis:$uid:$relation" . "total:$year" . $month, 60);
		}
		
		return $this->getTopicsByKeyScore("timeaxis:$uid:$relation" . "total:$year" . $month, $startScore, $endScore, $lastTopicId, $viewerId);
	}
	
	/**
	 * 获取自己的topics
	 * 
	 * @param int $uid
	 * @param int $year
	 * @param int $month
	 * @param int $startScore
	 * @param int $lastTopicId
	 */
	private function getTopicsSelf($uid, $year, $month, $startScore, $endScore, $lastTopicId) {
		$combinedKey = "timeaxis:$uid:selftotal:$year" . $month;
		$this->_redis->zUnion($combinedKey, array("timeaxis:$uid:friend:$year" . $month, "timeaxis:$uid:custom:$year" . $month, "timeaxis:$uid:self:$year" . $month));
		
		return $this->getTopicsByKeyScore($combinedKey, $startScore, $endScore, $lastTopicId, false, true);
	}
	
	/**
	 * 通过key，score来获取Topics
	 * 
	 * @param string $key
	 * @param int $uid
	 * @param int $year
	 * @param int $month
	 * @param int $startScore  上一次返回数据的最后一条记录的score
	 * @param int $lastTopicId 上一次返回数据的最后一个ID
	 * @param int|boolean $filter 浏览时间线用户的ID
	 * @param boolean $clear  需要删除key所对应的数据（个人的话需要清理这些数据，以确保能读取最新的数据）
	 */
	private function getTopicsByKeyScore($key, $startScore, $endScore, $lastTopicId, $filter = false, $clear = false) {
		$topicIds = $this->_redis->zRevRangeByScore($key, $startScore, $endScore);
		
		// 需要处理一下数据：把lastTopicId之前(包括lastTopicId）的id都去除掉，防止多个topic有相同的score（需要吗？）
		if ($lastTopicId) {
			if (($lastTopicPosition = array_search($lastTopicId, $topicIds)) !== false) {
				array_splice($topicIds, 0, $lastTopicPosition + 1);
			}
		}
		
		$topics = array();
		$isEnd = false;
		$currentLastTopicId = 0;
		
		if (count($topicIds)) {
			$num = 0;
		
			$filterTopicIds = array(); // 当初是用来干嘛的?
			foreach ($topicIds as $topicId) {
				if ($num < $this->maxTopicsPerPage) {
					if ($filter) {
						$topic = $this->getTopic($topicId);
						if ($this->filterSpecialTopics($topic, $filter)) {
							$filterTopicIds[] = $topicId;
							$topics[] = $topic;
							$num++;
						}
					} else {
						$topics[] = $this->getTopic($topicId);
						$num++;
					}
				} else {
					break;
				}
			}
			
			// 获取当前获取数据的最后一条记录，比较它的ID是不是跟上一次返回的最后一条记录的ID相同
			$lastTopicId = end($topicIds);
			$currentLastTopic = end($topics);
			$currentLastTopicId = $currentLastTopic['tid'];
			if ($lastTopicId && $lastTopicId == $currentLastTopicId) {
				$isEnd = true;
			}
			// 作为下次取数据的开始时间
			$startScore = $currentLastTopic['ctime'];
		
			foreach ($topics as &$topic) {
				if (!empty($topic)) {
					$topic['friendly_time'] = friendlyDate($topic['ctime']);
		
					if (isset($topic['type'])) {
						if (in_array($topic['type'],$this->infoType())) {
							$topic = $this->prepareTopic($topic);
						}
					}
				}
			}
		
			$fetchedTopicNums = count($topics);
			if ($fetchedTopicNums) {
				$status = 1;
			} else {
				$status = 0;
			}
		
			if ($fetchedTopicNums < $this->maxTopicsPerPage) {
				$isEnd = true;
			}
		} else {
			$isEnd = true;
			$status = 0;
		}
		if ($clear) {
			$this->_redis->delete($key);
		}
		return array('topics' => $topics, 'isEnd' => $isEnd, 'startScore' => $startScore, 'lastTopicId' => $currentLastTopicId, 'status' => $status);
	}
	
	/**
	 * 通过IDs获取数据信息（只限于直接读取并不需要权限处理的情况即公开、粉丝、用户自己）
	 * 
	 * @param array $topicIds
	 */
	private function getTopicsByIds($topicIds, $lastTopicId)
	{
		$isEnd = false;
		$topics = array();
		$startScore = 0;
		
		if (!empty($topicIds)) {
		
		
			$totalCount = count($topicIds);
			$currentLastTopicId = $topicIds[$totalCount - 1];
		
			if ($lastTopicId == $currentLastTopicId) {
				$isEnd = true;
			}
		
		
			// 如果是公开的，粉丝，本人的话直接返回20条记录
			foreach ($topicIds as $topicId) {
				$topic = $this->getTopic($topicId);
		
				if (!empty($topic)) {
					// 保存开始的时间点
					if ($topicId == $currentLastTopicId) {
						$startScore = $topic['ctime'];
					}
					
					$topic['friendly_time'] = friendlyDate($topic['ctime']);
					
					if (isset($topic['type'])) {
						if (in_array($topic['type'], $this->infoType())) {
							$topic = $this->prepareTopic($topic);
						}
					}
					
					$topics[] = $topic;
				}
			}		
		
			if (count($topics) == 0) {
				$status = 0;
			} else {
				$status = 1;
			}
		
		} else {
			$isEnd = true;
			$status = 0;
		}
		
		
		return array('topics' => $topics, 'isEnd' => $isEnd, 'startScore' => $startScore, 'lastTopicId' => $lastTopicId, 'status' => $status);
	}
	
	/**
	 * 获取用户的生日  (如果只是浏览自己的时间线数据，则前面已经取到了生日信息，不需要这一步，session中获取？)
	 * 
	 * @param int $uid
	 */
	private function getBirthYear($uid)
	{
		//$user = call_soap('ucenter', 'User', 'getUserInfo', array($uid, 'uid', array('birthday')));
		$user = service('User')->getUserInfo($uid,'uid',array('birthday'));
		return $user['birthday'];
	}
	
	/**
	 * 处理格式化数据
	 * 
	 * @param array $topic
	 */
	private function prepareTopic($topic)
	{
		switch ($topic['type']) {
			case 'album':
                return $this->switchAlbum($topic);
            case 'photo':
                return $this->switchPhoto($topic);
			case 'ask':
                return $this->switchAsk($topic);
			case 'event':
                return $this->switchEvent($topic);
			case 'forward':
                return $this->switchForward($topic);
			case 'social':
				return $this->switchSocial($topic);
			case 'change':
                return $this->switchChange($topic);
			case 'answer':
                return $this->switchAnswer($topic);
			case 'join':
                return $this->switchJoin($topic);
			default:
				return $topic;
		}
	}
	/**
	 * 转发的信息
	 * 
	 * @param array $topic
	 */
	private function switchForward($topic) {
		$topic['forward'] = $this->getTopic($topic['fid']);
		if ($topic['forward'] && $topic['forward']['type'] == 'album') {
			$topic['forward']['picurl'] = json_decode($topic['forward']['picurl']);
		}
		if($topic['forward'] && $topic['forward']['type']=='photo') {
			$topic['forward']['picurl'] = json_decode($topic['forward']['picurl']);
		}
		return $topic;
	}
	/**
	 * 活动
	 * 
	 * @param array $topic
	 */
	private function switchEvent($topic) {
		$topic['starttime'] = date('Y-n-j H:i',$topic['starttime']);
		return $topic;
	}
	/**
	 * 问答
	 * 
	 * @param array $topic
	 */
	private function switchAsk($topic) {
		$result = service('Ask')->timelineAskData($topic['fid'],$this->uid);
		$topic['ask'] = $result;
		return $topic;
	}
	/**
	 * 
	 * 相册
	 * @param array $topic
	 */
	private function switchAlbum($topic) {
		$topic['picurl'] = json_decode($topic['picurl'], true);
		return $topic;
	}
	/**
	 * 
	 * 照片
	 * @param array $topic
	 */
	private function switchPhoto($topic) {
		$topic['picurl'] = json_decode($topic['picurl'], true);
		return $topic;
	}
	//对 关注 加好友 关注网页 处理
	private function switchSocial($topic) {
		if(date("Ym",$topic['ctime'])==date("Ym")) return array(); //过滤 social的日期等于当前的年月 去掉不要
        $info  = $this->getCurrentMonthTop($this->auid,$topic['ctime']);
        $topic = array_merge($topic,$info);
		return $topic;
	}
	//对问答处理
	private function switchAnswer($topic) {
		if($topic['questions']) {
			$question = json_decode($topic['questions'],true);
			$topic['question_num'] = count($question);
			$question = $this->arraySort($question, 'time');

			$tmparr = array();
			$question = array_slice($question,0,3,true);
			foreach($question as $key=>$one) {

				$title              = strip_tags($one['title']);
				$one['title_full']  = $title;
				$one['title']       = mb_strlen($title) > 28 ? mb_substr($title,0,28) . '...': $title;
				$one['answers']     = array_slice(array_map(
					function($val){return mb_strlen(strip_tags($val)) > 28 ? mb_substr(strip_tags($val), 0,28) : strip_tags($val);},
					json_decode($one['answers'],true)
				),0,3,true);
				
				array_push($tmparr, $one);
			}
			$topic['questions'] = $tmparr;
		}
		$topic['start_time'] = date('m月d日',$topic['dateline']);
		$topic['end_time']   = date('m月d日',$topic['ctime']);
		return $topic;
	}
	//对活动内容处理
	private function switchJoin($topic) {
		if($topic['events']) {
			$events = json_decode($topic['events'],true);
			$topic['events_num'] = count($events);
			$events = $this->arraySort($events, 'time');

			$tmparr = array();
			$events = array_slice($events,0,3,true);
			foreach($events as $key=>$one) { 
				$one['join_time'] = date('Y年m月d日',intval($one['time']));
				array_push($tmparr, $one);
			}
			$topic['events'] = $tmparr;
		}
		return $topic;
	}
	//对头像更改内容处理
	private function switchChange($topic) {
		if($topic['filename'] && $topic['groupname'] && $topic['imgtype']) {
			$topic['imgurl'] = $topic['groupname'] .'/'.$topic['filename'].'.'.$topic['imgtype'];
			unset($topic['filename']);
			unset($topic['groupname']);
			unset($topic['imgtype']);
		}
		return $topic;
	}
	/**
	 * 需要转换的类型
	 * 
	 * @return Array
	 */
	private function infoType() {
		return array('album','ask','event',
				     'forward','social','change',
				     'answer','join','photo'
				    );
	}
	
	/*
	 *分段获取 关注网页 关注 问答 活动 的更多数据; 
	 * 
	 */
	public  function getMoreFeeds($type,$id,$limit,$uid='') {
		$feeds=array();
        
		$feeds = $this->getTopic($id);
		if(!$feeds) return array('msg'=>'数据不存在','status'=>0);
 
		if($type=='social_follows'|| $type=='social_friends'  || $type=='answer' || $type=='join') {
			return $this->getMoreTypeFeeds($type, $feeds, $limit,$id,$uid);
		}else {
			return array('msg'=>'传递参数错误','status'=>0);
		}
		
	}
	private  function getMoreTypeFeeds($type,$feeds='',$limit,$id='',$uid='') {
		switch($type) {
			case 'social_follows':
                $getInfo = $this->getUserFollowingOrFriendsInfo($this->getUserFollowing, $uid, $feeds['ctime'],$limit+1);
				return array('data'=>$getInfo[1],'isEnd'=>$getInfo[0][2],'status'=>1);
				break;
			case 'social_friends':
                $getInfo = $this->getUserFollowingOrFriendsInfo($this->getUserFriends, $uid, $feeds['ctime'],$limit+1);
			    return array('data'=>$getInfo[1],'isEnd'=>$getInfo[0][2],'status'=>1);
			    break;
			case 'answer':
				$question = json_decode($feeds['questions'],true);
				$tmparr = array();
				$count = count($question);
				$question = $this->arraySort($question, 'time');
				$question = $this->getLengthArray($question, (($limit*$this->maxTopicsPerPage) - $this->maxTopicsPerPage),$this->maxTopicsPerPage);
				foreach($question as $key=>$one) {
					
					$title              = strip_tags($one['title']);
					$one['title_full']  = $title;
					$one['title']       = mb_strlen($title) > 28 ? mb_substr($title,0,28) . '...': $title;
					$one['answers']     = array_slice(array_map(
							function($val){
						return mb_strlen(strip_tags($val)) > 28 ? mb_substr(strip_tags($val), 0,28) : strip_tags($val);
					},
					json_decode($one['answers'],true)
					),0,3,true);
					array_push($tmparr, $one);
				}
				$is_end = ($limit*$this->maxTopicsPerPage) < $count  ? false :true;
				return array('data'=>$tmparr,'isEnd'=>$is_end,'status'=>1);
				break;
			case 'join':
				$events = json_decode($feeds['events'],true);
				$tmparr = array();
				$count = count($events);
				$events = $this->arraySort($events, 'time');
				$events = $this->getLengthArray($events, (($limit*$this->maxTopicsPerPage) - $this->maxTopicsPerPage),$this->maxTopicsPerPage);
				foreach($events as $key=>$one) {
					$one['join_time'] = date('Y年m月d日',$one['time']);
					array_push($tmparr, $one);
				}
				$is_end = ($limit*$this->maxTopicsPerPage) < $count  ? false :true;
				return array('data'=>$tmparr,'isEnd'=>$is_end,'status'=>1);
				break;
			default:
				return array('data'=>0,'isEnd'=>true,'status'=>0);
				break;
		}
	}
	/**
	 * 取得关注
	 * 先计算月头  月尾 取得相应的unix时间戳
	 * @param int $starttime
	 * @param int $endtime
	 * @return array
	 */
	private function getUserFollowingOrFriends($type,$uid,$starttime=null,$limit=1) {
		if(!$starttime){
			$starttime = mktime(0,0,0,date('n'),1,date('Y'));
			$endtime   = mktime(23,59,59,date('n'),date('t'),date('Y'));
		}else {
			$starttime = mktime(0,0,0,date('n',$starttime),1,date('Y'));
			$endtime   = mktime(23,59,59,date('n',$starttime),date('t',$starttime),date('Y'));
		}
		$getCount = $this->redis->ZCOUNT(sprintf($type,$uid),$starttime,$endtime);
		$getFollowingOrFriends = array();
		if($getCount) {
			//$endtime 在前面 $starttime在后面
            if($limit==1) {
            	$offset = 0;
            	$size   = 6; 
            }else {
            	//从第二页开始取10条
            	$offset = ($this->maxTopicsPerPage * $limit) - $this->maxTopicsPerPage - 5;
            	$size   = $this->maxTopicsPerPage ;
            }
			$getFollowingOrFriends = $this->redis->zRevRangeByScore(sprintf($type,$uid),$endtime,$starttime,array('limit'=>array($offset,$size)));
		}
		$is_end = ($limit * $this->maxTopicsPerPage) - 5 < $getCount ? false : true;
		return array($getCount,$getFollowingOrFriends,$is_end);
	}	
	//对数组取指定长度
	private  function getLengthArray($array,$offset,$length) {
		return array_slice($array, $offset,$length,true);
	}
	/**
	 * 获取朋友或者关注的信息
	 * @param string $type
	 * @param int $uid
	 * @param int $time
	 * @param int $limit
	 * @return array
	 */
	private function getUserFollowingOrFriendsInfo($type,$uid,$time,$limit=1) {
		$getInfo                 = $this->getUserFollowingOrFriends($type, $uid,$time,$limit);
		$getUserInfo             = array();
		$getUserFollowFriendInfo = array();
		if($getInfo[1]) {
			$getUserFollowFriendInfo  = $this->getUserInfo($getInfo[1],false);
		}
	    return array($getInfo,$getUserFollowFriendInfo);
	}
	
	/**
	 * 获取置顶信息
	 * 
	 * @param int $uid
	 */
	private function getCurrentMonthTop($uid,$time=null,$limit=1) {
        $getFollowing       = $this->getUserFollowingOrFriendsInfo($this->getUserFollowing,$uid,$time,$limit);
        $getFriends         = $this->getUserFollowingOrFriendsInfo($this->getUserFriends,$uid,$time,$limit);
        $getUserInfo             = $this->getUserInfo($uid,true,array('username','dkcode'));
        return array('follows'=>$getFollowing[1],'follows_num'=>$getFollowing[0][0],
        		     'friends'=>$getFriends[1],'friends_num'=>$getFriends[0][0],
        		     'uname'=>$getUserInfo['username'],'dkcode'=>$getUserInfo['dkcode']
        		     );
	}
	/**
	 * 获取用户信息
	 * 批量 或 单 个
	 * @param mixed $userid
	 * @param boolean $is_single
	 * @param array $array_type
	 * @return array
	 */
	private function getUserInfo($uid,$is_single=true,$array_type=array()) {
		if($is_single) {
			//获取单个
			return service('User')->getUserInfo($uid,'uid',$array_type);
		}else {
			//批量获取
			$userInfo = service('User')->getShortInfoByIds($uid);
			$userInfo = array_map(function($data){
				$data['headpic'] = get_avatar($data['id'],'m');
				return $data;
			},$userInfo);
			return $userInfo;
		}
	}
	/*
	 * 针对二维数组按键排序 默认降序
	 */
	private function arraySort($arr,$key,$order = SORT_DESC ) {
		if(empty($arr)) return false;
        $tmp = array();
		foreach($arr as $k=>$value) {
			 $arr[$k]['id'] = $k;
			 $tmp[$k] = (int) isset($value[$key]) ? $value[$key] : 0;
		}

		array_multisort($tmp,$order,$arr);
		return $arr;
	}
	
}
