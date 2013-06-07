<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Blogmodel
 *
 * @author        guoshaobo
 * @date          2011/11/24
 * @version       1.0
 * @description   blog模块
 * @history       <author><time><version><desc>
 */
class Note extends MY_Controller{
	
	private $my_uid = null;					// 访问者的uid
	private $blog_uid = null;				// 被访问者的uid
	private $dkcode = null;					// 被访问者的端口号
	private $blog_url = '';					// 当前链接
	private $headPhoto = '';				// 访问者的头像(小)
	private $headPhotoB = '';				// 访问者的头像(大)
	private $img_save_dir = 'socials';		// 图片上传的文件夹
	private $limit = 10;					// 分页控制，10条一页
	private $power = false;					// 权限
	
	public function __construct()
	{
		parent::__construct();
		
		$this->my_uid = UID;
		$this->blog_uid = ACTION_UID;
		$this->dkcode = ACTION_DKCODE;
		$author = getUserName($this->blog_uid);			// 被访问者的名字
		// 判断是否为自己访问自己
		if($this->my_uid == $this->blog_uid)
		{
			$this->power = true;
			$this->blog_url = WEB_ROOT . 'blog/note/';
		}
		else
		{
			$this->power = false;
			$this->blog_url = WEB_ROOT .($this->dkcode).'/blog/note/';
		}
		$this->headPhoto = getUserAvatar($this->my_uid,'id','s');
		$this->headPhotoB = getUserAvatar($this->my_uid,'id','b');
		
		$this->load->model('blogmodel','blog');
		$this->load->model('api@apimodel','',true);
		$this->load->model('home@albummodel','',TRUE);
		$this->load->model('api@accessmodel', '_access', true);
		
		$this->assign('privc',$this->img_save_dir);		// 博客相册的圈子
		$this->assign('edit',$this->power);				// 编辑权限
		$this->assign('sType','note');					// 控制地址栏的地址跳转
		$this->assign('dkcode',$this->dkcode);			// 被访问者的端口号
		$this->assign('author',$author);				// 作者
		$this->assign('my_uid',UID);					// 自己的UID
		$this->assign('blog_url',$this->blog_url);		// 当前链接
		$this->assign('headPhoto',$this->headPhoto);	// 当前头像(小)
		$this->assign('headPhotoB',$this->headPhotoB);	// 当前头像(大)
	}
	
	function __destruct()
	{
		unset($scope);
		unset($author);
	}

	function index()
	{
		redirect( ($this->blog_url) . 'blogList/');
	}
	
	/**
	 * 博客列表
	 * @param $s	当前页面开始条数
	 */
	function blogList($s = 0)
	{
		$limit = 20;						// 每页显示条数,默认为20
		$my_uid = $this->my_uid;			// 当前访问者的uid
		$blog_uid = $this->blog_uid;		// 当前博主的uid
		$power = ($my_uid==$blog_uid) ? true : false;
		$is_friend = (is_friend($my_uid, $blog_uid)) ? '1' : '0';
		$dkcode = DKCODE;
		$data = $this->blog->getList($blog_uid, $s, $limit, $power, $dkcode, $is_friend);
		$nums = $data['nums'];
		// 博客列表数据
		$list = $data['res'];
		if($list)
		{
			foreach($list as $k=>$v)
			{
				// $list[$k]['pictures'] 为当前博客的图片数组 列表中最多显示一张图片
				if($list[$k]['pictures'])
				{
					$va = $list[$k]['pictures'][0];
					$list[$k]['pictures'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($va['id']).'_s'.$va['type'];
				}
				// 检查数据,是否为分享的博客
				if(isset($v['forward']) && $v['forward'])
				{
					$list[$k]['author'] = getUserName($v['ouid']);
					$list[$k]['dkcode'] = getUserDK($v['ouid']);
					$list[$k]['title'] = '[分享] '.$v['title'];
				}
				// blogImageChange()是将内容中的图片( 例如:{img_001} )提取出来并显示在下面
				$list[$k]['content'] = $this->blog->blogImageChange($list[$k]['content'],$list[$k]['id']);
				// 截取长度;
				$htmlres = htmlSubString($list[$k]['content'],200);
				if($htmlres[1])
				{
					$list[$k]['content'] = $htmlres[0];
				}
				// $list[$k]['content'] = strip_tags($list[$k]['content'],'<p><b><i><u><ul><ol><li><br>');
			}
		}
		// 获取草稿列表的数量
		$draft_nums = $this->blog->getNums('draft',$blog_uid,array('is_delete'=>'1'));
		
		$this->assign('list',$list);
		$this->assign('nums',(int)$nums);
		$this->assign('draft_nums',$draft_nums);
		$this->display('blogList');
	}
	
	/**
	 * 博客翻页
	 * 
	 * @param	$pager		当前页码,默认从2开始
	 *
	 * @return	json
	 */
	function blogAppend()
	{
		// $result['content']是插入页面中的代码;
		$result = array('result'=>false,'status'=>0,'msg'=>'','content'=>'','s'=>0);
		$pager = P('pager');
		$s = 20;
		if($pager && $pager>=2){
			$s = $pager * 10;
		}
		$limit = $this->limit;								// 每页显示条数,默认为10
		$my_uid = $this->my_uid;							// 当前访问者的uid
		$blog_uid = $this->blog_uid;						// 当前博主的uid
		$author = getUserName($this->blog_uid);				// 当前被访问者的名字
		$power = ($my_uid==$blog_uid) ? true : false;		// 权限,是否为自己访问自己
		$is_friend = (is_friend($my_uid, $blog_uid)) ? '1' : '0';
		$dkcode = DKCODE;									// 被访问者的端口号
		$data = $this->blog->getList($blog_uid, $s, $limit, $power, $dkcode, $is_friend);
		$nums = $data['nums'];
		$list = $data['res'];
		if($list)
		{
			foreach($list as $k=>$v)
			{
				// $list[$k]['pictures'] 为当前博客的图片数组
				if($list[$k]['pictures'])
				{
					$va = $list[$k]['pictures'][0];
					$list[$k]['pictures'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($va['id']).'_s'.$va['type'];
				}
				// 检查数据,是否为分享的博客
				if(isset($v['forward']) && $v['forward'])
				{
					$list[$k]['author'] = getUserName($v['ouid']);
					$list[$k]['dkcode'] = getUserDK($v['ouid']);
					$list[$k]['title'] = '[分享] '.$v['title'];
				}
				$list[$k]['content'] = $this->blog->blogImageChange($list[$k]['content'],$list[$k]['id']);
				$htmlres = htmlSubString($list[$k]['content'],200);
				if($htmlres[1])
				{
					$list[$k]['content'] = $htmlres[0];
				}
				
				$result['content'] .= '<li class="blogListLi">
					<h3 style="width:420px;" class="wordBreak"><a href="'.($this->blog_url).'main/'.$v['id'].'">'.$list[$k]['title'].'</a></h3>';
				if(isset($v['forward']) && $v['forward']){
					$result['content'] .= '<div class="authorInfo">原文作者：<a href="'.base_url().$list[$k]['dkcode'].'/home/socials/index/">'.$list[$k]['author'].'</a> · 分享时间：'.(friendlyDate($v['dateline'])).'</div>';
				}else{
					$result['content'] .= '<div class="authorInfo">作者：<a href="'.base_url().$dkcode.'/home/socials/index/">'.$author.'</a> · 在'.(friendlyDate($v['dateline'])).'发布</div>';
				}
				$result['content'] .= '<div class="paragraph wordBreak">
						<p>'.$list[$k]['content'].'</p>';
				if($list[$k]['pictures']){
					$result['content'] .= '<img src="'.$list[$k]['pictures'].'" /><br />';
				}
				$result['content'] .= '</div>
					<!--Start 评论-->
						<div class="blogComment blogCommentAppend" commentObjId='.$v['id'].' pageType="blog">
						</div>
					<!--End 评论-->
				</li>';
			}
			$result['result'] = true;
			$result['status'] = 1;
			$residue = ($nums - $s - $limit);
			$result['s'] = $residue > 0 ? $residue : 0;
		}
		die(json_encode($result));
		exit;
	}
	
	/**
	 * 草稿列表
	 * @param		$s		当前页面开始条数
	 */
	function draft($s = 0)
	{
		$limit = 20;
		$uid = $this->my_uid;
		$data = $this->blog->getDraftList($uid,$limit,$s);
		$nums = $data['nums'];
		// 草稿列表的数据
		$list = $data['res'];
		if($list)
		{
			foreach($list as $k=>$v)
			{
				// 草稿中的图片
				if($list[$k]['pictures'])
				{
					$va = $list[$k]['pictures'][0];
					$list[$k]['pictures'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($va['id']).'_s'.$va['type'];
				}
				// 转换草稿中的图片代码( {img_001} )
				$list[$k]['content'] = $this->blog->blogImageChange($list[$k]['content'],$list[$k]['id']);
				$list[$k]['content'] = msubstr($list[$k]['content'],0,200);
			}
		}
		//获取博客列表的数量
		$draft_nums = $this->blog->getNums('blog',$uid,array('is_delete'=>'1'));
		
		$this->assign('list',$list);
		$this->assign('nums',(int)$nums);
		$this->assign('draft_nums',$draft_nums);
		$this->display('draft_list');
	}
	
	/**
	 * 草稿箱的翻页
	 * 
	 * @param	$pager		当前页码,默认从2开始
	 *
	 * @return	json
	 */
	function draftAppend()
	{
		// $result['content']是插入页面中的代码;
		$result = array('result'=>false,'status'=>0,'msg'=>'','content'=>'','s'=>0);
		$pager = P('pager');
		$s = 20;
		if($pager && $pager>=2){
			$s = $pager * 10;
		}
		$base_url = base_url();
		$limit = $this->limit;
		$uid = $this->my_uid;
		$data = $this->blog->getDraftList($uid,$limit,$s);
		$nums = $data['nums'];
		// 草稿列表的数据
		$list = $data['res'];
		if($list)
		{
			foreach($list as $k=>$v)
			{
				// 草稿中的图片
				if($list[$k]['pictures'])
				{
					$va = $list[$k]['pictures'][0];
					$list[$k]['pictures'] = $base_url.'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($va['id']).'_s'.$va['type'];
				}
				// 转换草稿中的图片代码( {img_001} )
				$list[$k]['content'] = $this->blog->blogImageChange($list[$k]['content'],$list[$k]['id']);
				$list[$k]['content'] = msubstr($list[$k]['content'],0,200);
				
				$result['content'] .= '<li class="blogListLi">
										<h3 style="width:420px;" class="wordBreak"><a href="'.$base_url.'blog/note/editDraft/'.$v['id'].'">';
				if($v['title']){
					$result['content'] .= $v['title'].'</a></h3>';
				}else{
					$result['content'] .= '无标题</a></h3>';
				}
				$result['content'] .= '<div class="authorInfo">
										'.friendlyDate($v['lastupdate']).'
										&nbsp;&nbsp;<a href="'.$base_url.'blog/note/editDraft/'.$v['id'].'">编辑</a>
										· 
										<a href="'.$v['id'].'" name="draft" title="删除" class="delBlog">丢弃</a>
									</div>
									<div class="paragraph wordBreak">
									<p>'.$list[$k]['content'].'</p>';
				if($v['pictures']){
					$result['content'] .= '<img src="'.$list[$k]['pictures'].'" /><br />';
				}
				$result['content'] .= '</div></li>';
			}
			$result['result'] = true;
			$result['status'] = 1;
			$residue = ($nums - $s - $limit);
			$result['s'] = $residue > 0 ? $residue : 0;
		}
		die(json_encode($result));
		exit;
	}
	
	/**
	 * 新建草稿
	 * 说明:新建草稿,并跳转到该草稿的编辑页面
	 */
	public function newDraft()
	{
		$uid = $this->my_uid;
		$draft_nums = $this->blog->getNums('draft',$uid,array('is_delete'=>'1'));
		
		$draft_id = get_uuid();
		$res = $this->blog->newDraft($uid, $draft_id);
		if($res)
		{
			$res = $this->_access->set('blog', $draft_id, '1');
			redirect('blog/note/editDraft/'.$draft_id);
		}
		else
		{
			$this->showmessage(L('blog_error'));
		}
	}
	
	/**
	 * 编辑草稿
	 *
	 * @param		$draft_id		草稿id
	 * @param		$error			用来显示页面错误提示
	 */
	public function editDraft($draft_id = null, $error = null)
	{
		$uid = $this->my_uid;
		if(!empty($draft_id))
		{
			$data = $this->blog->getDraft($draft_id,true);
			if(!$data){
				$this->assign('error',$error);
				$this->assign('album',false);
				$this->assign('did',$draft_id);
				$this->assign('res',false);
				$this->display('blog_draft');
				die();
			}
			if($data[0]['havephotos']=='1')
			{
				// 获取图片数据
				$picdata = $this->blog->getPicture($draft_id,'draft');
				if($picdata)
				{
					foreach($picdata as $k=>$v)
					{
						$picdata[$k]['url'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($v['id'])."_s.jpg";
					}
				}
				$this->assign('photos',$picdata);
			}
			else
			{
				$this->assign('photos',false);
			}
			// 获取相册数据
			$_scope = S();
			$album = $this->albummodel->get_album_lists($uid,$_scope);
			// 判断错误信息
			if(!empty($error))
			{
				$error = '<div style="display:block" class="errorAlert">标题或内容不能为空</div>';
			}
			else
			{
				$error = '';
			}
			$blog = $data[0];
			
			// 获取预览的数据;
			$preview = $this->blog->get_preview($draft_id);
			if($preview)
			{
				$blog['title'] = $preview['title'];
				$blog['content'] = $preview['old_content'];
				$blog['object_type'] = ($preview['permission']=='1' or $preview['permission']=='2' or $preview['permission']=='3' ) ? $preview['permission'] : '0';
				$blog['object_content'] = $preview['permission'];
			}
			
			// 替换博客的内容,并用JS处理图片解析问题
			$preg = array('&lt'=>'&l_t','&gt'=>'&g_t');
			$blog['content'] = strtr($blog['content'],$preg);
			
			$this->assign('error',$error);
			$this->assign('album',$album);
			$this->assign('did',$draft_id);
			$this->assign('res',$blog);
			$this->display('blog_draft');
			
		}
		else
		{
			$this->showmessage(L('blog_draft_not_empty'));
		}
	}
	
	/**
	 * 保存草稿
	 *
	 * @param		$draft_id		草稿id
	 * @param		$title			草稿的标题(不能为空)
	 * @param		$content		草稿的内容(不能为空)
	 * @param		$permission		草稿的权限设置(不能为空,默认为公开)
	 */
	public function doDraft($draft_id = null)
	{
		// 
		if(empty($draft_id))
		{
			$draft_id = $this->input->post('draft_id');
		}
		$title = P('title');
		$content = $this->input->post('content');
		$permission = $this->input->post('permission');
		if(!empty($draft_id))
		{
			// 敏感词过滤后保存图片
			$content = filter($content,'3');
			$content = strip_tags($content,'<p><b><i><u><ul><ol><li><br><strong><em>');
			$this->blog->saveDraft($draft_id,$title,$content);
			$this->_access->set('blog', $draft_id, $permission);
		}
		redirect(base_url().'blog/note/draft/');
	}
	
	/**
	 * 判断草稿箱是否已满
	 * 
	 * @param		$draft_id		当前编辑的草稿的id
	 * 
	 * @return		json
	 */
	public function isDraftFull($draft_id = null)
	{
		$result = array('result'=>false,'status'=>'0','msg'=>'');
		if(!$draft_id)
		{
			die(json_encode($result));
		}
		$uid = $this->my_uid;
		$nums = $this->blog->getDraftNums($uid,$draft_id);
		if(is_int($nums)){
			if($nums>=50){
				$list = $this->blog->getEasyDraft($uid);
				if($list){
					$result['result'] = true;
					$result['status'] = '1';
					$result['text'] = '';
					foreach($list as $k=>$v){
						$title_tmp = $v['title'];
						if((mb_strlen($title_tmp))>27){
							$title_tmp = mb_substr($title_tmp,0,25).'...';
						}
						$result['text'] .= '<tr>
							<td class="d_title" title="'.$v['title'].'">'.$title_tmp.'</td>
							<td class="d_time">'.(friendlyDate($v['dateline'])).'</td>
							<td class="d_del"><a id="'.$v['id'].'" name="draft_del">删除</a></td>
							</tr>';
					}
				}else{
					$result['result'] = false;
					$result['status'] = '3';
				}
			}else{
				$result['result'] = true;
				$result['status'] = '2';
			}
		}else{
			$result['result'] = false;
			$result['status'] = '4';
			$result['msg'] = '';
		}
		die(json_encode($result));
	}
	
	/**
	 * 预览草稿
	 * 
	 * @param		$draft_id		预览的草稿的id
	 * @param		$title			预览草稿的标题
	 * @param		$old_content	预览草稿的内容(原代码版本,需要处理为图片显示)
	 * @param		$permission		草稿的权限设置
	 */
	public function preview($draft_id = null)
	{
		if(empty($draft_id))
		{
			$draft_id = $this->input->post('draft_id');
		}
		$title = P('title');
		$old_content = $this->input->post('content');
		$permission = $this->input->post('permission');
		
		$blog = array();
		$blog['id'] = $draft_id;
		$blog['title'] = P('title');
		$blog['old_content'] = $old_content;
		$blog['permission'] = $permission;
		$blog['object_content'] = $permission;
		$blog['dateline'] = time();
		$res = $this->blog->save_preview($blog['id'],$blog);
		
		$img = $this->blog->getPicture($blog['id'],'draft');
		if($img)
		{
			foreach($img as $k=>$v)
			{
				// 循环图片的地址;
				$img[$k]['url'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($v['id']).$v['type'];
			}
		}
		$img = $this->blog->getDownPhoto($blog['old_content'],$img);
		$blog['content'] = $this->blog->blogImageChange($blog['old_content'],$blog['id'],1);
		$this->assign('photo',$img);
		
		$this->assign('blog',$blog);
		$this->display('showDraft');
	}
	
	/**
	 * 删除草稿
	 */
	public function delDraft()
	{
		// 博客id
		$bid = $this->input->post('bid');
		$res = $this->blog->delDraft($bid,'0');
		if($res)
		{
			echo '<script>history.go(-2);</script>';
			// redirect('blog/note/draft/');
		}
		else
		{
			redirect('blog/note/draft/');
		}
	}
	
	/**
	 * 编辑时候删除的草稿
	 */
	public function draftDel()
	{
		$result = array('result'=>false,'status'=>'0','msg'=>'');
		$bid = $this->input->post('bid');
		$res = $this->blog->delDraft($bid,'0');
		if($res)
		{
			$result['result'] = true;
			$result['status'] = '1';
		}
		else
		{
			$result['result'] = false;
			$result['status'] = '2';
			$result['msg'] = 'delete die!';
		}
		die(json_encode($result));
	}
	
	/**
	 * 丢弃列表里的草稿
	 */
	public function disDraft()
	{
		// 博客id
		$bid = $this->input->post('bid');
		$res = $this->blog->delDraft($bid,'0');
		if($res)
		{
			redirect('blog/note/draft/');
		}
		else
		{
			redirect('blog/note/draft/');
		}
	}
	
	/**
	 * 博客发表
	 * @param	$draft_id	草稿id,如果发表,博客id跟草稿id保持一致;
	 */	
	public function doBlog()
	{
		// 获取草稿中包含图片的信息
		$draft_id = $this->input->post('draft_id');
		$res = $this->blog->getDraft($draft_id,true);
		$havePhoto = (int)$res[0]['havephotos'];
		// 获取用户提交过来的数据
		$title = P('title');
		$content = $this->input->post('content');
		$permission = $this->input->post('permission');
		// 检查博客的标题和内容是否为空
		$return = $this->blog->checkBlog($title,$content);
		if($return['result'])
		{
			// 敏感词过滤之后保存
			$content = filter($content,'3');
			$content = strip_tags($content,'<p><b><i><u><ul><ol><li><br><em><strong>');
			$this->blog->saveDraft($draft_id,$title,$content);
			if(filter($title,'2'))
			{
				$this->showmessage('标题中含有敏感词,请修改');
			}
			elseif(filter($content,'2'))
			{
				$this->showmessage('内容中含有敏感词,请修改');
			}
			// 检查博客是否存在,已经存在就修改,不存在就添加
			$isBlog = $this->blog->getBlog($draft_id);
			
			$preg = array('id="'=>'.id="','id=\''=>'.id=\'','class="'=>'.class="','class=\''=>'.class=\'');
			$content = strtr($content,$preg);
			
			if($isBlog){
				$res = $this->blog->editblog($draft_id,($this->my_uid),$title,$content);
			}else{
				$time = time();
				$data = array(
							'id'=>$draft_id,
							'uid'=>$this->my_uid,
							'title'=>$title,
							'content'=>$content,
							'havephotos'=>$havePhoto,
							'dateline'=>$time,
							'forward_id'=>'0'
						);
				$res = $this->blog->saveBlog($data);
			}
			if($res)
			{	
				$this->_access->set('blog', $draft_id, $permission);
				// 添加动态 暂时不做处理
				/* $s = S();
				if($s=='f' or $s=='v')
				{
					$summary = msubstr($content,0,200);
					$scope = $this->img_save_dir;
					$uid = $this->my_uid;
					$fid = get_uuid();
					$time = time();
					$username = getUserName($this->my_uid);
					$url = base_url().'blog/note/main/'.$draft_id;
					$type = $privacy=='1' ? 'public' : 'mine';
					$blogdata = array($title, $url, $summary, $type);
					$who_url = base_url()."home/{$scope}/{$uid}";
					$blog_name = ($s=='f') ? '日记' : '记录';
					$titleString = "<a href='{$who_url}'>{$username}</a>创建了{$blog_name}<a href='{$url}'>{$title}</a>";
					$data = array(
								'id'=>$fid,
								'uid'=>$uid,
								'username'=>$username,
								'object_id'=>$draft_id,
								'dateline'=>$time,
								'scope'=>$s,
								'title'=>$titleString,
								'data'=>$blogdata,
								'type'=>'blog'
							);
					$res = Service('Feeds')->feeds($data);
				} */
				// 添加成功之后删除草稿
				$this->blog->delDraft($draft_id,'2');
				redirect('blog/note/');
			}
			else
			{
				redirect('blog/note/editDraft/'.$draft_id);
			}
		}
		else
		{
			redirect('blog/note/editDraft/'.$draft_id.'/e');
			// $this->showmessage('标题和内容不能为空');
		}
	}
	
	/**
	 * 转载
	 * @param $bid	被转载的博客id
	 */
	public function forward($bid = null)
	{
		$uid = $this->my_uid;
		$result = array('result'=>false,'status'=>'0','msg'=>'');
		if(!empty($bid))
		{
			$res = $this->blog->getBlog($bid);
			if($res)
			{
				$blog = $res[0];
				if($blog['object_type']!='1'){
				
					$result['status'] = '6';
					$result['msg'] = L('nopower_forward');
					die($result);
				}
				// Insert ;
				$time = time();
				try
				{
					$id = get_uuid();
					$fid = $blog['id'];
					$fuid = $blog['uid'];
					$data = array(
						'id'=>$id,
						'uid'=>$this->my_uid,
						'title'=>$blog['title'],
						'content'=>$blog['content'],
						'forward_id'=>$bid,
						'havephotos'=>$blog['havephotos'],
						'dateline'=>$time
					);
					// 保存博客
					$res = $this->blog->saveBlog($data,false);
					if($res)
					{
						// 添加权限,默认为公开(只有公开的才能转载)
						$this->_access->set('blog', $id, '1');
						// 添加记录
						try
						{
							$f_res = $this->blog->getForward($fid,'f');
							if($f_res)
							{
								$oid = $f_res[0]['oid'];
								$ouid = $f_res[0]['ouid'];
							}
							else
							{
								$oid = $blog['id'];
								$ouid = $blog['uid'];
							}
							$type = S();
							$res = $this->blog->addForward($id,$uid,$fid,$oid,$fuid,$ouid,$time,$type);
							//copy图片
							$img = $this->blog->getPicture($bid,'blog');
							if($img)
							{
								foreach($img as $k=>$v)
								{
									// 将原博客的图片复制到转载的博客中
									$pid = get_uuid();
									$old = 'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($v['id']).$v['type'];
									$s_old = 'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($v['id']).'_s'.$v['type'];
									$new = 'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($pid).$v['type'];
									$s_new = 'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($pid).'_s'.$v['type'];
									@copy($old,$new);
									@copy($s_old,$s_new);
									$this->blog->savePhoto($pid,$id,$v['name'],$v['type'],$v['size'],$v['come_type'],$v['pid'],$v['title']);
								}
							}
						}
						catch(Exception $e)
						{
							// log_message();
						}
						/* $old_forward[] = $forward[0];
						$up_forward = serialize($old_forward);
						$this->blog->edit('blog',array('id'=>$blog['id']),array('forward'=>$up_forward)); */
						$result['result'] = true;
						$result['status'] = '1';
						die(json_encode($result));
					}
					else
					{
						$result['status'] = '4';
						$result['msg'] = L('operate_fail');
						die(json_encode($result));
					}
				}
				catch(Exception $e)
				{
					// log_message();
				};
			}
			else
			{
				$result['status'] = '3';
				$result['msg'] = L('unknow_blog');
				die(json_encode($result));
			}
		}
		else
		{
			$result['status'] = '2';
			$result['msg'] = L('unknow_blog');
			die(json_encode($result));
		}
	}
	
	/**
	 * 博文查看
	 * @param $bid  	博客id
	 * @param $scope	当前圈子
	 */
	public function main($bid = '', $scope = null)
	{
		if($bid == '')
		{
			@redirec('blog/note/');
		}
		else
		{
			if(!empty($scope))
			{
				// 判断圈子,在通知的时候使用$scope参数
				if($scope=='f' or $scope=='v'){
					S($scope);
				}else{
					S('s');
				}
				redirect($this->blog_url.'main/'.$bid);
			}
			$_scope = S();
			$my_uid = UID;
			$uid = ACTION_UID;
			$author = getUserName($uid);
			$edit = ($uid==$my_uid) ? true : false;
			$file_name = APPPATH.'modules/blog/views/tmp/'.$_scope.'_'.$bid.'.html';
			if(!file_exists($file_name)){
				$res = $this->blog->getBlog($bid,true);
				if($this->blog_uid!=$this->my_uid)
				{
					if(isset($res[0]['object_type']) && $res[0]['object_type']=='0')
					{
						$this->assign('blog',false);
						$this->assign('photo',false);
						$this->assign('file_name',$file_name);
						$this->display('blog_detail');
						exit;
					}
				}
				// 获取博客的图片
				$img = $this->blog->getPicture($bid,'blog');
				if($img)
				{
					foreach($img as $k=>$v)
					{
						// 循环图片的地址;
						$img[$k]['url'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($v['id']).$v['type'];
					}
				}
				if($res)
				{
					// 获取图片代码被删除了的图片
					$img = $this->blog->getDownPhoto($res[0]['content'],$img);
					$res[0]['dkcode'] = getUserDK($res[0]['uid']);
					// 判断是否为分享博客
					if(isset($res[0]['forward']) && $res[0]['forward'])
					{
						$res[0]['author'] = getUserName($res[0]['ouid']);
						$res[0]['dkcode'] = getUserDK($res[0]['ouid']);
						$res[0]['title'] = '[分享] '.$res[0]['title'];
					}
					$res[0]['content'] = $this->blog->blogImageChange($res[0]['content'],$bid,1);
				}
				else
				{
					$res[0] = false;
				}
				$blog = $res[0];
				
				/* if($blog){
					$blog_static = '<div class="blogContTitle" style="word-wrap:break-word;word-break:normal">
								<h3 style="width:420px;" class="wordBreak"><a>'.$blog["title"].'</a></h3>
							</div>';
					$blog_static .= ($blog["forward_id"]!="0")
								?
									'<div class="authorInfo">原文作者：<a href="'.base_url().$blog["dkcode"].'/home/'.$this->img_save_dir.'/index/">'.$blog["author"].'</a> · 分享时间：'.date("Y-m-d H:i",$blog["dateline"]).'</div>'
								:
									'<div class="authorInfo">作者：<a href="'.base_url().$blog["dkcode"].'/home/'.$this->img_save_dir.'/index/">'.$author.'</a> · 在'.date("Y-m-d H:i",$blog["dateline"]).'发布</div>';
					$blog_static .= '<div class="paragraph wordBreak">
									'.$blog["content"];
					if(is_array($img)){
						foreach($img as $k=>$v){
							$blog_static .= '<img style="max-width:480px" src="'.$v["url"].'" />';
						}
					} 
					$blog_static .= '</div>';
					// if(!file_exists($file_name)){
						// $fp = fopen($file_name,'w');
					// }
					// fwrite($fp,$blog_static);
					// fclose($fp);
					// echo $blog_static;exit;
				} */
			}else{
				$blog = false;
				$img = false;
			}
			
			// 获取博客赞的信息
			$isPraise = $this->apimodel->check_liked($bid,$my_uid);
			$praise = $this->apimodel->get_like_info($bid,$my_uid);
			$com_list = $this->apimodel->get_comment_lists($bid);
			if($com_list)
			{
				// 循环获取博客评论信息和赞信息
				foreach($com_list as $key=>$val)
				{
					$com_list[$key]['isPraise'] = $this->apimodel->check_liked($val['id'],$my_uid);
					$com_list[$key]['praise_list'] = $this->apimodel->get_like_info($val['id'],$my_uid);
				}
			}
			
			// 获取是否已经分享过该文章
			if(!$edit){
				$forward_pow = $this->blog->get_forward_power($my_uid,$res[0]['id']);
			}else{
				$forward_pow = false;
			}
			// echo $blog_static;
			
			$this->assign('file_name',$file_name);
			$this->assign('praise',$praise);
			$this->assign('my_uid',$my_uid);
			$this->assign('com_list',$com_list);
			$this->assign('isPraise',$isPraise);
			$this->assign('photo',$img);
			$this->assign('edit',$edit);
			$this->assign('author',$author);
			$this->assign('blog',$blog);
			$this->assign('bid',$bid);
			$this->assign('forward_pow',$forward_pow);
			$this->display('blog_detail');
		}
	}
	
	/**
	 * 删除博客
	 * @param $bid	博客id,并传去当前用户id做校验
	 */
	public function delBlog()
	{
		$bid = $this->input->post('bid');
		$res = $this->blog->delBlog($bid,$this->my_uid);
		if($res)
		{
			$file_name = './'.APPPATH.'modules/blog/views/tmp/'.S().'_'.$bid.'.html';
			$res = @unlink($file_name);
			redirect('blog/note/blogList/');
		}
		else
		{
			echo "<script>alert('操作失败,请稍候再试');</script>";
		}
	}
	
	/**
	 * 博客正文编辑
	 * @param $bid  	博客id
	 */
	public function edit( $bid = '')
	{
		$uid = $this->my_uid;
		// 判断是否为表单提交数据;
		if(isset($_POST['bid']))
		{
			$bid = $this->input->post('bid');
			$title = P('title');
			$content = $this->input->post('content');
			$permission = $this->input->post('permission');
			// 校验是否标题和内容为空
			$res = $this->blog->checkBlog($title,$content);
			if($res['result'])
			{
				$content = strip_tags($content,'<p><b><i><u><ul><ol><li><br><em><strong>');
				$this->blog->editblog($bid,$uid,$title,$content);
				$this->_access->set('blog', $bid, $permission);
				// delete static_file;
				$file_name = './'.APPPATH.'modules/blog/views/tmp/'.S().'_'.$bid.'.html';
				$res = @unlink($file_name);
				redirect('blog/note/main/'.$bid);
			}
			else
			{
				redirect('blog/note/edit/'.$bid);
			}
		}
		else
		{
			// 获取博客数据
			$res = $this->blog->getBlog($bid,true);
			if($res)
			{
				// 获取博客的图片数据
				$picdata = $this->blog->getPicture($bid,'blog');
				if($picdata)
				{
					foreach($picdata as $k=>$v)
					{
						$picdata[$k]['url'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($v['id']).'_s'.$v['type'];
					}
				}
				$this->assign('photos',$picdata);
				
				// 获取相册信息
				$_scope = S();
				$album = $this->albummodel->get_album_lists($uid,$_scope);
				$blog = $res[0];
				// 替换文章中的图片代码,并在编辑器中做检验
				$preg = array('&lt'=>'&l_t','&gt'=>'&g_t');
				$blog['content'] = strtr($blog['content'],$preg);
				$this->assign('album',$album);
				$this->assign('blog',$blog);
				$this->assign('bid',$bid);
				$this->display('blog_edit');
			}
			else
			{
				redirect('blog/note/blogList/');
			}
		}
	}
	
	/**
	 * 保存图片
	 * @return array
	 */
	public function doPhoto()
	{
		$uid = $this->my_uid;
		$return = array(
				'state'=>'1',
				'result'=>false,
				'type'=>0,
				'id'=>'',
				'title'=>'',
				'url'=>'',
			);
		//save Photos;
		$draft_id = $this->input->post('draft_id');
		$title = P('title');
		$layout = $this->input->post('layout');
		$from = $this->input->post('from');
		$pid = $this->input->post('id');
		$uuid = get_uuid();
		$titles = $this->blog->getBolgPhotoNums($draft_id);
		$titles = $titles>=1 ? ($titles+1) : 1;
		$title = str_pad($titles, 3, "0", STR_PAD_LEFT);
		// $from判断图片的来源,2表示从相册中选取图片
		if($from == '2')
		{
			// 检查相册中图片是否存在
			$res = $this->blog->getPhotoInfo($pid,$uid);
			if(!$res) 
			{
				$return['result'] = false;
				$return['type'] = L('unknow_pic');
				die(json_encode($return));
			}
			// 检查图片是否存在博客中
			$nums = $this->blog->checkPhoto($pid,$draft_id);
			if($nums>0)
			{
				$return['result'] = false;
				$return['type'] = '该图片已存在';
				die(json_encode($return));
			}
			// 保存图片数据并从相册中复制图片过来
			$photo_info = $res[0];
			$res = $this->blog->savePhoto($uuid,$draft_id,$photo_info['name'],'.'.$photo_info['type'],$photo_info['size'],$from,$pid,$title);
			if($res)
			{
				// 图片的格式
				$conf = array();
				$conf['image_library'] = 'gd2';
				$conf['source_image'] = 'misc/files/image/album/'.md5($uid).'/'.md5($pid).'.'.$photo_info['type'];
				$conf['new_image'] = 'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($uuid).'.'.$photo_info['type'];
				$conf['create_thumb'] = true;
				$conf['thumb_marker'] = '';
				$conf['maintain_ratio'] = true;
				$conf['master_dim'] = 'width';
				$conf['width'] = 450;
				$conf['height'] = 1;
				// 生成450的图
				$this->load->library('image_lib',$conf);
				$this->image_lib->resize();
				// 重置参数
				$this->image_lib->clear();
				// 生成缩略图
				$conf['thumb_marker'] = '_s';
				$conf['width'] = 144;
				$this->image_lib->initialize($conf);
				$this->image_lib->resize();
				
				$return['result'] = true;
				$return['id'] = $uuid;
				$return['url'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($uuid).'_s.'.$photo_info['type'];
				$return['title'] = $title;
				die(json_encode($return));
			}
			else
			{
				$return['result'] = false;
				$return['type'] = '图片选择失败,请重新选择';
				die(json_encode($return));
			}
		}
		elseif($from=='1')
		{
			// 上传的图片处理
			load_class('Upload');
			$config['upload_path'] = './misc/files/image/blog/'.($this->img_save_dir).'/';
			$config['allowed_types'] = 'gif|jpg|png|bmp';
			$config['max_size'] = '0';
			$config['file_name'] = md5($uuid);
			// 初始化图片参数
			$upload = new CI_Upload($config);
			$upload->initialize($config);
			$upload->upload_path = $config['upload_path'];
			
			if(!$upload->do_upload('uploadPhoto'))
			{
				// 上传失败
				$error = $upload->display_errors();
				$return['result'] = false;
				$return['type'] = '1';
				echo '<script>
						window.parent.judge_upload('.(json_encode($return)).')
						window.open(\''.(base_url()).'blog/note/uploadImg/'.$draft_id.'\',"upload_iframe");
					</script>';
			}
			else
			{
				$imginfo = $upload->data();
				$name = $imginfo['client_name'];
				$type = $imginfo['file_ext'];
				$size = $imginfo['file_size'];
				// 保存图片到数据库
				$res = $this->blog->savePhoto($uuid,$draft_id,$name,$type,$size,$from,'0',$title);
				if(!$res)
				{
					// 数据保存失败
					$return['result'] = false;
					$return['type'] = '0';
					echo '<script>
							window.parent.judge_upload('.json_encode($return).');
							window.open(\''.(base_url()).'blog/note/uploadImg/'.$draft_id.'\',"upload_iframe");
						</script>';
					exit;
				}
				// 判断图片的宽度,以宽度为标准做等比例缩放至450px宽度
				if($imginfo['image_width']>450){
					$height = ceil(($imginfo['image_width']/450)*$imginfo['image_height']);
					resize_img($imginfo['full_path'],$imginfo['full_path'],450,$height);
				}
				$conf['image_library'] = 'gd2';
				$conf['source_image'] = $imginfo['full_path'];
				$conf['creat_thumd'] = true;
				$conf['thumb_marker'] = '_s';
				$conf['new_image'] = $imginfo['raw_name'].'_s'.$imginfo['file_ext'];
				$conf['master_dim'] = 'width';
				// $res = $conf['image_width']/$conf['image_height'];
				$conf['width'] = '144';
				$conf['height'] = '111';
				// 压缩图
				$this->load->library('image_lib',$conf);
				$this->image_lib->resize();
				// $data = array('upload_data'=>$imginfo);
				
				$return['result'] = true;
				$return['id'] = $uuid;
				$return['title'] = $title;
				$return['url'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($uuid).$type;
				$return['url_s'] = base_url().'misc/files/image/blog/'.($this->img_save_dir).'/'.md5($uuid).'_s'.$type;
				echo '<script>
						window.parent.judge_upload('.(json_encode($return)).');
						window.open(\''.(base_url()).'blog/note/uploadImg/'.$draft_id.'\',"upload_iframe");
					</script>';
			}
		}
		else
		{
			die('Access Denied!');
		}
	}
		
	/**
	 * 获取相册中的图片
	 * @param $aid	相册id
	 */	
	public function getPhotos($aid = '')
	{
		$return = array(
					'state'=>1,
					'result'=>false,
					'text'=>'',
					'msg'=>''
				);
		$aid = $this->input->post('id');
		$uid = UID;
		$res = $this->albummodel->get_photo_lists($aid);
		if($res)
		{
			// 直接返回页面需要的HTML代码
			$text = '<tr>';
			$count = (int) count($res);
			for($i=0;$i<$count;$i++){
				if($i!=0 && $i%4==0)
				{
					$text .= '</tr><tr>';
				}
				$url = $res[$i]['img_small'];
				$text .= '<td><a id="'.$res[$i]['id'].'"><img src="'.$url.'" width="144" height="111" /></a></td>';
			}
			$text .= '</tr>';
			$return['result'] = true;
			$return['text'] = $text;
		}
		else
		{
			$return['msg'] = L('unknow_album');
		}
		die(json_encode($return));
	}
	
	/* 
	 * 删除图片
	*/
	public function delPhoto()
	{
		$uid = $this->my_uid;
		$return = array(
				'state'=>'1',
				'result'=>false,
				'msg'=>''
			);
		$photo_id = $this->input->post('photo_id');
		$res = $this->blog->upPhoto($photo_id);
		if($res)
		{
			$return['result'] = true;
			die(json_encode($return));
		}
		else
		{
			$return['msg'] = L('operate_fail');
			die(json_encode($return));
		}
	} 
	
	/**
	 * 图片上传的页面
	 * @param	$draft_id	博客或是草稿的id(博客和草稿的id是一致的)
	 */
	public function uploadImg($draft_id = '')
	{
		$this->assign('did',$draft_id);
		$this->display('blog_img_upload');
	}
	
	function test()
	{
		$res = $this->blog->getEasyDraft($this->my_uid);
		var_dump($res);
	}
	
}