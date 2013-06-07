<?php



class GoodsModel extends MY_Model{
	
	public $goods_page_size = 20;	// 每一次加载多少数据

	
	
	function __construct(){
		parent::__construct();
		//$this->mycache 	= $this->memcache;
		
	}
	
	
	
	
	/**
	 * 获得 商品数据
	 * **/
	public function get_goods_info( $web_id , $page){
		$page--;
		if($page<=0) $page = 0;
		$limit_page	= $page * $this->goods_page_size;
		
		$sql = "select * from goods where web_id={$web_id} ORDER BY `id` DESC  limit {$limit_page},".$this->goods_page_size;
		
		return $this->db->query($sql)->result_array();
		
	}
	
	// 获得 单个商品的数据
	public function get_goods_one($gid){
		// $sql 	= "select * from goods where id='{$gid}' limit 1 ";
		$sql	= "select g.*,b.name as bname, b.gbid , b.category_group as bcategory_group from goods as g left join goods_brand as b on (g.brand_id=b.gbid) where g.id='{$gid}' limit 1";
		return $this->db->query($sql)->row_array();
		
	}
	
	public function delete_goods($gid){
		$sql 	= "delete FROM `goods` where id={$gid} limit 1";
		return $this->db->query($sql);
	}
	
	
	
	
	
	
	/***
	 * 更新商品
	 * $data	数据
	 * $gid		商品id
	 * **/
	function update_goods($data,$gid){
		$this->__update('goods', $data , ' id='.$gid);
		
	}
	
	
	function addGoods($data) {
		return $this->db->insert('goods', $data);
	}
	
	
	// 添加品牌 并会回 id
	function add_goods_brand($name,$category_group){
		$arr['name'] 	= $name;
		$arr['category_group'] 	= $category_group;
		$arr_category	= explode('_',$category_group);
		$arr['imid'] 	= @$arr_category[0];
		$arr['iid'] 	= @$arr_category[1];
		if( count($arr_category)>2 ){
			$arr['eid'] = @array_pop($arr_category);
		}else{
			$arr['eid'] = "0";
		}
		$sql	= "select * from `goods_brand` where name='".$arr['name']."' and imid='".$arr['imid']."' and iid='".$arr['iid']."' and eid='".$arr['eid']."' limit 1 ";
		$result	= $this->__query($sql);
		if( count($result)>=1 ){
			return @$result[0]['gbid'];
		}else{
			$arr	= $this->__insert('goods_brand', $arr);
			return $this->__insert_id();
		}
	}
	
	// 获得品牌名
	function get_goods_brand_name($brand_id){
		$sql	= "SELECT * FROM `goods_brand` where category_group='{$brand_id}' limit 1";
		$result	= $this->__query($sql);
		return @$result[0]['name'];
		
	}
	

	function getGoodsList() {
		//$this->goodsdb->where('id',2);
		/* $this->goodsdb->order_by('utime desc');
		$goodsList = $this->goodsdb->get('goods')->result_array();

		foreach($goodsList as $goods) {
			$cat = $this->checkData($goods['iid']);
			$user = call_soap('ucenter', 'User', 'getUserInfo',array($goods['uid'], 'uid', array('username')));
			$category = call_soap('interest','Index','get_category_level_name',array('25', '2'));
			
			$gs['username'] = $user['username'];
			$gs['catname'] = $category['name'];
			$gs['name'] = $goods['name'];
			$gs['link'] = $goods['link'];
			$gs['price'] = $goods['price'];
			$gs['description'] = $goods['description'];
			$gs['pic'] =  explode(',', $goods['pics']);
			$gs['ctime'] = $goods['ctime'];
			$gs['utime'] = $goods['utime'];
			$goods_list[] = $gs;
		}

		return $goods_list ? $goods_list : array(); */
	}
	
	function updGoodsById($id, $data) {
		$this->$goodsdb->where('id', $id);
		$this->$goodsdb->update('goods', $data);
	}
	
	function delGoodsById($id) {
		$this->$goodsdb->where('id', $id);
		$this->$goodsdb->delete('goods');
	}
	
	function test() {
		$sql 	= "select * from goods where id=253";
		$arr	= $this->db->query($sql)->row_array();
		echo "<pre>";
		print_r(json_decode($arr['pics'],true) );
		/*
		$fastdfs = getConfig('fastdfs', 'default');
		echo '----';
		echo $fastdfs['host'];
		*/
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
 
    
/*********   sql 数据库操作	start   *************/
	/**
	 * 查询
	 * **/
	private function __query($sql){
		return $this->db->query($sql)->result_array();
	}
	
	private function __insert_id(){
		
		return $this->db->insert_id();
	}
	
	/**
	 * 删除
	 * **/
	private function __delete($sql){
		return $this->db->query($sql);
	}
	
	// 执行 sql 语句
	private function __execute($sql){
		return $this->db->query($sql);
	}
	
	
	/**
	 * 插入数据
	 * $table		表
	 * $arr 		要插入的数据		array(key=>value) key 字段	 value 值
	 * $del_null	删除 arr 中空的 字段    ， 这里是因为  字段是数据型如果生成一个'' 数据插入进去    就会有错误
	 * 
	 * return  0 表示没有插入成功
	 * **/
	private function __insert($table , $arr, $del_null=true){
		if(!is_array($arr))	return 0;
		if($del_null){
			foreach($arr as $key=>$val){
				if($val===null || $val ==='')	unset($arr[$key]);	// if(!$val) 不能这样   因为 0 也会被删除 
			}
		}
		$sql 	= "INSERT INTO `{$table}`(`" . implode('`,`',array_keys($arr)) . "`)VALUES ('". implode("','",array_values($arr)) ."')";
		return $this->db->query($sql);
		
	}
	
	/**
	 * 忽略重复  插入
	 * $table		表
	 * $arr 		要插入的数据		array(key=>value) key 字段	 value 值
	 * $del_null	删除 arr 中空的 字段    ， 这里是因为  字段是数据型如果生成一个'' 数据插入进去    就会有错误
	 * 
	 * return  0 表示没有插入成功
	 * **/
	private function __insert_ignore($table , $arr , $del_null=true){
		if(!is_array($arr)) return 0;
		if($del_null){
			foreach($arr as $key=>$val){
				if($val===null || $val ==='')	unset($arr[$key]);	// if(!$val) 不能这样   因为 0 也会被删除 
			}
		}
		$sql	= "INSERT IGNORE INTO `{$table}`(`" . implode('`,`',array_keys($arr)) . "`)VALUES ('". implode("','",array_values($arr)) ."')";
		return $this->db->query($sql);
		
	}
	
	
	
	/**
	 * 更新数据
	 * $table 		表
	 * $arr			要插入的数据
	 * $is_one		是否只更新一条	true 只更新一条 	false 可以更新多条
	 * 
	 * 返回  	1,2     0 表示没有更新成功
	 * **/
	private function __update($table , $arr , $where=null , $is_one=false ){
		if(!is_array($arr)) return 0;
		
		$where_val	= "";
		if($where)	$where_val = " where ".$where;
		$limit_val 	= "";
		if($is_one)	$limit_val = " limit 1";
		
		$set_val	= $this->__create_update($arr);
		$sql		= "update {$table} set {$set_val} {$where_val} {$limit_val} ";
		return $this->db->query($sql);
		
	}
	
	
	
	/*
	 * 变换 生成 更新的 sql 语句  的内容
	 * $arr 	==> array('field'=>'val',....);
	 * return   ==> `field1`='val1',`field2`='val2'
	 */
	private function __create_update($arr){
		//if(! is_array($arr)) return '';
		foreach($arr as $key=>$val){
			$set[] = " `{$key}`='{$val}' ";
		}
		return @ implode(',',$set);
	}
	
	
	
	
	
}



