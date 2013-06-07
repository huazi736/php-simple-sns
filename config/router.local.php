<?php
$router = array();
$router['routers']['app-controller-action-dkcode'] = array(
    'rule'=>'|^\/www_duankou(?:\/)?(\w+)?(?:\/)?(\d+)(?:\/)?(\w+)?(?:\/)?(\w+)?(?:\/)?|'
);

$router['routers']['app-controller-action'] = array(
    'rule'=>'|^\/www_duankou(?:\/)?(\w+)?(?:\/)?(\w+)?(?:\/)?(\w+)?|'
);


$router['subdomain'] = array('ads','ask','channel','credit','daemon','demo','event','feedback','front','group','interest','pay','service','user','video','walbum','webmain','wevent','wiki','wvideo','openapi');
$router['nodomain'] = array('front');

$router['maps'] = array();
$router['default'] = array(
    'main' => array('controller'=>'index','action'=>'main'),
    'front' => array('controller'=>'login','action'=>'index'),
    'pay' => array('controller'=>'pay','action'=>'index'),
	'ads' => array('controller'=>'ad','action'=>'index'),
    'default' => array('controller'=>'welcome','action'=>'index'),
	'blog'	=> array('controller'=>'blog', 'action'=>'index'),
	'group'	=> array('controller'=>'group', 'action'=>'index'),
	'credit' => array('controller' => 'credit', 'action' => 'index'),
	'webmain' => array('controller' => 'index', 'action' => 'main'),
	'openapi' => array('controller' => 'user_service', 'action' => 'index'),
);

return $router;