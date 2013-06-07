<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Blogmodel
 *
 * @author        guoshaobo
 * @date          2011/11/24
 * @version       1.0
 * @description   blog相关model
 * @history       <author><time><version><desc>
 */
class NoteModel extends MY_Model
{
	public $blogTable = "blog";
	public $draftTable = "blog_draft";
	public $photoTable = "blog_photo";
	public $forwardTable = "blog_forward";
	public $accessTable = "access_blog";
	private $blog_scope = 's';
	private $cacheTime = 600;
	
	function __construct()
	{
		parent::__construct();
		// $this->blog_scope = S();
	}
	
	/**
	 * 博客列表
	 * @param $uid 		博客用户的ID
	 * @param $s 		页面开始条数
	 * @param $limit	页面显示条数
	 * @param $power	是否有权利查看
	 * @return false:	查询结果为空
	 *		   array(): 返回查询结果;
	 */
	
	public function getList($uid = null,$s = 0, $limit = 10, $power = false, $dkcode = null, $is_friend = '0')
	{
		if(!empty($uid))
		{
			// 获取博客列表的总数目
			$data = array('uid'=>$uid,'is_delete'=>'1');
			if($power){
				$this->db->where($data);
				$nums = (int)$this->db->count_all_results($this->blogTable);
			}else{
				if(!$dkcode){
					return false;
				}
				// 添加权限之后的sql;
				$sql = "SELECT a.id,a.title,b.object_type, b.object_content
						FROM blog a LEFT JOIN access_blog b ON a.id=b.object_id
						WHERE a.uid='{$uid}' 
						AND a.is_delete='1'
						AND (
						CASE 
						WHEN (b.object_type='0' AND b.object_content LIKE '%{$dkcode}%') THEN '1'
						WHEN b.object_type='1' THEN '1'  
						WHEN b.object_type='3' THEN '{$is_friend}'
						ELSE '0'  
						END 
						) = '1'
					";
				$nums = $this->db->query($sql)->num_rows();
			}
			if($s>=$nums)
			{
				$this->db->limit($limit,$nums);
				$limits = $nums;
			}
			else
			{
				$this->db->limit($limit,$s);
				$limits = $s;
			}
			if($power){
				// 获取当前页面中博客列表的id
				$this->db->select('id,forward_id');
				$this->db->where($data);
				$this->db->order_by('dateline','DESC');
				$res= $this->db->get($this->blogTable)->result_array();
			}else{
				$sql .= ' ORDER BY a.dateline DESC LIMIT '.$limits.','.$limit;
				$res = $this->db->query($sql)->result_array();
			}
			
			if($res)
			{
				// 通过id去获取博客列表
				foreach($res as $k=>$v)
				{
					$blog = $this->getBlog($v['id']);
					
					$res[$k] = $blog[0];
					// 获取博客中的图片
					$res[$k]['pictures'] = $this->getPicture($v['id'],'blog');
				}
			}
			$list['nums'] = $nums;
			$list['res'] = $res;
			return $list;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 博客正文
	 * @param $bid  	博客ID 
	 * @param $up		true:取数据库数据;false:取缓存数据;
	 * @return array
	 */
	public function getBlog($bid = null, $up = false)
	{
		if(!$bid)
			return false;
		$cres = Service('Cache')->get('blog_blogModel_blogText_'.$bid,'blog_blogModel_blogText'.$this->blog_scope);
		// 判断数据是否存在缓存中
		if(!is_array($cres) or !is_array($cres[$bid]) or $up)
		{
			$sql = sprintf("SELECT a.*,b.oid,b.ouid,(CASE  WHEN b.id IS NOT null THEN 1 END) as forward,c.object_type,c.object_content
				FROM %s a LEFT JOIN %s b ON b.id=a.id 
					LEFT JOIN %s c ON c.object_id=a.id
				WHERE a.id='%s' AND a.is_delete='1'
				",($this->blogTable),($this->forwardTable),($this->accessTable),$bid);
			$lists = $this->db->query($sql)->result_array();
			$cres = array();
			$cres[$bid] = $lists;
			Service('Cache')->set('blog_blogModel_blogText_'.$bid,$cres,$this->cacheTime,'blog_blogModel_blogText'.$this->blog_scope);
		}
		else
		{
			$lists = $cres[$bid];
		}
		return $lists;		
	}
	
	/**
	 * 新建草稿
	 * @param	$uid		用户id
	 * @param	$draft_id	草稿id
	 * @return	string
	 */
	public function newDraft($uid = null, $draft_id = null)
	{
		if(!$uid or !$draft_id)
			return false;
		$time = time();
		$sql = sprintf("INSERT INTO %s (`id`, `uid`, `title`, `content`, `havephotos`, `dateline`, `lastupdate` ) 
			VALUES ('%s','%s',null,null,'0','%d','%d')",($this->draftTable),$draft_id, $uid, $time, $time);
		$res =  $this->db->query($sql);
		if($res)
		{
			$cres = array();
			$cres[$draft_id] = array(
						'id'=>$draft_id,
						'uid'=>$uid,
						'title'=>null,
						'content'=>null,
						'havephotos'=>0,
						'dateline'=>$time
					);
			Service('Cache')->set('blog_blogModel_blogDraftText_'.$draft_id,$cres,$this->cacheTime,'blog_blogModel_draftText'.$this->blog_scope);
		}
		return $res;
	}
	
	/**
	 * 获取草稿的内容
	 * @param $draft_id		博客草稿的id
	 * @param $up			true:取数据库数据;false:取缓存数据;
	 * @return array()		博客草稿内容
	 */
	public function getDraft($draft_id = null, $up = false)
	{
		if(!$draft_id)
			return false;
		$cres = Service('Cache')->get('blog_blogModel_blogDraftText_'.$draft_id,'blog_blogModel_draftText'.$this->blog_scope);
		if( (!is_array($cres) or !is_array($cres[$draft_id] )) or $up)
		{
			$sql = sprintf("SELECT a.*,b.object_type,b.object_content 
				FROM `%s` a LEFT JOIN `%s` b ON a.id=b.object_id
				where a.id='%s' and a.is_delete='1'",($this->draftTable),($this->accessTable),$draft_id);
			$draft = $this->db->query($sql)->result_array();
			/* $sqldata = array('id','uid','title','content','privacy','havephotos','dateline','lastupdate');
			$this->db->select($sqldata);
			$this->db->where(array('id'=>$draft_id,'is_delete'=>'1'));
			$draft = $this->db->get($this->draftTable)->result_array(); */
			$cres = array();
			$cres[$draft_id] = $draft;
			Service('Cache')->set('blog_blogModel_blogDraftText_'.$draft_id,$cres,$this->cacheTime,'blog_blogModel_draftText'.$this->blog_scope);
		}
		else
		{
			$draft = $cres[$draft_id];
		}
		return $draft;
	}
	
	/**
	 * 保存预览数据
	 */
	public function save_preview($bid = null, $data = null)
	{
		if(!$bid or !$data){
			return false;
		}
		$res = Service('Cache')->set('blog_blogModel_preview'.$bid,$data,$this->cacheTime,'blog_blogModel_preview');
		if($res){
			return true;
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
		$res = Service('Cache')->get('blog_blogModel_preview'.$bid,'blog_blogModel_preview');
		return $res;
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
		if(!empty($uid))
		{
			// 获取草稿总条数
			$data = array('uid'=>$uid,'is_delete'=>'1');
			$this->db->where($data);
			$this->db->where('title IS NOT NULL',null);
			$nums = (int)$this->db->count_all_results($this->draftTable);
			if($s>=$nums)
			{
				$this->db->limit($limit,$nums);
			}
			else
			{
				$this->db->limit($limit,$s);
			}
			// 获取当前页面中草稿列表的id
			$this->db->select('id');
			$this->db->where($data);
			$this->db->where('title IS NOT NULL',null);
			$this->db->order_by('lastupdate','DESC');
			$query= $this->db->get($this->draftTable);
			$res = $query->result_array();
			if($res)
			{
				// 通过id去查找草稿的内容
				foreach($res as $k=>$v)
				{
					$blog = $this->getDraft($v['id']);
					$res[$k] = $blog[0];
					// 获取草稿中包含的图片
					$res[$k]['pictures'] = $this->getPicture($v['id'],'blog');
				}
				$list['nums'] = $nums;
				$list['res'] = $res;
				return $list;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 获取简要的草稿列表;
	 */
	public function getEasyDraft($uid = null)
	{
		if(!$uid)
			return false;
		
		$data = array('uid'=>$uid,'is_delete'=>'1');
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
	public function getPicture($id = null, $type = 'draft', $up = false)
	{
		if(!$id)
			return false;
		$cres = Service('Cache')->get('blog_blogModel_blogPhoto_'.$id,'blog_blogModel_blogPhoto');
		if( ( !is_array($cres) or !isset($cres[$id]) ) or $up )
		{
			$sqldata = array('id','bid','name','size','type','title','come_type','pid');
			$wheredata = array('bid'=>$id,'is_delete'=>1);
			$this->db->select($sqldata);
			$this->db->where($wheredata);
			$query = $this->db->get($this->photoTable);
			$result = $query->result_array();
			$cres = array();
			$cres[$id] = $result;
			Service('Cache')->set('blog_blogModel_blogPhoto_'.$id,$cres,$this->cacheTime,'blog_blogModel_blogPhoto');
		}
		else
		{
			$result = $cres[$id];
		}
		return $result;
	}
	
	/**
	 * 博客草稿编辑
	 * @param $draft_id  博客草稿的id
	 * @param $title	 博客标题
	 * @param $content	 博客内容
	 * @param $privacy	 博客可见权限
	 * @return bool		 执行结果
	 */
	public function saveDraft($draft_id = null, $title = null, $content = null, $privacy = '1')
	{
		if(!$draft_id or !$title or !$content)
			return false;
		$time = time();
		$sqldata = array(
				'title'=>$title,
				'content'=>$content,
				'privacy'=>$privacy,
				'lastupdate'=>$time
			);
		$this->db->where('id',$draft_id);
		$res = $this->db->update($this->draftTable,$sqldata);
		if($res)
		{
			$draft = $this->getDraft($draft_id,true);
			$cres = array();
			$cres[$draft_id] = $draft;
			// 更新缓存
			Service('Cache')->set('blog_blogModel_blogDraftText_'.$draft_id,$cres,$this->cacheTime,'blog_blogModel_draftText'.$this->blog_scope);
		}
		return $res;
	}
	
	/**
	 * 删除草稿
	 * @param $draft_id 	博客草稿id
	 * @param $del 			修改标志,0为删除,1为正常,2为已发表
	 * @return bool
	 */
	public function delDraft($draft_id = null,$del = '0')
	{
		if(!$draft_id)
			return false;
		$time = time();
		$sqldata = array('is_delete'=>$del,'lastupdate'=>$time);
		$res = $this->db->update($this->draftTable,$sqldata,array('id'=>$draft_id));
		if($res){
			Service('Cache')->delete('blog_blogModel_blogDraftText_'.$draft_id,'blog_blogModel_draftText'.$this->blog_scope);
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
		$content = trim($content);
		if(empty($title))
		{
			$return['result'] = false;
			$return['info'] = '标题不能为空';
		}
		if(empty($content))
		{
			$return['result'] = false;
			$return['info'] = '内容不能为空';
		}
		
		return $return;
	}
	
	/**
	 * 图片保存入库
	 * @param $uuid 		图片id
	 * @param $draft_id 	草稿ID
	 * @param $title 		图片标题
	 * @param $layout 		图片样式
	 * @param $come 		图片来源
	 * @return bool
	 */
	public function savePhoto($uuid = null,$draft_id = null,$name = null,$type = '',$size = '0',$come = '1',$pid = '0',$title = '001')
	{
		if(!$uuid or !$draft_id or !$name)
			return false;
		$time = time();
		$sqldata = array(
				'id'=>$uuid,
				'bid'=>$draft_id,
				'draft_id'=>$draft_id,
				'name'=>$name,
				'type'=>$type,
				'size'=>$size,
				'dateline'=>$time,
				'come_type'=>$come,
				'pid'=>$pid,
				'title'=>$title
			);
		// 保存图片并更新数据
		$this->db->update($this->blogTable,array('havephotos'=>'1'),array('id'=>$draft_id));
		$this->db->update($this->draftTable,array('havephotos'=>'1'),array('id'=>$draft_id));
		// 保存图片数据
		$res = $this->db->insert($this->photoTable,$sqldata);
		if($res){
			$photo = $this->getPicture($draft_id,'draft',true);
			Service('Cache')->set('blog_blogModel_blogPhoto_'.$draft_id,$photo,$this->cacheTime,'blog_blogModel_blogPhoto');
		}
		return $res;
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
				'is_delete'=>'0',
				'lastupdate'=>$time
			);
		$this->db->where('id',$photo_id);
		$res = $this->db->update($this->photoTable,$sqldata);
		if($res)
		{
			// 获取最后一张图片的信息,并将其写入到博客(草稿)的图片缓存中
			$res = $this->db->select('draft_id')->where('id',$photo_id)->order_by('dateline','desc')->limit(1)->get($this->photoTable)->result_array();
			$photo = $this->getPicture($res[0]['draft_id'],'draft',true);
			Service('Cache')->set('blog_blogModel_blogPhoto_'.$res[0]['draft_id'],$photo,$this->cacheTime,'blog_blogModel_blogPhoto');
		}
		return $res;
	}
	
	/**
	 * 获取博客的图片数量
	 * 
	 * @param $bid 博客或者是草稿的id
	 */
	public function getBolgPhotoNums($bid = null)
	{
		$nums = 0;
		if(!$bid)
			return $nums;
		$wheresql = array('bid'=>$bid);
		$nums = $this->db->where($wheresql)->count_all_results($this->photoTable);
		return $nums;
	}
	
	/**
	 * 通过id获取相册里图片的信息;
	 * @param $pid  	图片id
	 * @param $uid  	图片所有者
	 * @return array
	 */
	public function getPhotoInfo($pid = null, $uid = null)
	{
		if(!$pid or !$uid)
			return false;
		$sqldata = array('name','size','type');
		$this->db->select($sqldata);
		$this->db->where(array('id'=>$pid,'uid'=>$uid));
		$res = $this->db->get('user_photo')->result_array();
		return $res;
	}
	
	/**
	 * 检查相册里的图片是否已经存在于博客中
	 * @param $pid	图片id
	 * @param $bid	博客id
	 * @return bool
	 */
	public function checkPhoto($pid = null, $bid = null)
	{
		if(empty($pid) or empty($bid))
			return 0;
		$wheresql = array(
				'pid'=>$pid,
				'bid'=>$bid,
				'is_delete'=>'1'
			);
		$nums = $this->db->where($wheresql)->count_all_results($this->photoTable);
		return $nums;
	}
	
	/*
	 * 发表博客
	 * @param $id 		博客id
	 * @return bool
	 */
	public function saveBlog($sqldata = null, $is_forward = true)
	{
		if(empty($sqldata))
			return false;
		$res = $this->db->insert($this->blogTable,$sqldata);
		if($res && $is_forward)
		{
			$id = $sqldata['id'];
			$blog[$id][0] = $sqldata;
			Service('Cache')->set('blog_blogModel_blogText_'.$id,$blog,$this->cacheTime,'blog_blogModel_blogText'.$this->blog_scope);
		}
		return $res;
	}
	
	/** 
	 * 博客更新
	 * @param $id		博客id
	 * @param $uid		博主id
	 * @param $title	博客标题
	 * @param $content	博客内容
	 * @param $privacy	博客权限
	 * @return bool
	 */
	public function editblog($id = null, $uid = null, $title = null,$content = null,$privacy = null)
	{
		// 暂时规定不能修改标题
		if(empty($id) or empty($uid) or empty($content))
			return false;
		$time = time();
		$sqldata = array(
					'content'=>$content,
					'lastupdate'=>$time
				);
		if($privacy !== null)
			$sqldata['privacy'] = $privacy;
		$this->db->where(array('id'=>$id,'uid'=>$uid));
		$res = $this->db->update($this->blogTable,$sqldata);
		if($res)
		{
			// 更新缓存数据
			$cres[$id] = $this->db->select('*')->where('id',$id)->get($this->blogTable)->result_array();
			Service('Cache')->set('blog_blogModel_blogText_'.$id,$cres,$this->cacheTime,'blog_blogModel_blogText'.$this->blog_scope);
		}
		return $res;
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
			if($table=='blog')
			{
				// 如果是博客,更新博客数据
				$this->getBlog($where['id'],true);
			}
		}
		return $res;
	}
	/**
	 * 删除博客
	 *
	 * @param $bid		博客id
	 * 
	 * @return bool
	 */
	public function delBlog($bid = null,$uid = null)
	{
		if(!$uid or !$bid){
			return false;
		}
		$time = time();
		$data = array('is_delete'=>'0','lastupdate'=>$time);
		$res = $this->db->update($this->blogTable,$data,array('id'=>$bid,'uid'=>$uid));
		if($res)
		{
			// 删除缓存数据
			Service('Cache')->delete('blog_blogModel_blogText_'.$bid,'blog_blogModel_blogText'.$this->blog_scope);
		}
		return $res;
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
	 * @param	$type	转载类型(社交，好友等)
	 */
	public function addForward($id = null, $uid = null,$fid = null,$oid = null,$fuid = null,$ouid = null,$time = null,$type = null)
	{
		if(!$id or !$uid or !$fid or !$oid or !$fuid or !$ouid or !$time or !$type)
			return false;
		$data = array(
				'id'=>$id,
				'uid'=>$uid,
				'fid'=>$fid,
				'fuid'=>$fuid,
				'oid'=>$oid,
				'ouid'=>$ouid,
				'dateline'=>$time,
				'type'=>$type
			);
		$res = $this->db->insert($this->forwardTable,$data);
		return $res;
	}
	
	/**
	 * 获取转载信息
	 * @param	$id		
	 * @param	$type	id的类型,'f'表示是fid,为空表示id
	 */
	public function getForward($fid = null, $type = null)
	{
		if($fid)
		{
			return $this->db->where($type.'id',$fid)->get($this->forwardTable)->result_array();
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 判断用户是否分享过该文章;
	 */
	public function get_forward_power($uid = null,$blog_id = null)
	{
		if(!$uid or !$blog_id){
			return false;
		}
		$data = array('uid'=>$uid,'fid'=>$blog_id);
		$res = $this->db->where($data)->count_all_results($this->forwardTable);
		if($res>0){
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 * 获取列表中的数据量,用作分页处理
	 * @param $table		需要查询的表
	 * @param $uid			用户
	 * @param $factor_arr	参数
	 * @ $uid			用户
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
	 * 检查图片
	 * @param	$string	需要替换的字符串
	 * @param	$bid	图片所属的博客(草稿)id
	 * @param	$type	替换规则,1为替换成图片,0为替换成空
	 */
	public function blogImageChange($string = null, $bid = null,$type = 0)
	{
		if(!$bid or !$string)
			return $string;
		// 查找图片代码 (例如:{img_001}),并
		$preg = '/\{img\_\d{3}\}/i';
		preg_match_all($preg, $string,$matches);
		if($matches[0]){
			$img_arr = array_unique($matches[0]);
			$id_preg = '/\d{3}/';
			foreach($img_arr as $k=>$v)
			{
				// 通过图片代码,查找其中的图片的title
				preg_match($id_preg,$v,$ids);
				if($ids[0])
				{
					$id = $ids[0];
					// 获取该title的图片信息
					$url = $this->getPhotoByTitle($bid,$id);
					if($url){
						$replace = ($type==1)?'<img style="max-width:480px" src="'.$url.'" />':'';
					}else{
						$replace = '';
					}
					// 将找到的第一个图片代码替换称图片,其他的替换为空
					$string = preg_replace('/{img_'.$id.'}/',$replace,$string,1);
					$string = preg_replace('/{img_'.$id.'}/','',$string,-1);
				}
			}
		}
		return $string;
	}
	
	/**
	 * 从字符串中搜索图片代码
	 */
	public function imgSearch($string = '')
	{
		if(empty($string))
			return false;
		$return = array();
		$preg = '/\{img\_\d{3}\}/i';
		preg_match_all($preg, $string,$matches);
		if($matches[0]){
			$id_preg = '/\d{3}/';
			foreach($matches[0] as $k=>$v){
				preg_match($id_preg,$v,$ids);
				if($ids[0]){
					$return[] = $ids[0];
				}
			}
		}
		return $return;
	}
	/**
	 * 搜索不在博文里的图片
	 * @param	$content	图片代码替换后的内容
	 * @param	$img_arr	已经检查出来的图片数组
	 */
	public function getDownPhoto($content = null,$img_arr = null)
	{
		if(!$content or !$img_arr)
			return false;
		$down_img = array();
		$arr = $this->imgSearch($content);
		if($arr){
			$arr = array_unique($arr);
		}else{
			return false;
		}
		foreach($img_arr as $k=>$v){
			if(!in_array($v['title'],$arr)){
				$down_img[] = $img_arr[$k];
			}
		}
		return $down_img;
	}
	
	/**
	 * 通过图片title和博客id获取图片信息
	 * @param	$bid	博客id
	 * @param	$title	图片的title
	 */
	public function getPhotoByTitle($bid = null,$title = null)
	{
		// 当前圈子
		if($this->blog_scope=='v') {
			$scope = 'visites';
		}elseif($this->blog_scope=='f') {
			$scope = 'friends';
		}else {
			$scope = 'socials';
		}
		if(empty($bid))
			return '';
		
		$sql = sprintf("SELECT a.id,a.bid,a.title,a.pid,a.come_type,a.`is_delete`,a.`type`
				FROM `%s` a 
				WHERE a.bid='%s' AND a.`is_delete`=1 ",($this->photoTable),$bid);
		if(!empty($title))
			$sql .= sprintf(" AND a.title='%s' ",$title);
		$res = $this->db->query($sql)->result_array();
		if($res){
			foreach($res as $k=>$v){
				$res[$k]['url'] = base_url().'misc/files/image/blog/'.$scope.'/'.md5($v['id']).$v['type'];
			}
			if(!empty($title)){
				return $res[0]['url'];
			}else{
				return $res;
			}
		}else{
			return '';
		}
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
			and id<>'".$draft_id."' and is_delete='1' and `title` is not null";
		$query = $this->db->query($sql);
		if($query){
			return $query->num_rows();
		}else{
			return false;
		}
	}
	
	public function test()
	{
		return 'This is a test!';
	}
}

/* End of file blog_model.php */
/* Location: ./app/modules/blog/models/blog_model.php */