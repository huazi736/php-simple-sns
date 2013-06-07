<?php
class BloguploadModel extends MY_Model
{
	static private $upload = null;
	public $error = null;
	private $dir = 'tmp/';
	
	public function __construct()
	{
		load_class('Upload');
        load_class('Image_lib');
        $this->fdfs = get_storage('default');
	}

	public function doUpload($file = null )
	{
		// echo $this->fdfs->test();
	}
	
	// 处理图片
	public function doUploadImg($upload_name = null, $file_name = null)
	{
		if(!$file_name or !$upload_name){
			return false;
		}
		$config = array();
		$config['upload_path'] = VAR_PATH .$this->dir;
		$config['allowed_types'] = 'gif|jpg|png|bmp|jpeg';
		$config['max_size'] = '4096';
		$config['file_name'] = $file_name;
		$upload = new DK_Upload($config);
		$upload->initialize($config);
		$upload->upload_path = $config['upload_path'];
		$res = $upload->do_upload($upload_name);
		if($res){
			return $upload->data();
		}else{
			$this->error = $upload->display_errors();
			return false;
		}
	}
	
	//add start 1.0(by jiangfangtao 2012/06/05)
	 /*
	  * @param int $img_width 图片的宽度
	  */
	//add start 1.0(by jiangfangtao)
	function doImage($file_name, $file_path = null, $img_width = 800)
	{
		$max_width = 800;    //默认图片最大宽度
		$min_width = 144;    //默认图片最小宽度
		$conf = array();
		$conf['image_library'] = 'gd2';
		$conf['source_image'] = $file_path ? $file_path : (VAR_PATH .$this->dir).$file_name;
		$conf['new_image'] = (VAR_PATH .$this->dir).$file_name;
		$conf['create_thumb'] = true;
		$conf['thumb_marker'] = '_b';
		$conf['maintain_ratio'] = true;
		$conf['master_dim'] = 'width';
		if($img_width > $max_width){
		  $conf['width'] = $max_width;
		}
		else{
		  $conf['width'] = $img_width;
		}
		
		// 生成450的图
        $this->image_lib = new CI_Image_lib($conf);
		$this->image_lib->initialize($conf);
		$res_b = $this->image_lib->resize();
		// 重置参数
		$this->image_lib->clear();
		// 生成缩略图
		$conf['thumb_marker'] = '_s';
		//add start 1.0(by jiangfangtao 2012/06/05)
		if($img_width < $min_width){
		  $conf['width'] = $img_width;
		}
		else{
		  $conf['width'] = $min_width;
		}
		//add end 1.0(by jiangfangtao)
		$this->image_lib->initialize($conf);
		$res_s = $this->image_lib->resize();
		if($res_b && $res_s) {
			return true;
		}
		return false;
	}
	
	
	/**
	 * 处理从相册里过来的图片;
	 */
	public function savePhoto($group = null, $file = null, $file_name = null)
	{
		if(!$group or !$file or !$file_name) {
			return false;
		}
		// getFile;
		$localfile = (VAR_PATH  . $this->dir) . $file_name;
		//$res = $this->fdfs->download_filename($group,$file,$localfile);
		$res = $this->fdfs->downloadFile($file,$localfile,$group);
		//var_dump($res);
		if($res) {
			if(function_exists('getimagesize')){
				$info = @getimagesize((VAR_PATH . $this->dir).$file_name);
				//add start 1.0(by jiangfangtao 2012/05/25)
				//判断图片宽度是否大于800，如果大于就取800，如果小于则取其宽度
				if($info[0]>800){
				 $max_width = 800;
				}
				else{
				 $max_width = $info[0];
				}
				//add end 1.0(by jiangfangtao)
			}
			else
			{
				$max_width = 800;
			}
			
			$this->doImage($file_name,$localfile,$max_width);
			return $res;
		}
		return false;
	}

	
}