<?php
/**
 * 相册照片
 *
 * @author vicente
 * @version $Id
 */

class Album extends MY_Controller {
	
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
        	$this->assign('login_avatar', get_webavatar($this->web_id, 's'));
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
     * 相册列表
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
            $total_page = 1;
            $is_author = 0;
            $album_list = array();
            $all_album_list = array();
    		$params = array(
    			'where' => array(
		    		'uid'   	=>  $this->action_uid,
			        'is_delete'	=>	1,
			        'web_id'    =>  $this->web_id 
    			),
    			'orderby' => array(),
    			'limit'   => array(
    				'pagesize'	=> 16,
    			)
	        );

            $album_count = $this->album->count($params);

            if($album_count > 0){
                $album_list = $this->album->index($params);
	            //设置照片列表地址
	            foreach($album_list as $k => $v){
	            	$album_list[$k]['photo_lists_url'] = mk_url('walbum/photo/index', array('web_id' => $this->web_id,'albumid'=>$v['id'])); 
	            }
	            
	            $total_page = round($album_count / $params['limit']['pagesize']);
            }

	        if($this->uid == $this->action_uid){
	            $is_author = 1;
	            $all_album_list = $this->album->getUserAlbums($this->action_uid, $this->web_id);
	        }

            $return_data['list'] = $album_list;
            $return_data['total_num'] = $album_count;
	        $this->assign('is_author',$is_author);
	        $this->assign('album_lists',$return_data);
	        $this->assign('all_album_list',$all_album_list);
	        $this->display('album');
    	}catch (MY_Exception $ex){
    		$this->_ex($ex, $return_struct);
    	}
    }

	/**
     * ajax方式获取更多相册
     * 
     * @author vicente
     * @access public
     */
    public function getAlbumMore()
    {	
    	
    	$return_struct = array(
    		'status'   => 0,
    		'code'     => 501,
    		'msg'      => 'Not Implemented',
    		'content'  => array()
    	);
    	try{
    		$is_end = false;
            $total_page = 1;
            $album_list = array();
            //所查询第几页数据
            $page = $this->input->post('page');
    		$params = array(
    			'where' => array(
		    		'uid'	    =>  $this->action_uid,
			        'is_delete'	=>	1,
			        'web_id'    =>  $this->web_id 
    			),
    			'limit'   => array(
                    'page'      => $page,
    				'pagesize'	=> 16,
    			)
	        );
            $album_num = $this->album->count($params);
            if($album_num > 0){
                $album_list = $this->album->index($params);

                //设置照片列表地址
                foreach($album_list as $k => $v){
                    $album_list[$k]['photo_lists_url'] = mk_url('walbum/photo/index', array('web_id' => $this->web_id, 'albumid'=>$v['id'])); 
                }

                //是否最后page
                $total_page = ceil($album_num / $params['limit']['pagesize']);
                if($page >= $total_page) {
                    $is_end = true;
                }
            }
            
            $data = array(
            	'content'  => $album_list,
            	'isend'    => $is_end
            );
            
            $this->ajaxReturn($data);
    	}catch (MY_Exception $ex){
    		$this->_ex($ex, $return_struct);
    	}
    }

    /**
     * 新增相册
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
        	//只有网页管理员才可以添加相册
        	if($this->uid != $this->action_uid){
        		throw new MY_Exception("当前用户非网页管理员，不能进行操作！");
        	}
            $request_data = $this->input->post();
            $name = $request_data['newAlbumName'];
            if(strlen($name) == 0){
                throw new MY_Exception("相册名称不能为空！");
            }

            $request_data['uid'] = $this->uid;
            $request_data['web_id'] = $this->web_id;

            if($id = $this->album->add($request_data)){
            	//搜索索引
            	$album_info = array(
	            	'id'       => $id,
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	
	            service('RestorationSearch')->restoreAlbumInfo($album_info);
	            $data = array(
	            	'album_id' => $id
	            );
	            //积分
            	//service('credit')->album();
            	
	            $this->ajaxReturn($data, '新增相册成功！');
            }else{
            	$this->ajaxReturn('', $this->action_uid.$name.'新增相册失败！', 0);
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 编辑相册
     * 
     * @author vicente
     * @access public
     */
    public function edit()
    {
	    $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );    
        try{
        	//只有网页管理员才可以编辑相册
        	if($this->uid != $this->action_uid){
        		throw new MY_Exception("当前用户非网页管理员，不能进行操作！");
        	}
            $request_data = $this->input->post();
            if(empty($request_data['albumID'])){
                throw new MY_Exception("非法操作！");
            }

            if(!$this->album->edit($request_data)){
            	$this->ajaxReturn('', '编辑失败！', 0);
            }else{
            	//搜索索引
            	$album_info = array(
	            	'id'       => $request_data['albumID'],
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	
	            service('RestorationSearch')->restoreAlbumInfo($album_info);
	            
	            $this->album->updateAlbumInfoFlow($this->dkcode, $request_data['albumID'], $this->web_info['name'], $this->web_id, false, false);
	            
                $this->ajaxReturn('', '编辑成功！');
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 删除相册
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
            $id = $this->input->post('albumID');
            if(empty($id)){
                throw new MY_Exception("非法操作！");
            }

            //查询相册信息
            $album_info = $this->album->get($id);
            if(empty($album_info)){
                throw new MY_Exception("相册不存在！");
            }

            if(!$this->album->delete($id)){
            	$this->ajaxReturn('', '删除相册失败！', 0);
            }
            
            //更新信息流
			$this->album->delAlbumInfosFlow($id, $this->web_id);
			
			//删除索引
			service('RelationIndexSearch')->deleteAlbum($id);
			
			//积分
            //service('credit')->album(false);
			
			$data = array(
				'album_url' => mk_url('walbum/album/index', array('web_id'=>$album_info['web_id']))
			);
			
			$this->ajaxReturn($data, '删除成功！');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 修改相册描述
     * 
     * @author vicente
     * @access public
     */
    public function editAlbumDesc() 
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $id = $this->input->post('id');
            $desc= $this->input->post('info');
            if(empty($id)) {
                throw new MY_Exception("错误请求！");
            }

            if($this->album->editDesc($id, $desc)) {
            	//搜索索引
	            $album_info = array(
	            	'id'       => $id,
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	
	            service('RestorationSearch')->restoreAlbumInfo($album_info);
	            
	            $this->ajaxReturn($desc, '修改成功！');
            }else{
            	$this->ajaxReturn('', '修改相册描述失败！', 0);
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }
    
    /**
     * 相册排序
     *
     * @author vicente
     * @access public
     */
    public function orderAlbum()
    {
        $moverA_id = $this->input->post('moverA_id');
        $moverB_id = $this->input->post('moverB_id');
        $res = $this->album->orderAlbum($this->action_uid, $moverA_id, $moverB_id);
        if(!$res){
        	$this->ajaxReturn('', '操作失败！', 0);
        }
        
        $this->ajaxReturn('', '操作成功！');
    }
}
