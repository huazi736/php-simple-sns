<?php
/**
 * [ Duankou Inc ]
 * Created on 2012-3-5
 * @author fbbin
 * The filename : WebTimelineApi.php
 */
class WebTimelineApi extends DkApi {

	//标识数据来源于应用
	const TOPIC_FROM_APP = 4;

	public function __initialize() {
		DKBase::helper('timeline');
	}

	/**
	 * @author fbbin
	 * @desc 添加网页数据
	 * @param array $data
	 * @param array $tags
	 */
	public function addWebtopic(array $data, array $tags, $online = true) {
		if(empty($tags)) {
			return DKBase::status(false, 'empty_category', 120105);
		}
		$owebTopic = DKBase::import('Webtopic', 'web_timeline');
		!isset($data['from']) && $data['from'] = self::TOPIC_FROM_APP;
		$data['from'] == self::TOPIC_FROM_APP && $data['ctime'] = $data['dateline'];
		if (!$owebTopic->strictCheckFields($data)) {
			return false;
		}

		//添加信息实体数据
		$res = $owebTopic->addWebtopic($data);
		if ($res === false) {
			return false;
		}

		//是否需要显示在时间线和信息流上面
		if( ! $online )
		{
			return DKBase::status($res, 'empty_category', 120105);
		}

		//添加时间线上数据节点
		$owebTimeline = DKBase::import('WebAxis', 'web_timeline');
		$pointStatus = $owebTimeline->createPoint($res);

		//添加信息流数据
		$owebInfo = DKBase::import('Web', 'web_timeline');
		$webInfoStatus = $owebInfo->addWebinfo($res, $tags);

		unset($owebTopic, $owebTimeline, $odateAlias, $owebInfo);

		return $webInfoStatus && $pointStatus ? $res : DKBase::status(false, 'info_point_create_err', 120104);
	}

	/**
	 * @author fbbin
	 * @desc 删除网页中的一条数据
	 * @param int $tid
	 * @param array $tags
	 */
	public function delWebtopic($tid, array $tags) {
		if (!$tid) {
			return DKBase::status(false, 'topic_id_err', 120201);
		}
		//删除信息流中的数据
		$owebInfo = DKBase::import('Web', 'web_timeline');
		$delStatus = $owebInfo->delWebinfo($tid, $tags);

		//删除时间线数据
		$owebTimeline = DKBase::import('WebAxis', 'web_timeline');
		$timelineStatus = $owebTimeline->deletePoint($tid);
		if (!$timelineStatus && !$delStatus) {
			return DKBase::status(false, 'delete_failed', 120202);
		}
		unset($owebInfo, $owebTimeline);
		//删除数据实体
		$owebTopic = DKBase::import('Webtopic', 'web_timeline');
		return $owebTopic->delWebtopic($tid) ? true : DKBase::status(false, 'delete_err', 120203);
	}

	/**
	 * @author fbbin
	 * @desc 取消关注一个网页
	 * @param int $pid 网页ID
	 * @param array $tags 网页标签数组
	 */
	public function delAttentionWeb($uid, $pid, array $tags) {
		if ($uid && $pid && !empty($tags)) {
			$owebInfo = DKBase::import('Web', 'web_timeline');
			return $owebInfo->delAttentionWeb($uid, $pid, $tags);
		} else {
			return DKBase::status(false, 'param_not_enough', 120501);
		}
	}

	/**
	 * @author fbbin
	 * @desc 删除一个网页
	 * @param int $pid
	 * @param array $tags
	 */
	public function delWebpage($pid, array $tags) {
		if (!$pid) {
			return false;
		}
		$owebInfo = DKBase::import('Web', 'web_timeline');
		$owebAxis = DKBase::import('WebAxis', 'web_timeline');
		//删除网页的时间别名，然后然后接受到得网页信息
		return $owebAxis->delAllDateAlias($pid) && $owebInfo->delWebpage($pid, $tags);
	}

	/**
	 * @author fbbin
	 * @desc 通过映射关系删除网页信息
	 * @param int $fid
	 * @param string $type
	 * @param array $tags
	 */
	public function delWebtopicByMap($fid, $type, array $tags, $pid) {
		if (!($type && $fid && $pid)) {
			return DKBase::status(false, 'map_del_param_err', 120204);
		}
		$owebTopic = DKBase::import('Webtopic', 'web_timeline');
		$tid = $owebTopic->getTidByMap($fid, $type, $pid);
		if (!$tid) {
			return DKBase::status(false, 'topic_not_found_by_map', 120205);
		}
		unset($owebTopic);
		return $tid && $this->delWebtopic($tid, $tags);
	}

	/**
	 * @author fbbin
	 * @desc 各个应用更新时间线上面的信息实体
	 * @param int $fid
	 * @param bool $batch
	 * @param array $infos
	 */
	public function updateWebtopicByMap(array $infos, $batch = false) {
		!$batch && $infos = array($infos);
		$ctimeStatus = true;
		$updateStatus = true;
		$owebTopic = DKBase::import('Webtopic', 'web_timeline');
		foreach ($infos as $info) {
			if (count($info) < 4 || !isset($info['type']) || !isset($info['fid']) || !isset($info['pid'])) {
				continue;
			}
			if (isset($info['dateline'])) {
				$tid = $owebTopic->getTidByMap($info['fid'], $info['type'], $info['pid']);
				$ctimeStatus = $this->updateWebtopicTime($tid, $info['dateline']);
				unset($info['dateline']);
			}
			$updateStatus = $owebTopic->updateWebtopic($info) && $ctimeStatus;
		}
		unset($owebTopic);
		return $updateStatus;
	}

	/**
	 * @author fbbin
	 * @desc 替换或者是添加 Topic 信息
	 * @param array $data    Topic 数据
	 * @param array $relations 关系人数据
	 */
	public function replaceWebtopicByMap(array $infos, array $tags) {
		if (count($infos) < 4 || !isset($infos['type']) || !isset($infos['fid']) || !isset($infos['pid'])) {
			return DKBase::status(false, 'replace_param_err', 120401);
		}
		$webtopic = $this->getWebtopicByMap($infos['fid'], $infos['type'], $infos['pid']);
		if (!$webtopic) {
			return $this->addWebtopic($infos, $tags);
		} else {
			return $this->updateWebtopicByMap($infos);
		}
	}

	/**
	 * @author fbbin
	 * @desc 更新时间轴上的信息
	 * @param type $tid
	 * @param type $time
	 * @return bool 
	 */
	public function updateWebtopicTime($tid, $time) {
		if (!( $tid && $time )) {
			return DKBase::status(false, 'update_param_err', 120301);
		}
		$owebTopic = DKBase::import('Webtopic', 'web_timeline');
		$owebTimeline = DKBase::import('WebAxis', 'web_timeline');
		$timeline = $owebTimeline->updatePoint($tid, $time);
		$webTopic = $owebTopic->updateSpecialKey($tid, 'ctime', $time);
		unset($owebTopic, $owebTimeline);
		return $webTopic && $timeline;
	}

	/**
	 * @author fbbin
	 * @desc 更新时间轴上信息的突出显示状态
	 * @param type $tid
	 * @param type $highlight
	 * @return bool
	 */
	public function updateWebtopicHighlight($tid, $highlight = 1) {
		if (!$tid) {
			return DKBase::status(false, 'update_param_err', 120301);
		}
		$owebTopic = DKBase::import('Webtopic', 'web_timeline');
		return $owebTopic->updateSpecialKey($tid, 'highlight', $highlight);
	}

	/**
	 * @author fbbin
	 * @desc 更改 Topic 的 Hot 值
	 * @param type $tid Topic ID
	 * @param type $inc
	 * @return bool 
	 */
	public function updateWebtopicHot($tid, $inc = 1) {
		if (!$tid) {
			return DKBase::status(false, 'update_param_err', 120301);
		}
		$owebTopic = DKBase::import('Webtopic', 'web_timeline');
		return $owebTopic->updateSpecialKey($tid, 'hot', $inc);
	}

	/**
	 * @author fbbin
	 * @desc 根据映射关系返回一条完整的信息实体
	 * @param string $fid
	 * @param string $type
	 */
	public function getWebtopicByMap($fid, $type, $pid) {
		$results = array();
		if (is_array($fid)) {
			foreach ($fid as $id) {
				$results[$id] = $this->getWebtopicByMap($id, $type, $pid);
			}
			return json_encode($results);
		}
		$owebTopic = DKBase::import('Webtopic', 'web_timeline');
		return $owebTopic->getWebtopic($fid, $type, $pid);
	}

	/**
	 * @author fbbin
	 * @desc 检测网页时间线信息是否存在
	 * @param string $fid
	 * @param string $type
	 * @param string $pid
	 */
	public function checkTopicExists($fid, $type, $pid)
	{
		$topicModel = DKBase::import('Webtopic', 'web_timeline');
		return $topicModel->getTidByMap($fid, $type, $pid);
	}

}
