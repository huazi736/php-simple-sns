<?php

/**
 * 头像封面
 * @author lvxinxin
 * @date <2012/03/23>
 *
 */
class Avatar extends MY_Controller
{		
	private $mduid;
	private $file_util;
	private	$myupload;
    public function __construct()
    {
        parent::__construct();		
		$this->load->redis('avatar','','redisdb');
		$this->load->fastdfs('avatar','', 'fdfs');		
		$this->mduid = md5($this->uid);
    }

    function index()
    {
        $this->set_avatar();
    }
	
	public function _show(){
		$this->assign('username', $this->user['username']);
		$this->assign('avatar50', get_avatar($this->uid));
		$this->assign('avatar_url',mk_url('main/index/profile'));
		$this->assign('avatar_upload', mk_url('main/avatar/avatar_upload'));
		$this->assign('url',mk_url('main/avatar/set_avatar'));		
		$this->display('timeline/upload_protrait.html');
	}
    function set_avatar()
    {        
        $camera = $this->input->get('camera');       
        $path = $this->input->get('p');
        $pid = $this->input->get('pid');		
        $this->_show();
        if (! empty($camera))
        {
            echo '<script type="text/javascript">$(function(){useCamera();})</script>';
        }
        if (! empty($path))
        {
            $upPath = config_item('avatar_root').'/';
            $avatar = $upPath . $this->mduid . '.jpg';			
            if (file_exists($avatar))
            {
                @unlink($avatar);
            }
            $a = @file_put_contents($avatar, @file_get_contents($path));
            if ($a > 0)
            {
                $web_path = config_item('avatar_webroot')  . $this->mduid . '.jpg?v='.time();
				if(WEB_ROOT == 'http://www.duankou.com/'){
					$api = 'http://127.0.0.1/index.php?app=album&controller=api&action=uploadHead&pid=' . $pid . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
				}
				else{
					$api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'pid'=>$pid));
				}
				// $api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'pid'=>$pid));
                // $api = WEB_ROOT . 'index.php?app=album&controller=api&action=uploadHead&pid=' . $pid . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
				$flag = call_curl($api);
				$_SESSION['avatartotl'] = json_decode($flag,true);            					
				list ($width, $height) = @getimagesize($avatar);				
				if($width > 2800 || $height >2800){
					$s = $this->pro_avatar($avatar, $this->uid, $width, $height);
					if(!$s){					
						echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
						echo '<script type="text/javascript">$.alert("' . $this->myupload->display_errors('','') . '");</script>';
            			exit;
					}					
				}			                
                echo '<script type="text/javascript">$(function(){$(".uploadHeader").hide();});</script>';
                echo '<script type="text/javascript">window.parent.hideLoading();window.parent.buildAvatarEditor("' . md5($this->uid) . '","' . $web_path . '?v=' . time() .
                 '","photo");</script>';
            }
            else
            {			
                echo '<script type="text/javascript">$.alert("保存图片失败");</script>';
                exit();
            }             
        }
    }

    /**
     * 通过本地上传方式更新头像
     *
     * 操作相册、头像
     * @author lvxinxin
     * @date   2012-04-25
     * @access public
     * @last-modify: 2011-12-09 liuGC
     * @return JS
     */
    public function setProfilePic()
    { 
        include_once(EXTEND_PATH.'vendor/File_util.php');
		$this->file_util = new File_util();
        $from = $this->input->post('from');
        $pic = $this->input->post('pic');
        $pid = $this->input->post('pid');
        $callback = $this->input->post('callback');
        if (! empty($from) && ! empty($pic))
        {
            $upPath = config_item('avatar_root').'/'; 
            $avatar = $upPath . $this->mduid . '_f.jpg';
        	if (! is_dir($upPath))
        	{
            	$this->file_util->createDir($upPath);
       		}
            if (file_exists($avatar))
            {
                @unlink($avatar);
            }
            $a = @file_put_contents($avatar, @file_get_contents($pic));
            if ($a > 0)
            {				
                $this->proPic($avatar,'',false,$pid);
            }            
        }
        $upload_config['upload_path'] = config_item('avatar_root').'/'; //上传路径
        $upload_config['allowed_types'] = 'jpg|jpeg|gif|png'; //文件上传类型
        $upload_config['overwrite'] = true; //同名文件覆盖
        $upload_config['file_name'] = $this->mduid . '_f.jpg'; //指定文件名
        $upload_config['max_size'] = 4096;
        include_once(EXTEND_PATH.'libraries/DK_Upload.php');
		$this->myupload = new DK_Upload($upload_config);		
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");         
        if (! is_dir($upload_config['upload_path']))
        {
            $this->file_util->createDir($upload_config['upload_path']);
        }
        if ($this->myupload->do_upload('FileData'))
        {
			$img_info = $this->myupload->data();
            $filePath = $upload_config['upload_path'] . $this->mduid . '_f.jpg';			
            $this->proPic($filePath,$callback);
        }
        else
        {
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            die("<script>parent.". $callback ."({'status':0,'data':'novia','msg':'" . $this->myupload->display_errors('', '') . "'});</script>");
			
		}
         
    }
	
    /**
     * 设置封面
     */
    public function set_cover()
    {		
        $height = abs($this->input->post('top'));       
        $this->load->library('image_lib');
        $config['image_library'] = 'gd2';
        $config['source_image'] = config_item('avatar_root').'/' . $this->mduid . '_thumb.jpg';
        $config['new_image'] = $this->mduid . '_cover.jpg';
        $config['y_axis'] = $height;
        list ($width, $height) = getimagesize($config['source_image']);
		if($height > 315)  $config['height'] = 315;        
        $this->image_lib->initialize($config);
        if (! $this->image_lib->crop())
        {
            die(json_encode(array('status' => 0, 'msg' => $this->image_lib->display_errors('', ''))));             
        }
        else
        {
			$fpath = realpath( config_item('avatar_root').'/'  . $this->mduid  .'_cover.jpg');
			if($fpath != false){
				$Mfdata = $this->fdfs->uploadFile($fpath,'jpg');				
				if(is_array($Mfdata)){
					$mf = preg_replace('/\/[A-Za-z0-9]*\//is','',$_SESSION['user']['coverurl'],1);
					$path = '/' . $Mfdata['group_name'] . '/' . $Mfdata['filename'];
					$this->load->model('avatarmodel');
					$res = $this->avatarmodel->save_cover($this->uid,$path);
					if($res){						
						set_cache('cover_'.$this->uid,$path);
						$this->fdfs->deleteFile('',$mf);//删除原图
						if(!empty($_SESSION['avatartotl'])){
							$img_size = json_decode($_SESSION['avatartotl']['notes'],true);
							if(empty($img_size)) {
								$h = 125;
								$w = 125;
							}
							else
							{
								$h = @$img_size['f']['h'];
								$w = @$img_size['f']['w'];
							}
							$data = array(
							 'uid'=>$this->uid,
							 'dkcode'=>$this->dkcode,
							 'uname'=>$this->username,
							 'permission'=>4,
							 'from'=>5,
							 'type'=>'change',
							 'dateline'=>time(),
							 'ctime'=>time(),			
							 'filename'=>$_SESSION['avatartotl']['filename'],			
							 'union' => 'cover',
							 'fid' =>$_SESSION['avatartotl']['pid'],
							 'groupname'=>$_SESSION['avatartotl']['groupname'],
							 'imgtype'=>$_SESSION['avatartotl']['type'],
							 'height'=>$h,
							 'width'=>$w
						 );
						 
						api('Timeline')->addTimeLine($data);
						unset($_SESSION['avatartotl']);
						}						
						//第一次传封面+积分
						@service('credit')->cover();
						 
						 unset($_SESSION['avatartotl']);
						die(json_encode(array('status' => 1,'data'=>$height)));
					}
					else{
						die(json_encode(array('status' => 0,'data'=>'save to database faild')));
					}
				}
				else{
					die(json_encode(array('status' => 0,'data'=>'fastdfs save faild!')));
				}
			}
			else{
				die(json_encode(array('status' => 0,'data'=>'file path is error')));
			}
			
					
        }
    }

    /**
     *上传头像
     */
    public function avatar_upload()
    {       
        $upload_config['upload_path'] = config_item('avatar_root').'/'; //上传路径
        $upload_config['allowed_types'] = 'jpg|jpeg|gif|png'; //文件上传类型
        $upload_config['overwrite'] = true; //同名文件覆盖
        $upload_config['file_name'] = $this->mduid.'.jpg'; //指定文件名
        $upload_config['max_size'] = 4096;
        include_once(EXTEND_PATH.'libraries/DK_Upload.php');
		include_once(EXTEND_PATH.'vendor/File_util.php');
		$this->file_util = new File_util();
		$this->myupload = new DK_Upload($upload_config);
        if (! is_dir($upload_config['upload_path']))
        {
            $this->file_util->createDir($upload_config['upload_path']);
        }        
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");		
        if ($this->myupload->do_upload('Filedata'))
        {			
        	$img_info = $this->myupload->data();			
			if($img_info['image_width'] > 2800 || $img_info['image_height'] > 2800){				
				$s = $this->pro_avatar($upload_config['upload_path'].md5($this->uid).'.jpg', $this->uid, $img_info['image_width'], $img_info['image_height']);
				if(!$s){
					$this->_show();
					echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
					echo '<script type="text/javascript">$.alert("' . $this->myupload->display_errors('','') . '");</script>';
            		exit;
				}				
			}
            
			$web_path = config_item('avatar_webroot') . $this->mduid . '.jpg?v='.time(); 
			if(WEB_ROOT == 'http://www.duankou.com/'){
				$api = 'http://127.0.0.1/index.php?app=album&controller=api&action=uploadHead&dkcode=' . $this->dkcode . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
			}
			else{
				$api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'dkcode'=>$this->dkcode));
			}
			// $api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'dkcode'=>$this->dkcode));
            // $api = WEB_ROOT . 'index.php?app=album&controller=api&action=uploadHead&dkcode=' . $this->dkcode . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
				
			$flag = call_curl($api);			
			$_SESSION['avatartotl'] = json_decode($flag,true);
			
            echo '<script type="text/javascript">window.parent.hideLoading();window.parent.buildAvatarEditor("' . $this->mduid . '","' . $web_path .
         	'","photo");</script>';        
        	exit();
        }
        else
        {
			$this->_show();
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';            
            echo '<script type="text/javascript">window.parent.$.alert("' . $this->myupload->display_errors('',''). '");parent.hideLoading();</script>';
            exit;
        }       
        
    }

    /**
     *保存头像
     */
    public function avatar_save()
    {       
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");
              
        $pic_size = $this->input->get('type');
        $type = isset($pic_size) ? $pic_size : 'big';		
        if ($type == 'big')
        {
            $type = 'b';            
        }         
        $pic_path = config_item('avatar_webroot') . $this->mduid . "_" . $type . ".jpg";     
        $file_addr = config_item('avatar_root'); 
		include_once(EXTEND_PATH.'vendor/File_util.php');
		$this->file_util = new File_util();
        if (! file_exists($file_addr))
        {
            $this->file_util->createDir($file_addr);
        }     
        $pic_abs_path = $file_addr . substr($pic_path, strrpos($pic_path, '/'));
		
        if(!file_put_contents($pic_abs_path, file_get_contents("php://input")))
		{
			$d = new pic_data();
			$d->data->urls[0] = $pic_path;
			$d->status = 0;
			$d->statusText = '保存失败!';
			die(json_encode($d));
		}
        $avtar_img = imagecreatefromjpeg($pic_abs_path);
        imagejpeg($avtar_img, $pic_abs_path, 100); 	
		$fpath = realpath($pic_abs_path);
		if($fpath != false){			
			$flag = $this->redisdb->get($this->uid);
			$setting = getConfig('fastdfs','avatar');
			if(empty($flag)){
				@service('credit')->avatar();
				$Mfdata = $this->fdfs->uploadFile($fpath,'jpg');
				$fast_path = 'http://' . $setting['host'] . '/' . $setting['group'] . '/' .$Mfdata['filename'];
				$this->redisdb->set($this->uid,$fast_path);
			}
			else{
				$this->_delete_fastdfs_file();
				@service('credit')->avatar();
				$Mfdata = $this->fdfs->uploadFile($fpath,'jpg');
				$fast_path = 'http://' . $setting['host'] . '/' . $setting['group'] . '/' .$Mfdata['filename'];
				$this->redisdb->set($this->uid,$fast_path);
			}
		}	
		
		$this->create_avatar($this->mduid, $file_addr, $avtar_img); 		
		$this->_delete_local_avatar($file_addr);		
		if(!empty($_SESSION['avatartotl'])){
			$img_size = json_decode($_SESSION['avatartotl']['notes'],true);
			if(empty($img_size)) {
				$h = 125;
				$w = 125;
			}
			else
			{
				$h = @$img_size['f']['h'];
				$w = @$img_size['f']['w'];
			}
			$data = array(
			 'uid'=>$this->uid,
			 'dkcode'=>$this->dkcode,
			 'uname'=>$this->username,
			 'permission'=>4,
			 'from'=>5,
			 'type'=>'change',
			 'dateline'=>time(),
			 'ctime'=>time(),			
			 'filename'=>$_SESSION['avatartotl']['filename'],			
			 'union' => 'face',
			 'fid' =>$_SESSION['avatartotl']['pid'],
			 'groupname'=>$_SESSION['avatartotl']['groupname'],
			 'imgtype'=>$_SESSION['avatartotl']['type'],
			 'height'=>$h,
			 'width'=>$w
			);
			api('Timeline')->addTimeLine($data);
			unset($_SESSION['avatartotl']);
		}
		
        $d = new pic_data();
        $d->data->urls[0] = $pic_path;
        $d->status = 1;
        $d->statusText = '上传成功!';
        die(json_encode($d));
    }

    /**
     *生成缩略图！
     */
    public function create_avatar($uid, $file_addr, $res)
    {
        if (empty($uid))
        {
            return false;
        }
        $s30_res = imagecreatetruecolor(30, 30);
        $s50_res = imagecreatetruecolor(50, 50);
		$s65_res = imagecreatetruecolor(65, 65);//--add
        $s100_res = imagecreatetruecolor(100, 100);
        imagecopyresampled($s30_res, $res, 0, 0, 0, 0, 30, 30, 125, 125);
        imagecopyresampled($s50_res, $res, 0, 0, 0, 0, 50, 50, 125, 125);
		imagecopyresampled($s65_res, $res, 0, 0, 0, 0, 65, 65, 125, 125);//--add
        imagejpeg($s30_res, $file_addr . '/' . $uid . '_ss.jpg', 100);
        imagejpeg($s50_res, $file_addr . '/' . $uid . '_s.jpg', 100);        
        imagejpeg($s65_res, $file_addr . '/' . $uid . '_mm.jpg', 100);//--add
        imagecopyresampled($s100_res, $res, 0, 0, 0, 0, 100, 100, 125, 125);        
        imagejpeg($s100_res, $file_addr . '/' . $uid . '_m.jpg', 100);        
        imagedestroy($s30_res);
        imagedestroy($s50_res);
		imagedestroy($s65_res);//--add
        imagedestroy($s100_res);        
        imagedestroy($res);        
    }
	public function _delete_fastdfs_file(){		
		$this->_delFile('_ss.jpg');
		$this->_delFile('_s.jpg');
		$this->_delFile('_mm.jpg');
		$this->_delFile('_m.jpg');
		$this->_delFile('_b.jpg');
		$this->_delFile();
	}
	public function _delete_local_avatar($file_addr){        
        $avatar_ss = $file_addr . '/' . $this->mduid . '_ss.jpg';		
        $avatar_s = $file_addr . '/' . $this->mduid . '_s.jpg';
		$avatar_mm = $file_addr . '/' . $this->mduid . '_mm.jpg'; //--add
        $avatar_m = $file_addr . '/' . $this->mduid . '_m.jpg';
        $avatar_b = $file_addr . '/' . $this->mduid . '_b.jpg';
		$avatar = $file_addr . '/' .  $this->mduid . '.jpg';
        if (file_exists($avatar_ss))
        {			
			$this->_saveFile($avatar_ss,'_ss');
            @unlink($avatar_ss);
		}
        if (file_exists($avatar_s))
        {
			$this->_saveFile($avatar_s,'_s');
            @unlink($avatar_s);
		}
		if (file_exists($avatar_mm))  //--add
        {
			$this->_saveFile($avatar_mm,'_mm');
            @unlink($avatar_mm);
		}
        if (file_exists($avatar_m))
        {
			$this->_saveFile($avatar_m,'_m');
            @unlink($avatar_m);
		}
        if (file_exists($avatar_b))
        {
			$this->_saveFile($avatar_b,'_b');
            @unlink($avatar_b);
		}
		if (file_exists($avatar))
        {
            @unlink($avatar);
        }
	}
    /**
     * 删除头像
     */
    public function delete_avatar()
    {        
        if (! $this->uid)
        {
            echo '<script type="text/javascript">alert("session失效!");</script>';
            exit();
        }
		$this->_delFile('_ss.jpg');
		$this->_delFile('_s.jpg');
		$this->_delFile('_mm.jpg'); //--add
		$this->_delFile('_m.jpg');
        $this->_delFile('_b.jpg');
		$this->_delFile();
		
        exit(json_encode(array('status' => 1,'data'=>MISC_ROOT.'img/default/avatar_b.gif')));
    }

    /**
     * 删除封面
     */
    public function delete_cover()
    {
    	$avatar_pic = config_item('avatar_root').'/';
		$avatar_f = $avatar_pic . $this->mduid . '_f.jpg';
        $avatar_thumb = $avatar_pic . $this->mduid . '_thumb.jpg';
        $avatar_cover = $avatar_pic . $this->mduid . '_cover.jpg';
		@unlink($avatar_f);@unlink($avatar_thumb);@unlink($avatar_thumb);
        
		$this->load->model('avatarmodel');
		$res = $this->avatarmodel->del_cover($this->uid);
		if($res){
			set_cache('cover_'.$this->uid,null);
			exit(json_encode(array('status' => 1,'msg'=>'')));
		}
		else{
			exit(json_encode(array('status' => 0,'删除失败')));
		}
        
    }
	
    /**
     * 保存图片至相册
     */
    public function save_album($pid = '',$avatar  = false){    	
    	if($avatar){
    		$web_path = config_item('avatar_webroot') . $this->mduid . '_b.jpg?v=' . time();    		
			if(WEB_ROOT == 'http://www.duankou.com/'){
				$api = 'http://127.0.0.1/index.php?app=album&controller=api&action=uploadHead&pid=' . $pid . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
			}
			else{
				$api = mk_url('album/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'pid'=>$pid));
			}
		}
    	else{
    		$web_path = config_item('avatar_webroot') . $this->mduid . '_f.jpg?v=' . time();
			if(WEB_ROOT == 'http://www.duankou.com/'){
				$api = 'http://127.0.0.1/index.php?app=album&controller=api&action=uploadWithMap&pid=' . $pid . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
			}
			else{
				$api = mk_url('album/api/uploadWithMap',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'pid'=>$pid));
			}			
    	}
    	
        $flag = call_curl($api);		
		$_SESSION['avatartotl'] = json_decode($flag,true);
		
        if ($flag == 's')
        {
           	return true;
        }
		return false;

    }
    
    /**
     * 图片处理
     */
    public function proPic($filePath,$callback = 'updataCover',$isPost = true,$pid = null)
    {
        $web_path = config_item('avatar_webroot')  . $this->mduid . '_thumb.jpg?v=' . time();
        $cover_path = 'http://' . config_item('fastdfs_domain') . get_cache('cover_'.$this->uid);  //原cover地址
        $path = array($web_path,$cover_path);
        if (empty($filePath))
        {
            return false;
        }
        list ($width, $height) = getimagesize($filePath);
        if($width < 850 || $height < 315){
        	if($isPost){
        		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';        		
        		die("<script>parent.". $callback ."({'status':0,'data':'novia','msg':'上传失败！上传的格式不正确，上传的照片宽度最小为851px，高度最小为315px 。图片最大尺寸为4M，端口网支持：jpg,jpeg,gif,png格式图片'});</script>"); 
        		
        	}
        	else{
        		 die(json_encode(array('status'=>0,'data'=>'novia','msg'=>'上传失败！上传的格式不正确，上传的照片宽度最小为851px，高度最小为315px 。图片最大尺寸为4M，端口网支持：jpg,jpeg,gif,png格式图片')));       		
        	}       	
        }
        else{        	
			$nw = 851;
			$nh = intval(($nw/$width)*$height);				
			$config = array(
					'image_library'=>'GD2',
					'source_image'=>$filePath,
					'width'=>$nw,					
					'height'=>$nh,
					'new_image'=>$this->mduid . '_thumb.jpg'		
			);
			$this->load->library('image_lib'); 
			$this->image_lib->initialize($config); 		
			if ( ! $this->image_lib->resize())
			{
    			echo  $this->image_lib->display_errors('','');
    			exit;
			}
			else{
				if ($isPost)
            	{
            		$this->save_album();//暂时不做判断
                	$json = json_encode(array('status' => 1,'data'=>$path,'msg' => $nh)); 					
                	die("<script>parent.". $callback ."(" . $json . ");</script>");
            	}
            	else
            	{
            		$this->save_album($pid);//暂时不做判断
                	die(json_encode(array('status' => 1,'data'=>$path,'msg' => $nh)));
            	}
			}
        	        	
        }        
    }

    /**
     *保存摄像头
     */
    public function avatar_camera_save()
    {
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");        
           
        $pic_path = config_item('avatar_webroot') . $this->mduid . "_b.jpg";
        
        $file_addr = config_item('avatar_root');
        include_once(EXTEND_PATH.'vendor/File_util.php');
		$this->file_util = new File_util();
        if (! file_exists($file_addr))
        {
            $this->file_util->createDir($file_addr);
        }
        $pic_abs_path = $file_addr . substr($pic_path, strrpos($pic_path, '/'));        
        if(!file_put_contents($pic_abs_path, file_get_contents("php://input")))
        {
			$d = new pic_data();
			$d->data->urls[0] = $pic_path;
			$d->status = 0;
			$d->statusText = '拍摄失败';
			die(json_encode($d));
		}
        $avtar_img = imagecreatefromjpeg($pic_abs_path);		
        imagejpeg($avtar_img, $pic_abs_path, 100); 		
        $this->save_album('',true);      
        $d = new pic_data();
        $d->data->urls[0] = $pic_path;
        $d->status = 1;
        $d->statusText = '上传成功!';
        die(json_encode($d));
    }
    
	/**
	 * 处理头像上传图片的最大高度和宽度  默认是w:2800 || h:2800
	 * @author lvxinxin
	 * @date 2012-04-19
	 */
	function pro_avatar($avatar_path,$uid,$w,$h){		
		if(empty($uid)){
			return false;
		}
		$wavg = sprintf('%.2f',2800/$w);
		$havg = sprintf('%.2f',2800/$h);
		if($wavg < $havg){
			$navg = $wavg;
		}
		else {
			$navg = $havg;
		}
		$nw = intval($w * $navg);
		$nh = intval($h * $navg);		
		$config = array(
					'image_library'=>'GD2',
					'source_image'=>$avatar_path,
					'width'=>$nw,
					'height'=>$nh		
		);
		$this->load->library('image_lib'); 
		$this->image_lib->initialize($config); 		
		if ( ! $this->image_lib->resize())
		{
    		echo  $this->image_lib->display_errors('','');
		}
		else{
			return true;
		}
	}
	
	public function _getMasterFile(){
		$fast_file = $this->redisdb->get($this->uid);
		$f_info = parse_url($fast_file);
		if(empty($f_info['path'])) return false;
		return preg_replace('/\/[A-Za-z0-9]*\//is','',$f_info['path'],1);
	}
	
	public function _saveFile($file,$size){			
		if(empty($file) || empty($size)) return false;
		$fpath = realpath($file);		
		return $f = $this->fdfs->uploadSlaveFile($fpath,$this->_getMasterFile(),$size,'jpg');	
	}
	
	public function _delFile($size = null){		
		if(empty($size)){
			$this->fdfs->deleteFile('',$this->_getMasterFile());
			$this->redisdb->delete($this->uid);
			return true;
		}
		$fname = rtrim($this->_getMasterFile(),'.jpg').$size;
		$f = $this->fdfs->deleteFile('',$fname);
		if($f){
			$furl = MISC_ROOT . 'img/default/avatar_' . $size . '.gif';			
			return true;
		}
		else{
			return false;
		}
	}
}

/**
 * 仅供头像上传的类
 *
 * @author
 * @date
 * @version 1.0
 * @description 头像上传相关数据
 * @history <author><time><version><desc>
 */
class pic_data
{
    public $data;
    public $status;
    public $statusText;

    public function __construct()
    {
        $this->data->urls = array();
    }
}
?>