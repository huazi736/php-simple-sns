<?php
/**
* [ Duankou Inc ]
* Created on 2012-3-7
* @author fbbin
* The filename : Web.php   10:03:45
*/
class Web extends DK_Controller {

	// 标识来源于网页
	const TOPIC_FROM_WEB = 3;
	// 转发网页中的数据权限为公开
	const PERMISSION_PUBLIC = 1;

	private $allowTypes = array(
			'photo',
			'info',
			'video',
			'event',
			'goods',
	);

	/**
	 * construct
	 */
	public function __construct() {
		parent::__construct();
		$this->load->helper('webmain');
	}

	public function test() {

	}

	public function loadPostbox() {
		// 所有者鉴权
		$webOwner = $this->web_info['uid'];
		if ($webOwner !== $this->uid) {
			return $this->dump(L('not_page_ownner'));
		}
		$tpl = $this->input->get('page');

        $contents = '';
        if ($tpl) {
            ob_start();
            include_once APPPATH.'views/timeline/'.$tpl.'.html';
            $contents = ob_get_clean();
        }

//		$filename = APPPATH.'views/timeline/'.$tpl.'.html';
//
//		$handle = fopen($filename, 'r');
//		$contents = '';
//		while(!feof($handle)) {
//			$contents .= fgetc($handle);
//		}
//		fclose($handle);

		echo $this->dump(L('page_success'), true, array('data'=>$contents));
	}

	/**
	 * 网页发布数据操作方法
	 *
	 * @author fbbin
	 * @param 前端需要提交的参数列表：
	 * @param content 用户填写的内容
	 * @param type 当前数据的格式:info/album/video/event...
	 * @param timestr 选择的发布时间:格式：2012-3-2
	 * @param bc 公元前后表示：1（前），-1（后）
	 * @param timedesc 时间描述信息
	 * @param web_id 当前网页的ID(int)
	 */
	public function doPost() {
		// 所有者鉴权
		$webOwner = $this->web_info['uid'];
		if ($webOwner !== $this->uid) {
			return $this->dump(L('not_page_ownner'));
		}

		$data = array(
				'uid' => $this->uid,
				'dkcode' => $this->dkcode,
				'uname' => $this->web_info['name'],
				'from' => self::TOPIC_FROM_WEB,
				'pid' => WEB_ID,
				'dateline' => date('YmdHis', SYS_TIME)
		);
		$data['type'] = P('type');

		if (!in_array($data['type'], $this->allowTypes)) {
			return $this->dump(L('unknow_style_content'));
		}
		// 内容处理
		$data['content'] = preg_replace('/\s+/', ' ', P('content'));

		if (($data['content'] == '' && $data['type'] == 'info') || filter($data['content'], 2))
		{
			return $this->dump(L('message_error'));
		}

		// 自动为地址添加链接
		$data['content'] = autoLink(msubstr($data['content'], 0, 500, 'utf-8', false));

		$data['timedesc'] = P('timedesc');
		$data['ctime'] = preg_replace_callback('/(?P<year>\d+)(-?)(?P<mon>\d{0,2})(-?)(?P<day>\d{0,2})/', function ($match) {
			(int)$match['mon'] < 10 && !($match['mon'] = '0' . $match['mon']) && $match['mon'] = '01';
			(int)$match['day'] < 10 && !($match['day'] = '0' . $match['day']) && $match['day'] = '01';
			return $match['year'] . min($match['mon'], 12) . min($match['day'], 31);
		}, P('timestr') ?  : date('Y-n-j', SYS_TIME));
		// 公元前
		$bc	= P('bc');
		$bc==0 && $bc = 1;
		if ( $bc < 1)
			$data['ctime'] = '-' . $data['ctime'] . '000000';
		// 公元后
		else
			$data['ctime'] = $data['ctime'] . ($data['ctime'] == date('Ymd', SYS_TIME) ? date('His', SYS_TIME) : '000000');

		$data['title'] = $data['content'];
		$parseMethod = '_parse' . ucfirst($data['type']) . 'Data';
		$methodData = $this->$parseMethod($data, $this->web_info);
		if($methodData === false) {
			return $this->dump(L('operation_fail'));
		}
		$data = array_merge($data, $methodData);
		$result = service('WebTimeline')->addWebtopic($data,$this->getWebpageTagID($data['pid']));
		if ($result === false) {
			return $this->dump(L('operation_fail'));
		}
		$result = $this->resultHanler($result);
		service('RelationIndexSearch')->addOrUpdateStatusInfo(json_encode($result));
		//蛋疼的时间线带动时间轴,导致年份的蛋疼请求 add by xwsoul
		$getYear = (bool)P('isRequestMonth');
		$months = array();
		if($getYear) {
			$this->load->model('TimelineModel');
			$year = substr($data['ctime'], 0, 4);
			$months = $this->TimelineModel->getYearHottestFeeds(WEB_ID, $year);
			$months = $months['months'];
		}
		$result['months'] = $months;
		if($data['type']=='goods'){	// 商品这里必段是 jsonp
			$this->ajaxReturn($result,'',1,'json');	// 商品这里必段是 jsonp
		}else{
			return $this->dump(L('operation_success'), true, array(
					'data' => $result
			));
		}
	}

	private function resultHanler($res) {
		$fastdfs_domain	= config_item('fastdfs_domain');

		$this->fastdfs_url= 'http://'.$fastdfs_domain.'/';

		if ($res['type'] == 'groupon' && isset($res['groupon'])) {
			$groupon = json_decode($res['groupon'], true);
			$diff = $groupon['expiretime'] - time();
			$res['diff'] = $diff > 0 ? $diff : 0;
			$res['groupon'] = $groupon;
		} else if($res['type'] == 'goods' && isset($res['goods'])) {
			$goods = json_decode($res['goods'], true);

			$goods['img'] = array_map( array($this,"create_url") , $goods['img']);
			$goods['thumb'] = array_map( array($this,"create_url") , $goods['thumb']);
			$res['goods'] = $goods;
		}
		return $res;
	}


	public function create_url($v){
		return $this->fastdfs_url.$v;
	}


	/**
	 * 返回用户拥有的网站,没有网站返回空数组
	 *
	 * @author xwsoul
	 * @param null
	 */
	public function getWebs() {
		$webs = json_decode(service('interest')->get_webs($this->uid));
		$data = array();
		if (!empty($webs)) {
			foreach ( $webs as $web ) {
				$data[$web->aid] = $web->name;
			}
		}
		return $this->dump($data, 1);
	}

	public function showPostBoxList() {
		$this->load->model('publish_tplmodel');

		$this->assign('web_info', $this->web_info);
		$this->display('timeline/postBoxTempList');
	}

	/**
	 * 网页中转发数据到时间线操作方法
	 *
	 * @author fbbin, xwsoul
	 * @param 前端需要提交的参数列表：
	 * @param content 用户填写的内容
	 * @param tid 当前信息实体
	 * @param fid 原信息实体
	 * @param reply_author 是否评论给原作者 UID
	 * @param reply_now 是否评论给当前作者 UID
	 */
	public function doShare() {
		// 所有者鉴权
		$webOwner = $this->web_info['uid'];
		if ($webOwner !== $this->uid) {
			return $this->dump(L('not_page_ownner'));
		}
		$this->load->model('TimelineModel');
		$time = date('YmdHis', SYS_TIME);
		$data = array(
				'uid' => $this->uid,
				'dkcode' => $this->dkcode,
				'pid' => WEB_ID,
				'uname' => $this->web_info['name'],
				'type' => 'forward',
				'dateline' => $time,
				'from' => self::TOPIC_FROM_WEB,
				'ctime' => $time
		);
		// 当前信息实体ID
		$tid = (int)P('tid');
		// 如若当前信息实体被转发了两次或者以上，那么就获取原实体ID，否则就为当前ID
		$fid = (int)P('fid');
		// 是否评论给原作者
		$replyAuthor = strtolower(P('reply_author'));
		// 是否评论给当前作者
		$replyNow = strtolower(P('reply_now'));
		if (!($fid && $tid)) {
			return $this->dump(L('err_topic_id'), false, array(
					'tid' => false
			));
		}
		if ($tid === $fid) {
			$replyAuthor = $replyNow;
			$tid = $replyNow = false;
		}
		$data['fid'] = $fid;
		$infos = $this->TimelineModel->getTopic($data['fid']);
		if (!$infos) {
			return $this->dump(L('topic_not_exist'));
		}
		$data['content'] = P('content');
		$data['title'] = isset($infos['title']) ? $infos['title'] : '';
		if (empty($data['content'])) {
			// $data['content'] = L('forward_weibo');
			$data['content'] = '转发';
		}

		// 从网页转发到自己的时间线
/* 		$result = $this->call(array(
				$data,
				$this->getWebpageTagID($data['pid'])
		)); */
		$result = service('WebTimeline')->addWebtopic($data,$this->getWebpageTagID($data['pid']));
		$result = json_decode($result, true);
		if ($result === false) {
			return $this->dump(L('operation_fail'));
		}

		// ===========添加到转发列表===========
		$params = array(
				'uid' => $data['uid'],
				'content' => $data['content']
		);
		/* call_soap('comlike', 'Share', 'add', array('web_topic',
				$fid,
				$result['tid'],
				$params
		 ));*/
		service('Share')->add('web_topic',$fid,$result['tid'],$params);
/* 		$this->call(array(
				$fid,
				1
		), 'updateWebtopicHot'); */
		service('WebTimeline')->updateWebtopicHot($fid,1);

		/*
		 * 暂不评论 //===========评论给原作者=========== $type = $infos['type'] != 'album'
		 * ? $infos['type'] : (count($infos['photonum']) > 2 ? 'album' :
		 * 'photo'); if( $replyAuthor && $fid ) { //调用接口评论给原作者 $replyData =
		 * array( 'object_id'=>P('object_id'), 'uid'=>$data['uid'],
		 * 'username'=>$data['uname'], 'src_uid'=>$replyAuthor,
		 * 'object_type'=>'web_topic', 'usr_ip'=>get_client_ip(),
		 * 'content'=>$data['content'] ); call_soap('comlike', 'Index',
		 * 'add_comment', array($replyData)); unset($replyData); }
		 * //===========评论给当前作者=========== if( $replyNow && $tid ) {
		 * $replyNowData = array( 'object_id'=>P('object_id'),
		 * 'uid'=>$data['uid'], 'username'=>$data['uname'],
		 * 'src_uid'=>$replyNow, 'object_type'=>'web_topic',
		 * 'usr_ip'=>get_client_ip(), 'content'=>$data['content'] );
		 * call_soap('comlike', 'Index', 'add_comment', array($replyNowData));
		 * unset($replyNowData); }
		 */

		//===========添加到转发列表===========
		$params = array('uid' => $data['uid'], 'content' => $data['content']);
		//call_soap('comlike','Share', 'add', array('web_topic', $fid, $result['tid'], $params));
		service('Share')->add('web_topic',$fid,$result['tid'],$params);

/* 		$this->call(array($fid, 1), 'updateWebtopicHot');
 */
		service('WebTimeline')->updateWebtopicHot($fid,1);
		//===========评论给原作者===========
		$type = $infos['type'] != 'album' ? $infos['type'] : (count($infos['photonum']) > 2 ? 'album' : 'photo');

		if( $replyAuthor && $fid ) {
			//调用接口评论给原作者
			$replyData = array(
				'object_id'=>$fid,
				'uid'=>$this->uid,
				'username'=>$this->username,
				'src_uid'=>$replyAuthor,
				'object_type'=>'web_topic',
				'usr_ip'=>get_client_ip(),
				'content'=>$data['content']
			);
			//call_soap('comlike', 'Index', 'add_comment', array($replyData));
			service('Comlike')->add_comment($replyData);
			unset($replyData);
		}
		//===========评论给当前作者===========
		if( $replyNow && $tid ) {
			$replyNowData = array(
				'object_id'=>$tid,
				'uid'=>$this->uid,
				'username'=>$this->username,
				'src_uid'=>$replyNow,
				'object_type'=>'web_forward',
				'usr_ip'=>get_client_ip(),
				'content'=>$data['content']
			);
			//$rs = call_soap('comlike', 'Index', 'add_comment', array($replyNowData));
			$rs = service('Comlike')->add_comment($replyNowData);
			unset($replyNowData);
		}

		/* 暂不通知
		//===========添加转发通知============
		if( ! $replyAuthor ) {
			$replyAuthor = $infos['uid'];
		}
		if( $type == 'info' || $type == 'photo') {
			$_static = array('info'=>'info_frowardinfo_web','photo'=>'info_frowardpic_web');
			call_soap('ucenter', 'Notice', 'add_notice',array($this->web_id,$data['uid'],$replyAuthor,'web',$_static[$type],array('name'=>$this->web_info['name'],'url'=>mk_url(APP_URL.'/index/index', array('web_id' =>$this->web_id)),'url1'=>mk_url(APP_URL.'/info/view', array('tid' =>$infos['tid'])))));
		} else if ( $type == 'video' || $type == 'album' ) {
			$_static = array('video'=>'info_frowardvideo_web','album'=>'info_frowardalbum_web');
			call_soap('ucenter', 'Notice', 'add_notice',array($this->web_id,$data['uid'],$replyAuthor,'web',$_static[$type],array('name'=>$this->web_info['name'],'url'=>mk_url(APP_URL.'/index/index', array('web_id' =>$this->web_id)),'name1'=>$infos['title'],'url1'=>mk_url(APP_URL.'/info/view', array('tid' =>$infos['tid'])))));
		}
		*/
		unset($data, $infos);
		return $this->dump(L('operation_success'), true, array(
				'data' => $result
		));
	}

	/**
	 * 移动网页时间轴上面的信息的时间位置
	 *
	 * @author fbbin
	 * @param 前端需要提交的参数列表：
	 * @param tid 信息的TID
	 * @param timeStr 选择的发布时间:格式：2012-3-2
	 * @param bc 公元前后表示：1（前），-1（后）
	 */
	public function doSetCtime() {
		$tid = (int)P('tid');
		$this->load->model('TimelineModel');
		$webTopic = $this->TimelineModel->getTopic($tid);
		if (!$webTopic) {
			return $this->dump(L('err_topic_id'));
		}
		// 非网页创建者
		if ($webTopic['uid'] != $this->uid) {
			return $this->dump(L('permission_denied'));
		}
		$newCtime = preg_replace_callback('/(?P<year>\d+)(-?)(?P<mon>\d{0,2})(-?)(?P<day>\d{0,2})/', function ($match) {
			(int)$match['mon'] < 10 && !($match['mon'] = '0' . intval($match['mon'])) && $match['mon'] = '01';
			(int)$match['day'] < 10 && !($match['day'] = '0' . intval($match['day'])) && $match['day'] = '01';
			return $match['year'] . min($match['mon'], 12) . min($match['day'], 31);
		}, P('timeStr') ?  : date('Y-n-j', SYS_TIME));
		// 公元前
		if (P('bc') < 1)
			$newCtime = '-' . $newCtime . '000000';
			// 公元后
		else
			$newCtime = $newCtime . ($newCtime == date('Ymd', SYS_TIME) ? date('His', SYS_TIME) : '000000');

		/* $callStatus = $this->call(array(
				$tid,
				$newCtime
		), 'updateWebtopicTime');
		 */
		$callStatus = service('WebTimeline')->updateWebtopicTime($tid,$newCtime);
		// 更新赞模块存储时间 add by guojianhua
/* 		call_soap("comlike", "Index", "update_Like", array(
				"tid" => $tid,
				"object_type" => "web_info",
				"ctime" => $newCtime
		)); */
		service('Comlike')->update_Like($tid,'web_info',$newCtime);
		unset($tid, $newCtime, $webTopic);
		return $callStatus ? $this->dump(L('operation_success'), true) : $this->dump(L('operation_fail'));
	}

	/**
	 * 删除网页上面的一条信息
	 *
	 * @author fbbin
	 * @param tid 信息ID
	 * @param padgeid 当前网页的ID
	 */
	public function doDelTopic() {
		$tid = (int)P('tid');
		$pageid = (int)P('web_id');
		$this->load->model('TimelineModel');
		$webTopic = $this->TimelineModel->getTopic($tid);
		if (!($webTopic && $pageid)) {
			return $this->dump(L('err_tid_or_web_id'));
		}
		// 非网页创建者
		if ($webTopic['uid'] != $this->uid) {
			return $this->dump(L('permission_denied'));
		}
		// 删除赞、评论、统计的数据 add by 郭建华
		service('Comlike')->delObject(array('object_id'=>$tid,'object_type'=>'web_topic', 'web_id'=>$pageid));
		service('Comlike')->delObject(array('object_id'=>$tid,'object_type'=>'web_topic','web_id'=>$pageid));
		$delStatus = service('WebTimeline')->delWebtopic($tid,$this->getWebpageTagID($pageid));
		// 删除转发列表的数据
		if ($webTopic['type'] == 'forward') {
			service('Share')->del('web_topic',$tid);
		}
		service('RelationIndexSearch')->deleteAStatusOfWeb($tid);
		unset($tid, $pageid, $webTopic);
		return $delStatus ? $this->dump(L('operation_success'), true) : $this->dump(L('operation_fail'));
	}

	/**
	 * 设置一个信息突出显示
	 *
	 * @author fbbin
	 * @param tid 信息ID
	 * @param heightlight 1/0
	 */
	public function doUpdateHighlight() {
		$tid = (int)P('tid');
		$this->load->model('TimelineModel');
		$webTopic = $this->TimelineModel->getTopic($tid);
		if (!$webTopic) {
			return $this->dump(L('err_topic_id'));
		}
		// 非网页创建者
		if ($webTopic['uid'] != $this->uid) {
			return $this->dump(L('permission_denied'));
		}
		$highlight = P('highlight');
		$updateStatus = service('WebTimeline')->updateWebtopicHighlight($tid,$highlight);
		unset($tid, $highlight, $webTopic);
		return $updateStatus ? $this->dump(L('operation_success'), true) : $this->dump(L('operation_fail'));
	}

	/**
	 * 通过网页ID获取网页的标签
	 *
	 * @author fbbin
	 * @param int $pageid
	 */
	private function getWebpageTagID($pageid) {
	      return service('Interest')->get_web_category_imid($pageid) ?  : array();
	}

	/**
	 * 解析信息流数据
	 *
	 * @author fbbin
	 */
	private function _parseInfoData() {
		return array();
	}

	/**
	 * 解析照片的数据
	 *
	 * @author fbbin
	 * @param 相册数据类型额外的参数列表：
	 * @param fid 相册的ID
	 * @param pid 相片的PID
	 * @param picurl 大图地址
	 */
	private function _parsePhotoData() {
		$album['fid'] = P('fid');//以时间戳为相册ID
		$picurl = unserialize(base64_decode(P('picurl')));//相片的JSON数据
		$album['picurl'] = json_encode($picurl);
		$album['type'] = count($picurl) > 1 ? 'album' : 'photo';
		count($picurl) > 1 && $album['photonum'] = count($picurl);
		$album['note'] = P('note');//真实的相册ID
		return $album;
	}

	/**
	 * 解析视频的数据
	 *
	 * @author fbbin
	 * @param 视频类型数据额外参数列表
	 * @param vid 视频ID
	 * @param videourl 视频资源地址
	 * @param imgurl 视频截图地址
	 * @param url 视频在视频模块中的链接地址
	 * @param width 视频的宽度
	 * @param height 视频的高度
	 */
	private function _parseVideoData() {
		$video['fid'] = P('vid');
		$video['imgurl'] = P('imgurl');
		$video['width'] = P('width');
		$video['height'] = P('height');
		return $video;
	}


	/**
	 * 团购
	 */
	private function _parseGrouponData() {
		$group['groupname'] = P('groupname');
		$group['href'] = P('href');
		$group['currprice'] = P('currprice');
		$group['oriprice'] = P('oriprice');
		$group['img'] = P('img');
		$group['href'] = P('href');
		$group['expiretime'] = strtotime(P('expiretime'));	// 过期时间

 		if(in_array('', $group)) {
 			return false;
 		}
		$group['spaprice'] = sprintf('%.1f', $group['oriprice'] - $group['currprice']);
		$group['discount'] = sprintf('%.1f', ($group['currprice'] / $group['oriprice']) * 10);

		return array('groupon' => json_encode($group));
	}

	/**
	 * 商品
	 */
	private function _parseGoodsData($data, $web) {
		$this->load->model('goodsmodel');

		$goods['goodsname'] = P('goodsname');
		$goods['href'] 		= P('href');
		$goods['saleprice'] = P('saleprice');
		$goods['img'] 		=  $this->input->get_post("img");
		$goods['thumb'] 	=  explode(',', P("thumb"));
		$goods['web_id']	= $this->web_id;
		

		$catid	= service('Interest')->get_category_group(P('catid'), 4);
		$brand_id	= P('brand');

		if($brand_id<=0){
			$brand_name	= trim(P('brand_name'));
			if($brand_name==''){
				return false;
			}
			$brand_id	= $this->goodsmodel->add_goods_brand($brand_name,$catid);

		}else{
			$brand_name	= $this->goodsmodel->get_goods_brand_name($catid);
		}
		$goods['brand_id'] 	= $brand_id;
		$goods['brand_name']= $brand_name;
		$firstCover	= intval(P('firstCover'))+1;

		$pics_img	= json_decode($goods['img'],true);
		if(is_array($pics_img)){
			foreach($pics_img as $key=>$val){
				$cc[$key] 	= $val['groupname'].'/'.$val['filename'].'_b.'.$val['type'];
			}
			if(isset($cc[$firstCover])){
				$main_pics_arr_val	= $pics_img[$firstCover];
			}else{
				foreach($pics_img as $key=>$val){
					$main_pics_arr_val	= $val;
					$firstCover	= $key;
					break;
				}
			}
			$main_pics_arr[$firstCover]	= $main_pics_arr_val;
			$main_pics	= json_encode($main_pics_arr);

			$aa[]	= $cc[$firstCover];
			foreach($cc as $key=>$val){
				if($firstCover!=$key){
					$aa[]	= $val;
				}
			}
			$goods['img']	= $aa;
		}else{
			$goods['img']= "";
			$main_pics 		= "";
		}

		$goodata = array(
			'uid' => $data['uid'],
			'web_id' => $data['pid'],
			'iid' => $catid,
			'brand_id' => $brand_id,
			'main_pics' => $main_pics,
			'name' => $goods['goodsname'],
			'link' => $goods['href'],
			'price' => $goods['saleprice'],
			'pics' => addslashes_deep($this->input->get_post("img")),
			'description' => $data['content'],
			'ctime' => @strtotime($data['ctime']),
			'utime' => @strtotime($data['ctime'])
		);
		if(trim($goodata['ctime'])=='' || $goodata['ctime']<=0){
			$goodata['ctime'] = time();
			$goodata['utime'] = time();
		}


		$gid	= $this->goodsmodel->addGoods($goodata);	// 加入数据库


		$goods['gid']	= $gid;	// 商品的两个标示
		return array('goods' => json_encode($goods) , 'fid'=>$gid);
	}

	/**
	 * 解析活动的数据
	 *
	 * @author fbbin
	 * @param 活动类型数据额外参数列表
	 * @param eid 活动ID
	 */
	private function _parseEventData() {
		$event['fid'] = P('eid');
		return $event;
	}

	/**
	 * 对平台核心发起数据请求
	 *
	 * @author fbbin
	 * @param string $action
	 * @param array $data
	 * @param string $module
	 */
	private function call($data = array(), $action = 'addWebtopic', $module = 'WebTimeline', $app = 'timeline') {
		if (empty($data)) {
			return false;
		}
		return call_soap($app, $module, $action, $data);
	}

	/**
	 * 对输出进行控制
	 *
	 * @author fbbin
	 * @param array/string $info
	 * @param bool $status
	 * @param array $extra
	 */
	private function dump($info = '', $status = false, $extra = array()) {
		if (is_string($info)) {
			$data = array(
					'data' => array(),
					'status' => (int)$status,
					'info' => $info
			);
		} elseif (is_array($info)) {
			$data = $info;
		}
		if (!empty($extra)) {
			$data = array_merge($data, $extra);
		}
		exit(json_encode($data));
	}

}
