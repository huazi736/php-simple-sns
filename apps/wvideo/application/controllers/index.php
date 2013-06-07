<?php
/**
 * 视频
 * @author        qqyu wangying
 * @date          2012/02/21
 * @version       1.2
 * @description   视频页面相关功能
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class index extends MY_Controller {
	function __construct()
	{
		parent::__construct();
	}
	public function main()
	{
		header('Location:'.mk_url('wvideo/video/index',array('web_id'=>$this->web_id)));
	}
}

