<?php


/* *
 * 财付通配置文件
 */
 
//合作身份者id
$config['partner']      = '1214132701';

//安全检验码，以数字和字母组成的32位字符
$config['key']          = 'YYM123shelly799987kkwqzzscingong';

$config['spname'] = '财付通双接口';

//财付通支付网关地址
$config['tenpay_gateway']   = 'https://gw.tenpay.com/gateway/pay.htm';

//页面跳转同步通知页面路径，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
$config['return_url'] = 'http://pay.duankou.com/pay/tenpayresult/';

//服务器异步通知页面路径，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
$config['notify_url'] = 'http://pay.duankou.com/notity/tenpaynotity/';



//签名方式 不需修改
$config['sign_type']    = 'MD5';

//字符编码格式 目前支持 gbk 或 utf-8
$config['input_charset']= 'utf-8';

//接口版本号
$config['service_version']    = '1.0';

//密钥序号
$config['sign_key_index']= '1';

?>