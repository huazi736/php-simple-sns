<?php
namespace Domains;

use \Models as Model;

/**
 * 用户model
 * @author hpw
 * @date  2012/07/07
 */
class EventUser extends Row
{
	public $event;
	

	/**
	 * @param object $event 
	 */
	public function __construct($data, $event=null)
	{
		$this->event = $event;
		$this->row = $data;
		$this->uid = $data['user_id'];
		parent::__construct();
		
	}
	
	/**
	 * 得到群组活动列表
	 * @param $offset 
	 * @param $length
	 * @param bool $isEnd 是否结束的活动 
	 */
	public function getGroupEvents($offset, $length,$gid, $isEnd=false)
	{
		if(!$isEnd)
			$timeCondition = ' endtime >NOW()';
		else
			$timeCondition = ' endtime <NOW()';
		$sql = "SELECT
			id, user_id,group_id, starttime, name, fdfs_group,area,address,endtime, fdfs_filename, join_num 
			FROM group_event WHERE group_id = '{$gid}'  
			ORDER BY endtime desc,id desc
			LIMIT {$offset}, {$length}";

		$query = $this->db->query($sql);
		$rows = array();

		include APPPATH.DS.'config'.DS.'area.php';
		foreach($query->result_array() as $row)
		{
			$nation = '';
			$province = '';
			$city = '';
			$row['img'] = url_fdfs($row['fdfs_group'], $row['fdfs_filename'], '_s');
			
			$areaArr = explode('/',$row['area']);
			if($areaArr[0]!='-1')
			{				
				$area = json_decode($areaJson);
				if(isset($areaArr[0]))
					$nation = $area->$areaArr[0]->area_name;
				if(isset($area->$areaArr[0]->list->$areaArr[1]))
					$province = $area->$areaArr[0]->list->$areaArr[1]->area_name;
				if(isset($area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]))
					$city = $area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]->area_name;
			}
			$row['address'] = $nation.$province.$city.$row['address'];
			$serviceRe = service_api('User', 'getUserInfo', array($row['user_id'], 'uid', array('username','dkcode')));
			$row['user_name'] = $serviceRe['username'];
			$row['user_url'] = mk_url('main/index/main',array('dkcode'=>$serviceRe['dkcode']));
			$row['url'] = mk_url('wevent/event/detail',array('id'=>$row['id'],'gid'=>$gid));
			$rows[] = $row;
			
		}
		return $rows;
	}

	/**
	 * 得到我的活动
	 */
	public function getMyEvents($offset, $length, $gid, $isEnd=false)
	{

		$sql = "SELECT
			user_id, type, event_id
			FROM group_event_users WHERE user_id = '{$this->uid}' AND type>1 AND group_id = {$gid}
			LIMIT {$offset}, {$length}";

		$query = $this->db->query($sql);

		return $this->_get_list($query->result_array(), $isEnd);
	}

	
	protected function _get_list($query, $isEnd=false )
	{
		if (empty($query))
			return array();
			
		if(!$isEnd)
			$timeCondition = ' endtime >NOW()';
		else
			$timeCondition = ' endtime <NOW()';

		$eids = $rows = array();

		foreach ($query as $row) 
			$eids[] = $row['event_id'];

		if (!empty($eids)) {
			$sql = "SELECT
			id, starttime, name,group_id, fdfs_group,area,address,endtime, fdfs_filename, join_num ,user_id
			FROM group_event WHERE id IN(".implode(',', $eids).")  ORDER BY endtime desc,id desc";

			$tmp = $this->db->query($sql)->result_array();
	
			
			include APPPATH.DS.'config'.DS.'area.php';
			foreach ($tmp as $row)
			{
				$nation = '';
				$province = '';
				$city = '';
				$areaArr = explode('/',$row['area']);
				if($areaArr[0]!='-1')
				{					
					$area = json_decode($areaJson);
					if(isset($areaArr[0]))
						$nation = $area->$areaArr[0]->area_name;
					if(isset($area->$areaArr[0]->list->$areaArr[1]))
						$province = $area->$areaArr[0]->list->$areaArr[1]->area_name;
					if(isset($area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]))
						$city = $area->$areaArr[0]->list->$areaArr[1]->list->$areaArr[2]->area_name;
				}
				$row['address'] = $nation.$province.$city.$row['address'];
				$row['img'] = url_fdfs($row['fdfs_group'], $row['fdfs_filename'], '_s');
				$serviceRe = service_api('User', 'getUserInfo', array($row['user_id'], 'uid', array('username', 'dkcode')));
				$row['user_name'] = $serviceRe['username'];
				$row['user_url'] = mk_url('main/index/main',array('dkcode'=>$serviceRe['dkcode']));
				$row['url'] = mk_url('wevent/event/detail',array('id'=>$row['id'],'gid'=>$row['group_id']));
				$rows[] = $row;
			}
		}


		return $rows;
	}
	

	/**
	 * 更改答复
	 */
	public function changeAnswer($state)
	{
		

		//用户复答没有改变
		if ($this->row['type'] == $state)
			return;

		$sql = "update group_event_users set type = {$state} where event_id={$this->row['event_id']} AND user_id={$this->row['user_id']}";
		
		$this->db->simple_query($sql);
		if ($state == 1) 
			$this->event->_join_num('-1');
		else
			$this->event->_join_num('+1');

	}

	
	

	/**
	 * 删除用户并禁止用户再次参加活动
	 */
	public function blockUser($user)
	{
		/*
		 * 如果原来用户答复参加的,则用户计器减1
		 */
		if ($user['type'] == 2 ) {
			$this->event->_join_num('-1');
		}

		$sql = "UPDATE group_event_users SET type = 0 WHERE event_id = {$this->row['event_id']} AND user_id = {$user['user_id']} ";
		$this->db->query($sql);
	}

	
	/**
	 * 查看用户是否有管理权限
	 */
	public function canAdmin()
	{
		return $this->row['type']==3;
	}

	
	/**
	 * return -1 未参加 0 禁止参加 1 不参加  2 参加  3 创建者
	 */
	
	public function checkAnswer($gid, $eid)
	{
		$sql = "select type from group_event_users where group_id={$gid} and event_id ={$eid} and user_id={$this->user['uid']}";
		
		$row = $this->db->query($sql)->row_array();
		if(empty($row))
			$row['type'] =-1;
		return $row['type'];			
		
	}
}
