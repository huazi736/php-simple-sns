<?php
/**
 * 相册API接口
 * 
 * @author weijian
 * @version $Id$
 */
class Api extends MY_Controller 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('albummodel', 'album');
        $this->load->model('photomodel', 'photo');
        $this->load->model('uploadmodel', 'upload');
    }
    
	/**
     * 上传照片至默认相册
     * 
     * 步骤：
     * 1、获取二进制数据，$type表示为哪个默认相册
     * 2、上传文件
     * 3、判断默认相册是否创建，未创建则先创建
     * 4、保存照片至数据库中
     * 5、根据$type生成相应的缩略图
     * 6、更新图片记录
     */
	public function camera()
	{
        $return_struct = array(
            'state'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            if(!empty($_POST)){
                $type = $this->input->get_post('type');
                $web_id = intval($this->input->get_post('web_id'));

                if(empty($type) || !in_array($type, array('2', '3'))){
                    throw new MY_Exception("缺少相册类型");
                }

                $file = $this->input->get_post('img');
                if(empty($file)){
                    throw new MY_Exception("摄像头图片输入有误");
                }
                $file = base64_decode($file);

                //生成图片存放路径
                $this->config->load('album');
                $tmp_storage_path = $this->config->item('tmp_storage_path');
                $pic_path = rtrim($tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). ".jpg";
                file_put_contents($pic_path, $file);

                $photo_id = $this->upload->uploadFileBuff($pic_path, $this->uid);
                if(empty($photo_id)){
                    throw new MY_Exception("图片上传失败！");
                }

                $params = array(
                    'where' => array(
                        'uid'       => $this->uid,
                        'web_id'    => $web_id,
                		'is_delete' => 1,
                        'a_type'    => $type
                     )
                );
                $album_list = $this->album->index($params);

                //获得当前类型默认相册名称
                $album_default = $this->upload->GetThumbConf($type);
                //创建默认相册
                if(empty($album_list)){
                    $data = array(
                        'uid'    => $uid,
                        'newAlbumName'   => $album_default['name'],
                        'a_type' => $type,
                        'web_id' => $web_id
                    );
                    $album_id = $this->album->add($data);
                    if(empty($album_id)) {
                        throw new MY_Exception("无法创建相册");
                    }
                } else {
                    $album = array_shift($album_list);
                    $album_id = $album['id'];
                }

                $photo_data = array(
                    'uid'   => $this->uid,
                    'album_id'   => $album_id,
                    'id'    => $photo_id,
                );
                //保存照片到数据库
                $return = $this->photo->edit($photo_data);
                if(!$return) {
                    throw new MY_Exception("无法更新照片信息");
                }
                
                //设置相册封面
            	$this->photo->setAutoCover($album_id, $photo_id, false);
            	
            	$photo_info = $this->photo->get($photo_id);
            	
            	$album_data = array(
	            	'id'       => $album_id,
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	
	            service('RestorationSearch')->restoreAlbumInfo($album_data);
	            
	            $photo_data = array('id'=>$photo_info['id'], 'type'=> 1);

	            service('RestorationSearch')->restorePhotoInfo($photo_data);
                
                @unlink($pic_path);
                
                $array = array();
                $array['fid'] = $photo_info['id'];
                $array['note'] = $album_id;
                $ret = array();
                $ret[0]['pid'] = $photo_info['id'];
                $ret[0]['groupname'] = $photo_info['groupname'];
                $ret[0]['filename'] = $photo_info['filename'];
                $ret[0]['type'] = $photo_info['type'];
            	if(!empty($photo_info['notes'])){
                	$ret[0]['size'] = json_decode($photo_info['notes'], true);
                }else{
                	$ret[0]['size'] = array();
                }
                
                $array['picurl'] = base64_encode(serialize($ret));
                
                $this->ajaxReturn($array, '', 1, 'jsonp');
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 上传照片至默认相册
     * 
     * 步骤：
     * 1、检查$_FILES['file']和$type两个变量，$type表示为哪个默认相册
     * 2、上传文件
     * 3、判断默认相册是否创建，未创建则先创建
     * 4、保存照片至数据库中
     * 5、根据$type生成相应的缩略图
     * 6、更新图片记录
     */
    public function upload()
    {
        $return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            error_reporting(E_ALL);
            ini_set("display_errors", true);
            if(isset($_FILES['uploadPhotoFile'])){
                $type = $this->input->get_post('type');
                $web_id = intval($this->input->get_post('web_id'));
                if(empty($type) || !in_array($type, array('2', '3'))){
                    throw new MY_Exception("缺少相册类型");
                }

                $return_data = $this->upload->uploadPic($_FILES['uploadPhotoFile'], $this->action_uid);
                $photo_id = $return_data['photo_id'];
                if(empty($photo_id)){
                	$this->ajaxReturn('', '无法上传图片', 0, 'jsonp');
                }

                $params = array(
                    'where' => array(
                        'uid'       => $this->action_uid,
                        'web_id'    => $web_id,
                		'is_delete' => 1,
                        'a_type'    => $type
                     )
                );
 
                $album_list = $this->album->index($params);

                //获得当前类型默认相册名称
                $album_default = $this->upload->GetThumbConf($type);
                //创建默认相册
                if(empty($album_list)){
                    $data = array(
                        'uid'    => $this->action_uid,
                        'newAlbumName'   => $album_default['name'],
                        'a_type' => $type,
                        'web_id' => $web_id
                    );
                    $album_id = $this->album->add($data);
                    if(empty($album_id)) {
                        throw new MY_Exception("无法创建相册！");
                    }
                } else {
                    $album = array_shift($album_list);
                    $album_id = $album['id'];
                }

                $photo_data = array(
                    'uid'   => $this->action_uid,
                    'album_id'   => $album_id,
                    'id'    => $photo_id
                );
                //保存照片到数据库
                $return = $this->photo->edit($photo_data);
                if(!$return) {
                    throw new MY_Exception("无法更新照片信息！");
                }
                
                $photo_info = $this->photo->get($photo_id);
                
                //设置相册封面
            	$this->photo->setAutoCover($album_id, $photo_id, false);

                //相册自动排序
                $this->album->autoUpdateAlbumOrder($this->action_uid, $album_id);
                
                $album_data = array(
	            	'id'       => $album_id,
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	
	            service('RestorationSearch')->restoreAlbumInfo($album_data);
	            
	            $photo_data = array('id'=>$photo_info['id'], 'type'=> 1);

	            service('RestorationSearch')->restorePhotoInfo($photo_data);

                $array = array();
                $array['fid'] = $photo_info['id'];
                $array['note'] = $album_id;
                $ret = array();
                $ret[0]['pid'] = $photo_info['id'];
                $ret[0]['groupname'] = $photo_info['groupname'];
                $ret[0]['filename'] = $photo_info['filename'];
                $ret[0]['type'] = $photo_info['type'];
                if(!empty($photo_info['notes'])){
                	$ret[0]['size'] = json_decode($photo_info['notes'], true);
                }else{
                	$ret[0]['size'] = array();
                }
                
                $array['picurl'] = base64_encode(serialize($ret));
                $this->ajaxReturn($array, '', 1, 'jsonp');
            }
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 个人头像上传接口
     */
    public function uploadHead()
    {
        $return_struct = array(
            'state'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $file = urldecode($this->input->get_post('filePath'));
            if(!empty($file)){
                $type = 1;
                //验证是否为头像相册的照片
                $photo_id = $this->input->get_post('pid');
                $web_id = intval($this->input->get_post('web_id'));
                if(!empty($photo_id)){
                    if($this->album->checkAlbumType($photo_id, $type)){
                        die('s');
                    }
                }
                
                $photo_id = $this->upload->uploadFileBuff($file, $this->action_uid, 0, 1);
                if(!$photo_id){
                    throw new MY_Exception("上传失败！");
                }

                $photo_info = $this->photo->get($photo_id);
                $params = array(
                    'where' => array(
                        'uid'       => $this->action_uid,
                        'web_id'    => $web_id,
                		'is_delete' => 1,
                        'a_type'    => $type
                     )
                );
                
                $album_list = $this->album->index($params);

                //获得当前类型默认相册名称
                $album_default = $this->upload->GetThumbConf($type);
                
                //创建默认相册
                if(empty($album_list)){
                    $data = array(
                        'uid'    => $this->action_uid,
                        'newAlbumName'   => $album_default['name'],
                        'a_type' => $type,
                        'web_id' => $web_id
                    );
                    $album_id = $this->album->add($data);
                    if(empty($album_id)) {
                        throw new MY_Exception("无法创建相册");
                    }
                } else {
                	$album = array_shift($album_list);
                    $album_id = $album['id'];
                }

                $photo_data = array(
                    'uid'   => $this->action_uid,
                    'album_id'   => $album_id,
                    'id'    => $photo_id,
                );
                //保存照片到数据库
                $return = $this->photo->edit($photo_data);
                if(!$return) {
                    throw new MY_Exception("无法更新照片信息");
                }
                
                //设置相册封面
            	$this->photo->setAutoCover($album_id, $photo_id, false);
                //相册自动排序
                $this->album->autoUpdateAlbumOrder($this->action_uid, $album_id);
                
                $album_data = array(
	            	'id'       => $album_id,
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	
	            service('RestorationSearch')->restoreAlbumInfo($album_data);
	            
	            $photo_data = array('id'=>$photo_info['id'], 'type'=> 1);

	            service('RestorationSearch')->restorePhotoInfo($photo_data);
	            
                $this->ajaxReturn('', 's', '1', 'jsonp');
            }
            die('error');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 封面上传接口(文件流方式)
     */
    public function uploadWithMap()
    {
    	$return_struct = array(
            'state'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
	        $file = urldecode($this->input->get('filePath'));
	        
	        if(!empty($file)){
	        	$photo_id = $this->input->get_post('pid');
                $web_id = intval($this->input->get_post('web_id'));
	            $type = 2;
	            $this->load->model('albummodel', 'album');
	            //验证是否为封面相册的照片
	            if(!empty($photo_id)){
	                if($this->album->checkAlbumType($photo_id, $type)){
	                    die($photo_id);
	                }
	            }

	            $photo_id = $this->upload->uploadFileBuff($file, $this->action_uid, 0, 1);
	            if(empty($photo_id)){
	                throw new MY_Exception("上传失败！");
	            }
	
	            $photo_info = $this->photo->get($photo_id);
	
	            $params = array(
	                'where' => array(
	                    'uid'       => $this->action_uid,
                        'web_id'    => $web_id,
                		'is_delete' => 1,
                        'a_type'    => $type
	                )
	            );
	            $album_num = $this->album->count($params);
	            $album_list = $this->album->index($params);
	
	            //获得当前类型默认相册名称
	            $album_default = $this->upload->GetThumbConf($type);
	            //创建默认相册
	
	            if(empty($album_list)){
	                $data = array(
	                    'uid'    => $this->action_uid,
	                    'newAlbumName'   => $album_default['name'],
	                    'a_type' => $type,
	                    'web_id' => $web_id
	                );
	                $album_id = $this->album->add($data);
	                if(empty($album_id)) {
	                    throw new MY_Exception("无法创建相册！");
	                }
	            } else {
	                $album = array_shift($album_list);
                    $album_id = $album['id'];
	            }
	
	            $photo_data = array(
	                'uid'   => $this->action_uid,
	                'album_id'   => $album_id,
	                'id'    => $photo_id,
	            );
	            //保存照片到数据库
	            $return = $this->photo->edit($photo_data);
	            if(!$return) {
	                throw new MY_Exception("无法更新照片信息");
	            }
	            
	            //设置相册封面和应用区封面
            	$this->photo->setAutoCover($album_id, $photo_id, false);
	
	            //相册自动排序
	            $this->album->autoUpdateAlbumOrder($this->action_uid, $album_id);
	            
	            $album_data = array(
	            	'id'       => $album_id,
	            	'type'     => 1,
	            	'visible'  => 0
	            );
	
	            service('RestorationSearch')->restoreAlbumInfo($album_data);
	            
	            $photo_data = array('id'=>$photo_info['id'], 'type'=> 1);

	            service('RestorationSearch')->restorePhotoInfo($photo_data);
	            
	            $this->ajaxReturn('', 's', '1', 'jsonp');
	        }
	        $this->ajaxReturn('', 'error', '1', 'jsonp');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }

    /**
     * 相册调用接口
     * 如果page和pagesize不传值，则显示所有符合条件的相册列表
     * 
     * @author weijian
     * @param string $id 相册ID，可选
     * @param string $order 排序类型，可选，可选值：sort_asc，sort_desc,date_asc,date_desc,id_asc,id_desc
     * @param integer $page 当前页，需要和pagesize配合
     * @param integer $pagesize 当前显示多少数量
     */
    public function get_album_list()
    {
		$web_id = intval($this->input->get('web_id'));
		$order = $this->input->get('order');
        if(empty($web_id)){
        	$this->ajaxReturn('', '要选择网页！', 0);
        }
        $callback = htmlspecialchars($this->input->get('callback'),ENT_QUOTES);
        if(!empty($order)){
        	switch($order){
                case 'sort_asc':
                    $orderby = array('a_sort' => 'asc');
                    break;
                case 'sort_desc':
                	$orderby = array('a_sort' => 'desc');
                    break;
                case 'date_asc':
                	$orderby = array('dateline' => 'asc');
                    break;
                case 'date_desc':
                	$orderby = array('dateline' => 'desc');
                    break;
                case 'id_asc':
                	$orderby = array('id' => 'asc');
                    break;
                case 'id_desc':
                	$orderby = array('id' => 'desc');
                    break;
            }
        }
    	
        $params = array(
            'where' => array(
                'uid'       => $this->uid,
                'id'        => $this->input->get('id'),
                'web_id'    => intval($this->input->get('web_id')),
                'is_delete'	=> 1,
            ),
            'orderby' => $orderby,
            'limit' => array(
                'page'	    =>  $this->input->get('page'),
                'pagesize'	=>  $this->input->get('pagesize'),
            )
        );

        $data = $this->album->index($params);
        
        $this->ajaxReturn($data);
    }

    /**
     * 照片调用接口
     * 如果page和pagesize不传值，则显示所有符合条件的照片列表
     * 
     * @author weijian
     * @param string $aid 相册ID，必选
     * @param string $order 排序类型，可选，可选值：sort_asc，sort_desc,date_asc,date_desc,id_asc,id_desc
     * @param integer $page 当前页，需要和pagesize配合
     * @param integer $pagesize 当前显示多少数量
     */
    public function get_photo_list()
    {
    	$orderby = array();
        $album_id = $this->input->get('aid');
        if(empty($album_id)){
        	$this->ajaxReturn('', '无效的相册编号！', 0);
        }
    	$web_id = $this->input->get('web_id');
		$order = $this->input->get('order');
        if(empty($web_id)){
        	$this->ajaxReturn('', 'web_id不能为空！', 0);
        }
        $callback = htmlspecialchars($this->input->get('callback'),ENT_QUOTES);
        
    	if(!empty($order)){
        	switch($order){
                case 'sort_asc':
                    $orderby = array('p_sort' => 'asc');
                    break;
                case 'sort_desc':
                	$orderby = array('p_sort' => 'desc');
                    break;
                case 'date_asc':
                	$orderby = array('dateline' => 'asc');
                    break;
                case 'date_desc':
                	$orderby = array('dateline' => 'desc');
                    break;
                case 'id_asc':
                	$orderby = array('id' => 'asc');
                    break;
                case 'id_desc':
                	$orderby = array('id' => 'desc');
                    break;
            }
        }

        $params = array(
            'where' => array(
                'uid'       =>    $this->uid,
        		'aid'       =>    $this->input->get('aid'),
                'id'        =>    $this->input->get('id'),
                'web_id'    =>    intval($this->input->get('web_id')),
                'is_delete'	=>    1,
            ),
            'orderby' => $orderby,
            'limit' => array(
                'page'	=>    $this->input->get('page'),
                'pagesize'	=>    $this->input->get('pagesize'),
            )
        );
        $data = $this->photo->index($params);
        $return['status'] = 1;
        if(!empty($data)){
	        foreach($data as $key => $item){
	            $data[$key]['img'] = getImgPath($item['groupname'], $item['filename'], $item['type']);
	        }
        }
        
        $this->ajaxReturn($data);
    }

    /**
     * 照片详情调用接口
     * 
     * @author weijian
     * @param string $pid 照片ID，必选
     */
    public function get_photo_info()
    {
        $photo_id = $this->input->get('pid');
        if(empty($photo_id)){
        	$this->ajaxReturn('', '无效的照片编号', 0, 'jsonp');
        }

        $data = $this->photo->get($photo_id);
        
        $this->ajaxReturn($data, '', 1, 'jsonp');
    }

    /**
     * 相册统计
     * /web/album/?c=api&m=get_album_number&flashUploadUid=1000001055&action_dkcode=xxx
     * @param integer uid 用户编号
     * @param integer action_dkcode 被访问者端口号
     */
    public function get_album_number()
    {
        $params = array(
            'where' => array(
                'uid'   =>    $this->action_uid,
                'is_delete'	=>    1,
            )
        );

        $num = $this->album->count($params);
        
        $data = array(
        	'num' => $num
        );

        $this->ajaxReturn($data, '', 1, 'jsonp');
    }
    
    /**
     * 根据用户uid获取相册数量
     * 
     * @author vicente
     * @access public
     */
    public function get_photo_num()
    {
        $return_struct = array(
            'state'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $uid = $this->input->get('uid');
            $web_id = intval($this->input->get('web_id'));
            if(empty($uid) || empty($web_id)){
                throw new MY_Exception("非法操作！");
            }
            
            $params = array(
	            'where' => array(
	                'uid'       => mysql_real_escape_string($uid),
	                'is_delete'	=> 1,
            		'web_id'    => $web_id
	            )
	        );
	
	        $num = $this->album->count($params);
	        
	        $this->ajaxReturn($num, '', 1, 'jsonp');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }
    
	pubLic function judgePhotoAccess()
    {
    	$return_struct = array(
            'status'  => 0,
            'code'  => 501,
            'msg'  => 'Not Implemented',
            'content'  => array()
        );
        try{
            $photo_id = $this->input->post('pid');
	    	if(empty($photo_id)) {
	    		throw new MY_Exception("非法操作！");
	    	}
	    	
	  		$photo_info = $this->photo->get($photo_id);
	    	if(empty($photo_info)) {
	    		throw new MY_Exception("照片信息有误！");
	    	}
	    	
	  		$album_id = $photo_info['aid'];
	  		$album_info = $this->album->get($album_id);
	  		if(empty($album_info)) {
	  			throw new MY_Exception("照片所在的相册信息有误！");
	  		}
	  		
	  		$return = array('status' => 1);
	  		
	  		$this->ajaxReturn('', '', 1, 'jsonp');
        }catch(MY_Exception $ex){
            $this->_ex($ex, $return_struct);
        }
    }
}
