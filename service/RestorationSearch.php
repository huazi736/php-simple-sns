<?php
class RestorationSearchService extends DK_Service
{ 
	private $solr_global = null;
	
	public function __construct(){
		parent::__construct();
		$this->init_db("solr");
		$this->init_solr();
		$this->solr_global = $this->solr->getSolr('global');
	}
	
    public function test() {
    	$keyword = array('id'=>1233, 'type'=>1);
        $res = $this->restoreVideoInfo($keyword);
        echo '<pre>';
        print_r($res);
        echo '</pre>';
    }
	/**
	 * 用户粉丝数变更
	 * 
	 * Enter description here ...
	 * @param int $user_id
	 */
	public function restoreUserInfo($user_id)
	{//DONE
		$table = 'solr_user';
		$field = '`id`,`user_id`';
		$user_id = intval($user_id);
		$value = '\'\','.$user_id;
		$delete = 'DELETE FROM '.$table.' WHERE user_id='.$user_id;
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		@$this->db->query($delete);
		return  $this->db->query($sql);
	}
	
	/**
	 * 网页粉丝数变更
	 * 
	 * Enter description here ...
	 * @param int $web_id
	 */
	public function restoreWebpageInfo($web_id)
	{
		$table = 'solr_webpage';
		$field = '`id`,`web_id`';
		$web_id = intval($web_id);
		$value = '\'\','.$web_id;
		$delete = 'DELETE FROM '.$table.' WHERE web_id='.$web_id;
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		@$this->db->query($delete);
		return  $this->db->query($sql);
	}
	
	/**
	 * 状态修改
	 * 
	 * Enter description here ...
	 * @param array $status_info
	 */
	public function restoreStatusInfo(array $status_info)
	{//DONE
		$status_id = intval($status_info['id']);
		$status_type = intval($status_info['type']);
		$table = 'solr_status';
		$field = '`id`,`topic_id`,`type`';
		$value = '\'\','.$status_id.','.$status_type;
		$delete = 'DELETE FROM '.$table.' WHERE topic_id='.$status_id.' AND type='.$status_type;
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		@$this->db->query($delete);
		return  $this->db->query($sql);	
	}
	
	/**
	 * 图片修改
	 * 
	 * Enter description here ...
	 * @param array $photo_info
	 */
	public function restorePhotoInfo(array $photo_info)
	{//DONE
		foreach ($photo_info as $val)
		{
			if (is_array($val))
			{
				$this->photoChanged($val);
			}else 
				$this->photoChanged($photo_info);
		}
		return true;
	}
	
	//图片
	private function photoChanged(array $photo_info)
	{
		$photo_id = intval($photo_info['id']);
		$photo_type = intval($photo_info['type']);
		$table = 'solr_photo';
		$field = '`id`,`photo_id`,`type`';
		$value = '\'\','.$photo_id.','.$photo_type;
		$delete = 'DELETE FROM '.$table.' WHERE photo_id='.$photo_id.' AND type='.$photo_type;
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		@$this->db->query($delete);
		return $this->db->query($sql);	
	}
	
	/**
	 * 转移图片
	 * Enter description here ...
	 * @param unknown_type $album_info
	 */
	public function restorePhotoInfoTransfered(array $album_info)
	{
		$prefix = 'unique_id:user_photo_';
		
		$photo_id = $album_info['photo_id'];
		
		foreach ($album_info['album'] as $val)
		{
			$this->restoreAlbumInfo($val);
		}    	
		
		if (!is_array($photo_id))
		{
			if (trim($photo_id) != '')$this->solr_global->deleteByQuery($prefix.$photo_id);
			return true;
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
			
			if ($query_struct != null) $this->solr_global->deleteByQuery($query_struct);
			return true;
		}
		return false;
	}
	
	/**
	 * 相册修改
	 * 
	 * Enter description here ...
	 * @param array $album_info
	 */
	public function restoreAlbumInfo(array $album_info)
	{//DONE
		$album_id = intval($album_info['id']);
		$album_permission = intval($album_info['visible']);
		$album_type = intval($album_info['type']);
		$table = 'solr_album';
		$field = '`id`,`album_id`,`type`,`permission`';
		$delete = 'DELETE FROM '.$table.' WHERE album_id='.$album_id.' AND type='.$album_type.' AND permission=0';
		$value ='\'\','.$album_id.','.$album_type.','.$album_permission;
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		@$this->db->query($delete);
		return $this->db->query($sql);	
	}
	
	/**
	 * 视频修改
	 * 
	 * Enter description here ...
	 * @param array $video_info
	 */
	public function restoreVideoInfo(array $video_info)
	{//DONE
		$video_id = intval($video_info['id']);
		$video_type = intval($video_info['type']);
		$table = 'solr_video';
		$field = '`id`,`video_id`,`type`';
		$value = '\'\','.$video_id.','.$video_type;
		$delete = 'DELETE FROM '.$table.' WHERE video_id='.$video_id.' AND type='.$video_type;
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		@$this->db->query($delete);
		return $this->db->query($sql);	
	}
	
	/**
	 * 博客修改
	 * 
	 * Enter description here ...
	 * @param int $blog_id
	 */
	public function restoreBlogInfo($blog_id)
	{//DONE
		$table = 'solr_blog';
		$field = '`id`,`blog_id`';
		$value = '\'\','.intval($blog_id);
		$delete = 'DELETE FROM '.$table.' WHERE blog_id='.$blog_id;
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		@$this->db->query($delete);
		return $this->db->query($sql);	
	}
	
	/**
	 * 问答修改
	 * 
	 * Enter description here ...
	 * @param int $qa_id
	 */
	public function restoreAskInfo($qa_id)
	{//DONE
		$table = 'solr_ask';
		$field = '`id`,`ask_id`';
		$value = '\'\','.intval($qa_id);
		$delete = 'DELETE FROM '.$table.' WHERE ask_id='.$qa_id;
		$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
		@$this->db->query($delete);
		return $this->db->query($sql);	
	}
	
	/**
	 * 活动修改
	 * 
	 * Enter description here ...
	 * @param array $event_info
	 */
	public function restoreEventInfo(array $event_info)
	{
		if (count($event_info) == 2)
		{//DONE
			$table = 'solr_event';
			$event_id = intval($event_info['id']);
			$event_type = intval($event_info['type']);
			$field = '`id`,`event_id`,`type`';
			$value = '\'\','.$event_id.','.$event_type;
			$delete = 'DELETE FROM '.$table.' WHERE event_id='.$event_id.' AND type='.$event_type;
			$sql = 'INSERT INTO '.$table.'('.$field.') VALUES('.$value.')';
			@$this->db->query($delete);
			return $this->db->query($sql);	
		}else{
			$doc = array();
			
			$doc['fdfs_filename'] = $event_info['filename'];
			$doc['fdfs_group'] = $event_info['groupname'];
			$doc['joinNum'] = (int)$event_info['join_num'];
			$doc['starttime'] = $event_info['starttime'];
			$doc['id'] = $event_info['id'];
			$doc['name'] = $event_info['title'];
			$doc['type'] = 9;
			if (isset($event_info['is_web']) && $event_info['is_web'] == 1)
			{
				$doc['person_web_type'] = 1;
				$doc['web_id'] = $event_info['uid'];
				$doc['unique_id'] = 'web_event_'.$event_info['id'];
			}else{
				$doc['person_web_type'] = 0;
				$doc['unique_id'] = 'event_'.$event_info['id'];
				$doc['user_id'] = $event_info['uid'];
			}
			return $this->solr->addDoc($doc, $this->solr_global);
		}
	}
}
?>
