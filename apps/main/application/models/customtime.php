<?php

class CustomTimeModel {

    /**
     * 生成自定义时间
     * 月，日，小时，分钟，秒的长度为2个字符
     * @param type $year 年，必填
     * @param type $month 月
     * @param type $day 日
     * @param type $hour 小时
     * @param type $minute 分钟
     * @param type $second 秒
     * @return type 
     */
    public function makeTime($year, $month = '01', $day = '01', $hour = '00', $minute = '00', $second = '00') {
        if (strlen($month . $day . $hour . $minute . $second) != 10) {
            return false;
        }

        if ($year >= 1) {
            return $year . $month . $day . $hour . $minute . $second;
        } elseif ($year < 0) {
            return $year . $month . $day;
        } else {
            return false;
        }
    }

    /**
     * 解析时间字符串，获取年、月、日、时、分、秒等信息
     * @param type $strtime 时间字符串
     * @return mix 
     */
    public function parseTime($strtime) {
        $time = floatval($strtime);
        $rev_strtime = strrev($strtime);

        if ($time > 0) {
            $datetime = array(
                'year' => strrev(substr($rev_strtime, 10, strlen($strtime) - 10)),
                'month' => strrev(substr($rev_strtime, 8, 2)),
                'month2' => intval(strrev(substr($rev_strtime, 8, 2))),
                'day' => strrev(substr($rev_strtime, 6, 2)),
                'hour' => strrev(substr($rev_strtime, 4, 2)),
                'minute' => strrev(substr($rev_strtime, 2, 2)),
                'second' => strrev(substr($rev_strtime, 0, 2)),
            );
        } elseif ($time < 0) {
            $datetime = array(
                'year' => strrev(substr($rev_strtime, 4, strlen($strtime) - 4)),
                'month' => strrev(substr($rev_strtime, 2, 2)),
                'month2' => intval(strrev(substr($rev_strtime, 2, 2))),
                'day' => strrev(substr($rev_strtime, 0, 2)),
            );
        } else {
            return false;
        }
        return $datetime;
    }

    /**
     * 格式化时间
     * @param array $datetime 时间信息
     * @param string $format 格式化模板
     * @return string 
     */
    public function formatTime($datetime, $format = false) {
        if (isset($datetime['hour']) && isset($datetime['minute']) && isset($datetime['second'])) {
            //Default Format
            $format = $format == false ? 'Y/m/d H:i:s' : $format;
            //时间格式化的映射 Y m d H i s
            $maps = array('Y' => 'year', 'm' => 'month', 'n' => 'month2', 'd' => 'day', 'H' => 'hour', 'i' => 'minute', 's' => 'second');
        } else {
            //Default Format
            $format = $format == false ? 'Y/m/d' : $format;
            //时间格式化的映射 Y m d H i s
            $maps = array('Y' => 'year', 'm' => 'month', 'n' => 'month2', 'd' => 'day');
        }

        //统计执行格式化信息
        $map_values = array();
        foreach ($maps as $key => $map) {
            $map_values[$key] = $datetime[$map];
        }
        //格式化信息并返回
        return str_replace(array_keys($maps), $map_values, $format);
    }

    public function makeFriendlyTime($strtime) {
        $datetime = $this->parseTime($strtime);
        $year = floatval($datetime['year']);

        if ($year < 0) {
            $year = 0 - $year;
            $start = '公元前';
            if ($year >= 10000 && $year <= 99990000) {
                $num = $year / 10000;
                $end = '万年';
            } else if ($year >= 100000000 && $year <= 999900000000) {
                $num = $year / 100000000;
                $end = '亿年';
            } else {
                $num = $year;
                $end = '年';
            }
            return $start . $num . $end;
        } else if ($year > 0) {
            $now = time();
            if ($datetime['year'] == date('Y', $now)) {
                $inhand = strtotime($this->formatTime($datetime, 'Ymd H:i:s'));
                $seconds = $now - $inhand;
                $abs_seconds = abs($seconds);
                if ($seconds >= 0) {
                    //before
                    return $this->expressBefore($datetime, $abs_seconds);
                } else if ($seconds < 0) {
                    //after
                    return $this->expressAfter($datetime, $abs_seconds);
                }
            } else {
                if (($datetime['hour'] . $datetime['hour'] . $datetime['hour']) == '000000') {
                    return $this->formatTime($datetime, 'Y/m/d');
                }
                return $this->formatTime($datetime);
            }
        }
        return false;
    }

    public function expressBefore($datetime, $seconds) {
        if ($seconds > 0 && $seconds < 60) {
            //秒
            $num = $seconds;
            $end = '秒前';
        } else if ($seconds >= 60 && $seconds < 3600) {
            //分钟
            $num = intval($seconds / 60);
            $end = '分钟前';
        } else if ($seconds >= 3600 && $seconds < 86400) {
            //小时
            $num = intval($seconds / 3600);
            $end = '小时前';
        } else if ($seconds >= 86400 && $seconds < 172800) {
            //昨天
            $num = '';
            $end = '昨天 ' . $this->formatTime($datetime, 'H:i');
        } else if ($seconds >= 172800 && $seconds < 259200) {
            //前天
            $num = '';
            $end = '前天 ' . $this->formatTime($datetime, 'H:i');
        } else {
            return $this->formatTime($datetime);
        }
        return $num . $end;
    }

    public function expressAfter($datetime, $seconds) {
        if ($seconds > 0 && $seconds < 60) {
            //秒
            $num = $seconds;
            $end = '秒后';
        } else if ($seconds >= 60 && $seconds < 3600) {
            //分钟
            $num = intval($seconds / 60);
            $end = '分钟后';
        } else if ($seconds >= 3600 && $seconds < 86400) {
            //小时 今天
            $num = intval($seconds / 3600);
            if ($num > 3) {
                $num = '';
                $end = '今天';
            } else {
                $end = '小时后';
            }
        } else if ($seconds >= 86400 && $seconds < 172800) {
            //明天
            $num = '';
            $end = '明天 ' . $this->formatTime($datetime, 'H:i');
        } else if ($seconds >= 172800 && $seconds < 259200) {
            //后天
            $num = '';
            $end = '天 ' . $this->formatTime($datetime, 'H:i');
        } else {
            return $this->formatTime($datetime);
        }
        return $num . $end;
    }

}