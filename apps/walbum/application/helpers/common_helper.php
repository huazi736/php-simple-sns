<?php
/**
 * 函数库
 * 
 * @author vicente
 * @date <2012-02-10>
 * @version $Id
 */


	function getDefaultAlbumCover()
	{
	    return MISC_ROOT.'img/default/album_default.png';
	}

	/**
	 * 获得图片后缀
	 * 
	 * @author weijian
	 * @param stirng $type
	 */
	function getImgRealType($type) {
	    if($type == 1){
	        return 'gif';
	    }elseif($type == 2){
	        return 'jpg';
	    }elseif($type == 3){
	        return 'png';
	    }else{
	        return false;
	    }
	}
	
		/**
	 * 获得图片后缀
	 * 
	 * @author weijian
	 * @param stirng $type
	 */
	function getImgType($type) {
	    if(strpos($type, 'gif') !== false){
	        return 'gif';
	    }elseif(strpos($type, 'png') !== false){
	        return 'png';
	    }elseif(strpos($type, 'jpg') !== false){
	        return 'jpg';
	    }elseif(strpos($type, 'jpeg') !== false){
	        return 'jpg';
	    }else{
	        return false;
	    }
	}

	/**
	 * 获得用户首页的URL
	 * 
	 * @author weijian
	 * @param string $uid 用户编号
	 */
	function getUserUrl($dkcode)
	{
		return mk_url('main/index/main', array('dkcode' => $dkcode));
	}

	/**
	 * 获得网页首页的URL
	 * 
	 * @author weijian
	 * @param string $uid 用户编号
	 */
	function getWebUrl($web_id = 0)
	{
		return mk_url('webmain/index/main',array('web_id'=>$web_id));
	}

	/**
	 * 得到图片路径
	 * 
	 * @author weijian
	 * @param string $group 组名
	 * @param string $filename 文件名（不带后缀）
	 * @param string $ext 文件后缀
	 * @param string $thumb 缩略图名称，如果为空则表示原图
	 */
	function getImgPath($group, $filename, $ext, $thumb = null) 
	{
		$filename = null === $thumb ? $filename : $filename."_".$thumb;
		return "http://".config_item('fastdfs_domain')."/".$group."/".$filename.".".$ext;
	}
	
	/**
	 * 得到远程图片路径
	 * 
	 * @author vicente
	 * @param string $filename 名称
	 * @param string $ext 文件后缀
	 * @param string $type 类型
	 * @param string $romote_img_url 远程服务器
	 */
	function getImgRomotePath($filename, $ext, $type, $day_file, $romote_img_url) 
	{
		$name = trim(substr($filename, strrpos($filename, '/')+1));
		return $romote_img_url."/".$day_file."/".$name."_".$ext.'.'.$type;
	}
	
	/**
     * 判断对应信息流是否存在
     * 
     * @author vicente
     * @data   2012-08-02
     * @access public
     * @param int $fid 主键id
     * @param int $web_id 网页ID
     * @param string $type 类型
     * @return boolean
     */
    function checkWebTimeline($fid, $web_id, $type = 'album') 
    {
    	return api('WebTimeline')->checkTopicExists($fid, $type, $web_id);
    }
    
    /**
     * 更新信息流
     * 
     * @author vicente
     * @data   2012-08-02
     * @access public
     * @param array $data 信息主体
     * @param int $web_id 网页ID
     * @param string $update 更新 | 替代
     * @return boolean
     */
    function updateWebTimeLine($data, $web_id = 0, $update = true) 
    {
    	if($update === true) return api('WebTimeline')->updateWebtopicByMap($data);
    	
    	$tags = getWebCategoryTags($web_id);
    	
    	return api('WebTimeline')->replaceWebtopicByMap($data, $tags);
    }
    
    /**
     * 删除对应信息流是否存在
     * 
     * @author vicente
     * @data   2012-08-02
     * @access public
     * @param int $fid 主键id
     * @param int $web_id 网页ID
     * @param string $type 类型
     * @return boolean
     */
    function delWebTimeLine($fid, $web_id, $type = 'album') 
    {
    	$tags = getWebCategoryTags($web_id);
    	
    	return api('WebTimeline')->delWebtopicByMap($fid, $type, $tags, $web_id);
    }
    
    /**
     * 获取网页的相关信息
     * 
     * @author vicente
     * @data   2012-08-02
     * @access public
     * @param int $web_id 网页ID
     * @return array
     */
    function getWebCategoryTags($web_id)
    {
    	$tags = api('Interest')->get_web_category_imid($web_id);
    	
    	return $tags;
    }

	/**
	 * 判断两个数组是否相等
	 * 
	 * @author vciente
	 * @param array $arr1
	 * @param array $arr2
	 * @return boolean
	 */
    function checkArrayEqual($arr1, $arr2)
    {
        if(array_diff($arr1, $arr2) || array_diff($arr2, $arr1))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
	 * 生成排序sql语句
	 * 
	 * @author vciente
	 * @param array $orderby 排序规则
	 * @param string $sign 对应的标识，读取配置文件里面对应的信息
	 * @return string
	 */
	function getOrderBy($orderby = array(), $sign, $orderby_arr)
	{
		$order_sql = '';
		if(empty($orderby_arr)) return $order_sql; 
        $orderby_arr = $orderby_arr[$sign];
		if(!empty($orderby) && !empty($orderby_arr)){
            foreach($orderby_arr as $k=>$v){
                if(is_array($v)){
                    if(checkArrayEqual($orderby, $v)){
                        $orderby = $v;
                        break;
                    }
                }else{
                    $orderby = $orderby_arr[0];
                }
            }
		}else 
		{
			$orderby = $orderby_arr[0];
		}
		
		foreach ($orderby as $key=>$val){
			if(is_string($val)) {
				$order_sql .= " ORDER BY {$key} {$val}";
				break;
			}
		}
		
		return $order_sql;
	}
	
	/**
	 * 生成分页sql语句
	 * 
	 * @author vciente
	 * @param array $params 排序规则
	 * @return string
	 */
	function gePageSize($params = array())
	{
		$sql = '';
		if(isset($params['pagesize']) && !empty($params['pagesize'])){
            $params['pagesize'] = intval($params['pagesize']);
        }else{
            $params['pagesize'] = 16;
        }
	    //如果每页显示数为0，则表示取全部内容
        if($params['pagesize'] > 0){
            if(isset($params['page']) && $params['page'] > 0){
                $params['page'] = intval($params['page']);
            }else{
                $params['page'] = 1;
            }
            $sql .= " LIMIT ".(($params['page']-1) * $params['pagesize']).",".$params['pagesize'];
        }
        
        return $sql;
	}