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
		if(!$this->is_self)  show_404();
		if(!$this->web_id){
			$this->web_id	= intval($this->input->get_post('web_id'));
		}
		
		
    }

    function index()
    {		
        $this->set_avatar();
    }
	public function avatar_init(){
		$web_id = addslashes($this->input->get('aid'));
		$this->assign('webid',$web_id);
		$this->assign('searchfriend',mk_url('webmain/create/add_fellow',array('aid'=>$web_id)));
		$this->assign('avatar_upload',mk_url('webmain/avatar/avatar_upload',array('web_id'=>$web_id)));
		$this->assign('avatar_pic',mk_url('webmain/avatar/avatar_pic',array('web_id'=>$web_id)));
		$this->assign('avatar_photo',mk_url('webmain/avatar/avatar_photo',array('web_id'=>$web_id)));
		$this->assign('avatar',get_webavatar($web_id,'b'));
		$this->display('avatar.html');
	}
	public function avatar_pic(){
		$web_id = addslashes($this->input->get('web_id'));
		$this->assign('webid',$web_id);	
		$this->assign('photo',mk_url('webmain/avatar/avatar_photo',array('web_id'=>$web_id)));
		$this->assign('action',mk_url('webmain/avatar/avatar_upload',array('web_id'=>$web_id)));
		$this->assign('searchfriend',mk_url('webmain/create/add_fellow',array('aid'=>$web_id)));
		$this->display('avatar_pic.html');
	}
	public function avatar_photo(){
		$web_id = addslashes($this->input->get('web_id'));
		$this->assign('webid',$web_id);	
		$this->assign('searchfriend',mk_url('webmain/create/add_fellow',array('aid'=>$web_id)));		
		$this->assign('avatar_pic',mk_url('webmain/avatar/avatar_pic',array('web_id'=>$web_id)));
		$this->display('photograph.html');
	}
	public function _show(){
		$web_id = addslashes($this->input->get('web_id'));
		$this->assign('webid',$web_id);
        $this->assign('username', $this->web_info['name']);
        $this->assign('avatar50', get_webavatar($web_id,'s'));
        $this->assign('avatar_upload', mk_url('webmain/avatar/avatar_upload'));
        $this->assign('url',mk_url('webmain/avatar/set_avatar',array('web_id'=>$web_id)));
        $this->assign('nameurl',mk_url('webmain/index/main',array('web_id'=>$web_id)));
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
            $upPath = config_item('avatar_root') . '/';
            $avatar = $upPath . $this->mduid . '_'. $this->web_id .'.jpg';
            if (file_exists($avatar))
            {
                @unlink($avatar);
            }
            $a = @file_put_contents($avatar, @file_get_contents($path));
            if ($a > 0)
            {
                $web_path = config_item('avatar_webroot')  . $this->mduid . '_' . $this->web_id .'.jpg?v='.time();
				if(WEB_ROOT == 'http://www.duankou.com/'){
					$api = 'http://127.0.0.1/index.php?app=walbum&controller=api&action=uploadHead&web_id=' . $this->web_id . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid . '&pid=' . $pid;
				}
				else{
					$api = mk_url('walbum/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'web_id'=>$this->web_id,'pid'=>$pid));
				}
                // $api = mk_url('walbum/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'web_id'=>$this->web_id,'pid'=>$pid));
				// $api = WEB_ROOT . 'index.php?app=walbum&controller=api&action=uploadHead&web_id=' . $this->web_id . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid . '&pid=' . $pid;
				call_curl($api); 
				             
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
                echo '<script type="text/javascript">window.parent.hideLoading();window.parent.buildAvatarEditor("' . $this->mduid . '_' . $this->web_id . '","' . $web_path . '?v=' . time() .
                 '","photo");</script>';
            }
            else
            {
                echo '<script type="text/javascript">$.alert("保存图片失败");</script>';
                exit();
            }
             //exit;
        }
	}
	
	
	
	
	
    /**
     * 通过本地上传方式更新头像
     *
     * 操作相册、头像
     * @author mawenpei
     * @date   2011-10-25
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
		// die($callback);
		// echo $callback;
		// $type = $this->input->post('type');		
        if (! empty($from) && ! empty($pic))
        {			
            $upPath = config_item('avatar_root') . '/'; 
            $avatar = $upPath . $this->mduid . '_' . $this->web_id . '_f.jpg';
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
            else
            {
				echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
				die("parent.". $callback ."({'status':0,'data':'novia','msg':'相册图片生成失败'});");
                
            }
        }
        $upload_config['upload_path'] = config_item('avatar_root') .'/';  //上传路径
        $upload_config['allowed_types'] = 'jpg|jpeg|gif|png'; //文件上传类型
        $upload_config['overwrite'] = true; //同名文件覆盖
        $upload_config['file_name'] = $this->mduid . '_' . $this->web_id . '_f.jpg'; //指定文件名
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
            $filePath = $upload_config['upload_path'] . $this->mduid  .'_'. $this->web_id . '_f.jpg';			
            $this->proPic($filePath,$callback);			
        }
        else
        {			
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            die("parent.". $callback ."({'status':0,'data':'novia','msg':'" . $this->myupload->display_errors('', '') . "'});");
        }
         
    }

    /**
     * 设置封面
     */
    public function set_cover()
    {        
        $height = abs($this->input->post('top'));       
		if(empty($this->web_id)){
			die(json_encode(array('status'=>0,'msg'=>'网页ID获取失败')));
		}
        $this->load->library('image_lib');
        $config['image_library'] = 'gd2';
        $config['source_image'] = config_item('avatar_root') . '/'  . $this->mduid . '_' .$this->web_id . '_thumb.jpg';
        $config['new_image'] = $this->mduid . '_' .$this->web_id . '_cover.jpg';
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
			$fpath = realpath( config_item('avatar_root') . '/'  . $this->mduid . '_' . $this->web_id .'_cover.jpg');
			if($fpath != false){
				$Mfdata = $this->fdfs->uploadFile($fpath,'jpg');				
				if(is_array($Mfdata)){
					$mf = preg_replace('/\/[A-Za-z0-9]*\//is','',$this->web_info['webcover'],1);					
					$path = '/' . $Mfdata['group_name'] . '/' . $Mfdata['filename'];
					$this->load->model('avatarmodel');
					$res = $this->avatarmodel->save_cover($this->web_id,$path);					
					if($res){
						set_cache('webcover_'.$this->web_id,$path);
						$this->fdfs->deleteFile('',$mf);//删除原图片						
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
        $filename = $this->mduid . '_' . $this->web_id;               
        $upload_config['upload_path'] = config_item('avatar_root') . '/'; //上传路径
        $upload_config['allowed_types'] = 'jpg|jpeg|gif|png|pjpeg|x-png'; //文件上传类型
        $upload_config['overwrite'] = true; //同名文件覆盖
        $upload_config['file_name'] = $filename.'.jpg'; //指定文件名
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
				$s = $this->pro_avatar($upload_config['upload_path'].$filename.'.jpg',$img_info['image_width'], $img_info['image_height']);
				if(!$s){
					$this->_show();
					echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
					echo '<script type="text/javascript">$.alert("' . $this->myupload->display_errors('','') . '");</script>';
            		exit;
				}				
			}
            $web_path = config_item('avatar_webroot')  . $filename . '.jpg?v=' . time(); 
			if(WEB_ROOT == 'http://www.duankou.com/'){
				$api = 'http://127.0.0.1/index.php?app=walbum&controller=api&action=uploadHead&web_id=' . $this->web_id . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
			}
			else{
				$api = mk_url('walbum/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'web_id'=>$this->web_id));
			}
			// $api = mk_url('walbum/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'web_id'=>$this->web_id));
            // $api = WEB_ROOT . 'index.php?app=walbum&controller=api&action=uploadHead&web_id=' . $this->web_id . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
			$flag = call_curl($api);
			// $ctx = stream_context_create(array('http'=>array('timeout'=>3)));
			// file_get_contents($api,0,$ctx);
            echo '<script type="text/javascript">window.parent.hideLoading();window.parent.buildAvatarEditor("' . $filename . '","' . $web_path .
         	'","photo");</script>';        
        	exit();
        }
        else
        {
        	$this->_show();
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';            
            echo '<script type="text/javascript">document.domain="' . config_item('cover_domain') .'";window.parent.$.alert("' . $this->myupload->display_errors('',''). '");</script>';
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
		include_once(EXTEND_PATH.'vendor/File_util.php'); 
		$pic_size = $this->input->get('type');
        $type = isset($pic_size) ? $pic_size : 'big';		
        if ($type == 'big')
        {
            $type = 'b';            
        }               
        $pic_path = config_item('avatar_webroot') . $this->mduid . "_" . $this->web_id . '_' . $type . ".jpg";        
        $file_addr = config_item('avatar_root'); 
        if (! file_exists($file_addr))
        {
            $this->file_util->createDir($file_addr);
        }        
        $pic_abs_path = $file_addr . substr($pic_path, strrpos($pic_path, '/'));        
        if(!file_put_contents($pic_abs_path, file_get_contents("php://input"))){
			$d = new pic_data();
			$d->data->urls[0] = $pic_path;
			$d->status = 0;
			$d->statusText = '保存失败!';
			die(json_encode($d));
		}        
        $avtar_img = imagecreatefromjpeg($pic_abs_path);
        imagejpeg($avtar_img, $pic_abs_path, 80);        
        
		$fpath = realpath($pic_abs_path);
		if($fpath != false){			
			$flag = $this->redisdb->get($this->web_id);
			$setting = getConfig('fastdfs','avatar');
			if(empty($flag)){
				$Mfdata = $this->fdfs->uploadFile($fpath,'jpg');
				$fast_path = 'http://' . $setting['host'] . '/' . $setting['group'] . '/' .$Mfdata['filename'];
				$this->redisdb->set($this->web_id,$fast_path);
			}
			else{
				$this->_delete_fastdfs_file();
				$Mfdata = $this->fdfs->uploadFile($fpath,'jpg');
				$fast_path = 'http://' . $setting['host'] . '/' . $setting['group'] . '/' .$Mfdata['filename'];
				$this->redisdb->set($this->web_id,$fast_path);
			}
		}	
		$this->create_avatar($this->mduid.'_'.$this->web_id, $file_addr, $avtar_img);		
		$this->_delete_local_avatar($file_addr);		
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
		$s99_res = imagecreatetruecolor(99, 99);//--add
        $s100_res = imagecreatetruecolor(100, 100);
        imagecopyresampled($s30_res, $res, 0, 0, 0, 0, 30, 30, 125, 125);
        imagecopyresampled($s50_res, $res, 0, 0, 0, 0, 50, 50, 125, 125);
		imagecopyresampled($s99_res, $res, 0, 0, 0, 0, 99, 99, 125, 125);//--add
        imagejpeg($s30_res, $file_addr . '/' . $uid . '_ss.jpg', 80);
        imagejpeg($s50_res, $file_addr . '/' . $uid . '_s.jpg', 80);        
        imagejpeg($s99_res, $file_addr . '/' . $uid . '_mm.jpg', 100);//--add
        imagecopyresampled($s100_res, $res, 0, 0, 0, 0, 100, 100, 125, 125);        
        imagejpeg($s100_res, $file_addr . '/' . $uid . '_m.jpg', 100);
       
        imagedestroy($s30_res);
        imagedestroy($s50_res);
        imagedestroy($s100_res);
        imagedestroy($s99_res);//--add
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
        $avatar_ss = $file_addr . '/'  . $this->mduid . '_' . $this->web_id . '_ss.jpg';
        $avatar_s = $file_addr . '/' . $this->mduid . '_' . $this->web_id . '_s.jpg';
        $avatar_m = $file_addr . '/' . $this->mduid . '_' . $this->web_id . '_m.jpg';
		$avatar_mm = $file_addr . '/' . $this->mduid . '_' . $this->web_id . '_mm.jpg'; //--add
        $avatar_b = $file_addr . '/' . $this->mduid . '_' . $this->web_id . '_b.jpg';
		$avatar = $file_addr . '/' .  $this->mduid . '_' . $this->web_id . '.jpg';
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
        $this->_delFile('_ss.jpg');
		$this->_delFile('_s.jpg');
		$this->_delFile('_m.jpg');
		$this->_delFile('_mm.jpg'); //--add
        $this->_delFile('_b.jpg');
		$this->_delFile();
		
        exit(json_encode(array('status' => 1,'data'=>MISC_ROOT.'img/default/web_b.gif')));
    }

    /**
     * 删除封面
     */
    public function delete_cover()
    {
    	$avatar_pic = config_item('avatar_root');
		$avatar_f = $avatar_pic . $this->mduid . '_' . $this->web_id . '_f.jpg';
        $avatar_thumb = $avatar_pic . $this->mduid . '_' . $this->web_id . '_thumb.jpg';
        $avatar_cover = $avatar_pic . $this->mduid . '_' . $this->web_id . '_cover.jpg';
		@unlink($avatar_f);@unlink($avatar_thumb);@unlink($avatar_thumb);
		$mf = preg_replace('/\/[A-Za-z0-9]*\//is','',$this->web_info['webcover'],1);					
					
		$this->load->model('avatarmodel');
		$res = $this->avatarmodel->del_cover($this->web_id);
		if($res){
			$this->fdfs->deleteFile('',$mf);
			set_cache('webcover_'.$this->web_id,null);
			exit(json_encode(array('status' => 1)));
		}
		else{
			exit(json_encode(array('status' => 0,'msg'=>'delete faild')));
		}
        
        
        
    }
	
    /**
     * 保存图片至相册
     */
	 public function save_album($pid = '',$avatar  = false){    	
    	if($avatar){
    		$web_path = config_item('avatar_webroot') . $this->mduid . '_' . $this->web_id . '_b.jpg?v=' . time();
			if(WEB_ROOT == 'http://www.duankou.com/'){
				$api = 'http://127.0.0.1/index.php?app=walbum&controller=api&action=uploadHead&web_id=' . $this->web_id . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
			}
			else{
				$api = mk_url('walbum/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'web_id'=>$this->web_id));
			}
			// $api = mk_url('walbum/api/uploadHead',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'web_id'=>$this->web_id));
			// $api = WEB_ROOT . 'index.php?app=walbum&controller=api&action=uploadHead&web_id=' . $this->web_id . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid;
		}
    	else{
    		$web_path = config_item('avatar_webroot') . $this->mduid . '_' . $this->web_id . '_f.jpg?v=' . time();
			if(WEB_ROOT == 'http://www.duankou.com/'){
				$api = 'http://127.0.0.1/index.php?app=walbum&controller=api&action=uploadWithMap&web_id=' . $this->web_id . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid . '&pid=' . $pid;
			}
			else{
				$api = mk_url('walbum/api/uploadWithMap',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'web_id'=>$this->web_id,'pid'=>$pid));
			}
			// $api = mk_url('walbum/api/uploadWithMap',array('filePath'=>urlencode($web_path),'flashUploadUid'=>$this->uid,'web_id'=>$this->web_id,'pid'=>$pid));
			// $api = WEB_ROOT . 'index.php?app=walbum&controller=api&action=uploadWithMap&web_id=' . $this->web_id . '&filePath=' . urlencode($web_path) . '&flashUploadUid=' . $this->uid . '&pid=' . $pid;
		}   	
        
        call_curl($api);	
		// $ctx = stream_context_create(array('http'=>array('timeout'=>3)));
		// file_get_contents($api,0,$ctx);
        // if ($flag == 's')
        // {
           	// return true;
        // }
		// return false;

    }
    
    /**
     * 图片处理
     */
    public function proPic($filePath,$callback = 'updataCover',$isPost = true,$pid = null )
    {
        $web_path = config_item('avatar_webroot')  . $this->mduid . '_' . $this->web_id . '_thumb.jpg?v=' . time();
		$setting = getConfig('fastdfs','avatar');
        $cover_path = 'http://' . config_item('fastdfs_domain') . get_cache('webcover_'.$this->web_id);;
        $path = array($web_path,$cover_path);
        if (empty($filePath) || empty($this->web_id))
        {			
            return false;
        }
        list ($width, $height) = getimagesize($filePath);
        if($width < 851 || $height < 301){
        	if($isPost){				
        		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';        		
        		die("<script type='text/javascript'>document.domain='" . config_item('cover_domain') . "';parent.". $callback ."({'status':0,'data':'novia','msg':'上传失败！上传的格式不正确，上传的照片宽度最小为851px，高度最小为315px 。图片最大尺寸为4M，端口网支持：jpg,jpeg,gif,png格式图片'})</script>"); 
        		
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
					'new_image'=>$this->mduid . '_' . $this->web_id . '_thumb.jpg'		
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
                	$json = json_encode(array('status' => 1,'data'=>$path,'msg' => 'success'));					
                	die("<script>document.domain='" . config_item('cover_domain') ."';parent.". $callback ."(" . $json . ");</script>");
					// die("<script>parent.". $callback ."(" . $json . ");</script>");
            	}
            	else
            	{
            		$this->save_album($pid);//暂时不做判断
                	die(json_encode(array('status' => 1,'data'=>$path,'msg' => 'success')));
            	}
			}
        	        	
        }        
    }

    /**
     *保存摄像头
	 *保存相册  待实现
     */
    public function avatar_camera_save()
    {        
        @header("Expires: 0");
        @header("Cache-Control: private, post-check=0, pre-check=0, max-age=0", FALSE);
        @header("Pragma: no-cache");
        
        $pic_path = config_item('avatar_webroot') . $this->mduid . "_" . $this->web_id . '_b.jpg'; 		
        $file_addr = config_item('avatar_root');
		include_once(EXTEND_PATH.'vendor/File_util.php');
		$this->file_util = new File_util();
        if (! file_exists($file_addr))
        {
            $this->file_util->createDir($file_addr);
        }
        $pic_abs_path = $file_addr . substr($pic_path, strrpos($pic_path, '/'));
        
        if(!file_put_contents($pic_abs_path, file_get_contents("php://input"))){
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
	function pro_avatar($avatar_path,$w,$h){
		
		if(empty($this->uid)){
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
		$fast_file = $this->redisdb->get($this->web_id);
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
			$this->redisdb->delete($this->web_id);
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