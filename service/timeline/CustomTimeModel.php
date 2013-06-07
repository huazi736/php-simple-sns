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

        if ($year > 0 || $year < 0) {
            return $year . $month . $day . $hour . $minute . $second;
        } else {
            return false;
        }

//        if ($year >= 1) {
//            return $year . $month . $day . $hour . $minute . $second;
//        } elseif ($year < 0) {
//            return $year . $month . $day;
//        } else {
//            return false;
//        }
    }

    /**
     * 解析自定义格式的时间字符串
     * @param type $strtime 时间字符串
     * @param type $format 可自定义的格式化模板，默认：公元前 Y/m/d H:i:s 公元后 'Y/m/d'
     * @return type 
     */
    public function parseTime($strtime, $format = false) {
        $time = floatval($strtime);
        $rev_strtime = strrev($strtime);

        $datetime = array(
            'year' => strrev(substr($rev_strtime, 10, strlen($strtime) - 10)),
            'month' => strrev(substr($rev_strtime, 8, 2)),
            'month2' => intval(strrev(substr($rev_strtime, 8, 2))),
            'day' => strrev(substr($rev_strtime, 6, 2)),
            'hour' => strrev(substr($rev_strtime, 4, 2)),
            'minute' => strrev(substr($rev_strtime, 2, 2)),
            'second' => strrev(substr($rev_strtime, 0, 2)),
        );

        if ($time < 0) {
            $datetime['bc'] = 1;
        } elseif ($time > 0) {
            $datetime['bc'] = 0;
        } else {
            return false;
        }

        if (isset($datetime['bc']) && $datetime['bc']) {
            //公元前
            $format = $format == false ? 'Y/m/d' : $format;
            //时间格式化的映射 Y m d H i s
            $maps = array('Y' => 'year', 'm' => 'month', 'n' => 'month2', 'd' => 'day');
        } else {
            //公元
            if ($format === false) {
                $format = $datetime['hour'] . $datetime['minute'] == '0000' ? 'Y年m月d日' : 'Y年m月d日H:i';
            }
            //时间格式化的映射 Y m d H i s
            $maps = array('Y' => 'year', 'm' => 'month', 'n' => 'month2', 'd' => 'day', 'H' => 'hour', 'i' => 'minute', 's' => 'second');
        }

//        if ($time > 0) {
//            $datetime = array(
//                'year' => strrev(substr($rev_strtime, 10, strlen($strtime) - 10)),
//                'month' => strrev(substr($rev_strtime, 8, 2)),
//                'month2' => intval(strrev(substr($rev_strtime, 8, 2))),
//                'day' => strrev(substr($rev_strtime, 6, 2)),
//                'hour' => strrev(substr($rev_strtime, 4, 2)),
//                'minute' => strrev(substr($rev_strtime, 2, 2)),
//                'second' => strrev(substr($rev_strtime, 0, 2)),
//            );
//
//            //Default Format
//            $format = $format == false ? 'Y/m/d H:i:s' : $format;
//            //时间格式化的映射 Y m d H i s
//            $maps = array('Y' => 'year', 'm' => 'month', 'n' => 'month2', 'd' => 'day', 'H' => 'hour', 'i' => 'minute', 's' => 'second');
//        } elseif ($time < 0) {
//            $datetime = array(
//                'year' => strrev(substr($rev_strtime, 4, strlen($strtime) - 4)),
//                'month' => strrev(substr($rev_strtime, 2, 2)),
//                'month2' => intval(strrev(substr($rev_strtime, 2, 2))),
//                'day' => strrev(substr($rev_strtime, 0, 2)),
//            );
//
//            //Default Format
//            $format = $format == false ? 'Y/m/d' : $format;
//            //时间格式化的映射 Y m d H i s
//            $maps = array('Y' => 'year', 'm' => 'month', 'n' => 'month2', 'd' => 'day');
//        } else {
//            return false;
//        }

        //统计执行格式化信息
        $map_values = array();
        foreach ($maps as $key => $map) {
            $map_values[$key] = $datetime[$map];
        }
        //格式化信息并返回
        return str_replace(array_keys($maps), $map_values, $format);
    }

}