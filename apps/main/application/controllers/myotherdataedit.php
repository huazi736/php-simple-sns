<?php
    class MY_OtherDataEdit extends MY_Controller{
        public function __construct() {
            parent::__construct();
            $this->load->model('myeditmodel','myedit');
            $this->load->model('userwikimodel','userwiki');
        }
        
      /**
     * 用户培训经历添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
        public function teachAdd(){
            $keys = $this->addKeys;

			//获取数据
			$startMonth = P('startMonth');
			$startYear = P('startYear');
			$endMonth = P('endMonth');
			$endtYear = P('endtYear');

            if(!empty($startMonth) && !empty($startYear) ){
                $_POST['starttime']=  mktime(0, 0, 0, $startMonth+1, 0, $startYear);
            }
            if(!empty($endMonth) && !empty($endtYear)){
                $_POST['endtime']= mktime(0, 0, 0, $endMonth+1, 0, $endtYear);
            }
            $_POST['dateline']=time();
            $this->load->model('isexistsmodel','isexists');
             $count = $this->userwiki->get_count('tech', $this->uid);
            if($count >= 10)
            {
                json_encodes(0,L('培训经历最多只能10 条！'));
            }
            if($this->isexists->teachIsExists(P($keys['provider']),P($keys['subject']))){
                json_encodes(0,L('数据已存在！'));//data_exists
            }
            $usefulKeys=array_flip($keys);
            if(!$keyVals=getKeyVals($keys,$usefulKeys)){
                return false;
            }
            $keyVals=array_merge($keyVals, array('uid'=>$this->uid));
            if(!$this->db->insert(RESUME_TRAIN,$keyVals)){
                json_encodes(0,L('操作失败！'));//operate_fail
            }else{
                $id = $this->db->insert_id();
				json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
            }
        }
        
    /**
     * 用户培训经历修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function teachEdit(){
		$id = P('id');
		$keys = $this->editKeys;
		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');
		$endMonth = P('endMonth');
		$endtYear = P('endtYear');

		if(!empty($startMonth) && !empty($startYear) ){
			$_POST['starttime']=  mktime(0, 0, 0, $startMonth+1, 0, $startYear);
		}
		if(!empty($endMonth) && !empty($endtYear)){
			$_POST['endtime']= mktime(0, 0, 0, $endMonth+1, 0, $endtYear);
		}
		$_POST['lastupdate_time']=time();
		if(!($id && $keys)){
			json_encodes(0,L('数据丢失！'));//lost para
		}
		if(!$oldVals=$this->getDataById($id,RESUME_TRAIN)){
			 json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$usefulKeys=filterKeys($keys,$oldVals)){
			 return true;//没有需要修改的东西
		//json_encodes(0,L('operate_fail'));
		}
		if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_TRAIN)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			json_encodes(1,L('操作成功！'));//operate_success
		}
	}
            
    /**
     * 用户培训经历删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function teachDelete(){
		$id=P('id');
		if(!$oldVals=$this->getDataById($id,RESUME_TRAIN)){
			json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$this->delete($id,$this->uid,RESUME_TRAIN)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}
		json_encodes(1,L('操作成功！'));//operate_success
	}
            
    /**
     * 用户语言状况添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function languageAdd(){
		$keys = $this->addKeys;
		if(!$keys){
			return false;
		}
		$this->load->model('isexistsmodel','isexists');
         $count = $this->userwiki->get_count('lang', $this->uid);
        if($count>=10)
        {
            json_encodes(0,L('语言情况最多只能5 条！'));
        }
		if($this->isexists->languageIsExists(P('type'))){
			json_encodes(0,L('数据已存在！'));//data_exists
		}
		$_POST['dateline']=time();
		$usefulKeys=array_flip($keys);
		if(!$keyVals=getKeyVals($keys,$usefulKeys)){
			return false;
		}
		$keyVals=array_merge($keyVals, array('uid'=>$this->uid));
		if(!$this->db->insert(RESUME_LANGUAGE,$keyVals)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			$id = $this->db->insert_id();
			json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
		}
	   
	}
     
    /**
     * 获取生活习惯
     * @author chenxujia
     * @date   2012/3/28
     * @param  $uid 用户id
     * @access public
     * @return array $data 用户对应的生活习惯
     */
       function getDataLife($uid){
           if(!is_numeric($uid) || $uid <= 0){
               return false;
           }else{
            $result=$this->db->where(array('uid'=>$uid))->get(USER_LIFE)->result_array();
            return ($result)?array_shift($result):false;
           }
       }
            
            
    /**
     * 用户语言状况修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function languageEdit(){
		$_POST['lastupdate_time']=time();
		$id = P('id');
		$keys = $this->editKeys;
		if(!($id && $keys)){
			json_encodes(0,L('数据丢失！'));//lost para
		}
		if(!$oldVals=$this->getDataById($id,RESUME_LANGUAGE)){
			 json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$usefulKeys=filterKeys($keys,$oldVals)){
			 return true;//没有需要修改的东西
		}
		if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_LANGUAGE)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			json_encodes(1,L('操作成功！'));//operate_success
		}
	}
            
   /**
     * 用户语言情况删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function languageDelete(){
		$id=$_POST['id'];
		if(!$oldVals=$this->getDataById($id,RESUME_LANGUAGE)){
			json_encodes(0,L('信息未找到！'));//data_not_found
		}
		if(!$this->delete($id,$this->uid,RESUME_LANGUAGE)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}
		json_encodes(1,L('操作成功！'));//operate_success
	}
            
            
    /**
     * 用户项目经历添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function projectAdd(){
		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');
		$endMonth = P('endMonth');
		$endYear = P('endYear');

		$keys = $this->addKeys;
		$this->load->model('isexistsmodel','isexists');
        $count = $this->userwiki->get_count('pro', $this->uid);
        if($count>=10)
        {
            json_encodes(0,L('项目经历最多只能10 条！'));
        }
		if($this->isexists->languageIsExists(P('name'))){
			json_encodes(0,L('数据已存在！'));//data_exists
		}
		if(!empty($startMonth) && !empty($startYear) ){
			$_POST['starttime']=  mktime(0, 0, 0, $startMonth+1, 0, $startYear);
		}
		if(!empty($endMonth) && !empty($endYear) ){
			$_POST['endtime']= mktime(0, 0, 0, $endMonth+1, 0, $endYear);
		}
		$_POST['dateline']=time();
		$usefulKeys=array_flip($keys);
		if(!$keyVals=getKeyVals($keys,$usefulKeys)){
			return false;
		}
		$keyVals=array_merge($keyVals, array('uid'=>$this->uid));
		if(!$this->db->insert(RESUME_PROJECT,$keyVals)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			$id = $this->db->insert_id();
			json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
		}
		
	}
            
    /**
     * 用户项目经历修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function projectEdit(){
		//获取数据
		$startMonth = P('startMonth');
		$startYear = P('startYear');
		$endMonth = P('endMonth');
		$endYear = P('endYear');

		$id = P('id');
		$keys = $this->editKeys;
		if(!($id && $keys)){
			return false;
		}
		if(!empty($startMonth) && !empty($startYear) ){
			$_POST['starttime']=  mktime(0, 0, 0, $startMonth+1, 0, $startYear);
		}
		if(!empty($endMonth) && !empty($endYear) ){
			$_POST['endtime']= mktime(0, 0, 0, $$endMonth+1, 0, $endYear);
		}
		$_POST['lastupdate_time']=time();
		if(!$oldVals=$this->getDataById($id,RESUME_PROJECT)){
			 json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$usefulKeys=filterKeys($keys,$oldVals)){
			 return true;//没有需要修改的东西
		}
		if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_PROJECT)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			json_encodes(1,L('操作成功！'));//operate_success
		}
	}
            
    /**
     * 用户项目经历删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function projectDelete(){
		$id=$_POST['id'];
		if(!$oldVals=$this->getDataById($id,RESUME_PROJECT)){
			json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$this->delete($id,$this->uid,RESUME_PROJECT)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}
		json_encodes(1,L('操作成功！'));//operate_success
	}
            
    /**
     * 用户获得证书添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function bookAdd(){
		//获取数据
		$month = P('month');
		$year = P('year');

		$keys = $this->addKeys;
		$this->load->model('isexistsmodel','isexists');
       
        $count = $this->userwiki->get_count('book', $this->uid);
        if($count>=5)
        {
            json_encodes(0,L('用户证书最多只能5 条！'));
        }
        
		if($this->isexists->bookIsExists(P($keys['name']))){
			json_encodes(0,L('数据已存在！'));//data_exists
		}
		if(!empty($month) && !empty($year)){
			$_POST['starttime']=mktime(0, 0, 0, P('month')+1, 0, P('year'));
		}
		$_POST['dateline']=time();
		$usefulKeys=array_flip($keys);
		if(!$keyVals=getKeyVals($keys,$usefulKeys)){
			return false;
		}
		$keyVals=array_merge($keyVals, array('uid'=>$this->uid));
		if(!$this->db->insert(RESUME_BOOK,$keyVals)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			$id = $this->db->insert_id();
			json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
		}
	  
	}
            
            
    /**
     * 用户获得证书修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function bookEdit(){
		//获取数据
		$month = P('month');
		$year = P('year');

		$id = P('id');
		$keys = $this->editKeys;
		if(!($id && $keys)){
			return false;
		}
		if(!empty($year) && !empty($month)){
			$_POST['starttime']=mktime(0, 0, 0, P('month')+1, 0, P('year'));
		}
		$_POST['lastupdate_time']=time();
		if(!$oldVals=$this->getDataById($id,RESUME_BOOK)){
			 json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$usefulKeys=filterKeys($keys,$oldVals)){
			 return true;//没有需要修改的东西
		}
		if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_BOOK)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			json_encodes(1,L('操作成功！'));//operate_success
		}
	}
            
    /**
     * 用户获得证书删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function bookDelete(){
		$id=P('id');
		if(!$oldVals=$this->getDataById($id,RESUME_BOOK)){
			json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$this->delete($id,$this->uid,RESUME_BOOK)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}
		json_encodes(1,L('操作成功！'));//operate_success
	}
            
    /**
     * 用户专业技能添加
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function skillAdd(){
		$keys = $this->addKeys;
		$this->load->model('isexistsmodel','isexists');
         $count = $this->userwiki->get_count('skill', $this->uid);
        if($count>=10)
        {
            json_encodes(0,L('专业技能最多只能5 条！'));
        }
		if($this->isexists->skillIsExists(P('type'),P('name'))){
			json_encodes(0,L('数据已存在！'));//data_exists
		}
		$_POST['dateline'] = time();
		$_POST['lastupdate_time'] = '';
		$usefulKeys=array_flip($keys);
		if(!$keyVals=getKeyVals($keys,$usefulKeys)){
			return false;
		}
		$keyVals=array_merge($keyVals, array('uid'=>$this->uid));
		if(!$this->db->insert(RESUME_SKILL,$keyVals)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			$id = $this->db->insert_id();
			json_encodes(1,L('操作成功！'),array('id'=>$id));//operate_success
		}
	}
            
    /**
     * 用户专业技能修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function skillEdit(){
		$id = P('id');
		$keys = $this->editKeys;
		if(!($id && $keys)){
			return false;
		}
		$_POST['lastupdate_time'] = time();
		if(!$oldVals=$this->getDataById($id,RESUME_SKILL)){
			 json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$usefulKeys=filterKeys($keys,$oldVals)){
			 return true;//没有需要修改的东西
		}
		if(!$this->myedit->schoolEdit($keys,$usefulKeys,$id,$this->uid,RESUME_SKILL)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}else{
			json_encodes(1,L('操作成功！'));//operate_success
		}
	}
            
    /**
     * 用户专业技能删除
     * @author chenxujia
     * @date   2012/3/22
     * @param  $_POST 数据
     * @access public
     * @return json
     */
	public function skillDelete(){
		$id=$_POST['id'];
		if(!$oldVals=$this->getDataById($id,RESUME_SKILL)){
			json_encodes(0,L('信息未找到'));//data_not_found
		}
		if(!$this->delete($id,$this->uid,RESUME_SKILL)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}
		json_encodes(1,L('操作成功！'));//operate_success
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
            
            
    /**
     * 公用删除方法
     * @author chenxujia
     * @date   2012/3/22
     * @param  $id 数据id $uid 用户id $tables 表
     * @access public
     * @return true/false
     */  
             public function delete($id,$uid,$tables){
                //echo $uid;exit;
                if(!($id && $uid)){
                    return false;
                }
                if(!$this->db->query("delete from ".$tables." where id='$id' and uid='$uid'")){
                    return false;
                }
                //clear memcache
                return true;
            }
        
    /**
     * 编辑生活习惯
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
	public function editDo(array $oldVals){
		//获取数据
		$drink = P('drink');
		$personality = P('personality');
		$religion = P('religion');
		$smoking = P('smoking');
		$children_order = P('children_order');
		$workrest = P('workrest');

		$keys = array(
			'smoking'=> 'smoke',
			'drink' => 'drink',
			'workrest' => 'workrest',
			'religion' => 'religion',
			'children_order' => 'children_order',
			'personality' => 'personality',
			'dateline' => 'dateline'
		);
		if(!$usefulKeys=filterKeys($keys,$oldVals)){
			return true;
		}
		if(!empty($drink) && $drink == -1){
			$_POST['drink'] = 0;  
		}
		if(!empty($personality) && $personality == -1){
			$_POST['personality'] = 0;  
		}
		if(!empty($religion) && $religion == -1){
			$_POST['religion'] = 0;  
		}
		if(!empty($smoking) && $smoking == -1){
			$_POST['smoking'] = 0;  
		}
		if(!empty($children_order) && $children_order == -1){
			$_POST['children_order'] = 0;  
		}
		if(!empty($workrest) && $workrest == -1){
			$_POST['workrest'] = 0;  
		}
		if(!$this->edit($keys,$usefulKeys,$this->uid)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}
	}
        
    /**
     * 添加生活习惯
     * @author chenxujia
     * @date   2012/3/22
     * @param  ''
     * @access public
     * @return json
     */
	public function addDo(){
		//获取数据
		$drink = P('drink');
		$personality = P('personality');
		$religion = P('religion');
		$smoking = P('smoking');
		$children_order = P('children_order');
		$workrest = P('workrest');

		$keys = array(
			'smoking'=> 'smoking',
			 'drink' => 'drink',
			 'workrest' => 'workrest',
			 'religion' => 'religion',
			 'children_order' => 'children_order',
			 'personality' => 'personality',
			 'dateline' => 'dateline'
		);

		if(!empty($drink) && $drink == -1){
			$_POST['drink'] = 0;  
		}
		if(!empty($personality) && $personality == -1){
			$_POST['personality'] = 0;  
		}
		if(!empty($religion) && $religion == -1){
			$_POST['religion'] = 0;  
		}
		if(!empty($smoking) && $smoking == -1){
			$_POST['smoking'] = 0;  
		}
		if(!empty($children_order) && $children_order == -1){
			$_POST['children_order'] = 0;  
		}
		if(!empty($workrest) && $workrest == -1){
			$_POST['workrest'] = 0;  
		}
		if(!$this->add($keys,$this->uid)){
			json_encodes(0,L('操作失败！'));//operate_fail
		}
	}

    /**
     * 生活习惯修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $uid 用户id $keys $usefulKeys
     * @access public
     * @return true/false
     */
	public function edit(array $keys,array $usefulKeys,$uid){
		if(!($keys && $uid)){
			return false;
		}
		$keyVals=getKeyVals($keys,$usefulKeys);
		if(!$keyVals){
			return true;
		}
		if(!$this->db->update(USER_LIFE,$keyVals,array('uid'=>$uid))){
			return false;
		}
		return true;
	}
        
    /**
     * 生活习惯修改
     * @author chenxujia
     * @date   2012/3/22
     * @param  $uid 用户id $keys 
     * @access public
     * @return true/false
     */
	public function add(array $keys,$uid){
		if(!($keys && $uid)){
			return false;
		}
		$keyVals=array_merge(getKeyVals($keys,$keys), array('uid'=>$uid));
		$keyVals['smoke'] = $keyVals['smoking'];
		unset($keyVals['smoking']);
		if(!$this->db->insert(USER_LIFE,$keyVals)){
			return false;
		}
		return true;
	}
        
    /**
     * 根据id获取数据
     * @author chenxujia
     * @date   2012/3/22
     * @param  $id 数据id $tables表 
     * @access public
     * @return true/false
     */
        private function getDataById($id,$tables){
            if(!$id){
                return false;
            }
            $result=$this->db->where(array('id'=>$id))->get($tables)->result_array();
            return ($result)?array_shift($result):false;
        }
    }
?>
