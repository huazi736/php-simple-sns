<?php

/**
 * 端口网测试入口文件
 * 
 * @author Yanguang Lan <lanyg.com@gmail.com>
 */

//加载常量定义文件
require dirname(__FILE__) . '/defined.inc.php';

//加载启动文件
define('CI_VERSION', '2.1.0');
require_once CONFIG_PATH . 'constants.php';
require(EXTEND_PATH . 'helpers/common_helper'.EXT);

if(function_exists('spl_autoload_register')){
    spl_autoload_register('autoload');
}

require_once BASEPATH . 'core/Controller.php';

//端口网测试基类
require 'DK_TestCase.php';