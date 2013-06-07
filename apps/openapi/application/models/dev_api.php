<?php
/*
 * user service class
 * Athor:xuxuefeng
 * date:2012/7/5
 */

class Dev_api extends MY_Model {
	
	var $object;
	 public function __construct()
	 {
	 	parent::__construct();
	 	include_once APPPATH . 'libraries' . DS . 'hessianphp' . DS . 'HessianService.php';
	 }
	 
	 public function getService($object = NULL)
	 {
	 	$ra = new HessianService($object);
	 	return $ra;
	 }
}