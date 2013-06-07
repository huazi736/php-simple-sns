<?php
/**
 * 邮件 相关配置 
 */
return array(
    'default' => array(
        'host' => '192.168.12.148',
        'protocol' => 'tcp',
        'port' => '61613',
        //队列名
        'queue_name' => 'email.queue',
        //是否需要验证
        'auth' => false,
        'queue_username' => 'username',
        'queue_password' => 'password',
    ),
);
