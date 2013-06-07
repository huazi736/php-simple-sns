<?php
/**
 * @desc 上传图片、生成缩略图
 * @author lijianwei
 * @date 2012-02-24
 * @version $Id$
 */
class UploadModel extends MY_Model
{
	public function __construct()
    {
        parent::__construct();
    }
    
    
	public function insertPics($pt = array())
	{
		//插入数据库中
		$this->db->insert("user_photo", $pt);
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
	public function uploadPic($file = array(), $uid = 0, $aid = 0, $type = 0)
	{
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
		$pt['size'] = $file['size'];
		$pt['type'] = getImgType(strtolower(substr($file['name'],(strrpos($file['name'],'.')+1))));

		//上传原图到fdfs     
		$this->load->fastdfs('album','', 'fdfs');
		
		if($file['size'] > 1048576 || $img_data[0] > 1920  || $img_data[1] > 1920){
			$this->config->load('album');
			$local_server_tmp_storage_path = $this->config->item('storage_path');
			$org_filename =  rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." . $pt['type'];
			$image = get_image('default');
		    $image->resize_ratio($file['tmp_name'], $org_filename, 1920, 1920, 75);
		    $org_pic_info = $this->fdfs->uploadFile($org_filename, $pt['type']);
		    @unlink($org_filename);
		}else{
			$org_pic_info = $this->fdfs->uploadFile($file['tmp_name'], $pt['type']);
		}
		
		//验证是否上传成功
		if(!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename']))
		return false;

		$pt['filename'] = substr($org_pic_info['filename'], 0, strrpos($org_pic_info['filename'], "."));
		$pt['groupname'] = $org_pic_info['group_name'];
		
		//把图片信息放入数据库中
		//$pt['id'] = get_uuid();
		$pt['name'] = str_replace(".{$pt['type']}", "", $file['name']);
		$pt['size'] = $pt['size'];
		$pt['aid'] = $aid;
		$pt['uid'] = $uid;
		$pt['p_sort'] = 0;
		$pt['description'] = "";
		$pt['dateline'] = time();
		$pt['is_delete'] = 2;  //临时存储
		$pt['is_comment'] = 0;
		
		//生成缩略图
		$this->config->load('album');
		$photo_quality_cfg = $this->config->item('photo_quality');
		$photo_quality = $photo_quality_cfg['normal'];
		
		$thumb_pic = $this->thumbPic(array('type' =>$pt['type'], 'groupname' => $pt['groupname'], 'filename' => $pt['filename']), $photo_quality, $type, $file['tmp_name']);
		$sizes = $thumb_pic['sizes'];
		!empty($sizes) && $pt['notes'] = json_encode($sizes);
		
		if(!$this->insertPics($pt)) return false;
		$pid = $this->db->insert_id();
		
		//不成功的图片，写入日志和队列
		if(isset($thumb_pic['error_data']) && !empty($thumb_pic['error_data'])){
			$error_data = array($pid => $thumb_pic['error_data']);
			log_user_msg($uid, $error_data, '', 'ALBUM');
			
			$error_data = json_encode($error_data);
			$queue = get_httpsqs('album');
			$key = 'photo_queue_'.date('Ymd');
        	$queue->put($key, $error_data);
		}
		
		return $pid;
	}
	
	public function publicUploadPhoto($file = array(), $uid = null, $pic_type = 0)
	{
		//检查文件类型
		$src = $file['tmp_name'];
		if(isset($src) && $src){
		    $img_data = getimagesize($src);
		    if(!(is_numeric($img_data[2]) && in_array($img_data[2], array(1, 2, 3)))){
		        return false;
		    }
		}
		if(!is_file($src)) {
			echo 'ok';exit;
		}
		//检查文件大小
		if($file['size'] > 1048576 * 10){
		    return false;
		}
		$pt = array(); //返回数据
		$pt['size'] = $file['size'];
		$pt['type'] = getImgType(strtolower(substr($file['name'],(strrpos($file['name'],'.')+1))));

		//上传原图到fdfs     
		$this->load->fastdfs('album','', 'fdfs');
		if($file['size'] > 1048576 || $img_data[0] > 1920){
			$this->config->load('album');
			$local_server_tmp_storage_path = $this->config->item('tmp_storage_path');
			$org_filename =  @rtrim($local_server_tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." . $pt['type'];
			$image = get_image('default');
		    $image->resize_ratio($file['tmp_name'], $org_filename, 1920, 1920, 75);
		    $org_pic_info = $this->fdfs->uploadFile($org_filename, $pt['type']);
		    
		    @unlink($org_filename);
		}else{
			$org_pic_info = $this->fdfs->uploadFile($file['tmp_name'], $pt['type']);
		}
		//验证是否上传成功
		if(!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename']))
		return false;

		$pt['filename'] = substr($org_pic_info['filename'], 0, strrpos($org_pic_info['filename'], "."));
		$pt['groupname'] = $org_pic_info['group_name'];
		$pt['name'] = str_replace(".{$pt['type']}", "", $file['name']);
		$this->config->load('album');
		$photo_quality_cfg = $this->config->item('photo_quality');
		$photo_quality = $photo_quality_cfg['normal'];
		
		//设置相册类型及缩略图配置信息
		$pic_conf = GetThumbConf($pic_type); //大  中  小  尺寸 配置
		$local_server_tmp_storage_path = config_item("tmp_storage_path"); //临时存储图片路径，要有可写权限
		$is_concurrent = true; //判断是否并发
		$org_pic_url = $org_pic_info['filename'];
		//修改文件上传配置由一维数组改为二维数组配置 2012-03-08
		if(is_array($pic_conf['size'])){
		    $image = get_image('default');
			$loc_name = trim(substr($pt['filename'], strrpos($pt['filename'], '/')+1));
			$sizes = array();
			
			foreach($pic_conf['size'] AS $key => $val){
				$processing_result = false;
				while(!$processing_result){
					$dst = rtrim($local_server_tmp_storage_path, "/") . "/" . $loc_name . "_" . $key . "." . $pt['type'];
					$processing_result = $image->$val['type']($src, $dst, $val['width'], $val['height'], $photo_quality);		
				}	
				//把生成的缩略图放入fdfs
				if(is_file($dst)){
					$sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
					if(is_file($dst)){
						while(!is_array($sign)){
					    	$sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
					    }
				    }
				    //增加缩略图尺寸记录
				    if(is_array($sign)){
				    	$size = getimagesize($dst);
					    $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
					    $img_url['img_'.$key] = getImgPath($pt['groupname'], $pt['filename'], $pt['type'], $key);
				    }
				}else{
					$processing_result = $this->image->$val['type']($src, $dst, $val['width'], $val['height'], $photo_quality);
					$sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
					$size = getimagesize($dst);
				    $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
				}
				@unlink($dst);
			}	
		}
		if($is_concurrent){
			@unlink($src);
		}
		$pt['photosizes'] = $sizes;
		$pt['img_url'] = $img_url;
		return $pt;
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
	public function thumbPic($pt = array(), $pic_quality_num, $pic_type = 0, $src)
	{
		//添加$pic_type参数,设置相册类型及缩略图配置信息
		$pic_conf = GetThumbConf($pic_type);
		$this->config->load('album');
		$local_server_tmp_storage_path = $this->config->item('storage_path');
		//从fdfs下载图片
		
		//配置由一维数组改为二维数组配置 2012-03-08
		if(is_array($pic_conf['size'])){
			$loc_name = trim(substr($pt['filename'], strrpos($pt['filename'], '/')+1));
			$this->load->fastdfs('album','', 'fdfs');
			$org_pic_url = $pt['filename']. "." . $pt['type'];
		    $image = get_image('default');
			$sizes = array();
			$size = getimagesize($src);
			$sizes['self'] = array('w' => $size[0], 'h' => $size[1]);
			$data = array();
			foreach($pic_conf['size'] AS $key => $val){
				$dst = rtrim($local_server_tmp_storage_path, "/") . "/" . $loc_name . "_" . $key . "." . $pt['type'];
				//把生成的缩略图放入fdfs
				if($image->$val['type']($src, $dst, $val['width'], $val['height'], $pic_quality_num)){
					if(file_exists($dst)){
						$sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
						while(!is_array($sign)){
					    	$sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
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
		@unlink($src);
		//修改结束
		return array(
			'sizes' => $sizes,
			'error_data'  => $data
		);
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
	 * @author 杨顺军
	 * @param int $type 相册类型
	 * 
	 * @reutrn array 相册缩略图配置
	 */
	public function GetThumbConf($type){
		switch($type){
			case 1 : //个人头像
				$thumb_config = config_item("thumb_head_sizes");
				break;
			case 2 : //相册封面
				$thumb_config = config_item("thumb_cover_sizes");
				break;
			case 3 : //配图相册
				$thumb_config = config_item("thumb_other_sizes");
				break;
			case 4 : //店铺配图
				$thumb_config = config_item("thumb_shops_sizes");
				break;
			default: //普通相册
				$thumb_config = config_item("thumb_pic_sizes");
		}
		
		return $thumb_config;
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
	public function uploadFileBuff($file = '', $uid = 0, $aid = 0, $is_delete = 2, $type = 0, $ptname = null)
	{	
		$pt = array(); //保存上传图片信息，方便存入数据库中 
		$img_data = getimagesize($file);
		$pt['type'] = getImgRealType($img_data[2]);
		if(empty($ptname)) {
			$pt['name'] = current(explode('.', end(explode('/', $file))));
		}else {
			$pt['name'] = current(explode('.', $ptname));
		}
		
		//$file =  substr($file, 0, strpos($file, '?'));
		$files = file_get_contents($file);
		$this->config->load('album');
		$tmp_storage_path = $this->config->item('storage_path');
        $local_name = rtrim($tmp_storage_path, "/") . "/" . date("YmdHis") . mt_rand(1000, 9999). "." .$pt['type'];
		$file_path = file_put_contents($local_name, $files);
	    //检查文件类型
	    
	    $img_data = getimagesize($local_name);
	    
	    if(!(is_numeric($img_data[2]) && in_array($img_data[2], array(1, 2, 3)))){
	    	return false;
	    }
		$pt['size'] = strlen($files);
		
		//上传原图到fdfs     
		$this->load->fastdfs('album','', 'fdfs');
		$org_pic_info = $this->fdfs->uploadFileByBuff($files, $pt['type'], $meta = array('hight'=>300, 'width'=>200, 'author'=>$uid), $group = array('group_name'=>config_item("fastdfs_group"),'filename'=>''));
		
		//验证是否上传成功
		if(!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename']))
		return false;

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
		$this->config->load('album');
		$photo_quality_cfg = $this->config->item('photo_quality');
		$photo_quality = $photo_quality_cfg['normal'];
		$thumb_pic = $this->thumbPic(array('type' =>$pt['type'], 'groupname' => $pt['groupname'], 'filename' => $pt['filename']), $photo_quality, $type, $local_name);
		
		$sizes = $thumb_pic['sizes'];
		!empty($sizes) && $pt['notes'] = json_encode($sizes);
		
		if(!$this->insertPics($pt)) return false;
		$pid = $this->db->insert_id();
		
		//不成功的图片，写入日志和队列
		if(isset($thumb_pic['error_data']) && !empty($thumb_pic['error_data'])){
			$error_data = array($pid => $thumb_pic['error_data']);
			log_user_msg($uid, $error_data, '', 'ALBUM');
			
			$error_data = json_encode($error_data);
			$queue = get_httpsqs('album');
			$key = 'photo_queue_'.date('Ymd');
        	$queue->put($key, $error_data);
		}
		
		@unlink($local_name);
		return $pid;

	}
}

/* End of file uploadmodel.php */
/* Location: ./app/album/application/albummodels/uploadmodel.php */