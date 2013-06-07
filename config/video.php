<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 视频模块配置文件
 *
 * @author wangying qqyu
 * @version $Id
 */
$config['authcode_key'] = 'duankou';//视频模块公用密钥
$config['video_upload_url'] = 'http://192.168.12.203/upload2/index.php';
$config['recordurl'] = 'rtmp://192.168.12.203/oflaDemo/';//录制地址
$config['video_pic_domain'] = 'http://192.168.12.242/';//视频图片domain
$config['video_src_domain'] = 'rtmp://192.168.12.203/';//视频地址domain

$config['check'] = 1; //是否开启视频审核 0：关闭 1：开启
$config['transcod_url'] = 'http://192.168.12.203/upload2/';//转码服务器地址