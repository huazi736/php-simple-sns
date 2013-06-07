<?php

/**
 * post数据接收并去除空格
 */
if (!function_exists('P')) {
	function P($key = '') {
		if (empty($key)) {
			return '';
		}
		$ci = & get_instance();
		return shtmlspecialchars(trim($ci->input->get_post($key)));
	}
}

/**
 * get_post数据接收并去除空格
 */
if (!function_exists('get_post')) {
	function get_post($key = '') {
		if (empty($key)) {
			return '';
		}
		$ci = & get_instance();
		return shtmlspecialchars(trim($ci->input->get_post($key)));
	}
}

/**
 * get数据接收并去除空格
 */
if (!function_exists('G')) {
	function G($key = '') {
		if (empty($key)) {
			return '';
		}
		$ci = & get_instance();
		return shtmlspecialchars(trim($ci->input->get($key)));
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

/**
 * 对输出进行控制
 *
 * @author fbbin
 * @param array/string $info
 * @param bool $status
 * @param array $extra
 */
function dump($info = '', $status = false, $extra = array()) {
	if (is_string($info)) {
		$data = array(
				'data' => array(),
				'status' => (int)$status,
				'info' => $info
		);
	} elseif (is_array($info)) {
		$data = $info;
	}
	if (!empty($extra)) {
		$data = array_merge($data, $extra);
	}
	exit(json_encode($data));
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

function checkData($data) {
	if(empty($data)) {
		return false;
	} else {
		$val = explode('_', $data);
		if(is_string($val)) {
			return array($val, 1);
		} else if(is_array($val)) {
			return array($val[count($val)-1], count($val));
		} else {
			return false;
		}
	}
}



