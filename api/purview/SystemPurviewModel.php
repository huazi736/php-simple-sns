<?php

class SystemPurviewModel extends DkModel {

    const TABLE_NAME = 'system_purview';

    protected $cache_key = 'app_list';
    protected $cache_msg_box = 'main_msg_box';
    
    protected $ttl = 0;

    public function __initialize() {
        $this->init_db('system');
        $this->init_memcache('default');
    }

    /**
     * 获取系统应用能设置的权限列表
     * @param type $moudle  应用模块功能名
     */
    public function getPurviewList($moudle) {
        if (!$moudle) {
            return false;
        }

        $fields = 'purview';
        $where = array('moudle' => $moudle);
        return $this->get($fields, $where);
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
        if ($one) {
            return $this->db->limit($limit, 0)->from(self::TABLE_NAME)->where($where)->select($fields)->get()->row_array();
        } else {
            return $this->db->from(self::TABLE_NAME)->get()->result_array();
        }
    }

    //首页应用区
    function getAppList() {
        //缓存
        $data = $this->memcache->get($this->cache_key);
        if ($data === false) {
            $data = $this->_getMainList(false); 
            //cache 首页应用区          
            $this->memcache->set($this->cache_key, $data, 0, $this->ttl);
        }
        return $data;
    }

    /**
     * 获取网页应用区列表
     * @param type $all 是否为全部
     * @return string 
     */
    protected function _getMainList($all = false) {
        $pur_arr = array();
        //模块权限列表
        $module_purview = $this->db->get('system_purview')->result_array();
        foreach ($module_purview as $k => $v) {
            $tmp = json_decode($v['purview'],1);         
            $pur_arr[$v['moudle']] = $this->join_list($tmp);
        }
        unset($module_purview);

        $where['menu_status'] = 1;
        if(!$all){
            $where['is_system'] = 0;
        }
     
        //首页应用区列表
        $app_list = $this->db->get_where('main_menu', $where)->result_array();
        //replace
        foreach ($app_list as $k => $v) {
            if (isset($pur_arr[$v['menu_moudle']])) {
                $app_list[$k]['purview_list'] = $pur_arr[$v['menu_moudle']];
            } else {
                $app_list[$k]['purview_list'] = '';
            }
        }

        return $app_list;
    }
    
    /**
     * 拼接权限值
     * @param type $data 
     */
    function join_list($data) {
        $html = '';
        if ($data) {
            foreach ($data as $k => $v) {
                $html .= ",{$v['purview']}";
            }
            $html = trim($html, ',');
        }
        return $html;
    }

    /**
     * 检查和获取首页app 模块权限、功能权限
     * @param type $module
     * @return boolean 
     */
    function checkApp($module) {
        $data = $this->memcache->get($module);       
        if ($data === false) {
            $list = $this->db->get_where('main_menu', array('menu_status' => 1, 'menu_moudle' => $module))->row_array();
            if ($list) {
                $tmp = $this->db->get_where('system_purview', array('moudle' => $list['menu_moudle']))->row_array();
                $data = isset($tmp['purview']) ? $tmp['purview'] : '';
                $data = json_decode($data, 1);
                $data = $this->join_list($data);
                //cache
                $this->memcache->set($module, $data, 0, $this->ttl);
            } else {
                return false;
            }
        }

        return $data;
    }

    //获取首页发表框
    function getMsgBox() {
        $tmp = $this->memcache->get($this->cache_msg_box);
        if ($tmp === false) {
            //获取app
            $app_list = $this->_getMainList(true);
            $msg_box = $this->_getMsgList();
            $tmp = array();
            //replace
            foreach ($app_list as $k => $v) {
                if (isset($msg_box[$v['menu_moudle']])) {
                    $tmp[$k]['msg_box'] = $msg_box[$v['menu_moudle']];
                } else {
                    //$tmp[$k]['msg_box'] = array();
                    continue;
                }
                $tmp[$k]['menu_title'] = $v['menu_title'];
                $tmp[$k]['menu_moudle'] = $v['menu_moudle'];
                $tmp[$k]['purview_list'] = $v['purview_list'];
            }

            $this->memcache->set($this->cache_msg_box, $tmp, 0, $this->ttl);
        }

        return $tmp;
    }

    //获取首页发表框内菜单列表
    protected function _getMsgList() {
        $list = $this->db->get_where('msg_box', array('status' => 1))->result_array();
        $tmp = array();
        foreach ($list as $k => $v) {
            if($v['pid'] == 0) continue;
            $tmp[$v['module_name']][] = $v['name'];
        }
        return $tmp;
    }

}