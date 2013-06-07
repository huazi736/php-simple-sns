<?php
$router = array();
$router['routers']['app-controller-action-dkcode'] = array(
    'rule'=>'|^http:\/\/(\w+)\.duankou\.dev(?:\/)?(\d+)(?:\/)?(\w+)?(?:\/)?(\w+)?(?:\/)?(\w+)?|'
);

$router['routers']['app-controller-action'] = array(
    'rule'=>'|^http:\/\/(\w+)\.duankou\.dev(?:\/)?(\w+)?(?:\/)?(\w+)?(?:\/)?(\w+)?|'
);

$router['subdomain'] = array('ads','blog','album','ask','channel','credit','daemon','demo','feedback','event','group','interest','pay','photo','service','user','video','walbum','webmain','wevent','wiki','wvideo','netdisk','gevent','openapi');
$router['nodomain'] = array('front');

$router['maps'] = array('www'=>'main');
$router['default'] = array(
    'main'	=> array('controller'=>'index','action'=>'main'),
    'front' => array('controller'=>'login','action'=>'index'),
    'default' => array('controller'=>'index','action'=>'main'),
	'blog'	=> array('controller'=>'blog', 'action'=>'index'),
	'group'	=> array('controller'=>'group', 'action'=>'index'),
	'pay'	=> array('controller'=>'pay', 'action'=>'index'),
	'ads' => array('controller'=>'ad','action'=>'index'),
	'credit' => array('controller' => 'credit', 'action' => 'index'),
	'webmain' => array('controller' => 'index', 'action' => 'main'),
	'netdisk'=>array('controller' => 'index', 'action' => 'index'),
	'openapi' => array('controller' => 'user_service', 'action' => 'index'),
);

return $router;