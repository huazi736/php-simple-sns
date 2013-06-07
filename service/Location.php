<?php

/**
 * 地区信息 
 * @author niupeyuan
 */
class LocationService extends DK_Service {

    protected $nation_table = 'info_nation';
    protected $area_table = 'info_area';

    function __construct() {
        parent::__construct();
        $this->init_db('info');
    }

    /**
     * 根据id 获取地区信息
     * @param int $area_id 区域id
     * @param string $type 1 国家 2 省份  3 市
     
    function getLocation($area_id, $type = 1) {
        if ($type != 1 && $type != 2 && $type !=3) {
            return false;
        }
        if ($type == 1) {
            $this->db->from($this->nation_table);
        } else {
            $this->db->from($this->area_table);
        }

        if (is_array($area_id)) {
            $this->db->where_in('area_id', $area_id);
        } else {
            $this->db->where(array('area_id'=> $area_id));
        }

        return $this->db->select('area_name')->get()->result_array();
    }*/
    
    /**
     * 根据地区id 获取地区名 如 3301 返回 中国 浙江 杭州
     * @param type $area_id
     * @return string 
     */
    function getLocation($area_id) {
        if($area_id == 1)
        {
            return '中国';
        }
        
        $where[] = $area_id;
        $len = strlen($area_id);
        if ($len == 4) {
            $provice_id = substr($area_id, 0, 2);
            $where[] = $provice_id;
        } elseif ($len == 2) {
            $provice_id = $area_id;
        }

        $area_name = '中国';
        $data = $this->db->select('area_name')->from($this->area_table)->where_in('area_id', $where)->get()->result_array();
        foreach ($data as $k => $v) {
            $area_name = $area_name . ' ' . $v['area_name'];
        }

        return $area_name;
    }
}