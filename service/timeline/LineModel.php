<?php

/**
 * Line Model 时间线模型
 * Created on 2012-3-12
 * @author shedequan
 */
class LineModel extends RedisModel {

    public function test() {
        return 'hello line';
    }

    /**
     * 更新有效年份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $plus 年份中的内容是增加还是减少: true 增加, false 减少
     * @return type 
     */
    public function updateYears($uid, $year, $plus = true) {
        $key = 'timeline:' . $uid . ':years';
        $has = $this->_redis->hExists($key, $year);
        if ($has) {
            if ($plus) {
                $this->_redis->hIncrBy($key, $year, 1);
            } else {
                $has_year = $this->_redis->exists('timeline:' . $uid . ':' . $year);
                if ($has_year) {
                    $this->_redis->hIncrBy($key, $year, -1);
                } else {
                    $this->_redis->hDel($key, $year);
                }
            }
        } else {
            $this->_redis->hSet($key, $year, 1);
        }
    }

    /**
     * 更新年份下有效的月份
     * @param type $uid 用户ID
     * @param type $year 年份
     * @param type $month 月份
     * @param type $is_plus 月份中的内容是增加还是减少: true 增加, false 减少
     * @return type 
     */
    public function updateMonths($uid, $year, $month, $plus = true) {
        $key = 'timeline:' . $uid . ':' . $year;
        $has = $this->_redis->hExists($key, $month);
        if ($has) {
            if ($plus) {
                $this->_redis->hIncrBy($key, $month, 1);
            } else {
                $has_month = $this->existsMonthOfYear($uid, $year, $month);
                if ($has_month) {
                    $this->_redis->hIncrBy($key, $month, -1);
                } else {
                    $this->_redis->hDel($key, $month);
                }
            }
        } else {
            $this->_redis->hSet($key, $month, 1);
        }
    }

    public function existsMonthOfYear($uid, $year, $month) {
        $scopes = array('self', 'friend', 'custom');
        foreach ($scopes as $scope) {
            $has_month = $this->_redis->exists('timeaxis:' . $uid . ':' . $scope . ':' . $year . $month);
            if ($has_month) {
                return true;
            }
        }
        return false;
    }

}