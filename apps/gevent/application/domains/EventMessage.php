<?php
namespace Domains;
use \Exception;
/**
 * 留言model
 * @author hpw
 * @date  2012/07/07
 */
class EventMessage extends Row
{
	
	public function __construct($joinData)
	{
		$this->row = $joinData;
		parent::__construct();
	}
	/**
	 * 删除留言
	 * 
	 */
	public function del()
	{
		$sql = "DELETE FROM group_event_messages WHERE
			id = {$this->row['id']}";
		
		if($this->row['group'] && $this->row['filename'])
		{
			$this->load->fastdfs('default','', 'fdfs');
			if($this->fdfs->deleteFile($this->row['group'], $this->row['filename']))
				throw new Exception('删除失败,请重试', 1);
		}
		$this->db->query($sql);
		return ($this->db->affected_rows() == 1) ? true : false;

	}
}
