<?php

class DateAliasModel extends RedisModel {

    public function set($pageid, $date, $alias) {
        //Clear invalid alias
        //$this->clearInvalid($pageid);
        
        return $this->_redis->hSet('datealias:' . $pageid, $date, $alias) !== false ? true : false;
    }

    public function get($pageid, $date) {
        return $this->_redis->hGet('datealias:' . $pageid, $date);
    }

    public function getAll($pageid) {
        return json_encode($this->_redis->hGetAll('datealias:' . $pageid));
    }

    public function delete($pageid, $date) {
        return $this->_redis->hDel('datealias:' . $pageid, $date);
    }
    
    public function deleteAll($pageid) {
        return $this->_redis->delete('datealias:' . $pageid) > 0 ? true : false;
    }
    
    private function clearInvalid($pageid) {
        $webpageLine = new WebpageLineModel();
        $years = $webpageLine->getAllYears($pageid);
        
        $keys = $this->_redis->hKeys('datealias:' . $pageid);
        foreach ($keys as $key) {
            if (in_array($key, $years)) {
                continue;
            }
            $this->delete($pageid, $key);
        }
    }
}