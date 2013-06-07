<?php
/*
 * 文本区域挂件
 */
class textarea extends MY_Widget{
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 渲染挂件模板
	 * @param array $params array('value' => "挂件值", 'label' => "挂件标签", 'web_id' => "网页id");
	 */
	public function render($data){
		return $this->renderFile("textarea.html", $data);
	}
	
}