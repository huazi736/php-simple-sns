<?php

class TestModel extends DkModel {
    
    public function __initialize()
	{
		$this->init_redis();
        var_dump($this->redis);
	}
    
    
    public function getRedis() {
        return $this->redis;
    }
    
    
}