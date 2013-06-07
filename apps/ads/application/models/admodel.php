<?php

/*
 * [ Duankou Inc ]
 * title :
 * Created on 2012-6-13
 * @author qianc
 * discription : 广告model
 */

class Admodel extends MY_Model {

    function __construct() {
        parent::__construct();
       	$this->init_mongodb('ads');
        $this->init_memcache('user');
    }
    
	/**
	 * 	添加内容
	 *  @author	    qianc
	 * 	@date	    2012/6/27
	 * 	@param $table	新建数据所在表
	 * 	@param $data		新建的数据
	 *
	 * @return		最后插入数据的id
	 */
	public function newData($table = NULL, $data = NULL) {
		if(!$data or !$table ) {
			return FALSE;
		}
		$res = $this->db->insert($table,$data);
		if($res){
			return $this->db->insert_id();
		}else{
			return FALSE;
		}
	}    

    /**
     * 
     * 添加广告商 
     * @author hujiashan
     * @date 2012/06/14
     * @param int $uid
     * @param array $data
     */
    function add_company($uid = NULL, $data = NULL){
    	if(!$uid || !$data){
    		return false;
    	}
    	
    	$this->db->select('cid');
    	$query = $this->db->get(AD_COMPANY);
    	$rs = $query->row_array();
		if($rs){
			return $rs['cid'];
		}else{
			
			$rs = $this->db->insert(AD_COMPANY, $data); 
	        if($rs){
	        	return $this->db->insert_id();
	        }else{
	        	return false;
	        }
			
		}
    }
    
    /**
     * 
     * 添加广告
     * @param array $data
     * @date  2012/06/14
     * @author hujiashan
     */
    function add_ad($data){
    	if(empty($data)){
    		return FALSE;
    	}
    	
    	$rs = $this->db->insert(AD_LIST, $data); 
        if($rs){
        	return $this->db->insert_id();
        }else{
        	return false;
        }
    }
    
    
    /** 
     * @author: qianc
     * @date: 2012-6-26
     * @desc: 删除广告
     * @access public
     * @return bool
     */
    public function delete($id) {
        return $this->db->delete(AD_LIST, array('ad_id' => $id));
    }    
    
    
    /**
     * 
     * 修改广告信息
     * @author hujiashan
     * @date  2012/06/14
     * @param int $ad_id
     * @param array $data
     * @return false | true
     */
    function edit_ad($ad_id = NULL, $data = NULL){
    	
    	if(!$ad_id || !$data){
    		return FALSE;
    	}
		
    	$this->db->where('ad_id', $ad_id);
    	
		$this->db->update(AD_LIST, $data); 
		
		if($this->db->affected_rows()){
			return TRUE;
		}
		
		return FALSE;

    }
    
    
	/**
     * 
     * 查询要显示的N条广告
     * @author donghu
     * @date  2012/07/03
     * @return array
     */
    function getAdList($limit,$webId,$dkcode,$webUrl,$ip){
    	
    	$cacheTime = 5;//缓存时间
     	$weightCoefficients = 10;//权重系数   
     	$userInfo = service('UserWiki')->getAreaCode($dkcode);

    	$cid = service('Interest')->get_web_category_group($webId);//查询网页所属兴趣分类id
    	if(!$cid)return FALSE;
		if(!is_array($cid))$cid = array($cid);
		
		
		/* 当前登录用户的个人资料  */
	    $myCity = $userInfo['cityid']?$userInfo['cityid']:0;
	    $mySex = $userInfo['sex'];
	    $age = $userInfo['birthday']?$userInfo['birthday']:0;
	    
	    /* 当前缓存key */
	    $curKey = $myCity.$mySex.$age.$webId;
	    
	    
	    /* 缓存不存在则写缓存 */
		if(!($this->memcache->get($curKey))){
	    	/* 计算年龄段  */
	    	$ages = 0;
	    	if($age>=10&&$age<=15){$ages = 1;}
	    	elseif($age>=16&&$age<=22){$ages = 2;}
	    	elseif($age>=23&&$age<=30){$ages = 3;}
	    	elseif($age>=31&&$age<=40){$ages = 4;}
	    	elseif($age>=41&&$age<=50){$ages = 5;}
	    	elseif($age>50){$ages = 6;}
			
	    	/* 根据当前用户的性别,年龄,所在地区查询出针对当前登录用户的广告 */
	    	$sexCond = $mySex==1 ? ' gender!=2 ' : ($mySex==2 ? ' gender!=1 ' : ' 1=1 ');
	    	$interestCond = '';
	    	$cidArr = array();
	    	foreach($cid as $single){
	    			$arrTmp = explode('_',$single);
	    			$vTmp = '';
	    			foreach($arrTmp as $k=>$v){
							if($k==0){
								$vTmp = $arrTmp[$k];
							}else{
								$vTmp .= '_'.$arrTmp[$k];	
							}
							$cidArr[] = $vTmp;
	    			}
	    	}

			

	    	if($cidArr){
	    		$interestCond = ' CASE ';
	    		foreach($cidArr as $row){
	    			$interestCond .=  
	    					' WHEN  FIND_IN_SET(\''.$row.'\',interest) THEN TRUE ';
	    		}
	    		$interestCond .=' ELSE  FALSE END';
	    	}
	    	
	    	/* 过滤投放人群  */
	    	$sql = 'SELECT ad_id FROM '.AD_CROWD." WHERE $sexCond AND (age_range=0 OR age_range=$ages) AND (".($interestCond ? $interestCond : '1=1').")  AND ( city=1 OR FIND_IN_SET('$myCity',city))";
	    	$rs = $this->db->query($sql)->result_array();

	    	if(!$rs)	return FALSE;
	    	$ad_ids = array();
	    	foreach($rs as $row){
    			$ad_ids[] = $row['ad_id'];
    		}
    		$ad_ids = implode(',',$ad_ids);
    		$timenow = time();
    		/* 日程过滤  */
    		/*
    		
    		$sql = ' SELECT ad_id FROM '.AD_TASK.' WHERE is_delete=-1 AND start_time>='.$timenow.' AND end_time<='.$timenow.' AND ad_id IN('.$ad_ids.')';
    		die(var_dump($sql));
    		$rs = $this->db->query($sql)->result_array();
    		if(!$rs)	return FALSE;
    		$ad_ids = array();
    		foreach($rs as $row){
    			$ad_ids[] = $row['ad_id'];
    		}
    		$ad_ids = implode(',',$ad_ids);
    		*/
    		
    		/* 过滤掉没有可用余额的广告主  */
    		$sql = 'SELECT al.ad_id FROM '.AD_LIST.' AS al LEFT JOIN '.AD_COMPANY_COST.' AS acc ON al.cid=acc.cid WHERE al.ad_id IN('.$ad_ids.') AND al.is_checked=3  AND acc.all_money-acc.cost_money>0';
    		$rs = $this->db->query($sql)->result_array();

    		if(!$rs)	return FALSE;
    		$ad_ids = array();
    		foreach($rs as $row){
    			$ad_ids[] = $row['ad_id'];
    		}
    		$ad_ids = implode(',',$ad_ids);

    		/* 存入缓存  */
    		$ads = $this->db->query('SELECT ac.budget,ac.budget_sort,ac.charge_mode,al.ad_id,cid,title,introduce,media_uri,url,bid FROM '.AD_LIST.' AS al LEFT JOIN '.AD_COST.' AS ac ON al.ad_id=ac.ad_id  WHERE sort=3 AND al.is_valid=1  AND classify=1 AND al.start_time<'.$timenow.' AND    al.ad_id IN('.$ad_ids.')')->result_array();
    		#$ads = $this->db->query('SELECT al.ad_id,cid,title,introduce,media_uri,url,bid FROM '.AD_LIST.' AS al LEFT JOIN '.AD_COST.' AS ac ON al.ad_id=ac.ad_id  WHERE sort=3  AND classify=1 AND    al.ad_id IN('.$ad_ids.')')->result_array();
    		if(!$ads)	return FALSE;
	    	$cacheKey =  $myCity.$mySex.$age.$cid;
    		$weightsNum = 0;//权值计数器
    		foreach($ads as $k=>$row){
    			$weightsNum += $row['bid']*$weightCoefficients;
    			$ads[$k]['weightsNum'] = floor($weightsNum);
    		}
    		$this->memcache->set($curKey,$ads,$cacheTime);
    	}


    	/* 随机取出limit条记录  */
		$data = $this->getAdCache($limit,$curKey);
    	foreach($data as $row){
    		$logData = array(
    			'ad_id'		=>$row['ad_id'],
    			'cid'		=>$row['cid'],
    			'event_type'=>1,  //事件类型（1 展示 2点击）
    			'typeid'	=>$webId,
    			'url'		=>$webUrl,
    			'ip'		=>$ip,
    			'dateline'	=>time(),
    			'type'		=>1,
    			'is_valid' => 0 //事件是否有效（0 有效 1无效）
    		);
    		
    		/*  更新广告每日计数器,写入广告日志  */
    		$is_valid = $this->updateCounter($row,'show');
			$logData['is_valid'] = $is_valid;
			$this->createLog($logData);
    	} 

    	/* 删除缓存 */
		$this->memcache->delete($curKey);
    	return $data;
    }
    
    
 	/**
     * 
     * 更新Mongodb中的展示和点击计数器
     * @author donghu
     * @date  2012/08/13
     * @return 0 or 1
     */   
    function updateCounter($row,$index){
			$is_valid = 0;	
    		$today = date('Ymd');
			$charge_mode = $index=='click' ? 'CPC' : 'CPM';
    		/* 如果今天此条广告计数器存在,计数器+1或者重置数据库字段(今天展示次数所产生 的费用己达上限) */
    		$mongoData = array();
			$k = $row['budget_sort']==0 ? $row['ad_id'].'_'.$index.'_'.$today : $row['ad_id'].'_'.$index.'_total';
    		if($row['charge_mode']==$charge_mode){
				if($mongoData=$this->mongodb->where(array('k'=>$k))->get(MONGODB_COLLECTION_ADS_COUNTER)){
	    			++$mongoData['cnt'];var_dump($k);
	    			if($mongoData['cnt']>$mongoData['max']){
	    				$is_valid = 1;
	    				$this->db->query('UPDATE '.AD_LIST.' SET is_valid=-1 WHERE ad_id='.$row['ad_id']);
	    				$this->memcache->delete($curKey);
	    			}
	    			else{
	    				$this->mongodb->where(array('k'=>$k))->update(MONGODB_COLLECTION_ADS_COUNTER,$mongoData);
	    			}
	    		}else{
	    			$mongoData = array(
	    				'k'		=>$k,
	    				'cnt'	=>1,//展示数
	    				'max'	=>floor($row['budget']/$row['bid']),//根据每日预算和竞价计算出每日最大展示数
	    			);
	    			$this->mongodb->insert(MONGODB_COLLECTION_ADS_COUNTER,$mongoData);
	    		}
    		}
    		return $is_valid;
    }
    
	/**
     * 
     * 查询要显示的N条广告[个人页面]
     * @author donghu
     * @date  2012/07/31
     * @return array
     */
    function getPersonalAd($limit,$dkcode,$ip,$url){
    	$cacheTime = 5;//缓存时间
     	$weightCoefficients = 10;//权重系数    
     	$curKey = $dkcode;
    	/* 缓存不存在则写缓存 */
		if(!($this->memcache->get($curKey))){
    		/* 查询出投放到指定uid的广告id  */
	    	$ad_ids = $this->db->query('SELECT ad_ids FROM '.AD_CUSTOM.' WHERE dkcode='.$dkcode.' AND status=0')->row()->ad_ids;
	    	if(!$ad_ids)	return FALSE;
			
	    	/* 过滤掉没有可用余额的广告主  */
    		$sql = 'SELECT al.ad_id FROM '.AD_LIST.' AS al LEFT JOIN '.AD_COMPANY_COST.' AS acc ON al.cid=acc.cid WHERE al.ad_id IN('.$ad_ids.') AND al.is_checked=3  AND acc.all_money-acc.cost_money>0';
    		$rs = $this->db->query($sql)->result_array();
    		if(!$rs)	return FALSE;
    		$ad_ids = array();
    		foreach($rs as $row){
    			$ad_ids[] = $row['ad_id'];
    		}
    		$ad_ids = implode(',',$ad_ids);
			$timenow = time();
    		/* 存入缓存  */
    		$ads = $this->db->query('SELECT ac.budget,ac.budget_sort,ac.charge_mode,al.ad_id,cid,title,introduce,media_uri,url,bid FROM '.AD_LIST.' AS al LEFT JOIN '.AD_COST.' AS ac ON al.ad_id=ac.ad_id  WHERE al.start_time<'.$timenow.' AND sort=3 AND    al.ad_id IN('.$ad_ids.')')->result_array();
    		if(!$ads)	return FALSE; 
    		$weightsNum = 0;//权值计数器
    		foreach($ads as $k=>$row){
    			$weightsNum += $row['bid']*$weightCoefficients;
    			$ads[$k]['weightsNum'] = floor($weightsNum);
    		}
    		$this->memcache->set($curKey,$ads,$cacheTime);
    	}
    	$data = $this->getAdCache($limit,$curKey);
    	foreach($data as $row){
    		$logData = array(
    			'ad_id'		=>$row['ad_id'],
    			'cid'		=>$row['cid'],
    			'event_type'=>1,  //事件类型（1 展示 2点击）
    			'typeid'	=>$dkcode,
    			'url'		=>$url,
    			'ip'		=>$ip,
    			'dateline'	=>time(),
    			'type'		=>2,
    			'is_valid' => 0 //事件是否有效（0 有效 1无效）
    		);
    		
    		/*  写入广告点击日志  */
    		$this->createLog($logData);
    	} 
    	/* 删除缓存 */
		$this->memcache->delete($curKey);
		return $data;
    }

   /**
     * 
     * 从广告缓存中随机返回指定数目的广告
     * @author donghu
     * @date  2012/07/31
     * @return array
   */
    public function getAdCache($limit,$curKey){
    	/* 随机取出limit条记录  */
    	$ads = $this->memcache->get($curKey);
    	if(!$ads)return FALSE;
    	$max = count($ads);
    	if(!$max)	return FALSE;
    	$min = 0;
    	$mid=0;
    	$maxWeights = $ads[$max-1]['weightsNum'];
    	if(!$maxWeights)return FALSE;
    	$randArr = $this->myRand(0,$maxWeights);//获得一个随机排列的数组,数组的值的区间为0与最大权值之间
    	$dataIndex = array();//最终要返回的广告数组下标集合[ads数组中的下标]
		$data = array();//最终要返回的广告数组


		
    	/* 如果缓存中的数据量小于要查询的数量,则无需生成随机数,直接全部取出,否则生成随机数据  */
		if($limit>=$max){
    		for($i=0;$i<$max;$i++){
    			$dataIndex[] = $i;
    		}
    	}
		else{
			while(count($dataIndex)<$limit){
	    		$randNum = array_pop($randArr);
	    		if(count($randArr)==0)break;//当随机数全部用完的时候直接返回
	    		$minTmp = $min;
	    		$maxTmp = $max;
	    		$f = FALSE;
	    		while($minTmp<=$maxTmp){
	    			$mid = floor(($minTmp+$maxTmp)/2);
	    			$midValue = $ads[$mid]['weightsNum'];
	    			$afterMidValue = $ads[$mid+1]['weightsNum'];
					if(!isset($ads[$mid+1])){--$mid;break;}
	    			if(($randNum>$midValue) && ($randNum<=$afterMidValue)){
	    				$f=TRUE;break;
	    			}
	    			elseif($randNum==$midValue){
	    				$f=TRUE;break;
	    			}
	    			elseif($randNum>$midValue){
	    				$minTmp = $mid+1;
	    			}
	    			elseif($randNum<$midValue){
	    				$maxTmp = $mid-1;
	    			}
	    		}

	    		$randDataIndex = $mid==0 ? ($f ? 1 :  0) : $mid+1;
	    		$dataIndex[] = $randDataIndex;
	    		$dataIndex = array_unique($dataIndex);
			}
		}

    	foreach($dataIndex as $row){
    		$dataTmp = $ads[$row];
    		$dataTmp['t'] = urlencode($this->encryption($row,AD_ID_SECRET,'ENCODE'));//memcache下标
    		$dataTmp['index'] =urlencode($this->encryption($dataTmp['ad_id'],AD_ID_SECRET,'ENCODE'));//广告id 
    		$data[] = $dataTmp;
    	}
    	return $data;
    	
    }
    
    /**
     * 
     * 返回兴趣分类相关数据
     * @author donghu
     * @date  2012/06/21
     * @return array
     */
    public function getInterestCategory($id){
    	$method = '';//Soap远程调用的方法
    	if($id==0){//获取顶级分类
    		$method = 'get_category_main';
    		
    	}else{//获取指定父id的二级分类
    		$method = 'get_category_small';
    	}
    	return service('Interest')->$method($id);
    }
    
    
    /**
     * 
     * 根据网页id返回网页为粉丝数
     * @author donghu
     * @param @pageIds array 网页id集合
     * @date  2012/06/25
     * @return array
     */
    public function getFansNumByWebPageIds($webPageIds=array()){
    	#die(var_dump($webPageIds));
    	$webPageIdsTmp = array();//将数组数字下标加字母前缀 
    	$counter = 0;//网页粉丝总数
    	foreach($webPageIds as $k=>$v){
    		$webPageIdsTmp['p'.$k] = $v;
    		$webPageIds[$k] = NULL;
    		unset($webPageIds[$k]); 
    	}
    	//$rs = call_soap('social', 'Webpage', 'getMultiNumOfFollowers',array($webPageIdsTmp));
    	
    	$rs = service('WebpageRelation')-> getMultiNumOfFollowers($webPageIdsTmp);

    	foreach($rs as $row){
    		$counter+=$row;
    	}
    	return $counter;
    }
    
    
    /**
     * 
     *  根据兴趣id返回该兴趣分类下的所有网页的id
     * @author donghu
     * @param @$interestIds array 兴趣id集合
     * @date  2012/06/25
     * @return array
     */
    public function getWebPageIdsByInterestId($interestIds){
    	//return call_soap('interest', 'Index', 'get_web_info_iid',array($interestIds));
    	return service('Interest')->get_web_info_imid($interestIds);
    }
    
    
    /**
     * 
     * 根据二级兴趣id返回该兴趣分类下的所有网页的总粉丝数
     * @author donghu
     * @param @$interestIds array 兴趣id集合
     * @date  2012/06/25
     * @return array
     */
    public function getFansNumByInterestId($interestIds=array()){
    	return $this->getFansNumByWebPageIds($this->getWebPageIdsByInterestId($interestIds));
    	
    }
    
    /**
     * 
     * 获取所有分类
     * @author donghu
     * @date  2012/06/25
     * @return array
     */
    
    public function getAllCategory(){
    	//$categoriesTmp = call_soap('interest', 'Index', 'get_category_all');
    	
    	$categoriesTmp =  service('Interest')->get_category_all();
    	$categories = array();
    	foreach($categoriesTmp as $k=>$v){
    		$categories[$v['imid']][] = $v;
    	}
    	return $categories;
    }
    
    
	/**
     * 
     *根据parentid和分类层次获取兴趣子类
     * @author donghu
     * @date  2012/06/25
     * @return array
     */
    
    public function getCategory($pid,$level){
    	return service('Interest')->get_category_level($pid,$level);
    }
    
    
    /**
     * 
     * 根据传入的地区,年龄,性别三个条件查询出符合条件的用户总数量
     * @author donghu
     * @date  2012/06/25
     * @return array
     */
    public function getUserCount($region_id,$age_range,$sex){
    	return service('UserWiki')->getUserCount($region_id,$age_range,$sex);
    	//return call_soap('ucenter','UserWiki','getUserCount',array($region_id,$age_range,$sex));
    }
    
    /**
     * 
     * 生成随机数
     * @author donghu
     * @date  2012/07/03
     * @return array
     */
    public function myRand($begin=0,$end=0,$limit=6){
		$randArr = range($begin,$end);
    	shuffle($randArr);
    	return $randArr;
    	#return array_slice($randArr,0,$limit);
    }

    /** 
     * @author: qianc
     * @date: 2012-7-3
     * @desc: 获取广告列表
     * @access public
     * @return array
     */
    function getAds($nowpage =1, $limit=5, $where ,$orderby){
        //$from = ($nowpage - 1) * $limit;
       // $data = $this->db->from(AD_LIST)->where($where)->order_by($orderby,'DESC')->limit($limit,$from)->get()->result_array();
		$sql = "SELECT t1.*,t2.budget,t2.budget_sort,t2.bid,t2.charge_mode,t2.cost_money FROM ".AD_LIST." t1 LEFT JOIN ".AD_COST." t2 
		on t1.ad_id = t2.ad_id WHERE ".$where."
		 ORDER BY ".$orderby." DESC LIMIT ".$nowpage." , ".$limit;
        
    	$data = $this->db->query($sql)->result_array();    
    	foreach ($data as $k=>$v){
    		switch ($v['sort']){
    			case '1':
    				$data[$k]['str_status'] = '已暂停';
    				break;
    			case '3':
    				$data[$k]['str_status'] = '进行中';
    				break;    				
    		}
    		
    	    switch ($v['is_checked']){
    			case '-1':
    				$data[$k]['str_checked'] = '未审核';
    				break;
    			case '1':
    				$data[$k]['str_checked'] = '不通过';
    				break;    
    			case '3':
    				$data[$k]['str_checked'] = '通过';
    				break;      								
    		} 
    		   		
    	    switch ($v['budget_sort']){
    			case '0':
    				$data[$k]['str_budget_sort'] = '每日预算';
    				break;
    			case '3':
    				$data[$k]['str_budget_sort'] = '总预算';
    				break;    				
    		}   

    	    switch ($v['classify']){
    			case '1':
    				$data[$k]['str_classify'] = 'WEB专页';
    				break;
    			case '2':
    				$data[$k]['str_classify'] = '个人专页';
    				break;    				
    		}      		
    		$data[$k]['start_time'] = date("Y-m-d",$data[$k]['start_time']);
    		$data[$k]['budget_format'] = number_format($data[$k]['budget'],2,'.',' '); //预算
    		$data[$k]['cost_money_format'] = number_format($data[$k]['cost_money'],2,'.',' ');//花费    
    		$data[$k]['surplus_format'] = number_format($data[$k]['budget']-$data[$k]['cost_money'],2,'.',' ');//结余
    		$data[$k]['bid_format'] = number_format($data[$k]['bid'],2,'.',' ');//竞价 		     		 		   		
    		
    	}
  		return $data;


   	  	

    }  

    
    /** 
     * @author: qianc
     * @date: 2012-7-3
     * @desc: 获取广告数目
     * @access public
     * @return int
     */
    function getAdsCount($where){
		$sql = "SELECT count(t1.ad_id) as adsCount  FROM ".AD_LIST." t1 WHERE ".$where ; 
    	$data = $this->db->query($sql)->result_array();  
    	return $data['0']['adsCount'];


   	  	

    }  

    
    
	/**
	 * 	取得广告详情
	 *  @author	    qianc
	 * 	@date	    2012/7/4
	 * @return		array
	 */
	function getAdInfo($where) {
		if(!$where) {
			return FALSE;
		}
		$sql = "SELECT * FROM  ".AD_LIST." WHERE ".$where;
		
    	$data = $this->db->query($sql)->result_array();

	    	foreach ($data as $k=>$v){
    		switch ($v['sort']){
    			case '1':
    				$data[$k]['str_status'] = '已暂停';
    				break;
    			case '3':
    				$data[$k]['str_status'] = '进行中';
    				break;    				
    		}
    		$data[$k]['start_time'] = date("Y-m-d",$data[$k]['start_time']);
    		if($data[$k]['end_time']){
    			$data[$k]['end_time'] = date("Y-m-d",$data[$k]['end_time']);    		
    		}else{
    			$data[$k]['end_time'] = '';    			
    		}
    		//$data[$k]['budget_format'] = number_format($data[$k]['budget'],2,'.',' ');   		 		   		
    		
    	}    	
    	return $data; 

	}      
    
	
	/**
	 * 	编辑广告
	 *  @author	    qianc
	 * 	@date	    2012/7/4
	 *  @return		boolean
	 */
	public function editAd( $data = NULL, $where = NULL) {
		if(!$data or !$where) {
			return FALSE;
		}
		$this->db->where($where);
		return $this->db->update(AD_LIST,$data);
	}	
	
	
	/**
     * 
     * 插入广告日志[点击 与展示日志]
     * @author donghu
     * @date  2012/07/06
     * @return insert_id OR FALSE
	 */
	public function createLog($data){
		
		return $this->mongodb->insert(MONGODB_COLLECTION_ADS,$data);
	}
   
	
	/**
     * 
     * 点击广告后跳转到广告对应的url并作日志记录
     * @author donghu
     * @date  2012/07/06
     * @return insert_id OR FALSE
	 */
	public function adRedirect($index,$ad_id,$webUrl,$ip,$typeid,$type,$dkcode){
		$index = $this->encryption($index,AD_ID_SECRET,'DECODE');
		$ad_id = $this->encryption($ad_id,AD_ID_SECRET,'DECODE');

		$dataTmp = $this->db->query('SELECT ac.budget,ac.budget_sort,ac.charge_mode,al.ad_id,cid,title,introduce,media_uri,url,bid FROM '.AD_LIST.' AS al LEFT JOIN '.AD_COST.' AS ac ON ac.ad_id=al.ad_id WHERE al.ad_id='.$ad_id)->row_array();
		if(!$dataTmp)	redirect('/');
		$logDataWhere = array(
    		'ad_id'		=>$ad_id,
    		'event_type'=>2,
    		'typeid'	=>$typeid,
    		'url'		=>$webUrl,
    		//'ip'		=>$ip,
			'type'		=>$type,
			'dkcode'	=>$dkcode
    	);
    	
		/* 同一个ip的用户一天只能点击同一个web页面的一个广告一次,否则置为无效 */
    	$start_time = strtotime(date('Y-m-d'));
    	$end_time = $start_time+60*60*24;
    	$is_valid = $this->mongodb->where($logDataWhere)->where_gt('dateline',$start_time)->where_lt('dateline',$end_time)->get(MONGODB_COLLECTION_ADS) ? 0 : 1;
		$logData = array(
    		'ad_id'		=>$ad_id,
    		'cid'		=>$dataTmp['cid'],
    		'event_type'=>2,
    		'typeid'	=>$typeid,
    		'url'		=>$webUrl,
    		'ip'		=>$ip,
			'type'		=>$type,
    		'dateline'	=>time(),
			'dkcode'	=>$dkcode,
			'is_valid'	=>1//是否有效数据,1有效,0无效
    	);
    	$is_valid = $this->updateCounter($dataTmp,'click');
    	$logData['is_valid'] = $is_valid;
    	$this->createLog($logData);
		header("Location:$dataTmp[url]");
   }
   
   
	/**
     * 
     * 加密
     * @author donghu
     * @date  2012/07/06
     * @return string
	 */   
   public function encryption($string,$key,$operation='DECODE',$expiry = 0){
	    $ckey_length = 4;
	    $key = md5($key ? $key : '');
	    $keya = md5(substr($key, 0, 16));
	    $keyb = md5(substr($key, 16, 16));
	    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
	    $cryptkey = $keya . md5($keya . $keyc);
	    $key_length = strlen($cryptkey);
	    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
	    $string_length = strlen($string);
	    $result = '';
	    $box = range(0, 255);
	    $rndkey = array();
	    for ($i = 0; $i <= 255; $i++) {
	        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
	    }
	    for ($j = $i = 0; $i < 256; $i++) {
	        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
	        $tmp = $box[$i];
	        $box[$i] = $box[$j];
	        $box[$j] = $tmp;
	    }
	    for ($a = $j = $i = 0; $i < $string_length; $i++) {
	        $a = ($a + 1) % 256;
	        $j = ($j + $box[$a]) % 256;
	        $tmp = $box[$a];
	        $box[$a] = $box[$j];
	        $box[$j] = $tmp;
	        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	    }
	    if ($operation == 'DECODE') {
	        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
	            return substr($result, 26);
	        } else {
	            return '';
	        }
	    } else {
	        return $keyc . str_replace('=', '', base64_encode($result));
	    }    
   }
   
   
   
   
  	/**
     * 
     * 获取单条广告点击和展示路径以导出Excel  
     * @author donghu
     * @date  2012/07/10
     * @return array
	*/  
   public function getRecord($ad_id,$startTime,$endTime){

   		//al.sort 3正常  -1删除  1暂停
   		if(!$ad_id || !$startTime || !$endTime){
   			
   			return FALSE;
   		}

   		$records = $this->db->query('SELECT alist.title,alist.sort,alog.* FROM '.AD_LOG.' AS alog LEFT JOIN '.AD_LIST.' AS alist ON alist.ad_id=alog.ad_id WHERE alog.ad_id ='.$ad_id.' AND  alog.dateline>='.$startTime.' AND alog.dateline<'.$endTime)->result_array();
   		if($records){
	   		foreach($records as &$row){
				switch($row['sort']){
					case 3:	$row['sort'] = '正常';break;
					case -1:$row['sort'] = '删除';break;
					case 1:	$row['sort'] = '暂停';break;
				}
			}
			
			return $records;
		}
		return FALSE;
   }
   
   
   function outexcel($ad_id,$startTime,$endTime){
   	
   		if(!$ad_id || !$startTime || !$endTime){
   			
   			return FALSE;
   		}
   		
   		$where = '';
   		if(is_array($ad_id)){
   			$ad_id = implode(',', $ad_id);
			$where = "A.ad_id in($ad_id) AND ";
		}else{
			$where = "A.ad_id ='$ad_id' AND ";
		}
   		$records = $this->db->query('SELECT B.title,B.sort,A.* FROM '.AD_LOG_COUNT.' AS A LEFT JOIN '.AD_LIST.' AS B ON A.ad_id=B.ad_id WHERE '.$where.' (A.dateline>='.$startTime.' AND A.dateline<'.$endTime.') ORDER BY A.ad_id,A.dateline')->result_array();
   		if($records){
	   		foreach($records as &$row){
				switch($row['sort']){
					case 3:	$row['sort'] = '正常';break;
					case -1:$row['sort'] = '删除';break;
					case 1:	$row['sort'] = '暂停';break;
				}
			}
			
			return $records;
		}
		return FALSE;
   	
   }
   
   
   
  	/**
     * 
     * 获取广告报告 页面列表分页总数
     * @author donghu
     * @date  2012/07/10
     * @return int
	*/     
   public function getReportCounts($startTime,$endTime,$sort,$cid){
  	 	$sortCond = $sort ? 'B.sort='.$sort : 'B.sort != -1';
  	 	$data = $this->db->query('SELECT count(B.ad_id)as cot FROM '.AD_LOG_COUNT.' AS A LEFT JOIN '.AD_LIST.' AS B ON A.ad_id=B.ad_id WHERE  B.cid='.$cid.' AND '.$sortCond.'  AND (A.dateline>='.$startTime.' AND A.dateline<'.$endTime.')')->row(); 
   		return $data->cot;
   }
   
   
   
   	/**
     * 
     * 获取广告报告页列表
     * @author donghu
     * @date  2012/07/10
     * @return array
	*/   
   public function getReportList($page,$per_page,$startTime,$endTime,$sort,$cid){

   		$sortCond = $sort ? 'B.sort='.$sort : 'B.sort != -1';

   		/* 查询广告名称和ad_id */

   		$adBaseDate = $this->db->query('SELECT B.title,B.sort,A.* FROM '.AD_LOG_COUNT.' AS A LEFT JOIN '.AD_LIST.' AS B ON A.ad_id=B.ad_id WHERE B.cid='.$cid.' AND '.$sortCond.' AND (A.dateline>='.$startTime.' AND A.dateline<'.$endTime .')  ORDER BY B.ad_id LIMIT '.$page.','.$per_page)->result_array();
		if(!$adBaseDate){
   			return FALSE;
   		}
   		
   		
   		$adIdsStr = '';
   		foreach($adBaseDate as $row){
   			$adIdsStr .= $row['ad_id'].',';
   		}
   		$adIdsStr = substr($adIdsStr,0,strlen($adIdsStr)-1);//构造 ad_id WHERE IN 字符串

   		
   		/* 查询每条广告总费用 */
   		//$cost = $this->db->query('SELECT SUM(money) AS cost FROM '.AD_PAY_LOG.' WHERE dateline>='.$startTime.' AND dateline<'.$endTime.' AND ad_id IN('.$adIdsStr.') GROUP BY ad_id')->result_array();

   		//echo $this->db->last_query();
   		/* 查询每条广告点击数和展示数 */
   		//$clicks = $this->db->query('SELECT COUNT(event_type) AS cnt  FROM '.AD_LOG.' WHERE dateline>='.$startTime.' AND dateline<'.$endTime.' AND event_type=2 AND ad_id IN('.$adIdsStr.') GROUP BY ad_id')->result_array();
		//$display = $this->db->query('SELECT COUNT(event_type) AS cnt  FROM '.AD_LOG.' WHERE dateline>='.$startTime.' AND dateline<'.$endTime.' AND event_type=1 AND ad_id IN('.$adIdsStr.') GROUP BY ad_id')->result_array();
   		$data = array();

   		foreach($adBaseDate as $k=>$row){
   			$data[$k]['ad_id'] = $row['ad_id'];
   			$data[$k]['title'] = $row['title'];//标题
   			$data[$k]['status'] = $row['sort']==3 ? '正常' : ($row['sort']==-1 ? '己删除' : '暂停');//状态
   			$data[$k]['clicks'] = $row['click_count'];//点击次数
   			$data[$k]['display'] = $row['show_count'];//展示次数
   			//$data[$k]['averageCost'] = $data[$k]['clicks'] ? number_format($cost[$k]['cost']/$row['click_count'],1) : 0;//平均成本
   			$data[$k]['clicksRate'] = $data[$k]['display'] ? sprintf('%.2f%%',$row['click_count']/$row['show_count'] * 100) : 0;//点击率
   		}
   		return $data;
   }
   

   
	/**
	 * 检测广告是否存在
	 *
	 *  @author	    qianc
	 * 	@date	    2012/6/27
	 * 	@param $data		
	 * @return true / false
	 */
	public function checkAd($data = array()){
		if(empty($data) || 0 == sizeof($data)){
			return false;
		}

		$this->db->where($data);
		$result = $this->db->from(AD_LIST)->count_all_results();

		if(0 < $result){
			return TRUE;
		}

		return FALSE;
	}  

	
	/**
	 * 将广告数据存入memcache中
	 *
	 *  @author	    qianc
	 * 	@date	    2012/8/9
	 * 	@param $data		
	 *  @return true / false
	 */
	function setAdMemcache($data = array()){
		if(empty($data) || 0 == sizeof($data)){
			return false;
		}

    	$cacheTime = 86400;//缓存时间
    	if($this->memcache->set('adPost',$data,$cacheTime)){
    		return TRUE;
    	}
		return FALSE;
	} 

	
	/**
	 * 读取memcache中广告数据
	 *
	 *  @author	    qianc
	 * 	@date	    2012/8/9
	 * 	@param $data		
	 *  @return true / false
	 */
	function getAdMemcache(){
    	if($this->memcache->get('adPost')){
    		return $this->memcache->get('adPost');
    	}
		return FALSE;
	}

	
	/**
	 * 读取memcache中广告数据
	 *
	 *  @author	    qianc
	 * 	@date	    2012/8/9
	 * 	@param $data		
	 *  @return true / false
	 */
	function DelAdMemcache(){
    	if($this->memcache->delete('adPost')){
    		return TRUE;
    	}
		return FALSE;
	} 	
    
}


/* End of file admodel.php */
/* Location: ./app/models/admodel.php */