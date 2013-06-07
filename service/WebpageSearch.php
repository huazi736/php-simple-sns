<?php

/**
 * 网页搜索接口
 */
class WebpageSearchService extends DK_Service {

	private $search_util = null ;
	
	private $solr_redis = null;
	
	private $solr_global = null;
	
	private $result = array('total' => 0);
	
    public function __construct() 
    {
        parent::__construct();
        // Load Libraries
        $this->search_util = load_class('SearchUtil', 'libraries', 'DK_');

        $this->init_solr();
        
        $this->solr_redis = $this->solr->getSolr('redis');
        
        $this->solr_global = $this->solr->getSolr('global');
    }
    
    public function test() {
        $res = $this->deleteUserOfWeb(637,1000002395);
        echo '<pre>';
        PRINT_R(json_decode($res));
        echo '</pre>';
    }
    /**
     * 关注网页
     * 
     * Enter description here ...
     * @param array $info
     */
    public function addAFansToWeb($info) 
    {//DONE
        return $this->addAFanToAWeb($info);
    }

    /**
     * 取消对网页隐藏
     * 
     * Enter description here ...
     * @param array $info
     */
    public function unHidingAUserInWebpage($info) 
    {//DONE
        return $this->hiddingOrUnhidding($info);
    }

    /**
     * 隐藏网页
     * Enter description here ...
     * @param array $info
     */
    public function hidingAUserInWebpage($info) 
    {//DONE
        return $this->hiddingOrUnhidding($info, 1);
    }

    /**
     * 取消关注网页
     * 
     * Enter description here ...
     * @param int $web_id
     * @param int $user_id
     */
    public function deleteUserOfWeb($web_id=null, $user_id=null) 
    {//DONE
		if ($user_id != null && $user_id != null)
		{
			$unique_id='id:'.$web_id;
			
			$unique_id.=' AND user_id:'.$user_id;
			
			$unique_id.=' AND (category:follower_webpage OR category:webpage_follower)';

			if ($this->solr->deleteByQuery($unique_id, $this->solr_redis))
			{
				$this->addOrReduceAFans($web_id, false);
				
				return true;
			}
		}	
			return false;
    }

    /**
     * 网页的粉丝(搜索人名)
     * 
     * Enter description here ...
     * @param int $web_id
     * @param string $keyword
     * @param int $page
     * @param int $limit
     */
    public function getFansOfWebpage($web_id = null, $keyword = '', $page=1, $limit=27) 
    {//DONE
    	if ($web_id == null)   return json_encode($this->result);
        
        $start = ($page - 1) * $limit;
        
        if ($this->search_util->isBackWithEmpty($keyword))   return json_encode($this->result);
		
        $query = 'id:'.$web_id.' AND category:webpage_follower';

        if (trim($keyword) != '') $query .= ' AND '.$this->search_util->addWeightByField('user_name', $keyword);

		$params['sort'] = trim($keyword) == null ? 'createTime desc' : 'score desc,createTime desc';
		
		$params['fl'] = 'user_id,user_name,user_dkcode';
		
        $return = $this->solr->query($this->solr_redis, $query,$start,$limit,$params);//json_decode($this->solr_redis->search($query,$start,$limit,$params)->getRawResponse());                

        return json_encode($this->search_util->formatObject($return));
    }

    /**
     * 用户关注的网页 (搜索网页)
     * 
     * Enter description here ...
     * @param int $user_id
     * @param int $category_id
     * @param string $keyword
     * @param int $page
     * @param int $limit
     */
    public function getWebpagesByUser($user_id = null, $category_id=null, $keyword = null, $page=1, $limit=27) 
    {//DONE
    	if ($user_id == null || $category_id == null)  return json_encode($this->result);
        
//    		if ($this->search_util->isBackWithEmpty($keyword)) return json_encode($this->result);
		$query = "user_id:".$user_id." AND iid:".$category_id." AND category:follower_webpage";
        
		if (trim($keyword) != '') $query.= ' AND '.$this->search_util->addWeightByField('name', $keyword);

        $start = ($page - 1) * $limit;
        
        $params['sort'] =  trim($keyword) == null ? 'createTime desc' : 'score desc,followersNum desc,createTime desc';
        
        $params['fl'] = 'id, name, type, creator_id';

        $return = $this->solr->query($this->solr_redis, $query,$start,$limit,$params);//json_decode($this->solr_redis->search($query,$start,$limit,$params)->getRawResponse());                

        return json_encode($this->search_util->formatObject($return));
    }

	/**
	 * 添加或减少一个粉丝
	 * 
	 */
	private function addOrReduceAFans($web_id, $is_incr = true)
	{//DONE
		$unique_id = 'apps_info_'.$web_id;
		
		$this->init_db('solr');
		
		$table = 'solr_webpage';
		
		$field = '`id`,`web_id`';
		
		$web_id = intval($web_id);
		
		$value = '\'\','.$web_id;
		
		$delete = 'DELETE FROM '.$table.' WHERE web_id='.$web_id;
		
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		
		@$this->db->query($delete);
		
		@$this->db->query($sql);
		
		return $this->renewFollowersCountByUser($unique_id, $is_incr);
	}
	
	/**
	 * 添加关注
	 * 
	 * Enter description here ...
	 * @param array $info=[web_id,uid,user_dkcode,user_name,following_time,fans_count]
	 * @param int $is_hidden 显示:0, 隐藏:1
	 */
	private function addAFanToAWeb($info)
	{//DONE
		if ($info['web_id'] == null || $info['uid'] == null) return false;
		
		$field='a.uid,a.name,a.create_time,b.iid';
		
		$sql=sprintf('select %s from apps_info as a join apps_info_category as b on a.aid=\'%d\' and a.aid=b.aid ',$field,$info['web_id']);
		
		$this->init_db('interest');
		
		$web_info = $this->db->query($sql)->result_array();
		
		if (count($web_info) == 0) return false;
		
		$doc = array();
		//关注网页
		foreach ($web_info as $key =>$val)
		{	
			$doc[$key]['creator_id'] = $val['uid'];
			
			$doc[$key]['name'] = $val['name'];
			
			$doc[$key]['iid'] = $val['iid'];
			
			$doc[$key]['createTime']=(int)strtotime($val['create_time']);
			//参数
			$doc[$key]['category']='follower_webpage';
			
			$doc[$key]['type'] = 0;
			
			$doc[$key]['followersNum'] = (int)$info['fans_count'];
			
			$doc[$key]['id'] = $info['web_id'];
			
			$doc[$key]['user_id'] = $info['uid'];
			
			$doc[$key]['unique_id']='follower_webpage_'.$val['iid'].'_'.$info['web_id'].'_'.$info['uid'];
		}
		//网页的粉丝
		$key ++;
		
		$doc[$key]['unique_id'] = 'webpage_follower_'.$info['web_id'].'_'.$info['uid'];
		
		$doc[$key]['id'] = $info['web_id'];
		
		$doc[$key]['user_id'] = $info['uid'];
		
		$doc[$key]['user_name'] = $info['user_name'];
		
		$doc[$key]['user_dkcode'] = $info['user_dkcode'];
		
		$doc[$key]['category'] = 'webpage_follower';
		
		$doc[$key]['createTime'] = (int)$info['following_time'];

		if($this->solr->addDocs($doc, $this->solr_redis))
		{
			$this->addOrReduceAFans($info['web_id']);
			
			return true;
		}
		return false;
	}
	
	/**
	 * 显示或隐藏网页
	 * 
	 * Enter description here ...
	 * @param array $info 
	 * @param int $is_hidding 0:显示,1:隐藏
	 */
	private function hiddingOrUnhidding($info, $is_hidding=0)
	{//DONE
		$field='a.uid,a.name,a.iid,b.create_date';
		
		$sql=sprintf('select %s from apps_info as a join user_apps_attention as b on a.aid=\'%s\' and a.aid=b.aid and b.uid=\'%s\'',$field,$info['web_id'],$info['user_id']);
		
		$this->init_db('interest');
		
		$web_info = $this->db->query($sql)->result_array();

		if (count($web_info) == 0) return false;
		
		$doc = array();
		//关注网页
		foreach ($web_info as $key =>$val)
		{
			$doc[$key]['category']='follower_webpage';
			
			$doc[$key]['type'] = $is_hidding;	
			
			$doc[$key]['createTime']=(int)$val['create_date'];
			
			$doc[$key]['creator_id'] = $val['uid'];
			
			$doc[$key]['name'] = $val['name'];
			
			$doc[$key]['id']=$info['web_id'];
			
			$doc[$key]['iid'] = $val['iid'];
			
			$doc[$key]['user_id'] = $info['user_id'];
			
			$doc[$key]['unique_id']='follower_webpage_'.$val['iid'].'_'.$info['web_id'].'_'.$info['user_id'];
		}

		return $this->solr->addDocs($doc, $this->solr_redis);	
	}
	
	private function renewFollowersCountByUser($unique_id, $is_incr = true, $count='fansCount', $delete=array())
	{
		$query = 'unique_id:'.$unique_id;
		
		$response = $this->solr->query($this->solr_global, $query,0,1);//json_decode($this->solr_global->search($query,0,1)->getRawResponse());
		
		if ($response->response->numFound > 0)
		{
			foreach ($response->response->docs as $val)
			{
				if (isset($val->$count))
				{
					$is_incr ? $val->$count += 1 : $val->$count -= 1;
					
					if ($val->$count < 0)     $val->$count = 0;
					
				}else{
					$val->$count = $is_incr ? 1 : 0;
				}
			}
			
			$doc = new Apache_Solr_Document();
			
			foreach ($val as $key => $v)
			{
				if (in_array($key, $delete)) continue;
				
				$doc -> $key = $v;
			}
			
			return $this->solr->execute($doc, $this->solr_global);
		}
		return false;
	}
}