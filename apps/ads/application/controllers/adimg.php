<?php

/**
 * 上传广告图片
 * @author qianc
 * @date <2012/06/19>
 *
 */
class adimg extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();			
    }



    
    
	/**
	 * @author: qianc
	 * @desc: 上传图片至fdsf处理
	 * @access protected
	 * @return json
	 */	
	public function adimg_upload(){
		//是否登陆
	    if (! $this->uid){
        	$return = array( 'status' => 0, 'message' => 'session失效!'); 
        	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            echo '<script type="text/javascript">window.parent.retfunc('.(json_encode($return)).')</script>';
            exit();
        }
        //类型，大小，宽高限制

        $fType_allow = $this->system_config['pic_format'] ? explode('|',$this->system_config['pic_format']) :  array("gif","jpg","jpeg");

		//$fType_allow = array("image/gif","image/jpg","image/jpeg");
		$fSize_allow = $this->system_config['pic_max_size'] * 1024 * 1024; //图片大小  MB
        $fwidth_allow = $this->system_config['maxwidth']; //图片宽度
        $fheight_allow = $this->system_config['maxheight'];		//图片高度 
        
        if(! in_array(substr($_FILES['Filedata']['type'],6), $fType_allow)){
			$return = array( 'status' => 0, 'message' => '对不起，请上传'.$this->system_config['pic_format'].'格式的图片');
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'; 			
			echo '<script type="text/javascript">window.parent.retfunc(' . (json_encode($return)) . ');</script>';
            exit;        	
        }
        if($_FILES['Filedata']['size'] > $fSize_allow){
			$return = array( 'status' => 0, 'message' => '对不起，文件大小不能超过'.$this->system_config['pic_max_size'].'MB');
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'; 			
			echo '<script type="text/javascript">window.parent.retfunc(' . (json_encode($return)) . ');</script>';
            exit;         	
        }	
        $imgInfo_local = getimagesize($_FILES['Filedata']['tmp_name']);
	    if($imgInfo_local[0] != $fwidth_allow || $imgInfo_local[1] != $fheight_allow){
			$return = array( 'status' => 0, 'message' => '对不起，只能上传'.$fwidth_allow.'*'.$fheight_allow.'像素的图片');
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'; 			
			echo '<script type="text/javascript">window.parent.retfunc(' . (json_encode($return)) . ');</script>';
            exit;         	
        }       

        
		//$this->load->fastdfs('ads','', 'fdfs');
		$this->fdfs = get_storage('ads');
		
		//多次浏览,刚删除老文件
		$fdfs_old = $this->input->get('fdfs');
		if($fdfs_old){
			$fdfs_old_arr = explode('/', $fdfs_old);
			$this->fdfs->deleteFile($fdfs_old_arr[3], $fdfs_old_arr[4].'/'.$fdfs_old_arr[5].'/'.$fdfs_old_arr[6].'/'.$fdfs_old_arr[7]);			
		}
					
        $Mfdata = $this->fdfs->uploadFile($_FILES['Filedata']['tmp_name'],'jpg');        		
		//成功返回数组，失败返回错误信息
		if($Mfdata && is_array($Mfdata)){
			//$setting = getConfig('fastdfs','ads');	
			//$Mf_img = 'http://'.$setting['host'] . '/'.$Mfdata['group_name'].'/'.$Mfdata['filename'];
			
			$Mf_img = $this->fdfs->get_file_url($Mfdata['filename'], $Mfdata['group_name']); 
			$return = array(
				'status'=>1,
				'message'=>'上传成功',
        		'data'=>array('Mf_img'=>$Mf_img)
			);			
	        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';                  
	        echo '<script>window.parent.retfunc('.(json_encode($return)).')</script>';
		    exit;			
			
		}else{
			$return = array( 'status' => 0, 'message' => $Mfdata);			
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'; 			
			echo '<script type="text/javascript">window.parent.retfunc(' . (json_encode($return)) . ');</script>';
            exit; 
		}
	}    
}
?>