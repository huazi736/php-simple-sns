<?php

class MY_Controller extends DK_Controller
{
    protected $view_var = array();

	function __construct()
	{
		parent::__construct();
	}

	public function assign($name, $value)
	{
		$this->view_var[$name] = $value;
        $this->view->assign($name, $value);
    }

	public function ajaxReturn($type='jsonp')
	{
		$result = $this->view_var;

		unset($result['js_config'], $result['is_index_current'], $result['navigation_menu']);

        $type = strtoupper($type);

        if ($type == 'JSON') {
            header("Content-Type:text/html; charset=utf-8");
            echo json_encode($result);
        } elseif ($type == 'EVAL') {
            header("Content-Type:text/html; charset=utf-8");
            echo json_encode($result);
        } elseif ($type == 'JSONP') {
            header("Content-Type:text/html; charset=utf-8");
			echo $this->input->get_post('callback').'('.json_encode($result).')';
        }
    }

}
