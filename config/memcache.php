<?php
return array(
    'default' => array(
            'host'       => '192.168.12.252',
            'port'       => '11211',
            'timeout'    => '300',
            'persistent' => true,
               ),
    'user' => array(
            'host'       => '192.168.12.252',
            'port'       => '11211',
            'timeout'    => '300',
            'persistent' => true,
        ),
    'session' => array(
            'host'       => '192.168.12.252',
            'port'       => '11211',
            'timeout'    => '300',
            'persistent' => true,
         ),			   
         
    /*     
     * 如果使用多台服务器可以使用如下配置
    'session' => array(
            'host'       => '192.168.12.252,192.168.12.253',
            'port'       => '11211,11212', //如果有多台主机，只有一个port，则后面的都主机都使用11211端口
            'timeout'    => '300,400',   //如果有多台主机，只有一个timeout，则后面的都主机都使用300
            'persistent' => true,
         ),			   
      */   
         
);

