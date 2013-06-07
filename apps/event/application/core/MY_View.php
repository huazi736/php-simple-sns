<?php
require_once APPPATH . 'core/MY_Smarty.php';

/**
 * 视图控制器
 *
 * 约定!:
 *   注册视图变量以 $view->key = $val 型式注册,
 *   不可以注册以'_'开头的变量
 *
 * @author xuwh
 * @date <2012/05/12>
 * @version $Id$
 */
class MY_View
{
	public function __construct()
	{
	}

	/**
	 * @param string $type 视图类型(html,json,upload)
	 */
	public function fetch($tpl, $type=null, $phpEngine=false)
	{
		ob_start();
		$this->display($tpl, $type);
		return ob_get_clean();
	}

	/**
	 * @param string $type 视图类型(html,json,upload)
	 */
	public function display($tpl, $type=null, $phpEngine=false)
	{
		if (!$type) {
			if ($this->_isAjax()) {
				$type = 'json';
			}
			else if (!empty($_FILES)) {
				$type = 'upload';
			}
			else {
				$type = 'html';
			}
		}

		$vars = (array)$this;

		switch ($type)
		{
		case 'json' :
			header("Content-Type:text/javascript; charset=utf-8");
			echo json_encode($vars);
			break;

		case 'upload' :
			header("Content-Type:text/html; charset=utf-8");
			echo '<script>';
			echo 'window.parent.sendPhotoComplete('.json_encode($vars).');';
			echo '</script>';
			break;

		case 'html' :
			header("Content-Type:text/html; charset=utf-8");
			if ($phpEngine) {
				$this->_reader($tpl);
			}
			else {
				echo MY_Smarty::getInstance()->fetch($tpl,$vars);
			}
			break;

		default:
			throw new Exception("view type:{$type} 错误");
		}
	}

	private function _reader($tpl)
	{
		require APPPATH . "views/{$tpl}";
	}
	
	/**
     * 是否是AJAX请求
     */
    private function _isAjax()
    {
		return (
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		);
    }
}
