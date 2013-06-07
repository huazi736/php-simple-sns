<?php
/** JUST TEST **/
class Index extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		$this->load->model('blogmodel','blog');
		$this->load->model('bloguploadmodel','upload');
	}
	
	public function test(){
		//echo "<pre>";
		//print_r($res = service('SystemPurview')->checkApp('blog'));
		//json_decode($res[module_purview]);
		echo "你妹的";
		//$a = api('UserPurview')->editBlogPurview($id,1);
		//$id = 1937;
		//var_dump(api('Blog')->delBlog($id));

	}
	
	
	// 这里只是用来做测试的!
	public function Index()
	{
		$id = '121';
		// $this->test($id = null);
		$blog_res = $this->blog->getBlog('121', '1000001556', '1000001556');
		$blog = $blog_res['0'];
		$imgs = $this->blog->getPicture($blog['id'],'blog');
		// var_dump($imgs);
		$content = $blog['content'] . '{img_003}123123';
		echo $this->strToImg($content, $imgs, '_s',true,0);
		/* echo $content .= '{img_002}只是一个{img_026}测试,看看有多少img{img_005}在这个里面';
		foreach($imgs as $k=>$v)
		{
			$_preg = '/\{img_'.$v['title'].'\}/i';
			$url = '<img src="' . base_url() . 'tmp/' . $v['name'] . '_s' . $v['ext'] . '" />';
			$content = preg_replace($_preg, $url, $content,1);
		}
		$preg = '/\{img\_\d{3}\}/i';
		$content = preg_replace($preg, '', $content);
		echo $content; */
	}
	
	// $location	是否将图片扔到内容最后面,false替换到当前位置;
	public function strToImg($content = '', $imgs = null, $img_type = '_s', $location = false, $_count = 0)
	{
		$nums = 0;
		if(is_array($imgs))
		{
			foreach($imgs as $k=>$v)
			{
				if(($_count == 0) or ($nums < $_count)) {
					$_preg = '/\{img_'.$v['title'].'\}/i';
					$url = '<br /><img src="' . base_url() . 'tmp/' . $v['name'] . $img_type . $v['ext'] . '" />';
					$_exists = preg_match($_preg, $content);
					if($_exists and !$location) {
						$content = preg_replace($_preg, $url, $content,1);
					} else {
						// $content = preg_replace($_preg, '', $content,1);
						$content .= $url;
					}
					$nums ++;
				}
			}
			$preg = '/\{img\_\d{3}\}/i';
			$content = preg_replace($preg, '', $content);
		}
		return $content;
	}
	
	public function doImg()
	{
		$file = 's.jpg';
		$this->load->library('Image','gd2');
		$this->Image->resize_two();
		// $this->upload->doImage($file,null,'144');
	}
	
	function getImgInfo()
	{
		$path = './tmp/3ed400c5b3673ebf93542bbf180a2ccc.jpg';
		if(function_exists('getimagesize'))
		{
			$info = @getimagesize($path);
			var_dump($info);
		}
		else
		{
			echo 'Not Func!';
		}
	}
	
	public function test_fdfs()
	{
		$f = $this->fdfs;
		
		$groupname = 'group2';
		$filename = 'M00/01/4F/wKgM8k-DrEDC6i7tAAAP5v2fXjk355.jpg';
		$preg = '/\./is';
		$filename = preg_replace($preg, '_b.', $filename);
		var_dump($filename);
		$url = $f->get_file_url($groupname, $filename);
		echo '<img src="'.$url.'" />';
	}
	
	function conf()
	{
		echo TOKENNAME,'<br />';
		echo DEFINE_TEST,'<br />';
		echo WEB_BAR,'<br />';
		echo DS,'<br />';
		echo '<pre>';
		print_r(get_defined_constants());
		echo '</pre>';
	}
}