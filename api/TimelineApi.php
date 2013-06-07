<?php
/**
 * [ Duankou Inc ]
 * Created on 2012-3-5
 * @author fbbin
 * The filename : TimelineApi.php
 */
class TimelineApi extends DkApi
{
	//数据来源
	const TOPIC_FROM_INFO = 1;
	const TOPIC_FROM_APPS = 2;
	const TOPIC_FROM_SYSTEM = 5;

    public function __initialize()
    {
        DKBase::helper('timeline');
    }

	/**
	 * 添加信息到时间轴
	 * @param array $data        信息的内容
	 * @param array $premission  自定义权限时，用户ID列表
	 * @param array $online  是否要在时间线和信息流上面显示
	 * @return array
	 */
	public function addTimeline(array $data = array(), array $permission = array(), $online = true) {
		$topicModel = DKBase::import('Topic', 'timeline');
		//设置添加时间轴信息的来源标记
		$data['from'] = isset($data['from']) ? $data['from'] : self::TOPIC_FROM_APPS;
		$data['from'] == self::TOPIC_FROM_APPS && $data['ctime'] = $data['dateline'];
		$check = $topicModel->strictCheckFields($data);
		if ($check) {
			if( $data['permission'] == -1 )
			{
				if(empty($permission) && !is_array($permission))
				{
					return DKBase::status(false, 'relationslist_error',110106);
				}
				foreach ($permission as $value)
				{
					if( !is_numeric($value) )
					{
						return DKBase::status(false, 'relationslist_member_error',110107);
					}
				}
			}

			//对来自应用的数据处理
			if ($data['from'] == 2 && isset($data['content'])) {
				$htmlres = htmlSubString($data['content'], 140);
				$data['content'] = $htmlres['0'];
			}

			//生成 Topic
			$topic = $topicModel->addTopic($data, $permission);
			if( $topic === false) {
				return false;
			} else if(DKBase::getMessage() === 'multi_data_updated') {
				return true;
			}

			//是否需要产生时间线和信息流
			if( ! $online )
			{
				return DKBase::status($topic);
			}

			//生成信息流
			if ( !$topicModel->checkSpecialType($data['type'])) {
				$oinfoModel = DKBase::import('Info', 'timeline');
				$oinfoModel->add($topic, $permission);
			}

			//生成时间线
			$oaxisModel = DKBase::import('Axis', 'timeline');
			$oaxisModel->createPoint($topic);
			unset($topicModel, $data, $oinfoModel, $oaxisModel);

			return DKBase::status($topic);
		} else {
			return false;
		}
	}

	/**
	 * 更新时间轴上的信息
	 * @param int $tid     Topic ID
	 * @param int $time    定位时间
	 * @return bool 
	 */
	public function updateTimeline($tid, $time) {
		$oaxisModel = DKBase::import('Axis', 'timeline');
		$res = $oaxisModel->updatePoint($tid, $time);

		//更新 Topic 的时间
		$topicModel = DKBase::import('Topic', 'timeline');
		$topicModel->updateSpecialKey($tid, 'ctime', $time);
		unset($oaxisModel, $topicModel);
		return $res;
	}

	/**
	 * 更新时间轴上信息的突出显示状态
	 * @param int $tid         Topic ID
	 * @param int $highlight   显示状态：1 突出显示, 0 常规显示
	 * @return bool
	 */
	public function updateTimelineHighlight($tid, $highlight = 1) {
		$topicModel = DKBase::import('Topic', 'timeline');
		return $topicModel->updateSpecialKey($tid, 'highlight', $highlight);
	}

	/**
	 * 移除多条目数据中的内容
	 *
	 * @param array
	 * @return bool
	 */
	public function removeMultiItem( array $data) {
		$topicModel = DKBase::import('Topic', 'timeline');
		return $topicModel->removeMultiItem($data);
	}

	/**
	 * 删除时间轴上的信息
	 * @param int $tid     Topic ID/ F(foreign) ID
	 * @param int $UID    用户ID
	 * @param string $type    F(foreign) 类型： blog, album ...
	 * @return bool
	 */
	public function removeTimeline($tid, $uid, $type = '', $fromTimeline = false) {
		$topicModel = DKBase::import('Topic', 'timeline');
		if (!empty($type)) {
			//根据类型和 fid 获取 Topic ID, 此处传入的 tid 为 fid
			$tid = $topicModel->getTidByMap($tid, $type, $uid);
			if(!$tid) {
				return DKBase::status(false, 'get_tid_by_map_failed', 110401, array($tid, $type, $uid));
			}
		}

		$oaxisModel = DKBase::import('Axis', 'timeline');
		$res = $oaxisModel->deletePoint($tid);
		//删除信息流
		if( $type != 'uinfo' ) {
			$oinfoModel = DKBase::import('Info', 'timeline');
			$oinfoModel->delInfo($tid);
		}
		//删除 Topic
		if ($res) {
			$res2 = $topicModel->delTopic($tid, $fromTimeline);
		} else {
			$res2 = false;
		}
		unset($oinfoModel, $topicModel, $fromTimeline);
		return $res && $res2;
	}

	/**
	 * @author fbbin
	 * @desc 解除人物关系后调用处理用户的数据
	 * @param int $fromUid
	 * @param int $toUid
	 * @param int $relation
	 */
	public function delRelationsTopic($fromUid, $toUid, $relation = 1) {
		if (!($fromUid && $toUid && $relation)) {
			return DKBase::status(false, 'required_data_empty', 110401);
		}
		/**
		 * 解除关注(4)/好友(1)关系的信息流
		 */
		if(!in_array($relation, array(1, 4))) {
			return DKBase::status(false, 'no_relation', 110402);
		}
		$oinfoModel = DKBase::import('Info', 'timeline');
		//解除好友关系
		if ((int) $relation == 1) {
			return $oinfoModel->delRelationsTopic($fromUid, $toUid, $relation)
				&& $oinfoModel->delRelationsTopic($toUid, $fromUid, $relation);
		}
		//解除关注关系
		return $oinfoModel->delRelationsTopic($fromUid, $toUid, $relation);
	}

	/**
	 * 更新 Topic 信息
	 * @param array $data    Topic 数据
	 * @param bool 是否是批量更新
	 * @return bool 
	 */
	public function updateTopic( array $data, $relations = array(), $batch = false) {
		!$batch && $data = array($data);
		$upstatus = true;$resultStatus = true;$permissionStatus=true;
		$topicModel = DKBase::import('Topic', 'timeline');
		foreach ($data as $value) {
			if( !empty($value['fid']) && !empty($value['type']) && !empty($value['uid'])) {
				if( isset($value['permission']) ) {
					$tid = $topicModel->getTidByMap($value['fid'], $value['type'], $value['uid']);
					$permissionStatus = $this->updatePermission($tid, $value['permission'], $relations);
					unset($value['permission']);
				}
				if( isset($value['dateline']) ) {
					$upstatus = $this->updateCtimeByMap($value['fid'], $value['type'], $value['uid'], $value['dateline']);
					unset($value['dateline']);
				}
			}
			$resultStatus = $upstatus && $permissionStatus;
			if( count($value) >= 4 ) {
				$resultStatus = $topicModel->updateTopic($value, $relations) && $resultStatus;
			}
		}
		unset($data, $topicModel);
		return $resultStatus;
	}

	/**
	 * @author fbbin
	 * @desc 替换或者是添加 Topic 信息
	 * @param array $data    Topic 数据
	 * @param array $relations 关系人数据
	 */
	public function replaceTopic(array $data, $relations = array()) {
		if( $data['fid'] && $data['type'] && count($data) >= 4 ) {
			$topic = $this->getTopicByMap($data['fid'], $data['type'], $data['uid']);
			if( $topic ) {
				return $this->updateTopic($data, $relations);
			} else {
				return $this->addTimeline($data, $relations);
			}
		}
		return false;
	}

	/**
	 * 更改 Topic 的 Hot 值
	 * @param int $tid Topic ID
	 * @param int $inc 步长
	 * @return bool 
	 */
	public function updateTopicHot($tid, $inc = 1) {
		$topicModel = DKBase::import('Topic', 'timeline');
		return $topicModel->updateSpecialKey($tid, 'hot', $inc);
	}

	/**
	 * 更新时间轴定位时间
	 * @param int $fid     外键ID
	 * @param string $type    外键类型
	 * @param int $time    定位的时间戳
	 * @param int $uid    用户ID
	 * @return bool 
	 */
	public function updateCtimeByMap($fid, $type, $uid, $time) {
		$topicModel = DKBase::import('Topic', 'timeline');
		$tid = $topicModel->getTidByMap($fid, $type, $uid);
		unset($topicModel);
		if(!$tid) {
			return DKBase::status(false, 'data_not_exists', 110202);
		} else {
			return $this->updateTimeline($tid, $time);
		}
	}

	/**
	 * @author fbbin
	 * @desc 修改信息实体的权限值
	 * @param intval $tid
	 * @param intval $newPermission
	 * @param arrray $relationslist
	 */
	public function updatePermission($tid, $newPermission, $relations = array()) {
		$topicModel = DKBase::import('Topic', 'timeline');
		$topic = $topicModel->getTopicByTid($tid);
		if(!$topic) {
			return DKBase::status(false, 'data_not_exists', 110202);
		}
		if ( $topic['permission'] == $newPermission && $newPermission != -1 ) {
			return DKBase::status(true);
		}
		if ($newPermission == -1 && (empty($relations) || !is_array($relations))){
			return DKBase::status(false, 'permission_data_err', 110203);
		}
		if ( $tid && $newPermission ) {
			$oinfoModel = DKBase::import('Info', 'timeline');
			$updateStatus = $oinfoModel->updatePermission($tid, $newPermission, $relations);
			if( $updateStatus === false ) {
				return false;
			}
			//更改topic的权限值
			$data = $topic;
			$data['dateline'] = $topic['ctime'];
			$data['permission'] = $newPermission;
			if( $newPermission == -1 ) {
				$topicModel->updateSpecialKey($tid, 'relations', json_encode($relations));
				$data['relations'] = $relations;
			}
			$oaxisModel = DKBase::import('Axis', 'timeline');
			$oaxisModel->deletePoint($tid) && $oaxisModel->createPoint($data);
			unset($oinfoModel, $data, $oaxisModel);
			return $topicModel->updateSpecialKey($tid, 'permission', $newPermission) && $updateStatus;
		}
		return false;
	}

	/**
	 * @author fbbin
	 * @desc 根据映射关系返回一条完整的信息实体
	 * @param string $fid
	 * @param string $type
	 */
	public function getTopicByMap($fid, $type, $uid) {
		$results = array();
		if (is_array($fid)) {
			foreach ($fid as $id) {
				$results[$id] = $this->getTopicByMap($id, $type, $uid);
			}
			return json_encode($results);
		}
		$topicModel = DKBase::import('Topic', 'timeline');
		return $topicModel->getTopicByFidAndType($fid, $type, $uid);
	}

	/**
	 * @author fbbin
	 * @desc 根据TID返回一条完整的信息实体
	 * @param string $fid
	 * @param string $type
	 */
	public function getTopicByTid($tid) {
		$results = array();
		if (is_array($tid)) {
			foreach ($tid as $id) {
				$results[$id] = $this->getTopicByTid($id);
			}
			return json_encode($results);
		}
		$topicModel = DKBase::import('Topic', 'timeline');
		return $topicModel->getTopicByTid($tid);
	}

	/**
	 * @author fbbin
	 * @desc 检测时间线信息是否存在
	 * @param string $fid
	 * @param string $type
	 * @param string $uid
	 */
	public function checkTopicExists($fid, $type, $uid)
	{
		$topicModel = DKBase::import('Topic', 'timeline');
		return $topicModel->getTidByMap($fid, $type, $uid);
	}

}
