<?php

/**
 * 检测是否有头像
 */
function exist_avatar($web_id) {
	$redis = get_redis('avatar');
	$res = $redis->get($web_id);
	if($res) return true;
	return false;
	/*require_cache(APPPATH . 'core' . DS . 'MY_Redis' . EXT);
    if (empty($uid))
        return false;
    $v = '?v=' . time();
	$redis = MY_Redis::getInstance();   
    $flag = $redis -> get('avatar:is_default' . $uid);

    //2 是默认头像 3不是默认头像
    if (empty($flag) || $flag == 2) {
        return false;
    } else {
        return true;
    }*/
	// return false;
}


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

function autolink_create_html_tags(&$value, $key, $other = null) {
	$target = $nofollow = null;
	$url = str_replace(array('&amp;','&quot;','&lt;','&lt','&gt;','&gt'),'', $key);
	if (strpos($key, 'http://') === false) {
		$url = 'http://' . $key;
	}
	if (is_array($other)) {
		$target = $other['target'] ? " target=\"$other[target]\"" : null;
		$nofollow = $other['nofollow'] ? ' rel="nofollow"' : null;
	}
	$value = "<a href=\"$url\"$target$nofollow>$key</a>";
}
/**
 * post数据接收并去除空格
 */
if (!function_exists('P')) {

	function P($key = '') {
		if (empty($key)) {
			return '';
		}
		$ci = & get_instance();
		return shtmlspecialchars(@trim($ci->input->get_post($key)));
	}

}
/**
 * 此方法已不加载语言包
 * 兼容原有的code
 * @modify by zengmingming
 */
function L($item) {
	return $item;
}