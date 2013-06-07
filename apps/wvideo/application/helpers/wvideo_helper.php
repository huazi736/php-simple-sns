<?php
/**
 *  获取视频地址 rtmp协议
 *  $filename 文件名 例如：video10/M00/01/C0/wKgM8k-Q-imHsthPACQZWj6RweY252.flv
 */
if ( ! function_exists('get_video_path')){
	function get_video_path($filename){
		$video_info = explode("/", $filename,3);
		return 'oflaDemo|data/'.$video_info[2];
	}
}
/**
 *  获取图片主文件或者从文件地址 
 *  $filename 例子：video10/M00/01/C0/wKgM8k-Q-imNhK8zAAAVoD6e72M251.jpg
 *  $prefix 如果是从文件，必须加后缀名
 */
if ( ! function_exists('get_img_path')){
	function get_img_path($filename, $prefix=null){
		if ($prefix) {
			$tmp = explode('.', $filename);
			return "{$tmp[0]}{$prefix}.{$tmp[1]}";
		}else {
			return "{$filename}";
		}
	}
}
if ( ! function_exists('base_url')){
	function base_url(){
		return mk_url('video/video/index');
	}
}
/**
 * 过滤字符中间空格
 * Enter description here ...
 * @param unknown_type $title
 */
if ( ! function_exists('check_string')){
	function check_string($title){
		$title = preg_replace('/[\s]+/',' ', preg_replace('/　+/', ' ',$title));	
		$title = htmlspecialchars($title,ENT_QUOTES);
		return $title;
	}
}





