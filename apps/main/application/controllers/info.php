<?php
/**
* [ Duankou Inc ]
* Created on 2012-3-7
* @author fbbin
* The filename : info.php   10:03:45
*/
class Info extends DK_Controller
{
	
	const PERMISION_CUSTOM = -1;
	const PERMISION_PUBLIC = 1;
	const TOPIC_FROM_INFO = 1;
	
	private $allowTypes = array('info', 'photo', 'video', 'sharevideo');
	
	/**
	 * construct
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('main');
	}

	/**
	 * @author fbbin
	 * @desc 时间线或者信息流发布数据操作方法
	 * @param 前端需要提交的参数列表：
	 * @param content 用户填写的内容
	 * @param type 当前数据的格式:info/album/video
	 * @param timestr 选择的发布时间:格式：2012-03-02 08:15:54
	 * @param permission 当前数据实体设置的权限(int)
	 */
	public function doPost()
	{
		$data = array('uid'=>$this->uid, 'uname'=>$this->username, 'dkcode'=>$this->dkcode, 'from'=>self::TOPIC_FROM_INFO, 'dateline'=>time());
		//数据类型:info/album/video
		$data['type'] = P('type');
		if( !in_array($data['type'], $this->allowTypes) )
		{
			return $this->dump( L('unknow_style_content'));
		}
		//内容处理
		$data['content'] = preg_replace('/\s+/', ' ', P('content'));
		if( ($data['content'] == '' && $data['type'] == 'info') || filter($data['content'], 2))
		{
			return $this->dump( L('message_error') );
		}
		$data['content'] = autoLink( msubstr($data['content'], 0, 140, 'utf-8', false) );
		if(function_exists('faceReplace')) {
		    $data['content'] = faceReplace($data['content']);
		}else {
			$data['content'] = $this->faceReplace($data['content']);
		}
		$data['title'] = $data['content'];
		$data['ctime'] = preg_replace_callback('/(?P<year>\d{4})(-?)(?P<mon>\d{0,2})(-?)(?P<day>\d{0,2})(-?)(?P<hou>\d{0,2})(-?)(?P<min>\d{0,2})(-?)(?P<sec>\d{0,2})/', function ($match)
		{
			!$match['mon'] && $match['mon'] = 1;
			!$match['day'] && $match['day'] = 1;
			!$match['hou'] && $match['hou'] = date('H');
			!$match['min'] && $match['min'] = date('i');
			!$match['sec'] && $match['sec'] = date('s');
			return mktime($match['hou'], $match['min'], $match['sec'], $match['mon'],$match['day'],$match['year']);
		}, P('timestr')?:date('Y-m-d-H-i-s') );
		$parseMethod = '_parse'.ucfirst($data['type']).'Data';
		try {
			$data = array_merge($data, $this->$parseMethod());
		} catch(Exception $e) {
			$this->dump( L($e->getMessage()));
		}
		$data['permission'] = P('permission');
		if( ! $data['permission'] )
		{
			return $this->dump(L('unenable_permission'));
		}
		//自定义情况下处理成员列表
		$relations = array();
		if( ! in_array($data['permission'], array(1,3,4,8)) )
		{
			$relations = explode(',', $data['permission']);
			$data['permission'] = self::PERMISION_CUSTOM;
			if( empty($relations) )
			{
				return $this->dump(L('relations_empty'));
			}
		}

		$result = service('Timeline')->addTimeline($data,$relations);
		if( $result === false )
		{
			return $this->dump(L('operation_fail'));
		}
		$data['permission'] == self::PERMISION_PUBLIC && service('RelationIndexSearch')->addOrUpdateStatusInfo(json_encode($result));
		if ($data['type'] == 'info') {
			service('credit')->do_status();  //添加发状态积分  Devin Yee
		}
		unset($data);
		return $this->dump(L('operation_success'), true, array('data'=>$result));
	}
	
	/**
	 * @author fbbin
	 * @desc 时间线或者信息流转发数据操作方法
	 * @param 前端需要提交的参数列表：
	 * @param content 用户填写的内容
	 * @param tid 当前信息实体
	 * @param fid 原信息实体
	 * @param reply_author 是否评论给原作者 UID
	 * @param reply_now 是否评论给当前作者 UID
	 */
	public function doShare()
	{
		$this->load->model('TimelineModel');
		$data = array('uid'=>$this->uid, 'dkcode'=>$this->dkcode, 'uname'=>$this->username,
						'type'=>'forward', 'dateline'=>time(), 'from'=>self::TOPIC_FROM_INFO, 'ctime'=>time());
		
		//当前信息实体ID
		$tid = intval(P('tid'));
		
		//如若当前信息实体被转发了两次或者以上，那么就获取原实体ID，否则就为当前ID
		$fid = intval(P('fid'));
		
		//是否评论给原作者
		$replyAuthor = strtolower( P( 'reply_author' ) );
		
		//是否评论给当前作者
		$replyNow = strtolower( P( 'reply_now' ) );
		if( !($fid && $tid) ) {
			$this->ajaxReturn('', '参数错误', 0);
		}
		
		if ( $tid === $fid ) {
			$replyAuthor = $replyNow;$tid = $replyNow = false;
		}
		$data['fid'] = $fid;
		$infos = $this->TimelineModel->getTopic( $data['fid'] );
		if( ! $infos ) {
			$this->ajaxReturn(array('tid'=>false), '数据错误', 0);
		}
		
		$data['permission'] = $infos['permission'];
		$data['content'] = P('content');
		$data['title'] = isset($infos['title']) ? $infos['title'] : '';
		$result = service('Timeline')->addTimeline($data);
		if ( $result === false ) {
			$this->ajaxReturn('', '操作错误', 0);
		}
		
		//===========添加到转发列表===========
		$params = array('uid' => $data['uid'], 'content' => $data['content']);
       	service('Share')->add('topic',$fid,$tid,$result['tid'],$params);
       	service('Timeline')->updateTopicHot($fid,1);
		
       	//===========评论给原作者=============
      	$type = $infos['type'] != 'album' ? ($infos['type'] == 'info' ? 'topic' : $infos['type']): ($infos['photonum'] > 1 ? 'album' : 'photo');
		if( $replyAuthor && $fid ) {
			if( $type == 'topic' || $type == 'sharevideo') {
				$objectId = $infos['tid'];
			} elseif( $type == 'photo' ) {
				$picurl = json_decode($infos['picurl'], true);
				$objectId = $picurl['0']['pid'];
			} elseif($type == 'album') {
				$objectId = $infos['note'];
			} else {
				$objectId = $infos['fid'];
			}
			$replyData = array('object_id'=>$objectId,'uid'=>$data['uid'],
								'username'=>$data['uname'],'src_uid'=>$replyAuthor,
								'object_type'=>$type,'usr_ip'=>get_client_ip(),
								'content'=>$data['content']);
			service('Comlike')->add_comment($replyData);
			unset($replyData);
		}
		
		//===========评论给当前作者===========
		if( $replyNow && $tid ) {
			$replyNowData = array('object_id'=>P('object_id'),'uid'=>$data['uid'],
								'username'=>$data['uname'],'src_uid'=>$replyNow,
								'object_type'=>'forward','usr_ip'=>get_client_ip(),
								'content'=>$data['content']);
			service('Comlike')->add_comment($replyNowData);
			unset($replyNowData);
		}
		
		//===========添加转发通知==============
		if ( ! $replyAuthor ) {
			$replyAuthor = $infos['uid'];
		}
		
		// 自己分享自己的信息不发送通知
		if ($replyAuthor != $this->uid) {
			if( $type == 'topic' || $type == 'photo') {
				$_static = array('topic'=>'info_frowardinfo','photo'=>'info_frowardpic');
			    service('Notice')->add_notice(1,$data['uid'],$replyAuthor,'info',$_static[$type],array('name'=>@$result['title'],'url'=>mk_url('main/info/view',array('tid'=>$result['tid']))));
			}else if( $type == 'video' || $type == 'album' ) {
				$_static = array('video'=>'info_frowardvideo','album'=>'info_frowardalbum');
			    service('Notice')->add_notice(1,$data['uid'],$replyAuthor,'info',$_static[$type],array('name'=>@$result['title'],'url'=>mk_url('main/info/view',array('tid'=>$result['tid']))));
			}
		}
		unset($data, $infos);
		
		//-------------------------------------关注操作时间接口start----------------------
    	service('Relation')->updateFollowTime($this->uid, $replyAuthor);
    	//-------------------------------------关注操作时间接口end------------------------
		$this->ajaxReturn($result, '操作成功', 1);
	}
	
	/**
	 * @author fbbin
	 * @desc 移动时间轴上面的信息实体
	 * @param 前端需要提交的参数列表：
	 * @param tid 信息实体的TID
	 * @param timeStr 选择的发布时间:格式：2012-3-2
	 */
	public function doSetCtime()
	{
		$tid = (int)P('tid');
		$this->_checkPermission($tid);
		$newCtime = preg_replace_callback('/(?P<year>\d{4})(-?)(?P<mon>\d{0,2})(-?)(?P<day>\d{0,2})(-?)(?P<hou>\d{0,2})(-?)(?P<min>\d{0,2})(-?)(?P<sec>\d{0,2})/', function ($match)
		{
			!$match['mon'] && $match['mon'] = 1;
			!$match['day'] && $match['day'] = 1;
			!$match['hou'] && $match['hou'] = date('H');
			!$match['min'] && $match['min'] = date('i');
			!$match['sec'] && $match['sec'] = date('s');
			return mktime($match['hou'], $match['min'], $match['sec'], $match['mon'],$match['day'],$match['year']);
		}, P('timeStr')?:date('Y-m-d-H-i-s',SYS_TIME) );
		$callStatus = service('Timeline')->updateTimeline($tid,$newCtime);
		service('Comlike')->update_Like($tid,'info',$newCtime);
		unset($tid, $newCtime, $timeStr);
		return $callStatus ? $this->dump( L('operation_success'), true ) : $this->dump( L('operation_fail') );
	}

	/**
	 * @author fbbin
	 * @desc 删除一条信息实体
	 * @param tid 信息实体ID
	 */
	public function doDelTopic() {
		$tid = (int)P('tid');
		$delOld = (bool)P('isDelOld');
		$permanentDel = !$delOld;
		$topic = $this->_checkPermission( $tid );
		service('Comlike')->delObject(array('object_id'=>$tid,'object_type'=>'topic'));
		$delStatus = service('Timeline')->removeTimeLine($tid, $this->uid, '', $permanentDel);
		//删除转发列表的数据
		if($topic['type'] == 'forward') {
			service('Share')->del('topic',$tid);
		}
		//删除原始数据
		if($delOld) {
			switch ($topic['type']) {
				case 'photo':
				case 'album':
					$pics = json_decode($topic['picurl'], true);
					if($pics) {
						foreach($pics as $pic) {
							$pic['pid'] and service('Album')->deletePhoto($pic['pid'], $this->uid);
						}
					}
					break;
				case 'video':
					$topic['fid'] and service('Video')->delVideoApi('video', $this->uid, $topic['fid']);
					break;
				case 'ask':
					$topic['fid'] and service('Ask')->delAsk($topic['fid'], $this->uid);
					break;
				case 'event':
					$topic['fid'] and api('Event')->delEvent($topic['fid'], $this->uid);
					break;
				case 'blog':
					$topic['fid'] and api('Blog')->delBlog($topic['fid']);
					break;
			}
		}
		//更新索引
		service('RelationIndexSearch')->deleteStatus($tid);
		if ($topic['type'] == 'info') {
			service('credit')->do_status(false);  //删除状态  减积分 Devin Yee
		}
		unset($tid);
		return $delStatus ? $this->dump( L('operation_success'), true ) : $this->dump( L('operation_fail') );
	}

	/**
	 * @author fbbin
	 * @desc 设置一个信息实体突出显示
	 * @param tid 信息实体ID
	 * @param heightlight 1/0
	 */
	public function doUpdateHeightlight()
	{
		$tid = (int)P('tid');
		$this->_checkPermission( $tid );
		$highlight = P('highlight');
		$updateStatus = service('Timeline')->updateTimelineHighlight($tid,$highlight);
		unset($tid, $highlight);
		return $updateStatus ? $this->dump( L('operation_success'), true ) : $this->dump( L('operation_fail') );
	}
	
	/**
	 * @author fbbin
	 * @desc 更新信息的权限
	 * @param tid 信息实体ID
	 * @param permission int/string
	 */
	public function doUpdatePermission()
	{
		$tid = (int)P('object_id');
		$topic = $this->_checkPermission($tid);
		$permission = P('permission');
		if( $topic['permission'] == $permission )
		{
			return $this->dump(L('operation_not_change'), true, array('state'=>1));
		}
		$relations = array();
		if( ! in_array($permission, array(1,3,4,8)) )
		{
			$relations = explode(',', $permission);
			$permission = self::PERMISION_CUSTOM;
			if( empty($relations) )
			{
				return $this->dump(L('relations_empty'));
			}
		}
		$updateStatus =service('Timeline')->updatePermission($tid,$permission,$relations); 
		unset($relations);
		if( $permission == self::PERMISION_PUBLIC )
		{
			service('RestorationSearch')->restoreStatusInfo(array('id'=>$tid,'type'=>'0'));
		}else
		{
			service('RelationIndexSearch')->deleteStatus($tid);
		}
		return $updateStatus ? $this->dump( L('operation_success'), true ,array('state'=>1)) : $this->dump( L('operation_fail'), false, array('state'=>false) );
	}
	
	/**
	 * @author fbbin
	 * @desc 检测是否有操作权限
	 * @param int $tid
	 */
	private function _checkPermission( $tid )
	{
		if( ! $tid )
		{
			$this->dump( L('err_topic_id') );
		}
		$this->load->model('TimelineModel');
		$topic = $this->TimelineModel->getTopic( $tid );
		if( ! $topic )
		{
			$this->dump( L('err_topic_id') );
		}
		if( $topic['uid'] != $this->uid )
		{
			$this->dump(L('permission_denied'));
		}
		return $topic;
	}
	
	/**
	 * @author yingxiaobin
	 * @param $tid
	 */
	public function view()
	{
		$tid = (int)$_GET['tid'];
		
		if (empty($tid)) {
			$this->assign('msg', array('没有相关的数据'));
			$this->assign('url', mk_url('main/index/main'));
			$this->display('error');
			exit();
		}
         
		// 登录者的用户信息
        $login_info['avatar_url'] = get_avatar($this->uid);
        $login_info['uid']    = $this->uid;
        $login_info['username'] = $this->user['username'];
        $login_info['url']    = mk_url('main/index/main', array('dkcode' => $this->dkcode));
        $this->assign('login_info',$login_info);
        
        $params['webId'] = isset($_GET['web_id']) ? (int)$_GET['web_id'] : '';
        $params['tid'] = (int)$_GET['tid']; 
        $params['from'] = isset($_GET['from']) ? $_GET['from'] : '';
        $this->assign('params', $params);
        $this->assign('fdfsinfo',config_item('fastdfs_domain'));
        /*   视频显示地址  */
        $this->config->load("video");
        /*   end */
        //$this->assign('fdfsinfo', array('host'=>$this->config->item('fastdfs_host'),'group'=>$this->config->item('fastdfs_group')));
		$this->display('timeline/comment_show');
	}
	
	
	public function ajaxView()
	{
		$tid = (int)$_POST['tid'];

		if (empty($tid)) {
			toJSON(array('status' => 0, 'msg' => '传递参数不正确'));
		}
		
		$type = (isset($_POST['from']) && $_POST['from'] == 'web') ? 'webtopic:' : 'topic:';
		$this->load->model('TimelineModel');
		$auth = false; //是否具有权限
		$topic = $this->TimelineModel->getTopicByKey($type . $tid);
		if (!empty($topic)) {
			if ($topic['type'] == 'forward') {
				$topic['forward'] = $this->TimelineModel->getTopicByKey($type . $topic['fid']);

				if ($topic['forward'] && $topic['forward']['type'] == 'album') {
					$topic['forward']['picurl'] = json_decode($topic['forward']['picurl']);
				} 
				if ($topic['forward'] && $topic['forward']['type'] == 'video') {
				
					$topic['forward']['imgurl'] = config_item('video_pic_domain') . $topic['forward']['imgurl'];
				}
				if ($topic['forward'] && $topic['forward']['type'] == 'photo') {
					$topic['forward']['picurl'] = json_decode($topic['forward']['picurl']);
				}
				
				// 设置下原信息用户头像
				if ($type == 'Webtopic:') {
					$webId = (int)$_POST['web_id'];
					$topic['user_avartar'] = get_webavatar( $webId, 's');
					$topic['web_home'] = mk_url('webmain/index/main',array('web_id'=>$webId));
				} else {
					$topic['user_avartar'] = get_avatar($topic['uid']);
				}
				
				
				// 判断这条信息的转发源是当前用户的

				if ($topic['uid'] == $this->uid || (isset($topic['forward']['uid']) && $topic['forward']['uid'] == $this->uid)) {
					$auth = true;
				}
				if(isset($topic['forward']) && empty($topic['forward'])) {
					$auth = true;
					$topic['forward'] = '原信息已删除';
				}

			} else {
				// 判断这条信息源是不是当前用户的
				if ($topic['uid'] == $this->uid) {
					$auth = true;
				}

				
				// 设置下原信息用户头像
				if ($type == 'Webtopic:') {
					$webId = isset($_POST['web_id']) ? (int)$_POST['web_id'] : 0;
					$topic['user_avartar'] = get_webavatar($webId, 's');
					$topic['web_home'] = mk_url('webmain/index/main',array('web_id'=>$webId));
				} else {
					$topic['user_avartar'] = get_avatar($topic['uid']);
				}
				
				if ($topic['type'] == 'album') {
					$topic['picurl'] = json_decode($topic['picurl']);
				}
				if ($topic['type'] == 'photo') {
					$topic['picurl'] = json_decode($topic['picurl']);
				}
				
				if ($topic['type'] == 'video') {
					$topic['videourl'] = config_item('video_pic_domain') . $topic['videourl'];
					$topic['imgurl'] = config_item('video_src_domain') . $topic['imgurl'];
				}
				if ($topic['type'] == 'ask') {
					$result = service('Ask')->timelineAskData($topic['fid'],$this->uid);
					$topic['ask'] = $result;
				}
				if ($topic['type'] == 'event') {
					$topic['starttime'] = date('Y-n-j H:i',$topic['starttime']);
				}
			}
			
			if ($auth) {
				$this->load->helper('timeline');
				$topic['friendly_time'] = makeFriendlyTime($topic['ctime']);
				toJSON(array('status' => 1, 'data' => $topic));
			} else {
				toJSON(array('status' => 0, 'msg' => '没有浏览权限'));
			}
		} else {
			toJSON(array('status' => 0, 'msg' => '该信息已被删除'));
		}
	}
	
	/**
	 * @author fbbin
	 * @desc 解析信息流数据
	 */
	private function _parseInfoData()
	{
		return array();
	}
	
	/**
	 * @author fbbin
	 * @desc 解析照片的数据
	 * @param 相册数据类型额外的参数列表：
	 * @param fid 相册的ID
	 * @param pid 相片的PID
	 * @param picurl 大图地址
	 */
	private function _parsePhotoData()
	{
		$album['fid'] = P('fid');//以时间戳为相册ID
		$picurl = unserialize(base64_decode(P('picurl')));//相片的JSON数据
		$album['picurl'] = json_encode($picurl);
		$album['type'] = count($picurl) > 1 ? 'album' : 'photo';
		count($picurl) > 1 && $album['photonum'] = count($picurl);
		$album['note'] = P('note');//真实的相册ID
		return $album;
	}
	
	/**
	 * @author fbbin
	 * @desc 解析视频的数据
	 * @param 视频类型数据额外参数列表
	 * @param vid 视频ID
	 * @param videourl 视频资源地址
	 * @param imgurl 视频截图地址
	 * @param url 视频在视频模块中的链接地址
	 */
	private function _parseVideoData()
	{
		$video['fid'] = P('vid');
		$video['imgurl'] = P('imgurl');
		$video['width'] = P('width');
		$video['height'] = P('height');
		return $video;
	}

	/**
	 * 分享视频数据解析
	 * @author xwsoul
	 */
	private function _parseShareVideoData() {
		$video['videourl'] = P('videourl');
		if(!is_url($video['videourl'])) {
			throw new Exception('videourl_invalid');
		}
		$video['imgurl'] = P('imgurl');
		if(!is_url($video['imgurl'])) {
			throw new Exception('imgurl_invalid');
		}
		$video['url'] = P('url');
		if(!is_url($video['url'])) {
			throw new Exception('url_invalid');
		}
		return $video;
	}
	
	/**
	 * @author fbbin
	 * @desc 对输出进行控制
	 * @param array/string $info
	 * @param bool $status
	 * @param array $extra
	 */
	private function dump($info = '', $status = false, $extra = array())
	{
		if( is_string( $info ) )
		{
			$data = array('data'=>array(), 'status'=>(int)$status, 'info'=>$info);
		}
		elseif( is_array( $info ) )
		{
			$data = $info;
		}
		if( !empty($extra) )
		{
			$data = array_merge($data, $extra);
		}
		exit( json_encode( $data ) );
	}
	/**
	 * @author 周天良
	 * @param string $str 要替换的字符串
	 * @param string $packagepath 表情包路径
	 */
	private function faceReplace($str,$packagepath='') {
		$facePackage = $this->faceArray();
		
		$facePackage=array_flip($facePackage);
	    if(preg_match_all('#\[.+?\]#', $str, $arr)) {
	    	if($arr[0] && isset($arr[0])) {
	    	    foreach($arr[0] as $k => $v){
					if(isset($facePackage[$v]))
					{
						$i = $facePackage[$v];
						$i = $i + 1;
						$str=str_replace($v,"<img src=\"{$packagepath}/{$i}.gif\" />", $str);
					}
				}
	    	}
	    }
        return $str;
	}

	private function faceArray() {
	
		return array('[微笑]','[撇嘴]','[色]','[发呆]','[大哭]','[害羞]','[闭嘴]','[睡]','[发怒]','[调皮]','[呲牙]','[难过]','[冷汗]','[吐]','[可爱]',
				'[饿]','[白眼]','[傲慢]','[困]','[惊恐]','[汗]','[憨笑]','[疑问]','[晕]','[折磨]','[抠鼻]','[坏笑]','[鄙视]','[委屈]','[快哭了]',
				'[亲亲]','[阴险]','[吓]','[囧]','[可怜]','[生气]','[财迷]','[惊]','[冰冻]','[石化]'/*,'[擦汗]','[抠鼻]','[鼓掌]','[糗大了]','[坏笑]',
				'[左哼哼]','[右哼哼]','[哈欠]','[鄙视]','[委屈]','[快哭了]','[阴险]','[亲亲]','[吓]','[可怜]','[菜刀]','[西瓜]','[啤酒]','[篮球]','[乒乓]',
				'[咖啡]','[饭]','[猪头]','[玫瑰]','[凋谢]','[示爱]','[爱心]','[心碎]','[蛋糕]','[闪电]','[炸弹]','[刀]','[足球]','[瓢虫]','[便便]',
				'[月亮]','[太阳]','[礼物]','[拥抱]','[强]','[弱]','[握手]','[胜利]','[抱拳]','[勾引]','[拳头]','[差劲]','[爱你]','[NO]','[OK]',
				'[爱情]','[飞吻]','[跳跳]','[发抖]','[怄火]','[转圈]','[磕头]','[回头]','[跳绳]','[挥手]','[激动]','[街舞]','[献吻]','[左太极]','[右太极]',
		*/);
	
	}

	public function createFace() {
		$str = get_cache('faceContent');
		//$str = '';
		if(empty($str) || $str==null) {
		    $arr = $this->faceArray();
	
	        $str = '<div class="face-tab-hd"><ul><li class="selected"><a href="">默认表情</a></li></ul><a class="face-overlay-close" title="关闭" href=""></a></div><div class="face-bd"><div class="face-bd-box face-default">';
			foreach ($arr as $key=>$one) {
				$i = $key + 1;
			    $str.="<a href=\"#\" title=\"".trim($one,"[]")."\" alt=\"{$i}\"></a>";
			}
	        $str.="</div></div>";
	        set_cache('faceContent', $str);
		}
        $json=array('data'=>$str,'status'=>1);
	    
		die(json_encode($json));
	}
	
}

?>
