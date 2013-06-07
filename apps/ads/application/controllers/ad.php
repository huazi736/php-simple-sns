<?php

/**
 * 广告系统
 *
 * @author        hujiashan
 * @date          2012/06/13
 * @version       1.0
 * @description   广告前端展示控制
 */

class ad  extends MY_Controller{
	public $cid = '';//广告商id
	public $companyInfo = '';//广告商信息		
	public function __construct(){
		parent::__construct();
		error_reporting(1024);
		$this->load->model('admodel','ad');
		$this->load->model('adcrowdmodel','adcrowd');	//投放对象	
		$this->load->model('adtaskmodel','adtask');	//广告日程	
		$this->load->model('adcompanymodel','adcompany');	//广告商	
		$this->load->model('adcostmodel','adcost');//广告花费	
		$this->load->model('adpaymodel','adpay');//充值支付信息	
		$this->load->model('adcompanycostmodel','adcompanycost');	//广告商cost	

		$companyInfoArr = $this->getCompanyInfo(" uid = $this->uid AND is_delete != 1 ");
		if(!empty($companyInfoArr)){
			$this->companyInfo = $companyInfoArr[0];
			$this->cid = $companyInfoArr[0]['id'];
		}					
	}
	
	public function common_user(){
		//获取user信息
		$user_info = array();
		$user_info['username'] = $this->username;
		$user_info['url'] = mk_url('main/index/main');
        $user_info['avatar_img'] = get_avatar($this->uid,'ss');
        
		$this->assign('user_info', $user_info);
	}
	
	/**
	 * @author: qianc
	 * @date 2012/7/7
	 * @desc: 获取广告商信息
	 * @access public
	 */	
	function getCompanyInfo($where){
		return $this->adcompany->getCompanyInfo($where);
       
	}		
	
	/**
	 * 
	 * 广告申请协议
	 * @author hujiashan
	 * @date 2012/06/19
	 */
	public function index(){
		
		$this->common_user();
		$this->assign('agreement', $this->system_config['agreement']);
		$this->display('agreement.html');
	}

	
	/**
	 * @author: qianc
	 * @desc: 发布广告
	 * @access public
	 * @return json
	 */	
	function addAd(){
		$this->common_user();
		//广告内容区	
		if($this->input->post()){
			$ad_url = $this->input->post('url');
			$ad_title = $this->input->post('title');		
			$ad_content = $this->input->post('introduce');
			$ad_img = $this->input->post('media_uri');
			$ad_name = $this->input->post('name');
				
			$ad_budget =  $this->input->post('budget');
			$ad_budget_sort =  $this->input->post('budget_sort');				
			$ad_charge_mode =  $this->input->post('charge_mode');
			$ad_bid =  $this->input->post('bid');	
			$ad_is_display =  $this->input->post('is_display');
			if($this->input->post('start_time')!='null'){
				$ad_start_time = strtotime($this->input->post('start_time'));
			}else{
				$ad_start_time = time();
			}

			//目标对象区
			$obj_region = $this->input->post('region_id');
			$obj_interest = $this->input->post('interest');	
			$obj_classify = $this->input->post('classify');
				
			
			if (mb_strlen($ad_title, 'utf-8') > 52 || mb_strlen($ad_title, 'utf-8') < 1) {
		 		$this->ajaxReturn('','请输入标题,小于52个字符!',0,'json');					
			}
			
			if (mb_strlen($ad_url, 'utf-8') > 150 || mb_strlen($ad_url, 'utf-8') < 1) {
		 		$this->ajaxReturn('','请输入URL,小于150个字符!',0,'json');					
			}
	
				//验证URL
			if(!preg_match('/(?:(?:http:\/\/)?\w+\.)(\w+)/ ',$ad_url)){
		 		$this->ajaxReturn('','您输入的URL格式不合法!',0,'json');									
			}		
			
			if (mb_strlen($ad_content, 'utf-8') > 144 || mb_strlen($ad_content, 'utf-8') < 1) {
		 		$this->ajaxReturn('','请输入内容,小于144个字符!',0,'json');					
			}	
	
			if (!$ad_img)  {
		 		$this->ajaxReturn('','请上传广告图片!',0,'json');					
			}
			
				
			if (mb_strlen($ad_name, 'utf-8') > 52 || mb_strlen($ad_name, 'utf-8') < 1) {
		 		$this->ajaxReturn('','请输入广告名称,小于52个字符!',0,'json');					
			}
			
			if (!$obj_region && $obj_classify == 1)  {
		 		$this->ajaxReturn('','请选择投放地区!',0,'json');					
			}		

			if (!$obj_interest && $obj_classify == 1)  {
				$this->ajaxReturn('','请选择兴趣!',0,'json');	
			}	
	
			$ad_budget = (float)$ad_budget;
			$ad_bid = (float)$ad_bid;		
					
			if (!strlen(trim($ad_budget)) || !isset($ad_budget)) {
		 		$this->ajaxReturn('','请输入预算!',0,'json');					
			}
	
			if(!is_numeric($ad_budget)){
		 		$this->ajaxReturn('','您输入的预算格式不合法!',0,'json');								
			}
				
			if(!$ad_budget){
		 		$this->ajaxReturn('','您输入的预算格式不合法!',0,'json');							
			}	

			if(!$ad_budget_sort && $ad_budget < $this->system_config['budget']){
		 		$this->ajaxReturn('','每日最低预算须大于￥ '+$this->system_config['budget']+'元，请重新输入!',0,'json');				
			}			
			
			if($ad_budget_sort && $ad_budget < $this->system_config['allbudget']){
		 		$this->ajaxReturn('','预算总额最低须大于￥ '+$this->system_config['allbudget']+'元，请重新输入!',0,'json');				
			}			
			
			if (!strlen(trim($ad_bid)) || !isset($ad_bid)) {
		 		$this->ajaxReturn('','请输入计费方式中竞价!',0,'json');					
			}	
			
			if(!is_numeric($ad_bid) || !$ad_bid){
		 		$this->ajaxReturn('','您输入的竞价格式不合法!',0,'json');							
			}

			$this->ad->setAdMemcache($this->input->post());
		 	$this->ajaxReturn('','验证成功，即将为你跳转!!',1,'json');						
		
		}else{
			$this->showApply();

		}

	}	
	
	/**
	 * @author: qianc
	 * @desc: 显示广告发布页
	 * @access protected
	 * @return json
	 */	
		
	protected function showApply(){
			$this->ad->delAdMemcache();			
			//时间显示
			$begin_date = date("Y-m-d",strtotime('+1 day'));
			$end_date = date("Y-m-d",strtotime('+100 year'));;

	
		
			//兴趣大分类
			$categories = $this->ad->getCategory(0, 1);
			$dataid = array();
			foreach ($categories as $k => $val){
				$dataid[] = $val['id'];
			}
			
			//年龄
			$age_output = array("不限","10-15岁","16-22岁","23-30岁","31-40岁","41-50岁","50岁以上");
			$age_val = array("0","1","2","3","4","5","6");


	        $this->assign('begin_date',$begin_date);	
	        $this->assign('end_date',$end_date);	        	        	
			$this->assign('categories',$categories);
			$this->assign('data_id',implode(',', $dataid));
			
			$this->assign('age_output',$age_output);
			$this->assign('age_val',$age_val);			
			//显示原有广告信息
			if($this->ad->getAdMemcache()){
				$simple = $this->ad->getAdMemcache();
			}
			$this->assign('adinfo',$simple);
 			$this->assign('sys_budget_day',$this->system_config['budget']); 
 			$this->assign('sys_budget_all',$this->system_config['allbudget']); 
 						
	        $this->display('apply.html');		
	}	
	
	/**
	 * @author: qianc
	 * @desc: 确认广告post
	 * @access public
	 * @return json
	 */
	function confirmAd() {
		if($this->ad->getAdMemcache()){
			$simple = $this->ad->getAdMemcache();
			$simple['title'] =  strFilter($simple['title']);
			$simple['name'] =  strFilter($simple['name']);	
			$simple['introduce'] =  strFilter($simple['introduce']);
			$simple['url'] =  strFilter($simple['url']);			
					
			$this->common_user();		
			$uid = $this->uid;		
			$this->assign('adinfo',$simple);	
	        $this->display('confirmad_add.html');				
			
		}else{
			$log =  '非法请求或页面已过期!';
			$this->error($log);
		}
		
	}
	

	/**
	 * @author: qianc
	 * @desc: 确认广告写入表
	 * @access public
	 * @return json
	 */
	function confirmAdPost() {
		$this->common_user();
		$uid = $this->uid;
		$user = $this->user;		
		if($_SERVER['REQUEST_METHOD']=='POST' && $this->ad->getAdMemcache()){
				$simple = $this->ad->getAdMemcache();		
			
				//广告内容区
				$ad_title = strFilter($simple['title']); //过滤字符串		
				$ad_content = strFilter($simple['introduce']); 
				$ad_name = strFilter($simple['name']);
				
				if($simple['start_time']!='null'){
					$ad_start_time = strtotime($simple['start_time']);
				}else{
					$ad_start_time = time();
				}
								
					
				if(!$this->cid){
					$data_adcompany = array('name'=>$user['username'],'mobile'=>$user['mobile'],'contact'=>$user['username'],'join_time'=>time(),'username'=>$user['username'],'dkcore'=>$user['dkcode'],'uid'=>$user['uid'],'email'=>$user['email']);
					$ret_cid = $this->adcompany->newData('ad_company',$data_adcompany);
					
					if($ret_cid){
						$data_adcompanycost = array('cid'=>$ret_cid);
						$ret_id_adcompanycost = $this->adcompanycost->newData('ad_company_cost',$data_adcompanycost);
						$money_available = 	0;//取得广告商可用余额

						
						//adlist表
						$data_adlist = array('url'=>$simple['url'],'title'=>$ad_title,'introduce'=>$ad_content,'media_uri'=>$simple['Mf_img'],'name'=>$ad_name,'start_time'=>$ad_start_time,'is_display'=>$simple['is_display'],'create_time'=>time(),'sort'=>3,'cid'=>$ret_cid,'classify'=>$simple['classify']);
						$ret_id_ad = $this->ad->newData('ad_list',$data_adlist);					
						
						//广告花费表								
						$data_adcost2 = array('ad_id'=>$ret_id_ad,'budget'=>$simple['budget'],'budget_sort'=>$simple['budget_sort'],'charge_mode'=>$simple['charge_mode'],'bid'=>$simple['bid']);
						$ret_id_adcost2 = $this->adcost->newData('ad_cost',$data_adcost2);	

						//aaaaaaa
						service('UserWiki')->setAccess($this->uid ,'1');					
						
					
						
					}else{						
						$this->ajaxReturn('','error!',0,'json');
					}	
																
				}else{
					//adlist表
					$data_adlist = array('url'=>$simple['url'],'title'=>$ad_title,'introduce'=>$ad_content,'media_uri'=>$simple['Mf_img'],'name'=>$ad_name,'start_time'=>$ad_start_time,'is_display'=>$simple['is_display'],'create_time'=>time(),'sort'=>3,'cid'=>$this->cid,'classify'=>$simple['classify']);
					$ret_id_ad = $this->ad->newData('ad_list',$data_adlist);
										
					//广告花费表
					$where = " uid = $uid AND is_delete != 1 ";									
					$company_info = $this->adcompany->getCompanyInfo($where);
					$data_adcost2 = array('ad_id'=>$ret_id_ad,'budget'=>$simple['budget'],'budget_sort'=>$simple['budget_sort'],'charge_mode'=>$simple['charge_mode'],'bid'=>$simple['bid']);
					$ret_id_adcost2 = $this->adcost->newData('ad_cost',$data_adcost2);	
																												
					$ret_cid = $this->cid;
					
				
	
					$company_cost_where = " cid = $ret_cid ";	
					$company_cost_info = $this->adcompanycost->getCompanyCostInfo($company_cost_where);
					$money_available = $company_cost_info[0]['leave_money_format'];
				}				
				
		


							
				if($ret_id_ad){
					//目标对象
					if($simple['classify'] == 1){
						$region_id_arr = $simple['region_id'];
						if($region_id_arr){
							$obj_region = implode(",", $region_id_arr);
						}					
						
						$obj_region_rank = $simple['region_rank'];
						$obj_city = implode(",",$simple['city']);
						$interest_id_arr =  $simple['interest'];
						if($interest_id_arr){
							$obj_interest = implode(",", $interest_id_arr);						
						}
						$obj_interest = ','.$obj_interest;
						
						$obj_age_range = $simple['age_range'];
						$obj_gender = $simple['gender'];
					}else{
						$obj_region = '';
						$obj_region_rank = '';
						$obj_city = '';
						$obj_interest = '';
						$obj_age_range = 0;
						$obj_gender = 3;
					}					
					$data_adcrowd = array('ad_id'=>$ret_id_ad,'region'=>$obj_region,'region_rank'=>$obj_region_rank,'city'=>$obj_city,'interest'=>$obj_interest,'age_range'=>$obj_age_range,'gender'=>$obj_gender);				
					$ret_id_adcrowd = $this->adcrowd->newData('ad_crowd',$data_adcrowd);
					
					
					if($ret_id_adcrowd){
						$ret_id_adtask = 1;
						/***
						 * 立即投放时is_display=1,日程表不用插入记录
						 * 选定时间就在日程表插入一条记录,is_display=-1
						 * 
						 */
						if($simple['is_display'] == -1){
							//日程
							$task_start_time = $simple['start_time'];
							$task_end_time = strtotime($simple['end_time']);
							$task_create_time = time();
							if($task_start_time=='null'){
								$task_start_time = $task_create_time;
							}else{
								$task_start_time = strtotime($task_start_time);
							}							
							$data_adtask = array('start_time'=>$task_start_time,'end_time'=>$task_end_time,'ad_id'=>$ret_id_ad,'create_time'=>time());
							$ret_id_adtask = $this->adtask->newData('ad_task',$data_adtask);
						}

						if(!$ret_id_adtask){
							$this->ad->delete($ret_id_ad);
							$this->adcrowd->delete($ret_id_adcrowd);
							$this->ajaxReturn('','error!',0,'json');						
						}
					}else{
						$this->ad->delete($ret_id_ad);
						$this->ajaxReturn('','error!',0,'json');			
					}	
				}else{
					$this->ajaxReturn('','error!',0,'json');		
				}
				//echo $ret_id_ad.'aa'.$ret_id_adcrowd.'bb'.$ret_id_adtask.'cc'. $ret_id_adcompany;exit;
				if ($ret_id_adtask) {
					$retData = $money_available > 0 ? array('data'=>array('url'=>mk_url('ads/adadmin/index'),'valid'=>true)) : array('data'=>array('url'=>mk_url('ads/adadmin/billad'),'valid'=>false)); 
					
					$this->ad->delAdMemcache();						
					$this->ajaxReturn($retData['data'],'',1,'json');				
				}
				$this->ajaxReturn('','error!',0,'json');			
		}
	}		
	
	
	
		
	/**
	 * @author: donghu
	 * @desc: 获取随机广告
	 * @access public
	 * @return json
	 */	
	public function adsdata(){
		$limit = intval($this->input->get('num'));
		$webId = intval($this->input->get('webid'));
		$callback = $this->input->get('callback');

		$webUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$ip = get_client_ip();
		if(!$limit){
			$this->ajaxReturn('', $info = 'error', $status = 0, $type = 'JSONP');
		}
		$data = $this->ad->getAdList($limit,$webId,$this->dkcode,$webUrl,$ip);
		if($data){
			$this->ajaxReturn($data, $info = '', $status = 1, $type = 'JSONP');
		}else{
			$this->ajaxReturn('', $info = 'error', $status = 0, $type = 'JSONP');
		}
	}
	
	/**
	 * @author: donghu
	 * @desc: 获取随机广告[个人页面]
	 * @access public
	 * @return json
	 */	
	public function getPersonalAd(){
		$dkcode = intval($this->input->get('dkcode'));
		if($dkcode==$this->dkcode){	
			$this->ajaxReturn('', $info = 'error', $status = 0, $type = 'JSONP');
		}
		$limit = intval($this->input->get('num'));
		$ip = get_client_ip();
		$url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$data = $this->ad->getPersonalAd($limit,$dkcode,$ip,$url);
		if($data){
			$this->ajaxReturn($data, $info = '', $status = 1, $type = 'JSONP');
		}else{
			$this->ajaxReturn('', $info = 'error', $status = 0, $type = 'JSONP');
		}		
	}
	
	/**
	 * @author: donghu
	 * @date: 2012/06/21
	 * @desc: 获取兴趣分类相关数据
	 * @paramter $ids 要获取的分类id数组
	 * @access public
	 * @return json
	 * 
	 */	
	public function getInterestCategory(){

		$pid = intval($this->input->get('pid'));
		$level = intval($this->input->get('level'));
		$data = $this->ad->getCategory($pid,$level);
		$this->ajaxReturn($data, $info = '', $status = 1, $type = 'json');

	}

	/**
	 * @author: donghu
	 * @date: 2012/06/25
	 * @desc: 根据兴趣id获取该兴趣下的所有网页总粉丝人数
	 * @access public
	 * @return json
	 * 
	 */
	public function getFansNumByInterestId(){
		$interestIds = $this->input->post('nscate');//获取兴趣id数组
		if(!is_array($interestIds)){
			$this->ajaxReturn($data, $info = 'param error', $status = 0, $type = 'json');
		}
		$data = $this->ad->getFansNumByInterestId($interestIds);
		$this->ajaxReturn($data, $info = '', $status = 1, $type = 'json');
		
		
	}
	
	/**
	 * @author: donghu
	 * @date: 2012/06/30
	 * @desc: 根据地区,年龄段,性别获取总人数
	 * @access public
	 * @return json
	 * 
	 */
	public function getUserCount(){		
		$age = $this->input->post('age');
		$now_addr = $this->input->post('now_addr');
		$sex = $this->input->post('sex');		
		$this->ajaxReturn($this->ad->getUserCount($now_addr,$age,$sex), $info = '', $status = 1, $type = 'json');
		
	}

	
	/**
	 * @author: donghu
	 * @date: 2012/07/06
	 * @desc: 广告点击
	 * @access public
	 * @return json
	 */
	public function adRedirect(){
		$index = $this->input->get('t');
		$ad_id = $this->input->get('index');
		$dkcode = $this->input->get('dkcode');
		$webUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$ip = get_client_ip();
		$webId = preg_replace('/[^\d]/','',$webUrl);
		$webId = intval($webId);
		$typeid = $dkcode ? $dkcode : $webId;//webid或个人页面dkcode
		$type = $webId ? 1 : 2;//web页面广告或个人页面广告
		$this->ad->adRedirect($index,$ad_id,$webUrl,$ip,$typeid,$type,$this->dkcode);
	}
}


?>