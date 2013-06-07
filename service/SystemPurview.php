<?php

/**
 * 系统权限接口
 */
class SystemPurviewService extends DK_Service {
    const TABLE_NAME = 'system_purview';

    public function __construct() {
        parent::__construct();
        // Init DB
        $this->init_db('system');
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
     * 权限设置操作
     * @param type $data    key->value 权限表数组
     * @param type $all     是否批量插入数据
     * @return type 
     */
    public function insert($data, $all = false) {
        if ($data) {
            try {
                if (!$all) {
                    $data['purview'] = addslashes($data['purview']);
                    $res = $this->db->insert(self::TABLE_NAME, $data);
                } else {
                    $res = true;
                    foreach ($data as $key => $val) {
                        $data[$key]['purview'] = addslashes($data[$key]['purview']);

                        if (!$this->db->insert(self::TABLE_NAME, $item)) {
                            $res = false;
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
            return $res ? true : false;
        }
        return false;
    }

    /**
     * 权限更新操作
     * @param type $data    key->value权限表数组
     * @param type $where   更新条件
     * @return type 
     */
    public function update($data, $where) {
        if ($data && $where) {
            try {
                return $this->db->update(self::TABLE_NAME, $data, $where);
            } catch (Exception $e) {
                return $e->getMessage();
            }
            return true;
        }
        return false;
    }

    /**
     * 权限删除操作
     * @param type $where   删除条件
     * @return type         模块ID
     */
    public function delete($where) {
        if ($where) {
            try {
                return $this->db->delete(self::TABLE_NAME, $where);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            return false;
        }
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
            return $this->db->from(self::TABLE_NAME)->where($where)->limit(0, $limit)->select($fields)->get()->result_array();
        } else {
            return $this->db->from(self::TABLE_NAME)->get()->result_array();
        }
    }

}