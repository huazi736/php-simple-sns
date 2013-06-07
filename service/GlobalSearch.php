<?php

/**
 * 全局搜索接口
 */
class GlobalSearchService extends DK_Service {

    protected $search_util = null;
    
    private $solr_global = null;
    
    private $spell = null;
    
    private static $METHOD = 'GET' ;

    public function __construct() {
        parent::__construct();
        // Load Libraries
        $this->search_util = load_class('SearchUtil', 'libraries', 'DK_');    
        $this->init_solr();
        $this->solr_global = $this->solr->getSolr('global');
    }

    public function test() {
    	echo $keyword = '你好理发师';
        $res = $this->getEventList($keyword);
        echo '<pre>';
        PRINT_R($res);
        echo '</pre>';
    }
    
    public function setPostRequest()
    {
    	self::$METHOD = 'POST';
    }
    
    public function setGetRequest()
    {
    	self::$METHOD = 'GET';
    }
    
    public function getStatisticsByGroup($keyword)
    {
		$keyword = strtolower($keyword);
		
		$query = $this->search_util->addWeightByField("userinfo_user_name", $keyword);
		
		$query .=' OR '. $this->search_util->addWeightByField("name", $keyword);
		
		if (preg_match("#^[a-z\\s]+$#is", $keyword)) $query .=' OR '.$this->search_util->addWeightByField("fullspell", $keyword, false);
		
		$query .=' OR '. $this->search_util->addWeightByField("content", $keyword);
		
		$query .=' OR '. $this->search_util->addWeightByField("title", $keyword);
		
		$params['facet']= 'on';
		$params['facet.field'] = 'type';
		$params['omitHeader'] = 'true';

		$return = $this->solr->query($this->solr_global, $query, 0, 0, $params);
		
		return $return;
	}
    /**
     * 获取人名与网页各8条的搜索
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     */
    public function getPeopleAndWebsite($keyword = null,  $start = 0, $limit = 8) 
    {
    	if (trim($keyword) == null) return $this->solr->getEmptyJSON();
    
        $keyword = strtolower($keyword);
        $is_letter = preg_match("/^[a-z\\s]+$/is", $keyword);
        $field_people = $this->search_util->addWeightByField("userinfo_user_name", $keyword) . '^15';
        $field_web = $this->search_util->addWeightByField("name", $keyword) . '^15';

        if ($is_letter) {
            $field_people_en = $this->search_util->addWeightByField('fullspell', $keyword, false) . '^5';
            $field_web_en = $this->search_util->addWeightByField("fullspell", $keyword, false) . '^5';
            $field_people = '(' . $field_people . ' OR ' . $field_people_en . ')';
            $field_web = '(' . $field_web . ' OR ' . $field_web_en . ')';
        }

        //人名
			$params["sort"] = "score desc,followersNum desc,registerTime desc";
			$params["fl"] = 'user_id,userinfo_user_name,user_dkcode, type';

			$return = $this->solr->query($this->solr_global, 'type:1 AND '.$field_people, $start, $limit, $params);////json_decode($this->solr_global->search('type:1 AND '.$field_people, $start, $limit, $params, self::$METHOD)->getRawResponse());

        //网页
		    $params["sort"] = "score desc,fansCount desc,createTime desc";
		    $params["fl"] = 'web_id, fansCount, name, type, user_id';

	    	$rtn = $this->solr->query($this->solr_global, 'type:2 AND '.$field_web, $start, $limit, $params);//json_decode($this->solr_global->search('type:2 AND '.$field_web, $start, $limit, $params, self::$METHOD)->getRawResponse());

        return array('people' => $return->response->docs, 'website' => $rtn->response->docs);
    }

    /**
     * 搜索人名
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getPeopleList($keyword, $start=0, $limit=10, $condition = array()) 
    {
        if (trim($keyword) == null) return  $this->solr->getEmptyJSON();

        $keyword = strtolower($keyword);
        $is_letter = preg_match("/^[a-z\\s]+$/is", $keyword);
        $field_people = $this->search_util->addWeightByField("userinfo_user_name", $keyword) . '^10';

        if ($is_letter) {
            $field_people_en = $this->search_util->addWeightByField('fullspell', $keyword, false) . '^5';
            $field_people = '(' . $field_people . ' OR ' . $field_people_en . ')';
        }
        
        
        if ($condition['college'] != false && trim($condition['college']) != '')  $field_people .= ' AND school_name:'.$condition['college'];
        	
        if ($condition['middle_school'] != false && trim($condition['middle_school']) != '')   $field_people .= ' AND school_name:'.$condition['middle_school'];
        	
        if ($condition['now_addr'] != false && trim($condition['now_addr']) != '')  $field_people .= ' AND now_addr:*'.$condition['now_addr'].'*';
        
        if ($condition['home_addr'] != false && trim($condition['home_addr']) != '' ) $field_people .= ' AND home_addr:*'.$condition['home_addr'].'*';
        	
        if ($condition['company'] != false && ($company = trim($condition['company'])) != '') 
        {
        	$length = preg_match_all("#[".chr(0xa1)."-".chr(0xff)."a-zA-Z0-9]+#", $company, $matches);
        	if ($length > 0)
        	{
        		$company = implode("", current($matches));
        		$field_people .= ' AND (company:'.$company.' OR company:*'.$company.'*)';
        	}else{
        		return  $this->solr->getEmptyJSON();
        	}
        }

		$params["sort"] = "score desc,followersNum desc,registerTime desc";
		
		$params["fl"] = 'user_id,userinfo_user_name,user_dkcode, type, school_name, company, now_addr,private_access,school_access,company_access';

        $return = $this->solr->query($this->solr_global, 'type:1 AND '.$field_people, $start, $limit, $params);//$this->solr_global->search('type:1 AND '.$field_people, $start, $limit, $params, self::$METHOD)->getRawResponse();

        return $return;
    }

    /**
     * 搜索网页
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getWebPageList($keyword, $start=0, $limit=10) 
    {
        if (trim($keyword) == null) return  $this->solr->getEmptyJSON();

        $keyword = strtolower($keyword);
        $is_letter = preg_match("/^[a-z\\s]+$/is", $keyword);
        $field_web = $this->search_util->addWeightByField("name", $keyword) . '^10';

        if ($is_letter) {
            $field_web_en = $this->search_util->addWeightByField("fullspell", $keyword, false) . '^5';
            $field_web = '(' . $field_web . ' OR ' . $field_web_en . ')';
        }

		 $params["sort"] = "score desc,fansCount desc,createTime desc";
		 $params["fl"] = 'web_id, fansCount, name, type, user_id, createTime';
		 $return = $this->solr->query($this->solr_global, 'type:2 AND '.$field_web, $start, $limit, $params);//$this->solr_global->search('type:2 AND '.$field_web, $start, $limit, $params, self::$METHOD)->getRawResponse();
        
        return $return;
    }

    /**
     * 搜索状态
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getStatusList($keyword, $start=0, $limit=10) 
    {
        if (trim($keyword) == null) return $this->solr->getEmptyJSON();

        //正常搜索  
        $keyword = strtolower($keyword);
        $where = $this->search_util->addWeightByField('content', $keyword);
		$params['sort'] = "score desc,hot desc, createTime desc";
		$params['fl'] = 'user_dkcode,status_type,createTime,content_show,user_id,user_name,web_name,type,person_web_type,web_id,createTime,picurl,imgurl';
        $return = $this->solr->query($this->solr_global, 'type:3 AND '.$where, $start, $limit, $params);//json_decode($this->solr_global->search('type:3 AND '.$where, $start, $limit, $params, self::$METHOD)->getRawResponse());
        
        return $return;
    }

    /**
     * 搜索图片
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getPhotoList($keyword, $start=0, $limit=10) 
    {	
        if (trim($keyword) == null) return $this->solr->getEmptyJSON();
        
        $keyword = strtolower($keyword);
        //正常搜索  
		$params['sort'] = "score desc,totalCount desc, createTime desc";
		$params['fl'] = 'id,user_dkcode, album_id, groupname, description,file_name,photo_type, name, type, person_web_type, web_id, createTime,web_name,user_name,user_id';
		$where = $this->search_util->addWeightByField('name', $keyword);
        $return = $this->solr->query($this->solr_global, 'type:4 AND '.$where, $start, $limit, $params);//json_decode($this->solr_global->search('type:4 AND '.$where, $start, $limit, $params, self::$METHOD)->getRawResponse());

        return $return;
    }

    /**
     * 搜索相册
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getAlbumList($keyword, $start=0, $limit=10) 
    {
        if (trim($keyword) == null) return $this->solr->getEmptyJSON();
        $keyword = strtolower($keyword);
        //正常搜索
        $where = $this->search_util->addWeightByField('name', $keyword);
 		$params['sort'] = "score desc,totalCount desc, createTime desc";
		$params['fl'] = 'id, user_dkcode, groupname, photo_count,photo_type,file_name, name, description, type, person_web_type, web_id, createTime';
        $return = $this->solr->query($this->solr_global, 'type:5 AND '.$where, $start, $limit, $params);//json_decode($this->solr_global->search('type:5 AND '.$where, $start, $limit, $params, self::$METHOD)->getRawResponse());

        return $return;
    }

    /**
     * 搜索视频
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getVideoList($keyword, $start=0, $limit=10) 
    {	
        if (trim($keyword) == null) return $this->solr->getEmptyJSON();

        $keyword = strtolower($keyword);
        //正常搜索
        $where = $this->search_util->addWeightByField('title', $keyword);
 		$params['sort'] = "score desc,totalCount desc, createTime desc";
		$params['fl'] = 'id,discription,title,video_pic,type, person_web_type, web_id, createTime, user_name,web_name, user_dkcode';
        $return = $this->solr->query($this->solr_global, 'type:6 AND '.$where, $start, $limit, $params);//json_decode($this->solr_global->search('type:6 AND '.$where, $start, $limit, $params, self::$METHOD)->getRawResponse());

        return $return;
    }

    /**
     * 搜索博客
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getBlogList($keyword, $start=0, $limit=10) 
    {
        if (trim($keyword) == null) return $this->solr->getEmptyJSON();

        $keyword = strtolower($keyword);
        //正常搜索
        $where = $this->search_util->addWeightByField('title', $keyword);
 		$params['sort'] = "score desc,totalCount desc, createTime desc";
		$params['fl'] = 'id,summary,user_id,user_name,user_dkcode, type, title, createTime';
        $return = $this->solr->query($this->solr_global, 'type:7 AND '.$where, $start, $limit, $params);//json_decode($this->solr_global->search('type:7 AND '.$where, $start, $limit, $params, self::$METHOD)->getRawResponse());

        return $return;
    }

    /**
     * 搜索问答
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getQuestionAndAnswerList($keyword, $start=0, $limit=10)
    {	
        if (trim($keyword) == null) return $this->solr->getEmptyJSON();
        
        $keyword = strtolower($keyword);
        //正常搜索
        $where = $this->search_util->addWeightByField('title', $keyword);
 		$params['sort'] = "score desc,totalVotes desc, createTime desc";
		$params['fl'] = 'title,multiple,id,user_dkcode,ask_option_list,totalVotes, type, createTime, user_id, user_name';
        $return = $this->solr->query($this->solr_global, 'type:8 AND '.$where, $start, $limit, $params);//json_decode($this->solr_global->search('type:8 AND '.$where, $start, $limit, $params, self::$METHOD)->getRawResponse());

        return $return;
    }

    /**
     * 搜索活动
     * 
     * Enter description here ...
     * @param string $keyword 关键词
     * @param int $start 开始位置
     * @param int $limit 显示条数
     */
    public function getEventList($keyword, $start=0, $limit=10) 
    {	
        if (trim($keyword) == null) return $this->solr->getEmptyJSON();
        //正常搜索
        $keyword = strtolower($keyword);
        $where = $this->search_util->addWeightByField('name', $keyword);
 		$params['sort'] = "score desc,joinNum desc";
		$params['fl'] = 'id,name,starttime,fdfs_group,fdfs_filename, type, person_web_type, web_id, joinNum, endtime, user_name, web_name';
        $return = $this->solr->query($this->solr_global, 'type:9 AND '.$where, $start, $limit, $params);//json_decode($this->solr_global->search('type:9 AND '.$where, $start, $limit, $params, self::$METHOD)->getRawResponse());
		
        return $return;
    }
}