<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * apps调用Image处理库的配置，可以自定义选择模块使用的图片库
 *
 * @author vicente
 * @description array('gd', 'gd2', 'imagick', 'imagick_class', 'gmagick', 'gmagick_class'); 支持的类，imagick_class是php扩展方式，imagick是php调用shell方式
 * @version $Id
 */

return array(
    'default' => array(
            'image_library'       => 'imagick_class',
               ),
    'local' => array(
            'image_library'       => 'imagick',
            'cmd_path'            => '/usr/local/ImageMagick/bin/',
          ),
	'gd' => array(
            'image_library'       => 'gd2',
          )

);