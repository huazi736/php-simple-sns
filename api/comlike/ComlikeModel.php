<?php

class ComlikeModel extends DkModel {

    public function __initialize() {
        $this->init_db('system');
        $this->init_redis();
    }

    /**
     * 将待显示的时间智能转换
     * @author wangqiang
     * @param int $sTime 待显示的时间
     * @return string $str 智能时间显示
     */
    function tran_time($sTime) {
        $time = time() - $sTime;
        if ($time < 0) {
            $str = "错误的时间！";
        } elseif ($time < 3) {
            $str = '刚刚';
        } elseif ($time < 60) {
            $str = $time . "秒前";
        } elseif ($time < 60 * 60) {
            $min = floor($time / 60);
            $str = $min . "分钟前";
        } elseif ($time < 60 * 60 * 24) {
            $h = round($time / (60 * 60));
            $str = $h . "小时前";
        } else {
            $time_array = getdate($sTime);
            $hours = $time_array['hours'];
            $minutes = $time_array['minutes'];
            if ($minutes < 10)
                $minutes = '0' . $minutes;
            $seconds = $time_array['seconds'];
            if ($seconds < 10)
                $seconds = '0' . $seconds;
            $month = $time_array['mon'];
            $day = $time_array['mday'];
            $year = $time_array['year'];
            $str = $year . "年" . $month . "月" . $day . "日" . $hours . ":" . $minutes;
        }

        return $str;
    }
}