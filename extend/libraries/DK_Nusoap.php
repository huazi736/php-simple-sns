<?php

class DK_Nusoap {
    
    function __construct() {
        require_once rtrim(EXTEND_PATH, '/ ') . '/vendor/Nusoap/nusoap.php';
    }
    
    static function start() {
        return new DK_Nusoap();
    }
    
}