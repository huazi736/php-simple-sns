<?php
/**
 * @desc 博客专用函数
 */

/**
 * object to array
 */
function obj_to_array($obj)
{
	$array = array();
	$_array = is_object($obj) ? get_object_vars($obj) : $obj;
	if($_array){
		foreach($_array as $key => $val)
		{
			$val = (is_array($val) || is_object($val)) ? obj_to_array($val) : $val;
			$array[$key] = $val;
		}
	}
	return $array;
}

/*
 * @author guosb
 * 文本截取
 *
 * @param	$content	需要替换的文本
 * @param	$maxlen		截取长度
 * @param	$charset	字符串编码
 *
 * @return	array(截取后的字符串,处理结果)
 */
if (!function_exists('htmlSubStr')) {

	function htmlSubStr($content, $maxlen=200, $charset="UTF-8") {

		$curlength = 0;
		$Tags = array();
		$outstr = '';
		$cut = false;
		$tempv = '';
		//把字符按HTML标签变成数组。
		//add start 1.0(by jiangfangtao 2012/04/26)
		//获取需要截取的内容的长度
		$contentLength = strlen($content);

		//$content = strip_tags($content,'<p><b><i><u><ul><ol><li><br><em><strong><table><tbody><tr><th><td><span>');
		if($contentLength<=$maxlen){
			return array('0'=>$content,'1'=>false);
			exit;
		}
		for ($i = 0; $i < $contentLength; $i++) {
			//add end 1.0(by jiangfangtao)
			$letter = $content{$i};
			//如果内容中没有html等标记
			if ($letter != '<' && $letter != '>') {
				$tempv.=$letter;
			} else {
				 
				if ($letter == '<' && $content{$i + 1} !== ' ') {
					//如果标记前面已经有文字内容，而不是html内容
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
			foreach ($contents as $key=>$value) {
				if($contents[$key]==""){
					unset($contents[$key]);
				}
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

		//add end 1.0(byjiangfangtao 2012/06/05)

		$outstr = str_replace('<p></p>','<br/>',$outstr);
		$outstr = str_replace('<p><br></p>','<br/>',$outstr);
		$outstr = preg_replace('/\<\/?br\/?\>(<\/?br\/?>)+/', '<br/>', $outstr);
		$outstr = str_replace(' ','&nbsp;',$outstr);
		return array($outstr, $cut);

		//return $contents;
	}
}

/**
 * 截取博客内容作为摘要
 * @author jiangfangtao  update yinyancai 
 * @date 2012/06/07
 * @param $content 要截取的内容
 * @param $maxLen 要截取的最大长度
 * @param $allowTags 需要保留的标签
 */
if(!function_exists('htmlSubStringTitlte')){
	function htmlSubStringTitlte($content,$maxLen=200){
	 $content = trim($content);
	    $content = str_replace('&nbsp;', '<nbsp>', $content);
	    $content = strip_tags($content);
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

/*
 * 过滤非法字符
 * @author jiangfangtao
 * @date 2012/05/11
 * @param $content 需要过滤的内容
 * @param $type 处理方式，1为直接替换成空格；2为替换成****
 */

function strip_script($content,$type=1){
	$pattern='/<script([^>]*)>(\.*)<\/script>/i';
	if($type==1){
		$replacement='';
	}
	else{
		$replacement='***';
	}
	$content=preg_replace($pattern,$replacement,$content);
	return $content;
}

/**
 * 过滤非法id
 * @author jiangfangtao
 * @date 2012/05/11
 * @param $id 博客id
 */
function clean_id($id){
  $pattern='/^\d+$/';
  if(preg_match($pattern,$id)){
    return $id;
  }
  else{
    return false;
  }
}

/**
 * 过滤所有空格
 * @author yinyancai
 * @date 2012/07/16
 * @param 
 */
function clean_space($str){
	
	$string = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/","",$str);
	return $string;
}

	/*
	 * 	过滤普通的注入
	 *
	 * 	@author		tongxy
	 * 	@date 2012/07/11
	 * 	来源php手册实例
	 */
function PHP_slashes($string,$type='add')
{
	if ($type == 'add')	{
		if (get_magic_quotes_gpc()){
			return $string;
		}else{
			if (function_exists('addslashes')){
				return addslashes($string);
			}else{
				return mysql_real_escape_string($string);
			}
		}
	}else if ($type == 'strip'){
		return stripslashes($string);
	}else{
		die('error in PHP_slashes (mixed,add | strip)');
	}
}