<?php
/**
 * 相册照片
 *
 * @author weijian
 * @version $Id: index.php 28679 2012-06-19 19:48:41Z guzb $
 */
class Index extends MY_Controller 
{
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('albummodel', 'album');
        $this->assign('uid', $this->uid);
        $this->assign('dkcode', $this->dkcode);
        $upload_url = '';
    	$this->config->load('album');
		$is_romote_upload = $this->config->item('is_romote_upload');
		
	    if($is_romote_upload === true){
	       	$upload_url = $this->config->item('romote_upload_url');
	    }
		
	    $upload_key = $this->config->item('security_key');
        if(isset($this->action_user)) {
        	$this->assign('action_uid', $this->action_uid);
       		$this->assign('action_dkcode', $this->action_dkcode);
        	$this->assign('action_userinfo', $this->action_user);
        }else{
	        $this->action_uid = $this->uid;
	        $this->action_dkcode = $this->dkcode;
        	$this->assign('action_uid', $this->uid);
       		$this->assign('action_dkcode', $this->dkcode);
        	$this->assign('action_userinfo', $this->user);
        }
        
        //$this->load->helper('common');
        $this->assign('login_username', $this->user['username']);
        $this->assign('login_avatar', get_avatar($this->uid));
        
        if($this->uid == $this->action_uid){
        	$author_url = mk_url('main/index/main', array('dkcode' => $this->dkcode));
            $album_baseurl = mk_url('album/index/main',array());
        }else{
        	$author_url = mk_url('main/index/main', array('dkcode' => $this->action_dkcode));
            $album_baseurl = mk_url('album/index/main', array('dkcode' => $this->action_dkcode));
        }
        $this->assign('author_url', $author_url);
        $this->assign('album_baseurl', $album_baseurl);
        $this->assign('upload_url', $upload_url);
        $this->assign('upload_key', $upload_key);
    }

    /**
     * 取得相册列表
     *
     * @author guzhongbin
     * data    2012-03-26
     * @access public
     * @param album_list array 相册列表信息
     */
    public function main()
    {
    	
        $album_list = $this->album->getAlbumList(array(
            'uid'	=>    $this->action_uid,
            'total' => true,
        	'is_delete'	=>	1,
        	'pagesize'	=> 16,
    		'web_id' => '0'
        ));
        if($album_list['total_num']){
            //设置照片列表地址
            foreach($album_list['list'] as $k => $v){
            	$album_list['list'][$k]['photo_lists_url'] = mk_url('album/index/photoLists', array('albumid'=>$v['id'], 'dkcode' => $this->action_dkcode)); 
            }
            
            $album_total_page = round($album_list['total_num'] / $album_list['pagesize']);
            $album_list['total_page'] = $album_total_page ? $album_total_page : 1;
        }else{
            $album_list = array();
            $album_total_page = 1;
            $album_list['list'] = array();
            $album_list['total_page'] = 1;
        }
        
        //修改结束
        if($this->uid == $this->action_uid){
            $is_author = 1;
        }else{
            $is_author = 0;
        }
    	
   		
        $all_album_list = $this->album->getAlbums($this->action_uid);
        $this->assign('is_author',$is_author);
        $this->assign('album_lists',$album_list);
        $this->assign('all_album_list',$all_album_list);
        $this->display('album');
    }
  
    /**
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param aid 相册ID
     * @param a_name 相册名称
     * @param a_addr 相册地址
     * @param a_explain 相册描述
     */
    public function modifyAlbum(){
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
        $aid = $this->input->post('albumID');
        $a_name = mb_substr($this->input->post('albumName'), 0, 50, 'utf-8');
        $a_name = htmlspecialchars($a_name, ENT_QUOTES);
        $a_name = preg_replace('/\s+/',' ',  preg_replace('/　+/',' ', $a_name));
        //$a_addr = $this->input->post('albumAddr');
        $a_explain = mb_substr($this->input->post('albumExplain'), 0, 140, 'utf-8');
        $a_explain= htmlspecialchars($a_explain, ENT_QUOTES);

        $data['name'] = $a_name;
        $data['a_address'] = '';
        $data['discription'] = $a_explain;
        $res = $this->album->updateAlbum($aid,$this->uid,$data);

        if(!$res){
            $this->ajaxReturn('', '编辑失败!', 0, 'json');
        }
        //设置权限 @weijian
        $this->load->model('accessmodel', '_access', true);
        $object_id = $aid;
        $permission = $this->input->post('permission');
        $flag = $this->_access->set($object_id, $permission, $this->uid);
        
        //修改搜索索引
        if($permission == 1 ) {
        	$this->album->albumSearchIndex($object_id, 0);
        }
        
        //修改相册时间线相册名字
        $this->album->updateAlbumInfoFlow($aid, $this->user['username'], false, $this->action_uid);
        
        $this->ajaxReturn('', 'success', 1, 'json');
    }


    /**
     * 删除相册
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param aid 相册ID
     * @param album_info  array 相册信息
     */
    public function delAlbum(){
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
        $aid = $this->input->post('albumID');
        
        //查询相册信息
        $album_info = $this->album->getAlbums($this->uid, $aid);
        if($album_info[0]['a_type'] != '0'){
        	$this->ajaxReturn('', '删除相册失败，默认相册无权限操作!', 0, 'json');
        }
        $res = $this->album->delAlbum($aid,$this->uid);
        if(!$res){
        	$this->ajaxReturn('', '删除相册失败!', 0, 'json');
        }
        
        //更新信息流
		$this->album->updateAlbumInfosFlow($aid, $this->user['username'], $this->uid);
		if($album_info[0]['object_type'] == 1) {
			$this->album->albumSearchIndexDel($aid);
		}
		$this->ajaxReturn(array('album_url' => mk_url('album/index/main', array('dkcode' => $this->dkcode))), 'success', 1, 'json');
    }

    /**
     * 相册排序
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param moverA_id 出发点
     * @param moverB_id 到达点
     */
    public function orderAlbum(){
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }

        $moverA_id = $this->input->post('moverA_id');
        $moverB_id = $this->input->post('moverB_id');
        $res = $this->album->orderAlbum($this->uid,$moverA_id,$moverB_id);
        if(!$res){
        	$this->ajaxReturn('', 'error!', 0, 'json');
        }
        $this->ajaxReturn('', 'success!', 1, 'json');
    }

    /**
     * 图片排序
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param moverA_id 出发点
     * @param moverB_id 到达点
     * @param album_id 到达点
     */
    public function orderPhoto(){
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }

        $moverA_id = $this->input->post('moverA_id');
        $moverB_id = $this->input->post('moverB_id');
        $album_id = $this->input->post('mover_ID');

        $res = $this->album->orderPhoto($moverA_id,$moverB_id,$album_id);
        if(!$res){
            $this->ajaxReturn('', 'error!', 0, 'json');
        }
        $this->ajaxReturn('', 'success!', 1, 'json');
    }

    /**
     * 照片列表
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param aid 相册ID
     * @param ab_info 相册信息
     */
    public function photoLists(){
    	
		if(!$_GET['albumid']){
            return false;
        }else{
        	$aid = $_GET['albumid'];
        	$aid = intval($aid);
        }
        
        //判断是否编辑页面跳转到此
        $isEditPageJump = 0;
        if(get_cookie('wherejump')){
        	$isEditPageJump = 1;
        	delete_cookie('wherejump');
        }
        
        //用户信息+相册信息@guzhongbin
    	$ab_info = $this->album->getAlbumList(array(
            'uid'	=>	$this->action_uid,
    		'id'	=>	$aid,
    		'is_delete'		=>	1,
    		'web_id' => '0'
        ));
    	if($ab_info['list']){
            $datainfo = $ab_info['list'][0];
            //小时时间显示
            $seconds = mktime() - $datainfo['temp_dateline'];
            if($seconds < 3600*24) {
            	if($seconds < 3600) {
            		if($seconds < 60) {
            			$datainfo['last_dateline'] = $seconds.'秒前';
            		}else {
            			$mins = floor($seconds/60);
            			$datainfo['last_dateline'] = $mins.'分钟前';
            		}
            		
            	}else {
            		$hours = floor($seconds/3600);
            		$datainfo['last_dateline'] = $hours.'小时前';
            	}
            	
            }
        }else{
            return false;
        }
        
        //权限判断
        $this->load->model('accessmodel', '_access', true);
        if(!$this->_access->isAllow($this->uid, $this->action_uid, $datainfo['object_type'], $datainfo['object_content'])){
            return false;
        }
        
        //-----------------------------关注操作时间接口start 李波2012/ 7/4---------------------------
		if( $this->uid != $this->action_uid ){
    		service('Relation')->updateFollowTime($this->uid, $this->action_uid);
		}
    	//-----------------------------关注操作时间接口end-------------------------------------------
    	
        //图片列表信息@guzhongbin
        $photo_list = $this->album->getPhotoList(array(
        	'uid' => $this->action_uid,
            'pagesize'	=>    50,
            'total'	=>    true,
        	'is_delete'		=>	1,
         	'aid' =>	$aid
        ));
        //照片视图地址
    	foreach($photo_list['data'] as $k => $v){
            if($v['uid']){
                $photo_list['data'][$k]['photo_view_url'] = mk_url('album/index/photoInfo',array('photoid'=>$v['id'], 'dkcode' => $this->action_dkcode));
            }
    	}
		//判断是否是当前相册用户
        if($this->uid == $this->action_uid){
            $is_author = 1;
        }else{
            $is_author = 0;
        }
        
        $photo_total_page = round($photo_list['total_num'] / $photo_list['pagesize']);
        $photo_list['total_page'] = $photo_total_page ? $photo_total_page : 1;
        
        //相册列表地址
        $album_url = mk_url('album/index/main', array('dkcode' => $this->action_dkcode));
        
        //照片列表地址
        $photolist_url = mk_url('album/index/photoLists', array('dkcode' => $this->action_dkcode, 'albumid' =>$aid));
        $all_album_list = $this->album->getAlbumList(array(
												            'uid'	=>	$this->action_uid,
        													'a_type' => 0,
												    		'is_delete'		=>	1,
												    		'web_id' => '0'));
       
        foreach($all_album_list['list'] as $key =>$album_info) {
        	if($album_info['id'] == $aid) {
        		unset($all_album_list['list'][$key]);
        	}
        }
        $this->assign('album_url',$album_url);
        $this->assign('photolist_url',$photolist_url);
        $this->assign('is_author',$is_author);
        $this->assign('isEditPageJump', $isEditPageJump);
        $this->assign('uid',$this->uid);
        $this->assign('userName',$this->user['username']);
        $this->assign('photo_lists',$photo_list);
        $this->assign('datainfo',$datainfo);
        $this->assign('all_album_list',$all_album_list['list']);
        $this->display('album_picList.html');
    }

    /**
     * 照片列表2
     *只显示有评论的照片
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param $aid 相册ID
     * 
     */
    public function photoGraphicLists(){
    	if(!$_GET['albumid']){
    		return FALSE;
    	}else{
    		$aid = $_GET['albumid'];
    		$aid = intval($aid);
    	}
       
        //用户信息+相册信息@guzhongbin
    	$ab_info = $this->album->getAlbumList(array(
            'uid'	=>	$this->action_uid,
    		'id'	=>	$aid,
    		'is_delete'	 =>  1,
    		'web_id' => '0'
    	
        ));
        $pagesize = 5;
        $lists = $this->album->getPhotoList(array('uid' => $this->action_uid, 'aid' => $aid, 'pagesize' => $pagesize, 'page' => 1, 'is_comment' => 1, 'total' => true, 'is_delete' => 1));
        
        //照片视图地址
    	foreach($lists['data'] as $k => $v){
            if($v['uid']){
                $lists['data'][$k]['photo_view_url'] = mk_url('album/index/photoInfo',array('photoid'=>$v['id'], 'dkcode' => $this->action_dkcode));
            }
    	}
        
        //取得总页数
        $pagecount = ceil($lists['total_num']/$pagesize);
        $object_arr = array_keys($lists['data']);
        
        if($ab_info['list']){
            $datainfo = $ab_info['list'][0];
        }else{
            return false;
        }
        //权限判断
        $this->load->model('accessmodel', '_access', true);
        if(!$this->_access->isAllow($this->uid, $this->action_uid, $datainfo['object_type'], $datainfo['object_content'])){
            return false;
        }
		
        //图片列表url
        $piclist_url = mk_url('album/index/photoLists', array('albumid' => $aid, 'dkcode' => $this->action_dkcode));
        
        //判断是否是当前用户
        if($this->uid == $this->action_uid){
            $is_author = 1;
        }else{
            $is_author = 0;
            
        }
        $album_url = mk_url('album/index/main', array('dkcode' => $this->action_dkcode));
        
    	$this->assign('piclist_url', $piclist_url);
        $this->assign('album_url',$album_url);
        $this->assign('is_author',$is_author);
        $this->assign('photo_lists',$lists);
        $this->assign('datainfo',$datainfo);
        $this->display('album_graphicList');
    }


    /**
     * 在照片列表中，取得某张照片的信息
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param $pid 照片ID
     */
    public function photoInfo(){
    	//验证photoid是否存在值
    	if(!isset($_GET['photoid'])){
    		return false;
        }else{
        	$pid = $_GET['photoid'];
        	$pid = intval($pid);
        	$permission = $this->input->get('permission');
        }
        
        $upload_url = mk_url('album/index/downloadPhoto', array('photoid' => $pid, 'dkcode' => $this->action_dkcode));
        $lists = $this->album->getPhotoInfo($pid);
        //通过照片获取相册信息
        $album_info = $this->album->getAlbums($this->action_uid,$lists['aid']);
        
        //照片列表地址
		$ptlists_url = mk_url('album/index/photoLists', array('albumid' => $lists['aid'], 'dkcode' => $this->action_dkcode));
     	
    	//判断是本人还是他人
        if($this->uid == $this->action_uid){
            $is_author = 1;
        }else{
            $is_author = 0;
        }
        
		//-----------------------------关注操作时间接口start 李波2012/ 7/4---------------------------
		if( $this->uid != $this->action_uid ){
    		service('Relation')->updateFollowTime($this->uid, $this->action_uid);
		}
    	//-----------------------------关注操作时间接口end-------------------------------------------
    	
        //首页端口配图相册非本人相册不能浏览上下图
		if(($album_info[0]['a_type'] == 3 && !$is_author) || $permission) {
			$this->assign('prev_url', null);
			$this->assign('next_url', null);
			$this->assign('prev_next_lists',null);
		}else{
			//照片视图上下页地址
	        $prev_next_lists = $this->album->getPhotoPrevNext($pid);
	        if($prev_next_lists['prev_pid']) {
	        	$prev_url = mk_url('album/index/photoInfo', array('photoid' => $prev_next_lists['prev_pid'], 'dkcode' => $this->action_dkcode));
	        	$this->assign('prev_url', $prev_url);
	        }
	    	if($prev_next_lists['next_pid']) {
	        	$next_url = mk_url('album/index/photoInfo', array('photoid' => $prev_next_lists['next_pid'], 'dkcode' => $this->action_dkcode));
	        	$this->assign('next_url', $next_url);
	    	}
	    	$this->assign('prev_next_lists',$prev_next_lists);
		}
		
		//删除照片地址
    	$ptdelete_url = mk_url('album/index/delPhoto', array('dkcode' => $this->action_dkcode, 'postpage' => 'photoLists'));
        
    	//照片不存在
        if($lists){
            $ptlists = $lists;
        }else{
        	$this->display('noPicTip');
            exit;
        }
        
     	//照片列表地址
        $all_album_list = $this->album->getAlbumList(array(
												            'uid'	=>	$this->action_uid,
        													'a_type' => 0,
												    		'is_delete'		=>	1,
												    		'web_id' => '0'));
       
        foreach($all_album_list['list'] as $key =>$single_album_info) {
        	if($single_album_info['id'] == $lists['aid']) {
        		unset($all_album_list['list'][$key]);
        	}
        }
        
        
        //详细照片地址
        $tempurl = mk_url('album/index/photoInfo', array('photoid' => $pid, 'dkcode' => $this->action_dkcode));
        $view_photo_url = mk_url('album/index/photoLists', array('albumid' => $lists['aid'], 'dkcode' => $this->action_dkcode, 'iscomment' => '1', 'jumpurl' => urlencode($tempurl)));
        
        //清空ie见面缓存问题
        header("Expires:Mon, 25 Jul 1998 05:00:00 GMT");
        header("Cache-Control:no-cache,must-revalidate");
        header("Pragma:no-cache");
        
        $this->assign('is_author',$is_author);
		$this->assign('uploadurl', $upload_url);
		$this->assign('ptlistsurl', $ptlists_url);
		$this->assign('all_album_list', $all_album_list['list']);
		$this->assign('view_photo_url', $view_photo_url);
		$this->assign('ptdelete_url', $ptdelete_url);
        $this->assign('username',$this->user['username']);
        $this->assign('ptlists',$ptlists);
        $this->assign('album_info',$album_info[0]);
        $this->display('album_picView');
    }

    
    /**
     * 设置封面
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param $aid 相册ID
     * @param $pid 照片ID
     */
    public function setAlbumCover(){
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
        $aid = $this->input->post('aid');
        $pid = $this->input->post('pid');

        $res = $this->album->setAlbumCover($aid,$pid,$this->uid);
        if(!$res){
            return false;
        }
        //die(json_encode($res));
        echo '1';
    }

    /**
     * 保存照片信息
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param Filedata 上传的文件信息流
     * @param $aid 相册ID
     * 
     * 将动态发布改成用户点击后才发布
     * 保持图片类型
     */
    
    public function addPhoto(){
        //上传图片过多时，需要设置超时时间
        set_time_limit(0);
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
        
        //创建相册
        if($this->input->post('createAlbum') === 'true') {
        	$aname = mb_substr($this->input->post('newAlbumName'), 0, 50, 'utf-8');
	        $aname = htmlspecialchars($aname, ENT_QUOTES);
	        $permission = $this->input->post('newAlbumPermission');
	        $txtaddr = '';
	        if(!$aname){
	        	$this->ajaxReturn('', '相册名称不能为空!', 0, 'json');
	        }
	        $aid = $this->album->addAlbum($this->uid,$aname,0,$txtaddr,null, $permission);
	        if(!$aid){
	            $this->ajaxReturn('', '新增相册失败!', 0, 'json');
	        }
            //增加相册索引
            if($permission == 1) {
            	$this->album->albumSearchIndex($aid, 0);
            }
        }else{
        	$aid = $this->input->post('albumId');
        }
        
        
        //得到pid值
        $picInfos = $this->input->post('picInfos');
        $coverPicId = $this->input->post('coverPicId');
        $coverPicId = intval($coverPicId);
        $aid = intval($aid);
        
        //相册信息
        $album_info = $this->album->getAlbums($this->uid, $aid);
        $photo_quality_cfg = config_item('photo_quality');
        $photo_quality = $photo_quality_cfg['normal'];
        
        //上传照片
        $temptime = time();
	    $res = $this->album->addBathPhoto($this->uid, $aid, $picInfos, 0, $photo_quality, $temptime);
    	if(!$res){
			$this->ajaxReturn('', '上传照片失败!', 0, 'json');
		}
		
		//设置封面
		$this->album->setAlbumCover($aid, $coverPicId, $this->uid);
    	if(!$res){
    		$this->ajaxReturn('', '设置封面失败!', 0, 'json');
		}
		
		//相册自动排序
		$this->album->autoUpdateAlbumOrder($this->uid, $aid);
		
		//检查有没有应用区封面
		$this->album->checkMainCover($this->uid);
		
		//取后八张照片添加到信息流
		$photo_num = count($picInfos);
    	foreach($picInfos as $picInfo) {
			$pids[] = $picInfo['picId'];
		}
		$flowpids = array_slice($pids, 0, 8);
		
		if($photo_num == 1) {
			$this->album->updateAlbumInfoFlow($aid, $this->user['username'], false, $this->uid);
			$this->album->addAlbumInfosFlow($this->uid, $aid, $flowpids[0], $flowpids, $this->user['username'], $photo_num);
		}else {
			$this->album->addAlbumInfosFlow($this->uid, $aid, $aid, $flowpids, $this->user['username'], $photo_num);
		}
		
		//更新搜索索引
		if($album_info[0]['object_type'] == 1) {
			$this->album->photoSearchIndex($pids);
			$this->album->albumSearchIndex($aid, 0);
		}
		
		$this->ajaxReturn(array('url' => mk_url('album/index/photoLists', array('albumid' => $aid, 'dkcode' => $this->dkcode))), 'success!', 1, 'json');
    }

    /**
     * 删除照片
     * 
     * @author guzhongbin
     * @data   2012/03/01
     * @access public
     * 
     * @param $pid 照片ID
     * @param $delphoarr 批量照片I
     */
    public function delPhoto() {
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
    	$delphoarr = $this->input->post('delArr');
    	$pid = $this->input->post('pic_id');
    	
    	if($this->uid == $this->action_uid){
            $is_author = 1;
        }else{
            $is_author = 0;
        }
    	if((!empty($pid) || !empty($delphoarr)) && $is_author) {
    		if($pid) {//删除单张照片
    			$photo_info = $this->album->getPhotoInfo($pid);
		        
		        //取得跳转到下一张照片的pid
		        $postpage = $this->input->get('postpage');
		        if($postpage == 'photoLists') {
		        	$get_nextID = $this->album->getDelphotoNext($pid);
		        	
	    			//删除当前照片
			        $aid = $this->album->delPhoto($pid,$this->uid);
			        if(!$aid){
			            return false;
			        }
			        //更新信息流
			        if($this->album->checkInfoFlowExist($pid, 'photo', $this->uid)) {
			        	$result = $this->album->delAlbumInfoFlow($pid, 'photo', $this->uid);
			        }else{
			        	$result = $this->album->updateAlbumInfoFlow($photo_info['aid'], $this->user['username'], false, $this->uid);
			        }
			        $album_info = $this->album->getAlbums($this->uid, $photo_info['aid']);
			        if($album_info[0]['object_type'] == 1) {
	    				$this->album->photoSearchIndexDel(array($pid));
						$this->album->albumSearchIndex($photo_info['aid'], 0);
						
	    			}
	    			
			        $result = true;
					if(!$result) {
						return false;
					}
			        if($get_nextID){
			        	$this->redirect('album/index/photoInfo', array('photoid' => $get_nextID, 'dkcode' => $this->action_dkcode));
			        }else{
			        	$this->redirect('album/index/photoLists', array('albumid' => $photo_info['aid'], 'dkcode' => $this->action_dkcode));
			        }
		        } 
		        
    		} else {//批量删除照片
    			$photo_info = $this->album->getPhotoInfo($delphoarr[0]);
    			$album_info = $this->album->getAlbums($this->uid, $photo_info['aid']);
    			
    			$aid = $this->album->delPhoto($delphoarr,$this->uid);
    			
    			if($album_info[0]['object_type'] == 1) {
    				$this->album->photoSearchIndexDel($delphoarr);
					$this->album->albumSearchIndex($photo_info['aid'], 0);
					
    			}
    			
    			//更新信息流
				$result = $this->album->updateTimestampsInfosFlow($photo_info['aid'], $delphoarr, $this->user['username'], $this->uid);
    			
    			if(!$aid){
    				$this->ajaxReturn('', '删除相册失败!', 0, 'json');
		        }
		        $this->ajaxReturn(array('album_url' => mk_url('album/index/photoLists', array('albumid' => $aid, 'dkcode' => $this->dkcode))), 'success!', 1, 'json');
		    }
    		
    	} else {
    		return false;
    	}
    }
    
    

    /**
     * 移动照片
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param $aid 目标相册ID
     * @param $pid 照片ID
     */
    public function movePhoto(){
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
    	$aid = $this->input->post('albumId');
    	$pids = $this->input->post('picId');
    	if(!is_array($pids)) {
    		$pids = array($pids);
    	}
    	
    	$first_pid = current($pids);
    	$photo_info = $this->album->getPhotoInfo($first_pid);
    	$get_nextID = false;
    	$photo_count = count($pids);
    	if($photo_count == 1) {
    		$get_nextID = $this->album->getDelphotoNext($pids[0]);
    	}
    	$res = $this->album->movePhoto($aid,$pids,$this->uid);
    	
    	$album_info = $this->album->getAlbums($this->uid, $photo_info['aid']);
    	if(in_array($album_info[0]['cover_id'], $pids)) {
    		$this->album->resetAlbumCover($photo_info['aid'], $this->uid);
    	}
    	
    	//更新信息流
    	$result = $this->album->updateAlbumInfoFlow($aid, $this->user['username'], false, $this->action_uid);
    	if($result == 'nofid') {
    		$this->album->addAlbumInfosFlow($this->uid , $aid, $aid, $pids, $this->user['username'], $photo_count);
    	}
    	$this->album->updateAlbumInfoFlow($photo_info['aid'], $this->user['username'], false, $this->action_uid);
		$result = $this->album->updateTimestampsInfosFlow($aid, $pids, $this->user['username'], $this->action_uid);
        
		//更新索引
		$beMoveAlbumInfo = $this->album->getAlbums($this->uid, $aid);
		if($photo_info['object_type'] == 1 && $beMoveAlbumInfo[0]['object_type'] != 1){
			$this->album->albumSearchIndexDel($photo_info['aid']);
		}elseif($photo_info['object_type'] != 1 && $beMoveAlbumInfo[0]['object_type'] == 1) {
			$this->album->albumSearchIndex($aid, 1);
		}else{
			$this->album->photoSearchIndexMove(array('id'=>$photo_info['aid'], 'type' =>0, 'visible' => 0), array('id'=>$aid, 'type' =>0, 'visible' => 0));
		}
		
        if(!$res){
        	$this->ajaxReturn('', '照片移动失败!', 1, 'json');
        }
        
        if($photo_count == 1) {
        	if($get_nextID) {
        		$photo_nextUrl = mk_url('album/index/photoInfo', array('photoid' => $get_nextID, 'dkcode' => $this->action_dkcode));
        		$this->ajaxReturn(array('photo' =>'single', 'photoNext' => $photo_nextUrl), '照片移动成功!', 1, 'json');
        	}else{
        		$photo_list = mk_url('album/index/photoLists', array('albumid'=>$photo_info['aid'], 'dkcode' => $this->action_dkcode));
        		$this->ajaxReturn(array('photo' =>'single', 'photoNext' => $photo_list), '照片移动成功!', 1, 'json');
        	}
        }else{
        	$this->ajaxReturn(array('photo' =>'more'), '照片移动成功!', 1, 'json');
        }
        
    }
 
    /**
     * 下载照片
     *
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     * @param $pid 照片ID
     */
    public function downloadPhoto(){
        if(!$this->uid){
            echo "<script>alert('请先登录!');</script>";
        }

        $pid = $this->input->get('photoid');
        $photo_info = $this->album->getPhotoInfo($pid);
        if(!$photo_info){
            echo "<script>alert('error!');</script>";
        }

        //取得照片原图
        $this->load->fastdfs('album','', 'fdfs');
        $photores = $this->fdfs->downloadFileBuff($photo_info['filename'].'.'.$photo_info['type'], $photo_info['groupname']);
        if ($photores) {
            header('Content-Description: File Transfer');
            header("Content-Type: application/force-download");
            if(strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")){
                $filename = urlencode($photo_info['pname']).'.'.$photo_info['type'];
                $filename = str_replace("+", "%20", $filename);
            }else{
                $filename = $photo_info['pname'].'.'.$photo_info['type'];
            }
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length:'.$photo_info['size']);
            //ob_clean();
            //flush();
            echo $photores;
            exit;			 
        }else{
            echo "<script>alert('文件不存在!');</script>";
        }
		$this->ajaxReturn('', 'success!', 1, 'json');
    }


    /**
     * 设置封面
     * 
     * @author guzhongbin
     * @date   2012-03-26
     * @access public
     */
    public function setCover()
    {	
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
        $pid = $this->input->get('pid');
        $aid = $this->input->get('aid');
        $return = array();
        if(empty($pid) || empty($aid) || empty($this->uid)){
            $this->ajaxReturn('', '参数错误', 0, 'json');
        }else{
            $flag = $this->album->setAlbumCover($aid, $pid, $this->uid);
            if($flag !== false){
	            //搜索索引soap
		    	$album_info = $this->album->getAlbums($this->uid, $aid);
		    	if($album_info[0]['object_type'] == 1 ) {
		    		$this->album->albumSearchIndex($aid, 0);
		    	}
		    	
                $this->ajaxReturn('', '封面设置成功', 1, 'json');
            }else{
                $this->ajaxReturn('', '封面设置失败', 0, 'json');
            }
        }
       $this->ajaxReturn('', '封面设置成功', 1, 'json');
    }

	/**
     * 更多相册
     * 
     * @author guzhongbin
     * @access public
     */
   	public function albumMore(){
   		//所查询第几页数据
   		$page = $this->input->post('page');
   		$permissionType = $this->action_uid == $this->uid ?$this->input->post('permissionType') : null;
    	$album_list = $this->album->getAlbumList(array(
            'uid'	=>    $this->action_uid,
            'total' => true,
    		'page' => $page,
        	'is_delete'	=> 1,
    	    'pagesize' => 16,
    		'web_id' => '0',
    		'permissionType' => $permissionType,
        ));
        if(!isset($album_list['list'])){
        	$this->ajaxReturn(array('content' => '', 'isend' => true), '', 1, 'json');
        }
   		//设置照片列表地址
        foreach($album_list['list'] as $k => $v){
        	$album_list['list'][$k]['photo_lists_url'] = mk_url('album/index/photoLists', array('albumid'=>$v['id'], 'dkcode' => $this->action_dkcode)); 
        	//echo mk_url('album/index/photoLists', array('albumid'=>$v['id'], 'dkcode' => $this->action_dkcode)); 
        }

        //是否最后page
        if($album_list['total_num']) {
	       $is_end = false;
	        $album_total_page = ceil($album_list['total_num'] / $album_list['pagesize']);
        	$album_list['total_page'] = $album_total_page ? $album_total_page : 1;
	        if($page >= $album_list['total_page']) {
	        	$is_end = true;
	        }
        }
        
   		if($album_list['list']) {
   			$this->ajaxReturn(array('content' => $album_list['list'], 'isend' => $is_end), '', 1, 'json');
        } 
   	}
   	
	/**
     * 更多照片
     * 
     * @author guzhongbin
     * @access public
     */
   	public function photosMore(){
   		//所查询第几页数据
   		$page = $this->input->post('page');
   		$aid = $this->input->get('albumid');
		
   		if(empty($page) || empty($aid)) 
   			return false;
   		
        $photo_list = $this->album->getPhotoList(array(
        	'uid' => $this->action_uid,
            'pagesize' => 20,
        	'is_delete'	=> 1,
         	'aid' => $aid,
        	'page' => $page,
            'total' => true,
        ));
        
        
        //判断如果当前相册为默认相册则不返回数据
        $user_album_info = $this->album->getAlbumList(array(
        	'uid' => $this->action_uid,
            'id'	=>	$aid,
        	'is_delete'	=> 1,
    		'web_id' => '0'
        ));
        if(!empty($user_album_info['list'])) {
	        if($user_album_info['list'][0]['a_type'] != 0){
	        	$album_data['list'] = 'f';
	        } else {
	        	$album_data = $this->album->getAlbumList(array(
		            'uid'	=>	$this->action_uid,
		    		'is_delete'	=> 1,
		        	'a_type' => 0,
	    			'web_id' => '0'
		        ));
	        }
	         $photo_list['album_list']  = $album_data['list'];
        }
        
        
   		//是否最后page
        if($photo_list['total_num']) {
	        $isend = false;
	        $photo_total_page = ceil($photo_list['total_num'] / $photo_list['pagesize']);
        	$photo_list['total_page'] = $photo_total_page ? $photo_total_page : 1;
	        if($page >= $photo_list['total_page']) {
	        	$isend = true;
	        }
        }
        
   	 	//照片视图地址
    	foreach($photo_list['data'] as $k => $v){
            if($v['uid']){
                $photo_list['data'][$k]['photo_view_url'] = mk_url('album/index/photoInfo',array('photoid'=>$v['id'], 'dkcode' => $this->action_dkcode));
            }
    	}
        
        if($photo_list['data']) {
        	$this->ajaxReturn(array('content' => $photo_list['data'], 'isend' => $isend), '', 1, 'json');
        } else {
        	$this->ajaxReturn(array('content' => $photo_list['data'], 'isend' => 0), '', 1, 'json');
        } 
   	}
   	
   	/**
   	 * 评论列表更多照片
   	 * 
   	 * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
   	 * @param page string 照片页数
   	 * @param aid 相册Id
   	 * 
   	 */
   	
   	public function graphicPhotoMore() {
   		//所查询第几页数据
   		$page = $this->input->post('page');
   		$aid = $this->input->get('albumid');
		
   		if(empty($page) || empty($aid)) 
   			return false;
   		
        $photo_list = $this->album->getPhotoList(array(
        	'uid' => $this->action_uid,
            'pagesize' => 5,
        	'is_comment' => 1,
        	'is_delete'	=> 1,
         	'aid' => $aid,
        	'page' => $page,
            'total' => true,
        ));
        
        
   	//是否最后page
        if($photo_list['total_num']) {
	        $last = false;
	        $photo_total_page = ceil($photo_list['total_num'] / $photo_list['pagesize']);
        	$photo_list['total_page'] = $photo_total_page ? $photo_total_page : 1;
	        if($page >= $photo_list['total_page']) {
	        	$last = true;;
	        }
        }
        
   	 	//照片视图地址
    	foreach($photo_list['data'] as $k => $v){
            if($v['uid']){
                $photo_list['data'][$k]['photo_view_url'] = mk_url('album/index/photoInfo',array('photoid'=>$v['id'], 'dkcode' => $this->action_dkcode));
            	$photo_list['data'][$k]['action_uid'] = $this->action_uid;
            }
    	}
        
        if($photo_list['data']) {
        	$this->ajaxReturn(array('content' => $photo_list['data'], 'isend' => $last), '', 1, 'json');
        }
   		
   	}
  
   	/**
   	 * 编辑照片名字
   	 * 
   	 * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
   	 * @id  照片id
   	 * @info照片名称 
   	 */
   	public function editPhotoName() {
   		if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
   		$picId = $this->input->post('id');
   		$picName =  mb_substr($this->input->post('info'), 0 , 50, 'utf-8');
   		$picName = htmlspecialchars($picName, ENT_QUOTES);
   		if(!$picId) {
   			return false;
   		}
   		
   		//搜索索引soap
   		$photo_info = $this->album->getPhotoInfo($picId);
    	$album_info = $this->album->getAlbums($this->uid, $photo_info['aid']);
    	if($album_info[0]['object_type'] == 1 ) {
    		$this->album->photoSearchIndex($picId);
    	}
   		
   		//如果名字为空，取名为未命名
   		if(!isset($picName)) {
   			$picName = '未命名';
   		}
   		if(!$this->album->updatePhotoName($picId, $picName, $this->uid)) {
   			$this->ajaxReturn('', '修改照片名失败', 0, 'json');
   		}
   		
   		//修改时间线上的名字
   		if($this->album->checkInfoFlowExist($picId, 'photo', $this->uid)) {
   			$this->album->updatePhotoInfoFlow($photo_info['aid'], $picId, $this->user['username'], $this->uid);
   		}
   		$this->ajaxReturn($picName, 'success!', 1, 'json');
   	}
   	
	/**
   	 * 修改照片描述
   	 * 
   	 * @author guzhongbin
   	 * @access public
   	 * @date 2012-03-20
   	 * @param integer picId 照片Id
   	 * @param varchar $picDesc 照片描述
   	 */
   	public function editPhotoDesc() {
   		if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
   		$picId = $this->input->post('id');
   		$picDesc = mb_substr($this->input->post('info'), 0, 140, 'utf-8');
   		$picDesc = htmlspecialchars($picDesc, ENT_QUOTES);
   		if(!$picId) {
   			return false;
   		}
   		
   		//搜索索引soap
   		$photo_info = $this->album->getPhotoInfo($picId);
    	$album_info = $this->album->getAlbums($this->uid, $photo_info['aid']);
    	if($album_info[0]['object_type'] == 1 ) {
    		$this->album->photoSearchIndex($picId);
    	}
   		
   		//更改照片描述
   		if(!$this->album->updatePhotoDesc($picId, $picDesc, $this->uid)) {
   			$this->ajaxReturn('', '修改照片描述失败', 0, 'json');
   		}
   		$this->ajaxReturn($picDesc, 'success!', 1, 'json');
   	}
   	
   	/**
   	 * 修改相册描述
   	 * 
   	 * @author guzhongbin
   	 * @access public
   	 * @date 2012-03-20
   	 * @param integer albumId 相册Id
   	 * @param varchar $albumDesc 相册说明
   	 */
   	public function editAlbumDesc() {
   		if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
		
   		$albumId = $this->input->post('id');
   		$albumDesc = mb_substr($this->input->post('info'), 0, 140, 'utf-8');
   		$albumDesc = htmlspecialchars($albumDesc, ENT_QUOTES);
   		if(!$albumId) {
   			return false;
   		}
		
   		//搜索索引soap
    	$album_info = $this->album->getAlbums($this->uid, $albumId);
    	if($album_info[0]['object_type'] == 1 ) {
    		$this->album->albumSearchIndex($albumId, 0);
    	}
   		
   		if(!$this->album->updateAlbumDesc($albumId, $albumDesc, $this->uid)) {
   			$this->ajaxReturn('', '修改相册说明失败!', 0, 'json');
   		}
   		$this->ajaxReturn($this->input->post('info'), 'success!', 1, 'json');
   	}

   	
   	public function changeAppPermissions(){
   		
   		$menu_id = $this->input->post('menu_id');
   		$weight = $this->input->post('weight');
   		
   		//确保用户本人操作
   		if($this->uid == $this->action_uid){
            $jsonstr=json_encode(array('1','2','3','4'));
			$data = array(
			   'uid' => $this->uid,
			   'menu_id' => 1,
				'weight'=>0,//自定义
				'weight_content'=>$jsonstr,
			);
			$result = do_call('UserMenuPurview', 'setUserMenuPruview',$data);
   		}
   		$this->ajaxReturn('', '没有权限!!', 0, 'json');
   	}
 	
	/**
	 * 在详细照片中的设置首页应用区相册封面
	 * 
	 * @author guzhongbin
	 * @data 2012-03-30
	 * @access public
	 * @param $pid 照片Id
	 *
	 */
	public function viewsetMainCover()
	{
		if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
	    $pid = $this->input->get('pid');
	    if(!$pid) {
	    	return false;
	    }
	    
	    //得到照片信息
		$photo_info = $this->album->getPhotoInfo($pid);
		if(!isset($photo_info)) {
			$photo_info = $this->album->getMainCover($this->uid);
		}
		
		$res = $this->album->setMainCover($photo_info['pid'], $this->uid);
		if(!$res){
			return false;
		}
		$this->load->model("appcovermodel", "appCover");
		
	    if($photo_info === false){
	        $info = $this->appCover->mergeImages(null, null, $this->uid, 'album');
	    }else{
	    	$info = $this->appCover->mergeImages($photo_info['groupname'], $photo_info['filename'].'.'.$photo_info['type'], $this->uid, 'album');
	    }
	   
	    if($info === true) {
	    	$this->ajaxReturn('', '设置首页应用区相册封面成功', 1, 'json');
	    }else {
	    	$this->ajaxReturn('', '设置首页应用区相册封面不成功', 0, 'json');
	    }
	    
	}
	
    /**
     * 旋转图片
     * 
     * @author vicente
     * @access public
     */
    public function rotate()
    {	
    	if($this->uid != $this->action_uid){
    		$this->ajaxReturn('', '当前用户非网页管理员，不能进行操作！', 0, 'json');
        }
        $id = $this->input->post('picId');
        $direction = $this->input->post('direction');

		if(empty($id) || empty($direction) || !in_array($direction, array('left', 'right'))){
                $this->ajaxReturn('', '非法操作！', 0, 'json');
		}
            
        $degree = $direction == 'right' ? 90 : 270;
            
     	if($return = $this->album->rotate($id, $degree)){
            $data = array(
            	'picUrl'=>$return['picUrl']
            );
            	
            //检查此照片是否是单张照片信息流
		    if($this->album->checkInfoFlowExist($id, 'photo', $this->uid)){
		        $this->album->updatePhotoInfoFlow($return['aid'], $id, $this->user['username'], $this->uid);
		    }
		    $this->album->updateAlbumInfoFlow($return['aid'], $this->user['username'], false, $this->uid);
            $this->ajaxReturn($data, '保存成功！', 1);
        }else{
            $this->ajaxReturn('', '保存失败！', 0);
        }
    }

	public function uploadtest() {
		if(isset($_FILES["filename"])) {
			$file = $_FILES['filename'];
			$pt = array(); //保存上传图片信息，方便存入数据库中 
			$pt['type'] = getImgType(strtolower(substr($file['name'],(strrpos($file['name'],'.')+1))));
	
			//上传原图到fdfs
			$this->load->model("fdfsmodel", "fdfs");
			$org_pic_info = $this->fdfs->upload_filename($file['tmp_name'], $pt['type']);
			dump($org_pic_info);
			exit;
		}
		$this->display('uploadtest');
	}
	
	public function test1() {
		$pid = intval($this->input->get('pid'));
		$tempurl = mk_url('album/index/photoInfo', array('photoid' => $pid, 'dkcode' => $this->action_dkcode));
		$lists = $this->album->getPhotoInfo($pid);
		$view_photo_url = mk_url('album/index/photoLists', array('albumid' => $lists['aid'], 'dkcode' => $this->action_dkcode, 'iscomment' => '1', 'jumpurl' => urlencode($tempurl)));
		header("Location:".$view_photo_url);
		//$result = $this->album->test();
	}
}
/* End of file album.php */
/* Location: ./app/modules/home/controllers/album.php */