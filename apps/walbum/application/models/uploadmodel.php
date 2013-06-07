<?php
/**
 * @desc 上传图片、生成缩略图
 * @author lijianwei
 * @date 2012-02-24
 * @version $Id$
 */
class UploadModel extends My_Model 
{
    const USER_PHOTO = "user_photo";
	public function insertPics($pt = array())
	{
		//插入数据库中
		$this->db->insert(self::USER_PHOTO, $pt);
		return $this->db->affected_rows();
	}
	
	/**
	 * @desc 上传单张图片
	 * @author lijianwei
	 * @date 2012-02-24
	 * @param $file array 单张图片$_FILES
	 * @param $uid int 用户uid
	 * @param $aid int 相册id
	 * @param $type int 相册类型
	 * @return true or false
	 */
	public function uploadPic($file = array(), $uid = 0, $aid = 0)
	{
		//$begin_time = $this->time();
		
        $aid = $aid ? $aid : 0;//相册id;
		$uid = $uid ? $uid : 0;//用户id
		
		//检查文件类型
		if(isset($file['tmp_name']) && $file['tmp_name']){
		    $img_data = getimagesize($file['tmp_name']);
		    if(!(is_numeric($img_data[2]) && in_array($img_data[2], array(1, 2, 3)))){
		    	return false;
		    }
		}
		//检查文件大小
		if($file['size'] > 1048576 * 10){
			return false;
		}
		$pt = array(); //保存上传图片信息，方便存入数据库中 
		$type = getImgType(strtolower(substr($file['name'],(strrpos($file['name'],'.')+1))));

		//上传原图到fdfs     
		$this->load->fastdfs('album','', 'fdfs');
		if($file['size'] > 1048576 || $img_data[0] > 1920 || $img_data[1] > 1920){
			$this->config->load('album');
			$local_server_tmp_storage_path = $this->config->item('tmp_storage_path');
			$org_filename =  rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." . $type;
			$image = get_image('default');
		    $image->resize_ratio($file['tmp_name'], $org_filename, 1920, 1920, 75);
		    $org_pic_info = $this->fdfs->uploadFile($org_filename, $type);
		    
		    $img_data = getimagesize($org_filename);
		    @unlink($org_filename);
		}else{
			$org_pic_info = $this->fdfs->uploadFile($file['tmp_name'], $type);
		}
		
		//验证是否上传成功
        if(!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename'])){
        	return false;
        }
        
        $pt = array(
        	'name'        => htmlspecialchars(str_replace(".{$type}", "", $file['name']), ENT_QUOTES),
        	'filename'    => substr($org_pic_info['filename'], 0, strrpos($org_pic_info['filename'], ".")),
        	'groupname'   => $org_pic_info['group_name'],
        	'size'        => $file['size'],
        	'type'        => $type,
        	'aid'         => $aid,
        	'uid'         => $uid,
        	'p_sort'      => 0,
        	'description' => '',
        	'dateline'    => time(),
        	'is_delete'   => 2,
        	'is_comment'  => 0
        );
        
        //生成缩略图
		$p_list = array(
			'groupname'  => $pt['groupname'],
			'filename'   => $pt['filename'],
			'type'       => $type
		);
		$photo_quality_cfg = config_item('photo_quality');
        $photo_quality = $photo_quality_cfg['normal'];
		
		$thumb_pic = $this->thumbPic($file['tmp_name'], $p_list, $photo_quality, 0);
		$sizes = $thumb_pic['sizes'];
		
		if(!empty($sizes)){
			$sizes['self'] = array('w' => $img_data[0], 'h' => $img_data[1]);
			$pt['notes'] = json_encode($sizes);
		}	

        if(!$this->insertPics($pt)){
        	print_r($pt);
        	exit;
        	return false;
        };

		$id = $this->db->insert_id();
		
		/*
		$end_time = $this->time();
		$data = array('ns'=>$end_time-$begin_time);
		log_user_msg(UID, $data);
		*/
		//不成功的图片，写入日志和队列
		if(isset($thumb_pic['error_data']) && !empty($thumb_pic['error_data'])){
			$error_data = array($id => $thumb_pic['error_data']);
			log_user_msg($uid, $error_data, '', 'ALBUM');
			
			$error_data = json_encode($error_data);
			$queue = get_httpsqs('album');
			$key = 'photo_queue_'.date('Ymd');
        	$queue->put($key, $error_data);
		}
		
		return array(
			'photo_id' => $id,
			'img_s'    => $thumb_pic['img']
		);
	}
	
	/**
	 * @desc 生成图片缩略图
	 * @author vicente
	 * @date 2012-02-29
	 * @param array $src 服务器端的临时文件
	 * @param array $pt 图片信息
	 * @param int $pic_quality  图片清晰度
	 * @param int $pic_type 图片类型(0:普通; 1:个人头像; 2:相册封面; 3:配图相册)
	 * @return true or false
	 */
	public function thumbPic($src, $pt = array(), $pic_quality_num, $pic_type = 0)
	{
		$sizes = array();
		$data = array();
		$pic_conf = $this->GetThumbConf($pic_type);
		$this->config->load('album');
		$local_server_tmp_storage_path = $this->config->item('tmp_storage_path');
		$pic_data = '';
		
		$loc_name = trim(substr($pt['filename'], strrpos($pt['filename'], '/')+1));
		if(is_array($pic_conf['size'])){
			$org_pic_url = $pt['filename']. "." . $pt['type'];
		    $image = get_image('default');
		    $this->load->fastdfs('album','', 'fdfs');
			foreach($pic_conf['size'] AS $key => $val){
				$dst = rtrim($local_server_tmp_storage_path, "/") . "/" . $loc_name . "_" . $key . "." . $pt['type'];
				if($image->$val['type']($src, $dst, $val['width'], $val['height'], $pic_quality_num)) {
					if(is_file($dst)){	
					    $sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
					    while(!is_array($sign)){
					    	$sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
					    }
					    
					    if($key == 's'){
					    	$thumb_img = getImgPath($pt['groupname'], $pt['filename'], $pt['type'], $key);
					    }
					    
						//增加缩略图尺寸记录
		                if(is_array($sign)){
		                    $size = getimagesize($dst);
		                    $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
		                    @unlink($dst);
		                }else{
		                	//上传不成功的图片
		                	$data[$key] = array(
		                        'filename'  => $pt['filename'],
		                        'groupname' => $pt['groupname'], 
		                        'ext'       => $key, 
		                        'type'      => $pt['type'],
		                        'loc_pic'   => $dst,
		                        'day_file'  => date('Ymd')
		                    );
		                }
					}
				}
			}
		}

		return array(
			'sizes' => $sizes,
			'img'   => $thumb_img,
			'error_data'  => $data
		);
	}
	
	/**
	 * 信息流上传图片
	 * 
	 * @author yangshunjun
	 * 
	 * @param string $file
	 * @param integer $uid
	 * @param integer $aid
	 * @param integer $is_delete 图片状态  1:正常 2：临时存储
	 */
	public function uploadFileBuff($file = '', $uid = 0, $aid = 0, $is_delete = 2)
	{
		$pt = array(); //保存上传图片信息，方便存入数据库中 
		$img_data = getimagesize($file);
		$pt['type'] = getImgRealType($img_data[2]);
		$pt['name'] = current(explode('.', end(explode('/', $file))));
		
		$files = file_get_contents($file);
		$this->config->load('album');
        $tmp_storage_path = $this->config->item('tmp_storage_path');
        $local_name = rtrim($tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." .$pt['type'];
  
		$file_length = file_put_contents($local_name, $files);
		
		if($file_length <= 0){
			throw new MY_Exception("图片保存失败！");
		}
	    $img_data = getimagesize($local_name);
	    
	    if(!(is_numeric($img_data[2]) && in_array($img_data[2], array(1, 2, 3)))){
	    	throw new MY_Exception("格式有误！");
	    }
		$pt['size'] = strlen($files);

		//上传原图到fdfs     
		$this->load->fastdfs('album','', 'fdfs');
		$org_pic_info = $this->fdfs->uploadFileByBuff($files, $pt['type'], $meta = array('hight'=>300, 'width'=>200, 'author'=>$uid), $group = array('group_name'=>config_item("fastdfs_group"),'filename'=>''));

		//验证是否上传成功
		if(!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename'])){
			throw new MY_Exception("服务器忙，请重新上传！");
		}

		$pt['filename'] = substr($org_pic_info['filename'], 0, strrpos($org_pic_info['filename'], "."));
		$pt['groupname'] = $org_pic_info['group_name'];
		
		//把图片信息放入数据库中
		$pt['size'] = $pt['size'];
		$pt['aid'] = $aid;
		$pt['uid'] = $uid;
		$pt['p_sort'] = 0;
		$pt['description'] = "";
		$pt['dateline'] = time();
		$pt['is_delete'] = $is_delete;  //临时存储
		$pt['is_comment'] = 0;
	
		//生成缩略图
		$p_list = array(
			'groupname'  => $pt['groupname'],
			'filename'   => $pt['filename'],
			'type'       => $pt['type']
		);
		$photo_quality_cfg = config_item('photo_quality');
        $photo_quality = $photo_quality_cfg['normal'];
        
        $thumb_pic = $this->thumbPic($local_name, $p_list, $photo_quality, 0);
		$sizes = $thumb_pic['sizes'];
		
		if(!empty($sizes)){
			$sizes['self'] = array('w' => $img_data[0], 'h' => $img_data[1]);
			$pt['notes'] = json_encode($sizes);
		}	

		if(!$this->insertPics($pt)){
            throw new MY_Exception("服务器忙，请重新上传！");
        };
		@unlink($local_name);
		$id = $this->db->insert_id();
		
		//不成功的图片，写入日志和队列
		if(isset($thumb_pic['error_data']) && !empty($thumb_pic['error_data'])){
			$error_data = array($id => $thumb_pic['error_data']);
			log_user_msg($uid, $error_data, '', 'ALBUM');
			
			$error_data = json_encode($error_data);
			$queue = get_httpsqs('album');
			$key = 'photo_queue_'.date('Ymd');
        	$queue->put($key, $error_data);
		}
		
		return $id;
	}
	
	public function time()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
	
	/**
	 * 判断此照片是否有各种型号的缩略图，如果没有重新生成
	 * 
	 * @author vicente
	 * @param array $photo_info 图片信息
	 * @param int $pic_type 图片类型(0:普通; 1:个人头像; 2:相册封面; 3:配图相册)
	 * @param int $pic_quality  图片清晰度
	 * @deprecated 暂不使用
	 * @return boolean
	 */
	public function setThumbPic($photo_info, $pic_type = 0, $pic_quality_num = 75)
	{
		$pic_conf = $this->GetThumbConf($pic_type);
		if(!empty($photo_info['notes'])){
			$notes = json_decode($photo_info['notes'], true);
			if(!is_array($pic_conf['size']) || array_keys($pic_conf['size']) === array_keys($notes)) return true;
			
			//临时存储图片路径，要有可写权限
			$local_server_tmp_storage_path = config_item("tmp_storage_path"); 
			$org_filename =  rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." . $photo_info['type'];
			$this->load->model("fdfsmodel", "fdfs");
			$image = get_image('default');
			$this->fdfs->download_filename($photo_info['groupname'], $photo_info['filename'] . "." . $photo_info['type'], $org_filename);
			$src = $org_filename;
			
			$org_pic_url = $photo_info['filename']. "." . $photo_info['type'];
			$sizes = array();
			foreach ($pic_conf['size'] as $key=>$val){
				if(!array_key_exists($key, $notes)){
					$dst = rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "_" . $key . "." . $photo_info['type'];
					if($image->$val['type']($src, $dst, $val['width'], $val['height'], $pic_quality_num)) {
						if(is_file($dst)){	
						    $sign = $this->fdfs->upload_slave_filename($dst, $org_pic_url, "_" . $key, $photo_info['groupname']);
						    while(!is_array($sign)){
						    	$sign = $this->fdfs->upload_slave_filename($dst, $org_pic_url, "_" . $key, $photo_info['groupname']);
						    }
						    
						    //增加缩略图尺寸记录
						    $size = getimagesize($dst);
						    $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
						}
			
						@unlink($dst);
					}	
				}
			}
			
			if(!empty($sizes)){
				$notes = array_merge($notes, $sizes);
			}
			
			@unlink($org_filename);
			
			return $notes;
		}else{
			//临时存储图片路径，要有可写权限
			$local_server_tmp_storage_path = config_item("tmp_storage_path"); 
			$org_filename =  rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." . $photo_info['type'];
			$this->load->model("fdfsmodel", "fdfs");
			$this->load->library("Storage/Image", "", "image");
			$this->image->set_library('imagick');
			$this->fdfs->download_filename($photo_info['groupname'], $photo_info['filename'] . "." . $photo_info['type'], $org_filename);
			$src = $org_filename;
			
			$org_pic_url = $photo_info['filename']. "." . $photo_info['type'];
			$sizes = array();
			foreach ($pic_conf['size'] as $key=>$val){
				$dst = rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "_" . $key . "." . $photo_info['type'];
				if($this->image->$val['type']($src, $dst, $val['width'], $val['height'], $pic_quality_num)) {
					if(is_file($dst)){	
					    $sign = $this->fdfs->upload_slave_filename($dst, $org_pic_url, "_" . $key, $photo_info['groupname']);
					    while(!is_array($sign)){
					    	$sign = $this->fdfs->upload_slave_filename($dst, $org_pic_url, "_" . $key, $photo_info['groupname']);
					    }
					    
					    //增加缩略图尺寸记录
					    $size = getimagesize($dst);
					    $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
					}
		
					@unlink($dst);
				}	
			}
			
			@unlink($org_filename);
			if(!empty($sizes)){
				return $sizes;
			}
		}

		return true;
	}
	
	/**
	 * @desc 删除过期图片， 因用户取消上传、直接关闭浏览器造成垃圾图片，定期删除。需要在config.php中配置  tmp_pics_delete_interval  间隔时间  单位为s
	 * @author lijianwei
	 * @date 2012-02-29
	 * @return true or false
	 */
	public function deleteTmpPics() {
		$tmp_pics_delete_interval = config_item("tmp_pics_delete_interval");
		$map = array();
		$map['is_delete'] = 0;
		$map['dateline < '] = time()-$tmp_pics_delete_interval;

		$delete_pics = $this->db->select("filename,type")->from("user_photo")->where($map)->limit(100)->get()->result_array();

		if(is_array($delete_pics) && count($delete_pics) > 1) {
			$this->load->model("fdfsmodel", "fdfs");
			foreach($delete_pics as $pic_info) {
				//$parse_pic_info = $this->parsePicInfo($pic_info);
				//删除对应在fdfs原图、大、中、小图
				$this->fdfs->delete_filename($pic_info['gropuname'], $pic_info['filename']. "." . $pic_info['type']);
				$this->fdfs->delete_filename($pic_info['gropuname'], $pic_info['filename']. "_s." . $pic_info['type']);
				$this->fdfs->delete_filename($pic_info['gropuname'], $pic_info['filename']. "_m." . $pic_info['type']);
				$this->fdfs->delete_filename($pic_info['gropuname'], $pic_info['filename']. "_b." . $pic_info['type']);
			}
			$this->db->where($map)->limit(100)->delete("user_photo");
		}
		return true;
	}
	/**
	 * @deprecated 暂不使用
	 * @param array $pic_info 图片信息
	 */
	public function parsePicInfo($pic_info = array()) 
	{
		if(empty($pic_info)) return false;

		$filename = $pic_info['filename'];
		$filename = str_replace("http://". config_item("fastdfs_host"). "/", "", $filename);

		$group_name = substr($filename, 0, strpos($filename, "/"));
		$filename = substr($filename, strpos($filename, "/") + 1);
		return array('group_name' => $group_name, "filename" =>$filename);
	}
	
	/**
	 * @author 杨顺军
	 * @param int $type 相册类型
	 * 
	 * @reutrn array 相册缩略图配置
	 */
	public function GetThumbConf($type){
		$this->config->load('album');
		switch($type){
			case 1 : //个人头像
				$thumb_config = $this->config->item('thumb_head_sizes');
				break;
			case 2 : //相册封面
				$thumb_config = $this->config->item('thumb_cover_sizes');
				break;
			case 3 : //配图相册
				$thumb_config = $this->config->item('thumb_other_sizes');
				break;
			default: //普通相册
				$thumb_config = $this->config->item('thumb_pic_sizes');
		}
		
		return $thumb_config;
	}
	
	/**
	 * @desc 上传单张图片
	 * @author lijianwei
	 * @date 2012-02-24
	 * @param $file array 单张图片$_FILES
	 * @param $uid int 用户uid
	 * @param $aid int 相册id
	 * @param $type int 相册类型
	 * @return true or false
	 */
	public function uploadPic_old($file = array(), $uid = 0, $aid = 0)
	{
		$begin_time = $this->time();
        $aid = $aid ? $aid : 0;//相册id;
		$uid = $uid ? $uid : 0;//用户id
		
		//检查文件类型
		if(isset($file['tmp_name']) && $file['tmp_name']){
		    $img_data = getimagesize($file['tmp_name']);
		    if(!(is_numeric($img_data[2]) && in_array($img_data[2], array(1, 2, 3)))){
		    	return false;
		        //throw new MY_Exception("图片类型有误！"); 
		    }
		}
		//检查文件大小
		if($file['size'] > 4194304){
			return false;
            //throw new MY_Exception("图片过大，上传失败！");
		}
		$pt = array(); //保存上传图片信息，方便存入数据库中 
		$pt['size'] = $file['size'];
		$pt['type'] = getImgType(strtolower(substr($file['name'],(strrpos($file['name'],'.')+1))));

		//上传原图到fdfs     
		$this->load->model("fdfsmodel", "fdfs");
		log_user_msg(UID, array('a'=>$this->fdfs));
		$org_pic_info = $this->fdfs->upload_filename($file['tmp_name'], $pt['type']);
		//验证是否上传成功
        if(!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename'])){
        	return false;
            //throw new MY_Exception("服务器忙，上传失败！");
        }

		$pt['filename'] = substr($org_pic_info['filename'], 0, strrpos($org_pic_info['filename'], "."));
		$pt['groupname'] = $org_pic_info['group_name'];
		
		//把图片信息放入数据库中
		$pt['name'] = str_replace(".{$pt['type']}", "", $file['name']);
		$pt['size'] = $pt['size'];
		$pt['aid'] = $aid;
		$pt['uid'] = $uid;
		$pt['p_sort'] = 0;
		$pt['description'] = "";
		$pt['dateline'] = time();
		$pt['is_delete'] = 2;  //临时存储
		$pt['is_comment'] = 0;

        if(!$this->insertPics($pt)){
        	return false;
            //throw new MY_Exception("服务器忙，请重新上传！");
        };

		$id = $this->db->insert_id();
	
		//生成缩略图
		$p_list = array(
			'id'         => $id,
			'groupname'  => $pt['groupname'],
			'filename'   => $pt['filename'],
			'type'       => $pt['type']
		);
		$photo_quality_cfg = config_item('photo_quality');
        $photo_quality = $photo_quality_cfg['normal'];
		
		$this->thumbPic_old($p_list, $photo_quality, 0);
		
		$end_time = $this->time();
				
		$data = array(
			'o_time'=> round(($end_time - $begin_time), 2).'s'
		);
				
		//log_user_msg(UID, $data);
		
		return $id;
	}
	
	/**
	 * @desc 生成图片缩略图
	 * @author lijianwei
	 * @date 2012-02-29
	 * @param array $pt 图片信息
	 * @param int $pic_quality  图片清晰度
	 * @param int $pic_type 图片类型(0:普通; 1:个人头像; 2:相册封面; 3:配图相册)
	 * @return true or false
	 */
	public function thumbPic_old($pt = array(), $pic_quality_num, $pic_type = 0)
	{
		$pic_conf = $this->GetThumbConf($pic_type); //大  中  小  尺寸 配置
		$local_server_tmp_storage_path = config_item("tmp_storage_path"); //临时存储图片路径，要有可写权限
		$org_filename =  rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." . $pt['type'];
		//从fdfs下载图片
		$this->load->model("fdfsmodel", "fdfs");
		$this->fdfs->download_filename($pt['groupname'], $pt['filename'] . "." . $pt['type'], $org_filename);

		$src = $org_filename;
		$org_pic_url = $pt['filename']. "." . $pt['type'];
		
		//by 杨顺军  修改文件上传配置由一维数组改为二维数组配置 2012-03-08
		if(is_array($pic_conf['size'])){
		    $this->load->library("Storage/Image", "", "image");
		    $this->image->set_library('magick');
			$sizes = array();
			foreach($pic_conf['size'] AS $key => $val){
				$begin_time = $this->time();
				$error = '';
				$dst = rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "_" . $key . "." . $pt['type'];
				if($this->image->$val['type']($src, $dst, $val['width'], $val['height'], $pic_quality_num)) {
					//把生成的缩略图放入fdfs
					if(is_file($dst)){	
					    $sign = $this->fdfs->upload_slave_filename($dst, $org_pic_url, "_" . $key, $pt['groupname']);
					    while(!is_array($sign)){
					    	$error = $val['type'];
					    	$sign = $this->fdfs->upload_slave_filename($dst, $org_pic_url, "_" . $key, $pt['groupname']);
					    }
					    
					    //增加缩略图尺寸记录
					    $size = getimagesize($dst);
					    $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
					}else{
						$this->fdfs->upload_slave_filename($src, $org_pic_url, "_" . $key, $pt['groupname']);
						$err = 'no';
					}
		
					//@unlink($dst);
				}
				
				$end_time = $this->time();
				
				$data = array(
					'id'  => $pt['id'],
					'file'=> $org_filename,
					'info' => $val,
					'sign'=> $sign,
					's'=> $error,
					'time'=> round(($end_time - $begin_time), 2).'s'
				);
				
				//log_user_msg(UID, $data);
			}
			
			if(!empty($sizes)){
				$data = array(
	               'notes' => json_encode($sizes),
	            );
	
	            $this->db->where('id', $pt['id']);
	            $this->db->update(USER_PHOTO, $data);
			}
			
			//@unlink($org_filename);
		}

		//修改结束
		return true;
	}
}
