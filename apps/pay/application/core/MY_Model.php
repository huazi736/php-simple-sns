<?php
//require_once EXTEND_PATH . 'core' . DS . 'DK_Model.php';
class MY_Model extends DK_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->init_db('user');
        $this->init_redis('user');
        $this->init_memcache('user');
        $this->init_mongodb('user');
    }
}