<?php
/**
 * 网页 Axis Model 时间轴模型
 * Created on 2012-4-5
 * @author shedequan,fbbin
 */
class WebAxisModel extends DkModel
{

    public function __initialize()
    {
        $this->init_redis();
    }

    /**
     * @author fbbin
     * @desc 创建一个时间线节点数据
     * @param array $data
     */
    public function createPoint( array $data)
    {
        $pid = $data['pid'];
        $tid = $data['tid'];
        $time = $data['ctime'];
        $year = $this->_getYearOfTime($time);
        $month = $this->_getMonthOfTime($time);

        //更新有效年份和月份
        $this->_updateYears($pid, $year);
        $this->_updateMonths($pid, $year, $month);

        $doStatus = $this->_doAction($pid, $time, $tid);
        if ($doStatus === false)
        {
            return false;
        }
        //设置网页时间别名
        if (isset($data['timedesc']) && !empty($data['timedesc']))
        {
            $this->_doDateAlias($pid, $year, $data['timedesc']);
        }
        
        return true;
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
        $topic = $this->redis->hMGet('webtopic:' . $tid, array('pid', 'ctime', 'timedesc'));
        if( !$topic )
        {
        	return false;
        }
        extract($topic);
        $year = $this->_getYearOfTime($ctime);
        $month = $this->_getMonthOfTime($ctime);

        //删除常规的时间轴Point
        $doStatus = $this->_doAction($pid, $ctime, $tid, false);
        if ($doStatus === false)
        {
            return false;
        }
        //更新有效年份和月份
        $this->_updateMonths($pid, $year, $month, false);
        $this->_updateYears($pid, $year, false);
        //取消网页时间别名
        if (!empty($timedesc))
        {
            $this->_doDateAlias($pid, $year, '', false);
        }
        return true;
    }

	/**
	 * @author fbbin
	 * @desc更新一个数据节点
	 * @param string $tid
	 * @param string $time
	 */
	public function updatePoint($tid, $time) {
		if( ! $tid ) {
			return DKBase::status(false, 'update_param_err', 120301);
		}
		$topic = $this->redis->hMGet('webtopic:' . $tid, array('pid', 'ctime'));
		if( !$topic ) {
			return DKBase::status(false, 'topic_not_exists', 120305);
		}
		extract($topic);
		$oldYear = $this->_getYearOfTime($ctime);
		$oldMonth = $this->_getMonthOfTime($ctime);
		$year = $this->_getYearOfTime($time);
		$month = $this->_getMonthOfTime($time);

		if ($year . $month != $oldYear . $oldMonth) {
			$this->_doAction($pid, $ctime, $tid, false);
			//更新有效月份
			$this->_updateMonths($pid, $oldYear, $oldMonth, false);
			$this->_updateMonths($pid, $year, $month);
		}
		if ($year != $oldYear) {
			//更新有效年份
			$this->_updateYears($pid, $oldYear, false);
			$this->_updateYears($pid, $year);
		}
		//更新常规的时间轴Point
		$doStatus = $this->_doAction($pid, $time, $tid);
		if ($doStatus === false) {
			return false;
		}
		return true;
	}

    /**
     * @author fbbin
     * @desc 删除一个网页的所有时间别名
     * @param string $pid
     */
    public function delAllDateAlias($pid)
    {
        $key = $this->_getDateAliasKey($pid);
        $this->redis->del($key);
        return true;
    }

    /**
     * @author fbbin
     * @desc 设置一个网页的时间别名
     * @param string $tid
     * @param string $time
     * @param string $alias
     * @param bool $action
     */
    private function _doDateAlias($pid, $time, $alias, $action = true )
    {
        $key = $this->_getDateAliasKey($pid);
        // $time = $this->_getYearOfTime($time);
        if( $action )
        {
            return $this->redis->hSet($key, $time, $alias);
        }
        else
        {
            return $this->redis->hDel($key, $time);
        }
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
        $key = $this->_getYearHashKey($uid);
        if ( $this->redis->hExists($key, $year) )
        {
            if ($plus) {
                $this->redis->hIncrBy($key, $year, 1);
            } else {
                $hasYear = $this->redis->exists($this->_getYearIndexKey($uid, $year));
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
    }

    /**
     * 更新年份下有效的月份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $month 月份
     * @param type $plus 月份中的内容是增加还是减少: true 增加, false 减少
     * @return type 
     */
    private function _updateMonths($uid, $year, $month, $plus = true)
    {
        $key = $this->_getYearIndexKey($uid, $year);
        if ( $this->redis->hExists($key, $month) )
        {
            if ($plus) {
                $this->redis->hIncrBy($key, $month, 1);
            } else {
                $hasMonth = $this->redis->exists($this->_getMonthIndexKey($uid, $year, $month));
                if ($hasMonth) {
                    $this->redis->hIncrBy($key, $month, -1);
                } else {
                    $this->redis->hDel($key, $month);
                }
            }
        } else 
        {
            $this->redis->hSet($key, $month, 1);
        }
    }

    /**
     * @author fbbin
     * @desc 获取时间线操作key
     * @param string $pid
     * @param string $time
     */
    private function _getAxisIndexKey($pid, $time)
    {
    	$year = $this->_getYearOfTime($time);
    	$month = $this->_getMonthOfTime($time);
    	return 'webaxis:' . $pid . ':' . $year . ':' . $month;
    }

    /**
     * @author fbbin
     * @desc 获取时间线年份操作key
     * @param string $uid
     * @param string $year
     */
    private function _getYearIndexKey($pid, $year)
    {
        return 'webline:' . $pid . ':' . $year;
    }

    /**
     * @author fbbin
     * @desc 获取时间线月份操作key
     * @param string $uid
     * @param string $year
     */
    private function _getMonthIndexKey($pid, $year, $month)
    {
        return 'webaxis:' . $pid . ':' . $year . ':' . $month;
    }

    /**
     * @author fbbin
     * @desc 获取时间线年份操作key
     * @param string $uid
     * @param string $year
     */
    private function _getYearHashKey($pid)
    {
        return 'webline:' . $pid . ':years';
    }

    /**
     * @author fbbin
     * @desc 获取网页时间别名hash key
     * @param string $pid
     */
    private function _getDateAliasKey($pid)
    {
        return 'datealias:' . $pid;
    }

    /*
     * @author fbbin
     * @desc 返回指定时间的年份
     * @param string $time
     */
    private function _getYearOfTime($time)
    {
    	return formatTime(parseTime($time), 'Y');
    }
    
    /*
     * @author fbbin
    * @desc 返回指定时间的月份
    * @param string $time
    */
    private function _getMonthOfTime($time)
    {
    	return formatTime(parseTime($time), 'n');
    }
    
    /**
     * @author fbbin
     * @desc 执行时间线数据的具体操作
     * @param string $uid
     * @param string $time
     * @param string $tid
     * @param bool $set
     */
    private function _doAction($pid, $time, $tid, $set = true)
    {
    	if( $set )
    	{
    		return $this->redis->zAdd($this->_getAxisIndexKey($pid, $time), floatval($time), $tid);
    	}
    	else
    	{
    		return $this->redis->zDelete($this->_getAxisIndexKey($pid, $time), $tid);
    	}
    }
    
}
