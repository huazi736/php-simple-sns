<?php

/**
 * Axis Model 时间轴模型
 * Created on 2012-3-5
 * @author shedequan
 */
class AxisModel extends DkModel
{

    public function __initialize()
    {
        $this->init_redis();
    }
    
    public function test() {
        return 'hello axis';
    }

    public function deletePoint($tid) {
        //获取Topic信息
        $topic = $this->_redis->hMGet('Topic:' . $tid, array('uid', 'ctime', 'permission'));
        $uid = $topic['uid'];
        $ctime = $topic['ctime'];
        $permission = $topic['permission'];
        $year = date('Y', $ctime);
        $month = date('n', $ctime);

        $scopes = $this->getScopesByPermission($permission);
        foreach ($scopes as $scope) {
            //删除Main Point
            $this->deleteMainPoint($uid, $scope, $year, $tid);

            //删除Top Point
            $this->deleteTopPoint($uid, $scope, $year, $tid);

            //删除Hot Point
            $this->deleteHotPoint($uid, $scope, $year, $tid);

            //删除常规的时间轴Point
            $res = $this->delete($uid, $scope, $ctime, $tid);
            if ($res === false) {
                return false;
            }
            
            //重建Top Points
            $this->buildTopPoints($uid, $scope, $ctime, $tid);
        }

        //更新有效年份和月份
        $line_model = new LineModel();
        $line_model->updateMonths($uid, $year, $month, false);
        $line_model->updateYears($uid, $year, false);

        return true;
    }

    public function updatePoint($tid, $time) {
        //获取Topic信息，根据过去的时间进行年月的定位
        $topic = $this->_redis->hMGet('Topic:' . $tid, array('uid', 'ctime', 'type', 'permission'));
        $uid = $topic['uid'];
        $ctime = $topic['ctime'];
        $type = $topic['type'];
        $permission = $topic['permission'];
        $old_year = date('Y', $ctime);
        $old_month = date('n', $ctime);
        $year = date('Y', $time);
        $month = date('n', $time);
        $scopes = $this->getScopesByPermission($permission);
        $line_model = new LineModel();

        if ($year . $month != $old_year . $old_month) {
            foreach ($scopes as $scope) {
                $this->delete($uid, $scope, $ctime, $tid);
            }

            //更新有效月份
            $line_model->updateMonths($uid, $old_year, $old_month, false);
            $line_model->updateMonths($uid, $year, $month);
        }
        if ($year != $old_year) {
            //更新有效年份
            $line_model->updateYears($uid, $old_year, false);
            $line_model->updateYears($uid, $year);
        }

        $topic_model = new TopicModel();
        foreach ($scopes as $scope) {
            if ($topic_model->checkSpecialType($type)) {
                //更新特别年度热点索引
                $this->updateMainPoint($uid, $scope, $old_year, $year, $time, $tid);
            }

            //更新常规的时间轴Point
            $res = $this->set($uid, $scope, $time, $tid);
            if ($res === false) {
                return false;
            }

            //清除 Top,Hot Points
            $this->deleteTopPoint($uid, $scope, $old_year, $tid);
            $this->deleteHotPoint($uid, $scope, $old_year, $tid);
            //重建 Top Points
            $this->buildTopPoints($uid, $scope, $time, $tid);
        }
        return true;
    }

    public function createPoint($data) {
        $uid = $data['uid'];
        $tid = $data['tid'];
        $type = $data['type'];
        $time = $data['ctime'];
        $year = date('Y', $time);
        $month = date('n', $time);
        $permission = $data['permission'];
        $scopes = $this->getScopesByPermission($permission);
        if (!$scopes) {
            return false;
        }

        //更新有效年份和月份
        $line_model = new LineModel();
        $line_model->updateYears($uid, $year);
        $line_model->updateMonths($uid, $year, $month);

        $topic_model = new TopicModel();
        foreach ($scopes as $scope) {
            $res = $this->set($uid, $scope, $time, $tid);
            if ($res === false) {
                return false;
            }

            if ($topic_model->checkSpecialType($type)) {
                $this->addMainPoint($uid, $scope, $time, $tid);
            }
            $this->buildTopPoints($uid, $scope, $time);
        }
        return true;
    }

    public function buildTopPoints($uid, $scope, $time, $update_tid = false) {
        //生成Top Point的年份
        $year = date('Y', $time);
        //获取每月热点的数量
        $top_num = 1;

        //获取月份的热点需要的参数
        $sort_params = array(
            'by' => 'Topic:*->hot',
            'limit' => array(0, $top_num),
            'get' => array('Topic:*->tid', 'Topic:*->ctime', 'Topic:*->type'),
            'sort' => 'desc',
            'alpha' => true,
        );

        //删除月份过期的热点
        $this->_redis->delete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':top');

        $topic_model = new TopicModel();
        for ($month = 1; $month <= 12; $month++) {
            //获取月份的新热点
            $arr_hot = $this->_redis->sort('timeaxis:' . $uid . ':' . $scope . ':' . $year . $month, $sort_params);
            //更新月份的热点
            for ($i = 0; $i < count($arr_hot); $i += 3) {
                $tid = $arr_hot[$i];
                $ctime = $arr_hot[$i + 1];
                //对特定tid进行时间更新
                if ($update_tid && $update_tid == $tid) {
                    $ctime = $time;
                }
                $type = $arr_hot[$i + 2];
                if ($topic_model->checkSpecialType($type)) {
                    continue;
                }
                $this->addTopPoint($uid, $scope, $ctime, $tid);
            }
        }

        $this->buildHotPoints($uid, $scope, $year);
    }

    public function buildHotPoints($uid, $scope, $year) {
        //合并Main和Top到Hot中
        $key_main = 'timeaxis:' . $uid . ':' . $scope . ':' . $year . ':main';
        $key_top = 'timeaxis:' . $uid . ':' . $scope . ':' . $year . ':top';
        $this->_redis->delete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':hot');
        $this->_redis->zUnion('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':hot', array($key_main, $key_top), array(1, 1), 'min');
    }

    //删除热点列表中指定的 Topic
    public function deleteHotPoint($uid, $scope, $year, $tid) {
        return $this->_redis->zDelete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':hot', $tid);
    }

    public function addMainPoint($uid, $scope, $time, $tid) {
        return $this->_redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . ':main', $time, $tid);
    }

    public function deleteMainPoint($uid, $scope, $year, $tid) {
        return $this->_redis->zDelete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':main', $tid);
    }

    public function updateMainPoint($uid, $scope, $old_year, $year, $time, $tid) {
        if ($year != $old_year) {
            $this->deleteMainPoint($uid, $scope, $old_year, $tid);
        }
        return $this->_redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':main', $time, $tid);
    }

    public function addTopPoint($uid, $scope, $time, $tid) {
        return $this->_redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . ':top', $time, $tid);
    }

    public function deleteTopPoint($uid, $scope, $year, $tid) {
        return $this->_redis->zDelete('timeaxis:' . $uid . ':' . $scope . ':' . $year . ':top', $tid);
    }

    public function updateTopPoint($uid, $scope, $old_year, $time, $tid) {
        $res = $this->deleteTopPoint($uid, $scope, $old_year, $tid);
        if (is_int($res) && $res == 1) {
            return $this->_redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . ':top', $time, $tid);
        }
        return $res;
    }

    private function set($uid, $scope, $time, $tid) {
        return $this->_redis->zAdd('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . date('n', $time), $time, $tid);
    }

    private function delete($uid, $scope, $time, $tid) {
        return $this->_redis->zDelete('timeaxis:' . $uid . ':' . $scope . ':' . date('Y', $time) . date('n', $time), $tid);
    }

    private function getScopesByPermission($permission) {
        //未设置权限，默认为公开
        $permission = isset($permission) ? (int) $permission : 1;
        switch ($permission) {
            case -1: //自定义
                return array('custom');
                break;
            case 1: //公开
                return array('public', 'follower', 'friend');
                break;
            case 3: //粉丝
                return array('follower', 'friend');
                break;
            case 4: //好友
                return array('friend');
                break;
            case 8: //仅自己
                return array('self');
                break;
            default:
                return false;
        }
    }

}