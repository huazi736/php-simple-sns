<?php

/**
 * 地区信息 
 * @author niupeyuan
 */
class LocationModel extends DkModel {

    protected $nation_table = 'info_nation';
    protected $area_table = 'info_area';

    public function __initialize() {
        $this->init_db('info');
    }

    /**
     * 根据地区id 获取地区名 如 3301 返回 中国 浙江 杭州
     * 支持批量获取
     * @param int/array $area_id 地区id
     * @param string    $pre_fix 连接符
     * @return string 
     */
    function getLocation($area_id, $pre_fix = ' ') {
        if ($area_id == 1) {
            return '中国';
        }

        //是否批量
        if (is_array($area_id)) {
            foreach ($area_id as $key => $val) {
                $where[$val] = $val;
                $len = strlen($val);
                if ($len == 4) {
                    $provice_id = substr($val, 0, 2);
                    $where[$provice_id] = $provice_id;
                } elseif ($len == 2) {
                    $provice_id = $val;
                }
            }
        } else {
            $where[] = $area_id;
            $len = strlen($area_id);
            if ($len == 4) {
                $provice_id = substr($area_id, 0, 2);
                $where[] = $provice_id;
            } elseif ($len == 2) {
                $provice_id = $area_id;
            }
        }


        $area_name = '中国';
        $data = $this->db->select('area_name')->from($this->area_table)->where_in('area_id', $where)->get()->result_array();
        if ($data) {
            foreach ($data as $k => $v) {
                $area_name = $area_name . $pre_fix . $v['area_name'];
            }
        }
        return $area_name;
    }
    
    /**
     * 获取区域列表
     */
    function getCityList($area_id, $country_id = 1) {
        $this->db->from($this->area_table);
        $this->db->where('parent_id', $area_id);
        return $this->db->select('area_id,area_name')->get()->result_array();        
    }
    
    /**
     * 获取省份列表
     * @param type $country_id 
     */
    function getProvinceList($country_id = 1) {
        $where['country_id'] = $country_id;
        $where['parent_id'] = $country_id;
        
        return $this->db->select('area_id,area_name')->get_where($this->area_table, $where)->result_array();
    }
    
    /**
     * 根据城市id获取
     * @param type $city_id
     * @return type 
     */
    function getCountyList($city_id){
        $where['parent_id'] = $city_id;
        return $this->db->select('area_id,area_name')->get_where($this->area_table, $where)->result_array();
    }

}