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

	private $timeaxisYearMonthTopicsKey = 'webaxis:%d:%d:%d';
	private $timeaxisYearHotsKey = 'webaxis:%d:%d:hot';
	private $timelineAvailableYearsKey = 'webline:%d:years';
	private $timelineAvailableMonthsInYearKey = 'webline:%d:%d';
	private $topicKey = 'webtopic:%d';
	private $maxTopicsPerPage = 20;
	private $customTimeTagsKey = 'datealias:%d';
	private $_redis = null;

	public function __construct()
	{
		parent::__construct();
		$this->init_redis();
		$this->_redis = $this->redis;
		$this->load->helper('timeline');
	}

	/**
	 * 从一个特定年份的某月开始取数据，一次返回20条记录
	 *
	 * @param int $webId
	 * @param int $year
	 * @param int $month
	 * @param int $lastTopicId  上次返回的最后一条信息的ID
	 * @param int $page
	 * @param int $startScore	上次返回的最后一条信息的创建时间
	 */
	public function getFragmentFeeds($webId, $year, $month, $lastTopicId = 0, $page = 0, $startScore = 0)
	{
		if ($startScore == 0) {
			$startScore = '+inf';
		}

		return $this->getTopicsByKeyScore(sprintf($this->timeaxisYearMonthTopicsKey,$webId,$year,$month), $startScore, $lastTopicId);
	}

	/**
	 * 从Redis中获取网站的年热点动态并返回该年有记录的月份
	 *
	 * @param int $webId
	 * @param int $year
	 */
	/*
	public function getYearHottestFeeds($webId, $year)
	{
		$rawYearHots = $this->_redis->zRevRange(sprintf($this->timeaxisYearHotsKey, $webId, $year), 0, -1);

        $yearHots = array();
		if (!empty($rawYearHots)) {

			// 只对当前年份的热点进行过滤
			$filter = false;
			if ($year == date('Y')) {
				$filter = true;
				$currentMonth = date('n');
			}

			foreach ($rawYearHots as $topicId) {
				$topic = $this->getTopic($topicId);

				if (!empty($topic)) {
					$topic['ymd'] = parseTime($topic['ctime']);
					// 是不是当前月份的，或者上一月份的数据，进行过滤
					if ($filter) {
						// 如果是当前月份的，或者上一月份的数据，直接跳过处理
						if ($topic['ymd']['month2'] == $currentMonth || $topic['ymd']['month2'] == $currentMonth - 1) {
							continue;
						}
					}

					$topic['friendly_time'] = makeFriendlyTime($topic['ctime']);
					$topic['friendly_line'] = makeFriendlyTime($topic['dateline']);
					$topic['dateline'] = date('YmdHis', $topic['dateline']);

					if ($topic['type'] == 'album') {
						$topic['picurl'] = json_decode($topic['picurl'], true);
					}
					$yearHots[] = $topic;
				}
			}

			// 根据年份获取该年的可用月份
			$avaliableMonthsInCurrentYear = $this->_redis->hKeys(sprintf($this->timelineAvailableMonthsInYearKey, $webId, $year));
			rsort($avaliableMonthsInCurrentYear, SORT_NUMERIC);
			return array('hots' => $yearHots, 'months' => $avaliableMonthsInCurrentYear, 'status' => 1);
		} else {
			// 如果没有记录则直接返回信息
			return array('status' => 0, 'msg' => '没有该年的热点动态');
		}
	}*/
	public function getYearHottestFeeds($webId, $year)
	{
		$avaliableMonthsInCurrentYear = $this->_redis->hKeys(sprintf($this->timelineAvailableMonthsInYearKey, $webId, $year));
		if($avaliableMonthsInCurrentYear) {
			rsort($avaliableMonthsInCurrentYear, SORT_NUMERIC);
			$current_year = date('Y');
			$current_months = date('n');
			if($year==$current_year) {
				if(isset($avaliableMonthsInCurrentYear[0])) {
					if($avaliableMonthsInCurrentYear[0]==$current_months) {
						array_shift($avaliableMonthsInCurrentYear);
					}
				}
				if(isset($avaliableMonthsInCurrentYear[0])) {
				    if($avaliableMonthsInCurrentYear[0]==($current_months-1)) {
						array_shift($avaliableMonthsInCurrentYear);
					}
				}
			}

			return array('months' => $avaliableMonthsInCurrentYear, 'status' => 1);
		}else {
			return array('months' => array(), 'status' => 0);
		}
	}

	/**
	 * 获取网站时间线上需要显示的年份
	 *
	 * @param int $webId
	 */
	public function getTimelineYears($webId, $createYear)
	{
		$timeline = $this->_redis->hKeys(sprintf($this->timelineAvailableYearsKey, $webId));

		$nearestYear = null;
		$timeline = array_filter($timeline);
		if (!empty($timeline)) {
			rsort($timeline);
			$nearestYear = $timeline[0];
		}

		// 如果今年没有数据，或者最近的年份与当前年份不一致，则只需要插入当前月份即可
		if (date('Y') != $nearestYear || null === $nearestYear) {
			array_unshift($timeline, array('date' => date('Y/n'), 'title' => '现在'));
		} else {
			// 获取当前这一年中可用的月份
			$nearestMonths = $this->_redis->hKeys(sprintf($this->timelineAvailableMonthsInYearKey, $webId, $nearestYear));

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

		// 获取用户自定义的时间标签
		$customTimeTags = $this->_redis->hGetAll(sprintf($this->customTimeTagsKey, $webId));

		// 替换年份为相应的用户自定义标签，替换加入端口网时间

		// 如果是加入端口的月份为当前月份或上一月份，则不把该年份改为加入端口网
		list($createYear, $createMonth) = explode('-', $createYear);
		$createMonth = (int) $createMonth;
		$currentMonth = date('n'); // 替换加入端口网进行比较用

		if (!empty($customTimeTags)) {
			foreach ($timeline as &$yearMonth) {
				// 如果是对应的是年月
				if (is_array($yearMonth)) {
					$realYearMonth = $yearMonth['date'];
					if (isset($customTimeTags[$realYearMonth])) {
						$yearMonth['memo'] = $customTimeTags[$realYearMonth];
					}

				} else {
					$tempYearMonth = $yearMonth;
					$yearMonth = array('date' => $yearMonth);
					if (isset($customTimeTags[$tempYearMonth])) {
						$yearMonth['memo'] = $customTimeTags[$tempYearMonth];
					}

					if ($tempYearMonth == $createYear && $createMonth != $currentMonth && $createMonth != $currentMonth - 1) {
						$yearMonth['title'] = '加入端口网';
					}
				}

			}


		} else {

			foreach ($timeline as &$yearMonth) {
				// 如果是对应的只是年份
				if (!is_array($yearMonth)) {
					if ($yearMonth == $createYear && $createMonth != $currentMonth && $createMonth != $currentMonth - 1) {
						$yearMonth = array('date' => $yearMonth, 'title' => '加入端口网');
					}else {
						$yearMonth = array('date'=>  $yearMonth);
					}
				}
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
	 * 通过key，score来获取Topics
	 *
	 * @param string $key
	 * @param int $startScore
	 * @param int $lastTopicId
	 */
	private function getTopicsByKeyScore($key, $startScore, $lastTopicId) {

		$topicIds = $this->_redis->zRevRangeByScore($key, $startScore, '-inf');
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

			//在截取数组之前把最后的topicId给取出来
			$lastTopicId = end($topicIds);

			$topicIds = array_slice($topicIds, 0, $this->maxTopicsPerPage);
			foreach ($topicIds as $topicId) {
				$topics[] = $this->getTopic($topicId);
			}

			// 获取当前获取数据的最后一条记录，比较它的ID是不是跟上一次返回的最后一条记录的ID相同

			$currentLastTopic = end($topics);
			$currentLastTopicId = $currentLastTopic['tid'];
			if ($lastTopicId && $lastTopicId == $currentLastTopicId) {
				$isEnd = true;
			}
			$startScore = $currentLastTopic['ctime'];

			foreach ($topics as &$topic) {
				if (!empty($topic)) {
					$topic['friendly_time'] = makeFriendlyTime($topic['ctime']);
					$topic['ymd'] = parseTime($topic['ctime']);
					$topic['friendly_line'] = makeFriendlyTime($topic['dateline']);
					$topic['dateline'] = date('YmdHis', $topic['dateline']);

					// 为特定的信息类型做处理
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
		return array('topics' => $topics, 'isEnd' => $isEnd, 'startScore' => $startScore, 'lastTopicId' => $currentLastTopicId, 'status' => $status);
	}

	/**
	 * 处理格式化数据
	 *
	 * @param array $topic
	 */
	private function prepareTopic($topic)
	{


		/* switch ($topic['type']) {
			case 'album':

			case 'event':

			case 'forward':

			case 'groupon':

			case 'dish':

			case 'goods':

			default:
				return $topic;
		} */
	    $costom = 'switch'.ucfirst($topic['type']);
		if(method_exists($this,$costom)) {
			return $this->$costom($topic);
		}
		return $topic;
	}
	public function  switchAlbum($topic) {
		$topic['picurl'] = json_decode($topic['picurl'], true);
		return $topic;
	}
	public function  switchPhoto($topic) {
		$topic['picurl'] = json_decode($topic['picurl'], true);
		return $topic;
	}
	public function switchEvent($topic) {
		$topic['starttime'] = makeFriendlyTime($topic['starttime']);
		$topic['starttime_ymd'] = parseTime($topic['starttime']);
		return $topic;
	}
	public function switchForward($topic) {
		$topic['forward'] = $this->getTopic($topic['fid']);
		if ($topic['forward'] && $topic['forward']['type'] == 'album') {
			$topic['forward']['picurl'] = json_decode($topic['forward']['picurl']);
		}
		if($topic['forward'] && $topic['forward']['type']=='photo') {
			$topic['forward']['picurl'] = json_decode($topic['forward']['picurl']);
		}
		return $topic;
	}
	public function switchGroupon($topic) {
		//$fastdfs = $this->getConfig();
		$groupon = json_decode($topic['groupon'], true);
		$topic['diff'] = $groupon['etime'] - time();
		$pics = json_decode($groupon['img'], true);
		if(is_array($pics)) {
			foreach($pics as $_k=>$_v) {
				$pics[$_k]['b']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['b']['url'];
			}
		}
		$groupon['img'] = $pics;
		// 信息流显示时候促销活动改了一下链接:denggang
		$groupon['link'] = mk_url('channel/catering_groupon/detail_page',array('id'=>$topic['fid'], 'web_id'=>$topic['pid']));
		$topic['groupon'] = $groupon;
		return $topic;
	}
	public function switchDish($topic) {
	    //$fastdfs = $this->getConfig();
		$dish = json_decode($topic['dish'], true);
		$pics = json_decode($dish['pics'], true);
		if(is_array($pics)) {
			foreach($pics as $_k=>$_v) {
				$pics[$_k]['b']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['b']['url'];
			}
		}
		$dish['pics'] = $pics;
		$topic['dish'] = $dish;
		return $topic;
	}
	public function switchGoods($topic) {
		$fastdfs_domain	= config_item('fastdfs_domain');

		$goods = json_decode($topic['goods'], true);
		if(is_array($goods['img'])){
			$goods['img'] = array_map(function($v) use ($fastdfs_domain){
				return 'http://'.$fastdfs_domain.'/'.$v;
			}, $goods['img']);
		}
		if(is_array($goods['thumb']) ){
			$goods['thumb'] = array_map(function($v) use ($fastdfs_domain){
				return 'http://'.$fastdfs_domain.'/'.$v;
			}, $goods['thumb']);
		}
		$topic['goods'] = $goods;
		return $topic;
	}
	/**
	 *景点频道--超值行程
	 */
	 public function switchTravel($topic){
	 	$travel = json_decode($topic['travel'], true);
		$pics = json_decode($travel['pics'], true);
		if(is_array($pics)) {
			foreach($pics as $_k=>$_v) {
				$pics[$_k]['b']['url'] = 'http://' .getFastdfs(). '/' . $pics[$_k]['b']['url'];
			}
		}
		$travel['pics'] = $pics;
		$topic['travel'] = $travel;
		return $topic;
	}

	/**
	 *景点频道--物价机票
	 */
	 public function switchAirticket($topic){
	 	$airticket = json_decode($topic['airticket'], true);
		$topic['airticket'] = $airticket;
		return $topic;
	}

	/**
	 * 需要转换的信息类型
	 *
	 */
    private function infoType() {
    	return array('album','event','forward',
    			     'groupon','goods','dish','photo','travel','airticket'
    			    );
    }
    //获取时间描述信息
    public function getDateAliases($pageid, $date) {
        return $this->_redis->hGet('datealias:' . $pageid, $date);
    }
    private function getConfig($configname='fastdfs',$group='default') {
    	// 获取fastdfs配置文件信息
    	return  getConfig($configname, $group);
    }
}