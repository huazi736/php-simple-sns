<?php

/**
 * 系统权限接口
 */
class SystemPurviewApi extends DkApi {

    protected $sysPurview;
    
    public function __initialize() {
        $this->sysPurview = DKBase::import('SystemPurview', 'purview');
    }

    /**
     * 获取系统应用能设置的权限列表
     * @param type $moudle  应用模块功能名
     */
    public function getPurviewList($moudle) {
        return $this->sysPurview->getPurviewList($moudle);
    }

    /**
     * 权限读取操作
     * @param type $field   要读取的字段
     * @param type $where   查询条件
     * @param type $one     是否只读取一条记录
     * @param type $limit   查询条数
     * @return type 
     */
    public function get($fields, $where, $one = true, $limit = 1) {
        return $this->sysPurview->get($fields, $where, $one, $limit);
    }
    
    //获取系统应用区
    public function getAppList(){
        return $this->sysPurview->getAppList();
    }
    
    /**
     * 检查和获取首页app 模块权限、功能权限
     * @param type $module
     * @return boolean 
     */
    function checkApp($module) {
        return $this->sysPurview->checkApp($module);
    }
    
    //首页发表框
    function getMsgBox(){
        return $this->sysPurview->getMsgBox();
    }
}