<?php

class PeopleSearchModel extends DkModel {

    private $result = array('total' => 0);
    
    private $search_util = null;
    
    private $solr_redis = null;
    
    private $read_redis = null;

    public function __initialize() 
    {
        $this->search_util = load_class('SearchUtil', 'libraries', 'DK_');
        
        $this->init_solr();
        
        $this->solr_redis = $this->solr->getSolr('relation');
        
    }

    /**
     * 获取相互关注
     * 
     * Enter description here ...
     * @param int $user_id 当前用户的ID
     * @param string $keyword 搜索的关键词
     * @param int $current_page 搜索的页,初始值为1
     * @param int $limit 显示的条数,初始值为27
     */
    public function getFollowingUserEachOther($user_id=null, $keyword=null, $current_page=1, $limit=27) 
    {//DONE
        $offset = ($current_page - 1) * $limit;
        
        $query = 'category:together_following AND user_id:' . $user_id;

        if ($this->isBackWithEmpty($keyword))   return $this->getResultJSON();

        if (($keyword = trim($keyword)) != null) $query .= $this->  getKeyWordString ( $keyword );

        $params['fl'] = 'id,name,dkcode,type';
        
        $params['sort'] = "score desc,createTime desc";

        $return = $this->solr->query($this->solr_redis, $query, $offset, $limit, $params); 
        
        $return = $return ? $return : $this ->solr->getEmptyJSON();
        
        return json_encode($this->search_util->formatObject($return));
    }

    /**
     * 我关注的人
     * 
     * Enter description here ...
     * @param int $user_id 当前用户的ID
     * @param string $keyword 搜索的关键词
     * @param int $current_page 搜索的页,初始值为1
     * @param int $limit 显示的条数,初始值为27
     */
    public function getFollowingReturnJSON($user_id = null, $keyword=null, $current_page=1, $limit=27) 
    {//DONE
        $offset = ($current_page - 1) * $limit;

        $query = 'category:following AND user_id:' . $user_id;

        if ($this->isBackWithEmpty($keyword))    return $this->getResultJSON();

        if (($keyword = trim($keyword)) != null) $query .= $this->getKeyWordString ( $keyword );
        
        $params['fl'] = 'id,name,dkcode,type';
        
        $params['sort'] = "score desc,createTime desc";

        $return = $this->solr->query($this->solr_redis, $query, $offset, $limit, $params); 
 
        $return = $return ? $return : $this ->solr->getEmptyJSON();
        
        return json_encode($this->search_util->formatObject($return));
    }

    /**
     * 关注我的人（粉丝）
     * 
     * Enter description here ...
     * @param int $user_id 当前用户的ID
     * @param string $keyword 搜索的关键词
     * @param int $current_page 搜索的页,初始值为1
     * @param int $limit 显示的条数,初始值为27
     */
    public function getFollowersReturnJSON($user_id = null, $keyword=null, $current_page=1, $limit=27) 
    {//DONE
        $offset = ($current_page - 1) * $limit;

        $query = 'category:follower AND user_id:' . $user_id;

        if ($this->isBackWithEmpty($keyword))   return $this->getResultJSON();

        if (($keyword = trim($keyword)) != null) $query .= $this->  getKeyWordString ( $keyword );
        
        $params['fl'] = 'id,name,dkcode,type';
        
        $params['sort'] = "score desc,createTime desc";
        
        $return = $this->solr->query($this->solr_redis, $query, $offset, $limit, $params); 

        $return = $return ? $return : $this ->solr->getEmptyJSON();
                
        return json_encode($this->search_util->formatObject($return));
    }

    /**
     * 好友
     * 
     * Enter description here ...
     * @param int $user_id
     * @param string $keyword
     * @param int $offset
     * @param int $limit
     */
    public function getFriendsReturnJSON($user_id = null, $keyword=null, $current_page=1, $limit=27) 
    {//DONE
        $offset = ($current_page - 1) * $limit;
        
        $query = 'category:friend AND user_id:' . $user_id;
        
        if ($this->isBackWithEmpty($keyword))    return $this->getResultJSON();

        if (($keyword = trim($keyword)) != null) $query .= $this->  getKeyWordString ( $keyword );

        $params['fl'] = 'id,name,dkcode,type';
        
        $params['sort'] = "score desc,createTime desc";
        
        $return = $this->solr->query($this->solr_redis, $query, $offset, $limit, $params); 

        $return = $return ? $return : $this ->solr->getEmptyJSON();
                
        return json_encode($this->search_util->formatObject($return));
    }
    
    /**
     *  问答获取好友接口
     * 
     * @param type $user_id
     * @param type $keyword
     * @param type $current_page
     * @param type $limit
     * @return type 
     */
    public function getFriendsReturnArray($user_id = null, $keyword=null, $current_page=1, $limit=27)
    {
        $offset = ($current_page - 1) * $limit;
        
        $query = 'category:friend AND user_id:' . $user_id;
        
        if ($this->isBackWithEmpty($keyword))    return $this->solr->getEmptyJSON();

        if (($keyword = trim($keyword)) != null) $query .= $this->  getKeyWordString ( $keyword );

        $params['fl'] = 'id,name,dkcode,type';
        
        $params['sort'] = "score desc,createTime desc";

        $return = $this->solr->query($this->solr_redis, $query, $offset, $limit, $params); 

        $return = $return ? $return : $this ->solr->getEmptyJSON();
        
        return array_map(function($val){return (array)$val;}, $return->response->docs);
    }

    /**
     * 添加关注
     * 
     * Enter description here ...
     * @param array $user_data 当前用户信息
     * @param array $following_data 关注用户信息
     */
    public function addFollowing($user_data=array(), $following_data=array(), $is_together_following = false) {//DONE
        $document = array();
        $user_data_name_spell = $this->solr->chinese2Pinyin($user_data["name"]);
        $following_data_name_spell = $this ->solr->chinese2Pinyin($following_data["name"]);
        $document[0]['unique_id'] = 'following_' . $user_data['id'] . '_' . $following_data['id'];
        $document[1]['unique_id'] = 'follower_' . $following_data['id'] . '_' . $user_data['id'];
        $document[0]['fullspell'] = 
        $document[0]['name'] = $following_data['name'];
        $document[1]['name'] = $user_data['name'];
        $document[0]['dkcode'] = $following_data['dkcode'];
        $document[1]['dkcode'] = $user_data['dkcode'];
        $document[0]['id'] = $following_data['id'];
        $document[1]['id'] = $user_data['id'];
        $document[0]['user_id'] = $user_data['id'];
        $document[1]['user_id'] = $following_data['id'];
        $document[0]['category'] = 'following';
        $document[1]['category'] = 'follower';
        $document[0]['type'] = 0;
        $document[1]['type'] = 0;
        $document[0]["fullspell"] = $following_data_name_spell;
        $document[1]["fullspell"] = $user_data_name_spell;
        $document[0]['createTime'] = (int) $user_data['time'];
        $document[1]['createTime'] = (int) $user_data['time'];
        if ($is_together_following) {//如果是相互关注,则添加相互关注的信息
            $document[2]['unique_id'] = 'together_following_' . $user_data['id'] . '_' . $following_data['id'];
            $document[3]['unique_id'] = 'together_following_' . $following_data['id'] . '_' . $user_data['id'];
            $document[2]['name'] = $following_data['name'];
            $document[3]['name'] = $user_data['name'];
            $document[2]['dkcode'] = $following_data['dkcode'];
            $document[3]['dkcode'] = $user_data['dkcode'];
            $document[2]['id'] = $following_data['id'];
            $document[3]['id'] = $user_data['id'];
            $document[2]['user_id'] = $user_data['id'];
            $document[3]['user_id'] = $following_data['id'];
            $document[2]['category'] = 'together_following';
            $document[3]['category'] = 'together_following';
            $document[2]['type'] = 0;
            $document[3]['type'] = 0;
            $document[2]["fullspell"] = $following_data_name_spell;
            $document[3]["fullspell"] = $user_data_name_spell;
            //相互关注的时间 = 双方的关注时间相加
            $document[2]['createTime'] = (int) ($user_data['time']);
            $document[3]['createTime'] = (int) ($user_data['time']);
        }

        return $this->solr->addDocs($document, $this->solr_redis);
    }

    /**
     * 删除关注
     * 
     * Enter description here ...
     * @param string $user_id 当前用户ID
     * @param string $following_id 删除关注ID
     */
    public function deleteFollowing($user_id=null, $following_id=null) {//DONE
        $user_id = intval($user_id);
        $following_id = intval($following_id);
        if ($user_id != null && $following_id != null) {
            $unique_id = 'unique_id:following_' . $user_id . '_' . $following_id;
            $unique_id.= ' OR unique_id:follower_' . $following_id . '_' . $user_id;
            $unique_id.= ' OR unique_id:together_following_' . $following_id . '_' . $user_id;
            $unique_id.= ' OR unique_id:together_following_' . $user_id . '_' . $following_id;
            return $this->solr->deleteByQuery($unique_id, $this->solr_redis);
        }
        return false;
    }

    /**
     * 成为朋友
     * 
     * Enter description here ...
     * @param array $user_data 当前用户信息
     * @param array $friend_data 成功朋友信息
     */
    public function makeFriendWithSomeone(array $user_data, array $friend_data) {
        $document = array();
        $document[0]['unique_id'] = 'friend_' . $user_data['id'] . '_' . $friend_data['id'];
        $document[1]['unique_id'] = 'friend_' . $friend_data['id'] . '_' . $user_data['id'];
        $document[0]['name'] = $friend_data['name'];
        $document[1]['name'] = $user_data['name'];
        $document[0]['dkcode'] = $friend_data['dkcode'];
        $document[1]['dkcode'] = $user_data['dkcode'];
        $document[0]['id'] = $friend_data['id'];
        $document[1]['id'] = $user_data['id'];
        $document[0]['user_id'] = $user_data['id'];
        $document[1]['user_id'] = $friend_data['id'];
        $document[0]['category'] = 'friend';
        $document[1]['category'] = 'friend';
        $document[0]['type'] = 0;
        $document[1]['type'] = 0;
        $document[0]["fullspell"] = $this ->solr->chinese2Pinyin($friend_data["name"]);
        $document[1]["fullspell"] = $this ->solr->chinese2Pinyin($user_data["name"]);
        $document[0]['createTime'] = (int) $user_data['frd_time'];
        $document[1]['createTime'] = (int) $user_data['frd_time'];

        return $this->solr->addDocs($document, $this->solr_redis);
    }

    /**
     * 删除朋友
     * 
     * Enter description here ...
     * @param string $user_id 当前用户ID
     * @param string $friend_id 取消朋友ID
     */
    public function deleteFriendById($user_id=null, $friend_id=null) {//DONE
        $user_id = intval($user_id);
        $friend_id = intval($friend_id);
        if ($user_id != null && $friend_id != null) {
            $unique_id = 'unique_id:friend_' . $user_id . '_' . $friend_id;
            $unique_id.=' OR unique_id:friend_' . $friend_id . '_' . $user_id;
            return $this->solr->deleteByQuery($unique_id, $this->solr_redis);
        }
        return false;
    }

    /**
     * 隐藏朋友
     * 
     * Enter description here ...
     * @param array $user_data 用户的相关信息
     * @param array $friend_data 朋友的相关信息
     */
    public function hideFriend(array $user_data, array $friend_data) {//DONE
        return $this->switchFriendHide($user_data, $friend_data);
    }

    /**
     * 隐藏关注
     * 
     * Enter description here ...
     * @param array $user_data 用户的相关信息
     * @param array $following_data 关注对象的相关信息
     * @param boolean $is_together_following 是否为相互关注
     * @param boolean $is_friend 是否为朋友
     */
    public function hideFollowing($user_data = array(), $following_data = array(), $is_together_following = false, $is_friend= false) {//DONE
        return $this->switchFollowingHide($user_data, $following_data, 1, $is_together_following, $is_friend);
    }

    /**
     * 取消隐藏关注
     * 
     * Enter description here ...
     * @param array $user_data 用户的相关信息
     * @param array $following_data 关注对象的相关信息
     * @param boolean $is_together_following 是否为相互关注
     */
    public function unHideFollowing(array $user_data, array $following_data, $is_together_following = false) {//DONE
        return $this->switchFollowingHide($user_data, $following_data, 0, $is_together_following);
    }

    /**
     * 取消隐藏朋友
     * 
     * Enter description here ...
     * @param array $user_data 用户的相关信息
     * @param array $friend_data 朋友的相关信息
     */
    public function unHideFriend(array $user_data, array $friend_data) {//DONE
        return $this->switchFriendHide($user_data, $friend_data, 0);
    }

    public function switchFollowingHide(array $user_data, array $following_data, $type = 1, $is_together_following=false, $is_friend=false) {
        $document = array();
        $following_data_name_spell = $this ->solr->chinese2Pinyin($following_data["name"]);
        $document[0]['unique_id'] = 'following_' . $user_data['id'] . '_' . $following_data['id'];
        $document[0]['name'] = $following_data['name'];
        $document[0]['dkcode'] = $following_data['dkcode'];
        $document[0]['id'] = $following_data['id'];
        $document[0]['user_id'] = $user_data['id'];
        $document[0]['type'] = $type;
        $document[0]['category'] = 'following';
        //操作者的时间
        $document[0]["fullspell"] = $following_data_name_spell;
        $document[0]['createTime'] = (int) $user_data['time'];
        if ($is_together_following) { //如果是相互关注,则隐藏相互关注的信息
            $document[1]['category'] = 'together_following';
            $document[1]['unique_id'] = 'together_following_' . $user_data['id'] . '_' . $following_data['id'];
            $document[1]['name'] = $following_data['name'];
            $document[1]['dkcode'] = $following_data['dkcode'];
            $document[1]['id'] = $following_data['id'];
            $document[1]['user_id'] = $user_data['id'];
            $document[1]['type'] = $type;
            $document[1]["fullspell"] = $following_data_name_spell;
            //相互关注的时间,把操作对象与被关注对象的时间相加
            $following_time = (int) $following_data['time'];
            $user_time = (int) $user_data['time'];
            $document[1]['createTime'] = $following_time > $user_time ? $following_time : $user_time;
        }
        //此操作 只在隐藏关注的时候才可能有
        if ($is_friend) {
            $document[2]['category'] = 'friend';
            $document[2]['unique_id'] = 'friend_' . $user_data['id'] . '_' . $following_data['id'];
            $document[2]['name'] = $following_data['name'];
            $document[2]['dkcode'] = $following_data['dkcode'];
            $document[2]['id'] = $following_data['id'];
            $document[2]['user_id'] = $user_data['id'];
            $document[2]['type'] = $type;
            $document[2]["fullspell"] = $following_data_name_spell;
            //成为朋友的时间
            $document[2]['createTime'] = (int) $user_data['frd_time'];
        }

        return $this->solr->addDocs($document, $this->solr_redis);
    }

    public function switchFriendHide($user_data, $friend_data, $type=1) {
        $document = array();
        $document['unique_id'] = 'friend_' . $user_data['id'] . '_' . $friend_data['id'];
        $document['name'] = $friend_data['name'];
        $document['dkcode'] = $friend_data['dkcode'];
        $document['id'] = $friend_data['id'];
        $document['user_id'] = $user_data['id'];
        $document['category'] = 'friend';
        $document['type'] = $type;
        $document["fullspell"] = $this ->solr->chinese2Pinyin($friend_data["name"]);
        //成为朋友的时间
        $document['createTime'] = (int) $user_data['frd_time'];

        return $this->solr->addDoc($document, $this->solr_redis);
    }
    
    private function isBackWithEmpty( $keyword )
    {
             $regex = "#[\(\)\[\]\*\?\{\}\+\-\"\*\?\~\^\|\&!\\:]#"; 
             
             return preg_match($regex, $keyword);
    }
    
    private function getKeyWordString( $keyword )
    {
        $keyword = strtolower(preg_replace("#\\s+#", "*" , $keyword));
        
        $english_regex = "#[\\w\\*]+#";
        
        $query_string = " AND ";
        
        if (  preg_match ( $english_regex, $keyword ))  $query_string .="((name:".$keyword." OR name:".$keyword."*)^10 OR (fullspell:".$keyword." OR fullspell:".$keyword."*)^5)";
                
        else    $query_string .="(name:".$keyword." OR name:".$keyword."*)";

        return $query_string;
    }

    private function getResultJSON() {
        return json_encode($this->result);
    }

}