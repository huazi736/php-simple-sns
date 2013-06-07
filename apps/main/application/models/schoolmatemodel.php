<?php
    class schoolMateModel extends CI_Model{
         public function __construct()
	{
                parent::__construct();
                require_once APPPATH . 'config/tables.php';
		$this->load->database();
	}
        
            /**
    * Created on 2011-12-16
    * @author  zhuzaiming@yeyaomai.net
    * @desc    同学同事添加
    */
    public function mateAdd($schoolId,$userId,array $inserts,$tables){
        if(!$inserts && $schoolId && $userId){
            return false;
        }
        $count=count($inserts);
        $sqlvals='';
        for($i=0;$i<$count;$i++){
            $sqlarrs[]='(\''.get_uuid().'\','.intval($schoolId).',\''.$userId.'\',\''.$inserts[$i].'\')';
        }
        if($tables==USER_JOBEXPER_WM){
            $fileds = 'id,eid,uid,workmate'; 
        }else{
            $fileds = 'id,sid,uid,classmate';
        }
        if(isset($sqlarrs)){
            $sqlarrs=join(',',$sqlarrs);
            $insertSql='insert into '.$tables.' ('.$fileds.') values '.$sqlarrs;//type字段忽略
            return $this->db->query($insertSql);
        }
    }
    
    /**
    * Created on 2011-12-16
    * @author  zhuzaiming@yeyaomai.net
    * @desc    同学同事删除
    */
    public function mateDelete($schoolId,$userId,array $deletes,$tables){
        if(!$deletes && $schoolId && $userId){
            return true;
        }
        if($tables==USER_JOBEXPER_WM){
            $fileds = 'eid';
            $mate = 'workmate';
        }else{
            $fileds = 'sid';
            $mate = 'classmate';
        }
        $deletes=join('\',\'',$deletes);
        $deleteSql='delete from '.$tables.' where uid=\''.$userId.'\' and '.$fileds.'='.intval($schoolId).' and '.$mate.' in (\''.$deletes .'\') ';
        return $this->db->query($deleteSql);
    }
    
    /**
    * Created on 2011-12-16
    * @author  zhuzaiming@yeyaomai.net
    * @desc    同学同事编辑
    */
    public function mateEdit($schoolId,$userId,array $inserts,array $deletes,$tables){
        if(!$schoolId && $userId){
            return false;
        }
        if(!($inserts || $deletes)){
            return false;
        }
        if($inserts && (!$this->mateAdd($schoolId,$userId,$inserts,$tables))){//执行操作
            return false;
        }
        if($deletes && (!$this->mateDelete($schoolId,$userId,$deletes,$tables))){
            return false;
        }
         //clear memcache
        return true;
    }
    
    /**
    * Created on 2011-12-16
    * @author  zhuzaiming@yeyaomai.net
    * @desc    获取指定学校和用户的同学
    */
   // public function getMatesBySchoolIdUserId($schoolId,$userId){
   //     if(!$userId){
   //         return false;
   //     }
   //     $sql="select * from $this->table where sid=".$schoolId." and uid='".$userId."'";
   //     return $this->db->query($sql)->result_array();
   // }
    
    /**
    * Created on 2011-12-16
    * @author  zhuzaiming@yeyaomai.net
    * @desc    获取指定学校和用户的同学
    */
//    public function getMateByUserIdSchoolId($uid,$sid){
//        return $this->db->where(array('uid'=>$uid,'sid'=>$sid))->get($this->table)->result_array();
//    }
    
    /**
    * Created on 2011-12-16
    * @author  zhuzaiming@yeyaomai.net
    * @desc    获取指定用户对应的数据
    */
//    public function getDataByUserId($uid){
//        return $this->db->where(array('uid'=>$uid))->get($this->table)->result_array();
//    }
        
    }
?>
