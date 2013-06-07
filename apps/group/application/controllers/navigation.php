<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 群组
 * title :
 * Created on 2012-06-16
 * @author hexin
 * discription : 群组需要的首页下拉菜单
 */
class Navigation extends MY_Controller
{
	public function __construct(){
		parent::__construct();
		$this->load->helper('common');
	}
	public function index()
	{
		$apps = array(
			0 => array(
				'name' => '粉丝',
				'url'  => mk_url('main/follower/', array('action_dkcode'=>$this->user['dkcode'])),
			),
			1 => array(
				'name' => '好友',
				'url'  => '#'
			),
			2 => array(
				'name' => '亲人',
				'url'  => '#'
			),
			3 => array(
				'name' => '同事',
				'url'  => mk_url('group/index/workmate', array('action_dkcode'=>$this->user['dkcode']))
			),
			4 => array(
				'name' => '同学',
				'url'  => '#',
				'memu' => array(
					0 => array(
						'name' 	=> '大学同学',
						'url'  	=> mk_url('group/index/classnate',array('type'=>'u', 'action_dkcode'=>$this->user['dkcode'])),
					),
					1 => array(
						'name' 	=> '高中同学',
						'url'  	=> mk_url('group/index/classnate',array('type'=>'g', 'action_dkcode'=>$this->user['dkcode']))
					),
					2 => array(
						'name' 	=> '初中同学',
						'url'  	=> mk_url('group/index/classnate',array('type'=>'m', 'action_dkcode'=>$this->user['dkcode']))
					),
					3 => array(
						'name' 	=> '小学同学',
						'url'  	=> mk_url('group/index/classnate',array('type'=>'s', 'action_dkcode'=>$this->user['dkcode']))
					)
				)
			),
			5 => array(
				'name' => '同行',
				'url'  => '#'
			),
		);
		
		$this->load->model('groupmodel', 'group');
		$groups = $this->group->getMyGroups($this->uid);
		$href = mk_url('group/index/detail', array('gid'=>''));
		foreach ($groups as $g) {
			$apps[] = array(
				'name' => $g['name'],
				'url'  => $href.$g['gid']
			);
		}
		
		$apps[] = array(
			'name' => '+',
			'url'  => mk_url('interest/group', array('action_dkcode'=>$this->dkcode))
		);
		
		echo json_encode($apps);
		exit;
	}
	
	public function test()
	{
		echo mk_url('gevent/event/index');exit;
	}
}