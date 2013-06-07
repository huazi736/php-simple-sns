<?php
/**
 * 函数库
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012-02-10>
 * 
 */





/**
 * 生成表单验证 
 * 
 * @author guoshaobo<guoshaobo@duankou.com>
 * @date <2012/02/14>
 */
function buildToken()
{
	$token_conf = config_item('token');
	$encrypt_array = $token_conf['encrypt_array'];
	$link_string = $token_conf['link_string'];
	if(!@ADDTOKEN) return '';				// 配置未开启,就不验证表单;
	static $token_static=null;
	if($token_static!==null) return $token_static;
	$CI = & get_instance();
	
	// 生成验证令牌
	$token = 'duankou'.$link_string.time().$link_string.$encrypt_array[rand(0,9)];
	// 加密 
	$token_static = $CI->encrypt->encode($token,TOKENKEY);
	return $token_static;
}

/**
 * 验证表单 
 * 
 * @author guoshaobo<guoshaobo@duankou.com>
 * @date <2012/02/14>
 * 
 * @param	$token		表单提交过来的验证值
 * 
 */
	function checkToken($token) 
    {
		$token_conf = config_item('token');
		$encrypt_array = $token_conf['encrypt_array'];
		$link_string = $token_conf['link_string'];
        if(!@ADDTOKEN) return true;				// 配置未开启,就不验证表单;
    	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
        {
        	return true;
        }
        else
        {
        	$CI = & get_instance();
        	$token = $CI->encrypt->decode($token,TOKENKEY);
        	$arr = explode($link_string,$token);
        	$time = time();
        	if(!empty($arr) or count($arr)==4)
        	{
        		// 判断是否为端口网标记
        		if($arr['0']!='duankou')
        		{
        			return false;
        		}
        		// 判断是否是我们的配置字符串
        		if(!in_array($arr['2'], $encrypt_array))
        		{
        			return false;
        		}
        		// 判断时间是否过期
        		if($arr['1']>$time or $arr['1'] <($time - TOKENTIMELIMIT))
        		{
        			return false;
        		}
        		return true;
        	}
        }
        return false;
    }


    
    
/**
 * @author guosb
 * 文本截取
 * 
 * @param	$content	需要替换的文本
 * @param	$maxlen		截取长度
 * @param	$charset	字符串编码
 *
 * @return	array(截取后的字符串,处理结果)
 */
if (!function_exists('htmlSubString')) {

    function htmlSubString($content, $maxlen=200, $charset="UTF-8") {

        $curlength = 0;
        $Tags = array();
        $outstr = '';
        $cut = false;
        $tempv = '';
        //把字符按HTML标签变成数组。
        for ($i = 0; $i < strlen($content); $i++) {
            $letter = $content{$i};
            if ($letter != '<' && $letter != '>') {
                $tempv.=$letter;
            } else {
                if ($letter == '<' && $content{$i + 1} !== ' ') {//新标记开始
                    if (trim($tempv) != '') {
                        $contents[] = $tempv;
                    }
                    $tempv = $letter;
                } elseif ($letter == '>' && $tempv{0} == '<') { //标记结束
                    $tempv.=$letter;
                    if (trim($tempv) != '') {
                        $contents[] = $tempv;
                    }
                    $tempv = '';
                } else {
                    $tempv.=$letter;
                }
            }
        }
        if (trim($tempv) !== '') {
            $contents[] = $tempv;
        }
        if ($contents) {
            foreach ($contents as $value) {

                if (preg_match('/<\S[^<>]*?>/si', $value)) { //处理标记
                    if (substr($value, 0, 2) == '</') {
                        $endTag = substr($value, 2, strlen($value) - 3);
                        if (count($Tags) < 1) {
                            $outstr.='<' . $endTag . '>' . $value; //纠正错误标记
                            continue;
                        } //丢弃错误结束标记
                        $tagName = array_pop($Tags);
                        while ($tagName != $endTag && $tagName !== '') {
                            $outstr.="</" . $tagName . ">";
                            if (count($Tags) > 0) {
                                $tagName = array_pop($Tags);
                            } else {
                                $tagName = '';
                            }
                        }
                        $outstr.=$value;
                    } elseif (substr($value, 0, 3) == '</ ') { //处理'</ '这样的错误标记
                        $outstr.=$value;
                        continue;
                    } else {
                        //取得起始标记
                        if (strpos($value, ' ') !== false) {
                            $tagName = substr($value, 1, strpos($value, ' ') - 1);
                        } else {
                            $tagName = substr($value, 1, -1);
                        }
                        //压入标记到堆栈，并添加到返回字符串
                        array_push($Tags, $tagName);
                        $outstr.=$value;
                    }
                } else { //处理内容
                    $curlength+=mb_strlen($value, $charset);

                    if ($maxlen <= $curlength) {
                        if ($maxlen < $curlength) { //规避特殊标记内容不允许截断
                            if (count($Tags) > 0 && preg_match('/object|iframe|script|embed/is', $Tags[count($Tags) - 1])) {
                                $outstr.=$value;
                            } else {
                                $outstr.=mb_substr($value, 0, $maxlen - $curlength, $charset);
                            }
                        } else {
                            $outstr.=$value;
                        }
                        while (count($Tags) > 0) {
                            $tagName = array_pop($Tags);
                            $outstr.="</" . $tagName . ">";
                        }
                        $cut = true;
                        break;
                    } else {
                        $outstr.=$value;
                        continue;
                    }
                }
            }
        }
        return array($outstr, $cut);
    }

}
    
    







/**
 * 获取用户资料信息
 * @param int/string $uid
 * @author fbbin
 * @return array
 */
function getUserInfo( $uids )
{
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