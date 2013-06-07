<?php
/**
 * 群组接口
 * @author Huifeng Yao
 */
class GroupService_model extends MY_Model
{

	public function __construct()
	{
		$loader = load_class( 'Loader', 'core' );
		$this->db = $loader->database( 'group', true, true );
	}
	
	/**
	 * 获取用户加入群列表
	 *
	 * @param $uid unknown_type       	
	 * @param $source_type unknown_type       	
	 * @param $extend unknown_type       	
	 */
	public function usergroups( $param )
	{
		$obj = json_decode( $param );
		
		/**
		 * 取出用户所有群组信息，自定义群组，非自定义群组，子群
		 *
		 * @var unknown_type $custom_group 自定义群信息
		 */
		$result = service( 'Group' )->getUserGroup( $obj->uid );
		
		/**
		 * 进行 json 数据组装
		 */
		$ret_arr = array ( 'code' => '1', 'text' => '用户加入群列表', 'result' => array( 'room' => $result ) );
		
		return json_encode( $ret_arr );
	}
	
	/**
	 * 查询父群内的子群列表
	 *
	 * @param $gid unknown_type       	
	 */
	public function subgroups( $param )
	{
		$obj = json_decode( $param );
		
		$result = service( 'Group' )->getSubgroupByGroup( $obj->gid );
		
		/**
		 * 进行 json 数据组装
		 */
		$ret_arr = array ( 'code' => '1', 'text' => '子群列表', 'result' => array( 'room' => $result )  );
		
		return json_encode( $ret_arr );
	}
	
	/**
	 * 查询群信息
	 *
	 * @param $gid unknown_type       	
	 */
	public function groupinfo( $param )
	{
		$obj = json_decode( $param );
		
		$result = service( 'Group' )->getGroupInfo( $obj->id );
		
		/**
		 * 进行 json 数据组装
		 */
		$ret_arr = array ( 'code' => '1', 'text' => '子群列表', 'result' => array( 'room' => $result )  );
		
		return json_encode( $ret_arr );
	}
	
	/**
	 * 群成员列表
	 *
	 * @param $gid unknown_type       	
	 */
	public function groupmembers( $param )
	{
		$obj = json_decode( $param );
		
		$result = service( 'Group' )->getGroupMembers( $obj->gid, $obj->is_sub );
		
		/**
		 * 进行 json 数据组装
		 */
		$ret_arr = array ( 'code' => '1', 'text' => '子群列表', 'result' => array( 'members' => $result )  );
		
		return json_encode( $ret_arr );
	}

}

/* End of file groupservice_model.php */
/* Location: ./app/service/application/controllers/groupservice_model.php */