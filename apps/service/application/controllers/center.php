<?php

class Center extends CI_Controller {

    public function __construct() {
        parent::__construct();
        try {
            $this->load->library('HessianPHP_lib');
        } catch (Exception $e) {
            $message = 'Code: ' . $e->getCode() . ' Message: ' . $e->getMessage();
            log_message('ERROR', $message);
        }
    }
    
    public function sayhello() {
        echo 'hello';
    }

    // 用户关系接口
    public function relation() {
        try {
            $this->load->model('RelationService_model');
            $service = new HessianService(new RelationService_model());
            $service->handle();
        } catch (Exception $e) {
            $message = 'Code: ' . $e->getCode() . ' Message: ' . $e->getMessage();
            log_message('ERROR', $message);
        }
    }

    // 群组接口
    public function group() {
        $this->load->model('GroupService_model');
        $service = new HessianService(new GroupService_model());
        // $service->displayInfo();
        $service->handle();
    }

    //用户资料接口  By sunlufu at 2012.8.2
    public function user() {
        $this->load->model('UserService_model');
        $service = new HessianService(new UserService_model());
        $service->handle();
    }

    // 测试客户端
    public function client() {
        $testurl = 'http://sunlufu.duankou.com/www_duankou/service/center/user';
        $proxy = new HessianClient($testurl);

        try {
//            $params = json_encode(array('userid' => '1000002103'));
//            $res = $proxy->getFriendList($params);
            $params = json_encode(array('sessionid' => '3hbj9tp5urubjc6nn4omo1b246', 'info' => true));
            $res = $proxy->getUserLoginState($params);
            var_dump(json_decode($res));
        } catch (Exception $e) {
            // handler error
            echo $e->getMessage();
        }
    }

}