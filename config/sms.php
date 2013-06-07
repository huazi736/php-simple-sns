<?php

return array(
    'default' => array(
        'wgUrl' => 'http://sdkhttp.eucp.b2m.cn/sdk/SDKService?wsdl', //网关地址
        'charset' => 'GBK', //短信内容编码
        'serialNumber' => '3SDK-EMY-0130-LKUNQ', //序列号
        'password' => '075522', //密码
        'sessionKey' => '123456', //二次加密
        'connectTimeOut' => '12', //连接超时时间
        'readTimeOut' => '10', //远程读取超时时间
        'proxyhost' => false, //可选，代理服务器地址，默认为 false ,则不使用代理服务器
        'proxyport' => false, //可选，代理服务器端口，默认为 false
        'proxyusername' => false, //可选，代理服务器用户名，默认为 false
        'proxypassword' => false, //可选，代理服务器密码，默认为 false
    ),
);