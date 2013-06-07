<?php
/**
 * 照片
 *
 * @author vicente
 * @version $Id
 */
class Photo extends MY_Controller 
{ 
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('common');
        $this->load->model('albummodel', 'album');
        $this->load->model('photomodel', 'photo');
        
        $this->assign('uid', $this->uid);
        $upload_url = '';
        $upload_key = '';

        if($this->uid == $this->action_uid){
        	$this->assign('login_username', $this->web_info['name']);
        	$this->assign('login_avatar', get_webavatar($this->uid, 's', $this->web_id));
        	$login_userpageurl = getWebUrl($this->web_id);

        	$this->config->load('album');
    		$is_romote_upload = $this->config->item('is_romote_upload');
	        if($is_romote_upload === true){
	        	$upload_url = $this->config->item('romote_upload_url');
	        }
	        
	        $upload_key = $this->config->item('security_key');
        }else{
        	$this->assign('login_username', $this->user['username']);
        	$this->assign('login_avatar', get_avatar($this->uid, 's'));
        	$login_userpageurl = getUserUrl($this->dkcode);
        }

        $this->assign('login_userpageurl', $login_userpageurl);
        $this->assign('web_id', $this->web_id);
        $this->assign('action_uid', $this->action_uid);
        $this->assign('action_dkcode', $this->action_dkcode);
        $this->assign('action_userinfo', $this->action_user);
        $this->assign('web_info', $this->web_info);  
        $this->assign('upload_url', $upload_url);
        $this->assign('upload_key', $upload_key);
        
		$album_baseurl = mk_url('walbum/album/index', array('web_id'=>$this->web_id));
        $this->assign('album_baseurl', $album_baseurl);
    }

    /**
     * 相册下的照片列表
     *
     * @author vicente 
     * @access public
     */
    public function index()
    {
    	$return_struct = array(
    		'status'   => 0,
    		'code'     => 501,
    		'msg'      => 'Not Implemented',
    		'content'  => array()
    	);
        try{
            $is_author = 0;
            $album_id = $this->input->get('albumid');
            $photo_list = array();
            if(empty($album_id) || WEB_ID == 0){
                throw new MY_Exception("非法请求！");
            }

            //判断是否编辑页面跳转到此
            $isEditPageJump = 0;
            if(get_cookie('wherejump')){
                $isEditPageJump = 1;
                delete_cookie('wherejump');
            }

            $album_info = $this->album->get($album_id);
            if(empty($album_info) || $album_info['uid'] != $this->action_uid){
                throw new MY_Exception("此相册已经不存在！");
            }

            $params = array(
                'where'   =>  array(
                    'uid'       =>  $this->action_uid,
                    'is_delete'	=>	1,
                    'aid'       =>	$album_id
                ),
                'limit'   =>  array(
                    'pagesize'	=>  20
                ) 
            );

            $photo_num = $this->photo->count($params);

            if($photo_num > 0){
                //图片列表信息
                $photo_list = $this->photo->index($params);
                //照片视图地址
                foreach($photo_list as $k => $v){
                    if($v['uid']){
                        $photo_list[$k]['photo_view_url'] = mk_url('walbum/photo/get', array('photoid'=>$v['id'], 'web_id' => $this->web_id));
                    }
                }

                $total_page= round($photo_num / $params['limit']['pagesize']);
            }
            
        	//判断是否是当前网页用户
			if($this->uid == $this->action_uid){
            	$is_author = 1;
            }
            $params = array(
                'where'   =>  array(
                    'uid'       =>  $this->action_uid,
                    'is_delete'	=>	1,
                    'web_id'       =>	$this->web_id,
            		'a_type' => 0,
                )
            );
			$all_album_list = $this->album->index($params);
            $return_data['data'] = $photo_list;
            $album_url = mk_url('walbum/album/index', array('web_id'=>$album_info['web_id']));
            $photo_url = mk_url('walbum/photo/index', array('albumid' =>$album_id, 'web_id'=>$album_info['web_id']));
            $this->assign('album_url',$album_url);
            $this->assign('photolist_url',$photo_url);
            $this->assign('is_author',$is_author);
            $this->assign('isEditPageJump', $isEditPageJump);
            $this->assign('uid',$this->uid);
            $this->assign('photo_lists',$return_data);
            $this->assign('datainfo',$album_info);
            $this->assign('all_album_list',$all_album_list);
            $this->display('album_picList.html');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * ajax方式获取更多照片
     * 
     * @author vicente
     * @access public
     */
    public function getPhotoMore()
    {	
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $is_end = false;
            //所查询第几页数据
            $page = $this->input->post('page');
            $album_id = $this->input->get('albumid');
            if(empty($page) || empty($album_id) || WEB_ID == 0){
                throw new MY_Exception("非法操作！");
            }
            
            $params = array(
                'where'  => array(
                    'uid'       => $this->action_uid,
                    'is_delete'	=> 1,
                    'aid'       => $album_id,
                ),
                'limit'  => array(
                    'pagesize' => 20,
                    'page'     => $page,
                )
            );

            $photo_num = $this->photo->count($params);
            $photo_list = $this->photo->index($params);

            //是否最后page
            if($photo_num > 0) {
                $total_page = ceil($photo_num / $params['limit']['pagesize']);
                if($page >= $total_page) {
                    $is_end = true;
                }
            }

            //照片地址
            foreach($photo_list as $k => $v){
                if(!empty($v['id'])){
                    $photo_list[$k]['photo_view_url'] = mk_url('walbum/photo/get', array('photoid'=>$v['id'], 'web_id' => $this->web_id));
                }
            }

            $data = array(
            	'content'  => $photo_list,
            	'isend'    => $is_end
            );
            
            $this->ajaxReturn($data);
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * ajax方式获取更多带评论的照片
     * 
     * @author vicente
     * @access public
     */
    public function getCommentPhotoMore() 
    {
        $return_struct = array(
        	'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
        	$is_end = false;
            $page = $this->input->post('page');
            $album_id = $this->input->get('albumid');
            if(empty($page) || empty($album_id) || WEB_ID == 0) {
                throw new MY_Exception("非法操作！");
            }

            $params = array(
                'where'  => array(
                    'uid'        => $this->action_uid,
                    'is_delete'	 => 1,
                    'is_comment' => 1,
                    'aid'        => $album_id,
                ),
                'limit'  => array(
                    'pagesize' => 5,
                    'page'     => $page,
                )
            );

            $photo_num = $this->photo->count($params);
            $photo_list = $this->photo->index($params);

            //是否最后page
            if($photo_num > 0) {
                $total_page = ceil($photo_num / $params['limit']['pagesize']);
                if($page >= $total_page) {
                    $is_end = true;
                }
            }

            //照片视图地址
            foreach($photo_list as $k => $v){
                if(!empty($v['id'])){
                    $photo_list[$k]['photo_view_url'] = mk_url('walbum/photo/get', array('web_id'=>$this->web_id,'photoid'=>$v['id']));
                }
            }
            
            $data = array(
            	'content'  => $photo_list,
            	'isend'    => $is_end,
            );
            
            $this->ajaxReturn($data);
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 新增照片信息
     *
     * @author vicente
     * @access public
     */
	public function add()
    {
        $return_struct = array(
        	'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        ); 
        try{
        	set_time_limit(0);
        	//只有网页管理员才可以添加照片
        	if($this->uid != $this->action_uid){
        		throw new MY_Exception("当前用户非网页管理员，不能进行操作！");
        	}
        	
        	$is_exist_album = false;
        	
	        //创建相册
	        if($this->input->post('createAlbum') === 'true') {
		        $request_data['newAlbumName'] = htmlspecialchars($this->input->post('newAlbumName'), ENT_QUOTES);
				$request_data['uid'] = $this->uid;
				$request_data['web_id'] = $this->web_id;
		        if(!$request_data['newAlbumName']){
		            throw new MY_Exception("相册名称不能为空！");
		        }
		        $album_id = $this->album->add($request_data);
		        if(empty($album_id)){
					throw new MY_Exception("新增相册失败！");
		        }
	            //增加相册索引
	            //搜索索引
            	$album_info = array(
	            	'id'       => $album_id,
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	            
	            service('RestorationSearch')->restoreAlbumInfo($album_info);
	        }else{
	        	$album_id = $this->input->post('albumId');
	        	
	        	$is_exist_album = true;
	        }
        	
            $request_data = $this->input->post();
            $pic_data = $request_data['picInfos'];
		
            if(empty($album_id) || empty($pic_data)) {
                throw new MY_Exception("操作非法！");
            }
           
            //为了图片以上传时顺序显示，逆转数组
            $pic_data = array_reverse($pic_data);
			if($cover_id = $this->input->post('coverPicId')) {
				$cover_id = intval($cover_id);
			}else{
				$pic = end($pic_data);
				$cover_id = $pic['picId'];
			}
            
            $count = count($pic_data);
			$temptime = time();
            $data = array(
                'uid'            => $this->uid,
                'album_id'       => $album_id,
            	'dateline'       => $temptime
            );
            
            if($count == 1){
            	$data['id'] = $pic_data[0]['picId'];
				$data['description'] = $pic_data[0]['picDesc'];
                if(!$this->photo->edit($data)){
                	$this->ajaxReturn('', '上传照片失败！', 0);
                }
            }else{
            	if(!$this->photo->batch_edit($pic_data, $data)){
            		$this->ajaxReturn('', '上传照片失败！', 0);
                }
            }
            
            //设置相册封面和应用区封面
            $this->photo->setAutoCover($album_id, $cover_id);
            
            //相册自动排序
            //$this->album->autoUpdateAlbumOrder($this->uid, $album_id);
            
            //搜索索引
            if(!empty($pic_data)){
            	$return = array();
            	foreach ($pic_data as $v){
            		$return[] = array('id'=>$v['picId'], 'type'=> 1);
            	}
            	
            	service('RestorationSearch')->restorePhotoInfo($return);
            	
            	$album_data = array(
	            	'id'       => $album_id,
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	
	            service('RestorationSearch')->restoreAlbumInfo($album_data);
            }
            
            //积分
            //service('credit')->album();
            
            //信息流处理
        	if($count == 1){
            	$this->photo->updatePhotoInfoFlow($this->dkcode, $pic_data[0]['picId'], $temptime, $this->web_info['name'], $this->web_id);
            	$is_exist_album && $this->album->updateAlbumInfoFlow($this->dkcode, $album_id, $this->web_info['name'], $this->web_id, false, false);
            }else{
            	$this->album->updateAlbumInfoFlow($this->dkcode, $album_id, $this->web_info['name'], $this->web_id);
            }
            
            $data = array(
            	'url' => mk_url('walbum/photo/index', array('web_id' => $this->web_id,'albumid' => $album_id))
            );
            
            $this->ajaxReturn($data, '上传照片成功！');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 只显示有评论的照片列表
     * @author vicente
     * @access public
     */
    public function getCommentList()
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $album_id = $this->input->get('albumid');
            $is_author = 0;
            $photo_list = array();
            if(empty($album_id) || WEB_ID == 0){
                throw new MY_Exception("非法请求！");
            }

            $album_info = $this->album->get($album_id);
            if(empty($album_info) || $album_info['uid'] != $this->action_uid){
                throw new MY_Exception("此相册信息有误！");
            }

            $params = array(
                'where' => array(
                    'uid'     	 =>	$this->action_uid,
                    'aid'     	 =>	$album_id,
                    'is_delete'	 => 1,
                    'is_comment' => 1
                ),
                'limit' => array(
                    'pagesize'   => 5
                )        
            );
            $photo_num = $this->photo->count($params);
            if($photo_num > 0){
                $photo_list = $this->photo->index($params);
                //照片地址
                foreach($photo_list as $k => $v){
                    if(!empty($v['id'])){
                        $photo_list[$k]['photo_view_url'] = mk_url('walbum/photo/get', array('web_id'=>$album_info['web_id'],'photoid'=>$v['id']));
                    }
                }

                //取得总页数
                $pagecount = ceil($photo_num/$params['limit']['pagesize']);
            }

            //图片列表url
            $piclist_url = mk_url('walbum/photo/index', array('web_id'=>$album_info['web_id'], 'albumid' => $album_id));
            $album_url = mk_url('walbum/album/index', array('web_id'=>$album_info['web_id']));

            //判断是否是当前用户
            if($this->uid == $this->action_uid){
                $is_author = 1;
            }
            
			$return_data['total_num'] = $photo_num;
            $return_data['data'] = $photo_list;
            $this->assign('piclist_url', $piclist_url);
            $this->assign('album_url',$album_url);
            $this->assign('is_author',$is_author);
            $this->assign('photo_lists',$return_data);
            $this->assign('datainfo',$album_info);
            $this->display('album_graphicList');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }
    
    /**
     * 中转
     *
     * @author vicente
     * @access public
     */
    public function set_redirect()
    {
    	$return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
        	$is_author = 0;
            $id = $this->input->get('id');
            if(empty($id)){
                throw new MY_Exception("错误请求！");
            }
        	$photo_info = $this->photo->get($id);
            if(empty($photo_info)){
                throw new MY_Exception("照片信息有误！");
            }
            $photo_url = mk_url('walbum/photo/get', array('photoid' => $id, 'web_id' => $this->web_id));
            $this->redirect('walbum/photo/index', array('albumid' => $photo_info['aid'], 'web_id' => $this->web_id, 'iscomment' => '1', 'jumpurl' => urlencode($photo_url)));
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 显示某张照片的信息
     *
     * @author vicente
     * @access public
     */
    public function get()
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
        	$is_author = 0;
            $id = $this->input->get('photoid');
            if(empty($id) || WEB_ID == 0){
                throw new MY_Exception("错误请求！");
            }

            $photo_info = $this->photo->get($id);

            if(empty($photo_info) || !is_array($photo_info) || $photo_info['is_delete'] == 0){
                $this->display('noPicTip');
            	exit;
            }
            $album_info = $this->album->get($photo_info['aid']);

            if(empty($album_info)){
                throw new MY_Exception("相册信息有误！");
            }

            //照片视图上下页地址
            $prev_next_list = $this->photo->getPhotoPrevNext($id);
            if($prev_next_list['prev_id']) {
                $prev_url = mk_url('walbum/photo/get', array('photoid' => $prev_next_list['prev_id'], 'web_id' => $this->web_id));
                $this->assign('prev_url', $prev_url);
            }
            if($prev_next_list['next_id']) {
                $next_url = mk_url('walbum/photo/get', array('photoid' => $prev_next_list['next_id'], 'web_id' => $this->web_id));
                $this->assign('next_url', $next_url);
            }

            //删除照片地址
            $delete_url = mk_url('walbum/photo/delete', array('postpage' => 'photoLists', 'web_id' => $this->web_id));
            $upload_url = mk_url('walbum/photo/download', array('photoid' => $id, 'web_id' => $this->web_id));
            $list_url = mk_url('walbum/photo/index', array('albumid' => $photo_info['aid'], 'web_id' => $this->web_id));
			$photo_url = mk_url('walbum/photo/get', array('photoid' => $id, 'web_id' => $this->web_id));
            $view_photo_url = mk_url('walbum/photo/index', array('albumid' => $photo_info['aid'], 'web_id' => $this->web_id, 'iscomment' => '1', 'jumpurl' => urlencode($photo_url)));

            if($this->uid == $this->action_uid){
                $is_author = 1;
            }
            
            $photo_info['album_name'] = mb_substr($album_info['name'], 0, 10, 'utf-8');
            $photo_info['photo_count'] = $album_info['photo_count'];
            
            $params = array(
                'where'   =>  array(
                    'uid'       =>  $this->action_uid,
                    'is_delete'	=>	1,
                    'web_id'       =>	$this->web_id,
            		'a_type' => 0,
                )
            );
			$all_album_list = $this->album->index($params);

            $this->assign('is_author',$is_author);
            $this->assign('uploadurl', $upload_url);
            $this->assign('ptlistsurl', $list_url);
            $this->assign('ptdelete_url', $delete_url);
            $this->assign('view_photo_url', $view_photo_url);
            $this->assign('username',$this->user['username']);
            $this->assign('ptlists',$photo_info);
            $this->assign('prev_next_list',$prev_next_list);
            $this->assign('album_info',$album_info);
            $this->assign('all_album_list',$all_album_list);
            $this->assign('js_config',$this->js_config);
            $this->display('album_picView');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 获取存在评论照片
     *
     * @author vicente
     * @access public
     */
    public function getComment()
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
        	$is_author = 0;
            $id = $this->input->get('photoid');
            if(empty($id) || WEB_ID == 0){
                throw new MY_Exception("错误请求！");
            }

            $photo_info = $this->photo->get($id);
            if(empty($photo_info)){
                throw new MY_Exception("照片信息有误！");
            }

            $album_info = $this->album->get($photo_info['aid']);

            if(empty($album_info)){
                throw new MY_Exception("照片信息有误！");
            }

            //照片视图上下页地址
            $prev_next_list = $this->photo->getPhotoPrevNext($id, true);
            if($prev_next_list['prev_id']) {
                $prev_url = mk_url('walbum/photo/get', array('photoid' => $prev_next_list['prev_id'], 'web_id' => $this->web_id));
                $this->assign('prev_url', $prev_url);
            }
            if($prev_next_list['next_id']) {
                $next_url = mk_url('walbum/photo/get', array('photoid' => $prev_next_list['next_id'], 'web_id' => $this->web_id));
                $this->assign('next_url', $next_url);
            }

            //删除照片地址
            $delete_url = mk_url('walbum/photo/delete', array('postpage' => 'photoLists', 'web_id' => $this->web_id));
            $upload_url = mk_url('walbum/photo/download', array('photoid' => $id, 'web_id' => $this->web_id));
            $list_url = mk_url('walbum/photo/index', array('albumid' => $photo_info['aid'], 'web_id' => $this->web_id));


            if($this->uid == $this->action_uid){
                $is_author = 1;
            }

            $this->assign('is_author',$is_author);
            $this->assign('uploadurl', $upload_url);
            $this->assign('ptlistsurl', $list_url);
            $this->assign('ptdelete_url', $delete_url);
            $this->assign('username',$this->user['username']);
            $this->assign('ptlists',$photo_info);
            $this->assign('prev_next_lists',$prev_next_list);
            $this->assign('album_info',$album_info);
            $this->display('album_picView');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 批量删除照片
     * 
     * @author vicente
     * @access public
     */
    public function delete() 
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
        	//只有网页管理员才可以删除照片
        	if($this->uid != $this->action_uid){
        		throw new MY_Exception("当前用户非网页管理员，不能进行操作！");
        	}
            $delphoarr = $this->input->post('delArr');
            $id = $this->input->post('pic_id');
            if(!empty($id)){
				$photo_info = $this->photo->get($id);
                if(empty($photo_info)){
                	throw new MY_Exception("照片信息不存在！");
                }
                $album_info = $this->album->get($photo_info['aid']);
                if(empty($album_info)){
                	throw new MY_Exception("照片所在的相册不存在！");
                }

                //检查此照片是否是单张照片信息流
                if(checkWebTimeline($photo_info['id'], $this->web_id, 'photo')){
                	$this->photo->delPhotoInfoFlow($photo_info['id'], $this->web_id);
                }
                    
                $this->album->updateAlbumInfoFlow($this->dkcode, $album_info['id'], $this->web_info['name'], $this->web_id, false, false);

                //是否有评论
                $postpage = $this->input->get('postpage');
                if($postpage == 'photoLists') {
                	$get_nextID = $this->photo->getDelphotoNext($id);

	                //此图片是应用区封面照片
	                if($photo_info['is_maincover'] == 1){ 
	                	$this->photo->deleteMainCover($photo_info['id'], $photo_info['uid'], $album_info['web_id']); 
	                }
	                //删除当前照片
                    if(!$this->photo->delete($id)){
                    	throw new MY_Exception("删除失败！");
                    }
                    
                    //删除索引
                    service('RelationIndexSearch')->deletePhoto($id);

                
	                //此图片是相册封面
	                if($album_info['cover_id'] == $photo_info['id']){
	                	$this->album->resetAlbumCover($album_info['id']);
	                }
                    
                    $album_data = array(
			        	'id'       => $photo_info['aid'],
			            'type'     => 1,
			            'visible'  => 0
			        );
			
			        service('RestorationSearch')->restoreAlbumInfo($album_data);
                        
                    if(!empty($get_nextID)){
                        $this->redirect('walbum/photo/get', array('web_id' => $album_info['web_id'], 'photoid' => $get_nextID));
                    }else{
                        $this->redirect('walbum/photo/index', array('web_id' => $album_info['web_id'], 'albumid' => $photo_info['aid']));
                    }
				} else {
                    $get_nextID = $this->photo->getDelphotoNext($id, true);
                     
	                //此图片是应用区封面照片
	                if($photo_info['is_maincover'] == 1){ 
	                	$this->photo->deleteMainCover($photo_info['id'], $photo_info['uid'], $album_info['web_id']); 
	                }
                    //删除当前照片
                    if(!$this->photo->delete($id)){
                    	throw new MY_Exception("删除失败！");
                    }
                        
                    //删除索引
                    service('RestorationSearch')->deletePhoto($id);

				   
                    //此图片是相册封面
	                if($album_info['cover_id'] == $photo_info['id']){
	                	$this->album->resetAlbumCover($album_info['id']);
	                }
                    $album_data = array(
			        	'id'       => $photo_info['aid'],
			            'type'     => 1,
			            'visible'  => 0
			        );
			
			        service('RestorationSearch')->restoreAlbumInfo($album_data);
			        
			        //积分
            		//service('credit')->album(false);
                        
                    if($get_nextID){
                        $this->redirect('walbum/photo/getComment', array('web_id' => $album_info['web_id'], 'photoid' => $get_nextID));
                    }else{
                        $this->redirect('walbum/photo/getCommentList', array('web_id' => $album_info['web_id'], 'albumid' => $photo_info['aid']));
                    }
                 }
            }else if(!empty($delphoarr)){
            	//批量删除照片
                $del_data = array();
                $check_data = array();
                foreach($delphoarr as $k => $v){
	                $photo_info = $this->photo->get($v);
	                if(empty($photo_info)){
	                    throw new MY_Exception("照片信息有误！");
	                    break;
	                }
	                	
                	//此图片是应用区封面照片
	                if($photo_info['is_maincover'] == 1){ 
	                    $this->photo->deleteMainCover($photo_info['id'], $photo_info['uid'], $this->web_id); 
	                }
		                	
	                //检查此照片是否是单张照片信息流
	                if(checkWebTimeline($photo_info['id'], $this->web_id, 'photo')){
	                    $this->photo->delPhotoInfoFlow($photo_info['id'], $this->web_id);
	                }
                }
                	
                if(!$this->photo->batch_delete($delphoarr, $photo_info['aid'])){
                	$this->ajaxReturn('', '删除相册失败！', 0);
                }
                    
                $this->album->updateAlbumInfoFlow($this->dkcode, $photo_info['aid'], $this->web_info['name'], $this->web_id, false, false);
                    
                //删除索引
                service('RelationIndexSearch')->deletePhoto($delphoarr);
                $album_data = array(
		        	'id'       => $photo_info['aid'],
		            'type'     => 1,
		            'visible'  => 0
		        );
		
		        service('RestorationSearch')->restoreAlbumInfo($album_data);
                $data = array(
                	'album_url' => mk_url('walbum/photo/index', array('web_id' => $this->web_id, 'albumid' => $photo_info['aid']))
                );
                
                //积分
            	//service('credit')->album(false);
            	
                $this->ajaxReturn($data, '删除成功！');
            } else {
                throw new MY_Exception("错误请求！");
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 下载照片
     *
     * @author vicente
     * @access public
     * @param $id 照片ID
     */
    public function download()
    {
        $return_struct = array(
        	'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $id = $this->input->get('photoid');
            if(empty($id)){
                throw new MY_Exception("错误请求！"); 
            }
            $photo_info = $this->photo->get($id);
            if(empty($photo_info)){
                throw new MY_Exception("照片信息有误！");
            }

            //取得照片原图
            $this->load->fastdfs('album', '', 'fdfs');
            $photo_res = $this->fdfs->downloadFileBuff($photo_info['filename'].'.'.$photo_info['type'], $photo_info['groupname']);
            if ($photo_res) {
                header('Content-Description: File Transfer');
                header("Content-Type: application/force-download");
                if(strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")){
                    $filename = urlencode($photo_info['name']).'.'.$photo_info['type'];
                    $filename = str_replace("+", "%20", $filename);
                }else{
                    $filename = $photo_info['name'].'.'.$photo_info['type'];
                }
                header('Content-Disposition: attachment; filename="'.$filename.'"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length:'.$photo_info['size']);
                echo $photo_res;
                exit;	
            }else{
                throw new MY_Exception("文件不存在！");
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 设置封面
     * 
     * @author vicente
     * @access public
     */
    public function setCover()
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $id = $this->input->get('pid');
            $album_id = $this->input->get('aid');
            $web_id = $this->input->get('web_id');
            if(empty($id) || empty($album_id) ||  empty($web_id)){
                throw new MY_Exception("非法请求！");
            }else{
            	$album_info = $this->album->get($album_id);
	            if(empty($album_info)){
	                throw new MY_Exception("非法请求！");
	            }
                $flag = $this->album->setAlbumCover($album_id, $id);
                if($flag !== false){
                	if($album_info['cover_id'] != $id){
                		$album_data = array(
			            	'id'       => $album_id,
			            	'type'     => 1,
			            	'visible'  => 0
			            );
			            
			            service('RestorationSearch')->restoreAlbumInfo($album_data);
                	}

                    $this->ajaxReturn('', '封面设置成功！');
                }else{
                	$this->ajaxReturn('', '封面设置失败！', 0);
                }
            }
            echo json_encode($return);
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 图片排序
     *
     * @author vicente
     * @access public
     */
    public function orderPhoto()
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $moverA_id = $this->input->post('moverA_id');
            $moverB_id = $this->input->post('moverB_id');
            $album_id = $this->input->post('mover_ID');

            if(!$this->photo->orderPhoto($moverA_id,$moverB_id,$album_id)){
            	$this->ajaxReturn('', '操作失败！', 0);
            }
            
            $this->ajaxReturn('', '操作成功！');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 修改照片描述
     * 
     * @author vicente
     * @access public
     */
    public function editPhotoDesc() 
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $id = $this->input->post('id');
            $desc = $this->input->post('info');
            if(empty($id)) {
                throw new MY_Exception("非法操作！");
            }
            
            //更改照片描述
            if($this->photo->editDesc($id, $desc)) {
            	//搜索索引
	            $photo_info = array(
	            	'id'    => $id,
	            	'type'  => 1,
	            );
	
	            service('RestorationSearch')->restorePhotoInfo($photo_info);
	
	            $this->ajaxReturn($desc, '修改成功！');
            }else{
            	$this->ajaxReturn('', '修改照片描述失败！', 0);
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 编辑照片名字
     * 
     * @author vicente
     * @access public
     */
    public function editPhotoName() 
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $id = $this->input->post('id');
            $name = $this->input->post('info');
            if(empty($id)) {
                throw new MY_Exception("非法操作！");
            }
            
            if($this->photo->editName($id, $name)) {
            	//搜索索引
	            $photo_info = array(
	            	'id'    => $id,
	            	'type'  => 1,
	            );
	
	            service('RestorationSearch')->restorePhotoInfo($photo_info);
	            
            	//检查此照片是否是单张照片信息流
            	if(checkWebTimeline($id, $this->web_id, 'photo')){
		        	$this->photo->updatePhotoInfoFlow($this->dkcode, $id, time(), $this->web_info['name'], $this->web_id, false);
		        }
	            
	            $this->ajaxReturn($name, '修改成功！');
            }else{
            	$this->ajaxReturn('', '修改照片名失败！', 0);
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 在详细照片中的设置首页应用区相册封面
     * 
     * @author vicente
     * @access public
     */
    public function setMainCover()
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
        	//只有网页管理员才可以设置封面
        	if($this->uid != $this->action_uid){
        		throw new MY_Exception("当前用户非网页管理员，不能进行操作！");
        	}
            $id = $this->input->get('pid');
            if(empty($id)) {
                throw new MY_Exception("非法请求！");
            }

            //得到照片信息
            $photo_info = $this->photo->get($id);
            if(empty($photo_info)) {
                throw new MY_Exception("照片信息有误，设置网页应用区相册封面不成功！");
            }

            $this->photo->setMainCover($photo_info['id'], $this->uid);
            $this->load->model("appcovermodel", "appCover");
            $filename = $photo_info['filename'].'.'.$photo_info['type'];
            $info = $this->appCover->mergeImages($photo_info['groupname'], $filename, $this->web_id, 2);

            if($info === true) {
            	$this->ajaxReturn('', '设置网页应用区相册封面成功！');
            }else {
            	$data = array(
            		'info' => $info
            	);
            	$this->ajaxReturn($data, '设置网页应用区相册封面不成功！', 0);
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
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
    	$return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
        	$id = $this->input->post('picId');
            $direction = $this->input->post('direction');

            if(empty($id) || empty($direction) || !in_array($direction, array('left', 'right'))){
                throw new MY_Exception("非法操作！");
            }
            
            $degree = $direction == 'right' ? 90 : 270;
            
            if($return = $this->photo->rotate($id, $degree)){
            	$data = array(
            		'picUrl'=>$return['picUrl']
            	);
            	
            	//检查此照片是否是单张照片信息流
            	if(checkWebTimeline($id, $this->web_id, 'photo')){
		        	$this->photo->updatePhotoInfoFlow($this->dkcode, $id, $return['dateline'], $this->web_info['name'], $this->web_id, false);
		        }
		
		        $this->album->updateAlbumInfoFlow($this->dkcode, $return['aid'], $this->web_info['name'], $this->web_id, false, false);
            	$this->ajaxReturn($data, '保存成功！');
            }else{
            	$this->ajaxReturn('', '保存失败！', 0);
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }
    
	/**
     * 转移照片
     * 
     * @author vicente
     * @access public
     */
    public function move()
    {
    	$return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
        	$album_id = $this->input->post('albumId');
    		$id_s = $this->input->post('picId');

            if(empty($album_id) || empty($id_s)){
                throw new MY_Exception("非法操作！");
            }
            
            if(!is_array($id_s)){
            	$get_nextID = $this->photo->getDelphotoNext($id_s);
            }
			if(is_array($id_s)){
				$params = array(
	                'where'   =>  array(
	                    'uid'       =>  $this->action_uid,
	                    'is_delete'	=>	1,
						'id'        =>  array_values($id_s)
	                )
	            );

	            $photo_list = $this->photo->index($params);
	            $album_info = $this->album->get($photo_list[0]['aid']);
	            if($this->photo->movePhoto($album_id, $id_s)){
	            	$isResetAlbumCover = false;
		            foreach ($photo_list as $photo_info){
			            //此图片是应用区封面照片
		                if($photo_info['is_maincover'] == 1){ 
		                    $this->photo->deleteMainCover($photo_info['id'], $photo_info['uid'], $this->web_id); 
		                }
		                
			            //此图片是相册封面
		                if($album_info['cover_id'] == $photo_info['id']){
		                	$isResetAlbumCover = true;
		                }
			                	
		                //检查此照片是否是单张照片信息流
		                if(checkWebTimeline($photo_info['id'], $this->web_id, 'photo')){
		                	$this->photo->delPhotoInfoFlow($photo_info['timestamp'], $this->web_id);
		                }
		            }
		            
					if($isResetAlbumCover) {
		                $this->album->resetAlbumCover($album_info['id']);
					}
					
					$album_data = array(
						array(
							'type' => 1,
							'visible' => 0,
							'id'      => $album_id
						),
						array(
							'type' => 1,
							'visible' => 0,
							'id'      => $album_info['id']
						)
					);
					
					service('RestorationSearch')->restorePhotoInfoTransfered($album_data);
					
					//转移前所属相册的信息流
		            $this->album->updateAlbumInfoFlow($this->dkcode, $photo_info['aid'], $this->web_info['name'], $this->web_id, true, false);
		            //转移后所属相册的信息流
		            $is_update_time = checkWebTimeline($album_id, $this->web_id, 'album') ? false : true;
		            $this->album->updateAlbumInfoFlow($this->dkcode, $album_id, $this->web_info['name'], $this->web_id, true, $is_update_time);
		            
		            $this->ajaxReturn('', '照片移动成功！');
	            }else{
					$this->ajaxReturn('', '照片移动失败！', 0);
				}
			}else{
				$photo_info = $this->photo->get($id_s);
            	$album_info = $this->album->get($photo_info['aid']);
				if($this->photo->movePhoto($album_id, $id_s)){
					//检查此照片是否是单张照片信息流
					if(checkWebTimeline($photo_info['id'], $this->web_id, 'photo')){
	                	$this->photo->delPhotoInfoFlow($photo_info['timestamp'], $this->web_id);
	                }
	                
					//此图片是相册封面
	                if($album_info['cover_id'] == $photo_info['id']){
	                	$this->album->resetAlbumCover($album_info['id']);
	                }
	                
	                //此图片是应用区封面照片
	                if($photo_info['is_maincover'] == 1){ 
	                	$this->photo->deleteMainCover($photo_info['id'], $photo_info['uid'], $album_info['web_id']); 
	                }
	                
	                $album_data = array(
						array(
							'type' => 1,
							'visible' => 0,
							'id'      => $album_id
						),
						array(
							'type' => 1,
							'visible' => 0,
							'id'      => $album_info['id']
						)
					);
					
					service('RestorationSearch')->restorePhotoInfoTransfered($album_data);
	                
	                //转移前所属相册的信息流
		            $this->album->updateAlbumInfoFlow($this->dkcode, $photo_info['aid'], $this->web_info['name'], $this->web_id, false, false);
		            //转移后所属相册的信息流
		            $is_update_time = checkWebTimeline($album_id, $this->web_id, 'album') ? false : true;
		            $this->album->updateAlbumInfoFlow($this->dkcode, $album_id, $this->web_info['name'], $this->web_id, true, $is_update_time);
	                
					if($get_nextID) {
						$data = array(
							'photoNext' => mk_url('walbum/photo/get', array('photoid' => $get_nextID, 'web_id' => $this->web_id))
						);
					}else{
						$data = array(
							'photoNext' => mk_url('walbum/photo/index', array('albumid' => $photo_info['aid'], 'web_id' => $this->web_id))
						);
					}
					$this->ajaxReturn($data, '照片移动成功！');
				}else{
					$this->ajaxReturn('', '照片移动失败！', 0);
				}
			}
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }
}
