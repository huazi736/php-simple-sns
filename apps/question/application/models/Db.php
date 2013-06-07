<?php

namespace DK\Question;

class Db
{
	public $db;

	function __construct()
	{
		$this->db = get_instance();
	}
}
