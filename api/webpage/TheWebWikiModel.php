<?php

class TheWebWikiModel extends DkModel {

    public function __initialize() {
        $this->init_mongo("webwiki");
        $this->mongodb->switch_db("wiki_duankou");
    }

    /**
     * 获取
     * @author guojianhua
     * @date 2012/07/12
     * @param int | array $web_id
     * @param int $length
     * @return Array
     */
    function getWebDesc($web_id = array(), $length = 140) {
        if (is_array($web_id)) {
            $where = $web_id;
        } else {
            $where = array($web_id);
        }

        $items = $this->mongodb->where_in("web_id", $where)->get('wiki_web_info');
        if (!count($items))
            return array();

        //组装查询条件，wiki_module_version
        $where_or = array();
        foreach ($items as $val) {
            $where_or[] = array('item_id' => $val['item_id'], 'version' => $val['use_module_version']);
        }
        $fields = array('item_id', 'description', 'edit_datetime', 'uid', 'version');
        $modules = $this->mongodb->where(array('$or' => $where_or))->select($fields)->get("wiki_module_version");
        return $this->array_merge_by_itemid($items, $modules, $length);
    }

    /**
     * 根据item_id组装信息
     * @param array $items
     * @param array $modules
     * @param int $length
     * @return array
     */
    protected function array_merge_by_itemid($items, $modules, $length) {
        $result = array();
        foreach ($items as $item) {
            $result[$item['web_id']]['web_id'] = $item['web_id'];

            foreach ($modules as $key => $val) {
                if ($val['item_id'] == $item['item_id']) {
                    $result[$item['web_id']]['description'] = mb_substr($val['description'], 0, $length);
                    $result[$item['web_id']]['edit_datetime'] = $val['edit_datetime'];
                    $result[$item['web_id']]['uid'] = $val['uid'];
                    $result[$item['web_id']]['version'] = $val['version'];
                    unset($modules[$key]);
                }
            }
        }

        return $result;
    }

}