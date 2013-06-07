<?php
namespace Domains;

class Row extends \ArrayObject
{
	public $row ;
	public function __construct()
	{
		parent::__construct($this->row);
		$this->ci = get_instance();
        $this->loader = load_class('Loader','core');
		$this->db = $this->loader->database('event',true,true);
	}
	public function __get($name)
	{
		return $this->ci->$name;
	}
}
