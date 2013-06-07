<?php
namespace Domains;
use \Models as Model;
use \Exception;
/**
 * 活动评论
 * @author hpw
 * @date 2012/05/7
 */
class EventMsg extends Row
{
	public $event;
	
	public function __construct($event, $row)
	{
		$this->event = $event;

		parent::__construct($row);
	}

	/**
	 * 删除留言
	 */
	public function del()
	{
		if($this->row['type'] == 2)
		{
			$this->load->fastdfs('default','', 'fdfs');
			$re = $this->fdfs->deleteFile($this->row['group'],$this->row['filename']);
			if(!$re)
				throw new Exception('删除失败,请稍后重试', 1);
		}
		$sql = "DELETE FROM web_event_messages WHERE id = {$this->row['id']}";
		$this->db->query($sql);
		return ($this->db->affected_rows() == 1) ? true : false;
	}

}
