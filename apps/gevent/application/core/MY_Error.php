<?php
class MY_Error
{		
	const NOTICE_LEVEL = 1;
	
	public function __construct()
	{
		$this->info = '服务器异常,请稍后再试';	
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
	
	protected function handlerView($content, $isLog = false, $isError = false)
	{

		if($isLog)
		{
			ob_start();
			var_dump($content);
			$str = ob_get_clean();	
			$str = date('[Y-m-d H:i:s] ') . $_SERVER['REQUEST_URI'] . ': ' . $str;
			if($isError)
				error_log($str, 3, LOG_PATH . 'gevent/error.txt');
			else
				error_log($str, 3, LOG_PATH . 'gevent/exception.txt');
		}
		$obj->status = 0;
		$obj->info = $this->info;
		header("Content-Type:text/html; charset=utf-8");
		if(isset($_REQUEST['callback']))
			exit($_REQUEST['callback'].'('.json_encode($obj).')');
		else
			exit(json_encode($obj));	
	}

	public static function regHandler()
	{
		$obj = new self;
		set_exception_handler(array($obj, "exception_handler"));
		set_error_handler(array($obj, "error_handler"), E_ALL);
	}
}
