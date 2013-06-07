<?php
/**
 * 相册照片model
 *
 * @author        vicente
 * @version       $Id
 */
class AlbumModel extends MY_Model
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
    	$where_sql = " WHERE 1 = 1";

        //用户
	    if(isset($params['uid']) && $params['uid']){
	        if(is_array($params['uid'])){
                $where_sql .= " AND uid in ('".implode("','", $params['uid'])."')";
            }else{
    	        $where_sql .= " AND uid = '{$params['uid']}'";
            }
	    }
	    
		//相册id
    	if(isset($params['id']) && $params['id']){
            if(is_array($params['id'])){
                $where_sql .= " AND id in ('".implode("','", $params['id'])."')";
            }else{
                $where_sql .= " AND id = ".$this->db->escape($params['id'])."";
            }
        }
	    
	    //网页id
	    if(isset($params['web_id']) && $params['web_id']){
	    	$where_sql .= " AND web_id = '{$params['web_id']}'";
	    }
	    
	    //相册类型
	    if(isset($params['a_type']) && is_numeric($params['a_type'])){
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
        
        return $where_sql;
    }
    
	/**
     * 相册列表
     * 
     * @author vicente
   	 * @access public
     * @param array $params 参数列表
     * @return array 相册信息
     */
	public function index($params)
	{
	    $return = array();
	    $list_sql = "SELECT id, name, uid, dateline, last_dateline, photo_count, cover_id, a_type, a_address, is_delete, discription, object_type, object_content FROM ".USER_ALBUM;
	    
	    $where_sql = $this->getQuerySql($params['where']);
        $orderby = isset($params['orderby']) && !empty($params['orderby']) ? $params['orderby'] : array();
        $this->config->load('album');
		$orderby_arr = $this->config->item('orderby');

        $where_sql .= getOrderBy($orderby, 'album', $orderby_arr);
        if(isset($params['limit'])){
            $where_sql .= gePageSize($params['limit']);
        }

        $res = $this->db->query($list_sql.$where_sql);
        $album_list = $res->result_array();

		foreach($album_list as $k => $v){
    		//默认相册封面地址
    		if(!empty($v['cover_id'])) {
    			$photo_info = $this->photo->get($v['cover_id']);
    			//如果此相片不存在，使用默认封面
                if(is_array($photo_info)){
                	$v['album_cover'] = $photo_info['img_f'];
                }else{
                    $v['album_cover'] = getDefaultAlbumCover(); 
                }
    		} else {
    			$v['album_cover'] = getDefaultAlbumCover(); 
    		}

    		$v['dateline'] = date('Y-m-d',$v['dateline']);
    		$v['last_dateline'] = date('Y-m-d',$v['last_dateline']);
    		$return[] = $v;
        }
        
        return $return;
	}

	/**
     * 相册数量
     * 
     * @author vicente
   	 * @access public
     * @param array $params 参数列表
     * @return int 相册数量
     */
    public function count($params)
    {	
	    $where_sql = $this->getQuerySql($params['where']); 
        $num_sql = "SELECT COUNT(*) AS num FROM " . USER_ALBUM . " ". $where_sql;
        $num_res = $this->db->query($num_sql);
        $num_row = $num_res->row_array();

        return $num_row['num'];
    } 

    /**
     * 新增相册
     * 
     * @author vicente
     * @access public
     * @param array $request_data 提交信息数组
     * @return int 相册id
     */
    public function add($request_data)
    {
    	$name = '';
    	if(isset($request_data['newAlbumName'])){
        	$name = htmlspecialchars($request_data['newAlbumName'], ENT_QUOTES);
        	$name = strlen($name) > 50 ? mb_substr($name, 0, 50, 'utf-8') : $name;
        }
        
        $description = '';
        if(isset($request_data['newAlbumdesc']) && !empty($request_data['newAlbumdesc'])){
        	$description = htmlspecialchars($request_data['newAlbumdesc'], ENT_QUOTES);
        	$description = strlen($description) > 140 ? mb_substr($description, 0, 140, 'utf-8') : $description;
        }
        
        $a_address = '';
        if(isset($request_data['a_address']) && !empty($request_data['a_address'])){
            $a_address = htmlspecialchars($request_data['a_address'], ENT_QUOTES);
            $a_address = strlen($a_address) > 50 ? mb_substr($a_address, 0, 50, 'utf-8') : $a_address;
        }

        $sql = sprintf("SELECT max(a_sort) AS max_sort FROM ".USER_ALBUM." WHERE uid = '%s' LIMIT 1 ", $request_data['uid']);
        $query = $this->db->query($sql);
        $list = $query->row_array();
        if(!empty($list)){
            $a_sort = $list['max_sort'] + 1;
        }else{
            $a_sort = 1;
        }
        
        $a_type = isset($request_data['a_type']) ? $request_data['a_type'] : 0;
        $uid = !isset($request_data['uid']) || empty($request_data['uid']) ? ACTION_UID : $request_data['uid'];

        $data= array(
            'uid'           => $uid,
            'name'          => $name,
            'a_address'     => $a_address,
            'a_sort'        => $a_sort,
        	'a_type'        => $a_type,
            'discription'   => $description,
            'dateline'      => time(),
            'last_dateline' => time(),
            'web_id'        => $request_data['web_id']
        );
        $this->db->insert(USER_ALBUM, $data);
        $id = $this->db->insert_id();

        return $id;
    }
    
    /**
     * 获取相册信息
     *
     * @author vicente
     * @access public
     * @param int $id 照片id
     * @return array 照片信息
     */
    public function get($id)
    {
        $this->db->where('id',$id);
        $this->db->where('is_delete','1');
        $query = $this->db->get(USER_ALBUM);
        $data = $query->row_array();
        if(!empty($data)){
            $data['last_dateline'] = friendlyDate($data['last_dateline']);
        }

        return $data;
    }
    
    /**
     * 编辑相册
     * 
     * @author vicente
     *
     * @access public
     * @param array $request_data 提交信息数组
     * @return boolean
     */
    public function edit($request_data)
    {
        $this->db->where('id', $request_data['albumID']);
        if(isset($request_data['albumName'])){
        	$request_data['albumName'] = htmlspecialchars($request_data['albumName']);
            $data['name'] = mb_substr($request_data['albumName'], 0, 50, 'utf-8');
        }
        if(isset($request_data['albumExplain'])){
        	$request_data['albumExplain'] = htmlspecialchars($request_data['albumExplain']);
            $data['discription'] = mb_substr($request_data['albumExplain'], 0, 140, 'utf-8');
        }

        return $this->db->update(USER_ALBUM, $data);
    }
    
    /**
     * 通过相册ID删除该相册，相册中的照片也将被删除
     *
     * @author vicente
     * @access public
     * @param int $id 相册ID
     * @return boolean
     */
    public function delete($id)
    {
        $update_data['is_delete'] = '0';
        $this->db->where('id',$id);
        $this->db->where('a_type','0');
        $this->db->update(USER_ALBUM,$update_data);

        $photo_data = array('id');
        $this->db->select($photo_data);
        $this->db->where('aid',$id);
        $photo_query = $this->db->get(USER_PHOTO);
        $photo_list = $photo_query->result_array();

        if(!empty($photo_list)){
            $this->db->where('aid',$id);
            $this->db->update(USER_PHOTO,$update_data);
        }
        
        return true;
    }
    
    /**
     * 编辑相册描述
     *
     * @author vicente
     * @access public
     * @param int $id 照片ID
     * @param string $desc 照片名字
     * @return boolean
     */
    public function editDesc($id, $desc)
    {
    	$desc = mb_substr($desc, 0, 140, 'utf-8');
        $desc = htmlspecialchars($desc, ENT_QUOTES);
        $update_data['discription'] = $desc;
        $this->db->where('id',$id);
        $this->db->where('is_delete','1');
        
        return $this->db->update(USER_ALBUM,$update_data);
    }
    
    /**
     * 设置照片为此相册的封面
     * 
     * @author vicente
     * @access public
     * @param int $id 相册ID
     * @param int $photo_id 照片ID
     * @return boolean
     */
    public function setAlbumCover($id, $photo_id)
    {
        $data = array('id', 'aid');
        $this->db->select($data);
        $this->db->where('id',$photo_id);
        $this->db->where('is_delete',1);
        $this->db->limit(1);
        $photo_query = $this->db->get(USER_PHOTO);
        $photo_info = $photo_query->row_array();

        if(empty($photo_info) || $photo_info['aid'] != $id){
            throw new MY_Exception("照片信息有误！");
        }

        $update_data['cover_id'] = $photo_id;
        $this->db->where('id',$id);
        
        return $this->db->update(USER_ALBUM, $update_data);
    }
    
    /**
     * 自动更新相册排序顺序
     * 
     * @author vicente
     * @access public
     * @param int $uid 用户ID
     * @param int $aid 相册ID
     * @return boolean
     */
    public function autoUpdateAlbumOrder($uid, $id)
    {
        $sql = "select id from ".USER_ALBUM."
            where uid = ? and is_delete = '1'
            order by a_sort desc
            limit 1";
        $res = $this->db->query($sql, array($uid));
        $row = $res->row_array();
        if(empty($row) || $row['id'] == $id){
            return true;
        }else{
            return $this->orderAlbum($uid, $id, $row['id']);
        }
    }

    /**
     * 相册排序 ，将相册A的排序为最大
     *
     * @author vicente
     * @access public
     * @param int $uid 用户ID
     * @param int moverA_id 出发点
     * @param int moverB_id 到达点
     * @return boolean
     */
    public function orderAlbum($uid,$moverA_id,$moverB_id)
    {
        $moverA_id = mysql_real_escape_string($moverA_id);
        $moverB_id = mysql_real_escape_string($moverB_id);
        $A_sql = sprintf("SELECT id,a_sort AS sort_num FROM ".USER_ALBUM." WHERE id = '%s' AND is_delete = '1' LIMIT 1 ",$moverA_id);
        $B_sql = sprintf("SELECT id,a_sort AS sort_num FROM ".USER_ALBUM." WHERE id = '%s' AND is_delete = '1' LIMIT 1 ",$moverB_id);
        $moverA_sort_num = $this->getSortNum($A_sql);
        $moverB_sort_num = $this->getSortNum($B_sql);

        if($moverA_sort_num < $moverB_sort_num){
            $a_sql = sprintf("UPDATE ".USER_ALBUM." 
                SET a_sort = a_sort - 1
                WHERE uid = '%s' 
                AND a_sort > '%d'
                AND a_sort <= '%d' ",$uid,$moverA_sort_num,$moverB_sort_num);
            $this->db->query($a_sql);
        }else{
            $b_sql = sprintf("UPDATE ".USER_ALBUM." 
                SET a_sort = a_sort + 1
                WHERE uid = '%s' 
                AND a_sort >= '%d'
                AND a_sort < '%d' ",$uid,$moverB_sort_num,$moverA_sort_num);
            $this->db->query($b_sql);
        }
        
        $sql = sprintf("UPDATE ".USER_ALBUM." 
            SET a_sort = '%d'
            WHERE id = '%s' ",$moverB_sort_num,$moverA_id);

        return $this->db->query($sql);
    }

    /**
     * 取得相册排序序号
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
            throw new MY_Exception("相册信息有误！");
        }
        return $list['sort_num'];
    }
    
	/**
     * 重置相册的封面，如果有照片，则选择第一张照片，如果没有照片则为空
     * 
     * @author vicente
     * @access public
     * @param int $id 相册ID
     * @param boolean 是否为空
     * @return boolean
     */
    public function resetAlbumCover($id, $clean = false)
    {
        if($clean){
            $update_data['cover_id'] = '';
        }else{
            $params = array(
                'where' => array(
                    'aid'          => $id,
                    'is_delete'    => 1
                ),
                'orderby'  => array(
                    'p_sort'         => 'desc'
                ),
                'limit'    => array(
                    'pagesize'     => 1
                 ),
            );
            $photo_list = $this->photo->index($params);
            if(empty($photo_list)){
                $update_data['cover_id'] = '';
            }else{
                $list = array_shift($photo_list);
                $update_data['cover_id'] = $list['id'];
            }
        }
        $this->db->where('id',$id);
        return $this->db->update(USER_ALBUM,$update_data);
    }
    
    /**
     * 检查此照片是否是相册的封面，如果是重新设置相册封面
     * 
     * @author vicente
     * @access public
     * @param int $id 相册id
     * @param int $photo_id 照片id
     */
    public function checkAlbumCover($id, $photo_id)
    {
        $album_info = $this->get($id);
        if(empty($album_info)){
            throw new MY_Exception("相册信息有误！");
        }
        if($album_info['cover_id'] == $photo_id){
            $this->resetAlbumCover($id);
        }
    }

    /**
     * 单个相册信息流更新
     * 
     * @author vicente
     * @access public
     * @param int $id 相册ID
     * @param int $web_id 网页ID
     * @return boolean
     */
    public function delAlbumInfosFlow($id, $web_id) 
    {
    	$set_data = array();
        $timestamps = $this->getAlbumTimestamp($id);

        //分别删除相册的信息流和照片的信息流
        foreach ($timestamps as $timestamp) {
        	if($timestamp['num'] == 1) {
        		$this->photo->delPhotoInfoFlow($timestamp['id'], $web_id);
        	}
        }
        
        return $this->delAlbumInfoFlow($id, $web_id);
    }
    
    /**
     * 获得相册时间戳 更新信息流使用
     * 
     * @author vicente
     * @access public
     * @param int $id 相册id
     * @return array 
     */
    public function getAlbumTimestamp($id)
    {
        $sql = "SELECT id,aid,dateline, count(*) as num FROM ".USER_PHOTO."
        		WHERE aid = ?
        		GROUP BY dateline";
        $res = $this->db->query($sql, array($id));
        $list = $res->result_array();
       
        return $list;
    }

    /**
     * 删除相册的信息流信息
     * 
     * @author vicente
     * @data   2012-05-14
     * @access public
     * @param int $id 相册id
     * @param int $web_id 网页ID
     * @return boolean
     */
    public function delAlbumInfoFlow($id, $web_id) 
    {	
    	if(checkWebTimeline($id, $web_id, 'album')) {
	        return delWebTimeLine($id, $web_id, 'album');
    	}
    	
    	return false;
    }

    /**
     * 上传照片更新相册信息流[存在此相册信息流，更新。不存在相册信息流如果flag为false相册信息流没变化，为true信息流新增，此处由信息流来处理。耦合的一米]
     * 
     * @author vicente
     * @access public
     * @param int $dkcode dkcode
     * @param int $id 相册id
     * @param int $time 上传照片时间戳
     * @param string $web_name 网页名称
     * @param int $web_id 相册id
     * @param boolean $flag 不仅会新增也可能更新
     * @param boolean $update_time 是否更新时间
     * @return array
     */
    public function updateAlbumInfoFlow($dkcode, $id, $web_name, $web_id, $flag = true, $update_time = true)
    {	
		$album_info = $this->get($id);
        if(empty($album_info)) {
            throw new MY_Exception("相册信息有误！");
        }

        //取相册中最新的照片
        $data = array('id', 'name' ,'dateline','filename', 'type', 'groupname', 'notes');
        $this->db->select($data);
        $this->db->where('is_delete', 1);
        $this->db->where('aid', $id); 
        $this->db->where('uid', $album_info['uid']);
        $this->db->order_by('p_sort', 'DESC');
        $this->db->limit(8);
        $query = $this->db->get(USER_PHOTO);
        $photo_list = $query->result_array();

        //如果相册没有照片，删除此相册信息流
        if(empty($photo_list)) {
        	$this->delAlbumInfoFlow($id, $album_info['web_id']);
            return true;
        }
	
        foreach ($photo_list as $val) {
        	$size = array();
    		if(!empty($val['notes'])){
    			$size = json_decode($val['notes'], true);
    		}
			$picurls[] = array(
            	'pid'       => $val['id'], 
                'groupname' => $val['groupname'], 
                'filename'  => $val['filename'], 
                'type'      => $val['type'], 
                'size'      => $size
            );
		}
		
		//$content = '<a href = '.mk_url('walbum/photo/index', array('albumid' => $id, 'web_id' => $album_info['web_id'])).' >'.$album_info['name'].' ('.$album_info['photo_count'].')</a>';
 		$content = array(
			'm' => 'walbum',
			'c' => 'photo',
			'a' => 'index',
			'params' => array('albumid' => $id, 'web_id' => $album_info['web_id']),
			'title'  => $album_info['name'].' ('.$album_info['photo_count'].')'
		);
		
        //信息流结构体
		$infoflow_data = array(
        	'uid'       => $album_info['uid'],
        	'dkcode'    => $dkcode,
        	'uname'     => $web_name,
			'title'     => $album_info['name'],
        	'pid'       => $album_info['web_id'],
        	'fid'       => $id,
        	'photonum'  => $album_info['photo_count'], 
        	'picurl'    => json_encode($picurls),
        	'timedesc'  => '',
        	'content'   => json_encode($content),
            'type'      =>'album',
	        'note'      => $album_info['id']
		);
		
		$current_time = date('YmdHis');
		
		//更新信息流
		$update_time && $infoflow_data['dateline'] = $current_time;
		
		if($flag === true){
			//新增或修改信息流
			updateWebTimeLine($infoflow_data, $web_id, false);
		}else{
			//信息流存在才可以修改
			checkWebTimeline($id, $web_id, 'album') && updateWebTimeLine($infoflow_data);
		}
		
		service('comlike')->update_Like($id, 'web_album', $current_time);
    }

    /**
     * 验证照片是否是某个默认相册的照片
     * 专为API调用
     * 
     * @author vicente
     * @access public
     * @param int $id 照片编号
     * @param string $type 相册类型
     * @return boolean
     */
    public function checkAlbumType($photo_id, $type)
    {
        $sql = "select aid from ".USER_PHOTO." where id = ?";
        $res = $this->db->query($sql, array($photo_id));
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
     * 更新相册信息
     * 专为API调用
     * 
     * @author vicente
     * @access public
     * @param int $id 照片编号
     * @param int $uid 用户id
     * @param string $a_type 相册类型
     * @param array $update_data 更新数据
     * @return boolean
     */
    public function updateAlbum($id, $uid, $a_type = 0, $update_data)
    {
        $this->db->where('id', $id);
        $this->db->where('uid', $uid);
        $this->db->where('a_type', $a_type);
        if(isset($update_data['name'])){
            $update_data['name'] = mb_substr($update_data['name'], 0, 50, 'utf-8');
        }
        if(isset($update_data['a_address'])){
            $update_data['a_address'] = mb_substr($update_data['a_address'], 0, 140, 'utf-8');
        }
        if(isset($update_data['discription'])){
            $update_data['discription'] = mb_substr($update_data['discription'], 0, 140, 'utf-8');
        }
        
        return $this->db->update(USER_ALBUM,$update_data);
    }
    
	/**
     * 取得相册简要列表
     *
     * @author vicente
   	 * @data   2012-05-15
   	 * @access public
     * @param int $uid 用户uid
     * @param int $web_id 网页id
     * @param int $id 相册uid
     * @return array
     */
    public function getUserAlbums($uid = 0, $web_id, $id = 0)
    {
    	
        $data = array('id','name', 'a_type', 'a_address', 'discription', 'cover_id', 'object_type', 'object_content');
        $this->db->select($data);
        if(!empty($id)) {
        	$this->db->where('id',$id);
        }
        
    	if(!empty($uid)) {
        	$this->db->where('uid',$uid);
        }
        
        $this->db->where('is_delete','1');
        $this->db->where('web_id', $web_id);
        $this->db->order_by('a_sort', 'asc');
        $query = $this->db->get(USER_ALBUM);
        $list = $query->result_array();

        return $list;
    }
    
    /**
     * 检查此时间线的图片是单张上传还是多张上传
     * 
     * @author vicente
     * @access public
     * @param int $album_id 相册ID
     * @param int $dateline 时间戳
     * @return boolean
     */
    public function isExistInfo($id)
    {
    	$sql = "SELECT dateline, COUNT(*) AS num FROM ".USER_PHOTO." WHERE aid = '{$id}' GROUP BY dateline HAVING num > 1 limit 1";

    	$query = $this->db->query($sql);
    	$return = $query->row_array($query);
    	
    	if(!empty($return) && $return['num'] > 1) return true;

        return false;
    }
    
    /**
     * 获取网页下所有相册信息
     * 
     * @author vicente
     * @access public
     * @param int $web_id 相册ID
     * @return boolean
     */
    public function getWebAlbumList($web_id)
    {
    	$data = array('id');
        $this->db->select($data);
        
        $this->db->where('is_delete','1');
        $this->db->where('web_id', $web_id);
        $query = $this->db->get(USER_ALBUM);
        $list = $query->result_array();

        return $list;
    }
    
    /**
     * 批量删除相册
     *
     * @author vicente
     * @access public
     * @param int $id 相册ID
     * @return boolean
     */
    public function batch_delete($id_s)
    {
        $update_data['is_delete'] = '0';
        $update_data['photo_count'] = '0';
        $this->db->where_in('id', $id_s);
        $this->db->update(USER_ALBUM, $update_data);

        $photo_data = array('id');
        $this->db->select($photo_data);
        $this->db->where_in('aid', $id_s);
        $photo_query = $this->db->get(USER_PHOTO);
        $photo_list = $photo_query->result_array();

        if(!empty($photo_list)){
            $this->db->where_in('aid', $id_s);
            $this->db->update(USER_PHOTO,array('is_delete'=>'0'));
        }
        
        return true;
    }
}
