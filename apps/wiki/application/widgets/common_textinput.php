<?php
/*
 * 普通文本框挂件
 */
class common_textinput extends MY_Widget{
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 渲染挂件模板
	 * @param array $params array('value' => "挂件值", 'label' => "挂件标签", 'web_id' => "网页id");
	 */
	public function render($data){
		return $this->renderFile("common_textinput.html", $data);
	}
	
}