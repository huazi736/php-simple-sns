<?php
namespace Domains;
use \Models as Model;
/**
 * 一条用户留言
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
	 * (自己删除自己的)
	 */
	public function del()
	{
		$result = true;
		if($this->row['group']){
			$fdfs = get_storage('event');
			$result = $fdfs->deleteFile($this->row['group'], $this->row['filename']);
		}
		if($result){
			$sql = "DELETE FROM event_messages WHERE
			id = {$this->row['id']} and uid = {$this->row['uid']}";
			$this->db->query($sql);
			$result = $this->db->affected_rows() == 1 ? true : false;
		}
		return $result;

	}
}
