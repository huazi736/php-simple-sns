<?php
class User extends CI_Controller
{
    public function index()
    {
        $user = service('User')->getUserInfo('1000001002');
        print_r($user);
    }
}