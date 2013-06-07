<?php


class Search extends CI_Controller {
    
    private $service;

    public function __construct() {
        parent::__construct();
        $this->service = service('GlobalSearch');
    }
    
    public function index() {
        $this->service->test();
    }
    
}