<?php

class Test extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {


		var_dump(api('WebTimeline')->delWebpage(1604,array(1)));
        
    }
    
    public function relation() {
        echo service('Relation')->test();
    }
    
    public function group() {
    	print_r( service('Group')->getGroupMembers(103568136803060)) ;
    }
}
    