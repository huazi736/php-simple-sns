<?php
class Demo extends MY_Controller
{
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 设计源稿
	 */
	public function index(){
		$this->view('demo');
	}

}