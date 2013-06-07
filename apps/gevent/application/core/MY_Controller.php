<?php
use \Models as Model;

require_once APPPATH . 'models/Loader.php';
require_once APPPATH . 'core/MY_Error.php';

Model\Loader::autoload(true);
MY_Error::regHandler();

/**
 * 控制器类文件
 * @author hpw
 * @version $ 2012/07/06
 */
class MY_Controller extends DK_Controller
{
   
    /**
     * 构造函数
     */
    public function __construct()
    {
    	parent::__construct();
		$_SESSION = Model\MY_Session::start();
		$_SESSION['uid']  = $this->uid;
    	
    }
	
	/**
	 * 错误提示
	 */
	public function error($info)
	{
		$status = 0;
		$result = array();
		$this->ajaxReturn($result, $info, $status);
	}
	
	/**
     * Ajax方式返回数据到客户端
     * 
     * @param mixed $data 要返回的数据
     * @param string $info 提示信息
     * @param boolean $status 返回的状态
     * @param string $type 返回的类型 JSON|XML|HTML|EVAL|TEXT
     * 
     */
    public function ajaxReturn($return, $info = '', $status = 1, $type = 'json')
    {
        $return['status'] = $status;
        $return['info'] = $info;
		if(!isset($return['data']))
			$return['data'] = '';
		$data = $return['data'];
        $type = strtoupper($type);
        $callback = $this->input->get_post('callback');
        if($type == 'JSON' && !empty($callback))
            $type = 'JSONP';
        if($type == 'JSONP' && empty($callback))
            $type = 'JSON';      
        if ($type == 'JSON')
        {
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($return));
        }
		elseif ($type == 'JSONP')
        {
            header("Content-Type:text/html; charset=utf-8");
			exit($callback.'('.json_encode($return).')');
        }
        elseif ($type == 'XML')
        {
            header("Content-Type:text/xml; charset=utf-8");
        }
        elseif ($type == 'EVAL')
        {
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }
        elseif ($type == 'TEXT')
        {
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }
        elseif ($type == 'HTML')
        {
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }
    }
}
