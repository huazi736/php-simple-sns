<?php

class MY_Model extends DK_Model {

    public function __construct() {
        parent::__construct();
    }

    protected function decodeParams($params) {
        return json_decode($params, true);
    }

    protected function encodeResult($status, $msg, $result) {
        return json_encode(array(
                    'code' => $status,
                    'text' => $msg,
                    'result' => $result
                ));
    }
    
}
