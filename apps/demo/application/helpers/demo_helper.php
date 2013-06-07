<?php
/**
 * 输出URL
 * @param string $udi 'app/controller/action'
 * @param array  $params 
 */
function echo_url($udi,$params=array())
{
    echo mk_url($udi,$params);
}