<?php

/**
 * 序列化加密
 *
 * @author qianc
 * @date 2012/06/30
 */
function serialize_and_ecode($array) {
	if(!empty($array)){
		$arr_to_str = serialize($array);
		$arr_to_str = authcode($arr_to_str,'ecode');
		return $arr_to_str;
	}
	return false;

}



/**
 * 反序列化解密
 *
 * @author qianc
 * @date 2012/06/30
 */
function unserialize_and_decode($has_been_ecode_and_serialize_string) {
	if(!empty($has_been_ecode_and_serialize_string)){
		$str_to_arr = authcode($has_been_ecode_and_serialize_string);
		$str_to_arr = unserialize($str_to_arr);		
		return $str_to_arr;
	}
	return false;

}



/**
 * 字符串过滤函数
 *
 * @author qianc
 * @date 2012/06/30
 * @param $uid
 */
function strFilter($string) {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = shtmlspecialchars($val);
            }
        } else {
        	$string = htmlspecialchars($string);
            //$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
        }
        //完全过滤JS
        $string = preg_replace('/<script?.*\/script>/', '********', $string);
        return $string;
}


/**
 * 打印变量
 * @author qianc
 */
function dump($var) {
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}



/**
 * 弹出信息框
 *
 * @author	    qianc
 * @date	    2012/07/14
 * @param	    $msg(信息内容)
 */
function alertMsg($msg) {
	$script = "<script type=\"text/javascript\">\n";
	if($msg){
		$script .= "alert('".$msg."');\n";
	}
	$script .="</script>\n";
	echo $script;
	exit;
}

/* End of file common_helper.php */
/* Location: ./helpers/common_helper.php */