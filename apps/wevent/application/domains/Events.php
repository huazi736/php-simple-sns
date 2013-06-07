<?php
namespace Domains;
use \Models as Model;
use \Exception;
/**
 * 活动
 * @author hpw
 * @date 2012/05/3
 */
class Events extends \DK_Model
{
	public $webinfo;
	public $user;

	public function __construct($webinfo, $user)
	{
		parent::__construct();
		$this->webinfo = $webinfo;
		$this->user = $user;
		$this->init_db('event');
	}
	
	/**
	 * 得到活动
	 * @param $eid 
	 * @return object or null
	 */
	public function getEvent($eid)
	{
		$sql = "SELECT * FROM web_event WHERE id = '{$eid}' AND webid={$this->webinfo['aid']}";

		$row  = $this->db->query($sql)->row_array();

		if ($row)
			return new Event($this->webinfo, $this->user, $row);
		return null;
	}
	
	/**
	 * 得到活动列表
	 * @param $offset 
	 * @param $length
	 * @param bool $isEnd 是否结束的活动 
	 * @return array $rows
	 */
	public function getEvents($offset, $length, $isEnd=false)
	{
		if(!$isEnd)
		{
			$timeCondition = ' endtime >NOW()';
		}
		else
		{
			$timeCondition = ' endtime <NOW()';
		}
		$sql = "SELECT
			id, starttime, name, fdfs_group,area,address,endtime, fdfs_filename, join_num 
			FROM web_event WHERE webid = '{$this->webinfo['aid']}'  AND {$timeCondition}
			ORDER BY starttime,id
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
			$row['answer'] = $this->checkAnswer($row['id']);
			$row['address'] = $nation.$province.$city.$row['address'];
			$rows[] = $row;
			
		}
		return $rows;
	}


	/**
	 * 创建活动
	 * @param array      $data    活动信息
	 * @param localfile  $img
	 * @param localfile  $img_s
	 * @param localfile  $img_b
	 */
	public function create($data, $img, $img_s, $img_b)
	{
		

		$inserts = $data;
		$inserts['webid'] = $this->webinfo['aid'];
		$inserts['join_num'] = 0;
		$inserts['addtime'] = date('Y-m-d H:i:s');

		if ($img) {
			$reImg = $this->saveImg($img, $img_s, $img_b);

			$inserts['fdfs_group'] = $reImg['group_name'];
			$inserts['fdfs_filename'] = $reImg['filename'];
		}
		else {
			$inserts['fdfs_group'] = '';
			$inserts['fdfs_filename'] = '';
		}

		$bool = $this->db->insert('web_event', $inserts);

		if (!$bool) {
			throw new Exception('创建活动失败', 1);
		}

		$inserts['id'] = $this->db->insert_id();

		$event_info = array(
			'id'=> $inserts['id'],
			'uid'=> $this->webinfo['uid'],
			'is_web' => 1,
  			'starttime'=> $inserts['starttime'],
			'filename'=> $inserts['fdfs_filename'],
			'groupname'=> $inserts['fdfs_group'],
			'title'=> $inserts['name'],
			'uname' => $this->webinfo['name'],
			'join_num'=> 1,
			'address'=>$inserts['address'],
			'endtime'=>$inserts['endtime'],
			'detail'=>$inserts['detail'],
			'time'=>strtotime($inserts['addtime']),
			'web_id'=>$this->webinfo['aid']
		);

		service_api('RelationIndexSearch', 'addOrUpdateEventInfo', array($event_info));

		$event = new Event($this->webinfo, $this->user, $inserts);

		/**
		 * 添加到时间线
		 */
		$event->_timeline_add();

		return $event;
	}

	protected function saveImg($img, $img_s, $img_b)
	{
		$this->load->fastdfs('default','', 'fdfs');

		$reImg = $this->fdfs->uploadFile($img);

		$this->fdfs->uploadSlaveFile($img_s, $reImg['filename'], '_s');
		$this->fdfs->uploadSlaveFile($img_b, $reImg['filename'], '_b');

		return $reImg;
	}

	public function getCover()
	{
		$event = $this->getEvents(0, 1);

		if (empty($event) || empty($event[0]['fdfs_group'])) {
			$img = MISC_ROOT . "img/default/active.gif";
		}
		else {
			$img = str_replace('_s', '_b', $event[0]['img']);
		}

		return $img;
	}

	public function getEventCount($isEnd=false)
	{
		if($isEnd)
			$sql = "SELECT COUNT(*) as num
			FROM web_event WHERE webid = {$this->webinfo['aid']}  AND endtime <NOW()";
		else
			$sql = "SELECT COUNT(*) as num
			FROM web_event WHERE webid = {$this->webinfo['aid']}  AND endtime >NOW()";
			
		$row = $this->db->query($sql)->row_array();

		if (empty($row)) {
			return 0;
		}
		else {
			return $row['num'];
		}
	}
	
	public function checkAnswer($eid)
	{
		$sql = "select answer from web_event_users where event_id={$eid} and user_id={$this->user['uid']}";
		
		$row = $this->db->query($sql)->row_array();
		if(empty($row))
			$row['answer'] =-1;
		return $row['answer'];			
		
	}

}
