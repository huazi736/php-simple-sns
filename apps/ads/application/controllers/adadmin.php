<?php
/**
 * 广告商后台
 *
 * @author        qianc
 * @date          2012/07/05
 */

class adadmin  extends MY_Controller{
	private $cid = '';//广告商id
	private $companyInfo = '';//广告商信息
	public 	$url;	
	public function __construct(){
		parent::__construct();
		error_reporting(E_ALL);
		$this->load->model('admodel','ad');
		$this->load->model('adcrowdmodel','adcrowd');	//投放对象	
		$this->load->model('adtaskmodel','adtask');	//广告日程	
		$this->load->model('adcompanymodel','adcompany');	//广告商		
		$this->load->model('adpaymodel','adpay');	//网银支付表	
		$this->load->model('adcostmodel','adcost');	//广告花费表			
		$this->load->model('adinvoicemodel','adinvoice');	//发票表	
		$this->load->model('adcompanycostmodel','adcompanycost');	//广告商cost					

		$companyInfoArr = $this->getCompanyInfo(" uid = $this->uid AND is_delete != 1 ");
		if(empty($companyInfoArr)){
			@header("Location:".mk_url('main/index/index'));
			exit;
		}
		$this->companyInfo = $companyInfoArr[0];
		$this->cid = $companyInfoArr[0]['id'];		
		
		
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
	 * @author: qianc
	 * @date 2012/7/4
	 * @desc: 广告后台首页
	 * @access public
	 */	
	function index(){
		$this->common_user();	
       	$where = " t1.cid = $this->cid AND t1.sort != -1 ";  		
		$adList = $this->ad->getAds(0,5,$where,'t1.create_time');

		//通知提醒
		$notice = $paylist = array();
		$result = $this->adcompany->notice_log($this->cid);
		if($result){
			foreach ($result as $k => $val){
				$time = date('Y-m-d',$val['dateline']);
				$notice[$time][] = $val;
			}
		}
			
		//每日支出
		$paylist = $this->adcompany->days_speed($this->cid);
		
		$this->assign('paylist', $paylist);
		$this->assign('notice', $notice);		
		$this->assign('adList', $adList);		
	    $this->display('indexad.html');
       
	}		
		
	
	/**
	 * @author: qianc
	 * @date 2012/7/3
	 * @desc: 广告列表
	 * @access public
	 */	
	function listAd(){
		$this->common_user();
		 	
		//选择时间断(1,所有,2,昨天,3,过去七天,4,过去一个月,5,过去三个月)
		$date_arr = array(1,2,3,4,5);
		$date_selected = $this->input->get('date_selected');
		if(!$date_selected || !in_array($date_selected, $date_arr)){
			$date_selected = 1;
		}
		switch ($date_selected){
			case 1:
        		$config['base_url'] = mk_url('ads/adadmin/listAd?/');
       			$where = " t1.cid = $this->cid AND t1.sort != -1 ";        		
        		break;	
			case 2:
        		$config['base_url'] = mk_url('ads/adadmin/listAd?date_selected=2');
        		$date_begin = strtotime(date('Y-m-d',strtotime('-1 day')));
        		$date_end = $date_begin + 86400;
        		        		
       			$where = " t1.cid = $this->cid AND t1.sort != -1 AND ((t1.create_time > ".$date_begin." AND t1.create_time < ".$date_end.") OR (t1.updatetime > ".$date_begin." AND t1.updatetime < ".$date_end."))";        		
        		break;
			case 3:
        		$config['base_url'] = mk_url('ads/adadmin/listAd?date_selected=3');
        		$date_begin = strtotime(date('Y-m-d',strtotime('-7 day')));
        		$date_end = time();        		
        		
       			$where = " t1.cid = $this->cid AND t1.sort != -1 AND ((t1.create_time > ".$date_begin." AND t1.create_time < ".$date_end.") OR (t1.updatetime > ".$date_begin." AND t1.updatetime < ".$date_end."))";        		      		
        		break;
			case 4:
        		$config['base_url'] = mk_url('ads/adadmin/listAd?date_selected=4');
        		$date_begin = strtotime(date('Y-m-d',strtotime('-1 month')));
        		$date_end = time();  
        		
       			$where = " t1.cid = $this->cid AND t1.sort != -1 AND ((t1.create_time > ".$date_begin." AND t1.create_time < ".$date_end.") OR (t1.updatetime > ".$date_begin." AND t1.updatetime < ".$date_end."))";         		       		
        		break;
			case 5:
        		$config['base_url'] = mk_url('ads/adadmin/listAd?date_selected=5');
        		$date_begin = strtotime(date('Y-m-d',strtotime('-3 month')));
        		$date_end = time(); 
        		
       			$where = " t1.cid = $this->cid AND t1.sort != -1 AND ((t1.create_time > ".$date_begin." AND t1.create_time < ".$date_end.") OR (t1.updatetime > ".$date_begin." AND t1.updatetime < ".$date_end."))";         		       		
        		break;        		
       		        		        		
		}
        //分页设置	
        $this->load->library('URI');	
        $this->load->library('pagination');	
        $per_page = $this->input->get('per_page');
		$page = $this->input->get('page');
		$per_page = empty($per_page) ? 0 : $per_page; //当前游标
        $offset = empty($page) ? $this->system_config['listmaxnum'] : $page; //每页条数
	       
        $config['total_rows'] = $this->ad->getAdsCount($where);
        $config['per_page'] = $offset; 
		$config['page_query_string'] = true;         
        $this->pagination->initialize($config); 
        $page_links = $this->pagination->create_links();
     
      

		$adList = $this->ad->getAds($per_page,$offset,$where,'t1.create_time');
		
        $this->assign('page_links', $page_links);
		$this->assign('adList', $adList);	        		
		$this->assign('date_selected', $date_selected);			
	    $this->display('listad.html');
       
	}		
	

	/**
	 * @author: qianc
	 * @date 2012/7/4
	 * @desc: 查看广告
	 * @access public
	 */	
	function detailAd(){
		$ad_id = addslashes($this->input->get('ad_id')); 		
		if(!$this->checkAdid(array('cid'=>$this->cid,'ad_id'=>$ad_id,'sort !='=>-1))){
				$log =  '对不起，您无权操作！谢谢';
				$this->error($log);			
		}
		$this->common_user();		

		$adlist_where = " ad_id = $ad_id AND sort != -1 ";
		$adDetail = $this->ad->getAdInfo($adlist_where);		

		$adcrowd_where = " ad_id = $ad_id ";
		$adcrowd = $this->adcrowd->getCrowdInfo($adcrowd_where);	

		$adcost_where = " ad_id = $ad_id ";
		$adcost = $this->adcost->getCostInfo($adcost_where);
				
		//***获取支付以参数
		$pay_status = $this->input->get('status'); 
		$this->assign('pay_status', $pay_status);		
		
		$this->assign('adDetail', $adDetail[0]);	
		$this->assign('adcrowd', $adcrowd[0]);		
		$this->assign('adcost', $adcost[0]);
		$this->assign('ad_detail_url', mk_url('ads/adadmin/detailad',array('ad_id'=>$ad_id)));							
	    $this->display('ad_detail.html');
       
	}

	/**
	 * @author: qianc
	 * @date 2012/7/9
	 * @desc: 编辑广告
	 * @access public
	 */	
	function editAd(){
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
			
			$task_start_time =  strtotime($this->input->post('start_time'));		
				
			
			//目标对象区
			$obj_region = $this->input->post('region_id');
			$obj_interest = $this->input->post('interest');	
			$obj_classify = $this->input->post('classify');					
					
			
			if (mb_strlen($ad_title, 'utf-8') > 52 || mb_strlen($ad_title, 'utf-8') < 1) {
		 		$this->ajaxReturn('','请输入标题,小于52个字符符!',0,'json');					
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
			
			if($ad_budget_sort && $ad_budget < $this->system_config['budget']){
		 		$this->ajaxReturn('','预算总额最低须大于￥ '+$this->system_config['budget']+'元，请重新输入!',0,'json');				
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
			$ad_id = addslashes($this->input->get('ad_id'));
			if(!$this->checkAdid(array('cid'=>$this->cid,'ad_id'=>$ad_id,'sort !='=>-1))){
				$log =  '对不起，您无权操作！谢谢';
				$this->error($log);			
			}
						 	
			$adlist_where = " ad_id = $ad_id AND sort != -1 ";
			$adDetail = $this->ad->getAdInfo($adlist_where);
	
			$adcrowd_where = " ad_id = $ad_id ";
			$adcrowd = $this->adcrowd->getCrowdInfo($adcrowd_where);

			$adcost_where = " ad_id = $ad_id ";
			$adcost = $this->adcost->getCostInfo($adcost_where);
			
			//分类
			$classify_output = array("Web专页","个人网页");
			$classify_val = array("1","2");				
	
			//年龄
			$age_output = array("不限","10-15岁","16-22岁","23-30岁","31-40岁","41-50岁","50岁以上");
			$age_val = array("0","1","2","3","4","5","6");	
		
			//时间显示
			$begin_date = date("Y-m-d",strtotime('+1 day'));
			$end_date = date("Y-m-d",strtotime('+100 year'));;
			
			$this->assign('adDetail', $adDetail[0]);	
			$this->assign('adcrowd', $adcrowd[0]);		
			$this->assign('adcost', $adcost[0]);

	
	        $this->assign('begin_date',$begin_date);	
	        $this->assign('end_date',$end_date);

			$this->assign('classify_output',$classify_output);
			$this->assign('classify_val',$classify_val);		        
	        
	        
			$this->assign('age_output',$age_output);
			$this->assign('age_val',$age_val);		        
	        
			//显示原有广告信息
			if($this->ad->getAdMemcache()){
				$simple = $this->ad->getAdMemcache();
				$this->assign('adinfo',$simple);	
			}
	        
			//兴趣大分类
			$categories = $this->ad->getCategory(0, 1);
			$dataid = array();
			foreach ($categories as $k => $val){
				$dataid[] = $val['id'];
			}
			
			$this->assign('categories',$categories);
			$this->assign('data_id',implode(',', $dataid));
			$this->assign('ad_index_url', mk_url('ads/ad/addAd'));				
 			$this->assign('sys_budget_day',$this->system_config['budget']); 
 			$this->assign('sys_budget_all',$this->system_config['allbudget']);  
		    $this->display('ad_edit.html');			
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
			$this->assign('ad_confirm_url', mk_url('ads/adadmin/confirmAd'));					
	        $this->display('confirmad_edit.html');
	        				
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
		if($_SERVER['REQUEST_METHOD']=='POST' && $this->ad->getAdMemcache()){
				$simple =  $this->ad->getAdMemcache();		
				//广告内容区
				$ad_title = strFilter($simple['title']); //过滤字符串		
				$ad_content = strFilter($simple['introduce']); 
				$ad_name = strFilter($simple['name']);
				
				if($simple['start_time']!='null'){
					$ad_start_time = strtotime($simple['start_time']);
				}else{
					$ad_start_time = time();
				}
				
				
						
				$data_adlist = array('url'=>$simple['url'],'title'=>$ad_title,'introduce'=>$ad_content,'media_uri'=>$simple['media_uri'],'name'=>$ad_name,'start_time'=>$ad_start_time,'is_display'=>$simple['is_display'],'create_time'=>time(),'sort'=>3,'is_checked'=>'-1','cid'=>$this->cid,'classify'=>$simple['classify']);
				$adlist_where = array('ad_id'=>$simple['ad_id'],'sort !='=>-1);
				$ret_id_ad = $this->ad->editAd($data_adlist,$adlist_where);

							
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
																	
					$data_adcrowd = array('region'=>$obj_region,'region_rank'=>$obj_region_rank,'city'=>$obj_city,'interest'=>$obj_interest,'age_range'=>$obj_age_range,'gender'=>$obj_gender);				
					$adcrowd_where = array('ad_id'=>$simple['ad_id']);					
					$ret_id_adcrowd = $this->adcrowd->editCrowd($data_adcrowd,$adcrowd_where);
					
					
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

							$task_create_time = time();
							if($task_start_time=='null'){
								$task_start_time = $task_create_time;
							}else{
								$task_start_time = strtotime($task_start_time);							
							}							
							$data_adtask = array('start_time'=>$task_start_time,'create_time'=>$task_create_time);
							$adtask_where = array('ad_id'=>$simple['ad_id']);
							$ret_id_adtask = $this->adtask->editTask($data_adtask,$adtask_where);
						}

						if($ret_id_adtask){		
								$data_adcost = array('budget'=>$simple['budget'],'budget_sort'=>$simple['budget_sort'],'charge_mode'=>$simple['charge_mode'],'bid'=>$simple['bid']);
								$adcost_where = array('ad_id'=>$simple['ad_id']);
								$ret_id_adcost = $this->adcost->editCost($data_adcost,$adcost_where);

								$company_cost_where = " cid = $this->cid ";

								$company_cost_info = $this->adcompanycost->getCompanyCostInfo($company_cost_where);
								if(is_array($company_cost_info)){
									$money_available = $company_cost_info[0]['leave_money_format'];	
								}else {
									$money_available = 0;
								}							
																									
											
						}else{
							$this->ajaxReturn('','error!',0,'json');							
						}
					}else{
						$this->ajaxReturn('','error!',0,'json');			
					}	
				}else{
					$this->ajaxReturn('','error!',0,'json');			
				}
				//echo $ret_id_ad.'aa'.$ret_id_adcrowd.'bb'.$ret_id_adtask.'cc'. $ret_id_adcompany;exit;
				if ($ret_id_adcrowd) {
					$retData = $money_available > 0 ? array('data'=>array('url'=>mk_url('ads/adadmin/index'),'valid'=>true)) : array('data'=>array('url'=>mk_url('ads/adadmin/billad'),'valid'=>false));					
					
					$this->ad->delAdMemcache();		
					$this->ajaxReturn($retData['data'],'',1,'json');											

				}
				$this->ajaxReturn('','error!',0,'json');
		}
	}		
	
		
	/**
	 * @author: qianc
	 * @date 2012/7/4
	 * @desc: 暂停广告/启用广告/删除广告
	 * @access public
	 */	
	function statusAd(){
		$ad_id_arr = $this->input->post('ad_id'); 
		$ad_status = $this->input->post('ad_status');

		foreach ($ad_id_arr as $v){
			$where = array('ad_id'=>$v,'sort !='=>-1);
			$data = array('sort'=>$ad_status);
			
			$ret = $this->ad->editAd($data,$where);
			if(!$ret){
				break;
			}					
		}

		if($ret){
			$this->ajaxReturn('','操作成功!',1,'json');						
		}
		$this->ajaxReturn('','操作失败!',0,'json');							
	}
	
	
	/**
	 * @author: qianc
	 * @date 2012/7/4
	 * @desc: 广告报告
	 * @access public
	 */	
	function reportAd(){
		$this->common_user();		
        //分页设置		
        	
        $this->load->library('URI');	
        $this->load->library('pagination');	

        $date = $this->input->get('date');
        $sort = $this->input->get('sort') ? $this->input->get('sort') : 0;
       
        if(!$date)	$date = date('Y-m-d',strtotime('-1 day'));
        $startTime = strtotime($date);//开始时间
        $endTime = $startTime + 86400;//结束时间

 		$per_page = $this->input->get('per_page');
		$page = $this->input->get('page');
		$per_page = empty($per_page) ? 0 : $per_page; //当前游标
        $offset = empty($page) ? $this->system_config['listmaxnum'] : $page; //每页条数
 		
 		
		
 		$page_links = $reportList = array();
 		$total_rows = $this->ad->getReportCounts($startTime,$endTime,$sort,$this->cid);
 		if($total_rows){
 			$config['base_url'] = mk_url('ads/adadmin/reportAd/').'?sort='.$sort.'&date='.$date;
	       	$config['total_rows'] = $total_rows;
	       	$config['per_page'] = $offset;
	       	$config['page_query_string'] = TRUE;

	       	$this->pagination->initialize($config); 
	        $page_links = $this->pagination->create_links();
	
			$reportList = $this->ad->getReportList($per_page,$offset,$startTime,$endTime,$sort,$this->cid);
 		}

		
		$this->assign('sort', $sort);
		$this->assign('date', $date);
        $this->assign('page_links', $page_links);
		$this->assign('date', $date);
		$this->assign('reportList', $reportList);
	    $this->display('reportad.html');
	}		
	
	
	/**
	 * @author: qianc
	 * @date 2012/7/4
	 * @desc: 广告设置
	 * @access public
	 */	
	function setAd(){
		$this->common_user();		
		$adCompanyInfo = $this->companyInfo;
		
		//获取user信息
		$user_info = array();
		$user_info['username'] = $this->username;
		$user_info['url'] = mk_url('main/index/main');
        $user_info['avatar_img'] = get_avatar($this->uid,'ss');		
				
		//通知处理
		if($adCompanyInfo['notice']){
			$noticeArr = explode(',', $adCompanyInfo['notice']);
			$this->assign('noticeArr',$noticeArr);			
		}        
        
		if(!$adCompanyInfo['industry']){
			$adCompanyInfo['industry'] = 1;
		}
		       
		$this->assign('adCompanyInfo', $adCompanyInfo);	
		$this->assign('user_info', $user_info);			
				
	    $this->display('setad.html');
       
	}

	
	/**
	 * @author: qianc
	 * @date 2012/7/4
	 * @desc: 广告设置post
	 * @access public
	 */	
	function setAdPost(){
		$uid = $this->uid;
		$company_name = $this->input->post('name');//帐户名称
		$company_industry = $this->input->post('industry');//行业		
		$company_contact = $this->input->post('contact');//联系人
		$company_mobile = $this->input->post('mobile');//联系电话
		$company_email = $this->input->post('email');//联系邮箱		
		$company_remarks = $this->input->post('remarks');//备注
		
		if(!preg_match("/^(\d{3,4}-?)?\d{7,9}$/",$company_mobile)){
			$this->ajaxReturn('','电话号码格式不正确!',0,'json');									
		}
		if( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $company_email)) {
			$this->ajaxReturn('','Email格式不正确!',0,'json');							
		}
		//$where = " uid = $uid AND is_delete != 1 ";
		$where = array('uid'=>$uid,'is_delete !='=>1);		
		$data = array('name'=>$company_name,'industry'=>$company_industry,'contact'=>$company_contact,'tel'=>$company_mobile,'email'=>$company_email,'remarks'=>$company_remarks);

		$ret = $this->adcompany->editCompany($data,$where);		
				
		if($ret){
			$this->ajaxReturn('','操作成功!',1,'json');							
		}
		$this->ajaxReturn('','操作失败!',0,'json');				
       
	}

	
	/**
	 * @author: qianc
	 * @date 2012/7/7
	 * @desc: 广告通知设置post
	 * @access public
	 */	
	function setNoticePost(){
		$company_notice_arr = $this->input->post('notice');//通知项
		$company_notice_str = implode(',', $company_notice_arr);
		//$where = " uid = $uid AND is_delete != 1 ";
		$where = array('id'=>$this->cid,'is_delete !='=>1);		
		$data = array('notice'=>$company_notice_str);

		$ret = $this->adcompany->editCompany($data,$where);		
				
		if($ret){
			$this->ajaxReturn('','操作成功!',1,'json');			
		}
		$this->ajaxReturn('','操作失败!',0,'json');	
       
	}		


	/**
	 * @author: qianc
	 * @date 2012/7/4
	 * @desc: 广告帐单
	 * @access public
	 */	
	function billAd(){
		$this->common_user();		
		$uid = $this->uid;		
        //分页设置	
        
        $this->load->library('URI');	    	
        $this->load->library('pagination');	
        $per_page = $this->input->get('per_page');
		$page = $this->input->get('page');
		$per_page = empty($per_page) ? 0 : $per_page; //当前游标
        $offset = empty($page) ? $this->system_config['listmaxnum'] : $page; //每页条数
	       
		$where = " uid = $uid AND status = 1 ";	
        $config['total_rows'] = $this->adpay->getPaysCount($where,'pay_id');
        $config['base_url'] = mk_url('ads/adadmin/billAd');        
        $config['per_page'] = $offset; 
		$config['page_query_string'] = true;//尾部加上'per_page=X'            
        $this->pagination->initialize($config); 
        $page_links = $this->pagination->create_links();

		$adPayList = $this->adpay->getPays($per_page,$offset,$where,'pay_id');
		
		//帐户余额
		$company_cost_where = " cid = $this->cid ";	
		$company_cost_info = $this->adcompanycost->getCompanyCostInfo($company_cost_where);
		$money_available = 0;
		if($company_cost_info){
			$this->assign('company_cost_info', $company_cost_info[0]);				
		}
		
	
        $this->assign('page_links', $page_links);
		$this->assign('adPayList', $adPayList);	
		$this->assign('pay_money', $this->system_config['pay_money']);
		
	    $this->display('billad.html');
       
	}	
	
	
	
	/**
	 * @author: qianc
	 * @date 2012/7/5
	 * @desc: 广告帐单导出
	 * @access public
	 */	
	function expToXls(){
	
		$ad_id = intval($this->input->get('ad_id', TRUE));
		$date = $this->input->get('date', TRUE);
		if(!$ad_id || !$date){
			$this->error('非法操作');	exit();
		}
		
		$startTime = strtotime($date);
		$endTime = $startTime+60*60*24;

		$detail = $this->ad->getRecord($ad_id,$startTime,$endTime);
		if(!$detail){
			$this->error('暂无记录');	exit();
		}
		$displayNum = $clicksNum = 1;//序号
		$display = $clicks = $str = '';
		foreach($detail as $row){
			if($row['event_type']==1){//展示
				$display .= $displayNum++ ."\t";
				$display .= iconv("UTF-8", "GB2312", $row['title'])."\t";
				$display .= iconv("UTF-8", "GB2312", '展示')."\t";
				$display .= iconv("UTF-8", "GB2312", $row['url'])."\t";
				$display .= iconv("UTF-8", "GB2312", $row['ip'])."\t";
				$display .= iconv("UTF-8", "GB2312", date('Y-m-d H:i:s',$row['dateline']))."\n";
			}else{
				$clicks .= $clicksNum++ ."\t";
				$clicks .= iconv("UTF-8", "GB2312", $row['title'])."\t";
				$clicks .= iconv("UTF-8", "GB2312", '点击')."\t";
				$clicks .= iconv("UTF-8", "GB2312", $row['url'])."\t";
				$clicks .= iconv("UTF-8", "GB2312", $row['ip'])."\t";
				$clicks .= iconv("UTF-8", "GB2312", date('Y-m-d H:i:s',$row['dateline']))."\n";
			}
	    } 
	    
		header("Content-Type: application/vnd.ms-excel; charset=utf-8");
		header('Content-Disposition:filename='.iconv("UTF-8", "GBK", $this->strip_whitespace($detail[0]['title']).$date).'.xls');
		header("Pragma: no-cache"); 
		header("Expires: 0"); 
		
		$str .=  "序号"."\t";
		$str .=  "广告名称"."\t";
		$str .=  "事件类型"."\t";
		$str .=  "URL地址"."\t";
		$str .=  "ip"."\t";
		$str .=  "时间"."\n";
		echo iconv("UTF-8", "GBK", $str);

	    echo iconv("UTF-8", "GB2312", '展示记录')."\n";
	    echo $display;
	    echo iconv("UTF-8", "GB2312", '点击记录')."\n";
	    echo $clicks;
	    
		exit;

	}
	
	
	function outexcel(){
		
		if ($this->input->post('ad_submit')){
			
			$ids = $this->input->post('adList', TRUE);
			if(!$ids){
				$this->error('请至少选择一条数据,谢谢');exit;
			}
			
			$startt = $this->input->post('startt', TRUE);
			$endt = $this->input->post('endt', TRUE);
			$startTime = strtotime($startt);
			$endTime = strtotime($endt) + 86400;
			if (!$startTime || !$endTime){
				$this->error('请选择日期');exit;
			}
			
			$detail = $this->ad->outexcel($ids,$startTime,$endTime);
			if(!$detail){
				$this->error('暂无展示数据');exit();
			}

			$display = $str ='';
			foreach($detail as $k => $row){
				$display .= ($k + 1) ."\t";
				$display .= iconv("UTF-8", "GB2312", $row['ad_id'])."\t";
				$display .= iconv("UTF-8", "GB2312", $row['title'])."\t";
				$display .= iconv("UTF-8", "GB2312", $row['sort'])."\t";
				$display .= iconv("UTF-8", "GB2312", $row['show_count'])."\t";
				$display .= iconv("UTF-8", "GB2312", $row['click_count'])."\t";
				$display .= iconv("UTF-8", "GB2312", sprintf('%.2f%%',$row['click_count']/$row['show_count'] * 100))."\t";
				$display .= iconv("UTF-8", "GB2312", date('Y-m-d',$row['dateline']))."\n";
		    } 
		    
	
			header("Content-Type: application/vnd.ms-excel; charset=utf-8");
			header('Content-Disposition:filename='.iconv("UTF-8", "GB2312", '端口网广告报表').'.xls');
			header("Pragma: no-cache"); 
			header("Expires: 0"); 
			
			$str .=  "序号"."\t";
			$str .=  "广告ID"."\t";
			$str .=  "广告名称"."\t";
			$str .=  "状态"."\t";
			$str .=  "展示次数"."\t";
			$str .=  "点击次数"."\t";
			$str .=  "点击率"."\t";
			$str .=  "时间"."\n";
			echo iconv("UTF-8", "GBK", $str);
		    echo $display;
			exit;
		}
		exit;
		
	}

	/**
	 * 
	 * 去除空格
	 * @param str $str
	 */
	
	function strip_whitespace($str){
		if(!$str){
			return NULL;
		}
		$str = trim($str);
		$str = preg_replace("/(　){2,}/", "", $str); //把全角状态下空格踢除
		$str = preg_replace('/\s+/', '', $str);   //把英文状态下的空格踢除
		$str = preg_replace('/[\n\r\t]/', '', $str); //去掉非space的空格踢除
		
		return $str;
	}
	
	/**
	 * @author: qianc
	 * @date 2012/7/6
	 * @desc: 申请发票post
	 * @access public
	 */	
	function invoicePost(){
			$cid = $this->cid;
			$invoice_money = (int)$this->input->post('money');//开发票金额		
			$invoice_titles = $this->input->post('titles');//抬头
			$invoice_addressee = $this->input->post('addressee');//收件人		
			$invoice_tel= $this->input->post('tel');//联系电话
			$invoice_email = $this->input->post('email');//联系邮箱	
			$invoice_addr = $this->input->post('addr');//地址				
			$invoice_zipcode = $this->input->post('zipcode');//邮编
			
			if (!strlen(trim($invoice_money)) || !isset($invoice_money)) {
				$this->ajaxReturn('','请输入金额!',0,'json');					
			}			
			
			if(!is_numeric($invoice_money) || !$invoice_money){
				$this->ajaxReturn('','您输入的发票金额不合法!',0,'json');									
			}	

			if( $invoice_money < $this->system_config['invoice']){
				$this->ajaxReturn('',"发票金额必须大于".$this->system_config['invoice']."元!",0,'json');									
			}			
				
			if (!strlen(trim($invoice_titles)) || !isset($invoice_titles)) {
				$this->ajaxReturn('','请输入抬头!',0,'json');					
			}

			if (!strlen(trim($invoice_addressee)) || !isset($invoice_addressee)) {
				$this->ajaxReturn('','请输入收件人!',0,'json');					
			}

			if (!strlen(trim($invoice_tel)) || !isset($invoice_tel)) {
				$this->ajaxReturn('','请输入联系电话!',0,'json');					
			}			
			
			if(!preg_match("/^(\d{3,4}-?)?\d{7,9}$/",$invoice_tel)){
				$this->ajaxReturn('','电话号码格式不正确!',0,'json');									
			}
			
			if (!strlen(trim($invoice_email)) || !isset($invoice_email)) {
				$this->ajaxReturn('','请输入联系邮箱!',0,'json');					
			}	
						
			if( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $invoice_email)) {
				$this->ajaxReturn('','邮箱格式不正确!',0,'json');							
			}	

			if (!strlen(trim($invoice_addr)) || !isset($invoice_addr)) {
				$this->ajaxReturn('','请输入地址!',0,'json');				
			}	

			if (!strlen(trim($invoice_zipcode)) || !isset($invoice_zipcode)) {
				$this->ajaxReturn('','请输入邮政编码!',0,'json');					
			}	

			if (!preg_match("/^([0-9]{6})(-[0-9]{5})?$/",$invoice_zipcode))  {
				$this->ajaxReturn('','邮政编码格式不正确!',0,'json');					
			}
			
			$data = array('cid'=>$cid,'money'=>$invoice_money,'titles'=>$invoice_titles,'addressee'=>$invoice_addressee,'tel'=>$invoice_tel,'email'=>$invoice_email,'addr'=>$invoice_addr,'zipcode'=>$invoice_zipcode,'status'=>1);
			$ret = $this->adinvoice->newData(AD_INVOICE,$data);		
					
			if($ret){
				$this->ajaxReturn('','操作成功!',1,'json');								
			}
			$this->ajaxReturn('','操作失败!',0,'json');		

	}	
		
	

	/**
	 * @author: qianc
	 * @date 2012/7/16
	 * @desc: 判断选定广告是否存在
	 * @access private
	 */	
	private function checkAdid($arr){
		$ad_exist = $this->ad->checkAd($arr);	
		return $ad_exist;				
	}	
	

}


/* End of file adadmin.php */
/* Location: ./controllers/adadmin.php */