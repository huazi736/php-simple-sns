<?php
/**
 * 推荐模块相关配置
 *
 * @author zhoulianbo
 * @date 2012-7-19
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 允许收藏的类别
 */
return array(
	
	// 赞，评论、分享允许的类
	'allow_type' => array(
		'share' => array(
			'topic',
			'blog',
			'photo', 
			'video', 
			'album', 
			'forward', 
			'web_topic',
			'web_photo', 
			'web_video', 
			'web_album',
			'web_forward',
			'info'
		),
		'other' => array(
			'comment', 
			'web_comment',
			'event',
			'web_event',
			'ask',
			'goods',                 //网页应用,商品
			'sharevideo',
			'group',                 //个人应用，群组
			'web_dish',              //网页应用，本地生活->菜品
			'web_groupon',           //网页应用，本地生活->促销
		 
			'web_travel',			 //旅游景点
			'web_airticket'			 //机票
		),
	),

	// 收藏允许的类
	'fav_allow_types' => array(
		'blog' => '日志', 
		'photo' => '照片', 
		'video' => '视频', 
		'album' => '相册',
		'web_blog' => '网页日志', 
		'web_photo' => '网页照片', 
		'web_video' => '网页视频', 
		'web_album' => '网页相册',
	),
	
	// 收藏菜单
	'tab_type' => array(1 => '日志', 2 => '视频', 3 => '照片'),
	'tab_type_fix' => array(1 => '篇', 2 => '个', 3 => '张'),
);