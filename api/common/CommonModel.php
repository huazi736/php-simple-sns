<?php
/**
 * @desc SqlRelay操作控制器
 * @author fbbin
 * @version 1.0
 * @access public
 * @return
 */
class CommonModel
{
    // 数据表名
    public $tableName = '';
	// 当前数据库操作对象
    protected $db = null;
    // 主键名称
    protected $pk = 'id';
    // 数据库名称
    protected $dbName = '';
    // 数据库配置项
    protected $config = array();
    // 最近错误信息
    protected $error = '';
    // 字段信息
    protected $fields = array();
    // 数据信息
    protected $data = array();
    // 查询表达式参数
    protected $options = array();
    // 是否自动检测数据表字段信息
    protected $autoCheckFields = false;
    // 是否开启字段类型检测
    protected $fieldCheck = false;
    // 开启字段类型验证
    protected $fieldTypeCheck = false;
    // 字段缓存目录
    protected $fieldCachePath = './fieldCache/';
    
	/**
     * @desc 构造函数,取得DB类的实例对象,字段检查
     * @param string $config 数据库配置文件
     * @access public
     */
    public function __construct( $config = array() )
    {
        // 模型初始化
        if( method_exists($this, '__initialize') )
        {
            $this->__initialize();
        }
        // 获取配置项
        if( !empty($config) )
        {
            $this->config = $config;
        }
        $this->initDb();
        if( !empty($this->tableName) && $this->autoCheckFields )
        {
        	$this->_checkTableInfo();
        }
    }
    
	/**
     * @desc 自动检测数据表信息
     * @access protected
     * @return void
     */
    protected function _checkTableInfo()
    {
        if( empty($this->fields) )
        {
            // 如果数据表字段没有定义则自动获取
            if( $this->fieldCheck )
            {
            	$fields = file_get_contents( $this->fieldCachePath . $this->tableName )?:'[]';
                $this->fields = json_decode($fields);
                if( !$this->fields )
                {
                	$this->flush();
                }
            }
            // 每次都会读取数据表信息
            else
            {
                $this->flush();
            }
        }
    }
    
	/**
     * @desc 获取字段信息并缓存
     * @access public
     * @return void
     */
    public function flush()
    {
        $fields = $this->db->getFields($this->getTableName());
        $this->fields = array_keys($fields);
        $this->fields['_autoinc'] = false;
        foreach ( $fields as $key=>$val )
        {
            // 记录字段类型
            $type[$key] = $val['type'];
            if( $val['primary'] )
            {
                $this->fields['_pk'] = $key;
                if( $val['autoinc'] )
                {
					$this->fields['_autoinc'] = true;
                }
            }
        }
        // 记录字段类型信息
		$this->fields['_type'] = $type;
        // 增加缓存开关控制
        if( $this->fieldCheck )
        {
			file_put_contents( $this->fieldCachePath . $this->tableName, json_encode($this->fields) );
        }
    }
    
	/**
     * @desc 设置数据对象的值
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return void
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
    
	/**
     * @desc 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : NULL;
    }
    
	/**
     * @desc 检测数据对象的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name)
    {
        return isset( $this->data[$name] );
    }
    
	/**
     * @desc 销毁数据对象的值
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
        return true;
    }
    
	/**
     * @desc 利用__call方法实现一些特殊的Model方法
     * @access public
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     */
    public function __call($method, $args)
    {
    	// 连贯操作的实现
        if( in_array(strtolower($method), array('field','table','where','order','limit','page','alias','having','group','lock','distinct'),true))
        {
            $this->options[strtolower($method)] = $args[0];
            return $this;
        }
        // 统计查询的实现
        elseif( in_array(strtolower($method), array('count', 'sum', 'min', 'max', 'avg'), true) )
        {
            $field =  isset($args[0]) ? $args[0] : '*';
            return $this->getField(strtoupper($method) . '(' . $field . ') AS ' . $method);
        }
        // 根据某个字段获取记录
        elseif( strtolower(substr($method, 0, 5)) == 'getby' )
        {
            $field = $this->parse_name(substr($method,5));
            $where[$field] = $args[0];
            return $this->where($where)->find();
        }
        //错误
        else
        {
        	//抛出 未定义的错误方法
            throw new Exception(__CLASS__.':'.$method.'_METHOD_NOT_EXIST_');
            return false;
        }
    }
    
	/**
     * @desc 对保存到数据库的数据进行处理
     * @access protected
     * @param mixed $data 要操作的数据
     * @return boolean
     */
     protected function _facade( $data )
     {
        // 检查数据字段
        if( !empty($this->fields) )
        {
            foreach ($data as $key=>$val)
            {
            	//检测字段是否正确
                if( !in_array($key, $this->fields, true) )
                {
                    unset($data[$key]);
                }
                // 字段类型检查
                elseif( $this->fieldCheck && is_scalar($val) )
                {
                    $fieldType = strtolower($this->fields['_type'][$key]);
                    if(false !== strpos($fieldType, 'int')) 
                    {
                        $data[$key] = intval($val);
                    }
                    elseif(false !== strpos($fieldType, 'float') || false !== strpos($fieldType, 'double'))
                    {
                        $data[$key] = floatval($val);
                    }
                }
            }
        }
        $this->_before_write($data);
        return $data;
     }
    
	/**
	 * @desc 写入数据前的回调方法 包括新增和更新
	 * @access public
     * @return void
	 */ 
    protected function _before_write(&$data)
    {
    	//
    }
    
    /**
	 * @desc 插入数据前的回调方法
	 * @access public
     * @return void
	 */
    protected function _before_insert(&$data, $options)
    {
    	//
    }
    
	/**
     * @desc 新增数据操作方法
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @param boolean $replace 是否replace
     * @return mixed
     */
    public function add( $data = '', $options = array(), $replace = false )
    {
    	// 没有传递数据，获取当前数据对象的值
        if( empty($data) )
        {
            if( !empty($this->data) )
            {
                $data = $this->data;
            }
            else
            {
                $this->error = '_DATA_TYPE_INVALID_';
                return false;
            }
        }
        // 分析表达式
        $options = $this->_parseOptions( $options );
        // 数据处理
        $data = $this->_facade( $data );
        if( false === $this->_before_insert($data, $options) )
        {
            return false;
        }
        // 写入数据到数据库
        $result = $this->db->insert($data, $options, $replace);
        if( false !== $result )
        {
			$this->_after_insert($data, $options);
        }
        return $result;
    }
    
    /**
	 * @desc 插入成功后的回调方法
	 * @access public
     * @return void
	 */
    protected function _after_insert($data, $options) 
    {
    	//
    }
    
    /**
     * @desc 批量的写入多条数据
     * @param array $dataList
     * @param array $options
     * @param bool $replace
     */
	public function addAll($dataList, $options=array(), $replace=false)
	{
        if( empty($dataList) )
        {
            $this->error = '_DATA_TYPE_INVALID_';
            return false;
        }
        // 分析表达式
        $options = $this->_parseOptions($options);
        // 数据处理
        foreach ($dataList as $key=>$data)
        {
            $dataList[$key] = $this->_facade( $data );
        }
        // 写入数据到数据库
        $result = $this->db->insertAll($dataList, $options, $replace);
        if( false !== $result )
        {
            $this->_after_insert($dataList, $options);
        }
        return $result;
    }
    
	/**
	 * @desc 更新之前的回调方法
	 * @access public
     * @return void
	 */
    protected function _before_update($data, $options)
    {
    	//
    }
    
	/**
     * @desc 保存数据
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return boolean
     */
    public function save( $data = '', $options = array() )
    {
    	// 没有传递数据，获取当前数据对象的值
        if( empty($data) )
        {
            if( ! empty($this->data) )
            {
                $data = $this->data;
            }
            else
            {
                $this->error = '_DATA_TYPE_INVALID_';
                return false;
            }
        }
        // 数据处理
        $data = $this->_facade($data);
        // 分析表达式
        $options = $this->_parseOptions($options);
        if( false === $this->_before_update($data, $options) )
        {
            return false;
        }
        if( !isset($options['where']) )
        {
            // 如果存在主键数据 则自动作为更新条件
            if( isset($data[$this->getPk()]) )
            {
                $pk = $this->getPk();
                $where[$pk] = $data[$pk];
                $options['where'] = $where;
                $pkValue = $data[$pk];
                unset($data[$pk]);
            }
            // 如果没有任何更新条件则不执行
            else
            {
                $this->error = '_OPERATION_WRONG_';
                return false;
            }
        }
        $result = $this->db->update($data, $options);
        if( false !== $result )
        {
            if( isset($pkValue) )
            {
            	$data[$pk] = $pkValue;
            }
            $this->_after_update($data, $options);
        }
        return $result;
    }
    
    /**
	 * @desc 更新成功后的回调方法
	 * @access public
     * @return void
	 */
    protected function _after_update($data, $options)
    {
    	//
    }
    
	/**
     * @desc 删除数据
     * @access public
     * @param mixed $options 表达式
     * @return mixed
     */
    public function delete( $options=array() )
    {
        if( empty($options) && empty($this->options) )
        {
            // 如果删除条件为空 则删除当前数据对象所对应的记录
            if( !empty($this->data) && isset($this->data[$this->getPk()]) )
            {
                return $this->delete( $this->data[$this->getPk()] );
            }
            else
            {
                return false;
            }
        }
        if( is_numeric($options) || is_string($options) )
        {
            // 根据主键删除记录
            $pk = $this->getPk();
            if( strpos($options, ',') )
            {
                $where[$pk] = array('IN', $options);
            }
            else
            {
                $where[$pk] = $options;
                $pkValue = $options;
            }
            $options = array();
            $options['where'] =  $where;
        }
        // 分析表达式
        $options = $this->_parseOptions($options);
        $result = $this->db->delete($options);
        if( false !== $result )
        {
            $data = array();
            if( isset($pkValue) )
            {
            	$data[$pk] = $pkValue;
            }
            $this->_after_delete($data, $options);
        }
        return $result;
    }
    
    /**
	 * @desc 删除成功后的回调方法
	 * @access public
     * @return void
	 */
    protected function _after_delete($data, $options)
    {
    	//
    }
    
	/**
     * @desc 查询数据集
     * @access public
     * @param array $options 表达式参数
     * @return mixed
     */
    public function select( $options=array() )
    {
        if( is_string($options) || is_numeric($options) )
        {
            // 根据主键查询
            $pk = $this->getPk();
            if( strpos($options, ',') )
            {
                $where[$pk] = array('IN', $options);
            }
            else
            {
                $where[$pk] = $options;
            }
            $options = array();
            $options['where'] = $where;
        }
        // 分析表达式
        $options =  $this->_parseOptions($options);
        $resultSet = $this->db->select($options);
        if( false === $resultSet )
        {
            return false;
        }
        // 查询结果为空
        if( empty($resultSet) )
        {
            return null;
        }
        $this->_after_select($resultSet, $options);
        return $resultSet;
    }
    
	/**
	 * @desc 删除成功后的回调方法
	 * @access public
     * @return void
	 */
    protected function _after_select($data, $options)
    {
    	//
    }
    
    /**
     * @desc 查询数据集(select的别名)
     * @access public
     * @param array $options 表达式参数
     * @return mixed
     */
	public function findAll($options=array())
	{
        return $this->select( $options );
    }
    
	/**
     * @desc 分析表达式
     * @access private
     * @param array $options 表达式参数
     * @return array
     */
    private function _parseOptions( $options )
    {
        if( is_array($options) )
        {
            $options = array_merge($this->options,$options);
        }
        // 查询过后清空sql表达式组装 避免影响下次查询
        $this->options = array();
        if( !isset($options['table']) )
        {
            // 自动获取表名
           	$options['table'] = $this->getTableName();
        }
        if( !empty($options['alias']) )
        {
            $options['table'] .= ' '.$options['alias'];
        }
        // 字段类型验证
		if( $this->fieldTypeCheck && isset($options['where']) && is_array($options['where']) )
		{
			// 对数组查询条件进行字段类型检查
			foreach ( $options['where'] as $key=>$val )
			{
				if( in_array($key, $this->fields, true) && is_scalar($val) )
				{
					$fieldType = strtolower($this->fields['_type'][$key]);
					if( false !== strpos($fieldType, 'int') )
					{
						$options['where'][$key] = intval($val);
					}
					elseif( false !== strpos($fieldType, 'float') || false !== strpos($fieldType, 'double') )
					{
						$options['where'][$key] = floatval($val);
					}
				}
			}
		}
        // 表达式过滤
        $this->_options_filter( $options );
        return $options;
    }
    
    /**
	 * @desc 表达式过滤回调方法
	 * @access public
     * @return void
	 */
    protected function _options_filter( &$options )
    {
    	//
    }
    
	/**
     * @desc 查询数据
     * @access public
     * @param mixed $options 表达式参数
     * @return mixed
     */
     public function find( $options=array() )
     {
         if( !empty($options) && ( is_numeric($options) || is_string($options)) )
         {
             $where[$this->getPk()] = $options;
             $options = array();
             $options['where'] = $where;
         }
         // 总是查找一条记录
        $options['limit'] = 1;
        // 分析表达式
        $options = $this->_parseOptions($options);
        $resultSet = $this->db->select($options);
        if( false === $resultSet )
        {
            return false;
        }
        // 查询结果为空
        if(empty($resultSet))
        {
            return null;
        }
        $this->data = $resultSet[0];
        $this->_after_find($this->data, $options);
        return $this->data;
	}
    
	/**
	 * @desc 查询成功的回调方法
	 * @access public
     * @return void
	 */
	protected function _after_find(&$result, $options)
	{
		//
	}
    
	/**
     * @desc 设置记录的某个字段值,支持使用数据库字段和方法
     * @access public
     * @param string|array $field  字段名
     * @param string|array $value  字段值
     * @param mixed $condition  条件
     * @return boolean
     */
    public function setfield($field, $value, $condition = '')
    {
        if( empty($condition) && isset($this->options['where']) )
        {
            $condition = $this->options['where'];
        }
        $options['where'] = $condition;
        if( is_array($field) )
        {
            foreach ($field as $key=>$val)
            {
                $data[$val] = $value[$key];
            }
        }
        else
        {
            $data[$field] = $value;
        }
        return $this->save($data,$options);
    }
    
	/**
     * @desc 字段值增长
     * @access public
     * @param string $field  字段名
     * @param integer $step  增长值
     * @return boolean
     */
    public function setinc($field, $step=1)
    {
        return $this->setField($field, array('exp', $field.'+'.$step));
    }
    
	/**
     * @desc 字段值减少
     * @access public
     * @param string $field  字段名
     * @param integer $step  减少值
     * @return boolean
     */
    public function setdec($field, $step=1)
    {
        return $this->setField($field, array('exp', $field.'-'.$step));
    }
    
	/**
     * @desc 获取一条记录的某个字段值
     * @access public
     * @param string $field  字段名
     * @param mixed $condition  查询条件
     * @param string $spea  字段数据间隔符号
     * @return mixed
     */
    public function getfield($field, $condition='', $sepa=' ')
    {
        if( empty($condition) && isset($this->options['where']) )
        {
            $condition = $this->options['where'];
        }
        $options['where'] = $condition;
        $options['field'] = $field;
        $options = $this->_parseOptions($options);
		// 多字段
        if( strpos($field, ',') )
        {
            $resultSet = $this->db->select($options);
            if( !empty($resultSet) )
            {
                $field = explode(',', $field);
                $key = array_shift($field);
                $cols = array();
                foreach ($resultSet as $result)
                {
                    $name = $result[$key];
                    $cols[$name] = '';
                    foreach ($field as $val)
                    {
                        $cols[$name] .= $result[$val].$sepa;
                    }
                    $cols[$name] = substr($cols[$name], 0, -strlen($sepa));
                }
                return $cols;
            }
        }
        // 多字段
        else
        {
            $options['limit'] = 1;
            $result = $this->db->select( $options );
            if( !empty($result) )
            {
                return reset($result[0]);
            }
        }
        return null;
    }
    
	/**
     * @desc SQL查询
     * @access public
     * @param mixed $sql  SQL指令
     * @return mixed
     */
    public function query( $sql )
    {
        if( !empty($sql) )
        {
            if( strpos($sql, '__TABLE__') )
            {
                $sql = str_replace('__TABLE__', $this->getTableName(), $sql);
            }
            return $this->db->query($sql);
        }
        else
        {
            return false;
        }
    }
    
	/**
     * @desc 执行SQL语句
     * @access public
     * @param string | array $sql  SQL指令
     * @return false | integer
     */
    public function execute( $sql )
    {
    	if( empty($sql) )
    	{
    		return false;
    	}
        $_status = true;
        if( !empty($sql) && is_string($sql) )
        {
        	$sql = array($sql);
        }
        //执行多条SQL，启动事务
        $this->startTrans();
        foreach ($sql as $value)
        {
        	if( strpos($value, '__TABLE__') )
            {
                $value = str_replace('__TABLE__', $this->getTableName(), $value);
            }
			if( false === $this->db->execute( $value ) )
            {
            	$_status = false;
            }
        }
        if( $_status )
        {
        	$this->commit();
        }
        else
        {
        	$this->rollback();
        }
        return $_status;
    }
    
	/**
     * 建立SQL Relay连接
     * @access public
     * @return object
     */
    protected function initDb()
    {
        static $_db = array();
        $linkId = $this->config['hostname'] . $this->config['database'];
        if( !isset( $_db[$linkId] ) )
        {
        	$_db[$linkId] = DB::getInstance( $this->config );
        }
        // 切换数据库连接
        $this->db = $_db[$linkId];
        return $this;
    }
    
	/**
     * @desc 得到完整的数据表名
     * @access public
     * @return string
     */
    public function getTableName()
    {
        $tableName = '';
		if( !empty($this->tableName) )
		{
			$tableName .= $this->tableName;
		}
		if( !empty($this->dbName) )
		{
			$tableName = $this->dbName . '.' . $tableName;
		}
		return $tableName;
    }
    
    public function parse_name($name, $type=0) 
    {
    	if ($type) {
    		return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    	} else {
    		$name = preg_replace("/[A-Z]/", "_\\0", $name);
    		return strtolower(trim($name, "_"));
    	}
    }
    
	/**
     * @desc 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->commit();
        $this->db->startTrans();
        return true;
    }
    
	/**
     * @desc 提交事务
     * @access public
     * @return boolean
     */
    public function commit()
    {
        return $this->db->commit();
    }
    
	/**
     * @desc 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback()
    {
        return $this->db->rollback();
    }
    
	/**
     * @desc 返回模型的错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error ? $this->error : $this->db->getError();
    }
    
	/**
     * @desc 返回最后执行的sql语句
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->db->getLastSql();
    }
    
	/**
     * @desc 获取主键名称
     * @access public
     * @return string
     */
    public function getPk()
    {
        return isset($this->fields['_pk']) ? $this->fields['_pk'] : $this->pk;
    }
    
	/**
     * @desc 获取数据表字段信息
     * @access public
     * @return array
     */
    public function getDbFields()
    {
        return $this->fields;
    }
    
	/**
     * @desc 设置数据对象值
     * @access public
     * @param mixed $data 数据
     * @return Model
     */
    public function data( $data )
    {
        if( is_string($data) )
        {
            parse_str($data, $data);
        }
        elseif( !is_array($data) )
        {
            throw new Exception( '_DATA_TYPE_INVALID_' );
        }
        $this->data = $data;
        return $this;
    }
    
	/**
     * @desc 查询SQL组装 join
     * @access public
     * @param mixed $join
     * @return object
     */
    public function join( $join )
    {
        if( is_array($join) )
        {
            $this->options['join'] = $join;
        }
        else
        {
            $this->options['join'][] = $join;
        }
        return $this;
    }
    
	/**
     * @desc 设置模型的属性值
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return Model
     */
    public function setProperty($name, $value)
    {
        if( property_exists($this, $name) )
        {
            $this->$name = $value;
        }
        return $this;
    }
    
	/**
     * @desc 查找前N个记录
     * @access public
     * @param integer $count 记录个数
     * @param array $options 查询表达式
     * @return array
     */
    public function topN( $count, $options = array() )
    {
        $options['limit'] = $count;
        return $this->select( $options );
    }
    
	/**
     * @desc 查询符合条件的第N条记录,0 表示第一条记录 -1 表示最后一条记录
     * @access public
     * @param integer $position 记录位置
     * @param array $options 查询表达式
     * @return mixed
     */
    public function getN($position = 0, $options = array())
    {
		// 正向查找
        if( $position >= 0 )
        { 
            $options['limit'] = $position . ',1';
            $list = $this->select($options);
            return $list ? $list[0] : false;
		// 逆序查找
        }
        else
        {
            $list = $this->select($options);
            return $list ? $list[count($list)-abs($position)] : false;
        }
    }
    
}

/**
 * @desc SqlRelay操作中间层
 * @author fbbin
 * @version 1.0
 * @access public
 * @return
 */
class DB
{
    // 是否显示调试信息 如果启用会在日志文件记录sql语句
    public $debug = false;
    // 当前SQL语句
    protected $queryStr = '';
    // 最后插入ID
    protected $lastInsID = null;
    // 返回或者影响记录数
    protected $numRows = 0;
     // 返回字段数
    protected $numCols = 0;
    // 事务执行数
    protected $transTimes = 0;
    // 错误信息
    protected $error = '';
    // 数据库连接句柄
    protected $connection = null;
    // 当前操作资源
    protected $cursor= null;
    // 当前查询ID
    protected $queryID = null;
    // 是否已经连接到SQL Relay
    protected $connected = false;
    // 是否使用永久连接
    protected $pconnect = false;
    // 数据库连接参数配置
    protected $config = array();
    // 数据库表达式
    protected $comparison = array('eq'=>'=','neq'=>'!=','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE');
    // 查询表达式样式
    protected $selectSql = 'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';
    // 开启查询缓存
    protected $db_query_cache = true;
    // 缓存目录
    protected $db_query_cache_path = './queryCache/';
    // 缓存超时时间
    protected $db_query_cache_ttl = 30;
    // 缓冲区大小
    protected $db_query_cache_size = 50;
    // 对象单例
    private static $_instance = array();
    
    /**
     * @desc 构造函数
     * @param array $config SqlRelay配置数组
     */
    public function __construct( $config = '' )
    {
		return self::getInstance( $config );
    }
    
    /**
     * @desc 取得数据库类实例
     * @static
     * @access public
     * @return mixed | object
     */
    public static function getInstance()
    {
        $args = func_get_args();
        $identify = md5(implode(',', $args['0']));
        if( !isset(self::$_instance[$identify]) )
        {
            self::$_instance[$identify] = self::factory($args['0']);
        }
        return self::$_instance[$identify];
    }
    
    /**
     * @desc 加载数据库 支持配置文件
     * @access public
     * @param mixed $dbConfig 数据库配置信息
     * @return string
     */
    public static function factory( $dbConfig = '' )
    {
        // 读取数据库配置
        $dbConfig = self::parseConfig( $dbConfig );
        if( empty($dbConfig['hostname']) )
        {
            //抛出 未选择SQL Relay的启动ID 错误
            DKBase::throwError('_UNKNOW_HOSTNAME_');
        }
        $driverName = isset($dbConfig['driver']) ? $dbConfig['driver'] : 'SqlRelay';
        $driverName .= 'Driver';
        $driverFilePath = __DIR__ . DS . 'Driver' . DS . ucfirst($driverName) . EXT;
        if( file_exists($driverFilePath) )
        {
            include_once $driverFilePath;
            if ( class_exists( $driverName ) ) // 检查驱动类
            {
                $dbObj = new $driverName( $dbConfig );
                /*if( C('APP_DEBUG') )
                {
                    $dbObj->debug = true;
                }*/
            } else 
            {
                DKBase::throwError('DRIVER_NOT_EXISTS :'.$driverName);
            }
        }
        else
        {
            DKBase::throwError('DRIVER_FILE_NOT_EXISTS :'.$driverFilePath);
        }
        unset($driverName, $driverFilePath);
        return $dbObj;
    }
    
    /**
     * @desc 解析数据库配置信息
     * @access private
     * @param mixed $dbConfig 数据库配置信息
     * @return string
     */
    private static function parseConfig( $dbConfig = '' )
    {
        $dbConfig = array (
                'hostname'  =>   $dbConfig['hostname'],
                'database'  =>   $dbConfig['database'],
                'user'      =>   $dbConfig['username'],
                'passwd'    =>   $dbConfig['password'],
                'driver'    =>   $dbConfig['dbdriver'],
                'port'      =>   isset($dbConfig['port'])?$dbConfig['port']:9093,
                'socket'    =>   isset($dbConfig['socket'])?$dbConfig['socket']:'',
                'charset'   =>   $dbConfig['char_set'],
                'timeout'   =>   isset($dbConfig['timeout'])?$dbConfig['timeout']:30,
                'retritime' =>   isset($dbConfig['retritime'])?$dbConfig['retritime']:3,
                'tris'      =>   isset($dbConfig['tris'])?$dbConfig['tris']:1,
            );
        return $dbConfig;
    }
    
    /**
     * @desc 初始化SqlRelay连接
     * @access protected
     * @return void
     */
    protected function initConnect()
    {
        if ( $this->connected )
        {
        	if( isset($this->config['driver']) && strtolower($this->config['driver']) == 'sqlrelay' )
        	{
        		$this->close();
        		$this->cursor = $this->connect();
        	}
        	return $this->cursor;
        }
		return $this->cursor = $this->connect();
    }
    
    /**
     * @desc 数据库调试 记录当前SQL
     * @access protected
     */
    protected function debug()
    {
        // 记录操作结束时间
        if ( $this->debug )
        {
            // G('queryEndTime');
            // Log::record($this->queryStr." [ RunTime:".G('queryStartTime','queryEndTime',6)."s ]",Log::SQL);
        }
    }
    
    /**
     * @desc 设置锁机制
     * @access protected
     * @return string
     */
    protected function parseLock( $lock = false )
    {
        if( ! $lock )
        {
            return '';
        }
        return ' FOR UPDATE ';
    }
    
    /**
     * @desc set分析
     * @access protected
     * @param array $data
     * @return string
     */
    protected function parseSet( $data = array() )
    {
        foreach ($data as $key=>$val)
        {
            $value = $this->parseValue( $val );
            if( is_scalar($value) )
            {
                // 过滤非标量数据
                $set[] = $this->addSpecialChar( $key ) . '=' . $value;
            }
        }
        return ' SET '.implode(',',$set);
    }
    
    /**
     * @desc value分析
     * @access protected
     * @param mixed $value
     * @return string
     */
    protected function parseValue( $value )
    {
        if( is_string($value) )
        {
            $value = '\''.$this->escape_string( $value ).'\'';
        }
        elseif( isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp' )
        {
            $value = $this->escape_string( $value[1] );
        }
        elseif( is_array($value) )
        {
            $value = array_map(array($this, 'parseValue'), $value);
        }
        elseif( is_null($value) )
        {
            $value = 'null';
        }
        return $value;
    }
    
    /**
     * @desc field/字段别名分析
     * @access protected
     * @param mixed $fields
     * @return string
     */
    protected function parseField( $fields )
    {
         //获取多个字段
        if( is_array($fields) )
        {
            // 完善数组方式传字段名的支持，支持 'field1'=>'field2' 这样的字段别名定义
            $array = array();
            foreach ( $fields as $key=>$field )
            {
                if( !is_numeric($key) )
                {
                    $array[] = $this->addSpecialChar($key).' AS '.$this->addSpecialChar($field);
                }
                else
                {
                    $array[]= $this->addSpecialChar( $field );
                }
            }
            $fieldsStr = implode(',', $array);
        }
        //获取单个字段
        elseif( is_string($fields) && !empty($fields) )
        {
            $fieldsStr = $this->addSpecialChar( $fields );
        }
        //全部
        else
        {
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }
    
    /**
     * @desc table分析
     * @access protected
     * @param mixed $table
     * @return string
     */
    protected function parseTable( $tables = '' )
    {
        if( is_string($tables) )
        {
            $tables = explode(',', $tables);
        }
        array_walk($tables, array(&$this, 'addSpecialChar'));
        return implode(',', $tables);
    }
    
    /**
     * @desc where条件语句分析
     * @access protected
     * @param mixed $where
     * @return string
     */
    protected function parseWhere( $where = '' )
    {
        $whereStr = '';
        // 直接使用字符串条件
        if( is_string($where) )
        {
            $whereStr = $where;
        }
        // 使用数组条件表达式
        else
        { 
            // 定义逻辑运算规则 例如 OR XOR AND NOT
            if( array_key_exists('_logic', $where) )
            {
                $operate = ' '.strtoupper( $where['_logic'] ).' ';
                unset($where['_logic']);
            }
            // 默认进行 AND 运算
            else
            {
                $operate = ' AND ';
            }
            foreach ($where as $key=>$val)
            {
                $whereStr .= "( ";
                // 解析特殊条件表达式
                if( 0===strpos($key, '_') )
                {
                    $whereStr .= $this->parseThinkWhere($key, $val);
                }
                else
                {
                    $key = $this->addSpecialChar( $key );
                    if( is_array($val) )
                    {
                        if( is_string($val[0]))
                        {
                            // 比较运算
                            if( preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i',$val[0]))
                            { 
                                $whereStr .= $key.' '.$this->comparison[strtolower($val[0])].' '.$this->parseValue($val[1]);
                            }
                            // 使用表达式
                            elseif( 'exp'==strtolower($val[0]) )
                            {
                                $whereStr .= ' ('.$key.' '.$val[1].') ';
                            }
                            // IN 运算
                            elseif( preg_match('/IN/i',$val[0]) )
                            {
                                if( is_string($val[1]) )
                                {
                                     $val[1] = explode(',',$val[1]);
                                }
                                $zone = implode(',', $this->parseValue($val[1]));
                                $whereStr .= $key.' '.strtoupper($val[0]).' ('.$zone.')';
                            }
                            // BETWEEN运算
                            elseif( preg_match('/BETWEEN/i',$val[0]) )
                            {
                                $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                                $whereStr .= ' ('.$key.' BETWEEN '.$data[0].' AND '.$data[1].' )';
                            }
                            //抛出 错误的 条件语句
                            else
                            {
                                 throw new Exception( '_EXPRESS_ERROR_:' . $val[0]);
                            }
                        }
                        else
                        {
                            $count = count( $val );
                            if( in_array(strtoupper(trim($val[$count-1])), array('AND','OR','XOR')) )
                            {
                                $rule = strtoupper(trim($val[$count-1]));
                                $count = $count -1;
                            }
                            else
                            {
                                $rule = 'AND';
                            }
                            for($i=0;$i<$count;$i++)
                            {
                                $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                                if( 'exp'==strtolower($val[$i][0]) )
                                {
                                    $whereStr .= '('.$key.' '.$data.') '.$rule.' ';
                                }
                                else
                                {
                                    $op = is_array($val[$i]) ? $this->comparison[strtolower($val[$i][0])] : '=';
                                    $whereStr .= '('.$key.' '.$op.' '.$this->parseValue($data).') '.$rule.' ';
                                }
                            }
                            $whereStr = substr($whereStr, 0, -4);
                        }
                    }
                    //对字符串类型字段采用模糊匹配
                    else
                    {
                        $whereStr .= $key." = ".$this->parseValue($val);
                    }
                }
                $whereStr .= ' )'.$operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }
    
    /**
     * @desc 特殊条件分析
     * @access protected
     * @param string $key
     * @param mixed $val
     * @return string
     */
    protected function parseThinkWhere($key, $val)
    {
        $whereStr = '';
        switch($key)
        {
            // 字符串模式查询条件
            case '_string':
                $whereStr = $val;
                break;
            // 复合查询条件
            case '_complex':
                $whereStr = substr($this->parseWhere($val), 6);
                break;
            // 字符串模式查询条件
            case '_query':
                parse_str($val, $where);
                if( array_key_exists('_logic',$where) )
                {
                    $op = ' '.strtoupper($where['_logic']).' ';
                    unset($where['_logic']);
                }
                else
                {
                    $op = ' AND ';
                }
                $array = array();
                foreach ($where as $field=>$data)
                {
                    $array[] = $this->addSpecialChar($field).' = '.$this->parseValue($data);
                }
                $whereStr = implode($op,$array);
                break;
        }
        return $whereStr;
    }
    
    /**
     * @desc limit条件分析
     * @access protected
     * @param mixed $lmit
     * @return string
     */
    protected function parseLimit( $limit = '')
    {
        return !empty($limit) ? ' LIMIT '.$limit.' ' : '';
    }
    
    /**
     * @desc join条件分析
     * @access protected
     * @param mixed $join
     * @return string
     */
    protected function parseJoin( $join = '')
    {
        $joinStr = '';
        if( ! empty($join) )
        {
            if( is_array($join) )
            {
                foreach ($join as $key=>$_join)
                {
                    if( false !== stripos($_join,'JOIN') )
                    {
                        $joinStr .= ' '.$_join;
                    }
                    else
                    {
                        $joinStr .= ' LEFT JOIN ' .$_join;
                    }
                }
            }
            else
            {
                $joinStr .= ' LEFT JOIN ' .$join;
            }
        }
        return $joinStr;
    }
    
    /**
     * @desc order条件分析
     * @access protected
     * @param mixed $order
     * @return string
     */
    protected function parseOrder( $order = '' )
    {
        if( is_array($order) )
        {
            $array = array();
            foreach ($order as $key=>$val)
            {
                if( is_numeric($key) )
                {
                    $array[] =  $this->addSpecialChar($val);
                }
                else
                {
                    $array[] =  $this->addSpecialChar($key).' '.$val;
                }
            }
            $order   =  implode(',', $array);
        }
        return !empty($order) ? ' ORDER BY '.$order : '';
    }
    
    /**
     * @desc group条件分析
     * @access protected
     * @param mixed $group
     * @return string
     */
    protected function parseGroup( $group = '' )
    {
        return !empty($group) ? ' GROUP BY '.$group : '';
    }
    
    /**
     * @desc having条件分析
     * @access protected
     * @param string $having
     * @return string
     */
    protected function parseHaving( $having = '' )
    {
        return  !empty($having) ? ' HAVING '.$having : '';
    }
    
    /**
     * @desc distinct条件分析
     * @access protected
     * @param mixed $distinct
     * @return string
     */
    protected function parseDistinct( $distinct = '' )
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }
    
    /**
     * @desc 插入记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insert($data, $options=array(), $replace=false)
    {
        foreach ($data as $key=>$val)
        {
            $value = $this->parseValue( $val );
            if( is_scalar($value) )
            {
                $values[] = $value;
                // 过滤非标量数据
                $fields[] = $this->addSpecialChar( $key );
            }
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT').' INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute( $sql );
    }
    
    /**
     * @desc 通过Select方式插入记录
     * @access public
     * @param string $fields 要插入的数据表字段名
     * @param string $table 要插入的数据表名
     * @param array $option  查询数据参数
     * @return false | integer
     */
    public function selectInsert($fields, $table, $options=array() )
    {
        if( is_string($fields) )
        {
            $fields = explode(',', $fields);
        }
        array_walk($fields, array($this, 'addSpecialChar'));
        $sql = 'INSERT INTO '.$this->parseTable($table).' ('.implode(',', $fields).') ';
        $sql .= str_replace(
            array('%TABLE%','%DISTINCT%','%FIELDS%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%'),
            array(
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct'])?$options['distinct']:false),
                $this->parseField(isset($options['field'])?$options['field']:'*'),
                $this->parseJoin(isset($options['join'])?$options['join']:''),
                $this->parseWhere(isset($options['where'])?$options['where']:''),
                $this->parseGroup(isset($options['group'])?$options['group']:''),
                $this->parseHaving(isset($options['having'])?$options['having']:''),
                $this->parseOrder(isset($options['order'])?$options['order']:''),
                $this->parseLimit(isset($options['limit'])?$options['limit']:'')
            ), $this->selectSql);
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute( $sql );
    }
    
    /**
     * @desc 更新记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return false | integer
     */
    public function update($data, $options)
    {
        $sql = 'UPDATE '
            .$this->parseTable($options['table'])
            .$this->parseSet($data)
            .$this->parseWhere(isset($options['where'])?$options['where']:'')
            .$this->parseOrder(isset($options['order'])?$options['order']:'')
            .$this->parseLimit(isset($options['limit'])?$options['limit']:'')
            .$this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $this->execute( $sql );
    }
    
    /**
     * @desc 删除记录
     * @access public
     * @param array $options 表达式
     * @return false | integer
     */
    public function delete( $options=array() )
    {
       $sql = 'DELETE FROM '
            .$this->parseTable($options['table'])
            .$this->parseWhere(isset($options['where'])?$options['where']:'')
            .$this->parseOrder(isset($options['order'])?$options['order']:'')
            .$this->parseLimit(isset($options['limit'])?$options['limit']:'')
            .$this->parseLock(isset($options['lock'])?$options['lock']:false);
        return $this->execute( $sql );
    }
    
    /**
     * @desc 查找记录
     * @access public
     * @param array $options 表达式
     * @return array
     */
    public function select( $options=array() )
    {
        // 根据页数计算limit
        if( isset($options['page']) )
        {
            list($page, $listRows) = explode(',', $options['page']);
            $page = $page ? $page : 1;
            $listRows = $listRows ? $listRows : ($options['limit'] ? $options['limit'] : 20);
            $offset = $listRows * ((int)$page-1);
            $options['limit'] = $offset.','.$listRows;
        }
        $sql = str_replace(
            array('%TABLE%','%DISTINCT%','%FIELDS%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%'),
            array(
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct'])?$options['distinct']:false),
                $this->parseField(isset($options['field'])?$options['field']:'*'),
                $this->parseJoin(isset($options['join'])?$options['join']:''),
                $this->parseWhere(isset($options['where'])?$options['where']:''),
                $this->parseGroup(isset($options['group'])?$options['group']:''),
                $this->parseHaving(isset($options['having'])?$options['having']:''),
                $this->parseOrder(isset($options['order'])?$options['order']:''),
                $this->parseLimit(isset($options['limit'])?$options['limit']:'')
            ), $this->selectSql);
        $sql .= $this->parseLock(isset($options['lock'])?$options['lock']:false);
        return $this->query($sql);
    }
    
    /**
     * @desc 字段和表名添加`(反引号)
     * 保证指令中使用关键字不出错
     * @access protected
     * @param mixed $value
     * @return mixed
     */
    protected function addSpecialChar( &$value )
    {
        $value = trim($value);
        if( false !== strpos($value,' ') || false !== strpos($value,',') || false !== strpos($value,'*') ||  false !== strpos($value,'(') || false !== strpos($value,'.') || false !== strpos($value,'`'))
        {
            //如果包含* 或者 使用了sql方法 则不作处理
        }
        else
        {
            $value = '`'.$value.'`';
        }
        return $value;
    }
    
    /**
     * @desc 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
    }
     
}
