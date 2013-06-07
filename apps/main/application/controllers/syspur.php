<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 系统五个权限范围获取
 *
 * @author        liufeng
 * @date           2012/3/21
 * @description   系统五个权限范围获取

 */

class Syspur extends MY_Controller {
   
  
    
    /**
     * 获取某模块的五个权限范围
     * @param $moudle 要获取权限范围的模块
     */
    public function getSysPurviewListByMoudle()
    {
    	$moudle = P('moudle');
    	
        if( ! $moudle )
        {
        	  die(json_encode(array('state' => '0','msg' => '没有设置要获取权限的模块')));
        }
        
        $retArray=call_soap('purview', 'SystemPurview', 'getPurviewList', array($moudle));
        if( $retArray )
        {
        	 die(json_encode(array('state' => '1','msg' => '成功','data'=>$retArray)));
        }
        else 
        {
        	 die(json_encode(array('state' => '1','msg' => '获取失败')));
        }
    }
}