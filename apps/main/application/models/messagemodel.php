<?php
/**
 * messagemodel
 *
 * @author        gefeichao
 * @date          2012/02/23
 * @version       1.2
 * @description   站内信
 * @history       <author><time><version><desc>
 */
class MessageModel extends MY_Model {
	
	protected  $mongo_db;
	function __construct(){
		parent::__construct();
		$this->init_mongodb('default');
		$this->mongo_db = $this->mongodb;
	}
	
	/**
	 * 获取站内信群组id
	 * @author gefeichao
	 * @param $uid 发送者uid
	 * @param $touid 接收者uid
	 */
	function get_gid($uid, $touid) {
		if (! $uid || ! $touid) {
			return false;
		}
		$users = $uid . "," . $touid;
		$newusers = $touid . "," . $uid;
		$gid = $this->mongo_db->where_in ( 'g_list', array ($users, $newusers ) )->select ( array ('_id' ) )->get ( 'message_usergroup' );
		$gid = ( array ) $gid [0] ['_id'];
		if (! $gid) {
			return false;
		}
		return $gid [0] ['gid'];
	}
	
	/*转换消息数据*/
	function replace_message(){
		/*$result = $this->mongo_db->select(array('dateline','files','from_uid','gid','message','operatelist','to_uid'))->get('message_info');
		foreach($result as $item){
			$myitem=array();
			$operate = unserialize ( $item ['operatelist'] );
			$myitem ['is_read'] = $operate['is_read'];
			$myitem ['is_archive'] = $operate['is_archive'];
			$myitem ['is_delete'] = $operate['is_delete'];
			$myitem ['dateline']= $item['dateline'];
			$myitem ['files']= $item['files'];
			$myitem ['from_uid']= $item['from_uid'];
			$myitem ['to_uid']= $item['to_uid'];
			$myitem ['gid']= $item['gid'];
			$myitem ['message']= $item['message'];
			$this->mongo_db->insert ( 'message_infos', $myitem );
		}
		*/
	}
	
	/**
	 * 发送站内信息
	 * @access pubic
	 * @author gefeichao
	 * Enter description here ...
	 * @param  $fromuid		消息发送者uid
	 * @param  $touid		消息接受者uid
	 * @param  $message		发送消息内容
	 * @param  $files		附件信息【array】
	 */
	function add_message($fromuid = NULL, $touid = NULL, $message = NULL, $files = NULL) {
		if (! $fromuid || ! $touid || ! $message)
			return false;
		if (! $files)
			$files = "";
		$message = shtmlspecialchars($message);
		$users = $fromuid . "," . $touid;
		$newusers = $touid . "," . $fromuid;
		$str ='';
		$musers = explode(',', $users);
		$user = service("User")->getUserList($musers);
		
		foreach($musers as $uitem){
			foreach ($user as $value) {
				if($uitem == $value['uid']){
					$str[] =  $value['username'];
				}
			}
		}
		 $dateline = time();
		if (strpos ( $touid, ',' )) { //多人会话、直接创建新会话
			$gid = new MongoId();
			$data = array ('_id'=>$gid, 'g_list'=>$users, 'u_list'=>implode(',',$str), 'dateline'=>$dateline );
			$this->mongo_db->insert ( 'message_usergroup', $data );
		} else { //否则先查看是否已有会话，如没有创建
			//根据参数获得对应 gid 
			$mgid = $this->mongo_db->where_in ( 'g_list', array ($users, $newusers ) )->select ( array ('_id' ) )->get ( 'message_usergroup' );
			if(count($mgid) > 0){
				$gid = ($mgid[0]['_id']);
			}else{
				$gid = new MongoId();
				$data = array ('_id'=>$gid, 'g_list'=>$users, 'u_list'=>implode(',',$str), 'dateline'=>$dateline  );
				$this->mongo_db->insert ( 'message_usergroup', $data );
			}
		}
		
		$ausers = explode ( ',', $touid );
		$gid = $gid.'';
		$messageid = new MongoId();
		$data = array ('_id'=>$messageid, 'gid' => $gid, 'from_uid' => $fromuid, 'to_uid' => $touid, 'message' => $message, 
		'files' => $files, 'dateline' => time (), 'is_read' => $fromuid,'is_archive'=>'','is_delete'=>'' );
		 $this->mongo_db->insert ( 'message_info', $data );

		/*重置 会话 存档状态 start*/
		$sqldata['is_archive'] = "";
		$msglist = $this->mongo_db->where(array('gid'=>$gid))->select(array('is_archive'))->get('message_info');
		foreach($msglist as $item){
			if($item['is_archive'] != null)
			$msglist = $this->mongo_db->where(array('_id'=>$item['_id']))->update('message_info', $sqldata);
		}
		/*end*/
		$messageid = $messageid . '';
			foreach ( $ausers as $i ) {
				//为每一个组成员添加未读信息 
				service("Notice")->setting($i, 'addmsg');
			}
			return array('gid'=>$gid,'msgid'=>$messageid);
	}
	
	/**
	 * 回复站内信
	 *
	 * @access public
	 * @author gefeichao
	 * @param $fromuid 发送者id
	 * @param $touid 接收者id
	 * @param $files 附件
	 * @param $message  内容
	 * @param $gid 会话id
	 */
	function reply_message($fromuid = NULL, $touid = NULL, $message = NULL, $files = NULL, $gid = NULL) {
		
		if (! $fromuid || ! $touid || ! $gid || !$message) {
			return false;
		}
		if (! $files) {
			$files = "";
		}
		$message = shtmlspecialchars($message);
		$users = $fromuid . "," . $touid;
		$str ='';
		$musers = explode(',', $users);
		$user = service("User")->getUserList($musers);
		
		foreach($musers as $uitem){
			foreach ($user as $value) {
				if($uitem == $value['uid']){
					$str[] =  $value['username'];
				}
			}
		}
		$dateline = time();
		$sqldata = array ('g_list' => $users  ,'u_list'=>implode(',',$str),'dateline'=>$dateline   );
		$this->mongo_db->where(array('_id'=> new MongoId($gid)))->update ( 'message_usergroup', $sqldata );

		$data = array ('gid' => $gid, 'from_uid' => $fromuid, 'to_uid' => $touid, 'message' => $message, 
		'files' => $files, 'dateline' => time (), 'is_read' => $fromuid,'is_archive'=>'','is_delete'=>'' );
		 $this->mongo_db->insert ( 'message_info', $data );
		
		/*重置 会话 存档状态 start*/
		$sqldata['is_archive'] = "";
		$msglist = $this->mongo_db->where(array('gid'=>$gid))->select(array('is_archive'))->get('message_info');
		foreach($msglist as $item){
			if($item['is_archive'] != null){
			$msglist = $this->mongo_db->where(array('_id'=>$item['_id']))->update('message_info', $sqldata);
			}
		}
		/*end*/
			$ausers = explode ( ',', $touid );
			foreach ( $ausers as $i ) {
				//为每一个组成员添加未读信息 
				service("Notice")->setting($i, 'addmsg');
			}
			return $gid;
	}
	
	/**
	 * 转换信息图片
	 * @author gefeichao
	 * @param $msg 信息内容
	 * @return $msg 替换后的消息内容
	 */
	function convertface($msg) {
		$faceArr = array ('[微笑]', '[撇嘴]', '[色]', '[发呆]', '[得意]', '[流泪]', '[害羞]', '[闭嘴]', '[睡]', '[大哭]', '[尴尬]', '[发怒]', '[调皮]', '[呲牙]', '[惊讶]', '[难过]', '[酷]', '[抓狂]', '[吐]', '[偷笑]', '[可爱]', '[白眼]', '[傲慢]', '[饥饿]', '[困]', '[惊恐]', '[流汗]', '[憨笑]', '[大兵]', '[奋斗]', '[咒骂]', '[疑问', '[嘘]', '[晕]', '[折磨]', '[衰]' );
		$faceArr = array_flip ( $faceArr );
		$msg = str_replace ( array ('\s', '\n' ), array ('&nbsp;', '<br/>' ), $msg );
		preg_match_all ( '/\[[^\]]*\]/', $msg, $tmp );
		foreach ( $tmp [0] as $key => $value ) {
			if (isset ( $faceArr [$value] )) {
				$pic = $faceArr [$value];
				$pic = $pic + 1;
				$msg = str_replace ( $value, '<img src="/misc/img/system/face/' . $pic . '.gif" alt="' . $value . '"/>', $msg );
			}
		}
		return $msg;
	}
	
	function message_archivelist_count($searchkey=null){
		$result = $this->mongo_db->like ( 'g_list', UID )->order_by(array('dateline'=>-1))->select ( array ('_id','g_list','u_list' ) )->get ( 'message_usergroup' );
		$sresult = array();
		
		foreach ( $result as $value ) {
			$res = $value['_id'] . '';
			if(isset($searchkey) && $searchkey != null){
			$mresult = $this->mongo_db->where ( array ('gid' => $res ) )
				->order_by ( array ('dateline' => - 1 ) )->like ( 'message', $searchkey, 'im' )
				->select(array('_id','dateline','from_uid','gid','is_archive','is_delete','is_read','message','to_uid'))
				->get ( 'message_info' );
			}else{
			$mresult = $this->mongo_db->where ( array ('gid' => $res ) )
				->order_by ( array ('dateline' => - 1 ) )
				->select(array('_id','dateline','from_uid','gid','is_archive','is_delete','is_read','message','to_uid'))
				->get ( 'message_info' );
			}	
			if ($mresult){
				foreach($mresult as $item){
					$is_read = $this->messState($item ['is_read'],'is_read');
					$is_delete = $this->messState($item ['is_delete'],'is_delete');
					$is_archive = $this->messState($item ['is_archive'],'is_archive');
					$item ['state'] = $is_read['state'];
					$item ['del'] = $is_delete['del']; 
					$item ['archive'] = $is_archive['archive'];
					if( $item ['del'] == 0 && $item ['archive'] == 1){
						$g_list = explode(',',$value['g_list']);
						$index = array_search(UID,$g_list);
						$u_list = explode(',', $value['u_list']);
						unset($u_list[$index]);
						$item ['u_list'] = isset($value['u_list']) ? implode(',', $u_list) : $value['g_list'];
						$item ['g_list'] = $value['g_list'];

						$sresult [] = $item;
						break;
					}
				}
			}
		}
		return $sresult;
	}

	/**
	 * 获取站内信已存档列表
	 * @author gefeichao
	 * Enter description here ...
	 * @return $messagelist 返回消息列表
	 */
	function message_archivelist($searchkey=NULL,$limit,$offset) {
		$result = $this->message_archivelist_count($searchkey);
		$sresult = array();$messagelist = array();
		$count = count($result);
		for ($i = $offset; $i<$count; $i++ ) {
			$sresult[] = $result[$i];
			if( count($sresult) < $limit){
				continue;
			}else{
				break;
			}
		} 
		if (! $sresult || count($sresult) == 0)
			return false;
		$nextpage = $count > ($offset + $limit) ? 1 : 0;
		foreach ( $sresult as $value ) {
			$messagestatus = false;
			if ($value ['from_uid'] == UID) {
				$users = explode ( ',', $value ['to_uid'] );
				$userid = $users [0];
				$messagestatus = true;
			} else {
				$userid = $value ['from_uid'];
			}

			$value['toUser'] = $messagestatus;
			$value ['m'] = msubstr(  $value ['message'] , 0, 42);
			/*$value ['mess'] = msubstr( $value ['message'] , 0, 20);*/
			$value ['message'] =  $value ['message'] ;
			$value ['id'] = $value ['_id'] .'';
			$value ['userid'] = $userid;
			$value ['date'] = friendlyDate ( $value ['dateline'], 'full' );
			$value ['dateline'] = friendlyDate ( $value ['dateline'] );
            $messagelist [$value ['date']] = $value;
		}
		krsort ( $messagelist );
		$messagelist = array_values ( $messagelist );
		$messagelist[] = $nextpage;
		return $messagelist;
	}
	
	function message_unreadlist_count($searchkey=null){
		$result = $this->mongo_db->like ( 'g_list', UID )->order_by(array('dateline'=>-1))->select ( array ('_id','g_list','u_list' ) )->get ( 'message_usergroup' );
		$sresult = array();
		
		foreach ( $result as $value ) {
			$res = $value['_id'] . '';
			if(isset($searchkey) && $searchkey != null){
			$mresult = $this->mongo_db->where ( array ('gid' => $res ) )->order_by ( array ('dateline' => - 1 ) )
				->like ( 'message', $searchkey, 'im' )
				->get ( 'message_info' );
			}else{
			$mresult = $this->mongo_db->where ( array ('gid' => $res) )
				->order_by ( array ('dateline' => - 1 ) )
				->get ( 'message_info' );
			}
			if ($mresult){
				foreach($mresult as $item){
					$is_read = $this->messState($item ['is_read'],'is_read');
					$is_delete = $this->messState($item ['is_delete'],'is_delete');
					$is_archive = $this->messState($item ['is_archive'],'is_archive');
					$item ['state'] = $is_read['state']; 
					$item ['del'] = $is_delete['del'];
					$item ['archive'] = $is_archive['archive'];
					if($item ['state'] ==1 && $item ['del'] == 0 && $item ['archive'] == 0){
						$g_list = explode(',',$value['g_list']);
						$index = array_search(UID,$g_list);
						$u_list = explode(',', $value['u_list']);
						unset($u_list[$index]);
						$item ['u_list'] = isset($value['u_list']) ? implode(',', $u_list) : $value['g_list'];
						$item ['g_list'] = $value['g_list'];
					
						$sresult [] = $item;
						break;
					}
				}
			}
		}
		return $sresult;
	}
	
	/**
	 * 获取站内信未读消息列表
	 * @author gefeichao
	 * Enter description here ...
	 * @return $messagelist 返回消息列表
	 */
	function message_unreadlist($searchkey=NULL,$limit,$offset) {
		$sresult = array();$messagelist=array();
		$result = $this->message_unreadlist_count($searchkey);
		$count = count($result);
		for ( $i= $offset; $i<$count; $i++) {
			$sresult[] = $result[$i];
			if(count($sresult)<$limit){
				continue;
			}else{
				break;
			}
		}
		if (! $sresult || count($sresult) == 0)
			return false;
		/*获取是否有下一页*/
		$nextpage = $count > ($offset + $limit) ? 1 : 0;

		foreach ( $sresult as $value ) {
			$messagestatus = false;
			if ($value ['from_uid'] == UID) {
				$users = explode ( ',', $value ['to_uid'] );
				$userid = $users [0];
				$messagestatus = true;
			} else {
				$userid = $value ['from_uid'];
			}
			$value['toUser'] = $messagestatus;
			$value ['m'] = msubstr(  $value ['message'] , 0, 42);
			$value ['id'] = $value ['_id'].'';
			$value ['userid'] = $userid;
			$value ['date'] = friendlyDate ( $value ['dateline'], 'full' );
			$value ['dateline'] = friendlyDate ( $value ['dateline'] );
			$messagelist [$value ['date']] = $value;
		}
		krsort ( $messagelist );
		$messagelist = array_values ( $messagelist );
		$messagelist[] = $nextpage;
		return $messagelist;
	}
	
	function message_showlist_count($searchkey=NULL){
		$result = $this->mongo_db->like ( 'g_list', UID )->order_by(array('dateline'=>-1))->select ( array ('_id','u_list','g_list' ) )->get ( 'message_usergroup' );
		$sresult = array();
		
		foreach ( $result as $value ) {
			$res = $value['_id']. '';
			if(isset($searchkey) && $searchkey != null){
					$mresult = $this->mongo_db->where ( array ('gid' => $res ) )->order_by ( array ('dateline' => - 1 ) )
						->like ( 'message', $searchkey, 'im' )
						->get ( 'message_info' );
			}else{
					$mresult = $this->mongo_db->where ( array ('gid' => $res ) )->order_by ( array ('dateline' => - 1 ) )
						->get ( 'message_info' );
			}
			
			if ($mresult){
				foreach($mresult as $item){
					$is_read = $this->messState($item ['is_read'],'is_read');
					$is_delete = $this->messState($item ['is_delete'],'is_delete');
					$is_archive = $this->messState($item ['is_archive'],'is_archive');
					$item ['state'] = $is_read['state'];
					$item ['del'] = $is_delete['del'];
					$item ['archive'] = $is_archive['archive'];
					if( $item ['del'] == 0 && $item ['archive'] == 0){
					$item ['g_list'] = $value['g_list'];
					$g_list = explode(',',$value['g_list']);
					$index = array_search(UID,$g_list);
					$u_list = explode(',', $value['u_list']);
					unset($u_list[$index]);
					$item ['u_list'] = isset($value['u_list']) ? implode(',', $u_list) : $value['g_list'];

					
						$sresult [] = $item;
						break;
					}
				}
				
			}
		}
		return $sresult;
	}
	/**
	 * 获取站内信收件箱列表
	 * @author gefeichao
	 * Enter description here ...
	 * @return $messagelist 返回消息列表
	 */
	function message_showmlist($searchkey=NULL,$limit,$offset) {
		$sresult = array();$messagelist=array();
		$result = $this->message_showlist_count($searchkey);	
		$count = count($result);
		for($i=$offset;$i<$count;$i++){
			$sresult[] = $result[$i];
			if(count($sresult)<$limit){
				continue;
			}else{
				break;
			}
		}
	
		/*获取是否还有下一页*/
		if($count > ($offset + $limit))
			$nextpage = 1;
		else
			$nextpage = 0;
		if (! $sresult || count($sresult) == 0)
			return false;
		foreach ( $sresult as $value ) {
			$messagestatus = false;
			if ($value ['from_uid'] == UID) {
				$users = explode ( ',', $value ['to_uid'] );
				$userid = $users [0];
				$messagestatus = true;
			} else {
				$userid = $value ['from_uid'];
			}
			
			$value['toUser'] = $messagestatus;
			$value ['m'] = msubstr(  $value ['message'] , 0, 42);
			$value ['id'] = $value ['_id'].'';
			$value ['userid'] = $userid;
			$value ['date'] = friendlyDate ( $value ['dateline'], 'full' );
			$value ['dateline'] = friendlyDate ( $value ['dateline'] );
			$messagelist [$value ['date']] = $value;
           
		}
		krsort ( $messagelist );
		$messagelist = array_values ( $messagelist );
		$messagelist[] = $nextpage;
		return $messagelist;
	}
	
	/**
	 * 获取站内信top列表
	 * @author gefeichao
	 * Enter description here ...
	 * @return $messagelist 返回消息列表
	 */
	function message_list_top() {
		$result = $this->mongo_db->like ( 'g_list', UID )->order_by(array('dateline'=>-1))->select ( array ('_id','u_list','g_list' ) )->get ( 'message_usergroup' );
		$sresult = array();
		$messagelist = array();
		foreach ( $result as $value ) {
			$res = $value['_id'] . '';
			$mresult = $this->mongo_db->where ( array ('gid' => $res ) )->order_by ( array ('dateline' => - 1 ) )	
			->get ( 'message_info' );
			if ($mresult){
				foreach($mresult as $item){
					$is_read = $this->messState($item ['is_read'],'is_read');
					$is_delete = $this->messState($item ['is_delete'],'is_delete');
					$is_archive = $this->messState($item ['is_archive'],'is_archive');
					$item ['state'] = $is_read['state']; 
					$item ['del'] = $is_delete['del']; 
					$item ['archive'] = $is_archive['archive'];
					if( $item ['del'] == 0 && $item ['archive'] == 0){
						$item ['g_list'] = $value['g_list'];
						$g_list = explode(',',$value['g_list']);
						$index = array_search(UID,$g_list);
						$u_list = explode(',', $value['u_list']);
						unset($u_list[$index]);
						$item ['u_list'] = isset($value['u_list']) ? implode(',',$u_list) : $value['g_list'];

						$sresult [] = $item;
						break;
					}
				}
			}
		}
		foreach ( $sresult as $value ) {
				
				$value['toUser'] = ($value ['from_uid'] == UID) ? true : false;
				$value ['mess'] = msubstr( $value ['message'] , 0, 20);
				$value ['message'] =  $value ['message'] ;
				$value ['id'] = $value ['_id'] .'';

				$value ['date'] = friendlyDate ( $value ['dateline'], 'full' );
				$value ['dateline'] = friendlyDate ( $value ['dateline'] );
				$messagelist [$value ['date']] = $value;
		}
		
		
		krsort ( $messagelist );
		$messagelist = array_values ( $messagelist );
		$topresult = array_slice($messagelist,0,5);
		return $topresult;
	}
	
	function setmessage_count($searchkey=null){
		$result = $this->mongo_db->like ( 'g_list', UID )->order_by(array('dateline'=>-1))->select ( '_id','u_list','g_list' )->get ( 'message_usergroup' );
		$sresult = array();$sentmessagelist=array();
		foreach ( $result as $value ) {
			$res = (array)$value['_id'];
			if(isset($searchkey) && $searchkey != null)
			$mresult = $this->mongo_db->like ( 'message', $searchkey, 'im')->where_and ( array ('gid' => $res['$id'], 'from_uid' => UID ) )
				->order_by ( array ('dateline' => - 1 ) )
				->select ( array ('gid', 'message', 'dateline', '_id', 'from_uid', 'to_uid', 'files','is_archive','is_delete','is_read'  ) )
				->get ( 'message_info' );
			else
			$mresult = $this->mongo_db->where_and ( array ('gid' => $res['$id'] , 'from_uid' => UID) )->order_by ( array ('dateline' => - 1 ) )
				->select ( array ('gid', 'message', 'dateline', '_id', 'from_uid', 'to_uid', 'files','is_archive','is_delete','is_read'  ) )
				->get ( 'message_info' );
			if ($mresult){
				foreach($mresult as $item){
					$is_read = $this->messState($item ['is_read'],'is_read');
					$is_delete = $this->messState($item ['is_delete'],'is_delete');
					$is_archive = $this->messState($item ['is_archive'],'is_archive');
					$item ['state'] = $is_read['state'];
					$item ['del'] = $is_delete['del']; 
					$item ['archive'] = $is_archive['archive'];
					if( $item ['del'] == 0 && $item ['archive'] == 0){
						$item ['g_list'] = $value['g_list'];
						$g_list = explode(',',$value['g_list']);
						$index = array_search(UID,$g_list);
						$u_list = explode(',', $value['u_list']);
						unset($u_list[$index]);
						$item ['u_list'] = isset($value['u_list']) ? implode(',', $u_list) : $value['g_list'];
					
						$sresult [] = $item;
						break;
					}
				}
			}
		}
		return $sresult;
	}

	/**
	 * 获取用户已发送列表
	 * Enter description here ...
	 * @author gefeichao
	 * @return $sentmessagelist 已发送列表
	 */
	function sentmessage($searchkey = NULL,$limit,$offset) {
		$sresult = array();$sentmessagelist=array();
		$result = $this->setmessage_count($searchkey);
		$count = count($result);
		for($i = $offset; $i<$count; $i++) {
			$sresult[] = $result[$i];
			
			if(count($sresult)<$limit){
				continue;
			}else{
				break;
			}
		}
		/*获取是否还有下一页*/
		$nextpage = ($count > ($offset + $limit)) ? 1 : 0;
		if (! $sresult) {
			return false;
		}
		foreach ( $sresult as $value ) {

			$messagestatus = false;
			if ($value ['from_uid'] == UID) {
				$users = explode ( ',', $value ['to_uid'] );
				$userid = $users [0];
				$messagestatus = true;
			} else {
				$userid = $value ['from_uid'];
			}
			$value['g_list'] = $value ['from_uid'] .','.$value ['to_uid'];
			$value['toUser'] = $messagestatus;
			$value ['m'] = msubstr( $value ['message'] , 0, 42);
			$value ['id'] = $value ['_id'].'';
			$value ['userid'] = $userid;
			$value ['date'] = friendlyDate ( $value ['dateline'], 'full' );
			$value ['dateline'] = friendlyDate ( $value ['dateline']);
            $sentmessagelist [$value ['date']] = $value;
		}
		krsort ( $sentmessagelist );
		$sentmessagelist = array_values ( $sentmessagelist );
		$sentmessagelist[] = $nextpage;
		return $sentmessagelist;
	}

	/*
	 * 返回 消息 各种状态
	 * */

	private function messState($mess,$type){
		$myarr = array();
			switch($type){
				case ($type == 'is_read'):
					if (strpos ( $mess, UID ) === false) {
						$myarr ['state'] = 1;
					} else {
						$myarr ['state'] = 2;
					}
					break;
				case ($type == 'is_archive'):
					if (strpos ( $mess, UID ) === false) {
						$myarr ['archive'] = 0;
					} else {
						$myarr ['archive'] = 1;
					}
					break;
				case ($type == 'is_delete'):
					if (strpos ( $mess, UID ) === false) {
						$myarr ['del'] = 0;
					} else {
						$myarr ['del'] = 1;
					}
					break;
			}
			return $myarr;
	}

	/**
	 * 设置用户站内信已读、未读
	 * Enter description here ...
	 * @param  $id  信息id
	 */
	function setmessage($id = NULL) {
		
		if (! $id)
			return false;
		$sresult = $this->mongo_db->where ( array ('_id' => new MongoId($id )) )->select ( array ('is_read' ) )->get ( 'message_info' );
		if(!$sresult)	return false;
		$operate = $sresult [0];
		$b = array ();
		if (strpos ( $operate ['is_read'], UID ) === false) {
			if (count ( $operate ['is_read'] ) > 0) {
				$operate ['is_read'] = $operate ['is_read'] . "," . UID;
			} else {
				$operate ['is_read'] = UID;
			}
			$state = 2;
		} else {
			$a = explode ( ',', $operate ['is_read'] );
			foreach ( $a as $v ) {
				if ($v != UID) {
					$b [] = $v;
				}
			}
			$operate ['is_read'] = implode ( ',', $b );
			$state = 1;
		}
		$upstate = $this->mongo_db->where ( array ('_id' => new MongoId($id) ) )->update ( 'message_info', array ('is_read' => $operate ['is_read'] ) );
		if ($upstate) {
			return $state;
		} else {
			return false;
		}
	}
	
	/**
	 * 设置用户站内信存档、未存档
	 * Enter description here ...
	 * @param  $id  信息id
	 */
	function setarchive($id = NULL) {
		if (! $id)
			return false;
		$operate = array();
		$sresult = $this->mongo_db->where ( array ('_id' => new MongoId($id) ) )->select ( array ('is_archive' ) )->get ( 'message_info' );
		if(!$sresult)	return false;
		$operate =  $sresult [0] ;
		
		$b = array ();
		if (strpos ( $operate ['is_archive'], UID ) === false) {
			if (count ( $operate ['is_archive'] ) > 0) {
				$operate ['is_archive'] = $operate ['is_archive'] . "," . UID;
			} else {
				$operate ['is_archive'] = UID;
			}
			$state = 2;
		} else {
			$a = explode ( ',', $operate ['is_archive'] );
			foreach ( $a as $v ) {
				if ($v != UID) {
					$b [] = $v;
				}
			}
			$operate ['is_archive'] = implode ( ',', $b );
			$state = 1;
		}
		
		$upstate = $this->mongo_db->where ( array ('_id' => new MongoId($id) ) )->update ( 'message_info', array ('is_archive' => $operate ['is_archive'] ) );
		if ($upstate) {
			return $state;
		} else {
			return false;
		}
	}
	
	/**
	 * 删除站内信
	 * @access  public
	 * @author gefeichao
	 * @param   $id  记录id 
	 * @param $uid  用户uid
	 * return   bool
	 */
	function del_pms($ids = NULL, $uid = NULL) {
		if (! count($ids)>0 || ! $uid) {
			return false;
		}
		$i = 0;

		foreach ($ids as $id){
			$sresult = $this->mongo_db->where ( array ('_id' => new MongoId($id) ) )->select ( array ('is_delete' ) )->get ( 'message_info' );
			$operate =  $sresult [0];
			$state = 1;
			$b = array ();
			if (strpos ( $operate ['is_delete'], UID ) === false) {
				if (count ( $operate ['is_delete'] ) > 0) {
					$operate ['is_delete'] = $operate ['is_delete'] . ',' . $uid;
				} else {
					$operate ['is_delete'] = $uid;
				}
				$state = 2;
			} 
			if($state == 2){
			$upstate = $this->mongo_db->where ( array ('_id' => new MongoId($id) ) )->update ( 'message_info', array ('is_delete' => $operate ['is_delete'] ) );
			if ($upstate) {
				$i++;
			} }
		}
		return ($i>0)? true : false ;
	}
	
	/*
	*获取所有对话信息
	*/
	function get_msg_list($gid=null){
		if(!$gid)return false;
		$items = array();
		$sresult = $this->mongo_db->order_by ( array ('dateline' => 1 ) )->where ( array ('gid' => $gid ) )
			->select ( array ( '_id', 'is_delete' ) )->get ( 'message_info' );
		foreach($sresult as $item){
		
				$items[] = $item['_id'].'';
		}
		return $items;
	}

	/**
	 * 获取站内信对话信息列表
	 * Enter description here ...
	 * @param  $gid  会话id
	 */
	function showdetailmessage($gid = NULL, $limit, $offset) {
	if (! $gid)
			return false;
		$sresult = $this->mongo_db->order_by ( array ('dateline' => 1 ) )->where ( array ('gid' => $gid ) )
			->select ( array ('is_read', 'message', 'dateline', '_id', 'from_uid', 'to_uid', 'files','is_delete' ) )->get ( 'message_info' );
		if (! $sresult) {
			return false;
		}
		$str = '';$mysresult = $showdmessage = array();
		foreach ($sresult as $value) {
			$str[] = $value['from_uid'];
			$is_delete = $this->messState($value ['is_delete'],'is_delete');
			if($is_delete['del'] == 0)
				$myrel[]=$value;
		}
		$count = count($myrel);
		
		for($i = $offset; $i<$count; $i++) {
			$mysresult[] = $myrel[$i];
			if(count($mysresult)<$limit){
				continue;
			}else{
				break;
			}
		}
		/*获取是否还有下一页*/
		$nextpage = ($count > ($offset + $limit)) ? 1 : 0;
		$user = service("User")->getUserList($str);
		foreach ( $mysresult as $value ) {
			$userid = $value ['from_uid'];
			
			foreach($user as $item){
			
				if($value['from_uid'] == $item['uid']){
					$value ['username'] =$item['username'];
					$value ['dkcode'] = $item['dkcode'];
					break;
				}
			}
			if (strpos ( $value ['is_read'], UID ) === false) {
				$this->setmessage ( $value ['_id'] );
			}
			$value ['message'] = $value ['message'] ;
			$value ['avatarurl'] =  get_avatar( $userid );	//获取头像
			$newid = (array)$value ['_id'];
			$value ['id'] = $newid['$id'];
			$value ['userid'] = $userid;
			$value ['dateline'] = friendlyDate ( $value ['dateline'] );
			$showdmessage [] = $value;
		}
		if ($showdmessage) {
			$showdmessage[] = $nextpage;
			return $showdmessage;
		} else {
			return false;
		}
	}
	
	/**
	 * 获取某个群组成员
	 * Enter description here ...
	 * @param  $gid  会话id
	 */
	function showgroup($gid = NULL) {
		if (! $gid)
			return false;
		$sresult = $this->mongo_db->where ( array ('_id' => new MongoId($gid) ) )->select (  array ('u_list','g_list' ) )->get ( 'message_usergroup' );
		if(!$sresult)	return false;
		return $sresult [0];
	}
	
	/**
	 * 站内信搜索
	 * @author gefeichao
	 * @param $searchkey 搜索关键字
	 * @param $uid 用户uid
	 */
	function search_msg($searchkey, $gid, $limit, $offset) {
		if ( !$gid) {
			return false;
		}
		$values = $this->mongo_db->like ( 'message', $searchkey, 'im')->where ( array ('gid' => $gid ) )
				->select ( array('message', 'dateline', 'mid', 'from_uid', 'to_uid', 'files' ,'is_delete'))->get ( 'message_info' );
		if (! $values)
			return false;
		$countres = array();
		$str = '';
		foreach ($values as $value) {
			$str[] = $value['from_uid'];
			$is_delete = $this->messState($value ['is_delete'],'is_delete');
			if($is_delete['del'] == 0)
				$myrel[]=$value;
		}
		
		$count = count($myrel);
		for($i = $offset; $i<$count; $i++) {
			$mysresult[] = $myrel[$i];
			if(count($mysresult)<$limit){
				continue;
			}else{
				break;
			}
		}
		/*获取是否还有下一页*/
		$nextpage = ($count > ($offset + $limit)) ? 1 : 0;

		$user = service("User")->getUserList($str);
		foreach ( $mysresult as $res ) {
			
			foreach($user as $item){
			
				if($value['from_uid'] == $item['uid']){
					$res ['username'] =$item['username'];
					$res ['dkcode'] = $item['dkcode'];
				}
			}
			
			$res ['avatarurl'] =  get_avatar( $res ['from_uid'] );
			$res ['message'] = $this->convertface ( $res ['message'] );
			$newid = (array)$res ['_id'];
			$res ['id'] = $newid['$id'];
			$res ['userid'] = $res ['from_uid'];
			$res ['dateline'] = friendlyDate ( $res ['dateline'] );
			$countres [] = $res;
		}
		if ($countres) {
			$countres[] = $nextpage;
			return $countres;
		} else {
			return false;
		}
		
	}
	
	/**
	 * 获取某站内信的附件信息
	 * @author gefeichao
	 * @param $id 附件id
	 */
	function get_files($id = NULL) {
		if (! $id)
			return false;
		$valstr_rel = $this->mongo_db->where ( array ('_id' => new MongoId($id) ) )->select ( array('is_image', 'group_name','orig_name','file_size','client_name', 'file_name', 'file_ext' ))->get ( 'message_fileupload' );
		if (! $valstr_rel) {
			return false;
		}
		return $valstr_rel;
	}
	

	
	/**
	 * 添加附件信息
	 * @access  public
	 * @author gefeichao
	 * @param   $data  附件信息 
	 * return   bool
	 */
	function addfile($data) {
		if (! $data)
			return 1;
		
 		$this->mongo_db->insert ( 'message_fileupload', $data );
		
		$file_id =  $this->mongo_db->where(array (
					'file_name' => $data['file_name'], 
					'group_name' =>$data['group_name'],
					'orig_name' => $data ['orig_name'] 
			 ))->get('message_fileupload');
		$res = (array)$file_id[0]['_id'];
		return $res['$id'];
	}
	
	
	/**
	 * 获取未读总数
	 *
	 * @author gefeichao
	 * @access public
	 * @param $uid 用户id
	 */
	function show_unread($uid) {
		if (! $uid) {
			return false;
		}
		$show_rel = $this->mongo_db->where(array('uid'=>$uid))->select(array('un_msg','un_notice','un_invite'))->get('expand');
		if(!$show_rel){
			return false;
		}
		$show_rel[0]['un_invite'] = service("Relation")->getNumOfReceivedFriendRequests($uid);
		return $show_rel;
	}

	/**
	 * 获取某个消息
	 * Enter description here ...
	 * @param  $gid  会话id
	 */
	function showmsgdetail($id = NULL) {
		if (! $id)
			return false;
		$sresult = $this->mongo_db->where ( array ('_id' => new MongoId($id) ) )->select(array('gid'))->get ( 'message_info' );
		if(!$sresult)	return false;
		return $sresult[0]['gid'];
	}
}
?>