<?php

class MY_Model extends DK_Model {

    public function __construct() {
        parent::__construct();
        
        $this->init_db('interest');
        
    }

}