<?php
/**
 * 自动创建链接
 * @ pragma string 需要处理的字符串
 * @author fbbin
 */
function autoLink($text, $target = '_blank', $nofollow = true) {
    $urls = autolink_find_URLS($text);
    if (!empty($urls)) {
        array_walk($urls, 'autolink_create_html_tags', array('target' => $target, 'nofollow' => $nofollow));
        $text = strtr($text, $urls);
    }
    return $text;
}

function autolink_find_URLS($text) {
    $scheme = '(http:\/\/|https:\/\/)';
    $www = 'www\.';
    $ip = '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
    $subdomain = '[-a-z0-9_]+\.';
    $name = '[a-z][-a-z0-9]+\.';
    $tld = '[a-z]+(\.[a-z]{2,2})?';
    $the_rest = '\/?[a-z0-9._\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1}';
    $pattern = "$scheme?(?(1)($ip|($subdomain)?$name$tld)|($www$name$tld))$the_rest";
    $pattern = '/' . $pattern . '/is';
    $c = preg_match_all($pattern, $text, $m);
    unset($text, $scheme, $www, $ip, $subdomain, $name, $tld, $the_rest, $pattern);
    if ($c) {
        return (array_flip($m[0]));
    }
    return array();
}

function P($key = '')
{
      if (empty($key)) {
          return '';
       }
    $ci = & get_instance();
    return shtmlspecialchars(trim($ci->input->get_post($key)));
    return trim($ci->input->get_post($key));
}

/**
 * 此方法已不加载语言包
 * 兼容原有的code
 * @modify by zengmingming
 */
function L($item) {
    return $item;
}

/**
 * 得到使用的频率
 * @param $time
 */
function frequencyOfUse($time){
	if(date('Y', $time) < date('Y'))
		return '一年前使用';
	elseif(date('n', $time) - date('n') >= 6)
		return '半年前使用';
	elseif(date('n', $time) - date('n') >= 3)
		return '三个多月前使用';
	elseif(date('n', $time) - date('n') >= 1)
		return '一个多月前使用';
	elseif(date('W', $time) - date('W') == 3)
		return '大约3周前使用';
	elseif(date('W', $time) - date('W') == 2)
		return '大约2周前使用';
	elseif(date('W', $time) - date('W') == 1)
		return '一个星期前使用';
	elseif(date('Y-m-d', $time) == date('Y-m-d', strtotime('-2 days')))
		return '前天使用过';
	elseif(date('Y-m-d', $time) == date('Y-m-d', strtotime('-1 day')))
		return '昨天使用过';
	elseif(date('Y-m-d', $time) == date('Y-m-d'))
		return '今天使用过';
	elseif(date('W', $time) == date('W'))
		return '本周使用过';
	else return '';
}