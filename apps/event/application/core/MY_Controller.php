<?php
use \Models as Model;

require_once APPPATH . 'models/Loader.php';
require_once APPPATH . 'core/MY_Error.php';

Model\Loader::autoload(true);
//MY_Error::regHandler();

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
    
}
