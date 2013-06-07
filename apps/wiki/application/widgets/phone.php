<?php
/*
 * 座机挂件
 */
class phone extends MY_Widget{
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 渲染挂件模板
	 * @param array $params array('value' => "挂件值", 'label' => "挂件标签", 'web_id' => "网页id");
	 */
	public function render($data){
		return $this->renderFile("phone.html", $data);
	}
	
}