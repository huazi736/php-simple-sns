<?php
/**
 *
 * @author zhoulianbo
 * @date 2012-7-14
 * @description to do
 */

/**
 * 将待显示的时间智能转换
 * @author wangqiang
 * @param int $sTime 待显示的时间
 * @return string $str 智能时间显示
 */
function tran_time($sTime)
{
	$time = time() - $sTime;
	if($time < 0) {
		$str = "错误的时间！";
	}elseif($time < 60) {
		$str = $time."秒前";
	}elseif($time < 60 * 60) {
		$min = floor($time/60);
		$str = $min."分钟前";
	}elseif($time < 60 * 60 * 24) {
		$h = round($time/(60*60));
		$str = $h."小时前";
	}else {
		$str = date("Y年m月d日H:i",$sTime);
	}

	return $str;
}

/**
 * @author fbbin
 * flash上传文件特殊处理
 */

function parseFlashUpload() {
    $CI = &get_instance();
    //传过来的用户信息解密
    if (isset($_POST['flashUploadUid'])) {
        $uid = sysAuthCode($_POST['flashUploadUid'], 'DECODE');
        $CI->session->set_userdata('uid', $uid);
    } elseif (isset($_GET['flashUploadUid'])) {
        $uid = sysAuthCode($_GET['flashUploadUid'], 'DECODE');
        $CI->session->set_userdata('uid', $uid);
    }

    return true;
}



/**
 * 打印变量
 * @author weijian
 */
function dump($var, $output = null)
{
    if($output == null){
        echo "<pre>";
        print_r($var);
        echo "</pre>";
    }elseif($output == 'firephp'){
        FB::info($var);
    }
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
 * get数据接收并且去处空格
 */
if (!function_exists('G')) {

    function G($key = '') {
        if (empty($key)) {
            return '';
        }
        $ci = & get_instance();
        return shtmlspecialchars(trim($ci->input->get($key, true)));
    }

}

/**
 * 获取变量
 * 
 * @author weijian
 * @param unknown_type $key
 * @param unknown_type $default
 */
function R($key, $default = null)
{
    if(empty($key)){
        return $default;
    }
    $ci = & get_instance();
    if(isset($_POST[$key])){
        return shtmlspecialchars(trim($ci->input->post($key)));
    }elseif(isset($_GET[$key])){
        return shtmlspecialchars(trim($ci->input->get($key, true)));
    }else{
        return $default;
    }
}

/**
 * 取消HTML代码替换原有的htmlspecialchars，支持数组
 * @ pragma string 需要处理的字符串
 * @author fbbin
 */
if (!function_exists('shtmlspecialchars')) {

    function shtmlspecialchars($string) {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = shtmlspecialchars($val);
            }
        } else {
            $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
        }
        //完全过滤JS
        $string = preg_replace('/<script?.*\/script>/', '********', $string);
        return $string;
    }

}

/**
 * 获得图片后缀
 * 
 * @author weijian
 * @param stirng $type
 */
function getImgType($type) {
    if(strpos($type, 'gif') !== false){
        return 'gif';
    }elseif(strpos($type, 'png') !== false){
        return 'png';
    }elseif(strpos($type, 'jpg') !== false){
        return 'jpg';
    }elseif(strpos($type, 'jpeg') !== false){
        return 'jpg';
    }else{
        return false;
    }
}

/**
 * 输出JSON格式字符串
 * 
 * @author weijian
 * @param mix $data
 */
function toJSON($data) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data);
    exit;
}


/**
 * 获得用户首页的URL
 * 
 * @author weijian
 * @param string $uid 用户编号
 */
function getUserUrl($dkcode)
{
    return WEB_ROOT . 'main/?action_dkcode='.$dkcode;
}

/**
 * 得到用户的dkcode
 * 
 * @author weijian
 */
function getUserDK($uid)
{
    $userinfo = getUserInfo($uid);
    return $userinfo['dkcode'];
}

/**
 * 得到用户的资料
 * 
 * @author weijian
 * @identified by fbbin
 */
function getUserInfo($uids)
{
//     return call_soap('ucenter','User', 'getUserInfo',array($uid, 'uid'));
	if( is_string( $uids ) )
	{
		$uids = array($uids);
		unset($uid);
	}
	require_cache(APPPATH . 'core' . DS . 'MY_Redis' . EXT);
	if (empty($uids))
	{
		return false;
	}
	$oRedis = MY_Redis::getInstance();
	$aResults = array();
	foreach ($uids as $uid)
	{
		$aResults[$uid] = $oRedis->hMGet('user:'.$uid, array('id','name','dkcode'));
		$aResults[$uid]['uid'] = $aResults[$uid]['id'];
		$aResults[$uid]['username'] = $aResults[$uid]['name'];
		unset($aResults[$uid]['id'], $aResults[$uid]['name']);
	}
	return count($uids) == 1 ? array_pop($aResults) : $aResults;
}


/**
 * @author fbbin
 * 字符串加密、解密函数
 * @param	string	$txt		字符串
 * @param	string	$operation	ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE，
 * @param	string	$key		密钥：数字、字母、下划线
 * @return	string
 */
if (!function_exists('sysAuthCode')) {

    function sysAuthCode($txt, $operation = 'ENCODE', $key = '!@#$%^&*1QAZ2WSX') {
        $key = $key ? $key : 'HZYEYAOMAI2011';
        $txt = $operation == 'ENCODE' ? (string) $txt : base64_decode($txt);
        $len = strlen($key);
        $code = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $k = $i % $len;
            $code .= $txt[$i] ^ $key[$k];
        }
        $code = $operation == 'DECODE' ? $code : base64_encode($code);
        return $code;
    }

}
/**
	 * curl获取网页返回的数据
	 */
	function get_page($url = null)
	{
		if(empty($url)) {
			return false;
		}
		
		if(!function_exists('curl_init')){
			return false;
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_exec($ch);
		$result = curl_multi_getcontent($ch);
		curl_close($ch);
		return trim($result);
	}

/**
 * 得到图片路径
 * 
 * @author weijian
 * @param string $group 组名
 * @param string $filename 文件名（不带后缀）
 * @param string $ext 文件后缀
 * @param string $thumb 缩略图名称，如果为空则表示原图
 */
function getImgPath($group, $filename, $ext, $thumb = null) 
{	
	//get_instance();
	$configs = include(CONFIG_PATH . 'fastdfs.php');
	$filename = null === $thumb ? $filename : $filename."_".$thumb;
	return "http://".$configs['album']['host']."/".$group."/".$filename.".".$ext;
}

	/**
	 * 得到远程图片路径
	 * 
	 * @author vicente
	 * @param string $filename 名称
	 * @param string $ext 文件后缀
	 * @param string $type 类型
	 * @param string $romote_img_url 远程服务器
	 */
	function getImgRomotePath($filename, $ext, $type, $day_file, $romote_img_url) 
	{
		$name = trim(substr($filename, strrpos($filename, '/')+1));
		return $romote_img_url."/".$day_file."/".$name."_".$ext.'.'.$type;
	}

/**
 * 获得用户头像图片
 */
function getAvatar($uid, $size = 's') 
{
    $v = '?v=' . time();
	$CI = &get_instance();
	$fname = @rtrim($CI->redisdb->get('avatar:'.$uid),'.jpg');
	if(empty($fname)) return MISC_ROOT.'img/default/avatar_' . $size . '.gif';
	return $fpath = 'http://'.config_item('fastdfs_host').'/'.config_item('fastdfs_group').'/'.$fname.'_'.$size.'.jpg'.$v;
	
}

function getSocial($type, $uid = UID, $action_uid = ACTION_UID)
{
    switch($type){
        case 'friend':
            return service('Relation')->isFriend($action_uid, $uid);
            break;
        case 'fans':
        	return service('Relation')->isFollower($action_uid, $uid);
            break;
        case 'follow':
        	return service('Relation')->isBothFollow($action_uid, $uid);
            break;
    }
}

