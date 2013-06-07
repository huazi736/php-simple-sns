<?php
/**
 * 权限model类
 * @author lijianwei
 * @date 2012/05/08
 */
class PrivModel extends CI_Model{
    private $_redis;
    private $user_ban_table = 'wiki_user_ban';

    //网页使用的redis键名
    private $_webpage_following_key = "webpage:following:%d"; //用户关注网页集合
    private $_webpage_following_hidden_key = "webpage:following:hidden:%d";//用户关注网页隐藏集合
    private $_tmp_zset_key = "tmp:mayknow:webpage:following:%d"; //临时使用   60秒过期  关注的全部网页(有序集合)

    public function __construct() {

    }
    /**
     * 检测用户是否具有创建词条  创建义项权限
     * @param int $uid
     * @param int $citiao_id
     * @return true or false
     * @edit 2012/05/15 不需要检测是否被封禁，因为是否封禁，只要是网页创建者都具有权限.
     */
    public function check_create($uid = 0, $web_id = 0) {
        //检测参数
        $uid = intval($uid); $web_id = intval($web_id);
        if(!$uid || !$web_id) return false;
        //获取网页信息
        $webinfo = call_soap('interest','Index','get_web_info',array($web_id));
        //只有网页创建者才有权限
        return isset($webinfo['uid']) && ($webinfo['uid'] == $uid);
    }

    /**
     * 检测用户是否具有编辑词条 编辑义项权限
     * 未被封禁的网页创建者、未被封禁的粉丝具有权限
     * 使用此方法之前，请一定要调用 $this->checkIdBan($uid)判断用户是否被封禁
     * @param int $uid
     * @param int $item_id 义项id
     * @return true or false
     */
    public function check_edit($uid = 0, $item_id = "") {
        $uid = intval($uid);
        if(!$uid) return false;
        if(!check_mongo_id($item_id)) return false;

        $this->load->library("mongo_db", "", "mdb");
        //查询义项对应网页ids
        $web_ids = $this->mdb->findOne("wiki_items", array("_id" => new MongoId($item_id)), array("web_id"));
        //查询用户创建的网页
        $create_web_id = call_soap("interest", "Index", "get_webs", array($uid));
        //取交集
        $common_web_id = array_intersect($web_ids, $create_web_id);

        if(is_array($common_web_id) && count($common_web_id)) return true;

        //获取用户关注的网页
        $following_web_ids = $this->getUserFollowingWeb($uid);
        //取 交集
        $common_web_id = array_intersect($web_ids, $following_web_ids);

        if(is_array($common_web_id) && count($common_web_id)) return true;

        return false;
    }

    /**
     * 检测用户是否具有还原历史版本权限,还原模块权限
     * 未被封禁的网页创建者、未被封禁的粉丝具有权限
     * 使用此方法之前，请一定要调用 $this->checkIdBan($uid)判断用户是否被封禁
     * @param int $uid
     * @param int $item_id 义项id
     * @return true or false
     */
    public function check_restore($uid = 0, $item_id = "") {
        return $this->check_edit($uid, $item_id);
    }

    /**
     * 检测用户是否具有举报权限
     * 未被封禁的网页创建者、未被封禁的粉丝具有权限
     * 使用此方法之前，请一定要调用 $this->checkIdBan($uid)判断用户是否被封禁
     * @param int $uid
     * @param int $item_id 义项id
     * @return true or false
     */
    public function check_report($uid = 0, $item_id = "") {
        return $this->check_edit($uid, $item_id);
    }

    /**
     * 获取用户关注的网页id
     * @param int $uid
     * return array(web_id,,,)
     */
    public function getUserFollowingWeb($uid = 0) {
        //实例化redis
        $this->_redis = get_redis("default");
        //用户关注的网页id
        $this->_redis->zUnion(sprintf($this->_tmp_zset_key, $uid), array(sprintf($this->_webpage_following_key, $uid), sprintf($this->_webpage_following_hidden_key, $uid)));
        $following_web_ids = $this->_redis->zRange(sprintf($this->_tmp_zset_key, $uid), 0, -1);
        //设置失效时间60s
        $this->_redis->expire(sprintf($this->_tmp_zset_key, $uid), 60);

        $following_web_ids = $following_web_ids ? $following_web_ids : array();

        return $following_web_ids;
    }

    /**
     * 判断用户是否被封禁
     *
     * @author bohailiang
     * @date   2012/5/9
     * @param  $uid   int  用户id
     * @param  $return_time   boolean  true - 返回封禁时间，false - 仅返回true or false
     * @access public
     * @return true 被封禁 | int =0 - 永久封禁，<0 封禁到期的时间戳 / false 未封禁
     */
    public function checkIdBan($uid = 0, $return_time = false){
        if(empty($uid) || !is_numeric($uid)){
            return (false == $return_time) ? true : 0;
        }
        $this->load->library("mongo_db", "", "mdb");
        //获取用户封禁记录
        $where = array('uid' => $uid);
        $ban_info = $this->mdb->findOne($this->user_ban_table, $where);
        if(empty($ban_info) || !isset($ban_info['ban']['ban_status']) || 0 == $ban_info['ban']['ban_status']){
            //未封禁
            return false;
        }

        //遍历封禁数组，检测封禁时间
        if(!isset($ban_info['ban']['ban_list']) || !is_array($ban_info['ban']['ban_list'])){
            return false;
        }
        $ban_list = $ban_info['ban']['ban_list'];
        $the_ban = array();
        $ban_index = false;
        //获取正在使用的封禁
        foreach($ban_list as $key => $value){
            if(1 == $value['ban_list_status']){
                $ban_index = $key;
                $the_ban = $value;
                break;
            }
        }
        if(false == $ban_index){
            return false;
        }

        if(in_array('level_2', $the_ban['ban_level'])){
            //永久封禁
            return (false == $return_time) ? true : 0;
        }
        //检测时间
        $the_sub_time = time() - $the_ban['ban_start'];
        $the_ban_time = $the_ban['ban_days'] * 24 * 60 * 60;
        if($the_sub_time >= $the_ban_time){
            //超过封禁时间，解禁
            $this->unBanUser($ban_info['_id'], $ban_index);
            return false;
        }
        return (false == $return_time) ? true : $the_ban_time;
    }

    /**
     * 封禁时间到，对用户解禁
     *
     * @author bohailiang
     * @date   2012/5/9
     * @param  $_id        string  封禁id
     * @param  $ban_index  int     封禁索引
     * @access public
     * @return true / false
     */
    public function unBanUser($_id = '', $ban_index = false){
        if(empty($_id) || !is_string($_id) || !is_numeric($ban_index) || 0 > $ban_index){
            return false;
        }

        $where = array('_id' => new MongoId($_id));
        $data = array('ban.ban_list.' . $ban_index . '.ban_list_status' => 0, 'ban.ban_status' => 0);
        $this->load->library("mongo_db", "", "mdb");
        $result = $this->mdb->update($this->user_ban_table, $where, $data);
        if($result){
            return $result;
        }
        return false;
    }

    /**
     * 检测是否有引用词条权限
     *
     * @param string $web_id 网页id
     * @param int $uid  用户id
     * return true or false
     */
    public function check_match($web_id = 0, $uid = 0) {
        if(!$web_id || !$uid || !is_numeric($web_id) || !is_numeric($uid)) return false;

        $web_info = service("interest")->get_web_info($web_id); //获取网页信息
        if(!$web_info || !isset($web_info['uid'])) return false;
        return ($web_info['uid'] == $uid);
    }

    /**
     *
     * 词条是否引用方法
     * 本方法主要针对编辑词条时，添加词条默认引用
     *     |
     *     |
     *     |网页创建者，来编辑词条1.词条正在引用(勾选)2.词条非引用，在编辑(不勾选)
     * 编辑 |
     *     |
     *     |
     *     |词条贡献者，贡献词条版本库
     * @param unknown_type $action
     * @param unknown_type $item_id
     * @param unknown_type $version
     * @param unknown_type $web_id
     * @param unknown_type $uid
     */
    public function check_match_checkbox($action = "add", $item_id = "", $version = 0 , $web_id = 0, $uid){

        $is_match = $this->check_match($web_id, $uid);//是否可以引用
        $return =array();
        if(!$is_match){//非网页创建者,不出现不引用词条
            return array("quote"=>'0','msg'=>"词条贡献者");
        }else{
            $this->load->library("Mongo_db", "", "mdb");
            $is_web_info = $this->mdb->findOne("wiki_web_info", array("web_id" => $web_id, "item_id" => $item_id, "use_module_version" =>intval($version)));
            $match_item_info = $this->mdb->findOne("wiki_web_info", array("web_id" => $web_id));

            if($is_web_info && $match_item_info){  //网页创建者，来编辑词条1.词条正在引用(勾选)
                return array("quote"=>'check','msg'=>"");
            }
            elseif($match_item_info){               //词条非引用，在编辑(不勾选)
                return array("quote"=>'1','msg'=>"你正在编辑网页 ");
            }

        }
    }
}