<?php
/**
 * 产生36位uuid
 *
 * @author chenjiali
 * @date 2011/09/07
 * @access public
 * @return string
 */
if (!function_exists('get_uuid')) {

	function get_uuid() {
		$chars = md5(uniqid(mt_rand(), true));
		$uuid = substr($chars, 0, 8) . '-';
		$uuid .= substr($chars, 8, 4) . '-';
		$uuid .= substr($chars, 12, 4) . '-';
		$uuid .= substr($chars, 16, 4) . '-';
		$uuid .= substr($chars, 20, 16);
		return $uuid;
	}

}

/**
 * 安全获取$_POST 变量
 */
if (!function_exists('P')){
	function P($key = '', $mode = "111"){
		if (empty($key)){
			return '';
		}
		$ci = &get_instance();
		return safedata($ci->input->post($key, true), $mode);
	}
}

/**
 * 安全获取$_GET 变量
 */
if (!function_exists('G')) {
	function G($key = '', $mode = "111"){
		if (empty($key)){
			return '';
		}
		$ci = &get_instance();
		return safedata($ci->input->get($key, true), $mode);
	}
}

/**
 * 安全获取$_POST或$_GET 变量
 */
if (!function_exists('R')) {
	function R($key, $mode = "111"){
		if(empty($key)){
			return $default;
		}
		$ci = & get_instance();
		return safedata($ci->input->get_post($key, true), $mode);
	}
}


/*************以下公共函数wiki项目添加  start *******************/
/**
 * 获取词条的名称
 *
 * @author bohailiang
 * @date   2012/5/4
 * @param  $citiao_id  int    词条id
 * @return string / false
 */
function getCitiaoName($citiao_id = 0){
	if(empty($citiao_id)){
		return false;
	}

	$ci = & get_instance();
	$ci->load->model('wikimodel', 'wiki');

	$citiao_name = $ci->wiki->getCitiaoName($citiao_id);
	return $citiao_name;
}

/**
 * @desc 二维数组根据字段转一维数组
 * @param array $arr 二维数组
 * @param array $field  字段
 * @return array
 */
function arrayTwoOneByField($arr = array(), $field = '') {
	if(!is_array($arr) || empty($arr)) {
		return array();
	}
	$return = array();
	foreach($arr as $k => $v) {
		$return[$k] = $v[$field];
	}
	return $return;
}

/**
 * @author lijianwei
 * @desc 判断是否是ajax请求
 * @date 2012/04/28
 */
if(!function_exists("isAjax")) {
	function isAjax()
	{
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']))
		{
			if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
			{
				return true;
			}
		}
		return false;
	}
}

/**
 * @author lijianwei
 * @desc  异常处理
 * @date 2012/04/28
 */
if(!function_exists("exception_handler")) {
	function exception_handler($e) {
		header("content-type:text/html;charset=utf-8");
		//@todo 记录日志中 $e->getFile()  $e->getLine()  $e->getMessage() $e->getCode();
		$msg = config_item("debug") ? "<<<<发生异常>>>><br>文件[ ".$e->getFile()." ]<br>行[ ". $e->getLine()." ]<br>错误信息[ ".$e->getMessage()." ]<br>错误编码[ ".$e->getCode()." ]" : "系统繁忙";

		if(isAjax()){
			$message = array(
			'file' => $e->getFile(),
			'code' => $e->getCode(),
			'line' => $e->getLine(),
			'message' => $e->getMessage(),
			'module' =>'wiki',
			'ajax'   =>1
			);
			log_message('ERROR', $message);
			die(json_encode(array("status" => 0, "msg" => $msg)));

		}else{


			$message = array(
			'file' => $e->getFile(),
			'code' => $e->getCode(),
			'line' => $e->getLine(),
			'message' => $e->getMessage(),
			'module' =>'wiki',
			'ajax'   =>0
			);
			log_message('ERROR', $message);

			$CI = &get_instance();
			$CI->showmessage($msg, 2, mk_url('webmain/index/main', array("web_id" => intval($CI->input->get_post('web_id')))), 10);
			//show_error($msg);
		}
	}
}

/**
 * @author lijianwei
 * @desc  错误处理
 * @date 2012/05/08
 */
//set_error_handler("error_handler");
if(!function_exists("error_handler")) {
	function error_handler($errno, $errstr, $errfile, $errline)
	{
		header("content-type:text/html;charset=utf-8");
		switch ($errno) {
			case E_ERROR:
			case E_USER_ERROR:
				$errorStr = config_item("debug") ? "<<<<发生错误>>>><br>文件[ ". $errfile. " ]<br>行[ ". $errline." ]<br>错误信息[ ". $errstr. " ]<br>错误编码[ ". $errno. " ]" : "系统繁忙";
				break;
			case E_STRICT:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			default:
				$errorStr = config_item("debug") ? "<<<<发生错误>>>><br>文件[ ". $errfile. " ]<br>行[ ". $errline." ]<br>错误信息[ ". $errstr. " ]<br>错误编码[ ". $errno. " ]" : "系统繁忙";
				break;
		}
		if(isset($errorStr) && $errorStr){
			if(isAjax()){
				die(json_encode(array("status" => 0, "msg" => $errorStr)));
			}else{
				$CI = &get_instance();
				$CI->showmessage($msg, 2, mk_url('webmain/index/main', array("web_id" => intval($CI->input->get_post('web_id')))), 10);
				//show_error($errorStr);
			}
		}
	}
}

/**
 * 检测mongodb _id合法性
 * @param string $mongo_id
 * @param boolean  $strict 是否严格检测
 * return true or false
 */
function check_mongo_id($mongo_id = "", $strict = true) {
	if(!$mongo_id) return false;

	$len = strlen($mongo_id);
	$mb_len = mb_strlen($mongo_id, "utf-8");

	if(($len == 24) && ($mb_len == 24) && !$strict) {
		return true;
	}else if(($len == 24) && ($mb_len == 24) && $strict) {
		return preg_match('/^[abcdef0-9]{24}$/', $mongo_id);
	}else{
		return false;
	}
}

/**
 * 获取wiki配置项
 */
function getWikiConfigItem($item_name = "") {
	if(empty($item_name)) return false;

	static $wiki_config = array();

	if(isset($wiki_config[$item_name])) return $wiki_config[$item_name];

	$CI = &get_instance();
	if(!isset($CI->mdb)){
		$CI->load->library("Mongo_db", "", "mdb");
	}
	$data = $CI->mdb->findOne("wiki_settings", array("name" => $item_name), array("value"));
	if($data && isset($data['value'])) return $wiki_config[$item_name] = $data['value'];
	return false;
}
/**
 * @desc 过滤敏感词
 * @author lijianwei
 * @param string $content
 * @return 处理之后的内容
 */
function filterContent($content = ""){
	$CI = &get_instance();
	$CI->load->model("commonmodel", "common");
	return $CI->common->filterContent($content);
}

/**
 * 检查字符串是否是UTF8编码
 * @param string $string 字符串
 * @return Boolean
 */
if(!function_exists("is_utf8")){
	function is_utf8($string){
		return preg_match('%^(?:
		 [\x09\x0A\x0D\x20-\x7E]            # ASCII
	   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
   )*$%xs', $string);
	}
}

/**
 * 获取字符串长度  如中国abc  返回5
 * 支持utf-8 gb2312
 */
if(!function_exists("getStrLen")){
	function getStrlen($str = ""){
		$str = trim($str);
		if(empty($str))return 0;
		if(!is_utf8($str)){
			if(function_exists("mb_convert_encoding")){
				$str = mb_convert_encoding($str, "UTF-8", "gb2312");
			}elseif(function_exists("iconv")){
				$str = iconv("gb2312", "UTF-8", $str);
			}
		}
		return mb_strlen($str, "UTF-8");
	}
}

/**
 * 获取用户资料信息
 * @param int/string $uid
 * @author fbbin
 * @return array
 */
if(!function_exists("getUserInfo")){
	function getUserInfo( $uids )
	{
		if( is_string( $uids ) )
		{
			$uids = array($uids);
			unset($uid);
		}
		if (empty($uids))
		{
			return false;
		}
		$oRedis = get_redis("default");
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
}
/**
 * 数组trim
 */
if(!function_exists("trim_deep")){
	function trim_deep(&$data){
		if(is_string($data)){
			$data = trim($data);
		}elseif(is_array($data)){
			foreach($data as $k => $v){
				trim_deep($v);
				$data[$k] = $v;
			}
		}
		return $data;
	}
}
/**
 * 数组过滤js iframe  frameset 
 */
if(!function_exists("js_deep")){
	function js_deep(&$data){
		if(is_string($data)){
			$data = preg_replace(array("|<script[^>]*?>.*?</script>|is", "|<iframe[^>]*/?>(.*?<\s*?/iframe>)?|is", "|<frameset[^>]+>.+<\s*?/frameset>|is"),array("", "", ""), $data);
		}elseif(is_array($data)){
			foreach($data as $k => $v){
				js_deep($v);
				$data[$k] = $v;
			}
		}
		return $data;
	}
}
/**
 * 数组过滤html标签
 */
if(!function_exists('html_deep')){
	function html_deep(&$data){
		if (is_array($data)){
			foreach($data as $k => $v){
				$data[$k] = html_deep($v);
			}
		}elseif(is_string($data)) {
			//edit by lijianwei 2012/05/02 因mongodb安全 转义  添加$
			$data = str_replace(array('&', '"', '<', '>', "'", '$'), array('&amp;', '&quot;', '&lt;', '&gt;', '&#039;', '\\$'), $data);
		}
		return $data;
	}
}
if(!function_exists("strips_deep")){
	function strips_deep(&$data){
		if(is_string($data)){
			if(get_magic_quotes_gpc())$data = stripslashes($data);
		}elseif(is_array($data)){
			foreach($data as $k => $v){
				stripslashes_deep($v);
				$data[$k] = $v;
			}
		}
		return $data;
	}
}
if(!function_exists("addslash_deep")){
	function addslash_deep(&$data){
		if(is_string($data)){
			if(!get_magic_quotes_gpc())$data = addslashes($data);
		}elseif(is_array($data)){
			foreach($data as $k => $v){
				addslash_deep($v);
				$data[$k] = $v;
			}
		}
		return $data;
	}
}
/**
 * 安全过滤  post get 数据
 * @author lijianwei
 */
if(!function_exists("safedata")){
	function safedata($data, $mode = "111"){

		trim_deep($data);
		if(empty($data)) return "";

		$mode_arr = preg_split("//", $mode, -1, PREG_SPLIT_NO_EMPTY);
		if(0 == $mode_arr[0]){//取消转义
			$data = strips_deep($data);
		}elseif(1 == $mode_arr[0]){//转义
			$data = addslash_deep($data);
		}

		if($mode_arr[1]) js_deep($data);
		if($mode_arr[2]) html_deep($data);
		return $data;
	}
}

/**
 * 输出Widget  仿widget
 * @param string $name
 * @param array  $data
 * @return bool $return 是否返回
 */
if(!function_exists("wiki_widget")){
	function wiki_widget($name, $data = array(), $return = true){
		$class = strtolower($name);

		//验证widget是否存在
		$all_widget = getAllWebPluginWidgetNames();
		if(empty($all_widget) || !in_array($class, $all_widget)) return "";

		static $_widgets = array();
		if (! isset($_widgets[$name]))
		{
			if (! class_exists('MY_Widget', false))
			{
				require_cache(APPPATH . 'core' . DS . 'MY_Widget' . EXT);
			}
			if (! class_exists($class, false))
			{
				$filepath = APPPATH . 'widgets' . DS . $class . EXT;
				if (file_exists($filepath))
				{
					require_cache($filepath);
				}
				else
				{
					return "";
					//throw new Exception("$filepath 找不到");
				}
			}
			$_widgets[$name] = new $class();
		}
		$widget = $_widgets[$name];
		$content = $widget->render($data);
		if ($return)
		{
			return $content;
		}
		echo $content;
	}
}
/*
* 获取网页所有插件信息
*/
function getAllWebPluginWidgetNames(){
	static $allwebpluginwidgetnames = array();
	if($allwebpluginwidgetnames) return $allwebpluginwidgetnames;
	$CI = &get_instance();
	$CI->load->model("pluginmodel", "plugin");
	$allwebplugin = $CI->plugin->getAllWebPlugins();

	return ($allwebpluginwidgetnames = arrayTwoOneByField($allwebplugin, "widget_name"));
}

/*
* 获取真实的文件格式    支持常见的文件格式  参见config/mimes.php
* @author lijianwei
*/
function getRealFileExt($filename = ""){
	if(empty($filename) || !file_exists($filename)) return "";

	$ext = "";

	if(!@ini_get('safe_mode')){//如果没有开启安全模式
		$mime_type = @exec(sprintf("file --mime-type -b %s", $filename));
		$ext  =  $mime_type ? getExtByMimetype($mime_type) : "";
	}
	if(!$ext){
		if(function_exists("finfo_open")){
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type =  finfo_file($finfo, $filename);
			finfo_close($finfo);
			$ext  = $mime_type ? getExtByMimetype($mime_type) : "";
		}else{
			$ext = getRealFileExtByRead2Byte($filename);
		}
	}
	return $ext;
}
/*
* 读取文件前2个字节获取文件格式，暂时支持常见的图片格式
* @author lijianwei
*/
function getRealFileExtByRead2Byte($filename){
	$file = fopen($filename, "rb");
	$bin = fread($file, 2);
	fclose($file);
	$strInfo = @unpack("c2chars", $bin);
	$typeCode = intval($strInfo['chars1']. $strInfo['chars2']);
	$fileType = "";

	switch($typeCode){
		case 7790:
			$fileType = "exe";
			break;

		case 7784:
			$fileType = "midi";
			break;

		case 8297:
			$fileType = "rar";
			break;

		case 255216:
			$fileType = "jpg";
			break;

		case 7173:
			$fileType = "gif";
			break;

		case 6677:
			$fileType = "bmp";
			break;

		case 13780:
			$fileType = "png";
			break;

		default:
			//$fileType = "unknown". $typeCode;
			$fileType = "";
			break;
	}
	if($strInfo['chars1'] == '-1' && $strInfo['chars2'] == '-40'){
		return "jpg";
	}
	if($strInfo['chars1'] == '-119' && $strInfo['chars2'] == '80'){
		return 'png';
	}
	return $fileType;
}
/*
* 根据mime获取文件格式
* @author lijianwei
*/
function getExtByMimetype($mime_type = ""){
	if(empty($mime_type)) return "";
	$mime_type = strtolower($mime_type);
	include CONFIG_PATH. "mimes.php";
	return array_search($mime_type, $mimes);
}
/*************以下公共函数wiki项目添加  end *******************/