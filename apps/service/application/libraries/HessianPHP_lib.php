<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class HessianPHP_lib {
    
    public function __construct() {
        require_once APPPATH . 'libraries/HessianPHP/HessianService.php';
        require_once APPPATH . 'libraries/HessianPHP/HessianClient.php';
    }

}