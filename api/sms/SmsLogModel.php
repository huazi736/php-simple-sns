<?php

class SmsLogModel extends DkModel {

    public function __initialize() {
        $this->init_db('system');
    }

    /**
     * 添加数据
     */
    public function addSmsLog($data) {
        $data['msg_id'] = $this->get_uuid();
        $result = $this->db->insert($this->tableName, $data);
        return $result;
    }

    /**
     * 
     * 更新数据
     * @param string $id
     * @param unknown_type $data
     */
    public function updateSmsLog($id, $data) {
        $where['msg_id'] = $id;
        $result = $this->db->where($where)->update($this->tableName, $data);
        return $result;
    }

    /**
     * 判断ID是否存在
     */
    public function isExistsByID($id) {
        $result = $this->db->where(array('msg_id' => $id))->select('msg_id')->row_array();
        return $result;
    }

}