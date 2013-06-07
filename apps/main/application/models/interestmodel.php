<?php
/**
 * 用户兴趣操作model
 */
class InterestModel extends CI_Model {

    public function __construct() {
        parent::__construct();
        require_once APPPATH . 'config/tables.php';
        $this->load->database();
    }
    
    /**
     * 添加兴趣
     * @param array     $data   数据数组
     * @param boolean   $muliti 是否批量添加
     * @return boolean
     */
    public function addUserInterest($data = array(), $muliti = true) {
        if (!$data) {
            return false;
        }

        if (!$muliti) {
            $result = $this->db->insert(USER_INTEREST, $data);
        } else {
            foreach ($data as $k => $v) {
                $result = $this->db->insert(USER_INTEREST, $v);
            }
        }

        return $result ? true : false;
    }
     /**
     * 更新兴趣
     * @param int $uid   用户id
     * @param type $data 数据数组
     * @param type $is_all 是否批量更新
     * @return boolean
     */
    public function updateUserInterest($uid = null, $data = array(), $is_all = true) {
        if (!$uid) {
            return false;
        }
        $where = array('uid' => $uid);
        if ($is_all) {
            foreach ($data as $k => $v) {
                $where['type'] = $v['type'];
                unset($v['type']);
                $result = $this->db->update(USER_INTEREST, $v, $where);
            }
        } else {
            $where['type'] = $data['type'];
            unset($data['type']);
            $result = $this->db->update(USER_INTEREST,$data, $where);
        }

        return $result ? true : false;
    }      
    /**
     * 根据uid获取用户兴趣
     * @param int $uid  用户id
     * @param int $type 兴趣类别
     * @return array | boolean
     */
    public function getUserInterest($uid = null, $type = null) {
        if (!$uid) {
            return false;
        }
        $where['uid'] = $uid;

        if ($type) {
            $where['type'] = $type;
        }

        $data = $this->db->from(USER_INTEREST)->where($where)->get()->result_array();

        if ($data) {
            $arr = array();
            foreach ($data as $value) {
                $arr[] = $value['type'];
            }
            return $arr;
        } else {
            return false;
        }
    }
}