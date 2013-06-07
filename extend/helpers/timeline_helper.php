<?php

function get_uuid() {
    $chars = md5(uniqid(mt_rand(), true));
    $uuid = substr($chars, 0, 8) . '-';
    $uuid .= substr($chars, 8, 4) . '-';
    $uuid .= substr($chars, 12, 4) . '-';
    $uuid .= substr($chars, 16, 4) . '-';
    $uuid .= substr($chars, 20, 16);
    return $uuid;
}

/**
 * 未来时间显示
 * @param unknown_type $stime
 */
function friendlyDateAfter($stime) {
    $cTime = time();
    $nowTime = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
    $todayTime = mktime('0', '0', '0', date('m'), date('d'), date('Y'));
    $tommrrowTime = $todayTime + 86400;
    $afterTommrrowTime = $tommrrowTime + 86400;
    $tTommrrowTime = $afterTommrrowTime + 86400;
    $weekTime = $todayTime - date('w', $cTime) * 86400;
    $nextWeekTime = $weekTime + 7 * 86400;
    $nNextWeekTime = $nextWeekTime + 7 * 86400;
    $dtime = $stime - $cTime;
    $week = array('日  ', '一  ', '二  ', '三  ', '四 ', '五  ', '六  ');
    //一分钟以内
    if ($dtime > 0 && $dtime < 60) {
        return $dtime . ' 秒以后';
        //一小时以内
    } elseif ($dtime > 60 && $dtime < 3600) {
        return floor($dtime / 60) . ' 分钟以后';
        //今天之内
    } elseif ($dtime > 3600 && $stime < $tommrrowTime) {
        $h = floor($dtime / 60 / 60);
        if ($h > 3) {
            return "今天  " . date('H:i', $stime);
        }
        return $h . '小时以后';
    }
    //今天和明天之间
    elseif ($stime > $tommrrowTime && $stime < $afterTommrrowTime) {
        return '明天 ' . date('H:i', $stime);
    }
    //明天和后天之间
    elseif ($stime > $afterTommrrowTime && $stime < $tTommrrowTime) {
        return '后天 ' . date('H:i', $stime);
    }
    //后天和本周六之间
    elseif ($stime > $tTommrrowTime && $stime < $nextWeekTime) {
        return '本周' . $week[date('w', $stime)] . date('H:i', $stime);
    }
    //本周和下周之间
    elseif ($stime > $nextWeekTime && $stime < $nNextWeekTime) {
        return '下周' . $week[date('w', $stime)] . date('H:i', $stime);
    } else {
        return date("Y年m月d日H:i", $stime);
    }
}

/**
 * 将字符串时间转换为时间信息数组
 * @author shedequan
 * @param type $strtime 时间字符串
 * @return type 
 */
function parseTime($strtime) {
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

    if ($time > 0) {
        $datetime['bc'] = 0;
    } elseif ($time < 0) {
        $datetime['bc'] = 1;
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
function formatTime($datetime, $format = false, $not = true) {
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
        $hms = $datetime['hour'] . $datetime['minute'] . $datetime['second'];
        if( !$not && !intval($hms) )
        {
            return '';
        }
        //时间格式化的映射 Y m d H i s
        $maps = array('Y' => 'year', 'm' => 'month', 'n' => 'month2', 'd' => 'day', 'H' => 'hour', 'i' => 'minute', 's' => 'second');
    }

    //统计执行格式化信息
    $map_values = array();
    foreach ($maps as $key => $map) {
        $map_values[$key] = $datetime[$map];
    }
    //格式化信息并返回
    return str_replace(array_keys($maps), $map_values, $format);
}

/**
 * 生成友好时间描述：处理格式为 2012 1 0101010101
 * @author shedequan
 * @param type $strtime 时间字符串
 * @return type 
 */
function makeFriendlyTime($strtime) {
    $val = floatval($strtime);
    $val = abs($val);
    if (strlen(strval($val)) <= 10) {
        return friendlyDate($strtime);
    }

    $datetime = parseTime($strtime);
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

        $month = floatval($datetime['month2']);
        $tail = $month >= 1 ? $month . '月' : '';

        $day = floatval($datetime['day']);
        $tail .= $day >= 1 ? $day . '日' : '';

        return $start . $num . $end . $tail;
    } else if ($year > 0) {
        $now = strtotime('23:59:59');
        if ($datetime['year'] == date('Y', $now)) {
            $inhand = strtotime(formatTime($datetime, 'Ymd H:i:s'));
            $seconds = $now - $inhand;
            $abs_seconds = abs($seconds);
            if ($seconds >= 0) {
                //before
                return expressBefore($datetime, $abs_seconds);
            } else if ($seconds < 0) {
                return '刚刚';
                //after
                //return expressAfter($datetime, $abs_seconds);
            }
        } else {
            if (($datetime['hour'] . $datetime['hour'] . $datetime['hour']) == '000000') {
                return formatTime($datetime, 'Y年m月d日');
            }
            return formatTime($datetime);
        }
    }
    return false;
}

/**
 * 对过去时间的描述
 * @author shedequan
 * @param type $datetime 时间信息数组
 * @param type $seconds 与当前时间的间隔
 * @return type 
 */
function expressBefore($datetime, $seconds) {
    if ($seconds > 0 && $seconds < 10) {
        //刚刚
        $num = '';
        $end = '刚刚';
    } else if ($seconds >= 10 && $seconds < 60) {
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
        $end = '昨天 ' . formatTime($datetime, 'H:i', false);
    } else if ($seconds >= 172800 && $seconds < 259200) {
        //前天
        $num = '';
        $end = '前天 ' . formatTime($datetime, 'H:i', false);
    } else {
        return formatTime($datetime);
    }
    return $num . $end;
}

/**
 * 对未来时间的描述
 * @author shedequan
 * @param type $datetime 时间信息数组
 * @param type $seconds 与当前时间相隔的秒数
 * @return type 
 */
function expressAfter($datetime, $seconds) {
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
        $end = '明天 ' . formatTime($datetime, 'H:i');
    } else if ($seconds >= 172800 && $seconds < 259200) {
        //后天
        $num = '';
        $end = '后天 ' . formatTime($datetime, 'H:i');
    } else {
        return formatTime($datetime);
    }
    return $num . $end;
}

/**
 * 截取博客内容作为摘要
 * @author jiangfangtao
 * @date 2012/06/07
 * @param $content 要截取的内容
 * @param $maxLen 要截取的最大长度
 * @param $allowTags 需要保留的标签
 */
 if (!function_exists('htmlSubString')) {
function htmlSubString($content, $maxLen=200) {
    $content = trim($content);
    $content = str_replace('&nbsp;', '<nbsp>', $content);
    $content = strip_tags($content, '<p><a><b><i><ul><ol><li><br><div><dl><dt><dd><img><strong><em><h1><h2><h3><h4><h5><h6><pre><span><table><tr><td><th><nbsp>');
    $curLen = mb_strlen(strip_tags($content), 'utf-8');
    if ($curLen <= $maxLen) {
        $cut = false;
    } else {
        $num = 0;
        $in_tag = false;
        for ($i = 0; $num < $maxLen || $in_tag; $i++) {
            if (mb_substr($content, $i, 1) == '<') {
                $in_tag = true;
            } elseif (mb_substr($content, $i, 1) == '>') {
                if (mb_substr($content, $i + 1, 1) == '<') {
                    $in_tag = true;
                } else {
                    $in_tag = false;
                }
            } elseif (mb_substr($content, $i, 1) == '{' && mb_substr($content, $i, 5) == '{img_') {
                $in_tag = true;
                $i+=9;
            } elseif (!$in_tag) {
                $num++;
            }
        }
        $content = mb_substr($content, 0, $i, 'utf-8');
        $cut = true;
    }
    $content = str_replace('<nbsp>', '&nbsp;', $content);
    $content = str_replace('<p></p>', '<br/>', $content);
    $content = str_replace('<p><br></p>', '<br/>', $content);
    $content = preg_replace('/\<\/?br\/?\>(<\/?br\/?>)+/', '<br/>', $content);
    $summary = array('0' => $content, '1' => $cut);
    return $summary;
}
}

function showtime() {
    return date('Y-m-d H:i:s', time());
}

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
function makeTime($year, $month = '01', $day = '01', $hour = '00', $minute = '00', $second = '00') {
    if (strlen($month . $day . $hour . $minute . $second) != 10) {
        return false;
    }

    if ($year > 0 || $year < 0) {
        return $year . $month . $day . $hour . $minute . $second;
    } else {
        return false;
    }
}
