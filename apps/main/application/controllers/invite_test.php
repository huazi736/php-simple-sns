<?php

class Invite_test extends DK_Controller{
    public function __construct() {
        parent::__construct();
        $this->load->model('invitecodemodel');
    }
    
    function index()
    {
        echo  'test';
    }
}