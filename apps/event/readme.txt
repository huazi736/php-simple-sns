@author weihua
@version 5 2012/5/4

0.安装
	a.需要修改的配置
		1./application/config/config.php
		配制项:server_url, avatar_base, fastdfs_port, fastdfs_port

		2./application/config/constants.php
		配制项:APP_NAME,WEB_ROOT

		3./application/config/database.php
	
	b.需要php扩展GD2,FastDFS

	c.开发机环境为PHP5.3.5

	d./tmp 文件夹需要读写权限

1. 未达到设计功能

2.测试需注意

3.性能有潜在问题的
	a.当前form表单存储使用了SESSION,可能导至性能下除,
	后面可能需要单独为它做存储

4.基础平台相关问题
	a.核心层SESSION缺少一次性批量货得和批量设置的方法,现在会进行
	很多次soap请求,比较浪费性能

	b.当前的SESSION实现在同一个会话同时多个请求时有相互覆盖的可能性.

	c.用户头像无法判断有无

5.对原demo作的修改
	a.为soap请求重写了封装

	b.重写了SESSION存储机制,实现了在$_SESSION保存会认,并自动存取

	c.增加form管理

	d.mk_url进行了改写

####################
程序简要说明
####################

	/application/controllers/event.php
	控制器,所有请求接收都在这儿

	/application/models/DetailForm.php
	活动编辑表单

	/application/models/FdfsModel.php
	分布式存储封装

	/application/models/ImageModel.php
	图片处理类

	/application/models/MagicModel.php
	SOAP封装

	/application/models/MY_Session.php
	SESSION管理类

	/application/helpers/time_helper.php
	time助手,现在有给时间分组

	/application/helpers/uri_helper.php
	url助手 (文件名uri只是为人避免覆盖ci的url助手)

业务逻辑封装

	/application/domains/Events.php           活动
		getEvent     得到一个活动
		create       创建一个活动

	/application/domains/Event.php            一个活动
		edit         编辑一个活动
		invite       邀请参与用户
		getUsers     得到活动参与用户
		getUser      得到一个用户
		setAdmins    设置活动管理员
		getAdmins    得到活动管理员
		cancel       取消活动
		getMessages  得到活动留言
		getMessage   得到一个留言
		isShowUsers  是否显示用户列表

	/application/domains/EventUser.php        一个活动参与用户
		answer         答复邀请
		changeAnswer   更改答复
		addMessage     发表留言
		blockUser      禁止用户参加活动
		canAdmin       查看用户是否有管理权限
		canReply       查看表户是否可以回复
		notifyFollowers 异步活动分发(新添的方法)(用户参加活动时调用此方法)

	/application/domains/EventMessage.php     一个回复
		del            删除一条回复

	/application/domains/UserEvents.php       用户的活动
		getEvents      得到"我的活动"
		getOtherEvents 得到"其它活动"
		getEndEvents   得到"己结束活动"
		applyJoin      申请参加活动("我要参加")


######################
表据库表
######################

event           活动主表
	id              int
	uid             int           创建人
	name            char(50)      活动名称
	address         char(50)      活动地点
	city            cahr(50)      市/县
	street          char(50)      街道
	detail          char(140)     详情
	starttime       timestamp     活动开始时间
	endtime         timestamp     活动结束时间
	is_show_users   tinyint       显示参与人列表[0 不显示][1 显示]
	fdfs_group      varchar(10)   图片.FastDFS
	fdfs_filename   varchar(60)
	join_num        int           参与用户数量
	PRIMARY(id)
	key(uid)

event_invite    活动邀请跟踪表
	id            int
	event_id      int             活动id
	from_uid      int             邀请人id
	to_uid        int             被邀请人id
	send_time     timestamp       发送邀请时间
	is_answer     tinyint         被邀请人是否己答复
	answer_time   timestamp       回复时间
	PRIMARY(id)
	key(event_id, from_uid, to_uid)

event_messages  活动的回复
	id          int
	event_id    int               活动id
	uid         int               用户id
	message     char(140)         留言内容
	addtime     timestamp         留言时间
	type        tinyint           评论类型[1 文字][2 图片][3 视频]
	src         varchar(255)      源文件地址(视频、图片)
	PRIMARY(id)
	key(uid)

event_users     活动的参与用户表
	id            int
	event_id      int             活动id
	uid           int             用户id
	type          tinyint         参与用户类型[0 一般人][1 管理员][2 创建者][-1 被禁止参加]
	answer        tinyint         用户回复[0 不参与][1 未回复][2 可能参加][3 确定参加]
	PRIMARY(id)
	key(event_id, uid)

user_events     用户的活动表
	id            int
	uid           int             用户id
	result        tinyint         [1 我的活动][2 其它活动]
	hide          tinyint         [0 公开][1 隐藏]
	type          tinyint         活动类型[1 首页活动][2 网页活动]
	event_id      int             活动id
	c_starttime   timestamp       cache event.starttime
	c_endtime     timestamp       cache event.endtime
	from_uid      int             系统推送来源id[0 表示不是推送来的]
	PRIMARY(id)
	key(uid, result, hide)       #我的活动，己经束活动,查看他人活动
	key(uid, hide)               #查看他人己结束活动
	key(type, event_id, uid)
	key(c_endtime)


