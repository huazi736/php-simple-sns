<?php
/**
 * noticemodel
 *
 * @author        gefeichao
 * @date          2012/02/23
 * @version       1.2
 * @description   通知
 * @history       <author><time><version><desc>
 */
class NoticeModel extends MY_Model {
	protected  $mongo_db;
	function __construct(){
		parent::__construct();
		$this->init_mongodb('default');
		$this->mongo_db = $this->mongodb;
	}
	
	/**
	 * 获取通知总数
	 * @author gefeichao
	 * @param $uid 接收用户uid
	 * @param $ntype  1  用户通知	2网页通知	
	 * @return count 总记录数
	 */
	function getcount($uid, $type =NULL)
	{
		if(!$type)
			$count = $this->mongo_db->where_and(array('uid' => $uid, 'is_delete' => 1))->where_gte( 'dateline' , strtotime('-1 month') )->count('notice');
		else 
			$count = $this->mongo_db->where_and(array('uid' => $uid, 'is_delete' => 1, 'ntype' => $type))->where_gte( 'dateline' , strtotime('-1 month') )->count('notice');
		return $count;
	}
	
	/**
	 * 查看通知
	 *
	 * @access public
	 * @author gefeichao
	 * @date 2012/02/23
	 * @param $uid 接收者id
	 * @param $top  是否截取
	 * @param $ntype   1  用户通知	2 网页通知	
	 */
	public function list_notice_m($uid = NULL, $top = NULL, $offset = 1, $limit = 30, $ntype =NULL) {
		if (! $uid) {
			return false;
		}
		
		//获得通知列表
		if ($top) {
			$vals = $this->mongo_db->where_and(array('uid' => $uid, 'is_delete' => 1))->where_lt('dateline',time())
					->select(array('_id','ntype','stype', 'uid', 'type', 'content', 'dateline'))->limit(5)->order_by(array('dateline' => -1))->where_gte( 'dateline' , strtotime('-1 month') )->get('notice');
		} else {
			if(!$ntype){
				$vals = $this->mongo_db->where_gte( 'dateline' , strtotime('-1 month') )->where_lt('dateline',time())
					->select(array('_id','ntype','stype', 'uid', 'type', 'content', 'dateline'))->order_by(array('dateline' => -1))->offset($offset)->limit($limit)->where_and(array('uid' => $uid, 'is_delete' => 1))->get('notice');
			}else
			
				$vals = $this->mongo_db->where_and(array('uid' => $uid, 'is_delete' => 1, 'ntype' => $ntype))->where_gte( 'dateline' , strtotime('-1 month') )->where_lt('dateline',time())
					->select(array('_id','ntype','stype', 'uid', 'type', 'content', 'dateline'))->order_by(array('dateline' => -1))->offset($offset)->limit($limit)->get('notice');
		}
		//更新通知状态
		if (! $vals)	return false;
		$str = '';$webstr='';
		foreach ($vals as $value) {
			$contentarray = $value ['content'];
			$str[] = $contentarray[0];
			
			if(isset($value['stype']) && ($value['stype'] == 'event_update_web' ||$value['stype'] == 'event_c_web' || $value['stype'] == 'event_ban_web')){
				
				$webstr[] = $value['ntype'];
			}
		}
		
		$webinfo = service("Interest")->get_web_info($webstr);		
		$user = service("User")->getUserList($str,array(),0,30);

		if(!$user)	return false;
		foreach ( $vals as $noticev ) {
			$contentarray = $noticev ['content'];
			if(!$contentarray[0])	return false;
			$dkcode = $uname ="";
			foreach($user as $item){
				if($contentarray[0] == $item['uid']){
					$dkcode=$item['dkcode'];		//获取端口号
					$uname=$item['username'];		//获取用户名称
				}
			}
			if(!$dkcode || !$uname)	continue;
			if($noticev['ntype'] > 1 && $noticev['stype'] != 'dk_guanzhu_web'){
				if(isset($noticev['stype']) && ( $noticev['stype'] == 'event_c_web' || $noticev['stype'] == 'event_update_web' ||$noticev['stype'] == 'event_ban_web' ||  $noticev['stype'] == 'dk_del_web'))
				$avatar=get_webavatar($noticev['ntype'],'s');	//获取头像
				else 
				$avatar=get_avatar($contentarray[0]);	//获取头像
			}else {
				$avatar=get_avatar($contentarray[0]);	//获取头像
			}
			if(isset($noticev['stype']) && ( $noticev['stype'] == 'event_c_web' || $noticev['stype'] == 'event_update_web' ||$noticev['stype'] == 'event_ban_web')){
				foreach ($webinfo as $item) {
					if($item['aid'] == $noticev['ntype']){
						$name1 = $item['name'];
						$aid = $item['aid'];
						break;
					}
				}
				
				if(isset($aid) && isset($name1)){
					
					$userurl='<span class="blueName"><a href="'.mk_url('main/index/main', array('web_id' => $aid),FALSE).'">'.$name1.'</a></span>';
				}else{
					
					 $name = $contentarray[2]['name1'];
					$userurl='<span class="blueName"><a href="'.mk_url('main/index/main', array('dkcode' => $dkcode)).'">'.$name.'</a></span>';
				}
			}else{
				
				$userurl='<span class="blueName"><a href="'.mk_url('main/index/main', array('dkcode' => $dkcode)).'">'.$uname.'</a></span>';
			}
		//从配置文件读取$stype值 当值为stirng和array时 构造的是不同样式的消息语句
		$temArray=$contentarray[1];
		if(empty($temArray)){ continue;}
		$temp = $contentarray[2];
		if(!isset($temp) ) {
			 $temp['name']="";
			$temp['url'] = mk_url('main/index/main', array('dkcode' => $dkcode));
		}else{
			if(isset($temp['name']))
				$temp['name'] = shtmlspecialchars($temp['name']);
			if(! isset($temp['url']))
				$temp['url'] = mk_url('main/index/main', array('dkcode' => $dkcode));
			if(isset($temp['url1']) && $temp['url1']!= null)
				$temp['url']=mk_url('main/index/main',array('web_id'=>$noticev['ntype']),FALSE);

		}
		if($contentarray[0] == UID )  $userurl = null;
		if(isset($noticev['stype']) && $noticev['stype'] == 'dk_del_web')  $userurl = null;
		
			$content = $this->check_content($userurl,$temArray,$temp);
			//设置截取通知
			$subcontent = $this->check_subcontent($uname,$userurl,$temArray,$temp);
			$noticev ['dkcode'] =  $dkcode;
			if (! $noticev) {
				continue;
			}
			$id = (array)$noticev['_id'];
			$noticev ['_id'] = $id['$id'];
			$noticev ['content'] = ($content);
			$noticev ['content2'] = strip_tags($content );
			$noticev ['content1'] = (preg_replace ( "/<(\/?a.*?)>/si", "", $subcontent ));
			if($noticev ['type'] == 'web' && isset($temp['url1'])){
				$noticev ['url'] = $temp['url1'];
			}else{
				$noticev ['url'] = $temp['url'];
			}
			$noticev ['suid'] = $contentarray[0];
			$noticev ['avatar'] =  $avatar;	//获取头像
			$noticev ['t'] = $noticev ['type'];
			$noticev ['date'] = $noticev ['dateline'];
			$noticev ['dateline'] = friendlyDate ( $noticev ['dateline'] );
			$noticevalues [] = $noticev;
		}
		
		if (! $noticevalues) {
			
			return false;
		}
		
		return $noticevalues;
	}
	
	/*消息内容分类显示*/
	function check_content($userurl=NULL, $temArray, $temp){
		if(is_array($temArray))
		{
			$temp['name'] = isset($temp['name']) ? $temp['name']:"";
			$temp['name1'] = isset($temp['name1']) ? $temp['name1']:"";
			if(count($temArray)==1){
				$content=$userurl.$temArray[0];
				if(isset($temp['name']))
				$content .= '<span class="blueName"><a href="'.$temp['url'].'">'
					.$temp['name'].'</a></span>';
			}else{
				 
				if (isset($temp['name1']) && $temp['name1'] != null){
					$content=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
					.$temp['name'].'</a></span>'.$temArray[1].'<span class="blueName"><a href="'.$temp['url1'].'">'
					.$temp['name1'].'</a></span>';
					if(isset($temArray[2]))
						$content .= $temArray[2];
					if(isset($temArray[3]))
						 $content .=  '<span class="blueName"><a href="'.$temp['url1'].'">'.$temArray[3].'</a></span>';
				}else if(!$temp['name1'] && isset($temp['url1']) && isset($temArray[2]) && isset($temp['name'])){
					$content=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
							.$temp['name'].'</a></span>'.$temArray[1].'<span class="blueName"><a href="'.$temp['url1'].'">'
							.$temArray[2].'</a></span>';
				}else if(isset($temp['name']) && $temp['name'] != null){
					if(isset($temArray[3])){
						$content=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
						.$temArray[1].'</a></span>'.$temArray[2].'<span class="blueName"><a href="'.$temp['url'].'">'
						.$temp['name'].'</a></span>'.$temArray[3];
					}else{
					$content=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
					.$temp['name'].'</a></span>'.$temArray[1];
					if(isset($temArray[2]))
						$content .= '<span class="blueName"><a href="'.$temp['url'].'">'.$temArray[2].'</a></span>';
					}
				}else{
					$content=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
					.$temArray[1].'</a></span>';
					if(isset($temArray[2]))	$content .= $temArray[2];
				}
			}
			
		}
		else
		{
			$content=$userurl.$temArray;
		}
		return $content;
	}
	
	/*消息内容分类截取显示*/
	function check_subcontent($uname, $userurl=NULL, $temArray, $temp){
			if(!$userurl)$uname=null;
			if(is_array($temArray))
			{
				$tempname = isset($temp['name']) ? $temp['name']:"";
				$tempname2 = isset($temp['name1']) ? $temp['name1']:"";

				$tempname1 = isset($temArray[2]) ? $temArray[2] : "";
				$temp1 = isset($temArray[1]) ? $temArray[1] : "";
				$tempname3 = isset($temArray[3]) ? $temArray[3] : "";
				if(count($temArray)==1){
					$suncontent = $this->sub_noticestr($uname ,$temArray[0],$tempname);
					$subcontent=$userurl.$temArray[0];
					if(isset($suncontent[0]))
					$subcontent .= '<span class="blueName"><a href="'.$temp['url'].'">'
					.$suncontent[0].'</a></span>';
				}else{
					
					if($tempname3 != null){
						if(isset($tempname2) &&  $tempname2 != null){
						$suncontent = $this->sub_noticestr($uname ,$temArray[0],$tempname,$temp1,$tempname2,$tempname1,$tempname3);
						$subcontent=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
							.$suncontent[0].'</a></span>';
							if(isset($suncontent[1])) $subcontent .= $suncontent[1];
							if(isset($suncontent[2])) $subcontent .= '<span class="blueName"><a href="'.$temp['url1'].'">'.$suncontent[2].'</a></span>';
							if(isset($suncontent[3])) $subcontent .= $suncontent[3];
							if(isset($suncontent[4])) $subcontent .= '<span class="blueName"><a href="'.$temp['url1'].'">'.$suncontent[4].'</a></span>';
						}else{
							$suncontent = $this->sub_noticestr($uname ,$temArray[0],$temp1,$tempname1,$tempname,$tempname3);
							$subcontent=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
							.$suncontent[0].'</a></span>';
							if(isset($suncontent[1])) $subcontent .= $suncontent[1];
							if(isset($suncontent[2])) $subcontent .= '<span class="blueName"><a href="'.$temp['url'].'">'.$suncontent[2].'</a></span>';
							if(isset($suncontent[3])) $subcontent .= $suncontent[3];
						}
					}else if($tempname2 != null){
						$suncontent = $this->sub_noticestr($uname ,$temArray[0],$tempname,$temp1,$tempname2,$tempname1);
						$subcontent=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'.$suncontent[0].'</a></span>';
						if(isset($suncontent[1]))
							$subcontent .= $suncontent[1];
						if(isset($suncontent[2]))
							$subcontent .= '<span class="blueName"><a href="'.$temp['url1'].'">'.$suncontent[2].'</a></span>';
						if(isset($suncontent[3]))
							$subcontent .= $suncontent[3];
					}else {
						$suncontent = $this->sub_noticestr($uname ,$temArray[0],$tempname,$temp1,$tempname1);
						if(isset($tempname1) && $tempname1 != null && $tempname != null && $temp1 != null){
							$subcontent=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
							.$suncontent[0].'</a></span>';
							if(isset($suncontent[1]))	$subcontent .= $suncontent[1];
							if(isset($suncontent[2]))	$subcontent .= '<span class="blueName"><a href="'.$temp['url'].'">'.$suncontent[2].'</a></span>';
						}else if($temp1 != null){
							if($tempname == null)
								$subcontent=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
											.$suncontent[0].'</a></span>';
							else
								$subcontent=$userurl.$temArray[0].'<span class="blueName"><a href="'.$temp['url'].'">'
											.$suncontent[0].'</a></span>';
								if(isset($suncontent[1]))	$subcontent .= $suncontent[1];
							
						}
					}
			
				}
				
			}
			else
			{
				$subcontent=$userurl.$temArray;
			}
			return $subcontent;
	}
	
	/**
	 * 截取通知函数
	 */
	function sub_noticestr($uname=NULL ,$tem,$temp =  NULL,$tem1 =  NULL,$tmp=NULL,$tmp3=NULL, $tmp4=NULL){
		$mycontent = array();
		$len = mb_strlen($uname);
		$len1 = mb_strlen($tem);
		$len2 = $len3 = $len4 = $len5 = $len6 = 0;
		$size = $len+$len1;
		if(isset($temp)){
			$len2 = 18 - $size;
			if($len2>=0)
			$mycontent[] = msubstr($temp, 0, $len2);
		}
		if(isset($tem1)){
			$len3 = 18 - $size - mb_strlen($temp);
			if($len3>=0)
			$mycontent[] = msubstr($tem1, 0, $len3);
		}
		if(isset($tmp)){
			$len4 = 18 - $size - mb_strlen($temp)- mb_strlen($tem1);

			if($len4>=0)
			$mycontent[] = msubstr($tmp, 0, $len4);
		}
		if(isset($tmp3)){
			$len5 = 18 - $size - mb_strlen($temp) -  mb_strlen($tem1) -  mb_strlen($tmp);
			if($len5>=0)
			$mycontent[] = msubstr($tmp3, 0, $len5);
		}
		if(isset($tmp4)){
			$len6 = 18 - $size - mb_strlen($temp) -  mb_strlen($tem1) - mb_strlen($tmp) - mb_strlen($tmp3);
			if($len6>=0)
			$mycontent[] = msubstr($tmp4, 0, $len6);
		}
		return $mycontent;
	}

	/**
	 * 删除通知
	 * @author gefeichao
	 * Enter description here ...
	 * @param  $id  通知id
	 */
	function del_notice($id = NULL) {
		if (! $id)
			return false;
		$sqldata ['is_delete'] = 0;
		$result = $this->mongo_db->where(array('_id' => new MongoId($id)))->update('notice', $sqldata);
		return $result;
	}
	
	/**
	 * 修改某子项的选中信息
	 * @author gefeichao
	 * @access public
	 * @param $ass  修改项
	 * @param $uid   用户uid
	 * @param $type  通知类型
	 * @return bool
	 */
	function noticeeditsetting($ass = NULL, $uid = NULL, $type = NULL) {
		if (! $uid || ! $type) {
			return false;
		}
		$valstr_rel =$this->mongo_db->where(array('uid' => $uid))->select(array('notice'))->get('expand');
		if (! $valstr_rel) {
			$arrays = array (array ($type, $ass ) );
			$data = array('un_msg' => 0,'un_notice' => 0, 'un_invite' => 0, 'notice' => $arrays, 'uid' => $uid);
			$this->mongo->insert('expand', $data);
			return true;
		} else {
			
			if (! $valstr_rel [0] ['notice']) {
				$arrays = array (array ($type, $ass ) );
				$values =  $arrays ;
			} else {
				$mstr =  $valstr_rel [0] ['notice'] ;
				$state = 0;
				foreach ( $mstr as $value ) {
					if ($value [0] == $type) {
						$value [1] = $ass;
						$state = 1;
					}
					$mstr1 [] = $value;
				}
				if ($state == 0) {
					$value = array ($type, $ass );
					$mstr  = $value;
					$mstr1[] = $mstr;
				}
				$values =  $mstr1 ;
			}
			$rel = $this->mongo_db->where(array('uid' => $uid))->update('expand', array('notice' => $values));
			
		}
	}
	
	
	/**
	 * 获取通知设置小分类所有类型
	 * @author gefeichao
	 * @date 2012-03-09
	 */
	function notice_s_setting() {
		$valstr_rel = $this->mongo_db->where(array('is_delete' => '1'))->get('notice_type_2');
		return $valstr_rel;
	}
	
	/**
	 * 获取通知设置大分类所有类型
	 * @author gefeichao
	 * @date 2012-03-09
	 */
	function notice_b_setting() {
		$valstr_rel = $this->mongo_db->where(array('is_delete' => '1'))->get('notice_type_1');
		return $valstr_rel;
	}
	
	/**
	 * 通知设置获取某模块的每一个模块
	 * @author gefeichao
	 * @access public
	 * @param $uid 用户uid
	 * @param $arrays 设置项集合
	 */
	function noticesettingscount($uid = NULL) {
		if (! $uid) {
			return false;
		}
		$arrays = $this->notice_s_setting();
		$valstr_rel = $this->mongo_db->where(array('uid' => $uid))->limit(1)->select(array('notice'))->get('expand');
		$values = "";
		if (! $valstr_rel || ! $valstr_rel [0]['notice']) {
			$values = $arrays;
		} else {
			//expand表读取用户设置 与系统设置比对  array('ask',array('ask_reply','ask_you'))
			foreach ($arrays as $item) {
				foreach ($valstr_rel as $value) {
					$value = $value['notice'];
					foreach ($value as $mitem) {
						if($item['bid'] == $mitem[0]){
							foreach ($mitem[1] as $v) {
								if($v == $item['key'])
									$item['value'] = 0;
							}
						}
					}
					
					$values[] = $item;
				}
			}
		}
		$b_setting = $this->notice_b_setting();
		foreach ($b_setting as $bs) {
			if($bs['value'] == 'web') continue;
			$result = array();$count =0;
			foreach ($values as $a) {
				if($bs['value'] == $a['bid'] ) {
					if($a['value'] == '1'){
						$count ++;
					}
					$result[] = $a;
				}
			}
			$bs['s_setting'] = $result;
			$bs['count'] = $count;
			$rel[] = $bs;
		}
		return $rel;
	}

	/**
	 * 通知设置获取某模块的每一个模块
	 * @author gefeichao
	 * @access public
	 * @param $uid 用户uid
	 * @param $arrays 设置项集合
	 */
	function noticesettingsweb($uid = NULL) {
		if (! $uid) {
			return false;
		}
		$arrays = $this->notice_s_setting();
		$valstr_rel = $this->mongo_db->where(array('uid' => $uid))->limit(1)->select(array('notice'))->get('expand');
		$values = "";
		if (! $valstr_rel || ! $valstr_rel [0]['notice']) {
			$values = $arrays;
		} else {
			//expand表读取用户设置 与系统设置比对  array('ask',array('ask_reply','ask_you'))
			foreach ($arrays as $item) {
				foreach ($valstr_rel as $value) {
					$value = $value['notice'];
					foreach ($value as $mitem) {
						if($item['bid'] == $mitem[0] && $item['bid'] == 'web'){
							foreach ($mitem[1] as $v) {
								if($v == $item['key'])
									$item['value'] = 0;
							}
						}
					}
					
					$values[] = $item;
				}
			}
		}
		
		$b_setting = $this->notice_b_setting();
		foreach ($b_setting as $bs) {
			if($bs['value'] != 'web') continue;
			$result = array();$count =0;
			foreach ($values as $a) {
				if($bs['value'] == $a['bid'] ) {
					
					if($a['value'] == '1'){
						$count ++;
						$result[] = $a;
					}
					
				}
			}
			foreach($result as $r){
				$type = explode('_',$r['key']);
				if($type[0] == 'dk'){
					$webdk[] = $r;
				}else if($type[0] == 'photo'){
					$webphoto[] = $r;
				}else if($type[0] == 'video' || $type[0] == 'upload'){
					$webvideo[] = $r;
				}else if( $type[0] == 'info'){
					$webinfo[] = $r;
				}else if($type[0] == 'event'){
					$webevent[] = $r;
				}
			}
			$bs['weblist0'] = $webdk;
			$bs['weblist1'] = $webphoto;
			$bs['weblist2'] = $webvideo;
			$bs['weblist3'] = $webinfo;
			$bs['weblist4'] = $webevent;
			$bs['count'] = $count;
			$rel[] = $bs;
		}
		return $rel;
	}

	/*测试通知*/
	function add_type(){
		$data = array(
			array('title' => 'Duankou', 'value' => 'dk', 'is_delete' => '1'),
			array('title' => '照片', 'value' => 'photo', 'is_delete' => '1'),
			array('title' => '视频', 'value' => 'video', 'is_delete' => '1'),
			array('title' => '博客', 'value' => 'blog', 'is_delete' => '1'),
			array('title' => '问答', 'value' => 'ask', 'is_delete' => '1'),
			array('title' => '信息流', 'value' => 'info', 'is_delete' => '1'),
			array('title' => '活动', 'value' => 'event', 'is_delete' => '1'),
			array('title' => '网页', 'value' => 'web', 'is_delete' => '1'),
			array('title' => '群组', 'value' => 'group', 'is_delete' => '1'),
		);
	$data1 = array(
		array('bid' => 'dk', 'key' => 'dk_guanzhu', 'value' => '1', 'name' => '有人关注了你', 'is_delete' => '1'),
		array('bid' => 'dk', 'key' => 'dk_addfriend', 'value' => '1', 'name' => '向你发送好友请求', 'is_delete' => '1'),
		array('bid' => 'dk', 'key' => 'dk_confirmfriend', 'value' => '1', 'name' => '确认你的朋友请求', 'is_delete' => '1'),
		array('bid' => 'dk', 'key' => 'dk_receiveinvite', 'value' => '1', 'name' => '有人接受了你的邀请加入Duankou', 'is_delete' => '1'),
		array('bid' => 'dk', 'key' => 'dk_leave_reply', 'value' => '1', 'name' => '有人回复了你的留言', 'is_delete' => '1'),
		array('bid' => 'dk', 'key' => 'dk_reply_comment', 'value' => '1', 'name' => '有人回复了你的评论', 'is_delete' => '1'),
		/*info*/
		array('bid' => 'info', 'key' => 'info_infocomment', 'value' => '1', 'name' => '有人评论了你的状态', 'is_delete' => '1'),
		array('bid' => 'info', 'key' => 'info_frowardinfo', 'value' => '1', 'name' => '有人转发了你的状态', 'is_delete' => '1'),
		array('bid' => 'info', 'key' => 'info_zaninfo', 'value' => '1', 'name' => '有人赞了你的状态', 'is_delete' => '1'),
		array('bid' => 'info', 'key' => 'info_frowardpic', 'value' => '1', 'name' => '有人评论了你的照片', 'is_delete' => '1'),
		array('bid' => 'info', 'key' => 'info_frowardalbum', 'value' => '1', 'name' => '有人转发了你的相册', 'is_delete' => '1'),
		array('bid' => 'info', 'key' => 'info_frowardvideo', 'value' => '1', 'name' => '有人转发了你的视频', 'is_delete' => '1'),
		array('bid' => 'info', 'key' => 'info_froward_blog', 'value' => '1', 'name' => '有人分享了你的日志', 'is_delete' => '1'),

		array('bid' => 'photo', 'key' => 'photo_albumcommenttoyou', 'value' => '1', 'name' => '有人评论了你的相册', 'is_delete' => '1'),
		array('bid' => 'photo', 'key' => 'photo_commenttoyou', 'value' => '1', 'name' => '有人评论了你的照片', 'is_delete' => '1'),
		array('bid' => 'photo', 'key' => 'photo_albumzan', 'value' => '1', 'name' => '有人赞了你的相册', 'is_delete' => '1'),
		array('bid' => 'photo', 'key' => 'photo_zan', 'value' => '1', 'name' => '有人赞了你的照片', 'is_delete' => '1'),
		
		array('bid' => 'blog', 'key' => 'blog_commenttoyou', 'value' => '1', 'name' => '有人评论了你的博客', 'is_delete' => '1'),
		array('bid' => 'blog', 'key' => 'blog_zan', 'value' => '1', 'name' => '有人赞了你的博客', 'is_delete' => '1'),
		array('bid' => 'blog', 'key' => 'blog_reprint', 'value' => '1', 'name' => '有人转载了你的博客', 'is_delete' => '1'),
		
		array('bid' => 'video', 'key' => 'video_commenttoyou', 'value' => '1', 'name' => '有人评论了你的视频', 'is_delete' => '1'),
		array('bid' => 'video', 'key' => 'video_zan', 'value' => '1', 'name' => '有人赞了你的视频', 'is_delete' => '1'),		
		array('bid' => 'video', 'key' => 'video_upload_true', 'value' => '1', 'name' => '你的视频上传成功', 'is_delete' => '1'),
		array('bid' => 'video', 'key' => 'video_upload_false', 'value' => '1', 'name' => '你的视频上传失败', 'is_delete' => '1'),

		array('bid' => 'video', 'key' => 'upload_check_true_video', 'value' => '1', 'name' => '你的视频审核成功', 'is_delete' => '1'),
		array('bid' => 'video', 'key' => 'upload_check_false_video', 'value' => '1', 'name' => '你的视频审核失败', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'upload_check_true_videoweb', 'value' => '1', 'name' => '网页的视频审核成功', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'upload_check_false_videoweb', 'value' => '1', 'name' => '网页的视频审核失败', 'is_delete' => '1'),
		
		/*收藏添加*/
		array('bid' => 'photo', 'key' => 'photo_favorite', 'value' => '1', 'name' => '收藏了你的照片', 'is_delete' => '1'),
		array('bid' => 'photo', 'key' => 'photo_albumfavorite', 'value' => '1', 'name' => '收藏了你的相册', 'is_delete' => '1'),
		array('bid' => 'video', 'key' => 'video_favorite', 'value' => '1', 'name' => '收藏了你的视频', 'is_delete' => '1'),
		array('bid' => 'blog', 'key' => 'blog_favorite', 'value' => '1', 'name' => '收藏了你的日志', 'is_delete' => '1'),

		/*ask*/
		array('bid' => 'ask', 'key' => 'ask_you', 'value' => '1', 'name' => '有人问你提问', 'is_delete' => '1'),
		array('bid' => 'ask', 'key' => 'ask_reply', 'value' => '1', 'name' => '有人关注了你的问题', 'is_delete' => '1'),
		array('bid' => 'ask', 'key' => 'ask_comment', 'value' => '1', 'name' => '有人评论了你的问题', 'is_delete' => '1'),
		array('bid' => 'ask', 'key' => 'ask_commentreply', 'value' => '1', 'name' => '有人评论并回答了你的问题', 'is_delete' => '1'),
		array('bid' => 'ask', 'key' => 'ask_commentyoufollow', 'value' => '1', 'name' => '有人评论了你关注的问题', 'is_delete' => '1'),
		array('bid' => 'ask', 'key' => 'ask_commentyoureply', 'value' => '1', 'name' => '有人评论并回答了你关注的问题', 'is_delete' => '1'),
		
		array('bid' => 'event', 'key' => 'event_invitejoin', 'value' => '1', 'name' => '邀请你参加了活动', 'is_delete' => '1'),
		array('bid' => 'event', 'key' => 'event_update', 'value' => '1', 'name' => '更新了活动', 'is_delete' => '1'),
		array('bid' => 'event', 'key' => 'event_cancel', 'value' => '1', 'name' => '取消了活动', 'is_delete' => '1'),
		array('bid' => 'event', 'key' => 'event_setting', 'value' => '1', 'name' => '将您设为了活动的管理员', 'is_delete' => '1'),
		array('bid' => 'event', 'key' => 'event_ban', 'value' => '1', 'name' => '禁止您参加活动', 'is_delete' => '1'),
		array('bid' => 'event', 'key' => 'event_answer', 'value' => '1', 'name' => '答复了您的活动', 'is_delete' => '1'),
		array('bid' => 'event', 'key' => 'event_edit', 'value' => '1', 'name' => '编辑了您的活动', 'is_delete' => '1'),
		array('bid' => 'event', 'key' => 'event_message', 'value' => '1', 'name' => '在您的活动上留言', 'is_delete' => '1'),
		array('bid' => 'event', 'key' => 'event_c_setting', 'value' => '1', 'name' => '取消了您在活动中的管理员身份', 'is_delete' => '1'),
		
		array('bid' => 'event', 'key' => 'event_c_manager', 'value' => '1', 'name' => '取消了你管理的活动', 'is_delete' => '1'),
		
		
		array('bid' => 'web', 'key' => 'dk_guanzhu_web', 'value' => '1', 'name' => '有人关注了你的网页', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'dk_creat_web', 'value' => '1', 'name' => '有人创建了网页并邀请你加入', 'is_delete' => '1'),
		
		array('bid' => 'web', 'key' => 'photo_albumcomment_web', 'value' => '1', 'name' => '有人评论了你的网页相册', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'photo_comment_web', 'value' => '1', 'name' => '有人评论了你的网页照片', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'photo_albumzan_web', 'value' => '1', 'name' => '有人赞了你的网页相册', 'is_delete' => '1'),
		
		array('bid' => 'web', 'key' => 'photo_zan_web', 'value' => '1', 'name' => '有人赞了你的网页照片', 'is_delete' => '1'),
		
		array('bid' => 'web', 'key' => 'video_comment_web', 'value' => '1', 'name' => '有人评论了你的网页视频', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'video_zan_web', 'value' => '1', 'name' => '有人赞了你的网页视频', 'is_delete' => '1'),	
		array('bid' => 'web', 'key' => 'upload_true_videoweb', 'value' => '1', 'name' => '你的网页视频转码成功', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'upload_false_videoweb', 'value' => '1', 'name' => '你的网页视频转码失败', 'is_delete' => '1'),
		
		array('bid' => 'web', 'key' => 'info_infocomment_web', 'value' => '1', 'name' => '有人评论了你的网页状态', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'info_frowardinfo_web', 'value' => '1', 'name' => '有人转发了你的网页状态', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'info_zaninfo_web', 'value' => '1', 'name' => '有人赞了你的网页状态', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'info_frowardpic_web', 'value' => '1', 'name' => '有人评论了你的网页照片', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'info_frowardalbum_web', 'value' => '1', 'name' => '有人转发了你的网页相册', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'info_frowardvideo_web', 'value' => '1', 'name' => '有人赞了你的网页视频', 'is_delete' => '1'),
		
		array('bid' => 'web', 'key' => 'event_message_web', 'value' => '1', 'name' => '在您的网页活动的墙上留言或上传照片、视频', 'is_delete' => '1'),
		
		array('bid' => 'web', 'key' => 'event_update_web', 'value' => '1', 'name' => '更新了你参加的网页活动', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'event_c_web', 'value' => '1', 'name' => '取消了你参加的网页活动', 'is_delete' => '1'),
		array('bid' => 'web', 'key' => 'event_ban_web', 'value' => '1', 'name' => '禁止你参加网页活动', 'is_delete' => '1'),
		
		array('bid' => 'web', 'key' => 'dk_del_web', 'value' => '1', 'name' => '你关注的网页被创建者删除了', 'is_delete' => '1'),
		);
	}
	
}