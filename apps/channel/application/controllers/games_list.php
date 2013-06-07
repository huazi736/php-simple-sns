<?php
    class Games_list extends MY_Controller
    {
        function __construct()
        {
            parent::__construct();
        }
        
        function index()
        {
        	$this->assign('web_info',$this->web_info);
            $this->assign("text","游戏列表");
            $this ->display("games/games_list");
        }
    }
?>