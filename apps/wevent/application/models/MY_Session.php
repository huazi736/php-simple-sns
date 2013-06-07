<?php
namespace Models;

/**
 * session处理
 *
 * @author hpw
 * @date 2012/07/09
 */

class MY_Session implements \ArrayAccess
{

	static protected $instance;
	static protected $_session_id;
	protected static $_cache_handler;
	protected $_data = array();

	/**
	 * 设置或得到session id
	 */
	static public function session_id($id=null)
	{
		if($id)
			self::$_session_id = $id;
		return self::$_session_id;
	}


	/**
	 * 初史化session
	 */
	static public function start()
	{
		if (!self::$instance)
			self::$instance = new self();
		return self::$instance;
	}

	protected function __construct()
	{
		require_cache(APPPATH . 'libraries' . DS . 'CacheMemcache.php');
        self::$_cache_handler = new \CacheMemcache();
		self::$_session_id = get_sessionid();
	}

	public function __destruct()
	{

		foreach ($this->_data as $offset => $data)
		{
			$key = self::$_session_id.$offset;
			if ($this->_data[$offset] === null)
				self::$_cache_handler->rmData($key);
			else
				self::$_cache_handler->setData($key, $this->_serialize($this->_data[$offset]), null);
				
		}

	}

	/**
	 * 序列化
	 */
	protected function _serialize($mixed)
	{
		if (is_string($mixed)) {
			return $mixed;
		}

		return serialize($mixed);
	}

	/**
	 * 反序列化
	 */
	protected function _unserialize($str)
	{
		if (!$str || strlen($str) < 4) {
			return $str;
		}

		static $tokens = array(
			's:;',
			'O:}',
			'i:;',
			'd:;',
			'a:}'
		);

		$token = substr($str, 0, 2) . substr($str, -1);

		if (!in_array($token, $tokens)) {
			return $str;
		}

		return unserialize($str);
	}

	public function offsetExists($offset)
	{
		if (!array_key_exists($offset, $this->_data))
		{
			$key = self::$_session_id.$offset;
			$data = self::$_cache_handler->get($key);

			if ($data)
				$this->_data[$offset] = $this->_unserialize($data);
			else {
				$this->_data[$offset] = null;
			}
		}

		return isset($this->_data[$offset]);
	}

	public function &offsetGet($offset)
	{
		isset($this[$offset]);

		return $this->_data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->_data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		$this->_data[$offset]=null;
	}


}

