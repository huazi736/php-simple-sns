<?php
/**
 * [ Duankou Inc ]
 * Created on 2012-4-5
 * @author fbbin
 * The filename : AxisModel.php
 */
class AxisModel extends DkModel
{

    //信息的权限设定
    const PER_SELF = 8;
    const PER_FRIS = 4;
    const PER_FANS = 3;
    const PER_OPEN = 1;
    const PER_CUSTOM = -1;

    public function __initialize()
    {
        $this->init_redis();
    }

    /**
     * @author fbbin
     * @desc 删除一个时间线节点数据
     * @param string $data
     */
    public function deletePoint($tid)
    {
        if( ! $tid )
        {
            return false;
        }
        $topic = $this->redis->hMGet('topic:' . $tid, array('uid', 'ctime', 'permission'));
        if( !$topic )
        {
            return false;
        }
        extract($topic);
        $year = date('Y', $ctime);
        $month = date('n', $ctime);
        $scopes = $this->_getScopesByPermission($permission);
		if ( !$scopes )
        {
            return false;
        }
        foreach ($scopes as $scope)
        {
            $indexKey = $this->_getTimelineKey($uid, $scope, $ctime);
            $actionStatus = $this->_doAction($indexKey, $ctime, $tid, false);
            if ($actionStatus === false)
            {
                //log
            }
        }
        //更新有效年份和月份
        $this->_updateMonths($uid, $year, $month, false);
        $this->_updateYears($uid, $year, false);
        return true;
    }

    /**
     * @author fbbin
     * @desc更新一个数据节点
     * @param string $tid
     * @param string $time
     */
    public function updatePoint($tid, $time)
    {
    	if( !$tid )
        {
            return false;
        }
        $topic = $this->redis->hMGet('topic:' . $tid, array('uid', 'ctime', 'permission'));
        if( !$topic )
        {
            return false;
        }
        extract($topic);
        $old_year = date('Y', $ctime);
        $old_month = date('n', $ctime);
        $year = date('Y', $time);
        $month = date('n', $time);
        $scopes = $this->_getScopesByPermission($permission);

        if ($year . $month != $old_year . $old_month)
        {
            //删除原先的记录的数据节点
            foreach ($scopes as $scope)
            {
                $indexKey = $this->_getTimelineKey($uid, $scope, $ctime);
                $actionStatus = $this->_doAction($indexKey, $ctime, $tid, false);
                if ($actionStatus === false)
                {
                    //log
                }
            }
            //更新有效月份
            $this->_updateMonths($uid, $old_year, $old_month, false);
            $this->_updateMonths($uid, $year, $month);
        }
        //更新有效年份
        if ($year != $old_year)
        {
            $this->_updateYears($uid, $old_year, false);
            $this->_updateYears($uid, $year);
        }
        //重新记录数据节点
        foreach ($scopes as $scope)
        {
            $indexKey = $this->_getTimelineKey($uid, $scope, $time, $tid);
            $actionStatus = $this->_doAction($indexKey, $time, $tid);
            if ($actionStatus === false)
            {
                //log
            }
        }
        return true;
    }

    /**
     * @author fbbin
     * @desc 创建一个时间线节点数据
     * @param array $data
     */
    public function createPoint( array $data)
    {
        $uid = $data['uid'];
        $tid = $data['tid'];
        $time = $data['ctime'];
        $year = date('Y', $time);
        $month = date('n', $time);
        $scopes = $this->_getScopesByPermission($data['permission']);
        if (!$scopes)
        {
            return false;
        }
        //更新有效年份和月份
        $this->_updateYears($uid, $year);
        $this->_updateMonths($uid, $year, $month);

        foreach ($scopes as $scope)
        {
            $indexKey = $this->_getTimelineKey($uid, $scope, $time);
            $actionStatus = $this->_doAction($indexKey, $time, $tid);
            if ($actionStatus === false)
            {
                //log
            }
        }
        return true;
    }
	
    /**
     * 更新有效年份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $plus 年份中的内容是增加还是减少: true 增加, false 减少
     * @return type
     */
    private function _updateYears($uid, $year, $plus = true)
    {
    	$key = $this->_getYearKey($uid);
    	$has = $this->redis->hExists($key, $year);
    	if ($has)
        {
    		if ($plus)
            {
    			$this->redis->hIncrBy($key, $year, 1);
    		} else 
            {
    			$hasYear = $this->redis->exists($this->_getYearHashKey($uid, $year));
    			if ($hasYear) {
    				$this->redis->hIncrBy($key, $year, -1);
    			} else {
    				$this->redis->hDel($key, $year);
    			}
    		}
    	} else
        {
    		$this->redis->hSet($key, $year, 1);
    	}
        return true;
    }
    
    /**
     * 更新年份下有效的月份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $month 月份
     * @param type $is_plus 月份中的内容是增加还是减少: true 增加, false 减少
     * @return type
     */
    private function _updateMonths($uid, $year, $month, $plus = true)
    {
        $key = $this->_getYearHashKey($uid, $year);
    	if ($this->redis->hExists($key, $month))
        {
    		if ($plus) {
    			$this->redis->hIncrBy($key, $month, 1);
    		} else {
    			$hasMonth = $this->_existsMonthOfYear($uid, $year, $month);
    			if ($hasMonth) {
    				$this->redis->hIncrBy($key, $month, -1);
    			} else {
    				$this->redis->hDel($key, $month);
    			}
    		}
    	}else
        {
    		$this->redis->hSet($key, $month, 1);
    	}
         return true;
    }
    
    /**
     * 判断年份下是否存在月份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $month 月份
     */
    private function _existsMonthOfYear($uid, $year, $month)
    {
    	$scopes = array('self', 'friend', 'custom');
    	foreach ($scopes as $scope)
        {
    		$hasMonth = $this->redis->exists($this->_getTimelineKey($uid, $scope, $year.$month, false));
    		if ($hasMonth)
            {
    			return true;
    		}
    	}
    	return false;
    }
    
    /**
     * @author fbbin
     * @desc 获取时间线key
     * @param string $uid
     * @param string $scope
     * @param string $time
     * @param string $tid
     */
    private function _getTimelineKey($uid, $scope, $time, $formateTime = true)
    {
        if (!$formateTime)
        {
            return 'timeaxis:' . $uid . ':' . $scope . ':' . $time;
        }
        return 'timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . date('n', $time);
    }

    /**
     * @author fbbin
     * @desc 获取用户的年时间key
     * @param string $uid
     */
    private function _getYearKey($uid)
    {
        return 'timeline:' . $uid . ':years';
    }

    /**
     * @author fbbin
     * @desc 获取用户的年时间hash key
     * @param string $uid
     * @param string $year
     */
    private function _getYearHashKey($uid, $year)
    {
        return 'timeline:' . $uid . ':' . $year;
    }

    /**
     * @author fbbin
     * @desc 执行时间线数据的具体操作
     * @param string $indexKey
     * @param string $tid
     * @param string $set 增加|删除
     */
    private function _doAction($indexKey, $time, $tid, $set = true)
    {
        if ( $set )
        {
            return $this->redis->zAdd($indexKey, $time, $tid);
        }
        else
        {
            return $this->redis->zDelete($indexKey, $tid);
        }
    }
	
    /**
	 * @author fbbin
	 * @desc 根据设定的权限值获取所在圈子
	 * @param int $permission
	 */
    private function _getScopesByPermission($permission)
    {
        switch ($permission)
        {
            case self::PER_CUSTOM: //自定义
                return array('custom');
                break;
            case self::PER_OPEN: //公开
                return array('public', 'follower', 'friend');
                break;
            case self::PER_FANS: //粉丝
                return array('follower', 'friend');
                break;
            case self::PER_FRIS: //好友
                return array('friend');
                break;
            case self::PER_SELF: //仅自己
                return array('self');
                break;
            default:
                return array();
        }
    }

}