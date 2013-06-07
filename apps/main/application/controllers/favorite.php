<?php
  /**
   * 收藏控制器
   * 
   * @author zhoulianbo
   * @date 2012-7-7
   */
class Favorite extends MY_Controller {
	
	/**
	 * 收藏的链接
	 */
	private $fav_url = '';
	private $my_avatar = '';
	private $author_url = '';
	
	
	/**
     * 构造函数
     */
    public function __construct(){
		parent::__construct();

		$this->my_avatar  = get_avatar ( $this->uid );
		$this->fav_url    = mk_url('main/favorite/index');
	    $this->author_url = mk_url('main/index/profile', array('dkcode' => $this->dkcode));

	    // 加载model
	    $this->load->model ( 'favoritemodel', 'favorite' );
	    
	    $this->assign ( 'author_url', $this->author_url );
		$this->assign ( 'myname', $this->username );
		$this->assign ( 'uid', $this->uid );
		$this->assign ( 'my_avatar', $this->my_avatar );
		$this->assign ( 'fav_url', $this->fav_url );
    }
    
    public function main() {
    	$this->index();
    }

    /**
     * 
     * 收藏首页
     */
	public function index() {
		
		$type = intval(abs($this->input->get('type')));
		if ($type > 3) {
			$type = '';
		}
		
		// 数据和类别
		$data = array();
		$tabTypes = getConfig('recommend', 'tab_type');
		$tabFix = getConfig('recommend', 'tab_type_fix');
		$typeName = '';
		if ($type) {
			$typeName = $tabTypes[$type];
		}
		
		// 判断是否为AJAX过来请求列表下面的页面;
		$isAjax = $this->isAjax();
		$limit = 10;
		$s = 0;
		if ($isAjax) {
			$pager = intval($this->input->post ( 'pager', 2));
			$s = ($pager - 1) * $limit;
		}
		
		$keyword = shtmlspecialchars(trim($this->input->post('keyword')));
		$nums = 0;
		$favCount= array();
		$pageText = '';
		$countData = $this->favorite->getCount($this->uid, $type, $keyword);
		if ($countData) {
			if ($type) {
				$nums = $countData;
				$pageText = '您当前共收藏了' . $nums . $tabFix[$type] . $tabTypes[$type];
			} else {
				foreach ($countData as $c) {
					$nums += $c['num'];
					$favCount[$c['type']] = $c['num'] . $tabFix[$c['type']] . $tabTypes[$c['type']];
				}
				$pageText = '您当前共有' . $nums . '条收藏，其中有' . implode('，', $favCount);
			}
		}
		

		if ($nums) {
			$data = $this->favorite->getList($this->uid, $type, $keyword, $s, $limit);
			if ($data) {
				foreach ($data as $key => $d) {
					
					// 获取显示的class
					$classType = '';
					switch ($d['type']) {
						case 2:
							$classType = 'typeAblum';
							break;
						case 3:
							$classType = 'typeVideo';
							break;
						default:
					}
					$dkcode = service('User')->getUserInfo($d['src_uid']);
					$content = json_decode(stripslashes($d['content']), true);
					
					$data[$key]['dateline']   = friendlyDate($d['dateline']);
					$data[$key]['classType']  = $classType;
					$data[$key]['typeName']   = $tabTypes[$d['type']];
					$data[$key]['content']    = $content;
					$data[$key]['dkcode']     = $dkcode['dkcode'];
					
					$aid = array_key_exists('aid', $content) ? $content['aid'] : 0;
					$data[$key]['object_url'] = $this->_createUrl($d['object_id'], $d['object_type'], $dkcode['dkcode'], $d['src_uid'], $content['web_id'], $aid);
					if (array_key_exists('web_name', $content) && $content['web_name']) {
						$data[$key]['author_url'] = mk_url('webmain/index/main', array('web_id' => $content['web_id']));
						$data[$key]['author_name'] = $content['web_name'];
					} else {
						$data[$key]['author_url'] = mk_url('main/index/profile', array('dkcode' => $dkcode['dkcode']));
						$data[$key]['author_name'] = $d['src_name'];
					}
					
					if (array_key_exists('lentime', $content)) {
						$data[$key]['content']['lentime'] = floor($content['lentime'] / 60) . ':' . ($content['lentime'] % 60);
					}
				}
				
				if ($isAjax) {
					$list = $this->_createList($data);
					
					// 判断是否需要显示查看更多按钮
					$last = ($s + $limit) >= $nums ? false : true;
					$return = array ('list' => $list, 'last' => $last);
					$this->ajaxReturn($return, '', 1);
				}
			}
		}
		
		$this->assign('data', $data);
		$this->assign('type', $type);
		$this->assign('nums', $nums);
		$this->assign('pageText', $pageText);
		$this->assign('limit', $limit);
		$this->assign('keyword', $keyword);
		$this->assign('tabs', $tabTypes);
		$this->assign('typeName', $typeName);
		$this -> display('favorite/index.html');
	}
	
	/**
	 * 
	 * 生成AJAX页面
	 * @param array $data
	 * @return string
	 */
	private function _createList($data) {
		
		$list = '';
		if (!$data) {
			return $list;
		}
		
		foreach ($data as $d) {
			$list .= '<li><h3 class="' . $d['classType'] . '">' . $d['typeName'] . '<strong>' . $d['dateline'] . '</strong></h3>
					  <div class="favoriteModel"><div class="favoriteUser"><a href="' . $this->author_url . '">
					  <img src="' . $this->my_avatar . '" width="50"></a></div>
					  <div class="favoriteMessage">
					  <h4><a href="javascript:;" onclick="delFavorite(this, ' . $d['id'] . ');" class="fr"></a>
					  <a href="' . $this->author_url . '">' . $this->username . '：</a></h4>
					  <p class="favCorner"></p><div class="favoriteBody clearfix">';
			if ($d['type'] == 1) {
				$list .= '<div class="blog"><h2><a href="' . $d['object_url'] . '">' . $d['title'] . '</a></h2>
				         <p>来自：<a target="_blank" href="' . $d['author_url'] .'">' . $d['author_name'] . '</a></p>
				         <div class="summary">' . $d['content']['resume'] . '</div>
				         <a target="_blank" href="' . $d['object_url'] . '"> &gt;&gt; 继续阅读</a></div>';
			} elseif ($d['type'] == 2) {
				$list .= '<dl class="vedio">
				         <dt><a target="_blank" href="' . $d['object_url'] . '"><img src="' . $d['content']['pic'] . '" width="168" /></a>
				         <span>' . $d['content']['lentime'] . '</span></dt>
				         <dd><h2><a target="_blank" href="' . $d['object_url'] . '">' . $d['title'] . '</a></h2>
				         <p>来自：<a target="_blank" href="' . $d['author_url'] . '">' . $d['author_name'] . '</a></p></dd></dl>';
			} else {
				$list .= '<dl class="ablum">
				          <dt><a target="_blank" href="' . $d['object_url'] . '"><img src="' . $d['content']['pic'] . '" /></a></dt>
				          <dd><h2><a target="_blank" href="' . $d['object_url'] . '">' . $d['title'] . '</a></h2>
				          <p>来自：<a target="_blank" href="' . $d['author_url'] . '">' . $d['author_name'] . '</a><br />';
				
				if ($d['object_type'] == 'photo' || $d['object_type'] == 'web_photo') {
					
					if ($d['object_type'] == 'photo') {
						$url = mk_url('album/index/photoLists', array('dkcode' => $d['dkcode'],'albumid' => $d['content']['aid']));
					} else {
						$url = mk_url('walbum/photo/index', array('web_id' => $d['content']['web_id'], 'albumid' => $d['content']['aid']));
					}
					
					$list .= '相册：<a target="_blank" href="' . $url . '">' . $d['content']['album_name'] . '</a>';
				} else {
					$list .= '共' . $d['content']['photo_count'] . '张照片';
				}
				
				$list .= '</p></dd></dl>';
			}
			$list .= '</div></div></div></li>';
		}
		return $list;
	}
	
	/**
	 * 
	 * 生成详情链接地址
	 * @param integer $oid
	 * @param string  $otype
	 * @param integer $dkcode
	 * @param integer $src_uid
	 * @param integer $web_id
	 * @param integer $aid 相册的ID
	 * @return string
	 */
	private function _createUrl($oid, $otype = '', $dkcode = 0, $src_uid = 0, $web_id, $aid = 0) {
		
		$url = '';
		switch ($otype) {
    		case 'photo':
    			$purl = mk_url('album/index/photoInfo', array('photoid' => $oid, 'dkcode' => $dkcode));
        		$url = mk_url('album/index/photoLists', 
        			array('albumid' => $aid, 'dkcode' => $dkcode, 'iscomment' => '1', 'jumpurl' => urlencode($purl)));
    			break;
    		case 'web_photo':
    			$purl = mk_url('walbum/photo/get', array('web_id' => $web_id, 'photoid' => $oid));
        		$url = mk_url('walbum/photo/index', 
        			array('albumid' => $aid, 'web_id' => $web_id, 'iscomment' => '1', 'jumpurl' => urlencode($purl)));
    			break;
    		case 'album':
    			$url = mk_url('album/index/photoLists', array('dkcode' => $dkcode, 'albumid' => $oid));
    			break;
    		case 'web_album':
    			$url = mk_url('walbum/photo/index', array('web_id' => $web_id, 'albumid' => $oid));
    			break;
    		case 'video':
    			$url = mk_url('video/video/player_video', array('vid' => $oid));
    			break;
    		case 'web_video':
    			$url = mk_url('wvideo/video/player_video', array('vid' => $oid));
    			break;
    		default:
    			$url = mk_url('blog/blog/main', array('id' => $oid, 'dkcode' => $dkcode));
    	}
    	
    	return $url;
	}
	
	/**
	 * 
	 * 添加收藏
	 */
	public function addFavorite() {
		
		$object_id = intval($this->input->get_post('object_id'));
		$src_uid   = intval($this->input->get_post('action_uid'));
		$page_type = trim($_GET['page_type']);
		$allowTypes = getConfig('recommend', 'fav_allow_types');
		$typeName  = array_key_exists($page_type, $allowTypes) ? $allowTypes[$page_type] : '';
		if (!$object_id || !$src_uid || !$page_type || !$typeName) {
			$this->ajaxReturn('', '收藏操作异常', 0);
		}
    	
    	// 不能收藏自己的东西
    	if ($src_uid == $this->uid) {
    		$this->ajaxReturn('', '您不能收藏自己的' . $typeName, 0);
    	}
    	
		// 检查模块是否存在和模块的权限
    	$data = $this->_checkAllow($object_id, $src_uid, $page_type);
    	if (!$data) {
    		$this->ajaxReturn('', '该' . $typeName . '对您不开放或者已删除', 0);
    	}
    	
    	// 检查是否收藏过
    	if ($this->favorite->checkFav($object_id, $page_type, $this->uid)) {
    		$this->ajaxReturn('', '您已经收藏过该' . $typeName . '了', 0);
    	}
		
    	// $data中有被访问者的dkcode，用来生成链接用
    	$dkcode = $data['dkcode'];
    	unset($data['dkcode']);
    	$re = $this->favorite->saveFav($data);
    	if ($re) {
    		$aid = 0;
    		$content = array();
    		if ($data['content']) {
    			$content = json_decode(stripslashes($data['content']), true);
    			if (is_array($content) && array_key_exists('aid', $content)) {
    				$aid = $content['aid'];
    			}
    		}
    		
    		$msgUrl = $this->_createUrl($object_id, $page_type, $dkcode, $src_uid, $this->web_id, $aid);
    		list($ttype, $stype) = $this->_getNoticeType($page_type);	
	    	if ($ttype || $stype) {
	    		
    			// 发送通知
	    		if (strstr($page_type, 'web_')) {
	    			$notice = array('name'  => $this->web_info['name'], 'url'   => mk_url('webmain/index/main', array('web_id' => $this->web_id)),
	    				'name1' => $data['title'], 'url1' => $msgUrl);
	    			service('Notice')->add_notice($this->web_id, $this->uid, $src_uid, $ttype, $stype, $notice);
	    		} else {
	    			$notice = array('name' => $data['title'], 'url' => $msgUrl);
	    			service('Notice')->add_notice(1, $this->uid, $src_uid, $ttype, $stype, $notice);
	    		}
	    	}
	    	$this->ajaxReturn($re, '收藏成功', 1);
    	} else {
    		$this->ajaxReturn('', '添加收藏异常，请稍后再试', 0);
    	}
	}
	
	/**
	 * 
	 * 获取通知类别
	 * @param $page_type
	 * @return array
	 */
	private function _getNoticeType($page_type) {
		
		$ttype = $stype = '';
		
		// 生成通知类别
    	switch ($page_type) {
    		case 'blog':
    			$ttype = 'blog';
    			$stype = 'blog_favorite';
    			break;
    		case 'video':
    			$ttype = 'video';
    			$stype = 'video_favorite';
    			break;
    		case 'photo':
    			$ttype = 'photo';
    			$stype = 'photo_favorite';
    			break;
    		case 'album':
    			$ttype = 'photo';
    			$stype = 'photo_albumfavorite';
    			break;
    		case 'web_video':
    			$ttype = 'web';
    			$stype = 'video_favorite_web';
    			break;
    		case 'web_photo':
    			$ttype = 'web';
    			$stype = 'photo_favorite_web';
    			break; 		
    		case 'web_album':
    			$ttype = 'web';
    			$stype = 'photo_albumfavorite_web';
    			break;
    	}
    	
    	return array($ttype, $stype);
	}

	
	/**
	 * 
	 * 检查权限并获取收藏的数据
	 * @param integer $oid  对象id
	 * @param integer $action_id  被收藏的用户id
	 * @param string  $otype 对象类型
	 * @param integer $web_id 网页id
	 * @return array
	 */
	private function _checkAllow($oid, $action_id, $otype) {
		
		// 收藏的类别和所属模块的ID
		switch ($otype) {
    		case 'blog':
    		case 'web_blog':
    			$type = 1;
    			$menu_module = 'blog';
    			break;
    		case 'video':
    		case 'web_video':
    			$type = 2;
    			$menu_module = 'video';
    			break;
    		default:
    			$type = 3;
    			$menu_module = 'album';
    	}
    	
    	if (!strstr($otype, 'web_')) {
    		
	    	// 检查个人模块的权限
	    	$appAllow = service('UserPurview')->checkAppPurview($action_id, $this->uid, $menu_module);
	    	if (!$appAllow) {
	    		return array();
			}
    	} elseif (!$this->web_id) {
			$this->ajaxReturn('', '数据错误', 0);
		}
		
		$objData = $content = array();
		$actionInfo = service('User')->getUserInfo($action_id);
		
		// 获取模块数据
		switch ($otype) {
    		case 'blog':
    		case 'web_blog':
    			$blog = array($oid => $action_id);
    			$objData = service('UserPurview')->getBlogPurview($blog, $this->uid);
    			if ($objData) {
	    			$objData = $objData[$oid][0];
	    			$title   = $objData['title'];
	    			$content = array(
	    				'resume' => $objData['resume'],
	    			);
    			}
    			break;
    		case 'video':
    		case 'web_video':
    			$video_tpye = 1;
    			if ($otype == 'web_video') {
    				$video_tpye = 2;
    			}
    			$objData = service('Video')->getVideoInfo($oid, $video_tpye, $this->uid);
    			if ($objData) {
	    			$title   = $objData['title'];
	    			$content = array(
	    				'pic'     => get_video_img($objData['video_pic']),
	    				'disc'    => $objData['discription'], 
	    				'lentime' => $objData['lentime']
	    			);
    			}
    			break;
    		case 'album':
    		case 'web_album':
				$album_type = 'album';
    			if ($otype == 'web_album') {
    				$album_type = 'walbum';
    			}
    			$objData = service('Album')->getAlbumInfo($oid, $album_type, $this->uid);
    			if ($objData) {
	    			$title   = $objData['name'];
	    			$content = array(
	    				'pic' => $objData['album_cover'],
	    				'photo_count' => $objData['photo_count']
	    			);
    			}
    			break;
    		default:
    			$album_type = 'album';
    			if ($otype == 'web_photo') {
    				$album_type = 'walbum';
    			}
    			$objData = service('Album')->getPhotoInfo($oid, $album_type, $this->uid);
    			if ($objData) {
	    			$title   = $objData['name'];
	    			$content = array(
	    				'aid'        => $objData['aid'],
	    				'album_name' => $objData['albumName'],
	    				'pic'        => $objData['img_f']
	    			);
    			}
    	}

    	// 检查信息的权限
    	if (!$objData) {
    		return array();
    	}
    	$content['web_id'] = $this->web_id;
    	$content['web_name'] = $this->web_info['name'];
    	
    	// 组装收藏数据
    	$data = array(
    		'type'          => $type, 
    		'object_id'		=> $oid, 
    		'object_type'	=> $otype, 
    		'usr_ip'		=> get_client_ip(), 
    		'username'		=> $this->username, 
    		'uid'			=> $this->uid, 
    		'src_uid'		=> $action_id,
    		'src_name'      => $actionInfo['username'],
    		'dkcode'        => $actionInfo['dkcode'],
    		'title'         => $title,
    		'content'       => addslashes(json_encode($content)),
    		'web_id'        => $this->web_id
    	);

    	return $data;
	}
	
	/**
	 * 
	 * 删除收藏
	 */
	public function delFavorite() {
		
		$fid = intval($this->input->post('fid'));
    	if (!$fid) {
    		$this->ajaxReturn('', '收藏操作异常', 0);
    	}
    	
    	$data = $this->favorite->getFavById($fid);
    	
    	// 检查是否收藏过
    	if (!$data) {
    		$this->ajaxReturn('', '数据异常', 0);
    	}
    	if ($data['uid'] != $this->uid) {
    		$this->ajaxReturn('', '该收藏不属于您', 0);
    	}

    	// 取消收藏操作
    	$re = $this->favorite->delFav($fid, $data['object_id'], $data['object_type']);
    	if ($re) {
    		$this->ajaxReturn('', '操作成功', 1);
    	} else {
    		$this->ajaxReturn('', '取消收藏异常，请稍后再试', 0);
    	}
	}
}