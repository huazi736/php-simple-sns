<?php
namespace Domains;
/**
 * @author hpw
 * @date 2012/08/03
 */

class Row extends \DK_Model implements \ArrayAccess
{
	public $row;

	public function __construct(array $row)
	{
		$this->row = $row;		
		parent::__construct();
		$this->init_db('event');
	}
	
	public function offsetSet($offset, $value)
	{
        if (is_null($offset))
            $this->row[] = $value;
		else 
			$this->row[$offset] = $value;

    }
    public function offsetExists($offset)
	{
		if (!array_key_exists($offset, $this->row))
			$this->row[$offset] = null;
        return isset($this->row[$offset]);
    }
    public function offsetUnset($offset)
	{
        unset($this->row[$offset]);
    }
    public function &offsetGet($offset)
	{
        isset($this->row[$offset]);
		return  $this->row[$offset];
    }

}
