<?php
/*摄影配置文件*/

/*临时目录*/
$config['tmp_file_path'] = VAR_PATH . 'tmp/shoot/';

/*允许的格式*/
$config['allowed_types'] = array('jpeg','jpg','png','gif');

/*单个文件大小 单位 KB*/
$config['max_size'] = 5*1024;

/*缩略图配置*/
$config['middle_pic'] = array(
			'width' => 300,
			'height' => 300,
			'quality'=>90
		);

$config['small_pic'] = array(
			'width' => 100,
			'height' => 100,
			'quality'=>80
		);
?>