<?php
/**
 * 短信 相关配置 
 * @author sunlufu 
 */
return array(
    'default' => array(
        'host' => '192.168.12.148',//本地服务：192.168.13.145
        'protocol' => 'tcp',
        'port' => '61613',
        //队列名
        'queue_name' => 'sms.queue',
        //是否需要验证
        'auth' => false,
        'queue_username' => 'username',
        'queue_password' => 'password'
    )
);