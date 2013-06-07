<?php

class Test extends DK_Controller
{
	public function __construct()
	{
		parent::__construct();
		
	}
	
	public function index()
	{
		$this->display('index.html');
	}
	
	public function main(){
	
		echo 'test data!';
	}
	
	
}