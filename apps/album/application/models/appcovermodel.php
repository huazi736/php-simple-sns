<?php
/**
 * 相册照片model
 *
 * @author        weijian
 * @version       $Id: albummodel.php 26102 2012-05-25 10:44:54Z guzb $
 */
class AppCoverModel extends MY_Model
{
	private $fastdfs;
	
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 应用区封面设置
     * @author yangshunjun
     * 
     * @param $group           fast组
     * @param $filename        fast文件名
     * @param $userid          用户uid
     * @param $menuid          应用区菜单id
     * @param $type            封面设置类型, 1:多张合成,2:单张图片合成
     */
	public function mergeImages($group = '', $filename = '', $userid = 0, $menuid = 0, $type = 2){
		
		if(!$userid || !$menuid)
			return 'f001';
		if(($group =='' && $filename != '') || ($group != '' && $filename == '')){
			return 'f007';
		}
		$type = $type ? $type : 2;
		
		//生成图片存放路径
        $this->config->load('album');
        $tmp_storage_path = $this->config->item('storage_path');
        $camera = rtrim($tmp_storage_path, "/") . "/appcovertmp_" . date("YmdHis") . mt_rand(1000, 9999). ".jpg";

        $file_addr = $tmp_storage_path;
		
        //Fastdfs
        $this->load->fastdfs('album','', 'fdfs');
        
		if($group == '' && $filename == ''){
			//if($menu_param[0] != 'img'){
				//$this->fdfs->deleteFile($app_cover['group'], $app_cover['menu_ico']);
			//}
			return service('User')->setAppMenuCover($userid, $menuid, '');
		}
		
		//设置新的封面
		$file[] = $this->fdfs->get_file_url($filename, $group);
		
		//图片定位
		$files_info = $this->resetPosition($file, $x_size = 30, $y_size = 30, $rows_num = 2, $colum_num = 3);
		
		$width_img = 30;
		$height_img = 30;
			
		if($type == 2){
			$width_img = 90;
			$height_img = 60;
		}
		
		//合成图片
		$file_new = $this->imgMerge($type, $files_info, $x_size = 90, $y_size = 60, $x_size_img = 0, $y_size_img = 0, $width_img, $height_img, $camera, $file_addr);
		//文件后缀名
		$file_suffux = end(explode('.', $file_new));
		
		//FastDFS上传
		if(file_exists($file_new)){
			$fastdfs_file = $this->fdfs->uploadFile($file_new);
		} else {
			return 'f008';
		}
		
		//删除临时图片
		if($fastdfs_file){
			@unlink($camera);
		}
		
		//设置应用区封面
		$result = service('User')->setAppMenuCover($userid, $menuid, $fastdfs_file['filename'], $fastdfs_file['group_name']);

		if($result === true){
			return true;
		}
		
		return '003';
        
	}
	
	
/**
	 * 
	 * 图片合成 
	 * 
	 * @author yangshunjun
	 * 
	 * @param integer $type 图片合成类型:1:合成;2:不合成 
	 * @param array $files
	 * @param integer $x_size
	 * @param integer $y_size
	 * @param integer $x_size_img
	 * @param integer $y_size_img
	 * @param integer $width_img
	 * @param integer $height_img
	 * @param string $saveFileName
	 * @param string $savePath
	 * 
	 * @return
	 */
	public function imgMerge($type = 1, $files = array(), $x_size = 90, $y_size = 60, $x_size_img = 0, $y_size_img = 0, $width_img = 0, $height_img = 0, $saveFileName = '', $savePath){
		//创建一张空白图像
//		header ("Content-type: image/png");
		$img_main = $this->crteaeImage($x_size, $y_size);
		
		//合并图像
		if(is_array($files)){
			foreach($files AS $key => $val){
				if(!$val['src'])
					break;
				//保存网络图片流
				$file = $this->saveFiles($val['src'], $savePath);
				if(!file_exists($file)){
					return '007' . $file;
				}
				//缩放图片
				$file_thumb = $this->zoomImage($file, $width_img, $height_img, $savePath);
				if(!file_exists($file_thumb)){
					return '006' . $file_thumb;
				}
				//加载图片
				$func_str = $this->getImageTypeAc($file_thumb, 'create');
				$file_source = @$func_str($file_thumb);
				
				//合成
				@imagecopymerge($img_main, $file_source, $val['x'], $val['y'], $x_size_img, $y_size_img, $width_img, $height_img, 100);
				
				//删除临时图片
				if(file_exists($file)) @unlink($file);
				if(file_exists($file_thumb)) @unlink($file_thumb);
			}
		}
		
		@imagepng($img_main, $saveFileName);
		@imagedestroy($img_main);
		
		return $saveFileName;		
	}
	
	/**
	 * 创建一张空白图像
	 * 
	 * @author yangshunjun
	 * 
	 * @param integer $x_size 图片宽度
	 * @param integer $y_size 图片高度
	 * 
	 * @return 
	 */
	public function crteaeImage($x_size, $y_size){
		//创建白色背景空白图片
		$dst_img = @imagecreatetruecolor($x_size, $y_size);
		$white = @imagecolorallocate($dst_img, 255, 255, 255);
		@imagefill($dst_img, 0, 0, $white);
		
		return $dst_img;
	}
	
	/**
	 * 计算每一张图片的位置
	 * 
	 * @author yangshunjun
	 * 
	 * @param array $files 图片数据
	 * @param integer $x_size 每张图宽度
	 * @param integer $Y_size 每张图高度
	 * @param integer $rows_num 每行图片个数
	 * @param integer $colum_num 第列图片个数
	 * 
	 */
	public function resetPosition($files = array(), $x_size = 0, $y_size = 0, $rows_num = 1, $colum_num = 1){
		if(!is_array($files))
			return false;
		$i = 1;
		$r = 1;
		$file = array();
		
		foreach($files AS $key => $val){
			$file[$key]['src'] = $val;
			$file[$key]['x'] = ($i-1) * $x_size;
			$file[$key]['y'] = $x_size * ($r - 1);
			
			//换行
			if($i % $colum_num == 0){
				$r++;
				$i = 0;
			}
			$i++;
		}
		
		return $file;
	}
	
	/**
	 * 缩放图片
	 * 
	 * @author yangshunjun
	 */
	public function zoomImage($file, $x_size, $y_size, $savePath){
		
		if(!$file || !is_numeric($x_size)  || !is_numeric($y_size)) 
			return false;
		list($width, $height) = getimagesize($file);
		
		//目标图像填充起始坐标
		$thumb_x = 0;
		$thumb_y = 0;
		
		//按比例缩小图片, 确保图片不小于90*60
		$ratio_w = $x_size/$width;
		$ratio_h = $y_size/$height;
		
		//缩小到相对较大的图片
		$scale = ($ratio_w > $ratio_h) ? $ratio_w : $ratio_h;

		$resize_width	= ceil($width * $scale);
		$resize_height	= ceil($height * $scale);
		
		//如果图片小于90*60则对于小的不进行缩略
		if($width < $x_size){
			$ratio_w = $resize_width = $width;
			$thumb_x = ceil(($x_size - $width) / 2);
		}
		if($height < $y_size){
			$ratio_h = $resize_height = $height;
			$thumb_y = ceil(($y_size - $height) / 2);
		}
		
		$thumb_temp = $this->crteaeImage($resize_width, $resize_height);
		
		$file_name = end(explode('/', $file));
		
		$file_name_new = $savePath . '/thumb_' . $file_name;
		
		//获取图像类型采用不同类型图片创建
		$func_img_create = $this->getImageTypeAc($file, 'create');
		$file_source = @ $func_img_create($file);
		
		if(function_exists("imagecopyresampled")){
			imagecopyresampled($thumb_temp, $file_source, 0, 0, 0, 0, $resize_width, $resize_height, $width, $height);
		} else {
			imagecopyresized($thumb_temp, $file_source, 0, 0, 0, 0, $resize_width, $resize_height, $width, $height);
		}
		$thumb_position = $this->get_thumb_position($resize_width, $resize_height, $x_size, $y_size);
		
		//获取图像类型采用不同类型图片创建
		$thumb = $this->crteaeImage($x_size, $y_size);
		if(function_exists("imagecopyresampled")){
			imagecopyresampled($thumb, $thumb_temp, $thumb_x, $thumb_y, $thumb_position['x'], $thumb_position['y'], $thumb_position['w'], $thumb_position['h'], $thumb_position['w'], $thumb_position['h']);
		} else {
			imagecopyresized($thumb, $thumb_temp, $thumb_x, $thumb_y, $thumb_position['x'], $thumb_position['y'], $thumb_position['w'], $thumb_position['h'], $thumb_position['w'], $thumb_position['h']);
		}
		
		$func_img_save = $this->getImageTypeAc($file);
		$func_img_save($thumb, $file_name_new);
		
		@imagedestroy($thumb_temp);
		@imagedestroy($thumb);
		return $file_name_new;
	}
	/**
	 * 保存网络图片
	 * 
	 * @author yangshunjun
	 */
	public function saveFiles($file = '', $savePath = ''){
		if (!$file || !$savePath) return 'f004';
		
		$this->load->fastdfs('album','', 'fdfs');
		//得到图片名称及类型
		$time = time();
		$file = urldecode($file);
		$file_pathinfo = pathinfo($file);
		$file_url_info = parse_url($file);
		
		$file_name = $file_pathinfo['basename'];
//		$file_type = $this->getImageType($file);
		//获取组
		$file_parse = explode('/', $file_url_info['path']);
		$group = $file_parse[1];
		//删除组名,组织filename
		unset($file_parse[1]);
		$filename = substr(implode('/', $file_parse),1);
		
		if(!is_dir($savePath))
			mkdir($savePath);
		
		$file_name_new = $savePath . '/' . $time . $file_name;
		//fastdfs下载
		
		$file_name_new =  substr($file_name_new, 0, strpos($file_name_new, '?'));
		$file_source = $this->fdfs->downloadFile($filename, $file_name_new, $group);
		if(!$file_source){
			return 'f005';
		}
		return $file_name_new;
	}
	
	/**
	 * 
	 * 获取图片类型
	 * 
	 * @param $file
	 * @param $ac
	 */
	public function getImageTypeAc($file, $ac = 'save'){
		$size = getimagesize($file);
		switch ($size['mime']){
			case 'image/bmp':
				if($ac == 'save'){
					$func = 'imagewbmp';
				}else {
					$func = 'imagecreatefromwbmp';
				}
				break;
			case 'image/gif':
				if($ac == 'save'){
					$func = 'imagegif';
				}else {
					$func = 'imagecreatefromgif';
				}
				break;
			case 'image/jpeg':
				if($ac == 'save'){
					$func = 'imagejpeg';
				}else {
					$func = 'imagecreatefromjpeg';
				}
				break;
			case 'image/png':
				if($ac == 'save'){
					$func = 'imagepng';
				}else {
					$func = 'imagecreatefrompng';
				}
				break;
			default:
				if($ac == 'save'){
					$func = 'imagejpeg';
				}else {
					$func = 'imagecreatefromjpeg';
				}
				break;
		}
		return $func;
	}
	
	/**
	 * 
	 * 获取图片类型
	 * 
	 * @param $file
	 * @param $ac
	 */
	public function getImageType($file){
		
		return $size = getimagesize($file);
		switch ($size['mime']){
			case 'image/bmp':
				$type = 'bmp';
				break;
			case 'image/gif':
				$type = 'gif';
				break;
			case 'image/jpeg':
				$type = 'jpg';
				break;
			case 'image/png':
				$type = 'png';
				break;
			default:
				$type = 'jpg';
				break;
		}
		return $type;
	}
	/**
	 * 计算图片中心位置,以图片中心截取图片缩略图
	 * 
	 * @author yangshunjun
	 * 
	 * @param integer $x 原图宽度
	 * @param integer $y 原图高度
	 * @param integer $w 缩略图宽度
	 * @param integer $h 缩略图高度
	 */
	function get_thumb_position($x, $y, $w, $h) {
		if(!$x || !$y){
			return array('x' => 0, 'y' => 0, 'w' => $x, 'h' => $y);
		}
		
		$x_size = intval(($x - $w) / 2);
		$y_size = intval(($y - $h) / 2);
		$w_size = $x_size + $w;
		$h_size = $y_size + $h;
		
		if($x < $w){
			$x_size = 0;
			$w_size = $x;
		}
		if($x > $w){
			$x_size = 0;
			$w_size = $w;
		}
		
		if($y < $h){
			$y_size = 0;
			$h_size = $y;
		}
		if($y > $h){
			$y_size = 0;
			$h_size = $h;
		}
		
		return array('x' => $x_size, 'y' => $y_size, 'w' => $w_size, 'h' => $h_size);
	}
}

/* End of file appcovermodel.php */
/* Location: ./app/album/application/albummodels/appcovermodel.php */