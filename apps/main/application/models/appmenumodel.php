<?php
/**
 * 应用区菜单模型
 *
 * @author zengmingming
 * @date 2012/7/2
 */

class AppmenuModel extends MY_Model {

	/**
	 * 类库定义
	 */

	// 用户库
	const USER_DB = 'user';
	// 系统库
	const SYSTEM_DB = 'system';

	/**
	 * USER_DB类库的表名定义
	 */

	// 系统应用菜单表
	const MAIN_MENU = 'main_menu'; 

	/**
	 * SYSTEM_DB类库的表名定义
	 */
	
	// 用户应用菜单表
	const USER_MENU_PURVIEW = 'user_menu_purview'; 

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * debug
	 */
	function debug($params)
	{
		echo '<pre>';
		print_r($params);
		echo '</pre>';
	}

	/**
	 * 获取用户主页应用区的菜单
	 *
	 * 菜单项中带有权限值
	 *
	 * @param int $uid 访问者UID
	 * @param int $touid 被访问者UID
	 * @param string $orderby 排序方式(默认按照排序值从小到大排序)
	 *
	 * @return array
	 */
	public function getAppMenu($uid, $touid, $orderby = 'DESC')
	{
		if (empty($uid)) { return array(); }

		if (empty($touid)) { $touid = $uid; }

		// 获取系统定义的应用区菜单
		$sys_app_menu = service('SystemPurview')->getAppList();
		
		if (!empty($sys_app_menu)) {
			foreach ($sys_app_menu as $k=>$v) {
				$tmparr_sys[$v['menu_moudle']] = $v;
			}
			$sys_app_menu = $tmparr_sys;
		}
		
		// 获取用户定义的应用区菜单
		$user_app_menu = $this->getUserMenu($touid, $orderby);
		
		if (!empty($user_app_menu)) {
			foreach ($user_app_menu as $k=>$v) {
				$tmparr[$v['menu_module']] = $v;
			}
			$user_app_menu = $tmparr;
		}
		
		if (!empty($user_app_menu)) {			
			// 应用区菜单已被用户设置

			// 记录是否需要按用户自定义的排序方式显示
			$issort = FALSE;
			if (count($sys_app_menu) == count($user_app_menu)) {
				$issort = TRUE;
			}

			// 系统定义的应用区菜单和用户设置的应用区菜单合并
			foreach ($sys_app_menu as $k=>$v) {
				if (isset($user_app_menu[$v['menu_moudle']])) {
					$sys_app_menu[$k] = array_merge($sys_app_menu[$k], $user_app_menu[$v['menu_moudle']]);
					
					//杨顺军 2012-07-11 修改 重构应用区封面
					if(!empty($user_app_menu[$v['menu_moudle']]['group']) && !empty($user_app_menu[$v['menu_moudle']]['menu_img'])){
						$sys_app_menu[$k]['menu_ico'] = $user_app_menu[$v['menu_moudle']]['group'] . DIRECTORY_SEPARATOR . $user_app_menu[$v['menu_moudle']]['menu_img'];
					}
					if(isset($user_app_menu[$v['menu_moudle']]['menu_order']) && !empty($user_app_menu[$v['menu_moudle']]['menu_order'])){
						$sys_app_menu[$k]['menu_sort'] = $user_app_menu[$v['menu_moudle']]['menu_order'];
					}
				}
				if(!isset($sys_app_menu[$k]['menu_sort'])){
					$sys_app_menu[$k]['menu_sort'] = 0;
				}
				$menu_sort[$k] = $sys_app_menu[$k]['menu_sort'];
			}
			array_multisort($menu_sort, SORT_DESC, $sys_app_menu);
			if ($uid != $touid) {
				// 用户访问他人主页

				// 获取访问者和被访问者的关系权限值
				$relation_weight = service('Relation')->getRelationWeightWithUser($uid, $touid);

				foreach ($sys_app_menu as $k=>$v) {
					if (isset($v['weight']) && $relation_weight < $v['weight']) {
						// 删除关系权限值小于用户定义的权限值
						unset($sys_app_menu[$k]);
					} elseif (isset($v['weight']) && $v['weight'] == -1) {
						// 自定义权限
						$userlist = json_decode($v['userlist_content']);
						if (!in_array($uid, $userlist)) {
							unset($sys_app_menu[$k]);
						}
					}
				}
			}
			if ($issort && $sys_app_menu) {
				// 菜单排序

				foreach ($sys_app_menu as $v) {
					if (isset($v['menu_order'])) {
						$sortarr[] = $v['menu_order'];
					} else {
						// 修复如下bug
						// 用户对应用菜单排序后, 系统又新增应用菜单
						// 暂时将新增菜单放在最后面
						$sortarr[] = 0;
					}
					
				}

				array_multisort($sortarr, SORT_DESC, $sys_app_menu);
			}
		}
		$sys_app_menu = $this->checkSysWeight($sys_app_menu, $user_app_menu, $uid, $touid);
		return $sys_app_menu;
	}
	
	/**
	 * 检测用户系统权限设置
	 * 
	 * @param array $sys_app_menu
	 * @param array $user_app_menu
	 */
	public function checkSysWeight($sys_app_menu, $user_app_menu, $uid, $touid){
		if( is_array($sys_app_menu) ){
			foreach ( $sys_app_menu AS $key => $val){
				//如果系统设置只有系统权限一项,对应关系做判断
				
				//用户自定义
				if( (!isset($user_app_menu[$key]['weight']) || empty($user_app_menu[$key]['weight'])) && $val['purview_list'] == '-1' ){
					$sys_app_menu[$key]['weight'] = -1;
				}
				
				//公开
				elseif( (!isset($user_app_menu[$key]['weight']) || empty($user_app_menu[$key]['weight'])) && $val['purview_list'] == '1' ){
					$sys_app_menu[$key]['weight'] = 1;
				}
				
				//好友
				elseif( (!isset($user_app_menu[$key]['weight']) || empty($user_app_menu[$key]['weight'])) && $val['purview_list'] == '4' ){
					$sys_app_menu[$key]['weight'] = 4;
					if ( $uid != $touid && isset($v['weight']) && $relation_weight < $v['weight'] ) {
						// 删除关系权限值小于用户定义的权限值
						unset($sys_app_menu[$key]);
					}
				}
				
				//仅自己可见
				elseif( (!isset($user_app_menu[$key]['weight']) || empty($user_app_menu[$key]['weight'])) && $val['purview_list'] == '8' ){
					$sys_app_menu[$key]['weight'] = 8;
					if($uid != $touid) unset($sys_app_menu[$key]);
					
				}
				
				//没有设置设为仅自己可见
				elseif( (!isset($user_app_menu[$key]['weight']) || empty($user_app_menu[$key]['weight'])) && $val['purview_list'] == '' ){
					$sys_app_menu[$key]['weight'] = 8;
					$sys_app_menu[$key]['purview_list'] = '8';
				} elseif( !isset($user_app_menu[$key]['weight']) || empty($user_app_menu[$key]['weight']) ) {
					
					$weight_list = explode(',', $val['purview_list']);
					if(in_array('1', $weight_list)){
						$sys_app_menu[$key]['weight'] = 1;
					} else {
						$sys_app_menu[$key]['weight'] = min($weight_list);
					}
				}
			}
		}
		
		return $sys_app_menu;
	}

	/**
	 * 获取用户定义的应用区菜单
	 *
	 * @param string $orderby 排序方式(默认按照排序值从小到大排序)
	 *
	 * @return array
	 */
	public function getUserMenu($uid = 0, $orderby = 'DESC')
	{
		if (empty($uid)) { return array(); }

		// 连接用户库
		$this->init_db(self::USER_DB);

		// 排序
		$this->db->order_by('menu_order', $orderby);

		$this->db->where('uid', $uid);

		return $this->db->get(self::USER_MENU_PURVIEW)->result_array();
	}

	/**
	 * 获取系统定义的应用区菜单
	 *
	 * @param string $orderby 排序方式(默认按照排序值从小到大排序)
	 *
	 * @return array
	 */
	public function getSysMenu($orderby = 'DESC')
	{
		// 连接系统库
		$this->init_db(self::SYSTEM_DB);

		// 排序
		$this->db->order_by('menu_sort', $orderby);

		// 选择系统设置有效的菜单
		$this->db->where('menu_status', 1);

		return $this->db->get(self::MAIN_MENU)->result_array();
	}

	/**
	 * 应用区菜单排序
	 *
	 * @param int $uid 用户UID
     * @param array $menuids 用户菜单及其排序数组
	 */
	public function sortAppMenu($uid, $menuids)
	{
		
		if (empty($uid) || empty($menuids)) { return FALSE; }

		// 连接用户库
		$this->init_db(self::USER_DB);

        foreach ($menuids as $k => $v) {

            //默认权限
            $weight = 0;

            switch ($v[0]) {
                case 'interest': case 'praise': case 'msg': case 'favorite': case 'ask':
                    $weight = 8;
                    break;
            }

            $where = array(
				'uid' => $uid,
				'menu_module' => $v[0]
            );

            $data = array_merge($where, array('menu_order' => $v[1], 'weight' => $weight));
			
			$nums = $this->db->where($where)->get(self::USER_MENU_PURVIEW)->num_rows();
            
            if (!$nums) {
            	$sys_app_info = $this->selectSysMenu(0, $v[0], array('menu_id'));
	        	if(!isset($sys_app_info['menu_id']) && $sys_app_info['menu_id']){
	        		return false;
	        	}
	        	$data['menu_id'] = $sys_app_info['menu_id'];
				// 设置菜单排序值
               $this->addUserApp($data);
            } else {
				// 更新菜单排序值
               $this->updateUserApp($data, $where);
            }
        }
        
        return TRUE;
	}

	/**
	 * 设置应用区菜单的权限
	 *
	 * example
	 * $data[] = array(
	 *		'uid' => $uid, // 用户UID
	 *		'menu_id' => $menu_id, // 菜单ID
	 *		'weight' => $weight, // 菜单权值
	 *		'userlist_content'=> $userlist_content, // 用户自定义用户列表
	 *	);
	 * 
	 * @param array $data
	 *
	 * @return boolean
	 */
	public function setAppMenuPurview($data)
	{
		if (empty($data['uid']) || !$data['menu_module']) { return FALSE; }
		// 连接用户库
		$this->init_db(self::USER_DB); 
			
        $where = array(
			'uid' => $data['uid'],
			'menu_module' => $data['menu_module']
        );
		
        $nums = $this->db->where($where)->get(self::USER_MENU_PURVIEW)->num_rows();
        if (!$nums) {
        	$sys_app_info = $this->selectSysMenu(0, $data['menu_module'], array('menu_id'));
        	if(!isset($sys_app_info['menu_id']) && $sys_app_info['menu_id']){
        		return false;
        	}
        	
        	$data = array_merge($where, array('menu_img' => '', 'group' => '', 'weight' => $data['weight'], 'menu_id' => $sys_app_info['menu_id']));
        	
			// 设置菜单权限
			$this->addUserApp($data);
           
        } else {
        	// 连接用户库
			$this->init_db(self::USER_DB); 
			
			// 更新菜单权限
            $this->updateUserApp($data, $where);
        }
        
        return TRUE;

	}
	
	/**
	 * 查询个人应用系统信息
	 * 
	 * @param integer $menu_id
	 * @param string $module
	 */
	public function selectSysMenu($menu_id = 0, $module = '', $fields = array()){
		$fields = implode(',', $fields);
		$where = array();
		if($menu_id){
			$where['menu_id'] = $menu_id;
		}
		
		if($module){
			$where['menu_moudle'] = $module;
		}
		// 连接用户库
		$this->init_db(self::SYSTEM_DB); 
		
		return $this->db->select($fields)->where($where)->get(self::MAIN_MENU)->row_array();
		
	}
	
	/**
	 * 添加到用户自定义库
	 * 
	 * @param $data
	 */
	public function addUserApp($data){
		// 连接用户库
		$this->init_db(self::USER_DB); 
		$this->db->insert(self::USER_MENU_PURVIEW, $data);
	}
	
	/**
	 * 更新到用户自定义库
	 * 
	 * @param $data
	 * @param $where
	 */
	public function updateUserApp($data, $where){
		// 连接用户库
		$this->init_db(self::USER_DB);
		$this->db->update(self::USER_MENU_PURVIEW, $data, $where);
	}
	
	/**
	 * 判断用户区系统设置权限显示
	 * 
	 * @param integer $sys_app_purview 应用区设置权限
	 */
	public function checkSysApp( $sys_app_purview = 0 ){
		//用户默认设置权限
		$user_purview = 0;
		switch( $sys_app_purview ) {
			case -1 :
				//自定义
				$user_purview = -1;
				break;
			case 1 :
				//公开
				$user_purview = 1;
				break;
			case 4 : 
				//好友
				$user_purview = 4;
				break;
			case 8 :
				//仅自己可见
				$user_purview = 8;
				break;
			default:
				//默认仅自己可见
				$user_purview = 8;
		}
		
		return $user_purview;
	}
}