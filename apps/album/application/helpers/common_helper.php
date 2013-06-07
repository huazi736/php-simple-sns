<?php
/**
 * 函数库
 * 
 * @author mawenpei<mawenpei@duankou.com>
 * @date <2012-02-10>
 * 
 */

/**
 * 获取变量
 * 
 * @author guzhongbin
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
	 * 获得图片后缀
	 * 
	 * @author weijian
	 * @param stirng $type
	 */
	function getImgRealType($type) {
	    if($type == 1){
	        return 'gif';
	    }elseif($type == 2){
	        return 'jpg';
	    }elseif($type == 3){
	        return 'png';
	    }else{
	        return false;
	    }
	}

/**
 * 获得图片后缀
 * 
 * @author guzhongbin
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
 * 得到图片路径
 * 
 * @author guzhongbin
 * @param string $group 组名
 * @param string $filename 文件名（不带后缀）
 * @param string $ext 文件后缀
 * @param string $thumb 缩略图名称，如果为空则表示原图
 */
function getImgPath($group, $filename, $ext, $thumb = null) 
{	
	$filename = null === $thumb ? $filename : $filename."_".$thumb;
	return "http://".config_item('fastdfs_domain')."/".$group."/".$filename.".".$ext;
}

/**
 * 得到远程图片路径
 * 
 * @author guzhongbin
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
 * 得到远程图片路径
 * 
 * @author guzhongbin
 * @param string $type 关系类型
 * @param string $uid 用户id
 * @param string $action_uid 被访问用户id
 */
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
    
/**
 * @author guzhongbin
 * @param int $type 相册类型
 * 
 * @reutrn array 相册缩略图配置
 */
function GetThumbConf($type)
{
	switch($type){
		case 1 : //个人头像
			$thumb_config = getConfig('album', 'thumb_head_sizes', 'noReturn');
			break;
		case 2 : //相册封面
			$thumb_config = getConfig('album', 'thumb_cover_sizes', 'noReturn');
			break;
		case 3 : //配图相册
			$thumb_config = getConfig('album', 'thumb_other_sizes', 'noReturn');
			break;
		case 4 : //店铺配图
			$thumb_config = getConfig('album', "thumb_goods_sizes", 'noReturn');
			break;
		default: //普通相册
			$thumb_config = getConfig('album', 'thumb_pic_sizes', 'noReturn');
	}
	
	return $thumb_config;
}
