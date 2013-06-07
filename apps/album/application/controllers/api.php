<?php
/**
 * 相册API接口
 * 
 * @author weijian
 * @version $Id: api.php 31472 2012-07-09 02:08:20Z guzb $
 */
class Api extends MY_Controller 
{
    public function __construct()
    {	
        parent::__construct();
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
	public function camera(){
        if(!empty($_POST)){
        	$type = $this->input->post('type');
            $uid = $this->input->post('flashUploadUid');
        	if(!$type || !in_array($type, array('2', '3'))){
                $return['status'] = 0;
				$return['msg'] = '缺少相册类型';
				echo json_encode($return);
				exit;
            }
        	if(!$uid){
                $return['status'] = 0;
				$return['msg'] = '用户信息有误';
				echo json_encode($return);
				exit;
            }
        	$file = $this->input->get_post('img');
        	if(!$file){
        		$return['status'] = 0;
				$return['msg'] = '摄像头图片输入有误';
				echo json_encode($return);
				exit;
        	}
        	$file = base64_decode($file);
        	
            $this->load->model('uploadmodel', 'upload');
            //生成图片存放路径
            $this->config->load('album');
            $tmp_storage_path = $this->config->item('storage_path');
            $pic_path = rtrim($tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). ".jpg";
            file_put_contents($pic_path, $file);
	        
            $pid = $this->upload->uploadFileBuff($pic_path, $uid, 0, 2, $type);
            if(!$pid){
                $return['status'] = 0;
				$return['msg'] = '无法上传图片';
				echo json_encode($return);
				exit;
            }
            
            $photo_quality = config_item('photo_quality');
            
            //查询默认相册是否创建
            $this->load->model('albummodel', 'album');
            $album_info = $this->album->getAlbumList(array(
            	'uid' => $this->uid,
            	'a_type' => $type,
            	'total' => true,
            	'web_id' => '0'
            ));

            //获得当前类型默认相册名称
            $album_default = GetThumbConf($type);
            //创建默认相册
            
            if($album_info['total_num'] == 0){
            	 $aid = $this->album->addAlbum($uid, $album_default['name'],$type, $txtaddr = '', $txtdesc = '');
            	 if(!$aid) {
    				$return['status'] = 0;
    				$return['msg'] = '无法创建相册';
    				echo json_encode($return);
    				exit;
    			}
            } else {
            	$aid = $album_info['list'][0]['id'];
            }
           
            //保存照片到数据库
            $add = $this->album->addPhoto($uid, $aid, $pid, $type);
            if(!$add) {
				$return['status'] = 0;
				$return['msg'] = '无法更新照片信息';
				echo json_encode($return);
				exit;
			}
			
            $photo_info = $this->album->getPhotoInfo($pid);
            @unlink($pic_path);
			$array = array();
			$array['fid'] = $pid;
			$array['note'] = $aid;
			$ret = array();
			$ret[0]['pid'] = $pid;
			$ret[0]['groupname'] = $photo_info['groupname'];
			$ret[0]['filename'] = $photo_info['filename'];
			$ret[0]['type'] = $photo_info['type'];
			$ret[0]['size'] = json_decode($photo_info['notes'], true);
			$array['picurl'] = base64_encode(serialize($ret));
			
            $this->ajaxReturn($array, '', 1, 'jsonp'); 
        }
    }
    
    /**
     * 上传照片至配图默认相册
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
    	error_reporting(E_ALL);
    	//ini_set("display_errors", true);
        if(isset($_FILES['uploadPhotoFile'])){
            $type = R('type');
            if(!$type || !in_array($type, array('2', '3'))){
            }
            $this->load->model('uploadmodel', 'upload');
            $pid = $this->upload->uploadPic($_FILES['uploadPhotoFile'], $this->uid, 0, $type);
            if(!$pid){
				$this->ajaxReturn('', '无法上传图片', 0);
            }else{
            	$return['status']= 1;
            	$return['photo_id'] = $pid;
            	$this->ajaxReturn(array('photo_id'=>$pid), '', 1);
            }
            
            
        }else{
			$this->ajaxReturn('', '缺少相册数据', 0);
        }
    }
    
    public function uploadSavePhoto() {
    	error_reporting(E_ALL);
    	//ini_set("display_errors", true);
        $this->load->model('albummodel', 'album');
    	$type = htmlspecialchars($this->input->get('type'), ENT_QUOTES);
    	
    	$pids = htmlspecialchars($this->input->get('pids'), ENT_QUOTES);
    	$pids = explode(',', $pids);
        $photo_lists = $this->album->getPhotoList(array('uid' => $this->uid, 'id' => $pids, 'is_delete' => 1));
        $photo_quality = config_item('photo_quality');
            
        //查询默认相册是否创建
        $album_info = $this->album->getAlbumList(array(
            'uid' => $this->uid,
            'a_type' => $type,
            'total' => true,
            'web_id' => '0'
        ));
		
        //获得当前类型默认相册名称
        $this->load->model('uploadmodel', 'upload');
        $album_default = $this->upload->GetThumbConf($type);
        //创建默认相册
        
        
        $new_album = 0;
        if($album_info['total_num'] == 0){
        	$aid = $this->album->addAlbum($this->uid, $album_default['name'],$type, $txtaddr = '', $txtdesc = '', true);
        	
            if(!$aid) {
    			$this->ajaxReturn('', '无法创建相册', 0);
    		}else{
    			$new_album = 1;
    		}
        } else {
            $aid = $album_info['list'][0]['id'];
		}
	
		
		//批量照片信息
		$temptimesamps = time();
		$array = array();
		$array['data']['fid'] = $pids[0];
		$array['data']['note'] = $aid;
		$ret = array();
		//保存照片到数据库
		foreach($pids as $pid) {
			$pidAndDesc[] = array('picId' => $pid, 'picDesc' => '');
		}

    	$res = $this->album->addBathPhoto($this->uid, $aid, $pidAndDesc, 0, $photo_quality, $temptimesamps);
    	if(!$res){
    		$this->ajaxReturn('', $info = '上传照片失败!', $status = 0, $type = 'jsonp');
		}
		
		
		foreach($pids as $key=>$pid){
	        $photo_info = $this->album->getPhotoInfo($pid);
	    
			$ret[$key]['pid'] = $pid;
			$ret[$key]['groupname'] = $photo_info['groupname'];
			$ret[$key]['filename'] = $photo_info['filename'];
			$ret[$key]['type'] = $photo_info['type'];
			$ret[$key]['size'] = json_decode($photo_info['notes'], true);
		}
         //相册自动排序
		$this->album->autoUpdateAlbumOrder($this->uid, $aid);
        
		if($new_album){
			$this->album->checkAlbumCover($aid, null, $this->uid);
		}
		
		$array['data']['picurl'] = base64_encode(serialize($ret));
        if($type == 3){
            $this->ajaxReturn($array['data'], $info = '', $status = 1, $type = 'jsonp');
        }else{
        	$this->ajaxReturn($array['data'], $info = '', $status = 1, $type = 'jsonp');
			//echo "<script>window.parent.sendPhotoComplete('{$arr}')</script>";
        }
    }
    
	//公共上传照片接口跨域
	public function publicUploadCrossPhoto(){
    	$this->load->model('uploadmodel', 'upload');
    	$pic_type = intval($this->input->get('type'));
    	$from = $this->input->get('from');
  		if(empty($_FILES['Filedata'])) {
    		die('上传数据为空');
    	}
    	$res = $this->upload->publicUploadPhoto($_FILES['Filedata'], UID, $pic_type);
    	$domain = substr(DOMAIN, strpos(DOMAIN,'.')+1);
    	if(LOCAL_RUN){
    		$domain = substr($domain, 0, strpos($domain,'/'));
    	}
    	if(empty($from)){
	    	if($res){
				$upload_index	= intval($this->input->get_post('upload_index'));
				
	            $ret	= '<script type="text/javascript">document.domain="'.$domain.'";var params = '.json_encode(array('state' => '1', 'msg' => $res)).';  window.parent.uploadCallback.success(params,'.$upload_index.');  </script>';
				die($ret);
				//die('<script type="text/javascript">document.domain="'.$domain.'";var params = '.json_encode(array('state' => '1', 'msg' => $res)).'</script>');   
	        } else {
	        	$ret 	= '<script type="text/javascript">document.domain="'.$domain.'";var params = '.json_encode(array('state' => '0','msg' => '上传照片失败!!!')).';  window.parent.uploadCallback.success(params,'.$upload_index.');</script>';
				die($ret);
				//die('<script type="text/javascript">document.domain="'.$domain.'";var params = '.json_encode(array('state' => '0','msg' => '上传照片失败!!!')).'</script>');
	        }
    	}else{
	    	if($res){
	    		$this->ajaxReturn($res, $info='上传照片成功', $status = 1, $type = 'json');
		        //die('<script type="text/javascript">document.domain="'.$domain.'";var params = '.json_encode(array('state' => '1', 'msg' => $res)).'</script>');   
		    } else {
		    	$this->ajaxReturn('', $info='上传照片失败', $status = 0, $type = 'json');
		        //die('<script type="text/javascript">document.domain="'.$domain.'";var params = '.json_encode(array('state' => '0','msg' => '上传照片失败!!!')).'</script>');
		    }
    	}
		
    }
    /**
     * 个人头像上传接口
     */
    public function uploadHead(){
    	$filename = $this->input->get('yfile');
    	$file = urldecode($this->input->get('filePath'));
        if(isset($file)){
            $type = 1;
            $this->load->model('albummodel', 'album');
            //验证是否为头像相册的照片
            $pid = R('pid');
            if($pid){
                if($this->album->checkAlbumType($pid, $type)){
                	$photo_info = $this->album->getPhotoInfo($pid);
                    die(json_encode(array('pid' => $pid, 'groupname' => $photo_info['groupname'], 
	    			'filename' => $photo_info['filename'], 'type' => $photo_info['type'], 'notes' => $photo_info['notes'])));
                }
            }
            $this->load->model('uploadmodel', 'upload');
            $pid = $this->upload->uploadFileBuff($file, $this->uid, 0, 1, $type, $filename);
            if(!$pid){
            	die('f001');
            }
            $photo_info = $this->album->getPhotoInfo($pid);
            $photo_quality = config_item('photo_quality');
            
            //查询默认相册是否创建
            $album_info = $this->album->getAlbumList(array(
            	'uid' => $this->uid,
            	'a_type' => $type,
            	'total' => true,
            	'web_id' => '0'
            ));
            
            //获得当前类型默认相册名称
            $album_default = GetThumbConf($type);
            //创建默认相册
            
            if($album_info['total_num'] == 0){
            	 $aid = $this->album->addAlbum($this->uid, $aname = $album_default['name'], $type, $txtaddr = '', $txtdesc = '');
            	 if(!$aid){
            		die('f002');
            	 }
            	 $this->album->checkAlbumCover($aid, null, $this->uid); 
            } else {
            	$aid = $album_info['list'][0]['id'];
            }
           
            //保存照片到数据库
            $add = $this->album->addPhoto($this->uid, $aid, $pid, $type);
            
            //相册自动排序
			$this->album->autoUpdateAlbumOrder($this->uid, $aid);
            
			//更新相册搜索索引
			$this->album->photoSearchIndex(array($pid));
			$this->album->albumSearchIndex($aid, 0);
			
            if(!$add){
            	die('f003');
            }
            die(json_encode(array('pid' => $pid, 'groupname' => $photo_info['groupname'], 
	    							'filename' => $photo_info['filename'], 'type' => $photo_info['type'], 
	    							'notes' => $photo_info['notes'])));
        }
        
        die('f004');
    }
    
    
	/**
     * 封面上传接口(文件流方式)
     */
    public function uploadWithMap(){
    	$filename = $this->input->get('yfile');
    	$file = urldecode($this->input->get('filePath'));
        if(!empty($file)){
            $type = 2;
            $this->load->model('albummodel', 'album');
            //验证是否为封面相册的照片
            $pid = R('pid');
            if($pid){
            	$photo_info = $this->album->getPhotoInfo($pid);
                if($this->album->checkAlbumType($pid, $type)){
                    die(json_encode(array('pid' => $pid, 'groupname' => $photo_info['groupname'], 
	    			'filename' => $photo_info['filename'], 'type' => $photo_info['type'], 'notes' => $photo_info['notes'])));
                }
            }
            $this->load->model('uploadmodel', 'upload');
            $pid = $this->upload->uploadFileBuff($file, $this->uid, 0, 1, $type, $filename);
            if(!$pid) die('f001');
            
            $photo_info = $this->album->getPhotoInfo($pid);
            $photo_quality = config_item('photo_quality');
            
            //查询默认相册是否创建
            $album_info = $this->album->getAlbumList(array(
            	'uid' => $this->uid,
            	'a_type' => $type,
            	'total' => true,
            	'web_id' => '0'
            ));
            
            //获得当前类型默认相册名称
            $album_default = $this->upload->GetThumbConf($type);
            
            //创建默认相册
            if($album_info['total_num'] == 0){
            	 $aid = $this->album->addAlbum($this->uid, $aname = $album_default['name'], $type, $txtaddr = '', $txtdesc = '');
            	 if(!$aid) 
            		die('f002'); 
            } else {
            	$aid = $album_info['list'][0]['id'];
            }
           
            //保存照片到数据库
            $add = $this->album->addPhoto($this->uid, $aid, $pid, $type);
            if(!$add) 
            	die('f003');
            //更新相册删除状态为1
         	if($album_info['total_num'] == 0 || ($album_info['total_num'] == 1 && $album_info['list'][0]['is_delete'] == 2)){
            	 $update = $this->album->updateAlbum($aid, $this->uid, $update_data = array('is_delete' => 1), $type);
            	 if(!$update) 
            	 	die('f005');
            }
            
            //相册自动排序
			$this->album->autoUpdateAlbumOrder($this->uid, $aid);
			
			//更新相册搜索索引
			$this->album->photoSearchIndex(array($pid));
			$this->album->albumSearchIndex($aid, 0);
            die(json_encode(array('pid' => $pid, 'groupname' => $photo_info['groupname'], 
            						'filename' => $photo_info['filename'], 'type' => $photo_info['type'],
            						'notes' => $photo_info['notes'])));
        }
        die('f004');
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
        if(!$this->uid){
            $this->ajaxReturn('', '无效的用户', 0);
        }
        $callback = htmlspecialchars($this->input->get('callback'),ENT_QUOTES);
        $params = array(
            'uid'   =>    $this->uid,
            'id'    =>    $this->input->get('id'),
            'is_delete'	=>    1,
            'order'	=>    $this->input->get('order'),
            'page'	=>    $this->input->get('page'),
            'pagesize'	=>    $this->input->get('pagesize'),
        	'web_id' => '0'
        );
        $this->load->model('albummodel', 'album');
        $data = $this->album->getAlbumList($params);
        $return['status'] = 1;
        $return['data'] = $data['list'];
        //toJSON($return);
        header("Content-type: text/javascript");
        die("$callback(".json_encode($return).')');
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
        $aid = $this->input->get('aid');
        $callback = htmlspecialchars($this->input->get('callback'),ENT_QUOTES);
        if(!$this->uid){
            $this->ajaxReturn('', '无效的用户', 0);
        }
        if(!$aid){
            $this->ajaxReturn('', '无效的相册编号', 0);
        }
        $params = array(
            'uid'   =>    $this->uid,
            'aid'	=>    $aid,
            'id'    =>    $this->input->get('id'),
            'is_delete'	=>    1,
            'order'	=>    $this->input->get('order'),
            'page'	=>    $this->input->get('page'),
            'pagesize'	=>    $this->input->get('pagesize'),
        );
        $this->load->model('albummodel', 'album');
        $data = $this->album->getPhotoList($params);
        $return['status'] = 1;
        foreach($data['data'] as $key => $item){
            $data['data'][$key]['img'] = getImgPath($item['groupname'], $item['filename'], $item['type']);
        }
        $return['data'] = $data['data'];
        //toJSON($return);
        header("Content-type: text/javascript");
        die("$callback(".json_encode($return).')');
    }
    
   	/**
     * 相册数量调用接口
     * 如果page和pagesize不传值，则显示所有符合条件的照片列表
     * 
     */
    public function get_album_number()
    {
        if(!$this->uid){
            die(0);
        }
		$callback = htmlspecialchars($this->input->get('callback'),ENT_QUOTES);
        $params = array(
            'uid' => $this->action_uid,
            'is_delete'	=> 1,
            'total'	=> true,
        	'web_id' => 0,
        );
        $this->load->model('albummodel', 'album');
        $data = $this->album->getAlbumList($params);
        $this->ajaxReturn(array('num'=>$data['total_num']), '', 1, 'jsonp');
    }
    
}

/* End of file api.php */
/* Location: ./app/album/application/controllers/api.php */