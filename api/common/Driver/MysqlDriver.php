<?php

/**
 * @desc Mysql数据驱动类
 * @author fbbin
 * @version 1.0
 * @access public
 * @return
 */

define('CLIENT_MULTI_RESULTS', 131072);

class MysqlDriver extends DB
{
    /**
     * @desc 构造函数，读取数据库配置信息，初步检测
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config='')
    {
        if ( !extension_loaded('mysql') )
        {
            DKBase::throwError('_NOT_SUPPERT_ : Mysql');
        }
        if (!empty($config))
        {
            $this->config = $config;
        }
    }

    /**
     * @desc Mysql链接方法
     * @access public
     * @param array $config 数据库配置数组
     */
    public function connect($config='',$linkNum=0)
    {
        if ( !isset($this->connection[$linkNum]) )
        {
            if(empty($config))
            {
            	$config = $this->config;
            }
            // 处理不带端口号的socket连接情况
            $host = $config['hostname'].($config['port']?":{$config['port']}":'');
            if($this->pconnect)
            {
                $this->connection[$linkNum] = mysql_pconnect( $host, $config['user'], $config['passwd'],CLIENT_MULTI_RESULTS);
            }else
            {
                $this->connection[$linkNum] = mysql_connect( $host, $config['user'], $config['passwd'],true,CLIENT_MULTI_RESULTS);
            }
            if ( !$this->connection[$linkNum] || (!empty($config['database']) && !mysql_select_db($config['database'], $this->connection[$linkNum])) )
            {
                DKBase::throwError(mysql_error());
            }
            $dbVersion = mysql_get_server_info($this->connection[$linkNum]);
            if ($dbVersion >= "4.1") 
            {
                //使用UTF8存取数据库 需要mysql 4.1.0以上支持
                mysql_query("SET NAMES '".$config['charset']."'", $this->connection[$linkNum]);
            }
            //设置 sql_model
            if($dbVersion >'5.0.1')
            {
                mysql_query("SET sql_mode=''",$this->connection[$linkNum]);
            }
            // 标记连接成功
            $this->connected = true;
            // 注销数据库连接配置信息
            unset($this->config);
        }
        return $this->connection[$linkNum];
    }

    /**
     * @desc 释放查询结果集
     * @access public
     */
    public function free()
    {
        @mysql_free_result($this->queryID);
        $this->queryID = NULL;
         return true;
    }

    /**
     * @desc 执行查询 返回数据集
     * @access public
     * @param string $str  sql语句
     * @return mixed
     */
    public function query($str) 
    {
        $this->initConnect();
        if ( !$this->cursor ) 
        {
        	return false;
        }
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) 
        { 
        	$this->free();
        }
        // N('db_query',1);
        // 记录开始执行时间
        // G('queryStartTime');
        $this->queryID = mysql_query($str, $this->cursor);
        $this->debug();
        if ( false === $this->queryID )
        {
            $this->getError();
            return false;
        } else 
        {
            $this->numRows = mysql_num_rows($this->queryID);
            return $this->getAll();
        }
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
        if ( !$this->cursor )
        {
        	return false;
        }
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) 
        {
            $this->free();    
        }
        // N('db_write',1);
        // 记录开始执行时间
        // G('queryStartTime');
        $result =   mysql_query($str, $this->cursor) ;
        $this->debug();
        if ( false === $result) 
        {
            $this->getError();
            return false;
        } else 
        {
            $this->numRows = mysql_affected_rows($this->cursor);
            $this->lastInsID = mysql_insert_id($this->cursor);
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
        if ( !$this->cursor )
        {
        	 return false;
        }
        //数据rollback 支持
        if ($this->transTimes == 0) 
        {
            mysql_query('START TRANSACTION', $this->cursor);
        }
        $this->transTimes++;
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
            $result = mysql_query('COMMIT', $this->cursor);
            $this->transTimes = 0;
            if(!$result)
            {
                DKBase::throwError($this->getError());
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
            $result = mysql_query('ROLLBACK', $this->cursor);
            $this->transTimes = 0;
            if(!$result)
            {
                DKBase::throwError($this->getError());
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
        //返回数据集
        $result = array();
        if ($this->numRows >0) 
        {
            while($row = mysql_fetch_assoc($this->queryID))
            {
                $result[] = $row;
            }
            mysql_data_seek($this->queryID,0);
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
        if (!empty($this->queryID))
            mysql_free_result($this->queryID);
        if ($this->cursor && !mysql_close($this->cursor))
        {
            DKBase::throwError($this->getError());
        }
        $this->cursor = NULL;
    }

    /**
     * @desc SQL Relay错误信息,并显示当前的SQL语句
     * @access public
     * @return string
     */
    public function getError()
    {
        $this->error = mysql_error($this->cursor);
        if($this->debug && '' != $this->queryStr)
        {
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
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