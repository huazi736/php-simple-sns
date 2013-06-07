<?php

/**
 * 网页 Axis Model 时间轴模型
 * Created on 2012-4-5
 * @author shedequan
 */
class WebpageAxisModel extends RedisModel {

    public $timer;

    public function __construct() {
        parent::__construct();
        $this->timer = new CustomTimeModel();
    }

    public function test() {
        return 'hello axis';
    }

    public function createPoint($data) {
        $pid = $data['pid'];
        $tid = $data['tid'];
        $type = $data['type'];
        $time = $data['ctime'];
        $year = $this->timer->parseTime($time, 'Y');
        $month = $this->timer->parseTime($time, 'n');

        //更新有效年份和月份
        $webpageline_model = new WebpageLineModel();
        $webpageline_model->updateYears($pid, $year);
        $webpageline_model->updateMonths($pid, $year, $month);

        $webtopic_model = new WebtopicModel();
        $res = $this->set($pid, $time, $tid);
        if ($res === false) {
            return false;
        }

        if ($webtopic_model->checkSpecialType($type)) {
            $this->addMainPoint($pid, $time, $tid);
        } else if ($year < 0) {
            $this->addTopPoint($pid, $time, $tid);
        }

        $this->buildTopPoints($pid, $time);

        return true;
    }

    public function deletePoint($tid) {
        //获取Topic信息
        $topic = $this->_redis->hMGet('Webtopic:' . $tid, array('pid', 'ctime'));
        $uid = $topic['pid'];
        $ctime = $topic['ctime'];
        $year = $this->timer->parseTime($ctime, 'Y');
        $month = $this->timer->parseTime($ctime, 'n');

        //删除Main Point
        $this->deleteMainPoint($uid, $year, $tid);

        //删除Top Point
        $this->deleteTopPoint($uid, $year, $tid);

        //删除Hot Point
        $this->deleteHotPoint($uid, $year, $tid);

        //删除常规的时间轴Point
        $res = $this->delete($uid, $ctime, $tid);
        if ($res === false) {
            return false;
        }
        
        $this->buildTopPoints($uid, $ctime);

        //更新有效年份和月份
        $webpageline_model = new WebpageLineModel();
        $webpageline_model->updateMonths($uid, $year, $month, false);
        $webpageline_model->updateYears($uid, $year, false);

        return true;
    }

    public function updatePoint($tid, $time) {
        //获取Topic信息，根据过去的时间进行年月的定位
        $topic = $this->_redis->hMGet('Webtopic:' . $tid, array('pid', 'ctime', 'type'));
        $uid = $topic['pid'];
        $ctime = $topic['ctime'];
        $type = $topic['type'];

        $old_year = $this->timer->parseTime($ctime, 'Y');
        $old_month = $this->timer->parseTime($ctime, 'n');
        $year = $this->timer->parseTime($time, 'Y');
        $month = $this->timer->parseTime($time, 'n');

        $webpageline_model = new WebpageLineModel();
        if ($year . $month != $old_year . $old_month) {
            $this->delete($uid, $ctime, $tid);

            //更新有效月份
            $webpageline_model->updateMonths($uid, $old_year, $old_month, false);
            $webpageline_model->updateMonths($uid, $year, $month);
        }

        if ($year != $old_year) {
            //更新有效年份
            $webpageline_model->updateYears($uid, $old_year, false);
            $webpageline_model->updateYears($uid, $year);
        }

        $webtopic_model = new WebtopicModel();
        if ($webtopic_model->checkSpecialType($type)) {
            //更新特别年度热点索引
            $this->updateMainPoint($uid, $old_year, $year, $time, $tid);
        }

        //更新常规的时间轴Point
        $res = $this->set($uid, $time, $tid);
        if ($res === false) {
            return false;
        }

        if ($year > 0) {
            //清除 Top Point
            $this->deleteTopPoint($uid, $old_year, $tid);
        } else {
            //更新 Top Point
            $this->updateTopPoint($uid, $old_year, $time, $tid);
        }
        
        //清除 Hot Point
        $this->deleteHotPoint($uid, $old_year, $tid);
        //重建 Top Points
        $this->buildTopPoints($uid, $time, $tid);

        return true;
    }

    public function buildTopPoints($uid, $time, $update_tid = false) {
        //生成Top Point的年份
        $year = floatval($this->timer->parseTime($time, 'Y'));
        //获取每月热点的数量
        $top_num = 1;

        //获取月份的热点需要的参数
        $sort_params = array(
            'by' => 'Webtopic:*->hot',
            'limit' => array(0, $top_num),
            'get' => array('Webtopic:*->tid', 'Webtopic:*->ctime', 'Webtopic:*->type'),
            'sort' => 'desc',
            'alpha' => true,
        );

        //使用排序生成年份下的top集合，不使用排序生成
        if ($year > 0) {
            //删除月份过期的热点
            $this->_redis->delete('webpage:timeaxis:' . $uid . ':' . $year . ':top');
            $webtopic_model = new WebtopicModel();
            for ($month = 1; $month <= 12; $month++) {
                //获取月份的新热点
                $arr_hot = $this->_redis->sort('webpage:timeaxis:' . $uid . ':' . $year . ':' . $month, $sort_params);
                //更新月份的热点
                for ($i = 0; $i < count($arr_hot); $i += 3) {
                    $tid = $arr_hot[$i];
                    $ctime = $arr_hot[$i + 1];
                    //对特定tid进行时间更新
                    if ($update_tid && $update_tid == $tid) {
                        $ctime = $time;
                    }
                    $type = $arr_hot[$i + 2];
                    if ($webtopic_model->checkSpecialType($type)) {
                        continue;
                    }
                    $this->addTopPoint($uid, $ctime, $tid);
                }
            }
        }

        $this->buildHotPoints($uid, $year);
    }

    public function buildHotPoints($uid, $year) {
        //合并Main和Top到Hot中
        $key_main = 'webpage:timeaxis:' . $uid . ':' . $year . ':main';
        $key_top = 'webpage:timeaxis:' . $uid . ':' . $year . ':top';
        $this->_redis->delete('webpage:timeaxis:' . $uid . ':' . $year . ':hot');
        $this->_redis->zUnion('webpage:timeaxis:' . $uid . ':' . $year . ':hot', array($key_main, $key_top), array(1, 1), 'min');
    }

    //删除热点列表中指定的 Topic
    public function deleteHotPoint($uid, $year, $tid) {
        return $this->_redis->zDelete('webpage:timeaxis:' . $uid . ':' . $year . ':hot', $tid);
    }

    public function addMainPoint($uid, $time, $tid) {
        $year = $this->timer->parseTime($time, 'Y');
        return $this->_redis->zAdd('webpage:timeaxis:' . $uid . ':' . $year . ':main', $time, $tid);
    }

    public function deleteMainPoint($uid, $year, $tid) {
        return $this->_redis->zDelete('webpage:timeaxis:' . $uid . ':' . $year . ':main', $tid);
    }

    public function updateMainPoint($uid, $old_year, $year, $time, $tid) {
        if ($year != $old_year) {
            $this->deleteMainPoint($uid, $old_year, $tid);
        }
        return $this->_redis->zAdd('webpage:timeaxis:' . $uid . ':' . $year . ':main', $time, $tid);
    }

    public function addTopPoint($uid, $time, $tid) {
        $year = $this->timer->parseTime($time, 'Y');
        return $this->_redis->zAdd('webpage:timeaxis:' . $uid . ':' . $year . ':top', $time, $tid);
    }

    public function deleteTopPoint($uid, $year, $tid) {
        return $this->_redis->zDelete('webpage:timeaxis:' . $uid . ':' . $year . ':top', $tid);
    }

    public function updateTopPoint($uid, $old_year, $time, $tid) {
        $year = $this->timer->parseTime($time, 'Y');
        $res = $this->deleteTopPoint($uid, $old_year, $tid);
        if (is_int($res) && $res == 1) {
            return $this->_redis->zAdd('webpage:timeaxis:' . $uid . ':' . $year . ':top', $time, $tid);
        }
        return $res;
    }

    private function set($pid, $time, $tid) {
        $year = $this->timer->parseTime($time, 'Y');
        $month = $this->timer->parseTime($time, 'n');
        $time = floatval($time);
        return $this->_redis->zAdd('webpage:timeaxis:' . $pid . ':' . $year . ':' . $month, $time, $tid);
    }

    private function delete($uid, $time, $tid) {
        $year = $this->timer->parseTime($time, 'Y');
        $month = $this->timer->parseTime($time, 'n');
        return $this->_redis->zDelete('webpage:timeaxis:' . $uid . ':' . $year . ':' . $month, $tid);
    }

}