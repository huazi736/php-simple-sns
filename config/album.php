<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 相册模块配置文件
 *
 * @author vicente
 * @version $Id
 */
//图片上传加密key
$config['security_key'] = "duankou";
//缩略图 大 中 小 尺寸  (宽度,高度)
$config['thumb_pic_sizes'] = array(
	'name' => '',
	'size' => array(
        'f' => array('width' => 168, 'height' => 111, 'type' => 'resize_two'),//封面
		's' => array('width' => 170, 'height' => 130, 'type' => 'resize_two'),
		'b' => array('width' => 720, 'height' => 640, 'type' => 'resize_ratio'),
        'ts' => array('width' => 133, 'height' => 133, 'type' => 'resize_two'),
		'tm' => array('width' => 403, 'height' => 403, 'type' => 'resize_ratio'),
	),
);
//个人头像缩略图信息配置
$config['thumb_head_sizes'] = array(
	'name' => '头像相册',
	'size' => array(
        'f' => array('width' => 168, 'height' => 111, 'type' => 'resize_two'),
		's' => array('width' => 170, 'height' => 130, 'type' => 'resize_two'),
		'm' => array('width' => 180, 'height' => 135, 'type' => 'resize_two'),
		'b' => array('width' => 720, 'height' => 640, 'type' => 'resize_ratio'),
	),
);
//相册封面缩略图信息配置
$config['thumb_cover_sizes'] = array(
	'name' => '封面相册',
	'size' => array(
        'f' => array('width' => 168, 'height' => 111, 'type' => 'resize_two'),
		's' => array('width' => 170, 'height' => 130, 'type' => 'resize_two'),
		'm' => array('width' => 180, 'height' => 135, 'type' => 'resize_two'),
		'b' => array('width' => 720, 'height' => 640, 'type' => 'resize_ratio'),
	),
);
//配图相册缩略图信息配置
$config['thumb_other_sizes'] = array(
	'name' => '端口配图',
	'size' => array(
        'f' => array('width' => 168, 'height' => 111, 'type' => 'resize_two'),//封面
		's' => array('width' => 170, 'height' => 130, 'type' => 'resize_two'),
        'b' => array('width' => 720, 'height' => 640, 'type' => 'resize_ratio'),
        'ts' => array('width' => 133, 'height' => 133, 'type' => 'resize_two'),
		'tm' => array('width' => 403, 'height' => 403, 'type' => 'resize_ratio'),
	),
);
//商品配图缩略图信息配置
$config['thumb_goods_sizes']	= array(
	'name'	=> '商品配图',
	'size'	=> array(
		'f' => array('width' => 194, 'height' => 1000, 'type' => 'resize_ratio'),	// 封面展示
		's' => array('width' => 189, 'height' => 1000, 'type' => 'resize_ratio'),
		'b' => array('width' => 635, 'height' => 1000, 'type' => 'resize_ratio'),
		'ts' => array('width' => 100, 'height' => 100, 'type' => 'resize_two'),
		'bs' => array('width' => 379, 'height' => 1000, 'type' => 'resize_ratio'),
	),
);
//排序
$config['orderby'] = array(
    'album'        =>  array(
        0 => array('a_sort'       => 'desc'),
        1 => array('a_sort'       => 'asc'),
        2 => array('dateline'     => 'asc'),
        3 => array('dateline'     => 'desc'),
        4 => array('id'           => 'asc'),
        5 => array('id'           => 'desc') 
     ),
    'photo'        =>  array(
        0 => array('p_sort'       => 'desc'),
        1 => array('p_sort'       => 'asc'),
        2 => array('dateline'     => 'asc'),
        3 => array('dateline'     => 'desc'),
        4 => array('id'           => 'asc'),
        5 => array('id'           => 'desc') 
     )
);

//临时存放上传图片路径 可写权限
$config['tmp_storage_path'] = APP_ROOT_PATH . 'walbum/application/var/uploadfiles';
$config['storage_path'] = APP_ROOT_PATH . 'album/application/var/uploadfiles';
//删除多久之前的垃圾图片
$config['tmp_pics_delete_interval'] = 86400;
//图片清晰度
$config['photo_quality'] = array('normal' => 30, 'high' => 95);

//上传临时图片保存用的session名
$config['upload_list_session'] = 'upload_list';

$config['constants'] = array(
);

//图片是否远程上传
$config['is_romote_upload'] = true;

//本地
if(LOCAL_RUN){
	//图片远程上传地址
	//$config['romote_upload_url'] = "http://localhost.duankou.com/duankou_image/upload_dev/";
	//远程图片存储地址
	//$config['romote_img_url'] = "http://localhost.duankou.com/duankou_image/upload_dev/var/uploadfiles";
	
	//图片远程上传地址
	$config['romote_upload_url'] = "http://imgstore.duankou.dev/";
	//远程图片存储地址
	$config['romote_img_url'] = "http://imgstore.duankou.dev/var/uploadfiles";
}else{
	//图片远程上传地址
	$config['romote_upload_url'] = "http://imgstore".DOMAIN."/";
	//远程图片存储地址
	$config['romote_img_url'] = "http://imgstore".DOMAIN."/var/uploadfiles";	
}