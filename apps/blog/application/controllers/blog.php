<?php
if (! defined ( 'BASEPATH' ))
 exit ( 'No direct script access allowed' );
/**
 * Blogmodel
 *
 * @author        guoshaobo
 * @date          2011/11/24
 * @version       1.0
 * @description   blog模块
 * @history       <author><time><version><desc>
 */

class Blog extends MY_Controller {

	private $my_uid = null; // 我的UID
	private $blog_uid = null; // 被访问者的UID
	private $blog_dkcode = null; // 被访问者的dkcode
	static $power = false; // 访问者的权限
	private $blog_url = ''; // 博客链接
	private $edit = false; // 编辑权限
	private $relation = false; //博主与访问者之间的关系

	public function __construct() 
	{
		error_reporting(E_ALL);
		parent::__construct ();
		$this->fdfs = get_storage('default');
		// 获取参数
		$my_info = $this->user;
		$blog_user_info = $this->action_user ? $this->action_user : $this->user;
		$this->my_uid = $my_info ['uid'];
		$this->my_name = $my_info ['username'];
		$this->my_avatar = get_avatar ( $this->my_uid );
		$this->blog_uid = $blog_user_info ['uid'];
		$this->blog_name = $blog_user_info ['username'];
		$this->blog_dkcode = $blog_user_info ['dkcode'];
		$this->blog_avatar = get_avatar ( $this->blog_uid );
		$this->edit = ($this->blog_uid == $this->my_uid) ? true : false;

		if($this->edit)
		{
			$this->blog_url = mk_url('blog/blog/index');
		}
		else
		{
			$this->blog_url = mk_url('blog/blog/index',array('dkcode'=>$blog_user_info['dkcode']));
		}

		// 加载model
		$this->load->model ( 'blogmodel', 'blog' );
		$this->load->model ( 'bloguploadmodel', 'upload' );
		$this->getUsersRelation ();
		// 压入模版变量

		if($this->edit)
		{
			$this->author_url = mk_url('main/index/main',array('dkcode'=>$my_info['dkcode']));
		}
		else
		{
			$this->author_url = mk_url('main/index/main',array('dkcode'=>$blog_user_info['dkcode']));
		}
		
		$this->assign ( 'author_url', $this->author_url );
		$this->assign ( 'test', 'This a assign test!' );
		$this->assign ( 'blogauthor', $this->blog_name );
		$this->assign ( 'myname', $this->my_name );
		$this->assign ( 'my_uid', $this->my_uid );
		$this->assign ( 'my_avatar', $this->my_avatar );
		$this->assign ( 'blog_avatar', $this->blog_avatar );
		$this->assign ( 'action_dkcode', $this->blog_dkcode );
		$this->assign ( 'edit', $this->edit );
		$this->assign ( 'blog_url', $this->blog_url );
	}

	public function index() 
	{
		$this->blogList ();
	}
	/**
	 * 博客列表
	 * @param $s	当前页面开始条数
	 */
	public function blogList() 
	{
		$blog_uid = $this->blog_uid;
		$my_uid = $this->my_uid;
		$power = $this->edit;
		
		if ($this->relation) {
			$relation = $this->relation;
		} else {
			$relation = 0;
		}
		if(!isset($typeId)){
			$typeId = 0;
		}
		// 判断是否为AJAX过来请求列表下面的页面;
		$isAjax = $this->isAjax ();
		if ($isAjax) {
			if($this->input->post('rel')){
				$typeId = PHP_slashes($this->input->post('rel'));
			}
			if($this->input->post ( 'pager' )){

				$pager = intval($this->input->post ( 'pager' ));
				$s = 2;
				if ($pager && $pager >= 2) {
					$s = $pager * 10;
				}
				$limit = 10;
			}else {
				$s = 0;
				$limit = 20;
			}
			
		} else {
			$s = 0;
			$limit = 20;
		}
		// 获取数据;
		$result = $this->blog->getBlogList ( $blog_uid, $my_uid, $s, $limit, $power, $relation,$typeId);
		$nums = $result ['nums'];
		$blogList = $result ['result'];

		// 处理数据;
		if ($blogList) {
			// 处理博客列表数据;
			foreach ( $blogList as $k => $v ) {
				// 获取图片
				$_img = $this->blog->getPicture ( $v ['id'], 'blog' );
				$blogList [$k] ['resume'] = $this->blog->strToImg ( $v ['resume'], $_img, '_s', true, 1 );
				$blogList [$k] ['time'] = friendlyDate ( $v ['dateline'] );
				$blogList [$k] ['author'] = $this->blog_name;
				$blogList [$k] ['author_url'] = $this->author_url;
				$blogList [$k] ['title'] = $v ['title'];
			}
		} else {
			$blogList = false;
		}
		if ($isAjax) {
			$count = $nums > 20 ? 1:0;
			$return = array ('state' => 1, 'list' => '', 'last' => true, 'msg' => '','count'=>$count );
				// 输出选项卡AJAX页面
				if ($blogList) {
						foreach ( $blogList as $k => $v ) {
							$url_arr = $this->edit ? array ('id' => $v ['id'] ) : array ('id' => $v ['id'], 'dkcode' => $this->blog_dkcode );
							$url = mk_url ( 'blog/blog/main',  $url_arr );
							$return ['list'] .= '<li class="blogList">
								<h4 class="blogListTitle">
									<a href="' . $url . '">' . ($v ['title']) . '</a>
								</h4>
								<div class="authorInfo">' . ($v ['time']) . '&nbsp;&nbsp;发布  </div>
								<div class="paragraph">' . ($v ['resume']) . '</div><div class="comment_easy" commentObjId="' . $v ['id'] . '" pageType="blog" action_uid="' . $v ['uid'] . '"></div>';
						}
					$return ['last'] = ($nums > ($s + $limit)) ? false : true;
				}else{
					
					$return['list'] = '<div class="blankWrap">您还未发布日志 <a href="'.mk_url('blog/blog/newDraft').'">发布新的日志？</a></div>';
				}
				echo json_encode ( $return );
				exit ();
		}
		$this->assign ( 'nums', $nums );
		$this->assign ( 'blog', $blogList );
		$this->display ( 'blogList.html' );

	}

	/**
	 * 草稿列表
	 * @param $s	当前页面开始条数
	 */
	public function draftList() {
		$blog_uid = $this->blog_uid;
		$my_uid = $this->my_uid;
		// 判断是否为AJAX过来请求列表下面的页面;
		$isAjax = $this->isAjax ();
		if ($isAjax) {
			$pager = $this->input->post ( 'pager' );
			$s = 2;
			$limit = 10;
			if ($pager && $pager >= $s) {
				$s = $pager * $limit;
			}
		} else {
			$s = 0;
			$limit = 20;
		}

		if ($this->edit) {
			// 获取数据;
			$result = $this->blog->getDraftList ( $my_uid, $limit, $s );
			$nums = $result ['nums'];
			$draftList = $result ['result'];
			// 处理数据;
			if ($draftList) {
				foreach ( $draftList as $k => $v ) {
					// 获取图片
					$_img = $this->blog->getPicture ( $v ['id'], 'draft' );
					//add start 1.0(by jiangfangtao 2012/04/24)
					$draftList [$k] ['title'] = htmlspecialchars ( $v ['title'] );
					//add end 1.0(by jiangfangtao)
					$draftList [$k] ['resume'] = $this->blog->strToImg ( $v ['resume'], $_img, '_s', true, 1 );
					$draftList [$k] ['time'] = friendlyDate ( $v ['dateline'] );
				}
			}
		} else {
			$nums = 0;
			$draftList = false;
		}
		if ($isAjax) {
			$return = array ('state' => 1, 'list' => '', 'last' => false, 'msg' => '' );
			// 输出AJAX页面
			if ($draftList) {
				foreach ( $draftList as $k => $v ) {
					$title_url = mk_url ( 'blog/blog/main',array ('id' => $v ['id'] ) );
					$edit_url = mk_url ( 'blog/blog/editDraft', array ('id' => $v ['id'] ) );
					$return ['list'] .= '<li class="blogList">
						<h4 class="blogListTitle">
							<a href="' . $title_url . '">' . ($v ['title']) . '</a>
						</h4>
						<div class="authorInfo">
							' . $v ['time'] . '
							&nbsp;&nbsp;<a href="' . $edit_url . '">编辑</a>
							· 
							<a href="' . $v ['id'] . '" name="draft" title="删除" class="delBlog">丢弃</a>
						</div>
						<div class="paragraph">
							' . ($v ['resume']) . '
						</div>
						<div class="blogComment"></div>
					</li>';
				}
			}
			$_nums = $s + $limit;
			$return ['last'] = $_nums < $nums ? false : true;
			echo json_encode ( $return );
			exit ();
		}
		$this->assign ( 'nums', $nums );
		$this->assign ( 'draft', $draftList );
		$this->display ( 'draftList.html' );

	}

	/**
	 * 新建草稿
	 * 说明:新建草稿,并跳转到该草稿的编辑页面
	 */
	public function newDraft() {
		$uid = $this->my_uid;
		// 生成一个id存放进去,或者是自动获取一个id;
		$res = $this->blog->newDraft ( $uid );
		if ($res) {
			// 页面跳转
			set_session ('did', $res );
			$this->redirect('blog/blog/editDraft', array('id' => $res));
		} else {
			// 新建草稿失败;
			// $this->showmessage(L('blog_error'));
		}
	}

	/**
	 * 编辑草稿
	 *
	 * @param		$draft_id		草稿id
	 * @param		$error			用来显示页面错误提示
	 */
	public function editDraft()
	{
		$uid = $this->my_uid;
		$draft_id = intval($this->input->get ( 'id' ));
		if ($draft_id) {
			// 获取草稿内容;
			$res = $this->blog->getDraft ( $draft_id, $uid );
			if ($res) {
				$draft = $res ['0'];

				$preview = $this->blog->get_preview ( $draft_id );
				if ($preview) {
					$draft ['title'] = $preview ['title'];
					$draft ['content'] = $preview ['content'];
					$draft ['privacy'] = $preview ['privacy'];
					$draft ['privacy_content'] = $preview ['object_content'];
				}

				$picdata = $this->blog->getPicture ( $draft_id, 'draft' );
				
				if ($picdata) {
					foreach ( $picdata as $k => $v ) {
						if (! empty ( $picdata [$k] ['file_name'] ) && ! empty ( $picdata [$k] ['group_name'] )) {
							$fileurl = str_replace ( '.', '_s.', $picdata [$k] ['file_name'] );
							$picdata [$k] ['url'] = $this->fdfs->get_file_url ( $fileurl, $picdata [$k] ['group_name'] );
						} else {
						}
					}
				}
			} else {
				$picdata = false;
				$draft = false;
			}

			// 获取相册信息
			$album = $this->blog->getAlbum ( $uid );

			//获取日志模块权限
			$sys_purview= service('SystemPurview')->checkApp('blog');
			$this->assign('sys_purview', $sys_purview);
			$this->assign ( 'album', $album );
			$this->assign ( 'photos', $picdata );
			$this->assign ( 'blog', $draft );
			$this->display ( 'draftEdit.html' );
		} else {
			$this->display ( 'noBlog.html' );
			die ();
		}
	}

	/**
	 * 保存草稿
	 *
	 * @param		$title			草稿的标题(不能为空)
	 * @param		$content		草稿的内容(不能为空)
	 * @param		$permission		草稿的权限设置(不能为空,默认为公开)
	 */
	public function doDraft() {
		// 获取草稿提交过来的数据;
		$draft = $_POST;
		$uid = $this->my_uid;
		$id = intval($this->input->post ( 'did' ));
		$title = PHP_slashes($this->input->post ( 'title' ));
		$content = PHP_slashes($this->input->post ( 'content' ));
		$permission = $this->input->post ( 'permissions' );
		
		if (in_array ( $permission, config_item ( 'permission' ) )) {
			$privacy = $privacy_content = $permission;
		} else {
			$privacy = '-1';
			$privacy_content = $permission;
		}

		$htmlres = htmlSubStringTitlte ( $content );

		if ($htmlres ['1']) {
			$resume = $htmlres ['0'] . "……";
		} else {
			$resume = $htmlres ['0'];
		}
		$resume = str_replace ( ' ', '&nbsp;', $resume );
		$resume = preg_replace ( "/<\/?br>(<\/?br\/?>)+/i", "<br/>", $resume );

		// 保存草稿;
		
		$this->blog->saveDraft ( $id, $uid, $title, $resume, $content, $privacy, $privacy_content );
		// 页面跳转到草稿列表中
		$this->redirect ( 'blog/blog/draftList' );
	}

	/**
	 * 判断草稿箱是否已满
	 *
	 * @param		$draft_id		当前编辑的草稿的id
	 *
	 * @return		json
	 */
	public function isDraftFull() {
		$result = array ('result' => false, 'status' => '0', 'msg' => '' );
		if (! isset ( $_GET ['id'] )) {
			die ( json_encode ( $result ) );
		}
		$draft_id = intval($this->input->get ( 'id' ));
		$uid = $this->my_uid;
		$nums = $this->blog->getDraftNums ( $uid, $draft_id );
		if (is_int ( $nums )) {
			if ($nums >= 50) {
				$list = $this->blog->getEasyDraft ( $uid );
				if ($list) {
					$result ['result'] = true;
					$result ['status'] = '1';
					$result ['text'] = '';
					foreach ( $list as $k => $v ) {
						$title_tmp = $v ['title'];
						if ((mb_strlen ( $title_tmp )) > 27) {
							$title_tmp = mb_substr ( $title_tmp, 0, 25 ) . '...';
						}
						$result ['text'] .= '<tr>
							<td class="d_title" title="' . $v ['title'] . '">' . $title_tmp . '</td>
							<td class="d_time">' . (friendlyDate ( $v ['dateline'] )) . '</td>
							<td class="d_del"><a id="' . $v ['id'] . '" name="draft_del">删除</a></td>
							</tr>';
					}
				} else {
					$result ['result'] = false;
					$result ['status'] = '3';
				}
			} else {
				$result ['result'] = true;
				$result ['status'] = '2';
			}
		} else {
			$result ['result'] = false;
			$result ['status'] = '4';
			$result ['msg'] = '';
		}
		die ( json_encode ( $result ) );
	}

	/**
	 * 设置权限
	 */
	public function setPermission() {
		$result = array ('state' => '0', 'msg' => '', 'data' => array () );
		$uid = $this->my_uid;
		$type = $this->input->post ( 'type' ); // blog
		$bid = $this->input->post ( 'object_id' ); // blog_id
		$access_type = $this->input->post ( 'access_type' ); // 权值
		$access_content = $this->input->post ( 'access_content' ); // 自定义内容

		if ($type == 'blog' && ! empty ( $object_id )) {
			$where = array ('id' => $bid, 'uid' => $uid );
			$update = array ('privacy' => $access_type, 'privacy_content' => $access_content );
			$res = $this->blog->edit ( 'blog', $where, $update );
			if ($res) {
				if ($access_type == '2') {
					$uids = split ( ',', $access_content );
					$result ['data'] = $this->blog->getUserInfo ( $uids );
				}
				$result ['state'] = '1';
				die ( json_encode ( $result ) );
			} else {
					
			}
		} else {
			$result ['state'] = '0';
			$result ['msg'] = '';
		}
		die ( json_encode ( $result ) );
	}

	/**
	 * 预览草稿
	 *
	 * @param		$draft_id		预览的草稿的id
	 * @param		$title			预览草稿的标题
	 * @param		$old_content	预览草稿的内容(原代码版本,需要处理为图片显示)
	 * @param		$permission		草稿的权限设置
	 */
	public function preview() {
		$draft_id = intval($this->input->post ( 'did' ));
		$title = $this->input->post ( 'title' );
		$old_content = $this->input->post ( 'content' );
		$permission = $this->input->post ( 'permissions' );

		$blog = array ();
		$blog ['id'] = $draft_id;
		$blog ['title'] = $this->input->post ( 'title' );
		$blog ['content'] = $old_content;
		$blog ['old_content'] = $old_content;
		$blog ['privacy'] = $permission;
		$blog ['object_content'] = $permission;
		$blog ['dateline'] = time ();
		$blog ['time'] = friendlyDate ( $blog ['dateline'] );
		$res = $this->blog->save_preview ( $blog ['id'], $blog );

		$img = $this->blog->getPicture ( $blog ['id'], 'draft' );
		$blog ['content'] = strip_tags ( $blog ['content'], '<p><b><i><u><ul><ol><li><br><em><strong>' );
		$blog ['content'] = $this->blog->strToImg ( $blog ['content'], $img, '_b', false );

		$this->assign ( 'blog', $blog );
		$this->display ( 'preview' );
	}

	/**
	 * 删除草稿
	 */
	public function delDraft() {
		// 博客id
		$uid = $this->my_uid;
		$bid = $this->input->get_post ( 'bid' );

		$res = $this->blog->delDraft ( $bid, $uid, '0' );
		if ($res) {
			echo '<script>history.go(-2);</script>';
		} else {

			$this->redirect ( 'blog/blog/index' );
		}
	}

	/**
	 * 编辑时候删除的草稿
	 */
	public function draftDel() {
		$result = array ('result' => false, 'status' => '0', 'msg' => '' );
		$bid = $this->input->post ( 'bid' );
		$uid = $this->my_uid;
		$res = $this->blog->delDraft ( $bid, $uid, '0' );
		if ($res) {
			$result ['result'] = true;
			$result ['status'] = '1';
		} else {
			$result ['result'] = false;
			$result ['status'] = '2';
			$result ['msg'] = 'delete die!';
		}
		die ( json_encode ( $result ) );
	}

	/**
	 * 丢弃列表里的草稿
	 */
	public function disDraft() {
		// 博客id
		$uid = $this->my_uid;
		$bid = $this->input->get ( 'bid' );

		$res = $this->blog->delDraft ( $bid, $uid, '0' );


		$this->redirect ( 'blog/blog/draftList' );
	}

	/**
	 * 博客发表
	 */
	public function doBlog() {
		$uid = $this->my_uid;
		$draft_id = $this->input->post('did');
		$draft_id = clean_id($draft_id);
		//分类ID
		
		if (empty ( $draft_id ))
		{
			$this->redirect ( 'blog/blog/index' );
		}
		$title = $this->input->post ( 'title' );
		$title = htmlspecialchars ( $title );
		$content = $this->input->post ( 'content' );
		$permission = $this->input->post ( 'permissions' );

		//权限
		if (in_array ( $permission, config_item ( 'permission' ) )) {
			$privacy = $privacy_content = $permission;
		} else {
			$privacy = '-1';
			$privacy_content = $permission;
		}

		$return = $this->blog->checkBlog ( $title, $content );
		
		if ($return ['result']) {
			$content = filter ( $content);

			//截取摘要（无样式）
			$htmlres = htmlSubStringTitlte( $content );
			$resume = $htmlres ['0'];
			if ($htmlres [1]) {
				$resume .= "……";
			}
			$this->blog->saveDraft ( $draft_id, $uid, $title, $resume, $content, $privacy, $privacy_content);
			
			// 保存博客
			$data = array ('uid' => $uid, 'title' => $title, 'resume' => $resume, 'content' => $content, 'privacy' => $privacy, 'dateline' => time (), 'privacy_content' => $privacy_content );

			$res = $this->blog->saveBlog ( $data );
			

			if (! empty ( $res )) {
				// 保存图片;

				$data ['id'] = $res;
				$data ['url'] = mk_url ( 'blog/blog/main', array ('id' => $res, 'dkcode' => $this->blog_dkcode ) );
				$data ['name'] = $this->my_name;
				$data ['dkcode'] = $this->user ['dkcode'];
				$data ['fname'] = $this->my_name;
				$data ['furl'] = $data ['url'];
				$data ['nameurl'] = $this->author_url;

				//add start 1.0(by jiangfangtao 2012/04/27)
				$this->blog->draftPhotoToBlog ( $draft_id, $res );
				//获取本篇博客内容中所有的图片信息
				$_img = $this->blog->getPicture ( $res, 'blog' );
				// 替换掉信息流中的图片代码,保留在博客内容中的图片标记为{img_001此类}，在显示时需要与之对应的图片进行替换
				$data ['resume'] = strip_tags ( $this->blog->strToImg ( $resume, $_img, '_s', true, 1 ), '<img><br><br/>' );
				
				//发表一篇博客后，把博客相关信息传递给首页信息流，供显示
				$r = $this->blog->addMsgFlow ( $uid, $data, '1' );

				//发表一篇博客后，把相关信息传递给搜索队列,如果是公共权限(权限值为1)，则传递，不公开不传递
				$visitorInfo = $this->user;

				$uid = $visitorInfo ['uid'];
				$uname = $visitorInfo ['username'];
				$dkcode = $visitorInfo ['dkcode'];
				if ($privacy == '1') {
					$u = $this->blog->updateSearch ( $res, $uid, 1, $resume, $data ['dateline'], $title, $uname, $dkcode );
				}
				
				$this->blog->delDraft ( $draft_id, $uid, '3' );
				
				$this->redirect ( 'blog/blog/main', array ('id' => $res ) );
			} else {
				echo 'Lost_temple!';
			}
		} else {
			echo '<script>history.go(-1);</script>';
		}
	}

	/**
	 * 转载
	 * @param $bid	被转载的博客id
	 */
	public function forward() {
		$bid = $this->input->get ( 'id' );
		$my_uid = $this->my_uid;
		$blog_uid = $this->blog_uid;
		$isFriend = $this->blog->getUsersRelation($blog_uid,$my_uid);

		$result = array ('result' => false, 'status' => '0', 'msg' => '' );
		if (! empty ( $bid )) 
		{
			$res = $this->blog->getBlog ( $bid, $blog_uid, $my_uid, $isFriend );
			if ($res) {
				$blog = $res [0];
				$time = time ();
				try {
					$fid = $blog ['id'];
					$fuid = $blog ['uid'];
					$data = array ('uid' => $my_uid, 'title' => $blog ['title'], 'resume' => $blog ['resume'], 'content' => $blog ['content'], 'dateline' => $time, 'privacy' => $blog ['privacy'], 'privacy_content' => $blog ['privacy_content'] );
					// 保存博客
					$blog_id = $this->blog->saveBlog ( $data, true );
					if ($blog_id) {
						$data ['id'] = $blog_id;
						$data ['name'] = $this->my_name;
						$data ['dkcode'] = $this->user ['dkcode'];
						$data ['url'] = mk_url ( 'blog/blog/main',array ('id' => $blog_id, 'dkcode' => $data ['dkcode'] ) );
						// 添加记录
						$f_res = $this->blog->getForward ( $fid, $fuid );
						if ($f_res) {
							$oid = $f_res [0] ['oid'];
							$ouid = $f_res [0] ['ouid'];
							$_user = getUserInfo ( $ouid );
							$_username = $_user ['username'];
							$_url = mk_url ( 'blog/blog',  array ('dkcode' => $_user ['dkcode']) );
							$data ['furl'] = mk_url ( 'blog/blog/main', array ('id' => $oid, 'dkcode' => $_user ['dkcode'] ) );
						} else {
							$oid = $fid;
							$ouid = $fuid;
							$_username = $this->blog_name;
							$_url = $this->author_url;
							$data ['furl'] = mk_url ( 'blog/blog/main',  array ('id' => $fid, 'dkcode' => ($this->blog_dkcode) ) );
						}
						$ouid_info = serialize ( array ('username' => $_username, 'url' => $_url ) );
						$res = $this->blog->addForward ( $blog_id, $my_uid, $fid, $oid, $fuid, $ouid, $time, $ouid_info );

						// 添加时间线数据
						$data ['fname'] = $_username;
						$data ['nameurl'] = $_url;
						$data ['resume'] = $this->blog->strToImg ( $data ['resume'], array () );
						$r = $this->blog->addMsgFlow ( $my_uid, $data, '2' );

						//转载一篇博客后，把相关信息传递给搜索队列
						$uid = $this->my_uid;
						$uname = $this->my_name;
						$dkcode = $this->user ['dkcode'];
						if ($blog ['privacy'] == '1') {
							$u = $this->blog->updateSearch ( $blog_id, $uid, 0, $blog ['resume'], $blog ['dateline'], $blog ['title'], $uname, $dkcode );
						}

						// 通知被转载的人
						$blog_url = mk_url ( 'blog/blog/main', array ('id' => $blog ['id'] ) );
						$m = $this->blog->addNotice ( $my_uid, $blog_uid, $blog ['title'], $blog_url );

						//copy图片
						$img = $this->blog->getPicture ( $bid, 'blog' );
						if ($img) {
							foreach ( $img as $k => $v ) {
								// 将原博客的图片复制到转载的博客中
								$file_name = $my_uid . time () . $v ['title'] . rand ( 1, 10 );
								$file_name = md5 ( $file_name );
								
								$file =  VAR_PATH .'tmp/' . $file_name . $ext;
								$old =   VAR_PATH .'tmp/' . $v ['name'] . $v ['ext'];
								$s_old = VAR_PATH .'tmp/' . $v ['name'] . '_s' . $v ['ext'];
								$b_old = VAR_PATH .'tmp/' . $v ['name'] . '_b' . $v ['ext'];
								$new =   VAR_PATH .'tmp/' . $file_name . $v ['ext'];
								$s_new = VAR_PATH .'tmp/' . $file_name . '_s' . $v ['ext'];
								$b_new = VAR_PATH .'tmp/' . $file_name . '_b' . $v ['ext'];
								$res_ = @copy ( $old, $new );
								$res_s = @copy ( $s_old, $s_new );
								$res_b = @copy ( $b_old, $b_new );
								$_pid = $this->blog->savePhoto ( $blog_id, '0', $v ['title'], $file_name, $v ['ext'], $v ['size'], $v ['come_type'], $v ['pid'] );
								if ($res_ && $_pid) {
									$upinfo = $this->fdfs->uploadFile ( $new );
									$where = array ('id' => $_pid );
									$update = array ('group_name' => $upinfo ['group_name'], 'file_name' => $upinfo ['filename'] );
									$this->blog->edit ( 'photo', $where, $update );
									if ($res_s) {
										$this->fdfs->uploadSlaveFile ( $s_new, $upinfo ['filename'], '_s',  $ext );
									}
									if ($res_b) {
										$this->fdfs->uploadSlaveFile ( $b_new, $upinfo ['filename'], '_b',  $ext );
									}
								}
							}
						}
						$result ['result'] = true;
						$result ['status'] = '1';
						die ( json_encode ( $result ) );
					} else {
						$result ['status'] = '4';
						$result ['msg'] = '转载失败,请稍后再试';
						die ( json_encode ( $result ) );
					}
				} catch ( Exception $e ) {
					// log_message();
				}
				;
			} else {
				$result ['status'] = '3';
				$result ['msg'] = '未知博客';
				die ( json_encode ( $result ) );
			}
		} else {
			$result ['status'] = '2';
			$result ['msg'] = '未知博客';
			die ( json_encode ( $result ) );
		}
	}

	/**
	 * 博文查看
	 *
	 * @param $bid  	博客id
	 *
	 */
	public function main() {
		$id = intval($_GET['id']);
		if (!$id) {
			$this->display ( 'noBlog.html' );
			exit ();
		}
		if ($this->relation) {
			$relation = $this->relation;
		} else {
			$relation = 0;
		}
			
		$bid = $id;
		$my_uid = $this->my_uid;
		$blog_uid = $this->blog_uid;
		$res = $this->blog->getBlog ( $bid, $blog_uid, $my_uid, $relation );
		if ($res) {
			$blog = $res ['0'];
			$blog ['time'] = friendlyDate ( $blog ['dateline'] );
			// 获取图片
			$img = $this->blog->getPicture ( $bid, 'blog' );
			$blog ['content'] = $this->blog->strToImg ( $blog ['content'], $img, '_b' );


				$blog ['title'] = $blog ['title'];
				$blog ['author'] = $this->blog_name;
				$blog ['author_url'] = $this->author_url;


			//----------------------关注操作时间接口start 李波2012/ 7/26--------------
			if( $this->uid != $blog_uid ){
	    		service('Relation')->updateFollowTime($this->uid, $blog_uid);
			}
			
			$this->blog_uid = $blog['uid'];
			$this->assign ( 'blog', $blog );
			$this->display ( 'blog.html' );
		} else {
			$this->display ( 'noBlog.html' );
		}

	}

	/**
	 * 删除博客
	 * @param $bid	博客id,并传去当前用户id做校验
	 */
	public function delBlog() {
		$bid = intval($this->input->post ('bid'));
		if(empty($bid)){
			exit('没有ID');
		}
		$res = $this->blog->delBlog ( $bid, $this->my_uid );

		if ($res) {
			// 删除信息流
			$this->blog->delMsgFlow ( $bid ,$this->my_uid);
			// 删除转载
			//$r = $this->blog->delForward ( $bid, $this->my_uid );
			//删除博客时也删除博客方面的搜索队列
			$u = $this->blog->delSearch ( $bid );
		}
		$this->redirect ( 'blog/blog/blogList' );
	}

	/**
	 * 博客正文编辑
	 * @param $bid  	博客id
	 */
	public function edit() {
		$my_uid = $this->my_uid;
		$blog_uid = $this->blog_uid;
		$bid = intval($this->input->get ( 'id' ));
	

		if (! $this->edit) {
			$this->display ( 'noBlog.html' );
			die ();
		}
		// 判断是否为表单提交数据;
				
		if (isset ( $_POST ['bid'] )) {

			$bid = intval($this->input->post('bid'));
			$title = PHP_slashes($this->input->post ('title'));
			$content = $this->input->post('content');
			$permission = PHP_slashes($this->input->post ('permissions'));
			if (in_array ( $permission, config_item ('permission') )) {
				$privacy = $privacy_content = $permission;
			} else {
				$privacy = '-1';
				$privacy_content = $permission;
			}
			// 校验是否标题和内容为空
			$res = $this->blog->checkBlog ( $title, $content );
			
			if ($res ['result']) {
				
				$htmlres = htmlSubStringTitlte( $content );
				$resume = $htmlres ['0'];
				if ($htmlres ['1']) {
					$resume .= "……";
				}
				$result = $this->blog->editblog ( $bid, $my_uid, $title, $content, $resume, $privacy, $privacy_content);
				
				if ($result) {

					//获取本篇博客内容中所有的图片信息
					$_img = $this->blog->getPicture ( $bid, 'blog' );
					
					// 替换掉信息流中的图片代码,保留在博客内容中的图片标记为{img_001此类}，在显示时需要与之对应的图片进行替换
					$resume = trim ( $this->blog->strToImg ( $resume, $_img, '_s', true, 1 ), '<br>' );
					$msgRes = $this->blog->upMsgFlow ( $bid, $resume, $privacy, $my_uid, $privacy_content );
					//修改一篇博客后，把相关信息传递给搜索队列
					//如果原博客是公开权限，修改后的权限是非公开权限  ，则删除其在搜索队列中的博客数据
					//如果原博客权限与现博客权限都为空，则把新的数据信息传递给搜索队列
					$uid = $this->my_uid;
					$uname = $this->my_name;
					$dkcode = $this->user ['dkcode'];
					$res = $this->blog->getBlog ( $bid, $blog_uid, $uid );
					$time = $res [0] ['dateline'];
					if ($privacy == "1") {
						$u = $this->blog->updateToSearch ( $bid );
							
					} else {
						$u = $this->blog->delSearch ( $bid );
							
					}
				}
				
				$this->redirect ( 'blog/blog/main',array('id' => $bid ) );
			} else {
				$this->redirect ( 'blog/blog/edit',array('id' => $bid ) );
			}
		} else {
	
			// 获取博客数据
			$blog_uid = $this->blog_uid;
			$res = $this->blog->getBlog ( $bid, $blog_uid, $my_uid );
			if ($res) {
				
				$blog = $res ['0'];
				$picdata = $this->blog->getPicture ( $bid, 'blog' );
				if ($picdata) {
					foreach ( $picdata as $k => $v ) 
					{
						//$picdata [$k] ['url'] = WEB_ROOT .'var/tmp/' . $v ['name'] . '_s' . $v ['ext'];
						$fileName = explode('.',$v ["file_name"]);
						$file_name = $fileName[0]."_s.jpg";
						$picdata [$k] ['url'] = $this->fdfs->get_file_url ( $file_name , $v ['group_name'] );
						
					}
				}
				$this->assign ( 'photos', $picdata );

				// 获取相册信息
				$album = $this->blog->getAlbum ( $my_uid );
				
				// 替换文章中的图片代码,并在编辑器中做检验
				$preg = array ('&lt' => '&l_t', '&gt' => '&g_t' );
				$blog ['content'] = strtr ( $blog ['content'], $preg );

				$this->assign ( 'album', $album );
				$this->assign ( 'blog', $blog );

				//获取日志模块权限
				$sys_purview= service('SystemPurview')->checkApp('blog');
				$this->assign('sys_purview', $sys_purview);
				$this->display ( 'blogEdit.html' );
			} else {
				$this->redirect ('blog/blog/blogList');
			}
		}
	}

	/**
	 * 保存图片
	 * @return array
	 */
	public function doPhoto() {
		$uid = $this->my_uid;
		$return = array ('state' => '1', 'result' => false, 'type' => 1, 'id' => '', 'title' => '', 'url' => '' );
		//save Photos;
		$bid = $this->input->post ( 'bid' );
		$did = $this->input->post ( 'did' );
		$from = $this->input->post ( 'from' );
		$type = $this->input->post ( 'type' );
		$pid = $this->input->post ( 'id' );
		$up_img_url = ($type == 'blog') ? mk_url ( 'blog/blog/uploadImg', array ('id' => $bid, 'type' => 'blog' ) ) : mk_url ( 'blog/blog/uploadImg', array ('id' => $did, 'type' => 'draft' ) );
		$id = ($type == 'blog') ? $bid : (($type == 'draft') ? $did : false);
		$file_name = $uid . time () . rand ( 1, 10 );
		$file_name = md5 ( $file_name );
		if (empty ( $id )) {
			echo '<script>
						window.parent.judge_upload(' . (json_encode ( $return )) . ')
						window.open(\'' . $up_img_url . '\',"upload_iframe");
					</script>';
			exit ();
		}
		$titles = $this->blog->getBolgPhotoNums ( $id, $type );
		$titles = $titles >= 1 ? ($titles + 1) : 1;
		$title = str_pad ( $titles, 3, "0", STR_PAD_LEFT );

		if ($from == '1') {
			// 处理上传的图片;
			$file_name = $uid . time () . rand ( 1, 10 );
			$file_name = md5 ( $file_name );
			$res = $this->upload->doUploadImg ( 'uploadPhoto', $file_name );
			if (empty ( $res )) 
			{
				$return ['result'] = false;
				$return ['type'] = '1';
				echo '<script>
						window.parent.judge_upload(' . (json_encode ( $return )) . ')
						window.open(\'' . $up_img_url . '\',"upload_iframe");
					</script>';
			} else {
				$imginfo = $res;
				$name = $file_name;
				$ext = $imginfo ['file_ext'];
				$size = $imginfo ['file_size'];

				$res = $this->blog->savePhoto ( $bid, $did, $title, $name, $ext, $size, $from, $pid );
				if (! $res) {
					// 数据保存失败
					$return ['result'] = false;
					$return ['type'] = '0';
					echo '<script>
							window.parent.judge_upload(' . json_encode ( $return ) . ');
							window.open(\'' . $up_img_url . '\',"upload_iframe");
						</script>';
					exit ();
				} else {
					$img_res = $this->upload->doImage ( $imginfo ['file_name'], null, $imginfo ['image_width'] );
					if ($img_res) {
						// 上传文件到FDFS;
						$file = VAR_PATH .'tmp/' . $file_name . $ext;
						$file_s = VAR_PATH .'tmp/' . $file_name . '_s' . $ext;
						$file_b = VAR_PATH .'tmp/' . $file_name . '_b' . $ext;
						

						$upinfo = $this->fdfs->uploadFile ( $file );

						if ($upinfo) 
						{
							$res_s = $this->fdfs->uploadSlaveFile ( $file_s, $upinfo ['filename'], '_s', $ext );
							$res_b = $this->fdfs->uploadSlaveFile ( $file_b, $upinfo ['filename'], '_b', $ext );
							$where = array ('id' => $res );

							$update = array ('group_name' => $upinfo ['group_name'], 'file_name' => $upinfo ['filename'] );
							$s_res = $this->blog->edit ( 'photo', $where, $update );
						}
							
					}

					$return ['result'] = true;
					$return ['id'] = $res;
					$return ['title'] = $title;
					$return ['url'] = VAR_PATH.'tmp/' . $name . $ext;

					if ($res_s) {
						$return ['url_s'] =  $this->fdfs->get_file_url ( $res_s ["filename"], $res_s ['group_name'] );
					} else {
						$return ['result'] = false;
					}

					echo '<script>
						window.parent.judge_upload(' . (json_encode ( $return )) . ');
						window.open(\'' . $up_img_url . '\',"upload_iframe");
					</script>';
				}
			}
		} elseif ($from == '2') {
			// 检查相册中图片是否存在
			$photo_res = $this->blog->getPhotoInfo ( $pid, $uid );

			if (! $photo_res ) {
				goto doerrorpage;
			} else {
				$photo_info = $photo_res;
			}

			if (! $photo_info) {
				goto doerrorpage;
			}
			// 检查图片是否存在博客中
			$nums = $this->blog->checkPhoto ( $pid, $id, $type );
			if ($nums > 0) {
				goto doerrorpage;
			}
			// 保存图片
			$ext = '.' . $photo_info ['type'];
			$size = $photo_info ['size'];
			$pid = $photo_info ['pid'];
			$saveid = $this->blog->savePhoto ( $bid, $did, $title, $file_name, $ext, $size, $from, $pid );
			// 保存图片到本地服务器,并处理图片
			$res = $this->upload->savePhoto ( $photo_info ['group_name'], $photo_info ['filename'] . $ext, ($file_name . $ext) );
			if ($res) {
				// 保存到fastdfs
				$file = VAR_PATH . 'tmp/' . $file_name . $ext;
				$file_s = VAR_PATH . 'tmp/' . $file_name . '_s' . $ext;
				$file_b = VAR_PATH . 'tmp/' . $file_name . '_b' . $ext;
				$upinfo = $this->fdfs->uploadFile( $file );
				if ($upinfo) {
					$res_s = $this->fdfs->uploadSlaveFile ( $file_s, $upinfo ['filename'], '_s', $ext );
					$res_b = $this->fdfs->uploadSlaveFile ( $file_b, $upinfo ['filename'], '_b', $ext );
					$where = array ('id' => $saveid );
					$update = array ('group_name' => $upinfo ['group_name'], 'file_name' => $upinfo ['filename'] );
					$s_res = $this->blog->edit ( 'photo', $where, $update );
				}
				$return ['result'] = true;
				$return ['id'] = $saveid;
				if ($res_s) {
					$return ['url'] = $this->fdfs->get_file_url ( $res_s ["filename"], $res_s ['group_name'] );
					//
				} else {
					goto doerrorpage;

				}
				$return ['title'] = $title;
				die ( json_encode ( $return ) );
			}
			doerrorpage:
			$return ['result'] = false;
			$return ['type'] = '图片重复上传';
			die(json_encode($return));
		} else {
			die ( 'Access Denied!' );
		}
	}

	/**
	 * 获取相册中的图片
	 * @param $aid	相册id
	 */
	public function getPhotos() {
		$return = array ('state' => 1, 'result' => false, 'text' => '', 'msg' => '' );
		$aid = intval($this->input->post ( 'id' ));

		$uid = $this->my_uid;
		$res = $this->blog->get_photo_lists ( $aid, $uid );
		if ($res) {
			// 直接返回页面需要的HTML代码
			$text = '<tr>';
			$count = ( int ) count ( $res );
			for($i = 0; $i < $count; $i ++) {
				if ($i != 0 && $i % 4 == 0) {
					$text .= '</tr><tr>';
				}
				$url = $res [$i]['img_s'];
				
				$text .= '<td><a id="' . $res [$i] ['id'] . '"><img src="' . $url . '" width="144" height="111" /></a></td>';
			}
			$text .= '</tr>';
			$return ['result'] = true;
			$return ['text'] = $text;
		} else {
			$return ['msg'] = '相册中没有图片,或该相册不存在';
		}
		die ( json_encode ( $return ) );
	}

	/**
	 * 删除图片
	 */
	public function delPhoto() {
		$uid = $this->my_uid;
		$return = array ('state' => '1', 'result' => false, 'msg' => '' );
		$photo_id = $this->input->post ( 'photo_id' );
		$res = $this->blog->upPhoto ( $photo_id );
		if ($res) {
			$return ['result'] = true;
			die ( json_encode ( $return ) );
		} else {
			$return ['msg'] = L ( 'operate_fail' );
			die ( json_encode ( $return ) );
		}
	}

	/**
	 * 图片上传的页面
	 * @param	$draft_id	上传图片所在博文的id
	 * @param	$type		上传图片所在博文的类别
	 */
	public function uploadImg() {
		$id = $this->input->get ( 'id' );
		$type = $this->input->get ( 'type' );
		$id = htmlspecialchars ( $id );
		$type = htmlspecialchars ( $type );
		if ($type == 'blog') {
			$bid = $id;
			$did = '0';
		} elseif ($type == 'draft') {
			$bid = '0';
			$did = $id;
		} else {
			$type = 'draft';
			$bid = $id;
			$did = '0';
		}
		$this->assign ( 'bid', $bid );
		$this->assign ( 'did', $did );
		$this->assign ( 'type', $type );

		$this->display ( 'blog_img_upload' );
	}

	/**
	 * 获取博主与访问者之间的关系
	 */
	public function getUsersRelation() {
		if ($this->blog_uid != $this->my_uid) {
			$this->relation = $this->blog->getUsersRelation ( $this->blog_uid, $this->my_uid );
		}
	}
	
}