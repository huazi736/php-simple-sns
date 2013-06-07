<?php

/**
 * 端口网测试基类
 * 
 * @author Yanguang Lan <lanyg.com@gmail.com>
 */

/**
 * 测试基类
 * 
 * 初始化 CI 实例
 *
 * @author Yanguang Lan <lanyg.com@gmail.com>
 */
class DK_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * CI 实例
     * 
     * @var object 
     */
    protected $CI = null;
    
    public function setUp()
    {
        $this->CI = &get_instance();
        if ($this->CI === null) {
            new CI_Controller();
            $this->CI = &get_instance();
        } 
    }
    
    public function tearDown()
    {
    }
}