<?php
/* *
 * 配置文件
 */
 
//合作身份者id，以2088开头的16位纯数字
$config['partner']      = '2088801304956905';

//安全检验码，以数字和字母组成的32位字符
$config['key']          = 'se52tzqv1v8i6hne6s9o63iv07eqkwwz';

//签约支付宝账号或卖家支付宝帐户
$config['seller_email'] = 'yeyaomai123@163.com';

//页面跳转同步通知页面路径，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
$config['return_url']   = 'http://pay.duankou.com/pay/alipayresult/';

//服务器异步通知页面路径，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
$config['notify_url']   = 'http://pay.duankou.com/notity/alipaynotity/';

//默认支付方式，取值见“即时到帐接口”技术文档中的请求参数列表
$config['paymethod']   = '';

//默认网银代号，代号列表见“即时到帐接口”技术文档“附录”→“银行列表”
$config['defaultbank']   = '';


//支付宝网关地址（新）
$config['alipay_gateway']   = 'https://mapi.alipay.com/gateway.do?';

//HTTPS形式消息验证地址
$config['https_verify_url']   = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

//HTTP形式消息验证地址
$config['http_verify_url']   = 'http://notify.alipay.com/trade/notify_query.do?';


//签名方式 不需修改
$config['sign_type']    = 'MD5';

//字符编码格式 目前支持 gbk 或 utf-8
$config['input_charset']= 'utf-8';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$config['transport']    = 'http';
?>