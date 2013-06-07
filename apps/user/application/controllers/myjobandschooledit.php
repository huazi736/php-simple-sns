<?php

    /**
     * 用户资料(工作、教育、在校情况)增、删、改控制器
     * @author chenxujia
     * @date   2012/3/22
     */


class MY_JobAndSchoolEdit extends MY_Controller{
	private $school_month = '01';
    public function __construct() {
        parent::__construct();
        $this->load->model('myeditmodel','myedit');
        $this->load->model('userwikimodel', 'userwiki');
        include_once ( APPPATH . 'helpers/dkpair.php'); 
    }
    
      /**
     * 获取访问者的uid
     *
     * @author liyundong
     * @date   2012/3/22
     * @param  $dkcode  int  访问者端uid
     * @access 
     * @return array / false
     */
    function getVisterUid($dkcode) {
        $this->load->model('userwikimodel','userwiki');
        return $this -> userwiki -> getVUid($dkcode);
    }
    
    
    /**
     * 获取用户对应模块的权限
     * @author chenxujia
     * @date   2012/3/22
     * @param  $type 类型如edu
     * @access private
     * @return true/false
     */
    private function getPermission($type)
    {
            if (!$type)
            {
                    return false;
            }
            $this -> load -> model('singleaccessmodel', '_access', true);
           return  $this -> _access -> getAccess($type, $this->uid);
    }
       
    
    /**
     * 用户基本资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $keys post数据和对应字段
     * @access public
     * @return true/false
     */
    public function baseEdit(array $keys){
    	$this -> myedit->refreshes($this->uid);
        if(!($keys)){
            return false;
        }
        
        if(!$oldVals=$this->myedit->getUserByUId($this->uid)){
            return false;
        }        
        if(!$usefulKeys=filterKeys($keys,$oldVals)){
            return false;
        }        
        if(!$this->myedit->baseEditDo($keys,$usefulKeys,$this->uid)){
            return false;
        }
        return true;
    }
    
    /**
     * 过滤同学同事的空数据
     * @author chenxujia
     * @date   2012/3/27
     * @param   $data 同学同事数组
     * @access public
     * @return array $daas 出去空值的数组
     */ 
    
    function filterNull($data){
        if(empty ($data)){
            return false;
        }else{
            if(is_array($data)){
                 $datas = array();
                foreach($data as $key => $val){
                   if(!empty ($val)){
                       $datas[$key] = $val;
                   }
                }
            }else{
                $datas[] = $data;
            }
        }
        return $datas;
    }
    
    /**
     * 查询用户生日是否已经填写
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    
    function hasValues(){
        return $this->myedit->hasValues($this->uid);
    }
    
    /**
     * 用户大学资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
     public function universityAdd(){
     	$this -> myedit->refreshes($this->uid);
        $this->load->model('isexistsmodel','isexists');
        if($this->isexists->schoolIsExists(P('schoolId'),USER_UNIVERSITY)){
            $this->ajaxReturn('', L('该学校已添加！'), 0);//school_exists
        }
		//获取数据
		$school_year = P('school_year');
		$classmate = P('classmate');
		$school_name = P('school_name');
		$classmate = explode("," , $classmate);
        $this->school_month=P('school_month');
        $_POST['school_year'] = $school_year = $this->get_school_date($school_year, $this->school_month);        
        $_POST['dateline']=time();
        $time = $this->userwiki->getEduTime($this->uid, 'university');
        $earliestUni = strtotime($time['earliestUni']);
        if($_POST['school_year'] < $earliestUni){
        $this->ajaxReturn('', L('入学时间不能早于高中！'), 0);//operate_faild	
        }
        
        if(!empty($classmate)){
            $classmate = $this->filterNull($classmate);
			//判断同学数量是否超出限制10个
			if(10 < sizeof($classmate)){
				//json_encodes(0,L('同学最多添加10个！'));
			}
			//bohailiang modify 2012/3/31
			if(!empty($classmate)){
			    $classmate = $this->filterUid($classmate);
				$classmate = implode(',', $classmate);
			}
			$_POST['classmate'] = json_encode($classmate);
        }
        $id=$this->myedit->schoolAdd($this->addKeys,true,USER_UNIVERSITY,$this->uid);        
        if($id){
			$permission = $this->getPermission('edu');
			$timedata = array(
		        'dkcode'=>$this->dkcode,
				'uid'=>$this->uid,
				//'fid'=>$_POST['dateline'],
				'fid'=>$id . '_edu',
				'uname'=>$this->user['username'],
				'content'=>'开始于：' . $school_name,
				'type'=>'uinfo',
				'subtype'=>'edu',
				'info'=> date('Y年n月', $school_year) . ' 大学',
				'permission'=>$permission['object_type'],
				'dateline'=>$school_year
			);			
			if(-1 == $permission['object_type'] && !empty($permission['object_content'])){
				$uid_arr = $permission['object_content'];				
				//入住时间轴
				$result = service('Timeline')->addTimeLine($timedata, $uid_arr);				
			} else {
				//入住时间轴											
				$result = service('Timeline')->addTimeLine($timedata);				
			}
			//更新索引
		    $rss = service('RestorationSearch')->restoreUserInfo($this->uid);  
         			
			$this->ajaxReturn(array('tid'=>$id), L('操作成功！'), 1);//operate_success			
        }else{
            $this->ajaxReturn('', L('操作失败！'), 0);//operate_faild
        }  
    }

    
    
    
    /**
     * 对存在的数据过滤
     * @author chenxujia
     * @date   2012/3/22
     * @param  $exists旧数据,$names新数据
     * @access public
     * @return array $vals
     */
        private function exists_filter(array $exists,array $names){
        if(!($exists && $names)){
            return false;
        }
        $count=count($names);
        $vals=array();
        for($i=0;$i<$count;$i++){
            if(!in_array($names[$i], $exists)){
                $vals[]=$names[$i];
            }
        }
        return $vals;
    }
    
    /**
     * 根据id获取对应的数据
     * @author chenxujia
     * @date   2012/3/22
     * @param  $id数据的id,$tables表名
     * @access public
     * @return true/false
     */
    
     private function getDataById($id,$tables){
        if(!$id){
            return false;
        }
        $rs = $this->myedit->getDataBy($id, $tables);
        return $rs;
    }
    
    
    /**
     * 用户大学资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function universityEdit(){
    	$this -> myedit->refreshes($this->uid);
        $id =P('tid');
        $keys = $this->editKeys;
        if(!($id && $keys)){
            $this->ajaxReturn('',L('数据丢失！'), 0);//lost para
        }
		//获取数据
		$school_year = P('school_year');
		$classmate = P('classmate');		
	    $classmate = explode(',', $classmate);	  
		$school_name = P('school_name');
        $this->school_month=P('school_month');
        if(!empty($classmate)){
            $classmate = $this->filterNull($classmate);
			//判断同学数量是否超出限制10个
			if(10 < sizeof($classmate)){
				//json_encodes(0,L('同学最多添加10个！'));
			}
			//bohailiang modify 2012/3/31
			if(!empty($classmate)){
			    $classmate = $this->filterUid($classmate);
				$classmate = implode(',', $classmate);
			}
			$_POST['classmate'] = json_encode($classmate);
            //$_POST['classmate'] = json_encode(implode(',', $classmate));
			//modify end
        }
        $_POST['school_year'] = $school_year = $this->get_school_date($school_year, $this->school_month);
        $time = $this->userwiki->getEduTime($this->uid, 'university');
        $earliestUni = strtotime($time['earliestUni']);
        if($_POST['school_year'] < $earliestUni){
        $this->ajaxReturn('', L('入学时间不能早于高中！'), 0);//operate_faild	
        }        
        if(!$oldVals=$this->getDataById($id,USER_UNIVERSITY)){
            $this->ajaxReturn('', L('信息未找到！'), 0);//data_not_found
        }
        if(!$usefulKeys=filterKeys($keys,$oldVals)){//或许需要修改的值
            return true;//没有需要修改的东西
        }
        if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,USER_UNIVERSITY)){
            $this->ajaxReturn('', L('操作失败！'), 0);//operate_fail
        }else{
            $times = $school_year;
            //$onlytime = $oldVals['dateline'];
            $onlytime = $id . '_edu';
            if($times){
                //$times = strtotime($times);
                $result = service('Timeline')->updateCtimeByMap($onlytime,'uinfo',$this->uid,$times);
				//更新学校info
				$data = array('fid' => $onlytime, 'info'=> date('Y年n月', $school_year) . ' 大学', 'type'=>'uinfo', 'uid'=>$this->uid);
				$tt = service('Timeline')->updateTopic($data);
            }
            //更新索引
		    $rss = service('RestorationSearch')->restoreUserInfo($this->uid);  
            $this->ajaxReturn('' ,L('操作成功！'), 1);//operate_success
        }
    }
    
     /**
     * 用户中学资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function highSchoolAdd(){
    	$this -> myedit->refreshes($this->uid);
        $this->load->model('isexistsmodel','isexists');
        if($this->isexists->schoolIsExists(P('schoolId'),USER_UNIVERSITY)){
            json_encodes(0,L('该学校已添加！'));//school_exists
        }
        $_POST['dateline']=time();        
		//获取数据
		$classmate = P('classmate');
		$school_year = P('school_year');		
		$school_name = P('school_name');
		$classmate = explode(',' , $classmate);
        $this->school_month=P('school_month');
        if(!empty($classmate)){
            $classmate = $this->filterNull($classmate);
			//判断同学数量是否超出限制10个
			if(10 < sizeof($classmate)){
				//json_encodes(0,L('同学最多添加10个！'));
			}
			//bohailiang modify 2012/3/31
			if(!empty($classmate)){
			    $classmate = $this->filterUid($classmate);
				$classmate = implode(',', $classmate);
			}
			$_POST['classmate'] = json_encode($classmate);
            //$_POST['classmate'] = json_encode(implode(',', $classmate));
			//modify end
        }        
        $_POST['school_year'] = $school_year = $this->get_school_date($school_year, $this->school_month);       
        $time = $this->userwiki->getEduTime($this->uid, 'highSchool');
        $earliestHighSchool = strtotime($time['earliestHighSchool']);
        $lastHighSchool = strtotime($time['lastHighSchool']);        
        if(($_POST['school_year'] < $earliestHighSchool) || ($_POST['school_year'] > $lastHighSchool && $lastHighSchool != 0)){
        $this->ajaxReturn('', L('入学时间不能低于小学或者高于大学！'), 0);//operate_faild	
        }     
        $id = $this->myedit->schoolAdd($this->addKeys,true,USER_UNIVERSITY,$this->uid);
        if($id){
			$permission = $this->getPermission('edu');
			$timedata = array(
				'dkcode'=>$this->dkcode,
                'uid'=>$this->uid,
				//'fid'=>$_POST['dateline'],
				'fid'=>$id . '_edu',
				'uname'=>$this->user['username'],
				'content'=>'开始于：'.$school_name,
				'type'=>'uinfo',
				'subtype'=>'edu',
				'info'=> date('Y年n月', $school_year) . ' 中学',
				'permission'=>$permission['object_type'],
				'dateline'=>$school_year
            );
			if(-1 == $permission['object_type'] && !empty($permission['object_content'])){
				$uid_arr = $permission['object_content'];
				//入住时间轴
				$result = service('Timeline')->addTimeLine($timedata, $uid_arr);
			} else {
				//入住时间轴
				$result = service('Timeline')->addTimeLine($timedata);
			}
		    //更新索引
		    $rss = service('RestorationSearch')->restoreUserInfo($this->uid);  
			$this->ajaxReturn(array('tid'=>$id), L('操作成功！'), 1);//operate_success
        }else{
            $this->ajaxReturn('', L('操作失败！'), 0);//operate_faild
        }  
    }
    
    /**
     * 用户中学资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function highSchoolEdit(){
    	$this -> myedit->refreshes($this->uid);
		$id =P('tid');
		$keys = $this->editKeys;
		if(!($id && $keys)){
			$this->ajaxReturn('',L('数据丢失！'), 0);//lost para
		}

		//获取数据
		$classmate = P('classmate');
		$classmate = explode(',', $classmate);
		$school_year = P('school_year');
        $this->school_month=P('school_month');
		if(isset($classmate)){
			 $classmate = $this->filterNull($classmate);
            
			//判断同学数量是否超出限制10个
			if(10 < sizeof($classmate)){
				//json_encodes(0,L('同学最多添加10个！'));
			}
			//bohailiang modify 2012/3/31
			if(!empty($classmate)){
               $classmate = $this->filterUid($classmate);
				$classmate = implode(',', $classmate);
			}
			$_POST['classmate'] = json_encode($classmate);
			//$_POST['classmate'] = json_encode(implode(',', $classmate));
			//modify end
			
		}
		if(!$oldVals=$this->getDataById($id,USER_UNIVERSITY)){
			json_encodes(0,L('信息未找到！'));//data_not_found
		}
		$_POST['school_year'] = $school_year = $this->get_school_date($school_year, $this->school_month);
        $time = $this->userwiki->getEduTime($this->uid, 'highSchool');
        $earliestHighSchool = strtotime($time['earliestHighSchool']);
        $lastHighSchool = strtotime($time['lastHighSchool']);
        if(($_POST['school_year'] < $earliestHighSchool) || ($_POST['school_year'] > $lastHighSchool && $lastHighSchool != 0)){
        $this->ajaxReturn('', L('入学时间不能低于小学或者高于大学！'), 0);//operate_faild	
        }        	
		if(!$usefulKeys=filterKeys($keys,$oldVals)){
			return true;//没有需要修改的东西
		}
		if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,USER_UNIVERSITY)){
			$this->ajaxReturn('',L('操作失败！'), 0);//operate_fail
		}else{
			$times = $school_year;
            //$onlytime = $oldVals['dateline'];
            $onlytime = $id . '_edu';
			if($times){
				//$times = strtotime($times);
				$result = service('Timeline')->updateCtimeByMap($onlytime,'uinfo', $this->uid, $times);
				//更新学校info
				$data = array('fid' => $onlytime, 'info'=> date('Y年n月', $school_year) . ' 中学', 'type'=>'uinfo', 'uid'=>$this->uid);
				$tt = service('Timeline')->updateTopic($data);
			}
		    //更新索引
		    $rss = service('RestorationSearch')->restoreUserInfo($this->uid);
			$this->ajaxReturn('',L('操作成功！'), 1);//operate_success
		}
	}
    
    /**
     * 用户小学资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function primarySchoolAdd(){
    	$this -> myedit->refreshes($this->uid);
        $this->load->model('isexistsmodel','isexists');
        if($this->isexists->schoolIsExists(P('schoolId'),USER_UNIVERSITY)){
           $this->ajaxReturn('', L('该学校已添加'), 0);//school_exists
        }
        $_POST['dateline']=time();

		//获取数据
		$classmate = P('classmate');
		$classmate = explode(',' , $classmate);
		$school_year = P('school_year');
		$school_name = P('school_name');
        $this->school_month=P('school_month');
        if(isset($classmate)){
             $classmate = $this->filterNull($classmate);
			//判断同学数量是否超出限制10个
			if(10 < sizeof($classmate)){
				//json_encodes(0,L('同学最多添加10个！'));
			}
			//bohailiang modify 2012/3/31
			if(!empty($classmate)){
			    $classmate = $this->filterUid($classmate);
				$classmate = implode(',', $classmate);
			}
			$_POST['classmate'] = json_encode($classmate);
            //$_POST['classmate'] = json_encode(implode(',', $classmate));
			//modify end
        }
       $_POST['school_year'] = $school_year = $this->get_school_date($school_year, $this->school_month);
       $time = $this->userwiki->getEduTime($this->uid, 'primarySchool');
       $lastPrimary = strtotime($time['lastPrimary']);     
       if($_POST['school_year'] > $lastPrimary && $lastPrimary !=0){
       $this->ajaxReturn('', L('入学时间不能高于中学！'), 0);//operate_faild	
       }       
       $id=$this->myedit->schoolAdd($this->addKeys,true,USER_UNIVERSITY,$this->uid);
        if($id){
			$permission = $this->getPermission('edu');
			$timedata = array(
				'dkcode'=>$this->dkcode,
				'uid'=>$this->uid,
				//'fid'=>$_POST['dateline'],
				'fid'=>$id . '_edu',
				'uname'=>$this->user['username'],
				'content'=>'开始于：'.$school_name,
				'type'=>'uinfo',
				'subtype'=>'edu',
				'info'=> date('Y年n月', $school_year) . ' 小学',
				'permission'=>$permission['object_type'],
				'dateline'=>$school_year
			 );
			if(-1 == $permission['object_type'] && !empty($permission['object_content'])){
				$uid_arr = $permission['object_content'];
				//入住时间轴
				$result = service('Timeline')->addTimeLine($timedata, $uid_arr);
			} else {
				//入住时间轴
				$result = service('Timeline')->addTimeLine($timedata);
			}
		    //更新索引
		    $rss = service('RestorationSearch')->restoreUserInfo($this->uid);
            $this->ajaxReturn(array('tid'=>$id),L('操作成功！'), 1);//operate_success
        }else{
            $this->ajaxReturn('', L('操作失败！'), 0);//operate_faild
        }  
    }
    
    
    /**
     * 用户小学资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function primarySchoolEdit(){
    	$this -> myedit->refreshes($this->uid);
        $boolvals=false;
        $return = true;
        $id =P('tid');
        $keys = $this->editKeys;
        if(!($id && $keys)){
            $this->ajaxReturn('',L('数据丢失！'), 0);//lost para
        }

		//获取数据
		$classmate = P('classmate');	   
		$classmate = explode(',' , $classmate);
		$school_year = P('school_year');
        $this->school_month=P('school_month');
        if(isset($classmate)){
             $classmate = $this->filterNull($classmate);
			//判断同学数量是否超出限制10个
			if(10 < sizeof($classmate)){
				//json_encodes(0,L('同学最多添加10个！'));
			}
			//bohailiang modify 2012/3/31
			if(!empty($classmate)){
			    $classmate = $this->filterUid($classmate);
				$classmate = implode(',', $classmate);
			}
			$_POST['classmate'] = json_encode($classmate);
            //$_POST['classmate'] = json_encode(implode(',', $classmate));
			//modify end
        }
        $_POST['school_year'] = $school_year = $this->get_school_date($school_year, $this->school_month);
        $time = $this->userwiki->getEduTime($this->uid, 'primarySchool');
        $lastPrimary = strtotime($time['lastPrimary']);     
        if($_POST['school_year'] > $lastPrimary && $lastPrimary !=0){
        $this->ajaxReturn('', L('入学时间不能高于中学！'), 0);//operate_faild	
        }        
        if(!$oldVals=$this->getDataById($id,USER_UNIVERSITY)){
            $this->ajaxReturn('',L('信息未找到！'),0);//data_not_found
        }
        if(!$usefulKeys=filterKeys($keys,$oldVals)){
            return true;//没有需要修改的东西
            //json_encodes(0,L('操作失败！'));//operate_fail
        }
        if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,USER_UNIVERSITY)){
            $this->ajaxReturn('',L('操作失败！'),0);//operate_fail
        }else{
			$times = $school_year;
            //$onlytime = $oldVals['dateline'];
            $onlytime = $id . '_edu';
			if($times){
				//$times = strtotime($times);
				$result = service('Timeline')->updateCtimeByMap($onlytime,'uinfo', $this->uid, $times);
				//更新学校info
				$data = array('fid' => $onlytime, 'info'=> date('Y年n月', $school_year) . ' 小学', 'type'=>'uinfo','uid'=>$this->uid);
				$tt = service('Timeline')->updateTopic($data);
			}
		    //更新索引
		    $rss = service('RestorationSearch')->restoreUserInfo($this->uid);
            $this->ajaxReturn('',L('操作成功！'),1);//operate_success
        }
    }
    
    /**
     * 用户大学资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function universityDelete(){
    	$this -> myedit->refreshes($this->uid);
		$id = P('tid');
        if(!$oldVals=$this->getDataById($id,USER_UNIVERSITY)){
            $this->ajaxReturn('',L('信息未找到！'),0);//data_not_found
        }
        if(!$this->delete($id,$this->uid,USER_UNIVERSITY)){
            $this->ajaxReturn('',L('操作失败！'), 0);//operate_fail
        }else{
            //删除时间
            //$onlytime = $oldVals['dateline'];
            $onlytime = $id . '_edu';
            $data = array( 'fid'=>$onlytime, 'type'=>'uinfo');
	    	$result = service('Timeline')->removeTimeline($onlytime,$this->uid, 'uinfo');
        }
       //更新索引
	   $rss = service('RestorationSearch')->restoreUserInfo($this->uid);
        $this->ajaxReturn('',L('操作成功！'),1);//operate_success        
        
    }
    
    
    /**
     * 用户中学资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function highSchoolDelete(){
    	$this -> myedit->refreshes($this->uid);
		$id = P('tid');
        if(!$oldVals=$this->getDataById($id,USER_UNIVERSITY)){
            $this->ajaxReturn('',L('信息未找到！'),0);//data_not_found
        }
        if(!$this->delete($id,$this->uid,USER_UNIVERSITY)){
            $this->ajaxReturn('',L('操作失败！'),0);//operate_fail
        }else{
            //$onlytime = $oldVals['dateline'];
            $onlytime = $id . '_edu';
            $data = array( 'fid'=>$onlytime, 'type'=>'uinfo');
			$result = service('Timeline')->removeTimeline($onlytime,$this->uid, 'uinfo');
        }
        //更新索引
		$rss = service('RestorationSearch')->restoreUserInfo($this->uid);
        $this->ajaxReturn('',L('操作成功！'), 1);//operate_success
    }
    
    /**
     * 用户小学资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function primarySchoolDelete(){
    	$this -> myedit->refreshes($this->uid);
		$id = P('tid');
        if(!$oldVals=$this->getDataById($id,USER_UNIVERSITY)){
            $this->ajaxReturn('',L('信息未找到！'),0);//data_not_found
        }
        if(!$this->delete($id,$this->uid,USER_UNIVERSITY)){
            $this->ajaxReturn('',L('操作失败！'),0);//operate_fail
        }else{
            //$onlytime = $oldVals['dateline'];
            $onlytime = $id . '_edu';
            $data = array( 'fid'=>$onlytime, 'type'=>'uinfo');
			$result = service('Timeline')->removeTimeline($onlytime,$this->uid, 'uinfo');
        }
        //更新索引
		$rss = service('RestorationSearch')->restoreUserInfo($this->uid);
        $this->ajaxReturn('',L('操作成功！'),1);//operate_success
    }
    
    /**
     * 用户资料公共删除方法
     * @author chenxujia
     * @date   2012/3/22
     * @param  $id数据id,$uid用户id,$tables表名
     * @access public
     * @return true/false
     */
    public function delete($id,$uid,$tables){
        if(!($id && $uid)){
            return false;
        }
        $rs = $this->myedit->deleteData($tables,$id,$uid);
        return $rs;
        
    }
    
    
/**
     * 用户工作资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return $id
     */
    public function jobAdd(){
    	$this -> myedit->refreshes($this->uid);
        $keys = $this->keys;       
        $this->load->model('isexistsmodel','isexists');
        if($this->isexists->companyIsExists(P('company_id'))){
            $this->ajaxReturn('',L('该公司已添加！'),0);//company_exists
        }

		//获取已添加的工作情况记录数
		$count = $this->get_count('work');
		//判断记录数是否已达到限制
		if(5 <= $count){
			$this->ajaxReturn('',L('工作情况最多可添加5条！'),0);
		}

		//获取数据
		$time_m_s = P('time_m_s');
		$time_y_s = P('time_y_s');
		$today = P('today');
		$time_m_e = P('time_m_e');
		$time_y_e = P('time_y_e');
		$colleague = P('colleague');
		$company = P('company');
		$position = P('position');
		
        $colleague = explode(',' , $colleague);
        $_POST['startdate'] = mktime(0, 0, 0, $time_m_s+1, 0, $time_y_s);
        if(!empty($today)){
			//modify by bohailiang 2012/4/1
            //$_POST['enddate'] = 1;//至今还在工作
            $_POST['enddate'] = 0;//至今还在工作
			//modify end
        }else{
            $_POST['enddate']= mktime(0, 0, 0, $time_m_e+1, 0, $time_y_e);
        }       
        if(!empty($colleague)){
            $colleague = $this->filterNull($colleague);
			//判断同事数量是否超出限制10个
			if(10 < sizeof($colleague)){
				//json_encodes(0,L('同事最多添加10个！'));
			}
			//bohailiang modify 2012/3/31
			if(!empty($colleague)){
			    $colleague = $this->filterUid($colleague);
				$colleague = implode(',', $colleague);
			}
			$_POST['colleague'] = json_encode($colleague);
            //$_POST['colleague'] = json_encode(implode(',', $colleague));
			//modify end
        }
        $_POST['dateline'] = time();
		$usefulKeys=array_flip($keys);		
		if(!$keyVals=getKeyVals($keys,$usefulKeys)){
			return false;
        }       
        $keyVals=array_merge($keyVals, array('uid'=>$this->uid));        
        $id = $this->myedit->insertData(USER_JOBEXPER,$keyVals);
        if(!$id){
            $this->ajaxReturn('',L('操作失败！'),0);//operate_fail
        }else{			
			$permission = $this->getPermission('job');
			$uid_arr = array();
			if(-1 == $permission['object_type'] && !empty($permission['object_content'])){
				$uid_arr = $permission['object_content'];
			}
			if($_POST['startdate']){
				$startdata = array(
				     'dkcode'=>$this->dkcode,
					'uid'=>$this->uid,
					//'fid'=>$_POST['dateline'] . '_s',
					'fid'=>$id . '_job_s',
					'uname'=>$this->user['username'],
					'content'=>'开始于：'.$company,
					'type'=>'uinfo',
					'subtype'=>'job',
					'info'=>'于' . date('Y年n月', $_POST['startdate']) . '任' . $position,//'工作'
					'permission'=>$permission['object_type'],
					'dateline'=>$_POST['startdate']
				);
				if(empty($uid_arr)){
					//入住时间线  工作开始
					$result = service('Timeline')->addTimeLine($startdata);
				} else {
					//入住时间线  工作开始
					$result = service('Timeline')->addTimeLine($startdata, $uid_arr);
				}
			}
			if($_POST['enddate'] > 0){//已经离职了就写入，否则不写入
				$enddata = array(
				    'dkcode'=>$this->dkcode,
					'uid'=>$this->uid,
					//'fid'=>$_POST['dateline'] . '_e',
					'fid'=>$id . '_job_e',
					'uname'=>$this->user['username'],
					'content'=>'结束于：'.$company,
					'type'=>'uinfo',
					'subtype'=>'job',
					'info'=>'于' . date('Y年n月', $_POST['startdate']) . '任' . $position,//'工作'
					'permission'=>$permission['object_type'],
					'dateline'=>$_POST['enddate']
				);
				if(empty($uid_arr)){
					//入住时间线  工作结束
					$result = service('Timeline')->addTimeLine($enddata);
				} else {
					//入住时间线  工作结束
					$result = service('Timeline')->addTimeLine($enddata, $uid_arr);
				}
			}
		    //更新索引
		    $rss = service('RestorationSearch')->restoreUserInfo($this->uid);
            $this->ajaxReturn(array('tid'=>$id),L('操作成功！'),1);//operate_success
        }
    }
    

    /**
     * 用户工作资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function jobEdit(){
    	$this -> myedit->refreshes($this->uid);
		//获取数据
		$time_m_s = P('time_m_s');
		$time_y_s = P('time_y_s');
		$today = P('today');
		$time_m_e = P('time_m_e');
		$time_y_e = P('time_y_e');
		$colleague = P('colleague');
		$company = P('company');
		$position = P('position');
        $colleague = explode(',' , $colleague);
        if(isset($time_y_s) && isset($time_m_s)){
            $_POST['startdate']=  mktime(0, 0, 0, $time_m_s+1, 0, $time_y_s);
        }
        if(isset($_POST['today'])){
            $_POST['enddate'] = 0;
        }else{
            $_POST['enddate'] = mktime(0, 0, 0, $time_m_e+1, 0, $time_y_e);
        }
        if(isset($colleague)){
            $colleague = $this->filterNull($colleague);
			//判断同事数量是否超出限制10个
			if(10 < sizeof($colleague)){
				//json_encodes(0,L('同事最多添加10个！'));
			}
			//bohailiang modify 2012/3/31
			if(!empty($colleague)){
			     $colleague = $this->filterUid($colleague);
				$colleague = implode(',', $colleague);
			}
			$_POST['colleague'] = json_encode($colleague);
            //$_POST['colleague'] = json_encode(implode(',', $colleague));
			//modify end
        }
        $id = P('tid');
        $keys = $this->keys;
        if(!($id && $keys)){
            $this->ajaxReturn('',L('数据丢失！'),0);//lost para
        }
        if(!$oldVals=$this->getDataById($id,USER_JOBEXPER)){
            $this->ajaxReturn('',L('信息未找到！'),0);//data_not_found
        }
        if(!$usefulKeys=filterKeys($keys,$oldVals)){
            return true;//没有需要修改的东西
        }
         if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,USER_JOBEXPER)){
            $this->ajaxReturn('',L('操作失败！'),0);//operate_fail
        }else{
			$starttimes = $_POST['startdate'];
			if($starttimes){
				//$onlytime = $oldVals['dateline'] . '_s';
				$onlytime = $id . '_job_s';
				$result = service('Timeline')->updateCtimeByMap($onlytime,'uinfo',$this->uid,$starttimes);

				//更新职位
				$data = array('fid' => $onlytime, 'info' => '于' . date('Y年n月', $starttimes) . '任' . $position, 'type'=>'uinfo', 'uid'=>$this->uid);
				$tt = service('Timeline')->updateTopic($data);
			}
			$endtimes = $_POST['enddate'];
			if($endtimes > 0 ){
			    //删除工作结束时间线
			    //$onlytime = $oldVals['dateline'] . '_e';
			    $onlytime = $id . '_job_e';
			    $data = array( 'fid'=>$onlytime, 'type'=>'uinfo', 'uid'=>$this->uid );
			    $result = service('Timeline')->removeTimeline($onlytime, $this->uid,'uinfo');
				//有工作结束时间，添加新的工作结束时间线
				$permission = $this->getPermission('job');
				$uid_arr = array();
				if(-1 == $permission['object_type'] && !empty($permission['object_content'])){
					$uid_arr = $permission['object_content'];
				}
				$enddata = array(
				  'dkcode'=>$this->dkcode,
					'uid' => $this->uid,
					'fid' => $onlytime,
					'uname' => $this->user['username'],
					'content' => '结束于：'.$company,
					'type' => 'uinfo',
					'subtype' => 'job',
					'info'=>'于' . date('Y年n月', $starttimes) . '任' . $position,//'工作'
					'permission' => $permission['object_type'],
					'dateline' => $endtimes
				);
				if(empty($uid_arr)){
					//入住时间线  工作结束
					$result = service('Timeline')->addTimeLine($enddata);
				} else {
					//入住时间线  工作结束
					$result = service('Timeline')->addTimeLine($enddata, $uid_arr);
				}
			}
			//更新索引
		    $rss = service('RestorationSearch')->restoreUserInfo($this->uid);
            $this->ajaxReturn('',L('操作成功！'),1);//operate_success
        }
    }
    
    
    /**
     * 用户工作资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
    public function jobDelete(){
    	$this -> myedit->refreshes($this->uid);
		$id = P('tid');
        if(!$oldVals = $this->getDataById($id,USER_JOBEXPER)){
            $this->ajaxReturn('',L('信息为未找到！'),0);//data_not_found
        }
        if(!$this->delete($id,$this->uid,USER_JOBEXPER)){
            $this->ajaxReturn('',L('操作失败！'),0);//operate_fail
        }else{
            //$onlytime = $oldVals['dateline'];
            $onlytime = $id . '_job';
			//时间线 删除工作开始
           // $data = array( 'fid'=>$onlytime . '_s', 'uid'=>$this->uid, 'type'=>'uinfo');
			$result = service('Timeline')->removeTimeline($onlytime . '_s', $this->uid, 'uinfo');			
			if($oldVals['endtime'] > 0){
				//存在工作结束时间
				//时间线 删除工作结束
				//$data = array( 'fid'=>$onlytime . '_e','uid'=>$this->uid, 'type'=>'uinfo');
				$result = service('Timeline')->removeTimeline($onlytime . '_e', $this->uid, 'uinfo');			   
			}
        }
        //更新索引
		$rss = service('RestorationSearch')->restoreUserInfo($this->uid);
        $this->ajaxReturn('',L('操作成功！'),1);//operate_success
    }
    
    /**
     * 用户在校情况奖学金资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
/*    public function addSchoolarship(){
         $keys = $this->keys;
         $_POST['subject_type']=1;

		 //获取数据
		 $startMonth = P('startMonth');
		 $startYear = P('startYear');

         if(!empty($startMonth) && !empty($startYear)){
            $_POST['starttime']=mktime(0, 0, 0, P('startMonth')+1, 0, P('startYear'));
         }
        $_POST['dateline']=time();
        $this->load->model('isexistsmodel','isexists');
        if($this->isexists->scholarshipIsExists(P('level1'),P('title2'),$_POST['starttime'])){
            json_encodes(0,L('数据已存在！'));//data_exists
        }
        $usefulKeys=array_flip($keys);
        if(!$keyVals=getKeyVals($keys,$usefulKeys)){
            return false;
        }
        $keyVals=array_merge($keyVals, array('uid'=>$this->uid));
        if(!$this->db->insert(RESUME_SCHOOL,$keyVals)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }else{
            $id = $this->db->insert_id();
            json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
        }
    }*/
    
    /**
     * 用户在校情况获得奖项资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
/*    public function addAward(){
        $keys = $this->keys;
        $_POST['subject_type']=2;

		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');

        if(!empty($startMonth) && !empty($startYear)){
        $_POST['starttime']=mktime(0, 0, 0, P('startMonth')+1, 0, P('startYear'));
        }
        $_POST['dateline']=time();
        $this->load->model('isexistsmodel','isexists');
        if($this->isexists->awardIsExists(P('title'),P('title1'),$_POST['starttime'])){
            json_encodes(0,L('数据已存在！'));//data_exists
        }
        $usefulKeys=array_flip($keys);
        if(!$keyVals=getKeyVals($keys,$usefulKeys)){
            return false;
        }
        $keyVals=array_merge($keyVals, array('uid'=>$this->uid));
        if(!$this->db->insert(RESUME_SCHOOL,$keyVals)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }else{
            $id = $this->db->insert_id();
            json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
        }
       
    }*/
    
    /**
     * 用户在校情况担任职务资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
 /*   public function addPosition(){
        $keys = $this->keys;
        $_POST['subject_type']=3;

		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');
		$endMonth = P('endMonth');
		$endYear = P('endYear');

        if(!empty($startMonth) && !empty($startMonth)){
        $_POST['starttime']=mktime(0, 0, 0, P('startMonth')+1, 0, P('startYear'));
        }
         if(!empty($endMonth) && !empty($endYear)){
        $_POST['endtime']=mktime(0, 0, 0, P('endMonth')+1, 0, P('endYear'));
         }
        $_POST['dateline']=time();
        $this->load->model('isexistsmodel','isexists');
        if($this->isexists->positionIsExists(P('title'),$_POST['starttime'],$_POST['endtime'])){
            json_encodes(0,L('数据已存在！'));//data_exists
        }
        $usefulKeys=array_flip($keys);
        if(!$keyVals=getKeyVals($keys,$usefulKeys)){
            return false;
        }
        $keyVals=array_merge($keyVals, array('uid'=>$this->uid));
        if(!$this->db->insert(RESUME_SCHOOL,$keyVals)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }else{
            $id = $this->db->insert_id();
            json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
        }
    }*/
    
    /**
     * 用户在校情况社会实践资料添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
 /*   public function addSocialPractice(){
        $keys = $this->keys;
        $_POST['subject_type']=4;

		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');
		$endMonth = P('endMonth');
		$endYear = P('endYear');

        $_POST['starttime'] = 0;
        $_POST['endtime'] = 0;
        if(!empty($startMonth) && !empty($startYear)){
			$_POST['starttime']=mktime(0, 0, 0, P('startMonth')+1, 0, P('startYear'));
        }
        if(!empty($endMonth) && !empty($endYear)){
			$_POST['endtime']=mktime(0, 0, 0, P('endMonth')+1, 0, P('endYear'));
        }
        $_POST['dateline']=time();
        $this->load->model('isexistsmodel','isexists');
        if($this->isexists->socialPracticeIsExists(P('title'),$_POST['starttime'],$_POST['endtime'],P('content'))){
            json_encodes(0,L('数据已存在！'));//data_exists
        }
        $usefulKeys=array_flip($keys);
        if(!$keyVals=getKeyVals($keys,$usefulKeys)){
            return false;
        }
        $keyVals=array_merge($keyVals, array('uid'=>$this->uid));
        if(!$this->db->insert(RESUME_SCHOOL,$keyVals)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }else{
            $id = $this->db->insert_id();
            json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
        }
    }*/
    
    
    /**
     * 用户在校情况奖学金资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
 /*   public function editSchoolarship(){
		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');

        if(!empty($startMonth) && !empty($startYear)){
			$_POST['starttime']=mktime(0, 0, 0, P('startMonth')+1, 0, P('startYear'));
        }
        $_POST['lastupdate_time']=time();
        $keys = $this->keys;
        $id = P('Id');
        if(!($keys && $id)){
            json_encodes(0,L('数据丢失！'));//lost para
        }
        if(!$oldVals=$this->getDataById($id,RESUME_SCHOOL)){
            json_encodes(0,L('信息未找到！'));//data_not_found
        }
        if(!$usefulKeys=filterKeys($keys,$oldVals)){
            return true;//没有需要修改的东西
        }
        if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_SCHOOL)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }else{
            json_encodes(1,L('操作成功！'));//operate_success
        }
    }*/
    
    /**
     *添加家庭成员 
     * @author hxm
     * @date 2012/06/20
     * @param ''
     * @access public 
     * @return json 
     */
	public function addFamilyMember(){
		//获取数据
		$uid = $this->uid;
		$re = P('relativemate');
		$ty = P('type');
		$delUid = P('delUid');
        $editUid = P('editUid');
		$editType = P('editType');
		$table = "user_relative";
		
		//删除指定的用户		
		if($delUid){						
			$arr_delUid = explode(' ', $delUid);		
			if(!$this->myedit->familyDel($arr_delUid, $this->uid, $table, true)){
				$data['del']=1;			
				$res = 0;
				$success = "操作失败";	
			}else{
				$data['del']=1;
				$res = 1;	
				$success = "操作成功";
			}
		}else{
			$data['del']=0;			
			$res = 0;
			$success = "操作失败";		
		}
		
		//修改用户
		if($editUid){
			$editUid = explode(',', $editUid);
			$editType = explode(',', $editType);
			$arr = array_combine($editUid, $editType);					
			if(!$this->myedit->familyEdit($arr, $this->uid, $table, true)){
				$data['edit']=1;			
				$res = 0;
				$success = "操作失败";	
			}else{
				$data['edit']=1;
				$res = 1;	
				$success = "操作成功";
			}			
		}else{
			$data['edit']=0;			
		    if(!$res){$res = 0;}
			$success = "操作失败";		
			
		}		
		//添加用户
		if(!empty($re) && !empty($ty)){
		$arr_re = explode(' ', $re);
		$arr_ty = explode(' ', $ty);		
		foreach($arr_re as $key=>$val){
			$dateline = time();
			$item = array('uid'=>$uid, 'relativemate'=>$val, 'type'=>$arr_ty[$key], 'dateline'=>$dateline);
			$rs[] = $item;
		}
		//print_r($rs);
		if(count($rs) <= 0){
			json_encode(0, L('数据丢失！'));
		}
		if(!$this->myedit->familyAdd($rs, $table, true)){
			$data['add']=1;		
			if(!$res){$res = 0;}
			$success = "操作失败";
		}else{
			$data['add']=1;			
			$res = 1;
			$success = "操作成功";			
		}
		}else{
			$data['add']=0;			
			if(!$res){$res = 0;}
			$success = "操作失败";			
		}		
		$this->ajaxReturn($data,$success,$res);
	}
	    
    /**
     * 用户在校情况获得奖项资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
 /*   public function editAward(){
		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');

        if(!empty($startMonth) && !empty($startYear)){
			$_POST['starttime']=mktime(0, 0, 0, P('startMonth')+1, 0, P('startYear'));
        }
        $_POST['lastupdate_time']=time();
        $keys = $this->keys;
        $id = P('Id');
        if(!($keys && $id)){
            json_encodes(0,L('数据丢失！'));//lost para
        }
        if(!$oldVals=$this->getDataById($id,RESUME_SCHOOL)){
            json_encodes(0,L('信息未找到！'));//data_not_found
        }
        if(!$usefulKeys=filterKeys($keys,$oldVals)){
            return true;//没有需要修改的东西
        }
        if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_SCHOOL)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }else{
            json_encodes(1,L('操作成功！'));//operate_success
        }
    }*/
    
    /**
     * 用户在校情况担任职务资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
 /*   public function editPosition(){
		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');
		$endMonth = P('endMonth');
		$endYear = P('endYear');

        if(!empty($startMonth) && !empty($startYear)){
			$_POST['starttime']=mktime(0, 0, 0, P('startMonth')+1, 0, P('startYear'));
        }
        if(!empty($endMonth) && !empty($endYear)){
			$_POST['endtime']=mktime(0, 0, 0, P('endMonth')+1, 0, P('endYear'));
        }
        $_POST['lastupdate_time']=time();
        $keys = $this->keys;
        $id = P('Id');
        if(!($keys && $id)){
            json_encodes(0,L('数据丢失！'));//lost para
        }
        if(!$oldVals=$this->getDataById($id,RESUME_SCHOOL)){
            json_encodes(0,L('信息未找到！'));//data_not_found
        }
        if(!$usefulKeys=filterKeys($keys,$oldVals)){
            return true;//没有需要修改的东西
        }
        if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_SCHOOL)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }else{
            json_encodes(1,L('操作成功！'));//operate_success
        }
    }
    */
    
    /**
     * 用户在校情况社会实践资料修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
  /*  public function editSocialPractice(){
		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');
		$endMonth = P('endMonth');
		$endYear = P('endYear');

		if(!empty($startMonth) && !empty($startYear)){
			$_POST['starttime']=mktime(0, 0, 0, P('startMonth')+1, 0, P('startYear'));
		}
		if(!empty($endMonth) && !empty($endYear)){
			$_POST['endtime']=mktime(0, 0, 0, P('endMonth')+1, 0, P('endYear'));
		}
        $_POST['lastupdate_time']=time();
         $keys = $this->keys;
        $id = P('Id');
        if(!($keys && $id)){
            json_encodes(0,L('数据丢失！'));//lost para
        }
        if(!$oldVals=$this->getDataById($id,RESUME_SCHOOL)){
            json_encodes(0,L('信息未找到！'));//data_not_found
        }
        if(!$usefulKeys=filterKeys($keys,$oldVals)){
            return true;//没有需要修改的东西
        }
        if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_SCHOOL)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }else{
            json_encodes(1,L('操作成功！'));//operate_success
        }
    }*/
    
    
    /**
     * 用户在校情况[奖学金、获得奖项、担任职务、社会实践]资料删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
/*    public function atSchoolDelete(){
        $id = P('id');
        if(!$oldVals=$this->getDataById($id,RESUME_SCHOOL)){
            json_encodes(0,L('信息未找到！'));//data_not_found
        }
        if(!$this->delete($id,$this->uid,RESUME_SCHOOL)){
            json_encodes(0,L('操作失败！'));//operate_fail
        }
        json_encodes(1,L('操作成功！'));//operate_success
    }*/
    
	 /**
     * 获取用户的工作情况等记录数
     * @author bohailiang
     * @date   2012/4/12
     * @param  $type  string  类型：work - 工作情况
     * @access public
     * @return int
     */
	function get_count($type = ''){
		if(empty($type)){
			return 0;
		}

        $this->load->model('userwikimodel','userwiki');
		$count = $this->userwiki->get_count($type, $this->uid);

		return $count;
	}
    
    /*
     * 过滤用不合法的同学，同事名字
     *@liyundong 
     * @param $type array() 类型
     * */
    function filterName($arr){
        foreach ($arr as  $value) {
               if (preg_match("/^[\x{4E00}-\x{9FFF}a-zA-Z\s+]+$/u", $value)) {
                 $value = preg_replace("/[\s+]+/", '', $value);  
                  $name[] = $value;
            }
        }
        return $name;
    }
    
    function filterUid($arr){
         foreach($arr as $val){
         	if(is_numeric($val)){
         	$rs[] = $val;	
         	}         	
         }
         return $rs; 	
    }
    
    /*
     * 获取当前月最后一天最后一秒的时间戳
     *@bohailiang
     * @param $type array() 类型
     * */
    function get_school_date($year = 0, $month = 0){
        if(empty($year) || empty($month) || 13 <= $month){
            return 0;
        }        
        $date_maked = 0;
        if(12 == $month){
            $year = $year + 1;
            $date_maked = mktime(0, 0, 0, 1, 1, $year) - 100;
        } else {
            $month = $month + 1;
            $date_maked = mktime(0, 0, 0, $month, 1, $year) - 100;
        }
        
        return $date_maked;
    }
    
    public function addIntroduction(){
		$introduction = P('introduction');
		$introduction = addslashes_deep($introduction);
		$introduction = trim($introduction);
		$introduction = html_entity_decode($introduction);
		//$introduction = strip_tags($introduction);
		$introduction = preg_replace("/<([a-zA-Z]+)[^>]*>/", "<\\1>", $introduction);	
		$introduction = htmlspecialchars($introduction);	
		$introduction = str_replace("\n", "<br/>", $introduction);		
		$introduction = mb_substr($introduction, 0, 255, 'utf-8');
		$table = 'user_info';
		$rs=$this->myedit->introductionAdd($this->uid, $introduction, $table);
		if($rs){
			//更新serssion
			$_SESSION['user']['introduction'] = $introduction;
			$this->ajaxReturn('', L('添加成功！'), 1);
		}else{
			$this->ajaxReturn('', L('添加失败！'), 0);
		}
		
    }
    
    /**
     * 用户私密资料编辑
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return true/false
     */
	public function privateEdit(array $keys){
		if(!($keys)){
			return false;
		}
		if(!$oldVals=$this->myedit->getUserByUId($this->uid)){
			return false;
		}		
		if(!$usefulKeys=filterKeys($keys,$oldVals)){			
			return true;
		}		
		if(!$this->myedit->baseEditDo($keys,$usefulKeys,$this->uid)){
			return false;
		}
		return true;
	}
	
	public function isBaseEdit($year=-1, $month=-1, $day=-1, $lastname='', $sex=''){		
		$rs = false;		
		$info = $this->myedit->getInfo($this->uid);			    
        if($year != -1 && $month != -1 && $day != -1 && !empty($year) && !empty($month) && !empty($day)){        	      	
        	$birthday = mktime(0, 0, 0, $month, $day, $year);
        	if($birthday != $info['birthday']){
            	$_SESSION['user']['birthday'] = $birthday;
        		$rs = true;
        	}else{
        		//$rs = false;
        	}
        }else{
        	//$rs = false;
        }        	
        if(!empty($lastname) && $lastname != $info['username']){        	
        	$_SESSION['user']['username'] = $lastname;
        	$rs = true;
        }else{
        	//$rs = false;
        }       
        if(!empty($sex) && $sex != $info['sex']){
        	$_SESSION['user']['sex'] = $sex;
        	$rs = true;
        }else{
        	//$rs = false;
        } 
		return $rs;
	}
}
?>
