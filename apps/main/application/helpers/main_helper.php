<?php

function getIsMarry($num) {
    $marry = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
    if (!in_array($num, $marry)) {
        return false;
    }
    switch ($num) {
        case 0 :
            return '保密'; break;
        case 1 :
            return '单身'; break;
        case 2 :
            return '正在恋爱中';break;
        case 3 :
            return '已订婚'; break;
        case 4 :
            return '已婚'; break;
        case 5 :
            return '关系复杂'; break;
        case 6 :
            return '开放式的交往关系'; break;
        case 7 :
            return '丧偶'; break;
        case 8 :
            return '分居'; break;
        case 9 :
            return '离婚';break;
    }
}


function getShotRes($str = ''){
   if (empty($str)) {
            return '';
        }
        $arr = explode(' ', $str);
        array_shift ( $arr );
	$str = implode ( ' ', $arr );
	return $str;
}



   /*
     * 截取中文字符串，超过长度用....代替
     * @ auth  liyd
     * */
    function utf8substr($string = '', $from = 0, $len = 0, $dot = '...') {
        if (empty($string)) {
            return $string;
        }
        $str_mode = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $from . '}' . '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $len . '}).*#s';
        $substr = preg_replace($str_mode, '$1', $string);
        if (mb_strlen($substr, 'UTF8') < mb_strlen($string, 'UTF8')) {
            $substr .= $dot;
        }
        return $substr;
    }

/**
 * 检测是否有头像
 */
function exist_avatar($uid) {
	$redis = get_redis('avatar');
	$res = $redis->get($uid);
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
 * post数据接收并去除空格
 */
if (!function_exists('P')) {

    function P($key = '') {
        if (empty($key)) {
            return '';
        }
        $ci = & get_instance();
        return shtmlspecialchars(trim($ci->input->post($key)));
    }

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
    $url = $key;
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
 * 此方法已不加载语言包
 * 兼容原有的code
 * @modify by zengmingming
 */
function L($item) {
	return $item;
}
/**
 * @author 周天良
 * @param string $str 要替换的字符串
 * @param string $packagepath 表情包路径
 */
function faceReplace($str,$packagepath = MISC_ROOT) {
	$packagepath = rtrim($packagepath,'/') . '/img/system/face/face_type_01';
	$facePackage = array('[微笑]','[撇嘴]','[色]','[发呆]','[大哭]','[害羞]','[闭嘴]','[睡]','[发怒]','[调皮]','[呲牙]','[难过]','[冷汗]','[吐]','[可爱]',
			'[饿]','[白眼]','[傲慢]','[困]','[惊恐]','[汗]','[憨笑]','[疑问]','[晕]','[折磨]','[抠鼻]','[坏笑]','[鄙视]','[委屈]','[快哭了]',
			'[亲亲]','[阴险]','[吓]','[囧]','[可怜]','[生气]','[财迷]','[惊]','[冰冻]','[石化]'/*,'[擦汗]','[抠鼻]','[鼓掌]','[糗大了]','[坏笑]',
			'[左哼哼]','[右哼哼]','[哈欠]','[鄙视]','[委屈]','[快哭了]','[阴险]','[亲亲]','[吓]','[可怜]','[菜刀]','[西瓜]','[啤酒]','[篮球]','[乒乓]',
	'[咖啡]','[饭]','[猪头]','[玫瑰]','[凋谢]','[示爱]','[爱心]','[心碎]','[蛋糕]','[闪电]','[炸弹]','[刀]','[足球]','[瓢虫]','[便便]',
	'[月亮]','[太阳]','[礼物]','[拥抱]','[强]','[弱]','[握手]','[胜利]','[抱拳]','[勾引]','[拳头]','[差劲]','[爱你]','[NO]','[OK]',
	'[爱情]','[飞吻]','[跳跳]','[发抖]','[怄火]','[转圈]','[磕头]','[回头]','[跳绳]','[挥手]','[激动]','[街舞]','[献吻]','[左太极]','[右太极]',
	*/);
	$facePackage=array_flip($facePackage);
	if(preg_match_all('#\[.+?\]#', $str, $arr)) {
		if($arr[0] && isset($arr[0])) {
			foreach($arr[0] as $k => $v){
				if(isset($facePackage[$v]))
				{
					$i = $facePackage[$v];
					$i = $i + 1;
					$str=str_replace($v,"<img src=\"{$packagepath}/{$i}.gif\" />", $str);
				}
			}
		}
	}
	return $str;
}
?>