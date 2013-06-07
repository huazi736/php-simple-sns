<?php
/**
 * 相册照片model
 *
 * @author        weijian
 * @version       $Id: albummodel.php 28196 2012-06-15 11:07:45Z guzb $
 */
class AlbumModel extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
	/**
     * 相册列表
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param array $params 参数列表
     */
	public function getAlbumList($params)
	{
	    // @todo 加入缓存
	    $return = array();
	    $list_sql = "SELECT id, name, uid, dateline, last_dateline, photo_count, cover_id, a_type, a_address, is_delete, discription, object_type, object_content FROM ".USER_ALBUM;
        $where_sql = " WHERE is_delete = 1";
        //用户
	    if(isset($params['uid']) && $params['uid']){
	        if(is_array($params['uid'])){
                $where_sql .= " AND uid in ('".implode("','", $uid_list)."')";
            }else{
    	        $where_sql .= " AND uid = '{$params['uid']}'";
            }
	    }
		//相册id
	    if(isset($params['id']) && $params['id']){
	        $params['id'] = intval($params['id']);
	        $where_sql .= " AND id = '{$params['id']}'";
	    }
	    
	    //网页相册编号
	    if(isset($params['web_id'])){
	        $params['web_id'] = intval($params['web_id']);
	    	$where_sql .= " AND web_id = '{$params['web_id']}'";
	    }
	    
	    //相册类型
	    if(isset($params['a_type']) && is_numeric($params['a_type'])){
	        $params['a_type'] = intval($params['a_type']);
	        $where_sql .= " AND a_type = '{$params['a_type']}'";
	    }
        //删除
	    if(isset($params['is_delete']) && $params['is_delete']){
	        $where_sql .= " AND is_delete = '".intval($params['is_delete'])."'";
	    }
	    //创建时间
	    if(isset($params['start_dateline']) && $params['start_dateline']){
	        $where_sql .= " AND dateline >= '".intval($params['start_dateline'])."'";
	    }
        if(isset($params['end_dateline']) && $params['end_dateline']){
            $where_sql .= " AND dateline <= '".intval($params['end_dateline'])."'";
        }
        
		//权限
    	if(isset($params['permissionType']) && is_numeric($params['permissionType'])){
            $where_sql .= " AND object_type = '".intval($params['permissionType'])."'";
        }
        //权限
        if($params['uid'] != UID){
            $is_fans = getSocial('fans', UID, $params['uid']) ? 1 : 0;
            $is_friend = getSocial('friend', UID, $params['uid']) ? 1 : 0;
            $is_myself = $params['uid'] == UID ? 1 : 0;
            $where_sql .= " AND (
                CASE 
                    WHEN object_type='1' THEN 1
                    WHEN object_type='3' THEN '{$is_fans}'
                    WHEN object_type='4' THEN '{$is_friend}'
                    WHEN (object_type='-1' AND object_content LIKE '%".UID."%') THEN 1  
                    WHEN object_type='8' THEN '{$is_myself}'
                ELSE 0  
                END 
                ) = '1'
            ";
        }
        //计算数量
        if(isset($params['total']) && $params['total']){
            // @todo 加入缓存
            $num_sql = "SELECT COUNT(*) AS num FROM ".USER_ALBUM. $where_sql;
            $num_res = $this->db->query($num_sql);
            $num_row = $num_res->row_array();
            $return['total_num'] = $num_row['num'];
            if($return['total_num'] == 0){
                return $return;
            }
        }
        //排序$params['order'] = 'sort_asc';
        if(isset($params['order']) && in_array($params['order'], array('sort_asc', 'sort_desc', 'date_asc', 'date_desc', 'id_asc', 'id_desc'))){
            switch($params['order']){
                case 'sort_asc':
                    $where_sql .= " ORDER BY a_sort ASC";
                    break;
                case 'sort_desc':
                    $where_sql .= " ORDER BY a_sort DESC";
                    break;
                case 'date_asc':
                    $where_sql .= " ORDER BY dateline ASC";
                    break;
                case 'date_desc':
                    $where_sql .= " ORDER BY dateline DESC";
                    break;
                case 'id_asc':
                    $where_sql .= " ORDER BY id ASC";
                    break;
                case 'id_desc':
                    $where_sql .= " ORDER BY id DESC";
                    break;
            }
        }else{
            $where_sql .= " ORDER BY a_sort DESC";
        }
	    //分页
	    $params['pagesize'] = isset($params['pagesize']) ? intval($params['pagesize']) : 1;
	    $params['page'] = isset($params['page']) ? intval($params['page']) : 0;
        if($params['pagesize'] > 0 && $params['page'] >= 1){
            $params['pagesize'] = intval($params['pagesize']);
    	    //如果每页显示数为0，则表示取全部内容
            $params['page'] = intval($params['page']);
            $where_sql .= " LIMIT ".(($params['page']-1) * $params['pagesize']).",".$params['pagesize'];
        }
        $res = $this->db->query($list_sql.$where_sql);
        $res = $res->result_array();
        //设置相册图片路径
		foreach($res as $k => $v){
    		
    		//默认相册封面地址
    		if($res[$k]['cover_id']) {
    			$photolist = $this->getPhotoInfo($res[$k]['cover_id']);
    			if(isset($photolist['img_f'])){
    				$res[$k]['album_cover'] = $photolist['img_f'];
    			}else{
    				$res[$k]['album_cover'] = MISC_ROOT.'img/default/album_default.png';
    			}
    		} else {
    			//默认地址
    			$res[$k]['album_cover'] = MISC_ROOT.'img/default/album_default.png';
    		}

    		$res[$k]['dateline'] = date('Y-m-d',$v['dateline']);
    		$res[$k]['temp_dateline'] = $v['last_dateline'];
    		$res[$k]['last_dateline'] = date('Y-m-d',$v['last_dateline']);
        }
	    $return['list'] = $res;
        $return['page'] = $params['page'];
        $return['pagesize'] = $params['pagesize'];
        
        return $return;
	}
    
	/**
     * 照片列表
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param array $params 参数列表
     */
    public function getPhotoList($params)
    {	
    	
        // @todo 加入缓存
        $return = array();
        $list_sql = "SELECT id, name, uid, dateline, aid, type, groupname, filename, size, dateline, description, notes FROM ".USER_PHOTO;
        $where_sql = " WHERE 1=1";
        //主键
        if(isset($params['id']) && $params['id']){
            if(is_array($params['id'])){
                $where_sql .= " AND id in ('".implode("','", $params['id'])."')";
            }else{
                $where_sql .= " AND id = ".$this->db->escape(intval($params['id']))."";
            }
        }
        //用户
        if(isset($params['uid']) && $params['uid']){
	        if(is_array($params['uid'])){
                $where_sql .= " AND uid in ('".implode("','", $uid_list)."')";
            }else{
    	        //检查用户是否被删除
    	        $where_sql .= " AND uid = '{$params['uid']}'";
            }
	    }
        //相册
        if(isset($params['aid']) && $params['aid']){
            $where_sql .= " AND aid = ".$this->db->escape(intval($params['aid']))."";
        }
        //是否有描述，编辑时用
        if(isset($params['is_description'])){
            if($params['is_description'] == 1){
                $where_sql .= " AND description <> ''";
            }else{
                $where_sql .= " AND description = ''";
            }
        }
        //是否有评论
        if(isset($params['is_comment']) && is_numeric($params['is_comment'])){
            $where_sql .= " AND is_comment = '".intval($params['is_comment'])."'";
        }
        //删除
        if(isset($params['is_delete']) && is_numeric($params['is_delete'])){
            $where_sql .= " AND is_delete = '".intval($params['is_delete'])."'";
        }
        //创建时间
        if(isset($params['start_dateline']) && $params['start_dateline']){
            $where_sql .= " AND dateline >= '".intval($params['start_dateline'])."'";
        }
        if(isset($params['end_dateline']) && $params['end_dateline']){
            $where_sql .= " AND dateline <= '".intval($params['end_dateline'])."'";
        }
        
        
        //计算数量
        
        if(isset($params['total']) && $params['total']){
            // @todo 加入缓存
            $num_sql = "SELECT COUNT(*) AS num FROM " . USER_PHOTO . " ". $where_sql;
            $num_res = $this->db->query($num_sql);
            $num_row = $num_res->row_array();
            $return['data'] = array();
            $return['total_num'] = $num_row['num'];
            //没有数据记录
            if($return['total_num'] == 0){
            	$return['page'] = 1;
            	$return['pagesize'] = $params['pagesize'];
                return $return;
            }
        }
        $list_sql .= $where_sql;
        //排序
        
        //$params['order'] = 'sort_asc';
        
        if(isset($params['order']) && in_array($params['order'], array('sort_asc', 'sort_desc', 'date_asc', 'date_desc', 'id_asc', 'id_desc'))){
            switch($params['order']){
                case 'sort_asc':
                    $list_sql .= " ORDER BY p_sort ASC";
                    break;
                case 'sort_desc':
                    $list_sql .= " ORDER BY p_sort DESC";
                    break;
                case 'date_asc':
                    $list_sql .= " ORDER BY dateline ASC";
                    break;
                case 'date_desc':
                    $list_sql .= " ORDER BY dateline DESC";
                    break;
                case 'id_asc':
                    $list_sql .= " ORDER BY id ASC";
                    break;
                case 'id_desc':
                    $list_sql .= " ORDER BY id DESC";
                    break;
            }
        }else{
            $list_sql .= " ORDER BY p_sort DESC";
        }
        //分页
	    $params['pagesize'] = isset($params['pagesize']) ? intval($params['pagesize']) : 1;
	    $params['page'] = isset($params['page']) ? intval($params['page']) : 0;
        if($params['pagesize'] > 0 && $params['page'] >= 1){
            $params['pagesize'] = intval($params['pagesize']);
    	    //如果每页显示数为0，则表示取全部内容
            $params['page'] = intval($params['page']);
            $list_sql .= " LIMIT ".(($params['page']-1) * $params['pagesize']).",".$params['pagesize'];
        }
        $res = $this->db->query($list_sql);
        $lists = $res->result_array();
     	$new_list = array();
     	$this->config->load('album');
		$photo_size_arr = $this->config->item('thumb_pic_sizes');
		$romote_img_url = $this->config->item('romote_img_url');
        foreach($lists as $k => $v){
        	$lists[$k]['temptimeamp'] = $v['dateline'];
            $notes = !empty($v['notes']) ? json_decode($v['notes'], true) : array();
            //得到大中小图片地址
        	foreach($photo_size_arr['size'] as $key=>$size) {
        		if(!empty($notes) && array_key_exists($key, (array)$notes)){
        			$lists[$k]['img_'.$key] = getImgPath($v['groupname'], $v['filename'], $v['type'], $key);
            	}else{
            		$lists[$k]['img_'.$key] = getImgRomotePath($v['filename'], $key, $v['type'], date('Ymd', $v['dateline']), $romote_img_url);
            	}
        	}
        	$lists[$k]['dateline'] = date('Y-m-d',$v['dateline']);
            $new_list[$v['id']] = $lists[$k];
        }
        $return['data'] = $lists;
        $return['page'] = $params['page'];
        $return['pagesize'] = $params['pagesize'];
        return $return;
    }
    
	/**
     * 新增相册
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $uid 使用者uid
     * @param $aname 相册名称
     * 杨顺军 新加$is_default 参数，判断是否为默认相册，为默认相册时 is_delete字段为2
     */
    public function addAlbum($uid = null,$aname = null,$a_type = 0,$txtaddr = null,$txtdesc = null, $permission=1){
        if(!$uid || !$aname){
            return false;
        }
        
    	//截取140个字符
   		if(strlen($txtdesc) > 140){
   			$txtdesc = substr($txtdesc, 0, 140);
   		}
        
        if($a_type > 0) {
            //如果为默认相册，检查相册是否存在
            $sql = "select id from ".USER_ALBUM." where uid = '{$uid}' and a_type = '{$a_type}' and web_id = 0 limit 1";
            $res = $this->db->query($sql);
            $row = $res->row_array($res);
            if(isset($row['id'])){
                return $row['id'];
            }
        }

        $u_sql = sprintf("SELECT max(a_sort) AS max_sort FROM ".USER_ALBUM." WHERE uid = '%s' AND web_id = 0 LIMIT 1 ",$uid);
        $u_query = $this->db->query($u_sql);
        $u_lists = $u_query->result_array();
        if(count($u_lists)){
            $a_sort = $u_lists[0]['max_sort'] + 1;
        }else{
            $a_sort = 1;
        }
		
        $data = array('name' => $aname,'uid' => $uid,'dateline' => time(),'last_dateline' => time(),'a_sort' => $a_sort,'a_type' => $a_type,'a_address' => $txtaddr,'discription' => $txtdesc);
        //增加默认相册权限功能
        if($a_type > 0){
            switch ($a_type){
                case 3:
                    $access_type = 8;
                    $access_content = '-1';
                    break;
                default:
                    $access_type = 1;
                    $access_content = '-1';
            }
        }else{
        		if(is_numeric($permission) && $permission < 9){
					$access_type = $permission;
					$access_content = '-1';
				}else{
					$access_type = -1;
					if(empty($permission)){
					$permission = '0';
					}
					$access_content = $permission;
			}
        }
        $data['object_type'] = $access_type;
        $data['object_content'] = $permission;
        $this->db->insert(USER_ALBUM, $data);
        $aid = $this->db->insert_id();
        $insert_res = $this->db->affected_rows();
        if(!$insert_res){
            return false;
        }
        $aid = $this->db->insert_id();
        
        //加积分
		service('credit')->album();
		
        return $aid;
    }

    
 	/**
     * 某个照片信息
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $pid 照片id
     */
    public function getPhotoInfo($pid = null){
        if(!$pid){
            return false;
        }
        
        $pid = intval($pid);
		
        $sql = sprintf("select id as pid, name as pname, description, dateline, groupname, filename, type, aid, uid, is_comment, size, notes
        				from ".USER_PHOTO." where id = '%s' and is_delete = '1' limit 1 ",$pid);
        $query = $this->db->query($sql);
        $listpid = $query->row_array();
        if(!$listpid) {
        	return false;
        }
        $sql = sprintf("select name AS aname,photo_count, object_type from ".USER_ALBUM." where id = '%s' and is_delete = '1' and web_id = 0 limit 1", $listpid['aid']);
        $query = $this->db->query($sql);
        $listaid = $query->row_array();
        $lists = array_merge($listpid, $listaid);
        
        if(!$lists){
            return false;
        }
		
		$this->config->load('album');
		$photo_size_arr = $this->config->item('thumb_pic_sizes');
		$romote_img_url = $this->config->item('romote_img_url');
		$notes = !empty($listpid['notes']) ? json_decode($listpid['notes'], true) : array();
        	
    	foreach($photo_size_arr['size'] as $key=>$size) {
    		if(!empty($notes) && array_key_exists($key, $notes)){
            	$lists['img_'.$key] = getImgPath($lists['groupname'], $lists['filename'], $lists['type'], $key);
            }else{
            	$lists['img_'.$key] = getImgRomotePath($lists['filename'], $key, $lists['type'], date('Ymd', $lists['dateline']), $romote_img_url);
            }
    		
    	}
    	
    	//取照片大小，原图地址
    	$thumperSize = json_decode($lists['notes'], true);
    	if(isset($thumperSize['self'])) {
    		$lists['img'] = getImgPath($lists['groupname'], $lists['filename'], $lists['type']);
    		$lists['thumperSize'] = $thumperSize['self'];
    	}else{
    		$lists['img'] = $lists['img_b'];
    		$lists['thumperSize'] = isset($thumperSize['b']) ? $thumperSize['b'] : array();
    	}
    	
    	$lists['timestamp'] = $lists['dateline'];
        $lists['dateline'] = date('Y-m-d',$lists['dateline']);
        return $lists;
    }
    
    /**
	  * 跳转到下一张照片ID
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $pid 照片ID
     */
	public function getDelphotoNext($pid = null){
		if(!$pid){
			return false;
		}
		$pid = intval($pid);
		//判断该照片是否存在
		$data = array('id','uid','aid','dateline');
 		$this->db->select($data);
		$this->db->where('id',$pid);
		$this->db->where('is_delete','1');
		$this->db->limit(1);
		$query = $this->db->get(USER_PHOTO);
		$lists = $query->result_array();
		if(!$lists){
			return false;
		}

		//下一张照片
		$n_sql = sprintf("SELECT id
                        FROM ".USER_PHOTO."
                        WHERE aid = '%s'
                        AND is_delete = '1' 
                        AND id <> '%s'
                        AND dateline <= '%d'
                        ORDER BY dateline DESC
                        LIMIT 1 ",$lists[0]['aid'],$pid,$lists[0]['dateline']);
		$n_query = $this->db->query($n_sql);
		$n_lists = $n_query->result_array();

		if(!$n_lists){
            //第一张照片
			$p_sql = sprintf("SELECT id
                        FROM ".USER_PHOTO."
                        WHERE aid = '%s'
                        AND is_delete = '1' 
                        AND id <> '%s'
                        AND dateline >= '%d'
                        ORDER BY dateline DESC
                        LIMIT 1 ",$lists[0]['aid'],$pid,$lists[0]['dateline']);

			$p_query = $this->db->query($p_sql);
			$p_lists = $p_query->result_array();

			if(!$p_lists){
				return false;
			}else{
				return $p_lists[0]['id'];
			}
		}else{
			return $n_lists[0]['id'];
		}
	}
    
    
	/**
     * 照片列表，取得上一张/下一张照片
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $pid 照片ID
     */
    public function getPhotoPrevNext($pid = null){
        if(!$pid){
            return false;
        }
		$pid = intval($pid);
		
        //判断该照片是否存在
        $data = array('id','uid','aid','dateline','p_sort');
        $this->db->select($data);
        $this->db->where('id',$pid);
        //$this->db->where('is_delete','1');
        $this->db->limit(1);
        $query = $this->db->get(USER_PHOTO);
        $lists = $query->result_array();
        if(!$lists){
            return false;
        }
        $next_pid = '';
        $prev_pid = '';

        //下一张照片
        $next_sql = sprintf("SELECT id
                    FROM ".USER_PHOTO."
                    WHERE aid = '%s'
                    AND is_delete = '1' 
                    AND id <> '%s'
                    AND p_sort < '%d'
                    ORDER BY p_sort DESC
                    LIMIT 1 ",$lists[0]['aid'],$pid,$lists[0]['p_sort']);
        $next_query = $this->db->query($next_sql);
        $next_lists = $next_query->result_array();
        if($next_lists){
            $next_pid = $next_lists[0]['id'];
        }

        //上一张照片
        $prev_sql = sprintf("SELECT id
                    FROM ".USER_PHOTO."
                    WHERE aid = '%s'
                    AND is_delete = '1' 
                    AND id <> '%s'
                    AND p_sort > '%d'
                    ORDER BY p_sort DESC
                    LIMIT 1 ",$lists[0]['aid'],$pid,$lists[0]['p_sort']);
        $prev_query = $this->db->query($prev_sql);
        $prev_lists = $prev_query->result_array();
        if($prev_lists){
            $prev_pid = $prev_lists[0]['id'];
        }

        //计算当前照片所属第几张
        $num_sql = sprintf("SELECT count(*) AS z
                    FROM ".USER_PHOTO."
                    WHERE aid = '%s'
                    AND is_delete = '1' 
                    AND p_sort >= '%d'
                    LIMIT 1 ",$lists[0]['aid'],$lists[0]['p_sort']);
        $num_query = $this->db->query($num_sql);
        $num_lists = $num_query->result_array();
        if($num_lists){
            $num = $num_lists[0]['z'];
        }else{
            $num = 0;
        }
        
        return array('prev_pid' => $prev_pid,'next_pid' => $next_pid,'num' => $num);
    }
    
    
	/**
     * 相册排序
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $uid 用户ID
     * @param moverA_id 出发点
     * @param moverB_id 到达点
     */
    public function orderAlbum($uid = null,$moverA_id = null,$moverB_id = null){
        if(!$uid || !$moverA_id || !$moverB_id){
            return false;
        }
        
        //取得当前圈子
        $moverA_id = mysql_real_escape_string($moverA_id);
        $moverB_id = mysql_real_escape_string($moverB_id);
        $A_sql = sprintf("SELECT id,a_sort AS sort_num FROM ".USER_ALBUM." WHERE id = '%s' and web_id = 0 AND is_delete = '1' LIMIT 1 ",$moverA_id);
        $B_sql = sprintf("SELECT id,a_sort AS sort_num FROM ".USER_ALBUM." WHERE id = '%s' and web_id = 0 AND is_delete = '1' LIMIT 1 ",$moverB_id);
        $moverA_sort_num = $this->getSortNum($A_sql);
        $moverB_sort_num = $this->getSortNum($B_sql);
        
        if($moverA_sort_num < $moverB_sort_num){
            $sql_1 = sprintf("UPDATE ".USER_ALBUM." 
                               SET a_sort = a_sort - 1
                               WHERE uid = '%s' 
                               AND web_id = 0 
                               AND a_sort > '%d'
                               AND a_sort <= '%d' ",$uid,$moverA_sort_num,$moverB_sort_num);
            $update_res_1 = $this->db->query($sql_1);
            if(!$update_res_1){
                return false;
            }
            
        }else{
            $sql_1 = sprintf("UPDATE ".USER_ALBUM." 
                               SET a_sort = a_sort + 1
                               WHERE uid = '%s' 
                               AND web_id = 0 
                               AND a_sort >= '%d'
                               AND a_sort < '%d' ",$uid,$moverB_sort_num,$moverA_sort_num);
            $update_res_1 = $this->db->query($sql_1);
            if(!$update_res_1){
                return false;
            }
          
            
        }
		$sql_2 = sprintf("UPDATE ".USER_ALBUM." 
                           SET a_sort = '%d'
                           WHERE id = '%s' ",$moverB_sort_num,$moverA_id);
        
        $update_res_2 = $this->db->query($sql_2);
        if(!$update_res_2){
            return false;
        }
        
        return true;
    }
    
	/**
     * 取得对象排序序号
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $object_sql 数据内容
     */
    public function getSortNum($object_sql = null){
        if(!$object_sql){
            return false;
        }

        $query = $this->db->query($object_sql);
        $lists = $query->result_array();
        if(!$lists){
            return false;
        }
        return $lists[0]['sort_num'];
    }
    
	/**
     * 照片排序
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $moverA_id 出发点
     * @param $moverB_id 到达点
     * @param $album_id 相册id
     */
    public function orderPhoto($moverA_id = null,$moverB_id = null,$album_id = null){
        if(!$moverA_id || !$moverB_id || !$album_id){
            return false;
        }

        $moverA_id = mysql_real_escape_string($moverA_id);
        $moverB_id = mysql_real_escape_string($moverB_id);
        $album_id = mysql_real_escape_string($album_id);
        $A_sql = sprintf("SELECT id,p_sort AS sort_num FROM ".USER_PHOTO." WHERE id = '%s' AND is_delete = '1' LIMIT 1 ",$moverA_id);
        $B_sql = sprintf("SELECT id,p_sort AS sort_num FROM ".USER_PHOTO." WHERE id = '%s' AND is_delete = '1' LIMIT 1 ",$moverB_id);
        $moverA_sort_num = $this->getSortNum($A_sql);
        $moverB_sort_num = $this->getSortNum($B_sql);

        if($moverA_sort_num < $moverB_sort_num){
            $sql_1 = sprintf("UPDATE ".USER_PHOTO." 
                               SET p_sort = p_sort - 1
                               WHERE aid = '%s' 
                               AND p_sort > '%d'
                               AND p_sort <= '%d' ",$album_id,$moverA_sort_num,$moverB_sort_num);
            $update_res_1 = $this->db->query($sql_1);
            if(!$update_res_1){
                return false;
            }
        }else{
            $sql_1 = sprintf("UPDATE ".USER_PHOTO." 
                               SET p_sort = p_sort + 1
                               WHERE aid = '%s' 
                               AND p_sort >= '%d'
                               AND p_sort < '%d' ",$album_id,$moverB_sort_num,$moverA_sort_num);
            $update_res_1 = $this->db->query($sql_1);
            if(!$update_res_1){
                return false;
            }
        }

        $sql_2 = sprintf("UPDATE ".USER_PHOTO." 
                           SET p_sort = '%d'
                           WHERE id = '%s' ",$moverB_sort_num,$moverA_id);
        $update_res_2 = $this->db->query($sql_2);
        if(!$update_res_2){
            return false;
        }
        
        return true;

    }
    
	/**
     * 编辑相册
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * 杨顺军修改 2012-03-13
     * 添加$a_type 参数，相册类型，默认为普通相册
     *
     * @access public
     * @param $aid 相册ID
     * @param $aname 相册名称
     * @param interger $a_type 相册类型
     */
    public function updateAlbum($aid = null,$uid = null,$update_data = null, $a_type = 0){
        if(!$aid || !$update_data || !$uid){
            return false;
        }
		$aid = intval($aid);
        $this->db->where('id', $aid);
        $this->db->where('uid', $uid);
        $this->db->where('a_type', $a_type);
        $update_res = $this->db->update(USER_ALBUM,$update_data);
        if(!$update_res){
            return false;
        }
        return true;
    }
    
    
	/**
     * 删除相册
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $aid 相册ID
     * @param $uid 用户ID
     */
    public function delAlbum($aid = null, $uid = null){
    	
    	if(!$aid || !$uid){
            return false;
        }
        $aid = intval($aid);
        //删除相册(is_delete标记为0)
        $update_data['is_delete'] = '0';
        $this->db->where('id',$aid);
        $this->db->where('uid',$uid);
        $this->db->where('a_type','0');
        $del_res = $this->db->update(USER_ALBUM,$update_data);
        if(!$del_res){
            return false;
        }
        
        $data = array('id');
        $this->db->select($data);
        $this->db->where('aid',$aid);
        $this->db->where('uid',$uid);
        $p_query = $this->db->get(USER_PHOTO);
        $p_lists = $p_query->result_array();

        if($p_lists){
            //删除该相册内的照片(is_delete标记为0)
            $p_data['is_delete'] = '0';
            $this->db->where('aid',$aid);
            $this->db->where('uid',$uid);
            $this->db->update(USER_PHOTO,$p_data);

        }
       //扣积分
		service('credit')->album(false);
        
        return true;
    }
    
    
/**
     * 删除照片
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $uid 使用者uid
     * @param $pid 照片ID array or integer
     */
    public function delPhoto($pid = null,$uid = null){
        if(!$uid || !$pid){
            return false;
        }
        //判断是否是批量删除，是，取第一个pid.
        if(is_array($pid)) {
        	$singlepid = $pid[0];
        } else {
        	$singlepid = $pid;
        }
        
    	//判断该照片是否存在
        $sql = "SELECT aid FROM ".USER_PHOTO."
                WHERE id = ? AND uid = ? AND is_delete = 1";
        $res = $this->db->query($sql, array($singlepid, $uid));
        $photo_info = $res->row_array();
        if(!$photo_info){
            return false;
        }
        
        $album_info = $this->getAlbums($uid, $photo_info['aid']);
        
		$p_data['is_delete'] = '0';
        if(is_array($pid)) {
        	//批量action(is_delete 标记为0)
        	//$where = 'id in ('.implode($pid, ',').')';
        	$this->db->where_in('id', $pid);
        	$p_res = $this->db->update(USER_PHOTO,$p_data);
        	//相册照片总数变更
	        $update_sql = sprintf("UPDATE ".USER_ALBUM." 
	                            SET photo_count = photo_count - ".count($pid)." 
	                            WHERE id = '%s' 
	                            LIMIT 1 ",$photo_info['aid']);
	        $this->db->query($update_sql);
	        if(in_array($album_info[0]['cover_id'], $pid)) {
	        	$this->resetAlbumCover($photo_info['aid'], $uid);
	        }
	        
	        //判断照片是否应用区封面照片
	        $data = array('id');
	        $this->db->select($data);
	        $this->db->where('is_maincover','1');
	        $this->db->where_in('id', $pid);
	        $query = $this->db->get(USER_PHOTO);
	        $select_result = $query->row_array();
	        if($select_result) {
	        	$this->deleteMainCover($select_result['id'], $uid);
	        }
	        
        } else {
	         //单张action(is_delete 标记为0)
	        $this->db->where('id',$pid);
	        $p_res = $this->db->update(USER_PHOTO,$p_data);
	        //相册照片总数变更
	        $update_sql = sprintf("UPDATE ".USER_ALBUM." 
	                            SET photo_count = photo_count - 1 
	                            WHERE id = '%s' 
	                            LIMIT 1 ",$photo_info['aid']);
	        $this->db->query($update_sql); 
	        
	        //判断是否为封面
        	$this->checkAlbumCover($photo_info['aid'], $pid, $uid);
        	
       		 //判断照片是否应用区封面照片
	        $data = array('id');
	        $this->db->select($data);
	        $this->db->where('is_maincover','1');
	        $this->db->where('id', $pid);
	        $query = $this->db->get(USER_PHOTO);
	        $select_result = $query->row_array();
	        if($select_result) {
	        	$this->deleteMainCover($select_result['id'], $uid);
	        }
        }
        
    	if(!$p_res){
    		return false;
	    }
 		
        //减积分
		service('credit')->album(false);
        return $photo_info['aid'];
    }
    
    
 	/**
     * 移动或删除照片时检查相册封面
     * 如果目标照片为相册封面，则重置相册封面
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param string $aid 相册编号
     * @param string $pid 照片编号
     */
    public function checkAlbumCover($aid, $pid, $uid)
    {	
    	$aid = intval($aid);
    	$pid = intval($pid);
        $album_info = $this->getAlbums($uid, $aid);
      //  echo $album_info[0]['cover_id'];
       // echo "<br>".$pid;
        if($album_info[0]['cover_id'] == $pid){
            $this->resetAlbumCover($aid, $uid);
        }
    }
    
    /**
     *批量上传照片
     * 
     * @author guzhongbin
     * @date 2012-04-16
     * 
     */
    
    public function addBathPhoto($uid = null,$aid = null,$pidAndDescs = null,$atype = 0, $photo_quality = 75, $dateline = null) {
    	if(!$uid || !$aid || !$pidAndDescs){
            return false;
        }
    	
        $aid = intval($aid);
        //为了图片以上传时顺序显示，逆转数组
        $pidAndDescs = array_reverse($pidAndDescs);
        $temp = end($pidAndDescs);
        $coverpid = $temp['picId'];
         //判断相册是否存在
        $data = array('id','cover_id', 'a_type');
        $this->db->select($data);
        $this->db->where('id',$aid);
        $this->db->where('is_delete','1');
     	$this->db->limit(1);
        $a_query = $this->db->get(USER_ALBUM);
        $a_lists = $a_query->result_array();
        
        if(!$a_lists){
            return false;
        }
        
    	//取得排序序号
    	$photoCount = count($pidAndDescs);
        $p_sql = sprintf("SELECT max(p_sort) AS max_sort FROM ".USER_PHOTO." WHERE aid = '%s' LIMIT 1 ",$aid);
        $p_query = $this->db->query($p_sql);
        $p_lists = $p_query->result_array();
        if(count($p_lists)){
            $p_sort = $p_lists[0]['max_sort'] + 1;
        }else{
            $p_sort = 1;
        }
       
    	foreach($pidAndDescs as $pidAndDesc) {
    		
    		$pidAndDesc['picDesc'] = htmlspecialchars($pidAndDesc['picDesc'], ENT_QUOTES);
        	//上传照片
	        $up_data = array('uid' => UID, 'aid' => $aid,'p_sort' => $p_sort,'is_delete' => 1, 'dateline' => $dateline, 'description' => $pidAndDesc['picDesc']);
	        
	        $this->db->where(array('id' => $pidAndDesc['picId']));
	        $this->db->update(USER_PHOTO, $up_data);
	        $res = $this->db->affected_rows();
	        if(!$res){
	            return false;
	        }
	        $p_sort ++;
		}
		
   		//相册照片数量+1
        $ab_sql = sprintf("UPDATE ".USER_ALBUM." 
                           SET photo_count = photo_count + ".$photoCount." 
                           ,last_dateline = '%d'
                           WHERE id = '%s' 
                           LIMIT 1 ",time(),$aid);
        $this->db->query($ab_sql);
        
         //如果是第一张照片,则添加相册封面
        if(!$a_lists[0]['cover_id']){
            $this->setAlbumCover($aid, $coverpid, $uid);
        }
        
		service('credit')->album();
        return true;
    }
    
    
	/**
     * 上传照片
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $uid 使用者uid
     * @param $aid 相册ID
     * @param $pid 照片ID	
     * @param $atype 相册类型
     * @param $photo_quality 图片保存质量
     * @param $dateline 时间戳
     * @param $coverpid 封面pid
     * @param $pt  array 文件信息
     */
    public function addPhoto($uid = null,$aid = null,$pid = null,$atype = 0, $photo_quality = 75, $dateline = null,$coverpid = null){
        if(!$uid || !$aid || !$pid){
            return false;
        } else if (!$dateline) {
        	//初始化$dateline
        	$dateline = time();
        }
        
     	if(!$coverpid) {
        	$coverpid = $pid;
        }
        
        //判断相册是否存在
        $data = array('id','cover_id');
        $this->db->select($data);
        $this->db->where('id',$aid);
        
        //杨顺军  2012-03-13 
        //添加当相册类型为第一次添加默认相册时，is_delete 为 2时处理添加照片
        if($atype == 0) {
        	$this->db->where('is_delete','1');
        	$album_delete_status = 1;
        } else {
        	$album_delete_status = 2;
        }
        	
        $this->db->limit(1);
        $a_query = $this->db->get(USER_ALBUM);
        $a_lists = $a_query->result_array();
        
        if(!$a_lists){
            return false;
        }

        //取得排序序号
        $p_sql = sprintf("SELECT max(p_sort) AS max_sort,filename,type FROM ".USER_PHOTO." WHERE aid = '%s' LIMIT 1 ",$aid);
        $p_query = $this->db->query($p_sql);
        $p_lists = $p_query->result_array();
        if(count($p_lists)){
            $p_sort = $p_lists[0]['max_sort'] + 1;
        }else{
            $p_sort = 1;
        }

        //上传照片
        $up_data = array('uid' => UID, 'aid' => $aid,'p_sort' => $p_sort,'is_delete' => 1, 'dateline' => $dateline);
        $this->db->where(array('id' => $pid));
        $this->db->update(USER_PHOTO, $up_data);
        $res = $this->db->affected_rows();
        if(!$res){
            return false;
        }

        //相册照片数量+1
        $ab_sql = sprintf("UPDATE ".USER_ALBUM." 
                           SET photo_count = photo_count + 1
                               ,last_dateline = '%d'
                           WHERE id = '%s' 
                           LIMIT 1 ",time(),$aid);
        $this->db->query($ab_sql);
        
         //如果是第一张照片,则添加相册封面
        if(!$a_lists[0]['cover_id']){
            $this->setAlbumCover($aid, $coverpid, UID);
        }
      	
        //加积分
		service('credit')->album(true, $uid);
        
        return true;
    }
    
	/**
     * 重置相册的封面
     * 如果有照片，则选择第一张照片，如果没有照片则为空
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param string $aid
     * @param string $uid
     * 
     */
    public function resetAlbumCover($aid, $uid, $clean = 0)
    {
        if(!$aid || !$uid){
            return false;
        }
        if($clean){
            $update_data['cover_id'] = '';
        }else{
            $photo_list = $this->getPhotoList(array(
                'aid'    =>    $aid,
                'uid'    =>    $uid,
                'order'    =>    'sort_desc',
                'is_delete'    =>    1,
                'pagesize'    =>    1,
            ));
            if(empty($photo_list['data'])){
                $update_data['cover_id'] = '';
            }else{
                $first = reset($photo_list['data']);
                $update_data['cover_id'] = $first['id'];
            }
        }
        $this->db->where('id',$aid);
        $update_res = $this->db->update(USER_ALBUM,$update_data);
        if(!$update_res){
            return false;
        }

        return true;    
    }
    
    
	/**
     * 取得相册简要列表
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $uid 用户uid
     * 
     */
    public function getAlbums($uid = null, $aid = null, $web_id = 0){
    	
    	$aid = intval($aid);
        $data = array('id','name', 'a_type', 'a_address', 'discription', 'cover_id', 'object_type', 'object_content');
        $this->db->select($data);
        //杨顺军   2012-03-12
        //查询某一个相册信息
        if(!empty($aid)) {
        	$this->db->where('id',$aid);
        }
        
    	if(!empty($uid)) {
        	$this->db->where('uid',$uid);
        }
        
        $this->db->where('is_delete','1');
        $this->db->where('web_id', $web_id);
        $this->db->order_by('a_sort', 'asc');
        $query = $this->db->get(USER_ALBUM);
        $lists = $query->result_array();

        if(!$lists){
            return false;
        }

        return $lists;
    }
    
	 /**
     * 编辑照片描述信息
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $pid 照片ID
     * @param $pdesc 照片描述信息
     * @param $uid 用户ID
     */
    public function updatePhotoDesc($pid = null, $pdesc = null, $uid = null){
        if(!$pid || !$uid){
            return false;
        }
        $pid = intval($pid);
    	$update_data['description'] = $pdesc;
        $this->db->where('id',$pid);
        $this->db->where('uid',$uid);
        $this->db->where('is_delete','1');
        $update_res = $this->db->update(USER_PHOTO,$update_data);
        if(!$update_res){
            return false;
        }
        return true;
    }
    
	/**
     * 设置封面
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $aid 相册ID
     * @param $pid 照片ID
     * @param $uid 用户ID
     */
    public function setAlbumCover($aid = null, $pid = null, $uid = null){
        if(!$aid || !$pid || !$uid){
            return false;
        }
        $aid = intval($aid);
        $pid = intval($pid);
        $data = array('id');
        $this->db->select($data);
        $this->db->where('id',$pid);
        $this->db->where('aid',$aid);
        $this->db->where('uid',$uid);
        $this->db->limit(1);
        $p_query = $this->db->get(USER_PHOTO);
        $p_lists = $p_query->result_array();
        
        if(!$p_lists){
            return false;
        }

        $update_data['cover_id'] = $pid;
        $this->db->where('id',$aid);
        $update_res = $this->db->update(USER_ALBUM,$update_data);
        
        if(!$update_res){
            return false;
        }

        return true;
    }
    
	/**
     * 移动照片
     *
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $aid 目标相册ID
     * @param $pid 照片ID
     * @param $uid 使用者uid
     */
    public function movePhoto($aid = null, $pids = null, $uid = null){
        if(!$uid || !$aid || !$pids){
        	echo '1';
            return false;
        }
        $aid = intval($aid);
        //判断传入的是否为数组
        if(is_array($pids)) {
        	$first_pid = current($pids);
        	$photo_count = count($pids);
        }else{
        	echo '2';
        	return false;
        }
        
        //判断该照片是否存在
        $data = array('id', 'aid', 'type');
        $this->db->select($data);
        $this->db->where('id',$first_pid);
        $this->db->where('uid',$uid);
        $this->db->limit(1);
        $p_query = $this->db->get(USER_PHOTO);
        $photo_info = $p_query->result_array();
        if(!$photo_info){
        	echo '3';
            return false;
        }

        //取得排序序号
        $p_sql = sprintf("SELECT max(p_sort) AS max_sort FROM ".USER_PHOTO." WHERE aid = '%s' LIMIT 1 ",$aid);
        $p_query = $this->db->query($p_sql);
        $p_lists = $p_query->result_array();
        if(count($p_lists)){
            $p_sort = $p_lists[0]['max_sort'] + 1;
        }else{
            $p_sort = 1;
        }
        //更新数据    
        $update_data = array();
        $update_data['aid'] = $aid;
      
        foreach ($pids as $pid) {
        	$update_data['p_sort'] = $p_sort;
	        $this->db->where('id',$pid);
		    $update_res = $this->db->update(USER_PHOTO,$update_data);
		    if(!$update_res){
		    	echo '4';
		        return false;
		    }
		    $p_sort++;
        }
       
        //原相册照片总数-1
        $src_sql = sprintf("UPDATE ".USER_ALBUM." 
                            SET photo_count = photo_count - {$photo_count} 
                            WHERE id = '%s' 
                            LIMIT 1 ",$photo_info[0]['aid']);
        $this->db->query($src_sql);
		
        //判断相册是否为最后一张图片
    	$data = array('photo_count');
        $this->db->select($data);
        $this->db->where('id',$photo_info[0]['aid']);
        $this->db->where('is_delete', 1);
        $query = $this->db->get(USER_ALBUM);
        $res = $query->result_array();
        if((int)$res[0]['photo_count'] === 0) {
        	$this->resetAlbumCover($photo_info[0]['aid'], UID, 1);
        }
        
        //目标相册照片总数+1
        $dst_sql = sprintf("UPDATE ".USER_ALBUM." 
                            SET photo_count = photo_count + {$photo_count} 
                            WHERE id = '%s' 
                            LIMIT 1 ",$aid);
        $this->db->query($dst_sql);
        
        //检查目标相册是否是空相册，如果为空，则设置封面
        $album_info = $this->getAlbums($uid, $aid);
        if(empty($album_info[0]['cover_id'])) {
        	$this->setAlbumCover($aid, $first_pid, UID);
        }
        return true;
    }
    
    /**
     * 自动更新相册排序顺序
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param integer $uid 用户ID
     * @param integer $aid 新增照片的相册ID
     */
    public function autoUpdateAlbumOrder($uid, $aid)
    {
        $sql = "select id from ".USER_ALBUM."
        		where uid = ? and is_delete = '1' AND web_id = 0 
        		order by a_sort desc
        		limit 1";
        $res = $this->db->query($sql, array($uid));
        $row = $res->row_array();
        if(empty($row) || $row['id'] == $aid){
            return true;
        }else{
            return $this->orderAlbum($uid, $aid, $row['id']);
        }
    }

    /**
     * 上传图片更新相册信息流
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * $param array $photo_list  照片列表信息
     * $param integer $time 上传照片时间戳
     * $param array $pids 照片id数组
     */
    public function addAlbumInfosFlow ($uid = null, $aid = null, $fid = null, $pids = null, $username, $photo_num)
    {
    	if(!$uid || !$aid || !$fid) {
    		return false;
    	}
    	
    	//得到相册信息
    	$data = array('uid' => $uid,
    					'id' => $aid,
    					'is_delete' => 1);
    	$res = $this->getAlbumList($data);
    	if(!$res) {
    		return false;
    	}else{
    		$album_info = $res['list'][0];
    	}
    	
    	//信息流照片地址
    	if($photo_num == 1) {
    		$photo_list = $this->getPhotoList(array('uid' => $uid,
    											'aid' => $aid,
    											'id' => $pids,
    											'is_delete' => 1));
	    	if(!$photo_list){
	    		return false;
	    	}else {
	    		$photo_list = $photo_list['data'];
	    	}
	    	$title = $photo_list[0]['name'];
    		$content = $photo_list[0]['name'];
    		$type = 'photo';
    	}else{
    		$data = array('id', 'filename', 'type', 'groupname', 'notes');
    		$this->db->select($data);
    		$this->db->where('is_delete', 1);
    		$this->db->where('aid', $aid);
   			$this->db->order_by('p_sort', 'DESC');
   			$query = $this->db->get(USER_PHOTO);
    		$photo_list = $query->result_array();
    		$photo_num = $query->num_rows();
    		$photo_list = array_slice($photo_list, 0, 8);
    		$title = $album_info['name'];
    		$content = '<a href = '.mk_url('album/index/photoLists', array('dkcode' => DKCODE, 'albumid' => $aid)).' >'.$album_info['name'].' ('.$photo_num.')</a>';
    		$type = 'album';
    	}
    	
    	foreach ($photo_list as $picval) {

    		//八张照片大中小等url地址数据
    		$picurls[] = array('pid' => $picval['id'], 'groupname' => $picval['groupname'], 'filename' => $picval['filename'], 'type' => $picval['type'], 'size' => json_decode($picval['notes'], true));
    	}
    	
    	//信息流发布
    	$infoflow_data = array('uid'=>$uid,
				               'fid'=>$fid,   //这里是照片的时间戳
    							'dkcode' => DKCODE,
    							'title' => $title,
				               'permission'=>$album_info['object_type'],      //权限，指的是相册的权限
				               'uname'=>$username,    //用户名
				               'content'=>$content,		//相册名  
				               'type'=>$type,
    						   'picurl' => json_encode($picurls),
				               'note' => $aid,       //相册ID
				               'dateline'=>time());
    	
    	if($type == 'album') {
    		$infoflow_data['photonum'] = $photo_num;
    	}
    	if($album_info['object_type'] == -1) {
    		$permision = explode(',', $album_info['object_content']);
    		$infoflow_data['permission'] = -1;
    	}
    	
    	if(isset($permision)) {
    		$info = service('Timeline')->replaceTopic($infoflow_data, $permision);
    	}else {
    		$info = service('Timeline')->replaceTopic($infoflow_data);
    	}
    	return $info;
    }

    /**
     * 单个相册信息流更新
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $aid integer 相册ID
     * @param array $timestamps 时间戳数组
     */
    public function updateAlbumInfosFlow($aid, $username = null, $uid) {
    	$aid = intval($aid);
    	$pids = $this->getAlbumPid($aid);
		
    	//寻求相册里不同时间戳
    	foreach ($pids as $pid) {
    			$this->delAlbumInfoFlow($pid, 'photo', $uid);
    	}
    	
    	$res = $this->updateAlbumInfoFlow($aid, $username, true, $uid);
    	if(!$res) {
    		return false;
    	}
    	return true;
    }
    
 	/**
     * 部分时间戳信息流更新
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param $aid integer 相册ID
     * @param array $timestamps 时间戳数组
     */
    public function updateTimestampsInfosFlow($aid, $pids, $username = null, $uid = null) {
    	$aid = intval($aid);
		$temppids = $this->getAlbumPid($pids, 'photo');
    	//寻求相册里不同时间戳
    	foreach ($temppids as $temppid) {
    		
			if($this->checkInfoFlowExist($temppid, 'photo', $uid)) {
				$this->updatePhotoInfoFlow($aid, $temppid, $username, $uid);
			}
    		
    	}
    	
    	$res = $this->updateAlbumInfoFlow($aid, $username, false, $uid);
    	if(!$res) {
    		return false;
    	}
    	return true;
    }
    
    
	/**
     * 编辑照片名称
     *
     * @author guzhongbin
     * @data   2012-03-13
     * @access public
     * @param $pid 照片ID
     * @param $pname 照片名字
     * @param $uid 用户ID
     */
    public function updatePhotoName($pid = null, $pname = null, $uid = null){
        if(!$pid || !$uid){
            return false;
        }
        $pid = intval($pid);
        if($pname == null){
        	$pname = '未命名';
        } 
        
        $update_data['name'] = $pname;
        $this->db->where('id',$pid);
        $this->db->where('uid',$uid);
        $this->db->where('is_delete','1');
        $update_res = $this->db->update(USER_PHOTO,$update_data);
        if(!$update_res){
            return false;
        }
        return true;
    }
    
    /**
     * 相册信息流更新
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param integer $aid 相册ID
     * @param integer $timestamp 时间戳
     */
	public function updateAlbumInfoFlow($aid = null, $username = null, $update_time = true, $uid = null)
	{
		$aid = intval($aid);
    	//判断该照片是否移动过
    	if($this->checkInfoFlowExist($aid, 'album', $uid)) {
    		//取删除移除之后的八张照片
    			
    		$data = array('id', 'filename', 'type', 'groupname', 'notes');
    		$this->db->select($data);
    		$this->db->where('is_delete', 1);
    		$this->db->where('aid', $aid);
   			$this->db->order_by('p_sort', 'DESC');
   			$query = $this->db->get(USER_PHOTO);
    		$photo_lists = $query->result_array();
    		
    		if(!$photo_lists) {//删除信息流
    			$this->delAlbumInfoFlow($aid, 'album', $uid);
    			return true;
    		}
    		
    		$photo_num = count($photo_lists);
    		$photo_lists = array_slice($photo_lists, 0, 8);
	    	foreach ($photo_lists as $picval) {
	
	    		//八张照片大中小等url地址数据
	    		$picurls[] = array('pid' => $picval['id'], 'groupname' => $picval['groupname'], 'filename' => $picval['filename'], 'type' => $picval['type'], 'size' => json_decode($picval['notes'], true));
	    	}
    		
    		$jsonpicurl = json_encode($picurls);
    		//dump($lists);
    		unset($picurls);
    		
    		//得到相册信息
	    	$data = array(
	    					'uid'=> $uid,
	    					'id' => $aid,
	    					'is_delete' => 1);
	    	$res = $this->getAlbumList($data);
	    	if(!$res) {
	    		return false;
	    	}else{
	    		$album_info = $res['list'][0];
	    	}
	    	$content = '<a href = '.mk_url('album/index/photoLists', array('dkcode' => DKCODE, 'albumid' => $aid)).' >'.$album_info['name'].' ('.$photo_num.')</a>';
			//信息流更新
	    	$infoflow_data = array('fid' => $aid,
	    							'type'=>'album',
	    							'uid'=> $uid,
	    						   'uname'=>$username,    //用户名
					               'content'=>$content,		//相册名
					               'photonum' => $photo_num,       //描述
	    						   'picurl' => $jsonpicurl
	    	);
	    	$update_time && $infoflow_data['dateline'] = time();
	    	$result = service('Timeline')->updateTopic($infoflow_data);
	    	//service('Comlike')->update_Like($aid,'album',time());
	    	
	    	if(!$result) {
				return false;
			}
			
    		return $result;
    	}
		return 'nofid';
	}
    
	/**
     * 照片信息流更新
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param integer $aid 相册ID
     * @param integer $timestamp 时间戳
     */
	public function updatePhotoInfoFlow($aid = null, $pid = null, $username = null, $uid =null)
	{
		if($this->checkInfoFlowExist($pid, 'photo', $uid)) {
			
			$aid = intval($aid);
    		//取删除移除之后的八张照片
    			
    		$data = array('id', 'filename', 'type', 'groupname', 'notes', 'name');
    		$this->db->select($data);
    		$this->db->where('is_delete', 1);
    		$this->db->where('aid', $aid);
   			$this->db->where('id', $pid);
   			$this->db->order_by('p_sort', 'ASC');
   			$query = $this->db->get(USER_PHOTO);
    		$photo_lists = $query->result_array();
    		
    		if(!$photo_lists) {//删除信息流
    			$this->delAlbumInfoFlow($pid, 'photo', $uid);
    			return true;
    		}
    		
    		$photo_num = count($photo_lists);
	    	foreach ($photo_lists as $picval) {
	
	    		$picurls[] = array(
	    			'pid' => $picval['id'], 
	    			'groupname' => $picval['groupname'], 
	    			'filename' => $picval['filename'], 
	    			'type' => $picval['type'], 
	    			'size' => json_decode($picval['notes']),
	    			//'size' => $size,
	    		);
	    	}
    		
    		$jsonpicurl = json_encode($picurls);
    		unset($picurls);
    		
    		//得到相册信息
	    	$data = array(	'uid' => $uid,
	    					'id' => $aid,
	    					'is_delete' => 1);
	    	$res = $this->getAlbumList($data);
	    	if(!$res) {
	    		return false;
	    	}else{
	    		$album_info = $res['list'][0];
	    	}
	    	
			//信息流更新
	    	$infoflow_data = array('fid' => $pid,
	    							'type'=>'photo',
	    							'uid' =>$uid,
	    						   'uname'=>$username,    //用户名
					               'content'=>$photo_lists[0]['name'],		//照片名
	    						   'picurl' => $jsonpicurl,
	    							'note' => $aid,
					               'dateline'=>time());
	    	$result = service('Timeline')->updateTopic($infoflow_data);
	    	if(!$result) {
				return false;
			}
			
    		return $result;
		}
		return 1;
	}
	
	/**
	 * 删除信息流信息
	 * 
	 * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
	 * @param integer $timestamp 时间戳
	 */
	public function delAlbumInfoFlow($fid, $type, $uid) {
		if($this->checkInfoFlowExist($fid, $type, $uid)) {
			$result = service('Timeline')->removeTimeline($fid, $uid, $type);
		}
	}
	
    /**
     * 检查信息流是否存在，存在返回信息流信息，不存在false
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param integer $timestamp 时间戳或者相册id
     */
	public function checkInfoFlowExist($fid, $type, $uid) {
		$result = service('Timeline')->getTopicByMap($fid, $type, $uid);
		if(!$result) {
			return false;
		}
		return $result;
	}
    
	
    /**
     * 获得相册单张pid 更新信息流使用
     * 
     * @author guzhongbin
   	 * @data   2012-03-26
   	 * @access public
     * @param int $aid
     */
    public function getAlbumPid($value, $type = 'album')
    {	
    	if($type == 'album') {
	        $sql = "SELECT id, dateline, count(*) as num FROM ".USER_PHOTO."
	        		WHERE aid = {$value}
	        		GROUP BY dateline";
    	}else{
    		$sql = "SELECT id, dateline, count(*) as num FROM ".USER_PHOTO." where id in (".implode(',', $value).") GROUP BY dateline";
    	}
        $res = $this->db->query($sql);
        $list = $res->result_array();
        $return = array();
        foreach($list as $item){
        	if($item['num'] == 1) {
        		$return[] = $item['id'];
        	}
        }
       
        return $return;
    }
    
    
	/**
     * 编辑相册名称
     *
     * @author guzhongbin
     * @date   2012-03-20
     * @access public
     * @param $aid 照片ID
     * @param $albumdesc 照片名字
     * @param $uid 用户ID
     */
    public function updateAlbumDesc($aid = null, $albumdesc = null, $uid = null){
    	$aid = intval($aid);
        if(!$aid || !$uid){
            return false;
        }
        
		$update_data['discription'] = $albumdesc;
    	
        $this->db->where('id',$aid);
        $this->db->where('uid',$uid);
        $this->db->where('is_delete','1');
        $update_res = $this->db->update(USER_ALBUM,$update_data);
        if(!$update_res){
            return false;
        }
        return true;
    }
    
    /**
     * 检查相册中符合条件的main封面照片
     * 
     * @author guzhongbin
     */
    public function checkMainCover($uid){
    	$this->db->select(array('id'));
    	$this->db->where(array('is_maincover' => 1, 'uid' => $uid, 'is_delete' => 1));
    	$res  = $this->db->get(USER_PHOTO)->row_array();
    	if(empty($res)){
    		$photo_info = $this->getMainCover($uid);
    		$this->setMainCover($photo_info['id'], $uid);
    		$this->load->model("appcovermodel", "appCover");
	        $info = $this->appCover->mergeImages($photo_info['groupname'], $photo_info['filename'].'.'.$photo_info['type'], $uid, 'album');
    	}
    }
    
    /**
     * 获得相册中符合条件的main封面照片
     * 
     * @author guzhongbin
     */
    public function getMainCover($uid)
    {
	    $sql = "select id from ".USER_ALBUM."
	    		where uid = '{$uid}' and is_delete = '1' AND web_id = 0 and a_type = '0' and photo_count > '0' and object_type = '1'
	    		order by id asc
	    		limit 1";
	    $res = $this->db->query($sql);
	    $album_info = $res->result_array($res);
	    if(!isset($album_info[0]['id'])){
	        return false;
	    }else{
    	    $sql = "select id, groupname, filename, type from ".USER_PHOTO."
    	    		where aid = '{$album_info[0]['id']}' and is_delete = '1'
    	    		order by id asc
    	    		limit 1";
    	    $res = $this->db->query($sql);
    	    $list = $res->result_array($res);
    	    if(!$list) {
    	    	return false;
    	    }
    	    return $list[0];
	    }
    }
    
    /**
     * 设置个人主页应用区封面
     * 
     * @author guzhongbin
     * @data 2012-04-10
     * @access public
     * @param $pid 应用区封面pid
     * 
     */
    
    public function setMainCover($pid, $uid) {
    	$pid = intval($pid);
    	$data = array('is_maincover' => '0');
    	$this->db->where('uid', $uid);
    	$update_result = $this->db->update(USER_PHOTO, $data);
    	if(!$update_result){
    		return false;
    	}
    	$data = array('is_maincover' => '1');
    	$this->db->where('id', $pid);
    	$this->db->where('uid', $uid);
    	$update_result = $this->db->update(USER_PHOTO, $data);
    	if($update_result){
    		return true;
    	}else{
    		return false;
    	}
    }
    
	/**
     * 删除个人主页应用区封面
     * 
     * @author guzhongbin
     * @data 2012-04-11
     * @access public
     * @param $pid 应用区封面pid
     * 
     */
    
    public function deleteMainCover($pid, $uid) {
    	$pid = intval($pid);
    	$data = array('is_maincover' => '0');
    	$this->db->where('id', $pid);
    	$this->db->where('uid', $uid);
    	$update_result = $this->db->update(USER_PHOTO, $data);
    	
    	$this->load->model("appcovermodel", "appCover");
	    $info = $this->appCover->mergeImages('', '', $uid, 'album');
    	if($info){
    		return true;
    	}else{
    		return false;
    	}
    	return true;
    }
    
	/**
     * 旋转照片
     * 
     * @author vicente
     * @access public
     * @param int $id 照片ID
     * @param int $degree 角度
     * @return boolean
     */
    public function rotate($photo_id, $degree)
    {
    	$pic_type = 0;
		$pic_info = $this->getPhotoInfo($photo_id);
    	
    	$this->config->load('album');
		$pic_conf = GetThumbConf($pic_type); //大  中  小  尺寸 配置
		$tmp_storage_path = $this->config->item('tmp_storage_path');
		$loc_name = trim(substr($pic_info['filename'], strrpos($pic_info['filename'], '/')+1));
		
		//原图和_b规格的图片
		$org_filename =  rtrim($tmp_storage_path, "/") . "/org_" . $loc_name. "." . $pic_info['type'];
		$org_filename_b =  rtrim($tmp_storage_path, "/") . "/org_" . $loc_name. "_b." . $pic_info['type'];

		//从fdfs下载图片
		$this->load->fastdfs('album','', 'fdfs');
		$this->fdfs->downloadFile($pic_info['filename'] . "." . $pic_info['type'], $org_filename, $pic_info['groupname']);
		$this->fdfs->downloadFile($pic_info['filename'] . "_b." . $pic_info['type'], $org_filename_b, $pic_info['groupname']);

		$image = get_image('defalut');
		
		//对原图进行旋转产生新图片
		$new_filename =  rtrim($tmp_storage_path, "/") . "/new_" . $loc_name . "." . $pic_info['type'];
		$new_filename_b =  rtrim($tmp_storage_path, "/") . "/new_" . $loc_name . "_b." . $pic_info['type'];
		
		$image->rotate($org_filename, $new_filename, $degree);
		$image->rotate($org_filename_b, $new_filename_b, $degree);
				
		//删除fdfs文件然后上传
		$this->fdfs->deleteFile($pic_info['groupname'], $pic_info['filename']. "." . $pic_info['type']);
		$org_pic_info = $this->fdfs->uploadFile($new_filename, $pic_info['type']);
		
    	//验证是否上传成功
        if(!is_array($org_pic_info) || !isset($org_pic_info['group_name']) || !isset($org_pic_info['filename'])){
        	return false;
        }
        
        $pt = array(
        	'filename'    => substr($org_pic_info['filename'], 0, strrpos($org_pic_info['filename'], ".")),
        	'groupname'   => $org_pic_info['group_name'],
        	'type'        => $pic_info['type'],
        	'size'        => filesize($new_filename),
        	'dateline'    => time()
        ); 

        //直接上传旋转后的_b图片
        $org_pic_url = $pt['filename']. "." . $pt['type'];
        $this->fdfs->uploadSlaveFile($new_filename_b, $org_pic_url, "_b", $pt['type'], array(), $pt['groupname']);

		//生成除_b缩略图
		$sizes = array();
		if(is_array($pic_conf['size'])){
			$photo_quality_cfg = $this->config->item('photo_quality');
        	$pic_quality_num = $photo_quality_cfg['normal'];
        	$new_loc_name = trim(substr($pt['filename'], strrpos($pt['filename'], '/')+1));
			foreach($pic_conf['size'] AS $key => $val){
				//_b不重新切图
				if($key != 'b'){
					$this->fdfs->deleteFile($pic_info['groupname'], $pic_info['filename']. "_" . $key ."." . $pt['type']);
					$dst = rtrim($tmp_storage_path, "/") . "/" . $new_loc_name . "_" . $key . "." . $pt['type'];
					if($image->$val['type']($new_filename, $dst, $val['width'], $val['height'], $pic_quality_num)) {
						if(is_file($dst)){	
						    $sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
						    while(!is_array($sign)){
						    	$error = $val['type'];
						    	$sign = $this->fdfs->uploadSlaveFile($dst, $org_pic_url, "_" . $key, $pt['type'], array(), $pt['groupname']);
						    }

						    //增加缩略图尺寸记录
			                if(is_array($sign)){
			                    $size = getimagesize($dst);
			                    $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
			                    @unlink($dst);
			                }
						}
					}
				}else{
					$size = getimagesize($new_filename_b);
			        $sizes[$key] = array('w' => $size[0], 'h' => $size[1]);
				}
			}
		}
		
		$img_b = getImgPath($pt['groupname'], $pt['filename'], $pt['type'], 'b');
		
		@unlink($org_filename);
		@unlink($org_filename_b);
		@unlink($new_filename);
		@unlink($new_filename_b);
		
		if(!empty($sizes)){
			$pt['notes'] = json_encode($sizes);
		}
		
		$this->db->where('id', $photo_id);
		$this->db->update(USER_PHOTO, $pt);
		
		return array(
			'picUrl'   =>$img_b,
			'dateline' => $pic_info['timestamp'],
			'aid'      => $pic_info['aid']
		);
    }
    
    
    /**
     * 验证照片是否是某个默认相册的照片
     * 专为API调用
     * 
     * @param integer $pid 照片编号
     * @param integer $type 相册类型
     * @return boolean
     */
    public function checkAlbumType($pid, $type)
    {	
    	$pid = intval($pid);
        $sql = "select aid from ".USER_PHOTO." where id = ?";
        $res = $this->db->query($sql, array($pid));
        $row = $res->row_array();
        if(isset($row['aid'])){
            $sql = "select a_type from ".USER_ALBUM." where id = ?";
            $res = $this->db->query($sql, array($row['aid']));
            $row = $res->row_array();
            if(isset($row['a_type']) && $row['a_type'] == $type){
                return true;
            }
            return false;
        }
        return false;
    }
    
    /**
     * 修改，增加照片搜索索引
     * 
     * @author guzhongbin
     * @date 2012-05-25
     * @param $pids array 照片ID 
     */
    
    public function  photoSearchIndex($pids){
    	if(is_array($pids)) {
	    	foreach ($pids as $pid) {
	    		$photo_info[] = array('id' => $pid, 'type' => 0);
	    	}
    	}else{
    		$photo_info = array(array('id'=> $pids, 'type' => 0));
    	}
    	
    	service('RestorationSearch')->restorePhotoInfo($photo_info);
    }
    
    
    /**
     * 删除照片搜索索引
     * 
     * @author guzhongbin
     * @date 2012-05-25
     * @param $pids array 照片ID 
     */
    
    public function  photoSearchIndexDel($pids){
    	service('RelationIndexSearch')->deletePhoto($pids);
    }
    
    
	/**
     * 修改，增加相册搜索索引
     * 
     * @author guzhongbin
     * @date 2012-05-25
     * @param $aid int 相册ID 
     */
    
    public function  albumSearchIndex($aid, $visible){
    	$aid = intval($aid);
    	$album_info = array('id' => $aid,
    						'type' => 0,
    						'visible' => $visible,
    						);
    	service('RestorationSearch')->restoreAlbumInfo($album_info);
    }
    
	/**
     * 删除或从公开转为非公开权限相册搜索索引
     * 
     * @author guzhongbin
     * @date 2012-05-25
     * @param $aid int 相册ID 
     */
    
    public function  albumSearchIndexDel($aid){
    	$aid = intval($aid);
    	service('RelationIndexSearch')->deleteAlbum($aid);
    }
    
	/**
     * 移动照片更新搜索索引
     * 
     * @author guzhongbin
     * @date 2012-05-25
     * @param $aids array 相册ID 
     * @param $pids array 照片ID 
     */
    
    public function  photoSearchIndexMove($albumInfo,$beAlbumInfo){
    
    service('RestorationSearch')->restorePhotoInfoTransfered(array($albumInfo, $beAlbumInfo));
    }
   
    
}

/* End of file albummodel.php */
/* Location: ./app/album/application/albummodels/albummodel.php */
	