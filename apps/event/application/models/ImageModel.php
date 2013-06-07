<?php
namespace Models;

/**
 * 图片处理
 *
 * 需要GD2
 *
 * @author weihua
 * @version n+1 2012-5-9
 */


class ImageModel
{
	protected $_result;
	protected $_info;
	/**
	 * 允许最大图片
	 */
    public function allowSize(){
    	return 4*1024*1024;
    }
    
	/**
	 * 得到图片信息
	 */
	static public function getInfo($filename)
	{
		static $cache = array();

		if (!isset($cache[$filename])) {
			$cache[$filename] = @getimagesize($filename);
		}

		return $cache[$filename];
	}

	/**
	 * 检查图片是否有效
	 */
	static public function isValid($filename)
	{
		$info = self::getInfo($filename);

		if (!$info) {
			return false;
		}

		//只允许gif,jpg,png
		if (in_array($info[2], array(1, 2, 3))) {
			return true;
		}

		return false;
	}

	public function __construct($filename)
	{
		$this->_info = self::getInfo($filename);

		$this->createFrom($filename, $this->_info['mime']);

	}

	public function __destruct()
	{
		imagedestroy($this->_result);
	}

	/**
	 * 等比例缩放
	 * (大图缩小,小图以填冲色撑大)
	 *
	 * @param string $filename 输出到文件
	 * @param int    $width    缩放宽度
	 * @param int    $height   缩放高度
	 * @param string $mime     输出类型(默认与源文件一至)
	 * @param string $color    补白顔色 如:'#2fee45'
	 */
	public function reSizeToFile($filename,$width, $height, $mime=null, $color=0)
	{
		if (!$mime) {
			$mime = $this->_info['mime'];
		}

		$result = $this->crop(0, 0, $this->_info[0], $this->_info[1], $width, $height, $color);

		$re = self::image($result, $mime, $filename);

		imagedestroy($result);
	}

	/**
	 * 等比例缩放
	 * (大图缩小,小图不变)
	 *
	 * @param string $filename 输出到文件
	 * @param int    $width    缩放宽度
	 * @param int    $height   缩放高度
	 * @param string $mime     输出类型(默认与源文件一至)
	 * @param string $color    补白顔色 如:'#2fee45'
	 */
	public function maybeReSizeToFile($filename,$width, $height, $mime=null, $color=0)
	{
		if (!$mime) {
			$mime = $this->_info['mime'];
		}

		if ($this->_info[0] < $width && $this->_info[1] < $height) {
			$width = $this->_info[0];
			$height = $this->_info[1];
		}
		else if ($this->_info[0] < $width){
			$width = $this->_info[0];
		}
		else if ($this->_info[1] < $height){
			$height = $this->_info[1];
		}

		$result = $this->crop(0, 0, $this->_info[0], $this->_info[1], $width, $height, $color);

		$re = self::image($result, $mime, $filename);

		imagedestroy($result);
	}

	public function resizeCrop($filename, $x_size, $y_size, $mime=null)
	{
		if (!$mime) {
			$mime = $this->_info['mime'];
		}

		list($width, $height) = $this->_info;

		//按比例缩小图片, 确保图片不小于90*60
		$ratio_w = $x_size/$width;
		$ratio_h = $y_size/$height;
		
		//缩小到相对较大的图片
		$scale = ($ratio_w > $ratio_h) ? $ratio_w : $ratio_h;

		$resize_width	= ceil($width * $scale);
		$resize_height	= ceil($height * $scale);

		//创建图片
		$dst_image = imagecreatetruecolor($resize_width, $resize_height);

		//重采样裁剪
		imagecopyresampled($dst_image, $this->_result, 0, 0, 0, 0, $resize_width, $resize_height, $width, $height);

		$re = self::image($dst_image, $mime, $filename);

		imagedestroy($dst_image);
	}

	/**
	 * 从文件创建一个图片
	 */
	protected function createFrom($filename, $mime=null)
	{
		switch ( $mime )
        {
            case 'gif':
            case 'image/gif':
                {
                    if (@imagetypes() & IMG_GIF) {
                        $oImage = @imagecreatefromgif($filename);
                    } else {
                        $ermsg = 'GIF images are not supported';
                    }
                }
                break;
            case 'jpg':
            case 'jpeg':
            case 'image/jpeg':
                {
                    if (@imagetypes() & IMG_JPG) {
                        $oImage = @imagecreatefromjpeg($filename) ;
                    } else {
                        $ermsg = 'JPEG images are not supported';
                    }
                }
                break;
            case 'png':
            case 'image/png':
                {
                    if (@imagetypes() & IMG_PNG) {
                        $oImage = @imagecreatefrompng($filename) ;
                    } else {
                        $ermsg = 'PNG images are not supported';
                    }
                }
                break;
            case 'wbmp':
            case 'image/wbmp':
                {
                    if (@imagetypes() & IMG_WBMP) {
                        $oImage = @imagecreatefromwbmp($filename);
                    } else {
                        $ermsg = 'WBMP images are not supported';
                    }
                }
                break;
            case 'bmp':
            case 'image/bmp':
                {
                    /*
                    * This is sad that PHP doesn't support bitmaps.
                    * Anyway, we will use our custom function at least to display thumbnails.
                    * We'll not resize images this way (if $sourceFile === $targetFile),
                    * because user defined imagecreatefrombmp and imagecreatebmp are horribly slow
                    */
                    if (@imagetypes() & IMG_JPG) {
                        $oImage = self::createFromBmp($filename);
                    } else {
                        $ermsg = 'BMP/JPG images are not supported';
                    }
                }
                break;
            default:
                $ermsg = $mime.' images are not supported';
                break;
        }

		if ( isset($ermsg) || false === $oImage )
		{
			throw new Exception( $ermsg );
        }

		$this->_result = $oImage;
	}

	/**
	 * 裁剪图片
	 * 如果指定了最终尺寸程充将等比例缩放图片
	 *
	 * @param int $src_x x轴
	 * @param int $src_y y轴
	 * @param int $src_w 裁剪宽度
	 * @param int $src_h 裁剪高度
	 * @param int $final_w 最终宽度 (可选)
	 * @param int $final_h 最终高度 (可选)
	 * @param string $color 背景填充色 例: #FF7EA9
	 *
	 * @return resource 裁剪后的图片资源句柄 (新打开的)
	 */
	public function crop($src_x, $src_y, $src_w, $src_h, $final_w=0, $final_h=0, $color=0)
	{
		/**
		 * 进行等比例裁剪和缩放
		 * 对于最终尺寸小于指定大小的图片用白色填充
		 */

		$dst_w = $src_w;
		$dst_h = $src_h;

		if ( $final_w == 0 || $final_h == 0 )
		{
			$final_w = $src_w;
			$final_h = $src_h;
		}

		//宽高比
		$aspectRatio = $src_w / $src_h;

		//宽度超出,宽度则为最终宽度，等比例缩放高度
		if ( $dst_w > $final_w )
		{
			$dst_w = $final_w;

			$dst_h = (int)($dst_w / $aspectRatio);
		}

		//缩放后高度是否依然超出,高度则为最终高度，等比例缩入宽度
		if ( $dst_h > $final_h )
		{
			$dst_h = $final_h;

			$dst_w = (int)($dst_h * $aspectRatio);
		}

		//计算x,y偏移
		$dst_x = ($dst_w < $final_w) ? (int)(($final_w - $dst_w) / 2) : 0;
		$dst_y = ($dst_h < $final_h) ? (int)(($final_h - $dst_h) / 2) : 0;

		//创建图片
		$dst_image = imagecreatetruecolor($final_w, $final_h);

		//填充底色
		if ( $color && strlen($color) == 7 )
		{
			$color = str_split(substr($color, 1), 2);

			$red = hexdec($color[0]);
			$green = hexdec($color[1]);
			$blue = hexdec($color[2]);
		}
		else
		{
			$red = $green = $blue = 255;
		}

		$im_color = imagecolorallocate($dst_image, $red, $green, $blue);

		imagefilledrectangle($dst_image, 0, 0, $final_w, $final_h, $im_color);

		//重采样裁剪
		imagecopyresampled($dst_image, $this->_result, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

		return $dst_image;
	}

	/**
	 * 将图像输出到浏览器或文件
	 *
	 * @param string $mime
	 * @param string filename 输出到的文件，默认输出到浏览器
	 *
	 * return bool
	 */
	static public function image($im, $mime, $filename=null)
	{
		switch ($mime)
        {
        case 'gif':
        case 'image/gif':
			imagegif($im, $filename);
			return true;
		case 'jpg':
		case 'jpeg':
		case 'image/jpeg':
		case 'image/bmp':
			imagejpeg($im, $filename);
			return true;
		case 'png':
		case 'image/png':
			imagepng($im, $filename);
			return true;
		case 'wbmp':
		case 'image/wbmp':
			imagewbmp($im, $filename);
			return true;
        }

		return false;
	}

	/**
    * Source: http://pl.php.net/imagecreate
    * (optimized for speed and memory usage, but yet not very efficient)
    *
    * @static
    * @access public
    * @param string $filename
    * @return resource
    */
    public static function createFromBmp($filename)
    {
        //20 seconds seems to be a reasonable value to not kill a server and process images up to 1680x1050
        @set_time_limit(20);

        if (false === ($f1 = fopen($filename, "rb"))) {
            return false;
        }

        $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
        if ($FILE['file_type'] != 19778) {
            return false;
        }

        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
        '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
        '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));

        $BMP['colors'] = pow(2,$BMP['bits_per_pixel']);

        if ($BMP['size_bitmap'] == 0) {
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        }

        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
        $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
        $BMP['decal'] = 4-(4*$BMP['decal']);

        if ($BMP['decal'] == 4) {
            $BMP['decal'] = 0;
        }

        $PALETTE = array();
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V'.$BMP['colors'], fread($f1, $BMP['colors']*4));
        }

        //2048x1536px@24bit don't even try to process larger files as it will probably fail
        if ($BMP['size_bitmap'] > 3 * 2048 * 1536) {
            return false;
        }

        $IMG = fread($f1, $BMP['size_bitmap']);
        fclose($f1);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
        $P = 0;
        $Y = $BMP['height']-1;

        $line_length = $BMP['bytes_per_pixel']*$BMP['width'];

        if ($BMP['bits_per_pixel'] == 24) {
            while ($Y >= 0)
            {
                $X=0;
                $temp = unpack( "C*", substr($IMG, $P, $line_length));

                while ($X < $BMP['width'])
                {
                    $offset = $X*3;
                    imagesetpixel($res, $X++, $Y, ($temp[$offset+3] << 16) + ($temp[$offset+2] << 8) + $temp[$offset+1]);
                }
                $Y--;
                $P += $line_length + $BMP['decal'];
            }
        }
        elseif ($BMP['bits_per_pixel'] == 8)
        {
            while ($Y >= 0)
            {
                $X=0;

                $temp = unpack( "C*", substr($IMG, $P, $line_length));

                while ($X < $BMP['width'])
                {
                    imagesetpixel($res, $X++, $Y, $PALETTE[$temp[$X] +1]);
                }
                $Y--;
                $P += $line_length + $BMP['decal'];
            }
        }
        elseif ($BMP['bits_per_pixel'] == 4)
        {
            while ($Y >= 0)
            {
                $X=0;
                $i = 1;
                $low = true;

                $temp = unpack( "C*", substr($IMG, $P, $line_length));

                while ($X < $BMP['width'])
                {
                    if ($low) {
                        $index = $temp[$i] >> 4;
                    }
                    else {
                        $index = $temp[$i++] & 0x0F;
                    }
                    $low = !$low;

                    imagesetpixel($res, $X++, $Y, $PALETTE[$index +1]);
                }
                $Y--;
                $P += $line_length + $BMP['decal'];
            }
        }
        elseif ($BMP['bits_per_pixel'] == 1)
        {
            $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
            if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
            elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
            elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
            elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
            elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
            elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
            elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
            elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
            $COLOR[1] = $PALETTE[$COLOR[1]+1];
        }
        else {
            return false;
        }

        return $res;
    }

}

