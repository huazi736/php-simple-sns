<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends MY_Controller 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('usermodel');
    }
	public function index()
	{        	    
	    $this->display('demo.html');
	}
	
	public function fastdfs_upload()
	{
	    $this->load->fastdfs('default', 'return', 'fastdfs');
	    $fileinfo = $this->fastdfs->uploadFile(VAR_PATH . 'photo.jpg');
	    echo '<pre>';
	    print_r($fileinfo);
	    echo '</pre>';
	}
	
	public function mysql()
	{
	    echo '通过MySQL修改【uid=1000001005】的用户的手机号:<br/>';
	    echo '<pre>';
	    echo $this->usermodel->editUserMobile('10000010010','13957119235');
	    echo '</pre>';
	}
	
	public function service()
	{	    
	    echo '通过service查询【uid=1000001005】的用户信息:<br/>';
	    echo '<pre>';
	    print_r($this->usermodel->getUserInfo('1000001000'));
	    echo '</pre>';
	}
	
	public function info()
	{
	    phpinfo();
	}
	
	public function memcache()
	{
	    echo '把【uid=1000001005】的用户信息保存到memcache,并输出:<br/>';
	    $user = $this->usermodel->getUserInfo('1000001000');
	    $this->usermodel->saveUserToMemcache($user);
	    echo '<pre>';
	    print_r($this->usermodel->getUserInfoByCache('1000001000','memcache'));
	    echo '</pre>';
	}
	
    public function redis()
	{
	    echo '把【uid=1000001005】的用户信息保存到redis,并输出:<br/>';
		die;
	    $user = $this->usermodel->getUserInfo('1000001000');
	    $this->usermodel->saveUserToRedis($user);
	    echo '<pre>';
	    print_r($this->usermodel->getUserInfoByCache('1000001000','redis'));
	    echo '</pre>';
	}
	
	public function helper()
	{
	    $this->load->helper('demo');
	    echo '调用自定义函数echo_url:<br/>';
	    echo_url('album/photo/view',array('id'=>'100012'));
	}
	
	public function mongodb()
	{
	    echo '把【uid=1000001005】的用户信息保存到mongodb,并输出:<br/>';
	    $uid = '1000001000';
		$user = $this->usermodel->getUserInfo($uid);
	    $this->usermodel->saveUserToMongo('test',$user);
	    $result = $this->usermodel->getUserInfoByMongo('test',array('uid' => $uid));
	    echo '<pre>';
	    print_r($result);
	    echo '</pre >';
	}	
	
	public function showurl()
	{
	    echo mk_url('front/login/index',array('backurl'=>'http://www.baidu.com'));
        echo '<br/>';
        
        echo mk_url('main/index/main',array('backurl'=>'http://www.baidu.com'));
        echo '<br/>';
        
        echo mk_url('blog/post/view',array('id'=>'123456'));
        echo '<br/>';
        
        echo mk_url('main/timeline/index',array('dkcode'=>'100000'));
        echo '<br/>';
        
        echo mk_url('blog/post/view',array('dkcode'=>'100000','id'=>'123456'));
        echo '<br/>';
	}
	
	public function parseurl()
	{	    
        $url[] = 'http://www.duankou.com';
        $url[] = 'http://www.duankou.com/front';
        $url[] = 'http://www.duankou.com/front/login';
        $url[] = 'http://www.duankou.com/front/login/dologin?backurl=http://www.duankou.com/timeline';
        $url[] = 'http://www.duankou.com/timeline/view';
        $url[] = 'http://www.duankou.com/timeline';
        $url[] = 'http://www.duankou.com/10000145/timeline/view';
        $url[] = 'http://www.duankou.com/10000145';
        $url[] = 'http://blog.duankou.com/post/view?id=123456';
        $url[] = 'http://blog.duankou.com/post?id=123456';
        $url[] = 'http://blog.duankou.com/10000145/post/view?id=123456';
	    $router = DK_Router::getInstance();
	    foreach($url as $key=>$one)
	    {
	        $result = $router->testParse($one);
	        echo $one . ':<br/><span style="color:#00f">';
	        print_r($result);
	        echo '</span><br/>';
	    }
	}
	
	public function log_message(){
		echo '把【uid=1000001005】的用户信息保存到日志中,<br/>';
	    $uid = '1000001000';
		$user = $this->usermodel->getUserInfo($uid);
		log_message('ERROR',$user);
	}
        
        public function log()
        {
            echo WEB_ROOT . "var/logs/aa.txt";
        }
	
        public function debugtoolbar()
        {
            $this->enable_profiler(true);  #1 在控制器里面开始调试工具
			$this->benchmark->mark('my_start'); #2 基准测试代码段时间，做标记。请以tag_start 开始，tag_end结束。tag可自定义
            $a = $this->usermodel->editUserMobile('10000010010','13957119235');
		    $this->benchmark->mark('my_end');
            
			DK_Console::log('Iinit date'); #3 输出调试日志
            DK_Console::log('Hey, this is really cool');
            DK_Console::log_memory($a, 'a'); #4 该方法，如果不加参数，则记录执行到当前代码使用内测，第一个参数是需要检查内测使用的变量或者对象，第二个参数是标记名称，默认为PHP. 
			
            $this->display('demo.html');
        }
}
