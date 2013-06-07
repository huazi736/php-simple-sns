<?php
class BlogModel extends MY_Model
{
	public $blogTable		= 'blog';
	public $draftTable		= 'blog_draft';
	public $contentTable	= 'blog_content';
	public $photoTable		= 'blog_photo';
	public $forwardTable	= 'blog_forward';
	public $cacheTime		= 600;
	
	public function __construct()
	{
		parent::__construct();
		$this->fdfs = get_storage('default');
		// $this->draftTable = 'blog_draft';
	}
	
	//博客权限：默认为1,公开;-1,自定义;3,粉丝;4,好友;8,仅自己可见;
	//关系接口 ：  1 好友, 2 相互关注, 3 关注对象, 4 粉丝, 5 已发请求的准好友, 0 无关系, -1 错误
	/**
	 * 获得博客列表信息
	 * @param $blog_uid 博主的uid
	 * @param $my_uid 当前访问者的uid
	 * @param $s 起始条数
	 * @param $limit 每页限制条数
	 * @param $power 权限（访问者与博主是否同一人）
	 * @param $relation 访问者与博主的关系（关系接口 ：  1 好友, 2 相互关注, 3 关注对象, 4 粉丝, 5 已发请求的准好友, 0 无关系, -1 错误）
	 */
	public function getBlogList($blog_uid, $my_uid, $s, $limit, $power = false,$relation = 0, $typeid= 0)
	{
		if(!$power)
		{
			$sql = "select a.*,b.ouid_info from {$this->blogTable} a 
				left join {$this->forwardTable} b 
					on (a.id=b.bid and b.status='1')
				where a.uid='{$blog_uid}' 
				and (
					CASE WHEN privacy='-1' and privacy_content like '%{$my_uid}%' then 1
					WHEN privacy='4' and {$relation}=10 then 1
					WHEN privacy='1' THEN 1
					ELSE 0 END 
				) = '1'";
		}
		else
		{
			if(!empty($typeid))
			{	
					$sql = "select * from {$this->blogTable} as a where uid='{$blog_uid}' and a.privacy = '".(int)$typeid."'";
			}else{
				$sql = "select * from {$this->blogTable} as a where a.uid='{$blog_uid}' and a.privacy = '1'";
			}
		}
		
	
		$sql .= " and  a.status='1' ";
		$nums = $this->db->query($sql)->num_rows();
		$sql .= " order by `dateline` desc limit {$s},{$limit} ";
		$res = $this->db->query($sql)->result_array();
		if($res)
		{
			$return = array('nums'=>$nums,'result'=>$res);
			return $return;
		}
		else
		{
			$return = array('nums'=>$nums,'result'=>$res);
			return $return;
		}
	}
	
	/**
	 * 博客正文
	 * 
	 * @param $bid  		博客ID 
	 * @param $blog_uid		博主uid
	 * @param $my_uid		访问者的UID
	 * 
	 * @return array
	 */
	public function getBlog($bid = null, $blog_uid = null, $my_uid = null, $relation = 0)
	{
		if(!$bid or !$blog_uid or !$my_uid)
			return false;
		$sql = "select a.*, b.content,c.ouid,c.ouid_info from {$this->blogTable} a 
			left join {$this->contentTable} b on (a.id=b.oid and b.type='1')
			left join {$this->forwardTable} c on (a.id=c.bid and a.uid='{$blog_uid}')
			where a.id='{$bid}' and a.uid='{$blog_uid}' and a.status='1' ";
		if($blog_uid!=$my_uid) {
			$sql .= " and (
				CASE WHEN privacy='-1' and privacy_content like '%{$my_uid}%' then 1
				WHEN privacy='4' and {$relation}=10 then 1
				WHEN privacy=1 then 1
				ELSE 0 END 
			) ='1' ";
		}
		$res = $this->db->query($sql)->result_array();
		if($res)
		{
			return $res;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 新建草稿
	 * @param	$uid		用户id
	 * @return	string
	 */
	public function newDraft($uid = null)
	{
		if(!$uid)
			return false;
		$time = time();
		$sql = 'BEGIN';
		$this->db->query($sql);
		$sql = sprintf("INSERT INTO %s ( `uid`, `title`, `resume`, `dateline`, `lastupdate`, `privacy_content`) 
			VALUES ('%s',null,null,'%d','%d','1')",($this->draftTable), $uid, $time, $time);
		$res =  $this->db->query($sql);
		if($res)
		{
			$id = $this->db->insert_id();
			if(!empty($id))
			{
				$newContent = "insert into {$this->contentTable} (`id`,`oid`) values (NULL,'{$id}')";
				$newres = $this->db->query($newContent);
				if($newres){
					$this->db->query('COMMIT');
				}else{
					$this->db->query('ROLLBACK');
					return false;
				}
				return $id;
			}
			else
			{
				return false;
			}
		}
		return false;
	}
	
	/**
	 * 获取草稿的内容
	 * @param $draft_id		博客草稿的id
	 * @param $up			true:取数据库数据;false:取缓存数据;
	 * @return array()		博客草稿内容
	 */
	public function getDraft($draft_id = null, $uid = null)
	{
		if(!$draft_id or !$uid)
			return false;
		$sql = "select a.*,b.content 
			from {$this->draftTable} a
				left join {$this->contentTable} b 
				on (b.oid=a.id and b.oid='{$draft_id}')
			where a.id='{$draft_id}' and a.uid='{$uid}' and a.status='1' ";
		$res = $this->db->query($sql)->result_array();
		if($res)
		{
			return $res;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 保存预览数据
	 */
	public function save_preview($bid = null, $data = null)
	{
		if(!$bid or !$data){
			return false;
		}
		$res = set_session('blog_blogModel_preview'.$bid,$data,$this->cacheTime);
		if($res){
			return $res;
		}
		return false;
	}
	
	/**
	 * 获取预览数据
	 */
	public function get_preview($bid = null)
	{
		if(!$bid){
			return false;
		}
		$res = get_session('blog_blogModel_preview'.$bid);
		if($res){
			return $res;
		}
		return false;
	}
	
	/**
	 * 获取草稿的内容
	 * @param	$uid		用户id
	 * @param	$limit		页面显示条数
	 * @param	$s			页面开始条数
	 * @return array()		博客草稿内容
	 */
	public function getDraftList($uid = null, $limit = 10,$s = 0)
	{
		if(empty($uid)) {
			return false;
		}
		$sql = "select * from {$this->draftTable}  where uid='{$uid}' and status='1' ";
		$sql .= ' and `title` is not null ';
		$nums = $this->db->query($sql)->num_rows();
		$sql .= " order by `dateline` desc limit {$s},{$limit} ";
		$res = $this->db->query($sql)->result_array();
		if($res)
		{
			$return = array('nums'=>$nums,'result'=>$res);
			return $return;
		}
		else
		{
			$return = array('nums'=>$nums,'result'=>$res);
			return $return;
		}
	}
	
	/**
	 * 获取简要的草稿列表;
	 */
	public function getEasyDraft($uid = null)
	{
		if(!$uid)
			return false;
		
		$data = array('uid'=>$uid,'status'=>'1');
		$this->db->select('id,title,dateline');
		$this->db->where($data);
		$this->db->where('title IS NOT NULL',null);
		$this->db->order_by('lastupdate','DESC');
		$query= $this->db->get($this->draftTable);
		$res = $query->result_array();
		if($res){
			return $res;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取草稿图片
	 * @param	$id	 		草稿id / 博客id
	 * @param	$type  		获取类型,'draft'是草稿id,'blog'是博客id
	 * @param	$up			true:取数据库数据;false:取缓存数据;
	 * @return	array()
	 */
	public function getPicture($id = null, $type = 'draft')
	{
		if($type!='blog' && $type!='draft') {
			return false;
		}
		$where = array();
		$oid = ( $type == 'blog' ) ? 'bid' : 'did';
		$where[$oid] = $id;
		$where['status'] = '1';
		$res = $this->db->where($where)->get($this->photoTable)->result_array();
		return $res;
	}
	
	/**
	 * 更新草稿图片至博客中
	 */
	public function draftPhotoToBlog($did = null, $bid = null)
	{
		if(!$did or !$bid)
			return false;
		
		$data = array('bid'=>$bid);
		$this->db->where('did',$did);
		$res = $this->db->update($this->photoTable, $data);
		if($res) {
			return $res;
		}
		return false;
	}
	
	/**
	 * 博客草稿编辑
	 * @param $draft_id  博客草稿的id
	 * @param $title	 博客标题
	 * @param $content	 博客内容
	 * @param $privacy	 博客可见权限
	 * @return bool		 执行结果
	 */
	public function saveDraft($draft_id = null, $uid = null, $title = null, $resume = null, $content = null, $privacy = '1', $privacy_content = '1')
	{
		
		if(!$draft_id or !strlen($title) or !strlen($content) or !$uid)
			return false;
		$time = time();
		$sqldata = array(
				'title'=>$title,
				'resume'=>$resume,
				'privacy'=>$privacy,
				'privacy_content'=>$privacy_content,
				'lastupdate'=>$time
			);
		$sql = 'BEGIN';
		$this->db->query($sql);
		$this->db->where('id',$draft_id);
		$res = $this->db->update($this->draftTable,$sqldata);
		
		if($res)
		{
			$this->db->where(array('oid'=>$draft_id,'type'=>'0'));
			$resl = $this->db->update($this->contentTable,array('content'=>$content));
			
			if($resl){
				$this->db->query('COMMIT');
			}else{
				$this->db->query('ROLLBACK');
				return false;
			}
			return $res;
		}
		return false;
	}
	
	/**
	 * 删除草稿
	 * @param $draft_id 	博客草稿id
	 * @param $del 			修改标志,0为删除,1为正常,2为已发表
	 * @return bool
	 */
	public function delDraft($draft_id = null, $uid = null, $del = '0')
	{
		if(!$draft_id or !$uid)
			return false;
		$time = time();
		$sqldata = array('status'=>$del,'lastupdate'=>$time);
		$res = $this->db->update($this->draftTable,$sqldata,array('id'=>$draft_id,'uid'=>$uid));

		if($res){
			delete_session('blog_blogModel_blogDraftText_'.$draft_id);
		}
		return $res;
	}
	
	/**
	 * 博客的标题和内容检查
	 * @param $title	博客标题
	 * @param $content	博客内容
	 * @return array('result'=>bool,'info'=>infomation)
	 */
	public function checkBlog($title = null,$content = null)
	{
		$return = array('result'=>true,'info'=>'');
		$title = trim($title);
		$titleLen = strlen($title);

		$length = strlen($content);
		$content = trim($content);
		$content = str_replace('&nbsp;','',$content);
		$content = str_replace('<br>','',$content);

		if($titleLen <= 0 || $title == '')
		{
			$return['result'] = false;
			$return['info'] = '标题不能为空';
		}
		if($length <= 0 || $content == '')
		{
			$return['result'] = false;
			$return['info'] = '内容不能为空';
		}
		
		if($length >= 65535){
		    $return['result'] = false;
			$return['info'] = '内容过长';
		}
		return $return;
	}
	
	/**
	 * 图片保存入库
	 * @param $bid 			博客ID
	 * @param $did 			草稿ID
	 * @param $title 		图片标题
	 * @param $layout 		图片样式
	 * @param $come 		图片来源(1为上传；2为相册导入)
	 * @return bool
	 */
	public function savePhoto($bid = 0, $did = 0, $title = '001', $name = '', $ext = null, $size = 0, $come_type = '1', $pid = 0)
	{
		if($bid=='0' && $did=='0')
		{
			return false;
		}
		$time = time();
		$data = array(
				'bid' => $bid,
				'did' => $did,
				'title' => $title,
				'name' => $name,
				'ext' => $ext,
				'size' => $size,
				'dateline' => $time,
				'come_type' => $come_type,
				'pid' => $pid
			);
		$res = $this->db->insert($this->photoTable,$data);
		if($res)
		{
			$id = $this->db->insert_id();
			if($id){
				return $id;
			}
			return false;
		}
		return false;
	}
	
	/**
	 * 删除图片
	 * @param	$photo_id 		图片id
	 * @return bool 		
	 */
	public function upPhoto($photo_id = null)
	{
		if(!$photo_id)
			return false;
		$time = time();
		$sqldata = array(
				'status'=>'0',
				'lastupdate'=>$time
			);
		$this->db->where('id',$photo_id);
		$res = $this->db->update($this->photoTable,$sqldata);
		if($res)
		{
			return $res;
		}
		return false;
	}
	
	/**
	 * 获取博客的图片数量
	 * 
	 * @param $bid 博客或者是草稿的id
	 */
	public function getBolgPhotoNums($bid = null, $type='blog')
	{
		$nums = 0;
		if(!$bid)
			return $nums;
		if($type=='blog'){
			$wheresql = array('bid'=>$bid);
		}elseif($type=='draft'){
			$wheresql = array('did'=>$bid);
		}else{
			return $nums;
		}
		$nums = $this->db->where($wheresql)->count_all_results($this->photoTable);
		return $nums;
	}
	
	/**
	 * 通过id获取相册里图片的信息;
	 * @param $pid  	图片id
	 * @return array
	 */
	public function getPhotoInfo($pid = null,$uid = null)
	{
		return service('Album')->getPhotoInfo($pid,$type='album',$uid);
	}
	
	/**
	 * 检查相册里的图片是否已经存在于博客中
	 * @param $pid	图片id
	 * @param $bid	博客id
	 * @return bool
	 */
	public function checkPhoto($pid = null, $bid = null, $type = null)
	{
		$wheresql = array(
				'pid'=>$pid,
				'status'=>'1'
			);
		if(empty($pid) or empty($bid))
			return 0;
		if($type=='blog') {
			$wheresql['bid'] = $bid;
		} elseif($type=='draft') {
			$wheresql['did'] = $bid;
		} else {
			return false;
		}
		$nums = $this->db->where($wheresql)->count_all_results($this->photoTable);
		return $nums;
	}
	
	/**
	 * 发表博客
	 * @param $id 		博客id
	 * @return bool
	 */
	public function saveBlog($sqldata = null, $is_forward = false)
	{
		if(empty($sqldata))
			return false;
		$this->db->query('BEGIN');
		$blog = array();
		$blog['uid'] = $sqldata['uid'];
		$blog['title'] = $sqldata['title'];
		$blog['resume'] = $sqldata['resume'];
		$blog['dateline'] = $sqldata['dateline'];
		$blog['privacy'] = $sqldata['privacy'];

		$blog['privacy_content'] = $sqldata['privacy_content'];
		if($is_forward) {
			$blog['share'] = '1';
		}
		$content = $sqldata['content'];
		$res = $this->db->insert($this->blogTable,$blog);
		
		//增加积分 yinyancia
		service('credit')->blog();
		
		if($res) {
			$id = $this->db->insert_id();
			if(!empty($id))
			{
				$newContent = sprintf("insert into `%s` (`oid`,`content`,`type`) 
						values ('%s','%s','1')",$this->contentTable,$id,addslashes($content));
				$newres = $this->db->query($newContent);
	
				if($newres){
					$this->db->query('COMMIT');
				}else{
					$this->db->query('ROLLBACK');
					return false;
				}
				return $id;
			}
			else
			{
				return false;
			}
		}
		return false;
	}
	
	/** 
	 * 博客更新
	 * @param $id		博客id
	 * @param $uid		博主id
	 * @param $title	博客标题
	 * @param $content	博客内容
	 * @param $resume	博客内容摘要
	 * @param $privacy	博客权限
	 * @return bool
	 */
	public function editblog($id = null, $uid = null, $title = null, $content = null, $resume = null,$privacy = null,$privacy_content = '-1')
	{
		// 暂时规定不能修改标题
		$lenContent = strlen($content);
		if(empty($id) or empty($uid) or $lenContent <0 )
			return false;
		$time = time();
		$sqldata = array(
					'resume'=>$resume,
					'lastupdate'=>$time,
					'privacy'=> $privacy,
					'privacy_content'=> $privacy_content,
			);
		$this->db->where(array('id'=>$id,'uid'=>$uid));
		$res = $this->db->update($this->blogTable,$sqldata);
		if($res)
		{
			// 更新博客内容表
			$data = array('content'=>$content);
			$this->db->update($this->contentTable,$data,array('oid'=>$id,'type'=>'1'));
			return true;
		}
		return false;
	}
	
	/**
	 * 编辑
	 * @param	$table		类型(博客,博客图片,博客草稿)
	 * @param	$where		编辑条件
	 * @param	$update		编辑数据
	 */
	public function edit($table = null,$where = null,$update = null)
	{
		if(!$table or !$where or !$update)
			return false;
		$tables = array(
				'blog'=>$this->blogTable,
				'photo'=>$this->photoTable,
				'draft'=>$this->draftTable
			);
		$this->db->where($where);
		$res = $this->db->update($tables[$table],$update);
		if($res)
		{
			return $res;
		}
		return false;
	}
	/**
	 * 删除博客
	 *
	 * @param $bid		博客id
	 * 
	 * @return bool
	 */
	public function delBlog($bid = null, $uid = null)
	{
		if(!$uid or !$bid) {
			return false;
		}
		$data = array('status'=>'0','lastupdate'=>time());
		$res = $this->db->update($this->blogTable,$data,array('id'=>$bid,'uid'=>$uid));
		if($res)
		{
			//扣除积分
			service('credit')->blog(false);
			
			// 删除缓存数据
			delete_session('blog_blogModel_blogText_'.$bid);
			return $res;
		}
		return false;
	}
	
	/**
	 * 添加转载记录
	 *
	 * @param	$id		转载后表id
	 * @param	$uid	转载者的id
	 * @param	$fid	转载的博客的id
	 * @param	$oid	转载的原始博客的id
	 * @param	$fuid	转载的博客的作者的id
	 * @param	$ouid	转载的博客的原始作者的id
	 * @param	$time	转载时间
	 */
	public function addForward($bid = null, $uid = null,$fid = null,$oid = null,$fuid = null,$ouid = null,$time = null,$ouid_info = '')
	{
		if(!$bid or !$uid or !$fid or !$oid or !$fuid or !$ouid or !$time)
			return false;
		$data = array(
				'bid'=>$bid,
				'uid'=>$uid,
				'fid'=>$fid,
				'fuid'=>$fuid,
				'oid'=>$oid,
				'ouid'=>$ouid,
				'dateline'=>$time,
				'ouid_info'=>$ouid_info
			);
		$res = $this->db->insert($this->forwardTable,$data);
		if($res) {
			return $res;
		}
		return false;
	}
	
	/**
	 * 获取转载信息
	 * @param	$id		
	 * @param	$type	id的类型,'f'表示是fid,为空表示id
	 */
	public function getForward($fid = null, $fuid = null)
	{
		if(!empty($fid) && !empty($fuid))
		{
			$this->db->where(array('bid'=>$fid,'uid'=>$fuid,'status'=>'1'));
			$res = $this->db->get($this->forwardTable)->result_array();
			if($res) {
				return $res;
			}
			return false;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 获取列表中的数据量,用作分页处理
	 * @param $table		需要查询的表
	 * @param $uid			用户
	 * @param $factor_arr	参数
	 * @ $uid				用户
	 */
	public function getNums($table = null, $uid = null,$factor_arr = array())
	{
		if(!$table or !$uid)
			return 0;
		$tables = array(
				'blog'=>$this->blogTable,
				'photo'=>$this->photoTable,
				'draft'=>$this->draftTable
			);
		$tb = $tables[$table];
		if(empty($tb))
			return 0;
		$factor_arr['uid'] = $uid;
		$this->db->where($factor_arr);
		if($table=='draft')
			$this->db->where('title IS NOT NULL',null);
		$res = $this->db->count_all_results($tb);
		return $res;
	}
	
	/**
	 * 获取相册
	 */
	public function getAlbum($uid = null)
	{
		if(!$uid) 
			return false;
		else 
			return service('Album')->getAlbumList($uid);
	}
	
	/**
	 * 获取相册中图片列表
	 */
	public function get_photo_lists($aid = null,$uid = null)
	{
		
		return service('album')->getPhotoList($aid,$uid);
	}
	
	public function strToImg($content = '', $imgs = null, $img_type = '_s', $location = false, $_count = 0)
	{
		$nums = 0;
		if(is_array($imgs))
		{
			if($imgs){
				
				  foreach($imgs as $k=>$v)
				  {
				  	  $_preg = '/\{img_'.$v['title'].'\}/i';
				  	  if(($_count == 0) or ($nums < $_count)){
						
						if(!empty($v['file_name']) && !empty($img_type)) {
							
							$preg = '/\./is';
							
							$filename = preg_replace($preg, $img_type.'.', $v['file_name']);
							$_src = $this->fdfs->get_file_url ( $filename, $v['group_name'] );
							
							$url = '<br/><img style="max-width:800px;" src="' . $_src . '" /><br/>';
						    
						} else {
							//$url = '<br/><img style="max-width:800px;" src="' . base_url() . 'tmp/' . $v['name'] . $img_type . $v['ext'] . '" />';
							$url = '{img'.$v['title'].'}';
						}
						$_exists = preg_match($_preg, $content);
						if($_exists) {
							$content = preg_replace($_preg, $url, $content,1);
						} else {
							$content = preg_replace($_preg, '', $content,1);
							//$content .= $url;
						}
						$nums ++;
				  	 }
				  	 else{
				  	    $content = preg_replace($_preg, '', $content,1);
				  	 }
				  	  
				 }
				
			}
			
			//$preg = '/\{img\_\d{3}\}/i';
			//$content = preg_replace($preg, '', $content);
		}
		return $content;
	}
	
	
	/**
	 * 获取草稿数量
	 *
	 * @param	$uid			用户id
	 * @param	$draft_id		草稿id
	 * 
	 * @return	int
	 */
	public function getDraftNums($uid = null, $draft_id = null)
	{
		$sql = "select * from `".($this->draftTable)."` 
			where uid='".$uid."'
			and id<>'".$draft_id."' and status='1' and `title` is not null";
		$query = $this->db->query($sql);
		if($query){
			return $query->num_rows();
		}else{
			return false;
		}
	}
	
	/**
	 * 添加信息流
	 */
	public function addMsgFlow($uid = null, $data = null, $type = '1')
	{
		if(!$uid or !$data) {
			return false;
		}
		
		$msgdata = array(
				'uid' 		=> $uid,				// 用户ID
				'fid' 		=> $data['id'],			// 博客ID
				'uname' 	=> $data['name'],		// 作者
				'dkcode' 	=> $data['dkcode'],		// 作者端口号
				'fname' 	=> $data['fname'],		// 原作者
				'furl' 		=> $data['furl'],		// 原博客链接
				'nameurl' 	=> $data['nameurl'],	// 原作者链接
				'title' 	=> $data['title'],		// 博客标题
				'type' 		=> 'blog',				// 资源类型:博客
				'content' 	=> rtrim($data['resume'],'<br>'),		// 简要内容
				'url' 		=> $data['url'],		// 链接地址
				'action' 	=> $type,				// 1发表,2转发
				'permission' => $data['privacy'],	// 8自己,4好友,3粉丝,1公开,自定义存放UID;
				'dateline' 	=> $data['dateline'],	// 发布时间
			);
		$privacy_content = array($data['privacy_content']);
		$privacy_content = explode(",",$privacy_content[0]);
		$res = service('Timeline')->addTimeLine($msgdata,$privacy_content);
		if($res) {
			return $res;
		} else {
			return false;
		}
	}
	
	/**
	 * 更新信息流
	 */
	public function upMsgFlow($id = null, $content = null, $privacy = null, $my_uid = null, $privacy_content=null)
	{
		if(!$id or (!$content and !$privacy)) {
			return false;
		}
		$msgdata = array(
				'fid'=> $id,
				'type' 		=> 'blog',
				'content'	=> $content,
				'permission' => $privacy,
				'uid'=> $my_uid,
			);
		if($privacy=-1){
		  $privacity = array($privacy_content);
		}
		$privacity = explode(",",$privacity[0]);
		$res = api('Timeline')->updateTopic($msgdata,$privacity);
		if($res) {
			return true;
		} else {
			// Log error!
			return false;
		}
	}
	
	/**
	 * 删除信息流
	 */
	public function delMsgFlow($bid = null,$my_id = null)
	{
		if(!$bid && $my_id) {
			return false;
		}
		$res = api('Timeline')->removeTimeline($bid, $my_id, $type = "blog");
		if($res) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 当博客被别人查看或者转载时通知博主本人（发通知信息）
	 * 添加消息;
	 */
	public function addNotice($uid = null,$touid = null, $blog_name = null, $url = null)
	{
		if(!$uid or !$touid or !$blog_name or !$url) {
			return false;
		}
		$msgdata = array('1',$uid,$touid,'blog','blog_reprint',array('name'=>$blog_name,'url'=>$url));
	
		$res = service('Notice')->add_notice($msgdata);
		if($res) {
			return $res;
		}
		return false;
	}
	
	/**
	 * 更新搜索队列(调用搜索接口)
	 * @author jiangfangtao
	 * @date 2012/05/10
	 * @param $bid 博客id
	 * @param $uid 当前用户id
	 * @param $isFirst 是否是第一次添加（0:不是第一次，1:是第一次）
	 * @param $resume 博客简要描述
	 * @param $time 博客创建时间
	 * @param $title 博客标题
	 * @param $uname 当前登录用户名
	 * @param $dkcode 当前登录用户端口号
	 */
	public function updateSearch($bid,$uid,$isFirst,$resume,$time,$title,$uname,$dkcode){
	  $blogInfo=array(
	        'id'=>$bid,
			'uid'=>$uid,   
			'isfirst'=>$isFirst,
			'resume'=>$resume,
			'time'=>$time,
			'title'=>$title,
			'uname'=>$uname,
			'dkcode'=>$dkcode
	  
	  );
	  $res = service('RelationIndexSearch')->addOrUpdateBlogArticleInfo($blogInfo);
	  if($res){
		return $res;
	   }
	  else{
		return false;
	  
	  }
	}
	
	/**
	 * 删除搜索队列的相关信息
	 * @author jiangfangtao
	 * @param $bid 博客id
	 */
    public function delSearch($bid){

      $res = service('RelationIndexSearch')->deleteBlog($bid);
	  if($res){
		return $res;
	   }
	  else{
		return false;
	  
	  }
    
    }

    /**
     * 更改博客时，修改搜索增量索引
     * @author jiangfangtao
     * @date 2012/05/24
     * @param $bid 博客id
     * 
     */
    public function updateToSearch($bid)
    {
      $res = service('RestorationSearch')->restoreBlogInfo($bid);
	  if($res){
		return $res;
	   }
	  else{
		return false;
	  
	  }
    }
    
    /**
     * 获取两个人之间的关系（本项目中获取博主与访问者之间的关系）
     * @author jiangfangtao
     * @date 2012/05/26
     * @param $blog_uid 博主的uid
     * @param $my_uid 访问者的uid
     * @return int 用户2与用户的关系值： 1 好友, 2 相互关注, 3 关注对象, 4 粉丝, 5 已发请求的准好友, 0 无关系, -1 错误
     */
    public function getUsersRelation($blog_uid,$my_uid)
    {
      $res =  service('Relation')->getRelationStatus($blog_uid, $my_uid);
	  if($res){
		return $res;
	  }
	  else{
		return false;
	  }
	}
	

	
}