<?php
/**
 * @desc    wiki下公用控制器
 * @author  sunlufu
 * @date    2012-05-15
 * @version v1.2.001
 */
class CommonModel extends MY_Model {
    public function __construct(){
        parent::__construct();
    }
    /**
     * @desc 引导用户添加关注网页功能
     * @param string $itemid 词条义项id
     * @return array 拍过序的含有本词条义项的网页信息,一位数组的键：aid,uid,`name`,name_pinyin,fans_count,imid,create_time
     */
    public function getwebs($itemid){
        if(empty($itemid)) return '0';
        $map = $item_info = $ret = $fans_count = array();
        $map = array('_id' => new MongoId($itemid));
        $item_info = $this->mdb->findOne('wiki_items', $map);
        if(empty($item_info['web_id'])) return '0';
        $ret = call_soap("interest", "Index", "get_web_info",array($item_info['web_id']));
        $fans_count = arrayTwoOneByField($ret,'fans_count');
        array_multisort($fans_count, SORT_DESC, SORT_NUMERIC,$ret);
        return $ret;
    }
    /**
     * @desc 用户关注网页
     * @param string $uid 用户id
     * @param string $webid 网页id
     * @return 成功返回网页粉丝数，失败返回false
     */
    public function setfollow($uid, $webid){
        $uid = intval($uid);
        $webid = intval($webid);
        if($uid < 1 || $webid < 1) return false;
        $ret = call_soap('social', 'Webpage', 'follow', array('uid' => $uid, 'pageid' => $webid));
        return $ret;
    }
    /**
     * @desc 获得用户关注的所有网页
     * @param int $uid 用户id
     * @return 成功返回关注网页的id的一维数组，失败返回false
     */
    public function getfollow($uid){
        $uid = intval($uid);
        if($uid < 1) return false;
        $ret =  call_soap('social', 'Webpage', 'getAllFollowings', array('uid' => $uid, 'self' => true));
        return $ret;
    }
    /**
     * @desc 词条+词条义项访问量
     * @param string $wordid 词条id
     * @param string $itemid 词条义项id
     * @return 成功返回true，失败返回false
     */
    public function setVisitNum($wordid, $itemid){
        $wordid = trim($wordid);
        $itemid = trim($itemid);
        if(empty($wordid) || empty($itemid)) return false;
        $wordarr = array('$inc' => array('visit_count'=>1));
        $mkword = $this->mdb->update_custom('wiki_citiao', array('_id' => new MongoId($wordid)), $wordarr);
        if(!$mkword) return false;
        $itemarr = array('$inc' => array('visit_count'=>1));
        $mkitem = $this->mdb->update_custom('wiki_items', array('_id' => new MongoId($itemid)), $itemarr);
        if($mkitem) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * @desc 获得一个词条的所有义项信息
     * @param string $wordid 词条id
     * @param array $field 返回数据的字段，默认所有字段都返回
     * @param array $sort      按字段排序
     * @param int $limit       查找数量
     * @param int $offset      开始游标
     * @return 成功返回词条义项信息的二位数组，失败返回false
     */
    public function getItemInfo($wordid, $field = array(), $sort = array(), $limit = 9999, $offset = 0){
        $wordid = trim($wordid);
        if(empty($wordid)) return false;
        $ret = $this->mdb->findAll('wiki_items', array('citiao_id' => $wordid), $field, $sort, $limit ,$offset);
        return $ret;
    }
    /**
     * @desc 获得一个词条的信息
     * @param string $wordid 词条id
     * @param array $field 返回数据的字段，默认所有字段都返回
     * @return 成功返回词条义项信息的二位数组，失败返回false
     */
    public function getWordInfo($wordid, $field = array()){
        $wordid = trim($wordid);
        if(empty($wordid)) return false;
        $ret = $this->mdb->findOne('wiki_citiao', array("_id"=>new MongoId($wordid)), $field);
        return $ret;
    }
    public function filterContent($content = ""){
        if(!$content) return "";
        $this->load->library("Mongo_db", "", "mdb");
        $filters = $this->mdb->findAll("wiki_filter");
        $wiki_last_create_filters_time = get_cache("wiki_last_create_filters_time");  //上次同步过滤词时间

        $expire_time = 2592000*3; //三个月

        if(!count($filters) || !$wiki_last_create_filters_time || ((time() - $wiki_last_create_filters_time) > $expire_time)) { //如果没有过滤词,或者已经过期
            //连接blog库
            //include APPPATH. "config/database.php";
            //$new_db = $db['default'];
            //$new_db['database'] = 'dk_blogdb';
            $blogdb = $this->load->database("blog", true, true);
            $filters = $blogdb->select("id,badword")->from("filter")->where(array("is_delete" => 1))->get()->result_array();
            //插入wiki_filter表中
            $this->mdb->clearCollection("wiki_filter"); //清空wiki_filter表
            $this->mdb->batchInsert("wiki_filter", $filters); //批量插入
            set_cache("wiki_last_create_filters_time", time());
        }
        $filter_arr = arrayTwoOneByField($filters, "badword");
        //@todo 可能有点性能问题
        return str_replace($filter_arr, "<a title='该内容涉及法律或道德问题，不能被显示'>***</a>", $content);
    }

    /**
     * @author zhengfanggang
     * @date 2012-07-09
     * 图片等比缩放
     * @$src            原图片路径		加文件名
     * @$dst            截取图保存路径  	加文件名
     * @$dst_w		          截取图宽度
     * @$dst_y		   	截取图高度
     * @$quality        截取图品质 100之内的正整数
     * @return  boolean 成功返回 true 失败返回 false
     */
    function resizeImageRatio($src,$dst,$dst_w,$dst_y,$quality='85'){

        if(!is_file($src) ){
            return false;
        }
        $data 	= @getimagesize($src);
        $src_width 	= $data[0];
        $src_height = $data[1];

        $ratio_w	= $dst_w/$src_width;
        $ratio_h	= $dst_y/$src_height;
        if($ratio_w < $ratio_h){
            $resize_width	= $src_width * $ratio_w;
            $resize_height	= $src_height * $ratio_w;
        }else if($ratio_w >= $ratio_h){
            $resize_width	= $src_width * $ratio_h;
            $resize_height	= $src_height * $ratio_h;
        }


        switch ($data[2]){
            case 1:
                $im = imagecreatefromgif($src);
                break;
            case 2:
                $im = imagecreatefromjpeg($src);
                break;
            case 3:
                $im = imagecreatefrompng($src);
                break;
            default:
                return false;
                break;
        }

        //  使用的函数
        $func_imagecreate = function_exists('imagecreatetruecolor') ? 'imagecreatetruecolor' : 'imagecreate';
        $func_imagecopy = function_exists('imagecopyresampled') ? 'imagecopyresampled' : 'imagecopyresized';
        $ni = $func_imagecreate($resize_width, $resize_height);
        if ($func_imagecreate == 'imagecreatetruecolor')
        {
            imagefill($ni, 0, 0, imagecolorallocate($ni, 255, 255, 255));
        }else{
            imagecolorallocate($ni, 255, 255, 255);
        }
        $func_imagecopy($ni, $im, 0, 0, 0, 0, $resize_width, $resize_height, $src_width, $src_height);


        switch($data[2]){
            case 1:
                imagegif($ni, $dst );
            case 2:
                imagejpeg($ni, $dst, $quality);
            case 3:
                imagepng($ni, $dst);
        }

        // 释放内存
        imagedestroy($ni);
        unset($data);

        return is_file($dst) ? true : false;
        	


    }

}