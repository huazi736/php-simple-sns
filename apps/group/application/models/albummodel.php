<?php
/*
 * 群组
 * title :
 * Created on 2012-07-07
 * @author hexin
 * discription : 群组相册基本业务逻辑
 */
class AlbumModel extends MY_Model
{
	/**
	 * 获取群组内相册列表
	 * @param int $gid
	 * @return array
	 */
	public function getAlbums($gid)
	{
		return $this->getDao('GroupAlbum')->findAllByGroup($gid);
	}
	
	/**
	 * 添加相册及照片
	 * @param int $gid
	 * @param int $uid
	 * @param string $name
	 * @param string $description
	 * @param array $photoes
	 */
	public function add($gid, $uid, $name, $description = null, $photoes = array())
	{
		$data = array(
			'gid' => $gid,
			'uid' => $uid,
			'name'=> htmlspecialchars($name),
			'description' => nl2br(htmlspecialchars($description)),
			'cover'=> isset($photoes[0]['filename'])?$photoes[0]['filename'].".".$photoes[0]['type']:null,
			'photo_count' => count($photoes),
		);
		$aid = $this->getDao('GroupAlbum')->create($data);
		
		$photoes_insert = array();
		foreach($photoes as $p){
			$photoes_insert[] = array(
				'name' => htmlspecialchars($p['name']),
				'groupname' => $p['groupname'],
				'filename' => $p['filename'],
				'size' => $p['size'],
				'aid' => $aid,
				'type' => $p['type'],
				'uid' => $uid,
				'description' => isset($p['description'])?nl2br(htmlspecialchars($p['description'])):'',
				'create_time' => time(),
			);
		}
		$this->getDao('GroupPhoto')->createMulti($photoes_insert);
		return true;
	}
	
	/**
	 * 更新相册
	 * @param int $aid
	 * @param string $name
	 * @param string $description
	 */
	public function update($aid, $name, $description = null)
	{
		$data['name'] = htmlspecialchars($name);
		$data['description'] = nl2br(htmlspecialchars($description));
		$data['update_time'] = time();
		return $this->getDao('GroupAlbum')->update($aid, $data);
	}
	
	/**
	 * 更新相册顺序
	 * @param $aid
	 * @param $sort
	 */
	public function updateSort($aid, $sort)
	{
		$data['a_sort'] = intval($sort);
		$data['update_time'] = time();
		return $this->getDao('GroupAlbum')->update($aid, $data);
	}
	
	/**
	 * 更新封面照
	 * @param int $aid
	 * @param string $cover
	 */
	public function updateCover($aid, $cover)
	{
		$data['cover'] = $cover;
		$data['update_time'] = time();
		return $this->getDao('GroupAlbum')->update($aid, $data);
	}
	
	/**
	 * 删除相册
	 * @param int $aid
	 */
	public function deleteAlbum($aid)
	{
		$this->getDao('GroupPhoto')->deleteByAlbum($aid);
		$this->getDao('GroupAlbum')->delete($aid);
		return true;
	}
	
	/**
	 * 获取群组相册的照片列表
	 */
	public function getPhotoes($aid)
	{
		return $this->getDao('GroupAlbum')->findAllByAlbum($aid);
	}
	
	/**
	 * 添加照片，可以添加一张照片也可以批量添加
	 * @param int $aid
	 * @param int $uid
	 * @param array $photoes
	 * @return boolean
	 */
	public function addPhotoes($aid,$uid,$photoes = array()){
		if(!is_array($photoes)) return false;
		if(empty($photoes)) return false;
		if(!isset($photoes[0]['name'])) $photoes = array($photoes);
		$photoes_insert = array();
		foreach($photoes as $p) {
			$photoes_insert[] = array(
				'name' 		=> htmlspecialchars($p['name']),
				'groupname' => $p['groupname'],
				'filename' 	=> $p['filename'],
				'size' 		=> $p['size'],
				'aid' 		=> $aid,
				'type'		=> $p['type'],
				'uid'		=> $uid,
				'description'=> isset($p['description'])?nl2br(htmlspecialchars($p['description'])):'',
				'create_time'=> time(),
			);
			
		}
		$this->getDao('GroupPhoto')->createMulti($photoes_insert);
		
		$count = count($photoes_insert);
		$this->getDao('GroupAlbum')->setPhotoInc($aid, $count);
		return true;
	}
	
	/**
	 * 更新照片，可更新一张照片也可批量更新
	 * @param int $aid
	 * @param array $photoes
	 */
	public function updatePhotoes($aid, $photoes = array())
	{
		if(!is_array($photoes)) return false;
		if(empty($photoes)) return false;
		if(!isset($photoes[0]['name'])) $photoes = array($photoes);
		foreach($photoes as $p) {
			$photo_update = array(
				'id' => intval($p['id']),
				'name' => htmlspecialchars($p['name']),
				'groupname' => isset($p['groupname'])? $p['groupname'] : null,
				'p_sort' => intval($p['p_sort']),
				'description' => isset($p['description'])?nl2br(htmlspecialchars($p['description'])):'',
				'is_delete' => isset($p['is_delete'])?intval($p['is_delete']):GroupConst::GROUP_NOT_DELETE,
			);
			$this->getDao('GroupPhoto')->update($photo_update['id'], $photo_update);
		}
		return true;
	}
	
	/**
	 * 更新单张照片顺序
	 * @param $aid
	 * @param $sort
	 */
	public function updateSort($pid, $sort)
	{
		$data['p_sort'] = intval($sort);
		return $this->getDao('GroupPhoto')->update($pid, $data);
	}
	
	/**
	 * 删除照片，可单张删除可批量删除
	 * @param int $aid
	 * @param max $pids
	 */
	public function deletePhotoes($aid, $pids)
	{
		if(!is_array($pids)) $pids = array(intval($pids));
		return $this->getDao('GroupPhoto')->delete($pids);
	}
	
	/**
	 * 某照片的评论数自增
	 * @param int $pid
	 * @param int $value 自增值，默认自增1
	 */
	public function photoCommetInc($pid,$value = 1) {
		return $this->getDao('GroupPhoto')->setCommentInc($pid, $value);
	}
}