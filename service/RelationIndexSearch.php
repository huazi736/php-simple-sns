<?php

class RelationIndexSearchService extends DK_Service {

	private $solr_global = null;
	
	private $search_util = null;
	
	private $solr_redis = null;
	
    public function __construct() {
        parent::__construct();
        $this->search_util = load_class('SearchUtil', 'libraries', 'DK_'); 
        $this->init_solr();
        $this->init_db('user');
        $this->solr_global = $this->solr->getSolr('global');
        $this->solr_redis = $this->solr->getSolr('redis');
    }
    
    public function test() {
    	
    	$id=386;
        $res = $this->deleteWebpage($id);
        echo '<pre>';
        PRINT_R($res);
        echo '</pre>';
    }

    public function addAFansForOne($follow_id) 
    {//DONE
        $unique_id = 'user_info_' . $follow_id;
        return $this->renewFollowersCountByUser($unique_id, true, 'followersNum', array('user_place'));
    }

    public function removeAFansForOne($follow_id) 
    {//DONE
        $unique_id = 'user_info_' . $follow_id;
        return $this->renewFollowersCountByUser($unique_id, false, 'followersNum', array('user_place'));
    }

    //人名：注册与更新信息
    public function addOrUpdateBasalInfoOfPeople($user_info) 
    {//DONE
       	$doc = array();
		$doc['unique_id'] = 'user_info_'.$user_info['uid'];
		$doc['userinfo_user_name'] = $user_info['uname'];
		$doc['fullspell'] = $this->solr->chinese2Pinyin($user_info['uname']);
		$doc['user_id'] = $user_info['uid'];
		$doc['user_dkcode'] = $user_info['dkcode'];
		$doc['followersNum'] = (int)$user_info['follower_num'];
		$doc['type'] = 1;
		$doc['company'] = $user_info['company'];
		$doc['home_addr'] = $user_info['home_addr'];
		$doc['now_addr'] = $user_info['now_addr'];
		$doc['school_name'] = $user_info['school_name'];
		$doc['registerTime'] = (int)$user_info['regdate'];

		return $this->solr->addDoc($doc, $this->solr_global);
    }

    //人名修改
    public function onlyUpdatePeopleName($user_info) 
    {//DONE
       	$record=array();
		$record['uid']=$user_info['uid'];
		$record['username']=$user_info['uname'];
		if ($this->addOrUpdateBasalInfoOfPeople($user_info))
		{
			$table = 'user_record_update';
			$user_id= $record['uid'];
			$user_name= $record['username'];
			$state = 1;
			$is_del_replicat = false;
			$sql = sprintf("INSERT INTO %s VALUES('','%d','%s','%d')",$table,$user_id,$user_name,$state);
			if ($is_del_replicat)
			{
				$del = sprintf("DELETE FROM %s WHERE uid='%d'",$table,$user_id);
				$this->db->query($del);
			}
			return $this->db->query($sql);
		}
		return false;	
    }

    //网页:注册与更新信息
    public function addOrUpdateWebpageinfo($web_info) 
    {//DONE
        $doc = array();
		$doc['unique_id'] = 'apps_info_'.$web_info['web_id'];
		$doc['fansCount'] = (int)$web_info['fans_count'];
		$doc['name']=$web_info['name'];
		$doc['pinyin']=$this->solr->chinese2Pinyin($web_info['name']);
		$doc['type']=2;
		$doc['user_id']=$web_info['uid'];
		$doc['web_id']=$web_info['web_id'];
		$doc['createTime'] = (int)strtotime($web_info['create_date']);

		return $this->solr->addDoc($doc, $this->solr_global);
    }

    //状态:发布与更新状态信息
    public function addOrUpdateStatusInfo($status_info) 
    {
    	$doc = array();
		$status = json_decode($status_info);

		$doc['user_id'] = $status->uid;
		$doc['user_dkcode'] = $status->dkcode;
		$doc['type'] = 3;
		$doc['status_type'] = $status->type;
		$doc['hot'] = (int)$status->hot;
		$doc['content'] = $this->search_util->htmlChars(strip_tags($status->content), false);
		$doc['content_show'] = $status->content;
		if ($status->from == 3)//来至网页
		{
			$doc['person_web_type'] = 1;
			$doc['web_id'] = $status->pid;
			$doc['web_name'] = $status->uname;
			$doc['createTime'] = (int)strtotime($status->dateline);
			$doc['unique_id']='web_topic_'.$status->tid;
		}else if ($status->from == 1){//来至个人
			$doc['person_web_type'] = 0;
			$doc['user_name'] = $status->uname;
			$doc['createTime'] = (int)$status->dateline;
			$doc['unique_id']='Topic_'.$status->tid;
		}
		//新增字段 LiuGC 2012/07/14 
		switch ($status->type)
		{
			case 'album':
				$doc['picurl'] = $status->picurl;
				break;
			case 'video':
				$doc['imgurl'] = $status->imgurl;
				$doc['videourl'] = $status->videourl;
				break;
		}
		
		return $this->solr->addDoc($doc, $this->solr_global);
    }

    //视频:添加或更新视频信息
    public function addOrUpdateVideoInfo($video_info) 
    {//DONE
        $doc = array();
		
		$doc['user_id'] = $video_info['uid'];
		$doc['discription'] = $video_info['discription'];
		$doc['title'] = $this->search_util->htmlChars($video_info['title']);
		$doc['totalCount'] = (int)0;
		$doc['id'] = $video_info['id'];
		if (isset($video_info['is_web']) && $video_info['is_web'] == 1)
		{
			$doc['person_web_type'] = 1;
			$doc['web_id'] = $video_info['web_id'];
			$doc['web_name'] = $doc['uname']; // LiuGC 2012/07/14
			$doc['unique_id'] = 'web_video_'.$video_info['id'];
		}else{
			$doc['person_web_type'] = 0;
			$doc['user_name'] = $doc['uname']; // LiuGC 2012/07/14
			$doc['unique_id'] = 'video_'.$video_info['id'];
		}
		$doc['video_pic'] = $video_info['cover_pic'];
		$doc['type'] = 6;
		$doc['createTime'] = (int)$video_info['time'];
		
		return $this->solr->addDoc($doc, $this->solr_global);
    }

    //博客:添加或更新博客信息
    public function addOrUpdateBlogArticleInfo($blog_info) 
    {//DONE
        $doc = array();		
		$doc['unique_id'] = 'blog_'.$blog_info['id'];
		$doc['title'] = $this->search_util->htmlChars($blog_info['title']);
		$doc['summary'] = $blog_info['resume'];
		$doc['user_id'] = $blog_info['uid'];
		$doc['user_name'] = $blog_info['uname'];
		$doc['id'] = $blog_info['id'];
		$doc['type'] = 7;
		$doc['totalCount'] = (int)0;
		$doc['user_dkcode'] = $blog_info['dkcode'];
		$doc['createTime'] = (int)$blog_info['time'];

		return $this->solr->addDoc($doc, $this->solr_global);
    }

    //问答:添加或更新问答信息
    public function addOrUpdateQuestionAndAnswerInfo($qa_info) 
    {//DONE
		$doc = array();
		$doc['unique_id'] = 'dkask_'.$qa_info['id'];
		$doc['user_id'] = $qa_info['uid'];
		$doc['user_name'] = $doc['uname'];
		$doc['id'] = $qa_info['id'];
		$doc['title'] = stripslashes($this->search_util->htmlChars($qa_info['title'], false));
		$doc['totalVotes'] = (int)$qa_info['votes'];
		$doc['ask_option_list'] = $this->search_util->getAskOptionalText($qa_info['option_list']);
		$doc['multiple'] = $qa_info['multiple'];
		$doc['type'] = 8 ;
		$doc['user_dkcode'] = $qa_info['user_dkcode'];
		$doc['createTime'] = (int)$qa_info['time'];

		return $this->solr->addDoc($doc, $this->solr_global);
    }

    //活动:添加、修改活动
    public function addOrUpdateEventInfo($event_info) 
    {//DONE
    	$doc = array();
		
		$doc['fdfs_filename'] = $event_info['filename'];
		$doc['fdfs_group'] = $event_info['groupname'];
		$doc['joinNum'] = (int)$event_info['join_num'];
		$doc['starttime'] = $event_info['starttime'];
		$doc['id'] = $event_info['id'];
		$doc['name'] = $event_info['title'];
		$doc['type'] = 9;
		$doc['address'] = $event_info['address']; // LiuGC 2012/07/14
		$doc['endtime'] = $event_info['endtime']; // LiuGC 2012/07/14
		if (isset($event_info['is_web']) && $event_info['is_web'] == 1)
		{
			$doc['person_web_type'] = 1;
			$doc['web_id'] = $event_info['uid'];
			$doc['web_name'] = $event_info['user_name'];
			$doc['unique_id'] = 'web_event_'.$event_info['id'];
		}else{
			$doc['person_web_type'] = 0;
			$doc['unique_id'] = 'event_'.$event_info['id'];
			$doc['user_name'] = $event_info['user_name'];
			$doc['user_id'] = $event_info['uid'];
		}

		return $this->solr->addDoc($doc, $this->solr_global);
    }

    //删除网页
    public function deleteWebpage($web_id) 
    {//DONE
		$global = 'web_id:'.$web_id.' AND (type:2 OR type:3 OR type:4 OR type:5 OR type:6 OR type:9)';
		$redis ='id:'.$web_id.' AND (category:follower_webpage OR category:webpage_follower)';;
		$this->solr->deleteByQuery( $global, $this->solr_global);
		$this->solr->deleteByQuery($redis, $this->solr_redis);
		return true;
    }

    //删除状态
    public function deleteStatus($status_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:Topic_'.$status_id, $this->solr_global);
    }

    public function deleteAStatusOfWeb($status_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:web_topic_'.$status_id, $this->solr_global);
    }

    //删除图片
    public function deletePhoto($photo_id) 
    {//DONE
    	$prefix = 'unique_id:user_photo_';
		
		if (!is_array($photo_id))
		{
			return $this->solr->deleteByQuery($prefix.$photo_id, $this->solr_global);
		}else{
			$query_struct = '';
			foreach ($photo_id as $key => $val)
			{
				if (trim($val) == null) continue;
				
				if ($query_struct == '')
				{
					$query_struct = $prefix.$val;
				}else{
					$query_struct.= ' OR '.$prefix.$val;
				}
			}
			
			return $this->solr->deleteByQuery($query_struct, $this->solr_global);
		}
    }

    //删除相册
    public function deleteAlbum($album_id) 
    {//DONE
        $query = 'unique_id:user_album_'.$album_id.' OR album_id:'.$album_id;
        return $this->solr->deleteByQuery($query, $this->solr_global);
    }

    //删除视频
    public function deleteVideo($video_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:video_'.$video_id, $this->solr_global);
    }

    public function deleteAVideoOfWeb($video_id) 
    {//DONE
        return  $this->solr->deleteByQuery('unique_id:web_video_'.$video_id, $this->solr_global);
    }

    //删除博客文章
    public function deleteBlog($blog_id) 
    {//DONE
        return  $this->solr->deleteByQuery('unique_id:blog_'.$blog_id, $this->solr_global);
    }

    //删除问答
    public function deleteAsk($ask_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:dkask_'.$ask_id, $this->solr_global);
    }

    //删除活动
    public function deleteEvent($event_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:event_'.$event_id, $this->solr_global);
    }

    public function deleteAEventOfWeb($event_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:web_event_'.$event_id, $this->solr_global);
    }

	private function renewFollowersCountByUser($unique_id, $is_incr = true, $count='fansCount', $delete=array())
	{
		$query = 'unique_id:'.$unique_id;
		
		$response = $this->solr->query($this->solr_global, $query, 0, 1);//json_decode($this->solr_global->search($query,0,1)->getRawResponse());
		
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