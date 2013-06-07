<?php

class Relation extends CI_Controller {
    
    private $service;

    public function __construct() {
        parent::__construct();
        
        $this->service = service('Relation');
    }

    public function index() {
        echo $this->service->test();
    }
    
    public function follow() {
        $uid = $this->input->get('uid');
        $uid2 = $this->input->get('uid2');
        
        echo $this->service->follow($uid, $uid2);
    }
    
    public function unFollow() {
        $uid = $this->input->get('uid');
        $uid2 = $this->input->get('uid2');
        
        echo $this->service->unFollow($uid, $uid2);
    }

    public function getFollowings() {
        $uid = $this->input->get('uid');
        
        $res = $this->service->getFollowings($uid);
        var_dump($res);
    }
    
    public function getRelationStatus() {
        $uid = $this->input->get('uid');
        $uid2 = $this->input->get('uid2');
        
        echo $this->service->getRelationStatus($uid, $uid2);
    }

}