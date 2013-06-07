<?php

/**
 * @desc SQLRelay数据驱动类
 * @author fbbin
 * @version 1.0
 * @access public
 * @return
 */
class SqlRelayDriver extends DB
{
    /**
     * @desc 构造函数，读取数据库配置信息，初步检测
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct( $config = '' )
    {
        if( ! extension_loaded('sql_relay') && ! function_exists( 'sqlrcon_alloc' ) )
        {
            //抛出错误信息：不支持SqlRelay数据访问层
            DKBase::throwError('_NOT_SUPPERT_ : SqlRelay');
        }
        if( ! empty($config) )
        {
            $this->config = $config;
        }
    }

    /**
     * @desc SqlRelay链接方法
     * @access public
     * @param array $config 数据库配置数组
     */
    public function connect( $config = '' )
    {
        if( empty($config) )
        {
            $config = $this->config;
        }
        $this->connection = sqlrcon_alloc($config['hostname'],$config['port'],$config['socket'],$config['user'],$config['passwd'],$config['retritime'],$config['tris']);
        $cursor = sqlrcur_alloc( $this->connection );
        if( ! sqlrcon_ping($this->connection) || ! $this->connection )
        {
            //抛出链接SQLRelay链接错误
            throw new Exception( '_SqlRelay_SERVER_CONNECT_ERROR_' );
        }
        //标记连接成功
        $this->connected = true;
        //设置超时时间
        sqlrcon_setTimeout( $this->connection, $config['timeout'], 1000 );
        //使用UTF8存取数据库
        sqlrcur_sendQuery($cursor, "set names " . $config['charset']);
        return $cursor;
    }

    /**
     * @desc 释放查询结果集
     * @access public
     */
    public function free()
    {
        $this->queryID = NULL;
        return true;
    }

    /**
     * @desc 预查询操作
     * @param string $str
     * @return mixed
     */
    protected function _prequery( $str = '' )
    {
        //开启当次查询缓存
        if( $this->db_query_cache )
        {
            sqlrcur_cacheToFile($this->cursor, $this->db_query_cache_path . md5($str));
            sqlrcur_setCacheTtl($this->cursor, $this->db_query_cache_ttl);
        }
        //设置结果集进入缓冲区
        sqlrcur_setResultSetBufferSize($this->cursor, $this->db_query_cache_size);
        return true;
    }

    /**
     * @desc 执行查询 返回数据集
     * @access public
     * @param string $str  sql语句
     * @return mixed
     */
    public function query( $str = '' )
    {
        $this->initConnect();
        if( !$this->cursor )
        {
            return false;
        }
        $this->queryStr = $str;
        if( $this->queryID )
        {
            $this->free();
        }
        // 数量统计
        //N('db_query',1);
        // 记录开始执行时间
        //G('queryStartTime');
        if( $this->db_query_cache )
        {
            if( sqlrcur_openCachedResultSet($this->cursor, $this->db_query_cache_path . md5($str)) )
            {
                return $this->getAll();
            }
        }
        //执行预查询
        $this->_prequery( $str );
        $this->queryID = sqlrcur_sendQuery( $this->cursor, $this->queryStr );
        $this->debug();
        //执行查询后操作
        $this->_afterquery( $str );
        if ( ! $this->queryID )
        {
            $this->debug || $this->getError();
            return false;
        }
        else
        {
            return $this->getAll();
        }
    }

    /**
     * @desc 查询之后执行函数
     * @param string $str
     * @return mixed
     */
    protected function _afterquery( $str = '' )
    {
        //关闭本次查询缓存
        if( $this->db_query_cache )
        {
            sqlrcur_cacheOff($this->cursor);
        }
        return true;
    }

    /**
     * @desc 执行非select语句
     * @access public
     * @param string $str  sql语句
     * @return integer
     */
    public function execute( $str = '' )
    {
        $this->initConnect();
        if( !$this->cursor )
        {
            return false;
        }
        $this->queryStr = $str;
        if( $this->queryID )
        {
            $this->free();
        }
        // 数量统计
        // N('db_query', 1);
        // 记录开始执行时间
        // G('queryStartTime');
        $res = sqlrcur_sendQuery( $this->cursor, $this->queryStr );
        $this->debug();
        if ( ! $res )
        {
            $this->debug || $this->getError();
            return false;
        }
        else
        {
            //返回影响的行数
            $this->numRows = sqlrcur_affectedRows( $this->cursor );
            sqlrcur_free( $this->cursor );
            //不支持返回最后一次写入的ID
            $this->lastInsID = $this->getLastInsetId();
            return $this->numRows;
        }
    }

    /**
     * @desc 返回最后一次写入的ID
     * @access public
     * @return void
     */
    private function getLastInsetId()
    {
        return $this->query('select last_insert_id()');
    }

    /**
     * @desc 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->initConnect();
        if( !$this->cursor )
        {
            return false;
        }
        if( $this->transTimes == 0 )
        {
            sqlrcon_autoCommitOff( $this->connection );
        }
        $this->transTimes ++;
        return ;
    }

    /**
     * @desc 用于事务非自动提交状态下面的查询提交
     * @access public
     * @return boolen
     */
    public function commit()
    {
        if ($this->transTimes > 0)
        {
            $result = sqlrcon_commit( $this->connection );
            $this->transTimes = 0;
            if( ! $result )
            {
                //事务回滚
                $this->rollback();
                //抛出链接SQLRelay事务提交错误
                throw new Exception( $this->getError() );
            }
        }
        return true;
    }

    /**
     * @desc 事务回滚
     * @access public
     * @return boolen
     */
    public function rollback()
    {
        if ($this->transTimes > 0)
        {
            $result = sqlrcon_rollback($this->connection);
            $this->transTimes = 0;
            if( ! $result )
            {
                //抛出链接SQLRelay事务回滚错误
                throw new Exception( $this->getError() );
            }
        }
        return true;
    }

    /**
     * @desc 获得所有的查询数据
     * @access private
     * @return array
     */
    private function getAll()
    {
        //结果集中的行数
        $this->numRows = sqlrcur_rowCount( $this->cursor );
        //返回数据集
        $result = Array();
        if( $this->numRows > 0 )
        {
            for($row = 0; $row < $this->numRows; $row++)
            {
                $result[] = sqlrcur_getRowAssoc($this->cursor, $row);
            }
            sqlrcur_free( $this->cursor );
        }
        return $result;
    }

    /**
     * @desc 取得数据表的字段信息
     * @param string $tableName
     * @access public
     */
    public function getFields($tableName = '')
    {
        $result = $this->query('SHOW COLUMNS FROM `'.$tableName.'`');
        $info = array();
        if( $result )
        {
            foreach ( $result as $key => $val )
            {
                $info[$val['Field']] = array(
                    'name'    => $val['Field'],
                    'type'    => $val['Type'],
                    'notnull' => (bool) ($val['Null'] === ''),
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * @desc 取得数据库的表信息
     * @access public
     */
    public function getTables($dbName='')
    {
        if( ! empty($dbName) )
        {
           $sql = 'SHOW TABLES FROM '.$dbName;
        }
        else
        {
           $sql = 'SHOW TABLES ';
        }
        $result = $this->query( $sql );
        $info = array();
        foreach ($result as $key => $val)
        {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * @desc 替换记录
     * @param mixed $data 数据
     * @param array $options 参数表达式
     * @return false | integer
     * @access public
     */
    public function replace($data, $options=array())
    {
        foreach ($data as $key=>$val)
        {
            $value = $this->parseValue($val);
            //检测标量
            if( is_scalar($value) )
            {
                $values[] = $value;
                 // 过滤非标量数据
                $fields[] = $this->addSpecialChar($key);
            }
        }
        $sql = 'REPLACE INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        return $this->execute( $sql );
    }

    /**
     * @desc 批量插入记录
     * @param mixed $datas 数据
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     * @access public
     */
    public function insertAll($datas, $options=array(), $replace=false)
    {
        if( ! is_array($datas[0]) )
        {
            return false;
        }
        // 获取字段
        $fields = array_keys( $datas[0] );
        // 字段过滤
        array_walk($fields, array($this, 'addSpecialChar'));
        $values = array();
        foreach ($datas as $data)
        {
            $value = array();
            foreach ( $data as $key=>$val )
            {
                $val = $this->parseValue( $val );
                // 过滤非标量数据
                if( is_scalar($val) )
                {
                    $value[] = $val;
                }
            }
            $values[] = '('.implode(',', $value).')';
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT').' INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES '.implode(',',$values);
        return $this->execute($sql);
    }

    /**
     * @desc 关闭与SQL Relay链接
     * @access public
     */
    public function close()
    {
        if( $this->connection )
        {
            sqlrcon_endSession( $this->connection );
            sqlrcon_free( $this->connection );
        }
        if( $this->cursor )
        {
            @sqlrcur_free( $this->cursor );
        }
        $this->connection = null;
        $this->cursor = null;
    }

    /**
     * @desc SQL Relay错误信息,并显示当前的SQL语句
     * @access public
     * @return string
     */
    public function getError()
    {
        if( empty( $this->error ) && is_resource( $this->cursor ) )
        {
            $this->error = sqlrcur_errorMessage( $this->cursor );
        }
        if( $this->debug && $this->queryStr != '' )
        {
            $this->error .= "\n [ SQL语句  ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     * SQL 指令安全过滤
     * @access public
     * @param string $str  SQL字符串
     * @return string
     */
    public function escape_string( $queryStr = '' )
    {
        return $queryStr;
    }

    /**
     * @desc 析构方法
     * @access public
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }

}


?>