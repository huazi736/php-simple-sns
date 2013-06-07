<?php

/**
 * 网页应用区菜单模型
 *
 * @author zengmingming
 * @date 2012/7/4
 */
class AppmenuModel extends MY_Model {
    /**
     * 类库定义
     */
    // 用户库
    const USER_DB = 'user';
    // 系统库
    const SYSTEM_DB = 'system';

    /**
     * USER_DB类库的表名定义
     */
    // 网页-系统应用菜单表
    const WEB_MENU = 'web_menu';

    /**
     * SYSTEM_DB类库的表名定义
     */
    // 网页-用户应用菜单表
    const USER_WEB_MENU = 'user_web_menu';

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * debug
     */
    function debug($params) {
        echo '<pre>';
        print_r($params);
        echo '</pre>';
    }

    /**
     * 获取频道的网页应用菜单ID列表
     * @param type $channelId
     * @return type 
     */
    public function getAppIds($webid) {
        if (empty($webid)) {
            return array();
        }

        $web_info = service('Interest')->get_web_info($webid);
        $main_category = service('Interest')->get_category_main($web_info['imid']);
        if ($main_category && is_array($main_category) && isset($main_category[0]['app_level'])) {
            switch ($main_category[0]['app_level']) {
                case 1:
                    $category = '';
                    break;
                case 2:
                    $category = $web_info['iid'];
                    break;
                default:
                    return array();
                    break;
            }
        } else {
            return array();
        }

        // 获取系统定义的网页应用区菜单
        $results = $this->getSysMenu($web_info['imid'], $category);

        array_walk($results, function(&$val, $key) {
                    $val = $val['menu_id'];
                });
        return $results;
    }

    /**
     * 获取网页应用区的菜单
     *
     * @param int $webid 网页ID
     * @param string $orderby 排序方式(默认按照排序值从小到大排序)
     * 
     * @return array
     */
    public function getAppMenu($webid, $orderby = 'DESC') {
        if (empty($webid)) {
            return array();
        }

        $web_info = service('Interest')->get_web_info($webid);
        $main_category = service('Interest')->get_category_main_one($web_info['imid']);

        if ($main_category && is_array($main_category) && isset($main_category[0]['app_level'])) {
            switch ($main_category[0]['app_level']) {
                case 1:
                    $category = '';
                    break;
                case 2:
                    $category = $web_info['iid'];
                    break;
                default:
                    $category = '';
                    break;
            }
        } else {
            $category = '';
        }
        
        // 获取系统定义的网页应用区菜单
        $sys_app_menu = $this->getSysMenu($web_info['imid'], $category, $orderby);
        
        if (!empty($sys_app_menu) && is_array($sys_app_menu)) {
            foreach ($sys_app_menu AS $key => $val) {
                $sys_app[$val['menu_id']] = $val;
            }
        }
        // 获取用户定义的网页应用区菜单
        $user_app_menu = $this->getUserMenu($webid, $orderby);

        $tmparr = array();
        foreach ($user_app_menu as $key => $v) {
            if (isset($sys_app[$v['menu_id']])) {
                $tmparr[$v['menu_id']] = $v;
                if (!isset($v['menu_ico']) || !$v['menu_ico']) {
                    $tmparr[$v['menu_id']]['menu_ico'] = $sys_app[$v['menu_id']]['menu_ico'];
                }
                if (!isset($v['group']) || !$v['group']) {
                    $tmparr[$v['menu_id']]['group'] = '';
                }
            }
        }
        $user_app_menu = $tmparr;

        // 菜单整合
        foreach ($sys_app_menu as $k => &$v) {
            if (isset($user_app_menu[$v['menu_id']])) {
                $v = array_merge($v, $user_app_menu[$v['menu_id']]);
            }
            $menu_sort[$k] = $v['menu_sort'];
        }

        if (!isset($menu_sort) || empty($menu_sort) || empty($sys_app_menu)) {
            return array();
        }
        array_multisort($menu_sort, SORT_DESC, $sys_app_menu);

        return $sys_app_menu;
    }

    /**
     * 获取用户定义的网页应用区菜单
     *
     * @param int $webid 网页ID
     * @param string $orderby 排序方式(默认按照排序值从小到大排序)
     *
     * @return array
     */
    public function getUserMenu($webid, $orderby = 'DESC') {
        if (empty($webid)) {
            return array();
        }

        // 连接用户库
        $this->init_db(self::USER_DB);

        $this->db->where('web_id', $webid);

        return $this->db->get(self::USER_WEB_MENU)->result_array();
    }

    /**
     * 获取系统定义的网页应用区菜单
     *
     * @param string $orderby 排序方式(默认按照排序值从小到大排序)
     *
     * @return array
     */
    public function getSysMenu($channelId, $category = '', $orderby = 'DESC') {
        // 连接系统库
        $this->init_db(self::SYSTEM_DB);

        if ($channelId) {
            // 获取指定频道的应用菜单
            // 获取有效的应用菜单
            $where = sprintf('(channel_id = %s or is_default = 1)', $channelId);

            // 获取指定频道的应用菜单
            // 获取有效的应用菜单
            if ($category) {
                // 根据频道子分类获取
                $where = sprintf('((channel_id = %s and category = %s) or is_default = 1)', $channelId, $category);
            } else {
                $where = sprintf('(channel_id = %s or is_default = 1)', $channelId);
            }
        } else {
            $where = 'is_default = 1';
        }

        $this->db->where($where);

        // 获取有效的应用菜单
        $this->db->where('menu_status', 1);

        // 应用菜单排序
        $this->db->order_by('menu_sort', $orderby);

        return $this->db->get(self::WEB_MENU)->result_array();
    }

    /**
     * 获取系统定义的网页应用区菜单
     *
     * @param string $orderby 排序方式(默认按照排序值从小到大排序)
     *
     * @return array
     */
    public function getSysMenuByCategory($channelId, $category, $orderby = 'DESC') {
        // 连接系统库
        $this->init_db(self::SYSTEM_DB);

        // 获取指定频道的应用菜单
        // 获取有效的应用菜单
        $where = sprintf('(channel_id = %s or is_default = 1)', $channelId);
        $this->db->where($where);

        // 获取有效的应用菜单
        $this->db->where('menu_status', 1);

        // 应用菜单排序
        $this->db->order_by('menu_sort', $orderby);

        return $this->db->get(self::WEB_MENU)->result_array();
    }

    /**
     * 网页应用区菜单排序
     *
     * @param int $webid 网页ID
     * @param array $sort 排序数组
     *
     * @return boolean
     */
    public function sortAppMenu($webid, $sort = array()) {
        if (empty($webid) || empty($sort)) {
            return FALSE;
        }

        // 连接用户库
        $this->init_db(self::USER_DB);

        foreach ($sort as $k => $v) {

            $where['web_id'] = $webid;
            $where['menu_id'] = $v['menu_id'];

            $data['menu_sort'] = $v['menu_sort'];

            $data = array_merge($where, array('menu_sort' => $v['menu_sort']));

            $nums = $this->db->where($where)->get(self::USER_WEB_MENU)->num_rows();
            if ($nums) {
                //更新网页应用区排序
                $this->db->update(self::USER_WEB_MENU, $data, $where);
            } else {
                //添加网页应用区排序
                $this->db->insert(self::USER_WEB_MENU, $data);
            }
            echo $this->db->last_query();
        }

        return TRUE;
    }

    /**
     * 设置网页应用区菜单的封面
     * 
     * @desc 此功能已在接口处提供, 模型中无需提供
     *
     * @return boolean
     */
    public function setAppMenuCover() {
        // TODO
    }

}