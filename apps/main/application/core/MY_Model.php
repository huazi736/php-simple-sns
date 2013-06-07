<?php
//require_once EXTEND_PATH . 'core' . DS . 'DK_Model.php';
class MY_Model extends DK_Model
{
    public function __construct()
    {
        parent::__construct();
        require_once CONFIG_PATH . '/tables.php';
        $this->init_db('user');
    }
}