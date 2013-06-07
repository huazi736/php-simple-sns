<?php
if(!extension_loaded("mongo")) show_error("mongo extension don't install", 500);

/*
 配置示例
$config['mongo'] = array(
	"mongo_host" => "192.168.12.232",
	"mongo_port" => "27017",
	"mongo_auth" => "false",
	"mongo_user" => "",
	"mongo_pwd"	 => "",
	"mongo_db"   => "wiki_duankou",
	"mongo_cursor_timeout" => 3000,
	"options"  => array(
		"persist" => "wiki_duankou_persist",
	),
);
*/


/**
 * @desc mongo 操作类
 * @author lijianwei
 * @date 2012/04/26
 */
class Mongo_db {
  public $safe = true;  //是否等mongodb返回结果
  public $fsync = false; //是否强制mongodb缓存写入磁盘中,考虑1000次更新一次
  public $mongo = null;  //方便直接调
  public $mongodb = null; 
  public $mongocollection = null;
   
  private $selects = array();
  private $wheres = array();
  private $sorts = array();
	
  private $limit = 999999; 
  private $offset = 0;
	
   public function __construct() {
   		
		$this->mongo = Single_Mongodb::getInstance();
   		
   		$mongo_config = config_item("mongo");
   		$dbname = $mongo_config['mongo_db'];
   		$mongocursortimeout = $mongo_config['mongo_cursor_timeout'];
   		
   		$this->mongodb = $this->mongo->$dbname;
   		Mongocursor::$timeout = $mongocursortimeout;
   }
   
   /**
    * 设置查询数据超时时间和增删改safe模式下超时时间 
    * @param int $timeout
    */
   public function setMongoCursorTimeOut($timeout = 0) {
   		$timeout = intval($timeout);
   		Mongocursor::$timeout = $timeout;
   }
   
   public function getOptions() {
   		return array('fsync' => $this->fsync, 'safe' => $this->safe);
   }
	
	/**
	 * 选择数据库
	 * @param string $dbname
	 * @return true or false
	 */
	public function selectDB($dbname = "") {
		if ($dbname == '') {
			return (false);
		} else {
			return $this->mongodb = $this->mongo->selectDB ( $dbname );
		}
	}
	
	/**
	 * 创建数据库
	 * @param string $dbname
	 * @return true;
	 */
	protected function createDB($dbname = "") {
	     $this->selectDB($dbname)->execute("function(){}");
	     return true;
	}
	
	/**
	 * 删除数据库  慎用
	 * @param string $dbname
	 * @return 1 or 0
	 */
	private function dropDB($dbname = "") {
		$result = $this->selectDB($dbname)->drop();
		return $result['ok'];
	}
	
	/**
	 * 查询所有集合名称
	 * @return array(集合名称,,,)
	 */
	public function showCollections() {
		$list = array ();
		$data = array ();
		
		$list = $this->mongodb->listCollections ();
		if (count ( $list ) > 0) {
			foreach ( $list as $collection ) {
				$data [] = $collection->getName ();
			}
			return $data;
		}
		return array ();
	}
	
	/**
	 * 选择集合
	 * @param string $collectionName
	 * @return mongocollection  集合存在或不存在都返回一个非空的MongoCollection对象
	 */
	public function selectCollection($collectionName = "") {
		return $this->mongocollection = $this->mongodb->selectCollection ( $collectionName );
	}
   
   /**
    * 创建非固定集合
    * @param string $collectionName
    * @return mongocollection 集合存在返回已经存在的集合对象，不存在返回新创建的集合对象
    */
   public function createCollection($collectionName = "") {
   	   return $this->mongocollection = $this->mongodb->createCollection($collectionName);
   }
   
   /**
    * 删除集合   *****会删除集合中全部文档,请谨慎使用哦******
    * @param string $collectionName
    * @return 1 or 0  1成功  0失败 (不存在集合)
    */
   public function dropCollection($collectionName = "") {
   	    //$result = $this->mongodb->{$collectionName}->drop();
   		$result = $this->mongodb->dropCollection($collectionName);
   		return $result['ok'];
   }
   /**
    * 清空集合数据
    * return 1 or 0
    */
   public function clearCollection($collectionName = "") {
   		$result = $this->mongodb->{$collectionName}->remove(array(), $this->getOptions());
   		return $result['ok'];
   }
   /**
    * 查看集合中所有索引
    * @return array() or array(索引信息);
    */
   public function showIndexs($collection = "") {
		if (empty ( $collection )) {
			return array();
			//show_error ( "No Mongo collection specified to remove all indexes from", 500 );
		}
		return ($this->mongodb->{$collection}->getIndexInfo ());
	}
   
   /**
    * 添加索引
    * @param string $collection
    * @param array $keys array('username'=>1 or -1, ...)
    * @param array $options array('unique' =>1 , 'dropDups' =>1)
    * @return true or false
    */
   public function addIndex($collection = "", $keys = array(), $options = array()) {
		
		if (empty ( $collection )) {
			return false;
			//show_error ( "No Mongo collection specified to add index to", 500 );
		}
		
		if (empty ( $keys ) || ! is_array ( $keys )) {
			return false;
			//show_error ( "Index could not be created to MongoDB Collection because no keys were specified", 500 );
		}
		
		foreach ( $keys as $col => $val ) {
			if ($val == - 1 || $val === FALSE || strtolower ( $val ) == 'desc') {
				$keys [$col] = - 1;
			} else {
				$keys [$col] = 1;
			}
		}
		
		if ($this->mongodb->{$collection}->ensureIndex ( $keys, $options ) == TRUE) {
			return (TRUE);
		} else {
			return false;
			//show_error ( "An error occured when trying to add an index to MongoDB Collection", 500 );
		}
	}
	
	
	/**
	 * 删除索引
	 * return true or false;
	 * @param string $collection
	 * @param array / string $keys 
	 * remove multi-key index  array('key1' =>1,'key2'=>2)
	 * remove a simple  index  key1
	 * return true or false;
	 */
	public function delIndex($collection = "", $keys = array()) {
		if (empty ( $collection )) {
			return false;
			//show_error ( "No Mongo collection specified to remove index from", 500 );
		}
		
		if (empty ( $keys ) || ! is_array ( $keys )) {
			return false;
			//show_error ( "Index could not be removed from MongoDB Collection because no keys were specified", 500 );
		}
		
		if ($this->mongodb->{$collection}->deleteIndex ( $keys ) == TRUE) {
			return (TRUE);
		} else {
			return false;
			//show_error ( "An error occured when trying to remove an index from MongoDB Collection", 500 );
		}
	}
	
	/**
	 * 删除集合中所有索引
	 * @param string $collection
	 * @return true or false
	 */
	public function delAllIndex($collection = "") {
		if (empty ( $collection )) {
			//show_error ( "No Mongo collection specified to remove index from", 500 );
			return false;
		}
		if ($this->mongodb->{$collection}->deleteIndexes () == TRUE) {
			return (TRUE);
		} else {
			return false;
			//show_error ( "An error occured when trying to remove an index from MongoDB Collection", 500 );
		}
	}
	
	/**
	 * 插入一个array到collection 
	 * @param string $collection
	 * @param array $data   array('key1'=>'data1','key2'=>'data2',[key3...])
	 * @param string $return_id_type 返回id类型   有string  object 两种
	 * @return _id or false   插入成功，返回 string类型_id
	 */
	public function insert($collection = "", $data = array(), $return_id_type = "object") {
		$result = array();
		if (empty ( $collection )) {
			return false;
			//show_error ( "No Mongo collection selected to insert into", 500 );
		}
		
		if (count ( $data ) == 0 || ! is_array ( $data )) {
			return false;
			//show_error ( "Nothing to insert into Mongo collection or insert is not an array", 500 );
		}
		
		try {
			//safe 模式插入
			$result = $this->mongodb->{$collection}->insert ( $data, $this->getOptions());
			if ($result['ok'])
				return ($return_id_type == "string") ? $data['_id']->__toString() : $data['_id'];
			else
				return (FALSE);
		} catch ( MongoCursorException $e ) {
			return false;
			//@todo 记录日志中
			//show_error ( "Insert of data into MongoDB failed: {$e->getMessage()}", 500 );
		}
	}
	
	/**
	 * 批量插入数据
	 * @param string $collection 集合名称
	 * @param array $datas  array(array('key1'=>'data1','key2'=>data2),array(key1=>data1,key2=>data2))
	 * @return false or $datas 成功返回包含_id的数组
	 */
	public function batchInsert($collection = "", $datas = array()) {
		$result = array ();
		if (empty ( $collection )) {
			return false;
			//show_error ( "No Mongo collection selected to insert into", 500 );
		}
		
		if (count ( $datas ) == 0 || ! is_array ( $datas )) {
			return false;
			//show_error ( "Nothing to insert into Mongo collection or insert is not an array", 500 );
		}
		
		try {
	        $result = $this->mongodb->{$collection}->batchInsert($datas, $this->getOptions());
	        if($result['ok']){
	        	return $datas;
	        }else{
	        	return false;
	        }
		
		} catch ( MongoCursorException $e ) {
			return false;
			//show_error ( "Insert of data into MongoDB failed: {$e->getMessage()}", 500 );
		}
	
	}
	/**
	 * 更新文档内容
	 * @param string $collection
	 * @param array $where  更新条件
	 * @param array $data   更新数据
	 * @param boolean $multiple  是否更新多条
	 * @param boolean $upsert 如果文档没有找到，是否添加  默认false
	 * @return boolean
	 */
	public function update($collection = "", $where = array(), $data = array(), $multiple = FALSE, $upsert = false) {
		$result = array();
		if (empty ( $collection )) {
			return false;
			//show_error ( "No Mongo collection selected to update", 500 );
		}
		if (count ( $data ) == 0 || ! is_array ( $data )) {
			return false;
			//show_error ( "Nothing to update in Mongo collection or update is not an array", 500 );
		}
		try {
			$options = $this->getOptions();
			$options['multiple'] = $multiple;
			$options['upsert'] = $upsert;
			
			$result = $this->mongodb->{$collection}->update ( $where, array ('$set' => $data ), $options );
			if($result['ok']){
				return true;
			}else{
				return false;	
			}
		} catch ( MongoCursorException $e ) {
			return false;
			//show_error ( "Update of data into MongoDB failed: {$e->getMessage()}", 500 );
		}
	
	}
	/**
	 * 删除数据
	 * @param string $collection
	 * @param array $where
	 * @param boolean $justone 是否只删除第一条匹配的数据  默认是true
	 * @return boolean
	 */
	public function delete($collection = "", $where = array(), $justone = true) {
		$result = array();
		if (empty ( $collection )) {
			return false;
			//show_error ( "No Mongo collection selected to delete from", 500 );
		}
		try {
		    $options = $this->getOptions();
			$options['justOne'] = $justone;
			
			$result = $this->mongodb->{$collection}->remove ( $where, $options );
			return $result['ok'];
		} catch ( MongoCursorException $e ) {
			return false;
			//show_error ( "Delete of data into MongoDB failed: {$e->getMessage()}", 500 );
		}
	}
	/**
	 * 查找 一条
	 * @param string $collection
	 * @param array $where   查询条件
	 * @param array $fields  查询字段
	 * @param array $return
	 */
	public function findOne($collection = "", $where = array(), $fields = array()) {
		if (empty ( $collection )) {
			return false;
			//show_error ( "In order to retreive documents from MongoDB, a collection name must be passed", 500 );
		}
		return $this->mongodb->{$collection}->findOne ( $where, $fields );
	}
	/**
	 * 查找所有
	 * @param string $collection
	 * @param array $where    查询条件
	 * @param array $fields    查询字段
	 * @param array $sort      按字段排序
	 * @param int $limit       查找数量
	 * @param int $offset      开始游标    如果这里偏移量过大，对性能有影响，请考虑使用 mongodb权威指南的方法
	 * @return array(文档);
	 */
	public function findAll($collection = "", $where = array(), $fields = array(), $sort = array(), $limit = 9999, $offset = 0) {
		$results = array ();
		if (empty ( $collection )) {
			return array();
			//show_error ( "In order to retreive documents from MongoDB, a collection name must be passed", 500 );
		}
		$documents = $this->mongodb->{$collection}->find ( $where, $fields )->limit ( ( int ) $limit )->skip ( ( int ) $offset )->sort ( $sort );
		$returns = array ();
		foreach ( $documents as $doc ) {
			$returns [] = $doc;
		}
		return ($returns);
	}
	//析构函数，销毁对象
	public function __destruct() {
		//因为默认是长连接，这里不会关闭mongo连接 $this->mongo->close();
		$this->mongodb = null;
		$this->mongo = null;
		$this->mongocollection = null;
	}
	
	
	/****************注以下办法是模仿ci方式的*************************/
	
	/**
	 *	--------------------------------------------------------------------------------
	 *	SELECT FIELDS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Determine which fields to include OR which to exclude during the query process.
	 *	Currently, including and excluding at the same time is not available, so the 
	 *	$includes array will take precedence over the $excludes array.  If you want to 
	 *	only choose fields to exclude, leave $includes an empty array().
	 *
	 *	@usage: $this->mongo_db->select(array('foo', 'bar'))->get('foobar');
	 */
	 
	public function select($includes = array(), $excludes = array())
	{
	 	if(!is_array($includes))
	 	{
	 		$includes = array();
	 	}
	 	
	 	if(!is_array($excludes))
	 	{
	 		$excludes = array();
	 	}
	 	
	 	if(!empty($includes))
	 	{
	 		foreach($includes as $col)
	 		{
	 			$this->selects[$col] = 1;
	 		}
	 	}
	 	else
	 	{
	 		foreach($excludes as $col)
	 		{
	 			$this->selects[$col] = 0;
	 		}
	 	}
	 	return($this);
	}
	
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents based on these search parameters.  The $wheres array should 
	 *	be an associative array with the field as the key and the value as the search
	 *	criteria.
	 *
	 *	@usage = $this->mongo_db->where(array('foo' => 'bar'))->get('foobar');
	 */
	 
	 public function where($wheres = array())
	 {
	 	foreach($wheres as $wh => $val)
	 	{
	 		$this->wheres[$wh] = $val;
	 	}
	 	return($this);
	 }
	 
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE_IN PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is in a given $in array().
	 *
	 *	@usage = $this->mongo_db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	 */
	 
	 public function where_in($field = "", $in = array())
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$in'] = $in;
	 	return($this);
	 }
	 
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE_NOT_IN PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is not in a given $in array().
	 *
	 *	@usage = $this->mongo_db->where_not_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	 */
	 
	 public function where_not_in($field = "", $in = array())
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$nin'] = $in;
	 	return($this);
	 }
	 
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE GREATER THAN PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is greater than $x
	 *
	 *	@usage = $this->mongo_db->where_gt('foo', 20);
	 */
	 
	 public function where_gt($field = "", $x)
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$gt'] = $x;
	 	return($this);
	 }

	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE GREATER THAN OR EQUAL TO PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is greater than or equal to $x
	 *
	 *	@usage = $this->mongo_db->where_gte('foo', 20);
	 */
	 
	 public function where_gte($field = "", $x)
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$gte'] = $x;
	 	return($this);
	 }

	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE LESS THAN PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is less than $x
	 *
	 *	@usage = $this->mongo_db->where_lt('foo', 20);
	 */
	 
	 public function where_lt($field = "", $x)
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$lt'] = $x;
	 	return($this);
	 }

	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE LESS THAN OR EQUAL TO PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is less than or equal to $x
	 *
	 *	@usage = $this->mongo_db->where_lte('foo', 20);
	 */
	 
	 public function where_lte($field = "", $x)
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$lte'] = $x;
	 	return($this);
	 }
	
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE BETWEEN PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is between $x and $y
	 *
	 *	@usage = $this->mongo_db->where_between('foo', 20, 30);
	 */
	 
	 public function where_between($field = "", $x, $y)
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$gte'] = $x;
	 	$this->wheres[$field]['$lte'] = $y;
	 	return($this);
	 }
	
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE BETWEEN AND NOT EQUAL TO PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is between but not equal to $x and $y
	 *
	 *	@usage = $this->mongo_db->where_between_ne('foo', 20, 30);
	 */
	 
	 public function where_between_ne($field = "", $x, $y)
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$gt'] = $x;
	 	$this->wheres[$field]['$lt'] = $y;
	 	return($this);
	 }
	
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE NOT EQUAL TO PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is not equal to $x
	 *
	 *	@usage = $this->mongo_db->where_between('foo', 20, 30);
	 */
	 
	 public function where_ne($field = "", $x)
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$ne'] = $x;
	 	return($this);
	 }
	 
	 /**
	 *	--------------------------------------------------------------------------------
	 *	WHERE OR
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the value of a $field is in one or more values
	 *
	 *	@usage = $this->mongo_db->where_or('foo', array( 'foo', 'bar', 'blegh' );
	 */
	 
	 public function where_or($field = "", $values)
	 {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$or'] = $values;
	 	return($this);
	 }
	 
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE AND
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where the elements match the specified values
	 *
	 *	@usage = $this->mongo_db->where_and( array ( 'foo' => 1, 'b' => 'someexample' );
	 */
	 
	 public function where_and( $elements_values = array() ) {
	 	foreach ( $elements_values as $element => $val ) {
	 		$this->wheres[$element] = $val;
	 	}
	 	return($this);
	 }
	 
	 /**
	 *	--------------------------------------------------------------------------------
	 *	WHERE MOD
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents where $field % $mod = $result
	 *
	 *	@usage = $this->mongo_db->where_mod( 'foo', 10, 1 );
	 */
	 
	 public function where_mod( $field, $num, $result ) {
	 	$this->where_init($field);
	 	$this->wheres[$field]['$mod'] = array ( $num, $result );
	 	return($this);
	 }

	/**
	*	--------------------------------------------------------------------------------
	*	Where size
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the size of a field is in a given $size int
	*
	*	@usage : $this->mongo_db->where_size('foo', 1)->get('foobar');
	*/
	
	public function where_size($field = "", $size = "")
	{
		$this->_where_init($field);
		$this->wheres[$field]['$size'] = $size;
		return ($this);
	}
	
	/**
	 *	--------------------------------------------------------------------------------
	 *	LIKE PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *	
	 *	Get the documents where the (string) value of a $field is like a value. The defaults
	 *	allow for a case-insensitive search.
	 *
	 *	@param $flags
	 *	Allows for the typical regular expression flags:
	 *		i = case insensitive
	 *		m = multiline
	 *		x = can contain comments
	 *		l = locale
	 *		s = dotall, "." matches everything, including newlines
	 *		u = match unicode
	 *
	 *	@param $enable_start_wildcard
	 *	If set to anything other than TRUE, a starting line character "^" will be prepended
	 *	to the search value, representing only searching for a value at the start of 
	 *	a new line.
	 *
	 *	@param $enable_end_wildcard
	 *	If set to anything other than TRUE, an ending line character "$" will be appended
	 *	to the search value, representing only searching for a value at the end of 
	 *	a line.
	 *
	 *	@usage = $this->mongo_db->like('foo', 'bar', 'im', FALSE, TRUE);
	 */
	 
	 public function like($field = "", $value = "", $flags = "i", $enable_start_wildcard = TRUE, $enable_end_wildcard = TRUE)
	 {
	 	$field = (string) trim($field);
	 	$this->where_init($field);
	 	$value = (string) trim($value);
	 	$value = quotemeta($value);
	 	
	 	if($enable_start_wildcard !== TRUE)
	 	{
	 		$value = "^" . $value;
	 	}
	 	
	 	if($enable_end_wildcard !== TRUE)
	 	{
	 		$value .= "$";
	 	}
	 	
	 	$regex = "/$value/$flags";
	 	$this->wheres[$field] = new MongoRegex($regex);
	 	return($this);
	 }
	 
	/**
	 *	--------------------------------------------------------------------------------
	 *	ORDER BY PARAMETERS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Sort the documents based on the parameters passed. To set values to descending order,
	 *	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	 *	set to 1 (ASC).
	 *
	 *	@usage = $this->mongo_db->where_between('foo', 20, 30);
	 */
	 
	 public function order_by($fields = array())
	 {
	 	foreach($fields as $col => $val)
	 	{
	 		if($val == -1 || $val === FALSE || strtolower($val) == 'desc')
	 		{
	 			$this->sorts[$col] = -1; 
	 		}
	 		else
	 		{
	 			$this->sorts[$col] = 1;
	 		}
	 	}
	 	return($this);
	 }
	
	/**
	 *	--------------------------------------------------------------------------------
	 *	LIMIT DOCUMENTS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Limit the result set to $x number of documents
	 *
	 *	@usage = $this->mongo_db->limit($x);
	 */
	 
	 public function limit($x = 99999) {
	 	if($x !== NULL && is_numeric($x) && $x >= 1)
	 	{
	 		$this->limit = (int) $x;
	 	}
	 	return($this);
	 }
	
	/**
	 *	--------------------------------------------------------------------------------
	 *	OFFSET DOCUMENTS
	 *	--------------------------------------------------------------------------------
	 *
	 *	Offset the result set to skip $x number of documents
	 *
	 *	@usage = $this->mongo_db->offset($x);
	 */
	 
	 public function offset($x = 0)
	 {
	 	if($x !== NULL && is_numeric($x) && $x >= 1)
	 	{
	 		$this->offset = (int) $x;
	 	}
	 	return($this);
	 }
	 
	/**
	 *	--------------------------------------------------------------------------------
	 *	GET_WHERE
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents based upon the passed parameters
	 *
	 *	@usage = $this->mongo_db->get_where('foo', array('bar' => 'something'));
	 */
	
	 public function get_where($collection = "", $where = array(), $limit = 99999)
	 {
	 	return($this->where($where)->limit($limit)->get($collection));
	 }
	 
	/**
	 *	--------------------------------------------------------------------------------
	 *	GET
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get the documents based upon the passed parameters
	 *
	 *	@usage = $this->mongo_db->get('foo', array('bar' => 'something'));
	 */
	
	 public function get($collection = "")
	 {
	 	if(empty($collection))
	 	{
	 		show_error("In order to retreive documents from MongoDB, a collection name must be passed", 500);
	 	}
	 	$results = array();
	 	$documents = $this->mongodb->{$collection}->find($this->wheres, $this->selects)->limit((int) $this->limit)->skip((int) $this->offset)->sort($this->sorts);
	 	
	 	$returns = array();
	 	
	 	foreach($documents as $doc):
	 		$returns[] = $doc;
	 	endforeach;
	 	$this->clear();
	 	return($returns);

	 }
	 
	/**
	 *	--------------------------------------------------------------------------------
	 *	COUNT
	 *	--------------------------------------------------------------------------------
	 *
	 *	Count the documents based upon the passed parameters
	 *
	 *	@usage = $this->mongo_db->get('foo');
	 */
	 
	 public function count($collection = "")
	 {
	 	if(empty($collection))
	 	{
	 		show_error("In order to retreive a count of documents from MongoDB, a collection name must be passed", 500);
	 	}
	 	$count = $this->mongodb->{$collection}->find($this->wheres)->limit((int) $this->limit)->skip((int) $this->offset)->count();
	 	$this->clear();
	 	return($count);
	 }
	/**
	 *	--------------------------------------------------------------------------------
	 *	WHERE INITIALIZER
	 *	--------------------------------------------------------------------------------
	 *
	 *	Prepares parameters for insertion in $wheres array().
	 */
	
	private function where_init($param) 
	{
		if(!isset($this->wheres[$param]))
		{
			$this->wheres[$param] = array();
	  }
	}
	/**
	 *	--------------------------------------------------------------------------------
	 *	CLEAR
	 *	--------------------------------------------------------------------------------
	 *
	 *	Resets the class variables to default settings
	 */
	
	private function clear()
	{
		$this->selects = array();
		$this->wheres = array();
		$this->limit = NULL;
		$this->offset = NULL;
		$this->sorts = array();
	}

	/**
	 * 对某一集合的某字段求和，仅限一个数字类型字段
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param string $collection
	 * @param array $where    查询条件
	 * @param array $fields    查询字段
	 * @return int / false
	 */
	public function sum($collection = "", $where = array(), $field = ''){
		$results = array ();
		if (empty ( $collection )) {
			return false;
			//show_error ( "In order to retreive documents from MongoDB, a collection name must be passed", 500 );
		}

		$results = $this->findAll($collection, $where, array($field));
		$count = 0;
		if($results){
			foreach($results as $key => $value){
				if(is_numeric($value[$field])){
					$count = $count + $value[$field];
				}
			}

			return $count;
		}
		return false;
	}

	/**
	 * 自定义更新文档内容
	 * 
	 * @author bohailiang
	 * @date   2012/5/4
	 * @param string $collection
	 * @param array $where  更新条件
	 * @param array $data   更新数据    类似 array('$set' => array('name' => $newvalue, 'passwd' => $newvalue), '$inc' => array('count' => -1)) 
	 * @param boolean $multiple  是否更新多条
	 * @param boolean $upsert 如果文档没有找到，是否添加  默认false
	 * @return boolean
	 */
	public function update_custom($collection = "", $where = array(), $data = array(), $multiple = FALSE, $upsert = false) {
		$result = array();
		if (empty ( $collection )) {
			return false;
			//show_error ( "No Mongo collection selected to update", 500 );
		}
		if (count ( $data ) == 0 || ! is_array ( $data )) {
			return false;
			//show_error ( "Nothing to update in Mongo collection or update is not an array", 500 );
		}
		try {
			$options = $this->getOptions();
			$options['multiple'] = $multiple;
			$options['upsert'] = $upsert;
			
			$result = $this->mongodb->{$collection}->update ( $where, $data, $options );
			if($result['ok']){
				return true;
			}else{
				return false;	
			}
		} catch ( MongoCursorException $e ) {
			return false;
			//show_error ( "Update of data into MongoDB failed: {$e->getMessage()}", 500 );
		}
	
	}
}

/**
 * @desc mongo 单例
 * @author lijianwei
 * @date 2012/04/26
 */
class Single_Mongodb {
	protected static $_mongo = null;
	
	//设置默认3s连接超时
	public static function getInstance($options = array('timeout' => 3000)) {
		if (null === self::$_mongo) {
			extract ( config_item ( "mongo" ), EXTR_OVERWRITE );
			$connstr = $mongo_auth ? "mongodb://{$mongo_user}:{$mongo_pwd}@{$mongo_host}:{$mongo_port}/admin" : "mongodb://{$mongo_host}:{$mongo_port}";
			try {
				self::$_mongo = new Mongo ( $connstr, $options );
			} catch ( MongoConnectionException $e ) {
				show_error ( "mongodb connect error! " . $e->getMessage (), 500 );
			}
		}	
		return self::$_mongo;
	}
}