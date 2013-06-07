<?php
/**
 * 分享控制器
 *
 * @author zhoulianbo
 * @date 2012-7-19
 */

class Share extends MY_Controller {
	
	const TOPIC_FROM_INFO = 1;
	const TOPIC_FROM_WEB  = 3;
	
	/**
     * 构造函数
     */
    public function __construct(){
		parent::__construct();
    }
	
	/**
	 * 分享模块中得的数据到信息流或者转发信息流利的数据
	 * 
	 * 前端需要提交的参数列表：
	 * @param content 用户填写的内容
	 * @param tid 当前信息实体
	 * @param fid 原信息实体
	 * @param reply_author 是否评论给原作者 UID
	 * @param reply_now 是否评论给当前作者 UID
	 */
	public function doShare() {
		
		$data = array(
			'uid'      => $this->uid, 
			'dkcode'   => $this->dkcode, 
			'uname'    => $this->username,
			'type'     => 'forward',
			'dateline' => time(), 
			'from'     => self::TOPIC_FROM_INFO, 
			'ctime'    => time()
		);
		
		// 当前信息实体ID
		$tid  = intval($this->input->get_post('tid'));
		
		// 源信息实体ID|模块信息ID
		$fid  = intval($this->input->get_post('fid'));
		$type = shtmlspecialchars($this->input->get_post('page_type'));
		$action_uid = shtmlspecialchars($this->input->get_post('action_uid'));
		
		if (!$tid || (!$type && !$fid) || !$action_uid) {
			$this->ajaxReturn('', '参数错误', 0);
		}

		// 取得实体内容
		if (!$type) {
			$infos = service('Timeline')->getTopicByTid($fid);
			$pid = $tid;
		} else {
			$infos = service('Timeline')->getTopicByMap($tid, $type, $action_uid);
			
			// 针对相册和照片做特殊处理
			if (!$infos) {
				$infos = $this->_addAlbumData($tid, $type);
			}
			
			$pid = $fid = $infos ? $infos['tid'] : 0;
		}

		if (!$infos) {
			$this->ajaxReturn('', '数据不存在，无法分享', 0);
		}
		
		// 检查分享类别
		if (!service('Comlike')->checkAllowType($infos['type'], 'share')) {
			$this->ajaxReturn('', '该类别无法分享', 0);
		}
		
		//是否评论给原作者
		$replyAuthor = shtmlspecialchars($this->input->get_post('reply_author'));
		
		//是否评论给当前作者
		$replyNow = shtmlspecialchars($this->input->get_post('reply_now'));
		if ( $tid === $fid ) {
			$replyAuthor = $replyNow;
			$replyNow = false;
		}
		
		$data['fid']        = $fid;
		$data['permission'] = $infos['permission'];
		$data['content']    = shtmlspecialchars($this->input->get_post('content'));
		$data['title']      = isset($infos['title']) ? $infos['title'] : '';
		$result = service('Timeline')->addTimeline($data) ?  : '';
		if ( !$result ) {
			$this->ajaxReturn('', '操作错误', 0);
		}
		
		// 增加分享积分
		if ($this->uid != $infos['uid']) {
			service('credit')->forward();
		}
		
		// 添加到转发列表
		$params = array('uid' => $data['uid'], 'content' => $data['content']);
       	service('Share')->add('topic', $data['fid'], $pid, $result['tid'], $params);
       	service('Timeline')->updateTopicHot($data['fid'], 1);
		
       	// 评论给原作者
      	$type = $infos['type'] == 'info' ? 'topic' : $infos['type'];
		if( $replyAuthor && $fid) {
			if( $type == 'topic' || $type == 'sharevideo') {
				$objectId = $infos['tid'];
			} elseif( $type == 'photo' ) {
				$objectId = $infos['picurl']['0']->pid;
			} else {
				$objectId = $infos['fid'];
			}

			$re = $this->_addComment($objectId, $replyAuthor, $type, $data['content']);
		}
		
		// 评论给当前作者
		if ( $replyNow && $tid ) {
			$this->_addComment($tid, $replyNow, 'forward', $data['content']);
		}
		
		// 增加评论积分
		if ($this->uid != $infos['uid'] && (($replyAuthor && $fid) || ($replyNow && $tid))) {
			service('credit')->comment();
		}
		
		//===========添加转发通知==============
		if ( ! $replyAuthor ) {
			$replyAuthor = $infos['uid'];
		}
		
		// 自己分享自己的信息不发送通知
		if ($replyAuthor != $this->uid) {
			$notice = array('name' => $result['title'], 'url' => mk_url('main/info/view', array('tid' => $result['tid'])));
			$_static = array('topic' => 'info_frowardinfo', 'photo' => 'info_frowardpic', 'video' => 'info_frowardvideo', 
					'album' => 'info_frowardalbum', 'blog' => 'info_froward_blog');
			if (array_key_exists($type, $_static)) {
				service('Notice')->add_notice(1, $data['uid'], $replyAuthor, 'info', $_static[$type], $notice);
			}
		}
		unset($data, $infos);
		
    	service('Relation')->updateFollowTime($this->uid, $replyAuthor);
		$this->ajaxReturn($result, '操作成功', 1);
	}
	
	/**
	 * 网页中转发数据到时间线操作方法
	 * 
	 * @param web_id 网页id
	 * @param content 用户填写的内容
	 * @param tid 当前信息实体
	 * @param fid 原信息实体
	 * @param reply_author 是否评论给原作者 UID
	 * @param reply_now 是否评论给当前作者 UID
	 */
	public function webShare() {
		
		$web_id = intval($this->input->get_post('my_web_id'));
		if (!$web_id || !$this->web_id) {
			$this->ajaxReturn('', '请选择所要分享到的网页', 0);
		}
		
		$webInfo = service('interest')->get_web_info($web_id);
		if (!$webInfo || $webInfo['uid'] !== $this->uid) {
			$this->ajaxReturn('', '该网页不属于您', 0);
		}

		$time = date('YmdHis');
		$data = array(
				'uid'      => $this->uid,
				'dkcode'   => $this->dkcode,
				'pid'      => $web_id,
				'uname'    => $webInfo['name'],
				'type'     => 'forward',
				'dateline' => $time,
				'from'     => self::TOPIC_FROM_WEB,
				'ctime'    => $time
		);
		
		// 当前信息实体ID
		$tid  = intval($this->input->get_post('tid'));
		
		// 源信息实体ID|模块信息ID
		$fid  = intval($this->input->get_post('fid'));
		$type = shtmlspecialchars($this->input->get_post('page_type'));
		
		if (!$tid || (!$type && !$fid)) {
			$this->ajaxReturn('', '参数错误', 0);
		}
		
		//是否评论给原作者
		$replyAuthor = shtmlspecialchars($this->input->get_post('reply_author'));
		
		//是否评论给当前作者
		$replyNow = shtmlspecialchars($this->input->get_post('reply_now'));
		if ($tid === $fid) {
			$replyAuthor = $replyNow;
			$replyNow = false;
		}
		
		// 取得实体内容
		if (!$type) {
			$infos = service('WebTimeline')->getWebtopicByMap($fid, '', $this->web_id);
			$pid = $tid;
		} else {
			$infos = service('WebTimeline')->getWebtopicByMap($tid, str_replace('web_', '', $type), $this->web_id);
			
			// 针对相册和照片做特殊处理
			if (!$infos) {
				$infos = $this->_addAlbumData($tid, $type);
			}
			$pid = $fid = $infos ? $infos['tid'] : 0;
		}
		
		if (!$infos) {
			$this->ajaxReturn('', '数据不存在，无法分享', 0);
		}
		
		// 检查分享类别
		if (!service('Comlike')->checkAllowType($infos['type'], 'share')) {
			$this->ajaxReturn('', '该类别无法分享', 0);
		}
		
		$data['fid'] = $fid;
		$data['content'] = shtmlspecialchars($this->input->get_post('content'));
		$data['title'] = isset($infos['title']) ? $infos['title'] : '';
		if (empty($data['content'])) {
			$data['content'] = '分享';
		}
		
		// 从网页转发到自己的时间线
		$result = service('WebTimeline')->addWebtopic($data, $this->_getWebpageTagID($data['pid']));
		if (!$result) {
			$this->ajaxReturn('', '操作错误', 0);
		}
		
		// 添加到转发列表
		$params = array(
			'uid' => $data['uid'],
			'content' => $data['content'] 
		);
		service('Share')->add('web_topic', $fid, $pid, $result['tid'], $params);
		service('WebTimeline')->updateWebtopicHot($fid, 1);
		
		// 评论给原作者 
		$type = $infos['type'] == 'info' ? 'topic' : $infos['type'];
		if( $type == 'topic' || $type == 'sharevideo') {
			$objectId = $infos['tid'];
		} elseif( $type == 'photo' ) {
			$objectId = $infos['picurl']['0']->pid;
		} else {
			$objectId = $infos['fid'];
		}
				
		// 评论给原作者
		if( $replyAuthor && $fid ) {
			$this->_addComment($objectId, $replyAuthor, 'web_' . $type, $data['content']);
		}
		
		// 评论给当前作者
		if( $replyNow && $tid ) {
			$this->_addComment($tid, $replyNow, 'web_forward', $data['content']);
		}

		// 添加分享通知
		if( ! $replyAuthor ) {
			$replyAuthor = $infos['uid'];
		}
		
		// 自己分享自己的信息不发送通知
		if ($replyAuthor != $this->uid) {
			$notice = array(
				'name'  => $this->web_info['name'],
				'url'   => mk_url('webmain/index/main', array('web_id' => $this->web_id)),
				'name1' => $infos['title'],
				'url1'  => mk_url('main/info/view',  array('tid' => $infos['tid'], 'web_id' => $this->web_id, 'from' => 'web'))
			);
			$_static = array('topic' => 'info_frowardinfo_web', 'photo' => 'info_frowardpic_web', 'video' => 'info_frowardvideo_web', 
					'album' => 'info_frowardalbum_web');
			if (array_key_exists($type, $_static)) {
				service('Notice')->add_notice($this->web_id, $data['uid'], $replyAuthor, 'web', $_static[$type], $notice);
			}
		}
		unset($data, $infos);
		
		$this->ajaxReturn($result, '操作成功', 1);
	}
	
	/**
	 * 
	 * 评论
	 * @param integer $id
	 * @param integer $src_uid
	 * @param string  $object_type
	 * @param string  $content
	 */
	private function _addComment($id, $src_uid, $object_type, $content) {
		
		$data = array(
			'object_id'   => $id,
			'uid'         => $this->uid,
			'username'    => $this->username,
			'src_uid'     => $src_uid,
			'object_type' => $object_type,
			'usr_ip'      => get_client_ip(),
			'content'     => $content
		);
		return service('Comlike')->add_comment($data);
	}
	
	/**
	 * 
	 * 插入相册和照片的信息流数据
	 * @param integer $tid  对象ID
	 * @param string  $type 类型
	 * @return array  $infos 返回时间线上的数据
	 */
	private function _addAlbumData($tid, $type) {
		
		if (!$tid || !$type || !in_array($type, array('album', 'web_album', 'photo', 'web_photo'))) {
			return array();
		}
		$data = array();
		$album_type = 'album';
    	if ($type == 'web_album' || $type == 'web_photo') {
    		$album_type = 'walbum';
    	}
    	
    	$photoList = $picurls = array();
    	switch ($type) {
    		case 'album':
    		case 'web_album':
    			$aid = $tid;
    			$objData = service('Album')->getAlbumInfo($tid, $album_type, $this->uid);
    			
    			// 取得最新的8张照片
    			$photoList = service('Album')->getPhotoList($tid, $this->uid, 8);
    			break;
    		case 'photo':
    		case 'web_photo':
    			$objData = service('Album')->getPhotoInfo($tid, $album_type, $this->uid);
    			$aid = $objData ? $objData['aid'] : 0;
    			break;
    	}
    	
    	if (!$objData) {
    		return array();
    	}
    	
    	$infoflow_data = array(
    		'uid'        => $objData['uid'],
			'fid'        => $tid,
			'dkcode'     => $objData['dkcode'],
			'title'      => $objData['name'],
			'type'       => str_replace('web_', '', $type),
			'note'       => $aid,
    		'dateline'   => strstr($type, 'web_') ? date('YmdHis') : time(),
    		'uname'      => '',
			'content'    => '',
		);
		
		$tags = array();
		switch ($type) {
    		case 'album':
    			$infoflow_data['photonum'] = $objData['photo_count'];
    			$infoflow_data['uname'] = $objData['author'];
    			$infoflow_data['content']  = '<a href = ' . mk_url('album/index/photoLists', 
    				array('dkcode' => $objData['dkcode'], 'albumid' => $tid)) . ' >' . $objData['name'] . ' (' . $objData['photo_count']. ')</a>';
				$infoflow_data['permission'] = $objData['object_type'];
    			if($objData['object_type'] == -1) {
		    		$tags = explode(',', $objData['object_content']);
		    	}
    			break;
    		case 'photo':
    			$infoflow_data['uname'] = $objData['author'];
    			$infoflow_data['content'] = $objData['name'];
    			$infoflow_data['permission'] = 1;
    			$photoList[0] = array(
		        	'id'        => $objData['pid'], 
		        	'groupname' => $objData['group_name'], 
		        	'filename'  => $objData['filename'], 
		        	'type'      => $objData['type'], 
		        	'notes'     => $objData['notes']
		        );
    			break;
    		case 'web_album':
    			$infoflow_data['uname'] = $objData['author'];
    			$infoflow_data['pid'] = $objData['web_id'];
    			$infoflow_data['photonum'] = $objData['photo_count'];
    			$infoflow_data['timedesc'] = '';
    			$content = array(
					'm' => 'walbum',
					'c' => 'photo',
					'a' => 'index',
					'params' => array('albumid' => $tid, 'web_id' => $objData['web_id']),
					'title'  => $objData['name'].' ('.$objData['photo_count'].')'
				);
    			$infoflow_data['content'] = json_encode($content);
		        $tags = service('Interest')->get_web_category_imid($objData['web_id']);
    			break;
    		case 'web_photo':
    			$infoflow_data['uname'] = $objData['author'];
    			$infoflow_data['pid'] = $objData['web_id'];
    			$infoflow_data['timedesc'] = '';
    			$content = array(
					'm' => 'walbum',
					'c' => 'photo',
					'a' => 'index',
					'params' => array('albumid' => $objData['aid'], 'web_id' => $objData['web_id']),
					'title'  => $objData['name']
				);
    			$infoflow_data['content'] = json_encode($content);
    			$tags = service('Interest')->get_web_category_imid($objData['web_id']);
    			$photoList[0] = array(
		        	'id'        => $objData['pid'], 
		        	'groupname' => $objData['group_name'], 
		        	'filename'  => $objData['filename'], 
		        	'type'      => $objData['type'], 
		        	'notes'     => $objData['notes']
		        );
    			break;
		}
		
		// 如果没有照片的列表数据，则不能插入信息流
		if (empty($photoList)) {
			return array();
		}
		
		foreach ($photoList as $picval) {
    		$picurls[] = array(
	    		'pid'       => $picval['id'], 
	    		'groupname' => $picval['groupname'], 
	    		'filename'  => $picval['filename'], 
	    		'type'      => $picval['type'], 
	    		'size'      => empty($picval['notes']) ? array() : json_decode($picval['notes'], true)
    		);
    	}
    	
		$infoflow_data['picurl'] = json_encode($picurls);
		if (strstr($type, 'web_')) {
			$infos = service('WebTimeline')->addWebtopic($infoflow_data, $tags, false);
		} else {
			$infos = service('Timeline')->addTimeline($infoflow_data, $tags, false);
		}
		return $infos;
	}
	
	/**
	 * 通过网页ID获取网页的标签
	 * 
	 * @param int $pageid        	
	 */
	private function _getWebpageTagID($pageid) {
	      return service('Interest')->get_web_category_imid($pageid) ?  : array();
	}
	
	/**
	 * 请求分享信息
	 * 
	 */
	public function share_info(){
		$object_id    = intval($this->input->get('comment_ID'));//commentobjid
    	$object_type  = $this->input->get('pageType');//
    	$topic_type   = $this->input->get('topic_type');//type
    	$action_uid   = intval($this->input->get('action_uid'));//uid
		$web_id       = $this->input->get('web_id');
		$iserror = false;
		if(!$this->uid || !$object_id || !$object_type){
			$this->ajaxReturn('', '请求失败', 0);
		}
		
		$data = array();
		
		$data['web_list'] = $web_id ? $this->web_list($this->uid) : '';
		
		switch($object_type){
			case 'album':
				$album_info = service('Album')->getAlbumInfo($object_id, 'album', $action_uid);
				
				$data['isdel']         = $album_info ? 0 : 1;
				if($data['isdel']){
					$iserror = true;
					break;
				}
				$data['uid']           = $album_info['uid'];
				$data['username']      = $album_info['author'];
				$data['dkcode']        = $album_info['dkcode'];
				$data['title']         = $album_info['name'];
				$data['imgurl']        = $album_info['album_cover'];
				$data['type']          = 'album';
				$data['content']       = $album_info['discription'];
				$data['avatar'] = get_avatar($action_uid, 'mm');
				$data['author']      = mk_url('main/index/profile', array('dkcode' => $data['dkcode']));
				
				break;
			case 'web_album':
				$album_info = service('Album')->getAlbumInfo($object_id, 'walbum', $action_uid);
				
				$data['isdel']         = $album_info ? 0 : 1;;
				
				if($data['isdel']){
					$iserror = true;
					break;
				}
				$data['uid']           = $album_info['uid'];
				$data['username']      = $album_info['author'];
				$data['dkcode']        = $album_info['dkcode'];
				$data['title']         = $album_info['name'];
				$data['dateline']      = $album_info['cover_id'];
				$data['type']          = 'album';
				$data['content']       = $album_info['discription'];
				$data['web_id']        = $album_info['web_id'];
				$data['avatar'] = get_avatar($action_uid, 'mm');
				$data['imgurl']         = $album_info['album_cover'];
				$data['author']      = mk_url('main/index/profile', array('dkcode' => $data['dkcode']));
				
				break;
			case 'photo':
				$photo_info = service('Album')->getPhotoInfo($object_id, 'album', $action_uid);
				
				$data['isdel']         = $photo_info ? 0 : 1;;
				
				if($data['isdel']){
					$iserror = true;
					break;
				}
				
				$data['uid']           = $photo_info['uid'];
				$data['username']      = $photo_info['author'];
				$data['dkcode']        = $photo_info['dkcode'];
				$data['title']         = $photo_info['albumName'];
				$data['type']          = 'photo';
				$data['content']       = $photo_info['discription'];
				$data['imgurl']         = $photo_info['img_f'];
				$data['avatar'] = get_avatar($action_uid, 'mm');
				$data['author']      = mk_url('main/index/profile', array('dkcode' => $data['dkcode']));
				
				break;
			case 'web_photo':
				$photo_info = service('Album')->getPhotoInfo($object_id, 'walbum', $action_uid);
				
				$data['isdel']         = $photo_info ? 0 : 1;;
				
				if($data['isdel']){
					$iserror = true;
					break;
				}
				$data['uid']           = $photo_info['uid'];
				$data['username']      = $photo_info['author'];
				$data['dkcode']        = $photo_info['dkcode'];
				$data['title']         = $photo_info['albumName'];
				$data['dateline']      = '';
				$data['type']          = 'wphoto';
				$data['content']       = $photo_info['discription'];
				$data['ctime']         = '';
				$data['imgurl']        = $photo_info['img_f'];
				$data['web_id']        = $photo_info['web_id'];
				$data['avatar'] = get_avatar($action_uid, 'mm');
				$data['author']      = mk_url('main/index/profile', array('dkcode' => $data['dkcode']));
				
				break;
			case 'video':
				$video_info = service('Video')->getVideoInfo($object_id, 1, $action_uid);
				
				$data['isdel']         = $video_info ? 0 : 1;;
				
				if($data['isdel']){
					$iserror = true;
					break;
				}
				
				$data['uid']           = $action_uid;
				$data['username']      = $video_info['author'];
				$data['dkcode']        = '';
				$data['title']         = $video_info['title'];
				$data['dateline']      = '';
				$data['type']          = 'video';
				$data['content']       = $video_info['discription'];
				$data['ctime']         = '';
				$data['imgurl']        = $video_info['video_pic'] ? get_video_img($video_info['video_pic']) : '';
				$data['web_id']        = '';
				$data['url']           = mk_url('video/video/player_video', array('vid' => $object_id));
				$data['avatar'] = get_avatar($action_uid, 'mm');
				$data['author']      = mk_url('main/index/profile', array('dkcode' => $video_info['dkcode_webid']));
				
				break;
			
			case 'web_video':
				$video_info = service('Video')->getVideoInfo($object_id, 2, $action_uid);
				
				$data['isdel']         = $video_info ? 0 : 1;;
				
				if($data['isdel']){
					$iserror = true;
					break;
				}
				
				$data['uid']           = $action_uid;
				$data['username']      = $video_info['author'];
				$data['dkcode']        = '';
				$data['title']         = $video_info['title'];
				$data['dateline']      = '';
				$data['type']          = 'video';
				$data['content']        = $video_info['discription'];
				$data['ctime']         = '';
				$data['imgurl']         = $video_info['video_pic'] ? get_video_img($video_info['video_pic']) : '';
				$data['url']           = mk_url('wvideo/video/player_video', array('vid' => $object_id));
				$data['avatar'] = get_avatar($action_uid, 'mm');
				$data['author']      = mk_url('main/index/profile', array('web_id' => $video_info['dkcode_webid']));
				
			break;
			
			case 'topic':
				$share_fid = service('Comlike')->shareFid($object_id);

                if($share_fid){
                    $share_fid = $share_fid[0];
                } else {
                    $share_fid = $object_id;
                }
				$topic_info = service('Timeline')->getTopicByTid($share_fid);
				$data['isdel']         = $topic_info ? 0 : 1;
				
				if($data['isdel']){
					$iserror = true;
					break;
				}
				$data['uid']           = $topic_info['uid'];
				$data['username']      = $topic_info['uname'];
				$data['dkcode']        = $topic_info['dkcode'];
				$data['title']         = $topic_info['title'];
				$data['dateline']      = $topic_info['dateline'];
				$data['type']          = $topic_info['type'];
				$data['content']       = $topic_info['content'];
				$data['ctime']         = $topic_info['ctime'];
				$data['permission']    = $topic_info['permission'];
				$data['tid']           = $topic_info['tid'];
				$data['imgurl']         = '';
				$data['avatar'] = get_avatar($action_uid, 'mm');
				$data['author']      = mk_url('main/index/profile', array('dkcode' => $topic_info['dkcode']));
				//如果类型为单张照片取出相册的名字
				if($topic_info['type'] == 'photo'){
					$photo_id      = isset($topic_info['picurl'][0]->pid) ? $topic_info['picurl'][0]->pid : 0;
					$photo_info    = $photo_id ? service('Album')->getPhotoInfo($photo_id, 'album', $action_uid) : false;
					$data['title'] = $photo_info ? $photo_info['albumName'] : $data['title'];
					$data['imgurl']= isset($topic_info['picurl'][0]->pid) ? 'http://' . config_item('fastdfs_domain') . '/' .$topic_info['picurl'][0]->groupname   . '/' . $topic_info['picurl'][0]->filename . '.' . $topic_info['picurl'][0]->type : '';
				} elseif ( $topic_info['type'] == 'album' ){
					//如果类型相册，封面取第一张图片
					$data['imgurl']= isset($topic_info['picurl'][0]->pid) ? 'http://' . config_item('fastdfs_domain') . '/' .$topic_info['picurl'][0]->groupname   . '/' . $topic_info['picurl'][0]->filename . '.' . $topic_info['picurl'][0]->type : '';
				}
				break;
			case 'blog' :
				$blogData = service('UserPurview')->getBlogPurview(array($object_id => $action_uid), $action_uid);
				$user_info = service('User')->getUserInfo($blogData[$object_id][0]['uid'],$type='uid',array('dkcode', 'username'));
				
				$data['isdel']         = $user_info ? 0 : 1;;
				if($data['isdel']){
					$iserror = true;
					break;
				}
				
				$data['uid']           = $blogData[$object_id][0]['uid'];
				$data['username']      = $user_info['username'];
				$data['dkcode']        = $user_info['dkcode'];
				$data['title']         = $blogData[$object_id][0]['title'];
				$data['dateline']      = $blogData[$object_id][0]['dateline'];
				$data['type']          = 'blog';
				$data['content']       = mb_substr($blogData[$object_id][0]['resume'], 0, 150);
				$data['ctime']         = $blogData[$object_id][0]['dateline'];
				$data['tid']           = $blogData[$object_id][0]['id'];
				$data['url']           = mk_url('blog/blog/main', array('dkcode' => $user_info['dkcode'], 'id' => $object_id));
				$data['avatar'] = get_avatar($action_uid, 'mm');
				$data['author']      = mk_url('main/index/profile', array('dkcode' => $user_info['dkcode']));
				break;
			case 'web_topic':
				$topic_type = ($topic_type != 'info') ? $topic_type : '';
				$share_fid = service('Comlike')->shareFid($object_id, 'web_topic');
				if($share_fid){
                    $share_fid = $share_fid[0];
                } else {
                    $share_fid = $object_id;
                }
				$topic_info = service('WebTimeline')->getWebtopicByMap($share_fid,'', $this->web_id);
				
				$data['isdel']         = $topic_info ? 0 : 1;
				if($data['isdel']){
					$iserror = true;
					break;
				}
				
				$data['uid']           = $topic_info['uid'];
				$data['username']      = $topic_info['uname'];
				$data['dkcode']        = $topic_info['dkcode'];
				$data['title']         = $topic_info['title'];
				$data['dateline']      = $topic_info['dateline'];
				$data['type']          = $topic_info['type'];
				$data['content']       = $topic_info['content'];
				$data['ctime']         = $topic_info['ctime'];
				$data['tid']           = $topic_info['tid'];
				$data['imgurl']        = '';
				$data['avatar']        = get_webavatar($topic_info['pid']);
				
				$data['author']      = mk_url('webmain/index/main', array('web_id' => $topic_info['pid']));
				//如果类型为单张照片取出相册的名字
				if($topic_info['type'] == 'photo'){
					$photo_id      = isset($topic_info['picurl'][0]->pid) ? $topic_info['picurl'][0]->pid : 0;
					$photo_info    = $photo_id ? $photo_info = service('Album')->getPhotoInfo($photo_id, 'walbum', $action_uid) : false;
					$data['title'] =  $photo_info ? $photo_info['albumName'] : $data['title'];
				}
				
				break;
		}
		
		if($iserror){
			$this->ajaxReturn('', '数据传输失败。', 0);
		} else {
			$this->ajaxReturn($data, '', 1);
		}
	}
	
	/**
	 * 获取网页列表
	 * 
	 * @param $uid
	 */
	public function web_list($uid){
		$web_list = array();
		$web_data = service("Interest")->get_webs($uid) ? json_decode(service("Interest")->get_webs($uid), true) : false;
		
		if(is_array($web_data)){
			foreach($web_data AS $key => $val){
				$web_list[$key]['aid'] = $val['aid'];
				$web_list[$key]['name'] = $val['name'];
			}
		}
		
		return $web_list;
	}
}