<?php
/**
 * 照片model
 *
 * @author        vicente
 * @version       $Id
 */
class PhotoModel extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 生成查询sql
     * 
     * @author vicente
   	 * @access public
     * @param array $params 参数数组
     * @return string sql 语句
     */
    public function getQuerySql($params)
    {
        $where_sql = " WHERE 1=1";

        //主键
        if(isset($params['id']) && $params['id']){
            if(is_array($params['id'])){
                $where_sql .= " AND id in ('".implode("','", $params['id'])."')";
            }else{
                $where_sql .= " AND id = ".$this->db->escape($params['id'])."";
            }
        }
        
    	if(isset($params['not_id']) && $params['not_id']){
			$where_sql .= " AND id <> ".$this->db->escape($params['not_id'])."";
        }
        

        //用户
        if(isset($params['uid']) && $params['uid']){
            if(is_array($params['uid'])){
                $where_sql .= " AND uid in ('".implode("','", $uid_list)."')";
            }else{
                $where_sql .= " AND uid = '{$params['uid']}'";
            }
        }

        //相册
        if(isset($params['aid']) && $params['aid']){
            $where_sql .= " AND aid = ".$this->db->escape($params['aid'])."";
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
        
        //当前时间
    	if(isset($params['dateline']) && $params['dateline']){
            $where_sql .= " AND dateline = '".intval($params['dateline'])."'";
        }

        //创建时间
        if(isset($params['start_dateline']) && $params['start_dateline']){
            $where_sql .= " AND dateline >= '".intval($params['start_dateline'])."'";
        }
        if(isset($params['end_dateline']) && $params['end_dateline']){
            $where_sql .= " AND dateline <= '".intval($params['end_dateline'])."'";
        }

        return $where_sql;
    }

    /**
     * 照片数量
     * 
     * @author vicente
   	 * @access public
     * @param array $params 参数列表
     * @return int 照片数量
     */
    public function count($params)
    {
        $where_sql = $this->getQuerySql($params['where']);
        $num_sql = "SELECT COUNT(*) AS num FROM " . USER_PHOTO . " ". $where_sql;

        $num_res = $this->db->query($num_sql);
        $num_row = $num_res->row_array();

        return $num_row['num'];
    }

    /**
     * 照片列表
     * 
     * @author vicente
   	 * @access public
     * @param array $params 参数列表
     * @return array 照片列表
     */
    public function index($params)
    {
        $return = array();
        $list_sql = "SELECT id, name, uid, dateline, aid, type, groupname, filename, size, description, notes, is_maincover FROM ".USER_PHOTO;
	    $where_sql = $this->getQuerySql($params['where']);
        $orderby = isset($params['orderby']) && !empty($params['orderby']) ? $params['orderby'] : array();
        $this->config->load('album');
		$orderby_arr = $this->config->item('orderby');
		$romote_img_url = $this->config->item('romote_img_url');
		$photo_size_arr = $this->config->item('thumb_pic_sizes');
		
        $where_sql .= getOrderBy($orderby, 'photo', $orderby_arr);
        if(isset($params['limit'])){
            $where_sql .= gePageSize($params['limit']);
        }

        $res = $this->db->query($list_sql.$where_sql);
        $photo_list = $res->result_array();
        foreach($photo_list as $k => $v){
            $notes = !empty($v['notes']) ? json_decode($v['notes'], true) : array();
            //得到大中小图片地址
            
            foreach($photo_size_arr['size'] as $key=>$size) {
            	if(!empty($notes) && array_key_exists($key, $notes)){
            		$v['img_'.$key] = getImgPath($v['groupname'], $v['filename'], $v['type'], $key);
            	}else{
            		$v['img_'.$key] = getImgRomotePath($v['filename'], $key, $v['type'], date('Ymd', $v['dateline']), $romote_img_url);
            	}
            }
            $v['timestamp'] = $v['dateline'];
            $v['dateline'] = date('Y-m-d',$v['dateline']);
            $return[] = $v;
        }

        return $return;

    }
    
    /**
     * 获取照片信息
     *
     * @author vicente
     * @access public
     * @param int $id 照片id
     * @return array 照片信息
     */
    public function get($id)
    {
        $this->db->where('id',$id);
        //$this->db->where('is_delete','1');
        $query = $this->db->get(USER_PHOTO);
        $photo_info = $query->row_array();

        if(empty($photo_info)){
            return $id;
        }

        $this->config->load('album');
		$photo_size_arr = $this->config->item('thumb_pic_sizes');
		$romote_img_url = $this->config->item('romote_img_url');
		$notes = !empty($photo_info['notes']) ? json_decode($photo_info['notes'], true) : array();
		
        foreach($photo_size_arr['size'] as $key=>$size) {
        	if(!empty($notes) && array_key_exists($key, $notes)){
            	$photo_info['img_'.$key] = getImgPath($photo_info['groupname'], $photo_info['filename'], $photo_info['type'], $key);
            }else{
            	$photo_info['img_'.$key] = getImgRomotePath($photo_info['filename'], $key, $photo_info['type'], date('Ymd', $photo_info['dateline']), $romote_img_url);
            }
        }
        
    	//取照片大小，原图地址
    	$thumperSize = json_decode($photo_info['notes'], true);
    	if(isset($thumperSize['self'])) {
    		$photo_info['img'] = getImgPath($photo_info['groupname'], $photo_info['filename'], $photo_info['type']);
    		$photo_info['thumperSize'] = $thumperSize['self'];
    	}else{
    		$photo_info['img'] = $photo_info['img_b'];
    		$photo_info['thumperSize'] = isset($thumperSize['b']) ? $thumperSize['b'] : array();
    	}
    	
    	$photo_info['timestamp'] = $photo_info['dateline'];
        $photo_info['dateline'] = date('Y-m-d', $photo_info['dateline']);

        return $photo_info;
    }

    /**
     * 修改照片
     *
     * @author vicente
     * @param array $request_data 提交信息数组
     * @return boolean
     */
    public function edit($request_data)
    {
        $album_id = $request_data['album_id'];
        $photo_quality = isset($request_data['photo_quality']) ? $request_data['photo_quality'] : 75;
        $request_data['dateline'] = isset($request_data['dateline']) ? $request_data['dateline'] : time();
        
        $photo_data = $this->get($request_data['id']);
    	if(empty($photo_data)){
        	throw new MY_Exception("照片信息有误！");
        }
        
    	$album_info = $this->album->get($album_id);
        if(empty($album_info)){
        	throw new MY_Exception("相册信息有误！");
        }
        
        //取得排序序号
        $photo_sql = sprintf("SELECT max(p_sort) AS max_sort,filename,type FROM ".USER_PHOTO." WHERE aid = '%s' LIMIT 1 ",$album_id);
        $photo_query = $this->db->query($photo_sql);
        $photo_info = $photo_query->row_array();
        if(!empty($photo_info)){
            $p_sort = $photo_info['max_sort'] + 1;
        }else{
            $p_sort = 1;
        }
        
        //重新检查图片看是否存在各种型号的缩略图，如果不存在，重新生成
        /*TODO
        $photo_quality_cfg = config_item('photo_quality');
        $photo_quality = $photo_quality_cfg['normal'];
        $this->load->model('uploadmodel', 'upload');
        $sizes = $this->upload->setThumbPic($photo_data, $album_info['a_type'], $photo_quality);*/

        //上传照片
        $upload_data = array(
            'uid'       => $request_data['uid'], 
            'aid'       => $album_id,
            'p_sort'    => $p_sort,
            'is_delete' => 1, 
            'dateline'  => $request_data['dateline'] 
        );
        
        isset($upload_data['description']) && $upload_data['description'] = $request_data['description'];
        //is_array($sizes) && $upload_data['notes'] = json_encode($sizes);

        $this->db->where(array('id' => $request_data['id']));
        $this->db->update(USER_PHOTO, $upload_data);
        $res = $this->db->affected_rows();
        if(!$res){
            throw new MY_Exception("照片增加失败！");
        }

        //相册照片数量+1
        $album_sql = sprintf("UPDATE ".USER_ALBUM." 
            SET photo_count = photo_count + 1
            ,last_dateline = '%d'
            WHERE id = '%s' 
            LIMIT 1 ",$request_data['dateline'],$album_id);
        $this->db->query($album_sql);
        
        return true;
    }
    
	/**
     * 批量修改照片
     *
     * @author vicente
     * @param array $photo_data 提交信息数组
     * @param array $request_data 常用的数据
     * @return boolean
     */
    public function batch_edit($photo_data, $request_data)
    {
    	$album_id = $request_data['album_id'];
        $request_data['dateline'] = isset($request_data['dateline']) ? $request_data['dateline'] : time();
        
        //取得排序序号
        $photo_sql = sprintf("SELECT max(p_sort) AS max_sort,filename,type FROM ".USER_PHOTO." WHERE aid = '%s' LIMIT 1 ",$album_id);
        $photo_query = $this->db->query($photo_sql);
        $photo_info = $photo_query->row_array();
        if(!empty($photo_info)){
            $p_sort = $photo_info['max_sort'] + 1;
        }else{
            $p_sort = 1;
        }
        foreach ($photo_data as $v){
        	//上传照片
	        $upload_data = array(
	            'uid'       => $request_data['uid'], 
	            'aid'       => $album_id,
	            'p_sort'    => $p_sort,
	            'is_delete' => 1, 
	            'dateline'  => $request_data['dateline'],
				'description' => htmlspecialchars($v['picDesc'], ENT_QUOTES)
	        );
	        
	        $this->db->where(array('id' => $v['picId']));
	        $this->db->update(USER_PHOTO, $upload_data);
	        $res = $this->db->affected_rows();
	        if(!$res){
	            throw new MY_Exception("照片增加失败！");
	        }
	        
	        $p_sort++;
        }
        
        $count = count($photo_data);

        //相册照片数量+1
        $album_sql = sprintf("UPDATE ".USER_ALBUM." 
            SET photo_count = photo_count + ".$count."
            ,last_dateline = '%d'
            WHERE id = '%s' 
            LIMIT 1 ",$request_data['dateline'],$album_id);
        $this->db->query($album_sql);
        
        return true;
    }
    
    /**
     * 设置照片为相册的封面以及应用区封面
     *
     * @author vicente
     * @access public
     * @param int $album_id 相册id
     * @param int $cover_id 照片id
     * @param boolean $flag 是否设置应用区
     * @return boolean
     */
    public function setAutoCover($album_id, $cover_id, $flag = true)
    {
    	$photo = $this->get($cover_id);
    	if(empty($photo)){
            throw new MY_Exception("照片信息有误！");
        }
        
    	//判断相册是否存在
        $arr = array('id','cover_id', 'web_id', 'uid');
        $this->db->select($arr);
        $this->db->where('id',$album_id);
        $this->db->where('is_delete','1');
        $this->db->limit(1);
        $album_query = $this->db->get(USER_ALBUM);
        $album_info = $album_query->row_array();
        if(empty($album_info)){
            throw new MY_Exception("相册信息有误！");
        }
        
        if(empty($album_info['cover_id'])){
            $this->album->setAlbumCover($album_id, $cover_id);

        	//如果没有应用区封面，设置应用区封面
	        if($flag === true && !empty($album_info['web_id']) && !$this->getMainCover($album_info['uid'], $album_info['web_id'])){
	            $this->setMainCover($photo['id'], $album_info['uid']);
            	
            	$this->load->model("appcovermodel", "appCover");
	            $filename = $photo['filename'].'.'.$photo['type'];
	            $info = $this->appCover->mergeImages($photo['groupname'], $filename, $album_info['web_id'], 2);
	        }
        }
        
        return true;
    }
    
	/**
     * 删除单个照片
     *
     * @author vicente
     * @access public
     * @param int $id
     * @return boolean
     */
    public function delete($id)
    {
        $sql = "SELECT aid FROM ".USER_PHOTO."
            WHERE id = ? AND is_delete = 1";
        $query = $this->db->query($sql, array($id));
        $photo_info = $query->row_array();
        if(empty($photo_info)){
            throw new MY_Exception("照片信息不存在！");
        }

        $update_data['is_delete'] = '0';
        $this->db->where('id',$id);
        $this->db->update(USER_PHOTO,$update_data);
        
        //相册照片总数变更
        $update_sql = sprintf("UPDATE ".USER_ALBUM." 
        	SET photo_count = photo_count - 1 
            WHERE id = '%s' 
            LIMIT 1 ",$photo_info['aid']);
		$this->db->query($update_sql); 
		
		return true;
    }
    
	/**
     * 设置某个照片不再为应用区封面
     * 
     * @author vicente
     * @access public
     * @param int $id 照片id
     * @param int $uid 用户id
     * @param int $web_id 网页id
     * @return boolean
     */
    public function deleteMainCover($id, $uid, $web_id) 
    {
    	$data = array('is_maincover' => '0');
    	$this->db->where('id', $id);
    	$this->db->where('uid', $uid);
    	$update_result = $this->db->update(USER_PHOTO, $data);
    	
    	$this->load->model("appcovermodel", "appCover");
        return $this->appCover->mergeImages('', '', $web_id, 2);
    }
    
	/**
     * 移动照片到目标相册
     *
     * @author vicente
     * @access public
     * @param int $album_id 目标相册ID
     * @param int $ids 照片ID
     * @return boolean
     */
	public function movePhoto($album_id, $ids)
    {
		 //判断传入的是否为数组
        if(is_array($ids)) {
        	$first_id = current($ids);
        	$photo_count = count($ids);
        }else{
        	$first_id = $ids;
        	$photo_count = 1;
        	$ids = array($ids);
        }

        $data = array('id', 'aid', 'type');
        $this->db->select($data);
        $this->db->where('id', $first_id);
        $this->db->limit(1);
        $p_query = $this->db->get(USER_PHOTO);
        $photo_info = $p_query->row_array();
        if(!$photo_info){
            throw new MY_Exception("照片信息有误！");
        }
        
        $album_info = $this->album->get($album_id);
    	if(!$album_info){
            throw new MY_Exception("相册信息有误！");
        }

        //取得排序序号
        $p_sql = sprintf("SELECT max(p_sort) AS max_sort FROM ".USER_PHOTO." WHERE aid = '%s' LIMIT 1 ",$album_id);
        $p_query = $this->db->query($p_sql);
        $p_list = $p_query->row_array();
        if(!empty($p_list)){
            $p_sort = $p_list['max_sort'] + 1;
        }else{
            $p_sort = 1;
        }  
           
		foreach ($ids as $id) { 
			$update_data['p_sort'] = $p_sort;
			$update_data['aid'] = $album_id;
			$this->db->where('id', $id);
			if(!$this->db->update(USER_PHOTO, $update_data)){
				throw new MY_Exception("移动失败！");
			}
			$p_sort++;
		}
        //原相册照片总数-1
        $sql = sprintf("UPDATE ".USER_ALBUM." 
            SET photo_count = photo_count - {$photo_count} 
            WHERE id = '%s' 
            LIMIT 1 ",$photo_info['aid']);
        $this->db->query($sql);

        //判断相册是否为最后一张图片
        $this->db->select(array('photo_count'));
        $this->db->where('id',$photo_info['aid']);
        $this->db->where('is_delete', 1);
        $query = $this->db->get(USER_ALBUM);
        $res = $query->row_array();
        if((int)$res['photo_count'] == 0) {
            $this->album->resetAlbumCover($photo_info['aid'],  true);
        }

        //目标相册照片总数+1
        $dst_sql = sprintf("UPDATE ".USER_ALBUM." 
            SET photo_count = photo_count + {$photo_count}
            WHERE id = '%s' 
            LIMIT 1 ", $album_id);
        $this->db->query($dst_sql);

        //检查目标相册是否是空相册，如果为空，则设置封面
        $this->db->select(array('photo_count', 'cover_id'));
        $this->db->where('id',$album_id);
        $this->db->where('is_delete', 1);
        $query = $this->db->get(USER_ALBUM);
        $origin_album = $query->row_array();
        if($origin_album['photo_count'] == 1 && $origin_album['cover_id'] == ''){
            $this->album->setAlbumCover($album_id, $first_id);
        }
        
        return true;
    }
    
	/**
     * 照片列表，取得上一张/下一张照片
     *
     * @author vicente
     * @access public
     * @param int $id 照片ID
     * @param boolean $is_comment 是否评论
     * @return array
     */
    public function getPhotoPrevNext($id, $is_comment = false)
    {
        $num = 0;
        //判断该照片是否存在
        $data = array('id','uid','aid','dateline','p_sort');
        $this->db->select($data);
        $this->db->where('id',$id);
        $this->db->where('is_delete','1');
        $this->db->limit(1);
        $query = $this->db->get(USER_PHOTO);
        $photo_info = $query->row_array();
        if(empty($photo_info)){
            throw new MY_Exception("照片信息有误！");
        }

        $next_id = $this->getNextPhotoId($photo_info['id'], $is_comment, $photo_info['p_sort'], $photo_info['aid']);
        $prev_id = $this->getPrevPhotoId($photo_info['id'], $is_comment, $photo_info['p_sort'], $photo_info['aid']);

        //计算当前照片所属第几张
        $num_sql = sprintf("SELECT count(*) AS num 
            FROM ".USER_PHOTO."
            WHERE aid = '%s'
            AND is_delete = '1' 
            AND p_sort >= '%d'
            LIMIT 1 ",$photo_info['aid'],$photo_info['p_sort']);
        $num_query = $this->db->query($num_sql);
        $num_list = $num_query->row_array();
        if(!empty($num_list)){
            $num = $num_list['num'];
        }

        return array('prev_id' => $prev_id,'next_id' => $next_id,'num' => $num);
    }
    
    /**
     * 取得此照片的上一个照片id
     *
     * @author vicente
     * @access public
     * @param int $id 照片ID
     * @param boolean $is_comment 是否评论
     * @param int $p_sort 排序数目
     * @param int $album_id 相册id
     * @return int
     */
    public function getPrevPhotoId($id, $is_comment = false, $p_sort = 0, $album_id = 0)
    {
        $prev_id = 0;
        //假若只传id参数，其他参数需要在查询下表获得 
        if(empty($album_id)){
            //判断该照片是否存在
            $data = array('id','uid','aid','dateline','p_sort');
            $this->db->select($data);
            $this->db->where('id',$id);
            $this->db->where('is_delete','1');
            $this->db->limit(1);
            $query = $this->db->get(USER_PHOTO);
            $photo_info = $query->row_array();
            if(empty($photo_info)){
                throw new MY_Exception("照片信息有误！");
            }
            $album_id = $photo_info['aid'];
            $p_sort = $photo_info['p_sort'];
        }
        //上一张照片
        if($is_comment){
            $prev_sql = sprintf("SELECT id
                FROM ".USER_PHOTO."
                WHERE aid = '%s'
                AND is_delete = '1' 
                AND is_comment = '1' 
                AND id <> '%s'
                AND p_sort > '%d'
                ORDER BY p_sort ASC
                LIMIT 1 ",$album_id,$id,$p_sort);
        }else{
            $prev_sql = sprintf("SELECT id
                FROM ".USER_PHOTO."
                WHERE aid = '%s'
                AND is_delete = '1' 
                AND id <> '%s'
                AND p_sort > '%d'
                ORDER BY p_sort ASC
                LIMIT 1 ",$album_id,$id,$p_sort);
        }
        $prev_query = $this->db->query($prev_sql);
        $prev_info = $prev_query->row_array();
        if(!empty($prev_info)){
            $prev_id = $prev_info['id'];
        }

        return $prev_id;
    }
    
    /**
     * 取得此照片的下一个照片id
     *
     * @author vicente
     * @access public
     * @param int $id 照片ID
     * @param boolean $is_comment 是否评论
     * @param int $p_sort 排序数目
     * @param int $album_id 相册id
     * @return int
     */
	public function getNextPhotoId($id, $is_comment = false, $p_sort = 0, $album_id = 0)
    {
        $next_id = 0;
        if(empty($id)){
            throw new MY_Exception("非法操作！");
        }
        //假若只传id参数，其他参数需要在查询下表获得 
        if(empty($album_id)){
            //判断该照片是否存在
            $data = array('id','uid','aid','dateline','p_sort');
            $this->db->select($data);
            $this->db->where('id',$id);
            $this->db->where('is_delete','1');
            $this->db->limit(1);
            $query = $this->db->get(USER_PHOTO);
            $photo_info = $query->row_array();
            if(empty($photo_info)){
                throw new MY_Exception("照片信息有误！");
            }
            $album_id = $photo_info['aid'];
            $p_sort = $photo_info['p_sort'];
        }
        //下一张照片
        if($is_comment){
            $next_sql = sprintf("SELECT id
                FROM ".USER_PHOTO."
                WHERE aid = '%s'
                AND is_delete = '1' 
                AND is_comment = '1' 
                AND id <> '%s'
                AND p_sort < '%d'
                ORDER BY p_sort DESC
                LIMIT 1 ",$album_id,$id,$p_sort);
        }else{
            $next_sql = sprintf("SELECT id
                FROM ".USER_PHOTO."
                WHERE aid = '%s'
                AND is_delete = '1' 
                AND id <> '%s'
                AND p_sort < '%d'
                ORDER BY p_sort DESC
                LIMIT 1 ",$album_id,$id,$p_sort);
        }

        $next_query = $this->db->query($next_sql);
        $next_info = $next_query->row_array();
        if(!empty($next_info)){
            $next_id = $next_info['id'];
        }

        return $next_id;
    }
    
 	/**
     * 跳转到下一张照片ID
     *
     * @author guzhongbin
     * @data   2012-03-26
     * @access public
     * @param $pid 照片ID
     */
    public function getDelphotoNext($id, $is_comment = false)
    {
        //判断该照片是否存在
        $data = array('id','uid','aid','dateline','p_sort');
        $this->db->select($data);
        $this->db->where('id',$id);
        $this->db->where('is_delete','1');
        $this->db->limit(1);
        $query = $this->db->get(USER_PHOTO);
        $photo_info = $query->row_array();
        if(empty($photo_info)){
            throw new MY_Exception("照片信息有误！");
        }

        //下一张照片
        if($is_comment){
            $next_sql = sprintf("SELECT id
                FROM ".USER_PHOTO."
                WHERE aid = '%s'
                AND is_delete = '1' 
                AND is_comment = '1' 
                AND id <> '%s'
                AND dateline <= '%d'
                ORDER BY dateline DESC
                LIMIT 1 ",$photo_info['aid'],$id,$photo_info['dateline']);
        }else{
            $next_sql = sprintf("SELECT id
                FROM ".USER_PHOTO."
                WHERE aid = '%s'
                AND is_delete = '1' 
                AND id <> '%s'
                AND dateline <= '%d'
                ORDER BY dateline DESC
                LIMIT 1 ",$photo_info['aid'],$id,$photo_info['dateline']);
        }

        $next_query = $this->db->query($next_sql);
        $next_list = $next_query->row_array();

        $next_id = 0;
        if(empty($next_list)){
            //上一张照片
            if($is_comment){
                $prev_sql = sprintf("SELECT id
                    FROM ".USER_PHOTO."
                    WHERE aid = '%s'
                    AND is_delete = '1' 
                    AND is_comment = '1' 
                    AND id <> '%s'
                    AND dateline >= '%d'
                    ORDER BY dateline ASC
                    LIMIT 1 ",$photo_info['aid'],$id,$photo_info['dateline']);
            }else{
                $prev_sql = sprintf("SELECT id
                    FROM ".USER_PHOTO."
                    WHERE aid = '%s'
                    AND is_delete = '1' 
                    AND id <> '%s'
                    AND dateline >= '%d'
                    ORDER BY dateline ASC
                    LIMIT 1 ",$photo_info['aid'],$id,$photo_info['dateline']);
            }

            $prev_query = $this->db->query($prev_sql);
            $prev_list = $prev_query->result_array();

            if(!empty($prev_list)){
                $next_id = $prev_list['id'];
            }
        }else{
            $next_id = $next_list['id'];
        }

        return $next_id;
    }
    
	/**
     * 批量删除照片
     *
     * @author vicente
     * @access public
     * @param int $id
     * @return boolean
     */
    public function batch_delete($arr_ids, $album_id)
    {
        //判断是否是批量删除，是，取第一个id.
		$album_info = $this->album->get($album_id);
        $update_data['is_delete'] = '0';
   		$this->db->where_in('id', $arr_ids);
        $this->db->update(USER_PHOTO,$update_data);
        //相册照片总数变更
        $update_sql = sprintf("UPDATE ".USER_ALBUM." 
        	SET photo_count = photo_count - ".count($arr_ids)." 
        	WHERE id = '%s' 
         	LIMIT 1 ",$album_id);
        $this->db->query($update_sql);

        if(in_array($album_info['cover_id'], $arr_ids)) {
			$this->album->resetAlbumCover($album_id);
        }

		//判断照片是否应用区封面照片
        $data = array('id');
        $this->db->select($data);
        $this->db->where('is_maincover','1');
        $this->db->where_in('id', $arr_ids);
        $cover_query = $this->db->get(USER_PHOTO);
        $cover_result = $cover_query->row_array();
        if(!empty($cover_result)) {
        	$this->photo->deleteMainCover($cover_result['id']);
        } 

        $this->db->select(array('photo_count'));
        $this->db->where('id',$album_id);
        $this->db->where('is_delete', 1);
        $query = $this->db->get(USER_ALBUM);
        $res = $query->row_array();
        if($res['photo_count'] == 0) {
            $this->album->resetAlbumCover($album_id, 1);
        }

        return true;
    }

    /**
     * 编辑照片名称
     *
     * @author vicente
     * @access public
     * @param int $id 照片ID
     * @param string $name 照片名字
     * @return boolean
     */
    public function editName($id, $name)
    {
    	$name = mb_substr($name, 0, 50, 'utf-8');
    	$name = htmlspecialchars($name, ENT_QUOTES);

        $update_data['name'] = $name;
        $this->db->where('id',$id);
        $this->db->where('is_delete','1');
        return $this->db->update(USER_PHOTO,$update_data);
    }

    /**
     * 编辑照片描述信息
     *
     * @author vicente
     * @access public
     * @param int $id 照片ID
     * @param string $desc 照片描述信息
     * @return boolean
     */
    public function editDesc($id, $desc)
    {
    	$desc = mb_substr($desc, 0, 140, 'utf-8');
    	$desc = htmlspecialchars($desc, ENT_QUOTES);

        $update_data['description'] = $desc;
        $this->db->where('id',$id);
        $this->db->where('is_delete','1');
        return $this->db->update(USER_PHOTO,$update_data);
    }
    
    /**
     * 取得照片排序序号
     *
     * @author vicente
     * @access public
     * @param string $sql sql语句
     * @return int 序号
     */
    public function getSortNum($sql)
    {
        $query = $this->db->query($sql);
        $list = $query->row_array();
        if(empty($list)){
            throw new MY_Exception("照片信息有误！");
        }
        return $list['sort_num'];
    }

    /**
     * 照片排序
     *
     * @author vicente
     * @access public
     * @param int $moverA_id 出发点
     * @param int $moverB_id 到达点
     * @param int $album_id 相册id
     */
    public function orderPhoto($moverA_id,$moverB_id,$album_id)
    {
    	$moverA_id = mysql_real_escape_string($moverA_id);
        $moverB_id = mysql_real_escape_string($moverB_id);
        $A_sql = sprintf("SELECT id,p_sort AS sort_num FROM ".USER_PHOTO." WHERE id = '%s' AND is_delete = '1' LIMIT 1 ",$moverA_id);
        $B_sql = sprintf("SELECT id,p_sort AS sort_num FROM ".USER_PHOTO." WHERE id = '%s' AND is_delete = '1' LIMIT 1 ",$moverB_id);
        $moverA_sort_num = $this->getSortNum($A_sql);
        $moverB_sort_num = $this->getSortNum($B_sql);

        if($moverA_sort_num < $moverB_sort_num){
            $sql = sprintf("UPDATE ".USER_PHOTO." 
                SET p_sort = p_sort - 1
                WHERE aid = '%s' 
                AND p_sort > '%d'
                AND p_sort <= '%d' ",$album_id,$moverA_sort_num,$moverB_sort_num);
            $this->db->query($sql);
        }else{
            $sql = sprintf("UPDATE ".USER_PHOTO." 
                SET p_sort = p_sort + 1
                WHERE aid = '%s' 
                AND p_sort >= '%d'
                AND p_sort < '%d' ",$album_id,$moverB_sort_num,$moverA_sort_num);
            $this->db->query($sql);
        }
        
        $sql = sprintf("UPDATE ".USER_PHOTO." 
            SET p_sort = '%d'
            WHERE id = '%s' ",$moverB_sort_num,$moverA_id);
        
		return $this->db->query($sql);
    }
    
	/**
     * 获得相册中符合条件的网页封面照片
     * 
     * @author vicente
     * @access public
     * @param int $uid 用户id
     * @param int $web_id 网页id
     * @return boolean
     */
    public function getMainCover($uid, $web_id)
    {
        $sql = "select id from ".USER_ALBUM."
            where uid = '{$uid}' and web_id = '{$web_id}' and is_delete = '1' and a_type = '0' and photo_count > '0' and object_type = '1'
            order by id desc";
        $res = $this->db->query($sql);
        $ids = $res->result_array($res);

        if(empty($ids)){
            throw new MY_Exception("错误请求！");
        }else{
        	foreach($ids as $v){
        		$id_s[] = $v['id'];
        	}
            $sql = "select id from ".USER_PHOTO."
                where aid  in ('".implode("','", $id_s)."') and is_delete = '1' and is_maincover = '1' limit 1";

            $res = $this->db->query($sql);
            $photo_info = $res->result_array($res);
            if(empty($photo_info)) {
                return false;
            }
            
            return true;
        }
    }

    /**
     * 设置个人主页应用区封面
     * 
     * @author vicente
     * @access public
     * @param int $id 应用区封面id
     * @param int $uid 用户id
     * @return boolean
     */
    public function setMainCover($photo_id, $uid) 
    {
        $data = array('is_maincover' => '0');
        $this->db->where('uid', $uid);
        $this->db->update(USER_PHOTO, $data);
        $data = array('is_maincover' => '1');
        $this->db->where('id', $photo_id);
        $this->db->where('uid', $uid);
        return $this->db->update(USER_PHOTO, $data);
    }
    
    /**
     * 获取用户在某网页中的照片总数
     * 
     * @author vicente
     * @access public
     * @param int $uid 用户id
     * @param int $web_id 网页id
     * @return boolean
     */
    public function getPhotoNum($uid, $web_id)
    {
    	$photo_num = 0;   	
    	$sql = sprintf("SELECT id FROM ".USER_ALBUM."
            WHERE uid = '%s' and web_id = '%s' and is_delete = 1", $uid, $web_id);
        $res = $this->db->query($sql);
        $album_ids = $res->result_array();

        if(!empty($album_ids)){
        	$return = array();
        	foreach($album_ids as $v){
        		$return[] = $v['id'];
        	}
        	
        	$this->db->select(array('id'));
        	$this->db->where('uid', $uid);
        	$this->db->where('is_delete', 1);
        	$this->db->where_in('aid', $return);
        	$query = $this->db->get(USER_PHOTO);
        	$photo_num = $query->num_rows();
        }

        return $photo_num;
    }
    
	/**
     * 上传单张，生成新的照片信息流
     * 
     * @author vicente
     * @access public
     * @param int $dkcode dkcode
     * @param int $id 照片id
     * @param int $time 上传照片时间戳
     * @param string $web_name 网页名称
     * @param int $web_id 相册id
     * @param boolean $update_time 是否更新时间
     * @return array
     */
    public function updatePhotoInfoFlow ($dkcode, $id, $time, $web_name, $web_id, $update_time = true)
    {
        $photo_into = $this->get($id);

        if(empty($photo_into)){
			throw new MY_Exception("照片信息有误！");
        }

        $size = empty($photo_into['notes']) ? array() : json_decode($photo_into['notes'], true);
        $picurls[] = array(
        	'pid'       => $photo_into['id'], 
        	'groupname' => $photo_into['groupname'], 
        	'filename'  => $photo_into['filename'], 
        	'type'      => $photo_into['type'], 
        	'size'      => $size
        );

        //$content = '<a href = '.mk_url('walbum/photo/index', array('albumid' => $photo_into['aid'], 'web_id' => $web_id)).' >'.$photo_into['name'].'</a>';
		$content = array(
			'm' => 'walbum',
			'c' => 'photo',
			'a' => 'index',
			'params' => array('albumid' => $photo_into['aid'], 'web_id' => $web_id),
			'title'  => $photo_into['name']
		);
        
        $infoflow_data = array(
        	'uid'          => $photo_into['uid'],
        	'title'        => $photo_into['name'],
            'dkcode'       => $dkcode,
            'uname'        => $web_name,
        	'pid'          => $web_id,
            'fid'          => $id, 
            'content'      => json_encode($content),
        	'timedesc'     => '',
            'type'         => 'photo',
            'picurl'       => json_encode($picurls),
        	'note'         => $photo_into['aid']
        );
        
        //更新信息流
		$update_time && $infoflow_data['dateline'] = date('YmdHis');

        updateWebTimeLine($infoflow_data, $web_id, false);
//        if($info === false){
//        	throw new MY_Exception("信息流更新失败！");
//        }
    }
    
	/**
     * 删除照片信息流
     * 
     * @author vicente
     * @access public
     * @param int $dateline 时间戳
     * @param int $web_id 网页ID
     * @return boolean
     */
    public function delPhotoInfoFlow($id, $web_id)
    {	
    	if(checkWebTimeline($id, $web_id, 'photo')) {
	        return delWebTimeLine($id, $web_id, 'photo');
    	}
    	
    	return false;
    }
    
    
    /**
     * 检查此时间线的图片是单张上传还是多张上传
     * 
     * @author vicente
     * @access public
     * @param int $album_id 相册ID
     * @param int $dateline 时间戳
     * @param int $id 照片id
     * @return boolean
     */
    public function isPhotoInfo($album_id, $dateline, $id)
    {
    	$params = array(
    		'where'  => array(
    			'aid'        => $album_id,
    			'dateline'   => $dateline,
    			'not_id'     => $id
    		)
    	);
    	$count = $this->count($params);
    	if($count >= 1) return false;

        return true;
    }
    
    /**
     * 旋转照片
     * 
     * @author vicente
     * @access public
     * @param int $id 照片ID
     * @param int $degree 角度
     * @return array
     */
	public function rotate($id, $degree)
    {
    	$pic_type = 0;
    	$pic_info = $this->get($id);
    	if(empty($pic_info)){
    		throw new MY_Exception("照片信息有误！");
    	}

    	$this->config->load('album');
		$pic_conf = $this->GetThumbConf($pic_type); //大  中  小  尺寸 配置
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
        	throw new MY_Exception("服务器太忙，请重试！");
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
		
		$this->db->where('id', $id);
		$this->db->update(USER_PHOTO, $pt);
		
		return array(
			'picUrl'   =>$img_b,
			'dateline' => $pic_info['timestamp'],
			'aid'      => $pic_info['aid']
		);
    }
    
	/**
	 * @author vicente
	 * @param int $type 相册类型
	 * 
	 * @reutrn array 相册缩略图配置
	 */
	function GetThumbConf($type)
	{
		$this->config->load('album');
		switch($type){
			case 1 : //个人头像
				$thumb_config = $this->config->item('thumb_head_sizes');
				break;
			case 2 : //相册封面
				$thumb_config = $this->config->item('thumb_cover_sizes');
				break;
			case 3 : //配图相册
				$thumb_config = $this->config->item('thumb_other_sizes');
				break;
			default: //普通相册
				$thumb_config = $this->config->item('thumb_pic_sizes');
		}
		
		return $thumb_config;
	}
}
