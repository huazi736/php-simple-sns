<?php
/**
 * 异常错误处理
 * 
 * @author hpw
 * @date 2012/07/09
 */
require_once EXTEND_PATH . 'core/DK_View.php';
class MY_Error
{
	public $isAlert = false ;//是否以弹窗形式提示异常
	
	const NOTICE_LEVEL = 1;
	
	public function __construct()
	{
		$this->isAlert = $this->isAjax();
		$this->info = '服务器异常,请稍后再试';		
		$this->url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	}


	/**
     * 是否是AJAX请求
     */
    public function isAjax()
    {
		return (
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		);
    }
	
	public function exception_handler($e)
	{
		if($e->getCode()==self::NOTICE_LEVEL)
		{
			$this->info = $e->getMessage();
			$this->handlerView($this->info);
		}
		else
			$this->handlerView($e->getMessage().$e->getTraceAsString(), true);

	}


	public function error_handler($errno, $errstr, $errfile, $errline, $errcontext)
	{
		if (strpos($errfile, '/smarty') === false)
			$this->handlerView("{$errfile}[{$errline}]{$errstr}", true, true);
	}

	/**
	 * 所有异常最终提示方式
	 * @param $content string 异常信息
	 * @param $isLog bool 是否记入日志
	 * @param $isError bool 错误 or 异常
	 */
	protected function handlerView($content, $isLog = false, $isError = false)
	{
		if($isLog)
		{
			ob_start();
			var_dump($content);
			$str = ob_get_clean();	
			$str = date('[Y-m-d H:i:s] ') . $_SERVER['REQUEST_URI'] . ': ' . $str;
			if($isError)
				error_log($str, 3, LOG_PATH . 'event/error.txt');
			else
				error_log($str, 3, LOG_PATH . 'event/exception.txt');
		}
		if($this->isAlert)
		{
			$obj->status = 0;
			$obj->info = $this->info;
			header("Content-Type:text/html; charset=utf-8");
			if(isset($_REQUEST['callback']))
				exit($_REQUEST['callback'].'('.json_encode($obj).')');
			else
				exit(json_encode($obj));
		}
		$view = new DK_View();
		$view->assign('msg', array($this->info));
		$view->assign('url' , isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
		$view->display('error.html');
		exit;			
	}
	public static function regHandler()
	{
		$obj = new self;
		set_exception_handler(array($obj, "exception_handler"));
		set_error_handler(array($obj, "error_handler"),E_ALL);
	}
}
