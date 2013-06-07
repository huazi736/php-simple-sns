<?php
    class isExistsmodel extends CI_Model{
         public function __construct()
	{
             parent::__construct();
                require_once APPPATH . 'config/tables.php';
		$this->load->database();
	}
        //判断学校是否存在
        public function schoolIsExists($school_id,$table){
             if(!$school_id){
                    return false;
             }
              return $this->db->select('uid')->where(array('uid'=>ACTION_UID,'schoolid'=>$school_id))->get($table)->result_array();
            
             }
        
             //公司是否已经存在
            public function companyIsExists($companyId){
                if(!$companyId){
                     return false;
                }
                    return $this->db->where(array('uid'=>ACTION_UID,'company_id'=>$companyId))->get(USER_JOBEXPER)->result_array();
            } 
            
               //奖学金是否已经存在
             public function scholarshipIsExists($title1,$title2,$starttime){
               if(!($title1 && $title2 && $starttime)){
                   return false;
               }     
               $re = $this->db->where(array('uid'=>ACTION_UID,'title1'=>$title1,'title2'=>$title2,'starttime'=>$starttime))->get(RESUME_SCHOOL)->result_array();
            }
            
            //获得奖项是否存在
            public function awardIsExists($title1,$title2,$starttime){
               if(!($title1 && $title2  && $starttime)){
                   return false;
               }
               return $this->db->where(array('uid'=>ACTION_UID,'title1'=>$title1,'title2'=>$title2,'starttime'=>$starttime))->get(RESUME_SCHOOL)->result_array();
            }
            
            //担任职务是否已经存在
            public function positionIsExists($title,$starttime,$endtime){
               if(!($title && $starttime && $endtime)){
                   return false;
               }
               return $this->db->where(array('uid'=>ACTION_UID,'title'=>$title,'starttime'=>$starttime,'endtime'=>$endtime))->get(RESUME_SCHOOL)->result_array();
            }
            
            //社会实践是否已经存在
            public function socialPracticeIsExists($title,$starttime,$endtime,$content){
               if(!($title && $starttime && $endtime && $content)){
                   return false;
               }
               return $this->db->where(array('uid'=>ACTION_UID,'title'=>$title,'starttime'=>$starttime,'endtime'=>$endtime,'content'=>$content))->get(RESUME_SCHOOL)->result_array();
            }
            
            //判断培训经历是否已经存在
            public function teachIsExists($provider,$subject){
                if(!($provider && $subject)){
                     return false;
                }
                    return $this->db->where(array('uid'=>ACTION_UID,'provider'=>$provider,'subject'=>$subject))->get(RESUME_TRAIN)->result_array();
            }
            
            //判断语言是否已经存在
            public function languageIsExists($type){
               if(!$type){
                   return false;
               }
               $result=$this->db->where(array('uid'=>ACTION_UID,'type'=>$type))->get(RESUME_LANGUAGE)->result_array();
               return $result;
           } 
           
           //判断项目是否已经存在
           public function projectIsExists($name){
               if(!$name){
                   return false;
               }
               return $this->db->where(array('uid'=>ACTION_UID,'name'=>$name))->get(RESUME_PROJECT)->result_array();
            }
            
           //判断证书是否存在
          public function bookIsExists($bookname){
               if(!$bookname){
                   return false;
               }
               return $this->db->where(array('uid'=>ACTION_UID,'name'=>$bookname))->get(RESUME_BOOK)->result_array();
          }
          
          //判断专业技能是否存在
          public function skillIsExists($type,$name){
               if(!($type && $name)){
                   return false;
               }
               return $this->db->where(array('uid'=>ACTION_UID,'type'=>$type,'name'=>$name))->get(RESUME_SKILL)->result_array();
           } 
             
    }
?>
