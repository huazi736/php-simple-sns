<?php

/**
 * 地区信息 
 * @author niupeyuan
 */
class LocationApi extends DkApi {   
    /**
     * 根据地区id 获取地区名 如 3301 返回 中国 浙江 杭州
     * 支持批量获取
     * @param int/array $area_id 地区id
     * @param string    $pre_fix 连接符
     * @return string 
     */
    function getLocation($area_id, $pre_fix = ' ') {
        $location = DKBase::import('Location', 'location');
        return $location->getLocation($area_id, $pre_fix);
    }
    
    
    /**
     * 根据身份id 获取相应城市列表 如33  array('杭州', '')
     * @param type $area_id
     * @param type $country_id 
     */
    function getCityList($area_id, $country_id = 1){
        $location = DKBase::import('Location', 'location');
        return $location->getCityList($area_id, $country_id);
    }
    
    /**
     * 省份省份
     * @param type $area_id
     * @return type 
     */
    function getProvinceList($country_id = 1){
        $location = DKBase::import('Location', 'location');
        return $location->getProvinceList($country_id);
    }
    
    /**
     * 根据城市id 获取区县列表
     * @param type $city_id 
     */
    function getCountyList($city_id){
        $location = DKBase::import('Location', 'location');
        return $location->getCountyList($city_id);
    }
}