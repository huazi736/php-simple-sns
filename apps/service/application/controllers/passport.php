<?php

class Passport extends CI_Controller
{
    public function index()
    {
        $user = service('Passport')->loginLocal('10000145','123456',false);
        print_r($user);
        //echo 'success';                
    }
}