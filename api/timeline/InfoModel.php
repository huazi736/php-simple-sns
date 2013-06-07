<?php
/**
 * [ Duankou Inc ]
 * Created on 2012-3-5
 * @author fbbin
 * The filename : InfoModel.php
 */
class InfoModel extends DkModel
{
    //topic相关的权限
    const PER_FRIS = 4;
    const PER_FANS = 3;
    const PER_SELF = 8;
    const PER_CUSTOM = -1;
    const PER_OPEN = 1;

    //人与人之间的关系
    const RELATION_FANS = 4;
    const RELATION_FRIS = 1;

    //标识来源于信息流
    private $_from = 1;
    //清除数据时是否清除自己的
    private $_delSelf = true;

    public function __initialize() 
    {
        $this->init_redis();
    }

    /**
     * @author fbbin
     * @desc 添加信息流数据
     * @param array $data
     * @param string $to
     * @param array $relations
     */
    public function add($data, $relations = array()) {
        if ($data['type'] == 'forward') {
            $info = $this->redis->hGetAll('topic:' . $data['fid']);
            if (!$info) {
                return false;
            }
            if ($info['permission'] != self::PER_OPEN) {
                $data['forward'] = $info;
                return $this->doSharePush($data, $relations, 'push');
            }
        }
        $this->_from = (int) $data['from'];
        return $this->doPush($data['uid'], $data['tid'], $data['dateline'], $data['permission'], $relations);
    }

    /**
     * @author fbbin
     * @desc 删除一条信息流数据
     * @param string $uid
     * @param string $dest
     */
    public function delInfo($tid) {
        $info = $this->redis->hGetAll('topic:' . $tid);
        if (empty($info)) {
            return false;
        }
        $relations = array();
        if ((int) $info['permission'] == self::PER_CUSTOM) {
            $relations = json_decode($info['relations']);
        }
        if ($info['type'] == 'forward') {
            $topic = $this->redis->hGetAll('topic:' . $info['fid']);
            if (!$topic) {
                return false;
            }
            if ($topic['permission'] != self::PER_OPEN ) {
                $info['forward'] = $topic;
                return $this->doSharePush($info, $relations, 'delpush');
            }
        }
        $this->_from = (int) $info['from'];
        return $this->doPush($info['uid'], $info['tid'], $info['dateline'], $info['permission'], $relations, 'delpush');
    }

    /**
     * @author fbbin
     * @desc 转发信息流权限和分发处理
     * @param array $data
     * @param array $relations
     * @param string $style
     */
    public function doSharePush(array $data, array $relations, $style = 'push') {
        if (empty($data)) {
            return false;
        }
        $social = DKBase::import('Relation');
        $shareStatus = true;
        switch (intval($data['forward']['permission'])) {
            //获取两者的共同好友
            case self::PER_FRIS:
                $relations = array_intersect($social->getAllFriends($data['uid']), $social->getAllFriends($data['forward']['uid']));
                $shareStatus = $this->doSent($data['tid'], $data['dateline'], 'fris', $style, $relations);
                break;
            //获取两者的共同粉丝
            case self::PER_FANS:
                $relations = array_intersect($social->getValiditionFollowers($data['uid']), $social->getValiditionFollowers($data['forward']['uid']));
                $shareStatus = $this->doSent($data['tid'], $data['dateline'], 'fans', $style, $relations);
                break;
            //获取自定义情况下的共同关系人
            case self::PER_CUSTOM:
                $relations = array_intersect($social->getAllFriends($data['uid']), $relations);
                foreach ($relations as $value) {
                    $frisStatus = $this->doSentUser($data['tid'], $data['dateline'], $value, 'fris', $style);
                }
                $shareStatus = $frisStatus;
                break;
        }
        return $shareStatus && $this->doSentUser($data['tid'], $data['dateline'], $data['uid'], 'self', $style);
    }

	/**
	 * @author fbbin
	 * @desc 解除人物关系后调用处理用户的数据
	 * @param int $fromUid
	 * @param int $toUid
	 * @param int $relation 1:好友，4：粉丝
	 */
	public function delRelationsTopic($fromUid, $toUid, $relation = 4) {
		$social = DKBase::import('Relation');
		$relationStartTime = $social->getStartTtimeOfUsers($fromUid, $toUid, $relation);
		if ($relationStartTime === false) {
			return DKBase::status(false, 'get_relation_start_failed', 110404);
		}
		$relationStartTime = date('Ym', $relationStartTime);
		$toObjectInfoKeys = $this->redis->keys('info:' . $toUid . ':self:*');
		//对key进行过滤，避免不必要的操作
		$toObjectInfoKeys = array_filter($toObjectInfoKeys, function($key) use($relationStartTime) {
			if (substr($key, strrpos($key, ':') + 1) >= $relationStartTime) {
				return true;
			}
			return false;
		});
		$removeStatus = true;
		switch ((int) $relation) {
			case self::RELATION_FANS :
				$indexInfoKey = $this->getIndexKey($fromUid, 'fans');
				foreach ($toObjectInfoKeys as $key) {
					$date = substr($key, strrpos($key, ':') + 1);
					$values = $this->redis->zRange($key, 0, -1) ? : array();
					$indexKey = "info:" . $fromUid . ":fans:" . $date;
					foreach ($values as $val) {
						if ($this->redis->zDelete($indexKey, $val)) {
							$this->redis->hIncrBy($indexInfoKey, $date, -1);
						}
					}
				}
				break;
			case self::RELATION_FRIS :
				$indexFrisInfo = $this->getIndexKey($fromUid, 'fris');
				foreach ($toObjectInfoKeys as $key) {
					$date = substr($key, strrpos($key, ':') + 1);
					$outputKey = 'tmp:ruit:' . $fromUid;
					//求出只写给好友的信息数据
					$this->redis->zInter($outputKey, array('info:' . $toUid . ':self:' . $date, 'info:' . $fromUid . ':fris:' . $date), array(1, 1), 'SUM');
					$values = $this->redis->zRange($outputKey, 0, -1) ? : array();
					$indexFriKey = "info:" . $fromUid . ":fris:" . $date;
					foreach ($values as $val) {
						if ($this->redis->zDelete($indexFriKey, $val)) {
							$this->redis->hIncrBy($indexFrisInfo, $date, -1);
						}
					}
					$this->redis->delete($outputKey);
				}
				break;
			default:
				$removeStatus = false;
				break;
		}
		return $removeStatus;
	}

    /**
     * @author fbbin
     * @desc 修改信息实体的权限值
     * @param intval $tid
     * @param intval $newPermission
     * @param arrray $relationslist
     */
    public function updatePermission($tid, $newPermission = 1, $relationslist = array()) {
        $topic = $this->redis->hGetAll('topic:' . $tid);
        if (empty($topic)) {
			return DKBase::status(false, 'data_not_exists', 110202);
        }
        $this->_from = (int) $topic['from'];
        //当权限修改是自己本身时，这时关系的数据量在发生变化，因此需要修改（这里有很多重复的耗性能的操作）
        if ((int) $topic['permission'] == $newPermission) {
            switch ($newPermission) {
                //修改为公开
                case self::PER_OPEN:
                //修改为粉丝可见
                case self::PER_FANS:
                //修改为好友可见
                case self::PER_FRIS:
                //修改为仅自己
                case self::PER_SELF:
                    return true;
                    break;
                //修改为自定义
                case self::PER_CUSTOM:
                    if (empty($relationslist)) {
                        unset($topic);
					   return DKBase::status(false, 'permission_data_err', 110203);
                    }
                    $oldRelations = isset($topic['relations']) ? json_decode($topic['relations']) : array();
                    sort($oldRelations);
                    sort($relationslist);
                    if ($oldRelations !== $relationslist) {
                        $intersect = array_intersect($oldRelations, $relationslist);
                        if (empty($intersect)) {
                            //删除以前自定义的全部用户
                            $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $oldRelations, 'delpush');
                            //完全添加新的用户
                            $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $relationslist, 'push');
                            unset($topic, $intersect, $oldRelations, $relationslist);
                            return true;
                        } else {
                            //删除除了两者公共数据以外的用户
                            $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, array_diff($oldRelations, $intersect), 'delpush');
                            //添加除了两者公共数据以外的用户
                            $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, array_diff($relationslist, $intersect), 'push');
                            unset($topic, $intersect, $oldRelations, $relationslist);
                            return true;
                        }
                    } else {
                        return true;
                    }
                    break;
            }
        } else {
            $social = DKBase::import('Relation');
            switch ($newPermission) {
                //修改为公开
                case self::PER_OPEN:
                //修改为粉丝可见
                case self::PER_FANS:
                    switch (intval($topic['permission'])) {
                        case self::PER_OPEN:
                            unset($topic);
                            return true; //由公开修改为粉丝可见不需要做任何修改
                            break;
                        case self::PER_CUSTOM:
                            $hadSendUsers = isset($topic['relations']) ? json_decode($topic['relations']) : array();
                            $followers = $social->getValiditionFollowers($topic['uid']);
                            //清除原先发送的自定义用户
                            $this->doSent($tid, $topic['dateline'], 'fris', 'delpush', $hadSendUsers);
                            //重新发送给粉丝用户
                            $this->doSent($tid, $topic['dateline'], 'fans', 'push', $followers);
                            //删除自定义的用户列表
                            $this->redis->hDel('topic:' . $tid, 'relations');
                            unset($followers, $hadSendUsers, $topic);
                            return true;
                            break;
                        case self::PER_FRIS:
                            $allFris = $social->getAllFriends($topic['uid']);
                            $allFollowers = $social->getValiditionFollowers($topic['uid']);
                            //清除原先发送的好友信息
                            $this->doSent($tid, $topic['dateline'], 'fris', 'delpush', $allFris);
                            //重新发送给粉丝用户
                            $this->doSent($tid, $topic['dateline'], 'fans', 'push', $allFollowers);
                            unset($allFris, $allFollowers, $topic);
                            return true;
                            break;
                        case self::PER_FANS:
                            unset($topic);
                            return true;
                            break;
                        case self::PER_SELF:
                            return $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission);
                            break;
                    }
                    break;
                //修改为自定义
                case self::PER_CUSTOM:
                    if (empty($relationslist)) {
                        unset($topic);
						return DKBase::status(false, 'permission_data_err', 110203);
                    }
                    switch (intval($topic['permission'])) {
                        case self::PER_OPEN:
                        //公开情况下和仅粉丝可见的操作相同
                        case self::PER_FANS:
                            $allFollowers = $social->getValiditionFollowers($topic['uid']);
                            //清除原先发送给粉丝的数据
                            $this->doSent($tid, $topic['dateline'], 'fans', 'delpush', $allFollowers);
                            //重新发送给自定义的用户
                            $this->doSent($tid, $topic['dateline'], 'fris', 'push', $relationslist);
                            unset($allFansFris, $topic);
                            return true;
                            break;
                        case self::PER_FRIS:
                            $allFris = $social->getAllFriends($topic['uid']);
                            //清除两者差集的部分用户
                            $this->doSent($tid, $topic['dateline'], 'fris', 'delpush', array_diff($allFris, $relationslist));
                            unset($allFris, $topic);
                            return true;
                            break;
                        case self::PER_SELF:
                            return $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission, $relationslist);
                            break;
                    }
                    break;
                //修改为仅好友可见
                case self::PER_FRIS:
                    switch (intval($topic['permission'])) {
                        case self::PER_OPEN:
                        //公开情况下和仅粉丝可见的操作相同
                        case self::PER_FANS:
                            $allFollowers = $social->getValiditionFollowers($topic['uid']);
                            //清除原先发送给粉丝的数据
                            $this->doSent($tid, $topic['dateline'], 'fans', 'delpush', $allFollowers);
                            $allFris = $social->getAllFriends($topic['uid']);
                            //重新发送给好友用户
                            $this->doSent($tid, $topic['dateline'], 'fris', 'push', $allFris);
                            unset($allFris, $allFollowers, $topic);
                            return true;
                            break;
                        case self::PER_CUSTOM:
                            $allFris = $social->getAllFriends($topic['uid']);
                            $hadSendUsers = isset($topic['relations']) ? json_decode($topic['relations']) : array();
                            //增加发送给未在自定义列表中的好友
                            $this->doSent($tid, $topic['dateline'], 'fris', 'push', array_diff($allFris, $hadSendUsers));
                            //删除自定义的用户列表
                            $this->redis->hDel('topic:' . $tid, 'relations');
                            unset($allFris, $hadSendUsers, $topic);
                            return true;
                            break;
                        case self::PER_SELF:
                            return $this->doPush($topic['uid'], $topic['tid'], $topic['dateline'], $newPermission);
                            break;
                    }
                    break;
                //修改为仅自己可见
                case self::PER_SELF:
                    //标识不要删除self里面自己的数据
                    $this->_delSelf = false;
                    $delStatus = $this->delInfo($tid); //删除发送出去的所有数据
                    if ($delStatus && $topic['permission'] == '-1') {
                        //删除自定义的用户列表
                        $this->redis->hDel('topic:' . $tid, 'relations');
                    }
                    unset($topic);
                    return $delStatus;
                    break;
            }
        }
    }

    /**
     * @author fbbin
     * @desc 获取信息流主题操作KEY
     * @param string $uid
     * @param string $dest
     */
    private function getKey($uid = '', $dest = 'fans', $time = '') {
        $unixTime = $time ? : time();
        $key = '';
        switch ($dest) {
            case 'fans' : $key = "info:" . $uid . ":fans:" . date('Ym', $unixTime);
                break;
            case 'both' : $key = "info:" . $uid . ":both:" . date('Ym', $unixTime);
                break;
            case 'fris' : $key = "info:" . $uid . ":fris:" . date('Ym', $unixTime);
                break;
            case 'self' : $key = "info:" . $uid . ":self:" . date('Ym', $unixTime);
                break;
        }
        return $key;
    }

    /**
     * @author fbbin
     * @desc 获取信息流主题操作索引KEY
     * @param string $uid
     * @param string $dest
     */
    private function getIndexKey($uid = '', $dest = 'fans') {
        $indexKey = '';
        switch ($dest) {
            case 'fans' : $indexKey = "info:" . $uid . ":fansInfos";
                break;
            case 'both' : $indexKey = "info:" . $uid . ":bothInfos";
                break;
            case 'fris' : $indexKey = "info:" . $uid . ":frisInfos";
                break;
            case 'self' : $indexKey = "info:" . $uid . ":selfInfos";
                break;
        }
        return $indexKey;
    }

    /**
     * @author fbbin
     * @desc 从粉丝或者是好友的INBOX中删除我的某条数据整个过程交由队列处理)
     * @param string $uid
     * @param int $tid
     * @param int $dateliine
     * @param int $permission
     * @param array $relations
     */
    private function doPush($uid, $tid, $dateline, $permission, $relations = array(), $style = 'push') {
        $pushStatus = true;
        switch ($permission) {
            //自定义
            case self::PER_CUSTOM:
                if (empty($relations)) {
                    return false;
                }
                //自定义的人员列表是基于好友的，因此不需要做判断
                foreach ($relations as $value) {
                    $fansStatus = $this->doSentUser($tid, $dateline, $value, 'fris', $style);
                }
                $pushStatus = $fansStatus;
                break;
            //公开
            case self::PER_OPEN:
                $pushStatus = $this->doSentFans($uid, $tid, $dateline, $style) && $this->doSentFris($uid, $tid, $dateline, $style);
                break;
            //粉丝可见
            case self::PER_FANS:
                $pushStatus = $this->doSentFans($uid, $tid, $dateline, $style);
                break;
            //仅好友可见
            case self::PER_FRIS:
                $pushStatus = $this->doSentFris($uid, $tid, $dateline, $style);
                break;
            //仅自己可见
            case self::PER_SELF:
                $pushStatus = true;
                break;
        }
        //此外要记录自己发布的所有数据
        if ($style == 'delpush' && !$this->_delSelf) {
            return $pushStatus;
        }
        return $pushStatus && $this->doSentUser($tid, $dateline, $uid, 'self', $style);
    }

    /**
     * @author fbbin
     * @desc 发送给好友模块
     * @param string $uid
     * @param int $tid
     * @param int $dateline
     */
    private function doSentFris($uid, $tid, $dateline, $style) {
        $social = DKBase::import('Relation');
        $relationList = $social->getAllFriends($uid);
        return $this->doSent($tid, $dateline, 'fris', $style, $relationList);
    }

    /**
     * @author fbbin
     * @desc 发送给粉丝
     * @param string $uid
     * @param int $tid
     * @param int $dateline
     * @param bool $friends
     */
    private function doSentFans($uid, $tid, $dateline, $style, $friends = false) {
        $social = DKBase::import('Relation');
        //取得粉丝中好友的人员
        if ($friends) {
            $relationList = $social->getAllFriends($uid);
        }
        //取得纯粉丝人员
        else {
            $relationList = $social->getValiditionFollowers($uid);
        }
        return $this->doSent($tid, $dateline, 'fans', $style, $relationList);
    }

    /**
     * @author fbbin
     * @desc 发送给互相关注
     * @param string $uid
     * @param int $tid
     * @param int $dateline
     * @param bool $friends
     */
    private function doSentFansBoth($uid, $tid, $dateline, $style, $friends = false) {
        $social = DKBase::import('Relation');
        //取得互相关注中好友人员
        if ($friends) {
            $relationList = $social->getAllFriends($uid);
        }
        //取得纯互相关注人员
        else {
            $relationList = $social->getAllBothFollowers($uid);
        }
        return $this->doSent($tid, $dateline, 'both', $style, $relationList);
    }

    /**
     * @author fbbin
     * @desc 发送给粉丝下面的好友
     * @param string $uid
     * @param int $tid
     * @param int $dateline
     */
    private function doSentFansFris($uid, $tid, $dateline, $style) {
        $social = DKBase::import('Relation');
        $relationList = $social->getAllFriends($uid);
        return $this->doSent($tid, $dateline, 'fris', $style, $relationList);
    }

    /**
     * @author fbbin
     * @desc 执行对象的分发/删除
     * @param int $tid
     * @param int $dateline
     * @param string $style
     * @param string $object
     * @param string $style
     * @param array $relationlist
     */
    private function doSent($tid, $dateline, $object, $style, $relationlist) {
        if (empty($relationlist)) {
            return true;
        }
        $dateline = intval($dateline);
        foreach ($relationlist as $value) {
            $key = $this->getKey($value, $object, $dateline);
            $indexKey = $this->getIndexKey($value, $object);
            if ($style === 'push') {
                if ($this->redis->zAdd($key, $dateline, $tid)) {
                    $this->redis->hIncrBy($indexKey, date('Ym', $dateline), 1);
                }
            } else if ($style === 'delpush') {
                if ($this->redis->zDelete($key, $tid)) {
                    $this->redis->hIncrBy($indexKey, date('Ym', $dateline), -1);
                }
            }
            if (empty($key)) {
                log_message('info', func_get_args());
            }
        }
        return true;
    }

    /**
     * @author fbbin
     * @desc 发送给指定的人
     * @param string $uid
     * @param int $tid
     * @param int $dateline
     * @param string $type
     */
    private function doSentUser($tid, $dateline, $touid, $type, $style) {
        $dateline = intval($dateline);
        $key = $this->getKey($touid, $type, $dateline);
        $indexKey = $this->getIndexKey($touid, $type);
        if ($style === 'push') {
            $status = $this->redis->zAdd($key, $dateline, $tid);
            $inc = 1;
        } else {
            $status = $this->redis->zRem($key, $tid);
            $inc = -1;
        }
        if ($status) {
            $this->redis->hIncrBy($indexKey, date('Ym', $dateline), $inc);
        }
        if (empty($key)) {
            log_message('info', func_get_args());
        }
        return true;
    }

}

?>
