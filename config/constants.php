<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);
define('SYS_TIME',time());
/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

//define('CSS_VER','1.3');
//define('JS_VER','1.3');
//edit by lijianwei 
define('CSS_VER', SYS_TIME);
define('JS_VER', SYS_TIME);
/* End of file constants.php */
/* Location: ./application/config/constants.php */

//群组统一状态参数
Class GroupConst
{
	//群成员角色
	const GROUP_ROLE_MEMBER = 0; //普通用户
	const GROUP_ROLE_MASTER = 1; //群主
	const GROUP_ROLE_ADMIN = 2; //群管理员
	
	const GROUP_OPERATE_NOBODY = 0; //任何人都拒绝操作
	const GROUP_OPERATE_MASTER = 1; //仅限群主操作
	const GROUP_OPERATE_ADMIN  = 2; //仅限管理员操作
	const GROUP_OPERATE_MEMBER = 3; //仅限群成员操作
	const GROUP_OPERATE_ALL    = 4; //所有访问者都可操作
	
	//审核状态
	const GROUP_STATUS_APPLY = 0; //申请
	const GROUP_STATUS_PASS = 1; //通过
	
	//群聊功能是否开通
	const GROUP_CHAT_ENABLED = 1; //开通
	const GROUP_CHAT_CLOSED = 0; //关闭
	
	const GROUP_PROCESSING_SUCCESS = 1; //处理成功
	const GROUP_PROCESSING_WAITTING = 0; //等待处理
	
	const GROUP_INVITE_ACCEPT = "ACCEPT"; //邀请结果，接受
	const GROUP_INVITE_REFUSE = "REFUSE"; //邀请结果，拒绝
	
	const GROUP_DELETE = 0; //删除标志
	const GROUP_NOT_DELETE = 1; //未删除标志
	
	const GROUP_TYPE_FRIEND = "FRIEND";		  //朋友
	const GROUP_TYPE_ATTENTION = "ATTENTION"; //关注
	const GROUP_TYPE_FANS = "FANS";			  //粉丝
	const GROUP_TYPE_CLASSMATE = "CLASSMATE"; //同学
	const GROUP_TYPE_COLLEAGUE = "COLLEAGUE"; //同事
	const GROUP_TYPE_PEER = "PEER";			  //同行
    const GROUP_TYPE_RELATIVE = "RELATIVE";   //亲人
	const GROUP_TYPE_CUSTOM = "CUSTOM";		  //自定义
	
	const GROUP_APP_INFOMATION_FLOW = 1; //信息流应用ID
	const GROUP_APP_ACTIVITY = 2; //活动应用ID
	
	const GROUP_PAGESIZE = 20; //分页
}

//错误代码
Class ErrorCode
{
	//成功无错
	const CODE_SUCCESS = 1;
	//无效的提交
	const CODE_INVALID_POST = 501001;
	//无效的请求
	const CODE_INVALID_GET = 501002;
	
	
	//用户模块
	//登录失败
	const CODE_LOGIN_FAILD = 502001;
	
	//群组模块
	//群组不存在
	const CODE_GROUP_NOT_EXIST = 503001;
	//没有操作权限
	const CODE_GROUP_NO_PERMISSION = 503002;
	//群成员已存在
	const CODE_GROUP_MEMBER_EXIST = 503003;
	//该成员不在该群组中
	const CODE_GROUP_MEMEBE_NOT_EXIST = 503004;
	//已确认过该邀请
	const CODE_INVITED_PROCESSED = 503005;
	
	//IM模块
	//IM连接异常
	const CODE_IM_CONNECTION_EXCEPTION = 504001;
	//IM操作失败
	const CODE_IM_OPERATE_FAILED = 504002;
	
	//好友关系模块
	//数量不能少于1个
	const CODE_RELATION_MIN = 505001;
    
    //后台参数限制
    //超出可创建群个数限制
    const CODE_GROUP_NUM_EXCEED_THE_LIMIT = 506001;
    //超出可创建子群个数限制
    const CODE_SUBGROUP_NUM_EXCEED_THE_LIMIT = 506002;
    //超出群成员数量限制
    const CODE_GROUP_MEMBER_NUM_EXCEED_THE_LIMIT = 506003;
    //超出子群成员数量限制
    const CODE_SUBGROUP_MEMBER_NUM_EXCEED_THE_LIMIT = 506004;
    //超出群邀请成员的度数限制
    const CODE_GROUP_INVIDED_DEGREE_EXCEED_THE_LIMIT = 506005;
}
