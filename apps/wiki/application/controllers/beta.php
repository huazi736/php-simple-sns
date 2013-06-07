<?php
//供前端测试使用
class Beta extends MY_Controller {
	public function __construct() {

		parent::__construct();

	}


	//新版
	public function edit(){
		$this->assignHeaderNav(array("编辑词条"));
		$this->display("wiki_edit.html");
	}
	public function view(){
		$this->assignHeaderNav(array("查看词条"));
		$this->display("wiki_view.html");
	}
	public function view_version(){
		$this->assignHeaderNav(array("查看版本"));
		$this->display("wiki_view_version.html");
	}
	public function info(){
		$this->assignHeaderNav(array("查看词条"));
		$this->display("wiki_info.html");
	}
	public function match(){
		$this->assignHeaderNav(array("网页资料"));
		$this->display("wiki_match.html");
	}
	public function unmatch(){
	    $this->assignHeaderNav(array("网页资料"));
		$this->display("wiki_unmatch.html");
	}
	public function category(){
		$this->display("wiki_category.html");
	}
	public function version(){
		$this->assignHeaderNav(array("历史版本"));
		$this->display("wiki_version.html");
	}
	public function testeditor(){
		$CI = &get_instance();
		print_r(get_class_methods($CI));
		exit;
		$this->display("testeditor");	
	}
}
