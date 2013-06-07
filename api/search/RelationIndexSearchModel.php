<?php

class RelationIndexSearchModel extends DkModel {
    
    private $search_util = null;
 
    private $solr_redis = null;
    
    private static $self = null;
    
    private $web_types = array("web"=>2, "status"=>3, "photo"=>4, "album"=>5, "video"=>6, "event"=>9);
    
    private $engine = array();

    public function __initialize() 
    {
        $this->search_util = load_class('SearchUtil', 'libraries', 'DK_');
        
        $this->init_solr();
        
        self::$self == null && self::$self = &$this;
        
        $this->init_db('user');
        
        $this->solr_redis = $this->solr->getSolr('relation');
 
        $this->engine["user"] = $this ->solr->getSolr("user");
        
        $this ->engine["web"] = $this ->solr->getSolr("web");
        
         $this->engine["status"] = $this ->solr->getSolr("status");
        
        $this ->engine["photo"] = $this ->solr->getSolr("photo");
        
         $this->engine["album"] = $this ->solr->getSolr("album");
        
        $this ->engine["video"] = $this ->solr->getSolr("video");
        
         $this->engine["blog"] = $this ->solr->getSolr("blog");
        
        $this ->engine["event"] = $this ->solr->getSolr("event");
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
        $doc['unique_id'] = 'user_info_' . $user_info['uid'];
        $doc['userinfo_user_name'] = $user_info['uname'];
        $doc['fullspell'] = $this->solr->chinese2Pinyin($user_info['uname']);
        $doc['user_id'] = $user_info['uid'];
        $doc['user_dkcode'] = $user_info['dkcode'];
        $doc['followersNum'] = (int) $user_info['follower_num'];
        $doc['type'] = 1;
        $doc['company'] = $user_info['company'];
        $doc['home_addr'] = $user_info['home_addr'];
        $doc['now_addr'] = $user_info['now_addr'];
        $doc['school_name'] = $user_info['school_name'];
        $doc['registerTime'] = (int) $user_info['regdate'];
	$doc['base_access'] = $user_info['people_level'];
	$doc['school_access'] = $user_info['school_level'];
	$doc['company_access'] = $user_info['company_level']; 
        
        return $this->solr->addDoc($doc, $this->engine["user"]);
    }

    //人名修改
    public function onlyUpdatePeopleName($user_info) 
    {//DONE
        $record = array();
        $record['uid'] = $user_info['uid'];
        $record['username'] = $user_info['uname'];
        if ($this->addOrUpdateBasalInfoOfPeople($user_info)) 
        {
            $table = 'user_record_update';
            $user_id = $record['uid'];
            $user_name = $record['username'];
            $state = 1;
            $is_del_replicat = false;
            $sql = sprintf("INSERT INTO %s VALUES('','%d','%s','%d')", $table, $user_id, $user_name, $state);
            if ($is_del_replicat) 
            {
                $del = sprintf("DELETE FROM %s WHERE uid='%d'", $table, $user_id);
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
        $doc['unique_id'] = 'apps_info_' . $web_info['web_id'];
        $doc['fansCount'] = (int) $web_info['fans_count'];
        $doc['name'] = $web_info['name'];
        $doc['fullspell'] = $this->solr->chinese2Pinyin($web_info['name']);
        $doc['type'] = 2;
        $doc["imname"] = $web_info["imname"];
        $doc["iname"] = $web_info["iname"];
        $doc["ename"] = $web_info["ename"];
        $doc['user_id'] = $web_info['uid'];
        $doc['web_id'] = $web_info['web_id'];
        $doc['createTime'] = (int) strtotime($web_info['create_date']);

        return $this->solr->addDoc($doc, $this->engine["web"]);
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
        $doc['hot'] = (int) $status->hot;
        $doc['content'] = $this->search_util->htmlChars(strip_tags($status->content), false);
        $doc['content_show'] = $status->content;
        if ($status->from == 3) {//来至网页
            $doc['person_web_type'] = 1;
            $doc['web_id'] = $status->pid;
            $doc['web_name'] = $status->uname;
            $doc['createTime'] = (int) strtotime($status->dateline);
            $doc['unique_id'] = 'web_topic_' . $status->tid;
        } else if ($status->from == 1) {//来至个人
            $doc['person_web_type'] = 0;
            $doc['user_name'] = $status->uname;
            $doc['createTime'] = (int) $status->dateline;
            $doc['unique_id'] = 'Topic_' . $status->tid;
        }
        $doc["id"] = $status->tid;
        //新增字段 LiuGC 2012/07/14 
        switch ($status->type) {
            case 'photo':
                //json转换为字符串 LiuGC 2012/07/16
                $picurl = $status->picurl;
                $doc['picurl'] = (is_array($picurl) || is_object($picurl)) ? json_encode($picurl) : $picurl;
                break;
            case 'video':
                $doc['imgurl'] = $status->imgurl;
                $doc['videoid'] = $status->fid;
                break;
        }

        return $this->solr->addDoc($doc, $this->engine["status"]);
    }

    //视频:添加或更新视频信息
    public function addOrUpdateVideoInfo($video_info) 
    {//DONE
        $doc = array();

        $doc['user_id'] = $video_info['uid'];
        $doc['discription'] = $video_info['discription'];
        $doc['title'] = $this->search_util->htmlChars($video_info['title']);
        $doc['totalCount'] = (int) 0;
        $doc['id'] = $video_info['id'];
        if (isset($video_info['is_web']) && $video_info['is_web'] == 1) {
            $doc['person_web_type'] = 1;
            $doc['web_id'] = $video_info['web_id'];
            $doc['web_name'] = $video_info['uname']; // LiuGC 2012/07/14
            $doc['unique_id'] = 'web_video_' . $video_info['id'];
        } else {
            $doc['person_web_type'] = 0;
            $doc['user_name'] = $video_info['uname']; // LiuGC 2012/07/14
            $doc['unique_id'] = 'video_' . $video_info['id'];
        }
        $doc['video_pic'] = $video_info['cover_pic'];
        $doc['type'] = 6;
        $doc['createTime'] = (int) $video_info['time'];

        return $this->solr->addDoc($doc, $this->engine["video"]);
    }

    //博客:添加或更新博客信息
    public function addOrUpdateBlogArticleInfo($blog_info) 
    {//DONE
        $doc = array();
        $doc['unique_id'] = 'blog_' . $blog_info['id'];
        $doc['title'] = $this->search_util->htmlChars($blog_info['title']);
        $doc['summary'] = $blog_info['resume'];
        $doc['user_id'] = $blog_info['uid'];
        $doc['user_name'] = $blog_info['uname'];
        $doc['id'] = $blog_info['id'];
        $doc['type'] = 7;
        $doc['totalCount'] = (int) 0;
        $doc['user_dkcode'] = $blog_info['dkcode'];
        $doc['createTime'] = (int) $blog_info['time'];

        return $this->solr->addDoc($doc, $this->engine["blog"]);
    }

    //活动:添加、修改活动
    public function addOrUpdateEventInfo($event_info) 
    {//DONE
        $doc = array();
        $doc['fdfs_filename'] = $event_info['filename'];
        $doc['fdfs_group'] = $event_info['groupname'];
        $doc['joinNum'] = (int) $event_info['join_num'];
        $doc['starttime'] =  $event_info['starttime'] ;
        $doc['id'] = $event_info['id'];
        $doc['name'] = $this->search_util->htmlChars($event_info['title']);
        $doc['type'] = 9;
        $doc["detail"] = $event_info["detail"];
        $doc['address'] = $event_info['address']; // LiuGC 2012/07/14
        $doc['endtime'] = $event_info['endtime'];// LiuGC 2012/07/14
        $doc['user_id'] = $event_info['uid'];
        $doc['createTime'] = isset($event_info["time"]) ? $event_info["time"] : 0;
        if (isset($event_info['is_web']) && $event_info['is_web'] == 1) {
            $doc['person_web_type'] = 1;
            $doc['web_id'] = $event_info['web_id'];
            $doc['web_name'] = $event_info['uname'];
            $doc['unique_id'] = 'web_event_' . $event_info['id'];
        } else {
            $doc['person_web_type'] = 0;
            $doc['unique_id'] = 'event_' . $event_info['id'];
            $doc['user_name'] = $event_info['uname'];
        }
 
        return $this->solr->addDoc($doc, $this->engine["event"]);
    }

    //删除网页
    public function deleteWebpage($web_id)
    {//DONE
        
        $redis = 'id:' . $web_id . ' AND (category:follower_webpage OR category:webpage_follower)';
        
        foreach($this ->web_types as $type => $num)
        {
            $this ->solr->deleteByQuery("web_id:".$web_id." AND type:".$num, $this ->engine[$type]);
        }
        
        $this->solr->deleteByQuery($redis, $this->solr_redis);
        
        return true;
    }

    //删除状态
    public function deleteStatus($status_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:Topic_' . $status_id, $this->engine["status"]);
    }

    public function deleteAStatusOfWeb($status_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:web_topic_' . $status_id, $this->engine["status"]);
    }

    //删除图片
    public function deletePhoto($photo_id) 
    {//DONE
        $prefix = 'unique_id:user_photo_';
        
        if (!is_array($photo_id)) {
            return $this->solr->deleteByQuery($prefix . $photo_id, $this->engine["photo"]);
        } else {
            $query_struct = '';
            
            foreach ($photo_id as $key => $val) 
            {
                if (trim($val) == null) continue;

                if ($query_struct == '') $query_struct = $prefix . $val;
                
                else    $query_struct.= ' OR ' . $prefix . $val;
            }

            return $this->solr->deleteByQuery($query_struct, $this->engine["photo"]);
        }
    }

    //删除相册
    public function deleteAlbum($album_id) 
    {//DONE
        $album_query = 'unique_id:user_album_' . $album_id ;
        $photo_query = 'album_id:' . $album_id;
        try{
            $this ->solr->deleteByQuery($album_query, $this ->engine["album"]);
            
            $this->solr->deleteByQuery($photo_query, $this->engine["photo"]);
        }catch(Exception $e){}
        
        return true;
    }

    //删除视频
    public function deleteVideo($video_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:video_' . $video_id, $this->engine["video"]);
    }

    public function deleteAVideoOfWeb($video_id)
    {//DONE
        return $this->solr->deleteByQuery('unique_id:web_video_' . $video_id, $this->engine["video"]);
    }

    //删除博客文章
    public function deleteBlog($blog_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:blog_' . $blog_id, $this->engine["blog"]);
    }

    //删除活动
    public function deleteEvent($event_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:event_' . $event_id, $this->engine["event"]);
    }

    public function deleteAEventOfWeb($event_id) 
    {//DONE
        return $this->solr->deleteByQuery('unique_id:web_event_' . $event_id, $this->engine["event"]);
    }

    private function renewFollowersCountByUser($unique_id, $is_incr = true, $count='fansCount', $delete=array()) 
    {
        $query = 'unique_id:' . $unique_id;

        $response = $this->solr->query($this->engine["user"], $query, 0, 1); 

        if ($response->response->numFound > 0) {
            
            foreach ($response->response->docs as $val) {
                
                if (isset($val->$count)) {
                    
                    $is_incr ? $val->$count += 1 : $val->$count -= 1;
                    
                    if ($val->$count < 0)    $val->$count = 0;
                }else {
                    $val->$count = $is_incr ? 1 : 0;
                }
                
                if (isset($val->userinfo_user_name)) $val->fullspell = $this ->solr->chinese2Pinyin($val->userinfo_user_name);
            }
            $doc = new Apache_Solr_Document();
            
            foreach ($val as $key => $v) {
                
                if (in_array($key, $delete))    continue;
                
                $doc->$key = $v;
            }

            return $this->solr->execute($doc, $this->engine["user"]);
        }
        return false;
    }
    
    public static function newInstance(){
        return self::$self == null ?  new self : self::$self;
    }
}